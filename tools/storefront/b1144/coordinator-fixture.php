<?php
declare(strict_types=1);
/**
 * The real B1144 coordinator over the real staged 8531-file runtime: real owned barriers, journal, switch, rollback,
 * real `ps` drain and drain positive control, real local SHA-256 verification, real artisan warmup / about /
 * migrate:status (SQLite with exactly 68 applied rows), the real probe file executed by PHP with OPcache, and real
 * Laravel log offsets. Explicit doubles: HTTP status responses (FixtureWeb) and the B1134 scheduler transport.
 * Host faults are injected PERSISTENTLY (same predicate for preflight, installed and restored labels and across runs),
 * and every stage after the switch, including after the lift, is failure-injected and must converge to a documented end.
 */
require $argv[5].'/ReleasePrimitives.php';require $argv[5].'/InstallFileOps.php';
foreach(['LeanGates','FreshnessProbe','B1144Recovery','InstallCoordinator'] as $class)require $argv[1].'/'.$class.'.php';

final class FixtureWeb
{
    public array $fail=[];public array $calls=[];public array $seen=[];public bool $logErrorOnUp=false;public bool $uploadOnLogin=false;public ?string $sapiOverride=null;public array $sapiLabels=['installed'];
    /** realpathTtl is the REAL ini value of the probe process; realpathOverride / override replace reported raw values per label (preflight, installed, restored). */
    public string $realpathTtl='0';public array $realpathOverride=[];public array $override=[];public array $times=[];
    /** Drain positive control double: a REAL process carrying this CMS's busy lsphp title runs while the sampled GET is "in flight". */
    public bool $lsphpTitle=true;public bool $otherSiteTitle=false;public bool $barrierSeen=false;public array $labels=[];
    /** One-shot hooks keyed like the smoke calls ("/up=200"), run before the response is decided. */
    public array $hooks=[];
    public function __construct(private string $active,private array $oldIdentity,private string $failed,private string $harness,private array $compile){}
    private function label():string {clearstatcache(true);$s=@stat($this->active);$old=is_array($s)&&$s['dev']===$this->oldIdentity['dev']&&$s['ino']===$this->oldIdentity['ino'];return $old?(is_dir($this->failed)?'restored':'preflight'):'installed';}
    private function consume(string $key):bool {if(($this->fail[$key]??0)<1)return false;$this->fail[$key]--;return true;}
    public function status(string $path,int $expected,?string $stamp=null,string $marker='',int $attempts=3,?string $smokeToken=null):void
    {
        $key=$path.'='.$expected.($smokeToken!==null?'-behind-barrier':'');$this->calls[]=$key;$this->times[]=[$key,time()];if(isset($this->hooks[$key])){$hook=$this->hooks[$key];unset($this->hooks[$key]);$hook();}
        clearstatcache(true);$barrier=$this->active.'/storage/framework/maintenance.php';$body='';
        $passThrough=$smokeToken!==null&&is_file($barrier)&&str_contains((string)file_get_contents($barrier),hash('sha256',$smokeToken));
        if(is_file($barrier)&&!$passThrough){$observed=503;$body=(string)file_get_contents($barrier);$this->barrierSeen=true;}else $observed=in_array($path,['/login','/up'],true)?200:404;
        if($this->consume($key))$observed=$expected===200?500:200;
        if($observed===200&&$path==='/login'&&$this->uploadOnLogin&&$smokeToken===null){file_put_contents($this->active.'/storage/app/public/written-while-live','user-bytes');$this->uploadOnLogin=false;}
        if($observed===200&&$path==='/up'&&$this->logErrorOnUp){file_put_contents($this->active.'/storage/logs/laravel-'.date('Y-m-d').'.log','['.date('Y-m-d H:i:s').'] production.ERROR: injected fixture failure after the switch'."\n",FILE_APPEND);$this->logErrorOnUp=false;}
        $pass=$observed===$expected&&($stamp===null||str_contains($body,'ALD1N owned maintenance '.$stamp));
        echo 'PUBLIC_HTTP_SMOKE='.json_encode(['path'=>$path,'expected'=>$expected,'result'=>$pass?'PASS':'FAIL','attempts'=>1,'fixtureDouble'=>true]+($smokeToken!==null?['behindOwnedBarrier'=>true]:[])).PHP_EOL;if(!$pass)throw new RuntimeException('PUBLIC_HTTP_SMOKE_'.$expected.($smokeToken!==null?'_BEHIND_BARRIER':''));
    }
    private function spawnTitle(string $title):mixed
    {
        $p=proc_open(['php','-n','-r','cli_set_process_title($argv[1]);sleep(60);',$title,str_repeat('p',400)],[0=>['pipe','r'],1=>['file','/dev/null','w'],2=>['file','/dev/null','w']],$pipes);fclose($pipes[0]);
        for($i=0;$i<100;$i++){if(in_array($title,array_map('rtrim',LeanGates::processes()),true))return $p;usleep(50000);}throw new RuntimeException('COORDINATOR_FIXTURE_TITLE_NOT_VISIBLE');
    }
    public function sampled(string $path,callable $sample,string $marker=''):array
    {
        $this->calls[]='sampled:'.$path;$spawned=[];
        if($this->lsphpTitle)$spawned[]=$this->spawnTitle('lsphp:'.substr($this->active.'/public/index.php',-LeanGates::TITLE_SUFFIX_BYTES));if($this->otherSiteTitle)$spawned[]=$this->spawnTitle('lsphp:/home/icaffeco/other-site/public_html/index.php');
        for($i=0;$i<3;$i++){$sample();usleep(20000);}
        foreach($spawned as $p){proc_terminate($p,9);proc_close($p);}
        clearstatcache(true);$status=is_file($this->active.'/storage/framework/maintenance.php')?503:200;if($status===503)$this->barrierSeen=true;
        return ['status'=>$status,'transport'=>false,'samplesDuringRequest'=>3];
    }
    public function probe(string $name,string $token,bool $invalidate,?string $route=null):array
    {
        $this->seen[]=$name;$this->seen[]=$token;$label=$this->label();$this->calls[]='probe:'.$label;$this->labels[]=$label;
        if($this->consume('probe-'.$label))throw new RuntimeException('FRESHNESS_PROBE_HTTP_UNAVAILABLE');
        $server=['REQUEST_METHOD'=>'GET','HTTP_HOST'=>'cms.ald1n.com','HTTP_X_ALD1N_RELEASE_PROBE'=>$token,'DOCUMENT_ROOT'=>$this->active.'/public']+($invalidate?['HTTP_X_ALD1N_RELEASE_INVALIDATE'=>'YES']:[]);
        $compile=array_values(array_filter(array_map(fn($r)=>$this->active.'/'.$r,$this->compile),'is_file'));
        $p=proc_open(array_merge(['php','-d','opcache.enable=1','-d','opcache.enable_cli=1','-d','opcache.revalidate_freq=0','-d','opcache.file_update_protection=0','-d','realpath_cache_ttl='.$this->realpathTtl,$this->harness,$this->active.'/public/'.$name,json_encode($server)],$compile),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);proc_close($p);
        if($out==='')throw new RuntimeException('FRESHNESS_PROBE_HTTP_UNAVAILABLE');$data=json_decode($out,true,16,JSON_THROW_ON_ERROR);
        if($this->sapiOverride!==null&&in_array($label,$this->sapiLabels,true))$data['sapi']=$this->sapiOverride;if(array_key_exists($label,$this->realpathOverride))$data['realpathCacheTtl']=$this->realpathOverride[$label];foreach($this->override[$label]??[] as $k=>$v)$data[$k]=$v;
        $data['_route']='FIXTURE_CLI_HARNESS';return $data;
    }
}

