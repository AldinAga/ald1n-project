<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InventoryCountItem extends Model
{
    protected $fillable = ['inventory_count_id', 'product_id', 'product_sku', 'product_name', 'system_quantity', 'counted_quantity', 'variance', 'note'];
    protected function casts(): array { return ['system_quantity' => 'integer', 'counted_quantity' => 'integer', 'variance' => 'integer']; }
    public function count(): BelongsTo { return $this->belongsTo(InventoryCount::class, 'inventory_count_id'); }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
}
