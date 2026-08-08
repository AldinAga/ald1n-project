<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'source_system' => $this->source_system,
            'status' => $this->completed_at !== null ? 'completed' : $this->status,
            'workflow_status' => $this->status,
            'completed_at' => optional($this->completed_at)->toIso8601String(),
            'completed_by' => $this->completed_by,
            'completion_note' => $this->completion_note,
            'inventory_state' => $this->inventory_state,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'subtotal_rsd' => (float) $this->subtotal_rsd,
            'supplier' => [
                'id' => $this->supplier_user_id,
                'name' => $this->supplier_name_snapshot,
                'email' => $this->supplier_email_snapshot,
                'phone' => $this->supplier_phone_snapshot,
                'role' => $this->supplier_role_snapshot,
            ],
            'shipping' => [
                'full_name' => $this->shipping_full_name,
                'address' => $this->shipping_address,
                'city' => $this->shipping_city,
                'postal_code' => $this->shipping_postal_code,
                'phone' => $this->shipping_phone,
            ],
            'tracking_number' => $this->tracking_number,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'sku' => $item->variant_sku_snapshot ?: $item->product_sku,
                'name' => $item->product_name.($item->variant_name_snapshot ? ' — '.$item->variant_name_snapshot : ''),
                'variant_name' => $item->variant_name_snapshot,
                'variant_attributes' => $item->variant_attributes_json ?? [],
                'quantity' => $item->quantity,
                'unit_price_rsd' => (float) $item->unit_price_rsd,
                'line_total_rsd' => (float) $item->line_total_rsd,
                'commission_total_eur' => (float) $item->commission_total_eur_snapshot,
            ])->values()),
            'commission' => $this->whenLoaded('commission', fn () => $this->commission ? [
                'total_eur' => (float) $this->commission->total_eur,
                'status' => $this->commission->status,
            ] : null),
            'created_at' => optional($this->created_at)->toIso8601String(),
            'updated_at' => optional($this->updated_at)->toIso8601String(),
        ];
    }
}
