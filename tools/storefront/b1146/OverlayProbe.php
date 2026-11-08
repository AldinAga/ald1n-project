<?php
declare(strict_types=1);

/**
 * B1146 temporary web probe for an explicit list of CMS files (the replaced and new PHP sources, the route cache file when
 * present) plus one directory (the real compiled-views directory). Token-header-gated, GET-only, Host-checked, expiring.
 * On request it calls opcache_invalidate($file,true) for each listed file that OPcache holds and for each cached compiled
 * view that still exists in that one directory. It never resets OPcache and never touches any other script. It reports
 * the sha256 the web worker itself reads for every listed file, so a stale or unexpected file on the web side is visible.
 * File handling reuses FreshnessProbe (same name prefix, crash-safe staging plus hard link, exact-digest removal), so
 * LeanWeb::probe() is reused unchanged.
 * @author Ald1n <https://ald1n.com>
 */
final class OverlayProbe
{
    public const KIND='B1146_OVERLAY_PROBE';
    public const MAX_TARGETS=40;

    /** $targets: absolute path => expected sha256 (null = the file must be absent). */
    public static function plan(string $id,array $targets,string $viewsDirectory,?int $now=null):array
    {
        if(!preg_match('/^[a-f0-9]{32}$/D',$id)||$targets===[]||count($targets)>self::MAX_TARGETS)throw new RuntimeException('OVERLAY_PROBE_PLAN_SCOPE');
        foreach($targets as $path=>$sha)if(!is_string($path)||!str_starts_with($path,'/')||str_contains($path,'/../')||preg_match('/[\x00-\x1f\x7f]/',$path)||($sha!==null&&!preg_match('/^[a-f0-9]{64}$/D',(string)$sha)))throw new RuntimeException('OVERLAY_PROBE_TARGET_SCOPE');
        if(!str_starts_with($viewsDirectory,'/')||str_ends_with($viewsDirectory,'/')||!str_ends_with($viewsDirectory,'/framework/views')||preg_match('/[\x00-\x1f\x7f]/',$viewsDirectory))throw new RuntimeException('OVERLAY_PROBE_VIEWS_SCOPE');
        $name=FreshnessProbe::PREFIX.bin2hex(random_bytes(24)).'.php';$token=bin2hex(random_bytes(32));$expires=($now??time())+FreshnessProbe::LIFETIME;
        $source=self::source($id,hash('sha256',$token),$expires,array_keys($targets),$viewsDirectory);return ['name'=>$name,'token'=>$token,'expires'=>$expires,'source'=>$source,'sha256'=>hash('sha256',$source)];
    }

