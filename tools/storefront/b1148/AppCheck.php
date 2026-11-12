<?php
declare(strict_types=1);
/**
 * B1148 read-only application check, run as its own uncached CLI process:
 * php -d opcache.enable=0 b1148-app-check.php <active tree> theme|branding <PublicStorefrontAppearance payload> <payload sha256>
 * It boots the CMS console kernel exactly as artisan does, refuses a cached configuration and prints ONE JSON object with
 * booleans, counts and fixed keywords only (never a setting value, path, URL or credential). It never writes.
 * theme: whether the two new classes load through composer, whether the named route public-shop.theme exists as
 * POST-only /tema behind throttle:public-shop-theme, whether the named rate limiter public-shop-theme is registered, and
 * whether the CSRF exception list (read after the HTTP kernel applied bootstrap/app.php) holds 'tema'.
 * branding: INFO for the owner (missing logo): whether site_name is empty, the CMS default 'Ald1n CMS' or custom; for the
 * light logo, dark logo and favicon settings whether each is set, under site-assets/, of an extension the storefront
 * shows (png, jpg, jpeg, webp; favicon png, ico) and present on the public disk; whether public/storage is a symlink to
 * storage/app/public; and what the shipped PublicStorefrontAppearance::branding() resolves (logo shown, favicon, default
 * brand). Before the install that class is not in the tree, so the verified payload bytes are loaded into this one
 * throwaway process instead: the rule is always the shipped code.
 * @author Ald1n <https://ald1n.com>
 */
$fail=static function(string $code):never{echo json_encode(['ok'=>false,'error'=>$code]).PHP_EOL;exit(2);};
[$self,$base,$mode,$payload,$sha]=$argv+[null,null,null,null,null];
if(PHP_SAPI!=='cli'||!is_string($base)||realpath($base)!==$base||!is_file($base.'/artisan')||!in_array($mode,['theme','branding'],true)||!is_string($payload)||realpath($payload)!==$payload||!is_string($sha)||!preg_match('/^[a-f0-9]{64}$/D',$sha))$fail('APP_CHECK_ARGUMENTS');
if(is_file($base.'/bootstrap/cache/config.php'))$fail('APP_CHECK_CONFIG_CACHED');
try{
    chdir($base);require $base.'/vendor/autoload.php';$app=require $base.'/bootstrap/app.php';
    if(method_exists($app,'configurationIsCached')&&$app->configurationIsCached())$fail('APP_CHECK_CONFIG_CACHED');
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $appearance=class_exists(App\Support\PublicStorefrontAppearance::class,true);$controller=class_exists(App\Http\Controllers\PublicThemeController::class,true);
    if($mode==='theme'){
        $routes=$app->make('router')->getRoutes();$routes->refreshNameLookups();$route=$routes->getByName('public-shop.theme');
        $methods=$route===null?[]:array_values(array_diff($route->methods(),['HEAD']));
        $throttle=$route!==null&&in_array('throttle:public-shop-theme',$route->gatherMiddleware(),true);
        $limiter=Illuminate\Support\Facades\RateLimiter::limiter('public-shop-theme')!==null;
        // The CSRF exceptions are applied when the HTTP kernel is resolved (bootstrap/app.php withMiddleware); read only.
        $app->make(Illuminate\Contracts\Http\Kernel::class);$csrf=null;
        foreach(['Illuminate\Foundation\Http\Middleware\PreventRequestForgery','Illuminate\Foundation\Http\Middleware\ValidateCsrfToken','Illuminate\Foundation\Http\Middleware\VerifyCsrfToken'] as $class){
            if(!class_exists($class)||!property_exists($class,'neverVerify'))continue;$p=new ReflectionProperty($class,'neverVerify');if(!$p->isStatic())continue;$list=(array)$p->getValue();$csrf=in_array('tema',$list,true);break;
        }
        echo json_encode(['ok'=>true,'appearanceAutoloaded'=>$appearance,'themeControllerAutoloaded'=>$controller,'themeRoute'=>$route!==null,'themeRouteUri'=>$route===null?null:($route->uri()==='tema'),'themeRoutePostOnly'=>$methods===['POST'],'themeRouteThrottled'=>$throttle,'themeLimiterRegistered'=>$limiter,'csrfExceptTema'=>$csrf,'routesCached'=>$app->routesAreCached()],JSON_THROW_ON_ERROR).PHP_EOL;exit(0);
    }
    $settings=$app->make(App\Services\SettingsService::class)->all();$name=trim((string)($settings['site_name']??''));
    $asset=static function(string $key,array $extensions)use($settings):array{
        $v=trim((string)($settings[$key]??''));$ext=strtolower((string)pathinfo($v,PATHINFO_EXTENSION));$known=['png','jpg','jpeg','webp','ico','svg','gif','avif'];
        $under=preg_match('~^site-assets/~',$v)===1&&!str_contains($v,'..');$exists=false;try{$exists=$v!==''&&!str_contains($v,'..')&&Illuminate\Support\Facades\Storage::disk('public')->exists($v);}catch(Throwable){$exists=false;}
        return ['set'=>$v!=='','underSiteAssets'=>$under,'extension'=>$v===''?null:(in_array($ext,$known,true)?$ext:'other'),'extensionShownByStorefront'=>in_array($ext,$extensions,true),'fileOnPublicDisk'=>$exists];
    };
    $link=$base.'/public/storage';$target=realpath($base.'/storage/app/public');
    if(!$appearance){$bytes=(string)file_get_contents($payload);if(!hash_equals($sha,hash('sha256',$bytes)))$fail('APP_CHECK_PAYLOAD_INTEGRITY');require $payload;}
    $b=App\Support\PublicStorefrontAppearance::branding();
    echo json_encode(['ok'=>true,'appearanceAutoloaded'=>$appearance,'siteName'=>$name===''?'EMPTY':($name==='Ald1n CMS'?'CMS_DEFAULT_ALD1N_CMS':'CUSTOM'),'siteNameLength'=>mb_strlen($name),
        'logoLight'=>$asset('site_logo_light_path',['png','jpg','jpeg','webp']),'logoDark'=>$asset('site_logo_dark_path',['png','jpg','jpeg','webp']),'favicon'=>$asset('site_favicon_path',['png','ico']),'footerShowLogo'=>($settings['site_footer_show_logo']??'0')==='1',
        'publicStorageSymlink'=>is_link($link),'publicStorageResolvesToStorageAppPublic'=>is_link($link)&&$target!==false&&realpath($link)===$target,'publicStorageExists'=>file_exists($link),
        'resolved'=>['logoShown'=>$b['has_logo']===true,'faviconShown'=>$b['favicon']!==null,'brandIsFallbackAPComputers'=>$b['brand']===App\Support\PublicCatalogPresentation::SITE_NAME,'footerLogoShown'=>$b['footer_logo']===true],'rule'=>$appearance?'INSTALLED_CLASS':'SHIPPED_PAYLOAD'],JSON_THROW_ON_ERROR).PHP_EOL;exit(0);
}catch(Throwable){$fail('APP_CHECK_BOOT_OR_CLASS_UNAVAILABLE');}