function check(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('COORDINATOR_FIXTURE_'.$name);}
function setProperty(object $o,string $name,mixed $value):void{(new ReflectionProperty($o,$name))->setValue($o,$value);}
function getProperty(object $o,string $name):mixed{return (new ReflectionProperty($o,$name))->getValue($o);}
function invoke(object $o,string $name,mixed ...$args):mixed{return (new ReflectionMethod($o,$name))->invoke($o,...$args);}
function identity(string $path):array{clearstatcache(true);$s=stat($path);return ['dev'=>$s['dev'],'ino'=>$s['ino']];}
function run(array $args,?string $cwd=null):string{$p=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0)throw new RuntimeException('COORDINATOR_FIXTURE_COMMAND_'.basename($args[0]).':'.substr($err,-400));return trim($out);}
function probesUnder(array $bases):array{$found=[];foreach($bases as $b)if(is_dir($b.'/public'))foreach(scandir($b.'/public') as $n)if(str_starts_with($n,FreshnessProbe::PREFIX))$found[]=$b.'/public/'.$n;return $found;}
function barriers(array $bases):array{$found=[];foreach($bases as $b)foreach(['maintenance.php','down'] as $n)if(is_file($b.'/storage/framework/'.$n))$found[]=$b.'/storage/framework/'.$n;return $found;}
function lastJournal(string $journal):string{$lines=array_values(array_filter(explode("\n",file_get_contents($journal))));return json_decode(end($lines),true)['state'];}
function line(string $output,string $key):mixed{return preg_match('/^'.preg_quote($key,'/').'=(.+)$/m',$output,$m)?json_decode($m[1],true,64,JSON_THROW_ON_ERROR):null;}

$tools=$argv[1];$stage=$argv[2];$payload=$argv[3];$directoryDoc=$argv[4];$base=getenv('RUNNER_TEMP').'/b1144-coordinator';mkdir($base);$base=realpath($base);
$manifest=json_decode(file_get_contents($payload.'/runtime-manifest.json'),true,512,JSON_THROW_ON_ERROR);$directories=json_decode(file_get_contents($directoryDoc),true,512,JSON_THROW_ON_ERROR)['directories'];
check(count($manifest['files'])===8531,'MANIFEST_8531');

// One template pair: identical trees, except two app/ files that only main has and one routes/ file whose old content differs at an EQUAL mtime.
$template=$base.'/template';mkdir($template);InstallFileOps::candidate($stage,$template.'/old',$manifest,$directories);InstallFileOps::candidate($stage,$template.'/new',$manifest,$directories);
if(!is_dir($template.'/new/bootstrap/cache'))mkdir($template.'/new/bootstrap/cache',0755,true);
$appFiles=[];foreach($manifest['files'] as $f)if(preg_match('~^app/[A-Za-z0-9/_-]+\.php$~D',$f['path']))$appFiles[]=$f['path'];sort($appFiles,SORT_STRING);$mainOnly=array_slice($appFiles,0,2);foreach($mainOnly as $r)unlink($template.'/old/'.$r);
$route='routes/console.php';check(is_file($template.'/new/'.$route),'ROUTE_FILE');file_put_contents($template.'/old/'.$route,file_get_contents($template.'/old/'.$route)."\n// B1144 fixture: older deployed route content\n");clearstatcache();touch($template.'/old/'.$route,filemtime($template.'/new/'.$route));
$diagnostic=LeanGates::mtimeDiagnostic($template.'/old',$template.'/new',$manifest);check($diagnostic['absentInActive']===2&&$diagnostic['contentDiffersWithEqualMtimeWithin1s']>=1&&count($diagnostic['probeFiles'])===3&&isset($diagnostic['probeFiles'][$route]),'TEMPLATE_DIAGNOSTIC');
$migrationNames=LeanGates::migrationFiles($template.'/new/database/migrations');check(count($migrationNames)===71&&count(array_diff(InstallCoordinator::PENDING,$migrationNames))===0,'SEVENTY_ONE_MIGRATIONS');

$shim=$base.'/php-shim';file_put_contents($shim,"#!/bin/sh\nif [ -n \"\${B1144_FIXTURE_ARTISAN_FAIL:-}\" ]; then for a in \"\$@\"; do if [ \"\$a\" = \"\$B1144_FIXTURE_ARTISAN_FAIL\" ]; then exit 1; fi; done; fi\nexec php \"\$@\"\n");chmod($shim,0755);
// Scheduler transport double. A scheduler-fail-<action> file: "x" fails the next call once, "*" always, ">=N" from the N-th call of that action on.
$schedulerDouble='<?php $c=json_decode(file_get_contents($argv[1]),true);$d=__DIR__;file_put_contents($d."/scheduler-actions.log",$c["action"]."\n",FILE_APPEND);$f=$d."/scheduler-fail-".$c["action"];if(is_file($f)){$rule=trim(file_get_contents($f));$n=substr_count(file_get_contents($d."/scheduler-actions.log"),$c["action"]."\n");if($rule==="x"){unlink($f);exit(2);}if($rule==="*"||(str_starts_with($rule,">=")&&$n>=(int)substr($rule,2)))exit(2);}echo json_encode(["status"=>"ok","receipt"=>["explicitFixtureBoundaryDouble"=>true,"action"=>$c["action"]]]);';

$build=static function(string $case,int $applied=68)use($base,$template,$manifest,$directoryDoc,$payload,$schedulerDouble,$diagnostic,$migrationNames):array{
    $home=$base.'/'.$case;mkdir($home);$root=$home.'/project';mkdir($root.'/incoming',0755,true);mkdir($root.'/apps/cms',0755,true);$active=$root.'/apps/cms/current';$runtime=$home.'/.ald1n-b1134-retained/'.str_repeat('c',32);mkdir($runtime,0755,true);
    $candidate=$runtime.'/candidate';$retained=$runtime.'/retained';$failed=$runtime.'/failed';run(['cp','-a',$template.'/old',$active]);run(['cp','-a',$template.'/new',$candidate]);
    foreach(['storage/app/public','storage/logs','storage/framework/sessions','storage/framework/cache/data','storage/framework/views'] as $p)if(!is_dir($active.'/'.$p))mkdir($active.'/'.$p,0755,true);
    $db=$home.'/production-like.sqlite';$pdo=new PDO('sqlite:'.$db);$pdo->exec('create table migrations (id integer primary key autoincrement not null, migration varchar not null, batch integer not null)');$insert=$pdo->prepare('insert into migrations (migration,batch) values (?,1)');$ran=array_values(array_diff($migrationNames,InstallCoordinator::PENDING));foreach(array_slice($ran,0,$applied) as $m)$insert->execute([$m]);$pdo=null;
    // Host profile: LOG_CHANNEL=daily, so storage/logs holds laravel-YYYY-MM-DD.log files.
    $env=implode("\n",['APP_NAME=Ald1n','APP_ENV=production','APP_DEBUG=false','APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=','APP_URL=https://cms.ald1n.com','APP_MAINTENANCE_DRIVER=file','DB_CONNECTION=sqlite','DB_DATABASE='.$db,'SESSION_DRIVER=file','CACHE_STORE=file','QUEUE_CONNECTION=sync','MAIL_MAILER=array','LOG_CHANNEL=daily','LOG_LEVEL=debug','PUBLIC_STOREFRONT_ENABLED=true','PUBLIC_CHECKOUT_ENABLED=true','TURNSTILE_ENABLED=false','FIXTURE_PRIVATE_MARKER=secret-never-output','']);file_put_contents($active.'/.env',$env);chmod($active.'/.env',0600);
    file_put_contents($active.'/storage/logs/laravel-'.date('Y-m-d',time()-86400).'.log','['.date('Y-m-d H:i:s',time()-86400).'] production.ERROR: historical error before the release must not count'."\n");file_put_contents($active.'/storage/app/public/original','original-upload');
    $preserved=ReleasePrimitives::preservePrivate($active,$candidate,$retained);$config=$candidate.'/config/public_storefront.php';$original=file_get_contents($config);$clamped=ReleasePrimitives::offConfig($original,hash('sha256',$original));file_put_contents($config,$clamped);
    run(['git','init','--quiet'],$root);run(['git','-c','user.name=Fixture','-c','user.email=fixture@example.invalid','commit','--allow-empty','-m','fixture','--quiet'],$root);file_put_contents($root.'/index-sentinel','fixture');run(['git','add','index-sentinel'],$root);
    file_put_contents($root.'/incoming/b1134-scheduler.php',$schedulerDouble);
    $verification=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU/verification';mkdir($verification,0755,true);copy($payload.'/runtime-manifest.json',$verification.'/runtime-manifest.json');copy($directoryDoc,$verification.'/runtime-directories.json');
    $private=$home.'/.ald1n-b1144-state';mkdir($private,0700);$id=bin2hex(random_bytes(16));$transaction=$private.'/'.$id;mkdir($transaction,0700);
    $plan=['active'=>$active,'candidate'=>$candidate,'rollback'=>$retained,'failed'=>$failed,'oldIdentity'=>identity($active),'candidateIdentity'=>identity($candidate)];$journal=$transaction.'/journal.jsonl';$owner=ReleasePrimitives::createJournal($journal,$plan);
    $state=['schemaVersion'=>1,'id'=>$id,'private'=>$transaction,'runtime'=>$runtime,'plan'=>$plan,'journal'=>$journal,'owner'=>$owner,'envSHA256'=>hash_file('sha256',$active.'/.env'),'clampedConfigSHA256'=>hash('sha256',$clamped),'preserved'=>$preserved,'oldMaintenance'=>null,'newMaintenance'=>null,'scheduler'=>null,'schedulerPauseAttempted'=>false,'maintenanceStart'=>time(),'maintenanceExpires'=>time()+1500,'probeFiles'=>$diagnostic['probeFiles'],'probes'=>[]];
    return compact('home','root','active','runtime','candidate','retained','failed','plan','journal','owner','state','private','transaction');
};
$coordinator=static function(array $c,array $options=[])use($tools,$shim,$manifest,$directories,$diagnostic):array{
    $o=(new ReflectionClass(InstallCoordinator::class))->newInstanceWithoutConstructor();
    $web=$options['web']??new FixtureWeb($c['active'],$c['plan']['oldIdentity'],$c['failed'],$tools.'/probe-harness.php',array_keys($diagnostic['probeFiles']));
    foreach(['root'=>$c['root'],'home'=>$c['home'],'pointer'=>$c['private'].'/current.json','manifest'=>$manifest,'directoryDocument'=>['directories'=>$directories],'web'=>$web,'php'=>$shim,'drainSeconds'=>$options['drain']??8,'allowedSapis'=>['cli'],'probeSamples'=>2,'controlRequests'=>$options['control']??5] as $k=>$v)setProperty($o,$k,$v);
    return [$o,$web];
};
$execute=static function(array $c,InstallCoordinator $o):array{$state=$c['state'];setProperty($o,'state',$state);$state['git']=invoke($o,'gitSnapshot');setProperty($o,'state',$state);ob_start();invoke($o,'save','PREPARED');$rc=invoke($o,'execute');return [$rc,ob_get_clean()];};
$rerun=static function(InstallCoordinator $o):array{ob_start();$rc=$o->run();return [$rc,ob_get_clean()];};
$saved=static fn(array $c):array=>json_decode(file_get_contents($c['transaction'].'/state.json'),true,512,JSON_THROW_ON_ERROR);
$spawned=[];$titled=static function(array $args,?string $cwd=null)use(&$spawned):void{$p=proc_open($args,[0=>['pipe','r'],1=>['file','/dev/null','w'],2=>['file','/dev/null','w']],$pipes,$cwd);fclose($pipes[0]);$spawned[]=$p;usleep(700000);};
$killAll=static function()use(&$spawned):void{foreach($spawned as $p){$s=proc_get_status($p);if($s['running'])proc_terminate($p,9);proc_close($p);}$spawned=[];};
$actionsOf=static fn(array $c):array=>is_file($c['root'].'/incoming/scheduler-actions.log')?array_values(array_filter(explode("\n",file_get_contents($c['root'].'/incoming/scheduler-actions.log')))):[];

