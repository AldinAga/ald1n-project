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

final class ProductInternalNotesVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const NOTE = "Prvi red napomene\nDrugi red <b>x</b>";

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('app.key', 'base64:'.base64_encode(str_repeat('k', 32)));
    }

    public function test_superadmin_sees_notes_on_detail_and_flag_in_list(): void
    {
        $admin = $this->user('superadmin', 'notes-admin');
        $product = $this->product($admin->id);

        $this->actingAs($admin)->get('/catalog/'.$product->slug)
            ->assertOk()
            ->assertSee('data-product-internal-notes', false)
            ->assertSee('Prvi red napomene<br />', false)
            ->assertSee('Drugi red &lt;b&gt;x&lt;/b&gt;', false);

        $this->actingAs($admin)->get('/catalog')
            ->assertOk()
            ->assertSee('data-product-has-notes', false);
    }

    public function test_notes_are_hidden_from_non_managers(): void
    {
        $admin = $this->user('superadmin', 'notes-owner');
        $product = $this->product($admin->id);
        $viewer = $this->user('user', 'notes-viewer', true);

        $this->actingAs($viewer)->get('/catalog/'.$product->slug)
            ->assertOk()
            ->assertDontSee('data-product-internal-notes', false)
            ->assertDontSee('Prvi red napomene');

        $this->actingAs($viewer)->get('/catalog')
            ->assertOk()
            ->assertDontSee('data-product-has-notes', false);
    }

    public function test_detail_without_notes_has_no_notes_card(): void
    {
        $admin = $this->user('superadmin', 'notes-empty');
        $product = $this->product($admin->id, null);

        $this->actingAs($admin)->get('/catalog/'.$product->slug)
            ->assertOk()
            ->assertDontSee('data-product-internal-notes', false);
    }

    private function user(string $role, string $name, bool $withCatalogView = false): User
    {
        $groupId = null;
        if ($withCatalogView) {
            $group = UserGroup::query()->create([
                'name' => $name, 'slug' => $name, 'status' => 'active',
                'category_access_mode' => 'all', 'include_uncategorized' => true,
            ]);
            DB::table('user_group_permissions')->insert([
                'group_id' => $group->id,
                'permission_id' => DB::table('permissions')->where('slug', 'catalog.view')->value('id'),
                'created_at' => now(),
            ]);
            $groupId = $group->id;
        }

        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $groupId,
            'username' => $name,
            'email' => $name.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function product(int $createdBy, ?string $notes = self::NOTE): Product
    {
        return Product::query()->create([
            'sku' => 'NOTE-1',
            'name' => 'Artikal sa napomenom',
            'slug' => 'artikal-sa-napomenom',
            'price_amount' => 100,
            'price_currency' => 'EUR',
            'description' => 'Opis',
            'notes' => $notes,
            'stock_quantity' => 2,
            'status' => 'active',
            'created_by' => $createdBy,
        ]);
    }
}
