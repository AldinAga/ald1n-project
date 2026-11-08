<?php
declare(strict_types=1);

/** Controlled OFF installation. No migrations, direct DB probes, environment edits or public activation. */
final class InstallCoordinator
{
    private const SOURCE='ef3f927b77ba573881c2b6dba341732c417e6725';
    private const MAIN='81e583a78c73745fd2e0d73f7415e4842ad89cf1';
    private const CMS_TREE='da8ac0c953e3c6741b921bb9fa660f80d247c55e';
    private array $state=[];
    private string $pointer;
    private mixed $lock;
    private ?HostWebControl $web=null;
    private array $manifest;
    private array $directoryDocument;

    public function __construct(private string $root,private string $home,private string $webLibrary)
    {
        if($root!=='/home/icaffeco/ald1n-project'||$home!=='/home/icaffeco'||realpath($root)!==$root||realpath($home)!==$home||!function_exists('posix_geteuid')||!extension_loaded('curl')||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('HOST_PROFILE');
        $private=$home.'/.ald1n-b1143-state';if(!is_dir($private)&&!mkdir($private,0700))throw new RuntimeException('PRIVATE_STATE_CREATE');
        if(is_link($private)||realpath($private)!==$private||(fileperms($private)&0777)!==0700||fileowner($private)!==posix_geteuid())throw new RuntimeException('PRIVATE_STATE_CONFINEMENT');
        $this->lock=fopen($private.'/installer.lock','c');if($this->lock===false||!flock($this->lock,LOCK_EX|LOCK_NB))throw new RuntimeException('ANOTHER_INSTALLER_ACTIVE');chmod($private.'/installer.lock',0600);$this->pointer=$private.'/current.json';
        putenv('GIT_OPTIONAL_LOCKS=0');
    }

    private function emit(string $key,mixed $value):void {echo $key.'='.json_encode($value,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;}
    private function identity(string $path):array {clearstatcache(true);$s=stat($path);if(!is_array($s)||is_link($path)||realpath($path)!==$path)throw new RuntimeException('PATH_IDENTITY');return ['dev'=>$s['dev'],'ino'=>$s['ino']];}
    private function active():string {return $this->root.'/apps/cms/current';}

    private function atomicJSON(string $path,array $data):void
    {
        if(realpath(dirname($path))!==dirname($path)||is_link($path))throw new RuntimeException('STATE_CONFINEMENT');$temporary=$path.'.'.bin2hex(random_bytes(8)).'.next';$handle=fopen($temporary,'x');if($handle===false)throw new RuntimeException('STATE_CREATE');chmod($temporary,0600);$text=json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);if(fwrite($handle,$text)!==strlen($text))throw new RuntimeException('STATE_WRITE');fflush($handle);if(function_exists('fsync'))fsync($handle);fclose($handle);if(!rename($temporary,$path))throw new RuntimeException('STATE_RENAME');
    }

    private function save(string $phase):void {$this->state['phase']=$phase;$this->atomicJSON($this->state['private'].'/state.json',$this->state);$this->atomicJSON($this->pointer,['schemaVersion'=>1,'private'=>$this->state['private'],'id'=>$this->state['id']]);$this->emit('RELEASE_PHASE',$phase);}

    /** No raw application output leaves the host process; report only audited command identity and real RC. */
    private function command(array $argv,?string $cwd=null,int $timeout=120):array
    {
        $pipes=[];$process=proc_open($argv,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd);if(!is_resource($process))throw new RuntimeException('CHILD_START');fclose($pipes[0]);stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$stdout='';$stderrBytes=0;$started=microtime(true);$last=null;
        do{$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);$stdout.=$out;$stderrBytes+=strlen($err);if(strlen($stdout)>4194304||$stderrBytes>4194304){proc_terminate($process);throw new RuntimeException('CHILD_OUTPUT_BOUND');}$last=proc_get_status($process);if(microtime(true)-$started>$timeout){proc_terminate($process);throw new RuntimeException('CHILD_TIMEOUT');}if($last['running'])usleep(100000);}while($last['running']);
        $stdout.=stream_get_contents($pipes[1]);$stderrBytes+=strlen(stream_get_contents($pipes[2]));fclose($pipes[1]);fclose($pipes[2]);$closed=proc_close($process);$rc=$last['exitcode']>=0?$last['exitcode']:$closed;return ['rc'=>$rc,'stdout'=>$stdout,'stderrBytes'=>$stderrBytes];
    }

