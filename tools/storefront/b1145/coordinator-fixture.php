<?php
declare(strict_types=1);
/**
 * The real B1145 coordinator over the real staged 8531-file runtime in the production topology (active tree with .env and
 * storage symlinks into a retained tree), real MariaDB 10.11.19 at the 68-migration schema, real artisan backup, verify,
 * pretend, migrate and config:show, and real Laravel served by `php -S` with OPcache (validate_timestamps=1,
 * revalidate_freq=2). Every coordinator run is a separate child process (fixture-run.php). Explicit doubles: plain loopback
 * HTTP instead of HTTPS, a mariadb-dump wrapper (docker exec) with failure markers, and a php shim for a wrong --pretend set.
 * Usage: php coordinator-fixture.php <b1145> <b1144> <b1134> <runtime stage> <payload dir> <runtime-directories.json>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$tools,$b1144,$b1134,$stage,$payload,$directoryDoc]=$argv;
require $b1134.'/ReleasePrimitives.php';require $b1134.'/InstallFileOps.php';foreach(['LeanGates','FreshnessProbe'] as $c)require $b1144.'/'.$c.'.php';foreach(['EnvFlags','UnclampProbe','MigrateUnclampCoordinator'] as $c)require $tools.'/'.$c.'.php';
const DB='ald1n_b1145_ci';const DB_PASSWORD='isolated-ci-only';const PORT=8145;
function check(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('B1145_FIXTURE_'.$name);}
function sh(array $args,?string $cwd=null,?string $stdin=null,?string $stdout=null):string{$spec=[0=>$stdin===null?['pipe','r']:['file',$stdin,'r'],1=>$stdout===null?['pipe','w']:['file',$stdout,'w'],2=>['pipe','w']];$p=proc_open($args,$spec,$pipes,$cwd);if($stdin===null)fclose($pipes[0]);$out=$stdout===null?stream_get_contents($pipes[1]):'';if($stdout===null)fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0)throw new RuntimeException('B1145_FIXTURE_COMMAND_'.basename($args[0]).'_RC'.$rc.':'.substr($err,-600));return trim((string)$out);}
function lastLine(string $output,string $key):mixed{return preg_match_all('/^'.preg_quote($key,'/').'=(.+)$/m',$output,$m)?json_decode(end($m[1]),true,64,JSON_THROW_ON_ERROR):null;}
function rmTree(string $path):void{if(is_link($path)||is_file($path)){unlink($path);return;}if(!is_dir($path))return;foreach(scandir($path) as $n)if($n!=='.'&&$n!=='..')rmTree($path.'/'.$n);rmdir($path);}

$container=getenv('B1145_MARIADB_CONTAINER');check(is_string($container)&&preg_match('/^[a-f0-9]{12,64}$/D',$container)===1,'CONTAINER_ID');
$pdo=static fn():PDO=>new PDO('mysql:host=127.0.0.1;port=3306;dbname='.DB,'root',DB_PASSWORD,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$version=(string)$pdo()->query('SELECT VERSION()')->fetchColumn();check(preg_match('/^10\.11\.19-MariaDB/',$version)===1,'MARIADB_10_11_19');
$manifest=json_decode(file_get_contents($payload.'/runtime-manifest.json'),true,512,JSON_THROW_ON_ERROR);$directories=json_decode(file_get_contents($directoryDoc),true,512,JSON_THROW_ON_ERROR)['directories'];check(count($manifest['files'])===8531,'MANIFEST_8531');

// Production topology: active tree from the pinned runtime; .env and storage are symlinks into the retained tree.
$base=getenv('RUNNER_TEMP').'/b1145';mkdir($base);$base=realpath($base);$home=$base.'/home';$root=$home.'/ald1n-project';foreach([$root.'/incoming',$root.'/apps/cms',$root.'/docs/operations',$home.'/cms-backups'] as $d)mkdir($d,0755,true);
$runtime=$home.'/.ald1n-b1134-retained/'.str_repeat('d',32);mkdir($runtime,0755,true);$retained=$runtime.'/retained';$active=$root.'/apps/cms/current';$candidate=$runtime.'/candidate';$old=$runtime.'/old';
InstallFileOps::candidate($stage,$candidate,$manifest,$directories);if(!is_dir($candidate.'/bootstrap/cache'))mkdir($candidate.'/bootstrap/cache',0755,true);
foreach(['storage/app/public','storage/logs','storage/framework/sessions','storage/framework/cache/data','storage/framework/views'] as $p)mkdir($old.'/'.$p,0755,true);
$dumpWrapper=$base.'/mariadb-dump-wrapper';$dumpFail=$base.'/dump-fail';$dumpTiny=$base.'/dump-tiny';$dumpSlow=$base.'/dump-slow';
file_put_contents($dumpWrapper,'#!/bin/sh'."\n".'if [ -e '.$dumpFail.' ]; then echo injected-dump-failure >&2; exit 2; fi'."\n".'if [ -e '.$dumpTiny.' ]; then printf -- "-- tiny dump\n"; exit 0; fi'."\n".'if [ -e '.$dumpSlow.' ]; then sleep 417; exit 0; fi'."\n".'exec docker exec -i -e MYSQL_PWD="$MYSQL_PWD" '.$container.' mariadb-dump "$@"'."\n");chmod($dumpWrapper,0755);
$appKey='base64:'.base64_encode(random_bytes(32));
$envBytes=implode("\n",['APP_NAME=Ald1n','APP_ENV=production','APP_DEBUG=false','APP_KEY='.$appKey,'APP_URL=https://cms.ald1n.com','APP_MAINTENANCE_DRIVER=file','DB_CONNECTION=mysql','DB_HOST=127.0.0.1','DB_PORT=3306','DB_DATABASE='.DB,'DB_USERNAME=root','DB_PASSWORD='.DB_PASSWORD,
    'SESSION_DRIVER=file','CACHE_STORE=file','QUEUE_CONNECTION=sync','MAIL_MAILER=array','LOG_CHANNEL=daily','LOG_LEVEL=debug','TURNSTILE_ENABLED=false','PULSE_ENABLED=false','TELESCOPE_ENABLED=false','MOBILE_PUSH_ENABLED=false','OPERATIONAL_EMAIL_NOTIFICATIONS=false',
    'LEGACY_DB_DATABASE=disabled','LEGACY_DB_USERNAME=disabled_qa_legacy','BACKUP_PATH='.$home.'/cms-backups','BACKUP_MYSQLDUMP_BINARY='.$dumpWrapper,'PUBLIC_CHECKOUT_ENABLED=false','FIXTURE_PRIVATE_MARKER=secret-never-output','']);
file_put_contents($old.'/.env',$envBytes);chmod($old.'/.env',0640);
if(!is_dir($old.'/public'))mkdir($old.'/public',0755,true);check(copy($candidate.'/public/.htaccess',$old.'/public/.htaccess')&&chmod($old.'/public/.htaccess',0644),'OLD_TREE_HTACCESS');
$preserved=ReleasePrimitives::preservePrivate($old,$candidate,$retained);rename($old,$retained);rename($candidate,$active);
check(is_link($active.'/.env')&&readlink($active.'/.env')===$retained.'/.env'&&is_link($active.'/storage/logs'),'TOPOLOGY_SYMLINKS');
$configPath=$active.'/config/public_storefront.php';$original=file_get_contents($configPath);check(hash('sha256',$original)===MigrateUnclampCoordinator::ORIGINAL_SHA,'RUNTIME_ORIGINAL_0CFBC864');
$clamp=ReleasePrimitives::offConfig($original,MigrateUnclampCoordinator::ORIGINAL_SHA);file_put_contents($configPath,$clamp);chmod($configPath,0644);
$stageDir=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU';mkdir($stageDir.'/verification',0755,true);mkdir($stageDir.'/runtime/config',0755,true);copy($payload.'/runtime-manifest.json',$stageDir.'/verification/runtime-manifest.json');file_put_contents($stageDir.'/runtime/config/public_storefront.php',$original);
$htaccess=hash_file('sha256',$active.'/public/.htaccess');check(!isset($preserved['customHtaccessSHA256'])||$preserved['customHtaccessSHA256']===$htaccess,'HTACCESS_DIGEST');
sh(['git','init','--quiet','-b','main'],$root);sh(['git','-c','user.name=Fixture','-c','user.email=fixture@example.invalid','commit','--allow-empty','-m','fixture','--quiet'],$root);$head=sh(['git','rev-parse','HEAD'],$root);

// B1144 CLOSED_INSTALL state and a Report1106-shaped terminal report bound to it (the real 1106 bytes are checked by the unit fixture).
$b1144Base=$home.'/.ald1n-b1144-state';mkdir($b1144Base,0700);$b1144Id=bin2hex(random_bytes(16));$b1144Private=$b1144Base.'/'.$b1144Id;mkdir($b1144Private,0700);
$plan=['active'=>$active,'candidate'=>$candidate,'rollback'=>$retained,'failed'=>$runtime.'/failed','oldIdentity'=>['dev'=>1,'ino'=>2],'candidateIdentity'=>['dev'=>1,'ino'=>3]];$journal=$b1144Private.'/journal.jsonl';$owner=ReleasePrimitives::createJournal($journal,$plan);ReleasePrimitives::journalState($journal,$owner,'CLOSED');
file_put_contents($b1144Private.'/state.json',json_encode(['schemaVersion'=>1,'id'=>$b1144Id,'private'=>$b1144Private,'phase'=>'CLOSED_INSTALL','plan'=>$plan,'journal'=>$journal,'owner'=>$owner,'git'=>['head'=>$head,'branch'=>'main'],'clampedConfigSHA256'=>hash('sha256',$clamp),'preserved'=>$preserved+['customHtaccessSHA256'=>$htaccess],'envSHA256'=>hash('sha256',$envBytes)],JSON_UNESCAPED_SLASHES));
file_put_contents($b1144Base.'/current.json',json_encode(['schemaVersion'=>1,'private'=>$b1144Private,'id'=>$b1144Id],JSON_UNESCAPED_SLASHES));
$reportPath=$root.'/'.MigrateUnclampCoordinator::REPORT;
file_put_contents($reportPath,implode("\n",['B1144 fixture report','HELPER_SOURCE_SHA='.MigrateUnclampCoordinator::B1144_HELPER,'HOST_GIT='.json_encode(['head'=>$head,'branch'=>'main','index'=>$root.'/.git/index','indexSHA256'=>str_repeat('0',64)],JSON_UNESCAPED_SLASHES),
    'MIGRATE_STATUS_READ_ONLY='.json_encode(['rc'=>0,'ran'=>68,'pending'=>MigrateUnclampCoordinator::PENDING,'migrateExecuted'=>false]),'RELEASE_PHASE="CLOSED_INSTALL"','PERMANENT_PRIVATE_DATA_DEPENDENCY_DO_NOT_PRUNE='.json_encode($retained,JSON_UNESCAPED_SLASHES),'B1144_FINAL=PASS_INSTALLED_OFF','EXIT=0','']));
$reportSHA=hash_file('sha256',$reportPath);

// Database at the 68-migration production schema with production roles (user/Korisnik, no customer), then a baseline dump.
$artisan=static fn(array $a):string=>sh(array_merge(['php','-d','opcache.enable=0',$active.'/artisan'],$a,['--no-interaction','--no-ansi']),$active);
$mig68=$base.'/mig68';mkdir($mig68);foreach(glob($active.'/database/migrations/*.php') as $f)if(!in_array(basename($f,'.php'),MigrateUnclampCoordinator::PENDING,true))copy($f,$mig68.'/'.basename($f));check(count(glob($mig68.'/*.php'))===68,'SIXTY_EIGHT_MIGRATIONS');
$artisan(['migrate','--path='.$mig68,'--realpath','--force']);$artisan(['db:seed','--class=Database\Seeders\CoreAccessSeeder','--force']);
$pdo()->exec("DELETE FROM roles WHERE slug='customer'");$pdo()->exec("UPDATE roles SET name='Korisnik' WHERE slug='user'");
$baseline=$base.'/baseline.sql';sh(['docker','exec','-e','MYSQL_PWD='.DB_PASSWORD,$container,'mariadb-dump','-uroot','--single-transaction','--routines','--triggers',DB],null,null,$baseline);check(filesize($baseline)>10240,'BASELINE_DUMP');
$restoreDb=static function()use($container,$baseline):void{sh(['docker','exec','-e','MYSQL_PWD='.DB_PASSWORD,$container,'mariadb','-uroot','-e','DROP DATABASE IF EXISTS '.DB.'; CREATE DATABASE '.DB]);sh(['docker','exec','-i','-e','MYSQL_PWD='.DB_PASSWORD,$container,'mariadb','-uroot',DB],null,$baseline);};
$count=static fn(string $sql):int=>(int)$pdo()->query($sql)->fetchColumn();

// Evidence: the REAL migrate --pretend at the 68-migration schema (raw output stays in CI, only the verdict is reported).
$p=proc_open(['php','-d','opcache.enable=0',$active.'/artisan','migrate','--pretend','--force','--no-interaction','--no-ansi'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$active);fclose($pipes[0]);$pOut=stream_get_contents($pipes[1]);$pErr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$pRC=proc_close($p);
$pretendEvidence=['rc'=>$pRC,'listed'=>MigrateUnclampCoordinator::pretendNames($pOut),'knownIntrospectionCodeSeen'=>str_contains($pOut.$pErr,MigrateUnclampCoordinator::PRETEND_INTROSPECTION_CODE),'verdict'=>MigrateUnclampCoordinator::pretendVerdict($pRC,$pOut,$pErr,MigrateUnclampCoordinator::PENDING,true)];
echo 'REAL_PRETEND_AT_68='.json_encode($pretendEvidence,JSON_UNESCAPED_SLASHES).PHP_EOL;check($pretendEvidence['verdict']['ok']===true,'REAL_PRETEND_VERDICT');check($count('SELECT COUNT(*) FROM migrations')===68,'PRETEND_CHANGED_NOTHING');
// The same REAL evidence for a resume at 69 and at 70 ran: each stops at its own first pending migration with its own code.
$realPretend=static function()use($active):array{$p=proc_open(['php','-d','opcache.enable=0',$active.'/artisan','migrate','--pretend','--force','--no-interaction','--no-ansi'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$active);fclose($pipes[0]);$o=stream_get_contents($pipes[1]);$e=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return [proc_close($p),$o,$e];};
foreach([1,2] as $done){
    $one=$base.'/mig-step-'.$done;mkdir($one);copy($active.'/database/migrations/'.MigrateUnclampCoordinator::PENDING[$done-1].'.php',$one.'/'.MigrateUnclampCoordinator::PENDING[$done-1].'.php');$artisan(['migrate','--path='.$one,'--realpath','--force']);check($count('SELECT COUNT(*) FROM migrations')===68+$done,'STEP_MIGRATED_'.$done);
    [$rc,$o,$e]=$realPretend();$rest=array_slice(MigrateUnclampCoordinator::PENDING,$done);$verdict=MigrateUnclampCoordinator::pretendVerdict($rc,$o,$e,$rest,false);
    $pretendEvidence['resumeAt'.(68+$done)]=['rc'=>$rc,'listed'=>MigrateUnclampCoordinator::pretendNames($o),'verdict'=>$verdict];check($verdict['ok']===true&&$verdict['knownIntrospectionCode']===MigrateUnclampCoordinator::PRETEND_KNOWN_STOPS[$rest[0]],'REAL_PRETEND_RESUME_'.(68+$done));
    check(MigrateUnclampCoordinator::pretendVerdict($rc,$o,$e,MigrateUnclampCoordinator::PENDING,false)['ok']===false,'REAL_PRETEND_RESUME_WRONG_SET_'.(68+$done));check($count('SELECT COUNT(*) FROM migrations')===68+$done,'RESUME_PRETEND_CHANGED_NOTHING_'.$done);
}
echo 'REAL_PRETEND_RESUME='.json_encode(['at69'=>$pretendEvidence['resumeAt69'],'at70'=>$pretendEvidence['resumeAt70']],JSON_UNESCAPED_SLASHES).PHP_EOL;
$restoreDb();check($count('SELECT COUNT(*) FROM migrations')===68,'BASELINE_BACK_AT_68');

// Shims: the php shim fakes a wrong --pretend listing, or hangs migrate --force, only while its marker exists.
$fakePretend=$base.'/fake-pretend';$slowMigrate=$base.'/slow-migrate';$shim=$base.'/php-shim';$realPhp=PHP_BINARY;
file_put_contents($shim,'#!/bin/sh'."\n".'m=0; f=0; p=0'."\n".'for a in "$@"; do case "$a" in migrate) m=1;; --force) f=1;; --pretend) p=1;; esac; done'."\n".'if [ $m = 1 ] && [ $f = 1 ] && [ $p = 0 ] && [ -e '.$slowMigrate.' ]; then sleep 419; exit 1; fi'."\n".'for a in "$@"; do if [ "$a" = "--pretend" ] && [ -e '.$fakePretend.' ]; then printf "\n   INFO  Running migrations.\n\n  2026_10_23_020000_create_public_order_accesses ....\n  2026_10_23_020100_create_public_checkout_attempts ....\n  2026_10_24_030000_add_retail_customer_role ....\n  2026_11_01_000000_unexpected_fourth_migration ....\n"; exit 0; fi; done'."\n".'exec '.$realPhp.' "$@"'."\n");chmod($shim,0755);
// A quota double for an unprivileged account with a tiny remaining cPanel quota (only on PATH for its own case).
$quotaDir=$base.'/quota-shim';mkdir($quotaDir);file_put_contents($quotaDir.'/quota','#!/bin/sh'."\n".'printf "Disk quotas for user runner (uid 1001): \n     Filesystem  blocks   quota   limit   grace   files   quota   limit   grace\n      /dev/root 5000000 5100000 5100000       0    1000       0       0       0\n"'."\n");chmod($quotaDir.'/quota',0755);

// Real Laravel behind php -S (cli-server SAPI) with OPcache on; requests carry Host: cms.ald1n.com.
$server=proc_open([$realPhp,'-d','opcache.enable=1','-d','opcache.enable_cli=1','-d','opcache.validate_timestamps=1','-d','opcache.revalidate_freq=2','-d','opcache.file_update_protection=0','-S','127.0.0.1:'.PORT,'-t',$active.'/public',$active.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],[0=>['pipe','r'],1=>['file',$base.'/server.log','a'],2=>['file',$base.'/server.log','a']],$pipes,$active.'/public',['PATH'=>getenv('PATH'),'HOME'=>$home]);
$http=static function(string $path):int{$c=curl_init('http://127.0.0.1:'.PORT.$path);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>['Host: cms.ald1n.com']]);curl_exec($c);$s=(int)curl_getinfo($c,CURLINFO_RESPONSE_CODE);curl_close($c);return $s;};
for($i=0;$i<60&&$http('/up')!==200;$i++)usleep(500000);check($http('/up')===200&&$http('/login')===200&&$http('/katalog')===404,'REAL_LARAVEL_SERVED_CLOSED');

$private=$home.'/.ald1n-b1145-state';$secrets=$base.'/probe-secrets';$faultLog=$base.'/faults.log';$logDir=$retained.'/storage/logs';
// Sessions of the fixture database that run SELECT SLEEP (the busy-session double); killed on reset.
$killSleepers=static function()use($pdo):void{foreach($pdo()->query("SELECT ID FROM information_schema.PROCESSLIST WHERE INFO LIKE 'SELECT SLEEP(%' AND ID<>CONNECTION_ID()")->fetchAll(PDO::FETCH_COLUMN) as $id)$pdo()->exec('KILL '.(int)$id);};
$leftoverSleeps=static fn():int=>(int)trim((string)shell_exec("ps -eo args= | grep -cE '^sleep (417|419)$' || true"));
$reset=static function()use($restoreDb,$configPath,$clamp,$retained,$envBytes,$private,$home,$dumpFail,$dumpTiny,$dumpSlow,$fakePretend,$slowMigrate,$secrets,$faultLog,$logDir,$http,$active,$reportPath,$killSleepers):void{
    chmod($retained,0755);chmod(dirname($configPath),0755);$killSleepers();$restoreDb();
    if(hash_file('sha256',$configPath)!==hash('sha256',$clamp)){file_put_contents($configPath,$clamp);chmod($configPath,0644);}
    if(hash_file('sha256',$retained.'/.env')!==hash('sha256',$envBytes)){file_put_contents($retained.'/.env',$envBytes);chmod($retained.'/.env',0640);}
    rmTree($private);foreach(glob($home.'/cms-backups/*')?:[] as $d)rmTree($d);foreach(array_merge([$dumpFail,$dumpTiny,$dumpSlow,$fakePretend,$slowMigrate,$secrets,$faultLog,$active.'/storage/framework/down'],glob($faultLog.'.*')?:[]) as $f)if(file_exists($f))unlink($f);
    foreach(glob($logDir.'/laravel*.log')?:[] as $f)unlink($f);sleep(3);check($http('/login')===200,'RESET_WARM_LOGIN');
};
$configJSON=$base.'/run.json';
// A fixed, safe app-local clock (Wednesday 14:00 Europe/Belgrade) keeps the scheduled-backup gate ON in every case.
$safeNow=strtotime('2026-11-11 13:00:00 UTC');
$run=static function(array $actions=[],array $env=[],array $overrides=[])use($configJSON,$tools,$b1144,$b1134,$root,$home,$private,$shim,$stageDir,$reportPath,&$reportSHA,$b1144Base,$secrets,$faultLog,$logDir,$retained,$safeNow):array{
    file_put_contents($configJSON,json_encode($overrides+['tools'=>$tools,'b1144'=>$b1144,'b1134'=>$b1134,'root'=>$root,'home'=>$home,'base'=>$private,'pointer'=>$private.'/current.json','php'=>$shim,'allowedSapis'=>['cli-server'],'stage'=>$stageDir,'reportPath'=>$reportPath,'reportSHA'=>$reportSHA,'b1144Base'=>$b1144Base,
        'appStateScript'=>$tools.'/AppState.php','censusSeconds'=>20,'waitMargin'=>3,'minFreeBytes'=>1073741824,'scheduleGate'=>true,'fixedNow'=>$safeNow,'backupTimeout'=>2400,'verifyTimeout'=>2400,'migrateTimeout'=>900,'backupMaxAgeSeconds'=>600,'probeSamples'=>2,'port'=>PORT,'secrets'=>$secrets,'faultLog'=>$faultLog,'actions'=>$actions,'dsn'=>'mysql:host=127.0.0.1;port=3306;dbname='.DB,'dbUser'=>'root','dbPassword'=>DB_PASSWORD,'logDir'=>$logDir,'retained'=>$retained],JSON_UNESCAPED_SLASHES));
    $p=proc_open(array_merge(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$tools.'/fixture-run.php',$configJSON]),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,array_merge(getenv(),$env));fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$rc=proc_close($p);
    return [$rc,$out.($err!==''?"\nSTDERR_BYTES=".strlen($err)."\n":'')];
};
$phase=static function()use($private):?string{$p=$private.'/current.json';if(!is_file($p))return null;$c=json_decode(file_get_contents($p),true);return json_decode(file_get_contents($c['private'].'/state.json'),true)['phase'];};
$secretFree=static function(string $case,string $out)use($secrets,$appKey):void{
    foreach(['isolated-ci-only','secret-never-output',substr($appKey,7),'disabled_qa_legacy'] as $s)check(!str_contains($out,$s),$case.'_SECRET_PRINTED');
    foreach(is_file($secrets)?array_filter(explode("\n",file_get_contents($secrets))):[] as $s)check(!str_contains($out,$s),$case.'_PROBE_NAME_OR_TOKEN_PRINTED');
};
$clamped=static function(string $case)use($configPath,$clamp,$retained,$envBytes,$active):void{
    check(hash_file('sha256',$configPath)===hash('sha256',$clamp),$case.'_CONFIG_NOT_CLAMP');check(file_get_contents($retained.'/.env')===$envBytes&&(fileperms($retained.'/.env')&0777)===0640,$case.'_ENV_CHANGED');
    check(is_link($active.'/.env')&&readlink($active.'/.env')===$retained.'/.env',$case.'_SYMLINK');check(glob($active.'/public/ald1n-b1144-*')===[],$case.'_PROBE_LEFTOVER');
};
$linkIno=lstat($active.'/.env')['ino'];
$unclamped=static function(string $case)use($configPath,$retained,$envBytes,$active,$linkIno,$count,$http):void{
    check(hash_file('sha256',$configPath)===MigrateUnclampCoordinator::ORIGINAL_SHA&&(fileperms($configPath)&0777)===0644,$case.'_CONFIG_NOT_ORIGINAL');
    $now=file_get_contents($retained.'/.env');check(EnvFlags::state($now)===array_fill_keys(EnvFlags::KEYS,'true')&&(fileperms($retained.'/.env')&0777)===0640,$case.'_ENV_NOT_UNCLAMPED');EnvFlags::assertOnlyKeysDiffer($envBytes,$now);
    check(str_contains($now,"PUBLIC_CHECKOUT_ENABLED=true\nFIXTURE_PRIVATE_MARKER")&&str_ends_with($now,"PUBLIC_STOREFRONT_ENABLED=true\n"),$case.'_ENV_EDIT_SHAPE');
    check(is_link($active.'/.env')&&readlink($active.'/.env')===$retained.'/.env'&&lstat($active.'/.env')['ino']===$linkIno,$case.'_SYMLINK_NOT_PRESERVED');
    check($count('SELECT COUNT(*) FROM migrations')===71&&$count("SELECT COUNT(*) FROM roles WHERE slug='customer'")===1&&$count("SELECT COUNT(*) FROM roles WHERE slug='user' AND name='SubAgent'")===1,$case.'_SCHEMA');
    check($count("SELECT COUNT(*) FROM settings WHERE setting_key='public_storefront_open'")===0,$case.'_DB_SWITCH_WRITTEN');check(glob($active.'/public/ald1n-b1144-*')===[]&&glob(dirname($configPath).'/.*.b1145-*')===[]&&glob($retained.'/.*.b1145-*')===[],$case.'_LEFTOVERS');
    check($http('/katalog')===404&&$http('/login')===200,$case.'_STOREFRONT_NOT_CLOSED');
};
$results=[];$expect=static function(string $case,array $r,int $rc,?string $want)use($phase,$secretFree):string{[$got,$out]=$r;$secretFree($case,$out);$ph=$phase();if($got!==$rc||$ph!==$want){fwrite(STDERR,$out);}check($got===$rc,$case.'_RC_'.$got);check($ph===$want,$case.'_PHASE_'.var_export($ph,true));return $out;};

// 1. Success, then an idempotent re-run that only re-verifies.
$reset();$out=$expect('SUCCESS',$run(),0,'CLOSED_UNCLAMPED');$unclamped('SUCCESS');
$pre=lastLine($out,'PREFLIGHT');check($pre['blockers']===[]&&$pre['stage']==='unclamp','SUCCESS_PREFLIGHT');check(str_contains($out,'B1145_PHASE="BACKUP_VERIFIED"')&&lastLine($out,'MIGRATE_PRETEND')['ok']===true,'SUCCESS_BACKUP_PRETEND');
check(lastLine($out,'CONFIG_SHOW_PUBLIC_STOREFRONT')===['enabled'=>true,'checkout_enabled'=>true],'SUCCESS_CONFIG_SHOW');$backups=$count('SELECT COUNT(*) FROM backup_runs');check($backups===1,'SUCCESS_ONE_BACKUP');
$probe=lastLine($out,'WEB_CONFIG_PROBE');check($probe['label']==='unclamped'&&$probe['samples'][0]['invalidated']===true&&$probe['failures']===[]&&$probe['globalReset']===false,'SUCCESS_EXACT_INVALIDATION');
$out=$expect('SUCCESS_RERUN',$run(),0,'CLOSED_UNCLAMPED');check(str_contains($out,'ALREADY_UNCLAMPED_NO_REPEAT=true')&&$count('SELECT COUNT(*) FROM backup_runs')===1&&$count('SELECT COUNT(*) FROM migrations')===71,'RERUN_NO_REPEAT');
$results['success']=['phase'=>'CLOSED_UNCLAMPED','rerun'=>'ALREADY_UNCLAMPED_NO_REPEAT','pretend'=>lastLine($out,'B1145_RESULT')];

// 2. Backup failures. Every one is a HOLD with nothing else changed. The app's own retention (exactly 2 restore-ready
// completed backups, verified inside app:backup-create) shapes which HOLD code a failed verification produces.
$completed=static fn():int=>$count("SELECT COUNT(*) FROM backup_runs WHERE status='completed'");
$held=static function(string $case,array $r,string $code)use($expect,$clamped,$count):void{$out=$expect($case,$r,2,'HOLD_BACKUP_FAILED');$clamped($case);check($count('SELECT COUNT(*) FROM migrations')===68,$case.'_MIGRATED');check(str_contains($out,'HOLD_CODE_DETAIL="'.$code.'"'),$case.'_CODE_'.$code);};
// 2a. The dump itself fails: BACKUP_CREATE_FAILED on every run, then converge.
$reset();touch($dumpFail);foreach([1,2] as $n)$held('BACKUP_FAIL_'.$n,$run(),'BACKUP_CREATE_FAILED');check($completed()===0,'BACKUP_FAIL_NO_COMPLETED');
unlink($dumpFail);$expect('BACKUP_FAIL_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('BACKUP_FAIL');$results['backupFail']='HOLD_TWICE_THEN_CONVERGED';
// 2b. No earlier backups and the new dump is unverifiable: VERIFY_FAILED, and the tool records exactly which backup of its
// own failed. That row stays "completed": the next app:backup-create would mark the NEW good backup failed (retention finds
// fewer than two restore-ready backups) and so would every nightly one. The preflight detects it and HOLDs before any
// create (persistent: twice), naming the row and printing the owner command; the documented recovery then converges.
$ownerCommands=static fn(string $out):array=>preg_match_all('/^OWNER_COMMAND: (.+)$/m',$out,$m)?$m[1]:[];
$runOwner=static function(array $commands,string $case):void{check($commands!==[],$case.'_NO_OWNER_COMMAND');foreach($commands as $c){check(str_starts_with($c,'cd ')&&str_contains($c,' && php artisan tinker --execute="'),$case.'_OWNER_COMMAND_SHAPE');sh(['bash','-c',$c]);}};
$reset();touch($dumpTiny);$out=$run();$held('VERIFY_FAIL_EMPTY_1',$out,'BACKUP_VERIFY_FAILED');check(count($ownerCommands($out[1]))===1,'VERIFY_FAIL_EMPTY_1_OWNER_COMMAND');check($completed()===1,'VERIFY_FAIL_EMPTY_ROW');
$badId=(int)$pdo()->query("SELECT id FROM backup_runs WHERE status='completed'")->fetchColumn();$note=lastLine($out[1],'RECOVERY_NOTE');check($note['kind']==='BACKUP_VERIFY_FAILED'&&$note['id']===$badId&&$note['createdByThisTool']===true,'VERIFY_FAIL_EMPTY_NOTE_NAMES_ROW');
foreach([2,3] as $n){
    $out=$expect('VERIFY_FAIL_EMPTY_'.$n,$run(),2,'HOLD_MIGRATE_PREFLIGHT_BLOCKED');$pre=lastLine($out,'PREFLIGHT');$clamped('VERIFY_FAIL_EMPTY_'.$n);
    check($pre['gates']['backupRetentionHealthy']==='BACKUP_RETENTION_WOULD_REJECT_NEW_BACKUP'&&$pre['values']['backupRetention']['failing'][0]['id']===$badId&&$pre['values']['backupRetention']['failing'][0]['createdByThisTool']===true,'VERIFY_FAIL_EMPTY_RETENTION_GATE');
    check($count('SELECT COUNT(*) FROM backup_runs')===1&&$completed()===1&&$count('SELECT COUNT(*) FROM migrations')===68,'VERIFY_FAIL_EMPTY_NO_NEW_CREATE');check(lastLine($out,'RECOVERY_NOTE')['kind']==='BACKUP_RETENTION','VERIFY_FAIL_EMPTY_RETENTION_NOTE');
    $commands=$ownerCommands($out);check(count($commands)===1&&str_contains($commands[0],"where('id',".$badId.")"),'VERIFY_FAIL_EMPTY_OWNER_COMMAND_NAMES_ROW');
}
unlink($dumpTiny);$runOwner($commands,'VERIFY_FAIL_EMPTY');check($pdo()->query('SELECT status FROM backup_runs WHERE id='.$badId)->fetchColumn()==='failed','VERIFY_FAIL_EMPTY_ROW_MARKED_FAILED');
$out=$expect('VERIFY_FAIL_EMPTY_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('VERIFY_FAIL_EMPTY');check($count("SELECT COUNT(*) FROM backup_runs WHERE status='failed'")===1&&$completed()===1,'VERIFY_FAIL_EMPTY_NEW_BACKUP_GOOD');
$results['verifyFailNoEarlierBackups']='VERIFY_FAILED_ROW_RECORDED_THEN_RETENTION_HOLD_TWICE_NO_CREATE_THEN_OWNER_MARKED_FAILED_AND_CONVERGED';
// 2c. Production-like: two restore-ready daily backups exist. The app's retention prunes the unverifiable new backup
// itself, so its run row is gone: BACKUP_RUN_NOT_COMPLETED. Both daily backups survive; then converge.
$reset();$artisan(['app:backup-create','--type=daily']);$artisan(['app:backup-create','--type=daily']);check($completed()===2,'VERIFY_FAIL_DAILIES');touch($dumpTiny);
foreach([1,2] as $n){$held('VERIFY_FAIL_PROD_'.$n,$run(),'BACKUP_RUN_NOT_COMPLETED');check($completed()===2&&$count("SELECT COUNT(*) FROM backup_runs WHERE status='completed' AND backup_type='daily'")===2,'VERIFY_FAIL_PROD_DAILIES_KEPT');}
unlink($dumpTiny);$expect('VERIFY_FAIL_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('VERIFY_FAIL');
check($completed()===2&&$count("SELECT COUNT(*) FROM backup_runs WHERE status='completed' AND backup_type='manual'")===1,'VERIFY_FAIL_CONVERGE_RETENTION');$results['verifyFailWithDailies']='PRUNED_BY_APP_HOLD_TWICE_THEN_CONVERGED';
// 2d. The dump hangs past the backup timeout: the whole child tree is stopped and waited for, HOLD_BACKUP_FAILED is saved
// with the running-row explanation; the app's row stays "running", so the next run HOLDs in preflight with the same
// explanation and the owner command; marking the stale row failed converges.
$running=static fn():int=>$count("SELECT COUNT(*) FROM backup_runs WHERE status='running'");
$reset();touch($dumpSlow);$out=$expect('BACKUP_TIMEOUT_1',$run([],[],['backupTimeout'=>8]),2,'HOLD_BACKUP_FAILED');$clamped('BACKUP_TIMEOUT');
$stop=lastLine($out,'CHILD_STOPPED');$note=lastLine($out,'RECOVERY_NOTE');
check(str_contains($out,'HOLD_CODE_DETAIL="BACKUP_CHILD_TERMINATED"')&&$stop['reason']==='CHILD_TIMEOUT'&&$stop['childExited']===true&&$stop['survivingDescendants']===0&&$leftoverSleeps()===0,'BACKUP_TIMEOUT_CHILD_STOPPED_AND_WAITED');
check($note['kind']==='BACKUP_CHILD_TERMINATED'&&count($note['rowsSinceIntent']['running'])===1&&str_contains($note['runningRowExplanation'],'6_HOURS')&&$running()===1&&$count('SELECT COUNT(*) FROM migrations')===68,'BACKUP_TIMEOUT_RUNNING_ROW_EXPLAINED');
foreach([2,3] as $n){$out=$expect('BACKUP_TIMEOUT_'.$n,$run([],[],['backupTimeout'=>8]),2,'HOLD_MIGRATE_PREFLIGHT_BLOCKED');$pre=lastLine($out,'PREFLIGHT');check($pre['gates']['noRunningBackup']==='BACKUP_RUN_IN_PROGRESS_OR_TABLE_MISSING'&&isset($pre['values']['runningBackups'][0]['gateClearsAt'])&&lastLine($out,'RECOVERY_NOTE')['kind']==='RUNNING_BACKUP_ROW','BACKUP_TIMEOUT_PREFLIGHT_EXPLAINS');$clamped('BACKUP_TIMEOUT_'.$n);$commands=$ownerCommands($out);}
unlink($dumpSlow);$runOwner($commands,'BACKUP_TIMEOUT');check($running()===0,'BACKUP_TIMEOUT_ROW_MARKED');$expect('BACKUP_TIMEOUT_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('BACKUP_TIMEOUT');
$results['backupChildTimeout']='TREE_STOPPED_AND_WAITED_HOLD_RUNNING_ROW_EXPLAINED_PREFLIGHT_HOLD_TWICE_OWNER_MARKED_THEN_CONVERGED';
// 2e. H1: the backup of THIS run is re-checked immediately before migrate. A prune of it (row and directory, as the nightly
// retention does) forces a fresh retake in the same run; a persistent prune is a HOLD with migrate never run; an aged
// backup (age bound) is retaken too. A backup is never reused across runs.
$reset();$out=$expect('RECHECK_PRUNED_ONCE',$run(['BEFORE_BACKUP_RECHECK'=>'pruneBackupOnce']),0,'CLOSED_UNCLAMPED');$unclamped('RECHECK_PRUNED_ONCE');
$r=lastLine($out,'BACKUP_RECHECK_FAILED');check($r['reason']==='ROW_MISSING'&&$r['action']==='RETAKE_A_FRESH_BACKUP'&&lastLine($out,'BACKUP_RECHECKED_BEFORE_MIGRATE')['reverified']===true&&substr_count($out,'B1145_PHASE="BACKUP_VERIFIED"')===2&&$count('SELECT COUNT(*) FROM backup_runs')===1,'RECHECK_PRUNED_ONCE_RETAKEN');
$reset();foreach([1,2] as $n){$out=$expect('RECHECK_PRUNED_'.$n,$run(['BEFORE_BACKUP_RECHECK'=>'pruneBackup']),2,'HOLD_BACKUP_RECHECK_FAILED');$clamped('RECHECK_PRUNED_'.$n);check(str_contains($out,'HOLD_CODE_DETAIL="BACKUP_RECHECK_FAILED_BEFORE_MIGRATE"')&&!str_contains($out,'B1145_PHASE="MIGRATE_INTENT"')&&$count('SELECT COUNT(*) FROM migrations')===68,'RECHECK_PRUNED_NO_MIGRATE');}
$expect('RECHECK_PRUNED_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('RECHECK_PRUNED');
$reset();$out=$expect('RECHECK_AGED',$run(['BEFORE_BACKUP_RECHECK'=>'ageBackupOnce']),0,'CLOSED_UNCLAMPED');$unclamped('RECHECK_AGED');check(str_starts_with(lastLine($out,'BACKUP_RECHECK_FAILED')['reason'],'OLDER_THAN_')&&substr_count($out,'B1145_PHASE="BACKUP_VERIFIED"')===2,'RECHECK_AGED_RETAKEN');
$results['backupRecheck']=['prunedOnce'=>'RETAKEN_IN_THE_SAME_RUN','prunedPersistent'=>'HOLD_TWICE_NO_MIGRATE_THEN_CONVERGED','aged'=>'RETAKEN_IN_THE_SAME_RUN'];
// 2f. M2: a busy database session immediately before migrate --force (persistent until killed): HOLD, migrate never run.
$reset();sh(['docker','exec','-d','-e','MYSQL_PWD='.DB_PASSWORD,$container,'mariadb','-uroot',DB,'-e','SELECT SLEEP(900)']);
for($i=0;$i<40&&$count("SELECT COUNT(*) FROM information_schema.PROCESSLIST WHERE INFO LIKE 'SELECT SLEEP(%'")===0;$i++)usleep(250000);check($count("SELECT COUNT(*) FROM information_schema.PROCESSLIST WHERE INFO LIKE 'SELECT SLEEP(%'")===1,'SLEEPER_STARTED');
foreach([1,2] as $n){$out=$expect('LOCK_CENSUS_'.$n,$run(),2,'HOLD_MIGRATE_CENSUS_BLOCKED');$clamped('LOCK_CENSUS_'.$n);check(str_contains($out,'HOLD_CODE_DETAIL="DB_SESSIONS_ACTIVE_BEFORE_MIGRATE"')&&lastLine($out,'PRE_MIGRATE_CENSUS')['clean']===false&&$count('SELECT COUNT(*) FROM migrations')===68,'LOCK_CENSUS_HOLD');}
$killSleepers();$out=$expect('LOCK_CENSUS_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('LOCK_CENSUS');check(lastLine($out,'PRE_MIGRATE_CENSUS')['clean']===true&&lastLine($out,'PRE_MIGRATE_CENSUS')['databaseSessions']['innodbTrxReadable']===true,'LOCK_CENSUS_CLEAN');
$results['preMigrateLockCensus']='HOLD_TWICE_THEN_CONVERGED';
// 2g. M2: migrate --force hangs past its timeout (php shim): stopped and waited for, HOLD_MIGRATION_FAILED saved with the
// recovery note; persistent twice, then converge.
$reset();touch($slowMigrate);
foreach([1,2] as $n){$out=$expect('MIGRATE_TIMEOUT_'.$n,$run([],[],['migrateTimeout'=>8]),2,'HOLD_MIGRATION_FAILED');$clamped('MIGRATE_TIMEOUT_'.$n);$m=lastLine($out,'MIGRATION_RECOVERY_NOTE');
    check(str_contains($out,'HOLD_CODE_DETAIL="MIGRATE_CHILD_TERMINATED_NO_AUTOMATIC_ROLLBACK"')&&$m['childTerminated']['reason']==='CHILD_TIMEOUT'&&$m['childTerminated']['childExited']===true&&$m['automaticRollback']===false&&is_int($m['firstPreMigrationBackupId'])&&$leftoverSleeps()===0&&$count('SELECT COUNT(*) FROM migrations')===68,'MIGRATE_TIMEOUT_NOTE');}
unlink($slowMigrate);$expect('MIGRATE_TIMEOUT_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('MIGRATE_TIMEOUT');$results['migrateChildTimeout']='STOPPED_AND_WAITED_HOLD_TWICE_THEN_CONVERGED';
// 2h. M3: the CLI default timezone is UTC; 01:20 UTC on a Tuesday is 02:20 in Europe/Belgrade, ten minutes before the
// app's 02:30 daily backup (the old CLI-local 02:00-03:40 window would have passed it). Persistent: HOLD twice, no backup.
$reset();$tuesday=strtotime('2026-11-10 01:20:00 UTC');
foreach([1,2] as $n){$out=$expect('SCHEDULE_UTC_CLI_'.$n,$run([],[],['fixedNow'=>$tuesday]),2,'HOLD_PREFLIGHT_BLOCKED');$pre=lastLine($out,'PREFLIGHT');$s=$pre['values']['schedule'];
    check($pre['gates']['outsideScheduledBackupWindow']==='SCHEDULED_BACKUP_OVERLAP_DAILY'&&$s['appTimezone']==='Europe/Belgrade'&&$s['cliDefaultTimezoneIgnored']==='UTC'&&str_contains($s['appLocalTime'],'02:20')&&$count('SELECT COUNT(*) FROM backup_runs')===0,'SCHEDULE_UTC_CLI_GATE');
    $exprs=array_column($s['events'],'expression','type');check(($exprs['daily']??null)==='30 2 * * *'&&($exprs['weekly']??null)==='10 3 * * 0','SCHEDULE_EVENTS_FROM_THE_APP');$clamped('SCHEDULE_UTC_CLI_'.$n);}
check(date('H:i',$tuesday)==='01:20','SCHEDULE_OLD_WINDOW_WOULD_HAVE_PASSED');$expect('SCHEDULE_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('SCHEDULE');$results['scheduleAppTimezone']='UTC_CLI_0120_IS_0220_BELGRADE_HOLD_TWICE_THEN_CONVERGED';
// 2i. M4: the account quota (quota double on PATH) leaves less than one backup needs: HOLD before any change, twice.
$reset();foreach([1,2] as $n){$out=$expect('QUOTA_'.$n,$run([],['PATH'=>$quotaDir.':'.getenv('PATH')]),2,'HOLD_PREFLIGHT_BLOCKED');$pre=lastLine($out,'PREFLIGHT');check($pre['gates']['accountQuotaHeadroom']==='ACCOUNT_QUOTA_HEADROOM_INSUFFICIENT'&&$count('SELECT COUNT(*) FROM backup_runs')===0,'QUOTA_GATE');$clamped('QUOTA_'.$n);}
$out=$expect('QUOTA_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('QUOTA');$q=lastLine($out,'PREFLIGHT')['values']['accountQuota'];check(is_array($q)&&array_key_exists('read',$q),'QUOTA_REPORTED');
$results['accountQuota']=['tinyQuota'=>'HOLD_TWICE_NO_MUTATION','runnerQuota'=>$q];

// 2j. H2: a Storefront table with every column but no unique key, index or foreign key (what a lock timeout between the
// Schema::create statements leaves). Fresh: preflight HOLD twice with the drop command for the EMPTY table; it converges.
$constraintsIn=static fn(string $table):array=>array_map('strval',$pdo()->query("SELECT CONCAT(INDEX_NAME,':',NON_UNIQUE,':',GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='".$table."' GROUP BY INDEX_NAME,NON_UNIQUE ORDER BY INDEX_NAME")->fetchAll(PDO::FETCH_COLUMN));
$reset();$pdo()->exec('CREATE TABLE public_order_accesses (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, order_id BIGINT UNSIGNED NOT NULL, token_hash CHAR(64) NOT NULL, email_ciphertext TEXT NOT NULL, expires_at DATETIME NOT NULL, revoked_at DATETIME NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)');
foreach([1,2] as $n){$out=$expect('TABLE_NO_CONSTRAINTS_'.$n,$run(),2,'HOLD_PREFLIGHT_BLOCKED');$pre=lastLine($out,'PREFLIGHT');$t=$pre['values']['schemaConstraints']['public_order_accesses'];
    check($pre['gates']['schemaConstraints']==='PUBLIC_TABLE_COLUMNS_WITHOUT_CONSTRAINTS'&&$t['columnsComplete']===true&&$t['rows']===0&&array_diff(['UNIQUE(order_id)','UNIQUE(token_hash)','INDEX(expires_at,revoked_at)','FOREIGN(order_id->orders.id_ON_DELETE_RESTRICT)'],$t['missing'])===[]&&!in_array('PRIMARY(id)',$t['missing'],true),'TABLE_NO_CONSTRAINTS_GATE');
    $commands=$ownerCommands($out);check(count($commands)===1&&str_contains($commands[0],"Schema::drop('public_order_accesses')")&&$count('SELECT COUNT(*) FROM backup_runs')===0&&$count('SELECT COUNT(*) FROM migrations')===68,'TABLE_NO_CONSTRAINTS_NO_MUTATION');$clamped('TABLE_NO_CONSTRAINTS_'.$n);}
$runOwner($commands,'TABLE_NO_CONSTRAINTS');$out=$expect('TABLE_NO_CONSTRAINTS_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('TABLE_NO_CONSTRAINTS');
check(lastLine($out,'SCHEMA_AFTER_MIGRATIONS')['gaps']===[]&&count($constraintsIn('public_order_accesses'))>=4&&$count("SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME IN ('public_order_accesses','public_checkout_attempts') AND DELETE_RULE='RESTRICT'")===2,'TABLE_NO_CONSTRAINTS_RECREATED_WITH_CONSTRAINTS');
// Resume: migration 1 ran, migration 2 failed on a partial table; the owner leaves a full-column table without constraints.
$reset();$pdo()->exec('CREATE TABLE public_checkout_attempts (id BIGINT UNSIGNED NOT NULL PRIMARY KEY)');$expect('RESUME_NO_CONSTRAINTS_0',$run(),2,'HOLD_MIGRATION_FAILED');check($count('SELECT COUNT(*) FROM migrations')===69,'RESUME_NO_CONSTRAINTS_69');
$pdo()->exec('DROP TABLE public_checkout_attempts');$pdo()->exec('CREATE TABLE public_checkout_attempts (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, key_hash CHAR(64) NOT NULL, request_fingerprint CHAR(64) NOT NULL, receipt_token_ciphertext TEXT NULL, terms_url VARCHAR(2048) NULL, privacy_url VARCHAR(2048) NULL, shipping_description VARCHAR(500) NULL, shipping_fee_rsd DECIMAL(12,2) NULL, merchant_policy_digest CHAR(64) NULL, delivery_method VARCHAR(32) NULL, customer_user_id BIGINT UNSIGNED NULL, payment_due_at_snapshot DATE NULL, terms_accepted_at DATETIME NULL, order_id BIGINT UNSIGNED NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)');
foreach([1,2] as $n){$out=$expect('RESUME_NO_CONSTRAINTS_'.$n,$run(),2,'HOLD_MIGRATE_PREFLIGHT_BLOCKED');$pre=lastLine($out,'PREFLIGHT');$t=$pre['values']['schemaConstraints']['public_checkout_attempts'];
    check($pre['gates']['schemaConstraints']==='PUBLIC_TABLE_COLUMNS_WITHOUT_CONSTRAINTS'&&array_diff(['UNIQUE(key_hash)','UNIQUE(order_id)','FOREIGN(order_id->orders.id_ON_DELETE_RESTRICT)'],$t['missing'])===[]&&$pre['values']['schemaConstraints']['public_order_accesses']['missing']===[],'RESUME_NO_CONSTRAINTS_GATE');
    $commands=$ownerCommands($out);check(count($commands)===1&&str_contains($commands[0],"Schema::drop('public_checkout_attempts')")&&$count('SELECT COUNT(*) FROM migrations')===69,'RESUME_NO_CONSTRAINTS_NO_MUTATION');$clamped('RESUME_NO_CONSTRAINTS_'.$n);}
$runOwner($commands,'RESUME_NO_CONSTRAINTS');$expect('RESUME_NO_CONSTRAINTS_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('RESUME_NO_CONSTRAINTS');
// After the terminal PASS a dropped unique key is a re-verify HOLD with a developer note and no drop command.
$pdo()->exec('ALTER TABLE public_order_accesses DROP INDEX public_order_accesses_token_hash_unique');
foreach([1,2] as $n){$out=$expect('CLOSED_NO_CONSTRAINT_'.$n,$run(),2,'CLOSED_UNCLAMPED');$note=lastLine($out,'RECOVERY_NOTE');check(str_contains($out,'HOLD_CLOSED_UNCLAMPED_REVERIFY=')&&lastLine($out,'PREFLIGHT')['gates']['schemaAfterMigrations']==='PUBLIC_TABLE_COLUMNS_WITHOUT_CONSTRAINTS'&&$note['kind']==='TABLE_WITHOUT_CONSTRAINTS'&&$note['migrationPending']===false&&$ownerCommands($out)===[],'CLOSED_NO_CONSTRAINT_HOLD');}
$pdo()->exec('ALTER TABLE public_order_accesses ADD UNIQUE public_order_accesses_token_hash_unique (token_hash)');$expect('CLOSED_NO_CONSTRAINT_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');
$results['tableWithoutConstraints']=['fresh'=>'HOLD_TWICE_OWNER_DROPPED_EMPTY_TABLE_THEN_CONVERGED_WITH_EVERY_CONSTRAINT','resume'=>'HOLD_TWICE_AT_69_OWNER_DROPPED_THEN_CONVERGED','closed'=>'REVERIFY_HOLD_TWICE_DEVELOPER_NOTE_THEN_CONVERGED'];

// 3. --pretend lists a wrong file set (php shim): HOLD before migrate; then converge.
$reset();touch($fakePretend);$out=$expect('PRETEND_WRONG_SET',$run(),2,'HOLD_PRETEND_UNEXPECTED');$clamped('PRETEND');check($count('SELECT COUNT(*) FROM migrations')===68&&str_contains($out,'PRETEND_LISTS_UNEXPECTED_MIGRATIONS'),'PRETEND_NOT_MIGRATED');
unlink($fakePretend);$expect('PRETEND_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('PRETEND');$results['pretendWrongSet']='HOLD_THEN_CONVERGED';

// 4. A fourth pending migration (row missing): pure preflight HOLD, twice, nothing changed.
$reset();$pdo()->exec("DELETE FROM migrations WHERE migration='2026_07_21_000003_create_system_tables'");
foreach([1,2] as $n){$out=$expect('FOURTH_PENDING_'.$n,$run(),2,'HOLD_PREFLIGHT_BLOCKED');$pre=lastLine($out,'PREFLIGHT');check($pre['gates']['migrateStatus']==='MIGRATION_SET_NOT_EXPECTED_FOR_FRESH'&&$pre['applicationFiles']==='NONE'&&$pre['database']==='NONE','FOURTH_PENDING_GATE');$clamped('FOURTH');check($count('SELECT COUNT(*) FROM backup_runs')===0,'FOURTH_NO_BACKUP');}
$results['fourthPending']='PREFLIGHT_HOLD_TWICE_NO_MUTATION';

// 5. Migration failure in the 2nd migration (partial table) and in the 3rd (legacy role missing): no automatic rollback, clamp kept, converge after the fix.
$reset();$pdo()->exec('CREATE TABLE public_checkout_attempts (id BIGINT UNSIGNED NOT NULL PRIMARY KEY)');
$firstId=null;
foreach([1,2] as $n){$out=$expect('MIGRATION2_'.$n,$run(),2,'HOLD_MIGRATION_FAILED');$clamped('MIGRATION2');check($count('SELECT COUNT(*) FROM migrations')===69,'MIGRATION2_RAN_69');$m=lastLine($out,'MIGRATE');check($m['pending']===array_slice(MigrateUnclampCoordinator::PENDING,1),'MIGRATION2_PENDING');check(lastLine($out,'MIGRATION_RECOVERY_NOTE')['automaticRollback']===false,'MIGRATION2_NOTE');
    // L1: the FIRST pre-migration backup (68 ran) stays in state and in every recovery note; later retries never overwrite it.
    $firstId??=lastLine($out,'BACKUP')['id'];check(lastLine($out,'MIGRATION_RECOVERY_NOTE')['firstPreMigrationBackupId']===$firstId&&lastLine($out,'RECOVERY_NOTE')['firstPreMigrationBackup']['id']===$firstId,'MIGRATION2_FIRST_BACKUP_KEPT_'.$n);
    if($n===2)check(lastLine($out,'BACKUP')['id']!==$firstId,'MIGRATION2_RETRY_TOOK_A_NEW_BACKUP');}
$pdo()->exec('DROP TABLE public_checkout_attempts');$out=$expect('MIGRATION2_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('MIGRATION2');
$warn=lastLine($out,'WARN_FIRST_PRE_MIGRATION_BACKUP');$first=lastLine($out,'FIRST_PRE_MIGRATION_BACKUP');
check($warn['id']===$firstId&&str_contains($warn['status'],'REMOVES_IT')&&$first['id']===$firstId&&$first['stillOnServer']===false&&lastLine($out,'B1145_RESULT')['firstPreMigrationBackupId']===$firstId,'MIGRATION2_FIRST_BACKUP_RETENTION_WARNED');
$results['firstPreMigrationBackup']=['keptAcrossRetries'=>true,'retentionWarned'=>true,'reportedRemovedAfterKeepTwo'=>true];
$reset();$pdo()->exec("UPDATE roles SET slug='legacy-user' WHERE slug='user'");$out=$expect('MIGRATION3',$run(),2,'HOLD_MIGRATION_FAILED');$clamped('MIGRATION3');check($count('SELECT COUNT(*) FROM migrations')===70,'MIGRATION3_RAN_70');
$pdo()->exec("UPDATE roles SET slug='user' WHERE slug='legacy-user'");$out=$expect('MIGRATION3_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('MIGRATION3');check($count('SELECT COUNT(*) FROM backup_runs')===2,'MIGRATION3_NEW_BACKUP_BEFORE_RETRY');
$results['migrationFailure']=['second'=>'HOLD_TWICE_RAN_69_THEN_CONVERGED','third'=>'HOLD_RAN_70_NEW_BACKUP_THEN_CONVERGED'];

// 6. Config swap failure (config directory unwritable) and .env write failure (retained directory unwritable after the config rename): automatic restore.
$reset();chmod(dirname($configPath),0555);$out=$expect('CONFIG_SWAP_FAIL',$run(),2,'RESTORED_CLAMPED');chmod(dirname($configPath),0755);$clamped('CONFIG_SWAP');check(str_contains($out,'UNCLAMP_OR_ACCEPTANCE_FAILED="ATOMIC_TEMP_CREATE"'),'CONFIG_SWAP_CODE');
$before=$count('SELECT COUNT(*) FROM backup_runs');$expect('CONFIG_SWAP_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('CONFIG_SWAP');check($count('SELECT COUNT(*) FROM backup_runs')===$before,'CONFIG_SWAP_NO_NEW_BACKUP');
$reset();$out=$expect('ENV_WRITE_FAIL',$run(['BEFORE_ENV_RENAME'=>'chmodRetained']),2,'RESTORED_CLAMPED');chmod($retained,0755);$clamped('ENV_WRITE');check(lastLine($out,'CONFIG_SHOW_PUBLIC_STOREFRONT')===['enabled'=>false,'checkout_enabled'=>false]&&str_contains($out,'UNCLAMP_OR_ACCEPTANCE_FAILED="ATOMIC_TEMP_CREATE"'),'ENV_WRITE_RESTORE_VERIFIED');
$expect('ENV_WRITE_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('ENV_WRITE');$results['swapFailures']=['config'=>'RESTORED_CLAMPED_THEN_CONVERGED','env'=>'RESTORED_CLAMPED_THEN_CONVERGED'];

// 7. Acceptance failures: a new ERROR log line, and the DB switch turned on mid-run (persistent until removed).
$reset();$out=$expect('ACCEPT_LOG',$run(['SAVED_ACCEPTANCE_INTENT'=>'logError']),2,'RESTORED_CLAMPED');$clamped('ACCEPT_LOG');check(str_contains($out,'NEW_LARAVEL_LOG_ERRORS_SINCE_UNCLAMP'),'ACCEPT_LOG_CODE');
$expect('ACCEPT_LOG_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('ACCEPT_LOG');
$reset();$out=$expect('ACCEPT_FLAG',$run(['SAVED_ENV_UNCLAMPED'=>'flag1']),2,'RESTORED_CLAMPED');$clamped('ACCEPT_FLAG');check(str_contains($out,'STOREFRONT_DB_SWITCH_NOT_ABSENT_OR_0')&&$http('/katalog')===404,'ACCEPT_FLAG_RESTORED_CLOSED');
foreach([1,2] as $n){$out=$expect('ACCEPT_FLAG_HOLD_'.$n,$run(),2,'HOLD_UNCLAMP_PREFLIGHT_BLOCKED');check(lastLine($out,'PREFLIGHT')['gates']['storefrontDbSwitchClosed']==='STOREFRONT_DB_SWITCH_NOT_ABSENT_OR_0','ACCEPT_FLAG_GATE');$clamped('ACCEPT_FLAG_HOLD');}
$pdo()->exec("DELETE FROM settings WHERE setting_key='public_storefront_open'");$expect('ACCEPT_FLAG_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('ACCEPT_FLAG');$results['acceptanceFailure']=['logError'=>'RESTORED_THEN_CONVERGED','dbSwitchOn'=>'RESTORED_HOLD_TWICE_THEN_CONVERGED'];

// 8. Crash (SIGKILL) right after each saved phase and before each rename; the plain re-run converges.
$crashes=[];
foreach(['SAVED_PREFLIGHT_PASSED','SAVED_BACKUP_CREATED','SAVED_BACKUP_VERIFIED','SAVED_MIGRATE_INTENT','SAVED_MIGRATED_VERIFIED','SAVED_UNCLAMP_INTENT','BEFORE_CONFIG_RENAME','SAVED_CONFIG_UNCLAMPED','BEFORE_ENV_RENAME','SAVED_ENV_UNCLAMPED','SAVED_ACCEPTANCE_INTENT','SAVED_RESTORE_INTENT','SAVED_CLOSED_UNCLAMPED'] as $point){
    $reset();$actions=[$point=>'kill'];if($point==='SAVED_RESTORE_INTENT')$actions=['SAVED_ACCEPTANCE_INTENT'=>'logError','SAVED_RESTORE_INTENT'=>'kill'];
    [$rc,$out]=$run($actions);$secretFree('CRASH_'.$point,$out);check($rc===137||$rc===-1||$rc===9,'CRASH_'.$point.'_NOT_KILLED_RC_'.$rc);check(str_contains((string)@file_get_contents($faultLog),$point.' kill'),'CRASH_'.$point.'_NOT_REACHED');
    $crashedBackup=$count('SELECT COALESCE(MAX(id),0) FROM backup_runs');
    $rerun=$expect('CRASH_'.$point.'_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('CRASH_'.$point);
    // H1 across runs: a re-run that still has to migrate takes and rechecks its OWN backup, never the crashed run's one.
    if(in_array($point,['SAVED_PREFLIGHT_PASSED','SAVED_BACKUP_CREATED','SAVED_BACKUP_VERIFIED','SAVED_MIGRATE_INTENT'],true)){$re=lastLine($rerun,'BACKUP_RECHECKED_BEFORE_MIGRATE');check(is_array($re)&&(int)($re['id']??0)>$crashedBackup&&($re['sameProcess']??null)===true,'CRASH_'.$point.'_REUSED_THE_CRASHED_RUN_BACKUP');}check($count('SELECT COUNT(*) FROM backup_runs')<=2,'CRASH_'.$point.'_BACKUPS');$crashes[$point]='CONVERGED';
}
$results['crashReentry']=$crashes;

// 9. Persistent preflight faults, collect-all, each run twice with nothing changed.
$faults=[
    'PROCESS_ENV'=>[fn()=>null,['PUBLIC_STOREFRONT_ENABLED'=>'true'],'operatorProcessEnvironment','PUBLIC_KEYS_IN_OPERATOR_PROCESS_ENVIRONMENT'],
    'MAINTENANCE'=>[fn()=>file_put_contents($active.'/storage/framework/down','{}'),[],'noMaintenanceMode','MAINTENANCE_MODE_ACTIVE'],
    'REPORT_CHANGED'=>[fn()=>file_put_contents($reportPath,"extra\n",FILE_APPEND),[],'chainB1144Report1106','B1144_REPORT1106_CHANGED'],
    'ENV_KEY_TRUE'=>[fn()=>file_put_contents($retained.'/.env',str_replace('PUBLIC_CHECKOUT_ENABLED=false','PUBLIC_CHECKOUT_ENABLED=true',$envBytes)),[],'envKeys','ENV_KEYS_NOT_ABSENT_OR_FALSE'],
];
$reportBytes=file_get_contents($reportPath);
foreach($faults as $case=>[$inject,$env,$gate,$code]){
    $reset();$inject();
    foreach([1,2] as $n){$out=$expect('PREFLIGHT_'.$case.'_'.$n,$run([],$env),2,'HOLD_PREFLIGHT_BLOCKED');$pre=lastLine($out,'PREFLIGHT');check(($pre['gates'][$gate]??null)===$code&&in_array($code,$pre['blockers'],true)&&count($pre['gates'])>=20,'PREFLIGHT_'.$case.'_GATE');check($count('SELECT COUNT(*) FROM backup_runs')===0&&$count('SELECT COUNT(*) FROM migrations')===68&&hash_file('sha256',$configPath)===hash('sha256',$clamp),'PREFLIGHT_'.$case.'_MUTATED');}
    file_put_contents($reportPath,$reportBytes);$results['preflight'][$case]='HOLD_TWICE_NO_MUTATION';
}
$reset();$expect('FINAL_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('FINAL');
proc_terminate($server);proc_close($server);
echo json_encode(['coordinatorFixture'=>'PASS','mariadb'=>$version,'realPretendAt68'=>$pretendEvidence]+$results,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
