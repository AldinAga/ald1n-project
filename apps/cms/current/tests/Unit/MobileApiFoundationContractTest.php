<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MobileApiFoundationContractTest extends TestCase
{
    public function test_v220_release_contains_mobile_api_contract(): void
    {
        $root = dirname(__DIR__, 2);
        $routes = (string) file_get_contents($root.'/routes/api.php');
        $bootstrap = (string) file_get_contents($root.'/bootstrap/app.php');
        $openApi = (string) file_get_contents($root.'/docs/openapi.yaml');
        $productResource = (string) file_get_contents($root.'/app/Http/Resources/ProductResource.php');

        self::assertStringContainsString("Route::get('/bootstrap'", $routes);
        self::assertStringContainsString("Route::post('/devices'", $routes);
        self::assertStringContainsString("Route::get('/catalog/filters'", $routes);
        self::assertStringContainsString("Route::get('/orders/options'", $routes);
        self::assertStringContainsString("Route::get('/notifications'", $routes);
        self::assertStringContainsString('validation_failed', $bootstrap);
        self::assertStringContainsString('request_id', $bootstrap);
        self::assertStringContainsString('$status >= 500', $bootstrap);
        self::assertStringContainsString('openapi: 3.1.0', $openApi);
        self::assertStringContainsString('/api/v1/bootstrap:', $openApi);
        self::assertStringContainsString('absoluteUrl', $productResource);
        self::assertStringContainsString('PersonalAccessToken::query()', (string) file_get_contents($root.'/app/Http/Controllers/Api/V1/MobileDeviceController.php'));
    }
}
