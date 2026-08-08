<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductType;
use App\Models\SpecificationField;
use App\Services\AuditLogger;
use App\Services\CatalogDictionary;
use App\Services\CatalogReferenceCache;
use App\Services\ProductCompletenessService;
use App\Services\ProductTypeCategoryService;
use App\Services\SpecificationDependencyService;
use App\Services\SpecificationFieldLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class CatalogDictionaryController extends Controller
{
    public function index(string $resource, CatalogDictionary $dictionary, SpecificationDependencyService $dependencies): View
    {
        $definition = $dictionary->definition($resource);
        $class = $definition['model'];
        $query = $class::query();

        if ($resource === 'product-lines') {
            $query->with('brand');
        }
        if ($resource === 'product-types') {
            $query->with(['category', 'fields'])->withCount('products');
        }
        if ($resource === 'specification-fields') {
            $query->with(['parentField', 'options.parentOptions', 'productTypes']);
        }

        $items = $query->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.dictionary.index', [
            'resource' => $resource,
            'definition' => $definition,
            'definitions' => $dictionary->definitions(),
            'items' => $items,
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'fields' => SpecificationField::query()->orderBy('sort_order')->orderBy('name')->get(),
            'selectableFields' => SpecificationField::query()->where('data_type', 'select')->where('status', 'active')->orderBy('sort_order')->orderBy('name')->get(),
            'dependencyMaps' => $resource === 'specification-fields'
                ? $items->mapWithKeys(fn (SpecificationField $field): array => [$field->id => $dependencies->mappingText($field)])->all()
                : [],
            'usageCounts' => $resource === 'specification-fields' ? $this->specificationUsageCounts($items->pluck('id')->all()) : [],
        ]);
    }

    public function productType(ProductType $productType, CatalogDictionary $dictionary): View
    {
        $productType->load([
            'category',
            'fields.options' => fn ($query) => $query->orderBy('sort_order')->orderBy('label'),
        ])->loadCount('products');

        $fields = SpecificationField::query()
            ->with(['parentField', 'options'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $assignedFields = $productType->fields->keyBy('id');
        $orderedFields = $fields->sortBy(static function (SpecificationField $field) use ($assignedFields): array {
            $assigned = $assignedFields->get($field->id);

            return [
                $assigned === null ? 1 : 0,
                (int) ($assigned?->pivot?->sort_order ?? $field->sort_order ?? 100000),
                mb_strtolower((string) $field->name),
            ];
        })->values();

        return view('admin.dictionary.product-type', [
            'resource' => 'product-types',
            'definition' => $dictionary->definition('product-types'),
            'definitions' => $dictionary->definitions(),
            'item' => $productType,
            'categories' => Category::query()->where('status', 'active')->orderBy('name')->get(),
            'brands' => Brand::query()->where('status', 'active')->orderBy('name')->get(),
            'fields' => $fields,
            'orderedFields' => $orderedFields,
            'selectableFields' => collect(),
            'dependencyMaps' => [],
        ]);
    }

    public function store(
        Request $request,
        string $resource,
        CatalogDictionary $dictionary,
        AuditLogger $audit,
        SpecificationDependencyService $dependencies,
        ProductCompletenessService $completeness,
        ProductTypeCategoryService $typeCategories,
    ): RedirectResponse {
        $data = $this->validated($request, $resource);
        $model = DB::transaction(function () use ($resource, $dictionary, $data, $request, $dependencies) {
            $model = $dictionary->model($resource);
            $model->fill($this->modelData($resource, $data));
            if ($model->isFillable('created_by')) $model->created_by = $request->user()->id;
            if ($model->isFillable('updated_by')) $model->updated_by = $request->user()->id;
            $model->save();
            if ($resource === 'product-types') $this->syncTypeFields($model, $data);
            if ($resource === 'specification-fields') {
                $dependencies->sync(
                    $model,
                    $data['options_text'] ?? null,
                    isset($data['parent_field_id']) ? (int) $data['parent_field_id'] : null,
                    $data['dependency_map_text'] ?? null,
                );
            }
            return $model->fresh();
        });

        $categoryResult = null;
        if ($resource === 'product-types' && $model instanceof ProductType) {
            $categoryResult = $typeCategories->ensureForType($model, (int) $request->user()->id);
            $model = $model->fresh(['category']) ?? $model;
            $completeness->recalculateType($model);
        }
        $audit->log('catalog.dictionary.created', 'Kreirano: '.$model->name, $model, after: $model->toArray(), metadata: [
            'resource' => $resource,
            'automatic_category' => $categoryResult !== null ? [
                'id' => (int) $categoryResult['category']->id,
                'action' => $categoryResult['action'],
                'products_synced' => $categoryResult['products_synced'],
            ] : null,
        ]);

        $this->forgetCatalogReferenceCache();

        if ($resource === 'product-types' && $model instanceof ProductType) {
            return redirect()->route('admin.dictionary.product-type', $model)->with('status', 'Tip artikla je kreiran. Sada podesi njegove specifikacije.');
        }

        return back()->with('status', 'Stavka je kreirana.');
    }

    public function update(
        Request $request,
        string $resource,
        int $item,
        CatalogDictionary $dictionary,
        AuditLogger $audit,
        SpecificationDependencyService $dependencies,
        ProductCompletenessService $completeness,
        ProductTypeCategoryService $typeCategories,
    ): RedirectResponse {
        $model = $dictionary->model($resource, $item);
        $before = $model->toArray();
        $data = $this->validated($request, $resource, $item);

        DB::transaction(function () use ($resource, $model, $data, $request, $dependencies): void {
            $model->fill($this->modelData($resource, $data));
            if ($model->isFillable('updated_by')) $model->updated_by = $request->user()->id;
            $model->save();
            if ($resource === 'product-types') $this->syncTypeFields($model, $data);
            if ($resource === 'specification-fields') {
                $dependencies->sync(
                    $model,
                    $data['options_text'] ?? null,
                    isset($data['parent_field_id']) ? (int) $data['parent_field_id'] : null,
                    $data['dependency_map_text'] ?? null,
                );
            }
        });

        $categoryResult = null;
        $categorySynced = 0;
        if ($resource === 'product-types' && $model instanceof ProductType) {
            $categoryResult = $typeCategories->ensureForType($model, (int) $request->user()->id);
            $categorySynced = (int) $categoryResult['products_synced'];
            $model = $model->fresh(['category']) ?? $model;
        }
        $recalculated = $resource === 'product-types' ? $completeness->recalculateType($model) : ['examined' => 0, 'downgraded' => 0];
        $audit->log('catalog.dictionary.updated', 'Izmenjeno: '.$model->name, $model, $before, $model->fresh()->toArray(), [
            'resource' => $resource,
            'recalculated_products' => $recalculated,
            'category_synced_products' => $categorySynced,
            'automatic_category' => $categoryResult !== null ? [
                'id' => (int) $categoryResult['category']->id,
                'action' => $categoryResult['action'],
            ] : null,
        ]);
        $message = 'Izmene su sačuvane.';
        if ($resource === 'product-types' && $recalculated['examined'] > 0) {
            $message .= ' Ponovo je obračunata kompletnost za '.$recalculated['examined'].' artikala';
            if ($recalculated['downgraded'] > 0) $message .= ', a '.$recalculated['downgraded'].' je vraćeno u nacrt';
            $message .= '.';
        }

        if ($categorySynced > 0) {
            $message .= ' Automatska kategorija je usklađena na '.$categorySynced.' artikala.';
        }
        $this->forgetCatalogReferenceCache();
        return back()->with('status', $message);
    }

    public function destroy(string $resource, int $item, CatalogDictionary $dictionary, AuditLogger $audit): RedirectResponse
    {
        $model = $dictionary->model($resource, $item);
        $before = $model->toArray();
        $model->update(['status' => 'inactive']);
        $audit->log('catalog.dictionary.deactivated', 'Deaktivirano: '.$model->name, $model, $before, $model->fresh()->toArray(), ['resource' => $resource]);
        $this->forgetCatalogReferenceCache();
        return back()->with('status', 'Stavka je deaktivirana, nije fizički obrisana.');
    }

    public function purge(Request $request, string $resource, int $item, CatalogDictionary $dictionary, AuditLogger $audit, SpecificationFieldLifecycleService $lifecycle): RedirectResponse
    {
        abort_unless($resource === 'specification-fields', 404);
        $model = $dictionary->model($resource, $item);
        abort_unless($model instanceof SpecificationField, 404);

        $data = $request->validate([
            'confirm_name' => ['required', 'string', 'max:120'],
        ]);
        if (!hash_equals(trim((string) $model->name), trim((string) $data['confirm_name']))) {
            return back()->withErrors(['confirm_name' => 'Naziv za potvrdu se ne podudara sa specifikacionim poljem „'.$model->name.'“.']);
        }

        $usage = $this->specificationUsageCounts([(int) $model->id])[(int) $model->id] ?? [];
        $before = $model->toArray();
        $cleanup = $lifecycle->purge($model);

        $audit->log(
            'catalog.specification_field.deleted',
            'Trajno obrisano specifikaciono polje: '.$before['name'],
            null,
            $before,
            null,
            [
                'resource' => $resource,
                'deleted_id' => $item,
                'usage_before_delete' => $usage,
                'cleanup' => $cleanup,
            ],
            level: 'warning',
        );

        $message = 'Specifikaciono polje „'.$before['name'].'“ je bezbedno obrisano zajedno sa povezanim vrednostima.';
        if ($cleanup['recalculated'] > 0) {
            $message .= ' Ponovo je obračunato '.$cleanup['recalculated'].' artikala';
            if ($cleanup['downgraded'] > 0) $message .= ', a '.$cleanup['downgraded'].' je vraćeno u nacrt';
            $message .= '.';
        }

        $this->forgetCatalogReferenceCache();
        return back()->with('status', $message);
    }

    public function reorder(Request $request, string $resource, CatalogDictionary $dictionary, AuditLogger $audit): JsonResponse
    {
        $dictionary->definition($resource);
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $ids = array_values(array_map('intval', $data['ids']));
        $modelClass = $dictionary->definition($resource)['model'];
        $existing = $modelClass::query()->whereIn('id', $ids)->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        if (count($existing) !== count($ids)) {
            abort(422, 'Jedna ili više stavki više ne postoji. Osveži stranicu i pokušaj ponovo.');
        }

        DB::transaction(static function () use ($modelClass, $ids): void {
            foreach ($ids as $index => $id) {
                $modelClass::query()->whereKey($id)->update(['sort_order' => ($index + 1) * 10]);
            }
        });

        $audit->log('catalog.dictionary.reordered', 'Promenjen raspored: '.$dictionary->definition($resource)['label'], metadata: ['resource' => $resource, 'ids' => $ids]);
        $this->forgetCatalogReferenceCache();
        return response()->json(['message' => 'Novi raspored je sačuvan.', 'ids' => $ids]);
    }

    public function reorderTypeFields(Request $request, ProductType $productType, AuditLogger $audit): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:specification_fields,id'],
        ]);
        $ids = array_values(array_map('intval', $data['ids']));
        $assigned = $productType->fields()->pluck('specification_fields.id')->map(static fn ($id): int => (int) $id)->all();
        $assignedLookup = array_fill_keys($assigned, true);

        DB::transaction(static function () use ($productType, $ids, $assignedLookup): void {
            $position = 10;
            foreach ($ids as $fieldId) {
                if (!isset($assignedLookup[$fieldId])) continue;
                DB::table('product_type_fields')
                    ->where('product_type_id', $productType->id)
                    ->where('field_id', $fieldId)
                    ->update(['sort_order' => $position]);
                $position += 10;
            }
        });

        $audit->log('catalog.product_type.fields_reordered', 'Promenjen raspored specifikacija za tip '.$productType->name, $productType, metadata: ['field_ids' => $ids]);
        $this->forgetCatalogReferenceCache();
        return response()->json(['message' => 'Raspored specifikacija je sačuvan.', 'ids' => $ids]);
    }

    private function forgetCatalogReferenceCache(): void
    {
        app(CatalogReferenceCache::class)->forget();
    }

    /** @return array<string,mixed> */
    private function validated(Request $request, string $resource, ?int $ignoreId = null): array
    {
        $table = match ($resource) {
            'categories' => 'categories', 'brands' => 'brands', 'product-lines' => 'product_lines',
            'product-types' => 'product_types', 'specification-fields' => 'specification_fields',
            default => abort(404),
        };
        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140', Rule::unique($table, 'slug')->ignore($ignoreId)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
        ];
        if ($resource === 'categories') $rules += ['parent_id' => ['nullable', 'integer', 'exists:categories,id', Rule::notIn(array_filter([$ignoreId]))], 'description' => ['nullable', 'string', 'max:65000']];
        if ($resource === 'brands') $rules += ['description' => ['nullable', 'string', 'max:65000'], 'website_url' => ['nullable', 'url', 'max:255']];
        if ($resource === 'product-lines') $rules += ['brand_id' => ['required', 'integer', 'exists:brands,id']];
        if ($resource === 'product-types') $rules += [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:65000'],
            'name_template' => ['nullable', 'string', 'max:500'],
            'auto_name_enabled' => ['nullable', 'boolean'],
            'minimum_completeness_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'default_product_status' => ['nullable', Rule::in(['draft','active','inactive'])],
            'required_core_fields' => ['array'],
            'required_core_fields.*' => [Rule::in(['brand','line','model','categories','description','price'])],
            'field_config' => ['array'],
            'field_config.*.enabled' => ['nullable', 'boolean'],
            'field_config.*.is_required' => ['nullable', 'boolean'],
            'field_config.*.is_filterable' => ['nullable', 'boolean'],
            'field_config.*.show_in_summary' => ['nullable', 'boolean'],
            'field_config.*.include_in_name' => ['nullable', 'boolean'],
            'field_config.*.sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'field_config.*.completeness_weight' => ['nullable', 'integer', 'min:1', 'max:100'],
            'field_config.*.default_value' => ['nullable', 'string', 'max:1000'],
            'field_config.*.default_detail' => ['nullable', 'string', 'max:500'],
        ];
        if ($resource === 'specification-fields') $rules += [
            'data_type' => ['required', Rule::in(['text','integer','decimal','select','boolean'])],
            'filter_type' => ['required', Rule::in(['none','select','range','boolean','text'])],
            'unit' => ['nullable','string','max:30'],
            'placeholder' => ['nullable','string','max:160'],
            'help_text' => ['nullable','string','max:500'],
            'options_text' => ['nullable','string','max:65000'],
            'min_value' => ['nullable','numeric'],
            'max_value' => ['nullable','numeric'],
            'parent_field_id' => ['nullable','integer','exists:specification_fields,id', Rule::notIn(array_filter([$ignoreId]))],
            'dependency_map_text' => ['nullable','string','max:65000'],
            'detail_input_enabled' => ['nullable','boolean'],
            'detail_label' => ['nullable','string','max:120'],
            'detail_placeholder' => ['nullable','string','max:190'],
        ];
        return $request->validate($rules);
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function modelData(string $resource, array $data): array
    {
        $data['slug'] = Str::slug((string) ($data['slug'] ?: $data['name']));
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        if ($resource === 'product-types') {
            if (!empty($data['category_id'])) $data['category_id'] = (int) $data['category_id'];
            else unset($data['category_id']);
            $data['auto_name_enabled'] = !empty($data['auto_name_enabled']);
            $data['minimum_completeness_percent'] = max(0, min(100, (int) ($data['minimum_completeness_percent'] ?? 0)));
            $data['default_product_status'] = in_array(($data['default_product_status'] ?? 'draft'), ['draft','active','inactive'], true) ? $data['default_product_status'] : 'draft';
            $data['required_core_fields_json'] = array_values(array_unique((array) ($data['required_core_fields'] ?? [])));
        }
        if ($resource === 'specification-fields') {
            $data['parent_field_id'] = !empty($data['parent_field_id']) ? (int) $data['parent_field_id'] : null;
            $data['detail_input_enabled'] = !empty($data['detail_input_enabled']);
            if (($data['data_type'] ?? null) !== 'select') {
                $data['parent_field_id'] = null;
                $data['detail_input_enabled'] = false;
                $data['detail_label'] = null;
                $data['detail_placeholder'] = null;
            }
        }
        unset($data['field_ids'], $data['field_config'], $data['required_core_fields'], $data['dependency_map_text']);
        return $data;
    }

    private function matchingCategoryId(string $name, string $slug): ?int
    {
        $normalizedSlug = Str::slug($slug !== '' ? $slug : $name);
        $id = $normalizedSlug !== '' ? Category::query()->where('slug', $normalizedSlug)->value('id') : null;
        if ($id === null) {
            $id = Category::query()->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))])->value('id');
        }
        return $id !== null ? (int) $id : null;
    }

    /** @param array<string,mixed> $data */
    private function syncTypeFields(ProductType $type, array $data): void
    {
        $config = (array) ($data['field_config'] ?? []);
        $requestedIds = [];
        foreach ($config as $fieldId => $row) {
            if (!empty($row['enabled'])) $requestedIds[] = (int) $fieldId;
        }
        $requestedIds = array_values(array_unique(array_filter($requestedIds)));
        $fields = SpecificationField::query()->whereIn('id', $requestedIds)->get(['id', 'parent_field_id'])->keyBy('id');
        $orderedIds = [];
        $appendWithParents = function (int $fieldId) use (&$appendWithParents, &$orderedIds, $fields): void {
            $field = $fields->get($fieldId) ?? SpecificationField::query()->find($fieldId, ['id', 'parent_field_id']);
            if ($field === null) return;
            if ($field->parent_field_id !== null && !in_array((int) $field->parent_field_id, $orderedIds, true)) $appendWithParents((int) $field->parent_field_id);
            if (!in_array((int) $field->id, $orderedIds, true)) $orderedIds[] = (int) $field->id;
        };
        foreach ($requestedIds as $fieldId) $appendWithParents($fieldId);

        usort($orderedIds, static function (int $a, int $b) use ($config): int {
            $sortA = (int) ($config[$a]['sort_order'] ?? 100000);
            $sortB = (int) ($config[$b]['sort_order'] ?? 100000);
            return $sortA <=> $sortB ?: $a <=> $b;
        });

        $sync = [];
        foreach ($orderedIds as $index => $fieldId) {
            $row = (array) ($config[$fieldId] ?? []);
            $sync[$fieldId] = [
                'is_required' => !empty($row['is_required']),
                'is_filterable' => !empty($row['is_filterable']),
                'show_in_summary' => !empty($row['show_in_summary']),
                'include_in_name' => !empty($row['include_in_name']),
                'sort_order' => (int) ($row['sort_order'] ?? (($index + 1) * 10)),
                'completeness_weight' => max(1, min(100, (int) ($row['completeness_weight'] ?? 1))),
                'default_value' => trim((string) ($row['default_value'] ?? '')) ?: null,
                'default_detail' => trim((string) ($row['default_detail'] ?? '')) ?: null,
                'created_at' => now(),
            ];
        }
        $type->fields()->sync($sync);
    }

    private function syncTypeProductCategories(ProductType $type): int
    {
        if (!Schema::hasTable('products') || !Schema::hasTable('product_categories')) return 0;
        $count = 0;
        DB::table('products')
            ->where('product_type_id', $type->id)
            ->orderBy('id')
            ->select('id')
            ->chunkById(250, function ($products) use ($type, &$count): void {
                $ids = $products->pluck('id')->map(static fn ($id): int => (int) $id)->all();
                if ($ids === []) return;
                DB::table('product_categories')->whereIn('product_id', $ids)->delete();
                if ($type->category_id !== null) {
                    DB::table('product_categories')->insertOrIgnore(array_map(static fn (int $productId): array => [
                        'product_id' => $productId,
                        'category_id' => (int) $type->category_id,
                    ], $ids));
                }
                $count += count($ids);
            });
        return $count;
    }

    /** @param array<int,int|string> $fieldIds @return array<int,array<string,int>> */
    private function specificationUsageCounts(array $fieldIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $fieldIds)));
        if ($ids === []) return [];

        $counts = [];
        foreach ($ids as $id) {
            $counts[$id] = ['types' => 0, 'products' => 0, 'variants' => 0, 'children' => 0];
        }

        $sources = [
            'types' => ['product_type_fields', 'field_id'],
            'products' => ['product_spec_values', 'field_id'],
            'variants' => ['product_variant_spec_values', 'field_id'],
            'children' => ['specification_fields', 'parent_field_id'],
        ];
        foreach ($sources as $key => [$table, $column]) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) continue;
            DB::table($table)
                ->select($column, DB::raw('COUNT(*) as aggregate_count'))
                ->whereIn($column, $ids)
                ->groupBy($column)
                ->get()
                ->each(function ($row) use (&$counts, $key, $column): void {
                    $id = (int) $row->{$column};
                    if (isset($counts[$id])) $counts[$id][$key] = (int) $row->aggregate_count;
                });
        }

        return $counts;
    }
}
