<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class AfterSalesCase extends Model
{
    protected $fillable = [
        'case_number', 'order_id', 'opened_by', 'assigned_to', 'case_type', 'priority', 'status',
        'subject', 'description', 'requested_resolution', 'resolution_type', 'resolution_summary',
        'customer_name_snapshot', 'customer_phone_snapshot', 'customer_address_snapshot', 'due_at',
        'first_response_at', 'resolved_at', 'closed_at', 'closed_by',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function opener(): BelongsTo { return $this->belongsTo(User::class, 'opened_by'); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }
    public function closer(): BelongsTo { return $this->belongsTo(User::class, 'closed_by'); }
    public function items(): HasMany { return $this->hasMany(AfterSalesCaseItem::class); }
    public function messages(): HasMany { return $this->hasMany(AfterSalesMessage::class)->orderBy('created_at'); }
    public function attachments(): HasMany { return $this->hasMany(AfterSalesAttachment::class)->orderBy('created_at'); }
    public function history(): HasMany { return $this->hasMany(AfterSalesStatusHistory::class)->orderBy('created_at'); }
    public function actions(): HasMany { return $this->hasMany(AfterSalesAction::class)->orderBy('created_at'); }

    public function isClosed(): bool
    {
        return in_array($this->status, ['closed'], true);
    }
}
