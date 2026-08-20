<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustStockRequest;
use App\Models\InventoryCount;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StockReceipt;
use App\Services\AdvancedInventoryService;
use App\Services\InventoryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class InventoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->assertReady();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'stock' => ['nullable', Rule::in(['all', 'low'])],
            'status' => ['nullable', 'string', 'max:40'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $query = Product::query()
            ->whereNull('deleted_at')
            ->orderBy('sku')
            ->orderBy('id');

        if (($filters['q'] ?? null) !== null && trim((string) $filters['q']) !== '') {
            $needle = '%'.trim((string) $filters['q']).'%';
            $query->where(static function (Builder $builder) use ($needle): void {
                $builder->where('sku', 'like', $needle)
                    ->orWhere('name', 'like', $needle);
            });
        }
        if (($filters['stock'] ?? 'all') === 'low') {
            $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
        }
        if (($filters['status'] ?? null) !== null && trim((string) $filters['status']) !== '') {
            $query->where('status', (string) $filters['status']);
        }

        $products = $query->paginate((int) ($filters['per_page'] ?? 35))->withQueryString();
        $actor = $request->user();

        return response()->json([
            'data' => $products->getCollection()
                ->map(fn (Product $product): array => $this->productRow($product))
                ->values(),
            'meta' => $this->paginationMeta($products),
            'filters' => $filters,
            'summary' => [
                'low_stock_count' => Product::query()
                    ->whereNull('deleted_at')
                    ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
                    ->count(),
                'recent_receipts' => StockReceipt::query()
                    ->latest('id')
                    ->limit(12)
                    ->get()
                    ->map(fn (StockReceipt $receipt): array => $this->receiptSummary($receipt))
                    ->values(),
                'recent_counts' => InventoryCount::query()
                    ->latest('id')
                    ->limit(12)
                    ->get()
                    ->map(fn (InventoryCount $count): array => $this->countSummary($count))
                    ->values(),
            ],
            'capabilities' => [
                'can_view' => (bool) $actor?->can('stock.view'),
                'can_adjust' => (bool) $actor?->can('stock.adjust'),
                'can_receive' => (bool) $actor?->can('inventory.receive'),
                'can_count' => (bool) $actor?->can('inventory.count'),
                'can_export' => (bool) $actor?->can('inventory.export'),
            ],
        ]);
    }

    public function movements(Request $request): JsonResponse
    {
        $this->assertReady();
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:190'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'movement_type' => ['nullable', 'string', 'max:60'],
            'source' => ['nullable', 'string', 'max:80'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $query = StockMovement::query()
            ->with('product:id,sku,name')
            ->latest('id');

        if (($filters['product_id'] ?? null) !== null) {
            $query->where('product_id', (int) $filters['product_id']);
        }
        if (($filters['movement_type'] ?? null) !== null && trim((string) $filters['movement_type']) !== '') {
            $query->where('movement_type', (string) $filters['movement_type']);
        }
        if (($filters['source'] ?? null) !== null && trim((string) $filters['source']) !== '') {
            $query->where('source', (string) $filters['source']);
        }
        if (($filters['q'] ?? null) !== null && trim((string) $filters['q']) !== '') {
            $needle = '%'.trim((string) $filters['q']).'%';
            $query->whereHas('product', static function (Builder $builder) use ($needle): void {
                $builder->where('sku', 'like', $needle)
                    ->orWhere('name', 'like', $needle);
            });
        }

        $movements = $query->paginate((int) ($filters['per_page'] ?? 50))->withQueryString();

        return response()->json([
            'data' => $movements->getCollection()
                ->map(fn (StockMovement $movement): array => $this->movementRow($movement))
                ->values(),
            'meta' => $this->paginationMeta($movements),
            'filters' => $filters,
        ]);
    }

    public function adjust(AdjustStockRequest $request, Product $product, InventoryService $inventory): JsonResponse
    {
        $this->assertReady();
        $data = $request->validated();
        $movement = $inventory->adjust(
            $product,
            (int) $data['quantity_change'],
            (string) $data['note'],
            (string) $data['idempotency_key'],
            $request->user(),
        );

        $freshProduct = Product::query()->findOrFail($product->id);

        return response()->json([
            'data' => [
                'movement' => $this->movementRow($movement->loadMissing('product:id,sku,name')),
                'product' => $this->productRow($freshProduct),
            ],
        ]);
    }

    public function receive(Request $request, AdvancedInventoryService $inventory): JsonResponse
    {
        $this->assertReady();
        $this->mergeIdempotencyHeader($request);
        $data = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:200'],
            'received_on' => ['required', 'date_format:Y-m-d'],
            'supplier_name' => ['nullable', 'string', 'max:190'],
            'supplier_document_number' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:250'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'items.*.unit_cost_rsd' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ]);

        $receipt = $inventory->receive($data, (string) $data['idempotency_key'], $request->user());

        return response()->json([
            'data' => $this->receiptDetail($receipt),
        ], 201);
    }

    public function count(Request $request, AdvancedInventoryService $inventory): JsonResponse
    {
        $this->assertReady();
        $this->mergeIdempotencyHeader($request);
        $data = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:200'],
            'counted_on' => ['required', 'date_format:Y-m-d'],
            'scope_label' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:250'],
            'items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'items.*.counted_quantity' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ]);

        $count = $inventory->finalizeCount($data, (string) $data['idempotency_key'], $request->user());

        return response()->json([
            'data' => $this->countDetail($count),
        ], 201);
    }

    public function csv(): StreamedResponse
    {
        $this->assertReady();
        return response()->streamDownload(
            static function (): void {
                $stream = fopen('php://output', 'wb');
                if ($stream === false) {
                    return;
                }
                fputcsv($stream, ['SKU', 'Naziv', 'Stanje', 'Minimalni prag', 'Status', 'Upozorenje'], ';');
                foreach (Product::query()
                    ->whereNull('deleted_at')
                    ->orderBy('sku')
                    ->orderBy('id')
                    ->cursor() as $product) {
                            $stock = (int) $product->stock_quantity;
                            $threshold = (int) $product->low_stock_threshold;
                            fputcsv($stream, [
                                (string) $product->sku,
                                (string) $product->name,
                                $stock,
                                $threshold,
                                (string) $product->status,
                                $stock <= $threshold ? 'NIZAK LAGER' : '',
                            ], ';');
                        }
                fclose($stream);
            },
            'inventory-'.now()->format('Ymd-His').'.csv',
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'private, no-store, max-age=0',
                'Pragma' => 'no-cache',
            ],
        );
    }

    private function assertReady(): void
    {
        $required = [
            'stock_movements' => ['id', 'product_id', 'quantity_change', 'stock_receipt_id', 'inventory_count_id'],
            'stock_receipts' => ['id', 'receipt_number', 'status', 'received_on'],
            'stock_receipt_items' => ['id', 'stock_receipt_id', 'product_id', 'quantity'],
            'inventory_counts' => ['id', 'count_number', 'status', 'counted_on'],
            'inventory_count_items' => ['id', 'inventory_count_id', 'product_id', 'variance'],
            'idempotency_keys' => ['id', 'scope', 'key_hash', 'status'],
        ];
        foreach ($required as $table => $columns) {
            if (!Schema::hasTable($table)) {
                abort(503, 'Napredni lager nije spreman. Pokrenite: php artisan app:payments-inventory-doctor --repair');
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    abort(503, 'Napredni lager nije spreman. Pokrenite: php artisan app:payments-inventory-doctor --repair');
                }
            }
        }
    }

    private function mergeIdempotencyHeader(Request $request): void
    {
        $header = trim((string) $request->header('Idempotency-Key', ''));
        if ($header !== '') {
            $request->merge(['idempotency_key' => $header]);
        }
    }

    /** @return array<string,mixed> */
    private function productRow(Product $product): array
    {
        $stock = (int) $product->stock_quantity;
        $threshold = (int) $product->low_stock_threshold;

        return [
            'id' => (int) $product->id,
            'sku' => (string) $product->sku,
            'name' => (string) $product->name,
            'stock_quantity' => $stock,
            'low_stock_threshold' => $threshold,
            'status' => (string) $product->status,
            'is_low_stock' => $stock <= $threshold,
            'updated_at' => $this->iso($product->updated_at),
        ];
    }

    /** @return array<string,mixed> */
    private function movementRow(StockMovement $movement): array
    {
        return [
            'id' => (int) $movement->id,
            'product' => $movement->product === null ? null : [
                'id' => (int) $movement->product->id,
                'sku' => (string) $movement->product->sku,
                'name' => (string) $movement->product->name,
            ],
            'movement_type' => (string) $movement->movement_type,
            'source' => (string) $movement->source,
            'quantity_change' => (int) $movement->quantity_change,
            'quantity_before' => (int) $movement->quantity_before,
            'quantity_after' => (int) $movement->quantity_after,
            'note' => $movement->note === null ? null : (string) $movement->note,
            'stock_receipt_id' => $movement->stock_receipt_id === null ? null : (int) $movement->stock_receipt_id,
            'inventory_count_id' => $movement->inventory_count_id === null ? null : (int) $movement->inventory_count_id,
            'created_at' => $this->iso($movement->created_at),
        ];
    }

    /** @return array<string,mixed> */
    private function receiptSummary(StockReceipt $receipt): array
    {
        return [
            'id' => (int) $receipt->id,
            'receipt_number' => (string) $receipt->receipt_number,
            'status' => (string) $receipt->status,
            'supplier_name' => $receipt->supplier_name === null ? null : (string) $receipt->supplier_name,
            'supplier_document_number' => $receipt->supplier_document_number === null ? null : (string) $receipt->supplier_document_number,
            'received_on' => $this->date($receipt->received_on),
            'total_units' => (int) $receipt->total_units,
            'created_at' => $this->iso($receipt->created_at),
        ];
    }

    /** @return array<string,mixed> */
    private function receiptDetail(StockReceipt $receipt): array
    {
        $receipt->loadMissing('items.product:id,sku,name');
        $data = $this->receiptSummary($receipt);
        $data['note'] = $receipt->note === null ? null : (string) $receipt->note;
        $data['items'] = $receipt->items->map(static fn ($item): array => [
            'id' => (int) $item->id,
            'product_id' => (int) $item->product_id,
            'product_sku' => (string) ($item->product_sku ?: $item->product?->sku ?: ''),
            'product_name' => (string) ($item->product_name ?: $item->product?->name ?: ''),
            'quantity' => (int) $item->quantity,
            'note' => $item->note === null ? null : (string) $item->note,
        ])->values();

        return $data;
    }

    /** @return array<string,mixed> */
    private function countSummary(InventoryCount $count): array
    {
        return [
            'id' => (int) $count->id,
            'count_number' => (string) $count->count_number,
            'status' => (string) $count->status,
            'counted_on' => $this->date($count->counted_on),
            'total_variance' => (int) $count->total_variance,
            'finalized_at' => $this->iso($count->finalized_at),
            'created_at' => $this->iso($count->created_at),
        ];
    }

    /** @return array<string,mixed> */
    private function countDetail(InventoryCount $count): array
    {
        $count->loadMissing('items.product:id,sku,name');
        $data = $this->countSummary($count);
        $data['note'] = $count->note === null ? null : (string) $count->note;
        $data['items'] = $count->items->map(static fn ($item): array => [
            'id' => (int) $item->id,
            'product_id' => (int) $item->product_id,
            'product_sku' => (string) ($item->product_sku ?: $item->product?->sku ?: ''),
            'product_name' => (string) ($item->product_name ?: $item->product?->name ?: ''),
            'system_quantity' => (int) $item->system_quantity,
            'counted_quantity' => (int) $item->counted_quantity,
            'variance' => (int) $item->variance,
            'note' => $item->note === null ? null : (string) $item->note,
        ])->values();

        return $data;
    }

    /** @return array<string,int|null> */
    private function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    private function iso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }
        try {
            return Carbon::parse((string) $value)->toIso8601String();
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof Carbon) {
            return $value->toDateString();
        }
        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
