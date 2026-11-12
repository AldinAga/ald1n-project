<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * PUT /account/password must not let a logged-in caller guess the current
 * password without limit. Only wrong guesses count (5 per user + IP / 15 min).
 */
final class AccountPasswordThrottleTest extends TestCase
{
    use RefreshDatabase;

    private const OLD = 'Synthetic-Old-Password123';
    private const NEW = 'Synthetic-New-Password456';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('services.turnstile.enabled', false);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_five_wrong_guesses_lock_the_route_even_for_the_correct_password(): void
    {
        $user = $this->login($this->person('throttle-lock'));

        for ($i = 1; $i <= 5; $i++) {
            $this->change('wrong-guess-'.$i)
                ->assertSessionHasErrors(['current_password' => 'Trenutna lozinka nije ispravna.']);
        }

        $this->change(self::OLD)->assertRedirect();
        self::assertStringContainsString('Previše neuspešnih pokušaja',
            (string) session('errors')->first('current_password'));
        self::assertTrue(Hash::check(self::OLD, (string) $user->fresh()->password_hash),
            'A locked-out request must not change the password, even with the right current password.');
        self::assertSame(1, AuditLog::query()->where('action', 'account.password_change_throttled')->count());
    }

    public function test_one_or_two_mistakes_do_not_block_a_legitimate_change_and_success_resets_the_counter(): void
    {
        $user = $this->login($this->person('throttle-legit'));

        $this->change('typo-1')->assertSessionHasErrors('current_password');
        $this->change('typo-2')->assertSessionHasErrors('current_password');
        $this->change(self::OLD)->assertSessionHasNoErrors();
        self::assertTrue(Hash::check(self::NEW, (string) $user->fresh()->password_hash));

        // Fresh counter: 2 earlier mistakes + 4 new ones would exceed 5 if the
        // success had not cleared it.
        for ($i = 1; $i <= 4; $i++) {
            $this->change('after-success-'.$i, 'Synthetic-Third-Password789')
                ->assertSessionHasErrors(['current_password' => 'Trenutna lozinka nije ispravna.']);
        }
        $this->change(self::NEW, 'Synthetic-Third-Password789')->assertSessionHasNoErrors();
        self::assertTrue(Hash::check('Synthetic-Third-Password789', (string) $user->fresh()->password_hash));
    }

    public function test_lockout_expires_after_fifteen_minutes(): void
    {
        $user = $this->login($this->person('throttle-expiry'));
        for ($i = 1; $i <= 5; $i++) {
            $this->change('wrong-'.$i)->assertSessionHasErrors('current_password');
        }
        $this->change(self::OLD)->assertRedirect();
        self::assertTrue(Hash::check(self::OLD, (string) $user->fresh()->password_hash));

        Carbon::setTestNow(Carbon::now()->addMinutes(16));
        $this->change(self::OLD)->assertSessionHasNoErrors();
        self::assertTrue(Hash::check(self::NEW, (string) $user->fresh()->password_hash),
            'After the lockout window the correct password works again.');
    }

    public function test_lockout_of_one_user_does_not_block_another_user(): void
    {
        $this->login($this->person('throttle-victim'));
        for ($i = 1; $i <= 5; $i++) {
            $this->change('wrong-'.$i)->assertSessionHasErrors('current_password');
        }
        $this->post('/logout')->assertRedirect();

        $other = $this->login($this->person('throttle-bystander'));
        $this->change(self::OLD)->assertSessionHasNoErrors();
        self::assertTrue(Hash::check(self::NEW, (string) $other->fresh()->password_hash));
    }

    private function change(string $current, string $new = self::NEW): \Illuminate\Testing\TestResponse
    {
        return $this->put('/account/password', [
            'current_password' => $current,
            'password' => $new,
            'password_confirmation' => $new,
        ]);
    }

    private function person(string $username): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'username' => $username,
            'email' => $username.'@example.test',
            'first_name' => 'Throttle Synthetic',
            'password_hash' => Hash::make(self::OLD),
            'status' => 'active',
        ]);
    }

    private function login(User $user): User
    {
        $this->post('/login', ['login' => $user->email, 'password' => self::OLD])->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        return $user;
    }
}
