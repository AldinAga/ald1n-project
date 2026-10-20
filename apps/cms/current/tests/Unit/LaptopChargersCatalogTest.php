<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Services\BrandLineCatalogService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
final class LaptopChargersCatalogTest extends TestCase
{
    public function test_connector_dimensions_and_brand_scope(): void
    {
        self::assertSame(['Debela igla (7,4 × 5,0 mm)', 'Tanka igla (4,5 × 3,0 mm)', 'USB-C PD'], BrandLineCatalogService::laptopChargerLines('Dell'));
        self::assertSame(['Debela igla (7,4 × 5,0 mm)', 'Plava igla (4,5 × 3,0 mm)', 'USB-C PD'], BrandLineCatalogService::laptopChargerLines('HP'));
        self::assertSame(['Kocka / Slim Tip (11,0 × 4,5 mm)', 'Tanka igla (4,0 × 1,7 mm)', 'Okrugli (7,9 × 5,5 mm)', 'USB-C PD'], BrandLineCatalogService::laptopChargerLines('Lenovo'));
        self::assertSame(BrandLineCatalogService::laptopChargerLines('HP'), BrandLineCatalogService::laptopChargerLines('Hewlett-Packard'));
        self::assertSame([], BrandLineCatalogService::laptopChargerLines('Seasonic'));
        foreach (['Dell','HP','Lenovo','Asus','Acer','MSI','Apple','Microsoft','Huawei','Honor','Samsung','Toshiba','Dynabook'] as $brand) {
            self::assertNotEmpty(BrandLineCatalogService::laptopChargerLines($brand), $brand);
            self::assertLessThanOrEqual(BrandLineCatalogService::MAX_LINES_PER_TYPE, count(BrandLineCatalogService::laptopChargerLines($brand)));
        }
    }

    public function test_laptop_charger_does_not_inherit_laptop_families(): void
    {
        $service = new BrandLineCatalogService();
        $method = new ReflectionMethod(BrandLineCatalogService::class, 'typeGroup');
        self::assertSame('laptop_charger', $method->invoke($service, 'Punjači za laptopove', 'Punjači za laptopove'));
        self::assertSame('laptop', $method->invoke($service, 'Laptopovi', 'Laptopovi'));
        self::assertSame('psu', $method->invoke($service, 'Napajanja', 'Napajanja'));
    }
}
