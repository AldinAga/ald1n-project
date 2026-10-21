<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DashboardFullBleedAuroraTest extends TestCase
{
    public function test_dashboard_aurora_uses_viewport_width_without_affecting_content_grid(): void
    {
        $css = file_get_contents(__DIR__.'/../../public/assets/css/ald1n-liquid-glass.css');
        self::assertIsString($css);
        self::assertStringContainsString('Dashboard aurora full-bleed', $css);
        self::assertStringContainsString('body:has(.page-shell .dashboard-kpi-card)', $css);
        self::assertStringContainsString('width: 100vw;', $css);
        self::assertStringContainsString('transform: translateX(-50%);', $css);
        self::assertStringContainsString('pointer-events: none;', $css);
        self::assertStringContainsString('filter: none;', $css);
        self::assertStringContainsString('(prefers-reduced-transparency: reduce)', $css);
        self::assertStringContainsString('.page-shell:has(.dashboard-kpi-card) > *', $css);
    }
}
