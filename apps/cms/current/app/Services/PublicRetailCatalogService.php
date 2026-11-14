<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\PublicCatalogPresentation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

final class PublicRetailCatalogService
{
    /** Image columns the public photo view model needs (PublicProductImages). */
    private const PHOTO_COLUMNS = 'id,product_id,storage_disk,file_path,file_hash,is_primary,sort_order';

    public function __construct(private readonly SettingsService $settings) {}

    /** @return Builder<Product> */
    public function available(): Builder
    {
        // Strict public allowlist: never inherit a logged-in user's catalog scope.
        return Product::query()->select([
            'id', 'slug', 'sku', 'name', 'description', 'model_name',
            'price_amount', 'price_currency', 'stock_quantity', 'brand_id',
            'product_type_id', 'status', 'deleted_at', 'created_at',
        ])->publiclyVisible()->where('price_amount', '>', 0);
    }

    /** @param array<string,mixed> $filters */
    public function search(array $filters): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $query = $this->available()->with([
            'brand:id,name', 'images:'.self::PHOTO_COLUMNS,
        ]);
        if ($q !== '') {
            $term = '%'.addcslashes(mb_substr($q, 0, 80), '\\%_').'%';
            $query->where(static fn (Builder $inner): Builder => $inner
                ->where('name', 'like', $term)
                ->orWhere('sku', 'like', $term)
                ->orWhere('model_name', 'like', $term));
        }
        if ((int) ($filters['brand_id'] ?? 0) > 0) {
            $query->where('brand_id', (int) $filters['brand_id']);
        }
        if ((int) ($filters['category_id'] ?? 0) > 0) {
            $categoryId = (int) $filters['category_id'];
            $query->whereHas('categories', static fn (Builder $category): Builder => $category
                ->where('categories.status', 'active')->whereKey($categoryId));
        }
        if (($filters['stock'] ?? null) === 'available') {
            $query->where('stock_quantity', '>', 0);
        }
        switch ($filters['sort'] ?? '') {
            case 'name':
                $query->orderBy('name')->orderBy('id'); break;
            case 'price_asc':
            case 'price_desc':
                // price_amount mixes RSD and EUR rows: compare the RSD amount
                // the cart would charge (same rule as priceRsd()). Rows with
                // no RSD equivalent (unset rate, other currency) sort last.
                $rate = $this->settings->eurRsdRate();
                $direction = $filters['sort'] === 'price_desc' ? 'desc' : 'asc';
                if ($rate !== null && is_finite($rate) && $rate > 0) {
                    $expression = "CASE WHEN price_currency = 'RSD' THEN price_amount "
                        ."WHEN price_currency = 'EUR' THEN price_amount * ? ELSE NULL END";
                    $bindings = [$rate];
                } else {
                    $expression = "CASE WHEN price_currency = 'RSD' THEN price_amount ELSE NULL END";
                    $bindings = [];
                }
                $query->orderByRaw('('.$expression.') IS NULL', $bindings)
                    ->orderByRaw('('.$expression.') '.$direction, $bindings)
                    ->orderByDesc('id');
                break;
            default:
                $query->orderByDesc('id');
        }
        // B901: the paginator otherwise derives an absolute site origin.
        // Internal page links and filter state must stay on the current origin,
        // independent of Host/proxy headers and deploy-time APP_URL.
        // per_page and view are whitelisted here, never taken from the request as-is.
        // appends() overrides the raw query values; null (the default) drops the key.
        $perPage = PublicCatalogPresentation::perPage($filters['per_page'] ?? null);
        $view = PublicCatalogPresentation::viewMode($filters['view'] ?? null);

        return $query->paginate($perPage)
            ->withPath(route('public-shop.catalog', [], false))
            ->withQueryString()
            ->appends([
                'per_page' => $perPage === PublicCatalogPresentation::DEFAULT_PER_PAGE ? null : $perPage,
                'view' => $view === PublicCatalogPresentation::DEFAULT_VIEW ? null : $view,
            ]);
    }

    public function bySlug(string $slug): Product
    {
        return $this->available()->where('slug', $slug)
            ->with(['brand:id,name','images:'.self::PHOTO_COLUMNS,
                'specificationValues.field', 'categories:id,name,status'])->firstOrFail();
    }

    public function byId(int $id): Product
    {
        return $this->available()->whereKey($id)
            ->with(['brand:id,name','images:id,product_id'])->firstOrFail();
    }

    /** @return array<string,mixed> Public presentation ONLY; no commissions/costs/creator */
    public function present(Product $product): array
    {
        $unit = $this->priceRsd($product);
        $image = $product->images->first();
        return [
            'id' => (int) $product->id,
            'slug' => (string) $product->slug,
            'name' => (string) $product->name,
            'sku' => (string) $product->sku,
            'description' => (string) ($product->description ?? ''),
            'brand' => (string) ($product->brand?->name ?? ''),
            'stock' => max(0,(int) $product->stock_quantity),
            'price_original' => number_format((float) $product->price_amount, 2, ',', '.'),
            'currency' => (string) $product->price_currency,
            'price_rsd' => $unit,
            'can_buy' => $unit !== null && $unit > 0 && (int) $product->stock_quantity > 0,
            'image' => $image ? route('public-shop.media', ['image' => $image->id], false) : null,
        ];
    }

    public function priceRsd(Product $product): ?float
    {
        $amount = (float) $product->price_amount;
        if (!is_finite($amount) || $amount <= 0) return null;
        if ((string) $product->price_currency === 'RSD') return round($amount, 2);
        if ((string) $product->price_currency !== 'EUR') return null;
        $rate = $this->settings->eurRsdRate();
        if ($rate === null || $rate <= 0) return null;
        return round($amount * $rate, 2);
    }

    public function requirePurchasable(Product $product, int $quantity): float
    {
        $price = $this->priceRsd($product);
        if ($price === null || (int) $product->stock_quantity < $quantity || $quantity < 1) {
            throw ValidationException::withMessages([
                'cart' => 'Izabrani artikal trenutno nije raspoloživ ili cena nije podešena.',
            ]);
        }
        return $price;
    }

    /** @return array{brands:mixed,categories:mixed} */
    public function filters(): array
    {
        return [
            'brands' => Brand::query()->where('status','active')->whereHas('products',
                static fn (Builder $q) => $q->publiclyVisible()->where('price_amount','>',0)
            )->orderBy('name')->get(['id','name']),
            'categories' => Category::query()->where('status','active')->whereHas('products',
                static fn (Builder $q) => $q->publiclyVisible()->where('price_amount','>',0)
            )->orderBy('name')->get(['id','name']),
        ];
    }
}
