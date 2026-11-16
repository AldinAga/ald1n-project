<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class OrderCommission extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'total_eur', 'status', 'status_note', 'approved_by', 'approved_at',
        'paid_by', 'paid_at', 'cancelled_by', 'cancelled_at', 'payment_batch_id', 'payment_method',
        'payment_reference', 'status_updated_at', 'auto_approved_at',
    ];

    protected function casts(): array
    {
        return [
            'total_eur' => 'decimal:2',
            'approved_at' => 'datetime',
            'auto_approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status_updated_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(CommissionStatusHistory::class, 'commission_id')->latest('created_at');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(CommissionAdjustment::class, 'commission_id')->latest('id');
    }

    /** Zbir storno/zaduženja u EUR (centi, string "0.00"); koristi eager-loaded adjustments bez dodatnog upita. */
    public function adjustmentSumEur(string $kind, array $statuses = ['confirmed']): string
    {
        if (!\App\Support\CommissionMoney::hasAdjustments()) {
            return '0.00';
        }
        $rows = $this->relationLoaded('adjustments') ? $this->adjustments : $this->adjustments()->get();
        $cents = 0;
        foreach ($rows as $row) {
            if ($row->kind === $kind && in_array($row->status, $statuses, true)) {
                $cents += \App\Support\CommissionMoney::cents($row->amount_eur);
            }
        }

        return \App\Support\CommissionMoney::format($cents);
    }

    public function paymentBatch(): BelongsTo
    {
        return $this->belongsTo(CommissionPaymentBatch::class, 'payment_batch_id');
    }
}
