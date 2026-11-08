<?php
declare(strict_types=1);
/**
 * CI-only child runner: one real MigrateUnclampCoordinator run against the fixture host (real artisan, real MariaDB, real
 * Laravel behind `php -S` with OPcache). Explicit double: FixtureWeb sends the same requests over plain HTTP to loopback
 * instead of HTTPS. Fault points from the config run named actions (kill = SIGKILL of this process).
 * Usage: php fixture-run.php <config.json>
 * @author Ald1n <https://ald1n.com>
 */
$cfg=json_decode(file_get_contents($argv[1]),true,16,JSON_THROW_ON_ERROR);
require $cfg['b1134'].'/ReleasePrimitives.php';require $cfg['b1134'].'/InstallFileOps.php';
foreach(['LeanGates','FreshnessProbe','LeanWeb'] as $c)require $cfg['b1144'].'/'.$c.'.php';foreach(['EnvFlags','UnclampProbe','MigrateUnclampCoordinator'] as $c)require $cfg['tools'].'/'.$c.'.php';

final class FixtureWeb
{
    public function __construct(private int $port,private string $secrets){}
    private function get(string $path,array $headers):array
    {
        $c=curl_init('http://127.0.0.1:'.$this->port.$path);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>30,CURLOPT_NOPROXY=>'*',CURLOPT_HTTPHEADER=>array_merge(['Host: cms.ald1n.com','Cache-Control: no-cache'],$headers)]);
        $body=curl_exec($c);$status=(int)curl_getinfo($c,CURLINFO_RESPONSE_CODE);$error=curl_errno($c);curl_close($c);return ['status'=>$status,'body'=>is_string($body)?$body:'','error'=>$error];
    }
    public function status(string $path,int $expected,?string $stamp=null,string $marker='',int $attempts=3,?string $smokeToken=null):void
    {
        $r=$this->get($path.'?ald1n_release_check='.$marker,[]);$pass=$r['error']===0&&$r['status']===$expected;
        echo 'PUBLIC_HTTP_SMOKE='.json_encode(['path'=>$path,'expected'=>$expected,'result'=>$pass?'PASS':'FAIL','observed'=>$r['error']!==0?'TRANSPORT':$r['status'],'fixtureLoopbackHttp'=>true]).PHP_EOL;
        if(!$pass)throw new RuntimeException('PUBLIC_HTTP_SMOKE_'.$expected);
    }
    public function probe(string $name,string $token,bool $invalidate,?string $route=null):array
    {
        if(!preg_match('/^ald1n-b1144-[a-f0-9]{48}\.php$/D',$name)||!preg_match('/^[a-f0-9]{64}$/D',$token))throw new RuntimeException('FRESHNESS_PROBE_REQUEST_SCOPE');
        file_put_contents($this->secrets,$name."\n".$token."\n",FILE_APPEND);
        $r=$this->get('/'.$name,array_merge(['X-Ald1n-Release-Probe: '.$token],$invalidate?['X-Ald1n-Release-Invalidate: YES']:[]));
        if($r['error']!==0||$r['status']!==200)throw new RuntimeException('FRESHNESS_PROBE_HTTP_UNAVAILABLE');$data=json_decode($r['body'],true,16,JSON_THROW_ON_ERROR);$data['_route']='FIXTURE_LOOPBACK_HTTP_CLI_SERVER';return $data;
    }
}

// The CLI default timezone is UTC on purpose: the scheduled-backup gate must use the APP timezone (Europe/Belgrade).
date_default_timezone_set('UTC');
$o=(new ReflectionClass(MigrateUnclampCoordinator::class))->newInstanceWithoutConstructor();
$set=static function(string $name,mixed $value)use($o):void{(new ReflectionProperty($o,$name))->setValue($o,$value);};
foreach(['root','home','base','pointer','php','allowedSapis','stage','reportPath','reportSHA','b1144Base','appStateScript','censusSeconds','waitMargin','minFreeBytes','scheduleGate','fixedNow','backupTimeout','verifyTimeout','migrateTimeout','backupMaxAgeSeconds','probeSamples'] as $key)$set($key,$cfg[$key]);
$set('web',new FixtureWeb($cfg['port'],$cfg['secrets']));
$actions=$cfg['actions']??[];
$pdo=static fn():PDO=>new PDO($cfg['dsn'],$cfg['dbUser'],$cfg['dbPassword'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$rmTree=static function(string $path)use(&$rmTree):void{if(is_link($path)||is_file($path)){unlink($path);return;}if(!is_dir($path))return;foreach(scandir($path) as $n)if($n!=='.'&&$n!=='..')$rmTree($path.'/'.$n);rmdir($path);};
// What the nightly retention does to an older backup: its directory and its backup_runs row disappear.
$prune=static function()use($pdo,$rmTree):void{$row=$pdo()->query("SELECT id,backup_path FROM backup_runs WHERE status='completed' AND backup_type='manual' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);if($row===false)throw new RuntimeException('FIXTURE_NOTHING_TO_PRUNE');$rmTree($row['backup_path']);$pdo()->exec('DELETE FROM backup_runs WHERE id='.(int)$row['id']);};
$once=static fn(string $name):bool=>!file_exists($cfg['faultLog'].'.'.$name)&&touch($cfg['faultLog'].'.'.$name);
$set('fault',static function(string $point)use($actions,$cfg,$pdo,$prune,$once,$o):void{
    foreach((array)($actions[$point]??[]) as $action){
        file_put_contents($cfg['faultLog'],$point.' '.$action."\n",FILE_APPEND);
        match($action){
            'kill'=>posix_kill(getmypid(),9),
            'flag1'=>$pdo()->exec("INSERT INTO settings (setting_key,setting_value,created_at,updated_at) VALUES ('public_storefront_open','1',NOW(),NOW())"),
            'logError'=>file_put_contents($cfg['logDir'].'/laravel-'.date('Y-m-d').'.log','['.date('Y-m-d H:i:s').'] production.ERROR: injected fixture failure during acceptance'."\n",FILE_APPEND),
            'chmodRetained'=>chmod($cfg['retained'],0555),
            'pruneBackup'=>$prune(),
            'pruneBackupOnce'=>$once('pruned')?$prune():null,
            // Ages the in-process backup by one hour (monotonic clock), once.
            'ageBackupOnce'=>$once('aged')?(static function()use($o):void{$p=new ReflectionProperty($o,'processBackup');$b=$p->getValue($o);$b['createdAt']-=3600000000000;$p->setValue($o,$b);})():null,
            // An unexpected extra pending migration appears after the preflight (its migrations row disappears).
            'extraPending'=>$pdo()->exec("DELETE FROM migrations WHERE migration='2026_07_21_000003_create_system_tables'"),
            // The HOLD save fails (disk or permissions): the recovery note must already be printed.
            'failSave'=>throw new RuntimeException('FIXTURE_HOLD_SAVE_FAILED'),
            default=>throw new RuntimeException('FIXTURE_UNKNOWN_ACTION'),
        };
    }
});
exit($o->run());