/** Common terminal assertions; returns the receipt. */
$verify=static function(string $case,array $c,int $rc,string $output,FixtureWeb $web,string $phase,int $expectedRC)use($saved,$coordinator,$rerun,$actionsOf):array{
    $s=$saved($c);check($rc===$expectedRC,$case.'_RC_'.$rc);check($s['phase']===$phase,$case.'_PHASE_'.$s['phase']);
    foreach($web->seen as $secret)check(!str_contains($output,$secret),$case.'_PROBE_NAME_OR_TOKEN_PRINTED');check(!str_contains($output,'secret-never-output')&&!str_contains($output,$c['state']['owner']),$case.'_SECRET_PRINTED');
    foreach(['newMaintenance','oldMaintenance'] as $k)if(is_array($s[$k]??null))check(!str_contains($output,$s[$k]['smokeToken']),$case.'_SMOKE_TOKEN_PRINTED');
    check(hash_file('sha256',$c['active'].'/.env')===$c['state']['envSHA256'],$case.'_ENV_PRESERVED');
    check(probesUnder([$c['active'],$c['candidate'],$c['retained'],$c['failed']])===[]||$case==='leftover-probe',$case.'_PROBE_REMOVED');check(glob($c['transaction'].'/probe-*.staging')===[],$case.'_PROBE_STAGING_REMOVED');
    check(file_get_contents($c['active'].'/storage/app/public/original')==='original-upload',$case.'_PRIVATE_DATA');
    if(in_array($phase,['CLOSED_INSTALL','ROLLED_BACK','ROLLED_BACK_PRE_SWITCH'],true)){
        check(barriers([$c['active'],$c['candidate'],$c['retained'],$c['failed']])===[],$case.'_ALL_BARRIERS_RELEASED');
        [$again]=$coordinator($c);[$rc2,$out2]=$rerun($again);check($rc2===($phase==='CLOSED_INSTALL'?0:2)&&str_contains($out2,$phase==='CLOSED_INSTALL'?'ALREADY_INSTALLED_OFF_NO_REPEAT':'PRIOR_ATTEMPT_ROLLED_BACK_NO_AUTOMATIC_REDEPLOY'),$case.'_NO_AUTOMATIC_REDEPLOY');
    }
    if($phase==='ROLLED_BACK'){check(identity($c['active'])===$c['plan']['oldIdentity']&&identity($c['failed'])===$c['plan']['candidateIdentity']&&!file_exists($c['retained']),$case.'_OLD_CODE_RESTORED');check(isset($s['freshness']['restored'])&&str_contains($output,'"label":"restored"'),$case.'_RESTORED_FRESHNESS');check(lastJournal($c['journal'])==='ROLLED_BACK',$case.'_JOURNAL_ROLLED_BACK');
        // The restored code is lifted only after the hold for the swap back (probe-derived, or the pre-switch fallback).
        $hold=$s['freshness']['restored']['hold']??[];$lastLogin=null;foreach($web->times as [$call,$at])if($call==='/login=200')$lastLogin=$at;
        $derived=($hold['source']??null)==='PRE_SWITCH_HOST_PROBE_COUNTED_FROM_NOW'?is_int($hold['liftNotBefore']??null)&&$hold['liftNotBefore']>=$hold['probedAt']+FreshnessProbe::HOLD_MARGIN&&$hold['liftNotBefore']>=$hold['swappedAt']+$hold['realpathCacheTtl']+FreshnessProbe::HOLD_MARGIN:is_int($hold['liftNotBefore']??null)&&$hold['liftNotBefore']===max($hold['swappedAt']+$hold['realpathCacheTtl'],$hold['probedAt'])+FreshnessProbe::HOLD_MARGIN;
        check(str_contains($output,'BARRIER_HOLD={"label":"restored"')&&$derived&&$lastLogin!==null&&$lastLogin>=$hold['liftNotBefore']&&($hold['heldUntil']??0)>=$hold['liftNotBefore'],$case.'_RESTORED_LIFT_AFTER_DERIVED_HOLD_'.json_encode($hold));}
    if($phase==='ROLLED_BACK_PRE_SWITCH'){check(identity($c['active'])===$c['plan']['oldIdentity']&&identity($c['candidate'])===$c['plan']['candidateIdentity']&&!file_exists($c['retained'])&&!file_exists($c['failed']),$case.'_CANDIDATE_UNTOUCHED');}
    $actions=$actionsOf($c);
    if(in_array($phase,['CLOSED_INSTALL','ROLLED_BACK'],true)||($phase==='ROLLED_BACK_PRE_SWITCH'&&$actions!==[]))check(end($actions)==='release',$case.'_SCHEDULER_RELEASED_LAST');
    preg_match_all('/^RELEASE_PHASE="([A-Z_]+)"$/m',$output,$m);
    return ['returnCode'=>$rc,'phase'=>$s['phase'],'holdReason'=>preg_match('/^INSTALL_HOLD_REASON="([A-Z0-9_]+)"$/m',$output,$h)?$h[1]:null,'phases'=>$m[1],'schedulerActions'=>$actions,'httpCalls'=>$web->calls,'probeNameOrTokenPrinted'=>false];
};
/** Zero downtime: the run never created a barrier, never paused the scheduler and every response it saw was unbarriered. */
$noDowntime=static function(string $case,array $c,array $receipt,FixtureWeb $web)use($actionsOf):void{
    check(!$web->barrierSeen&&!in_array('/login=503',$web->calls,true)&&$actionsOf($c)===[]&&array_intersect(['NEW_MAINTENANCE_INTENT','CANDIDATE_READY','SCHEDULER_PAUSE_INTENT','MAINTENANCE_INTENT','SWITCHED'],$receipt['phases'])===[],$case.'_ZERO_DOWNTIME');
};

