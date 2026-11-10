<?php
declare(strict_types=1);
/**
 * Builds the B1149 operator ZIP with root-relative incoming/ members only. Everything is read from git at build time, never
 * from a working tree: the payload is git show SOURCE_COMMIT:<path> (the reviewed merged head 7e748158), the pre-state is
 * git show BASE_COMMIT:<path> (main eca7755c, whose apps/cms/current is the live tree after B1148), and the three earlier
 * overlays (B1146: eabe16af over 77a3efea; B1147: 764bf513 over eabe16af; B1148: 56082845 over 764bf513) turn the 8531-file
 * runtime manifest into the live 8548-file tree. The payload is shipped as flat incoming/b1149-payload-NN.bin members, so
 * unzip never writes into the live CMS tree. The build fails unless:
 * - eca7755c..55d06b9c touches nothing under apps/ (main moved by docs only) and apps/cms/current at eca7755c is byte-equal
 *   to the B1148 payload head 56082845 (so "main eca7755c" and "what B1148 installed" are the same tree);
 * - BASE and MAIN are ancestors of SOURCE;
 * - git diff --name-status BASE SOURCE, minus apps/cms/current/tests/, .github/workflows/ and docs/, is exactly the coded
 *   install list (16 new, 27 modified, all under apps/cms/current, nothing under database/, config/, composer.*, no delete);
 * - the B1146, B1147 and B1148 file sets equal the pinned coordinators' lists, each pre digest equals the live digest before
 *   it (manifest, or the earlier overlay's payload) and each new path is absent before;
 * - each B1149 pre digest (git blob at BASE) equals the LIVE digest by the second, independent source (B1148 payload, else
 *   B1147, else B1146, else the runtime manifest; the source is printed per file), and each new path is absent everywhere;
 * - the markers are in the new bytes and absent from the old, the layout links the storefront CSS with a changed static ?v=,
 *   the catalog and product views link the gallery CSS with one and the same ?v=, and the dependency facts behind the install
 *   order hold in the new bytes.
 * The B1134-B1148 helpers already on the host are verified by digest (package.json), not redelivered.
 * Besides the ZIP it writes b1149-digests.tsv (path, kind, group, pre digest, pre-state source, payload digest) next to it.
 * Usage: php package.php <repo root> <b1149 dir> <b1134 dir> <b1144 dir> <b1145 dir> <b1146 dir> <b1147 dir> <b1148 dir> <runtime-manifest.json> <output dir> <helper source commit> <source commit>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$repo,$tools,$b1134,$b1144,$b1145,$b1146,$b1147,$b1148,$manifestPath,$out,$helper,$sourceArg]=$argv+array_fill(0,13,null);
if(!is_dir((string)$repo.'/.git')&&!is_file((string)$repo.'/.git'))throw new RuntimeException('PACKAGE_REPO');if(!is_dir((string)$tools)||!is_dir((string)$b1148)||!is_file((string)$manifestPath)||!preg_match('/^[a-f0-9]{40}$/D',(string)$helper)||!preg_match('/^[a-f0-9]{40}$/D',(string)$sourceArg))throw new RuntimeException('PACKAGE_ARGUMENTS');if(!is_dir($out)&&!mkdir($out,0755,true))throw new RuntimeException('PACKAGE_OUTPUT');
require $b1134.'/InstallFileOps.php';require $b1144.'/FreshnessProbe.php';require $b1144.'/LeanGates.php';require $b1144.'/LeanWeb.php';require $b1145.'/EnvFlags.php';require $b1145.'/MigrateUnclampCoordinator.php';require $b1146.'/OverlayProbe.php';require $b1146.'/OverlayWeb.php';require $b1146.'/OverlayCoordinator.php';require $b1147.'/CatalogWeb.php';require $b1147.'/CatalogOverlayCoordinator.php';require $b1148.'/GlassWeb.php';require $b1148.'/GlassOverlayCoordinator.php';require $tools.'/StorefrontWeb.php';require $tools.'/StorefrontInstallCoordinator.php';
const CMS='apps/cms/current/';const B1146_BASE='77a3efea';const INSTALLED='ef3f927b77ba573881c2b6dba341732c417e6725';
const MARKERS=['layout'=>'aria-label="Tema"','css'=>'@media (prefers-contrast:more){.shop-theme-option','gallery'=>'.shop-ratio-r34','catalog'=>'ald1n-public-gallery.css'];
$source=StorefrontInstallCoordinator::SOURCE_COMMIT;$base=StorefrontInstallCoordinator::BASE_COMMIT;$main=StorefrontInstallCoordinator::MAIN_COMMIT;$glass=StorefrontInstallCoordinator::GLASS_COMMIT;$catalog=StorefrontInstallCoordinator::CATALOG_COMMIT;$legal=StorefrontInstallCoordinator::LEGAL_COMMIT;
// The workflow passes the head it reviewed; it must be the commit the coordinator is pinned to.
if($sourceArg!==$source)throw new RuntimeException('PACKAGE_SOURCE_ARGUMENT_DIFFERS_FROM_THE_PINNED_SOURCE_COMMIT');
$git=static function(array $args,bool $allowFail=false)use($repo):?string{$p=proc_open(array_merge(['git','-C',$repo,'-c','core.autocrlf=false'],$args),[1=>['pipe','w'],2=>['pipe','w']],$pipes);$o=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0){if($allowFail)return null;throw new RuntimeException('PACKAGE_GIT_'.$args[0]);}return (string)$o;};
$blob=static fn(string $commit,string $path):string=>(string)$git(['show',$commit.':'.CMS.$path]);
foreach([$source,$base,$main,$glass,$catalog,$legal,INSTALLED] as $c)if(trim((string)$git(['rev-parse','--verify',$c.'^{commit}']))!==$c)throw new RuntimeException('PACKAGE_COMMIT_'.substr($c,0,8));
if($legal!==OverlayCoordinator::SOURCE_COMMIT||$catalog!==CatalogOverlayCoordinator::SOURCE_COMMIT||$legal!==CatalogOverlayCoordinator::BASE_COMMIT||$glass!==GlassOverlayCoordinator::SOURCE_COMMIT||$catalog!==GlassOverlayCoordinator::BASE_COMMIT||$legal!==GlassOverlayCoordinator::MAIN_COMMIT)throw new RuntimeException('PACKAGE_CHAIN_COMMITS_DIFFER_FROM_THE_PINNED_COORDINATORS');
foreach([[$legal,$catalog],[$catalog,$glass],[$base,$main],[$base,$source],[$main,$source]] as [$a,$b])if($git(['merge-base','--is-ancestor',$a,$b],true)===null)throw new RuntimeException('PACKAGE_NOT_AN_ANCESTOR_'.substr($a,0,8).'_'.substr($b,0,8));
// Main moved from eca7755c to 55d06b9c by docs only, and eca7755c is the tree B1148 installed.
if($git(['diff','--quiet',$base,$main,'--','apps'],true)===null)throw new RuntimeException('PACKAGE_MAIN_TOUCHES_APPS_ABOVE_THE_INSTALLED_TREE');
if($git(['diff','--quiet',$glass,$base,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_BASE_TREE_DIFFERS_FROM_THE_B1148_PAYLOAD_HEAD');
$b1146Base=trim((string)$git(['rev-parse','--verify',B1146_BASE.'^{commit}']));if(!preg_match('/^77a3efea[a-f0-9]{32}$/D',$b1146Base))throw new RuntimeException('PACKAGE_B1146_BASE');
if($git(['diff','--quiet',INSTALLED,$b1146Base,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_INSTALLED_TREE_DIFFERS_FROM_B1146_BASE');
$diff=static function(string $from,string $to,?string $scope='apps/cms/current')use($git):array{$d=[];$args=['diff','--no-renames','--name-status',$from,$to];if($scope!==null)array_push($args,'--',$scope);foreach(explode("\n",trim((string)$git($args))) as $line){if($line==='')continue;if(!preg_match('~^([AMD])\t(\S+)$~D',$line,$m))throw new RuntimeException('PACKAGE_DIFF_LINE');$d[$m[2]]=$m[1];}ksort($d,SORT_STRING);return $d;};
$cms=static function(array $d):array{$o=[];foreach($d as $p=>$k){if(!str_starts_with($p,CMS)||$k==='D')throw new RuntimeException('PACKAGE_DIFF_OUTSIDE_CMS_OR_DELETE_'.$p);$o[substr($p,strlen(CMS))]=$k;}return $o;};
$manifest=json_decode((string)file_get_contents($manifestPath),true,512,JSON_THROW_ON_ERROR);if(hash_file('sha256',$manifestPath)!==StorefrontInstallCoordinator::MANIFEST_SHA||count($manifest['files'])!==StorefrontInstallCoordinator::MANIFEST_FILES)throw new RuntimeException('PACKAGE_RUNTIME_MANIFEST');$byPath=[];foreach($manifest['files'] as $f)$byPath[$f['path']]=$f['sha256'];

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

// The B1149 file set: the whole diff BASE..SOURCE minus the CMS tests, the CI workflows and docs; nothing else may remain.
$whole=$diff($base,$source,null);$excluded=[];foreach(array_keys($whole) as $p)if(str_starts_with($p,CMS.'tests/')||str_starts_with($p,'.github/workflows/')||str_starts_with($p,'docs/')){$excluded[]=$p;unset($whole[$p]);}
$d=$cms($whole);$expected=[];foreach(StorefrontInstallCoordinator::STEPS as [$p,$kind])$expected[$p]=$kind==='new'?'A':'M';ksort($expected,SORT_STRING);
if($d!==$expected){fwrite(STDERR,json_encode(['diff'=>$d,'expected'=>$expected],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);throw new RuntimeException('PACKAGE_FILE_SET_DIFFERS_FROM_THE_SOURCE_DIFF');}
if(count($expected)!==43||count(array_filter($expected,static fn($k)=>$k==='A'))!==16)throw new RuntimeException('PACKAGE_43_FILES_16_NEW_27_MODIFIED');
foreach(array_keys($expected) as $p)if(preg_match('~^(database/|config/|composer\.|\.env)~',$p))throw new RuntimeException('PACKAGE_MIGRATION_CONFIG_OR_COMPOSER_CHANGE_STOP_AND_REPORT_'.$p);

$pins=['b1134-files.php'=>[$b1134.'/InstallFileOps.php','9dce8324bd4464a1689918775736bcd3a850f2b1c2b1b0ca7e130a9e486da12b'],'b1144-gates.php'=>[$b1144.'/LeanGates.php','7e62150fcad0884255ddb3c18063f5e2a23852a3eb0fdecb978c2955392ccf6e'],'b1144-probe.php'=>[$b1144.'/FreshnessProbe.php','3dbf4762e419d8df053fa7e642bd1b85df07d66dbffdb94fe0d8d5cdc1b50616'],'b1144-web.php'=>[$b1144.'/LeanWeb.php','54a04bc4dea81d56ad5fe0e6b25a141d4a86acf803f6df95f1c5d2b7adff6a73'],
    'b1145-env.php'=>[$b1145.'/EnvFlags.php','04a4bbd13b8aef346247d8641445ccb9f0a2d7fd3f26f7a03723724fc0132f69'],'b1145-app-state.php'=>[$b1145.'/AppState.php','642ddede76db8f1ea66bfc6a8d1ad74c894a70b665204cac4c96de17e97113a5'],'b1145-coordinator.php'=>[$b1145.'/MigrateUnclampCoordinator.php','46b159fbeab6c4f849dbb1a9291bbf13245b511ea58fd51646f26f28caa53fb9'],
    'b1146-probe.php'=>[$b1146.'/OverlayProbe.php','abcc9c64c6b9918d180afd387ec917a4924e1ce28c0099c0bbdae500e811c01f'],'b1146-app-check.php'=>[$b1146.'/AppCheck.php','68b4b2af5ad5a6399991adf474cfc753abf77a483fb12d2c1669a4b307ce2c2b'],
    'b1147-web.php'=>[$b1147.'/CatalogWeb.php','c4e4c0935b4c14e7fbee1089243f5be4784c5b25c5c0a62d11551d5dfa1461ce'],'b1147-app-check.php'=>[$b1147.'/AppCheck.php','278b03d1914580063a71f4f12c99e741376fbedb268e4570614dc3ccf3213b73'],'b1147-coordinator.php'=>[$b1147.'/CatalogOverlayCoordinator.php','d52bf4d475d3b3b1e6bd33fb6e766d24414b19b0fb3ee4dc0e9cdce59ed9cde9'],
    // The approved B1148 package members (6558e7a1), on the host since Report1113 (digests equal the b1148-package.json of the delivered ZIP).
    'b1148-web.php'=>[$b1148.'/GlassWeb.php','69bbdca104bfcbc62948e06a464fcaff55b5bf100a7ab513299fb19d00600c7c'],'b1148-app-check.php'=>[$b1148.'/AppCheck.php','0951b1ca3e9fcf6b7a2c722ce412903b593a9aa0b63dca5200f810a54c39e16a'],'b1148-coordinator.php'=>[$b1148.'/GlassOverlayCoordinator.php','332a13a5014845051aea3ffa68591b6075d798e94be5428e3cb288d222d3641b']];
foreach($pins as $name=>[$file,$digest])if(hash_file('sha256',$file)!==$digest)throw new RuntimeException('PINNED_DEPENDENCY_DRIFT_'.$name);
$members=['b1149-installer.php'=>'operator-entry.php','b1149-coordinator.php'=>'StorefrontInstallCoordinator.php','b1149-web.php'=>'StorefrontWeb.php','b1149-app-check.php'=>'AppCheck.php','b1149-README-SR.md'=>'README-SR.md'];
$bytes=[];foreach($members as $name=>$file){$data=file_get_contents($tools.'/'.$file);if(!is_string($data)||str_contains($data,"\r"))throw new RuntimeException('PACKAGE_SOURCE_BYTES_'.$name);$bytes[$name]=$data;}
// The shipped tool code must not reach cPanel, reset OPcache globally, overlay PHP flags, migrate, seed, build any cache, POST or
// send a custom method, send mail, or write the DB switch or any row. route:clear and queue:restart are the only allowed artisan
// cache commands (coded once each, in refreshViews).
foreach($bytes as $name=>$data)if(str_ends_with($name,'.php')&&preg_match('~/usr/local/cpanel|uapi|DomainInfo|opcache_reset|php_flag|HostProcessGuard|--seed|migrate:fresh|migrate:rollback|migrate:refresh|migrate:reset|db:wipe|\'migrate\'|config:cache|view:cache|route:cache|public_storefront_open|->update\(|->insert\(|->upsert\(|->delete\(|->set\(|->put\(|Mail::|mail\(|CURLOPT_POST|CURLOPT_CUSTOMREQUEST|mail:test|app:send-test-mail~i',$data))throw new RuntimeException('PACKAGE_FORBIDDEN_MECHANISM_'.$name);
if(substr_count($bytes['b1149-coordinator.php'],"artisanOk(['route:clear'])")!==1||substr_count($bytes['b1149-coordinator.php'],"artisanOk(['queue:restart'])")!==1)throw new RuntimeException('PACKAGE_ARTISAN_CACHE_COMMANDS');

$overlay=[];$new=[];$old=[];$digests="n\tgroup\tkind\tpath\tpre_sha256_git_eca7755c\tpre_state_source\tpayload_sha256_git_7e748158\n";
foreach(StorefrontInstallCoordinator::STEPS as $i=>[$path,$kind,$group]){
    $n=$i+1;$payload=$blob($source,$path);$pre=$kind==='modified'?$blob($base,$path):null;$new[$path]=$payload;$old[$path]=$pre;$liveBefore='ABSENT';
    if($kind==='new'&&($git(['cat-file','-e',$base.':'.CMS.$path],true)!==null||isset($byPath[$path])||isset($b1146Sha[$path])||isset($b1147Sha[$path])||isset($b1148Sha[$path])))throw new RuntimeException('PACKAGE_NEW_PATH_EXISTS_'.$n);
    if($kind==='modified'){$preSha=hash('sha256',(string)$pre);[$live,$liveBefore]=isset($b1148Sha[$path])?[$b1148Sha[$path],'B1148_PAYLOAD']:(isset($b1147Sha[$path])?[$b1147Sha[$path],'B1147_PAYLOAD']:(isset($b1146Sha[$path])?[$b1146Sha[$path],'B1146_PAYLOAD']:[$byPath[$path]??null,'RUNTIME_MANIFEST']));if($live!==$preSha)throw new RuntimeException('PACKAGE_PRE_STATE_NOT_LIVE_'.$n.'_'.$path);}
    if(str_contains($payload,"\r"))throw new RuntimeException('PACKAGE_PAYLOAD_CR_'.$n);
    $member=sprintf('b1149-payload-%02d.bin',$n);$bytes[$member]=$payload;$overlay[]=['n'=>$n,'path'=>$path,'kind'=>$kind,'group'=>$group,'payload'=>$member,'payloadSHA256'=>hash('sha256',$payload),'preSHA256'=>$pre===null?null:hash('sha256',$pre),'size'=>strlen($payload),'liveBefore'=>$liveBefore];
    $digests.=$n."\t".$group."\t".$kind."\t".$path."\t".($pre===null?'ABSENT':hash('sha256',$pre))."\t".$liveBefore."\t".hash('sha256',$payload)."\n";
}
$layout='resources/views/public-shop/layout.blade.php';$css='public/assets/css/ald1n-public-storefront.css';$gallery='public/assets/css/ald1n-public-gallery.css';$catalogView='resources/views/public-shop/catalog.blade.php';$show='resources/views/public-shop/show.blade.php';
foreach([['layout',$layout],['css',$css],['gallery',$gallery],['catalog',$catalogView],['catalog',$show]] as [$k,$p])if(!str_contains($new[$p],MARKERS[$k])||str_contains((string)$old[$p],MARKERS[$k]))throw new RuntimeException('PACKAGE_MARKER_'.strtoupper($k).'_'.$p);
$version=static function(string $text,string $file):string{if(preg_match_all('~'.preg_quote($file,'~').'\?v=([0-9]{1,12})[\'"]~',$text,$v)!==1)throw new RuntimeException('PACKAGE_CSS_VERSION');return $v[1][0];};
$cssVersion=$version($new[$layout],'ald1n-public-storefront.css');$oldCssVersion=$version((string)$old[$layout],'ald1n-public-storefront.css');if($cssVersion===$oldCssVersion)throw new RuntimeException('PACKAGE_CSS_VERSION_UNCHANGED');
$galleryVersion=$version($new[$catalogView],'ald1n-public-gallery.css');if($galleryVersion!==$version($new[$show],'ald1n-public-gallery.css'))throw new RuntimeException('PACKAGE_GALLERY_VERSION_DIFFERS');
// The dependency facts behind the install order, in the shipped bytes (absent from the old bytes where they are new).
$facts=['routes/web.php'=>['SiteLogoAssetService::FILE_PATTERN',"->name('site-assets.show')","->name('smtp.index')",'throttle:smtp-test'],'resources/views/layouts/app.blade.php'=>["route('admin.settings.smtp.index')"],'app/Http/Middleware/PublicNoScriptCsp.php'=>['PublicStorefrontAppearance::staleThemeCookie'],
    'app/Support/PublicStorefrontAppearance.php'=>['function staleThemeCookie'],'app/Support/PublicProductImages.php'=>['ProductImageDerivativeService::exifOrientation'],'app/Services/ProductImageDerivativeService.php'=>['function exifOrientation'],
    'bootstrap/providers.php'=>['MailSettingsServiceProvider::class'],'app/Providers/MailSettingsServiceProvider.php'=>["RateLimiter::for('smtp-test'"],'app/Http/Middleware/SecurityHeaders.php'=>['SiteAssetController::applySecurityHeaders'],'app/Http/Controllers/SiteAssetController.php'=>['function applySecurityHeaders'],
    'bootstrap/app.php'=>["'shop_theme'","trimStrings(except: ['smtp_password'])"]];
foreach($facts as $p=>$needles)foreach($needles as $needle)if(!str_contains($new[$p],$needle)||($old[$p]!==null&&str_contains($old[$p],$needle)))throw new RuntimeException('PACKAGE_ORDER_FACT_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/','_',$p.'_'.$needle)));
// The nonce'd theme script and its CSP are new in these bytes (the old state is scriptless).
if(!str_contains($new[$layout],'<script nonce="')||str_contains((string)$old[$layout],'<script nonce="')||!str_contains($new['app/Http/Middleware/PublicNoScriptCsp.php'],"script-src 'nonce-")||str_contains((string)$old['app/Http/Middleware/PublicNoScriptCsp.php'],"script-src 'nonce-"))throw new RuntimeException('PACKAGE_NONCE_SCRIPT_FACT');
$files=[];foreach($pins as $name=>[,$digest])$files[$name]=$digest;foreach($bytes as $name=>$data)$files[$name]=hash('sha256',$data);ksort($files,SORT_STRING);
$common=['schemaVersion'=>1,'batch'=>'B1149','sourceCommit'=>$source,'baseCommit'=>$base,'mainCommit'=>$main,'glassCommit'=>$glass,'catalogCommit'=>$catalog,'legalCommit'=>$legal,'b1146BaseCommit'=>$b1146Base,'installedSourceCommit'=>INSTALLED,'helperSource'=>$helper];
$bytes['b1149-package.json']=json_encode($common+['overlay'=>$overlay,'b1146Overlay'=>$b1146Overlay,'b1147Overlay'=>$b1147Overlay,'b1148Overlay'=>$b1148Overlay,'markers'=>MARKERS,'cssVersion'=>$cssVersion,'oldCssVersion'=>$oldCssVersion,'galleryVersion'=>$galleryVersion,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
$check=json_decode($bytes['b1149-package.json'],true,64,JSON_THROW_ON_ERROR);StorefrontInstallCoordinator::plan($check,'/home/icaffeco/ald1n-project/incoming');StorefrontInstallCoordinator::b1146Plan($check);StorefrontInstallCoordinator::b1147Plan($check);StorefrontInstallCoordinator::b1148Plan($check);StorefrontInstallCoordinator::markers($check);
$bytes['b1149-provenance.json']=json_encode($common+['author'=>'Ald1n <https://ald1n.com>','workflowRun'=>getenv('GITHUB_RUN_ID')?:null,'workflowRepository'=>getenv('GITHUB_REPOSITORY')?:null,
    'predecessor'=>['report'=>'docs/operations/1113-B1148-GLASS-OVERLAY-<date>-<time>.md','bytesPinned'=>false,'required'=>['B1148_FINAL'=>StorefrontInstallCoordinator::B1148_PASS,'EXIT'=>'0','B1148_PHASE'=>'INSTALLED_PASS','b1148State'=>'INSTALLED_PASS']],
    'runtimeManifestSHA256'=>StorefrontInstallCoordinator::MANIFEST_SHA,'treeFilesBefore'=>StorefrontInstallCoordinator::B1148_TREE_FILES,'treeFilesAfter'=>StorefrontInstallCoordinator::INSTALLED_TREE_FILES,'configAfterB1145SHA256'=>StorefrontInstallCoordinator::CONFIG_SHA,'excludedFromTheSourceDiff'=>$excluded,'installOrder'=>array_map(static fn($s)=>$s[2].' '.$s[1].' '.$s[0],StorefrontInstallCoordinator::STEPS),'preStateSource'=>array_map(static fn($o)=>$o['path'].' '.$o['liveBefore'],$overlay),'pinnedNotRedelivered'=>array_map(fn($p)=>$p[1],$pins),
    'cloudflare'=>['purgeSiteAssets'=>'https://cms.ald1n.com/site-assets/*','cachingLevel'=>'STANDARD_NO_QUERY_STRIPPING_FOR_/site-assets/*_NO_CACHE_EVERYTHING_FOR_/katalog*','newCssUrl'=>'https://cms.ald1n.com'.StorefrontInstallCoordinator::CSS_PATH.'?v='.$cssVersion,'oldCssUrl'=>'https://cms.ald1n.com'.StorefrontInstallCoordinator::CSS_PATH.'?v='.$oldCssVersion,'galleryCssUrl'=>'https://cms.ald1n.com'.StorefrontInstallCoordinator::GALLERY_CSS_PATH.'?v='.$galleryVersion],
    'newRoutes'=>['GET /site-assets/{file} site-assets.show','GET|PUT|POST|DELETE /admin/settings/smtp* admin.settings.smtp.*'],'rateLimiterDefinedByTheLastFile'=>'smtp-test (MailSettingsServiceProvider, activated by bootstrap/providers.php, the last step)',
    'neverDone'=>['storefrontDbSwitchWrite','merchantApprovalWrite','smtpSettingsReadOrWrite','testMail','migration','composer','routeCacheBuild','envEdit','configChange','opcacheGlobalReset','viewCache','login','productOrCartWrite','themePost'],'operatorCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1149-installer.sh','rollbackCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1149-installer.sh --rollback'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
ksort($bytes,SORT_STRING);$checksums='';foreach($bytes as $name=>$data)$checksums.=hash('sha256',$data).'  '.$name."\n";
$wrapper=file_get_contents($tools.'/operator-wrapper.sh');if(!is_string($wrapper)||str_contains($wrapper,"\r")||substr_count($wrapper,"\nSELF_SHA=__SELF_SHA__\n")!==1||substr_count($wrapper,"\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n")!==1)throw new RuntimeException('WRAPPER_TEMPLATE');
$wrapper=str_replace("\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n","\nCHECKSUMS_SHA=".hash('sha256',$checksums)."\n",$wrapper);$wrapper=str_replace("\nSELF_SHA=__SELF_SHA__\n","\nSELF_SHA=".hash('sha256',$wrapper)."\n",$wrapper);
$bytes['b1149-package.sha256']=$checksums;$bytes['b1149-installer.sh']=$wrapper;ksort($bytes,SORT_STRING);
file_put_contents($out.'/b1149-digests.tsv',$digests);
$zipPath=$out.'/b1149-operator.zip';if(file_exists($zipPath))unlink($zipPath);$zip=new ZipArchive();if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('ZIP_CREATE');
foreach($bytes as $name=>$data){$member='incoming/'.$name;$zip->addFromString($member,$data);$zip->setExternalAttributesName($member,ZipArchive::OPSYS_UNIX,(str_ends_with($name,'.sh')?0100755:0100644)<<16);$zip->setMtimeName($member,1762732800);}
if(!$zip->close())throw new RuntimeException('ZIP_CLOSE');
$zipSHA=hash_file('sha256',$zipPath);file_put_contents($zipPath.'.sha256',$zipSHA.'  b1149-operator.zip'."\n");
echo json_encode(['zip'=>basename($zipPath),'zipSHA256'=>$zipSHA,'zipBytes'=>filesize($zipPath),'members'=>array_map(fn($n)=>'incoming/'.$n,array_keys($bytes)),'checksumsSHA256'=>hash('sha256',$checksums),'wrapperSHA256'=>hash('sha256',$wrapper),'cssVersion'=>$cssVersion,'oldCssVersion'=>$oldCssVersion,'galleryVersion'=>$galleryVersion,'excluded'=>$excluded,'overlay'=>$overlay,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
