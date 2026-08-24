<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\SpecificationField;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

// MOBILE_V1_0_ADMIN_CATALOG_DICTIONARIES_BATCH22
final class CatalogDictionaryManagerService
{
    /** @var list<string> */
    public const MOBILE_RESOURCES = ['categories', 'product-lines', 'product-types', 'specification-fields'];

    public function __construct(
        private readonly CatalogDictionary $dictionary,
        private readonly AuditLogger $audit,
        private readonly SpecificationDependencyService $dependencies,
        private readonly ProductCompletenessService $completeness,
        private readonly ProductTypeCategoryService $typeCategories,
        private readonly SpecificationFieldLifecycleService $lifecycle,
        private readonly CatalogReferenceCache $referenceCache,
    ) {}

    /** @return array<string,mixed> */
    public function webIndexContext(string $resource): array
    {
        $definition = $this->dictionary->definition($resource);
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

        return [
            'definition' => $definition,
            'definitions' => $this->dictionary->definitions(),
            'items' => $items,
            'categories' => Category::query()->orderBy('name')->get(),
            'brands' => Brand::query()->orderBy('name')->get(),
            'fields' => SpecificationField::query()->orderBy('sort_order')->orderBy('name')->get(),
            'selectableFields' => SpecificationField::query()
                ->where('data_type', 'select')
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'dependencyMaps' => $resource === 'specification-fields'
                ? $items->mapWithKeys(fn (SpecificationField $field): array => [$field->id => $this->dependencies->mappingText($field)])->all()
                : [],
            'usageCounts' => $resource === 'specification-fields'
                ? $this->specificationUsageCounts($items->pluck('id')->all())
                : [],
        ];
    }

    /** @return array<string,mixed> */
    public function webProductTypeContext(ProductType $productType): array
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

