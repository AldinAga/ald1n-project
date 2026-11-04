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
final class PublicRetailReplayNoWriteB1073Test extends TestCase
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
            'shipping_description'=>'B1073 disposable shipping',
            'payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','own_transport','customer_pickup'],
            'support_email'=>'support@example.test',
        ] as $key=>$value) config()->set('public_storefront.'.$key,$value);
        $this->owner=$this->person('b1073-owner','superadmin');
        $this->buyer=$this->person('b1073-buyer','customer');
        $settings=app(SettingsService::class);
        $settings->putMany([
            'documents_company_name'=>'B1073 Synthetic Merchant',
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
            'sku'=>'B1073-SYNTHETIC-ONLY',
            'slug'=>'b1073-synthetic-only',
            'name'=>'B1073 Disposable Laptop',
            'description'=>'Disposable database fixture only',
            'price_amount'=>12000,'price_currency'=>'RSD',
            'stock_quantity'=>4,'purchase_price_rsd'=>9000,
            'status'=>'active','created_by'=>$this->owner->id,
        ]);
        $this->post('/login',[
            'login'=>$this->buyer->email,'password'=>'B1073-Synthetic-Only123!',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($this->buyer);
    }

    /** @return array<string,array{string}> */
    public static function invalidSnapshots(): array
    {
        return [
            'string-quantity'=>['string-quantity'],
            'quantity-exceeds-limit'=>['quantity-exceeds-limit'],
            'zero-product-id'=>['zero-product-id'],
            'sku-product-key'=>['sku-product-key'],
            'nested-quantity'=>['nested-quantity'],
            'boolean-quantity'=>['boolean-quantity'],
            'more-than-25-products'=>['more-than-25-products'],
            'empty-array'=>['empty-array'],
            'not-an-array'=>['not-an-array'],
        ];
    }

    #[DataProvider('invalidSnapshots')]
    public function test_invalid_completed_checkout_replay_never_writes_order_or_inventory(string $variant): void
    {
        $original=$this->successfulSyntheticCheckout();
        self::assertSame(3,(int)$this->product->fresh()->stock_quantity);
        $id=(int)$this->product->id;
        $bad=match($variant) {
            'string-quantity'=>[$id=>'1'],
            'quantity-exceeds-limit'=>[$id=>21],
            'zero-product-id'=>[0=>1],
            'sku-product-key'=>['SKU-B1073'=>1],
            'nested-quantity'=>[$id=>['quantity'=>1]],
            'boolean-quantity'=>[$id=>true],
            'more-than-25-products'=>array_fill_keys(range(1,26),1),
            'empty-array'=>[],
            'not-an-array'=>'damaged-session-cart',
        };
        $tables=[
            'orders','order_items','stock_movements','order_status_history',
            'public_checkout_attempts','public_order_accesses',
            'order_payments','order_commissions',
        ];
        $counts=[];
        foreach ($tables as $table) $counts[$table]=DB::table($table)->count();
        $oldStock=(int)$this->product->fresh()->stock_quantity;
        $oldOrderId=(int)DB::table('orders')->value('id');
        $mutations=[];
        DB::listen(static function ($query) use (&$mutations): void {
            $sql=(string)$query->sql;
            if (preg_match('~\b(?:INSERT\s+INTO|REPLACE\s+INTO|UPDATE|DELETE\s+FROM)\s+[`"]?(?:orders|order_items|products|stock_movements|order_status_history|public_checkout_attempts|public_order_accesses|order_payments|order_commissions)\b~i',$sql)===1) {
                $mutations[]=$sql;
            }
        });
        $this->withSession(['ald1n.public_checkout.last_cart'=>$bad])
            ->postJson('/porucivanje',$original)->assertUnprocessable()
            ->assertJsonValidationErrors('cart');
        foreach ($counts as $table=>$expected) {
            self::assertSame($expected,DB::table($table)->count(),$table.' changed on rejected replay');
        }
        self::assertSame($oldStock,(int)$this->product->fresh()->stock_quantity);
        self::assertSame($oldOrderId,(int)DB::table('orders')->value('id'));
        self::assertSame([], $mutations, 'Rejected replay attempted order/inventory SQL writes');
    }

    /** @return array<string,string> */
    private function successfulSyntheticCheckout(): array
    {
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])
            ->assertRedirect('/korpa');
        $html=$this->get('/porucivanje')->assertOk()->getContent();
        self::assertSame(1,preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$html,$nonce));
        self::assertSame(1,preg_match('/name="merchant_policy_fingerprint" value="([a-f0-9]{64})"/',$html,$digest));
        $payload=[
            'checkout_key'=>$nonce[1],
            'merchant_policy_fingerprint'=>$digest[1],
            'shipping_full_name'=>'B1073 Synthetic Buyer',
            'email'=>$this->buyer->email,
            'shipping_phone'=>'0601234567',
            'shipping_address'=>'Test 12',
            'shipping_city'=>'Beograd',
            'shipping_postal_code'=>'11000',
            'payment_method'=>'cash_on_delivery',
            'delivery_method'=>'courier',
            'accepted_terms'=>'1',
        ];
        $this->post('/porucivanje',$payload)->assertRedirect();
        self::assertDatabaseCount('orders',1);
        self::assertDatabaseCount('stock_movements',1);
        self::assertDatabaseCount('public_checkout_attempts',1);
        return $payload;
    }

    private function person(string $username,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$username,
            'email'=>$username.'@example.test',
            'first_name'=>'B1073 Synthetic',
            'password_hash'=>Hash::make('B1073-Synthetic-Only123!'),
            'status'=>'active',
            'email_verified_at'=>now(),
            'portal_activated_at'=>now(),
        ]);
    }
}
