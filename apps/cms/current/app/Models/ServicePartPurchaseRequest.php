<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ServicePartPurchaseRequest extends Model
{
    protected $fillable = ['request_number', 'supplier_id', 'status', 'supplier_reference', 'expected_at', 'submitted_at', 'ordered_at', 'received_at', 'cancelled_at', 'notes', 'cancellation_reason', 'total_cost_rsd', 'created_by', 'updated_by'];
    protected function casts(): array { return ['expected_at' => 'date', 'submitted_at' => 'datetime', 'ordered_at' => 'datetime', 'received_at' => 'datetime', 'cancelled_at' => 'datetime', 'total_cost_rsd' => 'decimal:2']; }
    public function supplier(): BelongsTo { return $this->belongsTo(ServicePartSupplier::class, 'supplier_id'); }
    public function items(): HasMany { return $this->hasMany(ServicePartPurchaseRequestItem::class, 'purchase_request_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function isTerminal(): bool { return in_array($this->status, ['received', 'cancelled'], true); }
    public static function statusLabels(): array { return ['draft' => 'Nacrt', 'submitted' => 'Poslato dobavljaču', 'ordered' => 'Poručeno', 'received' => 'Primljeno', 'cancelled' => 'Otkazano']; }
}
