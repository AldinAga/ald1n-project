<?php
declare(strict_types=1);
require $argv[1];$root=getenv('RUNNER_TEMP').'/b1133-fixture';mkdir($root);mkdir($root.'/vendor');file_put_contents($root.'/artisan','same');file_put_contents($root.'/vendor/a.php','vendor');
$files=[['path'=>'artisan','size'=>4,'sha256'=>hash('sha256','same')],['path'=>'vendor/a.php','size'=>6,'sha256'=>hash('sha256','vendor')]];
function check(bool $yes):void {if(!$yes)throw new RuntimeException('FIXTURE_FAILURE');}
$same=inspectRuntime($root,$files);check($same['counts']['sourceNonPHP']['same']===1&&$same['counts']['vendor']['same']===1);
file_put_contents($root.'/artisan','tamper');check(inspectRuntime($root,$files)['counts']['sourceNonPHP']['different']===1);
unlink($root.'/artisan');check(inspectRuntime($root,$files)['counts']['sourceNonPHP']['missing']===1);
symlink($root.'/vendor/a.php',$root.'/artisan');check(inspectRuntime($root,$files)['counts']['sourceNonPHP']['unsafe']===1);
try{inspectRuntime($root,[['path'=>'../escape','size'=>0,'sha256'=>'']]);throw new LogicException('PATH_NOT_REJECTED');}catch(RuntimeException $e){check($e->getMessage()==='MANIFEST_PATH');}
$parsed=cachedFlagInputs("<?php return array( 'public_storefront' => array('enabled'=>false,'checkout_enabled'=>false), 'password'=>'SECRET_SENTINEL' );");check($parsed['enabled']==='OFF'&&$parsed['checkout_enabled']==='OFF'&&!str_contains(json_encode($parsed),'SECRET_SENTINEL'));
foreach(["<?php return getenv('SECRET_SENTINEL');","<?php return [;"] as $bad){try{cachedFlagInputs($bad);throw new LogicException('CONFIG_NOT_REJECTED');}catch(RuntimeException|ParseError $e){}}
echo json_encode(['actualPHP'=>true,'positive'=>2,'negative'=>6,'secretSentinelNotOutput'=>true,'applicationBootstrapped'=>false,'databaseAccess'=>false,'helperSHA256'=>hash_file('sha256',$argv[1])],JSON_PRETTY_PRINT).PHP_EOL;
