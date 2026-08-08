<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ExchangeRateHistory extends Model
{
    protected $table = 'exchange_rate_history';

    public const UPDATED_AT = null;

    protected $fillable = [
        'old_rate', 'new_rate', 'mode', 'provider', 'source', 'provider_date', 'triggered_by',
        'status', 'message', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'old_rate' => 'decimal:6',
            'new_rate' => 'decimal:6',
            'provider_date' => 'date',
        ];
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
