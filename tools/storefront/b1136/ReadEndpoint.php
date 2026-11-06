<?php
declare(strict_types=1);
/** Read-only native PHP facts, entered through the exact front controller before autoload. */
final class B1136ReadEndpoint
{
    private static function deny(int $status=403):never{http_response_code($status);header('Content-Type: application/json');header('Cache-Control: no-store, private');echo '{"status":"denied"}';exit;}
    private static function integer(mixed $value):?int{return is_int($value)&&$value>=0?$value:null;}
    private static function flag(string $name):?bool{$v=ini_get($name);return $v===false?null:filter_var($v,FILTER_VALIDATE_BOOLEAN,FILTER_NULL_ON_FAILURE);}
    public static function serve(string $path,string $id):never
    {
        try{
            $private=dirname($path);$keyPath=$private.'/signing-key';
            foreach([$path,$keyPath] as $file)if(is_link($file)||realpath($file)!==$file||!is_file($file)||(fileperms($file)&0777)!==0600||filesize($file)>16384)self::deny();
            if(realpath($private)!==$private||(fileperms($private)&0777)!==0700||filesize($keyPath)!==64||!function_exists('posix_geteuid')||fileowner($path)!==posix_geteuid())self::deny();
            $envelope=json_decode(file_get_contents($path),true,32,JSON_THROW_ON_ERROR);$payload=$envelope['payload']??null;
            if(!is_string($payload)||!hash_equals(hash_hmac('sha256',$payload,file_get_contents($keyPath)),(string)($envelope['hmac']??'')))self::deny();$c=json_decode($payload,true,32,JSON_THROW_ON_ERROR);
            if(($c['schemaVersion']??0)!==1||($c['phase']??'')!=='active'||($c['id']??'')!==$id||($c['expires']??0)<time()||$c['expires']>time()+600)self::deny();
            $bridgeSHA=$c['bridgeSHA256']??'';if(!is_string($bridgeSHA)||!preg_match('/^[a-f0-9]{64}$/D',$bridgeSHA))self::deny();
            $index=$c['activeRoot'].'/public/index.php';$maintenance=$c['activeRoot'].'/storage/framework/maintenance.php';
            if(realpath($c['incoming'])!==$c['incoming']||realpath(__FILE__)!==$c['incoming'].'/b1136-probe-'.$c['librarySHA256'].'.php'||hash_file('sha256',__FILE__)!==$c['librarySHA256']||realpath($_SERVER['SCRIPT_FILENAME']??'')!==$index||realpath($_SERVER['DOCUMENT_ROOT']??'')!==$c['activeRoot'].'/public'||hash_file('sha256',$index)!==$c['indexSHA256'])self::deny();
            if(!is_link($maintenance)||readlink($maintenance)!==$c['bridgePath']||realpath($c['bridgePath'])!==$c['incoming'].'/b1136-bridge-'.$bridgeSHA.'.php'||hash_file('sha256',$c['bridgePath'])!==$bridgeSHA)self::deny();
            if(($_SERVER['REQUEST_METHOD']??'')!=='POST'||parse_url($_SERVER['REQUEST_URI']??'',PHP_URL_PATH)!==$c['rpcPath'])self::deny();$token=$_SERVER['HTTP_X_ALD1N_READ_TOKEN']??'';if(!is_string($token)||!hash_equals($c['tokenSHA256'],hash('sha256',$token)))self::deny();
            $body=file_get_contents('php://input',false,null,0,1025);if(!is_string($body)||strlen($body)>1024)self::deny();$r=json_decode($body,true,16,JSON_THROW_ON_ERROR);if(array_keys($r)!==['action','nonce']||$r['action']!=='probe'||!is_string($r['nonce'])||!preg_match('/^[a-f0-9]{32}$/D',$r['nonce']))self::deny();
            $status=function_exists('opcache_get_status')?@opcache_get_status(false):false;$readable=is_array($status);$enabled=$readable&&is_bool($status['opcache_enabled']??null)?$status['opcache_enabled']:self::flag('opcache.enable');$full=$readable&&is_bool($status['cache_full']??null)?$status['cache_full']:null;$pending=$readable&&is_bool($status['restart_pending']??null)?$status['restart_pending']:null;$progress=$readable&&is_bool($status['restart_in_progress']??null)?$status['restart_in_progress']:null;$memory=$readable?($status['memory_usage']??[]):[];$stats=$readable?($status['opcache_statistics']??[]):[];
            $free=self::integer($memory['free_memory']??null);$scripts=self::integer($stats['num_cached_scripts']??null);$keys=self::integer($stats['max_cached_keys']??null);$state=$enabled===false?'DISABLED':(!$readable||$free===null||$full===null||$pending===null||$progress===null||$scripts===null||$keys===null?'UNKNOWN_OR_UNREADABLE':(($pending||$progress)?'RESTART_PENDING':($full?'FULL':'READABLE')));
            $server=$_SERVER['SERVER_SOFTWARE']??null;$server=is_string($server)?substr(preg_replace('/[^\x20-\x7e]/','',$server),0,100):null;
            $result=['status'=>'ok','id'=>$id,'nonce'=>$r['nonce'],'actualIndex'=>true,'librarySHA256'=>$c['librarySHA256'],'bridgeSHA256'=>$bridgeSHA,'sapi'=>PHP_SAPI,'php'=>PHP_VERSION,'pid'=>getmypid(),'ppid'=>function_exists('posix_getppid')?posix_getppid():null,'uid'=>posix_geteuid(),'serverSoftware'=>$server,'cacheState'=>$state,'statusReadable'=>$readable,'opcacheEnabled'=>$enabled,'cacheFull'=>$full,'restartPending'=>$pending,'restartInProgress'=>$progress,'freeMemoryBytes'=>$free,'usedMemoryBytes'=>self::integer($memory['used_memory']??null),'wastedMemoryBytes'=>self::integer($memory['wasted_memory']??null),'cachedScripts'=>$scripts,'maxCachedKeys'=>$keys,'cacheStartTime'=>self::integer($stats['start_time']??null),'fileOverrideEnabled'=>self::flag('opcache.enable_file_override'),'fileCacheOnly'=>self::flag('opcache.file_cache_only'),'fileCacheConfigured'=>ini_get('opcache.file_cache')===false?null:ini_get('opcache.file_cache')!==''];
            http_response_code(200);header('Content-Type: application/json');header('Cache-Control: no-store, private');header('X-Content-Type-Options: nosniff');echo json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);exit;
        }catch(Throwable $error){self::deny(503);}
    }
}
