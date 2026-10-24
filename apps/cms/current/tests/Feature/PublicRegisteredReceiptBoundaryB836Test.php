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

    public function test_actual_logged_in_buyer_can_open_receipt_until_tracked_session_revoked(): void
    {
        $this->post('/login', [
            'login' => $this->buyer->email,
            'password' => 'B836-Synthetic-Only123!',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($this->buyer);
        $this->get($this->receipt())->assertOk()->assertSee('APC-B836-SYNTHETIC');
        self::assertGreaterThanOrEqual(1, UserLoginSession::query()->where('user_id', $this->buyer->id)->count());
        UserLoginSession::query()->where('user_id', $this->buyer->id)
            ->update(['revoked_at' => now(), 'revoked_by' => $this->owner->id]);
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
