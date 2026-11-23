<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Brži unos artikla: prazan formular posle snimanja, upozorenje na duplikat, kloniranje i autosave nacrta. */
final class ProductEntrySpeedTest extends TestCase
{
    use RefreshDatabase;

    private Brand $brand;
    private ProductLine $line;
    private ProductType $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->brand = Brand::query()->create(['name' => 'Lenovo', 'slug' => 'lenovo', 'status' => 'active']);
        $this->line = ProductLine::query()->create(['brand_id' => $this->brand->id, 'name' => 'ThinkPad', 'slug' => 'thinkpad', 'status' => 'active']);
        $category = Category::query()->create(['name' => 'Laptopovi', 'slug' => 'laptopovi', 'status' => 'active']);
        $this->type = ProductType::query()->create(['category_id' => $category->id, 'name' => 'Laptopovi', 'slug' => 'laptopovi', 'status' => 'active']);
        DB::table('brand_product_type')->insert(['brand_id' => $this->brand->id, 'product_type_id' => $this->type->id]);
        DB::table('product_line_product_type')->insert(['product_line_id' => $this->line->id, 'product_type_id' => $this->type->id]);
    }

    public function test_save_returns_empty_form_with_remembered_choices_and_link_to_saved_product(): void
    {
        $admin = $this->admin('speed-admin');
        $this->actingAs($admin)->post('/admin/catalog', $this->payload(['price_currency' => 'RSD']))
            ->assertRedirect('/admin/catalog/create');

        $product = Product::query()->sole();
        $page = $this->get('/admin/catalog/create')->assertOk();
        $page->assertSee('data-created-product-banner', false)
            ->assertSee('Otvori snimljeni artikal')
            ->assertSee('/admin/catalog/'.$product->id.'/edit', false);

        $shown = $page->viewData('product');
        self::assertFalse($shown->exists);
        self::assertSame((int) $this->type->id, (int) $shown->product_type_id);
        self::assertSame((int) $this->brand->id, (int) $shown->brand_id);
        self::assertSame((int) $this->line->id, (int) $shown->product_line_id);
        self::assertSame('RSD', $shown->price_currency);
        self::assertNull($shown->model_name);
        self::assertSame('', (string) $shown->name);
    }

    public function test_remembered_choices_are_one_shot_and_plain_create_stays_default(): void
    {
        $this->actingAs($this->admin('oneshot-admin'))->post('/admin/catalog', $this->payload());
        $this->get('/admin/catalog/create')->assertOk();

        $again = $this->get('/admin/catalog/create')->assertOk();
        $again->assertDontSee('data-created-product-banner', false);
        self::assertNull($again->viewData('product')->brand_id);
        self::assertSame('EUR', $again->viewData('product')->price_currency);
    }

    public function test_json_save_points_to_empty_form_and_exposes_edit_url(): void
    {
        $this->actingAs($this->admin('json-admin'));
        $response = $this->postJson('/admin/catalog', $this->payload())->assertCreated();
        $product = Product::query()->sole();

        $response->assertJsonPath('redirect_url', url('/admin/catalog/create'))
            ->assertJsonPath('edit_url', url('/admin/catalog/'.$product->id.'/edit'));
        $this->get('/admin/catalog/create')->assertSee('Otvori snimljeni artikal');
    }

    public function test_description_stays_required_when_activating(): void
    {
        $this->actingAs($this->admin('desc-admin'))
            ->post('/admin/catalog', array_replace($this->payload(), ['description' => '', 'status' => 'active']))
            ->assertSessionHasErrors('description');
        self::assertSame(0, Product::query()->count());
    }

    public function test_duplicate_check_matches_brand_and_model_case_insensitively_and_respects_scope(): void
    {
        $owner = $this->admin('dup-owner');
        $other = $this->admin('dup-other');
        $mine = $this->product('DUP-1', 'Moj T14', $owner->id, ['model_name' => 'T14 Gen 3']);
        $this->product('DUP-2', 'Tuđ T14', $other->id, ['model_name' => 'T14 Gen 3']);
        $this->product('DUP-3', 'Arhiviran T14', $owner->id, ['model_name' => 'T14 Gen 3', 'deleted_at' => now()]);
        $this->product('DUP-4', 'Drugi model', $owner->id, ['model_name' => 'T16']);

        $query = ['brand_id' => $this->brand->id, 'model_name' => '  t14   GEN 3 '];
        $matches = $this->actingAs($owner)->getJson('/admin/catalog/duplicate-check?'.http_build_query($query))
            ->assertOk()->json('matches');
        self::assertSame([(int) $mine->id], array_column($matches, 'id'));
        self::assertSame(url('/admin/catalog/'.$mine->id.'/clone'), $matches[0]['clone_url']);

        $this->getJson('/admin/catalog/duplicate-check?'.http_build_query($query + ['exclude_id' => $mine->id]))
            ->assertOk()->assertJsonPath('matches', []);
        $this->getJson('/admin/catalog/duplicate-check?brand_id='.$this->brand->id)->assertStatus(422);
    }

    public function test_one_click_clone_creates_draft_copy_with_new_sku(): void
    {
        $owner = $this->admin('clone-admin');
        $source = $this->product('CLN-1', 'Izvorni artikal', $owner->id, ['model_name' => 'X1', 'brand_id' => $this->brand->id]);

        $this->actingAs($owner)->post('/admin/catalog/'.$source->id.'/clone')->assertRedirect();
        $clone = Product::query()->where('source_product_id', $source->id)->sole();
        self::assertSame('draft', $clone->status);
        self::assertNotSame($source->sku, $clone->sku);
        self::assertSame('X1', $clone->model_name);
    }

    public function test_draft_autosave_keeps_product_draft_and_form_gates_autosave_to_drafts(): void
    {
        $owner = $this->admin('auto-admin');
        $draft = $this->product('AUTO-1', 'Nacrt', $owner->id, ['status' => 'draft']);

        $this->actingAs($owner)->putJson('/admin/catalog/'.$draft->id, [
            'sku' => 'AUTO-1', 'name' => 'Nacrt izmenjen', 'price_currency' => 'EUR', 'status' => 'draft', 'save_draft' => '1', 'autosave' => '1',
            'product_type_id' => $this->type->id, 'brand_id' => $this->brand->id,
        ])->assertOk();
        $draft->refresh();
        self::assertSame('Nacrt izmenjen', $draft->name);
        self::assertSame('draft', $draft->status);
        self::assertSame(2, (int) $draft->stock_quantity);

        $html = $this->get('/admin/catalog/'.$draft->id.'/edit')->assertOk()->getContent();
        self::assertStringContainsString('data-autosave-status', $html);
        self::assertStringContainsString("root.dataset.initialProductStatus === 'draft'", $html);
        self::assertStringContainsString("data.set('save_draft', '1')", $html);
        self::assertStringContainsString("data.set('autosave', '1')", $html);
    }

    public function test_autosave_loses_the_race_against_a_concurrent_activation(): void
    {
        $owner = $this->admin('auto-race');
        $stale = $this->product('AUTO-R', 'Trka', $owner->id, ['status' => 'draft']);
        // Zahtev je prošao ProductRequest dok je artikal još bio nacrt (stale instanca), a aktivacija je stigla pre zaključavanja.
        Product::query()->whereKey($stale->id)->update(['status' => 'active']);

        try {
            app(\App\Services\ProductAdminService::class)->update($stale, [
                'sku' => 'AUTO-R', 'name' => 'Trka izmenjena', 'price_currency' => 'EUR', 'status' => 'draft',
            ], $owner, true);
            self::fail('Autosave nad aktiviranim artiklom mora biti odbijen.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            self::assertArrayHasKey('autosave', $exception->errors());
        }
        $fresh = Product::query()->findOrFail($stale->id);
        self::assertSame('active', $fresh->status);
        self::assertSame('Trka', $fresh->name);
    }

    public function test_autosave_returns_409_when_the_product_was_changed_elsewhere(): void
    {
        $owner = $this->admin('auto-409');
        $draft = $this->product('AUTO-V', 'Verzija', $owner->id, ['status' => 'draft']);
        $payload = ['sku' => 'AUTO-V', 'name' => 'Verzija 2', 'price_currency' => 'EUR', 'status' => 'draft', 'save_draft' => '1', 'autosave' => '1'];

        $this->actingAs($owner)->putJson('/admin/catalog/'.$draft->id, $payload + ['autosave_version' => $draft->updated_at->getTimestamp() - 60])->assertStatus(409);
        self::assertSame('Verzija', $draft->refresh()->name);

        $this->putJson('/admin/catalog/'.$draft->id, $payload + ['autosave_version' => $draft->updated_at->getTimestamp()])
            ->assertOk()->assertJsonStructure(['version']);
    }

    public function test_server_refuses_autosave_for_a_product_that_is_no_longer_a_draft(): void
    {
        $owner = $this->admin('auto-guard');
        $active = $this->product('AUTO-2', 'Aktivan', $owner->id, ['status' => 'active']);
        $payload = ['sku' => 'AUTO-2', 'name' => 'Aktivan', 'price_currency' => 'EUR', 'status' => 'draft', 'save_draft' => '1', 'autosave' => '1'];

        $this->actingAs($owner)->putJson('/admin/catalog/'.$active->id, $payload)->assertStatus(422)->assertJsonValidationErrors('autosave');
        self::assertSame('active', $active->refresh()->status);

        // Isti zahtev bez autosave oznake i dalje radi kao ručno "Sačuvaj kao nacrt".
        $manual = $this->product('AUTO-3', 'Ručno', $owner->id, ['status' => 'active']);
        $this->putJson('/admin/catalog/'.$manual->id, array_replace($payload, ['sku' => 'AUTO-3', 'name' => 'Ručno', 'autosave' => null]))->assertOk();
        self::assertSame('draft', $manual->refresh()->status);

        $superadmin = User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'auto-guard-api', 'email' => 'auto-guard-api@test.local',
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
        $this->actingAs($superadmin, 'sanctum')->putJson('/api/v1/admin/catalog/products/'.$active->id, $payload)
            ->assertStatus(422)->assertJsonValidationErrors('autosave');
        self::assertSame('active', $active->refresh()->status);
    }

    /** @param array<string,mixed> $override @return array<string,mixed> */
    private function payload(array $override = []): array
    {
        return array_replace([
            'product_type_id' => $this->type->id, 'brand_id' => $this->brand->id, 'product_line_id' => $this->line->id,
            'name' => 'ThinkPad T14', 'price_amount' => '999.99', 'price_currency' => 'EUR', 'description' => 'Test opis',
            'stock_quantity' => 1, 'low_stock_threshold' => 1, 'status' => 'draft',
        ], $override);
    }

    private function admin(string $username): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => $username, 'email' => $username.'@test.local',
            'password_hash' => Hash::make('Secret123!'), 'status' => 'active',
        ]);
    }

    /** @param array<string,mixed> $extra */
    private function product(string $sku, string $name, int $createdBy, array $extra = []): Product
    {
        return Product::query()->create($extra + [
            'sku' => $sku, 'name' => $name, 'slug' => strtolower($sku), 'brand_id' => $this->brand->id,
            'price_amount' => 100, 'price_currency' => 'EUR', 'description' => 'Opis', 'stock_quantity' => 2,
            'low_stock_threshold' => 1, 'status' => 'active', 'created_by' => $createdBy, 'updated_by' => $createdBy,
        ]);
    }
}
