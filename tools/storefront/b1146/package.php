<?php
declare(strict_types=1);
/**
 * Builds the B1146 operator ZIP with root-relative incoming/ members only. The overlay payload is taken from git
 * (git show eabe16af:<path>), never from a working tree, and shipped as flat incoming/b1146-payload-NN.bin members, so
 * unzip never writes into the live CMS tree. The build fails unless git diff --name-status 77a3efea eabe16af under
 * apps/cms/current equals the coded install list plus exactly the two excluded test files, the installed CMS tree
 * (ef3f927) equals 77a3efea, and every pre-state digest equals its runtime-manifest entry (new paths absent).
 * The B1134/B1144/B1145 helpers already on the host are verified by digest (package.json), not redelivered.
 * Usage: php package.php <repo root> <b1146 dir> <b1134 dir> <b1144 dir> <b1145 dir> <runtime-manifest.json> <output dir> <helper source commit>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$repo,$tools,$b1134,$b1144,$b1145,$manifestPath,$out,$helper]=$argv+array_fill(0,9,null);
if(!is_dir((string)$repo.'/.git')&&!is_file((string)$repo.'/.git'))throw new RuntimeException('PACKAGE_REPO');if(!is_dir((string)$tools)||!is_file((string)$manifestPath)||!preg_match('/^[a-f0-9]{40}$/D',(string)$helper))throw new RuntimeException('PACKAGE_ARGUMENTS');if(!is_dir($out)&&!mkdir($out,0755,true))throw new RuntimeException('PACKAGE_OUTPUT');
require $b1134.'/InstallFileOps.php';require $b1144.'/FreshnessProbe.php';require $b1144.'/LeanGates.php';require $b1144.'/LeanWeb.php';require $b1145.'/EnvFlags.php';require $b1145.'/MigrateUnclampCoordinator.php';require $tools.'/OverlayProbe.php';require $tools.'/OverlayWeb.php';require $tools.'/OverlayCoordinator.php';
const SOURCE='eabe16af8c19fade882e25c70425eb43c3e17ed9';const INSTALLED='ef3f927b77ba573881c2b6dba341732c417e6725';const CMS='apps/cms/current/';
$git=static function(array $args,bool $allowFail=false)use($repo):?string{$p=proc_open(array_merge(['git','-C',$repo,'-c','core.autocrlf=false'],$args),[1=>['pipe','w'],2=>['pipe','w']],$pipes);$o=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0){if($allowFail)return null;throw new RuntimeException('PACKAGE_GIT_'.$args[0]);}return (string)$o;};
$base=trim((string)$git(['rev-parse','--verify','77a3efea^{commit}']));if(!preg_match('/^77a3efea[a-f0-9]{32}$/D',$base))throw new RuntimeException('PACKAGE_BASE_COMMIT');
if(trim((string)$git(['rev-parse','--verify',SOURCE.'^{commit}']))!==SOURCE)throw new RuntimeException('PACKAGE_SOURCE_COMMIT');
// The installed CMS tree (ef3f927, cms tree da8ac0c9) equals the PR base: the pre-state of every replaced file is 77a3efea.
if($git(['diff','--quiet',INSTALLED,$base,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_INSTALLED_TREE_DIFFERS_FROM_PR_BASE');
$diff=[];foreach(explode("\n",trim((string)$git(['diff','--no-renames','--name-status',$base,SOURCE,'--','apps/cms/current']))) as $line){if(!preg_match('~^([AM])\tapps/cms/current/(\S+)$~D',$line,$m))throw new RuntimeException('PACKAGE_DIFF_LINE');$diff[$m[2]]=$m[1];}
$excluded=['tests/Feature/PublicLegalPagesB1146Test.php'=>'A','tests/Feature/PublicMerchantSettingsB801Test.php'=>'M'];foreach($excluded as $p=>$k){if(($diff[$p]??null)!==$k)throw new RuntimeException('PACKAGE_EXCLUDED_TEST_SET');unset($diff[$p]);}
$expected=[];foreach(OverlayCoordinator::STEPS as [$p,$kind])$expected[$p]=$kind==='new'?'A':'M';ksort($diff,SORT_STRING);ksort($expected,SORT_STRING);
if($diff!==$expected){fwrite(STDERR,json_encode(['diff'=>$diff,'expected'=>$expected],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);throw new RuntimeException('PACKAGE_FILE_SET_DIFFERS_FROM_PR152_DIFF');}
$manifest=json_decode((string)file_get_contents($manifestPath),true,512,JSON_THROW_ON_ERROR);if(hash_file('sha256',$manifestPath)!==OverlayCoordinator::MANIFEST_SHA||count($manifest['files'])!==OverlayCoordinator::MANIFEST_FILES)throw new RuntimeException('PACKAGE_RUNTIME_MANIFEST');$byPath=[];foreach($manifest['files'] as $f)$byPath[$f['path']]=$f['sha256'];
$pins=['b1134-files.php'=>[$b1134.'/InstallFileOps.php','9dce8324bd4464a1689918775736bcd3a850f2b1c2b1b0ca7e130a9e486da12b'],'b1144-gates.php'=>[$b1144.'/LeanGates.php','7e62150fcad0884255ddb3c18063f5e2a23852a3eb0fdecb978c2955392ccf6e'],'b1144-probe.php'=>[$b1144.'/FreshnessProbe.php','3dbf4762e419d8df053fa7e642bd1b85df07d66dbffdb94fe0d8d5cdc1b50616'],'b1144-web.php'=>[$b1144.'/LeanWeb.php','54a04bc4dea81d56ad5fe0e6b25a141d4a86acf803f6df95f1c5d2b7adff6a73'],
    'b1145-env.php'=>[$b1145.'/EnvFlags.php','04a4bbd13b8aef346247d8641445ccb9f0a2d7fd3f26f7a03723724fc0132f69'],'b1145-app-state.php'=>[$b1145.'/AppState.php','642ddede76db8f1ea66bfc6a8d1ad74c894a70b665204cac4c96de17e97113a5'],'b1145-coordinator.php'=>[$b1145.'/MigrateUnclampCoordinator.php','46b159fbeab6c4f849dbb1a9291bbf13245b511ea58fd51646f26f28caa53fb9']];
foreach($pins as $name=>[$source,$digest])if(hash_file('sha256',$source)!==$digest)throw new RuntimeException('PINNED_DEPENDENCY_DRIFT_'.$name);
$members=['b1146-legal-overlay.php'=>'operator-entry.php','b1146-coordinator.php'=>'OverlayCoordinator.php','b1146-probe.php'=>'OverlayProbe.php','b1146-web.php'=>'OverlayWeb.php','b1146-app-check.php'=>'AppCheck.php','b1146-README-SR.md'=>'README-SR.md'];
$bytes=[];foreach($members as $name=>$source){$data=file_get_contents($tools.'/'.$source);if(!is_string($data)||str_contains($data,"\r"))throw new RuntimeException('PACKAGE_SOURCE_BYTES_'.$name);$bytes[$name]=$data;}
// The shipped tool code must not reach cPanel, reset OPcache globally, overlay PHP flags, migrate, seed, or write the DB switch or the approval.
foreach($bytes as $name=>$data)if(str_ends_with($name,'.php')&&preg_match('~/usr/local/cpanel|uapi|DomainInfo|opcache_reset|php_flag|HostProcessGuard|--seed|migrate:fresh|migrate:rollback|migrate:refresh|migrate:reset|db:wipe|\'migrate\'|config:cache|public_storefront_open|->update\(|->insert\(|->upsert\(|->delete\(|->set\(|->put\(|Mail::|mail\(~i',$data))throw new RuntimeException('PACKAGE_FORBIDDEN_MECHANISM_'.$name);
$overlay=[];
foreach(OverlayCoordinator::STEPS as $i=>[$path,$kind,$group]){
    $n=$i+1;$payload=(string)$git(['show',SOURCE.':'.CMS.$path]);$pre=$kind==='modified'?(string)$git(['show',$base.':'.CMS.$path]):null;
    if($kind==='new'&&($git(['cat-file','-e',$base.':'.CMS.$path],true)!==null||isset($byPath[$path])))throw new RuntimeException('PACKAGE_NEW_PATH_EXISTS_'.$n);
    if($kind==='modified'&&($byPath[$path]??null)!==hash('sha256',(string)$pre))throw new RuntimeException('PACKAGE_PRE_STATE_NOT_MANIFEST_'.$n);
    if(str_contains($payload,"\r"))throw new RuntimeException('PACKAGE_PAYLOAD_CR_'.$n);
    $member=sprintf('b1146-payload-%02d.bin',$n);$bytes[$member]=$payload;$overlay[]=['n'=>$n,'path'=>$path,'kind'=>$kind,'group'=>$group,'payload'=>$member,'payloadSHA256'=>hash('sha256',$payload),'preSHA256'=>$pre===null?null:hash('sha256',$pre),'size'=>strlen($payload)];
}
$files=[];foreach($pins as $name=>[,$digest])$files[$name]=$digest;foreach($bytes as $name=>$data)$files[$name]=hash('sha256',$data);ksort($files,SORT_STRING);
$common=['schemaVersion'=>1,'batch'=>'B1146','sourceCommit'=>SOURCE,'baseCommit'=>$base,'installedSourceCommit'=>INSTALLED,'cmsTree'=>'da8ac0c953e3c6741b921bb9fa660f80d247c55e','helperSource'=>$helper];
$bytes['b1146-package.json']=json_encode($common+['overlay'=>$overlay,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
OverlayCoordinator::plan(json_decode($bytes['b1146-package.json'],true,64,JSON_THROW_ON_ERROR),'/home/icaffeco/ald1n-project/incoming');
$bytes['b1146-provenance.json']=json_encode($common+['author'=>'Ald1n <https://ald1n.com>','workflowRun'=>getenv('GITHUB_RUN_ID')?:null,'workflowRepository'=>getenv('GITHUB_REPOSITORY')?:null,'pullRequest'=>152,
    'predecessor'=>['report'=>'docs/operations/1108-B1145-CONTROLLED-MIGRATE-UNCLAMP-<date>-<time>.md','bytesPinned'=>false,'required'=>['B1145_FINAL'=>'PASS_MIGRATED_UNCLAMPED_STOREFRONT_CLOSED','EXIT'=>'0','B1145_PHASE'=>'CLOSED_UNCLAMPED','b1145State'=>'CLOSED_UNCLAMPED'],'b1145HelperSource'=>'84facde9ff138d1e7dd0de7af5c811d800b0ccfe'],
    'runtimeManifestSHA256'=>OverlayCoordinator::MANIFEST_SHA,'configAfterB1145SHA256'=>OverlayCoordinator::CONFIG_SHA,'excludedTests'=>array_keys($excluded),'installOrder'=>array_map(static fn($s)=>$s[2].' '.$s[1].' '.$s[0],OverlayCoordinator::STEPS),'pinnedNotRedelivered'=>array_map(fn($p)=>$p[1],$pins),
    'neverDone'=>['storefrontDbSwitchWrite','merchantApprovalWrite','migration','envEdit','configChange','opcacheGlobalReset','mail'],'operatorCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1146-legal-overlay.sh','rollbackCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1146-legal-overlay.sh --rollback'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
ksort($bytes,SORT_STRING);$checksums='';foreach($bytes as $name=>$data)$checksums.=hash('sha256',$data).'  '.$name."\n";
$wrapper=file_get_contents($tools.'/operator-wrapper.sh');if(!is_string($wrapper)||str_contains($wrapper,"\r")||substr_count($wrapper,"\nSELF_SHA=__SELF_SHA__\n")!==1||substr_count($wrapper,"\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n")!==1)throw new RuntimeException('WRAPPER_TEMPLATE');
$wrapper=str_replace("\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n","\nCHECKSUMS_SHA=".hash('sha256',$checksums)."\n",$wrapper);$wrapper=str_replace("\nSELF_SHA=__SELF_SHA__\n","\nSELF_SHA=".hash('sha256',$wrapper)."\n",$wrapper);
$bytes['b1146-package.sha256']=$checksums;$bytes['b1146-legal-overlay.sh']=$wrapper;ksort($bytes,SORT_STRING);
$zipPath=$out.'/b1146-operator.zip';if(file_exists($zipPath))unlink($zipPath);$zip=new ZipArchive();if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('ZIP_CREATE');
foreach($bytes as $name=>$data){$member='incoming/'.$name;$zip->addFromString($member,$data);$zip->setExternalAttributesName($member,ZipArchive::OPSYS_UNIX,(str_ends_with($name,'.sh')?0100755:0100644)<<16);$zip->setMtimeName($member,1762560000);}
if(!$zip->close())throw new RuntimeException('ZIP_CLOSE');
$zipSHA=hash_file('sha256',$zipPath);file_put_contents($zipPath.'.sha256',$zipSHA.'  b1146-operator.zip'."\n");
echo json_encode(['zip'=>basename($zipPath),'zipSHA256'=>$zipSHA,'zipBytes'=>filesize($zipPath),'members'=>array_map(fn($n)=>'incoming/'.$n,array_keys($bytes)),'checksumsSHA256'=>hash('sha256',$checksums),'wrapperSHA256'=>hash('sha256',$wrapper),'overlay'=>$overlay,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
