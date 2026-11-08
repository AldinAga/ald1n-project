<?php
declare(strict_types=1);
/** Real coordinator + filesystem/journal/Git; HTTP/process/scheduler transports are explicit doubles.
 * Actual HTTP/OPcache and Laravel scheduler behavior are exercised separately by sibling fixtures.
 */
final class HostWebControl {
    public static array $calls=[];
    public static bool $failOriginalOnce=false;
    public function __construct(mixed ...$args){}
    public function setGroup(array $group):void{}
    public function setPhase(string $phase,array $files=[]):void{self::$calls[]=$phase;}
    public function calibrateVisibility():array{return ['explicitFixtureBoundaryDouble'=>true];}
    public function verifyFiles():array{self::$calls[]='invalidate';return ['fixtureBoundaryDouble'=>true];}
    public function request(mixed ...$args):array{return ['status'=>'ok'];}
    public function publicStatus(mixed ...$args):void{self::$calls[]='http';if(self::$failOriginalOnce&&$args===['/login',200]){self::$failOriginalOnce=false;throw new RuntimeException('INJECTED_ORIGINAL_PROFILE_STALE_503');}}
    public function cleanupStatus():void{self::$calls[]='cleanup';}
}
final class HostProcessGuard {public static function quiet(string $root,array $visibility):void{if(($visibility['explicitFixtureBoundaryDouble']??false)!==true)throw new RuntimeException('FIXTURE_BOUNDARY');}}
require $argv[5].'/ReleasePrimitives.php';require $argv[5].'/InstallFileOps.php';require $argv[1].'/InstallCoordinator.php';require $argv[1].'/UncachedProfile.php';
function check(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('COORDINATOR_FIXTURE_'.$name);}
function setProperty(object $o,string $name,mixed $value):void{(new ReflectionProperty($o,$name))->setValue($o,$value);}
function invoke(object $o,string $name,mixed ...$args):mixed{return (new ReflectionMethod($o,$name))->invoke($o,...$args);}
function identity(string $path):array{$s=stat($path);return ['dev'=>$s['dev'],'ino'=>$s['ino']];}
function shell(array $args,string $cwd):string{$p=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);check(proc_close($p)===0,'GIT');return trim($out);}
$tools=$argv[1];$stage=$argv[2];$payload=$argv[3];$directoryDoc=$argv[4];$base=getenv('RUNNER_TEMP').'/b1134-coordinator';mkdir($base);
$manifest=json_decode(file_get_contents($payload.'/runtime-manifest.json'),true,512,JSON_THROW_ON_ERROR);$directories=json_decode(file_get_contents($directoryDoc),true,512,JSON_THROW_ON_ERROR)['directories'];$receipts=[];
foreach(['success','failure-after-rename','interrupted-gap','lost-pause-intent','stale-original-profile','interrupted-after-config-restore','unsupported-original-baseline','interrupted-after-old-rename'] as $case){
    $home=$base.'/'.$case;mkdir($home);$root=$home.'/project';mkdir($root);mkdir($root.'/incoming');mkdir($root.'/apps/cms',0755,true);$active=$root.'/apps/cms/current';$runtime=$home.'/retained';mkdir($runtime);$candidate=$runtime.'/candidate';$retained=$runtime.'/old';$failed=$runtime.'/failed';
    InstallFileOps::candidate($stage,$active,$manifest,$directories);InstallFileOps::candidate($stage,$candidate,$manifest,$directories);
    foreach(['storage/app/public','storage/logs','storage/framework/sessions','storage/framework/cache','storage/framework/views'] as $path)mkdir($active.'/'.$path,0755,true);
    file_put_contents($active.'/.env','FAKE_FIXTURE_ONLY=secret-never-output');file_put_contents($active.'/storage/app/public/original','original');
    $preserved=ReleasePrimitives::preservePrivate($active,$candidate,$retained);
    $config=$candidate.'/config/public_storefront.php';$original=file_get_contents($config);$clamped=ReleasePrimitives::offConfig($original,hash('sha256',$original));file_put_contents($config,$clamped);
    shell(['git','init','--quiet'],$root);shell(['git','-c','user.name=Fixture','-c','user.email=fixture@example.invalid','commit','--allow-empty','-m','fixture','--quiet'],$root);file_put_contents($root.'/index-sentinel','fixture');shell(['git','add','index-sentinel'],$root);
    $private=$home.'/.ald1n-b1143-state';mkdir($private,0700);$transaction=$private.'/'.str_repeat('a',32);mkdir($transaction,0700);
    $plan=['active'=>$active,'candidate'=>$candidate,'rollback'=>$retained,'failed'=>$failed,'oldIdentity'=>identity($active),'candidateIdentity'=>identity($candidate)];$journal=$transaction.'/journal.jsonl';$owner=ReleasePrimitives::createJournal($journal,$plan);
    $key=bin2hex(random_bytes(32));$token=bin2hex(random_bytes(32));$started=time();$expires=$started+1500;
    $librarySource='<?php // fixture immutable transport';$library=$root.'/incoming/b1143-webcache-'.hash('sha256',$librarySource).'.php';file_put_contents($library,$librarySource);
    $context=['library'=>$library,'envelope'=>$transaction.'/web.json','frontSHA256'=>hash('sha256',$token)];$oldMaintenance=ReleasePrimitives::ownMaintenance($active.'/storage',$owner,$expires,$started,$context);$newMaintenance=ReleasePrimitives::ownMaintenance($candidate.'/storage',$owner,$expires,$started,$context);
    file_put_contents($root.'/incoming/b1134-scheduler.php','<?php $c=json_decode(file_get_contents($argv[1]),true);echo json_encode(["status"=>"ok","receipt"=>["explicitFixtureBoundaryDouble"=>true,"action"=>$c["action"]]]);');
    $object=(new ReflectionClass(InstallCoordinator::class))->newInstanceWithoutConstructor();setProperty($object,'root',$root);setProperty($object,'home',$home);setProperty($object,'webLibrary',$library);setProperty($object,'pointer',$private.'/current.json');setProperty($object,'manifest',$manifest);setProperty($object,'directoryDocument',['directories'=>$directories]);
    $state=['schemaVersion'=>1,'id'=>str_repeat('a',32),'private'=>$transaction,'plan'=>$plan,'journal'=>$journal,'owner'=>$owner,'git'=>invoke($object,'gitSnapshot'),'envSHA256'=>hash_file('sha256',$active.'/.env'),'clampedConfigSHA256'=>hash('sha256',$clamped),'preserved'=>$preserved,'oldMaintenance'=>$oldMaintenance,'newMaintenance'=>$newMaintenance,'scheduler'=>['explicitFixtureBoundaryDouble'=>true],'web'=>['key'=>$key,'token'=>$token,'librarySHA256'=>hash_file('sha256',$library)],'maintenanceStart'=>$started,'maintenanceExpires'=>$expires];$state['uncached']=UncachedProfile::plan($active,$candidate,$transaction,$preserved['customHtaccessSHA256'],$state['id']);UncachedProfile::apply($state['uncached'],'old',$active);UncachedProfile::apply($state['uncached'],'candidate',$candidate);$state['uncachedFreshness']=['samePathFreshnessProven'=>true,'id'=>$state['id'],'profileSHA256'=>$state['uncached']['profileSHA256']];setProperty($object,'state',$state);setProperty($object,'web',new HostWebControl());
    check(invoke($object,'freshnessProven'),'CURRENT_JOB_FRESHNESS_PROOF');$foreignProof=$state;$foreignProof['uncachedFreshness']['id']='foreign';setProperty($object,'state',$foreignProof);check(!invoke($object,'freshnessProven'),'FOREIGN_JOB_FRESHNESS_REJECTED');$foreignProof['uncachedFreshness']=$state['uncachedFreshness'];$foreignProof['uncachedFreshness']['profileSHA256']=str_repeat('0',64);setProperty($object,'state',$foreignProof);check(!invoke($object,'freshnessProven'),'FOREIGN_PROFILE_FRESHNESS_REJECTED');setProperty($object,'state',$state);
    if($case==='lost-pause-intent'){
        $state['scheduler']=null;$state['schedulerPauseAttempted']=true;setProperty($object,'state',$state);file_put_contents($transaction.'/fail-once','fixture');
        file_put_contents($root.'/incoming/b1134-scheduler.php','<?php $c=json_decode(file_get_contents($argv[1]),true);$p=dirname($argv[1]);if(is_file($p."/fail-once")){unlink($p."/fail-once");exit(2);}file_put_contents($p."/restored","yes");echo json_encode(["status"=>"ok","receipt"=>["fixtureRestored"=>true]]);');
        invoke($object,'save','SCHEDULER_PAUSE_INTENT');ob_start();invoke($object,'recover','INJECTED_FIRST_FAILURE');$first=ob_get_clean();check(json_decode(file_get_contents($transaction.'/state.json'),true)['phase']==='HOLD_RECOVERY_OWNED_MAINTENANCE_RETAINED','FIRST_RESTORE_HELD');
        ob_start();invoke($object,'recover','INJECTED_RETRY');$second=ob_get_clean();if(!is_file($transaction.'/restored')){echo $second;throw new RuntimeException('COORDINATOR_FIXTURE_LOST_PAUSE_INTENT_RETRIED');}$receipts[$case]=['lostReceiptAndTransientRestoreFailureRetried'=>true,'externalBoundaries'=>'EXPLICIT_DOUBLES'];continue;
    }
    if($case==='unsupported-original-baseline'){
        $state['uncachedFreshness']=[];setProperty($object,'state',$state);HostWebControl::$failOriginalOnce=true;ob_start();invoke($object,'recover','INJECTED_UNSUPPORTED_STARTUP_AND_BASELINE_500');ob_end_clean();$saved=json_decode(file_get_contents($transaction.'/state.json'),true);check($saved['phase']==='ORIGINAL_PROFILE_AVAILABILITY_HOLD','UNSUPPORTED_NOT_REAPPLIED');check(hash_file('sha256',$active.'/public/.htaccess')===$preserved['customHtaccessSHA256'],'UNSUPPORTED_EXACT_ORIGINAL_RESTORED');$receipts[$case]=['unprovenOverlayNotReapplied'=>true,'originalBytesRestoredAvailabilityStillHold'=>true];continue;
    }
    // The real class's manifest loader receives the exact authenticated stage documents on re-entry.
    $verification=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU/verification';mkdir($verification,0755,true);copy($payload.'/runtime-manifest.json',$verification.'/runtime-manifest.json');copy($directoryDoc,$verification.'/runtime-directories.json');
    if($case==='interrupted-gap'){ReleasePrimitives::journalState($journal,$owner,'INTENT_OLD_RENAME');rename($active,$retained);}
    else ReleasePrimitives::switchJournaled($active,$candidate,$retained,$journal,$owner);
    if($case!=='interrupted-gap')file_put_contents($active.'/storage/app/public/new','created-after-switch');
    if($case==='interrupted-after-old-rename'){ReleasePrimitives::recoverSwitch($plan,$journal,$owner,$failed);}
    if($case==='interrupted-after-config-restore'){
        ReleasePrimitives::recoverSwitch($plan,$journal,$owner,$failed);invoke($object,'clearMaintenance',$newMaintenance,$failed.'/storage');invoke($object,'clearMaintenance',$oldMaintenance,$active.'/storage');UncachedProfile::restore($state['uncached'],'candidate',$failed);UncachedProfile::restore($state['uncached'],'old',$active);$state['originalProfileRestore']=['switched'=>true];setProperty($object,'state',$state);invoke($object,'save','ORIGINAL_PROFILE_RESTORED_PENDING_HTTP');
    }else invoke($object,'save',$case==='success'?'COMMITTED_OFF':'SWITCHED');
    if($case==='stale-original-profile')HostWebControl::$failOriginalOnce=true;
    ob_start();$rc=$case==='success'?invoke($object,'finishCommitted'):invoke($object,'recover','INJECTED_FIXTURE_FAILURE');$output=ob_get_clean();check(!str_contains($output,'secret-never-output'),'NO_SECRET_OUTPUT');check($rc===($case==='success'?0:2),'EXPECTED_RC');
    $saved=json_decode(file_get_contents($transaction.'/state.json'),true);if($case==='stale-original-profile'){check($saved['phase']==='HOLD_RECOVERY_OWNED_MAINTENANCE_RETAINED','STALE_ORIGINAL_HELD');UncachedProfile::assertApplied($state['uncached'],'old',$active);check(file_exists($active.'/storage/framework/maintenance.php'),'STALE_ORIGINAL_OWNED_BARRIER');ob_start();invoke($object,'recover','INJECTED_RETRY_AFTER_STALE_ORIGINAL');ob_end_clean();$saved=json_decode(file_get_contents($transaction.'/state.json'),true);}
    check($saved['phase']===($case==='success'?'CLOSED_INSTALL':($case==='interrupted-gap'?'ROLLED_BACK_PRE_SWITCH':'ROLLED_BACK')),'TERMINAL_STATE');check(!file_exists($active.'/storage/framework/maintenance.php'),'MAINTENANCE_RELEASED');check(file_get_contents($active.'/.env')==='FAKE_FIXTURE_ONLY=secret-never-output','ENV_PRESERVED');
    if($case!=='interrupted-gap')check(file_get_contents($active.'/storage/app/public/new')==='created-after-switch','USER_WRITE_SURVIVES');
    $again=(new ReflectionClass(InstallCoordinator::class))->newInstanceWithoutConstructor();foreach(['root'=>$root,'home'=>$home,'webLibrary'=>$library,'pointer'=>$private.'/current.json'] as $key=>$value)setProperty($again,$key,$value);
    ob_start();$rerun=$again->run();$repeatOutput=ob_get_clean();check($rerun===($case==='success'?0:2),'REENTRY_RC');check(str_contains($repeatOutput,$case==='success'?'ALREADY_INSTALLED_OFF_NO_REPEAT':'PRIOR_ATTEMPT_ROLLED_BACK_NO_AUTOMATIC_REDEPLOY'),'NO_REPLAY');
    $receipts[$case]=['actualCoordinatorClass'=>true,'realFilesystemGitJournalAndManifest'=>true,'externalHTTPProcessSchedulerBoundaries'=>'EXPLICIT_DOUBLES_SEPARATELY_INTEGRATION_TESTED','returnCode'=>$rc,'reentryReturnCode'=>$rerun,'privateWriteSurvives'=>true];
}
echo json_encode($receipts,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
