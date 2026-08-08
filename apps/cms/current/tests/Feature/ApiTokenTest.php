<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ApiTokenTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_mobile_client_can_issue_use_and_revoke_token(): void
    {
        $user = User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1,
            'username' => 'mobile-user',
            'email' => 'mobile@example.test',
            'password_hash' => password_hash('Secret123!', PASSWORD_DEFAULT),
            'status' => 'active',
        ]);

        $login = $this->postJson('/api/v1/auth/token', [
            'login' => $user->email,
            'password' => 'Secret123!',
            'device_name' => 'Android test',
        ]);

        $login->assertCreated()->assertJsonPath('token_type', 'Bearer');
        $token = (string) $login->json('token');
        self::assertNotSame('', $token);

        $headers = ['Authorization' => 'Bearer '.$token];
        $this->withHeaders($headers)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.username', 'mobile-user');
        $this->withHeaders($headers)
            ->deleteJson('/api/v1/auth/token')
            ->assertNoContent();

        // Potvrdi da je token zaista uklonjen iz baze.
        $this->assertDatabaseCount('personal_access_tokens', 0);

        // PHPUnit izvršava više HTTP zahteva kroz istu aplikacionu
        // instancu, pa moramo odbaciti prethodno razrešeni Sanctum guard.
        $this->app['auth']->forgetGuards();

        $this->withHeaders($headers)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }
}
