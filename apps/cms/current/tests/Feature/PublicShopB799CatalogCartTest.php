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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PublicShopB799CatalogCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('public_storefront.enabled', true);
        app(SettingsService::class)->putMany(['eur_rsd_rate'=>'117.78'], null);
    }

    public function test_release_gate_is_closed_by_default_and_internal_routes_remain_private(): void
    {
        $this->createProduct('sensitive-visible', 'Vidljiv artikal');
        config()->set('public_storefront.enabled', false);
        $this->get('/katalog')->assertNotFound();
        $this->get('/korpa')->assertNotFound();
        $this->get('/katalog/sensitive-visible')->assertNotFound();
        $this->post('/korpa/dodaj/1', ['quantity'=>1])->assertNotFound();
        config()->set('public_storefront.enabled', true);
        $this->get('/catalog')->assertRedirect('/login');
        $this->get('/admin/orders')->assertRedirect('/login');
    }

    public function test_guest_catalog_uses_strict_active_product_filter_and_never_exposes_costs_or_commissions(): void
    {
        $this->createProduct('listed', 'Dostupni laptop', ['purchase_price_rsd'=>1000,'manual_commission_eur'=>900]);
        $this->createProduct('draft', 'Tajni nacrt', ['status'=>'draft']);
        $this->createProduct('inactive', 'Neaktivan laptop', ['status'=>'inactive']);
        $this->createProduct('archived', 'Arhivirani laptop', ['deleted_at'=>now()]);
        $this->createProduct('no-price', 'Bez prodajne cene', ['price_amount'=>0]);
        $this->get('/katalog')->assertOk()
            ->assertSee('Dostupni laptop')
            ->assertDontSee('Tajni nacrt')
            ->assertDontSee('Neaktivan laptop')
            ->assertDontSee('Arhivirani laptop')
            ->assertDontSee('Bez prodajne cene')
            ->assertDontSee('manual_commission')
            ->assertDontSee('purchase_price')
            ->assertDontSee('900,00');
        $this->get('/katalog/draft')->assertNotFound();
        $this->get('/katalog/archived')->assertNotFound();
    }

    public function test_product_details_show_full_retail_eur_price_without_discount_and_respect_missing_rate(): void
    {
        $this->createProduct('precision', 'Dell Precision', [
            'price_amount'=>199.00,'price_currency'=>'EUR','manual_commission_eur'=>123.00,
        ]);
        $this->get('/katalog/precision')->assertOk()
            ->assertSee('199,00 EUR')
            ->assertDontSee('123,00')
            ->assertDontSee('purchase_price_rsd');
        app(SettingsService::class)->putMany(['eur_rsd_rate'=>''], null);
        $this->get('/katalog/precision')->assertOk()->assertDontSee('Dodaj u korpu');
    }

    public function test_session_cart_does_not_reserve_stock_and_reprices_from_current_db(): void
    {
        $p=$this->createProduct('retail-price', 'Retail cena', [
            'price_amount'=>1000,'price_currency'=>'RSD','stock_quantity'=>5,
        ]);
        $this->post('/korpa/dodaj/'.$p->id,['quantity'=>2])->assertRedirect('/korpa');
        $this->get('/korpa')->assertOk()
            ->assertSee('2.000,00 RSD')
            ->assertSee('Retail cena');
        self::assertSame(5,(int)$p->fresh()->stock_quantity);
        $p->update(['price_amount'=>1200]);
        $this->get('/korpa')->assertOk()->assertSee('2.400,00 RSD');
        $this->patch('/korpa/'.$p->id,['quantity'=>3])->assertRedirect('/korpa');
        $this->get('/korpa')->assertSee('3.600,00 RSD');
        $this->delete('/korpa/'.$p->id)->assertRedirect('/korpa');
        $this->get('/korpa')->assertSee('Korpa je prazna');
        self::assertSame(0,\App\Models\Order::query()->count());
    }

    public function test_cart_rejects_invalid_stock_and_never_leaks_hidden_items(): void
    {
        $p=$this->createProduct('available-qty', 'Rasprodaje se', ['stock_quantity'=>1]);
        $this->post('/korpa/dodaj/'.$p->id,['quantity'=>2])->assertSessionHasErrors('cart');
        $this->post('/korpa/dodaj/'.$p->id,['quantity'=>1])->assertRedirect();
        $p->update(['status'=>'archived']);
        $this->get('/korpa')->assertOk()->assertDontSee('Rasprodaje se')
            ->assertSee('Neki artikli su promenjeni');
        $this->get('/katalog/available-qty')->assertNotFound();
    }

    public function test_guest_cannot_use_internal_product_media_urls_and_public_media_checks_product(): void
    {
        $p=$this->createProduct('media-visible', 'Slika dostupna');
        Storage::fake('public');
        Storage::disk('public')->put('products/'.$p->id.'/a.png', base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/ak8AAAAASUVORK5CYII='
        ));
        $image=ProductImage::query()->create([
            'product_id'=>$p->id,'storage_disk'=>'public',
            'file_path'=>'products/'.$p->id.'/a.png','original_filename'=>'a.png',
            'mime_type'=>'image/png','file_size'=>64,'file_hash'=>str_repeat('a',64),
            'sort_order'=>1,'is_primary'=>true,'created_at'=>now(),
        ]);
        $this->get('/katalog/slika/'.$image->id)->assertOk()->assertHeader('Content-Type','image/png');
        $this->get('/media/product/'.$image->id)->assertRedirect('/login');
        $p->update(['status'=>'inactive']);
        $this->get('/katalog/slika/'.$image->id)->assertNotFound();
    }

    public function test_long_names_and_untrusted_html_are_escaped_in_public_view(): void
    {
        $this->createProduct('unsafe', '<script>alert("SECRET")</script>', [
            'description'=>'<img src=x onerror=alert(1)>',
        ]);
        $response=$this->get('/katalog/unsafe')->assertOk();
        $response->assertSee('&lt;script&gt;',false)
            ->assertDontSee('<script>alert(',false)
            ->assertDontSee('<img src=x onerror',false);
    }

    private function createProduct(string $slug, string $name, array $overrides=[]): Product
    {
        return Product::query()->create(array_replace([
            'sku'=>strtoupper($slug),'slug'=>$slug,'name'=>$name,
            'price_amount'=>1000,'price_currency'=>'RSD',
            'description'=>'Javni opis', 'stock_quantity'=>3, 'status'=>'active',
        ],$overrides));
    }
}
