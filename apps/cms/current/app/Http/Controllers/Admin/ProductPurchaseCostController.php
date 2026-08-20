<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

final class ProductPurchaseCostController extends Controller
{
    public function index(Request $request): View
    {
        $this->assertSuperAdmin($request);
        $showAll = $request->query('show') === 'all';
        $base = Product::query()->whereNull('deleted_at');
        $missingTotal = (clone $base)->where(static function ($query): void {
            $query->whereNull('purchase_price_rsd')->orWhere('purchase_price_rsd', '<=', 0);
        })->count();
        $missingPositiveStock = (clone $base)->where('stock_quantity', '>', 0)->where(static function ($query): void {
            $query->whereNull('purchase_price_rsd')->orWhere('purchase_price_rsd', '<=', 0);
        })->count();

        $products = clone $base;
        if (!$showAll) {
            $products->where(static function ($query): void {
                $query->whereNull('purchase_price_rsd')->orWhere('purchase_price_rsd', '<=', 0);
            });
        }

        return view('admin.products.purchase-costs', [
            'products' => $products->orderBy('name')->orderBy('sku')->get([
                'id', 'name', 'sku', 'stock_quantity', 'price_amount', 'price_currency', 'purchase_price_rsd',
            ]),
            'showAll' => $showAll,
            'missingTotal' => $missingTotal,
            'missingPositiveStock' => $missingPositiveStock,
        ]);
    }

    public function update(Request $request, AuditLogger $audit): RedirectResponse
    {
        $this->assertSuperAdmin($request);
        $raw = (array) $request->input('costs', []);
        $normalized = [];
        foreach ($raw as $id => $value) {
            $text = trim((string) $value);
            $normalized[(string) $id] = $text === '' ? null : str_replace(',', '.', $text);
        }
        $request->merge(['costs' => $normalized]);
        $data = $request->validate([
            'costs' => ['required', 'array', 'max:500'],
            'costs.*' => ['nullable', 'numeric', 'min:0.01', 'max:9999999999.99'],
        ]);

        $costs = [];
        foreach ((array) $data['costs'] as $id => $value) {
            if ($value === null || $value === '') continue;
            $productId = (int) $id;
            if ($productId > 0) $costs[$productId] = round((float) $value, 2);
        }
        if ($costs === []) {
            throw ValidationException::withMessages(['costs' => 'Unesite najmanje jednu nabavnu cenu.']);
        }

        $actor = $request->user();
        $changed = DB::transaction(function () use ($costs, $actor, $audit): int {
            $products = Product::query()->whereNull('deleted_at')->whereIn('id', array_keys($costs))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($products->count() !== count($costs)) {
                throw ValidationException::withMessages(['costs' => 'Jedan ili više artikala više nisu dostupni. Osvežite stranicu.']);
            }
            $changed = 0;
            foreach ($costs as $productId => $cost) {
                /** @var Product $product */
                $product = $products->get($productId);
                $before = $product->purchase_price_rsd === null ? null : round((float) $product->purchase_price_rsd, 2);
                if ($before !== null && abs($before - $cost) < 0.005) continue;
                $product->update([
                    'purchase_price_rsd' => $cost,
                    'updated_by' => $actor?->getAuthIdentifier(),
                    'locally_modified_at' => now(),
                ]);
                $audit->log(
                    'product.purchase_cost.updated',
                    'Ažurirana nabavna cena artikla '.$product->sku,
                    $product,
                    ['purchase_price_rsd' => $before],
                    ['purchase_price_rsd' => $cost],
                    ['source' => 'superadmin_fast_purchase_cost_entry'],
                    $actor,
                );
                $changed++;
            }
            return $changed;
        }, 3);

        return redirect()
            ->route('admin.products.purchase-costs')
            ->with('status', $changed > 0 ? 'Sačuvane su nabavne cene za '.$changed.' artikala.' : 'Nema promena za čuvanje.');
    }

    private function assertSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('superadmin') === true, 403, 'Brzi unos nabavnih cena dostupan je samo Super Administratoru.');
    }
}
