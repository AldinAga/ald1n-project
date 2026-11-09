<?php
declare(strict_types=1);

/**
 * B1147 catalog-redesign overlay: ships the exact catalog file set (SOURCE_COMMIT over main eabe16af, 3 new and 5 modified
 * files, tests and CI excluded) into the installed CMS release apps/cms/current, which already carries the B1146 legal
 * overlay. The Storefront is OPEN in production: its state is recorded, re-read before the first change and before every
 * smoke batch, and never written. No route change, no migration, no .env, no config change, no OPcache reset.
 * Order: PREFLIGHT (read-only, collect-all; any blocker is a HOLD with nothing changed) -> per-file backup into the private
 * B1147 namespace -> install in dependency order (A: the new App\Support classes, B: service and controller, C: CSS,
 * D: views last, because layout.blade.php calls PublicCatalogPresentation on every public page), a revalidation pause
 * between groups, each file via same-directory temp + fsync + rename (link for a new file, so nothing is ever clobbered)
 * -> view:clear only -> targeted per-file opcache_invalidate through a token-gated temporary probe -> wait
 * max(realpath_cache_ttl, revalidate_freq) + 5 s -> smokes (catalog, legal pages, CSS, JSON-LD, CSP), fresh-code probe,
 * CLI class check, log scan, full tree verification. Any failure after the first file restores the backed-up bytes of all
 * 8 files in reverse install order (stopping at the first failed restore, so the tree is always an exact install prefix),
 * re-invalidates, clears the compiled views and smoke-tests the old state. A re-run converges from the saved phase.
 * @author Ald1n <https://ald1n.com>
 */
final class CatalogOverlayCoordinator
{
    public const MANIFEST_SHA='d737387e60a981b3b404e0980d9b70cb95b6e1d506a39211981d49dcd12be4c6';
    public const MANIFEST_FILES=8531;
    /** The tree after B1146 (manifest + 12 new files) and after this overlay (+ 3 new files). */
    public const B1146_TREE_FILES=8543;
    public const INSTALLED_TREE_FILES=8546;
    public const B1146_FILES=21;
    /** config/public_storefront.php after the B1145 unclamp: the staged original bytes. */
    public const CONFIG_SHA='0cfbc864a3d44822a9d14e80f6252e84eb208c9da5a25d5fe6989e4cad789818';
    public const B1146_REPORT_PATTERN='~^1109-B1146-LEGAL-OVERLAY-\d{8}-\d{6}\.md$~D';
    public const B1146_PASS='PASS_INSTALLED_LEGAL_OVERLAY_STOREFRONT_CLOSED';
    public const RAN=71;
    /** The reviewed catalog head; the payload is git show SOURCE_COMMIT:<path>. The pre-state is main eabe16af (= B1146). */
    public const SOURCE_COMMIT='764bf5135ad6241d64f728e4b141cc2713dc6c48';
    public const BASE_COMMIT='eabe16af8c19fade882e25c70425eb43c3e17ed9';
    /**
     * Install order. A: new classes without a caller in the old tree (SerbianPlural first, PublicCatalogPresentation uses
     * it). B: the service (new sorts) and the controller (calls PublicCatalogPresentation::build, 404 past the last page).
     * C: the CSS. D: the views last; pagination is new, the catalog view needs $seo from the controller and the new
     * pagination view, and the layout (used by every public page, legal pages included) needs PublicCatalogPresentation.
     */
    public const STEPS=[
        ['app/Support/SerbianPlural.php','new','A'],['app/Support/PublicCatalogPresentation.php','new','A'],
        ['app/Services/PublicRetailCatalogService.php','modified','B'],['app/Http/Controllers/PublicShopController.php','modified','B'],
        ['public/assets/css/ald1n-public-storefront.css','modified','C'],
        ['resources/views/public-shop/pagination.blade.php','new','D'],['resources/views/public-shop/catalog.blade.php','modified','D'],['resources/views/public-shop/layout.blade.php','modified','D'],
    ];
    public const LEGAL=['/pravne-informacije','/uslovi-prodaje','/politika-privatnosti','/podaci-o-trgovcu','/pravo-na-odustanak','/reklamacije','/dostava-i-placanje','/kolacici'];
    public const SHOP=['/katalog','/korpa','/porucivanje','/registracija'];
    public const SYSTEM=['/login','/up'];
    public const CSS_PATH='/assets/css/ald1n-public-storefront.css';
    public const B1146_ROUTE_NAMES=11;
    private const IN_FLIGHT=['BACKED_UP','INSTALL_INTENT','FILES_INSTALLED','ACCEPTANCE_INTENT','ROLLBACK_INTENT','ROLLBACK_INCOMPLETE'];
    private array $state=[];
    private string $base;
    private string $pointer;
    private mixed $lock=null;
    private object $web;
    private string $php=PHP_BINARY;
    private array $allowedSapis=['litespeed'];
    private string $stage;
    private string $b1146Base;
    private string $appStateScript;
    private string $routesScript;
    private string $catalogScript;
    private int $censusSeconds=75;
    private int $minFreeBytes=536870912;
    /** Monotonic time of the last tree change made by THIS process (never taken from a saved state of another process). */
    private ?int $lastChangeNs=null;
    private ?int $runStartNs=null;
    /** Wall-clock budget from the start of this run to the first change; beyond it the run HOLDs before any change. */
    private int $budgetSeconds=900;
    /**
     * The shop routes run behind throttle:public-shop-browse (120 per minute per ip(); without TrustProxies that key can be
     * a shared Cloudflare edge address). Every smoke GET is paced (about 85 per minute at most from this tool), a 429 honours
     * Retry-After (capped), and a smoke batch still limited after one whole-batch retry is INCONCLUSIVE, never a failure.
     */
    private int $paceMicros=700000;
    private int $rateLimitPauseSeconds=65;
    /** Total 429 waiting per smoke batch; the throttled routes share one limiter key, so after one GET stays 429 the rest are skipped. */
    private int $rateLimitCapSeconds=120;
    /** The 429 state of the current smoke batch: whether the shared limiter is known exhausted and the seconds waited. */
    private array $limitBatch=['limited'=>false,'waited'=>0];
    private const RETRY_AFTER_CAP=65;
    /** Upper bound of the GETs in one smoke batch (2 system, 8 legal, the CSS, at most 7 shop requests, 1 spare). */
    private const SMOKE_GETS=19;
    private const UNVERIFIED_CODES=['SMOKES_RATE_LIMITED_429','SHOP_STATE_CHANGED_DURING_ACCEPTANCE_CATALOG_NOT_VERIFIED','NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES'];
    private bool $scheduleGate=true;
    private ?int $fixedNow=null;
    private int $scheduleNeedSeconds=1800;
    private int $defaultBackupSeconds=2400;
    private ?Closure $fault=null;
    private ?array $manifest=null;
    private array $plan;
    private array $b1146;
    private array $markers;
    private string $cssVersion;
    public string $final='HOLD';

