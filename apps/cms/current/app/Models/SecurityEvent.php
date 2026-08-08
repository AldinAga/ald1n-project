<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class SecurityEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'event_type', 'severity', 'request_id', 'route_name', 'method',
        'ip_address', 'user_agent', 'context_json', 'created_at',
    ];

    protected function casts(): array
    {
        return ['context_json' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
