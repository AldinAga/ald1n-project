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

    public function test_admin_who_is_not_author_sees_notes_on_detail_and_in_list(): void
    {
        $author = $this->user('admin', 'notes-author');
        $product = $this->product($author->id);
        $other = $this->user('admin', 'notes-other-admin');

        $this->actingAs($other)->get('/catalog/'.$product->slug)
            ->assertOk()
            ->assertSee('data-product-internal-notes', false)
            ->assertSee('Prvi red napomene<br />', false)
            ->assertDontSee('Interno');

        $this->actingAs($other)->get('/catalog')
            ->assertOk()
            ->assertSee('data-product-has-notes', false);
    }

    public function test_catalog_notes_are_visible_only_with_notes_permission(): void
    {
        $admin = $this->user('superadmin', 'notes-owner');
        $product = $this->product($admin->id);
        $notesViewer = $this->user('user', 'notes-allowed', ['catalog.view', 'catalog.view_notes']);
        $viewer = $this->user('user', 'notes-viewer', ['catalog.view']);

        self::assertTrue($notesViewer->can('catalog.view_notes'));
        self::assertFalse($notesViewer->can('catalog.manage_products'));
        self::assertFalse($viewer->can('catalog.view_notes'));

        $this->actingAs($notesViewer)->get('/catalog/'.$product->slug)
            ->assertOk()
            ->assertSee('data-product-internal-notes', false)
            ->assertSee('Prvi red napomene');

        $this->get('/catalog')
            ->assertOk()
            ->assertSee('data-product-has-notes', false);

        $this->actingAs($viewer)->get('/catalog/'.$product->slug)
            ->assertOk()
            ->assertDontSee('data-product-internal-notes', false)
            ->assertDontSee('Prvi red napomene');

        $this->actingAs($viewer)->get('/catalog')
            ->assertOk()
            ->assertSee($product->name)
            ->assertDontSee('data-product-has-notes', false);
    }

    public function test_product_editor_without_notes_permission_cannot_see_notes(): void
    {
        $author = $this->user('user', 'notes-product-editor', ['catalog.manage_products']);
        $product = $this->product($author->id);

        self::assertFalse($author->can('catalog.view_notes'));

        $this->actingAs($author)->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertDontSee('name="notes"', false)
            ->assertDontSee('Prvi red napomene');
    }

    public function test_detail_without_notes_has_no_notes_card(): void
    {
        $admin = $this->user('superadmin', 'notes-empty');
        $product = $this->product($admin->id, null);

        $this->actingAs($admin)->get('/catalog/'.$product->slug)
            ->assertOk()
            ->assertDontSee('data-product-internal-notes', false);
    }

    /** @param list<string> $permissions */
    private function user(string $role, string $name, array $permissions = []): User
    {
        $groupId = null;
        if ($permissions !== []) {
            $group = UserGroup::query()->create([
                'name' => $name, 'slug' => $name, 'status' => 'active',
                'category_access_mode' => 'all', 'include_uncategorized' => true,
            ]);
            foreach ($permissions as $permission) {
                DB::table('user_group_permissions')->insert([
                    'group_id' => $group->id,
                    'permission_id' => DB::table('permissions')->where('slug', $permission)->value('id'),
                    'created_at' => now(),
                ]);
            }
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
