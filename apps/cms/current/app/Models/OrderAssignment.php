<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderAssignment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'old_supplier_user_id', 'new_supplier_user_id', 'changed_by', 'reason', 'created_at',
    ];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function oldSupplier(): BelongsTo { return $this->belongsTo(User::class, 'old_supplier_user_id'); }
    public function newSupplier(): BelongsTo { return $this->belongsTo(User::class, 'new_supplier_user_id'); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'changed_by'); }
}
