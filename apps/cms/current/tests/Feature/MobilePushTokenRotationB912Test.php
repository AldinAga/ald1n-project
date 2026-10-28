<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\MobileDevice;
use App\Models\MobilePushOutbox;
use App\Models\Role;
use App\Models\User;
use App\Services\ExpoPushTransport;
use App\Services\MobilePushDispatcher;
use App\Services\MobilePushOutboxService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * B912: late Expo receipts cannot erase a newer token of the same device owner.
 * All users and devices are disposable; HTTP calls are intercepted locally.
 */
final class MobilePushTokenRotationB912Test extends TestCase
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

    public function test_each_queued_device_records_its_own_token_fingerprint(): void
    {
        $user = $this->user('each');
        $deviceA = $this->device($user, 'one');
        $deviceB = $this->device($user, 'two');
        self::assertSame(2, $this->enqueue($user));
        $rows = MobilePushOutbox::query()->orderBy('id')->get();
        self::assertCount(2, $rows);
        $snapshots = [];
        foreach ($rows as $row) {
            $snapshots[$row->mobile_device_id] = $row->data_json[ExpoPushTransport::DEVICE_TOKEN_HASH_KEY] ?? null;
        }
        self::assertSame($deviceA->push_token_hash, $snapshots[$deviceA->id]);
        self::assertSame($deviceB->push_token_hash, $snapshots[$deviceB->id]);
        self::assertNotSame($snapshots[$deviceA->id], $snapshots[$deviceB->id]);
    }

    public function test_pending_push_cannot_follow_same_owner_token_rotation(): void
    {
        $user = $this->user('pending-rotation');
        $device = $this->device($user, 'old');
        $row = $this->queueOne($user);
        $this->rotate($device, 'new');
        Http::fake();
        $outcome = app(MobilePushDispatcher::class)->dispatch(10);
        self::assertSame(1, $outcome['processed']);
        self::assertSame(0, $outcome['sent']);
        self::assertSame(1, $outcome['failed']);
        self::assertSame('failed', $row->fresh()->status);
        self::assertTrue($device->fresh()->notifications_enabled);
        self::assertNotNull($device->fresh()->push_token_hash);
        Http::assertNothingSent();
    }

    public function test_valid_current_token_is_sent_without_internal_hash_in_expo_payload(): void
    {
        $user = $this->user('valid');
        $device = $this->device($user, 'valid');
        $row = $this->queueOne($user);
        $this->fakeSuccess();
        $outcome = app(MobilePushDispatcher::class)->dispatch(10);
        self::assertSame(1, $outcome['sent']);
        self::assertSame('sent', $row->fresh()->status);
        $token = $device->push_token;
        Http::assertSent(function ($request) use ($token): bool {
            $payload = $request->data();
            return ($payload['to'] ?? null) === $token
                && !array_key_exists(ExpoPushTransport::DEVICE_TOKEN_HASH_KEY, $payload['data'] ?? []);
        });
        Http::assertSentCount(1);
    }

    public function test_late_bad_receipt_for_old_token_keeps_new_same_owner_token(): void
    {
        $user = $this->user('late-rotation');
        $device = $this->device($user, 'old');
        $row = $this->queueOne($user);
        $this->fakeSuccess();
        self::assertSame(1, app(MobilePushDispatcher::class)->dispatch(10)['sent']);
        $row->forceFill(['receipt_due_at' => now()->subSecond()])->save();
        $this->rotate($device, 'new');
        $newHash = $device->fresh()->push_token_hash;
        $this->fakeBadReceipt();
        $result = app(MobilePushDispatcher::class)->checkReceipts(10);
        self::assertSame(1, $result['failed']);
        self::assertSame('failed', $row->fresh()->status);
        self::assertSame($newHash, $device->fresh()->push_token_hash);
        self::assertSame('expo', $device->fresh()->push_provider);
        self::assertTrue($device->fresh()->notifications_enabled);
    }

    public function test_bad_receipt_disables_only_exact_still_registered_token(): void
    {
        $user = $this->user('same-token');
        $device = $this->device($user, 'same');
        $row = $this->queueOne($user);
        $this->fakeSuccess();
        self::assertSame(1, app(MobilePushDispatcher::class)->dispatch(10)['sent']);
        $row->forceFill(['receipt_due_at' => now()->subSecond()])->save();
        $this->fakeBadReceipt();
        self::assertSame(1, app(MobilePushDispatcher::class)->checkReceipts(10)['failed']);
        self::assertSame('failed', $row->fresh()->status);
        self::assertNull($device->fresh()->push_token_hash);
        self::assertNull($device->fresh()->push_provider);
        self::assertFalse($device->fresh()->notifications_enabled);
    }

    public function test_malformed_snapshot_is_rejected_without_expo_request_or_token_revocation(): void
    {
        $user = $this->user('malformed');
        $device = $this->device($user, 'stable');
        $row = $this->queueOne($user);
        $data = $row->data_json;
        $data[ExpoPushTransport::DEVICE_TOKEN_HASH_KEY] = 'invalid';
        $row->forceFill(['data_json' => $data])->save();
        Http::fake();
        $outcome = app(MobilePushDispatcher::class)->dispatch(10);
        self::assertSame(0, $outcome['sent']);
        self::assertSame(1, $outcome['failed']);
        self::assertSame('failed', $row->fresh()->status);
        self::assertNotNull($device->fresh()->push_token_hash);
        Http::assertNothingSent();
    }

    public function test_token_rotation_between_retry_attempts_blocks_second_provider_send(): void
    {
        $user = $this->user('retry-rotation');
        $device = $this->device($user, 'old');
        $row = $this->queueOne($user);
        Http::fake(['https://exp.host/--/api/v2/push/send' => Http::response(['error' => 'synthetic'], 503)]);
        self::assertSame(1, app(MobilePushDispatcher::class)->dispatch(10)['retried']);
        self::assertSame('pending', $row->fresh()->status);
        Http::assertSentCount(1);
        $this->rotate($device, 'new');
        $row->forceFill(['scheduled_for' => now()->subSecond()])->save();
        $second = app(MobilePushDispatcher::class)->dispatch(10);
        self::assertSame(0, $second['sent']);
        self::assertSame(0, $second['retried']);
        self::assertSame(1, $second['failed']);
        self::assertSame('failed', $row->fresh()->status);
        Http::assertSentCount(1);
    }

    private function user(string $suffix): User
    {
        $user = User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'b912-'.$suffix,
            'email' => 'b912-'.$suffix.'@synthetic.example.test',
            'password_hash' => Hash::make('B912SyntheticOnly!'),
            'first_name' => 'Synthetic',
            'last_name' => 'B912',
            'status' => 'active',
        ]);
        $user->notificationPreference()->create([
            'in_app_enabled' => false, 'email_enabled' => false, 'push_enabled' => true,
            'order_updates' => true, 'payment_alerts' => true,
            'warranty_updates' => true, 'warranty_expiry_alerts' => false,
        ]);
        return $user;
    }

    private function device(User $user, string $suffix): MobileDevice
    {
        $token = 'ExponentPushToken[b912-'.$user->id.'-'.$suffix.']';
        return $user->mobileDevices()->create([
            'installation_id' => (string) Str::uuid(),
            'platform' => 'android', 'device_name' => 'Synthetic B912',
            'push_provider' => 'expo', 'push_token' => $token,
            'push_token_hash' => hash('sha256', $token),
            'notifications_enabled' => true, 'last_seen_at' => now(),
        ]);
    }

    private function enqueue(User $user): int
    {
        return app(MobilePushOutboxService::class)->enqueue($user, [
            'event' => 'order.status_changed',
            'title' => 'Synthetic B912', 'message' => 'Disposable token-rotation proof',
            'order_id' => 37,
        ]);
    }

    private function queueOne(User $user): MobilePushOutbox
    {
        self::assertSame(1, $this->enqueue($user));
        return MobilePushOutbox::query()->sole();
    }

    private function rotate(MobileDevice $device, string $suffix): void
    {
        $token = 'ExponentPushToken[b912-rotated-'.$device->id.'-'.$suffix.']';
        $device->forceFill([
            'push_token' => $token, 'push_token_hash' => hash('sha256', $token),
            'push_provider' => 'expo', 'notifications_enabled' => true,
        ])->save();
    }

    private function fakeSuccess(): void
    {
        Http::fake(['https://exp.host/--/api/v2/push/send' => Http::response([
            'data' => ['status' => 'ok', 'id' => 'ticket-b912-synthetic'],
        ])]);
    }

    private function fakeBadReceipt(): void
    {
        Http::fake(['https://exp.host/--/api/v2/push/getReceipts' => Http::response([
            'data' => ['ticket-b912-synthetic' => [
                'status' => 'error',
                'message' => 'Token is no longer registered',
                'details' => ['error' => 'DeviceNotRegistered'],
            ]],
        ])]);
    }
}
