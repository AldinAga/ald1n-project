<?php
declare(strict_types=1);
/** Six-generation admission: the real B1143 fixture builds B1134..B1142 and a B1143 state; B1144 then admits the exact Report1104 terminal. */
ob_start();require dirname(__DIR__).'/b1143/recovery-fixture.php';$inherited=ob_get_clean();
require __DIR__.'/B1144Recovery.php';
function need(bool $ok,string $why):void{if(!$ok)throw new RuntimeException('B1144_RECOVERY_FIXTURE_'.$why);}
function refuses(callable $call,string $code,string $why):void{try{$call();}catch(RuntimeException $e){need($e->getMessage()===$code,$why.'_CODE_'.$e->getMessage());return;}throw new RuntimeException('B1144_RECOVERY_FIXTURE_'.$why.'_ACCEPTED');}
$docs=$root.'/docs/operations';$report1102=$docs.'/1102-B1142-CONTROLLED-OFF-INSTALL-20261108-013756.md';copy(dirname(__DIR__).'/b1143/report1102-fixture.md',$report1102);
$report1104=$docs.'/1104-B1143-CONTROLLED-OFF-INSTALL-20261108-023449.md';copy(__DIR__.'/report1104-fixture.md',$report1104);need(hash_file('sha256',$report1104)==='3b469c0d0e6927be3d8143c8c3e466e473bb2e754408b408041889adeb853330','REPORT1104_FIXTURE_BYTES');
// Report1104 (B1143 HOLD at CMS_DOCROOT_SCOPE_UNAVAILABLE) is admitted byte-exactly; any change is refused.
$reader=new ReflectionMethod(B1144Recovery::class,'readReport');[$readBytes,$readGit,$read]=$reader->invoke(null,$root);need($read['_reportedGit1104']===['head'=>'b5b942e645d28d3ed5f248eaae4180ebe9a54ee6','branch'=>'main']&&isset($read['_admissionReports']['1104']),'REPORT1104_PARSED');
$original1104=file_get_contents($report1104);foreach(['append'=>$original1104."\n",'switched'=>str_replace('RELEASE_PHASE="ROLLED_BACK_PRE_SWITCH"','RELEASE_PHASE="SWITCHED"',$original1104),'empty'=>''] as $label=>$bytes){file_put_contents($report1104,$bytes);refuses(fn()=>$reader->invoke(null,$root),'B1144_EXACT_TERMINAL_1104_REQUIRED','REPORT1104_TAMPER_'.strtoupper($label));}
file_put_contents($report1104,$original1104);unlink($report1104);refuses(fn()=>$reader->invoke(null,$root),'B1135_PREDECESSOR_FILE','REPORT1104_ABSENT');copy(__DIR__.'/report1104-fixture.md',$report1104);
// Emulate the deduced B1143 terminal state: prepared, then rolled back before any scheduler, barrier, overlay or rename.
$terminal=$new;$terminal['phase']='ROLLED_BACK_PRE_SWITCH';$terminal['originalProfileRestore']=['switched'=>false];$terminal['web']['librarySHA256']='6065a4d3739fa956ab90c00213761c3fb3d022ae97c4ce67c9249a77d7beb0d3';foreach(['scheduler','oldMaintenance','newMaintenance'] as $key)$terminal[$key]=null;$terminal['schedulerPauseAttempted']=false;unset($terminal['uncached'],$terminal['uncachedFreshness']);
$b1143State=$terminal['private'].'/state.json';$b1143Pointer=$home.'/.ald1n-b1143-state/current.json';file_put_contents($b1143State,json_encode($terminal,JSON_UNESCAPED_SLASHES));file_put_contents($b1143Pointer,json_encode(['schemaVersion'=>1,'private'=>$terminal['private'],'id'=>$terminal['id']]));
mkdir($home.'/.ald1n-b1144-state',0700);
$src=$source;$src['_admissionReports']['1104']=$original1104;$src['_reportedGit1104']=['head'=>$git['head'],'branch'=>$git['branch']];
$verify=new ReflectionMethod(B1144Recovery::class,'verify');$admit=fn(?array $s=null)=>$verify->invoke(null,$root,$home,$manifest,$git,$reportBytes,$reportedGit,$s??$src);
$watched=[$b1143Pointer,$b1143State,$terminal['journal'],$home.'/.ald1n-b1142-state/current.json',$fresh['private'].'/state.json',$fresh['journal']];foreach($before as $path=>$hash)$watched[]=$path;$watched=array_values(array_unique($watched));
$hashes=static function()use($watched):array{$r=[];foreach($watched as $p){clearstatcache(true,$p);$r[$p]=hash_file('sha256',$p);}return $r;};$baseline=$hashes();
$admission=$admit();need($admission['proof']['private']===$terminal['private']&&$admission['proof']['reportSHA256']===hash('sha256',$original1104)&&$admission['state']['plan']===$plan,'ADMITTED_EXACT_B1143');
need(count($admission['archive'])===25&&isset($admission['archive']['predecessor-1104.md'],$admission['archive']['origin-b1142-state.json'],$admission['archive']['predecessor-1102.md']),'ARCHIVE_COMPLETE');need($hashes()===$baseline,'ADMISSION_IS_PURE');
need(scandir($home.'/.ald1n-b1144-state')===['.','..'],'ADMISSION_WRITES_NOTHING');
// Negative boundaries over the B1143 terminal, its journal, its archived ancestry, Report1104 Git and the live filesystem.
$mutate=static function(array $change)use($terminal,$b1143State):void{file_put_contents($b1143State,json_encode(array_replace($terminal,$change),JSON_UNESCAPED_SLASHES));};
foreach(['pauseAttempted'=>['schedulerPauseAttempted'=>true],'scheduler'=>['scheduler'=>['paused'=>true]],'maintenance'=>['oldMaintenance'=>['files'=>[]]],'switched'=>['originalProfileRestore'=>['switched'=>true]],'phase'=>['phase'=>'ROLLED_BACK'],'library'=>['web'=>['librarySHA256'=>str_repeat('0',64)]],'uncached'=>['uncached'=>['entries'=>[]]]] as $label=>$change){$mutate($change);refuses(fn()=>$admit(),'B1144_TERMINAL_B1143_STATE_REQUIRED','B1143_STATE_'.strtoupper($label));}
file_put_contents($b1143State,json_encode($terminal,JSON_UNESCAPED_SLASHES));
$journalBytes=file_get_contents($terminal['journal']);ReleasePrimitives::journalState($terminal['journal'],$terminal['owner'],'MAINTENANCE');refuses(fn()=>$admit(),'B1144_PREDECESSOR_JOURNAL_NOT_PRISTINE','B1143_JOURNAL_ADVANCED');file_put_contents($terminal['journal'],$journalBytes);
$archived=$terminal['private'].'/origin-b1139-state.json';$archivedBytes=file_get_contents($archived);file_put_contents($archived,$archivedBytes.' ');refuses(fn()=>$admit(),'B1144_ARCHIVED_CHAIN_DRIFT','B1143_ARCHIVE_DRIFT');file_put_contents($archived,$archivedBytes);
$drift=$src;$drift['_reportedGit1104']['head']=str_repeat('1',40);refuses(fn()=>$admit($drift),'B1144_REPORTED_1104_GIT_DRIFT','REPORT1104_GIT_DRIFT');
$drift=$src;$drift['_reportedPredecessor']=['private'=>'/forged'];refuses(fn()=>$admit($drift),'B1144_REPORTED_B1142_PREDECESSOR_DRIFT','B1142_REPORTED_PREDECESSOR_DRIFT');
$b1142Bytes=file_get_contents($fresh['private'].'/state.json');file_put_contents($fresh['private'].'/state.json',$b1142Bytes.' ');refuses(fn()=>$admit(),'B1144_B1142_CHAIN_DRIFT','B1142_STATE_DRIFT');file_put_contents($fresh['private'].'/state.json',$b1142Bytes);
mkdir($plan['rollback']);refuses(fn()=>$admit(),'B1135_PRE_SWITCH_FILESYSTEM_REQUIRED','ROLLBACK_PATH_PRESENT');rmdir($plan['rollback']);
file_put_contents($active.'/storage/framework/maintenance.php','<?php');refuses(fn()=>$admit(),'B1135_MAINTENANCE_PRESENT','FOREIGN_BARRIER_PRESENT');unlink($active.'/storage/framework/maintenance.php');
need($hashes()===$baseline,'NEGATIVES_RESTORED');
// Commit: new namespace only; a predecessor byte change between admission and commit is refused before the state exists.
$admission=$admit();$liveJournal=$terminal['journal'];file_put_contents($liveJournal,$journalBytes.' ');refuses(fn()=>B1144Recovery::commit($home,$admission,$git),'B1144_PREDECESSOR_CHANGED','COMMIT_RACE');file_put_contents($liveJournal,$journalBytes);
$admission=$admit();$state=B1144Recovery::commit($home,$admission,$git);need($hashes()===$baseline,'ALL_SIX_PREDECESSORS_UNCHANGED');
need($state['predecessor']===$admission['proof']&&$state['plan']===$plan&&$state['id']!==$terminal['id']&&$state['private']===$home.'/.ald1n-b1144-state/'.$state['id']&&$state['owner']!==$terminal['owner'],'FRESH_B1144_OWNER');
need($state['schedulerPauseAttempted']===false&&$state['scheduler']===null&&$state['oldMaintenance']===null&&$state['newMaintenance']===null&&!isset($state['web'])&&!isset($state['uncached'])&&!isset($state['originalProfileRestore']),'NO_B1143_WEB_OR_PROFILE_STATE_CARRIED');
foreach($admission['archive'] as $name=>$bytes){$p=$state['private'].'/'.$name;need(file_get_contents($p)===$bytes&&(fileperms($p)&0777)===0600,'ARCHIVE_'.$name);}
need((fileperms($state['private'])&0777)===0700&&json_decode(explode("\n",file_get_contents($state['journal']))[0],true)['plan']===$plan&&!file_exists($home.'/.ald1n-b1144-state/current.json'),'NEW_PRIVATE_JOURNAL_NO_POINTER_YET');
echo json_encode(['inheritedB1143Fixture'=>array_map(fn($line)=>json_decode($line,true),array_values(array_filter(explode("\n",$inherited)))),'exactReport1104'=>true,'report1104Tamper'=>'HOLD','b1143TerminalStateNegatives'=>7,'b1143JournalAdvanced'=>'HOLD','archivedAncestryDrift'=>'HOLD','report1104GitDrift'=>'HOLD','b1142Drift'=>'HOLD','foreignBarrier'=>'HOLD','rollbackPathPresent'=>'HOLD','admissionPure'=>true,'commitRace'=>'HOLD','sixGenerationsUnchanged'=>true,'archiveFiles'=>count($admission['archive']),'freshOwner'=>true],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
