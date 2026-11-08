<?php
declare(strict_types=1);
/** Real filesystem, real processes and the real probe file for every B1144 lean gate; each gate also gets failure injection. */
require $argv[2].'/InstallFileOps.php';require $argv[1].'/LeanGates.php';require $argv[1].'/FreshnessProbe.php';
function check(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('GATES_FIXTURE_'.$name);}
function rejects(callable $call,string $code,string $name):void{try{$call();}catch(RuntimeException $e){if($e->getMessage()!==$code)throw new RuntimeException('GATES_FIXTURE_'.$name.'_WRONG_CODE_'.$e->getMessage());return;}throw new RuntimeException('GATES_FIXTURE_'.$name.'_ACCEPTED');}
function quiet(array $argv):int{$p=proc_open($argv,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return proc_close($p);}
function put(string $path,string $bytes):void{if(!is_dir(dirname($path)))mkdir(dirname($path),0755,true);file_put_contents($path,$bytes);}
$base=getenv('RUNNER_TEMP').'/b1144-gates';mkdir($base,0755);$base=realpath($base);$receipt=[];

// 1. Filesystem-only scope.
$root=$base.'/scope/project';$active=$root.'/apps/cms/current';$candidate=$base.'/scope/retained/candidate';foreach([$active.'/public',$candidate.'/public'] as $d)mkdir($d,0755,true);
foreach([$root,$root.'/apps',$root.'/apps/cms',$active,$active.'/public',$candidate,$candidate.'/public'] as $d)chmod($d,0755);
$scope=LeanGates::scope($root,[$active,$candidate]);check($scope['directoriesChecked']===7&&$scope['sharingByOtherHostnamesProvable']===false&&$scope['filesystemOnly']===true,'SCOPE_POSITIVE');
chmod($active.'/public',0775);rejects(fn()=>LeanGates::scope($root,[$active,$candidate]),'CMS_SCOPE_GROUP_OR_WORLD_WRITABLE','SCOPE_GROUP_WRITABLE');chmod($active.'/public',0757);rejects(fn()=>LeanGates::scope($root,[$active,$candidate]),'CMS_SCOPE_GROUP_OR_WORLD_WRITABLE','SCOPE_WORLD_WRITABLE');chmod($active.'/public',0755);
file_put_contents($candidate.'/public/.user.ini','opcache.enable=0');rejects(fn()=>LeanGates::scope($root,[$active,$candidate]),'CMS_SCOPE_PUBLIC_USER_INI_PRESENT','SCOPE_USER_INI');unlink($candidate.'/public/.user.ini');
symlink($base.'/scope/missing',$active.'/public/.user.ini');rejects(fn()=>LeanGates::scope($root,[$active,$candidate]),'CMS_SCOPE_PUBLIC_USER_INI_PRESENT','SCOPE_USER_INI_DANGLING_LINK');unlink($active.'/public/.user.ini');
rename($active,$root.'/apps/cms/real');symlink($root.'/apps/cms/real',$active);rejects(fn()=>LeanGates::scope($root,[$active,$candidate]),'CMS_SCOPE_REAL_DIRECTORY_REQUIRED','SCOPE_SYMLINK_TREE');unlink($active);rename($root.'/apps/cms/real',$active);
rename($active.'/public',$active.'/public-real');symlink($active.'/public-real',$active.'/public');rejects(fn()=>LeanGates::scope($root,[$active,$candidate]),'CMS_SCOPE_REAL_DIRECTORY_REQUIRED','SCOPE_SYMLINK_PUBLIC');unlink($active.'/public');rename($active.'/public-real',$active.'/public');
check(quiet(['sudo','-n','chown','nobody',$candidate.'/public'])===0,'SUDO_CHOWN');rejects(fn()=>LeanGates::scope($root,[$active,$candidate]),'CMS_SCOPE_OWNER','SCOPE_FOREIGN_OWNER');check(quiet(['sudo','-n','chown',(string)posix_geteuid(),$candidate.'/public'])===0,'SUDO_CHOWN_BACK');
check(LeanGates::scope($root,[$active,$candidate])===$scope,'SCOPE_RESTORED');$receipt['scope']=['positive'=>true,'groupWritable'=>'HOLD','worldWritable'=>'HOLD','userIni'=>'HOLD','danglingUserIniLink'=>'HOLD','symlinkTree'=>'HOLD','symlinkPublic'=>'HOLD','foreignOwner'=>'HOLD','sharingByOtherHostnamesProvable'=>false];

// 2. Pure mtime diagnostic and probe-file selection.
$old=$base.'/mtime/old';$new=$base.'/mtime/new';$manifest=['files'=>[]];$when=time()-3600;
$files=['app/Same.php'=>['<?php // same','<?php // same'],'app/EqualMtime.php'=>['<?php // OLD','<?php // NEW'],'routes/Later.php'=>['<?php // old route','<?php // new route'],'app/MainOnly.php'=>[null,'<?php // main only'],'config/MainOnly.php'=>[null,'<?php return [];'],'resources/x.txt'=>['a','b']];
foreach($files as $relative=>[$o,$n]){put($new.'/'.$relative,$n);touch($new.'/'.$relative,$when);if($o!==null){put($old.'/'.$relative,$o);touch($old.'/'.$relative,$relative==='routes/Later.php'?$when-500:$when);}$manifest['files'][]=['path'=>$relative,'sha256'=>hash('sha256',$n)];}
$before=[];foreach([$old,$new] as $tree)foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tree,FilesystemIterator::SKIP_DOTS)) as $f){clearstatcache(true,$f->getPathname());$before[$f->getPathname()]=[hash_file('sha256',$f->getPathname()),filemtime($f->getPathname())];}
$diagnostic=LeanGates::mtimeDiagnostic($old,$new,$manifest);
check($diagnostic['presentInActive']===4&&$diagnostic['absentInActive']===2&&$diagnostic['contentDiffers']===3&&$diagnostic['contentDiffersWithEqualMtimeWithin1s']===2,'MTIME_COUNTS');
check(array_keys($diagnostic['probeFiles'])===['app/EqualMtime.php','app/MainOnly.php','routes/Later.php']&&$diagnostic['probeFiles']['app/MainOnly.php']['old']===null&&$diagnostic['probeFiles']['app/EqualMtime.php']['old']===hash('sha256','<?php // OLD'),'MTIME_PROBE_SELECTION');
foreach($before as $path=>[$sha,$mtime]){clearstatcache(true,$path);check(hash_file('sha256',$path)===$sha&&filemtime($path)===$mtime,'MTIME_DIAGNOSTIC_IS_PURE');}
rejects(fn()=>LeanGates::mtimeDiagnostic($old,$new,['files'=>[['path'=>'app/Same.php','sha256'=>hash('sha256','<?php // same')]]]),'NO_NEW_TREE_FILE_FOR_FRESHNESS_PROBE','MTIME_NO_PROBE_FILE');
rejects(fn()=>LeanGates::mtimeDiagnostic($old,$new,['files'=>[['path'=>'../escape.php','sha256'=>str_repeat('0',64)]]]),'UNSAFE_RELATIVE_PATH','MTIME_PATH_ESCAPE');
$receipt['mtimeDiagnostic']=['counts'=>array_diff_key($diagnostic,['probeFiles'=>1]),'pure'=>true,'noProbeFile'=>'HOLD'];

