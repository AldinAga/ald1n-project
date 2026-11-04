<?php
declare(strict_types=1);
namespace Tests\Feature;

use App\Models\{Order,Product,PublicCheckoutAttempt,Role,User};
use App\Services\{PublicMerchantPolicyService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Mail};
use Tests\TestCase;

final class PublicRetailBuyerCheckoutB802Test extends TestCase
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
        $this->owner=$this->actor('b802-owner','superadmin');
        $this->buyer=$this->actor('b802-buyer','customer');
        foreach ([
            'enabled'=>true,'checkout_enabled'=>true,
            'terms_url'=>'https://cms.ald1n.com/uslovi',
            'privacy_url'=>'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd'=>'0','shipping_description'=>'Synthetic agreed delivery.',
            'payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','own_transport','customer_pickup'],
            'support_email'=>'support@example.test',
        ] as $key=>$value) config()->set('public_storefront.'.$key,$value);
        app(SettingsService::class)->putMany([
            'public_storefront_open'=>'1','documents_company_name'=>'Synthetic Merchant',
            'documents_company_address'=>'Test 10','documents_company_city'=>'Beograd',
            'documents_company_tax_id'=>'100200300',
        ],$this->owner->id);
        $this->approve();
        $this->product=Product::query()->create([
            'sku'=>'B802-RETAIL-ITEM','slug'=>'b802-retail-item','name'=>'Synthetic laptop',
            'price_amount'=>12000,'price_currency'=>'RSD','stock_quantity'=>3,
            'description'=>'Synthetic only','status'=>'active',
            'created_by'=>$this->owner->id,'purchase_price_rsd'=>3333,
            'manual_commission_eur'=>777,
        ]);
    }

    public function test_customer_uses_public_catalog_and_selects_pickup_without_internal_prices_or_commission(): void
    {
        $this->actingAs($this->buyer)->get('/katalog')->assertOk()
            ->assertSee('Synthetic laptop')->assertDontSee('3333')->assertDontSee('777');
        [$data,$html]=$this->form();
        self::assertStringContainsString('name="delivery_method"',$html);
        $response=$this->post('/porucivanje',$data+['delivery_method'=>'customer_pickup','user_id'=>$this->owner->id])->assertRedirect();
        $order=Order::query()->sole();
        self::assertSame((int)$this->buyer->id,(int)$order->user_id);
        self::assertSame('public_retail',$order->sales_channel);
        self::assertSame('unpaid',$order->payment_state);
        self::assertSame(12000.0,(float)$order->subtotal_rsd);
        self::assertSame('customer_pickup',PublicCheckoutAttempt::query()->sole()->delivery_method);
        self::assertDatabaseCount('order_commissions',0);
        self::assertDatabaseCount('order_payments',0);
        self::assertDatabaseCount('order_shipments',0);
        self::assertDatabaseCount('order_deliveries',0);
        $privateUrl=$response->headers->get('Location');
        $this->get($privateUrl)->assertOk()->assertSee('Lično preuzimanje');
        $this->get('/catalog')->assertForbidden();
        $this->actingAs($this->owner)->get('/admin/orders/'.$order->id)->assertOk()
            ->assertSee('Lično preuzimanje');
        self::assertNull($order->fresh()->customer_note,
            'Operator display must not silently alter stored customer notes.');
        $this->post('/admin/settings/public-storefront/toggle',['enabled'=>'0'])->assertRedirect();
        $this->get('/katalog')->assertNotFound();
        $this->get('/porucivanje')->assertNotFound();
        $this->get($privateUrl)->assertOk();
        self::assertDatabaseCount('orders',1);
    }

    public function test_missing_disabled_or_forged_delivery_method_does_not_create_an_order(): void
    {
        $this->actingAs($this->buyer);
        [$data]=$this->form();
        foreach ([null,'express_secret','drone'] as $method) {
            $this->postJson('/porucivanje',$data+['delivery_method'=>$method])
                ->assertUnprocessable()->assertJsonValidationErrors('delivery_method');
        }
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('stock_movements',0);
    }

    public function test_public_deferred_payment_is_rejected_even_when_legacy_terms_are_present(): void
    {
        config()->set('public_storefront.payment_methods',['deferred_payment']);
        config()->set('public_storefront.deferred_payment_days','14');
        config()->set('public_storefront.deferred_payment_terms','Synthetic legacy conditions.');
        self::assertFalse(app(PublicMerchantPolicyService::class)->review()['complete']);
        self::assertFalse(app(PublicMerchantPolicyService::class)->review()['approved']);
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('order_payments',0);
    }

    public function test_duplicate_nonce_cannot_switch_delivery_or_buyer_identity(): void
    {
        $this->actingAs($this->buyer);
        [$data]=$this->form();
        $payload=$data+['delivery_method'=>'customer_pickup'];
        $this->post('/porucivanje',$payload)->assertRedirect();
        $this->postJson('/porucivanje',array_replace($payload,['delivery_method'=>'courier']))
            ->assertUnprocessable()->assertJsonValidationErrors('checkout');
        $other=$this->actor('b802-other-buyer','customer');
        $this->actingAs($other)->postJson('/porucivanje',$payload)
            ->assertUnprocessable()->assertJsonValidationErrors('checkout');
        self::assertDatabaseCount('orders',1);
        self::assertSame((int)$this->buyer->id,(int)Order::query()->sole()->user_id);
        self::assertSame('customer_pickup',PublicCheckoutAttempt::query()->sole()->delivery_method);
        self::assertSame(2,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_deferred_terms_cannot_be_invented_by_checkout_or_missing_merchant_fields(): void
    {
        config()->set('public_storefront.payment_methods',['deferred_payment']);
        config()->set('public_storefront.deferred_payment_days',null);
        config()->set('public_storefront.deferred_payment_terms',null);
        self::assertFalse(app(PublicMerchantPolicyService::class)->review()['complete']);
        self::assertFalse(app(PublicMerchantPolicyService::class)->review()['approved']);
        self::assertDatabaseCount('orders',0);
    }

    private function form(): array
    {
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])->assertRedirect('/korpa');
        $html=$this->get('/porucivanje')->assertOk()->getContent();
        preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$html,$key);
        preg_match('/name="merchant_policy_fingerprint" value="([a-f0-9]{64})"/',$html,$policy);
        return [[
            'checkout_key'=>$key[1],'merchant_policy_fingerprint'=>$policy[1],
            'shipping_full_name'=>'Synthetic Buyer','email'=>'buyer@example.test',
            'shipping_phone'=>'0601234567','shipping_address'=>'Test 12',
            'shipping_city'=>'Beograd','shipping_postal_code'=>'11000',
            'payment_method'=>'cash_on_delivery','accepted_terms'=>'1',
        ],$html];
    }
    private function approve(): void
    {
        $review=app(PublicMerchantPolicyService::class)->review();
        self::assertTrue($review['complete'],implode('; ',$review['issues']));
        app(SettingsService::class)->putMany([
            'public_checkout_approval_digest'=>$review['fingerprint'],
            'public_checkout_approved_by'=>(string)$this->owner->id,
            'public_checkout_approved_at'=>now()->toISOString(),
        ],$this->owner->id);
    }
    private function actor(string $name,string $role): User
    {
        $r=Role::query()->firstOrCreate(['slug'=>$role],['name'=>$role]);
        return User::query()->create(['role_id'=>$r->id,'username'=>$name,
            'email'=>$name.'@example.test','first_name'=>'Synthetic',
            'password_hash'=>Hash::make('SyntheticOnly123!'),'status'=>'active']);
    }
}
