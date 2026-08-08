<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'sku', 'name', 'price_amount', 'price_currency', 'purchase_price_rsd',
        'manual_commission_eur', 'stock_quantity', 'low_stock_threshold', 'status', 'is_default',
        'warranty_rule_id', 'sort_order', 'created_by', 'updated_by', 'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'price_amount' => 'decimal:2', 'purchase_price_rsd' => 'decimal:2',
            'manual_commission_eur' => 'decimal:2', 'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer', 'is_default' => 'boolean', 'sort_order' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('status', 'active')->whereNull('deleted_at');
    }

    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function warrantyRule(): BelongsTo { return $this->belongsTo(WarrantyRule::class); }
    public function specificationValues(): HasMany { return $this->hasMany(ProductVariantSpecValue::class); }
    public function images(): HasMany { return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'); }
    public function stockMovements(): HasMany { return $this->hasMany(StockMovement::class); }
}
