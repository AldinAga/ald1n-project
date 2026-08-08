<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class IdempotencyKey extends Model
{
    protected $fillable = [
        'scope', 'actor_key', 'key_hash', 'request_hash', 'status', 'response_type', 'response_id',
        'locked_at', 'completed_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'response_id' => 'integer',
            'locked_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }
}
