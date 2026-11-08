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
$dumpWrapper=$base.'/mariadb-dump-wrapper';$dumpFail=$base.'/dump-fail';$dumpTiny=$base.'/dump-tiny';
file_put_contents($dumpWrapper,'#!/bin/sh'."\n".'if [ -e '.$dumpFail.' ]; then echo injected-dump-failure >&2; exit 2; fi'."\n".'if [ -e '.$dumpTiny.' ]; then printf -- "-- tiny dump\n"; exit 0; fi'."\n".'exec docker exec -i -e MYSQL_PWD="$MYSQL_PWD" '.$container.' mariadb-dump "$@"'."\n");chmod($dumpWrapper,0755);
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

// Shims: the php shim fakes a wrong --pretend listing only while its marker exists.
$fakePretend=$base.'/fake-pretend';$shim=$base.'/php-shim';$realPhp=PHP_BINARY;
file_put_contents($shim,'#!/bin/sh'."\n".'for a in "$@"; do if [ "$a" = "--pretend" ] && [ -e '.$fakePretend.' ]; then printf "\n   INFO  Running migrations.\n\n  2026_10_23_020000_create_public_order_accesses ....\n  2026_10_23_020100_create_public_checkout_attempts ....\n  2026_10_24_030000_add_retail_customer_role ....\n  2026_11_01_000000_unexpected_fourth_migration ....\n"; exit 0; fi; done'."\n".'exec '.$realPhp.' "$@"'."\n");chmod($shim,0755);

// Real Laravel behind php -S (cli-server SAPI) with OPcache on; requests carry Host: cms.ald1n.com.
$server=proc_open([$realPhp,'-d','opcache.enable=1','-d','opcache.enable_cli=1','-d','opcache.validate_timestamps=1','-d','opcache.revalidate_freq=2','-d','opcache.file_update_protection=0','-S','127.0.0.1:'.PORT,'-t',$active.'/public',$active.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],[0=>['pipe','r'],1=>['file',$base.'/server.log','a'],2=>['file',$base.'/server.log','a']],$pipes,$active.'/public',['PATH'=>getenv('PATH'),'HOME'=>$home]);
$http=static function(string $path):int{$c=curl_init('http://127.0.0.1:'.PORT.$path);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>30,CURLOPT_HTTPHEADER=>['Host: cms.ald1n.com']]);curl_exec($c);$s=(int)curl_getinfo($c,CURLINFO_RESPONSE_CODE);curl_close($c);return $s;};
for($i=0;$i<60&&$http('/up')!==200;$i++)usleep(500000);check($http('/up')===200&&$http('/login')===200&&$http('/katalog')===404,'REAL_LARAVEL_SERVED_CLOSED');

