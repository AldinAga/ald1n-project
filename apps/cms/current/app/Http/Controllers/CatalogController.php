<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CatalogAccessService;
use App\Services\CatalogQueryService;
use App\Services\CatalogReferenceCache;
use App\Services\CommissionCalculator;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class CatalogController extends Controller
{
    public function index(
        Request $request,
        CatalogQueryService $catalog,
        CatalogReferenceCache $referenceCache,
        SettingsService $settings,
        CommissionCalculator $commission,
        CatalogAccessService $access,
    ): View|\Illuminate\Http\RedirectResponse {
        $user = $request->user();

        // ALD1N B8F V4: archived status bridges to Archive Center; archived rows stay outside the normal catalog.
        if ($access->canCreateProducts($user) && strtolower(trim((string) $request->query('status', ''))) === 'archived') {
            $archiveQuery = [];
            $archiveSearch = trim((string) $request->query('q', ''));
            if ($archiveSearch !== '') {
                $archiveQuery['q'] = $archiveSearch;
            }

            return redirect()->route('admin.products.archived', $archiveQuery);
        }
        $perPageSessionKey = 'catalog.per_page.'.(int) $user->getAuthIdentifier();
        $allowedPerPage = ['20', '50', '100', 'all'];
        $requestedPerPage = strtolower(trim((string) $request->query('per_page', '')));

        if (in_array($requestedPerPage, $allowedPerPage, true)) {
            $perPageOption = $requestedPerPage;
            $request->session()->put($perPageSessionKey, $perPageOption);
        } else {
            $perPageOption = strtolower(trim((string) $request->session()->get($perPageSessionKey, '20')));
            if (!in_array($perPageOption, $allowedPerPage, true)) {
                $perPageOption = '20';
            }
        }

        $products = $catalog->paginate(
            $user,
            $request->query(),
            $perPageOption === 'all' ? 20 : (int) $perPageOption,
            $perPageOption === 'all',
            100,
        );
        $rate = $settings->eurRsdRate();
        $products->getCollection()->transform(function (Product $product) use ($commission, $rate, $access, $user): Product {
            $product->setAttribute('commission_eur', $commission->unitEur(
                (float) $product->price_amount,
                $product->price_currency,
                $product->manual_commission_eur !== null ? (float) $product->manual_commission_eur : null,
                $rate,
            ));
            $product->setAttribute('can_manage', $access->canManage($product, $user));
            $product->setAttribute('can_manage_images', $access->canManageImages($product, $user));

            return $product;
        });

        $referenceOptions = $referenceCache->options();

        return view('catalog.index', [
            'products' => $products,
            'brands' => $referenceOptions['brands'],
            'types' => $referenceOptions['types'],
            'lines' => $referenceOptions['lines'],
            'categories' => $referenceOptions['categories'],
            'filterFields' => $referenceOptions['filterFields'],
            'catalogPerPage' => $perPageOption,
            'canViewPrices' => $user->can('catalog.view_prices'),
            'canManageCatalog' => $access->canCreateProducts($user),
            'isSuperAdministrator' => $user->hasRole('superadmin'),
        ]);
    }

    public function quickSearch(Request $request, CatalogQueryService $catalog): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:80'],
        ]);

        $query = trim((string) $data['q']);
        $products = $catalog->quickSearch($request->user(), $query, 8);

        return response()->json([
            'query' => $query,
            'results' => $products->map(static function (Product $product): array {
                $status = (string) $product->status;

                return [
                    'id' => (int) $product->id,
                    'name' => (string) $product->name,
                    'sku' => (string) $product->sku,
                    'brand' => $product->brand?->name,
                    'stock_quantity' => (int) $product->stock_quantity,
                    'status' => $status,
                    'status_label' => match ($status) {
                        'active' => 'Aktivan',
                        'draft' => 'Nacrt',
                        'inactive' => 'Neaktivan',
                        default => $status,
                    },
                    'url' => route('catalog.show', ['slug' => $product->slug]),
                ];
            })->values()->all(),
        ]);
    }

    public function show(
        Request $request,
        string $slug,
        CatalogQueryService $catalog,
        SettingsService $settings,
        CommissionCalculator $commission,
        CatalogAccessService $access,
    ): View {
        $user = $request->user();
        $product = $catalog->findVisibleBySlug($user, $slug);
        $rate = $settings->eurRsdRate();
        $commissionEur = $commission->unitEur(
            (float) $product->price_amount,
            (string) $product->price_currency,
            $product->manual_commission_eur !== null ? (float) $product->manual_commission_eur : null,
            $rate,
        );

        $canRecordDirectSale = $user->hasRole('superadmin')
            && in_array((string) $product->status, ['active', 'inactive'], true)
            && $product->deleted_at === null;        return view('catalog.show', [
            'product' => $product,
            'commissionEur' => $commissionEur,
            'canViewPrices' => $user->can('catalog.view_prices'),
            'canManageProduct' => $access->canManage($product, $user),
            'canManageImages' => $access->canManageImages($product, $user),
            'canRecordDirectSale' => $canRecordDirectSale,
            'directSaleIdempotencyKey' => $canRecordDirectSale ? (string) Str::uuid() : null,
        ]);
    }
}