// 3. Local PHP CLI SHA-256 verification.
$tree=LeanGates::verifyTree($new,$manifest,'unused','unused',count($manifest['files']));check($tree['verifiedFiles']===6&&$tree['webVerificationRequests']===0,'VERIFY_POSITIVE');
rejects(fn()=>LeanGates::verifyTree($new,$manifest,'x','x',7),'CANDIDATE_LOCAL_SHA256_COUNT','VERIFY_COUNT');
file_put_contents($new.'/app/MainOnly.php',' ',FILE_APPEND);rejects(fn()=>LeanGates::verifyTree($new,$manifest,'x','x',6),'CANDIDATE_LOCAL_SHA256_MISMATCH','VERIFY_TAMPER');file_put_contents($new.'/app/MainOnly.php','<?php // main only');
rename($new.'/app/Same.php',$base.'/mtime/Same.php');symlink($base.'/mtime/Same.php',$new.'/app/Same.php');rejects(fn()=>LeanGates::verifyTree($new,$manifest,'x','x',6),'CANDIDATE_LOCAL_SHA256_MISMATCH','VERIFY_SYMLINK');unlink($new.'/app/Same.php');rename($base.'/mtime/Same.php',$new.'/app/Same.php');
unlink($new.'/resources/x.txt');rejects(fn()=>LeanGates::verifyTree($new,$manifest,'x','x',6),'CANDIDATE_LOCAL_SHA256_MISMATCH','VERIFY_MISSING');put($new.'/resources/x.txt','b');
$clampManifest=['files'=>[['path'=>'config/public_storefront.php','sha256'=>str_repeat('a',64)],['path'=>'public/.htaccess','sha256'=>str_repeat('b',64)]]];put($base.'/clamp/config/public_storefront.php','CLAMPED');put($base.'/clamp/public/.htaccess','CUSTOM');
check(LeanGates::verifyTree($base.'/clamp',$clampManifest,hash('sha256','CLAMPED'),hash('sha256','CUSTOM'),2)['verifiedFiles']===2,'VERIFY_CLAMP_AND_CUSTOM_HTACCESS');rejects(fn()=>LeanGates::verifyTree($base.'/clamp',$clampManifest,str_repeat('a',64),hash('sha256','CUSTOM'),2),'CANDIDATE_LOCAL_SHA256_MISMATCH','VERIFY_UNCLAMPED_REJECTED');
$receipt['localVerification']=['positive'=>true,'count'=>'HOLD','tamper'=>'HOLD','symlink'=>'HOLD','missing'=>'HOLD','unclampedConfig'=>'HOLD'];

