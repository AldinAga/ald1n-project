<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

final class OrderArchiveService
{
    public function __construct(
        private readonly OrderAccessService $access,
        private readonly AuditLogger $audit,
    ) {
    }

    public function archivedQuery(User $actor): Builder
    {
        $query = Order::query()->archived();
        $this->access->applyManagedScope($query, $actor);

        return $query;
    }

    public function paginateArchived(User $actor, string $search = '', int $perPage = 30): LengthAwarePaginator
    {
        $query = $this->archivedQuery($actor)
            ->with(['user:id,username,email,first_name,last_name', 'supplier:id,username,email,first_name,last_name'])
            ->orderByDesc('archived_at')
            ->orderByDesc('id');

        $search = trim($search);
        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(static function (Builder $nested) use ($like): void {
                $nested
                    ->where('order_number', 'like', $like)
                    ->orWhere('shipping_full_name', 'like', $like)
                    ->orWhere('shipping_phone', 'like', $like);
            });
        }

        return $query->paginate(max(10, min(100, $perPage)))->withQueryString();
    }

    public function archive(Order $order, User $actor, string $reason): Order
    {
        $this->access->authorizeManage($order, $actor);
        $reason = trim($reason);

        if (mb_strlen($reason) < 3) {
            throw ValidationException::withMessages([
                'archive_reason' => 'Unesi razlog arhiviranja od najmanje 3 karaktera.',
            ]);
        }

        return DB::transaction(function () use ($order, $actor, $reason): Order {
            /** @var Order $locked */
            $locked = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if ($locked->archived_at !== null) {
                throw ValidationException::withMessages([
                    'archive_reason' => 'Porudžbina je već arhivirana.',
                ]);
            }

            if ($locked->completed_at === null && (string) $locked->status !== 'cancelled') {
                throw ValidationException::withMessages([
                    'archive_reason' => 'Arhivirati se može samo završena ili otkazana porudžbina.',
                ]);
            }

            $this->assertNoActiveAfterSales($locked);

            $before = [
                'status' => (string) $locked->status,
                'completed_at' => $locked->completed_at?->toISOString(),
                'archived_at' => null,
            ];

            $locked->forceFill([
                'archived_at' => now(),
                'archived_by' => $actor->getAuthIdentifier(),
                'archive_reason' => $reason,
                'updated_by' => $actor->getAuthIdentifier(),
            ])->save();

            $this->audit->log(
                'order.archived',
                'Arhivirana porudžbina '.$locked->order_number,
                $locked,
                before: $before,
                after: [
                    'status' => (string) $locked->status,
                    'completed_at' => $locked->completed_at?->toISOString(),
                    'archived_at' => $locked->archived_at?->toISOString(),
                ],
                metadata: ['archive_reason' => $reason],
                user: $actor,
            );

            return $locked->refresh();
        }, 5);
    }


    public function purgeById(int $orderId, User $actor, string $confirmation, string $reason): void
    {
        if (!$actor->hasRole('superadmin')) {
            abort(403);
        }

        $confirmation = trim($confirmation);
        $reason = trim($reason);

        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'purge_reason' => 'Unesi razlog trajnog uklanjanja od najmanje 5 karaktera.',
            ]);
        }

        DB::transaction(function () use ($orderId, $actor, $confirmation, $reason): void {
            $query = Order::query()
                ->whereKey($orderId)
                ->whereNull('purged_at')
                ->lockForUpdate();

            $this->access->applyManagedScope($query, $actor);

            /** @var Order|null $locked */
            $locked = $query->first();
            if (!$locked instanceof Order) {
                abort(404);
            }

            if ($locked->archived_at === null) {
                throw ValidationException::withMessages([
                    'confirmation' => 'Porudžbina mora prvo biti arhivirana pre trajnog uklanjanja iz operativnog sistema.',
                ]);
            }

            if (!hash_equals((string) $locked->order_number, $confirmation)) {
                throw ValidationException::withMessages([
                    'confirmation' => 'Za potvrdu upiši tačan broj porudžbine: '.$locked->order_number.'.',
                ]);
            }

            $before = [
                'status' => (string) $locked->status,
                'completed_at' => $locked->completed_at?->toISOString(),
                'archived_at' => $locked->archived_at?->toISOString(),
                'purged_at' => null,
            ];

            $locked->forceFill([
                'purged_at' => now(),
                'purged_by' => $actor->getAuthIdentifier(),
                'purge_reason' => $reason,
                'updated_by' => $actor->getAuthIdentifier(),
            ])->save();

            $this->audit->log(
                'order.purged_operationally',
                'Porudžbina '.$locked->order_number.' trajno uklonjena iz operativnog sistema',
                $locked,
                before: $before,
                after: [
                    'status' => (string) $locked->status,
                    'archived_at' => $locked->archived_at?->toISOString(),
                    'purged_at' => $locked->purged_at?->toISOString(),
                ],
                metadata: [
                    'purge_reason' => $reason,
                    'financial_legal_history_preserved' => true,
                    'normal_restore_available' => false,
                ],
                user: $actor,
            );
        }, 5);
    }
    public function restoreById(int $orderId, User $actor): Order
    {
        return DB::transaction(function () use ($orderId, $actor): Order {
            $query = Order::query()->archived()->whereKey($orderId)->lockForUpdate();
            $this->access->applyManagedScope($query, $actor);

            /** @var Order|null $locked */
            $locked = $query->first();
            if (!$locked instanceof Order) {
                abort(404);
            }

            $before = [
                'status' => (string) $locked->status,
                'completed_at' => $locked->completed_at?->toISOString(),
                'archived_at' => $locked->archived_at?->toISOString(),
                'archive_reason' => (string) ($locked->archive_reason ?? ''),
            ];

            $locked->forceFill([
                'archived_at' => null,
                'archived_by' => null,
                'archive_reason' => null,
                'updated_by' => $actor->getAuthIdentifier(),
            ])->save();

            $this->audit->log(
                'order.restored',
                'Vraćena porudžbina iz arhive '.$locked->order_number,
                $locked,
                before: $before,
                after: [
                    'status' => (string) $locked->status,
                    'completed_at' => $locked->completed_at?->toISOString(),
                    'archived_at' => null,
                ],
                user: $actor,
            );

            return $locked->refresh();
        }, 5);
    }

    private function assertNoActiveAfterSales(Order $order): void
    {
        if (!Schema::hasTable('after_sales_cases') || !Schema::hasColumn('after_sales_cases', 'order_id')) {
            return;
        }

        $query = DB::table('after_sales_cases')->where('order_id', (int) $order->id);
        if (Schema::hasColumn('after_sales_cases', 'status')) {
            $query->whereNotIn('status', ['closed', 'rejected']);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'archive_reason' => 'Porudžbina ima aktivan postprodajni slučaj. Zatvori ga pre arhiviranja.',
            ]);
        }
    }
}
