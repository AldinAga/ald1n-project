<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_user_can_request_and_complete_password_reset(): void
    {
        Notification::fake();
        $user = $this->createUser();
        $user->createToken('Old Android device');

        $response = $this->post('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertRedirect()->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            static function (ResetPasswordNotification $notification) use (&$token): bool {
                $token = $notification->token;
                return strlen($token) === 80;
            }
        );

        self::assertIsString($token);
        $this->assertDatabaseHas('password_reset_tokens', [
            'user_id' => $user->id,
            'token_hash' => hash('sha256', $token),
        ]);

        $this->get('/reset-password/'.$token)->assertOk();

        $this->post('/reset-password', [
            'token' => $token,
            'password' => 'NovaLozinka123',
            'password_confirmation' => 'NovaLozinka123',
        ])->assertRedirect('/login')->assertSessionHas('status');

        self::assertTrue(Hash::check('NovaLozinka123', (string) $user->fresh()?->password_hash));
        $this->assertDatabaseCount('password_reset_tokens', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->post('/login', [
            'login' => $user->username,
            'password' => 'NovaLozinka123',
        ])->assertRedirect('/');
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_unknown_email_receives_same_generic_response(): void
    {
        Notification::fake();

        $this->post('/forgot-password', [
            'email' => 'unknown@example.test',
        ])->assertRedirect()->assertSessionHas('status');

        $this->assertDatabaseCount('password_reset_tokens', 0);
        Notification::assertNothingSent();
    }

    public function test_reset_token_cannot_be_reused(): void
    {
        Notification::fake();
        $user = $this->createUser();

        $this->post('/forgot-password', ['email' => $user->email]);
        $token = null;
        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            static function (ResetPasswordNotification $notification) use (&$token): bool {
                $token = $notification->token;
                return true;
            }
        );

        $payload = [
            'token' => $token,
            'password' => 'NovaLozinka123',
            'password_confirmation' => 'NovaLozinka123',
        ];

        $this->post('/reset-password', $payload)->assertRedirect('/login');
        $this->post('/reset-password', $payload)->assertSessionHasErrors('token');
    }

    private function createUser(): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1,
            'username' => 'reset-user',
            'email' => 'reset@example.test',
            'password_hash' => password_hash('StaraLozinka123', PASSWORD_DEFAULT),
            'first_name' => 'Reset',
            'last_name' => 'Korisnik',
            'status' => 'active',
        ]);
    }
}
