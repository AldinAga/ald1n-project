<?php
declare(strict_types=1);
/**
 * Builds the B1150 operator ZIP with root-relative incoming/ members only. Everything is read from git at build time, never
 * from a working tree: the payload is git show SOURCE_COMMIT:<path> (the reviewed head of codex/product-media-route-20261112),
 * the pre-state is git show BASE_COMMIT:<path> (7e748158, whose apps/cms/current without tests is the live tree after B1149), and
 * the four earlier overlays (B1146: eabe16af over 77a3efea; B1147: 764bf513 over eabe16af; B1148: 56082845 over 764bf513;
 * B1149: 7e748158 over eca7755c) turn the 8531-file runtime manifest into the live 8564-file tree. The payload is shipped as
 * flat incoming/b1150-payload-NN.bin members, so unzip never writes into the live CMS tree. The build fails unless:
 * - BASE and SOURCE are related (BASE ancestor of SOURCE and of MAIN) and main above BASE touches nothing under apps/cms/current;
 * - git diff --name-status BASE SOURCE, minus apps/cms/current/tests/, .github/workflows/ and docs/, is exactly the coded
 *   install list MediaInstallCoordinator::STEPS (all under apps/cms/current, nothing under database/, config/, composer.*, no delete);
 * - the B1146, B1147, B1148 and B1149 file sets equal the pinned coordinators' lists, each pre digest equals the live digest before
 *   it (manifest, or the earlier overlay's payload) and each new path is absent before;
 * - each B1150 pre digest (git blob at BASE) equals the LIVE digest by the second, independent source (B1149 payload, else
 *   B1148, B1147, B1146, else the runtime manifest; the source is printed per file), and each new path is absent everywhere;
 * - the B1149 markers are in the final bytes and in the pre-state bytes of the layout, CSS and views (they hold in both states), the
 *   gallery stylesheet link keeps one and the same ?v= in the catalog and product views, and the dependency facts behind the
 *   install order (B1150_FILL below) hold in the new bytes.
 * The B1134-B1149 helpers already on the host are verified by digest (package.json), not redelivered.
 * Besides the ZIP it writes b1150-digests.tsv (path, kind, group, pre digest, pre-state source, payload digest) next to it.
 * Usage: php package.php <repo root> <b1150 dir> <b1134 dir> <b1144 dir> <b1145 dir> <b1146 dir> <b1147 dir> <b1148 dir> <b1149 dir> <runtime-manifest.json> <output dir> <helper source commit> <source commit>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$repo,$tools,$b1134,$b1144,$b1145,$b1146,$b1147,$b1148,$b1149,$manifestPath,$out,$helper,$sourceArg]=$argv+array_fill(0,14,null);
if(!is_dir((string)$repo.'/.git')&&!is_file((string)$repo.'/.git'))throw new RuntimeException('PACKAGE_REPO');if(!is_dir((string)$tools)||!is_dir((string)$b1149)||!is_file((string)$manifestPath)||!preg_match('/^[a-f0-9]{40}$/D',(string)$helper)||!preg_match('/^[a-f0-9]{40}$/D',(string)$sourceArg))throw new RuntimeException('PACKAGE_ARGUMENTS');if(!is_dir($out)&&!mkdir($out,0755,true))throw new RuntimeException('PACKAGE_OUTPUT');
require $b1134.'/InstallFileOps.php';require $b1144.'/FreshnessProbe.php';require $b1144.'/LeanGates.php';require $b1144.'/LeanWeb.php';require $b1145.'/EnvFlags.php';require $b1145.'/MigrateUnclampCoordinator.php';require $b1146.'/OverlayProbe.php';require $b1146.'/OverlayWeb.php';require $b1146.'/OverlayCoordinator.php';require $b1147.'/CatalogWeb.php';require $b1147.'/CatalogOverlayCoordinator.php';require $b1148.'/GlassWeb.php';require $b1148.'/GlassOverlayCoordinator.php';require $b1149.'/StorefrontInstallCoordinator.php';require $tools.'/MediaWeb.php';require $tools.'/MediaInstallCoordinator.php';
if(MediaInstallCoordinator::STEPS===[]||MediaInstallCoordinator::SOURCE_COMMIT===str_repeat('0',40))throw new RuntimeException('B1150_PAYLOAD_NOT_FILLED_IN_STEPS_AND_SOURCE_COMMIT_ARE_B1150_FILL');
const CMS='apps/cms/current/';const B1146_BASE='77a3efea';const INSTALLED='ef3f927b77ba573881c2b6dba341732c417e6725';
const MARKERS=MediaInstallCoordinator::MARKERS;
$source=MediaInstallCoordinator::SOURCE_COMMIT;$base=MediaInstallCoordinator::BASE_COMMIT;$main=MediaInstallCoordinator::MAIN_COMMIT;$store=MediaInstallCoordinator::STOREFRONT_COMMIT;$storeBase=MediaInstallCoordinator::STOREFRONT_BASE_COMMIT;$storeMain=MediaInstallCoordinator::STOREFRONT_MAIN_COMMIT;$glass=MediaInstallCoordinator::GLASS_COMMIT;$catalog=MediaInstallCoordinator::CATALOG_COMMIT;$legal=MediaInstallCoordinator::LEGAL_COMMIT;
// The workflow passes the head it reviewed; it must be the commit the coordinator is pinned to.
if($sourceArg!==$source)throw new RuntimeException('PACKAGE_SOURCE_ARGUMENT_DIFFERS_FROM_THE_PINNED_SOURCE_COMMIT');
$git=static function(array $args,bool $allowFail=false)use($repo):?string{$p=proc_open(array_merge(['git','-C',$repo,'-c','core.autocrlf=false'],$args),[1=>['pipe','w'],2=>['pipe','w']],$pipes);$o=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0){if($allowFail)return null;throw new RuntimeException('PACKAGE_GIT_'.$args[0]);}return (string)$o;};
$blob=static fn(string $commit,string $path):string=>(string)$git(['show',$commit.':'.CMS.$path]);
foreach([$source,$base,$main,$store,$storeBase,$storeMain,$glass,$catalog,$legal,INSTALLED] as $c)if(trim((string)$git(['rev-parse','--verify',$c.'^{commit}']))!==$c)throw new RuntimeException('PACKAGE_COMMIT_'.substr($c,0,8));
if($legal!==OverlayCoordinator::SOURCE_COMMIT||$catalog!==CatalogOverlayCoordinator::SOURCE_COMMIT||$legal!==CatalogOverlayCoordinator::BASE_COMMIT||$glass!==GlassOverlayCoordinator::SOURCE_COMMIT||$catalog!==GlassOverlayCoordinator::BASE_COMMIT||$legal!==GlassOverlayCoordinator::MAIN_COMMIT)throw new RuntimeException('PACKAGE_CHAIN_COMMITS_DIFFER_FROM_THE_PINNED_COORDINATORS');
if($store!==StorefrontInstallCoordinator::SOURCE_COMMIT||$storeBase!==StorefrontInstallCoordinator::BASE_COMMIT||$storeMain!==StorefrontInstallCoordinator::MAIN_COMMIT||$glass!==StorefrontInstallCoordinator::GLASS_COMMIT||$catalog!==StorefrontInstallCoordinator::CATALOG_COMMIT||$legal!==StorefrontInstallCoordinator::LEGAL_COMMIT||$base!==$store)throw new RuntimeException('PACKAGE_B1149_COMMITS_DIFFER_FROM_THE_PINNED_B1149_COORDINATOR');
foreach([[$legal,$catalog],[$catalog,$glass],[$storeBase,$store],[$base,$source],[$base,$main]] as [$a,$b])if($git(['merge-base','--is-ancestor',$a,$b],true)===null)throw new RuntimeException('PACKAGE_NOT_AN_ANCESTOR_'.substr($a,0,8).'_'.substr($b,0,8));
// Main above BASE (Report1115 and whatever else merged) must not touch the CMS tree the overlay is built against.
if($git(['diff','--quiet',$base,$main,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_MAIN_TOUCHES_THE_CMS_TREE_ABOVE_THE_INSTALLED_TREE');
if($git(['diff','--quiet',$glass,$storeBase,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_B1149_BASE_TREE_DIFFERS_FROM_THE_B1148_PAYLOAD_HEAD');
$b1146Base=trim((string)$git(['rev-parse','--verify',B1146_BASE.'^{commit}']));if(!preg_match('/^77a3efea[a-f0-9]{32}$/D',$b1146Base))throw new RuntimeException('PACKAGE_B1146_BASE');
if($git(['diff','--quiet',INSTALLED,$b1146Base,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_INSTALLED_TREE_DIFFERS_FROM_B1146_BASE');
$diff=static function(string $from,string $to,?string $scope='apps/cms/current')use($git):array{$d=[];$args=['diff','--no-renames','--name-status',$from,$to];if($scope!==null)array_push($args,'--',$scope);foreach(explode("\n",trim((string)$git($args))) as $line){if($line==='')continue;if(!preg_match('~^([AMD])\t(\S+)$~D',$line,$m))throw new RuntimeException('PACKAGE_DIFF_LINE');$d[$m[2]]=$m[1];}ksort($d,SORT_STRING);return $d;};
$cms=static function(array $d):array{$o=[];foreach($d as $p=>$k){if(!str_starts_with($p,CMS)||$k==='D')throw new RuntimeException('PACKAGE_DIFF_OUTSIDE_CMS_OR_DELETE_'.$p);$o[substr($p,strlen(CMS))]=$k;}return $o;};
$manifest=json_decode((string)file_get_contents($manifestPath),true,512,JSON_THROW_ON_ERROR);if(hash_file('sha256',$manifestPath)!==MediaInstallCoordinator::MANIFEST_SHA||count($manifest['files'])!==MediaInstallCoordinator::MANIFEST_FILES)throw new RuntimeException('PACKAGE_RUNTIME_MANIFEST');$byPath=[];foreach($manifest['files'] as $f)$byPath[$f['path']]=$f['sha256'];

// B1146 (PR #152 = main eabe16af over 77a3efea), as the B1147 and B1148 builds compute it.
$d1146=$cms($diff($b1146Base,$legal));foreach(['tests/Feature/PublicLegalPagesB1146Test.php'=>'A','tests/Feature/PublicMerchantSettingsB801Test.php'=>'M'] as $p=>$k){if(($d1146[$p]??null)!==$k)throw new RuntimeException('PACKAGE_B1146_EXCLUDED_TEST_SET');unset($d1146[$p]);}
$e=[];foreach(OverlayCoordinator::STEPS as [$p,$kind])$e[$p]=$kind==='new'?'A':'M';ksort($e,SORT_STRING);if($d1146!==$e)throw new RuntimeException('PACKAGE_B1146_FILE_SET');
$b1146Overlay=[];$b1146Sha=[];
foreach(OverlayCoordinator::STEPS as [$p,$kind]){
    $sha=hash('sha256',$blob($legal,$p));$pre=$kind==='modified'?hash('sha256',$blob($b1146Base,$p)):null;
    if($kind==='modified'&&($byPath[$p]??null)!==$pre)throw new RuntimeException('PACKAGE_B1146_PRE_NOT_MANIFEST');if($kind==='new'&&isset($byPath[$p]))throw new RuntimeException('PACKAGE_B1146_NEW_IN_MANIFEST');
    $b1146Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1146Sha[$p]=$sha;
}
// B1147 (PR #154 head 764bf513 over eabe16af, tests excluded).
$d1147=$cms($diff($legal,$catalog));foreach(array_keys($d1147) as $p)if(str_starts_with($p,'tests/'))unset($d1147[$p]);
$e=[];foreach(CatalogOverlayCoordinator::STEPS as [$p,$kind])$e[$p]=$kind==='new'?'A':'M';ksort($e,SORT_STRING);if($d1147!==$e)throw new RuntimeException('PACKAGE_B1147_FILE_SET');
$b1147Overlay=[];$b1147Sha=[];
foreach(CatalogOverlayCoordinator::STEPS as [$p,$kind]){
    $sha=hash('sha256',$blob($catalog,$p));$pre=$kind==='modified'?hash('sha256',$blob($legal,$p)):null;
    if($kind==='modified'&&($b1146Sha[$p]??($byPath[$p]??null))!==$pre)throw new RuntimeException('PACKAGE_B1147_PRE_NOT_LIVE');if($kind==='new'&&(isset($byPath[$p])||isset($b1146Sha[$p])))throw new RuntimeException('PACKAGE_B1147_NEW_ALREADY_LIVE');
    $b1147Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1147Sha[$p]=$sha;
}
// B1148 (PR #155 head 56082845 over 764bf513, tests and workflows excluded), as the approved B1148 build computes it.
$all=$diff($catalog,$glass,null);foreach(array_keys($all) as $p)if(str_starts_with($p,CMS.'tests/')||str_starts_with($p,'.github/workflows/'))unset($all[$p]);
$d1148=$cms($all);$e=[];foreach(GlassOverlayCoordinator::STEPS as [$p,$kind])$e[$p]=$kind==='new'?'A':'M';ksort($e,SORT_STRING);if($d1148!==$e)throw new RuntimeException('PACKAGE_B1148_FILE_SET');
$b1148Overlay=[];$b1148Sha=[];
foreach(GlassOverlayCoordinator::STEPS as [$p,$kind]){
    $sha=hash('sha256',$blob($glass,$p));$pre=$kind==='modified'?hash('sha256',$blob($catalog,$p)):null;
    if($kind==='modified'&&($b1147Sha[$p]??($b1146Sha[$p]??($byPath[$p]??null)))!==$pre)throw new RuntimeException('PACKAGE_B1148_PRE_NOT_LIVE');if($kind==='new'&&(isset($byPath[$p])||isset($b1146Sha[$p])||isset($b1147Sha[$p])))throw new RuntimeException('PACKAGE_B1148_NEW_ALREADY_LIVE');
    $b1148Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1148Sha[$p]=$sha;
}
// B1149 (7e748158 over eca7755c = the B1148 payload head tree; tests, workflows and docs excluded), as the approved B1149 build computes it.
$whole=$diff($storeBase,$store,null);foreach(array_keys($whole) as $p)if(str_starts_with($p,CMS.'tests/')||str_starts_with($p,'.github/workflows/')||str_starts_with($p,'docs/'))unset($whole[$p]);
$d1149=$cms($whole);$e=[];foreach(StorefrontInstallCoordinator::STEPS as [$p,$kind])$e[$p]=$kind==='new'?'A':'M';ksort($e,SORT_STRING);if($d1149!==$e)throw new RuntimeException('PACKAGE_B1149_FILE_SET');
$b1149Overlay=[];$b1149Sha=[];
foreach(StorefrontInstallCoordinator::STEPS as [$p,$kind]){
    $sha=hash('sha256',$blob($store,$p));$pre=$kind==='modified'?hash('sha256',$blob($storeBase,$p)):null;
    if($kind==='modified'&&($b1148Sha[$p]??($b1147Sha[$p]??($b1146Sha[$p]??($byPath[$p]??null))))!==$pre)throw new RuntimeException('PACKAGE_B1149_PRE_NOT_LIVE');if($kind==='new'&&(isset($byPath[$p])||isset($b1146Sha[$p])||isset($b1147Sha[$p])||isset($b1148Sha[$p])))throw new RuntimeException('PACKAGE_B1149_NEW_ALREADY_LIVE');
    $b1149Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1149Sha[$p]=$sha;
}

// The B1150 file set: the whole diff BASE..SOURCE minus the CMS tests, the CI workflows and docs; nothing else may remain.
$whole=$diff($base,$source,null);$excluded=[];foreach(array_keys($whole) as $p)if(str_starts_with($p,CMS.'tests/')||str_starts_with($p,'.github/workflows/')||str_starts_with($p,'docs/')){$excluded[]=$p;unset($whole[$p]);}
$d=$cms($whole);$expected=[];foreach(MediaInstallCoordinator::STEPS as [$p,$kind])$expected[$p]=$kind==='new'?'A':'M';ksort($expected,SORT_STRING);
if($d!==$expected){fwrite(STDERR,json_encode(['diff'=>$d,'expected'=>$expected],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);throw new RuntimeException('PACKAGE_FILE_SET_DIFFERS_FROM_THE_SOURCE_DIFF');}
if(count($expected)!==count(MediaInstallCoordinator::STEPS))throw new RuntimeException('PACKAGE_STEPS_HAVE_A_DUPLICATE_PATH');
foreach(array_keys($expected) as $p)if(preg_match('~^(database/|config/|composer\.|\.env|tests/)~',$p))throw new RuntimeException('PACKAGE_MIGRATION_CONFIG_COMPOSER_OR_TEST_CHANGE_STOP_AND_REPORT_'.$p);

$pins=['b1134-files.php'=>[$b1134.'/InstallFileOps.php','9dce8324bd4464a1689918775736bcd3a850f2b1c2b1b0ca7e130a9e486da12b'],'b1144-gates.php'=>[$b1144.'/LeanGates.php','7e62150fcad0884255ddb3c18063f5e2a23852a3eb0fdecb978c2955392ccf6e'],'b1144-probe.php'=>[$b1144.'/FreshnessProbe.php','3dbf4762e419d8df053fa7e642bd1b85df07d66dbffdb94fe0d8d5cdc1b50616'],'b1144-web.php'=>[$b1144.'/LeanWeb.php','54a04bc4dea81d56ad5fe0e6b25a141d4a86acf803f6df95f1c5d2b7adff6a73'],
    'b1145-env.php'=>[$b1145.'/EnvFlags.php','04a4bbd13b8aef346247d8641445ccb9f0a2d7fd3f26f7a03723724fc0132f69'],'b1145-app-state.php'=>[$b1145.'/AppState.php','642ddede76db8f1ea66bfc6a8d1ad74c894a70b665204cac4c96de17e97113a5'],'b1145-coordinator.php'=>[$b1145.'/MigrateUnclampCoordinator.php','46b159fbeab6c4f849dbb1a9291bbf13245b511ea58fd51646f26f28caa53fb9'],
    'b1146-probe.php'=>[$b1146.'/OverlayProbe.php','abcc9c64c6b9918d180afd387ec917a4924e1ce28c0099c0bbdae500e811c01f'],'b1146-app-check.php'=>[$b1146.'/AppCheck.php','68b4b2af5ad5a6399991adf474cfc753abf77a483fb12d2c1669a4b307ce2c2b'],
    'b1147-web.php'=>[$b1147.'/CatalogWeb.php','c4e4c0935b4c14e7fbee1089243f5be4784c5b25c5c0a62d11551d5dfa1461ce'],'b1147-app-check.php'=>[$b1147.'/AppCheck.php','278b03d1914580063a71f4f12c99e741376fbedb268e4570614dc3ccf3213b73'],'b1147-coordinator.php'=>[$b1147.'/CatalogOverlayCoordinator.php','d52bf4d475d3b3b1e6bd33fb6e766d24414b19b0fb3ee4dc0e9cdce59ed9cde9'],
    'b1148-web.php'=>[$b1148.'/GlassWeb.php','69bbdca104bfcbc62948e06a464fcaff55b5bf100a7ab513299fb19d00600c7c'],'b1148-app-check.php'=>[$b1148.'/AppCheck.php','0951b1ca3e9fcf6b7a2c722ce412903b593a9aa0b63dca5200f810a54c39e16a'],'b1148-coordinator.php'=>[$b1148.'/GlassOverlayCoordinator.php','332a13a5014845051aea3ffa68591b6075d798e94be5428e3cb288d222d3641b'],
    // The approved B1149 package members (head bf5ac045, Report1115 PASS): sha256 of the git blobs of tools/storefront/b1149 at bf5ac045. Assumption: the ZIP the owner ran was built from exactly these bytes.
    'b1149-web.php'=>[$b1149.'/StorefrontWeb.php','c090e207891fe0e70efaaa6c09ffb6eb00b44c475ccf4acc5790ac672a07c2ca'],'b1149-app-check.php'=>[$b1149.'/AppCheck.php','da100fde8e75fd0885e81366bc95d695f01bbedf31842dccec5fa5b2baa7aed3'],'b1149-coordinator.php'=>[$b1149.'/StorefrontInstallCoordinator.php','3742a8c1100f9d7bab9c4c489cc2e22d789a4d954fb47e01eeb112c52067e1d3']];
foreach($pins as $name=>[$file,$digest])if(hash_file('sha256',$file)!==$digest)throw new RuntimeException('PINNED_DEPENDENCY_DRIFT_'.$name);
$members=['b1150-installer.php'=>'operator-entry.php','b1150-coordinator.php'=>'MediaInstallCoordinator.php','b1150-web.php'=>'MediaWeb.php','b1150-app-check.php'=>'AppCheck.php','b1150-README-SR.md'=>'README-SR.md'];
$bytes=[];foreach($members as $name=>$file){$data=file_get_contents($tools.'/'.$file);if(!is_string($data)||str_contains($data,"\r"))throw new RuntimeException('PACKAGE_SOURCE_BYTES_'.$name);$bytes[$name]=$data;}
// The shipped tool code must not reach cPanel, reset OPcache globally, overlay PHP flags, migrate, seed, build any cache, POST or
// send a custom method, send mail, or write the DB switch or any row. route:clear and queue:restart are the only allowed artisan
// cache commands (coded once each, in refreshViews).
foreach($bytes as $name=>$data)if(str_ends_with($name,'.php')&&preg_match('~/usr/local/cpanel|uapi|DomainInfo|opcache_reset|php_flag|HostProcessGuard|--seed|migrate:fresh|migrate:rollback|migrate:refresh|migrate:reset|db:wipe|\'migrate\'|config:cache|view:cache|route:cache|public_storefront_open|->update\(|->insert\(|->upsert\(|->delete\(|->set\(|->put\(|Mail::|mail\(|CURLOPT_POST|CURLOPT_CUSTOMREQUEST|mail:test|app:send-test-mail~i',$data))throw new RuntimeException('PACKAGE_FORBIDDEN_MECHANISM_'.$name);
if(substr_count($bytes['b1150-coordinator.php'],"artisanOk(['route:clear'])")!==1||substr_count($bytes['b1150-coordinator.php'],"artisanOk(['queue:restart'])")!==1)throw new RuntimeException('PACKAGE_ARTISAN_CACHE_COMMANDS');

$overlay=[];$new=[];$old=[];$digests="n\tgroup\tkind\tpath\tpre_sha256_git_".substr($base,0,8)."\tpre_state_source\tpayload_sha256_git_".substr($source,0,8)."\n";
foreach(MediaInstallCoordinator::STEPS as $i=>[$path,$kind,$group]){
    $n=$i+1;$payload=$blob($source,$path);$pre=$kind==='modified'?$blob($base,$path):null;$new[$path]=$payload;$old[$path]=$pre;$liveBefore='ABSENT';
    if($kind==='new'&&($git(['cat-file','-e',$base.':'.CMS.$path],true)!==null||isset($byPath[$path])||isset($b1146Sha[$path])||isset($b1147Sha[$path])||isset($b1148Sha[$path])||isset($b1149Sha[$path])))throw new RuntimeException('PACKAGE_NEW_PATH_EXISTS_'.$n);
    if($kind==='modified'){$preSha=hash('sha256',(string)$pre);[$live,$liveBefore]=isset($b1149Sha[$path])?[$b1149Sha[$path],'B1149_PAYLOAD']:(isset($b1148Sha[$path])?[$b1148Sha[$path],'B1148_PAYLOAD']:(isset($b1147Sha[$path])?[$b1147Sha[$path],'B1147_PAYLOAD']:(isset($b1146Sha[$path])?[$b1146Sha[$path],'B1146_PAYLOAD']:[$byPath[$path]??null,'RUNTIME_MANIFEST'])));if($live!==$preSha)throw new RuntimeException('PACKAGE_PRE_STATE_NOT_LIVE_'.$n.'_'.$path);}
    if(str_contains($payload,"\r"))throw new RuntimeException('PACKAGE_PAYLOAD_CR_'.$n);
    $member=sprintf('b1150-payload-%02d.bin',$n);$bytes[$member]=$payload;$overlay[]=['n'=>$n,'path'=>$path,'kind'=>$kind,'group'=>$group,'payload'=>$member,'payloadSHA256'=>hash('sha256',$payload),'preSHA256'=>$pre===null?null:hash('sha256',$pre),'size'=>strlen($payload),'liveBefore'=>$liveBefore];
    $digests.=$n."\t".$group."\t".$kind."\t".$path."\t".($pre===null?'ABSENT':hash('sha256',$pre))."\t".$liveBefore."\t".hash('sha256',$payload)."\n";
}
// The B1149 markers hold in the final bytes and in the pre-state bytes (a file the payload does not touch is read at BASE, which is the live tree).
$layout='resources/views/public-shop/layout.blade.php';$css='public/assets/css/ald1n-public-storefront.css';$gallery='public/assets/css/ald1n-public-gallery.css';$catalogView='resources/views/public-shop/catalog.blade.php';$show='resources/views/public-shop/show.blade.php';
$final=static fn(string $p):string=>array_key_exists($p,$new)?$new[$p]:$blob($base,$p);$before=static fn(string $p):string=>$old[$p]??$blob($base,$p);
foreach([['layout',$layout],['css',$css],['gallery',$gallery],['catalog',$catalogView],['catalog',$show]] as [$k,$p])if(!str_contains($final($p),MARKERS[$k])||!str_contains($before($p),MARKERS[$k]))throw new RuntimeException('PACKAGE_MARKER_'.strtoupper($k).'_'.$p);
$version=static function(string $text,string $file):string{if(preg_match_all('~'.preg_quote($file,'~').'\?v=([0-9]{1,12})[\'"]~',$text,$v)!==1)throw new RuntimeException('PACKAGE_CSS_VERSION');return $v[1][0];};
$cssVersion=$version($final($layout),'ald1n-public-storefront.css');$oldCssVersion=$version($before($layout),'ald1n-public-storefront.css');
// The gallery stylesheet link is not touched by B1150: one and the same ?v= in both views and in both states.
$galleryVersion=$version($final($catalogView),'ald1n-public-gallery.css');if($galleryVersion!==$version($final($show),'ald1n-public-gallery.css')||$galleryVersion!==$version($before($catalogView),'ald1n-public-gallery.css')||$galleryVersion!==$version($before($show),'ald1n-public-gallery.css'))throw new RuntimeException('PACKAGE_GALLERY_VERSION_DIFFERS');
// B1150_FILL: the dependency facts behind the install order, in the shipped bytes (needle present in the new bytes, absent from the old where the file is modified),
// path => [needles]. Example: 'routes/web.php'=>["->name('<MEDIA_ROUTE_NAME>')"], a controller class the route needs, a service method a view calls.
$facts=[];
foreach($facts as $p=>$needles)foreach($needles as $needle)if(!isset($new[$p])||!str_contains($new[$p],$needle)||(($old[$p]??null)!==null&&str_contains($old[$p],$needle)))throw new RuntimeException('PACKAGE_ORDER_FACT_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/','_',$p.'_'.$needle)));
// The payload must contain the route name the coordinator and the CLI helper look for.
$routeFile='routes/web.php';if(isset($new[$routeFile])&&!str_contains($new[$routeFile],"'".MediaInstallCoordinator::MEDIA_ROUTE_NAME."'")&&!str_contains($new[$routeFile],'"'.MediaInstallCoordinator::MEDIA_ROUTE_NAME.'"'))throw new RuntimeException('PACKAGE_MEDIA_ROUTE_NAME_NOT_IN_ROUTES_WEB');
if(substr_count($bytes['b1150-app-check.php'],"const MEDIA_ROUTE_NAME='".MediaInstallCoordinator::MEDIA_ROUTE_NAME."';")!==1)throw new RuntimeException('PACKAGE_APP_CHECK_ROUTE_NAME_DIFFERS_FROM_THE_COORDINATOR');
$files=[];foreach($pins as $name=>[,$digest])$files[$name]=$digest;foreach($bytes as $name=>$data)$files[$name]=hash('sha256',$data);ksort($files,SORT_STRING);
$common=['schemaVersion'=>1,'batch'=>'B1150','sourceCommit'=>$source,'baseCommit'=>$base,'mainCommit'=>$main,'storefrontCommit'=>$store,'storefrontBaseCommit'=>$storeBase,'storefrontMainCommit'=>$storeMain,'glassCommit'=>$glass,'catalogCommit'=>$catalog,'legalCommit'=>$legal,'b1146BaseCommit'=>$b1146Base,'installedSourceCommit'=>INSTALLED,'helperSource'=>$helper];
$bytes['b1150-package.json']=json_encode($common+['overlay'=>$overlay,'b1146Overlay'=>$b1146Overlay,'b1147Overlay'=>$b1147Overlay,'b1148Overlay'=>$b1148Overlay,'b1149Overlay'=>$b1149Overlay,'markers'=>MARKERS,'cssVersion'=>$cssVersion,'oldCssVersion'=>$oldCssVersion,'galleryVersion'=>$galleryVersion,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
$check=json_decode($bytes['b1150-package.json'],true,64,JSON_THROW_ON_ERROR);MediaInstallCoordinator::plan($check,'/home/icaffeco/ald1n-project/incoming');MediaInstallCoordinator::b1146Plan($check);MediaInstallCoordinator::b1147Plan($check);MediaInstallCoordinator::b1148Plan($check);MediaInstallCoordinator::b1149Plan($check);MediaInstallCoordinator::markers($check);
$bytes['b1150-provenance.json']=json_encode($common+['author'=>'Ald1n <https://ald1n.com>','workflowRun'=>getenv('GITHUB_RUN_ID')?:null,'workflowRepository'=>getenv('GITHUB_REPOSITORY')?:null,
    'predecessor'=>['report'=>'docs/operations/1115-B1149-INSTALLER-<date>-<time>.md','bytesPinned'=>false,'required'=>['B1149_FINAL'=>MediaInstallCoordinator::B1149_PASS,'EXIT'=>'0','B1149_PHASE'=>'INSTALLED_PASS','b1149State'=>'INSTALLED_PASS'],'ignored'=>'1114-B1149-INSTALLER-*.md (the preflight HOLD of the same tool)'],
    'runtimeManifestSHA256'=>MediaInstallCoordinator::MANIFEST_SHA,'treeFilesBefore'=>MediaInstallCoordinator::B1149_TREE_FILES,'treeFilesAfter'=>MediaInstallCoordinator::installedTreeFiles(),'configAfterB1145SHA256'=>MediaInstallCoordinator::CONFIG_SHA,'excludedFromTheSourceDiff'=>$excluded,'installOrder'=>array_map(static fn($s)=>$s[2].' '.$s[1].' '.$s[0],MediaInstallCoordinator::STEPS),'preStateSource'=>array_map(static fn($o)=>$o['path'].' '.$o['liveBefore'],$overlay),'pinnedNotRedelivered'=>array_map(fn($p)=>$p[1],$pins),
    'cloudflare'=>['change'=>'NONE','purge'=>'NONE_NEEDED','newCssUrl'=>'https://cms.ald1n.com'.MediaInstallCoordinator::CSS_PATH.'?v='.$cssVersion,'oldCssUrl'=>'https://cms.ald1n.com'.MediaInstallCoordinator::CSS_PATH.'?v='.$oldCssVersion,'mediaRoute'=>'https://cms.ald1n.com'.MediaInstallCoordinator::MEDIA_PATH_PREFIX.'{image}/{rendition}?v=&s= (signed, session-less, Cache-Control private)'],
    'newRoutes'=>['GET '.MediaInstallCoordinator::MEDIA_PATH_PREFIX.'{image}/{rendition} '.MediaInstallCoordinator::MEDIA_ROUTE_NAME],
    'neverDone'=>['storefrontDbSwitchWrite','merchantApprovalWrite','smtpSettingsReadOrWrite','testMail','migration','composer','routeCacheBuild','envEdit','configChange','opcacheGlobalReset','viewCache','login','productOrCartWrite','themePost','retainedDirectoryModeChange'],'operatorCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1150-installer.sh','rollbackCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1150-installer.sh --rollback'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
ksort($bytes,SORT_STRING);$checksums='';foreach($bytes as $name=>$data)$checksums.=hash('sha256',$data).'  '.$name."\n";
$wrapper=file_get_contents($tools.'/operator-wrapper.sh');if(!is_string($wrapper)||str_contains($wrapper,"\r")||substr_count($wrapper,"\nSELF_SHA=__SELF_SHA__\n")!==1||substr_count($wrapper,"\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n")!==1)throw new RuntimeException('WRAPPER_TEMPLATE');
$wrapper=str_replace("\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n","\nCHECKSUMS_SHA=".hash('sha256',$checksums)."\n",$wrapper);$wrapper=str_replace("\nSELF_SHA=__SELF_SHA__\n","\nSELF_SHA=".hash('sha256',$wrapper)."\n",$wrapper);
$bytes['b1150-package.sha256']=$checksums;$bytes['b1150-installer.sh']=$wrapper;ksort($bytes,SORT_STRING);
file_put_contents($out.'/b1150-digests.tsv',$digests);
$zipPath=$out.'/b1150-operator.zip';if(file_exists($zipPath))unlink($zipPath);$zip=new ZipArchive();if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('ZIP_CREATE');
foreach($bytes as $name=>$data){$member='incoming/'.$name;$zip->addFromString($member,$data);$zip->setExternalAttributesName($member,ZipArchive::OPSYS_UNIX,(str_ends_with($name,'.sh')?0100755:0100644)<<16);$zip->setMtimeName($member,1762732800);}
if(!$zip->close())throw new RuntimeException('ZIP_CLOSE');
$zipSHA=hash_file('sha256',$zipPath);file_put_contents($zipPath.'.sha256',$zipSHA.'  b1150-operator.zip'."\n");
echo json_encode(['zip'=>basename($zipPath),'zipSHA256'=>$zipSHA,'zipBytes'=>filesize($zipPath),'members'=>array_map(fn($n)=>'incoming/'.$n,array_keys($bytes)),'checksumsSHA256'=>hash('sha256',$checksums),'wrapperSHA256'=>hash('sha256',$wrapper),'cssVersion'=>$cssVersion,'oldCssVersion'=>$oldCssVersion,'galleryVersion'=>$galleryVersion,'treeFilesAfter'=>MediaInstallCoordinator::installedTreeFiles(),'excluded'=>$excluded,'overlay'=>$overlay,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
