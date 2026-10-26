<?php
declare(strict_types=1);
namespace Tests\Feature;

use App\Models\{Order,Product,Role,User};
use App\Services\{PublicMerchantPolicyService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Mail};
use Tests\TestCase;

final class StorefrontNotificationOriginB806Test extends TestCase
{
    use RefreshDatabase;

    public function test_anonymous_host_header_cannot_poison_operator_order_notification(): void
    {
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Mail::fake();
        config()->set('app.url','https://cms.ald1n.com');
        foreach ([
            'enabled'=>true,'checkout_enabled'=>true,
            'terms_url'=>'https://cms.ald1n.com/uslovi','privacy_url'=>'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd'=>'0','shipping_description'=>'Synthetic delivery only.',
            'payment_methods'=>['cash_on_delivery'],'delivery_methods'=>['courier'],
            'support_email'=>'support@example.test',
        ] as $key=>$value) config()->set('public_storefront.'.$key,$value);
        $owner=User::query()->create([
            'role_id'=>Role::query()->where('slug','superadmin')->valueOrFail('id'),
            'username'=>'b806-notice-owner','email'=>'owner@example.test','first_name'=>'Synthetic',
            'password_hash'=>Hash::make('SyntheticOnly123!'),'status'=>'active',
        ]);
        $settings=app(SettingsService::class);
        $settings->putMany([
            'public_storefront_open'=>'1','documents_company_name'=>'Synthetic B806 Merchant',
            'documents_company_address'=>'Test 10','documents_company_city'=>'Beograd',
            'documents_company_tax_id'=>'100200300',
        ],$owner->id);
        $review=app(PublicMerchantPolicyService::class)->review();
        self::assertTrue($review['complete']);
        $settings->putMany([
            'public_checkout_approval_digest'=>$review['fingerprint'],
            'public_checkout_approved_by'=>(string)$owner->id,
            'public_checkout_approved_at'=>now()->toISOString(),
        ],$owner->id);
        $product=Product::query()->create([
            'sku'=>'B806-NOTICE','slug'=>'b806-notice','name'=>'Synthetic notice product',
            'description'=>'Synthetic only','price_amount'=>12000,'price_currency'=>'RSD',
            'stock_quantity'=>2,'status'=>'active','created_by'=>$owner->id,
        ]);
        $this->post('/korpa/dodaj/'.$product->id,['quantity'=>1])->assertRedirect();
        $html=$this->get('/porucivanje')->assertOk()->getContent();
        preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$html,$key);
        preg_match('/name="merchant_policy_fingerprint" value="([a-f0-9]{64})"/',$html,$policy);
        $this->post('https://injected.example.test/porucivanje',[
            'checkout_key'=>$key[1],'merchant_policy_fingerprint'=>$policy[1],
            'shipping_full_name'=>'Synthetic Buyer','email'=>'buyer@example.test',
            'shipping_phone'=>'0601234567','shipping_address'=>'Test 12','shipping_city'=>'Beograd',
            'shipping_postal_code'=>'11000','payment_method'=>'cash_on_delivery',
            'delivery_method'=>'courier','accepted_terms'=>'1',
        ])->assertRedirect();
        $order=Order::query()->sole();
        $notice=$owner->notifications()->get()->first(static fn ($n)=>
            ($n->data['event']??null)==='order.created_for_supplier');
        self::assertNotNull($notice,'Real operator database notification must be recorded.');
        self::assertSame('https://cms.ald1n.com/admin/orders/'.$order->id,$notice->data['url']);
        self::assertStringNotContainsString('injected.example.test',json_encode($notice->data,JSON_THROW_ON_ERROR));
        self::assertDatabaseCount('order_payments',0);
    }
}
