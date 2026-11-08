<?php
declare(strict_types=1);
umask(0077);
try{
    $incoming='/home/icaffeco/ald1n-project/incoming';
    if(PHP_SAPI!=='cli'||realpath(__FILE__)!==$incoming.'/b1144-controlled-install.php'||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('CANONICAL_CLI_PROFILE');
    $metadata=$incoming.'/b1144-package.json';if(is_link($metadata)||realpath($metadata)!==$metadata||filesize($metadata)>65536)throw new RuntimeException('PACKAGE_METADATA');$package=json_decode(file_get_contents($metadata),true,64,JSON_THROW_ON_ERROR);
    if(($package['schemaVersion']??0)!==1||($package['batch']??'')!=='B1144'||($package['sourceCommit']??'')!=='ef3f927b77ba573881c2b6dba341732c417e6725'||($package['cmsTree']??'')!=='da8ac0c953e3c6741b921bb9fa660f80d247c55e'||!is_array($package['files']??null))throw new RuntimeException('PACKAGE_SOURCE');
    foreach($package['files'] as $name=>$digest){if(!preg_match('/^(?:b1134-|b1144-)[A-Za-z0-9._-]+$/D',$name)||!preg_match('/^[a-f0-9]{64}$/D',$digest)||is_link($incoming.'/'.$name)||realpath($incoming.'/'.$name)!==$incoming.'/'.$name||hash_file('sha256',$incoming.'/'.$name)!==$digest)throw new RuntimeException('PACKAGE_FILE_INTEGRITY');}
    $required=['b1134-release.php','b1134-files.php','b1134-scheduler.php','b1144-gates.php','b1144-probe.php','b1144-web.php','b1144-recovery.php','b1144-coordinator.php'];
    foreach($required as $name)if(!isset($package['files'][$name]))throw new RuntimeException('PACKAGE_COMPONENT_MISSING');
    foreach(['opcache.enable','opcache.enable_cli'] as $setting)if(!in_array(ini_get($setting),['','0'],true)&&!(ini_get($setting)===false&&!extension_loaded('Zend OPcache')))throw new RuntimeException('CLI_UNCACHED_STARTUP_REQUIRED');
    foreach(['b1134-release.php','b1134-files.php','b1144-gates.php','b1144-probe.php','b1144-web.php','b1144-recovery.php','b1144-coordinator.php'] as $name)require $incoming.'/'.$name;
    $coordinator=new InstallCoordinator('/home/icaffeco/ald1n-project','/home/icaffeco');$rc=$coordinator->run();echo 'B1144_FINAL='.($rc===0?'PASS_INSTALLED_OFF':'HOLD').PHP_EOL;exit($rc);
}catch(Throwable $error){$code=$error->getMessage();if(!preg_match('/^[A-Z0-9_]+$/D',$code))$code='UNEXPECTED_INSTALLER_IO_OR_FRAMEWORK_ERROR';echo 'B1144_FINAL=HOLD'.PHP_EOL.'HOLD_CODE='.$code.PHP_EOL;exit(2);}
