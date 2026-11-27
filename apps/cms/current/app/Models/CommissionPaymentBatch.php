<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class CommissionPaymentBatch extends Model
{
    protected $fillable = [
        'batch_number', 'payment_method', 'payment_reference', 'note', 'commission_count', 'total_eur', 'debit_eur', 'net_eur', 'eur_rsd_rate', 'paid_by', 'paid_at',
    ];

    protected function casts(): array
    {
        return ['eur_rsd_rate' => 'decimal:6', 'total_eur' => 'decimal:2', 'debit_eur' => 'decimal:2', 'net_eur' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    public function commissions(): HasMany { return $this->hasMany(OrderCommission::class, 'payment_batch_id'); }
    public function payer(): BelongsTo { return $this->belongsTo(User::class, 'paid_by'); }
}
