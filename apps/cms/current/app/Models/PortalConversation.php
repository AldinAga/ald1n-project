<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class PortalConversation extends Model
{
    protected $fillable = [
        'user_id', 'order_id', 'assigned_to', 'created_by', 'subject', 'status', 'priority',
        'last_message_at', 'closed_at', 'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(PortalMessage::class, 'conversation_id')->orderBy('sent_at')->orderBy('id');
    }

    public function publicMessages(): HasMany
    {
        return $this->hasMany(PortalMessage::class, 'conversation_id')
            ->where('visibility', 'public')
            ->orderBy('sent_at')
            ->orderBy('id');
    }

    public function latestPublicMessage(): HasOne
    {
        return $this->hasOne(PortalMessage::class, 'conversation_id')
            ->where('visibility', 'public')
            ->latestOfMany('sent_at');
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /** @return array<string,string> */
    public static function statusLabels(): array
    {
        return [
            'waiting_staff' => 'Čeka odgovor podrške',
            'waiting_customer' => 'Čeka odgovor kupca',
            'open' => 'U obradi',
            'closed' => 'Zatvoreno',
        ];
    }
}
