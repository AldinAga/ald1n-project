<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\DashboardTrendSummary;
use PHPUnit\Framework\TestCase;

/** Author: Ald1n <https://ald1n.com> */
final class DashboardTrendSummaryTest extends TestCase
{
    public function test_totals_average_per_calendar_day_best_bucket_and_scales(): void
    {
        $trend = [
            ['period' => '2026-01-10', 'revenue_rsd' => 10000.0, 'gross_profit_rsd' => 4000.0, 'commissions_rsd' => 2340.0, 'orders_count' => 1],
            ['period' => '2026-01-12', 'revenue_rsd' => 5000.0, 'gross_profit_rsd' => 0.0, 'commissions_rsd' => 1170.0, 'orders_count' => 3],
        ];

        $s = DashboardTrendSummary::build($trend, '2026-01-01', '2026-01-31');

        self::assertTrue($s['daily']);
        self::assertSame(31, $s['days']);
        self::assertSame(10000.0, $s['moneyScale']);
        self::assertSame(3.0, $s['ordersScale']);
        self::assertSame(15000.0, $s['series']['rev']['total']);
        self::assertSame(483.87, $s['series']['rev']['average_per_day']);
        self::assertSame('2026-01-10', $s['series']['rev']['best_period']);
        self::assertSame(3510.0, $s['series']['comm']['total']);
        self::assertSame(4.0, $s['series']['orders']['total']);
        self::assertSame('2026-01-12', $s['series']['orders']['best_period']);
        self::assertSame(50, DashboardTrendSummary::height(5000.0, $s['moneyScale']));
        self::assertSame(100, DashboardTrendSummary::height(3.0, $s['ordersScale']));
        self::assertSame(0, DashboardTrendSummary::height(0.0, 0.0));
    }

    public function test_long_periods_use_month_buckets_and_period_labels(): void
    {
        $s = DashboardTrendSummary::build([['period' => '2026-03', 'revenue_rsd' => 100.0, 'gross_profit_rsd' => 50.0, 'commissions_rsd' => 0.0, 'orders_count' => 1]], '2026-01-01', '2026-06-30');

        self::assertFalse($s['daily']);
        self::assertSame(181, $s['days']);
        self::assertSame('03.2026.', DashboardTrendSummary::periodText('2026-03'));
        self::assertSame('09.03.2026.', DashboardTrendSummary::periodText('2026-03-09'));
    }
}