// 4. Strict migrate:status parser.
$migrations=$base.'/migrations';mkdir($migrations);$names=['2014_10_12_000000_create_users_table','2026_10_23_020000_create_public_order_accesses','2026_10_23_020100_create_public_checkout_attempts','2026_10_24_030000_add_retail_customer_role'];foreach($names as $n)file_put_contents($migrations.'/'.$n.'.php','<?php');file_put_contents($migrations.'/README.md','x');
$filesList=LeanGates::migrationFiles($migrations);check($filesList===$names,'MIGRATION_FILES');
$row=static fn(string $name,string $status):string=>'  '.$name.' '.str_repeat('.',max(1,120-strlen($name)-strlen($status))).' '.$status;
$output="\n  Migration name ".str_repeat('.',90)." Batch / Status  \n".$row($names[0],'[1] Ran')."\n".$row($names[1],'Pending')."\n".$row($names[2],'Pending')."\n".$row($names[3],'Pending')."\n\n";
$parsed=LeanGates::migrationStatus($output,$filesList);check($parsed===['ran'=>1,'pending'=>array_slice($names,1)],'MIGRATION_PARSE');
rejects(fn()=>LeanGates::migrationStatus($output."  Migration table not found.\n",$filesList),'MIGRATION_STATUS_UNPARSED','MIGRATION_UNPARSED');
rejects(fn()=>LeanGates::migrationStatus($output.$row($names[1],'[2] Ran')."\n",$filesList),'MIGRATION_STATUS_DUPLICATE','MIGRATION_DUPLICATE');
rejects(fn()=>LeanGates::migrationStatus($output,array_slice($filesList,1)),'MIGRATION_STATUS_FILE_SET','MIGRATION_FILE_SET');
rejects(fn()=>LeanGates::migrationStatus('',$filesList),'MIGRATION_STATUS_FILE_SET','MIGRATION_EMPTY');
$receipt['migrateStatusParser']=['positive'=>true,'unparsed'=>'HOLD','duplicate'=>'HOLD','fileSet'=>'HOLD','empty'=>'HOLD'];

