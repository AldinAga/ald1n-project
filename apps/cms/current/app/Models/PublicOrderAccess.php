<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PublicOrderAccess extends Model
{
    protected $table = 'public_order_accesses';

    protected $fillable = [
        'order_id', 'token_hash', 'checkout_key_hash', 'request_hash', 'guest_email',
        'terms_snapshot_hash', 'consent_at', 'expires_at', 'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'guest_email' => 'encrypted',
            'consent_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
