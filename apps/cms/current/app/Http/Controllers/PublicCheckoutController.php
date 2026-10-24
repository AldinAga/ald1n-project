<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OperationalNotificationService;
use App\Services\PublicCartService;
use App\Services\PublicCheckoutReadinessService;
use App\Services\PublicRetailCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Throwable;

final class PublicCheckoutController extends Controller
{
    private function requireRetailBuyer(Request $request): void
    {
        $actor=$request->user();
        // A public order is ONLY available to a signed-in, active Kupac.
        // Never turn a missing identity or staff account into an anonymous sale.
        if ($actor===null) {
            throw new \Illuminate\Auth\AuthenticationException(
                'Prijavi se ili registruj nalog Kupca pre poručivanja.', ['web'], route('login'));
        }
        abort_unless($actor->status==='active' && $actor->retired_at===null
            && $actor->hasRole('customer'),403);

        // Public checkout is outside the internal tracked-session route
        // group. It must enforce the same credential-rotation boundary, while
        // retaining the storefront OFF/404 check that runs before this method.
        $validSession=false;
        try {
            $validSession=app(\App\Services\PortalSessionService::class)
                ->validateAndTouch($request,$actor);
        } catch (Throwable $exception) {
            Log::warning('Registered buyer session verification failed closed.',[
                'user_id'=>$actor->getKey(),
                'exception'=>$exception::class,
            ]);
        }
        if (!$validSession) {
            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            throw new \Illuminate\Auth\AuthenticationException(
                'Prijava je istekla. Prijavite se ponovo.', ['web'], route('login')
            );
        }
    }

    public function form(Request $request, PublicCartService $cart, PublicCheckoutReadinessService $policy): View
    {
        // Preserve the storefront-wide OFF/404 contract for every visitor,
        // then enforce buyer-only authorization while it is available.
        $ready=$policy->assertReady();
        $this->requireRetailBuyer($request);
        $quote=$cart->quote($request);
        if (!$quote['items'] || $quote['stale']) {
            abort(409,'Korpa mora sadržati dostupne artikle pre poručivanja.');
        }
        $key=(string)$request->session()->get('ald1n.public_checkout.key','');
        $lastCompletedKey=(string)$request->session()->get('ald1n.public_checkout.last_key','');
        // A completed purchase keeps the original nonce for duplicate POST replay,
        // but visiting the checkout form with a NEW nonempty cart starts a new sale.
        if (strlen($key)<32 || ($lastCompletedKey!=='' && hash_equals($lastCompletedKey,$key))) {
            $key=bin2hex(random_bytes(24));
            $request->session()->put('ald1n.public_checkout.key',$key);
            $request->session()->forget([
                'ald1n.public_checkout.last_key',
                'ald1n.public_checkout.last_cart',
                'ald1n.public_checkout.last_token',
            ]);
        }
        return view('public-shop.checkout',[
            'ready'=>$ready,'quote'=>$quote,'checkoutKey'=>$key,
        ]);
    }

    public function store(
        Request $request,
        PublicRetailCheckoutService $checkout,
        PublicCheckoutReadinessService $policy,
        OperationalNotificationService $notifications,
    ): RedirectResponse {
        // OFF must stay a uniform 404 even for authenticated operators.
        $policy->assertReady();
        $this->requireRetailBuyer($request);
        $validated=$request->validate([
            'checkout_key'=>['required','string','size:48'],
            'merchant_policy_fingerprint'=>['required','regex:/^[a-f0-9]{64}$/D'],
            'shipping_full_name'=>['required','string','max:190'],
            'email'=>['required','email:rfc','max:254'],
            'shipping_phone'=>['required','string','min:6','max:40'],
            'shipping_address'=>['required','string','max:255'],
            'shipping_city'=>['required','string','max:120'],
            'shipping_postal_code'=>['required','string','max:20'],
            'payment_method'=>['required',\Illuminate\Validation\Rule::in(array_keys(\App\Support\PublicRetailOptions::PAYMENT))],
            'delivery_method'=>['required',\Illuminate\Validation\Rule::in(array_keys(\App\Support\PublicRetailOptions::DELIVERY))],
            'customer_note'=>['nullable','string','max:2000'],
            'accepted_terms'=>['accepted'],
            'website'=>['nullable','size:0'],
        ]);
        // An operator may have approved NEW terms since this customer
        // opened the form. Never accept agreement to a stale policy.
        $current=$policy->assertReady();
        if (!hash_equals($current['approval_digest'],
            (string)$validated['merchant_policy_fingerprint'])) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'checkout'=>'Uslovi prodaje ili dostave su promenjeni. Osveži poručivanje.',
            ]);
        }
        $result=$checkout->place($request,$validated);
        $order=$result['order'];
        $token=$result['token'];
        if ($result['created']) {
            try {
                // Mail links carry a private capability: never derive their origin
                // from Host/Forwarded headers supplied by the requesting client.
                $receiptPath=route('public-shop.order.show',['token'=>$token],false);
                $link=rtrim((string)config('app.url'),'/').$receiptPath;
                $body="Vaša porudžbina ".$order->order_number." je primljena.\n"
                    ."Ukupna vrednost artikala: ".number_format((float)$order->subtotal_rsd,2,',','.')." RSD.\n"
                    ."Status: nova, plaćanje još nije potvrđeno.\n"
                    ."Privatni link za pregled porudžbine, dokumenta i pošiljke:\n".$link."\n"
                    ."Prijavite se na svoj potvrđeni nalog Kupca za pregled.\\n"
                    ."Nemojte deliti ovu vezu. Ističe za 60 dana.\\n";
                if ($order->payment_method==='bank_transfer') {
                    $body.="Podaci za uplatu: ".$order->bank_account_number_display_snapshot."\n"
                        ."Primalac: ".$order->payment_recipient_name_snapshot."\n"
                        ."Poziv na broj: ".$order->payment_reference_snapshot."\n"
                        ."Uplatu potvrđuje prodavac, ne ova poruka.\n";
                }
                Mail::raw($body, static function ($mail) use($order): void {
                    // Receipt destination is the authenticated buyer's
                    // verified profile, never an arbitrary form-supplied email.
                    $mail->to(mb_strtolower(trim((string)$order->user?->email)))
                        ->subject('Potvrda porudžbine '.$order->order_number);
                });
            } catch (Throwable $e) {
                // Never undo a committed sale or disclose bearer URLs in logs.
                Log::warning('Public checkout confirmation email was not delivered.',[
                    'order_id'=>$order->id,'exception'=>$e::class,
                ]);
            }
            try {
                $order->loadMissing('supplier');
                if ($order->supplier !== null) {
                    $notifications->order(
                        $order->supplier,'order.created_for_supplier',
                        'Nova javna porudžbina',
                        'Primljena je javna porudžbina '.$order->order_number.'.',
                        $order,['severity'=>'warning','url'=>rtrim((string)config('app.url'),'/')
                            .route('admin.orders.show',$order,false)],
                    );
                }
            } catch (Throwable $e) {
                Log::warning('Public checkout operator notification failed.',[
                    'order_id'=>$order->id,'exception'=>$e::class,
                ]);
            }
        }
        // Use an internally generated relative Location; Redirector::to()
        // would turn it back into an absolute URL using the untrusted Host.
        return (new RedirectResponse(route('public-shop.order.show',['token'=>$token],false)))
            ->setSession($request->session())
            ->with('status','Porudžbina je primljena. Privatna veza zahteva prijavu na Vaš nalog Kupca.');
    }
}
