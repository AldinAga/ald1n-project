<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

// MOBILE_V1_0_ADMIN_PURCHASE_COST_PARITY_BATCH19_V4
final class ProductPurchaseCostService
{
    public function __construct(private readonly AuditLogger $audit)
    {
    }

    /**
     * @return array{
     *   products: Collection<int, Product>,
     *   showAll: bool,
     *   missingTotal: int,
     *   missingPositiveStock: int
     * }
     */
    public function overview(bool $showAll): array
    {
        $base = Product::query()->whereNull('deleted_at');
        $missingTotal = (clone $base)->where(static function ($query): void {
            $query->whereNull('purchase_price_rsd')->orWhere('purchase_price_rsd', '<=', 0);
        })->count();
        $missingPositiveStock = (clone $base)
            ->where('stock_quantity', '>', 0)
            ->where(static function ($query): void {
                $query->whereNull('purchase_price_rsd')->orWhere('purchase_price_rsd', '<=', 0);
            })
            ->count();

        $products = clone $base;
        if (!$showAll) {
            $products->where(static function ($query): void {
                $query->whereNull('purchase_price_rsd')->orWhere('purchase_price_rsd', '<=', 0);
            });
        }

        return [
            'products' => $products->orderBy('name')->orderBy('sku')->get([
                'id', 'name', 'sku', 'stock_quantity', 'price_amount', 'price_currency', 'purchase_price_rsd',
            ]),
            'showAll' => $showAll,
            'missingTotal' => (int) $missingTotal,
            'missingPositiveStock' => (int) $missingPositiveStock,
        ];
    }

    /** @param array<int|string,mixed> $raw */
    public function update(array $raw, User $actor, string $source): int
    {
        $normalized = [];
        foreach ($raw as $id => $value) {
            $text = trim((string) $value);
            $normalized[(string) $id] = $text === '' ? null : str_replace(',', '.', $text);
        }

        $data = Validator::make(['costs' => $normalized], [
            'costs' => ['required', 'array', 'max:500'],
            'costs.*' => ['nullable', 'numeric', 'min:0.01', 'max:9999999999.99'],
        ])->validate();

        $costs = [];
        foreach ((array) $data['costs'] as $id => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $productId = (int) $id;
            if ($productId > 0) {
                $costs[$productId] = round((float) $value, 2);
            }
        }

        if ($costs === []) {
            throw ValidationException::withMessages([
                'costs' => 'Unesite najmanje jednu nabavnu cenu.',
            ]);
        }

        return DB::transaction(function () use ($costs, $actor, $source): int {
            $products = Product::query()
                ->whereNull('deleted_at')
                ->whereIn('id', array_keys($costs))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== count($costs)) {
                throw ValidationException::withMessages([
                    'costs' => 'Jedan ili više artikala više nisu dostupni. Osvežite podatke.',
                ]);
            }

            $changed = 0;
            foreach ($costs as $productId => $cost) {
                /** @var Product $product */
                $product = $products->get($productId);
                $before = $product->purchase_price_rsd === null
                    ? null
                    : round((float) $product->purchase_price_rsd, 2);

                if ($before !== null && abs($before - $cost) < 0.005) {
                    continue;
                }

                $product->update([
                    'purchase_price_rsd' => $cost,
                    'updated_by' => $actor->getAuthIdentifier(),
                    'locally_modified_at' => now(),
                ]);

                $this->audit->log(
                    'product.purchase_cost.updated',
                    'Ažurirana nabavna cena artikla '.$product->sku,
                    $product,
                    ['purchase_price_rsd' => $before],
                    ['purchase_price_rsd' => $cost],
                    ['source' => $source],
                    $actor,
                );
                $changed++;
            }

            return $changed;
        }, 3);
    }
}
