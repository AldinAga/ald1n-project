<?php
declare(strict_types=1);
/**
 * CI-only child runner: one real B1157InstallCoordinator run against the fixture host (real artisan, real MariaDB, real
 * Laravel behind `php -S` with OPcache, the real B1157 payload from the built operator package, on top of the real B1146,
 * B1147, B1148, B1149, B1150, B1151, B1152, B1153 and B1154 installs). Explicit double: B1157FixtureWeb sends the same requests over plain HTTP to
 * loopback instead of HTTPS and plays the Cloudflare edge (script injection, e-mail obfuscation outside the email_off pair); it also builds the one special request of
 * B1157Web (GET /katalog?stock=x with the header Referer: https://evil.example/x) against the real application.
 * Fault points run named actions from the config (kill = SIGKILL of this process, stepSmoke = record the site, the guest pages and the AUTHENTICATED dashboard at that point, mailChange = switch the default mailer
 * in .env, rm:<new file> = delete one of the new files, chmodDeny:<new file> and chmodAllow:<new file> = make a new class file unreadable and readable again, clearAssetProof and clearClassProof:<D|F> = forget the
 * web-SAPI proof of this run, cssWrongType and cssTamperBody = the web double answers the two changed stylesheets wrongly, upFails = the web double answers every request 500).
 * With the config key adminWindow every point also records what an administrator would meet there (CI only, the shipped tool never logs in): the real logged-in dashboard / of a throwaway SuperAdministrator
 * (a real POST /login, its password comes from the config file and is never printed) at EVERY point, and at every GROUP_DONE_* point the markup of the dashboard (old, then the new one after group E), the navigation dropdown,
 * the registration page, the sub-agent report and its CSV (404 before the routes of group F, 200 after), the management report with its new link (group G), and at the last group the logout.
 * The hard login gate of the fixture is the environment variable AUTHENTICATED_HTTP=1, set by the parent only after a real login, an authenticated GET and a logout were proven over real HTTP; without it this runner refuses to start.
 * Usage: php fixture-run.php <config.json>
 * @author Ald1n <https://ald1n.com>
 */
$cfg=json_decode((string)file_get_contents($argv[1]),true,16,JSON_THROW_ON_ERROR);
if(isset($cfg['adminWindow'])&&getenv('AUTHENTICATED_HTTP')!=='1')throw new RuntimeException('B1157_FIXTURE_AUTHENTICATED_HTTP_GATE_NOT_PASSED');
require $cfg['b1134'].'/InstallFileOps.php';foreach(['LeanGates','FreshnessProbe','LeanWeb'] as $c)require $cfg['b1144'].'/'.$c.'.php';foreach(['EnvFlags','MigrateUnclampCoordinator'] as $c)require $cfg['b1145'].'/'.$c.'.php';require $cfg['b1146'].'/OverlayProbe.php';
foreach(['CatalogWeb','CatalogOverlayCoordinator','FixtureWeb'] as $c)require $cfg['b1147'].'/'.$c.'.php';
foreach(['GlassWeb','GlassOverlayCoordinator'] as $c)require $cfg['b1148'].'/'.$c.'.php';
// The B1149 to B1154 coordinator files (classes StorefrontInstallCoordinator, MediaInstallCoordinator, LegalAccountInstallCoordinator, OriginPinInstallCoordinator, LogoCatalogInstallCoordinator and AdminShellInstallCoordinator):
// B1157InstallCoordinator reads their STEPS and constants; MediaWeb is the pinned B1150 client, LogoCatalogWeb (in the B1153 coordinator file) is its HTTP client, LogoCatalogFiles its temp-file scope and LogoCatalogLoadProbe
// (the pinned B1153 load probe) the web probe of its preflight and of its class proofs. B1157 ships no LoadProbe of its own and the B1154 FixtureWeb is NOT loaded: B1157FixtureWeb (below) builds its request targets with LogoCatalogWeb::target.
require $cfg['b1149'].'/StorefrontInstallCoordinator.php';
foreach(['MediaWeb','MediaInstallCoordinator'] as $c)require $cfg['b1150'].'/'.$c.'.php';
require $cfg['b1151'].'/LegalAccountInstallCoordinator.php';require $cfg['b1152'].'/OriginPinInstallCoordinator.php';
require $cfg['b1153'].'/LoadProbe.php';require $cfg['b1153'].'/LogoCatalogInstallCoordinator.php';require $cfg['b1154'].'/AdminShellInstallCoordinator.php';require $cfg['b1155'].'/DashboardReportsInstallCoordinator.php';require $cfg['b1156'].'/CommissionsInstallCoordinator.php';require $cfg['tools'].'/Payload.php';
foreach(['B1157InstallCoordinator','FixtureWeb'] as $c)require $cfg['tools'].'/'.$c.'.php';

