<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DashboardFullBleedAuroraTest extends TestCase
{
    public function test_dashboard_aurora_uses_body_paint_without_overflowing_content_grid(): void
    {
        $css = file_get_contents(__DIR__.'/../../public/assets/css/ald1n-liquid-glass.css');
        self::assertIsString($css);
        self::assertStringContainsString('Dashboard aurora full-bleed', $css);
        self::assertMatchesRegularExpression('/body:has\(\.page-shell \.dashboard-kpi-card\)\s*\{[^}]*background:[^}]*radial-gradient/s', $css);
        self::assertStringContainsString('background-attachment: fixed;', $css);
        // UI794's headed browser RED proved the centered 100vw layer adds scrollbar width.
        $start = strpos($css, '/* UI794: body already paints');
        self::assertNotFalse($start);
        $paint = substr($css, $start, strpos($css, '/* Header and navigation', $start) - $start);
        self::assertMatchesRegularExpression('/\.page-shell:has\(\.dashboard-kpi-card\)::before\s*\{\s*content:\s*none;/', $paint);
        self::assertDoesNotMatchRegularExpression('/width\s*:\s*100vw|translateX\(-50%\)/', $paint);
        self::assertStringContainsString('(prefers-reduced-transparency: reduce)', $paint);
        self::assertStringContainsString('(forced-colors: active)', $paint);
        self::assertStringContainsString('.page-shell:has(.dashboard-kpi-card) > *', $css);
    }
}
