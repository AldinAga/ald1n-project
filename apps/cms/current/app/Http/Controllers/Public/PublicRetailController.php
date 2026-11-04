<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\OrderDocument;
use App\Models\Product;
use App\Models\PublicOrderAccess;
use App\Services\OrderDocumentService;
use App\Services\PublicRetailService;
use App\Services\SettingsService;
use App\Support\TrackingUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

final class PublicRetailController extends Controller
{
    public function index(Request $request, PublicRetailService $retail): View
    {
        $retail->requireEnabled();
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 100);
        $query = $retail->publicProducts()->with(['presentationImage', 'brand:id,name']);
        if ($search !== '') {
            $escaped = addcslashes($search, '\\%_');
            $query->where(static fn ($q) => $q->where('name', 'like', '%'.$escaped.'%')
                ->orWhere('sku', 'like', '%'.$escaped.'%')
                ->orWhere('model_name', 'like', '%'.$escaped.'%'));
        }
        $category = trim((string) $request->query('category', ''));
        if ($category !== '' && preg_match('/^[a-z0-9-]{1,100}$/D', $category)) {
            $query->whereHas('categories', static fn ($q) => $q->where('slug', $category)->where('status', 'active'));
        }
        return view('public.catalog', [
            'products' => $query->orderBy('name')->paginate(24)->withQueryString(),
            'search' => $search,
            'category' => $category,
            'cartCount' => array_sum($retail->normalizeCart($request->session()->get(PublicRetailService::CART_KEY, []))),
        ]);
    }

    public function product(Request $request, string $slug, PublicRetailService $retail): View
    {
        $retail->requireEnabled();
        $product = $retail->publicProducts()
            ->with(['images', 'brand:id,name', 'categories:id,name', 'specificationValues.field'])
            ->where('slug', $slug)->firstOrFail();
        return view('public.product', [
            'product' => $product,
            'unitCents' => $retail->retailUnitCents($product),
            'cartCount' => array_sum($retail->normalizeCart($request->session()->get(PublicRetailService::CART_KEY, []))),
        ]);
    }

    public function add(Request $request, Product $product, PublicRetailService $retail): RedirectResponse
    {
        $retail->requireEnabled();
        abort_unless($retail->publicProducts()->whereKey($product->id)->exists(), 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:10']]);
        $cart = $retail->normalizeCart($request->session()->get(PublicRetailService::CART_KEY, []));
        if (!isset($cart[$product->id]) && count($cart) >= (int) config('public_retail.max_distinct_items', 20)) {
            return back()->withErrors(['cart' => 'Korpa je popunjena.']);
        }
        $cart[$product->id] = min((int) config('public_retail.max_quantity_per_item', 10), ($cart[$product->id] ?? 0) + (int) $data['quantity']);
        $request->session()->put(PublicRetailService::CART_KEY, $cart);
        $request->session()->forget('public_retail_checkout_key');
        return redirect()->route('public.cart')->with('status', 'Artikal je dodat u korpu.');
    }

    public function cart(Request $request, PublicRetailService $retail): View
    {
        $retail->requireEnabled();
        return view('public.cart', ['preview' => $retail->preview((array) $request->session()->get(PublicRetailService::CART_KEY, []))]);
    }

    public function updateCart(Request $request, PublicRetailService $retail): RedirectResponse
    {
        $retail->requireEnabled();
        $data = $request->validate(['product_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:0', 'max:10']]);
        $cart = $retail->normalizeCart($request->session()->get(PublicRetailService::CART_KEY, []));
        $id = (int) $data['product_id'];
        if ((int) $data['quantity'] < 1) unset($cart[$id]);
        elseif (array_key_exists($id, $cart)) $cart[$id] = (int) $data['quantity'];
        $request->session()->put(PublicRetailService::CART_KEY, $cart);
        $request->session()->forget('public_retail_checkout_key');
        return redirect()->route('public.cart');
    }

    public function checkout(Request $request, PublicRetailService $retail, SettingsService $settings): View
    {
        $retail->assertMerchantReady();
        $preview = $retail->preview((array) $request->session()->get(PublicRetailService::CART_KEY, []));
        if ($preview['lines'] === []) abort(404);
        if (!$request->session()->has('public_retail_checkout_key')) {
            $request->session()->put('public_retail_checkout_key', bin2hex(random_bytes(32)));
        }
        return view('public.checkout', [
            'preview' => $preview,
            'key' => $request->session()->get('public_retail_checkout_key'),
            'company' => (string) $settings->get('documents_company_name'),
            'terms' => (string) $settings->get('public_retail_terms_text'),
            'hasBankTransfer' => BankAccount::query()->where('is_active', true)->whereNotNull('account_number')->exists(),
        ]);
    }

    public function place(Request $request, PublicRetailService $retail): RedirectResponse
    {
        $retail->assertMerchantReady();
        $input = $request->validate([
            'checkout_key' => ['required', 'string', 'regex:/^[a-f0-9]{64}$/D'],
            'expected_total_cents' => ['required', 'regex:/^[0-9]{1,14}$/D'],
            'shipping_full_name' => ['required', 'string', 'min:3', 'max:190'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'shipping_phone' => ['required', 'string', 'min:6', 'max:50'],
            'shipping_address' => ['required', 'string', 'min:3', 'max:255'],
            'shipping_city' => ['required', 'string', 'min:2', 'max:120'],
            'shipping_postal_code' => ['required', 'string', 'min:4', 'max:25'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'payment_method' => ['required', 'in:cash_on_delivery,bank_transfer'],
            'terms_accepted' => ['accepted'],
        ]);
        if (!hash_equals((string) $request->session()->get('public_retail_checkout_key', ''), (string) $input['checkout_key'])) {
            throw ValidationException::withMessages(['checkout' => 'Porudžbina je već obrađena ili je sesija promenjena.']);
        }
        $created = $retail->checkout((array) $request->session()->get(PublicRetailService::CART_KEY, []), $input, (string) $input['checkout_key']);
        $request->session()->forget([PublicRetailService::CART_KEY, 'public_retail_checkout_key']);
        return redirect()->route('public.order.show', ['token' => $created['token']]);
    }

    public function order(string $token, PublicRetailService $retail): Response
    {
        $record = $retail->authorizeAccess($token);
        $order = $record->order;
        $shipment = $order->shipment;
        $tracking = TrackingUrl::safe($shipment?->courier_tracking_url_snapshot);
        return response()->view('public.order', [
            'order' => $order, 'token' => $token, 'trackingUrl' => $tracking,
            'shipment' => $shipment,
            'documents' => $order->documents->filter(static fn ($doc) => $doc->status === 'issued'
                && in_array($doc->document_type, ['order_confirmation', 'proforma', 'invoice', 'delivery_note'], true)),
        ])->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function document(string $token, OrderDocument $document, PublicRetailService $retail, OrderDocumentService $pdf): Response
    {
        $record = $retail->authorizeAccess($token);
        abort_unless($document->order_id === $record->order_id && $document->status === 'issued'
            && in_array($document->document_type, ['order_confirmation', 'proforma', 'invoice', 'delivery_note'], true), 404);
        $bytes = $pdf->render($document);
        return response($bytes, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="potvrda-'.(int) $document->id.'.pdf"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'Referrer-Policy' => 'no-referrer',
            'X-Content-Type-Options' => 'nosniff',
            'X-Robots-Tag' => 'noindex, nofollow, noarchive',
        ]);
    }

    public function revoke(Request $request, string $token, PublicRetailService $retail): RedirectResponse
    {
        $access = $retail->authorizeAccess($token);
        DB::transaction(static fn () => PublicOrderAccess::query()
            ->whereKey($access->id)->whereNull('revoked_at')->update(['revoked_at' => now()]));
        return redirect()->route('public.catalog')->with('status', 'Privatni link je opozvan.');
    }
}
