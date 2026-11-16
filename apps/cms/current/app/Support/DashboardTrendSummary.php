<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;

/**
 * Presentation numbers for the dashboard trend chart: bar heights, totals, average per calendar
 * day and the best bucket for every series. Pure function of the report rows.
 * Author: Ald1n <https://ald1n.com>
 */
final class DashboardTrendSummary
{
    /** Series key (CSS/JS key) => report row column. The three money series share one scale, the order count has its own. */
    public const SERIES = [
        'rev' => ['column' => 'revenue_rsd', 'label' => 'Prihod', 'money' => true],
        'gross' => ['column' => 'gross_profit_rsd', 'label' => 'Bruto dobit', 'money' => true],
        'comm' => ['column' => 'commissions_rsd', 'label' => 'Provizija', 'money' => true],
        'orders' => ['column' => 'orders_count', 'label' => 'Broj porudžbina', 'money' => false],
    ];

    /**
     * @param list<array<string,mixed>> $trend rows of ManagementReportService::trend()
     * @return array{daily:bool,days:int,moneyScale:float,ordersScale:float,series:array<string,array<string,mixed>>}
     */
    public static function build(array $trend, string $dateFrom, string $dateTo): array
    {
        $days = max(1, (int) CarbonImmutable::parse($dateFrom)->diffInDays(CarbonImmutable::parse($dateTo)) + 1);
        // Same rule as ManagementReportService::trend(): up to 62 days apart the buckets are days, otherwise months.
        $daily = $days <= 63;

        $moneyScale = 1.0;
        $ordersScale = 1.0;
        foreach ($trend as $point) {
            foreach (self::SERIES as $meta) {
                $value = abs((float) ($point[$meta['column']] ?? 0));
                if ($meta['money']) {
                    $moneyScale = max($moneyScale, $value);
                } else {
                    $ordersScale = max($ordersScale, $value);
                }
            }
        }

        $series = [];
        foreach (self::SERIES as $key => $meta) {
            $total = 0.0;
            $best = null;
            $bestValue = null;
            foreach ($trend as $point) {
                $value = (float) ($point[$meta['column']] ?? 0);
                $total += $value;
                if ($bestValue === null || $value > $bestValue) {
                    $bestValue = $value;
                    $best = (string) $point['period'];
                }
            }
            $series[$key] = [
                'label' => $meta['label'],
                'money' => $meta['money'],
                'total' => round($total, 2),
                'average_per_day' => round($total / $days, 2),
                'best_period' => $best,
                'best_value' => $bestValue === null ? 0.0 : round($bestValue, 2),
            ];
        }

        return ['daily' => $daily, 'days' => $days, 'moneyScale' => $moneyScale, 'ordersScale' => $ordersScale, 'series' => $series];
    }

    /** Bar height in percent of the scale (0-100). */
    public static function height(float $value, float $scale): int
    {
        return (int) max(0, min(100, round(abs($value) / max(1.0, $scale) * 100)));
    }

    /**
     * SVG polyline points (viewBox 0 0 $w $h) for a small trend line; a flat middle line when there is one value or no range.
     *
     * @param list<float> $values
     */
    public static function sparkline(array $values, int $w = 96, int $h = 28): string
    {
        $n = count($values);
        if ($n === 0) {
            return '';
        }
        $min = min($values);
        $max = max($values);
        $points = [];
        foreach ($values as $i => $v) {
            $x = $n === 1 ? $w / 2 : $i / ($n - 1) * $w;
            $y = $max - $min < 0.000001 ? $h / 2 : $h - 2 - (($v - $min) / ($max - $min)) * ($h - 4);
            $points[] = number_format($x, 1, '.', '').','.number_format($y, 1, '.', '');
        }
        if ($n === 1) {
            // One bucket: a flat segment across the box instead of a single invisible point.
            $y = explode(',', $points[0])[1];
            $points = ['0.0,'.$y, number_format($w, 1, '.', '').','.$y];
        }

        return implode(' ', $points);
    }

    /** dd.mm.yyyy. for a daily bucket, mm.yyyy. for a monthly one. */
    public static function periodText(string $period): string
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $period, $m) === 1) {
            return $m[3].'.'.$m[2].'.'.$m[1].'.';
        }
        if (preg_match('/^(\d{4})-(\d{2})$/', $period, $m) === 1) {
            return $m[2].'.'.$m[1].'.';
        }

        return $period;
    }
}
