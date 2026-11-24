<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ProductNotesFormSubmitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_superadmin_store_saves_notes_and_detail_shows_them(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)->post('/admin/catalog', $this->payload(['notes' => 'Interna napomena pri unosu']))
            ->assertSessionHasNoErrors();

        $product = Product::query()->firstOrFail();
        self::assertSame('Interna napomena pri unosu', $product->notes);

        $this->actingAs($admin)->get('/catalog/'.$product->slug)
            ->assertOk()
            ->assertSee('data-product-internal-notes', false)
            ->assertSee('Interna napomena pri unosu');
    }

    public function test_superadmin_update_changes_notes_and_empty_notes_clear_them(): void
    {
        $admin = $this->superadmin();
        $product = $this->product($admin->id, 'Stara napomena');

        $this->actingAs($admin)->put('/admin/catalog/'.$product->id, $this->payload(['sku' => $product->sku, 'notes' => 'Nova napomena']))
            ->assertSessionHasNoErrors();
        self::assertSame('Nova napomena', $product->refresh()->notes);
        $this->actingAs($admin)->get('/catalog/'.$product->slug)->assertOk()->assertSee('Nova napomena');

        $this->actingAs($admin)->put('/admin/catalog/'.$product->id, $this->payload(['sku' => $product->sku, 'notes' => '']))
            ->assertSessionHasNoErrors();
        self::assertNull($product->refresh()->notes);
        $this->actingAs($admin)->get('/catalog/'.$product->slug)
            ->assertOk()
            ->assertDontSee('data-product-internal-notes', false);
    }

    public function test_too_long_notes_fail_validation_and_are_not_saved(): void
    {
        $admin = $this->superadmin();
        $product = $this->product($admin->id, 'Ostaje');

        $this->actingAs($admin)->put('/admin/catalog/'.$product->id, $this->payload(['sku' => $product->sku, 'notes' => str_repeat('a', 65001)]))
            ->assertSessionHasErrors('notes');
        self::assertSame('Ostaje', $product->refresh()->notes);

        $this->actingAs($admin)->post('/admin/catalog', $this->payload(['name' => 'Drugi artikal', 'notes' => str_repeat('a', 65001)]))
            ->assertSessionHasErrors('notes');
        self::assertSame(1, Product::query()->count());
    }

    public function test_user_without_permission_cannot_write_notes(): void
    {
        $owner = $this->superadmin();
        $product = $this->product($owner->id, 'Zasticena');
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/catalog', $this->payload(['name' => 'Tudji artikal', 'notes' => 'Upad']))
            ->assertForbidden();
        $this->actingAs($user)->put('/admin/catalog/'.$product->id, $this->payload(['sku' => $product->sku, 'notes' => 'Upad']))
            ->assertForbidden();

        self::assertSame('Zasticena', $product->refresh()->notes);
        self::assertSame(1, Product::query()->count());
    }

    /** @param array<string,mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'name' => 'Artikal sa napomenom',
            'price_amount' => '100',
            'price_currency' => 'EUR',
            'description' => 'Opis',
            'stock_quantity' => 2,
            'low_stock_threshold' => 1,
            'status' => 'active',
        ];
    }

    private function superadmin(): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'notes-form-admin',
            'email' => 'notes-form-admin@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function product(int $createdBy, ?string $notes): Product
    {
        return Product::query()->create([
            'sku' => 'NOTE-FORM-1',
            'name' => 'Artikal sa napomenom',
            'slug' => 'artikal-sa-napomenom',
            'price_amount' => 100,
            'price_currency' => 'EUR',
            'description' => 'Opis',
            'notes' => $notes,
            'stock_quantity' => 2,
            'low_stock_threshold' => 1,
            'status' => 'active',
            'created_by' => $createdBy,
        ]);
    }
}
