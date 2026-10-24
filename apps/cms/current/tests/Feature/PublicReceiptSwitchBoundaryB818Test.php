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
 * B818: previously issued PRIVATE buyer receipt must respect a live SuperAdmin
 * storefront OFF switch. Standalone synthetic capability fixture, not checkout
 * or the separately safety-denied B815 buyer checkout test.
 */
final class PublicReceiptSwitchBoundaryB818Test extends TestCase
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
            'shipping_description'=>'B818 synthetic approved shipping.',
            'payment_methods'=>['cash_on_delivery'],
            'delivery_methods'=>['courier','own_transport','customer_pickup'],
            'support_email'=>'support@example.test',
        ] as $key=>$value) {
            config()->set('public_storefront.'.$key,$value);
        }

        $this->owner=$this->person('b818-owner','superadmin');
        $this->buyer=$this->person('b818-buyer','customer');
        $settings=app(SettingsService::class);
        $settings->putMany([
            'documents_company_name'=>'B818 Synthetic Merchant',
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
            'order_number'=>'APC-B818-SYNTHETIC',
            'user_id'=>$this->buyer->id,
            'supplier_user_id'=>$this->owner->id,
            'status'=>'new',
            'inventory_state'=>'reserved',
            'shipping_full_name'=>'B818 Synthetic Buyer',
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

    public function test_superadmin_emergency_off_hides_existing_valid_receipt(): void
    {
        $path=$this->receipt();
        $this->get($path)->assertOk()->assertSee('APC-B818-SYNTHETIC');
        self::assertSame(1,PublicOrderAccess::query()->count());

        app(PublicStorefrontStateService::class)->change(false,$this->owner);
        self::assertFalse(app(PublicStorefrontStateService::class)->isOpen());
        $this->get('/katalog')->assertNotFound();
        $this->get($path)->assertNotFound();
        self::assertDatabaseCount('orders',1);
        self::assertDatabaseCount('public_order_accesses',1);
        self::assertNull(PublicOrderAccess::query()->sole()->revoked_at);
    }

    public function test_receipt_subroutes_remain_off_after_private_switch(): void
    {
        app(PublicStorefrontStateService::class)->change(false,$this->owner);
        $path=$this->receipt();
        $this->get($path)->assertNotFound();
        $this->get($path.'/dokument/1')->assertNotFound();
        $this->get($path.'/garancija/1')->assertNotFound();
        self::assertDatabaseCount('orders',1);
        self::assertDatabaseCount('order_payments',0);
    }

    public function test_release_env_flag_remains_independent_emergency_off(): void
    {
        $path=$this->receipt();
        $this->get($path)->assertOk();
        config()->set('public_storefront.enabled',false);
        $this->get($path)->assertNotFound();
        config()->set('public_storefront.enabled',true);
        $this->get($path)->assertOk();
    }

    public function test_disabling_new_checkout_does_not_revoke_earlier_receipt(): void
    {
        config()->set('public_storefront.checkout_enabled',false);
        $this->get($this->receipt())->assertOk()
            ->assertHeader('Referrer-Policy','no-referrer');
    }

    public function test_revocation_survives_emergency_off_and_reopen(): void
    {
        $path=$this->receipt();
        app(PublicStorefrontStateService::class)->change(false,$this->owner);
        app(PublicOrderAccessService::class)->revoke($this->order);
        app(PublicStorefrontStateService::class)->change(true,$this->owner);
        $this->get($path)->assertNotFound();
        self::assertNotNull(PublicOrderAccess::query()->sole()->revoked_at);
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
            'first_name'=>'B818 Synthetic',
            'password_hash'=>Hash::make('B818-Synthetic-Only123!'),
            'status'=>'active',
            'email_verified_at'=>now(),
            'portal_activated_at'=>now(),
        ]);
    }
}
