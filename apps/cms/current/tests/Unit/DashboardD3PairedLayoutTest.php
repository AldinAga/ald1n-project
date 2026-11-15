<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Module 2 (admin dashboard on Kobalt components). The brand chart and the trend chart are
 * paired on the 12-column grid; the chart colours come from tokens, not from hex literals.
 */
final class DashboardD3PairedLayoutTest extends TestCase
{
    private function moduleTwoCss(): string
    {
        $css = file_get_contents(__DIR__.'/../../public/assets/css/ald1n-admin.css');
        self::assertIsString($css);
        $start = strpos($css, 'Module 2: admin dashboard');
        self::assertNotFalse($start);

        return substr($css, $start);
    }

    public function test_pair_only_activates_when_superadmin_has_brand_chart(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/dashboard/index.blade.php');
        self::assertIsString($view);
        $css = $this->moduleTwoCss();

        self::assertStringContainsString('$showBrandMix = $isSuper && is_array($dashboardBrandMix);', $view);
        self::assertStringContainsString('data-dashboard-d3="1"', $view);
        self::assertSame(1, substr_count($view, 'a1-card a1-lg-5" data-dashboard-d3="1"'));
        self::assertStringContainsString('a1-card @if($showBrandMix) a1-lg-7 @endif', $view);
        self::assertStringContainsString("\$isSuper = \$user->hasRole('superadmin');", $view);

        self::assertMatchesRegularExpression('/\.a1-g12\{display:grid;grid-template-columns:repeat\(12,minmax\(0,1fr\)\)/', $css);
        self::assertMatchesRegularExpression('/\.a1-g12>\*\{grid-column:1\/-1;/', $css);
        self::assertMatchesRegularExpression('/@media \(min-width:1100px\)\{[^@]*\.a1-g12>\.a1-lg-5\{grid-column:span 5\}[^@]*\.a1-g12>\.a1-lg-7\{grid-column:span 7\}/', $css);
    }

    public function test_financial_chart_explains_marks_and_missing_cost_caveat(): void
    {
        $view = file_get_contents(__DIR__.'/../../resources/views/dashboard/index.blade.php');
        self::assertIsString($view);
        $css = $this->moduleTwoCss();

        self::assertStringContainsString('aria-label="Legenda trenda prodaje"', $view);
        self::assertStringContainsString('Puna traka: prihod završenih porudžbina.', $view);
        self::assertStringContainsString('Šrafirana traka: bruto dobit samo za stavke sa evidentiranom nabavnom cenom', $view);
        self::assertStringContainsString('pre provizija, povraćaja i troškova servisa', $view);
        self::assertStringContainsString('Tačne vrednosti po periodima', $view);
        // The trend is aria-hidden; its text alternative is the sentence plus the exact-values table.
        self::assertStringContainsString('<ol class="a1-trend" aria-hidden="true"', $view);
        self::assertStringContainsString('Tačne vrednosti su u tabeli ispod grafikona.', $view);

        self::assertStringContainsString('.a1-trend__bar--rev{background-color:var(--a1-button-primary-bg)', $css);
        self::assertStringContainsString('var(--a1-status-ok-dot)', $css);
        self::assertSame(0, preg_match('/#[0-9a-fA-F]{3,8}\b/', $css), 'Module 2 CSS must use tokens only, no hex colours.');
    }
}
