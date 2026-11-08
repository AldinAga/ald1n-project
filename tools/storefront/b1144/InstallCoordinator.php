<?php
declare(strict_types=1);

/**
 * B1144 lean controlled OFF install. No migrations, environment edits, PHP profile overlays, cPanel calls or public activation.
 * Order: admission -> filesystem scope -> mtime diagnostic -> PRE-BARRIER HOST PROBE of the old live tree (every fixed web
 * gate, hold bound, drain positive control, daily log gate; collect-all, zero downtime on HOLD) -> owned barrier intent ->
 * candidate barrier -> scheduler pause -> active barrier -> narrow drain -> local SHA-256 verification -> journaled switch ->
 * web freshness -> CLI boot -> probe-derived barrier hold -> lift -> smokes and log scan -> commit. Any failure after the
 * switch rolls back automatically. The rollback directory rename needs the journal; probe cleanup, state saves, barrier
 * steps, scheduler verify and drain before it are reported steps whose failure never stops it. The restored tree gets its
 * own freshness probe and hold; only when the probe could not report (reachability, realpath ini, hold bound) a bounded
 * fallback (pre-switch values, private /login re-check) may lift; otherwise the barrier is retained for the operator.
 */
final class InstallCoordinator
{
    private const SOURCE='ef3f927b77ba573881c2b6dba341732c417e6725';
    private const MAIN='81e583a78c73745fd2e0d73f7415e4842ad89cf1';
    private const CMS_TREE='da8ac0c953e3c6741b921bb9fa660f80d247c55e';
    public const PENDING=['2026_10_23_020000_create_public_order_accesses','2026_10_23_020100_create_public_checkout_attempts','2026_10_24_030000_add_retail_customer_role'];
    public const SMOKES=[['/login',200],['/up',200],['/katalog',404],['/registracija',404],['/korpa',404]];
    /** Terminal-for-this-run phases that a recovery HOLD must not overwrite: no owned barrier is retained in them, or it is retained on purpose. */
    private const KEEP_PHASES=['ORIGINAL_RELEASE_AVAILABILITY_HOLD','ORIGINAL_RELEASED_SCHEDULER_PENDING','RESTORED_BARRIER_RETAINED_OPERATOR_LIFT'];
    /** Phases after the restored (or untouched) original code passed its hold: a re-run continues the release and never re-imposes a barrier. */
    private const RELEASE_PHASES=['ORIGINAL_RELEASE_INTENT','ORIGINAL_RELEASED_PENDING_HTTP','ORIGINAL_RELEASE_AVAILABILITY_HOLD'];
    /** Restored-probe failures for which the conservative pre-switch fallback hold is allowed: the probe could not report, not "reported stale". */
    private const FALLBACK_CODES=['FRESHNESS_PROBE_HTTP_UNAVAILABLE','FRESHNESS_REALPATH_CACHE_UNREADABLE','BARRIER_HOLD_EXCEEDS_BOUND'];
    private array $state=[];
    private string $pointer;
    private mixed $lock;
    private object $web;
    private array $manifest;
    private array $directoryDocument;
    private string $php=PHP_BINARY;
    private int $drainSeconds=90;
    private array $allowedSapis=['litespeed'];
    private int $probeSamples=3;
    private int $controlRequests=5;

    public function __construct(private string $root,private string $home)
    {
        if($root!=='/home/icaffeco/ald1n-project'||$home!=='/home/icaffeco'||realpath($root)!==$root||realpath($home)!==$home||!function_exists('posix_geteuid')||!extension_loaded('curl')||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('HOST_PROFILE');
        $private=$home.'/.ald1n-b1144-state';if(!is_dir($private)&&!mkdir($private,0700))throw new RuntimeException('PRIVATE_STATE_CREATE');
        if(is_link($private)||realpath($private)!==$private||(fileperms($private)&0777)!==0700||fileowner($private)!==posix_geteuid())throw new RuntimeException('PRIVATE_STATE_CONFINEMENT');
        $this->lock=fopen($private.'/installer.lock','c');if($this->lock===false||!flock($this->lock,LOCK_EX|LOCK_NB))throw new RuntimeException('ANOTHER_INSTALLER_ACTIVE');chmod($private.'/installer.lock',0600);$this->pointer=$private.'/current.json';
        putenv('GIT_OPTIONAL_LOCKS=0');$this->web=new LeanWeb();
    }

    private function emit(string $key,mixed $value):void {echo $key.'='.json_encode($value,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;}
    private static function code(Throwable $error):string {$code=$error->getMessage();return preg_match('/^[A-Z0-9_]+$/D',$code)?$code:'UNEXPECTED_'.strtoupper((new ReflectionClass($error))->getShortName());}
    private function identity(string $path):array {clearstatcache(true);$s=stat($path);if(!is_array($s)||is_link($path)||realpath($path)!==$path)throw new RuntimeException('PATH_IDENTITY');return ['dev'=>$s['dev'],'ino'=>$s['ino']];}
    private function active():string {return $this->root.'/apps/cms/current';}
    private function marker():string {return bin2hex(random_bytes(8));}

    private function atomicJSON(string $path,array $data):void
    {
        if(realpath(dirname($path))!==dirname($path)||is_link($path))throw new RuntimeException('STATE_CONFINEMENT');$temporary=$path.'.'.bin2hex(random_bytes(8)).'.next';$handle=@fopen($temporary,'x');if($handle===false)throw new RuntimeException('STATE_CREATE');chmod($temporary,0600);$text=json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);if(fwrite($handle,$text)!==strlen($text))throw new RuntimeException('STATE_WRITE');fflush($handle);if(function_exists('fsync'))fsync($handle);fclose($handle);if(!rename($temporary,$path))throw new RuntimeException('STATE_RENAME');
    }

    /** The in-memory phase changes only once state.json holds it, so the in-memory phase is always the persisted one. */
    private function save(string $phase):void {$next=$this->state;$next['phase']=$phase;$this->atomicJSON($this->state['private'].'/state.json',$next);$this->state['phase']=$phase;$this->atomicJSON($this->pointer,['schemaVersion'=>1,'private'=>$this->state['private'],'id'=>$this->state['id']]);$this->emit('RELEASE_PHASE',$phase);}

    /** Phase as state.json holds it (the in-memory copy when the file cannot be read). */
    private function persistedPhase():string
    {
        try{$path=$this->state['private'].'/state.json';$saved=json_decode((string)@file_get_contents($path),true,512,JSON_THROW_ON_ERROR);if(is_array($saved)&&is_string($saved['phase']??null))return $saved['phase'];}catch(Throwable){}
        return (string)($this->state['phase']??'');
    }

    /** No raw application output leaves the host process; report only audited command identity and real RC. */
    private function command(array $argv,?string $cwd=null,int $timeout=120,?array $env=null):array
    {
        $pipes=[];$process=proc_open($argv,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd,$env);if(!is_resource($process))throw new RuntimeException('CHILD_START');fclose($pipes[0]);stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$stdout='';$stderrBytes=0;$started=microtime(true);$last=null;
        do{$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);$stdout.=$out;$stderrBytes+=strlen($err);if(strlen($stdout)>4194304||$stderrBytes>4194304){proc_terminate($process);throw new RuntimeException('CHILD_OUTPUT_BOUND');}$last=proc_get_status($process);if(microtime(true)-$started>$timeout){proc_terminate($process);throw new RuntimeException('CHILD_TIMEOUT');}if($last['running'])usleep(100000);}while($last['running']);
        $stdout.=stream_get_contents($pipes[1]);$stderrBytes+=strlen(stream_get_contents($pipes[2]));fclose($pipes[1]);fclose($pipes[2]);$closed=proc_close($process);$rc=$last['exitcode']>=0?$last['exitcode']:$closed;return ['rc'=>$rc,'stdout'=>$stdout,'stderrBytes'=>$stderrBytes];
    }

