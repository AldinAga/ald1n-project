<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class SettingsHubNavigationContractTest extends TestCase
{
    public function test_settings_hub_centralizes_configuration_links_and_removes_courier_link_from_order_detail(): void
    {
        $root = dirname(__DIR__, 2);

        $routes = (string) file_get_contents($root.'/routes/web.php');
        $layout = (string) file_get_contents($root.'/resources/views/layouts/app.blade.php');
        $hub = (string) file_get_contents($root.'/resources/views/admin/settings/index.blade.php');
        $order = (string) file_get_contents($root.'/resources/views/admin/orders/show.blade.php');
        $couriers = (string) file_get_contents($root.'/resources/views/admin/settings/couriers.blade.php');

        self::assertStringContainsString("SettingsHubController::class, 'index'", $routes);
        self::assertStringContainsString("name('settings.index')", $routes);
        self::assertStringContainsString("route('admin.settings.index')", $layout);
        self::assertStringContainsString("route('admin.settings.couriers.index')", $layout);
        self::assertStringContainsString('Centar podešavanja', $layout);
        self::assertStringContainsString('Pronađi podešavanje', $hub);
        self::assertStringContainsString('Kurirske službe', $hub);
        self::assertStringContainsString('Šifarnici kataloga', $hub);
        self::assertStringContainsString('Pravila potraživanja', $hub);
        self::assertStringContainsString('Garancijska pravila', $hub);
        self::assertStringContainsString('Terenske ekipe i partneri', $hub);
        self::assertStringContainsString('Dobavljači servisnih delova', $hub);
        self::assertStringContainsString('Grupe pristupa', $hub);
        self::assertStringNotContainsString('Podešavanja kurira', $order);
        self::assertStringContainsString("route('admin.settings.index')", $couriers);
        self::assertStringNotContainsString('Nazad na porudžbine', $couriers);
    }
}
