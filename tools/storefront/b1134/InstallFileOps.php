<?php
declare(strict_types=1);

final class InstallFileOps
{
    public static function relative(string $path):void
    {
        if($path===''||str_starts_with($path,'/')||str_contains($path,'\\')||preg_match('~(^|/)\.{1,2}(/|$)|[\x00-\x1f\x7f]~',$path))throw new RuntimeException('UNSAFE_RELATIVE_PATH');
    }

    public static function copyRegular(string $source,string $target,?string $sha=null,?int $size=null,?int $mode=null):void
    {
        if(is_link($source)||!is_file($source)||realpath($source)!==$source||is_link($target)||file_exists($target))throw new RuntimeException('COPY_REGULAR_CONFINEMENT');
        if(filesize($source)>33554432||($size!==null&&filesize($source)!==$size)||($sha!==null&&hash_file('sha256',$source)!==$sha))throw new RuntimeException('COPY_SOURCE_INTEGRITY');
        $parent=dirname($target);if(!is_dir($parent)&&!mkdir($parent,0755,true))throw new RuntimeException('COPY_PARENT');if(realpath($parent)!==$parent)throw new RuntimeException('COPY_PARENT_LINK');
        $input=fopen($source,'rb');$output=fopen($target,'x');if($input===false||$output===false)throw new RuntimeException('COPY_OPEN');$written=stream_copy_to_stream($input,$output);fflush($output);fclose($input);fclose($output);chmod($target,$mode??(fileperms($source)&0777));
        if($written!==filesize($source)||hash_file('sha256',$target)!==hash_file('sha256',$source))throw new RuntimeException('COPY_DESTINATION_INTEGRITY');
    }

    public static function candidate(string $stage,string $candidate,array $manifest,array $directories):void
    {
        if(realpath($stage)!==$stage||file_exists($candidate)||is_link($candidate)||($manifest['sourceCommit']??'')!=='ef3f927b77ba573881c2b6dba341732c417e6725'||count($manifest['files'])!==8531)throw new RuntimeException('CANDIDATE_SOURCE');
        if(!mkdir($candidate,0755)||!chmod($candidate,0755))throw new RuntimeException('CANDIDATE_CREATE');
        foreach($directories as $directory){self::relative($directory);if(!is_dir($candidate.'/'.$directory)&&!mkdir($candidate.'/'.$directory,0755,true))throw new RuntimeException('CANDIDATE_DIRECTORY');chmod($candidate.'/'.$directory,0755);}
        foreach($manifest['files'] as $file){self::relative($file['path']);$source=$stage.'/'.$file['path'];self::copyRegular($source,$candidate.'/'.$file['path'],$file['sha256'],$file['size'],(fileperms($source)&0111)?0755:0644);}
        if(!mkdir($candidate.'/bootstrap/cache',0755,true)&&!is_dir($candidate.'/bootstrap/cache'))throw new RuntimeException('FRESH_BOOTSTRAP_CACHE');chmod($candidate.'/bootstrap/cache',0755);
    }

