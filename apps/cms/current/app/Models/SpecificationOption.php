<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

final class SpecificationOption extends Model
{
    protected $fillable = ['field_id', 'label', 'value', 'status', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(SpecificationField::class, 'field_id');
    }

    public function parentOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'specification_option_dependencies',
            'child_option_id',
            'parent_option_id',
        );
    }

    public function childOptions(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'specification_option_dependencies',
            'parent_option_id',
            'child_option_id',
        );
    }
}
