<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\SpecificationField;
use App\Services\CatalogAccessService;
use App\Services\ProductAdminService;
use App\Services\ProductBulkService;
use App\Services\ProductTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class CatalogAdvancedController extends Controller
{
    public function __construct(private readonly CatalogAccessService $catalogAccess) {}

    public function namePreview(Request $request, ProductTemplateService $templates): JsonResponse
    {
        $data = $request->validate([
            'product_type_id' => ['required', 'integer', 'exists:product_types,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'product_line_id' => ['nullable', 'integer', 'exists:product_lines,id'],
            'model_name' => ['nullable', 'string', 'max:190'],
            'sku' => ['nullable', 'string', 'max:100'],
            'specs' => ['nullable', 'array'],
            'spec_details' => ['nullable', 'array'],
        ]);
        $type = ProductType::query()->with('fields')->findOrFail((int) $data['product_type_id']);

        return $this->jsonNoStore([
            'data' => [
                'name' => $templates->generateName(
                    $type,
                    $data,
                    (array) ($data['specs'] ?? []),
                    (array) ($data['spec_details'] ?? []),
                ),
            ],
        ]);
    }

    public function clone(Product $product, Request $request, ProductAdminService $products): JsonResponse
    {
        $this->authorizeProduct($product, $request);
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:190'],
            'copy_basic' => ['nullable', 'boolean'],
            'copy_specifications' => ['nullable', 'boolean'],
            'copy_price' => ['nullable', 'boolean'],
            'copy_description' => ['nullable', 'boolean'],
            'copy_notes' => ['nullable', 'boolean'],
            'copy_images' => ['nullable', 'boolean'],
            'copy_warranty_rules' => ['nullable', 'boolean'],
            'regenerate_name' => ['nullable', 'boolean'],
        ]);
        $clone = $products->clone($product, $data, $request->user());

        return $this->jsonNoStore([
            'message' => 'Artikal je kloniran.',
            'data' => [
                'id' => (int) $clone->id,
                'sku' => (string) $clone->sku,
                'name' => (string) $clone->name,
                'status' => (string) $clone->status,
                'source_product_id' => $clone->source_product_id !== null ? (int) $clone->source_product_id : null,
            ],
        ], 201);
    }

    public function regenerateName(Product $product, Request $request, ProductAdminService $products): JsonResponse
    {
        $this->authorizeProduct($product, $request);
        $updated = $products->regenerateName($product, $request->user());

        return $this->jsonNoStore([
            'message' => 'Naziv artikla je regenerisan prema šablonu.',
            'data' => [
                'id' => (int) $updated->id,
                'sku' => (string) $updated->sku,
                'name' => (string) $updated->name,
            ],
        ]);
    }

    public function bulkOptions(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('catalog.manage_products'), 403);

        return $this->jsonNoStore(['data' => [
            'brands' => Brand::query()->where('status', 'active')->orderBy('name')->get(['id', 'name'])
                ->map(static fn (Brand $brand): array => ['id' => (int) $brand->id, 'name' => (string) $brand->name])->values()->all(),
            'lines' => ProductLine::query()->where('status', 'active')->orderBy('name')->get(['id', 'brand_id', 'name'])
                ->map(static fn (ProductLine $line): array => ['id' => (int) $line->id, 'brand_id' => (int) $line->brand_id, 'name' => (string) $line->name])->values()->all(),
            'types' => ProductType::query()->where('status', 'active')->orderBy('name')->get(['id', 'category_id', 'name'])
                ->map(static fn (ProductType $type): array => ['id' => (int) $type->id, 'category_id' => $type->category_id !== null ? (int) $type->category_id : null, 'name' => (string) $type->name])->values()->all(),
            'specification_fields' => SpecificationField::query()->where('status', 'active')->orderBy('sort_order')->orderBy('name')->get()
                ->reject(static fn (SpecificationField $field): bool => $field->isDerivedStorageTotalField() || $field->isRepeatableStorageField())
                ->map(static fn (SpecificationField $field): array => [
                    'id' => (int) $field->id,
                    'name' => (string) $field->name,
                    'data_type' => (string) $field->data_type,
                    'unit' => $field->unit !== null ? (string) $field->unit : null,
                    'detail_input_enabled' => (bool) $field->detail_input_enabled,
                ])->values()->all(),
            'price_actions' => ['set', 'increase_percent', 'decrease_percent', 'increase_fixed', 'decrease_fixed'],
            'max_products' => 500,
        ]]);
    }

    public function bulkPreview(Request $request, ProductBulkService $bulk): JsonResponse
    {
        $data = $this->bulkData($request);
        $data['mode'] = 'preview';

        return $this->jsonNoStore(['data' => $bulk->preview($data, $request->user())]);
    }

    public function bulkExecute(Request $request, ProductBulkService $bulk): JsonResponse
    {
        $data = $this->bulkData($request);
        $data['mode'] = 'execute';
        $count = $bulk->execute($data, $request->user());

        return $this->jsonNoStore([
            'message' => 'Bulk izmena je završena.',
            'data' => ['updated' => $count],
        ]);
    }

    /** @return array<string,mixed> */
    private function bulkData(Request $request): array
    {
        return $request->validate([
            'product_ids' => ['required', 'array', 'min:1', 'max:500'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'apply_status' => ['nullable', 'boolean'],
            'status' => ['nullable', 'required_if:apply_status,1', Rule::in(['draft', 'active', 'inactive'])],
            'apply_brand' => ['nullable', 'boolean'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'apply_line' => ['nullable', 'boolean'],
            'product_line_id' => ['nullable', 'integer', 'exists:product_lines,id'],
            'apply_type' => ['nullable', 'boolean'],
            'product_type_id' => ['nullable', 'integer', 'exists:product_types,id'],
            'price_action' => ['nullable', Rule::in(['set', 'increase_percent', 'decrease_percent', 'increase_fixed', 'decrease_fixed'])],
            'price_value' => ['nullable', 'required_with:price_action', 'numeric', 'min:0', 'max:9999999999.99'],
            'specification_action' => ['nullable', Rule::in(['set', 'clear'])],
            'specification_field_id' => ['nullable', 'required_with:specification_action', 'integer', 'exists:specification_fields,id'],
            'specification_value' => ['nullable', 'required_if:specification_action,set', 'string', 'max:1000'],
            'specification_detail' => ['nullable', 'string', 'max:500'],
            'regenerate_names' => ['nullable', 'boolean'],
        ]);
    }

    private function authorizeProduct(Product $product, Request $request): void
    {
        abort_unless($request->user() !== null && $this->catalogAccess->canManage($product, $request->user()), 404);
    }

    /** @param array<string,mixed> $payload */
    private function jsonNoStore(array $payload, int $status = 200): JsonResponse
    {
        return response()->json($payload, $status, ['Cache-Control' => 'private, no-store, max-age=0']);
    }
}
