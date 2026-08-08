<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WarrantyMaintenanceRecord extends Model
{
    protected $fillable = [
        'product_warranty_id', 'status', 'due_at', 'scheduled_at', 'completed_at',
        'completed_by', 'service_reference', 'result', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'scheduled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function warranty(): BelongsTo { return $this->belongsTo(ProductWarranty::class, 'product_warranty_id'); }
    public function completer(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }
}
