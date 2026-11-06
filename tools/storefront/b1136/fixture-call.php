<?php
declare(strict_types=1);require $argv[1].'/ReadControl.php';$c=json_decode(file_get_contents($argv[2]),true,64,JSON_THROW_ON_ERROR);$o=(new ReflectionClass(B1136ReadControl::class))->newInstanceWithoutConstructor();foreach(['root'=>$c['root'],'home'=>$c['home'],'library'=>$c['library']] as $k=>$v)(new ReflectionProperty($o,$k))->setValue($o,$v);
try{
    if($c['action']==='prepare')(new ReflectionMethod($o,'prepare'))->invoke($o);
    elseif($c['action']==='recover')(new ReflectionMethod($o,'recoverPrevious'))->invoke($o);
    elseif($c['action']==='cleanup'){$s=json_decode(file_get_contents($c['state']),true,64,JSON_THROW_ON_ERROR);(new ReflectionProperty($o,'state'))->setValue($o,$s);(new ReflectionMethod($o,'cleanup'))->invoke($o);}
    elseif($c['action']==='validate')B1136ReadControl::validateSnapshot($c['snapshot'],$c['snapshotState'],$c['nonce']);
    else throw new RuntimeException('FIXTURE_ACTION');echo 'FIXTURE_OK'.PHP_EOL;
}catch(Throwable $e){echo 'FIXTURE_HOLD='.$e->getMessage().PHP_EOL;exit(2);}