    private function artisan(array $arguments,int $timeout):array
    {
        return $this->command(array_merge([$this->php,'-d','opcache.enable=0','-d','opcache.enable_cli=0',$this->active().'/artisan'],$arguments,['--no-interaction','--no-ansi']),$this->active(),$timeout,array_merge(getenv(),['COLUMNS'=>'240']));
    }

    private function git(array $arguments):string {$r=$this->command(array_merge(['git','-C',$this->root],$arguments),null,30);if($r['rc']!==0)throw new RuntimeException('GIT_READ_FAILED');return trim($r['stdout']);}
    private function gitSnapshot():array {$index=$this->git(['rev-parse','--git-path','index']);if(!str_starts_with($index,'/'))$index=$this->root.'/'.$index;return ['head'=>$this->git(['rev-parse','HEAD']),'branch'=>$this->git(['rev-parse','--abbrev-ref','HEAD']),'index'=>$index,'indexSHA256'=>hash_file('sha256',$index)];}
    private function assertGit():void {$now=$this->gitSnapshot();$old=$this->state['git'];if($now['head']!==$old['head']||$now['indexSHA256']!==$old['indexSHA256'])throw new RuntimeException('HOST_HEAD_OR_INDEX_CHANGED');}

    private function scheduler(string $action):array
    {
        $old=is_dir($this->state['plan']['rollback'])?$this->state['plan']['rollback']:$this->active();
        if($this->identity($old)!==$this->state['plan']['oldIdentity'])throw new RuntimeException('SCHEDULER_OLD_RELEASE_IDENTITY');
        $path=$this->state['private'].'/scheduler-command.json';$this->atomicJSON($path,['base'=>$old,'action'=>$action,'owner'=>$this->state['owner'],'identity'=>$this->state['plan']['oldIdentity'],'envSHA256'=>$this->state['envSHA256'],'previous'=>$this->state['scheduler']??null]);
        $result=$this->command([$this->php,'-d','opcache.enable=0','-d','opcache.enable_cli=0',$this->root.'/incoming/b1134-scheduler.php',$path],null,120);$receipt=json_decode($result['stdout'],true);
        if($result['rc']!==0||!is_array($receipt)||($receipt['status']??'')!=='ok')throw new RuntimeException('SCHEDULER_'.strtoupper($action).'_HOLD');
        $this->emit('TECHNICAL_SCHEDULER_CACHE',['action'=>$action,'rc'=>$result['rc'],'oldApplicationBootstrap'=>true,'directBusinessOrSchemaQueries'=>false]);return $receipt['receipt'];
    }

    private function loadManifest():void
    {
        $verification=$this->home.'/.ald1n-stage/b1131-ef3f927.6hh7mU/verification';$path=$verification.'/runtime-manifest.json';if(is_link($path)||realpath($path)!==$path||hash_file('sha256',$path)!=='d737387e60a981b3b404e0980d9b70cb95b6e1d506a39211981d49dcd12be4c6')throw new RuntimeException('RUNTIME_MANIFEST_INTEGRITY');
        $this->manifest=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$directories=$verification.'/runtime-directories.json';
        if(is_link($directories)||realpath($directories)!==$directories||hash_file('sha256',$directories)!=='1e790c20fb9712d416399975d6cbcce3be1bd2dc12ede59a95f2e1cc75ea7e3e')throw new RuntimeException('DIRECTORY_MANIFEST_INTEGRITY');$this->directoryDocument=json_decode(file_get_contents($directories),true,512,JSON_THROW_ON_ERROR);
    }

    /**
     * Admission, filesystem scope and the mtime diagnostic only read. B1144Recovery::commit then writes the new private
     * B1144 state namespace (archived predecessor bytes, journal, state); nothing in the application trees changes here.
     */
    private function prepare():void
    {
        if($this->git(['rev-parse','origin/main:apps/cms/current'])!==self::CMS_TREE)throw new RuntimeException('FRESH_MAIN_CMS_DRIFT');
        $git=$this->gitSnapshot();$admission=B1144Recovery::admit($this->root,$this->home,$this->manifest,$git);$plan=$admission['state']['plan'];
        $scope=LeanGates::scope($this->root,[$plan['active'],$plan['candidate']]);$diagnostic=LeanGates::mtimeDiagnostic($plan['active'],$plan['candidate'],$this->manifest);
        $this->state=B1144Recovery::commit($this->home,$admission,$git);$this->state['scope']=$scope;$this->state['probeFiles']=$diagnostic['probeFiles'];$this->state['probes']=[];$this->save('PREPARED');
        $summary=$diagnostic;$summary['probeFiles']=array_keys($diagnostic['probeFiles']);$this->emit('CMS_DIRECTORY_SCOPE',$scope);$this->emit('MTIME_DIAGNOSTIC',$summary);
        $this->emit('B1144_PREDECESSOR_RECOVERY',$this->state['predecessor']);$this->emit('EXISTING_COMPLETE_CANDIDATE_REUSED_NO_VENDOR_RECOPY',true);$this->emit('HOST_GIT',$this->state['git']);
    }

    private function existingMaintenance(array $receipt,string $storage):?array
    {
        $identity=$this->identity($storage);if($identity['dev']!==$receipt['storageDevice']||$identity['ino']!==$receipt['storageInode'])throw new RuntimeException('MAINTENANCE_STORAGE_IDENTITY');$files=[];
        foreach($receipt['files'] as $original=>$digest){$path=$storage.'/framework/'.basename($original);if(!file_exists($path)&&!is_link($path))continue;if(is_link($path)||realpath($path)!==$path||hash_file('sha256',$path)!==$digest)throw new RuntimeException('MAINTENANCE_FOREIGN_CONTENT');$files[$path]=$digest;}
        if(!$files)return null;$receipt['files']=$files;$receipt['storage']=$storage;return $receipt;
    }

