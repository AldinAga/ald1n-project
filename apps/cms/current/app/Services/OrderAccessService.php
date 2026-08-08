<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class OrderAccessService
{
    /** @param Builder<Order> $query */
    public function applyManagedScope(Builder $query, User $user): Builder
    {
        if ($user->hasRole('superadmin')) {
            return $query;
        }

        if ($user->hasRole('admin')) {
            return $query->where('supplier_user_id', $user->id);
        }

        return $query->where('user_id', $user->id);
    }

    public function canManage(Order $order, User $user): bool
    {
        return $user->hasRole('superadmin')
            || ($user->hasRole('admin') && (int) $order->supplier_user_id === (int) $user->id);
    }

    public function canView(Order $order, User $user): bool
    {
        return $this->canManage($order, $user) || (int) $order->user_id === (int) $user->id;
    }

    public function authorizeManage(Order $order, User $user): void
    {
        abort_unless($this->canManage($order, $user), 404);
    }

    public function authorizeView(Order $order, User $user): void
    {
        abort_unless($this->canView($order, $user), 404);
    }
}
