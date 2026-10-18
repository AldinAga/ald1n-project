<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\DashboardPeriod;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class DashboardPeriodTest extends TestCase
{
    private function today(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-10-18', 'Europe/Belgrade');
    }

    public function test_default_and_thirty_days_include_today(): void
    {
        $expected = ['period' => '30d', 'date_from' => '2026-09-19', 'date_to' => '2026-10-18', 'error' => null];
        self::assertSame($expected, DashboardPeriod::resolve([], $this->today()));
        self::assertSame($expected, DashboardPeriod::resolve(['period' => '30d'], $this->today()));
    }

    public function test_seven_day_preset_is_inclusive(): void
    {
        self::assertSame(
            ['period' => '7d', 'date_from' => '2026-10-12', 'date_to' => '2026-10-18', 'error' => null],
            DashboardPeriod::resolve(['period' => '7d'], $this->today()),
        );
    }

    public function test_custom_range_keeps_both_inclusive_endpoints(): void
    {
        self::assertSame(
            ['period' => 'custom', 'date_from' => '2026-09-01', 'date_to' => '2026-09-30', 'error' => null],
            DashboardPeriod::resolve(['period' => 'custom', 'date_from' => '2026-09-01', 'date_to' => '2026-09-30'], $this->today()),
        );
    }

    public function test_invalid_query_parameters_do_not_silently_become_valid_periods(): void
    {
        foreach ([
            ['period' => 'foo'],
            ['period' => ['30d']],
            ['period' => 'custom', 'date_from' => '2026-02-30', 'date_to' => '2026-03-01'],
            ['period' => 'custom', 'date_from' => '2026-10-19', 'date_to' => '2026-10-18'],
            ['period' => 'custom', 'date_from' => ['2026-09-01'], 'date_to' => '2026-09-30'],
            ['period' => 'custom', 'date_from' => '2026-09-01'],
            ['period' => 'custom', 'date_from' => '', 'date_to' => '2026-09-30'],
        ] as $query) {
            $result = DashboardPeriod::resolve($query, $this->today());
            self::assertNotEmpty($result['error'], json_encode($query));
            self::assertSame('2026-10-18', $result['date_to']);
            self::assertSame('2026-09-19', $result['date_from']);
        }
    }

    public function test_preset_ignores_unrelated_calendar_fields(): void
    {
        $result = DashboardPeriod::resolve([
            'period' => '7d', 'date_from' => 'not-a-date', 'date_to' => ['invalid'],
        ], $this->today());
        self::assertSame('7d', $result['period']);
        self::assertNull($result['error']);
        self::assertSame('2026-10-12', $result['date_from']);
    }
}
