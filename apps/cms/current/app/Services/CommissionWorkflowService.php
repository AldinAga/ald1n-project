<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CommissionPaymentBatch;
use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Support\CommissionMoney as M;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class CommissionWorkflowService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
        private readonly CommissionEligibilityService $eligibility,
        private readonly CommissionReversalService $reversals,
    ) {}

    /** @param array<string,mixed> $data */
    public function transition(OrderCommission $commission, string $newStatus, User $actor, array $data = []): OrderCommission
    {
        if ($newStatus === 'paid') {
            // Odluka vlasnika 16.11.2026: isplatu radi samo SuperAdmin; pojedinačna isplata ide kroz isti batch (prebijanje zaduženja).
            $this->assertSuperAdminPayout($actor);
            $current = OrderCommission::query()->find($commission->id);
            if ($current instanceof OrderCommission && $current->status === 'approved') {
                $this->markPaidBulk([(int) $current->id], $actor, $data);

                return OrderCommission::query()->with(['order', 'user', 'history.actor', 'paymentBatch'])->findOrFail($current->id);
            }
        }
        $updated = DB::transaction(function () use ($commission, $newStatus, $actor, $data): OrderCommission {
            $order = Order::query()->lockForUpdate()->findOrFail($commission->order_id);
            /** @var OrderCommission $locked */
            $locked = OrderCommission::query()->with('user')->where('order_id', $order->id)->lockForUpdate()->findOrFail($commission->id);
            $locked->setRelation('order', $order);
            $this->authorize($locked, $actor);
            $oldStatus = (string) $locked->status;

            if ($oldStatus === $newStatus) {
                return $locked;
            }

            if ($oldStatus === 'paid' && $newStatus === 'cancelled') {
                throw ValidationException::withMessages(['status' => 'Isplaćena provizija se ne otkazuje ručno jer bi isplata ostala bez traga. Koristi „Storniraj proviziju“ (beleži zaduženje).']);
            }
            if ($oldStatus === 'pending' && $newStatus === 'approved') {
                throw ValidationException::withMessages(['status' => 'Odobravanje je automatsko: provizija postaje "Za isplatu" čim je porudžbina u potpunosti plaćena ili isporučena. '.$this->eligibility->evaluate($locked)['reason']]);
            }
            if ($oldStatus === 'pending' && $newStatus === 'paid') {
                throw ValidationException::withMessages(['status' => 'Isplata je moguća samo za proviziju "Za isplatu". '.$this->eligibility->evaluate($locked)['reason']]);
            }
            $this->assertTransition($oldStatus, $newStatus, $actor);
            if ($oldStatus === 'cancelled' && $newStatus === 'pending') {
                if (M::cents($locked->total_eur) <= 0) {
                    throw ValidationException::withMessages(['status' => 'Provizija je u potpunosti stornirana (0,00 EUR) i ne može se vratiti na čekanje.']);
                }
                if ($this->reversals->hasOpenDebits($locked)) {
                    throw ValidationException::withMessages(['status' => 'Provizija ima zaduženja i ne može se vratiti na čekanje.']);
                }
            }
            $note = trim((string) ($data['note'] ?? ''));
            if ($newStatus === 'cancelled' && $note === '') {
                throw ValidationException::withMessages(['note' => 'Razlog storniranja je obavezan.']);
            }

            $changes = [
                'status' => $newStatus,
                'status_note' => $note !== '' ? $note : null,
                'status_updated_at' => now(),
            ];
            $metadata = [];

            if ($newStatus === 'pending') {
                $changes += [
                    'approved_by' => null,
                    'approved_at' => null,
                    'auto_approved_at' => null,
                    'paid_by' => null,
                    'paid_at' => null,
                    'payment_batch_id' => null,
                    'payment_method' => null,
                    'payment_reference' => null,
                    'cancelled_by' => null,
                    'cancelled_at' => null,
                ];
            } elseif ($newStatus === 'paid') {
                $method = $this->paymentMethod((string) ($data['payment_method'] ?? ''));
                $reference = trim((string) ($data['payment_reference'] ?? ''));
                $changes += [
                    'paid_by' => $actor->id,
                    'paid_at' => now(),
                    'payment_method' => $method,
                    'payment_reference' => $reference !== '' ? $reference : null,
                ];
                $metadata = ['payment_method' => $method, 'payment_reference' => $reference !== '' ? $reference : null];
            } elseif ($newStatus === 'cancelled') {
                $changes += ['cancelled_by' => $actor->id, 'cancelled_at' => now()];
            }

            $locked->update($changes);
            $this->history($locked, $actor, $oldStatus, $newStatus, $note, $metadata);
            $this->audit->log(
                'commission.status_changed',
                sprintf('Provizija #%d: %s -> %s', $locked->id, $oldStatus, $newStatus),
                $locked,
                before: ['status' => $oldStatus],
                after: ['status' => $newStatus, 'payment_method' => $locked->payment_method, 'payment_reference' => $locked->payment_reference],
                metadata: ['note' => $note] + $metadata,
                user: $actor,
            );
            if ($newStatus === 'pending' && $locked->order instanceof Order) {
                // "Vrati na čekanje" ponovo procenjuje uslov: ako važi, provizija odmah ide "Za isplatu".
                app(CommissionAutoApprovalService::class)->syncForOrder($locked->order, 'restored_to_pending', false);
            }

            return $locked->fresh(['order', 'user', 'history.actor', 'paymentBatch']);
        }, 5);

        DB::afterCommit(fn () => $this->notifyStatus($updated));
        return $updated;
    }

    /** @param list<int> $commissionIds @param array<string,mixed> $data */
    public function markPaidBulk(array $commissionIds, User $actor, array $data, array &$skipped = []): CommissionPaymentBatch
    {
        $this->assertSuperAdminPayout($actor);
        $ids = array_values(array_unique(array_filter(array_map('intval', $commissionIds), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            throw ValidationException::withMessages(['commissions' => 'Izaberi najmanje jednu odobrenu proviziju.']);
        }
        $method = $this->paymentMethod((string) ($data['payment_method'] ?? ''));
        $reference = trim((string) ($data['payment_reference'] ?? ''));
        $note = trim((string) ($data['note'] ?? ''));
        $paidCommissions = collect();

        $batch = DB::transaction(function () use ($ids, $actor, $method, $reference, $note, &$paidCommissions, &$skipped): CommissionPaymentBatch {
            $query = OrderCommission::query()->whereIn('id', $ids);
            $this->applyActorScope($query, $actor);
            $orderIds = (clone $query)->pluck('order_id');
            $orders = Order::query()->whereIn('id', $orderIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            /** @var Collection<int,OrderCommission> $commissions */
            $commissions = $query->with('user')->whereIn('order_id', $orders->keys())->orderBy('id')->lockForUpdate()->get();
            if ($commissions->count() !== count($ids)) {
                throw ValidationException::withMessages(['commissions' => 'Jedna ili više provizija nisu dostupne za obradu.']);
            }
            foreach ($commissions as $commission) {
                $commission->setRelation('order', $orders->get($commission->order_id));
            }
            // Samo odobrene (a odobrena je uvek prošla pravilo dospelosti ili je nasleđena): ostale se preskaču i prijavljuju.
            $skipped = [];
            foreach ($commissions->filter(static fn (OrderCommission $row): bool => $row->status !== 'approved') as $row) {
                $check = $this->eligibility->evaluate($row);
                $skipped[] = ['id' => (int) $row->id, 'ok' => false, 'message' => $row->status === 'pending'
                    ? 'Preskočena: nije za isplatu. '.$check['reason']
                    : 'Preskočena: isplata je dozvoljena samo za odobrene provizije.'];
            }
            $commissions = $commissions->filter(static fn (OrderCommission $row): bool => $row->status === 'approved')->values();
            if ($commissions->isEmpty()) {
                throw ValidationException::withMessages(['commissions' => 'Nijedna izabrana provizija nije odobrena za isplatu. '.($skipped[0]['message'] ?? '')]);
            }

            // Prebijanje: otvorena potvrđena zaduženja korisnika umanjuju isplatu (najviše do bruto iznosa; ostatak se prenosi).
            $grossByUser = [];
            foreach ($commissions as $row) {
                $grossByUser[(int) $row->user_id] = ($grossByUser[(int) $row->user_id] ?? 0) + M::cents($row->total_eur);
            }
            $settlements = [];
            if (Schema::hasTable('commission_debit_settlements')) {
                $openDebits = $this->reversals->openDebitsForUsers(array_keys($grossByUser));
                foreach ($grossByUser as $userId => $gross) {
                    $budget = $gross;
                    foreach ($openDebits[$userId] ?? [] as $adjustmentId => $left) {
                        if ($budget <= 0) {
                            break;
                        }
                        $settlements[$adjustmentId] = min($budget, $left);
                        $budget -= $settlements[$adjustmentId];
                    }
                }
            }
            $grossCents = array_sum($grossByUser);
            $debitCents = array_sum($settlements);

            $attributes = [
                'batch_number' => 'TMP-'.bin2hex(random_bytes(8)),
                'payment_method' => $method,
                'payment_reference' => $reference !== '' ? $reference : null,
                'note' => $note !== '' ? $note : null,
                'commission_count' => $commissions->count(),
                'total_eur' => M::format($grossCents),
                'paid_by' => $actor->id,
                'paid_at' => now(),
            ];
            if (Schema::hasColumn('commission_payment_batches', 'debit_eur')) {
                $attributes += ['debit_eur' => M::format($debitCents), 'net_eur' => M::format($grossCents - $debitCents)];
            }
            $batch = CommissionPaymentBatch::query()->create($attributes);
            $batch->update(['batch_number' => sprintf('ISPL-%s-%06d', now()->format('Ymd'), $batch->id)]);
            foreach ($settlements as $adjustmentId => $cents) {
                $this->reversals->settleDebit((int) $adjustmentId, (int) $batch->id, $cents);
            }

            foreach ($commissions as $commission) {
                $oldStatus = (string) $commission->status;
                $commission->update([
                    'status' => 'paid',
                    'status_note' => $note !== '' ? $note : null,
                    'status_updated_at' => now(),
                    'paid_by' => $actor->id,
                    'paid_at' => now(),
                    'payment_batch_id' => $batch->id,
                    'payment_method' => $method,
                    'payment_reference' => $reference !== '' ? $reference : $batch->batch_number,
                ]);
                $this->history($commission, $actor, $oldStatus, 'paid', $note, [
                    'batch_number' => $batch->batch_number,
                    'payment_method' => $method,
                    'payment_reference' => $reference !== '' ? $reference : null,
                ]);
            }

            $this->audit->log(
                'commission.bulk_paid',
                'Masovno isplaćene provizije '.$batch->batch_number,
                $batch,
                after: ['count' => $batch->commission_count, 'gross_eur' => M::format($grossCents), 'debit_eur' => M::format($debitCents), 'net_eur' => M::format($grossCents - $debitCents)],
                metadata: ['commission_ids' => $ids, 'payment_method' => $method, 'payment_reference' => $reference],
                user: $actor,
            );
            $paidCommissions = $commissions->map(static fn (OrderCommission $row): OrderCommission => $row->fresh(['order', 'user']));
            return $batch->fresh(['commissions.user', 'payer']);
        }, 5);

        foreach ($paidCommissions as $commission) {
            DB::afterCommit(fn () => $this->notifyStatus($commission));
        }

        return $batch;
    }

    public function reconcileAfterCustomerAmendmentLocked(Order $order, User $actor, bool $itemsChanged): void
    {
        if (!$itemsChanged) return;
        /** @var OrderCommission|null $commission */
        $commission = OrderCommission::query()->where('order_id', $order->id)->lockForUpdate()->first();
        if (!$commission instanceof OrderCommission) return;
        $oldStatus = (string) $commission->status;
        if (in_array($oldStatus, ['paid', 'cancelled'], true)) {
            throw ValidationException::withMessages([
                'items' => 'Stavke se ne mogu menjati preko isplaćene ili stornirane provizije. Obrati se SuperAdministratoru.',
            ]);
        }
        $reversedCents = 0;
        if (Schema::hasTable('commission_adjustments')) {
            foreach (DB::table('commission_adjustments')->where('commission_id', $commission->id)->where('kind', 'reversal')->where('status', 'confirmed')->pluck('amount_eur') as $v) {
                $reversedCents += M::cents($v);
            }
        }
        $itemsCents = 0;
        foreach (DB::table('order_items')->where('order_id', $order->id)->pluck('commission_total_eur_snapshot') as $v) {
            $itemsCents += M::cents($v);
        }
        $total = M::format(max(0, $itemsCents - $reversedCents));
        $changes = ['total_eur' => $total, 'status_updated_at' => now()];
        $note = 'Provizija je preračunata posle izmene stavki porudžbine.';
        if ($oldStatus === 'approved') {
            $changes += [
                'status' => 'pending',
                'status_note' => $note,
                'approved_by' => null,
                'approved_at' => null,
                'auto_approved_at' => null,
                'paid_by' => null,
                'paid_at' => null,
                'payment_batch_id' => null,
                'payment_method' => null,
                'payment_reference' => null,
            ];
        }
        $commission->update($changes);
        if ($oldStatus === 'approved') {
            $this->history($commission, $actor, 'approved', 'pending', $note, ['source' => 'customer_order_amendment']);
            // uslov dospelosti se ponovo proverava: ako i dalje važi, provizija odmah ide "Za isplatu" sa novim iznosom
            app(CommissionAutoApprovalService::class)->syncForOrder($order, 'customer_order_amendment', false);
        }
        $this->audit->log(
            'commission.recalculated_after_customer_amendment',
            'Provizija je preračunata posle izmene porudžbine '.$order->order_number,
            $commission,
            before: ['status' => $oldStatus],
            after: ['status' => (string) $commission->status, 'total_eur' => $total],
            metadata: ['source' => 'customer_order_amendment'],
            user: $actor,
        );
    }

    private function assertSuperAdminPayout(User $actor): void
    {
        if (!$actor->hasRole('superadmin')) {
            abort(403, 'Isplatu provizija radi samo SuperAdministrator.');
        }
    }

    public function authorize(OrderCommission $commission, User $actor): void
    {
        if ($actor->hasRole('superadmin')) return;
        if ($actor->hasRole('admin') && (int) $commission->order?->supplier_user_id === (int) $actor->id) return;
        abort(404);
    }

    /** @param Builder<OrderCommission> $query */
    private function applyActorScope(Builder $query, User $actor): void
    {
        if ($actor->hasRole('superadmin')) return;
        $query->whereHas('order', static fn (Builder $order): Builder => $order->where('supplier_user_id', $actor->id));
    }

    private function assertTransition(string $old, string $new, User $actor): void
    {
        $allowed = [
            'pending' => ['cancelled'],
            'approved' => ['paid', 'cancelled'],
            'paid' => $actor->hasRole('superadmin') ? ['cancelled'] : [],
            'cancelled' => $actor->hasRole('superadmin') ? ['pending'] : [],
        ];
        if (!in_array($new, $allowed[$old] ?? [], true)) {
            throw ValidationException::withMessages(['status' => sprintf('Prelaz provizije %s -> %s nije dozvoljen.', $old, $new)]);
        }
    }

    private function paymentMethod(string $method): string
    {
        if (!in_array($method, ['bank_transfer', 'cash', 'other'], true)) {
            throw ValidationException::withMessages(['payment_method' => 'Izaberi način isplate provizije.']);
        }
        return $method;
    }

    /** @param array<string,mixed> $metadata */
    private function history(OrderCommission $commission, User $actor, string $old, string $new, string $note, array $metadata): void
    {
        DB::table('commission_status_history')->insert([
            'commission_id' => $commission->id,
            'order_id' => $commission->order_id,
            'changed_by' => $actor->id,
            'old_status' => $old,
            'new_status' => $new,
            'note' => $note !== '' ? $note : null,
            'metadata_json' => $metadata !== [] ? json_encode($metadata, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE) : null,
            'created_at' => now(),
        ]);
    }

    private function notifyStatus(OrderCommission $commission): void
    {
        $user = $commission->user;
        if (!$user instanceof User) return;
        $labels = ['pending' => 'na čekanju', 'approved' => 'za isplatu', 'paid' => 'isplaćena', 'cancelled' => 'stornirana'];
        $this->notifications->commission(
            $user,
            'commission.'.$commission->status,
            'Provizija je '.($labels[$commission->status] ?? $commission->status),
            sprintf('Provizija za porudžbinu %s iznosi %s EUR i sada je %s.', $commission->order?->order_number ?? '#'.$commission->order_id, number_format((float) $commission->total_eur, 2, ',', '.'), $labels[$commission->status] ?? $commission->status),
            $commission,
        );
    }
}
