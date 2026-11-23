<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Pages without a session (login, password reset, activation, registration,
 * errors) are Kobalt: no orange accent, no hex colours, no inline styles.
 */
final class GuestPagesCobaltContractTest extends TestCase
{
    private const FILES = [
        'resources/views/auth/login.blade.php',
        'resources/views/auth/forgot-password.blade.php',
        'resources/views/auth/reset-password.blade.php',
        'resources/views/auth/activate-account.blade.php',
        'resources/views/auth/partials/brand.blade.php',
        'resources/views/errors/minimal.blade.php',
        'resources/views/public-shop/register.blade.php',
        'public/assets/css/ald1n-auth.css',
        'public/assets/css/ald1n-public-register.css',
    ];

    /** @return list<string> FILES plus the guest layout and every error page, relative to the CMS root */
    private function files(): array
    {
        $root = dirname(__DIR__, 2);
        $errors = array_map(
            static fn (string $path): string => 'resources/views/errors/'.basename($path),
            glob($root.'/resources/views/errors/*.blade.php') ?: []
        );
        // Floor: 403, 404, 419, 429, 500, 503 and minimal exist; an empty glob must not pass.
        self::assertGreaterThanOrEqual(7, count($errors));

        return array_values(array_unique(array_merge(['resources/views/layouts/guest.blade.php'], $errors, self::FILES)));
    }

    public function test_every_login_overlay_and_blur_value_has_a_css_rule(): void
    {
        $root = dirname(__DIR__, 2);
        $view = (string) file_get_contents($root.'/resources/views/auth/login.blade.php');
        $css = (string) file_get_contents($root.'/public/assets/css/ald1n-auth.css');

        // The view clamps the settings; the CSS must cover every value the clamp (and the step of 5) can emit.
        self::assertSame(1, preg_match('/\$loginOverlay = max\(0, min\((\d+),/', $view, $overlay));
        self::assertSame(1, preg_match('/\$loginBlur = max\(0, min\((\d+),/', $view, $blur));
        self::assertStringContainsString('round($loginOverlay / 5) * 5', $view);

        $missing = [];
        for ($value = 0; $value <= (int) $overlay[1]; $value += 5) {
            if (!str_contains($css, '[data-o="'.$value.'"]')) {
                $missing[] = 'data-o='.$value;
            }
        }
        for ($value = 0; $value <= (int) $blur[1]; $value++) {
            if (!str_contains($css, '[data-b="'.$value.'"]')) {
                $missing[] = 'data-b='.$value;
            }
        }
        self::assertSame([], $missing, 'ald1n-auth.css lacks a rule for: '.implode(', ', $missing));
        // Positive control: a value outside the clamp has no rule, so the loop above can fail.
        self::assertStringNotContainsString('[data-o="95"]', $css);
    }

    public function test_login_slides_after_the_first_and_the_fallback_load_lazily(): void
    {
        $view = (string) file_get_contents(dirname(__DIR__, 2).'/resources/views/auth/login.blade.php');
        self::assertStringContainsString('@if($index > 0)loading="lazy" @endif', $view);
        self::assertMatchesRegularExpression('/class="a1-auth-bg__fallback"[^>]*loading="lazy"/', $view);
    }

    public function test_guest_page_sources_have_no_hex_colours_or_inline_styles(): void
    {
        foreach ($this->files() as $file) {
            $source = (string) file_get_contents(dirname(__DIR__, 2).'/'.$file);

            self::assertNotSame('', $source, $file.' is empty or missing');
            self::assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', $source, $file.' has a hex colour');
            self::assertDoesNotMatchRegularExpression('/\b(rgba?|hsla?)\s*\(/i', $source, $file.' has an rgb()/hsl() colour');
            self::assertDoesNotMatchRegularExpression('/c45116|ff8a3d/i', $source, $file.' still has the orange accent');
            self::assertDoesNotMatchRegularExpression('/<style\b|\sstyle\s*=/i', $source, $file.' has inline styles');
        }
    }
}
