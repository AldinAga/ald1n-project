<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class DashboardD3PairedLayoutTest extends TestCase
{
    public function test_two_column_dashboard_only_activates_when_superadmin_has_brand_chart(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/dashboard/index.blade.php');
        $css = file_get_contents(__DIR__.'/../../public/assets/css/ald1n-dashboard-d1.css');
        self::assertIsString($view);
        self::assertIsString($css);

        self::assertStringContainsString(
            <<<'BLADE'
$user->hasRole('superadmin') && is_array($dashboardBrandMix) ? ' dashboard-main-grid--d3-pair' : ''
BLADE,
            $view,
        );
        self::assertSame(1, substr_count($view, 'dashboard-main-grid--d3-pair'));
        self::assertStringContainsString('data-dashboard-d3="1"', $view);
        self::assertStringContainsString("@unless(\$user->hasRole('superadmin'))", $view);

        self::assertMatchesRegularExpression(
            '/@media\s*\(min-width:\s*1181px\)[\s\S]*?\.build16-home-shell\.dashboard-d1-shell \.dashboard-main-grid--d3-pair\s*\{\s*grid-template-columns:\s*repeat\(2,\s*minmax\(0,\s*1fr\)\);/',
            $css,
        );
        self::assertMatchesRegularExpression(
            '/@media\s*\(max-width:\s*1180px\)[\s\S]*?\.dashboard-main-grid--d3-pair\s*\{\s*grid-template-columns:\s*minmax\(0,\s*1fr\);/',
            $css,
        );
    }

    public function test_financial_chart_explains_colors_and_missing_cost_caveat(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/dashboard/index.blade.php');
        $css = file_get_contents(__DIR__.'/../../public/assets/css/ald1n-dashboard-d1.css');
        self::assertIsString($view);
        self::assertIsString($css);

        self::assertStringContainsString('dashboard-d3-trend-legend', $view);
        self::assertStringContainsString('Legenda trenda prodaje', $view);
        self::assertStringContainsString('Plavo: prihod završenih porudžbina.', $view);
        self::assertStringContainsString('Zeleno: bruto dobit samo za stavke sa evidentiranom nabavnom cenom', $view);
        self::assertStringContainsString('pre provizija, povraćaja i troškova servisa', $view);
        self::assertStringContainsString('Tačne vrednosti po periodima', $view);
        self::assertStringContainsString('background: #287CFF;', $css);
        self::assertStringContainsString('.dashboard-d3-gross{background:#16CAA7}', $css);
    }
}