    private function git(array $arguments):string {$r=$this->command(array_merge(['git','-C',$this->root],$arguments),null,30);if($r['rc']!==0)throw new RuntimeException('GIT_READ_FAILED');return trim($r['stdout']);}
    private function gitSnapshot():array {$index=$this->git(['rev-parse','--git-path','index']);if(!str_starts_with($index,'/'))$index=$this->root.'/'.$index;return ['head'=>$this->git(['rev-parse','HEAD']),'branch'=>$this->git(['rev-parse','--abbrev-ref','HEAD']),'index'=>$index,'indexSHA256'=>hash_file('sha256',$index)];}
    private function assertGit():void {$now=$this->gitSnapshot();$old=$this->state['git'];if($now['head']!==$old['head']||$now['indexSHA256']!==$old['indexSHA256'])throw new RuntimeException('HOST_HEAD_OR_INDEX_CHANGED');}

    private function scheduler(string $action):array
    {
        $old=is_dir($this->state['plan']['rollback'])?$this->state['plan']['rollback']:$this->active();
        if($this->identity($old)!==$this->state['plan']['oldIdentity'])throw new RuntimeException('SCHEDULER_OLD_RELEASE_IDENTITY');
        $path=$this->state['private'].'/scheduler-command.json';$this->atomicJSON($path,['base'=>$old,'action'=>$action,'owner'=>$this->state['owner'],'identity'=>$this->state['plan']['oldIdentity'],'envSHA256'=>$this->state['envSHA256'],'previous'=>$this->state['scheduler']??null]);
        $result=$this->command([PHP_BINARY,'-d','opcache.enable=0','-d','opcache.enable_cli=0',$this->root.'/incoming/b1134-scheduler.php',$path],null,120);$receipt=json_decode($result['stdout'],true);
        if($result['rc']!==0||!is_array($receipt)||($receipt['status']??'')!=='ok')throw new RuntimeException('SCHEDULER_'.$action.'_HOLD');
        $this->emit('TECHNICAL_SCHEDULER_CACHE', ['action'=>$action,'rc'=>$result['rc'],'oldApplicationBootstrap'=>true,'directBusinessOrSchemaQueries'=>false]);return $receipt['receipt'];
    }

    private function loadManifest():void
    {
        $verification=$this->home.'/.ald1n-stage/b1131-ef3f927.6hh7mU/verification';$path=$verification.'/runtime-manifest.json';if(is_link($path)||realpath($path)!==$path||hash_file('sha256',$path)!=='d737387e60a981b3b404e0980d9b70cb95b6e1d506a39211981d49dcd12be4c6')throw new RuntimeException('RUNTIME_MANIFEST_INTEGRITY');
        $this->manifest=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$directories=$verification.'/runtime-directories.json';
        if(is_link($directories)||realpath($directories)!==$directories||hash_file('sha256',$directories)!=='1e790c20fb9712d416399975d6cbcce3be1bd2dc12ede59a95f2e1cc75ea7e3e')throw new RuntimeException('DIRECTORY_MANIFEST_INTEGRITY');$this->directoryDocument=json_decode(file_get_contents($directories),true,512,JSON_THROW_ON_ERROR);
    }

