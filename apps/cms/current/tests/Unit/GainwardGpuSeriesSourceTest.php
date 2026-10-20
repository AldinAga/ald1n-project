<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\BrandLineCatalogService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionClassConstant;

// Source-only regression. Does not create brands or mutate a database.
final class GainwardGpuSeriesSourceTest extends TestCase
{
    public function test_all_official_gainward_gpu_families_are_curated_and_type_scoped(): void
    {
        $class = new ReflectionClass(BrandLineCatalogService::class);
        $catalogConstant = $class->getReflectionConstant('CATALOG');
        self::assertInstanceOf(ReflectionClassConstant::class, $catalogConstant);

        $catalog = $catalogConstant->getValue();
        self::assertIsArray($catalog);
        self::assertSame(
            ['Phantom', 'Phoenix', 'Panther', 'Python', 'Ghost', 'Pegasus'],
            $catalog['gpu']['gainward'] ?? null
        );
        self::assertCount(6, $catalog['gpu']['gainward']);
        self::assertLessThanOrEqual(BrandLineCatalogService::MAX_LINES_PER_TYPE, count($catalog['gpu']['gainward']));

        $aliasesConstant = $class->getReflectionConstant('BRAND_ALIASES');
        self::assertInstanceOf(ReflectionClassConstant::class, $aliasesConstant);
        $aliases = $aliasesConstant->getValue();
        self::assertSame('gainward', $aliases['gainward'] ?? null);
    }
}
