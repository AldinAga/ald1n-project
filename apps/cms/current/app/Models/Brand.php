<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Brand extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'website_url', 'status', 'sort_order'];
    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function lines(): HasMany { return $this->hasMany(ProductLine::class); }
}
