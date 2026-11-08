<?php
declare(strict_types=1);

/**
 * B1145 temporary web probe for exactly one file: the active config/public_storefront.php. Token-header-gated, GET-only,
 * Host-checked, expiring. On request it calls opcache_invalidate() for that single absolute path when it is cached; it
 * never resets OPcache and never touches any other script. It also reports whether the web server itself defines the two
 * PUBLIC_* keys (then Laravel's immutable .env loading could not change them). File handling reuses FreshnessProbe
 * (same name prefix, crash-safe staging plus hard link, exact-digest removal), so LeanWeb::probe() is reused unchanged.
 * @author Ald1n <https://ald1n.com>
 */
final class UnclampProbe
{
    public const KIND='B1145_UNCLAMP_PROBE';

    public static function plan(string $id,string $target,?int $now=null):array
    {
        if(!preg_match('/^[a-f0-9]{32}$/D',$id)||!str_starts_with($target,'/')||!str_ends_with($target,'/config/public_storefront.php')||preg_match('/[\x00-\x1f\x7f]/',$target))throw new RuntimeException('UNCLAMP_PROBE_PLAN_SCOPE');
        $name=FreshnessProbe::PREFIX.bin2hex(random_bytes(24)).'.php';$token=bin2hex(random_bytes(32));$expires=($now??time())+FreshnessProbe::LIFETIME;
        $source=self::source($id,hash('sha256',$token),$expires,$target);return ['name'=>$name,'token'=>$token,'expires'=>$expires,'source'=>$source,'sha256'=>hash('sha256',$source)];
    }

