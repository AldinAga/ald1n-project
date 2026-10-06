<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id', 'in_app_enabled', 'email_enabled', 'push_enabled', 'shipment_tracking_channel', 'order_updates', 'payment_alerts',
        'document_updates', 'after_sales_updates', 'warranty_updates', 'service_updates', 'receivable_updates',
        'commission_updates', 'stock_alerts', 'daily_digest',
    ];

    protected function casts(): array
    {
        return [
            'in_app_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'order_updates' => 'boolean',
            'payment_alerts' => 'boolean',
            'document_updates' => 'boolean',
            'after_sales_updates' => 'boolean',
            'warranty_updates' => 'boolean',
            'service_updates' => 'boolean',
            'receivable_updates' => 'boolean',
            'commission_updates' => 'boolean',
            'stock_alerts' => 'boolean',
            'daily_digest' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
