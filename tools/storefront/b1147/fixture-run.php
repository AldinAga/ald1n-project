<?php
declare(strict_types=1);
/**
 * CI-only child runner: one real CatalogOverlayCoordinator run against the fixture host (real artisan, real MariaDB, real
 * Laravel behind `php -S` with OPcache, the real catalog payload from the built operator package, on top of the real B1146
 * install). Explicit double: CatalogFixtureWeb sends the same requests over plain HTTP to loopback instead of HTTPS. Fault
 * points run named actions from the config (kill = SIGKILL of this process, stepSmoke = record anonymous statuses).
 * Usage: php fixture-run.php <config.json>
 * @author Ald1n <https://ald1n.com>
 */
$cfg=json_decode((string)file_get_contents($argv[1]),true,16,JSON_THROW_ON_ERROR);
require $cfg['b1134'].'/InstallFileOps.php';foreach(['LeanGates','FreshnessProbe','LeanWeb'] as $c)require $cfg['b1144'].'/'.$c.'.php';foreach(['EnvFlags','MigrateUnclampCoordinator'] as $c)require $cfg['b1145'].'/'.$c.'.php';require $cfg['b1146'].'/OverlayProbe.php';
foreach(['CatalogWeb','CatalogOverlayCoordinator','FixtureWeb'] as $c)require $cfg['tools'].'/'.$c.'.php';

date_default_timezone_set('UTC');
$package=json_decode((string)file_get_contents($cfg['incoming'].'/b1147-package.json'),true,64,JSON_THROW_ON_ERROR);
$o=(new ReflectionClass(CatalogOverlayCoordinator::class))->newInstanceWithoutConstructor();
$set=static function(string $name,mixed $value)use($o):void{(new ReflectionProperty($o,$name))->setValue($o,$value);};
foreach(['root','home','base','pointer','stage','b1146Base','appStateScript','routesScript','catalogScript','php','allowedSapis','censusSeconds','minFreeBytes','scheduleGate','fixedNow','incoming','mode'] as $key)$set($key,$cfg[$key]);
// Explicit doubles for CI time only: shorter request pacing and whole-batch 429 pause (production: 0.7 s and 65 s).
$set('ignoreSchedule',false);$set('paceMicros',150000);$set('rateLimitPauseSeconds',3);(new ReflectionMethod($o,'load'))->invoke($o,$package,$cfg['incoming']);foreach((array)($cfg['overrides']??[]) as $key=>$value)$set($key,$value);
$set('web',new CatalogFixtureWeb($cfg['port'],$cfg['secrets'],$cfg['dropCsp'],$cfg['dropJsonLd'],$cfg['rate429']));
// The owner turning the shop OFF in the admin while the run is in its checks (fixture: the row deleted, the app cache cleared).
$shopOff=static function()use($cfg):void{$active=$cfg['root'].'/apps/cms/current';(new PDO($cfg['db'][0],$cfg['db'][1],$cfg['db'][2],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]))->exec("DELETE FROM settings WHERE setting_key='public_storefront_open'");shell_exec('cd '.escapeshellarg($active).' && '.escapeshellarg(PHP_BINARY).' -d opcache.enable=0 artisan cache:clear --no-interaction > /dev/null 2>&1');};
$actions=$cfg['actions']??[];$active=$cfg['root'].'/apps/cms/current';
// A process that LeanGates counts as a CMS artisan run (its arguments end in "artisan", its working directory is the CMS).
$busy=static function()use($active,$cfg):void{$pid=trim((string)shell_exec('cd '.escapeshellarg($active).' && { setsid bash -c "sleep 40; true" artisan > /dev/null 2>&1 < /dev/null & echo $!; }'));file_put_contents($cfg['busyPidFile'],$pid);};
// Changes one byte of every private backup copy written so far (an interrupted earlier backup left stale copies).
$corrupt=static function()use($cfg):void{$p=json_decode((string)file_get_contents($cfg['pointer']),true);foreach(glob($p['private'].'/backup/*.orig')?:[] as $f)file_put_contents($f,"\n",FILE_APPEND);};
$stepSmoke=static function(string $point)use($cfg):void{
    // Far below the public-shop-browse throttle (120/min per IP): two throttled paths per point.
    $row=['point'=>$point];foreach(['/up','/login','/katalog','/pravne-informacije'] as $p)$row['anon'][$p]=CatalogFixtureWeb::get($cfg['port'],$p)['status'];
    file_put_contents($cfg['stepLog'],json_encode($row,JSON_UNESCAPED_SLASHES)."\n",FILE_APPEND);
};
$set('fault',static function(string $point)use($actions,$cfg,$stepSmoke,$busy,$corrupt,$shopOff):void{
    foreach((array)($actions[$point]??[]) as $action){
        file_put_contents($cfg['faultLog'],$point.' '.$action."\n",FILE_APPEND);
        match($action){
            'kill'=>posix_kill(getmypid(),9),
            'stepSmoke'=>$stepSmoke($point),
            'logError'=>file_put_contents($cfg['logDir'].'/laravel-'.date('Y-m-d').'.log','['.date('Y-m-d H:i:s').'] production.ERROR: injected fixture failure during acceptance'."\n",FILE_APPEND),
            'fail'=>throw new RuntimeException('FIXTURE_INJECTED_FAILURE'),
            'busy'=>$busy(),
            'corruptBackup'=>$corrupt(),
            'dropCsp'=>touch($cfg['dropCsp']),
            'dropJsonLd'=>touch($cfg['dropJsonLd']),
            // The negative switches apply to the installed smokes only; the old-state smokes of the rollback see real headers.
            'rateLimit2'=>file_put_contents($cfg['rate429'],'2'),
            'rateLimitAlways'=>file_put_contents($cfg['rate429'],'ALWAYS'),
            'shopOff'=>$shopOff(),
            'clearDrops'=>array_map(static fn(string $f):bool=>!is_file($f)||unlink($f),[$cfg['dropCsp'],$cfg['dropJsonLd']]),
            default=>throw new RuntimeException('FIXTURE_UNKNOWN_ACTION'),
        };
    }
});
$rc=$o->run();echo 'B1147_FINAL='.($rc===0?$o->final:(str_starts_with($o->final,'HOLD')?$o->final:'HOLD')).PHP_EOL;exit($rc);
