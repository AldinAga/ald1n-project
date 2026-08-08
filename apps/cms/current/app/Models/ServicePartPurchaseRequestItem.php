<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ServicePartPurchaseRequestItem extends Model
{
    protected $fillable = ['purchase_request_id', 'service_part_id', 'ordered_quantity', 'received_quantity', 'unit_cost_rsd', 'notes'];
    protected function casts(): array { return ['ordered_quantity' => 'decimal:3', 'received_quantity' => 'decimal:3', 'unit_cost_rsd' => 'decimal:2']; }
    public function purchaseRequest(): BelongsTo { return $this->belongsTo(ServicePartPurchaseRequest::class, 'purchase_request_id'); }
    public function part(): BelongsTo { return $this->belongsTo(ServicePart::class, 'service_part_id'); }
}
