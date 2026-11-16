<?php
declare(strict_types=1);
use DashboardReportsInstallCoordinator as DR;
use AdminShellInstallCoordinator as AS54;
/**
 * Builds the B1155 operator ZIP with root-relative incoming/ members only. Everything is read from git at build time, never
 * from a working tree: each payload file is git show <SOURCE_COMMIT>:<path> (d2c07f62, origin/main, the merge of PR 193, 194, 196, 197 and 198: dashboard, dropdown menus, registration page, report of sales by subagent, safe redirects),
 * the pre-state is git show BASE_COMMIT:<path> (ad901a58, the B1154 payload commit: its apps/cms/current without tests is the live tree after the B1154 report 1120, and the main commit 6acf8aa9 that carries that report has the same
 * apps/cms/current), and the nine earlier overlays (B1146: eabe16af over 77a3efea; B1147: 764bf513 over eabe16af; B1148: 56082845 over 764bf513; B1149: 7e748158 over
 * eca7755c; B1150: 14e628ba over 7e748158; B1151: 43d3ab6e and ae00c5f6 over 14e628ba; B1152: 8505b110 and cfe97253 over 7e22afd4; B1153: d760bb2c over af1e46a3; B1154: ad901a58 over 482d2fa5) turn the 8531-file runtime
 * manifest into the live 8574-file tree. The payload is shipped as flat incoming/b1155-payload-NN.bin members, so unzip never writes into the live CMS tree. The build fails unless:
 * - BASE is an ancestor of SOURCE, the B1154 payload commit IS BASE, the B1153 payload commit is an ancestor of the B1154 code base, and BASE and SOURCE are different commits;
 * - git diff --name-status BASE SOURCE restricted to apps/cms/current, minus apps/cms/current/tests/ and apps/cms/current/docs/, is exactly the coded STEPS set (30 files: 9 new, 21 modified; nothing else, no delete;
 *   bin/static-check.php is in the runtime manifest, whose roots include bin/, so it ships; docs/openapi.yaml is not in the runtime manifest and does not ship); nothing in that diff, tests included, is under database/, config/ or composer.* or is .env,
 *   and under routes/ only routes/web.php changes (two GET routes: 568 to 570);
 * - every payload and pre-state digest equals the digest coded in DashboardReportsInstallCoordinator::DIGESTS (the reviewers' sha256 of the raw blobs);
 * - the B1146 to B1152 file sets equal the pinned coordinators' lists, each pre digest equals the live digest before it (manifest, or the earlier overlay's payload) and each new path is absent
 *   before; the B1153 and B1154 file sets, kinds and digests equal the pinned B1153 and B1154 STEPS and DIGESTS;
 * - production equals BASE: the whole apps/cms/current diff from the main commit 6acf8aa9 of Report 1120 to BASE is empty (tests included), so each B1155 pre digest (git blob at BASE) equals the LIVE digest
 *   by a second, independent source (B1154 payload, else B1153, B1152, B1151, B1150, B1149, B1148, B1147, B1146, else the runtime manifest; the source is printed per file), and each new path is absent everywhere;
 * - the backward compatibility facts hold in the shipped bytes: AppUrlOrigin and PublicStorefrontAppearance only gain members (every old line is still there, in order), ManagementReportService differs ONLY by the one date-range
 *   condition and the added reportOrderIds(), the admin stylesheet loses exactly the two dropdown panel rules it replaces, nothing at BASE names a new file or the new routes; and the order facts hold: every caller sits in a LATER group than what it
 *   calls (requests before controllers, libraries before callers, bootstrap/app.php after AppUrlOrigin, routes/web.php after its controller and after ManagementReportService, the management view after the routes), the old bytes of the
 *   callers do not yet use the new members;
 * - the B1149 markers are in the final bytes and in the pre-state bytes of the storefront files, and the storefront layout and stylesheet versions are the same before and after.
 * The B1134-B1154 helpers already on the host are verified by digest (package.json), not redelivered.
 * Besides the ZIP it writes b1155-digests.tsv (path, kind, group, source, pre digest, pre-state source, payload digest) next to it.
 * Usage: php package.php <repo root> <b1155 dir> <b1134 dir> <b1144 dir> <b1145 dir> <b1146 dir> <b1147 dir> <b1148 dir> <b1149 dir> <b1150 dir> <b1151 dir> <b1152 dir> <b1153 dir> <b1154 dir> <runtime-manifest.json> <output dir> <helper source commit> <source commit>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$repo,$tools,$b1134,$b1144,$b1145,$b1146,$b1147,$b1148,$b1149,$b1150,$b1151,$b1152,$b1153,$b1154,$manifestPath,$out,$helper,$srcArg]=$argv+array_fill(0,19,null);
if(!is_dir((string)$repo.'/.git')&&!is_file((string)$repo.'/.git'))throw new RuntimeException('PACKAGE_REPO');if(!is_dir((string)$tools)||!is_dir((string)$b1152)||!is_dir((string)$b1153)||!is_dir((string)$b1154)||!is_file((string)$manifestPath)||!preg_match('/^[a-f0-9]{40}$/D',(string)$helper)||!preg_match('/^[a-f0-9]{40}$/D',(string)$srcArg))throw new RuntimeException('PACKAGE_ARGUMENTS');if(!is_dir($out)&&!mkdir($out,0755,true))throw new RuntimeException('PACKAGE_OUTPUT');
require $b1134.'/InstallFileOps.php';require $b1144.'/FreshnessProbe.php';require $b1144.'/LeanGates.php';require $b1144.'/LeanWeb.php';require $b1145.'/EnvFlags.php';require $b1145.'/MigrateUnclampCoordinator.php';require $b1146.'/OverlayProbe.php';require $b1146.'/OverlayWeb.php';require $b1146.'/OverlayCoordinator.php';require $b1147.'/CatalogWeb.php';require $b1147.'/CatalogOverlayCoordinator.php';require $b1148.'/GlassWeb.php';require $b1148.'/GlassOverlayCoordinator.php';require $b1149.'/StorefrontInstallCoordinator.php';require $b1150.'/MediaWeb.php';require $b1150.'/MediaInstallCoordinator.php';require $b1151.'/LegalAccountInstallCoordinator.php';require $b1152.'/OriginPinInstallCoordinator.php';require $b1153.'/LoadProbe.php';require $b1153.'/LogoCatalogInstallCoordinator.php';require $b1154.'/AdminShellInstallCoordinator.php';require $tools.'/DashboardReportsInstallCoordinator.php';
if(DR::STEPS===[])throw new RuntimeException('B1155_PAYLOAD_NOT_FILLED_IN');
const CMS='apps/cms/current/';const B1146_BASE='77a3efea';const INSTALLED='ef3f927b77ba573881c2b6dba341732c417e6725';
// The main commit that carries Report 1120 (the B1154 PASS): its apps/cms/current is the live tree plus the tests the installers never ship.
const REPORT_MAIN='6acf8aa917c70751f88279845f08b7a90ff49e35';
const MARKERS=DR::MARKERS;
$base=DR::BASE_COMMIT;$payloadCommit=DR::SOURCE_COMMIT;$b1154Base=DR::B1154_BASE_COMMIT;$b1154Commit=DR::B1154_COMMIT;$b1153Base=DR::B1153_BASE_COMMIT;$b1153Commit=DR::B1153_COMMIT;$b1152Base=DR::B1152_BASE_COMMIT;$b1152Url=DR::B1152_URL_COMMIT;$b1152Pdf=DR::B1152_PDF_COMMIT;$b1152Main=DR::B1152_MAIN_COMMIT;$b1151Email=DR::B1151_EMAIL_COMMIT;$b1151Throttle=DR::B1151_THROTTLE_COMMIT;$b1150Source=DR::B1150_COMMIT;$store=DR::STOREFRONT_COMMIT;$storeBase=DR::STOREFRONT_BASE_COMMIT;$storeMain=DR::STOREFRONT_MAIN_COMMIT;$glass=DR::GLASS_COMMIT;$catalog=DR::CATALOG_COMMIT;$legal=DR::LEGAL_COMMIT;
// The workflow passes the source it reviewed; it must be the commit the coordinator is pinned to.
if($srcArg!==$payloadCommit)throw new RuntimeException('PACKAGE_SOURCE_ARGUMENT_DIFFERS_FROM_THE_PINNED_SOURCE');
$git=static function(array $args,bool $allowFail=false)use($repo):?string{$p=proc_open(array_merge(['git','-C',$repo,'-c','core.autocrlf=false'],$args),[1=>['pipe','w'],2=>['pipe','w']],$pipes);$o=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0){if($allowFail)return null;throw new RuntimeException('PACKAGE_GIT_'.$args[0]);}return (string)$o;};
$blob=static fn(string $commit,string $path):string=>(string)$git(['show',$commit.':'.CMS.$path]);
foreach([$payloadCommit,$base,$b1154Commit,$b1154Base,$b1153Commit,$b1153Base,$b1152Url,$b1152Pdf,$b1152Base,$b1152Main,$b1151Email,$b1151Throttle,$b1150Source,$store,$storeBase,$storeMain,$glass,$catalog,$legal,INSTALLED,REPORT_MAIN] as $c)if(trim((string)$git(['rev-parse','--verify',$c.'^{commit}']))!==$c)throw new RuntimeException('PACKAGE_COMMIT_'.substr($c,0,8));
if($legal!==OverlayCoordinator::SOURCE_COMMIT||$catalog!==CatalogOverlayCoordinator::SOURCE_COMMIT||$legal!==CatalogOverlayCoordinator::BASE_COMMIT||$glass!==GlassOverlayCoordinator::SOURCE_COMMIT||$catalog!==GlassOverlayCoordinator::BASE_COMMIT||$legal!==GlassOverlayCoordinator::MAIN_COMMIT)throw new RuntimeException('PACKAGE_CHAIN_COMMITS_DIFFER_FROM_THE_PINNED_COORDINATORS');
if($store!==StorefrontInstallCoordinator::SOURCE_COMMIT||$storeBase!==StorefrontInstallCoordinator::BASE_COMMIT||$storeMain!==StorefrontInstallCoordinator::MAIN_COMMIT||$glass!==StorefrontInstallCoordinator::GLASS_COMMIT||$catalog!==StorefrontInstallCoordinator::CATALOG_COMMIT||$legal!==StorefrontInstallCoordinator::LEGAL_COMMIT)throw new RuntimeException('PACKAGE_B1149_COMMITS_DIFFER_FROM_THE_PINNED_B1149_COORDINATOR');
if($b1150Source!==MediaInstallCoordinator::SOURCE_COMMIT||$store!==MediaInstallCoordinator::BASE_COMMIT||MediaInstallCoordinator::MEDIA_ROUTE_NAME!==DR::MEDIA_ROUTE_NAME)throw new RuntimeException('PACKAGE_B1150_COMMITS_DIFFER_FROM_THE_PINNED_B1150_COORDINATOR');
if($b1151Email!==LegalAccountInstallCoordinator::EMAIL_COMMIT||$b1151Throttle!==LegalAccountInstallCoordinator::THROTTLE_COMMIT||$b1150Source!==LegalAccountInstallCoordinator::BASE_COMMIT)throw new RuntimeException('PACKAGE_B1151_COMMITS_DIFFER_FROM_THE_PINNED_B1151_COORDINATOR');
if($b1152Url!==OriginPinInstallCoordinator::URL_COMMIT||$b1152Pdf!==OriginPinInstallCoordinator::PDF_COMMIT||$b1152Base!==OriginPinInstallCoordinator::BASE_COMMIT||$b1152Main!==OriginPinInstallCoordinator::MAIN_COMMIT||$b1151Email!==OriginPinInstallCoordinator::B1151_EMAIL_COMMIT||$b1151Throttle!==OriginPinInstallCoordinator::B1151_THROTTLE_COMMIT||$b1150Source!==OriginPinInstallCoordinator::B1150_COMMIT||DR::B1152_PASS!==OriginPinInstallCoordinator::FINAL_PASS||DR::ROUTES_BEFORE!==OriginPinInstallCoordinator::ROUTES||DR::EXPECTED_APP_URL!==OriginPinInstallCoordinator::EXPECTED_APP_URL)throw new RuntimeException('PACKAGE_B1152_COMMITS_DIFFER_FROM_THE_PINNED_B1152_COORDINATOR');
if($b1153Commit!==LogoCatalogInstallCoordinator::SOURCE_COMMIT||$b1153Base!==LogoCatalogInstallCoordinator::BASE_COMMIT||DR::B1153_PASS!==LogoCatalogInstallCoordinator::FINAL_PASS||DR::ROUTES_BEFORE!==LogoCatalogInstallCoordinator::ROUTES||DR::EXPECTED_APP_URL!==LogoCatalogInstallCoordinator::EXPECTED_APP_URL||DR::MANIFEST_SHA!==LogoCatalogInstallCoordinator::MANIFEST_SHA||DR::B1153_TREE_FILES!==LogoCatalogInstallCoordinator::installedTreeFiles()||$b1152Url!==LogoCatalogInstallCoordinator::B1152_URL_COMMIT||$b1152Pdf!==LogoCatalogInstallCoordinator::B1152_PDF_COMMIT||$b1152Base!==LogoCatalogInstallCoordinator::B1152_BASE_COMMIT)throw new RuntimeException('PACKAGE_B1153_COMMITS_DIFFER_FROM_THE_PINNED_B1153_COORDINATOR');
if($b1154Commit!==AS54::SOURCE_COMMIT||$b1154Base!==AS54::BASE_COMMIT||$b1153Commit!==AS54::B1153_COMMIT||$b1153Base!==AS54::B1153_BASE_COMMIT||DR::B1154_PASS!==AS54::FINAL_PASS||DR::ROUTES_BEFORE!==AS54::ROUTES||DR::EXPECTED_APP_URL!==AS54::EXPECTED_APP_URL||DR::MANIFEST_SHA!==AS54::MANIFEST_SHA||DR::B1154_TREE_FILES!==AS54::installedTreeFiles()||DR::B1154_FILES!==count(AS54::STEPS)||$base!==$b1154Commit)throw new RuntimeException('PACKAGE_B1154_COMMITS_DIFFER_FROM_THE_PINNED_B1154_COORDINATOR');
if($payloadCommit===$base||$b1154Base===$base||$b1154Base===$b1153Commit||$b1153Commit===$b1153Base||$b1152Url===$b1152Pdf||$b1152Url===$b1152Base||$b1152Pdf===$b1152Base)throw new RuntimeException('PACKAGE_HEADS_NOT_DISTINCT');
foreach([[$legal,$catalog],[$catalog,$glass],[$storeBase,$store],[$store,$b1150Source],[$b1150Source,$b1151Email],[$b1150Source,$b1151Throttle],[$b1152Base,$b1152Url],[$b1152Base,$b1152Pdf],[$b1152Url,$b1153Base],[$b1152Pdf,$b1153Base],[$b1152Base,$b1153Base],[$b1153Base,$b1153Commit],[$b1153Commit,$b1154Base],[$b1154Base,$b1154Commit],[$b1154Commit,$base],[$base,REPORT_MAIN],[REPORT_MAIN,$payloadCommit],[$base,$payloadCommit]] as [$a,$b])if($git(['merge-base','--is-ancestor',$a,$b],true)===null)throw new RuntimeException('PACKAGE_NOT_AN_ANCESTOR_'.substr($a,0,8).'_'.substr($b,0,8));
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
    if(hash('sha256',$blob($base,$p))!==$sha)throw new RuntimeException('PACKAGE_B1154_PAYLOAD_NOT_IN_THE_CODE_BASE_'.$p);
    $b1154Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1154Sha[$p]=$sha;
}
// Production equals BASE: the B1153 payload commit d760bb2c and the B1154 code base 482d2fa5 have the SAME apps/cms/current (tests and bin included), and the main commit 6acf8aa9 of Report1120 has the SAME apps/cms/current as BASE
// (BASE is the B1154 payload commit ad901a58), so BASE is the live tree plus the tests the installers never ship.
if($git(['diff','--quiet',$b1153Commit,$b1154Base,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_B1154_CODE_BASE_DIFFERS_FROM_THE_B1153_PAYLOAD_COMMIT');
if($git(['diff','--quiet',REPORT_MAIN,$base,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_PRODUCTION_BASE_DIFFERS_FROM_THE_REPORT1120_MAIN_COMMIT');
if($git(['diff','--quiet',$b1154Commit,$base,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_PRODUCTION_BASE_DIFFERS_FROM_THE_B1154_PAYLOAD_COMMIT');

// The B1155 file set: the CMS-scoped diff BASE..SOURCE minus the CMS tests and the CMS docs; nothing else may remain (bin/static-check.php stays: it is a file of the runtime manifest, whose roots include bin/, so the tree must follow main;
// docs/openapi.yaml is not in the runtime manifest, so it does not ship). Nothing in the whole CMS diff (tests included) may touch a migration, the config, composer or .env; under routes/ only routes/web.php may change.
$excluded=[];$expected=[];foreach(DR::STEPS as [$p,$kind])$expected[$p]=$kind==='new'?'A':'M';ksort($expected,SORT_STRING);
$whole=$diff($base,$payloadCommit);foreach(array_keys($whole) as $p){
    if(preg_match('~^'.preg_quote(CMS,'~').'(database/|config/|composer\.|\.env)~',$p)===1)throw new RuntimeException('PACKAGE_MIGRATION_CONFIG_COMPOSER_CHANGE_STOP_AND_REPORT_'.$p);
    if(str_starts_with($p,CMS.'routes/')&&$p!==CMS.'routes/web.php')throw new RuntimeException('PACKAGE_ROUTE_FILE_OTHER_THAN_WEB_PHP_CHANGE_STOP_AND_REPORT_'.$p);
    if(str_starts_with($p,CMS.'tests/')||str_starts_with($p,CMS.'docs/')){$excluded[]=$p;unset($whole[$p]);}
}
$perSource=$cms($whole);
if($perSource!==$expected){fwrite(STDERR,json_encode(['diff'=>$perSource,'expected'=>$expected],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);throw new RuntimeException('PACKAGE_FILE_SET_DIFFERS_FROM_THE_SOURCE_DIFF');}
if(count(DR::STEPS)!==count($expected))throw new RuntimeException('PACKAGE_STEPS_HAVE_A_DUPLICATE_PATH');
if(count($expected)!==30||count(array_filter($expected,static fn($k)=>$k==='A'))!==9)throw new RuntimeException('PACKAGE_STEPS_NOT_30_FILES_9_NEW');
foreach(array_keys($expected) as $p)if(preg_match('~^(database/|config/|composer\.|\.env|tests/|docs/)~',$p)||(str_starts_with($p,'routes/')&&$p!=='routes/web.php')||(str_starts_with($p,'bin/')&&$p!=='bin/static-check.php'))throw new RuntimeException('PACKAGE_MIGRATION_CONFIG_COMPOSER_TEST_DOC_OR_FOREIGN_ROUTE_CHANGE_STOP_AND_REPORT_'.$p);
// docs/openapi.yaml changed in the source diff but is documentation: it must be left out of the payload and must not be a file of the runtime manifest.
if(!in_array(CMS.'docs/openapi.yaml',$excluded,true)||isset($byPath['docs/openapi.yaml'])||isset($expected['docs/openapi.yaml']))throw new RuntimeException('PACKAGE_OPENAPI_YAML_MUST_BE_EXCLUDED_AND_NOT_IN_THE_RUNTIME_MANIFEST');
// bin/static-check.php is shipped because the runtime manifest the host tree is verified against contains it, so leaving it out would leave the tree different from main.
if(!isset($byPath['bin/static-check.php'])||!isset($expected['bin/static-check.php']))throw new RuntimeException('PACKAGE_STATIC_CHECK_NOT_THE_MANIFEST_FILE');

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
    'b1154-app-check.php'=>[$b1154.'/AppCheck.php','6da48e7998be940c4cca83f5afbe82c59c79121c036ac4efaf2e02cb08037385'],'b1154-coordinator.php'=>[$b1154.'/AdminShellInstallCoordinator.php','51dd96f6798bbebc74def1d1ddaed9f85a632ea93abcbe22b2ffd8866a05d016']];
foreach($pins as $name=>[$file,$digest])if(hash_file('sha256',$file)!==$digest)throw new RuntimeException('PINNED_DEPENDENCY_DRIFT_'.$name);
$members=['b1155-installer.php'=>'operator-entry.php','b1155-coordinator.php'=>'DashboardReportsInstallCoordinator.php','b1155-app-check.php'=>'AppCheck.php','b1155-README-SR.md'=>'README-SR.md'];
$bytes=[];foreach($members as $name=>$file){$data=file_get_contents($tools.'/'.$file);if(!is_string($data)||str_contains($data,"\r"))throw new RuntimeException('PACKAGE_SOURCE_BYTES_'.$name);$bytes[$name]=$data;}
// The shipped tool code must not reach cPanel, reset OPcache globally, overlay PHP flags, migrate, seed, build any cache, POST or
// send a custom method, send mail, or write the DB switch or any row. route:clear and queue:restart are the only allowed artisan
// cache commands (coded once each, in refreshViews); view:clear is coded once more in the recovery block only.
foreach($bytes as $name=>$data)if(str_ends_with($name,'.php')&&preg_match('~/usr/local/cpanel|uapi|DomainInfo|opcache_reset|php_flag|HostProcessGuard|--seed|migrate:fresh|migrate:rollback|migrate:refresh|migrate:reset|db:wipe|\'migrate\'|config:cache|view:cache|route:cache|public_storefront_open|->update\(|->insert\(|->upsert\(|->delete\(|->set\(|->put\(|Mail::|mail\(|CURLOPT_POST|CURLOPT_CUSTOMREQUEST|mail:test|app:send-test-mail~i',$data))throw new RuntimeException('PACKAGE_FORBIDDEN_MECHANISM_'.$name);
if(substr_count($bytes['b1155-coordinator.php'],"artisanOk(['route:clear'])")!==1||substr_count($bytes['b1155-coordinator.php'],"artisanOk(['queue:restart'])")!==1)throw new RuntimeException('PACKAGE_ARTISAN_CACHE_COMMANDS');
// The password endpoints are never requested by the shipped tool (no login, no PUT, no POST): none of their paths may appear in the shipped code.
foreach($bytes as $name=>$data)if(str_ends_with($name,'.php')&&preg_match('~/account/password|/me/password|/auth/password|password/email|forgot-password(?!\.blade\.php)~',$data))throw new RuntimeException('PACKAGE_PASSWORD_ENDPOINT_IN_THE_TOOL_'.$name);

$overlay=[];$new=[];$old=[];$digests="n\tgroup\tkind\tpath\tsource\tpre_sha256_git_".substr($base,0,8)."\tpre_state_source\tpayload_sha256_git_source\n";
foreach(DR::STEPS as $i=>[$path,$kind,$group,$s1]){
    $n=$i+1;$commit=DR::sourceCommit($s1);$payload=$blob($commit,$path);$pre=$kind==='modified'?$blob($base,$path):null;$new[$path]=$payload;$old[$path]=$pre;$liveBefore='ABSENT';
    if($kind==='new'&&($git(['cat-file','-e',$base.':'.CMS.$path],true)!==null||isset($byPath[$path])||isset($b1146Sha[$path])||isset($b1147Sha[$path])||isset($b1148Sha[$path])||isset($b1149Sha[$path])||isset($b1150Sha[$path])||isset($b1151Sha[$path])||isset($b1152Sha[$path])||isset($b1153Sha[$path])||isset($b1154Sha[$path])))throw new RuntimeException('PACKAGE_NEW_PATH_EXISTS_'.$n);
    if($kind==='modified'){$preSha=hash('sha256',(string)$pre);[$live,$liveBefore]=isset($b1154Sha[$path])?[$b1154Sha[$path],'B1154_PAYLOAD']:(isset($b1153Sha[$path])?[$b1153Sha[$path],'B1153_PAYLOAD']:(isset($b1152Sha[$path])?[$b1152Sha[$path],'B1152_PAYLOAD']:(isset($b1151Sha[$path])?[$b1151Sha[$path],'B1151_PAYLOAD']:(isset($b1150Sha[$path])?[$b1150Sha[$path],'B1150_PAYLOAD']:(isset($b1149Sha[$path])?[$b1149Sha[$path],'B1149_PAYLOAD']:(isset($b1148Sha[$path])?[$b1148Sha[$path],'B1148_PAYLOAD']:(isset($b1147Sha[$path])?[$b1147Sha[$path],'B1147_PAYLOAD']:(isset($b1146Sha[$path])?[$b1146Sha[$path],'B1146_PAYLOAD']:[$byPath[$path]??null,'RUNTIME_MANIFEST']))))))));if($live!==$preSha)throw new RuntimeException('PACKAGE_PRE_STATE_NOT_LIVE_'.$n.'_'.$path);}
    if(str_contains($payload,"\r"))throw new RuntimeException('PACKAGE_PAYLOAD_CR_'.$n);
    // The reviewers' digests (raw git blob bytes), coded in the coordinator's payload block: any other bytes are refused here and on the host.
    $dig=DR::DIGESTS[$path]??null;if($dig===null||$dig[1]!==hash('sha256',$payload)||$dig[0]!==($pre===null?null:hash('sha256',$pre)))throw new RuntimeException('PACKAGE_DIGEST_DIFFERS_FROM_THE_REVIEWED_ONE_'.$n.'_'.$path);
    $member=sprintf('b1155-payload-%02d.bin',$n);$bytes[$member]=$payload;$overlay[]=['n'=>$n,'path'=>$path,'kind'=>$kind,'group'=>$group,'source'=>$commit,'payload'=>$member,'payloadSHA256'=>hash('sha256',$payload),'preSHA256'=>$pre===null?null:hash('sha256',$pre),'size'=>strlen($payload),'liveBefore'=>$liveBefore];
    $digests.=$n."\t".$group."\t".$kind."\t".$path."\t".substr($commit,0,8)."\t".($pre===null?'ABSENT':hash('sha256',$pre))."\t".$liveBefore."\t".hash('sha256',$payload)."\n";
}
if(count(DR::DIGESTS)!==count(DR::STEPS))throw new RuntimeException('PACKAGE_DIGESTS_NOT_ONE_PER_STEP');
// The paths of the payload (short names for the facts below).
['lar'=>$lar,'car'=>$car,'svc'=>$svc,'ctl'=>$ctl,'regcss'=>$regcss,'kpi'=>$kpi,'pri'=>$pri,'tf'=>$tf,'sub'=>$sub,'url'=>$url,'app'=>$app,'mgmt'=>$mgmt,'login'=>$login,'forgot'=>$forgot,'reset'=>$reset,'asc'=>$asc,'prc'=>$prc,'cac'=>$cac,'csp'=>$csp,'admincss'=>$admincss,'check'=>$check,'boot'=>$boot,'oo'=>$oo,'cc'=>$cc,'idx'=>$idxView,'lay'=>$lay,'pl'=>$pl,'reg'=>$reg,'routes'=>$routes,'mv'=>$mv]=[
    'lar'=>'app/Http/Requests/LocalAuthFormRequest.php','car'=>'app/Http/Requests/CustomerActivationRequest.php','svc'=>'app/Services/SubagentSalesReportService.php','ctl'=>'app/Http/Controllers/Admin/SubagentSalesReportController.php','regcss'=>'public/assets/css/ald1n-public-register.css','kpi'=>'resources/views/dashboard/partials/kpi-delta.blade.php','pri'=>'resources/views/dashboard/partials/priorities.blade.php','tf'=>'resources/views/public-shop/partials/theme-form.blade.php','sub'=>'resources/views/admin/reports/subagent-sales.blade.php',
    'url'=>'app/Support/AppUrlOrigin.php','app'=>'app/Support/PublicStorefrontAppearance.php','mgmt'=>'app/Services/ManagementReportService.php','login'=>'app/Http/Requests/LoginRequest.php','forgot'=>'app/Http/Requests/ForgotPasswordRequest.php','reset'=>'app/Http/Requests/ResetPasswordRequest.php','asc'=>'app/Http/Controllers/Auth/AuthenticatedSessionController.php','prc'=>'app/Http/Controllers/Auth/PasswordResetController.php','cac'=>'app/Http/Controllers/Auth/CustomerActivationController.php','csp'=>'app/Http/Middleware/PublicNoScriptCsp.php','admincss'=>'public/assets/css/ald1n-admin.css','check'=>'bin/static-check.php',
    'boot'=>'bootstrap/app.php','oo'=>'resources/views/dashboard/partials/operator-orders.blade.php','cc'=>'resources/views/dashboard/partials/customer-center.blade.php','idx'=>'resources/views/dashboard/index.blade.php','lay'=>'resources/views/layouts/app.blade.php','pl'=>'resources/views/public-shop/layout.blade.php','reg'=>'resources/views/public-shop/register.blade.php','routes'=>'routes/web.php','mv'=>'resources/views/admin/reports/management.blade.php'];
foreach([$lar,$car,$svc,$ctl,$regcss,$kpi,$pri,$tf,$sub,$url,$app,$mgmt,$login,$forgot,$reset,$asc,$prc,$cac,$csp,$admincss,$check,$boot,$oo,$cc,$idxView,$lay,$pl,$reg,$routes,$mv] as $p)if(!isset($new[$p]))throw new RuntimeException('PACKAGE_STEP_MISSING_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/','_',$p)));
// The storefront is mostly not in this payload: the B1149 markers hold in the bytes at BASE (the live tree), the stylesheets and the catalog views are the same at BASE and at SOURCE; the storefront LAYOUT is in the payload (its theme
// buttons move into the theme-form partial), so its marker is checked in the layout plus the partial; the stylesheet versions are the B1153 ones.
$storeLayout=$pl;$css='public/assets/css/ald1n-public-storefront.css';$gallery='public/assets/css/ald1n-public-gallery.css';$catalogView='resources/views/public-shop/catalog.blade.php';$show='resources/views/public-shop/show.blade.php';
foreach([$css,$gallery,$catalogView,$show] as $p)if(isset($new[$p])||$blob($base,$p)!==$blob($payloadCommit,$p))throw new RuntimeException('PACKAGE_STOREFRONT_FILE_IN_THE_PAYLOAD_OR_CHANGED_'.$p);
foreach([['css',$css],['gallery',$gallery],['catalog',$catalogView],['catalog',$show]] as [$k,$p])if(!str_contains($blob($base,$p),MARKERS[$k]))throw new RuntimeException('PACKAGE_MARKER_'.strtoupper($k).'_'.$p);
if(!str_contains($old[$storeLayout],MARKERS['layout'])||!str_contains($new[$storeLayout].$new[$tf],MARKERS['layout']))throw new RuntimeException('PACKAGE_MARKER_LAYOUT_'.$storeLayout);
$version=static function(string $text,string $file):string{if(preg_match_all('~'.preg_quote($file,'~').'\?v=([0-9]{1,12})[\'"]~',$text,$v)!==1)throw new RuntimeException('PACKAGE_CSS_VERSION');return $v[1][0];};
$cssVersion=$version($old[$storeLayout],'ald1n-public-storefront.css');$oldCssVersion=$version($new[$storeLayout],'ald1n-public-storefront.css');
if($oldCssVersion!=='202611141'||$cssVersion!=='202611141')throw new RuntimeException('PACKAGE_STOREFRONT_CSS_VERSION_NOT_202611141_'.$oldCssVersion.'_'.$cssVersion);
$galleryVersion=$version($blob($base,$catalogView),'ald1n-public-gallery.css');if($galleryVersion!==$version($blob($base,$show),'ald1n-public-gallery.css'))throw new RuntimeException('PACKAGE_GALLERY_VERSION_DIFFERS');
$registerCssVersion=$version($new[$reg],'ald1n-public-register.css');if($registerCssVersion!=='202611151')throw new RuntimeException('PACKAGE_REGISTER_CSS_VERSION_NOT_202611151_'.$registerCssVersion);
// BACKWARD COMPATIBILITY, proven on the shipped bytes (the OLD callers must keep working between the groups):
// 1. AppUrlOrigin and PublicStorefrontAppearance only GAIN members: every line of the old file is still in the new file, in the same order (nothing removed, nothing changed), so every old caller and every old signature is untouched.
$pure=static function(string $o,string $n):bool{$o=explode("\n",$o);$n=explode("\n",$n);$j=0;$m=count($n);foreach($o as $line){while($j<$m&&$n[$j]!==$line)$j++;if($j>=$m)return false;$j++;}return true;};
foreach([$url,$app] as $p)if(!$pure((string)$old[$p],$new[$p])||strlen($new[$p])<=strlen((string)$old[$p]))throw new RuntimeException('PACKAGE_NOT_BACKWARD_COMPATIBLE_ONLY_ADDED_MEMBERS_MAY_DIFFER_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/','_',$p)));
if(substr_count($new[$url],'public static function ')!==substr_count((string)$old[$url],'public static function ')+1||substr_count($new[$app],'function ')!==substr_count((string)$old[$app],'function ')+2)throw new RuntimeException('PACKAGE_UNEXPECTED_NUMBER_OF_ADDED_MEMBERS');
// 2. ManagementReportService differs ONLY by the one date-range condition (whereDate on both bounds becomes the sargable range, identical rows for Y-m-d bounds) and by the added reportOrderIds(): undoing the condition leaves a pure addition.
$mgmtOldLine="        \$query->whereDate(\$dateColumn, '>=', \$filters['date_from'])->whereDate(\$dateColumn, '<=', \$filters['date_to']);";
$mgmtNewLines="        // Sargable day range (same rows as whereDate on both bounds, but it can use the date index).\n        \$query->where(\$dateColumn, '>=', \$filters['date_from'].' 00:00:00')\n            ->where(\$dateColumn, '<', CarbonImmutable::parse(\$filters['date_to'])->addDay()->format('Y-m-d').' 00:00:00');";
if(substr_count((string)$old[$mgmt],$mgmtOldLine)!==1||substr_count($new[$mgmt],$mgmtNewLines)!==1||substr_count($new[$mgmt],'whereDate(')!==substr_count((string)$old[$mgmt],'whereDate(')-2||!str_contains((string)$old[$mgmt],'use Carbon\CarbonImmutable;'))throw new RuntimeException('PACKAGE_MANAGEMENT_SERVICE_DATE_CONDITION_NOT_THE_REVIEWED_ONE');
if(!$pure((string)$old[$mgmt],str_replace($mgmtNewLines,$mgmtOldLine,$new[$mgmt]))||substr_count($new[$mgmt],'function ')!==substr_count((string)$old[$mgmt],'function ')+1)throw new RuntimeException('PACKAGE_MANAGEMENT_SERVICE_NOT_BACKWARD_COMPATIBLE_ONLY_THE_DATE_CONDITION_AND_REPORT_ORDER_IDS_MAY_DIFFER');
// 3. The admin stylesheet loses exactly the two dropdown panel rules it replaces (the base rule with the solid surface, the narrow-screen height cap); everything else of the old file is kept.
$cssGone=array_values(array_diff(explode("\n",(string)$old[$admincss]),explode("\n",$new[$admincss])));
if(count($cssGone)!==2||count(array_filter($cssGone,static fn($l)=>str_starts_with($l,'  .a1-nav-dd__panel{')))!==2)throw new RuntimeException('PACKAGE_ADMIN_CSS_MAY_ONLY_REPLACE_THE_TWO_DROPDOWN_PANEL_RULES');
// 4. Pre-state facts (negative): the OLD bytes of the callers do not use the new members and the old requests do not extend the new parent.
foreach([[$login,'LocalAuthFormRequest'],[$forgot,'LocalAuthFormRequest'],[$reset,'LocalAuthFormRequest'],[$asc,'localRedirect'],[$prc,'localRedirect'],[$cac,'localRedirect'],[$cac,'CustomerActivationRequest'],[$url,'redirect('],[$app,'loginBackground('],[$app,'resolveLoginBackground('],[$mgmt,'reportOrderIds('],[$boot,'AppUrlOrigin::redirect('],[$csp,'loginBackground'],[$routes,'SubagentSales'],[$routes,'reports.subagents'],[$mv,'admin.reports.subagents'],[$reg,'authShell'],[$pl,'authShell'],[$pl,'theme-form'],[$idxView,'kpi-delta'],[$idxView,'partials.priorities']] as [$p,$needle])if(!is_string($old[$p])||str_contains($old[$p],$needle))throw new RuntimeException('PACKAGE_PRE_STATE_ALREADY_HAS_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/','_',$p.'_'.$needle)));
foreach([$login,$forgot,$reset] as $p)if(!str_contains($old[$p],'extends FormRequest')||!str_contains($new[$p],'extends LocalAuthFormRequest'))throw new RuntimeException('PACKAGE_REQUEST_PARENT_FACT_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/','_',$p)));
// 5. The new files are inert until linked: nothing in the tree at BASE (app, bootstrap, resources, public, routes, config) names the new classes, views, stylesheet or routes.
if(trim((string)$git(['grep','-l','-E','LocalAuthFormRequest|CustomerActivationRequest|SubagentSales|ald1n-public-register|public-shop\.partials|dashboard\.partials\.(kpi-delta|priorities)|reports\.subagents',$base,'--','apps/cms/current/app','apps/cms/current/bootstrap','apps/cms/current/resources','apps/cms/current/public','apps/cms/current/routes','apps/cms/current/config'],true))!=='')throw new RuntimeException('PACKAGE_OLD_TREE_ALREADY_NAMES_THE_NEW_FILES');
// 6. The routes: the payload routes file adds exactly the two new named GET routes (each name once, none in the pre-state), the coordinator's counts agree (568 before, 570 after).
if(count(DR::NEW_ROUTE_NAMES)!==2||DR::ROUTES_BEFORE!==568||DR::ROUTES_AFTER!==570||DR::ROUTES_AFTER!==DR::ROUTES_BEFORE+count(DR::NEW_ROUTE_NAMES))throw new RuntimeException('PACKAGE_ROUTE_COUNTS_NOT_568_TO_570');
foreach(DR::NEW_ROUTE_NAMES as $name){if(!str_starts_with($name,'admin.'))throw new RuntimeException('PACKAGE_ROUTE_NAME_NOT_UNDER_ADMIN_'.$name);$tail=substr($name,6);if(substr_count($new[$routes],"->name('".$tail."')")!==1||substr_count((string)$old[$routes],"->name('".$tail."')")!==0)throw new RuntimeException('PACKAGE_ROUTE_NAME_NOT_ADDED_EXACTLY_ONCE_'.$name);}
if(substr_count($new[$routes],'Route::get(')!==substr_count((string)$old[$routes],'Route::get(')+2||substr_count($new[$routes],'Route::')!==substr_count((string)$old[$routes],'Route::')+3)throw new RuntimeException('PACKAGE_ROUTES_FILE_ADDS_MORE_THAN_THE_TWO_GET_ROUTES_AND_THEIR_GROUP');
if(!$pure((string)$old[$routes],$new[$routes]))throw new RuntimeException('PACKAGE_ROUTES_FILE_NOT_ONLY_ADDITIONS');
// The order facts behind the install order, in the shipped bytes: needle present in the new bytes, absent from the old where the file is modified.
$facts=[$lar=>['abstract class LocalAuthFormRequest','function localRedirect(','AppUrlOrigin::redirect('],$car=>['extends LocalAuthFormRequest'],$svc=>['reportOrderIds(','final class SubagentSalesReportService'],$ctl=>['SubagentSalesReportService'],
    $regcss=>['.reg-theme','public-auth'],$sub=>['admin.reports.subagents.csv'],$url=>['public static function redirect('],$app=>['public static function loginBackground(','private static function resolveLoginBackground('],
    $mgmt=>['public function reportOrderIds(','->where($dateColumn, \'<\', CarbonImmutable::parse('],$login=>['extends LocalAuthFormRequest'],$forgot=>['extends LocalAuthFormRequest'],$reset=>['extends LocalAuthFormRequest'],
    $asc=>['$request->localRedirect()'],$prc=>['$request->localRedirect()'],$cac=>['$request->localRedirect()','CustomerActivationRequest $request'],$csp=>['PublicStorefrontAppearance::loginBackground()'],$admincss=>['.a1-nav-dd__panel--cols'],$check=>['partials/priorities.blade.php'],
    $boot=>['AppUrlOrigin::redirect(','use App\Support\AppUrlOrigin;'],$idxView=>["@include('dashboard.partials.kpi-delta'","@include('dashboard.partials.priorities'"],$lay=>['a1-nav-dd__panel--cols'],
    $pl=>["@include('public-shop.partials.theme-form')",'$authShell'],$reg=>["['authShell' => true]",'ald1n-public-register.css','PublicStorefrontAppearance::loginBackground()'],
    $routes=>["->name('reports.subagents')","->name('reports.subagents.csv')",'use App\Http\Controllers\Admin\SubagentSalesReportController;'],$mv=>["route('admin.reports.subagents')"]];
foreach($facts as $p=>$needles)foreach($needles as $needle)if(!isset($new[$p])||!str_contains($new[$p],$needle)||(($old[$p]??null)!==null&&str_contains($old[$p],$needle)))throw new RuntimeException('PACKAGE_ORDER_FACT_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/','_',$p.'_'.$needle)));
// The dependency edges: [caller, callee]. Every caller sits in a LATER group than what it calls, so a request never meets a new caller over an old callee. The explicit edges (strict: a higher group) come first, then the edges that may
// share a group when the callee is EARLIER in the install order, then the edges read from the bytes (use statements of the PHP files, @include of the views). SubagentSalesReportService and SubagentSalesReportController are group A and inert: nothing
// at BASE names them, and only routes/web.php (group F, after ManagementReportService of group B, which supplies reportOrderIds()) can reach them, so the strict edges are routes/web.php after both and after ManagementReportService.
$idx=[];$grp=[];foreach(DR::STEPS as $i=>[$p,,$g]){$idx[$p]=$i;$grp[$p]=$g;}
$code=static fn(string $c,string $e):string=>'PACKAGE_ORDER_EDGE_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/','_',$c.'_AFTER_'.$e));
$strict=[[$login,$lar],[$forgot,$lar],[$reset,$lar],[$login,$url],[$forgot,$url],[$reset,$url],[$cac,$url],[$cac,$car],[$cac,$lar],[$asc,$lar],[$prc,$lar],[$csp,$app],[$boot,$url],[$boot,$app],
    [$idxView,$kpi],[$idxView,$pri],[$lay,$admincss],[$pl,$tf],[$reg,$app],[$reg,$regcss],[$reg,$tf],[$routes,$ctl],[$routes,$svc],[$routes,$mgmt],[$routes,$sub],[$mv,$routes],[$mv,$sub]];
foreach($strict as [$caller,$callee])if(!isset($grp[$caller],$grp[$callee])||$grp[$caller]<=$grp[$callee])throw new RuntimeException($code($caller,$callee));
$later=static fn(string $c,string $e):bool=>isset($grp[$c],$grp[$e])&&($grp[$c]>$grp[$e]||($grp[$c]===$grp[$e]&&$idx[$c]>$idx[$e]));
foreach([[$car,$lar],[$ctl,$svc],[$asc,$login],[$prc,$forgot],[$prc,$reset],[$idxView,$oo],[$idxView,$cc],[$reg,$pl]] as [$caller,$callee])if(!$later($caller,$callee))throw new RuntimeException($code($caller,$callee));
// The new group-A files are inert (only a later-group caller above can reach them), so what THEY use is not an order edge: LocalAuthFormRequest names AppUrlOrigin, but it is only loaded by LoginRequest and the other requests of group C.
foreach($new as $p=>$text){
    if($grp[$p]==='A')continue;
    if(str_ends_with($p,'.php')&&!str_ends_with($p,'.blade.php')&&preg_match_all('~^use (App\\\\[A-Za-z0-9_\\\\]+);~m',$text,$u))foreach($u[1] as $fq){$callee='app/'.str_replace('\\','/',substr($fq,4)).'.php';if(isset($grp[$callee])&&!$later($p,$callee))throw new RuntimeException($code($p,$callee));}
    if(str_ends_with($p,'.blade.php')&&preg_match_all("~@include\\('([a-z0-9._-]+)'~",$text,$u))foreach($u[1] as $view){$callee='resources/views/'.str_replace('.','/',$view).'.blade.php';if(isset($grp[$callee])&&!$later($p,$callee))throw new RuntimeException($code($p,$callee));}
}
// The groups: A the new inert files, B the three libraries, C the callers and assets, D bootstrap/app.php (guard: the web-SAPI class proof), E the views (guard: the web asset proof), F routes/web.php (guard: the class proof, then route:clear),
// G the management view that renders the new route, last. They are non-decreasing in the list, the management view is the last step and every guard group named by the coordinator exists.
$counts=array_count_values(array_values($grp));ksort($counts);if($counts!==['A'=>9,'B'=>3,'C'=>9,'D'=>1,'E'=>6,'F'=>1,'G'=>1])throw new RuntimeException('PACKAGE_GROUP_LAYOUT_NOT_A9_B3_C9_D1_E6_F1_G1');
$prev='';foreach($grp as $g){if($g<$prev)throw new RuntimeException('PACKAGE_GROUPS_NOT_NON_DECREASING');$prev=$g;}
if($grp[$boot]!=='D'||$grp[$routes]!=='F'||$grp[$mv]!=='G'||$idx[$mv]!==count($idx)-1||$idx[$routes]!==count($idx)-2)throw new RuntimeException('PACKAGE_BOOTSTRAP_ROUTES_OR_MANAGEMENT_VIEW_NOT_IN_PLACE');
$guardGroups=array_keys(DR::GUARDS);sort($guardGroups);if($guardGroups!==['D','E','F']||array_diff($guardGroups,array_values($grp))!==[])throw new RuntimeException('PACKAGE_GUARD_GROUPS_NOT_D_E_F');
// The classes of the new app files (the CLI feature check and the web-SAPI probe load exactly these) and the views the read-only acceptance step compiles.
$newClasses=DR::newClasses();sort($newClasses);if($newClasses!==['App\Http\Controllers\Admin\SubagentSalesReportController','App\Http\Requests\CustomerActivationRequest','App\Http\Requests\LocalAuthFormRequest','App\Services\SubagentSalesReportService'])throw new RuntimeException('PACKAGE_NEW_CLASSES');
$wantViews=[];foreach(array_keys($new) as $p)if(str_ends_with($p,'.blade.php'))$wantViews[]=$p;$haveViews=DR::VIEW_CHECKS;sort($wantViews);sort($haveViews);if($haveViews!==$wantViews||count($wantViews)!==11)throw new RuntimeException('PACKAGE_VIEW_CHECKS_NOT_THE_ELEVEN_VIEWS_OF_THE_PAYLOAD');
if(DR::B1154_TREE_FILES!==8574||DR::installedTreeFiles()!==8583)throw new RuntimeException('PACKAGE_TREE_FILE_COUNTS_NOT_8574_TO_8583');
// The new directories the install must create: only resources/views/public-shop/partials (everything else exists at BASE), asserted.
$dirs=[];foreach(DR::STEPS as [$p,$kind])if($kind==='new'){$d=dirname($p);while($d!=='.'&&$git(['cat-file','-e',$base.':'.CMS.$d],true)===null){$dirs[$d]=true;$d=dirname($d);}}
$dirList=array_keys($dirs);usort($dirList,static fn($a,$b)=>strlen($a)<=>strlen($b));if($dirList!==['resources/views/public-shop/partials'])throw new RuntimeException('PACKAGE_UNEXPECTED_NEW_DIRECTORY');
// The source markers the CLI helper reads from the live files: in every payload file, absent from the pre-state bytes of a modified file (a new file has none).
foreach(DR::SOURCE_MARKERS as $k=>[$p,$needle])if(!isset($new[$p])||!str_contains($new[$p],$needle)||(is_string($old[$p]??null)&&str_contains($old[$p],$needle)))throw new RuntimeException('PACKAGE_SOURCE_MARKER_'.strtoupper($k));
// The B1153 and B1154 source markers (asserted live in both states): present in the bytes at BASE and in the final bytes.
foreach([['B1153',LogoCatalogInstallCoordinator::SOURCE_MARKERS],['B1154',AS54::SOURCE_MARKERS]] as [$tag,$markers])foreach($markers as $k=>[$p,$needle])if(!str_contains($blob($base,$p),$needle)||!str_contains(isset($new[$p])?$new[$p]:$blob($base,$p),$needle))throw new RuntimeException('PACKAGE_'.$tag.'_SOURCE_MARKER_'.strtoupper($k));
$files=[];foreach($pins as $name=>[,$digest])$files[$name]=$digest;foreach($bytes as $name=>$data)$files[$name]=hash('sha256',$data);ksort($files,SORT_STRING);
$common=['schemaVersion'=>1,'batch'=>'B1155','baseCommit'=>$base,'sourceCommit'=>$payloadCommit,'reportMainCommit'=>REPORT_MAIN,'b1154Commit'=>$b1154Commit,'b1154BaseCommit'=>$b1154Base,'b1153Commit'=>$b1153Commit,'b1153BaseCommit'=>$b1153Base,'b1152BaseCommit'=>$b1152Base,'b1152UrlCommit'=>$b1152Url,'b1152PdfCommit'=>$b1152Pdf,'b1152MainCommit'=>$b1152Main,'b1151EmailCommit'=>$b1151Email,'b1151ThrottleCommit'=>$b1151Throttle,'b1150Commit'=>$b1150Source,'storefrontCommit'=>$store,'storefrontBaseCommit'=>$storeBase,'storefrontMainCommit'=>$storeMain,'glassCommit'=>$glass,'catalogCommit'=>$catalog,'legalCommit'=>$legal,'b1146BaseCommit'=>$b1146Base,'installedSourceCommit'=>INSTALLED,'helperSource'=>$helper];
$bytes['b1155-package.json']=json_encode($common+['overlay'=>$overlay,'b1146Overlay'=>$b1146Overlay,'b1147Overlay'=>$b1147Overlay,'b1148Overlay'=>$b1148Overlay,'b1149Overlay'=>$b1149Overlay,'b1150Overlay'=>$b1150Overlay,'b1151Overlay'=>$b1151Overlay,'b1152Overlay'=>$b1152Overlay,'b1153Overlay'=>$b1153Overlay,'b1154Overlay'=>$b1154Overlay,'markers'=>MARKERS,'cssVersion'=>$cssVersion,'oldCssVersion'=>$oldCssVersion,'galleryVersion'=>$galleryVersion,'registerCssVersion'=>$registerCssVersion,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
$check2=json_decode($bytes['b1155-package.json'],true,64,JSON_THROW_ON_ERROR);DR::plan($check2,'/home/icaffeco/ald1n-project/incoming');DR::b1146Plan($check2);DR::b1147Plan($check2);DR::b1148Plan($check2);DR::b1149Plan($check2);DR::b1150Plan($check2);DR::b1151Plan($check2);DR::b1152Plan($check2);DR::b1153Plan($check2);DR::b1154Plan($check2);DR::markers($check2);
$bytes['b1155-provenance.json']=json_encode($common+['author'=>'Ald1n <https://ald1n.com>','workflowRun'=>getenv('GITHUB_RUN_ID')?:null,'workflowRepository'=>getenv('GITHUB_REPOSITORY')?:null,
    'sourceNote'=>'ONE source commit: origin/main d2c07f62 (the merge of PR 193, 194, 196, 197 and 198) over ad901a58, the B1154 payload commit, whose apps/cms/current equals the live tree (and that of main 6acf8aa9, which carries Report1120)',
    'predecessor'=>['report'=>'docs/operations/1120-B1154-INSTALLER-<date>-<time>.md','reportSHA256Pinned'=>DR::B1154_REPORT_SHA256,'b1154StateId'=>DR::B1154_STATE_ID,'required'=>['B1154_FINAL'=>DR::B1154_PASS,'EXIT'=>'0','B1154_PHASE'=>'INSTALLED_PASS','b1154State'=>'INSTALLED_PASS'],'ignored'=>'any other B1154 report'],
    'runtimeManifestSHA256'=>DR::MANIFEST_SHA,'treeFilesBefore'=>DR::B1154_TREE_FILES,'treeFilesAfter'=>DR::installedTreeFiles(),'configAfterB1145SHA256'=>DR::CONFIG_SHA,'pinnedAppUrl'=>DR::EXPECTED_APP_URL,'excludedFromTheSourceDiff'=>$excluded,'directoriesTheInstallCreates'=>$dirList,'installOrder'=>array_map(static fn($s)=>$s[2].' '.$s[1].' '.$s[0].' '.$s[3],DR::STEPS),'preStateSource'=>array_map(static fn($o)=>$o['path'].' '.$o['liveBefore'],$overlay),'pinnedNotRedelivered'=>array_map(fn($p)=>$p[1],$pins),
    'backwardCompatibility'=>['appUrlOrigin'=>'ONLY_ADDED_MEMBERS_EVERY_OLD_LINE_STILL_IN_PLACE','publicStorefrontAppearance'=>'ONLY_ADDED_MEMBERS_EVERY_OLD_LINE_STILL_IN_PLACE','managementReportService'=>'ONLY_THE_DATE_RANGE_CONDITION_AND_THE_ADDED_REPORT_ORDER_IDS_DIFFER_IDENTICAL_ROWS_FOR_Y_M_D_BOUNDS','adminStylesheet'=>'ONLY_THE_TWO_DROPDOWN_PANEL_RULES_ARE_REPLACED','oldTreeNamesNewFiles'=>false],
    'cloudflare'=>['change'=>'NONE','purge'=>'NONE_NEEDED','stylesheetUrls'=>'?v=<file modification time> for the admin stylesheets, ?v=202611151 for the registration stylesheet, never requested before the install'],
    'newRoutes'=>DR::NEW_ROUTE_NAMES,'routeCountBefore'=>DR::ROUTES_BEFORE,'routeCountAfter'=>DR::ROUTES_AFTER,'routeCount'=>DR::ROUTES_BEFORE,
    'neverDone'=>['storefrontDbSwitchWrite','merchantApprovalWrite','smtpSettingsReadOrWrite','testMail','passwordResetRequest','migration','composer','routeCacheBuild','envEdit','configChange','opcacheGlobalReset','viewCache','login','passwordEndpointCall','productOrCartWrite','themePost','registrationPost','pdfGeneration','forgedHostRequest','retainedDirectoryModeChange','authenticatedAdminRequest'],'operatorCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1155-installer.sh','rollbackCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1155-installer.sh --rollback'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
ksort($bytes,SORT_STRING);$checksums='';foreach($bytes as $name=>$data)$checksums.=hash('sha256',$data).'  '.$name."\n";
$wrapper=file_get_contents($tools.'/operator-wrapper.sh');if(!is_string($wrapper)||str_contains($wrapper,"\r")||substr_count($wrapper,"\nSELF_SHA=__SELF_SHA__\n")!==1||substr_count($wrapper,"\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n")!==1)throw new RuntimeException('WRAPPER_TEMPLATE');
$wrapper=str_replace("\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n","\nCHECKSUMS_SHA=".hash('sha256',$checksums)."\n",$wrapper);$wrapper=str_replace("\nSELF_SHA=__SELF_SHA__\n","\nSELF_SHA=".hash('sha256',$wrapper)."\n",$wrapper);
$bytes['b1155-package.sha256']=$checksums;$bytes['b1155-installer.sh']=$wrapper;ksort($bytes,SORT_STRING);
file_put_contents($out.'/b1155-digests.tsv',$digests);
$zipPath=$out.'/b1155-operator.zip';if(file_exists($zipPath))unlink($zipPath);$zip=new ZipArchive();if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('ZIP_CREATE');
foreach($bytes as $name=>$data){$member='incoming/'.$name;$zip->addFromString($member,$data);$zip->setExternalAttributesName($member,ZipArchive::OPSYS_UNIX,(str_ends_with($name,'.sh')?0100755:0100644)<<16);$zip->setMtimeName($member,1762732800);}
if(!$zip->close())throw new RuntimeException('ZIP_CLOSE');
$zipSHA=hash_file('sha256',$zipPath);file_put_contents($zipPath.'.sha256',$zipSHA.'  b1155-operator.zip'."\n");
echo json_encode(['zip'=>basename($zipPath),'zipSHA256'=>$zipSHA,'zipBytes'=>filesize($zipPath),'members'=>array_map(fn($n)=>'incoming/'.$n,array_keys($bytes)),'checksumsSHA256'=>hash('sha256',$checksums),'wrapperSHA256'=>hash('sha256',$wrapper),'cssVersion'=>$cssVersion,'oldCssVersion'=>$oldCssVersion,'galleryVersion'=>$galleryVersion,'registerCssVersion'=>$registerCssVersion,'treeFilesAfter'=>DR::installedTreeFiles(),'excluded'=>$excluded,'directoriesTheInstallCreates'=>$dirList,'overlay'=>$overlay,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
