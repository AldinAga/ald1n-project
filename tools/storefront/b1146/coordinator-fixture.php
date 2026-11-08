<?php
declare(strict_types=1);
/**
 * The real B1146 coordinator over the real staged 8531-file runtime (ef3f927 = PR #152 base) in the production topology
 * (active tree with .env and storage symlinks into a retained tree), after the B1145 unclamp (staged original config, .env
 * PUBLIC_* = true, 71 migrations ran, DB switch absent), real MariaDB 10.11.19, real Laravel served by `php -S` with OPcache
 * (validate_timestamps=1, revalidate_freq=2), and the real PR #152 payload taken from the built operator ZIP.
 * Explicit doubles: plain loopback HTTP instead of HTTPS, realpath_cache_ttl=10 on the test server (shorter hold),
 * SESSION_SECURE_COOKIE=false for the authenticated superadmin checks, a fixed app-local clock outside the backup window.
 * Cases: unknown pre-state refusal, install with a smoke after every single file step (anonymous and superadmin), approval
 * reset notice, idempotent re-run, owner --rollback, forced acceptance failure with automatic rollback, SIGKILL during the
 * copy and convergence, and the same install / rollback / kill with a route cache present (exact route-cache bytes back).
 * Usage: php coordinator-fixture.php <b1146> <b1145> <b1144> <b1134> <runtime stage> <payload dir> <runtime-directories.json> <operator zip>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$tools,$b1145,$b1144,$b1134,$stage,$payloadDir,$directoryDoc,$zipPath]=$argv;
require $b1134.'/ReleasePrimitives.php';require $b1134.'/InstallFileOps.php';foreach(['LeanGates','FreshnessProbe','LeanWeb'] as $c)require $b1144.'/'.$c.'.php';require $b1145.'/EnvFlags.php';require $b1145.'/MigrateUnclampCoordinator.php';foreach(['OverlayProbe','OverlayWeb','OverlayCoordinator','FixtureWeb'] as $c)require $tools.'/'.$c.'.php';
const DB='ald1n_b1146_ci';const DB_PASSWORD='isolated-ci-only';const PORT=8146;
function check(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('B1146_FIXTURE_'.$name);}
function sh(array $args,?string $cwd=null):string{$p=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0)throw new RuntimeException('B1146_FIXTURE_COMMAND_'.basename($args[0]).'_RC'.$rc.':'.substr($err.$out,-900));return trim((string)$out);}
function lastLine(string $output,string $key):mixed{return preg_match_all('/^'.preg_quote($key,'/').'=(.+)$/m',$output,$m)?json_decode(end($m[1]),true,64,JSON_THROW_ON_ERROR):null;}
function lastRaw(string $output,string $key):?string{return preg_match_all('/^'.preg_quote($key,'/').'=(.*)$/m',$output,$m)?end($m[1]):null;}
function rmTree(string $path):void{if(is_link($path)||is_file($path)){unlink($path);return;}if(!is_dir($path))return;foreach(scandir($path) as $n)if($n!=='.'&&$n!=='..')rmTree($path.'/'.$n);rmdir($path);}

$pdo=static fn():PDO=>new PDO('mysql:host=127.0.0.1;port=3306;dbname='.DB,'root',DB_PASSWORD,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
check(preg_match('/^10\.11\.19-MariaDB/',(string)$pdo()->query('SELECT VERSION()')->fetchColumn())===1,'MARIADB_10_11_19');
$manifest=json_decode((string)file_get_contents($payloadDir.'/runtime-manifest.json'),true,512,JSON_THROW_ON_ERROR);$directories=json_decode((string)file_get_contents($directoryDoc),true,512,JSON_THROW_ON_ERROR)['directories'];check(count($manifest['files'])===8531,'MANIFEST_8531');

// Production topology: active tree from the pinned runtime; .env and storage are symlinks into the retained tree.
$base=getenv('RUNNER_TEMP').'/b1146';mkdir($base);$base=realpath($base);$home=$base.'/home';$root=$home.'/ald1n-project';foreach([$root.'/incoming',$root.'/apps/cms',$root.'/docs/operations',$home.'/cms-backups'] as $d)mkdir($d,0755,true);
$runtime=$home.'/.ald1n-b1134-retained/'.str_repeat('d',32);mkdir($runtime,0755,true);$retained=$runtime.'/retained';$active=$root.'/apps/cms/current';$candidate=$runtime.'/candidate';$old=$runtime.'/old';
InstallFileOps::candidate($stage,$candidate,$manifest,$directories);if(!is_dir($candidate.'/bootstrap/cache'))mkdir($candidate.'/bootstrap/cache',0755,true);
foreach(['storage/app/public','storage/logs','storage/framework/sessions','storage/framework/cache/data','storage/framework/views'] as $p)mkdir($old.'/'.$p,0755,true);
$envBytes=implode("\n",['APP_NAME=Ald1n','APP_ENV=production','APP_DEBUG=false','APP_KEY=base64:'.base64_encode(random_bytes(32)),'APP_URL=https://cms.ald1n.com','APP_TIMEZONE=Europe/Belgrade','APP_MAINTENANCE_DRIVER=file','DB_CONNECTION=mysql','DB_HOST=127.0.0.1','DB_PORT=3306','DB_DATABASE='.DB,'DB_USERNAME=root','DB_PASSWORD='.DB_PASSWORD,
    'SESSION_DRIVER=file','SESSION_SECURE_COOKIE=false','CACHE_STORE=file','QUEUE_CONNECTION=sync','MAIL_MAILER=array','LOG_CHANNEL=daily','LOG_LEVEL=debug','TURNSTILE_ENABLED=false','PULSE_ENABLED=false','TELESCOPE_ENABLED=false','MOBILE_PUSH_ENABLED=false','OPERATIONAL_EMAIL_NOTIFICATIONS=false',
    'LEGACY_DB_DATABASE=disabled','LEGACY_DB_USERNAME=disabled_qa_legacy','BACKUP_PATH='.$home.'/cms-backups','PUBLIC_STOREFRONT_ENABLED=true','PUBLIC_CHECKOUT_ENABLED=true','']);
file_put_contents($old.'/.env',$envBytes);chmod($old.'/.env',0640);
if(!is_dir($old.'/public'))mkdir($old.'/public',0755,true);check(copy($candidate.'/public/.htaccess',$old.'/public/.htaccess')&&chmod($old.'/public/.htaccess',0644),'OLD_TREE_HTACCESS');
$preserved=ReleasePrimitives::preservePrivate($old,$candidate,$retained);rename($old,$retained);rename($candidate,$active);
check(is_link($active.'/.env')&&is_link($active.'/storage/logs'),'TOPOLOGY_SYMLINKS');check(hash_file('sha256',$active.'/config/public_storefront.php')===OverlayCoordinator::CONFIG_SHA,'CONFIG_IS_STAGED_ORIGINAL_AFTER_B1145');
$stageDir=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU';mkdir($stageDir.'/verification',0755,true);copy($payloadDir.'/runtime-manifest.json',$stageDir.'/verification/runtime-manifest.json');
$htaccess=hash_file('sha256',$active.'/public/.htaccess');

// The B1145 terminal state and a Report1108-shaped terminal report.
$b1145Base=$home.'/.ald1n-b1145-state';mkdir($b1145Base,0700);$b1145Id=bin2hex(random_bytes(16));mkdir($b1145Base.'/'.$b1145Id,0700);
file_put_contents($b1145Base.'/'.$b1145Id.'/state.json',json_encode(['schemaVersion'=>1,'kind'=>'B1145','id'=>$b1145Id,'private'=>$b1145Base.'/'.$b1145Id,'phase'=>'CLOSED_UNCLAMPED','b1144'=>['retained'=>$retained,'htaccessSHA256'=>$htaccess]],JSON_UNESCAPED_SLASHES));
file_put_contents($b1145Base.'/current.json',json_encode(['schemaVersion'=>1,'kind'=>'B1145','private'=>$b1145Base.'/'.$b1145Id,'id'=>$b1145Id],JSON_UNESCAPED_SLASHES));
file_put_contents($root.'/docs/operations/1108-B1145-CONTROLLED-MIGRATE-UNCLAMP-20261108-150000.md',implode("\n",['B1145 fixture report','B1145_PHASE="CLOSED_UNCLAMPED"','B1145_FINAL=PASS_MIGRATED_UNCLAMPED_STOREFRONT_CLOSED','EXIT=0','']));
sh(['git','init','--quiet','-b','main'],$root);sh(['git','-c','user.name=Fixture','-c','user.email=fixture@example.invalid','commit','--allow-empty','-m','fixture','--quiet'],$root);

// Operator package exactly as the owner unzips it, plus the pinned helpers already on the host under their host names.
sh(['unzip','-q','-n',$zipPath,'-d',$root]);$incoming=$root.'/incoming';
foreach(['b1134/InstallFileOps.php'=>'b1134-files.php','b1144/LeanGates.php'=>'b1144-gates.php','b1144/FreshnessProbe.php'=>'b1144-probe.php','b1144/LeanWeb.php'=>'b1144-web.php','b1145/EnvFlags.php'=>'b1145-env.php','b1145/AppState.php'=>'b1145-app-state.php','b1145/MigrateUnclampCoordinator.php'=>'b1145-coordinator.php'] as $from=>$to)copy(dirname($tools).'/'.$from,$incoming.'/'.$to);
$package=json_decode((string)file_get_contents($incoming.'/b1146-package.json'),true,64,JSON_THROW_ON_ERROR);foreach($package['files'] as $n=>$d)check(hash_file('sha256',$incoming.'/'.$n)===$d,'PACKAGE_FILE_'.$n);$plan=OverlayCoordinator::plan($package,$incoming);check(count($plan)===21,'PLAN_21');
foreach($plan as $s){$t=$active.'/'.$s['path'];check($s['kind']==='new'?!file_exists($t):hash_file('sha256',$t)===$s['preSHA256'],'RUNTIME_PRE_STATE_'.$s['n']);}

// Database after B1145: all 71 migrations ran, production roles, DB switch absent; one superadmin; the old-code approval signed.
$artisan=static fn(array $a):string=>sh(array_merge(['php','-d','opcache.enable=0',$active.'/artisan'],$a,['--no-interaction','--no-ansi']),$active);
check(count(glob($active.'/database/migrations/*.php'))===71,'SEVENTY_ONE_MIGRATION_FILES');$artisan(['migrate','--force']);$artisan(['db:seed','--class=Database\Seeders\CoreAccessSeeder','--force']);
$pdo()->exec("UPDATE roles SET name='Korisnik' WHERE slug='user'");
$roleId=(int)$pdo()->query("SELECT id FROM roles WHERE slug='superadmin'")->fetchColumn();check($roleId>0,'SUPERADMIN_ROLE');$password='SyntheticOnly123!';
$pdo()->prepare("INSERT INTO users (role_id,username,email,first_name,password_hash,status,created_at,updated_at) VALUES (?,?,?,?,?,'active',NOW(),NOW())")->execute([$roleId,'b1146-owner','b1146-owner@example.test','Fixture',password_hash($password,PASSWORD_BCRYPT)]);
$fingerprintScript=$base.'/fingerprint.php';file_put_contents($fingerprintScript,'<?php chdir($argv[1]);require "vendor/autoload.php";$app=require "bootstrap/app.php";$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();echo $app->make(App\Services\PublicMerchantPolicyService::class)->review()["fingerprint"];');
$oldFingerprint=sh(['php','-d','opcache.enable=0',$fingerprintScript,$active]);check(preg_match('/^[a-f0-9]{64}$/D',$oldFingerprint)===1,'OLD_FINGERPRINT');
$pdo()->prepare("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('public_checkout_approval_digest',?,NOW(),NOW())")->execute([$oldFingerprint]);$artisan(['cache:clear']);
check((int)$pdo()->query("SELECT COUNT(*) FROM settings WHERE setting_key='public_storefront_open'")->fetchColumn()===0,'DB_SWITCH_ABSENT');

// Real Laravel behind php -S (cli-server SAPI) with OPcache on; requests carry Host: cms.ald1n.com.
$server=proc_open([PHP_BINARY,'-d','opcache.enable=1','-d','opcache.enable_cli=1','-d','opcache.validate_timestamps=1','-d','opcache.revalidate_freq=2','-d','opcache.file_update_protection=0','-d','realpath_cache_ttl=10','-S','127.0.0.1:'.PORT,'-t',$active.'/public',$active.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],[0=>['pipe','r'],1=>['file',$base.'/server.log','a'],2=>['file',$base.'/server.log','a']],$pipes,$active.'/public',['PATH'=>getenv('PATH'),'HOME'=>$home]);
$status=static fn(string $path,array $h=[]):int=>FixtureWeb::get(PORT,$path,$h)['status'];
for($i=0;$i<60&&$status('/up')!==200;$i++)usleep(500000);check($status('/up')===200&&$status('/login')===200&&$status('/katalog')===404&&$status('/pravne-informacije')===404,'REAL_LARAVEL_SERVED_CLOSED_OLD');

// One superadmin login through the real form (old code); the session cookie is reused at every step.
$login=FixtureWeb::get(PORT,'/login');check(preg_match('/name="_token"\s+value="([^"]+)"/',$login['body'],$m)===1,'LOGIN_CSRF');$jar=$login['cookies'];
$cookie=static fn(array $jar):string=>implode('; ',array_map(static fn($k,$v)=>$k.'='.$v,array_keys($jar),$jar));
$post=FixtureWeb::get(PORT,'/login',['Cookie: '.$cookie($jar),'Content-Type: application/x-www-form-urlencoded'],http_build_query(['_token'=>$m[1],'login'=>'b1146-owner','password'=>$password]));$jar=$post['cookies']+$jar;
check($post['status']===302&&!str_contains((string)$post['location'],'/login'),'SUPERADMIN_LOGIN');$adminCookie=$cookie($jar);check($status('/admin/settings/public-storefront',['Cookie: '.$adminCookie])===200,'SUPERADMIN_PAGE_OLD_CODE');

$private=$home.'/.ald1n-b1146-state';$secrets=$base.'/probe-secrets';$faultLog=$base.'/faults.log';$stepLog=$base.'/steps.jsonl';$logDir=$retained.'/storage/logs';$configJSON=$base.'/run.json';$safeNow=strtotime('2026-11-11 13:00:00 UTC');
$run=static function(array $actions=[],string $mode='install')use($configJSON,$tools,$b1145,$b1144,$b1134,$root,$home,$private,$stageDir,$b1145Base,$incoming,$secrets,$faultLog,$stepLog,$logDir,$adminCookie,$safeNow):array{
    file_put_contents($configJSON,json_encode(['tools'=>$tools,'b1145'=>$b1145,'b1144'=>$b1144,'b1134'=>$b1134,'root'=>$root,'home'=>$home,'base'=>$private,'pointer'=>$private.'/current.json','stage'=>$stageDir,'b1145Base'=>$b1145Base,'appStateScript'=>$incoming.'/b1145-app-state.php','appCheckScript'=>$incoming.'/b1146-app-check.php','php'=>PHP_BINARY,'allowedSapis'=>['cli-server'],
        'censusSeconds'=>20,'minFreeBytes'=>268435456,'scheduleGate'=>true,'fixedNow'=>$safeNow,'incoming'=>$incoming,'mode'=>$mode,'port'=>PORT,'secrets'=>$secrets,'faultLog'=>$faultLog,'stepLog'=>$stepLog,'logDir'=>$logDir,'adminCookie'=>$adminCookie,'actions'=>$actions],JSON_UNESCAPED_SLASHES));
    $p=proc_open(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$tools.'/fixture-run.php',$configJSON],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$rc=proc_close($p);
    echo "----- RUN mode=$mode rc=$rc -----\n".$out.($err!==''?"\nSTDERR=".substr($err,-2000)."\n":'');return [$rc,$out];
};
$tree=static function()use($plan,$active):string{$pre=true;$inst=true;foreach($plan as $s){$t=$active.'/'.$s['path'];$sha=is_file($t)?hash_file('sha256',$t):null;$pre=$pre&&$sha===$s['preSHA256'];$inst=$inst&&$sha===$s['payloadSHA256'];}return $pre?'PRE':($inst?'INSTALLED':'MIXED');};
$routeCacheSHA=static function()use($active):?string{$files=glob($active.'/bootstrap/cache/routes-*.php')?:[];sort($files);if($files===[])return null;$h=[];foreach($files as $f)$h[]=basename($f).':'.hash_file('sha256',$f);return hash('sha256',implode("\n",$h));};
$reset=static function()use($private,$secrets,$faultLog,$stepLog,$logDir,$artisan,$tree,$status):void{
    rmTree($private);foreach(array_merge([$secrets,$faultLog,$stepLog],glob($logDir.'/laravel*.log')?:[]) as $f)if(file_exists($f))unlink($f);file_put_contents($logDir.'/laravel-'.date('Y-m-d').'.log','');$artisan(['cache:clear']);
    check($tree()==='PRE','RESET_TREE_PRE');sleep(3);check($status('/login')===200,'RESET_WARM_LOGIN');
};
$allSteps=static function():array{$a=[];for($n=1;$n<=21;$n++)$a['AFTER_STEP_'.$n]=['stepSmoke'];$a['AFTER_ROUTES']=['stepSmoke'];$a['AFTER_INVALIDATE']=['stepSmoke'];return $a;};
$stepsClean=static function()use($stepLog):array{$rows=array_map(static fn($l)=>json_decode($l,true),array_filter(explode("\n",(string)@file_get_contents($stepLog))));$bad=[];
    foreach($rows as $r){foreach($r['anon'] as $p=>$s){$legal=in_array($p,OverlayCoordinator::LEGAL,true);if($s>=500||($legal&&!in_array($s,[200,404],true))||(!$legal&&in_array($p,OverlayCoordinator::CLOSED,true)&&$s!==404)||(in_array($p,OverlayCoordinator::SYSTEM,true)&&$s!==200))$bad[]=$r['point'].' '.$p.' '.$s;}foreach($r['admin'] as $p=>$s)if($s!==200)$bad[]=$r['point'].' ADMIN '.$p.' '.$s;}
    return ['points'=>count($rows),'bad'=>$bad];};
$results=[];

foreach(['noRouteCache','routeCache'] as $variant){
    $artisan(['route:clear']);if($variant==='routeCache'){$artisan(['route:cache']);check($routeCacheSHA()!==null,'ROUTE_CACHE_CREATED');}$cacheBefore=$routeCacheSHA();$artisan(['view:cache']);$reset();

    if($variant==='noRouteCache'){
        // Refusal: an unknown pre-state of one replaced file stops the run before any change.
        $web=$active.'/routes/web.php';$original=file_get_contents($web);file_put_contents($web,$original."\n// foreign local edit\n");
        [$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&lastLine($o,'B1146_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('UNKNOWN_PRE_STATE_REFUSED',$p['blockers'],true)&&!str_contains($o,'OVERLAY_STEP='),'UNKNOWN_PRE_STATE_REFUSED');
        check(file_get_contents($web)===$original."\n// foreign local edit\n"&&!is_dir($active.'/resources/views/public-shop/legal'),'UNKNOWN_PRE_STATE_NOTHING_CHANGED');file_put_contents($web,$original);$reset();$results[$variant]['unknownPreState']='HOLD_NOTHING_CHANGED';
    }

    // Install with a smoke after every single file step (anonymous and authenticated superadmin).
    [$rc,$o]=$run($allSteps());$steps=$stepsClean();echo 'STEP_SMOKES='.json_encode($steps,JSON_UNESCAPED_SLASHES).PHP_EOL;
    check($rc===0&&lastRaw($o,'B1146_FINAL')==='PASS_INSTALLED_LEGAL_OVERLAY_STOREFRONT_CLOSED'&&$tree()==='INSTALLED','INSTALL_PASS_'.$variant);check($steps['points']===23&&$steps['bad']===[],'EVERY_STEP_SERVES_'.$variant);
    $approval=lastLine($o,'MERCHANT_APPROVAL');check($approval['before']['approvalDigestMatchesFingerprint']===true&&$approval['after']['approvalDigestMatchesFingerprint']===false&&$approval['resetByThisInstall']===true&&str_contains($o,'OWNER_NOTICE_MERCHANT_APPROVAL=THE_MERCHANT_APPROVAL_IS_RESET'),'APPROVAL_RESET_PRINTED_'.$variant);
    $smokes=lastLine($o,'PUBLIC_HTTP_SMOKES');check($smokes['state']==='INSTALLED'&&$smokes['failed']===[]&&count(array_filter($smokes['rows'],static fn($r)=>in_array($r['path'],OverlayCoordinator::LEGAL,true)&&$r['observed']===200&&$r['noindex']))===8,'LEGAL_200_NOINDEX_'.$variant);
    check($status('/admin/settings/public-storefront',['Cookie: '.$adminCookie])===200&&$status('/admin/settings/public-storefront/legal/terms',['Cookie: '.$adminCookie])===200,'SUPERADMIN_PAGES_NEW_CODE_'.$variant);
    check(($variant==='routeCache')===($routeCacheSHA()!==null)&&($cacheBefore===null||$routeCacheSHA()!==$cacheBefore),'ROUTE_CACHE_MODE_KEPT_'.$variant);
    check(!str_contains($o,'globalReset":true')&&str_contains($o,'"label":"installed-invalidate"'),'TARGETED_INVALIDATION_ONLY_'.$variant);
    $results[$variant]['install']=['final'=>lastRaw($o,'B1146_FINAL'),'stepSmokePoints'=>$steps['points'],'approval'=>$approval['resetByThisInstall']];

    // Idempotent re-run.
    [$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1146_FINAL')==='PASS_ALREADY_INSTALLED'&&!str_contains($o,'OVERLAY_STEP=')&&$tree()==='INSTALLED','IDEMPOTENT_RERUN_'.$variant);$results[$variant]['rerun']='PASS_ALREADY_INSTALLED';

    // Owner rollback: exact old bytes, old route cache bytes, legal pages 404, approval matches the old code again.
    [$rc,$o]=$run([],'rollback');check($rc===0&&lastRaw($o,'B1146_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE'&&$routeCacheSHA()===$cacheBefore&&!is_dir($active.'/resources/views/public-shop/legal'),'OWNER_ROLLBACK_'.$variant);
    check(lastLine($o,'PUBLIC_HTTP_SMOKES')['state']==='OLD'&&$status('/pravne-informacije')===404&&$status('/admin/settings/public-storefront',['Cookie: '.$adminCookie])===200,'OLD_STATE_SERVED_'.$variant);
    $after=json_decode(sh(['php','-d','opcache.enable=0',$incoming.'/b1146-app-check.php',$active,'approval']),true);check($after['approvalDigestMatchesFingerprint']===true&&$after['legalTextsInFingerprint']===false,'OLD_APPROVAL_VALID_AGAIN_'.$variant);
    $results[$variant]['ownerRollback']=['final'=>'PASS_ROLLED_BACK_BY_OWNER','routeCacheBytesRestored'=>$routeCacheSHA()===$cacheBefore];$reset();

    // Forced acceptance failure (a new ERROR line in laravel.log): automatic rollback to the verified old state.
    [$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['logError']]);check($rc===2&&lastRaw($o,'B1146_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"NEW_LARAVEL_LOG_ERRORS_SINCE_INSTALL"'&&$tree()==='PRE'&&$routeCacheSHA()===$cacheBefore&&lastLine($o,'B1146_PHASE')==='ROLLED_BACK','FORCED_FAILURE_ROLLED_BACK_'.$variant);
    check($status('/pravne-informacije')===404&&$status('/login')===200,'FORCED_FAILURE_OLD_SERVED_'.$variant);$results[$variant]['forcedFailure']='ROLLED_BACK';
    // The next run after ROLLED_BACK installs again in a new private transaction.
    [$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1146_FINAL')==='PASS_INSTALLED_LEGAL_OVERLAY_STOREFRONT_CLOSED'&&$tree()==='INSTALLED','REINSTALL_AFTER_ROLLBACK_'.$variant);[$rc,$o]=$run([],'rollback');check($rc===0&&$tree()==='PRE','ROLLBACK_AGAIN_'.$variant);$reset();

    // SIGKILL in the middle of the copy (after routes/web.php in the route-cache variant): the re-run restores, then installs.
    $point=$variant==='routeCache'?'AFTER_ROUTES':'AFTER_STEP_14';[$rc,$o]=$run([$point=>['kill']]);check($rc!==0&&$tree()==='MIXED','KILLED_MID_COPY_'.$variant);
    [$rc,$o]=$run();check($rc===2&&lastRaw($o,'B1146_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&str_contains($o,'RESUMED_INTERRUPTED_INSTALL=')&&$tree()==='PRE'&&$routeCacheSHA()===$cacheBefore,'KILL_RERUN_RESTORES_'.$variant);
    [$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1146_FINAL')==='PASS_INSTALLED_LEGAL_OVERLAY_STOREFRONT_CLOSED','KILL_THEN_INSTALL_'.$variant);[$rc,$o]=$run([],'rollback');check($rc===0&&$tree()==='PRE','KILL_CASE_CLEANUP_'.$variant);
    $results[$variant]['killMidCopy']='RESTORED_THEN_INSTALLED';$reset();
}
// A probe file never stays behind and the server log has no fatal error.
check(glob($active.'/public/ald1n-b1144-*')===[],'NO_PROBE_LEFT');check(!preg_match('/PHP Fatal error/',(string)file_get_contents($base.'/server.log')),'NO_FATAL_IN_SERVER_LOG');
proc_terminate($server);echo json_encode(['b1146Fixture'=>'PASS','cases'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
