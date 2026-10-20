<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\BrandLineCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Tests\TestCase;

final class LaptopChargerCatalogTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = '/database/migrations/2026_10_20_154800_add_laptop_charger_catalog.php';

    public function test_existing_laptop_brands_get_only_their_connectors_and_power_field_without_variants(): void
    {
        $laptopTypeId = $this->createLaptopType();
        $dell = $this->brand('Dell', 'dell');
        $hp = $this->brand('HP', 'hp');
        $lenovo = $this->brand('Lenovo', 'lenovo');
        $unrelated = $this->brand('Asus', 'asus');
        foreach ([$dell, $hp, $lenovo] as $brandId) {
            DB::table('brand_product_type')->insert(['brand_id' => $brandId, 'product_type_id' => $laptopTypeId]);
        }
        // Asus exists globally, but this database does NOT offer Asus laptops.
        $existingBrands = DB::table('brands')->count();

        $this->installCatalog();
        $this->installCatalog();

        $type = DB::table('product_types')->where('slug', 'punjaci-za-laptopove')->first();
        $this->assertNotNull($type);
        self::assertSame('Punjači za laptopove', $type->name);
        self::assertSame(1, DB::table('product_types')->where('slug', 'punjaci-za-laptopove')->count());
        $category = DB::table('categories')->where('slug', 'punjaci-za-laptopove')->first();
        $this->assertNotNull($category);
        self::assertSame((int) $category->id, (int) $type->category_id);
        self::assertSame(1, DB::table('categories')->where('slug', 'punjaci-za-laptopove')->count());
        self::assertSame($existingBrands, DB::table('brands')->count());

        $linked = DB::table('brand_product_type')->where('product_type_id', $type->id)->pluck('brand_id')->map('intval')->all();
        sort($linked);
        $expected = [$dell, $hp, $lenovo];
        sort($expected);
        self::assertSame($expected, $linked);
        self::assertNotContains($unrelated, $linked);

        self::assertSame([
            'Debela igla (7,4 × 5,0 mm)',
            'Tanka igla (4,5 × 3,0 mm)',
            'Type-C (USB-C PD)',
        ], $this->linesForBrand((int) $type->id, $dell));
        self::assertSame([
            'Debela igla (7,4 × 5,0 mm)',
            'Plava igla (4,5 × 3,0 mm)',
            'Type-C (USB-C PD)',
        ], $this->linesForBrand((int) $type->id, $hp));
        self::assertSame([
            'Kocka / Slim Tip (11 × 4,5 mm)',
            'Tanka igla (4,0 × 1,7 mm)',
            'Okrugli (7,9 × 5,5 mm)',
            'Type-C (USB-C PD)',
        ], $this->linesForBrand((int) $type->id, $lenovo));
        self::assertSame([], $this->linesForBrand((int) $type->id, $unrelated));

        $power = DB::table('specification_fields')->where('slug', 'snaga-napajanja')->first();
        $this->assertNotNull($power);
        self::assertContains($power->data_type, ['integer', 'decimal']);
        self::assertSame('W', $power->unit);
        $assignment = DB::table('product_type_fields')->where('product_type_id', $type->id)->where('field_id', $power->id)->first();
        $this->assertNotNull($assignment);
        self::assertSame(1, (int) $assignment->is_required);
        self::assertSame(1, (int) $assignment->show_in_summary);
        self::assertSame(1, DB::table('product_type_fields')->where('product_type_id', $type->id)->count());
        self::assertFalse(DB::getSchemaBuilder()->hasTable('product_variant_values'));
    }

    public function test_preserves_existing_manual_brand_lines_and_data_when_reapplied(): void
    {
        $laptopTypeId = $this->createLaptopType();
        $dell = $this->brand('Dell', 'dell');
        DB::table('brand_product_type')->insert(['brand_id' => $dell, 'product_type_id' => $laptopTypeId]);
        $lineId = DB::table('product_lines')->insertGetId([
            'brand_id' => $dell,
            'name' => 'Debela igla (7,4 × 5,0 mm)',
            'slug' => 'dell-debela-igla-748',
            'status' => 'active',
        ]);
        $this->installCatalog();
        $this->installCatalog();
        $typeId = (int) DB::table('product_types')->where('slug', 'punjaci-za-laptopove')->value('id');
        self::assertSame($lineId, (int) DB::table('product_lines')->where('brand_id', $dell)->where('name', 'Debela igla (7,4 × 5,0 mm)')->value('id'));
        self::assertSame(3, count($this->linesForBrand($typeId, $dell)));
        self::assertSame(3, DB::table('product_line_product_type')->where('product_type_id', $typeId)->count());
    }

    public function test_line_enrichment_never_mistakes_charger_catalog_for_laptop_models(): void
    {
        $service = app(BrandLineCatalogService::class);
        $method = new ReflectionMethod(BrandLineCatalogService::class, 'typeGroup');
        self::assertNull($method->invoke($service, 'Punjači za laptopove', 'Punjači za laptopove'));
        self::assertSame('laptop', $method->invoke($service, 'Laptopovi', 'Laptopovi'));
    }

    private function installCatalog(): void
    {
        $migration = require base_path().self::MIGRATION;
        $migration->up();
    }

    private function createLaptopType(): int
    {
        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Laptopovi test',
            'slug' => 'laptopovi-test-probni',
            'status' => 'active',
        ]);
        return (int) DB::table('product_types')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Laptopovi',
            'slug' => 'laptopovi-test-probni',
            'status' => 'active',
        ]);
    }

    private function brand(string $name, string $slug): int
    {
        return (int) DB::table('brands')->insertGetId(['name' => $name, 'slug' => $slug, 'status' => 'active']);
    }

    /** @return list<string> */
    private function linesForBrand(int $typeId, int $brandId): array
    {
        return DB::table('product_lines as lines')
            ->join('product_line_product_type as pivot', 'pivot.product_line_id', '=', 'lines.id')
            ->where('pivot.product_type_id', $typeId)
            ->where('lines.brand_id', $brandId)
            ->orderBy('lines.sort_order')->orderBy('lines.id')
            ->pluck('lines.name')
            ->all();
    }
}