    private function prepare():void
    {
        if($this->git(['rev-parse','origin/main:apps/cms/current'])!==self::CMS_TREE)throw new RuntimeException('FRESH_MAIN_CMS_DRIFT');
        $this->state=B1143Recovery::prepare($this->root,$this->home,$this->manifest,$this->gitSnapshot());
        $topology=$this->command(['/usr/local/cpanel/bin/uapi','--output=json','DomainInfo','domains_data','format=list','hide_temporary_domains=0'],null,15);if($topology['rc']!==0||strlen($topology['stdout'])>1048576)throw new RuntimeException('CMS_DOCROOT_SCOPE_UNAVAILABLE');$this->emit('CMS_DIRECTORY_SCOPE',UncachedProfile::topology(json_decode($topology['stdout'],true,64,JSON_THROW_ON_ERROR),$this->active().'/public'));$this->state['uncached']=UncachedProfile::plan($this->active(),$this->state['plan']['candidate'],$this->state['private'],$this->state['preserved']['customHtaccessSHA256'],$this->state['id']);$this->save('PREPARED');$this->mapping();$new=ReleasePrimitives::ownMaintenance($this->state['plan']['candidate'].'/storage',$this->state['owner'],$this->state['maintenanceExpires'],$this->state['maintenanceStart'],$this->webContext());
        $old=$new;$old['storage']=$this->active().'/storage';$identity=$this->identity($old['storage']);$old['storageDevice']=$identity['dev'];$old['storageInode']=$identity['ino'];$old['files']=[];foreach($new['files'] as $path=>$digest)$old['files'][$this->active().'/storage/framework/'.basename($path)]=$digest;
        $this->state['newMaintenance']=$new;$this->state['oldMaintenance']=$old;$this->save('CANDIDATE_READY');$this->emit('B1143_PREDECESSOR_RECOVERY',$this->state['predecessor']);$this->emit('EXISTING_COMPLETE_CANDIDATE_REUSED_NO_VENDOR_RECOPY',true);$this->emit('HOST_GIT',$this->state['git']);
    }
    private function webContext():array
    {
        return ['library'=>$this->webLibrary,'envelope'=>$this->state['private'].'/web.json','frontSHA256'=>hash('sha256',$this->state['web']['token'])];
    }

    /** No public alias or standalone public PHP. The owned framework maintenance barrier dispatches actual index.php. */
    private function mapping(string $phase='preflight'):void
    {
        $incoming=$this->root.'/incoming';$digest=hash_file('sha256',$this->webLibrary);
        if(realpath($this->webLibrary)!==$incoming.'/b1143-webcache-'.$digest.'.php')throw new RuntimeException('IMMUTABLE_WEB_LIBRARY_PATH');
        if(isset($this->state['web']['librarySHA256'])&&$this->state['web']['librarySHA256']!==$digest)throw new RuntimeException('RECOVERY_REQUIRES_ORIGINAL_INSTALLER_LIBRARY');
        $this->state['web']['librarySHA256']=$digest;$key=$this->state['web']['key'];$keyPath=$this->state['private'].'/web-signing-key';
        if(file_exists($keyPath)){if(is_link($keyPath)||realpath($keyPath)!==$keyPath||(fileperms($keyPath)&0777)!==0600||file_get_contents($keyPath)!==$key)throw new RuntimeException('WEB_KEY_COLLISION');}
        else{$handle=fopen($keyPath,'x');if($handle===false)throw new RuntimeException('WEB_KEY_CREATE');chmod($keyPath,0600);if(fwrite($handle,$key)!==64)throw new RuntimeException('WEB_KEY_WRITE');fflush($handle);fclose($handle);}
        $indexSHA=null;foreach($this->manifest['files'] as $file)if($file['path']==='public/index.php')$indexSHA=$file['sha256'];
        $config=['schemaVersion'=>1,'id'=>$this->state['id'],'phase'=>$phase,'expires'=>time()+1500,'tokenSHA256'=>hash('sha256',$this->state['web']['token']),'smokeTokenSHA256'=>hash('sha256',hash_hmac('sha256','private-http-smoke',$this->state['owner'])),'ownerStamp'=>hash('sha256',$this->state['owner']),'incoming'=>$incoming,'activeRoot'=>$this->active(),'rpcPath'=>'/__ald1n_release_'.$this->state['id'],'indexSHA256'=>$indexSHA,'librarySHA256'=>$digest,'executionProfile'=>'UNCACHED_CMS','samePathFreshnessProven'=>($this->state['uncachedFreshness']['samePathFreshnessProven']??false)===true,'profileSHA256'=>$this->state['uncached']['profileSHA256']??str_repeat('0',64),'overlaySHA256'=>$this->state['uncached']['entries']['old']['overlaySHA256']??str_repeat('0',64),'allowedSapis'=>['litespeed'],'files'=>[]];
        $this->web=new HostWebControl('https://cms.ald1n.com',$this->state['private'],$config,$key,$this->state['web']['token'],hash_hmac('sha256','private-http-smoke',$this->state['owner']));
        if(isset($this->state['visibility']))$this->web->setGroup($this->state['visibility']);$this->web->setPhase($phase);
    }

