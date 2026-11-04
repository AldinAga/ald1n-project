<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Role, User};
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, Route};
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Real middleware/rendering; browser refusal and HTTPS are separate CI cases. */
final class TrustedScriptCspB1115Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        config()->set('services.turnstile.enabled', false);
    }

    public static function authPages(): array
    {
        return [
            'login' => ['/login'],
            'forgot' => ['/forgot-password'],
            'reset' => ['/reset-password/'.str_repeat('R', 80)],
            'activation' => ['/activate-account/'.str_repeat('A', 80)],
        ];
    }

    #[DataProvider('authPages')]
    public function test_auth_scripts_require_a_matching_unpredictable_request_nonce(string $uri): void
    {
        $first = $this->get($uri)->assertOk();
        $nonce = $this->assertTrustedHtml($first);
        $second = $this->withHeaders(['If-None-Match' => 'synthetic-old', 'X-CSP-Nonce' => $nonce])
            ->get($uri.'?_csp_nonce='.$nonce)->assertOk();
        self::assertNotSame($nonce, $this->assertTrustedHtml($second), 'B1115_NONCE_MUST_BE_FRESH');
        $second->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_admin_templates_use_the_request_nonce_and_keep_owner_permissions(): void
    {
        $owner = $this->person('owner', 'superadmin');
        $login = $this->post('/login', ['login' => $owner->email, 'password' => 'B1115-Synthetic-Only123!'])->assertRedirect();
        foreach ($login->headers->getCookies() as $cookie) $this->withUnencryptedCookie($cookie->getName(), $cookie->getValue());
        $this->assertAuthenticatedAs($owner);
        $seen = [];
        foreach (['/account', '/admin/catalog/create', '/admin/catalog-settings/brands', '/catalog', '/order/new'] as $uri) {
            $response = $this->get($uri)->assertOk();
            $seen[] = $this->assertTrustedHtml($response);
        }
        self::assertCount(count($seen), array_unique($seen), 'B1115_CROSS_RESPONSE_NONCE_REUSE');
    }

    public function test_turnstile_tags_are_explicitly_trusted_without_broad_origin_fallback(): void
    {
        config()->set('services.turnstile.enabled', true);
        config()->set('services.turnstile.site_key', 'synthetic-site-key');
        config()->set('services.turnstile.secret_key', 'synthetic-not-a-provider-secret');
        foreach (['/login', '/forgot-password', '/activate-account/'.str_repeat('A', 80)] as $uri) {
            $response = $this->get($uri)->assertOk();
            $this->assertTrustedHtml($response);
            self::assertStringContainsString('challenges.cloudflare.com/turnstile/v0/api.js', $response->getContent());
        }
    }

    #[DataProvider('routedErrors')]
    public function test_routed_html_errors_keep_nonce_policy_and_back_control(int $status): void
    {
        Route::get('/b1115-error-'.$status, static fn () => abort($status));
        config()->set('app.debug', false);
        $response = $this->get('/b1115-error-'.$status)->assertStatus($status);
        $this->assertTrustedHtml($response);
    }

    public static function routedErrors(): array
    {
        return ['forbidden' => [403], 'missing' => [404], 'expired' => [419], 'invalid' => [422], 'server' => [500]];
    }

    public function test_nonce_policy_does_not_transform_json_pdf_or_redirect(): void
    {
        Route::get('/b1115-json', static fn () => response()->json(['synthetic' => true], 422)
            ->header('Cache-Control', 'private, no-store')->header('Referrer-Policy', 'no-referrer'));
        Route::get('/b1115-pdf', static fn () => response('%PDF-B1115-SYNTHETIC', 200, [
            'Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="synthetic.pdf"',
            'Cache-Control' => 'private, no-store',
        ]));
        Route::get('/b1115-redirect', static fn () => redirect('/login')->header('Referrer-Policy', 'no-referrer'));
        $json = $this->getJson('/b1115-json')->assertUnprocessable()->assertExactJson(['synthetic' => true]);
        self::assertStringContainsString('application/json', (string) $json->headers->get('Content-Type'));
        $pdf = $this->get('/b1115-pdf')->assertOk()->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'inline; filename="synthetic.pdf"');
        self::assertSame('%PDF-B1115-SYNTHETIC', $pdf->getContent());
        $this->get('/b1115-redirect')->assertRedirect('/login');
        foreach ([$json, $pdf] as $response) self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    private function assertTrustedHtml(TestResponse $response): string
    {
        $policy = (string) $response->headers->get('Content-Security-Policy');
        self::assertSame(1, preg_match("/\bscript-src 'nonce-([A-Za-z0-9+\/=]+)'(?:;|$)/", $policy, $matches), 'B1115_MISSING_NONCE_ONLY_SCRIPT_SRC');
        $nonce = $matches[1];
        self::assertSame(16, strlen((string) base64_decode($nonce, true)), 'B1115_NONCE_ENTROPY');
        foreach (["script-src-attr 'none'", "base-uri 'none'", "object-src 'none'", "frame-ancestors 'self'", "form-action 'self'"] as $directive) {
            self::assertStringContainsString($directive, $policy);
        }
        foreach (["'unsafe-inline'", "'unsafe-eval'", "'strict-dynamic'", "script-src 'self'"] as $unsafe) self::assertStringNotContainsString($unsafe, $policy);
        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$response->getContent());
        $scripts = $dom->getElementsByTagName('script');
        self::assertGreaterThan(0, $scripts->length, 'B1115_EXPECTED_TRUSTED_SCRIPTS');
        foreach ($scripts as $script) self::assertSame($nonce, $script->getAttribute('nonce'), 'B1115_TRUSTED_TAG_HEADER_MISMATCH');
        foreach ($dom->getElementsByTagName('*') as $element) {
            foreach ($element->attributes as $attribute) self::assertFalse(str_starts_with(strtolower($attribute->name), 'on'), 'B1115_INLINE_EVENT_HANDLER');
        }
        self::assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'), 'B1115_NONCE_HTML_MUST_NOT_BE_STORED');
        return $nonce;
    }

    private function person(string $name, string $role): User
    {
        return User::query()->create([
            'role_id' => Role::query()->where('slug', $role)->valueOrFail('id'),
            'username' => 'b1115-'.$name, 'email' => 'b1115-'.$name.'@example.test',
            'password_hash' => Hash::make('B1115-Synthetic-Only123!'), 'status' => 'active',
            'email_verified_at' => now(), 'portal_activated_at' => now(),
        ]);
    }
}
