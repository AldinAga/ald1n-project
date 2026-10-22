<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductWarranty;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Canonical expiration recipient policy. Seller lineage is immutable;
 * SuperAdministrator is the only role-based exception, never a global broadcast.
 */
final class WarrantyExpiryRecipientPolicy
{
    public function allows(ProductWarranty $warranty, User $recipient): bool
    {
        if ($warranty->status !== 'active' || $recipient->status !== 'active' || $recipient->retired_at !== null) {
            return false;
        }
        $warranty->loadMissing('orderItem.order.user');
        $item = $warranty->orderItem;
        if (!$item instanceof OrderItem || (int) $item->order_id !== (int) $warranty->order_id) {
            return false;
        }
        $order = $item->order;
        if (!$order instanceof Order || (int) $order->id !== (int) $warranty->order_id
            || (string) $order->status === 'cancelled') {
            return false;
        }
        $sellerId = $order->user instanceof User ? (int) $order->user->id : null;
        if (!$recipient->hasRole('superadmin') && $sellerId !== (int) $recipient->id) {
            return false;
        }
        // Do not create new preference rows or enable consent through a read.
        return $recipient->notificationPreference()->first()?->warranty_expiry_alerts === true;
    }

    /** @return Collection<int,User> */
    public function recipients(ProductWarranty $warranty): Collection
    {
        $warranty->loadMissing('orderItem.order.user');
        $seller = $warranty->strictCustomerOwner();
        $admins = User::query()->with('role')->where('status', 'active')
            ->whereHas('role', static fn ($query) => $query->where('slug', 'superadmin'))
            ->whereHas('notificationPreference', static fn ($query) => $query->where('warranty_expiry_alerts', true))
            ->get();
        if ($seller instanceof User) $admins->push($seller);
        return $admins->unique('id')->filter(fn (User $recipient): bool => $this->allows($warranty, $recipient))->values();
    }
}
