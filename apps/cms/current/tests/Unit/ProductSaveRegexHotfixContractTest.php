<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class ProductSaveRegexHotfixContractTest extends TestCase
{
    #[Test]
    public function product_sku_rule_uses_a_valid_delimiter_and_retired_variant_request_is_absent(): void
    {
        $root = dirname(__DIR__, 2);
        $product = (string) file_get_contents($root.'/app/Http/Requests/ProductRequest.php');

        self::assertStringContainsString("'regex:#^[A-Z0-9._/-]+$#'", $product);
        self::assertFileDoesNotExist($root.'/app/Http/Requests/ProductVariantRequest.php');
        self::assertSame(1, preg_match('#^[A-Z0-9._/-]+$#', 'DELL/7320-16GB_512.1'));
        self::assertSame(0, preg_match('#^[A-Z0-9._/-]+$#', 'DELL 7320'));
    }
}
