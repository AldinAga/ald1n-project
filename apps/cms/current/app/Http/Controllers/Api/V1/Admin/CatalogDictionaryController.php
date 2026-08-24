<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CatalogDictionaryRequest;
use App\Models\ProductType;
use App\Models\User;
use App\Services\CatalogDictionaryManagerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// MOBILE_V1_0_ADMIN_CATALOG_DICTIONARIES_BATCH22
final class CatalogDictionaryController extends Controller
{
    public function index(Request $request, string $resource, CatalogDictionaryManagerService $manager): JsonResponse
    {
        $this->actor($request);
        $manager->assertMobileResource($resource);

        return response()->json(['data' => $manager->mobileIndexData($resource)], 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function store(CatalogDictionaryRequest $request, string $resource, CatalogDictionaryManagerService $manager): JsonResponse
    {
        $actor = $this->actor($request);
        $manager->assertMobileResource($resource);
        $result = $manager->create($resource, $request->validated(), $actor, 'mobile');
        $model = $result['model'];

        return response()->json([
            'message' => $result['message'],
            'data' => [
                'id' => (int) $model->getKey(),
                'resource' => $resource,
            ],
        ], 201);
    }

    public function update(CatalogDictionaryRequest $request, string $resource, int $item, CatalogDictionaryManagerService $manager): JsonResponse
    {
        $actor = $this->actor($request);
        $manager->assertMobileResource($resource);
        $result = $manager->update($resource, $item, $request->validated(), $actor, 'mobile');

        return response()->json([
            'message' => $result['message'],
            'data' => ['id' => (int) $result['model']->getKey(), 'resource' => $resource],
        ]);
    }

    public function destroy(Request $request, string $resource, int $item, CatalogDictionaryManagerService $manager): JsonResponse
    {
        $actor = $this->actor($request);
        $manager->assertMobileResource($resource);
        $model = $manager->deactivate($resource, $item, $actor, 'mobile');

        return response()->json([
            'message' => 'Stavka je deaktivirana, nije fizički obrisana.',
            'data' => ['id' => (int) $model->getKey(), 'resource' => $resource, 'status' => 'inactive'],
        ]);
    }

    public function reorderBrands(Request $request, CatalogDictionaryManagerService $manager): JsonResponse
    {
        $actor = $this->actor($request);
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $ids = $manager->reorder('brands', array_values(array_map('intval', $data['ids'])), $actor, 'mobile');

        return response()->json(['message' => 'Raspored brendova je sačuvan.', 'data' => ['ids' => $ids]]);
    }

    public function reorder(Request $request, string $resource, CatalogDictionaryManagerService $manager): JsonResponse
    {
        $actor = $this->actor($request);
        $manager->assertMobileResource($resource);
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:1000'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $ids = $manager->reorder($resource, array_values(array_map('intval', $data['ids'])), $actor, 'mobile');

        return response()->json(['message' => 'Novi raspored je sačuvan.', 'data' => ['ids' => $ids]]);
    }

    public function productType(Request $request, int $productType, CatalogDictionaryManagerService $manager): JsonResponse
    {
        $this->actor($request);
        ProductType::query()->findOrFail($productType);

        return response()->json(['data' => $manager->mobileProductTypeData($productType)], 200, [
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function reorderTypeFields(Request $request, int $productType, CatalogDictionaryManagerService $manager): JsonResponse
    {
        $actor = $this->actor($request);
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['required', 'integer', 'distinct', 'exists:specification_fields,id'],
        ]);
        $ids = $manager->reorderTypeFields($productType, array_values(array_map('intval', $data['ids'])), $actor, 'mobile');

        return response()->json(['message' => 'Raspored specifikacija je sačuvan.', 'data' => ['ids' => $ids]]);
    }

    public function purgeSpecificationField(Request $request, int $item, CatalogDictionaryManagerService $manager): JsonResponse
    {
        $actor = $this->actor($request);
        $data = $request->validate(['confirm_name' => ['required', 'string', 'max:120']]);
        $cleanup = $manager->purgeSpecificationField($item, (string) $data['confirm_name'], $actor, 'mobile');

        return response()->json([
            'message' => 'Specifikaciono polje je bezbedno trajno obrisano zajedno sa povezanim vrednostima.',
            'data' => ['deleted_id' => $item, 'cleanup' => $cleanup],
        ]);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        abort_unless($actor->can('catalog.manage_taxonomy'), 403);
        return $actor;
    }
}
