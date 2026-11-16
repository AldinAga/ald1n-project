<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AfterSalesAction;
use App\Models\CommissionAdjustment;
use App\Models\OrderCommission;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\CommissionMoney as M;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Storno provizije pri povratu artikla (odluka vlasnika 16.11.2026), zaduženja i prebijanje pri isplati.
 *  - Neisplaćena provizija (pending/approved): umanjuje se order_commissions.total_eur; red kind=reversal čuva trag.
 *  - Isplaćena provizija se ne menja. Zaduženje (kind=debit) je status=confirmed (SuperAdmin ručno, otkazivanje porudžbine)
 *    ili status=proposed (automatski pri povratu): predlog ne važi dok ga SuperAdmin ne potvrdi.
 *  - Iznosi su celi centi. Količina vraćenog artikla se prati po stavci porudžbine preko ručnih i automatskih redova.
 */
final class CommissionReversalService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
    ) {}

    /** Ručni storno: ili iznos, ili artikal + količina; bez oba = ceo preostali iznos (samo ako porudžbina nema povrata). */
    public function reverse(OrderCommission $commission, User $actor, string $reason, ?string $amountEur = null, ?int $orderItemId = null, ?int $quantity = null): CommissionAdjustment
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'Razlog storniranja je obavezan.']);
        }
        $hasAmount = $amountEur !== null && trim($amountEur) !== '';
        if ($hasAmount && $orderItemId !== null) {
            throw ValidationException::withMessages(['amount_eur' => 'Unesi ili iznos ili artikal sa količinom, ne oba.']);
        }

        $adjustment = DB::transaction(function () use ($commission, $actor, $reason, $amountEur, $hasAmount, $orderItemId, $quantity): CommissionAdjustment {
            /** @var OrderCommission $locked */
            $locked = OrderCommission::query()->with('order')->lockForUpdate()->findOrFail($commission->id);
            $this->authorize($locked, $actor);
            if ($locked->status === 'cancelled') {
                throw ValidationException::withMessages(['reason' => 'Provizija je već stornirana.']);
            }
            if ($locked->status === 'paid' && !$actor->hasRole('superadmin')) {
                throw ValidationException::withMessages(['commission' => 'Zaduženje na isplaćenu proviziju može da upiše samo SuperAdministrator.']);
            }
            if ($orderItemId === null && $this->orderHasReturns((int) $locked->order_id)) {
                throw ValidationException::withMessages(['order_item_id' => 'Za porudžbinu sa povratom izaberi artikal i količinu, da isti komad ne bude storniran dva puta.']);
            }

            $available = $this->availableCents($locked);
            if ($available <= 0) {
                throw ValidationException::withMessages(['amount_eur' => 'Nema preostalog iznosa za storniranje.']);
            }

            $item = null;
            $qty = null;
            if ($orderItemId !== null) {
                $item = OrderItem::query()->where('order_id', $locked->order_id)->whereKey($orderItemId)->first();
                if (!$item instanceof OrderItem) {
                    throw ValidationException::withMessages(['order_item_id' => 'Artikal ne pripada ovoj porudžbini.']);
                }
                $qty = $quantity ?? 1;
                $remaining = $this->remainingQuantity($locked, $item);
                if ($qty < 1 || $qty > $remaining) {
                    throw ValidationException::withMessages(['quantity' => 'Za ovaj artikal je preostalo '.$remaining.' kom za storniranje.']);
                }
                $cents = $this->unitCents($item) * $qty;
                if ($cents <= 0) {
                    throw ValidationException::withMessages(['order_item_id' => 'Za ovaj artikal nema obračunate provizije.']);
                }
                if ($cents > $available) {
                    throw ValidationException::withMessages(['quantity' => 'Iznos '.M::format($cents).' EUR prelazi preostalih '.M::format($available).' EUR.']);
                }
            } elseif (!$hasAmount) {
                $cents = $available;
            } else {
                $cents = M::cents((string) $amountEur);
                if ($cents <= 0 || $cents > $available) {
                    throw ValidationException::withMessages(['amount_eur' => 'Iznos mora biti veći od nule i najviše '.M::format($available).' EUR.']);
                }
            }

            return $this->apply($locked, $cents, $reason, 'manual', $actor, $item?->id, $qty, null, 'confirmed');
        }, 5);

        $this->notifyOwner($commission, $adjustment);

        return $adjustment;
    }

    /** Otkazivanje porudžbine sa već isplaćenom provizijom: isplata ostaje, automatski potvrđeno zaduženje za ceo preostali iznos. */
    public function debitForCancellation(OrderCommission $locked, User $actor, string $note): ?CommissionAdjustment
    {
        if ($locked->status !== 'paid'
            || CommissionAdjustment::query()->where('commission_id', $locked->id)->where('source', 'order_cancellation')->exists()) {
            return null;
        }
        $available = $this->availableCents($locked);
        if ($available <= 0) {
            return null;
        }
        $adjustment = $this->apply($locked, $available, 'Porudžbina je otkazana posle isplate provizije. '.$note, 'order_cancellation', $actor, null, null, null, 'confirmed');
        $this->notifyOwner($locked, $adjustment);

        return $adjustment;
    }

    /**
     * Automatski storno kad je izvršena postprodajna radnja "return_receipt" sa identifikovanim artiklima.
     * Poziva se UNUTAR transakcije radnje; idempotentno po after_sales_action_items.id. Neisplaćena provizija se umanjuje;
     * isplaćena dobija PREDLOG zaduženja (status=proposed) koji SuperAdmin potvrđuje ili odbija.
     *
     * @return int broj kreiranih stavki
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
        // Ručni storno samo iznosom ne zna koji je komad pokrio: ne dupliramo, SuperAdmin odlučuje ručno.
        if (CommissionAdjustment::query()->where('commission_id', $commission->id)->whereNull('order_item_id')->where('status', '!=', 'rejected')->exists()) {
            $this->audit->log('commission.auto_reversal_skipped', sprintf('Provizija #%d: povrat %s nije automatski stornirana (postoji ručni storno bez artikla)', $commission->id, $action->action_number), $commission, metadata: ['action_id' => $action->id], user: $actor);

            return 0;
        }

        $paid = $commission->status === 'paid';
        $created = 0;
        foreach ($action->items as $actionItem) {
            $orderItemId = $actionItem->caseItem?->order_item_id;
            if ($orderItemId === null) {
                continue;
            }
            if (CommissionAdjustment::query()->where('after_sales_action_item_id', $actionItem->id)->exists()) {
                continue;
            }
            $item = OrderItem::query()->where('order_id', $orderId)->whereKey((int) $orderItemId)->first();
            if (!$item instanceof OrderItem) {
                continue;
            }
            $qty = min((int) $actionItem->quantity, $this->remainingQuantity($commission, $item));
            $cents = min($this->unitCents($item) * $qty, $this->availableCents($commission));
            if ($qty < 1 || $cents <= 0) {
                continue;
            }
            $adjustment = $this->apply(
                $commission,
                $cents,
                'Povrat artikla (postprodajna radnja '.$action->action_number.')',
                'after_sales_return',
                $actor,
                (int) $item->id,
                $qty,
                (int) $actionItem->id,
                $paid ? 'proposed' : 'confirmed',
            );
            $commission->refresh();
            $created++;
            if ($paid) {
                $this->notifySuperAdmins($commission, $adjustment);
            }
        }

        return $created;
    }

    /** SuperAdmin potvrđuje predlog zaduženja. */
    public function confirmDebit(CommissionAdjustment $adjustment, User $actor): CommissionAdjustment
    {
        $this->assertSuperAdmin($actor);
        $result = DB::transaction(function () use ($adjustment, $actor): CommissionAdjustment {
            /** @var CommissionAdjustment $locked */
            $locked = CommissionAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);
            $commission = OrderCommission::query()->lockForUpdate()->findOrFail($locked->commission_id);
            if ($locked->kind !== 'debit' || $locked->status !== 'proposed') {
                throw ValidationException::withMessages(['adjustment' => 'Samo predlog zaduženja može da se potvrdi.']);
            }
            $others = $this->debitCents($commission, ['confirmed']);
            if ($others + M::cents($locked->amount_eur) > M::cents($commission->total_eur)) {
                throw ValidationException::withMessages(['adjustment' => 'Zaduženje prelazi preostali isplaćeni iznos provizije.']);
            }
            $locked->update(['status' => 'confirmed', 'decided_by' => $actor->id, 'decided_at' => now()]);
            $this->audit->log('commission.debit_confirmed', sprintf('Provizija #%d: zaduženje %s EUR potvrđeno', $commission->id, $locked->amount_eur), $commission, metadata: ['adjustment_id' => $locked->id], user: $actor);

            return $locked->fresh();
        }, 5);
        $this->notifyOwner(OrderCommission::query()->findOrFail($result->commission_id), $result, 'commission.debit_confirmed', 'Zaduženje je potvrđeno');

        return $result;
    }

    public function rejectDebit(CommissionAdjustment $adjustment, User $actor, string $note): CommissionAdjustment
    {
        $this->assertSuperAdmin($actor);
        $note = trim($note);
        if ($note === '') {
            throw ValidationException::withMessages(['note' => 'Razlog odbijanja je obavezan.']);
        }

        return DB::transaction(function () use ($adjustment, $actor, $note): CommissionAdjustment {
            /** @var CommissionAdjustment $locked */
            $locked = CommissionAdjustment::query()->lockForUpdate()->findOrFail($adjustment->id);
            if ($locked->kind !== 'debit' || $locked->status !== 'proposed') {
                throw ValidationException::withMessages(['adjustment' => 'Samo predlog zaduženja može da se odbije.']);
            }
            $locked->update(['status' => 'rejected', 'decided_by' => $actor->id, 'decided_at' => now(), 'decision_note' => mb_substr($note, 0, 500)]);
            $this->audit->log('commission.debit_rejected', sprintf('Provizija #%d: predlog zaduženja odbijen', $locked->commission_id), $locked, metadata: ['note' => $note], user: $actor);

            return $locked->fresh();
        }, 5);
    }

    /** Neto iznos koji se još može stornirati (isplaćena: umanjeno za potvrđena i predložena zaduženja). */
    public function availableCents(OrderCommission $commission): int
    {
        $total = M::cents($commission->total_eur);

        return $commission->status !== 'paid' ? $total : max(0, $total - $this->debitCents($commission, ['confirmed', 'proposed']));
    }

    public function remainingQuantity(OrderCommission $commission, OrderItem $item): int
    {
        $done = (int) CommissionAdjustment::query()->where('commission_id', $commission->id)->where('order_item_id', $item->id)
            ->where('status', '!=', 'rejected')->sum('quantity');

        return max(0, (int) $item->quantity - $done);
    }

    /** Otvorena potvrđena zaduženja korisnika: [adjustment_id => preostali centi], najstarija prva. @return array<int,int> */
    public function openDebitsForUser(int $userId): array
    {
        $rows = CommissionAdjustment::query()->where('kind', 'debit')->where('status', 'confirmed')
            ->whereIn('commission_id', OrderCommission::query()->where('user_id', $userId)->select('id'))->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return [];
        }
        $settled = DB::table('commission_debit_settlements')->whereIn('adjustment_id', $rows->pluck('id'))
            ->selectRaw('adjustment_id, SUM(amount_eur) AS s')->groupBy('adjustment_id')->pluck('s', 'adjustment_id');
        $open = [];
        foreach ($rows as $row) {
            $left = M::cents($row->amount_eur) - M::cents($settled[$row->id] ?? 0);
            if ($left > 0) {
                $open[(int) $row->id] = $left;
            }
        }

        return $open;
    }

    public function hasOpenDebits(OrderCommission $commission): bool
    {
        return CommissionAdjustment::query()->where('commission_id', $commission->id)->where('kind', 'debit')->whereIn('status', ['confirmed', 'proposed'])->exists();
    }

    private function orderHasReturns(int $orderId): bool
    {
        return AfterSalesAction::query()->where('action_type', 'return_receipt')->where('status', '!=', 'cancelled')
            ->whereIn('after_sales_case_id', DB::table('after_sales_cases')->where('order_id', $orderId)->select('id'))->exists();
    }

    /** @param list<string> $statuses */
    private function debitCents(OrderCommission $commission, array $statuses): int
    {
        $sum = 0;
        foreach (CommissionAdjustment::query()->where('commission_id', $commission->id)->where('kind', 'debit')->whereIn('status', $statuses)->pluck('amount_eur') as $v) {
            $sum += M::cents($v);
        }

        return $sum;
    }

    private function unitCents(OrderItem $item): int
    {
        return M::cents($item->commission_unit_eur_snapshot ?? '0');
    }

    private function apply(OrderCommission $locked, int $cents, string $reason, string $source, User $actor, ?int $orderItemId, ?int $quantity, ?int $actionItemId, string $status): CommissionAdjustment
    {
        $paid = $locked->status === 'paid';
        $adjustment = CommissionAdjustment::query()->create([
            'commission_id' => $locked->id,
            'order_id' => $locked->order_id,
            'order_item_id' => $orderItemId,
            'kind' => $paid ? 'debit' : 'reversal',
            'status' => $status,
            'amount_eur' => M::format($cents),
            'quantity' => $quantity,
            'reason' => mb_substr($reason, 0, 1000),
            'source' => $source,
            'after_sales_action_item_id' => $actionItemId,
            'created_by' => $actor->id,
        ]);

        $oldStatus = (string) $locked->status;
        $newStatus = $oldStatus;
        if (!$paid) {
            $newTotal = M::cents($locked->total_eur) - $cents;
            $changes = ['total_eur' => M::format($newTotal), 'status_updated_at' => now()];
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
            'note' => ($paid ? ($status === 'proposed' ? 'Predlog zaduženja ' : 'Zaduženje ') : 'Storno ').M::format($cents).' EUR: '.$reason,
            'metadata_json' => json_encode(['adjustment_id' => $adjustment->id, 'kind' => $adjustment->kind, 'status' => $status, 'source' => $source], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'created_at' => now(),
        ]);
        $this->audit->log(
            $paid ? ($status === 'proposed' ? 'commission.debit_proposed' : 'commission.debit_recorded') : 'commission.reversed',
            sprintf('Provizija #%d: %s %s EUR', $locked->id, $paid ? 'zaduženje' : 'storno', M::format($cents)),
            $locked,
            before: ['status' => $oldStatus],
            after: ['status' => $newStatus, 'total_eur' => (string) $locked->total_eur],
            metadata: ['adjustment_id' => $adjustment->id, 'amount_eur' => M::format($cents), 'order_item_id' => $orderItemId, 'quantity' => $quantity, 'source' => $source, 'status' => $status, 'reason' => $reason],
            user: $actor,
        );

        return $adjustment;
    }

    private function authorize(OrderCommission $commission, User $actor): void
    {
        if ($actor->hasRole('superadmin')) {
            return;
        }
        if ($actor->hasRole('admin') && (int) $commission->order?->supplier_user_id === (int) $actor->id) {
            return;
        }
        abort(404);
    }

    private function assertSuperAdmin(User $actor): void
    {
        if (!$actor->hasRole('superadmin')) {
            abort(403);
        }
    }

    private function notifyOwner(OrderCommission $commission, CommissionAdjustment $adjustment, string $event = '', string $title = ''): void
    {
        try {
            $owner = $commission->user()->first();
            if (!$owner instanceof User) {
                return;
            }
            $paid = $adjustment->kind === 'debit';
            $this->notifications->commission(
                $owner,
                $event !== '' ? $event : ($paid ? 'commission.debit' : 'commission.reversed'),
                $title !== '' ? $title : ($paid ? 'Zaduženje na isplaćenu proviziju' : 'Provizija je delimično stornirana'),
                sprintf('Provizija za porudžbinu #%d umanjena je za %s EUR. Razlog: %s', $commission->order_id, number_format((float) $adjustment->amount_eur, 2, ',', '.'), $adjustment->reason),
                $commission,
            );
        } catch (Throwable) {
            // obaveštenje ne sme da poništi finansijsku operaciju
        }
    }

    private function notifySuperAdmins(OrderCommission $commission, CommissionAdjustment $adjustment): void
    {
        try {
            $admins = User::query()->whereHas('role', static fn ($role) => $role->where('slug', 'superadmin'))->where('status', 'active')->get();
            foreach ($admins as $admin) {
                $this->notifications->commission(
                    $admin,
                    'commission.debit_proposed',
                    'Predlog zaduženja čeka potvrdu',
                    sprintf('Povrat artikla na porudžbini #%d: predloženo zaduženje %s EUR na isplaćenu proviziju.', $commission->order_id, number_format((float) $adjustment->amount_eur, 2, ',', '.')),
                    $commission,
                );
            }
        } catch (Throwable) {
            // obaveštenje ne sme da poništi povrat
        }
    }
}
