<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WarrantyActivationRecoveryAttempt extends Model
{
    protected $fillable = [
        'order_id', 'status', 'attempt_count', 'next_attempt_at',
        'last_error_code', 'last_attempt_at', 'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'next_attempt_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
