<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class DataQualitySnapshot extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'source', 'status', 'score', 'summary_json', 'issues_json', 'metrics_json',
        'duration_ms', 'run_by', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'summary_json' => 'array',
            'issues_json' => 'array',
            'metrics_json' => 'array',
            'duration_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function runner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'run_by');
    }
}
