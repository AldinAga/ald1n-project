<?php
declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * B817: invalid sensitive POST responses must protect tokens and credentials
 * even on validation failures. Disposable Laravel/MariaDB only; no real mail.
 */
final class SensitiveAuthPostBoundaryB817Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('services.turnstile.enabled', false);
        config()->set('public_storefront.enabled', true);
    }

    public static function unsafePostInputs(): array
    {
        return [
            'login-invalid' => ['/login',['login'=>'synthetic@example.test','password'=>'wrong']],
            'password-link-invalid' => ['/forgot-password',['email'=>'not-an-email']],
            'password-reset-invalid' => ['/reset-password',['token'=>'invalid']],
            'account-activation-invalid' => ['/activate-account',['token'=>'invalid']],
            'registered-buyer-invalid' => ['/registracija',['email'=>'invalid']],
        ];
    }

    #[DataProvider('unsafePostInputs')]
    public function test_sensitive_invalid_posts_never_cache_or_refer_secret(
        string $path, array $payload
    ): void {
        $response=$this->post($path,$payload);
        self::assertContains($response->status(),[302,422],
            'Invalid sensitive POST must be rejected, not processed');
        self::assertSame('no-referrer',$response->headers->get('Referrer-Policy'));
        self::assertStringContainsString('no-store',
            (string)$response->headers->get('Cache-Control'));
        self::assertStringContainsString('private',
            (string)$response->headers->get('Cache-Control'));
        self::assertSame('no-cache',$response->headers->get('Pragma'));
        self::assertStringContainsString('noindex',
            (string)$response->headers->get('X-Robots-Tag'));
        self::assertSame('nosniff',$response->headers->get('X-Content-Type-Options'));
    }

    public function test_invalid_reset_and_activation_do_not_create_accounts(): void
    {
        $this->post('/reset-password',[
            'token'=>'synthetic-invalid-token',
            'password'=>'B817-Synthetic-Only123!',
            'password_confirmation'=>'B817-Synthetic-Only123!',
        ])->assertRedirect();

        $this->post('/activate-account',[
            'token'=>'synthetic-invalid-token',
            'password'=>'B817-Synthetic-Only123!',
            'password_confirmation'=>'B817-Synthetic-Only123!',
        ])->assertRedirect();

        self::assertDatabaseCount('users',0);
        self::assertDatabaseCount('orders',0);
        self::assertDatabaseCount('user_activation_tokens',0);
    }
}
