<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Order,Product,PublicCheckoutAttempt,PublicOrderAccess,Role,User};
use App\Services\{PublicMerchantPolicyService,PublicStorefrontStateService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Mail};
use Tests\TestCase;

/**
 * B843: replaying a completed public checkout may return only the ORIGINAL
 * still-valid private receipt; no fresh stock write or resurrected capability.
 * Synthetic database and mail only. Not a global storefront acceptance suite.
 */
final class PublicCheckoutReplayIntegrityB843Test extends TestCase
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
            'shipping_description'=>'B843 synthetic shipping',
            'payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','own_transport','customer_pickup'],
            'support_email'=>'support@example.test',
        ] as $name=>$value) config()->set('public_storefront.'.$name,$value);

        $this->owner=$this->person('b843-owner','superadmin');
        $this->buyer=$this->person('b843-buyer','customer');
        $settings=app(SettingsService::class);
        $settings->putMany([
            'documents_company_name'=>'B843 Synthetic Merchant',
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
            'sku'=>'B843-SYNTHETIC-ONLY',
            'slug'=>'b843-synthetic-only',
            'name'=>'B843 Synthetic Laptop',
            'description'=>'Disposable test fixture',
            'price_amount'=>12000,
            'price_currency'=>'RSD',
            'stock_quantity'=>4,
            'purchase_price_rsd'=>9000,
            'status'=>'active',
            'created_by'=>$this->owner->id,
        ]);
        $this->post('/login',[
            'login'=>$this->buyer->email,
            'password'=>'B843-Synthetic-Only123!',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($this->buyer);
    }

    public function test_unmodified_replay_returns_original_receipt_and_does_not_rebook_stock(): void
    {
        [$data,$location]=$this->firstCheckout();
        $this->post('/porucivanje',$data)->assertRedirect($location);
        $this->assertOneSaleOnly();
        self::assertDatabaseCount('public_order_accesses',1);
    }

    public function test_revoked_receipt_replay_is_rejected_without_reissuing_access(): void
    {
        [$data]=$this->firstCheckout();
        PublicOrderAccess::query()->sole()->forceFill(['revoked_at'=>now()])->save();
        $this->postJson('/porucivanje',$data)->assertUnprocessable()
            ->assertJsonValidationErrors('checkout');
        $this->assertOneSaleOnly();
        self::assertNotNull(PublicOrderAccess::query()->sole()->revoked_at);
    }

    public function test_expired_receipt_replay_is_rejected_without_reissuing_access(): void
    {
        [$data]=$this->firstCheckout();
        PublicOrderAccess::query()->sole()->forceFill(['expires_at'=>now()->subSecond()])->save();
        $this->postJson('/porucivanje',$data)->assertUnprocessable()
            ->assertJsonValidationErrors('checkout');
        $this->assertOneSaleOnly();
        self::assertDatabaseCount('public_order_accesses',1);
    }

    public function test_corrupted_encrypted_receipt_replay_fails_closed_without_leakage(): void
    {
        [$data]=$this->firstCheckout();
        PublicCheckoutAttempt::query()->sole()->forceFill([
            'receipt_token_ciphertext'=>'B843-SYNTHETIC-BROKEN-CIPHERTEXT'
        ])->save();
        $response=$this->postJson('/porucivanje',$data)->assertUnprocessable()
            ->assertJsonValidationErrors('checkout');
        $response->assertDontSee('DecryptException');
        $response->assertDontSee('receipt_token_ciphertext');
        $this->assertOneSaleOnly();
    }

    public function test_purged_order_checkout_replay_is_forbidden_without_stock_change(): void
    {
        [$data]=$this->firstCheckout();
        Order::query()->sole()->forceFill(['purged_at'=>now()])->save();
        $this->postJson('/porucivanje',$data)->assertForbidden();
        $this->assertOneSaleOnly();
    }

    /** @return array{0:array<string,string>,1:string} */
    private function firstCheckout(): array
    {
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])
            ->assertRedirect('/korpa');
        $html=$this->get('/porucivanje')->assertOk()->getContent();
        self::assertSame(1,preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$html,$nonce));
        self::assertSame(1,preg_match('/name="merchant_policy_fingerprint" value="([a-f0-9]{64})"/',$html,$digest));
        $data=[
            'checkout_key'=>$nonce[1],
            'merchant_policy_fingerprint'=>$digest[1],
            'shipping_full_name'=>'B843 Synthetic Buyer',
            'email'=>$this->buyer->email,
            'shipping_phone'=>'0601234567',
            'shipping_address'=>'Test 12',
            'shipping_city'=>'Beograd',
            'shipping_postal_code'=>'11000',
            'payment_method'=>'cash_on_delivery',
            'delivery_method'=>'courier',
            'accepted_terms'=>'1',
        ];
        $first=$this->post('/porucivanje',$data)->assertRedirect();
        $location=(string)$first->headers->get('Location');
        self::assertStringContainsString('/porudzbina/',$location);
        $this->assertOneSaleOnly();
        return [$data,$location];
    }

    private function assertOneSaleOnly(): void
    {
        self::assertDatabaseCount('orders',1);
        self::assertDatabaseCount('public_checkout_attempts',1);
        self::assertDatabaseCount('public_order_accesses',1);
        self::assertDatabaseCount('stock_movements',1);
        self::assertSame(3,(int)$this->product->fresh()->stock_quantity);
    }

    private function person(string $username,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$username,
            'email'=>$username.'@example.test',
            'first_name'=>'B843 Synthetic',
            'password_hash'=>Hash::make('B843-Synthetic-Only123!'),
            'status'=>'active',
            'email_verified_at'=>now(),
            'portal_activated_at'=>now(),
        ]);
    }
}