    private function cleanupMapping():void
    {
        if(!isset($this->state['web']['librarySHA256']))return;
        if($this->web===null)$this->mapping('revoked');else $this->web->setPhase('revoked');
        $this->emit('AUTHENTICATED_FRONT_DISPATCH_REVOKED',['noPublicAliasEverCreated'=>true,'signedStateRevoked'=>true,'frameworkBarrierRemovalFollows'=>true,'immutableLibraryStaysIncomingOnly'=>true]);
    }

    private function existingMaintenance(array $receipt,string $storage):?array
    {
        $identity=$this->identity($storage);if($identity['dev']!==$receipt['storageDevice']||$identity['ino']!==$receipt['storageInode'])throw new RuntimeException('MAINTENANCE_STORAGE_IDENTITY');$files=[];
        foreach($receipt['files'] as $original=>$digest){$path=$storage.'/framework/'.basename($original);if(!file_exists($path)&&!is_link($path))continue;if(is_link($path)||realpath($path)!==$path||hash_file('sha256',$path)!==$digest)throw new RuntimeException('MAINTENANCE_FOREIGN_CONTENT');$files[$path]=$digest;}
        if(!$files)return null;$receipt['files']=$files;$receipt['storage']=$storage;return $receipt;
    }

    private function clearMaintenance(array $receipt,string $storage):void {$present=$this->existingMaintenance($receipt,$storage);if($present!==null)ReleasePrimitives::releaseMaintenance($present,$storage);}

    private function warmup():void
    {
        foreach(['package:discover','view:cache'] as $command){$r=$this->command([PHP_BINARY,'-d','opcache.enable=0','-d','opcache.enable_cli=0',$this->active().'/artisan',$command,'--no-interaction','--no-ansi'],$this->active(),180);$this->emit('TECHNICAL_CACHE_WARMUP',['command'=>$command,'rc'=>$r['rc'],'outputBytes'=>strlen($r['stdout']),'stderrBytes'=>$r['stderrBytes'],'rawOutputNotExported'=>true]);if($r['rc']!==0)throw new RuntimeException('CANDIDATE_TECHNICAL_WARMUP_FAILED');}
        if(file_exists($this->active().'/bootstrap/cache/config.php'))throw new RuntimeException('UNEXPECTED_CONFIG_CACHE_CREATED');
    }

    private function verifyInstalledFiles():void
    {
        foreach($this->manifest['files'] as $file){$relative=$file['path'];$expected=$relative==='config/public_storefront.php'?$this->state['clampedConfigSHA256']:($relative==='public/.htaccess'?($this->state['uncached']['entries']['candidate']['overlaySHA256']??$this->state['preserved']['customHtaccessSHA256']):$file['sha256']);$path=$this->active().'/'.$relative;if(is_link($path)||realpath($path)!==$path||!is_file($path)||hash_file('sha256',$path)!==$expected)throw new RuntimeException('INSTALLED_RUNTIME_FILE_MISMATCH');}
        if(hash_file('sha256',$this->active().'/.env')!==$this->state['envSHA256'])throw new RuntimeException('PRIVATE_ENV_CHANGED');$this->assertGit();
    }

