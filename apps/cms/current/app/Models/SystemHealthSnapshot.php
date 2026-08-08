<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SystemHealthSnapshot extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['status', 'checks_json', 'metrics_json', 'checked_by', 'checked_at', 'created_at'];

    protected function casts(): array
    {
        return [
            'checks_json' => 'array',
            'metrics_json' => 'array',
            'checked_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
