<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\CommissionCalculator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CommissionCalculatorTest extends TestCase
{
    #[DataProvider('cases')]
    public function test_commission_rules(float $amount, string $currency, ?float $manual, ?float $rate, float $expected): void
    {
        self::assertSame($expected, (new CommissionCalculator())->unitEur($amount, $currency, $manual, $rate));
    }

    public static function cases(): array
    {
        return [
            'minimum' => [100.0, 'EUR', null, null, 20.0],
            'percentage' => [300.0, 'EUR', null, null, 30.0],
            'maximum' => [900.0, 'EUR', null, null, 50.0],
            'manual wins' => [100.0, 'EUR', 125.50, null, 125.50],
            'manual below minimum ignored' => [300.0, 'EUR', 19.99, null, 30.0],
            'rsd conversion' => [35_241.0, 'RSD', null, 117.47, 30.0],
            'missing rsd rate uses minimum' => [35_241.0, 'RSD', null, null, 20.0],
        ];
    }
}
