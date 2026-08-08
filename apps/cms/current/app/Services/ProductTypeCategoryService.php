<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Category;
use App\Models\ProductType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class ProductTypeCategoryService
{
    /**
     * @return array{examined:int,matched:int,reactivated:int,created:int,products_synced:int}
     */
    public function ensureAll(?int $actorId = null): array
    {
        $summary = [
            'examined' => 0,
            'matched' => 0,
            'reactivated' => 0,
            'created' => 0,
            'products_synced' => 0,
        ];

        ProductType::query()
            ->where('status', 'active')
            ->orderBy('id')
            ->chunkById(100, function ($types) use (&$summary, $actorId): void {
                foreach ($types as $type) {
                    $result = $this->ensureForType($type, $actorId);
                    $summary['examined']++;
                    $summary[$result['action']]++;
                    $summary['products_synced'] += $result['products_synced'];
                }
            });

        return $summary;
    }

    /**
     * @return array{category:Category,action:'matched'|'reactivated'|'created',products_synced:int}
     */
    public function ensureForType(ProductType $type, ?int $actorId = null): array
    {
        return DB::transaction(function () use ($type, $actorId): array {
            /** @var ProductType $locked */
            $locked = ProductType::query()->lockForUpdate()->findOrFail($type->id);
            $sourceCategoryIds = $this->assignedCategoryIds((int) $locked->id);
            $category = $locked->category_id !== null
                ? Category::query()->lockForUpdate()->find((int) $locked->category_id)
                : null;

            $action = 'matched';
            if ($category === null) {
                $category = $this->findMatchingCategory($locked);
            }
            if ($category === null) {
                $category = $this->mostUsedAssignedCategory((int) $locked->id);
            }
            if ($category === null) {
                $category = new Category();
                $category->forceFill([
                    'name' => trim((string) $locked->name),
                    'slug' => $this->uniqueCategorySlug((string) ($locked->slug ?: $locked->name)),
                    'description' => 'Automatska sistemska kategorija za tip proizvoda „'.trim((string) $locked->name).'“.',
                    'status' => 'active',
                    'sort_order' => (int) ($locked->sort_order ?? 0),
                    'created_by' => $actorId ?? $locked->created_by,
                    'updated_by' => $actorId ?? $locked->updated_by,
                ])->save();
                $action = 'created';
                $this->inheritSelectedGroupAccess((int) $category->id, $sourceCategoryIds);
            } elseif ((string) $category->status !== 'active') {
                $category->forceFill([
                    'status' => 'active',
                    'updated_by' => $actorId ?? $locked->updated_by,
                ])->save();
                $action = 'reactivated';
            }

            if ((int) $locked->category_id !== (int) $category->id) {
                $locked->forceFill([
                    'category_id' => (int) $category->id,
                    'updated_by' => $actorId ?? $locked->updated_by,
                ])->save();
            }

            $productsSynced = $this->syncProducts($locked->fresh() ?? $locked);

            return [
                'category' => $category,
                'action' => $action,
                'products_synced' => $productsSynced,
            ];
        }, 3);
    }

    public function syncProducts(ProductType $type): int
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('product_categories') || $type->category_id === null) {
            return 0;
        }

        $count = 0;
        DB::table('products')
            ->where('product_type_id', $type->id)
            ->orderBy('id')
            ->select('id')
            ->chunkById(250, function ($products) use ($type, &$count): void {
                $ids = $products->pluck('id')->map(static fn ($id): int => (int) $id)->all();
                if ($ids === []) {
                    return;
                }

                DB::table('product_categories')->whereIn('product_id', $ids)->delete();
                DB::table('product_categories')->insertOrIgnore(array_map(static fn (int $productId): array => [
                    'product_id' => $productId,
                    'category_id' => (int) $type->category_id,
                ], $ids));
                $count += count($ids);
            });

        return $count;
    }

    /** @return list<int> */
    private function assignedCategoryIds(int $productTypeId): array
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('product_categories')) {
            return [];
        }

        return DB::table('products')
            ->join('product_categories', 'product_categories.product_id', '=', 'products.id')
            ->where('products.product_type_id', $productTypeId)
            ->distinct()
            ->pluck('product_categories.category_id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    private function findMatchingCategory(ProductType $type): ?Category
    {
        $slug = Str::slug((string) ($type->slug ?: $type->name));
        if ($slug !== '') {
            $category = Category::query()->where('slug', $slug)->first();
            if ($category !== null) {
                return $category;
            }
        }

        $name = mb_strtolower(trim((string) $type->name));
        if ($name !== '') {
            $category = Category::query()->whereRaw('LOWER(name) = ?', [$name])->first();
            if ($category !== null) {
                return $category;
            }
        }

        $canonical = $this->canonicalKey((string) $type->name.' '.(string) $type->slug);
        if ($canonical === '') {
            return null;
        }

        return Category::query()
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->get(['id', 'name', 'slug', 'status'])
            ->first(fn (Category $category): bool => $this->canonicalKey((string) $category->name.' '.(string) $category->slug) === $canonical);
    }

    private function mostUsedAssignedCategory(int $productTypeId): ?Category
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('product_categories')) {
            return null;
        }

        $categoryId = DB::table('products')
            ->join('product_categories', 'product_categories.product_id', '=', 'products.id')
            ->where('products.product_type_id', $productTypeId)
            ->select('product_categories.category_id', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('product_categories.category_id')
            ->orderByDesc('usage_count')
            ->orderBy('product_categories.category_id')
            ->value('product_categories.category_id');

        return $categoryId !== null ? Category::query()->find((int) $categoryId) : null;
    }

    /** @param list<int> $sourceCategoryIds */
    private function inheritSelectedGroupAccess(int $newCategoryId, array $sourceCategoryIds): void
    {
        if ($sourceCategoryIds === [] || !Schema::hasTable('user_group_categories')) {
            return;
        }

        $groupIds = DB::table('user_group_categories')
            ->whereIn('category_id', $sourceCategoryIds)
            ->distinct()
            ->pluck('group_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($groupIds === []) {
            return;
        }

        DB::table('user_group_categories')->insertOrIgnore(array_map(static fn (int $groupId): array => [
            'group_id' => $groupId,
            'category_id' => $newCategoryId,
            'created_at' => now(),
        ], $groupIds));
    }

    private function uniqueCategorySlug(string $value): string
    {
        $base = Str::slug($value) ?: 'tip-proizvoda';
        $slug = $base;
        $suffix = 2;
        while (Category::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function canonicalKey(string $value): string
    {
        $slug = Str::slug($value);
        $tokens = array_values(array_filter(explode('-', $slug)));
        $aliases = [
            'laptop' => 'laptop', 'laptops' => 'laptop', 'laptopovi' => 'laptop', 'notebook' => 'laptop', 'notebooks' => 'laptop',
            'pc' => 'pc', 'pcs' => 'pc', 'racunar' => 'pc', 'racunari' => 'pc', 'computer' => 'pc', 'computers' => 'pc', 'desktop' => 'pc',
            'monitor' => 'monitor', 'monitors' => 'monitor', 'monitori' => 'monitor',
            'printer' => 'printer', 'printers' => 'printer', 'stampac' => 'printer', 'stampaci' => 'printer',
            'telefon' => 'phone', 'telefoni' => 'phone', 'phone' => 'phone', 'phones' => 'phone', 'smartphone' => 'phone',
            'tablet' => 'tablet', 'tablets' => 'tablet', 'tableti' => 'tablet',
            'komponenta' => 'component', 'komponente' => 'component', 'component' => 'component', 'components' => 'component',
        ];
        $ignored = ['tip', 'tipovi', 'proizvod', 'proizvodi', 'artikli', 'artikal', 'uredjaj', 'uredjaji', 'i', 'za'];

        $normalized = [];
        foreach ($tokens as $token) {
            if (in_array($token, $ignored, true)) {
                continue;
            }
            $normalized[] = $aliases[$token] ?? $token;
        }
        sort($normalized);

        return implode('-', array_values(array_unique($normalized)));
    }
}
