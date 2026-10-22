<?php

declare(strict_types=1);
namespace App\Services;

use App\Models\{Order, User, WarrantyIssuance};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

final class WarrantyIssuancePresenter
{
    /** @return list<array<string,mixed>> */
    public function forOrder(Order $order, User $actor): array
    {
        if (!Schema::hasTable('warranty_issuances')) return [];
        return WarrantyIssuance::query()->visibleTo($actor)->where('order_id', $order->id)
            ->where('status', 'pending_activation')->with('order')->orderBy('id')->get()
            ->map(fn (WarrantyIssuance $issued): array => $this->row($issued))->all();
    }

    public function pending(User $actor, ?string $search = null): ?LengthAwarePaginator
    {
        if (!Schema::hasTable('warranty_issuances')) return null;
        $query = WarrantyIssuance::query()->visibleTo($actor)->where('status', 'pending_activation')->with('order')->latest('id');
        if (filled($search)) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], trim($search)).'%';
            $query->where(static fn ($nested) => $nested->where('warranty_number', 'like', $term)
                ->orWhereHas('order', static fn ($orders) => $orders->where('order_number', 'like', $term)));
        }
        return $query->paginate(20, ['*'], 'pending_page')->withQueryString()
            ->through(fn (WarrantyIssuance $issued): array => $this->row($issued));
    }

    /** @return array<string,mixed> */
    public function row(WarrantyIssuance $issued): array
    {
        $snapshot = $issued->snapshot_json;
        return [
            'number' => $issued->warranty_number, 'status' => $issued->status,
            'product' => (string) ($snapshot['product_name_snapshot'] ?? ''),
            'sku' => (string) ($snapshot['product_sku_snapshot'] ?? ''),
            'customer' => (string) ($snapshot['customer_name_snapshot'] ?? ''),
            'order_number' => $issued->order?->order_number,
            'issued_at' => $issued->issued_at?->format('d.m.Y H:i'),
            'duration_months' => (int) ($snapshot['duration_months'] ?? 0),
            'duration_days' => (int) ($snapshot['duration_days'] ?? 0),
            'show_url' => route('warranty-issuances.show', $issued),
            'pdf_url' => route('warranty-issuances.pdf', $issued),
        ];
    }
}
