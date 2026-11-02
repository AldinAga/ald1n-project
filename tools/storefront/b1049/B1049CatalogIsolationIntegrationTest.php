<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * B1048 candidate REAL integration tests, to run only on a disposable
 * same-source Laravel 13 / MariaDB 10.11 main+M13+B1048 checkout.
 * NOT EXECUTED as part of the offline source-only QA receipt.
 */
final class B1049CatalogIsolationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('public_storefront.catalog_readonly_enabled', false);
        config()->set('public_storefront.enabled', false);
        config()->set('public_storefront.checkout_enabled', false);
    }

    private function openTestCatalog(): void
    {
        config()->set('public_storefront.catalog_readonly_enabled', true);
        app(SettingsService::class)->putMany(['public_catalog_readonly_open' => '1'], null);
    }

    public function test_strictly_off_with_no_new_catalogue_approval(): void
    {
        $this->get('/katalog')->assertNotFound();
        config()->set('public_storefront.catalog_readonly_enabled', true);
        $this->get('/katalog')->assertNotFound();
        app(SettingsService::class)->putMany(['public_catalog_readonly_open' => '1'], null);
        $this->get('/katalog')->assertOk();
    }

    public function test_catalog_only_cannot_unlock_any_purchase_route(): void
    {
        $this->openTestCatalog();
        $this->get('/katalog')->assertOk();
        $this->get('/korpa')->assertNotFound();
        $this->get('/porucivanje')->assertNotFound();
        $this->get('/registracija')->assertNotFound();
        $this->post('/korpa/dodaj/1', ['quantity' => 1])->assertNotFound();
        self::assertFalse(Route::getRoutes()->hasNamedRoute('public-shop.cart'));
        self::assertFalse(Route::getRoutes()->hasNamedRoute('public-shop.checkout'));
    }

    public function test_legacy_shop_environment_or_database_flag_forces_catalogue_off(): void
    {
        $this->openTestCatalog();
        $this->get('/katalog')->assertOk();
        config()->set('public_storefront.enabled', true);
        $this->get('/katalog')->assertNotFound();
        config()->set('public_storefront.enabled', false);
        app(SettingsService::class)->putMany(['public_storefront_open' => '1'], null);
        $this->get('/katalog')->assertNotFound();
    }

    public function test_checkout_enabled_environment_forces_catalogue_only_off(): void
    {
        $this->openTestCatalog();
        $this->get('/katalog')->assertOk();
        config()->set('public_storefront.checkout_enabled', true);
        $this->get('/katalog')->assertNotFound();
    }

    public function test_unexpected_new_buy_route_forces_catalogue_only_off(): void
    {
        $this->openTestCatalog();
        $this->get('/katalog')->assertOk();
        Route::post('/korpa/dodaj/{product}', static fn () => response('forbidden-ci-only'));
        $this->get('/katalog')->assertNotFound();
    }


    public function test_unnamed_post_route_nested_under_catalog_forces_off(): void
    {
        $this->openTestCatalog();
        $this->get('/katalog')->assertOk();
        Route::post('/katalog/checkout', static fn () => response('dangerous-writer-fixture'));
        $this->get('/katalog')->assertNotFound();
    }

    public function test_unknown_unnamed_get_under_catalog_forces_off(): void
    {
        $this->openTestCatalog();
        $this->get('/katalog')->assertOk();
        Route::get('/katalog/extra-preview', static fn () => response('unreviewed-view-fixture'));
        $this->get('/katalog')->assertNotFound();
    }

    public function test_emergency_off_revokes_catalogue_and_media_without_cache_delay(): void
    {
        $this->openTestCatalog();
        $this->get('/katalog')->assertOk();
        $owner = User::query()->firstOrCreate(['username' => 'b1048-qa-only'], [
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'email' => 'b1048-qa@example.test',
            'password_hash' => Hash::make('NeverUseThisInProduction-B1048'),
            'status' => 'active',
        ]);
        $p = Product::query()->create([
            'created_by' => $owner->id,
            'sku' => 'B1048-QA-ONLY', 'slug' => 'b1048-qa',
            'name' => 'Synthetic QA', 'price_amount' => 100,
            'price_currency' => 'RSD', 'stock_quantity' => 1,
            'status' => 'active',
        ]);
        $this->get('/katalog/b1048-qa')->assertOk();
        app(SettingsService::class)->putMany(['public_catalog_readonly_open' => '0'], null);
        $this->get('/katalog')->assertNotFound();
        $this->get('/katalog/b1048-qa')->assertNotFound();
        self::assertSame(1, (int) $p->fresh()->stock_quantity);
    }
}
