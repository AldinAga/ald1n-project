<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Product,Role,User};
use App\Notifications\ResetPasswordNotification;
use App\Services\{PasswordResetService,PublicMerchantPolicyService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash,Mail,Notification};
use Tests\TestCase;

/** B815: credential rotation cannot authorize a new buyer order. */
final class BuyerCheckoutSessionBoundaryB815Test extends TestCase
{
    use RefreshDatabase;

    private User $buyer;
    private Product $product;
    private SettingsService $settings;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        config()->set('services.turnstile.enabled',false);
        config()->set('mail.default','array');
        config()->set('app.url','https://cms.ald1n.com');
        Notification::fake();
        Mail::fake();

        foreach([
            'enabled'=>true,
            'checkout_enabled'=>true,
            'terms_url'=>'https://cms.ald1n.com/uslovi',
            'privacy_url'=>'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd'=>'0',
            'shipping_description'=>'B815 synthetic shipping.',
            'payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','own_transport','customer_pickup'],
            'support_email'=>'support@example.test',
        ] as $key=>$value) {
            config()->set('public_storefront.'.$key,$value);
        }

        $owner=$this->person('b815-owner','superadmin');
        $this->buyer=$this->person('b815-buyer','customer');
        $this->settings=app(SettingsService::class);
        $this->settings->putMany([
            'public_storefront_open'=>'1',
            'documents_company_name'=>'B815 Synthetic Seller',
            'documents_company_address'=>'Test 10',
            'documents_company_city'=>'Beograd',
            'documents_company_tax_id'=>'100200300',
        ],$owner->id);

        $review=app(PublicMerchantPolicyService::class)->review();
        self::assertTrue($review['complete'],implode('; ',$review['issues']));
        $this->settings->putMany([
            'public_checkout_approval_digest'=>$review['fingerprint'],
            'public_checkout_approved_by'=>(string)$owner->id,
            'public_checkout_approved_at'=>now()->toISOString(),
        ],$owner->id);
        $this->product=Product::query()->create([
            'sku'=>'B815-SESSION',
            'slug'=>'b815-session',
            'name'=>'B815 Synthetic Product',
            'description'=>'Disposable test fixture',
            'price_amount'=>12500,
            'price_currency'=>'RSD',
            'stock_quantity'=>4,
            'status'=>'active',
            'created_by'=>$owner->id,
        ]);
    }

    public function test_reset_logged_in_buyer_cannot_post_old_checkout_nonce_or_reserve_stock(): void
    {
        $this->post('/login',[
            'login'=>$this->buyer->email,
            'password'=>'B815-Old-Password123!',
        ])->assertRedirect('/korpa');

        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])
            ->assertRedirect('/korpa');
        $html=$this->get('/porucivanje')->assertOk()->getContent();
        self::assertSame(1,preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$html,$nonce));
        self::assertSame(1,preg_match('/name="merchant_policy_fingerprint" value="([a-f0-9]{64})"/',$html,$digest));

        $plain='';
        app(PasswordResetService::class)->sendResetLink((string)$this->buyer->email);
        Notification::assertSentTo(
            $this->buyer,ResetPasswordNotification::class,
            static function(ResetPasswordNotification $notice) use (&$plain): bool {
                $plain=$notice->token;
                return strlen($plain)===80;
            }
        );
        self::assertSame(80,strlen($plain));
        self::assertTrue(app(PasswordResetService::class)->resetPassword(
            $plain,'B815-New-Password123!'
        ));

        $this->postJson('/porucivanje',[
            'checkout_key'=>$nonce[1],
            'merchant_policy_fingerprint'=>$digest[1],
            'shipping_full_name'=>'B815 Synthetic Buyer',
            'email'=>$this->buyer->email,
            'shipping_phone'=>'0601234567',
            'shipping_address'=>'Test 12',
            'shipping_city'=>'Beograd',
            'shipping_postal_code'=>'11000',
            'payment_method'=>'cash_on_delivery',
            'delivery_method'=>'courier',
            'accepted_terms'=>'1',
        ])->assertUnauthorized();

        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('public_checkout_attempts',0);
        self::assertDatabaseCount('stock_movements',0);
        self::assertSame(4,(int)$this->product->fresh()->stock_quantity);
        $this->assertGuest();
    }

    public function test_storefront_off_remains_404_before_any_session_gate(): void
    {
        $this->post('/login',[
            'login'=>$this->buyer->email,
            'password'=>'B815-Old-Password123!',
        ])->assertRedirect('/korpa');
        $this->settings->put('public_storefront_open','0',null);
        $this->get('/porucivanje')->assertNotFound();
        $this->postJson('/porucivanje',['checkout_key'=>str_repeat('a',48)])
            ->assertNotFound();
        self::assertDatabaseCount('orders',0);
    }

    private function person(string $username,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$username,
            'email'=>$username.'@example.test',
            'first_name'=>'B815 Synthetic',
            'password_hash'=>Hash::make('B815-Old-Password123!'),
            'status'=>'active',
            'email_verified_at'=>now(),
            'portal_activated_at'=>now(),
        ]);
    }
}
