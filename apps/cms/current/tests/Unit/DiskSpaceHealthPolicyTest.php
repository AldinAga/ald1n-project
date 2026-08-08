<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\DiskSpaceHealthPolicy;
use PHPUnit\Framework\TestCase;

final class DiskSpaceHealthPolicyTest extends TestCase
{
    private const GIB = 1073741824;

    public function test_large_absolute_free_space_is_healthy_even_below_ten_percent(): void
    {
        $result = DiskSpaceHealthPolicy::evaluate(750.52 * self::GIB, 7817 * self::GIB);

        self::assertSame('healthy', $result['status']);
        self::assertEqualsWithDelta(9.6, (float) $result['free_percent'], 0.1);
    }

    public function test_low_absolute_free_space_is_critical(): void
    {
        $result = DiskSpaceHealthPolicy::evaluate(0.5 * self::GIB, 100 * self::GIB);

        self::assertSame('critical', $result['status']);
    }

    public function test_warning_threshold_is_preserved(): void
    {
        $result = DiskSpaceHealthPolicy::evaluate(4 * self::GIB, 100 * self::GIB);

        self::assertSame('warning', $result['status']);
    }
}
