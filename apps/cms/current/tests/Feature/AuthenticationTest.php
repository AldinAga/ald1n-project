<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
    }

    public function test_active_user_can_log_in_and_log_out(): void
    {
        $user = $this->createUser('active');

        $this->post('/login', [
            'login' => $user->username,
            'password' => 'Secret123!',
            'remember' => true,
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        self::assertNotNull($user->fresh()?->last_login_at);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }



    public function test_imported_php_password_hash_can_log_in(): void
    {
        $user = User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1,
            'username' => 'legacy-login',
            'email' => 'legacy-login@example.test',
            'password_hash' => password_hash('LegacyLozinka123', PASSWORD_DEFAULT),
            'status' => 'active',
        ]);

        $this->post('/login', [
            'login' => $user->username,
            'password' => 'LegacyLozinka123',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }


    public function test_login_is_case_insensitive_for_username_and_email(): void
    {
        $user = $this->createUser('active');

        $this->post('/login', [
            'login' => mb_strtoupper($user->username),
            'password' => 'Secret123!',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->post('/logout')->assertRedirect('/login');

        $this->post('/login', [
            'login' => mb_strtoupper($user->email),
            'password' => 'Secret123!',
        ])->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_page_does_not_expose_implementation_details(): void
    {
        $response = $this->get('/login')->assertOk();

        $response->assertDontSee('Laravel 13');
        $response->assertDontSee('API v1');
        $response->assertDontSee('Sanctum');
        $response->assertDontSee('PHP 8.4');
        $response->assertDontSee('Ovo je odvojeni Laravel staging sistem');
        $response->assertDontSee('Stari CMS ostaje netaknut');
    }

    public function test_cli_password_reset_finds_username_case_insensitively(): void
    {
        $user = $this->createUser('active');

        $this->artisan('app:reset-user-password', ['login' => mb_strtoupper($user->username)])
            ->expectsQuestion('Unesite novu lozinku (najmanje 12 karaktera)', 'NovaLozinka123')
            ->expectsQuestion('Ponovite novu lozinku', 'NovaLozinka123')
            ->assertExitCode(0);

        self::assertTrue(Hash::check('NovaLozinka123', (string) $user->fresh()?->password_hash));
    }

    public function test_blocked_user_cannot_log_in(): void
    {
        $user = $this->createUser('blocked');

        $this->post('/login', [
            'login' => $user->email,
            'password' => 'Secret123!',
        ])->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    private function createUser(string $status): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1,
            'username' => 'test-'.$status,
            'email' => $status.'@example.test',
            'password_hash' => Hash::make('Secret123!'),
            'first_name' => 'Test',
            'last_name' => 'Korisnik',
            'status' => $status,
        ]);
    }
}
