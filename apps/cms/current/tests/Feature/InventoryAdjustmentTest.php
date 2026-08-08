<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class InventoryAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_manual_adjustment_is_idempotent_and_audited(): void
    {
        $admin = $this->admin();
        $product = $this->product($admin, 10);
        $payload = ['idempotency_key' => 'adjust-1', 'quantity_change' => 5, 'note' => 'Prijem robe'];

        $this->actingAs($admin)->post('/admin/stock/'.$product->id.'/adjust', $payload)->assertRedirect();
        $this->actingAs($admin)->post('/admin/stock/'.$product->id.'/adjust', $payload)->assertRedirect();

        self::assertSame(15, $product->fresh()->stock_quantity);
        self::assertSame(1, StockMovement::query()->where('source', 'admin_adjustment')->count());
        self::assertDatabaseHas('audit_logs', ['action' => 'stock.adjusted']);
    }

    public function test_adjustment_cannot_make_stock_negative(): void
    {
        $admin = $this->admin();
        $product = $this->product($admin, 2);

        $this->actingAs($admin)->from('/admin/stock-movements')->post('/admin/stock/'.$product->id.'/adjust', [
            'idempotency_key' => 'adjust-negative',
            'quantity_change' => -3,
            'note' => 'Greška',
        ])->assertRedirect('/admin/stock-movements')->assertSessionHasErrors('quantity_change');

        self::assertSame(2, $product->fresh()->stock_quantity);
        self::assertSame(0, StockMovement::query()->count());
    }

    private function admin(): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'stock-admin',
            'email' => 'stock-admin@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function product(User $creator, int $stock): Product
    {
        return Product::query()->create([
            'sku' => 'STOCK-1',
            'name' => 'Stock product',
            'slug' => 'stock-product',
            'price_amount' => 100,
            'price_currency' => 'RSD',
            'description' => 'Opis',
            'stock_quantity' => $stock,
            'status' => 'active',
            'created_by' => $creator->id,
        ]);
    }
}
