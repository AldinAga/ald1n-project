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
        'returned_at', 'returned_by', 'return_reason',
    ];

    /** Kolone povrata dolaze iz migracije koja može stići posle koda; bez njih se ništa ne upisuje i ništa ne puca. */
    public static ?bool $returnColumnsOverride = null;

    public static function hasReturnColumns(): bool
    {
        if (self::$returnColumnsOverride !== null) {
            return self::$returnColumnsOverride;
        }
        static $has = null;

        return $has ??= \Illuminate\Support\Facades\Schema::hasColumn('order_shipments', 'returned_at');
    }

    public function isReturned(): bool
    {
        return $this->getAttribute('returned_at') !== null;
    }

    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'proof_size' => 'integer',
            'returned_at' => 'datetime',
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