    private function clearMaintenance(array $receipt,string $storage):void {$present=$this->existingMaintenance($receipt,$storage);if($present!==null)ReleasePrimitives::releaseMaintenance($present,$storage);}

    /** Re-imposes exactly the recorded barrier (same owner, start and expiry give the same digests). */
    private function ensureBarrier(array $receipt,string $storage):void
    {
        $present=$this->existingMaintenance($receipt,$storage);if($present!==null&&count($present['files'])===count($receipt['files']))return;if($present!==null)ReleasePrimitives::releaseMaintenance($present,$storage);
        $actual=ReleasePrimitives::ownMaintenance($storage,$this->state['owner'],$this->state['maintenanceExpires'],$this->state['maintenanceStart'],null);if(array_values($actual['files'])!==array_values($receipt['files']))throw new RuntimeException('BARRIER_REIMPOSE_MISMATCH');
        $this->emit('OWNED_BARRIER_REIMPOSED',['tree'=>$storage===$this->active().'/storage'?'ACTIVE':'INACTIVE']);
    }

    /**
     * Both barrier receipts are derived BEFORE any barrier exists, from a private model (same owner, start and expiry give
     * the same digests), and saved with NEW_MAINTENANCE_INTENT: a crash right after a barrier is created leaves no orphan.
     */
    private function planBarriers():void
    {
        $model=$this->state['private'].'/barrier-model';foreach([$model,$model.'/storage',$model.'/storage/framework'] as $directory)if(!is_dir($directory)&&!mkdir($directory,0700))throw new RuntimeException('BARRIER_MODEL_CREATE');
        $m=ReleasePrimitives::ownMaintenance($model.'/storage',$this->state['owner'],$this->state['maintenanceExpires'],$this->state['maintenanceStart'],null);ReleasePrimitives::releaseMaintenance($m,$model.'/storage');
        $receipt=function(string $storage)use($m):array{$r=$m;$r['storage']=$storage;$identity=$this->identity($storage);$r['storageDevice']=$identity['dev'];$r['storageInode']=$identity['ino'];$r['files']=[];foreach($m['files'] as $path=>$digest)$r['files'][$storage.'/framework/'.basename($path)]=$digest;return $r;};
        $this->state['newMaintenance']=$receipt($this->state['plan']['candidate'].'/storage');$this->state['oldMaintenance']=$receipt($this->active().'/storage');
    }

    private function warmup():void
    {
        foreach(['package:discover','view:cache'] as $command){$r=$this->artisan([$command],180);$this->emit('TECHNICAL_CACHE_WARMUP',['command'=>$command,'rc'=>$r['rc'],'outputBytes'=>strlen($r['stdout']),'stderrBytes'=>$r['stderrBytes'],'rawOutputNotExported'=>true]);if($r['rc']!==0)throw new RuntimeException('CANDIDATE_TECHNICAL_WARMUP_FAILED');}
        if(file_exists($this->active().'/bootstrap/cache/config.php'))throw new RuntimeException('UNEXPECTED_CONFIG_CACHE_CREATED');
    }

    private function verifyInstalledFiles():void
    {
        foreach($this->manifest['files'] as $file){$relative=$file['path'];$expected=$relative==='config/public_storefront.php'?$this->state['clampedConfigSHA256']:($relative==='public/.htaccess'?$this->state['preserved']['customHtaccessSHA256']:$file['sha256']);$path=$this->active().'/'.$relative;if(is_link($path)||realpath($path)!==$path||!is_file($path)||hash_file('sha256',$path)!==$expected)throw new RuntimeException('INSTALLED_RUNTIME_FILE_MISMATCH');}
        if(hash_file('sha256',$this->active().'/.env')!==$this->state['envSHA256'])throw new RuntimeException('PRIVATE_ENV_CHANGED');$this->assertGit();
    }

    private function probeRoots():array {$p=$this->state['plan'];return [$p['active'],$p['candidate'],$p['rollback'],$p['failed']];}

    /** Narrow drain: this CMS's lsphp titles and artisan processes whose arguments or working directory are in a CMS release root. */
    private function drain():array {$result=LeanGates::drain($this->active().'/public/index.php',$this->probeRoots(),$this->drainSeconds);$this->emit('CMS_DRAIN',$result);return $result;}

    /** Every recorded probe (and its private staging file) is removed from wherever it is; foreign bytes at a recorded public name are a HOLD. */
    private function removeRecordedProbes():void
    {
        $changed=false;
        foreach($this->state['probes']??[] as $index=>$probe){
            if(($probe['removed']??false)===true)continue;if(is_string($probe['staging']??null))FreshnessProbe::removeStaging($probe['staging']);
            foreach($this->probeRoots() as $base)if(is_dir($base.'/public'))FreshnessProbe::remove($base.'/public',$probe['name'],$probe['sha256']);$this->state['probes'][$index]['removed']=true;$changed=true;
        }
        if($changed)$this->atomicJSON($this->state['private'].'/state.json',$this->state);
    }

    private function expected(string $which):array {$expected=[];foreach($this->state['probeFiles'] as $relative=>$digests)$expected[$relative]=$digests[$which];return $expected;}

    /** Records the probe intent (name, digest, staging path) before writing it, writes it crash-safely, asks it, and always removes it. */
    private function probeRequests(string $label,array $expected,bool $invalidate,?string $route):array
    {
        $public=$this->active().'/public';$this->removeRecordedProbes();FreshnessProbe::assertNoLeftover($public);
        $probe=FreshnessProbe::plan($this->state['id'],$this->probeRoots(),array_keys($expected));$staging=$this->state['private'].'/probe-'.bin2hex(random_bytes(8)).'.staging';
        $this->state['probes'][]=['name'=>$probe['name'],'sha256'=>$probe['sha256'],'label'=>$label,'staging'=>$staging];$this->save('FRESHNESS_PROBE_INTENT_'.strtoupper($label));$responses=[];
        try{FreshnessProbe::write($public.'/'.$probe['name'],$probe['source'],$staging);for($n=0;$n<$this->probeSamples;$n++)$responses[]=$this->web->probe($probe['name'],$probe['token'],$invalidate,$route);}
        finally{$this->removeRecordedProbes();}
        return $responses;
    }

    private function markSwap(string $label,bool $now):void
    {
        if($now||!is_int($this->state['swappedAt'][$label]??null)){$this->state['swappedAt'][$label]=time();$this->state['swappedMono'][$label]=FreshnessProbe::monotonic();}
    }

