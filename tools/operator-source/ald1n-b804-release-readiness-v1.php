<?php
declare(strict_types=1);
/** Read-only B804 prerequisite check. No source patch, migration, restore or prune. */
function b804Blob(string $path): string {
    $bytes=file_get_contents($path);
    if ($bytes===false) throw new RuntimeException('Unreadable source');
    return sha1('blob '.strlen($bytes)."\0".$bytes);
}
function b804SafeRelative(string $path): bool {
    return $path!=='' && !str_starts_with($path,'/') && !str_contains($path,'\\')
        && !preg_match('/[\x00-\x20\x7f]/',$path)
        && !in_array('..',explode('/',$path),true)
        && !in_array('.',explode('/',$path),true);
}
function b804PathState(string $root,array $row): string {
    $path=(string)($row['path']??'');
    if (!b804SafeRelative($path)) return 'UNSAFE';
    $cursor=rtrim($root,'/');
    foreach(explode('/',$path) as $part) {
        $cursor.='/'.$part;
        if(is_link($cursor)) return 'SYMLINK';
    }
    if (!file_exists($cursor)) return ($row['predecessor_blob']??null)===null?'ABSENT_NEW':'MISSING_OLD';
    if (!is_file($cursor)) return 'NOT_FILE';
    $hash=b804Blob($cursor);
    if (hash_equals((string)($row['candidate_blob']??''),$hash)) return 'TARGET';
    if (isset($row['predecessor_blob']) && hash_equals((string)$row['predecessor_blob'],$hash)) return 'BASELINE';
    return 'DRIFT';
}
function b804Age(string $date, int $now): ?int {
    if(trim($date)==='')return null;
    try{return $now-(new DateTimeImmutable($date))->getTimestamp();}catch(Throwable){return null;}
}
if(in_array('--self-test',$argv,true)) {
    $dir=sys_get_temp_dir().'/ald1n-b804-fixture-'.bin2hex(random_bytes(6));
    if(!mkdir($dir,0700))exit(70);
    $checks=[];
    try {
        file_put_contents($dir.'/item.php','baseline');
        $row=['path'=>'item.php','predecessor_blob'=>b804Blob($dir.'/item.php'),'candidate_blob'=>str_repeat('f',40)];
        $checks[]=b804PathState($dir,$row)==='BASELINE';
        $row['candidate_blob']=$row['predecessor_blob'];$checks[]=b804PathState($dir,$row)==='TARGET';
        file_put_contents($dir.'/item.php','changed');$checks[]=b804PathState($dir,$row)==='DRIFT';
        $checks[]=b804PathState($dir,['path'=>'new.php','predecessor_blob'=>null])==='ABSENT_NEW';
        $checks[]=b804PathState($dir,['path'=>'old.php','predecessor_blob'=>str_repeat('a',40)])==='MISSING_OLD';
        $checks[]=b804PathState($dir,['path'=>'../outside'])==='UNSAFE';
        $checks[]=!b804SafeRelative('/absolute') && !b804SafeRelative('nested/../parent');
        symlink($dir.'/item.php',$dir.'/link.php');$checks[]=b804PathState($dir,['path'=>'link.php'])==='SYMLINK';
        $checks[]=b804Age('',100)===null && b804Age('invalid timestamp',100)===null;
        $checks[]=b804Age('1970-01-01T00:00:10Z',100)===90;
        echo 'B804_PREFLIGHT_FIXTURE_CHECKS='.count($checks).' FAILURES='.count(array_filter($checks,fn($x)=>!$x))."\n";
    }finally{
        if(is_link($dir.'/link.php'))unlink($dir.'/link.php');
        if(is_file($dir.'/item.php'))unlink($dir.'/item.php');
        rmdir($dir);
    }
    exit(in_array(false,$checks,true)?71:0);
}
$root='/home/icaffeco/ald1n-project';
if(realpath(__FILE__)!==$root.'/incoming/ald1n-b804-release-readiness-v1.php') {
    echo "HOLD_CANONICAL_HELPER_PATH\n";exit(30);
}
$cms=$root.'/apps/cms/current';
$manifestPath=$root.'/incoming/b804-runtime-manifest.json';
if(!is_file($manifestPath) || is_link($manifestPath)
    || !hash_equals('2d25f76fcf59235218458293aedc127dbadf8212e21fb45a5d2fdeaf95726108',hash_file('sha256',$manifestPath))) {
    echo "HOLD_MANIFEST_INTEGRITY\n";exit(31);
}
try {
    $manifest=json_decode((string)file_get_contents($manifestPath),true,512,JSON_THROW_ON_ERROR);
    if(($manifest['head']??'')!=='d4e9e2ba506e82d2f4b5ec639c6ce6b39748f34d'
        || count($manifest['files']??[])!==44)throw new RuntimeException('Manifest authority drift');
    $counts=[];$before=[];$drift=false;
    foreach($manifest['files'] as $row){
        $state=b804PathState($cms,$row);$counts[$state]=($counts[$state]??0)+1;
        if(!in_array($state,['BASELINE','ABSENT_NEW'],true)){
            echo 'RUNTIME_PATH_HOLD='.$row['path'].' STATE='.$state."\n";$drift=true;
        }
        $p=$cms.'/'.$row['path'];$before[$row['path']]=is_file($p)&&!is_link($p)?b804Blob($p):null;
    }
    ksort($counts);echo 'AFFECTED_RUNTIME_FILES=44 STATES='.json_encode($counts)."\n";
    if($drift){echo "RUNTIME_COMPATIBILITY=HOLD\n";exit(32);}
    echo "RUNTIME_COMPATIBILITY=PASS_AFFECTED_PATHS_ONLY\n";
    foreach($manifest['backup_helpers'] as $path=>$blob){
        if(!is_file($cms.'/'.$path) || is_link($cms.'/'.$path) || !hash_equals($blob,b804Blob($cms.'/'.$path))){
            echo "BACKUP_GATE=HOLD_READER_SOURCE_DRIFT\n";exit(33);
        }
    }
    require $cms.'/vendor/autoload.php';
    $app=require $cms.'/bootstrap/app.php';
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $db=\Illuminate\Support\Facades\DB::connection();
    if($db->getDriverName()!=='mysql')throw new RuntimeException('Unexpected DB driver');
    $db->statement('SET SESSION TRANSACTION READ ONLY');
    $version=(string)$db->selectOne('SELECT VERSION() AS version')->version;
    if(!str_starts_with($version,'10.11.19-MariaDB'))throw new RuntimeException('Unverified database family');
    echo 'HOST_PHP='.PHP_VERSION.' MARIADB_FAMILY=10.11.19'."\n";
    if(!\Illuminate\Support\Facades\Schema::hasTable('backup_runs'))throw new RuntimeException('Missing backup authority');
    // Require an existing root before the first-party reader can call ensureDirectoryExists.
    $configured=(string)config('backup.path');$base=realpath($configured);
    if(!$base || !is_dir($base) || is_link($configured))throw new RuntimeException('Missing or indirect backup root');
    $runs=\App\Models\BackupRun::query()->where('status','completed')
        ->orderByDesc('started_at')->orderByDesc('id')->limit(2)->get();
    $valid=[];$ages=[];
    foreach($runs as $run){
        $id=(int)$run->id;$dir=realpath((string)$run->backup_path);
        if(!$dir || is_link((string)$run->backup_path) || !str_starts_with($dir.'/',$base.'/')
            || isset($valid[$dir]) || !is_file($dir.'/manifest.json') || is_link($dir.'/manifest.json')){
            echo 'BACKUP_RUN_'.$id."=HOLD_PATH\n";continue;
        }
        $manifestBefore=hash_file('sha256',$dir.'/manifest.json');
        $m=json_decode((string)file_get_contents($dir.'/manifest.json'),true,512,JSON_THROW_ON_ERROR);
        $age=b804Age((string)($m['created_at']??''),time());
        $full=!empty($m['files']) && (($run->metadata_json['database_only']??false)!==true);
        $sameVersion=(string)($m['version']??'')===(string)config('app.version');
        $safeFiles=true;
        foreach((array)($m['files']??[]) as $record){
            $rel=is_array($record)?(string)($record['target']??''):'';
            if(!b804SafeRelative($rel)){$safeFiles=false;break;}
            $p=$dir;foreach(explode('/',$rel) as $part){$p.='/'.$part;if(is_link($p)){$safeFiles=false;break 2;}}
        }
        $dbName=(string)($m['database']??'');
        if($dbName==='' || basename($dbName)!==$dbName || is_link($dir.'/'.$dbName))$safeFiles=false;
        if(!$safeFiles){echo 'BACKUP_RUN_'.$id."=HOLD_SYMLINK_OR_MANIFEST_PATH\n";continue;}
        $rc=\Illuminate\Support\Facades\Artisan::call('app:backup-verify',['--run'=>(string)$id]);
        // Consume buffered details without disclosing filenames, SQL or credentials.
        \Illuminate\Support\Facades\Artisan::output();
        clearstatcache();
        $unchanged=is_file($dir.'/manifest.json') && hash_equals($manifestBefore,hash_file('sha256',$dir.'/manifest.json'));
        echo 'BACKUP_RUN_'.$id.' VERIFY_RC='.$rc.' AGE_SECONDS='.($age??'UNKNOWN')
            .' FULL='.($full?'YES':'NO').' VERSION_MATCH='.($sameVersion?'YES':'NO')
            .' MANIFEST_UNCHANGED='.($unchanged?'YES':'NO')."\n";
        if($rc===0 && $full && $sameVersion && $unchanged && $age!==null && $age>=-300){$valid[$dir]=$id;$ages[]=$age;}
    }
    sort($ages,SORT_NUMERIC);
    $two=count($valid)===2;
    // Stated preparation window for a daily pair; actual release must recheck.
    $fresh=$two && $ages[0]<=86400 && $ages[1]<=172800;
    echo 'BACKUP_TWO_LOGICALLY_VERIFIED='.($two?'PASS':'HOLD')."\n";
    echo 'BACKUP_PREPARATION_24H_48H_WINDOW='.($fresh?'PASS':'HOLD')."\n";
    $unchanged=true;
    foreach($before as $path=>$blob){
        $p=$cms.'/'.$path;$actual=is_file($p)&&!is_link($p)?b804Blob($p):null;
        if($actual!==$blob){$unchanged=false;echo 'RUNTIME_CHANGED_DURING_READ='.$path."\n";}
    }
    echo 'RUNTIME_READ_PRESERVATION='.($unchanged?'PASS':'HOLD')."\n";
    echo "RESTORE_DRILL=NOT_RUN\nBACKUP_CREATION_PRUNE=NONE\n";
    echo "FPM_OPCACHE_CUSTOMER_UAT=NOT_ATTESTED\n";
    echo "FINAL_INODE_CLEANUP=DEFERRED_ISSUE95_NO_DELETION\n";
    exit($two&&$fresh&&$unchanged?0:35);
}catch(Throwable $e){
    echo 'B804_PREREQUISITES=HOLD_EXCEPTION TYPE='.get_class($e)."\n";exit(36);
}
