<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ProductLine;
use App\Models\ProductVariant;
use App\Models\SpecificationOption;
use App\Services\CatalogAccessService;
use App\Services\StorageSpecificationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class ProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $product = $this->route('product');

        return $user !== null
            && $product !== null
            && app(CatalogAccessService::class)->canManage($product, $user);
    }

    public function rules(): array
    {
        $variant = $this->route('variant');
        $variantId = $variant instanceof ProductVariant ? $variant->id : null;
        return [
            'sku' => ['required', 'string', 'max:100', 'regex:#^[A-Z0-9._/-]+$#', Rule::unique('product_variants', 'sku')->ignore($variantId), Rule::unique('products', 'sku')],
            'name' => ['nullable', 'string', 'max:190'],
            'price_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'price_currency' => ['required', Rule::in(['RSD', 'EUR'])],
            'purchase_price_rsd' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'manual_commission_eur' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'stock_quantity' => [$variantId ? 'nullable' : 'required', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
            'is_default' => ['nullable', 'boolean'],
            'warranty_rule_id' => ['nullable', 'integer', 'exists:warranty_rules,id'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'specs' => ['array'],
            'specs.*' => ['nullable'],
            'spec_details' => ['array'],
            'spec_details.*' => ['nullable', 'string', 'max:500'],
            'spec_lists' => ['array'],
            'spec_lists.*' => ['array', 'max:8'],
            'spec_lists.*.*' => ['nullable', 'string', 'max:255'],
            'spec_capacities' => ['array'],
            'spec_capacities.*' => ['array', 'max:8'],
            'spec_capacities.*.*' => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'spec_structured' => ['array'],
            'spec_structured.*' => ['array', 'max:8'],
            'spec_structured.*.*.type' => ['required', 'string', 'max:255'],
            'spec_structured.*.*.capacity_gb' => ['nullable', 'integer', 'min:0', 'max:10000000'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $product = $this->route('product');
            if ($product === null || $product->product_type_id === null) {
                $validator->errors()->add('specs', 'Artikal mora imati izabran tip pre dodavanja varijanti.');
                return;
            }
            $product->load([
                'type.fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order'),
                'type.fields.options' => fn ($query) => $query->where('status', 'active'),
            ]);
            $fields = $product->type?->fields->keyBy('id') ?? collect();
            $specs = (array) $this->input('specs', []);
            $structured = (array) $this->input('spec_structured', []);
            $hasDifferentiator = false;
            foreach ($specs as $fieldId => $raw) {
                if ($raw === '' || $raw === null) continue;
                $field = $fields->get((int) $fieldId);
                if ($field === null) {
                    $validator->errors()->add('specs.'.$fieldId, 'Specifikacija ne pripada tipu ovog artikla.');
                    continue;
                }
                if ($field->isDerivedStorageTotalField()) continue;
                if ($field->isRepeatableStorageField()) {
                    $rows = (array) ($structured[$field->id] ?? []);
                    if ($rows !== []) $hasDifferentiator = true;
                    if ($field->data_type === 'select' && $field->options->isNotEmpty()) {
                        $allowedValues = $field->options->pluck('value')->map(static fn ($value): string => (string) $value)->all();
                        foreach ($rows as $index => $row) {
                            $selectedValue = trim((string) ($row['type'] ?? ''));
                            if ($selectedValue !== '' && !in_array($selectedValue, $allowedValues, true)) {
                                $validator->errors()->add('spec_lists.'.$field->id.'.'.$index, 'Izabrana opcija diska „'.$selectedValue.'“ više nije dostupna.');
                            }
                        }
                    }
                    continue;
                }
                $hasDifferentiator = true;
                if ($field->requiresWholeGigabytes() && in_array((string) $field->data_type, ['integer', 'decimal'], true)) {
                    $normalized = str_replace(',', '.', trim((string) $raw));
                    if (!preg_match('/^\d+$/', $normalized)) {
                        $validator->errors()->add('specs.'.$fieldId, 'Polje „'.$field->name.'“ u GB mora biti ceo broj bez decimala.');
                    }
                }
                if ($field->data_type !== 'select' || $field->options->isEmpty()) continue;
                $selected = $field->options->first(fn (SpecificationOption $option): bool => (string) $option->value === (string) $raw && $option->status === 'active');
                if ($selected === null) {
                    $validator->errors()->add('specs.'.$fieldId, 'Izabrana opcija više nije dostupna.');
                    continue;
                }
                if ($field->parent_field_id === null) continue;
                $parentRaw = $specs[$field->parent_field_id] ?? null;
                $parentId = SpecificationOption::query()->where('field_id', $field->parent_field_id)->where('status', 'active')->where('value', (string) $parentRaw)->value('id');
                if ($parentId === null || !DB::table('specification_option_dependencies')->where('parent_option_id', $parentId)->where('child_option_id', $selected->id)->exists()) {
                    $validator->errors()->add('specs.'.$fieldId, 'Izabrana opcija nije povezana sa roditeljskom vrednošću.');
                }
            }
            if (!$hasDifferentiator && trim((string) $this->input('name')) === '') {
                $validator->errors()->add('name', 'Unesi naziv varijante ili izaberi najmanje jednu specifikaciju po kojoj se razlikuje.');
            }
            $warrantyRuleId = (int) $this->input('warranty_rule_id', 0);
            if ($warrantyRuleId > 0 && !DB::table('warranty_rules')->where('id', $warrantyRuleId)->where('is_active', true)->where(function ($query) use ($product): void {
                $query->where('scope_type', 'global')->orWhere(function ($nested) use ($product): void {
                    $nested->where('scope_type', 'product')->where('product_id', $product->id);
                });
            })->exists()) {
                $validator->errors()->add('warranty_rule_id', 'Izabrano pravilo garancije nije dostupno za ovaj artikal.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $product = $this->route('product');
        $fields = collect();
        if ($product !== null && $product->product_type_id !== null) {
            $product->load([
                'type.fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order'),
            ]);
            $fields = $product->type?->fields ?? collect();
        }
        $allowedKeys = $fields->pluck('id')->mapWithKeys(static fn ($id): array => [(int) $id => true])->all();

        $details = [];
        foreach ((array) $this->input('spec_details', []) as $fieldId => $value) {
            if ($allowedKeys !== [] && !isset($allowedKeys[(int) $fieldId])) continue;
            $details[$fieldId] = trim((string) $value);
        }

        $specs = (array) $this->input('specs', []);
        if ($allowedKeys !== []) $specs = array_intersect_key($specs, $allowedKeys);
        $structured = [];
        $capacityLists = (array) $this->input('spec_capacities', []);
        foreach ((array) $this->input('spec_lists', []) as $fieldId => $values) {
            if (!is_array($values) || ($allowedKeys !== [] && !isset($allowedKeys[(int) $fieldId]))) continue;
            $capacities = is_array($capacityLists[$fieldId] ?? null) ? $capacityLists[$fieldId] : [];
            $rows = [];
            $display = [];
            foreach ($values as $index => $value) {
                $diskType = mb_substr(trim((string) $value), 0, 255);
                $capacityRaw = trim((string) ($capacities[$index] ?? ''));
                if ($diskType === '' && $capacityRaw === '') continue;
                $capacity = $capacityRaw === '' ? null : $capacityRaw;
                $rows[] = ['type' => $diskType, 'capacity_gb' => $capacity];
                $display[] = trim($diskType.($capacity !== null ? ' '.$capacity.' GB' : ''));
            }
            $structured[$fieldId] = $rows;
            $specs[$fieldId] = implode(' + ', array_filter($display, static fn (string $value): bool => $value !== ''));
        }

        if ($fields->isNotEmpty()) {
            $structured = array_intersect_key($structured, $allowedKeys);
            app(StorageSpecificationService::class)->applyComputedTotals($fields, $specs, $structured);
        }

        $this->merge([
            'sku' => mb_strtoupper(trim((string) $this->input('sku'))),
            'name' => trim((string) $this->input('name')),
            'price_amount' => str_replace(',', '.', (string) $this->input('price_amount')),
            'purchase_price_rsd' => $this->filled('purchase_price_rsd') ? str_replace(',', '.', (string) $this->input('purchase_price_rsd')) : null,
            'manual_commission_eur' => $this->filled('manual_commission_eur') ? str_replace(',', '.', (string) $this->input('manual_commission_eur')) : null,
            'warranty_rule_id' => $this->filled('warranty_rule_id') ? $this->integer('warranty_rule_id') : null,
            'is_default' => $this->boolean('is_default'),
            'specs' => $specs,
            'spec_details' => $details,
            'spec_structured' => $structured,
        ]);
    }
}