    /** Drain positive control: GET /login on the live old tree while sampling ps; this CMS's busy title must be seen at least once. */
    private function drainPositiveControl():array
    {
        $index=$this->active().'/public/index.php';$snapshots=[];$statuses=[];$observation=LeanGates::titleObservation($index,[]);
        for($n=0;$n<$this->controlRequests;$n++){
            $r=$this->web->sampled('/login',static function()use(&$snapshots):void{$snapshots[]=LeanGates::processes();},$this->marker());$statuses[]=$r['transport']?'TRANSPORT':$r['status'];
            $observation=LeanGates::titleObservation($index,$snapshots);if($observation['observed'])break;
        }
        return $observation+['requests'=>count($statuses),'loginStatuses'=>$statuses];
    }

    /**
     * PRE-BARRIER HOST PROBE against the OLD live tree, over the route the installer will use later. It requests the same
     * targeted invalidation the post-switch probes use (per file, only cached scripts under the CMS release trees, never a
     * reset), so an edge that strips the invalidation header or a failing opcache_invalidate is seen BEFORE any barrier.
     * Also an artisan census of THIS CMS (counts only). Collect-all: every gate result and host-derived value is printed
     * even when some fail; then HOLD with zero downtime, since no barrier exists yet. The probe file is removed on every path.
     */
    private function hostPreflight():void
    {
        $expected=$this->expected('old');$gates=[];$values=null;$routes=[];$sapis=[];$reach=null;$invalidation=['requested'=>true,'reportedRequested'=>[],'candidates'=>0,'invalidated'=>0,'failed'=>0,'vanished'=>0];
        try{
            foreach($this->probeRequests('preflight',$expected,true,null) as $response){
                $routes[]=is_string($response['_route']??null)?$response['_route']:'UNKNOWN';unset($response['_route']);$a=FreshnessProbe::assess($response,$this->state['id'],$expected,$this->allowedSapis,true);
                $invalidation['reportedRequested'][]=($response['invalidationRequested']??null)===true;foreach(['candidates'=>'invalidationCandidates','invalidated'=>'invalidated','failed'=>'invalidationFailed','vanished'=>'vanishedCachedScripts'] as $key=>$field)if(is_int($response[$field]??null))$invalidation[$key]+=$response[$field];
                foreach($a['gates'] as $gate=>$code)if(!array_key_exists($gate,$gates)||$gates[$gate]===null)$gates[$gate]=$code;$sapis[]=$a['values']['sapi'];
                if($values===null)$values=$a['values'];else foreach(['realpathCacheTtl','revalidateFreq','holdSeconds'] as $key)if(is_int($a['values'][$key])&&(!is_int($values[$key])||$a['values'][$key]>$values[$key]))$values[$key]=$a['values'][$key];
            }
        }catch(Throwable $error){$reach=self::code($error);}
        $gates=['probeReachable'=>$reach]+($gates===[]?array_fill_keys(['scope','sapi','php84','docroot','treeContent','opcacheStatus','scriptsListed','fields','targetedInvalidation','revalidateFreq','validateTimestamps','realpathIni','holdBound'],'NOT_EVALUATED_PROBE_UNREACHABLE'):$gates);
        $route=in_array('PUBLIC_TLS_HOSTNAME',$routes,true)?'PUBLIC_TLS_HOSTNAME':($routes[0]??null);
        try{$control=$this->drainPositiveControl();$gates['drainPositiveControl']=$control['observed']?null:'DRAIN_POSITIVE_CONTROL_NOT_OBSERVED';$gates['liveLogin200']=in_array(200,$control['loginStatuses'],true)?null:'PRE_SWITCH_LOGIN_NOT_200';}
        catch(Throwable $error){$control=['error'=>self::code($error)];$gates['drainPositiveControl']=self::code($error);$gates['liveLogin200']='NOT_EVALUATED';}
        $logs=LeanGates::dailyLogs($this->active().'/storage/logs');$gates['dailyLaravelLog']=$logs['dailyFiles']>0?null:'LOG_GATE_NO_DAILY_LARAVEL_LOG';
        // Artisan census of THIS CMS before any barrier: a path argument or working directory in a CMS release root (unreadable cwd counts); lsphp request titles and other sites are ignored.
        try{$census=LeanGates::busy($this->active().'/public/index.php',LeanGates::processes(),getmypid(),$this->probeRoots());$artisan=['artisan'=>$census['artisan'],'otherArtisanIgnored'=>$census['otherArtisanIgnored'],'lsphpRequestTitlesIgnored'=>true];$gates['artisanCensus']=$census['artisan']>0?'PRE_SWITCH_CMS_ARTISAN_RUNNING':null;}
        catch(Throwable $error){$artisan=['error'=>self::code($error)];$gates['artisanCensus']=self::code($error);}
        $blockers=array_values(array_unique(array_filter($gates,static fn($code)=>$code!==null)));
        $report=['gates'=>array_map(static fn($code)=>$code??'PASS',$gates),'values'=>$values,'reachability'=>['route'=>$route,'samples'=>count($routes),'routes'=>$routes,'sapis'=>array_values(array_unique($sapis))],'drainPositiveControl'=>$control,'artisanCensus'=>$artisan,'dailyLog'=>$logs+['explanation'=>'LOG_CHANNEL=daily writes storage/logs/laravel-YYYY-MM-DD.log; without one the post-switch log gate could pass vacuously'],'invalidationRequested'=>true,'invalidation'=>$invalidation,'barrier'=>'NONE_YET','blockers'=>$blockers];
        $this->state['hostPreflight']=['route'=>$route,'values'=>$values,'blockers'=>$blockers];$this->emit('HOST_PREFLIGHT',$report);
        if($blockers!==[]){$this->save('HOST_PREFLIGHT_BLOCKED');throw new RuntimeException('HOST_PREFLIGHT_BLOCKED');}
        $this->save('HOST_PREFLIGHT_PASSED');
    }

