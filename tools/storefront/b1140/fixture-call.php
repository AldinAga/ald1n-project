<?php
declare(strict_types=1);require $argv[1].'/ReadControl.php';require $argv[1].'/VisibilityObserver.php';require $argv[1].'/../b1135/HostWebControl.php';$c=json_decode(file_get_contents($argv[2]),true,64,JSON_THROW_ON_ERROR);$o=(new ReflectionClass(B1140ReadControl::class))->newInstanceWithoutConstructor();foreach(['root'=>$c['root'],'home'=>$c['home'],'library'=>$c['library']] as $k=>$v)(new ReflectionProperty($o,$k))->setValue($o,$v);
try{
    if($c['action']==='observe'){if(parse_url($c['origin'],PHP_URL_HOST)!=='127.0.0.1')throw new RuntimeException('FIXTURE_LOOPBACK_REQUIRED');$s=json_decode(file_get_contents($c['state']),true,64,JSON_THROW_ON_ERROR);$curl=curl_init($c['origin'].$s['config']['rpcPath']);curl_setopt_array($curl,[CURLOPT_FOLLOWLOCATION=>false,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode(['action'=>'visibility','nonce'=>$c['nonce']]),CURLOPT_HTTPHEADER=>['Content-Type: application/json','X-Ald1n-Read-Token: '.$s['token']]]);echo 'OBSERVER_JSON='.json_encode(VisibilityObserver::observe($curl,$s['private'],$s['config'],$c['nonce'])).PHP_EOL;}elseif($c['action']==='prepare')(new ReflectionMethod($o,'prepare'))->invoke($o);
    elseif($c['action']==='recover')(new ReflectionMethod($o,'recoverPrevious'))->invoke($o);
    elseif($c['action']==='cleanup'){$s=json_decode(file_get_contents($c['state']),true,64,JSON_THROW_ON_ERROR);(new ReflectionProperty($o,'state'))->setValue($o,$s);(new ReflectionMethod($o,'cleanup'))->invoke($o);}
    elseif($c['action']==='validate')B1140ReadControl::validateSnapshot($c['snapshot'],$c['snapshotState'],$c['nonce']);
    else throw new RuntimeException('FIXTURE_ACTION');echo 'FIXTURE_OK'.PHP_EOL;
}catch(Throwable $e){echo 'FIXTURE_HOLD='.$e->getMessage().PHP_EOL;exit(2);}
