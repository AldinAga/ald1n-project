<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AfterSalesActionItem extends Model
{
    protected $fillable = [
        'after_sales_action_id', 'after_sales_case_item_id', 'product_id', 'product_variant_id', 'sku_snapshot',
        'product_name_snapshot', 'quantity', 'disposition', 'stock_effect', 'stock_movement_id',
    ];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function action(): BelongsTo { return $this->belongsTo(AfterSalesAction::class, 'after_sales_action_id'); }
    public function caseItem(): BelongsTo { return $this->belongsTo(AfterSalesCaseItem::class, 'after_sales_case_item_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function variant(): BelongsTo { return $this->belongsTo(ProductVariant::class, 'product_variant_id'); }
    public function stockMovement(): BelongsTo { return $this->belongsTo(StockMovement::class); }
}
