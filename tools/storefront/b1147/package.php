<?php
declare(strict_types=1);
/**
 * Builds the B1147 operator ZIP with root-relative incoming/ members only. Everything is read from git at build time, never
 * from a working tree: the payload is git show SOURCE_COMMIT:<path>, the pre-state is git show eabe16af:<path> (main, the
 * B1146 payload), and the B1146 overlay (eabe16af over 77a3efea) that turns the 8531-file runtime manifest into the live
 * 8543-file tree. The payload is shipped as flat incoming/b1147-payload-NN.bin members, so unzip never writes into the
 * live CMS tree. The build fails unless:
 * - git diff --name-status eabe16af SOURCE_COMMIT under apps/cms/current, minus tests/, equals the coded install list
 *   (5 modified, 3 new);
 * - git diff 77a3efea eabe16af under apps/cms/current, minus its two tests, equals the B1146 install list, each B1146 pre
 *   digest equals its runtime-manifest entry and each B1146 new path is absent from the manifest;
 * - each B1147 pre digest equals the B1146 payload digest (a file B1146 installed) or the manifest entry (any other file),
 *   and each B1147 new path is absent at eabe16af, in the manifest and in the B1146 set;
 * - the three markers (layout HTML, catalog view HTML, CSS selector) are present in the new bytes and absent from the old,
 *   the catalog view carries the JSON-LD block, and the layout links the CSS with exactly one static ?v= suffix.
 * The B1134/B1144/B1145/B1146 helpers already on the host are verified by digest (package.json), not redelivered.
 * Usage: php package.php <repo root> <b1147 dir> <b1134 dir> <b1144 dir> <b1145 dir> <b1146 dir> <runtime-manifest.json> <output dir> <helper source commit>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$repo,$tools,$b1134,$b1144,$b1145,$b1146,$manifestPath,$out,$helper]=$argv+array_fill(0,10,null);
if(!is_dir((string)$repo.'/.git')&&!is_file((string)$repo.'/.git'))throw new RuntimeException('PACKAGE_REPO');if(!is_dir((string)$tools)||!is_dir((string)$b1146)||!is_file((string)$manifestPath)||!preg_match('/^[a-f0-9]{40}$/D',(string)$helper))throw new RuntimeException('PACKAGE_ARGUMENTS');if(!is_dir($out)&&!mkdir($out,0755,true))throw new RuntimeException('PACKAGE_OUTPUT');
require $b1134.'/InstallFileOps.php';require $b1144.'/FreshnessProbe.php';require $b1144.'/LeanGates.php';require $b1144.'/LeanWeb.php';require $b1145.'/EnvFlags.php';require $b1145.'/MigrateUnclampCoordinator.php';require $b1146.'/OverlayProbe.php';require $b1146.'/OverlayWeb.php';require $b1146.'/OverlayCoordinator.php';require $tools.'/CatalogWeb.php';require $tools.'/CatalogOverlayCoordinator.php';
const CMS='apps/cms/current/';const B1146_BASE='77a3efea';const INSTALLED='ef3f927b77ba573881c2b6dba341732c417e6725';
const MARKERS=['layout'=>'class="shop-brand-mark"','catalog'=>'class="shop-filter-panel"','css'=>'.shop-brand-mark'];
$source=CatalogOverlayCoordinator::SOURCE_COMMIT;$main=CatalogOverlayCoordinator::BASE_COMMIT;
$git=static function(array $args,bool $allowFail=false)use($repo):?string{$p=proc_open(array_merge(['git','-C',$repo,'-c','core.autocrlf=false'],$args),[1=>['pipe','w'],2=>['pipe','w']],$pipes);$o=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0){if($allowFail)return null;throw new RuntimeException('PACKAGE_GIT_'.$args[0]);}return (string)$o;};
$blob=static fn(string $commit,string $path):string=>(string)$git(['show',$commit.':'.CMS.$path]);
foreach([$source,$main,OverlayCoordinator::SOURCE_COMMIT,INSTALLED] as $c)if(trim((string)$git(['rev-parse','--verify',$c.'^{commit}']))!==$c)throw new RuntimeException('PACKAGE_COMMIT_'.substr($c,0,8));
if($main!==OverlayCoordinator::SOURCE_COMMIT)throw new RuntimeException('PACKAGE_MAIN_IS_NOT_THE_B1146_SOURCE');
if($git(['merge-base','--is-ancestor',$main,$source],true)===null)throw new RuntimeException('PACKAGE_SOURCE_NOT_ON_MAIN');
$b1146Base=trim((string)$git(['rev-parse','--verify',B1146_BASE.'^{commit}']));if(!preg_match('/^77a3efea[a-f0-9]{32}$/D',$b1146Base))throw new RuntimeException('PACKAGE_B1146_BASE');
if($git(['diff','--quiet',INSTALLED,$b1146Base,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_INSTALLED_TREE_DIFFERS_FROM_B1146_BASE');
$diff=static function(string $from,string $to)use($git):array{$d=[];foreach(explode("\n",trim((string)$git(['diff','--no-renames','--name-status',$from,$to,'--','apps/cms/current']))) as $line){if(!preg_match('~^([AM])\tapps/cms/current/(\S+)$~D',$line,$m))throw new RuntimeException('PACKAGE_DIFF_LINE');$d[$m[2]]=$m[1];}ksort($d,SORT_STRING);return $d;};
$manifest=json_decode((string)file_get_contents($manifestPath),true,512,JSON_THROW_ON_ERROR);if(hash_file('sha256',$manifestPath)!==CatalogOverlayCoordinator::MANIFEST_SHA||count($manifest['files'])!==CatalogOverlayCoordinator::MANIFEST_FILES)throw new RuntimeException('PACKAGE_RUNTIME_MANIFEST');$byPath=[];foreach($manifest['files'] as $f)$byPath[$f['path']]=$f['sha256'];

// The B1146 overlay that is live in production (PR #152 = main eabe16af over 77a3efea).
$d1146=$diff($b1146Base,$main);foreach(['tests/Feature/PublicLegalPagesB1146Test.php'=>'A','tests/Feature/PublicMerchantSettingsB801Test.php'=>'M'] as $p=>$k){if(($d1146[$p]??null)!==$k)throw new RuntimeException('PACKAGE_B1146_EXCLUDED_TEST_SET');unset($d1146[$p]);}
$e1146=[];foreach(OverlayCoordinator::STEPS as [$p,$kind])$e1146[$p]=$kind==='new'?'A':'M';ksort($e1146,SORT_STRING);if($d1146!==$e1146)throw new RuntimeException('PACKAGE_B1146_FILE_SET');
$b1146Overlay=[];$b1146Sha=[];
foreach(OverlayCoordinator::STEPS as [$p,$kind]){
    $sha=hash('sha256',$blob($main,$p));$pre=$kind==='modified'?hash('sha256',$blob($b1146Base,$p)):null;
    if($kind==='modified'&&($byPath[$p]??null)!==$pre)throw new RuntimeException('PACKAGE_B1146_PRE_NOT_MANIFEST');if($kind==='new'&&isset($byPath[$p]))throw new RuntimeException('PACKAGE_B1146_NEW_IN_MANIFEST');
    $b1146Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1146Sha[$p]=$sha;
}

// The B1147 catalog file set: everything under apps/cms/current except tests/.
$d=$diff($main,$source);$excluded=[];foreach(array_keys($d) as $p)if(str_starts_with($p,'tests/')){$excluded[]=$p;unset($d[$p]);}
$expected=[];foreach(CatalogOverlayCoordinator::STEPS as [$p,$kind])$expected[$p]=$kind==='new'?'A':'M';ksort($expected,SORT_STRING);
if($d!==$expected){fwrite(STDERR,json_encode(['diff'=>$d,'expected'=>$expected],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);throw new RuntimeException('PACKAGE_FILE_SET_DIFFERS_FROM_THE_CATALOG_DIFF');}

$pins=['b1134-files.php'=>[$b1134.'/InstallFileOps.php','9dce8324bd4464a1689918775736bcd3a850f2b1c2b1b0ca7e130a9e486da12b'],'b1144-gates.php'=>[$b1144.'/LeanGates.php','7e62150fcad0884255ddb3c18063f5e2a23852a3eb0fdecb978c2955392ccf6e'],'b1144-probe.php'=>[$b1144.'/FreshnessProbe.php','3dbf4762e419d8df053fa7e642bd1b85df07d66dbffdb94fe0d8d5cdc1b50616'],'b1144-web.php'=>[$b1144.'/LeanWeb.php','54a04bc4dea81d56ad5fe0e6b25a141d4a86acf803f6df95f1c5d2b7adff6a73'],
    'b1145-env.php'=>[$b1145.'/EnvFlags.php','04a4bbd13b8aef346247d8641445ccb9f0a2d7fd3f26f7a03723724fc0132f69'],'b1145-app-state.php'=>[$b1145.'/AppState.php','642ddede76db8f1ea66bfc6a8d1ad74c894a70b665204cac4c96de17e97113a5'],'b1145-coordinator.php'=>[$b1145.'/MigrateUnclampCoordinator.php','46b159fbeab6c4f849dbb1a9291bbf13245b511ea58fd51646f26f28caa53fb9'],
    'b1146-probe.php'=>[$b1146.'/OverlayProbe.php','abcc9c64c6b9918d180afd387ec917a4924e1ce28c0099c0bbdae500e811c01f'],'b1146-app-check.php'=>[$b1146.'/AppCheck.php','68b4b2af5ad5a6399991adf474cfc753abf77a483fb12d2c1669a4b307ce2c2b']];
foreach($pins as $name=>[$file,$digest])if(hash_file('sha256',$file)!==$digest)throw new RuntimeException('PINNED_DEPENDENCY_DRIFT_'.$name);
$members=['b1147-catalog-overlay.php'=>'operator-entry.php','b1147-coordinator.php'=>'CatalogOverlayCoordinator.php','b1147-web.php'=>'CatalogWeb.php','b1147-app-check.php'=>'AppCheck.php','b1147-README-SR.md'=>'README-SR.md'];
$bytes=[];foreach($members as $name=>$file){$data=file_get_contents($tools.'/'.$file);if(!is_string($data)||str_contains($data,"\r"))throw new RuntimeException('PACKAGE_SOURCE_BYTES_'.$name);$bytes[$name]=$data;}
// The shipped tool code must not reach cPanel, reset OPcache globally, overlay PHP flags, migrate, seed, or write the DB switch or any row.
foreach($bytes as $name=>$data)if(str_ends_with($name,'.php')&&preg_match('~/usr/local/cpanel|uapi|DomainInfo|opcache_reset|php_flag|HostProcessGuard|--seed|migrate:fresh|migrate:rollback|migrate:refresh|migrate:reset|db:wipe|\'migrate\'|config:cache|view:cache|route:cache|route:clear|public_storefront_open|->update\(|->insert\(|->upsert\(|->delete\(|->set\(|->put\(|Mail::|mail\(|CURLOPT_POST|CURLOPT_CUSTOMREQUEST~i',$data))throw new RuntimeException('PACKAGE_FORBIDDEN_MECHANISM_'.$name);

$overlay=[];$new=[];$old=[];
foreach(CatalogOverlayCoordinator::STEPS as $i=>[$path,$kind,$group]){
    $n=$i+1;$payload=$blob($source,$path);$pre=$kind==='modified'?$blob($main,$path):null;$new[$path]=$payload;$old[$path]=$pre;
    if($kind==='new'&&($git(['cat-file','-e',$main.':'.CMS.$path],true)!==null||isset($byPath[$path])||isset($b1146Sha[$path])))throw new RuntimeException('PACKAGE_NEW_PATH_EXISTS_'.$n);
    if($kind==='modified'){$preSha=hash('sha256',(string)$pre);$live=$b1146Sha[$path]??($byPath[$path]??null);if($live!==$preSha)throw new RuntimeException('PACKAGE_PRE_STATE_NOT_LIVE_'.$n);}
    if(str_contains($payload,"\r"))throw new RuntimeException('PACKAGE_PAYLOAD_CR_'.$n);
    $member=sprintf('b1147-payload-%02d.bin',$n);$bytes[$member]=$payload;$overlay[]=['n'=>$n,'path'=>$path,'kind'=>$kind,'group'=>$group,'payload'=>$member,'payloadSHA256'=>hash('sha256',$payload),'preSHA256'=>$pre===null?null:hash('sha256',$pre),'size'=>strlen($payload),'liveBefore'=>$kind==='new'?'ABSENT':(isset($b1146Sha[$path])?'B1146_PAYLOAD':'RUNTIME_MANIFEST')];
}
$layout='resources/views/public-shop/layout.blade.php';$catalog='resources/views/public-shop/catalog.blade.php';$css='public/assets/css/ald1n-public-storefront.css';
foreach(['layout'=>$layout,'catalog'=>$catalog,'css'=>$css] as $k=>$p)if(!str_contains($new[$p],MARKERS[$k])||str_contains((string)$old[$p],MARKERS[$k]))throw new RuntimeException('PACKAGE_MARKER_'.strtoupper($k));
// The old layout and old views of the live tree never carry the layout marker (legal pages render the same layout).
foreach($b1146Overlay as $e)if(str_ends_with($e['path'],'.blade.php')&&str_contains($blob($main,$e['path']),MARKERS['layout']))throw new RuntimeException('PACKAGE_LAYOUT_MARKER_IN_OLD_VIEW');
if(!str_contains($new[$catalog],'<script type="application/ld+json">'))throw new RuntimeException('PACKAGE_CATALOG_JSON_LD');
if(preg_match_all('~ald1n-public-storefront\.css\?v=([0-9]{1,12})[\'"]~',$new[$layout],$v)!==1)throw new RuntimeException('PACKAGE_CSS_VERSION');$cssVersion=$v[1][0];
$files=[];foreach($pins as $name=>[,$digest])$files[$name]=$digest;foreach($bytes as $name=>$data)$files[$name]=hash('sha256',$data);ksort($files,SORT_STRING);
$common=['schemaVersion'=>1,'batch'=>'B1147','sourceCommit'=>$source,'baseCommit'=>$main,'b1146BaseCommit'=>$b1146Base,'installedSourceCommit'=>INSTALLED,'helperSource'=>$helper];
$bytes['b1147-package.json']=json_encode($common+['overlay'=>$overlay,'b1146Overlay'=>$b1146Overlay,'markers'=>MARKERS,'cssVersion'=>$cssVersion,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
$check=json_decode($bytes['b1147-package.json'],true,64,JSON_THROW_ON_ERROR);CatalogOverlayCoordinator::plan($check,'/home/icaffeco/ald1n-project/incoming');CatalogOverlayCoordinator::b1146Plan($check);CatalogOverlayCoordinator::markers($check);
$bytes['b1147-provenance.json']=json_encode($common+['author'=>'Ald1n <https://ald1n.com>','workflowRun'=>getenv('GITHUB_RUN_ID')?:null,'workflowRepository'=>getenv('GITHUB_REPOSITORY')?:null,'pullRequest'=>154,
    'predecessor'=>['report'=>'docs/operations/1109-B1146-LEGAL-OVERLAY-<date>-<time>.md','bytesPinned'=>false,'required'=>['B1146_FINAL'=>CatalogOverlayCoordinator::B1146_PASS,'EXIT'=>'0','B1146_PHASE'=>'INSTALLED_PASS','b1146State'=>'INSTALLED_PASS']],
    'runtimeManifestSHA256'=>CatalogOverlayCoordinator::MANIFEST_SHA,'treeFilesBefore'=>CatalogOverlayCoordinator::B1146_TREE_FILES,'treeFilesAfter'=>CatalogOverlayCoordinator::INSTALLED_TREE_FILES,'configAfterB1145SHA256'=>CatalogOverlayCoordinator::CONFIG_SHA,'excludedTests'=>$excluded,'installOrder'=>array_map(static fn($s)=>$s[2].' '.$s[1].' '.$s[0],CatalogOverlayCoordinator::STEPS),'pinnedNotRedelivered'=>array_map(fn($p)=>$p[1],$pins),
    'cloudflarePurge'=>['https://cms.ald1n.com'.CatalogOverlayCoordinator::CSS_PATH,'https://cms.ald1n.com'.CatalogOverlayCoordinator::CSS_PATH.'?v='.$cssVersion],
    'neverDone'=>['storefrontDbSwitchWrite','merchantApprovalWrite','migration','routeChange','envEdit','configChange','opcacheGlobalReset','viewCache','mail','productOrCartWrite'],'operatorCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1147-catalog-overlay.sh','rollbackCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1147-catalog-overlay.sh --rollback'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
ksort($bytes,SORT_STRING);$checksums='';foreach($bytes as $name=>$data)$checksums.=hash('sha256',$data).'  '.$name."\n";
$wrapper=file_get_contents($tools.'/operator-wrapper.sh');if(!is_string($wrapper)||str_contains($wrapper,"\r")||substr_count($wrapper,"\nSELF_SHA=__SELF_SHA__\n")!==1||substr_count($wrapper,"\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n")!==1)throw new RuntimeException('WRAPPER_TEMPLATE');
$wrapper=str_replace("\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n","\nCHECKSUMS_SHA=".hash('sha256',$checksums)."\n",$wrapper);$wrapper=str_replace("\nSELF_SHA=__SELF_SHA__\n","\nSELF_SHA=".hash('sha256',$wrapper)."\n",$wrapper);
$bytes['b1147-package.sha256']=$checksums;$bytes['b1147-catalog-overlay.sh']=$wrapper;ksort($bytes,SORT_STRING);
$zipPath=$out.'/b1147-operator.zip';if(file_exists($zipPath))unlink($zipPath);$zip=new ZipArchive();if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('ZIP_CREATE');
foreach($bytes as $name=>$data){$member='incoming/'.$name;$zip->addFromString($member,$data);$zip->setExternalAttributesName($member,ZipArchive::OPSYS_UNIX,(str_ends_with($name,'.sh')?0100755:0100644)<<16);$zip->setMtimeName($member,1762646400);}
if(!$zip->close())throw new RuntimeException('ZIP_CLOSE');
$zipSHA=hash_file('sha256',$zipPath);file_put_contents($zipPath.'.sha256',$zipSHA.'  b1147-operator.zip'."\n");
echo json_encode(['zip'=>basename($zipPath),'zipSHA256'=>$zipSHA,'zipBytes'=>filesize($zipPath),'members'=>array_map(fn($n)=>'incoming/'.$n,array_keys($bytes)),'checksumsSHA256'=>hash('sha256',$checksums),'wrapperSHA256'=>hash('sha256',$wrapper),'cssVersion'=>$cssVersion,'overlay'=>$overlay,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
