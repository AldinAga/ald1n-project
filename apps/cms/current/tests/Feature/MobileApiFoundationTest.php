<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\OperationalNotification;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class MobileApiFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_bootstrap_exposes_user_permissions_features_and_app_contract(): void
    {
        $user = $this->admin();
        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.user.username', 'mobile-admin')
            ->assertJsonPath('data.features.mobile_devices', true)
            ->assertJsonPath('data.app.backend_version', '2.2.0')
            ->assertJsonPath('data.app.api_version', 'v1')
            ->assertJsonStructure([
                'data' => [
                    'permissions',
                    'features',
                    'notification_counts' => ['unread'],
                    'notification_preferences' => ['in_app_enabled', 'email_enabled', 'push_enabled'],
                    'app' => ['android', 'ios'],
                ],
            ]);
    }

    public function test_mobile_device_can_be_registered_updated_listed_and_revoked(): void
    {
        $user = $this->admin();
        Sanctum::actingAs($user, ['*']);

        $create = $this->postJson('/api/v1/devices', [
            'installation_id' => '91a6b36c-1c1c-4db3-8d20-f88e71df1300',
            'platform' => 'android',
            'device_name' => 'Pixel test',
            'push_provider' => 'expo',
            'push_token' => 'ExponentPushToken[test-mobile-api-foundation]',
            'app_version' => '1.0.0',
            'build_number' => '1',
            'locale' => 'sr-Latn',
            'timezone' => 'Europe/Belgrade',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.platform', 'android')
            ->assertJsonPath('data.push_registered', true)
            ->assertJsonMissingPath('data.push_token');
        $deviceId = (int) $create->json('data.id');

        $this->patchJson('/api/v1/devices/'.$deviceId, [
            'notifications_enabled' => false,
            'app_version' => '1.0.1',
        ])->assertOk()
            ->assertJsonPath('data.notifications_enabled', false)
            ->assertJsonPath('data.app_version', '1.0.1');

        $this->patchJson('/api/v1/devices/'.$deviceId, [
            'push_provider' => 'fcm',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['push_token']);

        $this->getJson('/api/v1/devices')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->deleteJson('/api/v1/devices/'.$deviceId)->assertNoContent();
        $this->getJson('/api/v1/devices')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/devices?include_revoked=1')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_re_registering_installation_replaces_old_session_and_device_revoke_removes_current_session(): void
    {
        $user = $this->admin();
        $installationId = 'b3f53e4a-69cb-4f40-a4d7-2db7975a749f';

        $oldToken = $user->createToken('Pixel old', ['*'], now()->addDays(30));
        $oldTokenId = (int) $oldToken->accessToken->getKey();
        $this->withToken($oldToken->plainTextToken)
            ->postJson('/api/v1/devices', [
                'installation_id' => $installationId,
                'platform' => 'android',
                'device_name' => 'Pixel old',
            ])
            ->assertCreated();

        $newToken = $user->createToken('Pixel current', ['*'], now()->addDays(30));
        $newTokenId = (int) $newToken->accessToken->getKey();
        $registration = $this->withToken($newToken->plainTextToken)
            ->postJson('/api/v1/devices', [
                'installation_id' => $installationId,
                'platform' => 'android',
                'device_name' => 'Pixel current',
            ]);

        $registration->assertOk();
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $oldTokenId]);
        $this->assertDatabaseHas('mobile_devices', [
            'id' => (int) $registration->json('data.id'),
            'personal_access_token_id' => $newTokenId,
            'revoked_at' => null,
        ]);

        $this->withToken($newToken->plainTextToken)
            ->deleteJson('/api/v1/devices/'.(int) $registration->json('data.id'))
            ->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $newTokenId]);
        $this->assertDatabaseHas('mobile_devices', [
            'id' => (int) $registration->json('data.id'),
            'personal_access_token_id' => null,
            'notifications_enabled' => false,
        ]);
    }

    public function test_notifications_can_be_listed_and_marked_as_read(): void
    {
        $user = $this->admin();
        Sanctum::actingAs($user, ['*']);
        $user->notifyNow(new OperationalNotification([
            'event' => 'order.status_changed',
            'title' => 'Status porudžbine',
            'message' => 'Porudžbina je prihvaćena.',
            'order_id' => 42,
            'severity' => 'info',
        ]));

        $list = $this->getJson('/api/v1/notifications?unread=1');
        $list->assertOk()
            ->assertJsonPath('data.0.route', '/orders/42')
            ->assertJsonPath('data.0.read', false);
        $notificationId = (string) $list->json('data.0.id');

        $this->postJson('/api/v1/notifications/'.$notificationId.'/read')
            ->assertOk()
            ->assertJsonPath('data.read', true);

        $this->postJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('data.unread', 0);
    }

    public function test_profile_and_notification_preferences_can_be_updated(): void
    {
        $user = $this->admin();
        Sanctum::actingAs($user, ['*']);

        $this->patchJson('/api/v1/me', [
            'first_name' => 'Mobilni',
            'last_name' => 'Administrator',
            'phone' => '+38160111222',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Mobilni Administrator');

        $this->putJson('/api/v1/me/notification-preferences', [
            'push_enabled' => true,
            'email_enabled' => false,
            'order_updates' => true,
        ])->assertOk()
            ->assertJsonPath('data.push_enabled', true)
            ->assertJsonPath('data.email_enabled', false);
    }

    public function test_api_validation_errors_use_stable_envelope_and_request_id(): void
    {
        $user = $this->admin();
        Sanctum::actingAs($user, ['*']);

        $this->withHeader('X-Request-ID', 'mobile-test-request-001')
            ->postJson('/api/v1/devices', ['platform' => 'windows'])
            ->assertUnprocessable()
            ->assertHeader('X-Request-ID', 'mobile-test-request-001')
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonPath('request_id', 'mobile-test-request-001')
            ->assertJsonValidationErrors(['installation_id', 'platform']);
    }

    private function admin(): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'mobile-admin',
            'email' => 'mobile-admin@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'first_name' => 'Mobile',
            'last_name' => 'Admin',
            'status' => 'active',
        ]);
    }
}
