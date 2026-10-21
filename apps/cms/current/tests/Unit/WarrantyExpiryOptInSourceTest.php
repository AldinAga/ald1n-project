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
}
