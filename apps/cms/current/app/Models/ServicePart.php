<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ServicePart extends Model
{
    protected $fillable = ['sku', 'name', 'unit', 'stock_quantity', 'reserved_quantity', 'minimum_quantity', 'average_cost_rsd', 'preferred_supplier_id', 'is_active', 'notes', 'created_by', 'updated_by'];
    protected function casts(): array { return ['stock_quantity' => 'decimal:3', 'reserved_quantity' => 'decimal:3', 'minimum_quantity' => 'decimal:3', 'average_cost_rsd' => 'decimal:2', 'is_active' => 'boolean']; }
    public function preferredSupplier(): BelongsTo { return $this->belongsTo(ServicePartSupplier::class, 'preferred_supplier_id'); }
    public function movements(): HasMany { return $this->hasMany(ServicePartMovement::class); }
    public function workOrderParts(): HasMany { return $this->hasMany(FieldWorkOrderPart::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function availableQuantity(): float { return max(0.0, round((float) $this->stock_quantity - (float) $this->reserved_quantity, 3)); }
    public function isLowStock(): bool { return $this->availableQuantity() <= (float) $this->minimum_quantity; }
}
