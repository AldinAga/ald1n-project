<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Order,Role,User};
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Support\Facades\Hash;
use App\Models\PublicOrderAccess;
use App\Services\PublicOrderAccessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicOrderCapabilityB799Test extends TestCase
{
    use RefreshDatabase;
    private User $buyer;

    protected function setUp(): void
    {
        parent::setUp();
        // Test expiry and revocation, not cumulative per-IP throttling across the suite.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        config()->set('public_storefront.enabled',true);
        config()->set('services.turnstile.enabled',false);
        $this->seed(CoreAccessSeeder::class);
        $owner=$this->person('b799-cap-owner','owner@example.test','superadmin');
        $this->buyer=$this->person('b799-cap-buyer','buyer@example.test','customer');
        app(SettingsService::class)->putMany(['public_storefront_open'=>'1'],$owner->id);
        $this->loginBuyer($this->buyer);
    }

    public function test_public_order_capability_is_random_hashed_at_rest_and_scoped_to_one_order(): void
    {
        $order=$this->order('public_retail','cap1');
        $token=app(PublicOrderAccessService::class)->issue($order,'  BUYER@example.test ');
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D',$token);
        $row=PublicOrderAccess::query()->sole();
        self::assertSame(hash('sha256',$token),$row->token_hash);
        self::assertStringNotContainsString($token,$row->toJson());
        self::assertStringNotContainsString('buyer@example.test',$row->email_ciphertext);
        $this->get('/porudzbina/'.$token)->assertOk()
            ->assertSee($order->order_number)
            ->assertHeader('Referrer-Policy','no-referrer')
            ->assertHeader('Cache-Control','max-age=0, no-store, private');
        $other=$this->order('public_retail','cap2');
        $this->get('/porudzbina/'.str_repeat('e',64))->assertNotFound();
        $this->get('/porudzbina/'.$other->id)->assertNotFound();
        $this->get('/admin/orders/'.$order->id)->assertForbidden();
        $this->get('/porudzbina/'.$token)->assertDontSee($other->order_number);
    }

    public function test_token_expiry_and_revocation_are_hard_denials(): void
    {
        $order=$this->order('public_retail','expire');
        $token=app(PublicOrderAccessService::class)->issue($order,'buyer@example.test');
        $this->get('/porudzbina/'.$token)->assertOk();
        $row=PublicOrderAccess::query()->sole();
        $row->update(['expires_at'=>now()->subSecond()]);
        $this->get('/porudzbina/'.$token)->assertNotFound();
        $row->update(['expires_at'=>now()->addDays(2)]);
        $this->get('/porudzbina/'.$token)->assertOk();
        app(PublicOrderAccessService::class)->revoke($order);
        $this->get('/porudzbina/'.$token)->assertNotFound();
    }

    public function test_direct_sales_and_personal_orders_never_issue_public_capability(): void
    {
        foreach (['direct_sale','order'] as $channel) {
            $order=$this->order($channel,'private-'.$channel);
            try {
                app(PublicOrderAccessService::class)->issue($order,'buyer@example.test');
                self::fail('Non-retail order must never get guest capability');
            } catch (\Illuminate\Validation\ValidationException $e) {
                self::assertArrayHasKey('order',$e->errors());
            }
        }
        self::assertSame(0,PublicOrderAccess::query()->count());
    }

    public function test_pending_private_url_is_closed_when_storefront_disabled(): void
    {
        $order=$this->order('public_retail','closed');
        $token=app(PublicOrderAccessService::class)->issue($order,'buyer@example.test');
        config()->set('public_storefront.enabled',false);
        $this->get('/porudzbina/'.$token)->assertNotFound();
    }


    public function test_private_receipt_requires_the_matching_logged_in_buyer(): void
    {
        $order=$this->order('public_retail','owner-only');
        $token=app(PublicOrderAccessService::class)->issue($order,$this->buyer->email);
        $path='/porudzbina/'.$token;
        $this->get($path)->assertOk();
        $this->post('/logout')->assertRedirect();
        $this->get($path)->assertNotFound();
        $other=$this->person('b799-other-buyer','other@example.test','customer');
        $this->loginBuyer($other);
        $this->get($path)->assertNotFound();
        $this->loginBuyer($this->buyer);
        $this->get($path)->assertOk();
        self::assertDatabaseCount('orders',1);
        self::assertDatabaseCount('public_order_accesses',1);
        self::assertDatabaseCount('order_payments',0);
    }

    public function test_previously_issued_receipt_with_lost_order_owner_fails_closed(): void
    {
        $order=$this->order('public_retail','orphaned');
        $token=app(PublicOrderAccessService::class)->issue($order,$this->buyer->email);
        $path='/porudzbina/'.$token;
        $this->get($path)->assertOk();
        $order->forceFill(['user_id'=>null])->save();
        $this->post('/logout')->assertRedirect();
        $this->get($path)->assertNotFound();
        $this->loginBuyer($this->buyer);
        $this->get($path)->assertNotFound();
        self::assertDatabaseCount('orders',1);
        self::assertDatabaseCount('public_order_accesses',1);
        self::assertNull(PublicOrderAccess::query()->sole()->revoked_at);
        self::assertDatabaseCount('order_payments',0);
    }

    private function loginBuyer(User $buyer): void
    {
        if (auth()->check()) {
            $this->post('/logout')->assertRedirect();
        }
        $this->post('/login',[
            'login'=>$buyer->email,'password'=>'B799-Synthetic-Only123!',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($buyer);
    }

    private function person(string $username,string $email,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$username,'email'=>$email,
            'first_name'=>'Synthetic','last_name'=>'Buyer',
            'address'=>'Test 12','city'=>'Beograd','phone'=>'0601234567',
            'password_hash'=>Hash::make('B799-Synthetic-Only123!'),'status'=>'active',
            'email_verified_at'=>now(),'portal_activated_at'=>now(),
        ]);
    }

    private function order(string $channel,string $suffix): Order
    {
        return Order::query()->create([
            'source_system'=>'laravel','sales_channel'=>$channel,
            'order_number'=>'APC-B799-'.$suffix,
            'user_id'=>$this->buyer->id,'status'=>'new',
            'shipping_full_name'=>'Sinteticki Kupac',
            'shipping_address'=>'Test ulica 1',
            'shipping_city'=>'Beograd','shipping_postal_code'=>'11000',
            'shipping_phone'=>'0601111222',
            'subtotal_rsd'=>1000,'payment_method'=>'cash_on_delivery',
            'payment_status'=>'pending','payment_state'=>'unpaid',
            'inventory_state'=>'reserved','paid_total_rsd'=>0,
        ]);
    }
}
