<?php
declare(strict_types=1);

/** Immutable incoming library, dispatched only by owned maintenance inside actual public/index.php. */
final class WebCacheEndpoint
{
    private static function deny(int $code=403):never {http_response_code($code);header('Content-Type: application/json');header('Cache-Control: no-store, private');header('Pragma: no-cache');header('X-Content-Type-Options: nosniff');echo '{"status":"denied"}';exit;}

    private static function configuration(string $path):array
    {
        $private=dirname($path);$keyPath=$private.'/web-signing-key';
        if(PHP_SAPI==='cli'||is_link($path)||realpath($path)!==$path||realpath($private)!==$private||(fileperms($private)&0777)!==0700||(fileperms($path)&0777)!==0600||filesize($path)>4194304||is_link($keyPath)||realpath($keyPath)!==$keyPath||(fileperms($keyPath)&0777)!==0600||filesize($keyPath)!==64)throw new RuntimeException('PRIVATE_ENVELOPE');
        $e=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$payload=$e['payload']??null;if(!is_string($payload)||!hash_equals(hash_hmac('sha256',$payload,file_get_contents($keyPath)),(string)($e['hmac']??'')))throw new RuntimeException('ENVELOPE_SIGNATURE');$c=json_decode($payload,true,512,JSON_THROW_ON_ERROR);
        if(($c['schemaVersion']??0)!==1||($c['expires']??0)<time()||$c['expires']>time()+1800||!in_array($c['phase']??'',['preflight','maintenance','installed','rollback'],true)||!preg_match('/^[a-f0-9]{64}$/D',$c['librarySHA256']??''))throw new RuntimeException('ENVELOPE_SCOPE');
        $root=$c['activeRoot'];$incoming=$c['incoming'];$index=$root.'/public/index.php';
        if(realpath($incoming)!==$incoming||realpath(__FILE__)!==$incoming.'/b1134-webcache-'.$c['librarySHA256'].'.php'||hash_file('sha256',__FILE__)!==$c['librarySHA256']||realpath($_SERVER['SCRIPT_FILENAME']??'')!==$index||realpath($_SERVER['DOCUMENT_ROOT']??'')!==$root.'/public'||hash_file('sha256',$index)!==$c['indexSHA256'])throw new RuntimeException('ACTUAL_FRONT_SCOPE');
        if(!in_array(PHP_SAPI,$c['allowedSapis'],true)||!function_exists('posix_geteuid')||fileowner($path)!==posix_geteuid()||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('WEB_HANDLER');
        $status=function_exists('opcache_get_status')?opcache_get_status(false):false;if(!is_array($status)||($status['opcache_enabled']??false)!==true||($status['restart_pending']??false)||filter_var(ini_get('opcache.file_cache_only'),FILTER_VALIDATE_BOOLEAN)||!function_exists('opcache_invalidate')||!function_exists('opcache_is_script_cached'))throw new RuntimeException('OPCACHE_CAPABILITY');
        if(filter_var(ini_get('opcache.enable_file_override'),FILTER_VALIDATE_BOOLEAN))throw new RuntimeException('UNSUPPORTED_FILE_EXISTENCE_OVERRIDE');
        foreach(['auto_prepend_file','auto_append_file','opcache.preload'] as $setting)if(!in_array(ini_get($setting),['',false],true))throw new RuntimeException('UNSUPPORTED_PREBOOT_OVERRIDE');
        foreach(['APP_CONFIG_CACHE','APP_BASE_PATH','APP_ROUTES_CACHE','APP_SERVICES_CACHE','APP_PACKAGES_CACHE','VIEW_COMPILED_PATH'] as $key)foreach([getenv($key),$_SERVER[$key]??null,$_ENV[$key]??null] as $value)if(is_string($value)&&$value!=='')throw new RuntimeException('UNSUPPORTED_PROCESS_OVERRIDE');
        $sentinel=$c['sentinel'];if(dirname($sentinel)!==$incoming||is_link($sentinel)||realpath($sentinel)!==$sentinel||hash_file('sha256',$sentinel)!==$c['sentinelSHA256'])throw new RuntimeException('CACHE_SENTINEL_SCOPE');return $c;
    }

    private static function caps(array $c):array {$s=opcache_get_status(false);return ['status'=>'ok','id'=>$c['id'],'phase'=>$c['phase'],'sapi'=>PHP_SAPI,'php'=>PHP_VERSION,'pid'=>getmypid(),'ppid'=>posix_getppid(),'pidNamespace'=>readlink('/proc/self/ns/pid'),'uid'=>posix_geteuid(),'opcacheEnabled'=>true,'fileCacheConfigured'=>ini_get('opcache.file_cache')!=='','unsupportedProcessOverrides'=>false,'librarySHA256'=>$c['librarySHA256'],'actualIndex'=>true,'cacheStartTime'=>$s['opcache_statistics']['start_time']??null];}

    /** No browser Set-Cookie. The framework cookie exists only in this authenticated GET's memory. */
    public static function barrier(string $envelopePath,string $ownerStamp,string $frontSHA256,string $smokeSHA256,string $cookieKey):void
    {
        $front=$_SERVER['HTTP_X_ALD1N_RELEASE_FRONT']??'';if(is_string($front)&&$front!==''&&hash_equals($frontSHA256,hash('sha256',$front)))self::serve($envelopePath);
        $smoke=$_SERVER['HTTP_X_ALD1N_RELEASE_SMOKE']??'';
        if(is_string($smoke)&&$smoke!==''&&hash_equals($smokeSHA256,hash('sha256',$smoke))){try{
            $c=self::configuration($envelopePath);$path=parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH);
            if(($_SERVER['REQUEST_METHOD']??'')!=='GET'||!in_array($path,['/login','/admin/catalog','/katalog','/korpa','/porucivanje'],true)||!in_array($c['phase'],['installed','rollback'],true)||($c['smokeTokenSHA256']??'')!==$smokeSHA256||!opcache_is_script_cached($c['sentinel']))throw new RuntimeException('SMOKE_SCOPE_OR_CACHE_POOL');
            $nonce=$_SERVER['HTTP_X_ALD1N_RELEASE_SMOKE_NONCE']??'';if(!is_string($nonce)||!preg_match('/^[a-f0-9]{32}$/D',$nonce))throw new RuntimeException('SMOKE_NONCE');
            $expires=min(time()+60,$c['expires']);$_COOKIE['laravel_maintenance']=base64_encode(json_encode(['expires_at'=>$expires,'mac'=>hash_hmac('sha256',(string)$expires,$cookieKey)],JSON_THROW_ON_ERROR));$record=self::caps($c)+['path'=>$path,'nonce'=>$nonce,'cacheSharedWithRPC'=>true];$recordPath=dirname($envelopePath).'/smoke-'.hash('sha256',$path).'.json';
            register_shutdown_function(static function()use($record,$recordPath,$c):void{
                $result=$record;try{$app=app();$result['applicationBootstrapped']=true;$result['basePathMatches']=$app->basePath()===$c['activeRoot'];$result['configCacheAbsent']=$app->getCachedConfigPath()===$c['activeRoot'].'/bootstrap/cache/config.php'&&!is_file($app->getCachedConfigPath());$result['storefrontEnabled']=config('public_storefront.enabled');$result['checkoutEnabled']=config('public_storefront.checkout_enabled');$result['releaseLocalCompiledViews']=realpath(config('view.compiled'))===$c['activeRoot'].'/storage/framework/views';}catch(Throwable $error){$result['applicationBootstrapped']=false;}
                $text=json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);$temporary=$recordPath.'.next';$h=fopen($temporary,'x');if($h!==false){chmod($temporary,0600);fwrite($h,$text);fflush($h);fclose($h);rename($temporary,$recordPath);}
            });return;
        }catch(Throwable $error){}}
        http_response_code(503);header('Retry-After: 60');header('Cache-Control: no-store, private');echo 'ALD1N owned maintenance '.$ownerStamp;exit;
    }

    public static function serve(string $envelopePath):never
    {
        try{
            if(($_SERVER['REQUEST_METHOD']??'')!=='POST')self::deny();$c=self::configuration($envelopePath);$provided=$_SERVER['HTTP_X_ALD1N_RELEASE_TOKEN']??'';if(!is_string($provided)||!hash_equals($c['tokenSHA256'],hash('sha256',$provided))||parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)!==$c['rpcPath'])self::deny();
            $body=file_get_contents('php://input',false,null,0,4097);if(!is_string($body)||strlen($body)>4096)self::deny();$r=json_decode($body,true,32,JSON_THROW_ON_ERROR);$action=$r['action']??'';if(!in_array($action,['probe','visibility','invalidate','off-proof'],true))self::deny();$result=self::caps($c)+['checked'=>0,'cachedBefore'=>0,'invalidated'=>0];$result['sentinelPreviouslyCached']=opcache_is_script_cached($c['sentinel']);
            if($action==='probe'){$status=opcache_get_status(false);$result['sentinelDiagnostics']=['fileUpdateProtectionSeconds'=>(int)ini_get('opcache.file_update_protection'),'sentinelAgeSeconds'=>time()-filemtime($c['sentinel']),'maxFileSizeBytes'=>(int)ini_get('opcache.max_file_size'),'sentinelSizeBytes'=>filesize($c['sentinel']),'cacheFull'=>(bool)($status['cache_full']??true),'freeMemoryBytes'=>(int)($status['memory_usage']['free_memory']??0),'cachedScripts'=>(int)($status['opcache_statistics']['num_cached_scripts']??0),'maxCachedKeys'=>(int)($status['opcache_statistics']['max_cached_keys']??0)];}
            if((require $c['sentinel'])!==$c['id'])self::deny(409);
            if($action==='visibility'){
                $nonce=$r['nonce']??'';if($c['phase']!=='preflight'||!is_string($nonce)||!preg_match('/^[a-f0-9]{32}$/D',$nonce))self::deny();$h=fopen(dirname($envelopePath).'/visibility-'.$nonce.'.json','x+');if($h===false)self::deny();chmod(dirname($envelopePath).'/visibility-'.$nonce.'.json',0600);
                $write=static function(string $stage)use($h,$nonce):void{$v=json_encode(['nonce'=>$nonce,'stage'=>$stage,'pid'=>getmypid(),'pidNamespace'=>readlink('/proc/self/ns/pid'),'uid'=>posix_geteuid()],JSON_THROW_ON_ERROR);rewind($h);ftruncate($h,0);fwrite($h,$v);fflush($h);};$write('execution');register_shutdown_function(static function()use($write,$h):void{$write('shutdown');usleep(3000000);$write('done');fclose($h);});usleep(1500000);$result['visibilityNonce']=$nonce;
            }
            if($action==='invalidate'){
                if($c['phase']==='preflight'||!is_array($c['files'])||count($c['files'])<1||count($c['files'])>20000)self::deny();$offset=$r['offset']??null;$total=count($c['files']);if(!is_int($offset)||$offset<0||$offset%128!==0||$offset>=$total)self::deny();clearstatcache(true);$validated=[];
                foreach(array_slice($c['files'],$offset,128,true) as $relative=>$digest){if(!is_string($relative)||!str_ends_with($relative,'.php')||str_starts_with($relative,'/')||str_contains($relative,'\\')||preg_match('~(^|/)\.{1,2}(/|$)|[\x00-\x1f]~',$relative)||!preg_match('/^[a-f0-9]{64}$/D',$digest))self::deny();$file=$c['activeRoot'].'/'.$relative;if(is_link($file)||!is_file($file)||realpath($file)!==$file||hash_file('sha256',$file)!==$digest)self::deny(409);$validated[]=$file;}
                $result['offset']=$offset;$result['total']=$total;$result['chunkSize']=128;foreach($validated as $file){if(opcache_is_script_cached($file))$result['cachedBefore']++;$result['checked']++;if(!opcache_invalidate($file,true))self::deny(503);$result['invalidated']++;}
            }
            if($action==='off-proof'){
                if($c['phase']!=='installed')self::deny();require $c['activeRoot'].'/vendor/autoload.php';$app=require $c['activeRoot'].'/bootstrap/app.php';$kernel=$app->make(\Illuminate\Contracts\Http\Kernel::class);$kernel->bootstrap();if($app->basePath()!==$c['activeRoot']||$app->getCachedConfigPath()!==$c['activeRoot'].'/bootstrap/cache/config.php'||is_file($app->getCachedConfigPath())||config('public_storefront.enabled')!==false||config('public_storefront.checkout_enabled')!==false)self::deny(409);$result['actualWebApplicationBootstrapped']=true;$result['storefrontEnabled']=false;$result['checkoutEnabled']=false;$result['configCacheAbsent']=true;
            }
            http_response_code(200);header('Content-Type: application/json');header('Cache-Control: no-store, private');header('X-Content-Type-Options: nosniff');echo json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);exit;
        }catch(Throwable $error){self::deny(503);}
    }
}
