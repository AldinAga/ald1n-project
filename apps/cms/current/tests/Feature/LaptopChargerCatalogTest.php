<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\BrandLineCatalogService;
use App\Services\LaptopChargerCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

final class LaptopChargerCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_is_read_only_and_reuses_only_existing_laptop_brands(): void
    {
        $laptop = $this->laptopType();
        $this->brand('Dell', $laptop);
        $this->brand('Lenovo', $laptop);
        $this->brand('HP', null); // Existing, but not a laptop-enabled brand.
        $this->brand('UnrelatedBrand', $laptop);
        $this->powerField();

        $report = app(LaptopChargerCatalogService::class)->preview();

        self::assertSame('preview', $report['mode']);
        self::assertSame(2, $report['eligible_brands']);
        self::assertSame([], $report['blockers']);
        self::assertSame(0, DB::table('product_types')->where('slug', 'punjaci-za-laptopove')->count());
        self::assertSame(0, DB::table('categories')->where('slug', 'punjaci-za-laptopove')->count());
        self::assertSame(0, DB::table('product_lines')->count());
    }

    public function test_apply_scopes_connectors_to_brand_and_charger_type_and_reuses_existing_watt_field(): void
    {
        $laptop = $this->laptopType();
        $dell = $this->brand('Dell', $laptop);
        $lenovo = $this->brand('Lenovo', $laptop);
        $asus = $this->brand('Asus', $laptop);
        $hpNotLinked = $this->brand('HP', null);
        $unknown = $this->brand('Generic', $laptop);
        $power = $this->powerField();

        $service = app(LaptopChargerCatalogService::class);
        $first = $service->apply();
        self::assertSame([], $first['blockers']);
        self::assertSame(3, $first['eligible_brands']);

        $type = DB::table('product_types')->where('slug', 'punjaci-za-laptopove')->first();
        $category = DB::table('categories')->where('slug', 'punjaci-za-laptopove')->first();
        self::assertNotNull($type);
        self::assertNotNull($category);
        self::assertSame('Punjači za laptopove', $type->name);
        self::assertSame((int) $category->id, (int) $type->category_id);
        self::assertSame(1, DB::table('product_type_fields')
            ->where('product_type_id', $type->id)->where('field_id', $power)->where('is_required', 1)->count());

        foreach ([$dell, $lenovo, $asus] as $brandId) {
            self::assertSame(1, DB::table('brand_product_type')
                ->where('brand_id', $brandId)->where('product_type_id', $type->id)->count());
        }
        foreach ([$hpNotLinked, $unknown] as $brandId) {
            self::assertSame(0, DB::table('brand_product_type')
                ->where('brand_id', $brandId)->where('product_type_id', $type->id)->count());
        }

        $dellOptions = $this->connectorNames($dell, (int) $type->id);
        self::assertEqualsCanonicalizing([
            'Debela igla (7,4 × 5,0 mm)',
            'Tanka igla (4,5 × 3,0 mm)',
            'USB-C PD',
        ], $dellOptions);
        $lenovoOptions = $this->connectorNames($lenovo, (int) $type->id);
        self::assertEqualsCanonicalizing([
            'Kocka / Slim Tip (11 × 4,5 mm)',
            'Tanka igla (4,0 × 1,7 mm)',
            'Okrugli veliki (7,9 × 5,5 mm)',
            'USB-C PD',
        ], $lenovoOptions);
        self::assertEqualsCanonicalizing([
            'Okrugli (4,0 × 1,35 mm)',
            'Okrugli (5,5 × 2,5 mm)',
            'USB-C PD',
        ], $this->connectorNames($asus, (int) $type->id));

        $before = [
            DB::table('product_types')->count(),
            DB::table('categories')->count(),
            DB::table('brand_product_type')->count(),
            DB::table('product_lines')->count(),
            DB::table('product_line_product_type')->count(),
            DB::table('product_type_fields')->count(),
        ];
        $service->apply();
        $after = [
            DB::table('product_types')->count(),
            DB::table('categories')->count(),
            DB::table('brand_product_type')->count(),
            DB::table('product_lines')->count(),
            DB::table('product_line_product_type')->count(),
            DB::table('product_type_fields')->count(),
        ];
        self::assertSame($before, $after, 'Ponovna primena ne sme kreirati duplikate.');
        self::assertSame(1, DB::table('specification_fields')->where('slug', 'snaga-napajanja')->count());
        self::assertSame(0, DB::table('product_line_product_type')->where('product_type_id', $laptop)->count(),
            'Tip konektora ne sme biti vezan za tip Laptopovi.');
    }

    public function test_missing_existing_watts_field_holds_before_any_write(): void
    {
        $laptop = $this->laptopType();
        $this->brand('Dell', $laptop);

        $preview = app(LaptopChargerCatalogService::class)->preview();
        self::assertContains('MISSING_ACTIVE_EXISTING_SNAGA_NAPAJANJA_W_FIELD', $preview['blockers']);

        $this->expectException(RuntimeException::class);
        try {
            app(LaptopChargerCatalogService::class)->apply();
        } finally {
            self::assertSame(0, DB::table('product_types')->where('slug', 'punjaci-za-laptopove')->count());
        }
    }

    public function test_existing_laptop_model_line_enricher_does_not_target_charger_type(): void
    {
        $service = new BrandLineCatalogService();
        $method = new \ReflectionMethod($service, 'typeGroup');
        self::assertSame('laptop-charger', $method->invoke($service, 'Punjači za laptopove', 'Punjači za laptopove'));
        self::assertSame('laptop', $method->invoke($service, 'Laptopovi', 'Laptopovi'));
    }

    public function test_artisan_requires_explicit_confirmation_and_preview_never_writes(): void
    {
        $laptop = $this->laptopType();
        $this->brand('Dell', $laptop);
        $this->powerField();

        $this->artisan('app:laptop-charger-catalog')->assertExitCode(0);
        $this->artisan('app:laptop-charger-catalog --apply')->assertExitCode(2);
        self::assertSame(0, DB::table('product_types')->where('slug', 'punjaci-za-laptopove')->count());
        $this->artisan('app:laptop-charger-catalog --apply --ack=ADD-LAPTOP-CHARGERS')->assertExitCode(0);
        self::assertSame(1, DB::table('product_types')->where('slug', 'punjaci-za-laptopove')->count());
    }

    /** @return list<string> */
    private function connectorNames(int $brandId, int $typeId): array
    {
        return DB::table('product_lines as pl')
            ->join('product_line_product_type as plt', 'plt.product_line_id', '=', 'pl.id')
            ->where('pl.brand_id', $brandId)->where('plt.product_type_id', $typeId)
            ->orderBy('pl.sort_order')->pluck('pl.name')->all();
    }

    private function laptopType(): int
    {
        $category = DB::table('categories')->insertGetId([
            'name' => 'Laptopovi', 'slug' => 'laptopovi', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return DB::table('product_types')->insertGetId([
            'name' => 'Laptopovi', 'slug' => 'laptopovi', 'category_id' => $category,
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function brand(string $name, ?int $laptopTypeId): int
    {
        $brandId = DB::table('brands')->insertGetId([
            'name' => $name, 'slug' => strtolower($name), 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($laptopTypeId !== null) {
            DB::table('brand_product_type')->insert(['brand_id' => $brandId, 'product_type_id' => $laptopTypeId]);
        }
        return (int) $brandId;
    }

    private function powerField(): int
    {
        return (int) DB::table('specification_fields')->insertGetId([
            'name' => 'Snaga napajanja', 'slug' => 'snaga-napajanja',
            'data_type' => 'integer', 'filter_type' => 'range', 'unit' => 'W', 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
