<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ServicePartSupplier extends Model
{
    protected $fillable = ['code', 'name', 'contact_person', 'phone', 'email', 'address', 'lead_time_days', 'is_active', 'notes', 'created_by', 'updated_by'];
    protected function casts(): array { return ['lead_time_days' => 'integer', 'is_active' => 'boolean']; }
    public function parts(): HasMany { return $this->hasMany(ServicePart::class, 'preferred_supplier_id'); }
    public function purchaseRequests(): HasMany { return $this->hasMany(ServicePartPurchaseRequest::class, 'supplier_id'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
