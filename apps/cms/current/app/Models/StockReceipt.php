<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class StockReceipt extends Model
{
    protected $fillable = [
        'receipt_number', 'status', 'supplier_name', 'supplier_document_number', 'received_on', 'note',
        'total_units', 'created_by', 'posted_by', 'posted_at', 'cancelled_by', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return ['received_on' => 'date', 'total_units' => 'integer', 'posted_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    public function items(): HasMany { return $this->hasMany(StockReceiptItem::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function poster(): BelongsTo { return $this->belongsTo(User::class, 'posted_by'); }
}
