<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class CatalogAccessService
{
    public function apply(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin', 'superadmin')) {
            return $query;
        }

        $group = $user->group()->with('categories:id')->first();
        if ($group === null || $group->status !== 'active' || !$user->hasPermission('catalog.view')) {
            return $query->whereRaw('1 = 0');
        }

        if ($group->category_access_mode === 'all') {
            return $query;
        }

        if ($group->category_access_mode !== 'selected') {
            return $group->include_uncategorized
                ? $query->whereDoesntHave('categories')
                : $query->whereRaw('1 = 0');
        }

        $allowedIds = $this->descendantsIncludingSelf($group->categories->pluck('id')->map(fn ($id) => (int) $id)->all());
        if ($allowedIds === []) {
            return $group->include_uncategorized
                ? $query->whereDoesntHave('categories')
                : $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $nested) use ($allowedIds, $group): void {
            $nested->whereHas('categories', static fn (Builder $categoryQuery) => $categoryQuery->whereIn('categories.id', $allowedIds));
            if ($group->include_uncategorized) {
                $nested->orWhereDoesntHave('categories');
            }
        });
    }

    /**
     * Primeni objedinjeni katalog scope.
     *
     * - obican korisnik vidi samo aktivne artikle dozvoljenih kategorija;
     * - administrator vidi isključivo artikle koje je sam kreirao, bez obzira na status;
     * - SuperAdministrator vidi kompletan katalog.
     */
    public function applyVisibleCatalog(Builder $query, User $user): Builder
    {
        if ($this->canCreateProducts($user)) {
            if ($user->hasRole('superadmin')) {
                return $query;
            }

            return $query->where('created_by', (int) $user->getAuthIdentifier());
        }

        $query->where('status', 'active')->whereNull('deleted_at');

        return $this->apply($query, $user);
    }

    /** Primeni scope proizvoda koje korisnik sme da menja. */
    public function applyManageable(Builder $query, User $user): Builder
    {
        if (!$this->canCreateProducts($user)) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('superadmin')) {
            return $query;
        }

        return $query->where('created_by', (int) $user->getAuthIdentifier());
    }

    public function canView(Product $product, User $user): bool
    {
        return $this->canViewInUnifiedCatalog($product, $user);
    }

    public function canViewInUnifiedCatalog(Product $product, User $user): bool
    {
        return $this->applyVisibleCatalog(Product::query()->whereKey($product->getKey()), $user)->exists();
    }

    public function canCreateProducts(User $user): bool
    {
        return $user->can('catalog.manage_products');
    }

    public function canManage(Product $product, User $user): bool
    {
        if (!$this->canCreateProducts($user)) {
            return false;
        }

        return $user->hasRole('superadmin')
            || (int) $product->created_by === (int) $user->getAuthIdentifier();
    }

    public function canManageImages(Product $product, User $user): bool
    {
        return $user->can('catalog.manage_images') && $this->canManage($product, $user);
    }

    /** @param list<int> $rootIds @return list<int> */
    private function descendantsIncludingSelf(array $rootIds): array
    {
        $rootIds = array_values(array_unique(array_filter($rootIds, static fn (int $id): bool => $id > 0)));
        if ($rootIds === []) {
            return [];
        }

        $children = Category::query()->where('status', 'active')->get(['id', 'parent_id'])->groupBy('parent_id');
        $result = [];
        $queue = $rootIds;
        while ($queue !== []) {
            $id = array_shift($queue);
            if (isset($result[$id])) {
                continue;
            }
            $result[$id] = true;
            foreach ($children->get($id, collect()) as $child) {
                $queue[] = (int) $child->id;
            }
        }

        return array_map('intval', array_keys($result));
    }
}
