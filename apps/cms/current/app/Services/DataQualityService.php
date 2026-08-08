<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DataQualitySnapshot;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class DataQualityService
{
    public function __construct(
        private readonly ProductTypeCategoryService $typeCategories,
        private readonly SpecificationFieldLifecycleService $fieldLifecycle,
        private readonly StorageSpecificationService $storage,
        private readonly ProductCompletenessService $completeness,
    ) {}

    /** @return array<string,mixed> */
    public function audit(int $limit = 20): array
    {
        $started = microtime(true);
        $limit = max(1, min(100, $limit));
        $issues = [];

        try {
            $this->appendCatalogIdentityIssues($issues, $limit);
            $this->appendCatalogCompletenessIssues($issues, $limit);
            $this->appendCategoryIssues($issues, $limit);
            $this->appendImageIssues($issues, $limit);
            $this->appendVariantIssues($issues, $limit);
            $this->appendSpecificationIssues($issues, $limit);
            $this->appendUserIssues($issues, $limit);
        } catch (Throwable $exception) {
            $issues[] = $this->issue(
                'audit_runtime_error',
                'Data quality audit nije mogao da završi sve SQL provere',
                'critical',
                1,
                $exception::class.': '.$exception->getMessage(),
                false,
                [],
            );
        }

        $critical = array_sum(array_map(static fn (array $issue): int => $issue['severity'] === 'critical' ? (int) $issue['count'] : 0, $issues));
        $warning = array_sum(array_map(static fn (array $issue): int => $issue['severity'] === 'warning' ? (int) $issue['count'] : 0, $issues));
        $info = array_sum(array_map(static fn (array $issue): int => $issue['severity'] === 'info' ? (int) $issue['count'] : 0, $issues));
        $score = max(0, 100 - min(60, $critical * 15) - min(30, $warning * 4) - min(10, intdiv($info + 4, 5)));
        $status = $critical > 0 ? 'critical' : ($warning > 0 ? 'attention' : 'healthy');

        return [
            'generated_at' => now()->toIso8601String(),
            'status' => $status,
            'score' => $score,
            'summary' => [
                'critical' => $critical,
                'warning' => $warning,
                'info' => $info,
                'issue_groups' => count(array_filter($issues, static fn (array $issue): bool => (int) $issue['count'] > 0)),
            ],
            'metrics' => $this->metrics(),
            'issues' => array_values($issues),
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
        ];
    }

    /** @return array<string,mixed> */
    public function repairSafe(?int $actorId = null): array
    {
        $started = microtime(true);
        $summary = [
            'categories' => [],
            'specifications' => [],
            'storage' => [],
            'image_groups_fixed' => 0,
            'variant_groups_fixed' => 0,
            'blank_models_normalized' => 0,
            'completeness_recalculated' => 0,
            'products_downgraded' => 0,
        ];

        if (Schema::hasTable('product_types') && Schema::hasTable('categories') && Schema::hasTable('product_categories')) {
            $summary['categories'] = $this->typeCategories->ensureAll($actorId);
        }
        $summary['specifications'] = $this->fieldLifecycle->repairIntegrity();
        $summary['storage'] = $this->storage->repair();
        $summary['image_groups_fixed'] = $this->normalizeImagePrimaries();
        $summary['variant_groups_fixed'] = $this->normalizeDefaultVariants();

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'model_name')) {
            $summary['blank_models_normalized'] = DB::table('products')
                ->whereNotNull('model_name')
                ->whereRaw("TRIM(model_name) = ''")
                ->update(['model_name' => null]);
        }

        if (Schema::hasTable('products') && Schema::hasColumn('products', 'completeness_percent')) {
            $completeness = $this->completeness->recalculateAll(false);
            $summary['completeness_recalculated'] = (int) $completeness['examined'];
            $summary['products_downgraded'] = (int) $completeness['downgraded'];
        }

        app(CatalogReferenceCache::class)->forget();
        $summary['duration_ms'] = (int) round((microtime(true) - $started) * 1000);

        return $summary;
    }

    /** @param array<string,mixed> $report */
    public function storeSnapshot(array $report, string $source = 'command', ?int $actorId = null): ?DataQualitySnapshot
    {
        if (!Schema::hasTable('data_quality_snapshots')) {
            return null;
        }

        return DataQualitySnapshot::query()->create([
            'source' => mb_substr($source, 0, 30),
            'status' => (string) ($report['status'] ?? 'healthy'),
            'score' => max(0, min(100, (int) ($report['score'] ?? 0))),
            'summary_json' => (array) ($report['summary'] ?? []),
            'issues_json' => (array) ($report['issues'] ?? []),
            'metrics_json' => (array) ($report['metrics'] ?? []),
            'duration_ms' => max(0, (int) ($report['duration_ms'] ?? 0)),
            'run_by' => $actorId,
            'created_at' => now(),
        ]);
    }

    /** @return Collection<int,DataQualitySnapshot> */
    public function latestSnapshots(int $limit = 10): Collection
    {
        if (!Schema::hasTable('data_quality_snapshots')) {
            return collect();
        }

        return DataQualitySnapshot::query()
            ->with('runner:id,first_name,last_name,username')
            ->orderByDesc('id')
            ->limit(max(1, min(50, $limit)))
            ->get();
    }

    /** @param list<array<string,mixed>> $issues */
    private function appendCatalogIdentityIssues(array &$issues, int $limit): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        foreach ([
            ['sku', 'duplicate_product_sku', 'Duplirani SKU brojevi artikala'],
            ['slug', 'duplicate_product_slug', 'Duplirani URL slugovi artikala'],
        ] as [$column, $key, $label]) {
            if (!Schema::hasColumn('products', $column)) {
                continue;
            }
            $groups = DB::table('products')
                ->select($column, DB::raw('COUNT(*) AS aggregate_count'))
                ->whereNotNull($column)
                ->whereRaw('TRIM('.$column.') <> ?', [''])
                ->groupBy($column)
                ->havingRaw('COUNT(*) > 1')
                ->orderByDesc('aggregate_count')
                ->limit($limit)
                ->get();
            $count = (int) $groups->sum('aggregate_count');
            $issues[] = $this->issue($key, $label, 'critical', $count, 'Jedinstveni identitet artikla mora ostati bez duplikata.', false, $groups->map(static fn ($row): array => [
                'value' => (string) $row->{$column},
                'count' => (int) $row->aggregate_count,
            ])->all());
        }

        $missingOwner = 0;
        $ownerSamples = collect();
        if (Schema::hasColumn('products', 'created_by')) {
            $base = DB::table('products')->whereNull('deleted_at')->whereNull('created_by');
            $missingOwner = (int) (clone $base)->count();
            $ownerSamples = (clone $base)->select(['id', 'sku', 'name'])->orderBy('id')->limit($limit)->get();
        }
        $issues[] = $this->issue('products_without_owner', 'Artikli bez vlasnika', 'info', $missingOwner, 'Artikle bez vlasnika može da uređuje samo SuperAdministrator.', false, $this->rows($ownerSamples), 'unassigned');
    }

    /** @param list<array<string,mixed>> $issues */
    private function appendCatalogCompletenessIssues(array &$issues, int $limit): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        $active = DB::table('products')->whereNull('deleted_at')->where('status', 'active');

        $noImage = 0;
        $noImageSamples = collect();
        if (Schema::hasTable('product_images')) {
            $query = (clone $active)->whereNotExists(static function ($nested): void {
                $nested->selectRaw('1')
                    ->from('product_images')
                    ->whereColumn('product_images.product_id', 'products.id')
                    ->whereNull('product_images.product_variant_id');
            });
            $noImage = (int) (clone $query)->count();
            $noImageSamples = (clone $query)->select(['id', 'sku', 'name'])->orderBy('id')->limit($limit)->get();
        }
        $issues[] = $this->issue('active_products_without_image', 'Aktivni artikli bez fotografije', 'info', $noImage, 'Aktivan artikal je objavljen bez glavne galerije.', false, $this->rows($noImageSamples), 'missing_image');

        $zeroPrice = 0;
        $zeroPriceSamples = collect();
        if (Schema::hasColumn('products', 'price_amount')) {
            $query = (clone $active)->where('price_amount', '<=', 0);
            $zeroPrice = (int) (clone $query)->count();
            $zeroPriceSamples = (clone $query)->select(['id', 'sku', 'name', 'price_amount'])->orderBy('id')->limit($limit)->get();
        }
        $issues[] = $this->issue('active_products_without_price', 'Aktivni artikli bez pozitivne cene', 'info', $zeroPrice, 'Proveri da li su besplatni artikli namerni.', false, $this->rows($zeroPriceSamples), 'missing_price');

        $missingModel = 0;
        $missingModelSamples = collect();
        if (Schema::hasColumn('products', 'model_name')) {
            $query = (clone $active)->where(static function ($nested): void {
                $nested->whereNull('model_name')->orWhereRaw("TRIM(model_name) = ''");
            });
            $missingModel = (int) (clone $query)->count();
            $missingModelSamples = (clone $query)->select(['id', 'sku', 'name'])->orderBy('id')->limit($limit)->get();
        }
        $issues[] = $this->issue('active_products_without_model', 'Aktivni artikli bez modela proizvoda', 'info', $missingModel, 'Model poboljšava naziv, pretragu i identifikaciju artikla.', false, $this->rows($missingModelSamples), 'missing_model');

        $incomplete = 0;
        $incompleteSamples = collect();
        if (Schema::hasTable('product_types') && Schema::hasColumn('products', 'completeness_percent') && Schema::hasColumn('product_types', 'minimum_completeness_percent')) {
            $query = DB::table('products')
                ->join('product_types', 'product_types.id', '=', 'products.product_type_id')
                ->whereNull('products.deleted_at')
                ->where('products.status', 'active')
                ->whereColumn('products.completeness_percent', '<', 'product_types.minimum_completeness_percent');
            $incomplete = (int) (clone $query)->count();
            $incompleteSamples = (clone $query)->select([
                'products.id', 'products.sku', 'products.name', 'products.completeness_percent',
                'product_types.minimum_completeness_percent',
            ])->orderBy('products.id')->limit($limit)->get();
        }
        $issues[] = $this->issue('active_products_below_minimum', 'Aktivni artikli ispod minimalne kompletnosti', 'warning', $incomplete, 'Aktivan artikal ne bi smeo da bude ispod minimuma svog tipa.', true, $this->rows($incompleteSamples), 'incomplete');
    }

    /** @param list<array<string,mixed>> $issues */
    private function appendCategoryIssues(array &$issues, int $limit): void
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('product_types') || !Schema::hasTable('product_categories')) {
            return;
        }

        $base = DB::table('products')
            ->join('product_types', 'product_types.id', '=', 'products.product_type_id')
            ->whereNull('products.deleted_at')
            ->whereNotNull('product_types.category_id');

        $query = (clone $base)->where(static function ($nested): void {
            $nested->whereNotExists(static function ($mapped): void {
                $mapped->selectRaw('1')->from('product_categories')
                    ->whereColumn('product_categories.product_id', 'products.id')
                    ->whereColumn('product_categories.category_id', 'product_types.category_id');
            })->orWhereExists(static function ($extra): void {
                $extra->selectRaw('1')->from('product_categories as extra_categories')
                    ->whereColumn('extra_categories.product_id', 'products.id')
                    ->whereColumn('extra_categories.category_id', '!=', 'product_types.category_id');
            });
        });

        $count = (int) (clone $query)->count();
        $samples = (clone $query)->select(['products.id', 'products.sku', 'products.name'])->distinct()->orderBy('products.id')->limit($limit)->get();
        $issues[] = $this->issue('product_category_mismatch', 'Kategorija artikla nije usklađena sa tipom', 'warning', $count, 'Bezbedna popravka ponovo povezuje automatsku kategoriju tipa.', true, $this->rows($samples));
    }

    /** @param list<array<string,mixed>> $issues */
    private function appendImageIssues(array &$issues, int $limit): void
    {
        if (!Schema::hasTable('product_images')) {
            return;
        }

        $groups = DB::table('product_images')
            ->select(['product_id', 'product_variant_id'])
            ->selectRaw('COUNT(*) AS image_count')
            ->selectRaw('SUM(CASE WHEN is_primary = 1 THEN 1 ELSE 0 END) AS primary_count')
            ->groupBy('product_id', 'product_variant_id')
            ->havingRaw('SUM(CASE WHEN is_primary = 1 THEN 1 ELSE 0 END) <> 1');
        $count = (int) DB::query()->fromSub($groups, 'invalid_image_groups')->count();
        $samples = $groups->orderBy('product_id')->limit($limit)->get();
        $issues[] = $this->issue('invalid_primary_image_groups', 'Galerije bez tačno jedne glavne slike', 'warning', $count, 'Bezbedna popravka bira prvu postojeću glavnu sliku ili prvu po redosledu.', true, $this->rows($samples));
    }

    /** @param list<array<string,mixed>> $issues */
    private function appendVariantIssues(array &$issues, int $limit): void
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('product_variants')) {
            return;
        }

        $invalidDefault = DB::table('products')
            ->where('variants_enabled', true)
            ->whereNull('deleted_at')
            ->where(static function ($query): void {
                $query->whereNull('default_variant_id')
                    ->orWhereNotExists(static function ($variant): void {
                        $variant->selectRaw('1')->from('product_variants')
                            ->whereColumn('product_variants.id', 'products.default_variant_id')
                            ->whereColumn('product_variants.product_id', 'products.id')
                            ->whereNull('product_variants.deleted_at');
                    });
            });
        $invalidCount = (int) (clone $invalidDefault)->count();
        $samples = (clone $invalidDefault)->select(['id', 'sku', 'name', 'default_variant_id'])->orderBy('id')->limit($limit)->get();
        $issues[] = $this->issue('invalid_default_variants', 'Proizvodi sa nevažećom podrazumevanom varijantom', 'warning', $invalidCount, 'Bezbedna popravka bira aktivnu ili prvu raspoloživu varijantu.', true, $this->rows($samples));

        $multipleDefaults = DB::table('product_variants')
            ->select('product_id')
            ->selectRaw('COUNT(*) AS aggregate_count')
            ->whereNull('deleted_at')
            ->where('is_default', true)
            ->groupBy('product_id')
            ->havingRaw('COUNT(*) > 1');
        $multipleCount = (int) DB::query()->fromSub($multipleDefaults, 'multiple_defaults')->count();
        $multipleSamples = $multipleDefaults->orderBy('product_id')->limit($limit)->get();
        $issues[] = $this->issue('multiple_default_variants', 'Proizvodi sa više podrazumevanih varijanti', 'warning', $multipleCount, 'Samo jedna varijanta može biti podrazumevana.', true, $this->rows($multipleSamples));
    }

    /** @param list<array<string,mixed>> $issues */
    private function appendSpecificationIssues(array &$issues, int $limit): void
    {
        if (!Schema::hasTable('product_spec_values') || !Schema::hasTable('products') || !Schema::hasTable('specification_fields')) {
            return;
        }

        $query = DB::table('product_spec_values')
            ->join('products', 'products.id', '=', 'product_spec_values.product_id')
            ->leftJoin('specification_fields', 'specification_fields.id', '=', 'product_spec_values.field_id')
            ->leftJoin('product_type_fields', static function ($join): void {
                $join->on('product_type_fields.product_type_id', '=', 'products.product_type_id')
                    ->on('product_type_fields.field_id', '=', 'product_spec_values.field_id');
            })
            ->where(static function ($nested): void {
                $nested->whereNull('specification_fields.id')
                    ->orWhere('specification_fields.status', '!=', 'active')
                    ->orWhereNull('product_type_fields.field_id');
            });
        $count = (int) (clone $query)->count();
        $samples = (clone $query)->select([
            'products.id', 'products.sku', 'products.name', 'product_spec_values.field_id',
        ])->orderBy('products.id')->limit($limit)->get();
        $issues[] = $this->issue('orphan_product_specifications', 'Zastarele ili nepovezane specifikacije artikala', 'critical', $count, 'Vrednost specifikacije mora pripadati aktivnom polju izabranog tipa proizvoda.', true, $this->rows($samples));

        if (Schema::hasTable('product_variant_spec_values') && Schema::hasTable('product_variants')) {
            $variantQuery = DB::table('product_variant_spec_values')
                ->join('product_variants', 'product_variants.id', '=', 'product_variant_spec_values.product_variant_id')
                ->join('products', 'products.id', '=', 'product_variants.product_id')
                ->leftJoin('specification_fields', 'specification_fields.id', '=', 'product_variant_spec_values.field_id')
                ->leftJoin('product_type_fields', static function ($join): void {
                    $join->on('product_type_fields.product_type_id', '=', 'products.product_type_id')
                        ->on('product_type_fields.field_id', '=', 'product_variant_spec_values.field_id');
                })
                ->where(static function ($nested): void {
                    $nested->whereNull('specification_fields.id')
                        ->orWhere('specification_fields.status', '!=', 'active')
                        ->orWhereNull('product_type_fields.field_id');
                });
            $variantCount = (int) (clone $variantQuery)->count();
            $variantSamples = (clone $variantQuery)->select([
                'product_variants.id', 'product_variants.sku', 'product_variants.name', 'product_variant_spec_values.field_id',
            ])->orderBy('product_variants.id')->limit($limit)->get();
            $issues[] = $this->issue('orphan_variant_specifications', 'Zastarele ili nepovezane specifikacije varijanti', 'critical', $variantCount, 'Vrednost varijante mora pripadati aktivnom polju tipa proizvoda.', true, $this->rows($variantSamples));
        }

        $storageCounts = $this->storage->integrityCounts();
        $storageProblems = array_sum($storageCounts);
        $issues[] = $this->issue('storage_specification_mismatches', 'Neusklađeni pojedinačni diskovi i ukupan kapacitet', 'warning', $storageProblems, 'Bezbedna popravka ponovo računa izvedeni ukupan kapacitet.', true, [$storageCounts]);
    }

    /** @param list<array<string,mixed>> $issues */
    private function appendUserIssues(array &$issues, int $limit): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'role_id')) {
            return;
        }

        $query = DB::table('users')->where('status', 'active')->whereNull('role_id');
        $count = (int) (clone $query)->count();
        $samples = (clone $query)->select(['id', 'username', 'email'])->orderBy('id')->limit($limit)->get();
        $issues[] = $this->issue('active_users_without_role', 'Aktivni korisnici bez uloge', 'critical', $count, 'Aktivan korisnik mora imati validnu ulogu pristupa.', false, $this->rows($samples));
    }

    /** @return array<string,int> */
    private function metrics(): array
    {
        $metrics = [
            'products_total' => 0,
            'products_active' => 0,
            'products_archived' => 0,
            'variants_total' => 0,
            'images_total' => 0,
            'users_active' => 0,
        ];

        if (Schema::hasTable('products')) {
            $metrics['products_total'] = (int) DB::table('products')->count();
            $metrics['products_active'] = (int) DB::table('products')->whereNull('deleted_at')->where('status', 'active')->count();
            $metrics['products_archived'] = (int) DB::table('products')->whereNotNull('deleted_at')->count();
        }
        if (Schema::hasTable('product_variants')) {
            $metrics['variants_total'] = (int) DB::table('product_variants')->whereNull('deleted_at')->count();
        }
        if (Schema::hasTable('product_images')) {
            $metrics['images_total'] = (int) DB::table('product_images')->count();
        }
        if (Schema::hasTable('users')) {
            $metrics['users_active'] = (int) DB::table('users')->where('status', 'active')->count();
        }

        return $metrics;
    }

    private function normalizeImagePrimaries(): int
    {
        if (!Schema::hasTable('product_images')) {
            return 0;
        }

        $groups = DB::table('product_images')
            ->select(['product_id', 'product_variant_id'])
            ->selectRaw('SUM(CASE WHEN is_primary = 1 THEN 1 ELSE 0 END) AS primary_count')
            ->groupBy('product_id', 'product_variant_id')
            ->havingRaw('SUM(CASE WHEN is_primary = 1 THEN 1 ELSE 0 END) <> 1')
            ->get();
        $fixed = 0;

        foreach ($groups as $group) {
            DB::transaction(function () use ($group, &$fixed): void {
                $base = DB::table('product_images')
                    ->where('product_id', (int) $group->product_id)
                    ->where(function ($query) use ($group): void {
                        $group->product_variant_id === null
                            ? $query->whereNull('product_variant_id')
                            : $query->where('product_variant_id', (int) $group->product_variant_id);
                    });
                $chosenId = (clone $base)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id')->value('id');
                if ($chosenId === null) {
                    return;
                }
                (clone $base)->update(['is_primary' => false]);
                DB::table('product_images')->where('id', (int) $chosenId)->update(['is_primary' => true]);
                $fixed++;
            }, 3);
        }

        return $fixed;
    }

    private function normalizeDefaultVariants(): int
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('product_variants') || !Schema::hasColumn('products', 'default_variant_id')) {
            return 0;
        }

        $fixed = 0;
        Product::query()
            ->where('variants_enabled', true)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunkById(100, function ($products) use (&$fixed): void {
                foreach ($products as $product) {
                    DB::transaction(function () use ($product, &$fixed): void {
                        $variants = DB::table('product_variants')
                            ->where('product_id', (int) $product->id)
                            ->whereNull('deleted_at')
                            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
                            ->orderByDesc('is_default')
                            ->orderBy('sort_order')
                            ->orderBy('id')
                            ->get(['id', 'is_default']);
                        if ($variants->isEmpty()) {
                            if ($product->default_variant_id !== null) {
                                DB::table('products')->where('id', (int) $product->id)->update(['default_variant_id' => null]);
                                $fixed++;
                            }
                            return;
                        }
                        $chosen = $variants->firstWhere('id', (int) $product->default_variant_id) ?? $variants->first();
                        $defaultCount = $variants->where('is_default', true)->count();
                        if ((int) $product->default_variant_id === (int) $chosen->id && $defaultCount === 1 && (bool) $chosen->is_default) {
                            return;
                        }
                        DB::table('product_variants')->where('product_id', (int) $product->id)->update(['is_default' => false]);
                        DB::table('product_variants')->where('id', (int) $chosen->id)->update(['is_default' => true]);
                        DB::table('products')->where('id', (int) $product->id)->update(['default_variant_id' => (int) $chosen->id]);
                        $fixed++;
                    }, 3);
                }
            });

        return $fixed;
    }

    /** @param array<int,object|array<string,mixed>>|Collection<int,object> $rows @return list<array<string,mixed>> */
    private function rows(array|Collection $rows): array
    {
        return collect($rows)->map(static fn ($row): array => (array) $row)->values()->all();
    }

    /** @param list<array<string,mixed>> $samples @return array<string,mixed> */
    private function issue(
        string $key,
        string $label,
        string $severity,
        int $count,
        string $description,
        bool $repairable,
        array $samples,
        ?string $catalogFilter = null,
    ): array {
        return [
            'key' => $key,
            'label' => $label,
            'severity' => in_array($severity, ['critical', 'warning', 'info'], true) ? $severity : 'info',
            'count' => max(0, $count),
            'description' => $description,
            'repairable' => $repairable,
            'samples' => $samples,
            'catalog_filter' => $catalogFilter,
        ];
    }
}
