<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class OrderItem extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id', 'product_sku', 'product_name', 'quantity',
        'unit_price_original', 'original_currency', 'unit_price_rsd', 'line_total_rsd',
        'variant_sku_snapshot', 'variant_name_snapshot', 'variant_attributes_json',
        'purchase_unit_rsd_snapshot', 'purchase_total_rsd_snapshot', 'cost_source_snapshot',
        'brand_name_snapshot', 'product_line_name_snapshot', 'product_type_name_snapshot',
        'commission_source_snapshot', 'commission_rate_percent_snapshot',
        'commission_unit_eur_snapshot', 'commission_total_eur_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_original' => 'decimal:2',
            'unit_price_rsd' => 'decimal:2',
            'line_total_rsd' => 'decimal:2',
            'purchase_unit_rsd_snapshot' => 'decimal:2',
            'purchase_total_rsd_snapshot' => 'decimal:2',
            'commission_rate_percent_snapshot' => 'decimal:2',
            'commission_unit_eur_snapshot' => 'decimal:2',
            'commission_total_eur_snapshot' => 'decimal:2',
            'variant_attributes_json' => 'array',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function warranty(): HasOne { return $this->hasOne(ProductWarranty::class); }
}
