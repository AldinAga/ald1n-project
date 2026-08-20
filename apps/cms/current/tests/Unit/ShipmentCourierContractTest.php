<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ShipmentCourierContractTest extends TestCase
{
    public function test_shipment_and_courier_contract_markers_are_present(): void
    {
        $root = dirname(__DIR__, 2);
        $routes = (string) file_get_contents($root.'/routes/web.php');
        $service = (string) file_get_contents($root.'/app/Services/OrderShipmentService.php');
        $workflow = (string) file_get_contents($root.'/app/Services/OrderWorkflowService.php');
        $view = (string) file_get_contents($root.'/resources/views/admin/orders/show.blade.php');
        $directory = (string) file_get_contents($root.'/app/Services/CourierDirectoryService.php');

        self::assertStringContainsString("Route::post('/orders/{order}/shipment'", $routes);
        self::assertStringContainsString("Route::get('/orders/{order}/shipment-proof'", $routes);
        self::assertStringContainsString("'status' => 'shipped'", $service);
        self::assertStringNotContainsString("'completed_at' =>", $service);
        self::assertStringNotContainsString("cash_on_delivery", $service);
        self::assertStringContainsString('Broj za praćenje se unosi isključivo kroz Evidenciju slanja pošiljke.', $workflow);
        self::assertStringContainsString('Evidencija slanja pošiljke', $view);
        self::assertStringContainsString('Evidentiraj da je poručeni artikal poslat kupcu', $view);
        self::assertStringContainsString('final class CourierDirectoryService', $directory);
        self::assertStringContainsString("hasRole('superadmin')", $directory);
    }
}