// 5. Laravel log scan from byte offsets recorded before the switch.
$logs=$base.'/logs';mkdir($logs);file_put_contents($logs.'/laravel.log',"[2026-11-08 01:00:00] production.ERROR: before the switch {\"x\":1}\n");file_put_contents($logs.'/laravel-2026-11-07.log',"[2026-11-07 23:00:00] production.CRITICAL: old\n");
$baseline=LeanGates::logBaseline($logs);check(LeanGates::newLogErrors($logs,$baseline)['newErrorLines']===0,'LOG_PRE_SWITCH_ERRORS_IGNORED');
file_put_contents($logs.'/laravel.log',"[2026-11-08 02:00:00] production.INFO: fine\n[2026-11-08 02:00:01] production.WARNING: fine\nproduction.ERROR: not at line start [x]\n",FILE_APPEND);check(LeanGates::newLogErrors($logs,$baseline)['newErrorLines']===0,'LOG_INFO_NOT_COUNTED');
file_put_contents($logs.'/laravel.log',"[2026-11-08 02:00:02] production.ERROR: injected after switch\n#0 trace\n",FILE_APPEND);check(LeanGates::newLogErrors($logs,$baseline)['newErrorLines']===1,'LOG_ERROR_COUNTED');
file_put_contents($logs.'/laravel-2026-11-08.log',"[2026-11-08 02:00:03] local.CRITICAL: new daily file\n[2026-11-08 02:00:04] local.EMERGENCY: x\n");check(LeanGates::newLogErrors($logs,$baseline)['newErrorLines']===3,'LOG_NEW_DAILY_FILE_SCANNED');
file_put_contents($logs.'/rotate.tmp',"[2026-11-08 02:00:05] production.ALERT: rotated\n");rename($logs.'/rotate.tmp',$logs.'/laravel-2026-11-07.log');$scan=LeanGates::newLogErrors($logs,$baseline);check($scan['newErrorLines']===4&&$scan['rotatedOrReplaced']===1,'LOG_ROTATION_RESCANNED');
rename($logs,$base.'/logs-old');mkdir($logs);rejects(fn()=>LeanGates::newLogErrors($logs,$baseline),'LOG_DIRECTORY_IDENTITY','LOG_DIRECTORY_REPLACED');rmdir($logs);rename($base.'/logs-old',$logs);
symlink($logs.'/laravel.log',$logs.'/laravel-link.log');rejects(fn()=>LeanGates::newLogErrors($logs,$baseline),'LOG_FILE_LINK','LOG_LINK');unlink($logs.'/laravel-link.log');
$receipt['logScan']=['preSwitchIgnored'=>true,'infoIgnored'=>true,'errorCounted'=>true,'newDailyFile'=>true,'rotation'=>true,'directoryReplaced'=>'HOLD','link'=>'HOLD'];