$private=$home.'/.ald1n-b1145-state';$secrets=$base.'/probe-secrets';$faultLog=$base.'/faults.log';$logDir=$retained.'/storage/logs';
$reset=static function()use($restoreDb,$configPath,$clamp,$retained,$envBytes,$private,$home,$dumpFail,$dumpTiny,$fakePretend,$secrets,$faultLog,$logDir,$http,$active,$reportPath):void{
    chmod($retained,0755);chmod(dirname($configPath),0755);$restoreDb();
    if(hash_file('sha256',$configPath)!==hash('sha256',$clamp)){file_put_contents($configPath,$clamp);chmod($configPath,0644);}
    if(hash_file('sha256',$retained.'/.env')!==hash('sha256',$envBytes)){file_put_contents($retained.'/.env',$envBytes);chmod($retained.'/.env',0640);}
    rmTree($private);foreach(glob($home.'/cms-backups/*')?:[] as $d)rmTree($d);foreach([$dumpFail,$dumpTiny,$fakePretend,$secrets,$faultLog,$active.'/storage/framework/down'] as $f)if(file_exists($f))unlink($f);
    foreach(glob($logDir.'/laravel*.log')?:[] as $f)unlink($f);sleep(3);check($http('/login')===200,'RESET_WARM_LOGIN');
};
$configJSON=$base.'/run.json';
$run=static function(array $actions=[],array $env=[])use($configJSON,$tools,$b1144,$b1134,$root,$home,$private,$shim,$stageDir,$reportPath,&$reportSHA,$b1144Base,$secrets,$faultLog,$logDir,$retained):array{
    file_put_contents($configJSON,json_encode(['tools'=>$tools,'b1144'=>$b1144,'b1134'=>$b1134,'root'=>$root,'home'=>$home,'base'=>$private,'pointer'=>$private.'/current.json','php'=>$shim,'allowedSapis'=>['cli-server'],'stage'=>$stageDir,'reportPath'=>$reportPath,'reportSHA'=>$reportSHA,'b1144Base'=>$b1144Base,
        'appStateScript'=>$tools.'/AppState.php','censusSeconds'=>20,'waitMargin'=>3,'minFreeBytes'=>1073741824,'backupWindow'=>null,'probeSamples'=>2,'port'=>PORT,'secrets'=>$secrets,'faultLog'=>$faultLog,'actions'=>$actions,'dsn'=>'mysql:host=127.0.0.1;port=3306;dbname='.DB,'dbUser'=>'root','dbPassword'=>DB_PASSWORD,'logDir'=>$logDir,'retained'=>$retained],JSON_UNESCAPED_SLASHES));
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

// 2. Backup failure and verify failure: HOLD, nothing else changed, persistent across runs, then converge.
foreach(['BACKUP_FAIL'=>$dumpFail,'VERIFY_FAIL'=>$dumpTiny] as $case=>$marker){
    $reset();touch($marker);
    foreach([1,2] as $n){$out=$expect($case.'_'.$n,$run(),2,'HOLD_BACKUP_FAILED');$clamped($case);check($count('SELECT COUNT(*) FROM migrations')===68,$case.'_MIGRATED');}
    check(str_contains($out,$case==='BACKUP_FAIL'?'HOLD_CODE_DETAIL="BACKUP_CREATE_FAILED"':'HOLD_CODE_DETAIL="BACKUP_VERIFY_FAILED"'),$case.'_CODE');
    unlink($marker);$expect($case.'_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped($case);$results[strtolower($case)]='HOLD_TWICE_THEN_CONVERGED';
}

// 3. --pretend lists a wrong file set (php shim): HOLD before migrate; then converge.
$reset();touch($fakePretend);$out=$expect('PRETEND_WRONG_SET',$run(),2,'HOLD_PRETEND_UNEXPECTED');$clamped('PRETEND');check($count('SELECT COUNT(*) FROM migrations')===68&&str_contains($out,'PRETEND_LISTS_UNEXPECTED_MIGRATIONS'),'PRETEND_NOT_MIGRATED');
unlink($fakePretend);$expect('PRETEND_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('PRETEND');$results['pretendWrongSet']='HOLD_THEN_CONVERGED';

// 4. A fourth pending migration (row missing): pure preflight HOLD, twice, nothing changed.
$reset();$pdo()->exec("DELETE FROM migrations WHERE migration='2026_07_21_000003_create_system_tables'");
foreach([1,2] as $n){$out=$expect('FOURTH_PENDING_'.$n,$run(),2,'HOLD_PREFLIGHT_BLOCKED');$pre=lastLine($out,'PREFLIGHT');check($pre['gates']['migrateStatus']==='MIGRATION_SET_NOT_EXPECTED_FOR_FRESH'&&$pre['applicationFiles']==='NONE'&&$pre['database']==='NONE','FOURTH_PENDING_GATE');$clamped('FOURTH');check($count('SELECT COUNT(*) FROM backup_runs')===0,'FOURTH_NO_BACKUP');}
$results['fourthPending']='PREFLIGHT_HOLD_TWICE_NO_MUTATION';

// 5. Migration failure in the 2nd migration (partial table) and in the 3rd (legacy role missing): no automatic rollback, clamp kept, converge after the fix.
$reset();$pdo()->exec('CREATE TABLE public_checkout_attempts (id BIGINT UNSIGNED NOT NULL PRIMARY KEY)');
foreach([1,2] as $n){$out=$expect('MIGRATION2_'.$n,$run(),2,'HOLD_MIGRATION_FAILED');$clamped('MIGRATION2');check($count('SELECT COUNT(*) FROM migrations')===69,'MIGRATION2_RAN_69');$m=lastLine($out,'MIGRATE');check($m['pending']===array_slice(MigrateUnclampCoordinator::PENDING,1),'MIGRATION2_PENDING');check(lastLine($out,'MIGRATION_RECOVERY_NOTE')['automaticRollback']===false,'MIGRATION2_NOTE');}
$pdo()->exec('DROP TABLE public_checkout_attempts');$expect('MIGRATION2_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('MIGRATION2');
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
    $expect('CRASH_'.$point.'_CONVERGE',$run(),0,'CLOSED_UNCLAMPED');$unclamped('CRASH_'.$point);check($count('SELECT COUNT(*) FROM backup_runs')<=2,'CRASH_'.$point.'_BACKUPS');$crashes[$point]='CONVERGED';
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
