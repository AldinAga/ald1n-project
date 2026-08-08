<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\BankAccountNumberService;
use PHPUnit\Framework\TestCase;

final class BankAccountNumberServiceTest extends TestCase
{
    public function test_it_normalizes_formats_and_checks_mod97(): void
    {
        $service = new BankAccountNumberService();
        self::assertSame('160123456789012312', $service->normalize('160-1234567890123-12'));
        self::assertSame('160-1234567890123-12', $service->format('160123456789012312'));
        self::assertTrue($service->passesMod97('160123456789012312'));
        self::assertFalse($service->passesMod97('160123456789012313'));
    }
}