$receipts=[];
// Full success, then re-entry.
$c=$build('success');[$o,$web]=$coordinator($c);$web->uploadOnLogin=true;$web->realpathTtl='9';[$rc,$out]=$execute($c,$o);$receipts['success']=$verify('success',$c,$rc,$out,$web,'CLOSED_INSTALL',0);$s=$saved($c);
check(identity($c['active'])===$c['plan']['candidateIdentity']&&identity($c['retained'])===$c['plan']['oldIdentity'],'SUCCESS_SWITCHED');check(lastJournal($c['journal'])==='CLOSED','SUCCESS_JOURNAL_CLOSED');
check($s['cliBoot']['ran']===68&&$s['cliBoot']['pending']===InstallCoordinator::PENDING&&str_contains($out,'MIGRATE_STATUS_READ_ONLY={"rc":0,"ran":68,"pending":["2026_10_23_020000_create_public_order_accesses","2026_10_23_020100_create_public_checkout_attempts","2026_10_24_030000_add_retail_customer_role"],"migrateExecuted":false}'),'SUCCESS_MIGRATE_STATUS');
check($s['treeVerification']['verifiedFiles']===8531&&$s['logScan']['newErrorLines']===0&&$s['freshness']['installed']['samples'][0]['treeFilesVerified']===3&&$s['freshness']['installed']['samples'][0]['targetedInvalidations']>=1,'SUCCESS_GATES');
check(str_contains($out,'CANONICAL_STATIC_CHECK="NOT_RUN_NOT_AN_EXISTING_INSTALL_GATE"')&&str_contains($out,'CMS_DRAIN={"drained":true'),'SUCCESS_REPORT_LINES');
$order=$receipts['success']['phases'];foreach([['FRESHNESS_PROBE_INTENT_PREFLIGHT','HOST_PREFLIGHT_PASSED'],['HOST_PREFLIGHT_PASSED','NEW_MAINTENANCE_INTENT'],['NEW_MAINTENANCE_INTENT','CANDIDATE_READY'],['CANDIDATE_READY','SCHEDULER_PAUSE_INTENT'],['SCHEDULER_PAUSED','MAINTENANCE_INTENT'],['MAINTENANCE_OWNED','CMS_DRAINED'],['CMS_DRAINED','CANDIDATE_LOCAL_VERIFIED'],['LOG_BASELINE_RECORDED','SWITCHED'],['SWITCHED','FRESHNESS_VERIFIED'],['FRESHNESS_VERIFIED','CLI_BOOT_VERIFIED'],['CLI_BOOT_VERIFIED','BARRIER_LIFTED_SMOKE_PENDING'],['BARRIER_LIFTED_SMOKE_PENDING','COMMITTED_OFF']] as [$a,$b])check(array_search($a,$order,true)!==false&&array_search($a,$order,true)<array_search($b,$order,true),'SUCCESS_ORDER_'.$a.'_'.$b);
$liftIndex=array_search('/login=200',$web->calls,true);check($liftIndex!==false&&array_slice($web->calls,$liftIndex,5)===['/login=200','/up=200','/katalog=404','/registracija=404','/korpa=404'],'SUCCESS_POST_LIFT_SMOKES');check(file_get_contents($c['retained'].'/storage/app/public/written-while-live')==='user-bytes','SUCCESS_LIVE_WRITE_IN_SHARED_STORAGE');
check($receipts['success']['schedulerActions']===['pause','verify','verify','release'],'SUCCESS_SCHEDULER_SEQUENCE');
// Pre-barrier host probe on the OLD tree: every gate PASS, the values printed, positive control observed, BEFORE any barrier.
$pre=line($out,'HOST_PREFLIGHT');check(is_array($pre)&&$pre['blockers']===[]&&array_unique(array_values($pre['gates']))===['PASS']&&$pre['values']['realpathCacheTtl']===9&&$pre['values']['holdSeconds']===14&&str_starts_with((string)$pre['values']['php'],'8.4.')&&$pre['values']['sapi']==='cli'&&$pre['reachability']['route']==='FIXTURE_CLI_HARNESS'&&$pre['drainPositiveControl']['observed']===true&&$pre['dailyLog']['dailyFiles']===1&&$pre['barrier']==='NONE_YET'&&$pre['invalidationRequested']===false,'SUCCESS_HOST_PREFLIGHT_'.json_encode($pre));
check($web->labels[0]==='preflight'&&array_search('sampled:/login',$web->calls,true)<array_search('/login=503',$web->calls,true)&&strpos($out,'HOST_PREFLIGHT=')<strpos($out,'RELEASE_PHASE="NEW_MAINTENANCE_INTENT"'),'SUCCESS_PREFLIGHT_BEFORE_BARRIER');
// The hold comes from the probe process's REAL realpath_cache_ttl (9, not the PHP default), counted from the swap, and the lift waits for it.
$h=$s['freshness']['installed']['hold'];$firstLogin=null;foreach($web->times as [$call,$at])if($call==='/login=200'){$firstLogin=$at;break;}
check($h['realpathCacheTtl']===9&&$s['freshness']['installed']['samples'][0]['realpathCacheTtl']===9&&$h['swappedAt']===$s['swappedAt']['installed']&&$h['liftNotBefore']===max($h['swappedAt']+9,$h['probedAt'])+FreshnessProbe::HOLD_MARGIN&&$h['heldUntil']>=$h['liftNotBefore']&&$firstLogin!==null&&$firstLogin>=$h['liftNotBefore']&&$h['clock']==='MONOTONIC','SUCCESS_HOLD_DERIVED_FROM_PROBE_INI_'.json_encode($h));
check(str_contains($out,'BARRIER_HOLD_START={"label":"installed"')&&array_search('CLI_BOOT_VERIFIED',$order,true)<array_search('BARRIER_LIFT_INTENT',$order,true)&&strpos($out,'BARRIER_HOLD={"label":"installed"')<strpos($out,'RELEASE_PHASE="BARRIER_LIFT_INTENT"'),'SUCCESS_HOLD_BEFORE_LIFT');
$receipts['success']['barrierHold']=$h;$receipts['success']['hostPreflight']=$pre;

