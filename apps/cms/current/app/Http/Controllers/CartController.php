<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CatalogAccessService;
use App\Services\OrderCartService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class CartController extends Controller
{
    public function index(
        Request $request,
        CatalogAccessService $access,
        OrderCartService $cart,
    ): View {
        $quantities = $cart->quantities($request);
        $products = collect();

        if ($quantities !== []) {
            $query = Product::query()
                ->publiclyVisible()
                ->whereIn('id', array_keys($quantities))
                ->orderBy('name');
            $access->apply($query, $request->user());
            $products = $query->get([
                'id',
                'slug',
                'sku',
                'name',
                'price_amount',
                'price_currency',
                'stock_quantity',
            ])->keyBy('id');
        }

        $availableQuantities = [];
        $items = [];
        $totalsByCurrency = [];
        $hasStockIssue = false;

        foreach ($quantities as $productId => $quantity) {
            /** @var Product|null $product */
            $product = $products->get($productId);
            if (!$product instanceof Product) {
                continue;
            }

            $availableQuantities[$productId] = $quantity;
            $stock = max(0, (int) $product->stock_quantity);
            $lineTotal = round((float) $product->price_amount * $quantity, 2);
            $currency = strtoupper((string) $product->price_currency);
            $totalsByCurrency[$currency] = round(($totalsByCurrency[$currency] ?? 0.0) + $lineTotal, 2);

            if ($quantity > $stock || $stock <= 0) {
                $hasStockIssue = true;
            }

            $items[] = [
                'product' => $product,
                'quantity' => $quantity,
                'stock' => $stock,
                'line_total' => $lineTotal,
                'currency' => $currency,
            ];
        }

        if ($availableQuantities !== $quantities) {
            $cart->replace($request, $availableQuantities);
        }

        return view('cart.index', [
            'items' => collect($items),
            'cartCount' => array_sum($availableQuantities),
            'totalsByCurrency' => $totalsByCurrency,
            'hasStockIssue' => $hasStockIssue,
        ]);
    }

    public function store(
        Request $request,
        Product $product,
        CatalogAccessService $access,
        OrderCartService $cart,
    ): RedirectResponse {
        $product = $this->availableProduct($request, $product, $access);
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $requested = (int) $data['quantity'];
        $stock = max(0, (int) $product->stock_quantity);
        if ($stock <= 0) {
            throw ValidationException::withMessages(['quantity' => 'Artikal trenutno nije na lageru.']);
        }
        if ($requested > $stock) {
            throw ValidationException::withMessages([
                'quantity' => 'Tražena količina nije dostupna. Na lageru: '.$stock.'.',
            ]);
        }

        $existing = $cart->quantity($request, (int) $product->id);
        if ($existing <= 0 && $cart->distinctCount($request) >= OrderCartService::MAX_DISTINCT_ITEMS) {
            throw ValidationException::withMessages([
                'quantity' => 'Korpa može sadržati najviše '.OrderCartService::MAX_DISTINCT_ITEMS.' različitih artikala.',
            ]);
        }

        // Set/max semantika čini ponovljeni isti klik bezbednim: isti submit ne duplira količinu.
        $next = min($stock, max($existing, $requested));
        $cart->set($request, (int) $product->id, $next);

        return back()->with(
            'status',
            $existing > 0
                ? 'Artikal je već bio u korpi. Količina je '.$next.'.'
                : 'Artikal je dodat u korpu.',
        );
    }

    public function update(
        Request $request,
        Product $product,
        CatalogAccessService $access,
        OrderCartService $cart,
    ): RedirectResponse {
        $product = $this->availableProduct($request, $product, $access);
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $quantity = (int) $data['quantity'];
        $stock = max(0, (int) $product->stock_quantity);
        if ($stock <= 0 || $quantity > $stock) {
            throw ValidationException::withMessages([
                'quantity' => 'Količina nije dostupna. Trenutni lager: '.$stock.'.',
            ]);
        }

        $cart->set($request, (int) $product->id, $quantity);

        return redirect()->route('cart.index')->with('status', 'Količina u korpi je ažurirana.');
    }

    public function destroy(
        Request $request,
        Product $product,
        OrderCartService $cart,
    ): RedirectResponse {
        $cart->remove($request, (int) $product->id);

        return redirect()->route('cart.index')->with('status', 'Artikal je uklonjen iz korpe.');
    }

    public function clear(Request $request, OrderCartService $cart): RedirectResponse
    {
        $cart->clear($request);

        return redirect()->route('cart.index')->with('status', 'Korpa je ispražnjena.');
    }

    private function availableProduct(
        Request $request,
        Product $product,
        CatalogAccessService $access,
    ): Product {
        /** @var Builder $query */
        $query = Product::query()
            ->publiclyVisible()
            ->whereKey((int) $product->getKey());
        $access->apply($query, $request->user());

        /** @var Product $available */
        $available = $query->firstOrFail();

        return $available;
    }
}