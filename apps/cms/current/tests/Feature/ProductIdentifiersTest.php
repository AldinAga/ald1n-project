<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\CatalogQueryService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** Šifra dobavljača i EAN: čuvanje, validacija, pretraga u katalogu i API. */
final class ProductIdentifiersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_migration_adds_nullable_identifier_columns(): void
    {
        self::assertTrue(Schema::hasColumn('products', 'supplier_code'));
        self::assertTrue(Schema::hasColumn('products', 'ean'));
        self::assertTrue(Product::hasIdentifierColumns());
    }

    public function test_web_create_normalizes_and_stores_identifiers(): void
    {
        $this->actingAs($this->admin('id-admin'))->post('/admin/catalog', $this->payload([
            'supplier_code' => '  AB   123/X ', 'ean' => '859 0123-456789',
        ]))->assertRedirect('/admin/catalog/create');

        $product = Product::query()->sole();
        self::assertSame('AB 123/X', $product->supplier_code);
        self::assertSame('8590123456789', $product->ean);
    }

    public function test_empty_identifiers_are_stored_as_null_and_ean_format_is_validated(): void
    {
        $admin = $this->admin('id-validate');
        $this->actingAs($admin)->post('/admin/catalog', $this->payload(['supplier_code' => '', 'ean' => '']))->assertRedirect();
        $product = Product::query()->sole();
        self::assertNull($product->supplier_code);
        self::assertNull($product->ean);

        foreach (['12345', 'ABCDEFGH12', '123456789012345'] as $bad) {
            $this->post('/admin/catalog', $this->payload(['name' => 'Loš '.$bad, 'ean' => $bad]))->assertSessionHasErrors('ean');
        }
        $this->post('/admin/catalog', $this->payload(['name' => 'Predug', 'supplier_code' => str_repeat('a', 101)]))->assertSessionHasErrors('supplier_code');
        self::assertSame(1, Product::query()->count());
    }

    public function test_update_keeps_identifiers_when_keys_are_missing_and_clears_them_when_sent_empty(): void
    {
        $admin = $this->admin('id-update');
        $product = $this->product('ID-1', $admin->id, ['supplier_code' => 'SUP-9', 'ean' => '12345678']);

        $base = ['sku' => 'ID-1', 'name' => 'Artikal ID-1', 'price_amount' => 100, 'price_currency' => 'EUR', 'description' => 'Opis',
            'stock_quantity' => 2, 'low_stock_threshold' => 1, 'status' => 'active'];

        $this->actingAs($admin)->putJson('/admin/catalog/'.$product->id, $base)->assertOk();
        $product->refresh();
        self::assertSame('SUP-9', $product->supplier_code);
        self::assertSame('12345678', $product->ean);

        $this->putJson('/admin/catalog/'.$product->id, $base + ['supplier_code' => '', 'ean' => ''])->assertOk();
        $product->refresh();
        self::assertNull($product->supplier_code);
        self::assertNull($product->ean);
    }

    public function test_catalog_search_finds_products_by_supplier_code_and_ean(): void
    {
        $admin = $this->admin('id-search', 'superadmin');
        $byCode = $this->product('ID-A', $admin->id, ['name' => 'Prvi', 'supplier_code' => 'DOB-777-X']);
        $byEan = $this->product('ID-B', $admin->id, ['name' => 'Drugi', 'ean' => '5901234123457']);
        $this->product('ID-C', $admin->id, ['name' => 'Treći']);

        $catalog = app(CatalogQueryService::class);
        self::assertSame([(int) $byCode->id], $catalog->paginate($admin, ['q' => 'DOB-777'])->getCollection()->pluck('id')->map(fn ($id): int => (int) $id)->all());
        self::assertSame([(int) $byEan->id], $catalog->paginate($admin, ['q' => '5901234123457'])->getCollection()->pluck('id')->map(fn ($id): int => (int) $id)->all());
        self::assertSame([(int) $byEan->id], $catalog->quickSearch($admin, '90123412')->pluck('id')->map(fn ($id): int => (int) $id)->all());

        $this->actingAs($admin, 'sanctum');
        $rows = $this->getJson('/api/v1/admin/catalog/products?q=DOB-777')->assertOk()->json('data');
        self::assertSame([(int) $byCode->id], array_column($rows, 'id'));
    }

    public function test_partner_cannot_search_by_supplier_code_but_admin_can(): void
    {
        $admin = $this->admin('id-partner-admin', 'superadmin');
        $product = $this->product('ID-P', $admin->id, ['name' => 'Partnerski artikal', 'supplier_code' => 'TAJNA-4711', 'ean' => '5901234123457']);

        $group = \App\Models\UserGroup::query()->create([
            'name' => 'Partneri', 'slug' => 'partneri', 'status' => 'active',
            'category_access_mode' => 'all', 'include_uncategorized' => true,
        ]);
        \Illuminate\Support\Facades\DB::table('user_group_permissions')->insert([
            'group_id' => $group->id, 'permission_id' => \Illuminate\Support\Facades\DB::table('permissions')->where('slug', 'catalog.view')->value('id'), 'created_at' => now(),
        ]);
        $partner = User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'), 'user_group_id' => $group->id,
            'username' => 'partner-1', 'email' => 'partner-1@test.local', 'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);

        $catalog = app(CatalogQueryService::class);
        $ids = static fn ($rows): array => $rows->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        // Kontrola: partner vidi artikal (ime i EAN), pa prazan rezultat za šifru nije posledica skrivenog kataloga.
        self::assertSame([(int) $product->id], $ids($catalog->paginate($partner, ['q' => 'Partnerski'])->getCollection()));
        self::assertSame([(int) $product->id], $ids($catalog->paginate($partner, ['q' => '5901234123457'])->getCollection()));
        self::assertSame([], $ids($catalog->paginate($partner, ['q' => 'TAJNA-4711'])->getCollection()));
        self::assertSame([], $ids($catalog->quickSearch($partner, 'TAJNA')));
        self::assertSame([(int) $product->id], $ids($catalog->paginate($admin, ['q' => 'TAJNA-4711'])->getCollection()));
        self::assertSame([(int) $product->id], $ids($catalog->quickSearch($admin, 'TAJNA')));
    }

    public function test_array_identifiers_are_rejected_without_server_error(): void
    {
        $this->actingAs($this->admin('id-array'))->post('/admin/catalog', $this->payload(['ean' => ['1'], 'supplier_code' => ['x']]))
            ->assertSessionHasErrors(['ean', 'supplier_code']);
        self::assertSame(0, Product::query()->count());
    }

    public function test_api_create_and_update_roundtrip_identifiers(): void
    {
        $admin = $this->admin('id-api', 'superadmin');
        $this->actingAs($admin, 'sanctum');
        $created = $this->postJson('/api/v1/admin/catalog/products', [
            'name' => 'API artikal', 'regenerate_sku' => true, 'price_amount' => 10, 'price_currency' => 'EUR', 'description' => 'Opis',
            'stock_quantity' => 1, 'low_stock_threshold' => 1, 'status' => 'draft', 'supplier_code' => 'S-1', 'ean' => '4006381333931',
        ])->assertCreated();

        $product = Product::query()->findOrFail((int) $created->json('data.id'));
        self::assertSame('S-1', $product->supplier_code);
        self::assertSame('4006381333931', $product->ean);

        $this->putJson('/api/v1/admin/catalog/products/'.$product->id, [
            'sku' => $product->sku, 'name' => 'API artikal', 'price_amount' => 10, 'price_currency' => 'EUR', 'description' => 'Opis',
            'stock_quantity' => 1, 'low_stock_threshold' => 1, 'status' => 'draft', 'ean' => '12345678',
        ])->assertOk()->assertJsonPath('data.ean', '12345678')->assertJsonPath('data.supplier_code', 'S-1');
    }

    public function test_clone_does_not_copy_unique_identifiers(): void
    {
        $admin = $this->admin('id-clone');
        $source = $this->product('ID-CL', $admin->id, ['supplier_code' => 'S-2', 'ean' => '12345678']);
        $this->actingAs($admin)->post('/admin/catalog/'.$source->id.'/clone')->assertRedirect();
        $clone = Product::query()->where('source_product_id', $source->id)->sole();
        self::assertNull($clone->ean);
        self::assertNull($clone->supplier_code);
    }

    public function test_form_renders_identifier_inputs(): void
    {
        $admin = $this->admin('id-form');
        $product = $this->product('ID-F', $admin->id, ['supplier_code' => 'SUP-F', 'ean' => '12345678']);
        $this->actingAs($admin)->get('/admin/catalog/'.$product->id.'/edit')->assertOk()
            ->assertSee('name="supplier_code"', false)->assertSee('value="SUP-F"', false)
            ->assertSee('name="ean"', false)->assertSee('value="12345678"', false);
    }

    /** @param array<string,mixed> $override @return array<string,mixed> */
    private function payload(array $override = []): array
    {
        return array_replace([
            'name' => 'Artikal sa šifrom', 'regenerate_sku' => true, 'price_amount' => '10', 'price_currency' => 'EUR',
            'description' => 'Opis', 'stock_quantity' => 1, 'low_stock_threshold' => 1, 'status' => 'draft',
        ], $override);
    }

    private function admin(string $username, string $role = 'admin'): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'username' => $username, 'email' => $username.'@test.local',
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
    }

    /** @param array<string,mixed> $extra */
    private function product(string $sku, int $createdBy, array $extra = []): Product
    {
        return Product::query()->create($extra + [
            'sku' => $sku, 'name' => 'Artikal '.$sku, 'slug' => strtolower($sku),
            'price_amount' => 100, 'price_currency' => 'EUR', 'description' => 'Opis', 'stock_quantity' => 2,
            'low_stock_threshold' => 1, 'status' => 'active', 'created_by' => $createdBy, 'updated_by' => $createdBy,
        ]);
    }
}
