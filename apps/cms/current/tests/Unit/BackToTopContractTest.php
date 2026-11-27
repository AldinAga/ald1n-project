<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * CI gate: every full-page layout of the CMS (layouts.*, errors.minimal) must carry the "Nazad na vrh" button.
 * The public shop layout keeps its own .shop-back-to-top and must not get a second one.
 * A new file in resources/views/layouts is picked up automatically. Author: Ald1n <https://ald1n.com>
 */
final class BackToTopContractTest extends TestCase
{
    private const INCLUDE = "@include('layouts.partials.back-to-top')";

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    /** @return array<string,string> relative path => source */
    private function layouts(): array
    {
        $out = [];
        foreach (glob($this->root().'/resources/views/layouts/*.blade.php') ?: [] as $file) {
            $out['layouts/'.basename($file)] = (string) file_get_contents($file);
        }
        $out['errors/minimal.blade.php'] = (string) file_get_contents($this->root().'/resources/views/errors/minimal.blade.php');

        return $out;
    }

    public function test_every_layout_includes_the_button_and_the_anchor(): void
    {
        $layouts = $this->layouts();
        // Floor: app, guest and errors.minimal exist today; an empty glob must not pass.
        self::assertGreaterThanOrEqual(3, count($layouts));

        $missing = [];
        foreach ($layouts as $name => $source) {
            if (self::lacksButton($source)) {
                $missing[] = $name;
            }
        }
        self::assertSame([], $missing, 'Layout without the Nazad na vrh button or id="a1-top": '.implode(', ', $missing));
    }

    private static function lacksButton(string $source): bool
    {
        return !str_contains($source, self::INCLUDE) || !str_contains($source, 'id="a1-top"');
    }

    public function test_detector_flags_a_layout_without_the_button(): void
    {
        // Positive control: the very check the main test uses must flag a bare layout and accept a complete one.
        self::assertTrue(self::lacksButton('<body class="a1-body"><main></main></body>'));
        self::assertTrue(self::lacksButton('<body id="a1-top"><main></main></body>'));
        self::assertFalse(self::lacksButton('<body id="a1-top">'.self::INCLUDE.'</body>'));
    }

    /** @return list<list<string>> for every rule that makes something fixed or sticky at the bottom: the classes in its selector */
    private static function bottomBarRules(string $css): array
    {
        $found = [];
        $css = (string) preg_replace('~/\*.*?\*/~s', '', $css);
        preg_match_all('/([^{}]+)\{([^{}]*)\}/', $css, $rules, PREG_SET_ORDER);
        foreach ($rules as [, $selector, $body]) {
            // bottom:, inset-block-end:, or an inset shorthand that is not the full-cover "inset:0" of a scrim.
            $atBottom = preg_match('/(^|[;\s])(bottom|inset-block-end)\s*:/', $body) === 1
                || preg_match('/(^|[;\s])inset\s*:\s*(?!0(px)?\s*(;|$))/', $body) === 1;
            if (preg_match('/position\s*:\s*(sticky|fixed)/', $body) !== 1 || !$atBottom) {
                continue;
            }
            preg_match_all('/\.([a-z][a-z0-9_-]*)/i', $selector, $classes);
            $found[] = array_values(array_unique($classes[1]));
        }

        return $found;
    }

