<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * G7 PR B: this contract used to read ald1n-liquid-glass.css. The old palette, ambient, header and editor-bar rules are
 * gone with that stylesheet (their Cobalt equivalents are a1-* in ald1n-admin.css and are covered by the Cobalt contract
 * tests). What stays here is every accessibility and responsive guarantee, now asserted against ald1n-admin.css.
 */
final class LiquidGlassFoundationContractTest extends TestCase
{
    private function css(): string
    {
        $path = dirname(__DIR__, 2).'/public/assets/css/ald1n-admin.css';
        self::assertFileExists($path);
        $value = file_get_contents($path);
        self::assertIsString($value);
        return $value;
    }

    public function test_glass_surfaces_have_opaque_fallback(): void
    {
        $css = $this->css();
        self::assertStringContainsString('@supports not', $css);
        self::assertStringContainsString('backdrop-filter', $css);
        self::assertStringNotContainsString('z-index: 9999', $css);
        self::assertStringNotContainsString('z-index:9999', $css);
    }

    public function test_preserves_navigation_and_control_markers(): void
    {
        $path = dirname(__DIR__, 2).'/resources/views/layouts/app.blade.php';
        self::assertFileExists($path);
        $layout = file_get_contents($path);
        self::assertIsString($layout);
        foreach ([
            'data-mobile-menu-toggle', 'data-theme-mode-option',
            'data-header-product-search-toggle', 'data-exchange-rate-sync',
            '<dialog', 'aria-expanded="false"', 'aria-label="Glavna navigacija"',
        ] as $marker) {
            self::assertStringContainsString($marker, $layout);
        }
    }

    public function test_common_controls_have_focus_treatment(): void
    {
        $css = $this->css();
        self::assertStringContainsString('.a1-app :focus-visible{', $css);
        foreach (['.a1-btn', '.a1-input', '.a1-select', '.a1-textarea', '.a1-tablewrap'] as $selector) {
            self::assertStringContainsString($selector, $css);
        }
    }

    public function test_accessibility_fallbacks_exist(): void
    {
        $css = $this->css();
        foreach ([
            '@media (prefers-reduced-motion:no-preference)',
            '(prefers-reduced-transparency',
            '(forced-colors',
            '@supports not',
        ] as $required) {
            self::assertStringContainsString($required, $css);
        }
    }

    public function test_order_archive_purge_card_and_safe_area_are_scoped(): void
    {
        $css = $this->css();
        self::assertStringContainsString('.a1-ol-purge{', $css);
        self::assertStringContainsString('.a1-ol-purge__title .a1-text{overflow-wrap:break-word}', $css);
        self::assertStringContainsString('env(safe-area-inset-bottom', $css);
    }

    public function test_small_mobile_touch_targets_are_at_least_44px(): void
    {
        $css = $this->css();
        self::assertStringContainsString('min-height:44px', $css);
        self::assertStringContainsString('.a1-btn--sm{min-height:var(--a1-size-touch-target)', $css);
    }

    public function test_product_editor_native_sticky_bar_blocks_duplicate_mobile_ux_dock(): void
    {
        $root = dirname(__DIR__, 2);
        $js = file_get_contents($root.'/public/assets/js/ux-runtime.js');
        $view = file_get_contents($root.'/resources/views/admin/products/form.blade.php');
        self::assertIsString($js);
        self::assertIsString($view);
        self::assertStringContainsString('const createMobileActionDock = () => {', $js);
        self::assertStringContainsString("!candidate.querySelector('[data-product-editor-action-bar]')", $js);
        self::assertStringContainsString('data-ux-sticky-actions', $view);
        self::assertStringContainsString('data-product-editor-action-bar', $view);
        self::assertStringContainsString('protectUnsavedChanges();', $js);
        self::assertStringContainsString('addKeyboardSave();', $js);
    }

    public function test_mobile_has_no_global_overflow_hidden_policy(): void
    {
        $css = $this->css();
        self::assertStringNotContainsString('body { overflow: hidden', $css);
        self::assertStringNotContainsString('body{overflow:hidden', $css);
        self::assertStringContainsString('overflow-wrap:break-word', $css);
    }
}
