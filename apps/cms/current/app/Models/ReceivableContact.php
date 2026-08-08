<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ReceivableContact extends Model
{
    protected $fillable = [
        'receivable_case_id', 'user_id', 'order_email_outbox_id', 'event_key', 'channel', 'direction',
        'subject', 'note', 'visible_to_customer', 'is_automatic', 'contacted_at',
    ];

    protected function casts(): array
    {
        return [
            'visible_to_customer' => 'boolean',
            'is_automatic' => 'boolean',
            'contacted_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo { return $this->belongsTo(ReceivableCase::class, 'receivable_case_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function outbox(): BelongsTo { return $this->belongsTo(OrderEmailOutbox::class, 'order_email_outbox_id'); }
}
