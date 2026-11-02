<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** B1046 disposable main + exact PR111 M13 source. Never committed into active CMS tests. */
final class B1046M13ReadOnlyCatalogIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('app.url', 'https://cms.ald1n.com');
        config()->set('public_storefront.enabled', false);
        config()->set('public_storefront.checkout_enabled', false);
    }

    public function test_off_by_default_and_no_checkout_or_cart_routes_exist(): void
    {
        $this->get('/katalog')->assertNotFound();
        $this->get('/katalog/example')->assertNotFound();
        $this->post('/korpa/dodaj/1', ['quantity' => 1])->assertNotFound();
        self::assertFalse(app('router')->getRoutes()->hasNamedRoute('public-shop.checkout'));
        self::assertFalse(app('router')->getRoutes()->hasNamedRoute('public-shop.cart'));
        self::assertFalse(app('router')->getRoutes()->hasNamedRoute('public-shop.cart.add'));
        $this->get('/catalog')->assertRedirect('/login');
    }

    public function test_enabled_synthetic_public_listing_excludes_hidden_prices_and_internal_data(): void
    {
        $this->openSyntheticCatalog();
        $this->product('listed', 'B1046 Visible Laptop', [
            'purchase_price_rsd' => 141.5, 'manual_commission_eur' => 9876.0,
        ]);
        $this->product('draft', 'B1046 Secret Draft', ['status' => 'draft']);
        $this->product('archive', 'B1046 Archived', ['deleted_at' => now()]);
        $this->product('unpriced', 'B1046 Unpriced', ['price_amount' => 0]);
        $r = $this->get('/katalog')->assertOk()
            ->assertSee('B1046 Visible Laptop')
            ->assertDontSee('B1046 Secret Draft')
            ->assertDontSee('B1046 Archived')
            ->assertDontSee('B1046 Unpriced')
            ->assertDontSee('purchase_price_rsd')
            ->assertDontSee('manual_commission_eur')
            ->assertDontSee('9876,00')
            ->assertDontSee('Dodaj u korpu')
            ->assertDontSee('Kupovina nije zavrsena');
        self::assertStringNotContainsString('action="/korpa', $r->getContent());
        $this->get('/katalog/draft')->assertNotFound();
        $this->get('/katalog/unpriced')->assertNotFound();
        app(SettingsService::class)->putMany(['public_storefront_open'=>'0'], null);
        $this->get('/katalog')->assertNotFound();
    }

    public function test_catalog_search_uses_literal_wildcards_and_validated_filters(): void
    {
        $this->openSyntheticCatalog();
        $this->product('percent', 'B1046 Exact 25% Mark');
        $this->product('underscore', 'B1046 ABC_DEF');
        $this->product('ordinary', 'B1046 Plain Product');
        $this->get('/katalog?q=%25')->assertOk()
            ->assertSee('B1046 Exact 25% Mark')
            ->assertDontSee('B1046 Plain Product');
        $this->get('/katalog?q=_')->assertOk()
            ->assertSee('B1046 ABC_DEF')
            ->assertDontSee('B1046 Plain Product');
        $this->getJson('/katalog?sort=price_amount%20DESC')->assertUnprocessable()
            ->assertJsonValidationErrors('sort');
        $this->getJson('/katalog?brand_id=1%20OR%201%3D1')->assertUnprocessable()
            ->assertJsonValidationErrors('brand_id');
        $this->getJson('/katalog?q%5B%5D=value')->assertUnprocessable()
            ->assertJsonValidationErrors('q');
    }

    public function test_product_detail_escapes_untrusted_html_and_has_no_buy_cta(): void
    {
        $this->openSyntheticCatalog();
        $this->product('escape', '<script>alert(42)</script>', [
            'description' => '<img src=x onerror=alert(42)>',
        ]);
        $this->get('/katalog/escape')->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert(42)</script>', false)
            ->assertDontSee('<img src=x onerror=alert(42)>', false)
            ->assertDontSee('Dodaj u korpu');
    }

    public function test_media_is_private_no_store_and_revoked_on_emergency_off(): void
    {
        $this->openSyntheticCatalog();
        $p = $this->product('image', 'B1046 Public Image');
        Storage::fake('public');
        $path = 'products/'.$p->id.'/image.png';
        Storage::disk('public')->put($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/ak8AAAAASUVORK5CYII='
        ));
        $img = ProductImage::query()->create([
            'product_id' => $p->id, 'storage_disk' => 'public',
            'file_path' => $path, 'original_filename' => 'image.png',
            'mime_type' => 'image/png', 'file_size' => 67,
            'file_hash' => str_repeat('a', 64), 'sort_order' => 1,
            'is_primary' => true, 'created_at' => now(),
        ]);
        $this->get('/katalog/slika/'.$img->id)->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'private, no-store, max-age=0');
        app(SettingsService::class)->putMany(['public_storefront_open'=>'0'], null);
        $this->get('/katalog/slika/'.$img->id)->assertNotFound();
    }

    private function openSyntheticCatalog(): void
    {
        config()->set('public_storefront.enabled', true);
        config()->set('public_storefront.checkout_enabled', false);
        app(SettingsService::class)->putMany([
            'public_storefront_open' => '1',
            'eur_rsd_rate' => '117.78',
        ], null);
    }

    private function product(string $slug, string $name, array $overrides = []): Product
    {
        $owner = User::query()->firstOrCreate(['username'=>'b1046-qa-owner'], [
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'email' => 'b1046-owner@example.test',
            'password_hash' => Hash::make('B1046-Synthetic-Only-Not-Production!'),
            'status' => 'active',
        ]);
        return Product::query()->create(array_replace([
            'created_by' => $owner->id,
            'sku' => 'B1046-'.strtoupper($slug),
            'slug' => $slug, 'name' => $name,
            'description' => 'Only synthetic data',
            'price_amount' => 14990, 'price_currency' => 'RSD',
            'stock_quantity' => 3, 'status' => 'active',
        ], $overrides));
    }
}
