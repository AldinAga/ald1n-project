<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ProductVariantsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_retired_variant_route_is_not_available_and_schema_is_removed(): void
    {
        $admin = User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'product-only-admin',
            'email' => 'product-only-admin@test.local',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
        $product = Product::query()->create([
            'sku' => 'PRODUCT-ONLY-1',
            'name' => 'Product only',
            'slug' => 'product-only-1',
            'price_amount' => 599,
            'price_currency' => 'EUR',
            'description' => 'Product-only decommission contract',
            'stock_quantity' => 4,
            'low_stock_threshold' => 1,
            'status' => 'active',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post('/admin/catalog/'.$product->id.'/variants', ['sku' => 'RETIRED'])
            ->assertNotFound();

        self::assertFalse(Schema::hasTable('product_variants'));
        self::assertFalse(Schema::hasTable('product_variant_spec_values'));
        self::assertFalse(Schema::hasColumn('products', 'variants_enabled'));
        self::assertFalse(Schema::hasColumn('products', 'default_variant_id'));
        self::assertFalse(Schema::hasColumn('order_items', 'product_variant_id'));
        self::assertFalse(Schema::hasColumn('order_items', 'variant_sku_snapshot'));
        self::assertFalse(Schema::hasColumn('order_items', 'variant_name_snapshot'));
        self::assertFalse(Schema::hasColumn('order_items', 'variant_attributes_json'));
    }
}
