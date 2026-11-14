<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\Pdf\BusinessDocumentPdfService;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Logo size per place (admin header and footer, login pages, PDF documents) and
 * no text beside an uploaded logo. The storefront is covered by PublicStorefrontThemeLogoTest.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class LogoSizeEverywhereTest extends TestCase
{
    use RefreshDatabase;

    /** 120x30 px PNG. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAHgAAAAeCAIAAABoq03CAAAARUlEQVR42u3QQQ0AAAgEoItjHGMbyxbOBxsJSPVwIApEi0a0aNEWRItGtGjRFkSLRrRo0YgWjWjRohEtGtGiRSNaNKI/WrZQlQDSSGEmAAAAAElFTkSuQmCC';

    private User $admin;

    /** @var list<string> */
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        Storage::fake('public');
        $this->admin = User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'logo-size-admin',
            'email' => 'logo-size-admin@example.test',
            'password_hash' => Hash::make('OnlySynthetic123!'),
            'status' => 'active',
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) @unlink($file);
        parent::tearDown();
    }

    private function settings(array $values): void
    {
        app(SettingsService::class)->putMany($values, $this->admin->id);
    }

    private function withLogo(array $extra = []): void
    {
        Storage::disk('public')->put('site-assets/size-logo.png', (string) base64_decode(self::PNG));
        $this->settings(['site_logo_light_path' => 'site-assets/size-logo.png', 'site_logo_alt' => 'Ald1n logo', 'site_name' => 'Zz Firma Zz'] + $extra);
    }

    private function adminPage(): string
    {
        return (string) $this->actingAs($this->admin)->get('/admin/settings/appearance')->assertOk()->getContent();
    }

    public static function adminHeaderHeights(): array
    {
        return [
            'default' => [null, '32'],
            'saved' => ['44', '44'],
            'lower bound' => ['20', '20'],
            'upper bound' => ['56', '56'],
            'corrupted text' => ['abc', '32'],
            'above range' => ['57', '32'],
            'float' => ['40.5', '32'],
        ];
    }

    #[DataProvider('adminHeaderHeights')]
    public function test_admin_header_logo_height_and_no_name_text_beside_the_logo(?string $stored, string $height): void
    {
        $this->withLogo($stored === null ? [] : ['site_admin_logo_height' => $stored]);
        $html = $this->adminPage();

        self::assertMatchesRegularExpression('~<img id="siteHeaderLogo"[^>]*style="height:'.$height.'px"[^>]*alt="Ald1n logo"~', $html);
        self::assertStringNotContainsString('header-brand-name', $html);
        // The link keeps an accessible name without the text.
        self::assertMatchesRegularExpression('~<a class="brand"[^>]*aria-label="Zz Firma Zz"~', $html);
    }

    public function test_admin_header_shows_the_name_text_when_there_is_no_logo(): void
    {
        $this->settings(['site_name' => 'Zz Firma Zz']);
        $html = $this->adminPage();

        self::assertStringNotContainsString('siteHeaderLogo', $html);
        self::assertStringContainsString('<span class="header-brand-name">Zz Firma Zz</span>', $html);
        self::assertStringContainsString('<span class="brand-mark">A</span>', $html);
    }

    #[DataProvider('footerHeights')]
    public function test_admin_footer_logo_uses_the_footer_height(?string $stored, string $height): void
    {
        $this->withLogo(['site_footer_show_logo' => '1'] + ($stored === null ? [] : ['site_footer_logo_height' => $stored]));

        self::assertMatchesRegularExpression('~<img class="site-footer-logo"[^>]*height="'.$height.'">~', $this->adminPage());
    }

    public static function footerHeights(): array
    {
        return ['default' => [null, '28'], 'saved' => ['40', '40'], 'corrupted' => ['abc', '28'], 'below range' => ['5', '28']];
    }

    public static function authHeights(): array
    {
        return [
            'default' => [null, '48'],
            'saved' => ['64', '64'],
            'lower bound' => ['32', '32'],
            'upper bound' => ['80', '80'],
            'corrupted text' => ['abc', '48'],
            'below range' => ['31', '48'],
            'above range' => ['81', '48'],
        ];
    }

    #[DataProvider('authHeights')]
    public function test_login_page_logo_height_and_no_text_beside_the_logo(?string $stored, string $height): void
    {
        $this->withLogo($stored === null ? [] : ['site_auth_logo_height' => $stored]);
        $html = (string) $this->get('/login')->assertOk()->getContent();

        self::assertMatchesRegularExpression('~<img class="auth-logo"[^>]*alt="Ald1n logo" height="'.$height.'">~', $html);
        self::assertStringNotContainsString('<span class="brand-mark">A</span>', $html);
        self::assertStringNotContainsString('ZZ FIRMA ZZ', $html);
    }

    public function test_login_page_shows_the_name_text_when_there_is_no_logo(): void
    {
        $this->settings(['site_name' => 'Zz Firma Zz']);
        $html = (string) $this->get('/login')->assertOk()->getContent();

        self::assertStringNotContainsString('auth-logo', $html);
        self::assertStringContainsString('<span class="brand-mark">A</span>', $html);
        self::assertStringContainsString('ZZ FIRMA ZZ', $html);
    }

    // ---- PDF documents (unit: PDF points) ----

    private function squareLogo(): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'ald1n-sq-');
        $image = imagecreatetruecolor(60, 60);
        imagepng($image, $path);
        $this->files[] = $path;

        return $path;
    }

    private function ordersReportPdf(string $logo): string
    {
        return (new BusinessDocumentPdfService())->renderOrdersReport([
            'company_name' => 'ZzCompanyZz', 'company_logo_path' => $logo, 'generated_at' => 'x', 'summary' => [], 'orders' => [],
        ]);
    }

    public static function pdfHeights(): array
    {
        return [
            'default' => [null, '26.00'],
            'saved' => ['36', '36.00'],
            'lower bound' => ['16', '16.00'],
            'corrupted text' => ['abc', '26.00'],
            'above range' => ['37', '26.00'],
        ];
    }

    #[DataProvider('pdfHeights')]
    public function test_pdf_logo_height_follows_the_setting(?string $stored, string $drawn): void
    {
        $this->settings($stored === null ? [] : ['documents_logo_height' => $stored]);
        $pdf = $this->ordersReportPdf($this->squareLogo());

        self::assertStringContainsString('q '.$drawn.' 0 0 '.$drawn.' ', $pdf);
    }

    public function test_pdf_header_prints_the_company_name_only_without_a_logo(): void
    {
        self::assertStringNotContainsString('(ZzCompanyZz) Tj', $this->ordersReportPdf($this->squareLogo()));
        self::assertStringContainsString('(ZzCompanyZz) Tj', $this->ordersReportPdf(''));
    }

    // ---- PDF setting form ----

    /** @return array<string,string> */
    private function documentsForm(array $extra = []): array
    {
        return $extra + [
            'documents_company_name' => 'AP Computers', 'documents_company_address' => 'Primer ulica 1',
            'documents_company_city' => '11000 Beograd', 'documents_vat_rate' => '20', 'documents_payment_due_days' => '7',
        ];
    }

    public function test_documents_form_shows_the_default_and_saves_a_valid_height(): void
    {
        $this->actingAs($this->admin)->get('/admin/settings/documents')->assertOk()
            ->assertSee('name="documents_logo_height" min="16" max="36" step="1" value="26"', false)
            ->assertSee('podrazumevano 26 pt');

        $this->actingAs($this->admin)->put('/admin/settings/documents', $this->documentsForm(['documents_logo_height' => '30']))
            ->assertSessionHasNoErrors();
        self::assertSame('30', app(SettingsService::class)->get('documents_logo_height'));
    }

    public static function invalidPdfHeights(): array
    {
        return [
            'below range' => ['15'], 'above range' => ['37'], 'zero' => ['0'], 'negative' => ['-5'],
            'float' => ['26.5'], 'text' => ['abc'], 'empty' => [''], 'array' => [['30']],
        ];
    }

    #[DataProvider('invalidPdfHeights')]
    public function test_documents_form_rejects_an_invalid_height_and_stores_nothing(mixed $height): void
    {
        $this->actingAs($this->admin)->from('/admin/settings/documents')
            ->put('/admin/settings/documents', $this->documentsForm(['documents_logo_height' => $height]))
            ->assertSessionHasErrors(['documents_logo_height' => 'Visina logotipa na PDF dokumentima mora biti ceo broj od 16 do 36 pt.']);

        self::assertFalse(app(SettingsService::class)->hasStoredValue('documents_logo_height'));
        self::assertFalse(app(SettingsService::class)->hasStoredValue('documents_company_name'));
    }
}
