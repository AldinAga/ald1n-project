<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Services\CatalogAccessService;
use App\Services\ProductAdminService;
use App\Services\ProductAnnouncementService;
use App\Services\ProductImageService;
use App\Services\StorageSpecificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// MOBILE_ADMIN_PRODUCT_CREATE_BATCH2B_STORAGE_METADATA_V06
final class CatalogProductController extends Controller
{
    public function __construct(
        private readonly CatalogAccessService $catalogAccess,
        private readonly StorageSpecificationService $storageSpecifications,
    ) {
    }

    public function options(Request $request): JsonResponse
    {
        $storageRepeaterCount = 0;

        $types = ProductType::query()
            ->with([
                'category',
                'fields' => fn ($query) => $query
                    ->where('specification_fields.status', 'active')
                    ->orderBy('product_type_fields.sort_order')
                    ->orderBy('specification_fields.name'),
                'fields.options' => fn ($query) => $query
                    ->where('status', 'active')
                    ->orderBy('sort_order')
                    ->orderBy('label'),
                'fields.options.parentOptions' => fn ($query) => $query
                    ->select('specification_options.id')
                    ->where('specification_options.status', 'active'),
            ])
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function (ProductType $type) use (&$storageRepeaterCount): array {
                $storageMap = $this->storageRepeaterMap($type);
                $storageRepeaterCount += count($storageMap);
                $derivedIds = array_values(array_unique(array_map(
                    static fn (array $metadata): int => (int) $metadata['total_field_id'],
                    $storageMap,
                )));

                return [
                    'id' => (int) $type->id,
                    'name' => (string) $type->name,
                    'category_id' => $type->category_id !== null ? (int) $type->category_id : null,
                    'category_name' => $type->category?->name,
                    'auto_name_enabled' => (bool) $type->auto_name_enabled,
                    'fields' => $type->fields->map(static function ($field) use ($storageMap, $derivedIds): array {
                        $fieldId = (int) $field->id;
                        return [
                            'id' => $fieldId,
                            'name' => (string) $field->name,
                            'slug' => (string) $field->slug,
                            'data_type' => (string) $field->data_type,
                            'filter_type' => $field->filter_type !== null ? (string) $field->filter_type : null,
                            'unit' => $field->unit !== null ? (string) $field->unit : null,
                            'min_value' => $field->min_value !== null ? (float) $field->min_value : null,
                            'max_value' => $field->max_value !== null ? (float) $field->max_value : null,
                            'parent_field_id' => $field->parent_field_id !== null ? (int) $field->parent_field_id : null,
                            'detail_input_enabled' => (bool) $field->detail_input_enabled,
                            'detail_label' => $field->detail_label !== null ? (string) $field->detail_label : null,
                            'required' => (bool) ($field->pivot?->getAttribute('is_required') ?? false),
                            'default_value' => $field->pivot?->getAttribute('default_value'),
                            'read_only_derived' => in_array($fieldId, $derivedIds, true),
                            'storage_repeater' => $storageMap[$fieldId] ?? null,
                            'options' => $field->options->map(static fn ($option): array => [
                                'id' => (int) $option->id,
                                'label' => (string) $option->label,
                                'value' => (string) $option->value,
                                'parent_option_ids' => $option->parentOptions
                                    ->pluck('id')
                                    ->map(static fn ($id): int => (int) $id)
                                    ->values()
                                    ->all(),
                            ])->values()->all(),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();

        // MOBILE_ADMIN_PRODUCT_CREATE_TYPE_SCOPED_TAXONOMY_V07
        $brandTypeMap = \Illuminate\Support\Facades\DB::table('brand_product_type')
            ->orderBy('brand_id')
            ->orderBy('product_type_id')
            ->get(['brand_id', 'product_type_id'])
            ->groupBy('brand_id')
            ->map(static fn ($rows): array => $rows->pluck('product_type_id')->map(static fn ($id): int => (int) $id)->values()->all())
            ->all();
        $lineTypeMap = \Illuminate\Support\Facades\DB::table('product_line_product_type')
            ->orderBy('product_line_id')
            ->orderBy('product_type_id')
            ->get(['product_line_id', 'product_type_id'])
            ->groupBy('product_line_id')
            ->map(static fn ($rows): array => $rows->pluck('product_type_id')->map(static fn ($id): int => (int) $id)->values()->all())
            ->all();

        $brands = Brand::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Brand $brand): array => [
                'id' => (int) $brand->id,
                'name' => (string) $brand->name,
                'product_type_ids' => $brandTypeMap[(int) $brand->id] ?? [],
            ])
            ->values()
            ->all();

        $lines = ProductLine::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'brand_id'])
            ->map(static fn (ProductLine $line): array => [
                'id' => (int) $line->id,
                'name' => (string) $line->name,
                'brand_id' => $line->brand_id !== null ? (int) $line->brand_id : null,
                'product_type_ids' => $lineTypeMap[(int) $line->id] ?? [],
            ])
            ->values()
            ->all();

        $user = $request->user();
        $canManageImages = $user !== null
            && method_exists($user, 'hasPermission')
            && $user->hasPermission('catalog.manage_images');

        return response()->json(['data' => [
            'types' => $types,
            'brands' => $brands,
            'lines' => $lines,
            'defaults' => [
                'status' => 'draft',
                'price_currency' => 'EUR',
                'stock_quantity' => 0,
                'low_stock_threshold' => 1,
            ],
            'currencies' => [
                ['value' => 'EUR', 'label' => 'EUR'],
                ['value' => 'RSD', 'label' => 'RSD'],
            ],
            'statuses' => [
                ['value' => 'draft', 'label' => 'Nacrt'],
                ['value' => 'active', 'label' => 'Aktivan'],
                ['value' => 'inactive', 'label' => 'Neaktivan'],
            ],
            'capabilities' => [
                'advanced_specifications' => true,
                'image_upload' => $canManageImages,
                'specialized_storage_repeater' => $storageRepeaterCount > 0,
            ],
            'image_limits' => [
                'max_files' => 20,
                'max_bytes' => 10 * 1024 * 1024,
                'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
                'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            ],
        ]]);
    }

    public function store(
        ProductRequest $request,
        ProductAdminService $service,
        ProductAnnouncementService $announcements,
    ): JsonResponse {
        $product = $service->create($request->validated(), $request->user());
        $announcementCount = $announcements->queueForNewlyPublished($product, $request->user());

        return response()->json([
            'message' => 'Artikal je uspešno kreiran.',
            'data' => [
                'id' => (int) $product->id,
                'slug' => (string) $product->slug,
                'sku' => (string) $product->sku,
                'name' => (string) $product->name,
                'status' => (string) $product->status,
            ],
            'announcement_count' => (int) $announcementCount,
        ], 201);
    }

    public function images(Request $request, Product $product, ProductImageService $images): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $this->catalogAccess->canManage($product, $user), 404);

        $validated = $request->validate([
            'images' => ['required', 'array', 'max:20'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $count = $images->upload($product, $validated['images']);

        return response()->json([
            'message' => $count === 1 ? 'Fotografija je dodata.' : 'Fotografije su dodate.',
            'uploaded_count' => (int) $count,
        ], 201);
    }

    /**
     * Detect the authoritative storage source -> derived total relation by exercising
     * StorageSpecificationService with a harmless in-memory 256 + 512 GB probe.
     *
     * @return array<int,array{enabled:true,total_field_id:int,total_field_name:string,total_unit:?string,max_items:int,capacity_unit:string}>
     */
    private function storageRepeaterMap(ProductType $type): array
    {
        $fields = $type->fields;
        if ($fields->count() < 2) return [];

        $fieldIds = $fields->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $result = [];

        foreach ($fields as $field) {
            $sourceId = (int) $field->id;
            $probeType = trim((string) ($field->options->first()?->value ?? ''));
            if ($probeType === '') $probeType = 'SSD';

            $specs = [$sourceId => $probeType." + ".$probeType];
            $structured = [
                $sourceId => [
                    ['type' => $probeType, 'capacity_gb' => 256],
                    ['type' => $probeType, 'capacity_gb' => 512],
                ],
            ];

            $this->storageSpecifications->applyComputedTotals($fields, $specs, $structured);

            $candidates = [];
            foreach ($specs as $candidateId => $value) {
                $targetId = (int) $candidateId;
                if ($targetId === $sourceId || !in_array($targetId, $fieldIds, true)) continue;
                if (!is_numeric($value) || abs((float) $value - 768.0) > 0.0001) continue;

                $target = $fields->firstWhere('id', $targetId);
                if ($target !== null) $candidates[$targetId] = $target;
            }

            if (count($candidates) !== 1) continue;

            $target = array_values($candidates)[0];
            $result[$sourceId] = [
                'enabled' => true,
                'total_field_id' => (int) $target->id,
                'total_field_name' => (string) $target->name,
                'total_unit' => $target->unit !== null ? (string) $target->unit : null,
                'max_items' => 8,
                'capacity_unit' => 'GB',
            ];
        }

        return $result;
    }
}
