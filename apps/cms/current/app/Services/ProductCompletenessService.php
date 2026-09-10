<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\ProductType;

final class ProductCompletenessService
{
    public function __construct(private readonly ProductTemplateService $templates) {}

    /** @return array{percent:int,missing:list<string>,status_changed:bool} */
    public function recalculate(Product $product, bool $enforceMinimum = true): array
    {
        $product->load([
            'type.fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order'),
            'specificationValues',
            'categories',
        ]);
        $specs = [];
        $details = [];
        foreach ($product->specificationValues as $value) {
            $specs[(int) $value->field_id] = $value->value_text
                ?? $value->value_number
                ?? ($value->value_boolean === null ? null : (int) $value->value_boolean);
            $details[(int) $value->field_id] = $value->value_detail;
        }

        $result = $this->templates->completeness(
            $product->type,
            $product->toArray() + ['category_ids' => $product->categories->pluck('id')->all()],
            $specs,
            $details,
        );

        $updates = ['completeness_percent' => $result['percent']];
        $statusChanged = false;
        $minimum = max(0, min(100, (int) ($product->type?->minimum_completeness_percent ?? 0)));
        $hasValidActivePrice = is_numeric($product->price_amount) && (float) $product->price_amount > 0;
        if ($enforceMinimum && $product->status === 'active' && ($result['percent'] < $minimum || !$hasValidActivePrice)) {
            $updates['status'] = 'draft';
            $statusChanged = true;
        }
        $product->update($updates);

        return [
            'percent' => (int) $result['percent'],
            'missing' => array_values((array) $result['missing']),
            'status_changed' => $statusChanged,
        ];
    }

    /** @return array{examined:int,downgraded:int} */
    public function recalculateType(ProductType $type, bool $enforceMinimum = true): array
    {
        $examined = 0;
        $downgraded = 0;
        Product::query()
            ->where('product_type_id', $type->id)
            ->orderBy('id')
            ->chunkById(100, function ($products) use (&$examined, &$downgraded, $enforceMinimum): void {
                foreach ($products as $product) {
                    $result = $this->recalculate($product, $enforceMinimum);
                    $examined++;
                    if ($result['status_changed']) $downgraded++;
                }
            });
        return ['examined' => $examined, 'downgraded' => $downgraded];
    }

    /** @return array{examined:int,downgraded:int} */
    public function recalculateAll(bool $enforceMinimum = true): array
    {
        $examined = 0;
        $downgraded = 0;
        Product::query()->orderBy('id')->chunkById(100, function ($products) use (&$examined, &$downgraded, $enforceMinimum): void {
            foreach ($products as $product) {
                $result = $this->recalculate($product, $enforceMinimum);
                $examined++;
                if ($result['status_changed']) $downgraded++;
            }
        });
        return ['examined' => $examined, 'downgraded' => $downgraded];
    }
}
