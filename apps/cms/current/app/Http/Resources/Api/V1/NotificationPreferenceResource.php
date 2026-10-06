<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class NotificationPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'in_app_enabled' => (bool) $this->in_app_enabled,
            'email_enabled' => (bool) $this->email_enabled,
            'push_enabled' => (bool) ($this->push_enabled ?? false),
            'shipment_tracking_channel' => in_array((string) ($this->shipment_tracking_channel ?? 'both'), ['push', 'email', 'both'], true)
                ? (string) $this->shipment_tracking_channel
                : 'both',
            'order_updates' => (bool) $this->order_updates,
            'payment_alerts' => (bool) $this->payment_alerts,
            'document_updates' => (bool) $this->document_updates,
            'after_sales_updates' => (bool) $this->after_sales_updates,
            'warranty_updates' => (bool) $this->warranty_updates,
            'service_updates' => (bool) $this->service_updates,
            'receivable_updates' => (bool) $this->receivable_updates,
            'commission_updates' => (bool) $this->commission_updates,
            'stock_alerts' => (bool) $this->stock_alerts,
            'daily_digest' => (bool) $this->daily_digest,
        ];
    }
}
