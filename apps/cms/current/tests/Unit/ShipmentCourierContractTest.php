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
        // Broj pošiljke se unosi isključivo kroz evidenciju slanja: mrtva ručna izmena (updateTracking, ruta orders.tracking) je uklonjena.
        self::assertStringNotContainsString('updateTracking', $workflow);
        self::assertStringNotContainsString("name('orders.tracking')", $routes);
        self::assertStringContainsString('Broj za praćenje pošiljke je obavezan za kurirsku službu.', $service);
        self::assertStringContainsString('Evidencija slanja pošiljke', $view);
        self::assertStringContainsString('Pošiljka je poslata', $view);
        self::assertStringContainsString('Ovo ne potvrđuje isporuku niti naplatu.', $view);
        self::assertStringContainsString('Potvrdi isporuku', $view);
        self::assertStringContainsString('Pošiljka vraćena', $view);
        self::assertStringContainsString('final class CourierDirectoryService', $directory);
        self::assertStringContainsString("hasRole('superadmin')", $directory);
    }
}
