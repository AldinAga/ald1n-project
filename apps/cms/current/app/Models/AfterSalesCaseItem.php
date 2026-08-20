<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AfterSalesCaseItem extends Model
{
    protected $fillable = ['after_sales_case_id', 'order_item_id', 'product_id', 'sku_snapshot', 'product_name_snapshot', 'quantity', 'issue_description'];
    public function case(): BelongsTo { return $this->belongsTo(AfterSalesCase::class, 'after_sales_case_id'); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
