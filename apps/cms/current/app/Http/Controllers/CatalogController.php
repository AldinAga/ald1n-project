<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CatalogAccessService;
use App\Services\CatalogQueryService;
use App\Services\CatalogReferenceCache;
use App\Services\CommissionCalculator;
use App\Services\SettingsService;
use Illuminate\Http\Request;
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
    ): View {
        $user = $request->user();
        $products = $catalog->paginate($user, $request->query(), 18);
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
            'canViewPrices' => $user->can('catalog.view_prices'),
            'canManageCatalog' => $access->canCreateProducts($user),
            'isSuperAdministrator' => $user->hasRole('superadmin'),
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
        $product->activeVariants->each(function ($variant) use ($commission, $rate): void {
            $variant->setAttribute('commission_eur', $commission->unitEur(
                (float) $variant->price_amount,
                (string) $variant->price_currency,
                $variant->manual_commission_eur !== null ? (float) $variant->manual_commission_eur : null,
                $rate,
            ));
        });

        return view('catalog.show', [
            'product' => $product,
            'commissionEur' => $commissionEur,
            'canViewPrices' => $user->can('catalog.view_prices'),
            'canManageProduct' => $access->canManage($product, $user),
            'canManageImages' => $access->canManageImages($product, $user),
        ]);
    }
}
