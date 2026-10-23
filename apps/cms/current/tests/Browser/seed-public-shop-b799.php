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
if(in_array('--verify',$argv,true)) {
    $orders=App\Models\Order::query()->where('sales_channel','public_retail')->get();
    $product=App\Models\Product::query()->where('sku','B799-VISUAL-CI')->firstOrFail();
    $ok=$orders->count()===2 && $orders->every(
        fn($o)=>$o->user_id===null && $o->payment_state==='unpaid')
        && (int)$product->stock_quantity===6
        && App\Models\OrderCommission::query()->count()===0
        && App\Models\OrderPayment::query()->count()===0
        && App\Models\PublicOrderAccess::query()->count()===2
        && App\Models\PublicCheckoutAttempt::query()->whereNotNull('terms_accepted_at')->count()===2;
    if(!$ok) {fwrite(STDERR,"B799_BROWSER_DB_POSTCHECK_FAIL\n");exit(4);}
    echo "B799_BROWSER_DB_POSTCHECK=PASS ORDERS=2 STOCK=6 COMMISSIONS=0 PAYMENTS=0\n";exit(0);
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
    'documents_company_name'=>'Synthetic Merchant Test',
    'documents_company_address'=>'Synthetic 1, Beograd',
    'documents_company_tax_id'=>'100000001',
    'eur_rsd_rate'=>'117.78',
],null);
echo "B799_BROWSER_SYNTHETIC_FIXTURE=PASS\n";
