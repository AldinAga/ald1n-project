<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ReleaseCheckContractTest extends TestCase
{
    public function test_profiles_are_unique_and_reference_registered_checks(): void
    {
        $root = dirname(__DIR__, 2);
        $config = require $root.'/config/release.php';

        self::assertArrayHasKey('quick', $config['profiles']);
        self::assertArrayHasKey('standard', $config['profiles']);
        self::assertArrayHasKey('full', $config['profiles']);
        self::assertArrayHasKey('rc', $config['profiles']);
        self::assertArrayHasKey('stable', $config['profiles']);

        foreach ($config['profiles'] as $keys) {
            self::assertNotSame([], $keys);
            self::assertSame('deployment', $keys[0]);
            self::assertSame('system_health', $keys[count($keys) - 1]);
            self::assertSame($keys, array_values(array_unique($keys)));
            foreach ($keys as $key) {
                self::assertArrayHasKey($key, $config['checks']);
                self::assertIsString($config['checks'][$key]['command']);
                self::assertIsString($config['checks'][$key]['label']);
            }
        }
    }

    public function test_default_release_plan_does_not_dispatch_business_actions(): void
    {
        $root = dirname(__DIR__, 2);
        $config = require $root.'/config/release.php';
        $encoded = json_encode($config['checks'], JSON_THROW_ON_ERROR);

        self::assertStringNotContainsString('--dispatch', $encoded);
        self::assertStringNotContainsString('--create-test', $encoded);
        self::assertStringNotContainsString('--backfill', $encoded);
        self::assertStringNotContainsString('--run', $encoded);
        self::assertStringNotContainsString('--force', $encoded);
    }

    public function test_command_has_release_metadata_and_atomic_report_contracts(): void
    {
        $root = dirname(__DIR__, 2);
        $source = (string) file_get_contents($root.'/app/Console/Commands/ReleaseCheckCommand.php');

        self::assertStringContainsString("base_path('VERSION')", $source);
        self::assertStringContainsString("base_path('RELEASE-TAG')", $source);
        self::assertStringContainsString("base_path('CHANGELOG.md')", $source);
        self::assertStringContainsString('$this->runCommand(', $source);
        self::assertStringContainsString('new BufferedOutput(', $source);
        self::assertStringContainsString('rename($temporary, $target)', $source);
        self::assertStringContainsString('rename($latestTemporary, $latest)', $source);
        self::assertStringContainsString('RELEASE CHECK: NOT READY', $source);
        self::assertStringContainsString('RELEASE CHECK: READY FOR PRODUCTION', $source);
        self::assertStringContainsString('RELEASE CHECK: RC READY', $source);
        self::assertStringContainsString('RELEASE CHECK: STABLE READY', $source);
    }
}
