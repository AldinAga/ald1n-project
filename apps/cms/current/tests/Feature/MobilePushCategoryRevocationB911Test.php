<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MobilePushOutbox;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductWarranty;
use App\Models\Role;
use App\Models\User;
use App\Services\MobilePushDispatcher;
use App\Services\OperationalNotificationService;
use App\Services\PushEventCategory;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

/**
 * B911 - Synthetic PHP8.4/MariaDB10.11, no real Expo/SMTP or customer records.
 * A category or shipment channel opted out AFTER enqueue cannot deliver.
 * Preserve the separately opted-in M11 warranty-expiry exception.
 */
final class MobilePushCategoryRevocationB911Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('mobile.push.enabled', true);
        config()->set('mobile.push.provider', 'expo');
        config()->set('mobile.push.expo.send_url', 'https://exp.host/--/api/v2/push/send');
        config()->set('mobile.push.expo.receipts_url', 'https://exp.host/--/api/v2/push/getReceipts');
        Http::preventStrayRequests();
    }

    public function test_payment_category_disabled_after_enqueue_blocks_delivery(): void
    {
        [$user, $row] = $this->queue('payment-off', 'payment.verified');
        $user->notificationPreference()->update(['payment_alerts' => false]);
        $this->assertDeclined($row);
    }

    public function test_document_category_disabled_after_enqueue_blocks_delivery(): void
    {
        [$user, $row] = $this->queue('document-off', 'document.issued');
        $user->notificationPreference()->update(['document_updates' => false]);
        $this->assertDeclined($row);
    }

    public function test_after_sales_category_disabled_after_enqueue_blocks_delivery(): void
    {
        [$user, $row] = $this->queue('after-sales-off', 'after_sales.case_updated');
        $user->notificationPreference()->update(['after_sales_updates' => false]);
        $this->assertDeclined($row);
    }

    public function test_standard_order_category_disabled_after_enqueue_blocks_delivery(): void
    {
        [$user, $row] = $this->queue('order-off', 'order.status_changed');
        $user->notificationPreference()->update(['order_updates' => false]);
        $this->assertDeclined($row);
    }

    public function test_shipment_tracking_push_is_revoked_when_channel_changes_to_email(): void
    {
        [$user, $row] = $this->queue('shipment-email', 'order.shipment_tracking');
        $user->notificationPreference()->update(['shipment_tracking_channel' => 'email']);
        $this->assertDeclined($row);
    }

    public function test_enabled_payment_push_delivers_once_without_false_denials(): void
    {
        [, $row] = $this->queue('payment-on', 'payment.verified');
        $this->allowOneHttp();
        $result = app(MobilePushDispatcher::class)->dispatch(5);
        self::assertSame(1, $result['sent']);
        self::assertSame(0, $result['failed']);
        self::assertSame('sent', $row->fresh()->status);
        Http::assertSentCount(1);
    }

    public function test_payment_category_opt_out_between_retry_attempts_denies_second_http(): void
    {
        [$user, $row] = $this->queue('payment-retry', 'payment.verified');
        Http::fake(['https://exp.host/--/api/v2/push/send' => Http::response(['error' => 'disposable'], 503)]);
        $first = app(MobilePushDispatcher::class)->dispatch(5);
        self::assertSame(1, $first['retried']);
        self::assertSame('pending', $row->fresh()->status);
        Http::assertSentCount(1);

        $user->notificationPreference()->update(['payment_alerts' => false]);
        // The dispatcher re-schedules a DIFFERENT Eloquent instance on retry.
        // Writing a pre-retry, stale model can be a no-op (same original second).
        // Set the due time on the persisted row and verify the precondition.
        self::assertSame('pending', $row->fresh()->status);
        self::assertTrue($row->fresh()->scheduled_for->greaterThan(now()));
        $rescheduled = MobilePushOutbox::query()
            ->whereKey($row->id)
            ->where('status', 'pending')
            ->update(['scheduled_for' => now()->subMinute()]);
        self::assertSame(1, $rescheduled);
        self::assertTrue($row->fresh()->scheduled_for->lessThanOrEqualTo(now()));
        $second = app(MobilePushDispatcher::class)->dispatch(5);
        self::assertSame(1, $second['processed']);
        self::assertSame(0, $second['sent']);
        self::assertSame(0, $second['retried']);
        self::assertSame(1, $second['failed']);
        self::assertSame('failed', $row->fresh()->status);
        Http::assertSentCount(1);
    }

    public function test_independently_opted_in_expiry_remains_allowed_with_general_warranty_updates_off(): void
    {
        [$seller, $row] = $this->queue('expiry-independent', 'warranty.expiry_reminder', 'user');
        $seller->notificationPreference()->update([
            'warranty_updates' => false,
            'warranty_expiry_alerts' => true,
        ]);
        $admin = $this->user('expiry-admin', 'superadmin');
        $product = Product::query()->create([
            'sku' => 'B911-EXPIRY-PRODUCT', 'slug' => 'b911-expiry-product',
            'name' => 'Synthetic B911 item', 'description' => 'Not for real commerce',
            'price_amount' => 12000, 'price_currency' => 'RSD',
            'stock_quantity' => 1, 'status' => 'active', 'created_by' => $admin->id,
        ]);
        $order = Order::query()->create([
            'source_system' => 'laravel', 'order_number' => 'B911-SYNTHETIC-ORDER',
            'user_id' => $seller->id, 'supplier_user_id' => $admin->id,
            'supplier_name_snapshot' => $admin->displayName(),
            'supplier_role_snapshot' => 'SuperAdministrator',
            'status' => 'new', 'inventory_state' => 'reserved',
            'shipping_full_name' => 'Synthetic B911', 'shipping_address' => 'Disposable 1',
            'shipping_city' => 'Synthetic', 'shipping_postal_code' => '11000',
            'shipping_phone' => '0600000000', 'subtotal_rsd' => 12000,
            'payment_method' => 'bank_transfer', 'payment_status' => 'pending',
            'payment_state' => 'unpaid', 'paid_total_rsd' => 0,
        ]);
        $item = $order->items()->create([
            'product_id' => $product->id, 'product_sku' => $product->sku,
            'product_name' => $product->name, 'quantity' => 1,
            'unit_price_original' => 12000, 'original_currency' => 'RSD',
            'unit_price_rsd' => 12000, 'line_total_rsd' => 12000,
        ]);
        $warranty = ProductWarranty::query()->create([
            'warranty_number' => 'B911-SYNTHETIC-WARRANTY',
            'order_id' => $order->id, 'order_item_id' => $item->id,
            'product_id' => $product->id, 'user_id' => $seller->id,
            'status' => 'active', 'starts_at' => today()->subMonth()->toDateString(),
            'expires_at' => today()->addMonth()->toDateString(),
            'duration_months' => 2, 'duration_days' => 0, 'quantity' => 1,
            'serial_numbers_json' => [], 'product_sku_snapshot' => $product->sku,
            'product_name_snapshot' => $product->name,
            'customer_name_snapshot' => 'Synthetic B911',
        ]);
        $row->forceFill(['data_json' => ['warranty_id' => $warranty->id]])->save();
        $this->allowOneHttp();
        $result = app(MobilePushDispatcher::class)->dispatch(5);
        self::assertSame(1, $result['sent']);
        self::assertSame('sent', $row->fresh()->status);
        Http::assertSentCount(1);
    }

    public function test_dispatch_classifier_matches_existing_enqueue_category_contract(): void
    {
        $instance = (new ReflectionClass(OperationalNotificationService::class))->newInstanceWithoutConstructor();
        $category = new ReflectionMethod(OperationalNotificationService::class, 'category');
        foreach ([
            'order.status_changed', 'order.shipment_tracking', 'payment.verified',
            'refund.confirmed', 'document.issued', 'invoice.issued',
            'warranty.updated', 'warranty.expiry_reminder', 'maintenance.started',
            'after_sales.case_updated', 'portal_message.received', 'return.accepted',
            'field_work.order_completed', 'service_visit.scheduled',
            'receivable.created', 'installment.paid', 'collection.overdue',
            'commission.paid', 'stock.low', 'inventory.changed', 'daily_digest.sent',
        ] as $event) {
            self::assertSame($category->invoke($instance, $event), PushEventCategory::fromEvent($event), $event);
        }
    }

    /** @return array{User,MobilePushOutbox} */
    private function queue(string $suffix, string $event, string $role = 'admin'): array
    {
        $user = $this->user($suffix, $role);
        $user->notificationPreference()->create([
            'in_app_enabled' => false, 'email_enabled' => false, 'push_enabled' => true,
            'shipment_tracking_channel' => 'both',
            'order_updates' => true, 'payment_alerts' => true,
            'document_updates' => true, 'after_sales_updates' => true,
            'warranty_updates' => true, 'warranty_expiry_alerts' => false,
            'service_updates' => true, 'receivable_updates' => true,
            'commission_updates' => true, 'stock_alerts' => true, 'daily_digest' => true,
        ]);
        $token = 'ExponentPushToken[b911-'.$user->id.']';
        $device = $user->mobileDevices()->create([
            'installation_id' => (string) Str::uuid(), 'platform' => 'android',
            'device_name' => 'B911 Synthetic Android', 'push_provider' => 'expo',
            'push_token' => $token, 'push_token_hash' => hash('sha256', $token),
            'notifications_enabled' => true, 'last_seen_at' => now(),
        ]);
        $row = MobilePushOutbox::query()->create([
            'user_id' => $user->id, 'mobile_device_id' => $device->id,
            'provider' => 'expo', 'event' => $event,
            'title' => 'Synthetic B911', 'message' => 'Disposable CI only',
            'data_json' => ['order_id' => 7], 'status' => 'pending',
            'scheduled_for' => now()->subSecond(),
        ]);
        return [$user, $row];
    }

    private function user(string $suffix, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'user_group_id' => $role === 'user' ? 1 : null,
            'username' => 'b911-'.$suffix,
            'email' => 'b911-'.$suffix.'@synthetic.example.test',
            'password_hash' => Hash::make('B911OnlySynthetic!'),
            'first_name' => 'Synthetic', 'last_name' => 'B911',
            'status' => 'active',
        ]);
    }

    private function assertDeclined(MobilePushOutbox $row): void
    {
        Http::fake();
        $result = app(MobilePushDispatcher::class)->dispatch(5);
        self::assertSame(1, $result['processed']);
        self::assertSame(0, $result['sent']);
        self::assertSame(0, $result['retried']);
        self::assertSame(1, $result['failed']);
        self::assertSame('failed', $row->fresh()->status);
        Http::assertNothingSent();
    }

    private function allowOneHttp(): void
    {
        Http::fake(['https://exp.host/--/api/v2/push/send' => Http::response([
            'data' => ['status' => 'ok', 'id' => 'ticket-b911-positive'],
        ])]);
    }
}
