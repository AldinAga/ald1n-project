<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\PublicCartService;
use App\Services\PublicRetailCatalogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class PublicShopController extends Controller
{
    private function guarded(): void
    {
        abort_unless(app(\App\Services\PublicStorefrontStateService::class)->isOpen(), 404);
    }

    public function catalog(Request $request, PublicRetailCatalogService $catalog): View
    {
        $this->guarded();
        $filters = $request->validate([
            'q' => ['nullable','string','max:80'],
            'brand_id'=>['nullable','integer','min:1'], 'category_id'=>['nullable','integer','min:1'],
            'stock'=>['nullable','in:available'], 'sort'=>['nullable','in:name,newest'],
        ]);
        $page = $catalog->search($filters);
        $page->getCollection()->transform(static fn (Product $p) => $catalog->present($p));
        return view('public-shop.catalog', ['page'=>$page, 'filters'=>$catalog->filters()]);
    }

    public function show(string $slug, PublicRetailCatalogService $catalog): View
    {
        $this->guarded();
        $product = $catalog->bySlug($slug);
        return view('public-shop.show', [
            'product'=>$catalog->present($product),
            'specifications'=>$product->specificationValues
                ->filter(static fn ($v) => $v->field !== null && (string) ($v->value_text ?? '') !== '')
                ->map(static fn ($v) => [
                    'label' => (string) $v->field->name,
                    'value' => (string) $v->value_text,
                ])->values(),
        ]);
    }

    public function media(ProductImage $image): BinaryFileResponse
    {
        $this->guarded();
        $product = $image->product()->firstOrFail();
        abort_unless(Product::query()->publiclyVisible()->where('price_amount','>',0)
            ->whereKey($product->id)->exists(), 404);

        $disk = (string) $image->storage_disk;
        $relative = ltrim(str_replace('\\','/',trim((string) $image->file_path)), '/');
        abort_if($relative === '' || in_array('..',explode('/',$relative),true),404);
        if ($disk === 'legacy') {
            abort_unless(str_starts_with($relative,'uploads/products/'),404);
            $configuredRoot = trim((string) config('services.legacy_media.root'));
            $root = $configuredRoot !== '' ? realpath($configuredRoot) : false;
            $allowed = is_string($root) ? realpath($root.'/uploads/products') : false;
            $full = is_string($root) ? realpath($root.'/'.$relative) : false;
        } elseif ($disk === 'public') {
            abort_unless(str_starts_with($relative,'products/'.(int) $product->id.'/'),404);
            $root = realpath(Storage::disk('public')->path(''));
            $allowed = is_string($root) ? realpath($root.'/products/'.(int)$product->id) : false;
            $full = is_string($root) ? realpath($root.'/'.$relative) : false;
        } else abort(404);
        abort_unless(is_string($allowed)&&is_string($full)&&is_file($full)
            &&str_starts_with($full,rtrim($allowed,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR),404);
        $mime=mime_content_type($full) ?: '';
        abort_unless(in_array($mime,['image/jpeg','image/png','image/webp'],true),404);
        return response()->file($full, [
            'Content-Type'=>$mime,'Cache-Control'=>'public, max-age=3600',
            'X-Content-Type-Options'=>'nosniff',
        ]);
    }

    public function cart(Request $request, PublicCartService $cart): View
    {
        $this->guarded();
        return view('public-shop.cart',['quote'=>$cart->quote($request)]);
    }

    public function add(Request $request, Product $product, PublicCartService $cart): RedirectResponse
    {
        $this->guarded();
        $quantity = (int) $request->validate(['quantity'=>['required','integer','min:1','max:20']])['quantity'];
        $current = $cart->get($request);
        $cart->change($request, (int)$product->id, ($current[(int)$product->id] ?? 0)+$quantity);
        return redirect()->route('public-shop.cart')->with('status','Artikal je dodat u korpu.');
    }

    public function update(Request $request, Product $product, PublicCartService $cart): RedirectResponse
    {
        $this->guarded();
        $quantity=(int)$request->validate(['quantity'=>['required','integer','min:0','max:20']])['quantity'];
        $cart->change($request,(int)$product->id,$quantity);
        return redirect()->route('public-shop.cart');
    }

    public function remove(Request $request, Product $product, PublicCartService $cart): RedirectResponse
    {
        $this->guarded();
        $cart->change($request,(int)$product->id,0);
        return redirect()->route('public-shop.cart');
    }
}
