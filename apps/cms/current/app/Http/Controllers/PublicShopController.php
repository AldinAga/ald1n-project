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
        // B893: realpath() on the allowed directory may itself resolve a
        // symbolic link outside the approved root. The existing leaf-path
        // check alone would then serve images from that external directory.
        abort_unless(is_string($root) && is_string($allowed),404);
        $rootPrefix=rtrim($root,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        abort_unless(str_starts_with($allowed,$rootPrefix),404);
        if ($disk === 'public') {
            // A public product directory cannot alias another product's
            // real directory even if both happen to reside on this disk.
            $expected=$rootPrefix.'products'.DIRECTORY_SEPARATOR.(int)$product->id;
            abort_unless(hash_equals($expected,$allowed),404);
        }
        abort_unless(is_string($full)&&is_file($full)
            &&str_starts_with($full,rtrim($allowed,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR),404);
        $mime=mime_content_type($full) ?: '';
        abort_unless(in_array($mime,['image/jpeg','image/png','image/webp'],true),404);
        return response()->file($full, [
            'Content-Type'=>$mime,
            // B877: emergency OFF must revoke dynamically gated media for
            // browser/intermediary caches, not merely for fresh origin GETs.
            'Cache-Control'=>'private, no-store, max-age=0',
            'Pragma'=>'no-cache',
            'X-Robots-Tag'=>'noindex, nofollow, noarchive',
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
        // A cart POST must never produce an absolute Location from an
        // untrusted Host / X-Forwarded-Host header.
        return (new RedirectResponse(route('public-shop.cart',[],false)))
            ->setSession($request->session())
            ->with('status','Artikal je dodat u korpu.');
    }

    public function update(Request $request, Product $product, PublicCartService $cart): RedirectResponse
    {
        $this->guarded();
        $quantity=(int)$request->validate(['quantity'=>['required','integer','min:0','max:20']])['quantity'];
        $cart->change($request,(int)$product->id,$quantity);
        return new RedirectResponse(route('public-shop.cart',[],false));
    }

    public function remove(Request $request, Product $product, PublicCartService $cart): RedirectResponse
    {
        $this->guarded();
        $cart->change($request,(int)$product->id,0);
        return new RedirectResponse(route('public-shop.cart',[],false));
    }
}
