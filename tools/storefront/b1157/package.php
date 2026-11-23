<?php
declare(strict_types=1);
use B1157InstallCoordinator as DR;
use CommissionsInstallCoordinator as DR56;
use DashboardReportsInstallCoordinator as DR55;
use AdminShellInstallCoordinator as AS54;
/**
 * Builds the B1157 operator ZIP with root-relative incoming/ members only. Everything is read from git at build time, never from a working tree: each payload file is git show <SOURCE_COMMIT>:<path> (origin/main 94de4be5, the merge
 * of PR 195, 200, 201, 202 and 209: commissions, orders and dashboard UI), the pre-state is git show BASE_COMMIT:<path> (d2c07f62, the B1155 payload commit: its apps/cms/current without tests is the live tree after Report1121), and the ten
 * earlier overlays (B1146: eabe16af over 77a3efea; B1147: 764bf513 over eabe16af; B1148: 56082845 over 764bf513; B1149: 7e748158 over eca7755c; B1150: 14e628ba over 7e748158; B1151: 43d3ab6e and ae00c5f6 over 14e628ba;
 * B1152: 8505b110 and cfe97253 over 7e22afd4; B1153: d760bb2c over af1e46a3; B1154: ad901a58 over 482d2fa5; B1155: d2c07f62 over ad901a58) turn the 8531-file runtime manifest into the live 8583-file tree. The payload is shipped as
 * flat incoming/b1157-payload-NN.bin members, so unzip never writes into the live CMS tree. The build fails unless:
 * - BASE is an ancestor of SOURCE, the B1155 payload commit IS BASE, and BASE and SOURCE are different commits;
 * - git diff --name-status BASE SOURCE restricted to apps/cms/current, minus apps/cms/current/tests/ and apps/cms/current/docs/, is exactly the coded STEPS set (69 files: 13 new, 56 modified) plus the two DELETED files, which are exactly
 *   the coded ORPHANS (they stay on the host: nothing is deleted; their bytes at BASE are their B1155 payload digests; nothing in the SOURCE tree includes them); nothing in that diff is under config/, composer.* or .env, under
 *   database/ only the two migration files (new), under routes/ only web.php and api.php, under bin/ only the three tools;
 * - every payload and pre-state digest equals the digest coded in B1157InstallCoordinator::DIGESTS (the reviewers' sha256 of the raw blobs);
 * - the B1146 to B1154 file sets equal the pinned coordinators' lists, each pre digest equals the live digest before it (manifest, or the earlier overlay's payload) and each new path is absent before; the B1155 file set, kinds and
 *   digests equal the pinned B1155 STEPS and DIGESTS (class DashboardReportsInstallCoordinator of the owner's b1155-coordinator.php, whose package checksums hash to the CHECKSUMS_SHA256 of Report1121);
 * - every B1157 pre digest equals the LIVE digest by the independent chain (B1155 payload, else B1154, ... else the runtime manifest; the source is printed per file), and each new path is absent everywhere;
 * - the backward compatibility facts hold in the shipped bytes: no public method of a modified class disappears, nothing at BASE names a new file or route, the routes files only gain the nine routes, and the order facts hold (routes after the
 *   controllers they name, every view that calls a new route after the routes, the provider after the calculator it binds, `use` and @include edges);
 * - the B1149 markers are in the bytes at BASE (the storefront is not in this payload), the B1155 markers are in the bytes at BASE and in the final bytes.
 * The B1134-B1155 helpers already on the host are verified by digest (package.json), not redelivered.
 * Besides the ZIP it writes b1157-digests.tsv (path, kind, group, source, pre digest, pre-state source, payload digest) next to it.
 * Usage: php package.php <repo root> <b1157 dir> <b1134 dir> <b1144 dir> <b1145 dir> <b1146 dir> <b1147 dir> <b1148 dir> <b1149 dir> <b1150 dir> <b1151 dir> <b1152 dir> <b1153 dir> <b1154 dir> <b1155 dir> <runtime-manifest.json> <output dir> <helper source commit> <source commit>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$repo,$tools,$b1134,$b1144,$b1145,$b1146,$b1147,$b1148,$b1149,$b1150,$b1151,$b1152,$b1153,$b1154,$b1155,$b1156,$manifestPath,$out,$helper,$srcArg]=$argv+array_fill(0,21,null);
if(!is_dir((string)$repo.'/.git')&&!is_file((string)$repo.'/.git'))throw new RuntimeException('PACKAGE_REPO');if(!is_dir((string)$tools)||!is_dir((string)$b1152)||!is_dir((string)$b1153)||!is_dir((string)$b1154)||!is_dir((string)$b1155)||!is_dir((string)$b1156)||!is_file((string)$manifestPath)||!preg_match('/^[a-f0-9]{40}$/D',(string)$helper)||!preg_match('/^[a-f0-9]{40}$/D',(string)$srcArg))throw new RuntimeException('PACKAGE_ARGUMENTS');if(!is_dir($out)&&!mkdir($out,0755,true))throw new RuntimeException('PACKAGE_OUTPUT');
require $b1134.'/InstallFileOps.php';require $b1144.'/FreshnessProbe.php';require $b1144.'/LeanGates.php';require $b1144.'/LeanWeb.php';require $b1145.'/EnvFlags.php';require $b1145.'/MigrateUnclampCoordinator.php';require $b1146.'/OverlayProbe.php';require $b1146.'/OverlayWeb.php';require $b1146.'/OverlayCoordinator.php';require $b1147.'/CatalogWeb.php';require $b1147.'/CatalogOverlayCoordinator.php';require $b1148.'/GlassWeb.php';require $b1148.'/GlassOverlayCoordinator.php';require $b1149.'/StorefrontInstallCoordinator.php';require $b1150.'/MediaWeb.php';require $b1150.'/MediaInstallCoordinator.php';require $b1151.'/LegalAccountInstallCoordinator.php';require $b1152.'/OriginPinInstallCoordinator.php';require $b1153.'/LoadProbe.php';require $b1153.'/LogoCatalogInstallCoordinator.php';require $b1154.'/AdminShellInstallCoordinator.php';require $b1155.'/DashboardReportsInstallCoordinator.php';require $b1156.'/CommissionsInstallCoordinator.php';require $tools.'/Payload.php';require $tools.'/B1157InstallCoordinator.php';
if(DR::STEPS===[])throw new RuntimeException('B1157_PAYLOAD_NOT_FILLED_IN');
const CMS='apps/cms/current/';const B1146_BASE='77a3efea';const INSTALLED='ef3f927b77ba573881c2b6dba341732c417e6725';
const MARKERS=DR::MARKERS;
$base=DR::BASE_COMMIT;$b1156Base=DR::B1155_COMMIT;$payloadCommit=DR::SOURCE_COMMIT;$b1155Base=DR::B1155_BASE_COMMIT;$b1155Commit=DR::B1155_COMMIT;$b1154Base=DR::B1154_BASE_COMMIT;$b1154Commit=DR::B1154_COMMIT;$b1153Base=DR::B1153_BASE_COMMIT;$b1153Commit=DR::B1153_COMMIT;$b1152Base=DR::B1152_BASE_COMMIT;$b1152Url=DR::B1152_URL_COMMIT;$b1152Pdf=DR::B1152_PDF_COMMIT;$b1152Main=DR::B1152_MAIN_COMMIT;$b1151Email=DR::B1151_EMAIL_COMMIT;$b1151Throttle=DR::B1151_THROTTLE_COMMIT;$b1150Source=DR::B1150_COMMIT;$store=DR::STOREFRONT_COMMIT;$storeBase=DR::STOREFRONT_BASE_COMMIT;$storeMain=DR::STOREFRONT_MAIN_COMMIT;$glass=DR::GLASS_COMMIT;$catalog=DR::CATALOG_COMMIT;$legal=DR::LEGAL_COMMIT;
// The workflow passes the source it reviewed; it must be the commit the coordinator is pinned to.
if($srcArg!==$payloadCommit)throw new RuntimeException('PACKAGE_SOURCE_ARGUMENT_DIFFERS_FROM_THE_PINNED_SOURCE');
$git=static function(array $args,bool $allowFail=false)use($repo):?string{$p=proc_open(array_merge(['git','-C',$repo,'-c','core.autocrlf=false'],$args),[1=>['pipe','w'],2=>['pipe','w']],$pipes);$o=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0){if($allowFail)return null;throw new RuntimeException('PACKAGE_GIT_'.$args[0]);}return (string)$o;};
$blob=static fn(string $commit,string $path):string=>(string)$git(['show',$commit.':'.CMS.$path]);
foreach([$payloadCommit,$base,$b1154Commit,$b1154Base,$b1153Commit,$b1153Base,$b1152Url,$b1152Pdf,$b1152Base,$b1152Main,$b1151Email,$b1151Throttle,$b1150Source,$store,$storeBase,$storeMain,$glass,$catalog,$legal,INSTALLED,$b1155Commit,$b1155Base] as $c)if(trim((string)$git(['rev-parse','--verify',$c.'^{commit}']))!==$c)throw new RuntimeException('PACKAGE_COMMIT_'.substr($c,0,8));
if($legal!==OverlayCoordinator::SOURCE_COMMIT||$catalog!==CatalogOverlayCoordinator::SOURCE_COMMIT||$legal!==CatalogOverlayCoordinator::BASE_COMMIT||$glass!==GlassOverlayCoordinator::SOURCE_COMMIT||$catalog!==GlassOverlayCoordinator::BASE_COMMIT||$legal!==GlassOverlayCoordinator::MAIN_COMMIT)throw new RuntimeException('PACKAGE_CHAIN_COMMITS_DIFFER_FROM_THE_PINNED_COORDINATORS');
if($store!==StorefrontInstallCoordinator::SOURCE_COMMIT||$storeBase!==StorefrontInstallCoordinator::BASE_COMMIT||$storeMain!==StorefrontInstallCoordinator::MAIN_COMMIT||$glass!==StorefrontInstallCoordinator::GLASS_COMMIT||$catalog!==StorefrontInstallCoordinator::CATALOG_COMMIT||$legal!==StorefrontInstallCoordinator::LEGAL_COMMIT)throw new RuntimeException('PACKAGE_B1149_COMMITS_DIFFER_FROM_THE_PINNED_B1149_COORDINATOR');
if($b1150Source!==MediaInstallCoordinator::SOURCE_COMMIT||$store!==MediaInstallCoordinator::BASE_COMMIT||MediaInstallCoordinator::MEDIA_ROUTE_NAME!==DR::MEDIA_ROUTE_NAME)throw new RuntimeException('PACKAGE_B1150_COMMITS_DIFFER_FROM_THE_PINNED_B1150_COORDINATOR');
if($b1151Email!==LegalAccountInstallCoordinator::EMAIL_COMMIT||$b1151Throttle!==LegalAccountInstallCoordinator::THROTTLE_COMMIT||$b1150Source!==LegalAccountInstallCoordinator::BASE_COMMIT)throw new RuntimeException('PACKAGE_B1151_COMMITS_DIFFER_FROM_THE_PINNED_B1151_COORDINATOR');
if($b1152Url!==OriginPinInstallCoordinator::URL_COMMIT||$b1152Pdf!==OriginPinInstallCoordinator::PDF_COMMIT||$b1152Base!==OriginPinInstallCoordinator::BASE_COMMIT||$b1152Main!==OriginPinInstallCoordinator::MAIN_COMMIT||$b1151Email!==OriginPinInstallCoordinator::B1151_EMAIL_COMMIT||$b1151Throttle!==OriginPinInstallCoordinator::B1151_THROTTLE_COMMIT||$b1150Source!==OriginPinInstallCoordinator::B1150_COMMIT||DR::B1152_PASS!==OriginPinInstallCoordinator::FINAL_PASS||DR55::ROUTES!==OriginPinInstallCoordinator::ROUTES||DR::EXPECTED_APP_URL!==OriginPinInstallCoordinator::EXPECTED_APP_URL)throw new RuntimeException('PACKAGE_B1152_COMMITS_DIFFER_FROM_THE_PINNED_B1152_COORDINATOR');
if($b1153Commit!==LogoCatalogInstallCoordinator::SOURCE_COMMIT||$b1153Base!==LogoCatalogInstallCoordinator::BASE_COMMIT||DR::B1153_PASS!==LogoCatalogInstallCoordinator::FINAL_PASS||DR55::ROUTES!==LogoCatalogInstallCoordinator::ROUTES||DR::EXPECTED_APP_URL!==LogoCatalogInstallCoordinator::EXPECTED_APP_URL||DR::MANIFEST_SHA!==LogoCatalogInstallCoordinator::MANIFEST_SHA||DR::B1153_TREE_FILES!==LogoCatalogInstallCoordinator::installedTreeFiles()||$b1152Url!==LogoCatalogInstallCoordinator::B1152_URL_COMMIT||$b1152Pdf!==LogoCatalogInstallCoordinator::B1152_PDF_COMMIT||$b1152Base!==LogoCatalogInstallCoordinator::B1152_BASE_COMMIT)throw new RuntimeException('PACKAGE_B1153_COMMITS_DIFFER_FROM_THE_PINNED_B1153_COORDINATOR');
if($b1154Commit!==AS54::SOURCE_COMMIT||$b1154Base!==AS54::BASE_COMMIT||$b1153Commit!==AS54::B1153_COMMIT||$b1153Base!==AS54::B1153_BASE_COMMIT||DR::B1154_PASS!==AS54::FINAL_PASS||DR55::ROUTES!==AS54::ROUTES||DR::EXPECTED_APP_URL!==AS54::EXPECTED_APP_URL||DR::MANIFEST_SHA!==AS54::MANIFEST_SHA||DR::B1154_TREE_FILES!==AS54::installedTreeFiles()||DR::B1154_FILES!==count(AS54::STEPS)||$b1155Base!==$b1154Commit)throw new RuntimeException('PACKAGE_B1154_COMMITS_DIFFER_FROM_THE_PINNED_B1154_COORDINATOR');
if($b1155Commit!==DR55::SOURCE_COMMIT||$b1155Base!==DR55::BASE_COMMIT||DR::B1155_PASS!==DR55::FINAL_PASS||DR55::ROUTES_AFTER!==DR56::ROUTES||DR::B1155_TREE_FILES!==DR55::installedTreeFiles()||DR::B1155_FILES!==count(DR55::STEPS)||DR56::BASE_COMMIT!==$b1155Commit||DR::EXPECTED_APP_URL!==DR55::EXPECTED_APP_URL||DR::MANIFEST_SHA!==DR55::MANIFEST_SHA)throw new RuntimeException('PACKAGE_B1155_COMMITS_DIFFER_FROM_THE_PINNED_B1155_COORDINATOR');
if($base!==DR56::SOURCE_COMMIT||DR::B1156_PASS!==DR56::FINAL_PASS||DR::ROUTES!==DR56::ROUTES_AFTER||DR::B1156_TREE_FILES!==DR56::installedTreeFiles()||DR::B1156_FILES!==count(DR56::STEPS)||DR::EXPECTED_APP_URL!==DR56::EXPECTED_APP_URL||DR::MANIFEST_SHA!==DR56::MANIFEST_SHA)throw new RuntimeException('PACKAGE_B1156_COMMITS_DIFFER_FROM_THE_PINNED_B1156_COORDINATOR');
if($payloadCommit===$base||$b1154Base===$base||$b1154Base===$b1153Commit||$b1153Commit===$b1153Base||$b1152Url===$b1152Pdf||$b1152Url===$b1152Base||$b1152Pdf===$b1152Base)throw new RuntimeException('PACKAGE_HEADS_NOT_DISTINCT');
foreach([[$legal,$catalog],[$catalog,$glass],[$storeBase,$store],[$store,$b1150Source],[$b1150Source,$b1151Email],[$b1150Source,$b1151Throttle],[$b1152Base,$b1152Url],[$b1152Base,$b1152Pdf],[$b1152Url,$b1153Base],[$b1152Pdf,$b1153Base],[$b1152Base,$b1153Base],[$b1153Base,$b1153Commit],[$b1153Commit,$b1154Base],[$b1154Base,$b1154Commit],[$b1154Commit,$b1155Base],[$b1155Base,$b1155Commit],[$b1155Commit,$base],[$base,$payloadCommit]] as [$a,$b])if($git(['merge-base','--is-ancestor',$a,$b],true)===null)throw new RuntimeException('PACKAGE_NOT_AN_ANCESTOR_'.substr($a,0,8).'_'.substr($b,0,8));
if($git(['diff','--quiet',$glass,$storeBase,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_B1149_BASE_TREE_DIFFERS_FROM_THE_B1148_PAYLOAD_HEAD');
$b1146Base=trim((string)$git(['rev-parse','--verify',B1146_BASE.'^{commit}']));if(!preg_match('/^77a3efea[a-f0-9]{32}$/D',$b1146Base))throw new RuntimeException('PACKAGE_B1146_BASE');
if($git(['diff','--quiet',INSTALLED,$b1146Base,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_INSTALLED_TREE_DIFFERS_FROM_B1146_BASE');
$diff=static function(string $from,string $to,?string $scope='apps/cms/current')use($git):array{$d=[];$args=['diff','--no-renames','--name-status',$from,$to];if($scope!==null)array_push($args,'--',$scope);foreach(explode("\n",trim((string)$git($args))) as $line){if($line==='')continue;if(!preg_match('~^([AMD])\t(\S+)$~D',$line,$m))throw new RuntimeException('PACKAGE_DIFF_LINE');$d[$m[2]]=$m[1];}ksort($d,SORT_STRING);return $d;};
$cms=static function(array $d):array{$o=[];foreach($d as $p=>$k){if(!str_starts_with($p,CMS)||$k==='D')throw new RuntimeException('PACKAGE_DIFF_OUTSIDE_CMS_OR_DELETE_'.$p);$o[substr($p,strlen(CMS))]=$k;}return $o;};
$manifest=json_decode((string)file_get_contents($manifestPath),true,512,JSON_THROW_ON_ERROR);if(hash_file('sha256',$manifestPath)!==DR::MANIFEST_SHA||count($manifest['files'])!==DR::MANIFEST_FILES)throw new RuntimeException('PACKAGE_RUNTIME_MANIFEST');$byPath=[];foreach($manifest['files'] as $f)$byPath[$f['path']]=$f['sha256'];

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
// B1150 (14e628ba over 7e748158; tests, bin, workflows and docs excluded), as the approved B1150 build computes it.
$whole=$diff($store,$b1150Source,null);foreach(array_keys($whole) as $p)if(str_starts_with($p,CMS.'tests/')||str_starts_with($p,CMS.'bin/')||str_starts_with($p,'.github/workflows/')||str_starts_with($p,'docs/'))unset($whole[$p]);
$d1150=$cms($whole);$e=[];foreach(MediaInstallCoordinator::STEPS as [$p,$kind])$e[$p]=$kind==='new'?'A':'M';ksort($e,SORT_STRING);if($d1150!==$e)throw new RuntimeException('PACKAGE_B1150_FILE_SET');
$b1150Overlay=[];$b1150Sha=[];
foreach(MediaInstallCoordinator::STEPS as [$p,$kind]){
    $sha=hash('sha256',$blob($b1150Source,$p));$pre=$kind==='modified'?hash('sha256',$blob($store,$p)):null;
    if($kind==='modified'&&($b1149Sha[$p]??($b1148Sha[$p]??($b1147Sha[$p]??($b1146Sha[$p]??($byPath[$p]??null)))))!==$pre)throw new RuntimeException('PACKAGE_B1150_PRE_NOT_LIVE');if($kind==='new'&&(isset($byPath[$p])||isset($b1146Sha[$p])||isset($b1147Sha[$p])||isset($b1148Sha[$p])||isset($b1149Sha[$p])))throw new RuntimeException('PACKAGE_B1150_NEW_ALREADY_LIVE');
    $b1150Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1150Sha[$p]=$sha;
}

// B1151 (the pinned B1151 STEPS: 43d3ab6e and ae00c5f6 over 14e628ba; tests, bin, workflows and docs excluded), as the approved B1151 build computes it.
$expected1151=['E'=>[],'T'=>[]];foreach(LegalAccountInstallCoordinator::STEPS as [$p,$kind,,$src])$expected1151[$src][$p]=$kind==='new'?'A':'M';foreach($expected1151 as &$x)ksort($x,SORT_STRING);unset($x);
$perHead1151=[];foreach(['E'=>$b1151Email,'T'=>$b1151Throttle] as $key=>$head){$whole=$diff($b1150Source,$head,null);foreach(array_keys($whole) as $p)if(str_starts_with($p,CMS.'tests/')||str_starts_with($p,CMS.'bin/')||str_starts_with($p,'.github/workflows/')||str_starts_with($p,'docs/'))unset($whole[$p]);$perHead1151[$key]=$cms($whole);}
if($perHead1151!==$expected1151)throw new RuntimeException('PACKAGE_B1151_FILE_SET');
$b1151Overlay=[];$b1151Sha=[];
foreach(LegalAccountInstallCoordinator::STEPS as [$p,$kind,,$src]){
    $sha=hash('sha256',$blob(LegalAccountInstallCoordinator::sourceCommit($src),$p));$pre=$kind==='modified'?hash('sha256',$blob($b1150Source,$p)):null;
    if($kind==='modified'&&($b1150Sha[$p]??($b1149Sha[$p]??($b1148Sha[$p]??($b1147Sha[$p]??($b1146Sha[$p]??($byPath[$p]??null))))))!==$pre)throw new RuntimeException('PACKAGE_B1151_PRE_NOT_LIVE');if($kind==='new'&&(isset($byPath[$p])||isset($b1146Sha[$p])||isset($b1147Sha[$p])||isset($b1148Sha[$p])||isset($b1149Sha[$p])||isset($b1150Sha[$p])))throw new RuntimeException('PACKAGE_B1151_NEW_ALREADY_LIVE');
    // The code base is the live tree: its blob of every B1151 file is the B1151 payload.
    if(hash('sha256',$blob($b1152Base,$p))!==$sha)throw new RuntimeException('PACKAGE_B1151_PAYLOAD_NOT_IN_THE_CODE_BASE_'.$p);
    $b1151Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1151Sha[$p]=$sha;
}

// B1152 (the pinned B1152 STEPS: 8505b110 and cfe97253 over 7e22afd4; tests, bin, workflows and docs excluded), as the approved B1152 build computes it.
$drop=static fn(string $p):bool=>str_starts_with($p,CMS.'tests/')||str_starts_with($p,CMS.'bin/')||str_starts_with($p,'.github/workflows/')||str_starts_with($p,'docs/');
$expected1152=['U'=>[],'P'=>[]];foreach(OriginPinInstallCoordinator::STEPS as [$p,$kind,,$s1])$expected1152[$s1][$p]=$kind==='new'?'A':'M';foreach($expected1152 as &$x)ksort($x,SORT_STRING);unset($x);
$perHead1152=[];foreach(['U'=>$b1152Url,'P'=>$b1152Pdf] as $key=>$head){$whole=$diff($b1152Base,$head,null);foreach(array_keys($whole) as $p)if($drop($p))unset($whole[$p]);$perHead1152[$key]=$cms($whole);}
if($perHead1152!==$expected1152)throw new RuntimeException('PACKAGE_B1152_FILE_SET');
$b1152Overlay=[];$b1152Sha=[];
foreach(OriginPinInstallCoordinator::STEPS as [$p,$kind,,$s1]){
    $sha=hash('sha256',$blob(OriginPinInstallCoordinator::sourceCommit($s1),$p));$pre=$kind==='modified'?hash('sha256',$blob($b1152Base,$p)):null;
    if($kind==='modified'&&($b1151Sha[$p]??($b1150Sha[$p]??($b1149Sha[$p]??($b1148Sha[$p]??($b1147Sha[$p]??($b1146Sha[$p]??($byPath[$p]??null)))))))!==$pre)throw new RuntimeException('PACKAGE_B1152_PRE_NOT_LIVE');if($kind==='new'&&(isset($byPath[$p])||isset($b1146Sha[$p])||isset($b1147Sha[$p])||isset($b1148Sha[$p])||isset($b1149Sha[$p])||isset($b1150Sha[$p])||isset($b1151Sha[$p])))throw new RuntimeException('PACKAGE_B1152_NEW_ALREADY_LIVE');
    // The digests the B1152 coordinator that the owner ran has coded, and the B1153 code base (af1e46a3, the live tree after Report1118): its blob of every B1152 file is the B1152 payload.
    if(OriginPinInstallCoordinator::DIGESTS[$p]!==[$pre,$sha])throw new RuntimeException('PACKAGE_B1152_DIGEST_NOT_THE_PINNED_ONE_'.$p);
    if(hash('sha256',$blob($b1153Base,$p))!==$sha)throw new RuntimeException('PACKAGE_B1152_PAYLOAD_NOT_IN_PRODUCTION_'.$p);
    $b1152Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1152Sha[$p]=$sha;
}
// Production after B1152 is the B1153 code base af1e46a3: the diff from the B1152 code base to it (apps/cms/current, minus tests and bin) is exactly the nine B1152 files and nothing else.
$prod=$diff($b1152Base,$b1153Base);foreach(array_keys($prod) as $p)if($drop($p))unset($prod[$p]);$prod=$cms($prod);$want=$expected1152['U']+$expected1152['P'];ksort($want,SORT_STRING);
if($prod!==$want)throw new RuntimeException('PACKAGE_B1153_BASE_IS_NOT_THE_B1151_TREE_PLUS_THE_B1152_FILES');

// B1153 (the pinned B1153 STEPS: d760bb2c over af1e46a3, the owner's package of Report1119): the file set is the CMS diff minus tests and bin, as the B1153 build computed it; kinds and digests equal the pinned
// B1153 coordinator (its STEPS and DIGESTS, read from the class the owner ran), each pre digest equals the live digest before it and each new path is absent before.
$whole=$diff($b1153Base,$b1153Commit);foreach(array_keys($whole) as $p)if($drop($p))unset($whole[$p]);$d1153=$cms($whole);$e=[];foreach(LogoCatalogInstallCoordinator::STEPS as [$p,$kind])$e[$p]=$kind==='new'?'A':'M';ksort($e,SORT_STRING);
if($d1153!==$e)throw new RuntimeException('PACKAGE_B1153_FILE_SET');
$b1153Overlay=[];$b1153Sha=[];
foreach(LogoCatalogInstallCoordinator::STEPS as [$p,$kind]){
    $sha=hash('sha256',$blob($b1153Commit,$p));$pre=$kind==='modified'?hash('sha256',$blob($b1153Base,$p)):null;
    if($kind==='modified'&&($b1152Sha[$p]??($b1151Sha[$p]??($b1150Sha[$p]??($b1149Sha[$p]??($b1148Sha[$p]??($b1147Sha[$p]??($b1146Sha[$p]??($byPath[$p]??null))))))))!==$pre)throw new RuntimeException('PACKAGE_B1153_PRE_NOT_LIVE');if($kind==='new'&&(isset($byPath[$p])||isset($b1146Sha[$p])||isset($b1147Sha[$p])||isset($b1148Sha[$p])||isset($b1149Sha[$p])||isset($b1150Sha[$p])||isset($b1151Sha[$p])||isset($b1152Sha[$p])))throw new RuntimeException('PACKAGE_B1153_NEW_ALREADY_LIVE');
    // The digests the B1153 coordinator that the owner ran has coded.
    if(LogoCatalogInstallCoordinator::DIGESTS[$p]!==[$pre,$sha])throw new RuntimeException('PACKAGE_B1153_DIGEST_NOT_THE_PINNED_ONE_'.$p);
    $b1153Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1153Sha[$p]=$sha;
}
// B1154 (the pinned B1154 STEPS: ad901a58 over 482d2fa5, the owner's package of Report1120): the file set is the CMS diff minus the CMS tests (bin/static-check.php included: it is a file of the runtime manifest); kinds and digests equal the pinned
// B1154 coordinator (its STEPS and DIGESTS, read from the class the owner ran), each pre digest equals the live digest before it and each new path is absent before.
$whole=$diff($b1154Base,$b1154Commit);foreach(array_keys($whole) as $p)if(str_starts_with($p,CMS.'tests/'))unset($whole[$p]);$d1154=$cms($whole);$e=[];foreach(AS54::STEPS as [$p,$kind])$e[$p]=$kind==='new'?'A':'M';ksort($e,SORT_STRING);
if($d1154!==$e)throw new RuntimeException('PACKAGE_B1154_FILE_SET');
$b1154Overlay=[];$b1154Sha=[];
foreach(AS54::STEPS as [$p,$kind]){
    $sha=hash('sha256',$blob($b1154Commit,$p));$pre=$kind==='modified'?hash('sha256',$blob($b1154Base,$p)):null;
    if($kind==='modified'&&($b1153Sha[$p]??($b1152Sha[$p]??($b1151Sha[$p]??($b1150Sha[$p]??($b1149Sha[$p]??($b1148Sha[$p]??($b1147Sha[$p]??($b1146Sha[$p]??($byPath[$p]??null)))))))))!==$pre)throw new RuntimeException('PACKAGE_B1154_PRE_NOT_LIVE');if($kind==='new'&&(isset($byPath[$p])||isset($b1146Sha[$p])||isset($b1147Sha[$p])||isset($b1148Sha[$p])||isset($b1149Sha[$p])||isset($b1150Sha[$p])||isset($b1151Sha[$p])||isset($b1152Sha[$p])||isset($b1153Sha[$p])))throw new RuntimeException('PACKAGE_B1154_NEW_ALREADY_LIVE');
    // The digests the B1154 coordinator that the owner ran has coded, and the code base of this rollout (the B1154 payload commit, the live tree after Report1120): its blob of every B1154 file is the B1154 payload.
    if(AS54::DIGESTS[$p]!==[$pre,$sha])throw new RuntimeException('PACKAGE_B1154_DIGEST_NOT_THE_PINNED_ONE_'.$p);
    if(hash('sha256',$blob($b1155Base,$p))!==$sha)throw new RuntimeException('PACKAGE_B1154_PAYLOAD_NOT_IN_THE_CODE_BASE_'.$p);
    $b1154Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1154Sha[$p]=$sha;
}
// Production equals the B1155 code base chain: the B1153 payload commit d760bb2c and the B1154 code base 482d2fa5 have the SAME apps/cms/current (tests and bin included).
if($git(['diff','--quiet',$b1153Commit,$b1154Base,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_B1154_CODE_BASE_DIFFERS_FROM_THE_B1153_PAYLOAD_COMMIT');
// B1155 (the pinned B1155 STEPS: d2c07f62 over ad901a58, the owner's package of Report1121): the file set is the CMS diff minus the CMS tests and docs; kinds and digests equal the pinned B1155 coordinator (its STEPS and DIGESTS, read from the class the owner ran),
// each pre digest equals the live digest before it, each new path is absent before, and the code base of this rollout (d2c07f62) holds every B1155 payload file byte for byte (BASE is the B1155 payload commit itself).
$whole=$diff($b1155Base,$b1155Commit);foreach(array_keys($whole) as $p)if(str_starts_with($p,CMS.'tests/')||str_starts_with($p,CMS.'docs/'))unset($whole[$p]);$d1155=$cms($whole);$e=[];foreach(DR55::STEPS as [$p,$kind])$e[$p]=$kind==='new'?'A':'M';ksort($e,SORT_STRING);
if($d1155!==$e)throw new RuntimeException('PACKAGE_B1155_FILE_SET');
$b1155Overlay=[];$b1155Sha=[];
foreach(DR55::STEPS as [$p,$kind]){
    $sha=hash('sha256',$blob($b1155Commit,$p));$pre=$kind==='modified'?hash('sha256',$blob($b1155Base,$p)):null;
    if($kind==='modified'&&($b1154Sha[$p]??($b1153Sha[$p]??($b1152Sha[$p]??($b1151Sha[$p]??($b1150Sha[$p]??($b1149Sha[$p]??($b1148Sha[$p]??($b1147Sha[$p]??($b1146Sha[$p]??($byPath[$p]??null))))))))))!==$pre)throw new RuntimeException('PACKAGE_B1155_PRE_NOT_LIVE');if($kind==='new'&&(isset($byPath[$p])||isset($b1146Sha[$p])||isset($b1147Sha[$p])||isset($b1148Sha[$p])||isset($b1149Sha[$p])||isset($b1150Sha[$p])||isset($b1151Sha[$p])||isset($b1152Sha[$p])||isset($b1153Sha[$p])||isset($b1154Sha[$p])))throw new RuntimeException('PACKAGE_B1155_NEW_ALREADY_LIVE');
    if(DR55::DIGESTS[$p]!==[$pre,$sha])throw new RuntimeException('PACKAGE_B1155_DIGEST_NOT_THE_PINNED_ONE_'.$p);
    $b1155Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1155Sha[$p]=$sha;
}

// B1156 (the pinned B1156 STEPS: 94de4be5 over d2c07f62, the owner's package of Report1122): the file set is the CMS diff minus the CMS tests and docs; the deleted files are exactly the pinned orphans; kinds and digests equal the pinned
// B1156 coordinator (class CommissionsInstallCoordinator of tools/storefront/b1156), each pre digest equals the live digest before it and each new path is absent before.
$whole=$diff($b1156Base,$base);$del=[];foreach(array_keys($whole) as $p){$r=substr($p,strlen(CMS));if(str_starts_with($r,'tests/')||str_starts_with($r,'docs/')){unset($whole[$p]);continue;}if($whole[$p]==='D'){$del[]=$r;unset($whole[$p]);}}
sort($del,SORT_STRING);$op=array_keys(DR56::ORPHANS);sort($op,SORT_STRING);if($del!==$op)throw new RuntimeException('PACKAGE_B1156_DELETED_FILES_ARE_NOT_THE_PINNED_ORPHANS');
$d1156=$cms($whole);$e=[];foreach(DR56::STEPS as [$p,$kind])$e[$p]=$kind==='new'?'A':'M';ksort($e,SORT_STRING);
if($d1156!==$e)throw new RuntimeException('PACKAGE_B1156_FILE_SET');
$b1156Overlay=[];$b1156Sha=[];
foreach(DR56::STEPS as [$p,$kind]){
    $sha=hash('sha256',$blob($base,$p));$pre=$kind==='modified'?hash('sha256',$blob($b1156Base,$p)):null;
    if($kind==='modified'&&($b1155Sha[$p]??($b1154Sha[$p]??($b1153Sha[$p]??($b1152Sha[$p]??($b1151Sha[$p]??($b1150Sha[$p]??($b1149Sha[$p]??($b1148Sha[$p]??($b1147Sha[$p]??($b1146Sha[$p]??($byPath[$p]??null)))))))))))!==$pre)throw new RuntimeException('PACKAGE_B1156_PRE_NOT_LIVE');
    if($kind==='new'&&(isset($byPath[$p])||isset($b1146Sha[$p])||isset($b1147Sha[$p])||isset($b1148Sha[$p])||isset($b1149Sha[$p])||isset($b1150Sha[$p])||isset($b1151Sha[$p])||isset($b1152Sha[$p])||isset($b1153Sha[$p])||isset($b1154Sha[$p])||isset($b1155Sha[$p])))throw new RuntimeException('PACKAGE_B1156_NEW_ALREADY_LIVE');
    if(DR56::DIGESTS[$p]!==[$pre,$sha])throw new RuntimeException('PACKAGE_B1156_DIGEST_NOT_THE_PINNED_ONE_'.$p);
    $b1156Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1156Sha[$p]=$sha;
}
foreach(DR56::ORPHANS as $p=>$orphanSha)if(hash('sha256',$blob($b1156Base,$p))!==$orphanSha)throw new RuntimeException('PACKAGE_B1156_ORPHAN_DIGEST_'.$p);

// The B1157 file set: the CMS-scoped diff BASE..SOURCE minus the CMS tests and the CMS docs. The two deleted files are the retained orphans (owner rule: nothing is deleted on the host): their set, their B1155 digests and the fact that
// nothing in the new tree includes them are asserted here. Everything else is exactly the coded STEPS: under database/ only the two migration files (additions), under routes/ only web.php and api.php, under bin/ only the three
// tools of NEW_BIN; nothing under config/, composer.* or .env.
$excluded=[];$expected=[];foreach(DR::STEPS as [$p,$kind])$expected[$p]=$kind==='new'?'A':'M';ksort($expected,SORT_STRING);
$whole=$diff($base,$payloadCommit);$deleted=[];
foreach(array_keys($whole) as $p){
    if(!str_starts_with($p,CMS))throw new RuntimeException('PACKAGE_DIFF_OUTSIDE_CMS_'.$p);
    $r=substr($p,strlen(CMS));
    if(preg_match('~^(config/|composer\.|\.env)~',$r)===1)throw new RuntimeException('PACKAGE_CONFIG_COMPOSER_ENV_CHANGE_STOP_AND_REPORT_'.$p);
    if(str_starts_with($r,'database/')&&!str_starts_with($r,'database/migrations/'))throw new RuntimeException('PACKAGE_DATABASE_CHANGE_OTHER_THAN_A_MIGRATION_STOP_AND_REPORT_'.$p);
    if(str_starts_with($r,'database/migrations/')&&!in_array($r,DR::migrationFiles(),true))throw new RuntimeException('PACKAGE_UNEXPECTED_MIGRATION_STOP_AND_REPORT_'.$p);
    if(str_starts_with($r,'routes/')&&!in_array($r,['routes/web.php','routes/api.php'],true))throw new RuntimeException('PACKAGE_ROUTE_FILE_OTHER_THAN_WEB_OR_API_CHANGE_STOP_AND_REPORT_'.$p);
    if(str_starts_with($r,'bin/')&&!in_array($r,DR::NEW_BIN,true))throw new RuntimeException('PACKAGE_BIN_FILE_OTHER_THAN_THE_THREE_TOOLS_STOP_AND_REPORT_'.$p);
    if(str_starts_with($r,'tests/')||str_starts_with($r,'docs/')){$excluded[]=$p;unset($whole[$p]);continue;}
    if($whole[$p]==='D'){$deleted[$r]=true;unset($whole[$p]);}
}
$deleted=array_keys($deleted);sort($deleted,SORT_STRING);$orphanPaths=array_keys(DR::ORPHANS);sort($orphanPaths,SORT_STRING);
if($deleted!==$orphanPaths)throw new RuntimeException('PACKAGE_DELETED_FILES_ARE_NOT_THE_CODED_ORPHANS_'.json_encode($deleted));
$perSource=$cms($whole);
if($perSource!==$expected){fwrite(STDERR,json_encode(['diff'=>$perSource,'expected'=>$expected],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);throw new RuntimeException('PACKAGE_FILE_SET_DIFFERS_FROM_THE_SOURCE_DIFF');}
if(count(DR::STEPS)!==count($expected)||count($expected)!==B1157Payload::FILES||count(array_filter($expected,static fn($k)=>$k==='A'))!==B1157Payload::NEW_FILES)throw new RuntimeException('PACKAGE_STEPS_NOT_THE_GENERATED_COUNTS');
// The orphans: their bytes at BASE are their B1155 payload digests (the host holds them), they are in the runtime manifest roots (resources/), and no file of the SOURCE tree (app, bootstrap, resources, routes, public, config, database, bin) includes them.
foreach(DR::ORPHANS as $p=>$sha){
    if(hash('sha256',$blob($base,$p))!==$sha||($b1156Sha[$p]??(DR56::ORPHANS[$p]??null))!==$sha&&($b1155Sha[$p]??null)!==$sha)throw new RuntimeException('PACKAGE_ORPHAN_DIGEST_IS_NOT_THE_LIVE_ONE_'.$p);
    if($git(['cat-file','-e',$payloadCommit.':'.CMS.$p],true)!==null)throw new RuntimeException('PACKAGE_ORPHAN_STILL_EXISTS_IN_THE_SOURCE_'.$p);
}
if(DR::ORPHANS!==[]){$pats=[];foreach(array_keys(DR::ORPHANS) as $p)$pats[]=str_ends_with($p,'.blade.php')?preg_quote(str_replace('/','.',substr($p,16,-10)),'~').'|'.preg_quote(substr($p,16,-10),'~'):preg_quote(basename($p,'.php'),'~');
    if(trim((string)$git(['grep','-l','-E',implode('|',$pats),$payloadCommit,'--','apps/cms/current/app','apps/cms/current/bootstrap','apps/cms/current/resources','apps/cms/current/public','apps/cms/current/routes','apps/cms/current/config','apps/cms/current/database','apps/cms/current/bin'],true))!=='')throw new RuntimeException('PACKAGE_THE_NEW_TREE_STILL_INCLUDES_A_RETAINED_ORPHAN');}
// docs/openapi.yaml is documentation, not a file of the runtime manifest.
if(isset($byPath['docs/openapi.yaml'])||isset($expected['docs/openapi.yaml']))throw new RuntimeException('PACKAGE_OPENAPI_YAML_MUST_NOT_BE_IN_THE_RUNTIME_MANIFEST');
// The three bin tools and the migrations are files of the runtime manifest roots (bin/, database/migrations/): the tree follows main.
foreach(DR::NEW_BIN as $p)if(!isset($byPath[$p])||!isset($expected[$p]))throw new RuntimeException('PACKAGE_BIN_TOOL_NOT_THE_MANIFEST_FILE_'.$p);
foreach(DR::migrationFiles() as $p)if(isset($byPath[$p])||($expected[$p]??null)!=='A')throw new RuntimeException('PACKAGE_MIGRATION_NOT_A_NEW_FILE_'.$p);


$pins=['b1134-files.php'=>[$b1134.'/InstallFileOps.php','9dce8324bd4464a1689918775736bcd3a850f2b1c2b1b0ca7e130a9e486da12b'],'b1144-gates.php'=>[$b1144.'/LeanGates.php','7e62150fcad0884255ddb3c18063f5e2a23852a3eb0fdecb978c2955392ccf6e'],'b1144-probe.php'=>[$b1144.'/FreshnessProbe.php','3dbf4762e419d8df053fa7e642bd1b85df07d66dbffdb94fe0d8d5cdc1b50616'],'b1144-web.php'=>[$b1144.'/LeanWeb.php','54a04bc4dea81d56ad5fe0e6b25a141d4a86acf803f6df95f1c5d2b7adff6a73'],
    'b1145-env.php'=>[$b1145.'/EnvFlags.php','04a4bbd13b8aef346247d8641445ccb9f0a2d7fd3f26f7a03723724fc0132f69'],'b1145-app-state.php'=>[$b1145.'/AppState.php','642ddede76db8f1ea66bfc6a8d1ad74c894a70b665204cac4c96de17e97113a5'],'b1145-coordinator.php'=>[$b1145.'/MigrateUnclampCoordinator.php','46b159fbeab6c4f849dbb1a9291bbf13245b511ea58fd51646f26f28caa53fb9'],
    'b1146-probe.php'=>[$b1146.'/OverlayProbe.php','abcc9c64c6b9918d180afd387ec917a4924e1ce28c0099c0bbdae500e811c01f'],'b1146-app-check.php'=>[$b1146.'/AppCheck.php','68b4b2af5ad5a6399991adf474cfc753abf77a483fb12d2c1669a4b307ce2c2b'],
    'b1147-web.php'=>[$b1147.'/CatalogWeb.php','c4e4c0935b4c14e7fbee1089243f5be4784c5b25c5c0a62d11551d5dfa1461ce'],'b1147-app-check.php'=>[$b1147.'/AppCheck.php','278b03d1914580063a71f4f12c99e741376fbedb268e4570614dc3ccf3213b73'],'b1147-coordinator.php'=>[$b1147.'/CatalogOverlayCoordinator.php','d52bf4d475d3b3b1e6bd33fb6e766d24414b19b0fb3ee4dc0e9cdce59ed9cde9'],
    'b1148-web.php'=>[$b1148.'/GlassWeb.php','69bbdca104bfcbc62948e06a464fcaff55b5bf100a7ab513299fb19d00600c7c'],'b1148-app-check.php'=>[$b1148.'/AppCheck.php','0951b1ca3e9fcf6b7a2c722ce412903b593a9aa0b63dca5200f810a54c39e16a'],'b1148-coordinator.php'=>[$b1148.'/GlassOverlayCoordinator.php','332a13a5014845051aea3ffa68591b6075d798e94be5428e3cb288d222d3641b'],
    // The approved B1149 package members (head bf5ac045, Report1115 PASS): sha256 of the git blobs of tools/storefront/b1149 at bf5ac045. Assumption: the ZIP the owner ran was built from exactly these bytes.
    'b1149-web.php'=>[$b1149.'/StorefrontWeb.php','c090e207891fe0e70efaaa6c09ffb6eb00b44c475ccf4acc5790ac672a07c2ca'],'b1149-app-check.php'=>[$b1149.'/AppCheck.php','da100fde8e75fd0885e81366bc95d695f01bbedf31842dccec5fa5b2baa7aed3'],'b1149-coordinator.php'=>[$b1149.'/StorefrontInstallCoordinator.php','3742a8c1100f9d7bab9c4c489cc2e22d789a4d954fb47e01eeb112c52067e1d3'],
    // The B1150 package members the owner ran (Report1116 PASS): sha256 of the members of the owner's b1150-operator.zip, equal to the git blobs of tools/storefront/b1150 at 2c74fd7b (checked byte for byte when that package was written).
    'b1150-web.php'=>[$b1150.'/MediaWeb.php','6c570b0c89be1e11d35a3155972c661d6af4b504d0ba6c68a9d8f397415227a8'],'b1150-app-check.php'=>[$b1150.'/AppCheck.php','d438f04becd62f226902d0cb66f9cb7cf2cb12a11d1b0dd624f44344c982f196'],'b1150-coordinator.php'=>[$b1150.'/MediaInstallCoordinator.php','cf85b7822a0acba558c4c8a1b52a2dbdd24ee3c168be297cd1c663126e1402cb'],
    // The B1151 package members (Report1117 PASS): sha256 of the git blobs of tools/storefront/b1151 on origin/main fc954517. ASSUMPTION, not proven here: the ZIP the owner ran carried exactly these bytes. The operator entry verifies the host copies against this pin before anything runs, so a difference is a HOLD with nothing changed.
    'b1151-app-check.php'=>[$b1151.'/AppCheck.php','6842828a326e0397022479c6cd6a46ebf6da04a40326b7c03be5facffe8182ec'],'b1151-coordinator.php'=>[$b1151.'/LegalAccountInstallCoordinator.php','becac22c0582f69338a27bf5acd82aa3e6583d846f939c9b4bb079300fee8872'],
    // The B1152 package members (Report1118 PASS), PROVEN: the members of the owner's b1152-operator.zip (CI run 43469605280, artifact b1152-operator-package) carry these sha256 values in its b1152-package.sha256, and
    // the sha256 of that file is b9287ffc90fd2878e3835f94e7e78046ad2b5455807d9c96d1e73a3fcc01dda8, the CHECKSUMS_SHA256 line of Report1118. They equal the git blobs of tools/storefront/b1152 on origin/main.
    'b1152-app-check.php'=>[$b1152.'/AppCheck.php','96cb4550a6e2f0d5158788cdb62eff4fd4bf8d217ab0955d138bbe826f548262'],'b1152-coordinator.php'=>[$b1152.'/OriginPinInstallCoordinator.php','721ffe3f7387ef0326c798d50217bcdefee4b0a2aaebed190b75debb9c0ae97f'],'b1152-load-probe.php'=>[$b1152.'/LoadProbe.php','457f3da1776654bf69930075e5c1851062e597a7e136217eae57a07ee6388a4b'],
    // The B1153 package members (Report1119 PASS), PROVEN: the members of the owner's b1153-operator.zip (CI run 43637960668, artifact b1153-operator-package, head ab967b45) carry these sha256 values in its b1153-package.sha256,
    // and the sha256 of that file is 6a1169580386dd8d818d25f74d693ae1bd4f4d58b9905d045469e5670f8749f3, the CHECKSUMS_SHA256 of Report1119 (verified when that package was written). They equal the git blobs of tools/storefront/b1153 on origin/main.
    'b1153-app-check.php'=>[$b1153.'/AppCheck.php','3049f0a741bc47f4f32a3f0314d4509a9f61b0a047a995f2a5ae1ac7e044ca19'],'b1153-coordinator.php'=>[$b1153.'/LogoCatalogInstallCoordinator.php','c040b3b2afe9f5401d3a93b3e73edc3894174d250db49cdd362ead8185584cdd'],'b1153-load-probe.php'=>[$b1153.'/LoadProbe.php','4b110fa05ba8f18e192a3332525c56dc0e5a8217daef21d2299ef1494e8ace97'],
    // The B1154 package members (Report1120 PASS): sha256 of the git blobs of tools/storefront/b1154 on origin/main (they are the files the owner's b1154 package was built from; the CI of this rollout builds the b1154 ZIP again and
    // compares these two members with the pin). ASSUMPTION, not proven here: the ZIP the owner ran carried exactly these bytes. The operator entry verifies the host copies against this pin before anything runs, so a difference is a HOLD with nothing changed.
    'b1154-app-check.php'=>[$b1154.'/AppCheck.php','6da48e7998be940c4cca83f5afbe82c59c79121c036ac4efaf2e02cb08037385'],'b1154-coordinator.php'=>[$b1154.'/AdminShellInstallCoordinator.php','51dd96f6798bbebc74def1d1ddaed9f85a632ea93abcbe22b2ffd8866a05d016'],
    // The B1155 package members (Report1121 PASS), PROVEN: the members of the owner's b1155-operator.zip (CI run 43764935044, artifact b1155-operator-package) carry these sha256 values in its b1155-package.sha256, and the sha256 of that file is
    // 40916a069bbef1deeb8ff7b3f9cc5783b608fd4682d6eaaa7b1254a23c75f0fa, the CHECKSUMS_SHA256 line of Report1121. They equal the git blobs of tools/storefront/b1155 on origin/main.
    'b1155-app-check.php'=>[$b1155.'/AppCheck.php','ada769856ff6d7c498bb952cb2f1df12e11f8079cccae7f7740e79a27c96613e'],'b1155-coordinator.php'=>[$b1155.'/DashboardReportsInstallCoordinator.php','bff6b32639318b7b1f174c675aa7aa336c8e9efdae10b6fdd4dfd167f95c8e34'],
    // The B1156 package members (Report1122 PASS): sha256 of the git blobs of tools/storefront/b1156 on origin/main (merged PR 204). ASSUMPTION, not proven here: the ZIP the owner ran carried exactly these bytes (the CI of this rollout builds the b1156 ZIP again and compares them). The operator entry verifies the host copies against this pin before anything runs.
    'b1156-app-check.php'=>[$b1156.'/AppCheck.php','4076dca6f60f5229fc5b6866d9e69846eece0128a6372a398bdd08061ae9cc96'],'b1156-coordinator.php'=>[$b1156.'/CommissionsInstallCoordinator.php','436abbe5b274186a185e29fe3c9fee39abd5cea497da6bbf3d321b78ec84009c']];
foreach($pins as $name=>[$file,$digest])if(hash_file('sha256',$file)!==$digest)throw new RuntimeException('PINNED_DEPENDENCY_DRIFT_'.$name);
$members=['b1157-installer.php'=>'operator-entry.php','b1157-coordinator.php'=>'B1157InstallCoordinator.php','b1157-payload.php'=>'Payload.php','b1157-app-check.php'=>'AppCheck.php','b1157-README-SR.md'=>'README-SR.md'];
$bytes=[];foreach($members as $name=>$file){$data=file_get_contents($tools.'/'.$file);if(!is_string($data)||str_contains($data,"\r"))throw new RuntimeException('PACKAGE_SOURCE_BYTES_'.$name);$bytes[$name]=$data;}
// The shipped tool code must not reach cPanel, reset OPcache globally, overlay PHP flags, migrate, seed, build any cache, POST or
// send a custom method, send mail, or write the DB switch or any row. route:clear and queue:restart are the only allowed artisan
// cache commands (coded once each, in refreshViews); view:clear is coded once more in the recovery block only.
foreach($bytes as $name=>$data)if(str_ends_with($name,'.php')&&preg_match('~/usr/local/cpanel|uapi|DomainInfo|opcache_reset|php_flag|HostProcessGuard|--seed|migrate:fresh|migrate:rollback|migrate:refresh|migrate:reset|db:wipe|config:cache|view:cache|route:cache|public_storefront_open|->update\(|->insert\(|->upsert\(|->delete\(|->set\(|->put\(|Mail::|mail\(|CURLOPT_POST|CURLOPT_CUSTOMREQUEST|mail:test|app:send-test-mail~i',$data))throw new RuntimeException('PACKAGE_FORBIDDEN_MECHANISM_'.$name);
if(substr_count($bytes['b1157-coordinator.php'],"artisanOk(['route:clear'])")!==1||substr_count($bytes['b1157-coordinator.php'],"artisanOk(['queue:restart'])")!==1)throw new RuntimeException('PACKAGE_ARTISAN_CACHE_COMMANDS');
// The ONE migrate call of the tool: exactly this guarded call, nowhere else any 'migrate' command (a --pretend, a status-less run or a second call is refused).
if(substr_count($bytes['b1157-coordinator.php'],"['migrate','--force','--realpath']")!==1||substr_count($bytes['b1157-coordinator.php'],"'migrate'")!==1||substr_count($bytes['b1157-coordinator.php'],"'migrate:status'")!==2)throw new RuntimeException('PACKAGE_MIGRATE_CALL_NOT_THE_ONE_GUARDED_CALL');
foreach(['b1157-installer.php','b1157-app-check.php'] as $n)if(preg_match("~'migrate'|migrate --~",$bytes[$n])===1)throw new RuntimeException('PACKAGE_MIGRATE_IN_'.$n);
// The password endpoints are never requested by the shipped tool (no login, no PUT, no POST): none of their paths may appear in the shipped code.
foreach($bytes as $name=>$data)if(str_ends_with($name,'.php')&&preg_match('~/account/password|/me/password|/auth/password|password/email|forgot-password(?!\.blade\.php)~',$data))throw new RuntimeException('PACKAGE_PASSWORD_ENDPOINT_IN_THE_TOOL_'.$name);

$overlay=[];$new=[];$old=[];$digests="n\tgroup\tkind\tpath\tsource\tpre_sha256_git_".substr($base,0,8)."\tpre_state_source\tpayload_sha256_git_source\n";
$earlier=static function(string $path)use($byPath,$b1146Sha,$b1147Sha,$b1148Sha,$b1149Sha,$b1150Sha,$b1151Sha,$b1152Sha,$b1153Sha,$b1154Sha,$b1155Sha,$b1156Sha):array{
    foreach([['B1156_PAYLOAD',$b1156Sha],['B1155_PAYLOAD',$b1155Sha],['B1154_PAYLOAD',$b1154Sha],['B1153_PAYLOAD',$b1153Sha],['B1152_PAYLOAD',$b1152Sha],['B1151_PAYLOAD',$b1151Sha],['B1150_PAYLOAD',$b1150Sha],['B1149_PAYLOAD',$b1149Sha],['B1148_PAYLOAD',$b1148Sha],['B1147_PAYLOAD',$b1147Sha],['B1146_PAYLOAD',$b1146Sha]] as [$label,$map])if(isset($map[$path]))return [$map[$path],$label];
    return [$byPath[$path]??null,'RUNTIME_MANIFEST'];
};
foreach(DR::STEPS as $i=>[$path,$kind,$group,$s1]){
    $n=$i+1;$commit=DR::sourceCommit($s1);$payload=$blob($commit,$path);$pre=$kind==='modified'?$blob($base,$path):null;$new[$path]=$payload;$old[$path]=$pre;$liveBefore='ABSENT';
    if($kind==='new'&&($git(['cat-file','-e',$base.':'.CMS.$path],true)!==null||$earlier($path)[0]!==null))throw new RuntimeException('PACKAGE_NEW_PATH_EXISTS_'.$n);
    if($kind==='modified'){$preSha=hash('sha256',(string)$pre);[$live,$liveBefore]=$earlier($path);if($live!==$preSha)throw new RuntimeException('PACKAGE_PRE_STATE_NOT_LIVE_'.$n.'_'.$path);}
    if(str_contains($payload,"\r"))throw new RuntimeException('PACKAGE_PAYLOAD_CR_'.$n);
    // The reviewers' digests (raw git blob bytes), coded in the coordinator's payload block: any other bytes are refused here and on the host.
    $dig=DR::DIGESTS[$path]??null;if($dig===null||$dig[1]!==hash('sha256',$payload)||$dig[0]!==($pre===null?null:hash('sha256',$pre)))throw new RuntimeException('PACKAGE_DIGEST_DIFFERS_FROM_THE_REVIEWED_ONE_'.$n.'_'.$path);
    $member=sprintf('b1157-payload-%02d.bin',$n);$bytes[$member]=$payload;$overlay[]=['n'=>$n,'path'=>$path,'kind'=>$kind,'group'=>$group,'source'=>$commit,'payload'=>$member,'payloadSHA256'=>hash('sha256',$payload),'preSHA256'=>$pre===null?null:hash('sha256',$pre),'size'=>strlen($payload),'liveBefore'=>$liveBefore];
    $digests.=$n."\t".$group."\t".$kind."\t".$path."\t".substr($commit,0,8)."\t".($pre===null?'ABSENT':hash('sha256',$pre))."\t".$liveBefore."\t".hash('sha256',$payload)."\n";
}
if(count(DR::DIGESTS)!==count(DR::STEPS))throw new RuntimeException('PACKAGE_DIGESTS_NOT_ONE_PER_STEP');
// The groups: A the new inert files (13, the two migrations among them), B libraries, C controllers + bin + the admin stylesheet, D the provider (guard: web-SAPI class proof), E the routes (guard: class proof, then route:clear), F the views (guard: asset proof).
$idx=[];$grp=[];foreach(DR::STEPS as $i=>[$p,,$g]){$idx[$p]=$i;$grp[$p]=$g;}
$counts=array_count_values(array_values($grp));ksort($counts);if(array_diff(array_keys($counts),['A','B','C','D','E','F'])!==[])throw new RuntimeException('PACKAGE_GROUP_LAYOUT_UNKNOWN_GROUP');
$prev='';foreach($grp as $g){if($g<$prev)throw new RuntimeException('PACKAGE_GROUPS_NOT_NON_DECREASING');$prev=$g;}
// The guard groups are validated by DR::plan() below (provider alone in D, routes in E, guard F only with the admin stylesheet).
// BACKWARD COMPATIBILITY and ORDER, proven on the shipped bytes (the OLD callers and the OLD views must keep working between the groups).
// 1. No public method of any modified PHP class disappears (the new code only adds methods and optional parameters; constructor dependencies are classes of group A).
foreach($new as $p=>$text)if($old[$p]!==null&&str_starts_with($p,'app/')&&str_ends_with($p,'.php')){
    preg_match_all('/\bpublic\s+(?:static\s+)?function\s+(\w+)\s*\(/',(string)$old[$p],$o);preg_match_all('/\bpublic\s+(?:static\s+)?function\s+(\w+)\s*\(/',$text,$nm);
    $gone=array_diff($o[1],$nm[1]);if($gone!==[])throw new RuntimeException('PACKAGE_A_PUBLIC_METHOD_DISAPPEARS_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/','_',$p.'_'.implode('_',$gone))));
}
// 2. The new files are inert until linked: nothing in the tree at BASE (app, bootstrap, resources, public, routes, config) names the new classes or views.
$needles=[];foreach(DR::STEPS as [$p,$kind]){if($kind!=='new'||str_starts_with($p,'database/'))continue;$needles[]=str_ends_with($p,'.blade.php')?preg_quote(str_replace('/','.',substr($p,16,-10)),'~'):'\\b'.preg_quote(basename($p,'.php'),'~').'\\b';}
if($needles!==[]&&trim((string)$git(['grep','-l','-E',implode('|',$needles),$base,'--','apps/cms/current/app','apps/cms/current/bootstrap','apps/cms/current/resources','apps/cms/current/public','apps/cms/current/routes','apps/cms/current/config'],true))!=='')throw new RuntimeException('PACKAGE_OLD_TREE_ALREADY_NAMES_THE_NEW_FILES');
// 3. The routes: the count after equals the count before plus the added Route:: declarations (an estimate unless the generator was given --routes-after; the CI fixture measures the real count). Informational: the shape of the changes to the two route files.
$pure=static function(string $o,string $n):bool{$o=explode("\n",$o);$n=explode("\n",$n);$j=0;$m=count($n);foreach($o as $line){while($j<$m&&$n[$j]!==$line)$j++;if($j>=$m)return false;$j++;}return true;};
$routesPure=[];foreach(['routes/web.php','routes/api.php'] as $p)if(isset($new[$p]))$routesPure[$p]=$pure((string)$old[$p],$new[$p]);
if(DR::ROUTES_BEFORE!==579||DR::ROUTES_AFTER<DR::ROUTES_BEFORE)throw new RuntimeException('PACKAGE_ROUTE_COUNTS');
// 4. The order edges (caller group above callee group, or later in the same group): every modified view after the routes, every `use` and @include edge below.
$code=static fn(string $c,string $e):string=>'PACKAGE_ORDER_EDGE_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/','_',$c.'_AFTER_'.$e));
$later=static fn(string $c,string $e):bool=>isset($grp[$c],$grp[$e])&&($grp[$c]>$grp[$e]||($grp[$c]===$grp[$e]&&$idx[$c]>$idx[$e]));
// The `use App\...;` edges of every modified or new file outside group A, and the @include edges of the views: the callee is in a lower group or earlier in the same group (the model pair OrderCommission/CommissionPaymentBatch refers to each other by class name only, lazily, no `use`).
foreach($new as $p=>$text){
    if($grp[$p]==='A'&&!str_ends_with($p,'.blade.php'))continue;
    if(str_ends_with($p,'.php')&&!str_ends_with($p,'.blade.php')&&preg_match_all('~^use (App\\\\[A-Za-z0-9_\\\\]+);~m',$text,$u))foreach($u[1] as $fq){$callee='app/'.str_replace('\\','/',substr($fq,4)).'.php';if(isset($grp[$callee])&&!$later($p,$callee)&&!($p==='routes/web.php'&&str_starts_with($callee,'app/Http/Controllers/')))throw new RuntimeException($code($p,$callee));}
    if(str_ends_with($p,'.blade.php')&&preg_match_all("~@include\\('([a-z0-9._-]+)'~",$text,$u))foreach($u[1] as $view){$callee='resources/views/'.str_replace('.','/',$view).'.blade.php';if(isset($grp[$callee])&&!$later($p,$callee))throw new RuntimeException($code($p,$callee));}
}
// The classes of the new app files and the views the read-only acceptance step compiles; the new directories the install would create (none: every parent exists at BASE).
if(count(DR::proofClasses())>8)throw new RuntimeException('PACKAGE_PROOF_CLASSES_MORE_THAN_EIGHT');foreach(DR::proofClasses() as $c)if(!isset($new['app/'.str_replace('\\','/',substr($c,4)).'.php']))throw new RuntimeException('PACKAGE_PROOF_CLASS_NOT_A_STEP_'.$c);
$wantViews=[];foreach(array_keys($new) as $p)if(str_ends_with($p,'.blade.php'))$wantViews[]=$p;$haveViews=DR::VIEW_CHECKS;sort($wantViews);sort($haveViews);if($haveViews!==$wantViews)throw new RuntimeException('PACKAGE_VIEW_CHECKS_NOT_THE_VIEWS_OF_THE_PAYLOAD');
if(DR::B1156_TREE_FILES!==8596||DR::installedTreeFiles()!==8596+B1157Payload::NEW_FILES)throw new RuntimeException('PACKAGE_TREE_FILE_COUNTS');
$dirs=[];foreach(DR::STEPS as [$p,$kind])if($kind==='new'){$d=dirname($p);while($d!=='.'&&$git(['cat-file','-e',$base.':'.CMS.$d],true)===null){$dirs[$d]=true;$d=dirname($d);}}
$dirList=array_keys($dirs);usort($dirList,static fn($a,$b)=>strlen($a)<=>strlen($b));if($dirList!==[])throw new RuntimeException('PACKAGE_UNEXPECTED_NEW_DIRECTORY_'.json_encode($dirList));
// The storefront is not in this payload (not one file of it changes): the B1149 markers and the stylesheet and gallery versions are those of the live tree (the B1153 ones).
$storeLayout='resources/views/public-shop/layout.blade.php';$themeForm='resources/views/public-shop/partials/theme-form.blade.php';$css='public/assets/css/ald1n-public-storefront.css';$gallery='public/assets/css/ald1n-public-gallery.css';$catalogView='resources/views/public-shop/catalog.blade.php';$show='resources/views/public-shop/show.blade.php';
foreach([$storeLayout,$themeForm,$css,$gallery,$catalogView,$show] as $p)if(isset($new[$p])||$blob($base,$p)!==$blob($payloadCommit,$p))throw new RuntimeException('PACKAGE_STOREFRONT_FILE_IN_THE_PAYLOAD_OR_CHANGED_'.$p);
foreach([['css',$css],['gallery',$gallery],['catalog',$catalogView],['catalog',$show]] as [$k,$p])if(!str_contains($blob($base,$p),MARKERS[$k]))throw new RuntimeException('PACKAGE_MARKER_'.strtoupper($k).'_'.$p);
if(!str_contains($blob($base,$storeLayout).$blob($base,$themeForm),MARKERS['layout']))throw new RuntimeException('PACKAGE_MARKER_LAYOUT');
$version=static function(string $text,string $file):string{if(preg_match_all('~'.preg_quote($file,'~').'\?v=([0-9]{1,12})[\'"]~',$text,$v)!==1)throw new RuntimeException('PACKAGE_CSS_VERSION');return $v[1][0];};
$cssVersion=$version($blob($base,$storeLayout),'ald1n-public-storefront.css');$oldCssVersion=$cssVersion;
if($cssVersion!=='202611141')throw new RuntimeException('PACKAGE_STOREFRONT_CSS_VERSION_NOT_202611141_'.$cssVersion);
$galleryVersion=$version($blob($base,$catalogView),'ald1n-public-gallery.css');if($galleryVersion!==$version($blob($base,$show),'ald1n-public-gallery.css'))throw new RuntimeException('PACKAGE_GALLERY_VERSION_DIFFERS');
// The source markers the CLI helper reads from the live files: in every payload file, absent from the pre-state bytes of a modified file (a new file has none).
foreach(DR::SOURCE_MARKERS as $k=>[$p,$needle])if(!isset($new[$p])||!str_contains($new[$p],$needle)||(is_string($old[$p]??null)&&str_contains($old[$p],$needle)))throw new RuntimeException('PACKAGE_SOURCE_MARKER_'.strtoupper($k));
if(count(DR::SOURCE_MARKERS)>40)throw new RuntimeException('PACKAGE_MORE_THAN_40_SOURCE_MARKERS');
// The B1156 markers the helper asserts in BOTH states (generated: only those still in the new tree): present in the bytes at BASE and in the final bytes.
foreach(DR::PRIOR_MARKERS as $k=>[$p,$needle]){$final=isset($new[$p])?$new[$p]:$blob($payloadCommit,$p);if(!str_contains($blob($base,$p),$needle)||!str_contains($final,$needle))throw new RuntimeException('PACKAGE_B1156_SOURCE_MARKER_'.strtoupper($k));}
if(count(DR::PRIOR_MARKERS)<1||count(DR::PRIOR_MARKERS)>40)throw new RuntimeException('PACKAGE_PRIOR_MARKER_COUNT');
$files=[];foreach($pins as $name=>[,$digest])$files[$name]=$digest;foreach($bytes as $name=>$data)$files[$name]=hash('sha256',$data);ksort($files,SORT_STRING);
$common=['schemaVersion'=>1,'batch'=>'B1157','baseCommit'=>$base,'sourceCommit'=>$payloadCommit,'b1156Commit'=>$base,'b1156BaseCommit'=>$b1156Base,'b1155Commit'=>$b1155Commit,'b1155BaseCommit'=>$b1155Base,'b1154Commit'=>$b1154Commit,'b1154BaseCommit'=>$b1154Base,'b1153Commit'=>$b1153Commit,'b1153BaseCommit'=>$b1153Base,'b1152BaseCommit'=>$b1152Base,'b1152UrlCommit'=>$b1152Url,'b1152PdfCommit'=>$b1152Pdf,'b1152MainCommit'=>$b1152Main,'b1151EmailCommit'=>$b1151Email,'b1151ThrottleCommit'=>$b1151Throttle,'b1150Commit'=>$b1150Source,'storefrontCommit'=>$store,'storefrontBaseCommit'=>$storeBase,'storefrontMainCommit'=>$storeMain,'glassCommit'=>$glass,'catalogCommit'=>$catalog,'legalCommit'=>$legal,'b1146BaseCommit'=>$b1146Base,'installedSourceCommit'=>INSTALLED,'helperSource'=>$helper];
$bytes['b1157-package.json']=json_encode($common+['overlay'=>$overlay,'b1146Overlay'=>$b1146Overlay,'b1147Overlay'=>$b1147Overlay,'b1148Overlay'=>$b1148Overlay,'b1149Overlay'=>$b1149Overlay,'b1150Overlay'=>$b1150Overlay,'b1151Overlay'=>$b1151Overlay,'b1152Overlay'=>$b1152Overlay,'b1153Overlay'=>$b1153Overlay,'b1154Overlay'=>$b1154Overlay,'b1155Overlay'=>$b1155Overlay,'b1156Overlay'=>$b1156Overlay,'markers'=>MARKERS,'cssVersion'=>$cssVersion,'oldCssVersion'=>$oldCssVersion,'galleryVersion'=>$galleryVersion,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
$check2=json_decode($bytes['b1157-package.json'],true,64,JSON_THROW_ON_ERROR);DR::plan($check2,'/home/icaffeco/ald1n-project/incoming');DR::b1146Plan($check2);DR::b1147Plan($check2);DR::b1148Plan($check2);DR::b1149Plan($check2);DR::b1150Plan($check2);DR::b1151Plan($check2);DR::b1152Plan($check2);DR::b1153Plan($check2);DR::b1154Plan($check2);DR::b1155Plan($check2);DR::b1156Plan($check2);DR::markers($check2);
$bytes['b1157-provenance.json']=json_encode($common+['author'=>'Ald1n <https://ald1n.com>','workflowRun'=>getenv('GITHUB_RUN_ID')?:null,'workflowRepository'=>getenv('GITHUB_REPOSITORY')?:null,
    'sourceNote'=>'ONE source commit: the generated target '.$payloadCommit.' over '.$base.' (the B1156 payload commit, whose apps/cms/current equals the live tree, tests excluded)',
    'predecessor'=>['report'=>'docs/operations/1122-B1156-INSTALLER-<date>-<time>.md','reportSHA256Pinned'=>DR::B1156_REPORT_SHA256,'b1156StateId'=>DR::B1156_STATE_ID,'pinsFilled'=>B1157Payload::B1156_PINS_FILLED,'required'=>['B1156_FINAL'=>DR::B1156_PASS,'EXIT'=>'0','B1156_PHASE'=>'INSTALLED_PASS','b1156State'=>'INSTALLED_PASS'],'ignored'=>'any other B1156 report'],
    'runtimeManifestSHA256'=>DR::MANIFEST_SHA,'treeFilesBefore'=>DR::B1156_TREE_FILES,'treeFilesAfter'=>DR::installedTreeFiles(),'configAfterB1145SHA256'=>DR::CONFIG_SHA,'pinnedAppUrl'=>DR::EXPECTED_APP_URL,'excludedFromTheSourceDiff'=>$excluded,'retainedOrphans'=>DR::ORPHANS,'directoriesTheInstallCreates'=>$dirList,'installOrder'=>array_map(static fn($s)=>$s[2].' '.$s[1].' '.$s[0].' '.$s[3],DR::STEPS),'preStateSource'=>array_map(static fn($o)=>$o['path'].' '.$o['liveBefore'],$overlay),'pinnedNotRedelivered'=>array_map(fn($p)=>$p[1],$pins),
    'migrations'=>['files'=>DR::NEW_MIGRATIONS,'ranAfter'=>'EVERY_OTHER_FILE_AND_A_FRESH_VERIFIED_DATABASE_BACKUP','command'=>'artisan migrate --force --realpath --path=<file> (one per migration)','pendingBefore'=>DR::NEW_MIGRATIONS,'ranBefore'=>DR::RAN,'ranAfter'=>DR::RAN_AFTER,'neverDone'=>['migrate:fresh','migrate:refresh','migrate:rollback','migrate:reset','down()','dropping a table']],
    'rollbackPolicy'=>['errorBeforeTheMigrateCommand'=>'FULL_REVERSE_ORDER_FILE_ROLLBACK_MIGRATION_FILES_REMOVED_ALSO_AFTER_MIGRATE_INTENT_WHEN_NOTHING_RAN','killFromMigrateIntentOn'=>'RESUME_FORWARD_ONLY_NEVER_THE_GENERIC_RESTORE_MIGRATE_INTENT_MIGRATED_AND_ACCEPTANCE_INTENT','acceptanceFailsAfterTheMigration'=>'FILES_RESTORED_EXCEPT_THE_MIGRATION_FILES_FINAL_HOLD_ROLLED_BACK_SCHEMA_AHEAD'],
    'newRoutes'=>DR::NEW_WEB_ROUTES,'newApiRoutes'=>DR::NEW_API_ROUTES,'routeCountBefore'=>DR::ROUTES_BEFORE,'routeCountAfter'=>DR::ROUTES_AFTER,'routeCount'=>DR::ROUTES_BEFORE,
    'neverDone'=>['storefrontDbSwitchWrite','merchantApprovalWrite','smtpSettingsReadOrWrite','testMail','passwordResetRequest','composer','routeCacheBuild','envEdit','configChange','opcacheGlobalReset','viewCache','login','passwordEndpointCall','productOrCartWrite','themePost','registrationPost','pdfGeneration','forgedHostRequest','retainedDirectoryModeChange','authenticatedAdminRequest','fileDeletion'],'operatorCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1157-installer.sh','rollbackCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1157-installer.sh --rollback'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
ksort($bytes,SORT_STRING);$checksums='';foreach($bytes as $name=>$data)$checksums.=hash('sha256',$data).'  '.$name."\n";
$wrapper=file_get_contents($tools.'/operator-wrapper.sh');if(!is_string($wrapper)||str_contains($wrapper,"\r")||substr_count($wrapper,"\nSELF_SHA=__SELF_SHA__\n")!==1||substr_count($wrapper,"\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n")!==1)throw new RuntimeException('WRAPPER_TEMPLATE');
$wrapper=str_replace("\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n","\nCHECKSUMS_SHA=".hash('sha256',$checksums)."\n",$wrapper);$wrapper=str_replace("\nSELF_SHA=__SELF_SHA__\n","\nSELF_SHA=".hash('sha256',$wrapper)."\n",$wrapper);
$bytes['b1157-package.sha256']=$checksums;$bytes['b1157-installer.sh']=$wrapper;ksort($bytes,SORT_STRING);
file_put_contents($out.'/b1157-digests.tsv',$digests);
$zipPath=$out.'/b1157-operator.zip';if(file_exists($zipPath))unlink($zipPath);$zip=new ZipArchive();if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('ZIP_CREATE');
foreach($bytes as $name=>$data){$member='incoming/'.$name;$zip->addFromString($member,$data);$zip->setExternalAttributesName($member,ZipArchive::OPSYS_UNIX,(str_ends_with($name,'.sh')?0100755:0100644)<<16);$zip->setMtimeName($member,1763683200);}
if(!$zip->close())throw new RuntimeException('ZIP_CLOSE');
$zipSHA=hash_file('sha256',$zipPath);file_put_contents($zipPath.'.sha256',$zipSHA.'  b1157-operator.zip'."\n");
echo json_encode(['zip'=>basename($zipPath),'zipSHA256'=>$zipSHA,'zipBytes'=>filesize($zipPath),'members'=>array_map(fn($n)=>'incoming/'.$n,array_keys($bytes)),'checksumsSHA256'=>hash('sha256',$checksums),'wrapperSHA256'=>hash('sha256',$wrapper),'cssVersion'=>$cssVersion,'galleryVersion'=>$galleryVersion,'treeFilesAfter'=>DR::installedTreeFiles(),'excluded'=>$excluded,'retainedOrphans'=>array_keys(DR::ORPHANS),'directoriesTheInstallCreates'=>$dirList,'overlay'=>$overlay,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
