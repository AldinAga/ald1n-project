<?php
declare(strict_types=1);
namespace Tests\Feature;

use App\Models\{BankAccount,Order,Product,PublicCheckoutAttempt,Role,User};
use App\Services\{OrderPaymentService,OrderWorkflowService,PublicMerchantPolicyService,SettingsService};
use App\Support\PublicRetailOptions;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Mail};
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PublicRetailPaymentB803Test extends TestCase
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
        $this->owner=$this->actor('b803-owner','superadmin');
        $this->buyer=$this->actor('b803-buyer','customer');
        foreach ([
            'enabled'=>true,'checkout_enabled'=>true,
            'terms_url'=>'https://cms.ald1n.com/uslovi',
            'privacy_url'=>'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd'=>'0','shipping_description'=>'Synthetic approved delivery.',
            'payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','own_transport','customer_pickup'],
            'support_email'=>'support@example.test',
        ] as $key=>$value) config()->set('public_storefront.'.$key,$value);
        app(SettingsService::class)->putMany([
            'public_storefront_open'=>'1','documents_company_name'=>'Synthetic B803 Merchant',
            'documents_company_address'=>'Test 10','documents_company_city'=>'Beograd',
            'documents_company_tax_id'=>'100200300',
        ],$this->owner->id);
        $this->approve();
        $this->product=Product::query()->create([
            'sku'=>'B803-PAYMENT-ITEM','slug'=>'b803-payment-item','name'=>'Synthetic B803 laptop',
            'price_amount'=>12000,'price_currency'=>'RSD','stock_quantity'=>4,
            'description'=>'Synthetic only','status'=>'active','created_by'=>$this->owner->id,
            'purchase_price_rsd'=>4000,'manual_commission_eur'=>100,
        ]);
    }

    public function test_public_payment_choices_are_exactly_the_three_owner_requested_modes(): void
    {
        self::assertSame(['cash_on_delivery','bank_transfer','cash'],array_keys(PublicRetailOptions::PAYMENT));
        self::assertArrayNotHasKey('deferred_payment',PublicRetailOptions::PAYMENT);
        self::assertSame('Plaćanje pri ličnom preuzimanju/dostavi',PublicRetailOptions::PAYMENT['cash']);
    }

    public function test_previously_signed_deferred_policy_is_not_valid_for_new_public_orders(): void
    {
        config()->set('public_storefront.payment_methods',['deferred_payment']);
        config()->set('public_storefront.deferred_payment_days','14');
        config()->set('public_storefront.deferred_payment_terms','Legacy synthetic deferred terms.');
        $review=app(PublicMerchantPolicyService::class)->review();
        self::assertFalse($review['complete'],'Old deferred configuration must fail closed after the owner change.');
        self::assertFalse($review['approved']);
        self::assertDatabaseCount('orders',0);
    }

    public function test_guest_cannot_post_a_forged_deferred_choice(): void
    {
        $data=$this->form();
        $data['payment_method']='deferred_payment';
        $data['payment_due_at']=today()->addDays(14)->toDateString();
        $this->postJson('/porucivanje',$data)->assertUnprocessable()->assertJsonValidationErrors('payment_method');
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('stock_movements',0);
        self::assertDatabaseCount('order_payments',0);
    }

    public static function personalDeliveries(): array
    {
        return [['customer_pickup'],['own_transport']];
    }

    #[DataProvider('personalDeliveries')]
    public function test_in_person_payment_stays_unpaid_until_operator_records_collection(string $delivery): void
    {
        config()->set('public_storefront.payment_methods',['cash']);
        $this->approve();
        $this->actingAs($this->buyer);
        $data=$this->form();
        $data['payment_method']='cash';
        $data['delivery_method']=$delivery;
        $url=$this->post('/porucivanje',$data+['paid_total_rsd'=>12000,'payment_state'=>'paid'])
            ->assertRedirect()->headers->get('Location');
        $order=Order::query()->sole();
        self::assertSame('cash',$order->payment_method);
        self::assertSame('unpaid',$order->payment_state);
        self::assertSame(0.0,(float)$order->paid_total_rsd);
        self::assertNull($order->payment_due_at);
        self::assertSame((int)$this->buyer->id,(int)$order->user_id);
        self::assertDatabaseCount('order_payments',0);
        self::assertDatabaseCount('order_deliveries',0);
        self::assertDatabaseCount('warranty_issuances',0);
        self::assertDatabaseCount('order_commissions',0);
        $this->get($url)->assertOk()->assertSee('Plaćanje pri ličnom preuzimanju/dostavi');
        $this->actingAs($this->owner)->get('/admin/orders/'.$order->id)->assertOk()->assertSee('Plaćanje pri ličnom preuzimanju/dostavi');

        $workflow=app(OrderWorkflowService::class);
        $processing=$workflow->changeStatus($order,'processing',$this->owner);
        $confirmed=$workflow->changeStatus($processing,'confirmed',$this->owner,null,
            app(\App\Services\OrderVersionService::class)->token($processing));
        $receipt=['delivery_method'=>$delivery,'delivered_at'=>now()->subMinute()->toDateTimeString(),'recipient_name'=>'Synthetic B803 Buyer'];
        try {
            $workflow->complete($confirmed,$this->owner,$receipt);
            self::fail('The in-person preference must not fabricate a collected payment.');
        } catch (ValidationException $e) {
            self::assertArrayHasKey('completion',$e->errors());
        }
        self::assertDatabaseCount('order_payments',0);
        self::assertDatabaseCount('order_deliveries',0);
        app(OrderPaymentService::class)->record($order->fresh(),[
            'entry_type'=>'payment','amount_rsd'=>12000,'payment_method'=>'cash',
            'paid_at'=>now()->toDateTimeString(),'reference'=>'B803-SYNTHETIC-COLLECTION',
        ],$this->owner);
        $completed=$workflow->complete($order->fresh(),$this->owner,$receipt);
        $workflow->complete($completed,$this->owner,$receipt);
        self::assertSame('paid',$order->fresh()->payment_state);
        self::assertDatabaseCount('order_payments',1);
        self::assertDatabaseCount('order_deliveries',1);
        self::assertSame(3,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_in_person_payment_cannot_be_sent_to_courier(): void
    {
        config()->set('public_storefront.payment_methods',['cash']);
        $this->approve();
        $data=$this->form();
        $data['payment_method']='cash';
        $data['delivery_method']='courier';
        $this->postJson('/porucivanje',$data)->assertUnprocessable()->assertJsonValidationErrors('payment_method');
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('stock_movements',0);
        self::assertSame(4,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_bank_transfer_and_cod_keep_their_existing_unpaid_workflows(): void
    {
        $account=BankAccount::query()->create([
            'label'=>'Synthetic B803 Bank','recipient_name'=>'Synthetic B803 Merchant',
            'account_number'=>'160000000000000001','account_number_display'=>'160-0000000000000-01','is_active'=>true,
        ]);
        config()->set('public_storefront.payment_methods',['cash_on_delivery','bank_transfer','cash']);
        config()->set('public_storefront.bank_account_id',$account->id);
        $this->approve();
        foreach (['cash_on_delivery','bank_transfer'] as $method) {
            $data=$this->form();$data['payment_method']=$method;
            $this->post('/porucivanje',$data)->assertRedirect();
        }
        self::assertDatabaseCount('orders',2);
        self::assertTrue(Order::query()->get()->every(fn ($o)=>$o->payment_state==='unpaid'));
        self::assertDatabaseCount('order_payments',0);
        self::assertDatabaseCount('order_commissions',0);
        self::assertSame((int)$account->id,(int)Order::query()->where('payment_method','bank_transfer')->sole()->bank_account_id);
    }

    public function test_superadmin_form_does_not_offer_public_deferred_payment(): void
    {
        $this->actingAs($this->owner)->get('/admin/settings/public-storefront')->assertOk()
            ->assertSee('value="cash"',false)->assertDontSee('value="deferred_payment"',false)
            ->assertDontSee('name="deferred_payment_days"',false);
    }

    private function form(): array
    {
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])->assertRedirect('/korpa');
        $html=$this->get('/porucivanje')->assertOk()->getContent();
        preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$html,$key);
        preg_match('/name="merchant_policy_fingerprint" value="([a-f0-9]{64})"/',$html,$policy);
        return [
            'checkout_key'=>$key[1],'merchant_policy_fingerprint'=>$policy[1],
            'shipping_full_name'=>'Synthetic B803 Buyer','email'=>'buyer@example.test',
            'shipping_phone'=>'0601234567','shipping_address'=>'Test 12','shipping_city'=>'Beograd',
            'shipping_postal_code'=>'11000','payment_method'=>'cash_on_delivery',
            'delivery_method'=>'courier','accepted_terms'=>'1',
        ];
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
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$name,'email'=>$name.'@example.test','first_name'=>'Synthetic',
            'password_hash'=>Hash::make('SyntheticOnly123!'),'status'=>'active',
        ]);
    }
}
