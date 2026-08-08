<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class AfterSalesAction extends Model
{
    protected $fillable = [
        'action_number', 'after_sales_case_id', 'action_type', 'status', 'inventory_handling',
        'assigned_to', 'created_by', 'updated_by', 'scheduled_at', 'due_at', 'started_at',
        'started_by', 'completed_at', 'completed_by', 'cancelled_at', 'cancelled_by',
        'cancellation_reason', 'amount_rsd', 'payment_id', 'reference', 'public_note',
        'internal_note', 'completion_note', 'metadata_json',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'due_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'amount_rsd' => 'decimal:2',
            'metadata_json' => 'array',
        ];
    }

    public function case(): BelongsTo { return $this->belongsTo(AfterSalesCase::class, 'after_sales_case_id'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function starter(): BelongsTo { return $this->belongsTo(User::class, 'started_by'); }
    public function completer(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }
    public function canceller(): BelongsTo { return $this->belongsTo(User::class, 'cancelled_by'); }
    public function payment(): BelongsTo { return $this->belongsTo(OrderPayment::class, 'payment_id'); }
    public function items(): HasMany { return $this->hasMany(AfterSalesActionItem::class); }
    public function workOrder(): HasOne { return $this->hasOne(FieldWorkOrder::class); }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'cancelled'], true);
    }

    /** @return array<string,string> */
    public static function typeLabels(): array
    {
        return [
            'service_visit' => 'Servisna poseta / popravka',
            'replacement_dispatch' => 'Zamenska isporuka',
            'return_receipt' => 'Prijem vraćene robe',
            'refund' => 'Refundacija kupcu',
        ];
    }

    /** @return array<string,string> */
    public static function statusLabels(): array
    {
        return [
            'planned' => 'Planirana',
            'in_progress' => 'U toku',
            'completed' => 'Izvršena',
            'cancelled' => 'Otkazana',
        ];
    }

    /** @return array<string,string> */
    public static function dispositionLabels(): array
    {
        return [
            'none' => 'Bez promene lagera',
            'repair' => 'Popravka',
            'replace' => 'Zamena artikla',
            'restock' => 'Vraća se na raspoloživ lager',
            'quarantine' => 'Izdvojeno na pregled',
            'scrap' => 'Otpis / ne vraća se na lager',
        ];
    }
}
