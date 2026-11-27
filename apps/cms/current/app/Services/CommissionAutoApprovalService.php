<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Odluka vlasnika 16.11.2026: nema ručnog "Odobri". Čim je uslov dospelosti ispunjen (puna naplata ILI potvrđena
 * isporuka, šta prvo nastupi), provizija sama prelazi pending -> approved ("Za isplatu"). Poziva se iz iste
 * transakcije kao događaj (OrderFinancialStateService::projectLocked i ponovno otvaranje porudžbine), idempotentno.
 * Ako uslov prestane da važi pre isplate, automatski odobrena provizija (approved_by IS NULL) se vraća na pending.
 * Ručno odobrene (nasleđene, approved_by popunjen) i isplaćene provizije se nikad ne diraju.
 */
final class CommissionAutoApprovalService
{
    public function __construct(
        private readonly CommissionEligibilityService $eligibility,
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
        private readonly CommissionReversalService $reversals,
    ) {}

    /** @return string|null 'approved' | 'pending' ako se stanje promenilo, inače null */
    public function syncForOrder(Order $order, string $source = 'order_event', bool $notify = true, bool $notifyReversal = false): ?string
    {
        if (!$this->ready()) {
            return null; // migracija još nije izvršena: bez automatike, plaćanja i isporuke rade kao pre
        }

        return DB::transaction(function () use ($order, $source, $notify, $notifyReversal): ?string {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);
            /** @var OrderCommission|null $commission */
            $commission = OrderCommission::query()->where('order_id', $order->id)->lockForUpdate()->first();
            if (!$commission instanceof OrderCommission) {
                return null;
            }
            $meets = $this->eligibility->orderMeetsCondition($order);

            if ($commission->status === 'pending' && $meets) {
                // refundacija pre dospelosti (npr. refundacija pa isporuka) uračunava se pre "Za isplatu", istim pravilom kao refundacija
                $this->reversals->reverseForRefund($order, null, 'auto-approval', CommissionReversalService::EVENT_APPROVAL, $notify || $notifyReversal);
                $commission = OrderCommission::query()->lockForUpdate()->findOrFail($commission->id);
                if ($commission->status !== 'pending') {
                    return null;
                }
                $reason = $order->completed_at !== null
                    ? 'Automatski za isplatu: isporuka je potvrđena.'
                    : 'Automatski za isplatu: porudžbina je u potpunosti naplaćena.';
                $this->move($commission, 'pending', 'approved', $reason, $source, [
                    'approved_by' => null, 'approved_at' => now(), 'auto_approved_at' => now(),
                ]);
                if ($notify) {
                    DB::afterCommit(fn () => $this->notifyOwner($commission, $order));
                }

                return 'approved';
            }

            if ($commission->status === 'approved' && $commission->auto_approved_at !== null && !$meets) {
                $this->move($commission, 'approved', 'pending', 'Automatski vraćena na čekanje: porudžbina više nije isporučena ni u potpunosti naplaćena.', $source, [
                    'approved_at' => null, 'auto_approved_at' => null,
                ]);

                return 'pending';
            }

            return null;
        }, 5);
    }

    private function ready(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasColumn('order_commissions', 'auto_approved_at');
    }

    /** Idempotentni prenos dospelih provizija. Migracija podrazumevano radi bez obaveštenja. */
    public function backfill(?array $onlyOrderIds = null, bool $notifyReversal = false): int
    {
        $count = 0;
        $query = $this->eligibility->scopeState(OrderCommission::query(), 'due');
        if ($onlyOrderIds !== null) { // pozivalac suzava na narudzbine koje je sam oslobodio; ostale pending provizije se ne diraju
            $query->whereIn('order_commissions.order_id', $onlyOrderIds);
        }
        $ids = $query->pluck('order_commissions.order_id');
        foreach ($ids as $orderId) {
            $order = Order::query()->find($orderId);
            if ($order instanceof Order && $this->syncForOrder($order, 'backfill_20261116', notify: false, notifyReversal: $notifyReversal) === 'approved') {
                $count++;
            }
        }

        return $count;
    }

    /** @param array<string,mixed> $extra */
    private function move(OrderCommission $commission, string $from, string $to, string $note, string $source, array $extra): void
    {
        $commission->update(['status' => $to, 'status_note' => $note, 'status_updated_at' => now()] + $extra);
        DB::table('commission_status_history')->insert([
            'commission_id' => $commission->id,
            'order_id' => $commission->order_id,
            'changed_by' => null,
            'old_status' => $from,
            'new_status' => $to,
            'note' => $note,
            'metadata_json' => json_encode(['source' => $source, 'automatic' => true], JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
        $this->audit->log(
            $to === 'approved' ? 'commission.auto_approved' : 'commission.auto_reverted',
            sprintf('Provizija #%d: %s -> %s (automatski)', $commission->id, $from, $to),
            $commission,
            before: ['status' => $from],
            after: ['status' => $to],
            metadata: ['source' => $source, 'note' => $note],
        );
    }

    private function notifyOwner(OrderCommission $commission, Order $order): void
    {
        try {
            $user = $commission->user()->first();
            if (!$user instanceof User) {
                return;
            }
            $this->notifications->commission(
                $user,
                'commission.approved',
                'Provizija je za isplatu',
                sprintf('Provizija za porudžbinu %s iznosi %s EUR i sada je za isplatu.', $order->order_number, number_format((float) $commission->total_eur, 2, ',', '.')),
                $commission,
            );
        } catch (Throwable) {
            // obaveštenje ne sme da poništi finansijski događaj
        }
    }
}
