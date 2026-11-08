<?php
declare(strict_types=1);

/** B1144 lean, read-only gates. Nothing here mutates the application, its database or PHP configuration. */
final class LeanGates
{
    public const TITLE_SUFFIX_BYTES=44;

    /** Filesystem-only, fail-closed CMS directory scope. Sharing of the docroot by other hostnames is not provable without root. */
    public static function scope(string $root,array $trees):array
    {
        $chain=[$root,$root.'/apps',$root.'/apps/cms'];foreach($trees as $tree){$chain[]=$tree;$chain[]=$tree.'/public';}$checked=0;
        foreach(array_values(array_unique($chain)) as $path){
            clearstatcache(true,$path);$s=@lstat($path);
            if(!is_array($s)||is_link($path)||!is_dir($path)||realpath($path)!==$path)throw new RuntimeException('CMS_SCOPE_REAL_DIRECTORY_REQUIRED');
            if($s['uid']!==posix_geteuid())throw new RuntimeException('CMS_SCOPE_OWNER');
            if(($s['mode']&0022)!==0)throw new RuntimeException('CMS_SCOPE_GROUP_OR_WORLD_WRITABLE');$checked++;
        }
        foreach($trees as $tree){$ini=$tree.'/public/.user.ini';if(file_exists($ini)||is_link($ini))throw new RuntimeException('CMS_SCOPE_PUBLIC_USER_INI_PRESENT');}
        return ['filesystemOnly'=>true,'directoriesChecked'=>$checked,'realPathsNotSymlinks'=>true,'ownedByEffectiveUser'=>true,'notGroupOrWorldWritable'=>true,'publicUserIniAbsent'=>true,'sharingByOtherHostnamesProvable'=>false];
    }

    /**
     * Pure preflight diagnostic. Counts candidate files whose content differs from the active file at the same
     * relative path while the mtimes are equal within one second (the case timestamp revalidation cannot see).
     * Also selects a few files that only the new tree has (or that differ) for the web freshness probe.
     */
    public static function mtimeDiagnostic(string $active,string $candidate,array $manifest):array
    {
        $present=0;$absent=0;$differs=0;$equal=0;$mainOnly=[];$changed=[];
        foreach($manifest['files'] as $file){
            $relative=$file['path'];InstallFileOps::relative($relative);$old=$active.'/'.$relative;$new=$candidate.'/'.$relative;clearstatcache(true,$old);clearstatcache(true,$new);
            $eligible=str_ends_with($relative,'.php')&&(str_starts_with($relative,'app/')||str_starts_with($relative,'routes/'));
            if(is_link($old)||!is_file($old)){$absent++;if($eligible)$mainOnly[$relative]=['new'=>$file['sha256'],'old'=>null];continue;}
            $present++;$oldSHA=hash_file('sha256',$old);if($oldSHA===hash_file('sha256',$new))continue;$differs++;
            if(abs(filemtime($old)-filemtime($new))<=1)$equal++;if($eligible)$changed[$relative]=['new'=>$file['sha256'],'old'=>$oldSHA];
        }
        ksort($mainOnly,SORT_STRING);ksort($changed,SORT_STRING);$probe=array_slice($mainOnly,0,4,true);
        foreach($changed as $relative=>$digests){if(count($probe)>=4)break;$probe[$relative]=$digests;}
        if($probe===[])throw new RuntimeException('NO_NEW_TREE_FILE_FOR_FRESHNESS_PROBE');ksort($probe,SORT_STRING);
        return ['candidateFiles'=>count($manifest['files']),'presentInActive'=>$present,'absentInActive'=>$absent,'contentDiffers'=>$differs,'contentDiffersWithEqualMtimeWithin1s'=>$equal,'diagnosticOnly'=>true,'probeFileCount'=>count($probe),'probeFiles'=>$probe];
    }

