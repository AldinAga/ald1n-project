<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ProductSkuGenerator;
use PHPUnit\Framework\TestCase;

final class ProductSkuGeneratorTest extends TestCase
{
    public function test_it_normalizes_serbian_characters_and_adds_unique_suffix(): void
    {
        $existing = ['LENOVO-THINKPAD-T14-RACUNAR'];
        $sku = (new ProductSkuGenerator())->generate(['Lenovo','ThinkPad','T14','Računar'], fn (string $candidate): bool => in_array($candidate,$existing,true));
        self::assertSame('LENOVO-THINKPAD-T14-RACUNAR-2',$sku);
    }
}