// (a) PERSISTENT host fault: the same predicate fails for preflight, installed and restored, in two separate attempts.
foreach([1,2] as $attempt){
    $case='persistent-host-sapi-'.$attempt;$c=$build($case);[$o,$web]=$coordinator($c);$web->sapiOverride='apache2handler';$web->sapiLabels=['preflight','installed','restored'];[$rc,$out]=$execute($c,$o);
    $receipts[$case]=$verify($case,$c,$rc,$out,$web,'ROLLED_BACK_PRE_SWITCH',2);$noDowntime($case,$c,$receipts[$case],$web);$p=line($out,'HOST_PREFLIGHT');
    check($receipts[$case]['holdReason']==='HOST_PREFLIGHT_BLOCKED'&&$p['blockers']===['FRESHNESS_PROBE_SAPI']&&$p['gates']['sapi']==='FRESHNESS_PROBE_SAPI'&&$p['values']['sapi']==='apache2handler'&&$p['reachability']['sapis']===['apache2handler']&&str_starts_with((string)$p['values']['php'],'8.4.')&&is_int($p['values']['holdSeconds'])&&is_int($p['values']['realpathCacheTtl'])&&isset($p['values']['opcacheEnabled'],$p['values']['revalidateFreq'],$p['values']['realpathCacheSize'])&&$p['drainPositiveControl']['observed']===true&&str_contains($out,'ORIGINAL_APPLICATION_CODE_VENDOR_UNTOUCHED=true')&&$web->labels===['preflight','preflight'],$case.'_HOLD_PRE_SWITCH_WITH_VALUES_'.json_encode($p));
    $receipts[$case]['hostPreflight']=$p;
}
// Collect-all: several host blockers at once are ALL reported in one run.
$c=$build('collect-all-host-blockers');[$o,$web]=$coordinator($c,['control'=>2]);$web->sapiOverride='apache2handler';$web->sapiLabels=['preflight'];$web->override=['preflight'=>['revalidateFreq'=>61,'docrootMatches'=>false]];$web->lsphpTitle=false;foreach(glob($c['active'].'/storage/logs/laravel-*.log') as $f)unlink($f);
[$rc,$out]=$execute($c,$o);$receipts['collect-all-host-blockers']=$verify('collect-all-host-blockers',$c,$rc,$out,$web,'ROLLED_BACK_PRE_SWITCH',2);$noDowntime('collect-all-host-blockers',$c,$receipts['collect-all-host-blockers'],$web);$p=line($out,'HOST_PREFLIGHT');
check($p['blockers']===['FRESHNESS_PROBE_SAPI','FRESHNESS_PROBE_DOCROOT','FRESHNESS_REVALIDATE_FREQ_UNSUPPORTED','DRAIN_POSITIVE_CONTROL_NOT_OBSERVED','LOG_GATE_NO_DAILY_LARAVEL_LOG']&&$p['values']['revalidateFreq']===61&&$p['values']['docrootMatches']===false&&$p['dailyLog']['dailyFiles']===0,'COLLECT_ALL_'.json_encode($p));$receipts['collect-all-host-blockers']['hostPreflight']=$p;
// (c) Hold bound rejected PRE-switch: realpath_cache_ttl 600 gives 605 s > 600 s.
$c=$build('preflight-hold-bound');[$o,$web]=$coordinator($c);$web->realpathOverride=['preflight'=>'600','installed'=>'600','restored'=>'600'];[$rc,$out]=$execute($c,$o);$receipts['preflight-hold-bound']=$verify('preflight-hold-bound',$c,$rc,$out,$web,'ROLLED_BACK_PRE_SWITCH',2);$noDowntime('preflight-hold-bound',$c,$receipts['preflight-hold-bound'],$web);$p=line($out,'HOST_PREFLIGHT');
check($p['blockers']===['BARRIER_HOLD_EXCEEDS_BOUND']&&$p['values']['holdSeconds']===605&&$p['values']['maxHoldSeconds']===600&&$p['gates']['holdBound']==='BARRIER_HOLD_EXCEEDS_BOUND','PREFLIGHT_HOLD_BOUND_'.json_encode($p));
// (d) Drain positive control: this CMS's busy title never seen (only another site's) -> HOLD pre-switch printing the titles seen.
$c=$build('positive-control-not-observed');[$o,$web]=$coordinator($c,['control'=>3]);$web->lsphpTitle=false;$web->otherSiteTitle=true;[$rc,$out]=$execute($c,$o);$receipts['positive-control-not-observed']=$verify('positive-control-not-observed',$c,$rc,$out,$web,'ROLLED_BACK_PRE_SWITCH',2);$noDowntime('positive-control-not-observed',$c,$receipts['positive-control-not-observed'],$web);$p=line($out,'HOST_PREFLIGHT');
check($p['blockers']===['DRAIN_POSITIVE_CONTROL_NOT_OBSERVED']&&$p['drainPositiveControl']['observed']===false&&$p['drainPositiveControl']['requests']===3&&in_array('lsphp:/home/icaffeco/other-site/public_html/index.php',$p['drainPositiveControl']['lsphpTitlesSeen'],true)&&$p['drainPositiveControl']['expectedTitle']==='lsphp:'.substr($c['active'].'/public/index.php',-44),'POSITIVE_CONTROL_'.json_encode($p['drainPositiveControl']));
// Log gate: no daily laravel-*.log -> HOLD pre-switch with the explanation.
$c=$build('no-daily-log');[$o,$web]=$coordinator($c);foreach(glob($c['active'].'/storage/logs/laravel-*.log') as $f)unlink($f);file_put_contents($c['active'].'/storage/logs/laravel.log','single channel');[$rc,$out]=$execute($c,$o);$receipts['no-daily-log']=$verify('no-daily-log',$c,$rc,$out,$web,'ROLLED_BACK_PRE_SWITCH',2);$noDowntime('no-daily-log',$c,$receipts['no-daily-log'],$web);$p=line($out,'HOST_PREFLIGHT');
check($p['blockers']===['LOG_GATE_NO_DAILY_LARAVEL_LOG']&&str_contains($p['dailyLog']['explanation'],'LOG_CHANNEL=daily'),'NO_DAILY_LOG_'.json_encode($p['dailyLog']));
// Probe unreachable pre-switch: the other gates are still evaluated and printed; the probe file is removed.
$c=$build('preflight-unreachable');[$o,$web]=$coordinator($c);$web->fail=['probe-preflight'=>99];[$rc,$out]=$execute($c,$o);$receipts['preflight-unreachable']=$verify('preflight-unreachable',$c,$rc,$out,$web,'ROLLED_BACK_PRE_SWITCH',2);$noDowntime('preflight-unreachable',$c,$receipts['preflight-unreachable'],$web);$p=line($out,'HOST_PREFLIGHT');
check($p['gates']['probeReachable']==='FRESHNESS_PROBE_HTTP_UNAVAILABLE'&&$p['gates']['sapi']==='NOT_EVALUATED_PROBE_UNREACHABLE'&&$p['gates']['drainPositiveControl']==='PASS'&&$p['gates']['dailyLaravelLog']==='PASS'&&$p['values']===null,'PREFLIGHT_UNREACHABLE_'.json_encode($p));

// Pre-switch HOLDs after the barrier: the candidate is never renamed.
$preSwitch=['barrier-not-observed'=>['web'=>['/login=503'=>1],'reason'=>'PUBLIC_HTTP_SMOKE_503'],'drain-cms-worker'=>['title'=>'cms','reason'=>'CMS_WORKERS_OR_ARTISAN_NOT_DRAINED'],'drain-artisan'=>['title'=>'artisan','reason'=>'CMS_WORKERS_OR_ARTISAN_NOT_DRAINED'],'candidate-tamper'=>['tamper'=>true,'reason'=>'CANDIDATE_LOCAL_SHA256_MISMATCH']];
foreach($preSwitch as $case=>$spec){
    $c=$build($case);[$o,$web]=$coordinator($c,['drain'=>3]);$web->fail=$spec['web']??[];
    if(($spec['title']??null)==='cms')$titled(['php','-n','-r','cli_set_process_title($argv[1]);sleep(120);','lsphp:'.substr($c['active'].'/public/index.php',-LeanGates::TITLE_SUFFIX_BYTES),str_repeat('p',400)]);
    // "cd <cms> && php artisan ..." as cron runs it: recognised by the working directory, not by a path argument.
    if(($spec['title']??null)==='artisan'){file_put_contents($c['root'].'/artisan','<?php sleep(120);');$titled(['php','-n','../../../artisan','queue:work'],$c['active']);}
    if($spec['tamper']??false){$victim=null;foreach($manifest['files'] as $f)if(str_starts_with($f['path'],'vendor/')&&str_ends_with($f['path'],'.php')){$victim=$f['path'];break;}file_put_contents($c['candidate'].'/'.$victim,"\n",FILE_APPEND);}
    [$rc,$out]=$execute($c,$o);$killAll();$receipts[$case]=$verify($case,$c,$rc,$out,$web,'ROLLED_BACK_PRE_SWITCH',2);check($receipts[$case]['holdReason']===$spec['reason'],$case.'_REASON_'.$receipts[$case]['holdReason']);
    check(!in_array('SWITCHED',$receipts[$case]['phases'],true)&&str_contains($out,'ORIGINAL_APPLICATION_CODE_VENDOR_UNTOUCHED=true'),$case.'_NEVER_SWITCHED');
}

