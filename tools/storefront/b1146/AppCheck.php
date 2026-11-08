<?php
declare(strict_types=1);
/**
 * B1146 read-only application check, run as its own uncached CLI process: php -d opcache.enable=0 b1146-app-check.php
 * <active tree> approval|routes. It boots the CMS console kernel exactly as artisan does, refuses a cached configuration
 * and prints ONE JSON object with booleans and counts only (never settings values, digests, names or credentials).
 * approval: the merchant review of the code that is installed right now (read-only SELECTs; the app may warm its own
 * settings cache exactly as any web request does). routes: whether the B1146 route names are registered and whether
 * the routes come from bootstrap/cache. It never writes to the database.
 * @author Ald1n <https://ald1n.com>
 */
$fail=static function(string $code):never{echo json_encode(['ok'=>false,'error'=>$code]).PHP_EOL;exit(2);};
[$self,$base,$mode]=$argv+[null,null,null];
if(PHP_SAPI!=='cli'||!is_string($base)||realpath($base)!==$base||!is_file($base.'/artisan')||!in_array($mode,['approval','routes'],true))$fail('APP_CHECK_ARGUMENTS');
if(is_file($base.'/bootstrap/cache/config.php'))$fail('APP_CHECK_CONFIG_CACHED');
$legal=['legal.index','legal.trader','legal.terms','legal.privacy','legal.withdrawal','legal.complaints','legal.delivery','legal.cookies','admin.settings.public-storefront.legal.edit','admin.settings.public-storefront.legal.update','admin.settings.public-storefront.legal.reset'];
try{
    chdir($base);require $base.'/vendor/autoload.php';$app=require $base.'/bootstrap/app.php';
    if(method_exists($app,'configurationIsCached')&&$app->configurationIsCached())$fail('APP_CHECK_CONFIG_CACHED');
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    if($mode==='routes'){
        $routes=$app->make('router')->getRoutes();$routes->refreshNameLookups();$present=0;foreach($legal as $name)if($routes->hasNamedRoute($name))$present++;
        echo json_encode(['ok'=>true,'routesCached'=>$app->routesAreCached(),'b1146RouteNames'=>count($legal),'b1146RouteNamesPresent'=>$present,'routeCount'=>count($routes->getRoutes()),'storefrontSettingsRoute'=>$routes->hasNamedRoute('admin.settings.public-storefront.index')],JSON_THROW_ON_ERROR).PHP_EOL;exit(0);
    }
    $db=$app->make('db')->connection();$db->select('select 1 as ok');
    $review=$app->make(App\Services\PublicMerchantPolicyService::class)->review();
    $settings=$app->make(App\Services\SettingsService::class);$digest=trim((string)$settings->get('public_checkout_approval_digest',''));$fingerprint=(string)($review['fingerprint']??'');
    echo json_encode(['ok'=>true,'approved'=>($review['approved']??null)===true,'complete'=>($review['complete']??null)===true,'issues'=>is_array($review['issues']??null)?count($review['issues']):null,'approvalDigestPresent'=>$digest!=='','approvalDigestMatchesFingerprint'=>$digest!==''&&$fingerprint!==''&&hash_equals($digest,$fingerprint),'legalTextsInFingerprint'=>array_key_exists('legal_texts_digest',(array)($review['policy']??[])),'signatureReported'=>is_array($review['signature']??null)],JSON_THROW_ON_ERROR).PHP_EOL;exit(0);
}catch(Throwable){$fail('APP_CHECK_BOOT_OR_DATABASE_UNAVAILABLE');}
