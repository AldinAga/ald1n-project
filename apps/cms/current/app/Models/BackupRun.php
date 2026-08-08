<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BackupRun extends Model
{
    protected $fillable = [
        'backup_key', 'backup_type', 'status', 'backup_path', 'database_file', 'size_bytes',
        'created_by', 'started_at', 'finished_at', 'error_message', 'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
