<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\CatalogAccessService;
use App\Services\ProductImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

final class ProductImageController extends Controller
{
    public function __construct(private readonly CatalogAccessService $catalogAccess) {}

    public function index(Product $product): View
    {
        $this->authorizeProduct($product);

        return view('admin.products.images', ['product' => $product->load('images')]);
    }

    public function store(Request $request, Product $product, ProductImageService $service): RedirectResponse|JsonResponse
    {
        $this->authorizeProduct($product, $request);
        $validated = $request->validate([
            'images' => ['required', 'array', 'max:20'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $count = $service->upload($product, $validated['images']);
        $message = 'Dodato slika: '.$count.'.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'count' => $count,
                'reload' => true,
            ]);
        }

        return back()->with('status', $message);
    }

    public function primary(Request $request, Product $product, ProductImage $image, ProductImageService $service): RedirectResponse|JsonResponse
    {
        $this->authorizeProduct($product, $request);
        abort_unless((int) $image->product_id === (int) $product->id, 404);
        $service->setPrimary($product, $image);
        $message = 'Glavna slika je promenjena.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'image_id' => (int) $image->id,
                'reload' => true,
            ]);
        }

        return back()->with('status', $message);
    }

    public function rotate(Request $request, Product $product, ProductImage $image, ProductImageService $service): RedirectResponse|JsonResponse
    {
        $this->authorizeProduct($product, $request);
        abort_unless((int) $image->product_id === (int) $product->id, 404);
        $degrees = (int) $request->validate(['degrees' => ['required', 'integer', 'in:90,180,270']])['degrees'];
        try {
            $wasLegacy = $image->storage_disk === 'legacy';
            $service->rotate($product, $image, $degrees);
            $image->refresh();
            $message = 'Slika je rotirana za '.$degrees.'°'.($wasLegacy ? ' i sačuvana kao lokalna kopija.' : '.');

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'image' => [
                        'id' => (int) $image->id,
                        'url' => $image->url,
                        'download_url' => $image->download_url,
                        'rotation_degrees' => (int) $image->rotation_degrees,
                        'storage_disk' => (string) $image->storage_disk,
                    ],
                    'reload' => $wasLegacy,
                ]);
            }

            return back()->with('status', $message);
        } catch (Throwable $exception) {
            try {
                Log::warning('Rotacija slike artikla nije uspela.', [
                    'product_id' => $product->id,
                    'image_id' => $image->id,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            } catch (Throwable) {
                // Logging ne sme proizvesti novi HTTP 500.
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => ['images' => [$exception->getMessage()]],
                ], 422);
            }

            return back()->withErrors(['images' => $exception->getMessage()]);
        }
    }

    public function reorder(Request $request, Product $product, ProductImageService $service): RedirectResponse|JsonResponse
    {
        $this->authorizeProduct($product, $request);
        if ($request->has('image_ids')) {
            $validated = $request->validate([
                'image_ids' => ['required', 'array'],
                'image_ids.*' => ['integer', 'min:1'],
            ]);
            $orderedIds = array_map('intval', $validated['image_ids']);
        } else {
            $orders = $request->validate([
                'orders' => ['required', 'array'],
                'orders.*' => ['integer', 'min:0', 'max:1000000'],
            ])['orders'];
            asort($orders, SORT_NUMERIC);
            $orderedIds = array_map('intval', array_keys($orders));
        }

        $service->reorder($product, $orderedIds);
        $message = 'Redosled slika je sačuvan.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'image_ids' => $orderedIds,
            ]);
        }

        return back()->with('status', $message);
    }

    public function destroy(Request $request, Product $product, ProductImage $image, ProductImageService $service): RedirectResponse|JsonResponse
    {
        $this->authorizeProduct($product, $request);
        abort_unless((int) $image->product_id === (int) $product->id, 404);
        $service->delete($product, $image);
        $message = 'Slika je uklonjena.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'reload' => true]);
        }

        return back()->with('status', $message);
    }

    private function authorizeProduct(Product $product, ?Request $request = null): void
    {
        $user = ($request ?? request())->user();
        abort_unless($user !== null && $this->catalogAccess->canManageImages($product, $user), 404);
    }
}
