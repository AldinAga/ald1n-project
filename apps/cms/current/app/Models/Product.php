<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Product extends Model
{
    protected $fillable = [
        'product_type_id', 'brand_id', 'product_line_id', 'model_name', 'sku', 'name', 'slug', 'price_amount',
        'price_currency', 'purchase_price_rsd', 'manual_commission_eur', 'description', 'notes', 'stock_quantity',
        'low_stock_threshold', 'status', 'created_by', 'updated_by', 'deleted_at', 'legacy_checksum',
        'legacy_synced_at', 'locally_modified_at', 'completeness_percent', 'name_is_manual', 'source_product_id',
    ];

    protected function casts(): array
    {
        return [
            'price_amount' => 'decimal:2',
            'purchase_price_rsd' => 'decimal:2',
            'manual_commission_eur' => 'decimal:2',
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
            'deleted_at' => 'datetime',
            'legacy_synced_at' => 'datetime',
            'locally_modified_at' => 'datetime',
            'completeness_percent' => 'integer',
            'name_is_manual' => 'boolean',
        ];
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('status', 'active')->whereNull('deleted_at');
    }

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function line(): BelongsTo { return $this->belongsTo(ProductLine::class, 'product_line_id'); }
    public function type(): BelongsTo { return $this->belongsTo(ProductType::class, 'product_type_id'); }
    public function sourceProduct(): BelongsTo { return $this->belongsTo(self::class, 'source_product_id'); }
    public function categories(): BelongsToMany { return $this->belongsToMany(Category::class, 'product_categories'); }
    public function images(): HasMany { return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'); }
    public function allImages(): HasMany { return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'); }
    public function primaryImage(): HasOne { return $this->hasOne(ProductImage::class)->where('is_primary', true)->orderBy('sort_order'); }
    public function presentationImage(): HasOne { return $this->hasOne(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id'); }
    public function specificationValues(): HasMany { return $this->hasMany(ProductSpecValue::class); }
    public function stockMovements(): HasMany { return $this->hasMany(StockMovement::class); }
    public function stockReceiptItems(): HasMany { return $this->hasMany(StockReceiptItem::class); }
    public function inventoryCountItems(): HasMany { return $this->hasMany(InventoryCountItem::class); }
    public function warrantyRules(): HasMany { return $this->hasMany(WarrantyRule::class); }
    public function warranties(): HasMany { return $this->hasMany(ProductWarranty::class); }
}
