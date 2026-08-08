<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AutomationRun extends Model
{
    protected $fillable = [
        'task', 'status', 'started_at', 'finished_at', 'examined_count', 'action_count',
        'notification_count', 'summary_json', 'error_text', 'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'summary_json' => 'array',
        ];
    }

    public function trigger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
