<?php
declare(strict_types=1);
/**
 * Builds the B1145 operator ZIP with root-relative incoming/ members.
 * The B1134 primitives and the B1144 gates, probe and web client are already on the host from the B1144 package; they are
 * verified by digest (package.json), not redelivered.
 * Usage: php package.php <b1145 dir> <b1144 dir> <b1134 dir> <output dir> <helper source commit>
 * @author Ald1n <https://ald1n.com>
 */
[$self,$tools,$b1144,$b1134,$out,$helper]=$argv+[null,null,null,null,null,null];
if(!is_dir((string)$tools)||!is_dir((string)$b1144)||!is_dir((string)$b1134)||!preg_match('/^[a-f0-9]{40}$/D',(string)$helper))throw new RuntimeException('PACKAGE_ARGUMENTS');if(!is_dir($out)&&!mkdir($out,0755,true))throw new RuntimeException('PACKAGE_OUTPUT');
$pins=['b1134-release.php'=>[$b1134.'/ReleasePrimitives.php','20ba7d5baa2affa719f8e858a5d5c6e89d46341cb20d2dbef4d8cfb2ca64b077'],'b1134-files.php'=>[$b1134.'/InstallFileOps.php','9dce8324bd4464a1689918775736bcd3a850f2b1c2b1b0ca7e130a9e486da12b'],
    'b1144-gates.php'=>[$b1144.'/LeanGates.php','7e62150fcad0884255ddb3c18063f5e2a23852a3eb0fdecb978c2955392ccf6e'],'b1144-probe.php'=>[$b1144.'/FreshnessProbe.php','3dbf4762e419d8df053fa7e642bd1b85df07d66dbffdb94fe0d8d5cdc1b50616'],'b1144-web.php'=>[$b1144.'/LeanWeb.php','54a04bc4dea81d56ad5fe0e6b25a141d4a86acf803f6df95f1c5d2b7adff6a73']];
