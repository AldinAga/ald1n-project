<?php
declare(strict_types=1);

/** INCOMPLETE DRAFT LIBRARY. No hosting entrypoint or production authorization. */
final class ReleasePrimitives
{
    private static function confinedDirectory(string $path):void
    {
        if(is_link($path)||!is_dir($path)||realpath($path)!==$path)throw new RuntimeException('DIRECTORY_CONFINEMENT');
    }

    /** Candidate is inactive. Private bytes remain in the retained original release. */
    public static function preservePrivate(string $active,string $candidate,string $rollback,array $publicPrivatePaths=[]):array
    {
        self::confinedDirectory($active);self::confinedDirectory($candidate);
        if(file_exists($rollback)||is_link($rollback)||dirname($rollback)!==dirname($active))throw new RuntimeException('ROLLBACK_COLLISION');
        self::confinedDirectory($candidate.'/public');
        foreach(['.env','storage'] as $name){
            $source=$active.'/'.$name;$target=$candidate.'/'.$name;
            if(is_link($source)||realpath($source)!==$source||($name==='.env'?!is_file($source):!is_dir($source))||file_exists($target)||is_link($target))throw new RuntimeException('PRIVATE_PATH_CONFINEMENT');
        }
        self::confinedDirectory($active.'/storage/framework');
        if(is_link($active.'/storage/framework/views'))throw new RuntimeException('OLD_VIEWS_LINK');
        // New compiled views are release-local, while ALL business storage entries remain shared.
        $links=['.env'=>$rollback.'/.env'];$newDirectories=[$candidate.'/storage',$candidate.'/storage/framework',$candidate.'/storage/framework/views'];
        foreach(new DirectoryIterator($active.'/storage') as $item){if($item->isDot()||$item->getFilename()==='framework')continue;$name=$item->getFilename();$links['storage/'.$name]=$rollback.'/storage/'.$name;}
        foreach(new DirectoryIterator($active.'/storage/framework') as $item){if($item->isDot()||in_array($item->getFilename(),['views','maintenance.php','down'],true))continue;$name=$item->getFilename();$links['storage/framework/'.$name]=$rollback.'/storage/framework/'.$name;}
        foreach($publicPrivatePaths as $relative){
            if(!in_array($relative,['public/storage','public/uploads','public/attachments'],true))throw new RuntimeException('UNAPPROVED_PRIVATE_PUBLIC_PATH');
            $source=$active.'/'.$relative;$target=$candidate.'/'.$relative;$resolved=realpath($source);
            if(!is_string($resolved)||(!is_dir($resolved)&&!is_file($resolved))||file_exists($target)||is_link($target))throw new RuntimeException('PRIVATE_PUBLIC_CONFINEMENT');
            if(is_link($source)){
                if(!str_starts_with($resolved,$active.'/storage/'))throw new RuntimeException('PRIVATE_LINK_OUTSIDE_STORAGE');
                $links[$relative]=$rollback.substr($resolved,strlen($active));
            }else{
                if($resolved!==$source)throw new RuntimeException('PRIVATE_PUBLIC_PARENT_LINK');
                $links[$relative]=$rollback.'/'.$relative;
            }
        }
        $htaccess=$active.'/public/.htaccess';$candidateHtaccess=$candidate.'/public/.htaccess';
        if(is_link($htaccess)||!is_file($htaccess)||realpath($htaccess)!==$htaccess||is_link($candidateHtaccess)||!is_file($candidateHtaccess))throw new RuntimeException('HTACCESS_CONFINEMENT');
        $originalCandidate=file_get_contents($candidateHtaccess);$originalMode=fileperms($candidateHtaccess)&0777;$custom=file_get_contents($htaccess);$made=[];
        try{
            foreach($newDirectories as $directory)if(!mkdir($directory,0755))throw new RuntimeException('PRIVATE_DIRECTORY_CREATE');
            foreach($links as $relative=>$destination){if(!symlink($destination,$candidate.'/'.$relative))throw new RuntimeException('PRIVATE_LINK_CREATE');$made[]=$relative;}
            if(file_put_contents($candidateHtaccess,$custom)!==strlen($custom)||!chmod($candidateHtaccess,fileperms($htaccess)&0777))throw new RuntimeException('HTACCESS_COPY');
        }catch(Throwable $error){
            foreach(array_reverse($made) as $relative){$target=$candidate.'/'.$relative;if(is_link($target)&&readlink($target)===$links[$relative])unlink($target);}
            foreach(array_reverse($newDirectories) as $directory)if(is_dir($directory)&&!is_link($directory))rmdir($directory);
            file_put_contents($candidateHtaccess,$originalCandidate);chmod($candidateHtaccess,$originalMode);throw $error;
        }
        return ['privateLinks'=>$links,'originalEnvSHA256'=>hash_file('sha256',$active.'/.env'),'customHtaccessSHA256'=>hash('sha256',$custom),'privateBytesCopied'=>false,'compiledViews'=>'NEW_EMPTY_RELEASE_LOCAL','retainedRelease'=>$rollback,'retainedReleasePolicy'=>'PERMANENT_PRIVATE_DATA_DEPENDENCY_DO_NOT_PRUNE'];
    }

