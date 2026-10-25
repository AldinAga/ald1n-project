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
        // B905-B907: always re-authorize against current database state.
        // Caller-side Eloquent relations and cached User roles may predate
        // recipient retirement/demotion, order cancellation or warranty void.
        if (!$warranty->exists || !$recipient->exists) return false;

        $currentWarranty = ProductWarranty::query()
            ->with('orderItem.order.user')->find($warranty->getKey());
        $currentRecipient = User::query()->with('role')->find($recipient->getKey());
        if (!$currentWarranty instanceof ProductWarranty
            || !$currentRecipient instanceof User
            || $currentWarranty->status !== 'active'
            || $currentRecipient->status !== 'active'
            || $currentRecipient->retired_at !== null) return false;

        $item = $currentWarranty->orderItem;
        if (!$item instanceof OrderItem
            || (int) $item->order_id !== (int) $currentWarranty->order_id
            || (int) $item->product_id !== (int) $currentWarranty->product_id) return false;

        $order = $item->order;
        if (!$order instanceof Order
            || (int) $order->id !== (int) $currentWarranty->order_id
            || (string) $order->status === 'cancelled') return false;

        $sellerId = $order->user instanceof User ? (int) $order->user->id : null;
        if (!$currentRecipient->hasRole('superadmin') && $sellerId !== (int) $currentRecipient->id) return false;

        // Only current individual expiry opt-in, not general warranty_updates.
        return $currentRecipient->notificationPreference()->first()?->warranty_expiry_alerts === true;
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
