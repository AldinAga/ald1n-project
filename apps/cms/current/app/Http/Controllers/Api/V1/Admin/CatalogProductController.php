<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Models\User;
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

    // MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8
    public function index(Request $request): JsonResponse
    {
        return $this->productList($request, false);
    }

    public function archived(Request $request): JsonResponse
    {
        return $this->productList($request, true);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        $actor = $this->actor($request);
        abort_unless($this->catalogAccess->canManage($product, $actor), 404);

        return response()->json(['data' => $this->detailPayload($product, $actor)]);
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

    // MOBILE_BUILD16_PRODUCT_CREATE_IDEMPOTENCY_BATCH134
    public function store(
        ProductRequest $request,
        ProductAdminService $service,
        ProductAnnouncementService $announcements,
        \App\Services\IdempotencyService $idempotency,
    ): JsonResponse {
        $validated = $request->validated();
        $actor = $request->user();
        $idempotencyKey = trim((string) $request->header('Idempotency-Key', ''));

        /** @var Product $product */
        $product = $idempotencyKey !== ''
            ? $idempotency->run(
                $actor,
                'catalog.product.create',
                $idempotencyKey,
                $validated,
                Product::class,
                fn (): Product => $service->create($validated, $actor),
                static fn (int $id): Product => Product::query()->findOrFail($id),
            )
            : $service->create($validated, $actor);

        // Announcement queues already deduplicate by product/recipient, so an idempotent replay is safe.
        $announcementCount = $announcements->queueForNewlyPublished($product, $actor);

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

    public function update(
        ProductRequest $request,
        Product $product,
        ProductAdminService $service,
        ProductAnnouncementService $announcements,
    ): JsonResponse {
        $actor = $this->actor($request);
        abort_unless($this->catalogAccess->canManage($product, $actor), 404);
        abort_if($product->deleted_at !== null, 409, 'Arhivirani artikal prvo vrati iz arhive.');

        $wasActive = (string) $product->status === 'active';
        $updated = $service->update($product, $request->validated(), $actor);
        $announcementCount = !$wasActive && (string) $updated->status === 'active'
            ? $announcements->queueForNewlyPublished($updated, $actor)
            : 0;

        return response()->json([
            'message' => 'Izmene su sačuvane.',
            'data' => $this->detailPayload($updated, $actor),
            'announcement_count' => (int) $announcementCount,
        ]);
    }

    public function archive(Request $request, Product $product, ProductAdminService $service): JsonResponse
    {
        $actor = $this->actor($request);
        abort_unless($this->catalogAccess->canManage($product, $actor), 404);

        if ($product->deleted_at === null) {
            $service->archive($product, $actor);
            $product->refresh();
        }

        return response()->json([
            'message' => 'Artikal je arhiviran.',
            'data' => $this->detailPayload($product, $actor),
        ]);
    }

    public function restore(Request $request, Product $product, ProductAdminService $service): JsonResponse
    {
        $actor = $this->actor($request);
        abort_unless($this->catalogAccess->canManage($product, $actor), 404);

        if ($product->deleted_at !== null) {
            $service->restore($product, $actor);
            $product->refresh();
        }

        return response()->json([
            'message' => 'Artikal je vraćen iz arhive.',
            'data' => $this->detailPayload($product, $actor),
        ]);
    }

    // MOBILE_V0_8_SUPERADMIN_DIRECT_SALE_BATCH10
    public function directSaleOptions(
        Request $request,
        Product $product,
        \App\Services\SettingsService $settings,
    ): JsonResponse {
        $actor = $this->actor($request);
        abort_unless($actor->hasRole('superadmin'), 403);
        abort_unless($this->catalogAccess->canManage($product, $actor), 404);

        $product->refresh();
        $rate = $settings->eurRsdRate();
        $catalogUnitPriceRsd = null;
        if ((string) $product->price_currency === 'RSD') {
            $catalogUnitPriceRsd = round((float) $product->price_amount, 2);
        } elseif ($rate !== null && $rate > 0) {
            $catalogUnitPriceRsd = round((float) $product->price_amount * $rate, 2);
        }

        $blockingReason = null;
        if ($product->deleted_at !== null) {
            $blockingReason = 'Arhiviran artikal nije dostupan za direktnu prodaju.';
        } elseif (!in_array((string) $product->status, ['active', 'inactive'], true)) {
            $blockingReason = 'Direktna prodaja je dostupna samo za aktivan ili neaktivan artikal.';
        } elseif ((int) $product->stock_quantity < 1) {
            $blockingReason = 'Artikal trenutno nema raspoloživ lager.';
        } elseif ($catalogUnitPriceRsd === null || $catalogUnitPriceRsd <= 0) {
            $blockingReason = (string) $product->price_currency === 'EUR'
                ? 'EUR/RSD kurs mora biti podešen pre direktne prodaje EUR artikla.'
                : 'Prodajna cena artikla mora biti veća od nule.';
        }

        return response()->json(['data' => [
            'product' => [
                'id' => (int) $product->id,
                'sku' => (string) $product->sku,
                'name' => (string) $product->name,
                'status' => (string) $product->status,
                'stock_quantity' => (int) $product->stock_quantity,
                'catalog_price_amount' => (float) $product->price_amount,
                'catalog_price_currency' => (string) $product->price_currency,
                'catalog_unit_price_rsd' => $catalogUnitPriceRsd,
            ],
            'eur_rsd_rate' => $rate,
            'payment_methods' => [
                ['value' => 'cash', 'label' => 'Gotovina'],
                ['value' => 'card', 'label' => 'Kartica'],
                ['value' => 'bank_transfer', 'label' => 'Bankovni prenos'],
                ['value' => 'other', 'label' => 'Ostalo'],
                // MOBILE_V0_9_DIRECT_SALE_DEFERRED_PAYMENT_RECEIVABLES_BATCH5B_V2
                ['value' => 'deferred_payment', 'label' => 'Odloženo plaćanje'],
            ],
            'idempotency_key' => 'mobile-direct-sale:'.(string) \Illuminate\Support\Str::uuid(),
            'can_submit' => $blockingReason === null,
            'blocking_reason' => $blockingReason,
        ]]);
    }

    public function directSale(
        Request $request,
        Product $product,
        \App\Services\DirectSaleService $sales,
    ): JsonResponse {
        $actor = $this->actor($request);
        abort_unless($actor->hasRole('superadmin'), 403);
        abort_unless($this->catalogAccess->canManage($product, $actor), 404);

        $data = $request->validate([
            'buyer_name' => ['nullable', 'string', 'max:190'],
            'buyer_phone' => ['nullable', 'string', 'max:80'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'sale_price_rsd' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'payment_method' => ['required', \Illuminate\Validation\Rule::in(['cash', 'card', 'bank_transfer', 'other', 'deferred_payment'])],
            'installment_count' => ['exclude_unless:payment_method,deferred_payment', 'required_if:payment_method,deferred_payment', 'integer', 'min:1', 'max:24'],
            'payment_due_at' => ['exclude_unless:payment_method,deferred_payment', 'required_if:payment_method,deferred_payment', 'date_format:Y-m-d', 'after_or_equal:today'],
            'installments' => ['exclude_unless:payment_method,deferred_payment', 'nullable', 'array', 'min:1', 'max:24'],
            'installments.*.amount_rsd' => ['required_with:installments', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'installments.*.due_at' => ['required_with:installments', 'date_format:Y-m-d', 'after_or_equal:today'],
            'first_payment_method' => ['exclude_unless:payment_method,deferred_payment', 'required_with:installments', \Illuminate\Validation\Rule::in(['cash', 'card', 'bank_transfer', 'other'])],
            'idempotency_key' => ['required', 'string', 'max:200'],
        ]);

        $order = $sales->record($product, $actor, $data, (string) $data['idempotency_key']);
        $product->refresh();

        return response()->json([
            'message' => 'Direktna prodaja '.(string) $order->order_number.' je evidentirana.',
            'data' => [
                'order_id' => (int) $order->id,
                'order_number' => (string) $order->order_number,
                'status' => (string) $order->status,
                'payment_state' => (string) $order->payment_state,
                'subtotal_rsd' => (float) $order->subtotal_rsd,
                'quantity' => (int) $data['quantity'],
                'sale_price_rsd' => (float) $data['sale_price_rsd'],
                'stock_quantity_after' => (int) $product->stock_quantity,
            ],
        ], 201);
    }

    // MOBILE_V0_8_SHARED_PRODUCT_IMAGE_MANAGER_BATCH9
    public function imageIndex(Request $request, Product $product): JsonResponse
    {
        $this->imageActor($request, $product);

        return $this->imageResponse($product);
    }

    public function images(Request $request, Product $product, ProductImageService $images): JsonResponse
    {
        $this->imageActor($request, $product);
        $validated = $request->validate([
            'images' => ['required', 'array', 'max:20'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        [$count, $beforeIds, $skippedDuplicates, $skippedInputIndexes] = \Illuminate\Support\Facades\DB::transaction(
            function () use ($product, $images, $validated): array {
                Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();

                $beforeIds = ProductImage::query()
                    ->where('product_id', $product->id)
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();

                $existingHashes = ProductImage::query()
                    ->where('product_id', $product->id)
                    ->whereNotNull('file_hash')
                    ->pluck('file_hash')
                    ->filter()
                    ->map(static fn ($hash): string => (string) $hash)
                    ->all();
                $knownHashes = array_fill_keys($existingHashes, true);

                $uniqueFiles = [];
                $skippedDuplicates = 0;
                $skippedInputIndexes = [];
                foreach ($validated['images'] as $inputIndex => $file) {
                    $source = $file->getRealPath();
                    $hash = is_string($source) && is_file($source)
                        ? (hash_file('sha256', $source) ?: null)
                        : null;
                    if ($hash !== null && isset($knownHashes[$hash])) {
                        $skippedDuplicates++;
                        $skippedInputIndexes[] = (int) $inputIndex;
                        continue;
                    }
                    if ($hash !== null) $knownHashes[$hash] = true;
                    $uniqueFiles[] = $file;
                }

                $count = $images->upload($product, $uniqueFiles);

                return [$count, $beforeIds, $skippedDuplicates, $skippedInputIndexes];
            },
            3,
        );

        $uploadedQuery = ProductImage::query()
            ->where('product_id', $product->id);
        if ($beforeIds !== []) $uploadedQuery->whereNotIn('id', $beforeIds);
        $uploaded = $uploadedQuery
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $message = $count === 1
            ? 'Fotografija je dodata.'
            : ($count > 1 ? 'Fotografije su dodate.' : 'Nema novih fotografija za dodavanje.');

        return $this->imageResponse(
            $product,
            $message,
            [
                'uploaded_count' => (int) $count,
                'skipped_duplicate_count' => (int) $skippedDuplicates,
                'skipped_input_indexes' => array_values($skippedInputIndexes),
                'uploaded_images' => $uploaded->map(fn (ProductImage $image): array => $this->imagePayload($image))->values()->all(),
            ],
            201,
        );
    }

    public function imagePrimary(
        Request $request,
        Product $product,
        ProductImage $image,
        ProductImageService $images,
    ): JsonResponse {
        $this->imageActor($request, $product);
        abort_unless((int) $image->product_id === (int) $product->id, 404);
        $images->setPrimary($product, $image);

        return $this->imageResponse($product, 'Glavna slika je promenjena.');
    }

    public function imageRotate(
        Request $request,
        Product $product,
        ProductImage $image,
        ProductImageService $images,
    ): JsonResponse {
        $this->imageActor($request, $product);
        abort_unless((int) $image->product_id === (int) $product->id, 404);
        $degrees = (int) $request->validate([
            'degrees' => ['required', 'integer', 'in:90,180,270'],
        ])['degrees'];

        try {
            $images->rotate($product, $image, $degrees);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['images' => [$exception->getMessage()]],
            ], 422);
        }

        return $this->imageResponse($product, 'Slika je rotirana.');
    }

    public function imageReorder(
        Request $request,
        Product $product,
        ProductImageService $images,
    ): JsonResponse {
        $this->imageActor($request, $product);
        $validated = $request->validate([
            'image_ids' => ['required', 'array', 'max:100'],
            'image_ids.*' => ['required', 'integer', 'min:1'],
        ]);
        $images->reorder($product, array_map('intval', $validated['image_ids']));

        return $this->imageResponse($product, 'Redosled slika je sačuvan.');
    }

    public function imageDestroy(
        Request $request,
        Product $product,
        ProductImage $image,
        ProductImageService $images,
    ): JsonResponse {
        $this->imageActor($request, $product);
        abort_unless((int) $image->product_id === (int) $product->id, 404);

        try {
            $images->delete($product, $image);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => ['images' => [$exception->getMessage()]],
            ], 422);
        }

        return $this->imageResponse($product, 'Slika je uklonjena.');
    }

    private function imageActor(Request $request, Product $product): User
    {
        $actor = $this->actor($request);
        abort_unless($this->catalogAccess->canManageImages($product, $actor), 404);

        return $actor;
    }

    /** @return array<string,mixed> */
    private function imagePayload(ProductImage $image): array
    {
        return [
            'id' => (int) $image->id,
            'url' => $image->url,
            'original_url' => $image->original_url,
            'display_url' => $image->display_url,
            'thumbnail_url' => $image->thumbnail_url,
            'download_url' => $image->download_url,
            'original_filename' => $image->original_filename ?: null,
            'mime_type' => $image->mime_type ?: null,
            'file_size' => $image->file_size !== null ? (int) $image->file_size : null,
            'storage_disk' => (string) $image->storage_disk,
            'rotation_degrees' => (int) $image->rotation_degrees,
            'sort_order' => (int) $image->sort_order,
            'is_primary' => (bool) $image->is_primary,
            'can_delete' => (string) $image->storage_disk === 'public',
        ];
    }

    /**
     * @param array<string,mixed> $extra
     */
    private function imageResponse(
        Product $product,
        ?string $message = null,
        array $extra = [],
        int $status = 200,
    ): JsonResponse {
        $managedImages = ProductImage::query()
            ->where('product_id', $product->id)
            ->orderByDesc('is_primary')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $payload = [
            'data' => $managedImages
                ->map(fn (ProductImage $image): array => $this->imagePayload($image))
                ->values()
                ->all(),
            'capabilities' => [
                'primary' => true,
                'rotate' => true,
                'reorder' => true,
                'delete_public' => true,
                'legacy_copy_on_write' => true,
            ],
            'image_limits' => [
                'max_files' => 20,
                'max_bytes' => 10 * 1024 * 1024,
                'mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
                'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            ],
        ];
        if ($message !== null) $payload['message'] = $message;

        return response()->json(array_merge($payload, $extra), $status);
    }

    // MOBILE_V0_9_ADMIN_CATALOG_PRODUCTLIST_HOTFIX_V4
    private function productList(Request $request, bool $archived): JsonResponse
    {
        $actor = $this->actor($request);
        $perPage = (int) $request->integer('per_page', 20);
        if (!in_array($perPage, [20, 50, 100], true)) $perPage = 20;

        $query = Product::query()
            ->with(['brand:id,name', 'line:id,name', 'type:id,name'])
            ->withCount('images');
        $this->catalogAccess->applyManageable($query, $actor);

        if ($archived) $query->whereNotNull('deleted_at');
        else $query->whereNull('deleted_at');

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(static function ($nested) use ($search): void {
                $like = '%'.$search.'%';
                $nested->where('name', 'like', $like)
                    ->orWhere('sku', 'like', $like)
                    ->orWhere('model_name', 'like', $like);
            });
        }

        $status = trim((string) $request->query('status', ''));
        if (!$archived && in_array($status, ['draft', 'active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        $products = $query
            ->orderByDesc($archived ? 'deleted_at' : 'updated_at')
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => $products->getCollection()->map(fn (Product $product): array => $this->summaryPayload($product))->values()->all(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
                'from' => $products->firstItem(),
                'to' => $products->lastItem(),
            ],
            'filters' => [
                'q' => $search !== '' ? $search : null,
                'status' => $status !== '' ? $status : null,
                'archived' => $archived,
            ],
            'capabilities' => [
                'create' => $actor->can('catalog.manage_products'),
                'update' => $actor->can('catalog.manage_products'),
                'archive' => $actor->can('catalog.manage_products'),
                'restore' => $actor->can('catalog.manage_products'),
                'manage_images' => $actor->can('catalog.manage_images'),
            ],
        ]);
    }

    /** @return array<string,mixed> */
    private function summaryPayload(Product $product): array
    {
        return [
            'id' => (int) $product->id,
            'sku' => (string) $product->sku,
            'name' => (string) $product->name,
            'model_name' => $product->model_name ?: null,
            'status' => (string) $product->status,
            'is_archived' => $product->deleted_at !== null,
            'deleted_at' => $product->deleted_at?->toISOString(),
            'product_type' => $product->type ? ['id' => (int) $product->type->id, 'name' => (string) $product->type->name] : null,
            'brand' => $product->brand ? ['id' => (int) $product->brand->id, 'name' => (string) $product->brand->name] : null,
            'product_line' => $product->line ? ['id' => (int) $product->line->id, 'name' => (string) $product->line->name] : null,
            'price_amount' => (float) $product->price_amount,
            'price_currency' => (string) $product->price_currency,
            'purchase_price_rsd' => $product->purchase_price_rsd !== null ? (float) $product->purchase_price_rsd : null,
            'stock_quantity' => (int) $product->stock_quantity,
            'low_stock_threshold' => (int) $product->low_stock_threshold,
            'images_count' => (int) ($product->images_count ?? 0),
            'updated_at' => $product->updated_at?->toISOString(),
        ];
    }

    /** @return array<string,mixed> */
    private function detailPayload(Product $product, User $actor): array
    {
        $product->loadMissing(['brand:id,name', 'line:id,name', 'type:id,name', 'categories:id,name', 'specificationValues']);
        $product->loadCount('images');

        $specs = [];
        $details = [];
        $structured = [];
        foreach ($product->specificationValues as $value) {
            $fieldId = (string) ((int) $value->field_id);
            $raw = $value->value_text;
            if ($raw === null && $value->value_number !== null) $raw = (float) $value->value_number;
            if ($raw === null && $value->value_boolean !== null) $raw = (bool) $value->value_boolean;
            if ($raw !== null) $specs[$fieldId] = $raw;
            if ($value->value_detail !== null && trim((string) $value->value_detail) !== '') {
                $details[$fieldId] = (string) $value->value_detail;
            }
            if (is_array($value->value_json) && $value->value_json !== []) {
                $structured[$fieldId] = array_values($value->value_json);
            }
        }

        return [
            'id' => (int) $product->id,
            'sku' => (string) $product->sku,
            'name' => (string) $product->name,
            'slug' => (string) $product->slug,
            'model_name' => $product->model_name ?: null,
            'status' => (string) $product->status,
            'is_archived' => $product->deleted_at !== null,
            'deleted_at' => $product->deleted_at?->toISOString(),
            'product_type_id' => $product->product_type_id !== null ? (int) $product->product_type_id : null,
            'brand_id' => $product->brand_id !== null ? (int) $product->brand_id : null,
            'product_line_id' => $product->product_line_id !== null ? (int) $product->product_line_id : null,
            'category_ids' => $product->categories->pluck('id')->map(static fn ($id): int => (int) $id)->values()->all(),
            'description' => (string) ($product->description ?? ''),
            'notes' => $product->notes ?: null,
            'price_amount' => (float) $product->price_amount,
            'price_currency' => (string) $product->price_currency,
            'purchase_price_rsd' => $product->purchase_price_rsd !== null ? (float) $product->purchase_price_rsd : null,
            'manual_commission_eur' => $product->manual_commission_eur !== null ? (float) $product->manual_commission_eur : null,
            'stock_quantity' => (int) $product->stock_quantity,
            'low_stock_threshold' => (int) $product->low_stock_threshold,
            'specs' => $specs,
            'spec_details' => $details,
            'spec_structured' => $structured,
            'images_count' => (int) ($product->images_count ?? 0),
            'capabilities' => [
                'update' => $actor->can('catalog.manage_products') && $product->deleted_at === null,
                'archive' => $actor->can('catalog.manage_products') && $product->deleted_at === null,
                'restore' => $actor->can('catalog.manage_products') && $product->deleted_at !== null,
                'manage_images' => $actor->can('catalog.manage_images'),
                'stock_adjust' => $actor->can('stock.adjust'),
            ],
            'updated_at' => $product->updated_at?->toISOString(),
        ];
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);
        return $actor;
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
