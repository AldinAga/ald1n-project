<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MobilePushOutbox;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductWarranty;
use App\Models\Role;
use App\Models\User;
use App\Notifications\OperationalNotification;
use App\Services\OperationalNotificationService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class WarrantyExpiryDeliveryGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_default_off_denies_expiry_even_when_general_warranty_updates_are_enabled(): void
    {
        [$buyer, $other, $warranty] = $this->makeWarranty('off');
        Notification::fake();

        $this->sendExpiry($buyer, $warranty->id);

        Notification::assertNothingSent();
        self::assertTrue(Schema::hasColumn('notification_preferences', 'warranty_expiry_alerts'));
        self::assertFalse((bool) $buyer->notificationPreference()->firstOrFail()->warranty_expiry_alerts);
        self::assertTrue((bool) $buyer->notificationPreference()->firstOrFail()->warranty_updates);
    }

    public function test_only_immutable_order_item_customer_with_explicit_opt_in_may_receive_expiry(): void
    {
        [$buyer, $other, $warranty] = $this->makeWarranty('on');
        $buyer->notificationPreference()->updateOrCreate([], [
            'in_app_enabled' => true,
            'email_enabled' => false,
            'push_enabled' => false,
            'warranty_updates' => true,
            'warranty_expiry_alerts' => true,
        ]);
        $other->notificationPreference()->updateOrCreate([], [
            'in_app_enabled' => true,
            'email_enabled' => false,
            'push_enabled' => false,
            'warranty_updates' => true,
            'warranty_expiry_alerts' => true,
        ]);
        Notification::fake();

        // The editable warranty.user_id must NOT grant eligibility to a different user.
        $warranty->update(['user_id' => $other->id]);
        $this->sendExpiry($other, $warranty->id);
        $this->sendExpiry($buyer, null);
        $this->sendExpiry($buyer, 999999);
        Notification::assertNothingSent();

        $this->sendExpiry($buyer, $warranty->id);
        Notification::assertSentTo($buyer, OperationalNotification::class, 1);
        Notification::assertNotSentTo($other, OperationalNotification::class);

        // A warranty with mismatched order ID is never a valid owner chain.
        $secondOrder = $this->createOrder($other, $this->admin(), $this->product(), 'expiry-other-order');
        $warranty->update(['order_id' => $secondOrder->id]);
        Notification::fake();
        $this->sendExpiry($buyer, $warranty->id);
        Notification::assertNothingSent();
    }


    public function test_default_off_blocks_email_and_push_but_regular_warranty_updates_still_deliver(): void
    {
        [$buyer, , $warranty] = $this->makeWarranty('channels');
        $buyer->notificationPreference()->updateOrCreate([], [
            'in_app_enabled' => true,
            'email_enabled' => true,
            'push_enabled' => true,
            'warranty_updates' => true,
            'warranty_expiry_alerts' => false,
        ]);
        $buyer->mobileDevices()->create([
            'installation_id' => 'e3fd1fb4-fb95-41f2-a9bc-bbedc50e27d6',
            'platform' => 'android',
            'push_provider' => 'expo',
            'push_token_hash' => hash('sha256', 'warranty-channels-'.$buyer->id),
            'notifications_enabled' => true,
        ]);
        config()->set('mobile.push.enabled', true);
        config()->set('mobile.push.provider', 'expo');
        Notification::fake();

        // Positive control: delivery channels are live for ordinary warranty events.
        app(OperationalNotificationService::class)->send($buyer, [
            'event' => 'warranty.updated',
            'title' => 'Garancija je ažurirana',
            'message' => 'Test obaveštenje.',
            '_in_app' => true,
            '_email' => false,
            '_push' => true,
        ]);
        Notification::assertSentTo($buyer, OperationalNotification::class, 1);
        self::assertSame(1, MobilePushOutbox::query()->where('user_id', $buyer->id)->count());

        // Negative control: the expiry opt-in must block all three channels.
        app(OperationalNotificationService::class)->send($buyer, [
            'event' => 'warranty.expiration_reminder',
            'title' => 'Ističe garancija',
            'message' => 'Test isteka.',
            'warranty_id' => $warranty->id,
            '_in_app' => true,
            '_email' => true,
            '_push' => true,
        ]);
        Notification::assertSentTo($buyer, OperationalNotification::class, 1);
        self::assertSame(1, MobilePushOutbox::query()->where('user_id', $buyer->id)->count());
    }

    public function test_push_only_expiry_goes_to_opted_in_verified_buyer_not_other_user(): void
    {
        [$buyer, $other, $warranty] = $this->makeWarranty('pushowner');
        foreach ([$buyer, $other] as $recipient) {
            $recipient->notificationPreference()->updateOrCreate([], [
                'in_app_enabled' => false,
                'email_enabled' => false,
                'push_enabled' => true,
                'warranty_updates' => true,
                'warranty_expiry_alerts' => true,
            ]);
            $recipient->mobileDevices()->create([
                'installation_id' => '3dfb4e70-a138-4e0c-a8a7-'.str_pad((string) $recipient->id, 12, '0', STR_PAD_LEFT),
                'platform' => 'android',
                'push_provider' => 'expo',
                'push_token_hash' => hash('sha256', 'warranty-pushowner-'.$recipient->id),
                'notifications_enabled' => true,
            ]);
        }
        config()->set('mobile.push.enabled', true);
        config()->set('mobile.push.provider', 'expo');

        foreach ([$other, $buyer] as $recipient) {
            app(OperationalNotificationService::class)->send($recipient, [
                'event' => 'warranty.expiry_reminder',
                'title' => 'Ističe garancija',
                'message' => 'Test isteka.',
                'warranty_id' => $warranty->id,
                '_in_app' => false,
                '_email' => false,
                '_push' => true,
            ]);
        }
        self::assertSame(0, MobilePushOutbox::query()->where('user_id', $other->id)->count());
        self::assertSame(1, MobilePushOutbox::query()->where('user_id', $buyer->id)->count());
    }

    public function test_expiry_consent_is_independent_of_general_warranty_updates(): void
    {
        [$buyer, , $warranty] = $this->makeWarranty('generaloff');
        $buyer->notificationPreference()->updateOrCreate([], [
            'in_app_enabled' => true,
            'email_enabled' => false,
            'push_enabled' => false,
            'warranty_updates' => false,
            'warranty_expiry_alerts' => true,
        ]);
        Notification::fake();

        // Explicit expiry consent and matching immutable owner suffice;
        // disabling ordinary warranty updates must not suppress expiry.
        $this->sendExpiry($buyer, $warranty->id);
        Notification::assertSentTo($buyer, OperationalNotification::class, 1);

        // General warranty category stays disabled independently.
        Notification::fake();
        app(OperationalNotificationService::class)->send($buyer, [
            'event' => 'warranty.updated',
            'title' => 'Ažurirana garancija',
            'message' => 'Redovno garancijsko obaveštenje.',
            '_in_app' => true,
            '_email' => false,
            '_push' => false,
        ]);
        Notification::assertNothingSent();

        // Turning expiry consent OFF blocks expiry even while general updates remain OFF.
        $buyer->notificationPreference()->update(['warranty_expiry_alerts' => false]);
        $this->sendExpiry($buyer, $warranty->id);
        Notification::assertNothingSent();
    }

    public function test_expiry_opt_in_is_independent_for_originating_seller_and_superadministrator_only(): void
    {
        [$seller, $other, $warranty] = $this->makeWarranty('separate');
        $super = User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'expiry-opted-superadmin',
            'email' => 'expiry-opted-superadmin@example.test',
            'first_name' => 'Operator',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
        $admin = $this->admin();
        foreach ([$seller, $other, $super, $admin] as $user) {
            $user->notificationPreference()->updateOrCreate([], [
                'in_app_enabled' => true, 'email_enabled' => false, 'push_enabled' => false,
                'warranty_expiry_alerts' => false, 'warranty_updates' => true,
            ]);
        }
        $policy = app(\App\Services\WarrantyExpiryRecipientPolicy::class);
        self::assertCount(0, $policy->recipients($warranty));

        $super->notificationPreference()->update(['warranty_expiry_alerts' => true]);
        self::assertSame([$super->id], $policy->recipients($warranty)->pluck('id')->all());

        $seller->notificationPreference()->update(['warranty_expiry_alerts' => true]);
        self::assertEqualsCanonicalizing([$seller->id, $super->id], $policy->recipients($warranty)->pluck('id')->all());

        $other->notificationPreference()->update(['warranty_expiry_alerts' => true]);
        $admin->notificationPreference()->update(['warranty_expiry_alerts' => true]);
        Notification::fake();
        foreach ([$seller, $super, $other, $admin] as $user) $this->sendExpiry($user, $warranty->id);
        Notification::assertSentTo($seller, OperationalNotification::class, 1);
        Notification::assertSentTo($super, OperationalNotification::class, 1);
        Notification::assertNotSentTo($other, OperationalNotification::class);
        Notification::assertNotSentTo($admin, OperationalNotification::class);

        $super->notificationPreference()->update(['warranty_expiry_alerts' => false]);
        Notification::fake();
        $this->sendExpiry($super, $warranty->id);
        Notification::assertNothingSent();

        // A mismatched order-item/order link also blocks the SuperAdministrator exception.
        $secondOrder = $this->createOrder($other, $admin, $this->product(), 'expiry-mismatched-valid-fk');
        $warranty->update(['order_id' => $secondOrder->id]);
        Notification::fake();
        $this->sendExpiry($seller, $warranty->id);
        $super->notificationPreference()->update(['warranty_expiry_alerts' => true]);
        $this->sendExpiry($super, $warranty->id);
        Notification::assertNothingSent();
    }

    public function test_expiry_push_queued_before_opt_out_is_blocked_at_dispatch(): void
    {
        [$seller, , $warranty] = $this->makeWarranty('revoke');
        $seller->notificationPreference()->updateOrCreate([], [
            'in_app_enabled' => false, 'email_enabled' => false,
            'push_enabled' => true, 'warranty_expiry_alerts' => true,
        ]);
        $seller->mobileDevices()->create([
            'installation_id' => '89a8d797-14a0-40ed-917e-10810cc16a91',
            'platform' => 'android',
            'push_provider' => 'expo',
            'push_token_hash' => hash('sha256', 'warranty-revocation'),
            'notifications_enabled' => true,
        ]);
        config()->set('mobile.push.enabled', true);
        config()->set('mobile.push.provider', 'expo');
        app(OperationalNotificationService::class)->send($seller, [
            'event' => 'warranty.expiry_reminder', 'warranty_id' => $warranty->id,
            'title' => 'Ističe', 'message' => 'Test red za odjavu',
            '_in_app' => false, '_email' => false, '_push' => true,
        ]);
        $pending = MobilePushOutbox::query()->sole();
        self::assertSame('pending', $pending->status);

        $seller->notificationPreference()->update(['warranty_expiry_alerts' => false]);
        \Illuminate\Support\Facades\Http::fake();
        $result = app(\App\Services\MobilePushDispatcher::class)->dispatch(5);
        self::assertSame(0, $result['sent']);
        self::assertSame('failed', $pending->fresh()->status);
        \Illuminate\Support\Facades\Http::assertNothingSent();
    }

    /** @return array{User, User, ProductWarranty} */
    private function makeWarranty(string $key): array
    {
        $buyer = $this->user('expiry-'.$key.'-buyer');
        $other = $this->user('expiry-'.$key.'-other');
        $admin = $this->admin();
        $product = $this->product($admin, 'EXPIRY-'.$key);
        $order = $this->createOrder($buyer, $admin, $product, 'expiry-'.$key.'-order');
        $item = $order->items()->firstOrFail();
        $warranty = ProductWarranty::query()->create([
            'warranty_number' => 'EXPIRY-'.strtoupper($key),
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'user_id' => $buyer->id,
            'status' => 'active',
            'starts_at' => '2026-01-01',
            'expires_at' => '2027-01-01',
            'duration_months' => 12,
            'customer_name_snapshot' => 'Kupac',
            'product_name_snapshot' => $product->name,
        ]);
        return [$buyer, $other, $warranty];
    }

    private function sendExpiry(User $recipient, ?int $warrantyId): void
    {
        app(OperationalNotificationService::class)->send($recipient, [
            'event' => 'warranty.expiry_reminder',
            'title' => 'Ističe garancija',
            'message' => 'Obaveštenje o isteku.',
            'warranty_id' => $warrantyId,
            '_in_app' => true,
            '_email' => false,
            '_push' => false,
        ]);
    }

    private function createOrder(User $buyer, User $admin, Product $product, string $key): Order
    {
        $this->actingAs($buyer)->post('/orders', [
            'idempotency_key' => $key,
            'supplier_user_id' => $admin->id,
            'shipping_full_name' => 'Test Kupac',
            'shipping_address' => 'Test adresa 1',
            'shipping_city' => 'Beograd',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '060123456',
            'payment_method' => 'cash_on_delivery',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect();
        return Order::query()->latest('id')->firstOrFail();
    }

    private function user(string $username): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1,
            'username' => $username,
            'email' => $username.'@example.test',
            'first_name' => 'Kupac',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function admin(): User
    {
        $existing = User::query()->where('username', 'expiry-admin')->first();
        if ($existing instanceof User) return $existing;
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'expiry-admin',
            'email' => 'expiry-admin@example.test',
            'first_name' => 'Admin',
            'password_hash' => Hash::make('Secret123!'),
            'status' => 'active',
        ]);
    }

    private function product(?User $admin = null, string $sku = 'EXPIRY-ITEM'): Product
    {
        $existing = Product::query()->where('sku', $sku)->first();
        if ($existing instanceof Product) return $existing;
        return Product::query()->create([
            'sku' => $sku,
            'name' => 'Test '.$sku,
            'slug' => strtolower($sku),
            'price_amount' => 12000,
            'price_currency' => 'RSD',
            'description' => 'Test.',
            'stock_quantity' => 10,
            'status' => 'active',
            'created_by' => ($admin ?? $this->admin())->id,
        ]);
    }
}
