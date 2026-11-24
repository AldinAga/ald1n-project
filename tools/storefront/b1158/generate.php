<?php
declare(strict_types=1);
/**
 * B1158 payload generator: ONE command from a target commit to everything that depends on it.
 *   php tools/storefront/b1158/generate.php <target sha> [--repo <dir>] [--chain-report <report of the installed predecessor>] [--base <sha>] [--prev-payload <Payload.php>] [--routes-after <n>] [--pin <path>=<A-F>] [--check]
 * Reads git only (never a working tree). The overlay is `git diff --no-renames --name-status <previous payload source> <target>` restricted to apps/cms/current, minus tests/ and docs/:
 *   A -> new file (group A, migrations last), M -> modified (group by rule below), D -> retained orphan (never deleted on the host).
 * Groups as in B1156 and B1157: A new files (inert), B libraries (app/ except controllers and the provider), C controllers + bin + public, D AppServiceProvider (guard: classes), E routes (guard: classes),
 * F views (guard: assets only when public/assets/css/ald1n-admin.css changes, partials before pages, layouts before pages). --pin overrides one path's group (the result is still validated).
 * Writes Payload.php (class B1158Payload) and README-SR.md (from README-SR.tpl.md) next to this file. --check writes nothing and fails when the committed Payload.php differs.
 * The route count after is an ESTIMATE (added minus removed Route:: declarations) unless --routes-after is given; the CI fixture measures the real count and fails on a difference.
 * @author Ald1n <https://ald1n.com>
 */
$args=array_slice($argv,1);$target=null;$repo=dirname(__DIR__,3);$reportFile=null;$baseArg=null;$prevPayload=__DIR__.'/../b1157/Payload.php';$routesAfterArg=null;$pins=[];$check=false;
for($i=0;$i<count($args);$i++){$a=$args[$i];
    if($a==='--repo')$repo=$args[++$i];elseif($a==='--report'||$a==='--chain-report')$reportFile=$args[++$i];elseif($a==='--base')$baseArg=$args[++$i];elseif($a==='--prev-payload')$prevPayload=$args[++$i];elseif($a==='--routes-after')$routesAfterArg=(int)$args[++$i];elseif($a==='--check')$check=true;
    elseif($a==='--pin'){[$p,$g]=explode('=',$args[++$i],2)+[1=>''];if(!preg_match('/^[A-F]$/D',$g))throw new RuntimeException('GENERATE_PIN');$pins[$p]=$g;}
    elseif(preg_match('/^[a-f0-9]{7,40}$/D',$a))$target=$a;else throw new RuntimeException('GENERATE_ARGUMENT_'.$a);}
