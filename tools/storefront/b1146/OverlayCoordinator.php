<?php
declare(strict_types=1);

/**
 * B1146 legal-pages overlay: ships the exact PR #152 file set (main eabe16af, 12 new and 9 modified files, tests excluded)
 * into the installed CMS release apps/cms/current. The Storefront stays CLOSED: the Storefront DB switch is only read, never
 * written; no migration, no .env, no config change, no OPcache reset.
 * Order: PREFLIGHT (read-only, collect-all; any blocker is a HOLD with nothing changed) -> per-file backup into the private
 * B1146 namespace -> install in dependency order (new classes and views, route-independent modified files, routes/web.php,
 * route cache handling, route-dependent modified files and CSS), each via same-directory temp + fsync + rename (link for a
 * new file, so nothing is ever clobbered) -> view:clear (+ view:cache when views were cached) -> targeted per-file
 * opcache_invalidate through a token-gated temporary probe -> wait max(realpath_cache_ttl, revalidate_freq) + 5 s ->
 * smokes, fresh-code probe, merchant-approval notice, log scan, full tree verification. Any failure after the first file
 * restores every backed-up byte, re-invalidates, re-clears the caches and smoke-tests the old state.
 * Every phase is saved before the next step; a re-run converges from the saved phase.
 * @author Ald1n <https://ald1n.com>
 */
final class OverlayCoordinator
{
    public const MANIFEST_SHA='d737387e60a981b3b404e0980d9b70cb95b6e1d506a39211981d49dcd12be4c6';
    public const MANIFEST_FILES=8531;
    /** config/public_storefront.php after the B1145 unclamp: the staged original bytes. */
    public const CONFIG_SHA='0cfbc864a3d44822a9d14e80f6252e84eb208c9da5a25d5fe6989e4cad789818';
    public const B1145_REPORT_PATTERN='~^1108-B1145-CONTROLLED-MIGRATE-UNCLAMP-\d{8}-\d{6}\.md$~D';
    public const RAN=71;
    public const SOURCE_COMMIT='eabe16af8c19fade882e25c70425eb43c3e17ed9';
    public const BASE_COMMIT='77a3efea';
    /**
     * Install order. 'new' files have no caller in the old tree. Group B is route-independent. routes/web.php then makes
     * the new routes live; group D (the settings controller) calls route() for the new names; group E (views and CSS)
     * calls route('legal.*') or route('admin.settings.public-storefront.legal.*') and the admin view needs the variables of
     * the new controller, so it follows the controller after a revalidation pause.
     */
    public const STEPS=[
        ['app/Support/PublicLegalTemplates.php','new','A'],['app/Support/LegalTextRenderer.php','new','A'],['app/Services/PublicLegalTextStore.php','new','A'],
        ['app/Services/PublicLegalPagesService.php','new','A'],['app/Services/PublicStorefrontOnReadinessService.php','new','A'],['app/Http/Controllers/PublicLegalPageController.php','new','A'],
        ['app/Http/Controllers/Admin/PublicLegalTextController.php','new','A'],['resources/views/admin/settings/public-storefront-legal.blade.php','new','A'],
        ['resources/views/public-shop/legal/nav.blade.php','new','A'],['resources/views/public-shop/legal/draft.blade.php','new','A'],['resources/views/public-shop/legal/index.blade.php','new','A'],['resources/views/public-shop/legal/page.blade.php','new','A'],
        ['app/Services/PublicMerchantPolicyService.php','modified','B'],['app/Services/PublicStorefrontStateService.php','modified','B'],['app/Http/Middleware/PublicNoScriptCsp.php','modified','B'],
        ['routes/web.php','modified','C'],
        ['app/Http/Controllers/Admin/PublicMerchantSettingsController.php','modified','D'],['resources/views/admin/settings/public-storefront.blade.php','modified','E'],
        ['resources/views/layouts/app.blade.php','modified','E'],['resources/views/public-shop/layout.blade.php','modified','E'],['public/assets/css/ald1n-public-storefront.css','modified','E'],
    ];
    public const LEGAL=['/pravne-informacije','/uslovi-prodaje','/politika-privatnosti','/podaci-o-trgovcu','/pravo-na-odustanak','/reklamacije','/dostava-i-placanje','/kolacici'];
    public const CLOSED=['/katalog','/korpa','/porucivanje','/registracija'];
    public const SYSTEM=['/login','/up'];
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
    private string $b1145Base;
    private string $appStateScript;
    private string $appCheckScript;
    private int $censusSeconds=75;
    private int $minFreeBytes=268435456;
    private bool $scheduleGate=true;
    private ?int $fixedNow=null;
    private int $scheduleNeedSeconds=1800;
    private int $defaultBackupSeconds=2400;
    private ?Closure $fault=null;
    private ?array $manifest=null;
    private array $plan;
    public string $final='HOLD';

