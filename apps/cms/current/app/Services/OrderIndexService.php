<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class OrderIndexService
{
    /** @var array<string,list<string>> */
    private const REQUIRED_SCHEMA = [
        'orders' => [
            'id', 'order_number', 'user_id', 'source_system', 'status', 'payment_status',
            'subtotal_rsd', 'shipping_full_name', 'created_at', 'supplier_user_id', 'supplier_name_snapshot',
            'accepted_at', 'expected_processing_at', 'expected_shipping_at', 'completed_at', 'completed_by',
            'reopened_at', 'reopened_by', 'reopen_reason',
        ],
        'users' => ['id', 'username', 'email', 'first_name', 'last_name', 'status', 'role_id'],
        'roles' => ['id', 'slug'],
        'order_deliveries' => ['id', 'order_id', 'delivery_method', 'delivered_at', 'recipient_name', 'confirmed_by'],
    ];

    /** @return list<string> */
    public function readinessIssues(): array
    {
        $issues = [];

        try {
            foreach (self::REQUIRED_SCHEMA as $table => $columns) {
                if (!Schema::hasTable($table)) {
                    $issues[] = 'Nedostaje tabela '.$table.'.';
                    continue;
                }

                $existing = Schema::getColumnListing($table);
                foreach (array_diff($columns, $existing) as $column) {
                    $issues[] = 'Nedostaje kolona '.$table.'.'.$column.'.';
                }
            }
        } catch (Throwable) {
            $issues[] = 'Laravel baza ili šema porudžbina trenutno nije dostupna.';
        }

        return $issues;
    }

    /**
     * @param array<string,mixed> $filters
     * @return LengthAwarePaginator<int,Order>
     */
    public function paginate(User $actor, array $filters, OrderAccessService $access, int $perPage = 40): LengthAwarePaginator
    {
        $relations = ['user'];
        if (Schema::hasColumn('orders', 'supplier_user_id')) {
            $relations[] = 'supplier';
        }
        if (Schema::hasTable('order_commissions')) {
            $relations[] = 'commission';
        }

        $query = Order::query()->with($relations)->latest('id');
        $access->applyManagedScope($query, $actor);

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(static function (Builder $q) use ($search): void {
                $q->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('shipping_full_name', 'like', '%'.$search.'%')
                    ->orWhereHas('user', static fn (Builder $u): Builder => $u
                        ->where('username', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%'));
            });
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status === 'completed') {
            $query->whereNotNull('completed_at');
        } elseif ($status === 'shipped') {
            $query->where('status', 'shipped')->whereNull('completed_at');
        } elseif (in_array($status, ['new', 'processing', 'confirmed', 'cancelled'], true)) {
            $query->where('status', $status)->whereNull('completed_at');
        }
        if (in_array($filters['payment_status'] ?? null, ['pending', 'paid', 'cancelled'], true)) {
            $query->where('payment_status', $filters['payment_status']);
        }
        if (in_array($filters['source_system'] ?? null, ['legacy', 'laravel'], true)) {
            $query->where('source_system', $filters['source_system']);
        }
        if ($actor->hasRole('superadmin') && (int) ($filters['supplier_user_id'] ?? 0) > 0) {
            $query->where('supplier_user_id', (int) $filters['supplier_user_id']);
        }
        if (filled($filters['date_from'] ?? null)) {
            $query->whereDate('created_at', '>=', (string) $filters['date_from']);
        }
        if (filled($filters['date_to'] ?? null)) {
            $query->whereDate('created_at', '<=', (string) $filters['date_to']);
        }

        $attention = (string) ($filters['attention'] ?? '');
        if ($attention === 'unaccepted') {
            $query->where('source_system', 'laravel')
                ->whereNull('accepted_at')
                ->whereIn('status', ['new', 'processing']);
        } elseif ($attention === 'overdue') {
            $this->applyOverdueScope($query);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /** @return array{unaccepted:int,overdue:int} */
    public function attentionCounts(User $actor, OrderAccessService $access): array
    {
        $base = Order::query()->where('source_system', 'laravel');
        $access->applyManagedScope($base, $actor);

        return [
            'unaccepted' => (clone $base)
                ->whereNull('accepted_at')
                ->whereIn('status', ['new', 'processing'])
                ->count(),
            'overdue' => $this->applyOverdueScope(clone $base)->count(),
        ];
    }

    /** @return Collection<int,User> */
    public function suppliers(User $actor): Collection
    {
        if (!$actor->hasRole('superadmin')) {
            return collect();
        }

        return User::query()
            ->where('status', 'active')
            ->whereHas('role', static fn (Builder $query): Builder => $query->whereIn('slug', ['superadmin', 'admin']))
            ->with('role')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /** @param Builder<Order> $query */
    private function applyOverdueScope(Builder $query): Builder
    {
        return $query
            ->where('source_system', 'laravel')
            ->whereNull('completed_at')
            ->whereNotIn('status', ['shipped', 'cancelled'])
            ->where(static function (Builder $q): void {
                $q->where(static fn (Builder $inner): Builder => $inner
                    ->whereNotNull('expected_processing_at')
                    ->where('expected_processing_at', '<', now()))
                    ->orWhere(static fn (Builder $inner): Builder => $inner
                        ->whereNotNull('expected_shipping_at')
                        ->where('expected_shipping_at', '<', now()));
            });
    }
}
