<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderEmailOutbox extends Model
{
    protected $table = 'order_email_outbox';

    protected $fillable = [
        'order_id', 'recipient_user_id', 'document_id', 'recipient_email', 'recipient_name',
        'event_type', 'dedupe_key', 'batch_key', 'subject', 'message', 'action_url',
        'attach_document', 'attach_active_invoice', 'status', 'attempt_count',
        'scheduled_for', 'last_attempt_at', 'sent_at', 'last_error', 'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'attach_document' => 'boolean',
            'attach_active_invoice' => 'boolean',
            'attempt_count' => 'integer',
            'scheduled_for' => 'datetime',
            'last_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function recipient(): BelongsTo { return $this->belongsTo(User::class, 'recipient_user_id'); }
    public function document(): BelongsTo { return $this->belongsTo(OrderDocument::class, 'document_id'); }
}
