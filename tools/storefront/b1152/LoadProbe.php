<?php
declare(strict_types=1);

/**
 * B1152 temporary web probe that proves, in the WEB SAPI (the very process type that serves the shop, not the CLI), that the new
 * classes of the payload load through the application's own composer autoloader (PSR-4) and that the file each class comes from
 * holds exactly the payload bytes. bootstrap/app.php names PinAppUrlOrigin::class, so it must never go live before both classes
 * load in a web worker; the coordinator refuses that step without this proof.
 * Token-header-gated, GET-only, Host-checked, expiring, writes nothing, never boots the application (only vendor/autoload.php is
 * required) and never calls any class method. File handling reuses FreshnessProbe (same name prefix, crash-safe staging plus hard
 * link, exact-digest removal), so LeanWeb::probe() is reused unchanged.
 * @author Ald1n <https://ald1n.com>
 */
final class OriginPinLoadProbe
{
    public const KIND='B1152_CLASS_LOAD_PROBE';
    public const MAX_CLASSES=8;

    /** $classes: fully qualified class name => [absolute path of the file it must come from, sha256 of the shipped bytes]. */
    public static function plan(string $id,array $classes,string $origin,?int $now=null):array
    {
        if(!preg_match('/^[a-f0-9]{32}$/D',$id)||$classes===[]||count($classes)>self::MAX_CLASSES||preg_match('~^https://[a-z0-9.-]{1,120}$~D',$origin)!==1)throw new RuntimeException('LOAD_PROBE_PLAN_SCOPE');
        foreach($classes as $name=>$want)if(!is_string($name)||!preg_match('/^App\\\\[A-Za-z0-9_\\\\]{1,200}$/D',$name)||!is_array($want)||count($want)!==2||!is_string($want[0])||!str_starts_with($want[0],'/')||str_contains($want[0],'/../')||preg_match('/[\x00-\x1f\x7f]/',$want[0])||!is_string($want[1])||!preg_match('/^[a-f0-9]{64}$/D',$want[1]))throw new RuntimeException('LOAD_PROBE_CLASS_SCOPE');
        $name=FreshnessProbe::PREFIX.bin2hex(random_bytes(24)).'.php';$token=bin2hex(random_bytes(32));$expires=($now??time())+FreshnessProbe::LIFETIME;
        $source=self::source($id,hash('sha256',$token),$expires,$classes,$origin);return ['name'=>$name,'token'=>$token,'expires'=>$expires,'source'=>$source,'sha256'=>hash('sha256',$source)];
    }