// 6. Narrow drain against REAL processes with LiteSpeed-style titles.
$index=$base.'/drain/home/icaffeco/ald1n-project/apps/cms/current/public/index.php';$suffix=substr($index,-LeanGates::TITLE_SUFFIX_BYTES);check(strlen($suffix)===44,'TITLE_SUFFIX');
$synthetic=[10=>'lsphp:'.$suffix,11=>'lsphp:/home/other/public_html/index.php',12=>'lsphp',13=>'php /x/artisan schedule:run',14=>'php artisan',15=>'php /x/artisanal.php',16=>'lsphp:'.substr($suffix,1),17=>'bash /x/b1144-controlled-install.sh --worker'];
check(LeanGates::busy($index,$synthetic,99)===['cms'=>1,'artisan'=>2],'BUSY_CLASSIFICATION');check(LeanGates::busy($index,$synthetic,10)===['cms'=>0,'artisan'=>2],'BUSY_SELF_EXCLUDED');
$titleCode='file_put_contents("/proc/self/comm","lsphp");cli_set_process_title($argv[1]);usleep((int)($argv[2]*1000000));';$padding=str_repeat('p',400);$children=[];
$spawn=static function(array $args)use(&$children):void{$p=proc_open($args,[0=>['pipe','r'],1=>['file','/dev/null','w'],2=>['file','/dev/null','w']],$pipes);fclose($pipes[0]);$children[]=$p;};
$waitFor=static function(callable $condition):void{for($i=0;$i<100;$i++){if($condition())return;usleep(100000);}throw new RuntimeException('GATES_FIXTURE_PROCESS_TITLE_NOT_VISIBLE');};
$spawn(['php','-n','-r',$titleCode,'lsphp:/home/icaffeco/other-site/public_html/index.php','60',$padding]);$spawn(['php','-n','-r',$titleCode,'lsphp','60',$padding]);
$waitFor(fn()=>in_array('lsphp',array_map('rtrim',LeanGates::processes()),true));
ob_start();$quiet=LeanGates::drain($index,10,200);ob_end_clean();check($quiet['drained']===true,'DRAIN_OTHER_SITE_AND_IDLE_IGNORED');
$spawn(['php','-n','-r',$titleCode,'lsphp:'.$suffix,'2.5',$padding]);$waitFor(fn()=>LeanGates::busy($index,LeanGates::processes(),getmypid())['cms']===1);$started=microtime(true);
ob_start();$drained=LeanGates::drain($index,15,200);$drainOutput=ob_get_clean();check($drained['drained']===true&&microtime(true)-$started>=1.0&&str_contains($drainOutput,'CMS_DRAIN_WAIT'),'DRAIN_WAITS_FOR_EXITING_CMS_WORKER');
$spawn(['php','-n','-r',$titleCode,'lsphp:'.$suffix,'60',$padding]);$waitFor(fn()=>LeanGates::busy($index,LeanGates::processes(),getmypid())['cms']===1);
ob_start();try{LeanGates::drain($index,2,200);throw new LogicException('LINGERING_CMS_ACCEPTED');}catch(RuntimeException $e){check($e->getMessage()==='CMS_WORKERS_OR_ARTISAN_NOT_DRAINED','DRAIN_CMS_TIMEOUT_CODE');}$timeoutOutput=ob_get_clean();check(str_contains($timeoutOutput,'CMS_DRAIN_TIMEOUT={"cms":1,"artisan":0}'),'DRAIN_TIMEOUT_REPORTED');
proc_terminate(array_pop($children),9);$waitFor(fn()=>LeanGates::busy($index,LeanGates::processes(),getmypid())['cms']===0);
$artisan=$base.'/drain/artisan';mkdir($base.'/drain',0755);file_put_contents($artisan,'<?php usleep(60000000);');$spawn(['php','-n',$artisan,'queue:work']);$waitFor(fn()=>LeanGates::busy($index,LeanGates::processes(),getmypid())['artisan']===1);
ob_start();try{LeanGates::drain($index,2,200);throw new LogicException('ARTISAN_ACCEPTED');}catch(RuntimeException $e){check($e->getMessage()==='CMS_WORKERS_OR_ARTISAN_NOT_DRAINED','DRAIN_ARTISAN_CODE');}ob_end_clean();
foreach($children as $p){$s=proc_get_status($p);if($s['running'])proc_terminate($p,9);proc_close($p);}
rejects(fn()=>LeanGates::drain('/x/index.php',5),'DRAIN_SCOPE','DRAIN_SCOPE');
$receipt['drain']=['realProcessTitles'=>true,'titleSuffixBytes'=>44,'otherSiteIgnored'=>true,'idleLsphpIgnored'=>true,'exitingCmsWorkerAwaited'=>true,'lingeringCmsWorker'=>'HOLD','artisanProcess'=>'HOLD'];

