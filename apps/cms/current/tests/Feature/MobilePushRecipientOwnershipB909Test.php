<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MobileDevice;
use App\Models\MobilePushOutbox;
use App\Models\Role;
use App\Models\User;
use App\Services\MobilePushDispatcher;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * B909: queued notifications and delayed receipts must remain bound to the
 * original device owner. All data is synthetic, all Expo HTTP is faked.
 */
final class MobilePushRecipientOwnershipB909Test extends TestCase
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
        Http::preventStrayRequests();
    }

    public function test_pending_push_must_not_follow_device_reassigned_to_another_user(): void
    {
        $original = $this->user('original');
        $newOwner = $this->user('new-owner');
        $device = $this->device($original);
        $row = $this->outbox($original, $device);

        MobileDevice::query()->whereKey($device->id)->update(['user_id' => $newOwner->id]);
        Http::fake();
        $outcome = app(MobilePushDispatcher::class)->dispatch(10);

        self::assertSame(1, $outcome['processed']);
        self::assertSame(0, $outcome['sent']);
        self::assertSame(0, $outcome['retried']);
        self::assertSame(1, $outcome['failed']);
        self::assertSame('failed', $row->fresh()->status);
        Http::assertNothingSent();
        self::assertSame($newOwner->id, (int) $device->fresh()->user_id);
        self::assertSame('expo', $device->fresh()->push_provider);
        self::assertTrue($device->fresh()->notifications_enabled);
        self::assertNotNull($device->fresh()->push_token_hash);
    }

    public function test_same_owner_and_active_device_are_still_sent_once(): void
    {
        $user = $this->user('positive');
        $device = $this->device($user);
        $row = $this->outbox($user, $device);
        Http::fake([
            'https://exp.host/--/api/v2/push/send' => Http::response([
                'data' => ['status' => 'ok', 'id' => 'ticket-b909-positive'],
            ]),
        ]);

        $outcome = app(MobilePushDispatcher::class)->dispatch(10);
        self::assertSame(1, $outcome['sent']);
        self::assertSame(0, $outcome['failed']);
        self::assertSame('sent', $row->fresh()->status);
        self::assertSame('ticket-b909-positive', $row->fresh()->provider_ticket_id);
        Http::assertSentCount(1);
    }

    public function test_revoked_device_does_not_send_queued_push(): void
    {
        $user = $this->user('revoked');
        $device = $this->device($user);
        $row = $this->outbox($user, $device);
        MobileDevice::query()->whereKey($device->id)->update(['revoked_at' => now()]);
        Http::fake();

        $outcome = app(MobilePushDispatcher::class)->dispatch(10);
        self::assertSame(0, $outcome['sent']);
        self::assertSame(1, $outcome['failed']);
        self::assertSame('failed', $row->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_device_notification_opt_out_does_not_send_queued_push(): void
    {
        $user = $this->user('device-optout');
        $device = $this->device($user);
        $row = $this->outbox($user, $device);
        MobileDevice::query()->whereKey($device->id)->update(['notifications_enabled' => false]);
        Http::fake();

        $outcome = app(MobilePushDispatcher::class)->dispatch(10);
        self::assertSame(0, $outcome['sent']);
        self::assertSame(1, $outcome['failed']);
        self::assertSame('failed', $row->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_stale_device_not_registered_receipt_cannot_disable_new_owner_token(): void
    {
        $original = $this->user('receipt-original');
        $newOwner = $this->user('receipt-new');
        $device = $this->device($original);
        $row = $this->outbox($original, $device, 'sent');
        MobileDevice::query()->whereKey($device->id)->update(['user_id' => $newOwner->id]);
        Http::fake([
            'https://exp.host/--/api/v2/push/getReceipts' => Http::response([
                'data' => ['ticket-b909-old' => [
                    'status' => 'error',
                    'message' => 'Device is not registered',
                    'details' => ['error' => 'DeviceNotRegistered'],
                ]],
            ]),
        ]);

        $outcome = app(MobilePushDispatcher::class)->checkReceipts(10);
        self::assertSame(1, $outcome['checked']);
        self::assertSame(1, $outcome['failed']);
        self::assertSame('failed', $row->fresh()->status);
        self::assertSame('expo', $device->fresh()->push_provider);
        self::assertTrue($device->fresh()->notifications_enabled);
        self::assertNotNull($device->fresh()->push_token_hash);
        Http::assertSentCount(1);
    }

    public function test_device_not_registered_receipt_may_disable_same_owner_token(): void
    {
        $original = $this->user('receipt-same-owner');
        $device = $this->device($original);
        $row = $this->outbox($original, $device, 'sent');
        Http::fake([
            'https://exp.host/--/api/v2/push/getReceipts' => Http::response([
                'data' => ['ticket-b909-old' => [
                    'status' => 'error',
                    'message' => 'Device is not registered',
                    'details' => ['error' => 'DeviceNotRegistered'],
                ]],
            ]),
        ]);

        $outcome = app(MobilePushDispatcher::class)->checkReceipts(10);
        self::assertSame(1, $outcome['failed']);
        self::assertSame('failed', $row->fresh()->status);
        self::assertNull($device->fresh()->push_provider);
        self::assertFalse($device->fresh()->notifications_enabled);
        self::assertNull($device->fresh()->push_token_hash);
        Http::assertSentCount(1);
    }

    private function user(string $suffix): User
    {
        $user = User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'b909-'.$suffix,
            'email' => 'b909-'.$suffix.'@synthetic.example.test',
            'password_hash' => Hash::make('B909SyntheticOnly!'),
            'first_name' => 'Synthetic',
            'last_name' => 'B909',
            'status' => 'active',
        ]);
        // New B910 live push preference guard must preserve B909 controls.
        $user->notificationPreference()->create([
            'in_app_enabled' => false, 'email_enabled' => false, 'push_enabled' => true,
            'warranty_expiry_alerts' => false, 'warranty_updates' => true,
            'order_updates' => true,
        ]);
        return $user;
    }

    private function device(User $user): MobileDevice
    {
        $token = 'ExponentPushToken[b909-'.$user->id.']';
        return $user->mobileDevices()->create([
            'installation_id' => (string) Str::uuid(),
            'platform' => 'android',
            'device_name' => 'Synthetic B909 Android',
            'push_provider' => 'expo',
            'push_token' => $token,
            'push_token_hash' => hash('sha256', $token),
            'notifications_enabled' => true,
            'last_seen_at' => now(),
        ]);
    }

    private function outbox(User $user, MobileDevice $device, string $status = 'pending'): MobilePushOutbox
    {
        return MobilePushOutbox::query()->create([
            'user_id' => $user->id,
            'mobile_device_id' => $device->id,
            'provider' => 'expo',
            'event' => 'order.status_changed',
            'title' => 'B909 synthetic',
            'message' => 'No real recipient or event',
            'data_json' => ['order_id' => 42],
            'status' => $status,
            'attempt_count' => $status === 'sent' ? 1 : 0,
            'provider_ticket_id' => $status === 'sent' ? 'ticket-b909-old' : null,
            'scheduled_for' => now()->subSecond(),
            'sent_at' => $status === 'sent' ? now()->subMinutes(20) : null,
            'receipt_due_at' => $status === 'sent' ? now()->subMinute() : null,
        ]);
    }
}
