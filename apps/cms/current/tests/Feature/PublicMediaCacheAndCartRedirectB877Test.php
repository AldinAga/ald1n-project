<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Product,ProductImage,Role,User};
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\{Hash,Storage};
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * B877-B879: public dynamic media cache revocation and relative cart redirects.
 * Real isolated Laravel HTTP+MariaDB with synthetic item/image only.
 * No modification to historical guest-only or provider-denied security fixtures.
 */
final class PublicMediaCacheAndCartRedirectB877Test extends TestCase
{
    use RefreshDatabase;

    private Product $product;
    private ProductImage $image;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('app.url','https://cms.ald1n.com');
        config()->set('public_storefront.enabled',true);
        app(SettingsService::class)->putMany(['public_storefront_open'=>'1'],null);
        Storage::fake('public');

        $owner=User::query()->create([
            'role_id'=>Role::query()->where('slug','superadmin')->valueOrFail('id'),
            'username'=>'b877-owner',
            'email'=>'b877-owner@example.test',
            'first_name'=>'B877 Synthetic Owner',
            'password_hash'=>Hash::make('B877-Test-Only!'),
            'status'=>'active',
        ]);
        $this->product=Product::query()->create([
            'sku'=>'B877-SYNTHETIC-ONLY','slug'=>'b877-synthetic-only',
            'name'=>'B877 Synthetic Dynamic Media Product',
            'description'=>'Disposable HTTP test product',
            'price_amount'=>9500,'price_currency'=>'RSD',
            'stock_quantity'=>4,'status'=>'active','created_by'=>$owner->id,
        ]);
        $relative='products/'.$this->product->id.'/b877.png';
        Storage::disk('public')->put($relative,(string)base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/ak8AAAAASUVORK5CYII='
        ));
        $this->image=ProductImage::query()->create([
            'product_id'=>$this->product->id,'storage_disk'=>'public',
            'file_path'=>$relative,'original_filename'=>'b877.png',
            'mime_type'=>'image/png','file_size'=>67,
            'file_hash'=>str_repeat('a',64),'sort_order'=>1,
            'is_primary'=>true,'created_at'=>now(),
        ]);
    }

    public function test_successful_dynamic_image_is_not_publicly_cacheable(): void
    {
        $response=$this->get($this->imagePath())->assertOk()
            ->assertHeader('Content-Type','image/png')
            ->assertHeader('X-Content-Type-Options','nosniff');
        $this->assertPrivateNoStore($response);
        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
        self::assertDatabaseCount('orders',0);
    }

    public function test_public_env_emergency_off_returns_uncacheable_media_404(): void
    {
        $this->assertPrivateNoStore($this->get($this->imagePath())->assertOk());
        config()->set('public_storefront.enabled',false);
        $this->assertPrivateNoStore($this->get($this->imagePath())->assertNotFound());
        config()->set('public_storefront.enabled',true);
        $this->assertPrivateNoStore($this->get($this->imagePath())->assertOk());
        self::assertSame(4,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_db_emergency_off_returns_uncacheable_media_404(): void
    {
        $this->assertPrivateNoStore($this->get($this->imagePath())->assertOk());
        app(SettingsService::class)->putMany(['public_storefront_open'=>'0'],null);
        $this->assertPrivateNoStore($this->get($this->imagePath())->assertNotFound());
        app(SettingsService::class)->putMany(['public_storefront_open'=>'1'],null);
        $this->assertPrivateNoStore($this->get($this->imagePath())->assertOk());
    }

    public function test_missing_and_hidden_dynamic_images_are_no_store_404(): void
    {
        $this->assertPrivateNoStore($this->get('/katalog/slika/999999')->assertNotFound());
        $this->product->forceFill(['status'=>'inactive'])->save();
        $this->assertPrivateNoStore($this->get($this->imagePath())->assertNotFound());
        $this->product->forceFill(['status'=>'active','price_amount'=>0])->save();
        $this->assertPrivateNoStore($this->get($this->imagePath())->assertNotFound());
    }

    public function test_add_cart_redirect_is_relative_and_preserves_flash_message(): void
    {
        $response=$this->post($this->addPath(),['quantity'=>1])
            ->assertRedirect('/korpa');
        self::assertSame('/korpa',$response->headers->get('Location'));
        $this->get('/korpa')->assertOk()
            ->assertSee('B877 Synthetic Dynamic Media Product')
            ->assertSee('Artikal je dodat u korpu');
        self::assertDatabaseCount('orders',0);
    }

    public function test_cart_update_and_remove_use_relative_internal_location(): void
    {
        $this->post($this->addPath(),['quantity'=>1])->assertRedirect('/korpa');
        $update=$this->patch(route('public-shop.cart.update',['product'=>$this->product->id],false),['quantity'=>2]);
        // Route path spelling is asserted against the actual named route
        // to avoid assuming URL text beyond current route definitions.
        $update->assertRedirect('/korpa');
        self::assertSame('/korpa',$update->headers->get('Location'));
        $html=$this->get('/korpa')->assertOk()->getContent();
        self::assertStringContainsString('value="2"',$html);
        $removed=$this->delete(route('public-shop.cart.remove',['product'=>$this->product->id],false))
            ->assertRedirect('/korpa');
        self::assertSame('/korpa',$removed->headers->get('Location'));
        $this->get('/korpa')->assertOk()->assertSee('Korpa je prazna');
        self::assertSame(4,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_attacker_host_and_forwarded_host_never_poison_add_redirect(): void
    {
        $response=$this->withHeaders([
            'Host'=>'evil.synthetic.invalid',
            'X-Forwarded-Host'=>'evil-forwarded.synthetic.invalid',
            'X-Forwarded-Proto'=>'http',
            'Origin'=>'https://evil-origin.synthetic.invalid',
        ])->post($this->addPath(),['quantity'=>1])->assertRedirect('/korpa');
        self::assertSame('/korpa',$response->headers->get('Location'));
        self::assertStringNotContainsString('evil',
            (string)$response->headers->get('Location'));
    }

    public function test_attacker_host_never_poison_update_or_remove_redirect(): void
    {
        $this->post($this->addPath(),['quantity'=>1])->assertRedirect('/korpa');
        $this->withHeaders([
            'Host'=>'evil.synthetic.invalid',
            'X-Forwarded-Host'=>'evil-forwarded.synthetic.invalid',
        ]);
        $update=$this->patch(route('public-shop.cart.update',['product'=>$this->product->id],false),
            ['quantity'=>2])->assertRedirect('/korpa');
        $remove=$this->delete(route('public-shop.cart.remove',['product'=>$this->product->id],false))
            ->assertRedirect('/korpa');
        foreach ([$update,$remove] as $response) {
            self::assertSame('/korpa',$response->headers->get('Location'));
            self::assertStringNotContainsString('evil',(string)$response->headers->get('Location'));
        }
        self::assertDatabaseCount('orders',0);
    }

    public function test_emergency_off_blocks_cart_mutation_and_private_no_store_is_preserved(): void
    {
        config()->set('public_storefront.enabled',false);
        $response=$this->post($this->addPath(),['quantity'=>1])->assertNotFound();
        $this->assertPrivateNoStore($response);
        self::assertDatabaseCount('orders',0);
        self::assertSame(4,(int)$this->product->fresh()->stock_quantity);
    }

    private function imagePath(): string
    {
        return route('public-shop.media',['image'=>$this->image->id],false);
    }

    private function addPath(): string
    {
        return route('public-shop.cart.add',['product'=>$this->product->id],false);
    }

    private function assertPrivateNoStore(TestResponse $response): void
    {
        $cache=strtolower((string)$response->headers->get('Cache-Control'));
        foreach (['private','no-store','max-age=0'] as $fragment) {
            self::assertStringContainsString($fragment,$cache);
        }
        self::assertStringNotContainsString('public',$cache);
        $response->assertHeader('Pragma','no-cache');
        self::assertStringContainsString('noindex',
            (string)$response->headers->get('X-Robots-Tag'));
        $response->assertHeader('X-Content-Type-Options','nosniff');
    }
}