    private function loadExisting():bool
    {
        if(!file_exists($this->pointer))return false;if(is_link($this->pointer)||filesize($this->pointer)>8192)throw new RuntimeException('PENDING_POINTER');$pointer=json_decode(file_get_contents($this->pointer),true,32,JSON_THROW_ON_ERROR);if(!preg_match('/^[a-f0-9]{32}$/D',$pointer['id']??'')||$pointer['private']!==$this->home.'/.ald1n-b1143-state/'.$pointer['id'])throw new RuntimeException('PENDING_POINTER_SCOPE');$path=$pointer['private'].'/state.json';if(is_link($path)||realpath($path)!==$path||filesize($path)>16777216)throw new RuntimeException('PENDING_STATE');$this->state=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$first=json_decode(explode("\n",file_get_contents($this->state['journal']))[0],true,512,JSON_THROW_ON_ERROR);if(($this->state['schemaVersion']??0)!==1||$first['owner']!==$this->state['owner']||$first['plan']!==$this->state['plan'])throw new RuntimeException('JOURNAL_STATE_BINDING');return true;
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
        try{
            $this->prepare();$this->state['schedulerPauseAttempted']=true;$this->save('SCHEDULER_PAUSE_INTENT');$this->state['scheduler']=$this->scheduler('pause');$this->save('SCHEDULER_PAUSED');
            $this->save('MAINTENANCE_INTENT');$actual=ReleasePrimitives::ownMaintenance($this->active().'/storage',$this->state['owner'],$this->state['maintenanceExpires'],$this->state['maintenanceStart'],$this->webContext());if($actual['files']!==$this->state['oldMaintenance']['files'])throw new RuntimeException('MAINTENANCE_PLAN_MISMATCH');$this->save('MAINTENANCE_OWNED');$this->save('UNCACHED_OVERLAY_INTENT');UncachedProfile::apply($this->state['uncached'],'old',$this->active());UncachedProfile::apply($this->state['uncached'],'candidate',$this->state['plan']['candidate']);$this->save('UNCACHED_OVERLAY_APPLIED');
            // Fresh executed nonce and authenticated real-index response are mandatory before any code/vendor rename.
            $probe=null;for($attempt=0;$attempt<17;$attempt++){try{$this->web->request('probe',null,false);$probe=$this->web->request('probe');break;}catch(Throwable $error){if($attempt===16)throw $error;usleep(1000000);}}
            $this->web->publicStatus('/login',503,$this->state['oldMaintenance']['ownerSHA256']);$this->state['uncachedFreshness']=$this->web->proveFreshness();$this->save('UNCACHED_FRESHNESS_PROVEN');$this->state['visibility']=$this->web->calibrateVisibility();$this->save('WORKER_VISIBILITY_CALIBRATED');HostProcessGuard::quiet($this->active(),$this->state['visibility']);$this->scheduler('verify');
            $this->state['oldPHP']=InstallFileOps::phpCensus($this->active());$this->save('PRE_UNCACHED_CENSUS_INTENT');$this->web->setPhase('maintenance',$this->state['oldPHP']);$this->state['preInvalidation']=$this->web->verifyFiles();$this->save('OLD_UNCACHED_CENSUS_VERIFIED');HostProcessGuard::quiet($this->active(),$this->state['visibility']);$this->scheduler('verify');
            UncachedProfile::assertApplied($this->state['uncached'],'old',$this->active());UncachedProfile::assertApplied($this->state['uncached'],'candidate',$this->state['plan']['candidate']);ReleasePrimitives::switchJournaled($this->active(),$this->state['plan']['candidate'],$this->state['plan']['rollback'],$this->state['journal'],$this->state['owner']);$this->save('SWITCHED');$this->warmup();$this->verifyInstalledFiles();
            $this->web->setPhase('installed',InstallFileOps::phpCensus($this->active()));$this->state['postInvalidation']=$this->web->verifyFiles();$this->web->request('off-proof');
            // Bootstrap can generate services/packages files; verify the resulting concrete census uncached too.
            $this->web->setPhase('installed',InstallFileOps::phpCensus($this->active()));$this->web->verifyFiles();$this->web->request('off-proof');
            $this->web->publicStatus('/login',200,null,true);$this->web->publicStatus('/admin/catalog',302,null,true);foreach(['/katalog','/korpa','/porucivanje'] as $path)$this->web->publicStatus($path,404,null,true);$this->web->publicStatus('/login',503,$this->state['newMaintenance']['ownerSHA256']);
            HostProcessGuard::quiet($this->active(),$this->state['visibility']);$this->verifyInstalledFiles();$this->scheduler('verify');$this->save('OFF_PRIVATE_HTTP_SMOKES_PASSED');
            $this->web->setPhase('installed',['public/index.php'=>hash_file('sha256',$this->active().'/public/index.php'),'storage/framework/maintenance.php'=>hash_file('sha256',$this->active().'/storage/framework/maintenance.php')]);$this->web->verifyFiles();$this->cleanupMapping();$this->save('COMMITTED_OFF');return $this->finishCommitted();
        }catch(Throwable $error){if(!$this->state){$this->emit('HOLD_BEFORE_LIVE_APPLICATION_MUTATION',get_class($error));return 2;}return $this->recover($error->getMessage());}
    }

