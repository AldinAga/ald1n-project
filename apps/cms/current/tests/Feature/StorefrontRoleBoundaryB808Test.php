<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Order,OrderDocument,Product,Role,User};
use App\Services\{PublicMerchantPolicyService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Mail};
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** B808: synthetic-only role, horizontal PDF and capability authorization. */
final class StorefrontRoleBoundaryB808Test extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Mail::fake();
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
        $this->operator=$this->person('b808-owner','superadmin');
        app(SettingsService::class)->putMany([
            'public_storefront_open'=>'1','documents_company_name'=>'Synthetic B808 Merchant',
            'documents_company_address'=>'Test 10','documents_company_city'=>'Beograd',
            'documents_company_tax_id'=>'100200300',
        ],$this->operator->id);
        $review=app(PublicMerchantPolicyService::class)->review();
        self::assertTrue($review['complete'],implode('; ',$review['issues']));
        app(SettingsService::class)->putMany([
            'public_checkout_approval_digest'=>$review['fingerprint'],
            'public_checkout_approved_by'=>(string)$this->operator->id,
            'public_checkout_approved_at'=>now()->toISOString(),
        ],$this->operator->id);
        $this->product=Product::query()->create([
            'sku'=>'B808-SYNTHETIC','slug'=>'b808-synthetic',
            'name'=>'B808 synthetic retail product','description'=>'Synthetic only',
            'price_amount'=>12000,'price_currency'=>'RSD','stock_quantity'=>4,
            'status'=>'active','created_by'=>$this->operator->id,
            'purchase_price_rsd'=>987654,'manual_commission_eur'=>876543,
        ]);
    }

    public static function operatorRoles(): array
    {
        return [
            'SubAgent'=>['user'],
            'Administrator'=>['admin'],
            'SuperAdministrator'=>['superadmin'],
        ];
    }

    #[DataProvider('operatorRoles')]
    public function test_operator_cannot_checkout_as_anonymous_buyer(string $role): void
    {
        $payload=$this->form();
        $actor=$role==='superadmin'?$this->operator:$this->person('b808-'.$role,$role);
        $this->actingAs($actor)->get('/katalog')->assertOk()
            ->assertDontSee('987654')->assertDontSee('876543');
        $this->get('/porucivanje')->assertForbidden();
        $this->postJson('/porucivanje',$payload)->assertForbidden();

        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('public_checkout_attempts',0);
        self::assertDatabaseCount('stock_movements',0);
        self::assertDatabaseCount('order_payments',0);
        self::assertDatabaseCount('order_commissions',0);
        self::assertSame(4,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_valid_customer_still_checks_out_with_real_identity_and_no_commission(): void
    {
        $buyer=$this->person('b808-kupac','customer');
        $this->actingAs($buyer)->get('/katalog')->assertOk()
            ->assertDontSee('987654')->assertDontSee('876543');
        $payload=$this->form();
        $result=$this->post('/porucivanje',$payload)->assertRedirect();
        $order=Order::query()->sole();
        self::assertSame((int)$buyer->id,(int)$order->user_id);
        self::assertSame('public_retail',$order->sales_channel);
        self::assertSame('unpaid',$order->payment_state);
        self::assertSame(12000.0,(float)$order->subtotal_rsd);
        self::assertDatabaseCount('order_commissions',0);
        self::assertDatabaseCount('order_payments',0);
        self::assertDatabaseCount('stock_movements',1);
        self::assertSame(3,(int)$this->product->fresh()->stock_quantity);
        $this->get((string)$result->headers->get('Location'))->assertOk()
            ->assertDontSee('987654')->assertDontSee('876543');
        $this->get('/catalog')->assertForbidden();
    }

    public function test_anonymous_guest_still_orders_without_fake_staff_customer_id(): void
    {
        $location=$this->post('/porucivanje',$this->form())->assertRedirect()
            ->headers->get('Location');
        self::assertNull(Order::query()->sole()->user_id);
        self::assertSame('public_retail',Order::query()->sole()->sales_channel);
        $this->get((string)$location)->assertOk();
        self::assertDatabaseCount('order_payments',0);
        self::assertDatabaseCount('order_commissions',0);
    }

    public function test_retired_customer_cannot_continue_guest_checkout_with_old_session(): void
    {
        $payload=$this->form();
        $buyer=$this->person('b808-retired','customer');
        $buyer->forceFill(['retired_at'=>now()])->save();
        $this->actingAs($buyer)->get('/porucivanje')->assertForbidden();
        $this->postJson('/porucivanje',$payload)->assertForbidden();
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('stock_movements',0);
    }

    public function test_other_retail_order_document_cannot_be_read_with_valid_foreign_capability(): void
    {
        $linkA=$this->post('/porucivanje',$this->form())->assertRedirect()
            ->headers->get('Location');
        $orderA=Order::query()->sole();
        $linkB=$this->post('/porucivanje',$this->form())->assertRedirect()
            ->headers->get('Location');
        $orderB=Order::query()->where('id','!=',$orderA->id)->firstOrFail();
        $document=OrderDocument::query()->create([
            'order_id'=>$orderB->id,'document_type'=>'invoice',
            'document_number'=>'B808-OTHER-ORDER-INVOICE',
            'status'=>'issued','issued_at'=>now(),'currency'=>'RSD',
            // Current MariaDB schema has non-null financial snapshot fields.
            // Synthetic fixture only: do not weaken production constraints.
            'subtotal_rsd'=>12000,'tax_rate_percent'=>0,
            'tax_base_rsd'=>12000,'tax_amount_rsd'=>0,'total_rsd'=>12000,
            'company_name'=>'Synthetic B808 Merchant',
            'customer_name'=>'Synthetic buyer B',
        ]);
        $this->get((string)$linkA)->assertOk()
            ->assertDontSee('B808-OTHER-ORDER-INVOICE');
        $this->get((string)$linkA.'/dokument/'.$document->id)->assertNotFound();
        $this->get('/porudzbina/'.$orderB->id.'/dokument/'.$document->id)->assertNotFound();
        $this->get((string)$linkB)->assertOk()->assertSee('B808-OTHER-ORDER-INVOICE');
        // Use the real document cancellation enum; revoked records must stay private.
        $document->update(['status'=>'cancelled']);
        $this->get((string)$linkB)->assertOk()->assertDontSee('B808-OTHER-ORDER-INVOICE');
        $this->get((string)$linkB.'/dokument/'.$document->id)->assertNotFound();
        self::assertDatabaseCount('order_payments',0);
        self::assertDatabaseCount('order_commissions',0);
    }

    private function person(string $username,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$username,'email'=>$username.'@example.test',
            'first_name'=>'Synthetic','password_hash'=>Hash::make('OnlySynthetic123!'),
            'status'=>'active',
        ]);
    }

    private function form(): array
    {
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])->assertRedirect('/korpa');
        $html=$this->get('/porucivanje')->assertOk()->getContent();
        preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$html,$key);
        preg_match('/name="merchant_policy_fingerprint" value="([a-f0-9]{64})"/',$html,$digest);
        self::assertNotEmpty($key[1]??null);
        self::assertNotEmpty($digest[1]??null);
        return [
            'checkout_key'=>$key[1],
            'merchant_policy_fingerprint'=>$digest[1],
            'shipping_full_name'=>'Synthetic B808 Buyer',
            'email'=>'b808-buyer@example.test','shipping_phone'=>'0601234567',
            'shipping_address'=>'Test 12','shipping_city'=>'Beograd',
            'shipping_postal_code'=>'11000','payment_method'=>'cash_on_delivery',
            'delivery_method'=>'courier','accepted_terms'=>'1',
        ];
    }
}
