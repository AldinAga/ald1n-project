<?php
declare(strict_types=1);
ini_set('display_errors','0');ini_set('log_errors','0');umask(0077);
try{
    $incoming='/home/icaffeco/ald1n-project/incoming';
    if(PHP_SAPI!=='cli'||realpath(__FILE__)!==$incoming.'/b1142-controlled-install.php'||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('CANONICAL_CLI_PROFILE');
    $metadata=$incoming.'/b1142-package.json';if(is_link($metadata)||realpath($metadata)!==$metadata||filesize($metadata)>65536)throw new RuntimeException('PACKAGE_METADATA');$package=json_decode(file_get_contents($metadata),true,64,JSON_THROW_ON_ERROR);
    if(($package['schemaVersion']??0)!==1||($package['sourceCommit']??'')!=='ef3f927b77ba573881c2b6dba341732c417e6725'||($package['cmsTree']??'')!=='da8ac0c953e3c6741b921bb9fa660f80d247c55e')throw new RuntimeException('PACKAGE_SOURCE');
    foreach($package['files'] as $name=>$digest){if(!preg_match('/^(?:b1134-|b1142-)[A-Za-z0-9._-]+$/D',$name)||!preg_match('/^[a-f0-9]{64}$/D',$digest)||is_link($incoming.'/'.$name)||realpath($incoming.'/'.$name)!==$incoming.'/'.$name||hash_file('sha256',$incoming.'/'.$name)!==$digest)throw new RuntimeException('PACKAGE_FILE_INTEGRITY');}
    foreach(['b1134-release.php','b1134-files.php','b1142-web-client.php','b1142-recovery.php','b1142-coordinator.php'] as $name)require $incoming.'/'.$name;
    $coordinator=new InstallCoordinator('/home/icaffeco/ald1n-project','/home/icaffeco',$incoming.'/'.$package['webLibrary']);exit($coordinator->run());
}catch(Throwable $error){$code=$error->getMessage();if(!preg_match('/^[A-Z0-9_]+$/D',$code))$code='UNEXPECTED_INSTALLER_IO_OR_FRAMEWORK_ERROR';echo 'B1142_FINAL=HOLD'.PHP_EOL.'HOLD_CODE='.$code.PHP_EOL;exit(2);}