if($target===null)throw new RuntimeException('GENERATE_USAGE_php_generate.php_<target sha>');
const CMS='apps/cms/current/';
// The predecessor payload (the generated Payload.php of the installed rollout) gives the base commit, the tree and route counts and the ran-migration count BEFORE this rollout, its orphans and its source markers.
require __DIR__.'/../b1156/CommissionsInstallCoordinator.php';
$prevText=(string)file_get_contents($prevPayload);if(!preg_match('/final class (\w+)/',$prevText,$pm))throw new RuntimeException('GENERATE_PREV_PAYLOAD');if(!class_exists($pm[1],false))require $prevPayload;$P=$pm[1];
$B_TREE=$P::TREE_FILES_AFTER;$B_ROUTES=$P::ROUTES_AFTER;$B_RAN=$P::RAN_AFTER;
$git=static function(array $a,bool $fail=true)use($repo):?string{$p=proc_open(array_merge(['git','-C',$repo,'-c','core.autocrlf=false'],$a),[1=>['pipe','w'],2=>['pipe','w']],$pipes);$o=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);$rc=proc_close($p);if($rc!==0){if($fail)throw new RuntimeException('GENERATE_GIT_'.$a[0].'_'.implode('_',array_slice($a,1,2)));return null;}return $o;};
$base=$baseArg===null?$P::SOURCE_COMMIT:trim((string)$git(['rev-parse','--verify',$baseArg.'^{commit}']));
if($base!==$P::SOURCE_COMMIT)throw new RuntimeException('GENERATE_BASE_IS_NOT_THE_PREVIOUS_PAYLOAD_SOURCE_PASS_THE_RIGHT_--prev-payload');
$src=trim((string)$git(['rev-parse','--verify',$target.'^{commit}']));
if($git(['merge-base','--is-ancestor',$base,$src],false)===null)throw new RuntimeException('GENERATE_TARGET_NOT_A_DESCENDANT_OF_THE_PREVIOUS_PAYLOAD_SOURCE');
if($src===$base)throw new RuntimeException('GENERATE_TARGET_IS_THE_PREVIOUS_PAYLOAD_SOURCE');
$blob=static fn(string $c,string $p):string=>(string)$git(['show',$c.':'.CMS.$p]);
$diff=[];foreach(explode("\n",trim((string)$git(['diff','--no-renames','--name-status',$base,$src,'--',rtrim(CMS,'/')]))) as $l){if($l==='')continue;if(!preg_match('~^([AMD])\t'.preg_quote(CMS,'~').'(\S+)$~D',$l,$m))throw new RuntimeException('GENERATE_DIFF_LINE');$diff[$m[2]]=$m[1];}
ksort($diff,SORT_STRING);
$excludedFiles=[];$orphansBefore=defined($P.'::ORPHANS_ALL')?constant($P.'::ORPHANS_ALL'):array_merge(CommissionsInstallCoordinator::ORPHANS,$P::ORPHANS);$new=[];$mod=[];$orph=[];
foreach($diff as $p=>$k){
    if(str_starts_with($p,'tests/')||str_starts_with($p,'docs/'))continue;
    // composer.lock is NOT installed (the vendor tree stays as it is on the host): excluded and reported, never shipped. Any other composer, config or .env change stops the build.
    if($p==='composer.lock'){$excludedFiles[$p]=[hash('sha256',$blob($base,$p)),hash('sha256',$blob($src,$p))];fwrite(STDERR,'WARNING composer.lock differs between the base and the target: it is EXCLUDED from the payload, the vendor tree on the host is NOT updated; check that the code does not need the newer vendor versions'.PHP_EOL);continue;}
    if(preg_match('~^(config/|composer\.|\.env)~',$p)===1)throw new RuntimeException('GENERATE_FORBIDDEN_PATH_'.$p);
    if(str_starts_with($p,'database/')&&!preg_match('~^database/migrations/\d{4}_\d{2}_\d{2}_\d{6}_[a-z0-9_]+\.php$~D',$p))throw new RuntimeException('GENERATE_FORBIDDEN_DATABASE_PATH_'.$p);
    if(str_starts_with($p,'routes/')&&!in_array($p,['routes/web.php','routes/api.php','routes/console.php'],true))throw new RuntimeException('GENERATE_FORBIDDEN_ROUTES_PATH_'.$p);
    if(str_starts_with($p,'bin/')&&!preg_match('~^bin/[A-Za-z0-9._-]+\.php$~D',$p))throw new RuntimeException('GENERATE_FORBIDDEN_BIN_PATH_'.$p);
    if($k==='D'){$orph[$p]=hash('sha256',$blob($base,$p));continue;}
    // A path that main deleted in B1156 and now adds again is a retained orphan on the host: it is modified, its pre digest is the orphan digest.
    if($k==='A'&&isset($orphansBefore[$p])){$mod[$p]=$orphansBefore[$p];continue;}
    if($k==='A')$new[]=$p;else $mod[$p]=hash('sha256',$blob($base,$p));
}
// Route names (->name('x') of web.php and api.php, matched by suffix because of group prefixes) removed and added by the payload.
$rn=static function(string $b):array{preg_match_all("~->name\\(\\s*'([^']+)'~",$b,$m);return array_values(array_unique($m[1]));};
$removedN=[];$addedN=[];foreach(['routes/web.php','routes/api.php'] as $rf){$o=$rn($blob($base,$rf));$n=$rn($blob($src,$rf));$removedN=array_merge($removedN,array_diff($o,$n));$addedN=array_merge($addedN,array_diff($n,$o));}
$removedN=array_values(array_unique($removedN));$addedN=array_values(array_unique($addedN));
$callsAny=static function(string $b,array $names):bool{if(!preg_match_all("~route\\(\\s*'([^']+)'~",$b,$m))return false;foreach($m[1] as $c)foreach($names as $n)if($c===$n||(str_contains($n,'.')&&str_ends_with($c,'.'.$n)))return true;return false;};
$groupOf=static function(string $p):string{
    if($p==='app/Providers/AppServiceProvider.php')return 'D';
    if($p==='routes/api.php'||$p==='routes/web.php'||$p==='routes/console.php')return 'E';
    if(str_ends_with($p,'.blade.php'))return 'F';
    if(str_starts_with($p,'app/Http/Controllers/')||str_starts_with($p,'bin/')||str_starts_with($p,'public/'))return 'C';
    return 'B';
};
$viewRank=static fn(string $p):int=>str_contains($p,'/layouts/')?0:(str_contains($p,'/partials/')?1:2);
$steps=[];
$isMig=static fn(string $p):bool=>str_starts_with($p,'database/migrations/');
$newOrdered=array_merge(array_values(array_filter($new,static fn($p)=>!$isMig($p))),array_values(array_filter($new,$isMig)));
foreach($newOrdered as $p)$steps[]=[$p,'new','A'];
$byGroup=[];$lateC=[];$mixed=[];$tight=[];
foreach(array_keys($mod) as $p){$grp=$pins[$p]??$groupOf($p);
    if($grp==='F'&&!isset($pins[$p])&&str_ends_with($p,'.blade.php')&&$removedN!==[]&&$callsAny($blob($base,$p),$removedN)&&!$callsAny($blob($src,$p),$addedN)){$grp='C';fwrite(STDERR,'NOTE '.$p.' calls a route the payload removes and none it adds: installed in group C, before the routes file'.PHP_EOL);}
    if($grp==='B'&&str_starts_with($p,'app/')&&str_ends_with($p,'.php')){$re='/\\bpublic\\s+(?:static\\s+)?function\\s+(\\w+)\\s*\\(/';preg_match_all($re,$blob($base,$p),$o1);preg_match_all($re,$blob($src,$p),$o2);
        // A service that loses a public method goes to the END of group C: the old controllers that still call it are replaced first (package.php proves that nothing new calls it). One that also gains methods stays in B and is reported (REVIEW) here and in the provenance.
        if(array_diff($o1[1],$o2[1])!==[]){if(array_diff($o2[1],$o1[1])!==[]){$mixed[$p]=[array_values(array_diff($o1[1],$o2[1])),array_values(array_diff($o2[1],$o1[1]))];continue;}else{$lateC[]=$p;continue;}}}
    $byGroup[$grp][]=$p;}