date_default_timezone_set('UTC');
$package=json_decode((string)file_get_contents($cfg['incoming'].'/b1157-package.json'),true,64,JSON_THROW_ON_ERROR);
$o=(new ReflectionClass(B1157InstallCoordinator::class))->newInstanceWithoutConstructor();
$set=static function(string $name,mixed $value)use($o):void{(new ReflectionProperty($o,$name))->setValue($o,$value);};
foreach(['root','home','base','pointer','stage','b1146Base','b1147Base','b1148Base','b1149Base','b1150Base','b1151Base','b1152Base','b1153Base','b1154Base','b1155Base','b1156Base','appStateScript','routesScript','featureScript','php','allowedSapis','censusSeconds','minFreeBytes','scheduleGate','fixedNow','incoming','mode','b1156ReportSHA256','b1156StateId','expectedRoutes','expectedRoutesAfter'] as $key)$set($key,$cfg[$key]);
// Explicit doubles for CI time only, scaled down but run through the real logic: request pacing, whole-batch 429 pause and
// per-batch 429 budget (production: 0.7 s, 65 s, 120 s; the fixture answers Retry-After: 3, so the 7 s budget allows two waits).
$set('ignoreSchedule',false);$set('backupTimeout',600);$set('verifyTimeout',300);$set('migrateTimeout',300);$set('paceMicros',150000);$set('rateLimitPauseSeconds',3);$set('rateLimitCapSeconds',7);(new ReflectionMethod($o,'load'))->invoke($o,$package,$cfg['incoming']);foreach((array)($cfg['overrides']??[]) as $key=>$value)if($key!=='probeRoute')$set($key,$value);
$edgeWeb=new B1157FixtureWeb($cfg['port'],$cfg['secrets'],$cfg['switches'],$cfg['root'].'/apps/cms/current/public/.htaccess',(string)($cfg['overrides']['probeRoute']??'FIXTURE_LOOPBACK_HTTP_CLI_SERVER'));$set('web',$edgeWeb);
$shopOff=static function()use($cfg):void{$active=$cfg['root'].'/apps/cms/current';(new PDO($cfg['db'][0],$cfg['db'][1],$cfg['db'][2],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]))->exec("DELETE FROM settings WHERE setting_key='public_storefront_open'");shell_exec('cd '.escapeshellarg($active).' && '.escapeshellarg(PHP_BINARY).' -d opcache.enable=0 artisan cache:clear --no-interaction > /dev/null 2>&1');};
$actions=$cfg['actions']??[];$active=$cfg['root'].'/apps/cms/current';
$busy=static function()use($active,$cfg):void{$pid=trim((string)shell_exec('cd '.escapeshellarg($active).' && { setsid bash -c "sleep 40; true" artisan > /dev/null 2>&1 < /dev/null & echo $!; }'));file_put_contents($cfg['busyPidFile'],$pid);};
$corrupt=static function()use($cfg):void{$p=json_decode((string)file_get_contents($cfg['pointer']),true);foreach(glob($p['private'].'/backup/*.orig')?:[] as $f)file_put_contents($f,"\n",FILE_APPEND);};
// The effective mail transport is switched (the default mailer array -> log) in the .env of the retained tree.
$mailChange=static function()use($cfg):void{$env=$cfg['home'].'/.ald1n-b1134-retained/'.str_repeat('d',32).'/retained/.env';$t=(string)file_get_contents($env);file_put_contents($env,str_replace('MAIL_MAILER=array','MAIL_MAILER=log',$t));};
// Negative controls of the order guards, limited to the NEW files of the payload (a rollback accepts an absent new file; a modified file is never touched): a new file vanishes right before the first file of a guard group,
// a new class file becomes unreadable for the web worker (the class proof of group D must refuse), and the web-SAPI proof of this run is forgotten.
$newPaths=[];foreach(B1157InstallCoordinator::STEPS as [$p,$kind])if($kind==='new')$newPaths[$p]=true;
$removeFile=static function(string $rel)use($active,$newPaths):void{if(!isset($newPaths[$rel])||!is_file($active.'/'.$rel)||!unlink($active.'/'.$rel))throw new RuntimeException('FIXTURE_REMOVE_FILE');};
$chmodFile=static function(string $rel,int $mode)use($active,$newPaths):void{if(!isset($newPaths[$rel])||!is_file($active.'/'.$rel)||!chmod($active.'/'.$rel,$mode))throw new RuntimeException('FIXTURE_CHMOD_FILE');clearstatcache();};
// APP_URL of the retained .env, as the web worker AND the CLI read it: a different http origin (a web-side mismatch after the CLI preflight), a wrong https origin
// (the pin would then point every redirect at it), and the restore of the exact fixture value (the rollback verification needs the real APP_URL again).
$setAppUrl=static function(string $value)use($cfg):void{$env=$cfg['home'].'/.ald1n-b1134-retained/'.str_repeat('d',32).'/retained/.env';$t=(string)file_get_contents($env);$n=preg_replace('/^APP_URL=.*$/m','APP_URL='.$value,$t,-1,$c);if($c!==1||!is_string($n))throw new RuntimeException('FIXTURE_APP_URL_LINE');file_put_contents($env,$n);};
$clearAssetProof=static function()use($o):void{$p=new ReflectionProperty($o,'state');$st=$p->getValue($o);unset($st['webAssetProof']);$p->setValue($o,$st);};
$clearClassProof=static function(string $group)use($o):void{$p=new ReflectionProperty($o,'state');$st=$p->getValue($o);unset($st['webClassProof'][$group]);$p->setValue($o,$st);};

