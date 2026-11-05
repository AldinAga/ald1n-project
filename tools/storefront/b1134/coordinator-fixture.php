<?php
declare(strict_types=1);
/** Real coordinator + filesystem/journal/Git; HTTP/process/scheduler transports are explicit doubles.
 * Actual HTTP/OPcache and Laravel scheduler behavior are exercised separately by sibling fixtures.
 */
final class HostWebControl {
    public static array $calls=[];
    public function __construct(mixed ...$args){}
    public function setGroup(array $group):void{}
    public function setPhase(string $phase,array $files=[]):void{self::$calls[]=$phase;}
    public function calibrateVisibility():array{return ['explicitFixtureBoundaryDouble'=>true];}
    public function invalidateAll():array{self::$calls[]='invalidate';return ['fixtureBoundaryDouble'=>true];}
    public function request(mixed ...$args):array{return ['status'=>'ok'];}
    public function publicStatus(mixed ...$args):void{self::$calls[]='http';}
    public function cleanupStatus():void{self::$calls[]='cleanup';}
}
final class HostProcessGuard {public static function quiet(string $root,array $visibility):void{if(($visibility['explicitFixtureBoundaryDouble']??false)!==true)throw new RuntimeException('FIXTURE_BOUNDARY');}}
require $argv[1].'/ReleasePrimitives.php';require $argv[1].'/InstallFileOps.php';require $argv[1].'/InstallCoordinator.php';
function check(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('COORDINATOR_FIXTURE_'.$name);}
function setProperty(object $o,string $name,mixed $value):void{(new ReflectionProperty($o,$name))->setValue($o,$value);}
function invoke(object $o,string $name,mixed ...$args):mixed{return (new ReflectionMethod($o,$name))->invoke($o,...$args);}
function identity(string $path):array{$s=stat($path);return ['dev'=>$s['dev'],'ino'=>$s['ino']];}
function shell(array $args,string $cwd):string{$p=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);check(proc_close($p)===0,'GIT');return trim($out);}
$tools=$argv[1];$stage=$argv[2];$payload=$argv[3];$directoryDoc=$argv[4];$base=getenv('RUNNER_TEMP').'/b1134-coordinator';mkdir($base);
$manifest=json_decode(file_get_contents($payload.'/runtime-manifest.json'),true,512,JSON_THROW_ON_ERROR);$directories=json_decode(file_get_contents($directoryDoc),true,512,JSON_THROW_ON_ERROR)['directories'];$receipts=[];
foreach(['success','failure-after-rename','interrupted-gap','lost-pause-intent'] as $case){
    $home=$base.'/'.$case;mkdir($home);$root=$home.'/project';mkdir($root);mkdir($root.'/incoming');mkdir($root.'/apps/cms',0755,true);$active=$root.'/apps/cms/current';$runtime=$home.'/retained';mkdir($runtime);$candidate=$runtime.'/candidate';$retained=$runtime.'/old';$failed=$runtime.'/failed';
    InstallFileOps::candidate($stage,$active,$manifest,$directories);InstallFileOps::candidate($stage,$candidate,$manifest,$directories);
    foreach(['storage/app/public','storage/logs','storage/framework/sessions','storage/framework/cache','storage/framework/views'] as $path)mkdir($active.'/'.$path,0755,true);
    file_put_contents($active.'/.env','FAKE_FIXTURE_ONLY=secret-never-output');file_put_contents($active.'/storage/app/public/original','original');
    $preserved=ReleasePrimitives::preservePrivate($active,$candidate,$retained);
    $config=$candidate.'/config/public_storefront.php';$original=file_get_contents($config);$clamped=ReleasePrimitives::offConfig($original,hash('sha256',$original));file_put_contents($config,$clamped);
    shell(['git','init','--quiet'],$root);shell(['git','-c','user.name=Fixture','-c','user.email=fixture@example.invalid','commit','--allow-empty','-m','fixture','--quiet'],$root);file_put_contents($root.'/index-sentinel','fixture');shell(['git','add','index-sentinel'],$root);
    $private=$home.'/state';mkdir($private,0700);$transaction=$private.'/'.str_repeat('a',32);mkdir($transaction,0700);
    $plan=['active'=>$active,'candidate'=>$candidate,'rollback'=>$retained,'failed'=>$failed,'oldIdentity'=>identity($active),'candidateIdentity'=>identity($candidate)];$journal=$transaction.'/journal.jsonl';$owner=ReleasePrimitives::createJournal($journal,$plan);
    $oldMaintenance=ReleasePrimitives::ownMaintenance($active.'/storage',$owner);$newMaintenance=ReleasePrimitives::ownMaintenance($candidate.'/storage',$owner);
    $librarySource='<?php // fixture immutable transport';$library=$root.'/incoming/b1134-webcache-'.hash('sha256',$librarySource).'.php';file_put_contents($library,$librarySource);
    file_put_contents($root.'/incoming/b1134-scheduler.php','<?php $c=json_decode(file_get_contents($argv[1]),true);echo json_encode(["status"=>"ok","receipt"=>["explicitFixtureBoundaryDouble"=>true,"action"=>$c["action"]]]);');
    $object=(new ReflectionClass(InstallCoordinator::class))->newInstanceWithoutConstructor();setProperty($object,'root',$root);setProperty($object,'home',$home);setProperty($object,'webLibrary',$library);setProperty($object,'pointer',$private.'/current.json');setProperty($object,'manifest',$manifest);setProperty($object,'directoryDocument',['directories'=>$directories]);
    $state=['schemaVersion'=>1,'id'=>str_repeat('a',32),'private'=>$transaction,'plan'=>$plan,'journal'=>$journal,'owner'=>$owner,'git'=>invoke($object,'gitSnapshot'),'envSHA256'=>hash_file('sha256',$active.'/.env'),'clampedConfigSHA256'=>hash('sha256',$clamped),'preserved'=>$preserved,'oldMaintenance'=>$oldMaintenance,'newMaintenance'=>$newMaintenance,'scheduler'=>['explicitFixtureBoundaryDouble'=>true],'web'=>['key'=>bin2hex(random_bytes(32)),'token'=>bin2hex(random_bytes(32)),'librarySHA256'=>hash_file('sha256',$library)],'maintenanceStart'=>time(),'maintenanceExpires'=>time()+1500];setProperty($object,'state',$state);setProperty($object,'web',new HostWebControl());
    if($case==='lost-pause-intent'){
        $state['scheduler']=null;$state['schedulerPauseAttempted']=true;setProperty($object,'state',$state);file_put_contents($transaction.'/fail-once','fixture');
        file_put_contents($root.'/incoming/b1134-scheduler.php','<?php $c=json_decode(file_get_contents($argv[1]),true);$p=dirname($argv[1]);if(is_file($p."/fail-once")){unlink($p."/fail-once");exit(2);}file_put_contents($p."/restored","yes");echo json_encode(["status"=>"ok","receipt"=>["fixtureRestored"=>true]]);');
        invoke($object,'save','SCHEDULER_PAUSE_INTENT');ob_start();invoke($object,'recover','INJECTED_FIRST_FAILURE');$first=ob_get_clean();check(json_decode(file_get_contents($transaction.'/state.json'),true)['phase']==='HOLD_RECOVERY_OWNED_MAINTENANCE_RETAINED','FIRST_RESTORE_HELD');
        ob_start();invoke($object,'recover','INJECTED_RETRY');$second=ob_get_clean();check(is_file($transaction.'/restored'),'LOST_PAUSE_INTENT_RETRIED');$receipts[$case]=['lostReceiptAndTransientRestoreFailureRetried'=>true,'externalBoundaries'=>'EXPLICIT_DOUBLES'];continue;
    }
    // The real class's manifest loader receives the exact authenticated stage documents on re-entry.
    $verification=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU/verification';mkdir($verification,0755,true);copy($payload.'/runtime-manifest.json',$verification.'/runtime-manifest.json');copy($directoryDoc,$verification.'/runtime-directories.json');
    if($case==='interrupted-gap'){ReleasePrimitives::journalState($journal,$owner,'INTENT_OLD_RENAME');rename($active,$retained);}
    else ReleasePrimitives::switchJournaled($active,$candidate,$retained,$journal,$owner);
    if($case!=='interrupted-gap')file_put_contents($active.'/storage/app/public/new','created-after-switch');
    invoke($object,'save',$case==='success'?'COMMITTED_OFF':'SWITCHED');
    ob_start();$rc=$case==='success'?invoke($object,'finishCommitted'):invoke($object,'recover','INJECTED_FIXTURE_FAILURE');$output=ob_get_clean();check(!str_contains($output,'secret-never-output'),'NO_SECRET_OUTPUT');check($rc===($case==='success'?0:2),'EXPECTED_RC');
    $saved=json_decode(file_get_contents($transaction.'/state.json'),true);check($saved['phase']===($case==='success'?'CLOSED_INSTALL':'ROLLED_BACK'),'TERMINAL_STATE');check(!file_exists($active.'/storage/framework/maintenance.php'),'MAINTENANCE_RELEASED');check(file_get_contents($active.'/.env')==='FAKE_FIXTURE_ONLY=secret-never-output','ENV_PRESERVED');
    if($case!=='interrupted-gap')check(file_get_contents($active.'/storage/app/public/new')==='created-after-switch','USER_WRITE_SURVIVES');
    $again=(new ReflectionClass(InstallCoordinator::class))->newInstanceWithoutConstructor();foreach(['root'=>$root,'home'=>$home,'webLibrary'=>$library,'pointer'=>$private.'/current.json'] as $key=>$value)setProperty($again,$key,$value);
    ob_start();$rerun=$again->run();$repeatOutput=ob_get_clean();check($rerun===($case==='success'?0:2),'REENTRY_RC');check(str_contains($repeatOutput,$case==='success'?'ALREADY_INSTALLED_OFF_NO_REPEAT':'PRIOR_ATTEMPT_ROLLED_BACK_NO_AUTOMATIC_REDEPLOY'),'NO_REPLAY');
    $receipts[$case]=['actualCoordinatorClass'=>true,'realFilesystemGitJournalAndManifest'=>true,'externalHTTPProcessSchedulerBoundaries'=>'EXPLICIT_DOUBLES_SEPARATELY_INTEGRATION_TESTED','returnCode'=>$rc,'reentryReturnCode'=>$rerun,'privateWriteSurvives'=>true];
}
echo json_encode($receipts,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
