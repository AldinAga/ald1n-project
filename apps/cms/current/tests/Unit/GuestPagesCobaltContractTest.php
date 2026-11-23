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

    public function test_guest_page_sources_have_no_hex_colours_or_inline_styles(): void
    {
        foreach (self::FILES as $file) {
            $source = (string) file_get_contents(dirname(__DIR__, 2).'/'.$file);

            self::assertNotSame('', $source, $file.' is empty or missing');
            self::assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', $source, $file.' has a hex colour');
            self::assertDoesNotMatchRegularExpression('/c45116|ff8a3d/i', $source, $file.' still has the orange accent');
            self::assertDoesNotMatchRegularExpression('/<style\b|\sstyle\s*=/i', $source, $file.' has inline styles');
        }
    }
}