    /** Web freshness for the tree now at the active path. The probe name and token never reach output or the report. */
    private function freshness(string $label):array
    {
        $swappedAt=$this->state['swappedAt'][$label]??null;if(!is_int($swappedAt))throw new RuntimeException('BARRIER_HOLD_SWAP_TIME_UNKNOWN');
        $expected=$this->expected($label==='installed'?'new':'old');$samples=[];
        foreach($this->probeRequests($label,$expected,true,$this->state['hostPreflight']['route']??null) as $response){
            $route=is_string($response['_route']??null)?$response['_route']:'UNKNOWN';unset($response['_route']);
            $summary=FreshnessProbe::validate($response,$this->state['id'],$expected,$this->allowedSapis,true);$summary['route']=$route;$samples[]=$summary;
        }
        // The barrier stays until every pre-swap realpath entry of every worker has expired and OPcache has revalidated.
        $probedAt=time();$liftNotBefore=FreshnessProbe::liftNotBefore($swappedAt,$probedAt,$samples);
        $hold=['swappedAt'=>$swappedAt,'probedAt'=>$probedAt,'liftNotBefore'=>$liftNotBefore,'requiredSecondsAfterSwap'=>$liftNotBefore-$swappedAt,'realpathCacheTtl'=>max(array_column($samples,'realpathCacheTtl')),'realpathCacheSize'=>$samples[0]['realpathCacheSize'],'revalidateFreq'=>max(array_column($samples,'revalidateFreq')),'marginSeconds'=>FreshnessProbe::HOLD_MARGIN,'maxSeconds'=>FreshnessProbe::MAX_HOLD_SECONDS];
        $result=['label'=>$label,'samples'=>$samples,'hold'=>$hold,'targetedInvalidationOnly'=>true,'globalReset'=>false,'probeRemoved'=>true];
        $this->state['freshness'][$label]=$result;$this->emit('WEB_FRESHNESS',$result);
        try{FreshnessProbe::remaining($liftNotBefore);}catch(RuntimeException $error){$this->emit('BARRIER_HOLD_BOUND',['label'=>$label,'remainingSeconds'=>$liftNotBefore-time(),'maxSeconds'=>FreshnessProbe::MAX_HOLD_SECONDS]);throw $error;}
        return $result;
    }

    /** Keeps the owned barrier up to the derived lift time of this swap, on the monotonic clock, with a heartbeat every 15 s. */
    private function holdBarrier(string $label):void
    {
        $hold=$this->state['freshness'][$label]['hold']??null;if(!is_array($hold)||!is_int($hold['liftNotBefore']??null))throw new RuntimeException('BARRIER_HOLD_UNDERIVABLE');
        $this->emit('BARRIER_HOLD_START',['label'=>$label,'remainingSeconds'=>FreshnessProbe::remaining($hold['liftNotBefore']),'requiredSecondsAfterSwap'=>$hold['requiredSecondsAfterSwap'],'realpathCacheTtl'=>$hold['realpathCacheTtl'],'revalidateFreq'=>$hold['revalidateFreq'],'heartbeatSeconds'=>15]);
        $swap=$this->state['swappedMono'][$label]??null;$monotonic=is_array($swap)?['ns'=>$swap['ns']??null,'boot'=>$swap['boot']??null,'requiredSeconds'=>$hold['liftNotBefore']-$hold['swappedAt']]:null;
        $receipt=FreshnessProbe::hold($hold['liftNotBefore'],$monotonic,$label);foreach(['waitedSeconds','heldUntil','clock'] as $key)$this->state['freshness'][$label]['hold'][$key]=$receipt[$key];
        $this->emit('BARRIER_HOLD',['label'=>$label]+$this->state['freshness'][$label]['hold']);
    }

    /** Read-only CLI boot of the installed tree. migrate:status reads the migrations table; migrate is never run. */
    private function cliBoot():void
    {
        $about=$this->artisan(['about'],120);$this->emit('CLI_BOOT_ABOUT',['rc'=>$about['rc'],'outputBytes'=>strlen($about['stdout']),'stderrBytes'=>$about['stderrBytes'],'rawOutputNotExported'=>true]);if($about['rc']!==0)throw new RuntimeException('CLI_ABOUT_FAILED');
        $status=$this->artisan(['migrate:status'],120);if($status['rc']!==0){$this->emit('MIGRATE_STATUS_READ_ONLY',['rc'=>$status['rc'],'migrateExecuted'=>false]);throw new RuntimeException('MIGRATE_STATUS_FAILED');}
        $parsed=LeanGates::migrationStatus($status['stdout'],LeanGates::migrationFiles($this->active().'/database/migrations'));
        $this->emit('MIGRATE_STATUS_READ_ONLY',['rc'=>0,'ran'=>$parsed['ran'],'pending'=>$parsed['pending'],'migrateExecuted'=>false]);if($parsed['pending']!==self::PENDING)throw new RuntimeException('MIGRATION_PENDING_SET_MISMATCH');
        $this->emit('CANONICAL_STATIC_CHECK','NOT_RUN_NOT_AN_EXISTING_INSTALL_GATE');$this->state['cliBoot']=['aboutRC'=>0,'ran'=>$parsed['ran'],'pending'=>$parsed['pending']];
    }

    private function postLiftSmoke():void
    {
        $marker=$this->marker();foreach(self::SMOKES as [$path,$expected])$this->web->status($path,$expected,null,$marker);
        $scan=LeanGates::newLogErrors($this->active().'/storage/logs',$this->state['logBaseline']);$this->state['logScan']=$scan;$this->emit('LARAVEL_LOG_SINCE_SWITCH',$scan);
        if($scan['newErrorLines']!==0)throw new RuntimeException('NEW_LARAVEL_LOG_ERRORS_SINCE_SWITCH');
    }

    private function loadExisting():bool
    {
        if(!file_exists($this->pointer))return false;if(is_link($this->pointer)||filesize($this->pointer)>8192)throw new RuntimeException('PENDING_POINTER');$pointer=json_decode(file_get_contents($this->pointer),true,32,JSON_THROW_ON_ERROR);if(!preg_match('/^[a-f0-9]{32}$/D',$pointer['id']??'')||$pointer['private']!==$this->home.'/.ald1n-b1144-state/'.$pointer['id'])throw new RuntimeException('PENDING_POINTER_SCOPE');$path=$pointer['private'].'/state.json';if(is_link($path)||realpath($path)!==$path||filesize($path)>16777216)throw new RuntimeException('PENDING_STATE');$this->state=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$first=json_decode(explode("\n",file_get_contents($this->state['journal']))[0],true,512,JSON_THROW_ON_ERROR);if(($this->state['schemaVersion']??0)!==1||$first['owner']!==$this->state['owner']||$first['plan']!==$this->state['plan'])throw new RuntimeException('JOURNAL_STATE_BINDING');return true;
    }

    public function run():int
    {
        $this->loadManifest();
        if($this->loadExisting()){
            if($this->state['phase']==='CLOSED_INSTALL'){$this->verifyInstalledFiles();$this->emit('ALREADY_INSTALLED_OFF_NO_REPEAT',self::MAIN);$this->emit('PERMANENT_PRIVATE_DATA_DEPENDENCY_DO_NOT_PRUNE',$this->state['plan']['rollback']);return 0;}
            if(in_array($this->state['phase'],['COMMITTED_OFF','PUBLIC_RELEASED','SCHEDULER_RESTORED'],true))return $this->finishCommitted();
            if(in_array($this->state['phase'],['ROLLED_BACK','ROLLED_BACK_PRE_SWITCH'],true)){$this->emit('PRIOR_ATTEMPT_ROLLED_BACK_NO_AUTOMATIC_REDEPLOY',true);return 2;}
            if($this->state['phase']==='ORIGINAL_RELEASED_SCHEDULER_PENDING')return $this->resumeSchedulerRelease();
            if(in_array($this->state['phase'],self::RELEASE_PHASES,true)&&is_array($this->state['originalRelease']??null))return $this->resumeOriginalRelease();
            $this->emit('RECOVERY_OF_EXISTING_TRANSACTION',$this->state['id']);return $this->recover('OPERATOR_RESUMED_INTERRUPTED_INSTALL');
        }
        try{$this->prepare();}catch(Throwable $error){
            if(!$this->state){$this->emit('HOLD_BEFORE_LIVE_APPLICATION_MUTATION',self::code($error));return 2;}
            return $this->recover(self::code($error));
        }
        return $this->execute();
    }

