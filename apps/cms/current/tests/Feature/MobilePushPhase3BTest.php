<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MobileDevice;
use App\Models\MobilePushOutbox;
use App\Models\Role;
use App\Models\User;
use App\Services\MobilePushDispatcher;
use App\Services\OperationalNotificationService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class MobilePushPhase3BTest extends TestCase
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
        config()->set('mobile.push.expo.receipt_delay_minutes', 15);
    }

    public function test_operational_notification_enqueues_sends_and_confirms_expo_push(): void
    {
        $user = $this->user();
        $user->notificationPreference()->create([
            'in_app_enabled' => true,
            'email_enabled' => false,
            'push_enabled' => true,
            'order_updates' => true,
            'payment_alerts' => true,
            'document_updates' => true,
            'after_sales_updates' => true,
            'warranty_updates' => true,
            'service_updates' => true,
            'receivable_updates' => true,
            'commission_updates' => true,
            'stock_alerts' => false,
            'daily_digest' => false,
        ]);
        $device = $this->device($user, 'ExponentPushToken[phase3b-test]');

        app(OperationalNotificationService::class)->send($user, [
            'event' => 'order.status_changed',
            'title' => 'Status porudžbine',
            'message' => 'Porudžbina je prihvaćena.',
            'order_id' => 42,
            'severity' => 'info',
        ]);

        $row = MobilePushOutbox::query()->sole();
        self::assertSame($device->id, $row->mobile_device_id);
        self::assertSame('/orders/42', $row->data_json['route']);

        Http::fake([
            'https://exp.host/--/api/v2/push/send' => Http::response([
                'data' => ['status' => 'ok', 'id' => 'ticket-phase3b-001'],
            ]),
            'https://exp.host/--/api/v2/push/getReceipts' => Http::response([
                'data' => ['ticket-phase3b-001' => ['status' => 'ok']],
            ]),
        ]);

        $dispatcher = app(MobilePushDispatcher::class);
        $sent = $dispatcher->dispatch(10);
        self::assertSame(1, $sent['sent']);

        $row->refresh()->forceFill(['receipt_due_at' => now()->subSecond()])->save();
        $receipts = $dispatcher->checkReceipts(10);
        self::assertSame(1, $receipts['delivered']);
        self::assertSame('delivered', $row->fresh()->status);
    }

    public function test_device_not_registered_receipt_disables_stale_push_token(): void
    {
        $user = $this->user();
        $device = $this->device($user, 'ExponentPushToken[stale-phase3b-test]');
        $row = MobilePushOutbox::query()->create([
            'user_id' => $user->id,
            'mobile_device_id' => $device->id,
            'provider' => 'expo',
            'event' => 'order.status_changed',
            'title' => 'Test',
            'message' => 'Test',
            'data_json' => ['order_id' => 42],
            'status' => 'sent',
            'attempt_count' => 1,
            'provider_ticket_id' => 'ticket-stale-001',
            'sent_at' => now()->subMinutes(20),
            'receipt_due_at' => now()->subMinute(),
        ]);

        Http::fake([
            'https://exp.host/--/api/v2/push/getReceipts' => Http::response([
                'data' => [
                    'ticket-stale-001' => [
                        'status' => 'error',
                        'message' => 'Device is not registered',
                        'details' => ['error' => 'DeviceNotRegistered'],
                    ],
                ],
            ]),
        ]);

        $result = app(MobilePushDispatcher::class)->checkReceipts(10);
        self::assertSame(1, $result['failed']);
        self::assertSame('failed', $row->fresh()->status);
        $device->refresh();
        self::assertNull($device->push_provider);
        self::assertNull($device->push_token_hash);
        self::assertFalse($device->notifications_enabled);
    }

    private function user(): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'phase3b-push-user',
            'email' => 'phase3b@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'first_name' => 'Phase',
            'last_name' => 'Push',
            'status' => 'active',
        ]);
    }

    private function device(User $user, string $token): MobileDevice
    {
        return $user->mobileDevices()->create([
            'installation_id' => (string) fake()->uuid(),
            'platform' => 'android',
            'device_name' => 'Phase3B Android',
            'push_provider' => 'expo',
            'push_token' => $token,
            'push_token_hash' => hash('sha256', $token),
            'notifications_enabled' => true,
            'last_seen_at' => now(),
        ]);
    }
}
