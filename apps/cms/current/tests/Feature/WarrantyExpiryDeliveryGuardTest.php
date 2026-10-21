<?php

declare(strict_types=1);

namespace Tests\Feature;

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
        $buyer->notificationPreference()->create([
            'in_app_enabled' => true,
            'email_enabled' => false,
            'push_enabled' => false,
            'warranty_updates' => true,
            'warranty_expiry_alerts' => true,
        ]);
        $other->notificationPreference()->create([
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
