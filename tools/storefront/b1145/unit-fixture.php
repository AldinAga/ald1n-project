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
// L4: a resume accepts only the exact known stop of ITS first pending migration, never any non-zero exit.
$K=MigrateUnclampCoordinator::PRETEND_KNOWN_STOPS;
check(MigrateUnclampCoordinator::pretendVerdict(1,$listing([$P[1]]),'In migration: '.$K[$P[1]],[$P[1],$P[2]],false)['ok'],'PRETEND_RESUME_69_KNOWN');
check(MigrateUnclampCoordinator::pretendVerdict(1,$listing([$P[2]]),$K[$P[2]],[$P[2]],false)['ok'],'PRETEND_RESUME_70_KNOWN');
check(MigrateUnclampCoordinator::pretendVerdict(1,$listing([$P[1]]),'',[$P[1],$P[2]],false)['code']==='PRETEND_FAILED_UNEXPECTEDLY','PRETEND_RESUME_ANY_FAILURE_REFUSED');
check(MigrateUnclampCoordinator::pretendVerdict(1,$listing([$P[1]]),'SQLSTATE[HY000]: General error: 1205 Lock wait timeout',[$P[1],$P[2]],false)['ok']===false,'PRETEND_RESUME_LOCK_TIMEOUT_REFUSED');
check(MigrateUnclampCoordinator::pretendVerdict(1,$listing([$P[1]]),$K[$P[0]],[$P[1],$P[2]],false)['ok']===false,'PRETEND_RESUME_OTHER_MIGRATIONS_CODE_REFUSED');
check(MigrateUnclampCoordinator::pretendVerdict(1,$listing([$P[1]]),$K[$P[1]].' '.$K[$P[2]],[$P[1],$P[2]],false)['ok']===false,'PRETEND_RESUME_TWO_CODES_REFUSED');
check(MigrateUnclampCoordinator::pretendVerdict(1,$listing([$P[1],$P[2]]),$K[$P[1]],[$P[1],$P[2]],false)['ok']===false,'PRETEND_RESUME_STOP_NOT_AT_FIRST_REFUSED');
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

// L2: a target with a second hard link or a POSIX ACL is refused before any write; the group is kept across the rename.
$t2=$dir.'/retained/hard.env';file_put_contents($t2,"A=b\n");chmod($t2,0640);link($t2,$dir.'/retained/hard-twin');
check(refused(fn()=>AtomicFile::replace($t2,"A=c\n",0640,AtomicFile::tempFor($t2)),'ATOMIC_TARGET_HARDLINKED')&&file_get_contents($t2)==="A=b\n",'HARDLINK_REFUSED');
symlink($t2,$dir.'/active/hard-link.env');check(refused(fn()=>EnvFlags::inspect($dir.'/active/hard-link.env',$t2,$dir.'/retained'),'ENV_TARGET_HARDLINKED'),'ENV_HARDLINK_REFUSED');unlink($dir.'/retained/hard-twin');
check(AtomicFile::replaceable($t2)['nlink']===1&&AtomicFile::aclFree($t2),'SINGLE_LINK_ACL_FREE');
$setfacl=trim((string)shell_exec('command -v setfacl'));check($setfacl!=='','SETFACL_AVAILABLE');
exec('setfacl -m u:root:r '.escapeshellarg($t2),$o,$rc);check($rc===0&&!AtomicFile::aclFree($t2),'ACL_DETECTED');
check(refused(fn()=>AtomicFile::replace($t2,"A=c\n",0640,AtomicFile::tempFor($t2)),'ATOMIC_TARGET_HAS_ACL')&&file_get_contents($t2)==="A=b\n",'ACL_REFUSED');
check(refused(fn()=>EnvFlags::inspect($dir.'/active/hard-link.env',$t2,$dir.'/retained'),'ENV_TARGET_HAS_ACL'),'ENV_ACL_REFUSED');exec('setfacl -b '.escapeshellarg($t2));check(AtomicFile::aclFree($t2),'ACL_REMOVED');
$groups=array_values(array_diff(posix_getgroups(),[posix_getegid()]));$gidCase='NO_SECONDARY_GROUP_PRIMARY_GID_ONLY';
if($groups!==[]){check(chgrp($t2,$groups[0]),'CHGRP_SECONDARY');clearstatcache();$r=AtomicFile::replace($t2,"A=d\n",0640,AtomicFile::tempFor($t2));clearstatcache();check($r['gidPreserved']===true&&stat($t2)['gid']===$groups[0]&&$r['gid']===$groups[0],'GID_PRESERVED_ACROSS_RENAME');$gidCase='SECONDARY_GROUP_PRESERVED';}
else{$r=AtomicFile::replace($t2,"A=d\n",0640,AtomicFile::tempFor($t2));check($r['gidPreserved']===true,'GID_PRESERVED');}
$receipt['hardlinkAclGid']=['hardlink'=>'REFUSED','acl'=>'REFUSED','gid'=>$gidCase];

