<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class SpecificationField extends Model
{
    protected $fillable = [
        'name', 'slug', 'data_type', 'filter_type', 'unit', 'placeholder', 'help_text', 'options_text',
        'min_value', 'max_value', 'status', 'sort_order', 'parent_field_id', 'detail_input_enabled',
        'detail_label', 'detail_placeholder', 'storage_role', 'storage_source_field_id',
    ];

    protected $casts = [
        'min_value' => 'decimal:4',
        'max_value' => 'decimal:4',
        'detail_input_enabled' => 'boolean',
        'storage_source_field_id' => 'integer',
    ];

    public function requiresWholeGigabytes(): bool
    {
        $unit = Str::lower(trim((string) $this->unit));
        if (in_array($unit, ['gb', 'gib'], true)) return true;

        $needle = Str::slug(trim((string) $this->slug).' '.trim((string) $this->name), '_');
        return (str_contains($needle, 'memorij') || str_contains($needle, 'ram') || str_contains($needle, 'kapacitet'))
            && (str_contains($needle, 'gb') || $unit === '');
    }

    public function isStorageComponentsField(): bool
    {
        return (string) $this->storage_role === \App\Services\StorageSpecificationService::ROLE_COMPONENTS;
    }

    public function isStorageTotalField(): bool
    {
        if ((string) $this->storage_role === \App\Services\StorageSpecificationService::ROLE_TOTAL_CAPACITY) return true;
        if (!in_array((string) $this->data_type, ['integer', 'decimal'], true)) return false;

        $needle = Str::slug(trim((string) $this->slug).' '.trim((string) $this->name), '_');
        return (str_contains($needle, 'disk') || str_contains($needle, 'storage') || str_contains($needle, 'skladist'))
            && (str_contains($needle, 'kapacitet') || str_contains($needle, 'capacity'));
    }

    public function isDerivedStorageTotalField(): bool
    {
        return $this->isStorageTotalField() && (int) ($this->storage_source_field_id ?? 0) > 0;
    }

    public function isRepeatableStorageField(): bool
    {
        if ($this->isStorageComponentsField()) return true;
        if ($this->parent_field_id !== null || !in_array((string) $this->data_type, ['text', 'select'], true)) {
            return false;
        }

        $needle = Str::slug(trim((string) $this->slug).' '.trim((string) $this->name), '_');

        return str_contains($needle, 'disk')
            || str_contains($needle, 'storage')
            || str_contains($needle, 'skladist')
            || str_contains($needle, 'ssd')
            || str_contains($needle, 'hdd');
    }

    public function storageSourceField(): BelongsTo
    {
        return $this->belongsTo(self::class, 'storage_source_field_id');
    }

    public function storageTotalFields(): HasMany
    {
        return $this->hasMany(self::class, 'storage_source_field_id');
    }

    public function productTypes(): BelongsToMany
    {
        return $this->belongsToMany(ProductType::class, 'product_type_fields', 'field_id', 'product_type_id')->withPivot(['is_required', 'is_filterable', 'show_in_summary', 'sort_order', 'default_value', 'default_detail', 'completeness_weight', 'include_in_name']);
    }

    public function parentField(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_field_id');
    }

    public function childFields(): HasMany
    {
        return $this->hasMany(self::class, 'parent_field_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(SpecificationOption::class, 'field_id')->orderBy('sort_order')->orderBy('label');
    }
}
