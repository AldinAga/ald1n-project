<?php
declare(strict_types=1);
// Synthetic-only fixture. Never run on production or customer data.
if (getenv('B807_ISOLATED') !== '1' || getenv('APP_ENV') !== 'testing'
    || getenv('DB_DATABASE') !== 'ald1n_test' || getenv('DB_HOST') !== '127.0.0.1'
    || !isset($argv[1]) || !str_starts_with((string)$argv[1],(string)getenv('RUNNER_TEMP'))) {
    fwrite(STDERR,"B807_HOLD_NOT_DISPOSABLE\n"); exit(70);
}
require __DIR__.'/../../vendor/autoload.php';
$app=require __DIR__.'/../../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default')!=='mysql' || config('database.connections.mysql.database')!=='ald1n_test') {
    fwrite(STDERR,"B807_HOLD_WRONG_DATABASE\n"); exit(71);
}
\Illuminate\Support\Facades\Artisan::call('migrate:fresh',['--force'=>true,'--no-interaction'=>true]);
\Illuminate\Support\Facades\Artisan::call('db:seed',[
    '--class'=>\Database\Seeders\CoreAccessSeeder::class,'--force'=>true,'--no-interaction'=>true,
]);
$owner=\App\Models\User::query()->create([
    'role_id'=>\App\Models\Role::query()->where('slug','superadmin')->valueOrFail('id'),
    'username'=>'b807-synthetic-owner','email'=>'owner@example.test','first_name'=>'Synthetic',
    'password_hash'=>\Illuminate\Support\Facades\Hash::make('OnlySynthetic123!'),
    'status'=>'active',
]);
$settings=app(\App\Services\SettingsService::class);
$settings->putMany([
    'public_storefront_open'=>'1','documents_company_name'=>'Synthetic B807 Merchant',
    'documents_company_address'=>'Test 10','documents_company_city'=>'Beograd',
    'documents_company_tax_id'=>'100200300',
],$owner->id);
$review=app(\App\Services\PublicMerchantPolicyService::class)->review();
if (!$review['complete']) {
    fwrite(STDERR,"B807_HOLD_SYNTHETIC_MERCHANT_NOT_READY\n"); exit(72);
}
$settings->putMany([
    'public_checkout_approval_digest'=>$review['fingerprint'],
    'public_checkout_approved_by'=>(string)$owner->id,
    'public_checkout_approved_at'=>now()->toISOString(),
],$owner->id);
$product=\App\Models\Product::query()->create([
    'sku'=>'B807-REAL-HTTP-ONLY','slug'=>'b807-http',
    'name'=>'Synthetic B807 product','description'=>'Isolated proof only',
    'price_amount'=>12000,'price_currency'=>'RSD',
    'stock_quantity'=>4,'status'=>'active','created_by'=>$owner->id,
]);
file_put_contents($argv[1],(string)$product->id."\n");
echo "B807_SEED_DISPOSABLE=PASS\n";
