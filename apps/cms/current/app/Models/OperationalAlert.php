<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OperationalAlert extends Model
{
    protected $fillable = [
        'alert_key', 'type', 'severity', 'status', 'order_id', 'product_id', 'user_id',
        'title', 'message', 'action_url', 'first_detected_at', 'last_detected_at',
        'last_notified_at', 'resolved_at', 'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'first_detected_at' => 'datetime',
            'last_detected_at' => 'datetime',
            'last_notified_at' => 'datetime',
            'resolved_at' => 'datetime',
            'metadata_json' => 'array',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