    private function finishCommitted():int
    {
        try{
            $this->verifyInstalledFiles();$this->cleanupMapping();
            $this->clearMaintenance($this->state['oldMaintenance'],$this->state['plan']['rollback'].'/storage');$this->clearMaintenance($this->state['newMaintenance'],$this->active().'/storage');$this->save('PUBLIC_RELEASED');
            $this->scheduler('release');$this->save('SCHEDULER_RESTORED');if($this->web===null)$this->mapping('revoked');$this->web->cleanupStatus();$this->web->publicStatus('/login',200);foreach(['/katalog','/korpa','/porucivanje'] as $path)$this->web->publicStatus($path,404);$this->assertGit();$this->save('CLOSED_INSTALL');ReleasePrimitives::journalState($this->state['journal'],$this->state['owner'],'CLOSED');
            $this->emit('CONTROLLED_CMS_INSTALL','PASS_MAIN_RUNTIME_WITH_REVIEWED_OFF_CLAMP');$this->emit('PUBLIC_CHECKOUT','UNCONDITIONALLY_OFF_CONFIG_FALSE_NO_ENV_OR_DB_FLAG_CHANGE');$this->emit('PERMANENT_PRIVATE_DATA_DEPENDENCY_DO_NOT_PRUNE',$this->state['plan']['rollback']);$this->emit('COMPLETE_DIRECTORY_SWITCH','TWO_RENAMES_WITH_OWNED_MAINTENANCE_AND_RECOVERY_NOT_A_SINGLE_ATOMIC_TRANSACTION');return 0;
        }catch(Throwable $error){$this->emit('HOLD_INSTALLED_OFF_FINISH_PENDING',$error->getMessage());return 2;}
    }

    /** Code is already original before the original PHP profile can be restored. */
    private function finishOriginalProfile(bool $switched):int
    {
        $plan=$this->state['plan'];$active=$this->active();if($this->identity($active)!==$plan['oldIdentity'])throw new RuntimeException('ORIGINAL_CODE_REQUIRED_BEFORE_CONFIG_RESTORE');
        $other=is_dir($plan['failed'])?$plan['failed']:$plan['candidate'];if($this->identity($other)!==$plan['candidateIdentity'])throw new RuntimeException('RECOVERY_CANDIDATE_IDENTITY');
        $this->state['originalProfileRestore']=['switched'=>$switched];$this->save('ORIGINAL_PROFILE_RESTORE_INTENT');
        try{
            $this->cleanupMapping();if(is_array($this->state['newMaintenance']))$this->clearMaintenance($this->state['newMaintenance'],$other.'/storage');if(is_array($this->state['oldMaintenance']))$this->clearMaintenance($this->state['oldMaintenance'],$active.'/storage');
            if(isset($this->state['uncached'])){UncachedProfile::restore($this->state['uncached'],'candidate',$other);UncachedProfile::restore($this->state['uncached'],'old',$active);}
            $this->save('ORIGINAL_PROFILE_RESTORED_PENDING_HTTP');if($this->web===null)$this->mapping('revoked');$this->web->publicStatus('/login',200);$this->web->cleanupStatus();
            if(($this->state['schedulerPauseAttempted']??false)||$this->state['scheduler']!==null)$this->scheduler('release');$this->assertGit();$this->save($switched?'ROLLED_BACK':'ROLLED_BACK_PRE_SWITCH');
            $this->emit($switched?'ROLLBACK':'ORIGINAL_APPLICATION_CODE_VENDOR_UNTOUCHED',$switched?'ORIGINAL_CODE_PRIVATE_DATA_AND_PROFILE_RESTORED_HTTP_VERIFIED':true);return 2;
        }catch(Throwable $error){
            if(($this->state['uncachedFreshness']['samePathFreshnessProven']??false)===true){UncachedProfile::apply($this->state['uncached'],'old',$active);$this->mapping('preflight');if(is_array($this->state['oldMaintenance'])&&$this->existingMaintenance($this->state['oldMaintenance'],$active.'/storage')===null)ReleasePrimitives::ownMaintenance($active.'/storage',$this->state['owner'],$this->state['maintenanceExpires'],$this->state['maintenanceStart'],$this->webContext());$this->save('UNCACHED_OLD_PROFILE_REAPPLIED_HOLD');}
            if(($this->state['uncachedFreshness']['samePathFreshnessProven']??false)!==true)$this->save('ORIGINAL_PROFILE_AVAILABILITY_HOLD');throw $error;
        }
    }

