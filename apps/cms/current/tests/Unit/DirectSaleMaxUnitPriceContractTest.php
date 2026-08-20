<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Product;
use App\Services\DirectSaleService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DirectSaleMaxUnitPriceContractTest extends TestCase
{
    public function test_catalog_unit_price_rsd_matches_product_currency_rules(): void
    {
        $service = $this->serviceWithoutConstructor();
        $method = (new ReflectionClass(DirectSaleService::class))->getMethod('catalogUnitPriceRsd');

        $productRsd = new Product();
        $productRsd->forceFill(['price_currency' => 'RSD', 'price_amount' => 13000.129]);
        self::assertSame(13000.13, $method->invoke($service, $productRsd, null));

        $productEur = new Product();
        $productEur->forceFill(['price_currency' => 'EUR', 'price_amount' => 100.00]);
        self::assertSame(11741.00, $method->invoke($service, $productEur, 117.41));
    }

    public function test_price_below_and_equal_catalog_are_allowed_but_above_is_rejected(): void
    {
        $service = $this->serviceWithoutConstructor();
        $method = (new ReflectionClass(DirectSaleService::class))->getMethod('assertSalePriceWithinCatalogUnitPrice');

        $method->invoke($service, 12999.99, 13000.00);
        $method->invoke($service, 13000.00, 13000.00);
        self::addToAssertionCount(2);

        try {
            $method->invoke($service, 13000.01, 13000.00);
            self::fail('Expected ValidationException for unit price above catalog maximum.');
        } catch (ValidationException $exception) {
            self::assertSame(
                ['Prodajna cena po komadu ne sme biti veća od zadate cene artikla (13000.00 RSD).'],
                $exception->errors()['sale_price_rsd'] ?? [],
            );
        }
    }

    public function test_eur_product_requires_a_positive_rate(): void
    {
        $service = $this->serviceWithoutConstructor();
        $method = (new ReflectionClass(DirectSaleService::class))->getMethod('catalogUnitPriceRsd');
        $product = new Product();
        $product->forceFill(['price_currency' => 'EUR', 'price_amount' => 100.00]);

        foreach ([null, 0.0, -1.0] as $rate) {
            try {
                $method->invoke($service, $product, $rate);
                self::fail('Expected ValidationException when EUR/RSD rate is unavailable or non-positive.');
            } catch (ValidationException $exception) {
                self::assertNotEmpty($exception->errors()['sale_price_rsd'] ?? []);
            }
        }
    }

    public function test_source_contract_is_product_only_and_preserves_order_price_parity(): void
    {
        $root = dirname(__DIR__, 2);
        $direct = (string) file_get_contents($root.'/app/Services/DirectSaleService.php');
        $orders = (string) file_get_contents($root.'/app/Services/OrderService.php');

        self::assertStringContainsString('DIRECT_SALE_MAX_UNIT_PRICE_GUARD', $direct);
        self::assertStringNotContainsString('ProductVariant', $direct);
        self::assertStringNotContainsString('product_variant_id', $direct);
        self::assertStringContainsString('if ($salePrice > $catalogUnitPriceRsd)', $direct);
        self::assertStringNotContainsString('if ($salePrice >= $catalogUnitPriceRsd)', $direct);
        self::assertStringContainsString('Prodajna cena po komadu ne sme biti veća od zadate cene artikla', $direct);
        self::assertStringContainsString("if (\$sellable->price_currency === 'RSD')", $direct);
        self::assertStringContainsString('return round((float) $sellable->price_amount * $rate, 2);', $direct);
        self::assertStringContainsString('$lineTotal = round($salePrice * $quantity, 2);', $direct);

        self::assertStringContainsString('private function priceRsd(Product $product, ?float $rate): float', $orders);
        self::assertStringNotContainsString('ProductVariant', $orders);
        self::assertStringNotContainsString('product_variant_id', $orders);
        self::assertStringContainsString("if (\$product->price_currency === 'RSD')", $orders);
        self::assertStringContainsString('return round((float) $product->price_amount * $rate, 2);', $orders);
    }

    private function serviceWithoutConstructor(): DirectSaleService
    {
        $reflection = new ReflectionClass(DirectSaleService::class);
        /** @var DirectSaleService $service */
        $service = $reflection->newInstanceWithoutConstructor();
        return $service;
    }
}
