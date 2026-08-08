<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class UserLoginSession extends Model
{
    protected $fillable = [
        'user_id', 'session_hash', 'ip_address', 'user_agent', 'device_label', 'remembered',
        'logged_in_at', 'last_seen_at', 'revoked_at', 'revoked_by', 'logged_out_at',
    ];

    protected $hidden = ['session_hash'];

    protected function casts(): array
    {
        return [
            'remembered' => 'boolean',
            'logged_in_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
            'logged_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && $this->logged_out_at === null;
    }
}
