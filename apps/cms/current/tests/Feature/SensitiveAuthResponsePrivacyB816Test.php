<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * B816 SEC07/SEC22: browser pages bearing activation/password/OAuth
 * credentials must never persist or referrer-share a one-time secret.
 * All requests run in synthetic isolated CI; no email or OAuth exchange.
 */
final class SensitiveAuthResponsePrivacyB816Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('public_storefront.enabled', true);

        $owner = User::query()->create([
            'role_id' => Role::query()->where('slug','superadmin')->valueOrFail('id'),
            'username' => 'b816-synthetic-owner',
            'email' => 'b816-owner@example.test',
            'first_name' => 'Synthetic',
            'password_hash' => Hash::make('B816-Synthetic-Only123!'),
            'status' => 'active',
        ]);
        app(SettingsService::class)->putMany(
            ['public_storefront_open' => '1'],
            (int) $owner->id
        );
    }

    public static function sensitivePages(): array
    {
        $token = str_repeat('A', 80);
        return [
            'login-page' => ['/login',200],
            'forgot-password' => ['/forgot-password',200],
            'reset-token' => ['/reset-password/'.$token,200],
            'activation-token' => ['/activate-account/'.$token,200],
            'registered-buyer-signup' => ['/registracija',200],
            // An OAuth callback without server-issued state is rejected locally;
            // no Google HTTP request, secrets or live users.
            'oauth-callback' => ['/auth/google/callback?state=not-issued&code=synthetic',302],
        ];
    }

    #[DataProvider('sensitivePages')]
    public function test_sensitive_get_responses_disallow_referrer_and_cache_and_index(
        string $path, int $expectedStatus
    ): void {
        $response = $this->get($path)->assertStatus($expectedStatus);
        self::assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
        self::assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        self::assertSame('no-cache', $response->headers->get('Pragma'));
        self::assertStringContainsString('noindex', (string) $response->headers->get('X-Robots-Tag'));
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function test_invalid_registration_post_does_not_return_storable_response(): void
    {
        // The sender has not been approved: fail before any DB user/token writes.
        $response = $this->post('/registracija', [
            'email' => 'b816-pending@example.test',
            'first_name' => 'Synthetic',
            'last_name' => 'Kupac',
            'accepted_privacy' => '1',
        ])->assertRedirect();
        self::assertSame('no-referrer', $response->headers->get('Referrer-Policy'));
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        self::assertStringContainsString('noindex', (string) $response->headers->get('X-Robots-Tag'));
        self::assertDatabaseCount('user_activation_tokens',0);
        self::assertSame(1,User::query()->count());
    }

    public function test_public_catalog_retains_public_caching_boundary(): void
    {
        $response = $this->get('/katalog')->assertOk();
        self::assertSame('same-origin', $response->headers->get('Referrer-Policy'));
        self::assertStringNotContainsString('no-store',
            (string) $response->headers->get('Cache-Control'));
    }
}
