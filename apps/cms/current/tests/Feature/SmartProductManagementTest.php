<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\Role;
use App\Models\SpecificationField;
use App\Models\SpecificationOption;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class SmartProductManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_template_generates_name_defaults_and_completeness(): void
    {
        [$admin, $brand, $line, $type, $cpu] = $this->fixture();

        $response = $this->actingAs($admin)->post('/admin/catalog', [
            'product_type_id' => $type->id,
            'brand_id' => $brand->id,
            'product_line_id' => $line->id,
            'regenerate_name' => '1',
            'price_amount' => '650',
            'price_currency' => 'EUR',
            'description' => 'Test laptop',
            'stock_quantity' => 3,
            'low_stock_threshold' => 1,
            'status' => 'active',
            'specs' => [$cpu->id => 'Intel Core i5'],
            'spec_details' => [$cpu->id => '1135G7'],
        ]);

        $product = Product::query()->firstOrFail();
        $response->assertRedirect('/admin/catalog/'.$product->id.'/edit');
        self::assertSame('HP EliteBook Intel Core i5 1135G7', $product->name);
        self::assertSame(100, $product->completeness_percent);
        self::assertFalse($product->name_is_manual);
    }

    public function test_clone_has_new_sku_zero_stock_and_does_not_copy_unchecked_sections(): void
    {
        [$admin, $brand, $line, $type, $cpu] = $this->fixture();
        $source = Product::query()->create([
            'product_type_id' => $type->id,
            'brand_id' => $brand->id,
            'product_line_id' => $line->id,
            'sku' => 'HP-001',
            'name' => 'HP EliteBook test',
            'slug' => 'hp-elitebook-test',
            'price_amount' => 500,
            'price_currency' => 'EUR',
            'description' => 'Opis',
            'stock_quantity' => 9,
            'low_stock_threshold' => 1,
            'status' => 'active',
            'completeness_percent' => 100,
            'name_is_manual' => true,
        ]);

        $response = $this->actingAs($admin)->post('/admin/catalog/'.$source->id.'/clone', [
            'name' => 'Klon bez osnovnih podataka',
            'copy_basic' => '0',
            'copy_specifications' => '0',
            'copy_categories' => '0',
            'copy_price' => '0',
            'copy_description' => '0',
            'copy_notes' => '0',
            'copy_images' => '0',
            'copy_warranty_rules' => '0',
            'regenerate_name' => '0',
        ]);

        $clone = Product::query()->where('source_product_id', $source->id)->firstOrFail();
        $response->assertRedirect('/admin/catalog/'.$clone->id.'/edit');
        self::assertNotSame($source->sku, $clone->sku);
        self::assertSame(0, $clone->stock_quantity);
        self::assertSame('draft', $clone->status);
        self::assertNull($clone->brand_id);
        self::assertNull($clone->product_type_id);
        self::assertSame('0.00', $clone->price_amount);
    }

    public function test_bulk_brand_change_clears_line_from_previous_brand(): void
    {
        [$admin, $brand, $line, $type] = $this->fixture();
        $otherBrand = Brand::query()->create(['name' => 'Lenovo', 'slug' => 'lenovo', 'status' => 'active']);
        $product = Product::query()->create([
            'product_type_id' => $type->id,
            'brand_id' => $brand->id,
            'product_line_id' => $line->id,
            'sku' => 'HP-002',
            'name' => 'HP test',
            'slug' => 'hp-test',
            'price_amount' => 500,
            'price_currency' => 'EUR',
            'description' => 'Opis',
            'stock_quantity' => 0,
            'low_stock_threshold' => 1,
            'status' => 'draft',
            'completeness_percent' => 0,
            'name_is_manual' => true,
        ]);

        $this->actingAs($admin)->post('/admin/catalog/bulk', [
            'product_ids' => [$product->id],
            'mode' => 'execute',
            'apply_brand' => '1',
            'brand_id' => $otherBrand->id,
        ])->assertRedirect('/catalog?ownership=mine');

        $product->refresh();
        self::assertSame($otherBrand->id, $product->brand_id);
        self::assertNull($product->product_line_id);
    }

    /** @return array{User,Brand,ProductLine,ProductType,SpecificationField} */
    private function fixture(): array
    {
        $admin = User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'smart-admin',
            'email' => 'smart-admin@test.local',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
        $brand = Brand::query()->create(['name' => 'HP', 'slug' => 'hp', 'status' => 'active']);
        $line = ProductLine::query()->create(['brand_id' => $brand->id, 'name' => 'EliteBook', 'slug' => 'elitebook', 'status' => 'active']);
        $type = ProductType::query()->create([
            'name' => 'Laptop',
            'slug' => 'laptop',
            'status' => 'active',
            'name_template' => '{brand} {line} {procesor} {procesor_detail}',
            'auto_name_enabled' => true,
            'minimum_completeness_percent' => 100,
            'default_product_status' => 'draft',
            'required_core_fields_json' => ['brand', 'line', 'description', 'price'],
        ]);
        $cpu = SpecificationField::query()->create([
            'name' => 'Procesor',
            'slug' => 'procesor',
            'data_type' => 'select',
            'filter_type' => 'select',
            'status' => 'active',
            'detail_input_enabled' => true,
            'detail_label' => 'Tačan model procesora',
        ]);
        SpecificationOption::query()->create([
            'field_id' => $cpu->id,
            'label' => 'Intel Core i5',
            'value' => 'Intel Core i5',
            'status' => 'active',
            'sort_order' => 10,
        ]);
        $type->fields()->attach($cpu->id, [
            'is_required' => true,
            'is_filterable' => true,
            'show_in_summary' => true,
            'sort_order' => 10,
            'default_value' => null,
            'default_detail' => null,
            'completeness_weight' => 2,
            'include_in_name' => true,
            'created_at' => now(),
        ]);
        return [$admin, $brand, $line, $type, $cpu];
    }
}