    private function execute():int
    {
        try{
            $plan=$this->state['plan'];$active=$this->active();
            $this->hostPreflight();
            $this->planBarriers();$this->save('NEW_MAINTENANCE_INTENT');
            $new=ReleasePrimitives::ownMaintenance($plan['candidate'].'/storage',$this->state['owner'],$this->state['maintenanceExpires'],$this->state['maintenanceStart'],null);if($new['files']!==$this->state['newMaintenance']['files'])throw new RuntimeException('MAINTENANCE_PLAN_MISMATCH');$this->save('CANDIDATE_READY');
            $this->state['schedulerPauseAttempted']=true;$this->save('SCHEDULER_PAUSE_INTENT');$this->state['scheduler']=$this->scheduler('pause');$this->save('SCHEDULER_PAUSED');
            $this->save('MAINTENANCE_INTENT');$actual=ReleasePrimitives::ownMaintenance($active.'/storage',$this->state['owner'],$this->state['maintenanceExpires'],$this->state['maintenanceStart'],null);if($actual['files']!==$this->state['oldMaintenance']['files'])throw new RuntimeException('MAINTENANCE_PLAN_MISMATCH');$this->save('MAINTENANCE_OWNED');
            $this->web->status('/login',503,$this->state['oldMaintenance']['ownerSHA256'],$this->marker(),10);$this->scheduler('verify');$this->save('BARRIER_OBSERVED');
            $this->state['drain']=$this->drain();$this->save('CMS_DRAINED');
            $this->state['treeVerification']=LeanGates::verifyTree($plan['candidate'],$this->manifest,$this->state['clampedConfigSHA256'],$this->state['preserved']['customHtaccessSHA256'],count($this->manifest['files']));
            if(hash_file('sha256',$active.'/.env')!==$this->state['envSHA256'])throw new RuntimeException('PRIVATE_ENV_CHANGED');$this->assertGit();$this->emit('CANDIDATE_LOCAL_SHA256',$this->state['treeVerification']);$this->save('CANDIDATE_LOCAL_VERIFIED');
            $this->state['logBaseline']=LeanGates::logBaseline($active.'/storage/logs');$this->save('LOG_BASELINE_RECORDED');
            ReleasePrimitives::switchJournaled($active,$plan['candidate'],$plan['rollback'],$this->state['journal'],$this->state['owner']);$this->markSwap('installed',true);$this->save('SWITCHED');$this->warmup();$this->verifyInstalledFiles();
            $this->freshness('installed');$this->save('FRESHNESS_VERIFIED');
            $this->cliBoot();$this->verifyInstalledFiles();$this->scheduler('verify');$this->save('CLI_BOOT_VERIFIED');
            $this->holdBarrier('installed');$this->save('BARRIER_LIFT_INTENT');$this->clearMaintenance($this->state['oldMaintenance'],$plan['rollback'].'/storage');$this->clearMaintenance($this->state['newMaintenance'],$active.'/storage');$this->save('BARRIER_LIFTED_SMOKE_PENDING');
            $this->postLiftSmoke();$this->verifyInstalledFiles();$this->save('COMMITTED_OFF');return $this->finishCommitted();
        }catch(Throwable $error){return $this->recover(self::code($error));}
    }

    private function finishCommitted():int
    {
        try{
            $this->verifyInstalledFiles();$this->removeRecordedProbes();
            $this->clearMaintenance($this->state['oldMaintenance'],$this->state['plan']['rollback'].'/storage');$this->clearMaintenance($this->state['newMaintenance'],$this->active().'/storage');
            if($this->state['phase']!=='SCHEDULER_RESTORED'){$this->save('PUBLIC_RELEASED');$this->scheduler('release');$this->save('SCHEDULER_RESTORED');}
            $marker=$this->marker();$this->web->status('/login',200,null,$marker);$this->web->status('/katalog',404,null,$marker);$this->assertGit();$this->save('CLOSED_INSTALL');ReleasePrimitives::journalState($this->state['journal'],$this->state['owner'],'CLOSED');
            $this->emit('CONTROLLED_CMS_INSTALL','PASS_MAIN_RUNTIME_WITH_REVIEWED_OFF_CLAMP');$this->emit('PUBLIC_CHECKOUT','UNCONDITIONALLY_OFF_CONFIG_FALSE_NO_ENV_OR_DB_FLAG_CHANGE');$this->emit('PERMANENT_PRIVATE_DATA_DEPENDENCY_DO_NOT_PRUNE',$this->state['plan']['rollback']);$this->emit('COMPLETE_DIRECTORY_SWITCH','TWO_RENAMES_WITH_OWNED_MAINTENANCE_AND_RECOVERY_NOT_A_SINGLE_ATOMIC_TRANSACTION');return 0;
        }catch(Throwable $error){$this->emit('HOLD_INSTALLED_OFF_FINISH_PENDING',self::code($error));return 2;}
    }