// M3: the scheduled-backup overlap in the APP timezone, independent of the CLI default timezone.
date_default_timezone_set('UTC');$ev=[['type'=>'daily','expression'=>'30 2 * * *','timezone'=>'Europe/Belgrade'],['type'=>'weekly','expression'=>'10 3 * * 0','timezone'=>'Europe/Belgrade']];
$at=static fn(string $local):int=>(new DateTimeImmutable($local,new DateTimeZone('Europe/Belgrade')))->getTimestamp();$C=static fn(int $now,int $need=1140,int $dur=720)=>MigrateUnclampCoordinator::scheduleConflict($ev,$now,$need,$dur);
check(($C($at('2026-11-10 02:20'))['type']??null)==='daily','DAILY_AHEAD_IN_APP_TZ');check(date('H:i',$at('2026-11-10 02:20'))==='01:20','CLI_UTC_SHOWS_0120');
check($C($at('2026-11-10 02:05'),900)===null&&$C($at('2026-11-10 14:00'))===null,'OUTSIDE_PASSES');check(($C($at('2026-11-10 02:35'))['type']??null)==='daily','DAILY_RUNNING');
check(($C($at('2026-11-15 03:15'))['type']??null)==='weekly'&&$C($at('2026-11-16 03:15'))===null,'WEEKLY_SUNDAY_ONLY');check(($C($at('2026-11-15 02:55'))['type']??null)==='weekly','WEEKLY_AHEAD_SUNDAY');
check(($C($at('2026-11-09 23:50'),3*3600)['type']??null)==='daily','LONG_RUN_REACHES_NEXT_DAY');check($C($at('2026-03-29 03:20'))!==null&&$C($at('2026-03-30 14:00'))===null,'DST_SPRING_DAY');
check(refused(fn()=>MigrateUnclampCoordinator::scheduleConflict([['type'=>'daily','expression'=>'*/5 * * * *','timezone'=>'Europe/Belgrade']],time(),60,60),'SCHEDULE_EXPRESSION_UNSUPPORTED')&&refused(fn()=>MigrateUnclampCoordinator::scheduleConflict([],time(),60,60),'SCHEDULE_BACKUP_EVENTS_UNREADABLE'),'SCHEDULE_FAIL_CLOSED');
$receipt['scheduleAppTimezone']='PASS';

// M4: quota parsing and the uncompressed-dump headroom.
$q=MigrateUnclampCoordinator::parseQuota("Disk quotas for user icaffeco (uid 1001): \n     Filesystem  blocks   quota   limit   grace   files   quota   limit   grace\n      /dev/sda2  1000*  2000  3000  6days  10  0  500  0\n");
check($q===[['usedBytes'=>1024000,'limitBytes'=>2048000,'usedInodes'=>10,'limitInodes'=>500]],'QUOTA_PARSED');check(MigrateUnclampCoordinator::parseQuota("Disk quotas for user icaffeco (uid 1001): none\n")===[],'QUOTA_NONE');check(MigrateUnclampCoordinator::parseQuota("quota: Cannot open quotafile\n")===null,'QUOTA_UNPARSED');
$n=MigrateUnclampCoordinator::backupNeed(1073741824,100*1048576,2000*1048576,50);check($n['bytes']===100*1048576+3*2000*1048576+536870912&&$n['inodes']===2*150+1000,'NEED_COVERS_UNCOMPRESSED_DUMP');check(MigrateUnclampCoordinator::backupNeed(1073741824,null,null,null)['bytes']===1073741824,'NEED_MINIMUM');
$receipt['quotaAndNeed']='PASS';

