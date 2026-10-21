<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class WarrantyExpiryOptInSourceTest extends TestCase
{
    public function test_expiry_is_default_off_and_requires_strict_immutable_order_customer(): void
    {
        $root = dirname(__DIR__, 2);
        $preferences = file_get_contents($root.'/app/Services/UserNotificationPreferenceService.php');
        $dispatch = file_get_contents($root.'/app/Services/OperationalNotificationService.php');
        $model = file_get_contents($root.'/app/Models/ProductWarranty.php');
        $api = file_get_contents($root.'/app/Http/Resources/Api/V1/NotificationPreferenceResource.php');
        $migration = file_get_contents($root.'/database/migrations/2026_10_21_080000_add_warranty_expiry_opt_in.php');
        foreach ([$preferences, $dispatch, $model, $api, $migration] as $file) self::assertIsString($file);
        self::assertStringContainsString("'warranty_expiry_alerts' => false", $preferences);
        self::assertStringContainsString("'warranty_expiry_alerts' => false", $dispatch);
        self::assertStringContainsString('if ($preference->warranty_expiry_alerts !== true) return;', $dispatch);
        self::assertStringContainsString('strictCustomerOwner()', $dispatch);
        self::assertStringContainsString('orderItem.order.user', $model);
        self::assertStringContainsString("warranty_expiry_alerts", $api);
        self::assertStringContainsString("->default(false)", $migration);
    }

    public function test_openapi_mirrors_include_explicit_warranty_expiry_opt_in(): void
    {
        $cmsRoot = dirname(__DIR__, 2);
        $repoRoot = dirname($cmsRoot, 3);
        $canonical = file_get_contents($repoRoot.'/packages/api-contract/openapi.yaml');
        $cms = file_get_contents($cmsRoot.'/docs/openapi.yaml');
        $mobile = file_get_contents($repoRoot.'/apps/mobile/current/docs/openapi.yaml');
        self::assertIsString($canonical);
        self::assertSame($canonical, $cms);
        self::assertSame($canonical, $mobile);
        self::assertStringContainsString(
            "warranty_expiry_alerts: { type: boolean, default: false",
            $canonical,
        );
        self::assertSame(1, substr_count($canonical, 'warranty_expiry_alerts: { type: boolean'));
    }

    public function test_expiry_migration_must_fail_closed_on_unbounded_ddl(): void
    {
        $migration = file_get_contents(dirname(__DIR__, 2).'/database/migrations/2026_10_21_080000_add_warranty_expiry_opt_in.php');
        self::assertIsString($migration);
        self::assertStringContainsString('ALGORITHM=INSTANT, LOCK=NONE', $migration);
        self::assertStringContainsString('lock_wait_timeout', $migration);
        self::assertStringContainsString('information_schema.TABLES', $migration);
        self::assertStringContainsString('InnoDB', $migration);
    }
}
