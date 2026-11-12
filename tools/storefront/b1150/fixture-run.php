<?php
declare(strict_types=1);
/**
 * CI-only child runner: one real MediaInstallCoordinator run against the fixture host (real artisan, real MariaDB, real
 * Laravel behind `php -S` with OPcache, the real B1150 payload from the built operator package, on top of the real B1146,
 * B1147, B1148 and B1149 installs). Explicit double: MediaFixtureWeb sends the same requests over plain HTTP to loopback instead
 * of HTTPS. Fault points run named actions from the config (kill = SIGKILL of this process, stepSmoke = record anonymous
 * statuses, the logo, the guest SMTP page and the media-route link of the catalog page, mailChange = switch the default
 * mailer in .env).
 * Usage: php fixture-run.php <config.json>
 * @author Ald1n <https://ald1n.com>
 */
$cfg=json_decode((string)file_get_contents($argv[1]),true,16,JSON_THROW_ON_ERROR);
require $cfg['b1134'].'/InstallFileOps.php';foreach(['LeanGates','FreshnessProbe','LeanWeb'] as $c)require $cfg['b1144'].'/'.$c.'.php';foreach(['EnvFlags','MigrateUnclampCoordinator'] as $c)require $cfg['b1145'].'/'.$c.'.php';require $cfg['b1146'].'/OverlayProbe.php';
foreach(['CatalogWeb','CatalogOverlayCoordinator','FixtureWeb'] as $c)require $cfg['b1147'].'/'.$c.'.php';
foreach(['GlassWeb','GlassOverlayCoordinator'] as $c)require $cfg['b1148'].'/'.$c.'.php';
// The B1149 coordinator file (classes StorefrontInstallCoordinator and StorefrontFiles): MediaInstallCoordinator reads its STEPS and constants.
require $cfg['b1149'].'/StorefrontInstallCoordinator.php';
foreach(['MediaWeb','MediaInstallCoordinator','FixtureWeb'] as $c)require $cfg['tools'].'/'.$c.'.php';

