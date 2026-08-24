<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductPurchaseCostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// MOBILE_V1_0_ADMIN_PURCHASE_COST_PARITY_BATCH19_V4
final class ProductPurchaseCostController extends Controller
{
    public function index(Request $request, ProductPurchaseCostService $service): JsonResponse
    {
        $this->actor($request);
        $showAll = $request->query('show') === 'all' || $request->boolean('show_all');
        $overview = $service->overview($showAll);

        return response()->json(['data' => [
            'products' => $overview['products']->map(static fn (Product $product): array => [
                'id' => (int) $product->id,
                'name' => (string) $product->name,
                'sku' => (string) $product->sku,
                'stock_quantity' => (int) $product->stock_quantity,
                'price_amount' => round((float) $product->price_amount, 2),
                'price_currency' => (string) $product->price_currency,
                'purchase_price_rsd' => $product->purchase_price_rsd === null
                    ? null
                    : round((float) $product->purchase_price_rsd, 2),
            ])->values()->all(),
            'show_all' => (bool) $overview['showAll'],
            'missing_total' => (int) $overview['missingTotal'],
            'missing_positive_stock' => (int) $overview['missingPositiveStock'],
            'capabilities' => [
                'update' => true,
                'max_batch' => 500,
                'superadmin_only' => true,
            ],
        ]], 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function update(Request $request, ProductPurchaseCostService $service): JsonResponse
    {
        $actor = $this->actor($request);
        $changed = $service->update(
            (array) $request->input('costs', []),
            $actor,
            'mobile_superadmin_purchase_cost_entry',
        );

        return response()->json([
            'message' => $changed > 0
                ? 'Sačuvane su nabavne cene za '.$changed.' artikala.'
                : 'Nema promena za čuvanje.',
            'data' => ['changed' => $changed],
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->can('catalog.manage_products') && $actor->hasRole('superadmin'), 403, 'Nabavne cene su dostupne samo Super Administratoru.');
        return $actor;
    }
}
