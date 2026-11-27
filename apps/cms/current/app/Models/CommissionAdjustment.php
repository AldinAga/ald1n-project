<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class CommissionAdjustment extends Model
{
    public const CREDIT_SETTLED = 'settled';

    protected $fillable = [
        'commission_id', 'order_id', 'order_item_id', 'kind', 'status', 'amount_eur', 'quantity', 'reason', 'source',
        'after_sales_action_item_id', 'created_by', 'decided_by', 'decided_at', 'decision_note', 'settled_at', 'settled_by',
    ];

    protected function casts(): array
    {
        return ['amount_eur' => 'decimal:2', 'decided_at' => 'datetime', 'settled_at' => 'datetime'];
    }

    public function commission(): BelongsTo
    {
        return $this->belongsTo(OrderCommission::class, 'commission_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
