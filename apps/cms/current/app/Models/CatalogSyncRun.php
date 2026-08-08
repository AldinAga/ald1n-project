<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CatalogSyncRun extends Model
{
    protected $fillable = [
        'mode', 'status', 'started_by', 'legacy_database', 'target_database',
        'summary_json', 'error_message', 'started_at', 'finished_at',
    ];
    protected function casts(): array
    {
        return ['summary_json' => 'array', 'started_at' => 'datetime', 'finished_at' => 'datetime'];
    }
}
