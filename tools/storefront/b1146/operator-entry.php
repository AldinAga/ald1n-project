<?php
declare(strict_types=1);
// B1146 operator entry. Author: Ald1n <https://ald1n.com>
umask(0077);
try{
    $incoming='/home/icaffeco/ald1n-project/incoming';
    if(PHP_SAPI!=='cli'||realpath(__FILE__)!==$incoming.'/b1146-legal-overlay.php'||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('CANONICAL_CLI_PROFILE');
    $mode=(string)getenv('B1146_MODE');if(!in_array($mode,['install','rollback'],true))throw new RuntimeException('OPERATOR_MODE');
    $metadata=$incoming.'/b1146-package.json';if(is_link($metadata)||realpath($metadata)!==$metadata||filesize($metadata)>262144)throw new RuntimeException('PACKAGE_METADATA');$package=json_decode((string)file_get_contents($metadata),true,64,JSON_THROW_ON_ERROR);
    if(($package['schemaVersion']??0)!==1||($package['batch']??'')!=='B1146'||($package['sourceCommit']??'')!=='eabe16af8c19fade882e25c70425eb43c3e17ed9'||($package['installedSourceCommit']??'')!=='ef3f927b77ba573881c2b6dba341732c417e6725'||!is_array($package['files']??null))throw new RuntimeException('PACKAGE_SOURCE');
    foreach($package['files'] as $name=>$digest){if(!preg_match('/^(?:b1134-|b1144-|b1145-|b1146-)[A-Za-z0-9._-]+$/D',$name)||!preg_match('/^[a-f0-9]{64}$/D',$digest)||is_link($incoming.'/'.$name)||realpath($incoming.'/'.$name)!==$incoming.'/'.$name||hash_file('sha256',$incoming.'/'.$name)!==$digest)throw new RuntimeException('PACKAGE_FILE_INTEGRITY');}
    $required=['b1134-files.php','b1144-gates.php','b1144-probe.php','b1144-web.php','b1145-env.php','b1145-app-state.php','b1145-coordinator.php','b1146-probe.php','b1146-web.php','b1146-app-check.php','b1146-coordinator.php'];
    foreach($required as $name)if(!isset($package['files'][$name]))throw new RuntimeException('PACKAGE_COMPONENT_MISSING');
    foreach($package['overlay']??[] as $entry)if(!isset($package['files'][(string)($entry['payload']??'')]))throw new RuntimeException('PACKAGE_PAYLOAD_NOT_PINNED');
    foreach(['opcache.enable','opcache.enable_cli'] as $setting)if(!in_array(ini_get($setting),['','0'],true)&&!(ini_get($setting)===false&&!extension_loaded('Zend OPcache')))throw new RuntimeException('CLI_UNCACHED_STARTUP_REQUIRED');
    foreach(['b1134-files.php','b1144-gates.php','b1144-probe.php','b1144-web.php','b1145-env.php','b1145-coordinator.php','b1146-probe.php','b1146-web.php','b1146-coordinator.php'] as $name)require $incoming.'/'.$name;
    echo 'B1146_MODE='.$mode.PHP_EOL;
    $coordinator=new OverlayCoordinator('/home/icaffeco/ald1n-project','/home/icaffeco',$package,$incoming,$mode);$rc=$coordinator->run();echo 'B1146_FINAL='.($rc===0?$coordinator->final:($coordinator->final==='HOLD'||str_starts_with($coordinator->final,'HOLD')?$coordinator->final:'HOLD')).PHP_EOL;exit($rc);
}catch(Throwable $error){$code=$error->getMessage();if(!preg_match('/^[A-Z0-9_]+$/D',$code))$code='UNEXPECTED_OPERATOR_IO_OR_FRAMEWORK_ERROR';echo 'B1146_FINAL=HOLD'.PHP_EOL.'HOLD_CODE='.$code.PHP_EOL;exit(2);}