    public static function createJournal(string $path,array $plan=[]):string
    {
        self::confinedDirectory(dirname($path));if(file_exists($path)||is_link($path))throw new RuntimeException('JOURNAL_EXISTS');
        $owner=bin2hex(random_bytes(32));$handle=fopen($path,'x');if($handle===false)throw new RuntimeException('JOURNAL_CREATE');
        chmod($path,0600);fwrite($handle,json_encode(['owner'=>$owner,'state'=>'PREPARED','plan'=>$plan],JSON_THROW_ON_ERROR)."\n");fflush($handle);fclose($handle);return $owner;
    }

    public static function journalState(string $path,string $owner,string $state):void
    {
        if(is_link($path)||realpath($path)!==$path||!is_file($path)||filesize($path)>131072)throw new RuntimeException('JOURNAL_CONFINEMENT');
        $handle=fopen($path,'r+');if($handle===false||!flock($handle,LOCK_EX))throw new RuntimeException('JOURNAL_LOCK');
        try{$text=stream_get_contents($handle);$first=json_decode(explode("\n",$text)[0],true,512,JSON_THROW_ON_ERROR);if(!hash_equals($first['owner']??'',$owner))throw new RuntimeException('JOURNAL_OWNER');
            if(!in_array($state,['MAINTENANCE','INTENT_OLD_RENAME','OLD_RENAMED','INTENT_NEW_RENAME','SWITCHED','INTENT_ROLLBACK','ROLLED_BACK','HOLD','CLOSED'],true))throw new RuntimeException('JOURNAL_STATE');
            fseek($handle,0,SEEK_END);$line=json_encode(['state'=>$state,'previousSHA256'=>hash('sha256',$text)],JSON_THROW_ON_ERROR)."\n";if(fwrite($handle,$line)!==strlen($line))throw new RuntimeException('JOURNAL_WRITE');fflush($handle);
        }finally{flock($handle,LOCK_UN);fclose($handle);}
    }

    /** File maintenance blocks HTTP before autoload and also supplies Laravel's file driver state. */
    public static function ownMaintenance(string $storage,string $owner):array
    {
        self::confinedDirectory($storage);self::confinedDirectory($storage.'/framework');$directory=$storage.'/framework';$paths=[$directory.'/maintenance.php',$directory.'/down'];
        foreach($paths as $path)if(file_exists($path)||is_link($path))throw new RuntimeException('MAINTENANCE_ALREADY_OWNED');
        $stamp=hash('sha256',$owner);$php="<?php\nhttp_response_code(503);header('Retry-After: 60');header('Cache-Control: no-store');echo 'ALD1N owned maintenance ".$stamp."';exit;\n";
        $payloads=[$php,json_encode(['time'=>time(),'retry'=>60,'status'=>503,'secret'=>null,'redirect'=>null,'template'=>null,'ald1n_owner'=>$stamp],JSON_THROW_ON_ERROR)];$created=[];
        try{foreach($paths as $i=>$path){$handle=fopen($path,'x');if($handle===false)throw new RuntimeException('MAINTENANCE_CREATE');$created[$path]=hash('sha256',$payloads[$i]);$written=fwrite($handle,$payloads[$i]);fflush($handle);fclose($handle);if($written!==strlen($payloads[$i]))throw new RuntimeException('MAINTENANCE_WRITE');}}
        catch(Throwable $error){foreach($created as $path=>$digest)if(is_file($path)&&!is_link($path)&&hash_file('sha256',$path)===$digest)unlink($path);throw $error;}
        $identity=stat($storage);return ['files'=>$created,'storage'=>$storage,'storageDevice'=>$identity['dev'],'storageInode'=>$identity['ino'],'ownerSHA256'=>$stamp];
    }

