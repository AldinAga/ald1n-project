<?php
declare(strict_types=1);
/**
 * The real B1144 coordinator over the real staged 8531-file runtime: real owned barriers, journal, switch, rollback,
 * real `ps` drain, real local SHA-256 verification, real artisan warmup / about / migrate:status (SQLite with exactly
 * 68 applied rows), the real probe file executed by PHP with OPcache, and real Laravel log offsets.
 * Explicit doubles: HTTP status responses (FixtureWeb) and the B1134 scheduler transport. Every stage after the
 * switch, including after the lift, is failure-injected and must roll back automatically.
 */
require $argv[5].'/ReleasePrimitives.php';require $argv[5].'/InstallFileOps.php';
foreach(['LeanGates','FreshnessProbe','B1144Recovery','InstallCoordinator'] as $class)require $argv[1].'/'.$class.'.php';

final class FixtureWeb
{
    public array $fail=[];public array $calls=[];public array $seen=[];public bool $logErrorOnUp=false;public bool $uploadOnLogin=false;public ?string $sapiOverride=null;
    /** realpathTtl is the REAL ini value of the probe process; realpathOverride replaces the reported raw value per tree label. */
    public string $realpathTtl='0';public array $realpathOverride=[];public array $times=[];
    public function __construct(private string $active,private array $oldIdentity,private string $harness,private array $compile){}
    private function restored():bool {clearstatcache(true);$s=@stat($this->active);return is_array($s)&&$s['dev']===$this->oldIdentity['dev']&&$s['ino']===$this->oldIdentity['ino'];}
    private function consume(string $key):bool {if(($this->fail[$key]??0)<1)return false;$this->fail[$key]--;return true;}
    public function status(string $path,int $expected,?string $stamp=null,string $marker='',int $attempts=3):void
    {
        $this->calls[]=$path.'='.$expected;$this->times[]=[$path.'='.$expected,time()];clearstatcache(true);$barrier=$this->active.'/storage/framework/maintenance.php';$body='';
        if(is_file($barrier)){$observed=503;$body=(string)file_get_contents($barrier);}else $observed=in_array($path,['/login','/up'],true)?200:404;
        if($this->consume($path.'='.$expected))$observed=$expected===200?500:200;
        if($observed===200&&$path==='/login'&&$this->uploadOnLogin){file_put_contents($this->active.'/storage/app/public/written-while-live','user-bytes');$this->uploadOnLogin=false;}
        if($observed===200&&$path==='/up'&&$this->logErrorOnUp){file_put_contents($this->active.'/storage/logs/laravel.log','['.date('Y-m-d H:i:s').'] production.ERROR: injected fixture failure after the switch'."\n",FILE_APPEND);$this->logErrorOnUp=false;}
        $pass=$observed===$expected&&($stamp===null||str_contains($body,'ALD1N owned maintenance '.$stamp));
        echo 'PUBLIC_HTTP_SMOKE='.json_encode(['path'=>$path,'expected'=>$expected,'result'=>$pass?'PASS':'FAIL','attempts'=>1,'fixtureDouble'=>true]).PHP_EOL;if(!$pass)throw new RuntimeException('PUBLIC_HTTP_SMOKE_'.$expected);
    }
    public function probe(string $name,string $token,bool $invalidate):array
    {
        $this->seen[]=$name;$this->seen[]=$token;$label=$this->restored()?'restored':'installed';$this->calls[]='probe:'.$label;
        if($this->consume('probe-'.$label))throw new RuntimeException('FRESHNESS_PROBE_HTTP_UNAVAILABLE');
        $server=['REQUEST_METHOD'=>'GET','HTTP_HOST'=>'cms.ald1n.com','HTTP_X_ALD1N_RELEASE_PROBE'=>$token,'DOCUMENT_ROOT'=>$this->active.'/public']+($invalidate?['HTTP_X_ALD1N_RELEASE_INVALIDATE'=>'YES']:[]);
        $compile=array_values(array_filter(array_map(fn($r)=>$this->active.'/'.$r,$this->compile),'is_file'));
        $p=proc_open(array_merge(['php','-d','opcache.enable=1','-d','opcache.enable_cli=1','-d','opcache.revalidate_freq=0','-d','opcache.file_update_protection=0','-d','realpath_cache_ttl='.$this->realpathTtl,$this->harness,$this->active.'/public/'.$name,json_encode($server)],$compile),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);proc_close($p);
        if($out==='')throw new RuntimeException('FRESHNESS_PROBE_HTTP_UNAVAILABLE');$data=json_decode($out,true,16,JSON_THROW_ON_ERROR);
        if($label==='installed'&&$this->sapiOverride!==null)$data['sapi']=$this->sapiOverride;if(array_key_exists($label,$this->realpathOverride))$data['realpathCacheTtl']=$this->realpathOverride[$label];$data['_route']='FIXTURE_CLI_HARNESS';return $data;
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
$schedulerDouble='<?php $c=json_decode(file_get_contents($argv[1]),true);$d=__DIR__;file_put_contents($d."/scheduler-actions.log",$c["action"]."\n",FILE_APPEND);if(is_file($d."/scheduler-fail-".$c["action"])){unlink($d."/scheduler-fail-".$c["action"]);exit(2);}echo json_encode(["status"=>"ok","receipt"=>["explicitFixtureBoundaryDouble"=>true,"action"=>$c["action"]]]);';

$build=static function(string $case,int $applied=68)use($base,$template,$manifest,$directoryDoc,$payload,$schedulerDouble,$diagnostic,$migrationNames):array{
    $home=$base.'/'.$case;mkdir($home);$root=$home.'/project';mkdir($root.'/incoming',0755,true);mkdir($root.'/apps/cms',0755,true);$active=$root.'/apps/cms/current';$runtime=$home.'/.ald1n-b1134-retained/'.str_repeat('c',32);mkdir($runtime,0755,true);
    $candidate=$runtime.'/candidate';$retained=$runtime.'/retained';$failed=$runtime.'/failed';run(['cp','-a',$template.'/old',$active]);run(['cp','-a',$template.'/new',$candidate]);
    foreach(['storage/app/public','storage/logs','storage/framework/sessions','storage/framework/cache/data','storage/framework/views'] as $p)if(!is_dir($active.'/'.$p))mkdir($active.'/'.$p,0755,true);
    $db=$home.'/production-like.sqlite';$pdo=new PDO('sqlite:'.$db);$pdo->exec('create table migrations (id integer primary key autoincrement not null, migration varchar not null, batch integer not null)');$insert=$pdo->prepare('insert into migrations (migration,batch) values (?,1)');$ran=array_values(array_diff($migrationNames,InstallCoordinator::PENDING));foreach(array_slice($ran,0,$applied) as $m)$insert->execute([$m]);$pdo=null;
    $env=implode("\n",['APP_NAME=Ald1n','APP_ENV=production','APP_DEBUG=false','APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=','APP_URL=https://cms.ald1n.com','APP_MAINTENANCE_DRIVER=file','DB_CONNECTION=sqlite','DB_DATABASE='.$db,'SESSION_DRIVER=file','CACHE_STORE=file','QUEUE_CONNECTION=sync','MAIL_MAILER=array','LOG_CHANNEL=single','LOG_LEVEL=debug','PUBLIC_STOREFRONT_ENABLED=true','PUBLIC_CHECKOUT_ENABLED=true','TURNSTILE_ENABLED=false','FIXTURE_PRIVATE_MARKER=secret-never-output','']);file_put_contents($active.'/.env',$env);chmod($active.'/.env',0600);
    file_put_contents($active.'/storage/logs/laravel.log','['.date('Y-m-d H:i:s',time()-86400).'] production.ERROR: historical error before the release must not count'."\n");file_put_contents($active.'/storage/app/public/original','original-upload');
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
    $web=$options['web']??new FixtureWeb($c['active'],$c['plan']['oldIdentity'],$tools.'/probe-harness.php',array_keys($diagnostic['probeFiles']));
    foreach(['root'=>$c['root'],'home'=>$c['home'],'pointer'=>$c['private'].'/current.json','manifest'=>$manifest,'directoryDocument'=>['directories'=>$directories],'web'=>$web,'php'=>$shim,'drainSeconds'=>$options['drain']??8,'allowedSapis'=>['cli'],'probeSamples'=>2] as $k=>$v)setProperty($o,$k,$v);
    return [$o,$web];
};
$execute=static function(array $c,InstallCoordinator $o):array{$state=$c['state'];setProperty($o,'state',$state);$state['git']=invoke($o,'gitSnapshot');setProperty($o,'state',$state);ob_start();invoke($o,'save','PREPARED');$rc=invoke($o,'execute');return [$rc,ob_get_clean()];};
$rerun=static function(InstallCoordinator $o):array{ob_start();$rc=$o->run();return [$rc,ob_get_clean()];};
$saved=static fn(array $c):array=>json_decode(file_get_contents($c['transaction'].'/state.json'),true,512,JSON_THROW_ON_ERROR);
$spawned=[];$titled=static function(array $args)use(&$spawned):void{$p=proc_open($args,[0=>['pipe','r'],1=>['file','/dev/null','w'],2=>['file','/dev/null','w']],$pipes);fclose($pipes[0]);$spawned[]=$p;usleep(700000);};
$killAll=static function()use(&$spawned):void{foreach($spawned as $p){$s=proc_get_status($p);if($s['running'])proc_terminate($p,9);proc_close($p);}$spawned=[];};

/** Common terminal assertions; returns the receipt. */
$verify=static function(string $case,array $c,int $rc,string $output,FixtureWeb $web,string $phase,int $expectedRC)use($saved,$coordinator,$rerun):array{
    $s=$saved($c);check($rc===$expectedRC,$case.'_RC_'.$rc);check($s['phase']===$phase,$case.'_PHASE_'.$s['phase']);
    foreach($web->seen as $secret)check(!str_contains($output,$secret),$case.'_PROBE_NAME_OR_TOKEN_PRINTED');check(!str_contains($output,'secret-never-output')&&!str_contains($output,$c['state']['owner']),$case.'_SECRET_PRINTED');
    check(hash_file('sha256',$c['active'].'/.env')===$c['state']['envSHA256'],$case.'_ENV_PRESERVED');
    check(probesUnder([$c['active'],$c['candidate'],$c['retained'],$c['failed']])===[]||$case==='leftover-probe',$case.'_PROBE_REMOVED');
    check(file_get_contents($c['active'].'/storage/app/public/original')==='original-upload',$case.'_PRIVATE_DATA');
    if(in_array($phase,['CLOSED_INSTALL','ROLLED_BACK','ROLLED_BACK_PRE_SWITCH'],true)){
        check(barriers([$c['active'],$c['candidate'],$c['retained'],$c['failed']])===[],$case.'_ALL_BARRIERS_RELEASED');
        [$again]=$coordinator($c);[$rc2,$out2]=$rerun($again);check($rc2===($phase==='CLOSED_INSTALL'?0:2)&&str_contains($out2,$phase==='CLOSED_INSTALL'?'ALREADY_INSTALLED_OFF_NO_REPEAT':'PRIOR_ATTEMPT_ROLLED_BACK_NO_AUTOMATIC_REDEPLOY'),$case.'_NO_AUTOMATIC_REDEPLOY');
    }
    if($phase==='ROLLED_BACK'){check(identity($c['active'])===$c['plan']['oldIdentity']&&identity($c['failed'])===$c['plan']['candidateIdentity']&&!file_exists($c['retained']),$case.'_OLD_CODE_RESTORED');check(isset($s['freshness']['restored'])&&str_contains($output,'"label":"restored"'),$case.'_RESTORED_FRESHNESS');check(lastJournal($c['journal'])==='ROLLED_BACK',$case.'_JOURNAL_ROLLED_BACK');
        // The restored code is lifted only after the probe-derived hold counted from the swap back.
        $hold=$s['freshness']['restored']['hold']??[];$lastLogin=null;foreach($web->times as [$call,$at])if($call==='/login=200')$lastLogin=$at;
        check(str_contains($output,'BARRIER_HOLD={"label":"restored"')&&is_int($hold['liftNotBefore']??null)&&$hold['liftNotBefore']===max($hold['swappedAt']+$hold['realpathCacheTtl'],$hold['probedAt'])+FreshnessProbe::HOLD_MARGIN&&$lastLogin!==null&&$lastLogin>=$hold['liftNotBefore']&&($hold['heldUntil']??0)>=$hold['liftNotBefore'],$case.'_RESTORED_LIFT_AFTER_PROBE_DERIVED_HOLD');}
    if($phase==='ROLLED_BACK_PRE_SWITCH'){check(identity($c['active'])===$c['plan']['oldIdentity']&&identity($c['candidate'])===$c['plan']['candidateIdentity']&&!file_exists($c['retained'])&&!file_exists($c['failed']),$case.'_CANDIDATE_UNTOUCHED');}
    $actions=is_file($c['root'].'/incoming/scheduler-actions.log')?array_values(array_filter(explode("\n",file_get_contents($c['root'].'/incoming/scheduler-actions.log')))):[];
    if(in_array($phase,['CLOSED_INSTALL','ROLLED_BACK'],true)||($phase==='ROLLED_BACK_PRE_SWITCH'&&$actions!==[]))check(end($actions)==='release',$case.'_SCHEDULER_RELEASED_LAST');
    preg_match_all('/^RELEASE_PHASE="([A-Z_]+)"$/m',$output,$m);
    return ['returnCode'=>$rc,'phase'=>$s['phase'],'holdReason'=>preg_match('/^INSTALL_HOLD_REASON="([A-Z0-9_]+)"$/m',$output,$h)?$h[1]:null,'phases'=>$m[1],'schedulerActions'=>$actions,'httpCalls'=>$web->calls,'probeNameOrTokenPrinted'=>false];
};

$receipts=[];
// Full success, then re-entry.
$c=$build('success');[$o,$web]=$coordinator($c);$web->uploadOnLogin=true;$web->realpathTtl='9';[$rc,$out]=$execute($c,$o);$receipts['success']=$verify('success',$c,$rc,$out,$web,'CLOSED_INSTALL',0);$s=$saved($c);
check(identity($c['active'])===$c['plan']['candidateIdentity']&&identity($c['retained'])===$c['plan']['oldIdentity'],'SUCCESS_SWITCHED');check(lastJournal($c['journal'])==='CLOSED','SUCCESS_JOURNAL_CLOSED');
check($s['cliBoot']['ran']===68&&$s['cliBoot']['pending']===InstallCoordinator::PENDING&&str_contains($out,'MIGRATE_STATUS_READ_ONLY={"rc":0,"ran":68,"pending":["2026_10_23_020000_create_public_order_accesses","2026_10_23_020100_create_public_checkout_attempts","2026_10_24_030000_add_retail_customer_role"],"migrateExecuted":false}'),'SUCCESS_MIGRATE_STATUS');
check($s['treeVerification']['verifiedFiles']===8531&&$s['logScan']['newErrorLines']===0&&$s['freshness']['installed']['samples'][0]['treeFilesVerified']===3&&$s['freshness']['installed']['samples'][0]['targetedInvalidations']>=1,'SUCCESS_GATES');
check(str_contains($out,'CANONICAL_STATIC_CHECK="NOT_RUN_NOT_AN_EXISTING_INSTALL_GATE"')&&str_contains($out,'CMS_DRAIN={"drained":true'),'SUCCESS_REPORT_LINES');
$order=$receipts['success']['phases'];foreach([['MAINTENANCE_OWNED','CMS_DRAINED'],['CMS_DRAINED','CANDIDATE_LOCAL_VERIFIED'],['LOG_BASELINE_RECORDED','SWITCHED'],['SWITCHED','FRESHNESS_VERIFIED'],['FRESHNESS_VERIFIED','CLI_BOOT_VERIFIED'],['CLI_BOOT_VERIFIED','BARRIER_LIFTED_SMOKE_PENDING'],['BARRIER_LIFTED_SMOKE_PENDING','COMMITTED_OFF']] as [$a,$b])check(array_search($a,$order,true)<array_search($b,$order,true),'SUCCESS_ORDER_'.$a.'_'.$b);
$liftIndex=array_search('/login=200',$web->calls,true);check($liftIndex!==false&&array_slice($web->calls,$liftIndex,5)===['/login=200','/up=200','/katalog=404','/registracija=404','/korpa=404'],'SUCCESS_POST_LIFT_SMOKES');check(file_get_contents($c['retained'].'/storage/app/public/written-while-live')==='user-bytes','SUCCESS_LIVE_WRITE_IN_SHARED_STORAGE');
check($receipts['success']['schedulerActions']===['pause','verify','verify','release'],'SUCCESS_SCHEDULER_SEQUENCE');
// The hold comes from the probe process's REAL realpath_cache_ttl (9, not the PHP default), counted from the swap, and the lift waits for it.
$h=$s['freshness']['installed']['hold'];$firstLogin=null;foreach($web->times as [$call,$at])if($call==='/login=200'){$firstLogin=$at;break;}
check($h['realpathCacheTtl']===9&&$s['freshness']['installed']['samples'][0]['realpathCacheTtl']===9&&$h['swappedAt']===$s['swappedAt']['installed']&&$h['liftNotBefore']===max($h['swappedAt']+9,$h['probedAt'])+FreshnessProbe::HOLD_MARGIN&&$h['heldUntil']>=$h['liftNotBefore']&&$firstLogin!==null&&$firstLogin>=$h['liftNotBefore'],'SUCCESS_HOLD_DERIVED_FROM_PROBE_INI_'.json_encode($h));
check(str_contains($out,'BARRIER_HOLD_START={"label":"installed"')&&array_search('CLI_BOOT_VERIFIED',$order,true)<array_search('BARRIER_LIFT_INTENT',$order,true)&&strpos($out,'BARRIER_HOLD={"label":"installed"')<strpos($out,'RELEASE_PHASE="BARRIER_LIFT_INTENT"'),'SUCCESS_HOLD_BEFORE_LIFT');
$receipts['success']['barrierHold']=$h;

// Pre-switch HOLDs: the candidate is never renamed.
$preSwitch=['barrier-not-observed'=>['web'=>['/login=503'=>1],'reason'=>'PUBLIC_HTTP_SMOKE_503'],'drain-cms-worker'=>['title'=>'cms','reason'=>'CMS_WORKERS_OR_ARTISAN_NOT_DRAINED'],'drain-artisan'=>['title'=>'artisan','reason'=>'CMS_WORKERS_OR_ARTISAN_NOT_DRAINED'],'candidate-tamper'=>['tamper'=>true,'reason'=>'CANDIDATE_LOCAL_SHA256_MISMATCH']];
foreach($preSwitch as $case=>$spec){
    $c=$build($case);[$o,$web]=$coordinator($c,['drain'=>3]);$web->fail=$spec['web']??[];
    if(($spec['title']??null)==='cms')$titled(['php','-n','-r','cli_set_process_title($argv[1]);sleep(120);','lsphp:'.substr($c['active'].'/public/index.php',-LeanGates::TITLE_SUFFIX_BYTES),str_repeat('p',400)]);
    if(($spec['title']??null)==='artisan'){file_put_contents($c['home'].'/artisan','<?php sleep(120);');$titled(['php','-n',$c['home'].'/artisan','queue:work']);}
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
    if($spec['leftover']??false)file_put_contents($c['candidate'].'/public/'.FreshnessProbe::PREFIX.str_repeat('e',48).'.php','<?php // leftover from an unknown earlier run');
    putenv('B1144_FIXTURE_ARTISAN_FAIL='.($spec['env']??''));[$rc,$out]=$execute($c,$o);putenv('B1144_FIXTURE_ARTISAN_FAIL');
    $receipts[$case]=$verify($case,$c,$rc,$out,$web,'ROLLED_BACK',2);check($receipts[$case]['holdReason']===$spec['reason'],$case.'_REASON_'.$receipts[$case]['holdReason']);
    $phases=$receipts[$case]['phases'];check(in_array('SWITCHED',$phases,true)&&in_array('OLD_RESTORED_MAINTENANCE_HELD',$phases,true),$case.'_SWITCHED_THEN_RESTORED');
    check(in_array('BARRIER_LIFTED_SMOKE_PENDING',$phases,true)===$spec['lifted'],$case.'_LIFT_STAGE');if($spec['lifted']){check(in_array('ROLLBACK_BARRIER_OWNED',$phases,true)&&str_contains($out,'OWNED_BARRIER_REIMPOSED'),$case.'_BARRIER_REIMPOSED_AFTER_LIFT');check(file_get_contents($c['active'].'/storage/app/public/written-while-live')==='user-bytes',$case.'_LIVE_WRITE_SURVIVES_ROLLBACK');}
    check(array_slice($receipts[$case]['httpCalls'],-1)===['/login=200'],$case.'_ORIGINAL_LOGIN_VERIFIED');
    if($spec['leftover']??false){$left=probesUnder([$c['failed']]);check(count($left)===1&&file_get_contents($left[0])==='<?php // leftover from an unknown earlier run','LEFTOVER_NOT_OVERWRITTEN_OR_DELETED');}
    if(isset($spec['realpath']))check(!in_array('CLI_BOOT_VERIFIED',$phases,true)&&!str_contains($out,'BARRIER_HOLD={"label":"installed"')&&($spec['reason']!=='BARRIER_HOLD_EXCEEDS_BOUND'||str_contains($out,'BARRIER_HOLD_BOUND={"label":"installed"')),$case.'_INSTALLED_HOLD_FAILS_CLOSED_BEFORE_LIFT');
}

// Unreadable or out-of-bound hold on the RESTORED code: the barrier stays on the old code; the operator re-run completes the rollback.
foreach(['restored-realpath-ttl-unreadable'=>[null,'FRESHNESS_REALPATH_CACHE_UNREADABLE'],'restored-hold-exceeds-bound'=>['100000','BARRIER_HOLD_EXCEEDS_BOUND']] as $case=>[$value,$code]){
    $c=$build($case);[$o,$web]=$coordinator($c);$web->fail=['/login=200'=>1];$web->uploadOnLogin=true;$web->realpathOverride=['restored'=>$value];[$rc,$out]=$execute($c,$o);$s=$saved($c);
    check($rc===2&&$s['phase']==='HOLD_RECOVERY_OWNED_MAINTENANCE_RETAINED'&&str_contains($out,'RECOVERY_HOLD="'.$code.'"')&&identity($c['active'])===$c['plan']['oldIdentity']&&is_file($c['active'].'/storage/framework/maintenance.php')&&probesUnder([$c['active'],$c['failed']])===[]&&!str_contains($out,'BARRIER_HOLD={"label":"restored"')&&!str_contains($out,'RELEASE_PHASE="ORIGINAL_RELEASE_INTENT"'),$case.'_RESTORED_HOLD_FAILS_CLOSED_KEEPS_BARRIER');
    [$again,$web2]=$coordinator($c);[$rc,$out2]=$rerun($again);$web2->seen=array_merge($web->seen,$web2->seen);$receipts[$case]=$verify($case,$c,$rc,$out.$out2,$web2,'ROLLED_BACK',2)+['firstAttempt'=>'HOLD_RECOVERY_OWNED_MAINTENANCE_RETAINED_'.$code,'operatorRerun'=>'ROLLED_BACK'];
}

// Restored-tree freshness HOLD keeps the barrier on the restored code; the operator re-run completes the rollback.
$c=$build('restored-freshness-hold');[$o,$web]=$coordinator($c);$web->fail=['/login=200'=>1,'probe-restored'=>1];[$rc,$out]=$execute($c,$o);$s=$saved($c);
check($rc===2&&$s['phase']==='HOLD_RECOVERY_OWNED_MAINTENANCE_RETAINED'&&identity($c['active'])===$c['plan']['oldIdentity']&&is_file($c['active'].'/storage/framework/maintenance.php')&&probesUnder([$c['active'],$c['failed']])===[],'RESTORED_FRESHNESS_HOLD_KEEPS_BARRIER');
[$again,$web2]=$coordinator($c);[$rc,$out2]=$rerun($again);$web2->seen=array_merge($web->seen,$web2->seen);$receipts['restored-freshness-hold']=$verify('restored-freshness-hold',$c,$rc,$out.$out2,$web2,'ROLLED_BACK',2)+['firstAttempt'=>'HOLD_RECOVERY_OWNED_MAINTENANCE_RETAINED','operatorRerun'=>'ROLLED_BACK'];

// Scheduler release failure AFTER the commit point: finish-pending HOLD, no rollback, re-run closes.
$c=$build('commit-finish-pending');[$o,$web]=$coordinator($c);file_put_contents($c['root'].'/incoming/scheduler-fail-release','x');[$rc,$out]=$execute($c,$o);$s=$saved($c);
check($rc===2&&$s['phase']==='PUBLIC_RELEASED'&&str_contains($out,'HOLD_INSTALLED_OFF_FINISH_PENDING')&&identity($c['active'])===$c['plan']['candidateIdentity'],'FINISH_PENDING_NO_ROLLBACK');
[$again,$web2]=$coordinator($c);[$rc,$out2]=$rerun($again);$web2->seen=array_merge($web->seen,$web2->seen);$receipts['commit-finish-pending']=$verify('commit-finish-pending',$c,$rc,$out.$out2,$web2,'CLOSED_INSTALL',0)+['firstAttempt'=>'HOLD_INSTALLED_OFF_FINISH_PENDING','operatorRerun'=>'CLOSED_INSTALL'];

// Interrupted processes (state as a crash leaves it), resumed by an operator re-run of the real run().
$owned=static function(array &$c):void{$s=$c['state'];$new=ReleasePrimitives::ownMaintenance($c['candidate'].'/storage',$s['owner'],$s['maintenanceExpires'],$s['maintenanceStart']);$old=ReleasePrimitives::ownMaintenance($c['active'].'/storage',$s['owner'],$s['maintenanceExpires'],$s['maintenanceStart']);$c['state']['newMaintenance']=$new;$c['state']['oldMaintenance']=$old;$c['state']['schedulerPauseAttempted']=true;$c['state']['scheduler']=['explicitFixtureBoundaryDouble'=>true];};
$crash=static function(array $c,string $phase)use($coordinator):void{[$o]=$coordinator($c);$state=$c['state'];$state['git']=invoke($o,'gitSnapshot');setProperty($o,'state',$state);ob_start();invoke($o,'save',$phase);ob_end_clean();};
foreach(['crash-with-probe-on-disk'=>'ROLLED_BACK','interrupted-between-renames'=>'ROLLED_BACK_PRE_SWITCH','interrupted-after-old-restore'=>'ROLLED_BACK','lost-pause-intent'=>'ROLLED_BACK_PRE_SWITCH'] as $case=>$terminal){
    $c=$build($case);
    if($case==='lost-pause-intent'){$c['state']['schedulerPauseAttempted']=true;file_put_contents($c['root'].'/incoming/scheduler-fail-release','x');$crash($c,'SCHEDULER_PAUSE_INTENT');[$first]=$coordinator($c);[$rc1,$out1]=$rerun($first);check($rc1===2&&$saved($c)['phase']==='HOLD_RECOVERY_OWNED_MAINTENANCE_RETAINED','LOST_PAUSE_FIRST_RELEASE_HELD');}
    else{
        $owned($c);
        if($case==='interrupted-between-renames'){ReleasePrimitives::journalState($c['journal'],$c['owner'],'INTENT_OLD_RENAME');rename($c['active'],$c['retained']);$crash($c,'LOG_BASELINE_RECORDED');}
        else{
            ReleasePrimitives::switchJournaled($c['active'],$c['candidate'],$c['retained'],$c['journal'],$c['owner']);file_put_contents($c['active'].'/storage/app/public/written-while-live','user-bytes');
            if($case==='crash-with-probe-on-disk'){$p=FreshnessProbe::plan($c['state']['id'],[$c['active'],$c['candidate'],$c['retained'],$c['failed']],array_keys($c['state']['probeFiles']));FreshnessProbe::write($c['active'].'/public/'.$p['name'],$p['source']);$c['state']['probes'][]=['name'=>$p['name'],'sha256'=>$p['sha256'],'label'=>'installed'];$crash($c,'FRESHNESS_PROBE_INTENT_INSTALLED');check(count(probesUnder([$c['active']]))===1,'CRASH_PROBE_PRESENT');}
            else{ReleasePrimitives::recoverSwitch($c['plan'],$c['journal'],$c['owner'],$c['failed']);$crash($c,'OLD_RESTORED_MAINTENANCE_HELD');}
        }
        $out1='';
    }
    [$o,$web]=$coordinator($c);[$rc,$out]=$rerun($o);$receipts[$case]=$verify($case,$c,$rc,$out1.$out,$web,$terminal,2);check(str_contains($out,'RECOVERY_OF_EXISTING_TRANSACTION'),$case.'_RESUMED_EXISTING_TRANSACTION');
    if($case==='crash-with-probe-on-disk')check(probesUnder([$c['active'],$c['failed'],$c['candidate'],$c['retained']])===[]&&count($saved($c)['probes'])===2,'CRASH_PROBE_REMOVED_BEFORE_RESTORED_PROBE');
    if($case==='lost-pause-intent')check(in_array('release',$receipts[$case]['schedulerActions'],true),'LOST_PAUSE_RELEASE_RETRIED');
}
echo json_encode(['cases'=>count($receipts),'externalBoundaries'=>'HTTP_STATUS_AND_SCHEDULER_TRANSPORT_EXPLICIT_DOUBLES','realComponents'=>['ownedBarrier','journaledSwitchAndRecovery','psDrain','localSha256Verification','artisanWarmupAboutMigrateStatus','probeFileWithOpcacheTargetedInvalidation','laravelLogOffsets'],'receipts'=>$receipts],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
