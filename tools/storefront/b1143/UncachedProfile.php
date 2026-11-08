<?php
declare(strict_types=1);

/** One CMS-directory overlay, with exact original bytes retained until terminal recovery. */
final class UncachedProfile
{
    public const BLOCK="\n# ALD1N B1143 CMS-only uncached profile\n<IfModule LiteSpeed>\nphp_flag opcache.enable Off\n</IfModule>\n";
    private static function checked(string $path):array
    {
        clearstatcache(true,$path);$s=lstat($path);if(!is_array($s)||!is_file($path)||is_link($path)||realpath($path)!==$path||$s['uid']!==posix_geteuid()||$s['nlink']!==1||$s['size']>65536)throw new RuntimeException('UNCACHED_CONFIG_IDENTITY');return $s;
    }
    private static function create(string $path,string $bytes,int $mode):void
    {
        $h=fopen($path,'x');if($h===false)throw new RuntimeException('UNCACHED_CREATE');$owned=fstat($h);$ok=false;try{if(!chmod($path,$mode)||fwrite($h,$bytes)!==strlen($bytes)||!fflush($h)||(function_exists('fsync')&&!fsync($h)))throw new RuntimeException('UNCACHED_DURABLE_WRITE');if(!fclose($h))throw new RuntimeException('UNCACHED_CLOSE');$h=null;$s=self::checked($path);if(($s['mode']&0777)!==$mode)throw new RuntimeException('UNCACHED_MODE');$ok=true;}finally{if(is_resource($h))fclose($h);if(!$ok){$s=@lstat($path);if(is_array($s)&&$s['dev']===$owned['dev']&&$s['ino']===$owned['ino']&&!is_link($path))unlink($path);}}
    }
    private static function replace(string $path,string $expected,string $bytes,int $mode):void
    {
        $original=self::checked($path);if(hash_file('sha256',$path)!==$expected)throw new RuntimeException('UNCACHED_CONFIG_DRIFT');$tmp=$path.'.b1143-'.bin2hex(random_bytes(8));$owned=null;try{self::create($tmp,$bytes,$mode);$owned=lstat($tmp);$now=self::checked($path);if($now['dev']!==$original['dev']||$now['ino']!==$original['ino']||hash_file('sha256',$path)!==$expected||!rename($tmp,$path))throw new RuntimeException('UNCACHED_CONFIG_RENAME');}finally{if($owned!==null){$s=@lstat($tmp);if(is_array($s)&&$s['dev']===$owned['dev']&&$s['ino']===$owned['ino']&&!is_link($tmp))unlink($tmp);}}
    }
    /** Input is the bounded private DomainInfo response, never printed or archived in an operation report. */
    public static function topology(array $response,string $public):array
    {
        $r=$response['result']??null;if(!is_array($r)||($r['status']??null)!==1||!empty($r['errors'])||!empty($r['warnings'])||!is_array($r['data']??null)||!array_is_list($r['data']))throw new RuntimeException('CMS_DOCROOT_SCOPE_UNAVAILABLE');$rows=$r['data'];$approved=[];
        foreach($rows as $row)if(is_array($row)&&($row['domain']??null)==='cms.ald1n.com'){foreach(preg_split('/\s+/',trim((string)($row['serveralias']??''))) as $alias)if(preg_match('/^[a-z0-9.-]+$/D',$alias))$approved[$alias]=true;}
        if(count($rows)>256)throw new RuntimeException('CMS_DOCROOT_SCOPE_BOUND');$found=false;$outside=0;$aliases=0;
        foreach($rows as $row){if(!is_array($row)||!is_string($row['domain']??null)||!is_string($row['documentroot']??null))throw new RuntimeException('CMS_DOCROOT_SCOPE_INCOMPLETE');$p=realpath($row['documentroot']);if(!is_string($p)||!is_dir($p))throw new RuntimeException('CMS_DOCROOT_SCOPE_UNRESOLVED');if($row['domain']==='cms.ald1n.com'){if($p!==$public)throw new RuntimeException('CMS_DOCROOT_MISMATCH');$found=true;}elseif($p===$public&&isset($approved[$row['domain']])){$aliases++;}elseif($p===$public||str_starts_with($p,$public.'/'))throw new RuntimeException('CMS_DOCROOT_SCOPE_INTERSECTION');else $outside++;}
        if(!$found)throw new RuntimeException('CMS_DOCROOT_MISSING');return ['cmsDocrootBound'=>true,'sameApplicationAliases'=>$aliases,'listedDocrootsOutsideScope'=>$outside,'universalAliasIsolationProven'=>false];
    }
    public static function plan(string $active,string $candidate,string $private,string $expected,string $id):array
    {
        $entries=[];foreach(['old'=>$active,'candidate'=>$candidate] as $label=>$base){$path=$base.'/public/.htaccess';$s=self::checked($path);$bytes=file_get_contents($path);if(strlen($bytes)+strlen(self::BLOCK)>65536||hash('sha256',$bytes)!==$expected||str_contains($bytes,'ALD1N B1143'))throw new RuntimeException('UNCACHED_CONFIG_ADMISSION');$archive=$private.'/original-'.$label.'.htaccess';self::create($archive,$bytes,0600);$ds=stat($base.'/public');$entries[$label]=['archive'=>$archive,'originalSHA256'=>$expected,'overlaySHA256'=>hash('sha256',$bytes.self::BLOCK),'mode'=>$s['mode']&0777,'publicIdentity'=>['dev'=>$ds['dev'],'ino'=>$ds['ino']]];}
        return ['mode'=>'UNCACHED_CMS','id'=>$id,'entries'=>$entries,'profileSHA256'=>hash('sha256',json_encode([$id,$entries],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES))];
    }
    private static function entry(array $profile,string $label,string $base):array
    {
        $e=$profile['entries'][$label]??null;$s=@stat($base.'/public');if(!is_array($e)||!is_array($s)||is_link($base.'/public')||realpath($base.'/public')!==$base.'/public'||['dev'=>$s['dev'],'ino'=>$s['ino']]!==$e['publicIdentity'])throw new RuntimeException('UNCACHED_DIRECTORY_DRIFT');$a=self::checked($e['archive']);if(($a['mode']&0777)!==0600||hash_file('sha256',$e['archive'])!==$e['originalSHA256'])throw new RuntimeException('UNCACHED_ARCHIVE_DRIFT');return $e;
    }
    public static function apply(array $profile,string $label,string $base):void
    {
        $e=self::entry($profile,$label,$base);$path=$base.'/public/.htaccess';self::checked($path);$sha=hash_file('sha256',$path);if($sha===$e['overlaySHA256'])return;if($sha!==$e['originalSHA256'])throw new RuntimeException('UNCACHED_CONFIG_DRIFT');self::replace($path,$sha,file_get_contents($e['archive']).self::BLOCK,$e['mode']);
    }
    public static function assertApplied(array $profile,string $label,string $base):void
    {
        $e=self::entry($profile,$label,$base);$path=$base.'/public/.htaccess';self::checked($path);if(hash_file('sha256',$path)!==$e['overlaySHA256'])throw new RuntimeException('UNCACHED_PROFILE_NOT_APPLIED');
    }
    public static function restore(array $profile,string $label,string $base):void
    {
        $e=self::entry($profile,$label,$base);$path=$base.'/public/.htaccess';self::checked($path);$sha=hash_file('sha256',$path);if($sha===$e['originalSHA256'])return;if($sha!==$e['overlaySHA256'])throw new RuntimeException('UNCACHED_CONFIG_DRIFT');self::replace($path,$sha,file_get_contents($e['archive']),$e['mode']);
    }
    public static function witness(string $incoming,string $id,string $literal):array
    {
        if(!preg_match('/^[a-f0-9]{32}$/D',$id)||!in_array($literal,['A','B'],true)||realpath($incoming)!==$incoming)throw new RuntimeException('UNCACHED_WITNESS_SCOPE');$dep=$incoming.'/b1143-witness-'.$id.'-dependency.php';$main=$incoming.'/b1143-witness-'.$id.'.php';$codes=[$dep=>'<?php return '.var_export($literal,true).';',$main=>'<?php return ['.var_export($literal,true).',require __DIR__.'.var_export('/'.basename($dep),true).'];'];
        foreach($codes as $path=>$code){$old='<?php return '.var_export('A',true).';';if($literal==='A'){if(file_exists($path)||is_link($path))throw new RuntimeException('UNCACHED_WITNESS_COLLISION');self::create($path,$code,0600);}else{self::checked($path);$prior=$path===$dep?$old:'<?php return [\'A\',require __DIR__.'.var_export('/'.basename($dep),true).'];';self::replace($path,hash('sha256',$prior),$code,0600);}}
        return ['path'=>$main,'sha256'=>hash_file('sha256',$main),'dependency'=>$dep,'dependencySHA256'=>hash_file('sha256',$dep),'literal'=>$literal];
    }
}