sort($lateC,SORT_STRING);
// Files of group B whose NEW bytes call an added method of a mixed service follow that service into group C (they cannot run before it exists, and the service must wait for the old callers of the methods it removes).
$followers=[];
foreach($mixed as $sv=>[$rem,$add]){foreach($byGroup['B']??[] as $k=>$q){if($q===$sv||!str_ends_with($q,'.php'))continue;$nb=$blob($src,$q);
    foreach($add as $m)if(str_contains($nb,'->'.$m.'(')||str_contains($nb,'::'.$m.'(')){$followers[$sv][]=$q;unset($byGroup['B'][$k]);break;}}
    $byGroup['B']=array_values($byGroup['B']??[]);sort($followers[$sv],SORT_STRING);}
// Order inside a group: callee before caller (`use App\...;` edges of PHP files, @include/@extends edges of views), otherwise alphabetical (views: layouts, partials, pages).
$deps=static function(string $p)use($blob,$src):array{$t=$blob($src,$p);$d=[];
    if(str_ends_with($p,'.blade.php')){if(preg_match_all("~@(?:include|extends|includeIf|includeWhen|includeFirst)\\(\\s*(?:[a-z0-9_.-]+\\s*,\\s*)?'([a-z0-9._-]+)'~",$t,$m))foreach($m[1] as $v)$d[]='resources/views/'.str_replace('.','/',$v).'.blade.php';}
    elseif(str_ends_with($p,'.php')&&preg_match_all('~^use (App\\\\[A-Za-z0-9_\\\\]+);~m',$t,$m))foreach($m[1] as $fq)$d[]='app/'.str_replace('\\','/',substr($fq,4)).'.php';
    return $d;};
