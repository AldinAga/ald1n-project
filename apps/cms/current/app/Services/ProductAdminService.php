<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\SpecificationField;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WarrantyRule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProductAdminService
{
    public function __construct(
        private readonly ProductSkuGenerator $skuGenerator,
        private readonly ProductShortSkuSequenceService $shortSkuSequence,
        private readonly ProductImageService $images,
        private readonly ProductTemplateService $templates,
        private readonly StorageSpecificationService $storageSpecifications,
        private readonly AuditLogger $audit,
    ) {}

    /** @param array<string,mixed> $data */
    public function create(array $data, User $user): Product
    {
        return DB::transaction(function () use ($data, $user): Product {
            [$data, $specs, $specDetails, $specStructured, $files] = $this->prepareTemplateData($data);
            $generated = $this->shouldGenerateName($data);
            if ($generated) $data['name'] = $this->generateProductName($data, $specs, $specDetails);
            if (trim((string) ($data['name'] ?? '')) === '') throw ValidationException::withMessages(['name' => 'Naziv artikla nije mogao biti formiran. Proveri šablon naziva i specifikacije.']);

            $type = $this->typeFromData($data);
            $completeness = $this->templates->completeness($type, $data, $specs, $specDetails);
            $data['completeness_percent'] = $completeness['percent'];
            $data['name_is_manual'] = !$generated;
            $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $specDetails]);
            $data['slug'] = $this->uniqueSlug((string) $data['name']);
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;
            $data['locally_modified_at'] = now();
            $categories = array_map('intval', $data['category_ids'] ?? []);
            unset($data['category_ids'], $data['specs'], $data['spec_details'], $data['spec_lists'], $data['spec_capacities'], $data['spec_structured'], $data['images'], $data['regenerate_sku'], $data['regenerate_name']);

            $product = Product::query()->create($data);
            if ((int) $product->stock_quantity > 0) {
                StockMovement::query()->create([
                    'event_key' => 'product:'.$product->id.':initial',
                    'product_id' => $product->id,
                    'user_id' => $user->id,
                    'movement_type' => 'initial',
                    'source' => 'product_creation',
                    'quantity_change' => (int) $product->stock_quantity,
                    'quantity_before' => 0,
                    'quantity_after' => (int) $product->stock_quantity,
                    'note' => 'Početno stanje pri kreiranju artikla.',
                    'metadata_json' => ['tracked' => true],
                ]);
            }
            $product->categories()->sync($categories);
            $this->syncSpecifications($product, $specs, $specDetails, $specStructured);
            $this->images->upload($product, $files);
            $this->audit->log('product.created', 'Kreiran artikal '.$product->sku, $product, after: $this->snapshot($product), metadata: ['completeness' => $completeness]);
            return $product;
        }, 3);
    }

    /** @param array<string,mixed> $data */
    public function update(Product $product, array $data, User $user): Product
    {
        return DB::transaction(function () use ($product, $data, $user): Product {
            /** @var Product $locked */
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $before = $this->snapshot($locked);
            $stockBefore = (int) $locked->stock_quantity;
            [$data, $specs, $specDetails, $specStructured, $files] = $this->prepareTemplateData($data);

            $generated = $this->shouldGenerateName($data);
            if ($generated) $data['name'] = $this->generateProductName($data, $specs, $specDetails);
            if (trim((string) ($data['name'] ?? '')) === '') throw ValidationException::withMessages(['name' => 'Naziv artikla nije mogao biti formiran. Proveri šablon naziva i specifikacije.']);

            $type = $this->typeFromData($data);
            $completeness = $this->templates->completeness($type, $data, $specs, $specDetails);
            $data['completeness_percent'] = $completeness['percent'];
            $submittedName = trim((string) ($data['name'] ?? ''));
            $data['name_is_manual'] = $generated
                ? false
                : ($submittedName !== trim((string) $locked->name) ? true : (bool) $locked->name_is_manual);
            if (!empty($data['regenerate_sku'])) $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $specDetails], $locked->id);
            $data['slug'] = $this->uniqueSlug((string) $data['name'], $locked->id);
            $data['updated_by'] = $user->id;
            $data['locally_modified_at'] = now();
            $categories = array_map('intval', $data['category_ids'] ?? []);
            unset($data['category_ids'], $data['specs'], $data['spec_details'], $data['spec_lists'], $data['spec_capacities'], $data['spec_structured'], $data['images'], $data['regenerate_sku'], $data['regenerate_name']);

            $locked->update($data);
            $stockAfter = (int) $locked->stock_quantity;
            if ($stockAfter !== $stockBefore) {
                StockMovement::query()->create([
                    'event_key' => 'product-edit:'.(string) Str::uuid(),
                    'product_id' => $locked->id,
                    'user_id' => $user->id,
                    'movement_type' => 'manual_adjustment',
                    'source' => 'product_edit',
                    'quantity_change' => $stockAfter - $stockBefore,
                    'quantity_before' => $stockBefore,
                    'quantity_after' => $stockAfter,
                    'note' => 'Korekcija lagera kroz izmenu artikla.',
                    'metadata_json' => ['tracked' => true],
                ]);
            }
            $locked->categories()->sync($categories);
            $this->syncSpecifications($locked, $specs, $specDetails, $specStructured);
            $this->images->upload($locked, $files);
            $locked->refresh();
            $this->audit->log('product.updated', 'Izmenjen artikal '.$locked->sku, $locked, $before, $this->snapshot($locked), ['completeness' => $completeness]);
            return $locked;
        }, 3);
    }

    /** @param array<string,mixed> $options */
    public function clone(Product $source, array $options, User $user): Product
    {
        return DB::transaction(function () use ($source, $options, $user): Product {
            $source->loadMissing(['categories', 'specificationValues.field', 'images', 'warrantyRules']);
            $copyBasic = (bool) ($options['copy_basic'] ?? true);
            $copySpecs = (bool) ($options['copy_specifications'] ?? true);
            $copyPrice = (bool) ($options['copy_price'] ?? true);
            $copyDescription = (bool) ($options['copy_description'] ?? true);

            $specs = [];
            $details = [];
            $structured = [];
            if ($copySpecs) {
                foreach ($source->specificationValues as $value) {
                    $raw = $value->value_text ?? $value->value_number ?? ($value->value_boolean === null ? null : (int) $value->value_boolean);
                    if ($raw !== null) $specs[(int) $value->field_id] = $raw;
                    if ($value->value_detail !== null) $details[(int) $value->field_id] = $value->value_detail;
                    if (is_array($value->value_json) && $value->value_json !== []) $structured[(int) $value->field_id] = $value->value_json;
                }
            }

            $data = [
                'product_type_id' => $copyBasic ? $source->product_type_id : null,
                'brand_id' => $copyBasic ? $source->brand_id : null,
                'product_line_id' => $copyBasic ? $source->product_line_id : null,
                'model_name' => $copyBasic ? $source->model_name : null,
                'name' => trim((string) ($options['name'] ?? '')) ?: $source->name.' — kopija',
                'price_amount' => $copyPrice ? $source->price_amount : 0,
                'purchase_price_rsd' => $copyPrice ? $source->purchase_price_rsd : null,
                'price_currency' => $copyPrice ? $source->price_currency : 'EUR',
                'manual_commission_eur' => $copyPrice ? $source->manual_commission_eur : null,
                'description' => $copyDescription ? $source->description : 'Kloniran artikal — dopuniti opis.',
                'notes' => !empty($options['copy_notes']) ? $source->notes : null,
                'stock_quantity' => 0,
                'low_stock_threshold' => $source->low_stock_threshold,
                'status' => 'draft',
                'source_product_id' => $source->id,
                'created_by' => $user->id,
                'updated_by' => $user->id,
                'locally_modified_at' => now(),
            ];
            if (!empty($options['regenerate_name']) && $data['product_type_id']) {
                $data['name'] = $this->generateProductName($data, $specs, $details);
                $data['name_is_manual'] = false;
            } else {
                $data['name_is_manual'] = true;
            }
            $data['name'] = $this->uniqueCloneName((string) $data['name']);
            $data['slug'] = $this->uniqueSlug((string) $data['name']);
            $data['sku'] = $this->generateSku($data + ['specs' => $specs, 'spec_details' => $details]);
            $typeCategoryId = !empty($data['product_type_id'])
                ? ProductType::query()->whereKey((int) $data['product_type_id'])->value('category_id')
                : null;
            $categoryIds = $typeCategoryId !== null ? [(int) $typeCategoryId] : [];
            $completeness = $this->templates->completeness($this->typeFromData($data), $data + ['category_ids' => $categoryIds], $specs, $details);
            $data['completeness_percent'] = $completeness['percent'];

            $clone = Product::query()->create($data);
            $clone->categories()->sync($categoryIds);
            if ($copySpecs) $this->syncSpecifications($clone, $specs, $details, $structured);
            if (!empty($options['copy_images'])) $this->images->cloneImages($source, $clone);
            if (!empty($options['copy_warranty_rules']) && Schema::hasTable('warranty_rules')) {
                $this->cloneWarrantyRules($source, $clone, $user);
            }

            $this->audit->log('product.cloned', 'Kloniran artikal '.$source->sku.' kao '.$clone->sku, $clone, after: $this->snapshot($clone), metadata: ['source_product_id' => $source->id, 'options' => $options]);
            return $clone;
        }, 3);
    }

    public function regenerateName(Product $product, User $user): Product
    {
        return DB::transaction(function () use ($product, $user): Product {
            $locked = Product::query()->with([
                'type.fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order'),
                'specificationValues',
            ])->lockForUpdate()->findOrFail($product->id);
            $specs = [];
            $details = [];
            $structured = [];
            foreach ($locked->specificationValues as $value) {
                $specs[(int) $value->field_id] = $value->value_text ?? $value->value_number ?? ($value->value_boolean === null ? null : (int) $value->value_boolean);
                $details[(int) $value->field_id] = $value->value_detail;
            }
            $data = $locked->toArray();
            $name = $this->generateProductName($data, $specs, $details);
            if ($name === '') throw ValidationException::withMessages(['name' => 'Naziv nije mogao biti formiran iz šablona. Proveri podatke artikla i šablon.']);
            $before = $this->snapshot($locked);
            $locked->update(['name' => $name, 'slug' => $this->uniqueSlug($name, $locked->id), 'name_is_manual' => false, 'updated_by' => $user->id, 'locally_modified_at' => now()]);
            $this->audit->log('product.name.regenerated', 'Regenerisan naziv artikla '.$locked->sku, $locked, $before, $this->snapshot($locked));
            return $locked;
        }, 3);
    }

    public function archive(Product $product, User $user): void
    {
        $before = $this->snapshot($product);
        $product->update(['status' => 'archived', 'deleted_at' => now(), 'updated_by' => $user->id, 'locally_modified_at' => now()]);
        $this->audit->log('product.archived', 'Arhiviran artikal '.$product->sku, $product, $before, $this->snapshot($product));
    }

    public function restore(Product $product, User $user): void
    {
        $before = $this->snapshot($product);
        $product->update(['status' => 'inactive', 'deleted_at' => null, 'updated_by' => $user->id, 'locally_modified_at' => now()]);
        $this->audit->log('product.restored', 'Vraćen artikal '.$product->sku, $product, $before, $this->snapshot($product));
    }

    /** @param array<string,mixed> $data @return array{0:array<string,mixed>,1:array<int|string,mixed>,2:array<int|string,mixed>,3:array<int|string,array<int,array<string,mixed>>>,4:array<int,mixed>} */
    private function prepareTemplateData(array $data): array
    {
        $type = $this->typeFromData($data);
        $specs = (array) ($data['specs'] ?? []);
        $details = (array) ($data['spec_details'] ?? []);
        $structured = (array) ($data['spec_structured'] ?? []);
        if ($type !== null) {
            $allowedKeys = $type->fields->pluck('id')->mapWithKeys(static fn ($id): array => [(int) $id => true])->all();
            $specs = array_intersect_key($specs, $allowedKeys);
            $details = array_intersect_key($details, $allowedKeys);
            $structured = array_intersect_key($structured, $allowedKeys);
        }
        $defaults = $this->templates->defaults($type);
        foreach ($defaults['specs'] as $fieldId => $value) if (!array_key_exists($fieldId, $specs) || $specs[$fieldId] === '') $specs[$fieldId] = $value;
        foreach ($defaults['details'] as $fieldId => $value) if (!array_key_exists($fieldId, $details) || $details[$fieldId] === '') $details[$fieldId] = $value;
        if ($type !== null) $this->storageSpecifications->applyComputedTotals($type->fields, $specs, $structured);
        if (empty($data['status']) && $type !== null) $data['status'] = $defaults['status'];
        return [$data, $specs, $details, $structured, (array) ($data['images'] ?? [])];
    }

    /** @param array<string,mixed> $data */
    private function shouldGenerateName(array $data): bool
    {
        if (!empty($data['regenerate_name'])) return true;
        if (trim((string) ($data['name'] ?? '')) !== '') return false;
        return (bool) ($this->typeFromData($data)?->auto_name_enabled ?? false);
    }

    /** @param array<string,mixed> $data @param array<int|string,mixed> $specs @param array<int|string,mixed> $details */
    private function generateProductName(array $data, array $specs, array $details): string
    {
        return $this->templates->generateName($this->typeFromData($data), $data, $specs, $details);
    }

    /** @param array<string,mixed> $data */
    private function typeFromData(array $data): ?ProductType
    {
        $id = (int) ($data['product_type_id'] ?? 0);
        return $id > 0 ? ProductType::query()->with([
            'fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order'),
        ])->find($id) : null;
    }

    /** @param array<string,mixed> $data */
    private function generateSku(array $data, ?int $ignoreId = null): string
    {
        $prefix = '';

        if (!empty($data['brand_id'])) {
            $prefix = trim((string) Brand::query()->whereKey((int) $data['brand_id'])->value('name'));
        }

        if ($prefix === '') {
            $categoryId = null;
            $categoryIds = array_values(array_filter(
                array_map('intval', (array) ($data['category_ids'] ?? [])),
                static fn (int $id): bool => $id > 0,
            ));

            if ($categoryIds !== []) {
                $categoryId = $categoryIds[0];
            } elseif (!empty($data['product_type_id'])) {
                $resolvedCategoryId = ProductType::query()
                    ->whereKey((int) $data['product_type_id'])
                    ->value('category_id');
                if ($resolvedCategoryId !== null) {
                    $categoryId = (int) $resolvedCategoryId;
                }
            }

            if ($categoryId !== null) {
                $prefix = trim((string) \App\Models\Category::query()->whereKey($categoryId)->value('name'));
            }
        }

        if ($prefix === '') {
            $prefix = 'ARTIKAL';
        }

        return $this->shortSkuSequence->generate(
            $prefix,
            static function (string $candidate) use ($ignoreId): bool {
                $productExists = Product::query()
                    ->whereRaw('LOWER(sku) = ?', [mb_strtolower($candidate)])
                    ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                    ->exists();

                if ($productExists) {
                    return true;
                }

                return false;
            },
        );
    }
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'artikal';
        $slug = $base;
        $i = 2;
        while (Product::query()->where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) $slug = $base.'-'.$i++;
        return $slug;
    }

    private function uniqueCloneName(string $name): string
    {
        $base = trim($name) !== '' ? trim($name) : 'Klonirani artikal';
        $candidate = $base;
        $i = 2;
        while (Product::query()->where('name', $candidate)->exists()) $candidate = $base.' '.$i++;
        return mb_substr($candidate, 0, 190);
    }

    /** @param array<int|string,mixed> $specs @param array<int|string,mixed> $details @param array<int|string,array<int,array<string,mixed>>> $structured */
    private function syncSpecifications(Product $product, array $specs, array $details = [], array $structured = []): void
    {
        DB::table('product_spec_values')->where('product_id', $product->id)->delete();
        if ($product->product_type_id === null) return;
        $allowed = SpecificationField::query()
            ->where('status', 'active')
            ->whereHas('productTypes', fn ($q) => $q->where('product_types.id', $product->product_type_id))
            ->get()
            ->keyBy('id');
        foreach ($specs as $fieldId => $raw) {
            $field = $allowed->get((int) $fieldId);
            if (!$field || $raw === '' || $raw === null) continue;
            $row = [
                'product_id' => $product->id,
                'field_id' => $field->id,
                'created_at' => now(),
                'updated_at' => now(),
                'value_text' => null,
                'value_detail' => null,
                'value_json' => null,
                'value_number' => null,
                'value_boolean' => null,
            ];
            if (in_array($field->data_type, ['integer', 'decimal'], true)) {
                $normalized = str_replace(',', '.', (string) $raw);
                $row['value_number'] = $field->requiresWholeGigabytes() ? (int) $normalized : (float) $normalized;
            } elseif ($field->data_type === 'boolean') $row['value_boolean'] = (bool) $raw;
            else {
                $row['value_text'] = mb_substr(trim((string) $raw), 0, 1000);
                $structuredValue = (array) ($structured[$field->id] ?? []);
                if ($field->isRepeatableStorageField() && $structuredValue !== []) {
                    $row['value_json'] = json_encode(array_values($structuredValue), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
                }
                if ($field->detail_input_enabled) {
                    $detail = trim((string) ($details[$field->id] ?? ''));
                    $row['value_detail'] = $detail !== '' ? mb_substr($detail, 0, 500) : null;
                }
            }
            try {
                DB::table('product_spec_values')->insert($row);
            } catch (QueryException $exception) {
                throw ValidationException::withMessages([
                    'specs.'.$field->id => 'Specifikacija „'.$field->name.'“ nije sačuvana. Osveži stranicu i pokušaj ponovo. Tehnički kod: '.(string) ($exception->errorInfo[1] ?? $exception->getCode()),
                ]);
            }
        }
    }

    /** @return array<int,int> */
    private function cloneWarrantyRules(Product $source, Product $clone, User $user): array
    {
        $map = [];
        foreach ($source->warrantyRules()->where('scope_type', 'product')->get() as $rule) {
            $copy = $rule->replicate(['id', 'created_at', 'updated_at']);
            $copy->product_id = $clone->id;
            $copy->name = mb_substr($rule->name.' — '.$clone->sku, 0, 190);
            $copy->created_by = $user->id;
            $copy->updated_by = $user->id;
            $copy->save();
            $map[(int) $rule->id] = (int) $copy->id;
        }
        return $map;
    }


    /** @return array<string,mixed> */
    private function snapshot(Product $product): array
    {
        $fresh = $product->fresh(['categories:id', 'images:id,is_primary,sort_order']);
        if ($fresh === null) return [];
        return $fresh->only([
            'id','sku','name','slug','product_type_id','brand_id','product_line_id','model_name','price_amount','price_currency','purchase_price_rsd',
            'manual_commission_eur','description','notes','stock_quantity','low_stock_threshold','status','deleted_at',
            'completeness_percent','name_is_manual','source_product_id',
        ]) + ['category_ids' => $fresh->categories->pluck('id')->map(fn ($id) => (int) $id)->all()];
    }
}