    public static function source(string $id,string $tokenSHA256,int $expires,array $paths,string $viewsDirectory):string
    {
        if(!preg_match('/^[a-f0-9]{64}$/D',$tokenSHA256))throw new RuntimeException('OVERLAY_PROBE_TOKEN_HASH');
        $replace=['__EXPIRES__'=>(string)$expires,'__TOKEN__'=>var_export($tokenSHA256,true),'__ID__'=>var_export($id,true),'__PATHS__'=>var_export(array_values($paths),true),'__VIEWS__'=>var_export($viewsDirectory,true),'__KIND__'=>var_export(self::KIND,true)];
        $template=<<<'PROBE'
<?php
declare(strict_types=1);
// B1146 temporary overlay probe. Token-header-gated, GET-only, Host-checked, expiring; removed by the operator script.
if(time()>__EXPIRES__||($_SERVER['REQUEST_METHOD']??'')!=='GET'||($_SERVER['HTTP_HOST']??'')!=='cms.ald1n.com'||!is_string($_SERVER['HTTP_X_ALD1N_RELEASE_PROBE']??null)||!hash_equals(__TOKEN__,hash('sha256',$_SERVER['HTTP_X_ALD1N_RELEASE_PROBE']))){http_response_code(404);exit;}
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: private, no-store, max-age=0');header('X-Robots-Tag: noindex, nofollow, noarchive');header("Content-Security-Policy: default-src 'none'");
$paths=__PATHS__;$views=__VIEWS__;$docroot=realpath((string)($_SERVER['DOCUMENT_ROOT']??''));$here=realpath(__DIR__);$self=realpath(__FILE__);
$status=function_exists('opcache_get_status')?@opcache_get_status(true):false;$enabled=is_array($status)&&!empty($status['opcache_enabled']);$scripts=is_array($status)&&is_array($status['scripts']??null)?$status['scripts']:null;
$cache=[];if($scripts!==null)foreach($scripts as $key=>$info){$path=is_array($info)&&is_string($info['full_path']??null)?$info['full_path']:(is_string($key)?$key:'');if($path!=='')$cache[$path]=is_int($info['timestamp']??null)?$info['timestamp']:null;}
$invalidate=($_SERVER['HTTP_X_ALD1N_RELEASE_INVALIDATE']??'')==='YES';$invalidated=0;$failed=0;$vanished=0;$viewInvalidated=0;
if($invalidate&&$enabled){
    if($scripts===null)$failed++;
    else{
        foreach($paths as $path){if(!array_key_exists($path,$cache))continue;clearstatcache(true,$path);if(!is_file($path)){$vanished++;continue;}if(function_exists('opcache_invalidate')&&opcache_invalidate($path,true))$invalidated++;else $failed++;}
        foreach($cache as $path=>$stamp){if(!str_starts_with($path,$views.'/')||$path===$self)continue;clearstatcache(true,$path);if(!is_file($path)){$vanished++;continue;}if(function_exists('opcache_invalidate')&&opcache_invalidate($path,true))$viewInvalidated++;else $failed++;}
    }
}
$files=[];
foreach($paths as $path){
    clearstatcache(true,$path);$exists=is_file($path)&&!is_link($path);$mtime=$exists?@filemtime($path):false;$cached=$enabled&&$scripts!==null?array_key_exists($path,$cache):null;$stamp=$cached===true?$cache[$path]:null;
    $stale=$cached===true&&$exists&&(!is_int($stamp)||($stamp>0&&(!is_int($mtime)||$stamp<$mtime)));
    $files[$path]=['sha256'=>$exists?hash_file('sha256',$path):null,'cached'=>$cached,'stale'=>$stale,'awaitingRecompile'=>$cached===true&&$stamp===0];
}
$ini=strtolower(trim((string)ini_get('opcache.enable')));$realpathTtl=ini_get('realpath_cache_ttl');$realpathSize=ini_get('realpath_cache_size');
echo json_encode(['kind'=>__KIND__,'id'=>__ID__,'php'=>PHP_VERSION,'sapi'=>PHP_SAPI,'euid'=>function_exists('posix_geteuid')?posix_geteuid():null,'docrootMatches'=>$docroot!==false&&$here!==false&&hash_equals($docroot,$here),'opcacheLoaded'=>extension_loaded('Zend OPcache'),'opcacheIniEnabled'=>in_array($ini,['1','on','yes','true'],true),'opcacheEnabled'=>$enabled,'statusAccess'=>is_array($status),'scriptsListed'=>$scripts!==null,'fileCacheOnly'=>(int)ini_get('opcache.file_cache_only')===1,'validateTimestamps'=>(int)ini_get('opcache.validate_timestamps'),'revalidateFreq'=>(int)ini_get('opcache.revalidate_freq'),'realpathCacheTtl'=>is_string($realpathTtl)?$realpathTtl:null,'realpathCacheSize'=>is_string($realpathSize)?$realpathSize:null,'invalidationRequested'=>$invalidate,'invalidated'=>$invalidated,'compiledViewsInvalidated'=>$viewInvalidated,'invalidationFailed'=>$failed,'vanishedCachedScripts'=>$vanished,'globalReset'=>false,'files'=>$files],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
PROBE;
        return strtr($template,$replace)."\n";
    }

    /**
     * Collect-all: every gate is null (PASS) or its HOLD code; nothing throws. $expected: absolute path => sha256|null.
     * With $requireFresh every listed file that exists must not be held stale by OPcache.
     */
    public static function assess(array $r,string $id,array $expected,array $allowedSapis,bool $invalidationRequested,bool $requireFresh,?int $operatorEuid=null):array
    {
        $g=[];$int=static fn(string $k):?int=>is_int($r[$k]??null)?$r[$k]:null;$enabled=($r['opcacheEnabled']??null)===true;$ini=($r['opcacheIniEnabled']??null)===true;
        $g['scope']=($r['kind']??null)===self::KIND&&($r['id']??null)===$id?null:'OVERLAY_PROBE_SCOPE';
        // The web worker must run as the operator account (suEXEC): files it reads and the caches it writes share one owner.
        $g['webEuid']=$operatorEuid!==null&&$int('euid')!==null&&$int('euid')!==$operatorEuid?'OVERLAY_WEB_EUID_NOT_OPERATOR':null;
        $g['sapi']=in_array($r['sapi']??null,$allowedSapis,true)?null:'OVERLAY_PROBE_SAPI';
        $g['php84']=is_string($r['php']??null)&&str_starts_with($r['php'],'8.4.')?null:'OVERLAY_PROBE_PHP';
        $g['docroot']=($r['docrootMatches']??null)===true?null:'OVERLAY_PROBE_DOCROOT';
        $g['opcacheStatus']=$ini&&(($r['opcacheLoaded']??null)!==true||($r['statusAccess']??null)!==true||!$enabled)?'OVERLAY_OPCACHE_STATUS_UNAVAILABLE':(!$ini&&$enabled?'OVERLAY_OPCACHE_STATE_AMBIGUOUS':null);
        $g['scriptsListed']=$enabled&&(($r['scriptsListed']??null)!==true||($r['fileCacheOnly']??null)!==false)?'OVERLAY_OPCACHE_SCRIPTS_UNLISTED':null;
        $g['validateTimestamps']=$enabled&&$int('validateTimestamps')!==1?'OVERLAY_OPCACHE_VALIDATE_TIMESTAMPS_OFF':null;
        $frequency=$int('revalidateFreq');$g['revalidateFreq']=$frequency===null||$frequency<0||$frequency>60?'OVERLAY_REVALIDATE_FREQ_UNSUPPORTED':null;
        $ttl=$r['realpathCacheTtl']??null;$readable=is_string($ttl)&&preg_match('/^[0-9]{1,9}$/D',$ttl)===1;$g['realpathIni']=$readable?null:'OVERLAY_REALPATH_CACHE_UNREADABLE';
        $hold=$readable&&$frequency!==null?self::holdSeconds((int)$ttl,$enabled,max(0,$frequency)):null;$g['holdBound']=$hold!==null&&$hold>FreshnessProbe::MAX_HOLD_SECONDS?'OVERLAY_HOLD_EXCEEDS_BOUND':null;
        foreach(['invalidated','compiledViewsInvalidated','invalidationFailed','vanishedCachedScripts'] as $k)if($int($k)===null||$int($k)<0)$g['fields']='OVERLAY_PROBE_FIELDS';$g['fields']??=null;
        $g['targetedInvalidation']=($r['invalidationRequested']??null)!==$invalidationRequested||($r['globalReset']??null)!==false||$int('invalidationFailed')!==0?'OVERLAY_TARGETED_INVALIDATION_FAILED':null;
        $files=is_array($r['files']??null)?$r['files']:[];$content=[];$stale=[];
        foreach($expected as $path=>$sha){$f=$files[$path]??null;if(!is_array($f)||($f['sha256']??null)!==$sha)$content[]=$path;elseif($requireFresh&&($f['stale']??null)!==false)$stale[]=$path;}
        if(count($files)!==count($expected))$content[]='FILE_SET';
        $g['webTreeContent']=$content===[]?null:'OVERLAY_WEB_TREE_CONTENT';$g['fresh']=$stale===[]?null:'OVERLAY_WEB_CODE_STALE';
        $values=['php'=>is_string($r['php']??null)?$r['php']:null,'sapi'=>is_string($r['sapi']??null)?$r['sapi']:null,'webEuidIsOperator'=>$int('euid')===null?null:$int('euid')===$operatorEuid,'opcacheEnabled'=>$enabled,'validateTimestamps'=>$int('validateTimestamps'),'revalidateFreq'=>$frequency,'realpathCacheTtl'=>$readable?(int)$ttl:null,'realpathCacheSize'=>is_scalar($r['realpathCacheSize']??null)?(string)$r['realpathCacheSize']:null,'holdSeconds'=>$hold,
            'targets'=>count($expected),'targetsCached'=>count(array_filter($files,static fn($f)=>is_array($f)&&($f['cached']??null)===true)),'invalidated'=>$int('invalidated'),'compiledViewsInvalidated'=>$int('compiledViewsInvalidated'),'vanishedCachedScripts'=>$int('vanishedCachedScripts'),'contentMismatches'=>count($content),'staleTargets'=>count($stale),'globalReset'=>false];
        return ['gates'=>$g,'failures'=>array_values(array_filter($g,static fn($c)=>$c!==null)),'values'=>$values];
    }

    /** Wait after the last rename: every worker's realpath entry expires and OPcache (when enabled) revalidates, plus 5 s. */
    public static function holdSeconds(int $realpathTtl,bool $opcacheEnabled,int $revalidateFreq):int {return max($realpathTtl,$opcacheEnabled?$revalidateFreq:0)+FreshnessProbe::HOLD_MARGIN;}
}
