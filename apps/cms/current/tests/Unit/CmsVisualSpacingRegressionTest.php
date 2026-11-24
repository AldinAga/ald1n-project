<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CmsVisualSpacingRegressionTest extends TestCase
{
    public function test_dashboard_aurora_background_has_no_clipped_container_paint(): void
    {
        $css = file_get_contents(__DIR__.'/../../public/assets/css/ald1n-liquid-glass.css');
        self::assertIsString($css);
        $marker = strpos($css, 'CMS_VISUAL_SPACING_V1');
        $viewportPaint = strpos($css, 'Dashboard aurora full-bleed');
        self::assertNotFalse($marker);
        self::assertNotFalse($viewportPaint);
        self::assertGreaterThan($viewportPaint, $marker);
        $tail = substr($css, $marker);
        self::assertMatchesRegularExpression(
            '/html\[data-theme="dark"\]\s+\.page-shell:has\(\.dashboard-kpi-card\),\s*html\[data-theme="light"\]\s+\.page-shell:has\(\.dashboard-kpi-card\)\s*\{\s*background:\s*transparent;/',
            $tail,
        );
        self::assertMatchesRegularExpression('/body:has\(\.page-shell \.dashboard-kpi-card\)\s*\{[^}]*background:[^}]*radial-gradient/s', $css);
        self::assertMatchesRegularExpression('/\.page-shell:has\(\.dashboard-kpi-card\)::before\s*\{\s*content:\s*none;/', $css);
    }

    public function test_build16_product_info_starts_with_gallery_and_has_readable_spacing(): void
    {
        // Kobalt G7: the detail layout lives in ald1n-admin-catalog.css under a1-cat-* names.
        $css = file_get_contents(__DIR__.'/../../public/assets/css/ald1n-admin-catalog.css');
        $blade = file_get_contents(__DIR__.'/../../resources/views/catalog/show.blade.php');
        self::assertIsString($css);
        self::assertIsString($blade);
        self::assertStringContainsString('class="a1-cat-layout ', $blade);
        self::assertStringContainsString('class="a1-card a1-cat-detail"', $blade);
        // Gallery and summary start in the same grid row and the summary is not sticky-offset on desktop.
        self::assertMatchesRegularExpression('/\.a1-cat-layout\{[^}]*align-items:start;/', $css);
        self::assertDoesNotMatchRegularExpression('/\.a1-cat-detail\{[^}]*position:\s*(sticky|fixed)/', $css);
        self::assertMatchesRegularExpression('/@media \(min-width:821px\)\{[^@]*\.a1-cat-layout\{align-items:stretch\}/', $css);
        self::assertStringContainsString('@media (max-width:580px)', $css);
    }

    public function test_only_d1_kpis_are_equal_height_and_single_priority_spans_full_row(): void
    {
        $css = file_get_contents(__DIR__.'/../../public/assets/css/ald1n-dashboard-d1.css');
        self::assertIsString($css);
        self::assertStringContainsString('CMS_VISUAL_SPACING_V1', $css);
        self::assertMatchesRegularExpression(
            '/\.dashboard-d1-shell\s+\.dashboard-kpi-grid--d1\s*>\s*\.dashboard-kpi-card\s*\{[^}]*height:\s*100%;[^}]*min-height:\s*158px;/',
            $css,
        );
        self::assertMatchesRegularExpression(
            '/\.dashboard-d1-shell\s+\.dashboard-d1-priorities\s+\.dashboard-priority-list\s*>\s*\.dashboard-priority-item:only-child\s*\{\s*grid-column:\s*1\s*\/\s*-1;/',
            $css,
        );
        self::assertStringContainsString('@media (max-width: 590px)', $css);
    }
}
