<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductLine;
use App\Models\ProductType;
use Illuminate\Support\Facades\Cache;

final class CatalogReferenceCache
{
    private const KEY = 'catalog.reference-options.v2.1.6';

    public function __construct(private readonly CatalogSpecificationFilterService $specificationFilters) {}

    /** @return array{brands:mixed,types:mixed,lines:mixed,categories:mixed,filterFields:mixed} */
    public function options(): array
    {
        $seconds = max(30, (int) config('performance.catalog_reference_cache_seconds', 600));

        return Cache::remember(self::KEY, now()->addSeconds($seconds), function (): array {
            return [
                'brands' => Brand::query()
                    ->select(['id', 'name', 'slug', 'sort_order'])
                    ->where('status', 'active')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(),
                'types' => ProductType::query()
                    ->select(['id', 'name', 'slug', 'sort_order'])
                    ->where('status', 'active')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(),
                'lines' => ProductLine::query()
                    ->select(['id', 'brand_id', 'name', 'slug', 'sort_order'])
                    ->where('status', 'active')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(),
                'categories' => Category::query()
                    ->select(['id', 'name', 'slug', 'sort_order'])
                    ->where('status', 'active')
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->get(),
                'filterFields' => $this->specificationFilters->fields(),
            ];
        });
    }

    public function forget(): void
    {
        Cache::forget(self::KEY);
    }
}
