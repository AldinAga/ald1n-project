<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class OrderDocument extends Model
{
    protected $fillable = [
        'order_id', 'document_type', 'revision_number', 'supersedes_document_id', 'document_number', 'status', 'issued_by', 'issued_at', 'due_at',
        'currency', 'subtotal_rsd', 'tax_rate_percent', 'tax_base_rsd', 'tax_amount_rsd', 'total_rsd',
        'company_name', 'company_address', 'company_city', 'company_tax_id', 'company_registration_number',
        'company_phone', 'company_email', 'company_website', 'company_logo_path', 'customer_name',
        'customer_address', 'customer_city', 'customer_phone', 'customer_email', 'supplier_name',
        'supplier_email', 'payment_method_snapshot', 'payment_status_snapshot', 'bank_account_snapshot',
        'ips_payload_snapshot', 'ips_qr_image_path', 'ips_qr_generated_at', 'ips_qr_error', 'note', 'delivery_method_snapshot', 'delivery_recipient_snapshot', 'delivered_at_snapshot', 'delivery_reference_snapshot', 'delivery_note_snapshot', 'cancelled_by', 'cancelled_at', 'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'due_at' => 'date',
            'cancelled_at' => 'datetime',
            'ips_qr_generated_at' => 'datetime',
            'revision_number' => 'integer',
            'delivered_at_snapshot' => 'datetime',
            'subtotal_rsd' => 'decimal:2',
            'tax_rate_percent' => 'decimal:2',
            'tax_base_rsd' => 'decimal:2',
            'tax_amount_rsd' => 'decimal:2',
            'total_rsd' => 'decimal:2',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function supersedes(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_document_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'supersedes_document_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderDocumentItem::class, 'order_document_id')->orderBy('sequence_no')->orderBy('id');
    }
}