    public function test_every_fixed_or_sticky_bottom_bar_is_known_to_the_button(): void
    {
        $css = '';
        $files = array_merge(
            glob($this->root().'/public/assets/css/*.css') ?: [],
            glob($this->root().'/public/assets/css/*/*.css') ?: []
        );
        // Floor: the CMS ships well over a dozen stylesheets; an empty glob must not pass.
        self::assertGreaterThanOrEqual(10, count($files));
        foreach ($files as $file) {
            $css .= (string) file_get_contents($file)."\n";
        }
        $partial = (string) file_get_contents($this->root().'/resources/views/layouts/partials/back-to-top.blade.php');
        preg_match("/const bars = '([^']+)'/", $partial, $match);
        $known = array_map(static fn (string $s): string => ltrim($s, '.'), explode(',', $match[1] ?? ''));
        // Not bars: the button itself, the public shop's own button, and the toast stack (above the button by design, short-lived).
        $ignored = ['a1-btt', 'a1-toasts', 'shop-back-to-top'];

        $rules = array_values(array_filter(
            self::bottomBarRules($css),
            static fn (array $classes): bool => array_intersect($classes, $ignored) === []
        ));
        // G7-D removed the legacy rules, leaving the Cobalt action bar and phone dock.
        self::assertGreaterThanOrEqual(2, count($rules));

        $unknown = [];
        foreach ($rules as $classes) {
            if (array_intersect($classes, $known) === []) {
                $unknown[] = implode(' ', $classes);
            }
        }
        self::assertSame([], $unknown, 'Fixed/sticky bottom bar not listed in back-to-top.blade.php: '.implode(' | ', $unknown));

        // Positive control: the parser must report a new bar that nothing lists.
        $control = self::bottomBarRules('.zz-new-bar{position:sticky;bottom:0}');
        self::assertSame([['zz-new-bar']], $control);
        self::assertSame([['zz-inset-bar']], self::bottomBarRules('.zz-inset-bar{position:fixed;inset:auto 0 8px 0}'));
        self::assertSame([], self::bottomBarRules('.zz-scrim{position:fixed;inset:0}'));
        self::assertSame([], array_intersect($control[0], $known));
    }

    public function test_partial_is_accessible_and_scripted_under_the_csp_nonce(): void
    {
        $partial = (string) file_get_contents($this->root().'/resources/views/layouts/partials/back-to-top.blade.php');
        foreach (['aria-label="Nazad na vrh"', 'href="#a1-top"', 'class="a1-btt"', "_csp_nonce", 'prefers-reduced-motion', 'window.innerHeight'] as $needle) {
            self::assertStringContainsString($needle, $partial);
        }
    }

    public function test_css_has_focus_safe_area_reduced_motion_and_forced_colors(): void
    {
        $css = (string) file_get_contents($this->root().'/public/assets/css/ald1n-admin.css');
        foreach ([
            '.a1-btt{', '.a1-btt:focus-visible', 'env(safe-area-inset-bottom', 'env(safe-area-inset-right',
            'var(--a1-back-to-top-bg)', 'var(--a1-back-to-top-hover)', 'prefers-reduced-motion:no-preference',
        ] as $needle) {
            self::assertStringContainsString($needle, $css);
        }
        self::assertMatchesRegularExpression('/@media \(forced-colors:active\)\{\.a1-btt\{/', $css);
    }

    public function test_layouts_load_cobalt_css_in_order_without_legacy_stylesheets(): void
    {
        $layouts = $this->layouts();
        foreach ([
            'layouts/app.blade.php' => "@stack('styles')",
            'layouts/guest.blade.php' => "@stack('styles')",
            'errors/minimal.blade.php' => 'assets/css/ald1n-auth.css',
        ] as $name => $pageStyles) {
            $source = $layouts[$name];
            $previousOffset = -1;
            foreach (['assets/css/ald1n-tokens.css', 'assets/css/ald1n-admin.css', $pageStyles] as $marker) {
                $offset = strpos($source, $marker);
                self::assertNotFalse($offset, $name.' is missing '.$marker);
                self::assertGreaterThan($previousOffset, $offset, $name.' loads '.$marker.' out of order');
                $previousOffset = $offset;
            }

            foreach (['app.css', 'ald1n-ui-v2.css', 'ald1n-liquid-glass.css', 'ald1n-ui-refinement.css', 'vendor/liquid-glass-web.css', 'ald1n-dashboard-d1.css'] as $legacy) {
                self::assertStringNotContainsString('assets/css/'.$legacy, $source, $name);
            }
        }
    }

    public function test_public_shop_keeps_its_own_button_and_gets_no_second_one(): void
    {
        $shop = (string) file_get_contents($this->root().'/resources/views/public-shop/layout.blade.php');
        self::assertStringContainsString('shop-back-to-top', $shop);
        self::assertStringNotContainsString('back-to-top\')', $shop);
        self::assertStringNotContainsString('a1-btt', $shop);
    }
}
