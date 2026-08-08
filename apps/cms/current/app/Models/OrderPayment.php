<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderPayment extends Model
{
    protected $fillable = [
        'order_id', 'after_sales_action_id', 'payment_number', 'entry_type', 'status', 'amount_rsd', 'payment_method', 'paid_at',
        'reference', 'note', 'proof_path', 'proof_original_name', 'proof_mime_type', 'proof_size_bytes',
        'submitted_by', 'verified_by', 'verified_at', 'rejected_by', 'rejected_at', 'rejection_reason',
        'voided_by', 'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_rsd' => 'decimal:2',
            'paid_at' => 'datetime',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
            'voided_at' => 'datetime',
            'proof_size_bytes' => 'integer',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function afterSalesAction(): BelongsTo { return $this->belongsTo(AfterSalesAction::class); }
    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function verifier(): BelongsTo { return $this->belongsTo(User::class, 'verified_by'); }
    public function rejector(): BelongsTo { return $this->belongsTo(User::class, 'rejected_by'); }
    public function voider(): BelongsTo { return $this->belongsTo(User::class, 'voided_by'); }

    public function signedAmount(): float
    {
        return (float) $this->amount_rsd * ($this->entry_type === 'refund' ? -1 : 1);
    }
}
