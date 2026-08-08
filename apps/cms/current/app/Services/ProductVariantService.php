<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SpecificationField;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ProductVariantService
{
    public function __construct(private readonly AuditLogger $audit, private readonly StorageSpecificationService $storageSpecifications) {}

    /** @param array<string,mixed> $data */
    public function create(Product $product, array $data, User $actor): ProductVariant
    {
        return DB::transaction(function () use ($product, $data, $actor): ProductVariant {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $specs = (array) ($data['specs'] ?? []);
            $details = (array) ($data['spec_details'] ?? []);
            $structured = (array) ($data['spec_structured'] ?? []);
            $locked->load(['type.fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order')]);
            if ($locked->type) $this->storageSpecifications->applyComputedTotals($locked->type->fields, $specs, $structured);
            $name = $this->name($locked, trim((string) ($data['name'] ?? '')), $specs, $details);
            $stock = (int) ($data['stock_quantity'] ?? 0);
            $variant = ProductVariant::query()->create([
                'product_id' => $locked->id,
                'sku' => (string) $data['sku'],
                'name' => $name,
                'price_amount' => $data['price_amount'],
                'price_currency' => $data['price_currency'],
                'purchase_price_rsd' => $data['purchase_price_rsd'] ?? null,
                'manual_commission_eur' => $data['manual_commission_eur'] ?? null,
                'stock_quantity' => $stock,
                'low_stock_threshold' => (int) $data['low_stock_threshold'],
                'status' => $data['status'],
                'is_default' => false,
                'warranty_rule_id' => $data['warranty_rule_id'] ?? null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $this->syncSpecifications($locked, $variant, $specs, $details, $structured);
            if ($stock > 0) {
                StockMovement::query()->create([
                    'event_key' => 'variant-opening:'.$variant->id,
                    'product_id' => $locked->id,
                    'product_variant_id' => $variant->id,
                    'user_id' => $actor->id,
                    'movement_type' => 'opening_balance',
                    'source' => 'variant_create',
                    'quantity_change' => $stock,
                    'quantity_before' => 0,
                    'quantity_after' => $stock,
                    'note' => 'Početno stanje varijante '.$variant->sku,
                    'metadata_json' => ['variant_id' => $variant->id],
                ]);
            }
            $mustDefault = (bool) ($data['is_default'] ?? false) || !ProductVariant::query()->where('product_id', $locked->id)->where('id', '!=', $variant->id)->whereNull('deleted_at')->exists();
            if ($mustDefault) $this->setDefaultLocked($locked, $variant);
            $this->syncParentLocked($locked);
            $this->audit->log('product.variant.created', 'Kreirana varijanta '.$variant->sku, $variant, after: $this->snapshot($variant), user: $actor);
            return $variant->fresh(['specificationValues.field', 'images']) ?? $variant;
        });
    }

    /** @param array<string,mixed> $data */
    public function update(Product $product, ProductVariant $variant, array $data, User $actor): ProductVariant
    {
        $this->assertOwner($product, $variant);
        return DB::transaction(function () use ($product, $variant, $data, $actor): ProductVariant {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $before = $this->snapshot($locked);
            $specs = (array) ($data['specs'] ?? []);
            $details = (array) ($data['spec_details'] ?? []);
            $structured = (array) ($data['spec_structured'] ?? []);
            $lockedProduct->load(['type.fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order')]);
            if ($lockedProduct->type) $this->storageSpecifications->applyComputedTotals($lockedProduct->type->fields, $specs, $structured);
            $locked->fill([
                'sku' => (string) $data['sku'],
                'name' => $this->name($lockedProduct, trim((string) ($data['name'] ?? '')), $specs, $details),
                'price_amount' => $data['price_amount'], 'price_currency' => $data['price_currency'],
                'purchase_price_rsd' => $data['purchase_price_rsd'] ?? null,
                'manual_commission_eur' => $data['manual_commission_eur'] ?? null,
                'low_stock_threshold' => (int) $data['low_stock_threshold'], 'status' => $data['status'],
                'warranty_rule_id' => $data['warranty_rule_id'] ?? null, 'sort_order' => (int) ($data['sort_order'] ?? 0),
                'updated_by' => $actor->id,
            ])->save();
            $this->syncSpecifications($lockedProduct, $locked, $specs, $details, $structured);
            if ((bool) ($data['is_default'] ?? false)) $this->setDefaultLocked($lockedProduct, $locked);
            $this->ensureDefaultLocked($lockedProduct);
            $this->syncParentLocked($lockedProduct);
            $this->audit->log('product.variant.updated', 'Izmenjena varijanta '.$locked->sku, $locked, $before, $this->snapshot($locked), user: $actor);
            return $locked->fresh(['specificationValues.field', 'images']) ?? $locked;
        });
    }

    public function adjustStock(Product $product, ProductVariant $variant, int $change, string $note, string $key, User $actor): StockMovement
    {
        $this->assertOwner($product, $variant);
        return DB::transaction(function () use ($product, $variant, $change, $note, $key, $actor): StockMovement {
            $eventKey = 'variant-adjust:'.hash('sha256', $variant->id.'|'.$actor->id.'|'.trim($key));
            $existing = StockMovement::query()->where('event_key', $eventKey)->first();
            if ($existing) return $existing;
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            $before = (int) $locked->stock_quantity;
            $after = $before + $change;
            if ($after < 0) throw ValidationException::withMessages(['quantity_change' => 'Korekcija ne može spustiti lager varijante ispod nule.']);
            $locked->forceFill(['stock_quantity' => $after, 'updated_by' => $actor->id])->save();
            $movement = StockMovement::query()->create([
                'event_key' => $eventKey, 'product_id' => $product->id, 'product_variant_id' => $locked->id,
                'user_id' => $actor->id, 'movement_type' => 'manual_adjustment', 'source' => 'variant_adjustment',
                'quantity_change' => $change, 'quantity_before' => $before, 'quantity_after' => $after,
                'note' => trim($note), 'metadata_json' => ['variant_id' => $locked->id, 'idempotent' => true],
            ]);
            $this->syncParentLocked($lockedProduct);
            $this->audit->log('product.variant.stock_adjusted', 'Korigovan lager varijante '.$locked->sku, $locked, ['stock_quantity' => $before], ['stock_quantity' => $after], ['movement_id' => $movement->id], $actor);
            return $movement;
        });
    }

    public function archive(Product $product, ProductVariant $variant, User $actor): void
    {
        $this->assertOwner($product, $variant);
        DB::transaction(function () use ($product, $variant, $actor): void {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $locked = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
            if ((int) $locked->stock_quantity > 0) throw ValidationException::withMessages(['variant' => 'Varijanta sa stanjem većim od nule ne može biti arhivirana.']);
            $locked->forceFill(['status' => 'inactive', 'is_default' => false, 'deleted_at' => now(), 'updated_by' => $actor->id])->save();
            $this->ensureDefaultLocked($lockedProduct);
            $this->syncParentLocked($lockedProduct);
            $this->audit->log('product.variant.archived', 'Arhivirana varijanta '.$locked->sku, $locked, user: $actor);
        });
    }

    public function setDefault(Product $product, ProductVariant $variant, User $actor): void
    {
        $this->assertOwner($product, $variant);
        DB::transaction(function () use ($product, $variant, $actor): void {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $locked = ProductVariant::query()->whereNull('deleted_at')->lockForUpdate()->findOrFail($variant->id);
            $this->setDefaultLocked($lockedProduct, $locked);
            $this->syncParentLocked($lockedProduct);
            $this->audit->log('product.variant.default', 'Postavljena podrazumevana varijanta '.$locked->sku, $locked, user: $actor);
        });
    }

    public function syncParent(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $this->ensureDefaultLocked($locked);
            $this->syncParentLocked($locked);
        });
    }

    private function setDefaultLocked(Product $product, ProductVariant $variant): void
    {
        ProductVariant::query()->where('product_id', $product->id)->where('id', '!=', $variant->id)->update(['is_default' => false]);
        $variant->forceFill(['is_default' => true])->save();
        $product->forceFill(['default_variant_id' => $variant->id, 'variants_enabled' => true])->save();
    }

    private function ensureDefaultLocked(Product $product): void
    {
        $base = ProductVariant::query()->where('product_id', $product->id)->whereNull('deleted_at');
        $default = (clone $base)->where('is_default', true)->where('status', 'active')->first();
        if ($default === null) $default = (clone $base)->where('status', 'active')->orderBy('sort_order')->orderBy('id')->first();
        if ($default === null) $default = (clone $base)->where('is_default', true)->first();
        if ($default === null) $default = (clone $base)->orderBy('sort_order')->orderBy('id')->first();
        if ($default) $this->setDefaultLocked($product, $default);
        else $product->forceFill(['default_variant_id' => null, 'variants_enabled' => false])->save();
    }

    private function syncParentLocked(Product $product): void
    {
        $variants = ProductVariant::query()->where('product_id', $product->id)->whereNull('deleted_at')->get();
        if ($variants->isEmpty()) {
            $product->forceFill(['variants_enabled' => false, 'default_variant_id' => null])->save();
            return;
        }
        $active = $variants->where('status', 'active');
        $default = $active->firstWhere('id', (int) $product->default_variant_id) ?? $active->first() ?? $variants->firstWhere('id', (int) $product->default_variant_id) ?? $variants->first();
        ProductVariant::query()->where('product_id', $product->id)->update(['is_default' => false]);
        if ($default) ProductVariant::query()->whereKey($default->id)->update(['is_default' => true]);
        $product->forceFill([
            'variants_enabled' => true,
            'default_variant_id' => $default?->id,
            'stock_quantity' => (int) $active->sum('stock_quantity'),
            'low_stock_threshold' => (int) max(0, $active->sum('low_stock_threshold')),
            'price_amount' => $default?->price_amount ?? $product->price_amount,
            'price_currency' => $default?->price_currency ?? $product->price_currency,
            'manual_commission_eur' => $default?->manual_commission_eur,
        ])->save();
    }

    /** @param array<int|string,mixed> $specs @param array<int|string,mixed> $details @param array<int|string,array<int,array<string,mixed>>> $structured */
    private function syncSpecifications(Product $product, ProductVariant $variant, array $specs, array $details, array $structured = []): void
    {
        DB::table('product_variant_spec_values')->where('product_variant_id', $variant->id)->delete();
        $allowed = SpecificationField::query()->where('status', 'active')->whereHas('productTypes', fn ($query) => $query->where('product_types.id', $product->product_type_id))->get()->keyBy('id');
        foreach ($specs as $fieldId => $raw) {
            $field = $allowed->get((int) $fieldId);
            if (!$field || $raw === '' || $raw === null) continue;
            $row = ['product_variant_id' => $variant->id, 'field_id' => $field->id, 'value_text' => null, 'value_detail' => null, 'value_json' => null, 'value_number' => null, 'value_boolean' => null, 'created_at' => now(), 'updated_at' => now()];
            if (in_array($field->data_type, ['integer', 'decimal'], true)) {
                $normalized = str_replace(',', '.', (string) $raw);
                $row['value_number'] = $field->requiresWholeGigabytes() ? (int) $normalized : (float) $normalized;
            }
            elseif ($field->data_type === 'boolean') $row['value_boolean'] = (bool) $raw;
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
                DB::table('product_variant_spec_values')->insert($row);
            } catch (QueryException $exception) {
                throw ValidationException::withMessages([
                    'specs.'.$field->id => 'Specifikacija varijante „'.$field->name.'“ nije sačuvana. Osveži stranicu i pokušaj ponovo. Tehnički kod: '.(string) ($exception->errorInfo[1] ?? $exception->getCode()),
                ]);
            }
        }
    }

    /** @param array<int|string,mixed> $specs @param array<int|string,mixed> $details */
    private function name(Product $product, string $name, array $specs, array $details): string
    {
        if ($name !== '') return mb_substr($name, 0, 190);
        $product->load(['type.fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order')]);
        $parts = [];
        foreach ($product->type?->fields ?? [] as $field) {
            if ($field->isDerivedStorageTotalField()) continue;
            $value = $specs[$field->id] ?? null;
            if ($value === null || $value === '') continue;
            $detail = trim((string) ($details[$field->id] ?? ''));
            $display = trim((string) $value.' '.$detail);
            if ($field->unit) $display .= ' '.$field->unit;
            $parts[] = $field->name.': '.$display;
        }
        return mb_substr($parts !== [] ? implode(' · ', $parts) : 'Varijanta', 0, 190);
    }

    private function assertOwner(Product $product, ProductVariant $variant): void
    {
        abort_unless((int) $variant->product_id === (int) $product->id, 404);
    }

    /** @return array<string,mixed> */
    private function snapshot(ProductVariant $variant): array
    {
        return $variant->only(['id','product_id','sku','name','price_amount','price_currency','purchase_price_rsd','manual_commission_eur','stock_quantity','low_stock_threshold','status','is_default','warranty_rule_id','sort_order','deleted_at']);
    }
}
