<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Tests\TestCase;

/**
 * B896/SEC23: executable PHP8.4/MariaDB10.11 HTTP proof for the
 * script-bearing public Kupac registration page only.
 * Synthetic data, no Turnstile network verification and no real email.
 * This is not a whole-CMS CSP, a live-browser UAT or ASVS completion.
 */
final class PublicRegistrationScriptCspB896Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('app.url', 'https://cms.ald1n.com');
        config()->set('public_storefront.enabled', true);
        config()->set('public_storefront.checkout_enabled', false);
        config()->set('services.turnstile.site_key', 'B896-SYNTHETIC-NONLIVE-SITE-KEY');
        app(SettingsService::class)->putMany([
            'public_storefront_open'=>'1',
            'turnstile_enabled'=>'1',
            'public_registration_sender_address'=>'',
        ], null);
    }

    public function test_live_turnstile_registration_limits_scripts_to_cloudflare_origin_only(): void
    {
        $response=$this->get('/registracija')->assertOk();
        $policy=$this->assertRegisterPolicy($response);
        self::assertStringContainsString("script-src https://challenges.cloudflare.com", $policy);
        self::assertStringContainsString("script-src-elem https://challenges.cloudflare.com", $policy);
        self::assertStringContainsString("script-src-attr 'none'", $policy);
        self::assertStringContainsString("frame-src https://challenges.cloudflare.com", $policy);
        self::assertStringContainsString("connect-src 'self' https://challenges.cloudflare.com", $policy);
        self::assertStringNotContainsString("'unsafe-inline'", $policy);
        self::assertStringNotContainsString("'unsafe-eval'", $policy);
        self::assertStringNotContainsString("script-src *", $policy);
        self::assertStringNotContainsString("https://www.youtube.com", $policy);
        $html=$response->getContent();
        self::assertIsString($html);
        self::assertStringContainsString(
            'src="https://challenges.cloudflare.com/turnstile/v0/api.js"', $html
        );
        self::assertSame(1,preg_match_all('~<script\b[^>]*>~i',$html,$scripts));
        self::assertStringContainsString('src=',(string)$scripts[0][0]);
        self::assertStringContainsString('B896-SYNTHETIC-NONLIVE-SITE-KEY',$html);
        self::assertDatabaseCount('users',0);
    }

    public function test_turnstile_disabled_registration_keeps_restricted_script_allowlist_without_script_tag(): void
    {
        app(SettingsService::class)->putMany(['turnstile_enabled'=>'0'],null);
        $response=$this->get('/registracija')->assertOk();
        $policy=$this->assertRegisterPolicy($response);
        self::assertStringContainsString("script-src https://challenges.cloudflare.com",$policy);
        self::assertStringContainsString("script-src-attr 'none'",$policy);
        self::assertStringNotContainsString('<script',strtolower($response->getContent()));
        self::assertDatabaseCount('users',0);
    }

    public function test_malicious_host_referer_and_forwarded_origin_cannot_infect_registration_policy(): void
    {
        $response=$this->withHeaders([
            'Host'=>'attacker.synthetic.invalid',
            'X-Forwarded-Host'=>'forwarded.synthetic.invalid',
            'X-Forwarded-Proto'=>'http',
            'Referer'=>'https://attacker.synthetic.invalid/landing',
            'Origin'=>'https://attacker.synthetic.invalid',
        ])->get('/registracija')->assertOk();
        $policy=$this->assertRegisterPolicy($response);
        self::assertStringNotContainsString('attacker.synthetic.invalid',$policy);
        self::assertStringNotContainsString('forwarded.synthetic.invalid',$policy);
        self::assertFalse($response->headers->has('Access-Control-Allow-Origin'));
    }

    public function test_off_registration_404_must_never_receive_the_success_page_script_policy(): void
    {
        config()->set('public_storefront.enabled',false);
        $response=$this->get('/registracija')->assertNotFound();
        $policy=(string)$response->headers->get('Content-Security-Policy');
        self::assertStringNotContainsString('script-src https://challenges.cloudflare.com',$policy);
        self::assertStringContainsString("base-uri 'none'",$policy);
        self::assertDatabaseCount('users',0);
    }

    public function test_registration_validation_redirect_keeps_original_global_header_not_success_page_csp(): void
    {
        $response=$this->post('/registracija',[
            'email'=>'invalid','first_name'=>'X',
        ])->assertRedirect('/registracija')->assertSessionHasErrors();
        $policy=(string)$response->headers->get('Content-Security-Policy');
        self::assertStringNotContainsString('script-src https://challenges.cloudflare.com',$policy);
        self::assertStringContainsString("base-uri 'none'",$policy);
        self::assertDatabaseCount('users',0);
    }

    public function test_legacy_login_theme_retains_its_original_global_policy(): void
    {
        $response=$this->get('/login')->assertOk();
        $policy=(string)$response->headers->get('Content-Security-Policy');
        self::assertStringContainsString("base-uri 'none'",$policy);
        self::assertStringNotContainsString("script-src https://challenges.cloudflare.com",$policy);
        self::assertStringNotContainsString("default-src 'none'",$policy);
    }

    public function test_existing_script_free_catalog_keeps_its_stronger_original_policy(): void
    {
        $response=$this->get('/katalog')->assertOk();
        $policy=(string)$response->headers->get('Content-Security-Policy');
        self::assertStringContainsString("script-src 'none'",$policy);
        self::assertStringContainsString("default-src 'none'",$policy);
        self::assertStringNotContainsString('challenges.cloudflare.com',$policy);
        self::assertDatabaseCount('orders',0);
    }

    private function assertRegisterPolicy(\Illuminate\Testing\TestResponse $response): string
    {
        $policy=(string)$response->headers->get('Content-Security-Policy');
        foreach ([
            "base-uri 'none'",
            "object-src 'none'",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "style-src 'self'",
            "img-src 'self' data: https://challenges.cloudflare.com",
            "font-src 'self' data:",
            "worker-src 'none'",
            "media-src 'none'",
        ] as $directive) {
            self::assertStringContainsString($directive,$policy);
        }
        self::assertFalse($response->headers->has('Content-Security-Policy-Report-Only'));
        $response->assertHeader('X-Frame-Options','SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options','nosniff');
        $response->assertHeader('Referrer-Policy','no-referrer');
        self::assertStringContainsString('no-store',(string)$response->headers->get('Cache-Control'));
        return $policy;
    }
}
