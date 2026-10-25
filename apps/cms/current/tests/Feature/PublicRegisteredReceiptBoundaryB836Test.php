<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Order, PublicOrderAccess, Role, User, UserLoginSession};
use App\Services\{PublicMerchantPolicyService, PublicOrderAccessService, PublicStorefrontStateService, SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** B836: a newly issued registered-Kupac receipt is not an anonymous bearer login. */
final class PublicRegisteredReceiptBoundaryB836Test extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $buyer;
    private User $other;
    private Order $order;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('services.turnstile.enabled', false);
        config()->set('app.url', 'https://cms.ald1n.com');
        foreach ([
            'enabled' => true,
            'checkout_enabled' => true,
            'terms_url' => 'https://cms.ald1n.com/uslovi',
            'privacy_url' => 'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd' => '0',
            'shipping_description' => 'B836 synthetic approved shipping',
            'payment_methods' => ['cash_on_delivery'],
            'delivery_methods' => ['courier', 'own_transport', 'customer_pickup'],
            'support_email' => 'support@example.test',
        ] as $key => $value) {
            config()->set('public_storefront.'.$key, $value);
        }
        $this->owner = $this->person('b836-owner', 'superadmin');
        $this->buyer = $this->person('b836-buyer', 'customer');
        $this->other = $this->person('b836-other', 'customer');
        $settings = app(SettingsService::class);
        $settings->putMany([
            'documents_company_name' => 'B836 Synthetic Merchant',
            'documents_company_address' => 'Synthetic Address 1',
            'documents_company_city' => 'Beograd',
            'documents_company_tax_id' => '100200300',
        ], (int) $this->owner->id);
        $review = app(PublicMerchantPolicyService::class)->review();
        self::assertTrue($review['complete'], implode('; ', $review['issues']));
        $settings->putMany([
            'public_checkout_approval_digest' => $review['fingerprint'],
            'public_checkout_approved_by' => (string) $this->owner->id,
            'public_checkout_approved_at' => now()->toISOString(),
        ], (int) $this->owner->id);
        app(PublicStorefrontStateService::class)->change(true, $this->owner);
        $this->order = Order::query()->create([
            'source_system' => 'laravel',
            'sales_channel' => 'public_retail',
            'order_number' => 'APC-B836-SYNTHETIC',
            'user_id' => $this->buyer->id,
            'supplier_user_id' => $this->owner->id,
            'status' => 'new',
            'inventory_state' => 'reserved',
            'shipping_full_name' => 'B836 Synthetic Buyer',
            'shipping_address' => 'Synthetic 12',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '0601234567',
            'subtotal_rsd' => 12500,
            'payment_method' => 'cash_on_delivery',
            'payment_status' => 'pending',
            'payment_state' => 'unpaid',
            'paid_total_rsd' => 0,
        ]);
        $this->token = app(PublicOrderAccessService::class)
            ->issue($this->order, (string) $this->buyer->email);
    }

    public function test_anonymous_bearer_cannot_read_registered_order_or_pdfs(): void
    {
        $path = $this->receipt();
        $this->get($path)->assertNotFound();
        $this->get($path.'/dokument/1')->assertNotFound();
        $this->get($path.'/garancija/1')->assertNotFound();
        self::assertDatabaseCount('orders', 1);
        self::assertDatabaseCount('public_order_accesses', 1);
    }

    public function test_second_active_customer_and_staff_cannot_reuse_receipt_token(): void
    {
        $this->actingAs($this->other);
        $this->get($this->receipt())->assertNotFound();
        $this->actingAs($this->owner);
        $this->get($this->receipt())->assertNotFound();
    }

    public function test_actual_logged_in_buyer_can_open_receipt_until_logout(): void
    {
        $this->post('/login', [
            'login' => $this->buyer->email,
            'password' => 'B836-Synthetic-Only123!',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($this->buyer);
        $this->get($this->receipt())->assertOk()->assertSee('APC-B836-SYNTHETIC');
        self::assertGreaterThanOrEqual(1, UserLoginSession::query()->where('user_id', $this->buyer->id)->count());
        $this->post('/logout')->assertRedirect();
        $this->assertGuest();
        $this->get($this->receipt())->assertNotFound();
    }

    public function test_retired_buyer_token_stays_unusable_without_changing_order_or_access(): void
    {
        $this->buyer->forceFill(['retired_at' => now()])->save();
        $this->actingAs($this->buyer);
        $this->get($this->receipt())->assertNotFound();
        self::assertDatabaseCount('orders', 1);
        self::assertNull(PublicOrderAccess::query()->sole()->revoked_at);
    }

    /** B838: a real authenticated buyer cannot resurrect a revoked/expired capability. */
    public function test_registered_buyer_receipt_revocation_and_expiry_are_enforced(): void
    {
        $this->post('/login', [
            'login' => $this->buyer->email,
            'password' => 'B836-Synthetic-Only123!',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($this->buyer);
        $this->get($this->receipt())->assertOk();

        $capability = PublicOrderAccess::query()->sole();
        $capability->forceFill(['revoked_at' => now()])->save();
        $this->get($this->receipt())->assertNotFound();

        $capability->forceFill([
            'revoked_at' => null,
            'expires_at' => now()->subSecond(),
        ])->save();
        $this->get($this->receipt())->assertNotFound();
        self::assertDatabaseCount('orders', 1);
        self::assertDatabaseCount('public_order_accesses', 1);
    }

    /** B838: private receipt and document paths cannot survive SuperAdmin OFF. */
    public function test_registered_buyer_private_receipt_respects_emergency_off_and_reopen(): void
    {
        $this->post('/login', [
            'login' => $this->buyer->email,
            'password' => 'B836-Synthetic-Only123!',
        ])->assertRedirect();
        $this->get($this->receipt())->assertOk();

        app(PublicStorefrontStateService::class)->change(false, $this->owner);
        self::assertFalse(app(PublicStorefrontStateService::class)->isOpen());
        $this->get($this->receipt())->assertNotFound();
        $this->get($this->receipt().'/dokument/1')->assertNotFound();
        $this->get($this->receipt().'/garancija/1')->assertNotFound();

        app(PublicStorefrontStateService::class)->change(true, $this->owner);
        self::assertTrue(app(PublicStorefrontStateService::class)->isOpen());
        $this->get($this->receipt())->assertOk();
        self::assertDatabaseCount('orders', 1);
    }

    /** B839: no capability may be minted for a buyer who became inactive. */
    public function test_issue_rejects_inactive_registered_buyer_without_creating_token(): void
    {
        $unissued=$this->unissuedOrder('INACTIVE');
        $this->buyer->forceFill(['status'=>'blocked'])->save();

        try {
            app(PublicOrderAccessService::class)->issue($unissued,(string)$this->buyer->email);
            self::fail('Inactive buyer must never receive a new private capability.');
        } catch (\Illuminate\Validation\ValidationException) {
            self::assertDatabaseCount('public_order_accesses',1);
        }
    }

    /** B839: the same invariant holds for permanently retired accounts. */
    public function test_issue_rejects_retired_registered_buyer_without_creating_token(): void
    {
        $unissued=$this->unissuedOrder('RETIRED');
        $this->buyer->forceFill(['retired_at'=>now()])->save();

        try {
            app(PublicOrderAccessService::class)->issue($unissued,(string)$this->buyer->email);
            self::fail('Retired buyer must never receive a new private capability.');
        } catch (\Illuminate\Validation\ValidationException) {
            self::assertDatabaseCount('public_order_accesses',1);
        }
    }

    /** B839: a purged order must not get a fresh unreadable private link. */
    public function test_issue_rejects_purged_retail_order_without_creating_token(): void
    {
        $unissued=$this->unissuedOrder('PURGED');
        $unissued->forceFill(['purged_at'=>now()])->save();

        try {
            app(PublicOrderAccessService::class)->issue($unissued,(string)$this->buyer->email);
            self::fail('Purged order must never receive a new private capability.');
        } catch (\Illuminate\Validation\ValidationException) {
            self::assertDatabaseCount('public_order_accesses',1);
        }
    }

    /** B839: legitimate newly purchased items retain working private tracking. */
    public function test_issue_allows_second_active_registered_order_and_denies_anonymous_read(): void
    {
        $unissued=$this->unissuedOrder('ACTIVE');
        $token=app(PublicOrderAccessService::class)->issue($unissued,(string)$this->buyer->email);

        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D',$token);
        self::assertDatabaseCount('public_order_accesses',2);
        $this->get('/porudzbina/'.$token)->assertNotFound();
    }

    /** B841: active status alone must never bypass real verified email activation. */
    public function test_unverified_kupac_cannot_get_new_capability_or_open_existing_receipt(): void
    {
        $unissued=$this->unissuedOrder('UNVERIFIED');
        $this->buyer->forceFill(['email_verified_at'=>null])->save();

        try {
            app(PublicOrderAccessService::class)->issue($unissued,(string)$this->buyer->email);
            self::fail('An unverified account cannot mint a private receipt.');
        } catch (\Illuminate\Validation\ValidationException) {
            self::assertDatabaseCount('public_order_accesses',1);
        }
        $this->actingAs($this->buyer);
        $this->get($this->receipt())->assertNotFound();
        self::assertDatabaseCount('orders',2);
    }

    /** B841: incomplete portal activation is not equivalent to full registration. */
    public function test_active_but_never_activated_kupac_cannot_get_private_capability(): void
    {
        $unissued=$this->unissuedOrder('NOT-ACTIVATED');
        $this->buyer->forceFill(['portal_activated_at'=>null])->save();

        try {
            app(PublicOrderAccessService::class)->issue($unissued,(string)$this->buyer->email);
            self::fail('A never-activated account cannot mint a private receipt.');
        } catch (\Illuminate\Validation\ValidationException) {
            self::assertDatabaseCount('public_order_accesses',1);
        }
    }

    /** B841: both public checkout HTTP entrypoints reject unverified customer. */
    public function test_unverified_active_buyer_cannot_enter_or_submit_checkout(): void
    {
        $this->buyer->forceFill(['email_verified_at'=>null])->save();
        $this->actingAs($this->buyer);
        $this->get('/porucivanje')->assertForbidden();
        $this->post('/porucivanje',[])->assertForbidden();
        self::assertDatabaseCount('orders',1);
        self::assertDatabaseCount('public_checkout_attempts',0);
        self::assertDatabaseCount('public_order_accesses',1);
    }

    private function unissuedOrder(string $suffix): Order
    {
        $copy=$this->order->replicate();
        $copy->order_number='APC-B839-'.$suffix;
        $copy->saveOrFail();
        return $copy;
    }

    private function receipt(): string
    {
        return '/porudzbina/'.$this->token;
    }

    private function person(string $username, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'username' => $username,
            'email' => $username.'@example.test',
            'first_name' => 'B836 Synthetic',
            'password_hash' => Hash::make('B836-Synthetic-Only123!'),
            'status' => 'active',
            'email_verified_at' => now(),
            'portal_activated_at' => now(),
        ]);
    }
}