    private function recover(string $reason):int
    {
        $this->emit('INSTALL_HOLD_REASON',$reason);
        try{
            $plan=$this->state['plan'];$active=$this->active();$current=is_dir($active)?$this->identity($active):null;
            if($current===$plan['oldIdentity']&&!is_dir($plan['rollback'])){
                $switched=is_dir($plan['failed']);if(isset($this->state['originalProfileRestore'])){
                    if(($this->state['originalProfileRestore']['switched']??null)!==$switched)throw new RuntimeException('RESTORE_PHASE_TOPOLOGY_MISMATCH');
                    if(($this->state['uncachedFreshness']['samePathFreshnessProven']??false)===true){UncachedProfile::apply($this->state['uncached'],'old',$active);$this->mapping('preflight');if(is_array($this->state['oldMaintenance'])&&$this->existingMaintenance($this->state['oldMaintenance'],$active.'/storage')===null)ReleasePrimitives::ownMaintenance($active.'/storage',$this->state['owner'],$this->state['maintenanceExpires'],$this->state['maintenanceStart'],$this->webContext());
                        if(($this->state['uncachedFreshness']['samePathFreshnessProven']??false)===true){unset($this->state['visibility']);$this->mapping();$this->state['visibility']=$this->web->calibrateVisibility();HostProcessGuard::quiet($active,$this->state['visibility']);}}
                }
                return $this->finishOriginalProfile($switched);
            }
            if($current===$plan['candidateIdentity']){
                $present=$this->existingMaintenance($this->state['newMaintenance'],$active.'/storage');if($present===null)ReleasePrimitives::ownMaintenance($active.'/storage',$this->state['owner'],$this->state['maintenanceExpires'],$this->state['maintenanceStart'],$this->webContext());
                UncachedProfile::assertApplied($this->state['uncached'],'candidate',$active);unset($this->state['visibility']);$this->mapping();$this->state['visibility']=$this->web->calibrateVisibility();HostProcessGuard::quiet($active,$this->state['visibility']);$this->scheduler('verify');$this->web->setPhase('installed',InstallFileOps::phpCensus($active));$this->web->verifyFiles();
            }
            ReleasePrimitives::recoverSwitch($plan,$this->state['journal'],$this->state['owner'],$plan['failed']);$this->save('OLD_RESTORED_MAINTENANCE_HELD');
            UncachedProfile::assertApplied($this->state['uncached'],'old',$active);unset($this->state['visibility']);$this->mapping();$this->state['visibility']=$this->web->calibrateVisibility();HostProcessGuard::quiet($active,$this->state['visibility']);$this->web->setPhase('rollback',InstallFileOps::phpCensus($active));$this->web->verifyFiles();$this->web->request('probe');$this->web->publicStatus('/login',200,null,true);HostProcessGuard::quiet($active,$this->state['visibility']);return $this->finishOriginalProfile(is_dir($plan['failed']));
        }catch(Throwable $error){if(($this->state['phase']??'')!=='ORIGINAL_PROFILE_AVAILABILITY_HOLD')$this->save('HOLD_RECOVERY_OWNED_MAINTENANCE_RETAINED');$this->emit('RECOVERY_HOLD',$error->getMessage());return 2;}
    }
}
