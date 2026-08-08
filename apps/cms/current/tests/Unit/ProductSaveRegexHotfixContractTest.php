<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProductSaveRegexHotfixContractTest extends TestCase
{
    #[Test]
    public function product_and_variant_sku_rules_use_a_valid_delimiter(): void
    {
        $root = dirname(__DIR__, 2);
        $product = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');
        $variant = (string) file_get_contents($root.'/app/Http/Requests/ProductVariantRequest.php');

        self::assertStringContainsString("'regex:#^[A-Z0-9._/-]+$#'", $product);
        self::assertStringContainsString("'regex:#^[A-Z0-9._/-]+$#'", $variant);
        self::assertSame(1, preg_match('#^[A-Z0-9._/-]+$#', 'DELL/7320-16GB_512.1'));
        self::assertSame(0, preg_match('#^[A-Z0-9._/-]+$#', 'DELL 7320'));
    }
}
