<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use App\Support\LegalTextRenderer;
use App\Support\PublicLegalTemplates;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Cloudflare Email Address Obfuscation rewrites every address on a page into
 * data-cfemail and injects a decoder script. The storefront CSP allows only
 * one nonce'd script, so the decoder never runs and visitors read
 * "[email protected]" instead of the trader's address. The cure is the
 * documented email_off comment pair around the page. Cloudflare is not part
 * of CI, so these tests pin the markup that makes it leave the addresses
 * alone; whether the live HTML still carries __cf_email__ must be checked
 * after installation.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class PublicStorefrontEmailOffTest extends TestCase
{
    use RefreshDatabase;

    private const SUPPORT = 'podrska@example.test';
    private const COMPANY = 'firma@example.test';
    private const OPEN = '<!--email_off-->';
    private const CLOSE = '<!--/email_off-->';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        $this->withoutMiddleware(ThrottleRequests::class);
        config()->set('app.url', 'https://cms.ald1n.com');
        config()->set('public_storefront.enabled', false);
        config()->set('public_storefront.checkout_enabled', false);
        $owner = User::query()->create([
            'role_id' => Role::query()->where('slug', 'superadmin')->valueOrFail('id'),
            'username' => 'emailoff-owner', 'email' => 'emailoff-owner@example.test',
            'first_name' => 'Test', 'password_hash' => Hash::make('SyntheticOnly123!'),
            'status' => 'active',
        ]);
        app(SettingsService::class)->putMany([
            'documents_company_name' => 'Sinteticki Trgovac DOO',
            'documents_company_address' => 'Testna ulica 10',
            'documents_company_city' => 'Beograd',
            'documents_company_tax_id' => '100200300',
            'documents_company_email' => self::COMPANY,
            'public_checkout_support_email' => self::SUPPORT,
        ], $owner->id);
    }

    /** @return list<string> */
    private function allUris(): array
    {
        return ['/pravne-informacije', ...array_map(
            static fn (array $page): string => '/'.$page['slug'], array_values(PublicLegalTemplates::PAGES))];
    }

    public function test_every_legal_page_wraps_the_whole_body_in_one_email_off_pair(): void
    {
        $uris = $this->allUris();
        self::assertCount(8, $uris);
        foreach ($uris as $uri) {
            $html = $this->get($uri)->assertOk()->getContent();
            self::assertSame(1, substr_count($html, self::OPEN), $uri.' open');
            self::assertSame(1, substr_count($html, self::CLOSE), $uri.' close');
            $open = strpos($html, self::OPEN);
            $close = strpos($html, self::CLOSE);
            self::assertLessThan($close, $open, $uri.' order');
            self::assertGreaterThan(strpos($html, '<body'), $open, $uri.' inside body');
            // Header, article and footer are all inside; the nonce'd script is not.
            self::assertGreaterThan($open, strpos($html, '<main'), $uri);
            self::assertLessThan($close, strpos($html, '<footer'), $uri);
            self::assertLessThan(strpos($html, '<script nonce='), $close, $uri.' script outside');
        }
    }

    public function test_the_trader_address_is_inside_the_pair_on_every_page_that_shows_it(): void
    {
        $withAddress = 0;
        foreach ($this->allUris() as $uri) {
            $html = $this->get($uri)->assertOk()->getContent();
            $open = strpos($html, self::OPEN);
            $close = strpos($html, self::CLOSE);
            foreach ([self::SUPPORT, self::COMPANY] as $mail) {
                $offset = 0;
                while (($at = strpos($html, $mail, $offset)) !== false) {
                    self::assertGreaterThan($open, $at, $uri.' '.$mail.' before the pair');
                    self::assertLessThan($close, $at, $uri.' '.$mail.' after the pair');
                    $offset = $at + 1;
                    $withAddress++;
                }
            }
        }
        // 7 pages quote the support address; the trader page also shows the company one.
        self::assertGreaterThanOrEqual(8, $withAddress);
        foreach (['/podaci-o-trgovcu' => [self::SUPPORT, self::COMPANY], '/reklamacije' => [self::SUPPORT],
            '/pravo-na-odustanak' => [self::SUPPORT]] as $uri => $mails) {
            $html = $this->get($uri)->getContent();
            foreach ($mails as $mail) {
                self::assertStringContainsString($mail, $html, $uri);
            }
        }
    }

    public function test_the_footer_and_other_shop_pages_use_the_same_layout_wrapper(): void
    {
        $layout = (string) file_get_contents(resource_path('views/public-shop/layout.blade.php'));
        self::assertSame(1, substr_count($layout, self::OPEN));
        self::assertSame(1, substr_count($layout, self::CLOSE));
        self::assertLessThan(strpos($layout, '<footer'), strpos($layout, self::OPEN));
        self::assertGreaterThan(strpos($layout, '</footer>'), strpos($layout, self::CLOSE));
        foreach (glob(resource_path('views/public-shop/*.blade.php')) ?: [] as $view) {
            if (basename($view) === 'layout.blade.php') continue;
            self::assertStringNotContainsString('email_off', (string) file_get_contents($view), basename($view).' must not nest a second pair');
        }
    }

    public function test_owner_text_cannot_close_or_forge_the_pair(): void
    {
        $text = "Pisite na {{email_podrske}} <!--/email_off--><script>alert(1)</script> <!--email_off-->";
        $html = (new LegalTextRenderer())->render($text, ['email_podrske' => self::SUPPORT]);
        self::assertStringNotContainsString('<!--', $html);
        self::assertStringNotContainsString('<script', $html);
        self::assertStringContainsString('&lt;!--/email_off--&gt;', $html);
    }
}
