<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';
$app=require dirname(__DIR__,2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db=Illuminate\Support\Facades\DB::connection();
if ($app->environment()!=='testing' || $db->getDriverName()!=='mysql'
    || config('database.connections.mysql.host')!=='127.0.0.1'
    || config('database.connections.mysql.database')!=='ald1n_test'
    || config('app.url')!=='http://127.0.0.1:8124'
    || !config('public_storefront.enabled') || !config('public_storefront.checkout_enabled')) {
    fwrite(STDERR,"B799_HOLD_DISPOSABLE_ENV_REQUIRED\n"); exit(3);
}
// All race tooling is hard-pinned to this disposable MariaDB 10.11 environment.
$holdMode=null;
$waiterMode=null;
require_once dirname(__DIR__).'/Support/CheckoutConcurrencyWitness.php';
foreach ($argv as $argument) {
    if (str_starts_with($argument,'--hold=')) $holdMode=substr($argument,7);
    if (str_starts_with($argument,'--waiters=')) $waiterMode=substr($argument,10);
}
if ($holdMode!==null) {
    $sku=match($holdMode) {
        'lastunit'=>'B799-RACE-LASTUNIT',
        'duplicate'=>'B799-RACE-DUPLICATE',
        default=>throw new RuntimeException('B799_RACE_UNKNOWN_LOCK'),
    };
    $db->beginTransaction();
    try {
        $product=App\Models\Product::query()->where('sku',$sku)
            ->lockForUpdate()->firstOrFail();
        fwrite(STDOUT,"B799_LOCKED ".$holdMode."\n");
        fflush(STDOUT);
        $line=fgets(STDIN);
        if (trim((string)$line)!=='RELEASE') {
            throw new RuntimeException('B799_LOCK_RELEASE_NOT_RECEIVED');
        }
        $db->commit();
        fwrite(STDOUT,"B799_RELEASED ".$holdMode."\n");
        exit(0);
    } catch (Throwable $e) {
        $db->rollBack();
        fwrite(STDERR,"B799_LOCK_ABORT ".$e::class."\n");
        exit(11);
    }
}
if ($waiterMode!==null) {
    if (!in_array($waiterMode,['lastunit','duplicate'],true)) exit(12);
    $seenProducts=0;$seenAttemptInserts=0;
    $previousIds=[];$stableSnapshots=0;
    for ($i=0;$i<100;$i++) {
        $processes=DB::select(
            'SELECT ID, INFO FROM information_schema.PROCESSLIST WHERE DB = ? AND USER = ?',
            [config('database.connections.mysql.database'),
                config('database.connections.mysql.username')]
        );
        $witness=Tests\Support\CheckoutConcurrencyWitness::inspect($processes,$waiterMode);
        $seenProducts=$witness['product_waits'];
        $seenAttemptInserts=$witness['attempt_inserts'];
        $ids=$witness['connection_ids'];
        $stableSnapshots=$witness['ready']
            ? ($ids===$previousIds ? $stableSnapshots+1 : 1) : 0;
        $previousIds=$ids;
        if ($stableSnapshots>=2) {
            echo "B799_PARALLEL_WAITER_BARRIER=PASS mode=".$waiterMode
                ." independent_connections=".count($ids)
                ." stable_snapshots=".$stableSnapshots
                ." product_waits=".$seenProducts
                ." attempt_inserts=".$seenAttemptInserts."\n";
            exit(0);
        }
        usleep(100000);
    }
    fwrite(STDERR,"B799_PARALLEL_WAITER_BARRIER=FAIL mode=".$waiterMode
        ." product_waits=".$seenProducts." duplicate_insert_waits=".$seenAttemptInserts."\n");
    exit(13);
}
if(in_array('--verify',$argv,true)) {
    $orders=App\Models\Order::query()->where('sales_channel','public_retail')->get();
    $product=App\Models\Product::query()->where('sku','B799-VISUAL-CI')->firstOrFail();
    $last=App\Models\Product::query()->where('sku','B799-RACE-LASTUNIT')->firstOrFail();
    $duplicate=App\Models\Product::query()->where('sku','B799-RACE-DUPLICATE')->firstOrFail();
    $lastOrders=Illuminate\Support\Facades\DB::table('order_items')
        ->where('product_sku','B799-RACE-LASTUNIT')->distinct()->count('order_id');
    $duplicateOrders=Illuminate\Support\Facades\DB::table('order_items')
        ->where('product_sku','B799-RACE-DUPLICATE')->distinct()->count('order_id');
    $ok=$orders->count()===4 && $orders->every(
        fn($o)=>$o->user_id===null && $o->payment_state==='unpaid')
        && (int)$product->stock_quantity===6
        && (int)$last->stock_quantity===0
        && (int)$duplicate->stock_quantity===1
        && $lastOrders===1 && $duplicateOrders===1
        && App\Models\OrderCommission::query()->count()===0
        && App\Models\OrderPayment::query()->count()===0
        && App\Models\StockMovement::query()->where('movement_type','sale')->count()===4
        && App\Models\PublicOrderAccess::query()->count()===4
        && App\Models\PublicCheckoutAttempt::query()->whereNotNull('terms_accepted_at')->count()===4;
    if(!$ok) {fwrite(STDERR,"B799_BROWSER_DB_POSTCHECK_FAIL\n");exit(4);}
    echo "B799_BROWSER_DB_POSTCHECK=PASS ORDERS=4 VISUAL_STOCK=6 LAST_UNIT=0 DUPLICATE_STOCK=1 COMMISSIONS=0 PAYMENTS=0\n";exit(0);
}
if(App\Models\Product::query()->exists()||App\Models\Order::query()->exists()) {
    fwrite(STDERR,"HOLD_NONEMPTY_DISPOSABLE_DB\n");exit(5);
}
$role=App\Models\Role::query()->where('slug','superadmin')->valueOrFail('id');
$admin=App\Models\User::query()->create([
    'role_id'=>$role,'username'=>'b799-browser-admin',
    'email'=>'synthetic-staff@example.test','first_name'=>'Synthetic',
    'password_hash'=>Illuminate\Support\Facades\Hash::make('SyntheticOnly-NotProduction!'),
    'status'=>'active',
]);
App\Models\Product::query()->create([
    'sku'=>'B799-VISUAL-CI','slug'=>'b799-browser-laptop',
    'name'=>'Synthetic Laptop B799','description'=>'CI-only synthetic product',
    'price_amount'=>12500,'price_currency'=>'RSD',
    'stock_quantity'=>8,'status'=>'active',
    'purchase_price_rsd'=>999888,'manual_commission_eur'=>77777,
    'created_by'=>$admin->id,
]);
app(App\Services\SettingsService::class)->putMany([
    'public_storefront_open'=>'1',
    'documents_company_name'=>'Synthetic Merchant Test',
    'documents_company_address'=>'Synthetic 1, Beograd',
    'documents_company_city'=>'Beograd',
    'documents_company_tax_id'=>'100000001',
    'eur_rsd_rate'=>'117.78',
],null);
foreach ([
    ['B799-RACE-LASTUNIT','b799-race-lastunit',1],
    ['B799-RACE-DUPLICATE','b799-race-duplicate',2],
] as [$sku,$slug,$stock]) {
    App\Models\Product::query()->create([
        'sku'=>$sku,'slug'=>$slug,'name'=>'Synthetic '.$sku,
        'description'=>'Disposable parallel checkout fixture only',
        'price_amount'=>7500,'price_currency'=>'RSD',
        'stock_quantity'=>$stock,'status'=>'active',
        'purchase_price_rsd'=>2500,'manual_commission_eur'=>500,
        'created_by'=>$admin->id,
    ]);
}
$merchant=app(App\Services\PublicMerchantPolicyService::class);
$review=$merchant->review();
if (!$review['complete']) {
    fwrite(STDERR,"HOLD_B799_BROWSER_MERCHANT_CONFIGURATION\n");
    exit(6);
}
app(App\Services\SettingsService::class)->putMany([
    'public_checkout_approval_digest'=>$review['fingerprint'],
    'public_checkout_approved_by'=>(string)$admin->id,
    'public_checkout_approved_at'=>now()->toISOString(),
],$admin->id);
echo "B799_BROWSER_SYNTHETIC_FIXTURE=PASS\n";
