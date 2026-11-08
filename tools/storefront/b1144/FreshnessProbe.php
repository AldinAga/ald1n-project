<?php
declare(strict_types=1);

/**
 * One temporary, random-named, token-header-gated, GET-only, Host-checked, 1800-second web probe per tree.
 * It reports the web SAPI/OPcache profile, reads a few new-tree files and, on request, calls
 * opcache_invalidate($file,true) ONLY for cached scripts under the CMS release trees. It never resets OPcache.
 */
final class FreshnessProbe
{
    public const PREFIX='ald1n-b1144-';
    public const LIFETIME=1800;
    public const HOLD_MARGIN=5;
    public const MAX_HOLD_SECONDS=600;

    public static function assertNoLeftover(string $public):void
    {
        if(is_link($public)||realpath($public)!==$public||!is_dir($public))throw new RuntimeException('FRESHNESS_PUBLIC_SCOPE');$names=scandir($public);if(!is_array($names))throw new RuntimeException('FRESHNESS_PUBLIC_UNREADABLE');
        foreach($names as $name)if(str_starts_with($name,self::PREFIX))throw new RuntimeException('LEFTOVER_FRESHNESS_PROBE_REQUIRES_OPERATOR_INSPECTION');
    }

    /** The token exists only in the returned array (process memory); only its SHA-256 is embedded in the file. */
    public static function plan(string $id,array $roots,array $files,?int $now=null):array
    {
        if(!preg_match('/^[a-f0-9]{32}$/D',$id)||$roots===[]||$files===[]||count($files)>8)throw new RuntimeException('FRESHNESS_PLAN_SCOPE');
        foreach($roots as $root)if(!is_string($root)||!str_starts_with($root,'/')||str_ends_with($root,'/')||preg_match('/[\x00-\x1f\x7f]/',$root))throw new RuntimeException('FRESHNESS_ROOT_SCOPE');
        foreach($files as $relative){InstallFileOps::relative($relative);if(!str_ends_with($relative,'.php'))throw new RuntimeException('FRESHNESS_FILE_SCOPE');}
        $name=self::PREFIX.bin2hex(random_bytes(24)).'.php';$token=bin2hex(random_bytes(32));$expires=($now??time())+self::LIFETIME;$source=self::source($id,hash('sha256',$token),$expires,array_values($roots),array_values($files));
        return ['name'=>$name,'token'=>$token,'expires'=>$expires,'source'=>$source,'sha256'=>hash('sha256',$source)];
    }

