<?php
declare(strict_types=1);
ini_set('display_errors','0');ini_set('log_errors','0');umask(0077);
try{
    $incoming='/home/icaffeco/ald1n-project/incoming';if(PHP_SAPI!=='cli'||realpath(__FILE__)!==$incoming.'/b1140-visibility-observe.php')throw new RuntimeException('CANONICAL_ENTRY');$path=$incoming.'/b1140-package.json';if(is_link($path)||realpath($path)!==$path||filesize($path)>16384)throw new RuntimeException('PACKAGE_METADATA');$c=json_decode(file_get_contents($path),true,32,JSON_THROW_ON_ERROR);if(($c['schemaVersion']??0)!==1||($c['mode']??'')!=='OBSERVATION_ONLY_VISIBILITY')throw new RuntimeException('PACKAGE_MODE');
    foreach($c['files'] as $name=>$sha)if(!preg_match('/^(?:b1140-[A-Za-z0-9._-]+|b1135-web-client\.php)$/D',$name)||!preg_match('/^[a-f0-9]{64}$/D',$sha)||is_link($incoming.'/'.$name)||realpath($incoming.'/'.$name)!==$incoming.'/'.$name||hash_file('sha256',$incoming.'/'.$name)!==$sha)throw new RuntimeException('PACKAGE_INTEGRITY');
    require $incoming.'/b1135-web-client.php';require $incoming.'/b1140-observer.php';require $incoming.'/b1140-control.php';$runner=new B1140ReadControl('/home/icaffeco/ald1n-project','/home/icaffeco',$incoming.'/'.$c['library']);exit($runner->run());
}catch(Throwable $e){$code=$e->getMessage();echo 'B1140_FINAL=HOLD'.PHP_EOL.'HOLD_CODE='.(preg_match('/^[A-Z0-9_]+$/D',$code)?$code:'UNEXPECTED_NATIVE_IO').PHP_EOL;exit(2);}
