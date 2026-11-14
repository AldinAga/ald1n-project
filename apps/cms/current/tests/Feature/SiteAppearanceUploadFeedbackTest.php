<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\CoreAccessSeeder;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Izgled sajta: every logo/favicon upload failure is visible and named.
 *
 * @author Ald1n <https://ald1n.com>
 */
final class SiteAppearanceUploadFeedbackTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(CoreAccessSeeder::class);
        Storage::fake('public');
        $this->admin = User::query()->create([
            'role_id' => Role::query()->where('slug', 'admin')->valueOrFail('id'),
            'username' => 'appearance-admin',
            'email' => 'appearance-admin@example.test',
            'password_hash' => Hash::make('OnlySynthetic123!'),
            'status' => 'active',
        ]);
    }

    public function test_upload_rejected_by_php_size_limit_is_reported_instead_of_skipped(): void
    {
        $file = $this->failedUpload('logo.png', UPLOAD_ERR_INI_SIZE);
        $this->actingAs($this->admin)->from('/admin/settings/appearance')
            ->put('/admin/settings/appearance', $this->form(['site_logo_light' => $file]))
            ->assertRedirect('/admin/settings/appearance')
            ->assertSessionHasErrors('site_logo_light');

        $message = (string) session('errors')->first('site_logo_light');
        self::assertStringContainsString('Logo za svetlu temu', $message);
        self::assertStringContainsString('upload_max_filesize', $message);
        self::assertStringContainsString('4 MB', $message);
        // Nothing else from the form was silently saved.
        self::assertFalse(app(SettingsService::class)->hasStoredValue('site_name'));
        self::assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_partial_and_server_side_upload_errors_have_clear_messages(): void
    {
        foreach ([
            [UPLOAD_ERR_PARTIAL, 'site_logo_dark', 'Logo za tamnu temu', 'delimično'],
            [UPLOAD_ERR_NO_TMP_DIR, 'site_favicon', 'Favicon', 'privremeni direktorijum'],
            [UPLOAD_ERR_CANT_WRITE, 'site_logo_light', 'Logo za svetlu temu', 'upiše datoteku'],
            [UPLOAD_ERR_FORM_SIZE, 'site_favicon', 'Favicon', '1 MB'],
        ] as [$error, $input, $label, $reason]) {
            $this->actingAs($this->admin)->from('/admin/settings/appearance')
                ->put('/admin/settings/appearance', $this->form([$input => $this->failedUpload('asset.png', $error)]))
                ->assertSessionHasErrors($input);
            $message = (string) session('errors')->first($input);
            self::assertStringContainsString($label, $message);
            self::assertStringContainsString($reason, $message);
        }
    }

    public function test_unusable_svg_and_oversized_logo_get_serbian_message_with_types_and_limit(): void
    {
        // SVG is accepted (sanitized); one without dimensions or content is
        // refused with a named Serbian reason (SiteLogoSvgTest covers the rest).
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $this->actingAs($this->admin)->from('/admin/settings/appearance')
            ->put('/admin/settings/appearance', $this->form(['site_logo_light' => $svg]))
            ->assertSessionHasErrors('site_logo_light');
        $message = (string) session('errors')->first('site_logo_light');
        self::assertStringStartsWith('Logo za svetlu temu: ', $message);
        self::assertStringContainsString('SVG nema dimenzije', $message);
        self::assertStringNotContainsString('nije podržan', $message);

        $big = UploadedFile::fake()->image('logo.png', 20, 20)->size(5000);
        $this->actingAs($this->admin)->from('/admin/settings/appearance')
            ->put('/admin/settings/appearance', $this->form(['site_logo_dark' => $big]))
            ->assertSessionHasErrors('site_logo_dark');
        $message = (string) session('errors')->first('site_logo_dark');
        self::assertStringContainsString('Logo za tamnu temu', $message);
        self::assertStringContainsString('PNG, JPG, WEBP ili SVG', $message);
        self::assertStringContainsString('4 MB (4096 KB)', $message);

        $favicon = UploadedFile::fake()->image('icon.jpg', 16, 16);
        $this->actingAs($this->admin)->from('/admin/settings/appearance')
            ->put('/admin/settings/appearance', $this->form(['site_favicon' => $favicon]))
            ->assertSessionHasErrors('site_favicon');
        self::assertStringContainsString('PNG, ICO ili SVG', (string) session('errors')->first('site_favicon'));
        self::assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_successful_upload_names_what_was_saved(): void
    {
        $this->actingAs($this->admin)->from('/admin/settings/appearance')
            ->put('/admin/settings/appearance', $this->form([
                'site_logo_light' => UploadedFile::fake()->image('logo.png', 120, 40),
                'site_favicon' => UploadedFile::fake()->image('icon.png', 32, 32),
            ]))
            ->assertRedirect('/admin/settings/appearance')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Izgled sajta je sačuvan. Otpremljeno: logo za svetlu temu, favicon.');

        $path = (string) app(SettingsService::class)->get('site_logo_light_path');
        self::assertStringStartsWith('site-assets/', $path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($this->admin)->put('/admin/settings/appearance', $this->form())
            ->assertSessionHas('status', 'Izgled sajta je sačuvan.');
    }

    public function test_page_shows_hints_and_saved_logo_file_status(): void
    {
        Storage::disk('public')->put('site-assets/present-dark.png', 'png-bytes');
        app(SettingsService::class)->putMany([
            'site_logo_light_path' => 'site-assets/missing-light.png',
            'site_logo_dark_path' => 'site-assets/present-dark.png',
        ], $this->admin->id);

        $response = $this->actingAs($this->admin)->get('/admin/settings/appearance')->assertOk()
            ->assertSee('Datoteka nedostaje na disku')
            ->assertSee('Datoteka postoji na disku')
            ->assertSee('data-asset-status="missing"', false)
            ->assertSee('data-asset-status="present"', false)
            ->assertSee('Dozvoljeni formati su PNG, JPG, WEBP ili SVG, najviše 4 MB (4096 KB); SVG (do 200 KB) se automatski čisti od skripti i spoljnih veza.')
            ->assertSee('Dozvoljeni formati su PNG, ICO ili SVG, najviše 1 MB (1024 KB); SVG (do 200 KB) se automatski čisti od skripti i spoljnih veza.')
            ->assertDontSee('SVG nije podržan')
            ->assertSee('accept=".png,.jpg,.jpeg,.webp,.svg"', false)
            ->assertSee('accept=".png,.ico,.svg"', false);
        self::assertStringContainsString(Storage::disk('public')->url('site-assets/present-dark.png'), (string) $response->getContent());
        // A missing file is not rendered as a broken image.
        self::assertStringNotContainsString('src="'.Storage::disk('public')->url('site-assets/missing-light.png').'"', (string) $response->getContent());
    }

    public function test_post_max_size_overflow_returns_to_page_with_friendly_message(): void
    {
        $handler = app(ExceptionHandler::class);
        $response = $handler->render(Request::create('/admin/settings/appearance', 'POST'), new PostTooLargeException());
        self::assertSame(303, $response->getStatusCode());
        self::assertSame('/admin/settings/appearance?upload=too-large', $response->headers->get('Location'));

        // Other pages keep Laravel's default 413 handling.
        $other = $handler->render(Request::create('/admin/settings/documents', 'POST'), new PostTooLargeException());
        self::assertSame(413, $other->getStatusCode());

        $limit = $this->iniBytes((string) ini_get('post_max_size'));
        if ($limit > 0) {
            // Real HTTP path through ValidatePostSize (PHP has already discarded the body).
            $this->call('POST', '/admin/settings/appearance', [], [], [], ['CONTENT_LENGTH' => (string) ($limit + 1)])
                ->assertStatus(303)
                ->assertRedirect('/admin/settings/appearance?upload=too-large');
        }

        $this->actingAs($this->admin)->get('/admin/settings/appearance?upload=too-large')->assertOk()
            ->assertSee('data-upload-too-large', false)
            ->assertSee('post_max_size')
            ->assertSee('Ništa nije sačuvano');
        $this->actingAs($this->admin)->get('/admin/settings/appearance?upload=%3Cscript%3E')->assertOk()
            ->assertDontSee('data-upload-too-large', false);
    }

    /** @return array<string,array{string,int,int,int,string}> key, min, max, default, message */
    public static function logoHeightFields(): array
    {
        return [
            'header' => ['site_header_logo_height', 24, 80, 32, 'Visina logotipa u zaglavlju mora biti ceo broj od 24 do 80 px.'],
            'footer' => ['site_footer_logo_height', 20, 96, 28, 'Visina logotipa u podnožju mora biti ceo broj od 20 do 96 px.'],
            'admin' => ['site_admin_logo_height', 20, 56, 32, 'Visina logotipa u zaglavlju admin panela mora biti ceo broj od 20 do 56 px.'],
            'auth' => ['site_auth_logo_height', 32, 80, 48, 'Visina logotipa na stranicama za prijavu mora biti ceo broj od 32 do 80 px.'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('logoHeightFields')]
    public function test_logo_height_default_is_shown_and_saved_values_are_stored(string $key, int $min, int $max, int $default): void
    {
        $this->actingAs($this->admin)->get('/admin/settings/appearance')->assertOk()
            ->assertSee('name="'.$key.'" min="'.$min.'" max="'.$max.'" step="1" value="'.$default.'"', false)
            ->assertSee('podrazumevano '.$default.' px');

        foreach ([$min, intdiv($min + $max, 2), $max] as $height) {
            $this->actingAs($this->admin)->put('/admin/settings/appearance', $this->form([$key => (string) $height]))
                ->assertSessionHasNoErrors();
            self::assertSame((string) $height, app(SettingsService::class)->get($key));
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('logoHeightFields')]
    public function test_logo_height_absent_from_a_partial_save_keeps_the_stored_value(string $key, int $min, int $max): void
    {
        app(SettingsService::class)->putMany([$key => (string) $max], $this->admin->id);
        $form = $this->form();
        unset($form[$key]);
        $this->actingAs($this->admin)->put('/admin/settings/appearance', $form);
        self::assertSame((string) $max, app(SettingsService::class)->get($key));
    }

    /** @return array<string,array{mixed}> Rejected for every field, whatever its range. */
    public static function invalidLogoHeights(): array
    {
        return [
            'zero' => ['0'], 'negative' => ['-5'], 'far above range' => ['500'],
            'float' => ['32.5'], 'text' => ['abc'], 'empty' => [''], 'array' => [['40']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('logoHeightFields')]
    public function test_invalid_logo_heights_are_rejected_with_a_serbian_message_and_nothing_is_stored(string $key, int $min, int $max, int $default, string $message): void
    {
        $invalid = [(string) ($min - 1), (string) ($max + 1)];
        foreach (array_merge($invalid, array_map(static fn (array $row): mixed => $row[0], self::invalidLogoHeights())) as $value) {
            $this->actingAs($this->admin)->from('/admin/settings/appearance')
                ->put('/admin/settings/appearance', $this->form([$key => $value]))
                ->assertRedirect('/admin/settings/appearance')
                ->assertSessionHasErrors([$key => $message]);

            self::assertFalse(app(SettingsService::class)->hasStoredValue($key));
            self::assertFalse(app(SettingsService::class)->hasStoredValue('site_name'));
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('logoHeightFields')]
    public function test_a_corrupted_stored_logo_height_reads_as_the_default(string $key, int $min, int $max, int $default): void
    {
        foreach (['abc', '', (string) ($min - 1), (string) ($max + 1), '30.5'] as $stored) {
            app(SettingsService::class)->putMany([$key => $stored], $this->admin->id);
            self::assertSame((string) $default, app(SettingsService::class)->get($key), 'stored: '.$stored);
        }
    }

    public function test_non_admin_cannot_save_the_footer_logo_height(): void
    {
        $user = User::query()->create([
            'role_id' => Role::query()->where('slug', 'user')->valueOrFail('id'),
            'username' => 'appearance-user',
            'email' => 'appearance-user@example.test',
            'password_hash' => Hash::make('OnlySynthetic123!'),
            'status' => 'active',
        ]);
        $this->actingAs($user)->put('/admin/settings/appearance', $this->form(['site_footer_logo_height' => '50']))->assertForbidden();
        self::assertFalse(app(SettingsService::class)->hasStoredValue('site_footer_logo_height'));
    }

    /** @param array<string,mixed> $extra @return array<string,mixed> */
    private function form(array $extra = []): array
    {
        return array_replace([
            'site_name' => 'Ald1n Upload Test',
            'site_logo_alt' => 'Ald1n',
            'site_header_logo_height' => 40,
            'site_footer_layout' => 'split',
        ], $extra);
    }

    private function failedUpload(string $name, int $error): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'ald1n-upload');
        file_put_contents($path, 'partial');

        return new UploadedFile($path, $name, 'image/png', $error, true);
    }

    private function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || !preg_match('/^(\d+)([KMG]?)$/i', $value, $match)) {
            return 0;
        }

        return (int) $match[1] * match (strtoupper($match[2])) {
            'K' => 1024, 'M' => 1024 ** 2, 'G' => 1024 ** 3, default => 1,
        };
    }
}