date_default_timezone_set('UTC');
$package=json_decode((string)file_get_contents($cfg['incoming'].'/b1150-package.json'),true,64,JSON_THROW_ON_ERROR);
$o=(new ReflectionClass(MediaInstallCoordinator::class))->newInstanceWithoutConstructor();
$set=static function(string $name,mixed $value)use($o):void{(new ReflectionProperty($o,$name))->setValue($o,$value);};
foreach(['root','home','base','pointer','stage','b1146Base','b1147Base','b1148Base','b1149Base','appStateScript','routesScript','featureScript','php','allowedSapis','censusSeconds','minFreeBytes','scheduleGate','fixedNow','incoming','mode'] as $key)$set($key,$cfg[$key]);
// Explicit doubles for CI time only, scaled down but run through the real logic: request pacing, whole-batch 429 pause and
// per-batch 429 budget (production: 0.7 s, 65 s, 120 s; the fixture answers Retry-After: 3, so the 7 s budget allows two waits).
$set('ignoreSchedule',false);$set('paceMicros',150000);$set('rateLimitPauseSeconds',3);$set('rateLimitCapSeconds',7);(new ReflectionMethod($o,'load'))->invoke($o,$package,$cfg['incoming']);foreach((array)($cfg['overrides']??[]) as $key=>$value)if($key!=='probeRoute')$set($key,$value);
$set('web',new MediaFixtureWeb($cfg['port'],$cfg['secrets'],$cfg['switches'],$cfg['root'].'/apps/cms/current/public/.htaccess',(string)($cfg['overrides']['probeRoute']??'FIXTURE_LOOPBACK_HTTP_CLI_SERVER')));
$shopOff=static function()use($cfg):void{$active=$cfg['root'].'/apps/cms/current';(new PDO($cfg['db'][0],$cfg['db'][1],$cfg['db'][2],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]))->exec("DELETE FROM settings WHERE setting_key='public_storefront_open'");shell_exec('cd '.escapeshellarg($active).' && '.escapeshellarg(PHP_BINARY).' -d opcache.enable=0 artisan cache:clear --no-interaction > /dev/null 2>&1');};
$actions=$cfg['actions']??[];$active=$cfg['root'].'/apps/cms/current';
$busy=static function()use($active,$cfg):void{$pid=trim((string)shell_exec('cd '.escapeshellarg($active).' && { setsid bash -c "sleep 40; true" artisan > /dev/null 2>&1 < /dev/null & echo $!; }'));file_put_contents($cfg['busyPidFile'],$pid);};
$corrupt=static function()use($cfg):void{$p=json_decode((string)file_get_contents($cfg['pointer']),true);foreach(glob($p['private'].'/backup/*.orig')?:[] as $f)file_put_contents($f,"\n",FILE_APPEND);};
// The effective mail transport is switched (the default mailer array -> log) in the .env of the retained tree.
$mailChange=static function()use($cfg):void{$env=$cfg['home'].'/.ald1n-b1134-retained/'.str_repeat('d',32).'/retained/.env';$t=(string)file_get_contents($env);file_put_contents($env,str_replace('MAIL_MAILER=array','MAIL_MAILER=log',$t));};
// Far below the browse throttle (120/min): at most one throttled GET per step point, the full set at group points. Records the
// anonymous statuses, the logo URL taken from the catalog page (the B1149 logo route stays served at every point), the guest SMTP
// page (302 at every point), the media-route link of the catalog page when it carries one (a linked /media/p/ URL must always be
// served; products without a visible image simply have no link) and one CI-only POST /tema (303 with the cookie at the group
// points: a 419 (CSRF), 429 (no limiter) or 5xx at any point is a wrong order).
$stepSmoke=static function(string $point)use($cfg):void{
    $row=['point'=>$point];$group=str_starts_with($point,'GROUP_DONE_')||$point==='AFTER_INVALIDATE';$n=str_starts_with($point,'AFTER_STEP_')?(int)substr($point,11):0;
    foreach(['/up','/login'] as $p)$row['anon'][$p]=MediaFixtureWeb::get($cfg['port'],$p)['status'];
    // The browse limiter allows 120 per minute: a catalog GET at every second step, the full set at group points (the last step of a group is always followed by its group point).
    $cat=null;if($group||$n%2===0){$cat=MediaFixtureWeb::get($cfg['port'],'/katalog');$row['anon']['/katalog']=$cat['status'];}
    if($group){$row['anon']['/pravne-informacije']=MediaFixtureWeb::get($cfg['port'],'/pravne-informacije')['status'];$row['anon']['/katalog/b1150-ci-1']=MediaFixtureWeb::get($cfg['port'],'/katalog/b1150-ci-1')['status'];}
    $row['logoChecked']=$cat!==null;
    $logo=$cat===null?null:MediaWeb::siteAssetUrl($cat['body']);$row['logoLinked']=$logo!==null;
    $row['logoStatus']=$logo===null?null:MediaFixtureWeb::get($cfg['port'],$logo['path'].($logo['query']===[]?'':'?v='.$logo['query']['v']))['status'];
    $media=$cat===null?null:MediaWeb::mediaUrl($cat['body']);$row['mediaLinked']=$media!==null;
    $row['mediaStatus']=$media===null?null:MediaFixtureWeb::get($cfg['port'],$media['path'].'?'.http_build_query($media['query'],'','&',PHP_QUERY_RFC3986))['status'];
    $row['smtpGuest']=MediaFixtureWeb::get($cfg['port'],'/admin/settings/smtp')['status'];
    // The theme limiter allows 20 POSTs per minute: one CI-only POST at the group points only.
    if($group){$t=MediaFixtureWeb::get($cfg['port'],'/tema',['Content-Type: application/x-www-form-urlencoded'],'theme=dark&back=%2Fkatalog');$row['themePost']=$t['status'];$row['themeCookie']=isset($t['cookies']['shop_theme']);}
    file_put_contents($cfg['stepLog'],json_encode($row,JSON_UNESCAPED_SLASHES)."\n",FILE_APPEND);
};
$sw=$cfg['switches'];
$set('fault',static function(string $point)use($actions,$cfg,$stepSmoke,$busy,$corrupt,$shopOff,$mailChange,$sw):void{
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
            'dropCsp','dropJsonLd','dropVaryEdge','dropVaryAll','noHtaccess','rogueScript'=>touch($sw[$action]),
            'rateLimit2'=>file_put_contents($sw['rate429'],'2'),
            'rateLimitAlways'=>file_put_contents($sw['rate429'],'ALWAYS'),
            'shopOff'=>$shopOff(),
            'clearDrops'=>array_map(static fn(string $f):bool=>!is_file($f)||unlink($f),[$sw['dropCsp'],$sw['dropJsonLd'],$sw['dropVaryEdge'],$sw['dropVaryAll'],$sw['rogueScript']]),
            default=>throw new RuntimeException('FIXTURE_UNKNOWN_ACTION'),
        };
    }
});
$rc=$o->run();echo 'B1150_FINAL='.($rc===0?$o->final:(str_starts_with($o->final,'HOLD')?$o->final:'HOLD')).PHP_EOL;exit($rc);
