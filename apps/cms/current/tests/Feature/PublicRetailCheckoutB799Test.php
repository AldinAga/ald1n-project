<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Order;
use App\Models\OrderCommission;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\PublicCheckoutAttempt;
use App\Models\PublicOrderAccess;
use App\Models\Role;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class PublicRetailCheckoutB799Test extends TestCase
{
    use RefreshDatabase;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        config()->set('public_storefront.enabled',true);
        config()->set('public_storefront.checkout_enabled',true);
        config()->set('public_storefront.shipping_description','Besplatna dostava na adresu.');
        config()->set('public_storefront.shipping_fee_rsd','0');
        config()->set('public_storefront.terms_url','https://cms.ald1n.com/uslovi');
        config()->set('public_storefront.privacy_url','https://cms.ald1n.com/privatnost');
        config()->set('public_storefront.support_email','prodaja@example.test');
        config()->set('public_storefront.payment_methods',['cash_on_delivery']);
        app(SettingsService::class)->putMany([
            'documents_company_name'=>'AP Computers Test',
            'documents_company_address'=>'Primer 1, Beograd',
            'documents_company_tax_id'=>'123456789',
        ],null);
        $admin=User::query()->create([
            'role_id'=>Role::query()->where('slug','superadmin')->valueOrFail('id'),
            'username'=>'b799-retail-admin','email'=>'staff@example.test',
            'first_name'=>'Test','password_hash'=>Hash::make('SyntheticOnly123!'),'status'=>'active',
        ]);
        $this->product=Product::query()->create([
            'sku'=>'B799-CART-RETAIL','slug'=>'b799-cart-retail','name'=>'Laptop retail',
            'price_amount'=>12000,'price_currency'=>'RSD',
            'stock_quantity'=>4,'description'=>'Testni retail artikal','purchase_price_rsd'=>5000,
            'manual_commission_eur'=>250,
            'status'=>'active','created_by'=>$admin->id,
        ]);
        Mail::fake();
    }

    public function test_anonymous_checkout_creates_one_real_operational_order_with_no_commission(): void
    {
        [$data,$initialStock]=$this->prepare();
        $response=$this->post('/porucivanje',$data+['price_rsd'=>1,'user_id'=>1])
            ->assertRedirect();
        $location=$response->headers->get('Location');
        self::assertStringContainsString('/porudzbina/',$location);
        self::assertSame(1,Order::query()->count());
        $order=Order::query()->sole();
        self::assertNull($order->user_id);
        self::assertSame('public_retail',$order->sales_channel);
        self::assertSame('new',$order->status);
        self::assertSame('reserved',$order->inventory_state);
        self::assertSame('unpaid',$order->payment_state);
        self::assertSame('cash_on_delivery',$order->payment_method);
        self::assertSame(24000.0,(float)$order->subtotal_rsd);
        self::assertSame(2,$order->items->first()->quantity);
        self::assertSame(0.0,(float)$order->items->first()->commission_total_eur_snapshot);
        self::assertSame(0.0,(float)$order->items->first()->commission_unit_eur_snapshot);
        self::assertSame(1,StockMovement::query()->where('order_id',$order->id)->count());
        self::assertSame($initialStock-2,(int)$this->product->fresh()->stock_quantity);
        self::assertSame(0,OrderCommission::query()->count());
        self::assertSame(0,OrderPayment::query()->count());
        self::assertSame(1,PublicOrderAccess::query()->count());
        self::assertSame(1,PublicCheckoutAttempt::query()->count());
        $this->get($location)->assertOk()->assertSee('Laptop retail')
            ->assertDontSee('5000')->assertDontSee('250');
    }

    public function test_duplicate_submission_returns_same_capability_without_rebooking_stock(): void
    {
        [$data]=$this->prepare();
        $first=$this->post('/porucivanje',$data)->assertRedirect();
        $second=$this->post('/porucivanje',$data)->assertRedirect();
        self::assertSame($first->headers->get('Location'),$second->headers->get('Location'));
        self::assertSame(1,Order::query()->count());
        self::assertSame(1,StockMovement::query()->count());
        self::assertSame(2,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_second_separate_checkout_rotates_nonce_and_can_create_a_new_order(): void
    {
        [$first]=$this->prepare();
        $this->post('/porucivanje',$first)->assertRedirect();
        self::assertSame(1,Order::query()->count());

        // Repeated POST of the first purchase is idempotent (covered separately),
        // but adding more articles must allow an entirely new checkout.
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])
            ->assertRedirect('/korpa');
        $form=$this->get('/porucivanje')->assertOk();
        preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$form->getContent(),$matches);
        self::assertNotEmpty($matches[1]??null);
        self::assertNotSame($first['checkout_key'],$matches[1]);
        $second=array_replace($first,['checkout_key'=>$matches[1]]);
        $this->post('/porucivanje',$second)->assertRedirect();
        self::assertSame(2,Order::query()->count());
        self::assertSame(2,StockMovement::query()->count());
        self::assertSame(1,(int)$this->product->fresh()->stock_quantity);
        self::assertSame(0,OrderCommission::query()->count());
    }

    public function test_stock_changed_between_cart_and_checkout_rejects_without_partial_writes(): void
    {
        [$data]=$this->prepare();
        $this->product->update(['stock_quantity'=>1]);
        $this->postJson('/porucivanje',$data)->assertUnprocessable()->assertJsonValidationErrors('cart');
        self::assertSame(0,Order::query()->count());
        self::assertSame(0,PublicOrderAccess::query()->count());
        self::assertSame(0,StockMovement::query()->count());
        self::assertSame(1,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_unaccepted_terms_and_invalid_checkout_key_never_create_orders(): void
    {
        [$data]=$this->prepare();
        $this->postJson('/porucivanje',array_merge($data,['accepted_terms'=>'0']))
            ->assertUnprocessable()->assertJsonValidationErrors('accepted_terms');
        $this->postJson('/porucivanje',array_merge($data,['checkout_key'=>str_repeat('a',48)]))
            ->assertUnprocessable()->assertJsonValidationErrors('checkout');
        self::assertSame(0,Order::query()->count());
    }

    public function test_bank_transfer_uses_only_configured_active_account_and_is_not_marked_paid(): void
    {
        $account=BankAccount::query()->create([
            'label'=>'Sintetički račun','recipient_name'=>'AP Computers Test',
            'account_number'=>'160000000000000001',
            'account_number_display'=>'160-0000000000000-01',
            'is_active'=>true,
        ]);
        config()->set('public_storefront.payment_methods',['bank_transfer']);
        config()->set('public_storefront.bank_account_id',$account->id);
        [$data]=$this->prepare();
        $data['payment_method']='bank_transfer';
        $this->post('/porucivanje',$data)->assertRedirect();
        $order=Order::query()->sole();
        self::assertSame((int)$account->id,(int)$order->bank_account_id);
        self::assertSame($account->account_number_display,$order->bank_account_number_display_snapshot);
        self::assertSame('unpaid',$order->payment_state);
        self::assertSame(0,OrderPayment::query()->count());
        self::assertSame(0,OrderCommission::query()->count());
    }

    /** @return array{array<string,string>,int} */
    private function prepare(): array
    {
        $stock=(int)$this->product->stock_quantity;
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>2])->assertRedirect('/korpa');
        $form=$this->get('/porucivanje')->assertOk();
        preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$form->getContent(),$matches);
        self::assertNotEmpty($matches[1]??null);
        return [[
            'checkout_key'=>$matches[1],
            'shipping_full_name'=>'Sintetički Kupac',
            'email'=>'buyer@example.test','shipping_phone'=>'0601234567',
            'shipping_address'=>'Test 12','shipping_city'=>'Beograd',
            'shipping_postal_code'=>'11000',
            'payment_method'=>'cash_on_delivery','accepted_terms'=>'1',
        ],$stock];
    }
}
