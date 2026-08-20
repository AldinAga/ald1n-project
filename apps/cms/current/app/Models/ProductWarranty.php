<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductWarranty extends Model
{
    protected $fillable = [
        'warranty_number', 'order_id', 'order_item_id', 'product_id', 'user_id', 'warranty_rule_id',
        'status', 'starts_at', 'expires_at', 'duration_months', 'duration_days', 'maintenance_interval_months',
        'last_maintenance_at', 'next_maintenance_at', 'customer_name_snapshot',
        'customer_address_snapshot', 'customer_city_snapshot', 'customer_postal_code_snapshot',
        'customer_phone_snapshot', 'product_sku_snapshot', 'product_name_snapshot', 'quantity',
        'serial_numbers_json', 'terms_snapshot', 'voided_at', 'voided_by', 'void_reason', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'expires_at' => 'date',
            'last_maintenance_at' => 'date',
            'next_maintenance_at' => 'date',
            'voided_at' => 'datetime',
            'duration_months' => 'integer',
            'duration_days' => 'integer',
            'maintenance_interval_months' => 'integer',
            'quantity' => 'integer',
            'serial_numbers_json' => 'array',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function rule(): BelongsTo { return $this->belongsTo(WarrantyRule::class, 'warranty_rule_id'); }
    public function voidedBy(): BelongsTo { return $this->belongsTo(User::class, 'voided_by'); }
    public function maintenanceRecords(): HasMany { return $this->hasMany(WarrantyMaintenanceRecord::class)->orderByDesc('due_at')->orderByDesc('id'); }

    public function isExpired(): bool
    {
        return $this->status === 'active' && $this->expires_at?->isBefore(today());
    }

    public function effectiveStatus(): string
    {
        return $this->isExpired() ? 'expired' : (string) $this->status;
    }
}