    public static function source(string $id,string $tokenSHA256,int $expires,array $roots,array $files):string
    {
        if(!preg_match('/^[a-f0-9]{64}$/D',$tokenSHA256))throw new RuntimeException('FRESHNESS_TOKEN_HASH');
        $replace=['__EXPIRES__'=>(string)$expires,'__TOKEN__'=>var_export($tokenSHA256,true),'__ID__'=>var_export($id,true),'__ROOTS__'=>var_export($roots,true),'__FILES__'=>var_export($files,true)];
        $template=<<<'PROBE'
<?php
declare(strict_types=1);
// B1144 temporary freshness probe. Token-header-gated, GET-only, Host-checked, expiring; removed by the installer.
if(time()>__EXPIRES__||($_SERVER['REQUEST_METHOD']??'')!=='GET'||($_SERVER['HTTP_HOST']??'')!=='cms.ald1n.com'||!is_string($_SERVER['HTTP_X_ALD1N_RELEASE_PROBE']??null)||!hash_equals(__TOKEN__,hash('sha256',$_SERVER['HTTP_X_ALD1N_RELEASE_PROBE']))){http_response_code(404);exit;}
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: private, no-store, max-age=0');header('X-Robots-Tag: noindex, nofollow, noarchive');header("Content-Security-Policy: default-src 'none'");
$roots=__ROOTS__;$files=__FILES__;$docroot=realpath((string)($_SERVER['DOCUMENT_ROOT']??''));$here=realpath(__DIR__);$self=realpath(__FILE__);
$status=function_exists('opcache_get_status')?@opcache_get_status(true):false;$enabled=is_array($status)&&!empty($status['opcache_enabled']);$scripts=is_array($status)&&is_array($status['scripts']??null)?$status['scripts']:null;
$invalidate=($_SERVER['HTTP_X_ALD1N_RELEASE_INVALIDATE']??'')==='YES';$candidates=0;$invalidated=0;$failed=0;$vanished=0;
if($invalidate&&$enabled){
    if($scripts===null)$failed++;
    else foreach($scripts as $key=>$info){
        $path=is_array($info)&&is_string($info['full_path']??null)?$info['full_path']:(is_string($key)?$key:'');$inside=false;
        foreach($roots as $root)if(str_starts_with($path,$root.'/')){$inside=true;break;}
        if(!$inside||$path===$self)continue;
        // A cached script whose file no longer exists at that path (only one tree has it) cannot be resolved for invalidation; count it apart.
        if(!is_file($path)){$vanished++;continue;}$candidates++;
        if(function_exists('opcache_invalidate')&&opcache_invalidate($path,true))$invalidated++;else $failed++;
    }
}
$hashes=[];foreach($files as $relative){$full=__DIR__.'/../'.$relative;$hashes[$relative]=is_file($full)&&!is_link($full)?hash_file('sha256',$full):null;}
$ini=strtolower(trim((string)ini_get('opcache.enable')));$realpathTtl=ini_get('realpath_cache_ttl');$realpathSize=ini_get('realpath_cache_size');
// Raw ini strings: the installer derives the barrier hold from them. Worker realpath caches are per process and are NOT cleared here.
echo json_encode(['kind'=>'B1144_FRESHNESS_PROBE','id'=>__ID__,'php'=>PHP_VERSION,'sapi'=>PHP_SAPI,'realpathCacheTtl'=>is_string($realpathTtl)?$realpathTtl:null,'realpathCacheSize'=>is_string($realpathSize)?$realpathSize:null,'docrootMatches'=>$docroot!==false&&$here!==false&&hash_equals($docroot,$here),'opcacheLoaded'=>extension_loaded('Zend OPcache'),'opcacheIniEnabled'=>in_array($ini,['1','on','yes','true'],true),'opcacheEnabled'=>$enabled,'statusAccess'=>is_array($status),'scriptsListed'=>$scripts!==null,'validateTimestamps'=>(int)ini_get('opcache.validate_timestamps'),'revalidateFreq'=>(int)ini_get('opcache.revalidate_freq'),'fileCache'=>(string)ini_get('opcache.file_cache')!=='','fileCacheOnly'=>(int)ini_get('opcache.file_cache_only')===1,'invalidationRequested'=>$invalidate,'invalidationCandidates'=>$candidates,'invalidated'=>$invalidated,'invalidationFailed'=>$failed,'vanishedCachedScripts'=>$vanished,'globalReset'=>false,'files'=>$hashes],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
PROBE;
        return strtr($template,$replace)."\n";
    }

    /**
     * Crash-safe and never overwrites: the bytes are written, synced and verified in a private staging file first, then
     * hard-linked to the public name (link() fails if the name exists). A kill mid-write leaves only the private staging
     * file; the public name is either absent or complete. The staging file is removed afterwards and by removeStaging().
     */
    public static function write(string $path,string $source,string $staging):void
    {
        $public=dirname($path);if(!str_starts_with(basename($path),self::PREFIX)||is_link($public)||realpath($public)!==$public)throw new RuntimeException('FRESHNESS_WRITE_SCOPE');
        self::stagingScope($staging);if(file_exists($path)||is_link($path))throw new RuntimeException('FRESHNESS_PROBE_NAME_COLLISION');
        $h=@fopen($staging,'x');if($h===false)throw new RuntimeException('FRESHNESS_PROBE_STAGING_COLLISION');$ok=false;
        try{if(fwrite($h,$source)!==strlen($source)||!fflush($h)||(function_exists('fsync')&&!fsync($h)))throw new RuntimeException('FRESHNESS_PROBE_WRITE');$ok=true;}finally{fclose($h);if(!$ok)@unlink($staging);}
        if(!chmod($staging,0644)||is_link($staging)||hash_file('sha256',$staging)!==hash('sha256',$source)||fileowner($staging)!==posix_geteuid()){@unlink($staging);throw new RuntimeException('FRESHNESS_PROBE_VERIFY');}
        if(!@link($staging,$path)){@unlink($staging);throw new RuntimeException(file_exists($path)||is_link($path)?'FRESHNESS_PROBE_NAME_COLLISION':'FRESHNESS_PROBE_LINK');}
        @unlink($staging);clearstatcache(true,$path);
        if(is_link($path)||!is_file($path)||hash_file('sha256',$path)!==hash('sha256',$source))throw new RuntimeException('FRESHNESS_PROBE_VERIFY');
    }

    private static function stagingScope(string $staging):void
    {
        $directory=dirname($staging);if(!preg_match('/^probe-[a-f0-9]{16}\.staging$/D',basename($staging))||is_link($directory)||realpath($directory)!==$directory||(fileperms($directory)&0077)!==0)throw new RuntimeException('FRESHNESS_STAGING_SCOPE');
    }

    /** A staging file lives only in the private 0700 state directory, so whatever is there is ours (possibly partial). */
    public static function removeStaging(string $staging):bool
    {
        self::stagingScope($staging);clearstatcache(true,$staging);if(!file_exists($staging)&&!is_link($staging))return false;
        if(is_link($staging)||!is_file($staging)||!unlink($staging))throw new RuntimeException('FRESHNESS_STAGING_REMOVE');return true;
    }

    /** Removes only the exact recorded probe. Absent is fine; foreign content at that name is a HOLD. */
    public static function remove(string $public,string $name,string $sha256):bool
    {
        if(!preg_match('/^'.preg_quote(self::PREFIX,'/').'[a-f0-9]{48}\.php$/D',$name))throw new RuntimeException('FRESHNESS_PROBE_NAME');if(!is_dir($public))return false;$path=$public.'/'.$name;clearstatcache(true,$path);
        if(!file_exists($path)&&!is_link($path))return false;if(is_link($path)||!is_file($path)||hash_file('sha256',$path)!==$sha256)throw new RuntimeException('FRESHNESS_PROBE_FOREIGN_CONTENT');
        if(!unlink($path))throw new RuntimeException('FRESHNESS_PROBE_REMOVE');clearstatcache(true,$path);if(file_exists($path))throw new RuntimeException('FRESHNESS_PROBE_REMOVE');return true;
    }

    /**
     * Collect-all evaluation of every fixed gate, in the order validate() reports them, plus the host-derived values.
     * A gate is null when it passes, otherwise its HOLD code. Nothing throws, so one run shows every host blocker.
     */
    public static function assess(array $r,string $id,array $expectedFiles,array $allowedSapis,bool $invalidationRequested):array
    {
        $int=static fn(string $key):?int=>is_int($r[$key]??null)?$r[$key]:null;$g=[];
        $g['scope']=($r['kind']??null)==='B1144_FRESHNESS_PROBE'&&($r['id']??null)===$id?null:'FRESHNESS_PROBE_SCOPE';
        $g['sapi']=in_array($r['sapi']??null,$allowedSapis,true)?null:'FRESHNESS_PROBE_SAPI';
        $g['php84']=is_string($r['php']??null)&&str_starts_with($r['php'],'8.4.')?null:'FRESHNESS_PROBE_PHP';
        $g['docroot']=($r['docrootMatches']??null)===true?null:'FRESHNESS_PROBE_DOCROOT';
        $files=is_array($r['files']??null)?$r['files']:null;if($files!==null)ksort($files,SORT_STRING);ksort($expectedFiles,SORT_STRING);$g['treeContent']=$files===$expectedFiles?null:'FRESHNESS_PROBE_TREE_CONTENT';
        $ini=($r['opcacheIniEnabled']??null)===true;$enabled=($r['opcacheEnabled']??null)===true;
        $g['opcacheStatus']=$ini&&(($r['opcacheLoaded']??null)!==true||($r['statusAccess']??null)!==true||!$enabled)?'FRESHNESS_OPCACHE_STATUS_UNAVAILABLE':(!$ini&&$enabled?'FRESHNESS_OPCACHE_STATE_AMBIGUOUS':null);
        $g['scriptsListed']=$enabled&&(($r['scriptsListed']??null)!==true||($r['fileCacheOnly']??null)!==false)?'FRESHNESS_OPCACHE_SCRIPTS_UNLISTED':null;
        $g['fields']=null;foreach(['invalidationCandidates','invalidated','invalidationFailed','vanishedCachedScripts','validateTimestamps','revalidateFreq'] as $key)if($int($key)===null||$int($key)<0)$g['fields']='FRESHNESS_PROBE_FIELDS';
        $g['targetedInvalidation']=($r['invalidationRequested']??null)!==$invalidationRequested||($r['globalReset']??null)!==false||$int('invalidationFailed')!==0||$int('invalidated')!==$int('invalidationCandidates')?'FRESHNESS_TARGETED_INVALIDATION_FAILED':null;
        $frequency=$int('revalidateFreq');$g['revalidateFreq']=$frequency!==null&&$frequency>60?'FRESHNESS_REVALIDATE_FREQ_UNSUPPORTED':null;
        $ttl=$r['realpathCacheTtl']??null;$size=$r['realpathCacheSize']??null;
        $readable=is_string($ttl)&&preg_match('/^[0-9]{1,9}$/D',$ttl)===1&&is_string($size)&&preg_match('/^[0-9]{1,12}[KMGkmg]?$/D',$size)===1;$g['realpathIni']=$readable?null:'FRESHNESS_REALPATH_CACHE_UNREADABLE';
        $holdSeconds=$readable?self::holdSeconds((int)$ttl,$enabled,max(0,(int)$frequency)):null;
        $g['holdBound']=$holdSeconds!==null&&$holdSeconds>self::MAX_HOLD_SECONDS?'BARRIER_HOLD_EXCEEDS_BOUND':null;
        $values=['php'=>is_string($r['php']??null)?$r['php']:null,'sapi'=>is_string($r['sapi']??null)?$r['sapi']:null,'docrootMatches'=>($r['docrootMatches']??null)===true,'treeFilesExpected'=>count($expectedFiles),'opcacheLoaded'=>($r['opcacheLoaded']??null)===true,'opcacheIniEnabled'=>$ini,'opcacheEnabled'=>$enabled,'statusAccess'=>($r['statusAccess']??null)===true,'scriptsListed'=>($r['scriptsListed']??null)===true,'fileCache'=>($r['fileCache']??null)===true,'fileCacheOnly'=>($r['fileCacheOnly']??null)===true,'validateTimestamps'=>$int('validateTimestamps'),'revalidateFreq'=>$frequency,'realpathCacheTtl'=>$readable?(int)$ttl:(is_scalar($ttl)?(string)$ttl:null),'realpathCacheSize'=>is_scalar($size)?(string)$size:null,'holdSeconds'=>$holdSeconds,'maxHoldSeconds'=>self::MAX_HOLD_SECONDS];
        return ['gates'=>$g,'failures'=>array_values(array_filter($g,static fn($code)=>$code!==null)),'values'=>$values];
    }

    /** Barrier hold for one swap: every worker's realpath entry expires and OPcache (when enabled) revalidates, plus margin. */
    public static function holdSeconds(int $realpathTtl,bool $opcacheEnabled,int $revalidateFreq):int {return max($realpathTtl,$opcacheEnabled?$revalidateFreq:0)+self::HOLD_MARGIN;}

    /** Fail-fast form of assess() for the post-switch probes. The hold bound is enforced separately, from the swap time. */
    public static function validate(array $r,string $id,array $expectedFiles,array $allowedSapis,bool $invalidationRequested):array
    {
        $a=self::assess($r,$id,$expectedFiles,$allowedSapis,$invalidationRequested);foreach($a['gates'] as $gate=>$code)if($code!==null&&$gate!=='holdBound')throw new RuntimeException($code);
        $enabled=$a['values']['opcacheEnabled'];$ttl=(int)$r['realpathCacheTtl'];
        return ['sapi'=>$r['sapi'],'php'=>$r['php'],'docrootMatches'=>true,'treeFilesVerified'=>count($expectedFiles),'opcacheLoaded'=>($r['opcacheLoaded']??null)===true,'opcacheEnabled'=>$enabled,'validateTimestamps'=>$r['validateTimestamps'],'revalidateFreq'=>$r['revalidateFreq'],'fileCache'=>($r['fileCache']??null)===true,'targetedInvalidations'=>$r['invalidated'],'vanishedCachedScripts'=>$r['vanishedCachedScripts'],'globalReset'=>false,'waitSeconds'=>$r['revalidateFreq']+2,'realpathCacheTtl'=>$ttl,'realpathCacheSize'=>$r['realpathCacheSize'],'holdSeconds'=>self::holdSeconds($ttl,$enabled,$r['revalidateFreq'])];
    }

    /**
     * Earliest second at which the owned barrier may be lifted after a directory swap. Each web worker keeps its own
     * realpath cache (realpath_cache_ttl) of resolutions made BEFORE the swap, e.g. ".env" -> the retained tree; only
     * expiry clears it in other workers. Under the barrier no application request creates new entries, so every stale
     * entry is gone once realpath_cache_ttl has passed since the swap. The OPcache revalidation window runs from the probe.
     */
    public static function liftNotBefore(int $swappedAt,int $probedAt,array $summaries):int
    {
        if($summaries===[]||$swappedAt<=0||$probedAt<$swappedAt)throw new RuntimeException('BARRIER_HOLD_UNDERIVABLE');$ttl=0;$frequency=0;
        foreach($summaries as $s){
            if(!is_array($s)||!is_int($s['realpathCacheTtl']??null)||$s['realpathCacheTtl']<0||!is_int($s['revalidateFreq']??null)||$s['revalidateFreq']<0||!is_bool($s['opcacheEnabled']??null))throw new RuntimeException('FRESHNESS_REALPATH_CACHE_UNREADABLE');
            $ttl=max($ttl,$s['realpathCacheTtl']);if($s['opcacheEnabled'])$frequency=max($frequency,$s['revalidateFreq']);
        }
        return max($swappedAt+$ttl,$probedAt+$frequency)+self::HOLD_MARGIN;
    }

    /** Seconds still to hold; a hold longer than the bound is refused (rollback after install, retained barrier after restore). */
    public static function remaining(int $liftNotBefore,?int $now=null):int
    {
        $left=$liftNotBefore-($now??time());if($left>self::MAX_HOLD_SECONDS)throw new RuntimeException('BARRIER_HOLD_EXCEEDS_BOUND');return max(0,$left);
    }

    /** Monotonic nanoseconds plus the kernel boot id, so a reading from another process of the same boot is comparable. */
    public static function monotonic():array
    {
        $boot=@file_get_contents('/proc/sys/kernel/random/boot_id');return ['ns'=>hrtime(true),'boot'=>is_string($boot)&&preg_match('/^[a-f0-9-]{36}$/D',trim($boot))?trim($boot):null];
    }

    /**
     * Keeps the barrier until $liftNotBefore. When the swap's monotonic reading is known and comes from this boot, the wait
     * is measured on the monotonic clock from the swap (immune to wall-clock steps); otherwise from the wall clock, once.
     * The sleep itself always runs on hrtime and prints a heartbeat line every $beatSeconds.
     */
    public static function hold(int $liftNotBefore,?array $swap=null,string $label='',int $beatSeconds=15):array
    {
        $now=self::monotonic();$wallLeft=$liftNotBefore-time();$clock='WALL';$left=$wallLeft;
        if(is_array($swap)&&is_int($swap['ns']??null)&&is_int($swap['requiredSeconds']??null)&&($swap['boot']??null)!==null&&($swap['boot']??null)===$now['boot']&&$swap['ns']<=$now['ns']){$clock='MONOTONIC';$left=(int)ceil($swap['requiredSeconds']-($now['ns']-$swap['ns'])/1e9);}
        if($left>self::MAX_HOLD_SECONDS)throw new RuntimeException('BARRIER_HOLD_EXCEEDS_BOUND');$left=max(0,$left);
        $start=hrtime(true);$deadline=$start+$left*1000000000;$nextBeat=$start+$beatSeconds*1000000000;
        while(($rest=$deadline-hrtime(true))>0){
            usleep((int)max(1000,intdiv(min($rest,max(0,$nextBeat-hrtime(true)),1000000000),1000)));$t=hrtime(true);
            if($t>=$nextBeat&&$t<$deadline){echo 'BARRIER_HOLD_HEARTBEAT='.json_encode(['label'=>$label,'remainingSeconds'=>(int)ceil(($deadline-$t)/1e9),'clock'=>$clock],JSON_THROW_ON_ERROR).PHP_EOL;$nextBeat+=$beatSeconds*1000000000;}
        }
        return ['liftNotBefore'=>$liftNotBefore,'clock'=>$clock,'waitedSeconds'=>(int)round((hrtime(true)-$start)/1e9),'heldUntil'=>time()];
    }
}
