<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\LaptopChargerCatalogProvisioner;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class LaptopChargersCatalogProvisioningTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dry_run_is_pure_and_apply_is_idempotent_with_existing_laptop_brands_only(): void
    {
        $now = now();
        $sourceCategory = DB::table('categories')->insertGetId([
            'name' => 'Test laptopovi', 'slug' => 'qa-source-laptop-charger', 'status' => 'active',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $sourceType = DB::table('product_types')->insertGetId([
            'name' => 'Laptopovi', 'slug' => 'qa-source-laptop-charger', 'category_id' => $sourceCategory,
            'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $powerId = DB::table('specification_fields')->where('slug', 'snaga-napajanja')->value('id');
        if ($powerId === null) {
            $powerId = DB::table('specification_fields')->insertGetId([
                'name' => 'Snaga napajanja', 'slug' => 'snaga-napajanja', 'unit' => 'W',
                'data_type' => 'integer', 'filter_type' => 'range', 'status' => 'active',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $brandIds = [];
        foreach (['Dell', 'HP', 'Lenovo', 'Acer', 'Unrelated vendor'] as $brand) {
            $id = DB::table('brands')->insertGetId([
                'name' => $brand,
                'slug' => 'qa-charger-'.strtolower(str_replace(' ', '-', $brand)),
                'status' => 'active', 'created_at' => $now, 'updated_at' => $now,
            ]);
            $brandIds[$brand] = $id;
            if ($brand !== 'Unrelated vendor') {
                DB::table('brand_product_type')->insert(['brand_id' => $id, 'product_type_id' => $sourceType]);
            }
        }

        $service = app(LaptopChargerCatalogProvisioner::class);
        $before = DB::table('product_types')->count();
        $preview = $service->preview();
        self::assertSame('dry-run', $preview['mode']);
        self::assertSame(4, $preview['brand_count']);
        self::assertSame(13, $preview['connector_count']);
        self::assertSame($before, DB::table('product_types')->count());

        $result = $service->apply();
        self::assertSame('apply', $result['mode']);
        self::assertSame(4, $result['brand_count']);
        $charger = DB::table('product_types')->where('slug', 'punjaci-za-laptopove')->first();
        self::assertNotNull($charger);
        self::assertSame(100, (int) $charger->minimum_completeness_percent);
        self::assertSame(['brand', 'line'], json_decode((string) $charger->required_core_fields_json, true));
        self::assertSame('Punjači za laptopove', DB::table('categories')->where('id', $charger->category_id)->value('name'));
        self::assertSame(4, DB::table('brand_product_type')->where('product_type_id', $charger->id)->count());
        self::assertFalse(DB::table('brand_product_type')->where('brand_id', $brandIds['Unrelated vendor'])->where('product_type_id', $charger->id)->exists());
        self::assertTrue(DB::table('product_type_fields')->where('product_type_id', $charger->id)->where('field_id', $powerId)->where('is_required', true)->exists());

        $lines = DB::table('product_lines as pl')
            ->join('product_line_product_type as link', 'pl.id', '=', 'link.product_line_id')
            ->where('pl.brand_id', $brandIds['Dell'])
            ->where('link.product_type_id', $charger->id)
            ->pluck('pl.name')->all();
        self::assertEqualsCanonicalizing(['Debela igla (7,4 × 5,0 mm)', 'Tanka igla (4,5 × 3,0 mm)', 'USB-C PD'], $lines);

        $beforeRepeat = [
            DB::table('categories')->count(), DB::table('product_types')->count(),
            DB::table('brand_product_type')->count(), DB::table('product_lines')->count(),
            DB::table('product_line_product_type')->count(), DB::table('product_type_fields')->count(),
        ];
        $service->apply();
        self::assertSame($beforeRepeat, [
            DB::table('categories')->count(), DB::table('product_types')->count(),
            DB::table('brand_product_type')->count(), DB::table('product_lines')->count(),
            DB::table('product_line_product_type')->count(), DB::table('product_type_fields')->count(),
        ]);
    }
}
