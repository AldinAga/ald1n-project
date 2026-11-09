<?php
declare(strict_types=1);
/**
 * B1147 read-only application check, run as its own uncached CLI process:
 * php -d opcache.enable=0 b1147-app-check.php <active tree> catalog <PublicCatalogPresentation payload> <payload sha256>
 * It boots the CMS console kernel exactly as artisan does, refuses a cached configuration and prints ONE JSON object with
 * booleans only (never the URL, settings values or credentials). It reports whether the two new App\Support classes load
 * through composer from the active tree, and whether config('app.url') is an origin that the shipped
 * PublicCatalogPresentation::baseUrl() accepts. Before the install that class does not exist in the tree, so the verified
 * payload bytes are loaded into this one throwaway process instead: the rule is always the shipped code, never a copy.
 * It never writes to the database or the tree.
 * @author Ald1n <https://ald1n.com>
 */
$fail=static function(string $code):never{echo json_encode(['ok'=>false,'error'=>$code]).PHP_EOL;exit(2);};
[$self,$base,$mode,$payload,$sha]=$argv+[null,null,null,null,null];
if(PHP_SAPI!=='cli'||!is_string($base)||realpath($base)!==$base||!is_file($base.'/artisan')||$mode!=='catalog'||!is_string($payload)||realpath($payload)!==$payload||!is_string($sha)||!preg_match('/^[a-f0-9]{64}$/D',$sha))$fail('APP_CHECK_ARGUMENTS');
if(is_file($base.'/bootstrap/cache/config.php'))$fail('APP_CHECK_CONFIG_CACHED');
try{
    chdir($base);require $base.'/vendor/autoload.php';$app=require $base.'/bootstrap/app.php';
    if(method_exists($app,'configurationIsCached')&&$app->configurationIsCached())$fail('APP_CHECK_CONFIG_CACHED');
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $presentation=class_exists(App\Support\PublicCatalogPresentation::class,true);$plural=class_exists(App\Support\SerbianPlural::class,true);
    if(!$presentation){$bytes=(string)file_get_contents($payload);if(!hash_equals($sha,hash('sha256',$bytes)))$fail('APP_CHECK_PAYLOAD_INTEGRITY');require $payload;}
    $origin=App\Support\PublicCatalogPresentation::baseUrl()!==null;
    echo json_encode(['ok'=>true,'presentationAutoloaded'=>$presentation,'serbianPluralAutoloaded'=>$plural,'appUrlHttpsOrigin'=>$origin,'rule'=>$presentation?'INSTALLED_CLASS':'SHIPPED_PAYLOAD'],JSON_THROW_ON_ERROR).PHP_EOL;exit(0);
}catch(Throwable){$fail('APP_CHECK_BOOT_OR_CLASS_UNAVAILABLE');}
