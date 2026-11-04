<?php
declare(strict_types=1);
function need(bool $yes,string $why):void { if(!$yes){fwrite(STDERR,"HOLD: $why\n");exit(2);} }
$mode=$argv[1]??'';
if($mode==='sequence'){
    $highest=0;
    foreach(file($argv[2],FILE_IGNORE_NEW_LINES)?:[] as $path){if(preg_match('/^(\d+)-/',basename($path),$m))$highest=max($highest,(int)$m[1]);}
    foreach(new DirectoryIterator($argv[3]) as $item){if($item->isFile()&&preg_match('/^(\d+)-/',$item->getFilename(),$m))$highest=max($highest,(int)$m[1]);}
    need($highest>0,'report sequence unavailable');echo $highest+1;exit;
}
need(in_array($mode,['pre','post'],true),'unknown helper mode');
$payload=$argv[2];$stage=$argv[3];
$m=json_decode(file_get_contents($payload.'/runtime-manifest.json'),true,512,JSON_THROW_ON_ERROR);
need(($m['sourceCommit']??'')==='ef3f927b77ba573881c2b6dba341732c417e6725','wrong source');
need(($m['schemaVersion']??0)===1&&($m['deploymentAuthorized']??true)===false,'manifest policy');
need(count($m['files'])===$m['totalFiles']&&$m['totalFiles']>0&&$m['totalFiles']<=30000,'file count');
$expected=[];$bytes=0;
foreach($m['files'] as $entry){
    $p=$entry['path'];need(is_string($p)&&$p!==''&&!str_starts_with($p,'/')&&!str_contains($p,'\\')&&!preg_match('/[\x00-\x1f\x7f]/',$p),'unsafe path');
    foreach(explode('/',$p) as $part)need(!in_array($part,['','..','.','.git','node_modules','storage','tests','test','fixtures','__fixtures__'],true)&&!str_starts_with($part,'.env'),'forbidden path');
    need(!isset($expected[$p])&&preg_match('/^[a-f0-9]{64}$/',$entry['sha256'])===1,'duplicate or hash');
    need(is_int($entry['size'])&&$entry['size']>=0&&$entry['size']<=16777216,'file size');$bytes+=$entry['size'];$expected[$p]=$entry;
}
need($bytes===$m['totalBytes']&&$bytes<=201326592,'total size');
if($mode==='pre'){
    foreach(file($payload.'/verbose.txt',FILE_IGNORE_NEW_LINES)?:[] as $line)need($line!==''&&in_array($line[0],['-','d'],true),'nonregular tar member');
    $seen=[];
    foreach(file($payload.'/names.txt',FILE_IGNORE_NEW_LINES)?:[] as $name){
        need(!isset($seen[$name]),'duplicate tar member');$seen[$name]=true;$rel=rtrim($name,'/');
        need($rel!==''&&!str_starts_with($rel,'/')&&!str_contains($rel,'\\')&&!preg_match('/[\x00-\x1f\x7f]/',$rel),'unsafe tar path');
        foreach(explode('/',$rel) as $part)need(!in_array($part,['','..','.'],true),'tar traversal');
        if(!str_ends_with($name,'/'))need(isset($expected[$name]),'unlisted tar file');
        else { $prefix=$rel.'/';need(count(array_filter(array_keys($expected),static fn($file)=>str_starts_with($file,$prefix)))>0,'unlisted tar directory'); }
    }
    foreach($expected as $p=>$entry)need(isset($seen[$p]),'missing tar file');echo "TAR_ALLOWLIST_PASS\n";exit;
}
$actual=[];
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($stage,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::SELF_FIRST) as $item){
    need(!$item->isLink()&&($item->isDir()||$item->isFile()),'stage link or special file');if($item->isDir())continue;
    $p=substr($item->getPathname(),strlen($stage)+1);need(isset($expected[$p]),'unexpected staged file');$entry=$expected[$p];
    need($item->getSize()===$entry['size']&&hash_file('sha256',$item->getPathname())===$entry['sha256'],'staged hash mismatch');$actual[$p]=true;
}
need(count($actual)===count($expected),'staged file census');
need(hash_file('sha256',$stage.'/composer.lock')===$m['composerLockSHA256'],'lock mismatch');
echo 'STAGED_FILES='.count($actual).' STAGED_BYTES='.$bytes."\n";
