<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ProductVariantSpecValue extends Model
{
    protected $table = 'product_variant_spec_values';
    protected $primaryKey = null;
    public $incrementing = false;
    protected $fillable = ['product_variant_id', 'field_id', 'value_text', 'value_detail', 'value_json', 'value_number', 'value_boolean'];
    protected function casts(): array { return ['value_json' => 'array', 'value_number' => 'decimal:4', 'value_boolean' => 'boolean']; }
    public function field(): BelongsTo { return $this->belongsTo(SpecificationField::class, 'field_id'); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
}