// Post-switch failures at every stage, including after the lift: automatic rollback with restored-tree freshness.
$postSwitch=[
    'warmup-failure'=>['env'=>'view:cache','reason'=>'CANDIDATE_TECHNICAL_WARMUP_FAILED','lifted'=>false],
    'probe-http-unavailable'=>['web'=>['probe-installed'=>1],'reason'=>'FRESHNESS_PROBE_HTTP_UNAVAILABLE','lifted'=>false],
    'probe-wrong-sapi'=>['sapi'=>'apache2handler','reason'=>'FRESHNESS_PROBE_SAPI','lifted'=>false],
    'leftover-probe'=>['leftover'=>true,'reason'=>'LEFTOVER_FRESHNESS_PROBE_REQUIRES_OPERATOR_INSPECTION','lifted'=>false],
    'cli-about-failure'=>['env'=>'about','reason'=>'CLI_ABOUT_FAILED','lifted'=>false],
    'migrate-status-failure'=>['env'=>'migrate:status','reason'=>'MIGRATE_STATUS_FAILED','lifted'=>false],
    'migrate-pending-mismatch'=>['applied'=>67,'reason'=>'MIGRATION_PENDING_SET_MISMATCH','lifted'=>false],
    'scheduler-verify-post-switch'=>['scheduler'=>['verify'=>'>=2'],'reason'=>'SCHEDULER_VERIFY_HOLD','lifted'=>false],
    'lift-login-500'=>['web'=>['/login=200'=>1],'reason'=>'PUBLIC_HTTP_SMOKE_200','lifted'=>true],
    'lift-up-500'=>['web'=>['/up=200'=>1],'reason'=>'PUBLIC_HTTP_SMOKE_200','lifted'=>true],
    'lift-katalog-200'=>['web'=>['/katalog=404'=>1],'reason'=>'PUBLIC_HTTP_SMOKE_404','lifted'=>true],
    'lift-registracija-200'=>['web'=>['/registracija=404'=>1],'reason'=>'PUBLIC_HTTP_SMOKE_404','lifted'=>true],
    'lift-korpa-200'=>['web'=>['/korpa=404'=>1],'reason'=>'PUBLIC_HTTP_SMOKE_404','lifted'=>true],
    'lift-new-log-error'=>['logError'=>true,'reason'=>'NEW_LARAVEL_LOG_ERRORS_SINCE_SWITCH','lifted'=>true],
    'realpath-ttl-unreadable'=>['realpath'=>['installed'=>null],'reason'=>'FRESHNESS_REALPATH_CACHE_UNREADABLE','lifted'=>false],
    'realpath-ttl-not-a-number'=>['realpath'=>['installed'=>'2m'],'reason'=>'FRESHNESS_REALPATH_CACHE_UNREADABLE','lifted'=>false],
    'hold-exceeds-bound'=>['realpath'=>['installed'=>'100000'],'reason'=>'BARRIER_HOLD_EXCEEDS_BOUND','lifted'=>false],
];
foreach($postSwitch as $case=>$spec){
    $c=$build($case,$spec['applied']??68);[$o,$web]=$coordinator($c);$web->fail=$spec['web']??[];$web->sapiOverride=$spec['sapi']??null;$web->logErrorOnUp=$spec['logError']??false;$web->uploadOnLogin=$spec['lifted'];$web->realpathOverride=$spec['realpath']??[];
    foreach($spec['scheduler']??[] as $action=>$rule)file_put_contents($c['root'].'/incoming/scheduler-fail-'.$action,$rule);
    if($spec['leftover']??false)file_put_contents($c['candidate'].'/public/'.FreshnessProbe::PREFIX.str_repeat('e',48).'.php','<?php // leftover from an unknown earlier run');
    putenv('B1144_FIXTURE_ARTISAN_FAIL='.($spec['env']??''));[$rc,$out]=$execute($c,$o);putenv('B1144_FIXTURE_ARTISAN_FAIL');
    $receipts[$case]=$verify($case,$c,$rc,$out,$web,'ROLLED_BACK',2);check($receipts[$case]['holdReason']===$spec['reason'],$case.'_REASON_'.$receipts[$case]['holdReason']);
    $phases=$receipts[$case]['phases'];check(in_array('SWITCHED',$phases,true)&&in_array('OLD_RESTORED_MAINTENANCE_HELD',$phases,true),$case.'_SWITCHED_THEN_RESTORED');
    check(in_array('BARRIER_LIFTED_SMOKE_PENDING',$phases,true)===$spec['lifted'],$case.'_LIFT_STAGE');if($spec['lifted']){check(in_array('ROLLBACK_BARRIER_OWNED',$phases,true)&&str_contains($out,'OWNED_BARRIER_REIMPOSED'),$case.'_BARRIER_REIMPOSED_AFTER_LIFT');check(file_get_contents($c['active'].'/storage/app/public/written-while-live')==='user-bytes',$case.'_LIVE_WRITE_SURVIVES_ROLLBACK');}
    check(array_slice($receipts[$case]['httpCalls'],-1)===['/login=200'],$case.'_ORIGINAL_LOGIN_VERIFIED');
    if($spec['leftover']??false){$left=probesUnder([$c['failed']]);check(count($left)===1&&file_get_contents($left[0])==='<?php // leftover from an unknown earlier run','LEFTOVER_NOT_OVERWRITTEN_OR_DELETED');}
    if(isset($spec['realpath']))check(!in_array('CLI_BOOT_VERIFIED',$phases,true)&&!str_contains($out,'BARRIER_HOLD={"label":"installed"')&&($spec['reason']!=='BARRIER_HOLD_EXCEEDS_BOUND'||str_contains($out,'BARRIER_HOLD_BOUND={"label":"installed"')),$case.'_INSTALLED_HOLD_FAILS_CLOSED_BEFORE_LIFT');
    if(isset($spec['scheduler']))check(str_contains($out,'ROLLBACK_PREFIX_STEP={"step":"SCHEDULER_VERIFY_REPORT_ONLY","result":"SCHEDULER_VERIFY_HOLD","rollbackProceeds":true}')&&in_array('OLD_RESTORED_MAINTENANCE_HELD',$phases,true),$case.'_SCHEDULER_VERIFY_NOT_A_ROLLBACK_PRECONDITION');
}

// (b) Restored-phase probe failures never wedge the site: one targeted-invalidation retry, then the pre-switch fallback hold,
// a private /login 200 re-check behind the barrier, and only then the lift.
foreach(['restored-realpath-ttl-unreadable'=>[['restored'=>null],[],'FRESHNESS_REALPATH_CACHE_UNREADABLE',true],'restored-hold-exceeds-bound'=>[['restored'=>'100000'],[],'BARRIER_HOLD_EXCEEDS_BOUND',true],'restored-freshness-retry'=>[[],['probe-restored'=>1],'FRESHNESS_PROBE_HTTP_UNAVAILABLE',false],'restored-probe-persistent-http'=>[[],['probe-restored'=>99],'FRESHNESS_PROBE_HTTP_UNAVAILABLE',true]] as $case=>[$realpath,$faults,$code,$fallback]){
    $c=$build($case);[$o,$web]=$coordinator($c);$web->fail=['/login=200'=>1]+$faults;$web->uploadOnLogin=true;$web->realpathOverride=$realpath;[$rc,$out]=$execute($c,$o);
    $receipts[$case]=$verify($case,$c,$rc,$out,$web,'ROLLED_BACK',2);$s=$saved($c);
    check(str_contains($out,'RESTORED_FRESHNESS_ATTEMPT_FAILED={"attempt":1,"code":"'.$code.'"}')&&str_contains($out,'RESTORED_FRESHNESS_ATTEMPT_FAILED={"attempt":2')===$fallback&&($s['freshness']['restored']['fallback']??false)===$fallback,$case.'_RETRY_THEN_FALLBACK');
    if($fallback){$calls=$web->calls;$smoke=array_search('/login=200-behind-barrier',$calls,true);check(str_contains($out,'RESTORED_FRESHNESS_FALLBACK={"label":"restored"')&&str_contains($out,'RESTORED_FALLBACK_RECHECK="PASS_OLD_CODE_LOGIN_200_BEHIND_OWNED_BARRIER"')&&$smoke!==false&&$smoke<count($calls)-1&&end($calls)==='/login=200'&&$s['freshness']['restored']['hold']['source']==='PRE_SWITCH_HOST_PROBE_COUNTED_FROM_NOW'&&strpos($out,'BARRIER_HOLD={"label":"restored"')<strpos($out,'RESTORED_FALLBACK_RECHECK='),$case.'_FALLBACK_RECHECK_BEFORE_LIFT');}
}
// Fallback re-check fails (old code does not answer /login 200 behind the barrier): barrier retained in a DISTINCT phase with the
// documented operator lift command; a re-run retries; after the printed lift the re-run finishes without re-imposing the barrier.
$c=$build('restored-recheck-fails');[$o,$web]=$coordinator($c);$web->fail=['/login=200'=>1,'/login=200-behind-barrier'=>99];$web->realpathOverride=['restored'=>null];[$rc,$out]=$execute($c,$o);$s=$saved($c);
$lift=line($out,'OPERATOR_LIFT_COMMAND');check($rc===2&&$s['phase']==='RESTORED_BARRIER_RETAINED_OPERATOR_LIFT'&&identity($c['active'])===$c['plan']['oldIdentity']&&is_file($c['active'].'/storage/framework/maintenance.php')&&is_string($lift),'RECHECK_FAIL_LINE_SHAPE');
check(preg_match('/^OPERATOR_LIFT_COMMAND="(rm -f [^"]+ && bash [^"]+)"$/m',$out,$cmd)===1&&str_contains($cmd[1],$c['active'].'/storage/framework/down '.$c['active'].'/storage/framework/maintenance.php')&&str_ends_with($cmd[1],'/incoming/b1144-controlled-install.sh')&&str_contains($out,'RECOVERY_HOLD="RESTORED_BARRIER_RETAINED_OPERATOR_LIFT"'),'RECHECK_FAIL_DOCUMENTED_LIFT_'.substr($out,-1500));
[$again,$web2]=$coordinator($c);$web2->fail=['/login=200-behind-barrier'=>99];$web2->realpathOverride=['restored'=>null];[$rc2,$out2]=$rerun($again);check($rc2===2&&$saved($c)['phase']==='RESTORED_BARRIER_RETAINED_OPERATOR_LIFT'&&is_file($c['active'].'/storage/framework/maintenance.php'),'RECHECK_FAIL_RERUN_STAYS_DOCUMENTED');
run(['sh','-c',explode(' && ',$cmd[1])[0]]);check(barriers([$c['active']])===[],'OPERATOR_LIFT_COMMAND_REMOVES_BARRIER');
[$again,$web3]=$coordinator($c);[$rc3,$out3]=$rerun($again);$web3->seen=array_merge($web->seen,$web2->seen,$web3->seen);$web3->times=array_merge($web->times,$web3->times);
check(str_contains($out3,'OPERATOR_LIFTED_RESTORED_BARRIER')&&!str_contains($out3,'OWNED_BARRIER_REIMPOSED')&&!in_array('probe:restored',$web3->calls,true)&&$web3->calls===['/login=200'],'OPERATOR_LIFT_NOT_REIMPOSED_'.json_encode($web3->calls));
$receipts['restored-recheck-fails']=$verify('restored-recheck-fails',$c,$rc3,$out.$out2.$out3,$web3,'ROLLED_BACK',2)+['firstAttempt'=>'RESTORED_BARRIER_RETAINED_OPERATOR_LIFT','rerunWithoutLift'=>'RESTORED_BARRIER_RETAINED_OPERATOR_LIFT','afterOperatorLift'=>'ROLLED_BACK'];

