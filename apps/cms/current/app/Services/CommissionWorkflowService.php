<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CommissionPaymentBatch;
use App\Models\OrderCommission;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CommissionWorkflowService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly OperationalNotificationService $notifications,
    ) {}

    /** @param array<string,mixed> $data */
    public function transition(OrderCommission $commission, string $newStatus, User $actor, array $data = []): OrderCommission
    {
        $updated = DB::transaction(function () use ($commission, $newStatus, $actor, $data): OrderCommission {
            /** @var OrderCommission $locked */
            $locked = OrderCommission::query()->with(['order', 'user'])->lockForUpdate()->findOrFail($commission->id);
            $this->authorize($locked, $actor);
            $oldStatus = (string) $locked->status;

            if ($oldStatus === $newStatus) {
                return $locked;
            }

            $this->assertTransition($oldStatus, $newStatus, $actor);
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
                    'paid_by' => null,
                    'paid_at' => null,
                    'payment_batch_id' => null,
                    'payment_method' => null,
                    'payment_reference' => null,
                    'cancelled_by' => null,
                    'cancelled_at' => null,
                ];
            } elseif ($newStatus === 'approved') {
                $changes += ['approved_by' => $actor->id, 'approved_at' => now()];
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

            return $locked->fresh(['order', 'user', 'history.actor', 'paymentBatch']);
        }, 5);

        $this->notifyStatus($updated);
        return $updated;
    }

    /** @param list<int> $commissionIds @param array<string,mixed> $data */
    public function markPaidBulk(array $commissionIds, User $actor, array $data): CommissionPaymentBatch
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $commissionIds), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            throw ValidationException::withMessages(['commissions' => 'Izaberi najmanje jednu odobrenu proviziju.']);
        }
        $method = $this->paymentMethod((string) ($data['payment_method'] ?? ''));
        $reference = trim((string) ($data['payment_reference'] ?? ''));
        $note = trim((string) ($data['note'] ?? ''));
        $paidCommissions = collect();

        $batch = DB::transaction(function () use ($ids, $actor, $method, $reference, $note, &$paidCommissions): CommissionPaymentBatch {
            $query = OrderCommission::query()->with(['order', 'user'])->whereIn('id', $ids)->orderBy('id')->lockForUpdate();
            $this->applyActorScope($query, $actor);
            /** @var Collection<int,OrderCommission> $commissions */
            $commissions = $query->get();
            if ($commissions->count() !== count($ids)) {
                throw ValidationException::withMessages(['commissions' => 'Jedna ili više provizija nisu dostupne za obradu.']);
            }
            $invalid = $commissions->first(static fn (OrderCommission $row): bool => $row->status !== 'approved');
            if ($invalid !== null) {
                throw ValidationException::withMessages(['commissions' => 'Masovna isplata je dozvoljena samo za odobrene provizije.']);
            }

            $batch = CommissionPaymentBatch::query()->create([
                'batch_number' => 'TMP-'.bin2hex(random_bytes(8)),
                'payment_method' => $method,
                'payment_reference' => $reference !== '' ? $reference : null,
                'note' => $note !== '' ? $note : null,
                'commission_count' => $commissions->count(),
                'total_eur' => round((float) $commissions->sum('total_eur'), 2),
                'paid_by' => $actor->id,
                'paid_at' => now(),
            ]);
            $batch->update(['batch_number' => sprintf('ISPL-%s-%06d', now()->format('Ymd'), $batch->id)]);

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
                after: ['count' => $batch->commission_count, 'total_eur' => $batch->total_eur],
                metadata: ['commission_ids' => $ids, 'payment_method' => $method, 'payment_reference' => $reference],
                user: $actor,
            );
            $paidCommissions = $commissions->map(static fn (OrderCommission $row): OrderCommission => $row->fresh(['order', 'user']));
            return $batch->fresh(['commissions.user', 'payer']);
        }, 5);

        foreach ($paidCommissions as $commission) {
            $this->notifyStatus($commission);
        }

        return $batch;
    }

    private function authorize(OrderCommission $commission, User $actor): void
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
            'pending' => ['approved', 'cancelled'],
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
        $labels = ['pending' => 'na čekanju', 'approved' => 'odobrena', 'paid' => 'isplaćena', 'cancelled' => 'stornirana'];
        $this->notifications->commission(
            $user,
            'commission.'.$commission->status,
            'Provizija je '.($labels[$commission->status] ?? $commission->status),
            sprintf('Provizija za porudžbinu %s iznosi %s EUR i sada je %s.', $commission->order?->order_number ?? '#'.$commission->order_id, number_format((float) $commission->total_eur, 2, ',', '.'), $labels[$commission->status] ?? $commission->status),
            $commission,
        );
    }
}