foreach($pins as $name=>[$source,$digest])if(hash_file('sha256',$source)!==$digest)throw new RuntimeException('PINNED_DEPENDENCY_DRIFT_'.$name);
$members=['b1145-controlled-migrate-unclamp.php'=>'operator-entry.php','b1145-coordinator.php'=>'MigrateUnclampCoordinator.php','b1145-env.php'=>'EnvFlags.php','b1145-probe.php'=>'UnclampProbe.php','b1145-app-state.php'=>'AppState.php','b1145-README-SR.md'=>'README-SR.md'];
$bytes=[];foreach($members as $name=>$source){$data=file_get_contents($tools.'/'.$source);if(!is_string($data)||str_contains($data,"\r"))throw new RuntimeException('PACKAGE_SOURCE_BYTES_'.$name);$bytes[$name]=$data;}
// The shipped code must not reach cPanel, reset OPcache globally, overlay PHP flags, seed, refresh or roll back the database.
foreach($bytes as $name=>$data)if(str_ends_with($name,'.php')&&preg_match('~/usr/local/cpanel|uapi|DomainInfo|opcache_reset|php_flag|HostProcessGuard|UncachedProfile|--seed|migrate:fresh|migrate:rollback|migrate:refresh|migrate:reset|db:wipe~i',$data))throw new RuntimeException('PACKAGE_FORBIDDEN_MECHANISM_'.$name);
if(preg_match('/public_storefront_open[^\n]*(?:update|insert|upsert|firstOrCreate|updateOrInsert|->save)/i',$bytes['b1145-app-state.php'].$bytes['b1145-coordinator.php']))throw new RuntimeException('PACKAGE_DB_SWITCH_WRITE');
$files=[];foreach($pins as $name=>[,$digest])$files[$name]=$digest;foreach($bytes as $name=>$data)$files[$name]=hash('sha256',$data);ksort($files,SORT_STRING);
$common=['schemaVersion'=>1,'batch'=>'B1145','sourceCommit'=>'ef3f927b77ba573881c2b6dba341732c417e6725','cmsTree'=>'da8ac0c953e3c6741b921bb9fa660f80d247c55e','helperSource'=>$helper];
$bytes['b1145-package.json']=json_encode($common+['files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
$bytes['b1145-provenance.json']=json_encode($common+['author'=>'Ald1n <https://ald1n.com>','workflowRun'=>getenv('GITHUB_RUN_ID')?:null,'workflowRepository'=>getenv('GITHUB_REPOSITORY')?:null,
    'predecessor'=>['report'=>'docs/operations/1106-B1144-CONTROLLED-OFF-INSTALL-20261108-101421.md','reportSHA256'=>'0ac995257de5e18d61b9e0ffcd207af616517cbc5e5295bc28954d3438e433f7','terminal'=>'B1144_FINAL=PASS_INSTALLED_OFF','b1144HelperSource'=>'db3ba00ff9b954ebb6e80b6ee23a7321baffee0a','postInstallVerify'=>'1107 read-only'],
    'stagedOriginalConfig'=>['path'=>'~/.ald1n-stage/b1131-ef3f927.6hh7mU/runtime/config/public_storefront.php','sha256'=>'0cfbc864a3d44822a9d14e80f6252e84eb208c9da5a25d5fe6989e4cad789818'],'runtimeManifestSHA256'=>'d737387e60a981b3b404e0980d9b70cb95b6e1d506a39211981d49dcd12be4c6',
    'pendingMigrations'=>['2026_10_23_020000_create_public_order_accesses','2026_10_23_020100_create_public_checkout_attempts','2026_10_24_030000_add_retail_customer_role'],'pinnedNotRedelivered'=>array_map(fn($p)=>$p[1],$pins),
    'neverDone'=>['storefrontDbSwitchWrite','opcacheGlobalReset','migrateSeedFreshRollbackRefresh','envValuePrinting','symlinkReplacement','mail'],'operatorCommand'=>'bash /home/icaffeco/ald1n-project/incoming/b1145-controlled-migrate-unclamp.sh'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n";
ksort($bytes,SORT_STRING);$checksums='';foreach($bytes as $name=>$data)$checksums.=hash('sha256',$data).'  '.$name."\n";
$wrapper=file_get_contents($tools.'/operator-wrapper.sh');if(!is_string($wrapper)||str_contains($wrapper,"\r")||substr_count($wrapper,"\nSELF_SHA=__SELF_SHA__\n")!==1||substr_count($wrapper,"\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n")!==1)throw new RuntimeException('WRAPPER_TEMPLATE');
$wrapper=str_replace("\nCHECKSUMS_SHA=__CHECKSUMS_SHA__\n","\nCHECKSUMS_SHA=".hash('sha256',$checksums)."\n",$wrapper);$wrapper=str_replace("\nSELF_SHA=__SELF_SHA__\n","\nSELF_SHA=".hash('sha256',$wrapper)."\n",$wrapper);
$bytes['b1145-package.sha256']=$checksums;$bytes['b1145-controlled-migrate-unclamp.sh']=$wrapper;ksort($bytes,SORT_STRING);
$zipPath=$out.'/b1145-operator.zip';if(file_exists($zipPath))unlink($zipPath);$zip=new ZipArchive();if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::EXCL)!==true)throw new RuntimeException('ZIP_CREATE');
foreach($bytes as $name=>$data){$member='incoming/'.$name;$zip->addFromString($member,$data);$zip->setExternalAttributesName($member,ZipArchive::OPSYS_UNIX,(str_ends_with($name,'.sh')?0100755:0100644)<<16);$zip->setMtimeName($member,1762560000);}
if(!$zip->close())throw new RuntimeException('ZIP_CLOSE');
$zipSHA=hash_file('sha256',$zipPath);file_put_contents($zipPath.'.sha256',$zipSHA.'  b1145-operator.zip'."\n");
echo json_encode(['zip'=>basename($zipPath),'zipSHA256'=>$zipSHA,'zipBytes'=>filesize($zipPath),'members'=>array_map(fn($n)=>'incoming/'.$n,array_keys($bytes)),'checksumsSHA256'=>hash('sha256',$checksums),'wrapperSHA256'=>hash('sha256',$wrapper),'files'=>$files],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
