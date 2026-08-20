<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderShipment extends Model
{
    protected $fillable = [
        'order_id', 'shipment_method', 'courier_service_id', 'courier_name_snapshot',
        'courier_tracking_url_snapshot', 'shipped_at', 'recipient_name', 'recipient_phone',
        'tracking_number_snapshot', 'note', 'proof_disk', 'proof_path', 'proof_original_name',
        'proof_mime_type', 'proof_size', 'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'proof_size' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(CourierService::class, 'courier_service_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
