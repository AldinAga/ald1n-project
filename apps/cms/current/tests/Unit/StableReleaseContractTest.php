<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class StableReleaseContractTest extends TestCase
{
    public function test_stable_profile_is_identical_to_confirmed_rc_profile(): void
    {
        $root = dirname(__DIR__, 2);
        $config = require $root.'/config/release.php';

        self::assertSame($config['profiles']['rc'], $config['profiles']['stable']);
        self::assertSame('deployment', $config['profiles']['stable'][0]);
        self::assertSame('system_health', $config['profiles']['stable'][count($config['profiles']['stable']) - 1]);
    }

    public function test_stable_promotes_rc_hardening_without_a_new_migration(): void
    {
        $root = dirname(__DIR__, 2);

        $version = trim((string) file_get_contents($root.'/VERSION'));
        $tag = trim((string) file_get_contents($root.'/RELEASE-TAG'));
        $from = ltrim(trim((string) file_get_contents($root.'/UPGRADE-FROM')), 'v');

        self::assertTrue(version_compare($version, '2.1.3', '>='));
        self::assertSame('v'.$version, $tag);
        self::assertTrue(version_compare($from, $version, '<'));
        self::assertSame([], glob($root.'/database/migrations/*stable*.php') ?: []);
    }

    public function test_stable_uses_one_universal_home_dashboard_and_removes_the_old_portal_page(): void
    {
        $root = dirname(__DIR__, 2);
        $routes = (string) file_get_contents($root.'/routes/web.php');
        $layout = (string) file_get_contents($root.'/resources/views/layouts/app.blade.php');
        $dashboard = (string) file_get_contents($root.'/resources/views/dashboard/index.blade.php');
        $customerCenter = (string) file_get_contents($root.'/resources/views/dashboard/partials/customer-center.blade.php');
        $controller = (string) file_get_contents($root.'/app/Http/Controllers/DashboardController.php');

        self::assertStringContainsString('data-universal-dashboard-ready="1"', $dashboard);
        self::assertStringContainsString("@include('dashboard.partials.customer-center'", $dashboard);
        self::assertStringContainsString('data-customer-center-ready="1"', $customerCenter);
        self::assertStringContainsString('CustomerPortalService $portalService', $controller);
        self::assertStringContainsString("Route::permanentRedirect('/portal', '/')", $routes);
        self::assertStringNotContainsString("name('portal.index')", $routes);
        self::assertStringNotContainsString("route('portal.index')", $layout);
        self::assertFileDoesNotExist($root.'/resources/views/portal/index.blade.php');
        self::assertFileDoesNotExist($root.'/app/Http/Controllers/CustomerPortalController.php');
    }

    public function test_stable_acceptance_command_and_docs_exist(): void
    {
        $root = dirname(__DIR__, 2);
        $command = (string) file_get_contents($root.'/app/Console/Commands/ReleaseCheckCommand.php');
        $composer = json_decode((string) file_get_contents($root.'/composer.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertStringContainsString('RELEASE CHECK: STABLE READY', $command);
        self::assertArrayHasKey('release:check:stable', $composer['scripts']);
        self::assertArrayHasKey('smoke:stable', $composer['scripts']);
        self::assertFileExists($root.'/docs/UPGRADE-V2.1-STABLE.md');
        self::assertFileExists($root.'/docs/STABLE-OPERATIONS.md');
        self::assertFileExists($root.'/DELETE-FILES.txt');
    }
}
