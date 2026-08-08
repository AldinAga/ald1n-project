<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\UserGroup;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class CatalogAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_selected_parent_category_includes_children_and_optional_uncategorized_products(): void
    {
        $group = UserGroup::query()->create([
            'name' => 'Ograničena grupa',
            'slug' => 'ogranicena-grupa',
            'status' => 'active',
            'category_access_mode' => 'selected',
            'include_uncategorized' => true,
        ]);
        $catalogPermission = DB::table('permissions')->where('slug', 'catalog.view')->value('id');
        DB::table('user_group_permissions')->insert(['group_id' => $group->id, 'permission_id' => $catalogPermission, 'created_at' => now()]);

        $parent = $this->category('Racunari', null);
        $child = $this->category('Laptopovi', $parent->id);
        $outside = $this->category('Telefoni', null);
        DB::table('user_group_categories')->insert(['group_id' => $group->id, 'category_id' => $parent->id, 'created_at' => now()]);

        $user = $this->user($group);
        $visible = $this->product('visible', 'Vidljiv laptop', $user->id);
        $visible->categories()->attach($child->id);
        $hidden = $this->product('hidden', 'Skriven telefon', $user->id);
        $hidden->categories()->attach($outside->id);
        $uncategorized = $this->product('uncategorized', 'Bez kategorije', $user->id);
        $response = $this->actingAs($user)->get('/catalog');

        $response->assertOk()->assertSee('Vidljiv laptop')->assertSee('Bez kategorije')->assertDontSee('Skriven telefon');
    }

    public function test_user_without_price_permission_does_not_receive_price_from_api(): void
    {
        $group = UserGroup::query()->create([
            'name' => 'Bez cena', 'slug' => 'bez-cena', 'status' => 'active',
            'category_access_mode' => 'all', 'include_uncategorized' => true,
        ]);
        $catalogPermission = DB::table('permissions')->where('slug', 'catalog.view')->value('id');
        DB::table('user_group_permissions')->insert(['group_id' => $group->id, 'permission_id' => $catalogPermission, 'created_at' => now()]);
        $user = $this->user($group);
        $product = $this->product('api-product', 'API proizvod', $user->id);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/products/'.$product->slug);

        $response->assertOk()->assertJsonPath('data.name', 'API proizvod')->assertJsonMissingPath('data.price');
        $response->assertJsonPath('data.commission_eur', 20);
    }

    private function user(UserGroup $group): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => $group->id,
            'username' => 'group-'.$group->id,
            'email' => 'group-'.$group->id.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function category(string $name, ?int $parentId): Category
    {
        return Category::query()->create([
            'parent_id' => $parentId,
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)).'-'.uniqid(),
            'status' => 'active',
        ]);
    }

    private function product(string $slug, string $name, int $createdBy): Product
    {
        return Product::query()->create([
            'sku' => strtoupper($slug),
            'name' => $name,
            'slug' => $slug,
            'price_amount' => 100,
            'price_currency' => 'EUR',
            'description' => 'Opis',
            'stock_quantity' => 2,
            'status' => 'active',
            'created_by' => $createdBy,
        ]);
    }
}
