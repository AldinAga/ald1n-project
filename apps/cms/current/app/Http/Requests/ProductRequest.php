<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\SpecificationOption;
use App\Services\CatalogAccessService;
use App\Services\ProductTemplateService;
use App\Services\StorageSpecificationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null || !$user->can('catalog.manage_products')) return false;

        $product = $this->route('product');
        if ($product !== null && !app(CatalogAccessService::class)->canManage($product, $user)) return false;

        if ($product && $this->has('stock_quantity') && (int) $this->input('stock_quantity') !== (int) $product->stock_quantity) {
            return $user->can('stock.adjust');
        }

        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;
        return [
            'product_type_id' => ['nullable', 'integer', 'exists:product_types,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'product_line_id' => ['nullable', 'integer', 'exists:product_lines,id'],
            'model_name' => ['nullable', 'string', 'max:190'],
            'sku' => [$productId ? 'required' : 'nullable', 'string', 'max:100', 'regex:#^[A-Z0-9._/-]+$#', Rule::unique('products', 'sku')->ignore($productId)],
            'regenerate_sku' => ['nullable', 'boolean'],
            'regenerate_name' => ['nullable', 'boolean'],
            'name' => ['nullable', 'string', 'max:190'],
            'price_amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'price_currency' => ['required', Rule::in(['RSD', 'EUR'])],
            'purchase_price_rsd' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'manual_commission_eur' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'description' => ['required', 'string', 'max:65000'],
            'notes' => ['nullable', 'string', 'max:65000'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:1000000'],
            'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:1000000'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
            'category_ids' => ['array', 'max:1'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
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
            'images' => ['array', 'max:20'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            // COMMISSION_PERCENTAGE_POLICY_V0_7
            if (
                $this->filled('manual_commission_eur')
                && is_numeric($this->input('manual_commission_eur'))
                && is_numeric($this->input('price_amount'))
            ) {
                $priceAmount = (float) $this->input('price_amount');
                $currency = mb_strtoupper(trim((string) $this->input('price_currency')));
                $manualEur = (float) $this->input('manual_commission_eur');

                if (in_array($currency, ['EUR', 'RSD'], true)) {
                    $rate = null;
                    if ($currency === 'RSD') {
                        try {
                            $rate = app(\App\Services\SettingsService::class)->eurRsdRate();
                        } catch (\Throwable) {
                            $rate = null;
                        }
                    }

                    if ($currency === 'RSD' && ($rate === null || $rate <= 0.0)) {
                        $validator->errors()->add(
                            'manual_commission_eur',
                            'EUR/RSD kurs mora biti dostupan da bi se proverilo 10% vrednosti artikla.',
                        );
                    } else {
                        $calculator = app(\App\Services\CommissionCalculator::class);
                        $minimum = $calculator->manualMinimumEur($priceAmount, $currency, $rate);
                        if (!$calculator->usesManual($priceAmount, $currency, $manualEur, $rate)) {
                            $validator->errors()->add(
                                'manual_commission_eur',
                                sprintf(
                                    'Ručna provizija mora biti najmanje %s EUR (10%% vrednosti artikla).',
                                    number_format($minimum, 2, ',', '.'),
                                ),
                            );
                        }
                    }
                }
            }

            $product = $this->route('product');

            $brandId = $this->input('brand_id');
            $lineId = $this->input('product_line_id');
            if ($lineId !== null) {
                $lineBrand = ProductLine::query()->whereKey((int) $lineId)->value('brand_id');
                if ($lineBrand !== null && (int) $lineBrand !== (int) $brandId) {
                    $validator->errors()->add('product_line_id', 'Izabrana linija ne pripada izabranom brendu.');
                }
            }

            // PRODUCT_REQUEST_TYPE_SCOPED_TAXONOMY_V07
            $typeId = $this->input('product_type_id');
            if ($typeId !== null && $brandId !== null) {
                $brandAllowed = \Illuminate\Support\Facades\DB::table('brand_product_type')
                    ->where('brand_id', (int) $brandId)
                    ->where('product_type_id', (int) $typeId)
                    ->exists();
                if (!$brandAllowed) {
                    $validator->errors()->add('brand_id', 'Izabrani brend nije dostupan za izabrani tip artikla.');
                }
            }
            if ($typeId !== null && $lineId !== null) {
                $lineAllowed = \Illuminate\Support\Facades\DB::table('product_line_product_type')
                    ->where('product_line_id', (int) $lineId)
                    ->where('product_type_id', (int) $typeId)
                    ->exists();
                if (!$lineAllowed) {
                    $validator->errors()->add('product_line_id', 'Izabrana linija nije dostupna za izabrani tip artikla.');
                }
            }
            if ($typeId === null) {
                if (trim((string) $this->input('name')) === '') $validator->errors()->add('name', 'Naziv je obavezan kada tip artikla nije izabran.');
                return;
            }

            $type = ProductType::query()
                ->with([
                    'fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order'),
                    'fields.options' => fn ($query) => $query->where('status', 'active'),
                ])
                ->find((int) $typeId);
            $fields = $type?->fields->keyBy('id') ?? collect();
            $specs = (array) $this->input('specs', []);
            $details = (array) $this->input('spec_details', []);
            $structured = (array) $this->input('spec_structured', []);

            if ($type !== null && $type->category_id === null) {
                $validator->errors()->add('product_type_id', 'Izabrani tip nema automatsku sistemsku kategoriju. Pokreni catalog settings repair ili ponovo sačuvaj tip.');
            }

            if (trim((string) $this->input('name')) === '' && !$this->boolean('regenerate_name') && !(bool) ($type?->auto_name_enabled ?? false)) {
                $validator->errors()->add('name', 'Naziv je obavezan ili uključi automatsko formiranje naziva.');
            }

            foreach ($fields as $field) {
                $raw = $specs[$field->id] ?? null;
                if ((bool) ($field->pivot?->is_required ?? false) && ($raw === '' || $raw === null)) {
                    $validator->errors()->add('specs.'.$field->id, 'Polje „'.$field->name.'“ je obavezno.');
                }

                if ($field->requiresWholeGigabytes() && $raw !== '' && $raw !== null && in_array((string) $field->data_type, ['integer', 'decimal'], true)) {
                    $normalized = str_replace(',', '.', trim((string) $raw));
                    if (!preg_match('/^\d+$/', $normalized)) {
                        $validator->errors()->add('specs.'.$field->id, 'Polje „'.$field->name.'“ u GB mora biti ceo broj bez decimala.');
                    }
                }

                if ($field->isRepeatableStorageField()) {
                    $rows = (array) ($structured[$field->id] ?? []);
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

                if ($raw === '' || $raw === null || $field->data_type !== 'select' || $field->options->isEmpty()) continue;

                $selectedOption = $field->options->first(fn (SpecificationOption $option): bool => (string) $option->value === (string) $raw);
                if ($selectedOption === null) {
                    $validator->errors()->add('specs.'.$field->id, 'Izabrana opcija više nije dostupna.');
                    continue;
                }

                if ($field->parent_field_id === null) continue;
                if (!$fields->has((int) $field->parent_field_id)) {
                    $validator->errors()->add('specs.'.$field->id, 'Roditeljsko polje nije dodeljeno izabranom tipu artikla.');
                    continue;
                }
                $parentRaw = $specs[$field->parent_field_id] ?? null;
                if ($parentRaw === '' || $parentRaw === null) {
                    $validator->errors()->add('specs.'.$field->id, 'Prvo izaberi vrednost roditeljskog polja.');
                    continue;
                }
                $parentOptionId = SpecificationOption::query()
                    ->where('field_id', $field->parent_field_id)
                    ->where('status', 'active')
                    ->where('value', (string) $parentRaw)
                    ->value('id');
                if ($parentOptionId === null || !DB::table('specification_option_dependencies')
                    ->where('parent_option_id', $parentOptionId)
                    ->where('child_option_id', $selectedOption->id)
                    ->exists()) {
                    $validator->errors()->add('specs.'.$field->id, 'Izabrana opcija nije povezana sa roditeljskim izborom.');
                }
            }

            if ($type !== null && (string) $this->input('status') === 'active') {
                $result = app(ProductTemplateService::class)->completeness($type, $this->all(), $specs, $details);
                $minimum = max(0, min(100, (int) ($type->minimum_completeness_percent ?? 0)));
                if ($result['percent'] < $minimum) {
                    $validator->errors()->add('status', 'Artikal ima '.$result['percent'].'% kompletnosti, a za aktivaciju je potrebno najmanje '.$minimum.'%. Nedostaje: '.implode(', ', array_slice($result['missing'], 0, 8)).'.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $details = [];
        foreach ((array) $this->input('spec_details', []) as $fieldId => $value) {
            $details[$fieldId] = trim((string) $value);
        }

        $typeId = $this->filled('product_type_id') ? $this->integer('product_type_id') : null;
        $type = $typeId !== null ? ProductType::query()->with([
            'fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order'),
        ])->find($typeId) : null;
        $allowedFieldKeys = $type?->fields->pluck('id')->mapWithKeys(static fn ($id): array => [(int) $id => true])->all() ?? [];
        $specs = (array) $this->input('specs', []);
        $structured = [];
        $capacityLists = (array) $this->input('spec_capacities', []);

        foreach ((array) $this->input('spec_lists', []) as $fieldId => $values) {
            if (!is_array($values) || ($type !== null && !isset($allowedFieldKeys[(int) $fieldId]))) continue;
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

        if ($type !== null) {
            $specs = array_intersect_key($specs, $allowedFieldKeys);
            $details = array_intersect_key($details, $allowedFieldKeys);
            $structured = array_intersect_key($structured, $allowedFieldKeys);

            $defaults = app(ProductTemplateService::class)->defaults($type);
            foreach ($defaults['specs'] as $fieldId => $value) if (!array_key_exists($fieldId, $specs) || $specs[$fieldId] === '') $specs[$fieldId] = $value;
            foreach ($defaults['details'] as $fieldId => $value) if (!array_key_exists($fieldId, $details) || $details[$fieldId] === '') $details[$fieldId] = $value;
            app(StorageSpecificationService::class)->applyComputedTotals($type->fields, $specs, $structured);
        }

        $categoryId = $this->resolveCategoryId($type);

        $this->merge([
            'sku' => mb_strtoupper(trim((string) $this->input('sku'))),
            'purchase_price_rsd' => $this->filled('purchase_price_rsd') ? str_replace(',', '.', (string) $this->input('purchase_price_rsd')) : null,
            'manual_commission_eur' => $this->filled('manual_commission_eur') ? str_replace(',', '.', (string) $this->input('manual_commission_eur')) : null,
            'brand_id' => $this->filled('brand_id') ? $this->integer('brand_id') : null,
            'product_line_id' => $this->filled('product_line_id') ? $this->integer('product_line_id') : null,
            'model_name' => preg_replace('/\s+/u', ' ', trim((string) $this->input('model_name'))) ?: null,
            'product_type_id' => $typeId,
            'category_ids' => $categoryId !== null ? [$categoryId] : [],
            'specs' => $specs,
            'spec_details' => $details,
            'spec_structured' => $structured,
        ]);
    }

    private function resolveCategoryId(?ProductType $type): ?int
    {
        if ($type === null) return null;
        if ($type->category_id !== null) return (int) $type->category_id;

        $slug = Str::slug((string) $type->slug ?: (string) $type->name);
        $categoryId = $slug !== '' ? Category::query()->where('status', 'active')->where('slug', $slug)->value('id') : null;
        if ($categoryId === null) {
            $categoryId = Category::query()->where('status', 'active')->whereRaw('LOWER(name) = ?', [mb_strtolower(trim((string) $type->name))])->value('id');
        }
        return $categoryId !== null ? (int) $categoryId : null;
    }
}
