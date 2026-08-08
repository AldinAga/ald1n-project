<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StockReceiptItem extends Model
{
    protected $fillable = ['stock_receipt_id', 'product_id', 'product_sku', 'product_name', 'quantity', 'unit_cost_rsd', 'note'];
    protected function casts(): array { return ['quantity' => 'integer', 'unit_cost_rsd' => 'decimal:2']; }
    public function receipt(): BelongsTo { return $this->belongsTo(StockReceipt::class, 'stock_receipt_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
