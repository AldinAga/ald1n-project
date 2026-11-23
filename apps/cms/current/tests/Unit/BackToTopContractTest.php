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
            if (!str_contains($source, self::INCLUDE) || !str_contains($source, 'id="a1-top"')) {
                $missing[] = $name;
            }
        }
        self::assertSame([], $missing, 'Layout without the Nazad na vrh button or id="a1-top": '.implode(', ', $missing));
    }

    public function test_detector_flags_a_layout_without_the_button(): void
    {
        // Positive control, independent of the tree: the check above must be able to fail.
        $bare = '<body class="a1-body"><main></main></body>';
        self::assertFalse(str_contains($bare, self::INCLUDE));
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

    public function test_guest_and_error_layouts_load_the_tokens_and_component_css(): void
    {
        foreach (['layouts/guest.blade.php', 'errors/minimal.blade.php'] as $name) {
            $source = $this->layouts()[$name];
            self::assertStringContainsString('assets/css/ald1n-tokens.css', $source, $name);
            self::assertStringContainsString('assets/css/ald1n-admin.css', $source, $name);
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
