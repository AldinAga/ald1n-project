<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class OrderVersionService
{
    public function canCustomerAmend(Order $order, User $actor): bool
    {
        if ((int) $order->user_id !== (int) $actor->id) return false;
        if ((string) $order->source_system !== 'laravel') return false;
        if ((string) ($order->sales_channel ?? 'order') === 'direct_sale') return false;
        if ($order->completed_at !== null) return false;
        if (!in_array((string) $order->status, ['new', 'processing', 'confirmed'], true)) return false;
        if ($this->hasShipment($order)) return false;
        return true;
    }

    public function token(Order $order): string
    {
        $order->loadMissing('items');
        $items = $order->items
            ->map(static fn ($item): array => [
                'id' => (int) $item->id,
                'product_id' => $item->product_id !== null ? (int) $item->product_id : null,
                'quantity' => (int) $item->quantity,
                'unit_price_original' => (string) $item->unit_price_original,
                'original_currency' => (string) $item->original_currency,
                'unit_price_rsd' => (string) $item->unit_price_rsd,
                'purchase_unit_rsd_snapshot' => $item->purchase_unit_rsd_snapshot !== null ? (string) $item->purchase_unit_rsd_snapshot : null,
                'commission_unit_eur_snapshot' => (string) $item->commission_unit_eur_snapshot,
            ])
            ->sortBy(static fn (array $item): string => sprintf('%012d:%012d', (int) ($item['product_id'] ?? 0), (int) $item['id']))
            ->values()
            ->all();

        $payload = [
            'id' => (int) $order->id,
            'status' => (string) $order->status,
            'completed_at' => $order->completed_at?->toISOString(),
            'has_shipment' => $this->hasShipment($order),
            'shipping_full_name' => (string) $order->shipping_full_name,
            'shipping_address' => (string) $order->shipping_address,
            'shipping_city' => (string) $order->shipping_city,
            'shipping_postal_code' => (string) $order->shipping_postal_code,
            'shipping_phone' => (string) $order->shipping_phone,
            'customer_note' => $order->customer_note,
            'subtotal_rsd' => (string) $order->subtotal_rsd,
            'updated_at' => $order->updated_at?->toISOString(),
            'items' => $items,
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function assertFresh(Order $order, string $expectedToken, string $field = 'expected_edit_token'): void
    {
        $expectedToken = trim($expectedToken);
        $actual = $this->token($order);
        if ($expectedToken === '' || !hash_equals($actual, $expectedToken)) {
            throw new ConflictHttpException(
                $field === 'order_version_token'
                    ? 'Porudžbina je izmenjena nakon otvaranja ove stranice. Osveži podatke i ponovo proveri poslednju verziju.'
                    : 'Porudžbina je u međuvremenu promenjena. Osveži podatke i proveri poslednju verziju pre čuvanja.',
            );
        }
    }

    private function hasShipment(Order $order): bool
    {
        if ($order->relationLoaded('shipment')) return $order->shipment !== null;
        return $order->shipment()->exists();
    }
}
