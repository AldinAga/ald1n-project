<?php
declare(strict_types=1);

/** One documented own-account LSAPI restart request; never a cache reset or a retry loop. */
final class AccountRefresh
{
    private static function snapshot(string $path):?array
    {
        clearstatcache(true,$path);$s=@lstat($path);if($s===false)return null;
        if(($s['mode']&0170000)!==0100000||$s['uid']!==posix_geteuid()||$s['nlink']!==1||($s['mode']&0022)!==0||$s['size']>65536)throw new RuntimeException('ACCOUNT_REFRESH_MARKER_UNSAFE');
        $h=fopen($path,'rb');if($h===false)throw new RuntimeException('ACCOUNT_REFRESH_MARKER_READ');$actual=fstat($h);$bytes=stream_get_contents($h,65537);fclose($h);
        foreach(['dev','ino','uid','mode','size','mtime'] as $key)if($actual[$key]!==$s[$key])throw new RuntimeException('ACCOUNT_REFRESH_MARKER_RACE');
        if(!is_string($bytes)||strlen($bytes)!==$s['size'])throw new RuntimeException('ACCOUNT_REFRESH_MARKER_READ');
        return ['dev'=>$s['dev'],'ino'=>$s['ino'],'uid'=>$s['uid'],'mode'=>$s['mode']&0777,'size'=>$s['size'],'mtime'=>$s['mtime'],'atime'=>$s['atime'],'sha256'=>hash('sha256',$bytes),'bytesBase64'=>base64_encode($bytes)];
    }
    public static function bindCalibration(array $refresh,array $visibility,array $probe):void
    {
        self::scope($probe);
        if(($refresh['phase']??'')!=='ACKNOWLEDGED'||($visibility['parent']['pid']??null)!==($refresh['newPPid']??null)||$probe['ppid']!==$refresh['newPPid']||$probe['cacheStartTime']!==($refresh['newCacheStartTime']??null))throw new RuntimeException('ACCOUNT_REFRESH_GROUP_OR_EPOCH_CHANGED');
    }
    private static function awaitReplacement(array $before,callable $probe,int $deadline):array
    {
        $last='NO_RESPONSE';while(hrtime(true)<$deadline){
            $remaining=(int)floor(($deadline-hrtime(true))/1000000000);if($remaining<1)break;
            try{$response=$probe(min(10,$remaining));self::scope($response);$last='OLD_OR_UNACKNOWLEDGED_CACHE';
                if($response['ppid']!==$before['ppid']&&$response['cacheStartTime']>$before['cacheStartTime'])return $response;
            }catch(Throwable $error){$last='UNAVAILABLE_OR_UNSUPPORTED';}
            $remainingNanos=$deadline-hrtime(true);if($remainingNanos<=0)break;usleep((int)min(500000,intdiv($remainingNanos,1000)));
        }
        throw new RuntimeException('ACCOUNT_REFRESH_NOT_ACKNOWLEDGED_'.$last);
    }
    private static function scope(array $probe):void
    {
        $namespace=@readlink('/proc/self/ns/pid');if(!is_string($namespace)||$namespace===''||!is_string($probe['pidNamespace']??null)||($probe['status']??'')!=='ok'||($probe['sapi']??'')!=='litespeed'||($probe['actualIndex']??false)!==true||($probe['uid']??-1)!==posix_geteuid()||!is_int($probe['ppid']??null)||$probe['ppid']<=1||!is_int($probe['cacheStartTime']??null)||$probe['cacheStartTime']<=0||$probe['pidNamespace']!==$namespace)throw new RuntimeException('ACCOUNT_REFRESH_WEB_SCOPE');
    }
    /** Private status saved by the coordinator before the single account-wide signal. */
    public static function run(string $home,string $private,array $before,?array $prior,callable $persist,callable $probe):array
    {
        if($prior!==null)throw new RuntimeException('ACCOUNT_REFRESH_INTENT_ALREADY_EXISTS_NO_RESIGNAL');self::scope($before);
        if(is_link($home)||realpath($home)!==$home||fileowner($home)!==posix_geteuid()||(fileperms($home)&0022)!==0||is_link($private)||realpath($private)!==$private||(fileperms($private)&0777)!==0700||fileowner($private)!==posix_geteuid())throw new RuntimeException('ACCOUNT_REFRESH_PRIVATE_SCOPE');
        $marker=$home.'/.lsphp_restart.txt';$old=self::snapshot($marker);$now=time();if($old!==null&&$old['mtime']>$now)throw new RuntimeException('ACCOUNT_REFRESH_FUTURE_MARKER');
        $archive=['path'=>$marker,'prior'=>$old,'accountScope'=>'icaffeco_ALL_PHP_DOMAINS','oldPPid'=>$before['ppid'],'oldCacheStartTime'=>$before['cacheStartTime']];
        $archivePath=$private.'/account-refresh-original.json';$h=fopen($archivePath,'x');if($h===false)throw new RuntimeException('ACCOUNT_REFRESH_ARCHIVE_CREATE');chmod($archivePath,0600);$text=json_encode($archive,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);if(fwrite($h,$text)!==strlen($text)||!fflush($h)||!fsync($h))throw new RuntimeException('ACCOUNT_REFRESH_ARCHIVE_DURABILITY');fclose($h);
        $record=['phase'=>'INTENT','marker'=>$marker,'archiveSHA256'=>hash('sha256',$text),'oldPPid'=>$before['ppid'],'oldCacheStartTime'=>$before['cacheStartTime'],'intentTime'=>$now];$persist($record);
        // Allow a distinct coarse filesystem second before exactly one descriptor mutation.
        $deadline=hrtime(true)+3000000000;while($old!==null&&time()<=$old['mtime']){if(hrtime(true)>=$deadline)throw new RuntimeException('ACCOUNT_REFRESH_CLOCK_NOT_ADVANCING');usleep(100000);}
        if($old===null){clearstatcache(true,$marker);if(file_exists($marker)||is_link($marker))throw new RuntimeException('ACCOUNT_REFRESH_MARKER_APPEARED');$h=fopen($marker,'x');if($h===false)throw new RuntimeException('ACCOUNT_REFRESH_CREATE');$expected='';}
        else{
            $h=fopen($marker,'r+');if($h===false)throw new RuntimeException('ACCOUNT_REFRESH_OPEN');$fd=fstat($h);$current=self::snapshot($marker);
            foreach(['dev','ino','uid'] as $key)if($fd[$key]!==$old[$key])throw new RuntimeException('ACCOUNT_REFRESH_MARKER_RACE');if($current===null||$current['sha256']!==$old['sha256']||$current['mode']!==$old['mode']||$current['mtime']!==$old['mtime'])throw new RuntimeException('ACCOUNT_REFRESH_MARKER_CHANGED');
            $expected=base64_decode($old['bytesBase64'],true);$byte=$expected===''?"\n":$expected[0];if(fwrite($h,$byte)!==1)throw new RuntimeException('ACCOUNT_REFRESH_SIGNAL_WRITE');if($expected==='')$expected="\n";
        }
        if(!fflush($h)||!fsync($h))throw new RuntimeException('ACCOUNT_REFRESH_SIGNAL_DURABILITY');$fd=fstat($h);fclose($h);$after=self::snapshot($marker);
        if($after===null||$after['dev']!==$fd['dev']||$after['ino']!==$fd['ino']||$after['sha256']!==hash('sha256',$expected)||($old!==null&&($after['mode']!==$old['mode']||$after['mtime']<=$old['mtime'])))throw new RuntimeException('ACCOUNT_REFRESH_SIGNAL_POSTCHECK');
        unset($after['bytesBase64']);$record['phase']='SIGNALLED';$record['markerAfter']=$after;$persist($record);
        $response=self::awaitReplacement($before,$probe,hrtime(true)+90000000000);$record['phase']='ACKNOWLEDGED';$record['newPPid']=$response['ppid'];$record['newCacheStartTime']=$response['cacheStartTime'];$persist($record);return $record;
    }
}
