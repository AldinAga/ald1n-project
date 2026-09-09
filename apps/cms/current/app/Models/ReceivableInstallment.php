<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ReceivableInstallment extends Model
{
    protected $fillable = [
        'receivable_case_id', 'sequence_no', 'due_at', 'amount_rsd', 'paid_amount_rsd', 'status', 'paid_at', 'note',
    ];

    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
            'due_at' => 'datetime',
            'amount_rsd' => 'decimal:2',
            'paid_amount_rsd' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo { return $this->belongsTo(ReceivableCase::class, 'receivable_case_id'); }
    public function allocations(): HasMany { return $this->hasMany(ReceivablePaymentAllocation::class, 'receivable_installment_id'); }
}
