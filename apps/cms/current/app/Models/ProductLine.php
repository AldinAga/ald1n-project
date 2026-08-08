<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductLine extends Model
{
    protected $fillable = ['brand_id', 'name', 'slug', 'status', 'sort_order'];
    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
}
