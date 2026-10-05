<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class OrderDocumentItem extends Model
{
    protected $fillable = [
        'order_document_id', 'sequence_no', 'product_id', 'product_sku', 'product_name',
        'quantity', 'unit_price_rsd', 'line_total_rsd',
    ];

    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
            'product_id' => 'integer',
            'quantity' => 'integer',
            'unit_price_rsd' => 'decimal:2',
            'line_total_rsd' => 'decimal:2',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(OrderDocument::class, 'order_document_id');
    }
}