// (b) Rollback prefix: drain timeout, unwritable state and a foreign barrier file never stop the directory rename back.
$c=$build('recovery-drain-timeout');[$o,$web]=$coordinator($c,['drain'=>3]);$web->uploadOnLogin=true;$web->fail=['/up=200'=>1];$web->hooks['/up=200']=fn()=>$titled(['php','-n','-r','cli_set_process_title($argv[1]);sleep(120);','lsphp:'.substr($c['active'].'/public/index.php',-LeanGates::TITLE_SUFFIX_BYTES),str_repeat('p',400)]);
[$rc,$out]=$execute($c,$o);$killAll();$receipts['recovery-drain-timeout']=$verify('recovery-drain-timeout',$c,$rc,$out,$web,'ROLLED_BACK',2);
check(str_contains($out,'CMS_DRAIN_TIMEOUT=')&&str_contains($out,'ROLLBACK_PREFIX_STEP={"step":"RECOVERY_DRAIN_BOUNDED","result":"CMS_WORKERS_OR_ARTISAN_NOT_DRAINED","rollbackProceeds":true}')&&strpos($out,'RECOVERY_DRAIN_BOUNDED')<strpos($out,'RELEASE_PHASE="OLD_RESTORED_MAINTENANCE_HELD"'),'RECOVERY_DRAIN_BOUNDED_THEN_RENAME');
$c=$build('recovery-state-unwritable');[$o,$web]=$coordinator($c);$web->uploadOnLogin=true;$web->fail=['/up=200'=>1];$web->hooks['/up=200']=fn()=>chmod($c['transaction'],0500);[$rc,$out]=$execute($c,$o);chmod($c['transaction'],0700);$s=$saved($c);
check($rc===2&&$s['phase']==='BARRIER_LIFTED_SMOKE_PENDING'&&identity($c['active'])===$c['plan']['oldIdentity']&&identity($c['failed'])===$c['plan']['candidateIdentity']&&is_file($c['active'].'/storage/framework/maintenance.php')&&str_contains($out,'ROLLBACK_PREFIX_STEP={"step":"ROLLBACK_BARRIER_INTENT","result":"STATE_CREATE","rollbackProceeds":true}')&&str_contains($out,'RECOVERY_STATE_UNWRITABLE="FIX_DISK_OR_PERMISSIONS_THEN_RERUN_SAME_COMMAND"')&&lastJournal($c['journal'])==='ROLLED_BACK','STATE_UNWRITABLE_OLD_CODE_BEHIND_BARRIER_'.substr($out,-1200));
[$again,$web2]=$coordinator($c);[$rc2,$out2]=$rerun($again);$web2->seen=array_merge($web->seen,$web2->seen);$receipts['recovery-state-unwritable']=$verify('recovery-state-unwritable',$c,$rc2,$out.$out2,$web2,'ROLLED_BACK',2)+['firstAttempt'=>'OLD_CODE_RESTORED_BEHIND_BARRIER_STATE_UNWRITABLE','operatorRerun'=>'ROLLED_BACK'];
$c=$build('recovery-foreign-barrier-file');[$o,$web]=$coordinator($c);$web->uploadOnLogin=true;$web->fail=['/up=200'=>1];$foreign=$c['active'].'/storage/framework/down';$web->hooks['/up=200']=fn()=>file_put_contents($foreign,'{"foreign":"artisan down by someone else"}');[$rc,$out]=$execute($c,$o);
check($rc===2&&$saved($c)['phase']==='ROLLED_BACK'&&str_contains($out,'ROLLBACK_PREFIX_STEP={"step":"BARRIER_ON_NEW_ACTIVE_TREE","result":"MAINTENANCE_FOREIGN_CONTENT","rollbackProceeds":true}')&&str_contains($out,'INACTIVE_TREE_BARRIER_NOT_CLEARED="MAINTENANCE_FOREIGN_CONTENT"')&&barriers([$c['active'],$c['failed'],$c['candidate'],$c['retained']])===[$c['failed'].'/storage/framework/down']&&file_get_contents($c['failed'].'/storage/framework/down')==='{"foreign":"artisan down by someone else"}','FOREIGN_BARRIER_FILE_DOES_NOT_BLOCK_ROLLBACK_'.substr($out,-1500));
unlink($c['failed'].'/storage/framework/down');$receipts['recovery-foreign-barrier-file']=$verify('recovery-foreign-barrier-file',$c,$rc,$out,$web,'ROLLED_BACK',2)+['foreignFileKeptOnInactiveTree'=>true];

// (b)/L2 Scheduler release failure after a SWITCHED rollback: no barrier is retained, the re-run only retries the release.
$c=$build('scheduler-release-after-rollback');[$o,$web]=$coordinator($c);$web->uploadOnLogin=true;$web->fail=['/up=200'=>1];file_put_contents($c['root'].'/incoming/scheduler-fail-release','x');[$rc,$out]=$execute($c,$o);$s=$saved($c);
check($rc===2&&$s['phase']==='ORIGINAL_RELEASED_SCHEDULER_PENDING'&&identity($c['active'])===$c['plan']['oldIdentity']&&barriers([$c['active'],$c['failed']])===[]&&str_contains($out,'SCHEDULER_RELEASE_PENDING_ORIGINAL_SITE_LIVE_NO_BARRIER="SCHEDULER_RELEASE_HOLD"')&&end($web->calls)==='/login=200','RELEASE_PENDING_NO_BARRIER');
[$again,$web2]=$coordinator($c);[$rc2,$out2]=$rerun($again);check($web2->calls===[]&&!str_contains($out2,'OWNED_BARRIER_REIMPOSED')&&!str_contains($out2,'BARRIER_HOLD')&&str_contains($out2,'RESUME_SCHEDULER_RELEASE_ONLY={"barrier":"NOT_REIMPOSED"}'),'RELEASE_PENDING_RERUN_ONLY_RELEASES');
$web->seen=array_merge($web->seen,$web2->seen);$receipts['scheduler-release-after-rollback']=$verify('scheduler-release-after-rollback',$c,$rc2,$out.$out2,$web,'ROLLED_BACK',2)+['firstAttempt'=>'ORIGINAL_RELEASED_SCHEDULER_PENDING','operatorRerun'=>'ROLLED_BACK_SCHEDULER_RELEASE_ONLY'];

