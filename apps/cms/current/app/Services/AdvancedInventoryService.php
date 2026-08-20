<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryCount;
use App\Models\InventoryCountItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AdvancedInventoryService
{
    public function __construct(
        private readonly IdempotencyService $idempotency,
        private readonly DocumentNumberService $numbers,
        private readonly AuditLogger $audit,
    ) {}

    /** @param array<string,mixed> $data */
    public function receive(array $data, string $idempotencyKey, User $actor): StockReceipt
    {
        $items = $this->normalizeItems((array) $data['items'], 'quantity');
        $payload = [
            'received_on' => $data['received_on'],
            'supplier_name' => trim((string) ($data['supplier_name'] ?? '')),
            'supplier_document_number' => trim((string) ($data['supplier_document_number'] ?? '')),
            'items' => $items,
        ];

        /** @var StockReceipt $receipt */
        $receipt = $this->idempotency->run(
            $actor,
            'inventory.receive',
            $idempotencyKey,
            $payload,
            StockReceipt::class,
            function () use ($data, $items, $actor): StockReceipt {
                $products = Product::query()->whereIn('id', array_column($items, 'product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                if ($products->count() !== count($items)) throw ValidationException::withMessages(['items' => 'Jedan ili više artikala ne postoje.']);

                $receipt = StockReceipt::query()->create([
                    'receipt_number' => $this->numbers->next('stock_receipt', (int) date('Y')),
                    'status' => 'draft',
                    'supplier_name' => trim((string) ($data['supplier_name'] ?? '')) ?: null,
                    'supplier_document_number' => trim((string) ($data['supplier_document_number'] ?? '')) ?: null,
                    'received_on' => $data['received_on'],
                    'note' => trim((string) ($data['note'] ?? '')) ?: null,
                    'total_units' => array_sum(array_column($items, 'quantity')),
                    'created_by' => $actor->id,
                ]);

                foreach ($items as $row) {
                    /** @var Product $product */
                    $product = $products->get($row['product_id']);
                    StockReceiptItem::query()->create([
                        'stock_receipt_id' => $receipt->id,
                        'product_id' => $product->id,
                        'product_sku' => $product->sku,
                        'product_name' => $product->name,
                        'quantity' => $row['quantity'],
                        'unit_cost_rsd' => $row['unit_cost_rsd'],
                        'note' => $row['note'],
                    ]);
                    $before = (int) $product->stock_quantity;
                    $after = $before + $row['quantity'];
                    $product->update(['stock_quantity' => $after, 'updated_by' => $actor->id]);
                    StockMovement::query()->create([
                        'event_key' => 'stock-receipt:'.$receipt->id.':'.$product->id,
                        'product_id' => $product->id,
                        'user_id' => $actor->id,
                        'stock_receipt_id' => $receipt->id,
                        'movement_type' => 'purchase',
                        'source' => 'stock_receipt',
                        'quantity_change' => $row['quantity'],
                        'quantity_before' => $before,
                        'quantity_after' => $after,
                        'note' => 'Ulaz robe '.$receipt->receipt_number,
                        'metadata_json' => ['supplier_document_number' => $receipt->supplier_document_number],
                    ]);
                }
                $receipt->update(['status' => 'posted', 'posted_by' => $actor->id, 'posted_at' => now()]);
                $this->audit->log('inventory.receipt_posted', 'Proknjižen ulaz robe '.$receipt->receipt_number, $receipt, after: ['total_units' => $receipt->total_units, 'items' => count($items)], user: $actor);
                return $receipt;
            },
            static fn (int $id): StockReceipt => StockReceipt::query()->findOrFail($id),
        );

        return $receipt->load('items.product');
    }

    /** @param array<string,mixed> $data */
    public function finalizeCount(array $data, string $idempotencyKey, User $actor): InventoryCount
    {
        $items = $this->normalizeItems((array) $data['items'], 'counted_quantity');
        $payload = ['counted_on' => $data['counted_on'], 'scope_label' => trim((string) ($data['scope_label'] ?? '')), 'items' => $items];

        /** @var InventoryCount $count */
        $count = $this->idempotency->run(
            $actor,
            'inventory.count',
            $idempotencyKey,
            $payload,
            InventoryCount::class,
            function () use ($data, $items, $actor): InventoryCount {
                $products = Product::query()->whereIn('id', array_column($items, 'product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                if ($products->count() !== count($items)) throw ValidationException::withMessages(['items' => 'Jedan ili više artikala ne postoje.']);

                $count = InventoryCount::query()->create([
                    'count_number' => $this->numbers->next('inventory_count', (int) date('Y')),
                    'status' => 'draft',
                    'scope_label' => trim((string) ($data['scope_label'] ?? '')) ?: null,
                    'counted_on' => $data['counted_on'],
                    'note' => trim((string) ($data['note'] ?? '')) ?: null,
                    'created_by' => $actor->id,
                ]);
                $totalVariance = 0;
                foreach ($items as $row) {
                    /** @var Product $product */
                    $product = $products->get($row['product_id']);
                    $before = (int) $product->stock_quantity;
                    $after = $row['counted_quantity'];
                    $variance = $after - $before;
                    $totalVariance += $variance;
                    InventoryCountItem::query()->create([
                        'inventory_count_id' => $count->id,
                        'product_id' => $product->id,
                        'product_sku' => $product->sku,
                        'product_name' => $product->name,
                        'system_quantity' => $before,
                        'counted_quantity' => $after,
                        'variance' => $variance,
                        'note' => $row['note'],
                    ]);
                    if ($variance !== 0) {
                        $product->update(['stock_quantity' => $after, 'updated_by' => $actor->id]);
                        StockMovement::query()->create([
                            'event_key' => 'inventory-count:'.$count->id.':'.$product->id,
                            'product_id' => $product->id,
                            'user_id' => $actor->id,
                            'inventory_count_id' => $count->id,
                            'movement_type' => 'manual_adjustment',
                            'source' => 'inventory_count',
                            'quantity_change' => $variance,
                            'quantity_before' => $before,
                            'quantity_after' => $after,
                            'note' => 'Popis '.$count->count_number,
                            'metadata_json' => ['counted_on' => $count->counted_on?->format('Y-m-d')],
                        ]);
                    }
                }
                $count->update(['status' => 'finalized', 'total_variance' => $totalVariance, 'finalized_by' => $actor->id, 'finalized_at' => now()]);
                $this->audit->log('inventory.count_finalized', 'Zaključen popis '.$count->count_number, $count, after: ['total_variance' => $totalVariance, 'items' => count($items)], user: $actor);
                return $count;
            },
            static fn (int $id): InventoryCount => InventoryCount::query()->findOrFail($id),
        );

        return $count->load('items.product');
    }

    /** @param list<mixed> $raw @return list<array{product_id:int,quantity?:int,counted_quantity?:int,unit_cost_rsd:?float,note:?string}> */
    private function normalizeItems(array $raw, string $quantityKey): array
    {
        $items = [];
        foreach ($raw as $row) {
            if (!is_array($row)) continue;
            $productId = (int) ($row['product_id'] ?? 0);
            $quantity = (int) ($row[$quantityKey] ?? -1);
            if ($productId <= 0 || $quantity < ($quantityKey === 'quantity' ? 1 : 0)) continue;
            $items[$productId] = [
                'product_id' => $productId,
                $quantityKey => $quantity,
                'unit_cost_rsd' => isset($row['unit_cost_rsd']) && $row['unit_cost_rsd'] !== '' ? (float) $row['unit_cost_rsd'] : null,
                'note' => trim((string) ($row['note'] ?? '')) ?: null,
            ];
        }
        if ($items === []) throw ValidationException::withMessages(['items' => 'Dodajte najmanje jednu ispravnu stavku.']);
        ksort($items);
        return array_values($items);
    }
}
