<?php
declare(strict_types=1);
/**
 * B1145 unit fixture: .env key handling, crash-safe replacement through a symlink, the --pretend verdict, the exact
 * Report1106 facts and the real probe file executed by PHP with OPcache. Usage: php unit-fixture.php <b1145> <b1144> <b1134>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$tools,$b1144,$b1134]=$argv;
require $b1134.'/ReleasePrimitives.php';require $b1134.'/InstallFileOps.php';require $b1144.'/LeanGates.php';require $b1144.'/FreshnessProbe.php';require $b1144.'/LeanWeb.php';
require $tools.'/EnvFlags.php';require $tools.'/UnclampProbe.php';require $tools.'/MigrateUnclampCoordinator.php';
function check(bool $ok,string $name):void{if(!$ok)throw new RuntimeException('UNIT_FIXTURE_'.$name);}
function refused(callable $f,string $code):bool{try{$f();return false;}catch(RuntimeException $e){return $e->getMessage()===$code;}}
$receipt=[];

// .env states and the minimal edit.
$K1='PUBLIC_STOREFRONT_ENABLED';$K2='PUBLIC_CHECKOUT_ENABLED';
$base="APP_NAME=Ald1n\nDB_PASSWORD=never-printed\n";
check(EnvFlags::state($base)===[$K1=>'absent',$K2=>'absent'],'STATE_ABSENT');
$t=EnvFlags::transform($base);check($t['bytes']===$base.$K1."=true\n".$K2."=true\n"&&$t['changed']===[$K1,$K2],'APPEND_BOTH');
$mixed="APP_NAME=Ald1n\n".$K2."=false\nMAIL_HOST=x\n";$t=EnvFlags::transform($mixed);check($t['bytes']==="APP_NAME=Ald1n\n".$K2."=true\nMAIL_HOST=x\n".$K1."=true\n"&&$t['changed']===[$K1,$K2],'FALSE_IN_PLACE_PLUS_APPEND');
foreach(['0','off','no',''] as $false){$t=EnvFlags::transform($K1.'='.$false."\nA=b\n");check(str_starts_with($t['bytes'],$K1."=true\nA=b\n"),'FALSE_FORM_'.$false);}
$noNewline="A=b\n".$K1.'=false';$t=EnvFlags::transform($noNewline);check($t['bytes']==="A=b\n".$K1."=true\n".$K2.'=true','NO_TRAILING_NEWLINE_KEPT');
$true=$K1."=true\n".$K2."=true\n";check(EnvFlags::transform($true)['changed']===[],'ALREADY_TRUE_NOOP');check(EnvFlags::transform('')['bytes']===$K1."=true\n".$K2.'=true','EMPTY_FILE');
check(refused(fn()=>EnvFlags::state("A=b\r\n"),'ENV_FORMAT_CRLF_OR_NUL'),'CRLF');check(refused(fn()=>EnvFlags::state($K1."=false\n".$K1."=true\n"),'ENV_FORMAT_DUPLICATE_KEY'),'DUPLICATE');
check(refused(fn()=>EnvFlags::state('export '.$K1."=false\n"),'ENV_FORMAT_KEY_LINE_NOT_PLAIN'),'EXPORT');check(refused(fn()=>EnvFlags::state($K1."=\"false\"\n"),'ENV_FORMAT_KEY_LINE_NOT_PLAIN'),'QUOTED');
check(refused(fn()=>EnvFlags::transform($K1."=maybe\n"),'ENV_KEY_VALUE_NOT_BOOLEAN'),'OTHER_VALUE');check(refused(fn()=>EnvFlags::assertOnlyKeysDiffer("A=b\n","A=c\n".$K1."=true\n".$K2."=true\n"),'ENV_OTHER_LINES_CHANGED'),'OTHER_LINE_CHANGE_DETECTED');
$receipt['envFlags']='PASS';

// Atomic replacement THROUGH a symlink: the link stays, the real target gets the new bytes with the same mode and a newer mtime.
$dir=sys_get_temp_dir().'/b1145-unit-'.bin2hex(random_bytes(4));mkdir($dir.'/retained',0755,true);mkdir($dir.'/active',0755,true);mkdir($dir.'/private',0700);
$target=$dir.'/retained/.env';file_put_contents($target,$mixed);chmod($target,0640);touch($target,time()-50);symlink($target,$dir.'/active/.env');
$info=EnvFlags::inspect($dir.'/active/.env',$target,$dir.'/retained');$linkIno=lstat($dir.'/active/.env')['ino'];
$next=EnvFlags::transform((string)file_get_contents($info['target']));$temp=AtomicFile::tempFor($info['target']);check(AtomicFile::isOwnTemp($temp),'TEMP_NAME');
$r=AtomicFile::replace($info['target'],$next['bytes'],$info['mode'],$temp);clearstatcache();
check(is_link($dir.'/active/.env')&&readlink($dir.'/active/.env')===$target&&lstat($dir.'/active/.env')['ino']===$linkIno,'SYMLINK_PRESERVED');
check((fileperms($target)&0777)===0640&&file_get_contents($target)===$next['bytes']&&$r['mtimeAdvanced']&&$r['newInode']&&!file_exists($temp),'REAL_TARGET_REPLACED');
check(refused(fn()=>EnvFlags::inspect($dir.'/active/.env',$dir.'/retained/other',$dir.'/retained'),'ENV_SYMLINK_TARGET_CHANGED'),'LINK_TARGET_PINNED');
check(refused(fn()=>AtomicFile::replace($dir.'/active/.env',"x\n",0640,AtomicFile::tempFor($dir.'/active/.env')),'ATOMIC_REPLACE_SCOPE'),'NEVER_REPLACES_A_LINK');
$sha=AtomicFile::privateCopy($dir.'/private/copy',"secret\n");check((fileperms($dir.'/private/copy')&0777)===0600&&AtomicFile::readPrivate($dir.'/private/copy',$sha)==="secret\n",'PRIVATE_COPY_0600');
chmod($dir.'/retained',0555);check(refused(fn()=>AtomicFile::replace($target,"y\n",0640,AtomicFile::tempFor($target)),'ATOMIC_TEMP_CREATE'),'UNWRITABLE_DIRECTORY_REFUSED');check(file_get_contents($target)===$next['bytes'],'UNWRITABLE_TARGET_UNCHANGED');chmod($dir.'/retained',0755);
$receipt['atomicThroughSymlink']='PASS';

// --pretend verdict.
$P=MigrateUnclampCoordinator::PENDING;$listing=static fn(array $names)=>"\n   INFO  Running migrations.\n\n".implode('',array_map(fn($n)=>'  '.$n." .......... \n  create table x\n",$names));
check(MigrateUnclampCoordinator::pretendVerdict(0,$listing($P),'',$P,true)['ok'],'PRETEND_ALL_LISTED');
check(MigrateUnclampCoordinator::pretendVerdict(1,$listing([$P[0]]),'In migration: '.MigrateUnclampCoordinator::PRETEND_INTROSPECTION_CODE,$P,true)['ok'],'PRETEND_STOP_FIRST_KNOWN');
check(!MigrateUnclampCoordinator::pretendVerdict(1,$listing([$P[0]]),'some other failure',$P,true)['ok'],'PRETEND_STOP_FIRST_UNKNOWN_FRESH_REFUSED');
check(MigrateUnclampCoordinator::pretendVerdict(1,$listing([$P[1]]),'',[$P[1],$P[2]],false)['ok'],'PRETEND_RESUME_STOP_FIRST');
$extra=array_merge($P,['2026_11_01_000000_unexpected_fourth']);check(MigrateUnclampCoordinator::pretendVerdict(0,$listing($extra),'',$P,true)['code']==='PRETEND_LISTS_UNEXPECTED_MIGRATIONS','PRETEND_FOURTH_REFUSED');
check(MigrateUnclampCoordinator::pretendVerdict(0,$listing([$P[1],$P[0],$P[2]]),'',$P,true)['code']==='PRETEND_LISTS_UNEXPECTED_MIGRATIONS','PRETEND_ORDER_REFUSED');
check(MigrateUnclampCoordinator::pretendVerdict(0,$listing([$P[0]]),'',$P,true)['code']==='PRETEND_INCOMPLETE_LISTING','PRETEND_INCOMPLETE_RC0_REFUSED');
check(!MigrateUnclampCoordinator::pretendVerdict(1,$listing([]),'',$P,true)['ok'],'PRETEND_NOTHING_LISTED_REFUSED');
$receipt['pretendVerdict']='PASS';

// The exact Report1106 bytes and their terminal facts.
$report=file_get_contents($tools.'/report1106-fixture.md');check(hash('sha256',$report)===MigrateUnclampCoordinator::REPORT_SHA,'REPORT1106_SHA');
$facts=MigrateUnclampCoordinator::reportFacts($report);check($facts['retained']==='/home/icaffeco/.ald1n-b1134-retained/99111dcf09304b97aac05be0ef6ee1ee/retained'&&$facts['git']===['head'=>'b5b942e645d28d3ed5f248eaae4180ebe9a54ee6','branch'=>'main'],'REPORT1106_FACTS');
check(refused(fn()=>MigrateUnclampCoordinator::reportFacts(str_replace('B1144_FINAL=PASS_INSTALLED_OFF','B1144_FINAL=HOLD',$report)),'B1144_REPORT_NOT_TERMINAL_PASS'),'REPORT_HOLD_REFUSED');
check(refused(fn()=>MigrateUnclampCoordinator::reportFacts($report."RELEASE_PHASE=\"ROLLED_BACK\"\n"),'B1144_REPORT_NOT_TERMINAL_PASS'),'REPORT_LATER_PHASE_REFUSED');
$receipt['report1106']=['sha256'=>MigrateUnclampCoordinator::REPORT_SHA,'retained'=>$facts['retained'],'head'=>$facts['git']['head']];

// The real probe file under PHP with OPcache: exact-file invalidation, staleness, server-level keys.
$cfg=$dir.'/active/config';mkdir($cfg);mkdir($dir.'/active/public');$file=$cfg.'/public_storefront.php';file_put_contents($file,"<?php\nreturn ['enabled'=>false];\n");$id=bin2hex(random_bytes(16));
$probe=UnclampProbe::plan($id,$file);FreshnessProbe::write($dir.'/active/public/'.$probe['name'],$probe['source'],$dir.'/private/probe-'.bin2hex(random_bytes(8)).'.staging');
$ask=static function(bool $invalidate,array $env=[])use($probe,$dir,$file,$b1144):array{
    $server=['REQUEST_METHOD'=>'GET','HTTP_HOST'=>'cms.ald1n.com','HTTP_X_ALD1N_RELEASE_PROBE'=>$probe['token'],'DOCUMENT_ROOT'=>$dir.'/active/public']+($invalidate?['HTTP_X_ALD1N_RELEASE_INVALIDATE'=>'YES']:[]);
    $p=proc_open(['php','-d','opcache.enable=1','-d','opcache.enable_cli=1','-d','opcache.validate_timestamps=1','-d','opcache.revalidate_freq=2','-d','opcache.file_update_protection=0',$b1144.'/probe-harness.php',$dir.'/active/public/'.$probe['name'],json_encode($server),$file],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,array_merge(getenv(),$env));
    fclose($pipes[0]);$out=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);proc_close($p);return json_decode($out,true,16,JSON_THROW_ON_ERROR);
};
$r=$ask(true);$a=UnclampProbe::assess($r,$id,hash_file('sha256',$file),['cli'],true,false);check($a['failures']===[]&&$r['targetCached']===true&&$r['invalidated']===true&&$r['globalReset']===false,'PROBE_EXACT_INVALIDATION');
$r=$ask(false);check(UnclampProbe::assess($r,$id,hash_file('sha256',$file),['cli'],false,true)['failures']===[],'PROBE_FRESH');
check(in_array('UNCLAMP_PROBE_TARGET_CONTENT',UnclampProbe::assess($r,$id,str_repeat('0',64),['cli'],false,true)['failures'],true),'PROBE_CONTENT_GATE');
$r=$ask(false,[$K1=>'false']);check(in_array('PUBLIC_KEYS_DEFINED_BY_WEB_SERVER_ENVIRONMENT',UnclampProbe::assess($r,$id,hash_file('sha256',$file),['cli'],false,true)['failures'],true),'PROBE_SERVER_LEVEL_KEYS');
check(in_array('UNCLAMP_PROBE_SAPI',UnclampProbe::assess($r,$id,hash_file('sha256',$file),['litespeed'],false,true)['failures'],true),'PROBE_SAPI_GATE');
$stale=$r;$stale['targetCachedStale']=true;check(in_array('UNCLAMP_WEB_CONFIG_STALE',UnclampProbe::assess($stale,$id,hash_file('sha256',$file),['cli'],false,true)['failures'],true),'PROBE_STALE_GATE');
check(!str_contains($probe['source'],$probe['token'])&&FreshnessProbe::remove($dir.'/active/public',$probe['name'],$probe['sha256']),'PROBE_TOKEN_NOT_EMBEDDED_AND_REMOVED');
$receipt['probe']='PASS_REAL_OPCACHE_EXACT_FILE';
echo json_encode(['unitFixture'=>'PASS']+$receipt,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
