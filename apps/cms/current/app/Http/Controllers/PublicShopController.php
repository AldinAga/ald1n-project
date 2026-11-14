<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\PublicCartService;
use App\Services\PublicRetailCatalogService;
use App\Support\PublicProductImageFiles;
use App\Support\PublicProductImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'stock'=>['nullable','in:available'], 'sort'=>['nullable','in:name,newest,price_asc,price_desc'],
        ]);
        // Not validated with rules (a bad value falls back instead of failing):
        // both are reduced to whitelist members before anything uses them.
        $filters['per_page'] = \App\Support\PublicCatalogPresentation::perPage($request->query('per_page'));
        $filters['view'] = \App\Support\PublicCatalogPresentation::viewMode($request->query('view'));
        $page = $catalog->search($filters);
        // A page past the last one is not an empty listing: answer 404 so it
        // is never indexed as a soft-404 copy of the catalog.
        abort_if($page->currentPage() > max(1, $page->lastPage()), 404);
        $page->getCollection()->transform(static fn (Product $p) => $catalog->present($p)
            + ['photo' => PublicProductImages::card($p)]);
        $lists = $catalog->filters();
        return view('public-shop.catalog', [
            'page'=>$page,
            'filters'=>$lists,
            'seo'=>\App\Support\PublicCatalogPresentation::build($page, $filters, $lists, $catalog->available()->count()),
        ]);
    }

    public function show(string $slug, PublicRetailCatalogService $catalog): View
    {
        $this->guarded();
        $product = $catalog->bySlug($slug);
        $presented = $catalog->present($product);
        $gallery = PublicProductImages::gallery($product);
        return view('public-shop.show', [
            'product'=>$presented,
            'gallery'=>$gallery,
            'schemaJson'=>PublicProductImages::productSchemaJson($presented, $gallery),
            'specifications'=>$product->specificationValues
                ->filter(static fn ($v) => $v->field !== null && (string) ($v->value_text ?? '') !== '')
                ->map(static fn ($v) => [
                    'label' => (string) $v->field->name,
                    'value' => (string) $v->value_text,
                ])->values(),
        ]);
    }

    public function media(Request $request, ProductImage $image): BinaryFileResponse
    {
        $this->guarded();
        $product = $image->product()->firstOrFail();
        abort_unless(Product::query()->publiclyVisible()->where('price_amount','>',0)
            ->whereKey($product->id)->exists(), 404);

        // Optional WebP rendition (?r=thumbnail|display). Any other value,
        // or an array, is a 404; a rendition that does not exist (yet)
        // falls back to the gated original below.
        if ($request->query->has('r')) {
            $rendition = $request->query->all()['r'] ?? null;
            abort_unless(is_string($rendition) && in_array($rendition, PublicProductImageFiles::RENDITIONS, true), 404);
            $derived = PublicProductImageFiles::derivative($image, (int) $product->id, $rendition);
            if ($derived !== null && (mime_content_type($derived) ?: '') === 'image/webp') {
                return $this->imageFile($derived, 'image/webp');
            }
        }

        // B893 path boundary (legacy root, own products/{id} directory, no
        // symlink escape), unchanged, now in PublicProductImageFiles.
        $full = PublicProductImageFiles::original($image, (int) $product->id);
        abort_unless(is_string($full), 404);
        $mime=mime_content_type($full) ?: '';
        abort_unless(in_array($mime,['image/jpeg','image/png','image/webp'],true),404);

        return $this->imageFile($full, $mime);
    }

    private function imageFile(string $full, string $mime): BinaryFileResponse
    {
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
