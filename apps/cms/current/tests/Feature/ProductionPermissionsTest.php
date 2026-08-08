<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ProductionPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_order_creation_requires_orders_create_permission(): void
    {
        $user = $this->userWithoutPermissions();

        $this->actingAs($user)->get('/order/new')->assertForbidden();
        $this->actingAs($user)->post('/orders', [])->assertForbidden();
    }

    public function test_standard_user_cannot_manage_orders_or_adjust_stock(): void
    {
        $user = $this->standardUser();
        $product = Product::query()->create([
            'sku' => 'PERMISSION-STOCK',
            'name' => 'Permission stock',
            'slug' => 'permission-stock',
            'price_amount' => 1000,
            'price_currency' => 'RSD',
            'description' => 'Opis',
            'stock_quantity' => 4,
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)->get('/admin/orders')->assertForbidden();
        $this->actingAs($user)->post('/admin/stock/'.$product->id.'/adjust', [
            'idempotency_key' => 'forbidden-adjustment',
            'quantity_change' => 2,
            'note' => 'Neovlašćena korekcija',
        ])->assertForbidden();

        self::assertSame(4, $product->fresh()->stock_quantity);
    }

    private function userWithoutPermissions(): User
    {
        DB::table('user_groups')->insert([
            'id' => 2,
            'name' => 'Bez dozvola',
            'slug' => 'bez-dozvola',
            'description' => 'Test grupa bez dozvola.',
            'status' => 'active',
            'category_access_mode' => 'none',
            'include_uncategorized' => false,
            'sort_order' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->createUser('no-permissions', 2);
    }

    private function standardUser(): User
    {
        return $this->createUser('standard-permissions', 1);
    }

    private function createUser(string $suffix, int $groupId): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => $groupId,
            'username' => $suffix,
            'email' => $suffix.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }
}
