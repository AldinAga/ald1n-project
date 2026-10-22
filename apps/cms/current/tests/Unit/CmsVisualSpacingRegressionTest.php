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
        $css = file_get_contents(__DIR__.'/../../public/assets/css/ald1n-liquid-glass.css');
        $refinement = file_get_contents(__DIR__.'/../../public/assets/css/ald1n-ui-refinement.css');
        $blade = file_get_contents(__DIR__.'/../../resources/views/catalog/show.blade.php');
        self::assertIsString($css);
        self::assertIsString($refinement);
        self::assertIsString($blade);
        self::assertStringContainsString('product-detail build16-product-detail-layout', $blade);
        self::assertStringContainsString('detail-panel build16-product-summary', $blade);
        self::assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*821px\)[\s\S]*?\.build16-product-detail-shell\s+\.product-detail\.build16-product-detail-layout\s*>\s*\.detail-panel\.build16-product-summary\s*\{[^}]*position:\s*static;[^}]*top:\s*auto;/',
            substr($css, strpos($css, 'CMS_VISUAL_SPACING_V1')),
        );
        self::assertMatchesRegularExpression('/@media\s*\(min-width:\s*821px\)[\s\S]*?\.product-detail\.build16-product-detail-layout\s*>\s*\.detail-panel\.build16-product-summary,[^{]+\{\s*align-self:\s*stretch;/', $refinement);
        self::assertStringContainsString('.build16-product-detail-shell .build16-product-summary .tags', $css);
        self::assertStringContainsString('.build16-product-detail-shell .build16-product-summary .build16-product-metrics small', $css);
        self::assertStringContainsString('.build16-product-detail-shell .build16-product-summary .build16-product-specifications :is(dt,dd)', $css);
        self::assertStringContainsString('@media (max-width: 580px)', $css);
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
