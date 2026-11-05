<?php
declare(strict_types=1);

/** Standalone authenticated endpoint library; the executable entry lives ONLY in incoming/. */
final class WebCacheEndpoint
{
    private static function deny(int $code=403):never
    {
        http_response_code($code);header('Content-Type: application/json');header('Cache-Control: no-store, private');header('Pragma: no-cache');header('X-Content-Type-Options: nosniff');
        echo '{"status":"denied"}';exit;
    }

    public static function serve(string $envelopePath):never
    {
        try{
            if(PHP_SAPI==='cli'||($_SERVER['REQUEST_METHOD']??'')!=='POST')self::deny();
            $private=dirname($envelopePath);
            if(is_link($envelopePath)||realpath($envelopePath)!==$envelopePath||realpath($private)!==$private||(fileperms($private)&0777)!==0700||(fileperms($envelopePath)&0777)!==0600||filesize($envelopePath)>4194304)self::deny();
            $keyPath=$private.'/web-signing-key';if(is_link($keyPath)||realpath($keyPath)!==$keyPath||(fileperms($keyPath)&0777)!==0600||filesize($keyPath)!==64)self::deny();
            $key=file_get_contents($keyPath);$envelope=json_decode(file_get_contents($envelopePath),true,512,JSON_THROW_ON_ERROR);
            $payload=$envelope['payload']??null;if(!is_string($payload)||!hash_equals(hash_hmac('sha256',$payload,$key),(string)($envelope['hmac']??'')))self::deny();
            $config=json_decode($payload,true,512,JSON_THROW_ON_ERROR);
            $provided=$_SERVER['HTTP_X_ALD1N_RELEASE_TOKEN']??'';
            if(!is_string($provided)||!hash_equals($config['tokenSHA256']??'',hash('sha256',$provided)))self::deny();
            if(($config['schemaVersion']??0)!==1||($config['expires']??0)<time()||$config['expires']>time()+1800||!in_array($config['phase']??'', ['preflight','maintenance','installed','rollback'],true))self::deny();
            $incoming=$config['incoming'];$entry=$config['entry'];$root=$config['activeRoot'];$link=$root.'/public/'.$config['alias'];
            if(!preg_match('/^[a-f0-9]{64}$/D',$config['librarySHA256'])||realpath($incoming)!==$incoming||dirname($entry)!==$incoming||realpath($entry)!==$entry||realpath(__FILE__)!==$incoming.'/b1134-webcache-'.$config['librarySHA256'].'.php'||hash_file('sha256',__FILE__)!==$config['librarySHA256'])self::deny();
            if(realpath($_SERVER['SCRIPT_FILENAME']??'')!==$entry||realpath($_SERVER['DOCUMENT_ROOT']??'')!==realpath($root.'/public')||!is_link($link)||readlink($link)!==$entry||realpath($link)!==$entry)self::deny();
            if(!preg_match('/^ald1n-release-[a-f0-9]{32}\.php$/D',$config['alias'])||!in_array(PHP_SAPI,$config['allowedSapis'],true))self::deny();
            $status=function_exists('opcache_get_status')?opcache_get_status(false):false;
            if(!is_array($status)||($status['opcache_enabled']??false)!==true||($status['restart_pending']??false)||filter_var(ini_get('opcache.file_cache_only'),FILTER_VALIDATE_BOOLEAN)||!function_exists('opcache_invalidate')||!function_exists('opcache_is_script_cached')||!function_exists('posix_geteuid'))self::deny(503);
            foreach(['APP_CONFIG_CACHE','APP_BASE_PATH','APP_ROUTES_CACHE','APP_SERVICES_CACHE','APP_PACKAGES_CACHE','VIEW_COMPILED_PATH'] as $key){foreach([getenv($key),$_SERVER[$key]??null,$_ENV[$key]??null] as $value)if(is_string($value)&&$value!=='')self::deny(503);}
            $body=file_get_contents('php://input',false,null,0,4097);if(!is_string($body)||strlen($body)>4096)self::deny();$request=json_decode($body,true,32,JSON_THROW_ON_ERROR);
            $action=$request['action']??'';if(!in_array($action,['probe','invalidate','off-proof'],true))self::deny();
            $result=['status'=>'ok','id'=>$config['id'],'phase'=>$config['phase'],'sapi'=>PHP_SAPI,'php'=>PHP_VERSION,'uid'=>posix_geteuid(),'opcacheEnabled'=>true,'fileCacheConfigured'=>ini_get('opcache.file_cache')!=='','fileCacheOnly'=>false,'unsupportedProcessOverrides'=>false,'librarySHA256'=>$config['librarySHA256'],'checked'=>0,'cachedBefore'=>0,'invalidated'=>0];
            if($action==='invalidate'){
                if($config['phase']==='preflight'||!is_array($config['files'])||count($config['files'])<1||count($config['files'])>20000)self::deny();
                $offset=$request['offset']??null;$total=count($config['files']);
                if(!is_int($offset)||$offset<0||$offset%128!==0||$offset>=$total)self::deny();
                clearstatcache(true);$validated=[];
                foreach(array_slice($config['files'],$offset,128,true) as $relative=>$digest){
                    if(!is_string($relative)||!str_ends_with($relative,'.php')||str_starts_with($relative,'/')||str_contains($relative,'\\')||preg_match('~(^|/)\.{1,2}(/|$)|[\x00-\x1f]~',$relative)||!preg_match('/^[a-f0-9]{64}$/D',$digest))self::deny();
                    $file=$root.'/'.$relative;
                    if(is_link($file)||!is_file($file)||realpath($file)!==$file||hash_file('sha256',$file)!==$digest)self::deny(409);
                    $validated[]=$file;
                }
                $result['offset']=$offset;$result['total']=$total;$result['chunkSize']=128;
                foreach($validated as $file){$cached=opcache_is_script_cached($file);$result['checked']++;if($cached)$result['cachedBefore']++;if(!opcache_invalidate($file,true))self::deny(503);$result['invalidated']++;}
            }
            if($action==='off-proof'){
                if($config['phase']!=='installed')self::deny();
                require $root.'/vendor/autoload.php';$app=require $root.'/bootstrap/app.php';$kernel=$app->make(\Illuminate\Contracts\Http\Kernel::class);$kernel->bootstrap();
                if($app->basePath()!==$root||$app->getCachedConfigPath()!==$root.'/bootstrap/cache/config.php'||is_file($app->getCachedConfigPath())||config('public_storefront.enabled')!==false||config('public_storefront.checkout_enabled')!==false)self::deny(409);
                $result['actualWebApplicationBootstrapped']=true;$result['storefrontEnabled']=false;$result['checkoutEnabled']=false;$result['configCacheAbsent']=true;
            }
            http_response_code(200);header('Content-Type: application/json');header('Cache-Control: no-store, private');header('Pragma: no-cache');header('X-Content-Type-Options: nosniff');
            echo json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);exit;
        }catch(Throwable $error){self::deny(503);}
    }
}
