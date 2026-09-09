<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ReceivableCase extends Model
{
    protected $fillable = [
        'order_id', 'case_number', 'status', 'collection_stage', 'assigned_to', 'next_action_at',
        'promised_payment_at', 'last_contact_at', 'last_reminder_stage', 'last_reminder_at',
        'internal_note', 'metadata_json', 'created_by', 'updated_by', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'collection_stage' => 'integer',
            'next_action_at' => 'datetime',
            'promised_payment_at' => 'datetime',
            'last_contact_at' => 'datetime',
            'last_reminder_stage' => 'integer',
            'last_reminder_at' => 'datetime',
            'metadata_json' => 'array',
            'closed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function installments(): HasMany { return $this->hasMany(ReceivableInstallment::class)->orderBy('sequence_no'); }
    public function contacts(): HasMany { return $this->hasMany(ReceivableContact::class)->latest('contacted_at')->latest('id'); }
    public function paymentAllocations(): HasMany { return $this->hasMany(ReceivablePaymentAllocation::class, 'receivable_case_id'); }

    public function statusLabel(): string
    {
        return [
            'monitoring' => 'Praćenje',
            'contacted' => 'Kontaktiran kupac',
            'promised' => 'Obećana uplata',
            'installment_plan' => 'Plan otplate',
            'escalated' => 'Eskalirano',
            'disputed' => 'Sporno potraživanje',
            'closed' => 'Zatvoreno',
        ][$this->status] ?? $this->status;
    }
}
