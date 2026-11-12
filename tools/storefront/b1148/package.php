<?php
declare(strict_types=1);
/**
 * Builds the B1148 operator ZIP with root-relative incoming/ members only. Everything is read from git at build time, never
 * from a working tree: the payload is git show SOURCE_COMMIT:<path> (the storefront glass head), the pre-state is
 * git show 764bf513:<path> (the B1147 catalog payload, live in production), and the two earlier overlays (B1146: eabe16af
 * over 77a3efea; B1147: 764bf513 over eabe16af) that turn the 8531-file runtime manifest into the live 8546-file tree.
 * The payload is shipped as flat incoming/b1148-payload-NN.bin members, so unzip never writes into the live CMS tree.
 * The build fails unless:
 * - git diff --name-status 764bf513 SOURCE_COMMIT, minus apps/cms/current/tests/ and .github/workflows/, is exactly the
 *   coded install list (2 new, 9 modified, all under apps/cms/current) and 764bf513 is an ancestor of SOURCE_COMMIT;
 * - the B1146 and B1147 file sets equal the pinned B1146 and B1147 coordinators' lists, each pre digest equals the live
 *   digest before it (manifest, or the earlier overlay's payload) and each new path is absent before;
 * - each B1148 pre digest equals the live digest: the B1147 payload, else the B1146 payload, else the manifest (printed per
 *   file), and each new path is absent at 764bf513, in the manifest and in both earlier overlays;
 * - the markers (the theme-switch form in the layout, a new CSS selector) are in the new bytes and absent from the old,
 *   the layout links the CSS with exactly one static ?v= that differs from the old one, and the dependency facts behind the
 *   install order hold in the new bytes (layout calls route('public-shop.theme'), routes name it behind
 *   throttle:public-shop-theme, the provider defines that limiter, bootstrap/app.php exempts 'tema', the middleware sets
 *   Vary: Cookie).
 * The B1134-B1147 helpers already on the host are verified by digest (package.json), not redelivered.
 * Usage: php package.php <repo root> <b1148 dir> <b1134 dir> <b1144 dir> <b1145 dir> <b1146 dir> <b1147 dir> <runtime-manifest.json> <output dir> <helper source commit> <source commit>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$repo,$tools,$b1134,$b1144,$b1145,$b1146,$b1147,$manifestPath,$out,$helper,$sourceArg]=$argv+array_fill(0,12,null);
if(!is_dir((string)$repo.'/.git')&&!is_file((string)$repo.'/.git'))throw new RuntimeException('PACKAGE_REPO');if(!is_dir((string)$tools)||!is_dir((string)$b1147)||!is_file((string)$manifestPath)||!preg_match('/^[a-f0-9]{40}$/D',(string)$helper)||!preg_match('/^[a-f0-9]{40}$/D',(string)$sourceArg))throw new RuntimeException('PACKAGE_ARGUMENTS');if(!is_dir($out)&&!mkdir($out,0755,true))throw new RuntimeException('PACKAGE_OUTPUT');
require $b1134.'/InstallFileOps.php';require $b1144.'/FreshnessProbe.php';require $b1144.'/LeanGates.php';require $b1144.'/LeanWeb.php';require $b1145.'/EnvFlags.php';require $b1145.'/MigrateUnclampCoordinator.php';require $b1146.'/OverlayProbe.php';require $b1146.'/OverlayWeb.php';require $b1146.'/OverlayCoordinator.php';require $b1147.'/CatalogWeb.php';require $b1147.'/CatalogOverlayCoordinator.php';require $tools.'/GlassWeb.php';require $tools.'/GlassOverlayCoordinator.php';
const CMS='apps/cms/current/';const B1146_BASE='77a3efea';const INSTALLED='ef3f927b77ba573881c2b6dba341732c417e6725';
const MARKERS=['layout'=>'class="shop-theme" method="post"','css'=>'.shop-theme-option'];
$source=GlassOverlayCoordinator::SOURCE_COMMIT;$base=GlassOverlayCoordinator::BASE_COMMIT;$main=GlassOverlayCoordinator::MAIN_COMMIT;
// The workflow passes the head it reviewed; it must be the commit the coordinator is pinned to.
if($sourceArg!==$source)throw new RuntimeException('PACKAGE_SOURCE_ARGUMENT_DIFFERS_FROM_THE_PINNED_SOURCE_COMMIT');
$git=static function(array $args,bool $allowFail=false)use($repo):?string{$p=proc_open(array_merge(['git','-C',$repo,'-c','core.autocrlf=false'],$args),[1=>['pipe','w'],2=>['pipe','w']],$pipes);$o=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$rc=proc_close($p);if($rc!==0){if($allowFail)return null;throw new RuntimeException('PACKAGE_GIT_'.$args[0]);}return (string)$o;};
$blob=static fn(string $commit,string $path):string=>(string)$git(['show',$commit.':'.CMS.$path]);
foreach([$source,$base,$main,INSTALLED] as $c)if(trim((string)$git(['rev-parse','--verify',$c.'^{commit}']))!==$c)throw new RuntimeException('PACKAGE_COMMIT_'.substr($c,0,8));
if($main!==OverlayCoordinator::SOURCE_COMMIT||$base!==CatalogOverlayCoordinator::SOURCE_COMMIT||$main!==CatalogOverlayCoordinator::BASE_COMMIT)throw new RuntimeException('PACKAGE_CHAIN_COMMITS_DIFFER_FROM_THE_PINNED_COORDINATORS');
if($git(['merge-base','--is-ancestor',$main,$base],true)===null||$git(['merge-base','--is-ancestor',$base,$source],true)===null)throw new RuntimeException('PACKAGE_SOURCE_NOT_ON_TOP_OF_THE_B1147_PAYLOAD');
$b1146Base=trim((string)$git(['rev-parse','--verify',B1146_BASE.'^{commit}']));if(!preg_match('/^77a3efea[a-f0-9]{32}$/D',$b1146Base))throw new RuntimeException('PACKAGE_B1146_BASE');
if($git(['diff','--quiet',INSTALLED,$b1146Base,'--','apps/cms/current'],true)===null)throw new RuntimeException('PACKAGE_INSTALLED_TREE_DIFFERS_FROM_B1146_BASE');
$diff=static function(string $from,string $to,?string $scope='apps/cms/current')use($git):array{$d=[];$args=['diff','--no-renames','--name-status',$from,$to];if($scope!==null)array_push($args,'--',$scope);foreach(explode("\n",trim((string)$git($args))) as $line){if(!preg_match('~^([AMD])\t(\S+)$~D',$line,$m))throw new RuntimeException('PACKAGE_DIFF_LINE');$d[$m[2]]=$m[1];}ksort($d,SORT_STRING);return $d;};
$cms=static function(array $d):array{$o=[];foreach($d as $p=>$k){if(!str_starts_with($p,CMS)||$k==='D')throw new RuntimeException('PACKAGE_DIFF_OUTSIDE_CMS_OR_DELETE_'.$p);$o[substr($p,strlen(CMS))]=$k;}return $o;};
$manifest=json_decode((string)file_get_contents($manifestPath),true,512,JSON_THROW_ON_ERROR);if(hash_file('sha256',$manifestPath)!==GlassOverlayCoordinator::MANIFEST_SHA||count($manifest['files'])!==GlassOverlayCoordinator::MANIFEST_FILES)throw new RuntimeException('PACKAGE_RUNTIME_MANIFEST');$byPath=[];foreach($manifest['files'] as $f)$byPath[$f['path']]=$f['sha256'];

// B1146 (PR #152 = main eabe16af over 77a3efea), as the B1147 build computes it.
$d1146=$cms($diff($b1146Base,$main));foreach(['tests/Feature/PublicLegalPagesB1146Test.php'=>'A','tests/Feature/PublicMerchantSettingsB801Test.php'=>'M'] as $p=>$k){if(($d1146[$p]??null)!==$k)throw new RuntimeException('PACKAGE_B1146_EXCLUDED_TEST_SET');unset($d1146[$p]);}
$e=[];foreach(OverlayCoordinator::STEPS as [$p,$kind])$e[$p]=$kind==='new'?'A':'M';ksort($e,SORT_STRING);if($d1146!==$e)throw new RuntimeException('PACKAGE_B1146_FILE_SET');
$b1146Overlay=[];$b1146Sha=[];
foreach(OverlayCoordinator::STEPS as [$p,$kind]){
    $sha=hash('sha256',$blob($main,$p));$pre=$kind==='modified'?hash('sha256',$blob($b1146Base,$p)):null;
    if($kind==='modified'&&($byPath[$p]??null)!==$pre)throw new RuntimeException('PACKAGE_B1146_PRE_NOT_MANIFEST');if($kind==='new'&&isset($byPath[$p]))throw new RuntimeException('PACKAGE_B1146_NEW_IN_MANIFEST');
    $b1146Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1146Sha[$p]=$sha;
}
// B1147 (PR #154 head 764bf513 over eabe16af, tests excluded), as the approved B1147 build computes it.
$d1147=$cms($diff($main,$base));foreach(array_keys($d1147) as $p)if(str_starts_with($p,'tests/'))unset($d1147[$p]);
$e=[];foreach(CatalogOverlayCoordinator::STEPS as [$p,$kind])$e[$p]=$kind==='new'?'A':'M';ksort($e,SORT_STRING);if($d1147!==$e)throw new RuntimeException('PACKAGE_B1147_FILE_SET');
$b1147Overlay=[];$b1147Sha=[];
foreach(CatalogOverlayCoordinator::STEPS as [$p,$kind]){
    $sha=hash('sha256',$blob($base,$p));$pre=$kind==='modified'?hash('sha256',$blob($main,$p)):null;
    if($kind==='modified'&&($b1146Sha[$p]??($byPath[$p]??null))!==$pre)throw new RuntimeException('PACKAGE_B1147_PRE_NOT_LIVE');if($kind==='new'&&(isset($byPath[$p])||isset($b1146Sha[$p])))throw new RuntimeException('PACKAGE_B1147_NEW_ALREADY_LIVE');
    $b1147Overlay[]=['path'=>$p,'kind'=>$kind,'sha256'=>$sha,'preSHA256'=>$pre];$b1147Sha[$p]=$sha;
}

// The B1148 glass file set: the whole diff 764bf513..SOURCE minus the CMS tests and the CI workflows.
$all=$diff($base,$source,null);$excluded=[];foreach(array_keys($all) as $p)if(str_starts_with($p,CMS.'tests/')||str_starts_with($p,'.github/workflows/')){$excluded[]=$p;unset($all[$p]);}
$d=$cms($all);$expected=[];foreach(GlassOverlayCoordinator::STEPS as [$p,$kind])$expected[$p]=$kind==='new'?'A':'M';ksort($expected,SORT_STRING);
if($d!==$expected){fwrite(STDERR,json_encode(['diff'=>$d,'expected'=>$expected],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL);throw new RuntimeException('PACKAGE_FILE_SET_DIFFERS_FROM_THE_GLASS_DIFF');}

$pins=['b1134-files.php'=>[$b1134.'/InstallFileOps.php','9dce8324bd4464a1689918775736bcd3a850f2b1c2b1b0ca7e130a9e486da12b'],'b1144-gates.php'=>[$b1144.'/LeanGates.php','7e62150fcad0884255ddb3c18063f5e2a23852a3eb0fdecb978c2955392ccf6e'],'b1144-probe.php'=>[$b1144.'/FreshnessProbe.php','3dbf4762e419d8df053fa7e642bd1b85df07d66dbffdb94fe0d8d5cdc1b50616'],'b1144-web.php'=>[$b1144.'/LeanWeb.php','54a04bc4dea81d56ad5fe0e6b25a141d4a86acf803f6df95f1c5d2b7adff6a73'],
    'b1145-env.php'=>[$b1145.'/EnvFlags.php','04a4bbd13b8aef346247d8641445ccb9f0a2d7fd3f26f7a03723724fc0132f69'],'b1145-app-state.php'=>[$b1145.'/AppState.php','642ddede76db8f1ea66bfc6a8d1ad74c894a70b665204cac4c96de17e97113a5'],'b1145-coordinator.php'=>[$b1145.'/MigrateUnclampCoordinator.php','46b159fbeab6c4f849dbb1a9291bbf13245b511ea58fd51646f26f28caa53fb9'],
    'b1146-probe.php'=>[$b1146.'/OverlayProbe.php','abcc9c64c6b9918d180afd387ec917a4924e1ce28c0099c0bbdae500e811c01f'],'b1146-app-check.php'=>[$b1146.'/AppCheck.php','68b4b2af5ad5a6399991adf474cfc753abf77a483fb12d2c1669a4b307ce2c2b'],
    // The approved B1147 package members (c41f78fc), on the host since Report1112.
    'b1147-web.php'=>[$b1147.'/CatalogWeb.php','c4e4c0935b4c14e7fbee1089243f5be4784c5b25c5c0a62d11551d5dfa1461ce'],'b1147-app-check.php'=>[$b1147.'/AppCheck.php','278b03d1914580063a71f4f12c99e741376fbedb268e4570614dc3ccf3213b73'],'b1147-coordinator.php'=>[$b1147.'/CatalogOverlayCoordinator.php','d52bf4d475d3b3b1e6bd33fb6e766d24414b19b0fb3ee4dc0e9cdce59ed9cde9']];
foreach($pins as $name=>[$file,$digest])if(hash_file('sha256',$file)!==$digest)throw new RuntimeException('PINNED_DEPENDENCY_DRIFT_'.$name);
$members=['b1148-glass-overlay.php'=>'operator-entry.php','b1148-coordinator.php'=>'GlassOverlayCoordinator.php','b1148-web.php'=>'GlassWeb.php','b1148-app-check.php'=>'AppCheck.php','b1148-README-SR.md'=>'README-SR.md'];
$bytes=[];foreach($members as $name=>$file){$data=file_get_contents($tools.'/'.$file);if(!is_string($data)||str_contains($data,"\r"))throw new RuntimeException('PACKAGE_SOURCE_BYTES_'.$name);$bytes[$name]=$data;}
// The shipped tool code must not reach cPanel, reset OPcache globally, overlay PHP flags, migrate, seed, cache or clear routes,
// pre-compile views, POST or send a custom method, or write the DB switch or any row.
foreach($bytes as $name=>$data)if(str_ends_with($name,'.php')&&preg_match('~/usr/local/cpanel|uapi|DomainInfo|opcache_reset|php_flag|HostProcessGuard|--seed|migrate:fresh|migrate:rollback|migrate:refresh|migrate:reset|db:wipe|\'migrate\'|config:cache|view:cache|route:cache|route:clear|public_storefront_open|->update\(|->insert\(|->upsert\(|->delete\(|->set\(|->put\(|Mail::|mail\(|CURLOPT_POST|CURLOPT_CUSTOMREQUEST~i',$data))throw new RuntimeException('PACKAGE_FORBIDDEN_MECHANISM_'.$name);

$overlay=[];$new=[];$old=[];
foreach(GlassOverlayCoordinator::STEPS as $i=>[$path,$kind,$group]){
    $n=$i+1;$payload=$blob($source,$path);$pre=$kind==='modified'?$blob($base,$path):null;$new[$path]=$payload;$old[$path]=$pre;$liveBefore='ABSENT';
    if($kind==='new'&&($git(['cat-file','-e',$base.':'.CMS.$path],true)!==null||isset($byPath[$path])||isset($b1146Sha[$path])||isset($b1147Sha[$path])))throw new RuntimeException('PACKAGE_NEW_PATH_EXISTS_'.$n);
    if($kind==='modified'){$preSha=hash('sha256',(string)$pre);[$live,$liveBefore]=isset($b1147Sha[$path])?[$b1147Sha[$path],'B1147_PAYLOAD']:(isset($b1146Sha[$path])?[$b1146Sha[$path],'B1146_PAYLOAD']:[$byPath[$path]??null,'RUNTIME_MANIFEST']);if($live!==$preSha)throw new RuntimeException('PACKAGE_PRE_STATE_NOT_LIVE_'.$n);}
    if(str_contains($payload,"\r"))throw new RuntimeException('PACKAGE_PAYLOAD_CR_'.$n);
    $member=sprintf('b1148-payload-%02d.bin',$n);$bytes[$member]=$payload;$overlay[]=['n'=>$n,'path'=>$path,'kind'=>$kind,'group'=>$group,'payload'=>$member,'payloadSHA256'=>hash('sha256',$payload),'preSHA256'=>$pre===null?null:hash('sha256',$pre),'size'=>strlen($payload),'liveBefore'=>$liveBefore];
}
$layout='resources/views/public-shop/layout.blade.php';$css='public/assets/css/ald1n-public-storefront.css';
foreach(['layout'=>$layout,'css'=>$css] as $k=>$p)if(!str_contains($new[$p],MARKERS[$k])||str_contains((string)$old[$p],MARKERS[$k]))throw new RuntimeException('PACKAGE_MARKER_'.strtoupper($k));
// No old view of the live tree carries the layout marker (legal pages render the same layout).
foreach(array_merge($b1146Overlay,$b1147Overlay) as $e)if(str_ends_with($e['path'],'.blade.php')&&str_contains($blob($e['path']===$layout||isset($b1147Sha[$e['path']])?$base:$main,$e['path']),MARKERS['layout']))throw new RuntimeException('PACKAGE_LAYOUT_MARKER_IN_OLD_VIEW');
$version=static function(string $text):string{if(preg_match_all('~ald1n-public-storefront\.css\?v=([0-9]{1,12})[\'"]~',$text,$v)!==1)throw new RuntimeException('PACKAGE_CSS_VERSION');return $v[1][0];};
$cssVersion=$version($new[$layout]);$oldCssVersion=$version((string)$old[$layout]);if($cssVersion===$oldCssVersion)throw new RuntimeException('PACKAGE_CSS_VERSION_UNCHANGED');
// The dependency facts behind the install order, in the shipped bytes.
$facts=['resources/views/public-shop/layout.blade.php'=>["route('public-shop.theme'",'\App\Support\PublicStorefrontAppearance::'],'routes/web.php'=>["Route::post('/tema', \App\Http\Controllers\PublicThemeController::class)","throttle:public-shop-theme","->name('theme')"],
    'app/Providers/AppServiceProvider.php'=>["RateLimiter::for('public-shop-theme'"],'bootstrap/app.php'=>["'tema',"],'app/Http/Middleware/PublicNoScriptCsp.php'=>["setVary('Cookie'"],'app/Support/PublicCatalogPresentation.php'=>['PublicStorefrontAppearance::brandName()'],'resources/views/public-shop/catalog.blade.php'=>["\$seo['site_name']"]];
foreach($facts as $p=>$needles)foreach($needles as $needle)if(!str_contains($new[$p],$needle)||($old[$p]!==null&&str_contains($old[$p],$needle)))throw new RuntimeException('PACKAGE_ORDER_FACT_'.strtoupper(preg_replace('/[^A-Za-z0-9]+/','_',$p)));
if(!str_contains($new['app/Support/PublicStorefrontAppearance.php'],'final class PublicStorefrontAppearance')||!str_contains($new['app/Http/Controllers/PublicThemeController.php'],'final class PublicThemeController'))throw new RuntimeException('PACKAGE_NEW_CLASSES');
$files=[];foreach($pins as $name=>[,$digest])$files[$name]=$digest;foreach($bytes as $name=>$data)$files[$name]=hash('sha256',$data);ksort($files,SORT_STRING);
$common=['schemaVersion'=>1,'batch'=>'B1148','sourceCommit'=>$source,'baseCommit'=>$base,'mainCommit'=>$main,'b1146BaseCommit'=>$b1146Base,'installedSourceCommit'=>INSTALLED,'helperSource'=>$helper];
$bytes['b1148-package.json']=json_encode($common+['overlay'=>$overlay,'b1146Overlay'=>$b1146Overlay,'b1147Overlay'=>$b1147Overlay,'markers'=>MARKERS,'cssVersion'=>$cssVersion,'oldCssVersion'=>$oldCssVersion,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
$check=json_decode($bytes['b1148-package.json'],true,64,JSON_THROW_ON_ERROR);GlassOverlayCoordinator::plan($check,'/home/icaffeco/ald1n-project/incoming');GlassOverlayCoordinator::b1146Plan($check);GlassOverlayCoordinator::b1147Plan($check);GlassOverlayCoordinator::markers($check);
$bytes['b1148-provenance.json']=json_encode($common+['author'=>'Ald1n <https://ald1n.com>','workflowRun'=>getenv('GITHUB_RUN_ID')?:null,'workflowRepository'=>getenv('GITHUB_REPOSITORY')?:null,'pullRequest'=>155,
    'predecessor'=>['report'=>'docs/operations/1112-B1147-CATALOG-OVERLAY-<date>-<time>.md','bytesPinned'=>false,'required'=>['B1147_FINAL'=>GlassOverlayCoordinator::B1147_PASS,'EXIT'=>'0','B1147_PHASE'=>'INSTALLED_PASS','b1147State'=>'INSTALLED_PASS']],
    'runtimeManifestSHA256'=>GlassOverlayCoordinator::MANIFEST_SHA,'treeFilesBefore'=>GlassOverlayCoordinator::B1147_TREE_FILES,'treeFilesAfter'=>GlassOverlayCoordinator::INSTALLED_TREE_FILES,'configAfterB1145SHA256'=>GlassOverlayCoordinator::CONFIG_SHA,'excludedFromTheGlassDiff'=>$excluded,'installOrder'=>array_map(static fn($s)=>$s[2].' '.$s[1].' '.$s[0],GlassOverlayCoordinator::STEPS),'preStateSource'=>array_map(static fn($o)=>$o['path'].' '.$o['liveBefore'],$overlay),'pinnedNotRedelivered'=>array_map(fn($p)=>$p[1],$pins),
    'cloudflare'=>['purgeNeeded'=>false,'newCssUrl'=>'https://cms.ald1n.com'.GlassOverlayCoordinator::CSS_PATH.'?v='.$cssVersion,'oldCssUrl'=>'https://cms.ald1n.com'.GlassOverlayCoordinator::CSS_PATH.'?v='.$oldCssVersion],
    'newRoute'=>['method'=>'POST','uri'=>'/tema','name'=>'public-shop.theme','rateLimiter'=>'public-shop-theme','csrfExcept'=>'tema','routeCache'=>'MUST_BE_ABSENT'],
    'neverDone'=>['storefrontDbSwitchWrite','merchantApprovalWrite','migration','routeCacheOrClear','envEdit','configChange','opcacheGlobalReset','viewCache','mail','productOrCartWrite','themePost'],'operatorCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1148-glass-overlay.sh','rollbackCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1148-glass-overlay.sh --rollback'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
ksort($bytes,SORT_STRING);$checksums='';foreach($bytes as $name=>$data)$checksums.=hash('sha256',$data).'  '.$name."\n";
$wrapper=file_get_contents($tools.'/operator-wrapper.sh');if(!is_string($wrapper)||str_contains($wrapper,"\r")||substr_count($wrapper,"\nSELF_SHA=__SELF_SHA__\n")!==1||substr_count($wrapper,"\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n")!==1)throw new RuntimeException('WRAPPER_TEMPLATE');
$wrapper=str_replace("\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n","\nCHECKSUMS_SHA=".hash('sha256',$checksums)."\n",$wrapper);$wrapper=str_replace("\nSELF_SHA=__SELF_SHA__\n","\nSELF_SHA=".hash('sha256',$wrapper)."\n",$wrapper);
$bytes['b1148-package.sha256']=$checksums;$bytes['b1148-glass-overlay.sh']=$wrapper;ksort($bytes,SORT_STRING);
$zipPath=$out.'/b1148-operator.zip';if(file_exists($zipPath))unlink($zipPath);$zip=new ZipArchive();if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('ZIP_CREATE');
foreach($bytes as $name=>$data){$member='incoming/'.$name;$zip->addFromString($member,$data);$zip->setExternalAttributesName($member,ZipArchive::OPSYS_UNIX,(str_ends_with($name,'.sh')?0100755:0100644)<<16);$zip->setMtimeName($member,1762646400);}
if(!$zip->close())throw new RuntimeException('ZIP_CLOSE');
$zipSHA=hash_file('sha256',$zipPath);file_put_contents($zipPath.'.sha256',$zipSHA.'  b1148-operator.zip'."\n");
echo json_encode(['zip'=>basename($zipPath),'zipSHA256'=>$zipSHA,'zipBytes'=>filesize($zipPath),'members'=>array_map(fn($n)=>'incoming/'.$n,array_keys($bytes)),'checksumsSHA256'=>hash('sha256',$checksums),'wrapperSHA256'=>hash('sha256',$wrapper),'cssVersion'=>$cssVersion,'oldCssVersion'=>$oldCssVersion,'excluded'=>$excluded,'overlay'=>$overlay,'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
