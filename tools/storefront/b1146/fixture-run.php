<?php
declare(strict_types=1);
/**
 * CI-only child runner: one real OverlayCoordinator run against the fixture host (real artisan, real MariaDB, real Laravel
 * behind `php -S` with OPcache, the real PR #152 payload from the built operator package). Explicit double: FixtureWeb
 * sends the same requests over plain HTTP to loopback instead of HTTPS. Fault points run named actions from the config
 * (kill = SIGKILL of this process, stepSmoke = record anonymous and authenticated superadmin statuses at that point).
 * Usage: php fixture-run.php <config.json>
 * @author Ald1n <https://ald1n.com>
 */
$cfg=json_decode((string)file_get_contents($argv[1]),true,16,JSON_THROW_ON_ERROR);
require $cfg['b1134'].'/InstallFileOps.php';foreach(['LeanGates','FreshnessProbe','LeanWeb'] as $c)require $cfg['b1144'].'/'.$c.'.php';foreach(['EnvFlags','MigrateUnclampCoordinator'] as $c)require $cfg['b1145'].'/'.$c.'.php';
foreach(['OverlayProbe','OverlayWeb','OverlayCoordinator','FixtureWeb'] as $c)require $cfg['tools'].'/'.$c.'.php';

date_default_timezone_set('UTC');
$package=json_decode((string)file_get_contents($cfg['incoming'].'/b1146-package.json'),true,64,JSON_THROW_ON_ERROR);
$o=(new ReflectionClass(OverlayCoordinator::class))->newInstanceWithoutConstructor();
$set=static function(string $name,mixed $value)use($o):void{(new ReflectionProperty($o,$name))->setValue($o,$value);};
foreach(['root','home','base','pointer','stage','b1145Base','appStateScript','appCheckScript','php','allowedSapis','censusSeconds','minFreeBytes','scheduleGate','fixedNow','incoming','mode'] as $key)$set($key,$cfg[$key]);
foreach((array)($cfg['overrides']??[]) as $key=>$value)$set($key,$value);
$set('plan',OverlayCoordinator::plan($package,$cfg['incoming']));$set('web',new FixtureWeb($cfg['port'],$cfg['secrets'],$cfg['dropCsp']));
$actions=$cfg['actions']??[];$active=$cfg['root'].'/apps/cms/current';
// A process that LeanGates counts as a CMS artisan run (its arguments end in "artisan", its working directory is the CMS).
$busy=static function()use($active,$cfg):void{$pid=trim((string)shell_exec('cd '.escapeshellarg($active).' && { setsid bash -c "sleep 40; true" artisan > /dev/null 2>&1 < /dev/null & echo $!; }'));file_put_contents($cfg['busyPidFile'],$pid);};
// Changes one byte of every private backup copy written so far (an interrupted earlier backup left stale copies).
$corrupt=static function()use($cfg):void{$p=json_decode((string)file_get_contents($cfg['pointer']),true);foreach(glob($p['private'].'/backup/*.orig')?:[] as $f)file_put_contents($f,"\n",FILE_APPEND);};
$stepSmoke=static function(string $point)use($cfg):void{
    // A sample that stays far below the public-shop-browse throttle (120/min per IP) across 23 points.
    $row=['point'=>$point];foreach(array_merge(OverlayCoordinator::SYSTEM,['/pravne-informacije','/uslovi-prodaje','/katalog','/registracija']) as $p)$row['anon'][$p]=FixtureWeb::get($cfg['port'],$p)['status'];
    foreach(['/admin/settings/public-storefront','/admin/settings','/'] as $p)$row['admin'][$p]=FixtureWeb::get($cfg['port'],$p,['Cookie: '.$cfg['adminCookie']])['status'];
    file_put_contents($cfg['stepLog'],json_encode($row,JSON_UNESCAPED_SLASHES)."\n",FILE_APPEND);
};
$set('fault',static function(string $point)use($actions,$cfg,$stepSmoke,$busy,$corrupt):void{
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
            default=>throw new RuntimeException('FIXTURE_UNKNOWN_ACTION'),
        };
    }
});
$rc=$o->run();echo 'B1146_FINAL='.($rc===0?$o->final:(str_starts_with($o->final,'HOLD')?$o->final:'HOLD')).PHP_EOL;exit($rc);
