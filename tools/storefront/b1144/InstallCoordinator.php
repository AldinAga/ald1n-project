<?php
declare(strict_types=1);

/**
 * B1144 lean controlled OFF install. No migrations, environment edits, PHP profile overlays, cPanel calls or public activation.
 * Order: admission -> filesystem scope -> mtime diagnostic -> owned barrier -> scheduler pause -> narrow drain ->
 * local SHA-256 verification -> journaled switch -> web freshness -> CLI boot -> lift -> smokes and log scan -> commit.
 * Any failure after the switch rolls back automatically; the commit point is after the post-lift smokes.
 */
final class InstallCoordinator
{
    private const SOURCE='ef3f927b77ba573881c2b6dba341732c417e6725';
    private const MAIN='81e583a78c73745fd2e0d73f7415e4842ad89cf1';
    private const CMS_TREE='da8ac0c953e3c6741b921bb9fa660f80d247c55e';
    public const PENDING=['2026_10_23_020000_create_public_order_accesses','2026_10_23_020100_create_public_checkout_attempts','2026_10_24_030000_add_retail_customer_role'];
    public const SMOKES=[['/login',200],['/up',200],['/katalog',404],['/registracija',404],['/korpa',404]];
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

    public function __construct(private string $root,private string $home)
    {
        if($root!=='/home/icaffeco/ald1n-project'||$home!=='/home/icaffeco'||realpath($root)!==$root||realpath($home)!==$home||!function_exists('posix_geteuid')||!extension_loaded('curl')||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('HOST_PROFILE');
        $private=$home.'/.ald1n-b1144-state';if(!is_dir($private)&&!mkdir($private,0700))throw new RuntimeException('PRIVATE_STATE_CREATE');
        if(is_link($private)||realpath($private)!==$private||(fileperms($private)&0777)!==0700||fileowner($private)!==posix_geteuid())throw new RuntimeException('PRIVATE_STATE_CONFINEMENT');
        $this->lock=fopen($private.'/installer.lock','c');if($this->lock===false||!flock($this->lock,LOCK_EX|LOCK_NB))throw new RuntimeException('ANOTHER_INSTALLER_ACTIVE');chmod($private.'/installer.lock',0600);$this->pointer=$private.'/current.json';
        putenv('GIT_OPTIONAL_LOCKS=0');$this->web=new LeanWeb();
    }

    private function emit(string $key,mixed $value):void {echo $key.'='.json_encode($value,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;}
    private function identity(string $path):array {clearstatcache(true);$s=stat($path);if(!is_array($s)||is_link($path)||realpath($path)!==$path)throw new RuntimeException('PATH_IDENTITY');return ['dev'=>$s['dev'],'ino'=>$s['ino']];}
    private function active():string {return $this->root.'/apps/cms/current';}
    private function marker():string {return bin2hex(random_bytes(8));}

    private function atomicJSON(string $path,array $data):void
    {
        if(realpath(dirname($path))!==dirname($path)||is_link($path))throw new RuntimeException('STATE_CONFINEMENT');$temporary=$path.'.'.bin2hex(random_bytes(8)).'.next';$handle=fopen($temporary,'x');if($handle===false)throw new RuntimeException('STATE_CREATE');chmod($temporary,0600);$text=json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);if(fwrite($handle,$text)!==strlen($text))throw new RuntimeException('STATE_WRITE');fflush($handle);if(function_exists('fsync'))fsync($handle);fclose($handle);if(!rename($temporary,$path))throw new RuntimeException('STATE_RENAME');
    }

    private function save(string $phase):void {$this->state['phase']=$phase;$this->atomicJSON($this->state['private'].'/state.json',$this->state);$this->atomicJSON($this->pointer,['schemaVersion'=>1,'private'=>$this->state['private'],'id'=>$this->state['id']]);$this->emit('RELEASE_PHASE',$phase);}

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
        if($result['rc']!==0||!is_array($receipt)||($receipt['status']??'')!=='ok')throw new RuntimeException('SCHEDULER_'.$action.'_HOLD');
        $this->emit('TECHNICAL_SCHEDULER_CACHE',['action'=>$action,'rc'=>$result['rc'],'oldApplicationBootstrap'=>true,'directBusinessOrSchemaQueries'=>false]);return $receipt['receipt'];
    }

