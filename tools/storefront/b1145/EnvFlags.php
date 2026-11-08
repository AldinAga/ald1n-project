<?php
declare(strict_types=1);

/**
 * B1145 .env handling for exactly two keys and crash-safe same-directory file replacement.
 * The .env is edited THROUGH its symlink: the real target is resolved, a temp file is written next to it with the same
 * mode, synced, and renamed over the REAL target, so the symlink itself is never replaced. Values are never returned.
 * @author Ald1n <https://ald1n.com>
 */
final class EnvFlags
{
    public const KEYS=['PUBLIC_STOREFRONT_ENABLED','PUBLIC_CHECKOUT_ENABLED'];

    /** The link, its unchanged target and everything the atomic write needs. Fails closed; returns no content. */
    public static function inspect(string $link,string $expectedTarget,string $retainedRoot):array
    {
        clearstatcache(true);
        if(!is_link($link))throw new RuntimeException('ENV_NOT_A_SYMLINK');$target=readlink($link);if($target!==$expectedTarget)throw new RuntimeException('ENV_SYMLINK_TARGET_CHANGED');
        $real=realpath($link);if($real!==$expectedTarget||!str_starts_with($real,$retainedRoot.'/'))throw new RuntimeException('ENV_REALPATH_OUTSIDE_RETAINED_TREE');
        if(is_link($real)||!is_file($real))throw new RuntimeException('ENV_TARGET_NOT_REGULAR_FILE');$s=stat($real);
        if($s['uid']!==posix_geteuid())throw new RuntimeException('ENV_TARGET_OWNER');if(!is_readable($real)||!is_writable($real))throw new RuntimeException('ENV_TARGET_NOT_READ_WRITE');
        $directory=dirname($real);if(is_link($directory)||realpath($directory)!==$directory||!is_writable($directory))throw new RuntimeException('ENV_TARGET_DIRECTORY_NOT_WRITABLE');
        if(stat($directory)['dev']!==$s['dev'])throw new RuntimeException('ENV_TARGET_DIRECTORY_FILESYSTEM');if($s['size']>1048576)throw new RuntimeException('ENV_TARGET_SIZE');
        return ['link'=>$link,'target'=>$real,'directory'=>$directory,'mode'=>$s['mode']&0777,'uid'=>$s['uid'],'dev'=>$s['dev'],'ino'=>$s['ino'],'linkIno'=>lstat($link)['ino']];
    }

    /** Per key: absent, false, true or other. A duplicate, CRLF, NUL or a non-plain key line is refused. */
    public static function state(string $bytes):array
    {
        if(str_contains($bytes,"\r")||str_contains($bytes,"\0"))throw new RuntimeException('ENV_FORMAT_CRLF_OR_NUL');$lines=explode("\n",$bytes);$state=[];
        foreach(self::KEYS as $key){
            $found=[];foreach($lines as $index=>$line)if(preg_match('/^\s*(?:export\s+)?'.$key.'\s*=/',$line))$found[]=$index;
            if(count($found)>1)throw new RuntimeException('ENV_FORMAT_DUPLICATE_KEY');if($found===[]){$state[$key]='absent';continue;}
            if(!preg_match('/^'.$key.'=([A-Za-z0-9]*)$/D',$lines[$found[0]],$m))throw new RuntimeException('ENV_FORMAT_KEY_LINE_NOT_PLAIN');
            $bool=filter_var($m[1],FILTER_VALIDATE_BOOLEAN,FILTER_NULL_ON_FAILURE);$state[$key]=$bool===true?'true':($bool===false?'false':'other');
        }
        return $state;
    }

    /** New bytes: a false key line becomes KEY=true in place, an absent key is appended; nothing else moves. */
    public static function transform(string $bytes):array
    {
        $state=self::state($bytes);$lines=explode("\n",$bytes);$trailing=str_ends_with($bytes,"\n");if($trailing)array_pop($lines);$changed=[];
        foreach(self::KEYS as $key){
            if($state[$key]==='other')throw new RuntimeException('ENV_KEY_VALUE_NOT_BOOLEAN');if($state[$key]==='true')continue;$changed[]=$key;
            if($state[$key]==='false'){foreach($lines as $i=>$line)if(preg_match('/^'.$key.'=/',$line))$lines[$i]=$key.'=true';continue;}
            if($lines===[''])$lines=[];$lines[]=$key.'=true';
        }
        $new=implode("\n",$lines).($trailing?"\n":'');self::assertOnlyKeysDiffer($bytes,$new);return ['bytes'=>$new,'changed'=>$changed];
    }

