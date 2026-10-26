<?php
declare(strict_types=1);

/**
 * B812: browser-only synthetic fixture. The --mock-delivery option replaces a
 * pending account's already-created one-time token with a known synthetic token,
 * because this disposable CI intentionally uses Laravel's array mail transport.
 * It is NOT a test of SMTP delivery or a production activation procedure.
 */
require dirname(__DIR__,2).'/vendor/autoload.php';
$app=require dirname(__DIR__,2).'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{Order, Product, PublicCheckoutAttempt, PublicOrderAccess, Role, StockMovement, User, UserActivationToken};
use App\Services\{PublicMerchantPolicyService, PublicRegistrationMailSenderService, SettingsService};
use Illuminate\Support\Facades\{DB,Hash};

$mode=$argv[1]??'';
$buyerEmail='b812-browser-buyer@example.test';
$plain='B812'.str_repeat('x',76);
$connection=DB::connection();
$version=(string)$connection->selectOne('SELECT VERSION() AS version')->version;
if (!in_array($mode,['--seed','--mock-delivery','--verify'],true)
    || !$app->environment('testing')
    || $connection->getDriverName()!=='mysql'
    || config('database.connections.mysql.host')!=='127.0.0.1'
    || config('database.connections.mysql.database')!=='ald1n_test'
    || !str_starts_with($version,'10.11.19-MariaDB')
    || config('app.url')!=='http://127.0.0.1:8124'
    || config('mail.default')!=='array'
    || !config('public_storefront.enabled')
    || !config('public_storefront.checkout_enabled')) {
    fwrite(STDERR,"B812_HOLD_DISPOSABLE_MARIADB_ARRAY_MAIL_ONLY\n");
    exit(3);
}

if ($mode==='--seed') {
    if (User::query()->exists() || Product::query()->exists() || Order::query()->exists()) {
        fwrite(STDERR,"B812_HOLD_FIXTURE_NOT_EMPTY\n");
        exit(4);
    }
    $owner=User::query()->create([
        'role_id'=>Role::query()->where('slug','superadmin')->valueOrFail('id'),
        'username'=>'b812-owner','email'=>'b812-staff@example.test',
        'first_name'=>'Synthetic','password_hash'=>Hash::make('B812-Owner-Only123!'),
        'status'=>'active',
    ]);
    $settings=app(SettingsService::class);
    $senderEmail='signup@synthetic.example.test';
    $senderName='B812 Synthetic Registration';
    $settings->putMany([
        'public_registration_sender_address'=>$senderEmail,
        'public_registration_sender_name'=>$senderName,
        'public_registration_sender_digest'=>PublicRegistrationMailSenderService::fingerprint($senderEmail,$senderName),
        'public_registration_sender_approved_by'=>(string)$owner->id,
        'public_registration_sender_approved_at'=>now()->toISOString(),
        'public_storefront_open'=>'1',
        'documents_company_name'=>'B812 Synthetic Merchant',
        'documents_company_address'=>'Test 10',
        'documents_company_city'=>'Beograd',
        'documents_company_tax_id'=>'100200300',
        'eur_rsd_rate'=>'117.78',
    ],$owner->id);

    Product::query()->create([
        'sku'=>'B812-BROWSER',
        'slug'=>'b812-browser-laptop',
        'name'=>'B812 Synthetic Browser Laptop',
        'description'=>'Isolated real-browser integration fixture',
        'price_amount'=>12500,'price_currency'=>'RSD',
        'stock_quantity'=>4,'status'=>'active',
        'purchase_price_rsd'=>999888,
        'manual_commission_eur'=>777777,
        'created_by'=>$owner->id,
    ]);
    $review=app(PublicMerchantPolicyService::class)->review();
    if (!$review['complete']) {
        fwrite(STDERR,"B812_HOLD_MERCHANT_MISSING\n");
        exit(5);
    }
    $settings->putMany([
        'public_checkout_approval_digest'=>$review['fingerprint'],
        'public_checkout_approved_by'=>(string)$owner->id,
        'public_checkout_approved_at'=>now()->toISOString(),
    ],$owner->id);
    echo "B812_SYNTHETIC_SEED=PASS\n";
    exit(0);
}

$buyer=User::query()->where('email',$buyerEmail)->sole();
if ($mode==='--mock-delivery') {
    if ($buyer->status!=='pending' || $buyer->retired_at!==null || !$buyer->hasRole('customer')
        || $buyer->email_verified_at!==null || UserActivationToken::query()->where('user_id',$buyer->id)->count()!==1) {
        fwrite(STDERR,"B812_HOLD_PENDING_SIGNUP_NOT_CONFIRMED\n");
        exit(6);
    }
    $token=UserActivationToken::query()->where('user_id',$buyer->id)->sole();
    if (!$token->sent_at || $token->accepted_at!==null || $token->expires_at<=now()) {
        fwrite(STDERR,"B812_HOLD_UNSENT_OR_EXPIRED_ACTIVATION\n");
        exit(7);
    }
    $token->forceFill(['token_hash'=>hash('sha256',$plain)])->save();
    echo "B812_SYNTHETIC_TOKEN_SUBSTITUTION=PASS_NO_SMTP_DELIVERY_CLAIM\n";
    exit(0);
}

$order=Order::query()->where('sales_channel','public_retail')->sole();
$product=Product::query()->where('sku','B812-BROWSER')->sole();
$token=UserActivationToken::query()->where('user_id',$buyer->id)->sole();
$pass=$buyer->hasRole('customer')
    && $buyer->status==='active'
    && $buyer->retired_at===null
    && $buyer->email_verified_at!==null
    && $buyer->portal_activated_at!==null
    && $token->accepted_at!==null
    && hash_equals($token->token_hash,hash('sha256',$plain))
    && $order->user_id===$buyer->id
    && $order->payment_state==='unpaid'
    && $order->sales_channel==='public_retail'
    && (float)$order->subtotal_rsd===12500.0
    && (int)$product->stock_quantity===3
    && Order::query()->count()===1
    && StockMovement::query()->where('order_id',$order->id)->count()===1
    && PublicCheckoutAttempt::query()->count()===1
    && PublicOrderAccess::query()->count()===1
    && DB::table('order_commissions')->count()===0
    && DB::table('order_payments')->count()===0;
if (!$pass) {
    fwrite(STDERR,"B812_DB_POSTCHECK=FAIL\n");
    exit(8);
}
echo "B812_DB_POSTCHECK=PASS REGISTERED_ORDER=1 STOCK_DECREMENT=1 COMMISSIONS=0 PAYMENTS=0\n";