    public static function releaseMaintenance(array $receipt,?string $storageLocation=null):void
    {
        $storage=realpath($storageLocation??$receipt['storage']);if(!is_string($storage))throw new RuntimeException('MAINTENANCE_STORAGE_MISSING');
        $identity=stat($storage);if($identity['dev']!==$receipt['storageDevice']||$identity['ino']!==$receipt['storageInode'])throw new RuntimeException('MAINTENANCE_STORAGE_CHANGED');
        self::confinedDirectory($storage.'/framework');$resolved=[];
        foreach($receipt['files'] as $path=>$digest){$actual=$storage.'/framework/'.basename($path);if(is_link($actual)||!is_file($actual)||realpath($actual)!==$actual||hash_file('sha256',$actual)!==$digest)throw new RuntimeException('MAINTENANCE_OWNERSHIP_CHANGED_HOLD');$resolved[]=$actual;}
        // Remove the framework marker first; pre-autoload HTTP blocker is released last.
        foreach(array_reverse($resolved) as $path)if(!unlink($path))throw new RuntimeException('MAINTENANCE_REMOVE');
    }

    public static function switchJournaled(string $active,string $candidate,string $rollback,string $journal,string $owner):array
    {
        self::confinedDirectory($active);self::confinedDirectory($candidate);
        if(file_exists($rollback)||is_link($rollback)||dirname($rollback)!==dirname($active)||stat($active)['dev']!==stat($candidate)['dev'])throw new RuntimeException('SWITCH_CONFINEMENT');
        self::journalState($journal,$owner,'INTENT_OLD_RENAME');
        if(!rename($active,$rollback))throw new RuntimeException('OLD_RELEASE_RENAME');
        self::journalState($journal,$owner,'OLD_RENAMED');self::journalState($journal,$owner,'INTENT_NEW_RENAME');
        if(!rename($candidate,$active)){
            self::journalState($journal,$owner,'INTENT_ROLLBACK');
            if(!rename($rollback,$active))throw new RuntimeException('RESTORE_RENAME_FAILED_HOLD_MAINTENANCE');
            self::journalState($journal,$owner,'ROLLED_BACK');throw new RuntimeException('CANDIDATE_RENAME_FAILED_OLD_RESTORED');
        }
        self::journalState($journal,$owner,'SWITCHED');return ['active'=>$active,'rollback'=>$rollback,'candidateMovedFrom'=>$candidate,'oldReleasePreserved'=>true];
    }

    /** Disk-state recovery, including interruption between the two directory renames. */
    public static function recoverSwitch(array $plan,string $journal,string $owner,string $failedRelease):string
    {
        $active=$plan['active'];$candidate=$plan['candidate'];$rollback=$plan['rollback'];
        foreach([$active,$candidate,$rollback,$failedRelease] as $path){if(is_link($path))throw new RuntimeException('RECOVERY_LINK');if(file_exists($path))self::confinedDirectory($path);}
        $matches=static function(string $path,array $identity):bool{$s=is_dir($path)?stat($path):false;return is_array($s)&&$s['dev']===$identity['dev']&&$s['ino']===$identity['ino'];};
        if($matches($active,$plan['oldIdentity'])&&!file_exists($rollback))return 'OLD_ALREADY_ACTIVE';
        if(!$matches($rollback,$plan['oldIdentity']))throw new RuntimeException('RECOVERY_OLD_IDENTITY');
        self::journalState($journal,$owner,'INTENT_ROLLBACK');
        if(!file_exists($active)&&$matches($candidate,$plan['candidateIdentity'])){
            if(!rename($rollback,$active))throw new RuntimeException('RECOVERY_OLD_RESTORE');
        }elseif(!file_exists($active)&&!file_exists($candidate)&&$matches($failedRelease,$plan['candidateIdentity'])){
            if(!rename($rollback,$active))throw new RuntimeException('RECOVERY_ROLLBACK_GAP_RESTORE');
        }elseif($matches($active,$plan['candidateIdentity'])&&!file_exists($candidate)&&!file_exists($failedRelease)){
            self::rollbackDirectories(['active'=>$active,'rollback'=>$rollback],$failedRelease);
        }else throw new RuntimeException('AMBIGUOUS_RECOVERY_HOLD_MAINTENANCE');
        self::journalState($journal,$owner,'ROLLED_BACK');return 'OLD_RESTORED';
    }

