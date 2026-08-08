<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductLine;
use App\Models\ProductType;
use App\Services\CatalogAccessService;
use App\Services\CatalogSpecificationFilterService;
use App\Services\ProductAdminService;
use App\Services\ProductDeletionService;
use App\Services\ProductAnnouncementService;
use App\Services\ProductTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

final class ProductController extends Controller
{
    public function __construct(
        private readonly CatalogAccessService $catalogAccess,
        private readonly ProductDeletionService $deletions,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        return redirect()->route('catalog.index', $request->query());
    }

    public function create(ProductTemplateService $templates): View
    {
        return $this->formView(new Product(['status' => 'draft', 'price_currency' => 'EUR', 'stock_quantity' => 0, 'low_stock_threshold' => 1]), $templates);
    }

    public function store(ProductRequest $request, ProductAdminService $service, ProductAnnouncementService $announcements): RedirectResponse|JsonResponse
    {
        $product = $service->create($request->validated(), $request->user());
        $announcementCount = $announcements->queueForNewlyPublished($product, $request->user());
        $redirect = route('admin.products.edit', $product);
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Artikal je kreiran.',
                'redirect_url' => $redirect,
                'product_id' => (int) $product->id,
                'announcement_count' => $announcementCount,
            ], 201);
        }

        $status = 'Artikal je kreiran.';
        if ($announcementCount > 0) $status .= ' E-mail obaveštenje je pripremljeno za '.$announcementCount.' korisnika.';
        return redirect()->to($redirect)->with('status', $status);
    }

    public function edit(Product $product, ProductTemplateService $templates): View
    {
        $this->authorizeProduct($product);
        return $this->formView($product->load(['type', 'categories', 'specificationValues', 'images']), $templates);
    }

    public function update(ProductRequest $request, Product $product, ProductAdminService $service, ProductAnnouncementService $announcements): RedirectResponse|JsonResponse
    {
        $this->authorizeProduct($product, $request);
        $wasActive = (string) $product->status === 'active';
        $updated = $service->update($product, $request->validated(), $request->user());
        $announcementCount = !$wasActive && (string) $updated->status === 'active'
            ? $announcements->queueForNewlyPublished($updated, $request->user())
            : 0;
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Izmene su sačuvane.',
                'redirect_url' => route('admin.products.edit', $updated),
                'product_id' => (int) $updated->id,
                'announcement_count' => $announcementCount,
            ]);
        }

        $status = 'Izmene su sačuvane.';
        if ($announcementCount > 0) $status .= ' E-mail obaveštenje je pripremljeno za '.$announcementCount.' korisnika.';
        return back()->with('status', $status);
    }


    public function cloneForm(Product $product): View
    {
        $this->authorizeProduct($product);
        $product->load(['brand', 'line', 'type', 'categories', 'specificationValues', 'images', 'warrantyRules', 'variants.specificationValues', 'variants.images']);
        return view('admin.products.clone', ['product' => $product]);
    }

    public function cloneStore(Product $product, Request $request, ProductAdminService $service): RedirectResponse
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
            'copy_variants' => ['nullable', 'boolean'],
            'regenerate_name' => ['nullable', 'boolean'],
        ]);
        $clone = $service->clone($product, $data, $request->user());
        return redirect()->route('admin.products.edit', $clone)->with('status', 'Artikal je kloniran. Novi SKU je '.$clone->sku.'.');
    }

    public function regenerateName(Product $product, Request $request, ProductAdminService $service): RedirectResponse
    {
        $this->authorizeProduct($product, $request);
        $service->regenerateName($product, $request->user());
        return back()->with('status', 'Naziv artikla je regenerisan prema šablonu.');
    }

    public function namePreview(Request $request, ProductTemplateService $templates): JsonResponse
    {
        $data = $request->validate([
            'product_type_id' => ['required', 'integer', 'exists:product_types,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'product_line_id' => ['nullable', 'integer', 'exists:product_lines,id'],
            'model_name' => ['nullable', 'string', 'max:190'],
            'sku' => ['nullable', 'string', 'max:100'],
            'specs' => ['array'],
            'spec_details' => ['array'],
        ]);
        $type = ProductType::query()->with('fields')->findOrFail((int) $data['product_type_id']);
        return response()->json(['name' => $templates->generateName($type, $data, (array) ($data['specs'] ?? []), (array) ($data['spec_details'] ?? []))]);
    }

    public function archive(Product $product, Request $request, ProductAdminService $service): RedirectResponse
    {
        $this->authorizeProduct($product, $request);
        $service->archive($product, $request->user());
        return redirect()->route('catalog.index')->with('status', 'Artikal je arhiviran.');
    }

    public function restore(Product $product, Request $request, ProductAdminService $service): RedirectResponse
    {
        $this->authorizeProduct($product, $request);
        $service->restore($product, $request->user());
        return back()->with('status', 'Artikal je vraćen iz arhive.');
    }

    public function purge(Product $product, Request $request): RedirectResponse
    {
        $this->authorizeProduct($product, $request);
        $data = $request->validate([
            'confirmation' => ['required', 'string', 'max:100'],
            'delete_images' => ['nullable', 'boolean'],
        ]);

        if (!hash_equals((string) $product->sku, trim((string) $data['confirmation']))) {
            throw ValidationException::withMessages([
                'confirmation' => 'Za trajno brisanje upiši tačnu šifru artikla: '.$product->sku.'.',
            ]);
        }

        $result = $this->deletions->purge($product, $request->user(), $request->boolean('delete_images'));
        $message = 'Artikal je trajno obrisan.';
        if ($result['files_requested']) {
            $message .= $result['files_deleted']
                ? ' Lokalne slike su uklonjene sa servera.'
                : ' Zapisi slika su obrisani, ali proveri storage jer direktorijum nije potpuno uklonjen.';
        } else {
            $message .= ' Fajlovi slika su ostavljeni na serveru po izabranoj opciji.';
        }
        if ($result['legacy_images'] > 0) {
            $message .= ' Legacy slike ('.$result['legacy_images'].') nisu fizički brisane jer pripadaju read-only izvoru.';
        }

        return redirect()->route('catalog.index')->with('status', $message);
    }

    private function authorizeProduct(Product $product, ?Request $request = null): void
    {
        $user = ($request ?? request())->user();
        abort_unless($user !== null && $this->catalogAccess->canManage($product, $user), 404);
    }

    private function formView(Product $product, ProductTemplateService $templates): View
    {
        $types = ProductType::query()
            ->with([
                'category',
                'fields' => fn ($query) => $query->where('specification_fields.status', 'active')->orderBy('product_type_fields.sort_order')->orderBy('specification_fields.name'),
                'fields.options' => fn ($query) => $query->where('status', 'active')->orderBy('sort_order')->orderBy('label'),
                'fields.options.parentOptions' => fn ($query) => $query->select('specification_options.id')->where('specification_options.status', 'active'),
            ])
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.products.form', [
            'product' => $product,
            'brands' => Brand::query()->where('status', 'active')->orderBy('name')->get(),
            'lines' => ProductLine::query()->where('status', 'active')->orderBy('name')->get(),
            'types' => $types,
            'specValues' => $product->exists
                ? $product->specificationValues->mapWithKeys(fn ($value) => [$value->field_id => $value->value_text ?? $value->value_number ?? ($value->value_boolean === null ? null : (int) $value->value_boolean)])->all()
                : [],
            'specDetails' => $product->exists
                ? $product->specificationValues->mapWithKeys(fn ($value) => [$value->field_id => $value->value_detail])->all()
                : [],
            'specStructured' => $product->exists
                ? $product->specificationValues->mapWithKeys(fn ($value) => [$value->field_id => is_array($value->value_json) ? $value->value_json : []])->all()
                : [],
            'templateDefaults' => $types->mapWithKeys(fn (ProductType $type): array => [$type->id => $templates->defaults($type)])->all(),
            'templatePlaceholders' => $types->mapWithKeys(fn (ProductType $type): array => [$type->id => $templates->availablePlaceholders($type)])->all(),
            'currentCompleteness' => $product->exists ? (int) $product->completeness_percent : 0,
            'deletionBlockers' => $product->exists ? $this->deletions->blockers($product) : [],
        ]);
    }
}