    public function __construct(private string $root,private string $home,array $package,private string $incoming,private string $mode='install')
    {
        if($root!=='/home/icaffeco/ald1n-project'||$home!=='/home/icaffeco'||realpath($root)!==$root||realpath($home)!==$home||!function_exists('posix_geteuid')||!extension_loaded('curl')||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('HOST_PROFILE');
        $this->base=$home.'/.ald1n-b1146-state';$this->pointer=$this->base.'/current.json';$this->stage=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU';$this->b1145Base=$home.'/.ald1n-b1145-state';
        $this->appStateScript=$incoming.'/b1145-app-state.php';$this->appCheckScript=$incoming.'/b1146-app-check.php';$this->web=new OverlayWeb();$this->plan=self::plan($package,$incoming);putenv('GIT_OPTIONAL_LOCKS=0');
        if(!in_array($mode,['install','rollback'],true))throw new RuntimeException('OPERATOR_MODE');
    }

    /** The overlay plan from b1146-package.json must be exactly STEPS (paths, kinds, order) with digests and a readable payload. */
    public static function plan(array $package,string $incoming):array
    {
        $overlay=$package['overlay']??null;if(!is_array($overlay)||count($overlay)!==count(self::STEPS)||($package['sourceCommit']??null)!==self::SOURCE_COMMIT)throw new RuntimeException('OVERLAY_PLAN_SCOPE');$plan=[];
        foreach(array_values($overlay) as $i=>$e){
            [$path,$kind,$group]=self::STEPS[$i];
            if(!is_array($e)||($e['path']??null)!==$path||($e['kind']??null)!==$kind||($e['group']??null)!==$group||!preg_match('/^b1146-payload-\d{2}\.bin$/D',(string)($e['payload']??''))||!preg_match('/^[a-f0-9]{64}$/D',(string)($e['payloadSHA256']??''))||!is_int($e['size']??null))throw new RuntimeException('OVERLAY_PLAN_ENTRY');
            $pre=$e['preSHA256']??null;if(($kind==='new')!==($pre===null)||($pre!==null&&!preg_match('/^[a-f0-9]{64}$/D',$pre))||$pre===$e['payloadSHA256'])throw new RuntimeException('OVERLAY_PLAN_PRE_STATE');
            InstallFileOps::relative($path);$plan[]=['n'=>$i+1,'path'=>$path,'kind'=>$kind,'group'=>$group,'payload'=>$incoming.'/'.$e['payload'],'payloadSHA256'=>$e['payloadSHA256'],'preSHA256'=>$pre,'size'=>$e['size']];
        }
        return $plan;
    }

    private function open():void
    {
        if($this->lock!==null)return;if(!is_dir($this->base)&&!mkdir($this->base,0700))throw new RuntimeException('PRIVATE_STATE_CREATE');
        if(is_link($this->base)||realpath($this->base)!==$this->base||(fileperms($this->base)&0777)!==0700||fileowner($this->base)!==posix_geteuid())throw new RuntimeException('PRIVATE_STATE_CONFINEMENT');
        $lock=fopen($this->base.'/operator.lock','c');if($lock===false||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('ANOTHER_B1146_RUN_ACTIVE');chmod($this->base.'/operator.lock',0600);$this->lock=$lock;
    }

    private function emit(string $key,mixed $value):void {echo $key.'='.json_encode($value,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;}
    private static function code(Throwable $error):string {$code=$error->getMessage();return preg_match('/^[A-Z0-9_]+$/D',$code)?$code:'UNEXPECTED_'.strtoupper((new ReflectionClass($error))->getShortName());}
    private function active():string {return $this->root.'/apps/cms/current';}
    private function target(array $step):string {return $this->active().'/'.$step['path'];}
    private function fault(string $point):void {if($this->fault!==null)($this->fault)($point);}
    private function now():int {return $this->fixedNow??time();}
    private function retained():string {$r=$this->state['chain']['retained']??null;if(!is_string($r))throw new RuntimeException('RETAINED_TREE_UNKNOWN');return $r;}

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
        $this->atomicJSON($this->pointer,['schemaVersion'=>1,'kind'=>'B1146','private'=>$this->state['private'],'id'=>$this->state['id']]);$this->emit('B1146_PHASE',$phase);$this->fault('SAVED_'.$phase);
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
        return $this->command(array_merge([$this->php,'-d','opcache.enable=0','-d','opcache.enable_cli=0',$this->active().'/artisan'],$arguments,['--no-interaction','--no-ansi']),$this->active(),$timeout,array_merge(getenv(),['COLUMNS'=>'240']));
    }

    private function artisanOk(array $arguments,int $timeout=300):void
    {
        $r=$this->artisan($arguments,$timeout);$this->emit('ARTISAN',['command'=>implode(' ',$arguments),'rc'=>$r['rc'],'stderrBytes'=>$r['stderrBytes']]);if($r['rc']!==0)throw new RuntimeException('ARTISAN_FAILED_'.strtoupper(str_replace([':','-'],'_',$arguments[0])));
    }

    /** One of the read-only helpers (b1145-app-state.php state, b1146-app-check.php approval|routes); JSON booleans and counts only. */
    private function helper(string $script,string $mode,int $timeout=120):array
    {
        $r=$this->command([$this->php,'-d','opcache.enable=0','-d','opcache.enable_cli=0',$script,$this->active(),$mode],$this->active(),$timeout,array_merge(getenv(),['COLUMNS'=>'240']));
        $data=json_decode(trim($r['stdout']),true,32);if(!is_array($data))throw new RuntimeException('APP_HELPER_UNPARSED');
        if($r['rc']!==0||($data['ok']??false)!==true){$code=is_string($data['error']??null)&&preg_match('/^[A-Z0-9_]+$/D',$data['error'])?$data['error']:'APP_HELPER_FAILED';throw new RuntimeException($code);}unset($data['ok']);return $data;
    }

    private function manifest():array
    {
        if($this->manifest!==null)return $this->manifest;$path=$this->stage.'/verification/runtime-manifest.json';
        if(is_link($path)||realpath($path)!==$path||!is_file($path)||hash_file('sha256',$path)!==self::MANIFEST_SHA)throw new RuntimeException('RUNTIME_MANIFEST_INTEGRITY');
        $m=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);if(!is_array($m['files']??null)||count($m['files'])!==self::MANIFEST_FILES)throw new RuntimeException('RUNTIME_MANIFEST_COUNT');return $this->manifest=$m;
    }

    /** The manifest with the overlay applied: the 9 modified entries carry the payload digest and the 12 new files are added. */
    private function installedManifest():array
    {
        $m=$this->manifest();$by=[];foreach($this->plan as $s)$by[$s['path']]=$s;$files=[];
        foreach($m['files'] as $f){$s=$by[$f['path']]??null;if($s!==null&&$s['kind']==='new')throw new RuntimeException('NEW_FILE_ALREADY_IN_MANIFEST');if($s!==null){if($f['sha256']!==$s['preSHA256'])throw new RuntimeException('MANIFEST_PRE_STATE_MISMATCH');$f['sha256']=$s['payloadSHA256'];}$files[]=$f;}
        foreach($this->plan as $s)if($s['kind']==='new')$files[]=['path'=>$s['path'],'sha256'=>$s['payloadSHA256']];return ['files'=>$files];
    }

    /** Last value of every KEY=value line of the B1145 terminal report (Report1108). */
    public static function b1145Facts(string $bytes):array
    {
        if(str_contains($bytes,"\r")||strlen($bytes)>4194304)throw new RuntimeException('B1145_REPORT_FORMAT');$last=[];
        foreach(explode("\n",$bytes) as $line)if(preg_match('/^([A-Z0-9_]+)=(.*)$/D',$line,$m))$last[$m[1]]=$m[2];
        if(($last['B1145_FINAL']??null)!=='PASS_MIGRATED_UNCLAMPED_STOREFRONT_CLOSED'||($last['EXIT']??null)!=='0'||json_decode($last['B1145_PHASE']??'null',true)!=='CLOSED_UNCLAMPED')throw new RuntimeException('B1145_REPORT_NOT_TERMINAL_PASS');
        return ['final'=>$last['B1145_FINAL'],'phase'=>'CLOSED_UNCLAMPED','exit'=>0];
    }

    /** Chain: exactly one Report1108 of B1145 with its terminal PASS, and the live B1145 state CLOSED_UNCLAMPED. Read-only. */
    private function chain():array
    {
        $directory=$this->root.'/docs/operations';$found=[];foreach(scandir($directory)?:[] as $name)if(preg_match(self::B1145_REPORT_PATTERN,$name))$found[]=$name;
        if(count($found)!==1)throw new RuntimeException(count($found)===0?'B1145_REPORT1108_MISSING':'B1145_REPORT1108_AMBIGUOUS');$path=$directory.'/'.$found[0];
        if(is_link($path)||realpath($path)!==$path||!is_file($path))throw new RuntimeException('B1145_REPORT1108_SCOPE');$bytes=(string)file_get_contents($path);$facts=self::b1145Facts($bytes);
        $pointer=$this->b1145Base.'/current.json';if(is_link($pointer)||!is_file($pointer)||filesize($pointer)>8192)throw new RuntimeException('B1145_POINTER_MISSING');$p=json_decode((string)file_get_contents($pointer),true,8,JSON_THROW_ON_ERROR);
        if(!is_array($p)||!preg_match('/^[a-f0-9]{32}$/D',(string)($p['id']??''))||($p['private']??null)!==$this->b1145Base.'/'.$p['id'])throw new RuntimeException('B1145_POINTER_SCOPE');
        $statePath=$p['private'].'/state.json';if(is_link($statePath)||realpath($statePath)!==$statePath||filesize($statePath)>16777216)throw new RuntimeException('B1145_STATE_MISSING');$stateBytes=(string)file_get_contents($statePath);$s=json_decode($stateBytes,true,512,JSON_THROW_ON_ERROR);
        if(!is_array($s)||($s['kind']??null)!=='B1145'||($s['id']??null)!==$p['id']||($s['phase']??null)!=='CLOSED_UNCLAMPED')throw new RuntimeException('B1145_STATE_NOT_CLOSED_UNCLAMPED');
        $retained=$s['b1144']['retained']??null;$htaccess=$s['b1144']['htaccessSHA256']??null;
        if(!is_string($retained)||!preg_match('~^/[A-Za-z0-9._/-]+/retained$~D',$retained)||!is_string($htaccess)||!preg_match('/^[a-f0-9]{64}$/D',$htaccess))throw new RuntimeException('B1145_STATE_FACTS');
        return ['report'=>'docs/operations/'.$found[0],'reportSHA256'=>hash('sha256',$bytes),'b1145Id'=>$p['id'],'b1145StateSHA256'=>hash('sha256',$stateBytes),'retained'=>$retained,'htaccessSHA256'=>$htaccess]+$facts;
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
        return $result;
    }

    /** Scheduled app:backup-create (02:30 daily, 03:10 Sunday, app timezone) must not start within the next 30 minutes. */
    private function scheduleState(array $app):array
    {
        $last=$app['backups']['lastCompletedSeconds']??null;$base=$last===null?$this->defaultBackupSeconds:max(60,(int)$last);$duration=2*$base+600;$now=$this->now();$tz=(string)($app['appTimezone']??'');
        try{$local=(new DateTimeImmutable('@'.$now))->setTimezone(new DateTimeZone($tz))->format('Y-m-d H:i T');}catch(Throwable){throw new RuntimeException('APP_TIMEZONE_INVALID');}
        return ['conflict'=>MigrateUnclampCoordinator::scheduleConflict($app['backupSchedule']??[],$now,$this->scheduleNeedSeconds,$duration),'appTimezone'=>$tz,'appLocalTime'=>$local,'needSeconds'=>$this->scheduleNeedSeconds,'scheduledBackupSeconds'=>$duration,'events'=>$app['backupSchedule']??[]];
    }

    private function migrationStatus():array
    {
        $r=$this->artisan(['migrate:status'],120);if($r['rc']!==0)throw new RuntimeException('MIGRATE_STATUS_FAILED_OR_DB_UNREACHABLE');
        $s=LeanGates::migrationStatus($r['stdout'],LeanGates::migrationFiles($this->active().'/database/migrations'));if($s['ran']!==self::RAN||$s['pending']!==[])throw new RuntimeException('MIGRATION_SET_NOT_71_RAN_0_PENDING');return $s;
    }

    private function ownTemps():array
    {
        $found=[];$dirs=[];foreach($this->plan as $s)$dirs[dirname($this->target($s))]=true;$dirs[$this->active().'/bootstrap/cache']=true;
        foreach(array_keys($dirs) as $d)if(is_dir($d))foreach(scandir($d)?:[] as $name)if(OverlayFiles::isOwnTemp($d.'/'.$name))$found[]=$d.'/'.$name;return $found;
    }

    private function cleanupRecorded():void
    {
        $changed=false;
        foreach($this->state['probes']??[] as $i=>$probe){if(($probe['removed']??false)===true)continue;FreshnessProbe::removeStaging($probe['staging']);FreshnessProbe::remove($this->active().'/public',$probe['name'],$probe['sha256']);$this->state['probes'][$i]['removed']=true;$changed=true;}
        foreach($this->state['temps']??[] as $i=>$temp){if(OverlayFiles::isOwnTemp($temp)&&is_file($temp)&&!is_link($temp)&&!unlink($temp))throw new RuntimeException('TEMP_FILE_REMOVE');unset($this->state['temps'][$i]);$changed=true;}
        if($changed){$this->state['temps']=array_values($this->state['temps']??[]);$this->persist();}
    }

    private function recordTemp(string $temp):string {$this->state['temps'][]=$temp;$this->persist();return $temp;}

    /** absolute path => expected sha for the probe: PHP sources of the plan ('installed' or 'pre' bytes) and the route cache files. */
    private function probeTargets(bool $installed):array
    {
        $t=[];foreach($this->plan as $s){if(!str_ends_with($s['path'],'.php')||str_ends_with($s['path'],'.blade.php'))continue;$dir=realpath(dirname($this->target($s)));$path=($dir===false?dirname($this->target($s)):$dir).'/'.basename($s['path']);$t[$path]=$installed?$s['payloadSHA256']:$s['preSHA256'];}
        foreach($this->routeCacheFiles() as $name=>$sha)$t[$this->active().'/bootstrap/cache/'.$name]=$sha;return $t;
    }

    /** One probe file: one request (targeted invalidation when asked), assessed collect-all. Always removed. */
    private function probeRun(string $label,bool $installed,bool $invalidate,bool $requireFresh,bool $collect=false):array
    {
        $this->cleanupRecorded();FreshnessProbe::assertNoLeftover($this->active().'/public');$targets=$this->probeTargets($installed);$plan=OverlayProbe::plan($this->state['id'],$targets,$this->viewsDirectory());
        $staging=$this->state['private'].'/probe-'.bin2hex(random_bytes(8)).'.staging';$this->state['probes'][]=['name'=>$plan['name'],'sha256'=>$plan['sha256'],'label'=>$label,'staging'=>$staging];$this->persist();
        try{FreshnessProbe::write($this->active().'/public/'.$plan['name'],$plan['source'],$staging);$response=$this->web->probe($plan['name'],$plan['token'],$invalidate,null);}finally{$this->cleanupRecorded();}
        $route=is_string($response['_route']??null)?$response['_route']:'UNKNOWN';unset($response['_route']);$a=OverlayProbe::assess($response,$this->state['id'],$targets,$this->allowedSapis,$invalidate,$requireFresh);
        $result=['label'=>$label,'route'=>$route,'invalidationRequested'=>$invalidate,'requireFresh'=>$requireFresh,'gates'=>array_map(static fn($c)=>$c??'PASS',$a['gates'])]+$a['values']+['probeRemoved'=>true,'failures'=>$a['failures']];$this->emit('WEB_OVERLAY_PROBE',$result);
        if(!$collect&&$a['failures']!==[])throw new RuntimeException($a['failures'][0]);return $result;
    }

    /** Waits on the monotonic clock until $seconds have passed since the last change; heartbeat every 15 s. */
    private function hold(string $label,int $seconds):void
    {
        if($seconds<0||$seconds>FreshnessProbe::MAX_HOLD_SECONDS)throw new RuntimeException('OVERLAY_HOLD_EXCEEDS_BOUND');$since=(int)($this->state['lastChangeNs']??hrtime(true));$deadline=$since+$seconds*1000000000;$beat=hrtime(true)+15000000000;
        $this->emit('OVERLAY_HOLD_START',['label'=>$label,'seconds'=>$seconds,'remainingSeconds'=>max(0,(int)ceil(($deadline-hrtime(true))/1e9)),'clock'=>'MONOTONIC']);
        while(($rest=$deadline-hrtime(true))>0){usleep((int)max(1000,intdiv(min($rest,max(0,$beat-hrtime(true)),1000000000),1000)));if(hrtime(true)>=$beat&&hrtime(true)<$deadline){$this->emit('OVERLAY_HOLD_HEARTBEAT',['label'=>$label,'remainingSeconds'=>(int)ceil(($deadline-hrtime(true))/1e9)]);$beat+=15000000000;}}
        $this->emit('OVERLAY_HOLD',['label'=>$label,'seconds'=>$seconds,'done'=>true]);
    }

    private function changed():void {$this->state['lastChangeNs']=hrtime(true);}

    /** $installed: legal pages 200 with noindex; otherwise 404 (the old tree has no such route). Shop routes always 404. */
    private function smokes(bool $installed):array
    {
        $this->fault('BEFORE_SMOKES_'.($installed?'INSTALLED':'OLD'));$marker=bin2hex(random_bytes(8));$failed=[];$rows=[];
        $expect=[];foreach(self::SYSTEM as $p)$expect[$p]=200;foreach(self::LEGAL as $p)$expect[$p]=$installed?200:404;foreach(self::CLOSED as $p)$expect[$p]=404;
        foreach($expect as $path=>$status){
            for($attempt=1;$attempt<=3;$attempt++){$r=$this->web->fetch($path,$marker);if(!$r['transport']&&!in_array($r['status'],[502,503,504,520,521,522,523,524,525,526,527],true))break;if($attempt<3)usleep(2000000);}
            $noindex=OverlayWeb::noindex($r['robots']);$pass=!$r['transport']&&$r['status']===$status&&(!($installed&&in_array($path,self::LEGAL,true))||$noindex);
            $rows[]=['path'=>$path,'expected'=>$status,'observed'=>$r['transport']?'TRANSPORT':$r['status'],'noindex'=>$noindex,'result'=>$pass?'PASS':'FAIL','attempts'=>$attempt];if(!$pass)$failed[]=$path;
        }
        $this->emit('PUBLIC_HTTP_SMOKES',['state'=>$installed?'INSTALLED':'OLD','rows'=>$rows,'failed'=>$failed]);if($failed!==[])throw new RuntimeException($installed?'INSTALLED_SMOKE_FAILED':'OLD_STATE_SMOKE_FAILED');return $rows;
    }

    /** Route names after a route change: all 11 B1146 names (or none after a rollback) and the same cache mode as before. */
    private function routesCheck(bool $installed):array
    {
        $r=$this->helper($this->appCheckScript,'routes');$this->emit('ROUTES_CHECK',$r+['expectInstalled'=>$installed]);
        if($r['b1146RouteNamesPresent']!==($installed?self::B1146_ROUTE_NAMES:0)||$r['storefrontSettingsRoute']!==true)throw new RuntimeException($installed?'ROUTES_NOT_LOADED_AFTER_INSTALL':'ROUTES_NOT_OLD_AFTER_ROLLBACK');
        if($r['routesCached']!==($this->state['cache']['routeCacheFiles']!==[]))throw new RuntimeException('ROUTE_CACHE_MODE_CHANGED');return $r;
    }

    /**
     * PREFLIGHT, read-only apart from the private probe record and its transient probe file. Every gate is evaluated and
     * printed; the caller HOLDs on any blocker. $stage 'fresh' (before install) or 'installed' (re-run after the PASS).
     */
    private function preflight(string $stage):array
    {
        $g=[];$v=['stage'=>$stage];$installed=$stage==='installed';
        $try=static function(string $gate,callable $check)use(&$g):mixed{try{$r=$check();$g[$gate]=null;return $r;}catch(Throwable $e){$g[$gate]=self::code($e);return null;}};
        $chain=$try('chainB1145Report1108',fn()=>$this->chain());if($chain!==null)$this->state['chain']=$chain;$v['chain']=$chain;
        $manifest=$try('runtimeManifest',fn()=>$this->manifest());
        $targets=$try('overlayTargets',fn()=>$this->targets());$v['targets']=$targets;
        if($targets!==null){if(!$installed&&!$targets['preState'])$g['overlayTargets']=$targets['installed']?'ALREADY_INSTALLED':($targets['link']!==[]?'TARGET_IS_SYMLINK':'UNKNOWN_PRE_STATE_REFUSED');if($installed&&!$targets['installed'])$g['overlayTargets']='INSTALLED_DIGESTS_CHANGED';}
        $v['tree']=$manifest!==null&&$chain!==null?$try('activeTreeEqualsManifest',fn()=>$installed?LeanGates::verifyTree($this->active(),$this->installedManifest(),self::CONFIG_SHA,$chain['htaccessSHA256'],self::MANIFEST_FILES+12):LeanGates::verifyTree($this->active(),$manifest,self::CONFIG_SHA,$chain['htaccessSHA256'],self::MANIFEST_FILES)):null;
        if(!array_key_exists('activeTreeEqualsManifest',$g))$g['activeTreeEqualsManifest']='NOT_EVALUATED_CHAIN_OR_MANIFEST';
        if(!$installed)$v['replaceable']=$try('targetsReplaceable',function():array{$n=0;foreach($this->plan as $s){if($s['kind']==='modified'){OverlayFiles::replaceable($this->target($s));$n++;}else{$d=dirname($this->target($s));while(!is_dir($d))$d=dirname($d);if(is_link($d)||!is_writable($d))throw new RuntimeException('TARGET_DIRECTORY_NOT_WRITABLE');}}return ['modifiedOneLinkNoAcl'=>$n];});
        $g['configNotCached']=file_exists($this->active().'/bootstrap/cache/config.php')?'CONFIG_CACHE_PRESENT':null;
        $g['noMaintenanceMode']=file_exists($this->active().'/storage/framework/down')||file_exists($this->active().'/storage/framework/maintenance.php')?'MAINTENANCE_MODE_ACTIVE':null;
        $try('noLeftoverProbeOrTemp',function():void{FreshnessProbe::assertNoLeftover($this->active().'/public');if($this->ownTemps()!==[])throw new RuntimeException('LEFTOVER_B1146_TEMP_FILE');});
        $v['caches']=$try('cacheState',fn()=>['routeCacheFiles'=>$this->routeCacheFiles(),'compiledViews'=>$this->compiledViews(),'viewsCached'=>$this->compiledViews()>0]);
        $v['logs']=LeanGates::dailyLogs($this->active().'/storage/logs');$g['laravelDailyLog']=$v['logs']['dailyFiles']>0?null:'LARAVEL_DAILY_LOG_MISSING';
        $v['migrations']=$try('migrateStatus71Ran0Pending',fn()=>$this->migrationStatus());
        $app=$try('appStateDatabase',fn()=>$this->helper($this->appStateScript,'state'));
        if($app!==null){
            $v['app']=['storefrontDbSwitch'=>$app['storefrontFlag'],'storefrontConfig'=>$app['storefrontConfig'],'backupsRunning'=>$app['backups']['running']];
            $g['storefrontDbSwitchClosed']=in_array($app['storefrontFlag'],['absent','0'],true)?null:'STOREFRONT_DB_SWITCH_NOT_ABSENT_OR_0';
            $g['noRunningBackup']=$app['backups']['table']===true&&$app['backups']['running']===0?null:'BACKUP_RUN_IN_PROGRESS_OR_TABLE_MISSING';
            if($this->scheduleGate){$s=$try('outsideScheduledBackupWindow',fn()=>$this->scheduleState($app));$v['schedule']=$s;if($s!==null&&$s['conflict']!==null)$g['outsideScheduledBackupWindow']='SCHEDULED_BACKUP_WITHIN_30_MINUTES_'.strtoupper((string)$s['conflict']['type']);}
        }else foreach(['storefrontDbSwitchClosed','noRunningBackup','outsideScheduledBackupWindow'] as $gate)$g[$gate]='NOT_EVALUATED_APP_STATE';
        $v['headroom']=$try('diskHeadroom',fn()=>$this->headroom());
        $v['artisanCensus']=$chain!==null?$try('noRunningCmsArtisan',fn()=>$this->census()):null;if(!array_key_exists('noRunningCmsArtisan',$g))$g['noRunningCmsArtisan']='NOT_EVALUATED_CHAIN';
        $v['approval']=$try('merchantApprovalReadable',fn()=>$this->helper($this->appCheckScript,'approval'));
        if($targets!==null&&($targets['preState']||$targets['installed'])&&isset($this->state['id'])&&$v['caches']!==null){$web=$try('webProbe',fn()=>$this->probeRun('preflight-'.$stage,$installed,false,false,true));$v['web']=$web;if($web!==null&&$web['failures']!==[])$g['webProbe']=$web['failures'][0];}else $g['webProbe']='NOT_EVALUATED_TARGETS';
        $try('liveSmokes',fn()=>$this->smokes($installed));
        $blockers=array_values(array_unique(array_filter($g,static fn($c)=>$c!==null)));
        $report=['stage'=>$stage,'gates'=>array_map(static fn($c)=>$c??'PASS',$g),'values'=>$v,'blockers'=>$blockers,'applicationFiles'=>'NONE','database'=>'NONE','transientProbe'=>'WRITTEN_AND_REMOVED','privateState'=>'B1146_NAMESPACE_ONLY'];$this->emit('PREFLIGHT',$report);return $report;
    }

    private function loadExisting():bool
    {
        if(!file_exists($this->pointer))return false;if(is_link($this->pointer)||filesize($this->pointer)>8192)throw new RuntimeException('B1146_POINTER');$p=json_decode((string)file_get_contents($this->pointer),true,8,JSON_THROW_ON_ERROR);
        if(!preg_match('/^[a-f0-9]{32}$/D',(string)($p['id']??''))||($p['private']??null)!==$this->base.'/'.$p['id'])throw new RuntimeException('B1146_POINTER_SCOPE');$path=$p['private'].'/state.json';
        if(is_link($path)||realpath($path)!==$path||filesize($path)>16777216)throw new RuntimeException('B1146_STATE');$this->state=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
        if(($this->state['schemaVersion']??0)!==1||($this->state['kind']??null)!=='B1146'||($this->state['id']??null)!==$p['id'])throw new RuntimeException('B1146_STATE_BINDING');return true;
    }

    private function begin():void
    {
        $id=bin2hex(random_bytes(16));$private=$this->base.'/'.$id;if(!mkdir($private,0700))throw new RuntimeException('PRIVATE_TRANSACTION_CREATE');
        $this->state=['schemaVersion'=>1,'kind'=>'B1146','id'=>$id,'private'=>$private,'phase'=>'NEW','startedAt'=>time(),'probes'=>[],'temps'=>[],'applied'=>[],'createdDirs'=>[]];$this->save('PREFLIGHT');
    }

    /** Per-file 0600 copies of every replaced file and of each route cache file, with a digest manifest (0600). */
    private function backup(array $preflight):void
    {
        $dir=$this->state['private'].'/backup';if(!is_dir($dir)&&!mkdir($dir,0700))throw new RuntimeException('BACKUP_DIRECTORY_CREATE');$files=[];
        foreach($this->plan as $s){
            if($s['kind']==='new'){$files[$s['n']]=['path'=>$s['path'],'kind'=>'new','before'=>'ABSENT'];continue;}
            $t=$this->target($s);$bytes=(string)file_get_contents($t);if(!hash_equals((string)$s['preSHA256'],hash('sha256',$bytes)))throw new RuntimeException('PRE_STATE_CHANGED_BEFORE_BACKUP');clearstatcache(true,$t);$st=stat($t);
            $copy=$dir.'/'.sprintf('%02d',$s['n']).'.orig';if(is_file($copy))AtomicFile::readPrivate($copy,(string)$s['preSHA256']);else AtomicFile::privateCopy($copy,$bytes);
            $files[$s['n']]=['path'=>$s['path'],'kind'=>'modified','copy'=>$copy,'sha256'=>$s['preSHA256'],'size'=>strlen($bytes),'mode'=>$st['mode']&0777,'gid'=>$st['gid'],'mtime'=>$st['mtime']];
        }
        $routes=[];foreach($preflight['values']['caches']['routeCacheFiles'] as $name=>$sha){$t=$this->active().'/bootstrap/cache/'.$name;$bytes=(string)file_get_contents($t);if(hash('sha256',$bytes)!==$sha)throw new RuntimeException('ROUTE_CACHE_CHANGED_BEFORE_BACKUP');clearstatcache(true,$t);$st=stat($t);$copy=$dir.'/'.$name.'.orig';if(!is_file($copy))AtomicFile::privateCopy($copy,$bytes);$routes[$name]=['copy'=>$copy,'sha256'=>$sha,'mode'=>$st['mode']&0777,'gid'=>$st['gid']];}
        $this->state['backup']=['directory'=>$dir,'files'=>$files,'routeCache'=>$routes];$this->state['cache']=['routeCacheFiles'=>$preflight['values']['caches']['routeCacheFiles'],'viewsCached'=>$preflight['values']['caches']['viewsCached']];
        $this->state['approvalBefore']=$preflight['values']['approval'];$this->state['groupPauseSeconds']=(int)($preflight['values']['web']['revalidateFreq']??2)+1;$doc=$dir.'/manifest.json';if(is_file($doc))unlink($doc);AtomicFile::privateCopy($doc,json_encode(['batch'=>'B1146','files'=>array_values($files),'routeCache'=>$routes],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n");
        $this->emit('PER_FILE_BACKUP',['directory'=>$dir,'modifiedFilesCopied'=>count(array_filter($files,static fn($f)=>$f['kind']==='modified')),'newFilesRecordedAbsent'=>count(array_filter($files,static fn($f)=>$f['kind']==='new')),'routeCacheFilesCopied'=>count($routes),'mode'=>'0600','manifestSHA256'=>hash_file('sha256',$doc)]);
        $this->save('BACKED_UP');$this->fault('AFTER_BACKUP');
    }

    /** Route cache handling after routes/web.php changed: route:clear, then route:cache again only when a cache existed. */
    private function refreshRoutes():void
    {
        $this->artisanOk(['route:clear']);if($this->state['cache']['routeCacheFiles']!==[]){$this->artisanOk(['route:cache']);if($this->routeCacheFiles()===[])throw new RuntimeException('ROUTE_CACHE_NOT_REBUILT');}$this->changed();$this->persist();
        $this->routesCheck(true);$this->fault('AFTER_ROUTES');
    }

    private function refreshViews():void {$this->artisanOk(['view:clear']);if($this->state['cache']['viewsCached']===true)$this->artisanOk(['view:cache'],600);$this->changed();$this->persist();}

    private function install():void
    {
        $this->state['logBaseline']=LeanGates::logBaseline($this->active().'/storage/logs');$this->state['applied']=[];$this->save('INSTALL_INTENT');$group=null;
        // Between groups every web worker revalidates the files of the previous group first (revalidate_freq + 1 s), so a
        // request never combines new bytecode of a later group with still-cached old bytecode of an earlier one.
        $pause=max(1,min(61,(int)($this->state['groupPauseSeconds']??3)));
        foreach($this->plan as $s){
            if($group!==null&&$group!==$s['group']){$this->emit('OVERLAY_GROUP_PAUSE',['after'=>$group,'before'=>$s['group'],'seconds'=>$pause]);sleep($pause);}$group=$s['group'];
            $t=$this->target($s);$bytes=$this->payload($s);$this->state['applied'][$s['n']]='PENDING';
            if($s['kind']==='new'){
                $dir=dirname($t);if(!is_dir($dir)){if(!is_dir(dirname($dir)))throw new RuntimeException('TARGET_PARENT_MISSING');$this->state['createdDirs'][]=$dir;$this->persist();if(!mkdir($dir,0755))throw new RuntimeException('TARGET_DIRECTORY_CREATE');chmod($dir,0755);}
                $temp=$this->recordTemp(OverlayFiles::tempFor($t));$receipt=OverlayFiles::create($t,$bytes,0644,$temp);
            }else{$b=$this->state['backup']['files'][$s['n']];$temp=$this->recordTemp(OverlayFiles::tempFor($t));$receipt=OverlayFiles::replace($t,$bytes,$b['mode'],$b['gid'],$temp,(string)$s['preSHA256']);}
            $this->state['temps']=array_values(array_filter($this->state['temps'],static fn($x)=>$x!==$temp));$this->state['applied'][$s['n']]='DONE';$this->changed();$this->persist();
            $this->emit('OVERLAY_STEP',['n'=>$s['n'],'group'=>$s['group'],'kind'=>$s['kind'],'path'=>$s['path'],'sha256'=>$s['payloadSHA256']]+$receipt);$this->fault('AFTER_STEP_'.$s['n']);
            if($s['group']==='C')$this->refreshRoutes();
        }
        $this->refreshViews();$this->save('FILES_INSTALLED');
    }

    private function approvalNotice(?array $before,?array $after):void
    {
        $reset=($before['approvalDigestMatchesFingerprint']??false)===true&&($after['approvalDigestMatchesFingerprint']??true)===false;
        $this->emit('MERCHANT_APPROVAL',['before'=>$before,'after'=>$after,'resetByThisInstall'=>$reset]);
        echo 'OWNER_NOTICE_MERCHANT_APPROVAL='.($reset?'THE_MERCHANT_APPROVAL_IS_RESET':'THE_MERCHANT_APPROVAL_WAS_NOT_VALID_BEFORE_THIS_INSTALL').'_THE_LEGAL_TEXTS_ARE_NOW_PART_OF_WHAT_THE_OWNER_SIGNS_REVIEW_THE_TERMS_AT_/admin/settings/public-storefront_AND_CONFIRM_THE_APPROVAL_AGAIN_BEFORE_THE_STOREFRONT_CAN_BE_TURNED_ON'.PHP_EOL;
    }

    private function accept():void
    {
        $this->save('ACCEPTANCE_INTENT');$first=$this->probeRun('installed-invalidate',true,true,false);$this->fault('AFTER_INVALIDATE');$this->hold('installed',(int)$first['holdSeconds']);
        $this->smokes(true);$this->probeRun('installed-fresh',true,false,true);$this->routesCheck(true);
        $after=$this->helper($this->appCheckScript,'approval');if(($after['legalTextsInFingerprint']??null)!==true)throw new RuntimeException('NEW_MERCHANT_POLICY_CODE_NOT_ACTIVE_IN_CLI');$this->approvalNotice($this->state['approvalBefore']??null,$after);
        $app=$this->helper($this->appStateScript,'state');if(!in_array($app['storefrontFlag'],['absent','0'],true))throw new RuntimeException('STOREFRONT_DB_SWITCH_NOT_ABSENT_OR_0');
        $scan=LeanGates::newLogErrors($this->active().'/storage/logs',$this->state['logBaseline']);$this->emit('LARAVEL_LOG_SINCE_INSTALL',$scan);if($scan['newErrorLines']!==0)throw new RuntimeException('NEW_LARAVEL_LOG_ERRORS_SINCE_INSTALL');
        $tree=LeanGates::verifyTree($this->active(),$this->installedManifest(),self::CONFIG_SHA,$this->state['chain']['htaccessSHA256'],self::MANIFEST_FILES+12);$this->emit('ACTIVE_TREE_AFTER_INSTALL',$tree+['manifestPlusOverlay'=>true]);
        $this->save('INSTALLED_PASS');
    }

    /**
     * Puts every backed-up byte back (reverse order; a modified file only when it holds the payload, a new file removed only
     * when it holds the payload; anything foreign is reported and never overwritten), restores the route cache bytes, clears
     * and re-caches views like before, re-invalidates, waits and smoke-tests the old state.
     */
    private function restore(string $reason):bool
    {
        $this->emit('OVERLAY_ROLLBACK',['reason'=>$reason]);$problems=[];try{$this->save('ROLLBACK_INTENT');}catch(Throwable $e){$problems[]=self::code($e);}
        try{$this->cleanupRecorded();}catch(Throwable $e){$problems[]=self::code($e);}
        $backup=$this->state['backup']??null;$restored=0;$removed=0;$routesDone=false;
        if(is_array($backup)){
            $group=null;$pause=max(1,min(61,(int)($this->state['groupPauseSeconds']??3)));
            foreach(array_reverse($this->plan) as $s){
                if($group!==null&&$group!==$s['group']&&$restored+$removed>0)sleep($pause);$group=$s['group'];
                try{
                    $c=$this->classify($s);$t=$this->target($s);
                    if($s['kind']==='modified'){
                        if($c['state']==='payload'){$b=$backup['files'][$s['n']];$bytes=AtomicFile::readPrivate($b['copy'],$b['sha256']);OverlayFiles::replace($t,$bytes,$b['mode'],$b['gid'],$this->recordTemp(OverlayFiles::tempFor($t)),$s['payloadSHA256']);$restored++;$this->changed();}
                        elseif($c['state']!=='pre')throw new RuntimeException('ROLLBACK_FOREIGN_CONTENT_NOT_OVERWRITTEN_'.$s['n']);
                    }elseif($c['state']==='payload'){OverlayFiles::removeExact($t,$s['payloadSHA256']);$removed++;$this->changed();}
                    elseif($c['state']!=='absent')throw new RuntimeException('ROLLBACK_FOREIGN_NEW_FILE_NOT_REMOVED_'.$s['n']);
                }catch(Throwable $e){$problems[]=self::code($e);}
                if($s['group']==='C')try{$this->restoreRouteCache($backup);$routesDone=true;}catch(Throwable $e){$problems[]=self::code($e);}
            }
            foreach(array_reverse($this->state['createdDirs']??[]) as $dir)if(is_dir($dir)&&!is_link($dir)&&(scandir($dir)?:[])===['.','..']&&!rmdir($dir))$problems[]='CREATED_DIRECTORY_NOT_REMOVED';
            try{$this->cleanupRecorded();$this->refreshViews();}catch(Throwable $e){$problems[]=self::code($e);}
        }
        $this->persist();$this->emit('ROLLBACK_FILES',['modifiedRestored'=>$restored,'newRemoved'=>$removed,'routeCacheRestored'=>$routesDone,'problems'=>$problems]);
        if($problems===[])try{
            $t=$this->targets();if(!$t['preState'])throw new RuntimeException('ROLLBACK_TARGETS_NOT_PRE_STATE');$this->routesCheck(false);
            $first=$this->probeRun('restored-invalidate',false,true,false);$this->hold('restored',(int)$first['holdSeconds']);$this->smokes(false);$this->probeRun('restored-fresh',false,false,true);
            $tree=LeanGates::verifyTree($this->active(),$this->manifest(),self::CONFIG_SHA,$this->state['chain']['htaccessSHA256'],self::MANIFEST_FILES);$this->emit('ACTIVE_TREE_AFTER_ROLLBACK',$tree+['equalsManifest'=>true]);
        }catch(Throwable $e){$problems[]=self::code($e);}
        if($problems!==[]){
            try{$this->save('ROLLBACK_INCOMPLETE');}catch(Throwable){$this->emit('ROLLBACK_STATE_UNWRITABLE','FIX_DISK_OR_PERMISSIONS_THEN_RUN_THE_SAME_COMMAND');}
            $this->recoveryNote(array_values(array_unique($problems)));return false;
        }
        $this->save('ROLLED_BACK');$this->emit('ROLLED_BACK',['files'=>'EXACT_PRIVATE_COPIES','newFiles'=>'REMOVED','routeCache'=>$this->state['cache']['routeCacheFiles']===[]?'ABSENT_AS_BEFORE':'EXACT_PRIVATE_COPY','tree'=>'EQUALS_8531_FILE_MANIFEST','storefront'=>'CLOSED']);return true;
    }

    private function restoreRouteCache(array $backup):void
    {
        $now=$this->routeCacheFiles();$before=$backup['routeCache']??[];
        foreach($now as $name=>$sha)if(!isset($before[$name])){$t=$this->active().'/bootstrap/cache/'.$name;OverlayFiles::removeExact($t,$sha);}
        foreach($before as $name=>$b){$t=$this->active().'/bootstrap/cache/'.$name;$bytes=AtomicFile::readPrivate($b['copy'],$b['sha256']);if(isset($now[$name])){if($now[$name]!==$b['sha256'])OverlayFiles::replace($t,$bytes,$b['mode'],$b['gid'],$this->recordTemp(OverlayFiles::tempFor($t)),$now[$name]);}else OverlayFiles::create($t,$bytes,$b['mode'],$this->recordTemp(OverlayFiles::tempFor($t)));}
        $this->cleanupRecorded();$this->changed();
    }

    private function recoveryNote(array $problems):void
    {
        $files=[];foreach($this->plan as $s){$c=$this->classify($s);$b=$this->state['backup']['files'][$s['n']]??null;$files[]=['n'=>$s['n'],'path'=>$s['path'],'now'=>$c['state'],'backupCopy'=>$b['copy']??null,'mode'=>isset($b['mode'])?sprintf('%04o',$b['mode']):null];}
        $this->emit('RECOVERY_NOTE',['kind'=>'ROLLBACK_INCOMPLETE','problems'=>$problems,'files'=>$files,'backupDirectory'=>$this->state['backup']['directory']??null,'privateCopies'=>'KEPT_0600','next'=>'FIX_THE_REPORTED_CAUSE_THEN_RUN_THE_SAME_COMMAND_IT_RETRIES_THE_ROLLBACK_FIRST','manualOnlyIfTheSameCommandKeepsFailing'=>'OWNER_COMMAND_LINES_BELOW']);
        foreach($files as $f){$t=$this->active().'/'.$f['path'];if($f['now']==='payload'&&$f['backupCopy']!==null)echo 'OWNER_COMMAND: install -m '.$f['mode'].' '.$f['backupCopy'].' '.$t.PHP_EOL;elseif($f['now']==='payload')echo 'OWNER_COMMAND: rm -f '.$t.PHP_EOL;}
    }

    private function hold2(string $phase,array $report):int {$this->save($phase);$this->emit('HOLD_NO_MUTATION',['phase'=>$phase,'blockers'=>$report['blockers']]);return 2;}

    private function finish(string $final):int
    {
        $this->final=$final;$this->emit('B1146_RESULT',['overlay'=>'PR152_LEGAL_PAGES_21_FILES','newFiles'=>12,'modifiedFiles'=>9,'tests'=>'NOT_INSTALLED','storefront'=>'CLOSED_DB_SWITCH_ABSENT_OR_0','migrations'=>'NONE','env'=>'UNCHANGED','config'=>'UNCHANGED','opcache'=>'TARGETED_PER_FILE_INVALIDATION_ONLY','backupDirectory'=>$this->state['backup']['directory']??'NONE_ALREADY_INSTALLED_BEFORE_THIS_TOOL_STATE']);
        $this->emit('OWNER_NEXT_STEPS',['1_LOG_IN_TO_CMS_ALD1N_COM_AS_SUPERADMIN','2_OPEN_/admin/settings/public-storefront_AND_CHECK_THE_ON_CHECKLIST_AND_THE_LEGAL_TEXT_LINKS','3_REVIEW_THE_LEGAL_TEXTS_AND_CONFIRM_THE_MERCHANT_APPROVAL_AGAIN','4_THE_STOREFRONT_STAYS_OFF_UNTIL_YOU_TURN_IT_ON','ROLLBACK_IF_NEEDED=bash /home/icaffeco/ald1n-project/incoming/b1146-legal-overlay.sh --rollback']);
        $this->emit('DO_NOT_RERUN_B1144_OR_B1145_AFTER_B1146','THEIR_MANIFEST_GATES_NOW_SEE_THE_9_MODIFIED_FILES');return 0;
    }

    private function alreadyInstalled():int
    {
        $p=$this->preflight('installed');if($p['blockers']!==[]){$this->emit('HOLD_INSTALLED_REVERIFY',$p['blockers']);return 2;}
        $this->emit('ALREADY_INSTALLED_NO_REPEAT',['digestsVerified'=>count($this->plan),'treeFiles'=>self::MANIFEST_FILES+12]);echo 'OWNER_NOTICE_MERCHANT_APPROVAL=IF_NOT_DONE_YET_REVIEW_THE_TERMS_AT_/admin/settings/public-storefront_AND_CONFIRM_THE_MERCHANT_APPROVAL_AGAIN'.PHP_EOL;return $this->finish('PASS_ALREADY_INSTALLED');
    }

    public function run():int
    {
        try{$this->open();$existing=$this->loadExisting();if(!$existing||($this->mode==='install'&&$this->state['phase']==='ROLLED_BACK'))$this->begin();}catch(Throwable $e){$this->emit('HOLD_BEFORE_ANY_MUTATION',self::code($e));return 2;}
        try{
            $phase=$this->state['phase'];
            if($this->mode==='rollback'){
                if(!in_array($phase,['INSTALLED_PASS','ROLLBACK_INCOMPLETE','ROLLBACK_INTENT','ACCEPTANCE_INTENT','FILES_INSTALLED','INSTALL_INTENT','BACKED_UP'],true)){$this->emit('HOLD_NOTHING_TO_ROLL_BACK',['phase'=>$phase]);return 2;}
                $this->state['chain']??=$this->chain();if(!$this->restore('OWNER_REQUESTED_ROLLBACK'))return 2;$this->final='PASS_ROLLED_BACK_BY_OWNER';$this->emit('OWNER_NOTICE','THE_OLD_CODE_IS_ACTIVE_AGAIN_THE_LEGAL_PAGES_ARE_GONE_A_LATER_RUN_OF_THE_SAME_COMMAND_WITHOUT_OPTIONS_INSTALLS_AGAIN');return 0;
            }
            if($phase==='INSTALLED_PASS')return $this->alreadyInstalled();
            if(in_array($phase,self::IN_FLIGHT,true)){$this->emit('RESUMED_INTERRUPTED_INSTALL',['phase'=>$phase]);$this->state['chain']??=$this->chain();$ok=$this->restore('RESUMED_INTERRUPTED_'.$phase);$this->final=$ok?'HOLD_ROLLED_BACK_OLD_STATE_VERIFIED':'HOLD_ROLLBACK_INCOMPLETE';return 2;}
            if(!in_array($phase,['PREFLIGHT','HOLD_PREFLIGHT_BLOCKED'],true))throw new RuntimeException('UNKNOWN_SAVED_PHASE');
            $this->cleanupRecorded();$p=$this->preflight('fresh');
            if($p['blockers']===['ALREADY_INSTALLED']){$this->emit('ALREADY_INSTALLED_WITHOUT_B1146_STATE','REVERIFYING');return $this->alreadyInstalled();}
            if($p['blockers']!==[])return $this->hold2('HOLD_PREFLIGHT_BLOCKED',$p);
            $this->backup($p);
            try{$this->census();$this->install();$this->accept();}catch(Throwable $e){$code=self::code($e);$this->emit('INSTALL_OR_ACCEPTANCE_FAILED',$code);$ok=$this->restore($code);$this->final=$ok?'HOLD_ROLLED_BACK_OLD_STATE_VERIFIED':'HOLD_ROLLBACK_INCOMPLETE';return 2;}
            return $this->finish('PASS_INSTALLED_LEGAL_OVERLAY_STOREFRONT_CLOSED');
        }catch(Throwable $e){$this->emit('HOLD_CODE_DETAIL',self::code($e));return 2;}
    }
}

/** Same-directory temp + fsync + rename (replace) or temp + fsync + link (create, never clobbers). Mode and group are kept. */
final class OverlayFiles
{
    public static function tempFor(string $target):string {return dirname($target).'/.'.basename($target).'.b1146-'.bin2hex(random_bytes(8)).'.tmp';}
    public static function isOwnTemp(string $path):bool {return preg_match('~/\.[A-Za-z0-9_.-]+\.b1146-[a-f0-9]{16}\.tmp$~D',$path)===1;}

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
    public static function create(string $target,string $bytes,int $mode,string $temp):array
    {
        if(!self::isOwnTemp($temp)||dirname($temp)!==dirname($target)||is_link(dirname($target)))throw new RuntimeException('OVERLAY_CREATE_SCOPE');clearstatcache(true,$target);if(file_exists($target)||is_link($target))throw new RuntimeException('OVERLAY_NEW_TARGET_EXISTS');
        self::writeTemp($temp,$bytes,$mode);if(!@link($temp,$target)){@unlink($temp);throw new RuntimeException(file_exists($target)?'OVERLAY_NEW_TARGET_EXISTS':'OVERLAY_LINK');}@unlink($temp);$synced=self::syncDirectory(dirname($target));
        clearstatcache(true,$target);if(is_link($target)||!is_file($target)||hash_file('sha256',$target)!==hash('sha256',$bytes)||stat($target)['nlink']!==1)throw new RuntimeException('OVERLAY_RESULT_VERIFY');
        return ['op'=>'LINK_NEW','directorySynced'=>$synced,'mode'=>sprintf('%04o',$mode)];
    }

    /** Removes $target only while it holds exactly $sha256. */
    public static function removeExact(string $target,string $sha256):void
    {
        clearstatcache(true,$target);if(is_link($target)||!is_file($target)||hash_file('sha256',$target)!==$sha256)throw new RuntimeException('OVERLAY_REMOVE_FOREIGN_CONTENT');if(!unlink($target))throw new RuntimeException('OVERLAY_REMOVE');self::syncDirectory(dirname($target));
    }
}
