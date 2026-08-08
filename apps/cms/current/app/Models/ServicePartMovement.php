<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ServicePartMovement extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['event_key', 'service_part_id', 'field_work_order_id', 'purchase_request_id', 'user_id', 'movement_type', 'stock_change', 'reserved_change', 'stock_before', 'stock_after', 'reserved_before', 'reserved_after', 'unit_cost_rsd', 'note', 'metadata_json'];
    protected function casts(): array { return ['stock_change' => 'decimal:3', 'reserved_change' => 'decimal:3', 'stock_before' => 'decimal:3', 'stock_after' => 'decimal:3', 'reserved_before' => 'decimal:3', 'reserved_after' => 'decimal:3', 'unit_cost_rsd' => 'decimal:2', 'metadata_json' => 'array', 'created_at' => 'datetime']; }
    public function part(): BelongsTo { return $this->belongsTo(ServicePart::class, 'service_part_id'); }
    public function workOrder(): BelongsTo { return $this->belongsTo(FieldWorkOrder::class, 'field_work_order_id'); }
    public function purchaseRequest(): BelongsTo { return $this->belongsTo(ServicePartPurchaseRequest::class, 'purchase_request_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
