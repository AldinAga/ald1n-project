<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BrandManagerRequest;
use App\Models\Brand;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\BrandManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// MOBILE_V0_9_GLOBAL_BRAND_MANAGER_BATCH3
final class CatalogBrandController extends Controller
{
    public function index(Request $request, BrandManagerService $service): JsonResponse
    {
        $this->actor($request);
        $q = trim((string) $request->query('q', ''));
        $typeId = $request->filled('product_type_id') ? (int) $request->query('product_type_id') : null;
        return response()->json(['data' => $service->managerData($q, $typeId)], 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function options(Request $request, BrandManagerService $service): JsonResponse
    {
        $this->actor($request);
        return response()->json(['data' => $service->optionsData()], 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function store(BrandManagerRequest $request, BrandManagerService $service, AuditLogger $audit): JsonResponse
    {
        $actor = $this->actor($request);
        $brand = $service->create($request->validated(), $actor);
        $payload = $service->brandPayload((int) $brand->getKey());
        $audit->log(
            'catalog.brand.created',
            'Kreiran globalni brend '.$brand->name,
            $brand,
            after: $payload,
            metadata: ['source' => 'mobile_global_brand_manager', 'product_type_ids' => $payload['product_type_ids']],
            user: $actor,
        );
        return response()->json(['message' => 'Brend je kreiran.', 'data' => $payload], 201);
    }

    public function update(BrandManagerRequest $request, Brand $brand, BrandManagerService $service, AuditLogger $audit): JsonResponse
    {
        $actor = $this->actor($request);
        $before = $service->brandPayload((int) $brand->getKey());
        $updated = $service->update($brand, $request->validated(), $actor);
        $payload = $service->brandPayload((int) $updated->getKey());
        $audit->log(
            'catalog.brand.updated',
            'Izmenjen globalni brend '.$updated->name,
            $updated,
            before: $before,
            after: $payload,
            metadata: ['source' => 'mobile_global_brand_manager', 'product_type_ids' => $payload['product_type_ids']],
            user: $actor,
        );
        return response()->json(['message' => 'Brend je sačuvan.', 'data' => $payload]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->can('catalog.manage_taxonomy'), 403);
        return $actor;
    }
}
