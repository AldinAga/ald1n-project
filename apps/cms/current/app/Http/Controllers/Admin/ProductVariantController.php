<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductVariantRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\WarrantyRule;
use App\Services\CatalogAccessService;
use App\Services\ProductImageService;
use App\Services\ProductVariantService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class ProductVariantController extends Controller
{
    public function __construct(private readonly CatalogAccessService $catalogAccess) {}

    public function index(Product $product): View
    {
        $this->authorizeProduct($product);
        $product->load([
            'type.fields.options.parentOptions',
            'variants.specificationValues.field',
            'variants.images',
            'variants.warrantyRule',
        ]);
        return view('admin.products.variants', [
            'product' => $product,
            'warrantyRules' => WarrantyRule::query()->where('is_active', true)->where(function ($query) use ($product): void {
                $query->where('scope_type', 'global')->orWhere(function ($nested) use ($product): void {
                    $nested->where('scope_type', 'product')->where('product_id', $product->id);
                });
            })->orderByDesc('priority')->orderBy('name')->get(),
            'stockIdempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function store(ProductVariantRequest $request, Product $product, ProductVariantService $service): RedirectResponse
    {
        $this->authorizeProduct($product, $request);
        $variant = $service->create($product, $request->validated(), $request->user());
        return redirect()->route('admin.products.variants.index', $product)->with('status', 'Varijanta '.$variant->sku.' je kreirana.');
    }

    public function update(ProductVariantRequest $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
    {
        $this->authorizeProduct($product, $request);
        abort_unless((int) $variant->product_id === (int) $product->id, 404);
        $service->update($product, $variant, $request->validated(), $request->user());
        return back()->with('status', 'Varijanta je sačuvana.');
    }

    public function adjust(Request $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
    {
        $this->authorizeProduct($product, $request);
        abort_unless((int) $variant->product_id === (int) $product->id, 404);
        $data = $request->validate([
            'quantity_change' => ['required', 'integer', 'min:-1000000', 'max:1000000', 'not_in:0'],
            'note' => ['required', 'string', 'max:1000'],
            'idempotency_key' => ['required', 'string', 'max:200'],
        ]);
        $service->adjustStock($product, $variant, (int) $data['quantity_change'], (string) $data['note'], (string) $data['idempotency_key'], $request->user());
        return back()->with('status', 'Lager varijante je korigovan.');
    }

    public function setDefault(Request $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
    {
        $this->authorizeProduct($product, $request);
        abort_unless((int) $variant->product_id === (int) $product->id, 404);
        $service->setDefault($product, $variant, $request->user());
        return back()->with('status', 'Podrazumevana varijanta je promenjena.');
    }

    public function archive(Request $request, Product $product, ProductVariant $variant, ProductVariantService $service): RedirectResponse
    {
        $this->authorizeProduct($product, $request);
        abort_unless((int) $variant->product_id === (int) $product->id, 404);
        $service->archive($product, $variant, $request->user());
        return back()->with('status', 'Varijanta je arhivirana.');
    }

    public function images(Request $request, Product $product, ProductVariant $variant, ProductImageService $images): RedirectResponse
    {
        $this->authorizeProduct($product, $request);
        abort_unless((int) $variant->product_id === (int) $product->id, 404);
        $data = $request->validate(['images' => ['required', 'array', 'max:12'], 'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240']]);
        $count = $images->uploadVariant($product, $variant, $data['images']);
        return back()->with('status', 'Dodato slika varijante: '.$count.'.');
    }

    public function deleteImage(Product $product, ProductVariant $variant, ProductImage $image, ProductImageService $images): RedirectResponse
    {
        $this->authorizeProduct($product);
        abort_unless((int) $variant->product_id === (int) $product->id && (int) $image->product_variant_id === (int) $variant->id, 404);
        $images->delete($product, $image);
        return back()->with('status', 'Slika varijante je uklonjena.');
    }
    private function authorizeProduct(Product $product, ?Request $request = null): void
    {
        $user = ($request ?? request())->user();
        abort_unless($user !== null && $this->catalogAccess->canManage($product, $user), 404);
    }

}
