<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CatalogNotesApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_subagent_with_notes_permission_receives_notes_on_detail_only(): void
    {
        $user = $this->user('user', ['catalog.view', 'catalog.view_notes']);
        $product = $this->product('Interna napomena');

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/products/'.$product->slug)
            ->assertOk()->assertJsonPath('data.notes', 'Interna napomena');
        // Lista je namjerno bez napomena (polje do 65000 znakova).
        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/products')->assertOk()->assertJsonMissingPath('data.0.notes');
        $this->assertStringNotContainsString('Interna napomena', $list->getContent());
    }

    public function test_empty_notes_are_null_for_permitted_user(): void
    {
        $user = $this->user('user', ['catalog.view', 'catalog.view_notes']);
        $product = $this->product(null);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/products/'.$product->slug)
            ->assertOk()->assertJsonPath('data.notes', null)->assertJsonStructure(['data' => ['notes']]);
    }

    public function test_admin_receives_notes_and_default_standard_group_gets_permission_from_seeder(): void
    {
        $product = $this->product('Za admina');
        $this->actingAs($this->user('admin', []), 'sanctum')->getJson('/api/v1/products/'.$product->slug)
            ->assertOk()->assertJsonPath('data.notes', 'Za admina');

        $standard = User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'), 'user_group_id' => 1,
            'username' => 'std', 'email' => 'std@example.test',
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
        $this->actingAs($standard, 'sanctum')->getJson('/api/v1/products/'.$product->slug)
            ->assertOk()->assertJsonPath('data.notes', 'Za admina');
    }

    public function test_subagent_without_notes_permission_does_not_receive_notes(): void
    {
        $user = $this->user('user', ['catalog.view']);
        $product = $this->product('Tajna');

        $detail = $this->actingAs($user, 'sanctum')->getJson('/api/v1/products/'.$product->slug)->assertOk()->assertJsonMissingPath('data.notes');
        $this->assertStringNotContainsString('Tajna', $detail->getContent());
        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/products')->assertOk()->assertJsonMissingPath('data.0.notes');
        $this->assertStringNotContainsString('Tajna', $list->getContent());
    }

    public function test_customer_and_guest_never_receive_notes(): void
    {
        $product = $this->product('Tajna');
        // Kupac nema grupna prava, cak ni kad grupa nosi obe dozvole.
        $customer = $this->user('customer', ['catalog.view', 'catalog.view_notes']);

        $response = $this->actingAs($customer, 'sanctum')->getJson('/api/v1/products/'.$product->slug)->assertForbidden();
        $this->assertStringNotContainsString('Tajna', $response->getContent());
        $this->assertFalse($customer->can('catalog.view_notes'));

        $guest = $this->getJson('/api/v1/products/'.$product->slug)->assertUnauthorized();
        $this->assertStringNotContainsString('Tajna', $guest->getContent());
    }

    public function test_product_model_serialization_still_includes_notes_for_admin_flows(): void
    {
        $this->assertArrayHasKey('notes', $this->product('Admin tok')->toArray());
    }

    /** @param list<string> $permissions */
    private function user(string $role, array $permissions): User
    {
        $group = UserGroup::query()->create([
            'name' => 'G '.uniqid(), 'slug' => 'g-'.uniqid(), 'status' => 'active',
            'category_access_mode' => 'all', 'include_uncategorized' => true,
        ]);
        foreach ($permissions as $slug) {
            DB::table('user_group_permissions')->insert([
                'group_id' => $group->id, 'permission_id' => DB::table('permissions')->where('slug', $slug)->value('id'), 'created_at' => now(),
            ]);
        }

        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $group->id,
            'username' => 'u-'.uniqid(), 'email' => uniqid().'@example.test',
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
    }

    private function product(?string $notes): Product
    {
        $slug = 'p-'.uniqid();

        return Product::query()->create([
            'sku' => strtoupper($slug), 'name' => 'Artikal', 'slug' => $slug, 'price_amount' => 100,
            'price_currency' => 'EUR', 'description' => 'Opis', 'notes' => $notes, 'stock_quantity' => 2, 'status' => 'active',
            'created_by' => $this->user('admin', [])->id, // products.created_by nema default
        ]);
    }
}
