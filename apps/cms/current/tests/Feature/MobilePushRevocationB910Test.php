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
 * B910: real DB source test, synthetic users and fake HTTP only.
 * Changing the live recipient's global preference or eligibility must cancel
 * any still-pending push, not only the warranty-expiry category.
 */
final class MobilePushRevocationB910Test extends TestCase
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

    public function test_ordinary_pending_push_does_not_outlive_global_push_opt_out(): void
    {
        [$user, $row] = $this->queue('global-optout');
        $user->notificationPreference()->update(['push_enabled' => false]);
        Http::fake();

        $result = app(MobilePushDispatcher::class)->dispatch(5);
        self::assertSame(1, $result['processed']);
        self::assertSame(0, $result['sent']);
        self::assertSame(1, $result['failed']);
        self::assertSame('failed', $row->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_ordinary_pending_push_does_not_outlive_deactivated_account(): void
    {
        [$user, $row] = $this->queue('deactivated');
        User::query()->whereKey($user->id)->update(['status' => 'pending']);
        Http::fake();

        $result = app(MobilePushDispatcher::class)->dispatch(5);
        self::assertSame(0, $result['sent']);
        self::assertSame(1, $result['failed']);
        self::assertSame('failed', $row->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_ordinary_pending_push_does_not_outlive_retired_account(): void
    {
        [$user, $row] = $this->queue('retired');
        User::query()->whereKey($user->id)->update(['retired_at' => now()]);
        Http::fake();

        $result = app(MobilePushDispatcher::class)->dispatch(5);
        self::assertSame(0, $result['sent']);
        self::assertSame(1, $result['failed']);
        self::assertSame('failed', $row->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_missing_preference_fails_closed_for_legacy_queued_push(): void
    {
        [$user, $row] = $this->queue('preference-missing');
        $user->notificationPreference()->delete();
        Http::fake();

        $result = app(MobilePushDispatcher::class)->dispatch(5);
        self::assertSame(0, $result['sent']);
        self::assertSame(1, $result['failed']);
        self::assertSame('failed', $row->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_active_user_with_live_push_opt_in_still_receives_one_push(): void
    {
        [, $row] = $this->queue('positive');
        $this->successTransport();

        $result = app(MobilePushDispatcher::class)->dispatch(5);
        self::assertSame(1, $result['sent']);
        self::assertSame(0, $result['failed']);
        self::assertSame('sent', $row->fresh()->status);
        Http::assertSentCount(1);
    }

    public function test_retry_cannot_replay_a_push_after_global_opt_out(): void
    {
        [$user, $row] = $this->queue('retry-optout');
        Http::fake([
            'https://exp.host/--/api/v2/push/send' => Http::response(['message' => 'Temporary synthetic outage'], 503),
        ]);
        $first = app(MobilePushDispatcher::class)->dispatch(5);
        self::assertSame(1, $first['retried']);
        self::assertSame('pending', $row->fresh()->status);
        Http::assertSentCount(1);

        $user->notificationPreference()->update(['push_enabled' => false]);
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

    /** @return array{User, MobilePushOutbox} */
    private function queue(string $suffix): array
    {
        $user = User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'b910-'.$suffix,
            'email' => 'b910-'.$suffix.'@synthetic.example.test',
            'password_hash' => Hash::make('B910SyntheticOnly!'),
            'first_name' => 'Synthetic', 'last_name' => 'B910', 'status' => 'active',
        ]);
        $user->notificationPreference()->create([
            'in_app_enabled' => false, 'email_enabled' => false, 'push_enabled' => true,
            'warranty_expiry_alerts' => false, 'warranty_updates' => true,
            'order_updates' => true,
        ]);
        $token = 'ExponentPushToken[b910-'.$user->id.']';
        $device = $user->mobileDevices()->create([
            'installation_id' => (string) Str::uuid(),
            'platform' => 'android', 'device_name' => 'Synthetic B910 Android',
            'push_provider' => 'expo', 'push_token' => $token,
            'push_token_hash' => hash('sha256', $token),
            'notifications_enabled' => true, 'last_seen_at' => now(),
        ]);
        $row = MobilePushOutbox::query()->create([
            'user_id' => $user->id, 'mobile_device_id' => $device->id,
            'provider' => 'expo', 'event' => 'order.status_changed',
            'title' => 'B910 synthetic', 'message' => 'Disposable test only',
            'data_json' => ['order_id' => 42], 'status' => 'pending',
            'scheduled_for' => now()->subSecond(),
        ]);
        return [$user, $row];
    }

    private function successTransport(): void
    {
        Http::fake([
            'https://exp.host/--/api/v2/push/send' => Http::response([
                'data' => ['status' => 'ok', 'id' => 'ticket-b910-positive'],
            ]),
        ]);
    }
}
