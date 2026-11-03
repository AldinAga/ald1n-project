<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Product,Role,User};
use App\Services\{PublicMerchantPolicyService,PublicStorefrontStateService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Hash,Mail};
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Real HTTP checkout and SQL writes, but synthetic data in disposable MariaDB only. */
final class RegisteredStorefrontFlowB1075Test extends TestCase
{
    use RefreshDatabase;
    private User $owner;
    private User $buyer;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Mail::fake();
        config()->set('app.url','https://cms.ald1n.com');
        config()->set('services.turnstile.enabled',false);
        foreach ([
            'enabled'=>true, 'checkout_enabled'=>true,
            'terms_url'=>'https://cms.ald1n.com/uslovi',
            'privacy_url'=>'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd'=>'0',
            'shipping_description'=>'B1075 disposable shipping',
            'payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','own_transport','customer_pickup'],
            'support_email'=>'support@example.test',
        ] as $key=>$value) config()->set('public_storefront.'.$key,$value);
        $this->owner=$this->person('b1073-owner','superadmin');
        $this->buyer=$this->person('b1073-buyer','customer');
        $settings=app(SettingsService::class);
        $settings->putMany([
            'documents_company_name'=>'B1075 Synthetic Merchant',
            'documents_company_address'=>'Test 10',
            'documents_company_city'=>'Beograd',
            'documents_company_tax_id'=>'100200300',
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
            'sku'=>'B1075-SYNTHETIC-ONLY',
            'slug'=>'b1073-synthetic-only',
            'name'=>'B1075 Disposable Laptop',
            'description'=>'Disposable database fixture only',
            'price_amount'=>12000,'price_currency'=>'RSD',
            'stock_quantity'=>4,'purchase_price_rsd'=>9000,
            'status'=>'active','created_by'=>$this->owner->id,
        ]);
        $this->post('/login',[
            'login'=>$this->buyer->email,'password'=>'B1075-Synthetic-Only123!',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($this->buyer);
    }


    public function test_verified_buyer_catalog_cart_checkout_and_replay_preserve_inventory_and_identity(): void
    {
        $this->get('/katalog')->assertOk()->assertSee('B1075 Disposable Laptop');
        $this->get('/katalog/'.$this->product->slug)->assertOk();
        $payload=$this->checkoutPayload();
        $response=$this->post('/porucivanje',$payload+['subtotal_rsd'=>'1','user_id'=>$this->owner->id])->assertRedirect();
        $receipt=(string)$response->headers->get('Location');
        $this->get($receipt)->assertOk()->assertSee('B1075 Disposable Laptop');
        $order=DB::table('orders')->sole();
        self::assertSame((int)$this->buyer->id,(int)$order->user_id);
        self::assertSame(12000.0,(float)$order->subtotal_rsd);
        self::assertSame('unpaid',$order->payment_state);
        self::assertSame(3,(int)$this->product->fresh()->stock_quantity);
        $replay=$this->post('/porucivanje',$payload)->assertRedirect();
        self::assertSame($receipt,$replay->headers->get('Location'));
        foreach(['orders','order_items','stock_movements','public_checkout_attempts'] as $table)self::assertDatabaseCount($table,1);
        foreach(['order_payments','order_commissions'] as $table)self::assertDatabaseCount($table,0);
        self::assertSame(3,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_anonymous_catalog_and_cart_do_not_authorize_checkout_or_stock_writes(): void
    {
        $this->post('/logout')->assertRedirect();
        $this->get('/katalog')->assertOk();
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])->assertRedirect();
        $this->get('/korpa')->assertOk();
        $this->get('/porucivanje')->assertRedirect();
        $this->postJson('/porucivanje',[])->assertUnauthorized();
        foreach(['orders','order_items','stock_movements','public_checkout_attempts','order_payments'] as $table)self::assertDatabaseCount($table,0);
        self::assertSame(4,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_checkout_reprices_current_inventory_and_refuses_a_forged_buyer_email(): void
    {
        $payload=$this->checkoutPayload();
        $tampered=$payload;
        $tampered['email']='unrelated@example.test';
        $this->postJson('/porucivanje',$tampered)->assertUnprocessable()->assertJsonValidationErrors('email');
        self::assertDatabaseCount('orders',0);
        self::assertSame(4,(int)$this->product->fresh()->stock_quantity);
        $this->product->update(['price_amount'=>15000]);
        $this->post('/porucivanje',$payload)->assertRedirect();
        self::assertSame(15000.0,(float)DB::table('orders')->value('subtotal_rsd'));
        self::assertSame(3,(int)$this->product->fresh()->stock_quantity);
        self::assertDatabaseCount('orders',1);
    }

    #[DataProvider('withdrawnBuyerAccess')]
    public function test_receipt_rechecks_current_buyer_when_authenticated_model_is_stale(string $field): void
    {
        $receipt=$this->post('/porucivanje',$this->checkoutPayload())
            ->assertRedirect()->headers->get('Location');
        $this->get($receipt)->assertOk();
        $cached=auth()->user();
        self::assertNotNull($cached);
        $value=match($field) {
            'role_id'=>$this->owner->role_id,
            'status'=>'inactive',
            'retired_at'=>now()->toDateTimeString(),
            default=>null,
        };
        DB::table('users')->where('id',$this->buyer->id)->update([$field=>$value]);
        // Another operation can revoke access after the guard loaded this user.
        auth()->guard('web')->setUser($cached);
        $this->get($receipt)->assertNotFound();
        foreach(['orders','order_items','stock_movements','public_order_accesses'] as $table) {
            self::assertDatabaseCount($table,1);
        }
        self::assertSame(3,(int)$this->product->fresh()->stock_quantity);
        self::assertDatabaseCount('order_payments',0);
    }

    public static function withdrawnBuyerAccess(): array
    {
        return [
            'email verification withdrawn'=>['email_verified_at'],
            'activation withdrawn'=>['portal_activated_at'],
            'buyer role withdrawn'=>['role_id'],
            'account disabled'=>['status'],
            'account retired'=>['retired_at'],
        ];
    }

    public function test_real_buyer_receipt_respects_emergency_off_and_permanent_token_revocation(): void
    {
        $receipt=$this->post('/porucivanje',$this->checkoutPayload())
            ->assertRedirect()->headers->get('Location');
        config()->set('public_storefront.checkout_enabled',false);
        $this->get($receipt)->assertOk()->assertHeader('Referrer-Policy','no-referrer');
        config()->set('public_storefront.enabled',false);
        $this->get($receipt)->assertNotFound();
        config()->set('public_storefront.enabled',true);
        $this->get($receipt)->assertOk();
        app(PublicStorefrontStateService::class)->change(false,$this->owner);
        $this->get($receipt)->assertNotFound();
        app(\App\Services\PublicOrderAccessService::class)->revoke(\App\Models\Order::query()->sole());
        app(PublicStorefrontStateService::class)->change(true,$this->owner);
        $this->get($receipt)->assertNotFound();
        self::assertDatabaseCount('orders',1);
        self::assertSame(3,(int)$this->product->fresh()->stock_quantity);
        self::assertDatabaseCount('order_payments',0);
    }

    /** @return array<string,string> */
    private function checkoutPayload(): array
    {
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])
            ->assertRedirect('/korpa');
        $html=$this->get('/porucivanje')->assertOk()->getContent();
        self::assertSame(1,preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$html,$nonce));
        self::assertSame(1,preg_match('/name="merchant_policy_fingerprint" value="([a-f0-9]{64})"/',$html,$digest));
        $payload=[
            'checkout_key'=>$nonce[1],
            'merchant_policy_fingerprint'=>$digest[1],
            'shipping_full_name'=>'B1075 Synthetic Buyer',
            'email'=>$this->buyer->email,
            'shipping_phone'=>'0601234567',
            'shipping_address'=>'Test 12',
            'shipping_city'=>'Beograd',
            'shipping_postal_code'=>'11000',
            'payment_method'=>'cash_on_delivery',
            'delivery_method'=>'courier',
            'accepted_terms'=>'1',
        ];
        return $payload;
    }

    private function person(string $username,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$username,
            'email'=>$username.'@example.test',
            'first_name'=>'B1075 Synthetic',
            'password_hash'=>Hash::make('B1075-Synthetic-Only123!'),
            'status'=>'active',
            'email_verified_at'=>now(),
            'portal_activated_at'=>now(),
        ]);
    }
}
