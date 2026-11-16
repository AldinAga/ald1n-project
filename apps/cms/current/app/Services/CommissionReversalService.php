<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AfterSalesAction;
use App\Models\CommissionAdjustment;
use App\Models\OrderCommission;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Storno provizije pri povratu artikla (odluka vlasnika 16.11.2026).
 * Neisplaćena provizija (pending/approved): umanjuje se order_commissions.total_eur, a red u
 * commission_adjustments (kind=reversal) čuva trag. Isplaćena provizija se ne menja: upisuje se
 * zaduženje (kind=debit) koje se vidi kao dugovanje korisnika. Iznosi se računaju u centima (int).
 */
final class CommissionReversalService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CommissionWorkflowService $workflow,
        private readonly OperationalNotificationService $notifications,
    ) {}

    /** Ručni storno. Iznos ili artikal+količina; bez oba = ceo preostali iznos. */
    public function reverse(OrderCommission $commission, User $actor, string $reason, ?string $amountEur = null, ?int $orderItemId = null, ?int $quantity = null): CommissionAdjustment
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Razlog storniranja je obavezan.']);
        }

        $adjustment = DB::transaction(function () use ($commission, $actor, $reason, $amountEur, $orderItemId, $quantity): CommissionAdjustment {
            /** @var OrderCommission $locked */
            $locked = OrderCommission::query()->with('order')->lockForUpdate()->findOrFail($commission->id);
            $this->workflow->authorize($locked, $actor);
            if ($locked->status === 'cancelled') {
                throw ValidationException::withMessages(['reason' => 'Provizija je već stornirana.']);
            }
            if ($locked->status === 'paid' && !$actor->hasRole('superadmin')) {
                throw ValidationException::withMessages(['reason' => 'Zaduženje na isplaćenu proviziju može da upiše samo SuperAdministrator.']);
            }

            $available = $this->availableCents($locked);
            if ($available <= 0) {
                throw ValidationException::withMessages(['amount_eur' => 'Nema preostalog iznosa za storniranje.']);
            }

            if ($orderItemId !== null) {
                $item = OrderItem::query()->where('order_id', $locked->order_id)->whereKey($orderItemId)->first();
                if (!$item instanceof OrderItem) {
                    throw ValidationException::withMessages(['order_item_id' => 'Artikal ne pripada ovoj porudžbini.']);
                }
                $qty = $quantity ?? 1;
                $remaining = $this->remainingQuantity($locked, $item);
                if ($qty < 1 || $qty > $remaining) {
                    throw ValidationException::withMessages(['quantity' => 'Količina mora biti između 1 i '.$remaining.'.']);
                }
                $cents = $this->unitCents($item) * $qty;
                if ($cents <= 0) {
                    throw ValidationException::withMessages(['order_item_id' => 'Za ovaj artikal nema obračunate provizije.']);
                }
                $cents = min($cents, $available);
            } elseif ($amountEur === null || trim($amountEur) === '') {
                $cents = $available;
                $qty = null;
                $item = null;
            } else {
                $cents = $this->parseCents($amountEur);
                $qty = null;
                $item = null;
                if ($cents <= 0 || $cents > $available) {
                    throw ValidationException::withMessages(['amount_eur' => 'Iznos mora biti veći od nule i najviše '.$this->format($available).' EUR.']);
                }
            }

            return $this->apply($locked, $cents, $reason, 'manual', $actor, $item?->id, $qty, null);
        }, 5);

        $this->notifyOwner($commission, $adjustment);

        return $adjustment;
    }

    /**
     * Automatski storno kad je izvršena postprodajna radnja "return_receipt" (povrat robe) sa identifikovanim
     * artiklima. Poziva se UNUTAR transakcije radnje; idempotentno po after_sales_action_items.id.
     *
     * @return int broj kreiranih storno stavki
     */
    public function reverseForReturnAction(AfterSalesAction $action, User $actor): int
    {
        if ($action->action_type !== 'return_receipt') {
            return 0;
        }
        $action->loadMissing(['items.caseItem', 'case']);
        $orderId = (int) $action->case?->order_id;
        if ($orderId <= 0) {
            return 0;
        }

        /** @var OrderCommission|null $commission */
        $commission = OrderCommission::query()->where('order_id', $orderId)->lockForUpdate()->first();
        if (!$commission instanceof OrderCommission || $commission->status === 'cancelled') {
            return 0;
        }

        $created = 0;
        foreach ($action->items as $actionItem) {
            $orderItemId = $actionItem->caseItem?->order_item_id;
            if ($orderItemId === null) {
                continue; // stavka povrata ne identifikuje artikal porudžbine: samo ručni storno
            }
            if (CommissionAdjustment::query()->where('after_sales_action_item_id', $actionItem->id)->exists()) {
                continue;
            }
            $item = OrderItem::query()->where('order_id', $orderId)->whereKey((int) $orderItemId)->first();
            if (!$item instanceof OrderItem) {
                continue;
            }
            $qty = min((int) $actionItem->quantity, $this->remainingQuantity($commission, $item));
            $available = $this->availableCents($commission);
            $cents = min($this->unitCents($item) * $qty, $available);
            if ($qty < 1 || $cents <= 0) {
                continue;
            }
            $this->apply(
                $commission,
                $cents,
                'Povrat artikla (postprodajna radnja '.$action->action_number.')',
                'after_sales_return',
                $actor,
                (int) $item->id,
                $qty,
                (int) $actionItem->id,
            );
            $commission->refresh();
            $created++;
        }

        return $created;
    }

    /** Neto iznos koji se još može stornirati. */
    public function availableCents(OrderCommission $commission): int
    {
        $total = $this->parseCents((string) $commission->total_eur);
        if ($commission->status !== 'paid') {
            return $total;
        }
        $debited = $this->sumCents(CommissionAdjustment::query()->where('commission_id', $commission->id)->where('kind', 'debit')->pluck('amount_eur')->all());

        return max(0, $total - $debited);
    }

    public function remainingQuantity(OrderCommission $commission, OrderItem $item): int
    {
        $done = (int) CommissionAdjustment::query()->where('commission_id', $commission->id)->where('order_item_id', $item->id)->sum('quantity');

        return max(0, (int) $item->quantity - $done);
    }

    public function parseCents(string $value): int
    {
        $value = str_replace(',', '.', trim($value));
        if (!preg_match('/^(\d{1,9})(?:\.(\d{1,2}))?$/', $value, $m)) {
            return 0;
        }

        return ((int) $m[1]) * 100 + (int) str_pad($m[2] ?? '0', 2, '0');
    }

    public function format(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }

    private function unitCents(OrderItem $item): int
    {
        return $this->parseCents((string) ($item->commission_unit_eur_snapshot ?? '0'));
    }

    /** @param list<mixed> $values */
    private function sumCents(array $values): int
    {
        return array_sum(array_map(fn (mixed $v): int => $this->parseCents((string) $v), $values));
    }

    private function apply(OrderCommission $locked, int $cents, string $reason, string $source, User $actor, ?int $orderItemId, ?int $quantity, ?int $actionItemId): CommissionAdjustment
    {
        $paid = $locked->status === 'paid';
        $adjustment = CommissionAdjustment::query()->create([
            'commission_id' => $locked->id,
            'order_id' => $locked->order_id,
            'order_item_id' => $orderItemId,
            'kind' => $paid ? 'debit' : 'reversal',
            'amount_eur' => $this->format($cents),
            'quantity' => $quantity,
            'reason' => mb_substr($reason, 0, 1000),
            'source' => $source,
            'after_sales_action_item_id' => $actionItemId,
            'created_by' => $actor->id,
        ]);

        $oldStatus = (string) $locked->status;
        $newStatus = $oldStatus;
        if (!$paid) {
            $newTotal = $this->parseCents((string) $locked->total_eur) - $cents;
            $changes = ['total_eur' => $this->format($newTotal), 'status_updated_at' => now()];
            if ($newTotal <= 0) {
                $newStatus = 'cancelled';
                $changes += ['status' => 'cancelled', 'status_note' => $reason, 'cancelled_by' => $actor->id, 'cancelled_at' => now()];
            }
            $locked->update($changes);
        }

        DB::table('commission_status_history')->insert([
            'commission_id' => $locked->id,
            'order_id' => $locked->order_id,
            'changed_by' => $actor->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => ($paid ? 'Zaduženje ' : 'Storno ').$this->format($cents).' EUR: '.$reason,
            'metadata_json' => json_encode(['adjustment_id' => $adjustment->id, 'kind' => $adjustment->kind, 'source' => $source], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
        $this->audit->log(
            $paid ? 'commission.debit_recorded' : 'commission.reversed',
            sprintf('Provizija #%d: %s %s EUR', $locked->id, $paid ? 'zaduženje' : 'storno', $this->format($cents)),
            $locked,
            before: ['status' => $oldStatus],
            after: ['status' => $newStatus, 'total_eur' => (string) $locked->total_eur],
            metadata: ['adjustment_id' => $adjustment->id, 'amount_eur' => $this->format($cents), 'order_item_id' => $orderItemId, 'quantity' => $quantity, 'source' => $source, 'reason' => $reason],
            user: $actor,
        );

        return $adjustment;
    }

    private function notifyOwner(OrderCommission $commission, CommissionAdjustment $adjustment): void
    {
        $owner = $commission->user()->first();
        if (!$owner instanceof User) {
            return;
        }
        $paid = $adjustment->kind === 'debit';
        $this->notifications->commission(
            $owner,
            $paid ? 'commission.debit' : 'commission.reversed',
            $paid ? 'Zaduženje na isplaćenu proviziju' : 'Provizija je delimično stornirana',
            sprintf('Provizija za porudžbinu #%d umanjena je za %s EUR. Razlog: %s', $commission->order_id, number_format((float) $adjustment->amount_eur, 2, ',', '.'), $adjustment->reason),
            $commission,
        );
    }
}
