<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Support\ApiErrorResponse;
use Illuminate\Http\Request;

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
        $errors=(string)file_get_contents($root.'/app/Support/ApiErrorResponse.php');
        self::assertStringContainsString('ApiErrorResponse::make', $bootstrap);
        self::assertStringContainsString("'request_id' => self::requestId(\$request)", $errors);
        self::assertStringContainsString('$status >= 500', $bootstrap);
        self::assertStringContainsString('openapi: 3.1.0', $openApi);
        self::assertStringContainsString('/api/v1/bootstrap:', $openApi);
        self::assertStringContainsString('absoluteUrl', $productResource);
        self::assertStringContainsString('PersonalAccessToken::query()', (string) file_get_contents($root.'/app/Http/Controllers/Api/V1/MobileDeviceController.php'));
    }
    public function test_shared_api_error_response_preserves_status_request_id_errors_and_headers(): void
    {
        $request=Request::create('/api/v1/catalog','GET');
        $request->attributes->set('request_id','b1091-synthetic-request');
        $response=ApiErrorResponse::make($request,'Invalid input','validation_failed',422,[
            'name'=>['Required'],
        ],['Retry-After'=>'7']);
        self::assertSame(422,$response->getStatusCode());
        self::assertSame([
            'message'=>'Invalid input','code'=>'validation_failed',
            'request_id'=>'b1091-synthetic-request','errors'=>['name'=>['Required']],
        ],$response->getData(true));
        self::assertSame('7',$response->headers->get('Retry-After'));
        $missing=ApiErrorResponse::make(Request::create('/api/v1/catalog'),'Unavailable','server_error',500);
        self::assertSame(500,$missing->getStatusCode());
        self::assertNull($missing->getData(true)['request_id']);
        self::assertArrayNotHasKey('errors',$missing->getData(true));
    }

}
