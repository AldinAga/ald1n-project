<?php
declare(strict_types=1);
/**
 * Builds the B1153 operator ZIP with root-relative incoming/ members only. Everything is read from git at build time, never
 * from a working tree: each payload file is git show <SOURCE_COMMIT>:<path> (d760bb2c, origin/main, the merge of PR 181 and PR 182; ONE source
 * commit, because both PRs change public-shop/layout.blade.php), the pre-state is git show BASE_COMMIT:<path> (af1e46a3, origin/main after the B1152
 * report 1118: its apps/cms/current without tests is the live tree), and the seven earlier overlays (B1146: eabe16af over 77a3efea; B1147: 764bf513
 * over eabe16af; B1148: 56082845 over 764bf513; B1149: 7e748158 over eca7755c; B1150: 14e628ba over 7e748158; B1151: 43d3ab6e and ae00c5f6 over
 * 14e628ba; B1152: 8505b110 and cfe97253 over 7e22afd4) turn the 8531-file runtime manifest into the live 8569-file tree. The payload is shipped as
 * flat incoming/b1153-payload-NN.bin members, so unzip never writes into the live CMS tree. The build fails unless:
 * - BASE is an ancestor of SOURCE, both B1152 heads are ancestors of BASE, and BASE and SOURCE are different commits;
 * - git diff --name-status BASE SOURCE restricted to apps/cms/current, minus apps/cms/current/tests/ and apps/cms/current/bin/ (the rule B1152 used,
 *   plus .github/workflows/ and docs/ which the scope already leaves out), is exactly the coded STEPS set (nothing else, no delete); nothing in
 *   that diff, tests included, is under database/, config/, routes/ or composer.* or is .env (no route, no migration, no composer, no config);
 * - every payload and pre-state digest equals the digest coded in LogoCatalogInstallCoordinator::DIGESTS (the reviewers' sha256 of the raw blobs);
 * - the B1146 to B1151 file sets equal the pinned coordinators' lists, each pre digest equals the live digest before it (manifest, or the earlier
 *   overlay's payload) and each new path is absent before; the B1152 file set equals the pinned B1152 STEPS and its digests equal the pinned B1152 DIGESTS;
 * - production equals BASE: the diff 7e22afd4..af1e46a3 minus tests and bin is exactly the nine B1152 files, each at the B1152 payload digest, so each
 *   B1153 pre digest (git blob at BASE) equals the LIVE digest by a second, independent source (B1152 payload, else B1151, B1150, B1149, B1148,
 *   B1147, B1146, else the runtime manifest; the source is printed per file), and each new path is absent everywhere;
 * - the order facts hold in the shipped bytes: every caller sits in a later group than what it calls (libraries before callers, the stylesheet before the
 *   layout that links its new ?v= URL, the new e-mail header files before the views that include them, the layout last), the new class MailBranding is in
 *   group A, and the source markers the CLI helper reads are in the payload files and not in the pre-state bytes;
 * - the B1149 markers are in the final bytes and in the pre-state bytes of the layout, CSS and views (they hold in both states), the storefront
 *   stylesheet link changes from ?v=202611101 to ?v=202611141 in exactly one place and the gallery stylesheet version does not change.
 * The B1134-B1152 helpers already on the host are verified by digest (package.json), not redelivered.
 * Besides the ZIP it writes b1153-digests.tsv (path, kind, group, source, pre digest, pre-state source, payload digest) next to it.
 * Usage: php package.php <repo root> <b1153 dir> <b1134 dir> <b1144 dir> <b1145 dir> <b1146 dir> <b1147 dir> <b1148 dir> <b1149 dir> <b1150 dir> <b1151 dir> <b1152 dir> <runtime-manifest.json> <output dir> <helper source commit> <source commit>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$repo,$tools,$b1134,$b1144,$b1145,$b1146,$b1147,$b1148,$b1149,$b1150,$b1151,$b1152,$manifestPath,$out,$helper,$srcArg]=$argv+array_fill(0,17,null);
if(!is_dir((string)$repo.'/.git')&&!is_file((string)$repo.'/.git'))throw new RuntimeException('PACKAGE_REPO');if(!is_dir((string)$tools)||!is_dir((string)$b1152)||!is_file((string)$manifestPath)||!preg_match('/^[a-f0-9]{40}$/D',(string)$helper)||!preg_match('/^[a-f0-9]{40}$/D',(string)$srcArg))throw new RuntimeException('PACKAGE_ARGUMENTS');if(!is_dir($out)&&!mkdir($out,0755,true))throw new RuntimeException('PACKAGE_OUTPUT');
require $b1134.'/InstallFileOps.php';require $b1144.'/FreshnessProbe.php';require $b1144.'/LeanGates.php';require $b1144.'/LeanWeb.php';require $b1145.'/EnvFlags.php';require $b1145.'/MigrateUnclampCoordinator.php';require $b1146.'/OverlayProbe.php';require $b1146.'/OverlayWeb.php';require $b1146.'/OverlayCoordinator.php';require $b1147.'/CatalogWeb.php';require $b1147.'/CatalogOverlayCoordinator.php';require $b1148.'/GlassWeb.php';require $b1148.'/GlassOverlayCoordinator.php';require $b1149.'/StorefrontInstallCoordinator.php';require $b1150.'/MediaWeb.php';require $b1150.'/MediaInstallCoordinator.php';require $b1151.'/LegalAccountInstallCoordinator.php';require $b1152.'/OriginPinInstallCoordinator.php';require $tools.'/LoadProbe.php';require $tools.'/LogoCatalogInstallCoordinator.php';
if(LogoCatalogInstallCoordinator::STEPS===[])throw new RuntimeException('B1153_PAYLOAD_NOT_FILLED_IN');
const CMS='apps/cms/current/';const B1146_BASE='77a3efea';const INSTALLED='ef3f927b77ba573881c2b6dba341732c417e6725';
const MARKERS=LogoCatalogInstallCoordinator::MARKERS;
$base=LogoCatalogInstallCoordinator::BASE_COMMIT;$payloadCommit=LogoCatalogInstallCoordinator::SOURCE_COMMIT;$b1152Base=LogoCatalogInstallCoordinator::B1152_BASE_COMMIT;$b1152Url=LogoCatalogInstallCoordinator::B1152_URL_COMMIT;$b1152Pdf=LogoCatalogInstallCoordinator::B1152_PDF_COMMIT;$b1152Main=LogoCatalogInstallCoordinator::B1152_MAIN_COMMIT;$b1151Email=LogoCatalogInstallCoordinator::B1151_EMAIL_COMMIT;$b1151Throttle=LogoCatalogInstallCoordinator::B1151_THROTTLE_COMMIT;$b1150Source=LogoCatalogInstallCoordinator::B1150_COMMIT;$store=LogoCatalogInstallCoordinator::STOREFRONT_COMMIT;$storeBase=LogoCatalogInstallCoordinator::STOREFRONT_BASE_COMMIT;$storeMain=LogoCatalogInstallCoordinator::STOREFRONT_MAIN_COMMIT;$glass=LogoCatalogInstallCoordinator::GLASS_COMMIT;$catalog=LogoCatalogInstallCoordinator::CATALOG_COMMIT;$legal=LogoCatalogInstallCoordinator::LEGAL_COMMIT;
// The workflow passes the source it reviewed; it must be the commit the coordinator is pinned to.
if($srcArg!==$payloadCommit)throw new RuntimeException('PACKAGE_SOURCE_ARGUMENT_DIFFERS_FROM_THE_PINNED_SOURCE');
$git=static function(array $args,bool $allowFail=false)use($repo):?string{$p=proc_open(array_merge(['git','-C',$repo,'-c','core.autocrlf=false'],$args),[1=>['pipe','w'],2=>['pipe','w']],$pipes);$o=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0){if($allowFail)return null;throw new RuntimeException('PACKAGE_GIT_'.$args[0]);}return (string)$o;};
$blob=static fn(string $commit,string $path):string=>(string)$git(['show',$commit.':'.CMS.$path]);
foreach([$payloadCommit,$base,$b1152Url,$b1152Pdf,$b1152Base,$b1152Main,$b1151Email,$b1151Throttle,$b1150Source,$store,$storeBase,$storeMain,$glass,$catalog,$legal,INSTALLED] as $c)if(trim((string)$git(['rev-parse','--verify',$c.'^{commit}']))!==$c)throw new RuntimeException('PACKAGE_COMMIT_'.substr($c,0,8));
if($legal!==OverlayCoordinator::SOURCE_COMMIT||$catalog!==CatalogOverlayCoordinator::SOURCE_COMMIT||$legal!==CatalogOverlayCoordinator::BASE_COMMIT||$glass!==GlassOverlayCoordinator::SOURCE_COMMIT||$catalog!==GlassOverlayCoordinator::BASE_COMMIT||$legal!==GlassOverlayCoordinator::MAIN_COMMIT)throw new RuntimeException('PACKAGE_CHAIN_COMMITS_DIFFER_FROM_THE_PINNED_COORDINATORS');
if($store!==StorefrontInstallCoordinator::SOURCE_COMMIT||$storeBase!==StorefrontInstallCoordinator::BASE_COMMIT||$storeMain!==StorefrontInstallCoordinator::MAIN_COMMIT||$glass!==StorefrontInstallCoordinator::GLASS_COMMIT||$catalog!==StorefrontInstallCoordinator::CATALOG_COMMIT||$legal!==StorefrontInstallCoordinator::LEGAL_COMMIT)throw new RuntimeException('PACKAGE_B1149_COMMITS_DIFFER_FROM_THE_PINNED_B1149_COORDINATOR');
if($b1150Source!==MediaInstallCoordinator::SOURCE_COMMIT||$store!==MediaInstallCoordinator::BASE_COMMIT||MediaInstallCoordinator::MEDIA_ROUTE_NAME!==LogoCatalogInstallCoordinator::MEDIA_ROUTE_NAME)throw new RuntimeException('PACKAGE_B1150_COMMITS_DIFFER_FROM_THE_PINNED_B1150_COORDINATOR');
if($b1151Email!==LegalAccountInstallCoordinator::EMAIL_COMMIT||$b1151Throttle!==LegalAccountInstallCoordinator::THROTTLE_COMMIT||$b1150Source!==LegalAccountInstallCoordinator::BASE_COMMIT)throw new RuntimeException('PACKAGE_B1151_COMMITS_DIFFER_FROM_THE_PINNED_B1151_COORDINATOR');
if($b1152Url!==OriginPinInstallCoordinator::URL_COMMIT||$b1152Pdf!==OriginPinInstallCoordinator::PDF_COMMIT||$b1152Base!==OriginPinInstallCoordinator::BASE_COMMIT||$b1152Main!==OriginPinInstallCoordinator::MAIN_COMMIT||$b1151Email!==OriginPinInstallCoordinator::B1151_EMAIL_COMMIT||$b1151Throttle!==OriginPinInstallCoordinator::B1151_THROTTLE_COMMIT||$b1150Source!==OriginPinInstallCoordinator::B1150_COMMIT||LogoCatalogInstallCoordinator::B1152_PASS!==OriginPinInstallCoordinator::FINAL_PASS||LogoCatalogInstallCoordinator::ROUTES!==OriginPinInstallCoordinator::ROUTES||LogoCatalogInstallCoordinator::EXPECTED_APP_URL!==OriginPinInstallCoordinator::EXPECTED_APP_URL)throw new RuntimeException('PACKAGE_B1152_COMMITS_DIFFER_FROM_THE_PINNED_B1152_COORDINATOR');
if($payloadCommit===$base||$b1152Url===$b1152Pdf||$b1152Url===$b1152Base||$b1152Pdf===$b1152Base)throw new RuntimeException('PACKAGE_HEADS_NOT_DISTINCT');
foreach([[$legal,$catalog],[$catalog,$glass],[$storeBase,$store],[$store,$b1150Source],[$b1150Source,$b1151Email],[$b1150Source,$b1151Throttle],[$b1152Base,$b1152Url],[$b1152Base,$b1152Pdf],[$b1152Url,$base],[$b1152Pdf,$base],[$b1152Base,$base],[$base,$payloadCommit]] as [$a,$b])if($git(['merge-base','--is-ancestor',$a,$b],true)===null)throw new RuntimeException('PACKAGE_NOT_AN_ANCESTOR_'.substr($a,0,8).'_'.substr($b,0,8));
if($git(['diff','--quiet',$glass,$storeBase,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_B1149_BASE_TREE_DIFFERS_FROM_THE_B1148_PAYLOAD_HEAD');
$b1146Base=trim((string)$git(['rev-parse','--verify',B1146_BASE.'^{commit}']));if(!preg_match('/^77a3efea[a-f0-9]{32}$/D',$b1146Base))throw new RuntimeException('PACKAGE_B1146_BASE');
if($git(['diff','--quiet',INSTALLED,$b1146Base,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_INSTALLED_TREE_DIFFERS_FROM_B1146_BASE');
$diff=static function(string $from,string $to,?string $scope='apps/cms/current')use($git):array{$d=[];$args=['diff','--no-renames','--name-status',$from,$to];if($scope!==null)array_push($args,'--',$scope);foreach(explode("\n",trim((string)$git($args))) as $line){if($line==='')continue;if(!preg_match('~^([AMD])\t(\S+)$~D',$line,$m))throw new RuntimeException('PACKAGE_DIFF_LINE');$d[$m[2]]=$m[1];}ksort($d,SORT_STRING);return $d;};
$cms=static function(array $d):array{$o=[];foreach($d as $p=>$k){if(!str_starts_with($p,CMS)||$k==='D')throw new RuntimeException('PACKAGE_DIFF_OUTSIDE_CMS_OR_DELETE_'.$p);$o[substr($p,strlen(CMS))]=$k;}return $o;};
$manifest=json_decode((string)file_get_contents($manifestPath),true,512,JSON_THROW_ON_ERROR);if(hash_file('sha256',$manifestPath)!==LogoCatalogInstallCoordinator::MANIFEST_SHA||count($manifest['files'])!==LogoCatalogInstallCoordinator::MANIFEST_FILES)throw new RuntimeException('PACKAGE_RUNTIME_MANIFEST');$byPath=[];foreach($manifest['files'] as $f)$byPath[$f['path']]=$f['sha256'];

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
    // The digests the B1152 coordinator that the owner ran has coded, and production (BASE, af1e46a3): its blob of every B1152 file is the B1152 payload.
    if(OriginPinInstallCoordinator::DIGESTS[$p]!==[$pre,$sha])throw new RuntimeException('PACKAGE_B1152_DIGEST_NOT_THE_PINNED_ONE_'.$p);
    if(hash('sha256',$blob($base,$p))!==$sha)throw new RuntimeException('PACKAGE_B1152_PAYLOAD_NOT_IN_PRODUCTION_'.$p);
    $b1152Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1152Sha[$p]=$sha;
}
// Production equals BASE: the diff from the B1152 code base to BASE (apps/cms/current, minus tests and bin) is exactly the B1152 file set and nothing else.
$prod=$diff($b1152Base,$base);foreach(array_keys($prod) as $p)if($drop($p))unset($prod[$p]);$prod=$cms($prod);$want=$expected1152['U']+$expected1152['P'];ksort($want,SORT_STRING);
if($prod!==$want)throw new RuntimeException('PACKAGE_PRODUCTION_BASE_IS_NOT_THE_B1151_TREE_PLUS_THE_B1152_FILES');

// The B1153 file set: the CMS-scoped diff BASE..SOURCE minus the CMS tests and bin; nothing else may remain. Nothing in the whole CMS diff (tests included) may touch a route,
// a migration, the config, composer or .env (the diff of those directories is empty).
$excluded=[];$expected=[];foreach(LogoCatalogInstallCoordinator::STEPS as [$p,$kind])$expected[$p]=$kind==='new'?'A':'M';ksort($expected,SORT_STRING);
$whole=$diff($base,$payloadCommit);foreach(array_keys($whole) as $p){
    if(preg_match('~^'.preg_quote(CMS,'~').'(database/|config/|routes/|composer\.|\.env)~',$p)===1)throw new RuntimeException('PACKAGE_ROUTE_MIGRATION_CONFIG_COMPOSER_CHANGE_STOP_AND_REPORT_'.$p);
    if($drop($p)){if(!str_starts_with($p,CMS.'tests/'))throw new RuntimeException('PACKAGE_EXCLUDED_PATH_IS_NOT_A_TEST_'.$p);$excluded[]=$p;unset($whole[$p]);}
}
$perSource=$cms($whole);
if($perSource!==$expected){fwrite(STDERR,json_encode(['diff'=>$perSource,'expected'=>$expected],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);throw new RuntimeException('PACKAGE_FILE_SET_DIFFERS_FROM_THE_SOURCE_DIFF');}
if(count(LogoCatalogInstallCoordinator::STEPS)!==count($expected))throw new RuntimeException('PACKAGE_STEPS_HAVE_A_DUPLICATE_PATH');
foreach(array_keys($expected) as $p)if(preg_match('~^(database/|config/|routes/|composer\.|\.env|tests/|bin/)~',$p))throw new RuntimeException('PACKAGE_ROUTE_MIGRATION_CONFIG_COMPOSER_OR_TEST_CHANGE_STOP_AND_REPORT_'.$p);

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
    'b1152-app-check.php'=>[$b1152.'/AppCheck.php','96cb4550a6e2f0d5158788cdb62eff4fd4bf8d217ab0955d138bbe826f548262'],'b1152-coordinator.php'=>[$b1152.'/OriginPinInstallCoordinator.php','721ffe3f7387ef0326c798d50217bcdefee4b0a2aaebed190b75debb9c0ae97f'],'b1152-load-probe.php'=>[$b1152.'/LoadProbe.php','457f3da1776654bf69930075e5c1851062e597a7e136217eae57a07ee6388a4b']];
foreach($pins as $name=>[$file,$digest])if(hash_file('sha256',$file)!==$digest)throw new RuntimeException('PINNED_DEPENDENCY_DRIFT_'.$name);
$members=['b1153-installer.php'=>'operator-entry.php','b1153-coordinator.php'=>'LogoCatalogInstallCoordinator.php','b1153-app-check.php'=>'AppCheck.php','b1153-load-probe.php'=>'LoadProbe.php','b1153-README-SR.md'=>'README-SR.md'];
$bytes=[];foreach($members as $name=>$file){$data=file_get_contents($tools.'/'.$file);if(!is_string($data)||str_contains($data,"\r"))throw new RuntimeException('PACKAGE_SOURCE_BYTES_'.$name);$bytes[$name]=$data;}
// The shipped tool code must not reach cPanel, reset OPcache globally, overlay PHP flags, migrate, seed, build any cache, POST or
// send a custom method, send mail, or write the DB switch or any row. route:clear and queue:restart are the only allowed artisan
// cache commands (coded once each, in refreshViews); view:clear is coded once more in the recovery block only.
foreach($bytes as $name=>$data)if(str_ends_with($name,'.php')&&preg_match('~/usr/local/cpanel|uapi|DomainInfo|opcache_reset|php_flag|HostProcessGuard|--seed|migrate:fresh|migrate:rollback|migrate:refresh|migrate:reset|db:wipe|\'migrate\'|config:cache|view:cache|route:cache|public_storefront_open|->update\(|->insert\(|->upsert\(|->delete\(|->set\(|->put\(|Mail::|mail\(|CURLOPT_POST|CURLOPT_CUSTOMREQUEST|mail:test|app:send-test-mail~i',$data))throw new RuntimeException('PACKAGE_FORBIDDEN_MECHANISM_'.$name);
if(substr_count($bytes['b1153-coordinator.php'],"artisanOk(['route:clear'])")!==1||substr_count($bytes['b1153-coordinator.php'],"artisanOk(['queue:restart'])")!==1)throw new RuntimeException('PACKAGE_ARTISAN_CACHE_COMMANDS');
// The password endpoints are never requested by the shipped tool (no login, no PUT, no POST): none of their paths may appear in the shipped code. (The view file name auth/forgot-password.blade.php in STEPS is a file path, not a request.)
foreach($bytes as $name=>$data)if(str_ends_with($name,'.php')&&preg_match('~/account/password|/me/password|/auth/password|password/email|forgot-password(?!\.blade\.php)~',$data))throw new RuntimeException('PACKAGE_PASSWORD_ENDPOINT_IN_THE_TOOL_'.$name);

$overlay=[];$new=[];$old=[];$digests="n\tgroup\tkind\tpath\tsource\tpre_sha256_git_".substr($base,0,8)."\tpre_state_source\tpayload_sha256_git_source\n";
foreach(LogoCatalogInstallCoordinator::STEPS as $i=>[$path,$kind,$group,$s1]){
    $n=$i+1;$commit=LogoCatalogInstallCoordinator::sourceCommit($s1);$payload=$blob($commit,$path);$pre=$kind==='modified'?$blob($base,$path):null;$new[$path]=$payload;$old[$path]=$pre;$liveBefore='ABSENT';
    if($kind==='new'&&($git(['cat-file','-e',$base.':'.CMS.$path],true)!==null||isset($byPath[$path])||isset($b1146Sha[$path])||isset($b1147Sha[$path])||isset($b1148Sha[$path])||isset($b1149Sha[$path])||isset($b1150Sha[$path])||isset($b1151Sha[$path])||isset($b1152Sha[$path])))throw new RuntimeException('PACKAGE_NEW_PATH_EXISTS_'.$n);
    if($kind==='modified'){$preSha=hash('sha256',(string)$pre);[$live,$liveBefore]=isset($b1152Sha[$path])?[$b1152Sha[$path],'B1152_PAYLOAD']:(isset($b1151Sha[$path])?[$b1151Sha[$path],'B1151_PAYLOAD']:(isset($b1150Sha[$path])?[$b1150Sha[$path],'B1150_PAYLOAD']:(isset($b1149Sha[$path])?[$b1149Sha[$path],'B1149_PAYLOAD']:(isset($b1148Sha[$path])?[$b1148Sha[$path],'B1148_PAYLOAD']:(isset($b1147Sha[$path])?[$b1147Sha[$path],'B1147_PAYLOAD']:(isset($b1146Sha[$path])?[$b1146Sha[$path],'B1146_PAYLOAD']:[$byPath[$path]??null,'RUNTIME_MANIFEST']))))));if($live!==$preSha)throw new RuntimeException('PACKAGE_PRE_STATE_NOT_LIVE_'.$n.'_'.$path);}
    if(str_contains($payload,"\r"))throw new RuntimeException('PACKAGE_PAYLOAD_CR_'.$n);
    // The reviewers' digests (raw git blob bytes), coded in the coordinator's payload block: any other bytes are refused here and on the host.
    $dig=LogoCatalogInstallCoordinator::DIGESTS[$path]??null;if($dig===null||$dig[1]!==hash('sha256',$payload)||$dig[0]!==($pre===null?null:hash('sha256',$pre)))throw new RuntimeException('PACKAGE_DIGEST_DIFFERS_FROM_THE_REVIEWED_ONE_'.$n.'_'.$path);
    $member=sprintf('b1153-payload-%02d.bin',$n);$bytes[$member]=$payload;$overlay[]=['n'=>$n,'path'=>$path,'kind'=>$kind,'group'=>$group,'source'=>$commit,'payload'=>$member,'payloadSHA256'=>hash('sha256',$payload),'preSHA256'=>$pre===null?null:hash('sha256',$pre),'size'=>strlen($payload),'liveBefore'=>$liveBefore];
    $digests.=$n."\t".$group."\t".$kind."\t".$path."\t".substr($commit,0,8)."\t".($pre===null?'ABSENT':hash('sha256',$pre))."\t".$liveBefore."\t".hash('sha256',$payload)."\n";
}
// The B1149 markers hold in the final bytes and in the pre-state bytes (the layout, the CSS and the catalog view are in the payload; the gallery CSS and the product view are read at BASE, which is the live tree).
$layout='resources/views/public-shop/layout.blade.php';$css='public/assets/css/ald1n-public-storefront.css';$gallery='public/assets/css/ald1n-public-gallery.css';$catalogView='resources/views/public-shop/catalog.blade.php';$show='resources/views/public-shop/show.blade.php';
$final=static fn(string $p):string=>array_key_exists($p,$new)?$new[$p]:$blob($base,$p);$before=static fn(string $p):string=>$old[$p]??$blob($base,$p);
foreach([['layout',$layout],['css',$css],['gallery',$gallery],['catalog',$catalogView],['catalog',$show]] as [$k,$p])if(!str_contains($final($p),MARKERS[$k])||!str_contains($before($p),MARKERS[$k]))throw new RuntimeException('PACKAGE_MARKER_'.strtoupper($k).'_'.$p);
$version=static function(string $text,string $file):string{if(preg_match_all('~'.preg_quote($file,'~').'\?v=([0-9]{1,12})[\'"]~',$text,$v)!==1)throw new RuntimeException('PACKAGE_CSS_VERSION');return $v[1][0];};
$cssVersion=$version($final($layout),'ald1n-public-storefront.css');$oldCssVersion=$version($before($layout),'ald1n-public-storefront.css');
// The storefront stylesheet URL changes (the one cache-bust query of the layout): the stylesheet file is in the payload, so a new URL is the right thing; nothing else is needed for Cloudflare.
if($oldCssVersion!=='202611101'||$cssVersion!=='202611141')throw new RuntimeException('PACKAGE_CSS_VERSION_NOT_202611101_TO_202611141_'.$oldCssVersion.'_'.$cssVersion);
$galleryVersion=$version($final($catalogView),'ald1n-public-gallery.css');if($galleryVersion!==$version($final($show),'ald1n-public-gallery.css')||$galleryVersion!==$version($before($catalogView),'ald1n-public-gallery.css')||$galleryVersion!==$version($before($show),'ald1n-public-gallery.css'))throw new RuntimeException('PACKAGE_GALLERY_VERSION_DIFFERS');
// The order facts behind the install order, in the shipped bytes: needle present in the new bytes, absent from the old where the file is modified.
$facts=['app/Services/SettingsService.php'=>['public const LOGO_HEIGHTS','public static function logoHeight('],
    'app/Support/PublicCatalogPresentation.php'=>['public const PER_PAGE_OPTIONS','public static function perPage(','public static function viewMode('],
    'app/Support/PublicStorefrontAppearance.php'=>['public static function freshBranding(','SettingsService::logoHeight('],
    'app/Support/MailBranding.php'=>['namespace App\Support;','final class MailBranding','public static function logo()','PublicStorefrontAppearance::freshBranding()','SettingsService::logoHeight('],
    'app/Services/Pdf/FlowPdfLayout.php'=>['private readonly bool $nameBesideLogo','SettingsService::logoHeight('],
    'app/Services/Pdf/BusinessDocumentPdfService.php'=>['nameBesideLogo: false'],'app/Services/Pdf/WarrantyCertificatePdfService.php'=>['nameBesideLogo: false'],
    'app/Providers/AppServiceProvider.php'=>['loadComponentsFrom'],
    'resources/views/emails/partials/header.blade.php'=>['MailBranding::logo()'],'resources/views/vendor/mail/html/header.blade.php'=>['MailBranding::logo()'],
    'resources/views/emails/order-events.blade.php'=>['emails.partials.header'],'resources/views/emails/management-report.blade.php'=>['emails.partials.header'],
    'resources/views/public-shop/layout.blade.php'=>['footer_logo_height','ald1n-public-storefront.css?v=202611141'],
    'resources/views/public-shop/catalog.blade.php'=>[LogoCatalogInstallCoordinator::PER_PAGE_MARKER,LogoCatalogInstallCoordinator::VIEW_SWITCH_MARKER,LogoCatalogInstallCoordinator::LIST_MARKER],
    'public/assets/css/ald1n-public-storefront.css'=>['.shop-perpage','.shop-view-group','.shop-products-list'],
    'resources/views/layouts/app.blade.php'=>['SettingsService::logoHeight(\'site_admin_logo_height\'']];
foreach($facts as $p=>$needles)foreach($needles as $needle)if(!isset($new[$p])||!str_contains($new[$p],$needle)||(($old[$p]??null)!==null&&str_contains($old[$p],$needle)))throw new RuntimeException('PACKAGE_ORDER_FACT_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/','_',$p.'_'.$needle)));
// The dependency edges: [caller, callee]. Every caller sits in a LATER group than what it calls (so a request never meets a new caller over an old callee), and the stylesheet goes before the layout.
$idx=[];$grp=[];foreach(LogoCatalogInstallCoordinator::STEPS as $i=>[$p,,$g]){$idx[$p]=$i;$grp[$p]=$g;}
$edges=[['app/Services/Pdf/FlowPdfLayout.php','app/Services/SettingsService.php'],['app/Support/PublicStorefrontAppearance.php','app/Services/SettingsService.php'],['app/Services/PublicRetailCatalogService.php','app/Support/PublicCatalogPresentation.php'],['app/Http/Controllers/PublicShopController.php','app/Support/PublicCatalogPresentation.php'],['app/Http/Controllers/Admin/SiteAppearanceController.php','app/Services/SettingsService.php'],
    ['app/Services/Pdf/BusinessDocumentPdfService.php','app/Services/Pdf/FlowPdfLayout.php'],['app/Services/Pdf/WarrantyCertificatePdfService.php','app/Services/Pdf/FlowPdfLayout.php'],
    ['resources/views/emails/order-events.blade.php','resources/views/emails/partials/header.blade.php'],['resources/views/emails/management-report.blade.php','resources/views/emails/partials/header.blade.php'],['app/Providers/AppServiceProvider.php','resources/views/vendor/mail/html/header.blade.php'],
    ['resources/views/emails/order-events.blade.php','app/Support/MailBranding.php'],['resources/views/layouts/app.blade.php','app/Services/SettingsService.php'],['resources/views/public-shop/layout.blade.php','app/Support/PublicStorefrontAppearance.php'],['resources/views/public-shop/catalog.blade.php','app/Support/PublicCatalogPresentation.php'],
    ['resources/views/public-shop/layout.blade.php','public/assets/css/ald1n-public-storefront.css'],['resources/views/public-shop/catalog.blade.php','public/assets/css/ald1n-public-storefront.css']];
foreach($edges as [$caller,$callee])if(!isset($grp[$caller],$grp[$callee])||$grp[$caller]<=$grp[$callee])throw new RuntimeException('PACKAGE_ORDER_EDGE_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/','_',$caller.'_AFTER_'.$callee)));
if($idx[$layout]!==count($idx)-1||$idx[$css]>=$idx[$layout]||$grp['app/Support/MailBranding.php']!=='A'||$grp[$layout]!==LogoCatalogInstallCoordinator::GUARD_GROUP)throw new RuntimeException('PACKAGE_LAYOUT_NOT_LAST_OR_CSS_NOT_BEFORE_IT_OR_MAIL_BRANDING_NOT_IN_GROUP_A');
// The new directories the install must create: only these two, both under resources/views.
$dirs=[];foreach(LogoCatalogInstallCoordinator::STEPS as [$p,$kind])if($kind==='new'){$d=dirname($p);while($d!=='.'&&$git(['cat-file','-e',$base.':'.CMS.$d],true)===null){$dirs[$d]=true;$d=dirname($d);}}
$dirList=array_keys($dirs);usort($dirList,static fn($a,$b)=>strlen($a)<=>strlen($b));
// The source markers the CLI helper reads from the live files.
foreach(LogoCatalogInstallCoordinator::SOURCE_MARKERS as $k=>[$p,$needle])if(!isset($new[$p])||!str_contains($new[$p],$needle)||!is_string($old[$p]??null)||str_contains($old[$p],$needle))throw new RuntimeException('PACKAGE_SOURCE_MARKER_'.strtoupper($k));
// The generated web class probe is valid PHP and carries no hard-coded secret.
$probe=LogoCatalogLoadProbe::plan(str_repeat('a',32),['App\Support\MailBranding'=>['/x/app/Support/MailBranding.php',str_repeat('b',64)]],LogoCatalogInstallCoordinator::EXPECTED_APP_URL);$probeFile=sys_get_temp_dir().'/b1153-probe-lint-'.bin2hex(random_bytes(6)).'.php';file_put_contents($probeFile,$probe['source']);$lint=proc_open([PHP_BINARY,'-l',$probeFile],[1=>['pipe','w'],2=>['pipe','w']],$lp);$lintOut=stream_get_contents($lp[1]).stream_get_contents($lp[2]);fclose($lp[1]);fclose($lp[2]);$lintRc=proc_close($lint);@unlink($probeFile);
if($lintRc!==0||str_contains($probe['source'],$probe['token']))throw new RuntimeException('PACKAGE_LOAD_PROBE_SOURCE_INVALID');
$files=[];foreach($pins as $name=>[,$digest])$files[$name]=$digest;foreach($bytes as $name=>$data)$files[$name]=hash('sha256',$data);ksort($files,SORT_STRING);
$common=['schemaVersion'=>1,'batch'=>'B1153','baseCommit'=>$base,'sourceCommit'=>$payloadCommit,'b1152BaseCommit'=>$b1152Base,'b1152UrlCommit'=>$b1152Url,'b1152PdfCommit'=>$b1152Pdf,'b1152MainCommit'=>$b1152Main,'b1151EmailCommit'=>$b1151Email,'b1151ThrottleCommit'=>$b1151Throttle,'b1150Commit'=>$b1150Source,'storefrontCommit'=>$store,'storefrontBaseCommit'=>$storeBase,'storefrontMainCommit'=>$storeMain,'glassCommit'=>$glass,'catalogCommit'=>$catalog,'legalCommit'=>$legal,'b1146BaseCommit'=>$b1146Base,'installedSourceCommit'=>INSTALLED,'helperSource'=>$helper];
$bytes['b1153-package.json']=json_encode($common+['overlay'=>$overlay,'b1146Overlay'=>$b1146Overlay,'b1147Overlay'=>$b1147Overlay,'b1148Overlay'=>$b1148Overlay,'b1149Overlay'=>$b1149Overlay,'b1150Overlay'=>$b1150Overlay,'b1151Overlay'=>$b1151Overlay,'b1152Overlay'=>$b1152Overlay,'markers'=>MARKERS,'cssVersion'=>$cssVersion,'oldCssVersion'=>$oldCssVersion,'galleryVersion'=>$galleryVersion,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
$check=json_decode($bytes['b1153-package.json'],true,64,JSON_THROW_ON_ERROR);LogoCatalogInstallCoordinator::plan($check,'/home/icaffeco/ald1n-project/incoming');LogoCatalogInstallCoordinator::b1146Plan($check);LogoCatalogInstallCoordinator::b1147Plan($check);LogoCatalogInstallCoordinator::b1148Plan($check);LogoCatalogInstallCoordinator::b1149Plan($check);LogoCatalogInstallCoordinator::b1150Plan($check);LogoCatalogInstallCoordinator::b1151Plan($check);LogoCatalogInstallCoordinator::b1152Plan($check);LogoCatalogInstallCoordinator::markers($check);
$bytes['b1153-provenance.json']=json_encode($common+['author'=>'Ald1n <https://ald1n.com>','workflowRun'=>getenv('GITHUB_RUN_ID')?:null,'workflowRepository'=>getenv('GITHUB_REPOSITORY')?:null,
    'sourceNote'=>'ONE source commit: PR 181 and PR 182 both change public-shop/layout.blade.php, so the payload is origin/main d760bb2c over af1e46a3, not one head per PR',
    'predecessor'=>['report'=>'docs/operations/1118-B1152-INSTALLER-<date>-<time>.md','reportSHA256Pinned'=>LogoCatalogInstallCoordinator::B1152_REPORT_SHA256,'b1152StateId'=>LogoCatalogInstallCoordinator::B1152_STATE_ID,'required'=>['B1152_FINAL'=>LogoCatalogInstallCoordinator::B1152_PASS,'EXIT'=>'0','B1152_PHASE'=>'INSTALLED_PASS','b1152State'=>'INSTALLED_PASS'],'ignored'=>'any other B1152 report'],
    'runtimeManifestSHA256'=>LogoCatalogInstallCoordinator::MANIFEST_SHA,'treeFilesBefore'=>LogoCatalogInstallCoordinator::B1152_TREE_FILES,'treeFilesAfter'=>LogoCatalogInstallCoordinator::installedTreeFiles(),'configAfterB1145SHA256'=>LogoCatalogInstallCoordinator::CONFIG_SHA,'pinnedAppUrl'=>LogoCatalogInstallCoordinator::EXPECTED_APP_URL,'excludedFromTheSourceDiff'=>$excluded,'directoriesTheInstallCreates'=>$dirList,'installOrder'=>array_map(static fn($s)=>$s[2].' '.$s[1].' '.$s[0].' '.$s[3],LogoCatalogInstallCoordinator::STEPS),'preStateSource'=>array_map(static fn($o)=>$o['path'].' '.$o['liveBefore'],$overlay),'pinnedNotRedelivered'=>array_map(fn($p)=>$p[1],$pins),
    'cloudflare'=>['change'=>'NONE','purge'=>'NONE_NEEDED','cssUrlOld'=>'https://cms.ald1n.com'.LogoCatalogInstallCoordinator::CSS_PATH.'?v='.$oldCssVersion,'cssUrlNew'=>'https://cms.ald1n.com'.LogoCatalogInstallCoordinator::CSS_PATH.'?v='.$cssVersion],
    'newRoutes'=>[],'routeCount'=>LogoCatalogInstallCoordinator::ROUTES,
    'neverDone'=>['storefrontDbSwitchWrite','merchantApprovalWrite','smtpSettingsReadOrWrite','testMail','passwordResetRequest','migration','composer','routeCacheBuild','routeChange','envEdit','configChange','opcacheGlobalReset','viewCache','login','passwordEndpointCall','productOrCartWrite','themePost','pdfGeneration','forgedHostRequest','retainedDirectoryModeChange','logoHeightSettingWrite'],'operatorCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1153-installer.sh','rollbackCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1153-installer.sh --rollback'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
ksort($bytes,SORT_STRING);$checksums='';foreach($bytes as $name=>$data)$checksums.=hash('sha256',$data).'  '.$name."\n";
$wrapper=file_get_contents($tools.'/operator-wrapper.sh');if(!is_string($wrapper)||str_contains($wrapper,"\r")||substr_count($wrapper,"\nSELF_SHA=__SELF_SHA__\n")!==1||substr_count($wrapper,"\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n")!==1)throw new RuntimeException('WRAPPER_TEMPLATE');
$wrapper=str_replace("\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n","\nCHECKSUMS_SHA=".hash('sha256',$checksums)."\n",$wrapper);$wrapper=str_replace("\nSELF_SHA=__SELF_SHA__\n","\nSELF_SHA=".hash('sha256',$wrapper)."\n",$wrapper);
$bytes['b1153-package.sha256']=$checksums;$bytes['b1153-installer.sh']=$wrapper;ksort($bytes,SORT_STRING);
file_put_contents($out.'/b1153-digests.tsv',$digests);
$zipPath=$out.'/b1153-operator.zip';if(file_exists($zipPath))unlink($zipPath);$zip=new ZipArchive();if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('ZIP_CREATE');
foreach($bytes as $name=>$data){$member='incoming/'.$name;$zip->addFromString($member,$data);$zip->setExternalAttributesName($member,ZipArchive::OPSYS_UNIX,(str_ends_with($name,'.sh')?0100755:0100644)<<16);$zip->setMtimeName($member,1762732800);}
if(!$zip->close())throw new RuntimeException('ZIP_CLOSE');
$zipSHA=hash_file('sha256',$zipPath);file_put_contents($zipPath.'.sha256',$zipSHA.'  b1153-operator.zip'."\n");
echo json_encode(['zip'=>basename($zipPath),'zipSHA256'=>$zipSHA,'zipBytes'=>filesize($zipPath),'members'=>array_map(fn($n)=>'incoming/'.$n,array_keys($bytes)),'checksumsSHA256'=>hash('sha256',$checksums),'wrapperSHA256'=>hash('sha256',$wrapper),'cssVersion'=>$cssVersion,'oldCssVersion'=>$oldCssVersion,'galleryVersion'=>$galleryVersion,'treeFilesAfter'=>LogoCatalogInstallCoordinator::installedTreeFiles(),'excluded'=>$excluded,'directoriesTheInstallCreates'=>$dirList,'overlay'=>$overlay,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
