<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class WarrantyExpiryPreferenceApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_bootstrap_defaults_off_and_explicit_toggle_round_trips(): void
    {
        $buyer = $this->buyer('warranty-http-roundtrip');
        Sanctum::actingAs($buyer, ['*']);

        $this->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.notification_preferences.warranty_updates', true)
            ->assertJsonPath('data.notification_preferences.warranty_expiry_alerts', false);

        $this->putJson('/api/v1/me/notification-preferences', [
            'warranty_expiry_alerts' => true,
        ])->assertOk()
            ->assertJsonPath('data.warranty_expiry_alerts', true)
            ->assertJsonPath('data.warranty_updates', true)
            ->assertJsonPath('data.push_enabled', false);

        $this->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.notification_preferences.warranty_expiry_alerts', true);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $buyer->id,
            'warranty_expiry_alerts' => 1,
            'warranty_updates' => 1,
        ]);

        $this->putJson('/api/v1/me/notification-preferences', [
            'warranty_expiry_alerts' => false,
        ])->assertOk()->assertJsonPath('data.warranty_expiry_alerts', false);

        $this->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.notification_preferences.warranty_expiry_alerts', false);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $buyer->id,
            'warranty_expiry_alerts' => 0,
            'warranty_updates' => 1,
        ]);
    }

    public function test_opt_in_stays_scoped_to_authenticated_user_and_cannot_be_reassigned(): void
    {
        $buyer = $this->buyer('warranty-http-buyer');
        $other = $this->buyer('warranty-http-other');
        Sanctum::actingAs($buyer, ['*']);

        $this->putJson('/api/v1/me/notification-preferences', [
            'warranty_expiry_alerts' => true,
            'user_id' => $other->id,
        ])->assertOk()->assertJsonPath('data.warranty_expiry_alerts', true);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $buyer->id,
            'warranty_expiry_alerts' => 1,
        ]);
        $this->assertDatabaseMissing('notification_preferences', [
            'user_id' => $other->id,
            'warranty_expiry_alerts' => 1,
        ]);

        Sanctum::actingAs($other, ['*']);
        $this->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.notification_preferences.warranty_expiry_alerts', false);

        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $other->id,
            'warranty_expiry_alerts' => 0,
        ]);
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $buyer->id,
            'warranty_expiry_alerts' => 1,
        ]);
    }

    public function test_non_boolean_or_null_opt_in_cannot_change_prior_consent(): void
    {
        $buyer = $this->buyer('warranty-http-invalid');
        Sanctum::actingAs($buyer, ['*']);

        $this->putJson('/api/v1/me/notification-preferences', [
            'warranty_expiry_alerts' => true,
        ])->assertOk();

        foreach (['not-a-boolean', null] as $invalid) {
            $this->putJson('/api/v1/me/notification-preferences', [
                'warranty_expiry_alerts' => $invalid,
            ])->assertUnprocessable()
                ->assertJsonValidationErrors(['warranty_expiry_alerts']);

            $this->assertDatabaseHas('notification_preferences', [
                'user_id' => $buyer->id,
                'warranty_expiry_alerts' => 1,
            ]);
        }

        $this->getJson('/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('data.notification_preferences.warranty_expiry_alerts', true);
    }

    public function test_unauthenticated_client_cannot_enable_expiry_notifications(): void
    {
        $this->putJson('/api/v1/me/notification-preferences', [
            'warranty_expiry_alerts' => true,
        ])->assertUnauthorized();
        $this->assertDatabaseCount('notification_preferences', 0);
    }

    private function buyer(string $username): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1,
            'username' => $username,
            'email' => $username.'@example.test',
            'first_name' => 'Kupac',
            'password_hash' => Hash::make('IsolatedOnly123!'),
            'status' => 'active',
        ]);
    }
}