    public function __construct(private string $root,private string $home,array $package,private string $incoming,private string $mode='install',private bool $ignoreSchedule=false)
    {
        if($ignoreSchedule&&$mode!=='rollback')throw new RuntimeException('OPERATOR_SCHEDULE_OVERRIDE');
        if($root!=='/home/icaffeco/ald1n-project'||$home!=='/home/icaffeco'||realpath($root)!==$root||realpath($home)!==$home||!function_exists('posix_geteuid')||!extension_loaded('curl')||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('HOST_PROFILE');
        $this->base=$home.'/.ald1n-b1147-state';$this->pointer=$this->base.'/current.json';$this->stage=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU';$this->b1146Base=$home.'/.ald1n-b1146-state';
        $this->appStateScript=$incoming.'/b1145-app-state.php';$this->routesScript=$incoming.'/b1146-app-check.php';$this->catalogScript=$incoming.'/b1147-app-check.php';$this->web=new CatalogWeb();
        $this->load($package,$incoming);putenv('GIT_OPTIONAL_LOCKS=0');
        if(!in_array($mode,['install','rollback'],true))throw new RuntimeException('OPERATOR_MODE');
    }

    private function load(array $package,string $incoming):void
    {
        $this->plan=self::plan($package,$incoming);$this->b1146=self::b1146Plan($package);$this->markers=self::markers($package);
        $v=$package['cssVersion']??null;if(!is_string($v)||!preg_match('/^[0-9]{1,12}$/D',$v))throw new RuntimeException('CSS_VERSION_SCOPE');$this->cssVersion=$v;
    }

    /** The overlay plan from b1147-package.json must be exactly STEPS (paths, kinds, groups, order) with digests and a payload. */
    public static function plan(array $package,string $incoming):array
    {
        $overlay=$package['overlay']??null;if(!is_array($overlay)||count($overlay)!==count(self::STEPS)||($package['sourceCommit']??null)!==self::SOURCE_COMMIT||($package['baseCommit']??null)!==self::BASE_COMMIT)throw new RuntimeException('OVERLAY_PLAN_SCOPE');$plan=[];
        foreach(array_values($overlay) as $i=>$e){
            [$path,$kind,$group]=self::STEPS[$i];
            if(!is_array($e)||($e['path']??null)!==$path||($e['kind']??null)!==$kind||($e['group']??null)!==$group||!preg_match('/^b1147-payload-\d{2}\.bin$/D',(string)($e['payload']??''))||!preg_match('/^[a-f0-9]{64}$/D',(string)($e['payloadSHA256']??''))||!is_int($e['size']??null))throw new RuntimeException('OVERLAY_PLAN_ENTRY');
            $pre=$e['preSHA256']??null;if(($kind==='new')!==($pre===null)||($pre!==null&&!preg_match('/^[a-f0-9]{64}$/D',$pre))||$pre===$e['payloadSHA256'])throw new RuntimeException('OVERLAY_PLAN_PRE_STATE');
            InstallFileOps::relative($path);$plan[]=['n'=>$i+1,'path'=>$path,'kind'=>$kind,'group'=>$group,'payload'=>$incoming.'/'.$e['payload'],'payloadSHA256'=>$e['payloadSHA256'],'preSHA256'=>$pre,'size'=>$e['size']];
        }
        return $plan;
    }

    /** The 21 B1146 entries (eabe16af over 77a3efea): with them the 8531-file manifest becomes the 8543-file live tree. */
    public static function b1146Plan(array $package):array
    {
        $list=$package['b1146Overlay']??null;if(!is_array($list)||count($list)!==self::B1146_FILES)throw new RuntimeException('B1146_PLAN_SCOPE');$out=[];$new=0;
        foreach($list as $e){
            $path=$e['path']??null;$kind=$e['kind']??null;$sha=$e['sha256']??null;$pre=$e['preSHA256']??null;
            if(!is_string($path)||isset($out[$path])||!in_array($kind,['new','modified'],true)||!is_string($sha)||!preg_match('/^[a-f0-9]{64}$/D',$sha)||(($kind==='new')!==($pre===null))||($pre!==null&&(!is_string($pre)||!preg_match('/^[a-f0-9]{64}$/D',$pre))))throw new RuntimeException('B1146_PLAN_ENTRY');
            InstallFileOps::relative($path);$out[$path]=['path'=>$path,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];if($kind==='new')$new++;
        }
        if($new!==self::B1146_TREE_FILES-self::MANIFEST_FILES)throw new RuntimeException('B1146_PLAN_NEW_COUNT');return $out;
    }

    /** Build-time markers: present in the new bytes, absent from the old (package.php asserts both). */
    public static function markers(array $package):array
    {
        $m=$package['markers']??null;if(!is_array($m))throw new RuntimeException('MARKER_SCOPE');
        foreach(['layout','catalog','css'] as $k)if(!is_string($m[$k]??null)||!preg_match('/^[A-Za-z0-9 ="._-]{6,64}$/D',$m[$k]))throw new RuntimeException('MARKER_SCOPE');
        return ['layout'=>$m['layout'],'catalog'=>$m['catalog'],'css'=>$m['css']];
    }

    /** The exact Cloudflare purge: the cache key includes the query string, so the bare URL and the ?v= URL are separate entries. */
    public function purgeStep():string
    {
        return '1_CLOUDFLARE_PURGE_EXACTLY_THESE_TWO_URLS_https://cms.ald1n.com'.self::CSS_PATH.'_AND_https://cms.ald1n.com'.self::CSS_PATH.'?v='.$this->cssVersion.'_(THE_CACHE_KEY_INCLUDES_THE_QUERY_STRING_SO_EACH_URL_IS_ITS_OWN_ENTRY_THE_v='.$this->cssVersion.'_SUFFIX_IS_STATIC_A_LATER_CSS_CHANGE_NEEDS_A_NEW_SUFFIX_OR_THIS_PURGE)_OR_WAIT_FOR_THE_CACHE_TTL';
    }

    private function open():void
    {
        if($this->lock!==null)return;if(!is_dir($this->base)&&!mkdir($this->base,0700))throw new RuntimeException('PRIVATE_STATE_CREATE');
        if(is_link($this->base)||realpath($this->base)!==$this->base||(fileperms($this->base)&0777)!==0700||fileowner($this->base)!==posix_geteuid())throw new RuntimeException('PRIVATE_STATE_CONFINEMENT');
        $lock=fopen($this->base.'/operator.lock','c');if($lock===false||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('ANOTHER_B1147_RUN_ACTIVE');chmod($this->base.'/operator.lock',0600);$this->lock=$lock;
    }

    /** No B1146 run holds its operator lock (read-only: the lock file is opened for reading and only probed). */
    private function b1146Idle():void
    {
        $path=$this->b1146Base.'/operator.lock';if(!file_exists($path))return;if(is_link($path)||!is_file($path))throw new RuntimeException('B1146_LOCK_SCOPE');
        $h=@fopen($path,'r');if($h===false)throw new RuntimeException('B1146_LOCK_UNREADABLE');$free=flock($h,LOCK_SH|LOCK_NB);if($free)flock($h,LOCK_UN);fclose($h);if(!$free)throw new RuntimeException('B1146_RUN_ACTIVE');
    }

    private function emit(string $key,mixed $value):void {echo $key.'='.json_encode($value,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;}
    private static function code(Throwable $error):string {$code=$error->getMessage();return preg_match('/^[A-Z0-9_]+$/D',$code)?$code:'UNEXPECTED_'.strtoupper((new ReflectionClass($error))->getShortName());}
    private function active():string {return $this->root.'/apps/cms/current';}
    private function target(array $step):string {return $this->active().'/'.$step['path'];}
    private function fault(string $point):void {if($this->fault!==null)($this->fault)($point);}
    private function now():int {return $this->fixedNow??time();}
    private function retained():string {$r=$this->state['chain']['retained']??null;if(!is_string($r))throw new RuntimeException('RETAINED_TREE_UNKNOWN');return $r;}
    private function step(string $path):array {foreach($this->plan as $s)if($s['path']===$path)return $s;throw new RuntimeException('PLAN_PATH_UNKNOWN');}

    private function atomicJSON(string $path,array $data):void
    {
        if(realpath(dirname($path))!==dirname($path)||is_link($path))throw new RuntimeException('STATE_CONFINEMENT');$temporary=$path.'.'.bin2hex(random_bytes(8)).'.next';$handle=@fopen($temporary,'x');if($handle===false)throw new RuntimeException('STATE_CREATE');
        chmod($temporary,0600);$text=json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);$ok=fwrite($handle,$text)===strlen($text)&&fflush($handle)&&(!function_exists('fsync')||fsync($handle));fclose($handle);
        if(!$ok){@unlink($temporary);throw new RuntimeException('STATE_WRITE');}if(!rename($temporary,$path)){@unlink($temporary);throw new RuntimeException('STATE_RENAME');}
    }

    /** state.json first, then the journal line, then the pointer; the in-memory phase changes only after the state file holds it. */
    private function save(string $phase):void
    {
        $next=$this->state;$next['phase']=$phase;$this->atomicJSON($this->state['private'].'/state.json',$next);$this->state['phase']=$phase;
        $journal=$this->state['private'].'/journal.jsonl';$h=fopen($journal,'a');if($h===false||!flock($h,LOCK_EX))throw new RuntimeException('JOURNAL_WRITE');
        $line=json_encode(['phase'=>$phase,'at'=>time()],JSON_THROW_ON_ERROR)."\n";$ok=fwrite($h,$line)===strlen($line)&&fflush($h);flock($h,LOCK_UN);fclose($h);if(!$ok)throw new RuntimeException('JOURNAL_WRITE');
        $this->atomicJSON($this->pointer,['schemaVersion'=>1,'kind'=>'B1147','private'=>$this->state['private'],'id'=>$this->state['id']]);$this->emit('B1147_PHASE',$phase);$this->fault('SAVED_'.$phase);
    }

    private function persist():void {$this->atomicJSON($this->state['private'].'/state.json',$this->state);}

    /** No raw application output leaves the process: callers report audited identity, RC and parsed facts only. */
    private function command(array $argv,?string $cwd=null,int $timeout=120,?array $env=null):array
    {
        $pipes=[];$process=proc_open($argv,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd,$env);if(!is_resource($process))throw new RuntimeException('CHILD_START');fclose($pipes[0]);stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$stdout='';$stderr='';$started=microtime(true);
        do{$stdout.=(string)stream_get_contents($pipes[1]);$stderr.=(string)stream_get_contents($pipes[2]);$last=proc_get_status($process);
            if($last['running']&&(microtime(true)-$started>$timeout||strlen($stdout)>4194304||strlen($stderr)>4194304)){foreach(MigrateUnclampCoordinator::descendants((int)$last['pid']) as $p)@posix_kill($p,9);@posix_kill((int)$last['pid'],9);$deadline=microtime(true)+20;while(proc_get_status($process)['running']&&microtime(true)<$deadline)usleep(100000);fclose($pipes[1]);fclose($pipes[2]);proc_close($process);throw new RuntimeException('CHILD_TIMEOUT_OR_OUTPUT_BOUND');}
            if($last['running'])usleep(100000);}while($last['running']);
        $stdout.=(string)stream_get_contents($pipes[1]);$stderr.=(string)stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$closed=proc_close($process);$rc=$last['exitcode']>=0?$last['exitcode']:$closed;return ['rc'=>$rc,'stdout'=>$stdout,'stderrBytes'=>strlen($stderr)];
    }

    private function artisan(array $arguments,int $timeout=300):array
    {
        // The operator entry runs with umask 077; artisan children get 022 so anything they write matches the tree (0644).
        $previous=umask(0022);try{return $this->command(array_merge([$this->php,'-d','opcache.enable=0','-d','opcache.enable_cli=0',$this->active().'/artisan'],$arguments,['--no-interaction','--no-ansi']),$this->active(),$timeout,array_merge(getenv(),['COLUMNS'=>'240']));}finally{umask($previous);}
    }

    private function artisanOk(array $arguments,int $timeout=300):void
    {
        $r=$this->artisan($arguments,$timeout);$this->emit('ARTISAN',['command'=>implode(' ',$arguments),'rc'=>$r['rc'],'stderrBytes'=>$r['stderrBytes']]);if($r['rc']!==0)throw new RuntimeException('ARTISAN_FAILED_'.strtoupper(str_replace([':','-'],'_',$arguments[0])));
    }

    /** One read-only helper (b1145-app-state.php state, b1146-app-check.php routes, b1147-app-check.php catalog); JSON facts only. */
    private function helper(string $script,array $arguments,int $timeout=120):array
    {
        $r=$this->command(array_merge([$this->php,'-d','opcache.enable=0','-d','opcache.enable_cli=0',$script,$this->active()],$arguments),$this->active(),$timeout,array_merge(getenv(),['COLUMNS'=>'240']));
        $data=json_decode(trim($r['stdout']),true,32);if(!is_array($data))throw new RuntimeException('APP_HELPER_UNPARSED');
        if($r['rc']!==0||($data['ok']??false)!==true){$code=is_string($data['error']??null)&&preg_match('/^[A-Z0-9_]+$/D',$data['error'])?$data['error']:'APP_HELPER_FAILED';throw new RuntimeException($code);}unset($data['ok']);return $data;
    }

    private function manifest():array
    {
        if($this->manifest!==null)return $this->manifest;$path=$this->stage.'/verification/runtime-manifest.json';
        if(is_link($path)||realpath($path)!==$path||!is_file($path)||hash_file('sha256',$path)!==self::MANIFEST_SHA)throw new RuntimeException('RUNTIME_MANIFEST_INTEGRITY');
        $m=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);if(!is_array($m['files']??null)||count($m['files'])!==self::MANIFEST_FILES)throw new RuntimeException('RUNTIME_MANIFEST_COUNT');return $this->manifest=$m;
    }

    /** $files with $changes applied: a modified entry must hold its pre digest and gets the new one; a new path must be absent. */
    private static function applied(array $files,array $changes,string $code):array
    {
        $by=[];foreach($changes as $c)$by[$c['path']]=$c;$out=[];
        foreach($files as $f){$c=$by[$f['path']]??null;if($c!==null&&$c['kind']==='new')throw new RuntimeException($code.'_NEW_FILE_ALREADY_LISTED');if($c!==null){if($f['sha256']!==$c['preSHA256'])throw new RuntimeException($code.'_PRE_STATE_MISMATCH');$f['sha256']=$c['sha256'];}$out[]=$f;}
        foreach($changes as $c)if($c['kind']==='new')$out[]=['path'=>$c['path'],'sha256'=>$c['sha256']];return $out;
    }

    /** The live tree before this overlay: the manifest with the 21 B1146 files (8543 entries). */
    private function b1146Manifest():array
    {
        $files=self::applied($this->manifest()['files'],array_values($this->b1146),'B1146_MANIFEST');if(count($files)!==self::B1146_TREE_FILES)throw new RuntimeException('B1146_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** The tree after this overlay (8546 entries). */
    private function installedManifest():array
    {
        $changes=array_map(static fn($s)=>['path'=>$s['path'],'kind'=>$s['kind'],'sha256'=>$s['payloadSHA256'],'preSHA256'=>$s['preSHA256']],$this->plan);
        $files=self::applied($this->b1146Manifest()['files'],$changes,'B1147_MANIFEST');if(count($files)!==self::INSTALLED_TREE_FILES)throw new RuntimeException('B1147_MANIFEST_COUNT');return ['files'=>$files];
    }

    /** Last value of every KEY=value line of the B1146 terminal report (Report1109). */
    public static function b1146Facts(string $bytes):array
    {
        if(str_contains($bytes,"\r")||strlen($bytes)>4194304)throw new RuntimeException('B1146_REPORT_FORMAT');$last=[];
        foreach(explode("\n",$bytes) as $line)if(preg_match('/^([A-Z0-9_]+)=(.*)$/D',$line,$m))$last[$m[1]]=$m[2];
        if(($last['B1146_FINAL']??null)!==self::B1146_PASS||($last['EXIT']??null)!=='0'||json_decode($last['B1146_PHASE']??'null',true)!=='INSTALLED_PASS')throw new RuntimeException('B1146_REPORT_NOT_TERMINAL_PASS');
        return ['final'=>$last['B1146_FINAL'],'phase'=>'INSTALLED_PASS','exit'=>0];
    }

    /** Chain: exactly one Report1109 of B1146 with its terminal PASS, the live B1146 state INSTALLED_PASS and no B1146 run. */
    private function chain():array
    {
        $directory=$this->root.'/docs/operations';$found=[];foreach(scandir($directory)?:[] as $name)if(preg_match(self::B1146_REPORT_PATTERN,$name))$found[]=$name;
        if(count($found)!==1)throw new RuntimeException(count($found)===0?'B1146_REPORT1109_MISSING':'B1146_REPORT1109_AMBIGUOUS');$path=$directory.'/'.$found[0];
        if(is_link($path)||realpath($path)!==$path||!is_file($path))throw new RuntimeException('B1146_REPORT1109_SCOPE');$bytes=(string)file_get_contents($path);$facts=self::b1146Facts($bytes);
        $pointer=$this->b1146Base.'/current.json';if(is_link($pointer)||!is_file($pointer)||filesize($pointer)>8192)throw new RuntimeException('B1146_POINTER_MISSING');$p=json_decode((string)file_get_contents($pointer),true,8,JSON_THROW_ON_ERROR);
        if(!is_array($p)||($p['kind']??null)!=='B1146'||!preg_match('/^[a-f0-9]{32}$/D',(string)($p['id']??''))||($p['private']??null)!==$this->b1146Base.'/'.$p['id'])throw new RuntimeException('B1146_POINTER_SCOPE');
        $statePath=$p['private'].'/state.json';if(is_link($statePath)||realpath($statePath)!==$statePath||filesize($statePath)>16777216)throw new RuntimeException('B1146_STATE_MISSING');$stateBytes=(string)file_get_contents($statePath);$s=json_decode($stateBytes,true,512,JSON_THROW_ON_ERROR);
        if(!is_array($s)||($s['kind']??null)!=='B1146'||($s['id']??null)!==$p['id']||($s['phase']??null)!=='INSTALLED_PASS')throw new RuntimeException('B1146_STATE_NOT_INSTALLED_PASS');
        $retained=$s['chain']['retained']??null;$htaccess=$s['chain']['htaccessSHA256']??null;
        if(!is_string($retained)||!preg_match('~^/[A-Za-z0-9._/-]+/retained$~D',$retained)||!is_string($htaccess)||!preg_match('/^[a-f0-9]{64}$/D',$htaccess))throw new RuntimeException('B1146_STATE_FACTS');
        $this->b1146Idle();
        return ['report'=>'docs/operations/'.$found[0],'reportSHA256'=>hash('sha256',$bytes),'b1146Id'=>$p['id'],'b1146StateSHA256'=>hash('sha256',$stateBytes),'retained'=>$retained,'htaccessSHA256'=>$htaccess]+$facts;
    }

    /** Payload bytes from incoming/, re-verified against the plan on every read. */
    private function payload(array $step):string
    {
        $p=$step['payload'];if(is_link($p)||realpath($p)!==$p||!is_file($p))throw new RuntimeException('PAYLOAD_MISSING');$bytes=(string)file_get_contents($p);
        if(!hash_equals($step['payloadSHA256'],hash('sha256',$bytes))||strlen($bytes)!==$step['size'])throw new RuntimeException('PAYLOAD_INTEGRITY');return $bytes;
    }

    /** pre | payload | absent | foreign | link for one target; never writes. */
    private function classify(array $step):array
    {
        $t=$this->target($step);clearstatcache(true,$t);if(is_link($t))return ['state'=>'link','sha256'=>null];if(!file_exists($t))return ['state'=>'absent','sha256'=>null];if(!is_file($t))return ['state'=>'foreign','sha256'=>null];
        $sha=hash_file('sha256',$t);$state=$sha===$step['payloadSHA256']?'payload':($step['preSHA256']!==null&&$sha===$step['preSHA256']?'pre':'foreign');return ['state'=>$state,'sha256'=>$sha];
    }

    private function targets():array
    {
        $out=['pre'=>0,'payload'=>0,'absent'=>0,'foreign'=>[],'link'=>[]];
        foreach($this->plan as $s){$c=$this->classify($s);if(in_array($c['state'],['foreign','link'],true))$out[$c['state']][]=['n'=>$s['n'],'path'=>$s['path'],'sha256'=>$c['sha256'],'expectedPre'=>$s['preSHA256'],'payload'=>$s['payloadSHA256']];else $out[$c['state']]++;}
        $new=count(array_filter($this->plan,static fn($s)=>$s['kind']==='new'));$out['preState']=$out['foreign']===[]&&$out['link']===[]&&$out['pre']===count($this->plan)-$new&&$out['absent']===$new;
        $out['installed']=$out['foreign']===[]&&$out['link']===[]&&$out['payload']===count($this->plan);return $out;
    }

    private function routeCacheFiles():array
    {
        $files=[];foreach(glob($this->active().'/bootstrap/cache/routes-*.php')?:[] as $path){if(is_link($path)||!is_file($path))throw new RuntimeException('ROUTE_CACHE_FILE_SCOPE');$files[basename($path)]=hash_file('sha256',$path);}ksort($files,SORT_STRING);return $files;
    }

    private function viewsDirectory():string {$d=realpath($this->active().'/storage/framework/views');if(!is_string($d)||!is_dir($d))throw new RuntimeException('COMPILED_VIEWS_DIRECTORY');return $d;}
    private function compiledViews():int {return count(glob($this->viewsDirectory().'/*.php')?:[]);}

    /** No artisan process of this CMS: three consecutive clean samples, bounded (cron schedule:run fires every minute). */
    private function census():array
    {
        $roots=[$this->active(),$this->retained()];$deadline=hrtime(true)+$this->censusSeconds*1000000000;$clean=0;$samples=0;$seen=0;
        do{$b=LeanGates::busy($this->active().'/public/index.php',LeanGates::processes(),getmypid(),$roots);$samples++;if($b['artisan']===0){if(++$clean>=3)return ['clean'=>true,'samples'=>$samples,'busySamples'=>$seen,'otherArtisanIgnored'=>$b['otherArtisanIgnored']];}else{$clean=0;$seen++;}usleep(1000000);}while(hrtime(true)<$deadline);
        throw new RuntimeException('CMS_ARTISAN_RUNNING');
    }

    private function headroom():array
    {
        $need=$this->minFreeBytes;foreach($this->plan as $s)$need+=3*$s['size'];$result=[];
        foreach([$this->active(),$this->base] as $i=>$path){
            $r=$this->command(['stat','-f','-c','%a %S %d %c',$path],null,20);if($r['rc']!==0||!preg_match('/^(\d+) (\d+) (\d+) (\d+)$/D',trim($r['stdout']),$m))throw new RuntimeException('DISK_STAT_UNAVAILABLE');
            $free=(int)$m[1]*(int)$m[2];$result[]=['filesystem'=>$i===0?'CMS_TREE':'PRIVATE_STATE','freeBytes'=>$free,'requiredBytes'=>$need,'freeInodes'=>(int)$m[3]];if($free<$need)throw new RuntimeException('DISK_HEADROOM_INSUFFICIENT');if((int)$m[3]<1000)throw new RuntimeException('INODE_HEADROOM_INSUFFICIENT');
        }
        $result[]=['filesystem'=>'CPANEL_ACCOUNT_QUOTA']+$this->quota($need,1000);
        return $result;
    }

    /** The per-user (cPanel/CageFS) quota as `quota -w -p` shows it, parsed by the pinned B1145 parser; unreadable = filesystem only. */
    private function quota(int $bytes,int $inodes):array
    {
        $bin=null;foreach(explode(':',(string)getenv('PATH')) as $d)if($d!==''&&str_starts_with($d,'/')&&is_file($d.'/quota')&&is_executable($d.'/quota')){$bin=$d.'/quota';break;}
        if($bin===null)return ['read'=>false,'note'=>'ACCOUNT_QUOTA_NOT_READ_NO_QUOTA_TOOL_FILESYSTEM_ONLY'];
        try{$r=$this->command([$bin,'-w','-p'],null,20,array_merge(getenv(),['LC_ALL'=>'C']));}catch(Throwable $e){return ['read'=>false,'note'=>'ACCOUNT_QUOTA_NOT_READ_'.self::code($e).'_FILESYSTEM_ONLY'];}
        $rows=MigrateUnclampCoordinator::parseQuota($r['stdout']);if($rows===null)return ['read'=>false,'note'=>'ACCOUNT_QUOTA_NOT_READ_UNPARSED_RC'.$r['rc'].'_FILESYSTEM_ONLY'];$out=['read'=>true,'limited'=>false,'requiredBytes'=>$bytes,'requiredInodes'=>$inodes,'rows'=>[]];
        foreach($rows as $row){
            $freeBytes=$row['limitBytes']===null?null:$row['limitBytes']-$row['usedBytes'];$freeInodes=$row['limitInodes']===null?null:$row['limitInodes']-$row['usedInodes'];$out['limited']=$out['limited']||$freeBytes!==null||$freeInodes!==null;$out['rows'][]=['freeBytes'=>$freeBytes,'freeInodes'=>$freeInodes];
            if($freeBytes!==null&&$freeBytes<$bytes)throw new RuntimeException('ACCOUNT_QUOTA_HEADROOM_INSUFFICIENT');if($freeInodes!==null&&$freeInodes<$inodes)throw new RuntimeException('ACCOUNT_QUOTA_INODES_INSUFFICIENT');
        }
        return $out;
    }

    /** Scheduled app:backup-create (02:30 daily, 03:10 Sunday, app timezone) must not start within the next 30 minutes. */
    private function scheduleState(array $app):array
    {
        $last=$app['backups']['lastCompletedSeconds']??null;$base=$last===null?$this->defaultBackupSeconds:max(60,(int)$last);$duration=2*$base+600;$now=$this->now();$tz=(string)($app['appTimezone']??'');
        try{$local=(new DateTimeImmutable('@'.$now))->setTimezone(new DateTimeZone($tz))->format('Y-m-d H:i T');}catch(Throwable){throw new RuntimeException('APP_TIMEZONE_INVALID');}
        return ['conflict'=>MigrateUnclampCoordinator::scheduleConflict($app['backupSchedule']??[],$now,$this->scheduleNeedSeconds,$duration),'appTimezone'=>$tz,'appLocalTime'=>$local,'needSeconds'=>$this->scheduleNeedSeconds,'scheduledBackupSeconds'=>$duration,'events'=>$app['backupSchedule']??[]];
    }

    /** The shop as the app sees it: open = config public_storefront.enabled === true AND DB switch '1'. Read-only. */
    private static function shopOf(array $app):array
    {
        $flag=(string)$app['storefrontFlag'];$enabled=($app['storefrontConfig']['enabled']??null)===true;
        return ['dbSwitch'=>$flag,'configEnabled'=>$enabled,'known'=>in_array($flag,['absent','0','1'],true),'open'=>$enabled&&$flag==='1'];
    }

    private function shop():array {$s=self::shopOf($this->helper($this->appStateScript,['state']));if(!$s['known'])throw new RuntimeException('SHOP_SWITCH_VALUE_UNKNOWN');return $s;}

    private function migrationStatus():array
    {
        $r=$this->artisan(['migrate:status'],120);if($r['rc']!==0)throw new RuntimeException('MIGRATE_STATUS_FAILED_OR_DB_UNREACHABLE');
        $s=LeanGates::migrationStatus($r['stdout'],LeanGates::migrationFiles($this->active().'/database/migrations'));if($s['ran']!==self::RAN||$s['pending']!==[])throw new RuntimeException('MIGRATION_SET_NOT_71_RAN_0_PENDING');return $s;
    }

    private function ownTemps():array
    {
        $found=[];$dirs=[];foreach($this->plan as $s)$dirs[dirname($this->target($s))]=true;
        foreach(array_keys($dirs) as $d)if(is_dir($d))foreach(scandir($d)?:[] as $name)if(CatalogFiles::isOwnTemp($d.'/'.$name))$found[]=$d.'/'.$name;return $found;
    }

    private function cleanupRecorded():void
    {
        $changed=false;
        foreach($this->state['probes']??[] as $i=>$probe){if(($probe['removed']??false)===true)continue;FreshnessProbe::removeStaging($probe['staging']);FreshnessProbe::remove($this->active().'/public',$probe['name'],$probe['sha256']);$this->state['probes'][$i]['removed']=true;$changed=true;}
        foreach($this->state['temps']??[] as $i=>$temp){if(CatalogFiles::isOwnTemp($temp)&&is_file($temp)&&!is_link($temp)&&!unlink($temp))throw new RuntimeException('TEMP_FILE_REMOVE');unset($this->state['temps'][$i]);$changed=true;}
        if($changed){$this->state['temps']=array_values($this->state['temps']??[]);$this->persist();}
    }

    private function recordTemp(string $temp):string {$this->state['temps'][]=$temp;$this->persist();return $temp;}

    /**
     * absolute path => expected sha for the probe: all 8 targets ('installed' or 'pre' bytes, null = absent) and the route
     * cache files. Only the PHP sources (and the compiled views) can be held by OPcache; the probe invalidates exactly those.
     */
    private function probeTargets(bool $installed):array
    {
        $t=[];foreach($this->plan as $s){$dir=realpath(dirname($this->target($s)));$path=($dir===false?dirname($this->target($s)):$dir).'/'.basename($s['path']);$t[$path]=$installed?$s['payloadSHA256']:$s['preSHA256'];}
        foreach($this->routeCacheFiles() as $name=>$sha)$t[$this->active().'/bootstrap/cache/'.$name]=$sha;return $t;
    }

    /** One probe file: one request (targeted invalidation when asked), assessed collect-all. Always removed. */
    private function probeRun(string $label,bool $installed,bool $invalidate,bool $requireFresh,bool $collect=false):array
    {
        $this->cleanupRecorded();FreshnessProbe::assertNoLeftover($this->active().'/public');$targets=$this->probeTargets($installed);$plan=OverlayProbe::plan($this->state['id'],$targets,$this->viewsDirectory());
        $staging=$this->state['private'].'/probe-'.bin2hex(random_bytes(8)).'.staging';$this->state['probes'][]=['name'=>$plan['name'],'sha256'=>$plan['sha256'],'label'=>$label,'staging'=>$staging];$this->persist();
        try{FreshnessProbe::write($this->active().'/public/'.$plan['name'],$plan['source'],$staging);$response=$this->web->probe($plan['name'],$plan['token'],$invalidate,null);}finally{$this->cleanupRecorded();}
        $route=is_string($response['_route']??null)?$response['_route']:'UNKNOWN';unset($response['_route']);$a=OverlayProbe::assess($response,$this->state['id'],$targets,$this->allowedSapis,$invalidate,$requireFresh,posix_geteuid());
        $result=['label'=>$label,'route'=>$route,'invalidationRequested'=>$invalidate,'requireFresh'=>$requireFresh,'gates'=>array_map(static fn($c)=>$c??'PASS',$a['gates'])]+$a['values']+['probeRemoved'=>true,'failures'=>$a['failures']];$this->emit('WEB_OVERLAY_PROBE',$result);
        if(!$collect&&$a['failures']!==[])throw new RuntimeException($a['failures'][0]);return $result;
    }

    /**
     * Waits until $seconds have passed since the last tree change; heartbeat every 15 s. A change made by this process is
     * timed on the monotonic clock; otherwise (a fresh process resuming a rollback or an acceptance) the wall-clock time of
     * the last change saved in the state is used, clamped to [0, $seconds] so a clock jump can never shorten the wait below
     * zero or stretch it beyond $seconds; without any saved change the full $seconds are waited.
     */
    private function hold(string $label,int $seconds):void
    {
        if($seconds<0||$seconds>FreshnessProbe::MAX_HOLD_SECONDS)throw new RuntimeException('OVERLAY_HOLD_EXCEEDS_BOUND');
        if($this->lastChangeNs!==null){$deadline=$this->lastChangeNs+$seconds*1000000000;$clock='MONOTONIC_THIS_PROCESS';}
        else{$at=$this->state['lastTreeChangeAt']??null;$rest=is_int($at)?max(0,min($seconds,$at+$seconds-time())):$seconds;$deadline=hrtime(true)+$rest*1000000000;$clock=is_int($at)?'SAVED_WALL_CLOCK_OF_THE_LAST_CHANGE':'FULL_NO_SAVED_CHANGE';}
        $beat=hrtime(true)+15000000000;
        $this->emit('OVERLAY_HOLD_START',['label'=>$label,'seconds'=>$seconds,'remainingSeconds'=>max(0,(int)ceil(($deadline-hrtime(true))/1e9)),'clock'=>$clock]);
        $this->fault('DURING_HOLD_'.$label);
        while(($rest=$deadline-hrtime(true))>0){usleep((int)max(1000,intdiv(min($rest,max(0,$beat-hrtime(true)),1000000000),1000)));if(hrtime(true)>=$beat&&hrtime(true)<$deadline){$this->emit('OVERLAY_HOLD_HEARTBEAT',['label'=>$label,'remainingSeconds'=>(int)ceil(($deadline-hrtime(true))/1e9)]);$beat+=15000000000;}}
        $this->emit('OVERLAY_HOLD',['label'=>$label,'seconds'=>$seconds,'done'=>true]);
    }

    /** Every tree change: the monotonic time for this process and the wall-clock time in the saved state (for a fresh process). */
    private function changed():void {$this->lastChangeNs=hrtime(true);$this->state['lastTreeChangeAt']=time();$this->persist();}

    /**
     * One paced GET with up to 4 attempts on a transport error, 429 or an edge/gateway status. A 429 waits Retry-After
     * (6 s when absent, at most RETRY_AFTER_CAP); a response still 429 after the last attempt is marked rateLimited.
     */
    private function get(string $path,array $query,string $marker):array
    {
        $throttled=in_array($path,self::LEGAL,true)||in_array($path,self::SHOP,true);
        // The shared limiter is already exhausted in this batch: no request, no wait (INCONCLUSIVE, never PASS or FAIL).
        if($throttled&&$this->limitBatch['limited'])return ['status'=>429,'transport'=>false,'robots'=>[],'csp'=>[],'frame'=>[],'cfCache'=>null,'contentType'=>null,'retryAfter'=>null,'body'=>'','bytes'=>0,'attempts'=>0,'rateLimited'=>true,'waitedSeconds'=>0,'skippedSharedLimiter'=>true];
        $waited=0;$limited=false;
        for($attempt=1;$attempt<=4;$attempt++){
            if($this->paceMicros>0)usleep($this->paceMicros);
            $r=$this->web->fetch($path,$query,$marker);$limited=!$r['transport']&&$r['status']===429;
            if(!$r['transport']&&!$limited&&!in_array($r['status'],[502,503,504,520,521,522,523,524,525,526,527],true))break;
            if($attempt<4){
                $s=$limited?min(self::RETRY_AFTER_CAP,max(1,$r['retryAfter']??6)):2;
                // The per-batch 429 budget: a wait that would exceed it is not taken; the limiter counts as exhausted.
                if($limited&&$this->limitBatch['waited']+$s>$this->rateLimitCapSeconds)break;
                if($limited)$this->limitBatch['waited']+=$s;$waited+=$s;sleep($s);
            }
        }
        if($limited)$this->limitBatch['limited']=true;
        return $r+['attempts'=>min($attempt,4),'rateLimited'=>$limited,'waitedSeconds'=>$waited,'skippedSharedLimiter'=>false];
    }

    /**
     * The worst-case wall time of one smokes() call: two batches (each the 429 budget, the pacing of every GET and a 60 s
     * margin for slow answers) and the pause between them.
     */
    private function smokeCapSeconds():int
    {
        $batch=$this->rateLimitCapSeconds+(int)ceil(self::SMOKE_GETS*$this->paceMicros/1000000)+60;return 2*$batch+$this->rateLimitPauseSeconds;
    }

    /**
     * The scheduled-backup window this run needs: the hold before the views and after the install, a full smokes() call,
     * and, if the acceptance fails, the rollback's holds before the new classes and after the restore plus its smokes, with
     * 300 s for artisan, probes and pauses. Never below the default 30 minutes.
     */
    private function requiredWindowSeconds(int $holdSeconds):int
    {
        return max(1800,4*$holdSeconds+2*$this->smokeCapSeconds()+300);
    }

    /**
     * Public GET smokes; no product, cart or account is written. The expectations follow the shop state read right before
     * this batch (the installer never changes it) and $installed (new code) or not (old code):
     * always: /login and /up 200; the 8 legal pages 200 with CSP script-src 'none', DENY and the layout marker
     * exactly when $installed (legal pages render the public layout, so the new marker proves the new class loads).
     * Owner policy: the shop is never indexed. The server-wide public/.htaccess sets X-Robots-Tag "noindex, nofollow,
     * noarchive" on every response (Header always set replaces the app header, so the app rule "noindex only while the
     * shop is OFF" is not what production sends: Report1111, also /up in Report1109). Every public shop and legal page
     * (the 8 legal pages, /katalog with its sorted and paged views, /korpa, /registracija; with the shop OFF the shop
     * paths) must carry an X-Robots-Tag with noindex in both states. A check that passes in every other respect but lacks
     * it is FAIL_NOINDEX_POLICY_HEADER_MISSING and the batch ends with NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES: a
     * server or edge policy problem, not a code one (preflight HOLDs, an installed acceptance HOLDs with the exact files
     * kept, a rollback completes with an owner notice). Nothing else in the stack is assumed away: CSP and X-Frame-Options come from the app (the .htaccess sets neither),
     * Cache-Control, Content-Type and the encoding are not asserted (curl sends no Accept-Encoding, so the body is
     * identity-coded and the markers are read as sent), every request carries a unique cache-busting marker, and the
     * Cloudflare status is used only to call a stale edge copy of the CSS INCONCLUSIVE;
     * the CSS carries the new selector exactly when $installed (an edge-cached copy without it is INCONCLUSIVE, never PASS);
     * shop ON: /katalog 200 scriptless with both markers exactly when $installed and, installed, one parsable
     * CollectionPage JSON-LD block; ?sort=price_asc 200 (installed); an unknown sort 302/422 (validation, never 5xx);
     * ?page=999 404 (installed); /korpa 200 with the layout marker; /registracija 200; /porucivanje 302 (sign-in) or 404
     * (checkout not ready); shop OFF: the 4 shop paths 404. Old-state variants of the new-only checks only require < 500.
     */
    private function smokes(bool $installed,bool $retried=false):array
    {
        $state=$installed?'INSTALLED':'OLD';$this->fault('BEFORE_SMOKES_'.$state);$shop=$this->shop();$m=$this->markers;$marker=bin2hex(random_bytes(8));$rows=[];$failed=[];$inconclusive=[];$limited=[];$this->limitBatch=['limited'=>false,'waited'=>0];
        // A response still 429 after its retries proves nothing about the code: INCONCLUSIVE, never PASS and never FAIL.
        // $policy: a public shop or legal page that must carry X-Robots-Tag noindex (owner policy, see above).
        $noindexMissing=[];
        $add=function(string $label,array $r,string $expected,bool $pass,array $facts=[],bool $policy=false)use(&$rows,&$failed,&$limited,&$noindexMissing):void{
            if($policy)$expected.='_NOINDEX';
            if(($r['rateLimited']??false)===true){$rows[]=['check'=>$label,'expected'=>$expected,'observed'=>429,'result'=>'INCONCLUSIVE_RATE_LIMITED_429','attempts'=>$r['attempts'],'waitedSeconds'=>$r['waitedSeconds'],'skippedSharedLimiter'=>$r['skippedSharedLimiter']??false];$limited[]=$label;return;}
            $noindex=CatalogWeb::noindex($r['robots']);$facts=['noindex'=>$noindex]+$facts;$result=$pass?'PASS':'FAIL';
            if($pass&&$policy&&!$noindex){$result='FAIL_NOINDEX_POLICY_HEADER_MISSING';$noindexMissing[]=$label;}
            $rows[]=['check'=>$label,'expected'=>$expected,'observed'=>$r['transport']?'TRANSPORT':$r['status'],'result'=>$result,'attempts'=>$r['attempts']]+$facts;if(!$pass)$failed[]=$label;};
        $lt=static fn(array $r):bool=>!$r['transport']&&$r['status']>0&&$r['status']<500;
        foreach(self::SYSTEM as $p){$r=$this->get($p,[],$marker);$add($p,$r,'200',!$r['transport']&&$r['status']===200);}
        foreach(self::LEGAL as $p){
            $r=$this->get($p,[],$marker);$f=['cspScriptSrcNone'=>CatalogWeb::scriptless($r['csp']),'frameDeny'=>CatalogWeb::frameDeny($r['frame']),'layoutMarker'=>str_contains($r['body'],$m['layout'])];
            $add($p,$r,'200_CSP_DENY_LAYOUT_'.($installed?'NEW':'OLD'),!$r['transport']&&$r['status']===200&&$f['cspScriptSrcNone']&&$f['frameDeny']&&$f['layoutMarker']===$installed,$f,true);
        }
        $this->fault('DURING_SMOKES_'.$state);
        $r=$this->get(self::CSS_PATH,[],$marker);$has=str_contains($r['body'],$m['css']);$ok=!$r['transport']&&$r['status']===200;
        if($ok&&$has!==$installed&&CatalogWeb::edgeCached($r['cfCache'])){$inconclusive[]=self::CSS_PATH;$rows[]=['check'=>self::CSS_PATH,'expected'=>'200_CSS_'.($installed?'NEW':'OLD'),'observed'=>$r['status'],'result'=>'INCONCLUSIVE_EDGE_CACHED_COPY','cfCache'=>$r['cfCache'],'cssMarker'=>$has,'attempts'=>$r['attempts']];}
        else $add(self::CSS_PATH,$r,'200_CSS_'.($installed?'NEW':'OLD'),$ok&&$has===$installed,['cfCache'=>$r['cfCache'],'cssMarker'=>$has]);
        if($shop['open']){
            $r=$this->get('/katalog',[],$marker);$f=['cspScriptSrcNone'=>CatalogWeb::scriptless($r['csp']),'layoutMarker'=>str_contains($r['body'],$m['layout']),'catalogMarker'=>str_contains($r['body'],$m['catalog']),'jsonLd'=>$installed?(CatalogWeb::jsonLdProblem($r['body'])??'PASS'):'NOT_EXPECTED_OLD_CODE'];
            $add('/katalog',$r,'200_CSP_MARKERS_'.($installed?'NEW_JSON_LD':'OLD'),!$r['transport']&&$r['status']===200&&$f['cspScriptSrcNone']&&$f['layoutMarker']===$installed&&$f['catalogMarker']===$installed&&(!$installed||$f['jsonLd']==='PASS'),$f,true);
            $r=$this->get('/katalog',['sort'=>'price_asc'],$marker);$add('/katalog?sort=price_asc',$r,$installed?'200':'BELOW_500',$installed?(!$r['transport']&&$r['status']===200):$lt($r),[],true);
            $r=$this->get('/katalog',['sort'=>'ald1n_unknown'],$marker);$add('/katalog?sort=ald1n_unknown',$r,'302_OR_422',!$r['transport']&&in_array($r['status'],[302,422],true),[],true);
            $r=$this->get('/katalog',['page'=>'999'],$marker);$add('/katalog?page=999',$r,$installed?'404':'BELOW_500',$installed?(!$r['transport']&&$r['status']===404):$lt($r),[],true);
            $r=$this->get('/korpa',[],$marker);$f=['layoutMarker'=>str_contains($r['body'],$m['layout'])];$add('/korpa',$r,'200_LAYOUT_'.($installed?'NEW':'OLD'),!$r['transport']&&$r['status']===200&&$f['layoutMarker']===$installed,$f,true);
            $r=$this->get('/registracija',[],$marker);$add('/registracija',$r,'200',!$r['transport']&&$r['status']===200,[],true);
            // A redirect to the sign-in page or a 404: noindex recorded, not asserted (not a page a search engine lists).
            $r=$this->get('/porucivanje',[],$marker);$add('/porucivanje',$r,'302_OR_404',!$r['transport']&&in_array($r['status'],[302,404],true));
        }else foreach(self::SHOP as $p){$r=$this->get($p,[],$marker);$add($p,$r,'404_SHOP_OFF',!$r['transport']&&$r['status']===404,[],$p!=='/porucivanje');}
        // A failed check is judged against the shop state of the whole batch: if the owner changed it meanwhile, the
        // expectations were wrong for part of the batch and nothing is proven (no rollback of exact files on that basis).
        $shopAfter=null;if($failed!==[]){try{$shopAfter=$this->shop();}catch(Throwable $e){$shopAfter=['unreadable'=>self::code($e)];}}
        $this->emit('PUBLIC_HTTP_SMOKES',['state'=>$state,'shop'=>$shop,'shopAfterFailure'=>$shopAfter,'rows'=>$rows,'failed'=>$failed,'inconclusive'=>$inconclusive,'rateLimited'=>$limited,'rateLimitWaitedSeconds'=>$this->limitBatch['waited'],'batch'=>$retried?2:1,'noindexPolicy'=>'OWNER_POLICY_NO_INDEXING_X_ROBOTS_TAG_NOINDEX_REQUIRED_ON_PUBLIC_SHOP_AND_LEGAL_PAGES_SENT_BY_THE_SERVER_WIDE_PUBLIC_HTACCESS','noindexMissing'=>$noindexMissing,'requestsWrite'=>'NONE_GET_ONLY']);
        if($inconclusive!==[])$this->state['cssEdgeStale']=true;
        if($failed!==[]&&$shopAfter!==$shop)throw new RuntimeException('SHOP_STATE_CHANGED_DURING_ACCEPTANCE_CATALOG_NOT_VERIFIED');
        if($failed!==[])throw new RuntimeException($installed?'INSTALLED_SMOKE_FAILED':'OLD_STATE_SMOKE_FAILED');
        // Every code check passed; only the server/edge noindex header is missing: never PASS, never a rollback of exact files.
        if($noindexMissing!==[])throw new RuntimeException('NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES');
        if($limited!==[]){
            if(!$retried){$this->emit('SMOKES_RATE_LIMITED_PAUSE',['seconds'=>$this->rateLimitPauseSeconds,'paths'=>$limited,'next'=>'THE_WHOLE_BATCH_ONCE_MORE']);sleep($this->rateLimitPauseSeconds);return $this->smokes($installed,true);}
            throw new RuntimeException('SMOKES_RATE_LIMITED_429');
        }
        return $rows;
    }

    /** The 11 B1146 route names stay registered, the route cache mode and bytes are unchanged (this overlay changes no route). */
    private function routesCheck(array $cacheBefore):array
    {
        $r=$this->helper($this->routesScript,['routes']);$now=$this->routeCacheFiles();$this->emit('ROUTES_CHECK',$r+['routeCacheUnchanged'=>$now===$cacheBefore]);
        if($r['b1146RouteNamesPresent']!==self::B1146_ROUTE_NAMES||$r['storefrontSettingsRoute']!==true)throw new RuntimeException('B1146_ROUTES_NOT_ALL_PRESENT');
        if($r['routesCached']!==($cacheBefore!==[])||$now!==$cacheBefore)throw new RuntimeException('ROUTE_CACHE_CHANGED');return $r;
    }

    /** The merchant approval facts from the pinned b1146-app-check approval mode (read-only SELECTs). */
    private static function approvalFacts(array $r):array
    {
        // helper() already required ok=true (and removed it); the approval fields must be present booleans.
        if(!is_bool($r['approved']??null)||!is_bool($r['approvalDigestMatchesFingerprint']??null))throw new RuntimeException('MERCHANT_APPROVAL_UNREADABLE');
        return ['approved'=>$r['approved']??null,'complete'=>$r['complete']??null,'digestPresent'=>$r['approvalDigestPresent']??null,'digestMatchesFingerprint'=>$r['approvalDigestMatchesFingerprint']??null];
    }

    /** Prints the approval before (preflight) and now; informational, the approval is never written. */
    private function approvalReport(string $label):void
    {
        $before=$this->state['approvalBefore']??null;try{$after=self::approvalFacts($this->helper($this->routesScript,['approval']));}catch(Throwable $e){$after=self::code($e);}
        $this->emit('MERCHANT_APPROVAL',['label'=>$label,'before'=>$before,'after'=>$after,'ok'=>$before!==null&&$before===$after?'UNCHANGED':'CHANGED_OR_UNREADABLE_REVIEW_IT_IN_THE_ADMIN','writtenByThisTool'=>false]);
    }

    /** The shop state now equals the state recorded at the preflight and is ON; otherwise the catalog checks prove nothing. */
    private function requireRecordedShop(string $point):array
    {
        $shop=$this->shop();$this->emit('SHOP_STATE_CHECK',['point'=>$point,'shop'=>$shop]);
        if(!$shop['open']||$shop!==array_diff_key($this->state['shopRecorded']??[],['writtenByThisTool'=>true]))throw new RuntimeException('SHOP_STATE_CHANGED_DURING_ACCEPTANCE_CATALOG_NOT_VERIFIED');
        return $shop;
    }

    /** The measured web hold (max(realpath_cache_ttl, revalidate_freq) + 5), used between groups where stale bytecode could meet new code. */
    private function viewHold():int
    {
        return max(1,min(FreshnessProbe::MAX_HOLD_SECONDS,(int)($this->state['holdSeconds']??FreshnessProbe::MAX_HOLD_SECONDS)));
    }

    /** CLI: the new classes load through composer exactly when $installed, and APP_URL is an origin the shipped rule accepts. */
    private function catalogCheck(bool $installed):array
    {
        $s=$this->step('app/Support/PublicCatalogPresentation.php');$this->payload($s);$r=$this->helper($this->catalogScript,['catalog',$s['payload'],$s['payloadSHA256']]);$this->emit('CATALOG_CLI_CHECK',$r+['expectInstalled'=>$installed]);
        if($r['presentationAutoloaded']!==$installed||$r['serbianPluralAutoloaded']!==$installed)throw new RuntimeException($installed?'NEW_CLASSES_NOT_LOADABLE_IN_CLI':'NEW_CLASSES_STILL_LOADABLE_AFTER_ROLLBACK');
        return $r;
    }

    /**
     * PREFLIGHT, read-only apart from the private probe record and its transient probe file. Every gate is evaluated and
     * printed; the caller HOLDs on any blocker. $stage 'fresh' (before install) or 'installed' (re-run after the PASS).
     */
    private function preflight(string $stage):array
    {
        $g=[];$v=['stage'=>$stage];$installed=$stage==='installed';
        $try=static function(string $gate,callable $check)use(&$g):mixed{try{$r=$check();$g[$gate]=null;return $r;}catch(Throwable $e){$g[$gate]=self::code($e);return null;}};
        $chain=$try('chainB1146Report1109',fn()=>$this->chain());if($chain!==null)$this->state['chain']=$chain;$v['chain']=$chain;
        $manifest=$try('runtimeManifestPlusB1146',fn()=>$this->b1146Manifest());
        $targets=$try('overlayTargets',fn()=>$this->targets());$v['targets']=$targets;
        if($targets!==null){if(!$installed&&!$targets['preState'])$g['overlayTargets']=$targets['installed']?'OVERLAY_ALREADY_PRESENT_WITHOUT_B1147_STATE_NO_BACKUP_NOTHING_CHANGED':($targets['link']!==[]?'TARGET_IS_SYMLINK':'UNKNOWN_PRE_STATE_REFUSED');if($installed&&!$targets['installed'])$g['overlayTargets']='INSTALLED_DIGESTS_CHANGED';}
        $v['tree']=$manifest!==null&&$chain!==null?$try('activeTreeEqualsManifestPlusB1146',fn()=>$installed?LeanGates::verifyTree($this->active(),$this->installedManifest(),self::CONFIG_SHA,$chain['htaccessSHA256'],self::INSTALLED_TREE_FILES):LeanGates::verifyTree($this->active(),$manifest,self::CONFIG_SHA,$chain['htaccessSHA256'],self::B1146_TREE_FILES)):null;
        if(!array_key_exists('activeTreeEqualsManifestPlusB1146',$g))$g['activeTreeEqualsManifestPlusB1146']='NOT_EVALUATED_CHAIN_OR_MANIFEST';
        if(!$installed)$v['replaceable']=$try('targetsReplaceable',function():array{$n=0;foreach($this->plan as $s){if($s['kind']==='modified'){CatalogFiles::replaceable($this->target($s));$n++;}else{$d=dirname($this->target($s));while(!is_dir($d))$d=dirname($d);if(is_link($d)||!is_writable($d))throw new RuntimeException('TARGET_DIRECTORY_NOT_WRITABLE');}}return ['modifiedOneLinkNoAcl'=>$n];});
        // The 2 new classes are found through the PSR-4 fallback; an authoritative classmap would make them invisible.
        $g['composerClassmapNotAuthoritative']=$this->classmapBlocker();
        $g['configNotCached']=file_exists($this->active().'/bootstrap/cache/config.php')?'CONFIG_CACHE_PRESENT':null;
        $g['noMaintenanceMode']=file_exists($this->active().'/storage/framework/down')||file_exists($this->active().'/storage/framework/maintenance.php')?'MAINTENANCE_MODE_ACTIVE':null;
        $try('noLeftoverProbeOrTemp',function():void{FreshnessProbe::assertNoLeftover($this->active().'/public');if($this->ownTemps()!==[])throw new RuntimeException('LEFTOVER_B1147_TEMP_FILE');});
        $v['caches']=$try('cacheState',fn()=>['routeCacheFiles'=>$this->routeCacheFiles(),'compiledViews'=>$this->compiledViews(),'viewsCached'=>$this->compiledViews()>0]);
        $v['logs']=LeanGates::dailyLogs($this->active().'/storage/logs');$g['laravelDailyLog']=$v['logs']['dailyFiles']>0?null:'LARAVEL_DAILY_LOG_MISSING';
        $v['migrations']=$try('migrateStatus71Ran0Pending',fn()=>$this->migrationStatus());
        $app=$try('appStateDatabase',fn()=>$this->helper($this->appStateScript,['state']));
        if($app!==null){
            $shop=self::shopOf($app);$v['shop']=$shop+['writtenByThisTool'=>false];$v['app']=['backupsRunning'=>$app['backups']['running']];
            // Recorded, never written. A fresh install proves the catalog acceptance only with the shop ON.
            $g['shopStateRecorded']=!$shop['known']?'SHOP_SWITCH_VALUE_UNKNOWN':(!$installed&&!$shop['open']?'SHOP_IS_OFF_B1147_INSTALLS_ONLY_WITH_THE_SHOP_ON_NOTHING_CHANGED':null);
            $g['noRunningBackup']=$app['backups']['table']===true&&$app['backups']['running']===0?null:'BACKUP_RUN_IN_PROGRESS_OR_TABLE_MISSING';
            if($this->scheduleGate){$s=$try('outsideScheduledBackupWindow',fn()=>$this->scheduleState($app));$v['schedule']=$s;if($s!==null&&$s['conflict']!==null)$g['outsideScheduledBackupWindow']='SCHEDULED_BACKUP_WITHIN_30_MINUTES_'.strtoupper((string)$s['conflict']['type']);}
        }else foreach(['shopStateRecorded','noRunningBackup','outsideScheduledBackupWindow'] as $gate)$g[$gate]='NOT_EVALUATED_APP_STATE';
        $catalog=$try('catalogCliCheck',fn()=>$this->catalogCheck($installed));$v['catalogCli']=$catalog;
        // The JSON-LD block and the canonical URL render only for an https origin APP_URL (the shipped baseUrl() rule).
        $g['appUrlHttpsOrigin']=$catalog===null?'NOT_EVALUATED_CATALOG_CLI':(($catalog['appUrlHttpsOrigin']??false)===true?null:'APP_URL_NOT_A_BARE_HTTPS_ORIGIN_JSON_LD_WOULD_BE_MISSING');
        $v['routes']=$v['caches']!==null?$try('b1146LegalRoutesAndRouteCache',fn()=>$this->routesCheck($v['caches']['routeCacheFiles'])):null;if(!array_key_exists('b1146LegalRoutesAndRouteCache',$g))$g['b1146LegalRoutesAndRouteCache']='NOT_EVALUATED_CACHE_STATE';
        $v['headroom']=$try('diskHeadroom',fn()=>$this->headroom());
        $v['artisanCensus']=$chain!==null?$try('noRunningCmsArtisan',fn()=>$this->census()):null;if(!array_key_exists('noRunningCmsArtisan',$g))$g['noRunningCmsArtisan']='NOT_EVALUATED_CHAIN';
        if($targets!==null&&($targets['preState']||$targets['installed'])&&isset($this->state['id'])&&$v['caches']!==null){$web=$try('webProbe',fn()=>$this->probeRun('preflight-'.$stage,$installed,false,false,true));$v['web']=$web;if($web!==null&&$web['failures']!==[])$g['webProbe']=$web['failures'][0];}else $g['webProbe']='NOT_EVALUATED_TARGETS';
        // Time bound: the whole run, including a worst-case 429 acceptance and a rollback, must end before the next scheduled
        // backup. The measured hold sets the need; the window check is repeated with it (and again before the first change).
        if(isset($v['web']['holdSeconds'])){
            $need=$this->requiredWindowSeconds((int)$v['web']['holdSeconds']);$v['timeBound']=['holdSeconds'=>(int)$v['web']['holdSeconds'],'smokeCapSeconds'=>$this->smokeCapSeconds(),'requiredWindowSeconds'=>$need,'rule'=>'4*HOLD+2*SMOKE_CAP+300<=WINDOW'];
            $this->scheduleNeedSeconds=max($this->scheduleNeedSeconds,$need);
            if($this->scheduleGate&&$app!==null){$s=$try('scheduleWindowFitsTimeBound',fn()=>$this->scheduleState($app));$v['scheduleTimeBound']=$s;if($s!==null&&$s['conflict']!==null)$g['scheduleWindowFitsTimeBound']='SCHEDULED_BACKUP_WITHIN_THE_'.$need.'_SECONDS_THIS_RUN_MAY_NEED';}
        }else $g['timeBound']='NOT_EVALUATED_WEB_PROBE';
        // Recorded before any change and compared after: none of the 8 files feeds the merchant approval digest.
        $v['merchantApproval']=$try('merchantApprovalRead',fn()=>self::approvalFacts($this->helper($this->routesScript,['approval'])));
        $try('liveSmokes',fn()=>$this->smokes($installed));
        $blockers=array_values(array_unique(array_filter($g,static fn($c)=>$c!==null)));
        if(isset($v['shop']))$this->state['shopRecorded']=$v['shop'];
        $report=['stage'=>$stage,'gates'=>array_map(static fn($c)=>$c??'PASS',$g),'values'=>$v,'blockers'=>$blockers,'applicationFiles'=>'NONE','database'=>'NO_PRODUCT_CART_OR_SWITCH_WRITES_SESSIONS_AND_APP_CACHE_ONLY','shopSwitch'=>'READ_ONLY','transientProbe'=>'WRITTEN_AND_REMOVED','privateState'=>'B1147_NAMESPACE_ONLY'];$this->emit('PREFLIGHT',$report);return $report;
    }

    /** null = composer autoload is not authoritative; otherwise the blocker code (authoritative, or the autoloader is unreadable). */
    private function classmapBlocker():?string
    {
        foreach(['autoload_real.php','autoload_static.php'] as $name){
            $path=$this->active().'/vendor/composer/'.$name;if(!is_file($path)||is_link($path)){if($name==='autoload_real.php')return 'COMPOSER_AUTOLOAD_REAL_MISSING_OR_LINK_CANNOT_VERIFY_THE_CLASSMAP_MODE';continue;}$text=(string)file_get_contents($path);
            if(preg_match('/setClassMapAuthoritative\s*\(\s*true\s*\)/i',$text)||preg_match('/classMapAuthoritative\s*=\s*true/i',$text))return 'COMPOSER_CLASSMAP_AUTHORITATIVE_NEW_CLASSES_WOULD_NOT_LOAD';
        }
        return null;
    }

    private function loadExisting():bool
    {
        if(!file_exists($this->pointer))return false;if(is_link($this->pointer)||filesize($this->pointer)>8192)throw new RuntimeException('B1147_POINTER');$p=json_decode((string)file_get_contents($this->pointer),true,8,JSON_THROW_ON_ERROR);
        if(!preg_match('/^[a-f0-9]{32}$/D',(string)($p['id']??''))||($p['private']??null)!==$this->base.'/'.$p['id'])throw new RuntimeException('B1147_POINTER_SCOPE');$path=$p['private'].'/state.json';
        if(is_link($path)||realpath($path)!==$path||filesize($path)>16777216)throw new RuntimeException('B1147_STATE');$this->state=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
        if(($this->state['schemaVersion']??0)!==1||($this->state['kind']??null)!=='B1147'||($this->state['id']??null)!==$p['id'])throw new RuntimeException('B1147_STATE_BINDING');return true;
    }

    private function begin():void
    {
        $id=bin2hex(random_bytes(16));$private=$this->base.'/'.$id;if(!mkdir($private,0700))throw new RuntimeException('PRIVATE_TRANSACTION_CREATE');
        $this->state=['schemaVersion'=>1,'kind'=>'B1147','id'=>$id,'private'=>$private,'phase'=>'NEW','startedAt'=>time(),'probes'=>[],'temps'=>[],'applied'=>[],'createdDirs'=>[]];$this->save('PREFLIGHT');
    }

    /** Per-file 0600 copies of every replaced file with a digest manifest (0600); the route cache digests are recorded. */
    private function backup(array $preflight):void
    {
        $dir=$this->state['private'].'/backup';if(!is_dir($dir)&&!mkdir($dir,0700))throw new RuntimeException('BACKUP_DIRECTORY_CREATE');$files=[];
        foreach($this->plan as $s){
            if($s['kind']==='new'){$files[$s['n']]=['path'=>$s['path'],'kind'=>'new','before'=>'ABSENT'];continue;}
            $t=$this->target($s);$bytes=(string)file_get_contents($t);if(!hash_equals((string)$s['preSHA256'],hash('sha256',$bytes)))throw new RuntimeException('PRE_STATE_CHANGED_BEFORE_BACKUP');clearstatcache(true,$t);$st=stat($t);
            $copy=$dir.'/'.sprintf('%02d',$s['n']).'.orig';$this->privateBackupCopy($copy,$bytes);
            $files[$s['n']]=['path'=>$s['path'],'kind'=>'modified','copy'=>$copy,'sha256'=>$s['preSHA256'],'size'=>strlen($bytes),'mode'=>$st['mode']&0777,'gid'=>$st['gid'],'mtime'=>$st['mtime']];
        }
        $this->state['backup']=['directory'=>$dir,'files'=>$files];$this->state['cache']=['routeCacheFiles'=>$preflight['values']['caches']['routeCacheFiles'],'viewsCached'=>$preflight['values']['caches']['viewsCached']];
        $this->state['groupPauseSeconds']=(int)($preflight['values']['web']['revalidateFreq']??2)+1;$this->state['holdSeconds']=(int)($preflight['values']['web']['holdSeconds']??FreshnessProbe::MAX_HOLD_SECONDS);$this->state['approvalBefore']=$preflight['values']['merchantApproval']??null;$doc=$dir.'/manifest.json';if(is_file($doc))unlink($doc);AtomicFile::privateCopy($doc,json_encode(['batch'=>'B1147','files'=>array_values($files),'routeCacheDigests'=>$this->state['cache']['routeCacheFiles']],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
        $this->fault('AFTER_BACKUP_COPIES');
        $this->emit('PER_FILE_BACKUP',['directory'=>$dir,'modifiedFilesCopied'=>count(array_filter($files,static fn($f)=>$f['kind']==='modified')),'newFilesRecordedAbsent'=>count(array_filter($files,static fn($f)=>$f['kind']==='new')),'routeCacheFilesRecorded'=>count($this->state['cache']['routeCacheFiles']),'mode'=>'0600','manifestSHA256'=>hash_file('sha256',$doc)]);
        $this->save('BACKED_UP');$this->fault('AFTER_BACKUP');
    }

    /** A copy left by an interrupted backup of this transaction (still pre-state) is reused only when it holds exactly the live bytes. */
    private function privateBackupCopy(string $copy,string $bytes):void
    {
        if(file_exists($copy)||is_link($copy)){
            try{AtomicFile::readPrivate($copy,hash('sha256',$bytes));return;}catch(Throwable){if(is_link($copy)||!is_file($copy)||!unlink($copy))throw new RuntimeException('BACKUP_COPY_STALE_NOT_REMOVABLE');$this->emit('BACKUP_COPY_REWRITTEN',basename($copy));}
        }
        AtomicFile::privateCopy($copy,$bytes);AtomicFile::readPrivate($copy,hash('sha256',$bytes));
    }

    /** view:clear only: compiled views are rebuilt on demand by the web workers; views are never pre-compiled. */
    private function refreshViews():void {$this->artisanOk(['view:clear']);$this->changed();$this->persist();}

    private function install():void
    {
        $this->state['logBaseline']=LeanGates::logBaseline($this->active().'/storage/logs');$this->state['applied']=[];$this->save('INSTALL_INTENT');$group=null;
        // Between groups every web worker revalidates the files of the previous group first (revalidate_freq + 1 s), so a
        // request never combines new bytecode of a later group with still-cached old bytecode of an earlier one.
        $pause=max(1,min(61,(int)($this->state['groupPauseSeconds']??3)));
        foreach($this->plan as $s){
            // The views come last and only after the full measured hold: by then every worker has dropped the cached old
            // controller, service and stylesheet state, so a new view never renders against old code (the shop is ON).
            if($group!==null&&$group!==$s['group']){if($s['group']==='D')$this->hold('before-views',$this->viewHold());else{$this->emit('OVERLAY_GROUP_PAUSE',['after'=>$group,'before'=>$s['group'],'seconds'=>$pause]);sleep($pause);}}$group=$s['group'];
            $this->fault('BEFORE_STEP_'.$s['n']);$t=$this->target($s);$bytes=$this->payload($s);$this->state['applied'][$s['n']]='PENDING';
            if($s['kind']==='new'){
                // A new file or directory takes the group of its parent directory, like the rest of the tree.
                $dir=dirname($t);if(!is_dir($dir)){if(!is_dir(dirname($dir)))throw new RuntimeException('TARGET_PARENT_MISSING');$this->state['createdDirs'][]=$dir;$this->persist();if(!mkdir($dir,0755))throw new RuntimeException('TARGET_DIRECTORY_CREATE');chmod($dir,0755);$pg=stat(dirname($dir))['gid'];if(stat($dir)['gid']!==$pg)@chgrp($dir,$pg);clearstatcache(true,$dir);if(stat($dir)['gid']!==$pg)throw new RuntimeException('OVERLAY_DIRECTORY_GID_NOT_PRESERVABLE');}
                $temp=$this->recordTemp(CatalogFiles::tempFor($t));$receipt=CatalogFiles::create($t,$bytes,0644,$temp,stat($dir)['gid']);
            }else{$b=$this->state['backup']['files'][$s['n']];$temp=$this->recordTemp(CatalogFiles::tempFor($t));$receipt=CatalogFiles::replace($t,$bytes,$b['mode'],$b['gid'],$temp,(string)$s['preSHA256']);}
            $this->state['temps']=array_values(array_filter($this->state['temps'],static fn($x)=>$x!==$temp));$this->state['applied'][$s['n']]='DONE';$this->changed();$this->persist();
            $this->emit('OVERLAY_STEP',['n'=>$s['n'],'group'=>$s['group'],'kind'=>$s['kind'],'path'=>$s['path'],'sha256'=>$s['payloadSHA256']]+$receipt);$this->fault('AFTER_STEP_'.$s['n']);
        }
        $this->refreshViews();$this->save('FILES_INSTALLED');
    }

    private function accept():void
    {
        $this->save('ACCEPTANCE_INTENT');$first=$this->probeRun('installed-invalidate',true,true,false);$this->fault('AFTER_INVALIDATE');$this->hold('installed',(int)$first['holdSeconds']);
        // The catalog checks (/katalog, JSON-LD, page=999) run only with the shop ON as recorded; checked before and after.
        $this->requireRecordedShop('BEFORE_INSTALLED_SMOKES');$this->smokes(true);$this->fault('AFTER_SMOKES_INSTALLED');$this->requireRecordedShop('AFTER_INSTALLED_SMOKES');
        $this->probeRun('installed-fresh',true,false,true);$this->routesCheck($this->state['cache']['routeCacheFiles']);$this->catalogCheck(true);
        $scan=LeanGates::newLogErrors($this->active().'/storage/logs',$this->state['logBaseline']);$this->emit('LARAVEL_LOG_SINCE_INSTALL',$scan);if($scan['newErrorLines']!==0)throw new RuntimeException('NEW_LARAVEL_LOG_ERRORS_SINCE_INSTALL');
        $tree=LeanGates::verifyTree($this->active(),$this->installedManifest(),self::CONFIG_SHA,$this->state['chain']['htaccessSHA256'],self::INSTALLED_TREE_FILES);$this->emit('ACTIVE_TREE_AFTER_INSTALL',$tree+['manifestPlusB1146PlusB1147'=>true]);
        $this->approvalReport('after-install');$this->requireRecordedShop('BEFORE_INSTALLED_PASS');
        $this->save('INSTALLED_PASS');
    }

    /**
     * Puts every backed-up byte back in exact reverse install order (a modified file only when it holds the payload, a new
     * file removed only when it holds the payload; anything foreign is reported and never overwritten; the first failure
     * stops the loop), clears the compiled views (never pre-compiles them), re-invalidates, waits and smoke-tests the old state with the
     * shop in whatever state it is now (the rollback never changes it and does not need it OFF: no data or route changes).
     */
    private function restore(string $reason):bool
    {
        $this->emit('OVERLAY_ROLLBACK',['reason'=>$reason]);$problems=[];try{$this->save('ROLLBACK_INTENT');}catch(Throwable $e){$problems[]=self::code($e);}
        try{$this->cleanupRecorded();}catch(Throwable $e){$problems[]=self::code($e);}
        $backup=$this->state['backup']??null;$restored=0;$removed=0;$stoppedBefore=null;$touched=true;
        try{$touched=$this->touched();}catch(Throwable $e){$problems[]=self::code($e);}
        if(!$touched){
            // Nothing of the overlay reached the tree: no view or web work at all, reported only when every target is exactly
            // in its pre-state (re-checked here, not assumed).
            try{if(!$this->targets()['preState'])$problems[]='NOTHING_APPLIED_BUT_TARGETS_NOT_EXACTLY_PRE_STATE';}catch(Throwable $e){$problems[]=self::code($e);}
            foreach(array_reverse($this->state['createdDirs']??[]) as $dir)if(is_dir($dir)&&!is_link($dir)&&(scandir($dir)?:[])===['.','..']&&!rmdir($dir))$problems[]='CREATED_DIRECTORY_NOT_REMOVED';
            try{$this->persist();}catch(Throwable $e){$problems[]=self::code($e);}
            $this->emit('ROLLBACK_FILES',['nothingApplied'=>true,'modifiedRestored'=>0,'newRemoved'=>0,'problems'=>$problems]);
            if($problems===[]){$this->save('ROLLED_BACK');$this->emit('ROLLED_BACK',['files'=>'NONE_WERE_CHANGED','refreshAndWebVerification'=>'NOT_NEEDED','shopSwitch'=>'NOT_WRITTEN']);return true;}
        }elseif(!is_array($backup))$problems[]='ROLLBACK_BACKUP_RECORD_MISSING';
        if($touched&&is_array($backup)){
            $group=null;$pause=max(1,min(61,(int)($this->state['groupPauseSeconds']??3)));
            foreach(array_reverse($this->plan) as $s){
                // Exact reverse of the install order, stopping at the FIRST failed restore: the tree is then always an exact
                // install prefix (steps 1..n installed, everything after n back to old), a state the install itself passed.
                // A new file is removed in its own later step group: a worker still holding the new compiled catalog view
                // (which includes the new pagination view) must first revalidate the restored catalog view. The new classes
                // (group A) go only after the full measured hold, once no worker can still run the new controller or views.
                // Every boundary waits, also in a fresh process resuming a rollback (hold() then times the wait from the
                // saved wall-clock time of the last change, so an old change costs nothing and a recent one is waited out).
                // Before the new pagination view goes, the compiled views are cleared, so no worker renders a compiled
                // catalog view that still includes it once the pause has passed.
                $key=$s['group'].($s['kind']==='new'?'-NEW':'');$boundary=$group!==null&&$group!==$key;$group=$key;
                try{
                    if($boundary){
                        if($s['group']==='A')$this->hold('rollback-before-new-classes',$this->viewHold());
                        else{
                            // Only while the new view is still there: clearing views counts as a change, so a resumed rollback
                            // whose pagination view is already gone keeps the saved clock of the earlier change.
                            $clear=$key==='D-NEW'&&$this->classify($s)['state']==='payload';if($clear)$this->refreshViews();
                            $this->emit('ROLLBACK_GROUP_PAUSE',['before'=>$key,'seconds'=>$pause,'viewsClearedFirst'=>$clear]);$this->hold('rollback-pause-'.$key,$pause);}
                    }
                    $this->fault('BEFORE_RESTORE_'.$s['n']);$c=$this->classify($s);$t=$this->target($s);
                    if($s['kind']==='modified'){
                        if($c['state']==='payload'){$b=$backup['files'][$s['n']];$bytes=AtomicFile::readPrivate($b['copy'],$b['sha256']);CatalogFiles::replace($t,$bytes,$b['mode'],$b['gid'],$this->recordTemp(CatalogFiles::tempFor($t)),$s['payloadSHA256']);$restored++;$this->changed();}
                        elseif($c['state']!=='pre')throw new RuntimeException('ROLLBACK_FOREIGN_CONTENT_NOT_OVERWRITTEN_'.$s['n']);
                    }elseif($c['state']==='payload'){CatalogFiles::removeExact($t,$s['payloadSHA256']);$removed++;$this->changed();}
                    elseif($c['state']!=='absent')throw new RuntimeException('ROLLBACK_FOREIGN_NEW_FILE_NOT_REMOVED_'.$s['n']);
                }catch(Throwable $e){$problems[]=self::code($e);$stoppedBefore=$s['n'];break;}
            }
            if($stoppedBefore===null)foreach(array_reverse($this->state['createdDirs']??[]) as $dir)if(is_dir($dir)&&!is_link($dir)&&(scandir($dir)?:[])===['.','..']&&!rmdir($dir))$problems[]='CREATED_DIRECTORY_NOT_REMOVED';
            try{$this->cleanupRecorded();$this->refreshViews();}catch(Throwable $e){$problems[]=self::code($e);}
        }
        if($touched){
            try{$this->persist();}catch(Throwable $e){$problems[]=self::code($e);}
            $this->emit('ROLLBACK_FILES',['modifiedRestored'=>$restored,'newRemoved'=>$removed,'stoppedAtStep'=>$stoppedBefore,'exactInstallPrefixKept'=>$stoppedBefore===null?null:($this->isInstallPrefix($stoppedBefore)?'STEPS_1_TO_'.$stoppedBefore.'_INSTALLED_LATER_STEPS_RESTORED':'NOT_AN_INSTALL_PREFIX_SEE_RECOVERY_NOTE_FILES'),'problems'=>$problems]);
        }
        if($problems===[])try{
            $t=$this->targets();if(!$t['preState'])throw new RuntimeException('ROLLBACK_TARGETS_NOT_PRE_STATE');$this->routesCheck($this->state['cache']['routeCacheFiles']??[]);$this->catalogCheck(false);
            $first=$this->probeRun('restored-invalidate',false,true,false);$this->hold('restored',(int)$first['holdSeconds']);
            // A rate-limited old-state smoke proves nothing either way; with the files exact (checked above and by the tree
            // digest below) it is reported to the owner and never turns an exact restore into ROLLBACK_INCOMPLETE.
            // A shop switch flipped during the batch: the old state is judged once more against the state read now.
            try{try{$this->smokes(false);}catch(Throwable $e){if(self::code($e)!=='SHOP_STATE_CHANGED_DURING_ACCEPTANCE_CATALOG_NOT_VERIFIED')throw $e;$this->emit('OLD_STATE_SMOKES_REPEATED','THE_SHOP_STATE_CHANGED_DURING_THE_BATCH');$this->smokes(false);}}catch(Throwable $e){
                // Server or edge facts, not the restored code: reported to the owner, the exact restore still completes.
                if(self::code($e)==='NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES'){$this->state['rollbackNoindexMissing']=true;$this->emit('OWNER_NOTICE_NOINDEX_MISSING','THE_RESTORED_FILES_ARE_EXACT_BUT_PUBLIC_PAGES_ARE_SERVED_WITHOUT_X-ROBOTS-TAG_NOINDEX_RESTORE_THE_HEADER_LINE_IN_apps/cms/current/public/.htaccess_OR_THE_CLOUDFLARE_RULE_THAT_REMOVES_IT');}
                elseif(self::code($e)==='SMOKES_RATE_LIMITED_429'){$this->state['rollbackSmokesRateLimited']=true;$this->emit('OWNER_NOTICE_RATE_LIMITED','THE_RESTORED_FILES_ARE_EXACT_BUT_THE_SITE_ANSWERED_429_TO_THE_SMOKES_OPEN_https://cms.ald1n.com/katalog_AND_ONE_LEGAL_PAGE_YOURSELF');}
                else throw $e;}
            $this->probeRun('restored-fresh',false,false,true);
            $tree=LeanGates::verifyTree($this->active(),$this->b1146Manifest(),self::CONFIG_SHA,$this->state['chain']['htaccessSHA256'],self::B1146_TREE_FILES);$this->emit('ACTIVE_TREE_AFTER_ROLLBACK',$tree+['equalsManifestPlusB1146'=>true]);
            $this->approvalReport('after-rollback');
        }catch(Throwable $e){$problems[]=self::code($e);}
        if($problems!==[]){
            try{$this->save('ROLLBACK_INCOMPLETE');}catch(Throwable){$this->emit('ROLLBACK_STATE_UNWRITABLE','FIX_DISK_OR_PERMISSIONS_THEN_RUN_THE_SAME_COMMAND');}
            $this->recoveryNote(array_values(array_unique($problems)));return false;
        }
        $this->save('ROLLED_BACK');$this->emit('ROLLED_BACK',['files'=>'EXACT_PRIVATE_COPIES','newFiles'=>'REMOVED','routeCache'=>'UNCHANGED_NEVER_TOUCHED','compiledViews'=>'CLEARED_REBUILT_ON_DEMAND_NOT_PRECOMPILED','tree'=>'EQUALS_8543_FILE_MANIFEST_PLUS_B1146','shopSwitch'=>'NOT_WRITTEN']);return true;
    }

    /** Whether any overlay byte may have reached the tree: an applied step, a payload file, a created directory, a non-pre target. */
    private function touched():bool
    {
        foreach((array)($this->state['applied']??[]) as $v)if($v==='DONE'||$v==='PENDING')return true;
        if(($this->state['createdDirs']??[])!==[])return true;
        $t=$this->targets();return !$t['preState']||$t['payload']>0||$t['foreign']!==[]||$t['link']!==[];
    }

    /** Verified, not assumed: steps 1..$k hold the payload, every later step its old bytes (a later new file is absent). */
    private function isInstallPrefix(int $k):bool
    {
        try{foreach($this->plan as $s){$c=$this->classify($s)['state'];if($s['n']<=$k?$c!=='payload':$c!==($s['kind']==='new'?'absent':'pre'))return false;}}catch(Throwable){return false;}
        return true;
    }

    /**
     * The recovery note: every target's state and ONE manual block (a quoted heredoc run by `bash -e`, so the first failing
     * line stops the block). The automatic rollback order: exact reverse install order (each backup copy sha256-verified
     * right before it is installed, each new file removed), a sleep at every group boundary, view:clear before the new
     * pagination view goes, the full measured hold before the new classes, then the now-empty directories and view:clear
     * with the PHP binary of this tool. The shop may stay ON: these files change no data and no route.
     * $kind ROLLBACK_INCOMPLETE: a rollback ran and stopped. ROLLBACK_NOT_STARTED_MANUAL_ONLY: a gate stopped it before any
     * restore; nothing was attempted and the block is for the owner only.
     */
    private function recoveryNote(array $problems,string $kind='ROLLBACK_INCOMPLETE'):void
    {
        $a=$this->active();$files=[];$q=static fn(string $v):string=>escapeshellarg($v);
        foreach($this->plan as $s){try{$now=$this->classify($s)['state'];}catch(Throwable){$now='UNREADABLE';}$b=$this->state['backup']['files'][$s['n']]??null;$files[$s['n']]=['n'=>$s['n'],'group'=>$s['group'],'kind'=>$s['kind'],'path'=>$s['path'],'now'=>$now,'backupCopy'=>$b['copy']??null,'sha256'=>$b['sha256']??null,'mode'=>isset($b['mode'])?sprintf('%04o',$b['mode']):null,'gid'=>$b['gid']??null];}
        $record=is_array($this->state['backup']['files']??null);
        $next=$kind==='ROLLBACK_INCOMPLETE'?'FIX_THE_REPORTED_CAUSE_THEN_RUN_THE_SAME_COMMAND_IT_RETRIES_THE_ROLLBACK_FIRST':'NOTHING_WAS_ATTEMPTED_CLEAR_THE_BLOCKERS_AND_RUN_THE_SAME_COMMAND';
        $this->emit('RECOVERY_NOTE',['kind'=>$kind,'problems'=>$problems,'files'=>array_values($files),'backupDirectory'=>$this->state['backup']['directory']??null,'backupRecord'=>$record?'PRESENT':'MISSING_NEW_FILES_ARE_NOT_REMOVED_BY_THE_BLOCK','privateCopies'=>'KEPT_0600','next'=>$next,'manualBlock'=>'ONLY_IF_THE_SAME_COMMAND_KEEPS_FAILING_THE_SHOP_MAY_STAY_ON','order'=>'THE_AUTOMATIC_ROLLBACK_ORDER_REVERSE_INSTALL_ORDER_WITH_A_SLEEP_AT_EACH_GROUP_BOUNDARY_VIEW_CLEAR_BEFORE_THE_NEW_PAGINATION_VIEW_THE_FULL_HOLD_BEFORE_THE_NEW_CLASSES_THEN_VIEW_CLEAR']);
        $lines=['set -euo pipefail','umask 022'];$view='cd '.$q($a).' && '.$q($this->php).' -d opcache.enable=0 -d opcache.enable_cli=0 artisan view:clear --no-interaction';
        $pause=max(1,min(61,(int)($this->state['groupPauseSeconds']??3)));$last=null;
        // The same order and waits as the automatic rollback: reverse install order, the new pagination view in its own later
        // group after view:clear and a pause, a pause at every group boundary, the full measured hold before the new classes.
        foreach(array_reverse($files) as $f){
            $t=$a.'/'.$f['path'];$key=$f['group'].($f['kind']==='new'?'-NEW':'');$act=[];
            if($f['kind']==='modified'&&$f['now']==='payload'&&$f['backupCopy']!==null)$act=['printf '.$q('%s  %s\n').' '.$q((string)$f['sha256']).' '.$q((string)$f['backupCopy']).' | sha256sum --check --strict --quiet -','install -m '.$f['mode'].' -g '.(int)$f['gid'].' '.$q((string)$f['backupCopy']).' '.$q($t)];
            elseif($f['kind']==='modified'&&$f['now']==='payload')$act=['echo '.$q('NO_BACKUP_COPY_FOR '.$t.' STOP').' >&2','exit 2'];
            elseif($f['kind']==='new'&&$f['now']==='payload'&&$record)$act=['rm -f -- '.$q($t)];
            elseif($f['kind']==='modified'&&$f['now']==='absent')echo 'OWNER_INSPECT: '.$t.' IS_MISSING_NOT_REMOVED_BY_B1147_THE_OLD_BYTES_ARE_IN '.($f['backupCopy']??'NO_BACKUP_COPY').' SHA256 '.($f['sha256']??'UNKNOWN').PHP_EOL;
            elseif(!in_array($f['now'],['pre','absent','payload'],true))echo 'OWNER_INSPECT: '.$t.' IS_'.strtoupper($f['now']).'_NOT_TOUCHED_BY_B1147'.PHP_EOL;
            if($act===[])continue;
            if($last!==null&&$last!==$key){if($f['group']==='A')$lines[]='sleep '.$this->viewHold();else{if($key==='D-NEW')$lines[]=$view;$lines[]='sleep '.$pause;}}
            $last=$key;array_push($lines,...$act);
        }
        if($record)foreach(array_reverse($this->state['createdDirs']??[]) as $dir)if(is_dir($dir)&&!is_link($dir))$lines[]='rmdir --ignore-fail-on-non-empty -- '.$q($dir);
        $lines[]='cd '.$q($a).' && '.$q($this->php).' -d opcache.enable=0 -d opcache.enable_cli=0 artisan view:clear --no-interaction';$lines[]='echo B1147_MANUAL_RECOVERY_BLOCK_DONE';
        echo 'OWNER_COMMAND_BLOCK=PASTE_EVERYTHING_FROM_THE_bash_-e_LINE_TO_THE_B1147_RECOVERY_LINE_INTO_THE_SSH_SHELL_IT_STOPS_AT_THE_FIRST_FAILING_LINE'.PHP_EOL;
        echo "bash -e <<'B1147_RECOVERY'".PHP_EOL.implode(PHP_EOL,$lines).PHP_EOL.'B1147_RECOVERY'.PHP_EOL;
        echo 'OWNER_COMMAND_NOTE=THE_WEB_WORKERS_SEE_THE_RESTORED_FILES_AFTER_ABOUT_2_MINUTES_(realpath_cache_ttl)'.PHP_EOL;
    }

    /**
     * Gates before any restore that the owner asks for (--rollback, $strict) or that resumes an interrupted install: no CMS
     * artisan process, and for --rollback a readable app state and a clear scheduled-backup window. The shop state is only
     * recorded: no data or route changes, so the shop may stay ON during the rollback. Read-only.
     */
    private function rollbackGates(bool $strict):array
    {
        $b=[];$v=[];
        try{$app=$this->helper($this->appStateScript,['state']);$v['shop']=self::shopOf($app)+['writtenByThisTool'=>false];
            if($strict&&$this->scheduleGate){$s=$this->scheduleState($app);$v['schedule']=$s;
                // The owner override (--rollback --ignore-schedule) lifts only this gate; the census gate stays.
                if($s['conflict']!==null){if($this->ignoreSchedule)$v['scheduleOverride']='SCHEDULED_BACKUP_CONFLICT_IGNORED_BY_THE_OWNER_OVERRIDE';else $b[]='SCHEDULED_BACKUP_WITHIN_30_MINUTES_'.strtoupper((string)$s['conflict']['type']).'_WAIT_OR_RUN_WITH_--rollback_--ignore-schedule';}}
        }catch(Throwable $e){$v['appState']=self::code($e);if($strict)$b[]='APP_STATE_UNREADABLE_'.self::code($e);}
        try{$v['census']=$this->census();}catch(Throwable $e){$b[]=self::code($e);}
        $this->emit('ROLLBACK_GATES',['strict'=>$strict,'values'=>$v,'blockers'=>$b]);return $b;
    }

    /** Right before the first change: inside the time budget, the shop state as recorded, no backup due, no B1146 run. */
    private function beforeFirstChange():void
    {
        $elapsed=intdiv(hrtime(true)-(int)$this->runStartNs,1000000000);if($elapsed>$this->budgetSeconds)throw new RuntimeException('TIME_BUDGET_EXCEEDED_BEFORE_FIRST_CHANGE');
        $app=$this->helper($this->appStateScript,['state']);$shop=self::shopOf($app);if($shop!==array_diff_key($this->state['shopRecorded']??[],['writtenByThisTool'=>true]))throw new RuntimeException('SHOP_STATE_CHANGED_BEFORE_FIRST_CHANGE');if(!$shop['open'])throw new RuntimeException('SHOP_IS_OFF_B1147_INSTALLS_ONLY_WITH_THE_SHOP_ON_NOTHING_CHANGED');
        if($this->scheduleGate){$s=$this->scheduleState($app);if($s['conflict']!==null)throw new RuntimeException('SCHEDULED_BACKUP_WITHIN_30_MINUTES_RECHECK');}
        $this->b1146Idle();
        $this->emit('BEFORE_FIRST_CHANGE',['elapsedSeconds'=>$elapsed,'budgetSeconds'=>$this->budgetSeconds,'shop'=>$shop,'scheduleRechecked'=>$this->scheduleGate,'b1146RunActive'=>false]);
    }

    /** A rollback gate stopped the run before any restore: when the tree holds overlay bytes, the manual-only block is printed. */
    private function manualNoteIfTouched(array $blockers):void
    {
        try{$touched=$this->touched();}catch(Throwable){$touched=true;}
        if($touched)$this->recoveryNote($blockers,'ROLLBACK_NOT_STARTED_MANUAL_ONLY');
    }

    private function hold2(string $phase,array $report):int {$this->save($phase);$this->emit('HOLD_NO_MUTATION',['phase'=>$phase,'blockers'=>$report['blockers']]);return 2;}

    private function cssNotice():void
    {
        if(($this->state['cssEdgeStale']??false)===true)echo 'OWNER_NOTICE_CSS=CLOUDFLARE_SERVED_AN_EDGE_CACHED_CSS_COPY_DURING_THE_SMOKES_THE_PURGE_IN_STEP_1_IS_REQUIRED'.PHP_EOL;
    }

    private function finish(string $final):int
    {
        $this->final=$final;$this->emit('B1147_RESULT',['overlay'=>'CATALOG_REDESIGN_8_FILES','sourceCommit'=>self::SOURCE_COMMIT,'newFiles'=>3,'modifiedFiles'=>5,'tests'=>'NOT_INSTALLED','shopSwitch'=>'READ_ONLY_NOT_WRITTEN','routes'=>'UNCHANGED','migrations'=>'NONE','env'=>'UNCHANGED','config'=>'UNCHANGED','opcache'=>'TARGETED_PER_FILE_INVALIDATION_ONLY','backupDirectory'=>$this->state['backup']['directory']??'NONE_ALREADY_INSTALLED_BEFORE_THIS_TOOL_STATE']);
        $this->cssNotice();
        $this->emit('OWNER_NEXT_STEPS',array_merge([$this->purgeStep(),'2_OPEN_https://cms.ald1n.com/katalog_IN_A_PRIVATE_WINDOW_LIGHT_AND_DARK_THEME_PHONE_AND_DESKTOP_AND_CHECK_THE_FILTERS_SORTING_PAGINATION_AND_THE_BACK_TO_TOP_BUTTON','3_OPEN_ONE_LEGAL_PAGE_FOR_EXAMPLE_https://cms.ald1n.com/pravne-informacije_AND_CHECK_THE_NEW_HEADER','4_THE_SHOP_STAYS_IN_THE_STATE_YOU_SET_THIS_TOOL_NEVER_CHANGES_IT'],isset($this->state['backup']['directory'])?['ROLLBACK_IF_NEEDED=bash /home/icaffeco/ald1n-project/incoming/b1147-catalog-overlay.sh --rollback (THE_SHOP_MAY_STAY_ON)']:['NO_B1147_BACKUP_IN_THIS_STATE_NO_AUTOMATIC_ROLLBACK']));
        $this->emit('DO_NOT_RERUN_B1144_B1145_OR_B1146_AFTER_B1147','THEIR_MANIFEST_GATES_NOW_SEE_THE_8_CATALOG_FILES');return 0;
    }

    private function alreadyInstalled():int
    {
        $p=$this->preflight('installed');if($p['blockers']!==[]){$this->emit('HOLD_INSTALLED_REVERIFY',$p['blockers']);return 2;}
        $this->emit('ALREADY_INSTALLED_NO_REPEAT',['digestsVerified'=>count($this->plan),'treeFiles'=>self::INSTALLED_TREE_FILES]);return $this->finish('PASS_ALREADY_INSTALLED');
    }

    public function run():int
    {
        $this->runStartNs=hrtime(true);
        try{$this->open();$existing=$this->loadExisting();if(!$existing||($this->mode==='install'&&$this->state['phase']==='ROLLED_BACK'))$this->begin();}catch(Throwable $e){$this->emit('HOLD_BEFORE_ANY_MUTATION',self::code($e));return 2;}
        try{
            $phase=$this->state['phase'];
            if($this->mode==='rollback'){
                if(!in_array($phase,['INSTALLED_PASS','ACCEPTANCE_NOT_VERIFIED','ROLLBACK_INCOMPLETE','ROLLBACK_INTENT','ACCEPTANCE_INTENT','FILES_INSTALLED','INSTALL_INTENT','BACKED_UP'],true)){$this->emit('HOLD_NOTHING_TO_ROLL_BACK',['phase'=>$phase]);return 2;}
                $this->state['chain']??=$this->chain();
                $blockers=$this->rollbackGates(true);
                if($blockers!==[]){$this->emit('HOLD_ROLLBACK_NOT_STARTED',['blockers'=>$blockers,'filesChanged'=>'NONE','next'=>'CLEAR_THE_BLOCKERS_THEN_RUN_THE_SAME_COMMAND']);$this->manualNoteIfTouched($blockers);return 2;}
                if(!$this->restore('OWNER_REQUESTED_ROLLBACK'))return 2;$this->final='PASS_ROLLED_BACK_BY_OWNER';$this->emit('OWNER_NOTICE','THE_OLD_CATALOG_CODE_IS_ACTIVE_AGAIN_A_LATER_RUN_OF_THE_SAME_COMMAND_WITHOUT_OPTIONS_INSTALLS_AGAIN');
                $this->cssNotice();$this->emit('OWNER_NEXT_STEPS',[$this->purgeStep(),'2_THE_OLD_LAYOUT_LINKS_THE_BARE_CSS_URL_SO_ITS_PURGE_MATTERS_MOST','3_THE_SHOP_STAYS_IN_THE_STATE_YOU_SET_THIS_TOOL_NEVER_CHANGES_IT']);return 0;
            }
            if($phase==='INSTALLED_PASS')return $this->alreadyInstalled();
            if($phase==='ACCEPTANCE_NOT_VERIFIED')return $this->resumeAcceptance();
            if(in_array($phase,self::IN_FLIGHT,true)){
                $this->emit('RESUMED_INTERRUPTED_INSTALL',['phase'=>$phase]);$this->state['chain']??=$this->chain();
                $blockers=$this->rollbackGates(false);if($blockers!==[]){$this->emit('HOLD_RESUMED_ROLLBACK_NOT_STARTED',['blockers'=>$blockers,'filesChanged'=>'NONE','next'=>'RUN_THE_SAME_COMMAND_AGAIN_WHEN_THE_BLOCKERS_ARE_GONE']);$this->manualNoteIfTouched($blockers);return 2;}
                $ok=$this->restore('RESUMED_INTERRUPTED_'.$phase);$this->final=$ok?'HOLD_ROLLED_BACK_OLD_STATE_VERIFIED':'HOLD_ROLLBACK_INCOMPLETE';if($ok)$this->emit('OWNER_NEXT_STEPS',[$this->purgeStep()]);return 2;
            }
            if(!in_array($phase,['PREFLIGHT','HOLD_PREFLIGHT_BLOCKED'],true))throw new RuntimeException('UNKNOWN_SAVED_PHASE');
            $this->cleanupRecorded();$p=$this->preflight('fresh');
            if($p['blockers']!==[])return $this->hold2('HOLD_PREFLIGHT_BLOCKED',$p);
            $this->fault('AFTER_PREFLIGHT');
            // Before the first change (backup copies included): a clean artisan census, the time budget, the shop state as
            // recorded, the scheduled-backup window re-checked and no B1146 run. Any failure is a HOLD with nothing changed.
            try{$this->census();$this->beforeFirstChange();}catch(Throwable $e){$p['blockers']=[self::code($e)];return $this->hold2('HOLD_PREFLIGHT_BLOCKED',$p);}
            $this->backup($p);
            try{$this->install();$this->accept();}catch(Throwable $e){return $this->acceptanceFailed($e);}
            return $this->finish('PASS_INSTALLED_CATALOG_OVERLAY_SHOP_STATE_UNCHANGED');
        }catch(Throwable $e){$this->emit('HOLD_CODE_DETAIL',self::code($e));return 2;}
    }

    /**
     * An install or acceptance failure rolls back, except when the acceptance could not be judged (persistent 429, or the
     * shop state changed during the checks) while all 8 files hold exactly the payload: then the correct files stay, the run
     * HOLDs, and the same command later repeats only the acceptance (or --rollback restores the old files).
     */
    private function acceptanceFailed(Throwable $e):int
    {
        $code=self::code($e);$this->emit('INSTALL_OR_ACCEPTANCE_FAILED',$code);
        if(in_array($code,self::UNVERIFIED_CODES,true)&&($this->state['phase']??'')==='ACCEPTANCE_INTENT'){
            try{$exact=$this->targets()['installed']===true;}catch(Throwable){$exact=false;}
            // Everything that needs no throttled route and no particular shop state must still pass before the files stay.
            if($exact)try{$this->unthrottledChecks();}catch(Throwable $f){$exact=false;$code=self::code($f);$this->emit('UNTHROTTLED_CHECK_FAILED',$code);}
            if($exact){
                $this->state['unverifiedReason']=$code;$this->save('ACCEPTANCE_NOT_VERIFIED');$this->final='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED';
                $this->emit('HOLD_INSTALLED_NOT_VERIFIED',['reason'=>$code,'files'=>'ALL_8_HOLD_EXACTLY_THE_PAYLOAD','rollback'=>'NOT_STARTED_THE_FILES_ARE_CORRECT','shopSwitch'=>'NOT_WRITTEN']);
                $first=match($code){'SMOKES_RATE_LIMITED_429'=>'1_THE_SITE_ANSWERED_429_TO_THE_CHECKS_WAIT_A_FEW_MINUTES_(LOW_TRAFFIC)',
                    'NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES'=>'1_PUBLIC_PAGES_ARE_SERVED_WITHOUT_X-ROBOTS-TAG_NOINDEX_RESTORE_THE_HEADER_LINE_IN_apps/cms/current/public/.htaccess_OR_THE_CLOUDFLARE_RULE_THAT_REMOVES_IT_(THE_OWNER_POLICY_IS_NO_INDEXING)',
                    default=>'1_THE_SHOP_STATE_CHANGED_DURING_THE_CHECKS_TURN_THE_SHOP_ON_AGAIN_IN_THE_ADMIN'};
                $this->emit('OWNER_NEXT_STEPS',[$first,'2_THEN_RUN_THE_SAME_COMMAND_AGAIN_IT_REPEATS_ONLY_THE_ACCEPTANCE_CHECKS_NO_FILE_IS_CHANGED','3_OR_RESTORE_THE_OLD_FILES=bash /home/icaffeco/ald1n-project/incoming/b1147-catalog-overlay.sh --rollback',$this->purgeStep()]);
                return 2;
            }
        }
        $ok=$this->restore($code);$this->final=$ok?'HOLD_ROLLED_BACK_OLD_STATE_VERIFIED':'HOLD_ROLLBACK_INCOMPLETE';if($ok)$this->emit('OWNER_NEXT_STEPS',[$this->purgeStep()]);return 2;
    }

    /** The acceptance checks that need neither a throttled route nor the shop: fresh probe, routes, CLI classes, log, tree. */
    private function unthrottledChecks():void
    {
        $this->probeRun('unverified-fresh',true,false,true);$this->routesCheck($this->state['cache']['routeCacheFiles']);$this->catalogCheck(true);
        $scan=LeanGates::newLogErrors($this->active().'/storage/logs',$this->state['logBaseline']);$this->emit('LARAVEL_LOG_SINCE_INSTALL',$scan);if($scan['newErrorLines']!==0)throw new RuntimeException('NEW_LARAVEL_LOG_ERRORS_SINCE_INSTALL');
        $tree=LeanGates::verifyTree($this->active(),$this->installedManifest(),self::CONFIG_SHA,$this->state['chain']['htaccessSHA256'],self::INSTALLED_TREE_FILES);$this->emit('ACTIVE_TREE_INSTALLED_UNVERIFIED',$tree+['manifestPlusB1146PlusB1147'=>true]);
    }

    /**
     * ACCEPTANCE_NOT_VERIFIED: the same command repeats only the acceptance, after the same gates as an owner rollback
     * (no CMS artisan run, no backup running or due, no B1146 run) and with the shop ON as recorded. The log is judged from
     * a fresh baseline taken at the resume.
     */
    private function resumeAcceptance():int
    {
        $this->emit('RESUMED_ACCEPTANCE_ONLY',['reason'=>$this->state['unverifiedReason']??null]);$this->state['chain']??=$this->chain();
        try{
            $app=$this->helper($this->appStateScript,['state']);if(($app['backups']['table']??null)!==true||($app['backups']['running']??null)!==0)throw new RuntimeException('BACKUP_RUN_IN_PROGRESS_OR_TABLE_MISSING');
            $this->scheduleNeedSeconds=max($this->scheduleNeedSeconds,$this->requiredWindowSeconds($this->viewHold()));
            if($this->scheduleGate){$s=$this->scheduleState($app);if($s['conflict']!==null)throw new RuntimeException('SCHEDULED_BACKUP_WITHIN_THE_NEEDED_WINDOW_WAIT');}
            $this->b1146Idle();$this->census();$this->requireRecordedShop('BEFORE_RESUMED_ACCEPTANCE');
            $this->state['logBaseline']=LeanGates::logBaseline($this->active().'/storage/logs');$this->persist();
        }catch(Throwable $e){$this->emit('HOLD_ACCEPTANCE_NOT_RESUMED',['blockers'=>[self::code($e)],'filesChanged'=>'NONE','next'=>'TURN_THE_SHOP_ON_AS_BEFORE_OR_WAIT_FOR_THE_ARTISAN_RUN_THEN_RUN_THE_SAME_COMMAND_OR_USE_--rollback']);$this->final='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED';return 2;}
        if($this->targets()['installed']!==true){$ok=$this->restore('RESUMED_ACCEPTANCE_FILES_NOT_EXACT');$this->final=$ok?'HOLD_ROLLED_BACK_OLD_STATE_VERIFIED':'HOLD_ROLLBACK_INCOMPLETE';return 2;}
        try{$this->accept();}catch(Throwable $e){return $this->acceptanceFailed($e);}
        return $this->finish('PASS_INSTALLED_CATALOG_OVERLAY_SHOP_STATE_UNCHANGED');
    }
}

/** Same-directory temp + fsync + rename (replace) or temp + fsync + link (create, never clobbers). Mode and group are kept. */
final class CatalogFiles
{
    public static function tempFor(string $target):string {return dirname($target).'/.'.basename($target).'.b1147-'.bin2hex(random_bytes(8)).'.tmp';}
    public static function isOwnTemp(string $path):bool {return preg_match('~/\.[A-Za-z0-9_.-]+\.b1147-[a-f0-9]{16}\.tmp$~D',$path)===1;}

    public static function replaceable(string $target):array {return AtomicFile::replaceable($target);}

    private static function writeTemp(string $temp,string $bytes,int $mode):void
    {
        $h=@fopen($temp,'x');if($h===false)throw new RuntimeException('OVERLAY_TEMP_CREATE');$ok=false;
        try{if(!chmod($temp,$mode)||fwrite($h,$bytes)!==strlen($bytes)||!fflush($h)||(function_exists('fsync')&&!fsync($h)))throw new RuntimeException('OVERLAY_TEMP_WRITE');$ok=true;}finally{fclose($h);if(!$ok)@unlink($temp);}
        clearstatcache(true,$temp);if(is_link($temp)||hash_file('sha256',$temp)!==hash('sha256',$bytes)||(fileperms($temp)&0777)!==$mode){@unlink($temp);throw new RuntimeException('OVERLAY_TEMP_VERIFY');}
    }

    private static function syncDirectory(string $directory):bool {$d=@fopen($directory,'r');if($d===false)return false;$ok=function_exists('fsync')&&@fsync($d);fclose($d);return $ok;}

    /** Replaces $target only while it still holds $expectedSHA256; the result gets an mtime newer than the replaced file. */
    public static function replace(string $target,string $bytes,int $mode,int $gid,string $temp,string $expectedSHA256):array
    {
        if(!self::isOwnTemp($temp)||dirname($temp)!==dirname($target))throw new RuntimeException('OVERLAY_REPLACE_SCOPE');$facts=AtomicFile::replaceable($target);clearstatcache(true,$target);$before=stat($target);
        if(hash_file('sha256',$target)!==$expectedSHA256)throw new RuntimeException('OVERLAY_TARGET_CHANGED_BEFORE_RENAME');self::writeTemp($temp,$bytes,$mode);
        if(stat($temp)['gid']!==$gid)@chgrp($temp,$gid);clearstatcache(true,$temp);if(stat($temp)['gid']!==$gid){@unlink($temp);throw new RuntimeException('OVERLAY_GID_NOT_PRESERVABLE');}
        if(filemtime($temp)<=$before['mtime']&&!touch($temp,$before['mtime']+1)){@unlink($temp);throw new RuntimeException('OVERLAY_TEMP_MTIME');}
        if(hash_file('sha256',$target)!==$expectedSHA256){@unlink($temp);throw new RuntimeException('OVERLAY_TARGET_CHANGED_BEFORE_RENAME');}
        if(!@rename($temp,$target)){@unlink($temp);throw new RuntimeException('OVERLAY_RENAME');}$synced=self::syncDirectory(dirname($target));
        clearstatcache(true,$target);$after=stat($target);if(is_link($target)||hash_file('sha256',$target)!==hash('sha256',$bytes)||($after['mode']&0777)!==$mode||$after['gid']!==$gid)throw new RuntimeException('OVERLAY_RESULT_VERIFY');
        return ['op'=>'RENAME_OVER','directorySynced'=>$synced,'mode'=>sprintf('%04o',$mode),'gidPreserved'=>true,'nlinkBefore'=>$facts['nlink'],'aclFreeBefore'=>true,'mtimeAdvanced'=>$after['mtime']>$before['mtime']];
    }

    /** Creates $target from a synced temp with link(): it fails if the name exists, so nothing is ever clobbered. */
    public static function create(string $target,string $bytes,int $mode,string $temp,int $gid):array
    {
        if(!self::isOwnTemp($temp)||dirname($temp)!==dirname($target)||is_link(dirname($target)))throw new RuntimeException('OVERLAY_CREATE_SCOPE');clearstatcache(true,$target);if(file_exists($target)||is_link($target))throw new RuntimeException('OVERLAY_NEW_TARGET_EXISTS');
        self::writeTemp($temp,$bytes,$mode);if(stat($temp)['gid']!==$gid)@chgrp($temp,$gid);clearstatcache(true,$temp);if(stat($temp)['gid']!==$gid){@unlink($temp);throw new RuntimeException('OVERLAY_GID_NOT_PRESERVABLE');}if(!@link($temp,$target)){@unlink($temp);throw new RuntimeException(file_exists($target)?'OVERLAY_NEW_TARGET_EXISTS':'OVERLAY_LINK');}@unlink($temp);$synced=self::syncDirectory(dirname($target));
        clearstatcache(true,$target);if(is_link($target)||!is_file($target)||hash_file('sha256',$target)!==hash('sha256',$bytes)||stat($target)['nlink']!==1)throw new RuntimeException('OVERLAY_RESULT_VERIFY');
        return ['op'=>'LINK_NEW','directorySynced'=>$synced,'mode'=>sprintf('%04o',$mode),'gid'=>$gid];
    }

    /** Removes $target only while it holds exactly $sha256. */
    public static function removeExact(string $target,string $sha256):void
    {
        clearstatcache(true,$target);if(is_link($target)||!is_file($target)||hash_file('sha256',$target)!==$sha256)throw new RuntimeException('OVERLAY_REMOVE_FOREIGN_CONTENT');if(!unlink($target))throw new RuntimeException('OVERLAY_REMOVE');self::syncDirectory(dirname($target));
    }
}
