<?php
declare(strict_types=1);
// B1149 operator entry. Author: Ald1n <https://ald1n.com>
umask(0077);
try{
    $incoming='/home/icaffeco/ald1n-project/incoming';
    if(PHP_SAPI!=='cli'||realpath(__FILE__)!==$incoming.'/b1149-installer.php'||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('CANONICAL_CLI_PROFILE');
    $mode=(string)getenv('B1149_MODE');if(!in_array($mode,['install','rollback'],true))throw new RuntimeException('OPERATOR_MODE');
    $ignore=getenv('B1149_IGNORE_SCHEDULE');$ignore=$ignore===false||$ignore===''?'0':(string)$ignore;if(!in_array($mode.':'.$ignore,['install:0','rollback:0','rollback:1'],true))throw new RuntimeException('OPERATOR_SCHEDULE_OVERRIDE');
    $metadata=$incoming.'/b1149-package.json';if(is_link($metadata)||realpath($metadata)!==$metadata||filesize($metadata)>524288)throw new RuntimeException('PACKAGE_METADATA');$package=json_decode((string)file_get_contents($metadata),true,64,JSON_THROW_ON_ERROR);
    if(($package['schemaVersion']??0)!==1||($package['batch']??'')!=='B1149'||($package['sourceCommit']??'')!=='7e7481582fa0be3e95503a81b316be63836e508c'||($package['baseCommit']??'')!=='eca7755c6efe05143a6d3cd19f8c9e70b9ee3515'||($package['mainCommit']??'')!=='55d06b9cc010c976dfc56743763dbfeb09b87acf'||($package['glassCommit']??'')!=='560828458974a56a8f7173b986a40d46629e4d8b'||!is_array($package['files']??null))throw new RuntimeException('PACKAGE_SOURCE');
    // Every file is verified by digest; the B1134-B1148 helpers are the copies already on the host (never redelivered).
    foreach($package['files'] as $name=>$digest){if(!preg_match('/^(?:b1134-|b1144-|b1145-|b1146-|b1147-|b1148-|b1149-)[A-Za-z0-9._-]+$/D',$name)||!preg_match('/^[a-f0-9]{64}$/D',$digest)||is_link($incoming.'/'.$name)||realpath($incoming.'/'.$name)!==$incoming.'/'.$name||hash_file('sha256',$incoming.'/'.$name)!==$digest)throw new RuntimeException('PACKAGE_FILE_INTEGRITY');}
    $required=['b1134-files.php','b1144-gates.php','b1144-probe.php','b1144-web.php','b1145-env.php','b1145-app-state.php','b1145-coordinator.php','b1146-probe.php','b1146-app-check.php','b1147-web.php','b1147-app-check.php','b1147-coordinator.php','b1148-web.php','b1148-app-check.php','b1148-coordinator.php','b1149-web.php','b1149-app-check.php','b1149-coordinator.php'];
    foreach($required as $name)if(!isset($package['files'][$name]))throw new RuntimeException('PACKAGE_COMPONENT_MISSING');
    foreach($package['overlay']??[] as $entry)if(!isset($package['files'][(string)($entry['payload']??'')]))throw new RuntimeException('PACKAGE_PAYLOAD_NOT_PINNED');
    foreach(['opcache.enable','opcache.enable_cli'] as $setting)if(!in_array(ini_get($setting),['','0'],true)&&!(ini_get($setting)===false&&!extension_loaded('Zend OPcache')))throw new RuntimeException('CLI_UNCACHED_STARTUP_REQUIRED');
    foreach(['b1134-files.php','b1144-gates.php','b1144-probe.php','b1144-web.php','b1145-env.php','b1145-coordinator.php','b1146-probe.php','b1147-web.php','b1147-coordinator.php','b1148-web.php','b1148-coordinator.php','b1149-web.php','b1149-coordinator.php'] as $name)require $incoming.'/'.$name;
    if(($package['sourceCommit']??'')!==StorefrontInstallCoordinator::SOURCE_COMMIT||GlassOverlayCoordinator::SOURCE_COMMIT!==StorefrontInstallCoordinator::GLASS_COMMIT||CatalogOverlayCoordinator::SOURCE_COMMIT!==StorefrontInstallCoordinator::CATALOG_COMMIT)throw new RuntimeException('PACKAGE_SOURCE');
    echo 'B1149_MODE='.$mode.PHP_EOL.'B1149_IGNORE_SCHEDULE='.$ignore.PHP_EOL;
    $coordinator=new StorefrontInstallCoordinator('/home/icaffeco/ald1n-project','/home/icaffeco',$package,$incoming,$mode,$ignore==='1');$rc=$coordinator->run();echo 'B1149_FINAL='.($rc===0?$coordinator->final:(str_starts_with($coordinator->final,'HOLD')?$coordinator->final:'HOLD')).PHP_EOL;exit($rc);
}catch(Throwable $error){$code=$error->getMessage();if(!preg_match('/^[A-Z0-9_]+$/D',$code))$code='UNEXPECTED_OPERATOR_IO_OR_FRAMEWORK_ERROR';echo 'B1149_FINAL=HOLD'.PHP_EOL.'HOLD_CODE='.$code.PHP_EOL;exit(2);}
