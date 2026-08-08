<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InventoryCount extends Model
{
    protected $fillable = ['count_number', 'status', 'scope_label', 'counted_on', 'note', 'total_variance', 'created_by', 'finalized_by', 'finalized_at'];
    protected function casts(): array { return ['counted_on' => 'date', 'total_variance' => 'integer', 'finalized_at' => 'datetime']; }
    public function items(): HasMany { return $this->hasMany(InventoryCountItem::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function finalizer(): BelongsTo { return $this->belongsTo(User::class, 'finalized_by'); }
}
