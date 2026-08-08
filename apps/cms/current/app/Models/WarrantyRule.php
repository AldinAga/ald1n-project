<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class WarrantyRule extends Model
{
    protected $fillable = [
        'name', 'scope_type', 'category_id', 'product_id', 'duration_months', 'duration_days',
        'maintenance_interval_months', 'priority', 'is_active', 'terms',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'duration_months' => 'integer',
            'duration_days' => 'integer',
            'maintenance_interval_months' => 'integer',
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function warranties(): HasMany { return $this->hasMany(ProductWarranty::class); }
}
