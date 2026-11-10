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

    public function test_svg_and_oversized_logo_get_serbian_message_with_types_and_limit(): void
    {
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $this->actingAs($this->admin)->from('/admin/settings/appearance')
            ->put('/admin/settings/appearance', $this->form(['site_logo_light' => $svg]))
            ->assertSessionHasErrors('site_logo_light');
        $message = (string) session('errors')->first('site_logo_light');
        self::assertStringContainsString('Logo za svetlu temu', $message);
        self::assertStringContainsString('PNG, JPG, JPEG ili WEBP', $message);
        self::assertStringContainsString('4 MB (4096 KB)', $message);
        self::assertStringContainsString('SVG nije podržan', $message);

        $big = UploadedFile::fake()->image('logo.png', 20, 20)->size(5000);
        $this->actingAs($this->admin)->from('/admin/settings/appearance')
            ->put('/admin/settings/appearance', $this->form(['site_logo_dark' => $big]))
            ->assertSessionHasErrors('site_logo_dark');
        $message = (string) session('errors')->first('site_logo_dark');
        self::assertStringContainsString('Logo za tamnu temu', $message);
        self::assertStringContainsString('4 MB (4096 KB)', $message);

        $favicon = UploadedFile::fake()->image('icon.jpg', 16, 16);
        $this->actingAs($this->admin)->from('/admin/settings/appearance')
            ->put('/admin/settings/appearance', $this->form(['site_favicon' => $favicon]))
            ->assertSessionHasErrors('site_favicon');
        self::assertStringContainsString('PNG ili ICO', (string) session('errors')->first('site_favicon'));
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
            ->assertSee('Dozvoljeni formati su PNG, JPG, JPEG ili WEBP, najviše 4 MB (4096 KB); SVG nije podržan.')
            ->assertSee('Dozvoljeni formati su PNG ili ICO, najviše 1 MB (1024 KB); SVG nije podržan.');
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
