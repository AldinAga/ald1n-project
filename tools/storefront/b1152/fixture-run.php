<?php
declare(strict_types=1);
/**
 * CI-only child runner: one real OriginPinInstallCoordinator run against the fixture host (real artisan, real MariaDB, real
 * Laravel behind `php -S` with OPcache, the real B1152 payload from the built operator package, on top of the real B1146,
 * B1147, B1148, B1149, B1150 and B1151 installs). Explicit double: OriginPinFixtureWeb sends the same requests over plain HTTP to
 * loopback instead of HTTPS and plays the Cloudflare edge (script injection, e-mail obfuscation outside the email_off pair).
 * Fault points run named actions from the config (kill = SIGKILL of this process, stepSmoke = record anonymous statuses with a
 * timestamp, the logo, the guest admin pages, and the media-route link of the catalog page, mailChange = switch the default mailer
 * in .env, removeClass = delete the second new class file, clearProof = forget the web-SAPI class proof of this run).
 * Usage: php fixture-run.php <config.json>
 * @author Ald1n <https://ald1n.com>
 */
$cfg=json_decode((string)file_get_contents($argv[1]),true,16,JSON_THROW_ON_ERROR);
require $cfg['b1134'].'/InstallFileOps.php';foreach(['LeanGates','FreshnessProbe','LeanWeb'] as $c)require $cfg['b1144'].'/'.$c.'.php';foreach(['EnvFlags','MigrateUnclampCoordinator'] as $c)require $cfg['b1145'].'/'.$c.'.php';require $cfg['b1146'].'/OverlayProbe.php';
foreach(['CatalogWeb','CatalogOverlayCoordinator','FixtureWeb'] as $c)require $cfg['b1147'].'/'.$c.'.php';
foreach(['GlassWeb','GlassOverlayCoordinator'] as $c)require $cfg['b1148'].'/'.$c.'.php';
// The B1149, B1150 and B1151 coordinator files (classes StorefrontInstallCoordinator, MediaInstallCoordinator and LegalAccountInstallCoordinator): OriginPinInstallCoordinator reads their STEPS and constants; MediaWeb is its HTTP client.
require $cfg['b1149'].'/StorefrontInstallCoordinator.php';
foreach(['MediaWeb','MediaInstallCoordinator'] as $c)require $cfg['b1150'].'/'.$c.'.php';
require $cfg['b1151'].'/LegalAccountInstallCoordinator.php';
foreach(['LoadProbe','OriginPinInstallCoordinator','FixtureWeb'] as $c)require $cfg['tools'].'/'.$c.'.php';

