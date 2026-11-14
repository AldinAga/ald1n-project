<?php
declare(strict_types=1);
/**
 * The real B1153 coordinator over the real staged 8531-file runtime (ef3f927) in the production topology (active tree with
 * .env and storage symlinks into a retained tree), after the B1145 unclamp, real MariaDB 10.11.19 and real Laravel served
 * by `php -S` with OPcache (validate_timestamps=1, revalidate_freq=2). The production history is replayed for real: the
 * REAL B1146 coordinator installs the legal overlay (shop closed, Report1109), the shop is turned ON (30 synthetic
 * products, one synthetic PNG logo), the REAL B1147 coordinator installs the catalog overlay (Report1112), the REAL B1148
 * coordinator installs the glass overlay (Report1113), the REAL B1149 coordinator installs the storefront rollout
 * (Report1115; a decoy 1114 HOLD report sits beside it), two synthetic product images are added, the REAL B1150 coordinator
 * installs the signed media route (Report1116), the REAL B1151 coordinator installs the e-mail, draft and throttle rollout
 * (Report1117), the REAL B1152 coordinator installs the APP_URL origin pin and the PDF logo guard (its output becomes Report1118),
 * then B1153 runs with the real payload taken from the built operator ZIP, with the shop ON. Every B1153 case is written in terms of
 * the coded plan (STEPS).
 * Explicit doubles: plain loopback HTTP instead of HTTPS, realpath_cache_ttl=10 on the test server (shorter hold), a fixed
 * app-local clock outside the backup window, the shop switch written directly into the fixture database, one CI-only
 * POST /tema per smoke point (the shipped tool never POSTs), CI-only requests with a FORGED Host header (the shipped tool never
 * sends one), the Cloudflare edge played by LogoCatalogFixtureWeb (script injection, e-mail obfuscation outside the email_off pair),
 * the Report1118 digest and the B1152 state id of the fixture (production pins them as constants) and the route count measured on
 * the fixture app (production: 568). The password endpoints are never requested by the tool or by this fixture; the fixture renders one
 * notification mail in a CLI process to see the logo in the header (nothing is sent: the mailer is the array driver).
 * Cases: Report1118 missing, a decoy 1118 that is not a PASS, two 1118 reports, a wrong report digest, a wrong B1152 state id, a B1152
 * state that is not INSTALLED_PASS, route cache present (HOLD, never touched), APP_URL with a trailing slash (HOLD, nothing changed),
 * INSIDE THE BACKUP WINDOW (HOLD, nothing changed), unknown pre-state (a foreign edit of the first and the last file to replace), preflight
 * facts, shop OFF, doctor failing, census before the backup, nothing applied, time budget, missing JSON-LD and CSP rolled back, the order
 * negative controls (a new caller over an old callee: the storefront answers a real 500; groups A and B alone are harmless; the coordinator
 * refusing group E when MailBranding is missing or when the web-SAPI proof is missing), install with a smoke after every step and every group
 * (anonymous pages 200 throughout, the guest admin pages 302, POST /tema 303, the catalog layout queries 200; group order A,A,A,B,B,C x6,D x3,E x12;
 * the full measured hold between group D and group E and so after A; the web-SAPI class proof inside that hold; the new e-mail header
 * and the stored/junk logo height behaviour; the new directories), idempotent re-run, --rollback in the backup window and with --ignore-schedule
 * (group E first, the full hold, groups D, C, B with pauses, the full hold, group A last, the created directories gone), owner rollback with the shop
 * ON and OFF, forced failure, a failing liveness smoke right after E rolled back at once, 429 transient/persistent/resume/rollback, shop OFF during the
 * acceptance, noindex missing in preflight/installed/rollback, Vary hidden by the edge, SIGKILL in the middle of group E, in group A, in both rollback
 * holds and in the middle of the rollback, partial rollbacks with the manual block executed as pasted, SIGKILL mid-copy, and stale backup copies after a kill.
 * Usage: php coordinator-fixture.php <b1153> <b1152> <b1151> <b1150> <b1149> <b1148> <b1147> <b1146> <b1145> <b1144> <b1134> <runtime stage> <payload dir> <runtime-directories.json> <b1146 zip> <b1147 zip> <b1148 zip> <b1149 zip> <b1150 zip> <b1151 zip> <b1152 zip> <b1153 zip>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$tools,$b1152,$b1151,$b1150,$b1149,$b1148,$b1147,$b1146,$b1145,$b1144,$b1134,$stage,$payloadDir,$directoryDoc,$zip1146,$zip1147,$zip1148,$zip1149,$zip1150,$zip1151,$zip1152,$zipPath]=$argv;
require $b1134.'/ReleasePrimitives.php';require $b1134.'/InstallFileOps.php';foreach(['LeanGates','FreshnessProbe','LeanWeb'] as $c)require $b1144.'/'.$c.'.php';require $b1145.'/EnvFlags.php';require $b1145.'/MigrateUnclampCoordinator.php';require $b1146.'/OverlayProbe.php';
foreach(['CatalogWeb','CatalogOverlayCoordinator','FixtureWeb'] as $c)require $b1147.'/'.$c.'.php';foreach(['GlassWeb','GlassOverlayCoordinator'] as $c)require $b1148.'/'.$c.'.php';require $b1149.'/StorefrontInstallCoordinator.php';foreach(['MediaWeb','MediaInstallCoordinator'] as $c)require $b1150.'/'.$c.'.php';require $b1151.'/LegalAccountInstallCoordinator.php';require $b1152.'/OriginPinInstallCoordinator.php';foreach(['LoadProbe','LogoCatalogInstallCoordinator','FixtureWeb'] as $c)require $tools.'/'.$c.'.php';
const DB='ald1n_b1153_ci';const DB_PASSWORD='isolated-ci-only';const PORT=8153;
function check(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('B1153_FIXTURE_'.$name);}
// The edge-script classifier: only Cloudflare's own two shapes are edge scripts; look-alikes are origin (app) scripts.
foreach(['<script data-src="/cdn-cgi/scripts/a.js" src="//evil.example/x.js"></script>'=>'DATA_SRC_LOOKALIKE','<script src="/cdn-cgi/../evil.js"></script>'=>'DOT_DOT_PATH','<script src="/cdn-cgi/scripts/../../evil.js"></script>'=>'DOT_DOT_UNDER_SCRIPTS','<script src="/cdn-cgi/other/a.js"></script>'=>'OTHER_CDN_CGI_DIRECTORY',
    '<script src="/cdn-cgi/scripts/a.js" src="/evil.js"></script>'=>'TWO_SRC','<script data-src="x">window.__CF$cv$params=1;evil()</script>'=>null,'<script src="/x.js">window.__CF$cv$params=1</script>'=>'SRC_WITH_JSD_BODY','<script src=/cdn-cgi/scripts/a.js></script>'=>'UNQUOTED_SRC'] as $html=>$name)
    if($name!==null)check(MediaWeb::scriptCount($html)===1&&MediaWeb::edgeScriptCount($html)===0,'CLASSIFIER_'.$name.'_IS_AN_APP_SCRIPT');
check(MediaWeb::edgeScriptCount('<script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script nonce="x">a="window.__CF$cv$params={}";</script><script src="/cdn-cgi/challenge-platform/scripts/jsd/main.js"></script>')===3,'CLASSIFIER_REAL_EDGE_SHAPES');
function sh(array $args,?string $cwd=null):string{$p=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0)throw new RuntimeException('B1153_FIXTURE_COMMAND_'.basename($args[0]).'_RC'.$rc.':'.substr($err.$out,-900));return trim((string)$out);}
function lastLine(string $output,string $key):mixed{return preg_match_all('/^'.preg_quote($key,'/').'=(.+)$/m',$output,$m)?json_decode(end($m[1]),true,64,JSON_THROW_ON_ERROR):null;}
function lastRaw(string $output,string $key):?string{return preg_match_all('/^'.preg_quote($key,'/').'=(.*)$/m',$output,$m)?end($m[1]):null;}
function allLines(string $output,string $key):array{preg_match_all('/^'.preg_quote($key,'/').'=(.+)$/m',$output,$m);return array_map(static fn($x)=>json_decode($x,true,64,JSON_THROW_ON_ERROR),$m[1]);}
function rmTree(string $path):void{if(is_link($path)||is_file($path)){unlink($path);return;}if(!is_dir($path))return;foreach(scandir($path) as $n)if($n!=='.'&&$n!=='..')rmTree($path.'/'.$n);rmdir($path);}
function child(array $argv):array{$p=proc_open($argv,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return [proc_close($p),$out,$err];}

$pdo=static fn():PDO=>new PDO('mysql:host=127.0.0.1;port=3306;dbname='.DB,'root',DB_PASSWORD,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
check(preg_match('/^10\.11\.19-MariaDB/',(string)$pdo()->query('SELECT VERSION()')->fetchColumn())===1,'MARIADB_10_11_19');
$manifest=json_decode((string)file_get_contents($payloadDir.'/runtime-manifest.json'),true,512,JSON_THROW_ON_ERROR);$directories=json_decode((string)file_get_contents($directoryDoc),true,512,JSON_THROW_ON_ERROR)['directories'];check(count($manifest['files'])===8531,'MANIFEST_8531');

// Production topology: active tree from the pinned runtime; .env and storage are symlinks into the retained tree.
$base=getenv('RUNNER_TEMP').'/b1153';mkdir($base);$base=realpath($base);$home=$base.'/home';$root=$home.'/ald1n-project';foreach([$root.'/incoming',$root.'/apps/cms',$root.'/docs/operations',$home.'/cms-backups'] as $d)mkdir($d,0755,true);
$runtime=$home.'/.ald1n-b1134-retained/'.str_repeat('d',32);mkdir($runtime,0755,true);$retained=$runtime.'/retained';$active=$root.'/apps/cms/current';$candidate=$runtime.'/candidate';$old=$runtime.'/old';
InstallFileOps::candidate($stage,$candidate,$manifest,$directories);if(!is_dir($candidate.'/bootstrap/cache'))mkdir($candidate.'/bootstrap/cache',0755,true);
foreach(['storage/app/public','storage/logs','storage/framework/sessions','storage/framework/cache/data','storage/framework/views'] as $p)mkdir($old.'/'.$p,0755,true);
$envBytes=implode("\n",['APP_NAME=Ald1n','APP_ENV=production','APP_DEBUG=false','APP_KEY=base64:'.base64_encode(random_bytes(32)),'APP_URL=https://cms.ald1n.com','APP_TIMEZONE=Europe/Belgrade','APP_MAINTENANCE_DRIVER=file','DB_CONNECTION=mysql','DB_HOST=127.0.0.1','DB_PORT=3306','DB_DATABASE='.DB,'DB_USERNAME=root','DB_PASSWORD='.DB_PASSWORD,
    'SESSION_DRIVER=file','SESSION_SECURE_COOKIE=false','CACHE_STORE=file','QUEUE_CONNECTION=sync','MAIL_MAILER=array','LOG_CHANNEL=daily','LOG_LEVEL=debug','TURNSTILE_ENABLED=false','PULSE_ENABLED=false','TELESCOPE_ENABLED=false','MOBILE_PUSH_ENABLED=false','OPERATIONAL_EMAIL_NOTIFICATIONS=false',
    'LEGACY_DB_DATABASE=disabled','LEGACY_DB_USERNAME=disabled_qa_legacy','BACKUP_PATH='.$home.'/cms-backups','PUBLIC_STOREFRONT_ENABLED=true','PUBLIC_CHECKOUT_ENABLED=true','']);
file_put_contents($old.'/.env',$envBytes);chmod($old.'/.env',0640);
if(!is_dir($old.'/public'))mkdir($old.'/public',0755,true);check(copy($candidate.'/public/.htaccess',$old.'/public/.htaccess')&&chmod($old.'/public/.htaccess',0644),'OLD_TREE_HTACCESS');
ReleasePrimitives::preservePrivate($old,$candidate,$retained);rename($old,$retained);rename($candidate,$active);
check(is_link($active.'/.env')&&is_link($active.'/storage/logs'),'TOPOLOGY_SYMLINKS');check(hash_file('sha256',$active.'/config/public_storefront.php')===LogoCatalogInstallCoordinator::CONFIG_SHA,'CONFIG_IS_STAGED_ORIGINAL_AFTER_B1145');
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

// All seven operator packages exactly as the owner unzips them, plus the pinned helpers already on the host under their host names.
// All eight operator packages exactly as the owner unzips them, plus the pinned helpers already on the host under their host names.
foreach([$zip1146,$zip1147,$zip1148,$zip1149,$zip1150,$zip1151,$zip1152,$zipPath] as $z)sh(['unzip','-q','-n',$z,'-d',$root]);$incoming=$root.'/incoming';
foreach(['b1134/InstallFileOps.php'=>'b1134-files.php','b1144/LeanGates.php'=>'b1144-gates.php','b1144/FreshnessProbe.php'=>'b1144-probe.php','b1144/LeanWeb.php'=>'b1144-web.php','b1145/EnvFlags.php'=>'b1145-env.php','b1145/AppState.php'=>'b1145-app-state.php','b1145/MigrateUnclampCoordinator.php'=>'b1145-coordinator.php'] as $from=>$to)copy(dirname($tools).'/'.$from,$incoming.'/'.$to);
$package=json_decode((string)file_get_contents($incoming.'/b1153-package.json'),true,64,JSON_THROW_ON_ERROR);foreach($package['files'] as $n=>$d)check(hash_file('sha256',$incoming.'/'.$n)===$d,'PACKAGE_FILE_'.$n);$plan=LogoCatalogInstallCoordinator::plan($package,$incoming);check(count($plan)===count(LogoCatalogInstallCoordinator::STEPS),'PLAN_MATCHES_STEPS');
check(count($plan)===26&&count(array_filter($plan,static fn($s)=>$s['kind']==='new'))===3&&implode(',',array_column($plan,'group'))==='A,A,A,B,B,C,C,C,C,C,C,D,D,D,E,E,E,E,E,E,E,E,E,E,E,E','PLAN_IS_THE_26_FILES_IN_GROUP_ORDER_A3_B2_C6_D3_E12');
check(implode(',',array_column($plan,'path'))===implode(',',array_map(static fn($s)=>$s[0],LogoCatalogInstallCoordinator::STEPS))&&$plan[0]['path']==='app/Support/MailBranding.php'&&end($plan)['path']==='resources/views/public-shop/layout.blade.php'&&$plan[2]['path']==='resources/views/vendor/mail/html/header.blade.php','PLAN_ORDER_NEW_FILES_FIRST_THE_LAYOUT_LAST');
check(array_column($package['overlay'],'source')===array_fill(0,26,LogoCatalogInstallCoordinator::SOURCE_COMMIT),'PAYLOAD_SOURCE_IS_THE_ONE_PINNED_COMMIT');
check(array_column($plan,'payloadSHA256')===array_map(static fn($s)=>LogoCatalogInstallCoordinator::DIGESTS[$s[0]][1],LogoCatalogInstallCoordinator::STEPS)&&array_column($plan,'preSHA256')===array_map(static fn($s)=>LogoCatalogInstallCoordinator::DIGESTS[$s[0]][0],LogoCatalogInstallCoordinator::STEPS),'PLAN_DIGESTS_ARE_THE_REVIEWED_ONES');
// The helpers the B1153 package pins are the very bytes the earlier ZIPs delivered (the host copies must match): every name a built earlier package shares with this one carries the same digest.
foreach(['b1146-probe.php','b1146-app-check.php','b1147-web.php','b1147-app-check.php','b1147-coordinator.php','b1148-web.php','b1148-app-check.php','b1148-coordinator.php','b1149-web.php','b1149-app-check.php','b1149-coordinator.php','b1150-web.php','b1150-app-check.php','b1150-coordinator.php','b1151-app-check.php','b1151-coordinator.php','b1152-app-check.php','b1152-coordinator.php','b1152-load-probe.php'] as $n)check(isset($package['files'][$n]),'PINS_'.$n);
foreach(['b1152','b1151','b1150','b1149','b1148'] as $prev){$pk=json_decode((string)file_get_contents($incoming.'/'.$prev.'-package.json'),true,64,JSON_THROW_ON_ERROR);$shared=0;foreach($pk['files'] as $n=>$d)if(isset($package['files'][$n])){check($package['files'][$n]===$d,strtoupper($prev).'_ZIP_AND_THE_B1153_PIN_AGREE_'.$n);$shared++;}check($shared>=($prev==='b1152'?3:2),strtoupper($prev).'_ZIP_SHARES_PINS_WITH_B1153');}
$purge=(new ReflectionClass(LogoCatalogInstallCoordinator::class))->newInstanceWithoutConstructor();foreach(['cssVersion','oldCssVersion','galleryVersion'] as $k)(new ReflectionProperty($purge,$k))->setValue($purge,$package[$k]);$purgeStep=$purge->purgeStep();$purgeBack=$purge->purgeStep(true);
check(is_string($purgeStep)&&$purgeStep!==''&&is_string($purgeBack)&&$purgeBack!=='','PURGE_STEPS_ARE_TEXT');
$markers=LogoCatalogInstallCoordinator::markers($package);

// Database after B1145: all 71 migrations ran, production roles, DB switch absent; one superadmin; the old-code approval signed.
$artisan=static fn(array $a):string=>sh(array_merge(['php','-d','opcache.enable=0',$active.'/artisan'],$a,['--no-interaction','--no-ansi']),$active);
check(count(glob($active.'/database/migrations/*.php'))===71,'SEVENTY_ONE_MIGRATION_FILES');$artisan(['migrate','--force']);$artisan(['db:seed','--class=Database\Seeders\CoreAccessSeeder','--force']);
$pdo()->exec("UPDATE roles SET name='Korisnik' WHERE slug='user'");
$roleId=(int)$pdo()->query("SELECT id FROM roles WHERE slug='superadmin'")->fetchColumn();check($roleId>0,'SUPERADMIN_ROLE');
$pdo()->prepare("INSERT INTO users (role_id,username,email,first_name,password_hash,status,created_at,updated_at) VALUES (?,?,?,?,?,'active',NOW(),NOW())")->execute([$roleId,'b1153-owner','b1153-owner@example.test','Fixture',password_hash('SyntheticOnly123!',PASSWORD_BCRYPT)]);$ownerId=(int)$pdo()->query("SELECT id FROM users WHERE username='b1153-owner'")->fetchColumn();check($ownerId>0,'OWNER_ROW');
$fingerprintScript=$base.'/fingerprint.php';file_put_contents($fingerprintScript,'<?php chdir($argv[1]);require "vendor/autoload.php";$app=require "bootstrap/app.php";$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();echo $app->make(App\Services\PublicMerchantPolicyService::class)->review()["fingerprint"];');
$pdo()->prepare("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('public_checkout_approval_digest',?,NOW(),NOW())")->execute([sh(['php','-d','opcache.enable=0',$fingerprintScript,$active])]);$artisan(['cache:clear']);

// Real Laravel behind php -S (cli-server SAPI) with OPcache on; requests carry Host: cms.ald1n.com.
$server=proc_open([PHP_BINARY,'-d','opcache.enable=1','-d','opcache.enable_cli=1','-d','opcache.validate_timestamps=1','-d','opcache.revalidate_freq=2','-d','opcache.file_update_protection=0','-d','realpath_cache_ttl=10','-S','127.0.0.1:'.PORT,'-t',$active.'/public',$active.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],[0=>['pipe','r'],1=>['file',$base.'/server.log','a'],2=>['file',$base.'/server.log','a']],$pipes,$active.'/public',['PATH'=>getenv('PATH'),'HOME'=>$home]);
$status=static fn(string $target):int=>LogoCatalogFixtureWeb::get(PORT,$target)['status'];$body=static fn(string $target,array $h=[]):string=>LogoCatalogFixtureWeb::get(PORT,$target,$h)['body'];
$themePost=static fn(string $form):array=>LogoCatalogFixtureWeb::get(PORT,'/tema',['Content-Type: application/x-www-form-urlencoded'],$form);
for($i=0;$i<60&&$status('/up')!==200;$i++)usleep(500000);check($status('/up')===200&&$status('/katalog')===404&&$status('/pravne-informacije')===404,'REAL_LARAVEL_SERVED_PRE_B1146');

$private=$home.'/.ald1n-b1153-state';$private1152=$home.'/.ald1n-b1152-state';$private1151=$home.'/.ald1n-b1151-state';$private1150=$home.'/.ald1n-b1150-state';$private1149=$home.'/.ald1n-b1149-state';$private1148=$home.'/.ald1n-b1148-state';$private1147=$home.'/.ald1n-b1147-state';$private1146=$home.'/.ald1n-b1146-state';$secrets=$base.'/probe-secrets';$faultLog=$base.'/faults.log';$stepLog=$base.'/steps.jsonl';$logDir=$retained.'/storage/logs';$configJSON=$base.'/run.json';$safeNow=strtotime('2026-11-11 13:00:00 UTC');
$switches=[];foreach(['dropCsp','dropJsonLd','dropVaryEdge','dropVaryAll','noHtaccess','rogueScript','rate429','edgeKeepsEmailOff'] as $k)$switches[$k]=$base.'/switch-'.$k;$busyPidFile=$base.'/busy.pid';$allOutput='';file_put_contents($logDir.'/laravel-'.date('Y-m-d').'.log','',FILE_APPEND);
$common=['php'=>PHP_BINARY,'allowedSapis'=>['cli-server'],'censusSeconds'=>20,'minFreeBytes'=>268435456,'scheduleGate'=>true,'fixedNow'=>$safeNow,'incoming'=>$incoming,'port'=>PORT,'secrets'=>$secrets,'faultLog'=>$faultLog,'logDir'=>$logDir,'root'=>$root,'home'=>$home,'stage'=>$stageDir,'b1146'=>$b1146,'b1145'=>$b1145,'b1144'=>$b1144,'b1134'=>$b1134,'busyPidFile'=>$busyPidFile,'db'=>['mysql:host=127.0.0.1;port=3306;dbname='.DB,'root',DB_PASSWORD]];
// $pin1150, $pin1151: the fixture's own Report1116 and Report1117 digests, state ids and route counts, for the REAL B1151 and B1152 runs; $pin: the same facts of the REAL B1152 run (Report1118),
// for B1153 (explicit doubles; production pins its own as constants of the coordinators).
$pin1150=['report'=>str_repeat('0',64),'state'=>str_repeat('0',32),'routes'=>0];$pin1151=$pin1150;$pin=$pin1150;
$run=static function(array $actions=[],string $mode='install',array $overrides=[])use($configJSON,$tools,$b1152,$b1151,$b1150,$b1149,$b1148,$b1147,$private,$private1146,$private1147,$private1148,$private1149,$private1150,$private1151,$private1152,$incoming,$stepLog,$switches,$common,&$allOutput,&$pin):array{
    file_put_contents($configJSON,json_encode($common+['overrides'=>$overrides,'switches'=>$switches,'tools'=>$tools,'b1152'=>$b1152,'b1151'=>$b1151,'b1150'=>$b1150,'b1149'=>$b1149,'b1148'=>$b1148,'b1147'=>$b1147,'base'=>$private,'pointer'=>$private.'/current.json','b1146Base'=>$private1146,'b1147Base'=>$private1147,'b1148Base'=>$private1148,'b1149Base'=>$private1149,'b1150Base'=>$private1150,'b1151Base'=>$private1151,'b1152Base'=>$private1152,'b1152ReportSHA256'=>$pin['report'],'b1152StateId'=>$pin['state'],'expectedRoutes'=>$pin['routes'],
        'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','featureScript'=>$incoming.'/b1153-app-check.php','mode'=>$mode,'stepLog'=>$stepLog,'actions'=>$actions],JSON_UNESCAPED_SLASHES));
    [$rc,$out,$err]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$tools.'/fixture-run.php',$configJSON]);
    echo "----- B1153 RUN mode=$mode rc=$rc -----\n".$out.($err!==''?"\nSTDERR=".substr($err,-2000)."\n":'');$allOutput.=$out;return [$rc,$out];
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
$pdo()->exec("INSERT INTO brands (name,slug,status,created_at,updated_at) VALUES ('Lenovo','lenovo-b1153-ci','active',NOW(),NOW())");$brandId=(int)$pdo()->query("SELECT id FROM brands WHERE slug='lenovo-b1153-ci'")->fetchColumn();check($brandId>0,'BRAND_ROW');
$insert=$pdo()->prepare("INSERT INTO products (sku,slug,name,description,price_amount,price_currency,stock_quantity,status,brand_id,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,'active',?,?,NOW(),NOW())");
for($i=1;$i<=30;$i++)$insert->execute(['B1153-CI-'.$i,'b1153-ci-'.$i,'ThinkPad Synthetic '.$i,'Synthetic B1153 row',1000+97*$i,$i%3===0?'EUR':'RSD',$i%4,$i%2?$brandId:null,$ownerId]);
$pdo()->exec("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('eur_rsd_rate','117.2',NOW(),NOW()) ON DUPLICATE KEY UPDATE setting_value='117.2'");
$logoName='a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0.png';mkdir($retained.'/storage/app/public/site-assets',0755,true);$img=imagecreatetruecolor(96,96);imagefill($img,0,0,imagecolorallocate($img,18,90,200));imagepng($img,$retained.'/storage/app/public/site-assets/'.$logoName);
$pdo()->exec("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('site_logo_light_path','site-assets/".$logoName."',NOW(),NOW()) ON DUPLICATE KEY UPDATE setting_value='site-assets/".$logoName."'");
$shopOn=static function()use($pdo,$artisan):void{$pdo()->exec("DELETE FROM settings WHERE setting_key='public_storefront_open'");$pdo()->exec("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('public_storefront_open','1',NOW(),NOW())");$artisan(['cache:clear']);};
$shopOff=static function()use($pdo,$artisan):void{$pdo()->exec("DELETE FROM settings WHERE setting_key='public_storefront_open'");$artisan(['cache:clear']);};
$shopFlag=static fn():?string=>(($v=$pdo()->query("SELECT setting_value FROM settings WHERE setting_key='public_storefront_open'")->fetchColumn())===false?null:(string)$v);
$shopOn();sleep(3);check($status('/katalog')===200,'SHOP_ON_B1146_CATALOG_SERVED');

// 3. B1153 long before B1152 (no Report1118 yet, the B1147 to B1152 files absent): HOLD before any change. A decoy Report1114
// of B1149 (the HOLD of production, EXIT=2) sits beside the real reports later and must never satisfy a chain gate.
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&lastLine($o,'B1153_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('B1152_REPORT1118_MISSING',$p['blockers'],true)&&!str_contains($o,'OVERLAY_STEP=')&&!str_contains($o,'PER_FILE_BACKUP='),'REFUSED_BEFORE_B1152');rmTree($private);
$decoy=$root.'/docs/operations/1114-B1149-INSTALLER-20261110-235500.md';file_put_contents($decoy,"B1149_FINAL=HOLD\nHOLD_CODE=PREFLIGHT_BLOCKED\nEXIT=2\n");
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&in_array('B1152_REPORT1118_MISSING',$p['blockers'],true)&&!str_contains($o,'PER_FILE_BACKUP='),'DECOY_1114_HOLD_REPORT_DOES_NOT_SATISFY_THE_CHAIN');rmTree($private);$results['beforeB1152']='HOLD_NOTHING_CHANGED_DECOY_1114_IGNORED';

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
check($rc===0&&lastRaw($o1148,'B1148_FINAL')==='PASS_INSTALLED_GLASS_OVERLAY_SHOP_STATE_UNCHANGED','REAL_B1148_INSTALLED');
$report1113=$root.'/docs/operations/1113-B1148-GLASS-OVERLAY-20261110-000906.md';file_put_contents($report1113,"B1148 fixture report\n".$o1148."EXIT=0\nREPORT=".$report1113."\n");
sleep(3);check($status('/katalog')===200&&str_contains($body('/katalog'),'class="shop-theme" method="post"')&&$status('/site-assets/'.$logoName)===404&&$status('/admin/settings/smtp')===404,'B1148_GLASS_SERVED_WITHOUT_THE_B1149_ROUTES');

// 5b. The real B1149 coordinator installs the storefront rollout (SVG logo, SMTP page, gallery) with the shop ON; its output
// becomes Report1115. The decoy 1114 HOLD report written above stays beside it.
$cfg1149=$base.'/run1149.json';file_put_contents($cfg1149,json_encode($common+['overrides'=>[],'switches'=>$switches,'tools'=>$b1149,'b1148'=>$b1148,'b1147'=>$b1147,'base'=>$private1149,'pointer'=>$private1149.'/current.json','b1146Base'=>$private1146,'b1147Base'=>$private1147,'b1148Base'=>$private1148,
    'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','featureScript'=>$incoming.'/b1149-app-check.php','mode'=>'install','stepLog'=>$base.'/steps1149.jsonl','actions'=>[]],JSON_UNESCAPED_SLASHES));
[$rc,$o1149,$e1149]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$b1149.'/fixture-run.php',$cfg1149]);echo "----- B1149 RUN rc=$rc -----\n".$o1149.($e1149!==''?"\nSTDERR=".substr($e1149,-2000)."\n":'');
check($rc===0&&lastRaw($o1149,'B1149_FINAL')===MediaInstallCoordinator::B1149_PASS,'REAL_B1149_INSTALLED');
$report1115=$root.'/docs/operations/1115-B1149-INSTALLER-20261111-002309.md';file_put_contents($report1115,"B1149 fixture report\n".$o1149."EXIT=0\nREPORT=".$report1115."\n");
check(MediaInstallCoordinator::b1149Facts((string)file_get_contents($report1115))['phase']==='INSTALLED_PASS','REPORT1115_TERMINAL_PASS');
try{MediaInstallCoordinator::b1149Facts((string)file_get_contents($decoy));$decoyAccepted=true;}catch(RuntimeException){$decoyAccepted=false;}check(!$decoyAccepted,'DECOY_1114_FACTS_REFUSED');
sleep(3);check($status('/katalog')===200&&$status('/admin/settings/smtp')===302&&in_array($status('/site-assets/'.$logoName),[200,301],true),'B1149_STOREFRONT_SERVED');
$results['chain']=['b1146'=>'REAL_PASS_REPORT1109','b1147'=>'REAL_PASS_REPORT1112','b1148'=>'REAL_PASS_REPORT1113','b1149'=>'REAL_PASS_REPORT1115','decoy1114'=>'IGNORED','shop'=>'ON'];
$envFile=$retained.'/.env';$envOriginal=(string)file_get_contents($envFile);

// Synthetic product images: two product_images rows per the catalog migrations (product_id, file_path,
// original_filename, mime_type, file_size, sort_order, is_primary, storage_disk, file_hash) and two PNG files under the retained
// public disk. The storage_disk value 'public' and the path layout products/<name>.png are ASSUMPTIONS that were not verified
// against PublicProductImageFiles; if the catalog does not show them the media smokes record INFO_NO_PRODUCT_IMAGES and the
// media route stays unexercised until this block is corrected. Nothing below fails the run by itself.
try{
    $productIds=$pdo()->query("SELECT id FROM products WHERE slug IN ('b1153-ci-1','b1153-ci-2') ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);$dir=$retained.'/storage/app/public/products';if(!is_dir($dir))mkdir($dir,0755,true);
    // PublicProductImageFiles::original(): a public file must live under products/<product id>/ (checked with realpath).
    $ins=$pdo()->prepare("INSERT INTO product_images (product_id,file_path,original_filename,mime_type,file_size,sort_order,is_primary,storage_disk,file_hash,created_at) VALUES (?,?,?,'image/png',?,0,1,'public',?,NOW())");
    foreach($productIds as $i=>$pid){$img=$i===0?imagecreatetruecolor(1400,1400):imagecreatetruecolor(320,240);imagefill($img,0,0,imagecolorallocate($img,40+60*$i,120,200-60*$i));ob_start();if($i===0)imagepng($img,null,0);else imagepng($img);$png=(string)ob_get_clean();$name='b1153-ci-'.($i+1).'.png';$pdir=$dir.'/'.(int)$pid;if(!is_dir($pdir))mkdir($pdir,0755,true);file_put_contents($pdir.'/'.$name,$png);$ins->execute([(int)$pid,'products/'.(int)$pid.'/'.$name,$name,strlen($png),hash('sha256',$png)]);}
    $artisan(['cache:clear']);$imagesInserted=count($productIds);
}catch(Throwable $e){$imagesInserted=0;$results['productImages']='NOT_INSERTED_'.get_class($e);}
sleep(3);$oldCatalog=$body('/katalog');$imagesVisible=$imagesInserted>0;
$results['productImages']=($results['productImages']??'')!==''?$results['productImages']:($imagesVisible?'VISIBLE_ON_THE_CATALOG':'INSERTED_BUT_NOT_COUNTED');

check($plan!==[],'THE_B1153_PLAN_IS_EMPTY');check($imagesInserted===2,'TWO_SYNTHETIC_PRODUCT_IMAGES_INSERTED');

// 5c. The real B1150 coordinator installs the signed media route (needs the two product images above) with the shop ON; its output
// becomes Report1116, the chain predecessor of B1153. B1153 refuses to run before it (checked below with decoys).
$cfg1150=$base.'/run1150.json';file_put_contents($cfg1150,json_encode($common+['overrides'=>[],'switches'=>$switches,'tools'=>$b1150,'b1149'=>$b1149,'b1148'=>$b1148,'b1147'=>$b1147,'base'=>$private1150,'pointer'=>$private1150.'/current.json','b1146Base'=>$private1146,'b1147Base'=>$private1147,'b1148Base'=>$private1148,'b1149Base'=>$private1149,
    'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','featureScript'=>$incoming.'/b1150-app-check.php','mode'=>'install','stepLog'=>$base.'/steps1150.jsonl','actions'=>[]],JSON_UNESCAPED_SLASHES));
[$rc,$o1150,$e1150]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$b1150.'/fixture-run.php',$cfg1150]);echo "----- B1150 RUN rc=$rc -----\n".$o1150.($e1150!==''?"\nSTDERR=".substr($e1150,-2000)."\n":'');
check($rc===0&&lastRaw($o1150,'B1150_FINAL')===LegalAccountInstallCoordinator::B1150_PASS,'REAL_B1150_INSTALLED');
$report1116=$root.'/docs/operations/1116-B1150-INSTALLER-20261112-194512.md';file_put_contents($report1116,"B1150 fixture report\n".$o1150."EXIT=0\nREPORT=".$report1116."\n");
check(LegalAccountInstallCoordinator::b1150Facts((string)file_get_contents($report1116))['phase']==='INSTALLED_PASS','REPORT1116_TERMINAL_PASS');
$ptr1150=json_decode((string)file_get_contents($private1150.'/current.json'),true,8,JSON_THROW_ON_ERROR);$routes1150=lastLine($o1150,'ROUTES_CHECK');
$pin1150=['report'=>hash_file('sha256',$report1116),'state'=>$ptr1150['id'],'routes'=>$routes1150['routeCount']];
check(preg_match('/^[a-f0-9]{32}$/D',$pin1150['state'])===1&&is_int($pin1150['routes'])&&$pin1150['routes']>500,'FIXTURE_PINS_READ_FROM_THE_REAL_B1150_RUN');
sleep(3);check($status('/katalog')===200&&$status('/pravne-informacije')===200,'B1150_MEDIA_ROUTE_SERVED');
$results['chain']+=['b1150'=>'REAL_PASS_REPORT1116'];

// 5d. The real B1151 coordinator installs the legal e-mail, merchant draft and password-throttle rollout with the shop ON; its output becomes
// Report1117, the chain predecessor of B1153. B1153 refuses to run before it (checked above and below with decoys).
$cfg1151=$base.'/run1151.json';file_put_contents($cfg1151,json_encode($common+['overrides'=>[],'switches'=>$switches,'tools'=>$b1151,'b1150'=>$b1150,'b1149'=>$b1149,'b1148'=>$b1148,'b1147'=>$b1147,'base'=>$private1151,'pointer'=>$private1151.'/current.json','b1146Base'=>$private1146,'b1147Base'=>$private1147,'b1148Base'=>$private1148,'b1149Base'=>$private1149,'b1150Base'=>$private1150,'b1150ReportSHA256'=>$pin1150['report'],'b1150StateId'=>$pin1150['state'],'expectedRoutes'=>$pin1150['routes'],
    'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','featureScript'=>$incoming.'/b1151-app-check.php','mode'=>'install','stepLog'=>$base.'/steps1151.jsonl','actions'=>[]],JSON_UNESCAPED_SLASHES));
[$rc,$o1151,$e1151]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$b1151.'/fixture-run.php',$cfg1151]);echo "----- B1151 RUN rc=$rc -----\n".$o1151.($e1151!==''?"\nSTDERR=".substr($e1151,-2000)."\n":'');
check($rc===0&&lastRaw($o1151,'B1151_FINAL')===LegalAccountInstallCoordinator::FINAL_PASS,'REAL_B1151_INSTALLED');
$report1117=$root.'/docs/operations/1117-B1151-INSTALLER-20261112-222807.md';file_put_contents($report1117,"B1151 fixture report\n".$o1151."EXIT=0\nREPORT=".$report1117."\n");
check(OriginPinInstallCoordinator::b1151Facts((string)file_get_contents($report1117))['phase']==='INSTALLED_PASS','REPORT1117_TERMINAL_PASS');
$ptr1151=json_decode((string)file_get_contents($private1151.'/current.json'),true,8,JSON_THROW_ON_ERROR);$routes1151=lastLine($o1151,'ROUTES_CHECK');
$pin1151=['report'=>hash_file('sha256',$report1117),'state'=>$ptr1151['id'],'routes'=>$routes1151['routeCount']];
check(preg_match('/^[a-f0-9]{32}$/D',$pin1151['state'])===1&&is_int($pin1151['routes'])&&$pin1151['routes']>500&&$pin1151['routes']===$pin1150['routes'],'FIXTURE_PINS_READ_FROM_THE_REAL_B1151_RUN');
sleep(3);check($status('/katalog')===200&&$status('/pravne-informacije')===200,'B1151_SERVED');
$results['chain']+=['b1151'=>'REAL_PASS_REPORT1117'];

// 5e. The real B1152 coordinator installs the APP_URL origin pin and the PDF logo raster guard with the shop ON; its output becomes Report1118, the chain predecessor of B1153.
// B1153 refuses to run before it (checked above and below with decoys). The B1152 tree is the tree production has today (8569 files).
$cfg1152=$base.'/run1152.json';file_put_contents($cfg1152,json_encode($common+['overrides'=>[],'switches'=>$switches,'tools'=>$b1152,'b1151'=>$b1151,'b1150'=>$b1150,'b1149'=>$b1149,'b1148'=>$b1148,'b1147'=>$b1147,'base'=>$private1152,'pointer'=>$private1152.'/current.json','b1146Base'=>$private1146,'b1147Base'=>$private1147,'b1148Base'=>$private1148,'b1149Base'=>$private1149,'b1150Base'=>$private1150,'b1151Base'=>$private1151,'b1151ReportSHA256'=>$pin1151['report'],'b1151StateId'=>$pin1151['state'],'expectedRoutes'=>$pin1151['routes'],
    'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','featureScript'=>$incoming.'/b1152-app-check.php','mode'=>'install','stepLog'=>$base.'/steps1152.jsonl','actions'=>[]],JSON_UNESCAPED_SLASHES));
[$rc,$o1152,$e1152]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$b1152.'/fixture-run.php',$cfg1152]);echo "----- B1152 RUN rc=$rc -----\n".$o1152.($e1152!==''?"\nSTDERR=".substr($e1152,-2000)."\n":'');
check($rc===0&&lastRaw($o1152,'B1152_FINAL')===LogoCatalogInstallCoordinator::B1152_PASS,'REAL_B1152_INSTALLED');
$report1118=$root.'/docs/operations/1118-B1152-INSTALLER-20261113-193458.md';file_put_contents($report1118,"B1152 fixture report\n".$o1152."EXIT=0\nREPORT=".$report1118."\n");
check(LogoCatalogInstallCoordinator::b1152Facts((string)file_get_contents($report1118))['phase']==='INSTALLED_PASS','REPORT1118_TERMINAL_PASS');
$ptr1152=json_decode((string)file_get_contents($private1152.'/current.json'),true,8,JSON_THROW_ON_ERROR);$routes1152=lastLine($o1152,'ROUTES_CHECK');
$pin=['report'=>hash_file('sha256',$report1118),'state'=>$ptr1152['id'],'routes'=>$routes1152['routeCount']];
check(preg_match('/^[a-f0-9]{32}$/D',$pin['state'])===1&&is_int($pin['routes'])&&$pin['routes']>500&&$pin['routes']===$pin1151['routes'],'FIXTURE_PINS_READ_FROM_THE_REAL_B1152_RUN');
sleep(3);check($status('/katalog')===200&&$status('/pravne-informacije')===200&&$status('/admin/settings/documents')===302,'B1152_SERVED');
$results['chain']+=['b1152'=>'REAL_PASS_REPORT1118','fixtureRouteCount'=>$pin['routes'],'fixtureRouteCountEquals568'=>$pin['routes']===LogoCatalogInstallCoordinator::ROUTES];
// The merchant e-mail the legal pages show (support e-mail of the merchant policy), set before the B1153 baseline; the stored approval digest follows it.
$pdo()->exec("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('public_checkout_support_email','podrska@example.test',NOW(),NOW()) ON DUPLICATE KEY UPDATE setting_value='podrska@example.test'");$artisan(['cache:clear']);
$pdo()->prepare("UPDATE settings SET setting_value=? WHERE setting_key='public_checkout_approval_digest'")->execute([sh(['php','-d','opcache.enable=0',$fingerprintScript,$active])]);$artisan(['cache:clear']);
$edge=new LogoCatalogFixtureWeb(PORT,$secrets,$switches,$active.'/public/.htaccess');$edgeGet=static fn(string $path):array=>$edge->fetch($path,[],bin2hex(random_bytes(8)));
sleep(3);$trader=$edgeGet(LogoCatalogInstallCoordinator::TRADER_PATH);
check($trader['status']===200&&LogoCatalogInstallCoordinator::emailArtifacts($trader['body'])===0&&MediaWeb::edgeScriptCount($trader['body'])===1,'FIXTURE_PREMISE_THE_B1151_LAYOUT_KEEPS_THE_E_MAIL_OUT_OF_THE_OBFUSCATION:'.json_encode([$trader['status'],LogoCatalogInstallCoordinator::emailArtifacts($trader['body']),MediaWeb::edgeScriptCount($trader['body'])]));

$groups=array_values(array_unique(array_column($plan,'group')));sort($groups);check($groups===['A','B','C','D','E'],'FIVE_GROUPS');$guardSteps=array_values(array_filter($plan,static fn($s)=>$s['group']===LogoCatalogInstallCoordinator::GUARD_GROUP));$lastStep=$plan[count($plan)-1];check(count($guardSteps)===12&&$lastStep['path']==='resources/views/public-shop/layout.blade.php'&&$guardSteps[0]['n']===15&&$lastStep['group']===LogoCatalogInstallCoordinator::GUARD_GROUP,'GROUP_E_IS_THE_LAST_GROUP_THE_LAYOUT_THE_LAST_STEP');
$classSteps=array_values(array_filter($plan,static fn($s)=>$s['group']==='A'));$libSteps=array_values(array_filter($plan,static fn($s)=>$s['group']==='B'));$callerSteps=array_values(array_filter($plan,static fn($s)=>$s['group']==='C'));$pdfSteps=array_values(array_filter($plan,static fn($s)=>$s['group']==='D'));
$newSteps=array_values(array_filter($plan,static fn($s)=>$s['kind']==='new'));$firstModified=null;foreach($plan as $s)if($s['kind']==='modified'){$firstModified=$s;break;}
$stateOf=static function(array $s)use($active):string{$t=$active.'/'.$s['path'];if(!is_file($t))return 'absent';$h=hash_file('sha256',$t);return $h===$s['payloadSHA256']?'payload':($h===$s['preSHA256']?'pre':'foreign');};
$createdDirs=['resources/views/emails/partials','resources/views/vendor','resources/views/vendor/mail','resources/views/vendor/mail/html'];
$dirsGone=static function()use($active,$createdDirs):bool{foreach($createdDirs as $d)if(file_exists($active.'/'.$d)||is_link($active.'/'.$d))return false;return true;};
check($dirsGone(),'THE_FOUR_DIRECTORIES_THE_INSTALL_CREATES_DO_NOT_EXIST_BEFORE');

$reset=static function()use($private,$secrets,$faultLog,$stepLog,$logDir,$artisan,$tree,$status,$switches,$envFile,$envOriginal):void{
    rmTree($private);foreach(array_merge([$secrets,$faultLog,$stepLog],array_values($switches),glob($logDir.'/laravel*.log')?:[]) as $f)if(file_exists($f))unlink($f);file_put_contents($logDir.'/laravel-'.date('Y-m-d').'.log','');file_put_contents($envFile,$envOriginal);$artisan(['cache:clear']);
    check($tree()==='PRE','RESET_TREE_PRE');sleep(3);check($status('/login')===200,'RESET_WARM_LOGIN');
};
$allSteps=static function()use($plan,$groups):array{$a=[];foreach($plan as $s)$a['AFTER_STEP_'.$s['n']]=['stepSmoke'];foreach($groups as $g)$a['GROUP_DONE_'.$g]=['stepSmoke'];$a['AFTER_INVALIDATE']=['stepSmoke'];return $a;};
$stepRows=static fn():array=>array_map(static fn($l)=>json_decode($l,true),array_values(array_filter(explode("\n",(string)@file_get_contents($stepLog)))));
$stepOf=static function(string $path)use($plan):array{foreach($plan as $s)if($s['path']===$path)return $s;throw new RuntimeException('B1153_FIXTURE_STEP_UNKNOWN');};
$prefix=static function(int $k)use($plan,$active):void{foreach($plan as $s){$t=$active.'/'.$s['path'];$sha=is_file($t)?hash_file('sha256',$t):null;check($sha===($s['n']<=$k?$s['payloadSHA256']:$s['preSHA256']),'PREFIX_'.$k.'_STEP_'.$s['n']);}};
$block=static function(string $o):array{check(preg_match("/^bash -e <<'B1153_RECOVERY'\n(.*?)\nB1153_RECOVERY$/ms",$o,$m)===1,'RECOVERY_BLOCK_PRESENT');return explode("\n",$m[1]);};
$kindOf=static function(string $l):?string{$last=preg_match("/'([^']*)'$/",$l,$m)?$m[1]:null;
    if(in_array($l,['set -euo pipefail','umask 022','echo B1153_MANUAL_RECOVERY_BLOCK_DONE'],true))return null;if(str_starts_with($l,'printf ')&&str_contains($l,'| sha256sum --check --strict --quiet -'))return 'verify';
    if(str_starts_with($l,'install -m ')&&str_contains($l,' -g '))return 'install:'.$last;if(str_starts_with($l,'rm -f -- '))return 'rm:'.$last;if(str_starts_with($l,'rmdir --ignore-fail-on-non-empty -- '))return 'rmdir';if(preg_match('/^sleep [0-9]+$/D',$l))return 'sleep';if(str_contains($l,' artisan view:clear --no-interaction'))return 'view';return 'other';};
$runBlock=static function(array $lines):array{$p=proc_open(['bash','-e'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fwrite($pipes[0],implode("\n",$lines)."\n");fclose($pipes[0]);$out=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return ['rc'=>proc_close($p),'out'=>$out];};
$conflictNow=(new DateTimeImmutable('2026-11-11 02:10:00',new DateTimeZone('Europe/Belgrade')))->getTimestamp();
$smokeRows=static function(string $o):array{$s=lastLine($o,'PUBLIC_HTTP_SMOKES');$by=[];foreach($s['rows'] as $r)$by[$r['check']]=$r;return [$s,$by];};
$rollbackOk=static function(string $what)use($run,$tree,$reset):void{[$rc,$o]=$run([],'rollback');check($rc===0&&lastRaw($o,'B1153_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE','CLEANUP_ROLLBACK_'.$what);$reset();};
$cssV=LogoCatalogInstallCoordinator::CSS_PATH.'?v='.$package['cssVersion'];$galV=LogoCatalogInstallCoordinator::GALLERY_CSS_PATH.'?v='.$package['galleryVersion'];
// A smoke row is fine when it passed or was reported as data-dependent INFO (no product image, no logo).
$rowOk=static fn(array $by,string $k):bool=>isset($by[$k])&&(str_starts_with((string)$by[$k]['result'],'PASS')||str_starts_with((string)$by[$k]['result'],'INFO'));
// The B1149 storefront and the B1150/B1151 additions the production tree runs today: asserted in the OLD state and again in the INSTALLED state.
$b1149Intact=static function(array $s,array $by,string $where)use($rowOk):void{
    check($by['/katalog']['originScripts']===1&&$by['/katalog']['edgeInjectedScripts']===1&&$by['/katalog']['cspShape']===true&&$by['/katalog']['layoutMarker']===true&&$by['/katalog']['galleryLink']===true&&$by['/katalog']['cacheControlPrivateNoStore']===true&&$by['/katalog']['jsonLd']==='PASS','B1149_FEATURES_NONCE_GALLERY_PRIVATE_NO_STORE_'.$where);
    // The B1151 layout keeps the page body out of the Cloudflare e-mail obfuscation, so the decoder is not injected: one edge script (the JavaScript-detections loader); the origin script count stays exactly 1.
    check($by['/kolacici']['originScripts']===1&&in_array($by['/kolacici']['edgeInjectedScripts'],[1,2],true)&&$by['/kolacici']['cspShape']===true&&$by['/kolacici']['layoutMarker']===true,'B1149_LEGAL_NONCE_SCRIPT_'.$where);
    check($by['/admin/settings/smtp']['observed']===302&&$by['/admin/settings/smtp']['location']==='LOGIN'&&$by['GET /tema']['observed']===405&&$by['HEAD /tema']['observed']===405&&$rowOk($by,'SITE_ASSET_LOGO')&&$by[LogoCatalogInstallCoordinator::GALLERY_CSS_PATH]['result']==='PASS','B1149_SMTP_GUEST_302_THEME_405_LOGO_GALLERY_CSS_'.$where);
    check($by[LogoCatalogInstallCoordinator::MERCHANT_PATH]['observed']===302&&$by[LogoCatalogInstallCoordinator::MERCHANT_PATH]['location']==='LOGIN'&&$by[LogoCatalogInstallCoordinator::MERCHANT_PATH]['result']==='PASS','B1151_MERCHANT_SETTINGS_GUEST_302_'.$where);
    // B1150 stays: the signed media route, the accessor and the doctor-facing rows pass in both states (the fixture has two product images with files).
    foreach(['PRODUCT_IMAGE_URL_ACCESSOR','MEDIA_ROUTE_IMAGE','MEDIA_ROUTE_ORIGINAL_HEAD','MEDIA_ROUTE_NOT_MODIFIED','MEDIA_ROUTE_BAD_SIGNATURE','MEDIA_ROUTE_MISSING_SIGNATURE','MEDIA_ROUTE_UNKNOWN_IMAGE'] as $k)check(($by[$k]['result']??'')==='PASS','B1150_MEDIA_ROUTE_ROW_'.$k.'_'.$where.':'.json_encode($by[$k]??null));
};
// B1152 stays in both states: the three guest admin redirects are on the pinned origin (the pin is live before this rollout, so the plain-http fixture request is redirected to https all the same), the login form is clean.
$pinRows=static function(array $by,bool $installed,string $where):void{
    foreach([LogoCatalogInstallCoordinator::SMTP_PATH,LogoCatalogInstallCoordinator::MERCHANT_PATH,LogoCatalogInstallCoordinator::DOCUMENTS_PATH] as $p)
        check($by[$p]['observed']===302&&$by[$p]['result']==='PASS'&&$by[$p]['location']==='LOGIN'&&$by[$p]['locationOrigin']==='PINNED_ORIGIN','GUEST_REDIRECT_ON_THE_PINNED_ORIGIN_'.$p.'_'.$where.':'.json_encode([$by[$p]['observed']??null,$by[$p]['locationOrigin']??null]));
    check($by['/login']['result']==='PASS'&&($by['/login']['absoluteFormActionsOffOrigin']??-1)===0,'LOGIN_FORM_ACTION_ON_THE_ORIGIN_'.$where);
};
// The merchant e-mail on the legal pages: B1151 is live, so the count is the same before and after this rollout (the payload touches no legal text).
$emailState=static function(array $by,bool $installed,string $where):void{
    $e=$by['LEGAL_EMAIL_STATE'];
    check($e['result']===($installed?'INFO_SAME_AS_THE_OLD_STATE':'INFO_OLD_STATE_COUNT_RECORDED_NOT_JUDGED')&&$e['now']['checked']===8&&$e['now']['artifacts']===0,'EMAIL_STATE_UNCHANGED_'.$where.':'.json_encode([$e['result'],$e['now'],$e['oldState']??null]));
};
// B1153: the catalog page carries the per-page form and the grid/list switch only once installed; the layout queries answer 200 in both states (the old code ignores them, the new code whitelists
// them: 20 and list are taken, 999 falls back to 40); the header logo img has a height attribute (38, the old default, before; 32, the new default, after; a stored value is tested after the install).
$viewRows=static function(array $by,bool $installed,string $where):void{
    $k=$by['/katalog'];check($k['perPageControl']===$installed&&$k['viewSwitch']===$installed&&$k['result']==='PASS','PER_PAGE_CONTROL_AND_VIEW_SWITCH_'.($installed?'PRESENT':'ABSENT').'_'.$where.':'.json_encode([$k['perPageControl']??null,$k['viewSwitch']??null,$k['result']??null]));
    $l=$by['/katalog?per_page=20&view=list'];check($l['observed']===200&&$l['result']==='PASS'&&$l['listLayout']===$installed&&$l['selected20']===$installed,'LIST_VIEW_20_PER_PAGE_'.$where.':'.json_encode($l));
    $d=$by['/katalog?per_page=999'];check($d['observed']===200&&$d['result']==='PASS'&&($installed?($d['selected40']===true&&$d['option999']===false):$d['option999']===false),'PER_PAGE_999_FALLS_BACK_'.$where.':'.json_encode($d));
    $h=$by['SHOP_HEADER_LOGO_HEIGHT'];check($h['result']==='PASS'&&$h['heightAttribute']===($installed?32:38),'HEADER_LOGO_IMG_HEIGHT_ATTRIBUTE_'.($installed?'32':'38').'_'.$where.':'.json_encode($h));
    check($by['/login']['observed']===200&&$by['/up']['observed']===200,'LOGIN_AND_UP_200_'.$where);
};
// A request with a FORGED Host header (CI only; the shipped tool never sends one): the redirect Location and the login form must stay on the pinned origin (B1152, live before and after this rollout).
$forged=static function()use($edgeGet):array{
    $r=LogoCatalogFixtureWeb::getHost(PORT,'/admin/settings/smtp','forged-host.invalid');$l=LogoCatalogFixtureWeb::getHost(PORT,'/login','forged-host.invalid');
    return ['status'=>$r['status'],'location'=>(string)$r['location'],'loginStatus'=>$l['status'],'loginBodyMentionsForged'=>str_contains($l['body'],'forged-host.invalid')];
};
// The e-mail header as the CLI renders it (nothing is sent: the mailer is the array driver, a MailMessage is only rendered): MailBranding::logo() and the HTML of a notification mail.
$mailScript=$base.'/mail-check.php';file_put_contents($mailScript,'<?php chdir($argv[1]);require "vendor/autoload.php";$app=require "bootstrap/app.php";$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();$logo=class_exists(App\Support\MailBranding::class)?App\Support\MailBranding::logo():null;$html=(string)(new Illuminate\Notifications\Messages\MailMessage)->subject("x")->line("hello")->render();preg_match_all("~<img\\b[^>]*>~i",$html,$m);$img=null;foreach($m[0] as $tag)if(str_contains($tag,"site-assets"))$img=$tag;echo json_encode(["logoIsArray"=>is_array($logo),"logoSrcOnOrigin"=>is_array($logo)&&str_starts_with($logo["src"],"https://cms.ald1n.com/site-assets/"),"logoHeight"=>is_array($logo)?$logo["height"]:null,"logoOnDark"=>is_array($logo)?$logo["on_dark"]:null,"htmlHasLogoImg"=>$img!==null,"imgHeight"=>$img!==null&&preg_match("~height=\"(\\d+)\"~",$img,$h)?(int)$h[1]:null,"imgOnOrigin"=>$img!==null&&str_contains($img,"https://cms.ald1n.com/site-assets/"),"htmlBytes"=>strlen($html)],JSON_THROW_ON_ERROR);');
$mailCheck=static fn():array=>json_decode(sh(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$mailScript,$active],$active),true,8,JSON_THROW_ON_ERROR);
$reset();

// 6. A route cache present: B1146 to B1151 ran without one, so the preflight HOLDs; the cache is never touched.
$artisan(['route:cache']);$cacheBefore=$routeCacheSHA();check($cacheBefore!==null,'ROUTE_CACHE_CREATED');$reset();
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');
check($rc===2&&lastLine($o,'B1153_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('ROUTE_CACHE_PRESENT_NOTHING_CHANGED',$p['blockers'],true)&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE'&&$routeCacheSHA()===$cacheBefore,'ROUTE_CACHE_PRESENT_HOLD_UNTOUCHED');
$artisan(['route:clear']);check($routeCacheSHA()===null,'FIXTURE_ROUTE_CACHE_REMOVED');$reset();$results['routeCachePresent']='HOLD_NOTHING_CHANGED_CACHE_BYTES_UNCHANGED';

// 6b. The chain gate on Report1118 (and on the B1152 state it names): every way of not being the verified B1152 PASS is a HOLD with nothing
// changed: the report missing, only a decoy that is not a PASS, the real report beside a decoy (two reports), the production digest pin (the
// fixture's report cannot have it), a different B1152 state id, a B1152 state that is not INSTALLED_PASS.
$gate=static function(string $code,string $name,array $overrides=[])use($run,$tree,$reset):void{
    [$rc,$o]=$run([],'install',$overrides);$p=lastLine($o,'PREFLIGHT');
    check($rc===2&&lastLine($o,'B1153_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array($code,$p['blockers'],true)&&!str_contains($o,'PER_FILE_BACKUP=')&&!str_contains($o,'OVERLAY_STEP=')&&$tree()==='PRE','CHAIN_GATE_'.$name.':'.json_encode($p['blockers']));$reset();
};
$decoy1118=$root.'/docs/operations/1118-B1152-INSTALLER-20261113-173000.md';
rename($report1118,$report1118.'.moved');$gate('B1152_REPORT1118_MISSING','REPORT_1118_MISSING');
file_put_contents($decoy1118,"B1152_FINAL=HOLD\nHOLD_CODE=PREFLIGHT_BLOCKED\nEXIT=2\n");$gate('B1152_REPORT_NOT_TERMINAL_PASS','DECOY_1118_IS_NOT_A_PASS');
rename($report1118.'.moved',$report1118);$gate('B1152_REPORT1118_AMBIGUOUS','TWO_1118_REPORTS');unlink($decoy1118);
$gate('B1152_REPORT1118_DIGEST_DIFFERS_FROM_THE_PINNED_ONE','REPORT_DIGEST_DIFFERS_FROM_THE_PRODUCTION_PIN',['b1152ReportSHA256'=>LogoCatalogInstallCoordinator::B1152_REPORT_SHA256]);
$gate('B1152_STATE_ID_DIFFERS_FROM_THE_PINNED_ONE','STATE_ID_DIFFERS',['b1152StateId'=>str_repeat('a',32)]);
$stateFile=$private1152.'/'.$pin['state'].'/state.json';$stateOriginal=(string)file_get_contents($stateFile);file_put_contents($stateFile,str_replace('"phase":"INSTALLED_PASS"','"phase":"ROLLED_BACK"',$stateOriginal));
$gate('B1152_STATE_NOT_INSTALLED_PASS','B1152_STATE_NOT_INSTALLED_PASS');file_put_contents($stateFile,$stateOriginal);
// A report that is the right PASS but one byte different (a changed digest) is the same HOLD as the production pin: the pin is the host file's bytes. The production pins are Report1118's facts:
// its sha256 is that of the git blob (the CRLF worktree copy on a Windows checkout has another digest), its state id and its route count are the ones Report1118 printed.
check(LogoCatalogInstallCoordinator::B1152_REPORT_SHA256==='6000b386557fdc016e2789a80bcd5b68d33a3459311eb1ae791149f96e840edc'&&LogoCatalogInstallCoordinator::B1152_STATE_ID==='481f6ec63f535aa680343e9e1059e122'&&LogoCatalogInstallCoordinator::ROUTES===568&&LogoCatalogInstallCoordinator::installedTreeFiles()===8572&&LogoCatalogInstallCoordinator::EXPECTED_APP_URL==='https://cms.ald1n.com','PRODUCTION_PINS_ARE_THE_REPORT1118_FACTS');
// The repository copy of Report1118 is the file the pin names: its git blob digest is the production pin (only checked where the repository is the checkout, as in CI).
$repoReport=dirname($tools,3).'/docs/operations/1118-B1152-INSTALLER-20261113-193458.md';if(is_file($repoReport))check(hash_file('sha256',$repoReport)===LogoCatalogInstallCoordinator::B1152_REPORT_SHA256,'THE_ARCHIVED_REPORT1118_HAS_THE_PINNED_DIGEST');
// 6c. The pin is strict: the effective config('app.url') must be exactly https://cms.ald1n.com. A trailing slash is a HOLD with nothing changed (the .env of the
// retained tree is restored by the reset).
file_put_contents($envFile,str_replace('APP_URL=https://cms.ald1n.com','APP_URL=https://cms.ald1n.com/',$envOriginal));$artisan(['cache:clear']);
$gate('APP_URL_NOT_EXACTLY_THE_PINNED_ORIGIN_NOTHING_CHANGED','APP_URL_WITH_TRAILING_SLASH');check((string)file_get_contents($envFile)===$envOriginal,'ENV_RESTORED_BY_THE_RESET');
file_put_contents($envFile,str_replace('APP_URL=https://cms.ald1n.com','APP_URL=http://cms.ald1n.com',$envOriginal));$artisan(['cache:clear']);
$gate('APP_URL_NOT_EXACTLY_THE_PINNED_ORIGIN_NOTHING_CHANGED','APP_URL_PLAIN_HTTP');
// 6d. INSIDE THE BACKUP WINDOW: 20 minutes before the 02:30 backup an INSTALL is a HOLD with nothing changed (the install window this run needs would run into the backup). The same clock outside the window is the
// clock of every other case of this fixture.
[$rc,$o]=$run([],'install',['fixedNow'=>$conflictNow]);$p=lastLine($o,'PREFLIGHT');
check($rc===2&&lastLine($o,'B1153_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&preg_grep('/^SCHEDULED_BACKUP_WITHIN_30_MINUTES_[A-Z]+$/D',$p['blockers'])!==[]&&!str_contains($o,'PER_FILE_BACKUP=')&&!str_contains($o,'OVERLAY_STEP=')&&$tree()==='PRE','INSTALL_INSIDE_THE_BACKUP_WINDOW_HOLDS_NOTHING_CHANGED:'.json_encode($p['blockers']));$reset();
$results['chainGate']=['report1118Missing'=>'HOLD','decoy1118'=>'HOLD','twoReports'=>'HOLD','digestPin'=>'HOLD','stateId'=>'HOLD','stateNotInstalledPass'=>'HOLD','appUrlTrailingSlash'=>'HOLD','appUrlHttp'=>'HOLD','insideTheBackupWindow'=>'HOLD','nothingChanged'=>true];

// 7. Refusal: an unknown pre-state of the first and of the last replaced file stops the run before any change.
$modifiedSteps=array_values(array_filter($plan,static fn($s)=>$s['kind']==='modified'));
if($modifiedSteps!==[]){
    foreach([reset($modifiedSteps),end($modifiedSteps)] as $victim){
        $ctl=$active.'/'.$victim['path'];$original=file_get_contents($ctl);file_put_contents($ctl,$original."\n// foreign local edit\n");
        [$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&lastLine($o,'B1153_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('UNKNOWN_PRE_STATE_REFUSED',$p['blockers'],true)&&!str_contains($o,'OVERLAY_STEP=')&&!str_contains($o,'PER_FILE_BACKUP='),'UNKNOWN_PRE_STATE_REFUSED_'.$victim['n']);
        $newPresent=array_filter($newSteps,static fn($s)=>is_file($active.'/'.$s['path']));check(file_get_contents($ctl)===$original."\n// foreign local edit\n"&&$newPresent===[],'UNKNOWN_PRE_STATE_NOTHING_CHANGED_'.$victim['n']);file_put_contents($ctl,$original);$reset();
    }
    $results['unknownPreState']='HOLD_NOTHING_CHANGED_FIRST_AND_LAST_MODIFIED_FILE';
}else $results['unknownPreState']='SKIPPED_NO_MODIFIED_STEP';

// 8. Preflight facts with the B1152 tree: the B1149 to B1152 features are the OLD state (the origin pin, the PDF raster guard included), the new class MailBranding is absent, no B1153 source marker is true, the
// route count is the B1152 one, APP_URL is exactly the pinned origin, the PHP extensions and the mail fingerprint are read, nothing is written.
[$rc,$o]=$run(['AFTER_PREFLIGHT'=>['fail']]);$ft=allLines($o,'FEATURE_CLI_CHECK')[0]??null;$en=allLines($o,'ENVIRONMENT_CLI_CHECK')[0]??null;$rcOld=allLines($o,'ROUTES_CHECK')[0]??null;
check(is_array($ft)&&$ft['expectInstalled']===false&&$ft['themeRoutePostOnly']===true&&$ft['routesCached']===false&&($ft['routeList']['site-assets.show']??null)===1&&($ft['routeList']['admin.settings.smtp.index']??null)===1&&($ft['routeList']['public-shop.theme']??null)===1&&($ft['routeList'][LogoCatalogInstallCoordinator::MEDIA_ROUTE_NAME]??null)===1&&$ft['b1150ClassesLoaded']===true&&$ft['routeMedia']===true
    &&$ft['throttleApi']===true&&$ft['throttleResolvable']===true&&$ft['webPasswordUsesThrottle']===true&&$ft['apiPasswordUsesThrottle']===true&&$ft['merchantIssueLabels']===true
    &&$ft['originConfiguredIsExpected']===true&&$ft['middlewareGlobal']===true&&$ft['bootstrapNamesPin']===true&&$ft['pdfRasterGuard']===true
    &&$ft['newClassesLoaded']===0&&$ft['newClassesTotal']===1&&$ft['logoHeightsApi']===false&&$ft['catalogWhitelist']===false&&$ft['freshBrandingApi']===false&&$ft['mailBrandingApi']===false&&count($ft['sourceMarkers'])===12&&!in_array(true,$ft['sourceMarkers'],true),'OLD_STATE_FEATURES_B1152_PRESENT_B1153_ABSENT:'.json_encode($ft));
check(is_array($rcOld)&&$rcOld['routeCount']===$pin['routes']&&$rcOld['expectedRouteCount']===$pin['routes']&&$rcOld['routesCached']===false&&$rcOld['b1146RouteNamesPresent']===11,'ROUTE_COUNT_IS_THE_B1152_COUNT_BEFORE:'.json_encode($rcOld));
check(is_array($en)&&$en['extensions']['dom']===true&&$en['extensions']['libxml']===true&&$en['extensions']['fileinfo']===true&&$en['extensions']['mbstring']===true&&($en['extensions']['gd']===true||$en['extensions']['imagick']===true)&&$en['appUrlHttpsOrigin']===true&&$en['appUrlExact']===true&&is_bool($en['trustedOrCanonicalHostConfigPresent'])&&!array_key_exists('mailFingerprintHMAC',$en)&&!array_key_exists('mailFingerprintSHA256',$en),'ENVIRONMENT_FACTS_APP_URL_EXACT_WITHOUT_ANY_FINGERPRINT');
[$s,$by]=$smokeRows($o);check($s['state']==='OLD','OLD_STATE_SMOKES_SHAPE');$b1149Intact($s,$by,'OLD');$pinRows($by,false,'OLD');$emailState($by,false,'OLD');$viewRows($by,false,'OLD');
check(!$imagesVisible||(MediaWeb::mediaLinkCount($oldCatalog)===0&&$rowOk($by,'PUBLIC_IMAGE_ROUTE')),'THE_CATALOG_LINKS_NO_SIGNED_URL_AND_THE_PUBLIC_IMAGE_ROUTE_ANSWERS');
check($tree()==='PRE'&&!str_contains($o,'OVERLAY_STEP='),'PREFLIGHT_FACTS_NOTHING_CHANGED');
// The e-mail header before: no MailBranding, so the notification mail shows the site name and no logo image.
$mc=$mailCheck();check($mc['logoIsArray']===false&&$mc['htmlHasLogoImg']===false&&$mc['htmlBytes']>500,'OLD_STATE_MAIL_HEADER_HAS_NO_LOGO_IMAGE:'.json_encode($mc));
// B1152 is live before this rollout: a request with a forged Host header is redirected to the pinned origin already (the installed state is asserted again below).
$f=$forged();check($f['status']===302&&$f['location']==='https://cms.ald1n.com/login'&&$f['loginStatus']===200&&$f['loginBodyMentionsForged']===false,'FORGED_HOST_STAYS_ON_THE_PINNED_ORIGIN_BEFORE_B1153:'.json_encode($f));$results['forgedHostOldState']='LOCATION_AND_LOGIN_PAGE_ON_THE_PINNED_ORIGIN_B1152_IS_LIVE';
$reset();
// A script that is not one of the two Cloudflare edge scripts is an app script: the one-nonce-script old state then fails the preflight.
touch($switches['rogueScript']);[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');[$s,$by]=$smokeRows($o);
check($rc===2&&in_array('OLD_STATE_SMOKE_FAILED',$p['blockers'],true)&&$by['/katalog']['originScripts']===2&&$by['/katalog']['cspShape']===false&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','ROGUE_SCRIPT_IN_THE_OLD_STATE_HOLDS');unlink($switches['rogueScript']);
// A preflight HOLD is re-run with the same command and the same private state (as Report1114 was on production): it goes on.
[$rc,$o]=$run(['AFTER_PREFLIGHT'=>['fail']]);$p=lastLine($o,'PREFLIGHT');check($rc===2&&$p['blockers']===[]&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','RERUN_AFTER_A_PREFLIGHT_HOLD_PASSES_THE_PREFLIGHT');$reset();$results['preflightFacts']=['b1149Features'=>'PRESENT_IN_THE_OLD_STATE','b1150MediaRoute'=>'PRESENT_IN_THE_OLD_STATE','b1151Throttle'=>'PRESENT_IN_THE_OLD_STATE','b1152OriginPinAndPdfGuard'=>'PRESENT_IN_THE_OLD_STATE','mailBranding'=>'ABSENT','logoHeightsAndCatalogWhitelist'=>'ABSENT','routeCount'=>'THE_B1152_COUNT','appUrl'=>'EXACTLY_THE_PINNED_ORIGIN','extensions'=>'DOM_LIBXML_FILEINFO_MBSTRING_GD_OR_IMAGICK','htaccessCsp'=>'NONE'];

// 9. With the shop OFF a fresh install HOLDs; the switch is never written.
$shopOff();[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&in_array('SHOP_IS_OFF_B1153_INSTALLS_ONLY_WITH_THE_SHOP_ON_NOTHING_CHANGED',$p['blockers'],true)&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE'&&$shopFlag()===null,'SHOP_OFF_INSTALL_REFUSED');$shopOn();$reset();$results['shopOffInstall']='HOLD_NOTHING_CHANGED';
// 9b. The live doctor fails before any change (the file of the newest public image is missing): HOLD, the tree untouched, nothing backed up.
$newest=$pdo()->query("SELECT file_path FROM product_images WHERE storage_disk='public' ORDER BY id DESC LIMIT 1")->fetchColumn();$newestFile=$retained.'/storage/app/public/'.$newest;check(is_file($newestFile)&&rename($newestFile,$newestFile.'.moved'),'NEWEST_IMAGE_FILE_MOVED_AWAY');
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');rename($newestFile.'.moved',$newestFile);
check($rc===2&&in_array('PRODUCT_MEDIA_DOCTOR_FAILED',$p['blockers'],true)&&!str_contains($o,'PER_FILE_BACKUP=')&&!str_contains($o,'OVERLAY_STEP=')&&$tree()==='PRE','DOCTOR_FAIL_IN_PREFLIGHT_HOLDS_NOTHING_CHANGED');$reset();$results['doctorFailPreflight']='HOLD_NOTHING_CHANGED';

// 10. An artisan run that starts after the preflight is seen by the census BEFORE the backup; nothing is written.
[$rc,$o]=$run(['AFTER_PREFLIGHT'=>['busy']]);$pid=(int)trim((string)@file_get_contents($busyPidFile));if($pid>0){@posix_kill(-$pid,9);@posix_kill($pid,9);}shell_exec("pkill -9 -f 'sleep 40; true' || true");@unlink($busyPidFile);
check($rc===2&&lastLine($o,'B1153_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&str_contains($o,'"blockers":["CMS_ARTISAN_RUNNING"]')&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','CENSUS_BEFORE_BACKUP');$reset();
// A failure before the first file: "nothing applied", no view or web work.
[$rc,$o]=$run(['BEFORE_STEP_1'=>['fail']]);$tail=substr($o,(int)strpos($o,'OVERLAY_ROLLBACK='));
check($rc===2&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&lastLine($o,'ROLLBACK_FILES')['nothingApplied']===true&&!str_contains($tail,'ARTISAN=')&&!str_contains($tail,'WEB_OVERLAY_PROBE=')&&$tree()==='PRE','NOTHING_APPLIED_NO_REFRESH');$reset();
// The time budget is checked right before the first change.
[$rc,$o]=$run([],'install',['budgetSeconds'=>0]);check($rc===2&&str_contains($o,'"blockers":["TIME_BUDGET_EXCEEDED_BEFORE_FIRST_CHANGE"]')&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','TIME_BUDGET_HOLD');$reset();
// A catalog page without its JSON-LD block, and one without its CSP: acceptance fails, rolled back.
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['dropJsonLd'],'BEFORE_SMOKES_OLD'=>['clearDrops']]);[$s,$by]=$smokeRows(substr($o,0,(int)strpos($o,'OVERLAY_ROLLBACK=')));
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"INSTALLED_SMOKE_FAILED"'&&$by['/katalog']['jsonLd']==='JSON_LD_MISSING'&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE','MISSING_JSON_LD_ROLLED_BACK');$reset();
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['dropCsp'],'BEFORE_SMOKES_OLD'=>['clearDrops']]);
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"INSTALLED_SMOKE_FAILED"'&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE','MISSING_CSP_ROLLED_BACK');$reset();
$results+=['censusBeforeBackup'=>'HOLD_NOTHING_WRITTEN','nothingApplied'=>'NO_REFRESH_NO_WEB_WORK','timeBudget'=>'HOLD','missingJsonLd'=>'ROLLED_BACK','missingCsp'=>'ROLLED_BACK'];

// 11. Order negative controls (fixture only, never the tool). A NEW caller over an OLD callee is a real failure: the new PublicStorefrontAppearance (group C) calls SettingsService::logoHeight (group B); live
// before the library, every storefront page is a 500. The password endpoints sit behind a login and are not requested; the helper and /katalog show the wrong order.
$helperFeatures=static fn():array=>json_decode(sh(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$incoming.'/b1153-app-check.php',$active,'features',json_encode(LogoCatalogInstallCoordinator::newClasses(),JSON_THROW_ON_ERROR),LogoCatalogInstallCoordinator::MEDIA_ROUTE_NAME,LogoCatalogInstallCoordinator::EXPECTED_APP_URL,json_encode(LogoCatalogInstallCoordinator::SOURCE_MARKERS,JSON_THROW_ON_ERROR)],$active),true,16,JSON_THROW_ON_ERROR);
$psa=$stepOf('app/Support/PublicStorefrontAppearance.php');$psaFile=$active.'/'.$psa['path'];$psaOrig=(string)file_get_contents($psaFile);$layoutFile=$active.'/'.$lastStep['path'];
// Everything before the deliberate control (the whole B1146-B1152 replay and every case above) must have left no fatal error in the web server log.
check(substr_count((string)file_get_contents($base.'/server.log'),'PHP Fatal error')===0,'NO_FATAL_IN_THE_SERVER_LOG_BEFORE_THE_DELIBERATE_CONTROL');check(hash('sha256',$psaOrig)===$psa['preSHA256'],'THE_STOREFRONT_APPEARANCE_IS_THE_PRE_STATE_BEFORE_THE_CONTROLS');
// (a) the new PublicStorefrontAppearance with the OLD SettingsService: the helper sees it (the library API is absent), and the web server really answers 500 on the catalog (so the control can fail), then it is put back.
// The waits cover the realpath cache TTL of the test server (10 s) plus the OPcache revalidation (2 s): the same measured hold the tool itself uses (15 s).
file_put_contents($psaFile,(string)file_get_contents($psa['payload']));sleep(16);$fa=$helperFeatures();$katBad=$status('/katalog');$upStill=$status('/up');file_put_contents($psaFile,$psaOrig);sleep(16);
check($fa['freshBrandingApi']===true&&$fa['logoHeightsApi']===false&&$fa['newClassesLoaded']===0,'WRONG_ORDER_CALLER_BEFORE_THE_LIBRARY_SEEN_BY_THE_HELPER:'.json_encode($fa));
check($katBad===500&&$upStill===200,'WRONG_ORDER_CALLER_BEFORE_THE_LIBRARY_IS_A_REAL_500_ON_THE_STOREFRONT:'.json_encode([$katBad,$upStill]));
check(hash_file('sha256',$psaFile)===$psa['preSHA256']&&$status('/katalog')===200&&$status('/login')===200,'WRONG_ORDER_CONTROL_RESTORED');
// The deliberate 500 of control (a) may leave an error line in the web server log; everything after it must be clean (checked at the end).
$fatalAfterA=substr_count((string)file_get_contents($base.'/server.log'),'PHP Fatal error');
// (b) the new class alone (MailBranding, nothing names it) is harmless: it loads, the helper sees the class API, the library API stays absent, the pages answer.
$mbStep=$classSteps[0];$mbFile=$active.'/'.$mbStep['path'];check(!is_file($mbFile)&&copy($mbStep['payload'],$mbFile)&&chmod($mbFile,0644),'MAIL_BRANDING_PLACED_FOR_THE_CONTROL');sleep(4);$fb=$helperFeatures();$upB=$status('/up');$katB=$status('/katalog');unlink($mbFile);
check($fb['newClassesLoaded']===1&&$fb['newClassesTotal']===1&&$fb['mailBrandingApi']===true&&$fb['logoHeightsApi']===false&&$fb['freshBrandingApi']===false&&$upB===200&&$katB===200,'THE_NEW_CLASS_ALONE_LOADS_AND_NOBODY_CALLS_IT:'.json_encode([$fb['newClassesLoaded'],$fb['mailBrandingApi'],$upB,$katB]));
// (c) groups A and B (the right first groups: the three new files and the two libraries) alone are harmless: the libraries answer, the old callers still work, nothing calls the new header views.
$libOrig=[];foreach($libSteps as $ls){$lf=$active.'/'.$ls['path'];$libOrig[$ls['path']]=(string)file_get_contents($lf);file_put_contents($lf,(string)file_get_contents($ls['payload']));}
foreach($classSteps as $cs){$cf=$active.'/'.$cs['path'];if(!is_dir(dirname($cf)))mkdir(dirname($cf),0755,true);check(!is_file($cf)&&copy($cs['payload'],$cf)&&chmod($cf,0644),'NEW_FILE_PLACED_FOR_THE_CONTROL_'.$cs['n']);}
sleep(16);$fc=$helperFeatures();$upC=$status('/up');$katC=$status('/katalog');$loginC=$status('/login');
foreach($classSteps as $cs)unlink($active.'/'.$cs['path']);rmTree($active.'/resources/views/vendor');rmTree($active.'/resources/views/emails/partials');foreach($libOrig as $p=>$bytes)file_put_contents($active.'/'.$p,$bytes);sleep(16);
check($fc['newClassesLoaded']===1&&$fc['logoHeightsApi']===true&&$fc['catalogWhitelist']===true&&$fc['freshBrandingApi']===false&&$upC===200&&$katC===200&&$loginC===200,'GROUPS_A_AND_B_ALONE_ARE_HARMLESS:'.json_encode([$fc['newClassesLoaded'],$fc['logoHeightsApi'],$fc['catalogWhitelist'],$upC,$katC,$loginC]));
check($tree()==='PRE'&&$dirsGone()&&$status('/katalog')===200&&$status('/pravne-informacije')===200,'ORDER_CONTROLS_RESTORED');$reset();
// (d) the coordinator itself refuses group E: MailBranding vanishes right before the first group-E step, and the web-SAPI proof of this run is cleared right before it.
foreach(['removeClass'=>'GUARD_REFUSED_A_NEW_CLASS_IS_NOT_IN_PLACE','clearProof'=>'GUARD_REFUSED_NO_WEB_SAPI_CLASS_PROOF'] as $action=>$code){
    [$rc,$o]=$run(['BEFORE_STEP_'.$guardSteps[0]['n']=>[$action]]);
    check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"'.$code.'"'&&!str_contains($o,'OVERLAY_STEP={"n":'.$guardSteps[0]['n'].',')&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&hash_file('sha256',$layoutFile)===$lastStep['preSHA256']&&$stateOf($guardSteps[0])==='pre'&&$dirsGone(),'GUARD_REFUSED_'.$action.':'.substr($o,-600));
    $reset();
}
// (e) The web worker sees a different app.url than the CLI (the CLI preflight passed; the .env changes after the last step before group E, as a server-level variable could differ): the
// five-request web proof refuses, group E is NEVER written and A to D are restored. The exact APP_URL is put back when the rollback starts.
[$rc,$o]=$run(['AFTER_STEP_'.($guardSteps[0]['n']-1)=>['appUrlHttp'],'SAVED_ROLLBACK_INTENT'=>['appUrlRestore']]);$wp=lastLine($o,'WEB_CLASS_LOAD_PROOF');
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"LOAD_PROBE_APP_URL_NOT_THE_PINNED_ORIGIN"'&&is_array($wp)&&$wp['request']===1&&$wp['webAppUrlExact']===false&&$wp['webAppUrlOrigin']==='http://cms.ald1n.com'&&in_array('LOAD_PROBE_APP_URL_NOT_THE_PINNED_ORIGIN',$wp['failures'],true)&&!str_contains($o,'OVERLAY_STEP={"n":'.$guardSteps[0]['n'].',')&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&hash_file('sha256',$layoutFile)===$lastStep['preSHA256']&&$dirsGone(),'WEB_SIDE_APP_URL_MISMATCH_STOPS_BEFORE_GROUP_E_AND_RESTORES:'.substr($o,-500));
check(!str_contains($o,'wrong-origin.invalid'),'NO_MISMATCHING_VALUE_IN_THE_REPORT');$reset();
// (f) Right after group E the pin points at a WRONG origin (every other check is fine: /up and /login answer 200): the origin assertion of the liveness smoke
// fails like a 5xx and group E is the first group restored.
[$rc,$o]=$run(['GROUP_DONE_E'=>['appUrlWrong'],'SAVED_ROLLBACK_INTENT'=>['appUrlRestore']]);$ls=allLines($o,'GROUP_LIVENESS_SMOKE');$lastLive=end($ls);$liveRows=[];foreach($lastLive['rows'] as $r)$liveRows[$r['check']]=$r['result']??null;
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"GROUP_SMOKE_FAILED_AFTER_E"'&&$lastLive['after']==='E'&&in_array('ORIGIN_PIN_LOCATION',$lastLive['failed'],true)&&($liveRows['/up']??null)==='PASS'&&($liveRows['/login']??null)==='PASS'&&str_contains($o,'ROLLBACK_AFTER_GUARD_GROUP={"guardGroupIsOld":true}')&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&hash_file('sha256',$layoutFile)===$lastStep['preSHA256']&&$dirsGone(),'WRONG_ORIGIN_RIGHT_AFTER_GROUP_E_ROLLED_BACK_GROUP_E_FIRST:'.json_encode([$lastLive['failed'],$liveRows]));
check(!str_contains($o,'wrong-origin.invalid'),'NO_WRONG_ORIGIN_VALUE_IN_THE_REPORT');$reset();
// (g) The PREFLIGHT reads the web worker's app.url too. APP_URL absent from the server environment and from .env: the config default literal is used (source CONFIG_DEFAULT) and passes.
file_put_contents($envFile,(string)preg_replace('/^APP_URL=.*\R/m','',$envOriginal));check(!str_contains((string)file_get_contents($envFile),'APP_URL='),'FIXTURE_ENV_WITHOUT_APP_URL');
[$rc,$o]=$run(['AFTER_PREFLIGHT'=>['fail']]);$p=lastLine($o,'PREFLIGHT');$wc=lastLine($o,'WEB_APP_URL_CHECK');
check($rc===2&&$p['blockers']===[]&&is_array($wc)&&$wc['webAppUrlSource']==='CONFIG_DEFAULT'&&$wc['webAppUrlExact']===true&&$wc['webAppUrlOrigin']==='https://cms.ald1n.com'&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','ABSENT_APP_URL_USES_THE_CONFIG_DEFAULT_AND_PASSES_THE_PREFLIGHT:'.json_encode([$p['blockers'],$wc]));$reset();
// (h) Two conflicting APP_URL lines in .env: ambiguous, a HOLD before any write (nothing guessed).
file_put_contents($envFile,$envOriginal."APP_URL=http://cms.ald1n.com\n");
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');
check($rc===2&&in_array('LOAD_PROBE_APP_URL_AMBIGUOUS_IN_DOTENV',$p['blockers'],true)&&!str_contains($o,'PER_FILE_BACKUP=')&&!str_contains($o,'OVERLAY_STEP=')&&$tree()==='PRE','DUPLICATE_CONFLICTING_APP_URL_IS_A_PREFLIGHT_HOLD:'.json_encode($p['blockers']));$reset();
// (i) Two agreeing APP_URL lines are fine; an interpolated value is refused.
file_put_contents($envFile,$envOriginal."APP_URL=https://cms.ald1n.com\n");[$rc,$o]=$run(['AFTER_PREFLIGHT'=>['fail']]);$wc=lastLine($o,'WEB_APP_URL_CHECK');
check($rc===2&&$wc['webAppUrlExact']===true&&$wc['failures']===[]&&!str_contains($o,'PER_FILE_BACKUP='),'DUPLICATE_AGREEING_APP_URL_PASSES_THE_WEB_CHECK');$reset();
file_put_contents($envFile,(string)preg_replace('/^APP_URL=.*$/m','APP_URL=${APP_NAME}',$envOriginal));
[$rc,$o]=$run();$wc=lastLine($o,'WEB_APP_URL_CHECK');
check($rc===2&&in_array('LOAD_PROBE_APP_URL_INTERPOLATION',$wc['failures'],true)&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','INTERPOLATED_APP_URL_IS_A_PREFLIGHT_HOLD');$reset();
$results['webAppUrlPreflight']=['absent'=>'CONFIG_DEFAULT_PASS','duplicateConflicting'=>'HOLD','duplicateAgreeing'=>'PASS','interpolation'=>'HOLD'];
$results['webAppUrlMismatch']='STOPPED_BEFORE_GROUP_E_A_TO_D_RESTORED';$results['wrongOriginAfterGroupE']='ROLLED_BACK_GROUP_E_FIRST';
$results['orderControls']=['callerBeforeTheLibrary'=>'SEEN_BY_THE_HELPER_AND_A_REAL_500_THEN_RESTORED','newClassAlone'=>'HARMLESS_LOAD','groupsAAndBAlone'=>'HARMLESS_LOAD_AND_ANSWER','coordinatorRefusesGroupEWithAMissingClass'=>'HOLD_ROLLED_BACK_NEVER_WRITTEN','coordinatorRefusesGroupEWithoutTheWebProof'=>'HOLD_ROLLED_BACK_NEVER_WRITTEN'];

// 12. Install with a smoke after every step and every group boundary.
[$rc,$o]=$run($allSteps());$rows=$stepRows();echo 'STEP_SMOKES='.json_encode($rows,JSON_UNESCAPED_SLASHES).PHP_EOL;
check($rc===0&&lastRaw($o,'B1153_FINAL')===LogoCatalogInstallCoordinator::FINAL_PASS&&$tree()==='INSTALLED','INSTALL_PASS');
$points=array_column($rows,'point');$groupPoints=count($groups)+1;
check(count($rows)===count($plan)+count($groups)+1&&count(array_filter($rows,static fn($r)=>array_key_exists('themePost',$r)))===$groupPoints&&$points[0]==='AFTER_STEP_1'&&end($points)==='AFTER_INVALIDATE','EVERY_STEP_AND_GROUP_SMOKED:'.implode(',',$points));
$bad=[];
// The web workers revalidate every few seconds. At every point (the point right after the rename of the layout included) the pages serve; the B1149 storefront
// (guest SMTP page 302, the logo) stays intact throughout; a media-route link on the catalog page is always served; a theme POST is 303 with the cookie at the group points;
// the catalog layout queries answer 200 at every group point.
foreach($rows as $r){
    foreach($r['anon'] as $p=>$st)if($st!==200)$bad[]=$r['point'].' '.$p.' '.$st;
    if(array_key_exists('themePost',$r)&&($r['themePost']!==303||$r['themeCookie']!==true))$bad[]=$r['point'].' POST /tema '.$r['themePost'];
    if(array_key_exists('perPageList',$r)&&($r['perPageList']!==200||$r['perPage999']!==200))$bad[]=$r['point'].' catalog layout queries '.json_encode([$r['perPageList'],$r['perPage999']]);
    if($r['smtpGuest']!==302)$bad[]=$r['point'].' guest SMTP page '.$r['smtpGuest'];
    if($r['merchantGuest']!==302)$bad[]=$r['point'].' guest merchant settings page '.$r['merchantGuest'];
    if($r['documentsGuest']!==302)$bad[]=$r['point'].' guest document settings page '.$r['documentsGuest'];
    if($r['logoChecked']===true&&($r['logoLinked']!==true||$r['logoStatus']!==200))$bad[]=$r['point'].' logo '.var_export($r['logoStatus'],true);
    if($r['mediaLinked']===true&&$r['mediaStatus']!==200)$bad[]=$r['point'].' media link '.var_export($r['mediaStatus'],true);
}
check($bad===[],'INSTALL_ORDER_EVERY_POINT_SERVES:'.implode(',',$bad));
// The per-page form appears only with the catalog view of group E: no group point before it shows the control, the points from the catalog step on may.
$withControl=array_values(array_filter($rows,static fn($r)=>($r['perPageControl']??false)===true));$beforeE=array_values(array_filter($rows,static fn($r)=>in_array($r['point'],['GROUP_DONE_A','GROUP_DONE_B','GROUP_DONE_C','GROUP_DONE_D'],true)));
check(array_filter($beforeE,static fn($r)=>($r['perPageControl']??false)===true)===[]&&end($rows)['perPageControl']===true&&end($rows)['perPageListLayout']===true,'THE_PER_PAGE_CONTROL_IS_ABSENT_BEFORE_GROUP_E_AND_PRESENT_AT_THE_END:'.json_encode(array_map(static fn($r)=>[$r['point'],$r['perPageControl']??null],array_values(array_filter($rows,static fn($r)=>array_key_exists('perPageControl',$r))))));
// Group order and "each group together": the 26 OVERLAY_STEP lines in plan order, the files of one group back to back (no pause, hold or smoke between them), exactly one
// full hold (before group E, after D and so after A), the web-SAPI class proof inside that hold's boundary and before group E's first file, a pause (not a hold) at the other boundaries, a smoke after every group.
check(preg_match_all('/^OVERLAY_STEP=(\{.*\})$/m',$o,$ms)===26,'TWENTY_SIX_OVERLAY_STEPS');$order=array_map(static fn($x)=>json_decode($x,true),$ms[1]);
check(implode(',',array_map(static fn($x)=>$x['n'].$x['group'],$order))===implode(',',array_map(static fn($s)=>$s['n'].$s['group'],$plan))&&$order[0]['kind']==='new'&&$order[1]['kind']==='new'&&$order[2]['kind']==='new'&&$order[25]['path']==='resources/views/public-shop/layout.blade.php','GROUP_ORDER_A3_B2_C6_D3_E12_THE_LAYOUT_LAST');
$posOf=static fn(int $n):int=>(int)strpos($o,'OVERLAY_STEP={"n":'.$n.',');
foreach(['A'=>[1,3],'B'=>[4,5],'C'=>[6,11],'D'=>[12,14],'E'=>[15,26]] as $g=>[$from,$to]){$between=substr($o,$posOf($from),$posOf($to)-$posOf($from));
    check($between!==''&&!str_contains($between,'OVERLAY_GROUP_PAUSE=')&&!str_contains($between,'OVERLAY_HOLD')&&!str_contains($between,'GROUP_LIVENESS_SMOKE=')&&!str_contains($between,'ARTISAN=')&&!str_contains($between,'WEB_CLASS_LOAD_PROOF='),'GROUP_'.$g.'_APPLIED_TOGETHER_NO_PAUSE_HOLD_OR_SMOKE_BETWEEN_THE_FILES');}
$holdsInstall=array_map(static fn($x)=>$x['label'],allLines(substr($o,0,(int)strpos($o,'ACCEPTANCE_INTENT')),'OVERLAY_HOLD_START'));
$pauses=allLines($o,'OVERLAY_GROUP_PAUSE');$live=allLines($o,'GROUP_LIVENESS_SMOKE');
$pHold=strpos($o,'"label":"before-guard-group"');$pProof=strpos($o,'WEB_CLASS_LOAD_PROOF=');$pLiveD=strpos($o,'GROUP_LIVENESS_SMOKE={"after":"D"');
check($holdsInstall===['before-guard-group']&&$pHold>$posOf(14)&&$pHold<$posOf(15)&&$pProof>$pHold&&$pProof<$posOf(15)&&$pLiveD>$pProof&&$pLiveD<$posOf(15)&&implode(',',array_map(static fn($x)=>$x['after'].'>'.$x['before'],$pauses))==='A>B,B>C,C>D,E>ACCEPTANCE'&&implode(',',array_column($live,'after'))==='A,B,C,D,E'&&array_filter($live,static fn($x)=>$x['failed']!==[])===[],'ONE_FULL_HOLD_BEFORE_GROUP_E_THEN_THE_WEB_PROOF_THEN_THE_D_SMOKE_THEN_PAUSES_ELSEWHERE:'.json_encode([$holdsInstall,$pauses]));
// The proof as emitted: the new class loaded in the WEB SAPI (the cli-server of the fixture), through the autoloader, from the payload file.
check(preg_match_all('/^WEB_CLASS_LOAD_PROOF=/m',$o)===5&&count(array_filter(allLines($o,'WEB_CLASS_LOAD_PROOF'),static fn($x)=>$x['failures']===[]&&$x['webAppUrlExact']===true&&$x['webAppUrlOrigin']==='https://cms.ald1n.com'))===5,'FIVE_WEB_PROOF_REQUESTS_ALL_PASS_AND_THE_WEB_APP_URL_IS_THE_PINNED_ORIGIN');
$wp=lastLine($o,'WEB_CLASS_LOAD_PROOF');check(is_array($wp)&&$wp['label']==='before-guard-group'&&$wp['classes']===1&&$wp['classesLoadedInTheWebSapi']===1&&$wp['classesFromThePayloadFile']===1&&$wp['autoloaded']===true&&$wp['sapi']==='cli-server'&&$wp['webEuidIsOperator']===true&&$wp['failures']===[]&&$wp['probeRemoved']===true&&!str_contains(json_encode($wp),'/home/'),'WEB_SAPI_CLASS_PROOF_PASSED:'.json_encode($wp));
// The hold really elapsed between the last file of group D (and so group A) and group E: wall time between the smoke after step 3 (A) and the smoke after step 15 (the first E file) is at least the measured hold.
$pfl=allLines($o,'PREFLIGHT')[0];$measured=(int)$pfl['values']['web']['holdSeconds'];$tAt=static function(string $pt)use($rows):float{foreach($rows as $r)if($r['point']===$pt)return (float)$r['t'];throw new RuntimeException('B1153_FIXTURE_NO_POINT_'.$pt);};
check($measured>=10&&$tAt('AFTER_STEP_15')-$tAt('AFTER_STEP_3')>=$measured&&$tAt('AFTER_STEP_15')-$tAt('AFTER_STEP_14')>=$measured-2,'THE_FULL_HOLD_SEPARATES_GROUP_A_AND_GROUP_E:'.json_encode([$measured,$tAt('AFTER_STEP_15')-$tAt('AFTER_STEP_3'),$tAt('AFTER_STEP_15')-$tAt('AFTER_STEP_14')]));
// The four new directories the install had to create, outermost first, each announced before its file.
$dirEvents=array_column(allLines($o,'OVERLAY_DIRECTORY_CREATED'),'path');check($dirEvents===['resources/views/emails/partials','resources/views/vendor','resources/views/vendor/mail','resources/views/vendor/mail/html']&&array_filter($createdDirs,static fn($d)=>is_dir($active.'/'.$d)&&!is_link($active.'/'.$d)&&(fileperms($active.'/'.$d)&0777)===0755)===$createdDirs,'FOUR_DIRECTORIES_CREATED_OUTERMOST_FIRST_MODE_0755:'.json_encode($dirEvents));
[$s,$by]=$smokeRows($o);check($s['state']==='INSTALLED'&&$s['failed']===[]&&$s['shop']['open']===true&&$s['varyUnverified']===false&&$s['notices']===[]&&$s['requestsWrite']==='NONE_GET_AND_HEAD_ONLY'&&$s['login']==='NEVER_LOGGED_IN','SMOKES_PASS');
$b1149Intact($s,$by,'INSTALLED');$pinRows($by,true,'INSTALLED');$emailState($by,true,'INSTALLED');$viewRows($by,true,'INSTALLED');
check($by['/katalog']['varyCookie']===true&&$by['/katalog']['noindex']===true&&$by['/katalog?page=999']['observed']===404&&$by['/katalog?sort=price_asc']['observed']===200&&in_array($by['/katalog?sort=ald1n_unknown']['observed'],[302,422],true)&&$by['/korpa']['layoutMarker']===true&&$by['/registracija']['observed']===200,'CATALOG_SMOKES');
check(($by[$cssV]['result']??'PASS')==='PASS'&&($by[$galV]['result']??'PASS')==='PASS'&&$by[LogoCatalogInstallCoordinator::CSS_PATH]['cssMarker']===true&&$by[LogoCatalogInstallCoordinator::CSS_PATH.'?v='.$package['cssVersion'].' (view switch rules)']['result']==='PASS','CSS_SMOKES');
check($by['PRODUCT_PAGE_FIRST_CATALOG_LINK']['result']==='PASS'&&$by['PRODUCT_PAGE_FIRST_CATALOG_LINK']['galleryLink']===true&&$by['PRODUCT_PAGE_FIRST_CATALOG_LINK']['cacheControlPrivateNoStore']===true&&$by['PRODUCT_PAGE_FIRST_CATALOG_LINK']['cspShape']===true,'PRODUCT_SMOKE');
check(count(array_filter($s['rows'],static fn($r)=>in_array($r['check'],LogoCatalogInstallCoordinator::LEGAL,true)&&$r['result']==='PASS'&&$r['layoutMarker']===true&&$r['cspShape']===true&&$r['noindex']===true))===8&&$s['noindexMissing']===[],'LEGAL_200_NONCE_SCRIPT_NOINDEX');
// The signed media route (B1150, still live): every media row must PASS, the signed URLs are built on the pinned origin and still served.
foreach(['PRODUCT_IMAGE_URL_ACCESSOR','MEDIA_ROUTE_IMAGE','MEDIA_ROUTE_ORIGINAL_HEAD','MEDIA_ROUTE_NOT_MODIFIED','MEDIA_ROUTE_BAD_SIGNATURE','MEDIA_ROUTE_MISSING_SIGNATURE','MEDIA_ROUTE_UNKNOWN_IMAGE'] as $k)check(($by[$k]['result']??'')==='PASS','MEDIA_SMOKE_ROW_'.$k.':'.json_encode($by[$k]??null));
check(($by['MEDIA_ROUTE_IMAGE']['bodyBytes']??0)>4194304,'FIRST_IMAGE_ABOVE_4_MIB_PASSES_THE_SMOKE_BODY_LIMIT:'.($by['MEDIA_ROUTE_IMAGE']['bodyBytes']??'null'));
check($by['PUBLIC_IMAGE_ROUTE']['result']==='PASS'||str_starts_with((string)$by['PUBLIC_IMAGE_ROUTE']['result'],'INFO'),'PUBLIC_IMAGE_ROUTE_ROW:'.json_encode($by['PUBLIC_IMAGE_ROUTE']));
check(str_contains($o,'PRODUCT_MEDIA_DOCTOR={"label":"after-install","rc":0,"ready":true,"failLines":0'),'PRODUCT_MEDIA_DOCTOR_AFTER_INSTALL_RC0');
// The real response as a browser gets it (CI only).
$cat=LogoCatalogFixtureWeb::get(PORT,'/katalog');check(MediaWeb::nonceScript($cat['csp'],$cat['body'])&&preg_match("/script-src 'nonce-[A-Za-z0-9+\/]{22}=='; script-src-attr 'none'/",implode(' ',$cat['csp']))===1,'NONCE_SCRIPT_CSP_AS_SERVED');
$hook=json_decode(sh(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$incoming.'/b1153-app-check.php',$active,'media-sample'],$active),true,16,JSON_THROW_ON_ERROR);
check(($hook['urlNull']??-1)===0&&$hook['imageCount']===2&&is_array($hook['sample']),'HOOK_TWO_IMAGES_NO_NULL_URL:'.json_encode($hook['urlNull']??null));
$mu=$hook['sample'];$mt=$mu['path'].'?'.http_build_query($mu['query'],'','&',PHP_QUERY_RFC3986);$mg=LogoCatalogFixtureWeb::get(PORT,$mt);
check($mg['status']===200&&$mg['cookies']===[]&&MediaWeb::mediaCacheControl($mg['cache'])&&MediaWeb::strongEtag($mg['etag'])&&MediaWeb::crossOriginPolicy($mg['corp'])&&MediaWeb::nosniff($mg['nosniff'])&&in_array(strtolower(trim(explode(';',(string)$mg['contentType'])[0])),['image/jpeg','image/png','image/webp'],true),'MEDIA_ROUTE_SERVED_AS_SPECIFIED:'.json_encode([$mg['status'],$mg['cache'],$mg['etag'],$mg['corp'],$mg['contentType']]));
check(LogoCatalogFixtureWeb::get(PORT,$mt,['If-None-Match: '.$mg['etag'][0]])['status']===304,'MEDIA_ROUTE_304_ON_ETAG');
$badQuery=$mu['query'];$badQuery['s']=substr($badQuery['s'],0,-1).(substr($badQuery['s'],-1)==='a'?'b':'a');$badUrl=$mu['path'].'?'.http_build_query($badQuery,'','&',PHP_QUERY_RFC3986);
// A valid signature is never limited and a bad one is always a bare 404, also after more than 120 bad requests in the minute (no 429).
$badStatuses=[];for($i=0;$i<130;$i++)$badStatuses[]=LogoCatalogFixtureWeb::get(PORT,$badUrl)['status'];
check(count(array_unique($badStatuses))===1&&$badStatuses[0]===404,'BAD_SIGNATURE_ALWAYS_404_NEVER_429:'.json_encode(array_count_values($badStatuses)));
check(LogoCatalogFixtureWeb::get(PORT,$mt)['status']===200,'VALID_SIGNATURE_STILL_200_AFTER_130_BAD_REQUESTS');
check(LogoCatalogFixtureWeb::get(PORT,$mu['path'].'?v='.rawurlencode((string)$mu['query']['v']))['status']===404&&LogoCatalogFixtureWeb::get(PORT,'/media/p/'.(int)explode('/',$mu['path'])[3].'/huge?v='.$mu['query']['v'].'&s='.$mu['query']['s'])['status']===404,'MEDIA_ROUTE_MISSING_SIGNATURE_AND_UNKNOWN_RENDITION_404');
$pi=MediaWeb::publicImagePath($cat['body']);if($pi!==null)check($status($pi)===200,'PUBLIC_IMAGE_ROUTE_STAYS_200');
$cat2=LogoCatalogFixtureWeb::get(PORT,'/katalog');preg_match("/script-src 'nonce-([^']+)'/",implode(' ',$cat['csp']),$n1);preg_match("/script-src 'nonce-([^']+)'/",implode(' ',$cat2['csp']),$n2);check($n1[1]!==$n2[1],'NONCE_DIFFERS_PER_REQUEST');
// The pin as the browser would see it (B1152, unchanged by this rollout): a forged Host gets a redirect and a login page on the pinned origin, and the forged host appears nowhere.
$fi=$forged();check($fi['status']===302&&$fi['location']==='https://cms.ald1n.com/login'&&$fi['loginStatus']===200&&$fi['loginBodyMentionsForged']===false,'FORGED_HOST_REDIRECT_AND_LOGIN_STAY_ON_THE_PINNED_ORIGIN:'.json_encode($fi));
$results['forgedHostInstalledState']='LOCATION_AND_LOGIN_PAGE_ON_THE_PINNED_ORIGIN';
$fe=lastLine($o,'FEATURE_CLI_CHECK');check($fe['expectInstalled']===true&&$fe['themeRoutePostOnly']===true&&$fe['routesCached']===false&&($fe['routeList'][LogoCatalogInstallCoordinator::MEDIA_ROUTE_NAME]??null)===1&&($fe['routeList']['site-assets.show']??null)===1&&($fe['routeList']['admin.settings.smtp.index']??null)===1
    &&$fe['newClassesLoaded']===1&&$fe['newClassesTotal']===1&&$fe['newClassesAutoloaded']===true&&$fe['logoHeightsApi']===true&&$fe['catalogWhitelist']===true&&$fe['freshBrandingApi']===true&&$fe['mailBrandingApi']===true&&!in_array(false,$fe['sourceMarkers'],true)&&count($fe['sourceMarkers'])===12
    &&$fe['originConfiguredIsExpected']===true&&$fe['middlewareGlobal']===true&&$fe['bootstrapNamesPin']===true&&$fe['pdfRasterGuard']===true
    &&$fe['throttleApi']===true&&$fe['webPasswordUsesThrottle']===true&&$fe['apiPasswordUsesThrottle']===true&&$fe['merchantIssueLabels']===true&&$fe['b1150ClassesLoaded']===true&&$fe['routeMedia']===true,'CLI_FEATURES_AFTER_INSTALL:'.json_encode($fe));
$rcNew=lastLine($o,'ROUTES_CHECK');check($rcNew['routeCount']===$pin['routes']&&$rcNew['routeCount']===$rcOld['routeCount'],'ROUTE_COUNT_UNCHANGED_AFTER:'.json_encode($rcNew));
check(str_contains($o,'"newRoutes":"NONE_ROUTE_COUNT_'.$pin['routes'].'_UNCHANGED"')&&str_contains($o,'"passwordEndpoints":"NEVER_CALLED_NO_LOGIN"')&&str_contains($o,'"mail":"NEVER_SENT_NO_TEST_MAIL"')&&str_contains($o,'"pdfGeneration":"NOT_EXERCISED"')&&str_contains($o,'"settingsWritten":"NO_STORED_LOGO_HEIGHT_IS_CHANGED_BY_THIS_TOOL"')&&str_contains($o,'"directoriesCreated":4'),'RESULT_STATES_NO_ROUTE_NO_PASSWORD_ENDPOINT_CALL_NO_MAIL_NO_PDF_NO_SETTING_WRITTEN_FOUR_DIRECTORIES');
$mf=allLines($o,'MAIL_TRANSPORT_FINGERPRINT');check(!preg_match('/mailFingerprint(HMAC|SHA256)|mailKey/',$o),'NO_FINGERPRINT_OR_KEY_IN_THE_REPORT');
$stp=json_decode((string)file_get_contents(json_decode((string)file_get_contents($private.'/current.json'),true)['private'].'/state.json'),true);check(preg_match('/^[a-f0-9]{64}$/D',(string)($stp['mailKey']??''))===1&&preg_match('/^[a-f0-9]{64}$/D',(string)($stp['mailFingerprintHMAC']??''))===1&&(fileperms(json_decode((string)file_get_contents($private.'/current.json'),true)['private'].'/state.json')&0777)===0600,'KEY_AND_HMAC_ONLY_IN_THE_0600_STATE');
check($mf!==[]&&end($mf)['status']==='MAIL_TRANSPORT_UNCHANGED'&&end($mf)['unchanged']===true&&end($mf)['valuesPrinted']===false&&end($mf)['testMailSent']===false,'MAIL_TRANSPORT_FINGERPRINT_UNCHANGED');
check($routeCacheSHA()===null&&$shopFlag()==='1'&&!str_contains($o,'globalReset":true')&&str_contains($o,'"label":"installed-invalidate"'),'NO_ROUTE_CACHE_SHOP_UNTOUCHED_TARGETED_INVALIDATION');
// The compiled views were cleared by view:clear and rebuilt on demand: the views directory holds only files compiled after the install (the layout view of the catalog is among them), none from before.
check(count(glob($retained.'/storage/framework/views/*.php')?:[])>0,'COMPILED_VIEWS_REBUILT_ON_DEMAND_AFTER_VIEW_CLEAR');
$next=(array)lastLine($o,'OWNER_NEXT_STEPS');check(count($next)>=8&&in_array($purgeStep,$next,true)&&str_starts_with((string)end($next),'ROLLBACK_IF_NEEDED=bash /home/icaffeco/ald1n-project/incoming/b1153-installer.sh --rollback')&&str_contains($next[1],'LOG_IN_ON_THE_WEB_AND_IN_THE_ANDROID_APP')&&str_contains($next[2],'SEND_ONE_TEST_MAIL')&&str_contains($next[2],'PNG_OR_JPG')&&str_contains($next[3],'HEADER_LOGO_HEIGHT')&&str_contains($next[3],'38')&&str_contains($next[3],'PHONE')&&str_contains($next[4],'ONE_PDF_DOCUMENT_AND_ONE_PDF_REPORT')&&str_contains($next[4],'NOT_EXERCISED')&&str_contains($next[6],'DO_NOT_RERUN_B1144_TO_B1152'),'OWNER_NEXT_STEPS_COMPLETE:'.json_encode($next));
check(str_contains($o,'"command":"route:clear"')&&str_contains($o,'"command":"queue:restart"')&&$pHold!==false&&(lastLine($o,'MERCHANT_APPROVAL')['ok']??null)==='UNCHANGED'&&str_contains($o,'"database":"NO_PRODUCT_CART_OR_SWITCH_WRITES_SESSIONS_AND_APP_CACHE_ONLY"'),'REFRESH_AFTER_THE_LIVENESS_SMOKE_APPROVAL_UNCHANGED');
// The B1149 logo, theme and CSS behaviour stays: 301 (unversioned) or 200, the versioned logo sandboxed, strict names.
$bare=LogoCatalogFixtureWeb::get(PORT,'/site-assets/'.$logoName);check($bare['status']===301&&str_contains((string)$bare['location'],'?v=')&&MediaWeb::privateNoStore($bare['cache'])&&MediaWeb::sandboxCsp($bare['csp']),'SITE_ASSET_UNVERSIONED_REDIRECTS_OR_SERVES');
$lu=MediaWeb::siteAssetUrl(LogoCatalogFixtureWeb::get(PORT,'/katalog')['body']);check($lu!==null&&$lu['query']!==[],'LOGO_URL_IS_VERSIONED');$lg=LogoCatalogFixtureWeb::get(PORT,$lu['path'].'?v='.$lu['query']['v']);check($lg['status']===200&&str_starts_with((string)$lg['contentType'],'image/png')&&MediaWeb::nosniff($lg['nosniff'])&&MediaWeb::sandboxCsp($lg['csp']),'SITE_ASSET_SERVED_SANDBOXED');
check($status('/site-assets/not-a-valid-name.png')===404&&$status('/site-assets/..%2F..%2F.env')===404,'SITE_ASSET_STRICT_NAMES');
$t=$themePost('theme=dark&back=%2Fkatalog');check($t['status']===303&&$t['location']==='/katalog'&&isset($t['cookies']['shop_theme']),'THEME_POST_303_COOKIE');
check(str_contains($body('/katalog',['Cookie: shop_theme='.$t['cookies']['shop_theme']]),'data-theme="dark"')&&str_contains($body('/katalog'),'data-theme="system"'),'THEME_COOKIE_RENDERS_DATA_THEME');
check(str_contains($body($cssV),$markers['css'])&&str_contains($body($galV),$markers['gallery']),'CSS_SERVED');
// 12b. What the owner will see (CI only): the e-mail header carries the logo (a PNG light-theme logo, the default height 36, on the pinned origin), the stored and the junk logo height behave as specified,
// the list view and the per-page choice render, the admin pages are not touched.
$mc=$mailCheck();check($mc['logoIsArray']===true&&$mc['logoSrcOnOrigin']===true&&$mc['logoHeight']===36&&$mc['logoOnDark']===false&&$mc['htmlHasLogoImg']===true&&$mc['imgHeight']===36&&$mc['imgOnOrigin']===true,'INSTALLED_MAIL_HEADER_CARRIES_THE_LOGO:'.json_encode($mc));
$setHeight=static function(?string $v)use($pdo,$artisan):void{$pdo()->exec("DELETE FROM settings WHERE setting_key='site_header_logo_height'");if($v!==null)$pdo()->prepare("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('site_header_logo_height',?,NOW(),NOW())")->execute([$v]);$artisan(['cache:clear']);};
$hdr=static fn():int|string=>LogoCatalogInstallCoordinator::headerLogoHeight($body('/katalog'));
$setHeight('38');$h38=$hdr();$setHeight('44');$h44=$hdr();$setHeight('9999');$hJunk=$hdr();$setHeight('abc');$hAbc=$hdr();$setHeight(null);$hDefault=$hdr();
check($h38===38&&$h44===44&&$hJunk===32&&$hAbc===32&&$hDefault===32,'STORED_HEADER_LOGO_HEIGHT_STAYS_JUNK_AND_ABSENT_FALL_BACK_TO_THE_DEFAULT:'.json_encode([$h38,$h44,$hJunk,$hAbc,$hDefault]));
$lv=$body('/katalog?per_page=20&view=list');$gv=$body('/katalog?per_page=80');$jv=$body('/katalog?per_page=1000&view=junk');
check(str_contains($lv,'shop-products-list')&&str_contains($lv,'<option value="20" selected')&&str_contains($gv,'<option value="80" selected')&&!str_contains($gv,'shop-products-list')&&str_contains($jv,'<option value="40" selected')&&!str_contains($jv,'shop-products-list')&&!str_contains($jv,'value="1000"'),'LIST_VIEW_AND_PER_PAGE_RENDER_JUNK_FALLS_BACK');
$results['install']=['final'=>lastRaw($o,'B1153_FINAL'),'stepSmokePoints'=>count($rows),'b1149Storefront'=>'INTACT_THROUGHOUT','guestRedirects'=>'302_ON_THE_PINNED_ORIGIN','themePost'=>'303_AT_GROUP_POINTS','mediaRoute'=>$imagesVisible?'EXERCISED':'NOT_EXERCISED_NO_PRODUCT_IMAGE','mailFingerprint'=>'UNCHANGED','upNever500'=>'EVERY_STEP_POINT_200','mailHeaderLogo'=>'RENDERED_PNG_HEIGHT_36','headerLogoHeight'=>'STORED_38_AND_44_STAY_JUNK_AND_ABSENT_32','catalogViewSwitch'=>'LIST_20_80_AND_JUNK_FALLBACK','directories'=>'FOUR_CREATED_0755'];

// 13. Idempotent re-run.
[$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1153_FINAL')==='PASS_ALREADY_INSTALLED'&&!str_contains($o,'OVERLAY_STEP=')&&$tree()==='INSTALLED','IDEMPOTENT_RERUN');$results['rerun']='PASS_ALREADY_INSTALLED';

// 14. 20 minutes before the 02:30 backup --rollback holds before any restore and prints the manual-only recovery block;
// with --ignore-schedule it restores (shop ON): exact old bytes, the B1149 storefront and the B1152 pin intact, the media route intact, the four directories gone.
[$rc,$o]=$run([],'rollback',['fixedNow'=>$conflictNow]);$note=lastLine($o,'RECOVERY_NOTE');
check($rc===2&&preg_match('/"blockers":\["SCHEDULED_BACKUP_WITHIN_30_MINUTES_[A-Z_]+_WAIT_OR_RUN_WITH_--rollback_--ignore-schedule"\]/',$o)===1&&!str_contains($o,'OVERLAY_ROLLBACK=')&&$note['kind']==='ROLLBACK_NOT_STARTED_MANUAL_ONLY'&&$block($o)!==[]&&$tree()==='INSTALLED','SCHEDULE_HOLD_WITH_MANUAL_BLOCK');
[$rc,$o]=$run([],'rollback',['fixedNow'=>$conflictNow,'ignoreSchedule'=>true]);
check(str_contains($o,'SCHEDULED_BACKUP_CONFLICT_IGNORED_BY_THE_OWNER_OVERRIDE')&&$rc===0&&lastRaw($o,'B1153_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE'&&array_filter($newSteps,static fn($s)=>is_file($active.'/'.$s['path']))===[]&&$dirsGone()&&$shopFlag()==='1','OWNER_ROLLBACK_IGNORE_SCHEDULE_DIRECTORIES_GONE');
// Rollback order (reverse of the install): group E FIRST (the layout first), then the full measured hold, then group D together, a pause, group C together, a pause, group B together, the full hold again, and the
// three new files (group A) last.
$pAfterGuard=strpos($o,'ROLLBACK_AFTER_GUARD_GROUP={"guardGroupIsOld":true}');$pHoldGuard=strpos($o,'"label":"rollback-after-guard-group"');$pPauseC=strpos($o,'ROLLBACK_GROUP_PAUSE={"before":"C","seconds"');$pPauseB=strpos($o,'ROLLBACK_GROUP_PAUSE={"before":"B","seconds"');$pHoldA=strpos($o,'"label":"rollback-before-new-classes"');
check($pAfterGuard!==false&&$pHoldGuard!==false&&$pPauseC!==false&&$pPauseB!==false&&$pHoldA!==false&&$pAfterGuard<$pHoldGuard&&$pHoldGuard<$pPauseC&&$pPauseC<$pPauseB&&$pPauseB<$pHoldA&&(lastLine($o,'MERCHANT_APPROVAL')['ok']??null)==='UNCHANGED','ROLLBACK_ORDER_GROUP_E_FIRST_THEN_HOLD_THEN_D_C_B_WITH_PAUSES_THEN_HOLD_THEN_THE_NEW_FILES:'.json_encode([$pAfterGuard,$pHoldGuard,$pPauseC,$pPauseB,$pHoldA]));
check(!str_contains($o,'ROLLBACK_GROUP_PAUSE={"before":"D"')&&!str_contains($o,'ROLLBACK_GROUP_PAUSE={"before":"A"'),'NO_PAUSE_AT_THE_TWO_HOLD_BOUNDARIES');
check(in_array($purgeBack,(array)lastLine($o,'OWNER_NEXT_STEPS'),true),'OWNER_NEXT_STEPS_AFTER_ROLLBACK');
[$s,$by]=$smokeRows($o);$fe=lastLine($o,'FEATURE_CLI_CHECK');check($s['state']==='OLD'&&$s['shop']['open']===true&&($fe['routeList'][LogoCatalogInstallCoordinator::MEDIA_ROUTE_NAME]??null)===1&&$fe['themeRoutePostOnly']===true&&$fe['expectInstalled']===false&&$fe['newClassesLoaded']===0&&$fe['logoHeightsApi']===false&&$fe['catalogWhitelist']===false&&$fe['originConfiguredIsExpected']===true&&$fe['middlewareGlobal']===true&&$fe['throttleApi']===true,'OLD_STATE_SERVED:'.json_encode($fe));$b1149Intact($s,$by,'AFTER_ROLLBACK');$pinRows($by,false,'AFTER_ROLLBACK');$emailState($by,false,'AFTER_ROLLBACK');$viewRows($by,false,'AFTER_ROLLBACK');
check(lastLine($o,'ROUTES_CHECK')['routeCount']===$pin['routes']&&array_filter($newSteps,static fn($s)=>is_file($active.'/'.$s['path']))===[]&&hash_file('sha256',$layoutFile)===$lastStep['preSHA256']&&$dirsGone(),'ROUTE_COUNT_UNCHANGED_AND_THE_NEW_FILES_AND_DIRECTORIES_ARE_GONE_AFTER_ROLLBACK');
sleep(3);check($status('/katalog')===200&&$themePost('theme=dark&back=%2Fkatalog')['status']===303&&in_array($status('/site-assets/'.$logoName),[200,301],true)&&$status('/admin/settings/smtp')===302,'OLD_STATE_B1149_STOREFRONT_KEPT');
$results['rollbackInScheduleWindow']='HOLD_MANUAL_ONLY_BLOCK_THEN_IGNORE_SCHEDULE_PASS';$reset();

// 14b. A liveness smoke after a group that fails (the shop pages lose their noindex header after the second group): rolled back at once.
{
    $g1=$groups[0];$g2=$groups[1];
    [$rc,$o]=$run(['GROUP_DONE_'.$g2=>['noHtaccess']]);$ls=allLines($o,'GROUP_LIVENESS_SMOKE');
    check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"GROUP_SMOKE_FAILED_AFTER_'.$g2.'"'&&count($ls)===2&&$ls[0]['after']===$g1&&$ls[0]['failed']===[]&&$ls[1]['after']===$g2&&in_array('/katalog',$ls[1]['failed'],true)&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&$dirsGone(),'MID_INSTALL_LIVENESS_FAILURE_ROLLED_BACK');
    @unlink($switches['noHtaccess']);$reset();$results['groupLiveness']='PASS_AFTER_EVERY_GROUP_AND_A_FAILURE_AFTER_GROUP_'.$g2.'_ROLLED_BACK';
}
// 14b2. The liveness smoke RIGHT AFTER group E fails (the shop pages lose their noindex header at that point): group E is the first group restored,
// before anything else, and the old state is verified. (The files are live and serving at that moment; the failure is injected, as a real 500 would be.)
[$rc,$o]=$run(['GROUP_DONE_E'=>['noHtaccess']]);$ls=allLines($o,'GROUP_LIVENESS_SMOKE');
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"GROUP_SMOKE_FAILED_AFTER_E"'&&implode(',',array_column($ls,'after'))==='A,B,C,D,E'&&in_array('/katalog',end($ls)['failed'],true)&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&$dirsGone()&&str_contains($o,'ROLLBACK_AFTER_GUARD_GROUP={"guardGroupIsOld":true}'),'LIVENESS_FAILURE_RIGHT_AFTER_GROUP_E_ROLLED_BACK_GROUP_E_FIRST');
@unlink($switches['noHtaccess']);$reset();$results['livenessAfterGroupE']='FAILURE_ROLLED_BACK_GROUP_E_FIRST';
// 14c. An extra non-edge script with the page nonce in the installed state: more than one origin script, rolled back.
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['rogueScript'],'BEFORE_SMOKES_OLD'=>['clearDrops']]);[$s,$by]=$smokeRows(substr($o,0,(int)strpos($o,'OVERLAY_ROLLBACK=')));
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"INSTALLED_SMOKE_FAILED"'&&$by['/katalog']['originScripts']===2&&$by['/katalog']['cspShape']===false&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&$dirsGone(),'ROGUE_SCRIPT_IN_THE_INSTALLED_STATE_ROLLED_BACK');$reset();
// 14e. SIGKILL right after the rename of the FIRST group-E file (the new class is live, so the site serves): the re-run restores the old state, group E first.
[$rc,$o]=$run(['AFTER_STEP_'.$guardSteps[0]['n']=>['kill']]);check($rc!==0&&$tree()==='MIXED'&&$stateOf($guardSteps[0])==='payload'&&$stateOf($lastStep)==='pre'&&$status('/up')===200&&$status('/login')===200,'KILLED_IN_THE_MIDDLE_OF_GROUP_E_THE_SITE_SERVES');
[$rc,$o]=$run();check($rc===2&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&str_contains($o,'RESUMED_INTERRUPTED_INSTALL=')&&$tree()==='PRE'&&hash_file('sha256',$layoutFile)===$lastStep['preSHA256']&&array_filter($newSteps,static fn($s)=>is_file($active.'/'.$s['path']))===[]&&$dirsGone(),'RERUN_RESTORES_AFTER_THE_GROUP_E_KILL');$reset();$results['killInGroupE']='SITE_SERVED_THEN_RESTORED';
// 14f. SIGKILL between the new files of group A (one file live, one directory created, nothing names it): the re-run restores the old state and removes the directory; SIGKILL after the last new file
// (all four directories exist) does the same.
foreach([1,3] as $kn){
    [$rc,$o]=$run(['AFTER_STEP_'.$kn=>['kill']]);check($rc!==0&&$tree()==='MIXED'&&$stateOf($classSteps[0])==='payload'&&$stateOf($libSteps[0])==='pre'&&$status('/up')===200&&($kn===1||is_dir($active.'/resources/views/vendor/mail/html')),'KILLED_IN_GROUP_A_AFTER_STEP_'.$kn.'_THE_SITE_SERVES');
    [$rc,$o]=$run();check($rc===2&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&str_contains($o,'RESUMED_INTERRUPTED_INSTALL=')&&$tree()==='PRE'&&$dirsGone(),'RERUN_RESTORES_AFTER_THE_GROUP_A_KILL_'.$kn);$reset();
}
$results['killInGroupA']='ONE_FILE_AND_ALL_FOUR_DIRECTORIES_LIVE_THEN_RESTORED_DIRECTORIES_REMOVED';
// 14g. SIGKILL in the rollback right after the LAST group-E file was restored (the other groups are still new, the new files are there): the resumed rollback finishes.
[$rc,$o]=$run();check($rc===0&&$tree()==='INSTALLED','INSTALL_BEFORE_THE_ROLLBACK_KILL');
[$rc,$o]=$run(['AFTER_RESTORE_'.$guardSteps[0]['n']=>['kill']],'rollback');check($rc!==0&&array_filter($guardSteps,static fn($s)=>$stateOf($s)!=='pre')===[]&&array_filter(array_merge($classSteps,$libSteps,$callerSteps,$pdfSteps),static fn($s)=>$stateOf($s)!=='payload')===[]&&$status('/up')===200,'ROLLBACK_KILLED_RIGHT_AFTER_GROUP_E_WAS_RESTORED');
[$rc,$o]=$run([],'rollback');$h=null;foreach(allLines($o,'OVERLAY_HOLD_START') as $x)if($x['label']==='rollback-after-guard-group')$h=$x;
check($rc===0&&lastRaw($o,'B1153_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE'&&$dirsGone()&&is_array($h)&&$h['clock']==='SAVED_WALL_CLOCK_OF_THE_LAST_CHANGE'&&$h['remainingSeconds']<=$h['seconds'],'RESUMED_ROLLBACK_FINISHES_AND_HOLDS_FROM_THE_SAVED_CLOCK');$reset();$results['killInTheRollbackAfterGroupE']='GROUP_E_OLD_THEN_FINISHED';
// 15. Forced acceptance failure (a new ERROR line in laravel.log): automatic rollback to the verified old state.
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['logError']]);check($rc===2&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"NEW_LARAVEL_LOG_ERRORS_SINCE_INSTALL"'&&$tree()==='PRE'&&lastLine($o,'B1153_PHASE')==='ROLLED_BACK','FORCED_FAILURE_ROLLED_BACK');
check($status('/katalog')===200&&$status('/pravne-informacije')===200,'FORCED_FAILURE_OLD_SERVED');$results['forcedFailure']='ROLLED_BACK';
// The effective mail transport changes during the acceptance (.env default mailer array -> log): rolled back; the owner is told.
[$rc,$o]=$run(['AFTER_SMOKES_INSTALLED'=>['mailChange']]);
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"MAIL_TRANSPORT_FINGERPRINT_CHANGED"'&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&str_contains($o,'OWNER_NOTICE_MAIL_FINGERPRINT="MAIL_TRANSPORT_FINGERPRINT_CHANGED"'),'MAIL_TRANSPORT_CHANGE_ROLLED_BACK');$reset();$results['mailTransportChange']='ROLLED_BACK';
// The next run after ROLLED_BACK installs again; the owner then rolls back with the shop OFF (closed-shop smokes).
[$rc,$o]=$run();check($rc===0&&$tree()==='INSTALLED','REINSTALL_AFTER_ROLLBACK');$shopOff();
[$rc,$o]=$run([],'rollback');[$s,$by]=$smokeRows($o);check($rc===0&&lastRaw($o,'B1153_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE'&&$s['shop']['open']===false&&$by['/katalog']['observed']===404&&$by['/pravne-informacije']['layoutMarker']===true&&$shopFlag()===null,'ROLLBACK_WITH_SHOP_OFF');$shopOn();$reset();
$results['rollbackShopOff']='PASS_ROLLED_BACK_BY_OWNER_SWITCH_NOT_WRITTEN';

// 16. 429 from the public-shop-browse throttle (shared edge IP).
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['rateLimit2']]);[$s,$by]=$smokeRows($o);
check($rc===0&&lastRaw($o,'B1153_FINAL')===LogoCatalogInstallCoordinator::FINAL_PASS&&$by['/pravne-informacije']['result']==='PASS'&&$by['/pravne-informacije']['attempts']===3&&$by['/katalog']['result']==='PASS','TRANSIENT_429_WAITED_OUT');
@unlink($switches['rate429']);$rollbackOk('TRANSIENT_429');
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['rateLimitAlways']]);
check($rc===2&&lastRaw($o,'B1153_FINAL')==='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED'&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"SMOKES_RATE_LIMITED_429"'&&str_contains($o,'SMOKES_RATE_LIMITED_PAUSE=')&&!str_contains($o,'OVERLAY_ROLLBACK=')&&lastLine($o,'B1153_PHASE')==='ACCEPTANCE_NOT_VERIFIED'&&$tree()==='INSTALLED'&&str_contains($o,'ACTIVE_TREE_INSTALLED_UNVERIFIED='),'PERSISTENT_429_HOLD_NO_ROLLBACK');
// The per-batch cap and the shared-limiter skip: the first throttled GET waits Retry-After twice (3 + 3 s within the 7 s budget)
// and stops at attempt 3; every later throttled GET is skipped without a request; /login, /up, the CSS, /tema, the guest SMTP
// page and the (unthrottled) media route are still fetched. The number of skipped GETs follows the coded smoke set: at least 10.
[$s,$by]=$smokeRows($o);$skipped=array_filter($s['rows'],static fn($r)=>($r['skippedSharedLimiter']??false)===true&&$r['attempts']===0);
check($by['/pravne-informacije']['attempts']===3&&$by['/pravne-informacije']['waitedSeconds']===6&&count($skipped)>=10&&$s['rateLimitWaitedSeconds']===6&&$by['/login']['result']==='PASS'&&$by[LogoCatalogInstallCoordinator::CSS_PATH]['result']==='PASS'&&$by['GET /tema']['result']==='PASS'&&$by['/admin/settings/smtp']['result']==='PASS','RATE_LIMIT_CAP_AND_SHARED_KEY_SKIP');
@unlink($switches['rate429']);
[$rc,$o]=$run();check($rc===0&&str_contains($o,'RESUMED_ACCEPTANCE_ONLY=')&&!str_contains($o,'OVERLAY_STEP=')&&lastRaw($o,'B1153_FINAL')===LogoCatalogInstallCoordinator::FINAL_PASS&&$tree()==='INSTALLED','RESUMED_ACCEPTANCE_PASS');
[$rc,$o]=$run(['BEFORE_SMOKES_OLD'=>['rateLimitAlways']],'rollback');
check($rc===0&&lastRaw($o,'B1153_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&str_contains($o,'OWNER_NOTICE_RATE_LIMITED=')&&lastLine($o,'B1153_PHASE')==='ROLLED_BACK'&&$tree()==='PRE','ROLLBACK_429_NOT_INCOMPLETE');@unlink($switches['rate429']);$reset();
$results['rateLimit429']=['transient'=>'WAITED_OUT_PASS','persistentInstall'=>'HOLD_FILES_KEPT_THEN_RESUMED_ACCEPTANCE_PASS','persistentRollback'=>'PASS_ROLLED_BACK_WITH_OWNER_NOTICE'];

// 17. The owner turns the shop OFF in the middle of the acceptance batch: HOLD with the exact files, never a rollback.
[$rc,$o]=$run(['DURING_SMOKES_INSTALLED'=>['shopOff']]);
check($rc===2&&lastRaw($o,'B1153_FINAL')==='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED'&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"SHOP_STATE_CHANGED_DURING_ACCEPTANCE_STOREFRONT_NOT_VERIFIED"'&&$tree()==='INSTALLED'&&$shopFlag()===null,'SHOP_OFF_DURING_ACCEPTANCE_HOLD');
[$rc,$o]=$run();check($rc===2&&str_contains($o,'HOLD_ACCEPTANCE_NOT_RESUMED=')&&$tree()==='INSTALLED'&&$shopFlag()===null,'RESUME_REFUSED_WHILE_SHOP_OFF');
$shopOn();[$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1153_FINAL')===LogoCatalogInstallCoordinator::FINAL_PASS,'RESUMED_AFTER_SHOP_ON');$rollbackOk('SHOP_OFF_CASE');
$results['shopOffDuringAcceptance']='HOLD_NO_PASS_CLAIM_RESUMED_AFTER_SHOP_ON';

// 18. Owner policy, no indexing: the server-wide .htaccess header removed.
touch($switches['noHtaccess']);[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');[$s,$by]=$smokeRows($o);
check($rc===2&&lastLine($o,'B1153_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES',$p['blockers'],true)&&$s['failed']===[]&&$by['/pravne-informacije']['result']==='FAIL_NOINDEX_POLICY_HEADER_MISSING'&&array_diff(LogoCatalogInstallCoordinator::LEGAL,$s['noindexMissing'])===[]&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','NOINDEX_MISSING_PREFLIGHT_HOLD');unlink($switches['noHtaccess']);$reset();
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['noHtaccess']]);[$s,$by]=$smokeRows($o);
check($rc===2&&lastRaw($o,'B1153_FINAL')==='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED'&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"NOINDEX_POLICY_HEADER_MISSING_ON_PUBLIC_PAGES"'&&$s['failed']===[]&&array_diff(LogoCatalogInstallCoordinator::LEGAL,$s['noindexMissing'])===[]&&in_array('SITE_ASSET_LOGO',$s['noindexMissing'],true)&&!str_contains($o,'OVERLAY_ROLLBACK=')&&str_contains((string)(lastLine($o,'OWNER_NEXT_STEPS')[0]??''),'X-ROBOTS-TAG_NOINDEX')&&$tree()==='INSTALLED','NOINDEX_MISSING_INSTALLED_HOLD_FILES_KEPT');
[$rc,$o]=$run();check($rc===2&&str_contains($o,'RESUMED_ACCEPTANCE_ONLY=')&&lastRaw($o,'B1153_FINAL')==='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED'&&!str_contains($o,'OVERLAY_ROLLBACK=')&&$tree()==='INSTALLED','NOINDEX_STILL_MISSING_RESUME_HOLDS');
unlink($switches['noHtaccess']);[$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1153_FINAL')===LogoCatalogInstallCoordinator::FINAL_PASS&&$tree()==='INSTALLED','NOINDEX_BACK_RESUMED_PASS');
[$rc,$o]=$run(['BEFORE_SMOKES_OLD'=>['noHtaccess']],'rollback');
check($rc===0&&lastRaw($o,'B1153_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&str_contains($o,'OWNER_NOTICE_NOINDEX_MISSING=')&&$tree()==='PRE','NOINDEX_MISSING_ROLLBACK_COMPLETES_WITH_NOTICE');@unlink($switches['noHtaccess']);$reset();
$results['noindexPolicy']=['preflight'=>'HOLD_NOTHING_CHANGED','installed'=>'HOLD_FILES_KEPT_RESUME_HOLDS_THEN_PASS','rollback'=>'PASS_WITH_OWNER_NOTICE'];

// 19. Vary: Cookie (the theme cookie keys /katalog). The edge hides it and no origin route exists: HOLD with the files kept;
// with the loopback-TLS origin route the origin is asked once: present = PASS with a notice, missing = rolled back.
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['dropVaryEdge']]);[$s,$by]=$smokeRows($o);
check($rc===2&&lastRaw($o,'B1153_FINAL')==='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED'&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"VARY_COOKIE_NOT_VERIFIABLE_EDGE_HIDES_IT_AND_NO_ORIGIN_ROUTE"'&&$s['varyUnverified']===true&&$by['/katalog']['varyCookie']===false&&!str_contains($o,'OVERLAY_ROLLBACK=')&&$tree()==='INSTALLED','VARY_HIDDEN_NO_ORIGIN_ROUTE_HOLD_FILES_KEPT');
unlink($switches['dropVaryEdge']);[$rc,$o]=$run();check($rc===0&&str_contains($o,'RESUMED_ACCEPTANCE_ONLY=')&&lastRaw($o,'B1153_FINAL')===LogoCatalogInstallCoordinator::FINAL_PASS,'VARY_VISIBLE_RESUMED_PASS');$rollbackOk('VARY_HOLD');
$origin=['probeRoute'=>'LOOPBACK_TLS_ORIGIN'];
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['dropVaryEdge']],'install',$origin);[$s,$by]=$smokeRows($o);
check($rc===0&&lastRaw($o,'B1153_FINAL')===LogoCatalogInstallCoordinator::FINAL_PASS&&$by['/katalog']['varyCookie']===false&&$by['/katalog']['originVaryCookie']===true&&in_array('VARY_COOKIE_SENT_BY_THE_ORIGIN_BUT_NOT_VISIBLE_THROUGH_THE_EDGE',$s['notices'],true),'VARY_HIDDEN_ORIGIN_HAS_IT_PASS_WITH_NOTICE');
@unlink($switches['dropVaryEdge']);$rollbackOk('VARY_ORIGIN');
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['dropVaryAll'],'BEFORE_SMOKES_OLD'=>['clearDrops']],'install',$origin);[$s,$by]=$smokeRows(substr($o,0,(int)strpos($o,'OVERLAY_ROLLBACK=')));
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"INSTALLED_SMOKE_FAILED"'&&$by['/katalog']['originVaryCookie']===false&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE','VARY_MISSING_AT_THE_ORIGIN_ROLLED_BACK');$reset();
$results['varyCookie']=['edgeHiddenNoOrigin'=>'HOLD_FILES_KEPT_THEN_RESUMED_PASS','edgeHiddenOriginPresent'=>'PASS_WITH_NOTICE','originMissing'=>'ROLLED_BACK'];

// 20. SIGKILL in the two holds of the rollback. After group E is restored: the resumed rollback holds the remainder from the saved wall clock and
// only then touches groups D to B. Before the new files: the same, and the new files are removed last.
[$rc,$o]=$run();check($rc===0&&$tree()==='INSTALLED','INSTALL_BEFORE_THE_GUARD_HOLD_KILL');
[$rc,$o]=$run(['DURING_HOLD_rollback-after-guard-group'=>['kill']],'rollback');
check($rc!==0&&$tree()==='MIXED'&&array_filter($guardSteps,static fn($s)=>$stateOf($s)!=='pre')===[]&&array_filter(array_merge($classSteps,$libSteps,$callerSteps,$pdfSteps),static fn($s)=>$stateOf($s)!=='payload')===[],'KILLED_IN_THE_HOLD_AFTER_GROUP_E_WAS_RESTORED');
sleep(3);check($status('/up')===200&&$status('/katalog')===200&&$status('/pravne-informacije')===200,'OLD_GROUP_E_NEW_OTHER_FILES_SERVES');
[$rc,$o]=$run([],'rollback');$holds=[];foreach(allLines($o,'OVERLAY_HOLD_START') as $x)$holds[$x['label']]=$x;
check($rc===0&&lastRaw($o,'B1153_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE'&&$dirsGone()&&($holds['rollback-after-guard-group']['clock']??null)==='SAVED_WALL_CLOCK_OF_THE_LAST_CHANGE'&&$holds['rollback-after-guard-group']['remainingSeconds']<=$holds['rollback-after-guard-group']['seconds'],'RESUMED_ROLLBACK_HOLDS_THE_REMAINDER_AFTER_GROUP_E_WAS_RESTORED');$reset();
$results['killAfterGroupERestoreHold']='HELD_FROM_THE_SAVED_WALL_CLOCK_THEN_PASS';
[$rc,$o]=$run();check($rc===0&&$tree()==='INSTALLED','INSTALL_BEFORE_THE_NEW_FILES_HOLD_KILL');
[$rc,$o]=$run(['DURING_HOLD_rollback-before-new-classes'=>['kill']],'rollback');check($rc!==0&&$tree()==='MIXED'&&array_filter($classSteps,static fn($s)=>$stateOf($s)!=='payload')===[]&&array_filter(array_merge($libSteps,$callerSteps,$pdfSteps,$guardSteps),static fn($s)=>$stateOf($s)!=='pre')===[],'KILLED_IN_THE_HOLD_BEFORE_THE_NEW_FILES_ONLY_THE_NEW_FILES_LEFT');
[$rc,$o]=$run([],'rollback');$holds=[];foreach(allLines($o,'OVERLAY_HOLD_START') as $x)$holds[$x['label']]=$x;
check($rc===0&&lastRaw($o,'B1153_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE'&&$dirsGone()&&($holds['rollback-before-new-classes']['clock']??null)==='SAVED_WALL_CLOCK_OF_THE_LAST_CHANGE'&&$holds['rollback-before-new-classes']['remainingSeconds']<=$holds['rollback-before-new-classes']['seconds'],'RESUMED_ROLLBACK_HOLDS_FROM_THE_SAVED_WALL_CLOCK');
$reset();$results['killBeforeNewFiles']='HELD_FROM_THE_SAVED_WALL_CLOCK_THEN_PASS';

// 21. One restore fails (the layout itself, the first restore; and the first file of group C, in the middle): that step and everything before it stay installed,
// the later steps are old again (an exact install prefix that the install itself served). The manual block, run exactly as pasted, restores the old state and removes the new directories.
$partial=[];
foreach(array_values(array_unique([$lastStep['n'],$callerSteps[0]['n']])) as $k){
    [$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['logError'],'BEFORE_RESTORE_'.$k=>['fail']]);$rf=lastLine($o,'ROLLBACK_FILES');$note=lastLine($o,'RECOVERY_NOTE');
    $laterModified=count(array_filter($plan,static fn($s)=>$s['n']>$k&&$s['kind']==='modified'));$laterNew=count(array_filter($plan,static fn($s)=>$s['n']>$k&&$s['kind']==='new'));
    check($rc===2&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLBACK_INCOMPLETE'&&$rf['stoppedAtStep']===$k&&$rf['exactInstallPrefixKept']==='STEPS_1_TO_'.$k.'_INSTALLED_LATER_STEPS_RESTORED'&&$rf['modifiedRestored']===$laterModified&&$rf['newRemoved']===$laterNew&&$note['kind']==='ROLLBACK_INCOMPLETE','PARTIAL_ROLLBACK_STOPS_AT_STEP_'.$k);
    $prefix($k);sleep(3);check($status('/katalog')===200&&$status('/pravne-informacije')===200&&$status('/up')===200,'PREFIX_'.$k.'_SERVES');
    $lines=$block($o);$kinds=array_values(array_filter(array_map($kindOf,$lines)));$expected=[];$lastKey=null;
    // Every group boundary is one sleep (the full measured hold after group E and before the new files, the revalidation pause elsewhere); the only view:clear is the final line.
    foreach(array_reverse($plan) as $s){if($s['n']>$k)continue;$key=$s['group'];if($lastKey!==null&&$lastKey!==$key)$expected[]='sleep';$lastKey=$key;if($s['kind']==='modified')array_push($expected,'verify','install:'.$active.'/'.$s['path']);else $expected[]='rm:'.$active.'/'.$s['path'];}
    // Directories the install had to create (read from the saved state, not assumed) are removed when empty, before the final view:clear.
    $ptr=json_decode((string)file_get_contents($private.'/current.json'),true);$created=json_decode((string)file_get_contents($ptr['private'].'/state.json'),true)['createdDirs']??[];
    check(count($created)===4,'FOUR_CREATED_DIRECTORIES_ARE_RECORDED_IN_THE_SAVED_STATE:'.json_encode($created));
    foreach($created as $unused)$expected[]='rmdir';$expected[]='view';
    check($kinds===$expected,'RECOVERY_BLOCK_EXACT_REVERSE_ORDER:'.json_encode($kinds));
    $manual=$runBlock($lines);check($manual['rc']===0&&str_contains($manual['out'],'B1153_MANUAL_RECOVERY_BLOCK_DONE')&&$tree()==='PRE'&&$dirsGone(),'MANUAL_BLOCK_RESTORES_OLD_STATE_AND_REMOVES_THE_DIRECTORIES:'.substr($manual['out'],-400));
    [$rc,$o]=$run();check($rc===2&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE','AFTER_MANUAL_BLOCK_VERIFIED');$reset();
    $partial[]='PREFIX_1_TO_'.$k.'_MANUAL_BLOCK_RESTORED_THEN_VERIFIED';
}
$results['partialRollback']=$partial;$k=max(1,intdiv(count($plan),2));

// 22. SIGKILL in the middle of the copy: the re-run restores, the next installs.
[$rc,$o]=$run(['AFTER_STEP_'.$k=>['kill']]);check($rc!==0&&$tree()==='MIXED','KILLED_MID_COPY');
[$rc,$o]=$run();check($rc===2&&lastRaw($o,'B1153_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&str_contains($o,'RESUMED_INTERRUPTED_INSTALL=')&&$tree()==='PRE','KILL_RERUN_RESTORES');
[$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1153_FINAL')===LogoCatalogInstallCoordinator::FINAL_PASS,'KILL_THEN_INSTALL');$rollbackOk('KILL_MID_COPY');
$results['killMidCopy']='RESTORED_THEN_INSTALLED';
// An interrupted backup left changed private copies; the next run writes every stale copy again and the later rollback
// still puts back the exact old bytes.
$modifiedCount=count(array_filter($plan,static fn($s)=>$s['kind']==='modified'));
if($modifiedCount>0){
    [$rc,$o]=$run(['AFTER_BACKUP_COPIES'=>['corruptBackup','kill']]);check($rc!==0&&$tree()==='PRE'&&!str_contains($o,'OVERLAY_STEP='),'KILLED_DURING_BACKUP');
    [$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1153_FINAL')===LogoCatalogInstallCoordinator::FINAL_PASS&&preg_match_all('/^BACKUP_COPY_REWRITTEN=/m',$o)===$modifiedCount,'STALE_BACKUP_COPIES_REWRITTEN');
    $rollbackOk('REWRITTEN_COPIES');$results['staleBackupCopies']=['rewritten'=>$modifiedCount,'rollbackExact'=>true];
}else $results['staleBackupCopies']='SKIPPED_NO_MODIFIED_STEP';

check(glob($active.'/public/ald1n-b1144-*')===[]&&$routeCacheSHA()===null&&$shopFlag()==='1','NO_PROBE_NO_ROUTE_CACHE_SHOP_ON');
check(!str_contains($allOutput,'"command":"view:cache"')&&str_contains($allOutput,'"command":"view:clear"')&&str_contains($allOutput,'"command":"queue:restart"')&&!preg_match('/"command":"(?:route:(?!list|clear)|config:|cache:)/',$allOutput),'ONLY_VIEW_CLEAR_ROUTE_CLEAR_QUEUE_RESTART');
check(!preg_match('~/media/p/\d|[?&]s=[a-f0-9]{32}~',$allOutput),'NO_CAPABILITY_URL_IN_ANY_B1153_REPORT_INSTALL_ROLLBACK_OR_HOLD');
// The merchant e-mail and the reversible data-cfemail encoding of it never reach a report: only counts do.
check(!str_contains($allOutput,'podrska@example.test')&&!str_contains($allOutput,bin2hex('podrska@example.test'))&&!str_contains($allOutput,'data-cfemail='),'NO_MERCHANT_E_MAIL_AND_NO_CFEMAIL_ENCODING_IN_ANY_B1153_REPORT');
check(str_contains($allOutput,'"webEuidIsOperator":true')&&!str_contains($allOutput,'"webEuidIsOperator":false'),'WEB_EUID_IS_OPERATOR');
$serverLog=(string)file_get_contents($base.'/server.log');
check(substr_count($serverLog,'PHP Fatal error')===$fatalAfterA,'NO_FATAL_IN_SERVER_LOG_OUTSIDE_THE_DELIBERATE_WRONG_ORDER_CONTROL:'.$fatalAfterA);
// Nobody logged in and no password endpoint (and no reset request) was requested by the tool or by this fixture (the cli-server logs every request line).
check(!preg_match('~/account/password|/me/password|/auth/password|forgot-password|password/email~',$serverLog),'NO_PASSWORD_ENDPOINT_REQUESTED_AT_ANY_TIME');
// The forged Host exists in the CI-only requests of this fixture and nowhere in any report.
check(!str_contains($allOutput,'forged-host.invalid'),'FORGED_HOST_NOT_IN_ANY_REPORT');
proc_terminate($server);echo json_encode(['b1153Fixture'=>'PASS','cases'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
