<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ManagementReportService;
use PHPUnit\Framework\TestCase;

final class DashboardBrandMixTest extends TestCase
{
    public function test_revenue_and_units_rank_differently_and_others_preserve_full_denominators(): void
    {
        $rows = [
            (object) ['label' => 'A', 'revenue' => 1000, 'units' => 1],
            (object) ['label' => 'B', 'revenue' => 900, 'units' => 2],
            (object) ['label' => 'C', 'revenue' => 800, 'units' => 3],
            (object) ['label' => 'D', 'revenue' => 700, 'units' => 4],
            (object) ['label' => 'E', 'revenue' => 600, 'units' => 5],
            (object) ['label' => 'F', 'revenue' => 100, 'units' => 50],
            (object) ['label' => 'Bez podatka', 'revenue' => 50, 'units' => 6],
        ];

        $mix = ManagementReportService::summarizeDashboardBrandRows($rows);

        self::assertSame(4150.0, $mix['revenue']['total']);
        self::assertSame(71.0, $mix['units']['total']);
        self::assertSame(['A', 'B', 'C', 'D', 'E', 'Ostali'], array_column($mix['revenue']['slices'], 'label'));
        self::assertSame(['F', 'Bez podatka', 'E', 'D', 'C', 'Ostali'], array_column($mix['units']['slices'], 'label'));
        self::assertSame(150.0, $mix['revenue']['slices'][5]['value']);
        self::assertSame(3.0, $mix['units']['slices'][5]['value']);
        self::assertStringContainsString('100.000000%', $mix['revenue']['gradient']);
    }

    public function test_zero_or_negative_totals_never_create_fictional_pie_slices(): void
    {
        $mix = ManagementReportService::summarizeDashboardBrandRows([
            (object) ['label' => 'A', 'revenue' => 0, 'units' => 0],
            (object) ['label' => 'B', 'revenue' => -15, 'units' => -2],
        ]);

        self::assertSame([], $mix['revenue']['slices']);
        self::assertSame([], $mix['units']['slices']);
    }

    public function test_more_than_thirty_brands_still_count_toward_others(): void
    {
        $rows = [];
        for ($i = 1; $i <= 40; ++$i) {
            $rows[] = (object) ['label' => 'Brand'.$i, 'revenue' => 100, 'units' => 1];
        }
        $mix = ManagementReportService::summarizeDashboardBrandRows($rows);
        self::assertSame(4000.0, $mix['revenue']['total']);
        self::assertSame(3500.0, $mix['revenue']['slices'][5]['value']);
        self::assertSame(35.0, $mix['units']['slices'][5]['value']);
    }
}
