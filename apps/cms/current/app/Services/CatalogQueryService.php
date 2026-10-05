<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

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
                'presentationImage',
                'creator:id,first_name,last_name,email',
                'specificationValues.field',
            ]);

        $this->access->applyVisibleCatalog($query, $user);

        return $query->firstOrFail();
    }

    /** @return Collection<int,Product> */
    public function quickSearch(User $user, string $term, int $limit = 8): Collection
    {
        $search = trim($term);
        if (mb_strlen($search) < 2) {
            return collect();
        }

        $safeLimit = max(1, min(12, $limit));
        $escaped = addcslashes($search, '\\%_');
        $contains = '%'.$escaped.'%';
        $prefix = $escaped.'%';

        $query = Product::query()
            ->select(['id', 'brand_id', 'sku', 'name', 'model_name', 'slug', 'stock_quantity', 'status', 'deleted_at'])
            ->with('brand:id,name')
            ->where(static function (Builder $nested) use ($contains): void {
                $nested->where('name', 'like', $contains)
                    ->orWhere('sku', 'like', $contains)
                    ->orWhere('model_name', 'like', $contains);
            });

        $this->access->applyVisibleCatalog($query, $user);

        return $query
            ->orderByRaw(
                'CASE WHEN name = ? THEN 0 WHEN sku = ? THEN 1 WHEN name LIKE ? THEN 2 WHEN sku LIKE ? THEN 3 ELSE 4 END',
                [$search, $search, $prefix, $prefix],
            )
            ->orderBy('name')
            ->limit($safeLimit)
            ->get();
    }

    /** @param array<string,mixed> $filters */
    public function paginate(User $user, array $filters, int $perPage = 18, bool $all = false, int $maximumPerPage = 60): LengthAwarePaginator
    {
        $query = Product::query()
            ->with([
                'brand:id,name,slug',
                'line:id,name,slug,brand_id',
                'type:id,name,slug',
                'categories:id,name,slug',
                'creator:id,first_name,last_name,email',
                'presentationImage',
            ])
            ->withCount('images');

        $this->access->applyVisibleCatalog($query, $user);
        $canManage = $this->access->canCreateProducts($user);

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(static function (Builder $nested) use ($search): void {
                $like = '%'.$search.'%';
                $nested->where('name', 'like', $like)
                    ->orWhere('model_name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('description', 'like', $like);
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
                'archived' => $query->whereRaw('1 = 0'),
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

        if ($all) {
            $items = $query->get();
            $count = $items->count();

            return (new LengthAwarePaginator(
                $items,
                $count,
                max(1, $count),
                1,
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'page'],
            ))->withQueryString();
        }

        $safeMaximum = max(6, $maximumPerPage);

        return $query->paginate(max(6, min($safeMaximum, $perPage)))->withQueryString();
    }
}
