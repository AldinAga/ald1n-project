<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ReceivablePaymentAllocation extends Model
{
    protected $fillable = ['receivable_case_id', 'receivable_installment_id', 'order_payment_id', 'amount_rsd'];

    protected function casts(): array
    {
        return ['amount_rsd' => 'decimal:2'];
    }

    public function case(): BelongsTo { return $this->belongsTo(ReceivableCase::class, 'receivable_case_id'); }
    public function installment(): BelongsTo { return $this->belongsTo(ReceivableInstallment::class, 'receivable_installment_id'); }
    public function payment(): BelongsTo { return $this->belongsTo(OrderPayment::class, 'order_payment_id'); }
}