date_default_timezone_set('UTC');
$package=json_decode((string)file_get_contents($cfg['incoming'].'/b1152-package.json'),true,64,JSON_THROW_ON_ERROR);
$o=(new ReflectionClass(OriginPinInstallCoordinator::class))->newInstanceWithoutConstructor();
$set=static function(string $name,mixed $value)use($o):void{(new ReflectionProperty($o,$name))->setValue($o,$value);};
foreach(['root','home','base','pointer','stage','b1146Base','b1147Base','b1148Base','b1149Base','b1150Base','b1151Base','appStateScript','routesScript','featureScript','php','allowedSapis','censusSeconds','minFreeBytes','scheduleGate','fixedNow','incoming','mode','b1151ReportSHA256','b1151StateId','expectedRoutes'] as $key)$set($key,$cfg[$key]);
// Explicit doubles for CI time only, scaled down but run through the real logic: request pacing, whole-batch 429 pause and
// per-batch 429 budget (production: 0.7 s, 65 s, 120 s; the fixture answers Retry-After: 3, so the 7 s budget allows two waits).
$set('ignoreSchedule',false);$set('paceMicros',150000);$set('rateLimitPauseSeconds',3);$set('rateLimitCapSeconds',7);(new ReflectionMethod($o,'load'))->invoke($o,$package,$cfg['incoming']);foreach((array)($cfg['overrides']??[]) as $key=>$value)if($key!=='probeRoute')$set($key,$value);
$edgeWeb=new OriginPinFixtureWeb($cfg['port'],$cfg['secrets'],$cfg['switches'],$cfg['root'].'/apps/cms/current/public/.htaccess',(string)($cfg['overrides']['probeRoute']??'FIXTURE_LOOPBACK_HTTP_CLI_SERVER'));$set('web',$edgeWeb);
$shopOff=static function()use($cfg):void{$active=$cfg['root'].'/apps/cms/current';(new PDO($cfg['db'][0],$cfg['db'][1],$cfg['db'][2],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]))->exec("DELETE FROM settings WHERE setting_key='public_storefront_open'");shell_exec('cd '.escapeshellarg($active).' && '.escapeshellarg(PHP_BINARY).' -d opcache.enable=0 artisan cache:clear --no-interaction > /dev/null 2>&1');};
$actions=$cfg['actions']??[];$active=$cfg['root'].'/apps/cms/current';
$busy=static function()use($active,$cfg):void{$pid=trim((string)shell_exec('cd '.escapeshellarg($active).' && { setsid bash -c "sleep 40; true" artisan > /dev/null 2>&1 < /dev/null & echo $!; }'));file_put_contents($cfg['busyPidFile'],$pid);};
$corrupt=static function()use($cfg):void{$p=json_decode((string)file_get_contents($cfg['pointer']),true);foreach(glob($p['private'].'/backup/*.orig')?:[] as $f)file_put_contents($f,"\n",FILE_APPEND);};
// The effective mail transport is switched (the default mailer array -> log) in the .env of the retained tree.
$mailChange=static function()use($cfg):void{$env=$cfg['home'].'/.ald1n-b1134-retained/'.str_repeat('d',32).'/retained/.env';$t=(string)file_get_contents($env);file_put_contents($env,str_replace('MAIL_MAILER=array','MAIL_MAILER=log',$t));};
// Negative controls of the order guard: the second new class (the middleware) vanishes right before bootstrap/app.php, or the proof of this run is forgotten.
$removeClass=static function()use($active):void{$f=$active.'/app/Http/Middleware/PinAppUrlOrigin.php';if(!is_file($f)||!unlink($f))throw new RuntimeException('FIXTURE_REMOVE_CLASS');};
$clearProof=static function()use($o):void{$p=new ReflectionProperty($o,'state');$st=$p->getValue($o);unset($st['webClassProof']);$p->setValue($o,$st);};
// Far below the browse throttle (120/min): at most one throttled GET per step point, the full set at group points. Records the
// anonymous statuses, a timestamp (taken first, right after the tree change the fault point follows), the logo URL taken from the catalog page, the guest admin
// pages (302 at every point), and the media-route link of the catalog page when it carries one, and one CI-only POST /tema (303 with the cookie at the group
// points: a 419 (CSRF), 429 (no limiter) or 5xx at any point is a wrong order). /up and /login are asked at EVERY point, also right after the rename of
// bootstrap/app.php: a 500 there is the failure this rollout is ordered to avoid.
$stepSmoke=static function(string $point)use($cfg,$edgeWeb):void{
    $row=['point'=>$point,'t'=>microtime(true)];$group=str_starts_with($point,'GROUP_DONE_')||$point==='AFTER_INVALIDATE';$n=str_starts_with($point,'AFTER_STEP_')?(int)substr($point,11):0;
    foreach(['/up','/login'] as $p)$row['anon'][$p]=OriginPinFixtureWeb::get($cfg['port'],$p)['status'];
    // The browse limiter allows 120 per minute: a catalog GET at every second step, the full set at group points (the last step of a group is always followed by its group point).
    $cat=null;if($group||$n%2===0){$cat=OriginPinFixtureWeb::get($cfg['port'],'/katalog');$row['anon']['/katalog']=$cat['status'];}
    if($group){$row['anon']['/pravne-informacije']=OriginPinFixtureWeb::get($cfg['port'],'/pravne-informacije')['status'];$row['anon']['/katalog/b1152-ci-1']=OriginPinFixtureWeb::get($cfg['port'],'/katalog/b1152-ci-1')['status'];}
    $row['logoChecked']=$cat!==null;
    $logo=$cat===null?null:MediaWeb::siteAssetUrl($cat['body']);$row['logoLinked']=$logo!==null;
    $row['logoStatus']=$logo===null?null:OriginPinFixtureWeb::get($cfg['port'],$logo['path'].($logo['query']===[]?'':'?v='.$logo['query']['v']))['status'];
    $media=$cat===null?null:MediaWeb::mediaUrl($cat['body']);$row['mediaLinked']=$media!==null;
    $row['mediaStatus']=$media===null?null:OriginPinFixtureWeb::get($cfg['port'],$media['path'].'?'.http_build_query($media['query'],'','&',PHP_QUERY_RFC3986))['status'];
    $row['smtpGuest']=OriginPinFixtureWeb::get($cfg['port'],'/admin/settings/smtp')['status'];
    $row['merchantGuest']=OriginPinFixtureWeb::get($cfg['port'],OriginPinInstallCoordinator::MERCHANT_PATH)['status'];
    $row['documentsGuest']=OriginPinFixtureWeb::get($cfg['port'],OriginPinInstallCoordinator::DOCUMENTS_PATH)['status'];
    // The theme limiter allows 20 POSTs per minute: one CI-only POST at the group points only.
    if($group){$t=OriginPinFixtureWeb::get($cfg['port'],'/tema',['Content-Type: application/x-www-form-urlencoded'],'theme=dark&back=%2Fkatalog');$row['themePost']=$t['status'];$row['themeCookie']=isset($t['cookies']['shop_theme']);}
    file_put_contents($cfg['stepLog'],json_encode($row,JSON_UNESCAPED_SLASHES)."\n",FILE_APPEND);
};
$sw=$cfg['switches'];
$set('fault',static function(string $point)use($actions,$cfg,$stepSmoke,$busy,$corrupt,$shopOff,$mailChange,$removeClass,$clearProof,$sw):void{
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
            'removeClass'=>$removeClass(),
            'clearProof'=>$clearProof(),
            'dropCsp','dropJsonLd','dropVaryEdge','dropVaryAll','noHtaccess','rogueScript','edgeKeepsEmailOff'=>touch($sw[$action]),
            'rateLimit2'=>file_put_contents($sw['rate429'],'2'),
            'rateLimitAlways'=>file_put_contents($sw['rate429'],'ALWAYS'),
            'shopOff'=>$shopOff(),
            'clearDrops'=>array_map(static fn(string $f):bool=>!is_file($f)||unlink($f),[$sw['dropCsp'],$sw['dropJsonLd'],$sw['dropVaryEdge'],$sw['dropVaryAll'],$sw['rogueScript'],$sw['edgeKeepsEmailOff']]),
            default=>throw new RuntimeException('FIXTURE_UNKNOWN_ACTION'),
        };
    }
});
$rc=$o->run();echo 'B1152_FINAL='.($rc===0?$o->final:(str_starts_with($o->final,'HOLD')?$o->final:'HOLD')).PHP_EOL;exit($rc);
