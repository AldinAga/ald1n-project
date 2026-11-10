<?php
declare(strict_types=1);
/**
 * The real B1149 coordinator over the real staged 8531-file runtime (ef3f927) in the production topology (active tree with
 * .env and storage symlinks into a retained tree), after the B1145 unclamp, real MariaDB 10.11.19 and real Laravel served
 * by `php -S` with OPcache (validate_timestamps=1, revalidate_freq=2). The production history is replayed for real: the
 * REAL B1146 coordinator installs the legal overlay (shop closed, Report1109), the shop is turned ON (30 synthetic
 * products, one synthetic PNG logo), the REAL B1147 coordinator installs the catalog overlay (Report1112), the REAL B1148
 * coordinator installs the glass overlay (Report1113), then B1149 runs with the real payload taken from the built operator
 * ZIP, with the shop ON.
 * Explicit doubles: plain loopback HTTP instead of HTTPS, realpath_cache_ttl=10 on the test server (shorter hold), a fixed
 * app-local clock outside the backup window, the shop switch written directly into the fixture database, and one CI-only
 * POST /tema per smoke point (the shipped tool never POSTs).
 * Cases: refusal before B1148, route cache present (HOLD, never touched), unknown pre-state, preflight facts and PHP
 * extensions, shop OFF, census before the backup, nothing applied, time budget, missing JSON-LD and CSP rolled back, two
 * wrong-order negative controls (routes alone, the CSP middleware alone: every public page 500s), install with a smoke after
 * every step and every group (anonymous pages 200 throughout, the logo 404 until the route step then 200, the guest SMTP page
 * 404 until the route step then 302, POST /tema 303 throughout), the nonce'd theme script, Cache-Control private/no-store,
 * the sandboxed logo, gallery CSS, a mail-transport fingerprint change rolled back, idempotent re-run, --rollback in the
 * backup window and with --ignore-schedule, owner rollback with the shop ON and OFF, forced failure, 429
 * transient/persistent/resume/rollback, shop OFF during the acceptance, noindex missing in preflight/installed/rollback, Vary
 * hidden by the edge, SIGKILL in both rollback holds, a partial rollback with the manual block executed as pasted, SIGKILL
 * mid-copy, and stale backup copies after a kill.
 * Usage: php coordinator-fixture.php <b1149> <b1148> <b1147> <b1146> <b1145> <b1144> <b1134> <runtime stage> <payload dir> <runtime-directories.json> <b1146 zip> <b1147 zip> <b1148 zip> <b1149 zip>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$tools,$b1148,$b1147,$b1146,$b1145,$b1144,$b1134,$stage,$payloadDir,$directoryDoc,$zip1146,$zip1147,$zip1148,$zipPath]=$argv;
require $b1134.'/ReleasePrimitives.php';require $b1134.'/InstallFileOps.php';foreach(['LeanGates','FreshnessProbe','LeanWeb'] as $c)require $b1144.'/'.$c.'.php';require $b1145.'/EnvFlags.php';require $b1145.'/MigrateUnclampCoordinator.php';require $b1146.'/OverlayProbe.php';
foreach(['CatalogWeb','CatalogOverlayCoordinator','FixtureWeb'] as $c)require $b1147.'/'.$c.'.php';foreach(['GlassWeb','GlassOverlayCoordinator'] as $c)require $b1148.'/'.$c.'.php';foreach(['StorefrontWeb','StorefrontInstallCoordinator','FixtureWeb'] as $c)require $tools.'/'.$c.'.php';
const DB='ald1n_b1149_ci';const DB_PASSWORD='isolated-ci-only';const PORT=8149;
function check(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('B1149_FIXTURE_'.$name);}
function sh(array $args,?string $cwd=null):string{$p=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0)throw new RuntimeException('B1149_FIXTURE_COMMAND_'.basename($args[0]).'_RC'.$rc.':'.substr($err.$out,-900));return trim((string)$out);}
function lastLine(string $output,string $key):mixed{return preg_match_all('/^'.preg_quote($key,'/').'=(.+)$/m',$output,$m)?json_decode(end($m[1]),true,64,JSON_THROW_ON_ERROR):null;}
function lastRaw(string $output,string $key):?string{return preg_match_all('/^'.preg_quote($key,'/').'=(.*)$/m',$output,$m)?end($m[1]):null;}
function allLines(string $output,string $key):array{preg_match_all('/^'.preg_quote($key,'/').'=(.+)$/m',$output,$m);return array_map(static fn($x)=>json_decode($x,true,64,JSON_THROW_ON_ERROR),$m[1]);}
function rmTree(string $path):void{if(is_link($path)||is_file($path)){unlink($path);return;}if(!is_dir($path))return;foreach(scandir($path) as $n)if($n!=='.'&&$n!=='..')rmTree($path.'/'.$n);rmdir($path);}
function child(array $argv):array{$p=proc_open($argv,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return [proc_close($p),$out,$err];}

$pdo=static fn():PDO=>new PDO('mysql:host=127.0.0.1;port=3306;dbname='.DB,'root',DB_PASSWORD,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
check(preg_match('/^10\.11\.19-MariaDB/',(string)$pdo()->query('SELECT VERSION()')->fetchColumn())===1,'MARIADB_10_11_19');
$manifest=json_decode((string)file_get_contents($payloadDir.'/runtime-manifest.json'),true,512,JSON_THROW_ON_ERROR);$directories=json_decode((string)file_get_contents($directoryDoc),true,512,JSON_THROW_ON_ERROR)['directories'];check(count($manifest['files'])===8531,'MANIFEST_8531');

// Production topology: active tree from the pinned runtime; .env and storage are symlinks into the retained tree.
$base=getenv('RUNNER_TEMP').'/b1149';mkdir($base);$base=realpath($base);$home=$base.'/home';$root=$home.'/ald1n-project';foreach([$root.'/incoming',$root.'/apps/cms',$root.'/docs/operations',$home.'/cms-backups'] as $d)mkdir($d,0755,true);
$runtime=$home.'/.ald1n-b1134-retained/'.str_repeat('d',32);mkdir($runtime,0755,true);$retained=$runtime.'/retained';$active=$root.'/apps/cms/current';$candidate=$runtime.'/candidate';$old=$runtime.'/old';
InstallFileOps::candidate($stage,$candidate,$manifest,$directories);if(!is_dir($candidate.'/bootstrap/cache'))mkdir($candidate.'/bootstrap/cache',0755,true);
foreach(['storage/app/public','storage/logs','storage/framework/sessions','storage/framework/cache/data','storage/framework/views'] as $p)mkdir($old.'/'.$p,0755,true);
$envBytes=implode("\n",['APP_NAME=Ald1n','APP_ENV=production','APP_DEBUG=false','APP_KEY=base64:'.base64_encode(random_bytes(32)),'APP_URL=https://cms.ald1n.com','APP_TIMEZONE=Europe/Belgrade','APP_MAINTENANCE_DRIVER=file','DB_CONNECTION=mysql','DB_HOST=127.0.0.1','DB_PORT=3306','DB_DATABASE='.DB,'DB_USERNAME=root','DB_PASSWORD='.DB_PASSWORD,
    'SESSION_DRIVER=file','SESSION_SECURE_COOKIE=false','CACHE_STORE=file','QUEUE_CONNECTION=sync','MAIL_MAILER=array','LOG_CHANNEL=daily','LOG_LEVEL=debug','TURNSTILE_ENABLED=false','PULSE_ENABLED=false','TELESCOPE_ENABLED=false','MOBILE_PUSH_ENABLED=false','OPERATIONAL_EMAIL_NOTIFICATIONS=false',
    'LEGACY_DB_DATABASE=disabled','LEGACY_DB_USERNAME=disabled_qa_legacy','BACKUP_PATH='.$home.'/cms-backups','PUBLIC_STOREFRONT_ENABLED=true','PUBLIC_CHECKOUT_ENABLED=true','']);
file_put_contents($old.'/.env',$envBytes);chmod($old.'/.env',0640);
if(!is_dir($old.'/public'))mkdir($old.'/public',0755,true);check(copy($candidate.'/public/.htaccess',$old.'/public/.htaccess')&&chmod($old.'/public/.htaccess',0644),'OLD_TREE_HTACCESS');
ReleasePrimitives::preservePrivate($old,$candidate,$retained);rename($old,$retained);rename($candidate,$active);
check(is_link($active.'/.env')&&is_link($active.'/storage/logs'),'TOPOLOGY_SYMLINKS');check(hash_file('sha256',$active.'/config/public_storefront.php')===StorefrontInstallCoordinator::CONFIG_SHA,'CONFIG_IS_STAGED_ORIGINAL_AFTER_B1145');
$stageDir=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU';mkdir($stageDir.'/verification',0755,true);copy($payloadDir.'/runtime-manifest.json',$stageDir.'/verification/runtime-manifest.json');
$htaccess=hash_file('sha256',$active.'/public/.htaccess');
check($htaccess===hash_file('sha256',dirname($tools,3).'/apps/cms/current/public/.htaccess')&&str_contains(strtolower((string)(CatalogFixtureWeb::htaccessHeaders($active.'/public/.htaccess')['x-robots-tag']??'')),'noindex'),'ACTIVE_HTACCESS_IS_THE_REAL_ONE_WITH_X_ROBOTS_TAG_NOINDEX');
check(!isset(CatalogFixtureWeb::htaccessHeaders($active.'/public/.htaccess')['content-security-policy']),'HTACCESS_DOES_NOT_OVERRIDE_THE_APP_CSP');

// The B1145 terminal state and a Report1108-shaped terminal report (the predecessor of B1146).
$b1145Base=$home.'/.ald1n-b1145-state';mkdir($b1145Base,0700);$b1145Id=bin2hex(random_bytes(16));mkdir($b1145Base.'/'.$b1145Id,0700);
file_put_contents($b1145Base.'/'.$b1145Id.'/state.json',json_encode(['schemaVersion'=>1,'kind'=>'B1145','id'=>$b1145Id,'private'=>$b1145Base.'/'.$b1145Id,'phase'=>'CLOSED_UNCLAMPED','b1144'=>['retained'=>$retained,'htaccessSHA256'=>$htaccess]],JSON_UNESCAPED_SLASHES));
file_put_contents($b1145Base.'/current.json',json_encode(['schemaVersion'=>1,'kind'=>'B1145','private'=>$b1145Base.'/'.$b1145Id,'id'=>$b1145Id],JSON_UNESCAPED_SLASHES));
file_put_contents($root.'/docs/operations/1108-B1145-CONTROLLED-MIGRATE-UNCLAMP-20261108-150000.md',implode("\n",['B1145 fixture report','B1145_PHASE="CLOSED_UNCLAMPED"','B1145_FINAL=PASS_MIGRATED_UNCLAMPED_STOREFRONT_CLOSED','EXIT=0','']));
sh(['git','init','--quiet','-b','main'],$root);sh(['git','-c','user.name=Fixture','-c','user.email=fixture@example.invalid','commit','--allow-empty','-m','fixture','--quiet'],$root);

// All four operator packages exactly as the owner unzips them, plus the pinned helpers already on the host under their host names.
foreach([$zip1146,$zip1147,$zip1148,$zipPath] as $z)sh(['unzip','-q','-n',$z,'-d',$root]);$incoming=$root.'/incoming';
foreach(['b1134/InstallFileOps.php'=>'b1134-files.php','b1144/LeanGates.php'=>'b1144-gates.php','b1144/FreshnessProbe.php'=>'b1144-probe.php','b1144/LeanWeb.php'=>'b1144-web.php','b1145/EnvFlags.php'=>'b1145-env.php','b1145/AppState.php'=>'b1145-app-state.php','b1145/MigrateUnclampCoordinator.php'=>'b1145-coordinator.php'] as $from=>$to)copy(dirname($tools).'/'.$from,$incoming.'/'.$to);
$package=json_decode((string)file_get_contents($incoming.'/b1149-package.json'),true,64,JSON_THROW_ON_ERROR);foreach($package['files'] as $n=>$d)check(hash_file('sha256',$incoming.'/'.$n)===$d,'PACKAGE_FILE_'.$n);$plan=StorefrontInstallCoordinator::plan($package,$incoming);check(count($plan)===43,'PLAN_43');
// The B1148 helpers the B1149 package pins are the very bytes the B1148 ZIP delivered (the host copies must match), likewise B1147.
foreach(['b1146-probe.php','b1146-app-check.php','b1147-web.php','b1147-app-check.php','b1147-coordinator.php','b1148-web.php','b1148-app-check.php','b1148-coordinator.php'] as $n)check(isset($package['files'][$n]),'PINS_'.$n);
$pkg1148=json_decode((string)file_get_contents($incoming.'/b1148-package.json'),true,64,JSON_THROW_ON_ERROR);foreach(['b1148-web.php','b1148-app-check.php','b1148-coordinator.php','b1147-web.php','b1147-app-check.php','b1147-coordinator.php'] as $n)check($pkg1148['files'][$n]===$package['files'][$n],'B1148_HELPER_PIN_EQUALS_B1148_ZIP_'.$n);
$purge=(new ReflectionClass(StorefrontInstallCoordinator::class))->newInstanceWithoutConstructor();foreach(['cssVersion','oldCssVersion','galleryVersion'] as $k)(new ReflectionProperty($purge,$k))->setValue($purge,$package[$k]);$purgeStep=$purge->purgeStep();$purgeBack=$purge->purgeStep(true);
check(str_contains($purgeStep,'site-assets/*')&&str_contains($purgeStep,'?v='.$package['cssVersion'].'_')&&str_contains($purgeBack,'?v='.$package['oldCssVersion'].'_AND_'),'PURGE_STEPS_NAME_THE_URLS');
$markers=StorefrontInstallCoordinator::markers($package);

// Database after B1145: all 71 migrations ran, production roles, DB switch absent; one superadmin; the old-code approval signed.
$artisan=static fn(array $a):string=>sh(array_merge(['php','-d','opcache.enable=0',$active.'/artisan'],$a,['--no-interaction','--no-ansi']),$active);
check(count(glob($active.'/database/migrations/*.php'))===71,'SEVENTY_ONE_MIGRATION_FILES');$artisan(['migrate','--force']);$artisan(['db:seed','--class=Database\Seeders\CoreAccessSeeder','--force']);
$pdo()->exec("UPDATE roles SET name='Korisnik' WHERE slug='user'");
$roleId=(int)$pdo()->query("SELECT id FROM roles WHERE slug='superadmin'")->fetchColumn();check($roleId>0,'SUPERADMIN_ROLE');
$pdo()->prepare("INSERT INTO users (role_id,username,email,first_name,password_hash,status,created_at,updated_at) VALUES (?,?,?,?,?,'active',NOW(),NOW())")->execute([$roleId,'b1149-owner','b1149-owner@example.test','Fixture',password_hash('SyntheticOnly123!',PASSWORD_BCRYPT)]);$ownerId=(int)$pdo()->query("SELECT id FROM users WHERE username='b1149-owner'")->fetchColumn();check($ownerId>0,'OWNER_ROW');
$fingerprintScript=$base.'/fingerprint.php';file_put_contents($fingerprintScript,'<?php chdir($argv[1]);require "vendor/autoload.php";$app=require "bootstrap/app.php";$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();echo $app->make(App\Services\PublicMerchantPolicyService::class)->review()["fingerprint"];');
$pdo()->prepare("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('public_checkout_approval_digest',?,NOW(),NOW())")->execute([sh(['php','-d','opcache.enable=0',$fingerprintScript,$active])]);$artisan(['cache:clear']);

// Real Laravel behind php -S (cli-server SAPI) with OPcache on; requests carry Host: cms.ald1n.com.
$server=proc_open([PHP_BINARY,'-d','opcache.enable=1','-d','opcache.enable_cli=1','-d','opcache.validate_timestamps=1','-d','opcache.revalidate_freq=2','-d','opcache.file_update_protection=0','-d','realpath_cache_ttl=10','-S','127.0.0.1:'.PORT,'-t',$active.'/public',$active.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],[0=>['pipe','r'],1=>['file',$base.'/server.log','a'],2=>['file',$base.'/server.log','a']],$pipes,$active.'/public',['PATH'=>getenv('PATH'),'HOME'=>$home]);
$status=static fn(string $target):int=>StorefrontFixtureWeb::get(PORT,$target)['status'];$body=static fn(string $target,array $h=[]):string=>StorefrontFixtureWeb::get(PORT,$target,$h)['body'];
$themePost=static fn(string $form):array=>StorefrontFixtureWeb::get(PORT,'/tema',['Content-Type: application/x-www-form-urlencoded'],$form);
for($i=0;$i<60&&$status('/up')!==200;$i++)usleep(500000);check($status('/up')===200&&$status('/katalog')===404&&$status('/pravne-informacije')===404,'REAL_LARAVEL_SERVED_PRE_B1146');

$private=$home.'/.ald1n-b1149-state';$private1148=$home.'/.ald1n-b1148-state';$private1147=$home.'/.ald1n-b1147-state';$private1146=$home.'/.ald1n-b1146-state';$secrets=$base.'/probe-secrets';$faultLog=$base.'/faults.log';$stepLog=$base.'/steps.jsonl';$logDir=$retained.'/storage/logs';$configJSON=$base.'/run.json';$safeNow=strtotime('2026-11-11 13:00:00 UTC');
$switches=[];foreach(['dropCsp','dropJsonLd','dropVaryEdge','dropVaryAll','noHtaccess','rate429'] as $k)$switches[$k]=$base.'/switch-'.$k;$busyPidFile=$base.'/busy.pid';$allOutput='';file_put_contents($logDir.'/laravel-'.date('Y-m-d').'.log','',FILE_APPEND);
$common=['php'=>PHP_BINARY,'allowedSapis'=>['cli-server'],'censusSeconds'=>20,'minFreeBytes'=>268435456,'scheduleGate'=>true,'fixedNow'=>$safeNow,'incoming'=>$incoming,'port'=>PORT,'secrets'=>$secrets,'faultLog'=>$faultLog,'logDir'=>$logDir,'root'=>$root,'home'=>$home,'stage'=>$stageDir,'b1146'=>$b1146,'b1145'=>$b1145,'b1144'=>$b1144,'b1134'=>$b1134,'busyPidFile'=>$busyPidFile,'db'=>['mysql:host=127.0.0.1;port=3306;dbname='.DB,'root',DB_PASSWORD]];
$run=static function(array $actions=[],string $mode='install',array $overrides=[])use($configJSON,$tools,$b1148,$b1147,$private,$private1146,$private1147,$private1148,$incoming,$stepLog,$switches,$common,&$allOutput):array{
    file_put_contents($configJSON,json_encode($common+['overrides'=>$overrides,'switches'=>$switches,'tools'=>$tools,'b1148'=>$b1148,'b1147'=>$b1147,'base'=>$private,'pointer'=>$private.'/current.json','b1146Base'=>$private1146,'b1147Base'=>$private1147,'b1148Base'=>$private1148,
        'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','featureScript'=>$incoming.'/b1149-app-check.php','mode'=>$mode,'stepLog'=>$stepLog,'actions'=>$actions],JSON_UNESCAPED_SLASHES));
    [$rc,$out,$err]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$tools.'/fixture-run.php',$configJSON]);
    echo "----- B1149 RUN mode=$mode rc=$rc -----\n".$out.($err!==''?"\nSTDERR=".substr($err,-2000)."\n":'');$allOutput.=$out;return [$rc,$out];
};
$tree=static function()use($plan,$active):string{$pre=true;$inst=true;foreach($plan as $s){$t=$active.'/'.$s['path'];$sha=is_file($t)?hash_file('sha256',$t):null;$pre=$pre&&$sha===$s['preSHA256'];$inst=$inst&&$sha===$s['payloadSHA256'];}return $pre?'PRE':($inst?'INSTALLED':'MIXED');};
$routeCacheSHA=static function()use($active):?string{$files=glob($active.'/bootstrap/cache/routes-*.php')?:[];sort($files);if($files===[])return null;$h=[];foreach($files as $f)$h[]=basename($f).':'.hash_file('sha256',$f);return hash('sha256',implode("\n",$h));};
$results=[];

// 1. The real B1146 coordinator installs the legal overlay with the shop closed; its output becomes Report1109.
$cfg1146=$base.'/run1146.json';file_put_contents($cfg1146,json_encode(['overrides'=>[],'dropCsp'=>$base.'/drop-csp-1146','busyPidFile'=>$base.'/busy1146.pid','hiddenWeb'=>$base.'/web.hidden','tools'=>$b1146,'b1145'=>$b1145,'b1144'=>$b1144,'b1134'=>$b1134,'root'=>$root,'home'=>$home,'base'=>$private1146,'pointer'=>$private1146.'/current.json','stage'=>$stageDir,'b1145Base'=>$b1145Base,
    'appStateScript'=>$incoming.'/b1145-app-state.php','appCheckScript'=>$incoming.'/b1146-app-check.php','php'=>PHP_BINARY,'allowedSapis'=>['cli-server'],'censusSeconds'=>20,'minFreeBytes'=>268435456,'scheduleGate'=>true,'fixedNow'=>$safeNow,'incoming'=>$incoming,'mode'=>'install','port'=>PORT,'secrets'=>$secrets,'faultLog'=>$faultLog,'stepLog'=>$base.'/steps1146.jsonl','logDir'=>$logDir,'adminCookie'=>'','actions'=>[]],JSON_UNESCAPED_SLASHES));
[$rc,$o1146,$e1146]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$b1146.'/fixture-run.php',$cfg1146]);echo "----- B1146 RUN rc=$rc -----\n".$o1146.($e1146!==''?"\nSTDERR=".substr($e1146,-2000)."\n":'');
check($rc===0&&lastRaw($o1146,'B1146_FINAL')===CatalogOverlayCoordinator::B1146_PASS,'REAL_B1146_INSTALLED');
file_put_contents($root.'/docs/operations/1109-B1146-LEGAL-OVERLAY-20261108-160000.md',"B1146 fixture report\n".$o1146."EXIT=0\nREPORT=".$root."/docs/operations/1109-B1146-LEGAL-OVERLAY-20261108-160000.md\n");

// 2. The owner turns the shop ON (fixture: the switch row written directly); 30 synthetic products; one synthetic PNG logo.
$pdo()->exec("INSERT INTO brands (name,slug,status,created_at,updated_at) VALUES ('Lenovo','lenovo-b1149-ci','active',NOW(),NOW())");$brandId=(int)$pdo()->query("SELECT id FROM brands WHERE slug='lenovo-b1149-ci'")->fetchColumn();check($brandId>0,'BRAND_ROW');
$insert=$pdo()->prepare("INSERT INTO products (sku,slug,name,description,price_amount,price_currency,stock_quantity,status,brand_id,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,'active',?,?,NOW(),NOW())");
for($i=1;$i<=30;$i++)$insert->execute(['B1149-CI-'.$i,'b1149-ci-'.$i,'ThinkPad Synthetic '.$i,'Synthetic B1149 row',1000+97*$i,$i%3===0?'EUR':'RSD',$i%4,$i%2?$brandId:null,$ownerId]);
$pdo()->exec("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('eur_rsd_rate','117.2',NOW(),NOW()) ON DUPLICATE KEY UPDATE setting_value='117.2'");
$logoName='a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0.png';mkdir($retained.'/storage/app/public/site-assets',0755,true);$img=imagecreatetruecolor(96,96);imagefill($img,0,0,imagecolorallocate($img,18,90,200));imagepng($img,$retained.'/storage/app/public/site-assets/'.$logoName);
$pdo()->exec("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('site_logo_light_path','site-assets/".$logoName."',NOW(),NOW()) ON DUPLICATE KEY UPDATE setting_value='site-assets/".$logoName."'");
$shopOn=static function()use($pdo,$artisan):void{$pdo()->exec("DELETE FROM settings WHERE setting_key='public_storefront_open'");$pdo()->exec("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('public_storefront_open','1',NOW(),NOW())");$artisan(['cache:clear']);};
$shopOff=static function()use($pdo,$artisan):void{$pdo()->exec("DELETE FROM settings WHERE setting_key='public_storefront_open'");$artisan(['cache:clear']);};
$shopFlag=static fn():?string=>(($v=$pdo()->query("SELECT setting_value FROM settings WHERE setting_key='public_storefront_open'")->fetchColumn())===false?null:(string)$v);
$shopOn();sleep(3);check($status('/katalog')===200,'SHOP_ON_B1146_CATALOG_SERVED');

// 3. B1149 before B1147 and B1148 (no Report1113, the B1147 and B1148 files absent): HOLD before any change.
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&lastLine($o,'B1149_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('B1148_REPORT1113_MISSING',$p['blockers'],true)&&!str_contains($o,'OVERLAY_STEP=')&&!str_contains($o,'PER_FILE_BACKUP='),'REFUSED_BEFORE_B1148');rmTree($private);$results['beforeB1148']='HOLD_NOTHING_CHANGED';

// 4. The real B1147 coordinator installs the catalog overlay with the shop ON; its output becomes Report1112.
$cfg1147=$base.'/run1147.json';file_put_contents($cfg1147,json_encode($common+['overrides'=>[],'dropCsp'=>$base.'/drop-csp-1147','dropJsonLd'=>$base.'/drop-json-ld-1147','rate429'=>$base.'/rate-429-1147','noHtaccess'=>$base.'/no-htaccess-1147','tools'=>$b1147,'base'=>$private1147,'pointer'=>$private1147.'/current.json','b1146Base'=>$private1146,
    'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','catalogScript'=>$incoming.'/b1147-app-check.php','mode'=>'install','stepLog'=>$base.'/steps1147.jsonl','actions'=>[]],JSON_UNESCAPED_SLASHES));
[$rc,$o1147,$e1147]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$b1147.'/fixture-run.php',$cfg1147]);echo "----- B1147 RUN rc=$rc -----\n".$o1147.($e1147!==''?"\nSTDERR=".substr($e1147,-2000)."\n":'');
check($rc===0&&lastRaw($o1147,'B1147_FINAL')===GlassOverlayCoordinator::B1147_PASS,'REAL_B1147_INSTALLED');
file_put_contents($root.'/docs/operations/1112-B1147-CATALOG-OVERLAY-20261109-120000.md',"B1147 fixture report\n".$o1147."EXIT=0\n");
sleep(3);check($status('/katalog')===200&&$status('/tema')===404,'B1147_CATALOG_SERVED_WITHOUT_THE_THEME_ROUTE');

// 5. The real B1148 coordinator installs the glass overlay with the shop ON; its output becomes Report1113.
$cfg1148=$base.'/run1148.json';file_put_contents($cfg1148,json_encode($common+['overrides'=>[],'switches'=>$switches,'tools'=>$b1148,'b1147'=>$b1147,'base'=>$private1148,'pointer'=>$private1148.'/current.json','b1146Base'=>$private1146,'b1147Base'=>$private1147,
    'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','catalogScript'=>$incoming.'/b1147-app-check.php','glassScript'=>$incoming.'/b1148-app-check.php','mode'=>'install','stepLog'=>$base.'/steps1148.jsonl','actions'=>[]],JSON_UNESCAPED_SLASHES));
[$rc,$o1148,$e1148]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$b1148.'/fixture-run.php',$cfg1148]);echo "----- B1148 RUN rc=$rc -----\n".$o1148.($e1148!==''?"\nSTDERR=".substr($e1148,-2000)."\n":'');
check($rc===0&&lastRaw($o1148,'B1148_FINAL')===StorefrontInstallCoordinator::B1148_PASS,'REAL_B1148_INSTALLED');
$report1113=$root.'/docs/operations/1113-B1148-GLASS-OVERLAY-20261110-000906.md';file_put_contents($report1113,"B1148 fixture report\n".$o1148."EXIT=0\nREPORT=".$report1113."\n");
check(StorefrontInstallCoordinator::b1148Facts((string)file_get_contents($report1113))['phase']==='INSTALLED_PASS','REPORT1113_TERMINAL_PASS');
sleep(3);check($status('/katalog')===200&&str_contains($body('/katalog'),'class="shop-theme" method="post"')&&$status('/site-assets/'.$logoName)===404&&$status('/admin/settings/smtp')===404,'B1148_GLASS_SERVED_WITHOUT_THE_B1149_ROUTES');
$results['chain']=['b1146'=>'REAL_PASS_REPORT1109','b1147'=>'REAL_PASS_REPORT1112','b1148'=>'REAL_PASS_REPORT1113','shop'=>'ON'];
$envFile=$retained.'/.env';$envOriginal=(string)file_get_contents($envFile);

$reset=static function()use($private,$secrets,$faultLog,$stepLog,$logDir,$artisan,$tree,$status,$switches,$envFile,$envOriginal):void{
    rmTree($private);foreach(array_merge([$secrets,$faultLog,$stepLog],array_values($switches),glob($logDir.'/laravel*.log')?:[]) as $f)if(file_exists($f))unlink($f);file_put_contents($logDir.'/laravel-'.date('Y-m-d').'.log','');file_put_contents($envFile,$envOriginal);$artisan(['cache:clear']);
    check($tree()==='PRE','RESET_TREE_PRE');sleep(3);check($status('/login')===200,'RESET_WARM_LOGIN');
};
$allSteps=static function():array{$a=[];for($n=1;$n<=43;$n++)$a['AFTER_STEP_'.$n]=['stepSmoke'];foreach(['A','B','C','D','E','F','G','H'] as $g)$a['GROUP_DONE_'.$g]=['stepSmoke'];$a['AFTER_INVALIDATE']=['stepSmoke'];return $a;};
$stepRows=static fn():array=>array_map(static fn($l)=>json_decode($l,true),array_values(array_filter(explode("\n",(string)@file_get_contents($stepLog)))));
$prefix=static function(int $k)use($plan,$active):void{foreach($plan as $s){$t=$active.'/'.$s['path'];$sha=is_file($t)?hash_file('sha256',$t):null;check($sha===($s['n']<=$k?$s['payloadSHA256']:$s['preSHA256']),'PREFIX_'.$k.'_STEP_'.$s['n']);}};
$block=static function(string $o):array{check(preg_match("/^bash -e <<'B1149_RECOVERY'\n(.*?)\nB1149_RECOVERY$/ms",$o,$m)===1,'RECOVERY_BLOCK_PRESENT');return explode("\n",$m[1]);};
$kindOf=static function(string $l):?string{$last=preg_match("/'([^']*)'$/",$l,$m)?$m[1]:null;
    if(in_array($l,['set -euo pipefail','umask 022','echo B1149_MANUAL_RECOVERY_BLOCK_DONE'],true))return null;if(str_starts_with($l,'printf ')&&str_contains($l,'| sha256sum --check --strict --quiet -'))return 'verify';
    if(str_starts_with($l,'install -m ')&&str_contains($l,' -g '))return 'install:'.$last;if(str_starts_with($l,'rm -f -- '))return 'rm:'.$last;if(str_starts_with($l,'rmdir --ignore-fail-on-non-empty -- '))return 'rmdir';if(preg_match('/^sleep [0-9]+$/D',$l))return 'sleep';if(str_contains($l,' artisan view:clear --no-interaction'))return 'view';return 'other';};
$runBlock=static function(array $lines):array{$p=proc_open(['bash','-e'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fwrite($pipes[0],implode("\n",$lines)."\n");fclose($pipes[0]);$out=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return ['rc'=>proc_close($p),'out'=>$out];};
$conflictNow=(new DateTimeImmutable('2026-11-11 02:10:00',new DateTimeZone('Europe/Belgrade')))->getTimestamp();
$smokeRows=static function(string $o):array{$s=lastLine($o,'PUBLIC_HTTP_SMOKES');$by=[];foreach($s['rows'] as $r)$by[$r['check']]=$r;return [$s,$by];};
$rollbackOk=static function(string $what)use($run,$tree,$reset):void{[$rc,$o]=$run([],'rollback');check($rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE','CLEANUP_ROLLBACK_'.$what);$reset();};
$cssV=StorefrontInstallCoordinator::CSS_PATH.'?v='.$package['cssVersion'];$galV=StorefrontInstallCoordinator::GALLERY_CSS_PATH.'?v='.$package['galleryVersion'];
$stepOf=static function(string $path)use($plan):array{foreach($plan as $s)if($s['path']===$path)return $s;throw new RuntimeException('B1149_FIXTURE_STEP_UNKNOWN');};
$reset();

// 6. A route cache present: the new routes would be invisible, so the preflight HOLDs; the cache is never touched.
$artisan(['route:cache']);$cacheBefore=$routeCacheSHA();check($cacheBefore!==null,'ROUTE_CACHE_CREATED');$reset();
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');
check($rc===2&&lastLine($o,'B1149_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('ROUTE_CACHE_PRESENT_THE_NEW_ROUTES_WOULD_BE_INVISIBLE_NOTHING_CHANGED',$p['blockers'],true)&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE'&&$routeCacheSHA()===$cacheBefore,'ROUTE_CACHE_PRESENT_HOLD_UNTOUCHED');
$artisan(['route:clear']);check($routeCacheSHA()===null,'FIXTURE_ROUTE_CACHE_REMOVED');$reset();$results['routeCachePresent']='HOLD_NOTHING_CHANGED_CACHE_BYTES_UNCHANGED';

// 7. Refusal: an unknown pre-state of one replaced file stops the run before any change.
$ctl=$active.'/app/Services/SettingsService.php';$original=file_get_contents($ctl);file_put_contents($ctl,$original."\n// foreign local edit\n");
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&lastLine($o,'B1149_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('UNKNOWN_PRE_STATE_REFUSED',$p['blockers'],true)&&!str_contains($o,'OVERLAY_STEP='),'UNKNOWN_PRE_STATE_REFUSED');
check(file_get_contents($ctl)===$original."\n// foreign local edit\n"&&!is_file($active.'/app/Services/SvgSanitizer.php'),'UNKNOWN_PRE_STATE_NOTHING_CHANGED');file_put_contents($ctl,$original);$reset();$results['unknownPreState']='HOLD_NOTHING_CHANGED';

// 8. Preflight facts with the old tree: the new classes, routes and provider absent, the B1148 theme route present, the PHP
// extensions and the mail fingerprint read; the guest SMTP page 404 and the old CSP shape.
[$rc,$o]=$run(['AFTER_PREFLIGHT'=>['fail']]);$ft=allLines($o,'FEATURE_CLI_CHECK')[0]??null;$en=allLines($o,'ENVIRONMENT_CLI_CHECK')[0]??null;
check(is_array($ft)&&$ft['newClassesAutoloaded']===false&&$ft['routeSiteAssets']===false&&$ft['routeSmtpIndex']===false&&$ft['providerRegistered']===false&&$ft['themeRoutePostOnly']===true&&$ft['routeList']['public-shop.theme']===1&&$ft['routesCached']===false,'OLD_STATE_FEATURES_ABSENT');
check(is_array($en)&&$en['extensions']['dom']===true&&$en['extensions']['libxml']===true&&$en['extensions']['fileinfo']===true&&$en['extensions']['mbstring']===true&&($en['extensions']['gd']===true||$en['extensions']['imagick']===true)&&$en['appUrlHttpsOrigin']===true&&preg_match('/^[a-f0-9]{64}$/D',$en['mailFingerprintSHA256'])===1,'ENVIRONMENT_FACTS');
[$s,$by]=$smokeRows($o);check($s['state']==='OLD'&&$by['/admin/settings/smtp']['observed']===404&&$by[StorefrontInstallCoordinator::GALLERY_CSS_PATH]['observed']===404&&$by['/katalog']['cspShape']===true&&$by['/katalog']['cacheControlPrivateNoStore']===false&&$by['/katalog']['galleryLink']===false,'OLD_STATE_SMOKES_SHAPE');
check($tree()==='PRE'&&!str_contains($o,'OVERLAY_STEP='),'PREFLIGHT_FACTS_NOTHING_CHANGED');$reset();$results['preflightFacts']=['newFeatures'=>'ABSENT','extensions'=>'DOM_LIBXML_FILEINFO_MBSTRING_GD_OR_IMAGICK','htaccessCsp'=>'NONE'];

// 9. With the shop OFF a fresh install HOLDs; the switch is never written.
$shopOff();[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&in_array('SHOP_IS_OFF_B1149_INSTALLS_ONLY_WITH_THE_SHOP_ON_NOTHING_CHANGED',$p['blockers'],true)&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE'&&$shopFlag()===null,'SHOP_OFF_INSTALL_REFUSED');$shopOn();$reset();$results['shopOffInstall']='HOLD_NOTHING_CHANGED';

// 10. An artisan run that starts after the preflight is seen by the census BEFORE the backup; nothing is written.
[$rc,$o]=$run(['AFTER_PREFLIGHT'=>['busy']]);$pid=(int)trim((string)@file_get_contents($busyPidFile));if($pid>0){@posix_kill(-$pid,9);@posix_kill($pid,9);}shell_exec("pkill -9 -f 'sleep 40; true' || true");@unlink($busyPidFile);
check($rc===2&&lastLine($o,'B1149_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&str_contains($o,'"blockers":["CMS_ARTISAN_RUNNING"]')&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','CENSUS_BEFORE_BACKUP');$reset();
// A failure before the first file: "nothing applied", no view or web work.
[$rc,$o]=$run(['BEFORE_STEP_1'=>['fail']]);$tail=substr($o,(int)strpos($o,'OVERLAY_ROLLBACK='));
check($rc===2&&lastRaw($o,'B1149_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&lastLine($o,'ROLLBACK_FILES')['nothingApplied']===true&&!str_contains($tail,'ARTISAN=')&&!str_contains($tail,'WEB_OVERLAY_PROBE=')&&$tree()==='PRE','NOTHING_APPLIED_NO_REFRESH');$reset();
// The time budget is checked right before the first change.
[$rc,$o]=$run([],'install',['budgetSeconds'=>0]);check($rc===2&&str_contains($o,'"blockers":["TIME_BUDGET_EXCEEDED_BEFORE_FIRST_CHANGE"]')&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','TIME_BUDGET_HOLD');$reset();
// A catalog page without its JSON-LD block, and one without its CSP: acceptance fails, rolled back.
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['dropJsonLd'],'BEFORE_SMOKES_OLD'=>['clearDrops']]);[$s,$by]=$smokeRows(substr($o,0,(int)strpos($o,'OVERLAY_ROLLBACK=')));
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"INSTALLED_SMOKE_FAILED"'&&$by['/katalog']['jsonLd']==='JSON_LD_MISSING'&&lastRaw($o,'B1149_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE','MISSING_JSON_LD_ROLLED_BACK');$reset();
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['dropCsp'],'BEFORE_SMOKES_OLD'=>['clearDrops']]);
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"INSTALLED_SMOKE_FAILED"'&&lastRaw($o,'B1149_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE','MISSING_CSP_ROLLED_BACK');$reset();
$results+=['censusBeforeBackup'=>'HOLD_NOTHING_WRITTEN','nothingApplied'=>'NO_REFRESH_NO_WEB_WORK','timeBudget'=>'HOLD','missingJsonLd'=>'ROLLED_BACK','missingCsp'=>'ROLLED_BACK'];

// 11. Wrong-order negative controls (fixture only, never the tool): routes/web.php alone needs SiteLogoAssetService::FILE_PATTERN
// and PublicNoScriptCsp alone needs PublicStorefrontAppearance::staleThemeCookie; both take the public pages down. This is what
// the order prevents.
$wrong=[];
foreach(['routes/web.php'=>'routes','app/Http/Middleware/PublicNoScriptCsp.php'=>'csp'] as $path=>$key){
    $s=$stepOf($path);$file=$active.'/'.$path;$orig=(string)file_get_contents($file);file_put_contents($file,(string)file_get_contents($s['payload']));$artisan(['view:clear']);sleep(4);
    $wrong[$key]=['katalog'=>$status('/katalog'),'legal'=>$status('/pravne-informacije')];file_put_contents($file,$orig);$artisan(['view:clear']);sleep(4);
    check($wrong[$key]['katalog']===500&&$wrong[$key]['legal']===500,'WRONG_ORDER_'.strtoupper($key).'_FIRST_BREAKS_PUBLIC_PAGES:'.json_encode($wrong[$key]));check($status('/katalog')===200&&$status('/pravne-informacije')===200&&$tree()==='PRE','WRONG_ORDER_CONTROL_RESTORED_'.$key);
}
$reset();$results['wrongOrderControls']=$wrong+['meaning'=>'THE_STEP_SMOKES_BELOW_CAN_FAIL'];

// 12. Install with a smoke after every step and every group boundary.
[$rc,$o]=$run($allSteps());$rows=$stepRows();echo 'STEP_SMOKES='.json_encode($rows,JSON_UNESCAPED_SLASHES).PHP_EOL;
check($rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_INSTALLED_STOREFRONT_ROLLOUT_SHOP_STATE_UNCHANGED'&&$tree()==='INSTALLED','INSTALL_PASS');
$points=array_column($rows,'point');check(count($rows)===52&&count(array_filter($rows,static fn($r)=>array_key_exists('themePost',$r)))===9&&$points[0]==='AFTER_STEP_1'&&end($points)==='AFTER_INVALIDATE','EVERY_STEP_AND_GROUP_SMOKED:'.implode(',',$points));
$routeStep=$stepOf('routes/web.php')['n'];$bad=[];$routeFiles=false;$routeSettled=false;
// The web workers revalidate every few seconds: right after the routes step the new route table may or may not be seen yet
// (404 or 302), from the group boundary after it (pause >= revalidate_freq + 1) it must be. The logo is linked only once the
// route exists (assetUrl falls back to the legacy URL otherwise), and a linked logo is always served.
foreach($rows as $r){
    foreach($r['anon'] as $p=>$st)if($st!==200)$bad[]=$r['point'].' '.$p.' '.$st;
    if($r['point']==='AFTER_STEP_'.$routeStep)$routeFiles=true;if($r['point']==='GROUP_DONE_E')$routeSettled=true;
    if(array_key_exists('themePost',$r)&&($r['themePost']!==303||$r['themeCookie']!==true))$bad[]=$r['point'].' POST /tema '.$r['themePost'];
    if($r['logoLinked']===true&&$r['logoStatus']!==200)$bad[]=$r['point'].' linked logo '.var_export($r['logoStatus'],true);
    if(!$routeFiles&&($r['smtpGuest']!==404||$r['logoLinked']===true))$bad[]=$r['point'].' new route seen before the route step: smtp '.$r['smtpGuest'];
    if($routeFiles&&!in_array($r['smtpGuest'],[404,302],true))$bad[]=$r['point'].' guest SMTP page '.$r['smtpGuest'];
    if($routeSettled&&$r['smtpGuest']!==302)$bad[]=$r['point'].' guest SMTP page '.$r['smtpGuest'].' after the settled route group';
}
check($bad===[],'INSTALL_ORDER_EVERY_POINT_SERVES:'.implode(',',$bad));
check(array_column(array_filter($rows,static fn($r)=>$r['point']==='GROUP_DONE_D'),'smtpGuest')===[404]&&array_column(array_filter($rows,static fn($r)=>$r['point']==='GROUP_DONE_E'),'smtpGuest')===[302],'NO_ROUTE_BEFORE_GROUP_E');
check(end($rows)['logoLinked']===true&&end($rows)['logoStatus']===200,'LOGO_LINKED_AND_SERVED_AT_THE_END');
[$s,$by]=$smokeRows($o);check($s['state']==='INSTALLED'&&$s['failed']===[]&&$s['shop']['open']===true&&$s['varyUnverified']===false&&$s['notices']===[]&&$s['requestsWrite']==='NONE_GET_AND_HEAD_ONLY'&&$s['login']==='NEVER_LOGGED_IN','SMOKES_PASS');
check($by['/katalog']['jsonLd']==='PASS'&&$by['/katalog']['layoutMarker']===true&&$by['/katalog']['galleryLink']===true&&$by['/katalog']['cspShape']===true&&$by['/katalog']['cacheControlPrivateNoStore']===true&&$by['/katalog']['varyCookie']===true&&$by['/katalog']['noindex']===true&&$by['/katalog?page=999']['observed']===404&&$by['/katalog?sort=price_asc']['observed']===200&&in_array($by['/katalog?sort=ald1n_unknown']['observed'],[302,422],true)&&$by['/korpa']['layoutMarker']===true&&$by['/registracija']['observed']===200,'CATALOG_SMOKES');
check($by['GET /tema']['observed']===405&&$by['HEAD /tema']['observed']===405&&$by[StorefrontInstallCoordinator::CSS_PATH]['cssMarker']===true&&$by[$cssV]['result']==='PASS'&&$by[$galV]['result']==='PASS'&&$by[StorefrontInstallCoordinator::GALLERY_CSS_PATH]['result']==='PASS','CSS_AND_THEME_ROUTE_SMOKES');
check($by['PRODUCT_PAGE_FIRST_CATALOG_LINK']['result']==='PASS'&&$by['PRODUCT_PAGE_FIRST_CATALOG_LINK']['galleryLink']===true&&$by['PRODUCT_PAGE_FIRST_CATALOG_LINK']['cacheControlPrivateNoStore']===true&&$by['PRODUCT_PAGE_FIRST_CATALOG_LINK']['cspShape']===true,'PRODUCT_SMOKE');
check($by['SITE_ASSET_LOGO']['result']==='PASS'&&$by['SITE_ASSET_LOGO']['nosniff']===true&&$by['SITE_ASSET_LOGO']['sandboxCsp']===true&&$by['SITE_ASSET_LOGO']['contentTypeImage']===true&&$by['SITE_ASSET_LOGO']['noindex']===true,'LOGO_NOSNIFF_SANDBOX_NOINDEX');
check($by['/admin/settings/smtp']['result']==='PASS'&&$by['/admin/settings/smtp']['observed']===302&&$by['/admin/settings/smtp']['location']==='LOGIN','GUEST_SMTP_PAGE_REDIRECTS_TO_LOGIN');
check(count(array_filter($s['rows'],static fn($r)=>in_array($r['check'],StorefrontInstallCoordinator::LEGAL,true)&&$r['result']==='PASS'&&$r['layoutMarker']===true&&$r['cspShape']===true&&$r['noindex']===true))===8&&$s['noindexMissing']===[],'LEGAL_200_NEW_LAYOUT_NONCE_SCRIPT_NOINDEX');
// The real CSP and the nonce, as a browser gets them (CI only): one nonce in the header equals the nonce on the single script.
$cat=StorefrontFixtureWeb::get(PORT,'/katalog');check(StorefrontWeb::nonceScript($cat['csp'],$cat['body'])&&preg_match("/script-src 'nonce-[A-Za-z0-9+\/]{22}=='; script-src-attr 'none'/",implode(' ',$cat['csp']))===1,'NONCE_SCRIPT_CSP_AS_SERVED');
$cat2=StorefrontFixtureWeb::get(PORT,'/katalog');preg_match("/script-src 'nonce-([^']+)'/",implode(' ',$cat['csp']),$n1);preg_match("/script-src 'nonce-([^']+)'/",implode(' ',$cat2['csp']),$n2);check($n1[1]!==$n2[1],'NONCE_DIFFERS_PER_REQUEST');
$fe=lastLine($o,'FEATURE_CLI_CHECK');check($fe['expectInstalled']===true&&$fe['newClassesAutoloaded']===true&&$fe['changedStaticApis']===true&&$fe['routeSiteAssets']===true&&$fe['routeSmtpIndex']===true&&$fe['providerRegistered']===true&&$fe['themeRoutePostOnly']===true&&$fe['routeList']['site-assets.show']===1&&$fe['routeList']['admin.settings.smtp.index']===1&&$fe['routesCached']===false,'CLI_FEATURES_AFTER_INSTALL:'.json_encode($fe));
$mf=allLines($o,'MAIL_TRANSPORT_FINGERPRINT');check($mf!==[]&&end($mf)['unchanged']===true&&end($mf)['valuesPrinted']===false&&end($mf)['testMailSent']===false,'MAIL_TRANSPORT_FINGERPRINT_UNCHANGED');
check($routeCacheSHA()===null&&$shopFlag()==='1'&&!str_contains($o,'globalReset":true')&&str_contains($o,'"label":"installed-invalidate"')&&str_contains($o,'"partitions":2'),'NO_ROUTE_CACHE_SHOP_UNTOUCHED_TARGETED_INVALIDATION_TWO_PARTITIONS');
$next=(array)lastLine($o,'OWNER_NEXT_STEPS');check(count($next)===7&&$next[0]===$purgeStep&&str_contains((string)$next[1],'app:product-image-optimize')&&str_contains((string)$next[3],'JPG_OR_PNG')&&str_starts_with((string)$next[6],'ROLLBACK_IF_NEEDED=bash /home/icaffeco/ald1n-project/incoming/b1149-installer.sh --rollback'),'OWNER_NEXT_STEPS_COMPLETE');
check(str_contains($o,'"command":"route:clear"')&&str_contains($o,'"command":"queue:restart"')&&str_contains($o,'"label":"before-views"')&&strpos($o,'"label":"before-views"')<strpos($o,'"n":33,"group":"G"')&&(lastLine($o,'MERCHANT_APPROVAL')['ok']??null)==='UNCHANGED'&&str_contains($o,'"database":"NO_PRODUCT_CART_OR_SWITCH_WRITES_SESSIONS_AND_APP_CACHE_ONLY"'),'VIEWS_AFTER_MEASURED_HOLD_APPROVAL_UNCHANGED');
// The logo for real (CI only): 200, the PNG bytes, nosniff, sandbox CSP; a malformed name 404; a guest never sees the SMTP page.
$lg=StorefrontFixtureWeb::get(PORT,'/site-assets/'.$logoName);check($lg['status']===200&&str_starts_with((string)$lg['contentType'],'image/png')&&StorefrontWeb::nosniff($lg['nosniff'])&&StorefrontWeb::sandboxCsp($lg['csp']),'SITE_ASSET_SERVED_SANDBOXED');
check($status('/site-assets/not-a-valid-name.png')===404&&$status('/site-assets/..%2F..%2F.env')===404,'SITE_ASSET_STRICT_NAMES');
$t=$themePost('theme=dark&back=%2Fkatalog');check($t['status']===303&&$t['location']==='/katalog'&&isset($t['cookies']['shop_theme']),'THEME_POST_303_COOKIE');
check(str_contains($body('/katalog',['Cookie: shop_theme='.$t['cookies']['shop_theme']]),'data-theme="dark"')&&str_contains($body('/katalog'),'data-theme="system"'),'THEME_COOKIE_RENDERS_DATA_THEME');
check(str_contains($body($cssV),$markers['css'])&&str_contains($body($galV),$markers['gallery']),'NEW_CSS_SERVED');
$results['install']=['final'=>lastRaw($o,'B1149_FINAL'),'stepSmokePoints'=>count($rows),'logoRoute'=>'404_UNTIL_THE_ROUTE_STEP_THEN_200','guestSmtp'=>'404_UNTIL_THE_ROUTE_STEP_THEN_302','themePost'=>'303_THROUGHOUT','nonceScript'=>true,'mailFingerprint'=>'UNCHANGED','probePartitions'=>2];

// 13. Idempotent re-run.
[$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_ALREADY_INSTALLED'&&!str_contains($o,'OVERLAY_STEP=')&&$tree()==='INSTALLED','IDEMPOTENT_RERUN');$results['rerun']='PASS_ALREADY_INSTALLED';

// 14. 20 minutes before the 02:30 backup --rollback holds before any restore and prints the manual-only recovery block;
// with --ignore-schedule it restores (shop ON): exact old bytes, the B1148 storefront, the new routes gone.
[$rc,$o]=$run([],'rollback',['fixedNow'=>$conflictNow]);$note=lastLine($o,'RECOVERY_NOTE');
check($rc===2&&preg_match('/"blockers":\["SCHEDULED_BACKUP_WITHIN_30_MINUTES_[A-Z_]+_WAIT_OR_RUN_WITH_--rollback_--ignore-schedule"\]/',$o)===1&&!str_contains($o,'OVERLAY_ROLLBACK=')&&$note['kind']==='ROLLBACK_NOT_STARTED_MANUAL_ONLY'&&$block($o)!==[]&&$tree()==='INSTALLED','SCHEDULE_HOLD_WITH_MANUAL_BLOCK');
[$rc,$o]=$run([],'rollback',['fixedNow'=>$conflictNow,'ignoreSchedule'=>true]);
check(str_contains($o,'SCHEDULED_BACKUP_CONFLICT_IGNORED_BY_THE_OWNER_OVERRIDE')&&$rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE'&&!is_file($active.'/app/Services/SvgSanitizer.php')&&!is_file($active.'/public/assets/css/ald1n-public-gallery.css')&&$shopFlag()==='1','OWNER_ROLLBACK_IGNORE_SCHEDULE');
$pG=strpos($o,'"label":"rollback-after-views"');$pF=strpos($o,'"before":"E"');$pA=strpos($o,'"label":"rollback-before-new-classes"');$pV=strpos($o,'"viewsClearedFirst":true');$pH=strpos($o,'"before":"G"');
check($pH!==false&&$pV!==false&&$pG!==false&&$pF!==false&&$pA!==false&&$pH<$pV&&$pV<$pG&&$pG<$pF&&$pF<$pA&&(lastLine($o,'MERCHANT_APPROVAL')['ok']??null)==='UNCHANGED','ROLLBACK_ORDER_PROVIDERS_VIEWS_CLEAR_HOLD_ROUTES_HOLD_CLASSES');
check(in_array($purgeBack,(array)lastLine($o,'OWNER_NEXT_STEPS'),true),'CLOUDFLARE_PURGE_AFTER_ROLLBACK');
[$s,$by]=$smokeRows($o);$fe=lastLine($o,'FEATURE_CLI_CHECK');check($s['state']==='OLD'&&$s['shop']['open']===true&&$by['/katalog']['layoutMarker']===false&&$by['/admin/settings/smtp']['observed']===404&&$by['GET /tema']['observed']===405&&$fe['providerRegistered']===false&&$fe['routeSiteAssets']===false&&$fe['themeRoutePostOnly']===true,'OLD_STATE_SERVED');
sleep(3);check($status('/katalog')===200&&$themePost('theme=dark&back=%2Fkatalog')['status']===303&&$status('/site-assets/'.$logoName)===404,'OLD_STATE_THEME_ROUTE_KEPT_NEW_ROUTES_GONE');
$results['rollbackInScheduleWindow']='HOLD_MANUAL_ONLY_BLOCK_THEN_IGNORE_SCHEDULE_PASS';$reset();

// 14b. A liveness smoke after a group that fails (the shop pages lose their noindex header after group B): the install is rolled back at once.
[$rc,$o]=$run(['GROUP_DONE_B'=>['noHtaccess']]);$ls=allLines($o,'GROUP_LIVENESS_SMOKE');
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"GROUP_SMOKE_FAILED_AFTER_B"'&&count($ls)===2&&$ls[0]['after']==='A'&&$ls[0]['failed']===[]&&$ls[1]['after']==='B'&&in_array('/katalog',$ls[1]['failed'],true)&&lastRaw($o,'B1149_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE','MID_INSTALL_LIVENESS_FAILURE_ROLLED_BACK');
@unlink($switches['noHtaccess']);$reset();$results['groupLiveness']='PASS_AFTER_EVERY_GROUP_AND_A_FAILURE_AFTER_GROUP_B_ROLLED_BACK';
// 15. Forced acceptance failure (a new ERROR line in laravel.log): automatic rollback to the verified old state.
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['logError']]);check($rc===2&&lastRaw($o,'B1149_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"NEW_LARAVEL_LOG_ERRORS_SINCE_INSTALL"'&&$tree()==='PRE'&&lastLine($o,'B1149_PHASE')==='ROLLED_BACK','FORCED_FAILURE_ROLLED_BACK');
check($status('/katalog')===200&&$status('/pravne-informacije')===200,'FORCED_FAILURE_OLD_SERVED');$results['forcedFailure']='ROLLED_BACK';
// The effective mail transport changes during the acceptance (.env default mailer array -> log): rolled back; the owner is told.
[$rc,$o]=$run(['AFTER_SMOKES_INSTALLED'=>['mailChange']]);
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"MAIL_TRANSPORT_FINGERPRINT_CHANGED"'&&lastRaw($o,'B1149_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&str_contains($o,'OWNER_NOTICE_MAIL_FINGERPRINT="MAIL_TRANSPORT_FINGERPRINT_CHANGED"'),'MAIL_TRANSPORT_CHANGE_ROLLED_BACK');$reset();$results['mailTransportChange']='ROLLED_BACK';
// The next run after ROLLED_BACK installs again; the owner then rolls back with the shop OFF (closed-shop smokes).
[$rc,$o]=$run();check($rc===0&&$tree()==='INSTALLED','REINSTALL_AFTER_ROLLBACK');$shopOff();
[$rc,$o]=$run([],'rollback');[$s,$by]=$smokeRows($o);check($rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE'&&$s['shop']['open']===false&&$by['/katalog']['observed']===404&&$by['/pravne-informacije']['layoutMarker']===false&&$shopFlag()===null,'ROLLBACK_WITH_SHOP_OFF');$shopOn();$reset();
$results['rollbackShopOff']='PASS_ROLLED_BACK_BY_OWNER_SWITCH_NOT_WRITTEN';

// 16. 429 from the public-shop-browse throttle (shared edge IP).
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['rateLimit2']]);[$s,$by]=$smokeRows($o);
check($rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_INSTALLED_STOREFRONT_ROLLOUT_SHOP_STATE_UNCHANGED'&&$by['/pravne-informacije']['result']==='PASS'&&$by['/pravne-informacije']['attempts']===3&&$by['/katalog']['result']==='PASS','TRANSIENT_429_WAITED_OUT');
@unlink($switches['rate429']);$rollbackOk('TRANSIENT_429');
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['rateLimitAlways']]);
check($rc===2&&lastRaw($o,'B1149_FINAL')==='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED'&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"SMOKES_RATE_LIMITED_429"'&&str_contains($o,'SMOKES_RATE_LIMITED_PAUSE=')&&!str_contains($o,'OVERLAY_ROLLBACK=')&&lastLine($o,'B1149_PHASE')==='ACCEPTANCE_NOT_VERIFIED'&&$tree()==='INSTALLED'&&str_contains($o,'ACTIVE_TREE_INSTALLED_UNVERIFIED='),'PERSISTENT_429_HOLD_NO_ROLLBACK');
// The per-batch cap and the shared-limiter skip: the first throttled GET (a legal page) waits Retry-After twice (3 + 3 s
// within the 7 s budget) and stops at attempt 3; every later throttled GET is skipped without a request; /login, /up,
// the CSS, /tema and the guest SMTP page are still fetched.
[$s,$by]=$smokeRows($o);$skipped=array_filter($s['rows'],static fn($r)=>($r['skippedSharedLimiter']??false)===true&&$r['attempts']===0);
check($by['/pravne-informacije']['attempts']===3&&$by['/pravne-informacije']['waitedSeconds']===6&&count($skipped)===14&&$s['rateLimitWaitedSeconds']===6&&$by['/login']['result']==='PASS'&&$by[StorefrontInstallCoordinator::CSS_PATH]['result']==='PASS'&&$by['GET /tema']['result']==='PASS'&&$by['/admin/settings/smtp']['result']==='PASS','RATE_LIMIT_CAP_AND_SHARED_KEY_SKIP');
@unlink($switches['rate429']);
[$rc,$o]=$run();check($rc===0&&str_contains($o,'RESUMED_ACCEPTANCE_ONLY=')&&!str_contains($o,'OVERLAY_STEP=')&&lastRaw($o,'B1149_FINAL')==='PASS_INSTALLED_STOREFRONT_ROLLOUT_SHOP_STATE_UNCHANGED'&&$tree()==='INSTALLED','RESUMED_ACCEPTANCE_PASS');
[$rc,$o]=$run(['BEFORE_SMOKES_OLD'=>['rateLimitAlways']],'rollback');
check($rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&str_contains($o,'OWNER_NOTICE_RATE_LIMITED=')&&lastLine($o,'B1149_PHASE')==='ROLLED_BACK'&&$tree()==='PRE','ROLLBACK_429_NOT_INCOMPLETE');@unlink($switches['rate429']);$reset();
$results['rateLimit429']=['transient'=>'WAITED_OUT_PASS','persistentInstall'=>'HOLD_FILES_KEPT_THEN_RESUMED_ACCEPTANCE_PASS','persistentRollback'=>'PASS_ROLLED_BACK_WITH_OWNER_NOTICE'];

// 17. The owner turns the shop OFF in the middle of the acceptance batch: HOLD with the exact files, never a rollback.
[$rc,$o]=$run(['DURING_SMOKES_INSTALLED'=>['shopOff']]);
check($rc===2&&lastRaw($o,'B1149_FINAL')==='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED'&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"SHOP_STATE_CHANGED_DURING_ACCEPTANCE_STOREFRONT_NOT_VERIFIED"'&&$tree()==='INSTALLED'&&$shopFlag()===null,'SHOP_OFF_DURING_ACCEPTANCE_HOLD');
[$rc,$o]=$run();check($rc===2&&str_contains($o,'HOLD_ACCEPTANCE_NOT_RESUMED=')&&$tree()==='INSTALLED'&&$shopFlag()===null,'RESUME_REFUSED_WHILE_SHOP_OFF');
$shopOn();[$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_INSTALLED_STOREFRONT_ROLLOUT_SHOP_STATE_UNCHANGED','RESUMED_AFTER_SHOP_ON');$rollbackOk('SHOP_OFF_CASE');
$results['shopOffDuringAcceptance']='HOLD_NO_PASS_CLAIM_RESUMED_AFTER_SHOP_ON';

// 18. Owner policy, no indexing: the server-wide .htaccess header removed.
touch($switches['noHtaccess']);[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');[$s,$by]=$smokeRows($o);
check($rc===2&&lastLine($o,'B1149_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES',$p['blockers'],true)&&$s['failed']===[]&&$by['/pravne-informacije']['result']==='FAIL_NOINDEX_POLICY_HEADER_MISSING'&&array_diff(StorefrontInstallCoordinator::LEGAL,$s['noindexMissing'])===[]&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','NOINDEX_MISSING_PREFLIGHT_HOLD');unlink($switches['noHtaccess']);$reset();
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['noHtaccess']]);[$s,$by]=$smokeRows($o);
check($rc===2&&lastRaw($o,'B1149_FINAL')==='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED'&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES"'&&$s['failed']===[]&&array_diff(StorefrontInstallCoordinator::LEGAL,$s['noindexMissing'])===[]&&in_array('SITE_ASSET_LOGO',$s['noindexMissing'],true)&&!str_contains($o,'OVERLAY_ROLLBACK=')&&str_contains((string)(lastLine($o,'OWNER_NEXT_STEPS')[0]??''),'X-ROBOTS-TAG_NOINDEX')&&$tree()==='INSTALLED','NOINDEX_MISSING_INSTALLED_HOLD_FILES_KEPT');
[$rc,$o]=$run();check($rc===2&&str_contains($o,'RESUMED_ACCEPTANCE_ONLY=')&&lastRaw($o,'B1149_FINAL')==='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED'&&!str_contains($o,'OVERLAY_ROLLBACK=')&&$tree()==='INSTALLED','NOINDEX_STILL_MISSING_RESUME_HOLDS');
unlink($switches['noHtaccess']);[$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_INSTALLED_STOREFRONT_ROLLOUT_SHOP_STATE_UNCHANGED'&&$tree()==='INSTALLED','NOINDEX_BACK_RESUMED_PASS');
[$rc,$o]=$run(['BEFORE_SMOKES_OLD'=>['noHtaccess']],'rollback');
check($rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&str_contains($o,'OWNER_NOTICE_NOINDEX_MISSING=')&&$tree()==='PRE','NOINDEX_MISSING_ROLLBACK_COMPLETES_WITH_NOTICE');@unlink($switches['noHtaccess']);$reset();
$results['noindexPolicy']=['preflight'=>'HOLD_NOTHING_CHANGED','installed'=>'HOLD_FILES_KEPT_RESUME_HOLDS_THEN_PASS','rollback'=>'PASS_WITH_OWNER_NOTICE'];

// 19. Vary: Cookie (the theme cookie keys /katalog). The edge hides it and no origin route exists: HOLD with the files kept;
// with the loopback-TLS origin route the origin is asked once: present = PASS with a notice, missing = rolled back.
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['dropVaryEdge']]);[$s,$by]=$smokeRows($o);
check($rc===2&&lastRaw($o,'B1149_FINAL')==='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED'&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"VARY_COOKIE_NOT_VERIFIABLE_EDGE_HIDES_IT_AND_NO_ORIGIN_ROUTE"'&&$s['varyUnverified']===true&&$by['/katalog']['varyCookie']===false&&!str_contains($o,'OVERLAY_ROLLBACK=')&&$tree()==='INSTALLED','VARY_HIDDEN_NO_ORIGIN_ROUTE_HOLD_FILES_KEPT');
unlink($switches['dropVaryEdge']);[$rc,$o]=$run();check($rc===0&&str_contains($o,'RESUMED_ACCEPTANCE_ONLY=')&&lastRaw($o,'B1149_FINAL')==='PASS_INSTALLED_STOREFRONT_ROLLOUT_SHOP_STATE_UNCHANGED','VARY_VISIBLE_RESUMED_PASS');$rollbackOk('VARY_HOLD');
$origin=['probeRoute'=>'LOOPBACK_TLS_ORIGIN'];
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['dropVaryEdge']],'install',$origin);[$s,$by]=$smokeRows($o);
check($rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_INSTALLED_STOREFRONT_ROLLOUT_SHOP_STATE_UNCHANGED'&&$by['/katalog']['varyCookie']===false&&$by['/katalog']['originVaryCookie']===true&&in_array('VARY_COOKIE_SENT_BY_THE_ORIGIN_BUT_NOT_VISIBLE_THROUGH_THE_EDGE',$s['notices'],true),'VARY_HIDDEN_ORIGIN_HAS_IT_PASS_WITH_NOTICE');
@unlink($switches['dropVaryEdge']);$rollbackOk('VARY_ORIGIN');
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['dropVaryAll'],'BEFORE_SMOKES_OLD'=>['clearDrops']],'install',$origin);[$s,$by]=$smokeRows(substr($o,0,(int)strpos($o,'OVERLAY_ROLLBACK=')));
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"INSTALLED_SMOKE_FAILED"'&&$by['/katalog']['originVaryCookie']===false&&lastRaw($o,'B1149_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE','VARY_MISSING_AT_THE_ORIGIN_ROLLED_BACK');$reset();
$results['varyCookie']=['edgeHiddenNoOrigin'=>'HOLD_FILES_KEPT_THEN_RESUMED_PASS','edgeHiddenOriginPresent'=>'PASS_WITH_NOTICE','originMissing'=>'ROLLED_BACK'];

// 20. SIGKILL in the hold after the views: the resumed rollback clears the views again and holds the full time before
// bootstrap/app.php and the route go. SIGKILL in the hold before the new classes: the resumed rollback holds from the saved wall clock.
$layoutStep=$stepOf('resources/views/public-shop/layout.blade.php');$bootStep=$stepOf('bootstrap/app.php');$routeStepRow=$stepOf('routes/web.php');$classStep=$stepOf('app/Services/SvgSanitizer.php');
[$rc,$o]=$run();check($rc===0&&$tree()==='INSTALLED','INSTALL_BEFORE_VIEW_HOLD_KILL');
[$rc,$o]=$run(['DURING_HOLD_rollback-after-views'=>['kill']],'rollback');check($rc!==0&&$tree()==='MIXED'&&hash_file('sha256',$active.'/'.$layoutStep['path'])===$layoutStep['preSHA256']&&hash_file('sha256',$active.'/'.$bootStep['path'])===$bootStep['payloadSHA256']&&hash_file('sha256',$active.'/'.$routeStepRow['path'])===$routeStepRow['payloadSHA256'],'KILLED_IN_THE_HOLD_AFTER_THE_VIEWS');
sleep(3);check($status('/katalog')===200&&$status('/pravne-informacije')===200,'VIEWS_OLD_ROUTES_NEW_SERVES');
[$rc,$o]=$run([],'rollback');$h=null;foreach(allLines($o,'OVERLAY_HOLD_START') as $x)if($x['label']==='rollback-after-views')$h=$x;
check($rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE'&&is_array($h)&&$h['clock']==='MONOTONIC_THIS_PROCESS'&&$h['remainingSeconds']>=$h['seconds']-1&&str_contains($o,'"viewsClearedFirst":true'),'RESUMED_ROLLBACK_CLEARS_VIEWS_AND_HOLDS_FULL');$reset();
[$rc,$o]=$run();check($rc===0&&$tree()==='INSTALLED','INSTALL_BEFORE_CLASS_HOLD_KILL');
[$rc,$o]=$run(['DURING_HOLD_rollback-before-new-classes'=>['kill']],'rollback');check($rc!==0&&$tree()==='MIXED'&&is_file($active.'/'.$classStep['path']),'KILLED_IN_THE_HOLD_BEFORE_THE_NEW_CLASSES');
[$rc,$o]=$run([],'rollback');$holds=[];foreach(allLines($o,'OVERLAY_HOLD_START') as $x)$holds[$x['label']]=$x;$firstSaved=null;foreach(allLines($o,'OVERLAY_HOLD_START') as $x)if($x['clock']==='SAVED_WALL_CLOCK_OF_THE_LAST_CHANGE'){$firstSaved=$x;break;}
check($rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE'&&($holds['rollback-before-new-classes']['clock']??null)==='SAVED_WALL_CLOCK_OF_THE_LAST_CHANGE'&&is_array($firstSaved)&&$firstSaved['remainingSeconds']>0&&!str_contains($o,'"viewsClearedFirst":true'),'RESUMED_ROLLBACK_HOLDS_FROM_THE_SAVED_WALL_CLOCK');$reset();
$results['killInRollbackHolds']=['afterViews'=>'VIEWS_CLEARED_AGAIN_FULL_HOLD_THEN_PASS','beforeNewClasses'=>'HELD_FROM_THE_SAVED_WALL_CLOCK_THEN_PASS'];

// 21. One restore fails (bootstrap/app.php): that step and everything before it stay installed, the later steps are old again
// (an exact install prefix that the install itself served). The manual block, run exactly as pasted, restores the old state.
$k=$bootStep['n'];
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['logError'],'BEFORE_RESTORE_'.$k=>['fail']]);$rf=lastLine($o,'ROLLBACK_FILES');$note=lastLine($o,'RECOVERY_NOTE');
$laterModified=count(array_filter($plan,static fn($s)=>$s['n']>$k&&$s['kind']==='modified'));$laterNew=count(array_filter($plan,static fn($s)=>$s['n']>$k&&$s['kind']==='new'));
check($rc===2&&lastRaw($o,'B1149_FINAL')==='HOLD_ROLLBACK_INCOMPLETE'&&$rf['stoppedAtStep']===$k&&$rf['exactInstallPrefixKept']==='STEPS_1_TO_'.$k.'_INSTALLED_LATER_STEPS_RESTORED'&&$rf['modifiedRestored']===$laterModified&&$rf['newRemoved']===$laterNew&&$note['kind']==='ROLLBACK_INCOMPLETE','PARTIAL_ROLLBACK_STOPS_AT_STEP_'.$k);
$prefix($k);sleep(3);check($status('/katalog')===200&&$status('/pravne-informacije')===200&&$status('/up')===200,'PREFIX_'.$k.'_SERVES');
$lines=$block($o);$kinds=array_values(array_filter(array_map($kindOf,$lines)));$expected=[];$lastKey=null;
foreach(array_reverse($plan) as $s){if($s['n']>$k)continue;$key=$s['group'];if($lastKey!==null&&$lastKey!==$key){if($lastKey==='G')$expected[]='view';$expected[]='sleep';}$lastKey=$key;if($s['kind']==='modified')array_push($expected,'verify','install:'.$active.'/'.$s['path']);else $expected[]='rm:'.$active.'/'.$s['path'];}$expected[]='view';
check(count(array_keys($expected,'sleep',true))===5,'EXPECTED_BLOCK_HAS_A_SLEEP_AT_EVERY_GROUP_BOUNDARY');
check($kinds===$expected,'RECOVERY_BLOCK_EXACT_REVERSE_ORDER:'.json_encode($kinds));
$manual=$runBlock($lines);check($manual['rc']===0&&str_contains($manual['out'],'B1149_MANUAL_RECOVERY_BLOCK_DONE')&&$tree()==='PRE','MANUAL_BLOCK_RESTORES_OLD_STATE:'.substr($manual['out'],-400));
[$rc,$o]=$run();check($rc===2&&lastRaw($o,'B1149_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE','AFTER_MANUAL_BLOCK_VERIFIED');$reset();
$results['partialRollback']='PREFIX_1_TO_'.$k.'_MANUAL_BLOCK_RESTORED_THEN_VERIFIED';

// 22. SIGKILL in the middle of the copy: the re-run restores, the next installs.
[$rc,$o]=$run(['AFTER_STEP_20'=>['kill']]);check($rc!==0&&$tree()==='MIXED','KILLED_MID_COPY');
[$rc,$o]=$run();check($rc===2&&lastRaw($o,'B1149_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&str_contains($o,'RESUMED_INTERRUPTED_INSTALL=')&&$tree()==='PRE','KILL_RERUN_RESTORES');
[$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_INSTALLED_STOREFRONT_ROLLOUT_SHOP_STATE_UNCHANGED','KILL_THEN_INSTALL');$rollbackOk('KILL_MID_COPY');
$results['killMidCopy']='RESTORED_THEN_INSTALLED';
// An interrupted backup left changed private copies; the next run writes every stale copy again and the later rollback
// still puts back the exact old bytes.
$modifiedCount=count(array_filter($plan,static fn($s)=>$s['kind']==='modified'));
[$rc,$o]=$run(['AFTER_BACKUP_COPIES'=>['corruptBackup','kill']]);check($rc!==0&&$tree()==='PRE'&&!str_contains($o,'OVERLAY_STEP='),'KILLED_DURING_BACKUP');
[$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1149_FINAL')==='PASS_INSTALLED_STOREFRONT_ROLLOUT_SHOP_STATE_UNCHANGED'&&preg_match_all('/^BACKUP_COPY_REWRITTEN=/m',$o)===$modifiedCount,'STALE_BACKUP_COPIES_REWRITTEN');
$rollbackOk('REWRITTEN_COPIES');$results['staleBackupCopies']=['rewritten'=>$modifiedCount,'rollbackExact'=>true];

check(glob($active.'/public/ald1n-b1144-*')===[]&&$routeCacheSHA()===null&&$shopFlag()==='1','NO_PROBE_NO_ROUTE_CACHE_SHOP_ON');
check(!str_contains($allOutput,'"command":"view:cache"')&&str_contains($allOutput,'"command":"view:clear"')&&str_contains($allOutput,'"command":"queue:restart"')&&!preg_match('/"command":"(?:route:(?!list|clear)|config:|cache:)/',$allOutput),'ONLY_VIEW_CLEAR_ROUTE_CLEAR_QUEUE_RESTART');
check(str_contains($allOutput,'"webEuidIsOperator":true')&&!str_contains($allOutput,'"webEuidIsOperator":false'),'WEB_EUID_IS_OPERATOR');
// The wrong-order controls above wrote their own 500s; nothing else may have fatally failed.
check(substr_count((string)file_get_contents($base.'/server.log'),'PHP Fatal error')===0,'NO_FATAL_IN_SERVER_LOG');
proc_terminate($server);echo json_encode(['b1149Fixture'=>'PASS','cases'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
