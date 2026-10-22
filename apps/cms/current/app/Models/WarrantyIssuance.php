<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WarrantyIssuance extends Model
{
    protected $fillable = ['warranty_number', 'order_id', 'order_item_id', 'confirmed_payment_id',
        'issued_at', 'status', 'snapshot_json', 'activated_warranty_id', 'activated_at',
        'voided_at', 'voided_by', 'void_reason'];

    protected function casts(): array
    {
        return ['issued_at' => 'datetime', 'activated_at' => 'datetime', 'voided_at' => 'datetime',
            'snapshot_json' => 'array', 'confirmed_payment_id' => 'integer', 'activated_warranty_id' => 'integer'];
    }

    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function orderItem(): BelongsTo { return $this->belongsTo(OrderItem::class); }
    public function activatedWarranty(): BelongsTo { return $this->belongsTo(ProductWarranty::class, 'activated_warranty_id'); }

    public function scopeValidLineage(Builder $query): Builder
    {
        return $query->whereHas('orderItem', static fn (Builder $items): Builder =>
            $items->whereColumn('order_items.order_id', 'warranty_issuances.order_id')->whereHas('order'));
    }

    public function scopeVisibleTo(Builder $query, User $actor): Builder
    {
        $query->validLineage();
        return $query->whereHas('order', static function (Builder $orders) use ($actor): void {
            $orders->where(static function (Builder $scope) use ($actor): void {
                if ($actor->can('warranties.manage')) {
                    if ($actor->hasRole('superadmin')) { $scope->whereRaw('1=1'); return; }
                    $scope->where('supplier_user_id', $actor->id);
                } else { $scope->whereRaw('1=0'); }
                if ($actor->can('warranties.view_own')) $scope->orWhere('user_id', $actor->id);
            });
        });
    }
}
