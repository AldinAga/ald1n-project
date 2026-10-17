<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserRetirementService;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Route and contract smoke, intentionally does not mutate production data.
 * Database-backed transfer/rollback tests are still mandatory before merge.
 */
final class UserRetirementSafetyContractTest extends TestCase
{
    public function test_retirement_routes_have_separate_preview_and_write_methods(): void
    {
        $routes = Route::getRoutes();

        $webPreview = $routes->getByName('admin.users.retirement.preview');
        $webWrite = $routes->getByName('admin.users.retirement.store');
        $apiPreview = $routes->getByName('api.v1.admin.users.retirement.preview');
        $apiWrite = $routes->getByName('api.v1.admin.users.retirement.store');

        self::assertNotNull($webPreview);
        self::assertNotNull($webWrite);
        self::assertNotNull($apiPreview);
        self::assertNotNull($apiWrite);
        self::assertContains('GET', $webPreview->methods());
        self::assertContains('POST', $webWrite->methods());
        self::assertContains('GET', $apiPreview->methods());
        self::assertContains('POST', $apiWrite->methods());
    }

    public function test_user_retirement_is_not_exposed_as_model_delete(): void
    {
        self::assertTrue(method_exists(UserRetirementService::class, 'impact'));
        self::assertTrue(method_exists(UserRetirementService::class, 'retire'));
        self::assertFalse(method_exists(UserRetirementService::class, 'forceDeleteUser'));
        self::assertFalse(method_exists(UserRetirementService::class, 'deleteUser'));
    }
}
