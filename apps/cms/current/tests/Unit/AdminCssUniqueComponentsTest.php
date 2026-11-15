<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * A component name must not be declared twice as a base selector in ald1n-admin.css (outside media, supports and
 * theme blocks): the second block silently restyles every user of the first (module 2 once reused .a1-legend).
 * Duplicates that already existed are listed; a new one fails. Author: Ald1n <https://ald1n.com>
 */
final class AdminCssUniqueComponentsTest extends TestCase
{
    /** Declared twice by module 1 (shell overrides of header, brand, menu, skip link); do not add to this list. */
    private const KNOWN = ['.a1-brand', '.a1-brand__text', '.a1-header', '.a1-header__bar', '.a1-menu-btn', '.a1-skip'];

    public function test_no_new_duplicate_base_component_selector(): void
    {
        $css = (string) file_get_contents(__DIR__.'/../../public/assets/css/ald1n-admin.css');
        $css = (string) preg_replace('~/\*.*?\*/~s', '', $css);

        $counts = [];
        $depth = 0;
        $buffer = '';
        for ($i = 0, $n = strlen($css); $i < $n; $i++) {
            $c = $css[$i];
            if ($c === '{') {
                if ($depth === 0 && !str_starts_with(trim($buffer), '@') && !str_starts_with(trim($buffer), ':root')) {
                    // Only a rule that is the component's own definition (one plain class), not a shared group list.
                    $selector = trim($buffer);
                    if (preg_match('/^\.a1-[a-z0-9_-]+$/', $selector) === 1) {
                        $counts[$selector] = ($counts[$selector] ?? 0) + 1;
                    }
                }
                $depth++;
                continue;
            }
            if ($c === '}') {
                $depth--;
                if ($depth === 0) {
                    $buffer = '';
                }
                continue;
            }
            if ($depth === 0) {
                $buffer .= $c;
            }
        }

        $duplicates = array_keys(array_filter($counts, static fn (int $count): bool => $count > 1));
        sort($duplicates);
        $fresh = array_values(array_diff($duplicates, self::KNOWN));
        self::assertSame([], $fresh, 'Duplicate base selector(s) in ald1n-admin.css: '.implode(', ', $fresh));
        self::assertGreaterThan(100, count($counts), 'The parser should have seen the component selectors.');
    }
}