    private function loadManifest():void
    {
        $verification=$this->home.'/.ald1n-stage/b1131-ef3f927.6hh7mU/verification';$path=$verification.'/runtime-manifest.json';if(is_link($path)||realpath($path)!==$path||hash_file('sha256',$path)!=='d737387e60a981b3b404e0980d9b70cb95b6e1d506a39211981d49dcd12be4c6')throw new RuntimeException('RUNTIME_MANIFEST_INTEGRITY');
        $this->manifest=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$directories=$verification.'/runtime-directories.json';
        if(is_link($directories)||realpath($directories)!==$directories||hash_file('sha256',$directories)!=='1e790c20fb9712d416399975d6cbcce3be1bd2dc12ede59a95f2e1cc75ea7e3e')throw new RuntimeException('DIRECTORY_MANIFEST_INTEGRITY');$this->directoryDocument=json_decode(file_get_contents($directories),true,512,JSON_THROW_ON_ERROR);
    }

    /** Pure until B1144Recovery::commit: admission, filesystem scope and the mtime diagnostic write nothing. */
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

    private function drain():array {$result=LeanGates::drain($this->active().'/public/index.php',$this->drainSeconds);$this->emit('CMS_DRAIN',$result);return $result;}

    private function probeRoots():array {$p=$this->state['plan'];return [$p['active'],$p['candidate'],$p['rollback'],$p['failed']];}

    /** Every recorded probe is removed from whichever tree now holds it; foreign bytes at a recorded name are a HOLD. */
    private function removeRecordedProbes():void
    {
        $changed=false;
        foreach($this->state['probes']??[] as $index=>$probe){if(($probe['removed']??false)===true)continue;foreach($this->probeRoots() as $base)if(is_dir($base.'/public'))FreshnessProbe::remove($base.'/public',$probe['name'],$probe['sha256']);$this->state['probes'][$index]['removed']=true;$changed=true;}
        if($changed)$this->atomicJSON($this->state['private'].'/state.json',$this->state);
    }

