<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdjustStockRequest;
use App\Models\Product;
use App\Services\InventoryService;
use Illuminate\Http\RedirectResponse;

final class StockAdjustmentController extends Controller
{
    public function __invoke(AdjustStockRequest $request, Product $product, InventoryService $inventory): RedirectResponse
    {
        $data = $request->validated();
        $movement = $inventory->adjust(
            $product,
            (int) $data['quantity_change'],
            (string) $data['note'],
            (string) $data['idempotency_key'],
            $request->user(),
        );

        return redirect()->route('admin.stock.index', ['q' => $product->sku])
            ->with('status', sprintf('Lager %s je korigovan: %d → %d.', $product->sku, $movement->quantity_before, $movement->quantity_after));
    }
}
