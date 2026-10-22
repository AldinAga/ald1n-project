<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WarrantyIssuance extends Model
{
    protected $fillable = [
        'warranty_number', 'order_id', 'order_item_id', 'first_verified_payment_id',
        'status', 'snapshot_json', 'activated_warranty_id', 'issued_at', 'created_by',
    ];

    protected function casts(): array
    {
        return ['snapshot_json' => 'array', 'issued_at' => 'datetime'];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function item(): BelongsTo { return $this->belongsTo(OrderItem::class, 'order_item_id'); }
    public function activatedWarranty(): BelongsTo { return $this->belongsTo(ProductWarranty::class, 'activated_warranty_id'); }
}