// 7. Real probe file: request gates, targeted invalidation, validation and lifecycle.
$tree=$base.'/probe/current';$public=$tree.'/public';mkdir($public,0755,true);put($tree.'/app/Main.php','<?php final class B1144ProbeFixtureMain {}');put($tree.'/app/Other.php','<?php final class B1144ProbeFixtureOther {}');put($base.'/probe/outside/Outside.php','<?php final class B1144ProbeFixtureOutside {}');
$id=bin2hex(random_bytes(16));$probe=FreshnessProbe::plan($id,[$tree,$base.'/probe/retained'],['app/Main.php','app/Absent.php']);
check(preg_match('/^ald1n-b1144-[a-f0-9]{48}\.php$/D',$probe['name'])===1&&!str_contains($probe['source'],$probe['token'])&&str_contains($probe['source'],hash('sha256',$probe['token'])),'PROBE_TOKEN_HASH_ONLY');
check(!str_contains($probe['source'],'opcache_reset')&&str_contains($probe['source'],'opcache_invalidate($path,true)')&&str_contains($probe['source'],"!=='GET'")&&str_contains($probe['source'],"!=='cms.ald1n.com'"),'PROBE_NO_GLOBAL_RESET');
$path=$public.'/'.$probe['name'];FreshnessProbe::write($path,$probe['source']);check((fileperms($path)&0777)===0644,'PROBE_MODE');check(quiet(['php','-l',$path])===0,'PROBE_LINT');
rejects(fn()=>FreshnessProbe::write($path,$probe['source']),'FRESHNESS_PROBE_NAME_COLLISION','PROBE_NO_OVERWRITE');rejects(fn()=>FreshnessProbe::assertNoLeftover($public),'LEFTOVER_FRESHNESS_PROBE_REQUIRES_OPERATOR_INSPECTION','PROBE_LEFTOVER');
$ask=static function(array $server,bool $opcache=false,array $compile=[])use($path,$argv):string{$args=['php','-d','opcache.enable='.(int)$opcache,'-d','opcache.enable_cli='.(int)$opcache,'-d','opcache.revalidate_freq=0','-d','opcache.file_update_protection=0',$argv[1].'/probe-harness.php',$path,json_encode($server)];$p=proc_open(array_merge($args,$compile),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$out=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);proc_close($p);return $out;};
$good=['REQUEST_METHOD'=>'GET','HTTP_HOST'=>'cms.ald1n.com','HTTP_X_ALD1N_RELEASE_PROBE'=>$probe['token'],'DOCUMENT_ROOT'=>$public];
foreach(['noToken'=>array_diff_key($good,['HTTP_X_ALD1N_RELEASE_PROBE'=>1]),'wrongToken'=>['HTTP_X_ALD1N_RELEASE_PROBE'=>str_repeat('0',64)]+$good,'post'=>['REQUEST_METHOD'=>'POST']+$good,'head'=>['REQUEST_METHOD'=>'HEAD']+$good,'wrongHost'=>['HTTP_HOST'=>'shop.ald1n.com']+$good,'hostWithPort'=>['HTTP_HOST'=>'cms.ald1n.com:443']+$good] as $label=>$server)check($ask($server)==='','PROBE_DENIES_'.strtoupper($label));
$expected=['app/Main.php'=>hash_file('sha256',$tree.'/app/Main.php'),'app/Absent.php'=>null];
$plain=json_decode($ask($good),true,16,JSON_THROW_ON_ERROR);$summary=FreshnessProbe::validate($plain,$id,$expected,['cli'],false);check($summary['opcacheEnabled']===false&&$summary['globalReset']===false&&$summary['docrootMatches']===true,'PROBE_PLAIN_VALID');
$cached=json_decode($ask($good+['HTTP_X_ALD1N_RELEASE_INVALIDATE'=>'YES'],true,[$tree.'/app/Main.php',$tree.'/app/Other.php',$base.'/probe/outside/Outside.php']),true,16,JSON_THROW_ON_ERROR);
check($cached['opcacheEnabled']===true&&$cached['invalidationCandidates']===2&&$cached['invalidated']===2&&$cached['invalidationFailed']===0,'PROBE_TARGETED_INVALIDATION_ONLY_UNDER_ROOTS_'.json_encode(array_diff_key($cached,['files'=>1])));$cachedSummary=FreshnessProbe::validate($cached,$id,$expected,['cli'],true);check($cachedSummary['targetedInvalidations']===2&&$cachedSummary['waitSeconds']===2,'PROBE_CACHED_VALID');
$expiredProbe=FreshnessProbe::plan($id,[$tree],['app/Main.php'],time()-FreshnessProbe::LIFETIME-1);$expiredPath=$base.'/probe/expired.php';file_put_contents($expiredPath,$expiredProbe['source']);
$p=proc_open(['php',$argv[1].'/probe-harness.php',$expiredPath,json_encode(['HTTP_X_ALD1N_RELEASE_PROBE'=>$expiredProbe['token']]+$good)],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);fclose($pipes[0]);$expiredOut=stream_get_contents($pipes[1]);fclose($pipes[1]);fclose($pipes[2]);proc_close($p);check($expiredOut==='','PROBE_EXPIRES_AFTER_1800_SECONDS');
$mutations=['sapi'=>[['sapi'=>'litespeed'],'FRESHNESS_PROBE_SAPI'],'id'=>[['id'=>str_repeat('f',32)],'FRESHNESS_PROBE_SCOPE'],'kind'=>[['kind'=>'OTHER'],'FRESHNESS_PROBE_SCOPE'],'php'=>[['php'=>'8.3.1'],'FRESHNESS_PROBE_PHP'],'docroot'=>[['docrootMatches'=>false],'FRESHNESS_PROBE_DOCROOT'],'staleFile'=>[['files'=>['app/Main.php'=>str_repeat('0',64),'app/Absent.php'=>null]],'FRESHNESS_PROBE_TREE_CONTENT'],'statusDenied'=>[['statusAccess'=>false],'FRESHNESS_OPCACHE_STATUS_UNAVAILABLE'],'ambiguous'=>[['opcacheIniEnabled'=>false],'FRESHNESS_OPCACHE_STATE_AMBIGUOUS'],'unlisted'=>[['scriptsListed'=>false],'FRESHNESS_OPCACHE_SCRIPTS_UNLISTED'],'fileCacheOnly'=>[['fileCacheOnly'=>true],'FRESHNESS_OPCACHE_SCRIPTS_UNLISTED'],'failedInvalidation'=>[['invalidationFailed'=>1],'FRESHNESS_TARGETED_INVALIDATION_FAILED'],'partialInvalidation'=>[['invalidated'=>1],'FRESHNESS_TARGETED_INVALIDATION_FAILED'],'reset'=>[['globalReset'=>true],'FRESHNESS_TARGETED_INVALIDATION_FAILED'],'revalidate'=>[['revalidateFreq'=>61],'FRESHNESS_REVALIDATE_FREQ_UNSUPPORTED'],'negative'=>[['revalidateFreq'=>-1],'FRESHNESS_PROBE_FIELDS']];
foreach($mutations as $label=>[$change,$code])rejects(fn()=>FreshnessProbe::validate(array_replace($cached,$change),$id,$expected,['cli'],true),$code,'PROBE_VALIDATE_'.strtoupper($label));
rejects(fn()=>FreshnessProbe::validate($cached,$id,$expected,['cli'],false),'FRESHNESS_TARGETED_INVALIDATION_FAILED','PROBE_VALIDATE_UNREQUESTED_INVALIDATION');
check(FreshnessProbe::remove($public,$probe['name'],$probe['sha256'])===true&&!file_exists($path),'PROBE_REMOVED');check(FreshnessProbe::remove($public,$probe['name'],$probe['sha256'])===false,'PROBE_REMOVE_ABSENT');FreshnessProbe::assertNoLeftover($public);
file_put_contents($path,'<?php // foreign');rejects(fn()=>FreshnessProbe::remove($public,$probe['name'],$probe['sha256']),'FRESHNESS_PROBE_FOREIGN_CONTENT','PROBE_FOREIGN_NOT_DELETED');check(file_get_contents($path)==='<?php // foreign','PROBE_FOREIGN_BYTES_KEPT');unlink($path);
rejects(fn()=>FreshnessProbe::remove($public,'index.php',$probe['sha256']),'FRESHNESS_PROBE_NAME','PROBE_REMOVE_SCOPE');rejects(fn()=>FreshnessProbe::plan('bad',[$tree],['app/Main.php']),'FRESHNESS_PLAN_SCOPE','PROBE_PLAN_SCOPE');rejects(fn()=>FreshnessProbe::plan($id,[$tree.'/'],['app/Main.php']),'FRESHNESS_ROOT_SCOPE','PROBE_ROOT_SCOPE');rejects(fn()=>FreshnessProbe::plan($id,[$tree],['app/x.txt']),'FRESHNESS_FILE_SCOPE','PROBE_FILE_SCOPE');
$receipt['freshnessProbe']=['realProbeFile'=>true,'tokenHashOnlyInFile'=>true,'noGlobalReset'=>true,'mode0644'=>true,'lint'=>true,'deniedWithout404Body'=>['noToken','wrongToken','post','head','wrongHost','hostWithPort','expired'],'targetedInvalidationUnderRootsOnly'=>true,'validationNegatives'=>count($mutations)+1,'noOverwrite'=>true,'leftover'=>'HOLD','foreignContent'=>'HOLD_BYTES_KEPT','removed'=>true];
echo json_encode($receipt,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