// H2: constraint signatures as the two migrations define them.
$full=['exists'=>true,'rows'=>0,'columns'=>MigrateUnclampCoordinator::EXPECTED_SCHEMA['public_order_accesses']['columns'],'indexes'=>['PRIMARY'=>['unique'=>true,'columns'=>['id']],'a'=>['unique'=>true,'columns'=>['order_id']],'b'=>['unique'=>true,'columns'=>['token_hash']],'c'=>['unique'=>false,'columns'=>['expires_at','revoked_at']],'public_order_accesses_order_id_foreign'=>['unique'=>false,'columns'=>['order_id']]],'foreignKeys'=>[['name'=>'f','column'=>'order_id','referencedTable'=>'orders','referencedColumn'=>'id','deleteRule'=>'RESTRICT']]];
$g=MigrateUnclampCoordinator::schemaConstraintGaps(['public_order_accesses'=>$full]);check($g['public_order_accesses']['missing']===[]&&$g['public_order_accesses']['columnsComplete']===true&&$g['public_checkout_attempts']['exists']===false,'SCHEMA_COMPLETE_WITH_IMPLICIT_FK_INDEX');
$bare=$full;$bare['indexes']=['PRIMARY'=>['unique'=>true,'columns'=>['id']]];$bare['foreignKeys']=[];$g=MigrateUnclampCoordinator::schemaConstraintGaps(['public_order_accesses'=>$bare])['public_order_accesses'];
check($g['columnsComplete']&&$g['missing']===['UNIQUE(order_id)','UNIQUE(token_hash)','INDEX(expires_at,revoked_at)','FOREIGN(order_id->orders.id_ON_DELETE_RESTRICT)'],'SCHEMA_BARE_TABLE_GAPS');
$cascade=$full;$cascade['foreignKeys'][0]['deleteRule']='CASCADE';check(in_array('FOREIGN(order_id->orders.id_ON_DELETE_RESTRICT)',MigrateUnclampCoordinator::schemaConstraintGaps(['public_order_accesses'=>$cascade])['public_order_accesses']['missing'],true),'SCHEMA_WRONG_DELETE_RULE');
$uniqueIndex=$full;$uniqueIndex['indexes']['c']['unique']=true;check(in_array('INDEX(expires_at,revoked_at)',MigrateUnclampCoordinator::schemaConstraintGaps(['public_order_accesses'=>$uniqueIndex])['public_order_accesses']['missing'],true),'SCHEMA_UNIQUE_IS_NOT_THE_PLAIN_INDEX');
$extra=$full;$extra['indexes']['x']=['unique'=>true,'columns'=>['email_ciphertext']];check(in_array('UNEXPECTED_UNIQUE(email_ciphertext)',MigrateUnclampCoordinator::schemaConstraintGaps(['public_order_accesses'=>$extra])['public_order_accesses']['missing'],true),'SCHEMA_EXTRA_UNIQUE_REFUSED');
$partial=$bare;$partial['columns']=['id'];check(MigrateUnclampCoordinator::schemaConstraintGaps(['public_order_accesses'=>$partial])['public_order_accesses']['columnsComplete']===false,'SCHEMA_PARTIAL_COLUMNS');
$receipt['schemaConstraints']='PASS';

// M2: the process tree below a child is found (deepest first) so a stopped child leaves no grandchild behind.
$tree=proc_open(['sh','-c','sleep 37 & sleep 38 & wait'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$tp);usleep(500000);$tpid=proc_get_status($tree)['pid'];$d=MigrateUnclampCoordinator::descendants($tpid);
check(count($d)===2,'DESCENDANTS_FOUND');foreach($d as $p)posix_kill($p,9);posix_kill($tpid,9);foreach($tp as $h)fclose($h);proc_close($tree);$receipt['descendants']='PASS';
echo json_encode(['unitFixture'=>'PASS']+$receipt,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
