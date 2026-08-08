<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class StockMovementController extends Controller
{
    public function __invoke(Request $request): View
    {
        $query = StockMovement::query()->with(['product', 'variant', 'order', 'user'])->latest('id');
        if ($search = trim((string) $request->query('q'))) {
            $query->where(static fn ($scope) => $scope->whereHas('product', static fn ($q) => $q->where('sku', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%'))->orWhereHas('variant', static fn ($q) => $q->where('sku', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%')));
        }
        if ($type = trim((string) $request->query('type'))) {
            $query->where('movement_type', $type);
        }

        $products = Product::query()
            ->when(trim((string) $request->query('q')) !== '', function ($q) use ($request): void {
                $search = trim((string) $request->query('q'));
                $q->where(fn ($nested) => $nested->where('sku', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%'));
            })
            ->whereNull('deleted_at')
            ->withCount('variants')
            ->orderByRaw('CASE WHEN stock_quantity <= low_stock_threshold THEN 0 ELSE 1 END')
            ->orderBy('stock_quantity')
            ->limit(30)
            ->get();

        return view('admin.stock.index', [
            'movements' => $query->paginate(50)->withQueryString(),
            'products' => $products,
            'idempotencyKeys' => $products->mapWithKeys(fn (Product $product) => [$product->id => (string) Str::uuid()]),
        ]);
    }
}
