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
$invalidate=($_SERVER['HTTP_X_ALD1N_RELEASE_INVALIDATE']??'')==='YES';$candidates=0;$invalidated=0;$failed=0;
if($invalidate&&$enabled){
    if($scripts===null)$failed++;
    else foreach($scripts as $key=>$info){
        $path=is_array($info)&&is_string($info['full_path']??null)?$info['full_path']:(is_string($key)?$key:'');$inside=false;
        foreach($roots as $root)if(str_starts_with($path,$root.'/')){$inside=true;break;}
        if(!$inside||$path===$self)continue;$candidates++;
        if(function_exists('opcache_invalidate')&&opcache_invalidate($path,true))$invalidated++;else $failed++;
    }
}
$hashes=[];foreach($files as $relative){$full=__DIR__.'/../'.$relative;$hashes[$relative]=is_file($full)&&!is_link($full)?hash_file('sha256',$full):null;}
$ini=strtolower(trim((string)ini_get('opcache.enable')));
echo json_encode(['kind'=>'B1144_FRESHNESS_PROBE','id'=>__ID__,'php'=>PHP_VERSION,'sapi'=>PHP_SAPI,'docrootMatches'=>$docroot!==false&&$here!==false&&hash_equals($docroot,$here),'opcacheLoaded'=>extension_loaded('Zend OPcache'),'opcacheIniEnabled'=>in_array($ini,['1','on','yes','true'],true),'opcacheEnabled'=>$enabled,'statusAccess'=>is_array($status),'scriptsListed'=>$scripts!==null,'validateTimestamps'=>(int)ini_get('opcache.validate_timestamps'),'revalidateFreq'=>(int)ini_get('opcache.revalidate_freq'),'fileCache'=>(string)ini_get('opcache.file_cache')!=='','fileCacheOnly'=>(int)ini_get('opcache.file_cache_only')===1,'invalidationRequested'=>$invalidate,'invalidationCandidates'=>$candidates,'invalidated'=>$invalidated,'invalidationFailed'=>$failed,'globalReset'=>false,'files'=>$hashes],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
PROBE;
        return strtr($template,$replace)."\n";
    }

    /** Never overwrites: exclusive create, 0644 for the web user, content re-verified. */
    public static function write(string $path,string $source):void
    {
        $public=dirname($path);if(!str_starts_with(basename($path),self::PREFIX)||is_link($public)||realpath($public)!==$public)throw new RuntimeException('FRESHNESS_WRITE_SCOPE');
        $h=@fopen($path,'x');if($h===false)throw new RuntimeException('FRESHNESS_PROBE_NAME_COLLISION');$ok=false;
        try{if(fwrite($h,$source)!==strlen($source)||!fflush($h))throw new RuntimeException('FRESHNESS_PROBE_WRITE');$ok=true;}finally{fclose($h);if(!$ok)@unlink($path);}
        if(!chmod($path,0644)||is_link($path)||hash_file('sha256',$path)!==hash('sha256',$source)||fileowner($path)!==posix_geteuid())throw new RuntimeException('FRESHNESS_PROBE_VERIFY');
    }

    /** Removes only the exact recorded probe. Absent is fine; foreign content at that name is a HOLD. */
    public static function remove(string $public,string $name,string $sha256):bool
    {
        if(!preg_match('/^'.preg_quote(self::PREFIX,'/').'[a-f0-9]{48}\.php$/D',$name))throw new RuntimeException('FRESHNESS_PROBE_NAME');if(!is_dir($public))return false;$path=$public.'/'.$name;clearstatcache(true,$path);
        if(!file_exists($path)&&!is_link($path))return false;if(is_link($path)||!is_file($path)||hash_file('sha256',$path)!==$sha256)throw new RuntimeException('FRESHNESS_PROBE_FOREIGN_CONTENT');
        if(!unlink($path))throw new RuntimeException('FRESHNESS_PROBE_REMOVE');clearstatcache(true,$path);if(file_exists($path))throw new RuntimeException('FRESHNESS_PROBE_REMOVE');return true;
    }

    public static function validate(array $r,string $id,array $expectedFiles,array $allowedSapis,bool $invalidationRequested):array
    {
        if(($r['kind']??null)!=='B1144_FRESHNESS_PROBE'||($r['id']??null)!==$id)throw new RuntimeException('FRESHNESS_PROBE_SCOPE');
        if(!in_array($r['sapi']??null,$allowedSapis,true))throw new RuntimeException('FRESHNESS_PROBE_SAPI');
        if(!is_string($r['php']??null)||!str_starts_with($r['php'],'8.4.'))throw new RuntimeException('FRESHNESS_PROBE_PHP');
        if(($r['docrootMatches']??null)!==true)throw new RuntimeException('FRESHNESS_PROBE_DOCROOT');
        $files=is_array($r['files']??null)?$r['files']:null;if($files!==null)ksort($files,SORT_STRING);ksort($expectedFiles,SORT_STRING);if($files!==$expectedFiles)throw new RuntimeException('FRESHNESS_PROBE_TREE_CONTENT');
        $ini=($r['opcacheIniEnabled']??null)===true;$enabled=($r['opcacheEnabled']??null)===true;
        if($ini&&(($r['opcacheLoaded']??null)!==true||($r['statusAccess']??null)!==true||!$enabled))throw new RuntimeException('FRESHNESS_OPCACHE_STATUS_UNAVAILABLE');
        if(!$ini&&$enabled)throw new RuntimeException('FRESHNESS_OPCACHE_STATE_AMBIGUOUS');
        if($enabled&&(($r['scriptsListed']??null)!==true||($r['fileCacheOnly']??null)!==false))throw new RuntimeException('FRESHNESS_OPCACHE_SCRIPTS_UNLISTED');
        foreach(['invalidationCandidates','invalidated','invalidationFailed','validateTimestamps','revalidateFreq'] as $key)if(!is_int($r[$key]??null)||$r[$key]<0)throw new RuntimeException('FRESHNESS_PROBE_FIELDS');
        if(($r['invalidationRequested']??null)!==$invalidationRequested||($r['globalReset']??null)!==false||$r['invalidationFailed']!==0||$r['invalidated']!==$r['invalidationCandidates'])throw new RuntimeException('FRESHNESS_TARGETED_INVALIDATION_FAILED');
        if($r['revalidateFreq']>60)throw new RuntimeException('FRESHNESS_REVALIDATE_FREQ_UNSUPPORTED');
        return ['sapi'=>$r['sapi'],'php'=>$r['php'],'docrootMatches'=>true,'treeFilesVerified'=>count($expectedFiles),'opcacheLoaded'=>($r['opcacheLoaded']??null)===true,'opcacheEnabled'=>$enabled,'validateTimestamps'=>$r['validateTimestamps'],'revalidateFreq'=>$r['revalidateFreq'],'fileCache'=>($r['fileCache']??null)===true,'targetedInvalidations'=>$r['invalidated'],'globalReset'=>false,'waitSeconds'=>$r['revalidateFreq']+2];
    }
}