        return [
            'definition' => $this->dictionary->definition('product-types'),
            'definitions' => $this->dictionary->definitions(),
            'item' => $productType,
            'categories' => Category::query()->where('status', 'active')->orderBy('name')->get(),
            'brands' => Brand::query()->where('status', 'active')->orderBy('name')->get(),
            'fields' => $fields,
            'orderedFields' => $orderedFields,
            'selectableFields' => collect(),
            'dependencyMaps' => [],
        ];
    }

    /** @return array<string,mixed> */
    public function mobileIndexData(string $resource): array
    {
        $this->assertMobileResource($resource);
        $context = $this->webIndexContext($resource);
        $dependencyMaps = (array) $context['dependencyMaps'];
        $usageCounts = (array) $context['usageCounts'];

        $items = $context['items']->map(function (Model $model) use ($resource, $dependencyMaps, $usageCounts): array {
            return $this->itemPayload($resource, $model, $dependencyMaps, $usageCounts);
        })->values()->all();

        return [
            'resource' => $resource,
            'label' => (string) $context['definition']['label'],
            'items' => $items,
            'references' => [
                'categories' => $context['categories']->map(static fn (Category $category): array => [
                    'id' => (int) $category->id,
                    'name' => (string) $category->name,
                    'status' => (string) $category->status,
                    'parent_id' => $category->parent_id !== null ? (int) $category->parent_id : null,
                ])->values()->all(),
                'brands' => $context['brands']->map(static fn (Brand $brand): array => [
                    'id' => (int) $brand->id,
                    'name' => (string) $brand->name,
                    'status' => (string) $brand->status,
                ])->values()->all(),
                'selectable_fields' => $context['selectableFields']->map(static fn (SpecificationField $field): array => [
                    'id' => (int) $field->id,
                    'name' => (string) $field->name,
                    'slug' => (string) $field->slug,
                    'data_type' => (string) $field->data_type,
                ])->values()->all(),
            ],
            'options' => [
                'statuses' => [
                    ['value' => 'active', 'label' => 'Aktivno'],
                    ['value' => 'inactive', 'label' => 'Neaktivno'],
                ],
                'data_types' => ['text', 'integer', 'decimal', 'select', 'boolean'],
                'filter_types' => ['none', 'select', 'range', 'boolean', 'text'],
                'default_product_statuses' => ['draft', 'active', 'inactive'],
            ],
            'capabilities' => [
                'create' => true,
                'update' => true,
                'deactivate' => true,
                'reorder' => true,
                'purge' => $resource === 'specification-fields',
                'product_type_detail' => $resource === 'product-types',
            ],
        ];
    }

    /** @return array<string,mixed> */
    public function mobileProductTypeData(int $productTypeId): array
    {
        $productType = ProductType::query()->findOrFail($productTypeId);
        $context = $this->webProductTypeContext($productType);
        /** @var ProductType $item */
        $item = $context['item'];
        $assignedFields = $item->fields->keyBy('id');

        $fields = $context['orderedFields']->map(static function (SpecificationField $field) use ($assignedFields): array {
            $assigned = $assignedFields->get($field->id);
            $pivot = $assigned?->pivot;

            return [
                'id' => (int) $field->id,
                'name' => (string) $field->name,
                'slug' => (string) $field->slug,
                'data_type' => (string) $field->data_type,
                'unit' => $field->unit !== null ? (string) $field->unit : null,
                'status' => (string) $field->status,
                'detail_input_enabled' => (bool) $field->detail_input_enabled,
                'enabled' => $assigned !== null,
                'is_required' => (bool) ($pivot?->is_required ?? false),
                'is_filterable' => (bool) ($pivot?->is_filterable ?? false),
                'show_in_summary' => (bool) ($pivot?->show_in_summary ?? false),
                'include_in_name' => (bool) ($pivot?->include_in_name ?? false),
                'sort_order' => (int) ($pivot?->sort_order ?? $field->sort_order ?? 0),
                'completeness_weight' => (int) ($pivot?->completeness_weight ?? 1),
                'default_value' => $pivot?->default_value !== null ? (string) $pivot->default_value : null,
                'default_detail' => $pivot?->default_detail !== null ? (string) $pivot->default_detail : null,
            ];
        })->values()->all();

        return [
            'product_type' => [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'slug' => (string) $item->slug,
                'description' => $item->description !== null ? (string) $item->description : null,
                'status' => (string) $item->status,
                'sort_order' => (int) $item->sort_order,
                'category' => $item->category !== null ? [
                    'id' => (int) $item->category->id,
                    'name' => (string) $item->category->name,
                ] : null,
                'name_template' => $item->name_template !== null ? (string) $item->name_template : null,
                'auto_name_enabled' => (bool) $item->auto_name_enabled,
                'minimum_completeness_percent' => (int) $item->minimum_completeness_percent,
                'default_product_status' => (string) ($item->default_product_status ?? 'draft'),
                'required_core_fields' => array_values((array) ($item->required_core_fields_json ?? [])),
                'products_count' => (int) ($item->products_count ?? 0),
            ],
            'fields' => $fields,
            'required_core_options' => [
                ['value' => 'brand', 'label' => 'Brend'],
                ['value' => 'line', 'label' => 'Linija proizvoda'],
                ['value' => 'model', 'label' => 'Model proizvoda'],
                ['value' => 'description', 'label' => 'Opis'],
                ['value' => 'price', 'label' => 'Cena'],
            ],
            'capabilities' => [
                'update' => true,
                'reorder_fields' => true,
            ],
        ];
    }

    /** @param array<string,mixed> $data @return array{model:Model,message:string} */
    public function create(string $resource, array $data, User $actor, string $source = 'web'): array
    {
        $model = DB::transaction(function () use ($resource, $data, $actor): Model {
            $model = $this->dictionary->model($resource);
            $model->fill($this->modelData($resource, $data));
            if ($model->isFillable('created_by')) $model->setAttribute('created_by', (int) $actor->id);
            if ($model->isFillable('updated_by')) $model->setAttribute('updated_by', (int) $actor->id);
            $model->save();
            if ($resource === 'product-types' && $model instanceof ProductType) $this->syncTypeFields($model, $data);
            if ($resource === 'specification-fields' && $model instanceof SpecificationField) {
                $this->dependencies->sync(
                    $model,
                    $data['options_text'] ?? null,
                    isset($data['parent_field_id']) ? (int) $data['parent_field_id'] : null,
                    $data['dependency_map_text'] ?? null,
                );
            }
            return $model->fresh() ?? $model;
        }, 3);

        $categoryResult = null;
        if ($resource === 'product-types' && $model instanceof ProductType) {
            $categoryResult = $this->typeCategories->ensureForType($model, (int) $actor->id);
            $model = $model->fresh(['category']) ?? $model;
            $this->completeness->recalculateType($model);
        }

        $this->audit->log(
            'catalog.dictionary.created',
            'Kreirano: '.$model->getAttribute('name'),
            $model,
            after: $model->toArray(),
            metadata: [
                'resource' => $resource,
                'source' => $source,
                'automatic_category' => $categoryResult !== null ? [
                    'id' => (int) $categoryResult['category']->id,
                    'action' => $categoryResult['action'],
                    'products_synced' => $categoryResult['products_synced'],
                ] : null,
            ],
            user: $actor,
        );
        $this->forgetReferenceCache();

        return [
            'model' => $model,
            'message' => $resource === 'product-types'
                ? 'Tip artikla je kreiran. Sada podesi njegove specifikacije.'
                : 'Stavka je kreirana.',
        ];
    }

    /** @param array<string,mixed> $data @return array{model:Model,message:string} */
    public function update(string $resource, int $itemId, array $data, User $actor, string $source = 'web'): array
    {
        $model = $this->dictionary->model($resource, $itemId);
        $before = $model->toArray();

        DB::transaction(function () use ($resource, $model, $data, $actor): void {
            $model->fill($this->modelData($resource, $data));
            if ($model->isFillable('updated_by')) $model->setAttribute('updated_by', (int) $actor->id);
            $model->save();
            if ($resource === 'product-types' && $model instanceof ProductType) $this->syncTypeFields($model, $data);
            if ($resource === 'specification-fields' && $model instanceof SpecificationField) {
                $this->dependencies->sync(
                    $model,
                    $data['options_text'] ?? null,
                    isset($data['parent_field_id']) ? (int) $data['parent_field_id'] : null,
                    $data['dependency_map_text'] ?? null,
                );
            }
        }, 3);

        $categoryResult = null;
        $categorySynced = 0;
        if ($resource === 'product-types' && $model instanceof ProductType) {
            $categoryResult = $this->typeCategories->ensureForType($model, (int) $actor->id);
            $categorySynced = (int) $categoryResult['products_synced'];
            $model = $model->fresh(['category']) ?? $model;
        }

        $recalculated = $resource === 'product-types' && $model instanceof ProductType
            ? $this->completeness->recalculateType($model)
            : ['examined' => 0, 'downgraded' => 0];

        $fresh = $model->fresh() ?? $model;
        $this->audit->log(
            'catalog.dictionary.updated',
            'Izmenjeno: '.$fresh->getAttribute('name'),
            $fresh,
            before: $before,
            after: $fresh->toArray(),
            metadata: [
                'resource' => $resource,
                'source' => $source,
                'recalculated_products' => $recalculated,
                'category_synced_products' => $categorySynced,
                'automatic_category' => $categoryResult !== null ? [
                    'id' => (int) $categoryResult['category']->id,
                    'action' => $categoryResult['action'],
                ] : null,
            ],
            user: $actor,
        );

        $message = 'Izmene su sačuvane.';
        if ($resource === 'product-types' && $recalculated['examined'] > 0) {
            $message .= ' Ponovo je obračunata kompletnost za '.$recalculated['examined'].' artikala';
            if ($recalculated['downgraded'] > 0) $message .= ', a '.$recalculated['downgraded'].' je vraćeno u nacrt';
            $message .= '.';
        }
        if ($categorySynced > 0) {
            $message .= ' Automatska kategorija je usklađena na '.$categorySynced.' artikala.';
        }

        $this->forgetReferenceCache();
        return ['model' => $fresh, 'message' => $message];
    }

    public function deactivate(string $resource, int $itemId, User $actor, string $source = 'web'): Model
    {
        $model = $this->dictionary->model($resource, $itemId);
        $before = $model->toArray();
        $model->update(['status' => 'inactive']);
        $fresh = $model->fresh() ?? $model;
        $this->audit->log(
            'catalog.dictionary.deactivated',
            'Deaktivirano: '.$fresh->getAttribute('name'),
            $fresh,
            before: $before,
            after: $fresh->toArray(),
            metadata: ['resource' => $resource, 'source' => $source],
            user: $actor,
        );
        $this->forgetReferenceCache();
        return $fresh;
    }

    /** @return array<string,int> */
    public function purgeSpecificationField(int $itemId, string $confirmName, User $actor, string $source = 'web'): array
    {
        $model = $this->dictionary->model('specification-fields', $itemId);
        if (!$model instanceof SpecificationField) abort(404);
        if (!hash_equals(trim((string) $model->name), trim($confirmName))) {
            throw ValidationException::withMessages([
                'confirm_name' => 'Naziv za potvrdu se ne podudara sa specifikacionim poljem „'.$model->name.'“.',
            ]);
        }

        $usage = $this->specificationUsageCounts([(int) $model->id])[(int) $model->id] ?? [];
        $before = $model->toArray();
        $cleanup = $this->lifecycle->purge($model);

        $this->audit->log(
            'catalog.specification_field.deleted',
            'Trajno obrisano specifikaciono polje: '.$before['name'],
            null,
            before: $before,
            after: null,
            metadata: [
                'resource' => 'specification-fields',
                'source' => $source,
                'deleted_id' => $itemId,
                'usage_before_delete' => $usage,
                'cleanup' => $cleanup,
            ],
            level: 'warning',
            user: $actor,
        );
        $this->forgetReferenceCache();
        return $cleanup;
    }

    /** @param list<int> $ids @return list<int> */
    public function reorder(string $resource, array $ids, User $actor, string $source = 'web'): array
    {
        $this->dictionary->definition($resource);
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            throw ValidationException::withMessages(['ids' => 'Pošalji najmanje jednu stavku za raspored.']);
        }

        $modelClass = $this->dictionary->definition($resource)['model'];
        $existing = $modelClass::query()->whereIn('id', $ids)->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        sort($existing);
        $expected = $ids;
        sort($expected);
        if ($existing !== $expected) {
            throw ValidationException::withMessages(['ids' => 'Jedna ili više stavki više ne postoji. Osveži ekran i pokušaj ponovo.']);
        }

        DB::transaction(static function () use ($modelClass, $ids): void {
            foreach ($ids as $index => $id) {
                $modelClass::query()->whereKey($id)->update(['sort_order' => ($index + 1) * 10]);
            }
        }, 3);

        $this->audit->log(
            'catalog.dictionary.reordered',
            'Promenjen raspored: '.$this->dictionary->definition($resource)['label'],
            metadata: ['resource' => $resource, 'source' => $source, 'ids' => $ids],
            user: $actor,
        );
        $this->forgetReferenceCache();
        return $ids;
    }

    /** @param list<int> $ids @return list<int> */
    public function reorderTypeFields(int $productTypeId, array $ids, User $actor, string $source = 'web'): array
    {
        $productType = ProductType::query()->findOrFail($productTypeId);
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if ($ids === []) {
            throw ValidationException::withMessages(['ids' => 'Pošalji najmanje jedno specifikaciono polje.']);
        }

        $assigned = $productType->fields()->pluck('specification_fields.id')->map(static fn ($id): int => (int) $id)->all();
        $assignedLookup = array_fill_keys($assigned, true);
        foreach ($ids as $fieldId) {
            if (!isset($assignedLookup[$fieldId])) {
                throw ValidationException::withMessages(['ids' => 'Raspored može sadržati samo specifikacije dodeljene ovom tipu proizvoda.']);
            }
        }

        DB::transaction(static function () use ($productType, $ids): void {
            foreach ($ids as $index => $fieldId) {
                DB::table('product_type_fields')
                    ->where('product_type_id', $productType->id)
                    ->where('field_id', $fieldId)
                    ->update(['sort_order' => ($index + 1) * 10]);
            }
        }, 3);

        $this->audit->log(
            'catalog.product_type.fields_reordered',
            'Promenjen raspored specifikacija za tip '.$productType->name,
            $productType,
            metadata: ['source' => $source, 'field_ids' => $ids],
            user: $actor,
        );
        $this->forgetReferenceCache();
        return $ids;
    }

    public function assertMobileResource(string $resource): void
    {
        if (!in_array($resource, self::MOBILE_RESOURCES, true)) abort(404);
    }

    /** @param array<string,mixed> $data @return array<string,mixed> */
    private function modelData(string $resource, array $data): array
    {
        $data['slug'] = Str::slug((string) (($data['slug'] ?? null) ?: $data['name']));
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        if ($resource === 'product-types') {
            if (!empty($data['category_id'])) $data['category_id'] = (int) $data['category_id'];
            else unset($data['category_id']);
            $data['auto_name_enabled'] = !empty($data['auto_name_enabled']);
            $data['minimum_completeness_percent'] = max(0, min(100, (int) ($data['minimum_completeness_percent'] ?? 0)));
            $data['default_product_status'] = in_array(($data['default_product_status'] ?? 'draft'), ['draft', 'active', 'inactive'], true)
                ? $data['default_product_status']
                : 'draft';
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
            if ($field->parent_field_id !== null && !in_array((int) $field->parent_field_id, $orderedIds, true)) {
                $appendWithParents((int) $field->parent_field_id);
            }
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

    /** @param array<int,int|string> $fieldIds @return array<int,array<string,int>> */
    private function specificationUsageCounts(array $fieldIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $fieldIds)));
        if ($ids === []) return [];

        $counts = [];
        foreach ($ids as $id) {
            $counts[$id] = ['types' => 0, 'products' => 0, 'children' => 0];
        }

        $sources = [
            'types' => ['product_type_fields', 'field_id'],
            'products' => ['product_spec_values', 'field_id'],
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

    /** @param array<int,string> $dependencyMaps @param array<int,array<string,int>> $usageCounts @return array<string,mixed> */
    private function itemPayload(string $resource, Model $model, array $dependencyMaps, array $usageCounts): array
    {
        $payload = [
            'id' => (int) $model->getKey(),
            'name' => (string) $model->getAttribute('name'),
            'slug' => (string) $model->getAttribute('slug'),
            'status' => (string) $model->getAttribute('status'),
            'sort_order' => (int) $model->getAttribute('sort_order'),
        ];

        if ($resource === 'categories' && $model instanceof Category) {
            $payload += [
                'parent_id' => $model->parent_id !== null ? (int) $model->parent_id : null,
                'description' => $model->description !== null ? (string) $model->description : null,
            ];
        }
        if ($resource === 'brands' && $model instanceof Brand) {
            $payload += [
                'description' => $model->description !== null ? (string) $model->description : null,
                'website_url' => $model->website_url !== null ? (string) $model->website_url : null,
            ];
        }
        if ($resource === 'product-lines' && $model instanceof ProductLine) {
            $payload += [
                'brand_id' => (int) $model->brand_id,
                'brand' => $model->brand !== null ? ['id' => (int) $model->brand->id, 'name' => (string) $model->brand->name] : null,
            ];
        }
        if ($resource === 'product-types' && $model instanceof ProductType) {
            $payload += [
                'description' => $model->description !== null ? (string) $model->description : null,
                'category' => $model->category !== null ? ['id' => (int) $model->category->id, 'name' => (string) $model->category->name] : null,
                'fields_count' => $model->relationLoaded('fields') ? $model->fields->count() : 0,
                'products_count' => (int) ($model->products_count ?? 0),
            ];
        }
        if ($resource === 'specification-fields' && $model instanceof SpecificationField) {
            $payload += [
                'data_type' => (string) $model->data_type,
                'filter_type' => (string) $model->filter_type,
                'unit' => $model->unit !== null ? (string) $model->unit : null,
                'placeholder' => $model->placeholder !== null ? (string) $model->placeholder : null,
                'help_text' => $model->help_text !== null ? (string) $model->help_text : null,
                'options_text' => $model->options_text !== null ? (string) $model->options_text : null,
                'min_value' => $model->min_value !== null ? (float) $model->min_value : null,
                'max_value' => $model->max_value !== null ? (float) $model->max_value : null,
                'parent_field_id' => $model->parent_field_id !== null ? (int) $model->parent_field_id : null,
                'parent_field' => $model->parentField !== null ? ['id' => (int) $model->parentField->id, 'name' => (string) $model->parentField->name] : null,
                'dependency_map_text' => (string) ($dependencyMaps[(int) $model->id] ?? ''),
                'detail_input_enabled' => (bool) $model->detail_input_enabled,
                'detail_label' => $model->detail_label !== null ? (string) $model->detail_label : null,
                'detail_placeholder' => $model->detail_placeholder !== null ? (string) $model->detail_placeholder : null,
                'usage' => $usageCounts[(int) $model->id] ?? ['types' => 0, 'products' => 0, 'children' => 0],
            ];
        }

        return $payload;
    }

    private function forgetReferenceCache(): void
    {
        $this->referenceCache->forget();
    }
}
