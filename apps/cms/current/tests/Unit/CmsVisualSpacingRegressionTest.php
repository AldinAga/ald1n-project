<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class CmsVisualSpacingRegressionTest extends TestCase
{
    // G7 PR B: the dashboard aurora (ald1n-liquid-glass.css) and the D1 KPI/priority spacing (ald1n-dashboard-d1.css)
    // tests asserted only rules of the two old stylesheets for classes the dashboard no longer renders
    // (dashboard-kpi-card, dashboard-d1-shell). They were removed; the Cobalt dashboard layout is covered by
    // DashboardD3PairedLayoutTest, DashboardModuleTwoTest and the admin-visual-qa browser run.

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
}
