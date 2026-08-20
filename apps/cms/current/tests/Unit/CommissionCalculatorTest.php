<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CommissionCalculator;
use PHPUnit\Framework\TestCase;

final class CommissionCalculatorTest extends TestCase
{
    public function test_automatic_commission_is_ten_percent_without_fixed_twenty_euro_floor(): void
    {
        $calculator = new CommissionCalculator();

        self::assertSame(2.0, $calculator->unitEur(20.0, 'EUR', null, null));
        self::assertSame(5.0, $calculator->unitEur(50.0, 'EUR', null, null));
        self::assertSame(10.0, $calculator->unitEur(100.0, 'EUR', null, null));
        self::assertSame(2.0, $calculator->unitEur(2000.0, 'RSD', null, 100.0));
        self::assertSame(0.0, $calculator->unitEur(2000.0, 'RSD', null, null));
    }

    public function test_existing_automatic_fifty_euro_maximum_is_preserved(): void
    {
        $calculator = new CommissionCalculator();

        self::assertSame(50.0, $calculator->unitEur(500.0, 'EUR', null, null));
        self::assertSame(50.0, $calculator->unitEur(1000.0, 'EUR', null, null));
    }

    public function test_manual_commission_requires_uncapped_ten_percent_floor(): void
    {
        $calculator = new CommissionCalculator();

        self::assertTrue($calculator->usesManual(20.0, 'EUR', 2.0, null));
        self::assertFalse($calculator->usesManual(20.0, 'EUR', 1.99, null));
        self::assertSame(2.0, $calculator->unitEur(20.0, 'EUR', 1.99, null));
        self::assertSame(60.0, $calculator->unitEur(100.0, 'EUR', 60.0, null));

        self::assertSame(100.0, $calculator->manualMinimumEur(1000.0, 'EUR', null));
        self::assertFalse($calculator->usesManual(1000.0, 'EUR', 99.99, null));
        self::assertSame(50.0, $calculator->unitEur(1000.0, 'EUR', 99.99, null));
        self::assertTrue($calculator->usesManual(1000.0, 'EUR', 100.0, null));
        self::assertSame(120.0, $calculator->unitEur(1000.0, 'EUR', 120.0, null));
    }

    public function test_invalid_currency_or_missing_rsd_rate_never_reintroduces_nominal_floor(): void
    {
        $calculator = new CommissionCalculator();

        self::assertSame(0.0, $calculator->unitEur(100.0, 'USD', null, null));
        self::assertFalse($calculator->usesManual(1000.0, 'RSD', 20.0, null));
        self::assertSame(0.0, $calculator->unitEur(1000.0, 'RSD', 20.0, null));
    }
}
