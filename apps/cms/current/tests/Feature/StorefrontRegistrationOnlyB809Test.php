<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Order,Product,PublicCheckoutAttempt,Role,User,UserActivationToken};
use App\Notifications\CustomerPortalInvitationNotification;
use App\Services\{PublicMerchantPolicyService,PublicRetailCheckoutService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash,Mail,Notification};
use Tests\TestCase;

/** B809: registered Kupac is mandatory for every new public sale. */
final class StorefrontRegistrationOnlyB809Test extends TestCase
{
    use RefreshDatabase;
    private Product $product;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Notification::fake();
        Mail::fake();
        config()->set('app.url','https://cms.ald1n.com');
        config()->set('services.turnstile.enabled',false);
        foreach ([
            'enabled'=>true,'checkout_enabled'=>true,
            'terms_url'=>'https://cms.ald1n.com/uslovi',
            'privacy_url'=>'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd'=>'0','shipping_description'=>'Synthetic shipping only.',
            'payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','own_transport','customer_pickup'],
            'support_email'=>'support@example.test',
        ] as $key=>$value) config()->set('public_storefront.'.$key,$value);
        $this->owner=$this->person('b809-owner','superadmin');
        app(SettingsService::class)->putMany([
            'public_storefront_open'=>'1','documents_company_name'=>'Synthetic B809 Merchant',
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
            'sku'=>'B809-REGISTERED','slug'=>'b809-registered',
            'name'=>'B809 synthetic laptop','description'=>'Synthetic only',
            'price_amount'=>12000,'price_currency'=>'RSD','stock_quantity'=>4,
            'purchase_price_rsd'=>989999,'manual_commission_eur'=>989999,
            'status'=>'active','created_by'=>$this->owner->id,
        ]);
    }

    public function test_anonymous_can_browse_and_cart_but_cannot_open_or_post_checkout(): void
    {
        $this->get('/katalog')->assertOk()->assertSee('B809 synthetic laptop')
            ->assertDontSee('989999');
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])
            ->assertRedirect('/korpa');
        $this->get('/korpa')->assertOk()->assertSee('Registruj se');
        $this->get('/porucivanje')->assertRedirect('/login');
        $this->post('/porucivanje',['checkout_key'=>str_repeat('b',48)])
            ->assertRedirect('/login');
        $this->postJson('/porucivanje',[
            'checkout_key'=>str_repeat('a',48),
            'merchant_policy_fingerprint'=>str_repeat('b',64),
        ])->assertUnauthorized();
        foreach(['orders','order_items','public_checkout_attempts','stock_movements',
            'order_commissions','order_payments','public_order_accesses'] as $table) {
            self::assertSame(0,\Illuminate\Support\Facades\DB::table($table)->count(),$table);
        }
        self::assertSame(4,(int)$this->product->fresh()->stock_quantity);
    }

    public function test_direct_service_call_without_authenticated_customer_cannot_write_a_sale(): void
    {
        $request=Request::create('/porucivanje','POST');
        try {
            app(PublicRetailCheckoutService::class)->place($request,[
                'email'=>'guest@example.test',
            ]);
            self::fail('Anonymous caller bypassed controller authentication.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            self::assertSame(401,$e->getStatusCode());
        }
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('stock_movements',0);
    }

    public function test_self_registration_stays_pending_until_one_time_email_activation(): void
    {
        $this->get('/registracija')->assertOk()->assertSee('Registracija naloga');
        $payload=[
            'first_name'=>'Synthetic','last_name'=>'Customer',
            'email'=>'NewBuyer@Example.test','phone'=>'0601234567',
            'accepted_privacy'=>'1','role_id'=>$this->owner->role_id,
            'status'=>'active','user_group_id'=>1,
        ];
        $this->withHeaders(['Host'=>'attacker.example.test'])
            ->post('/registracija',$payload)->assertRedirect();
        $buyer=User::query()->where('email','newbuyer@example.test')->sole();
        self::assertTrue($buyer->hasRole('customer'));
        self::assertSame('pending',$buyer->status);
        self::assertNull($buyer->user_group_id);
        self::assertNull($buyer->email_verified_at);
        self::assertNull($buyer->portal_activated_at);
        self::assertDatabaseCount('orders',0);
        $this->post('/login',[
            'login'=>$buyer->email,'password'=>'Unverified-Password1',
        ])->assertSessionHasErrors('login');
        $notified=[];
        Notification::assertSentTo($buyer,CustomerPortalInvitationNotification::class,
            function ($notification) use (&$notified,$buyer): bool {
                $notified[]=$notification;
                $message=$notification->toMail($buyer);
                self::assertStringStartsWith('https://cms.ald1n.com/activate-account/',
                    (string)$message->actionUrl);
                self::assertStringNotContainsString('attacker.example.test',
                    (string)$message->actionUrl);
                return true;
            });
        self::assertCount(1,$notified);
        $plain=$notified[0]->token;
        self::assertSame(hash('sha256',$plain),UserActivationToken::query()->sole()->token_hash);
        $this->post('/activate-account',[
            'token'=>$plain,'password'=>'SyntheticSecure123Ab!',
            'password_confirmation'=>'SyntheticSecure123Ab!',
        ])->assertRedirect('/login');
        $buyer->refresh();
        self::assertSame('active',$buyer->status);
        self::assertNotNull($buyer->email_verified_at);
        self::assertNotNull($buyer->portal_activated_at);
        $this->post('/activate-account',[
            'token'=>$plain,'password'=>'SyntheticSecure123Ab!',
            'password_confirmation'=>'SyntheticSecure123Ab!',
        ])->assertSessionHasErrors('token');
        $this->post('/login',['login'=>$buyer->email,'password'=>'SyntheticSecure123Ab!'])
            ->assertRedirect(route('public-shop.cart'));
        self::assertTrue(auth()->check());
        self::assertSame($buyer->id,auth()->id());
        $this->get('/catalog')->assertForbidden();
    }

    public function test_existing_staff_email_does_not_become_a_customer_or_receive_tokens(): void
    {
        $payload=[
            'first_name'=>'Imposter','last_name'=>'Staff',
            'email'=>'b809-owner@example.test','accepted_privacy'=>'1',
            'role_id'=>Role::query()->where('slug','customer')->valueOrFail('id'),
        ];
        $this->post('/registracija',$payload)->assertRedirect();
        self::assertSame(1,User::query()->count());
        self::assertSame('superadmin',$this->owner->role->slug);
        self::assertDatabaseCount('user_activation_tokens',0);
        Notification::assertNothingSent();
    }

    public function test_only_registered_customer_can_buy_and_form_email_cannot_be_forged(): void
    {
        $buyer=$this->person('b809-buyer','customer');
        $this->actingAs($buyer);
        $this->post('/korpa/dodaj/'.$this->product->id,['quantity'=>1])->assertRedirect();
        $html=$this->get('/porucivanje')->assertOk()->getContent();
        self::assertStringNotContainsString('Bez registracije',$html);
        self::assertStringContainsString('readonly',$html);
        preg_match('/name="checkout_key" value="([a-f0-9]{48})"/',$html,$key);
        preg_match('/name="merchant_policy_fingerprint" value="([a-f0-9]{64})"/',$html,$policy);
        $input=[
            'checkout_key'=>$key[1],'merchant_policy_fingerprint'=>$policy[1],
            'shipping_full_name'=>'Synthetic Buyer','email'=>'victim@example.test',
            'shipping_phone'=>'0601234567','shipping_address'=>'Test 12',
            'shipping_city'=>'Beograd','shipping_postal_code'=>'11000',
            'payment_method'=>'cash_on_delivery',
            'delivery_method'=>'courier','accepted_terms'=>'1',
        ];
        $this->postJson('/porucivanje',$input)->assertUnprocessable()
            ->assertJsonValidationErrors('email');
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('stock_movements',0);
        $input['email']=$buyer->email;
        $location=$this->post('/porucivanje',$input)->assertRedirect()
            ->headers->get('Location');
        $order=Order::query()->sole();
        self::assertSame((int)$buyer->id,(int)$order->user_id);
        self::assertSame('unpaid',$order->payment_state);
        self::assertSame(3,(int)$this->product->fresh()->stock_quantity);
        self::assertDatabaseCount('order_commissions',0);
        self::assertDatabaseCount('order_payments',0);
        $this->get((string)$location)->assertOk()->assertDontSee('989999');
    }

    public function test_storefront_off_remains_404_for_public_signup_and_checkout(): void
    {
        config()->set('public_storefront.enabled',false);
        $this->get('/registracija')->assertNotFound();
        $this->post('/registracija',[
            'first_name'=>'Test','last_name'=>'Buyer',
            'email'=>'new@example.test','accepted_privacy'=>'1',
        ])->assertNotFound();
        $this->get('/porucivanje')->assertNotFound();
        self::assertSame(1,User::query()->count());
    }

    private function person(string $username,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$username,'email'=>$username.'@example.test',
            'first_name'=>'Synthetic','password_hash'=>Hash::make('SyntheticOnly123!'),
            'status'=>'active',
        ]);
    }
}
