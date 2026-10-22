<?php

declare(strict_types=1);
namespace App\Services;

use App\Models\{Order, OrderItem, ProductWarranty, User};
use Illuminate\Support\Collection;

/** One fresh, fail-closed consent/lineage policy for every expiry delivery boundary. */
final class WarrantyExpiryPolicy
{
    public static function isExpiry(mixed $event): bool
    {
        if (!is_string($event)) return false;
        $event = strtolower($event);
        return str_contains($event, 'warranty') && (str_contains($event, 'expiry') || str_contains($event, 'expir'));
    }

    /** @return array{user:User,warranty:ProductWarranty,channels:array<string,bool>}|null */
    public function resolve(int $recipientId, array $data): ?array
    {
        $id = filter_var($data['warranty_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) return null;
        // Do not trust cached roles, preferences, assignees or warranty.user_id.
        $recipient = User::query()->with(['role', 'notificationPreference'])->find($recipientId);
        $preference = $recipient?->notificationPreference;
        if (!$recipient || $recipient->status !== 'active' || $recipient->retired_at !== null
            || !$preference || $preference->warranty_expiry_alerts !== true) return null;
        $warranty = ProductWarranty::query()->with('orderItem.order')->find($id);
        $order = $this->lineage($warranty);
        if (!$order || !($recipient->hasRole('superadmin') || (int) $order->user_id === $recipientId)) return null;
        if (isset($data['warranty_expires_on']) && $data['warranty_expires_on'] !== $warranty->expires_at->toDateString()) return null;
        return ['user' => $recipient, 'warranty' => $warranty, 'channels' => [
            '_in_app' => $preference->in_app_enabled === true && ($data['_in_app'] ?? true) !== false,
            '_email' => $preference->email_enabled === true && ($data['_email'] ?? true) !== false,
            '_push' => $preference->push_enabled === true && ($data['_push'] ?? true) !== false,
        ]];
    }

    /** @return Collection<int,User> */
    public function recipients(ProductWarranty $warranty): Collection
    {
        $fresh = ProductWarranty::query()->with('orderItem.order')->find($warranty->id);
        $order = $this->lineage($fresh);
        if (!$order) return collect();
        return User::query()->where('status', 'active')->whereNull('retired_at')
            ->where(static fn ($users) => $users->whereKey((int) $order->user_id)
                ->orWhereHas('role', static fn ($roles) => $roles->where('slug', 'superadmin')))
            ->whereHas('notificationPreference', static fn ($prefs) => $prefs->where('warranty_expiry_alerts', true))
            ->orderBy('id')->get();
    }

    public function allowsChannel(int $recipientId, array $data, string $channel): bool
    {
        $resolved = $this->resolve($recipientId, $data);
        $key = ['database' => '_in_app', 'mail' => '_email', 'push' => '_push'][$channel] ?? '';
        return $resolved !== null && ($resolved['channels'][$key] ?? false);
    }

    /** @return array<string,mixed> */
    public function canonicalData(array $resolved, array $data): array
    {
        $w = $resolved['warranty']; $user = $resolved['user'];
        return array_replace($data, $resolved['channels'], [
            'event' => 'warranty.expiring', 'warranty_id' => $w->id,
            'warranty_expires_on' => $w->expires_at->toDateString(),
            'url' => route($user->hasRole('superadmin') ? 'admin.warranties.show' : 'warranties.show', $w),
            'route' => ($user->hasRole('superadmin') ? '/admin/warranties/' : '/warranties/').$w->id,
        ]);
    }

    private function lineage(?ProductWarranty $warranty): ?Order
    {
        if (!$warranty || $warranty->status !== 'active' || !$warranty->starts_at || !$warranty->expires_at
            || $warranty->starts_at->gt($warranty->expires_at)) return null;
        $item = $warranty->orderItem;
        if (!$item instanceof OrderItem || (int) $item->order_id !== (int) $warranty->order_id) return null;
        $order = $item->order;
        return $order instanceof Order && $order->status !== 'cancelled' ? $order : null;
    }
}
