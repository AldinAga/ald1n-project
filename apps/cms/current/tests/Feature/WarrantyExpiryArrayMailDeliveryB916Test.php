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
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * B916: actual Laravel Notification -> MailChannel -> array transport,
 * using only synthetic MariaDB fixtures. No SMTP, real recipients or Expo HTTP.
 * This does not attest a real external provider or a post-send recall.
 */
final class WarrantyExpiryArrayMailDeliveryB916Test extends TestCase
{
    use RefreshDatabase;

    private User $seller;
    private User $admin;
    private Order $order;
    private ProductWarranty $warranty;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('services.operational_notifications.mail_enabled', true);
        config()->set('mail.default', 'array');
        config()->set('mail.mailers.array.transport', 'array');
        config()->set('mail.from.address', 'sender-b916@synthetic.example.test');
        config()->set('mail.from.name', 'B916 Local Test');
        config()->set('mobile.push.enabled', false);
        config()->set('queue.default', 'sync');
        $this->mailTransport()->flush();

        $this->seller = $this->newUser('b916-seller', 'user');
        $this->admin = $this->newUser('b916-superadmin', 'superadmin');
        foreach ([$this->seller, $this->admin] as $recipient) {
            $recipient->notificationPreference()->create([
                'in_app_enabled' => false,
                'email_enabled' => true,
                'push_enabled' => false,
                'warranty_updates' => true,
                'warranty_expiry_alerts' => true,
            ]);
        }
        $product = Product::query()->create([
            'sku' => 'B916-SYNTHETIC-PRODUCT',
            'slug' => 'b916-synthetic-product',
            'name' => 'Synthetic item for mail QA',
            'description' => 'No real inventory',
            'price_amount' => 12300,
            'price_currency' => 'RSD',
            'stock_quantity' => 1,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
        $this->order = Order::query()->create([
            'source_system' => 'laravel',
            'order_number' => 'B916-SYNTHETIC-ORDER',
            'user_id' => $this->seller->id,
            'supplier_user_id' => $this->admin->id,
            'supplier_name_snapshot' => $this->admin->displayName(),
            'supplier_role_snapshot' => 'SuperAdministrator',
            'status' => 'new',
            'inventory_state' => 'reserved',
            'shipping_full_name' => 'Synthetic B916 Recipient',
            'shipping_address' => 'Test Street 1',
            'shipping_city' => 'Test City',
            'shipping_postal_code' => '11000',
            'shipping_phone' => '0600000000',
            'subtotal_rsd' => 12300,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'payment_state' => 'unpaid',
            'paid_total_rsd' => 0,
        ]);
        $item = $this->order->items()->create([
            'product_id' => $product->id,
            'product_sku' => $product->sku,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price_original' => 12300,
            'original_currency' => 'RSD',
            'unit_price_rsd' => 12300,
            'line_total_rsd' => 12300,
        ]);
        $this->warranty = ProductWarranty::query()->create([
            'warranty_number' => 'B916-SYNTHETIC-WARRANTY',
            'order_id' => $this->order->id,
            'order_item_id' => $item->id,
            'product_id' => $product->id,
            'user_id' => $this->seller->id,
            'status' => 'active',
            'starts_at' => today()->subMonth()->toDateString(),
            'expires_at' => today()->addMonth()->toDateString(),
            'duration_months' => 2,
            'duration_days' => 0,
            'quantity' => 1,
            'serial_numbers_json' => [],
            'product_sku_snapshot' => $product->sku,
            'product_name_snapshot' => $product->name,
            'customer_name_snapshot' => 'Synthetic B916 Recipient',
        ]);
    }

    public function test_opted_in_seller_reaches_actual_laravel_mail_channel_once(): void
    {
        $this->sendExpiry($this->seller);
        self::assertCount(1, $this->mailTransport()->messages());
        $sent = $this->mailTransport()->messages()->first();
        $message = $sent->getOriginalMessage();
        self::assertInstanceOf(Email::class, $message);
        self::assertSame($this->seller->email, $message->getTo()[0]->getAddress());
        self::assertSame('B916 Synthetic Warranty Expiry', $message->getSubject());
        self::assertStringContainsString('B916 disposable mail body', (string) $message->getTextBody());
    }

    public function test_without_expiry_opt_in_even_forced_email_is_not_sent(): void
    {
        $this->seller->notificationPreference()->update(['warranty_expiry_alerts' => false]);
        $this->sendExpiry($this->seller);
        self::assertCount(0, $this->mailTransport()->messages());
    }

    public function test_without_user_email_channel_even_forced_email_is_not_sent(): void
    {
        $this->seller->notificationPreference()->update(['email_enabled' => false]);
        $this->sendExpiry($this->seller);
        self::assertCount(0, $this->mailTransport()->messages());
    }

    public function test_global_operational_mail_off_denies_mail(): void
    {
        config()->set('services.operational_notifications.mail_enabled', false);
        $this->sendExpiry($this->seller);
        self::assertCount(0, $this->mailTransport()->messages());
    }

    public function test_expiry_opt_out_blocks_new_mail_after_one_completed_mail(): void
    {
        $this->sendExpiry($this->seller);
        self::assertCount(1, $this->mailTransport()->messages());
        $this->seller->notificationPreference()->update(['warranty_expiry_alerts' => false]);
        $this->sendExpiry($this->seller);
        self::assertCount(1, $this->mailTransport()->messages());
    }

    public function test_warranty_void_blocks_new_mail_after_previous_send(): void
    {
        $this->sendExpiry($this->seller);
        self::assertCount(1, $this->mailTransport()->messages());
        ProductWarranty::query()->whereKey($this->warranty->id)->update(['status' => 'void']);
        $this->sendExpiry($this->seller);
        self::assertCount(1, $this->mailTransport()->messages());
    }

    public function test_order_cancel_blocks_new_mail_after_previous_send(): void
    {
        $this->sendExpiry($this->seller);
        self::assertCount(1, $this->mailTransport()->messages());
        Order::query()->whereKey($this->order->id)->update(['status' => 'cancelled']);
        $this->sendExpiry($this->seller);
        self::assertCount(1, $this->mailTransport()->messages());
    }

    public function test_cached_superadmin_role_cannot_authorize_after_real_demotion(): void
    {
        $this->sendExpiry($this->admin);
        self::assertCount(1, $this->mailTransport()->messages());
        User::query()->whereKey($this->admin->id)->update([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
        ]);
        $this->sendExpiry($this->admin);
        self::assertCount(1, $this->mailTransport()->messages());
    }

    public function test_unrelated_user_with_expiry_opt_in_does_not_receive_mail(): void
    {
        $stranger = $this->newUser('b916-stranger', 'user');
        $stranger->notificationPreference()->create([
            'in_app_enabled' => false, 'email_enabled' => true,
            'push_enabled' => false, 'warranty_updates' => true,
            'warranty_expiry_alerts' => true,
        ]);
        $this->sendExpiry($stranger);
        self::assertCount(0, $this->mailTransport()->messages());
    }

    public function test_notification_is_not_laravel_queue_job_and_invalid_warranty_cannot_mail(): void
    {
        $notification = new OperationalNotification([
            'title' => 'B916', '_in_app' => false, '_email' => true,
        ]);
        self::assertFalse($notification instanceof ShouldQueue);
        self::assertSame(['mail'], $notification->via($this->seller));
        $this->sendExpiry($this->seller, 99999999);
        self::assertCount(0, $this->mailTransport()->messages());
    }

    private function sendExpiry(User $user, ?int $warrantyId = null): void
    {
        app(OperationalNotificationService::class)->send($user, [
            'event' => 'warranty.expiry_reminder',
            'warranty_id' => $warrantyId ?? $this->warranty->id,
            'title' => 'B916 Synthetic Warranty Expiry',
            'message' => 'B916 disposable mail body',
            '_in_app' => false,
            '_email' => true,
            '_push' => false,
        ]);
    }

    private function mailTransport(): ArrayTransport
    {
        $mailer = Mail::mailer('array');
        $transport = $mailer->getSymfonyTransport();
        self::assertInstanceOf(ArrayTransport::class, $transport);
        return $transport;
    }

    private function newUser(string $username, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => $username,
            'email' => $username.'@synthetic.example.test',
            'first_name' => 'Synthetic',
            'password_hash' => Hash::make('B916DisposableOnly!'),
            'status' => 'active',
        ]);
    }
}
