<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Order,PublicOrderAccess,Role,User};
use App\Services\{PublicMerchantPolicyService,PublicOrderAccessService,PublicStorefrontStateService,SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * B821: token rejection and private response headers on disposable synthetic data.
 * Independent from the safety-denied B815 buyer fixture.
 */
final class PublicReceiptTokenPrivacyB821Test extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $buyer;
    private Order $order;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('app.url','https://cms.ald1n.com');

        foreach ([
            'enabled'=>true,
            'checkout_enabled'=>true,
            'terms_url'=>'https://cms.ald1n.com/uslovi',
            'privacy_url'=>'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd'=>'0',
            'shipping_description'=>'B821 synthetic approved shipping.',
            'payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','own_transport','customer_pickup'],
            'support_email'=>'support@example.test',
        ] as $key=>$value) {
            config()->set('public_storefront.'.$key,$value);
        }

        $this->owner=$this->person('b821-owner','superadmin');
        $this->buyer=$this->person('b821-buyer','customer');
        $settings=app(SettingsService::class);
        $settings->putMany([
            'documents_company_name'=>'B821 Synthetic Merchant',
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

        $this->order=Order::query()->create([
            'source_system'=>'laravel',
            'sales_channel'=>'public_retail',
            'order_number'=>'APC-B821-SYNTHETIC',
            'user_id'=>$this->buyer->id,
            'supplier_user_id'=>$this->owner->id,
            'status'=>'new',
            'inventory_state'=>'reserved',
            'shipping_full_name'=>'B821 Synthetic Buyer',
            'shipping_address'=>'Test 12',
            'shipping_city'=>'Beograd',
            'shipping_postal_code'=>'11000',
            'shipping_phone'=>'0601234567',
            'subtotal_rsd'=>12500,
            'payment_method'=>'cash_on_delivery',
            'payment_status'=>'pending',
            'payment_state'=>'unpaid',
            'paid_total_rsd'=>0,
        ]);
        $this->token=app(PublicOrderAccessService::class)
            ->issue($this->order,(string)$this->buyer->email);
    }

    public function test_invalid_capability_shapes_never_reveal_order_and_do_not_mutate(): void
    {
        foreach (['invalid','1234567890',str_repeat('f',64)] as $bad) {
            $response=$this->get('/porudzbina/'.$bad);
            $response->assertNotFound();
            $response->assertDontSee('APC-B821-SYNTHETIC');
        }
        self::assertDatabaseCount('orders',1);
        self::assertDatabaseCount('public_order_accesses',1);
        self::assertNull(PublicOrderAccess::query()->sole()->revoked_at);
    }

    public function test_live_receipt_response_rejects_shared_caches_and_referrers(): void
    {
        $this->get($this->receipt())->assertOk()
            ->assertHeader('Cache-Control','private, no-store, max-age=0')
            ->assertHeader('Pragma','no-cache')
            ->assertHeader('X-Robots-Tag','noindex, nofollow, noarchive')
            ->assertHeader('Referrer-Policy','no-referrer')
            ->assertHeader('X-Content-Type-Options','nosniff');
        self::assertDatabaseCount('orders',1);
        self::assertDatabaseCount('public_order_accesses',1);
    }

    private function receipt(): string
    {
        return '/porudzbina/'.$this->token;
    }

    private function person(string $username,string $role): User
    {
        return User::query()->create([
            'role_id'=>Role::query()->where('slug',$role)->valueOrFail('id'),
            'username'=>$username,
            'email'=>$username.'@example.test',
            'first_name'=>'B821 Synthetic',
            'password_hash'=>Hash::make('B821-Synthetic-Only123!'),
            'status'=>'active',
            'email_verified_at'=>now(),
            'portal_activated_at'=>now(),
        ]);
    }
}
