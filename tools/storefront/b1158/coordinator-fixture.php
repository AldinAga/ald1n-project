<?php
declare(strict_types=1);
/**
 * The real B1158 coordinator (the generated overlay of B1158Payload, from the B1156 source commit 94de4be5 to the target commit) over the real staged 8531-file runtime (ef3f927) in the production topology (active tree with .env and storage
 * symlinks into a retained tree), after the B1145 unclamp, real MariaDB 10.11.19 and real Laravel served by `php -S` with OPcache. The production history is replayed for real: the REAL B1146 to B1156 coordinators install their overlays one after
 * the other (the output of each becomes its report; Report1122 is the chain predecessor of this rollout and its tree is the 8596-file tree of commit 94de4be5 without tests), the shop is ON, then B1158 runs with the real payload from the built operator ZIP.
 * Explicit doubles: plain loopback HTTP instead of HTTPS, realpath_cache_ttl=10, a fixed app-local clock outside the backup window, the shop switch written directly into the fixture database, CI-only POSTs of /login, /logout and /tema with throwaway users,
 * CI-only requests with a FORGED Host header, the Cloudflare edge played by B1158FixtureWeb, the Report1118 to Report1122 digests and state ids of the fixture (production pins them as constants), the route count measured on the fixture app, and CI-only database
 * inverses of the payload migrations between cases (the shipped tool never runs them). HARD LOGIN GATE as in B1156. Cases: Report1122 missing, decoy, two reports, wrong digest or state id, state not INSTALLED_PASS, route cache, APP_URL, the backup windows,
 * unknown pre-state, foreign file at a new path, preflight facts, shop OFF, doctor, census, time budget, JSON-LD and CSP, package mutations, guard groups without their proof, the install (every step smoked, the migration after the files), the retained
 * orphans, route count, idempotent re-run, owner rollback (files back, migration files kept, schema ahead), a re-run after that refused, failures before and after the migration, SIGKILL cases, an acceptance failure after the migration, a 429 that stays.
 * Usage: php coordinator-fixture.php <b1158 tools> <b1156> <b1155> <b1154> <b1153> <b1152> <b1151> <b1150> <b1149> <b1148> <b1147> <b1146> <b1145> <b1144> <b1134> <runtime stage> <payload dir> <runtime-directories.json> <b1146 zip> ... <b1156 zip> <b1158 zip>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$tools,$b1157,$b1156,$b1155,$b1154,$b1153,$b1152,$b1151,$b1150,$b1149,$b1148,$b1147,$b1146,$b1145,$b1144,$b1134,$stage,$payloadDir,$directoryDoc,$zip1146,$zip1147,$zip1148,$zip1149,$zip1150,$zip1151,$zip1152,$zip1153,$zip1154,$zip1155,$zip1156,$zip1157,$zipPath]=$argv;
require $b1134.'/ReleasePrimitives.php';require $b1134.'/InstallFileOps.php';foreach(['LeanGates','FreshnessProbe','LeanWeb'] as $c)require $b1144.'/'.$c.'.php';require $b1145.'/EnvFlags.php';require $b1145.'/MigrateUnclampCoordinator.php';require $b1146.'/OverlayProbe.php';
foreach(['CatalogWeb','CatalogOverlayCoordinator','FixtureWeb'] as $c)require $b1147.'/'.$c.'.php';foreach(['GlassWeb','GlassOverlayCoordinator'] as $c)require $b1148.'/'.$c.'.php';require $b1149.'/StorefrontInstallCoordinator.php';foreach(['MediaWeb','MediaInstallCoordinator'] as $c)require $b1150.'/'.$c.'.php';require $b1151.'/LegalAccountInstallCoordinator.php';require $b1152.'/OriginPinInstallCoordinator.php';
require $b1153.'/LoadProbe.php';require $b1153.'/LogoCatalogInstallCoordinator.php';require $b1154.'/AdminShellInstallCoordinator.php';require $b1155.'/DashboardReportsInstallCoordinator.php';require $b1156.'/CommissionsInstallCoordinator.php';require $b1157.'/Payload.php';require $b1157.'/B1157InstallCoordinator.php';require $tools.'/Payload.php';foreach(['B1158InstallCoordinator','FixtureWeb'] as $c)require $tools.'/'.$c.'.php';
const DB='ald1n_b1158_ci';const DB_PASSWORD='isolated-ci-only';const PORT=8157;
function check(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('B1158_FIXTURE_'.$name);}
// The edge-script classifier: only Cloudflare's own two shapes are edge scripts; look-alikes are origin (app) scripts.
foreach(['<script data-src="/cdn-cgi/scripts/a.js" src="//evil.example/x.js"></script>'=>'DATA_SRC_LOOKALIKE','<script src="/cdn-cgi/../evil.js"></script>'=>'DOT_DOT_PATH','<script src="/cdn-cgi/scripts/../../evil.js"></script>'=>'DOT_DOT_UNDER_SCRIPTS','<script src="/cdn-cgi/other/a.js"></script>'=>'OTHER_CDN_CGI_DIRECTORY',
    '<script src="/cdn-cgi/scripts/a.js" src="/evil.js"></script>'=>'TWO_SRC','<script data-src="x">window.__CF$cv$params=1;evil()</script>'=>null,'<script src="/x.js">window.__CF$cv$params=1</script>'=>'SRC_WITH_JSD_BODY','<script src=/cdn-cgi/scripts/a.js></script>'=>'UNQUOTED_SRC'] as $html=>$name)
    if($name!==null)check(MediaWeb::scriptCount($html)===1&&MediaWeb::edgeScriptCount($html)===0,'CLASSIFIER_'.$name.'_IS_AN_APP_SCRIPT');
check(MediaWeb::edgeScriptCount('<script data-cfasync="false" src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script nonce="x">a="window.__CF$cv$params={}";</script><script src="/cdn-cgi/challenge-platform/scripts/jsd/main.js"></script>')===3,'CLASSIFIER_REAL_EDGE_SHAPES');
function sh(array $args,?string $cwd=null):string{$p=proc_open($args,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0)throw new RuntimeException('B1158_FIXTURE_COMMAND_'.basename($args[0]).'_RC'.$rc.':'.substr($err.$out,-900));return trim((string)$out);}
function lastLine(string $output,string $key):mixed{return preg_match_all('/^'.preg_quote($key,'/').'=(.+)$/m',$output,$m)?json_decode(end($m[1]),true,64,JSON_THROW_ON_ERROR):null;}
function lastRaw(string $output,string $key):?string{return preg_match_all('/^'.preg_quote($key,'/').'=(.*)$/m',$output,$m)?end($m[1]):null;}
function allLines(string $output,string $key):array{preg_match_all('/^'.preg_quote($key,'/').'=(.+)$/m',$output,$m);return array_map(static fn($x)=>json_decode($x,true,64,JSON_THROW_ON_ERROR),$m[1]);}
function rmTree(string $path):void{if(is_link($path)||is_file($path)){unlink($path);return;}if(!is_dir($path))return;foreach(scandir($path) as $n)if($n!=='.'&&$n!=='..')rmTree($path.'/'.$n);rmdir($path);}
function child(array $argv):array{$p=proc_open($argv,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return [proc_close($p),$out,$err];}

$pdo=static fn():PDO=>new PDO('mysql:host=127.0.0.1;port=3306;dbname='.DB,'root',DB_PASSWORD,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
check(preg_match('/^10\.11\.19-MariaDB/',(string)$pdo()->query('SELECT VERSION()')->fetchColumn())===1,'MARIADB_10_11_19');
$manifest=json_decode((string)file_get_contents($payloadDir.'/runtime-manifest.json'),true,512,JSON_THROW_ON_ERROR);$directories=json_decode((string)file_get_contents($directoryDoc),true,512,JSON_THROW_ON_ERROR)['directories'];check(count($manifest['files'])===8531,'MANIFEST_8531');

// Production topology: active tree from the pinned runtime; .env and storage are symlinks into the retained tree.
$base=getenv('RUNNER_TEMP').'/b1158';mkdir($base);$base=realpath($base);$home=$base.'/home';$root=$home.'/ald1n-project';foreach([$root.'/incoming',$root.'/apps/cms',$root.'/docs/operations',$home.'/cms-backups'] as $d)mkdir($d,0755,true);
$runtime=$home.'/.ald1n-b1134-retained/'.str_repeat('d',32);mkdir($runtime,0755,true);$retained=$runtime.'/retained';$active=$root.'/apps/cms/current';$candidate=$runtime.'/candidate';$old=$runtime.'/old';
InstallFileOps::candidate($stage,$candidate,$manifest,$directories);if(!is_dir($candidate.'/bootstrap/cache'))mkdir($candidate.'/bootstrap/cache',0755,true);
foreach(['storage/app/public','storage/logs','storage/framework/sessions','storage/framework/cache/data','storage/framework/views'] as $p)mkdir($old.'/'.$p,0755,true);
file_put_contents($base."/dump-wrapper.sh","#!/bin/sh\nif command -v mariadb-dump >/dev/null 2>&1; then exec mariadb-dump \"\$@\"; fi\nexec mysqldump --column-statistics=0 \"\$@\"\n");chmod($base."/dump-wrapper.sh",0755);
$envBytes=implode("\n",['APP_NAME=Ald1n','APP_ENV=production','APP_DEBUG=false','APP_KEY=base64:'.base64_encode(random_bytes(32)),'APP_URL=https://cms.ald1n.com','APP_TIMEZONE=Europe/Belgrade','APP_MAINTENANCE_DRIVER=file','DB_CONNECTION=mysql','DB_HOST=127.0.0.1','DB_PORT=3306','DB_DATABASE='.DB,'DB_USERNAME=root','DB_PASSWORD='.DB_PASSWORD,
    'SESSION_DRIVER=file','SESSION_SECURE_COOKIE=false','CACHE_STORE=file','QUEUE_CONNECTION=sync','MAIL_MAILER=array','LOG_CHANNEL=daily','LOG_LEVEL=debug','TURNSTILE_ENABLED=false','PULSE_ENABLED=false','TELESCOPE_ENABLED=false','MOBILE_PUSH_ENABLED=false','OPERATIONAL_EMAIL_NOTIFICATIONS=false',
    'LEGACY_DB_DATABASE=disabled','LEGACY_DB_USERNAME=disabled_qa_legacy','BACKUP_PATH='.$home.'/cms-backups','BACKUP_MYSQLDUMP_BINARY='.$base.'/dump-wrapper.sh','PUBLIC_STOREFRONT_ENABLED=true','PUBLIC_CHECKOUT_ENABLED=true','']);
file_put_contents($old.'/.env',$envBytes);chmod($old.'/.env',0640);
if(!is_dir($old.'/public'))mkdir($old.'/public',0755,true);check(copy($candidate.'/public/.htaccess',$old.'/public/.htaccess')&&chmod($old.'/public/.htaccess',0644),'OLD_TREE_HTACCESS');
ReleasePrimitives::preservePrivate($old,$candidate,$retained);rename($old,$retained);rename($candidate,$active);
check(is_link($active.'/.env')&&is_link($active.'/storage/logs'),'TOPOLOGY_SYMLINKS');check(hash_file('sha256',$active.'/config/public_storefront.php')===AdminShellInstallCoordinator::CONFIG_SHA,'CONFIG_IS_STAGED_ORIGINAL_AFTER_B1145');
$stageDir=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU';mkdir($stageDir.'/verification',0755,true);copy($payloadDir.'/runtime-manifest.json',$stageDir.'/verification/runtime-manifest.json');
$htaccess=hash_file('sha256',$active.'/public/.htaccess');
check($htaccess===hash_file('sha256',dirname($tools,3).'/apps/cms/current/public/.htaccess')&&str_contains(strtolower((string)(CatalogFixtureWeb::htaccessHeaders($active.'/public/.htaccess')['x-robots-tag']??'')),'noindex'),'ACTIVE_HTACCESS_IS_THE_REAL_ONE_WITH_X_ROBOTS_TAG_NOINDEX');
check(!isset(CatalogFixtureWeb::htaccessHeaders($active.'/public/.htaccess')['content-security-policy']),'HTACCESS_DOES_NOT_OVERRIDE_THE_APP_CSP');
// The real .htaccess forces no Content-Type (the web double must not clobber the text/css of a served stylesheet): its Header lines are exactly the three below.
check(array_keys(CatalogFixtureWeb::htaccessHeaders($active.'/public/.htaccess'))===['x-robots-tag','x-content-type-options','referrer-policy'],'HTACCESS_HEADER_LINES_ARE_ROBOTS_NOSNIFF_REFERRER_AND_NO_CONTENT_TYPE');

// The B1145 terminal state and a Report1108-shaped terminal report (the predecessor of B1146).
$b1145Base=$home.'/.ald1n-b1145-state';mkdir($b1145Base,0700);$b1145Id=bin2hex(random_bytes(16));mkdir($b1145Base.'/'.$b1145Id,0700);
file_put_contents($b1145Base.'/'.$b1145Id.'/state.json',json_encode(['schemaVersion'=>1,'kind'=>'B1145','id'=>$b1145Id,'private'=>$b1145Base.'/'.$b1145Id,'phase'=>'CLOSED_UNCLAMPED','b1144'=>['retained'=>$retained,'htaccessSHA256'=>$htaccess]],JSON_UNESCAPED_SLASHES));
file_put_contents($b1145Base.'/current.json',json_encode(['schemaVersion'=>1,'kind'=>'B1145','private'=>$b1145Base.'/'.$b1145Id,'id'=>$b1145Id],JSON_UNESCAPED_SLASHES));
file_put_contents($root.'/docs/operations/1108-B1145-CONTROLLED-MIGRATE-UNCLAMP-20261108-150000.md',implode("\n",['B1145 fixture report','B1145_PHASE="CLOSED_UNCLAMPED"','B1145_FINAL=PASS_MIGRATED_UNCLAMPED_STOREFRONT_CLOSED','EXIT=0','']));
sh(['git','init','--quiet','-b','main'],$root);sh(['git','-c','user.name=Fixture','-c','user.email=fixture@example.invalid','commit','--allow-empty','-m','fixture','--quiet'],$root);

// All ten operator packages exactly as the owner unzips them (B1146 to B1154 first, then this one), plus the pinned helpers already on the host under their host names.
foreach([$zip1146,$zip1147,$zip1148,$zip1149,$zip1150,$zip1151,$zip1152,$zip1153,$zip1154,$zip1155,$zip1156,$zip1157,$zipPath] as $z)sh(['unzip','-q','-n',$z,'-d',$root]);$incoming=$root.'/incoming';
foreach(['b1134/InstallFileOps.php'=>'b1134-files.php','b1144/LeanGates.php'=>'b1144-gates.php','b1144/FreshnessProbe.php'=>'b1144-probe.php','b1144/LeanWeb.php'=>'b1144-web.php','b1145/EnvFlags.php'=>'b1145-env.php','b1145/AppState.php'=>'b1145-app-state.php','b1145/MigrateUnclampCoordinator.php'=>'b1145-coordinator.php'] as $from=>$to)copy(dirname($tools).'/'.$from,$incoming.'/'.$to);
$package=json_decode((string)file_get_contents($incoming.'/b1158-package.json'),true,64,JSON_THROW_ON_ERROR);foreach($package['files'] as $n=>$d)check(hash_file('sha256',$incoming.'/'.$n)===$d,'PACKAGE_FILE_'.$n);$plan=B1158InstallCoordinator::plan($package,$incoming);check(count($plan)===count(B1158InstallCoordinator::STEPS),'PLAN_MATCHES_STEPS');
check(count($plan)===B1158Payload::FILES&&count(array_filter($plan,static fn($s)=>$s['kind']==='new'))===B1158Payload::NEW_FILES&&implode('',array_column($plan,'group'))===implode('',array_map(static fn($s)=>$s[2],B1158InstallCoordinator::STEPS)),'PLAN_IS_THE_GENERATED_FILE_SET_IN_GROUP_ORDER');
check(implode(',',array_column($plan,'path'))===implode(',',array_map(static fn($s)=>$s[0],B1158InstallCoordinator::STEPS)),'PLAN_ORDER_IS_THE_CODED_ORDER');
check(array_column($package['overlay'],'source')===array_fill(0,count($plan),B1158InstallCoordinator::SOURCE_COMMIT)&&$package['baseCommit']===B1158InstallCoordinator::BASE_COMMIT&&$package['sourceCommit']===B1158InstallCoordinator::SOURCE_COMMIT,'PAYLOAD_SOURCE_IS_THE_ONE_PINNED_COMMIT');
check(array_column($plan,'payloadSHA256')===array_map(static fn($s)=>B1158InstallCoordinator::DIGESTS[$s[0]][1],B1158InstallCoordinator::STEPS)&&array_column($plan,'preSHA256')===array_map(static fn($s)=>B1158InstallCoordinator::DIGESTS[$s[0]][0],B1158InstallCoordinator::STEPS),'PLAN_DIGESTS_ARE_THE_REVIEWED_ONES');
check(count(B1158InstallCoordinator::b1157Plan($package))===123&&B1158InstallCoordinator::installedTreeFiles()===B1158Payload::TREE_FILES_AFTER&&B1158InstallCoordinator::B1157_TREE_FILES===8620&&B1158InstallCoordinator::ROUTES===597&&B1158InstallCoordinator::ROUTES_AFTER===B1158Payload::ROUTES_AFTER&&count(B1158InstallCoordinator::ORPHANS)===count(B1158Payload::ORPHANS),'THE_B1157_OVERLAY_LIST_OF_THE_PACKAGE_IS_THE_PINNED_B1157_SET');
check(array_filter($plan,static fn($s)=>preg_match('~^(tests/|docs/|config/|composer\.|\.env)~',$s['path'])===1)===[]&&array_values(array_map(static fn($s)=>$s['path'],array_filter($plan,static fn($s)=>str_starts_with($s['path'],'database/'))))===B1158InstallCoordinator::migrationFiles(),'ONLY_THE_TWO_MIGRATIONS_ARE_UNDER_DATABASE_NOTHING_UNDER_TESTS_DOCS_CONFIG_COMPOSER_ENV');
foreach(['b1146-probe.php','b1146-app-check.php','b1147-web.php','b1147-app-check.php','b1147-coordinator.php','b1148-web.php','b1148-app-check.php','b1148-coordinator.php','b1149-web.php','b1149-app-check.php','b1149-coordinator.php','b1150-web.php','b1150-app-check.php','b1150-coordinator.php','b1151-app-check.php','b1151-coordinator.php','b1152-app-check.php','b1152-coordinator.php','b1152-load-probe.php','b1153-app-check.php','b1153-coordinator.php','b1153-load-probe.php','b1154-app-check.php','b1154-coordinator.php','b1155-app-check.php','b1155-coordinator.php','b1156-app-check.php','b1156-coordinator.php','b1157-app-check.php','b1157-payload.php','b1157-coordinator.php'] as $n)check(isset($package['files'][$n]),'PINS_'.$n);
check($package['files']['b1157-coordinator.php']===hash_file('sha256',$b1157.'/B1157InstallCoordinator.php')&&$package['files']['b1157-app-check.php']===hash_file('sha256',$b1157.'/AppCheck.php')&&$package['files']['b1157-payload.php']===hash_file('sha256',$b1157.'/Payload.php')&&$package['files']['b1156-coordinator.php']===hash_file('sha256',$b1156.'/CommissionsInstallCoordinator.php'),'THE_B1157_AND_B1156_PINS_ARE_THE_TOOLS');
foreach(['b1157','b1156','b1155','b1154','b1153','b1152','b1151','b1150','b1149','b1148'] as $prev){$pk=json_decode((string)file_get_contents($incoming.'/'.$prev.'-package.json'),true,64,JSON_THROW_ON_ERROR);$shared=0;foreach($pk['files'] as $n=>$d)if(isset($package['files'][$n])){check($package['files'][$n]===$d,strtoupper($prev).'_ZIP_AND_THE_B1158_PIN_AGREE_'.$n);$shared++;}check($shared>=(in_array($prev,['b1157','b1156','b1155','b1154','b1153','b1152'],true)?3:2),strtoupper($prev).'_ZIP_SHARES_PINS_WITH_B1158');}
$purge=(new ReflectionClass(B1158InstallCoordinator::class))->newInstanceWithoutConstructor();$purgeStep=$purge->purgeStep();$purgeBack=$purge->purgeStep(true);
check(is_string($purgeStep)&&$purgeStep!==''&&is_string($purgeBack)&&$purgeBack!=='','PURGE_STEPS_ARE_TEXT');
$markers=B1158InstallCoordinator::markers($package);
$idx=static function(string $path)use($package):int{$i=array_search($path,array_column($package['overlay'],'path'),true);if(!is_int($i))throw new RuntimeException('B1158_FIXTURE_OVERLAY_PATH_UNKNOWN');return $i;};
// The package mutations the coordinator refuses before it touches anything (plan() compares the package with the coded STEPS: paths, kinds, GROUPS, order, digests, sources). The package BUILD refuses the same in CI (the mutation step of the workflow).
$pathOf=static function(string $g,string $kind)use($package):?string{foreach($package['overlay'] as $e)if($e['group']===$g&&$e['kind']===$kind)return $e['path'];return null;};
$migPath=B1158InstallCoordinator::migrationFiles()[0]??null;$ctlPath=$pathOf('C','modified');$viewPath=$pathOf('F','modified');$newPath=$package['overlay'][0]['path'];
$mutations=[
    'ROUTES_IN_GROUP_B'=>static function(array &$m)use($idx):void{$m['overlay'][$idx('routes/web.php')]['group']='B';},
    'PROVIDER_WITHOUT_ITS_GUARD_GROUP'=>static function(array &$m)use($idx):void{$m['overlay'][$idx('app/Providers/AppServiceProvider.php')]['group']='C';},
    'A_MIGRATION_IN_GROUP_B'=>static function(array &$m)use($idx,$migPath):void{$m['overlay'][$idx($migPath)]['group']='B';},
    'A_VIEW_BEFORE_THE_ROUTES'=>static function(array &$m)use($idx,$viewPath):void{$m['overlay'][$idx($viewPath)]['group']='E';},
    'ROUTES_BEFORE_THEIR_CONTROLLERS'=>static function(array &$m)use($idx,$ctlPath):void{$i=$idx('routes/web.php');$j=$idx($ctlPath);$r=$m['overlay'][$i];unset($m['overlay'][$i]);array_splice($m['overlay'],$j,0,[$r]);$m['overlay']=array_values($m['overlay']);},
    'PAYLOAD_DIGEST_OF_THE_PROVIDER'=>static function(array &$m)use($idx):void{$m['overlay'][$idx('app/Providers/AppServiceProvider.php')]['payloadSHA256']=str_repeat('a',64);},
    'PRE_DIGEST_OF_ROUTES'=>static function(array &$m)use($idx):void{$m['overlay'][$idx('routes/web.php')]['preSHA256']=str_repeat('b',64);},
    'A_NEW_FILE_WITH_A_PRE_DIGEST'=>static function(array &$m)use($idx,$newPath):void{$m['overlay'][$idx($newPath)]['preSHA256']=str_repeat('c',64);},
    'A_FILE_DROPPED'=>static function(array &$m):void{array_pop($m['overlay']);},
    'ANOTHER_SOURCE_COMMIT'=>static function(array &$m):void{$m['sourceCommit']=str_repeat('1',40);},
];
$have=array_column($package['overlay'],'path');$hasRoutes=in_array('routes/web.php',$have,true);$hasProvider=in_array('app/Providers/AppServiceProvider.php',$have,true);
if(!$hasRoutes){unset($mutations['ROUTES_IN_GROUP_B'],$mutations['ROUTES_BEFORE_THEIR_CONTROLLERS'],$mutations['PRE_DIGEST_OF_ROUTES']);}
if(!$hasProvider){unset($mutations['PROVIDER_WITHOUT_ITS_GUARD_GROUP'],$mutations['PAYLOAD_DIGEST_OF_THE_PROVIDER']);}
if($migPath===null)unset($mutations['A_MIGRATION_IN_GROUP_B']);if($viewPath===null)unset($mutations['A_VIEW_BEFORE_THE_ROUTES']);if($ctlPath===null)unset($mutations['ROUTES_BEFORE_THEIR_CONTROLLERS']);
$packageMutations=array_fill_keys(array_keys($mutations),'REFUSED_BEFORE_ANY_FILE');
foreach($mutations as $name=>$mutate){$m=$package;$mutate($m);try{B1158InstallCoordinator::plan($m,$incoming);$refused=false;}catch(RuntimeException $e){$refused=preg_match('/^OVERLAY_[A-Z_]+$/D',$e->getMessage())===1;}check($refused,'PACKAGE_MUTATION_IS_REFUSED_BY_THE_COORDINATOR_PLAN_'.$name);}


// Database after B1145: all 71 migrations ran, production roles, DB switch absent; one superadmin; the old-code approval signed.
$artisan=static fn(array $a):string=>sh(array_merge(['php','-d','opcache.enable=0',$active.'/artisan'],$a,['--no-interaction','--no-ansi']),$active);
check(count(glob($active.'/database/migrations/*.php'))===71,'SEVENTY_ONE_MIGRATION_FILES');$artisan(['migrate','--force']);$artisan(['db:seed','--class=Database\Seeders\CoreAccessSeeder','--force']);
$pdo()->exec("UPDATE roles SET name='Korisnik' WHERE slug='user'");
$roleId=(int)$pdo()->query("SELECT id FROM roles WHERE slug='superadmin'")->fetchColumn();check($roleId>0,'SUPERADMIN_ROLE');
$pdo()->prepare("INSERT INTO users (role_id,username,email,first_name,password_hash,status,created_at,updated_at) VALUES (?,?,?,?,?,'active',NOW(),NOW())")->execute([$roleId,'b1154-owner','b1154-owner@example.test','Fixture',password_hash('SyntheticOnly123!',PASSWORD_BCRYPT)]);$ownerId=(int)$pdo()->query("SELECT id FROM users WHERE username='b1154-owner'")->fetchColumn();check($ownerId>0,'OWNER_ROW');
$fingerprintScript=$base.'/fingerprint.php';file_put_contents($fingerprintScript,'<?php chdir($argv[1]);require "vendor/autoload.php";$app=require "bootstrap/app.php";$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();echo $app->make(App\Services\PublicMerchantPolicyService::class)->review()["fingerprint"];');
$pdo()->prepare("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('public_checkout_approval_digest',?,NOW(),NOW())")->execute([sh(['php','-d','opcache.enable=0',$fingerprintScript,$active])]);$artisan(['cache:clear']);

// Real Laravel behind php -S (cli-server SAPI) with OPcache on; requests carry Host: cms.ald1n.com.
$server=proc_open([PHP_BINARY,'-d','opcache.enable=1','-d','opcache.enable_cli=1','-d','opcache.validate_timestamps=1','-d','opcache.revalidate_freq=2','-d','opcache.file_update_protection=0','-d','realpath_cache_ttl=10','-S','127.0.0.1:'.PORT,'-t',$active.'/public',$active.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'],[0=>['pipe','r'],1=>['file',$base.'/server.log','a'],2=>['file',$base.'/server.log','a']],$pipes,$active.'/public',['PATH'=>getenv('PATH'),'HOME'=>$home]);
$status=static fn(string $target):int=>B1158FixtureWeb::get(PORT,$target)['status'];$body=static fn(string $target,array $h=[]):string=>B1158FixtureWeb::get(PORT,$target,$h)['body'];
$themePost=static fn(string $form):array=>B1158FixtureWeb::get(PORT,'/tema',['Content-Type: application/x-www-form-urlencoded'],$form);
for($i=0;$i<60&&$status('/up')!==200;$i++)usleep(500000);check($status('/up')===200&&$status('/katalog')===404&&$status('/pravne-informacije')===404,'REAL_LARAVEL_SERVED_PRE_B1146');

$private=$home.'/.ald1n-b1158-state';$private1157=$home.'/.ald1n-b1157-state';$private1156=$home.'/.ald1n-b1156-state';$private1155=$home.'/.ald1n-b1155-state';$private1154=$home.'/.ald1n-b1154-state';$private1153=$home.'/.ald1n-b1153-state';$private1152=$home.'/.ald1n-b1152-state';$private1151=$home.'/.ald1n-b1151-state';$private1150=$home.'/.ald1n-b1150-state';$private1149=$home.'/.ald1n-b1149-state';$private1148=$home.'/.ald1n-b1148-state';$private1147=$home.'/.ald1n-b1147-state';$private1146=$home.'/.ald1n-b1146-state';$secrets=$base.'/probe-secrets';$faultLog=$base.'/faults.log';$stepLog=$base.'/steps.jsonl';$logDir=$retained.'/storage/logs';$configJSON=$base.'/run.json';$safeNow=strtotime('2026-11-11 13:00:00 UTC');
$switches=[];foreach(['dropCsp','dropJsonLd','dropVaryEdge','dropVaryAll','noHtaccess','rogueScript','rate429','edgeKeepsEmailOff','cssWrongType','cssTamperBody','upFails'] as $k)$switches[$k]=$base.'/switch-'.$k;$busyPidFile=$base.'/busy.pid';$allOutput='';file_put_contents($logDir.'/laravel-'.date('Y-m-d').'.log','',FILE_APPEND);
$common=['php'=>PHP_BINARY,'allowedSapis'=>['cli-server'],'censusSeconds'=>20,'minFreeBytes'=>268435456,'scheduleGate'=>true,'fixedNow'=>$safeNow,'incoming'=>$incoming,'port'=>PORT,'secrets'=>$secrets,'faultLog'=>$faultLog,'logDir'=>$logDir,'root'=>$root,'home'=>$home,'stage'=>$stageDir,'b1146'=>$b1146,'b1145'=>$b1145,'b1144'=>$b1144,'b1134'=>$b1134,'busyPidFile'=>$busyPidFile,'db'=>['mysql:host=127.0.0.1;port=3306;dbname='.DB,'root',DB_PASSWORD]];
// $pin1150 .. $pin1154: the fixture's own report digests, state ids and route counts of the REAL B1151 to B1155 runs; $pin: the same facts of the REAL B1155 run (Report1121), for B1158 (explicit doubles; production pins its own as constants).
$pin1150=['report'=>str_repeat('0',64),'state'=>str_repeat('0',32),'routes'=>0];$pin1151=$pin1150;$pin1152=$pin1150;$pin1153=$pin1150;$pin1154=$pin1150;$pin=$pin1150;
$run=static function(array $actions=[],string $mode='install',array $overrides=[],array $extra=[])use($configJSON,$tools,$b1157,$b1156,$b1155,$b1154,$b1153,$b1152,$b1151,$b1150,$b1149,$b1148,$b1147,$private,$private1146,$private1147,$private1148,$private1149,$private1150,$private1151,$private1152,$private1153,$private1154,$private1155,$private1156,$private1157,$incoming,$stepLog,$switches,$common,&$allOutput,&$pin):array{
    file_put_contents($configJSON,json_encode($common+['overrides'=>$overrides,'switches'=>$switches,'tools'=>$tools,'b1157'=>$b1157,'b1156'=>$b1156,'b1155'=>$b1155,'b1154'=>$b1154,'b1153'=>$b1153,'b1152'=>$b1152,'b1151'=>$b1151,'b1150'=>$b1150,'b1149'=>$b1149,'b1148'=>$b1148,'b1147'=>$b1147,'base'=>$private,'pointer'=>$private.'/current.json','b1146Base'=>$private1146,'b1147Base'=>$private1147,'b1148Base'=>$private1148,'b1149Base'=>$private1149,'b1150Base'=>$private1150,'b1151Base'=>$private1151,'b1152Base'=>$private1152,'b1153Base'=>$private1153,'b1154Base'=>$private1154,'b1155Base'=>$private1155,'b1156Base'=>$private1156,'b1157Base'=>$private1157,'b1157ReportSHA256'=>$pin['report'],'b1157StateId'=>$pin['state'],'expectedRoutes'=>$pin['routes'],'expectedRoutesAfter'=>$pin['routes']+(B1158Payload::ROUTES_AFTER-B1158Payload::ROUTES),
        'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','featureScript'=>$incoming.'/b1158-app-check.php','mode'=>$mode,'stepLog'=>$stepLog,'actions'=>$actions]+$extra,JSON_UNESCAPED_SLASHES));
    [$rc,$out,$err]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$tools.'/fixture-run.php',$configJSON]);
    echo "----- B1158 RUN mode=$mode rc=$rc -----\n".$out.($err!==''?"\nSTDERR=".substr($err,-2000)."\n":'');$allOutput.=$out;return [$rc,$out];
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
$pdo()->exec("INSERT INTO brands (name,slug,status,created_at,updated_at) VALUES ('Lenovo','lenovo-b1154-ci','active',NOW(),NOW())");$brandId=(int)$pdo()->query("SELECT id FROM brands WHERE slug='lenovo-b1154-ci'")->fetchColumn();check($brandId>0,'BRAND_ROW');
$insert=$pdo()->prepare("INSERT INTO products (sku,slug,name,description,price_amount,price_currency,stock_quantity,status,brand_id,created_by,created_at,updated_at) VALUES (?,?,?,?,?,?,?,'active',?,?,NOW(),NOW())");
for($i=1;$i<=30;$i++)$insert->execute(['B1154-CI-'.$i,'b1154-ci-'.$i,'ThinkPad Synthetic '.$i,'Synthetic B1154 row',1000+97*$i,$i%3===0?'EUR':'RSD',$i%4,$i%2?$brandId:null,$ownerId]);
$pdo()->exec("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('eur_rsd_rate','117.2',NOW(),NOW()) ON DUPLICATE KEY UPDATE setting_value='117.2'");
$logoName='a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0.png';mkdir($retained.'/storage/app/public/site-assets',0755,true);$img=imagecreatetruecolor(96,96);imagefill($img,0,0,imagecolorallocate($img,18,90,200));imagepng($img,$retained.'/storage/app/public/site-assets/'.$logoName);
$pdo()->exec("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('site_logo_light_path','site-assets/".$logoName."',NOW(),NOW()) ON DUPLICATE KEY UPDATE setting_value='site-assets/".$logoName."'");
$shopOn=static function()use($pdo,$artisan):void{$pdo()->exec("DELETE FROM settings WHERE setting_key='public_storefront_open'");$pdo()->exec("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('public_storefront_open','1',NOW(),NOW())");$artisan(['cache:clear']);};
$shopOff=static function()use($pdo,$artisan):void{$pdo()->exec("DELETE FROM settings WHERE setting_key='public_storefront_open'");$artisan(['cache:clear']);};
$shopFlag=static fn():?string=>(($v=$pdo()->query("SELECT setting_value FROM settings WHERE setting_key='public_storefront_open'")->fetchColumn())===false?null:(string)$v);
$shopOn();sleep(3);check($status('/katalog')===200,'SHOP_ON_B1146_CATALOG_SERVED');

// 3. B1158 long before B1154 (no Report1120 yet, the B1147 to B1154 files absent): HOLD before any change. A decoy Report1114
// of B1149 (the HOLD of production, EXIT=2) sits beside the real reports later and must never satisfy a chain gate.
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&lastLine($o,'B1158_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('B1157_REPORT1123_MISSING',$p['blockers'],true)&&!str_contains($o,'OVERLAY_STEP=')&&!str_contains($o,'PER_FILE_BACKUP='),'REFUSED_BEFORE_B1154');rmTree($private);
$decoy=$root.'/docs/operations/1114-B1149-INSTALLER-20261110-235500.md';file_put_contents($decoy,"B1149_FINAL=HOLD\nHOLD_CODE=PREFLIGHT_BLOCKED\nEXIT=2\n");
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&in_array('B1157_REPORT1123_MISSING',$p['blockers'],true)&&!str_contains($o,'PER_FILE_BACKUP='),'DECOY_1114_HOLD_REPORT_DOES_NOT_SATISFY_THE_CHAIN');rmTree($private);$results['beforeB1154']='HOLD_NOTHING_CHANGED_DECOY_1114_IGNORED';

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
    $productIds=$pdo()->query("SELECT id FROM products WHERE slug IN ('b1154-ci-1','b1154-ci-2') ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);$dir=$retained.'/storage/app/public/products';if(!is_dir($dir))mkdir($dir,0755,true);
    // PublicProductImageFiles::original(): a public file must live under products/<product id>/ (checked with realpath).
    $ins=$pdo()->prepare("INSERT INTO product_images (product_id,file_path,original_filename,mime_type,file_size,sort_order,is_primary,storage_disk,file_hash,created_at) VALUES (?,?,?,'image/png',?,0,1,'public',?,NOW())");
    foreach($productIds as $i=>$pid){$img=$i===0?imagecreatetruecolor(1400,1400):imagecreatetruecolor(320,240);imagefill($img,0,0,imagecolorallocate($img,40+60*$i,120,200-60*$i));ob_start();if($i===0)imagepng($img,null,0);else imagepng($img);$png=(string)ob_get_clean();$name='b1154-ci-'.($i+1).'.png';$pdir=$dir.'/'.(int)$pid;if(!is_dir($pdir))mkdir($pdir,0755,true);file_put_contents($pdir.'/'.$name,$png);$ins->execute([(int)$pid,'products/'.(int)$pid.'/'.$name,$name,strlen($png),hash('sha256',$png)]);}
    $artisan(['cache:clear']);$imagesInserted=count($productIds);
}catch(Throwable $e){$imagesInserted=0;$results['productImages']='NOT_INSERTED_'.get_class($e);}
sleep(3);$oldCatalog=$body('/katalog');$imagesVisible=$imagesInserted>0;
$results['productImages']=($results['productImages']??'')!==''?$results['productImages']:($imagesVisible?'VISIBLE_ON_THE_CATALOG':'INSERTED_BUT_NOT_COUNTED');

check($plan!==[],'THE_B1154_PLAN_IS_EMPTY');check($imagesInserted===2,'TWO_SYNTHETIC_PRODUCT_IMAGES_INSERTED');

// 5c. The real B1150 coordinator installs the signed media route (needs the two product images above) with the shop ON; its output
// becomes Report1116.
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

// 5d. The real B1151 coordinator installs the legal e-mail, merchant draft and password-throttle rollout with the shop ON; its output becomes Report1117.
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
$cfg1152=$base.'/run1152.json';file_put_contents($cfg1152,json_encode($common+['overrides'=>[],'switches'=>$switches,'tools'=>$b1152,'b1151'=>$b1151,'b1150'=>$b1150,'b1149'=>$b1149,'b1148'=>$b1148,'b1147'=>$b1147,'base'=>$private1152,'pointer'=>$private1152.'/current.json','b1146Base'=>$private1146,'b1147Base'=>$private1147,'b1148Base'=>$private1148,'b1149Base'=>$private1149,'b1150Base'=>$private1150,'b1151Base'=>$private1151,'b1151ReportSHA256'=>$pin1151['report'],'b1151StateId'=>$pin1151['state'],'expectedRoutes'=>$pin1151['routes'],
    'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','featureScript'=>$incoming.'/b1152-app-check.php','mode'=>'install','stepLog'=>$base.'/steps1152.jsonl','actions'=>[]],JSON_UNESCAPED_SLASHES));
[$rc,$o1152,$e1152]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$b1152.'/fixture-run.php',$cfg1152]);echo "----- B1152 RUN rc=$rc -----\n".$o1152.($e1152!==''?"\nSTDERR=".substr($e1152,-2000)."\n":'');
check($rc===0&&lastRaw($o1152,'B1152_FINAL')===AdminShellInstallCoordinator::B1152_PASS,'REAL_B1152_INSTALLED');
$report1118=$root.'/docs/operations/1118-B1152-INSTALLER-20261113-193458.md';file_put_contents($report1118,"B1152 fixture report\n".$o1152."EXIT=0\nREPORT=".$report1118."\n");
check(LogoCatalogInstallCoordinator::b1152Facts((string)file_get_contents($report1118))['phase']==='INSTALLED_PASS','REPORT1118_TERMINAL_PASS');
$ptr1152=json_decode((string)file_get_contents($private1152.'/current.json'),true,8,JSON_THROW_ON_ERROR);$routes1152=lastLine($o1152,'ROUTES_CHECK');
$pin1152=['report'=>hash_file('sha256',$report1118),'state'=>$ptr1152['id'],'routes'=>$routes1152['routeCount']];
check(preg_match('/^[a-f0-9]{32}$/D',$pin1152['state'])===1&&is_int($pin1152['routes'])&&$pin1152['routes']>500&&$pin1152['routes']===$pin1151['routes'],'FIXTURE_PINS_READ_FROM_THE_REAL_B1152_RUN');
sleep(3);check($status('/katalog')===200&&$status('/pravne-informacije')===200&&$status('/admin/settings/documents')===302,'B1152_SERVED');
$results['chain']+=['b1152'=>'REAL_PASS_REPORT1118'];
// 5f. A B1158 attempt while only B1152 is installed (no Report1120, no B1154 state): HOLD with B1157_REPORT1123_MISSING, nothing changed (no new file, no backup, no state of this rollout kept).
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');
check($rc===2&&lastLine($o,'B1158_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('B1157_REPORT1123_MISSING',$p['blockers'],true)&&!str_contains($o,'OVERLAY_STEP=')&&!str_contains($o,'PER_FILE_BACKUP=')&&array_filter(array_filter($plan,static fn($s)=>$s['kind']==='new'),static fn($s)=>is_file($active.'/'.$s['path']))===[],'REFUSED_BEFORE_B1154_WITH_ONLY_B1152_INSTALLED_NOTHING_CHANGED');rmTree($private);
$results['beforeB1154']=['onlyB1146'=>'HOLD_NOTHING_CHANGED_DECOY_1114_IGNORED','onlyB1152'=>'HOLD_B1157_REPORT1123_MISSING_NOTHING_CHANGED'];
// The merchant e-mail the legal pages show (support e-mail of the merchant policy), set before the B1153 baseline; the stored approval digest follows it.
$pdo()->exec("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('public_checkout_support_email','podrska@example.test',NOW(),NOW()) ON DUPLICATE KEY UPDATE setting_value='podrska@example.test'");$artisan(['cache:clear']);
$pdo()->prepare("UPDATE settings SET setting_value=? WHERE setting_key='public_checkout_approval_digest'")->execute([sh(['php','-d','opcache.enable=0',$fingerprintScript,$active])]);$artisan(['cache:clear']);
$edge=new B1158FixtureWeb(PORT,$secrets,$switches,$active.'/public/.htaccess');$edgeGet=static fn(string $path):array=>$edge->fetch($path,[],bin2hex(random_bytes(8)));
sleep(3);$trader=$edgeGet(AdminShellInstallCoordinator::TRADER_PATH);
check($trader['status']===200&&AdminShellInstallCoordinator::emailArtifacts($trader['body'])===0&&MediaWeb::edgeScriptCount($trader['body'])===1,'FIXTURE_PREMISE_THE_B1151_LAYOUT_KEEPS_THE_E_MAIL_OUT_OF_THE_OBFUSCATION:'.json_encode([$trader['status'],AdminShellInstallCoordinator::emailArtifacts($trader['body']),MediaWeb::edgeScriptCount($trader['body'])]));

// 5g. The real B1153 coordinator installs the logo sizes, the e-mail logo and the catalog view switch with the shop ON; its output becomes Report1119, the chain predecessor of B1154. The B1153 tree is the tree production has today (8572 files).
$cfg1153=$base.'/run1153.json';file_put_contents($cfg1153,json_encode($common+['overrides'=>[],'switches'=>$switches,'tools'=>$b1153,'b1152'=>$b1152,'b1151'=>$b1151,'b1150'=>$b1150,'b1149'=>$b1149,'b1148'=>$b1148,'b1147'=>$b1147,'base'=>$private1153,'pointer'=>$private1153.'/current.json','b1146Base'=>$private1146,'b1147Base'=>$private1147,'b1148Base'=>$private1148,'b1149Base'=>$private1149,'b1150Base'=>$private1150,'b1151Base'=>$private1151,'b1152Base'=>$private1152,'b1152ReportSHA256'=>$pin1152['report'],'b1152StateId'=>$pin1152['state'],'expectedRoutes'=>$pin1152['routes'],
    'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','featureScript'=>$incoming.'/b1153-app-check.php','mode'=>'install','stepLog'=>$base.'/steps1153.jsonl','actions'=>[]],JSON_UNESCAPED_SLASHES));
[$rc,$o1153,$e1153]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$b1153.'/fixture-run.php',$cfg1153]);echo "----- B1153 RUN rc=$rc -----\n".$o1153.($e1153!==''?"\nSTDERR=".substr($e1153,-2000)."\n":'');
check($rc===0&&lastRaw($o1153,'B1153_FINAL')===LogoCatalogInstallCoordinator::FINAL_PASS,'REAL_B1153_INSTALLED');
$report1119=$root.'/docs/operations/1119-B1153-INSTALLER-20261114-230705.md';file_put_contents($report1119,"B1153 fixture report\n".$o1153."EXIT=0\nREPORT=".$report1119."\n");
check(AdminShellInstallCoordinator::b1153Facts((string)file_get_contents($report1119))['phase']==='INSTALLED_PASS','REPORT1119_TERMINAL_PASS');
$ptr1153=json_decode((string)file_get_contents($private1153.'/current.json'),true,8,JSON_THROW_ON_ERROR);$routes1153=lastLine($o1153,'ROUTES_CHECK');
$pin1153=['report'=>hash_file('sha256',$report1119),'state'=>$ptr1153['id'],'routes'=>$routes1153['routeCount']];
check(preg_match('/^[a-f0-9]{32}$/D',$pin1153['state'])===1&&is_int($pin1153['routes'])&&$pin1153['routes']>500&&$pin1153['routes']===$pin1152['routes'],'FIXTURE_PINS_READ_FROM_THE_REAL_B1153_RUN');
sleep(3);check($status('/katalog')===200&&$status('/pravne-informacije')===200&&$status('/admin/settings/documents')===302&&str_contains($body('/katalog'),AdminShellInstallCoordinator::PER_PAGE_MARKER),'B1153_SERVED_WITH_THE_PER_PAGE_FORM');
$results['chain']+=['b1153'=>'REAL_PASS_REPORT1119','fixtureRouteCount'=>$pin1153['routes'],'fixtureRouteCountEquals568'=>$pin1153['routes']===AdminShellInstallCoordinator::ROUTES];
$trader=$edgeGet(AdminShellInstallCoordinator::TRADER_PATH);
check($trader['status']===200&&AdminShellInstallCoordinator::emailArtifacts($trader['body'])===0&&MediaWeb::edgeScriptCount($trader['body'])===1,'FIXTURE_PREMISE_STILL_HOLDS_AFTER_B1153:'.json_encode([$trader['status'],AdminShellInstallCoordinator::emailArtifacts($trader['body']),MediaWeb::edgeScriptCount($trader['body'])]));

// 5g2. A B1158 attempt with B1153 installed and B1154 not yet: HOLD with B1157_REPORT1123_MISSING, nothing changed.
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');
check($rc===2&&lastLine($o,'B1158_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('B1157_REPORT1123_MISSING',$p['blockers'],true)&&!str_contains($o,'OVERLAY_STEP=')&&!str_contains($o,'PER_FILE_BACKUP=')&&array_filter(array_filter($plan,static fn($s)=>$s['kind']==='new'),static fn($s)=>is_file($active.'/'.$s['path']))===[],'REFUSED_BEFORE_B1154_WITH_B1153_INSTALLED_NOTHING_CHANGED');rmTree($private);$results['beforeB1154']['onlyB1153']='HOLD_B1157_REPORT1123_MISSING_NOTHING_CHANGED';
$results['packageMutations']=$packageMutations;

// 5h. The real B1154 coordinator installs the admin shell (navigation, stylesheets and views) with the shop ON; its output becomes Report1120, the chain predecessor of B1158. The B1154 tree is the tree production has today (8574 files, commit ad901a58): every pre digest of the B1158 plan must hold in it.
$cfg1154=$base.'/run1154.json';file_put_contents($cfg1154,json_encode($common+['overrides'=>[],'switches'=>$switches,'tools'=>$b1154,'b1153'=>$b1153,'b1152'=>$b1152,'b1151'=>$b1151,'b1150'=>$b1150,'b1149'=>$b1149,'b1148'=>$b1148,'b1147'=>$b1147,'base'=>$private1154,'pointer'=>$private1154.'/current.json','b1146Base'=>$private1146,'b1147Base'=>$private1147,'b1148Base'=>$private1148,'b1149Base'=>$private1149,'b1150Base'=>$private1150,'b1151Base'=>$private1151,'b1152Base'=>$private1152,'b1153Base'=>$private1153,'b1153ReportSHA256'=>$pin1153['report'],'b1153StateId'=>$pin1153['state'],'expectedRoutes'=>$pin1153['routes'],
    'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','featureScript'=>$incoming.'/b1154-app-check.php','mode'=>'install','stepLog'=>$base.'/steps1154.jsonl','actions'=>[]],JSON_UNESCAPED_SLASHES));
[$rc,$o1154,$e1154]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$b1154.'/fixture-run.php',$cfg1154]);echo "----- B1154 RUN rc=$rc -----\n".$o1154.($e1154!==''?"\nSTDERR=".substr($e1154,-2000)."\n":'');
check($rc===0&&lastRaw($o1154,'B1154_FINAL')===AdminShellInstallCoordinator::FINAL_PASS,'REAL_B1154_INSTALLED');
$report1120=$root.'/docs/operations/1120-B1154-INSTALLER-20261115-105942.md';file_put_contents($report1120,"B1154 fixture report\n".$o1154."EXIT=0\nREPORT=".$report1120."\n");
check(DashboardReportsInstallCoordinator::b1154Facts((string)file_get_contents($report1120),$pin1153['routes'])['phase']==='INSTALLED_PASS','REPORT1120_TERMINAL_PASS');
$ptr1154=json_decode((string)file_get_contents($private1154.'/current.json'),true,8,JSON_THROW_ON_ERROR);$routes1154=lastLine($o1154,'ROUTES_CHECK');
$pin1154=['report'=>hash_file('sha256',$report1120),'state'=>$ptr1154['id'],'routes'=>$routes1154['routeCount']];
check(preg_match('/^[a-f0-9]{32}$/D',$pin1154['state'])===1&&is_int($pin1154['routes'])&&$pin1154['routes']>500&&$pin1154['routes']===$pin1153['routes'],'FIXTURE_PINS_READ_FROM_THE_REAL_B1154_RUN');
sleep(3);check($status('/katalog')===200&&$status('/pravne-informacije')===200&&$status('/registracija')===200&&$status('/admin/reports/subagents')===404,'B1154_SERVED_WITH_THE_NEW_SHELL_AND_NO_REPORT_ROUTE');
$results['chain']+=['b1154'=>'REAL_PASS_REPORT1120','fixtureRouteCountB1154'=>$pin1154['routes']];

// 5i. The real B1155 coordinator installs the dashboard, the dropdown menus, the registration page, the sub-agent report and the pinned redirects with the shop ON; its output becomes Report1121, the chain predecessor of THIS rollout.
// The tree is then the 8583-file tree of commit d2c07f62 (the tree production has today) and every pre digest of the B1158 plan holds in it.
$cfg1155=$base.'/run1155.json';file_put_contents($cfg1155,json_encode($common+['overrides'=>[],'switches'=>$switches,'tools'=>$b1155,'b1154'=>$b1154,'b1153'=>$b1153,'b1152'=>$b1152,'b1151'=>$b1151,'b1150'=>$b1150,'b1149'=>$b1149,'b1148'=>$b1148,'b1147'=>$b1147,'base'=>$private1155,'pointer'=>$private1155.'/current.json','b1146Base'=>$private1146,'b1147Base'=>$private1147,'b1148Base'=>$private1148,'b1149Base'=>$private1149,'b1150Base'=>$private1150,'b1151Base'=>$private1151,'b1152Base'=>$private1152,'b1153Base'=>$private1153,'b1154Base'=>$private1154,'b1154ReportSHA256'=>$pin1154['report'],'b1154StateId'=>$pin1154['state'],'expectedRoutes'=>$pin1154['routes'],'expectedRoutesAfter'=>$pin1154['routes']+2,
    'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','featureScript'=>$incoming.'/b1155-app-check.php','mode'=>'install','stepLog'=>$base.'/steps1155.jsonl','actions'=>[]],JSON_UNESCAPED_SLASHES));
[$rc,$o1155,$e1155]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$b1155.'/fixture-run.php',$cfg1155]);echo "----- B1155 RUN rc=$rc -----\n".$o1155.($e1155!==''?"\nSTDERR=".substr($e1155,-2000)."\n":'');
check($rc===0&&lastRaw($o1155,'B1155_FINAL')===DashboardReportsInstallCoordinator::FINAL_PASS,'REAL_B1155_INSTALLED');
$report1121=$root.'/docs/operations/1121-B1155-INSTALLER-20261116-052404.md';file_put_contents($report1121,"B1155 fixture report\n".$o1155."EXIT=0\nREPORT=".$report1121."\n");
check(CommissionsInstallCoordinator::b1155Facts((string)file_get_contents($report1121),$pin1154['routes']+2)['phase']==='INSTALLED_PASS','REPORT1121_TERMINAL_PASS');
$ptr1155=json_decode((string)file_get_contents($private1155.'/current.json'),true,8,JSON_THROW_ON_ERROR);$routes1155=lastLine($o1155,'ROUTES_CHECK');
$pin1155=['report'=>hash_file('sha256',$report1121),'state'=>$ptr1155['id'],'routes'=>$routes1155['routeCount']];
check(preg_match('/^[a-f0-9]{32}$/D',$pin1155['state'])===1&&is_int($pin1155['routes'])&&$pin1155['routes']>500&&$pin1155['routes']===$pin1154['routes']+2,'FIXTURE_PINS_READ_FROM_THE_REAL_B1155_RUN');


sleep(3);check($status('/katalog')===200&&$status('/pravne-informacije')===200&&$status('/registracija')===200&&$status('/admin/reports/subagents')===302,'B1155_SERVED_WITH_THE_REPORT_ROUTES');
$results['chain']+=['b1155'=>'REAL_PASS_REPORT1121','fixtureRouteCountB1155'=>$pin1155['routes']];


// 5j. The real B1156 coordinator installs commissions, orders UI and its two migrations with the shop ON; its output becomes Report1122, the chain predecessor of THIS rollout. The tree is then the 8596-file tree of commit 94de4be5 (the tree production has today) and every pre digest of the B1158 plan holds in it; the database has 73 ran.
$cfg1156=$base.'/run1156.json';file_put_contents($cfg1156,json_encode($common+['overrides'=>[],'switches'=>$switches,'tools'=>$b1156,'b1155'=>$b1155,'b1154'=>$b1154,'b1153'=>$b1153,'b1152'=>$b1152,'b1151'=>$b1151,'b1150'=>$b1150,'b1149'=>$b1149,'b1148'=>$b1148,'b1147'=>$b1147,'base'=>$private1156,'pointer'=>$private1156.'/current.json','b1146Base'=>$private1146,'b1147Base'=>$private1147,'b1148Base'=>$private1148,'b1149Base'=>$private1149,'b1150Base'=>$private1150,'b1151Base'=>$private1151,'b1152Base'=>$private1152,'b1153Base'=>$private1153,'b1154Base'=>$private1154,'b1155Base'=>$private1155,'b1155ReportSHA256'=>$pin1155['report'],'b1155StateId'=>$pin1155['state'],'expectedRoutes'=>$pin1155['routes'],'expectedRoutesAfter'=>$pin1155['routes']+9,
    'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','featureScript'=>$incoming.'/b1156-app-check.php','mode'=>'install','stepLog'=>$base.'/steps1156.jsonl','actions'=>[]],JSON_UNESCAPED_SLASHES));
[$rc,$o1156,$e1156]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$b1156.'/fixture-run.php',$cfg1156]);echo "----- B1156 RUN rc=$rc -----\n".$o1156.($e1156!==''?"\nSTDERR=".substr($e1156,-2000)."\n":'');
check($rc===0&&lastRaw($o1156,'B1156_FINAL')===CommissionsInstallCoordinator::FINAL_PASS,'REAL_B1156_INSTALLED');
$report1122=$root.'/docs/operations/1122-B1156-INSTALLER-20261121-031500.md';file_put_contents($report1122,"B1156 fixture report\n".$o1156."EXIT=0\nREPORT=".$report1122."\n");
check(B1157InstallCoordinator::b1156Facts((string)file_get_contents($report1122),$pin1155['routes']+9)['phase']==='INSTALLED_PASS','REPORT1122_TERMINAL_PASS');
$ptr1156=json_decode((string)file_get_contents($private1156.'/current.json'),true,8,JSON_THROW_ON_ERROR);$routes1156=lastLine($o1156,'ROUTES_CHECK');
$pin1156=['report'=>hash_file('sha256',$report1122),'state'=>$ptr1156['id'],'routes'=>$routes1156['routeCount']];
check(preg_match('/^[a-f0-9]{32}$/D',$pin1156['state'])===1&&is_int($pin1156['routes'])&&$pin1156['routes']===$pin1155['routes']+9,'FIXTURE_PINS_READ_FROM_THE_REAL_B1156_RUN');


sleep(3);check($status('/katalog')===200&&$status('/pravne-informacije')===200&&$status('/registracija')===200&&$status('/notifications/recent')===302,'B1156_SERVED_WITH_THE_BELL_ROUTE');
$results['chain']+=['b1156'=>'REAL_PASS_REPORT1122','fixtureRouteCountB1156'=>$pin1156['routes'],'fixtureRouteCountEquals579'=>$pin1156['routes']===B1158InstallCoordinator::ROUTES];


// 5k. The real B1157 coordinator installs its overlay and three migrations with the shop ON; its output becomes Report1123, the chain predecessor of THIS rollout. The tree is then the 8620-file tree of commit 2ed96694 (the tree production has today) and every pre digest of the B1158 plan holds in it; the database has 76 ran.
$cfg1157=$base.'/run1157.json';file_put_contents($cfg1157,json_encode($common+['overrides'=>[],'switches'=>$switches,'tools'=>$b1157,'b1156'=>$b1156,'b1155'=>$b1155,'b1154'=>$b1154,'b1153'=>$b1153,'b1152'=>$b1152,'b1151'=>$b1151,'b1150'=>$b1150,'b1149'=>$b1149,'b1148'=>$b1148,'b1147'=>$b1147,'base'=>$private1157,'pointer'=>$private1157.'/current.json','b1146Base'=>$private1146,'b1147Base'=>$private1147,'b1148Base'=>$private1148,'b1149Base'=>$private1149,'b1150Base'=>$private1150,'b1151Base'=>$private1151,'b1152Base'=>$private1152,'b1153Base'=>$private1153,'b1154Base'=>$private1154,'b1155Base'=>$private1155,'b1156Base'=>$private1156,'b1156ReportSHA256'=>$pin1156['report'],'b1156StateId'=>$pin1156['state'],'expectedRoutes'=>$pin1156['routes'],'expectedRoutesAfter'=>$pin1156['routes']+(B1157Payload::ROUTES_AFTER-B1157Payload::ROUTES),
    'appStateScript'=>$incoming.'/b1145-app-state.php','routesScript'=>$incoming.'/b1146-app-check.php','featureScript'=>$incoming.'/b1157-app-check.php','mode'=>'install','stepLog'=>$base.'/steps1157.jsonl','actions'=>[]],JSON_UNESCAPED_SLASHES));
[$rc,$o1157,$e1157]=child(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$b1157.'/fixture-run.php',$cfg1157]);echo "----- B1157 RUN rc=$rc -----\n".$o1157.($e1157!==''?"\nSTDERR=".substr($e1157,-2000)."\n":'');
check($rc===0&&lastRaw($o1157,'B1157_FINAL')===B1157InstallCoordinator::FINAL_PASS,'REAL_B1157_INSTALLED');
$report1123=$root.'/docs/operations/1123-B1157-INSTALLER-20261123-233512.md';file_put_contents($report1123,"B1157 fixture report\n".$o1157."EXIT=0\nREPORT=".$report1123."\n");
check(B1158InstallCoordinator::b1157Facts((string)file_get_contents($report1123),$pin1156['routes']+(B1157Payload::ROUTES_AFTER-B1157Payload::ROUTES))['phase']==='INSTALLED_PASS','REPORT1123_TERMINAL_PASS');
$ptr1157=json_decode((string)file_get_contents($private1157.'/current.json'),true,8,JSON_THROW_ON_ERROR);$routes1157=lastLine($o1157,'ROUTES_CHECK');
$pin=['report'=>hash_file('sha256',$report1123),'state'=>$ptr1157['id'],'routes'=>$routes1157['routeCount']];
check(preg_match('/^[a-f0-9]{32}$/D',$pin['state'])===1&&is_int($pin['routes'])&&$pin['routes']===$pin1156['routes']+(B1157Payload::ROUTES_AFTER-B1157Payload::ROUTES),'FIXTURE_PINS_READ_FROM_THE_REAL_B1157_RUN');
$check1157Pre=static function()use($active):bool{foreach(B1158InstallCoordinator::STEPS as [$p,$kind]){$t=$active.'/'.$p;$pre=B1158InstallCoordinator::DIGESTS[$p][0];$sha=is_file($t)?hash_file('sha256',$t):null;if($sha!==$pre)return false;}return true;};
check($check1157Pre(),'AFTER_THE_REAL_B1157_EVERY_PRE_DIGEST_OF_THE_B1158_PLAN_HOLDS_THE_TREE_IS_COMMIT_2ED96694');
sleep(3);check($status('/katalog')===200&&$status('/pravne-informacije')===200&&$status('/registracija')===200&&$status('/notifications/recent')===302,'B1157_SERVED');
$results['chain']+=['b1157'=>'REAL_PASS_REPORT1123','fixtureRouteCountB1157'=>$pin['routes'],'fixtureRouteCountEquals597'=>$pin['routes']===B1158InstallCoordinator::ROUTES];

// What the plan looks like in the fixture: six groups, three guard groups (D classes, E classes, F assets), the step numbers the cases below name.
$groups=array_values(array_unique(array_column($plan,'group')));sort($groups);check($groups===array_values(array_unique(array_map(static fn($s)=>$s[2],B1158InstallCoordinator::STEPS))),'GROUPS_ARE_THE_GENERATED_ONES');
check(B1158InstallCoordinator::GUARDS===B1158Payload::GUARDS,'GUARDS_ARE_THE_GENERATED_ONES_WITH_D_AND_E');
$byPath=[];foreach($plan as $s)$byPath[$s['path']]=$s;
$groupSteps=static fn(string $g):array=>array_values(array_filter($plan,static fn($s)=>$s['group']===$g));
$stepProvider=$byPath['app/Providers/AppServiceProvider.php']??null;$stepRoutesWeb=$byPath['routes/web.php']??null;$stepRoutesApi=$byPath['routes/api.php']??null;$nMig=count(B1158InstallCoordinator::NEW_MIGRATIONS);$zero=$nMig===0;$stepMig1=$zero?['path'=>'database/migrations/0000_00_00_000000_none.php','n'=>0,'group'=>'A']:$byPath[B1158InstallCoordinator::migrationFiles()[0]];$stepMig2=$stepMig1;
check(($stepProvider===null||$stepRoutesApi===null||$stepRoutesWeb===null||($stepProvider['n']<$stepRoutesApi['n']&&$stepRoutesApi['n']<$stepRoutesWeb['n']))&&$stepMig1['group']==='A','THE_PROVIDER_THE_ROUTES_AND_THE_MIGRATION_ARE_IN_THEIR_GROUPS');
$newSteps=array_values(array_filter($plan,static fn($s)=>$s['kind']==='new'));$modifiedSteps=array_values(array_filter($plan,static fn($s)=>$s['kind']==='modified'));check(count($newSteps)===B1158Payload::NEW_FILES&&count($modifiedSteps)===B1158Payload::MODIFIED_FILES,'NEW_AND_MODIFIED_COUNTS');
$stateOf=static function(array $s)use($active):string{$t=$active.'/'.$s['path'];if(!is_file($t))return 'absent';$h=hash_file('sha256',$t);return $h===$s['payloadSHA256']?'payload':($h===$s['preSHA256']?'pre':'foreign');};
$cssUrls=array_keys(B1158InstallCoordinator::ADMIN_CSS_PATHS);
$undoMigrationPlaceholder=null;
$seedSnapshot=null;
$undoMigration=static function()use($pdo):void{
    $db=$pdo();
    foreach(array_reverse(B1158InstallCoordinator::NEW_TABLES) as $tb)$db->exec('DROP TABLE IF EXISTS '.$tb);
    foreach(B1158InstallCoordinator::NEW_COLUMNS as $tb=>$cols)foreach($cols as $c){$n=(int)$db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".DB."' AND TABLE_NAME='".$tb."' AND COLUMN_NAME='".$c."'")->fetchColumn();if($n===1)$db->exec('ALTER TABLE '.$tb.' DROP COLUMN '.$c);}
    $db->exec("DELETE FROM migrations WHERE migration IN ('".implode("','",B1158InstallCoordinator::NEW_MIGRATIONS)."')");
};
$reset=static function()use($private,$secrets,$faultLog,$stepLog,$logDir,$artisan,$tree,$status,$switches,$envFile,$envOriginal,$undoMigration,$active):void{
    rmTree($private);foreach(array_merge([$secrets,$faultLog,$stepLog],array_values($switches),glob($logDir.'/laravel*.log')?:[]) as $f)if(file_exists($f))unlink($f);file_put_contents($logDir.'/laravel-'.date('Y-m-d').'.log','');file_put_contents($envFile,$envOriginal);
    // CI only: the two migration files a schema-ahead rollback keeps are removed and the database is put back to the d2c07f62 schema and rows (the inverse of the two migrations; the shipped tool never does this).
    foreach(B1158InstallCoordinator::migrationFiles() as $mf)if(is_file($active.'/'.$mf))unlink($active.'/'.$mf);$undoMigration();$artisan(['cache:clear']);
    check($tree()==='PRE','RESET_TREE_PRE');sleep(3);check($status('/login')===200,'RESET_WARM_LOGIN');
};
// A smoke at EVERY step of the install (AFTER_STEP_n), at every group boundary (GROUP_DONE_g) and after the invalidation; the same at every step of a rollback (AFTER_RESTORE_n).
$allSteps=static function()use($plan,$groups):array{$a=[];foreach($plan as $s)$a['AFTER_STEP_'.$s['n']]=['stepSmoke'];foreach($groups as $g)$a['GROUP_DONE_'.$g]=['stepSmoke'];$a['AFTER_INVALIDATE']=['stepSmoke'];return $a;};
$allRestores=static function()use($plan):array{$a=[];foreach($plan as $s)$a['AFTER_RESTORE_'.$s['n']]=['stepSmoke'];return $a;};
$stepRows=static fn():array=>array_map(static fn($l)=>json_decode($l,true),array_values(array_filter(explode("\n",(string)@file_get_contents($stepLog)))));
$stepOf=static function(string $path)use($plan):array{foreach($plan as $s)if($s['path']===$path)return $s;throw new RuntimeException('B1158_FIXTURE_STEP_UNKNOWN');};
$prefix=static function(int $k)use($plan,$active):void{foreach($plan as $s){$t=$active.'/'.$s['path'];$sha=is_file($t)?hash_file('sha256',$t):null;check($sha===($s['n']<=$k?$s['payloadSHA256']:$s['preSHA256']),'PREFIX_'.$k.'_STEP_'.$s['n']);}};
$block=static function(string $o):array{check(preg_match("/^bash -e <<'B1158_RECOVERY'\n(.*?)\nB1158_RECOVERY$/ms",$o,$m)===1,'RECOVERY_BLOCK_PRESENT');return explode("\n",$m[1]);};
$kindOf=static function(string $l):?string{$last=preg_match("/'([^']*)'$/",$l,$m)?$m[1]:null;
    if(in_array($l,['set -euo pipefail','umask 022','echo B1158_MANUAL_RECOVERY_BLOCK_DONE'],true))return null;if(str_starts_with($l,'printf ')&&str_contains($l,'| sha256sum --check --strict --quiet -'))return 'verify';
    if(str_starts_with($l,'install -m ')&&str_contains($l,' -g '))return 'install:'.$last;if(str_starts_with($l,'rm -f -- '))return 'rm:'.$last;if(str_starts_with($l,'rmdir --ignore-fail-on-non-empty -- '))return 'rmdir';if(preg_match('/^sleep [0-9]+$/D',$l))return 'sleep';if(str_contains($l,' artisan view:clear --no-interaction'))return 'view';return 'other';};
$runBlock=static function(array $lines):array{$p=proc_open(['bash','-e'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fwrite($pipes[0],implode("\n",$lines)."\n");fclose($pipes[0]);$out=stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return ['rc'=>proc_close($p),'out'=>$out];};
$conflictNow=(new DateTimeImmutable('2026-11-11 02:10:00',new DateTimeZone('Europe/Belgrade')))->getTimestamp();
$smokeRows=static function(string $o):array{$s=lastLine($o,'PUBLIC_HTTP_SMOKES');$by=[];foreach($s['rows'] as $r)$by[$r['check']]=$r;return [$s,$by];};
$rollbackOk=static function(string $what)use($run,$tree,$reset):void{[$rc,$o]=$run([],'rollback');check($rc===0&&lastRaw($o,'B1158_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE','CLEANUP_ROLLBACK_'.$what);$reset();};
$cssV=B1158InstallCoordinator::CSS_PATH.'?v='.$package['cssVersion'];$galV=B1158InstallCoordinator::GALLERY_CSS_PATH.'?v='.$package['galleryVersion'];
// A smoke row is fine when it passed or was reported as data-dependent INFO (no product image, no logo).
$rowOk=static fn(array $by,string $k):bool=>isset($by[$k])&&(str_starts_with((string)$by[$k]['result'],'PASS')||str_starts_with((string)$by[$k]['result'],'INFO'));
// The B1149 storefront and the B1150/B1151 additions the production tree runs today: asserted in the OLD state and again in the INSTALLED state.
$b1149Intact=static function(array $s,array $by,string $where)use($rowOk):void{
    check($by['/katalog']['originScripts']===1&&$by['/katalog']['edgeInjectedScripts']===1&&$by['/katalog']['cspShape']===true&&$by['/katalog']['layoutMarker']===true&&$by['/katalog']['galleryLink']===true&&$by['/katalog']['cacheControlPrivateNoStore']===true&&$by['/katalog']['jsonLd']==='PASS','B1149_FEATURES_NONCE_GALLERY_PRIVATE_NO_STORE_'.$where);
    // The B1151 layout keeps the page body out of the Cloudflare e-mail obfuscation, so the decoder is not injected: one edge script (the JavaScript-detections loader); the origin script count stays exactly 1.
    check($by['/kolacici']['originScripts']===1&&in_array($by['/kolacici']['edgeInjectedScripts'],[1,2],true)&&$by['/kolacici']['cspShape']===true&&$by['/kolacici']['layoutMarker']===true,'B1149_LEGAL_NONCE_SCRIPT_'.$where);
    check($by['/admin/settings/smtp']['observed']===302&&$by['/admin/settings/smtp']['location']==='LOGIN'&&$by['GET /tema']['observed']===405&&$by['HEAD /tema']['observed']===405&&$rowOk($by,'SITE_ASSET_LOGO')&&$by[B1158InstallCoordinator::GALLERY_CSS_PATH]['result']==='PASS','B1149_SMTP_GUEST_302_THEME_405_LOGO_GALLERY_CSS_'.$where);
    check($by[B1158InstallCoordinator::MERCHANT_PATH]['observed']===302&&$by[B1158InstallCoordinator::MERCHANT_PATH]['location']==='LOGIN'&&$by[B1158InstallCoordinator::MERCHANT_PATH]['result']==='PASS','B1151_MERCHANT_SETTINGS_GUEST_302_'.$where);
    // B1150 stays: the signed media route, the accessor and the doctor-facing rows pass in both states (the fixture has two product images with files).
    foreach(['PRODUCT_IMAGE_URL_ACCESSOR','MEDIA_ROUTE_IMAGE','MEDIA_ROUTE_ORIGINAL_HEAD','MEDIA_ROUTE_NOT_MODIFIED','MEDIA_ROUTE_BAD_SIGNATURE','MEDIA_ROUTE_MISSING_SIGNATURE','MEDIA_ROUTE_UNKNOWN_IMAGE'] as $k)check(($by[$k]['result']??'')==='PASS','B1150_MEDIA_ROUTE_ROW_'.$k.'_'.$where.':'.json_encode($by[$k]??null));
};
// B1152 stays in both states: the guest admin redirects (the settings pages AND the dashboard /) are on the pinned origin (the pin is live before this rollout, so the plain-http fixture request is redirected to https all the same), the login form is clean.
$pinRows=static function(array $by,string $where):void{
    foreach([B1158InstallCoordinator::SMTP_PATH,B1158InstallCoordinator::MERCHANT_PATH,B1158InstallCoordinator::DOCUMENTS_PATH,'/'] as $p)
        check($by[$p]['observed']===302&&$by[$p]['result']==='PASS'&&$by[$p]['location']==='LOGIN'&&$by[$p]['locationOrigin']==='PINNED_ORIGIN','GUEST_REDIRECT_ON_THE_PINNED_ORIGIN_'.$p.'_'.$where.':'.json_encode([$by[$p]['observed']??null,$by[$p]['locationOrigin']??null]));
    check($by['/login']['result']==='PASS'&&($by['/login']['absoluteFormActionsOffOrigin']??-1)===0,'LOGIN_FORM_ACTION_ON_THE_ORIGIN_'.$where);
};
// The merchant e-mail on the legal pages: B1151 is live, so the count is the same before and after this rollout (the payload touches no legal text).
$emailState=static function(array $by,bool $installed,string $where):void{
    $e=$by['LEGAL_EMAIL_STATE'];
    check($e['result']===($installed?'INFO_SAME_AS_THE_OLD_STATE':'INFO_OLD_STATE_COUNT_RECORDED_NOT_JUDGED')&&$e['now']['checked']===8&&$e['now']['artifacts']===0,'EMAIL_STATE_UNCHANGED_'.$where.':'.json_encode([$e['result'],$e['now'],$e['oldState']??null]));
};
// B1153 is live before this rollout, so its catalog facts hold in BOTH states: the catalog page carries the per-page form and the grid/list switch; the layout queries answer 200 (20 and list are taken, 999 falls back to 40);
// the header logo img has the height attribute 32 (the B1153 default).
$viewRows=static function(array $by,string $where):void{
    $k=$by['/katalog'];check($k['perPageControl']===true&&$k['viewSwitch']===true&&$k['result']==='PASS','B1153_PER_PAGE_CONTROL_AND_VIEW_SWITCH_PRESENT_'.$where.':'.json_encode([$k['perPageControl']??null,$k['viewSwitch']??null,$k['result']??null]));
    $l=$by['/katalog?per_page=20&view=list'];check($l['observed']===200&&$l['result']==='PASS'&&$l['listLayout']===true&&$l['selected20']===true,'B1153_LIST_VIEW_20_PER_PAGE_'.$where.':'.json_encode($l));
    $d=$by['/katalog?per_page=999'];check($d['observed']===200&&$d['result']==='PASS'&&$d['selected40']===true&&$d['option999']===false,'B1153_PER_PAGE_999_FALLS_BACK_'.$where.':'.json_encode($d));
    $h=$by['SHOP_HEADER_LOGO_HEIGHT'];check($h['result']==='PASS'&&$h['heightAttribute']===32,'B1153_HEADER_LOGO_IMG_HEIGHT_ATTRIBUTE_32_'.$where.':'.json_encode($h));
    check($by['/login']['observed']===200&&$by['/up']['observed']===200,'LOGIN_AND_UP_200_'.$where);
};
// The rows only this rollout adds. The changed admin stylesheet: installed 200 text/css with the payload bytes; old: served with its pre-state bytes; the unchanged tokens stylesheet keeps its B1154 digest in both states.
$assetRows=static function(array $by,bool $installed,string $where)use($cssUrls):void{
    $r=$cssUrls===[]?null:($by['ADMIN_STYLESHEET '.$cssUrls[0]]??null);
    if($cssUrls===[]){}elseif($installed)check(is_array($r)&&$r['result']==='PASS'&&$r['observed']===200&&$r['contentType']==='text/css'&&$r['sha256EqualsThePayload']===true&&$r['ok']===true,'CHANGED_STYLESHEET_ROW_INSTALLED_'.$where.':'.json_encode($r));
    else check(is_array($r)&&$r['result']==='PASS'&&$r['observed']===200&&$r['contentType']==='text/css'&&$r['ok']===true,'CHANGED_STYLESHEET_ROW_OLD_SERVES_THE_PRE_STATE_BYTES_'.$where.':'.json_encode($r));
    $t=$by['TOKENS_STYLESHEET '.B1158InstallCoordinator::TOKENS_CSS_PATH]??null;check(is_array($t)&&$t['result']==='PASS'&&$t['ok']===true,'TOKENS_STYLESHEET_KEEPS_ITS_B1154_DIGEST_'.$where.':'.json_encode($t));
};
// The report URLs of B1155 and the foreign-Referer redirect are live in BOTH states; the guest bell URL: installed 302 to the login, old no route (never a 5xx); the CSP baselines.
$redirectRows=static function(array $by,bool $installed,string $where):void{
    foreach(['/admin/reports/subagents','/admin/reports/subagents.csv'] as $p){$r=$by[$p]??null;check(is_array($r)&&$r['observed']===302&&$r['result']==='PASS'&&$r['location']==='LOGIN'&&$r['locationOrigin']==='PINNED_ORIGIN','GUEST_REPORT_302_TO_THE_LOGIN_ON_THE_PINNED_ORIGIN_'.$p.'_'.$where.':'.json_encode($r));}
    $n=$by[B1158InstallCoordinator::NOTIFICATIONS_RECENT_PATH]??null;
    check(is_array($n)&&$n['observed']===302&&$n['result']==='PASS'&&$n['location']==='LOGIN','GUEST_BELL_URL_302_TO_THE_LOGIN_'.$where.':'.json_encode($n));
    $x=$by['FOREIGN_REFERER_REDIRECT']??null;check(is_array($x)&&$x['result']==='PASS'&&$x['observed']===302&&$x['locationKind']==='PINNED_CATALOG','FOREIGN_REFERER_REDIRECT_IS_PINNED_TO_THE_ORIGIN_CATALOG_'.$where.':'.json_encode($x));
    if($installed){$c=$by['CATALOG_CSP_UNCHANGED']??null;check(is_array($c)&&$c['result']==='PASS'&&$c['baselineRecorded']===true,'CATALOG_CSP_EQUALS_THE_PREFLIGHT_BASELINE_'.$where.':'.json_encode($c));
        $g=$by['REGISTER_CSP']??null;check(is_array($g)&&$g['result']==='PASS'&&$g['youtubeNocookieInFrameSrc']===$g['pageEmbedsYoutubeIframe'],'REGISTER_CSP_TURNSTILE_AS_BEFORE_'.$where.':'.json_encode($g));}
    else check(isset($by['CATALOG_CSP_BASELINE'])&&isset($by['REGISTER_CSP_BASELINE']),'OLD_STATE_RECORDS_THE_CSP_BASELINES_'.$where);
};
// A request with a FORGED Host header (CI only; the shipped tool never sends one): the redirect Location and the login form must stay on the pinned origin (B1152, live before and after this rollout).
$forged=static function():array{
    $r=B1158FixtureWeb::getHost(PORT,'/admin/settings/smtp','forged-host.invalid');$l=B1158FixtureWeb::getHost(PORT,'/login','forged-host.invalid');
    return ['status'=>$r['status'],'location'=>(string)$r['location'],'loginStatus'=>$l['status'],'loginBodyMentionsForged'=>str_contains($l['body'],'forged-host.invalid')];
};
// The e-mail header as the CLI renders it (nothing is sent: the mailer is the array driver, a MailMessage is only rendered): MailBranding::logo() and the HTML of a notification mail. B1153 is live, so the logo is there in BOTH states of this rollout.
$mailScript=$base.'/mail-check.php';file_put_contents($mailScript,'<?php chdir($argv[1]);require "vendor/autoload.php";$app=require "bootstrap/app.php";$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();$logo=class_exists(App\Support\MailBranding::class)?App\Support\MailBranding::logo():null;$html=(string)(new Illuminate\Notifications\Messages\MailMessage)->subject("x")->line("hello")->render();preg_match_all("~<img\\b[^>]*>~i",$html,$m);$img=null;foreach($m[0] as $tag)if(str_contains($tag,"site-assets"))$img=$tag;echo json_encode(["logoIsArray"=>is_array($logo),"logoSrcOnOrigin"=>is_array($logo)&&str_starts_with($logo["src"],"https://cms.ald1n.com/site-assets/"),"logoHeight"=>is_array($logo)?$logo["height"]:null,"logoOnDark"=>is_array($logo)?$logo["on_dark"]:null,"htmlHasLogoImg"=>$img!==null,"imgHeight"=>$img!==null&&preg_match("~height=\"(\\d+)\"~",$img,$h)?(int)$h[1]:null,"imgOnOrigin"=>$img!==null&&str_contains($img,"https://cms.ald1n.com/site-assets/"),"htmlBytes"=>strlen($html)],JSON_THROW_ON_ERROR);');
$mailCheck=static fn():array=>json_decode(sh(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$mailScript,$active],$active),true,8,JSON_THROW_ON_ERROR);
// What one stylesheet looks like to a browser and to the edge (CI only): the plain URL, the exact ?v=<file mtime> URL the new layout links, and the edge double with the real .htaccess headers: all three 200, text/css and the bytes of the file on disk.
$assetServed=static function(string $url,string $rel)use($active,$edge):array{
    $f=$active.'/'.$rel;$mt=(string)filemtime($f);$sha=hash_file('sha256',$f);$mime=static fn(array $r):string=>strtolower(trim(explode(';',(string)$r['contentType'])[0]));
    $plain=B1158FixtureWeb::get(PORT,$url);$ver=B1158FixtureWeb::get(PORT,$url.'?v='.$mt);$e=$edge->fetch($url,[],bin2hex(random_bytes(8)));
    return ['plain'=>$plain['status']===200&&$mime($plain)==='text/css'&&hash('sha256',$plain['body'])===$sha,'versioned'=>$ver['status']===200&&$mime($ver)==='text/css'&&hash('sha256',$ver['body'])===$sha,'edge'=>$e['status']===200&&$mime($e)==='text/css'&&hash('sha256',$e['body'])===$sha&&MediaWeb::nosniff($e['nosniff'])&&CatalogWeb::noindex($e['robots'])&&($e['htaccessHeadersApplied']??false)===true];
};
// A failed POST /login with a foreign Referer (CI only; the shipped tool never POSTs): the old validation redirect follows the Referer (back()), the payload redirects to the login URL on the pinned origin.
// The login is a nonexistent address, so no real account is ever counted by the login throttle; Turnstile is off in the fixture environment (TURNSTILE_ENABLED=false), the CSRF token and the session cookie come from a real GET /login.
$failedLogin=static function():array{
    $jar=[];$page=B1158FixtureWeb::jarGet(PORT,'/login',$jar);$tok=$page['status']===200?B1158FixtureWeb::csrfToken($page['body']):null;
    if($tok===null)return ['status'=>$page['status'],'location'=>'','kind'=>'NO_CSRF_TOKEN'];
    $r=B1158FixtureWeb::jarGet(PORT,'/login',$jar,http_build_query(['_token'=>$tok,'login'=>'ghost-'.bin2hex(random_bytes(4)).'@example.test','password'=>'Wrong-'.bin2hex(random_bytes(6)).'Aa1!'],'','&',PHP_QUERY_RFC3986),['Referer: '.B1158InstallCoordinator::FOREIGN_REFERER]);
    $loc=(string)($r['location']??'');$want=B1158InstallCoordinator::EXPECTED_APP_URL.'/login';
    return ['status'=>$r['status'],'location'=>$loc,'kind'=>$loc===''?'NONE':($loc===$want?'PINNED_LOGIN':(preg_match('~^https?://evil\.example(?:[/?#]|$)~i',$loc)===1?'FOREIGN_REFERER':'OTHER'))];
};
// The throwaway SuperAdministrator of the window evidence (CI only; the shipped tool never logs in). Its password is generated now, lives in this process and in the private run config of the runner, and is never printed. THE HARD LOGIN GATE: a real POST /login, an authenticated
// GET of the dashboard / (200, the B1154 shell, not yet the B1158 dashboard) and the logout must work in the fixture app at the OLD state BEFORE any run of the B1158 coordinator. Anything else FAILS the fixture: there is no CLI-only substitute and no skip.
$winLogin='b1158-window-ci';$winPassword=bin2hex(random_bytes(12)).'Aa1!';
$pdo()->prepare("INSERT INTO users (role_id,username,email,first_name,password_hash,status,created_at,updated_at) VALUES (?,?,?,?,?,'active',NOW(),NOW())")->execute([$roleId,$winLogin,$winLogin.'@example.test','Fixture',password_hash($winPassword,PASSWORD_BCRYPT)]);
$jarMain=[];$lg=B1158FixtureWeb::adminLogin(PORT,$winLogin,$winPassword,$jarMain);$dash=$lg['ok']?B1158FixtureWeb::jarGet(PORT,'/',$jarMain):null;$winMode='NOT_PROVEN';$winWhy='LOGIN_'.$lg['step'].'_STATUS_'.$lg['status'];
if($lg['ok']&&is_array($dash)){
    $winWhy='DASHBOARD_STATUS_'.$dash['status'];
    if($dash['status']===200&&str_contains($dash['body'],'a1-header__bar')&&str_contains($dash['body'],'a1-hero__title')){
        $tok=B1158FixtureWeb::csrfToken($dash['body']);$lo=$tok===null?null:B1158FixtureWeb::jarGet(PORT,'/logout',$jarMain,http_build_query(['_token'=>$tok],'','&',PHP_QUERY_RFC3986));$after=B1158FixtureWeb::jarGet(PORT,'/',$jarMain);
        if($lo!==null&&$lo['status']===302&&$after['status']===302){$winMode='AUTHENTICATED_HTTP';$winWhy='LOGIN_DASHBOARD_OF_THE_B1154_SHELL_AND_LOGOUT_WORK_IN_THE_FIXTURE_APP';}else $winWhy='LOGOUT_STATUS_'.json_encode([$lo['status']??null,$after['status']]);
    }
}
$jarMain=[];$results['hardLoginGate']=['mode'=>$winMode,'feasibility'=>$winWhy];
check($winMode==='AUTHENTICATED_HTTP','HARD_LOGIN_GATE_AUTHENTICATED_LOGIN_DASHBOARD_AND_LOGOUT_WORK_IN_THE_FIXTURE_'.$winWhy);putenv('AUTHENTICATED_HTTP=1');check(getenv('AUTHENTICATED_HTTP')==='1','AUTHENTICATED_HTTP_ENVIRONMENT_IS_SET_FOR_THE_CHILD_RUNNERS');
// The sub-agent (role user, a seeded user group: the real setup of tests/Feature/SubagentSalesReportTest.php) and an admin (allowed, sees only the orders it supplies): CI-only accounts with generated passwords.
$roleOf=static fn(string $slug):int=>(int)$pdo()->query("SELECT id FROM roles WHERE slug='".$slug."'")->fetchColumn();$groupId=(int)$pdo()->query('SELECT id FROM user_groups ORDER BY id LIMIT 1')->fetchColumn();
check($roleOf('user')>0&&$roleOf('admin')>0&&$groupId>0,'THE_USER_AND_ADMIN_ROLES_AND_A_USER_GROUP_EXIST');
$subLogin='b1158-subagent-ci';$subPassword=bin2hex(random_bytes(12)).'Bb2!';$admLogin='b1158-admin-ci';$admPassword=bin2hex(random_bytes(12)).'Cc3!';
$pdo()->prepare("INSERT INTO users (role_id,user_group_id,username,email,first_name,last_name,password_hash,status,created_at,updated_at) VALUES (?,?,?,?,?,?,?,'active',NOW(),NOW())")->execute([$roleOf('user'),$groupId,$subLogin,$subLogin.'@example.test','Sub','Agent',password_hash($subPassword,PASSWORD_BCRYPT)]);
$pdo()->prepare("INSERT INTO users (role_id,username,email,first_name,last_name,password_hash,status,created_at,updated_at) VALUES (?,?,?,?,?,?,'active',NOW(),NOW())")->execute([$roleOf('admin'),$admLogin,$admLogin.'@example.test','Ad','Min',password_hash($admPassword,PASSWORD_BCRYPT)]);
check((int)$pdo()->query("SELECT COUNT(*) FROM users WHERE username IN ('".$subLogin."','".$admLogin."')")->fetchColumn()===2,'SUBAGENT_AND_ADMIN_USERS_CREATED');
$winCfg=['mode'=>$winMode,'login'=>$winLogin,'password'=>$winPassword];
$reset();
// 6. A route cache present: B1146 to B1153 ran without one, so the preflight HOLDs; the cache is never touched.
$artisan(['route:cache']);$cacheBefore=$routeCacheSHA();check($cacheBefore!==null,'ROUTE_CACHE_CREATED');$reset();
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');
check($rc===2&&lastLine($o,'B1158_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('ROUTE_CACHE_PRESENT_NOTHING_CHANGED',$p['blockers'],true)&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE'&&$routeCacheSHA()===$cacheBefore,'ROUTE_CACHE_PRESENT_HOLD_UNTOUCHED');
$artisan(['route:clear']);check($routeCacheSHA()===null,'FIXTURE_ROUTE_CACHE_REMOVED');$reset();$results['routeCachePresent']='HOLD_NOTHING_CHANGED_CACHE_BYTES_UNCHANGED';
// 6b. The chain gate on Report1120 (and on the B1154 state it names): every way of not being the verified B1154 PASS is a HOLD with nothing
// changed: the report missing, only a decoy that is not a PASS, the real report beside a decoy (two reports), the production digest pin (the
// fixture's report cannot have it), a different B1154 state id, a B1154 state that is not INSTALLED_PASS.
$gate=static function(string $code,string $name,array $overrides=[])use($run,$tree,$reset):void{
    [$rc,$o]=$run([],'install',$overrides);$p=lastLine($o,'PREFLIGHT');
    check($rc===2&&lastLine($o,'B1158_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array($code,$p['blockers'],true)&&!str_contains($o,'PER_FILE_BACKUP=')&&!str_contains($o,'OVERLAY_STEP=')&&$tree()==='PRE','CHAIN_GATE_'.$name.':'.json_encode($p['blockers']));$reset();
};
$decoy1123=$root.'/docs/operations/1123-B1157-INSTALLER-20261123-101000.md';
rename($report1123,$report1123.'.moved');$gate('B1157_REPORT1123_MISSING','REPORT_1121_MISSING');
file_put_contents($decoy1123,"B1154_FINAL=HOLD\nHOLD_CODE=PREFLIGHT_BLOCKED\nEXIT=2\n");$gate('B1157_REPORT_NOT_TERMINAL_PASS','DECOY_1121_IS_NOT_A_PASS');
rename($report1123.'.moved',$report1123);$gate('B1157_REPORT1123_AMBIGUOUS','TWO_1121_REPORTS');unlink($decoy1123);
$gate('B1157_REPORT1123_DIGEST_DIFFERS_FROM_THE_PINNED_ONE','REPORT_DIGEST_DIFFERS_FROM_THE_PRODUCTION_PIN',['b1157ReportSHA256'=>str_repeat('d',64)]);
$gate('B1157_STATE_ID_DIFFERS_FROM_THE_PINNED_ONE','STATE_ID_DIFFERS',['b1157StateId'=>str_repeat('a',32)]);
$stateFile=$private1157.'/'.$pin['state'].'/state.json';$stateOriginal=(string)file_get_contents($stateFile);check(str_contains($stateOriginal,'"phase":"INSTALLED_PASS"'),'THE_B1157_STATE_IS_INSTALLED_PASS');file_put_contents($stateFile,str_replace('"phase":"INSTALLED_PASS"','"phase":"ROLLED_BACK"',$stateOriginal));
$gate('B1157_STATE_NOT_INSTALLED_PASS','B1157_STATE_NOT_INSTALLED_PASS');file_put_contents($stateFile,$stateOriginal);
// A report that is the right PASS but one byte different (a changed digest) is the same HOLD as the production pin: the pin is the host file's bytes. The production pins are Report1120's facts:
// its sha256 is that of the git blob (the CRLF worktree copy on a Windows checkout has another digest), its state id and its route count are the ones Report1120 printed.
check(B1158InstallCoordinator::ROUTES===597&&B1158InstallCoordinator::ROUTES_AFTER===B1158Payload::ROUTES_AFTER&&B1158InstallCoordinator::B1157_TREE_FILES===8620&&B1158InstallCoordinator::installedTreeFiles()===B1158Payload::TREE_FILES_AFTER&&B1158InstallCoordinator::EXPECTED_APP_URL==='https://cms.ald1n.com'&&B1158InstallCoordinator::RAN===76&&B1158InstallCoordinator::RAN_AFTER===76+count(B1158InstallCoordinator::NEW_MIGRATIONS),'THE_PRODUCTION_CONSTANTS_ARE_597_ROUTES_8620_FILES_76_RAN_AND_THE_GENERATED_DELTAS');
// The repository copy of Report1121 is the file the pin names: its git blob digest is the production pin (only checked where the repository is the checkout, as in CI).
foreach(glob(dirname($tools,3).'/docs/operations/1123-B1157-INSTALLER-*.md')?:[] as $repoReport)check(B1158Payload::CHAIN_PINS_FILLED&&hash_file('sha256',$repoReport)===B1158InstallCoordinator::B1157_REPORT_SHA256,'THE_ARCHIVED_REPORT1122_HAS_THE_PINNED_DIGEST');
// 6c. The pin is strict: the effective config('app.url') must be exactly https://cms.ald1n.com. A trailing slash is a HOLD with nothing changed (the .env of the
// retained tree is restored by the reset).
file_put_contents($envFile,str_replace('APP_URL=https://cms.ald1n.com','APP_URL=https://cms.ald1n.com/',$envOriginal));$artisan(['cache:clear']);
$gate('APP_URL_NOT_EXACTLY_THE_PINNED_ORIGIN_NOTHING_CHANGED','APP_URL_WITH_TRAILING_SLASH');check((string)file_get_contents($envFile)===$envOriginal,'ENV_RESTORED_BY_THE_RESET');
file_put_contents($envFile,str_replace('APP_URL=https://cms.ald1n.com','APP_URL=http://cms.ald1n.com',$envOriginal));$artisan(['cache:clear']);
$gate('APP_URL_NOT_EXACTLY_THE_PINNED_ORIGIN_NOTHING_CHANGED','APP_URL_PLAIN_HTTP');
// 6d. INSIDE THE BACKUP WINDOW: 20 minutes before the 02:30 backup an INSTALL is a HOLD with nothing changed (the install window this run needs would run into the backup). The same clock outside the window is the
// clock of every other case of this fixture. The Sunday window (03:10) is the same rule with the other schedule row.
[$rc,$o]=$run([],'install',['fixedNow'=>$conflictNow]);$p=lastLine($o,'PREFLIGHT');
check($rc===2&&lastLine($o,'B1158_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&preg_grep('/^SCHEDULED_BACKUP_WITHIN_THE_NEEDED_WINDOW_[A-Z]+$/D',$p['blockers'])!==[]&&!str_contains($o,'PER_FILE_BACKUP=')&&!str_contains($o,'OVERLAY_STEP=')&&$tree()==='PRE','INSTALL_INSIDE_THE_BACKUP_WINDOW_HOLDS_NOTHING_CHANGED:'.json_encode($p['blockers']));$reset();
$sundayNow=(new DateTimeImmutable('2026-11-15 02:55:00',new DateTimeZone('Europe/Belgrade')))->getTimestamp();
[$rc,$o]=$run([],'install',['fixedNow'=>$sundayNow]);$p=lastLine($o,'PREFLIGHT');
check($rc===2&&lastLine($o,'B1158_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&preg_grep('/^SCHEDULED_BACKUP_WITHIN_THE_NEEDED_WINDOW_[A-Z_]+$/D',$p['blockers'])!==[]&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','INSTALL_INSIDE_THE_SUNDAY_BACKUP_WINDOW_HOLDS_NOTHING_CHANGED:'.json_encode($p['blockers']));$reset();
$results['chainGate']=['report1121Missing'=>'HOLD','decoy1121'=>'HOLD','twoReports'=>'HOLD','digestPin'=>'HOLD','stateId'=>'HOLD','stateNotInstalledPass'=>'HOLD','appUrlTrailingSlash'=>'HOLD','appUrlHttp'=>'HOLD','insideTheBackupWindow0230AndSunday0310'=>'HOLD','nothingChanged'=>true];
// 7. Refusal: an unknown pre-state of the first and of the last replaced file stops the run before any change.
foreach([reset($modifiedSteps),end($modifiedSteps)] as $victim){
    $ctl=$active.'/'.$victim['path'];$original=file_get_contents($ctl);file_put_contents($ctl,$original."\n// foreign local edit\n");
    [$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&lastLine($o,'B1158_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&in_array('UNKNOWN_PRE_STATE_REFUSED',$p['blockers'],true)&&!str_contains($o,'OVERLAY_STEP=')&&!str_contains($o,'PER_FILE_BACKUP='),'UNKNOWN_PRE_STATE_REFUSED_'.$victim['n']);
    $newPresent=array_filter($newSteps,static fn($s)=>is_file($active.'/'.$s['path']));check(file_get_contents($ctl)===$original."\n// foreign local edit\n"&&$newPresent===[],'UNKNOWN_PRE_STATE_NOTHING_CHANGED_'.$victim['n']);file_put_contents($ctl,$original);$reset();
}
$results['unknownPreState']='HOLD_NOTHING_CHANGED_FIRST_AND_LAST_MODIFIED_FILE';
// 7b. A foreign file at the path of a new class and at the path of a migration is a HOLD with nothing changed too.
foreach([$byPath[(array_values(array_filter(array_column($newSteps,'path'),static fn($p)=>str_ends_with($p,'.php')&&!str_contains($p,'database/')))[0])],...($zero?[]:[$stepMig1])] as $nv){$foreignNew=$active.'/'.$nv['path'];file_put_contents($foreignNew,"<?php\n// foreign file at a new path\n");[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');
    check($rc===2&&in_array('UNKNOWN_PRE_STATE_REFUSED',$p['blockers'],true)&&in_array('NEW_PATH_OR_DIRECTORY_ALREADY_EXISTS_NOTHING_CHANGED',$p['blockers'],true)&&!str_contains($o,'OVERLAY_STEP=')&&!str_contains($o,'PER_FILE_BACKUP=')&&file_get_contents($foreignNew)==="<?php\n// foreign file at a new path\n",'FOREIGN_FILE_AT_A_NEW_PATH_HOLDS_NOTHING_CHANGED_'.$nv['path']);unlink($foreignNew);$reset();}
$results['newPathAlreadyThere']='HOLD_NOTHING_CHANGED_CLASS_AND_MIGRATION_PATH';
// 8. Preflight facts with the B1155 tree (the OLD state of this rollout): every B1149 to B1155 fact holds, the 32 B1158 markers are false, no new class, library method or route, 71 ran 0 pending, 570 routes, APP_URL exact, nothing written.
[$rc,$o]=$run(['AFTER_PREFLIGHT'=>['fail']]);$ft=allLines($o,'FEATURE_CLI_CHECK')[0]??null;$en=allLines($o,'ENVIRONMENT_CLI_CHECK')[0]??null;$rcOld=allLines($o,'ROUTES_CHECK')[0]??null;$pf=allLines($o,'PREFLIGHT')[0]??null;
check(is_array($ft)&&$ft['expectInstalled']===false&&$ft['themeRoutePostOnly']===true&&$ft['routesCached']===false&&$ft['b1150ClassesLoaded']===true&&$ft['routeMedia']===true&&$ft['throttleApi']===true&&$ft['originConfiguredIsExpected']===true&&$ft['middlewareGlobal']===true&&$ft['pdfRasterGuard']===true&&$ft['logoHeightsApi']===true&&$ft['catalogWhitelist']===true&&$ft['bootstrapNamesRedirect']===true&&count($ft['priorMarkers'])===count(B1158InstallCoordinator::PRIOR_MARKERS)&&!in_array(false,$ft['priorMarkers'],true)&&$ft['newClassesLoaded']===0&&$ft['newClassesTotal']===count(B1158InstallCoordinator::newClasses())&&count($ft['sourceMarkers'])===count(B1158InstallCoordinator::SOURCE_MARKERS)&&!in_array(true,$ft['sourceMarkers'],true),'OLD_STATE_FEATURES_PRESENT_THE_NEW_MARKERS_ABSENT:'.json_encode($ft));
check(is_array($rcOld)&&$rcOld['routeCount']===$pin['routes']&&$rcOld['expectedRouteCount']===$pin['routes']&&$rcOld['installed']===false&&$rcOld['b1146RouteNamesPresent']===11,'ROUTE_COUNT_IS_THE_B1155_COUNT_AND_THE_NEW_ROUTE_NAMES_ARE_ABSENT_BEFORE:'.json_encode($rcOld));
check(is_array($en)&&$en['appUrlHttpsOrigin']===true&&$en['appUrlExact']===true&&!array_key_exists('mailFingerprintHMAC',$en),'ENVIRONMENT_FACTS_APP_URL_EXACT_WITHOUT_ANY_FINGERPRINT');
check(is_array($pf)&&$pf['blockers']===[]&&$pf['values']['scope']['payloadUnderConfigComposerEnv']===0&&$pf['values']['scope']['databasePathsOtherThanTheTwoMigrations']===0&&$pf['values']['scope']['migrations']['files']===B1158InstallCoordinator::NEW_MIGRATIONS&&($pf['values']['migrations']['ran']??null)===B1158InstallCoordinator::RAN&&($pf['values']['migrations']['pending']??null)===[],'PREFLIGHT_SCOPE_AND_THE_MIGRATION_SET_71_RAN_0_PENDING:'.json_encode([$pf['values']['scope']??null,$pf['values']['migrations']??null]));
[$s,$by]=$smokeRows($o);check($s['state']==='OLD','OLD_STATE_SMOKES_SHAPE');$b1149Intact($s,$by,'OLD');$pinRows($by,'OLD');$emailState($by,false,'OLD');$viewRows($by,'OLD');$assetRows($by,false,'OLD');$redirectRows($by,false,'OLD');
check($tree()==='PRE'&&!str_contains($o,'OVERLAY_STEP='),'PREFLIGHT_FACTS_NOTHING_CHANGED');
$results['preflightFacts']='B1149_TO_B1155_PRESENT_THE_32_B1158_MARKERS_ABSENT_71_RAN_0_PENDING_570_ROUTES';
$reset();
// 9. With the shop OFF a fresh install HOLDs; the switch is never written.
$shopOff();[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');check($rc===2&&in_array('SHOP_IS_OFF_B1158_INSTALLS_ONLY_WITH_THE_SHOP_ON_NOTHING_CHANGED',$p['blockers'],true)&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE'&&$shopFlag()===null,'SHOP_OFF_INSTALL_REFUSED');$shopOn();$reset();$results['shopOffInstall']='HOLD_NOTHING_CHANGED';
// 9b. The live doctor fails before any change (the file of the newest public image is missing): HOLD, the tree untouched, nothing backed up.
$newest=$pdo()->query("SELECT file_path FROM product_images WHERE storage_disk='public' ORDER BY id DESC LIMIT 1")->fetchColumn();$newestFile=$retained.'/storage/app/public/'.$newest;check(is_file($newestFile)&&rename($newestFile,$newestFile.'.moved'),'NEWEST_IMAGE_FILE_MOVED_AWAY');
[$rc,$o]=$run();$p=lastLine($o,'PREFLIGHT');rename($newestFile.'.moved',$newestFile);
check($rc===2&&in_array('PRODUCT_MEDIA_DOCTOR_FAILED',$p['blockers'],true)&&!str_contains($o,'PER_FILE_BACKUP=')&&!str_contains($o,'OVERLAY_STEP=')&&$tree()==='PRE','DOCTOR_FAIL_IN_PREFLIGHT_HOLDS_NOTHING_CHANGED');$reset();$results['doctorFailPreflight']='HOLD_NOTHING_CHANGED';

// 10. An artisan run that starts after the preflight is seen by the census BEFORE the backup; nothing is written.
[$rc,$o]=$run(['AFTER_PREFLIGHT'=>['busy']]);$pid=(int)trim((string)@file_get_contents($busyPidFile));if($pid>0){@posix_kill(-$pid,9);@posix_kill($pid,9);}shell_exec("pkill -9 -f 'sleep 40; true' || true");@unlink($busyPidFile);
check($rc===2&&lastLine($o,'B1158_PHASE')==='HOLD_PREFLIGHT_BLOCKED'&&str_contains($o,'"blockers":["CMS_ARTISAN_RUNNING"]')&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','CENSUS_BEFORE_BACKUP');$reset();
// A failure before the first file: "nothing applied", no view or web work.
[$rc,$o]=$run(['BEFORE_STEP_1'=>['fail']]);$tail=substr($o,(int)strpos($o,'OVERLAY_ROLLBACK='));
check($rc===2&&lastRaw($o,'B1158_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&lastLine($o,'ROLLBACK_FILES')['nothingApplied']===true&&!str_contains($tail,'ARTISAN=')&&!str_contains($tail,'WEB_OVERLAY_PROBE=')&&$tree()==='PRE','NOTHING_APPLIED_NO_REFRESH');$reset();
// The time budget is checked right before the first change.
[$rc,$o]=$run([],'install',['budgetSeconds'=>0]);check($rc===2&&str_contains($o,'"blockers":["TIME_BUDGET_EXCEEDED_BEFORE_FIRST_CHANGE"]')&&!str_contains($o,'PER_FILE_BACKUP=')&&$tree()==='PRE','TIME_BUDGET_HOLD');$reset();
// A catalog page without its JSON-LD block, and one without its CSP: acceptance fails, rolled back.
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['dropJsonLd'],'BEFORE_SMOKES_OLD'=>['clearDrops']]);[$s,$by]=$smokeRows(substr($o,0,(int)strpos($o,'OVERLAY_ROLLBACK=')));
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"INSTALLED_SMOKE_FAILED"'&&$by['/katalog']['jsonLd']==='JSON_LD_MISSING'&&lastRaw($o,'B1158_FINAL')===($zero?'HOLD_ROLLED_BACK_OLD_STATE_VERIFIED':'HOLD_ROLLED_BACK_SCHEMA_AHEAD'),'MISSING_JSON_LD_AFTER_THE_MIGRATION_IS_A_SCHEMA_AHEAD_ROLLBACK');$reset();
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['dropCsp'],'BEFORE_SMOKES_OLD'=>['clearDrops']]);
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')==='"INSTALLED_SMOKE_FAILED"'&&lastRaw($o,'B1158_FINAL')===($zero?'HOLD_ROLLED_BACK_OLD_STATE_VERIFIED':'HOLD_ROLLED_BACK_SCHEMA_AHEAD'),'MISSING_CSP_AFTER_THE_MIGRATION_IS_A_SCHEMA_AHEAD_ROLLBACK');$reset();
$results+=['censusBeforeBackup'=>'HOLD_NOTHING_WRITTEN','nothingApplied'=>'NO_REFRESH_NO_WEB_WORK','timeBudget'=>'HOLD','missingJsonLd'=>'ROLLED_BACK','missingCsp'=>'ROLLED_BACK'];

// ================================================================== B1158 (commissions and migrations) cases ==================================================================
// 8b. The commission rows (CI only): the d2c07f62 schema (71 migrations ran) holds realistic data. Rows are inserted with the real column set (information_schema), the NOT NULL columns without a default get a neutral value, the interesting
// columns are set explicitly. The set the backfill must move is written down HERE from the owner rule (a pending commission of an order that is delivered or fully paid and not cancelled), independent of the tool's own census.
$colsOf=static function(string $table)use($pdo):array{$st=$pdo()->prepare('SELECT COLUMN_NAME,DATA_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA,COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');$st->execute([DB,$table]);return $st->fetchAll(PDO::FETCH_ASSOC);};
$enumPick=static function(string $table,string $column,array $prefer)use($colsOf):string{foreach($colsOf($table) as $c)if($c['COLUMN_NAME']===$column){if(preg_match_all("/'([^']*)'/",(string)$c['COLUMN_TYPE'],$m)&&$c['DATA_TYPE']==='enum'){foreach($prefer as $p)if(in_array($p,$m[1],true))return $p;return $m[1][0];}return $prefer[0];}throw new RuntimeException('B1158_FIXTURE_COLUMN_UNKNOWN_'.$table.'.'.$column);};
$insertRow=static function(string $table,array $over)use($colsOf,$pdo,$ownerId):int{
    $vals=[];foreach($colsOf($table) as $c){$n=$c['COLUMN_NAME'];if(array_key_exists($n,$over)){$vals[$n]=$over[$n];continue;}
        if(str_contains((string)$c['EXTRA'],'auto_increment')||str_contains((string)$c['EXTRA'],'GENERATED')||$c['IS_NULLABLE']==='YES'||$c['COLUMN_DEFAULT']!==null)continue;
        $t=(string)$c['DATA_TYPE'];$vals[$n]=match(true){in_array($t,['int','bigint','smallint','tinyint','mediumint'],true)=>(str_ends_with($n,'_id')||str_contains($n,'user')?$ownerId:0),in_array($t,['decimal','float','double'],true)=>'0',in_array($t,['datetime','timestamp'],true)=>date('Y-m-d H:i:s'),$t==='date'=>date('Y-m-d'),$t==='json'=>'{}',$t==='enum'=>(preg_match("/^enum\('([^']*)'/",(string)$c['COLUMN_TYPE'],$m)?$m[1]:''),default=>'x'};}
    $db=$pdo();$cols=array_keys($vals);$db->prepare('INSERT INTO '.$table.' ('.implode(',',$cols).') VALUES ('.implode(',',array_fill(0,count($cols),'?')).')')->execute(array_values($vals));return (int)$db->lastInsertId();
};
$statusOpen='processing';$statusDone='shipped';$statusCancelled='cancelled';
check($statusCancelled==='cancelled','THE_CANONICAL_CANCELLED_STATUS_EXISTS');
$now=date('Y-m-d H:i:s');$seed=[];
$mkOrder=static function(string $tag,string $status,string $payState,?string $completedAt)use($insertRow,$ownerId,$now):int{return $insertRow('orders',['order_number'=>'APC-'.strtoupper($tag).'-'.random_int(1000,9999),'status'=>$status,'payment_state'=>$payState,'completed_at'=>$completedAt,'user_id'=>$ownerId,'created_by'=>$ownerId,'created_at'=>$now,'updated_at'=>$now]);};
$mkCommission=static function(int $orderId,string $status,?int $approvedBy)use($insertRow,$ownerId,$now):int{return $insertRow('order_commissions',['order_id'=>$orderId,'user_id'=>$ownerId,'total_eur'=>'12.34','status'=>$status,'approved_by'=>$approvedBy,'approved_at'=>$approvedBy===null?null:$now,'status_updated_at'=>$now,'created_at'=>$now,'updated_at'=>$now]);};
$orderPaid=$mkOrder('PAID',$statusOpen,'paid',null);$orderDelivered=$mkOrder('DELIV',$statusDone,'unpaid',$now);$orderOpen=$mkOrder('OPEN',$statusOpen,'unpaid',null);$orderCancelled=$mkOrder('CANC',$statusCancelled,'paid',null);$orderPaidDone=$mkOrder('PDONE',$statusDone,'paid',$now);$orderLegacy=$mkOrder('LEGAC',$statusOpen,'paid',null);
$seed=['paidOrderPending'=>$mkCommission($orderPaid,'pending',null),'deliveredOrderPending'=>$mkCommission($orderDelivered,'pending',null),'unpaidUndeliveredPending'=>$mkCommission($orderOpen,'pending',null),'cancelledOrderPending'=>$mkCommission($orderCancelled,'pending',null),'paidCommission'=>$mkCommission($orderPaidDone,'paid',$ownerId),'legacyApproved'=>$mkCommission($orderLegacy,'approved',$ownerId)];
$expectedMoved=[$seed['paidOrderPending'],$seed['deliveredOrderPending']];sort($expectedMoved);
$seedSnapshot=['historyMax'=>(int)$pdo()->query('SELECT COALESCE(MAX(id),0) FROM commission_status_history')->fetchColumn(),'rows'=>$pdo()->query('SELECT * FROM order_commissions ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)];
$notifCounts=static function()use($pdo):array{$out=[];foreach($pdo()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $tb)if(preg_match('/notif|outbox|mail|push|jobs$|job_batches/i',(string)$tb)===1&&$tb!=='notification_preferences')$out[$tb]=(int)$pdo()->query('SELECT COUNT(*) FROM '.$tb)->fetchColumn();ksort($out);return $out;};
$notifBefore=$notifCounts();check($notifBefore!==[],'THE_NOTIFICATION_TABLES_ARE_COUNTED_NOT_AN_EMPTY_SET:'.json_encode(array_keys($notifBefore)));
$statusOf=static function()use($pdo):array{$o=[];foreach($pdo()->query('SELECT id,status,approved_by,approved_at FROM order_commissions')->fetchAll(PDO::FETCH_ASSOC) as $r)$o[(int)$r['id']]=$r;return $o;};
$schemaIs=static function()use($pdo):array{$db=$pdo();$t=0;foreach(B1158InstallCoordinator::NEW_TABLES as $tb)$t+=(int)$db->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='".DB."' AND TABLE_NAME='".$tb."'")->fetchColumn();$c=0;foreach(B1158InstallCoordinator::NEW_COLUMNS as $tb=>$cols)foreach($cols as $col)$c+=(int)$db->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='".DB."' AND TABLE_NAME='".$tb."' AND COLUMN_NAME='".$col."'")->fetchColumn();$m=(int)$db->query("SELECT COUNT(*) FROM migrations WHERE migration IN ('".implode("','",B1158InstallCoordinator::NEW_MIGRATIONS)."')")->fetchColumn();$ran=(int)$db->query('SELECT COUNT(*) FROM migrations')->fetchColumn();return ['tables'=>$t,'columns'=>$c,'migrationRows'=>$m,'ran'=>$ran];};
$schemaOld=static fn():bool=>($s=$schemaIs())['tables']===0&&$s['columns']===0&&$s['migrationRows']===0&&$s['ran']===B1158InstallCoordinator::RAN;
$schemaNew=static fn():bool=>($s=$schemaIs())['tables']===count(B1158InstallCoordinator::NEW_TABLES)&&$s['columns']===array_sum(array_map('count',B1158InstallCoordinator::NEW_COLUMNS))&&$s['migrationRows']===$nMig&&$s['ran']===B1158InstallCoordinator::RAN_AFTER;
$winCfg['orderId']=$orderPaid;
check($schemaOld(),'THE_FIXTURE_DATABASE_IS_AT_THE_B1156_SCHEMA_73_RAN');$reset();
// The sub-agent of the windows owns the seeded commissions' orders in the old schema too (CI only): nothing more is needed for the pages to render rows.
$winCfg['sub']=['login'=>$subLogin,'password'=>$subPassword];$winCfg['admin']=['login'=>$winLogin,'password'=>$winPassword];
$extraCfg=['adminWindow'=>$winCfg,'orderId'=>$orderPaid];
// BASELINE (old code, old schema, the seeded rows): the pages of both users the windows will judge. A page that already answers 5xx here (a synthetic row the page does not like) is not a finding of the install and is skipped by the window judgement.
$baselinePages=[];foreach([['admin',$winLogin,$winPassword,['/','/admin/commissions','/admin/orders','/admin/orders/'.$orderPaid,'/admin/after-sales','/admin/settings/modules','/admin/reports']],['sub',$subLogin,$subPassword,['/','/orders','/orders/'.$orderPaid,'/commissions']]] as [$who,$lg,$pw,$pgs]){$j=[];$l=B1158FixtureWeb::adminLogin(PORT,$lg,$pw,$j);foreach($pgs as $pg){$r=$l['ok']?B1158FixtureWeb::jarGet(PORT,$pg,$j):['status'=>0];$baselinePages[$who.' '.$pg]=$r['status'];}}
check(($baselinePages['admin /']??0)===200&&($baselinePages['admin /admin/commissions']??0)===200&&($baselinePages['admin /admin/orders']??0)===200&&($baselinePages['sub /']??0)===200&&($baselinePages['sub /orders']??0)===200,'BASELINE_THE_OLD_CODE_SERVES_THE_MAIN_PAGES_OF_BOTH_USERS:'.json_encode($baselinePages));
$results['baselinePages']=$baselinePages;
$judge=static function(array $rows,string $label)use($stepRows,$baselinePages):void{
    $bad=[];
    foreach($rows as $r){
        foreach($r['anon'] as $p=>$st)if($st!==200&&!($st===429&&preg_match('~^/(katalog|registracija|pravne-informacije|korpa|porucivanje|uslovi-prodaje|politika-privatnosti|podaci-o-trgovcu|pravo-na-odustanak|reklamacije|dostava-i-placanje|kolacici)~',(string)$p)===1))$bad[]=$r['point'].' '.$p.' '.$st;
        foreach(['/up','/login'] as $p)if(($r['anon'][$p]??null)!==200)$bad[]=$r['point'].' '.$p.' missing';
        if(array_key_exists('themePost',$r)&&($r['themePost']!==303||$r['themeCookie']!==true))$bad[]=$r['point'].' POST /tema '.$r['themePost'];
        foreach(['dashGuest','smtpGuest','merchantGuest','documentsGuest','reportGuest','reportCsvGuest'] as $k)if($r[$k]!==302)$bad[]=$r['point'].' '.$k.' '.$r[$k];
        if(!in_array($r['bellGuest'],[404,302],true))$bad[]=$r['point'].' guest bell URL '.$r['bellGuest'];
        if((($r['dashAuth']??null)!==200||($r['dashAuthShell']??null)!==true))$bad[]=$r['point'].' AUTHENTICATED superadmin dashboard '.json_encode([$r['dashAuth']??null,$r['dashAuthShell']??null]);
        if(($r['commissionsAuth']??null)!==200)$bad[]=$r['point'].' superadmin commissions page '.var_export($r['commissionsAuth']??null,true);
        if(($r['dashSub']??null)!==200)$bad[]=$r['point'].' AUTHENTICATED sub-agent dashboard '.var_export($r['dashSub']??null,true);
        if(!in_array($r['commissionsSub']??null,[200,403],true))$bad[]=$r['point'].' sub-agent commissions page '.var_export($r['commissionsSub']??null,true);
        if(isset($r['adminWindow'])){foreach(['admin','sub'] as $who)foreach($r['adminWindow'][$who] as $pg=>$f)if($f['status']>=500&&($baselinePages[$who.' '.$pg]??0)<500)$bad[]=$r['point'].' WINDOW '.$who.' '.$pg.' '.$f['status'];
            foreach(['/','/admin/commissions','/admin/orders'] as $pg)if(($r['adminWindow']['admin'][$pg]['status']??0)!==200)$bad[]=$r['point'].' WINDOW admin '.$pg.' '.($r['adminWindow']['admin'][$pg]['status']??'none');
            foreach(['/','/orders'] as $pg)if(($r['adminWindow']['sub'][$pg]['status']??0)!==200)$bad[]=$r['point'].' WINDOW sub '.$pg.' '.($r['adminWindow']['sub'][$pg]['status']??'none');}
    }
    check($bad===[],$label.':'.implode(',',array_slice($bad,0,12)));
};
$tampered=static function(string $path)use($active):string{$f=$active.'/'.$path;return is_file($f)?hash_file('sha256',$f):'ABSENT';};
$orphanOk=static function()use($active):bool{foreach(array_merge(CommissionsInstallCoordinator::ORPHANS,B1158InstallCoordinator::ORPHANS) as $p=>$sha)if(!is_file($active.'/'.$p)||hash_file('sha256',$active.'/'.$p)!==$sha)return false;return true;};
check($orphanOk(),'THE_TWO_ORPHAN_VIEWS_ARE_ON_THE_HOST_WITH_THEIR_B1155_BYTES_BEFORE');

// 9c. Guard groups without their proof are refused, each rolled back to the exact old tree (D: the provider needs the class proof, E: the routes need it, F: the views need the asset proof).
foreach(array_values(array_filter([['D',($stepProvider['n']??0),'clearClassProof:D','GUARD_REFUSED_NO_WEB_SAPI_CLASS_PROOF'],['E',($stepRoutesApi['n']??0),'clearClassProof:E','GUARD_REFUSED_NO_WEB_SAPI_CLASS_PROOF'],['F',(array_values(array_filter($plan,static fn($s)=>$s['group']==='F'))[0]['n']??0),'clearAssetProof','GUARD_REFUSED_NO_WEB_SAPI_ASSET_PROOF']],static fn($x)=>isset(B1158InstallCoordinator::GUARDS[$x[0]]))) as [$g,$n,$act,$code]){
    [$rc,$o]=$run(['BEFORE_STEP_'.$n=>[$act]]);
    check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')===json_encode($code)&&lastRaw($o,'B1158_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&$schemaOld(),'GUARD_GROUP_'.$g.'_REFUSED_WITHOUT_ITS_PROOF_AND_ROLLED_BACK');$reset();
}
$results['guardGroups']='D_E_F_REFUSED_WITHOUT_THEIR_PROOF_ROLLED_BACK';

// 12. THE INSTALL. A smoke after EVERY step and every group boundary, after the database backup (all files in, OLD schema) and after the migration; both users' pages at every point (hard login gate passed).
$allPoints=$allSteps()+($zero?[]:['AFTER_DB_BACKUP'=>['stepSmoke'],'AFTER_MIGRATED'=>['stepSmoke']]);
$histBefore=(int)$pdo()->query('SELECT COUNT(*) FROM commission_status_history')->fetchColumn();$notifBefore=$notifCounts();
[$rc,$o]=$run($allPoints,'install',[],$extraCfg);$rows=$stepRows();echo 'STEP_SMOKES='.json_encode($rows,JSON_UNESCAPED_SLASHES).PHP_EOL;
check(!str_contains($o,$winPassword)&&!str_contains(json_encode($rows),$winPassword)&&!str_contains($o,$subPassword)&&!str_contains($o,$admPassword),'THE_THROWAWAY_PASSWORDS_ARE_IN_NO_OUTPUT');
$logErrors=static function()use($logDir):array{$out=[];foreach(glob($logDir.'/laravel*.log')?:[] as $lf)foreach(file($lf,FILE_IGNORE_NEW_LINES)?:[] as $ln)if(str_contains($ln,'.ERROR:')&&count($out)<14)$out[]=substr($ln,0,260);return $out;};
echo 'LARAVEL_ERRORS_AFTER_THE_INSTALL_RUN='.json_encode($logErrors(),JSON_UNESCAPED_SLASHES).PHP_EOL;
check($rc===0&&lastRaw($o,'B1158_FINAL')===B1158InstallCoordinator::FINAL_PASS&&$tree()==='INSTALLED','INSTALL_PASS:'.json_encode(array_slice($logErrors(),0,4)));
$expectedPoints=[];foreach($plan as $i=>$s){$expectedPoints[]='AFTER_STEP_'.$s['n'];if(!isset($plan[$i+1])||$plan[$i+1]['group']!==$s['group'])$expectedPoints[]='GROUP_DONE_'.$s['group'];}if(!$zero){$expectedPoints[]='AFTER_DB_BACKUP';$expectedPoints[]='AFTER_MIGRATED';}$expectedPoints[]='AFTER_INVALIDATE';
$points=array_column($rows,'point');
check($points===$expectedPoints,'EVERY_ONE_OF_THE_69_STEPS_EVERY_GROUP_THE_BACKUP_THE_MIGRATION_AND_THE_ACCEPTANCE_SMOKED:'.implode(',',$points));
$judge($rows,'INSTALL_EVERY_POINT_SERVES_THE_SITE_AND_BOTH_USERS_PAGES');
$win=[];foreach($rows as $r)if(isset($r['adminWindow']))$win[$r['point']]=$r['adminWindow'];
check($zero||(isset($win['AFTER_DB_BACKUP'],$win['AFTER_MIGRATED'])&&$win['AFTER_DB_BACKUP']['admin']['/notifications/recent']['status']===200&&$win['AFTER_MIGRATED']['admin']['/admin/orders']['status']===200),'THE_NEW_CODE_ON_THE_OLD_SCHEMA_SERVES_EVERY_PAGE_BEFORE_THE_MIGRATION_AND_AFTER_IT');



$bellBad=[];foreach($rows as $r)if($r['bellGuest']!==302)$bellBad[]=$r['point'].' '.$r['bellGuest'];
check($bellBad===[],'THE_GUEST_BELL_URL_IS_302_AT_EVERY_POINT_THE_ROUTE_IS_LIVE_SINCE_B1156:'.implode(',',$bellBad));
// Group order and "each group together": the 69 OVERLAY_STEP lines in plan order, three full guard holds (before D, E and F), the migration after every file and after the liveness smoke of group F.
check(preg_match_all('/^OVERLAY_STEP=(\{.*\})$/m',$o,$ms)===count($plan),'ONE_OVERLAY_STEP_PER_FILE');$order=array_map(static fn($x)=>json_decode($x,true),$ms[1]);
check(implode(',',array_map(static fn($x)=>$x['n'].$x['group'],$order))===implode(',',array_map(static fn($s)=>$s['n'].$s['group'],$plan))&&$order[0]['path']===$plan[0]['path'],'GROUP_ORDER_IS_THE_GENERATED_ONE');
$phases=array_map(static fn($x)=>json_decode($x,true),(preg_match_all('/^B1158_PHASE=(.*)$/m',$o,$pm)?$pm[1]:[]));
$wantPhases=$zero?['PREFLIGHT','BACKED_UP','INSTALL_INTENT','FILES_INSTALLED','ACCEPTANCE_INTENT','INSTALLED_PASS']:['PREFLIGHT','BACKED_UP','INSTALL_INTENT','FILES_INSTALLED','DB_BACKUP_INTENT','DB_BACKUP_VERIFIED','MIGRATE_INTENT','MIGRATED','ACCEPTANCE_INTENT','INSTALLED_PASS'];
check(array_values(array_intersect($phases,$wantPhases))===$wantPhases,'PHASES_FILES_THEN_BACKUP_THEN_MIGRATE_INTENT_THEN_MIGRATED_THEN_ACCEPTANCE:'.json_encode($phases));
$pos=static fn(string $needle):int=>(int)strpos($o,$needle);
if(!$zero){check($pos('OVERLAY_STEP={"n":'.count($plan))<$pos('DB_BACKUP=')&&$pos('DB_BACKUP=')<$pos('BACKUP_RECHECKED_BEFORE_MIGRATE=')&&$pos('BACKUP_RECHECKED_BEFORE_MIGRATE=')<$pos('MIGRATE_SET_RECHECK=')&&$pos('MIGRATE_SET_RECHECK=')<$pos("\nMIGRATE={")&&$pos("\nMIGRATE={")<$pos('MIGRATION_EVIDENCE=')&&$pos('MIGRATION_EVIDENCE=')<$pos('VIEW_COMPILE_CHECK='),'THE_MIGRATION_RUNS_AFTER_EVERY_FILE_BEHIND_THE_BACKUP_ITS_RECHECK_AND_THE_SET_RECHECK');
$db1=lastLine($o,'DB_BACKUP');$rk=lastLine($o,'BACKUP_RECHECKED_BEFORE_MIGRATE');$mig=lastLine($o,'MIGRATE');$bf=lastLine($o,'MIGRATION_EVIDENCE');$sc=allLines($o,'SCHEMA_CHECK');
check($db1['status']==='VERIFIED'&&$rk['reverified']===true&&$rk['sameProcess']===true&&$mig['rc']===0&&$bf['ok']===true&&$bf['notificationRowsChangedInfo']===[]&&$bf['notificationTablesWatched']>0&&$sc[0]['expectInstalled']===false&&end($sc)['expectInstalled']===true,'THE_BACKUP_THE_RECHECK_THE_ONE_MIGRATE_CALL_AND_THE_BACKFILL_EVIDENCE:'.json_encode([$db1,$rk,$mig,$bf]));
check(preg_match_all('/^MIGRATE=/m',$o)===1&&!str_contains($o,'migrate:fresh')&&!str_contains($o,'migrate:rollback')&&!str_contains($o,'migrate:refresh')&&!str_contains($o,'migrate:reset'),'EXACTLY_ONE_MIGRATE_CALL_NO_FRESH_ROLLBACK_REFRESH_RESET');}
// The database, independently of the tool: 73 ran, both new tables, three columns, the moved set is exactly the hardcoded one, nothing else changed, no notification row.
check($schemaNew(),'AFTER_THE_INSTALL_THE_SCHEMA_IS_AHEAD');
$moved=[];
check($notifCounts()===$notifBefore,'NO_NOTIFICATION_ROW_WRITTEN_BY_THE_MIGRATIONS:'.json_encode([$notifBefore,$notifCounts()]));



// Retained orphans, tree and routes.
check($orphanOk()&&($zero||is_file($active.'/'.$stepMig1['path'])),'THE_ORPHANS_ARE_RETAINED_AND_THE_MIGRATION_FILE_IS_THERE');
$tr=lastLine($o,'ACTIVE_TREE_AFTER_INSTALL');check(is_array($tr)&&$tr['verifiedFiles']===B1158Payload::TREE_FILES_AFTER&&count(glob($active.'/database/migrations/*.php'))===B1158InstallCoordinator::RAN+$nMig,'THE_TREE_IS_THE_GENERATED_SIZE_WITH_THE_NEW_MIGRATION_FILES');
$rcNew=array_values(array_filter(allLines($o,'ROUTES_CHECK'),static fn($x)=>$x['installed']===true))[0]??null;
check(is_array($rcNew)&&$rcNew['routeCount']===$pin['routes']+(B1158Payload::ROUTES_AFTER-B1158Payload::ROUTES),'ROUTE_COUNT_IS_THE_B1156_COUNT_PLUS_THE_GENERATED_DELTA_(IF_THIS_FAILS_THE_GENERATOR_ESTIMATE_IS_WRONG_PASS_--routes-after_WITH_THE_MEASURED_COUNT):'.json_encode($rcNew));
$rcOk=true;foreach(B1158InstallCoordinator::NEW_WEB_ROUTES as $nm=>$mt)if(($rcNew['newWebRoutes'][$nm]['methods']??null)!==[$mt])$rcOk=false;check($rcOk,'THE_NEW_WEB_ROUTES_HAVE_THEIR_METHODS');
$smokesInstalled=array_values(array_filter(allLines($o,'PUBLIC_HTTP_SMOKES'),static fn($x)=>$x['state']==='INSTALLED'));$s=end($smokesInstalled);$by=[];foreach($s['rows'] as $r)$by[$r['check']]=$r;
$b1149Intact($s,$by,'INSTALLED');$pinRows($by,'INSTALLED');$emailState($by,true,'INSTALLED');$viewRows($by,'INSTALLED');$assetRows($by,true,'INSTALLED');$redirectRows($by,true,'INSTALLED');
check(!str_contains($o,'"MAIL_SENT"')&&lastLine($o,'MAIL_TRANSPORT_FINGERPRINT')['status']==='MAIL_TRANSPORT_UNCHANGED'&&lastLine($o,'SHOP_STATE_CHECK')['shop']['open']===true,'MAIL_UNCHANGED_SHOP_STATE_UNCHANGED');
$results['install']=['steps'=>count($plan),'migration'=>'AFTER_ALL_FILES_BEHIND_A_VERIFIED_BACKUP','tree'=>B1158Payload::TREE_FILES_AFTER,'routes'=>$rcNew['routeCount']];
$installedOutput=$o;

// 13. Idempotent re-run: nothing is applied again, the preflight of the installed state passes (73 ran, 0 pending).
[$rc,$o]=$run();check($rc===0&&lastRaw($o,'B1158_FINAL')==='PASS_ALREADY_INSTALLED'&&!str_contains($o,'OVERLAY_STEP=')&&!preg_match('/^MIGRATE=\{/m',$o)&&!str_contains($o,'DB_BACKUP=')&&$tree()==='INSTALLED'&&$schemaNew(),'IDEMPOTENT_RERUN_NO_FILE_NO_BACKUP_NO_MIGRATION');

if(!$zero){
// 14. OWNER ROLLBACK after the migration: the files go back, the two migration files STAY (schema ahead), nothing is undone in the database; the OLD code then serves the new schema and the backfilled rows.
[$rc,$o]=$run($allRestores(),'rollback',[],$extraCfg);$rrows=array_values(array_filter($stepRows(),static fn($r)=>str_starts_with($r['point'],'AFTER_RESTORE_')));
check($rc===0&&lastRaw($o,'B1158_FINAL')==='PASS_ROLLED_BACK_FILES_SCHEMA_AHEAD'&&lastLine($o,'ROLLED_BACK_SCHEMA_AHEAD')['database']==='AHEAD_OF_THE_CODE_NO_DOWN_NO_DROP_NOTHING_UNDONE','OWNER_ROLLBACK_AFTER_THE_MIGRATION_IS_SCHEMA_AHEAD');
$prefixKept=true;foreach($plan as $s){$sh=$tampered($s['path']);$isMig=in_array($s['path'],B1158InstallCoordinator::migrationFiles(),true);$want=$isMig?$s['payloadSHA256']:($s['kind']==='new'?'ABSENT':$s['preSHA256']);if($sh!==$want)$prefixKept=false;}
check($prefixKept&&$orphanOk()&&$schemaNew()&&count(glob($active.'/database/migrations/*.php'))===B1158InstallCoordinator::RAN+$nMig,'FILES_BACK_EXCEPT_THE_MIGRATION_FILES_ORPHANS_RETAINED_DATABASE_STILL_AHEAD');
$tr=lastLine($o,'ACTIVE_TREE_AFTER_ROLLBACK');check(is_array($tr)&&$tr['verifiedFiles']===B1158InstallCoordinator::B1157_TREE_FILES+$nMig&&$tr['migrationFilesKept']===true,'THE_TREE_AFTER_THE_ROLLBACK_IS_THE_B1156_TREE_PLUS_THE_MIGRATION_FILES');
check(lastLine($o,'ROUTES_CHECK')['routeCount']===$pin['routes']&&$status('/notifications/recent')===302,'ROUTES_ARE_BACK_TO_THE_B1156_COUNT');
$judge($rrows,'ROLLBACK_EVERY_POINT_SERVES_THE_SITE_AND_BOTH_USERS_PAGES_WITH_THE_NEW_SCHEMA_UNDER_THE_OLD_CODE');
// The OLD code on the NEW schema with the backfilled rows (advisor point: not asserted, proven): the dashboards, the commission lists and the order pages of both users.
$oldPages=[];$jars=['admin'=>[],'sub'=>[]];$logins=['admin'=>B1158FixtureWeb::adminLogin(PORT,$winLogin,$winPassword,$jars['admin']),'sub'=>B1158FixtureWeb::adminLogin(PORT,$subLogin,$subPassword,$jars['sub'])];
// ONE login per user (a login per page runs into the login throttle: the 9th login answered no redirect and the page was never requested).
foreach([['admin','/'],['admin','/admin/commissions'],['admin','/admin/orders'],['admin','/admin/orders/'.$orderPaid],['admin','/admin/after-sales'],['admin','/admin/reports'],['sub','/'],['sub','/orders'],['sub','/commissions']] as [$who,$pg]){$r=$logins[$who]['ok']?B1158FixtureWeb::jarGet(PORT,$pg,$jars[$who]):['status'=>0,'body'=>''];$oldPages[$who.' '.$pg]=$r['status'];}
check(array_filter($oldPages,static fn($s)=>$s>=500||$s===0)===[]&&$oldPages['admin /']===200&&$oldPages['admin /admin/commissions']===200&&$oldPages['sub /']===200,'THE_OLD_CODE_SERVES_THE_NEW_SCHEMA_AND_THE_BACKFILLED_ROWS:'.json_encode($oldPages));
check(!str_contains($o,'DROP TABLE')&&$schemaNew(),'NOTHING_DROPPED');
// A run after that is REFUSED (nothing runs): the owner asks for a new installer.
[$rc,$o]=$run();check($rc===2&&lastRaw($o,'B1158_FINAL')==='HOLD_SCHEMA_AHEAD'&&str_contains($o,'HOLD_SCHEMA_AHEAD_NOTHING_RUNS=')&&!str_contains($o,'OVERLAY_STEP=')&&$prefixKept,'A_RUN_AFTER_A_SCHEMA_AHEAD_ROLLBACK_IS_REFUSED');
$results['ownerRollbackAfterMigration']='FILES_BACK_MIGRATION_FILES_KEPT_SCHEMA_AHEAD_OLD_CODE_SERVES_THE_NEW_SCHEMA_RERUN_REFUSED';
$reset();check($schemaOld()&&$tree()==='PRE'&&$orphanOk(),'RESET_AFTER_THE_OWNER_ROLLBACK');

// 15. Failures BEFORE the migration started: the full reverse-order file rollback, the schema untouched, the migration files removed.
foreach([['BEFORE_DB_BACKUP','BEFORE_THE_BACKUP'],['AFTER_DB_BACKUP','AFTER_THE_BACKUP_BEFORE_MIGRATE_INTENT'],['AFTER_MIGRATE_INTENT','AFTER_MIGRATE_INTENT_BEFORE_THE_COMMAND']] as [$point,$label]){
    [$rc,$o]=$run([$point=>['fail']]);
    check($rc===2&&lastRaw($o,'B1158_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&$schemaOld()&&!is_file($active.'/'.$stepMig1['path'])&&!is_file($active.'/'.$stepMig2['path'])&&!preg_match('/^MIGRATE=\{/m',$o),'FAILURE_'.$label.'_IS_A_FULL_ROLLBACK_NOTHING_MIGRATED');
    $tr=lastLine($o,'ACTIVE_TREE_AFTER_ROLLBACK');check(is_array($tr)&&$tr['verifiedFiles']===B1158InstallCoordinator::B1157_TREE_FILES&&$tr['migrationFilesKept']===false&&$orphanOk(),'THE_TREE_AFTER_'.$label.'_IS_THE_8583_TREE');$reset();
}
$results['failureBeforeTheMigration']='FULL_ROLLBACK_THREE_POINTS_SCHEMA_UNTOUCHED';

// 16. Failures AFTER the migration ran: the files go back, the two migration files stay, final HOLD_ROLLED_BACK_SCHEMA_AHEAD; a re-run is refused; the old code serves the new schema.
foreach([['AFTER_MIGRATE_COMMAND',['fail'],'THE_COMMAND_RAN_THEN_A_FAILURE'],['AFTER_SMOKES_INSTALLED',['logError'],'AN_ERROR_LINE_IN_THE_LOG_AFTER_THE_MIGRATION']] as [$point,$act,$label]){
    [$rc,$o]=$run([$point=>$act],'install',[],$extraCfg);
    check($rc===2&&lastRaw($o,'B1158_FINAL')==='HOLD_ROLLED_BACK_SCHEMA_AHEAD'&&$schemaNew()&&$orphanOk()&&is_file($active.'/'.$stepMig1['path'])&&is_file($active.'/'.$stepMig2['path'])&&str_contains($o,'OWNER_NEXT_STEPS=')&&lastLine($o,'ROLLED_BACK_SCHEMA_AHEAD')['backupBeforeTheMigration']===lastLine($o,'DB_BACKUP')['key'],'FAILURE_'.$label.'_KEEPS_THE_MIGRATION_FILES_AND_REPORTS_THE_SCHEMA_AHEAD');
    $ok=true;foreach($plan as $s){$isMig=in_array($s['path'],B1158InstallCoordinator::migrationFiles(),true);$want=$isMig?$s['payloadSHA256']:($s['kind']==='new'?'ABSENT':$s['preSHA256']);if($tampered($s['path'])!==$want)$ok=false;}check($ok,'FILES_BACK_EXCEPT_THE_MIGRATIONS_AFTER_'.$label);
    $j=[];$l=B1158FixtureWeb::adminLogin(PORT,$winLogin,$winPassword,$j);$r=$l['ok']?B1158FixtureWeb::jarGet(PORT,'/admin/commissions',$j):['status'=>0];check($r['status']===200,'THE_OLD_COMMISSIONS_PAGE_SERVES_THE_BACKFILLED_ROWS_AFTER_'.$label.':'.$r['status']);
    [$rc2,$o2]=$run();check($rc2===2&&lastRaw($o2,'B1158_FINAL')==='HOLD_SCHEMA_AHEAD'&&!str_contains($o2,'OVERLAY_STEP='),'RERUN_REFUSED_AFTER_'.$label);$reset();
}
$results['failureAfterTheMigration']='SCHEMA_AHEAD_ROLLBACK_MIGRATION_FILES_KEPT_RERUN_REFUSED_TWO_POINTS';

// 17. SIGKILL after MIGRATE_INTENT (before the command) and after the command: the same command resumes FORWARD only (never the generic restore) and ends in PASS; the evidence still equals the hardcoded set.
foreach([['AFTER_MIGRATE_INTENT','BEFORE_THE_COMMAND'],['AFTER_MIGRATE_COMMAND','AFTER_THE_COMMAND'],['AFTER_MIGRATED','AFTER_MIGRATED_SAVED'],['AFTER_INVALIDATE','IN_THE_ACCEPTANCE_INTENT']] as [$point,$label]){
    [$rc,$o]=$run([$point=>['kill']],'install',[],$extraCfg);check($rc!==0&&$rc!==2&&$tree()==='INSTALLED','KILLED_'.$label.'_THE_FILES_STAY_INSTALLED');
    if($point==='AFTER_MIGRATE_INTENT')check($schemaOld(),'KILLED_BEFORE_THE_COMMAND_THE_SCHEMA_IS_STILL_OLD');else check($schemaNew(),'KILLED_AFTER_THE_COMMAND_THE_SCHEMA_IS_NEW');
    [$rc,$o]=$run([],'install',[],$extraCfg);
    check($rc===0&&lastRaw($o,'B1158_FINAL')===B1158InstallCoordinator::FINAL_PASS&&str_contains($o,'RESUMED_MIGRATION_FORWARD_ONLY=')&&!str_contains($o,'OVERLAY_ROLLBACK=')&&$tree()==='INSTALLED'&&$schemaNew(),'RESUMED_FORWARD_ONLY_'.$label);
check($schemaNew(),'THE_SCHEMA_IS_NEW_AFTER_THE_RESUME_'.$label);
    [$rc,$o]=$run([],'rollback',[],$extraCfg);check($rc===0&&lastRaw($o,'B1158_FINAL')==='PASS_ROLLED_BACK_FILES_SCHEMA_AHEAD','OWNER_ROLLBACK_AFTER_THE_RESUMED_INSTALL_'.$label);
    $reset();check($tree()==='PRE'&&$schemaOld(),'RESET_AFTER_THE_KILL_'.$label);
}
$results['killAroundTheMigration']='FORWARD_RESUME_PASS_BOTH_POINTS';

// 18. SIGKILL in the middle of the files and right after the backup: the generic restore, the schema untouched.
foreach([['AFTER_STEP_'.intdiv(count($plan),2),'IN_THE_MIDDLE_OF_THE_FILES'],['AFTER_DB_BACKUP','RIGHT_AFTER_THE_BACKUP'],['BEFORE_DB_BACKUP','IN_DB_BACKUP_INTENT_BEFORE_THE_BACKUP_COMMAND']] as [$point,$label]){
    [$rc,$o]=$run([$point=>['kill']],'install',[],$extraCfg);check($rc!==0&&$rc!==2,'KILLED_'.$label);
    [$rc,$o]=$run();check($rc===2&&lastRaw($o,'B1158_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&$schemaOld()&&!is_file($active.'/'.$stepMig1['path'])&&str_contains($o,'RESUMED_INTERRUPTED_INSTALL='),'RESUME_AFTER_A_KILL_'.$label.'_IS_THE_GENERIC_RESTORE_NOTHING_MIGRATED');$reset();
}
$results['killBeforeTheMigration']='GENERIC_RESTORE_TWO_POINTS';

}else{
// Zero-migration payload: the owner rollback restores every file (nothing in the database is ahead), a failure in the middle rolls back completely, a kill in the middle is restored by the same command, a failed acceptance rolls back.
[$rc,$o]=$run($allRestores(),'rollback',[],$extraCfg);$rrows=array_values(array_filter($stepRows(),static fn($r)=>str_starts_with($r['point'],'AFTER_RESTORE_')));
check($rc===0&&lastRaw($o,'B1158_FINAL')==='PASS_ROLLED_BACK_BY_OWNER'&&$tree()==='PRE'&&$schemaOld()&&$orphanOk(),'OWNER_ROLLBACK_FILES_BACK_NOTHING_IN_THE_DATABASE');$judge($rrows,'ROLLBACK_EVERY_POINT_SERVES_THE_SITE_AND_BOTH_USERS_PAGES');$reset();
[$rc,$o]=$run(['AFTER_STEP_'.intdiv(count($plan),2)=>['fail']]);check($rc===2&&lastRaw($o,'B1158_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&$schemaOld()&&$orphanOk(),'FAILURE_IN_THE_MIDDLE_IS_A_FULL_ROLLBACK');$reset();
[$rc,$o]=$run(['AFTER_SMOKES_INSTALLED'=>['logError']],'install',[],$extraCfg);check($rc===2&&lastRaw($o,'B1158_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&$schemaOld()&&$orphanOk(),'AN_ERROR_LINE_IN_THE_LOG_AFTER_THE_FILES_IS_A_FULL_ROLLBACK');$reset();
[$rc,$o]=$run(['AFTER_STEP_'.intdiv(count($plan),2)=>['kill']],'install',[],$extraCfg);check($rc!==0&&$rc!==2,'KILLED_IN_THE_MIDDLE_OF_THE_FILES');[$rc,$o]=$run();check($rc===2&&lastRaw($o,'B1158_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&str_contains($o,'RESUMED_INTERRUPTED_INSTALL='),'RESUME_AFTER_A_KILL_IS_THE_GENERIC_RESTORE');$reset();
$results['zeroMigrationCases']='OWNER_ROLLBACK_FAILURE_LOG_ERROR_KILL';
}
// 19. 429 that stays during the acceptance: the files and the migration stay, the run HOLDs, the same command repeats only the acceptance.
[$rc,$o]=$run(['BEFORE_SMOKES_INSTALLED'=>['rateLimitAlways'],'BEFORE_SMOKES_OLD'=>['clearDrops']],'install',[],$extraCfg);
check($rc===2&&lastRaw($o,'B1158_FINAL')==='HOLD_INSTALLED_ACCEPTANCE_NOT_VERIFIED'&&$tree()==='INSTALLED'&&$schemaNew(),'PERSISTENT_429_HOLDS_THE_FILES_AND_THE_MIGRATION_STAY');
@unlink($switches['rate429']);[$rc,$o]=$run([],'install',[],$extraCfg);
check($rc===0&&lastRaw($o,'B1158_FINAL')===B1158InstallCoordinator::FINAL_PASS&&str_contains($o,'RESUMED_ACCEPTANCE_ONLY=')&&!str_contains($o,'OVERLAY_STEP=')&&!preg_match('/^MIGRATE=\{/m',$o),'THE_SAME_COMMAND_REPEATS_ONLY_THE_ACCEPTANCE');
[$rc,$o]=$run([],'rollback',[],$extraCfg);check($rc===0&&lastRaw($o,'B1158_FINAL')===($zero?'PASS_ROLLED_BACK_BY_OWNER':'PASS_ROLLED_BACK_FILES_SCHEMA_AHEAD'),'OWNER_ROLLBACK_AFTER_THE_429_RESUME');$reset();
$results['persistent429']='ACCEPTANCE_REPEATED_ONLY';

// 20. Cases ported from the B1155 fixture for code B1158 rewrote or added.
if($stepProvider!==null){
// 20a. A provider whose register() throws is refused by the boot proof BEFORE the provider (group D) is written: the files of A to C are rolled back, the schema is untouched.
$badProvider="<?php\nnamespace App\\Providers;\nuse Illuminate\\Support\\ServiceProvider;\nfinal class AppServiceProvider extends ServiceProvider{public function register(): void{throw new RuntimeException('B1158_FIXTURE_REGISTER_THROWS');}}\n";
[$rc,$o]=$run([],'install',['bootProofBytes'=>$badProvider],$extraCfg);
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')===json_encode('APP_CHECK_BOOT_OR_CLASS_UNAVAILABLE')&&!str_contains($o,'OVERLAY_STEP={"n":'.$stepProvider['n'])&&lastRaw($o,'B1158_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&$schemaOld(),'A_PROVIDER_THAT_THROWS_IN_REGISTER_IS_REFUSED_BEFORE_GROUP_D_AND_ROLLED_BACK');$reset();
// 20b. The site dies right after the provider (every request answers 500): the fast check rolls the install back at once.
[$rc,$o]=$run(['AFTER_STEP_'.$stepProvider['n']=>['upFails'],'SAVED_ROLLBACK_INTENT'=>['clearDrops']],'install',[],$extraCfg);
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')===json_encode('FAST_CHECK_FAILED_AFTER_BOOTSTRAP')&&lastRaw($o,'B1158_FINAL')==='HOLD_ROLLED_BACK_OLD_STATE_VERIFIED'&&$tree()==='PRE'&&$schemaOld(),'THE_FAST_CHECK_AFTER_THE_PROVIDER_ROLLS_BACK');$reset();
}
// 20c. A deliberately broken Blade view (not part of the payload) fails the acceptance AFTER the migration: files back, migration files kept, schema ahead.
[$rc,$o]=$run(['AFTER_PREFLIGHT'=>['mkBroken'],'SAVED_ROLLBACK_INTENT'=>['rmBroken']],'install',['viewChecks'=>array_merge(B1158InstallCoordinator::VIEW_CHECKS,['resources/views/zz-b1158-broken.blade.php'])],$extraCfg);
check($rc===2&&lastRaw($o,'INSTALL_OR_ACCEPTANCE_FAILED')===json_encode('BLADE_VIEW_COMPILE_OR_LINT_FAILED')&&lastRaw($o,'B1158_FINAL')===($zero?'HOLD_ROLLED_BACK_OLD_STATE_VERIFIED':'HOLD_ROLLED_BACK_SCHEMA_AHEAD')&&$schemaNew()&&!is_file($active.'/resources/views/zz-b1158-broken.blade.php'),'A_BROKEN_VIEW_AFTER_THE_MIGRATION_IS_A_SCHEMA_AHEAD_ROLLBACK');$reset();
// 20d. A schema-ahead rollback that stops half way: the recovery note's manual block is executed as pasted and never removes a migration file.
[$rc,$o]=$run([],'install',[],$extraCfg);check($rc===0&&$tree()==='INSTALLED'&&$schemaNew(),'INSTALL_FOR_THE_MANUAL_BLOCK_CASE');
[$rc,$o]=$run(['BEFORE_RESTORE_'.intdiv(count($plan),2)=>['fail']],'rollback',[],$extraCfg);
check($rc===2&&str_contains($o,'ROLLBACK_INCOMPLETE')&&str_contains($o,'RECOVERY_NOTE='),'THE_INTERRUPTED_SCHEMA_AHEAD_ROLLBACK_PRINTS_A_RECOVERY_NOTE');
$lines=$block($o);$rm=array_values(array_filter($lines,static fn($l)=>str_starts_with($l,'rm -f -- ')));
check(array_filter($rm,static fn($l)=>str_contains($l,'database/migrations'))===[],'THE_MANUAL_BLOCK_NEVER_REMOVES_A_MIGRATION_FILE:'.json_encode(array_slice($rm,0,3)));
$run1=$runBlock($lines);check($run1['rc']===0,'THE_MANUAL_BLOCK_RUNS_AS_PASTED:'.substr($run1['out'],-300));
$ok=true;foreach($plan as $s){$isMig=in_array($s['path'],B1158InstallCoordinator::migrationFiles(),true);$want=$isMig?$s['payloadSHA256']:($s['kind']==='new'?'ABSENT':$s['preSHA256']);if($tampered($s['path'])!==$want)$ok=false;}
check($ok&&$schemaNew()&&$orphanOk(),'AFTER_THE_MANUAL_BLOCK_THE_FILES_ARE_BACK_THE_MIGRATION_FILES_STAY_THE_SCHEMA_IS_AHEAD');$reset();
$results['portedCases']='PROVIDER_REGISTER_THROWS_FAST_CHECK_BROKEN_VIEW_SCHEMA_AHEAD_MANUAL_BLOCK';

// Final: after every case the live tree is the B1155 tree again and the schema is the d2c07f62 one.
check($tree()==='PRE'&&$schemaOld()&&$orphanOk(),'THE_FIXTURE_ENDS_AT_THE_B1156_TREE_AND_THE_OLD_SCHEMA');
@proc_terminate($server);
echo json_encode(['b1158Fixture'=>'PASS','results'=>$results],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;