    public static function source(string $id,string $tokenSHA256,int $expires,string $target):string
    {
        if(!preg_match('/^[a-f0-9]{64}$/D',$tokenSHA256))throw new RuntimeException('UNCLAMP_PROBE_TOKEN_HASH');
        $replace=['__EXPIRES__'=>(string)$expires,'__TOKEN__'=>var_export($tokenSHA256,true),'__ID__'=>var_export($id,true),'__TARGET__'=>var_export($target,true),'__KEYS__'=>var_export(EnvFlags::KEYS,true),'__KIND__'=>var_export(self::KIND,true)];
        $template=<<<'PROBE'
<?php
declare(strict_types=1);
// B1145 temporary unclamp probe. Token-header-gated, GET-only, Host-checked, expiring; removed by the operator script.
if(time()>__EXPIRES__||($_SERVER['REQUEST_METHOD']??'')!=='GET'||($_SERVER['HTTP_HOST']??'')!=='cms.ald1n.com'||!is_string($_SERVER['HTTP_X_ALD1N_RELEASE_PROBE']??null)||!hash_equals(__TOKEN__,hash('sha256',$_SERVER['HTTP_X_ALD1N_RELEASE_PROBE']))){http_response_code(404);exit;}
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: private, no-store, max-age=0');header('X-Robots-Tag: noindex, nofollow, noarchive');header("Content-Security-Policy: default-src 'none'");
$target=__TARGET__;$keys=__KEYS__;$docroot=realpath((string)($_SERVER['DOCUMENT_ROOT']??''));$here=realpath(__DIR__);
$status=function_exists('opcache_get_status')?@opcache_get_status(true):false;$enabled=is_array($status)&&!empty($status['opcache_enabled']);$scripts=is_array($status)&&is_array($status['scripts']??null)?$status['scripts']:null;
$cached=null;$stamp=null;if($enabled&&$scripts!==null){$cached=false;foreach($scripts as $key=>$info){$path=is_array($info)&&is_string($info['full_path']??null)?$info['full_path']:(is_string($key)?$key:'');if($path===$target){$cached=true;$stamp=is_int($info['timestamp']??null)?$info['timestamp']:null;break;}}}
clearstatcache(true,$target);$mtime=@filemtime($target);$stale=$cached===true&&(!is_int($stamp)||!is_int($mtime)||$stamp<$mtime);
$invalidate=($_SERVER['HTTP_X_ALD1N_RELEASE_INVALIDATE']??'')==='YES';$invalidated=false;$failed=false;
if($invalidate&&$enabled){if($scripts===null)$failed=true;elseif($cached===true){if(function_exists('opcache_invalidate')&&opcache_invalidate($target,true))$invalidated=true;else $failed=true;}}
$server=[];foreach($keys as $key)$server[$key]=getenv($key)!==false||array_key_exists($key,$_SERVER)||array_key_exists($key,$_ENV);
$ini=strtolower(trim((string)ini_get('opcache.enable')));
echo json_encode(['kind'=>__KIND__,'id'=>__ID__,'php'=>PHP_VERSION,'sapi'=>PHP_SAPI,'docrootMatches'=>$docroot!==false&&$here!==false&&hash_equals($docroot,$here),'opcacheLoaded'=>extension_loaded('Zend OPcache'),'opcacheIniEnabled'=>in_array($ini,['1','on','yes','true'],true),'opcacheEnabled'=>$enabled,'statusAccess'=>is_array($status),'scriptsListed'=>$scripts!==null,'fileCacheOnly'=>(int)ini_get('opcache.file_cache_only')===1,'validateTimestamps'=>(int)ini_get('opcache.validate_timestamps'),'revalidateFreq'=>(int)ini_get('opcache.revalidate_freq'),'invalidationRequested'=>$invalidate,'targetCached'=>$cached,'targetCachedStale'=>$stale,'invalidated'=>$invalidated,'invalidationFailed'=>$failed,'targetSHA256'=>is_file($target)&&!is_link($target)?hash_file('sha256',$target):null,'publicKeysAtServerLevel'=>$server,'globalReset'=>false],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
PROBE;
        return strtr($template,$replace)."\n";
    }

    /** Collect-all: every gate is null (PASS) or its HOLD code; nothing throws. */
    public static function assess(array $r,string $id,string $expectedSHA256,array $allowedSapis,bool $invalidationRequested,bool $requireFresh):array
    {
        $g=[];$enabled=($r['opcacheEnabled']??null)===true;$ini=($r['opcacheIniEnabled']??null)===true;
        $g['scope']=($r['kind']??null)===self::KIND&&($r['id']??null)===$id?null:'UNCLAMP_PROBE_SCOPE';
        $g['sapi']=in_array($r['sapi']??null,$allowedSapis,true)?null:'UNCLAMP_PROBE_SAPI';
        $g['php84']=is_string($r['php']??null)&&str_starts_with($r['php'],'8.4.')?null:'UNCLAMP_PROBE_PHP';
        $g['docroot']=($r['docrootMatches']??null)===true?null:'UNCLAMP_PROBE_DOCROOT';
        $g['opcacheStatus']=$ini&&(($r['opcacheLoaded']??null)!==true||($r['statusAccess']??null)!==true||!$enabled)?'UNCLAMP_OPCACHE_STATUS_UNAVAILABLE':(!$ini&&$enabled?'UNCLAMP_OPCACHE_STATE_AMBIGUOUS':null);
        $g['scriptsListed']=$enabled&&(($r['scriptsListed']??null)!==true||($r['fileCacheOnly']??null)!==false)?'UNCLAMP_OPCACHE_SCRIPTS_UNLISTED':null;
        $g['validateTimestamps']=$enabled&&($r['validateTimestamps']??null)!==1?'UNCLAMP_OPCACHE_VALIDATE_TIMESTAMPS_OFF':null;
        $g['revalidateFreq']=!is_int($r['revalidateFreq']??null)||$r['revalidateFreq']<0||$r['revalidateFreq']>60?'UNCLAMP_REVALIDATE_FREQ_UNSUPPORTED':null;
        $g['targetContent']=($r['targetSHA256']??null)===$expectedSHA256?null:'UNCLAMP_PROBE_TARGET_CONTENT';
        $server=is_array($r['publicKeysAtServerLevel']??null)?$r['publicKeysAtServerLevel']:null;
        $g['serverLevelKeys']=$server!==null&&array_keys($server)===EnvFlags::KEYS&&!in_array(true,$server,true)&&!in_array(null,$server,true)?null:'PUBLIC_KEYS_DEFINED_BY_WEB_SERVER_ENVIRONMENT';
        $g['targetedInvalidation']=($r['invalidationRequested']??null)!==$invalidationRequested||($r['globalReset']??null)!==false||($r['invalidationFailed']??null)!==false||($invalidationRequested&&($r['targetCached']??null)===true&&($r['invalidated']??null)!==true)?'UNCLAMP_TARGETED_INVALIDATION_FAILED':null;
        $g['fresh']=$requireFresh&&($r['targetCachedStale']??null)!==false?'UNCLAMP_WEB_CONFIG_STALE':null;
        $values=['php'=>is_string($r['php']??null)?$r['php']:null,'sapi'=>is_string($r['sapi']??null)?$r['sapi']:null,'opcacheEnabled'=>$enabled,'validateTimestamps'=>is_int($r['validateTimestamps']??null)?$r['validateTimestamps']:null,'revalidateFreq'=>is_int($r['revalidateFreq']??null)?$r['revalidateFreq']:null,'targetCached'=>$r['targetCached']??null,'targetCachedStale'=>$r['targetCachedStale']??null,'invalidated'=>($r['invalidated']??null)===true,'publicKeysAtServerLevel'=>$server];
        return ['gates'=>$g,'failures'=>array_values(array_filter($g,static fn($code)=>$code!==null)),'values'=>$values];
    }
}
