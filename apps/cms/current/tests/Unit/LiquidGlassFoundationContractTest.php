<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class LiquidGlassFoundationContractTest extends TestCase
{
    private function css(): string
    {
        $path = dirname(__DIR__, 2).'/public/assets/css/ald1n-liquid-glass.css';
        self::assertFileExists($path);
        $value = file_get_contents($path);
        self::assertIsString($value);
        return $value;
    }

    public function test_dark_and_light_muted_token_palette_is_defined(): void
    {
        $css = $this->css();
        foreach ([
            'html[data-theme="dark"]', 'html[data-theme="light"]',
            '--ald-background: #13181C;', '--ald-background: #F3F5F6;',
            '--ald-surface: #1B2329;', '--ald-surface: #FCFDFE;',
            '--ald-text: #E9EEF1;', '--ald-text: #24313A;',
            '--ald-primary: #BFA590;', '--ald-primary: #68584D;',
        ] as $required) {
            self::assertStringContainsString($required, $css);
        }
    }

    public function test_no_semantic_danger_or_success_token_is_repurposed(): void
    {
        $css = $this->css();
        self::assertStringNotContainsString('--ald-danger:', $css);
        self::assertStringNotContainsString('--ald-success:', $css);
        self::assertStringNotContainsString('--ald-warning:', $css);
    }

    public function test_ambient_is_low_chroma_and_non_animated(): void
    {
        $css = $this->css();
        foreach (['cool', 'warm'] as $name) {
            $pattern = '/--lg-ambient-'.$name.'\s*:\s*rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*(0(?:\.\d+)?)\s*\)/';
            self::assertMatchesRegularExpression($pattern, $css);
            preg_match($pattern, $css, $matches);
            self::assertLessThanOrEqual(0.06, (float) $matches[1]);
        }
        self::assertStringNotContainsString('@keyframes', $css);
    }

    public function test_shell_targets_existing_header_nav_and_search_selectors(): void
    {
        $css = $this->css();
        foreach ([
            '.site-header', '.header-primary-row', '.header-secondary-row',
            '.nav-dropdown-menu', '.header-product-search-dialog',
            '.user-chip', '.rate-badge', '.page-shell',
        ] as $selector) {
            self::assertStringContainsString($selector, $css);
        }
    }

    public function test_glass_surfaces_have_opaque_fallback(): void
    {
        $css = $this->css();
        self::assertStringContainsString('@supports not', $css);
        self::assertStringContainsString('backdrop-filter', $css);
        self::assertStringContainsString('background: var(--ald-surface);', $css);
        self::assertStringNotContainsString('z-index: 9999', $css);
    }

    public function test_preserves_navigation_and_control_markers(): void
    {
        $path = dirname(__DIR__, 2).'/resources/views/layouts/app.blade.php';
        self::assertFileExists($path);
        $layout = file_get_contents($path);
        self::assertIsString($layout);
        foreach ([
            'data-mobile-menu-toggle', 'data-theme-toggle',
            'data-header-product-search-toggle', 'data-exchange-rate-sync',
            'role="dialog"',
        ] as $marker) {
            self::assertStringContainsString($marker, $layout);
        }
    }

    public function test_common_controls_and_table_surfaces_have_focus_treatment(): void
    {
        $css = $this->css();
        foreach ([
            '.button', '.button-primary', '.button-secondary', '.button-ghost',
            '.icon-button', 'input', 'textarea', 'select',
            '.admin-table-wrap', ':focus-visible',
        ] as $selector) {
            self::assertStringContainsString($selector, $css);
        }
    }

    public function test_accessibility_fallbacks_exist(): void
    {
        $css = $this->css();
        foreach ([
            '@media (prefers-reduced-motion: reduce)',
            '@media (prefers-reduced-transparency: reduce)',
            '@media (forced-colors: active)',
            '@supports not',
        ] as $required) {
            self::assertStringContainsString($required, $css);
        }
    }

    public function test_header_never_hides_rate_or_cart_at_tablet_and_phone_breakpoints(): void
    {
        $css = $this->css();
        self::assertStringContainsString('@media (max-width: 1250px)', $css);
        self::assertStringContainsString('.header-rate-persistent', $css);
        self::assertStringContainsString('.header-cart-button', $css);
        self::assertStringContainsString('display: inline-flex !important;', $css);
        self::assertStringContainsString('display: inline-grid !important;', $css);
        self::assertStringContainsString('@media (max-width: 390px)', $css);
    }

    public function test_order_archive_dialog_surfaces_and_accessible_responsive_controls_are_scoped(): void
    {
        $css = $this->css();
        self::assertStringContainsString('.order-archive-purge-dialog', $css);
        self::assertStringContainsString('.order-archive-purge-dialog::backdrop', $css);
        self::assertStringContainsString('.order-archive-purge-fields', $css);
        self::assertStringContainsString('.order-archive-purge-footer', $css);
        self::assertStringContainsString('env(safe-area-inset-bottom)', $css);
    }

    public function test_small_mobile_header_and_modal_close_preserve_44px_touch_targets(): void
    {
        $css = $this->css();
        self::assertStringContainsString('flex-basis: 44px;', $css);
        self::assertStringContainsString('flex: 0 0 44px;', $css);
        self::assertStringContainsString('min-height: 44px;', $css);
    }

    public function test_authenticated_search_is_persistent_and_no_disabled_cart_visual_remains(): void
    {
        $css = $this->css();
        self::assertDoesNotMatchRegularExpression('/\.header-cart-unavailable/', $css);
        self::assertMatchesRegularExpression(
            '/\.header-quick-actions\s*>\s*\.header-product-search-toggle\s*\{[^}]*display:\s*inline-grid\s*!important;/s',
            $css
        );
    }

    public function test_mobile_has_no_global_horizontal_or_sticky_overflow_policy(): void
    {
        $css = $this->css();
        self::assertStringContainsString('@media (max-width: 760px)', $css);
        self::assertStringContainsString('min-width: 0;', $css);
        self::assertStringNotContainsString('body { overflow: hidden', $css);
        self::assertStringNotContainsString('.product-editor-action-bar', $css);
        // Batch 2 owns only the isolated archive modal; the editor remains Batch 3.
        self::assertStringContainsString('.order-archive-purge-dialog:not([open])', $css);
    }
}
