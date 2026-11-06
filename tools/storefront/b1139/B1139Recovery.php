<?php
declare(strict_types=1);
/** Exactly one predecessor: verified B1134 pre-switch rollback. Never overwrites its pointer/state. */
final class B1139Recovery
{
    private static function regular(string $path,int $bound):string
    {
        if(is_link($path)||realpath($path)!==$path||!is_file($path)||filesize($path)>$bound||fileowner($path)!==posix_geteuid())throw new RuntimeException('B1135_PREDECESSOR_FILE');$data=file_get_contents($path);if(!is_string($data))throw new RuntimeException('B1135_PREDECESSOR_READ');return $data;
    }
    private static function identity(string $path):array
    {
        if(is_link($path)||realpath($path)!==$path||!is_dir($path)||fileowner($path)!==posix_geteuid())throw new RuntimeException('B1135_DIRECTORY_IDENTITY');$s=stat($path);return ['dev'=>$s['dev'],'ino'=>$s['ino']];
    }
    private static function publicExtras(string $base,array $expected,array $privatePaths):array
    {
        self::identity($base.'/public');$extras=[];$count=0;
        $iterator=new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(new RecursiveDirectoryIterator($base.'/public',FilesystemIterator::SKIP_DOTS),static fn($entry)=>!isset($privatePaths[substr($entry->getPathname(),strlen($base)+1)])),RecursiveIteratorIterator::SELF_FIRST);
        foreach($iterator as $entry){$relative=substr($entry->getPathname(),strlen($base)+1);if(++$count>20000||$entry->isLink())throw new RuntimeException('B1135_PUBLIC_EXTRAS_DRIFT');if($entry->isDir())continue;if(!$entry->isFile())throw new RuntimeException('B1135_PUBLIC_EXTRAS_DRIFT');if(isset($expected[$relative]))continue;
            if($relative==='public/.user.ini'){$text=self::regular($entry->getPathname(),65536);if(preg_match('/auto_prepend|auto_append|opcache\.preload|opcache\.file_cache_only|APP_CONFIG_CACHE|APP_BASE_PATH|APP_ROUTES_CACHE|APP_SERVICES_CACHE|APP_PACKAGES_CACHE|VIEW_COMPILED_PATH/i',$text))throw new RuntimeException('B1135_PUBLIC_EXTRAS_DRIFT');}
            elseif(!preg_match('/\.(?:png|jpe?g|gif|svg|webp|ico|css|js|map|txt|xml|json|woff2?|ttf|eot|pdf)$/i',$relative))throw new RuntimeException('B1135_PUBLIC_EXTRAS_DRIFT');
            $extras[$relative]=hash('sha256',self::regular($entry->getPathname(),33554432));
        }ksort($extras);return $extras;
    }
    public static function prepare(string $root,string $home,array $manifest,array $git):array
    {
        [$reportBytes,$reportedGit,$source]=self::readReport($root);
        return self::fromPredecessor($root,$home,$manifest,$git,$reportBytes,$reportedGit,$source);
    }
    private static function readReport(string $root):array
    {
        $report=$root.'/docs/operations/1083-B1134-CONTROLLED-OFF-INSTALL-20261106-082445.md';$reportBytes=self::regular($report,131072);
        if(hash('sha256',$reportBytes)!=='6666c636a1894c1bc7b1cd067ce1f12e418725aa485511f81bf6652eaacce7b1'||!preg_match('/^HOST_GIT=(.+)$/m',$reportBytes,$match))throw new RuntimeException('B1135_EXACT_1083_REQUIRED');$reportedGit=json_decode($match[1],true,32,JSON_THROW_ON_ERROR);
        if(!preg_match('/^SOURCE_PROVENANCE=(.+)$/m',$reportBytes,$match))throw new RuntimeException('B1135_REPORTED_PROVENANCE');$source=json_decode($match[1],true,32,JSON_THROW_ON_ERROR);
        $failed=self::regular($root.'/docs/operations/1085-B1135-CONTROLLED-OFF-INSTALL-20261106-090353.md',131072);
        $capacity=self::regular($root.'/docs/operations/1089-B1136-WEB-OPCACHE-READ-20261106-142108.md',131072);
        if(hash('sha256',$failed)!=='bf541cabcda5683e740c64ae1e30aa589ad7af0883c2e1139b314d775a104967'||hash('sha256',$capacity)!=='2da79ae7af3181efeefa6801046d583ac1fcaecceb3e6d526025fa7fb02f0bc6')throw new RuntimeException('B1137_EXACT_1085_1089_REQUIRED');
        if(!str_contains($failed,'RELEASE_PHASE="ROLLED_BACK_PRE_SWITCH"')||!preg_match('/^B1135_PREDECESSOR_RECOVERY=(.+)$/m',$failed,$match))throw new RuntimeException('B1137_FAILED_ATTEMPT_BINDING');$source['_reportedPredecessor']=json_decode($match[1],true,32,JSON_THROW_ON_ERROR);
        if(!preg_match('/^HOST_GIT=(.+)$/m',$failed,$match))throw new RuntimeException('B1137_FAILED_ATTEMPT_GIT');$source['_reportedGit1085']=json_decode($match[1],true,32,JSON_THROW_ON_ERROR);
        $nonces=[];for($n=1;$n<=3;$n++){if(!preg_match('/^WEB_OPCACHE_SNAPSHOT_'.$n.'=(.+)$/m',$capacity,$match))throw new RuntimeException('B1137_CAPACITY_REPORT');$sample=json_decode($match[1],true,32,JSON_THROW_ON_ERROR);if(($sample['cacheState']??'')!=='READABLE'||($sample['cacheFull']??null)!==false||($sample['restartPending']??null)!==false||($sample['restartInProgress']??null)!==false||($sample['actualIndex']??false)!==true||($sample['sapi']??'')!=='litespeed'||!is_int($sample['freeMemoryBytes']??null)||$sample['freeMemoryBytes']<=0||isset($nonces[$sample['nonce']??'']))throw new RuntimeException('B1137_CAPACITY_REPORT');$nonces[$sample['nonce']]=true;}
        foreach(['NORMAL_LOGIN_BEFORE_HTTP=200','NORMAL_LOGIN_DURING_HTTP=200','NORMAL_LOGIN_AFTER_CLEANUP_HTTP=200','OWNED_PASS_THROUGH_BRIDGE_CLEANUP=PASS','REVOKED_PROBE_UNAVAILABLE=PASS','EXIT=0'] as $line)if(!str_contains($capacity,$line))throw new RuntimeException('B1137_READ_ONLY_CHECK_NOT_CLOSED');
        $source['_admissionReports']=['1085'=>$failed,'1089'=>$capacity];
        return [$reportBytes,$reportedGit,$source];
    }
    private static function originalChain(string $home,array $s,array $plan,string $runtime,string $reportBytes):array
    {
        $originalId=basename($runtime);$private=$home.'/.ald1n-b1134-state/'.$originalId;$pointer=$home.'/.ald1n-b1134-state/current.json';$p=self::regular($pointer,8192);$state=self::regular($private.'/state.json',16777216);$journal=self::regular($private.'/journal.jsonl',131072);$proof=$s['predecessor']??[];
        if(($proof['private']??'')!==$private||($proof['preservedUnmodified']??false)!==true||($proof['reportSHA256']??'')!==hash('sha256',$reportBytes)||($proof['pointerSHA256']??'')!==hash('sha256',$p)||($proof['stateSHA256']??'')!==hash('sha256',$state)||($proof['journalSHA256']??'')!==hash('sha256',$journal))throw new RuntimeException('B1137_ORIGINAL_CHAIN_DRIFT');
        $decodedPointer=json_decode($p,true,32,JSON_THROW_ON_ERROR);$original=json_decode($state,true,512,JSON_THROW_ON_ERROR);$first=json_decode(explode("\n",$journal)[0],true,512,JSON_THROW_ON_ERROR);
        if(($decodedPointer['id']??'')!==$originalId||($decodedPointer['private']??'')!==$private||($original['phase']??'')!=='ROLLED_BACK_PRE_SWITCH'||($original['plan']??null)!==$plan||($first['plan']??null)!==$plan||($first['owner']??null)!==($original['owner']??null)||($original['web']['librarySHA256']??'')!=='4d4696404b59a0dcd33fdac2ca563c757a3f2a94ce8ad0fe3483869eb6f08c6e')throw new RuntimeException('B1137_ORIGINAL_CHAIN_BINDING');
        $archived=['predecessor-pointer.json'=>$p,'predecessor-state.json'=>$state,'predecessor-journal.jsonl'=>$journal,'predecessor-1083.md'=>$reportBytes];foreach($archived as $name=>$bytes)if(self::regular($s['private'].'/'.$name,16777216)!==$bytes)throw new RuntimeException('B1137_ARCHIVED_CHAIN_DRIFT');
        return ['pointerPath'=>$pointer,'statePath'=>$private.'/state.json','journalPath'=>$private.'/journal.jsonl','pointer'=>$p,'state'=>$state,'journal'=>$journal];
    }
    private static function fromPredecessor(string $root,string $home,array $manifest,array $git,string $reportBytes,array $reportedGit,array $source):array
    {
        $pointer=$home.'/.ald1n-b1135-state/current.json';$pointerBytes=self::regular($pointer,8192);$p=json_decode($pointerBytes,true,32,JSON_THROW_ON_ERROR);$id=$p['id']??'';$oldPrivate=$home.'/.ald1n-b1135-state/'.$id;
        if(!preg_match('/^[a-f0-9]{32}$/D',$id)||($p['private']??'')!==$oldPrivate)throw new RuntimeException('B1135_PREDECESSOR_POINTER');self::identity($oldPrivate);
        $stateBytes=self::regular($oldPrivate.'/state.json',16777216);$s=json_decode($stateBytes,true,512,JSON_THROW_ON_ERROR);$plan=$s['plan']??[];$active=$root.'/apps/cms/current';$runtime=$s['runtime']??'';
        if(!is_string($runtime)||!preg_match('~^'.preg_quote($home,'~').'/\.ald1n-b1134-retained/[a-f0-9]{32}$~D',$runtime)||basename($runtime)===$id)throw new RuntimeException('B1137_ORIGINAL_RETAINED_ID_REQUIRED');
        if(($s['schemaVersion']??0)!==1||($s['phase']??'')!=='ROLLED_BACK_PRE_SWITCH'||($s['private']??'')!==$oldPrivate||($s['id']??'')!==$id||($s['schedulerPauseAttempted']??false)!==true||($plan['active']??'')!==$active||($plan['candidate']??'')!==$runtime.'/candidate'||($plan['rollback']??'')!==$runtime.'/retained'||($plan['failed']??'')!==$runtime.'/failed')throw new RuntimeException('B1135_PRE_SWITCH_STATE_REQUIRED');
        if(($source['_reportedPredecessor']??null)!==($s['predecessor']??null))throw new RuntimeException('B1137_REPORTED_PREDECESSOR_DRIFT');$origin=self::originalChain($home,$s,$plan,$runtime,$reportBytes);
        foreach(['head','branch','indexSHA256'] as $key)if(($source['_reportedGit1085'][$key]??null)!==($s['git'][$key]??null))throw new RuntimeException('B1137_REPORTED_GIT_DRIFT');
        if($source['runtimeClampedConfigSHA256']!==$s['clampedConfigSHA256']||$source['runtimeOriginalConfigSHA256']!==$s['mainOriginalConfigSHA256']||$source['oldActiveConfigSHA256']!==$s['oldActiveConfigSHA256'])throw new RuntimeException('B1135_REPORTED_CONFIG_BINDING');
        $journalPath=$oldPrivate.'/journal.jsonl';if(($s['journal']??'')!==$journalPath)throw new RuntimeException('B1135_JOURNAL_PATH');$journalBytes=self::regular($journalPath,131072);$first=json_decode(explode("\n",$journalBytes)[0],true,512,JSON_THROW_ON_ERROR);
        if(($first['owner']??null)!==($s['owner']??null)||($first['plan']??null)!==$plan||($s['web']['librarySHA256']??'')!=='177be93697dc7fd78c80e6564d6c872a3f55787adf9cd603a5b323bbb6e8eb7e')throw new RuntimeException('B1135_PREDECESSOR_BINDING');
        if(self::identity($active)!==$plan['oldIdentity']||self::identity($plan['candidate'])!==$plan['candidateIdentity']||$plan['oldIdentity']['dev']!==$plan['candidateIdentity']['dev']||file_exists($plan['rollback'])||is_link($plan['rollback'])||file_exists($plan['failed'])||is_link($plan['failed']))throw new RuntimeException('B1135_PRE_SWITCH_FILESYSTEM_REQUIRED');
        if($git['head']!==$s['git']['head']||$git['branch']!==$s['git']['branch']||$git['indexSHA256']!==$s['git']['indexSHA256']||$reportedGit['head']!==$git['head']||$reportedGit['branch']!==$git['branch']||$reportedGit['indexSHA256']!==$git['indexSHA256'])throw new RuntimeException('B1135_HOST_GIT_DRIFT');
        if(hash('sha256',self::regular($active.'/.env',1048576))!==$s['envSHA256']||hash_file('sha256',$active.'/public/.htaccess')!==$s['preserved']['customHtaccessSHA256']||hash_file('sha256',$active.'/config/public_storefront.php')!==$s['oldActiveConfigSHA256']||file_exists($active.'/bootstrap/cache/config.php'))throw new RuntimeException('B1135_ACTIVE_PRIVATE_OR_CONFIG_DRIFT');
        foreach([$active,$plan['candidate']] as $base)foreach(['maintenance.php','down'] as $name)if(file_exists($base.'/storage/framework/'.$name)||is_link($base.'/storage/framework/'.$name))throw new RuntimeException('B1135_MAINTENANCE_PRESENT');
        if(($manifest['sourceCommit']??'')!=='ef3f927b77ba573881c2b6dba341732c417e6725'||count($manifest['files'])!==8531)throw new RuntimeException('B1135_RUNTIME_MANIFEST');$expected=[];
        foreach($manifest['files'] as $file){$relative=$file['path'];InstallFileOps::relative($relative);if(isset($expected[$relative]))throw new RuntimeException('B1135_DUPLICATE_RUNTIME_PATH');$expected[$relative]=true;$wanted=$relative==='config/public_storefront.php'?$s['clampedConfigSHA256']:($relative==='public/.htaccess'?$s['preserved']['customHtaccessSHA256']:$file['sha256']);$path=$plan['candidate'].'/'.$relative;if(hash('sha256',self::regular($path,33554432))!==$wanted)throw new RuntimeException('B1135_CANDIDATE_DRIFT');}
        foreach(['app','bootstrap','config','database','resources','routes','bin','vendor'] as $directory){$path=$plan['candidate'].'/'.$directory;if(!is_dir($path))continue;self::identity($path);foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path,FilesystemIterator::SKIP_DOTS)) as $file){$relative=substr($file->getPathname(),strlen($plan['candidate'])+1);if($file->isLink()||!$file->isFile()||!isset($expected[$relative]))throw new RuntimeException('B1135_UNEXPECTED_RUNTIME_FILE');}}
        $links=['.env'=>$plan['rollback'].'/.env'];
        foreach(['storage'=>['framework'],'storage/framework'=>['views','maintenance.php','down']] as $relative=>$excluded){self::identity($active.'/'.$relative);foreach(new DirectoryIterator($active.'/'.$relative) as $item){if($item->isDot()||in_array($item->getFilename(),$excluded,true))continue;$name=$relative.'/'.$item->getFilename();$links[$name]=$plan['rollback'].'/'.$name;}}
        foreach(['storage','uploads','attachments'] as $name){$relative='public/'.$name;$path=$active.'/'.$relative;if(!file_exists($path)&&!is_link($path))continue;$resolved=realpath($path);if(!is_string($resolved))throw new RuntimeException('B1135_PRIVATE_PUBLIC_PATH');if(is_link($path)){if(!str_starts_with($resolved,$active.'/storage/'))throw new RuntimeException('B1135_PRIVATE_PUBLIC_LINK');$links[$relative]=$plan['rollback'].substr($resolved,strlen($active));}else{$links[$relative]=$plan['rollback'].'/'.$relative;}}
        $stored=$s['preserved']['privateLinks'];ksort($stored);ksort($links);if($links!==$stored)throw new RuntimeException('B1135_PRIVATE_NAMESPACE_CHANGED');
        foreach($links as $relative=>$target){InstallFileOps::relative($relative);$path=$plan['candidate'].'/'.$relative;if(!is_link($path)||readlink($path)!==$target)throw new RuntimeException('B1135_PRIVATE_LINK_DRIFT');}
        $privatePublic=array_filter($links,static fn($path)=>str_starts_with($path,'public/'),ARRAY_FILTER_USE_KEY);
        if(self::publicExtras($active,$expected,$privatePublic)!==self::publicExtras($plan['candidate'],$expected,$privatePublic))throw new RuntimeException('B1135_PUBLIC_EXTRAS_DRIFT');
        foreach(['storage','storage/framework','storage/framework/views'] as $relative)self::identity($plan['candidate'].'/'.$relative);
        if(scandir($plan['candidate'].'/storage/framework/views')!==['.','..'])throw new RuntimeException('B1135_CANDIDATE_VIEWS_NOT_PRISTINE');
        $newId=bin2hex(random_bytes(16));$private=$home.'/.ald1n-b1137-state/'.$newId;self::identity(dirname($private));if(!mkdir($private,0700))throw new RuntimeException('B1135_NEW_STATE_CREATE');
        foreach(['predecessor-pointer.json'=>$pointerBytes,'predecessor-state.json'=>$stateBytes,'predecessor-journal.jsonl'=>$journalBytes,'origin-b1134-pointer.json'=>$origin['pointer'],'origin-b1134-state.json'=>$origin['state'],'origin-b1134-journal.jsonl'=>$origin['journal'],'predecessor-1083.md'=>$reportBytes,'predecessor-1085.md'=>$source['_admissionReports']['1085'],'capacity-1089.md'=>$source['_admissionReports']['1089']] as $name=>$bytes){$h=fopen($private.'/'.$name,'x');if($h===false)throw new RuntimeException('B1135_ARCHIVE_CREATE');chmod($private.'/'.$name,0600);if(fwrite($h,$bytes)!==strlen($bytes))throw new RuntimeException('B1135_ARCHIVE_WRITE');fflush($h);if(function_exists('fsync'))fsync($h);fclose($h);}
        // Bind archival bytes again before publishing the separate new pointer.
        if(self::regular($pointer,8192)!==$pointerBytes||self::regular($oldPrivate.'/state.json',16777216)!==$stateBytes)throw new RuntimeException('B1135_PREDECESSOR_CHANGED');
        foreach(['pointer','state','journal'] as $key)if(self::regular($origin[$key.'Path'],16777216)!==$origin[$key])throw new RuntimeException('B1137_ORIGINAL_CHAIN_CHANGED');
        $s['predecessor']=['reportSHA256'=>hash('sha256',$source['_admissionReports']['1085']),'capacityReportSHA256'=>hash('sha256',$source['_admissionReports']['1089']),'pointerSHA256'=>hash('sha256',$pointerBytes),'stateSHA256'=>hash('sha256',$stateBytes),'journalSHA256'=>hash('sha256',$journalBytes),'private'=>$oldPrivate,'preservedUnmodified'=>true,'originalB1134PointerSHA256'=>hash('sha256',$origin['pointer']),'originalB1134StateSHA256'=>hash('sha256',$origin['state'])];$s['id']=$newId;$s['private']=$private;$s['journal']=$private.'/journal.jsonl';$s['owner']=ReleasePrimitives::createJournal($s['journal'],$plan);$s['git']=$git;$s['maintenanceStart']=time();$s['maintenanceExpires']=time()+1500;$s['oldMaintenance']=null;$s['newMaintenance']=null;$s['scheduler']=null;$s['schedulerPauseAttempted']=false;$s['web']=['key'=>bin2hex(random_bytes(32)),'token'=>bin2hex(random_bytes(32))];unset($s['visibility'],$s['oldPHP'],$s['preInvalidation'],$s['postInvalidation']);return $s;
    }
}
