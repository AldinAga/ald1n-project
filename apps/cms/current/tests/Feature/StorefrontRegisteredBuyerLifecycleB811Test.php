<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Order, Product, Role, User, UserActivationToken};
use App\Notifications\CustomerPortalInvitationNotification;
use App\Services\{PublicMerchantPolicyService, PublicRegistrationMailSenderService, SettingsService};
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Mail, Notification};
use Tests\TestCase;

/**
 * B811: new registered-only buyer integration scenarios. All data and email
 * are synthetic. Does not change or replace the historical guest fixtures.
 */
final class StorefrontRegisteredBuyerLifecycleB811Test extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        Notification::fake();
        Mail::fake();

        config()->set('app.url', 'https://cms.ald1n.com');
        config()->set('services.turnstile.enabled', false);
        config()->set('mail.default', 'array');
        foreach ([
            'enabled' => true,
            'checkout_enabled' => true,
            'terms_url' => 'https://cms.ald1n.com/uslovi',
            'privacy_url' => 'https://cms.ald1n.com/privatnost',
            'shipping_fee_rsd' => '0',
            'shipping_description' => 'B811 synthetic approved shipping.',
            'payment_methods' => ['cash_on_delivery'],
            'delivery_methods' => ['courier', 'own_transport', 'customer_pickup'],
            'support_email' => 'support@example.test',
        ] as $key => $value) {
            config()->set('public_storefront.'.$key, $value);
        }

        $this->owner = $this->customerOrOperator('b811-owner', 'superadmin');
        $address = 'signup@synthetic.example.test';
        $name = 'B811 Synthetic Activation';
        $settings = app(SettingsService::class);
        $settings->putMany([
            'public_registration_sender_address' => $address,
            'public_registration_sender_name' => $name,
            'public_registration_sender_digest' => PublicRegistrationMailSenderService::fingerprint($address, $name),
            'public_registration_sender_approved_by' => (string) $this->owner->id,
            'public_registration_sender_approved_at' => now()->toISOString(),
            'public_storefront_open' => '1',
            'documents_company_name' => 'B811 Synthetic Merchant',
            'documents_company_address' => 'Test 10',
            'documents_company_city' => 'Beograd',
            'documents_company_tax_id' => '100200300',
        ], $this->owner->id);

        $review = app(PublicMerchantPolicyService::class)->review();
        self::assertTrue($review['complete'], implode('; ', $review['issues']));
        $settings->putMany([
            'public_checkout_approval_digest' => $review['fingerprint'],
            'public_checkout_approved_by' => (string) $this->owner->id,
            'public_checkout_approved_at' => now()->toISOString(),
        ], $this->owner->id);

        $this->product = Product::query()->create([
            'sku' => 'B811-SYNTHETIC',
            'slug' => 'b811-synthetic',
            'name' => 'B811 synthetic laptop',
            'description' => 'Synthetic-only catalog fixture',
            'price_amount' => 12000,
            'price_currency' => 'RSD',
            'stock_quantity' => 4,
            'purchase_price_rsd' => 999999,
            'manual_commission_eur' => 888888,
            'status' => 'active',
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_guest_cart_survives_signup_activation_login_and_exactly_once_checkout(): void
    {
        $this->post('/korpa/dodaj/'.$this->product->id, ['quantity' => 1])
            ->assertRedirect('/korpa');
        $this->get('/porucivanje')->assertRedirect('/login');
        $this->post('/registracija', $this->registration())->assertRedirect('/registracija');

        $buyer = User::query()->where('email', 'b811-new-buyer@example.test')->sole();
        self::assertTrue($buyer->hasRole('customer'));
        self::assertSame('pending', $buyer->status);
        self::assertNull($buyer->email_verified_at);
        self::assertDatabaseCount('orders', 0);

        $token = '';
        Notification::assertSentTo(
            $buyer,
            CustomerPortalInvitationNotification::class,
            static function (CustomerPortalInvitationNotification $notice) use (&$token): bool {
                $token = $notice->token;
                return true;
            }
        );
        self::assertNotSame('', $token);
        $this->post('/activate-account', [
            'token' => $token,
            'password' => 'B811-Synthetic-Pass123!',
            'password_confirmation' => 'B811-Synthetic-Pass123!',
        ])->assertRedirect('/login');
        $buyer->refresh();
        self::assertSame('active', $buyer->status);
        self::assertNotNull($buyer->email_verified_at);
        self::assertNotNull($buyer->portal_activated_at);
        $this->post('/login', [
            'login' => $buyer->email,
            'password' => 'B811-Synthetic-Pass123!',
        ])->assertRedirect('/korpa');

        $this->get('/korpa')->assertOk()->assertSee('B811 synthetic laptop');
        $data = $this->checkoutPayload($buyer->email);
        $data['price_rsd'] = 1;
        $data['user_id'] = $this->owner->id;
        $first = $this->post('/porucivanje', $data)->assertRedirect();
        $location = (string) $first->headers->get('Location');
        self::assertStringContainsString('/porudzbina/', $location);
        $again = $this->post('/porucivanje', $data)->assertRedirect();
        self::assertSame($location, $again->headers->get('Location'));

        $order = Order::query()->sole();
        self::assertSame((int) $buyer->id, (int) $order->user_id);
        self::assertSame('public_retail', $order->sales_channel);
        self::assertSame('unpaid', $order->payment_state);
        self::assertSame(12000.0, (float) $order->subtotal_rsd);
        self::assertSame(3, (int) $this->product->fresh()->stock_quantity);
        self::assertDatabaseCount('orders', 1);
        self::assertDatabaseCount('stock_movements', 1);
        self::assertDatabaseCount('public_checkout_attempts', 1);
        self::assertDatabaseCount('public_order_accesses', 1);
        self::assertDatabaseCount('order_commissions', 0);
        self::assertDatabaseCount('order_payments', 0);
        $this->get($location)->assertOk()
            ->assertSee('B811 synthetic laptop')
            ->assertDontSee('999999')
            ->assertDontSee('888888');
    }

    public function test_duplicate_pending_registration_does_not_send_another_token_and_expired_token_fails(): void
    {
        $payload = $this->registration();
        $this->post('/registracija', $payload)->assertRedirect('/registracija');
        $buyer = User::query()->where('email', 'b811-new-buyer@example.test')->sole();
        $token = '';
        Notification::assertSentTo(
            $buyer,
            CustomerPortalInvitationNotification::class,
            static function (CustomerPortalInvitationNotification $notice) use (&$token): bool {
                $token = $notice->token;
                return true;
            }
        );
        self::assertNotSame('', $token);
        $record = UserActivationToken::query()->sole();
        self::assertSame(hash('sha256', $token), $record->token_hash);

        $this->post('/registracija', $payload)->assertRedirect('/registracija');
        self::assertDatabaseCount('users', 2);
        self::assertDatabaseCount('user_activation_tokens', 1);
        self::assertSame($record->id, UserActivationToken::query()->sole()->id);

        $record->forceFill(['expires_at' => now()->subMinute()])->save();
        $this->post('/activate-account', [
            'token' => $token,
            'password' => 'B811-Synthetic-Pass123!',
            'password_confirmation' => 'B811-Synthetic-Pass123!',
        ])->assertSessionHasErrors('token');
        self::assertSame('pending', $buyer->fresh()->status);
        self::assertNull($buyer->fresh()->email_verified_at);
        $this->get('/porucivanje')->assertRedirect('/login');
        $this->postJson('/porucivanje', [
            'checkout_key' => str_repeat('a', 48),
        ])->assertUnauthorized();
        self::assertDatabaseCount('orders', 0);
        self::assertDatabaseCount('stock_movements', 0);
    }

    public function test_completed_checkout_nonce_cannot_be_reused_by_another_customer(): void
    {
        $buyerA = $this->customerOrOperator('b811-buyer-a', 'customer');
        $buyerB = $this->customerOrOperator('b811-buyer-b', 'customer');
        $this->actingAs($buyerA);
        $this->post('/korpa/dodaj/'.$this->product->id, ['quantity' => 1])->assertRedirect();
        $payload = $this->checkoutPayload($buyerA->email);
        $this->post('/porucivanje', $payload)->assertRedirect();
        $order = Order::query()->sole();
        self::assertSame((int) $buyerA->id, (int) $order->user_id);

        $this->actingAs($buyerB);
        $payload['email'] = $buyerB->email;
        $this->postJson('/porucivanje', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors('checkout');
        self::assertSame((int) $buyerA->id, (int) Order::query()->sole()->user_id);
        self::assertDatabaseCount('orders', 1);
        self::assertDatabaseCount('stock_movements', 1);
        self::assertDatabaseCount('public_order_accesses', 1);
        self::assertSame(3, (int) $this->product->fresh()->stock_quantity);
    }

    public function test_changed_merchant_policy_blocks_submission_without_reservation(): void
    {
        $buyer = $this->customerOrOperator('b811-policy-buyer', 'customer');
        $this->actingAs($buyer);
        $this->post('/korpa/dodaj/'.$this->product->id, ['quantity' => 1])->assertRedirect();
        $payload = $this->checkoutPayload($buyer->email);

        // Simulate the merchant changing approved terms while a buyer has a
        // checkout form open. The old attestation must no longer authorize it.
        config()->set('public_storefront.shipping_description', 'B811 changed shipping policy.');
        $this->postJson('/porucivanje', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors('checkout');
        self::assertDatabaseCount('orders', 0);
        self::assertDatabaseCount('public_checkout_attempts', 0);
        self::assertDatabaseCount('stock_movements', 0);
        self::assertSame(4, (int) $this->product->fresh()->stock_quantity);
    }

    /** @return array<string, string> */
    private function checkoutPayload(string $email): array
    {
        $html = $this->get('/porucivanje')->assertOk()->getContent();
        self::assertSame(1, preg_match('/name="checkout_key" value="([a-f0-9]{48})"/', $html, $nonce));
        self::assertSame(1, preg_match('/name="merchant_policy_fingerprint" value="([a-f0-9]{64})"/', $html, $digest));

        return [
            'checkout_key' => $nonce[1],
            'merchant_policy_fingerprint' => $digest[1],
            'shipping_full_name' => 'B811 Synthetic Buyer',
            'email' => $email,
            'shipping_phone' => '0601234567',
            'shipping_address' => 'Test 12',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'payment_method' => 'cash_on_delivery',
            'delivery_method' => 'courier',
            'accepted_terms' => '1',
        ];
    }

    /** @return array<string, string> */
    private function registration(): array
    {
        return [
            'first_name' => 'B811',
            'last_name' => 'Synthetic',
            'email' => 'B811-New-Buyer@example.test',
            'phone' => '0601234567',
            'accepted_privacy' => '1',
        ];
    }

    private function customerOrOperator(string $username, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'username' => $username,
            'email' => $username.'@example.test',
            'first_name' => 'Synthetic',
            'password_hash' => Hash::make('B811-Synthetic-Pass123!'),
            'status' => 'active',
        ]);
    }
}
