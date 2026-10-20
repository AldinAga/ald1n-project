<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Non-destructive, opt-in reference-data provisioning for laptop chargers.
 * No migration invokes this class. --apply is a separate authorized operator gate.
 */
final class LaptopChargerCatalogProvisioner
{
    private const SLUG = 'punjaci-za-laptopove';
    private const NAME = 'Punjači za laptopove';
    private const POWER_SLUG = 'snaga-napajanja';

    /** @return array<string,mixed> */
    public function preview(): array
    {
        return $this->process(false);
    }

    /** @return array<string,mixed> */
    public function apply(): array
    {
        return DB::transaction(fn (): array => $this->process(true), 3);
    }

    /** @return array<string,mixed> */
    private function process(bool $apply): array
    {
        foreach ([
            'categories', 'product_types', 'brands', 'brand_product_type',
            'product_lines', 'product_line_product_type', 'specification_fields',
            'product_type_fields', 'user_group_categories',
        ] as $table) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException('HOLD_CHARGER_CATALOG_TABLE_MISSING: '.$table);
            }
        }
        foreach (['category_id', 'minimum_completeness_percent', 'required_core_fields_json'] as $column) {
            if (!Schema::hasColumn('product_types', $column)) {
                throw new RuntimeException('HOLD_CHARGER_TYPE_COLUMN_MISSING: '.$column);
            }
        }

        $power = DB::table('specification_fields')->where('slug', self::POWER_SLUG)->first();
        if ($power === null || $power->status !== 'active'
            || !in_array((string) $power->data_type, ['integer', 'decimal'], true)
            || Str::lower(trim((string) $power->unit)) !== 'w') {
            throw new RuntimeException('HOLD_EXISTING_SNAGA_NAPAJANJA_W_FIELD_MISSING_OR_INVALID');
        }

        $laptopTypes = DB::table('product_types')->where('status', 'active')->get(['id', 'name', 'slug', 'category_id'])
            ->filter(static function ($type): bool {
                $key = Str::lower(Str::ascii((string) $type->name.' '.(string) $type->slug));
                return (str_contains($key, 'laptop') || str_contains($key, 'notebook') || str_contains($key, 'prenosni'))
                    && !str_contains($key, 'punjac') && !str_contains($key, 'charger');
            });
        if ($laptopTypes->isEmpty()) throw new RuntimeException('HOLD_NO_EXISTING_LAPTOP_PRODUCT_TYPE');
        $laptopIds = $laptopTypes->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $laptopCategoryIds = $laptopTypes->pluck('category_id')->filter()->unique()->values()->all();

        $brands = DB::table('brands as b')
            ->join('brand_product_type as bpt', 'bpt.brand_id', '=', 'b.id')
            ->whereIn('bpt.product_type_id', $laptopIds)
            ->where('b.status', 'active')
            ->distinct()
            ->orderBy('b.id')
            ->get(['b.id', 'b.name'])
            ->filter(static fn ($brand): bool => BrandLineCatalogService::laptopChargerLines((string) $brand->name) !== []);
        if ($brands->isEmpty()) throw new RuntimeException('HOLD_NO_CURATED_EXISTING_LAPTOP_BRANDS');

        $category = DB::table('categories')->where('slug', self::SLUG)->first();
        if ($category !== null && (string) $category->status !== 'active') {
            throw new RuntimeException('HOLD_EXISTING_CHARGER_CATEGORY_INACTIVE');
        }
        $type = DB::table('product_types')->where('slug', self::SLUG)->first();
        if ($type !== null && ($type->status !== 'active'
            || ($type->category_id !== null && ($category === null || (int) $type->category_id !== (int) $category->id)))) {
            throw new RuntimeException('HOLD_EXISTING_CHARGER_TYPE_CONFLICT');
        }

        $counts = [
            'mode' => $apply ? 'apply' : 'dry-run',
            'type_slug' => self::SLUG,
            'power_field_slug' => self::POWER_SLUG,
            'brand_count' => $brands->count(),
            'connector_count' => $brands->sum(static fn ($brand): int => count(BrandLineCatalogService::laptopChargerLines((string) $brand->name))),
            'category_exists' => $category !== null,
            'type_exists' => $type !== null,
            'brands' => $brands->map(static fn ($brand): string => (string) $brand->name)->values()->all(),
        ];
        if (!$apply) return $counts;

        $now = now();
        if ($category === null) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => self::NAME, 'slug' => self::SLUG,
                'description' => 'Laptop punjači, prema brendu, vrsti konektora i snazi u W.',
                'status' => 'active', 'sort_order' => 300, 'created_at' => $now, 'updated_at' => $now,
            ]);
        } else {
            $categoryId = (int) $category->id;
        }

        if ($type === null) {
            $typeId = DB::table('product_types')->insertGetId([
                'name' => self::NAME, 'slug' => self::SLUG, 'category_id' => $categoryId,
                'description' => 'Punjači za laptopove: postojeći brend, tip konektora i snaga napajanja (W).',
                'status' => 'active', 'sort_order' => 300, 'default_product_status' => 'draft',
                'auto_name_enabled' => false,
                'minimum_completeness_percent' => 100,
                'required_core_fields_json' => json_encode(['brand', 'line'], JSON_THROW_ON_ERROR),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        } else {
            $typeId = (int) $type->id;
            $required = json_decode((string) $type->required_core_fields_json, true);
            if ((int) $type->minimum_completeness_percent !== 100
                || !is_array($required) || !in_array('brand', $required, true) || !in_array('line', $required, true)) {
                throw new RuntimeException('HOLD_EXISTING_CHARGER_TYPE_COMPLETENESS_CONFLICT');
            }
            if ($type->category_id === null) {
                DB::table('product_types')->where('id', $typeId)->update(['category_id' => $categoryId, 'updated_at' => $now]);
            }
        }

        // Reuse the DESKTOP computer power specification; no duplicate field.
        $powerLink = [
            'product_type_id' => $typeId, 'field_id' => (int) $power->id,
            'is_required' => true, 'is_filterable' => true, 'show_in_summary' => true,
            'sort_order' => 30, 'created_at' => $now,
        ];
        foreach (['completeness_weight' => 3, 'include_in_name' => false] as $column => $value) {
            if (Schema::hasColumn('product_type_fields', $column)) $powerLink[$column] = $value;
        }
        DB::table('product_type_fields')->insertOrIgnore($powerLink);
        $existingPower = DB::table('product_type_fields')
            ->where('product_type_id', $typeId)->where('field_id', (int) $power->id)->first();
        if ($existingPower === null || !(bool) $existingPower->is_required) {
            throw new RuntimeException('HOLD_CHARGER_WATT_FIELD_NOT_REQUIRED');
        }

        foreach ($brands as $brand) {
            $brandId = (int) $brand->id;
            DB::table('brand_product_type')->insertOrIgnore(['brand_id' => $brandId, 'product_type_id' => $typeId]);
            foreach (BrandLineCatalogService::laptopChargerLines((string) $brand->name) as $lineName) {
                $line = DB::table('product_lines')->where('brand_id', $brandId)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($lineName))])->first();
                if ($line !== null && (string) $line->status !== 'active') {
                    throw new RuntimeException('HOLD_EXISTING_INACTIVE_CHARGER_CONNECTOR: '.$brand->name.' '.$lineName);
                }
                if ($line === null) {
                    $slug = Str::slug($brand->name.' '.$lineName) ?: 'charger-tip';
                    $base = $slug;
                    $suffix = 2;
                    while (DB::table('product_lines')->where('brand_id', $brandId)->where('slug', $slug)->exists()) {
                        $slug = $base.'-'.$suffix++;
                    }
                    $lineId = DB::table('product_lines')->insertGetId([
                        'brand_id' => $brandId, 'name' => $lineName, 'slug' => $slug,
                        'status' => 'active',
                        'sort_order' => ((int) DB::table('product_lines')->where('brand_id', $brandId)->max('sort_order')) + 10,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                } else {
                    $lineId = (int) $line->id;
                }
                DB::table('product_line_product_type')->insertOrIgnore([
                    'product_line_id' => $lineId, 'product_type_id' => $typeId,
                ]);
            }
        }

        // Only groups already allowed to see Laptop categories can see the new category.
        if ($laptopCategoryIds !== []) {
            $groupIds = DB::table('user_group_categories')->whereIn('category_id', $laptopCategoryIds)
                ->distinct()->pluck('group_id');
            foreach ($groupIds as $groupId) {
                DB::table('user_group_categories')->insertOrIgnore([
                    'group_id' => (int) $groupId, 'category_id' => $categoryId, 'created_at' => $now,
                ]);
            }
        }
        app(CatalogReferenceCache::class)->forget();

        $counts['category_id'] = $categoryId;
        $counts['product_type_id'] = $typeId;
        return $counts;
    }
}
