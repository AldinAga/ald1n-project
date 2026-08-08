<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FieldWorkOrderPart extends Model
{
    protected $fillable = ['field_work_order_id', 'service_part_id', 'supply_mode', 'part_sku_snapshot', 'part_name_snapshot', 'unit_snapshot', 'requested_quantity', 'reserved_quantity', 'consumed_quantity', 'unit_cost_snapshot_rsd', 'notes', 'created_by', 'updated_by'];
    protected function casts(): array { return ['requested_quantity' => 'decimal:3', 'reserved_quantity' => 'decimal:3', 'consumed_quantity' => 'decimal:3', 'unit_cost_snapshot_rsd' => 'decimal:2']; }
    public function workOrder(): BelongsTo { return $this->belongsTo(FieldWorkOrder::class, 'field_work_order_id'); }
    public function part(): BelongsTo { return $this->belongsTo(ServicePart::class, 'service_part_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function usesLocalStock(): bool { return $this->supply_mode === 'local_stock'; }
}