// Scheduler release failure AFTER the commit point: finish-pending HOLD, no rollback, re-run closes.
$c=$build('commit-finish-pending');[$o,$web]=$coordinator($c);file_put_contents($c['root'].'/incoming/scheduler-fail-release','x');[$rc,$out]=$execute($c,$o);$s=$saved($c);
check($rc===2&&$s['phase']==='PUBLIC_RELEASED'&&str_contains($out,'HOLD_INSTALLED_OFF_FINISH_PENDING')&&identity($c['active'])===$c['plan']['candidateIdentity'],'FINISH_PENDING_NO_ROLLBACK');
[$again,$web2]=$coordinator($c);[$rc,$out2]=$rerun($again);$web2->seen=array_merge($web->seen,$web2->seen);$receipts['commit-finish-pending']=$verify('commit-finish-pending',$c,$rc,$out.$out2,$web2,'CLOSED_INSTALL',0)+['firstAttempt'=>'HOLD_INSTALLED_OFF_FINISH_PENDING','operatorRerun'=>'CLOSED_INSTALL'];

// Interrupted processes (state as a crash leaves it), resumed by an operator re-run of the real run().
$owned=static function(array &$c):void{$s=$c['state'];$new=ReleasePrimitives::ownMaintenance($c['candidate'].'/storage',$s['owner'],$s['maintenanceExpires'],$s['maintenanceStart']);$old=ReleasePrimitives::ownMaintenance($c['active'].'/storage',$s['owner'],$s['maintenanceExpires'],$s['maintenanceStart']);$c['state']['newMaintenance']=$new;$c['state']['oldMaintenance']=$old;$c['state']['schedulerPauseAttempted']=true;$c['state']['scheduler']=['explicitFixtureBoundaryDouble'=>true];};
$crash=static function(array $c,string $phase)use($coordinator):void{[$o]=$coordinator($c);$state=$c['state'];$state['git']=invoke($o,'gitSnapshot');setProperty($o,'state',$state);ob_start();invoke($o,'save',$phase);ob_end_clean();};
$staging=static fn(array $c):string=>$c['transaction'].'/probe-'.bin2hex(random_bytes(8)).'.staging';
foreach(['crash-with-probe-on-disk'=>'ROLLED_BACK','interrupted-between-renames'=>'ROLLED_BACK_PRE_SWITCH','interrupted-after-old-restore'=>'ROLLED_BACK','lost-pause-intent'=>'ROLLED_BACK_PRE_SWITCH','crash-mid-probe-write'=>'ROLLED_BACK_PRE_SWITCH','crash-after-candidate-barrier'=>'ROLLED_BACK_PRE_SWITCH','crash-before-candidate-barrier'=>'ROLLED_BACK_PRE_SWITCH'] as $case=>$terminal){
    $c=$build($case);$out1='';
    if($case==='lost-pause-intent'){
        // Pause intent recorded, pause outcome unknown; the first release fails: the original site is live WITHOUT a barrier, so the phase says so.
        $c['state']['schedulerPauseAttempted']=true;file_put_contents($c['root'].'/incoming/scheduler-fail-release','x');$crash($c,'SCHEDULER_PAUSE_INTENT');[$first,$webFirst]=$coordinator($c);[$rc1,$out1]=$rerun($first);
        check($rc1===2&&$saved($c)['phase']==='ORIGINAL_RELEASED_SCHEDULER_PENDING'&&barriers([$c['active'],$c['candidate']])===[],'LOST_PAUSE_FIRST_RELEASE_PENDING_NO_BARRIER');
    }elseif($case==='crash-mid-probe-write'){
        // Killed while writing the pre-switch probe: only a partial PRIVATE staging file exists; no public name, no barrier.
        $p=FreshnessProbe::plan($c['state']['id'],[$c['active'],$c['candidate'],$c['retained'],$c['failed']],array_keys($c['state']['probeFiles']));$path=$staging($c);file_put_contents($path,substr($p['source'],0,64));chmod($path,0644);
        $c['state']['probes'][]=['name'=>$p['name'],'sha256'=>$p['sha256'],'label'=>'preflight','staging'=>$path];$crash($c,'FRESHNESS_PROBE_INTENT_PREFLIGHT');check(probesUnder([$c['active']])===[]&&is_file($path),'CRASH_MID_WRITE_PARTIAL_PRIVATE_ONLY');
    }elseif($case==='crash-after-candidate-barrier'||$case==='crash-before-candidate-barrier'){
        // Killed right after (or right before) the candidate barrier was created: the receipts were saved BEFORE it, so nothing is orphaned.
        [$o]=$coordinator($c);$state=$c['state'];$state['git']=invoke($o,'gitSnapshot');setProperty($o,'state',$state);ob_start();invoke($o,'planBarriers');invoke($o,'save','NEW_MAINTENANCE_INTENT');ob_end_clean();$c['state']=getProperty($o,'state');
        check(is_array($c['state']['newMaintenance'])&&scandir($c['transaction'].'/barrier-model/storage/framework')===['.','..'],'PLANNED_RECEIPTS_SAVED_MODEL_CLEAN');
        if($case==='crash-after-candidate-barrier'){$made=ReleasePrimitives::ownMaintenance($c['candidate'].'/storage',$c['state']['owner'],$c['state']['maintenanceExpires'],$c['state']['maintenanceStart']);check($made['files']===$c['state']['newMaintenance']['files']&&count(barriers([$c['candidate']]))===2,'PLANNED_RECEIPT_MATCHES_REAL_BARRIER');}
    }else{
        $owned($c);
        if($case==='interrupted-between-renames'){ReleasePrimitives::journalState($c['journal'],$c['owner'],'INTENT_OLD_RENAME');rename($c['active'],$c['retained']);$crash($c,'LOG_BASELINE_RECORDED');}
        else{
            ReleasePrimitives::switchJournaled($c['active'],$c['candidate'],$c['retained'],$c['journal'],$c['owner']);file_put_contents($c['active'].'/storage/app/public/written-while-live','user-bytes');
            if($case==='crash-with-probe-on-disk'){$p=FreshnessProbe::plan($c['state']['id'],[$c['active'],$c['candidate'],$c['retained'],$c['failed']],array_keys($c['state']['probeFiles']));$path=$staging($c);FreshnessProbe::write($c['active'].'/public/'.$p['name'],$p['source'],$path);$c['state']['probes'][]=['name'=>$p['name'],'sha256'=>$p['sha256'],'label'=>'installed','staging'=>$path];$crash($c,'FRESHNESS_PROBE_INTENT_INSTALLED');check(count(probesUnder([$c['active']]))===1,'CRASH_PROBE_PRESENT');}
            else{ReleasePrimitives::recoverSwitch($c['plan'],$c['journal'],$c['owner'],$c['failed']);$crash($c,'OLD_RESTORED_MAINTENANCE_HELD');}
        }
    }
    [$o,$web]=$coordinator($c);[$rc,$out]=$rerun($o);$receipts[$case]=$verify($case,$c,$rc,$out1.$out,$web,$terminal,2);check(str_contains($out,$case==='lost-pause-intent'?'RESUME_SCHEDULER_RELEASE_ONLY':'RECOVERY_OF_EXISTING_TRANSACTION'),$case.'_RESUMED_EXISTING_TRANSACTION');
    if($case==='crash-with-probe-on-disk')check(probesUnder([$c['active'],$c['failed'],$c['candidate'],$c['retained']])===[]&&count($saved($c)['probes'])===2,'CRASH_PROBE_REMOVED_BEFORE_RESTORED_PROBE');
    if($case==='lost-pause-intent')check($actionsOf($c)===['release','release']&&$web->calls===[],'LOST_PAUSE_RERUN_ONLY_RETRIES_RELEASE');
    if(in_array($case,['crash-mid-probe-write','crash-after-candidate-barrier','crash-before-candidate-barrier'],true))check($actionsOf($c)===[]&&!in_array('/login=503',$web->calls,true),$case.'_NO_SCHEDULER_NO_BARRIER_OBSERVED');
}
echo json_encode(['cases'=>count($receipts),'externalBoundaries'=>'HTTP_STATUS_AND_SCHEDULER_TRANSPORT_EXPLICIT_DOUBLES','realComponents'=>['ownedBarrier','journaledSwitchAndRecovery','psDrain','drainPositiveControlRealTitledProcess','localSha256Verification','artisanWarmupAboutMigrateStatus','probeFileWithOpcacheTargetedInvalidation','crashSafeProbeStaging','laravelLogOffsets','monotonicHold'],'receipts'=>$receipts],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