    /** Local PHP CLI sha256 verification of a release tree against the pinned manifest (replaces the web verify loop). */
    public static function verifyTree(string $base,array $manifest,string $clampedSHA256,string $htaccessSHA256,int $expectedCount):array
    {
        $count=0;$bytes=0;
        foreach($manifest['files'] as $file){
            $relative=$file['path'];InstallFileOps::relative($relative);$expected=$relative==='config/public_storefront.php'?$clampedSHA256:($relative==='public/.htaccess'?$htaccessSHA256:$file['sha256']);$path=$base.'/'.$relative;clearstatcache(true,$path);
            if(is_link($path)||!is_file($path)||realpath($path)!==$path||hash_file('sha256',$path)!==$expected)throw new RuntimeException('CANDIDATE_LOCAL_SHA256_MISMATCH');$count++;$bytes+=filesize($path);
        }
        if($count!==$expectedCount)throw new RuntimeException('CANDIDATE_LOCAL_SHA256_COUNT');
        return ['verifiedFiles'=>$count,'verifiedBytes'=>$bytes,'localPhpCliSha256'=>true,'webVerificationRequests'=>0];
    }

    /** Own-user process census: pid => args. No /proc reads of other processes are required. */
    public static function processes():array
    {
        $pipes=[];$process=proc_open(['ps','-ww','-u',(string)posix_geteuid(),'-o','pid=,args='],[1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($process))throw new RuntimeException('PS_UNAVAILABLE');
        $output=stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($process)!==0||!is_string($output)||strlen($output)>4194304)throw new RuntimeException('PS_UNATTESTED');$result=[];
        foreach(explode("\n",trim($output)) as $line){if(trim($line)==='')continue;if(!preg_match('/^\s*(\d+)(?:\s+(.*))?$/D',$line,$m))throw new RuntimeException('PS_PARSE');$result[(int)$m[1]]=$m[2]??'';}
        return $result;
    }

    /** Busy = an lsphp title for THIS CMS front controller ("lsphp:" + last 44 bytes of its path), or any artisan process. Other sites are ignored. */
    public static function busy(string $index,array $processes,int $self):array
    {
        $suffix=substr($index,-self::TITLE_SUFFIX_BYTES);$cms=0;$artisan=0;
        foreach($processes as $pid=>$args){if($pid===$self)continue;$args=rtrim((string)$args);if(str_starts_with($args,'lsphp:')&&str_ends_with($args,$suffix)){$cms++;continue;}if(preg_match('~(?:^|[\s/])artisan(?:\s|$)~',$args))$artisan++;}
        return ['cms'=>$cms,'artisan'=>$artisan];
    }

    /** Bounded narrow drain. Three consecutive clean samples are required; otherwise HOLD. */
    public static function drain(string $index,int $timeoutSeconds,int $intervalMilliseconds=500):array
    {
        if(!str_ends_with($index,'/public/index.php')||$timeoutSeconds<1||$timeoutSeconds>600)throw new RuntimeException('DRAIN_SCOPE');
        $deadline=hrtime(true)+$timeoutSeconds*1000000000;$consecutive=0;$samples=0;$last=['cms'=>0,'artisan'=>0];
        do{
            $last=self::busy($index,self::processes(),getmypid());$samples++;
            if($last['cms']===0&&$last['artisan']===0){if(++$consecutive>=3)return ['drained'=>true,'samples'=>$samples,'titleSuffixBytes'=>self::TITLE_SUFFIX_BYTES,'otherSitesIgnored'=>true];}else $consecutive=0;
            if($samples%20===1)echo 'CMS_DRAIN_WAIT='.json_encode(['sample'=>$samples]+$last,JSON_THROW_ON_ERROR).PHP_EOL;
            usleep($intervalMilliseconds*1000);
        }while(hrtime(true)<$deadline);
        echo 'CMS_DRAIN_TIMEOUT='.json_encode($last,JSON_THROW_ON_ERROR).PHP_EOL;throw new RuntimeException('CMS_WORKERS_OR_ARTISAN_NOT_DRAINED');
    }

    public static function migrationFiles(string $directory):array
    {
        if(is_link($directory)||realpath($directory)!==$directory||!is_dir($directory))throw new RuntimeException('MIGRATION_DIRECTORY');$names=[];
        foreach(new DirectoryIterator($directory) as $item){if($item->isDot()||!$item->isFile()||!str_ends_with($item->getFilename(),'.php'))continue;$names[]=substr($item->getFilename(),0,-4);}
        sort($names,SORT_STRING);return $names;
    }

