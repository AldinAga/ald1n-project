<?php
declare(strict_types=1);
namespace Tests\Feature;

use App\Models\{Order,Product,PublicCheckoutAttempt,PublicOrderAccess,Role,User};
use App\Services\{PublicMerchantPolicyService,PublicOrderAccessService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Mail};
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Synthetic integration checks only; never a production attack or complete pentest. */
final class StorefrontSecurityB806Test extends TestCase
{
    use RefreshDatabase;
    private Product $product;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        // Throttling is a separate real-HTTP test obligation, not bypassed evidence.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        config()->set('app.url','https://cms.ald1n.com');
        foreach ([
            'enabled'=>true,'checkout_enabled'=>true,
            'terms_url'=>'https://cms.ald1n.com/uslovi',
            'privacy_url'=>'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd'=>'0','shipping_description'=>'Synthetic approved delivery.',
            'payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','own_transport','customer_pickup'],
            'support_email'=>'support@example.test',
        ] as $key=>$value) config()->set('public_storefront.'.$key,$value);
        $this->owner=User::query()->create([
            'role_id'=>Role::query()->where('slug','superadmin')->valueOrFail('id'),
            'username'=>'b806-owner','email'=>'owner@example.test','first_name'=>'Synthetic',
            'password_hash'=>Hash::make('SyntheticOnly123!'),'status'=>'active',
        ]);
        app(SettingsService::class)->putMany([
            'public_storefront_open'=>'1','documents_company_name'=>'Synthetic B806 Merchant',
            'documents_company_address'=>'Test 10','documents_company_city'=>'Beograd',
            'documents_company_tax_id'=>'100200300',
        ],$this->owner->id);
        $review=app(PublicMerchantPolicyService::class)->review();
        self::assertTrue($review['complete'],implode('; ',$review['issues']));
        app(SettingsService::class)->putMany([
            'public_checkout_approval_digest'=>$review['fingerprint'],
            'public_checkout_approved_by'=>(string)$this->owner->id,
            'public_checkout_approved_at'=>now()->toISOString(),
        ],$this->owner->id);
        $this->product=Product::query()->create([
            'sku'=>'B806-SECURITY','slug'=>'b806-security','name'=>'Synthetic security item',
            'description'=>'Synthetic product only','price_amount'=>12000,
            'price_currency'=>'RSD','stock_quantity'=>4,'purchase_price_rsd'=>987654,
            'manual_commission_eur'=>876543,'status'=>'active','created_by'=>$this->owner->id,
        ]);
        Mail::fake();
    }

    public static function origins(): array
    {
        return [
            'host'=>['https://injected.example.test',[]],
            'forwarded-host'=>['https://cms.ald1n.com',['X-Forwarded-Host'=>'injected.example.test']],
            'forwarded-protocol'=>['https://cms.ald1n.com',['X-Forwarded-Proto'=>'http','Forwarded'=>'host=injected.example.test;proto=http']],
        ];
    }

    #[DataProvider('origins')]
    public function test_receipt_email_origin_never_comes_from_untrusted_request_headers(string $origin,array $headers): void
    {
        $data=$this->form();
        $body=null;
        // Capture only outbound transport; real controllers, sessions, DB and stock run.
        Mail::shouldReceive('raw')->once()->andReturnUsing(static function ($message,$callback) use (&$body): void {
            $body=(string)$message;
        });
        $this->withHeaders($headers)->post($origin.'/porucivanje',$data)->assertRedirect();
        self::assertIsString($body);
        self::assertMatchesRegularExpression('~https://cms\.ald1n\.com/porudzbina/[a-f0-9]{64}~',$body);
        self::assertStringNotContainsString('injected.example.test',$body);
        self::assertStringNotContainsString('http://cms.ald1n.com',$body);
        self::assertDatabaseCount('orders',1);
        self::assertDatabaseCount('order_payments',0);
    }

    public function test_checkout_redirect_does_not_echo_untrusted_authority_with_private_capability(): void
    {
        $response=$this->post('https://injected.example.test/porucivanje',$this->form())->assertRedirect();
        $location=(string)$response->headers->get('Location');
        self::assertMatchesRegularExpression('~^/porudzbina/[a-f0-9]{64}$~',$location,
            'Receipt redirects must be origin-relative, not an attacker-supplied absolute host.');
    }

    public static function sessionPages(): array
    {
        return [['/korpa'],['/porucivanje']];
    }

    #[DataProvider('sessionPages')]
    public function test_session_cart_and_checkout_cannot_be_stored_by_browser_or_intermediary(string $path): void
    {
        $this->form();
        $response=$this->get($path)->assertOk();
        self::assertStringContainsString('no-store',(string)$response->headers->get('Cache-Control'));
        self::assertStringContainsString('private',(string)$response->headers->get('Cache-Control'));
    }

    public static function invalidInputs(): array
    {
        return [
            'forged-credit'=>[['payment_method'=>'deferred_payment'],'payment_method'],
            'array-method'=>[['payment_method'=>['cash_on_delivery']],'payment_method'],
            'invalid-delivery'=>[['delivery_method'=>'drone'],'delivery_method'],
            'stale-policy'=>[['merchant_policy_fingerprint'=>str_repeat('b',64)],'checkout'],
            'missing-consent'=>[['accepted_terms'=>'0'],'accepted_terms'],
            'foreign-nonce'=>[['checkout_key'=>str_repeat('c',48)],'checkout'],
            'honeypot'=>[['website'=>'https://example.test'],'website'],
        ];
    }

    #[DataProvider('invalidInputs')]
    public function test_forged_checkout_fields_do_not_mutate_inventory_or_create_sale(array $overrides,string $field): void
    {
        $data=array_replace($this->form(),$overrides);
        $this->postJson('/porucivanje',$data)->assertUnprocessable()->assertJsonValidationErrors($field);
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('stock_movements',0);
        self::assertDatabaseCount('public_checkout_attempts',0);
        self::assertDatabaseCount('order_payments',0);
        self::assertSame(4,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_posted_prices_privileges_paid_state_and_supplier_are_not_mass_assignable(): void
    {
        $data=$this->form()+[
            'price_amount'=>1,'subtotal_rsd'=>1,'paid_total_rsd'=>12000,
            'payment_state'=>'paid','status'=>'completed','role_id'=>$this->owner->role_id,
            'user_id'=>$this->owner->id,'supplier_user_id'=>999999,'commission_total_eur'=>999,
        ];
        $location=$this->post('/porucivanje',$data)->assertRedirect()->headers->get('Location');
        $order=Order::query()->sole();
        self::assertSame(12000.0,(float)$order->subtotal_rsd);
        self::assertSame('unpaid',$order->payment_state);
        self::assertSame('new',$order->status);
        self::assertNull($order->user_id);
        self::assertSame((int)$this->owner->id,(int)$order->supplier_user_id);
        self::assertDatabaseCount('order_payments',0);
        self::assertDatabaseCount('order_commissions',0);
        self::assertDatabaseCount('warranty_issuances',0);
        $this->get($location)->assertOk()->assertDontSee('987654')->assertDontSee('876543');
    }

    public function test_product_and_buyer_markup_is_encoded_in_public_html(): void
    {
        $payload='<svg onload="alert(806)">';
        $this->product->update(['name'=>$payload,'description'=>$payload]);
        $this->get('/katalog/b806-security')->assertOk()->assertDontSee($payload,false)
            ->assertSee(e($payload),false);
        $data=$this->form();$data['shipping_full_name']=$payload;
        $location=$this->post('/porucivanje',$data)->assertRedirect()->headers->get('Location');
        $this->get($location)->assertOk()->assertDontSee($payload,false)->assertSee(e($payload),false);
    }

    public function test_private_capability_is_hashed_expiring_revocable_and_not_numeric_order_access(): void
    {
        $location=$this->post('/porucivanje',$this->form())->assertRedirect()->headers->get('Location');
        $token=basename((string)parse_url((string)$location,PHP_URL_PATH));
        $access=PublicOrderAccess::query()->sole();
        self::assertSame(hash('sha256',$token),$access->token_hash);
        self::assertStringNotContainsString($token,$access->toJson());
        $this->get('/porudzbina/'.Order::query()->sole()->id)->assertNotFound();
        $this->get('/porudzbina/'.str_repeat('a',64))->assertNotFound();
        $this->get($location)->assertOk()->assertHeader('Referrer-Policy','no-referrer');
        $access->update(['expires_at'=>now()->subSecond()]);
        $this->get($location)->assertNotFound();
        $access->update(['expires_at'=>now()->addDay()]);
        app(PublicOrderAccessService::class)->revoke(Order::query()->sole());
        $this->get($location)->assertNotFound();
    }

    private function form(): array
    {
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])->assertRedirect();
        $html=$this->get('/porucivanje')->assertOk()->getContent();
        preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$html,$key);
        preg_match('/name="merchant_policy_fingerprint" value="([a-f0-9]{64})"/',$html,$policy);
        self::assertNotEmpty($key[1]??null);self::assertNotEmpty($policy[1]??null);
        return [
            'checkout_key'=>$key[1],'merchant_policy_fingerprint'=>$policy[1],
            'shipping_full_name'=>'Synthetic B806 Buyer','email'=>'buyer@example.test',
            'shipping_phone'=>'0601234567','shipping_address'=>'Test 12','shipping_city'=>'Beograd',
            'shipping_postal_code'=>'11000','payment_method'=>'cash_on_delivery',
            'delivery_method'=>'courier','accepted_terms'=>'1',
        ];
    }
}
