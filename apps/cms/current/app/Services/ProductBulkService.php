<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\SpecificationField;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ProductBulkService
{
    public function __construct(
        private readonly ProductTemplateService $templates,
        private readonly ProductCompletenessService $completeness,
        private readonly ProductAdminService $products,
        private readonly AuditLogger $audit,
        private readonly CatalogAccessService $catalogAccess,
    ) {}

    /** @param array<string,mixed> $data @return array{count:int,products:list<array{id:int,sku:string,name:string}>,summary:list<string>} */
    public function preview(array $data, User $user): array
    {
        $this->assertHasChanges($data);
        $query = Product::query()->whereIn('id', $this->ids($data));
        $this->catalogAccess->applyManageable($query, $user);
        $products = $query->orderBy('id')->get(['id', 'sku', 'name']);
        if ($products->count() !== count($this->ids($data))) {
            throw ValidationException::withMessages(['product_ids' => 'Bulk izmena je dozvoljena samo nad artiklima koje ste kreirali.']);
        }
        return [
            'count' => $products->count(),
            'products' => $products->take(100)->map(fn (Product $product): array => ['id' => $product->id, 'sku' => $product->sku, 'name' => $product->name])->all(),
            'summary' => $this->summary($data),
        ];
    }

    /** @param array<string,mixed> $data */
    public function execute(array $data, User $user): int
    {
        $this->assertHasChanges($data);
        $ids = $this->ids($data);
        $updated = 0;
        foreach (array_chunk($ids, 50) as $chunk) {
            $query = Product::query()->whereIn('id', $chunk);
            $this->catalogAccess->applyManageable($query, $user);
            $products = $query->orderBy('id')->get();
            if ($products->count() !== count($chunk)) {
                throw ValidationException::withMessages(['product_ids' => 'Bulk izmena je dozvoljena samo nad artiklima koje ste kreirali.']);
            }
            foreach ($products as $product) {
                DB::transaction(function () use ($product, $data, $user, &$updated): void {
                    $locked = Product::query()->with(['categories', 'specificationValues', 'type.fields'])->lockForUpdate()->findOrFail($product->id);
                    if (!$this->catalogAccess->canManage($locked, $user)) {
                        throw ValidationException::withMessages(['product_ids' => 'Nemate dozvolu za izmenu jednog od izabranih artikala.']);
                    }
                    $before = $this->snapshot($locked);
                    $changes = [];

                    if (!empty($data['apply_status'])) $changes['status'] = (string) $data['status'];
                    if (!empty($data['apply_brand'])) {
                        $changes['brand_id'] = !empty($data['brand_id']) ? (int) $data['brand_id'] : null;
                        if (empty($data['apply_line']) && $locked->product_line_id !== null) {
                            $currentLineBrand = ProductLine::query()->whereKey($locked->product_line_id)->value('brand_id');
                            if ($changes['brand_id'] === null || (int) $currentLineBrand !== (int) $changes['brand_id']) {
                                $changes['product_line_id'] = null;
                            }
                        }
                    }
                    if (!empty($data['apply_line'])) {
                        $lineId = !empty($data['product_line_id']) ? (int) $data['product_line_id'] : null;
                        if ($lineId !== null) {
                            $lineBrand = ProductLine::query()->whereKey($lineId)->value('brand_id');
                            $effectiveBrand = $changes['brand_id'] ?? $locked->brand_id;
                            if ($lineBrand !== null && (int) $lineBrand !== (int) $effectiveBrand) {
                                throw ValidationException::withMessages(['product_line_id' => 'Linija ne pripada izabranom brendu.']);
                            }
                        }
                        $changes['product_line_id'] = $lineId;
                    }
                    if (!empty($data['apply_type'])) $changes['product_type_id'] = !empty($data['product_type_id']) ? (int) $data['product_type_id'] : null;

                    if (!empty($data['price_action'])) {
                        $value = (float) ($data['price_value'] ?? 0);
                        $current = (float) $locked->price_amount;
                        $changes['price_amount'] = match ($data['price_action']) {
                            'set' => max(0, $value),
                            'increase_percent' => max(0, round($current * (1 + $value / 100), 2)),
                            'decrease_percent' => max(0, round($current * (1 - $value / 100), 2)),
                            'increase_fixed' => max(0, round($current + $value, 2)),
                            'decrease_fixed' => max(0, round($current - $value, 2)),
                            default => $current,
                        };
                    }

                    if ($changes !== []) $locked->update($changes + ['updated_by' => $user->id, 'locally_modified_at' => now()]);

                    if (!empty($data['apply_type'])) {
                        $this->syncCategoryForType($locked);
                        $this->normalizeSpecificationsForType($locked);
                    }
                    $this->applySpecification($locked, $data);
                    $this->completeness->recalculate($locked);
                    if (!empty($data['regenerate_names'])) $this->products->regenerateName($locked, $user);

                    $locked->refresh();
                    $this->audit->log('product.bulk.updated', 'Bulk izmenjen artikal '.$locked->sku, $locked, $before, $this->snapshot($locked), ['bulk_count' => count($ids), 'summary' => $this->summary($data)]);
                    $updated++;
                }, 3);
            }
        }
        return $updated;
    }


    /** @param array<string,mixed> $data */
    private function assertHasChanges(array $data): void
    {
        $hasChanges = !empty($data['apply_status'])
            || !empty($data['apply_brand'])
            || !empty($data['apply_line'])
            || !empty($data['apply_type'])
            || !empty($data['price_action'])
            || !empty($data['specification_action'])
            || !empty($data['regenerate_names']);
        if (!$hasChanges) {
            throw ValidationException::withMessages(['mode' => 'Izaberi najmanje jednu promenu pre pregleda ili izvršenja.']);
        }
    }

    /** @param array<string,mixed> $data */
    private function applySpecification(Product $product, array $data): void
    {
        $fieldId = (int) ($data['specification_field_id'] ?? 0);
        $action = (string) ($data['specification_action'] ?? '');
        if ($fieldId <= 0 || $action === '') return;
        $field = SpecificationField::query()->find($fieldId);
        if ($field === null) return;
        if ($field->isDerivedStorageTotalField() || $field->isRepeatableStorageField()) {
            throw ValidationException::withMessages([
                'specification_field_id' => 'Diskovi i ukupan kapacitet menjaju se na formi konkretnog artikla, jer se zbir računa automatski.',
            ]);
        }
        $belongs = $product->product_type_id !== null && $field->productTypes()->whereKey($product->product_type_id)->exists();
        if (!$belongs) return;

        if ($action === 'clear') {
            DB::table('product_spec_values')->where('product_id', $product->id)->where('field_id', $fieldId)->delete();
            return;
        }
        $raw = $data['specification_value'] ?? null;
        if ($raw === null || $raw === '') return;
        $row = [
            'product_id' => $product->id,
            'field_id' => $fieldId,
            'value_text' => null,
            'value_detail' => null,
            'value_json' => null,
            'value_number' => null,
            'value_boolean' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (in_array($field->data_type, ['integer', 'decimal'], true)) {
            $normalized = str_replace(',', '.', trim((string) $raw));
            if ($field->requiresWholeGigabytes() && !preg_match('/^\d+$/', $normalized)) {
                throw ValidationException::withMessages(['specification_value' => 'Vrednost za „'.$field->name.'“ u GB mora biti ceo broj bez decimala.']);
            }
            $row['value_number'] = $field->requiresWholeGigabytes() ? (int) $normalized : (float) $normalized;
        } elseif ($field->data_type === 'boolean') $row['value_boolean'] = (bool) $raw;
        else {
            $row['value_text'] = mb_substr(trim((string) $raw), 0, 1000);
            if ($field->detail_input_enabled) {
                $detail = trim((string) ($data['specification_detail'] ?? ''));
                $row['value_detail'] = $detail !== '' ? mb_substr($detail, 0, 500) : null;
            }
        }
        DB::table('product_spec_values')->updateOrInsert(['product_id' => $product->id, 'field_id' => $fieldId], $row);
    }


    private function syncCategoryForType(Product $product): void
    {
        if ($product->product_type_id === null) {
            $product->categories()->sync([]);
            return;
        }

        $categoryId = ProductType::query()->whereKey($product->product_type_id)->value('category_id');
        $product->categories()->sync($categoryId !== null ? [(int) $categoryId] : []);
    }

    private function normalizeSpecificationsForType(Product $product): void
    {
        if ($product->product_type_id === null) {
            DB::table('product_spec_values')->where('product_id', $product->id)->delete();
            return;
        }
        $allowed = DB::table('product_type_fields')->where('product_type_id', $product->product_type_id)->pluck('field_id')->map(fn ($id) => (int) $id)->all();
        DB::table('product_spec_values')->where('product_id', $product->id)->when($allowed !== [], fn ($query) => $query->whereNotIn('field_id', $allowed), fn ($query) => $query)->delete();
        $type = ProductType::query()->with('fields')->find($product->product_type_id);
        $defaults = $this->templates->defaults($type);
        foreach ($defaults['specs'] as $fieldId => $value) {
            if (DB::table('product_spec_values')->where('product_id', $product->id)->where('field_id', $fieldId)->exists()) continue;
            $field = $type?->fields->firstWhere('id', $fieldId);
            if ($field === null) continue;
            $row = ['product_id' => $product->id, 'field_id' => $fieldId, 'value_text' => null, 'value_detail' => $defaults['details'][$fieldId] ?? null, 'value_json' => null, 'value_number' => null, 'value_boolean' => null, 'created_at' => now(), 'updated_at' => now()];
            if (in_array($field->data_type, ['integer', 'decimal'], true)) {
                $normalized = str_replace(',', '.', (string) $value);
                $row['value_number'] = $field->requiresWholeGigabytes() ? (int) $normalized : (float) $normalized;
            } elseif ($field->data_type === 'boolean') $row['value_boolean'] = (bool) $value;
            else $row['value_text'] = $value;
            DB::table('product_spec_values')->insert($row);
        }
    }

    /** @param array<string,mixed> $data @return list<int> */
    private function ids(array $data): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($data['product_ids'] ?? [])), fn (int $id): bool => $id > 0)));
        if ($ids === []) throw ValidationException::withMessages(['product_ids' => 'Izaberi najmanje jedan artikal.']);
        if (count($ids) > 500) throw ValidationException::withMessages(['product_ids' => 'Jednom operacijom može se izmeniti najviše 500 artikala.']);
        return $ids;
    }

    /** @param array<string,mixed> $data @return list<string> */
    private function summary(array $data): array
    {
        $summary = [];
        if (!empty($data['apply_status'])) $summary[] = 'Status: '.(string) $data['status'];
        if (!empty($data['apply_brand'])) $summary[] = 'Promena brenda';
        if (!empty($data['apply_line'])) $summary[] = 'Promena linije proizvoda';
        if (!empty($data['apply_type'])) $summary[] = 'Promena tipa i normalizacija specifikacija';
        if (!empty($data['price_action'])) $summary[] = 'Cena: '.(string) $data['price_action'].' '.(string) ($data['price_value'] ?? 0);
        if (!empty($data['specification_action'])) $summary[] = 'Specifikacija: '.(string) $data['specification_action'];
        if (!empty($data['regenerate_names'])) $summary[] = 'Regeneriši nazive prema šablonu';
        return $summary !== [] ? $summary : ['Nije izabrana nijedna promena.'];
    }

    /** @return array<string,mixed> */
    private function snapshot(Product $product): array
    {
        return $product->fresh()?->only(['id','sku','name','product_type_id','brand_id','product_line_id','price_amount','price_currency','status','completeness_percent']) ?? [];
    }
}