    /**
     * Restored original code: barrier back on the old tree, then web freshness against the old file hashes. A failing
     * restored probe gets one targeted-invalidation retry. If it still fails only because it could not report
     * (FALLBACK_CODES), a conservative fixed hold derived from the PRE-SWITCH host values replaces the probe-derived one,
     * and finishOriginal() lifts only after a re-check. Any other code (stale tree content, failed invalidation, timestamps
     * off, ...) means the web is NOT proven to serve the restored code: the barrier stays for the operator.
     */
    private function verifyRestored():void
    {
        if(is_array($this->state['oldMaintenance']))$this->ensureBarrier($this->state['oldMaintenance'],$this->active().'/storage');$failed=[];
        for($attempt=1;$attempt<=2;$attempt++){
            try{$this->freshness('restored');return;}catch(Throwable $error){$failed[]=self::code($error);$this->emit('RESTORED_FRESHNESS_ATTEMPT_FAILED',['attempt'=>$attempt,'code'=>self::code($error)]);}
        }
        if(array_diff($failed,self::FALLBACK_CODES)!==[]){
            $this->emit('RESTORED_FRESHNESS_NOT_PROVEN',['failedAttempts'=>$failed,'fallbackAllowedOnlyFor'=>self::FALLBACK_CODES,'barrier'=>'RETAINED']);$this->retainForOperator();
        }
        $pre=$this->state['hostPreflight']['values']??null;if(!is_array($pre)||!is_int($pre['holdSeconds']??null)||$pre['holdSeconds']>FreshnessProbe::MAX_HOLD_SECONDS)throw new RuntimeException('RESTORED_FALLBACK_PRESWITCH_VALUES_MISSING');
        $now=time();$swappedAt=$this->state['swappedAt']['restored'];$lift=$now+$pre['holdSeconds'];
        $hold=['swappedAt'=>$swappedAt,'probedAt'=>$now,'liftNotBefore'=>$lift,'requiredSecondsAfterSwap'=>$lift-$swappedAt,'realpathCacheTtl'=>$pre['realpathCacheTtl'],'realpathCacheSize'=>$pre['realpathCacheSize'],'revalidateFreq'=>$pre['revalidateFreq'],'marginSeconds'=>FreshnessProbe::HOLD_MARGIN,'maxSeconds'=>FreshnessProbe::MAX_HOLD_SECONDS,'source'=>'PRE_SWITCH_HOST_PROBE_COUNTED_FROM_NOW'];
        $this->state['freshness']['restored']=['label'=>'restored','fallback'=>true,'failedAttempts'=>$failed,'hold'=>$hold];
        $this->emit('RESTORED_FRESHNESS_FALLBACK',['label'=>'restored','failedAttempts'=>$failed,'holdSeconds'=>$pre['holdSeconds'],'liftOnlyAfter'=>'IDENTITY_OLD_TREE_HASHES_AND_PRIVATE_LOGIN_200_BEHIND_BARRIER']);
    }

    private function operatorLiftCommand():string
    {
        $framework=$this->active().'/storage/framework';return 'rm -f '.$framework.'/down '.$framework.'/maintenance.php && bash '.$this->root.'/incoming/b1144-controlled-install.sh';
    }

    /** The owned barrier stays on the restored old code on purpose; only the printed operator command lifts it. */
    private function retainForOperator():never
    {
        $this->save('RESTORED_BARRIER_RETAINED_OPERATOR_LIFT');$this->emit('OPERATOR_LIFT_COMMAND',$this->operatorLiftCommand());throw new RuntimeException('RESTORED_BARRIER_RETAINED_OPERATOR_LIFT');
    }

    /**
     * After a fallback hold: lift only if the restored old code is what is active AND answers /login 200 behind the barrier.
     * The barrier's private pass-through stops working at maintenanceExpires (prepare + 1500 s, baked into the barrier
     * file); after that the re-check cannot pass, so it is refused without a request and the barrier stays for the operator.
     */
    private function restoredRecheck():void
    {
        try{
            if($this->identity($this->active())!==$this->state['plan']['oldIdentity'])throw new RuntimeException('RESTORED_RECHECK_IDENTITY');
            foreach($this->expected('old') as $relative=>$digest){$path=$this->active().'/'.$relative;clearstatcache(true,$path);$actual=is_file($path)&&!is_link($path)?hash_file('sha256',$path):null;if($actual!==$digest)throw new RuntimeException('RESTORED_RECHECK_TREE_CONTENT');}
            if(time()>=$this->state['maintenanceExpires'])throw new RuntimeException('RESTORED_RECHECK_SMOKE_TOKEN_EXPIRED');
            $this->web->status('/login',200,null,$this->marker(),3,$this->state['oldMaintenance']['smokeToken']);$this->emit('RESTORED_FALLBACK_RECHECK','PASS_OLD_CODE_LOGIN_200_BEHIND_OWNED_BARRIER');
        }catch(Throwable $error){$this->emit('RESTORED_FALLBACK_RECHECK',self::code($error));$this->retainForOperator();}
    }

    /**
     * $resume: null (normal path: restored hold first), 'OPERATOR_LIFTED' (the operator removed the retained barrier) or
     * 'RESUMED_RELEASE' (a re-run after the release had already started). Neither resume form holds or re-imposes a barrier.
     */
    private function finishOriginal(bool $switched,?string $resume=null):int
    {
        $plan=$this->state['plan'];$active=$this->active();if($this->identity($active)!==$plan['oldIdentity'])throw new RuntimeException('ORIGINAL_CODE_REQUIRED_BEFORE_RELEASE');
        $other=is_dir($plan['failed'])?$plan['failed']:$plan['candidate'];if($this->identity($other)!==$plan['candidateIdentity'])throw new RuntimeException('RECOVERY_CANDIDATE_IDENTITY');
        if($switched&&$resume===null){$this->holdBarrier('restored');if(($this->state['freshness']['restored']['fallback']??false)===true)$this->restoredRecheck();}
        $previous=$this->state['originalRelease']['resume']??null;$this->state['originalRelease']=['switched'=>$switched,'resume'=>$resume==='RESUMED_RELEASE'&&$previous==='OPERATOR_LIFTED'?'OPERATOR_LIFTED':$resume];$this->save('ORIGINAL_RELEASE_INTENT');
        // The inactive tree is served by nothing: a barrier left there (e.g. next to a foreign file) is reported, never blocking.
        if(is_array($this->state['newMaintenance']))try{$this->clearMaintenance($this->state['newMaintenance'],$other.'/storage');}catch(Throwable $error){$this->emit('INACTIVE_TREE_BARRIER_NOT_CLEARED',self::code($error));}
        if(is_array($this->state['oldMaintenance']))$this->clearMaintenance($this->state['oldMaintenance'],$active.'/storage');
        $this->save('ORIGINAL_RELEASED_PENDING_HTTP');
        try{$this->web->status('/login',200,null,$this->marker());}catch(Throwable $error){$this->save('ORIGINAL_RELEASE_AVAILABILITY_HOLD');throw $error;}
        return $this->releaseSchedulerAfterOriginal();
    }

    /** The original site is live and verified; only the scheduler release can remain. Its failure never re-imposes a barrier. */
    private function releaseSchedulerAfterOriginal():int
    {
        $switched=($this->state['originalRelease']['switched']??null)===true;
        if(($this->state['schedulerPauseAttempted']??false)||$this->state['scheduler']!==null){
            $this->save('ORIGINAL_RELEASED_SCHEDULER_PENDING');
            try{$this->scheduler('release');}catch(Throwable $error){$this->emit('SCHEDULER_RELEASE_PENDING_ORIGINAL_SITE_LIVE_NO_BARRIER',self::code($error));return 2;}
        }
        $this->assertGit();$this->save($switched?'ROLLED_BACK':'ROLLED_BACK_PRE_SWITCH');
        $this->emit($switched?'ROLLBACK':'ORIGINAL_APPLICATION_CODE_VENDOR_UNTOUCHED',$switched?$this->rollbackLabel():true);return 2;
    }

