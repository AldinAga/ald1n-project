<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\PublicCheckoutAttempt;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Registered Kupac retail checkout uses real operational orders and stock.
 * No anonymous sales, no staff identity laundering and no commission.
 */
final class PublicRetailCheckoutService
{
    public function __construct(
        private readonly PublicRetailCatalogService $catalog,
        private readonly PublicCartService $cart,
        private readonly PublicCheckoutReadinessService $readiness,
        private readonly SettingsService $settings,
        private readonly PublicOrderAccessService $access,
    ) {}

    /** @param array<string,mixed> $input
     *  @return array{order:Order,token:string,created:bool}
     */
    public function place(Request $request, array $input): array
    {
        $policies=$this->readiness->assertReady();
        $actor=$request->user();
        // Domain guard independently blocks direct anonymous/internal callers.
        // Controller/session authorization is never sufficient for stock writes.
        abort_unless($actor instanceof User,401);
        abort_unless($actor->status==='active' && $actor->retired_at===null
            && $actor->hasRole('customer'),403);
        $buyerId=(int)$actor->id;
        $submittedEmail=mb_strtolower(trim((string)($input['email']??'')));
        if (!hash_equals(mb_strtolower(trim((string)$actor->email)),$submittedEmail)) {
            throw ValidationException::withMessages([
                'email'=>'Koristi email adresu potvrđenu na svom nalogu Kupca.',
            ]);
        }
        $delivery=(string)($input['delivery_method']??'');
        if (!in_array($delivery,$policies['delivery_methods'],true)) {
            throw ValidationException::withMessages(['delivery_method'=>'Izaberi dozvoljeni nacin dostave.']);
        }
        $submittedPolicy=(string)($input['merchant_policy_fingerprint']??'');
        if (strlen($submittedPolicy)!==64 || !hash_equals($policies['approval_digest'],$submittedPolicy)) {
            throw ValidationException::withMessages(['checkout'=>
                'Uslovi prodaje su promenjeni. Osveži poručivanje i proveri uslove.']);
        }
        $method=(string)($input['payment_method'] ?? '');
        if (!in_array($method,$policies['methods'],true)) {
            throw ValidationException::withMessages(['payment_method'=>'Nedozvoljen način plaćanja.']);
        }
        // In-person collection is distinct from a courier's COD service.
        // It remains only an unpaid preference until the seller records funds.
        if ($method==='cash' && !in_array($delivery,['customer_pickup','own_transport'],true)) {
            throw ValidationException::withMessages(['payment_method'=>
                'Placanje pri preuzimanju/dostavi je dostupno samo uz licno preuzimanje ili sopstvenu dostavu.']);
        }
        $submittedKey=(string)($input['checkout_key'] ?? '');
        $sessionKey=(string)$request->session()->get('ald1n.public_checkout.key','');
        if (strlen($submittedKey)<32 || strlen($sessionKey)<32 || !hash_equals($sessionKey,$submittedKey)) {
            throw ValidationException::withMessages(['checkout'=>'Obnovi stranicu poručivanja i pokušaj ponovo.']);
        }

        $cart=$this->cart->get($request);
        if ($cart===[] && (string)$request->session()->get('ald1n.public_checkout.last_key','')===$sessionKey) {
            $stored=$request->session()->get('ald1n.public_checkout.last_cart',[]);
            if (is_array($stored)) $cart=$stored;
        }
        if ($cart===[] || count($cart)>25) {
            throw ValidationException::withMessages(['cart'=>'Korpa je prazna ili premašuje dozvoljeni broj artikala.']);
        }
        ksort($cart,SORT_NUMERIC);
        $name=trim((string)$input['shipping_full_name']);
        $email=mb_strtolower(trim((string)$input['email']));
        $details=[
            'name'=>$name,'email'=>$email,
            'address'=>trim((string)$input['shipping_address']),
            'city'=>trim((string)$input['shipping_city']),
            'postal'=>trim((string)$input['shipping_postal_code']),
            'phone'=>trim((string)$input['shipping_phone']),
            'note'=>trim((string)($input['customer_note']??'')),
            'method'=>$method,'delivery_method'=>$delivery,'buyer_id'=>$buyerId,'items'=>$cart,
        ];
        // The fingerprint never persists PII in readable form.
        $fingerprint=hash_hmac('sha256',
            json_encode($details,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),
            (string)config('app.key'));
        // The signed random form nonce already uniquely identifies the checkout.
        // A session driver may rotate its ID between requests; do not rebook stock.
        $keyHash=hash_hmac('sha256',$sessionKey,
            (string)config('app.key'));

        $created=false;
        $result=DB::transaction(function () use ($keyHash,$fingerprint,$cart,$details,
                $method,$policies,$email,$name,$buyerId,$delivery,&$created): array {
            // Shared gate lock allows concurrent buyers, but serializes an OFF
            // action against all committing checkouts. No stale settings cache.
            abort_unless(app(PublicStorefrontStateService::class)->isOpen(true),404);
            // Recheck the real registered buyer under DB lock immediately
            // before creating any attempt/order or decrementing inventory.
            $buyer=User::query()->whereKey($buyerId)->sharedLock()->firstOrFail();
            abort_unless($buyer->status==='active' && $buyer->retired_at===null
                && $buyer->hasRole('customer'),403);
            if (!hash_equals(mb_strtolower(trim((string)$buyer->email)),$email)) {
                throw ValidationException::withMessages(['email'=>'Email adresa kupca je promenjena.']);
            }
            // Atomic duplicate-key upsert is essential under MariaDB REPEATABLE READ:
            // firstOrCreate() may perform a stale consistent read when two requests
            // race on the same nonce. Preserve the FIRST immutable fingerprint and
            // take a fresh current/locking read after the unique-key wait resolves.
            $now=now();
            DB::table('public_checkout_attempts')->upsert(
                [[
                    'key_hash'=>$keyHash,
                    'request_fingerprint'=>$fingerprint,
                    'created_at'=>$now,
                    'updated_at'=>$now,
                ]],
                ['key_hash'],
                ['key_hash']
            );
            $attempt=PublicCheckoutAttempt::query()->where('key_hash',$keyHash)
                ->lockForUpdate()->firstOrFail();
            if (!hash_equals((string)$attempt->request_fingerprint,$fingerprint)) {
                throw ValidationException::withMessages(['checkout'=>'Isti zahtev ne može promeniti sadržaj porudžbine.']);
            }
            if ($attempt->order_id!==null) {
                $order=Order::query()->findOrFail($attempt->order_id);
                // The same nonce is NOT a license to retrieve someone else's
                // private receipt after login or account reassignment.
                abort_unless((int)$order->user_id===$buyerId
                    && $order->sales_channel==='public_retail',403);
                $cipher=(string)$attempt->receipt_token_ciphertext;
                if ($cipher==='') throw ValidationException::withMessages(['checkout'=>'Kontaktiraj podršku za proveru ranije porudžbine.']);
                return ['order'=>$order,'token'=>Crypt::decryptString($cipher),'created'=>false];
            }

            $supplier=User::query()->where('status','active')->whereNull('retired_at')
                ->whereHas('role',static fn ($q)=>$q->whereIn('slug',['superadmin','admin']))
                ->with('role')->orderBy('id')->get()
                ->sortBy(static fn (User $u): int=>$u->hasRole('superadmin')?0:1)->first();
            if (!$supplier instanceof User) {
                throw ValidationException::withMessages(['checkout'=>'Trenutno nije dostupan operater porudžbina.']);
            }
            $productIds=array_keys($cart);
            $products=Product::query()->publiclyVisible()->where('price_amount','>',0)
                ->whereIn('id',$productIds)->with(['brand','line','type'])
                ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($products->count()!==count($cart)) {
                throw ValidationException::withMessages(['cart'=>'Jedan artikal je povučen iz prodaje.']);
            }

            $account=$method==='bank_transfer'?$policies['bank_account']:null;
            if ($method==='bank_transfer' && $account===null) {
                throw ValidationException::withMessages(['payment_method'=>'Žiro račun nije aktivan.']);
            }
            $rate=$this->settings->eurRsdRate();
            $order=Order::query()->create([
                'source_system'=>'laravel','sales_channel'=>'public_retail',
                'order_number'=>'TMP-'.bin2hex(random_bytes(12)),
                'idempotency_key_hash'=>$keyHash,
                'request_fingerprint'=>$fingerprint,
                'user_id'=>$buyerId,'supplier_user_id'=>$supplier->id,
                'supplier_name_snapshot'=>$supplier->displayName(),
                'supplier_email_snapshot'=>$supplier->email,
                'supplier_phone_snapshot'=>$supplier->phone,
                'supplier_role_snapshot'=>$supplier->roleName(),
                'assigned_at'=>now(),'status'=>'new',
                'inventory_state'=>'reserved','inventory_reserved_at'=>now(),
                'shipping_full_name'=>$name,
                'shipping_address'=>$details['address'],
                'shipping_city'=>$details['city'],
                'shipping_postal_code'=>$details['postal'],
                'shipping_phone'=>$details['phone'],
                'subtotal_rsd'=>0,'eur_rsd_rate'=>$rate,
                'customer_note'=>$details['note']!==''?$details['note']:null,
                'payment_method'=>$method,'payment_status'=>'pending',
                'payment_state'=>'unpaid','paid_total_rsd'=>0,
                // Retail buyers never receive deferred credit. Internal SubAgent
                // and direct-sale deferred workflows are deliberately unchanged.
                'payment_due_at'=>null,
                'bank_account_id'=>$account?->id,
                'bank_account_label_snapshot'=>$account?->label,
                'bank_account_number_snapshot'=>$account?->account_number,
                'bank_account_number_display_snapshot'=>$account?->account_number_display,
                'payment_recipient_name_snapshot'=>$account?->recipient_name,
                'payment_recipient_address_snapshot'=>$account?->recipient_address,
                'payment_code_snapshot'=>$account?->payment_code,
                'updated_by'=>$supplier->id,
            ]);
            $number=sprintf('APC-%s-%08d',now()->format('Ymd'),$order->id);
            $order->update([
                'order_number'=>$number,
                'payment_purpose_snapshot'=>$account?'Plaćanje porudžbine':null,
                'payment_reference_snapshot'=>$account?(string)$order->id:null,
            ]);
            $total=0.0;
            foreach ($cart as $id=>$quantity) {
                $product=$products->get((int)$id);
                if (!$product instanceof Product || $quantity<1 || $quantity>20) {
                    throw ValidationException::withMessages(['cart'=>'Nedozvoljeni artikal ili količina.']);
                }
                $unit=$this->catalog->requirePurchasable($product,$quantity);
                $line=round($unit*$quantity,2);
                $cost=(float)($product->purchase_price_rsd??0);
                $item=$order->items()->create([
                    'product_id'=>$product->id,
                    'product_sku'=>$product->sku,
                    'product_name'=>$product->name,
                    'quantity'=>$quantity,
                    'unit_price_original'=>$product->price_amount,
                    'original_currency'=>$product->price_currency,
                    'unit_price_rsd'=>$unit,
                    'line_total_rsd'=>$line,
                    'purchase_unit_rsd_snapshot'=>$cost>0?round($cost,2):null,
                    'purchase_total_rsd_snapshot'=>$cost>0?round($cost*$quantity,2):null,
                    'cost_source_snapshot'=>$cost>0?'product':'missing',
                    'brand_name_snapshot'=>$product->brand?->name,
                    'product_line_name_snapshot'=>$product->line?->name,
                    'product_type_name_snapshot'=>$product->type?->name,
                    // Existing enum's no-commission marker; the REAL channel is public_retail.
                    'commission_source_snapshot'=>'direct_sale',
                    'commission_rate_percent_snapshot'=>0,
                    'commission_unit_eur_snapshot'=>0,
                    'commission_total_eur_snapshot'=>0,
                ]);
                $before=(int)$product->stock_quantity;
                $after=$before-$quantity;
                $product->forceFill(['stock_quantity'=>$after,'updated_by'=>$supplier->id])->save();
                StockMovement::query()->create([
                    'event_key'=>sprintf('order:%d:item:%d:sale',$order->id,$item->id),
                    'product_id'=>$product->id,'order_id'=>$order->id,
                    'user_id'=>null,'movement_type'=>'sale','source'=>'order',
                    'quantity_change'=>-$quantity,'quantity_before'=>$before,
                    'quantity_after'=>$after,
                    'note'=>'Javna porudžbina '.$number,
                    'metadata_json'=>['source_system'=>'laravel',
                        'sales_channel'=>'public_retail','order_item_id'=>$item->id],
                ]);
                $total=round($total+$line,2);
            }
            $order->update(['subtotal_rsd'=>$total]);
            DB::table('order_status_history')->insert([
                'order_id'=>$order->id,
                'old_status'=>null,'new_status'=>'new','changed_by'=>null,
                'note'=>'Javna retail porudžbina registrovanog Kupca.',
                'created_at'=>now(),
            ]);
            $token=$this->access->issue($order,$email);
            $attempt->update([
                'order_id'=>$order->id,
                'receipt_token_ciphertext'=>Crypt::encryptString($token),
                'terms_url'=>$policies['terms_url'],
                'privacy_url'=>$policies['privacy_url'],
                'shipping_description'=>$policies['shipping_description'],
                'shipping_fee_rsd'=>$policies['shipping_fee_rsd'],
                'merchant_policy_digest'=>$policies['approval_digest'],
                'delivery_method'=>$delivery,
                'customer_user_id'=>$buyerId,
                'payment_due_at_snapshot'=>$order->payment_due_at?->toDateString(),
                'terms_accepted_at'=>now(),
            ]);
            $created=true;
            return ['order'=>$order,'token'=>$token,'created'=>true];
        },5);

        $request->session()->put('ald1n.public_checkout.last_key',$sessionKey);
        $request->session()->put('ald1n.public_checkout.last_cart',$cart);
        $request->session()->put('ald1n.public_checkout.last_token',$result['token']);
        $this->cart->clear($request);
        return $result;
    }
}