foreach(['B','C','D','E','F'] as $g){$list=$byGroup[$g]??[];if($g==='F')usort($list,static fn($a,$b)=>[$viewRank($a),$a]<=>[$viewRank($b),$b]);else sort($list,SORT_STRING);
    $set=array_flip($list);$done=[];$order=[];$visit=static function(string $p)use(&$visit,&$done,&$order,$set,$deps):void{if(isset($done[$p]))return;$done[$p]=false;foreach($deps($p) as $q)if(isset($set[$q])&&!array_key_exists($q,$done))$visit($q);$done[$p]=true;$order[]=$p;};
    foreach($list as $p)$visit($p);if($g!=='C')foreach($order as $p)$steps[]=[$p,'modified',$g];
    if($g==='C'){$cl=$order;foreach($lateC as $p)$cl[]=$p;
        // A service that removes AND adds public methods sits inside group C: after every step that still calls a removed method, before every step that calls an added one (names are matched as ->m( and ::m( in the bytes at BASE and at the target).
        foreach($mixed as $sv=>[$rem,$add]){$cr=[];$cn=[];
            foreach(array_keys($mod) as $q){if($q===$sv||!str_ends_with($q,'.php')||str_ends_with($q,'.blade.php'))continue;$ob=$blob($base,$q);$nb=$blob($src,$q);
                foreach($rem as $m)if(str_contains($ob,'->'.$m.'(')||str_contains($ob,'::'.$m.'('))$cr[$q]=true;
                foreach($add as $m)if(str_contains($nb,'->'.$m.'(')||str_contains($nb,'::'.$m.'('))$cn[$q]=true;}
            $bad=[];
            foreach(array_merge(array_keys($cr),array_keys($cn)) as $q)if(!in_array($q,$cl,true)&&!in_array($q,$followers[$sv]??[],true)){$grp=$pins[$q]??$groupOf($q);if(isset($cr[$q])&&$grp>'C')$bad[]=$q;if(isset($cn[$q])&&$grp<'C')$bad[]=$q;}
            if($bad!==[]||isset($cr[$sv]))throw new RuntimeException('GENERATE_SERVICE_REMOVES_AND_ADDS_PUBLIC_METHODS_NO_SAFE_POSITION_'.$sv.'_'.implode(',',$bad).' (pin it or review)');
            // A file that both still calls a removed method (old bytes) and calls an added one (new bytes) cannot be ordered around the service: it is installed IMMEDIATELY after it (the window is one file write, reported in the provenance).
            $both=array_keys(array_intersect_key($cr,$cn));
            // order inside C: old callers of the removed methods, the rest, the service with its followers, the new callers of the added methods, then the removal-only services.
            $late=array_values(array_intersect($cl,$lateC));$core=array_values(array_diff($cl,$lateC));
            foreach($followers[$sv]??[] as $fw){$fc=preg_quote(basename($fw,'.php'),'/');foreach($core as $q){if($q!==$fw&&preg_match('/^use [A-Za-z0-9_\\\\]*\\\\'.$fc.';/m',$blob($src,$q)))$cn[$q]=true;}}
            $both=array_keys(array_intersect_key($cr,$cn));
            $first=array_values(array_filter($core,static fn($q)=>isset($cr[$q])&&!isset($cn[$q])));$last=array_values(array_filter($core,static fn($q)=>isset($cn[$q])&&!isset($cr[$q])));$mid=array_values(array_filter($core,static fn($q)=>!isset($cr[$q])&&!isset($cn[$q])));
            $cl=array_merge($first,$mid,[$sv],$followers[$sv]??[],array_values(array_intersect($core,$both)),$last,$late);$tight[$sv]=$both;
            fwrite(STDERR,'NOTE '.$sv.' is installed inside group C after its old callers of '.implode(',',$rem).' and before the new callers of '.implode(',',$add).($followers[$sv]??[]?'; followers moved from B: '.implode(',',$followers[$sv]):'').PHP_EOL);}
        foreach($cl as $p)$steps[]=[$p,'modified','C'];}}
