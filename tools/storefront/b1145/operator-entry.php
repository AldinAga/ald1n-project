<?php
declare(strict_types=1);
// B1145 operator entry. Author: Ald1n <https://ald1n.com>
umask(0077);
try{
    $incoming='/home/icaffeco/ald1n-project/incoming';
    if(PHP_SAPI!=='cli'||realpath(__FILE__)!==$incoming.'/b1145-controlled-migrate-unclamp.php'||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('CANONICAL_CLI_PROFILE');
    $metadata=$incoming.'/b1145-package.json';if(is_link($metadata)||realpath($metadata)!==$metadata||filesize($metadata)>65536)throw new RuntimeException('PACKAGE_METADATA');$package=json_decode(file_get_contents($metadata),true,64,JSON_THROW_ON_ERROR);
    if(($package['schemaVersion']??0)!==1||($package['batch']??'')!=='B1145'||($package['sourceCommit']??'')!=='ef3f927b77ba573881c2b6dba341732c417e6725'||($package['cmsTree']??'')!=='da8ac0c953e3c6741b921bb9fa660f80d247c55e'||!is_array($package['files']??null))throw new RuntimeException('PACKAGE_SOURCE');
    foreach($package['files'] as $name=>$digest){if(!preg_match('/^(?:b1134-|b1144-|b1145-)[A-Za-z0-9._-]+$/D',$name)||!preg_match('/^[a-f0-9]{64}$/D',$digest)||is_link($incoming.'/'.$name)||realpath($incoming.'/'.$name)!==$incoming.'/'.$name||hash_file('sha256',$incoming.'/'.$name)!==$digest)throw new RuntimeException('PACKAGE_FILE_INTEGRITY');}
    $required=['b1134-release.php','b1134-files.php','b1144-gates.php','b1144-probe.php','b1144-web.php','b1145-env.php','b1145-probe.php','b1145-app-state.php','b1145-coordinator.php'];
    foreach($required as $name)if(!isset($package['files'][$name]))throw new RuntimeException('PACKAGE_COMPONENT_MISSING');
    foreach(['opcache.enable','opcache.enable_cli'] as $setting)if(!in_array(ini_get($setting),['','0'],true)&&!(ini_get($setting)===false&&!extension_loaded('Zend OPcache')))throw new RuntimeException('CLI_UNCACHED_STARTUP_REQUIRED');
    foreach(['b1134-release.php','b1134-files.php','b1144-gates.php','b1144-probe.php','b1144-web.php','b1145-env.php','b1145-probe.php','b1145-coordinator.php'] as $name)require $incoming.'/'.$name;
    $coordinator=new MigrateUnclampCoordinator('/home/icaffeco/ald1n-project','/home/icaffeco');$rc=$coordinator->run();echo 'B1145_FINAL='.($rc===0?'PASS_MIGRATED_UNCLAMPED_STOREFRONT_CLOSED':'HOLD').PHP_EOL;exit($rc);
}catch(Throwable $error){$code=$error->getMessage();if(!preg_match('/^[A-Z0-9_]+$/D',$code))$code='UNEXPECTED_OPERATOR_IO_OR_FRAMEWORK_ERROR';echo 'B1145_FINAL=HOLD'.PHP_EOL.'HOLD_CODE='.$code.PHP_EOL;exit(2);}
