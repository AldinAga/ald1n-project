<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Services\CatalogQueryService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class CatalogController extends Controller
{
    public function index(Request $request, CatalogQueryService $catalog): AnonymousResourceCollection
    {
        return ProductResource::collection($catalog->paginate($request->user(), $request->query(), (int) $request->integer('per_page', 20)));
    }

    public function show(Request $request, string $slug, CatalogQueryService $catalog): ProductResource
    {
        $product = $catalog->findVisibleBySlug($request->user(), $slug);
        $product->loadMissing('presentationImage');

        return new ProductResource($product);
    }
}