// The throwaway SuperAdministrator and sub-agent sessions of this process (CI only). authGet()/subGet() log in again when the session is gone (a 302 to the login), so a window is never an artefact of an expired session.
$jar=[];$jarSub=[];$w=$cfg['adminWindow']??null;
$login=static function(string $who,array &$j)use($cfg,$w):array{
    if(!is_array($w)||($w['mode']??'')!=='AUTHENTICATED_HTTP')throw new RuntimeException('B1157_FIXTURE_AUTHENTICATED_HTTP_GATE_NOT_PASSED');
    return B1157FixtureWeb::adminLogin($cfg['port'],$w[$who]['login'],$w[$who]['password'],$j);
};
$authGet=static function(string $path,string $who='admin')use($cfg,&$jar,&$jarSub,$login):array{
    $j=&$jar;if($who==='sub')$j=&$jarSub;
    $r=B1157FixtureWeb::jarGet($cfg['port'],$path,$j);
    if($r['status']===302&&str_contains((string)$r['location'],'/login')){$l=$login($who,$j);if($l['ok'])$r=B1157FixtureWeb::jarGet($cfg['port'],$path,$j);}
    return $r;
};
$orderId=(int)($cfg['orderId']??0);
// The pages of the dashboard, the commissions, the orders, the after-sales, the module settings, the reports and the bell that a logged-in user meets: the SuperAdministrator and the sub-agent, at EVERY step of the install and of the rollback
// (the dashboard and the commission lists) and at every group point (the full set). A 5xx anywhere is a real finding (a window the order of the groups is meant to avoid) and fails the fixture.
$pagesAdmin=['/','/admin/commissions','/admin/orders','/admin/orders/'.$orderId,'/admin/after-sales','/admin/settings/modules','/admin/reports','/notifications/recent'];
$pagesSub=['/','/orders','/orders/'.$orderId,'/commissions'];
$adminWindow=static function(string $letter)use($cfg,$authGet,$pagesAdmin,$pagesSub):array{
    $out=['group'=>$letter,'admin'=>[],'sub'=>[]];
    foreach($pagesAdmin as $pg){$r=$authGet($pg,'admin');$out['admin'][$pg]=['status'=>$r['status'],'shell'=>str_contains($r['body'],'a1-header__bar'),'bell'=>str_contains($r['body'],'data-bell-url'),'json'=>str_starts_with(strtolower((string)$r['contentType']),'application/json')];}
    foreach($pagesSub as $pg){$r=$authGet($pg,'sub');$out['sub'][$pg]=['status'=>$r['status'],'shell'=>str_contains($r['body'],'a1-header__bar'),'bell'=>str_contains($r['body'],'data-bell-url')];}
    $g=B1157FixtureWeb::get($cfg['port'],'/registracija');$out['registration']=['status'=>$g['status']];
    return $out;
};
// Far below the browse throttle (120/min): at most ONE throttled GET per step point (/katalog at the even steps, /registracija at the odd ones), the full set at group points. Records the anonymous statuses, a timestamp, the logo URL taken from the catalog page,
// the guest admin pages, the guest dashboard / (302 at every point), the guest report URLs of B1155 and the guest bell URL (404 until the routes are in, 302 after), the media-route link, one CI-only POST /tema (303 at the group points)
// and the AUTHENTICATED dashboards and commission lists of both users at EVERY point. /up and /login are asked at EVERY point.
$stepSmoke=static function(string $point)use($cfg,$authGet,$adminWindow):void{
    $row=['point'=>$point,'t'=>microtime(true)];$group=str_starts_with($point,'GROUP_DONE_')||$point==='AFTER_INVALIDATE'||$point==='AFTER_MIGRATED'||$point==='AFTER_DB_BACKUP';$n=preg_match('/^AFTER_(?:STEP|RESTORE)_(\d+)$/D',$point,$m)===1?(int)$m[1]:0;
    foreach(['/up','/login'] as $p)$row['anon'][$p]=B1157FixtureWeb::get($cfg['port'],$p)['status'];
    $cat=null;if($group||($n>0&&$n%2===0)){$cat=B1157FixtureWeb::get($cfg['port'],'/katalog');$row['anon']['/katalog']=$cat['status'];}
    if($group||$n%2===1)$row['anon'][B1157InstallCoordinator::REGISTER_PATH]=B1157FixtureWeb::get($cfg['port'],B1157InstallCoordinator::REGISTER_PATH)['status'];
    if($group){$row['anon']['/pravne-informacije']=B1157FixtureWeb::get($cfg['port'],'/pravne-informacije')['status'];$row['anon']['/katalog/b1154-ci-1']=B1157FixtureWeb::get($cfg['port'],'/katalog/b1154-ci-1')['status'];}
    $row['logoChecked']=$cat!==null;
    $logo=$cat===null?null:MediaWeb::siteAssetUrl($cat['body']);$row['logoLinked']=$logo!==null;
    $row['logoStatus']=$logo===null?null:B1157FixtureWeb::get($cfg['port'],$logo['path'].($logo['query']===[]?'':'?v='.$logo['query']['v']))['status'];
    $media=$cat===null?null:MediaWeb::mediaUrl($cat['body']);$row['mediaLinked']=$media!==null;
    $row['mediaStatus']=$media===null?null:B1157FixtureWeb::get($cfg['port'],$media['path'].'?'.http_build_query($media['query'],'','&',PHP_QUERY_RFC3986))['status'];
    $row['dashGuest']=B1157FixtureWeb::get($cfg['port'],'/')['status'];
    $row['smtpGuest']=B1157FixtureWeb::get($cfg['port'],'/admin/settings/smtp')['status'];
    $row['merchantGuest']=B1157FixtureWeb::get($cfg['port'],B1157InstallCoordinator::MERCHANT_PATH)['status'];
    $row['documentsGuest']=B1157FixtureWeb::get($cfg['port'],B1157InstallCoordinator::DOCUMENTS_PATH)['status'];
    $row['reportGuest']=B1157FixtureWeb::get($cfg['port'],'/admin/reports/subagents')['status'];
    $row['reportCsvGuest']=B1157FixtureWeb::get($cfg['port'],'/admin/reports/subagents.csv')['status'];
    $row['bellGuest']=B1157FixtureWeb::get($cfg['port'],B1157InstallCoordinator::NOTIFICATIONS_RECENT_PATH)['status'];
    if($group){$pl=B1157FixtureWeb::get($cfg['port'],'/katalog?per_page=20&view=list');$row['perPageList']=$pl['status'];$row['perPageListLayout']=str_contains($pl['body'],B1157InstallCoordinator::LIST_MARKER);$row['perPage999']=B1157FixtureWeb::get($cfg['port'],'/katalog?per_page=999')['status'];$row['perPageControl']=$cat!==null&&str_contains($cat['body'],B1157InstallCoordinator::PER_PAGE_MARKER);}
    if($group){$t=B1157FixtureWeb::get($cfg['port'],'/tema',['Content-Type: application/x-www-form-urlencoded'],'theme=dark&back=%2Fkatalog');$row['themePost']=$t['status'];$row['themeCookie']=isset($t['cookies']['shop_theme']);}
    if(isset($cfg['adminWindow'])){
        $d=$authGet('/','admin');$row['dashAuth']=$d['status'];$row['dashAuthShell']=str_contains($d['body'],'a1-header__bar');
        $c=$authGet('/admin/commissions','admin');$row['commissionsAuth']=$c['status'];
        $s=$authGet('/','sub');$row['dashSub']=$s['status'];$sc=$authGet('/commissions','sub');$row['commissionsSub']=$sc['status'];
    }
    if(isset($cfg['adminWindow'])&&(str_starts_with($point,'GROUP_DONE_')||in_array($point,['AFTER_DB_BACKUP','AFTER_MIGRATED'],true)))$row['adminWindow']=$adminWindow($point);
    file_put_contents($cfg['stepLog'],json_encode($row,JSON_UNESCAPED_SLASHES)."\n",FILE_APPEND);
};
$sw=$cfg['switches'];
$set('fault',static function(string $point)use($actions,$cfg,$stepSmoke,$busy,$corrupt,$shopOff,$mailChange,$removeFile,$chmodFile,$clearAssetProof,$clearClassProof,$setAppUrl,$sw):void{
    foreach((array)($actions[$point]??[]) as $action){
        file_put_contents($cfg['faultLog'],$point.' '.$action."\n",FILE_APPEND);
        [$name,$arg]=array_pad(explode(':',(string)$action,2),2,'');
        match($name){
            'kill'=>posix_kill(getmypid(),9),
            'stepSmoke'=>$stepSmoke($point),
            'logError'=>file_put_contents($cfg['logDir'].'/laravel-'.date('Y-m-d').'.log','['.date('Y-m-d H:i:s').'] production.ERROR: injected fixture failure during acceptance'."\n",FILE_APPEND),
            'fail'=>throw new RuntimeException('FIXTURE_INJECTED_FAILURE'),
            'busy'=>$busy(),
            'corruptBackup'=>$corrupt(),
            'mailChange'=>$mailChange(),
            'rm'=>$removeFile($arg),
            'chmodDeny'=>$chmodFile($arg,0000),
            'chmodAllow'=>$chmodFile($arg,0644),
            'clearAssetProof'=>$clearAssetProof(),
            'clearClassProof'=>$clearClassProof($arg),
            'appUrlHttp'=>$setAppUrl('http://cms.ald1n.com'),
            'appUrlWrong'=>$setAppUrl('https://wrong-origin.invalid'),
            'appUrlRestore'=>$setAppUrl('https://cms.ald1n.com'),
            'dropCsp','dropJsonLd','dropVaryEdge','dropVaryAll','noHtaccess','rogueScript','edgeKeepsEmailOff','cssWrongType','cssTamperBody','upFails'=>touch($sw[$name]),
            'rateLimit2'=>file_put_contents($sw['rate429'],'2'),
            'rateLimitAlways'=>file_put_contents($sw['rate429'],'ALWAYS'),
            'shopOff'=>$shopOff(),
            'mkBroken'=>file_put_contents($active.'/resources/views/zz-b1157-broken.blade.php',"<?php if( ?>\n"),
            'rmBroken'=>@unlink($active.'/resources/views/zz-b1157-broken.blade.php'),
            'clearDrops'=>array_map(static fn(string $f):bool=>!is_file($f)||unlink($f),[$sw['dropCsp'],$sw['dropJsonLd'],$sw['dropVaryEdge'],$sw['dropVaryAll'],$sw['rogueScript'],$sw['edgeKeepsEmailOff'],$sw['cssWrongType'],$sw['cssTamperBody'],$sw['upFails']]),
            default=>throw new RuntimeException('FIXTURE_UNKNOWN_ACTION'),
        };
    }
});
$rc=$o->run();echo 'B1157_FINAL='.($rc===0?$o->final:(str_starts_with($o->final,'HOLD')?$o->final:'HOLD')).PHP_EOL;exit($rc);