if($steps===[])throw new RuntimeException('GENERATE_EMPTY_OVERLAY');
// A new view that calls a new route only works after the routes: new files sit in A and are inert until a modified view includes them, so nothing else is needed.
$digests=[];foreach($steps as [$p,$k]){$digests[$p]=[$k==='new'?null:$mod[$p],hash('sha256',$blob($src,$p))];if($k==='modified'&&$digests[$p][0]===$digests[$p][1])throw new RuntimeException('GENERATE_UNCHANGED_'.$p);}
$groups=array_values(array_unique(array_column($steps,2)));
$guards=[];foreach($groups as $g){if($g==='D'||$g==='E')$guards[$g]='classes';}
$cssChanged=isset($digests['public/assets/css/ald1n-admin.css']);$viewsChanged=in_array('F',$groups,true);
if($viewsChanged&&$cssChanged)$guards['F']='assets';
ksort($guards);
// Migrations: tables and columns the migration files add (heuristic over the target bytes; CI measures the real schema and compares).
$colTypes='string|char|text|mediumText|longText|integer|unsignedInteger|bigInteger|unsignedBigInteger|tinyInteger|unsignedTinyInteger|smallInteger|unsignedSmallInteger|mediumInteger|decimal|float|double|boolean|date|dateTime|timestamp|time|json|enum|binary|uuid|foreignId|year';
$migs=array_values(array_filter(array_column($steps,0),$isMig));$tables=[];$cols=[];
foreach($migs as $m){$b=$blob($src,$m);
    if(preg_match_all('~Schema::create\(\s*[\'"](\w+)[\'"]~',$b,$mm))foreach($mm[1] as $t)$tables[$t]=true;
    if(preg_match_all('~Schema::table\(\s*[\'"](\w+)[\'"]\s*,\s*(?:static\s+)?function[^{]*\{(.*?)\n\s*\}\);~s',$b,$mm,PREG_SET_ORDER))foreach($mm as $x){if(!str_contains($x[0],'function up')&&str_contains($x[2],'dropColumn'))continue;foreach(explode("\n",$x[2]) as $line){if(str_contains($line,'->change()')||str_contains($line,'dropColumn')||str_contains($line,'renameColumn'))continue;if(preg_match('~\$table->(?:'.$colTypes.')\(\s*[\'"](\w+)[\'"]~',$line,$c))$cols[$x[1]][$c[1]]=true;}}}
