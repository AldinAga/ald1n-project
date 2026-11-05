<?php
declare(strict_types=1);
namespace Illuminate\Contracts\Cache {interface LockProvider{}}
namespace {
require $argv[1];
function config(string $key):mixed{return ['cache.default'=>'redis','cache.stores.redis'=>['connection'=>'fixture'],'cache.prefix'=>'fixture','database.redis'=>['fixture'=>true]][$key]??null;}
function env(string $key):mixed{return null;}
final class FixtureLock {public function isOwnedByCurrentProcess():bool{return true;}public function get():bool{return true;}public function release():bool{return true;}}
final class FixtureStore implements \Illuminate\Contracts\Cache\LockProvider{}
final class FixtureCache {
    public array $values=[];public string $fail='';
    public function store():self{return $this;}public function getStore():FixtureStore{return new FixtureStore();}public function lock(mixed ...$args):FixtureLock{return new FixtureLock();}
    public function get(string $key,mixed $default=null):mixed{return $this->values[$key]??$default;}
    public function forever(string $key,mixed $value):bool{if($this->fail!==''&&str_contains($key,$this->fail))return false;$this->values[$key]=$value;return true;}
    public function forget(string $key):bool{unset($this->values[$key]);return true;}
}
final class FixtureKernel {public int $pauses=0;public function __construct(private FixtureCache $cache){}public function bootstrap():void{}public function call(string $command,array $args):int{if($command==='schedule:pause'){$this->pauses++;$this->cache->values['illuminate:schedule:paused']=true;}else $this->cache->values['illuminate:schedule:paused']=false;return 0;}}
final class FixtureApp {public FixtureCache $cache;public FixtureKernel $kernel;public function __construct(private string $base){$this->cache=new FixtureCache();$this->kernel=new FixtureKernel($this->cache);}public function make(string $key):mixed{return $key==='cache'?$this->cache:$this->kernel;}public function basePath():string{return $this->base;}public function storagePath():string{return $this->base.'/storage';}public function getCachedConfigPath():string{return $this->base.'/bootstrap/cache/config.php';}}
$base=getenv('RUNNER_TEMP').'/b1134-cache-failure';mkdir($base);mkdir($base.'/vendor');mkdir($base.'/bootstrap');file_put_contents($base.'/.env','FAKE');file_put_contents($base.'/vendor/autoload.php','<?php');file_put_contents($base.'/bootstrap/app.php','<?php return $GLOBALS["fixtureApp"];');$s=stat($base);$identity=['dev'=>$s['dev'],'ino'=>$s['ino']];$env=hash_file('sha256',$base.'/.env');$failures=[];
$GLOBALS['fixtureApp']=$app=new FixtureApp($base);$app->cache->fail='scheduler-owner';try{SchedulerControl::execute($base,'pause',str_repeat('a',64),$identity,$env,null);$failures[]='OWNER_WRITE_FALSE_ACCEPTED';}catch(\RuntimeException $e){}if($app->kernel->pauses!==0)$failures[]='UNOWNED_PAUSE_EXECUTED';
$GLOBALS['fixtureApp']=$app=new FixtureApp($base);$owner=str_repeat('b',64);$receipt=SchedulerControl::execute($base,'pause',$owner,$identity,$env,null);$app->cache->fail='scheduler-restored';try{SchedulerControl::execute($base,'release',$owner,$identity,$env,$receipt);$failures[]='TERMINAL_WRITE_FALSE_ACCEPTED';}catch(\RuntimeException $e){}if(!isset($app->cache->values['ald1n:release:scheduler-owner']))$failures[]='OWNER_REMOVED_WITHOUT_DURABLE_RESTORE';
if($failures)throw new \RuntimeException(implode('_',$failures));echo json_encode(['actualSchedulerControl'=>true,'cacheBoundary'=>'EXPLICIT_FALSE_RETURN_DOUBLE','failedOwnerWriteNeverPauses'=>true,'failedTerminalWriteRetainsOwner'=>true]).PHP_EOL;
}
