<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;

final class PerformanceDataQualityContractTest extends TestCase
{
    public function test_v216_release_contract_is_present(): void
    {
        $root = dirname(__DIR__, 2);
        self::assertTrue(version_compare(trim((string) file_get_contents($root.'/VERSION')), '2.1.6', '>='));
        self::assertFileExists($root.'/app/Services/DataQualityService.php');
        self::assertFileExists($root.'/app/Console/Commands/PerformanceDoctorCommand.php');
        self::assertFileExists($root.'/resources/views/admin/data-quality/index.blade.php');
        self::assertFileExists($root.'/database/migrations/2026_08_05_000038_create_performance_data_quality_v2_1_6.php');
    }

    public function test_release_profile_and_catalog_quality_filters_are_connected(): void
    {
        $root = dirname(__DIR__, 2);
        $release = (string) file_get_contents($root.'/config/release.php');
        $query = (string) file_get_contents($root.'/app/Services/CatalogQueryService.php');

        self::assertStringContainsString("'cms_v216'", $release);
        self::assertStringContainsString("app:cms-v2-1-6-doctor", $release);
        self::assertTrue(Route::has('admin.data-quality.index'));
        self::assertSame('admin/data-quality',Route::getRoutes()->getByName('admin.data-quality.index')->uri());
        self::assertStringContainsString("'missing_image'", $query);
        self::assertStringContainsString("'incomplete'", $query);
    }
}
