<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class CatalogQueryService
{
    public function __construct(
        private readonly CatalogAccessService $access,
        private readonly CatalogSpecificationFilterService $specificationFilters,
    ) {}

    public function findVisibleBySlug(User $user, string $slug): Product
    {
        $query = Product::query()
            ->where('slug', $slug)
            ->with([
                'brand',
                'line',
                'type',
                'categories',
                'images',
                'creator:id,first_name,last_name,email',
                'specificationValues.field',
                'activeVariants.specificationValues.field',
                'activeVariants.images',
            ]);

        $this->access->applyVisibleCatalog($query, $user);

        return $query->firstOrFail();
    }

    /** @param array<string,mixed> $filters */
    public function paginate(User $user, array $filters, int $perPage = 18): LengthAwarePaginator
    {
        $query = Product::query()
            ->with([
                'brand:id,name,slug',
                'line:id,name,slug,brand_id',
                'type:id,name,slug',
                'categories:id,name,slug',
                'creator:id,first_name,last_name,email',
                'primaryImage',
                'activeVariants:id,product_id,sku,name,price_amount,price_currency,stock_quantity,is_default',
            ])
            ->withCount(['images', 'variants', 'activeVariants']);

        $this->access->applyVisibleCatalog($query, $user);
        $canManage = $this->access->canCreateProducts($user);

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(static function (Builder $nested) use ($search): void {
                $like = '%'.$search.'%';
                $nested->where('name', 'like', $like)
                    ->orWhere('model_name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('activeVariants', static fn (Builder $variantQuery) => $variantQuery
                        ->where('sku', 'like', $like)
                        ->orWhere('name', 'like', $like));
            });
        }

        foreach (['brand_id', 'product_type_id', 'product_line_id'] as $column) {
            $value = (int) ($filters[$column] ?? 0);
            if ($value > 0) {
                $query->where($column, $value);
            }
        }

        $this->specificationFilters->apply($query, $filters);

        $categoryId = (int) ($filters['category_id'] ?? 0);
        if ($categoryId > 0) {
            $query->whereHas('categories', static fn (Builder $categoryQuery) => $categoryQuery->where('categories.id', $categoryId));
        }

        if ($canManage) {
            $status = trim((string) ($filters['status'] ?? ''));
            match ($status) {
                'draft', 'inactive' => $query->where('status', $status)->whereNull('deleted_at'),
                'active' => $query->where('status', 'active')->whereNull('deleted_at'),
                'archived' => $query->whereNotNull('deleted_at'),
                default => null,
            };

            if ($user->hasRole('superadmin')) {
                $ownership = trim((string) ($filters['ownership'] ?? ''));
                if ($ownership === 'mine') {
                    $query->where('created_by', (int) $user->getAuthIdentifier());
                } elseif ($ownership === 'unassigned') {
                    $query->whereNull('created_by');
                }
            }
        } else {
            $query->where('status', 'active')->whereNull('deleted_at');
        }

        if ($canManage) {
            $quality = trim((string) ($filters['quality'] ?? ''));
            match ($quality) {
                'missing_image' => $query->whereDoesntHave('images'),
                'missing_price' => $query->where('price_amount', '<=', 0),
                'missing_model' => $query->where(static function (Builder $nested): void {
                    $nested->whereNull('model_name')->orWhereRaw("TRIM(model_name) = ''");
                }),
                'incomplete' => $query->where('completeness_percent', '<', 100),
                'unassigned' => $user->hasRole('superadmin') ? $query->whereNull('created_by') : null,
                default => null,
            };
        }

        $stock = trim((string) ($filters['stock'] ?? ''));
        if ($stock === 'out') {
            $query->where('stock_quantity', 0);
        } elseif ($stock === 'low') {
            $query->where('stock_quantity', '>', 0)->whereColumn('stock_quantity', '<=', 'low_stock_threshold');
        } elseif ($stock === 'available') {
            $query->where('stock_quantity', '>', 0);
        }

        $sort = (string) ($filters['sort'] ?? 'newest');
        match ($sort) {
            'name' => $query->orderBy('name'),
            'price_asc' => $query->orderBy('price_amount'),
            'price_desc' => $query->orderByDesc('price_amount'),
            'updated' => $query->orderByDesc('updated_at'),
            default => $query->orderByDesc('created_at'),
        };

        return $query->paginate(max(6, min(60, $perPage)))->withQueryString();
    }
}
