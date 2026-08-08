<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderDelivery extends Model
{
    protected $fillable = [
        'order_id', 'delivery_method', 'delivered_at', 'recipient_name', 'recipient_phone',
        'reference', 'note', 'proof_disk', 'proof_path', 'proof_original_name',
        'proof_mime_type', 'proof_size', 'confirmed_by',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'proof_size' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
