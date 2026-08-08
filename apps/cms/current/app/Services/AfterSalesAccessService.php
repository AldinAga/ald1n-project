<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AfterSalesCase;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class AfterSalesAccessService
{
    /** @param Builder<AfterSalesCase> $query */
    public function applyVisibleScope(Builder $query, User $user): Builder
    {
        if ($user->hasRole('superadmin')) {
            return $query;
        }

        if ($user->hasRole('admin')) {
            return $query->where(function (Builder $builder) use ($user): void {
                $builder->where('assigned_to', $user->id)
                    ->orWhereHas('order', static fn (Builder $orders) => $orders->where('supplier_user_id', $user->id));
            });
        }

        return $query->whereHas('order', static fn (Builder $orders) => $orders->where('user_id', $user->id));
    }

    public function canView(AfterSalesCase $case, User $user): bool
    {
        if ($user->hasRole('superadmin')) return true;
        if ($user->hasRole('admin')) {
            return (int) $case->assigned_to === (int) $user->id
                || (int) $case->order?->supplier_user_id === (int) $user->id;
        }

        return (int) $case->order?->user_id === (int) $user->id;
    }

    public function canManage(AfterSalesCase $case, User $user): bool
    {
        return $user->hasPermission('after_sales.manage')
            && ($user->hasRole('superadmin')
                || (int) $case->assigned_to === (int) $user->id
                || (int) $case->order?->supplier_user_id === (int) $user->id);
    }

    public function canCreateForOrder(Order $order, User $user): bool
    {
        if (!$user->hasPermission('after_sales.create')) return false;
        if ((int) $order->user_id !== (int) $user->id) return false;

        return $order->completed_at !== null || $order->delivery()->exists();
    }

    public function authorizeView(AfterSalesCase $case, User $user): void
    {
        abort_unless($this->canView($case, $user), 404);
    }

    public function authorizeManage(AfterSalesCase $case, User $user): void
    {
        abort_unless($this->canManage($case, $user), 404);
    }
}
