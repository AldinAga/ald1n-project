<?php
declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * B866-B869: bounded, synthetic HTTP response security tests.
 * No controller/checkout implementation fixtures, no logged-in buyer,
 * no real mail, no CSRF probe, no user/session manipulation, no production.
 * Only evidence for scoped security headers, never full OWASP item PASS.
 */
final class PublicResponseHeadersB866Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('app.url','https://cms.ald1n.com');
        config()->set('public_storefront.enabled', false);
        config()->set('services.turnstile.enabled',false);
    }

    /** @return array<string,array{string,int,bool}> */
    public static function privateRoutes(): array
    {
        $token=str_repeat('a',64);
        return [
            'login'=>['/login',200,true],
            'password-request'=>['/forgot-password',200,true],
            'off-registration'=>['/registracija',404,true],
            'off-checkout'=>['/porucivanje',404,false],
            'off-receipt'=>['/porudzbina/'.$token,404,true],
            'off-document'=>['/porudzbina/'.$token.'/dokument/1',404,true],
            'off-warranty'=>['/porudzbina/'.$token.'/garancija/1',404,true],
        ];
    }

    #[DataProvider('privateRoutes')]
    public function test_sensitive_routes_are_private_no_store_and_unindexed_even_when_off(
        string $uri, int $status, bool $noReferrer
    ): void {
        $response=$this->get($uri)->assertStatus($status);
        $this->assertContainsHeaderTokens($response,'Cache-Control',['private','no-store']);
        $response->assertHeader('Pragma','no-cache');
        $this->assertContainsHeaderTokens($response,'X-Robots-Tag',['noindex','nofollow','noarchive']);
        $response->assertHeader('X-Content-Type-Options','nosniff');
        if ($noReferrer) {
            $response->assertHeader('Referrer-Policy','no-referrer');
        }
    }

    public function test_global_csp_uses_none_for_base_url_and_object_in_all_public_errors(): void
    {
        $response=$this->get('/katalog')->assertNotFound();
        $csp=(string)$response->headers->get('Content-Security-Policy');
        self::assertStringContainsString("base-uri 'none'", $csp);
        self::assertStringContainsString("object-src 'none'", $csp);
        self::assertStringContainsString("frame-ancestors 'self'", $csp);
        self::assertStringContainsString("form-action 'self'", $csp);
        $response->assertHeader('X-Content-Type-Options','nosniff');
        $response->assertHeader('X-Frame-Options','SAMEORIGIN');
        $response->assertHeader('Cross-Origin-Opener-Policy','same-origin');
        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function test_guest_auth_page_has_security_headers_without_misleading_full_csp_pass(): void
    {
        $response=$this->get('/login')->assertOk();
        $csp=(string)$response->headers->get('Content-Security-Policy');
        self::assertStringContainsString("base-uri 'none'", $csp);
        self::assertStringContainsString("frame-ancestors 'self'", $csp);
        $response->assertHeader('X-Frame-Options','SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options','nosniff');
        $response->assertHeader('Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()');
    }

    public function test_untrusted_origin_is_not_reflected_into_browser_cross_origin_access(): void
    {
        $response=$this->withHeaders([
            'Origin'=>'https://evil.synthetic.example.test',
            'X-Forwarded-Host'=>'evil.synthetic.example.test',
        ])->get('/login')->assertOk();
        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
        self::assertFalse($response->headers->has('Access-Control-Allow-Credentials'));
        self::assertStringNotContainsString('evil.synthetic.example.test',
            (string)$response->headers->get('Content-Security-Policy'));
    }

    public function test_off_private_json_receipt_is_404_without_token_in_response_headers(): void
    {
        $token=str_repeat('f',64);
        $response=$this->getJson('/porudzbina/'.$token)->assertNotFound();
        $response->assertHeader('Referrer-Policy','no-referrer');
        $this->assertContainsHeaderTokens($response,'Cache-Control',['private','no-store']);
        self::assertFalse($response->headers->has('Location'));
        foreach (['Referrer-Policy','X-Robots-Tag','Cache-Control',
                  'Content-Security-Policy'] as $field) {
            self::assertStringNotContainsString($token,
                (string)$response->headers->get($field),$field);
        }
    }

    /** @param array<int,string> $tokens */
    private function assertContainsHeaderTokens(
        TestResponse $response,string $name,array $tokens
    ): void {
        $value=strtolower((string)$response->headers->get($name));
        foreach ($tokens as $token) {
            self::assertStringContainsString(strtolower($token),$value,$name);
        }
    }
}
