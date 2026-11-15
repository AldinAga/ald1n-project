<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\CustomerPortalInvitationNotification;
use App\Notifications\ResetPasswordNotification;
use App\Services\CustomerActivationService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * B883: a failed (or neutral) POST in login, forgot-password, reset-password
 * and account activation must redirect to the form's own GET route on the
 * APP_URL origin. Laravel's default back() and FormRequest redirect use the
 * Referer header, so a crafted Referer could send the user to another origin.
 *
 * Every case is posted three ways: with a hostile Referer, with a forged Host
 * (absolute URI, otherwise the test client drops the Host header; the control
 * assertion proves the forged host really became the request root), and with
 * both. Without a Referer and without a previous GET, Laravel falls back to
 * "/", so the Host-only case fails on the path, not on the origin.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class AuthLocalRedirectB883Test extends TestCase
{
    use RefreshDatabase;

    private const ORIGIN = 'https://cms.example.test';
    private const EVIL_REFERER = 'https://evil.example/x';
    private const EVIL_HOST = 'http://evil.example';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('app.url', self::ORIGIN);
        config()->set('services.turnstile.enabled', false);
        // The limiter counters outlive a test; only the 429 test wants them.
        $this->withoutMiddleware(ThrottleRequests::class);
        Notification::fake();
    }

    /** @return array<string,array{0:string,1:array<string,string>,2:string,3:string,4:string,5:bool}> */
    public static function failingFlows(): array
    {
        $token = str_repeat('A', 80);
        $strong = 'ValidSynthetic123!';
        $flows = [
            'login: validation (password missing)' => ['/login', ['login' => 'someone@example.test'], '/login', 'errors:password', false],
            'login: wrong credentials' => ['/login', ['login' => 'ghost@example.test', 'password' => 'Wrong-Pass-123!'], '/login', 'errors:login', false],
            'login: turnstile rejected' => ['/login', ['login' => 'ghost@example.test', 'password' => 'Wrong-Pass-123!'], '/login', 'errors:turnstile', true],
            'forgot: validation (bad e-mail)' => ['/forgot-password', ['email' => 'not-an-email'], '/forgot-password', 'errors:email', false],
            'forgot: neutral success' => ['/forgot-password', ['email' => 'absent@example.test'], '/forgot-password', 'status', false],
            'forgot: turnstile rejected' => ['/forgot-password', ['email' => 'absent@example.test'], '/forgot-password', 'errors:turnstile', true],
            'reset: validation (token shape)' => ['/reset-password', ['token' => 'bad', 'password' => $strong, 'password_confirmation' => $strong], '/forgot-password', 'errors:token', false],
            'reset: validation (weak password)' => ['/reset-password', ['token' => $token, 'password' => 'short', 'password_confirmation' => 'short'], '/reset-password/'.$token, 'errors:password', false],
            'reset: unknown token' => ['/reset-password', ['token' => $token, 'password' => $strong, 'password_confirmation' => $strong], '/reset-password/'.$token, 'errors:token', false],
            'activation: validation (token shape)' => ['/activate-account', ['token' => 'bad', 'password' => $strong, 'password_confirmation' => $strong], '/login', 'errors:token', false],
            'activation: validation (weak password)' => ['/activate-account', ['token' => $token, 'password' => 'weak', 'password_confirmation' => 'weak'], '/activate-account/'.$token, 'errors:password', false],
            'activation: unknown token' => ['/activate-account', ['token' => $token, 'password' => $strong, 'password_confirmation' => $strong], '/activate-account/'.$token, 'errors:token', false],
            'activation: turnstile rejected' => ['/activate-account', ['token' => $token, 'password' => $strong, 'password_confirmation' => $strong], '/activate-account/'.$token, 'errors:turnstile', true],
        ];

        $cases = [];
        foreach ($flows as $name => [$path, $payload, $expected, $flash, $turnstile]) {
            foreach (['referer', 'host', 'referer+host'] as $variant) {
                $cases[$name.' / '.$variant] = [$path, $payload, $expected, $flash, $variant, $turnstile];
            }
        }

        return $cases;
    }

    /** @return array<string,array{0:string,1:array<string,string>,2:string,3:string,4:string,5:bool}> */
    public static function unpinnedFlows(): array
    {
        return array_filter(
            self::failingFlows(),
            static fn (array $case): bool => $case[4] === 'referer+host',
        );
    }

    /** @param array<string,string> $payload */
    #[DataProvider('failingFlows')]
    public function test_failed_post_goes_to_the_forms_own_route_on_the_app_url_origin(
        string $path,
        array $payload,
        string $expectedPath,
        string $flash,
        string $variant,
        bool $turnstile,
    ): void {
        config()->set('services.turnstile.enabled', $turnstile);

        $response = $this->hostilePost($path, $payload, $variant);

        $response->assertStatus(302);
        self::assertSame(self::ORIGIN.$expectedPath, (string) $response->headers->get('Location'));
        if (str_starts_with($flash, 'errors:')) {
            $response->assertSessionHasErrors(substr($flash, 7));
        } else {
            $response->assertSessionHas($flash);
        }
        $this->assertNoSecretsFlashed();
    }

    /** @param array<string,string> $payload */
    #[DataProvider('unpinnedFlows')]
    public function test_without_a_usable_app_url_the_redirect_is_relative_and_never_follows_referer_or_host(
        string $path,
        array $payload,
        string $expectedPath,
        string $flash,
        string $variant,
        bool $turnstile,
    ): void {
        config()->set('app.url', '');
        config()->set('services.turnstile.enabled', $turnstile);

        $response = $this->hostilePost($path, $payload, $variant);

        $response->assertStatus(302);
        self::assertSame($expectedPath, (string) $response->headers->get('Location'));
    }

    public function test_valid_login_is_unchanged_under_a_hostile_referer_and_host(): void
    {
        $user = $this->user('b883-login');

        $response = $this->hostilePost('/login', [
            'login' => $user->username,
            'password' => 'Secret123!Aa',
        ], 'referer+host');

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_valid_reset_is_unchanged_under_a_hostile_referer_and_host(): void
    {
        $user = $this->user('b883-reset');
        $this->post('/forgot-password', ['email' => $user->email]);
        $token = $this->mailedToken($user, ResetPasswordNotification::class);

        $response = $this->hostilePost('/reset-password', [
            'token' => $token,
            'password' => 'NovaLozinka123',
            'password_confirmation' => 'NovaLozinka123',
        ], 'referer+host');

        self::assertSame(self::ORIGIN.'/login', (string) $response->headers->get('Location'));
        $response->assertSessionHas('status');
        self::assertTrue(Hash::check('NovaLozinka123', (string) $user->fresh()?->password_hash));
    }

    public function test_valid_activation_is_unchanged_under_a_hostile_referer_and_host(): void
    {
        $user = $this->user('b883-activate');
        app(CustomerActivationService::class)->invite($user);
        $token = $this->mailedToken($user, CustomerPortalInvitationNotification::class);

        $response = $this->hostilePost('/activate-account', [
            'token' => $token,
            'password' => 'NovaLozinka123',
            'password_confirmation' => 'NovaLozinka123',
        ], 'referer+host');

        self::assertSame(self::ORIGIN.'/login', (string) $response->headers->get('Location'));
        $response->assertSessionHas('status');
        self::assertTrue(Hash::check('NovaLozinka123', (string) $user->fresh()?->password_hash));
    }

    public function test_json_validation_keeps_the_422_and_has_no_redirect(): void
    {
        $response = $this->postJson('/login', ['login' => 'someone@example.test'], ['Referer' => self::EVIL_REFERER]);

        $response->assertUnprocessable()->assertJsonValidationErrors('password');
        self::assertFalse($response->headers->has('Location'));

        $response = $this->postJson('/activate-account', ['token' => 'bad'], ['Referer' => self::EVIL_REFERER]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['token', 'password']);
        self::assertFalse($response->headers->has('Location'));
    }

    /**
     * Pins the paths that never redirect: the login limiter answers 429
     * (rendered page) and Laravel maps a CSRF mismatch to a 419 HttpException;
     * neither consults the Referer. The 419 mapping is a framework fact
     * (Foundation Handler::prepareException) and is not reproducible in a
     * test, because Laravel skips CSRF verification when running unit tests.
     */
    public function test_login_throttle_answers_429_without_a_redirect(): void
    {
        $this->withMiddleware(ThrottleRequests::class);
        $login = 'throttle-'.bin2hex(random_bytes(4)).'@example.test';
        $last = null;
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $last = $this->post('/login', ['login' => $login, 'password' => 'Wrong-Pass-123!'], [
                'Referer' => self::EVIL_REFERER,
            ]);
        }

        self::assertNotNull($last);
        $last->assertStatus(429);
        self::assertFalse($last->headers->has('Location'));
    }

    /** @param array<string,string> $payload */
    private function hostilePost(string $path, array $payload, string $variant): TestResponse
    {
        $headers = [];
        $uri = $path;
        if (str_contains($variant, 'referer')) {
            $headers['Referer'] = self::EVIL_REFERER;
        }
        if (str_contains($variant, 'host')) {
            $uri = self::EVIL_HOST.$path;
            $headers['Host'] = 'evil.example';
            $headers['X-Forwarded-Host'] = 'evil-forwarded.example';
        }

        $response = $this->post($uri, $payload, $headers);

        if (str_contains($variant, 'host')) {
            // Control: the forged host really became the request root.
            self::assertSame(self::EVIL_HOST, request()->root());
        }

        return $response;
    }

    private function assertNoSecretsFlashed(): void
    {
        foreach (['password', 'password_confirmation', 'token', 'cf-turnstile-response'] as $key) {
            self::assertNull(session()->getOldInput($key), $key.' must not be flashed as old input.');
        }
    }

    private function mailedToken(User $user, string $notification): string
    {
        $token = '';
        Notification::assertSentTo($user, $notification, static function ($sent) use (&$token): bool {
            $token = $sent->token;

            return true;
        });
        self::assertSame(80, strlen($token));

        return $token;
    }

    private function user(string $username): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'user_group_id' => 1,
            'username' => $username,
            'email' => $username.'@example.test',
            'first_name' => 'B883',
            'last_name' => 'Synthetic',
            'password_hash' => Hash::make('Secret123!Aa'),
            'status' => 'active',
        ]);
    }
}
