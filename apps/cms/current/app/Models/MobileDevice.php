<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

final class MobileDevice extends Model
{
    protected $fillable = [
        'user_id',
        'personal_access_token_id',
        'installation_id',
        'platform',
        'device_name',
        'push_provider',
        'push_token',
        'push_token_hash',
        'app_version',
        'build_number',
        'locale',
        'timezone',
        'notifications_enabled',
        'last_seen_at',
        'revoked_at',
    ];

    protected $hidden = ['push_token', 'push_token_hash'];

    protected function casts(): array
    {
        return [
            'push_token' => 'encrypted',
            'notifications_enabled' => 'boolean',
            'last_seen_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accessToken(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'personal_access_token_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }
}