    /** Strict parser for "php artisan migrate:status --no-ansi". Every non-empty line must be the header or one migration. */
    public static function migrationStatus(string $stdout,array $files):array
    {
        if(strlen($stdout)>1048576)throw new RuntimeException('MIGRATION_STATUS_BOUND');$ran=[];$pending=[];
        foreach(preg_split('/\R/',$stdout) as $line){
            $text=trim($line);if($text==='')continue;if(preg_match('~^Migration name\s*\.*\s*Batch / Status$~D',$text))continue;
            if(!preg_match('~^(\d{4}_\d{2}_\d{2}_\d{6}_[A-Za-z0-9_]+)\s*\.*\s*(\[\d+\]\s+Ran|Pending)$~D',$text,$m))throw new RuntimeException('MIGRATION_STATUS_UNPARSED');
            if(isset($ran[$m[1]])||isset($pending[$m[1]]))throw new RuntimeException('MIGRATION_STATUS_DUPLICATE');if($m[2]==='Pending')$pending[$m[1]]=true;else $ran[$m[1]]=true;
        }
        $listed=array_merge(array_keys($ran),array_keys($pending));sort($listed,SORT_STRING);if($listed!==$files)throw new RuntimeException('MIGRATION_STATUS_FILE_SET');
        $pendingNames=array_keys($pending);sort($pendingNames,SORT_STRING);return ['ran'=>count($ran),'pending'=>$pendingNames];
    }

    /** Byte offsets of storage/logs/laravel*.log before the switch, bound to the physical log directory. */
    public static function logBaseline(string $logs):array
    {
        $directory=realpath($logs);if(!is_string($directory)||!is_dir($directory))throw new RuntimeException('LOG_DIRECTORY');$d=stat($directory);$files=[];
        foreach(glob($directory.'/laravel*.log')?:[] as $path){if(is_link($path)||!is_file($path))throw new RuntimeException('LOG_FILE_LINK');clearstatcache(true,$path);$s=stat($path);$files[basename($path)]=['dev'=>$s['dev'],'ino'=>$s['ino'],'size'=>$s['size']];}
        ksort($files,SORT_STRING);return ['dev'=>$d['dev'],'ino'=>$d['ino'],'files'=>$files];
    }

    public static function newLogErrors(string $logs,array $baseline):array
    {
        $directory=realpath($logs);$d=is_string($directory)?stat($directory):false;if(!is_array($d)||$d['dev']!==$baseline['dev']||$d['ino']!==$baseline['ino'])throw new RuntimeException('LOG_DIRECTORY_IDENTITY');$scanned=0;$bytes=0;$replaced=0;$errors=0;
        foreach(glob($directory.'/laravel*.log')?:[] as $path){
            if(is_link($path)||!is_file($path))throw new RuntimeException('LOG_FILE_LINK');clearstatcache(true,$path);$s=stat($path);$offset=0;$known=$baseline['files'][basename($path)]??null;
            if(is_array($known)){if($known['dev']===$s['dev']&&$known['ino']===$s['ino']&&$s['size']>=$known['size'])$offset=$known['size'];else $replaced++;}
            $length=$s['size']-$offset;if($length>33554432)throw new RuntimeException('LOG_APPENDED_BOUND');$chunk='';
            if($length>0){$h=fopen($path,'rb');if($h===false||fseek($h,$offset)!==0)throw new RuntimeException('LOG_READ');$chunk=(string)stream_get_contents($h,$length);fclose($h);}
            $scanned++;$bytes+=strlen($chunk);$errors+=preg_match_all('/^\[[^\]\n]{1,64}\]\s+[A-Za-z0-9_.-]+\.(?:ERROR|CRITICAL|ALERT|EMERGENCY):/m',$chunk);
        }
        return ['filesScanned'=>$scanned,'appendedBytes'=>$bytes,'rotatedOrReplaced'=>$replaced,'newErrorLines'=>$errors];
    }
}
