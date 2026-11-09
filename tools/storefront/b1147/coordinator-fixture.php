<?php
declare(strict_types=1);
/**
 * The real B1147 coordinator over the real staged 8531-file runtime (ef3f927) in the production topology (active tree with
 * .env and storage symlinks into a retained tree), after the B1145 unclamp (staged original config, .env PUBLIC_* = true,
 * 71 migrations ran), real MariaDB 10.11.19 and real Laravel served by `php -S` with OPcache (validate_timestamps=1,
 * revalidate_freq=2). The production history is replayed: first the REAL B1146 coordinator installs the legal overlay
 * (shop closed, its own Report1109), then the shop is turned ON (switch '1', 30 synthetic products), then B1147 runs with
 * the real catalog payload taken from the built operator ZIP.
 * Explicit doubles: plain loopback HTTP instead of HTTPS, realpath_cache_ttl=10 on the test server (shorter hold), a fixed
 * app-local clock outside the backup window, the shop switch written directly into the fixture database.
 * Cases: refusal before B1146, unknown pre-state refusal, shop-OFF install refusal, census before the backup, time budget,
 * nothing-applied rollback, missing JSON-LD and missing CSP rolled back, install with a smoke after every single file step
 * (/katalog and a legal page 200 throughout), idempotent re-run, --rollback inside the backup window (HOLD + manual-only
 * block) and with --ignore-schedule, owner rollback with the shop ON and with the shop OFF, forced acceptance failure with
 * automatic rollback, a failed restore that keeps the exact install prefix and the manual block executed as pasted,
 * SIGKILL during the copy and convergence, stale backup copies after a kill during the backup, and the same with a route
 * cache present (never touched, exact bytes throughout).
 * Usage: php coordinator-fixture.php <b1147> <b1146> <b1145> <b1144> <b1134> <runtime stage> <payload dir> <runtime-directories.json> <b1146 zip> <b1147 zip>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$tools,$b1146,$b1145,$b1144,$b1134,$stage,$payloadDir,$directoryDoc,$zip1146,$zipPath]=$argv;
require $b1134.'/ReleasePrimitives.php';require $b1134.'/InstallFileOps.php';foreach(['LeanGates','FreshnessProbe','LeanWeb'] as $c)require $b1144.'/'.$c.'.php';require $b1145.'/EnvFlags.php';require $b1145.'/MigrateUnclampCoordinator.php';require $b1146.'/OverlayProbe.php';foreach(['CatalogWeb','CatalogOverlayCoordinator','FixtureWeb'] as $c)require $tools.'/'.$c.'.php';
const DB='ald1n_b1147_ci';const DB_PASSWORD='isolated-ci-only';const PORT=8147;
function check(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('B1147_FIXTURE_'.$name);}
function sh(array $args,?string $cwd=null):string{$p=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0)throw new RuntimeException('B1147_FIXTURE_COMMAND_'.basename($args[0]).'_RC'.$rc.':'.substr($err.$out,-900));return trim((string)$out);}
function lastLine(string $output,string $key):mixed{return preg_match_all('/^'.preg_quote($key,'/').'=(.+)$/m',$output,$m)?json_decode(end($m[1]),true,64,JSON_THROW_ON_ERROR):null;}
function lastRaw(string $output,string $key):?string{return preg_match_all('/^'.preg_quote($key,'/').'=(.*)$/m',$output,$m)?end($m[1]):null;}
function rmTree(string $path):void{if(is_link($path)||is_file($path)){unlink($path);return;}if(!is_dir($path))return;foreach(scandir($path) as $n)if($n!=='.'&&$n!=='..')rmTree($path.'/'.$n);rmdir($path);}
function child(array $argv):array{$p=proc_open($argv,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return [proc_close($p),$out,$err];}

$pdo=static fn():PDO=>new PDO('mysql:host=127.0.0.1;port=3306;dbname='.DB,'root',DB_PASSWORD,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
check(preg_match('/^10\.11\.19-MariaDB/',(string)$pdo()->query('SELECT VERSION()')->fetchColumn())===1,'MARIADB_10_11_19');
$manifest=json_decode((string)file_get_contents($payloadDir.'/runtime-manifest.json'),true,512,JSON_THROW_ON_ERROR);$directories=json_decode((string)file_get_contents($directoryDoc),true,512,JSON_THROW_ON_ERROR)['directories'];check(count($manifest['files'])===8531,'MANIFEST_8531');

// Production topology: active tree from the pinned runtime; .env and storage are symlinks into the retained tree.
$base=getenv('RUNNER_TEMP').'/b1147';mkdir($base);$base=realpath($base);$home=$base.'/home';$root=$home.'/ald1n-project';foreach([$root.'/incoming',$root.'/apps/cms',$root.'/docs/operations',$home.'/cms-backups'] as $d)mkdir($d,0755,true);
$runtime=$home.'/.ald1n-b1134-retained/'.str_repeat('d',32);mkdir($runtime,0755,true);$retained=$runtime.'/retained';$active=$root.'/apps/cms/current';$candidate=$runtime.'/candidate';$old=$runtime.'/old';
InstallFileOps::candidate($stage,$candidate,$manifest,$directories);if(!is_dir($candidate.'/bootstrap/cache'))mkdir($candidate.'/bootstrap/cache',0755,true);
foreach(['storage/app/public','storage/logs','storage/framework/sessions','storage/framework/cache/data','storage/framework/views'] as $p)mkdir($old.'/'.$p,0755,true);
$envBytes=implode("\n",['APP_NAME=Ald1n','APP_ENV=production','APP_DEBUG=false','APP_KEY=base64:'.base64_encode(random_bytes(32)),'APP_URL=https://cms.ald1n.com','APP_TIMEZONE=Europe/Belgrade','APP_MAINTENANCE_DRIVER=file','DB_CONNECTION=mysql','DB_HOST=127.0.0.1','DB_PORT=3306','DB_DATABASE='.DB,'DB_USERNAME=root','DB_PASSWORD='.DB_PASSWORD,
    'SESSION_DRIVER=file','SESSION_SECURE_COOKIE=false','CACHE_STORE=file','QUEUE_CONNECTION=sync','MAIL_MAILER=array','LOG_CHANNEL=daily','LOG_LEVEL=debug','TURNSTILE_ENABLED=false','PULSE_ENABLED=false','TELESCOPE_ENABLED=false','MOBILE_PUSH_ENABLED=false','OPERATIONAL_EMAIL_NOTIFICATIONS=false',
    'LEGACY_DB_DATABASE=disabled','LEGACY_DB_USERNAME=disabled_qa_legacy','BACKUP_PATH='.$home.'/cms-backups','PUBLIC_STOREFRONT_ENABLED=true','PUBLIC_CHECKOUT_ENABLED=true','']);
file_put_contents($old.'/.env',$envBytes);chmod($old.'/.env',0640);
if(!is_dir($old.'/public'))mkdir($old.'/public',0755,true);check(copy($candidate.'/public/.htaccess',$old.'/public/.htaccess')&&chmod($old.'/public/.htaccess',0644),'OLD_TREE_HTACCESS');
ReleasePrimitives::preservePrivate($old,$candidate,$retained);rename($old,$retained);rename($candidate,$active);
check(is_link($active.'/.env')&&is_link($active.'/storage/logs'),'TOPOLOGY_SYMLINKS');check(hash_file('sha256',$active.'/config/public_storefront.php')===CatalogOverlayCoordinator::CONFIG_SHA,'CONFIG_IS_STAGED_ORIGINAL_AFTER_B1145');
$stageDir=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU';mkdir($stageDir.'/verification',0755,true);copy($payloadDir.'/runtime-manifest.json',$stageDir.'/verification/runtime-manifest.json');
$htaccess=hash_file('sha256',$active.'/public/.htaccess');

// The B1145 terminal state and a Report1108-shaped terminal report (the predecessor of B1146).
$b1145Base=$home.'/.ald1n-b1145-state';mkdir($b1145Base,0700);$b1145Id=bin2hex(random_bytes(16));mkdir($b1145Base.'/'.$b1145Id,0700);
file_put_contents($b1145Base.'/'.$b1145Id.'/state.json',json_encode(['schemaVersion'=>1,'kind'=>'B1145','id'=>$b1145Id,'private'=>$b1145Base.'/'.$b1145Id,'phase'=>'CLOSED_UNCLAMPED','b1144'=>['retained'=>$retained,'htaccessSHA256'=>$htaccess]],JSON_UNESCAPED_SLASHES));
file_put_contents($b1145Base.'/current.json',json_encode(['schemaVersion'=>1,'kind'=>'B1145','private'=>$b1145Base.'/'.$b1145Id,'id'=>$b1145Id],JSON_UNESCAPED_SLASHES));
file_put_contents($root.'/docs/operations/1108-B1145-CONTROLLED-MIGRATE-UNCLAMP-20261108-150000.md',implode("\n",['B1145 fixture report','B1145_PHASE="CLOSED_UNCLAMPED"','B1145_FINAL=PASS_MIGRATED_UNCLAMPED_STOREFRONT_CLOSED','EXIT=0','']));
sh(['git','init','--quiet','-b','main'],$root);sh(['git','-c','user.name=Fixture','-c','user.email=fixture@example.invalid','commit','--allow-empty','-m','fixture','--quiet'],$root);

// Both operator packages exactly as the owner unzips them, plus the pinned helpers already on the host under their host names.
sh(['unzip','-q','-n',$zip1146,'-d',$root]);sh(['unzip','-q','-n',$zipPath,'-d',$root]);$incoming=$root.'/incoming';
foreach(['b1134/InstallFileOps.php'=>'b1134-files.php','b1144/LeanGates.php'=>'b1144-gates.php','b1144/FreshnessProbe.php'=>'b1144-probe.php','b1144/LeanWeb.php'=>'b1144-web.php','b1145/EnvFlags.php'=>'b1145-env.php','b1145/AppState.php'=>'b1145-app-state.php','b1145/MigrateUnclampCoordinator.php'=>'b1145-coordinator.php'] as $from=>$to)copy(dirname($tools).'/'.$from,$incoming.'/'.$to);
$package=json_decode((string)file_get_contents($incoming.'/b1147-package.json'),true,64,JSON_THROW_ON_ERROR);foreach($package['files'] as $n=>$d)check(hash_file('sha256',$incoming.'/'.$n)===$d,'PACKAGE_FILE_'.$n);$plan=CatalogOverlayCoordinator::plan($package,$incoming);check(count($plan)===8,'PLAN_8');
$purge=(new ReflectionClass(CatalogOverlayCoordinator::class))->newInstanceWithoutConstructor();(new ReflectionProperty($purge,'cssVersion'))->setValue($purge,$package['cssVersion']);$purgeStep=$purge->purgeStep();check(str_contains($purgeStep,'ald1n-public-storefront.css?v='.$package['cssVersion'].'_'),'PURGE_STEP_NAMES_THE_VERSIONED_URL');

// Database after B1145: all 71 migrations ran, production roles, DB switch absent; one superadmin; the old-code approval signed.
$artisan=static fn(array $a):string=>sh(array_merge(['php','-d','opcache.enable=0',$active.'/artisan'],$a,['--no-interaction','--no-ansi']),$active);
check(count(glob($active.'/database/migrations/*.php'))===71,'SEVENTY_ONE_MIGRATION_FILES');$artisan(['migrate','--force']);$artisan(['db:seed','--class=Database\Seeders\CoreAccessSeeder','--force']);
$pdo()->exec("UPDATE roles SET name='Korisnik' WHERE slug='user'");
$roleId=(int)$pdo()->query("SELECT id FROM roles WHERE slug='superadmin'")->fetchColumn();check($roleId>0,'SUPERADMIN_ROLE');
$pdo()->prepare("INSERT INTO users (role_id,username,email,first_name,password_hash,status,created_at,updated_at) VALUES (?,?,?,?,?,'active',NOW(),NOW())")->execute([$roleId,'b1147-owner','b1147-owner@example.test','Fixture',password_hash('SyntheticOnly123!',PASSWORD_BCRYPT)]);$ownerId=(int)$pdo()->query("SELECT id FROM users WHERE username='b1147-owner'")->fetchColumn();check($ownerId>0,'OWNER_ROW');
$fingerprintScript=$base.'/fingerprint.php';file_put_contents($fingerprintScript,'<?php chdir($argv[1]);require "vendor/autoload.php";$app=require "bootstrap/app.php";$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();echo $app->make(App\Services\PublicMerchantPolicyService::class)->review()["fingerprint"];');
$pdo()->prepare("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('public_checkout_approval_digest',?,NOW(),NOW())")->execute([sh(['php','-d','opcache.enable=0',$fingerprintScript,$active])]);$artisan(['cache:clear']);

// Real Laravel behind php -S (cli-server SAPI) with OPcache on; requests carry Host: cms.ald1n.com.
$server=proc_open([PHP_BINARY,'-d','opcache.enable=1','-d','opcache.enable_cli=1','-d','opcache.validate_timestamps=1','-d','opcache.revalidate_freq=2','-d','opcache.file_update_protection=0','-d','realpath_cache_ttl=10','-S','127.0.0.1:'.PORT,'-t',$active.'/public',$active.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],[0=>['pipe','r'],1=>['file',$base.'/server.log','a'],2=>['file',$base.'/server.log','a']],$pipes,$active.'/public',['PATH'=>getenv('PATH'),'HOME'=>$home]);
$status=static fn(string $target):int=>CatalogFixtureWeb::get(PORT,$target)['status'];$body=static fn(string $target):string=>CatalogFixtureWeb::get(PORT,$target)['body'];
for($i=0;$i<60&&$status('/up')!==200;$i++)usleep(500000);check($status('/up')===200&&$status('/katalog')===404&&$status('/pravne-informacije')===404,'REAL_LARAVEL_SERVED_PRE_B1146');

$private=$home.'/.ald1n-b1147-state';$private1146=$home.'/.ald1n-b1146-state';$secrets=$base.'/probe-secrets';$faultLog=$base.'/faults.log';$stepLog=$base.'/steps.jsonl';$logDir=$retained.'/storage/logs';$configJSON=$base.'/run.json';$safeNow=strtotime('2026-11-11 13:00:00 UTC');
$dropCsp=$base.'/drop-csp';$dropJsonLd=$base.'/drop-json-ld';$busyPidFile=$base.'/busy.pid';$allOutput='';file_put_contents($logDir.'/laravel-'.date('Y-m-d').'.log','',FILE_APPEND);
$run=static function(array $actions=[],string $mode='install',array $overrides=[])use($configJSON,$tools,$b1146,$b1145,$b1144,$b1134,$root,$home,$private,$private1146,$stageDir,$incoming,$secrets,$faultLog,$stepLog,$logDir,$safeNow,$dropCsp,$dropJsonLd,$busyPidFile,&$allOutput):array{
    file_put_contents($configJSON,json_encode(['overrides'=>$overrides,'dropCsp'=>$dropCsp,'dropJsonLd'=>$dropJsonLd,'rate429'=>dirname($dropCsp).'/rate-429','db'=>['mysql:host=127.0.0.1;port=3306;dbname='.DB,'root',DB_PASSWORD],'busyPidFile'=>$busyPidFile,'tools'=>$tools,'b1146'=>$b1146,'b1145'=>$b1145,'b1144'=>$b1144,'b1134'=>$b1134,'root'=>$root,'home'=>$home,'base'=>$private,'pointer'=>$private.'/current.json','stage'=>$stageDir,'b1146Base'=>$private1146,
        'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','catalogScript'=>$incoming.'/b1147-app-check.php','php'=>PHP_BINARY,'allowedSapis'=>['cli-server'],
        'censusSeconds'=>20,'minFreeBytes'=>268435456,'scheduleGate'=>true,'fixedNow'=>$safeNow,'incoming'=>$incoming,'mode'=>$mode,'port'=>PORT,'secrets'=>$secrets,'faultLog'=>$faultLog,'stepLog'=>$stepLog,'logDir'=>$logDir,'actions'=>$actions],JSON_UNESCAPED_SLASHES));
    [$rc,$out,$err]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$tools.'/fixture-run.php',$configJSON]);
    echo "----- B1147 RUN mode=$mode rc=$rc -----\n".$out.($err!==''?"\nSTDERR=".substr($err,-2000)."\n":'');$allOutput.=$out;return [$rc,$out];
};
$tree=static function()use($plan,$active):string{$pre=true;$inst=true;foreach($plan as $s){$t=$active.'/'.$s['path'];$sha=is_file($t)?hash_file('sha256',$t):null;$pre=$pre&&$sha===$s['preSHA256'];$inst=$inst&&$sha===$s['payloadSHA256'];}return $pre?'PRE':($inst?'INSTALLED':'MIXED');};
$routeCacheSHA=static function()use($active):?string{$files=glob($active.'/bootstrap/cache/routes-*.php')?:[];sort($files);if($files===[])return null;$h=[];foreach($files as $f)$h[]=basename($f).':'.hash_file('sha256',$f);return hash('sha256',implode("\n",$h));};
$results=[];

// 0. B1147 before B1146 (the tree is still the 8531-file manifest): HOLD before any change.
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&lastLine($o,'B1147_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('B1146_REPORT1109_MISSING',$p['blockers'],true)&&in_array('UNKNOWN_PRE_STATE_REFUSED',$p['blockers'],true)&&!str_contains($o,'OVERLAY_STEP=')&&!str_contains($o,'PER_FILE_BACKUP='),'REFUSED_BEFORE_B1146');rmTree($private);$results['beforeB1146']='HOLD_NOTHING_CHANGED';

// 1. The real B1146 coordinator installs the legal overlay with the shop closed; its output becomes Report1109.
$cfg1146=$base.'/run1146.json';file_put_contents($cfg1146,json_encode(['overrides'=>[],'dropCsp'=>$base.'/drop-csp-1146','busyPidFile'=>$base.'/busy1146.pid','hiddenWeb'=>$base.'/web.hidden','tools'=>$b1146,'b1145'=>$b1145,'b1144'=>$b1144,'b1134'=>$b1134,'root'=>$root,'home'=>$home,'base'=>$private1146,'pointer'=>$private1146.'/current.json','stage'=>$stageDir,'b1145Base'=>$b1145Base,
    'appStateScript'=>$incoming.'/b1145-app-state.php','appCheckScript'=>$incoming.'/b1146-app-check.php','php'=>PHP_BINARY,'allowedSapis'=>['cli-server'],'censusSeconds'=>20,'minFreeBytes'=>268435456,'scheduleGate'=>true,'fixedNow'=>$safeNow,'incoming'=>$incoming,'mode'=>'install','port'=>PORT,'secrets'=>$secrets,'faultLog'=>$faultLog,'stepLog'=>$base.'/steps1146.jsonl','logDir'=>$logDir,'adminCookie'=>'','actions'=>[]],JSON_UNESCAPED_SLASHES));
[$rc,$o1146,$e1146]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$b1146.'/fixture-run.php',$cfg1146]);echo "----- B1146 RUN rc=$rc -----\n".$o1146.($e1146!==''?"\nSTDERR=".substr($e1146,-2000)."\n":'');
check($rc===0&&lastRaw($o1146,'B1146_FINAL')===CatalogOverlayCoordinator::B1146_PASS,'REAL_B1146_INSTALLED');
file_put_contents($root.'/docs/operations/1109-B1146-LEGAL-OVERLAY-20261108-160000.md',"B1146 fixture report\n".$o1146."EXIT=0\nREPORT=".$root."/docs/operations/1109-B1146-LEGAL-OVERLAY-20261108-160000.md\n");
check($status('/pravne-informacije')===200&&$status('/katalog')===404,'B1146_STATE_SERVED');

// 2. The owner turns the shop ON (fixture: the switch row written directly); 30 synthetic products.
$pdo()->exec("INSERT INTO brands (name,slug,status,created_at,updated_at) VALUES ('Lenovo','lenovo-b1147-ci','active',NOW(),NOW())");$brandId=(int)$pdo()->query("SELECT id FROM brands WHERE slug='lenovo-b1147-ci'")->fetchColumn();check($brandId>0,'BRAND_ROW');
$insert=$pdo()->prepare("INSERT INTO products (sku,slug,name,description,price_amount,price_currency,stock_quantity,status,brand_id,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,'active',?,?,NOW(),NOW())");
for($i=1;$i<=30;$i++)$insert->execute(['B1147-CI-'.$i,'b1147-ci-'.$i,'ThinkPad Synthetic '.$i,'Synthetic B1147 row',1000+97*$i,$i%3===0?'EUR':'RSD',$i%4,$i%2?$brandId:null,$ownerId]);
$pdo()->exec("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('eur_rsd_rate','117.2',NOW(),NOW()) ON DUPLICATE KEY UPDATE setting_value='117.2'");
$shopOn=static function()use($pdo,$artisan):void{$pdo()->exec("DELETE FROM settings WHERE setting_key='public_storefront_open'");$pdo()->exec("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('public_storefront_open','1',NOW(),NOW())");$artisan(['cache:clear']);};
$shopOff=static function()use($pdo,$artisan):void{$pdo()->exec("DELETE FROM settings WHERE setting_key='public_storefront_open'");$artisan(['cache:clear']);};
$shopFlag=static fn():?string=>(($v=$pdo()->query("SELECT setting_value FROM settings WHERE setting_key='public_storefront_open'")->fetchColumn())===false?null:(string)$v);
$shopOn();sleep(3);check($status('/katalog')===200&&$status('/katalog?page=999')<500&&!str_contains($body('/katalog'),$package['markers']['layout']),'SHOP_ON_OLD_CATALOG_SERVED');
$results['b1146']=['final'=>CatalogOverlayCoordinator::B1146_PASS,'report'=>'1109'];

$reset=static function()use($private,$secrets,$faultLog,$stepLog,$logDir,$artisan,$tree,$status,$dropCsp,$dropJsonLd):void{
    rmTree($private);foreach(array_merge([$secrets,$faultLog,$stepLog,$dropCsp,$dropJsonLd,dirname($dropCsp).'/rate-429'],glob($logDir.'/laravel*.log')?:[]) as $f)if(file_exists($f))unlink($f);file_put_contents($logDir.'/laravel-'.date('Y-m-d').'.log','');$artisan(['cache:clear']);
    check($tree()==='PRE','RESET_TREE_PRE');sleep(3);check($status('/login')===200,'RESET_WARM_LOGIN');
};
$allSteps=static function():array{$a=[];for($n=1;$n<=8;$n++)$a['AFTER_STEP_'.$n]=['stepSmoke'];$a['AFTER_INVALIDATE']=['stepSmoke'];return $a;};
$stepsClean=static function()use($stepLog):array{$rows=array_map(static fn($l)=>json_decode($l,true),array_filter(explode("\n",(string)@file_get_contents($stepLog))));$bad=[];
    foreach($rows as $r)foreach($r['anon'] as $p=>$s)if($s!==200)$bad[]=$r['point'].' '.$p.' '.$s;return ['points'=>count($rows),'bad'=>$bad];};
$prefix=static function(int $k)use($plan,$active):void{foreach($plan as $s){$t=$active.'/'.$s['path'];$sha=is_file($t)?hash_file('sha256',$t):null;check($sha===($s['n']<=$k?$s['payloadSHA256']:$s['preSHA256']),'PREFIX_'.$k.'_STEP_'.$s['n']);}};
$block=static function(string $o):array{check(preg_match("/^bash -e <<'B1147_RECOVERY'\n(.*?)\nB1147_RECOVERY$/ms",$o,$m)===1,'RECOVERY_BLOCK_PRESENT');return explode("\n",$m[1]);};
$kindOf=static function(string $l):?string{$last=preg_match("/'([^']*)'$/",$l,$m)?$m[1]:null;
    if(in_array($l,['set -euo pipefail','umask 022','echo B1147_MANUAL_RECOVERY_BLOCK_DONE'],true))return null;if(str_starts_with($l,'printf ')&&str_contains($l,'| sha256sum --check --strict --quiet -'))return 'verify';
    if(str_starts_with($l,'install -m ')&&str_contains($l,' -g '))return 'install:'.$last;if(str_starts_with($l,'rm -f -- '))return 'rm:'.$last;if(str_starts_with($l,'rmdir --ignore-fail-on-non-empty -- '))return 'rmdir';if(preg_match('/^sleep [0-9]+$/D',$l))return 'sleep';if(str_contains($l,' artisan view:clear --no-interaction'))return 'view';return 'other';};
$runBlock=static function(array $lines):array{$p=proc_open(['bash','-e'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fwrite($pipes[0],implode("\n",$lines)."\n");fclose($pipes[0]);$out=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return ['rc'=>proc_close($p),'out'=>$out];};
$conflictNow=(new DateTimeImmutable('2026-11-11 02:10:00',new DateTimeZone('Europe/Belgrade')))->getTimestamp();
$smokeRows=static function(string $o):array{$s=lastLine($o,'PUBLIC_HTTP_SMOKES');$by=[];foreach($s['rows'] as $r)$by[$r['check']]=$r;return [$s,$by];};

foreach(['noRouteCache','routeCache'] as $variant){
    $artisan(['route:clear']);if($variant==='routeCache'){$artisan(['route:cache']);check($routeCacheSHA()!==null,'ROUTE_CACHE_CREATED');}$cacheBefore=$routeCacheSHA();$artisan(['view:clear']);$reset();

    if($variant==='noRouteCache'){
        // Refusal: an unknown pre-state of one replaced file stops the run before any change.
        $ctl=$active.'/app/Http/Controllers/PublicShopController.php';$original=file_get_contents($ctl);file_put_contents($ctl,$original."\n// foreign local edit\n");
        [$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&lastLine($o,'B1147_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('UNKNOWN_PRE_STATE_REFUSED',$p['blockers'],true)&&!str_contains($o,'OVERLAY_STEP='),'UNKNOWN_PRE_STATE_REFUSED');
        check(file_get_contents($ctl)===$original."\n// foreign local edit\n"&&!is_file($active.'/app/Support/PublicCatalogPresentation.php'),'UNKNOWN_PRE_STATE_NOTHING_CHANGED');file_put_contents($ctl,$original);$reset();$results[$variant]['unknownPreState']='HOLD_NOTHING_CHANGED';
        // With the shop OFF a fresh install HOLDs (the catalog acceptance needs the open shop); the switch is never written.
        $shopOff();[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&in_array('SHOP_IS_OFF_B1147_INSTALLS_ONLY_WITH_THE_SHOP_ON_NOTHING_CHANGED',$p['blockers'],true)&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE'&&$shopFlag()===null,'SHOP_OFF_INSTALL_REFUSED');$shopOn();$reset();$results[$variant]['shopOffInstall']='HOLD_NOTHING_CHANGED';
        // An artisan run that starts after the preflight is seen by the census BEFORE the backup; nothing is written.
        [$rc,$o]=$run(['AFTER_PREFLIGHT'=>['busy']]);$pid=(int)trim((string)@file_get_contents($busyPidFile));if($pid>0){@posix_kill(-$pid,9);@posix_kill($pid,9);}shell_exec("pkill -9 -f 'sleep 40; true' || true");@unlink($busyPidFile);
        check($rc===2&&lastLine($o,'B1147_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&str_contains($o,'"blockers":["CMS_ARTISAN_RUNNING"]')&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','CENSUS_BEFORE_BACKUP');$reset();
        // A failure before the first file: "nothing applied", no view or web work.
        [$rc,$o]=$run(['BEFORE_STEP_1'=>['fail']]);$tail=substr($o,(int)strpos($o,'OVERLAY_ROLLBACK='));
        check($rc===2&&lastRaw($o,'B1147_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&lastLine($o,'ROLLBACK_FILES')['nothingApplied']===true&&!str_contains($tail,'ARTISAN=')&&!str_contains($tail,'WEB_OVERLAY_PROBE=')&&$tree()==='PRE','NOTHING_APPLIED_NO_REFRESH');$reset();
        // The time budget is checked right before the first change.
        [$rc,$o]=$run([],'install',['budgetSeconds'=>0]);check($rc===2&&str_contains($o,'"blockers":["TIME_BUDGET_EXCEEDED_BEFORE_FIRST_CHANGE"]')&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','TIME_BUDGET_HOLD');$reset();
        // A catalog page without its JSON-LD block, and one without CSP script-src 'none': acceptance fails, rolled back.
        [$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['dropJsonLd'],'BEFORE_SMOKES_OLD'=>['clearDrops']]);@unlink($dropJsonLd);[$s,$by]=$smokeRows(substr($o,0,(int)strpos($o,'OVERLAY_ROLLBACK=')));
        check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"INSTALLED_SMOKE_FAILED"'&&$by['/katalog']['jsonLd']==='JSON_LD_MISSING'&&lastRaw($o,'B1147_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE','MISSING_JSON_LD_ROLLED_BACK');$reset();
        [$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['dropCsp'],'BEFORE_SMOKES_OLD'=>['clearDrops']]);@unlink($dropCsp);
        check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"INSTALLED_SMOKE_FAILED"'&&lastRaw($o,'B1147_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE','MISSING_CSP_ROLLED_BACK');$reset();
        $results[$variant]+=['censusBeforeBackup'=>'HOLD_NOTHING_WRITTEN','nothingApplied'=>'NO_REFRESH_NO_WEB_WORK','timeBudget'=>'HOLD','missingJsonLd'=>'ROLLED_BACK','missingCsp'=>'ROLLED_BACK'];
    }else{
        // An interrupted backup left changed private copies; the next run writes every stale copy again and the later
        // rollback still puts back the exact old bytes.
        [$rc,$o]=$run(['AFTER_BACKUP_COPIES'=>['corruptBackup','kill']]);check($rc!==0&&$tree()==='PRE'&&!str_contains($o,'OVERLAY_STEP='),'KILLED_DURING_BACKUP');
        [$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1147_FINAL')==='PASS_INSTALLED_CATALOG_OVERLAY_SHOP_STATE_UNCHANGED'&&preg_match_all('/^BACKUP_COPY_REWRITTEN=/m',$o)===5,'STALE_BACKUP_COPIES_REWRITTEN');
        [$rc,$o]=$run([],'rollback');check($rc===0&&$tree()==='PRE'&&$routeCacheSHA()===$cacheBefore,'ROLLBACK_AFTER_REWRITTEN_COPIES_EXACT');$reset();
        $results[$variant]['staleBackupCopies']=['rewritten'=>5,'rollbackExact'=>true];
    }

    // Install with a smoke after every single file step: /katalog and a legal page stay 200 throughout (install order).
    [$rc,$o]=$run($allSteps());$steps=$stepsClean();echo 'STEP_SMOKES='.json_encode($steps,JSON_UNESCAPED_SLASHES).PHP_EOL;
    check($rc===0&&lastRaw($o,'B1147_FINAL')==='PASS_INSTALLED_CATALOG_OVERLAY_SHOP_STATE_UNCHANGED'&&$tree()==='INSTALLED','INSTALL_PASS_'.$variant);check($steps['points']===9&&$steps['bad']===[],'EVERY_STEP_SERVES_'.$variant.':'.implode(',',$steps['bad']));
    [$s,$by]=$smokeRows($o);check($s['state']==='INSTALLED'&&$s['failed']===[]&&$s['shop']['open']===true,'SMOKES_PASS_'.$variant);
    check($by['/katalog']['jsonLd']==='PASS'&&$by['/katalog']['layoutMarker']===true&&$by['/katalog']['catalogMarker']===true&&$by['/katalog?page=999']['observed']===404&&$by['/katalog?sort=price_asc']['observed']===200&&in_array($by['/katalog?sort=ald1n_unknown']['observed'],[302,422],true)&&$by[CatalogOverlayCoordinator::CSS_PATH]['cssMarker']===true&&$by['/korpa']['observed']===200,'CATALOG_SMOKES_'.$variant);
    check(count(array_filter($s['rows'],static fn($r)=>in_array($r['check'],CatalogOverlayCoordinator::LEGAL,true)&&$r['result']==='PASS'&&$r['layoutMarker']===true))===8,'LEGAL_200_NEW_LAYOUT_'.$variant);
    $cli=lastLine($o,'CATALOG_CLI_CHECK');check($cli['presentationAutoloaded']===true&&$cli['appUrlHttpsOrigin']===true,'CLI_CLASSES_'.$variant);
    check($routeCacheSHA()===$cacheBefore&&$shopFlag()==='1','ROUTE_CACHE_AND_SHOP_UNTOUCHED_'.$variant);
    check(!str_contains($o,'globalReset":true')&&str_contains($o,'"label":"installed-invalidate"'),'TARGETED_INVALIDATION_ONLY_'.$variant);
    $next=(array)lastLine($o,'OWNER_NEXT_STEPS');check(count($next)===5&&$next[0]===$purgeStep&&str_starts_with((string)$next[4],'ROLLBACK_IF_NEEDED=bash /home/icaffeco/ald1n-project/incoming/b1147-catalog-overlay.sh --rollback'),'OWNER_NEXT_STEPS_COMPLETE_'.$variant);
    check(str_contains($o,'"label":"before-views"')&&(lastLine($o,'MERCHANT_APPROVAL')['ok']??null)==='UNCHANGED'&&str_contains($o,'"database":"NO_PRODUCT_CART_OR_SWITCH_WRITES_SESSIONS_AND_APP_CACHE_ONLY"'),'VIEWS_AFTER_MEASURED_HOLD_APPROVAL_UNCHANGED_'.$variant);
    check(str_contains($body('/katalog'),$package['markers']['layout'])&&str_contains($body(CatalogOverlayCoordinator::CSS_PATH.'?v='.$package['cssVersion']),$package['markers']['css']),'NEW_CATALOG_SERVED_'.$variant);
    $results[$variant]['install']=['final'=>lastRaw($o,'B1147_FINAL'),'stepSmokePoints'=>$steps['points'],'jsonLd'=>'PASS','page999'=>404];

    // Idempotent re-run.
    [$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1147_FINAL')==='PASS_ALREADY_INSTALLED'&&!str_contains($o,'OVERLAY_STEP=')&&$tree()==='INSTALLED','IDEMPOTENT_RERUN_'.$variant);$results[$variant]['rerun']='PASS_ALREADY_INSTALLED';

    if($variant==='noRouteCache'){
        // 20 minutes before the 02:30 backup --rollback holds before any restore and prints the manual-only recovery block.
        [$rc,$o]=$run([],'rollback',['fixedNow'=>$conflictNow]);$note=lastLine($o,'RECOVERY_NOTE');
        check($rc===2&&preg_match('/"blockers":\["SCHEDULED_BACKUP_WITHIN_30_MINUTES_[A-Z_]+_WAIT_OR_RUN_WITH_--rollback_--ignore-schedule"\]/',$o)===1&&!str_contains($o,'OVERLAY_ROLLBACK=')&&$note['kind']==='ROLLBACK_NOT_STARTED_MANUAL_ONLY'&&$block($o)!==[]&&$tree()==='INSTALLED','SCHEDULE_HOLD_WITH_MANUAL_BLOCK');
        $results[$variant]['rollbackInScheduleWindow']='HOLD_NOTHING_CHANGED_MANUAL_ONLY_BLOCK_PRINTED';
    }

    // Owner rollback with the shop ON: exact old bytes, old catalog served, route cache and switch untouched. In the first
    // variant it runs inside the backup window with the owner override (--rollback --ignore-schedule).
    [$rc,$o]=$run([],'rollback',$variant==='noRouteCache'?['fixedNow'=>$conflictNow,'ignoreSchedule'=>true]:[]);
    check($variant!=='noRouteCache'||str_contains($o,'SCHEDULED_BACKUP_CONFLICT_IGNORED_BY_THE_OWNER_OVERRIDE'),'SCHEDULE_OVERRIDE_USED');
    check($rc===0&&lastRaw($o,'B1147_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE'&&$routeCacheSHA()===$cacheBefore&&!is_file($active.'/app/Support/PublicCatalogPresentation.php')&&$shopFlag()==='1','OWNER_ROLLBACK_'.$variant);
    // Rollback order: group D modified views, then the new pagination view in its own later group, ..., the new classes last after the measured hold.
    $pD=strpos($o,'"before":"D-NEW"');$pA=strpos($o,'"label":"rollback-before-new-classes"');check($pD!==false&&$pA!==false&&$pD<$pA&&(lastLine($o,'MERCHANT_APPROVAL')['ok']??null)==='UNCHANGED','ROLLBACK_GROUPS_AND_APPROVAL_'.$variant);
    check(in_array($purgeStep,(array)lastLine($o,'OWNER_NEXT_STEPS'),true),'CSS_PURGE_AFTER_ROLLBACK_'.$variant);
    [$s,$by]=$smokeRows($o);check($s['state']==='OLD'&&$s['shop']['open']===true&&$by['/katalog']['layoutMarker']===false&&$status('/katalog')===200&&$status('/katalog?page=999')<500,'OLD_STATE_SERVED_'.$variant);
    $results[$variant]['ownerRollback']=['final'=>'PASS_ROLLED_BACK_BY_OWNER','shopOn'=>true,'routeCacheBytesUnchanged'=>true];$reset();

    // Forced acceptance failure (a new ERROR line in laravel.log): automatic rollback to the verified old state.
    [$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['logError']]);check($rc===2&&lastRaw($o,'B1147_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"NEW_LARAVEL_LOG_ERRORS_SINCE_INSTALL"'&&$tree()==='PRE'&&$routeCacheSHA()===$cacheBefore&&lastLine($o,'B1147_PHASE')==='ROLLED_BACK','FORCED_FAILURE_ROLLED_BACK_'.$variant);
    check($status('/katalog')===200&&$status('/pravne-informacije')===200,'FORCED_FAILURE_OLD_SERVED_'.$variant);$results[$variant]['forcedFailure']='ROLLED_BACK';

    if($variant==='noRouteCache'){
        // The next run after ROLLED_BACK installs again; the owner then rolls back with the shop OFF (closed-shop smokes).
        [$rc,$o]=$run();check($rc===0&&$tree()==='INSTALLED','REINSTALL_AFTER_ROLLBACK');$shopOff();
        [$rc,$o]=$run([],'rollback');[$s,$by]=$smokeRows($o);check($rc===0&&lastRaw($o,'B1147_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE'&&$s['shop']['open']===false&&$by['/katalog']['observed']===404&&$by['/pravne-informacije']['layoutMarker']===false&&$shopFlag()===null,'ROLLBACK_WITH_SHOP_OFF');$shopOn();$reset();
        $results[$variant]['rollbackShopOff']='PASS_ROLLED_BACK_BY_OWNER_SWITCH_NOT_WRITTEN';
        // 429 from the public-shop-browse throttle (shared edge IP): two 429 answers to /katalog are waited out via Retry-After.
        $rateFile=dirname($dropCsp).'/rate-429';
        [$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['rateLimit2']]);[$s,$by]=$smokeRows($o);
        check($rc===0&&lastRaw($o,'B1147_FINAL')==='PASS_INSTALLED_CATALOG_OVERLAY_SHOP_STATE_UNCHANGED'&&$by['/pravne-informacije']['result']==='PASS'&&$by['/pravne-informacije']['attempts']===3&&$by['/katalog']['result']==='PASS','TRANSIENT_429_WAITED_OUT');
        [$rc,$o]=$run([],'rollback');check($rc===0&&$tree()==='PRE','TRANSIENT_429_CLEANUP');@unlink($rateFile);$reset();
        // A persistent 429 in the installed smokes: one whole-batch retry, then HOLD with the exact files kept (no rollback);
        // the same command later repeats only the acceptance; a persistent 429 after a rollback never makes it incomplete.
        [$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['rateLimitAlways']]);
        check($rc===2&&lastRaw($o,'B1147_FINAL')==='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED'&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"SMOKES_RATE_LIMITED_429"'&&str_contains($o,'SMOKES_RATE_LIMITED_PAUSE=')&&!str_contains($o,'OVERLAY_ROLLBACK=')&&lastLine($o,'B1147_PHASE')==='ACCEPTANCE_NOT_VERIFIED'&&$tree()==='INSTALLED'&&str_contains($o,'ACTIVE_TREE_INSTALLED_UNVERIFIED='),'PERSISTENT_429_HOLD_NO_ROLLBACK');
        // The real per-batch cap and the shared-limiter skip: the first throttled GET (a legal page) waits Retry-After twice
        // (3 + 3 s within the 7 s budget), a third wait would exceed it, so it stops at attempt 3; every later throttled GET
        // (legal pages and shop paths) is skipped without a request; /login, /up and the CSS are still fetched.
        [$s,$by]=$smokeRows($o);$skipped=array_filter($s['rows'],static fn($r)=>($r['skippedSharedLimiter']??false)===true&&$r['attempts']===0);
        check($by['/pravne-informacije']['attempts']===3&&$by['/pravne-informacije']['waitedSeconds']===6&&count($skipped)===14&&$s['rateLimitWaitedSeconds']===6&&$by['/login']['result']==='PASS'&&$by[CatalogOverlayCoordinator::CSS_PATH]['result']==='PASS','RATE_LIMIT_CAP_AND_SHARED_KEY_SKIP');
        @unlink($rateFile);
        [$rc,$o]=$run();check($rc===0&&str_contains($o,'RESUMED_ACCEPTANCE_ONLY=')&&!str_contains($o,'OVERLAY_STEP=')&&lastRaw($o,'B1147_FINAL')==='PASS_INSTALLED_CATALOG_OVERLAY_SHOP_STATE_UNCHANGED'&&$tree()==='INSTALLED','RESUMED_ACCEPTANCE_PASS');
        [$rc,$o]=$run(['BEFORE_SMOKES_OLD'=>['rateLimitAlways']],'rollback');
        check($rc===0&&lastRaw($o,'B1147_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&str_contains($o,'OWNER_NOTICE_RATE_LIMITED=')&&lastLine($o,'B1147_PHASE')==='ROLLED_BACK'&&$tree()==='PRE','ROLLBACK_429_NOT_INCOMPLETE');@unlink($rateFile);$reset();
        $results[$variant]['rateLimit429']=['transient'=>'WAITED_OUT_PASS','persistentInstall'=>'HOLD_FILES_KEPT_THEN_RESUMED_ACCEPTANCE_PASS','persistentRollback'=>'PASS_ROLLED_BACK_WITH_OWNER_NOTICE'];
        // The owner turns the shop OFF while the acceptance runs: no PASS claim, HOLD with the exact files; the resume waits for
        // the shop to be ON again; the switch is never written by the tool.
        // Turned OFF in the middle of the batch (after the legal pages, before the shop paths): the shop paths answer 404,
        // the failures are judged against the changed state, so HOLD, never a rollback of the exact files.
        [$rc,$o]=$run(['DURING_SMOKES_INSTALLED'=>['shopOff']]);
        check($rc===2&&lastRaw($o,'B1147_FINAL')==='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED'&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"SHOP_STATE_CHANGED_DURING_ACCEPTANCE_CATALOG_NOT_VERIFIED"'&&$tree()==='INSTALLED'&&$shopFlag()===null,'SHOP_OFF_DURING_ACCEPTANCE_HOLD');
        [$rc,$o]=$run();check($rc===2&&str_contains($o,'HOLD_ACCEPTANCE_NOT_RESUMED=')&&$tree()==='INSTALLED'&&$shopFlag()===null,'RESUME_REFUSED_WHILE_SHOP_OFF');
        $shopOn();[$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1147_FINAL')==='PASS_INSTALLED_CATALOG_OVERLAY_SHOP_STATE_UNCHANGED','RESUMED_AFTER_SHOP_ON');
        [$rc,$o]=$run([],'rollback');check($rc===0&&$tree()==='PRE','SHOP_OFF_CASE_CLEANUP');$reset();
        $results[$variant]['shopOffDuringAcceptance']='HOLD_NO_PASS_CLAIM_RESUMED_AFTER_SHOP_ON';
        // SIGKILL in the hold right before the new classes go: a fresh process resuming the rollback must still hold (timed
        // from the saved wall-clock time of the last restore), not remove group A at once.
        [$rc,$o]=$run();check($rc===0&&$tree()==='INSTALLED','INSTALL_BEFORE_HOLD_KILL');
        [$rc,$o]=$run(['DURING_HOLD_rollback-before-new-classes'=>['kill']],'rollback');check($rc!==0&&$tree()==='MIXED'&&is_file($active.'/app/Support/PublicCatalogPresentation.php'),'KILLED_IN_THE_HOLD_BEFORE_THE_NEW_CLASSES');
        [$rc,$o]=$run([],'rollback',['censusSeconds'=>1]);$h=null;foreach(explode("\n",$o) as $line)if(str_starts_with($line,'OVERLAY_HOLD_START=')&&str_contains($line,'"label":"rollback-before-new-classes"'))$h=json_decode(substr($line,19),true);
        check($rc===0&&lastRaw($o,'B1147_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE'&&is_array($h)&&$h['clock']==='SAVED_WALL_CLOCK_OF_THE_LAST_CHANGE'&&$h['remainingSeconds']>0,'RESUMED_ROLLBACK_STILL_HOLDS_BEFORE_THE_NEW_CLASSES');$reset();
        $results[$variant]['killInRollbackHold']='FRESH_PROCESS_HELD_FROM_THE_SAVED_WALL_CLOCK_THEN_PASS';
        // One restore fails (step 4, the controller): steps 1..4 stay installed, 5..8 are old again (an exact install
        // prefix). The manual block, run exactly as pasted, restores the old state; the same command then verifies it.
        [$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['logError'],'BEFORE_RESTORE_4'=>['fail']]);$rf=lastLine($o,'ROLLBACK_FILES');$note=lastLine($o,'RECOVERY_NOTE');
        check($rc===2&&lastRaw($o,'B1147_FINAL')==='HOLD_ROLLBACK_INCOMPLETE'&&$rf['stoppedAtStep']===4&&$rf['exactInstallPrefixKept']==='STEPS_1_TO_4_INSTALLED_LATER_STEPS_RESTORED'&&$rf['modifiedRestored']===3&&$rf['newRemoved']===1&&$note['kind']==='ROLLBACK_INCOMPLETE','PARTIAL_ROLLBACK_STOPS_AT_STEP_4');
        $prefix(4);sleep(3);check($status('/katalog')===200&&$status('/pravne-informacije')===200&&$status('/up')===200,'PREFIX_4_SERVES');
        $lines=$block($o);$kinds=array_values(array_filter(array_map($kindOf,$lines)));$expected=[];
        // The automatic order: reverse install order with a sleep at each group boundary (view:clear first before the new
        // pagination view); here steps 4, 3 (group B) then the full hold, then the new classes 2, 1; view:clear last.
        $lastKey=null;foreach(array_reverse($plan) as $s){if($s['n']>4)continue;$key=$s['group'].($s['kind']==='new'?'-NEW':'');if($lastKey!==null&&$lastKey!==$key){if($key==='D-NEW')$expected[]='view';$expected[]='sleep';}$lastKey=$key;if($s['kind']==='modified')array_push($expected,'verify','install:'.$active.'/'.$s['path']);else $expected[]='rm:'.$active.'/'.$s['path'];}$expected[]='view';
        check(in_array('sleep',$expected,true),'EXPECTED_BLOCK_HAS_THE_HOLD_BEFORE_THE_NEW_CLASSES');
        check($kinds===$expected,'RECOVERY_BLOCK_EXACT_REVERSE_ORDER:'.json_encode($kinds));
        $manual=$runBlock($lines);check($manual['rc']===0&&str_contains($manual['out'],'B1147_MANUAL_RECOVERY_BLOCK_DONE')&&$tree()==='PRE','MANUAL_BLOCK_RESTORES_OLD_STATE:'.substr($manual['out'],-400));
        [$rc,$o]=$run();check($rc===2&&lastRaw($o,'B1147_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE','AFTER_MANUAL_BLOCK_VERIFIED');$reset();
        $results[$variant]['partialRollback']='PREFIX_1_4_MANUAL_BLOCK_RESTORED_THEN_VERIFIED';
    }else $reset();

    // SIGKILL in the middle of the copy: the re-run restores, the next installs.
    $point=$variant==='routeCache'?'AFTER_STEP_3':'AFTER_STEP_5';[$rc,$o]=$run([$point=>['kill']]);check($rc!==0&&$tree()==='MIXED','KILLED_MID_COPY_'.$variant);
    [$rc,$o]=$run();check($rc===2&&lastRaw($o,'B1147_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&str_contains($o,'RESUMED_INTERRUPTED_INSTALL=')&&$tree()==='PRE'&&$routeCacheSHA()===$cacheBefore,'KILL_RERUN_RESTORES_'.$variant);
    [$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1147_FINAL')==='PASS_INSTALLED_CATALOG_OVERLAY_SHOP_STATE_UNCHANGED','KILL_THEN_INSTALL_'.$variant);[$rc,$o]=$run([],'rollback');check($rc===0&&$tree()==='PRE','KILL_CASE_CLEANUP_'.$variant);
    $results[$variant]['killMidCopy']='RESTORED_THEN_INSTALLED';$reset();
}
check(glob($active.'/public/ald1n-b1144-*')===[],'NO_PROBE_LEFT');
check(!str_contains($allOutput,'"command":"view:cache"')&&str_contains($allOutput,'"command":"view:clear"')&&!str_contains($allOutput,'"command":"route:'),'VIEW_CLEAR_ONLY_NO_ROUTE_WORK');check(str_contains($allOutput,'"webEuidIsOperator":true')&&!str_contains($allOutput,'"webEuidIsOperator":false'),'WEB_EUID_IS_OPERATOR');check(!preg_match('/PHP Fatal error/',(string)file_get_contents($base.'/server.log')),'NO_FATAL_IN_SERVER_LOG');
proc_terminate($server);echo json_encode(['b1147Fixture'=>'PASS','cases'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
