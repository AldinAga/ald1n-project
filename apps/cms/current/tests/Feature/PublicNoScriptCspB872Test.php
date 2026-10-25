<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Order,Product,ProductImage,Role,User};
use App\Services\{PublicMerchantPolicyService,PublicStorefrontStateService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\{Hash,Mail,Storage};
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * B872-B875: real Laravel HTTP on synthetic MariaDB.
 * No real SMTP/payment/customer data, remote browser, or production activation.
 * This is a bounded no-script CSP scope, NOT ASVS V3.4.3 global PASS.
 */
final class PublicNoScriptCspB872Test extends TestCase
{
    use RefreshDatabase;
    private User $owner;
    private User $buyer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        Mail::fake();
        config()->set('app.url','https://cms.ald1n.com');
        config()->set('services.turnstile.enabled',false);
        foreach ([
            'enabled'=>true,
            'checkout_enabled'=>true,
            'terms_url'=>'https://cms.ald1n.com/uslovi',
            'privacy_url'=>'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd'=>'0',
            'shipping_description'=>'B872 synthetic approved shipping',
            'payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','own_transport','customer_pickup'],
            'support_email'=>'support@example.test',
        ] as $name=>$value) config()->set('public_storefront.'.$name,$value);
        $this->owner=$this->person('b872-owner','superadmin');
        $this->buyer=$this->person('b872-buyer','customer');
        $settings=app(SettingsService::class);
        $settings->putMany([
            'public_storefront_open'=>'1',
            'documents_company_name'=>'B872 Synthetic Merchant',
            'documents_company_address'=>'Test 10',
            'documents_company_city'=>'Beograd',
            'documents_company_tax_id'=>'100200300',
            'eur_rsd_rate'=>'117.78',
        ],(int)$this->owner->id);
        $review=app(PublicMerchantPolicyService::class)->review();
        self::assertTrue($review['complete'],implode('; ',$review['issues']));
        $settings->putMany([
            'public_checkout_approval_digest'=>$review['fingerprint'],
            'public_checkout_approved_by'=>(string)$this->owner->id,
            'public_checkout_approved_at'=>now()->toISOString(),
        ],(int)$this->owner->id);
        app(PublicStorefrontStateService::class)->change(true,$this->owner);
        $this->product=Product::query()->create([
            'sku'=>'B872-SYNTHETIC-ONLY','slug'=>'b872-scriptless',
            'name'=>'B872 Synthetic Scriptless Product',
            'description'=>'B872 disposable real HTTP source only',
            'price_amount'=>12500,'price_currency'=>'RSD',
            'stock_quantity'=>4,'status'=>'active',
            'created_by'=>$this->owner->id,
        ]);
    }

    public static function publicHtml(): array
    {
        return [
            'catalog'=>['/katalog'],
            'product'=>['/katalog/b872-scriptless'],
            'cart'=>['/korpa'],
        ];
    }

    #[DataProvider('publicHtml')]
    public function test_scriptless_public_html_has_enforced_policy_and_framing_denial(string $uri): void
    {
        $response=$this->get($uri)->assertOk();
        $this->assertStrict($response);
        self::assertStringNotContainsString('<script',strtolower($response->getContent()));
        self::assertStringNotContainsString('<iframe',strtolower($response->getContent()));
    }

    public function test_cart_write_redirect_unchanged_and_cart_get_remains_scriptless(): void
    {
        $post=$this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])
            ->assertRedirect('/korpa');
        $this->assertGlobalOnly($post);
        $this->assertStrict($this->get('/korpa')->assertOk()
            ->assertSee('B872 Synthetic Scriptless Product'));
    }

    public function test_verified_buyer_checkout_form_is_scriptless_and_keeps_real_nonce(): void
    {
        $this->loginBuyer();
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])
            ->assertRedirect('/korpa');
        $response=$this->get('/porucivanje')->assertOk();
        $this->assertStrict($response);
        self::assertMatchesRegularExpression('/name="checkout_key" value="[a-f0-9]{48}"/',$response->getContent());
        self::assertMatchesRegularExpression('/name="_token" type="hidden"|name="_token" value=/',$response->getContent());
        self::assertDatabaseCount('orders',0);
        self::assertSame(4,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_registered_buyer_receipt_keeps_no_store_with_scriptless_policy(): void
    {
        $this->loginBuyer();
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])
            ->assertRedirect('/korpa');
        $html=$this->get('/porucivanje')->assertOk()->getContent();
        self::assertSame(1,preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$html,$nonce));
        self::assertSame(1,preg_match('/name="merchant_policy_fingerprint" value="([a-f0-9]{64})"/',$html,$digest));
        $response=$this->post('/porucivanje',[
            'checkout_key'=>$nonce[1],
            'merchant_policy_fingerprint'=>$digest[1],
            'shipping_full_name'=>'B872 Synthetic Buyer',
            'email'=>$this->buyer->email,
            'shipping_phone'=>'0601234567',
            'shipping_address'=>'Synthetic 12',
            'shipping_city'=>'Beograd',
            'shipping_postal_code'=>'11000',
            'payment_method'=>'cash_on_delivery',
            'delivery_method'=>'courier',
            'accepted_terms'=>'1',
        ])->assertRedirect();
        $location=(string)$response->headers->get('Location');
        self::assertMatchesRegularExpression('~^/porudzbina/[a-f0-9]{64}$~',$location);
        $receipt=$this->get($location)->assertOk();
        $this->assertStrict($receipt);
        $receipt->assertHeader('Referrer-Policy','no-referrer');
        self::assertStringContainsString('no-store',(string)$receipt->headers->get('Cache-Control'));
        self::assertStringNotContainsString(basename($location),
            (string)$receipt->headers->get('Content-Security-Policy'));
        self::assertDatabaseCount('orders',1);
        self::assertDatabaseCount('public_order_accesses',1);
        self::assertSame(3,(int)$this->product->fresh()->stock_quantity);
    }

    public static function externalScriptOrAuthRoutes(): array
    {
        return [
            'registration-turnstile-exempt'=>['/registracija'],
            'legacy-login-theme-exempt'=>['/login'],
        ];
    }

    #[DataProvider('externalScriptOrAuthRoutes')]
    public function test_registration_and_legacy_auth_keep_global_policy(string $uri): void
    {
        $response=$this->get($uri)->assertOk();
        $this->assertGlobalOnly($response);
        $response->assertHeader('X-Frame-Options','SAMEORIGIN');
    }

    public function test_invalid_catalog_json_keeps_global_not_html_policy(): void
    {
        $response=$this->getJson('/katalog?sort=injected')->assertUnprocessable()
            ->assertJsonValidationErrors('sort');
        $this->assertGlobalOnly($response);
        self::assertStringContainsString('application/json',
            (string)$response->headers->get('Content-Type'));
    }

    public function test_off_does_not_transform_404_to_apparently_public_html(): void
    {
        config()->set('public_storefront.enabled',false);
        $response=$this->get('/katalog')->assertNotFound();
        $this->assertGlobalOnly($response);
        self::assertDatabaseCount('orders',0);
        self::assertSame(4,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_public_png_binary_preserves_existing_media_mime_and_headers(): void
    {
        Storage::fake('public');
        $path='products/'.$this->product->id.'/b872.png';
        Storage::disk('public')->put($path,(string)base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/ak8AAAAASUVORK5CYII='
        ));
        $image=ProductImage::query()->create([
            'product_id'=>$this->product->id,
            'storage_disk'=>'public',
            'file_path'=>$path,
            'original_filename'=>'b872.png',
            'mime_type'=>'image/png',
            'file_size'=>67,
            'file_hash'=>str_repeat('a',64),
            'sort_order'=>1,
            'is_primary'=>true,
            'created_at'=>now(),
        ]);
        $response=$this->get('/katalog/slika/'.$image->id)->assertOk()
            ->assertHeader('Content-Type','image/png')
            ->assertHeader('X-Content-Type-Options','nosniff');
        $this->assertGlobalOnly($response);
    }

    public function test_untrusted_origin_never_enters_strict_policy_or_cors(): void
    {
        $response=$this->withHeaders([
            'Origin'=>'https://evil.synthetic.example.test',
            'X-Forwarded-Host'=>'evil.synthetic.example.test',
        ])->get('/katalog')->assertOk();
        $this->assertStrict($response);
        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
        self::assertStringNotContainsString('evil.synthetic.example.test',
            (string)$response->headers->get('Content-Security-Policy'));
    }

    public function test_anonymous_checkout_redirect_is_not_granted_html_policy(): void
    {
        $response=$this->get('/porucivanje')->assertRedirect('https://cms.ald1n.com/login');
        $this->assertGlobalOnly($response);
        self::assertDatabaseCount('orders',0);
    }

    private function assertStrict(TestResponse $response): void
    {
        $policy=(string)$response->headers->get('Content-Security-Policy');
        foreach ([
            "default-src 'none'", "script-src 'none'",
            "script-src-attr 'none'", "script-src-elem 'none'",
            "base-uri 'none'", "object-src 'none'",
            "frame-ancestors 'none'", "frame-src 'none'",
            "form-action 'self'", "style-src 'self'",
            "img-src 'self' data:", "connect-src 'none'",
        ] as $must) self::assertStringContainsString($must,$policy);
        self::assertStringNotContainsString("'unsafe-inline'",$policy);
        self::assertStringNotContainsString("'unsafe-eval'",$policy);
        self::assertStringNotContainsString('challenges.cloudflare.com',$policy);
        $response->assertHeader('X-Frame-Options','DENY');
        $response->assertHeader('X-Content-Type-Options','nosniff');
        self::assertFalse($response->headers->has('Content-Security-Policy-Report-Only'));
    }

    private function assertGlobalOnly(TestResponse $response): void
    {
        $policy=(string)$response->headers->get('Content-Security-Policy');
        self::assertStringContainsString("base-uri 'none'",$policy);
        self::assertStringContainsString("frame-ancestors 'self'",$policy);
        self::assertStringNotContainsString("script-src 'none'",$policy);
        self::assertStringNotContainsString("default-src 'none'",$policy);
    }

    private function loginBuyer(): void
    {
        $this->post('/login',[
            'login'=>$this->buyer->email,
            'password'=>'B872-Synthetic-Only123!',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($this->buyer);
    }

    private function person(string $name,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$name,
            'email'=>$name.'@example.test',
            'first_name'=>'B872 Synthetic',
            'password_hash'=>Hash::make('B872-Synthetic-Only123!'),
            'status'=>'active',
            'email_verified_at'=>now(),
            'portal_activated_at'=>now(),
        ]);
    }
}
