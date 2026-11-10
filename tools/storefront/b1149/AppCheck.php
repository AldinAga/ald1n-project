<?php
declare(strict_types=1);
/**
 * B1149 read-only application check, run as its own uncached CLI process:
 * php -d opcache.enable=0 b1149-app-check.php <active tree> features|env <64 hex HMAC key, env only>
 * It boots the CMS console kernel exactly as artisan does, refuses a cached configuration and prints ONE JSON object with
 * booleans, counts and fixed keywords only (never a setting value, path, URL or credential). It never writes and never sends mail.
 * features: whether the 11 new classes load through composer, whether the changed static APIs exist
 * (ProductImageDerivativeService::exifOrientation, PublicStorefrontAppearance::staleThemeCookie), whether the named routes
 * site-assets.show and admin.settings.smtp.index exist, whether MailSettingsServiceProvider is registered, and the B1148
 * theme route (POST-only /tema) that must stay.
 * env: PHP extensions of this CLI (dom, libxml, fileinfo, mbstring, gd, imagick), whether APP_URL is an origin the shipped
 * catalog rule accepts, and mailFingerprintHMAC: one HMAC-SHA256, keyed with a random per-run key the caller passes, over the effective mail transport (default mailer, transport,
 * host, port, encryption, timeout, hashes of username and password, from address, mailer names). The caller keeps the key and the
 * result only in its private 0600 state file and never prints either; comparing the two results proves the transport did not change.
 * @author Ald1n <https://ald1n.com>
 */
$fail=static function(string $code):never{echo json_encode(['ok'=>false,'error'=>$code]).PHP_EOL;exit(2);};
[$self,$base,$mode,$key]=$argv+[null,null,null,null];
if(PHP_SAPI!=='cli'||!is_string($base)||realpath($base)!==$base||!is_file($base.'/artisan')||!in_array($mode,['features','env'],true)||($mode==='env'&&(!is_string($key)||preg_match('/^[a-f0-9]{64}$/D',$key)!==1)))$fail('APP_CHECK_ARGUMENTS');
if(is_file($base.'/bootstrap/cache/config.php'))$fail('APP_CHECK_CONFIG_CACHED');
try{
    chdir($base);require $base.'/vendor/autoload.php';$app=require $base.'/bootstrap/app.php';
    if(method_exists($app,'configurationIsCached')&&$app->configurationIsCached())$fail('APP_CHECK_CONFIG_CACHED');
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    if($mode==='env'){
        $ext=[];foreach(['dom','libxml','fileinfo','mbstring','gd','imagick'] as $x)$ext[$x]=extension_loaded($x);
        $origin=class_exists(App\Support\PublicCatalogPresentation::class,true)&&App\Support\PublicCatalogPresentation::baseUrl()!==null;
        $mail=(array)config('mail');$default=(string)($mail['default']??'');$mailer=(array)(($mail['mailers']??[])[$default]??[]);
        $fingerprint=hash_hmac('sha256',json_encode(['default'=>$default,'transport'=>$mailer['transport']??null,'host'=>$mailer['host']??null,'port'=>$mailer['port']??null,'encryption'=>$mailer['encryption']??($mailer['scheme']??null),'timeout'=>$mailer['timeout']??null,
            'usernameSha'=>hash('sha256',(string)($mailer['username']??'')),'passwordSha'=>hash('sha256',(string)($mailer['password']??'')),'from'=>$mail['from']??null,'mailers'=>array_keys((array)($mail['mailers']??[]))],JSON_THROW_ON_ERROR),(string)$key);
        echo json_encode(['ok'=>true,'extensions'=>$ext,'phpVersionOk'=>PHP_VERSION_ID>=80401&&PHP_VERSION_ID<80500,'appUrlHttpsOrigin'=>$origin,'mailTransportKind'=>is_string($mailer['transport']??null)?$mailer['transport']:'UNKNOWN','mailFingerprintHMAC'=>$fingerprint],JSON_THROW_ON_ERROR).PHP_EOL;exit(0);
    }
    $classes=['App\Services\SvgSanitizer','App\Services\SvgSanitizeResult','App\Services\SvgSanitizerException','App\Services\SiteLogoAssetService','App\Rules\SiteLogoFile','App\Services\SmtpMailSettingsService','App\Services\MailRuntimeConfigurator','App\Services\SmtpTestTransportFactory','App\Providers\MailSettingsServiceProvider','App\Support\PublicProductImageFiles','App\Support\PublicProductImages'];
    $loaded=0;foreach($classes as $c)if(class_exists($c,true))$loaded++;
    $apis=method_exists(App\Services\ProductImageDerivativeService::class,'exifOrientation')&&method_exists(App\Support\PublicStorefrontAppearance::class,'staleThemeCookie');
    $routes=$app->make('router')->getRoutes();$routes->refreshNameLookups();
    $site=$routes->getByName('site-assets.show');$smtp=$routes->getByName('admin.settings.smtp.index');$theme=$routes->getByName('public-shop.theme');
    $themeMethods=$theme===null?[]:array_values(array_diff($theme->methods(),['HEAD']));
    echo json_encode(['ok'=>true,'newClassesAutoloaded'=>$loaded===count($classes),'newClassesLoaded'=>$loaded,'changedStaticApis'=>$apis,'routeSiteAssets'=>$site!==null&&in_array('GET',$site->methods(),true),'routeSmtpIndex'=>$smtp!==null&&in_array('GET',$smtp->methods(),true),
        'providerRegistered'=>array_key_exists('App\Providers\MailSettingsServiceProvider',$app->getLoadedProviders()),'themeRoutePostOnly'=>$themeMethods===['POST'],'routesCached'=>$app->routesAreCached()],JSON_THROW_ON_ERROR).PHP_EOL;exit(0);
}catch(Throwable){$fail('APP_CHECK_BOOT_OR_CLASS_UNAVAILABLE');}
