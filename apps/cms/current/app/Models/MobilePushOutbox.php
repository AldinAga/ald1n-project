<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MobilePushOutbox extends Model
{
    protected $table = 'mobile_push_outbox';

    protected $fillable = [
        'user_id',
        'mobile_device_id',
        'provider',
        'event',
        'title',
        'message',
        'data_json',
        'status',
        'attempt_count',
        'receipt_check_count',
        'scheduled_for',
        'provider_ticket_id',
        'sent_at',
        'receipt_due_at',
        'receipt_checked_at',
        'delivered_at',
        'failed_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'data_json' => 'array',
            'attempt_count' => 'integer',
            'receipt_check_count' => 'integer',
            'scheduled_for' => 'datetime',
            'sent_at' => 'datetime',
            'receipt_due_at' => 'datetime',
            'receipt_checked_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(MobileDevice::class, 'mobile_device_id');
    }
}
