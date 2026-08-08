<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ProductVariantsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_admin_can_create_variant_and_parent_receives_aggregate_stock(): void
    {
        $admin = User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'variant-admin',
            'email' => 'variant-admin@test.local',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
        $type = ProductType::query()->create(['name' => 'Laptop', 'slug' => 'variant-laptop', 'status' => 'active']);
        $product = Product::query()->create([
            'product_type_id' => $type->id,
            'sku' => 'LAP-PARENT-1',
            'name' => 'Laptop parent',
            'slug' => 'laptop-parent-1',
            'price_amount' => 0,
            'price_currency' => 'EUR',
            'stock_quantity' => 0,
            'low_stock_threshold' => 0,
            'status' => 'active',
        ]);

        $this->actingAs($admin)->post('/admin/catalog/'.$product->id.'/variants', [
            'sku' => 'LAP-PARENT-1-16-512',
            'name' => '16GB / 512GB',
            'price_amount' => '599',
            'price_currency' => 'EUR',
            'stock_quantity' => 4,
            'low_stock_threshold' => 1,
            'status' => 'active',
            'is_default' => '1',
            'sort_order' => 10,
            'specs' => [],
            'spec_details' => [],
        ])->assertRedirect('/admin/catalog/'.$product->id.'/variants');

        $product->refresh();
        self::assertTrue($product->variants_enabled);
        self::assertSame(4, $product->stock_quantity);
        self::assertNotNull($product->default_variant_id);
    }
}
