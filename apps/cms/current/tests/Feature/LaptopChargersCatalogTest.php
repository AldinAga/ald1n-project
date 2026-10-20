<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductType;
use App\Models\SpecificationField;
use App\Services\BrandLineCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class LaptopChargersCatalogTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = __DIR__.'/../../database/migrations/2026_10_20_210000_add_laptop_chargers_catalog.php';

    public function test_laptop_brands_and_existing_watts_are_reused_with_required_connector_lines(): void
    {
        $this->fixture();
        $this->migration()->up();
        $type = ProductType::where('slug', 'punjaci-za-laptopove')->sole();
        $category = Category::where('slug', 'punjaci-za-laptopove')->sole();
        self::assertSame((int) $category->id, (int) $type->category_id);
        self::assertSame('draft', $type->default_product_status);
        self::assertStringContainsString('{snaga-napajanja}', $type->name_template);
        $power = SpecificationField::where('slug', 'snaga-napajanja')->sole();
        $pivot = DB::table('product_type_fields')->where('product_type_id', $type->id)->where('field_id', $power->id)->sole();
        self::assertSame(1, (int) $pivot->is_required);
        self::assertSame(1, (int) $pivot->is_filterable);
        self::assertSame(1, SpecificationField::where('slug', 'snaga-napajanja')->count());
        self::assertSame(3, DB::table('brand_product_type')->where('product_type_id', $type->id)->count());
        $scoped = DB::table('product_lines as l')
            ->join('brands as b', 'b.id', '=', 'l.brand_id')
            ->join('product_line_product_type as p', 'p.product_line_id', '=', 'l.id')
            ->where('p.product_type_id', $type->id)->get(['b.slug as brand', 'l.name'])
            ->groupBy('brand');
        self::assertCount(3, $scoped);
        foreach ([
            'dell' => ['Debela igla', '7,4 × 5,0 mm', 'Tanka igla', '4,5 × 3,0 mm', 'USB-C Power Delivery'],
            'hp' => ['Debela igla', 'Plava igla', '4,5 × 3,0 mm', 'USB-C Power Delivery'],
            'lenovo' => ['Kocka / Slim Tip', '11 × 4,5 mm', 'Tanka igla', '4,0 × 1,7 mm', 'Okrugli', '7,9 × 5,5 mm', 'USB-C Power Delivery'],
        ] as $brand => $expected) {
            $names = $scoped[$brand]->pluck('name')->implode(' | ');
            foreach ($expected as $part) self::assertStringContainsString($part, $names);
            self::assertLessThanOrEqual(10, $scoped[$brand]->count());
        }
        self::assertSame(0, DB::table('products')->count());
    }

    public function test_rerun_adds_no_duplicates_or_unrelated_brands(): void
    {
        $this->fixture();
        $migration = $this->migration();
        $migration->up();
        $before = [];
        foreach (['categories','product_types','product_lines','brand_product_type','product_line_product_type','specification_fields','product_type_fields'] as $table) {
            $before[$table] = DB::table($table)->count();
        }
        $migration->up();
        foreach ($before as $table => $count) self::assertSame($count, DB::table($table)->count(), $table);
        $charger = ProductType::where('slug', 'punjaci-za-laptopove')->sole();
        $desktop = Brand::where('slug', 'corsair')->sole();
        self::assertFalse(DB::table('brand_product_type')->where('brand_id', $desktop->id)->where('product_type_id', $charger->id)->exists());
    }

    public function test_missing_watts_never_creates_partially_configured_chargers(): void
    {
        $this->fixture(false);
        $this->migration()->up();
        self::assertFalse(ProductType::where('slug', 'punjaci-za-laptopove')->exists());
        self::assertFalse(Category::where('slug', 'punjaci-za-laptopove')->exists());
    }

    public function test_catalog_brand_line_backfill_does_not_add_laptop_model_lines_to_chargers(): void
    {
        $this->fixture();
        $this->migration()->up();
        app(BrandLineCatalogService::class)->apply();
        $type = ProductType::where('slug', 'punjaci-za-laptopove')->sole();
        $names = DB::table('product_lines as l')
            ->join('product_line_product_type as p', 'p.product_line_id', '=', 'l.id')
            ->where('p.product_type_id', $type->id)->pluck('l.name')->all();
        foreach (['Latitude','Precision','ThinkPad','EliteBook','XPS'] as $laptopLine) {
            self::assertNotContains($laptopLine, $names);
        }
    }

    private function migration(): object
    {
        self::assertFileExists(self::MIGRATION);
        return require self::MIGRATION;
    }

    private function fixture(bool $power = true): void
    {
        $category = Category::create(['name'=>'Laptopovi','slug'=>'laptopovi','status'=>'active']);
        $type = ProductType::create(['name'=>'Laptopovi','slug'=>'laptopovi','status'=>'active','category_id'=>$category->id]);
        foreach (['Dell', 'HP', 'Lenovo', 'Corsair'] as $name) {
            $brand = Brand::create(['name'=>$name,'slug'=>strtolower($name),'status'=>'active']);
            if ($name !== 'Corsair') {
                DB::table('brand_product_type')->insert(['brand_id'=>$brand->id,'product_type_id'=>$type->id]);
            }
        }
        if ($power) SpecificationField::create([
            'name'=>'Snaga napajanja','slug'=>'snaga-napajanja',
            'data_type'=>'integer','filter_type'=>'range','unit'=>'W','status'=>'active'
        ]);
    }
}