    /** Web freshness for the tree now at the active path. The probe name and token never reach output or the report. */
    private function freshness(string $label):array
    {
        $public=$this->active().'/public';$this->removeRecordedProbes();FreshnessProbe::assertNoLeftover($public);
        $which=$label==='installed'?'new':'old';$expected=[];foreach($this->state['probeFiles'] as $relative=>$digests)$expected[$relative]=$digests[$which];
        $probe=FreshnessProbe::plan($this->state['id'],$this->probeRoots(),array_keys($expected));
        $this->state['probes'][]=['name'=>$probe['name'],'sha256'=>$probe['sha256'],'label'=>$label];$this->save('FRESHNESS_PROBE_INTENT_'.strtoupper($label));
        try{
            FreshnessProbe::write($public.'/'.$probe['name'],$probe['source']);$samples=[];$wait=0;
            for($n=0;$n<$this->probeSamples;$n++){
                $response=$this->web->probe($probe['name'],$probe['token'],true);$route=is_string($response['_route']??null)?$response['_route']:'UNKNOWN';unset($response['_route']);
                $summary=FreshnessProbe::validate($response,$this->state['id'],$expected,$this->allowedSapis,true);$summary['route']=$route;$samples[]=$summary;if($summary['opcacheEnabled'])$wait=max($wait,$summary['waitSeconds']);
            }
            if($wait>0)sleep($wait);
            $result=['label'=>$label,'samples'=>$samples,'waitedSeconds'=>$wait,'targetedInvalidationOnly'=>true,'globalReset'=>false,'probeRemoved'=>true];
        }finally{$this->removeRecordedProbes();}
        $this->state['freshness'][$label]=$result;$this->emit('WEB_FRESHNESS',$result);return $result;
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
            $this->emit('RECOVERY_OF_EXISTING_TRANSACTION',$this->state['id']);return $this->recover('OPERATOR_RESUMED_INTERRUPTED_INSTALL');
        }
        try{$this->prepare();}catch(Throwable $error){
            if(!$this->state){$code=$error->getMessage();if(!preg_match('/^[A-Z0-9_]+$/D',$code))$code=get_class($error);$this->emit('HOLD_BEFORE_LIVE_APPLICATION_MUTATION',$code);return 2;}
            return $this->recover($error->getMessage());
        }
        return $this->execute();
    }

    private function execute():int
    {
        try{
            $plan=$this->state['plan'];$active=$this->active();
            $new=ReleasePrimitives::ownMaintenance($plan['candidate'].'/storage',$this->state['owner'],$this->state['maintenanceExpires'],$this->state['maintenanceStart'],null);
            $old=$new;$old['storage']=$active.'/storage';$identity=$this->identity($old['storage']);$old['storageDevice']=$identity['dev'];$old['storageInode']=$identity['ino'];$old['files']=[];foreach($new['files'] as $path=>$digest)$old['files'][$active.'/storage/framework/'.basename($path)]=$digest;
            $this->state['newMaintenance']=$new;$this->state['oldMaintenance']=$old;$this->save('CANDIDATE_READY');
            $this->state['schedulerPauseAttempted']=true;$this->save('SCHEDULER_PAUSE_INTENT');$this->state['scheduler']=$this->scheduler('pause');$this->save('SCHEDULER_PAUSED');
            $this->save('MAINTENANCE_INTENT');$actual=ReleasePrimitives::ownMaintenance($active.'/storage',$this->state['owner'],$this->state['maintenanceExpires'],$this->state['maintenanceStart'],null);if($actual['files']!==$this->state['oldMaintenance']['files'])throw new RuntimeException('MAINTENANCE_PLAN_MISMATCH');$this->save('MAINTENANCE_OWNED');
            $this->web->status('/login',503,$this->state['oldMaintenance']['ownerSHA256'],$this->marker(),10);$this->scheduler('verify');$this->save('BARRIER_OBSERVED');
            $this->state['drain']=$this->drain();$this->save('CMS_DRAINED');
            $this->state['treeVerification']=LeanGates::verifyTree($plan['candidate'],$this->manifest,$this->state['clampedConfigSHA256'],$this->state['preserved']['customHtaccessSHA256'],count($this->manifest['files']));
            if(hash_file('sha256',$active.'/.env')!==$this->state['envSHA256'])throw new RuntimeException('PRIVATE_ENV_CHANGED');$this->assertGit();$this->emit('CANDIDATE_LOCAL_SHA256',$this->state['treeVerification']);$this->save('CANDIDATE_LOCAL_VERIFIED');
            $this->state['logBaseline']=LeanGates::logBaseline($active.'/storage/logs');$this->save('LOG_BASELINE_RECORDED');
            ReleasePrimitives::switchJournaled($active,$plan['candidate'],$plan['rollback'],$this->state['journal'],$this->state['owner']);$this->save('SWITCHED');$this->warmup();$this->verifyInstalledFiles();
            $this->freshness('installed');$this->save('FRESHNESS_VERIFIED');
            $this->cliBoot();$this->verifyInstalledFiles();$this->scheduler('verify');$this->save('CLI_BOOT_VERIFIED');
            $this->save('BARRIER_LIFT_INTENT');$this->clearMaintenance($this->state['oldMaintenance'],$plan['rollback'].'/storage');$this->clearMaintenance($this->state['newMaintenance'],$active.'/storage');$this->save('BARRIER_LIFTED_SMOKE_PENDING');
            $this->postLiftSmoke();$this->verifyInstalledFiles();$this->save('COMMITTED_OFF');return $this->finishCommitted();
        }catch(Throwable $error){return $this->recover($error->getMessage());}
    }

    private function finishCommitted():int
    {
        try{
            $this->verifyInstalledFiles();$this->removeRecordedProbes();
            $this->clearMaintenance($this->state['oldMaintenance'],$this->state['plan']['rollback'].'/storage');$this->clearMaintenance($this->state['newMaintenance'],$this->active().'/storage');
            if($this->state['phase']!=='SCHEDULER_RESTORED'){$this->save('PUBLIC_RELEASED');$this->scheduler('release');$this->save('SCHEDULER_RESTORED');}
            $marker=$this->marker();$this->web->status('/login',200,null,$marker);$this->web->status('/katalog',404,null,$marker);$this->assertGit();$this->save('CLOSED_INSTALL');ReleasePrimitives::journalState($this->state['journal'],$this->state['owner'],'CLOSED');
            $this->emit('CONTROLLED_CMS_INSTALL','PASS_MAIN_RUNTIME_WITH_REVIEWED_OFF_CLAMP');$this->emit('PUBLIC_CHECKOUT','UNCONDITIONALLY_OFF_CONFIG_FALSE_NO_ENV_OR_DB_FLAG_CHANGE');$this->emit('PERMANENT_PRIVATE_DATA_DEPENDENCY_DO_NOT_PRUNE',$this->state['plan']['rollback']);$this->emit('COMPLETE_DIRECTORY_SWITCH','TWO_RENAMES_WITH_OWNED_MAINTENANCE_AND_RECOVERY_NOT_A_SINGLE_ATOMIC_TRANSACTION');return 0;
        }catch(Throwable $error){$this->emit('HOLD_INSTALLED_OFF_FINISH_PENDING',$error->getMessage());return 2;}
    }

    /** Restored original code: barrier back on the old tree, then web freshness against the old file hashes. */
    private function verifyRestored():void
    {
        if(is_array($this->state['oldMaintenance']))$this->ensureBarrier($this->state['oldMaintenance'],$this->active().'/storage');
        $this->freshness('restored');
    }

    private function finishOriginal(bool $switched):int
    {
        $plan=$this->state['plan'];$active=$this->active();if($this->identity($active)!==$plan['oldIdentity'])throw new RuntimeException('ORIGINAL_CODE_REQUIRED_BEFORE_RELEASE');
        $other=is_dir($plan['failed'])?$plan['failed']:$plan['candidate'];if($this->identity($other)!==$plan['candidateIdentity'])throw new RuntimeException('RECOVERY_CANDIDATE_IDENTITY');
        $this->state['originalRelease']=['switched'=>$switched];$this->save('ORIGINAL_RELEASE_INTENT');
        if(is_array($this->state['newMaintenance']))$this->clearMaintenance($this->state['newMaintenance'],$other.'/storage');if(is_array($this->state['oldMaintenance']))$this->clearMaintenance($this->state['oldMaintenance'],$active.'/storage');
        $this->save('ORIGINAL_RELEASED_PENDING_HTTP');
        try{$this->web->status('/login',200,null,$this->marker());}catch(Throwable $error){$this->save('ORIGINAL_RELEASE_AVAILABILITY_HOLD');throw $error;}
        if(($this->state['schedulerPauseAttempted']??false)||$this->state['scheduler']!==null)$this->scheduler('release');$this->assertGit();$this->save($switched?'ROLLED_BACK':'ROLLED_BACK_PRE_SWITCH');
        $this->emit($switched?'ROLLBACK':'ORIGINAL_APPLICATION_CODE_VENDOR_UNTOUCHED',$switched?'ORIGINAL_CODE_PRIVATE_DATA_RESTORED_FRESHNESS_AND_HTTP_VERIFIED':true);return 2;
    }

    private function recover(string $reason):int
    {
        $this->emit('INSTALL_HOLD_REASON',$reason);
        try{
            $this->removeRecordedProbes();$plan=$this->state['plan'];$active=$this->active();$current=is_dir($active)?$this->identity($active):null;
            if($current===$plan['oldIdentity']&&!is_dir($plan['rollback'])){
                $switched=is_dir($plan['failed']);if(isset($this->state['originalRelease'])&&($this->state['originalRelease']['switched']??null)!==$switched)throw new RuntimeException('RESTORE_PHASE_TOPOLOGY_MISMATCH');
                if($switched)$this->verifyRestored();return $this->finishOriginal($switched);
            }
            if($current===$plan['candidateIdentity']){
                // Post-switch (including after the lift): barrier back on both trees, scheduler still paused, CMS drained.
                $this->save('ROLLBACK_BARRIER_INTENT');$this->ensureBarrier($this->state['newMaintenance'],$active.'/storage');if(is_dir($plan['rollback']))$this->ensureBarrier($this->state['oldMaintenance'],$plan['rollback'].'/storage');
                $this->save('ROLLBACK_BARRIER_OWNED');$this->scheduler('verify');$this->drain();
            }
            ReleasePrimitives::recoverSwitch($plan,$this->state['journal'],$this->state['owner'],$plan['failed']);$this->save('OLD_RESTORED_MAINTENANCE_HELD');
            $switched=is_dir($plan['failed']);if($switched)$this->verifyRestored();return $this->finishOriginal($switched);
        }catch(Throwable $error){if(($this->state['phase']??'')!=='ORIGINAL_RELEASE_AVAILABILITY_HOLD')$this->save('HOLD_RECOVERY_OWNED_MAINTENANCE_RETAINED');$this->emit('RECOVERY_HOLD',$error->getMessage());return 2;}
    }
}
