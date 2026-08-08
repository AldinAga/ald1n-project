<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ProductType extends Model
{
    protected $fillable = ['category_id', 'name', 'slug', 'description', 'status', 'sort_order', 'name_template', 'auto_name_enabled', 'minimum_completeness_percent', 'default_product_status', 'required_core_fields_json'];

    public function getRouteKeyName(): string { return 'slug'; }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
    protected function casts(): array { return ['auto_name_enabled' => 'boolean', 'minimum_completeness_percent' => 'integer', 'required_core_fields_json' => 'array']; }
    public function fields(): BelongsToMany { return $this->belongsToMany(SpecificationField::class, 'product_type_fields', 'product_type_id', 'field_id')->withPivot(['is_required', 'is_filterable', 'show_in_summary', 'sort_order', 'default_value', 'default_detail', 'completeness_weight', 'include_in_name']); }
}