    public static function source(string $id,string $tokenSHA256,int $expires,array $classes,string $origin):string
    {
        if(!preg_match('/^[a-f0-9]{64}$/D',$tokenSHA256))throw new RuntimeException('LOAD_PROBE_TOKEN_HASH');
        $replace=['__ORIGIN__'=>var_export($origin,true),'__EXPIRES__'=>(string)$expires,'__TOKEN__'=>var_export($tokenSHA256,true),'__ID__'=>var_export($id,true),'__CLASSES__'=>var_export($classes,true),'__KIND__'=>var_export(self::KIND,true)];
        $template=<<<'PROBE'
<?php
declare(strict_types=1);
// B1152 temporary class-load probe. Token-header-gated, GET-only, Host-checked, expiring; removed by the operator script.
if(time()>__EXPIRES__||($_SERVER['REQUEST_METHOD']??'')!=='GET'||($_SERVER['HTTP_HOST']??'')!=='cms.ald1n.com'||!is_string($_SERVER['HTTP_X_ALD1N_RELEASE_PROBE']??null)||!hash_equals(__TOKEN__,hash('sha256',$_SERVER['HTTP_X_ALD1N_RELEASE_PROBE']))){http_response_code(404);exit;}
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: private, no-store, max-age=0');header('X-Robots-Tag: noindex, nofollow, noarchive');header("Content-Security-Policy: default-src 'none'");
$classes=__CLASSES__;$docroot=realpath((string)($_SERVER['DOCUMENT_ROOT']??''));$here=realpath(__DIR__);
$autoload=realpath(__DIR__.'/../vendor/autoload.php');$autoloaded=false;
if(is_string($autoload)&&is_file($autoload)){try{require_once $autoload;$autoloaded=true;}catch(Throwable){$autoloaded=false;}}
// The app.url this web worker sees, WITHOUT booting the application: Laravel's own precedence (a value in the server environment wins, otherwise the first
// APP_URL line of the .env file). Only a boolean and the origin (scheme://host[:port], credentials, path and query dropped) are reported, never the raw value.
$rawUrl=null;$urlSource='NONE';
foreach(['SERVER'=>$_SERVER['APP_URL']??null,'ENV'=>$_ENV['APP_URL']??null,'GETENV'=>getenv('APP_URL')] as $k=>$v)if(is_string($v)&&$v!==''){$rawUrl=$v;$urlSource='SERVER_ENVIRONMENT';break;}
if($rawUrl===null){$envFile=__DIR__.'/../.env';if(is_file($envFile)&&is_readable($envFile))foreach(file($envFile,FILE_IGNORE_NEW_LINES)?:[] as $line){if(preg_match('/^\s*(?:export\s+)?APP_URL\s*=\s*(.*)$/',$line,$m)===1){$v=trim($m[1]);if($v!==''&&($v[0]==='"'||$v[0]==="'")){$end=strpos($v,$v[0],1);$v=$end===false?substr($v,1):substr($v,1,$end-1);}else{$v=trim((string)preg_replace('/\s+#.*$/','',$v));}$rawUrl=$v;$urlSource='DOTENV_FILE';break;}}}
$urlOrigin=null;if(is_string($rawUrl)){$pu=parse_url($rawUrl);if(is_array($pu)&&isset($pu['scheme'],$pu['host'])&&preg_match('/^[a-z0-9.-]{1,120}$/D',strtolower($pu['host']))===1&&in_array(strtolower($pu['scheme']),['http','https'],true))$urlOrigin=strtolower($pu['scheme']).'://'.strtolower($pu['host']).(isset($pu['port'])?':'.(int)$pu['port']:'');}
$urlExact=is_string($rawUrl)&&hash_equals(__ORIGIN__,$rawUrl);
$out=[];
foreach($classes as $name=>$want){
    $loaded=false;$file=null;$sha=null;
    if($autoloaded){try{$loaded=class_exists($name,true);if($loaded){$f=(new ReflectionClass($name))->getFileName();$file=is_string($f)?realpath($f):null;$sha=is_string($file)?hash_file('sha256',$file):null;}}catch(Throwable){$loaded=false;}}
    $out[$name]=['loaded'=>$loaded,'fromExpectedFile'=>is_string($file)&&hash_equals($want[0],$file),'sha256'=>$sha];
}
$status=function_exists('opcache_get_status')?@opcache_get_status(false):false;$enabled=is_array($status)&&!empty($status['opcache_enabled']);
echo json_encode(['kind'=>__KIND__,'id'=>__ID__,'php'=>PHP_VERSION,'sapi'=>PHP_SAPI,'euid'=>function_exists('posix_geteuid')?posix_geteuid():null,'docrootMatches'=>$docroot!==false&&$here!==false&&hash_equals($docroot,$here),'autoloaded'=>$autoloaded,'opcacheEnabled'=>$enabled,'appUrlExact'=>$urlExact,'appUrlOrigin'=>$urlOrigin,'appUrlSource'=>$urlSource,'classes'=>$out],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
PROBE;
        return strtr($template,$replace)."\n";
    }

    /** Collect-all: every gate is null (PASS) or its HOLD code; nothing throws. $classes as in plan(). */
    public static function assess(array $r,string $id,array $classes,array $allowedSapis,?int $operatorEuid=null):array
    {
        $g=[];$euid=is_int($r['euid']??null)?$r['euid']:null;
        $g['scope']=($r['kind']??null)===self::KIND&&($r['id']??null)===$id?null:'LOAD_PROBE_SCOPE';
        $g['webEuid']=$operatorEuid!==null&&$euid!==null&&$euid!==$operatorEuid?'LOAD_PROBE_WEB_EUID_NOT_OPERATOR':null;
        $g['sapi']=in_array($r['sapi']??null,$allowedSapis,true)?null:'LOAD_PROBE_SAPI';
        $g['docroot']=($r['docrootMatches']??null)===true?null:'LOAD_PROBE_DOCROOT';
        $g['autoloader']=($r['autoloaded']??null)===true?null:'LOAD_PROBE_AUTOLOADER_UNAVAILABLE_IN_THE_WEB_SAPI';
        // The web worker's own app.url must be exactly the pinned origin: otherwise the middleware would pin every URL to a wrong origin.
        $g['appUrl']=($r['appUrlExact']??null)===true?null:'LOAD_PROBE_APP_URL_NOT_THE_PINNED_ORIGIN';
        $got=is_array($r['classes']??null)?$r['classes']:[];$notLoaded=[];$wrong=[];
        foreach($classes as $name=>$want){
            $c=$got[$name]??null;
            if(!is_array($c)||($c['loaded']??null)!==true){$notLoaded[]=$name;continue;}
            if(($c['fromExpectedFile']??null)!==true||($c['sha256']??null)!==$want[1])$wrong[]=$name;
        }
        if(count($got)!==count($classes))$notLoaded[]='CLASS_SET';
        $g['classesLoadable']=$notLoaded===[]?null:'WEB_SAPI_CLASS_NOT_LOADABLE';$g['classesPayload']=$wrong===[]?null:'WEB_SAPI_CLASS_NOT_THE_PAYLOAD_FILE';
        $values=['php'=>is_string($r['php']??null)?$r['php']:null,'sapi'=>is_string($r['sapi']??null)?$r['sapi']:null,'webEuidIsOperator'=>$euid===null?null:$euid===$operatorEuid,'autoloaded'=>($r['autoloaded']??null)===true,'webAppUrlExact'=>($r['appUrlExact']??null)===true,'webAppUrlOrigin'=>is_string($r['appUrlOrigin']??null)&&preg_match('~^https?://[a-z0-9.:-]{1,130}$~D',$r['appUrlOrigin'])===1?$r['appUrlOrigin']:null,'webAppUrlSource'=>in_array($r['appUrlSource']??null,['SERVER_ENVIRONMENT','DOTENV_FILE','NONE'],true)?$r['appUrlSource']:null,'opcacheEnabled'=>($r['opcacheEnabled']??null)===true,'classes'=>count($classes),'classesLoadedInTheWebSapi'=>count($classes)-count($notLoaded),'classesFromThePayloadFile'=>count($classes)-count($notLoaded)-count($wrong)];
        return ['gates'=>$g,'failures'=>array_values(array_filter($g,static fn($c)=>$c!==null)),'values'=>$values];
    }
}
