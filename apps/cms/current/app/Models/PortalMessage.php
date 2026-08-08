<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class PortalMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'conversation_id', 'sender_id', 'visibility', 'body', 'sent_at',
        'read_by_customer_at', 'read_by_staff_at', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'read_by_customer_at' => 'datetime',
            'read_by_staff_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(PortalConversation::class, 'conversation_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
