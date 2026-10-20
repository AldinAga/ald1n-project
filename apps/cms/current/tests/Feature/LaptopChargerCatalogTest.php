<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductType;
use App\Models\SpecificationField;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class LaptopChargerCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_chargers_reuse_laptop_brands_and_existing_watt_field_without_duplicates(): void
    {
        $category = Category::query()->create(['name' => 'Laptopovi', 'slug' => 'laptopovi', 'status' => 'active']);
        $laptop = ProductType::query()->create(['name' => 'Laptopovi', 'slug' => 'laptopovi', 'status' => 'active', 'category_id' => $category->id]);
        $power = SpecificationField::query()->create([
            'name' => 'Snaga napajanja',
            'slug' => 'snaga-napajanja',
            'data_type' => 'integer',
            'filter_type' => 'range',
            'unit' => 'W',
            'status' => 'active',
        ]);
        $known = [];
        foreach (['Dell', 'HP', 'Lenovo', 'ASUS', 'Acer'] as $name) {
            $brand = Brand::query()->create(['name' => $name, 'slug' => strtolower($name), 'status' => 'active']);
            $known[] = (int) $brand->id;
            DB::table('brand_product_type')->insert(['brand_id' => $brand->id, 'product_type_id' => $laptop->id]);
        }
        $unrelated = Brand::query()->create(['name' => 'Micron', 'slug' => 'micron', 'status' => 'active']);

        $this->applyMigration();
        $this->applyMigration();

        $type = ProductType::query()->where('slug', 'punjaci-za-laptopove')->firstOrFail();
        $newCategory = Category::query()->where('slug', 'punjaci-za-laptopove')->firstOrFail();
        self::assertSame('Punjači za laptopove', $type->name);
        self::assertSame((int) $newCategory->id, (int) $type->category_id);
        self::assertSame(1, ProductType::query()->where('slug', 'punjaci-za-laptopove')->count());
        self::assertSame(1, Category::query()->where('slug', 'punjaci-za-laptopove')->count());
        self::assertSame(1, SpecificationField::query()->where('slug', 'snaga-napajanja')->count());

        $linkedBrands = DB::table('brand_product_type')->where('product_type_id', $type->id)->orderBy('brand_id')->pluck('brand_id')->map('intval')->all();
        self::assertSame($known, $linkedBrands);
        self::assertNotContains($unrelated->id, $linkedBrands);

        $connectorField = SpecificationField::query()->where('slug', 'tip-konektora-punjaca')->firstOrFail();
        self::assertSame('select', $connectorField->data_type);
        self::assertSame(2, DB::table('product_type_fields')->where('product_type_id', $type->id)->count());
        foreach ([$connectorField, $power] as $field) {
            $pivot = DB::table('product_type_fields')->where('product_type_id', $type->id)->where('field_id', $field->id)->first();
            self::assertNotNull($pivot);
            self::assertSame(1, (int) $pivot->is_required);
            self::assertSame(1, (int) $pivot->show_in_summary);
        }
        foreach ([
            'Dell — debela igla (7.4 × 5.0 mm, centar pin)',
            'Dell — tanka igla (4.5 × 3.0 mm, centar pin)',
            'Dell — USB-C PD',
            'HP — debela igla (7.4 × 5.0 mm, Smart Pin)',
            'HP — plava igla (4.5 × 3.0 mm, Smart Pin)',
            'HP — USB-C PD',
            'Lenovo — kocka / Slim Tip (11 × 4.5 mm)',
            'Lenovo — tanka igla (4.0 × 1.7 mm)',
            'Lenovo — okrugli (7.9 × 5.5 mm, centar pin)',
            'Lenovo — USB-C PD',
            'ASUS — okrugli (5.5 × 2.5 mm)',
            'Acer — okrugli (5.5 × 1.7 mm)',
        ] as $label) {
            $this->assertDatabaseHas('specification_options', ['field_id' => $connectorField->id, 'label' => $label, 'status' => 'active']);
        }
        self::assertSame(1, DB::table('specification_options')->where('field_id', $connectorField->id)->where('label', 'Dell — USB-C PD')->count());
        self::assertSame(0, DB::table('products')->count());
    }

    public function test_missing_existing_power_field_is_a_safe_noop_without_new_catalog_rows(): void
    {
        $category = Category::query()->create(['name' => 'Laptopovi', 'slug' => 'laptopovi', 'status' => 'active']);
        ProductType::query()->create(['name' => 'Laptopovi', 'slug' => 'laptopovi', 'status' => 'active', 'category_id' => $category->id]);
        $this->applyMigration();
        self::assertSame(0, ProductType::query()->where('slug', 'punjaci-za-laptopove')->count());
        self::assertSame(0, Category::query()->where('slug', 'punjaci-za-laptopove')->count());
    }

    private function applyMigration(): void
    {
        /** @var \Illuminate\Database\Migrations\Migration $migration */
        $migration = require base_path('database/migrations/2026_10_20_150000_add_laptop_chargers_catalog_data.php');
        $migration->up();
    }
}
