<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MobileGoogleAuthPhase3CContractTest extends TestCase
{
    public function test_phase3c_source_contains_google_auth_security_contract(): void
    {
        $root = dirname(__DIR__, 2);
        $verifier = file_get_contents($root.'/app/Services/GoogleIdentityVerifier.php');
        $service = file_get_contents($root.'/app/Services/GoogleAuthService.php');
        $routes = file_get_contents($root.'/routes/api.php');

        self::assertIsString($verifier);
        self::assertStringContainsString('OPENSSL_ALGO_SHA256', $verifier);
        self::assertStringContainsString("['accounts.google.com', 'https://accounts.google.com']", $verifier);
        self::assertStringContainsString("config('mobile.google_auth.web_client_id'", $verifier);
        self::assertStringContainsString("'sub'", $verifier);
        self::assertStringContainsString("'email_verified'", $verifier);
        self::assertStringContainsString("'hd'", $verifier);

        self::assertIsString($service);
        self::assertStringContainsString("provider_subject", $service);
        self::assertStringContainsString("status' => 'pending'", $service);
        self::assertStringContainsString("standardni-korisnik", $service);
        self::assertStringContainsString('googleIsAuthoritativeForEmail', $service);
        self::assertStringContainsString("@gmail.com", $service);
        self::assertStringContainsString("['hd']", $service);

        self::assertIsString($routes);
        self::assertStringContainsString("Route::post('/auth/google'", $routes);
        self::assertStringContainsString("throttle:api-google-auth", $routes);
    }
}
