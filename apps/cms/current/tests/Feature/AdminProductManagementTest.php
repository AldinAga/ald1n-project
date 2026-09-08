<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AdminProductManagementTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void { parent::setUp(); $this->seed(CoreAccessSeeder::class); }

    public function test_admin_can_create_product_with_automatic_sku_and_audit_log(): void
    {
        $admin = $this->admin();
        $brand = Brand::query()->create(['name'=>'Lenovo','slug'=>'lenovo','status'=>'active']);
        $category = Category::query()->create(['name'=>'Laptopovi','slug'=>'laptopovi','status'=>'active']);
        $response = $this->actingAs($admin)->post('/admin/catalog', [
            'brand_id'=>$brand->id,'name'=>'ThinkPad T14','price_amount'=>'999.99','price_currency'=>'EUR',
            'description'=>'Test opis','stock_quantity'=>5,'low_stock_threshold'=>1,'status'=>'active','category_ids'=>[$category->id],
        ]);
        $product = Product::query()->firstOrFail();
        $response->assertRedirect('/admin/catalog/'.$product->id.'/edit');
        self::assertStringStartsWith('LENOVO-THINKPAD-T14', $product->sku);
        self::assertTrue($product->categories()->whereKey($category->id)->exists());
        self::assertTrue(AuditLog::query()->where('action','product.created')->exists());
    }


    public function test_admin_can_edit_only_products_they_created_while_superadmin_can_edit_all(): void
    {
        $adminOne = $this->admin('admin-one');
        $adminTwo = $this->admin('admin-two');
        $superadmin = User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'superadmin-test',
            'email' => 'superadmin@test.local',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);

        $own = $this->product('OWN-15', 'Artikal administratora 1', $adminOne->id);
        $foreign = $this->product('FOREIGN-16', 'Artikal administratora 2', $adminTwo->id);

        $this->actingAs($adminOne)->get('/admin/catalog/'.$own->id.'/edit')->assertOk();
        $this->actingAs($adminOne)->get('/admin/catalog/'.$foreign->id.'/edit')->assertNotFound();
        $this->actingAs($superadmin)->get('/admin/catalog/'.$foreign->id.'/edit')->assertOk();

        $catalog = $this->actingAs($adminOne)->get('/catalog');
        $catalog->assertOk()->assertSee('Artikal administratora 1')->assertDontSee('Artikal administratora 2');
        $catalog->assertSee('/admin/catalog/'.$own->id.'/edit', false);
        $catalog->assertDontSee('/admin/catalog/'.$foreign->id.'/edit', false);
    }

    public function test_admin_catalog_list_redirects_to_unified_catalog(): void
    {
        $this->actingAs($this->admin('redirect-admin'))
            ->get('/admin/catalog?stock=out')
            ->assertRedirect('/catalog?stock=out');
    }

    public function test_regular_user_cannot_open_catalog_administration(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin/catalog')->assertForbidden();
    }

    public function test_mobile_product_create_replays_same_product_for_same_idempotency_key(): void
    {
        $superadmin = $this->superadmin('idempotent-create');
        $payload = [
            'name' => 'Idempotentni mobilni artikal',
            'regenerate_sku' => true,
            'price_amount' => 15000,
            'price_currency' => 'RSD',
            'description' => 'Test mobilnog idempotentnog kreiranja.',
            'stock_quantity' => 3,
            'low_stock_threshold' => 1,
            'status' => 'draft',
        ];
        $key = 'mobile-product-create:test:stable-key';

        $this->actingAs($superadmin, 'sanctum');
        $first = $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/admin/catalog/products', $payload);
        $first->assertCreated();
        $productId = (int) $first->json('data.id');

        $second = $this->withHeader('Idempotency-Key', $key)
            ->postJson('/api/v1/admin/catalog/products', $payload);
        $second->assertCreated()->assertJsonPath('data.id', $productId);

        self::assertSame(1, Product::query()->where('name', 'Idempotentni mobilni artikal')->count());
        self::assertSame(1, StockMovement::query()
            ->where('product_id', $productId)
            ->where('source', 'product_creation')
            ->count());
    }

    public function test_archived_product_is_excluded_from_operational_api_catalog_for_superadmin(): void
    {
        $superadmin = $this->superadmin('archive-api-scope');
        $product = $this->product('ARCHIVE-API-1', 'Arhiviran API artikal', $superadmin->id);
        $product->forceFill(['status' => 'archived', 'deleted_at' => now()])->save();

        $response = $this->actingAs($superadmin, 'sanctum')->getJson('/api/v1/products?per_page=30');
        $response->assertOk();
        $rows = collect($response->json('data'));

        self::assertFalse($rows->contains(
            static fn (array $row): bool => (int) ($row['id'] ?? 0) === (int) $product->id,
        ));
    }

    private function superadmin(string $username): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => $username,
            'email' => $username.'@test.local',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function admin(string $username = 'admin-test'): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => $username,
            'email' => $username.'@test.local',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function product(string $sku, string $name, int $createdBy): Product
    {
        return Product::query()->create([
            'sku' => $sku,
            'name' => $name,
            'slug' => strtolower($sku),
            'price_amount' => 100,
            'price_currency' => 'EUR',
            'description' => 'Opis artikla',
            'stock_quantity' => 2,
            'low_stock_threshold' => 1,
            'status' => 'active',
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
        ]);
    }
}
