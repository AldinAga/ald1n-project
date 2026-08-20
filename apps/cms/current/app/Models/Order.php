<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

final class Order extends Model
{
    protected $fillable = [
        'source_system', 'sales_channel', 'direct_sale_recorded_by', 'order_number', 'idempotency_key_hash', 'request_fingerprint', 'user_id', 'supplier_user_id', 'supplier_name_snapshot', 'supplier_email_snapshot', 'supplier_phone_snapshot', 'supplier_role_snapshot', 'assigned_at', 'status',
        'inventory_state', 'inventory_reserved_at', 'inventory_returned_at', 'cancelled_at', 'cancelled_by', 'shipping_full_name', 'shipping_address', 'shipping_city',
        'shipping_postal_code', 'shipping_phone', 'subtotal_rsd', 'eur_rsd_rate', 'customer_note',
        'payment_method', 'payment_status', 'payment_state', 'paid_total_rsd', 'payment_due_at', 'payment_verified_at', 'bank_account_id', 'bank_account_label_snapshot',
        'bank_account_number_snapshot', 'bank_account_number_display_snapshot',
        'payment_recipient_name_snapshot', 'payment_recipient_address_snapshot', 'payment_code_snapshot',
        'payment_purpose_snapshot', 'payment_reference_snapshot', 'tracking_number',
        'tracking_updated_at', 'tracking_updated_by', 'assigned_by', 'reassigned_at', 'accepted_by', 'accepted_at',
        'expected_processing_at', 'expected_shipping_at', 'last_internal_note_at', 'completed_at', 'completed_by', 'completion_note', 'reopened_at', 'reopened_by', 'reopen_reason', 'archived_at', 'archived_by', 'archive_reason', 'purged_at', 'purged_by', 'purge_reason', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_rsd' => 'decimal:2',
            'eur_rsd_rate' => 'decimal:6',
            'tracking_updated_at' => 'datetime',
            'inventory_reserved_at' => 'datetime',
            'inventory_returned_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'assigned_at' => 'datetime',
            'reassigned_at' => 'datetime',
            'accepted_at' => 'datetime',
            'expected_processing_at' => 'datetime',
            'expected_shipping_at' => 'datetime',
            'last_internal_note_at' => 'datetime',
            'completed_at' => 'datetime',
            'archived_at' => 'datetime',
            'purged_at' => 'datetime',
            'reopened_at' => 'datetime',
            'paid_total_rsd' => 'decimal:2',
            'payment_due_at' => 'datetime',
            'payment_verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function directSaleRecorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direct_sale_recorded_by');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OrderDocument::class)->orderByDesc('issued_at')->orderByDesc('id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function commission(): HasOne
    {
        return $this->hasOne(OrderCommission::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class)->latest('created_at');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('created_at');
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(OrderInternalNote::class)->latest('created_at');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(OrderAssignment::class)->latest('created_at');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(OrderDelivery::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(OrderShipment::class);
    }

    public function afterSalesCases(): HasMany
    {
        return $this->hasMany(AfterSalesCase::class);
    }

    public function warranties(): HasMany
    {
        return $this->hasMany(ProductWarranty::class);
    }

    public function receivableCase(): HasOne
    {
        return $this->hasOne(ReceivableCase::class);
    }

    public function portalConversations(): HasMany
    {
        return $this->hasMany(PortalConversation::class);
    }

    public function scopeOperational(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull($this->qualifyColumn('archived_at'));
    }

    public function scopeArchived(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query
            ->whereNotNull($this->qualifyColumn('archived_at'))
            ->whereNull($this->qualifyColumn('purged_at'));
    }

    public function scopePurged(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNotNull($this->qualifyColumn('purged_at'));
    }

    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        return parent::resolveRouteBindingQuery($query, $value, $field)
            ->whereNull($this->qualifyColumn('archived_at'));
    }
}