    public static function offConfig(string $original,string $expectedSHA256):string
    {
        if(!hash_equals($expectedSHA256,hash('sha256',$original)))throw new RuntimeException('CONFIG_SOURCE_HASH');
        $prefix="<?php\ndeclare(strict_types=1);\n";
        if(!str_starts_with($original,$prefix))throw new RuntimeException('CONFIG_PREFIX');
        $body=substr($original,strlen($prefix));
        return $prefix."// Owned runtime-only OFF clamp; original SHA256: ".$expectedSHA256."\n".
            '$values=(static function():array {'."\n".$body."\n})();\n".
            '$values[\'enabled\']=false;'."\n".'$values[\'checkout_enabled\']=false;'."\nreturn \$values;\n";
    }

    public static function switchDirectories(string $active,string $candidate,string $rollback):array
    {
        foreach([$active,$candidate] as $path)if(is_link($path)||!is_dir($path)||realpath($path)!==$path)throw new RuntimeException('DIRECTORY_CONFINEMENT');
        if(file_exists($rollback)||is_link($rollback)||dirname($rollback)!==dirname($active))throw new RuntimeException('ROLLBACK_COLLISION');
        if(stat($active)['dev']!==stat($candidate)['dev'])throw new RuntimeException('CROSS_FILESYSTEM');
        if(!rename($active,$rollback))throw new RuntimeException('OLD_RELEASE_RENAME');
        if(!rename($candidate,$active)){
            if(!rename($rollback,$active))throw new RuntimeException('RESTORE_RENAME_FAILED_HOLD_MAINTENANCE');
            throw new RuntimeException('CANDIDATE_RENAME_FAILED_OLD_RESTORED');
        }
        return ['active'=>$active,'rollback'=>$rollback,'candidateMovedFrom'=>$candidate,'oldReleasePreserved'=>true];
    }

    public static function rollbackDirectories(array $receipt,string $failedRelease):void
    {
        $active=$receipt['active'];$rollback=$receipt['rollback'];
        if(is_link($active)||is_link($rollback)||!is_dir($active)||!is_dir($rollback)||file_exists($failedRelease)||is_link($failedRelease)||dirname($failedRelease)!==dirname($active))throw new RuntimeException('ROLLBACK_CONFINEMENT');
        if(!rename($active,$failedRelease))throw new RuntimeException('FAILED_RELEASE_RETAIN');
        if(!rename($rollback,$active)){
            if(!rename($failedRelease,$active))throw new RuntimeException('ROLLBACK_AND_RESTORE_FAILED_HOLD_MAINTENANCE');
            throw new RuntimeException('ROLLBACK_FAILED_CANDIDATE_RESTORED');
        }
    }

    public static function assertCompleteHostGates(array $facts):void
    {
        foreach(['candidateCompleteManifest','privateFilesPreserved','ownedMaintenance','workersQuiescent','authenticatedIncomingWebMapping','webHandlerAttested','webOpcacheInvalidation','cleanupProven','candidateOffOldSchemaHTTP'] as $gate){
            if(($facts[$gate]??false)!==true)throw new RuntimeException('UNFINISHED_INSTALLER_GATE_'.$gate);
        }
    }
}