    /** What actually proved the restored code before the lift; the fallback and the operator lift never reuse the normal label. */
    private function rollbackLabel():string
    {
        if(($this->state['originalRelease']['resume']??null)==='OPERATOR_LIFTED')return 'ORIGINAL_CODE_PRIVATE_DATA_RESTORED_OPERATOR_LIFTED_BARRIER_PUBLIC_LOGIN_VERIFIED_FRESHNESS_NOT_PROVEN';
        if(($this->state['freshness']['restored']['fallback']??false)===true)return 'ORIGINAL_CODE_PRIVATE_DATA_RESTORED_PRESWITCH_FALLBACK_HOLD_PRIVATE_LOGIN_AND_HTTP_VERIFIED_FRESHNESS_PROBE_UNAVAILABLE';
        return 'ORIGINAL_CODE_PRIVATE_DATA_RESTORED_FRESHNESS_AND_HTTP_VERIFIED';
    }

    /**
     * Re-run after ORIGINAL_RELEASE_INTENT, ORIGINAL_RELEASED_PENDING_HTTP or ORIGINAL_RELEASE_AVAILABILITY_HOLD: the hold
     * for the original code had already passed. Any owned barrier still present is cleared (never re-imposed), public
     * /login is re-checked and the scheduler release retried; a failing /login stays ORIGINAL_RELEASE_AVAILABILITY_HOLD.
     */
    private function resumeOriginalRelease():int
    {
        try{
            $switched=($this->state['originalRelease']['switched']??null)===true;if($switched!==is_dir($this->state['plan']['failed']))throw new RuntimeException('RESTORE_PHASE_TOPOLOGY_MISMATCH');
            $this->emit('RESUME_ORIGINAL_RELEASE_NO_BARRIER',['phase'=>$this->state['phase'],'switched'=>$switched,'barrier'=>'NOT_REIMPOSED']);return $this->finishOriginal($switched,'RESUMED_RELEASE');
        }catch(Throwable $error){$this->emit('RECOVERY_HOLD',self::code($error));return 2;}
    }

    /** Re-run in ORIGINAL_RELEASED_SCHEDULER_PENDING: the old code is live without a barrier; only the scheduler release is retried. */
    private function resumeSchedulerRelease():int
    {
        try{
            if($this->identity($this->active())!==$this->state['plan']['oldIdentity'])throw new RuntimeException('ORIGINAL_CODE_REQUIRED_BEFORE_RELEASE');
            $this->emit('RESUME_SCHEDULER_RELEASE_ONLY',['barrier'=>'NOT_REIMPOSED']);return $this->releaseSchedulerAfterOriginal();
        }catch(Throwable $error){$this->emit('RECOVERY_HOLD',self::code($error));return 2;}
    }

    /** One rollback-prefix step; its failure is reported and the directory rollback proceeds under whatever barrier exists. */
    private function step(string $name,callable $action):void
    {
        try{$action();}catch(Throwable $error){$this->emit('ROLLBACK_PREFIX_STEP',['step'=>$name,'result'=>self::code($error),'rollbackProceeds'=>true]);}
    }

    private function recover(string $reason):int
    {
        $this->emit('INSTALL_HOLD_REASON',$reason);
        try{
            // Probe cleanup writes state.json: a disk or quota failure there is reported and never stops the rename back.
            $this->step('PROBE_CLEANUP',fn()=>$this->removeRecordedProbes());$plan=$this->state['plan'];$active=$this->active();$current=is_dir($active)?$this->identity($active):null;
            if($current===$plan['oldIdentity']&&!is_dir($plan['rollback'])){
                $switched=is_dir($plan['failed']);if(isset($this->state['originalRelease'])&&($this->state['originalRelease']['switched']??null)!==$switched)throw new RuntimeException('RESTORE_PHASE_TOPOLOGY_MISMATCH');
                if($switched&&($this->state['phase']??'')==='RESTORED_BARRIER_RETAINED_OPERATOR_LIFT'&&is_array($this->state['oldMaintenance'])&&$this->existingMaintenance($this->state['oldMaintenance'],$active.'/storage')===null){
                    $this->emit('OPERATOR_LIFTED_RESTORED_BARRIER','NOT_REIMPOSED_LOGIN_AND_SCHEDULER_ONLY');return $this->finishOriginal(true,'OPERATOR_LIFTED');
                }
                // No rename is pending here: a recorded probe that still could not be removed keeps whatever barrier exists.
                $this->removeRecordedProbes();
                // Restore time unknown after a crash: counting from now is the conservative choice.
                if($switched){$this->markSwap('restored',false);$this->verifyRestored();}return $this->finishOriginal($switched);
            }
            if($current===$plan['candidateIdentity']){
                // Post-switch (including after the lift): barrier back on both trees, then the journaled rename back. Scheduler
                // verify is report-only and the drain is bounded; neither (nor a failed save or barrier step) blocks the rename.
                $this->step('ROLLBACK_BARRIER_INTENT',fn()=>$this->save('ROLLBACK_BARRIER_INTENT'));
                $this->step('BARRIER_ON_NEW_ACTIVE_TREE',fn()=>$this->ensureBarrier($this->state['newMaintenance'],$active.'/storage'));
                if(is_dir($plan['rollback']))$this->step('BARRIER_ON_RETAINED_OLD_TREE',fn()=>$this->ensureBarrier($this->state['oldMaintenance'],$plan['rollback'].'/storage'));
                $this->step('ROLLBACK_BARRIER_OWNED',fn()=>$this->save('ROLLBACK_BARRIER_OWNED'));
                $this->step('SCHEDULER_VERIFY_REPORT_ONLY',fn()=>$this->scheduler('verify'));
                $this->step('RECOVERY_DRAIN_BOUNDED',fn()=>$this->drain());
            }
            $restore=ReleasePrimitives::recoverSwitch($plan,$this->state['journal'],$this->state['owner'],$plan['failed']);
            $this->markSwap('restored',$restore==='OLD_RESTORED');
            // After the rename: a recorded probe that still could not be removed (foreign bytes at its name) keeps the barrier.
            $this->removeRecordedProbes();$this->save('OLD_RESTORED_MAINTENANCE_HELD');
            $switched=is_dir($plan['failed']);if($switched)$this->verifyRestored();return $this->finishOriginal($switched);
        }catch(Throwable $error){
            if(!in_array($this->persistedPhase(),self::KEEP_PHASES,true)){try{$this->save('HOLD_RECOVERY_OWNED_MAINTENANCE_RETAINED');}catch(Throwable){$this->emit('RECOVERY_STATE_UNWRITABLE','FIX_DISK_OR_PERMISSIONS_THEN_RERUN_SAME_COMMAND');}}
            $this->emit('RECOVERY_HOLD',self::code($error));return 2;
        }
    }
}
