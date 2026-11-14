<?php
declare(strict_types=1);
/**
 * CI-only child runner: one real AdminShellInstallCoordinator run against the fixture host (real artisan, real MariaDB, real
 * Laravel behind `php -S` with OPcache, the real B1154 payload from the built operator package, on top of the real B1146,
 * B1147, B1148, B1149, B1150, B1151, B1152 and B1153 installs). Explicit double: AdminShellFixtureWeb sends the same requests over plain HTTP to
 * loopback instead of HTTPS and plays the Cloudflare edge (script injection, e-mail obfuscation outside the email_off pair).
 * Fault points run named actions from the config (kill = SIGKILL of this process, stepSmoke = record anonymous statuses with a
 * timestamp, the logo, the guest admin pages, and the media-route link of the catalog page, mailChange = switch the default mailer
 * in .env, removeAsset = delete the second new stylesheet, clearProof = forget the web-SAPI asset proof of this run, cssWrongType and
 * cssTamperBody = the web double answers the two stylesheets wrongly).
 * With the config key adminWindow every GROUP_DONE_* stepSmoke also records what an administrator would meet at that point (CI only, the shipped tool never logs in):
 * a CLI render of the icon component (always) and, when the fixture proved it feasible, the real logged-in dashboard / (a real POST /login of a throwaway fixture user,
 * its password comes from the config file and is never printed): shell markers, icon classes, the two stylesheet links with their ?v= and the bytes of those exact URLs, and at the end the logout.
 * Usage: php fixture-run.php <config.json>
 * @author Ald1n <https://ald1n.com>
 */
$cfg=json_decode((string)file_get_contents($argv[1]),true,16,JSON_THROW_ON_ERROR);
require $cfg['b1134'].'/InstallFileOps.php';foreach(['LeanGates','FreshnessProbe','LeanWeb'] as $c)require $cfg['b1144'].'/'.$c.'.php';foreach(['EnvFlags','MigrateUnclampCoordinator'] as $c)require $cfg['b1145'].'/'.$c.'.php';require $cfg['b1146'].'/OverlayProbe.php';
foreach(['CatalogWeb','CatalogOverlayCoordinator','FixtureWeb'] as $c)require $cfg['b1147'].'/'.$c.'.php';
foreach(['GlassWeb','GlassOverlayCoordinator'] as $c)require $cfg['b1148'].'/'.$c.'.php';
// The B1149, B1150, B1151, B1152 and B1153 coordinator files (classes StorefrontInstallCoordinator, MediaInstallCoordinator, LegalAccountInstallCoordinator, OriginPinInstallCoordinator and LogoCatalogInstallCoordinator):
// AdminShellInstallCoordinator reads their STEPS and constants; MediaWeb is the pinned B1150 client, LogoCatalogWeb (in the B1153 coordinator file) is its HTTP client, LogoCatalogFiles its temp-file scope and LogoCatalogLoadProbe
// (the pinned B1153 load probe) the web app.url probe of its preflight. B1154 ships no LoadProbe of its own and the B1153 FixtureWeb is NOT loaded: AdminShellFixtureWeb (below) builds its request targets with LogoCatalogWeb::target.
require $cfg['b1149'].'/StorefrontInstallCoordinator.php';
foreach(['MediaWeb','MediaInstallCoordinator'] as $c)require $cfg['b1150'].'/'.$c.'.php';
require $cfg['b1151'].'/LegalAccountInstallCoordinator.php';require $cfg['b1152'].'/OriginPinInstallCoordinator.php';
require $cfg['b1153'].'/LoadProbe.php';require $cfg['b1153'].'/LogoCatalogInstallCoordinator.php';
foreach(['AdminShellInstallCoordinator','FixtureWeb'] as $c)require $cfg['tools'].'/'.$c.'.php';

