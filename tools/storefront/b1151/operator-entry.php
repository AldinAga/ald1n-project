<?php
declare(strict_types=1);
// B1151 operator entry. Author: Ald1n <https://ald1n.com>
umask(0077);
try{
    $incoming='/home/icaffeco/ald1n-project/incoming';
    if(PHP_SAPI!=='cli'||realpath(__FILE__)!==$incoming.'/b1151-installer.php'||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('CANONICAL_CLI_PROFILE');
    $mode=(string)getenv('B1151_MODE');if(!in_array($mode,['install','rollback'],true))throw new RuntimeException('OPERATOR_MODE');
    $ignore=getenv('B1151_IGNORE_SCHEDULE');$ignore=$ignore===false||$ignore===''?'0':(string)$ignore;if(!in_array($mode.':'.$ignore,['install:0','rollback:0','rollback:1'],true))throw new RuntimeException('OPERATOR_SCHEDULE_OVERRIDE');
    $metadata=$incoming.'/b1151-package.json';if(is_link($metadata)||realpath($metadata)!==$metadata||filesize($metadata)>524288)throw new RuntimeException('PACKAGE_METADATA');$package=json_decode((string)file_get_contents($metadata),true,64,JSON_THROW_ON_ERROR);
    // The pinned commits are the constants of the shipped coordinator (b1151-coordinator.php), compared after it is loaded below; here only the shape.
    if(($package['schemaVersion']??0)!==1||($package['batch']??'')!=='B1151'||!is_array($package['files']??null))throw new RuntimeException('PACKAGE_SOURCE');
    // Every file is verified by digest; the B1134-B1150 helpers are the copies already on the host (never redelivered).
    foreach($package['files'] as $name=>$digest){if(!preg_match('/^(?:b1134-|b1144-|b1145-|b1146-|b1147-|b1148-|b1149-|b1150-|b1151-)[A-Za-z0-9._-]+$/D',$name)||!preg_match('/^[a-f0-9]{64}$/D',$digest)||is_link($incoming.'/'.$name)||realpath($incoming.'/'.$name)!==$incoming.'/'.$name||hash_file('sha256',$incoming.'/'.$name)!==$digest)throw new RuntimeException('PACKAGE_FILE_INTEGRITY');}
    $required=['b1134-files.php','b1144-gates.php','b1144-probe.php','b1144-web.php','b1145-env.php','b1145-app-state.php','b1145-coordinator.php','b1146-probe.php','b1146-app-check.php','b1147-web.php','b1147-app-check.php','b1147-coordinator.php','b1148-web.php','b1148-app-check.php','b1148-coordinator.php','b1149-web.php','b1149-app-check.php','b1149-coordinator.php','b1150-web.php','b1150-app-check.php','b1150-coordinator.php','b1151-app-check.php','b1151-coordinator.php'];
    foreach($required as $name)if(!isset($package['files'][$name]))throw new RuntimeException('PACKAGE_COMPONENT_MISSING');
    foreach($package['overlay']??[] as $entry)if(!isset($package['files'][(string)($entry['payload']??'')]))throw new RuntimeException('PACKAGE_PAYLOAD_NOT_PINNED');
    foreach(['opcache.enable','opcache.enable_cli'] as $setting)if(!in_array(ini_get($setting),['','0'],true)&&!(ini_get($setting)===false&&!extension_loaded('Zend OPcache')))throw new RuntimeException('CLI_UNCACHED_STARTUP_REQUIRED');
    // b1149-coordinator.php and b1150-coordinator.php supply the pinned B1149 and B1150 file lists (STEPS) the chain gate and the tree manifest are built from; b1150-web.php (MediaWeb) is the HTTP client of the smokes;
    // b1149-web.php is verified by digest only.
    foreach(['b1134-files.php','b1144-gates.php','b1144-probe.php','b1144-web.php','b1145-env.php','b1145-coordinator.php','b1146-probe.php','b1147-web.php','b1147-coordinator.php','b1148-web.php','b1148-coordinator.php','b1149-coordinator.php','b1150-web.php','b1150-coordinator.php','b1151-coordinator.php'] as $name)require $incoming.'/'.$name;
    if(($package['emailCommit']??'')!==LegalAccountInstallCoordinator::EMAIL_COMMIT||($package['throttleCommit']??'')!==LegalAccountInstallCoordinator::THROTTLE_COMMIT||($package['baseCommit']??'')!==LegalAccountInstallCoordinator::BASE_COMMIT||($package['b1150Commit']??'')!==LegalAccountInstallCoordinator::B1150_COMMIT||($package['storefrontCommit']??'')!==LegalAccountInstallCoordinator::STOREFRONT_COMMIT||($package['glassCommit']??'')!==LegalAccountInstallCoordinator::GLASS_COMMIT
        ||MediaInstallCoordinator::SOURCE_COMMIT!==LegalAccountInstallCoordinator::B1150_COMMIT||StorefrontInstallCoordinator::SOURCE_COMMIT!==LegalAccountInstallCoordinator::STOREFRONT_COMMIT||GlassOverlayCoordinator::SOURCE_COMMIT!==LegalAccountInstallCoordinator::GLASS_COMMIT||CatalogOverlayCoordinator::SOURCE_COMMIT!==LegalAccountInstallCoordinator::CATALOG_COMMIT)throw new RuntimeException('PACKAGE_SOURCE');
    echo 'B1151_MODE='.$mode.PHP_EOL.'B1151_IGNORE_SCHEDULE='.$ignore.PHP_EOL;
    $coordinator=new LegalAccountInstallCoordinator('/home/icaffeco/ald1n-project','/home/icaffeco',$package,$incoming,$mode,$ignore==='1');$rc=$coordinator->run();echo 'B1151_FINAL='.($rc===0?$coordinator->final:(str_starts_with($coordinator->final,'HOLD')?$coordinator->final:'HOLD')).PHP_EOL;exit($rc);
}catch(Throwable $error){$code=$error->getMessage();if(!preg_match('/^[A-Z0-9_]+$/D',$code))$code='UNEXPECTED_OPERATOR_IO_OR_FRAMEWORK_ERROR';echo 'B1151_FINAL=HOLD'.PHP_EOL.'HOLD_CODE='.$code.PHP_EOL;exit(2);}