$cols=array_map(static fn($c)=>array_keys($c),$cols);ksort($cols);$tables=array_keys($tables);sort($tables);
$migNames=array_map(static fn($m)=>basename($m,'.php'),$migs);
// Routes: names added with a plain Route:: line (only when the target has no name-prefix group), and the count estimate.
$routeFiles=array_values(array_filter(array_column($steps,0),static fn($p)=>str_starts_with($p,'routes/')));$newWeb=[];$delta=0;$apiAdded=0;$groupedNames=false;
foreach($routeFiles as $rf){
    $bb=$digests[$rf][0]===null?'':$blob($base,$rf);$ab=$blob($src,$rf);if(preg_match('~->name\(\s*[\'"][^\'"]*\.[\'"]\s*\)\s*->group|Route::name\(~',$ab))$groupedNames=true;
    $re='~Route::(get|post|put|patch|delete|options|any)\(~';$na=preg_match_all($re,$ab);$nb=preg_match_all($re,$bb);$delta+=$na-$nb;if($rf==='routes/api.php')$apiAdded=$na-$nb;
    if($rf==='routes/web.php'){preg_match_all('~Route::(get|post|put|patch|delete)\([^\n]*->name\(\s*[\'"]([A-Za-z0-9._-]+)[\'"]\)~',$bb,$o,PREG_SET_ORDER);$old=[];foreach($o as $x)$old[$x[2]]=true;
        preg_match_all('~Route::(get|post|put|patch|delete)\([^\n]*->name\(\s*[\'"]([A-Za-z0-9._-]+)[\'"]\)~',$ab,$n2,PREG_SET_ORDER);foreach($n2 as $x)if(!isset($old[$x[2]]))$newWeb[$x[2]]=strtoupper($x[1]);}}
if($groupedNames)$newWeb=[];
$routesAfter=$routesAfterArg??$B_ROUTES+$delta;
// Source markers: one needle per step (max 40): a class declaration of a new PHP class, else an added line that is absent from the pre-state bytes.
$markers=[];$i=0;
foreach($steps as [$p,$k]){
    $after=$blob($src,$p);$needle=null;$before=$k==='modified'?(str_ends_with($p,'.php')?($mod[$p]!==null?$blob($base,$p):''):''):'';
    if($k==='new'&&preg_match('~^((?:final |abstract )?(?:class|trait|interface|enum) \w+)~m',$after,$m))$needle=$m[1];
    else foreach(explode("\n",$after) as $line){$t=trim($line);if(strlen($t)<14||strlen($t)>120||str_contains($before,$t)||substr_count($after,$t)!==1||str_starts_with($t,'//')||str_starts_with($t,'*')||str_starts_with($t,'#')||str_starts_with($t,'{{--'))continue;$needle=$t;break;}
    if($needle===null)continue;
    if(!str_ends_with($p,'.php')&&!str_ends_with($p,'.css'))continue;
    $markers['m'.sprintf('%02d',++$i)]=[$p,$needle];
}
if(count($markers)>40){$keep=[];$n=count($markers);$j=0;$step=$n/40;foreach($markers as $k=>$v){if(floor($j/$step)!==floor(($j-1)/$step)||$j===0)$keep[$k]=$v;$j++;}$markers=array_slice($keep,0,40,true);}
if($markers===[])throw new RuntimeException('GENERATE_NO_SOURCE_MARKER');
$viewChecks=array_values(array_filter(array_column($steps,0),static fn($p)=>str_ends_with($p,'.blade.php')));
$newBin=array_values(array_filter(array_column($steps,0),static fn($p)=>str_starts_with($p,'bin/')));
$newClasses=[];foreach($steps as [$p,$k])if($k==='new'&&str_starts_with($p,'app/')&&str_ends_with($p,'.php'))$newClasses[]='App\\'.str_replace('/','\\',substr($p,4,-4));
// Classes the web-SAPI proof loads before the guard groups (pinned LoadProbe: at most 8): new classes first, then modified models/services.
$proofMod=array_values(array_filter(array_column(array_filter($steps,static fn($s)=>$s[1]==='modified'),0),static fn($p)=>str_starts_with($p,'app/')&&str_ends_with($p,'.php')&&!str_ends_with($p,'.blade.php')));
usort($proofMod,static fn($x,$y)=>[preg_match('~^app/(Models|Services|Support)/~',$x)?0:1,$x]<=>[preg_match('~^app/(Models|Services|Support)/~',$y)?0:1,$y]);
$proof=array_slice(array_merge($newClasses,array_map(static fn($p)=>'App\\'.str_replace('/','\\',substr($p,4,-4)),$proofMod)),0,8);
// Classes the provider registers: resolved by the boot proof (bind/singleton/scoped lines of the payload provider).
$bindings=[];if(isset($digests['app/Providers/AppServiceProvider.php'])&&preg_match_all('~->(?:bind|singleton|scoped)\(\s*\\\\?([A-Za-z0-9_\\\\]+)::class~',$blob($src,'app/Providers/AppServiceProvider.php'),$mb))$bindings=array_values(array_unique(array_map(static fn($c)=>str_starts_with($c,'App\\')?$c:'',$mb[1])));
$bindings=array_values(array_filter($bindings));
$prior=[];$tryBlob=static fn(string $c,string $p):?string=>$git(['show',$c.':'.CMS.$p],false);
foreach($P::SOURCE_MARKERS as $k=>[$p,$needle]){$a=$tryBlob($base,$p);$b=$tryBlob($src,$p);if($a!==null&&$b!==null&&str_contains($a,$needle)&&str_contains($b,$needle))$prior[$k]=[$p,$needle];}
if($prior===[])throw new RuntimeException('GENERATE_NO_PRIOR_MARKER');
$newFiles=count($new);$treeAfter=$B_TREE+$newFiles;
$reportSha=null;$stateId=null;
if($reportFile!==null){$rb=(string)file_get_contents($reportFile);if(str_contains($rb,"\r"))throw new RuntimeException('GENERATE_REPORT_HAS_CR');$reportSha=hash('sha256',$rb);if(!preg_match('~\.ald1n-b\d+-state/([a-f0-9]{32})/~',$rb,$sm))throw new RuntimeException('GENERATE_REPORT_NO_STATE_ID');$stateId=$sm[1];
    foreach(['EXIT=0','"verifiedFiles":'.$B_TREE,'"routeCount":'.$B_ROUTES] as $needle)if(!str_contains($rb,$needle))throw new RuntimeException('GENERATE_REPORT_LACKS_'.$needle);
    foreach(['~^B\d+_FINAL=PASS_[A-Z0-9_]+$~m','~^B\d+_PHASE="INSTALLED_PASS"$~m'] as $rx)if(preg_match($rx,$rb)!==1)throw new RuntimeException('GENERATE_REPORT_NOT_A_TERMINAL_PASS');}