    /** Keep existing non-runtime source paths as real files; never carry old app/vendor runtime over new source. */
    public static function nonRuntime(string $active,string $candidate,string $retained,array $tracked):array
    {
        $runtime=['app','bootstrap','config','database','resources','routes','public','bin','vendor','storage'];$copied=[];$linked=[];
        foreach(new DirectoryIterator($active) as $item){if($item->isDot())continue;$name=$item->getFilename();if(in_array($name,$runtime,true)||$name==='.env'||file_exists($candidate.'/'.$name)||is_link($candidate.'/'.$name))continue;self::relative($name);
            $source=$active.'/'.$name;$target=$candidate.'/'.$name;
            if($item->isLink())throw new RuntimeException('NONRUNTIME_EXISTING_LINK_UNSUPPORTED');
            if($item->isFile()){self::copyRegular($source,$target);$copied[]=$name;continue;}
            if(!$item->isDir())throw new RuntimeException('NONRUNTIME_SPECIAL_FILE');
            $hasTracked=false;foreach($tracked as $path)if(str_starts_with($path,$name.'/')){$hasTracked=true;break;}
            if($hasTracked||in_array($name,['docs','tests'],true)){
                if(!mkdir($target,0755))throw new RuntimeException('NONRUNTIME_CREATE');$count=0;
                foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST) as $entry){$relative=substr($entry->getPathname(),strlen($active)+1);self::relative($relative);if(++$count>20000||$entry->isLink())throw new RuntimeException('NONRUNTIME_TREE_BOUND');$destination=$candidate.'/'.$relative;if($entry->isDir()){if(!mkdir($destination,0755)&&!is_dir($destination))throw new RuntimeException('NONRUNTIME_DIRECTORY');}elseif($entry->isFile())self::copyRegular($entry->getPathname(),$destination);else throw new RuntimeException('NONRUNTIME_TREE_SPECIAL');}$copied[]=$name.'/';
            }else{if(!symlink($retained.'/'.$name,$target))throw new RuntimeException('NONRUNTIME_PRIVATE_LINK');$linked[]=$name.'/';}
        }
        return ['existingNonRuntimeCopied'=>$copied,'privateNonRuntimeLinked'=>$linked,'oldRuntimeNotOverlaid'=>true];
    }

    public static function publicExtras(string $active,string $candidate,array $manifest):array
    {
        $expected=array_fill_keys(array_column($manifest['files'],'path'),true);$private=[];foreach(['storage','uploads','attachments'] as $name)if(file_exists($active.'/public/'.$name)||is_link($active.'/public/'.$name))$private[]='public/'.$name;
        $count=0;$copied=0;$iterator=new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(new RecursiveDirectoryIterator($active.'/public',FilesystemIterator::SKIP_DOTS),static function($entry)use($active,$private){$relative=substr($entry->getPathname(),strlen($active)+1);return !in_array($relative,$private,true);}),RecursiveIteratorIterator::SELF_FIRST);
        foreach($iterator as $item){$relative=substr($item->getPathname(),strlen($active)+1);self::relative($relative);if(++$count>20000||$item->isLink())throw new RuntimeException('PUBLIC_TREE_CONFINEMENT');if($item->isDir())continue;if(!$item->isFile())throw new RuntimeException('PUBLIC_SPECIAL');
            if(str_ends_with(strtolower($relative),'.php')&&$relative!=='public/index.php')throw new RuntimeException('UNSUPPORTED_PUBLIC_PHP_ENTRYPOINT');
            if(isset($expected[$relative])||$relative==='public/.htaccess')continue;
            if($relative==='public/.user.ini'){
                $text=file_get_contents($item->getPathname());if(strlen($text)>65536||preg_match('/auto_prepend|auto_append|opcache\.preload|APP_CONFIG_CACHE|APP_BASE_PATH/i',$text))throw new RuntimeException('UNSUPPORTED_PER_DIRECTORY_PHP_OVERRIDE');
            }elseif(!preg_match('/\.(?:png|jpe?g|gif|svg|webp|ico|css|js|map|txt|xml|json|woff2?|ttf|eot|pdf)$/i',$relative))throw new RuntimeException('UNCLASSIFIED_PUBLIC_EXTRA');
            self::copyRegular($item->getPathname(),$candidate.'/'.$relative);$copied++;
        }
        return ['privatePaths'=>$private,'publicExtrasCopied'=>$copied];
    }

    public static function phpCensus(string $root):array
    {
        $files=[];$bytes=0;$directories=['app','bootstrap','config','database','resources','routes','bin','vendor','storage/framework/views'];
        foreach($directories as $relative){$base=$root.'/'.$relative;if(!is_dir($base))continue;if(is_link($base)||realpath($base)!==$base)throw new RuntimeException('PHP_CENSUS_DIRECTORY_LINK');
            foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS)) as $item){if($item->isLink())throw new RuntimeException('PHP_CENSUS_LINK');if(!$item->isFile()||!str_ends_with($item->getFilename(),'.php'))continue;$path=$item->getPathname();if(realpath($path)!==$path||$item->getSize()>33554432)throw new RuntimeException('PHP_CENSUS_FILE');$name=substr($path,strlen($root)+1);self::relative($name);$files[$name]=hash_file('sha256',$path);$bytes+=$item->getSize();if(count($files)>20000||$bytes>268435456)throw new RuntimeException('PHP_CENSUS_BOUND');}
        }
        foreach(['public/index.php','storage/framework/maintenance.php'] as $relative){$file=$root.'/'.$relative;if(is_file($file)&&!is_link($file)&&realpath($file)===$file)$files[$relative]=hash_file('sha256',$file);}
        ksort($files);return $files;
    }
}