    /** Only the two key lines may differ (each exactly KEY=true afterwards); order and every other line are identical. */
    public static function assertOnlyKeysDiffer(string $old,string $new):void
    {
        if(str_ends_with($old,"\n")!==str_ends_with($new,"\n")&&$old!=='')throw new RuntimeException('ENV_TRAILING_NEWLINE_CHANGED');
        $pattern='/^\s*(?:export\s+)?(?:'.implode('|',self::KEYS).')\s*=/';$strip=static function(string $bytes)use($pattern):array{$lines=explode("\n",rtrim($bytes,"\n"));return array_values(array_filter($lines,static fn($l)=>!preg_match($pattern,$l)));};
        $o=$strip($old);$n=$strip($new);if($o===[''])$o=[];if($n===[''])$n=[];
        if($o!==$n)throw new RuntimeException('ENV_OTHER_LINES_CHANGED');if(self::state($new)!==array_fill_keys(self::KEYS,'true'))throw new RuntimeException('ENV_KEYS_NOT_TRUE_AFTER_EDIT');
        foreach(self::KEYS as $key)if(substr_count("\n".$new."\n","\n".$key."=true\n")!==1)throw new RuntimeException('ENV_KEY_LINE_NOT_EXACT');
    }
}

final class AtomicFile
{
    /** Temp name in the same directory as the target; recorded by the caller before creation for crash cleanup. */
    public static function tempFor(string $target):string {return dirname($target).'/.'.basename($target).'.b1145-'.bin2hex(random_bytes(8)).'.tmp';}

    public static function isOwnTemp(string $path):bool {return preg_match('~/\.[A-Za-z0-9_.]+\.b1145-[a-f0-9]{16}\.tmp$~D',$path)===1;}

    /**
     * Writes, syncs and verifies the temp file, keeps the target's mode, gives it an mtime newer than the replaced file,
     * then renames it over the target (one rename in one directory). The parent directory is synced when the kernel allows it.
     */
    public static function replace(string $target,string $bytes,int $mode,string $temp):array
    {
        if(!self::isOwnTemp($temp)||dirname($temp)!==dirname($target)||is_link($target)||!is_file($target))throw new RuntimeException('ATOMIC_REPLACE_SCOPE');
        clearstatcache(true,$target);$before=stat($target);$h=@fopen($temp,'x');if($h===false)throw new RuntimeException('ATOMIC_TEMP_CREATE');$ok=false;
        try{
            if(!chmod($temp,$mode)||fwrite($h,$bytes)!==strlen($bytes)||!fflush($h)||(function_exists('fsync')&&!fsync($h)))throw new RuntimeException('ATOMIC_TEMP_WRITE');$ok=true;
        }finally{fclose($h);if(!$ok)@unlink($temp);}
        clearstatcache(true,$temp);if(is_link($temp)||hash_file('sha256',$temp)!==hash('sha256',$bytes)||(fileperms($temp)&0777)!==$mode){@unlink($temp);throw new RuntimeException('ATOMIC_TEMP_VERIFY');}
        if(filemtime($temp)<=$before['mtime']&&!touch($temp,$before['mtime']+1)){@unlink($temp);throw new RuntimeException('ATOMIC_TEMP_MTIME');}
        if(!@rename($temp,$target)){@unlink($temp);throw new RuntimeException('ATOMIC_RENAME');}
        $synced=false;$d=@fopen(dirname($target),'r');if($d!==false){$synced=function_exists('fsync')&&@fsync($d);fclose($d);}
        clearstatcache(true,$target);$after=stat($target);if(is_link($target)||hash_file('sha256',$target)!==hash('sha256',$bytes)||($after['mode']&0777)!==$mode||$after['mtime']<=$before['mtime'])throw new RuntimeException('ATOMIC_RESULT_VERIFY');
        return ['directorySynced'=>$synced,'newInode'=>$after['ino']!==$before['ino'],'mtimeAdvanced'=>true];
    }

    /** Private 0600 copy in the 0700 state directory, synced; returns its SHA-256. */
    public static function privateCopy(string $path,string $bytes):string
    {
        $directory=dirname($path);if(is_link($directory)||(fileperms($directory)&0077)!==0)throw new RuntimeException('PRIVATE_COPY_SCOPE');
        $h=@fopen($path,'x');if($h===false)throw new RuntimeException('PRIVATE_COPY_CREATE');$ok=false;
        try{if(!chmod($path,0600)||fwrite($h,$bytes)!==strlen($bytes)||!fflush($h)||(function_exists('fsync')&&!fsync($h)))throw new RuntimeException('PRIVATE_COPY_WRITE');$ok=true;}finally{fclose($h);if(!$ok)@unlink($path);}
        if(hash_file('sha256',$path)!==hash('sha256',$bytes))throw new RuntimeException('PRIVATE_COPY_VERIFY');return hash('sha256',$bytes);
    }

    public static function readPrivate(string $path,string $sha256):string
    {
        if(is_link($path)||!is_file($path)||(fileperms($path)&0777)!==0600||fileowner($path)!==posix_geteuid())throw new RuntimeException('PRIVATE_COPY_MISSING');$bytes=file_get_contents($path);
        if(!is_string($bytes)||hash('sha256',$bytes)!==$sha256)throw new RuntimeException('PRIVATE_COPY_CHANGED');return $bytes;
    }
}