$v=static fn(mixed $x):string=>var_export($x,true);
$out="<?php\ndeclare(strict_types=1);\n// GENERATED by tools/storefront/b1158/generate.php for target ".$src.". Do not edit: run the generator again.\n// @author Ald1n <https://ald1n.com>\nfinal class B1158Payload\n{\n";
$out.="    // ===== B1158_PAYLOAD (begin) =====\n";
$out.="    public const BASE_COMMIT=".$v($base).";\n    public const SOURCE_COMMIT=".$v($src).";\n";
$out.="    public const STEPS=[\n";foreach($steps as [$p,$k,$g])$out.="        [".$v($p).",".$v($k).",".$v($g).",'M'],\n";$out.="    ];\n";
$out.="    public const DIGESTS=[\n";foreach($digests as $p=>[$a,$b])$out.="        ".$v($p)."=>[".$v($a).",".$v($b)."],\n";$out.="    ];\n";
$out.="    public const ORPHANS=[\n";foreach($orph as $p=>$h)$out.="        ".$v($p)."=>".$v($h).",\n";$out.="    ];\n";
$out.="    // ===== B1158_PAYLOAD (end) =====\n";
$out.="    public const GUARDS=".$v($guards).";\n    public const NEW_MIGRATIONS=".$v($migNames).";\n    public const NEW_TABLES=".$v($tables).";\n    public const NEW_COLUMNS=".$v($cols).";\n";
$out.="    public const RAN=".$B_RAN.";\n    public const RAN_AFTER=".($B_RAN+count($migNames)).";\n    public const ORPHANS_ALL=".$v(array_merge($orphansBefore,$orph)).";\n    public const PRIOR_MARKERS=".$v($prior).";\n";
$out.="    public const ROUTES=".$B_ROUTES.";\n    public const ROUTES_AFTER=".$routesAfter.";\n    public const ROUTES_AFTER_IS_ESTIMATE=".($routesAfterArg===null?'true':'false').";\n    public const NEW_WEB_ROUTES=".$v($newWeb).";\n    public const NEW_API_ROUTES=".$apiAdded.";\n";
$out.="    public const NEW_BIN=".$v($newBin).";\n    public const VIEW_CHECKS=".$v($viewChecks).";\n    public const SOURCE_MARKERS=".$v($markers).";\n    public const PROOF_CLASSES=".$v($proof).";\n    public const PROVIDER_BINDINGS=".$v($bindings).";\n";
$out.="    public const ADMIN_CSS_PATHS=".$v($cssChanged?['/assets/css/ald1n-admin.css'=>'public/assets/css/ald1n-admin.css']:[]).";\n";
$out.="    public const TIGHT_ORDER=".$v($tight).";\n";
$out.="    public const EXCLUDED_FILES=".$v($excludedFiles).";\n";
$out.="    public const FILES=".count($steps).";\n    public const NEW_FILES=".$newFiles.";\n    public const MODIFIED_FILES=".(count($steps)-$newFiles).";\n    public const TREE_FILES_AFTER=".$treeAfter.";\n    public const TREE_FILES_BEFORE=".$B_TREE.";\n";
$out.="    public const CHAIN_REPORT_SHA256=".$v($reportSha??str_repeat('0',64)).";\n    public const CHAIN_STATE_ID=".$v($stateId??str_repeat('0',32)).";\n    public const CHAIN_PINS_FILLED=".($reportSha===null?'false':'true').";\n}\n";
$dir=__DIR__;
if($check){if(!is_file($dir.'/Payload.php')||file_get_contents($dir.'/Payload.php')!==$out)throw new RuntimeException('GENERATE_CHECK_PAYLOAD_PHP_IS_NOT_THE_GENERATED_ONE');echo "OK payload matches ".$src."\n";exit(0);}
file_put_contents($dir.'/Payload.php',$out);
$tpl=$dir.'/README-SR.tpl.md';
if(is_file($tpl)){$groupsCount=array_count_values(array_column($steps,2));ksort($groupsCount);$gl=implode(' ',array_map(static fn($g,$n)=>$g.$n,array_keys($groupsCount),$groupsCount));
    $map=['{{SRC8}}'=>substr($src,0,8),'{{SRC}}'=>$src,'{{FILES}}'=>(string)count($steps),'{{NEW}}'=>(string)$newFiles,'{{MOD}}'=>(string)(count($steps)-$newFiles),'{{ORPHANS}}'=>(string)count($orph),'{{ORPHAN_LIST}}'=>$orph===[]?'nema':implode(', ',array_map(static fn($p)=>'`'.$p.'`',array_keys($orph))),
        '{{TREE}}'=>(string)$treeAfter,'{{ROUTES_AFTER}}'=>(string)$routesAfter,'{{ROUTE_DELTA}}'=>(string)($routesAfter-$B_ROUTES),'{{MIGRATIONS}}'=>$migNames===[]?'nema':implode(', ',array_map(static fn($m)=>'`'.$m.'`',$migNames)),'{{RAN_AFTER}}'=>(string)($B_RAN+count($migNames)),'{{VIEWS}}'=>(string)count($viewChecks),'{{GROUPS}}'=>$gl];
    file_put_contents($dir.'/README-SR.md',strtr((string)file_get_contents($tpl),$map));}
echo json_encode(['target'=>$src,'files'=>count($steps),'new'=>$newFiles,'modified'=>count($steps)-$newFiles,'orphans'=>count($orph),'groups'=>array_count_values(array_column($steps,2)),'migrations'=>$migNames,'tables'=>$tables,'columns'=>$cols,'routesAfter'=>$routesAfter,'routesEstimate'=>$routesAfterArg===null,'newWebRoutes'=>count($newWeb),'markers'=>count($markers),'treeAfter'=>$treeAfter,'guards'=>$guards,'proofClasses'=>count($proof),'bindings'=>$bindings],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