date_default_timezone_set('UTC');
$package=json_decode((string)file_get_contents($cfg['incoming'].'/b1154-package.json'),true,64,JSON_THROW_ON_ERROR);
$o=(new ReflectionClass(AdminShellInstallCoordinator::class))->newInstanceWithoutConstructor();
$set=static function(string $name,mixed $value)use($o):void{(new ReflectionProperty($o,$name))->setValue($o,$value);};
foreach(['root','home','base','pointer','stage','b1146Base','b1147Base','b1148Base','b1149Base','b1150Base','b1151Base','b1152Base','b1153Base','appStateScript','routesScript','featureScript','php','allowedSapis','censusSeconds','minFreeBytes','scheduleGate','fixedNow','incoming','mode','b1153ReportSHA256','b1153StateId','expectedRoutes'] as $key)$set($key,$cfg[$key]);
// Explicit doubles for CI time only, scaled down but run through the real logic: request pacing, whole-batch 429 pause and
// per-batch 429 budget (production: 0.7 s, 65 s, 120 s; the fixture answers Retry-After: 3, so the 7 s budget allows two waits).
$set('ignoreSchedule',false);$set('paceMicros',150000);$set('rateLimitPauseSeconds',3);$set('rateLimitCapSeconds',7);(new ReflectionMethod($o,'load'))->invoke($o,$package,$cfg['incoming']);foreach((array)($cfg['overrides']??[]) as $key=>$value)if($key!=='probeRoute')$set($key,$value);
$edgeWeb=new AdminShellFixtureWeb($cfg['port'],$cfg['secrets'],$cfg['switches'],$cfg['root'].'/apps/cms/current/public/.htaccess',(string)($cfg['overrides']['probeRoute']??'FIXTURE_LOOPBACK_HTTP_CLI_SERVER'));$set('web',$edgeWeb);
$shopOff=static function()use($cfg):void{$active=$cfg['root'].'/apps/cms/current';(new PDO($cfg['db'][0],$cfg['db'][1],$cfg['db'][2],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]))->exec("DELETE FROM settings WHERE setting_key='public_storefront_open'");shell_exec('cd '.escapeshellarg($active).' && '.escapeshellarg(PHP_BINARY).' -d opcache.enable=0 artisan cache:clear --no-interaction > /dev/null 2>&1');};
$actions=$cfg['actions']??[];$active=$cfg['root'].'/apps/cms/current';
$busy=static function()use($active,$cfg):void{$pid=trim((string)shell_exec('cd '.escapeshellarg($active).' && { setsid bash -c "sleep 40; true" artisan > /dev/null 2>&1 < /dev/null & echo $!; }'));file_put_contents($cfg['busyPidFile'],$pid);};
$corrupt=static function()use($cfg):void{$p=json_decode((string)file_get_contents($cfg['pointer']),true);foreach(glob($p['private'].'/backup/*.orig')?:[] as $f)file_put_contents($f,"\n",FILE_APPEND);};
// The effective mail transport is switched (the default mailer array -> log) in the .env of the retained tree.
$mailChange=static function()use($cfg):void{$env=$cfg['home'].'/.ald1n-b1134-retained/'.str_repeat('d',32).'/retained/.env';$t=(string)file_get_contents($env);file_put_contents($env,str_replace('MAIL_MAILER=array','MAIL_MAILER=log',$t));};
// Negative controls of the order guard: the second new stylesheet vanishes right before the first file of group C, or the asset proof of this run is forgotten.
$removeAsset=static function()use($active):void{$f=$active.'/'.AdminShellInstallCoordinator::ADMIN_CSS_PATHS['/assets/css/ald1n-admin.css'];if(!is_file($f)||!unlink($f))throw new RuntimeException('FIXTURE_REMOVE_ASSET');};
// APP_URL of the retained .env, as the web worker AND the CLI read it: a different http origin (a web-side mismatch after the CLI preflight), a wrong https origin
// (the pin would then point every redirect at it), and the restore of the exact fixture value (the rollback verification needs the real APP_URL again).
$setAppUrl=static function(string $value)use($cfg):void{$env=$cfg['home'].'/.ald1n-b1134-retained/'.str_repeat('d',32).'/retained/.env';$t=(string)file_get_contents($env);$n=preg_replace('/^APP_URL=.*$/m','APP_URL='.$value,$t,-1,$c);if($c!==1||!is_string($n))throw new RuntimeException('FIXTURE_APP_URL_LINE');file_put_contents($env,$n);};
$clearProof=static function()use($o):void{$p=new ReflectionProperty($o,'state');$st=$p->getValue($o);unset($st['webAssetProof']);$p->setValue($o,$st);};
// What an administrator meets at a group point (CI only). The CLI icon render runs at every point (the icon component of the tree as it is right now: ui-icon before group B, the shell class only with the shell prop and only after group B).
// The logged-in dashboard / is fetched when the fixture proved it feasible (adminWindow.mode AUTHENTICATED_HTTP): after group A and B the OLD shell (data-site-header, ui-icon icons), after group C the NEW shell (a1-header__bar, a1-icon a1-icon--ph, both stylesheets linked
// with ?v=<file mtime>, the exact linked URLs serving the payload bytes, the logout POST working). At group C the page is polled up to 20 s until the new layout shows (the web workers revalidate within revalidate_freq seconds): every answer must be 200.
$jar=[];
$adminWindow=static function(string $letter)use($cfg,&$jar):array{
    $w=$cfg['adminWindow'];$out=['group'=>$letter];$act=$cfg['root'].'/apps/cms/current';
    $cli=json_decode((string)shell_exec(escapeshellarg(PHP_BINARY).' -d opcache.enable=0 -d opcache.enable_cli=0 '.escapeshellarg($w['cliScript']).' '.escapeshellarg($act).' 2>/dev/null'),true);
    $out['cli']=is_array($cli)?$cli:['error'=>'CLI_ICON_RENDER_FAILED'];
    if(($w['mode']??'')!=='AUTHENTICATED_HTTP')return $out;
    $page=static function()use($cfg,&$jar,$w):array{
        $r=AdminShellFixtureWeb::jarGet($cfg['port'],'/',$jar);
        if($r['status']===302){$l=AdminShellFixtureWeb::adminLogin($cfg['port'],$w['login'],$w['password'],$jar);if($l['ok'])$r=AdminShellFixtureWeb::jarGet($cfg['port'],'/',$jar);}
        return $r;
    };
    $t0=microtime(true);$polls=[];
    do{$r=$page();$polls[]=$r['status'];$new=str_contains($r['body'],'a1-header__bar');if($letter!=='C'||$new||microtime(true)-$t0>20)break;usleep(1000000);}while(true);
    $b=$r['body'];
    $out['http']=['status'=>$r['status'],'polls'=>$polls,'secondsUntilNewShell'=>($letter==='C'&&$new)?round(microtime(true)-$t0,1):null,'oldShell'=>str_contains($b,'data-site-header'),'newShell'=>$new,'uiIcon'=>str_contains($b,'ui-icon'),'shellIcon'=>str_contains($b,'a1-icon a1-icon--ph')];
    $links=[];
    foreach(AdminShellInstallCoordinator::ADMIN_CSS_PATHS as $url=>$rel){
        $name=str_contains($url,'tokens')?'tokens':'admin';
        if(preg_match('~<link\b[^>]*href="(?:https?://[^/"]+)?(/assets/css/ald1n-'.$name.'\.css)\?v=([^"&]+)"~',$b,$m)!==1){$links[$url]=null;continue;}
        $exact=AdminShellFixtureWeb::get($cfg['port'],$m[1].'?v='.$m[2]);$disk=$act.'/'.$rel;
        $links[$url]=['pathIsTheUrl'=>$m[1]===$url,'versionIsTheFileMtime'=>is_file($disk)&&$m[2]===(string)filemtime($disk),'status'=>$exact['status'],'contentType'=>strtolower(trim(explode(';',(string)$exact['contentType'])[0])),'sha256EqualsThePayload'=>$exact['status']===200&&hash('sha256',$exact['body'])===AdminShellInstallCoordinator::DIGESTS[$rel][1]];
    }
    $out['http']['links']=$links;
    if($letter==='C'){
        $tok=AdminShellFixtureWeb::csrfToken($b);$lo=$tok===null?null:AdminShellFixtureWeb::jarGet($cfg['port'],'/logout',$jar,http_build_query(['_token'=>$tok],'','&',PHP_QUERY_RFC3986));$after=AdminShellFixtureWeb::jarGet($cfg['port'],'/',$jar);
        $out['http']['logout']=['tokenFound'=>$tok!==null,'status'=>$lo['status']??null,'guestAfter'=>$after['status']];
    }
    return $out;
};
// Far below the browse throttle (120/min): at most one throttled GET per step point, the full set at group points. Records the
// anonymous statuses, a timestamp (taken first, right after the tree change the fault point follows), the logo URL taken from the catalog page, the guest admin
// pages and the guest dashboard / (302 at every point), and the media-route link of the catalog page when it carries one, and one CI-only POST /tema (303 with the cookie at the group
// points: a 419 (CSRF), 429 (no limiter) or 5xx at any point is a wrong order). /up and /login are asked at EVERY point, also right after the rename of
// bootstrap/app.php: a 500 there is the failure this rollout is ordered to avoid.
$stepSmoke=static function(string $point)use($cfg,$edgeWeb,$adminWindow):void{
    $row=['point'=>$point,'t'=>microtime(true)];$group=str_starts_with($point,'GROUP_DONE_')||$point==='AFTER_INVALIDATE';$n=str_starts_with($point,'AFTER_STEP_')?(int)substr($point,11):0;
    foreach(['/up','/login'] as $p)$row['anon'][$p]=AdminShellFixtureWeb::get($cfg['port'],$p)['status'];
    // The browse limiter allows 120 per minute: a catalog GET at every second step, the full set at group points (the last step of a group is always followed by its group point).
    $cat=null;if($group||$n%2===0){$cat=AdminShellFixtureWeb::get($cfg['port'],'/katalog');$row['anon']['/katalog']=$cat['status'];}
    if($group){$row['anon']['/pravne-informacije']=AdminShellFixtureWeb::get($cfg['port'],'/pravne-informacije')['status'];$row['anon']['/katalog/b1154-ci-1']=AdminShellFixtureWeb::get($cfg['port'],'/katalog/b1154-ci-1')['status'];}
    $row['logoChecked']=$cat!==null;
    $logo=$cat===null?null:MediaWeb::siteAssetUrl($cat['body']);$row['logoLinked']=$logo!==null;
    $row['logoStatus']=$logo===null?null:AdminShellFixtureWeb::get($cfg['port'],$logo['path'].($logo['query']===[]?'':'?v='.$logo['query']['v']))['status'];
    $media=$cat===null?null:MediaWeb::mediaUrl($cat['body']);$row['mediaLinked']=$media!==null;
    $row['mediaStatus']=$media===null?null:AdminShellFixtureWeb::get($cfg['port'],$media['path'].'?'.http_build_query($media['query'],'','&',PHP_QUERY_RFC3986))['status'];
    $row['dashGuest']=AdminShellFixtureWeb::get($cfg['port'],'/')['status'];
    $row['smtpGuest']=AdminShellFixtureWeb::get($cfg['port'],'/admin/settings/smtp')['status'];
    $row['merchantGuest']=AdminShellFixtureWeb::get($cfg['port'],AdminShellInstallCoordinator::MERCHANT_PATH)['status'];
    $row['documentsGuest']=AdminShellFixtureWeb::get($cfg['port'],AdminShellInstallCoordinator::DOCUMENTS_PATH)['status'];
    // The B1153 catalog layout queries (200 at every group point, live in both states) and whether the page carries the per-page form (it does at every point).
    if($group){$pl=AdminShellFixtureWeb::get($cfg['port'],'/katalog?per_page=20&view=list');$row['perPageList']=$pl['status'];$row['perPageListLayout']=str_contains($pl['body'],AdminShellInstallCoordinator::LIST_MARKER);$row['perPage999']=AdminShellFixtureWeb::get($cfg['port'],'/katalog?per_page=999')['status'];$row['perPageControl']=$cat!==null&&str_contains($cat['body'],AdminShellInstallCoordinator::PER_PAGE_MARKER);}
    // The theme limiter allows 20 POSTs per minute: one CI-only POST at the group points only.
    if($group){$t=AdminShellFixtureWeb::get($cfg['port'],'/tema',['Content-Type: application/x-www-form-urlencoded'],'theme=dark&back=%2Fkatalog');$row['themePost']=$t['status'];$row['themeCookie']=isset($t['cookies']['shop_theme']);}
    if(isset($cfg['adminWindow'])&&str_starts_with($point,'GROUP_DONE_'))$row['adminWindow']=$adminWindow(substr($point,11));
    file_put_contents($cfg['stepLog'],json_encode($row,JSON_UNESCAPED_SLASHES)."\n",FILE_APPEND);
};
$sw=$cfg['switches'];
$set('fault',static function(string $point)use($actions,$cfg,$stepSmoke,$busy,$corrupt,$shopOff,$mailChange,$removeAsset,$clearProof,$setAppUrl,$sw):void{
    foreach((array)($actions[$point]??[]) as $action){
        file_put_contents($cfg['faultLog'],$point.' '.$action."\n",FILE_APPEND);
        match($action){
            'kill'=>posix_kill(getmypid(),9),
            'stepSmoke'=>$stepSmoke($point),
            'logError'=>file_put_contents($cfg['logDir'].'/laravel-'.date('Y-m-d').'.log','['.date('Y-m-d H:i:s').'] production.ERROR: injected fixture failure during acceptance'."\n",FILE_APPEND),
            'fail'=>throw new RuntimeException('FIXTURE_INJECTED_FAILURE'),
            'busy'=>$busy(),
            'corruptBackup'=>$corrupt(),
            'mailChange'=>$mailChange(),
            'removeAsset'=>$removeAsset(),
            'clearProof'=>$clearProof(),
            'appUrlHttp'=>$setAppUrl('http://cms.ald1n.com'),
            'appUrlWrong'=>$setAppUrl('https://wrong-origin.invalid'),
            'appUrlRestore'=>$setAppUrl('https://cms.ald1n.com'),
            'dropCsp','dropJsonLd','dropVaryEdge','dropVaryAll','noHtaccess','rogueScript','edgeKeepsEmailOff','cssWrongType','cssTamperBody'=>touch($sw[$action]),
            'rateLimit2'=>file_put_contents($sw['rate429'],'2'),
            'rateLimitAlways'=>file_put_contents($sw['rate429'],'ALWAYS'),
            'shopOff'=>$shopOff(),
            'clearDrops'=>array_map(static fn(string $f):bool=>!is_file($f)||unlink($f),[$sw['dropCsp'],$sw['dropJsonLd'],$sw['dropVaryEdge'],$sw['dropVaryAll'],$sw['rogueScript'],$sw['edgeKeepsEmailOff'],$sw['cssWrongType'],$sw['cssTamperBody']]),
            default=>throw new RuntimeException('FIXTURE_UNKNOWN_ACTION'),
        };
    }
});
$rc=$o->run();echo 'B1154_FINAL='.($rc===0?$o->final:(str_starts_with($o->final,'HOLD')?$o->final:'HOLD')).PHP_EOL;exit($rc);
