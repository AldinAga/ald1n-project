<?php
declare(strict_types=1);

/**
 * B1145 controlled migrate and unclamp. The Storefront stays CLOSED: the DB switch public_storefront_open is never written.
 * Order: PREFLIGHT (pure, collect-all; any blocker is a HOLD with nothing changed) -> admission of the B1144 terminal PASS
 * into a private namespace -> (A) app:backup-create + app:backup-verify -> (B) migrate --pretend gate + migrate --force for
 * exactly the three pending migrations, then read-only schema verification -> second preflight before any file change ->
 * (C) private 0600 copies, clamp replaced by the staged original bytes (one same-directory rename), .env edited THROUGH its
 * symlink (rename over the REAL target) -> targeted invalidation of only config/public_storefront.php, wait, config:show,
 * closed HTTP smokes, Laravel log scan. Any failure after step C starts restores the clamp and the .env automatically and
 * re-verifies. A migration failure gets no automatic rollback: the clamp stays and a recovery note is printed.
 * Every phase is saved before the next step; a re-run converges from the saved phase.
 * Every run that will execute migrate --force takes its OWN fresh backup; a backup is reused only inside the same process,
 * after a re-check of its row, size and directory, a second app:backup-verify and an age bound, immediately before migrate.
 * The two Storefront tables are checked for every column, unique key, index and foreign key the migrations define, so a
 * table left with columns but without constraints (a lock timeout between Schema::create statements) is a HOLD.
 * @author Ald1n <https://ald1n.com>
 */
final class MigrateUnclampCoordinator
{
    public const ORIGINAL_SHA='0cfbc864a3d44822a9d14e80f6252e84eb208c9da5a25d5fe6989e4cad789818';
    public const MANIFEST_SHA='d737387e60a981b3b404e0980d9b70cb95b6e1d506a39211981d49dcd12be4c6';
    public const REPORT='docs/operations/1106-B1144-CONTROLLED-OFF-INSTALL-20261108-101421.md';
    public const REPORT_SHA='0ac995257de5e18d61b9e0ffcd207af616517cbc5e5295bc28954d3438e433f7';
    public const B1144_HELPER='db3ba00ff9b954ebb6e80b6ee23a7321baffee0a';
    public const PENDING=['2026_10_23_020000_create_public_order_accesses','2026_10_23_020100_create_public_checkout_attempts','2026_10_24_030000_add_retail_customer_role'];
    public const RAN_BEFORE=68;
    public const MANIFEST_FILES=8531;
    public const SMOKES=[['/katalog',404],['/registracija',404],['/korpa',404],['/porucivanje',404],['/login',200],['/up',200]];
    public const TOGGLE_COLUMNS=['merchant_policy_digest','delivery_method','customer_user_id','payment_due_at_snapshot'];
    /** Known first-migration introspection stop under --pretend (pretend returns no rows, so hasTable('orders') is false). */
    public const PRETEND_INTROSPECTION_CODE='PUBLIC_ORDER_ACCESS_REQUIRES_EXISTING_ORDERS';
    /**
     * The exact --pretend stop of each migration when it is the FIRST pending one: pretend returns no rows, so every
     * hasTable() guard is false and the migration throws its own code. Fresh and resume accept only this exact pair.
     */
    public const PRETEND_KNOWN_STOPS=['2026_10_23_020000_create_public_order_accesses'=>'PUBLIC_ORDER_ACCESS_REQUIRES_EXISTING_ORDERS','2026_10_23_020100_create_public_checkout_attempts'=>'PUBLIC_CHECKOUT_DEPENDENCIES_INCOMPLETE','2026_10_24_030000_add_retail_customer_role'=>'B802_ROLES_TABLE_MISSING'];
    /** Columns, unique keys, plain indexes and foreign keys exactly as the two create migrations (ef3f927) define them. */
    public const EXPECTED_SCHEMA=[
        'public_order_accesses'=>['migration'=>'2026_10_23_020000_create_public_order_accesses','columns'=>['id','order_id','token_hash','email_ciphertext','expires_at','revoked_at','created_at','updated_at'],
            'unique'=>[['id'],['order_id'],['token_hash']],'index'=>[['expires_at','revoked_at']],'foreign'=>[['order_id','orders','id','RESTRICT']]],
        'public_checkout_attempts'=>['migration'=>'2026_10_23_020100_create_public_checkout_attempts','columns'=>['id','key_hash','request_fingerprint','receipt_token_ciphertext','terms_url','privacy_url','shipping_description','shipping_fee_rsd','merchant_policy_digest','delivery_method','customer_user_id','payment_due_at_snapshot','terms_accepted_at','order_id','created_at','updated_at'],
            'unique'=>[['id'],['key_hash'],['order_id']],'index'=>[],'foreign'=>[['order_id','orders','id','RESTRICT']]],
    ];
    private const MIGRATE_PHASES=['PREFLIGHT_PASSED','BACKUP_INTENT','BACKUP_CREATED','HOLD_BACKUP_FAILED','BACKUP_VERIFIED','HOLD_PRETEND_UNEXPECTED','HOLD_SCHEDULED_BACKUP_WINDOW','HOLD_BACKUP_RECHECK_FAILED','HOLD_MIGRATE_CENSUS_BLOCKED','MIGRATE_INTENT','HOLD_MIGRATION_FAILED','HOLD_MIGRATION_VERIFY_FAILED','HOLD_MIGRATE_PREFLIGHT_BLOCKED'];
    private const UNCLAMP_READY=['MIGRATED_VERIFIED','RESTORED_CLAMPED','HOLD_UNCLAMP_PREFLIGHT_BLOCKED'];
    private const IN_FLIGHT=['UNCLAMP_INTENT','CONFIG_UNCLAMPED','ENV_UNCLAMPED','ACCEPTANCE_INTENT','RESTORE_INTENT','RESTORE_INCOMPLETE'];
    private array $state=[];
    private string $base;
    private string $pointer;
    private mixed $lock=null;
    private object $web;
    private string $php=PHP_BINARY;
    private array $allowedSapis=['litespeed'];
    private string $stage;
    private string $reportPath;
    private string $reportSHA=self::REPORT_SHA;
    private string $b1144Base;
    private string $appStateScript;
    private int $censusSeconds=75;
    private int $waitMargin=3;
    private int $minFreeBytes=2147483648;
    /** Scheduled-backup overlap gate in the APP timezone (never the CLI default); $fixedNow is a test clock only. */
    private bool $scheduleGate=true;
    private ?int $fixedNow=null;
    private int $scheduleMarginSeconds=900;
    private int $defaultBackupSeconds=2400;
    private int $backupTimeout=2400;
    private int $verifyTimeout=2400;
    private int $migrateTimeout=900;
    private int $backupMaxAgeSeconds=600;
    private int $probeSamples=2;
    private ?Closure $fault=null;
    private ?array $manifest=null;
    /** The backup THIS process created and verified (never loaded from saved state): id, key, size, monotonic times. */
    private ?array $processBackup=null;
    private ?array $lastChildStop=null;

    public function __construct(private string $root,private string $home)
    {
        if($root!=='/home/icaffeco/ald1n-project'||$home!=='/home/icaffeco'||realpath($root)!==$root||realpath($home)!==$home||!function_exists('posix_geteuid')||!extension_loaded('curl')||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500)throw new RuntimeException('HOST_PROFILE');
        $this->base=$home.'/.ald1n-b1145-state';$this->pointer=$this->base.'/current.json';$this->stage=$home.'/.ald1n-stage/b1131-ef3f927.6hh7mU';$this->reportPath=$root.'/'.self::REPORT;
        $this->b1144Base=$home.'/.ald1n-b1144-state';$this->appStateScript=$root.'/incoming/b1145-app-state.php';$this->web=new LeanWeb();putenv('GIT_OPTIONAL_LOCKS=0');
    }

    private function open():void
    {
        if($this->lock!==null)return;if(!is_dir($this->base)&&!mkdir($this->base,0700))throw new RuntimeException('PRIVATE_STATE_CREATE');
        if(is_link($this->base)||realpath($this->base)!==$this->base||(fileperms($this->base)&0777)!==0700||fileowner($this->base)!==posix_geteuid())throw new RuntimeException('PRIVATE_STATE_CONFINEMENT');
        $lock=fopen($this->base.'/operator.lock','c');if($lock===false||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('ANOTHER_B1145_RUN_ACTIVE');chmod($this->base.'/operator.lock',0600);$this->lock=$lock;
    }

    private function emit(string $key,mixed $value):void {echo $key.'='.json_encode($value,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;}
    private static function code(Throwable $error):string {$code=$error->getMessage();return preg_match('/^[A-Z0-9_]+$/D',$code)?$code:'UNEXPECTED_'.strtoupper((new ReflectionClass($error))->getShortName());}
    private function active():string {return $this->root.'/apps/cms/current';}
    private function configPath():string {return $this->active().'/config/public_storefront.php';}
    private function retained():string {$r=$this->state['b1144']['retained']??null;if(!is_string($r))throw new RuntimeException('RETAINED_TREE_UNKNOWN');return $r;}
    private function marker():string {return bin2hex(random_bytes(8));}
    private function fault(string $point):void {if($this->fault!==null)($this->fault)($point);}

    private function atomicJSON(string $path,array $data):void
    {
        if(realpath(dirname($path))!==dirname($path)||is_link($path))throw new RuntimeException('STATE_CONFINEMENT');$temporary=$path.'.'.bin2hex(random_bytes(8)).'.next';$handle=@fopen($temporary,'x');if($handle===false)throw new RuntimeException('STATE_CREATE');
        chmod($temporary,0600);$text=json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);$ok=fwrite($handle,$text)===strlen($text)&&fflush($handle)&&(!function_exists('fsync')||fsync($handle));fclose($handle);
        if(!$ok){@unlink($temporary);throw new RuntimeException('STATE_WRITE');}if(!rename($temporary,$path)){@unlink($temporary);throw new RuntimeException('STATE_RENAME');}
    }

    /** state.json first, then the journal line, then the pointer; the in-memory phase changes only after the state file holds it. */
    private function save(string $phase):void
    {
        $next=$this->state;$next['phase']=$phase;$this->atomicJSON($this->state['private'].'/state.json',$next);$this->state['phase']=$phase;
        $journal=$this->state['private'].'/journal.jsonl';$h=fopen($journal,'a');if($h===false||!flock($h,LOCK_EX))throw new RuntimeException('JOURNAL_WRITE');
        $line=json_encode(['phase'=>$phase,'at'=>time()],JSON_THROW_ON_ERROR)."\n";$ok=fwrite($h,$line)===strlen($line)&&fflush($h);flock($h,LOCK_UN);fclose($h);if(!$ok)throw new RuntimeException('JOURNAL_WRITE');
        $this->atomicJSON($this->pointer,['schemaVersion'=>1,'kind'=>'B1145','private'=>$this->state['private'],'id'=>$this->state['id']]);$this->emit('B1145_PHASE',$phase);$this->fault('SAVED_'.$phase);
    }

    /** Writes the current state without a phase change (probe and temp-file intents). */
    private function persist():void {$this->atomicJSON($this->state['private'].'/state.json',$this->state);}

    /** No raw application output leaves the process: callers report audited identity, RC and parsed facts only. */
    private function command(array $argv,?string $cwd=null,int $timeout=120,?array $env=null):array
    {
        $pipes=[];$process=proc_open($argv,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$cwd,$env);if(!is_resource($process))throw new RuntimeException('CHILD_START');fclose($pipes[0]);stream_set_blocking($pipes[1],false);stream_set_blocking($pipes[2],false);$stdout='';$stderr='';$started=microtime(true);
        do{$stdout.=(string)stream_get_contents($pipes[1]);$stderr.=(string)stream_get_contents($pipes[2]);if(strlen($stdout)>4194304||strlen($stderr)>4194304)$this->stopChild($process,$pipes,'CHILD_OUTPUT_BOUND');$last=proc_get_status($process);if($last['running']&&microtime(true)-$started>$timeout)$this->stopChild($process,$pipes,'CHILD_TIMEOUT');if($last['running'])usleep(100000);}while($last['running']);
        $stdout.=(string)stream_get_contents($pipes[1]);$stderr.=(string)stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$closed=proc_close($process);$rc=$last['exitcode']>=0?$last['exitcode']:$closed;return ['rc'=>$rc,'stdout'=>$stdout,'stderr'=>$stderr,'stderrBytes'=>strlen($stderr)];
    }

    /** Own-user process tree below $pid (ps pid/ppid), deepest first. */
    public static function descendants(int $pid):array
    {
        $pipes=[];$p=@proc_open(['ps','-u',(string)posix_geteuid(),'-o','pid=,ppid='],[1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($p))return [];
        $out=(string)stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);proc_close($p);$children=[];
        foreach(explode("\n",$out) as $line)if(preg_match('/^\s*(\d+)\s+(\d+)\s*$/',$line,$m))$children[(int)$m[2]][]=(int)$m[1];
        $result=[];$queue=[$pid];while($queue!==[]){$next=array_shift($queue);foreach($children[$next]??[] as $child)if(!in_array($child,$result,true)&&$child!==$pid){$result[]=$child;$queue[]=$child;}}
        return array_reverse($result);
    }

    /**
     * A child that ran too long or printed too much is stopped as a whole tree (TERM, KILL after 10 s) and this process
     * WAITS until the child has exited before the error propagates, so no artisan or dump keeps running behind a HOLD.
     */
    private function stopChild(mixed $process,array $pipes,string $code):never
    {
        $pid=(int)proc_get_status($process)['pid'];$tree=self::descendants($pid);foreach($tree as $p)@posix_kill($p,15);@posix_kill($pid,15);
        $alive=static fn(array $pids):array=>array_values(array_filter($pids,static fn($p)=>@posix_kill($p,0)));$deadline=microtime(true)+10;
        while(microtime(true)<$deadline&&(proc_get_status($process)['running']||$alive($tree)!==[])){foreach([1,2] as $i)if(is_resource($pipes[$i]??null))stream_get_contents($pipes[$i]);usleep(200000);}
        $killed=0;foreach($alive($tree) as $p){@posix_kill($p,9);$killed++;}if(proc_get_status($process)['running']){@posix_kill($pid,9);$killed++;}
        $deadline=microtime(true)+20;while(proc_get_status($process)['running']&&microtime(true)<$deadline){foreach([1,2] as $i)if(is_resource($pipes[$i]??null))stream_get_contents($pipes[$i]);usleep(200000);}
        $exited=!proc_get_status($process)['running'];foreach([1,2] as $i)if(is_resource($pipes[$i]??null))fclose($pipes[$i]);if($exited)proc_close($process);
        $this->lastChildStop=['reason'=>$code,'childExited'=>$exited,'descendantsSignalled'=>count($tree),'killedAfterGrace'=>$killed,'survivingDescendants'=>count($alive($tree))];
        $this->emit('CHILD_STOPPED',$this->lastChildStop);throw new RuntimeException($code);
    }

    private function artisan(array $arguments,int $timeout):array
    {
        return $this->command(array_merge([$this->php,'-d','opcache.enable=0','-d','opcache.enable_cli=0',$this->active().'/artisan'],$arguments,['--no-interaction','--no-ansi']),$this->active(),$timeout,array_merge(getenv(),['COLUMNS'=>'240']));
    }

    /** The read-only bootstrap helper; its JSON carries booleans, counts and names only. */
    private function appState(string $mode,?string $argument=null,int $timeout=120):array
    {
        $r=$this->command(array_merge([$this->php,'-d','opcache.enable=0','-d','opcache.enable_cli=0',$this->appStateScript,$this->active(),$mode],$argument===null?[]:[$argument]),$this->active(),$timeout,array_merge(getenv(),['COLUMNS'=>'240']));
        $data=json_decode(trim($r['stdout']),true,16);if(!is_array($data))throw new RuntimeException('APP_STATE_UNPARSED');
        if($r['rc']!==0||($data['ok']??false)!==true){$code=is_string($data['error']??null)&&preg_match('/^[A-Z0-9_]+$/D',$data['error'])?$data['error']:'APP_STATE_FAILED';throw new RuntimeException($code);}return $data;
    }

    private function git(array $arguments):string {$r=$this->command(array_merge(['git','-C',$this->root],$arguments),null,30);if($r['rc']!==0)throw new RuntimeException('GIT_READ_FAILED');return trim($r['stdout']);}

    private function manifest():array
    {
        if($this->manifest!==null)return $this->manifest;$path=$this->stage.'/verification/runtime-manifest.json';
        if(is_link($path)||realpath($path)!==$path||!is_file($path)||hash_file('sha256',$path)!==self::MANIFEST_SHA)throw new RuntimeException('RUNTIME_MANIFEST_INTEGRITY');
        $m=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);if(!is_array($m['files']??null)||count($m['files'])!==self::MANIFEST_FILES)throw new RuntimeException('RUNTIME_MANIFEST_COUNT');return $this->manifest=$m;
    }

    /** The staged original config bytes (main runtime ef3f927); the only bytes the unclamp ever writes to the config. */
    private function original():string
    {
        $path=$this->stage.'/runtime/config/public_storefront.php';if(is_link($path)||realpath($path)!==$path||!is_file($path))throw new RuntimeException('STAGED_ORIGINAL_CONFIG_MISSING');
        $bytes=file_get_contents($path);if(!is_string($bytes)||!hash_equals(self::ORIGINAL_SHA,hash('sha256',$bytes)))throw new RuntimeException('STAGED_ORIGINAL_CONFIG_HASH');return $bytes;
    }

    private function clamp():string {return ReleasePrimitives::offConfig($this->original(),self::ORIGINAL_SHA);}

    /** Last value of every KEY=value line; the B1144 terminal facts must all be present and exact. */
    public static function reportFacts(string $bytes):array
    {
        if(str_contains($bytes,"\r")||strlen($bytes)>1048576)throw new RuntimeException('B1144_REPORT_FORMAT');$last=[];
        foreach(explode("\n",$bytes) as $line)if(preg_match('/^([A-Z0-9_]+)=(.*)$/D',$line,$m))$last[$m[1]]=$m[2];
        $json=static function(string $key)use($last):mixed{if(!isset($last[$key]))throw new RuntimeException('B1144_REPORT_FACT_MISSING');return json_decode($last[$key],true,16,JSON_THROW_ON_ERROR);};
        if(($last['B1144_FINAL']??null)!=='PASS_INSTALLED_OFF'||($last['EXIT']??null)!=='0'||$json('RELEASE_PHASE')!=='CLOSED_INSTALL'||($last['HELPER_SOURCE_SHA']??null)!==self::B1144_HELPER)throw new RuntimeException('B1144_REPORT_NOT_TERMINAL_PASS');
        $retained=$json('PERMANENT_PRIVATE_DATA_DEPENDENCY_DO_NOT_PRUNE');$git=$json('HOST_GIT');$status=$json('MIGRATE_STATUS_READ_ONLY');
        if(!is_string($retained)||!preg_match('~^/[A-Za-z0-9._/-]+/retained$~D',$retained)||!is_array($git)||!preg_match('/^[a-f0-9]{40}$/D',(string)($git['head']??''))||!is_string($git['branch']??null))throw new RuntimeException('B1144_REPORT_FACTS');
        if(!is_array($status)||($status['ran']??null)!==self::RAN_BEFORE||($status['pending']??null)!==self::PENDING||($status['migrateExecuted']??null)!==false)throw new RuntimeException('B1144_REPORT_MIGRATION_FACTS');
        return ['retained'=>$retained,'git'=>['head'=>$git['head'],'branch'=>$git['branch']],'helperSource'=>self::B1144_HELPER];
    }

    /** Chain to the exact Report1106 bytes and the live B1144 CLOSED_INSTALL state bound to them. Read-only. */
    private function chain():array
    {
        $path=$this->reportPath;if(is_link($path)||realpath($path)!==$path||!is_file($path)||filesize($path)>1048576)throw new RuntimeException('B1144_REPORT1106_MISSING');$report=file_get_contents($path);
        if(!is_string($report)||!hash_equals($this->reportSHA,hash('sha256',$report)))throw new RuntimeException('B1144_REPORT1106_CHANGED');$facts=self::reportFacts($report);
        $pointer=$this->b1144Base.'/current.json';if(is_link($pointer)||!is_file($pointer)||filesize($pointer)>8192)throw new RuntimeException('B1144_POINTER_MISSING');$pointerBytes=file_get_contents($pointer);$p=json_decode($pointerBytes,true,8,JSON_THROW_ON_ERROR);
        if(!is_array($p)||!preg_match('/^[a-f0-9]{32}$/D',(string)($p['id']??''))||($p['private']??null)!==$this->b1144Base.'/'.$p['id'])throw new RuntimeException('B1144_POINTER_SCOPE');
        $statePath=$p['private'].'/state.json';if(is_link($statePath)||realpath($statePath)!==$statePath||filesize($statePath)>16777216)throw new RuntimeException('B1144_STATE_MISSING');$stateBytes=file_get_contents($statePath);$s=json_decode($stateBytes,true,512,JSON_THROW_ON_ERROR);
        if(!is_array($s)||($s['schemaVersion']??0)!==1||($s['id']??null)!==$p['id']||($s['phase']??null)!=='CLOSED_INSTALL')throw new RuntimeException('B1144_STATE_NOT_CLOSED_INSTALL');
        $journal=(string)($s['journal']??'');if($journal!==$p['private'].'/journal.jsonl'||is_link($journal)||!is_file($journal)||filesize($journal)>131072)throw new RuntimeException('B1144_JOURNAL_MISSING');$journalBytes=file_get_contents($journal);
        $lines=array_values(array_filter(explode("\n",$journalBytes),static fn($l)=>$l!==''));$first=json_decode($lines[0]??'null',true);$end=json_decode(end($lines)?:'null',true);
        if(!is_array($first)||!hash_equals((string)($s['owner']??''),(string)($first['owner']??''))||($first['plan']??null)!==($s['plan']??false)||!is_array($end)||($end['state']??null)!=='CLOSED')throw new RuntimeException('B1144_JOURNAL_NOT_CLOSED');
        $plan=$s['plan'];if(($plan['active']??null)!==$this->active()||($plan['rollback']??null)!==$facts['retained'])throw new RuntimeException('B1144_PLAN_REPORT_MISMATCH');
        if(($s['git']['head']??null)!==$facts['git']['head']||($s['git']['branch']??null)!==$facts['git']['branch'])throw new RuntimeException('B1144_GIT_REPORT_MISMATCH');
        $clamp=$s['clampedConfigSHA256']??null;$htaccess=$s['preserved']['customHtaccessSHA256']??null;if(!is_string($clamp)||!preg_match('/^[a-f0-9]{64}$/D',$clamp)||!is_string($htaccess)||!preg_match('/^[a-f0-9]{64}$/D',$htaccess))throw new RuntimeException('B1144_STATE_DIGESTS');
        return ['id'=>$p['id'],'retained'=>$facts['retained'],'git'=>$facts['git'],'helperSource'=>$facts['helperSource'],'clampSHA256'=>$clamp,'htaccessSHA256'=>$htaccess,'envSHA256AtB1144'=>is_string($s['envSHA256']??null)?$s['envSHA256']:null,
            'sha256'=>['report'=>hash('sha256',$report),'pointer'=>hash('sha256',$pointerBytes),'state'=>hash('sha256',$stateBytes),'journal'=>hash('sha256',$journalBytes)],'bytes'=>['report'=>$report,'pointer'=>$pointerBytes,'state'=>$stateBytes,'journal'=>$journalBytes]];
    }

    /**
     * Bytes and inodes one more backup needs at its peak: app:backup-create writes the plain SQL dump BEFORE gzip, so the
     * uncompressed dump (bounded by three times the data+index size, hex blobs included), the gzip copy and the copied
     * private files coexist, plus a fixed reserve for migrations, logs and the private state.
     */
    public static function backupNeed(int $minFreeBytes,?int $lastBackupBytes,?int $databaseBytes,?int $fileCount):array
    {
        return ['bytes'=>max($minFreeBytes,max(0,(int)$lastBackupBytes)+3*max(0,(int)$databaseBytes)+536870912),'inodes'=>2*(max(0,(int)$fileCount)+100)+1000];
    }

    /** Disk and inode headroom on every filesystem the run writes to (the CMS tree and the backup directory). */
    private function headroom(array $paths,array $need):array
    {
        $result=[];
        foreach(array_values(array_unique(array_filter($paths,'is_string'))) as $index=>$path){
            $r=$this->command(['stat','-f','-c','%a %S %d %c',$path],null,20);if($r['rc']!==0||!preg_match('/^(\d+) (\d+) (\d+) (\d+)$/D',trim($r['stdout']),$m))throw new RuntimeException('DISK_STAT_UNAVAILABLE');
            $free=(int)$m[1]*(int)$m[2];$inodes=(int)$m[4];$freeInodes=(int)$m[3];$percent=$inodes>0?round(100*$freeInodes/$inodes,2):null;
            $result[]=['filesystem'=>$index===0?'CMS_TREE':'BACKUP_DIRECTORY','freeBytes'=>$free,'requiredBytes'=>$need['bytes'],'freeInodes'=>$freeInodes,'requiredInodes'=>$need['inodes'],'freeInodePercent'=>$percent];
            if($free<$need['bytes'])throw new RuntimeException('DISK_HEADROOM_INSUFFICIENT');if(($percent!==null&&$percent<5)||($inodes>0&&$freeInodes<$need['inodes']))throw new RuntimeException('INODE_HEADROOM_INSUFFICIENT');
        }
        return $result;
    }

    /**
     * `quota -w -p` lines (1 KiB blocks): filesystem, blocks[*], soft, hard, grace, files[*], soft, hard, grace.
     * Returns null when the output is not that format; an explicit "none" is an empty list (no account limit).
     */
    public static function parseQuota(string $stdout):?array
    {
        if(preg_match('/^Disk quotas for (?:user|group) \S+ \(uid \d+\): none\s*$/m',$stdout))return [];$rows=[];$header=false;
        foreach(preg_split('/\R/',$stdout) as $line){
            if(preg_match('/^\s*Filesystem\s+blocks\s+quota\s+limit\s+grace\s+files\s+quota\s+limit\s+grace\s*$/',$line)){$header=true;continue;}
            if(!$header||trim($line)==='')continue;
            if(!preg_match('/^\s*(\S+)\s+(\d+)\*?\s+(\d+)\s+(\d+)\s+(\S+)\s+(\d+)\*?\s+(\d+)\s+(\d+)\s+(\S+)\s*$/D',$line,$m))return null;
            $blocks=array_filter([(int)$m[3],(int)$m[4]]);$files=array_filter([(int)$m[7],(int)$m[8]]);
            $rows[]=['usedBytes'=>1024*(int)$m[2],'limitBytes'=>$blocks===[]?null:1024*min($blocks),'usedInodes'=>(int)$m[6],'limitInodes'=>$files===[]?null:min($files)];
        }
        return $header?$rows:null;
    }

    /**
     * The cPanel account quota as an unprivileged CageFS shell sees it (`quota`). Without the tool or with output that is not
     * understood the quota is reported as NOT READ and the filesystem free space (df/stat) remains the only byte gate.
     */
    private function quota(array $need):array
    {
        $bin=null;foreach(explode(':',(string)getenv('PATH')) as $d)if($d!==''&&str_starts_with($d,'/')&&is_file($d.'/quota')&&is_executable($d.'/quota')){$bin=$d.'/quota';break;}
        if($bin===null)return ['read'=>false,'note'=>'CPANEL_ACCOUNT_QUOTA_NOT_READ_NO_QUOTA_TOOL_DF_ONLY'];
        try{$r=$this->command([$bin,'-w','-p'],null,20,array_merge(getenv(),['LC_ALL'=>'C']));}catch(Throwable $e){return ['read'=>false,'note'=>'CPANEL_ACCOUNT_QUOTA_NOT_READ_'.self::code($e).'_DF_ONLY'];}
        $rows=self::parseQuota($r['stdout']);if($rows===null)return ['read'=>false,'note'=>'CPANEL_ACCOUNT_QUOTA_NOT_READ_UNPARSED_RC'.$r['rc'].'_DF_ONLY'];$result=['read'=>true,'limited'=>false,'filesystems'=>[]];
        foreach($rows as $row){
            $freeBytes=$row['limitBytes']===null?null:$row['limitBytes']-$row['usedBytes'];$freeInodes=$row['limitInodes']===null?null:$row['limitInodes']-$row['usedInodes'];$result['limited']=$result['limited']||$freeBytes!==null||$freeInodes!==null;
            $result['filesystems'][]=['freeBytes'=>$freeBytes,'requiredBytes'=>$need['bytes'],'freeInodes'=>$freeInodes,'requiredInodes'=>$need['inodes']];
            if($freeBytes!==null&&$freeBytes<$need['bytes'])throw new RuntimeException('ACCOUNT_QUOTA_HEADROOM_INSUFFICIENT');if($freeInodes!==null&&$freeInodes<$need['inodes'])throw new RuntimeException('ACCOUNT_QUOTA_INODES_INSUFFICIENT');
        }
        return $result;
    }

    /** No artisan process of this CMS: three consecutive clean samples, bounded (cron schedule:run fires every minute). */
    private function census():array
    {
        $roots=[$this->active(),$this->retained()];$deadline=hrtime(true)+$this->censusSeconds*1000000000;$clean=0;$samples=0;$seen=0;
        do{$b=LeanGates::busy($this->active().'/public/index.php',LeanGates::processes(),getmypid(),$roots);$samples++;if($b['artisan']===0){if(++$clean>=3)return ['clean'=>true,'samples'=>$samples,'busySamples'=>$seen,'otherArtisanIgnored'=>$b['otherArtisanIgnored']];}else{$clean=0;$seen++;}usleep(1000000);}while(hrtime(true)<$deadline);
        throw new RuntimeException('CMS_ARTISAN_RUNNING');
    }

    /** Other sessions of this database executing a statement, and open InnoDB transactions when readable: 3 clean samples. */
    private function lockCensus():array
    {
        $r=$this->appState('locks',(string)$this->censusSeconds,$this->censusSeconds+60);if(($r['clean']??false)!==true)throw new RuntimeException('DB_SESSIONS_ACTIVE_BEFORE_MIGRATE');unset($r['ok']);return $r;
    }

    /** The artisan and database-session census immediately before migrate --force; a busy CMS is a HOLD with no migration. */
    private function preMigrateCensus():void
    {
        try{$artisan=$this->census();$locks=$this->lockCensus();}catch(Throwable $e){$this->emit('PRE_MIGRATE_CENSUS',['clean'=>false,'code'=>self::code($e)]);$this->save('HOLD_MIGRATE_CENSUS_BLOCKED');$this->recoveryNote('MIGRATE_CENSUS',['code'=>self::code($e),'migrateExecuted'=>false,'next'=>'WAIT_FOR_THE_CMS_ARTISAN_OR_DATABASE_SESSION_TO_FINISH_THEN_RUN_THE_SAME_COMMAND_IT_TAKES_A_NEW_BACKUP']);throw $e;}
        $this->emit('PRE_MIGRATE_CENSUS',['clean'=>true,'artisan'=>$artisan,'databaseSessions'=>$locks]);
    }

    private function migrationStatus():array
    {
        $r=$this->artisan(['migrate:status'],120);if($r['rc']!==0)throw new RuntimeException('MIGRATE_STATUS_FAILED_OR_DB_UNREACHABLE');
        return LeanGates::migrationStatus($r['stdout'],LeanGates::migrationFiles($this->active().'/database/migrations'));
    }

    /** config:show public_storefront, parsed strictly for the two booleans. */
    private function configShow():array
    {
        $r=$this->artisan(['config:show','public_storefront'],120);if($r['rc']!==0)throw new RuntimeException('CONFIG_SHOW_FAILED');$found=[];
        foreach(preg_split('/\R/',$r['stdout']) as $line)if(preg_match('/^\s*(enabled|checkout_enabled)\s*\.*\s*(true|false)\s*$/D',$line,$m)){if(isset($found[$m[1]]))throw new RuntimeException('CONFIG_SHOW_DUPLICATE');$found[$m[1]]=$m[2]==='true';}
        if(count($found)!==2)throw new RuntimeException('CONFIG_SHOW_UNPARSED');return ['enabled'=>$found['enabled'],'checkout_enabled'=>$found['checkout_enabled']];
    }

    private function leftoverTemps():array
    {
        $found=[];foreach([dirname($this->configPath()),$this->retained()] as $directory)foreach(scandir($directory)?:[] as $name)if(AtomicFile::isOwnTemp($directory.'/'.$name))$found[]=$directory.'/'.$name;return $found;
    }

    /** Removes every recorded probe and temp file that is ours; foreign bytes at a recorded probe name are a HOLD. */
    private function cleanupRecorded():void
    {
        $changed=false;
        foreach($this->state['probes']??[] as $i=>$probe){if(($probe['removed']??false)===true)continue;FreshnessProbe::removeStaging($probe['staging']);FreshnessProbe::remove($this->active().'/public',$probe['name'],$probe['sha256']);$this->state['probes'][$i]['removed']=true;$changed=true;}
        foreach($this->state['temps']??[] as $i=>$temp){if(AtomicFile::isOwnTemp($temp)&&is_file($temp)&&!is_link($temp)&&!unlink($temp))throw new RuntimeException('TEMP_FILE_REMOVE');unset($this->state['temps'][$i]);$changed=true;}
        if($changed){$this->state['temps']=array_values($this->state['temps']??[]);$this->persist();}
    }

    private function recordTemp(string $temp):string {$this->state['temps'][]=$temp;$this->persist();return $temp;}

    /**
     * One probe file: $samples requests (invalidating only the config file when $invalidate), then, when $settle, a wait of
     * revalidate_freq + margin and one final request that must see no stale cached copy. Always removed.
     */
    private function probeRun(string $label,string $expected,bool $invalidate,bool $settle,bool $collect=false):array
    {
        $this->cleanupRecorded();FreshnessProbe::assertNoLeftover($this->active().'/public');$plan=UnclampProbe::plan($this->state['id'],$this->configPath());$staging=$this->state['private'].'/probe-'.bin2hex(random_bytes(8)).'.staging';
        $this->state['probes'][]=['name'=>$plan['name'],'sha256'=>$plan['sha256'],'label'=>$label,'staging'=>$staging];$this->persist();$samples=[];$failures=[];$frequency=0;
        try{
            FreshnessProbe::write($this->active().'/public/'.$plan['name'],$plan['source'],$staging);$requests=[];for($n=0;$n<$this->probeSamples;$n++)$requests[]=[$invalidate,!$invalidate];
            foreach($requests as $index=>[$inv,$fresh]){
                if($settle&&$index===count($requests)-1&&$invalidate){$wait=$frequency+$this->waitMargin;$this->emit('WEB_CONFIG_SETTLE_WAIT',['label'=>$label,'seconds'=>$wait]);sleep($wait);$this->web->status('/login',200,null,$this->marker());$inv=false;$fresh=true;}
                $response=$this->web->probe($plan['name'],$plan['token'],$inv,null);$route=is_string($response['_route']??null)?$response['_route']:'UNKNOWN';unset($response['_route']);
                $a=UnclampProbe::assess($response,$this->state['id'],$expected,$this->allowedSapis,$inv,$fresh);$frequency=max($frequency,(int)($a['values']['revalidateFreq']??0));
                $samples[]=['route'=>$route,'invalidationRequested'=>$inv,'requireFresh'=>$fresh,'gates'=>array_map(static fn($c)=>$c??'PASS',$a['gates'])]+$a['values'];foreach($a['failures'] as $f)$failures[]=$f;
            }
            if($settle&&!$invalidate){$wait=$frequency+$this->waitMargin;sleep($wait);}
        }finally{$this->cleanupRecorded();}
        $result=['label'=>$label,'samples'=>$samples,'revalidateFreq'=>$frequency,'targetedInvalidationOnly'=>true,'globalReset'=>false,'probeRemoved'=>true,'failures'=>array_values(array_unique($failures))];$this->emit('WEB_CONFIG_PROBE',$result);
        if(!$collect&&$failures!==[])throw new RuntimeException($failures[0]);return $result;
    }

    /**
     * PREFLIGHT for one stage: fresh (68 ran, the 3 pending), migrate (resume: 68+k ran, pending suffix), unclamp (71/0, before
     * any file change) or closed (re-run after the terminal PASS). Pure apart from the private probe record: every gate is
     * evaluated and printed, then the caller HOLDs on any blocker. Only key names and booleans of .env are printed.
     */
    private function preflight(string $stage):array
    {
        $g=[];$v=['stage'=>$stage];
        $try=static function(string $gate,callable $check)use(&$g):mixed{try{$r=$check();$g[$gate]=null;return $r;}catch(Throwable $e){$g[$gate]=self::code($e);return null;}};
        $chain=$try('chainB1144Report1106',fn()=>$this->chain());
        if($chain!==null&&isset($this->state['admission']))foreach(['report','pointer','state','journal'] as $k)if($chain['sha256'][$k]!==$this->state['admission'][$k])$g['chainB1144Report1106']='B1144_CHAIN_CHANGED_SINCE_ADMISSION';
        if($chain!==null)$this->state['b1144']??=array_diff_key($chain,['bytes'=>true]);
        $v['chain']=$chain===null?null:['b1144Id'=>$chain['id'],'retained'=>$chain['retained'],'git'=>$chain['git'],'report'=>self::REPORT];
        $manifest=$try('runtimeManifest',fn()=>$this->manifest());$clamp=$try('stagedOriginalConfig',fn()=>$this->clamp());
        if($clamp!==null)$g['clampEqualsOffConfigOfOriginal']=$chain!==null&&hash('sha256',$clamp)===$chain['clampSHA256']?null:'CLAMP_NOT_OFFCONFIG_OF_STAGED_ORIGINAL';else $g['clampEqualsOffConfigOfOriginal']='NOT_EVALUATED_STAGED_ORIGINAL';
        $expected=$stage==='closed'?self::ORIGINAL_SHA:($clamp===null?null:hash('sha256',$clamp));
        $g['activeConfig']=$expected!==null&&is_file($this->configPath())&&!is_link($this->configPath())&&hash_file('sha256',$this->configPath())===$expected?null:($stage==='closed'?'ACTIVE_CONFIG_NOT_ORIGINAL':'ACTIVE_CONFIG_NOT_CLAMP');
        $tree=$manifest!==null&&$chain!==null&&$expected!==null?$try('activeTreeEqualsManifest',fn()=>LeanGates::verifyTree($this->active(),$manifest,$expected,$chain['htaccessSHA256'],self::MANIFEST_FILES)):null;if(!array_key_exists('activeTreeEqualsManifest',$g))$g['activeTreeEqualsManifest']='NOT_EVALUATED_CHAIN_OR_MANIFEST';
        $v['tree']=$tree===null?null:$tree+['publicIndexPhpUnchanged'=>true];
        $env=$chain!==null?$try('envSymlink',fn()=>EnvFlags::inspect($this->active().'/.env',$chain['retained'].'/.env',$chain['retained'])):null;if(!array_key_exists('envSymlink',$g))$g['envSymlink']='NOT_EVALUATED_CHAIN';
        $v['env']=$env===null?null:['symlink'=>true,'realpathInRetainedTree'=>true,'readableWritable'=>true,'sameFilesystemAsDirectory'=>true,'mode'=>sprintf('%04o',$env['mode']),'gid'=>$env['gid'],'nlink'=>$env['nlink'],'aclFree'=>$env['aclFree']];
        if($stage!=='closed'){$replaceable=$try('activeConfigReplaceable',fn()=>AtomicFile::replaceable($this->configPath()));$v['configFile']=$replaceable;}
        $keys=$env!==null?$try('envKeys',function()use($env,$stage):array{$s=EnvFlags::state((string)file_get_contents($env['target']));foreach($s as $state)if($stage==='closed'?$state!=='true':!in_array($state,['absent','false'],true))throw new RuntimeException($stage==='closed'?'ENV_KEYS_NOT_TRUE':'ENV_KEYS_NOT_ABSENT_OR_FALSE');return $s;}):null;
        if(!array_key_exists('envKeys',$g))$g['envKeys']='NOT_EVALUATED_ENV';$v['envKeys']=$keys;
        if($chain!==null&&$env!==null)$v['envSHA256EqualsB1144Record']=hash_file('sha256',$env['target'])===$chain['envSHA256AtB1144'];
        $g['operatorProcessEnvironment']=null;foreach(EnvFlags::KEYS as $key)if(getenv($key)!==false)$g['operatorProcessEnvironment']='PUBLIC_KEYS_IN_OPERATOR_PROCESS_ENVIRONMENT';
        $g['configNotCached']=file_exists($this->active().'/bootstrap/cache/config.php')?'CONFIG_CACHE_PRESENT':null;
        $g['noMaintenanceMode']=file_exists($this->active().'/storage/framework/down')||file_exists($this->active().'/storage/framework/maintenance.php')?'MAINTENANCE_MODE_ACTIVE':null;
        $try('noLeftoverProbeOrTemp',function()use($chain):void{FreshnessProbe::assertNoLeftover($this->active().'/public');if($chain!==null&&$this->leftoverTemps()!==[])throw new RuntimeException('LEFTOVER_B1145_TEMP_FILE');});
        $git=$chain!==null?$try('hostGit',function()use($chain):array{$h=$this->git(['rev-parse','HEAD']);$b=$this->git(['rev-parse','--abbrev-ref','HEAD']);if($h!==$chain['git']['head']||$b!==$chain['git']['branch'])throw new RuntimeException('HOST_GIT_HEAD_CHANGED_SINCE_B1144');return ['head'=>$h,'branch'=>$b];}):null;if(!array_key_exists('hostGit',$g))$g['hostGit']='NOT_EVALUATED_CHAIN';$v['git']=$git;
        $migrations=$try('migrateStatus',function()use($stage):array{
            $s=$this->migrationStatus();$k=$s['ran']-self::RAN_BEFORE;
            $ok=match($stage){'fresh'=>$k===0&&$s['pending']===self::PENDING,'migrate'=>$k>=0&&$k<=3&&$s['pending']===array_slice(self::PENDING,$k)&&($k===0||($this->state['migrateAttempted']??false)===true),default=>$k===3&&$s['pending']===[]};
            if(!$ok)throw new RuntimeException('MIGRATION_SET_NOT_EXPECTED_FOR_'.strtoupper($stage));return $s;});
        $v['migrations']=$migrations;
        $app=$try('appStateDatabase',fn()=>$this->appState('state'));$backupStage=in_array($stage,['fresh','migrate'],true)&&($migrations===null||$migrations['pending']!==[]);
        if($app!==null){
            $flag=$app['storefrontFlag'];$v['app']=['db'=>true,'configCached'=>false,'storefrontDbSwitch'=>$flag,'backupsRunning'=>$app['backups']['running'],'tables'=>$app['tables'],'toggleColumns'=>$app['toggleColumns'],'roles'=>$app['roles']];
            if($stage!=='closed')$g['storefrontDbSwitchClosed']=in_array($flag,['absent','0'],true)?null:'STOREFRONT_DB_SWITCH_NOT_ABSENT_OR_0';
            $g['noRunningBackup']=$app['backups']['table']===true&&$app['backups']['running']===0?null:'BACKUP_RUN_IN_PROGRESS_OR_TABLE_MISSING';
            if($app['backups']['runningRuns']!==[])$v['runningBackups']=array_map(fn($r)=>['id'=>$r['id'],'key'=>$r['key'],'type'=>$r['type'],'startedAt'=>$r['startedAt'],'gateClearsAt'=>$r['startedAt']===null?null:$r['startedAt']+21600,'explanation'=>'A_KILLED_OR_CRASHED_APP_BACKUP_LEAVES_ITS_ROW_RUNNING_FOREVER_THE_GATE_COUNTS_RUNNING_ROWS_STARTED_IN_THE_LAST_6_HOURS'],$app['backups']['runningRuns']);
            // Every existing Storefront table must carry every constraint of its migration; a table with all columns but a
            // missing unique key, index or foreign key would make the migration return early and record itself as run.
            $constraints=self::schemaConstraintGaps($app['schema']??[]);$v['schemaConstraints']=$constraints;
            $g['schemaConstraints']=array_filter($constraints,static fn($t)=>$t['exists']&&$t['columnsComplete']&&$t['missing']!==[])===[]?null:'PUBLIC_TABLE_COLUMNS_WITHOUT_CONSTRAINTS';
            if(in_array($stage,['unclamp','closed'],true)){$gaps=$this->schemaGaps($app);$g['schemaAfterMigrations']=$gaps===[]?null:(array_filter($gaps,static fn($x)=>!str_starts_with($x,'CONSTRAINT_'))===[]?'PUBLIC_TABLE_COLUMNS_WITHOUT_CONSTRAINTS':'SCHEMA_AFTER_MIGRATIONS_INCOMPLETE');}
            $need=self::backupNeed($this->minFreeBytes,$app['backups']['lastCompletedSizeBytes'],$app['databaseBytes']??null,$app['backups']['lastCompletedFileCount']??null);$v['backupNeed']=$need+['databaseBytes'=>$app['databaseBytes']??null,'uncompressedDumpCovered'=>true];
            $disk=$try('diskAndInodeHeadroom',fn()=>$this->headroom([$this->active(),$app['backupPathReal']],$need));$v['headroom']=$disk;$v['lastBackupSeconds']=$app['backups']['lastCompletedSeconds'];
            $v['accountQuota']=$try('accountQuotaHeadroom',fn()=>$this->quota($need));
            if($backupStage){
                $retention=$try('backupRetentionHealthy',fn()=>$this->retention($app));$v['backupRetention']=$retention;
                if($retention!==null&&$retention['blocked'])$g['backupRetentionHealthy']='BACKUP_RETENTION_WOULD_REJECT_NEW_BACKUP';
                if($this->scheduleGate){$schedule=$try('outsideScheduledBackupWindow',fn()=>$this->scheduleState($app,4));$v['schedule']=$schedule;if($schedule!==null&&$schedule['conflict']!==null)$g['outsideScheduledBackupWindow']='SCHEDULED_BACKUP_OVERLAP_'.strtoupper($schedule['conflict']['type']);}
            }
        }else foreach(['storefrontDbSwitchClosed','noRunningBackup','diskAndInodeHeadroom','schemaConstraints'] as $gate)$g[$gate]='NOT_EVALUATED_APP_STATE';
        if($stage!=='closed'){
            $v['artisanCensus']=$chain!==null?$try('noRunningCmsArtisan',fn()=>$this->census()):null;if(!array_key_exists('noRunningCmsArtisan',$g))$g['noRunningCmsArtisan']='NOT_EVALUATED_CHAIN';
            if($expected!==null&&isset($this->state['id'])){$web=$try('webProbe',fn()=>$this->probeRun('preflight-'.$stage,$expected,false,false,true));$v['web']=$web;if($web!==null&&$web['failures']!==[])$g['webProbe']=$web['failures'][0];}else $g['webProbe']='NOT_EVALUATED_CONFIG';
        }
        $blockers=array_values(array_unique(array_filter($g,static fn($c)=>$c!==null)));
        $report=['stage'=>$stage,'gates'=>array_map(static fn($c)=>$c??'PASS',$g),'values'=>$v,'blockers'=>$blockers,'applicationFiles'=>'NONE','database'=>'NONE','transientProbe'=>'WRITTEN_AND_REMOVED','privateState'=>'B1145_NAMESPACE_ONLY'];$this->emit('PREFLIGHT',$report);return $report;
    }

    private function schemaGaps(array $app):array
    {
        $gaps=[];foreach(['orders','public_order_accesses','public_checkout_attempts'] as $t)if(($app['tables'][$t]??false)!==true)$gaps[]='TABLE_'.$t;
        foreach(self::TOGGLE_COLUMNS as $c)if(($app['toggleColumns'][$c]??false)!==true)$gaps[]='COLUMN_'.$c;
        foreach(self::schemaConstraintGaps($app['schema']??[]) as $table=>$t){if(!$t['exists'])continue;if(!$t['columnsComplete'])$gaps[]='COLUMNS_'.$table;foreach($t['missing'] as $m)$gaps[]='CONSTRAINT_'.$table.'_'.$m;}
        if(($app['roles']['user']??null)!=='SubAgent')$gaps[]='ROLE_USER_NAME';if(!is_string($app['roles']['customer']??null))$gaps[]='ROLE_CUSTOMER';return $gaps;
    }

    /**
     * Per Storefront table: exists, every migration column present, and the missing constraints by signature (names are
     * not required, InnoDB may add or drop an implicit foreign-key index). Extra unique keys or foreign keys are refused.
     */
    public static function schemaConstraintGaps(array $schema):array
    {
        $result=[];
        foreach(self::EXPECTED_SCHEMA as $table=>$want){
            $t=$schema[$table]??null;if(!is_array($t)||($t['exists']??false)!==true){$result[$table]=['exists'=>false,'columnsComplete'=>false,'missing'=>[],'rows'=>null,'migration'=>$want['migration']];continue;}
            $columns=is_array($t['columns']??null)?$t['columns']:[];$indexes=is_array($t['indexes']??null)?$t['indexes']:[];$foreign=is_array($t['foreignKeys']??null)?$t['foreignKeys']:[];$missing=[];
            $sig=static fn(bool $unique,array $cols):string=>($unique?'UNIQUE':'INDEX').'('.implode(',',$cols).')';$have=[];foreach($indexes as $index)$have[]=$sig((bool)($index['unique']??false),(array)($index['columns']??[]));
            if(($indexes['PRIMARY']['columns']??null)!==['id']||($indexes['PRIMARY']['unique']??false)!==true)$missing[]='PRIMARY(id)';
            foreach($want['unique'] as $cols)if(!in_array($sig(true,$cols),$have,true))$missing[]=$sig(true,$cols);
            foreach($want['index'] as $cols)if(!in_array($sig(false,$cols),$have,true))$missing[]=$sig(false,$cols);
            $wantUnique=array_map(static fn($c)=>$sig(true,$c),$want['unique']);foreach(array_unique($have) as $h)if(str_starts_with($h,'UNIQUE(')&&!in_array($h,$wantUnique,true))$missing[]='UNEXPECTED_'.$h;
            $fk=static fn(array $f):string=>'FOREIGN('.$f[0].'->'.$f[1].'.'.$f[2].'_ON_DELETE_'.$f[3].')';$haveForeign=array_map(static fn($f)=>$fk([(string)($f['column']??''),(string)($f['referencedTable']??''),(string)($f['referencedColumn']??''),strtoupper((string)($f['deleteRule']??''))]),$foreign);
            foreach($want['foreign'] as $f)if(!in_array($fk($f),$haveForeign,true))$missing[]=$fk($f);foreach($haveForeign as $h)if(!in_array($h,array_map($fk,$want['foreign']),true))$missing[]='UNEXPECTED_'.$h;
            $result[$table]=['exists'=>true,'columnsComplete'=>array_diff($want['columns'],$columns)===[],'missing'=>array_values(array_unique($missing)),'rows'=>is_int($t['rows']??null)?$t['rows']:null,'migration'=>$want['migration']];
        }
        return $result;
    }

    /**
     * The app's own retention (BackupService::prune) walks completed backups newest first and keeps the first two that
     * verify; with two or more completed rows and fewer than two verifying it throws and marks the NEW backup failed.
     * Same walk here, read-only: completed rows that fail app:backup-verify while fewer than two verify is a HOLD.
     */
    private function retention(array $app):array
    {
        $verified=[];$failing=[];$own=array_column($this->state['unverifiableBackups']??[],'id');
        foreach($app['backups']['completedRuns'] as $run){if(count($verified)>=2)break;if($this->verifyRun((int)$run['id'])['pass'])$verified[]=['id'=>$run['id'],'key'=>$run['key'],'type'=>$run['type']];else $failing[]=['id'=>$run['id'],'key'=>$run['key'],'type'=>$run['type'],'dirPresent'=>$run['dirPresent'],'createdByThisTool'=>in_array($run['id'],$own,true)];}
        return ['completed'=>count($app['backups']['completedRuns']),'verified'=>$verified,'failing'=>$failing,'blocked'=>$failing!==[]&&count($verified)<2,'walk'=>'NEWEST_FIRST_LIKE_THE_APP_RETENTION'];
    }

    private function verifyRun(int $id):array
    {
        $started=hrtime(true);try{$v=$this->artisan(['app:backup-verify','--run='.$id],$this->verifyTimeout);}catch(Throwable $e){return ['pass'=>false,'rc'=>null,'child'=>self::code($e),'seconds'=>(hrtime(true)-$started)/1e9];}
        return ['pass'=>$v['rc']===0&&str_contains($v['stdout'],'Backup je logicki validan'),'rc'=>$v['rc'],'seconds'=>(hrtime(true)-$started)/1e9];
    }

    private function now():int {return $this->fixedNow??time();}

    /**
     * Whether [now, now+need) overlaps a scheduled backup [start, start+duration) of the app's own scheduler, each event in
     * its own (app) timezone. Only "M H * * D" expressions (dailyAt, weeklyOn) are understood; anything else is refused.
     */
    public static function scheduleConflict(array $events,int $now,int $needSeconds,int $scheduledSeconds):?array
    {
        if($events===[])throw new RuntimeException('SCHEDULE_BACKUP_EVENTS_UNREADABLE');
        foreach($events as $event){
            if(!is_array($event)||!is_string($event['expression']??null)||!preg_match('/^(\d{1,2}) (\d{1,2}) \* \* (\*|[0-7])$/D',$event['expression'],$m)||(int)$m[1]>59||(int)$m[2]>23)throw new RuntimeException('SCHEDULE_EXPRESSION_UNSUPPORTED');
            try{$tz=new DateTimeZone((string)($event['timezone']??''));}catch(Throwable){throw new RuntimeException('SCHEDULE_TIMEZONE_INVALID');}
            $today=(new DateTimeImmutable('@'.$now))->setTimezone($tz)->setTime(0,0);
            for($d=-2;$d<=9;$d++){
                $day=$today->modify(($d<0?'':'+').$d.' days');if($m[3]!=='*'&&(int)$day->format('w')!==(int)$m[3]%7)continue;$at=$day->setTime((int)$m[2],(int)$m[1]);$start=$at->getTimestamp();
                if($now<$start+$scheduledSeconds&&$start<$now+$needSeconds)return ['type'=>(string)($event['type']??'unknown'),'startsAtLocal'=>$at->format('Y-m-d H:i T'),'timezone'=>$tz->getName(),'secondsFromNowToStart'=>$start-$now];
            }
        }
        return null;
    }

    /** $factor backup durations of work still ahead, plus the margin, must end before the next scheduled backup starts. */
    private function scheduleState(array $app,int $factor):array
    {
        $last=$app['backups']['lastCompletedSeconds']??null;$base=$last===null?$this->defaultBackupSeconds:max(60,(int)$last);
        $need=$factor*$base+$this->scheduleMarginSeconds;$duration=2*$base+600;$now=$this->now();$tz=(string)($app['appTimezone']??'');
        try{$local=(new DateTimeImmutable('@'.$now))->setTimezone(new DateTimeZone($tz))->format('Y-m-d H:i T');}catch(Throwable){throw new RuntimeException('APP_TIMEZONE_INVALID');}
        return ['conflict'=>self::scheduleConflict($app['backupSchedule']??[],$now,$need,$duration),'appTimezone'=>$tz,'appLocalTime'=>$local,'cliDefaultTimezoneIgnored'=>date_default_timezone_get(),'needSeconds'=>$need,'scheduledBackupSeconds'=>$duration,'events'=>$app['backupSchedule']];
    }

    /** Re-checked before the backup and immediately before migrate --force: an overlap there is a HOLD before the step. */
    private function scheduleCheck(array $app,string $point,int $factor):void
    {
        if(!$this->scheduleGate)return;try{$s=$this->scheduleState($app,$factor);}catch(Throwable $e){$s=['conflict'=>['type'=>self::code($e)]];}
        if($s['conflict']===null)return;$this->emit('SCHEDULED_BACKUP_OVERLAP',['point'=>$point]+$s);$this->save('HOLD_SCHEDULED_BACKUP_WINDOW');
        $this->recoveryNote('SCHEDULED_BACKUP_WINDOW',['point'=>$point,'conflict'=>$s['conflict'],'next'=>'RUN_THE_SAME_COMMAND_AFTER_THE_SCHEDULED_BACKUP_HAS_FINISHED_IT_TAKES_A_NEW_BACKUP']);throw new RuntimeException('SCHEDULED_BACKUP_OVERLAP_'.strtoupper((string)$s['conflict']['type']));
    }

    /** Every recovery note carries the FIRST pre-migration backup (68 ran) and the current one; never raw output. */
    private function recoveryNote(string $kind,array $fields):void
    {
        $first=$this->state['firstPreMigrationBackup']??null;
        $this->emit('RECOVERY_NOTE',['kind'=>$kind]+$fields+['firstPreMigrationBackup'=>$first===null?'NONE_YET':['id'=>$first['id'],'key'=>$first['key'],'ranAtBackup'=>self::RAN_BEFORE,'keepNote'=>'THE_APP_KEEPS_ONLY_THE_2_NEWEST_VERIFIED_BACKUPS_COPY_IT_OFF_THE_SERVER_BEFORE_THE_NEXT_NIGHTLY_BACKUP'],'latestBackupOfThisTool'=>isset($this->state['backup'])?['id'=>$this->state['backup']['id'],'key'=>$this->state['backup']['key'],'verified'=>$this->state['backup']['verified']]:'NONE']);
    }

    /** One exact owner command per line (plain text, copy everything after the colon). Only ids, keys and table names. */
    private function ownerCommand(string $php):void {echo 'OWNER_COMMAND: cd '.$this->active().' && php artisan tinker --execute="'.$php.'"'.PHP_EOL;}

    /** Specific recovery for every preflight blocker that has one. */
    private function preflightRecovery(array $report):void
    {
        $v=$report['values'];$b=$report['blockers'];
        if(in_array('BACKUP_RETENTION_WOULD_REJECT_NEW_BACKUP',$b,true)){
            $this->recoveryNote('BACKUP_RETENTION',['failing'=>$v['backupRetention']['failing'],'verified'=>$v['backupRetention']['verified'],'why'=>'APP_BACKUP_CREATE_WOULD_MARK_THE_NEW_GOOD_BACKUP_FAILED_AND_EVERY_LATER_CREATE_INCLUDING_THE_NIGHTLY_ONE_WOULD_FAIL',
                'ownerProcedure'=>'MARK_EACH_LISTED_FAILING_ROW_FAILED_WITH_ITS_OWNER_COMMAND_THEN_RUN_THE_SAME_COMMAND_ITS_DIRECTORY_STAYS_IN_BACKUP_PATH_UNDER_ITS_KEY_AND_MAY_BE_DELETED_AFTERWARDS']);
            foreach($v['backupRetention']['failing'] as $row)$this->ownerCommand("DB::table('backup_runs')->where('id',".(int)$row['id'].")->where('status','completed')->update(['status'=>'failed','error_message'=>'B1145 owner: failed app:backup-verify'])");
        }
        if(in_array('BACKUP_RUN_IN_PROGRESS_OR_TABLE_MISSING',$b,true)&&isset($v['runningBackups'])){
            $this->recoveryNote('RUNNING_BACKUP_ROW',['rows'=>$v['runningBackups'],'ownerProcedure'=>'IF_NO_BACKUP_PROCESS_IS_RUNNING_PS_SHOWS_NO_ARTISAN_OR_DUMP_MARK_THE_STALE_ROW_FAILED_WITH_ITS_OWNER_COMMAND_OR_WAIT_UNTIL_GATE_CLEARS_AT']);
            foreach($v['runningBackups'] as $row)$this->ownerCommand("DB::table('backup_runs')->where('id',".(int)$row['id'].")->where('status','running')->update(['status'=>'failed','error_message'=>'B1145 owner: stale running backup'])");
        }
        if(in_array('PUBLIC_TABLE_COLUMNS_WITHOUT_CONSTRAINTS',$b,true))$this->schemaRecovery($v['schemaConstraints']??[],$v['migrations']['pending']??null);
        foreach($b as $code)if(str_starts_with($code,'SCHEDULED_BACKUP_OVERLAP_'))$this->recoveryNote('SCHEDULED_BACKUP_WINDOW',['conflict'=>$v['schedule']['conflict']??null,'next'=>'RUN_THE_SAME_COMMAND_AFTER_THE_SCHEDULED_BACKUP_HAS_FINISHED']);
        foreach(['DISK_HEADROOM_INSUFFICIENT','INODE_HEADROOM_INSUFFICIENT','ACCOUNT_QUOTA_HEADROOM_INSUFFICIENT','ACCOUNT_QUOTA_INODES_INSUFFICIENT'] as $code)if(in_array($code,$b,true))$this->recoveryNote('HEADROOM',['code'=>$code,'need'=>$v['backupNeed']??null,'next'=>'FREE_SPACE_OR_INODES_IN_THE_ACCOUNT_THEN_RUN_THE_SAME_COMMAND']);
    }

    /** An EMPTY table whose migration is still pending is dropped so the migration recreates it with every constraint. */
    private function schemaRecovery(array $constraints,?array $pending):void
    {
        foreach($constraints as $table=>$t){
            if(!$t['exists']||!$t['columnsComplete']||$t['missing']===[])continue;$isPending=is_array($pending)&&in_array($t['migration'],$pending,true);$drop=$isPending&&$t['rows']===0;
            $this->recoveryNote('TABLE_WITHOUT_CONSTRAINTS',['table'=>$table,'missing'=>$t['missing'],'rows'=>$t['rows'],'migrationPending'=>$isPending,
                'ownerProcedure'=>$drop?'THE_TABLE_IS_EMPTY_AND_ITS_MIGRATION_IS_PENDING_DROP_IT_WITH_THE_OWNER_COMMAND_THEN_RUN_THE_SAME_COMMAND_THE_MIGRATION_RECREATES_IT_WITH_EVERY_CONSTRAINT':'DO_NOT_DROP_THE_TABLE_HAS_ROWS_OR_ITS_MIGRATION_IS_RECORDED_A_DEVELOPER_MUST_ADD_THE_MISSING_CONSTRAINTS_EXACTLY_AS_THE_MIGRATION_DEFINES_STOREFRONT_STAYS_CLOSED']);
            if($drop)$this->ownerCommand("Schema::drop('".$table."')");
        }
    }

    private function loadExisting():bool
    {
        if(!file_exists($this->pointer))return false;if(is_link($this->pointer)||filesize($this->pointer)>8192)throw new RuntimeException('B1145_POINTER');$p=json_decode(file_get_contents($this->pointer),true,8,JSON_THROW_ON_ERROR);
        if(!preg_match('/^[a-f0-9]{32}$/D',(string)($p['id']??''))||($p['private']??null)!==$this->base.'/'.$p['id'])throw new RuntimeException('B1145_POINTER_SCOPE');$path=$p['private'].'/state.json';
        if(is_link($path)||realpath($path)!==$path||filesize($path)>16777216)throw new RuntimeException('B1145_STATE');$this->state=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);
        if(($this->state['schemaVersion']??0)!==1||($this->state['kind']??null)!=='B1145'||($this->state['id']??null)!==$p['id'])throw new RuntimeException('B1145_STATE_BINDING');return true;
    }

    private function begin():void
    {
        $id=bin2hex(random_bytes(16));$private=$this->base.'/'.$id;if(!mkdir($private,0700))throw new RuntimeException('PRIVATE_TRANSACTION_CREATE');
        $this->state=['schemaVersion'=>1,'kind'=>'B1145','id'=>$id,'private'=>$private,'phase'=>'NEW','startedAt'=>time(),'probes'=>[],'temps'=>[]];$this->save('PREFLIGHT');
    }

    /** Archives the exact B1144 report, pointer, state and journal bytes (0600) and re-checks the live bytes are unchanged. */
    private function admit():void
    {
        $chain=$this->chain();$archive=$this->state['private'].'/b1144-archive';if(!is_dir($archive)&&!mkdir($archive,0700))throw new RuntimeException('ADMISSION_ARCHIVE_CREATE');
        foreach($chain['bytes'] as $name=>$bytes){$path=$archive.'/'.$name;if(is_file($path)&&!is_link($path)&&hash_file('sha256',$path)===hash('sha256',$bytes))continue;AtomicFile::privateCopy($path,$bytes);}
        $again=$this->chain();if($again['sha256']!==$chain['sha256'])throw new RuntimeException('B1144_CHAIN_CHANGED_DURING_ADMISSION');
        $this->state['admission']=$chain['sha256'];$this->state['b1144']=array_diff_key($chain,['bytes'=>true]);$this->emit('B1144_TERMINAL_ADMISSION',['b1144Id'=>$chain['id'],'reportSHA256'=>$chain['sha256']['report'],'archivedPrivate0600'=>true,'liveBytesUnchanged'=>true]);
    }

    /**
     * (A) A FRESH backup of the production database and private files in THIS run, then its own verification. A backup of
     * an earlier run is never reused (the nightly retention may have removed it). Prints id, key, size and status only.
     */
    private function backup(int $ran):void
    {
        $this->processBackup=null;$app=$this->appState('state');$this->scheduleCheck($app,'BEFORE_BACKUP_CREATE',3);$this->warnFirstBackup($app);
        $this->save('BACKUP_INTENT');$since=time();$started=hrtime(true);
        try{$create=$this->artisan(['app:backup-create','--type=manual'],$this->backupTimeout);}catch(Throwable $e){$this->backupChildFailure('CREATE',$e,$since);}
        $key=preg_match('/^PASS Backup je kreiran: (\S+)\s*$/m',$create['stdout'],$m)?basename($m[1]):null;
        if($create['rc']!==0||$key===null||!preg_match('/^\d{8}-\d{6}-manual-[a-f0-9]{6}$/D',$key)){
            $this->emit('BACKUP',['status'=>'CREATE_FAILED','rc'=>$create['rc'],'rawOutputNotExported'=>true]);$this->save('HOLD_BACKUP_FAILED');
            $this->recoveryNote('BACKUP_CREATE_FAILED',['rowsSinceIntent'=>$this->backupRowsSince($since),'why'=>'READ_THE_LARAVEL_LOG_A_FAILED_DUMP_OR_THE_APP_RETENTION_FEWER_THAN_TWO_RESTORE_READY_BACKUPS_MARKS_THE_NEW_ROW_FAILED','next'=>'FIX_THE_CAUSE_THEN_RUN_THE_SAME_COMMAND']);throw new RuntimeException('BACKUP_CREATE_FAILED');
        }
        $run=$this->appState('backup',$key);if(($run['found']??false)!==true||($run['status']??null)!=='completed'||!is_int($run['id']??null)){$this->emit('BACKUP',['status'=>'RUN_NOT_COMPLETED']);$this->save('HOLD_BACKUP_FAILED');$this->recoveryNote('BACKUP_RUN_NOT_COMPLETED',['key'=>$key,'why'=>'THE_APP_RETENTION_REMOVED_THE_NEW_BACKUP_BECAUSE_IT_FAILED_APP_BACKUP_VERIFY_WHILE_TWO_OTHER_BACKUPS_VERIFIED','next'=>'READ_THE_LARAVEL_LOG_FIX_THE_CAUSE_THEN_RUN_THE_SAME_COMMAND']);throw new RuntimeException('BACKUP_RUN_NOT_COMPLETED');}
        $createdAt=hrtime(true);$this->state['backup']=['id'=>$run['id'],'key'=>$key,'sizeBytes'=>$run['sizeBytes'],'ranAtBackup'=>$ran,'verified'=>false,'createdAt'=>time()];$this->save('BACKUP_CREATED');
        $verify=$this->verifyRun($run['id']);
        $this->emit('BACKUP',['id'=>$run['id'],'key'=>$key,'sizeBytes'=>$run['sizeBytes'],'status'=>$verify['pass']?'VERIFIED':'VERIFY_FAILED','verifyRC'=>$verify['rc'],'createSeconds'=>round(($createdAt-$started)/1e9,1),'verifySeconds'=>round($verify['seconds'],1)]);
        if(!$verify['pass']){
            // Remember exactly which backup of THIS tool failed verification: it stays "completed" and blocks the app's retention.
            $this->state['unverifiableBackups'][]=['id'=>$run['id'],'key'=>$key,'at'=>time()];$this->save('HOLD_BACKUP_FAILED');
            $this->recoveryNote('BACKUP_VERIFY_FAILED',['id'=>$run['id'],'key'=>$key,'createdByThisTool'=>true,'child'=>$verify['child']??null,'why'=>'WHILE_THIS_COMPLETED_ROW_FAILS_VERIFY_AND_FEWER_THAN_TWO_OTHER_BACKUPS_VERIFY_THE_APP_REJECTS_EVERY_NEW_BACKUP_INCLUDING_THE_NIGHTLY_ONE','ownerProcedure'=>'MARK_THIS_ROW_FAILED_WITH_THE_OWNER_COMMAND_THEN_FIX_THE_CAUSE_AND_RUN_THE_SAME_COMMAND']);
            $this->ownerCommand("DB::table('backup_runs')->where('id',".(int)$run['id'].")->where('status','completed')->update(['status'=>'failed','error_message'=>'B1145 owner: failed app:backup-verify'])");
            throw new RuntimeException(isset($verify['child'])?'BACKUP_VERIFY_CHILD_TERMINATED':'BACKUP_VERIFY_FAILED');
        }
        $this->processBackup=['id'=>$run['id'],'key'=>$key,'sizeBytes'=>$run['sizeBytes'],'createdAt'=>$createdAt,'verifySeconds'=>$verify['seconds']];$this->state['backup']['verified']=true;
        // The FIRST verified pre-migration backup (68 ran) is kept in state for every later recovery note and never overwritten.
        if($ran===self::RAN_BEFORE&&!isset($this->state['firstPreMigrationBackup']))$this->state['firstPreMigrationBackup']=['id'=>$run['id'],'key'=>$key,'sizeBytes'=>$run['sizeBytes'],'verifiedAt'=>time()];
        $this->save('BACKUP_VERIFIED');
    }

    /** Backup rows (running) this run may have left, for the recovery note; empty when the database cannot be read. */
    private function backupRowsSince(int $since):array
    {
        try{$app=$this->appState('state');}catch(Throwable){return ['readable'=>false];}
        return ['readable'=>true,'running'=>array_values(array_filter($app['backups']['runningRuns'],static fn($r)=>($r['startedAt']??0)>=$since-5))];
    }

    /** A backup child stopped by timeout or output bound: HOLD saved after the child exited, with the running-row explanation. */
    private function backupChildFailure(string $step,Throwable $error,int $since):never
    {
        $code=self::code($error);$this->save('HOLD_BACKUP_FAILED');$rows=$this->backupRowsSince($since);
        $this->recoveryNote('BACKUP_CHILD_TERMINATED',['step'=>$step,'reason'=>$code,'child'=>$this->lastChildStop,'rowsSinceIntent'=>$rows,'migrateExecuted'=>false,
            'runningRowExplanation'=>'THE_STOPPED_APP_BACKUP_NEVER_UPDATES_ITS_BACKUP_RUNS_ROW_IT_STAYS_RUNNING_AND_THE_NO_RUNNING_BACKUP_GATE_BLOCKS_FOR_6_HOURS_AFTER_ITS_START_UNLESS_THE_OWNER_MARKS_IT_FAILED',
            'ownerProcedure'=>'CONFIRM_NO_ARTISAN_OR_DUMP_PROCESS_RUNS_THEN_MARK_EACH_LISTED_RUNNING_ROW_FAILED_WITH_ITS_OWNER_COMMAND_ITS_PARTIAL_DIRECTORY_MAY_BE_DELETED_THEN_RUN_THE_SAME_COMMAND']);
        foreach($rows['running']??[] as $row)$this->ownerCommand("DB::table('backup_runs')->where('id',".(int)$row['id'].")->where('status','running')->update(['status'=>'failed','error_message'=>'B1145 owner: stopped backup'])");
        throw new RuntimeException('BACKUP_CHILD_TERMINATED');
    }

    /** Warns when the app's keep-two retention could remove the first pre-migration backup with the backup about to start. */
    private function warnFirstBackup(array $app):void
    {
        $first=$this->state['firstPreMigrationBackup']??null;if($first===null)return;$ids=array_column($app['backups']['completedRuns'],'id');$position=array_search($first['id'],$ids,true);
        if($position===false)$this->emit('WARN_FIRST_PRE_MIGRATION_BACKUP',['id'=>$first['id'],'key'=>$first['key'],'status'=>'ALREADY_REMOVED_BY_APP_RETENTION_COPY_THE_OFF_SERVER_COPY_IF_YOU_HAVE_ONE']);
        elseif($position>=1)$this->emit('WARN_FIRST_PRE_MIGRATION_BACKUP',['id'=>$first['id'],'key'=>$first['key'],'status'=>'THE_APP_RETENTION_REMOVES_IT_WHEN_THIS_BACKUP_COMPLETES_COPY_IT_OFF_THE_SERVER_FIRST_IF_IT_IS_NEEDED','newerCompleted'=>$position]);
    }

    /**
     * Reuse of the backup taken in THIS process, immediately before migrate --force: the row exists and is completed with
     * the same id and size, its directory is present, and app:backup-verify passes again. Returns the failed check or null.
     */
    private function recheckBackup():?string
    {
        $b=$this->processBackup;if($b===null)return 'NO_BACKUP_IN_THIS_RUN';$this->fault('BEFORE_BACKUP_RECHECK');
        try{$row=$this->appState('backup',$b['key']);}catch(Throwable $e){return 'ROW_UNREADABLE_'.self::code($e);}
        if(($row['found']??false)!==true)return 'ROW_MISSING';if(($row['status']??null)!=='completed'||($row['id']??null)!==$b['id'])return 'ROW_NOT_COMPLETED';
        if(($row['sizeBytes']??null)!==$b['sizeBytes'])return 'SIZE_CHANGED';if(($row['dirPresent']??false)!==true)return 'DIRECTORY_MISSING';
        $v=$this->verifyRun($b['id']);return $v['pass']?null:'VERIFY_FAILED';
    }

    /** Migration names in --pretend output, in order of first appearance. */
    public static function pretendNames(string $stdout):array
    {
        preg_match_all('/\b(\d{4}_\d{2}_\d{2}_\d{6}_[A-Za-z0-9_]+)\b/',$stdout,$m);return array_values(array_unique($m[1]));
    }

    /**
     * --pretend may list only migrations that are pending, in order. It either lists all of them (rc 0) or stops at the FIRST
     * one because pretend returns no rows to schema introspection; then that migration's own exact code must appear
     * (PRETEND_KNOWN_STOPS), on a fresh run and on a resume alike. The authoritative file-set gate stays migrate:status.
     */
    public static function pretendVerdict(int $rc,string $stdout,string $stderr,array $pending,bool $fresh):array
    {
        $names=self::pretendNames($stdout);if($pending===[]||array_diff($names,$pending)!==[]||$names!==array_slice($pending,0,count($names)))return ['ok'=>false,'listed'=>$names,'code'=>'PRETEND_LISTS_UNEXPECTED_MIGRATIONS'];
        if($rc===0)return $names===$pending?['ok'=>true,'listed'=>$names,'mode'=>'ALL_PENDING_LISTED']:['ok'=>false,'listed'=>$names,'code'=>'PRETEND_INCOMPLETE_LISTING'];
        $expectedCode=self::PRETEND_KNOWN_STOPS[$pending[0]]??null;$known=$expectedCode!==null&&str_contains($stdout.$stderr,$expectedCode);
        $foreign=false;foreach(self::PRETEND_KNOWN_STOPS as $name=>$code)if($name!==$pending[0]&&str_contains($stdout.$stderr,$code))$foreign=true;
        if($names===[$pending[0]]&&$known&&!$foreign)return ['ok'=>true,'listed'=>$names,'mode'=>'STOPPED_AT_FIRST_PENDING_PRETEND_INTROSPECTION','knownIntrospectionCode'=>$expectedCode,'resume'=>!$fresh];
        return ['ok'=>false,'listed'=>$names,'code'=>'PRETEND_FAILED_UNEXPECTEDLY'];
    }

    /** (B) Exactly the pending migrations: never seeding, fresh, rollback or refresh. No automatic rollback on failure. */
    private function migrate(array $pending,bool $fresh,int $ran):void
    {
        if($pending!==[]){
            try{$p=$this->artisan(['migrate','--pretend','--force'],300);}catch(Throwable $e){$this->save('HOLD_PRETEND_UNEXPECTED');$this->recoveryNote('PRETEND_CHILD_TERMINATED',['reason'=>self::code($e),'child'=>$this->lastChildStop,'migrateExecuted'=>false]);throw new RuntimeException('PRETEND_CHILD_TERMINATED');}
            $verdict=self::pretendVerdict($p['rc'],$p['stdout'],$p['stderr'],$pending,$fresh);
            $this->emit('MIGRATE_PRETEND',['rc'=>$p['rc']]+$verdict+['rawOutputNotExported'=>true]);if(!$verdict['ok']){$this->save('HOLD_PRETEND_UNEXPECTED');throw new RuntimeException($verdict['code']);}
            // The backup of THIS run is re-checked and re-verified, then the census, then the age bound; otherwise one retake.
            for($attempt=0;;$attempt++){
                $reason=$this->recheckBackup();
                if($reason===null){
                    $this->preMigrateCensus();$age=(hrtime(true)-$this->processBackup['createdAt'])/1e9;$bound=max($this->backupMaxAgeSeconds,(int)ceil(2*$this->processBackup['verifySeconds'])+2*$this->censusSeconds+60);
                    if($age<=$bound){$this->emit('BACKUP_RECHECKED_BEFORE_MIGRATE',['id'=>$this->processBackup['id'],'key'=>$this->processBackup['key'],'rowCompleted'=>true,'sameSize'=>true,'directoryPresent'=>true,'reverified'=>true,'ageSeconds'=>(int)$age,'maxAgeSeconds'=>$bound,'sameProcess'=>true]);break;}
                    $reason='OLDER_THAN_'.$bound.'_SECONDS';
                }
                $this->emit('BACKUP_RECHECK_FAILED',['reason'=>$reason,'attempt'=>$attempt+1,'action'=>$attempt===0?'RETAKE_A_FRESH_BACKUP':'HOLD']);
                if($attempt>=1){$this->save('HOLD_BACKUP_RECHECK_FAILED');$this->recoveryNote('BACKUP_RECHECK_FAILED',['reason'=>$reason,'migrateExecuted'=>false,'next'=>'CHECK_WHAT_REMOVES_OR_CHANGES_BACKUPS_CRON_RETENTION_OR_DISK_THEN_RUN_THE_SAME_COMMAND']);throw new RuntimeException('BACKUP_RECHECK_FAILED_BEFORE_MIGRATE');}
                $this->backup($ran);
            }
            $this->scheduleCheck($this->appState('state'),'BEFORE_MIGRATE_FORCE',1);$this->fault('BEFORE_MIGRATE_FORCE');
            $this->state['migrateAttempted']=true;$this->save('MIGRATE_INTENT');$child=null;
            try{$m=$this->artisan(['migrate','--force'],$this->migrateTimeout);}catch(Throwable $e){$child=['reason'=>self::code($e)]+($this->lastChildStop??[]);$m=['rc'=>null];}
            try{$status=$this->migrationStatus();}catch(Throwable $e){$status=['ran'=>null,'pending'=>null,'error'=>self::code($e)];}
            $this->emit('MIGRATE',['rc'=>$m['rc'],'ran'=>$status['ran'],'pending'=>$status['pending'],'childTerminated'=>$child,'seedFreshRollbackOrRefresh'=>false,'rawOutputNotExported'=>true]);
            if($child!==null||$m['rc']!==0||$status['pending']!==[]||$status['ran']!==self::RAN_BEFORE+3){
                $this->save('HOLD_MIGRATION_FAILED');$schema=null;try{$schema=self::schemaConstraintGaps($this->appState('state')['schema']??[]);}catch(Throwable){}
                $this->emit('MIGRATION_RECOVERY_NOTE',['automaticRollback'=>false,'clamp'=>'IN_PLACE_STOREFRONT_CLOSED','backupId'=>$this->state['backup']['id']??null,'firstPreMigrationBackupId'=>$this->state['firstPreMigrationBackup']['id']??null,'ran'=>$status['ran'],'pending'=>$status['pending'],'childTerminated'=>$child,'schemaConstraints'=>$schema,
                    'next'=>'READ_THE_LARAVEL_LOG_FIX_THE_CAUSE_THEN_RUN_THE_SAME_COMMAND_AGAIN_IT_CHECKS_EVERY_TABLE_CONSTRAINT_TAKES_A_NEW_BACKUP_AND_RUNS_ONLY_WHAT_IS_STILL_PENDING_OR_RESTORE_THE_VERIFIED_BACKUP_ON_A_SEPARATE_DATABASE_FIRST']);
                $this->recoveryNote('MIGRATION_FAILED',['childTerminated'=>$child!==null,'ran'=>$status['ran'],'pending'=>$status['pending']]);
                throw new RuntimeException($child!==null?'MIGRATE_CHILD_TERMINATED_NO_AUTOMATIC_ROLLBACK':'MIGRATION_FAILED_NO_AUTOMATIC_ROLLBACK');
            }
        }
        $app=$this->appState('state');$gaps=$this->schemaGaps($app);$this->emit('SCHEMA_AFTER_MIGRATIONS',['tables'=>$app['tables'],'toggleColumns'=>$app['toggleColumns'],'roles'=>$app['roles'],'constraints'=>self::schemaConstraintGaps($app['schema']??[]),'gaps'=>$gaps,'readOnly'=>true]);
        if($gaps!==[]){
            $this->save('HOLD_MIGRATION_VERIFY_FAILED');$this->schemaRecovery(self::schemaConstraintGaps($app['schema']??[]),[]);
            throw new RuntimeException(array_filter($gaps,static fn($x)=>!str_starts_with($x,'CONSTRAINT_'))===[]?'PUBLIC_TABLE_COLUMNS_WITHOUT_CONSTRAINTS':'SCHEMA_AFTER_MIGRATIONS_INCOMPLETE');
        }
        $this->save('MIGRATED_VERIFIED');
    }

    /** (C) Private copies, then the config rename, then the .env rename over the REAL target behind the symlink. */
    private function unclamp():void
    {
        $config=$this->configPath();$original=$this->original();$clamp=$this->clamp();if(file_get_contents($config)!==$clamp)throw new RuntimeException('ACTIVE_CONFIG_NOT_CLAMP');
        $link=$this->active().'/.env';$target=$this->retained().'/.env';$env=EnvFlags::inspect($link,$target,$this->retained());$before=(string)file_get_contents($env['target']);$next=EnvFlags::transform($before);
        $attempt=bin2hex(random_bytes(4));$private=$this->state['private'];
        $u=['attempt'=>$attempt,'configMode'=>fileperms($config)&0777,'clampCopy'=>$private.'/clamp-'.$attempt.'.php','envCopy'=>$private.'/env-before-'.$attempt,'envMode'=>$env['mode'],'envLinkIno'=>$env['linkIno'],'envAfterSHA256'=>hash('sha256',$next['bytes']),'changedKeys'=>$next['changed']];
        $u['clampSHA256']=AtomicFile::privateCopy($u['clampCopy'],$clamp);$u['envBeforeSHA256']=AtomicFile::privateCopy($u['envCopy'],$before);
        $u['configTemp']=AtomicFile::tempFor($config);$u['envTemp']=AtomicFile::tempFor($env['target']);$this->state['temps']=array_values(array_merge($this->state['temps']??[],[$u['configTemp'],$u['envTemp']]));
        $this->state['unclamp']=$u;$this->state['logBaseline']=LeanGates::logBaseline($this->active().'/storage/logs');$this->save('UNCLAMP_INTENT');
        $this->fault('BEFORE_CONFIG_RENAME');$receipt=AtomicFile::replace($config,$original,$u['configMode'],$u['configTemp']);
        $this->emit('CONFIG_UNCLAMPED',['bytes'=>'STAGED_ORIGINAL_0CFBC864','mode'=>sprintf('%04o',$u['configMode']),'sameDirectoryRename'=>true]+$receipt);$this->save('CONFIG_UNCLAMPED');
        $again=EnvFlags::inspect($link,$target,$this->retained());if(hash_file('sha256',$again['target'])!==$u['envBeforeSHA256']||$again['linkIno']!==$u['envLinkIno'])throw new RuntimeException('ENV_CHANGED_DURING_UNCLAMP');
        $this->fault('BEFORE_ENV_RENAME');$receipt=AtomicFile::replace($again['target'],$next['bytes'],$u['envMode'],$u['envTemp']);
        $after=EnvFlags::inspect($link,$target,$this->retained());if($after['linkIno']!==$u['envLinkIno']||readlink($link)!==$target)throw new RuntimeException('ENV_SYMLINK_NOT_PRESERVED');EnvFlags::assertOnlyKeysDiffer($before,(string)file_get_contents($after['target']));
        $this->emit('ENV_UNCLAMPED',['keysSetTrue'=>EnvFlags::KEYS,'keysChanged'=>$u['changedKeys'],'symlinkPreserved'=>true,'renamedOverRealTarget'=>true,'onlyTheseLinesDiffer'=>true,'mode'=>sprintf('%04o',$u['envMode']),'valuesNotPrinted'=>true]+$receipt);$this->save('ENV_UNCLAMPED');
    }

    private function smokes():void {$marker=$this->marker();foreach(self::SMOKES as [$path,$expected])$this->web->status($path,$expected,null,$marker);}

    /** (D) Freshness and acceptance. Any failure here restores the clamp and the .env. */
    private function accept():void
    {
        $this->save('ACCEPTANCE_INTENT');$this->probeRun('unclamped',self::ORIGINAL_SHA,true,true);
        $shown=$this->configShow();$this->emit('CONFIG_SHOW_PUBLIC_STOREFRONT',$shown);if($shown!==['enabled'=>true,'checkout_enabled'=>true])throw new RuntimeException('CONFIG_SHOW_NOT_UNCLAMPED');
        $app=$this->appState('state');if(!in_array($app['storefrontFlag'],['absent','0'],true))throw new RuntimeException('STOREFRONT_DB_SWITCH_NOT_ABSENT_OR_0');
        $this->smokes();$scan=LeanGates::newLogErrors($this->active().'/storage/logs',$this->state['logBaseline']);$this->emit('LARAVEL_LOG_SINCE_UNCLAMP',$scan);if($scan['newErrorLines']!==0)throw new RuntimeException('NEW_LARAVEL_LOG_ERRORS_SINCE_UNCLAMP');
        $tree=LeanGates::verifyTree($this->active(),$this->manifest(),self::ORIGINAL_SHA,$this->state['b1144']['htaccessSHA256'],self::MANIFEST_FILES);$this->emit('ACTIVE_TREE_AFTER_UNCLAMP',$tree+['configIsStagedOriginal'=>true]);
        $env=EnvFlags::inspect($this->active().'/.env',$this->retained().'/.env',$this->retained());if(EnvFlags::state((string)file_get_contents($env['target']))!==array_fill_keys(EnvFlags::KEYS,'true')||hash_file('sha256',$env['target'])!==$this->state['unclamp']['envAfterSHA256'])throw new RuntimeException('ENV_NOT_UNCLAMPED_AFTER_ACCEPTANCE');
        $this->save('CLOSED_UNCLAMPED');
    }

    /** Puts the exact clamp and .env bytes back from the private copies, then re-verifies. Foreign changes are never overwritten. */
    private function restore(string $reason):bool
    {
        $this->emit('UNCLAMP_RESTORE',['reason'=>$reason]);$problems=[];try{$this->save('RESTORE_INTENT');}catch(Throwable $e){$problems[]=self::code($e);}
        try{$this->cleanupRecorded();}catch(Throwable $e){$problems[]=self::code($e);}
        $u=$this->state['unclamp']??null;$clampSHA=null;
        if(is_array($u)){
            $config=$this->configPath();
            try{$clamp=AtomicFile::readPrivate($u['clampCopy'],$u['clampSHA256']);$clampSHA=$u['clampSHA256'];$now=hash_file('sha256',$config);
                if($now!==$u['clampSHA256']){if($now!==self::ORIGINAL_SHA)throw new RuntimeException('RESTORE_CONFIG_FOREIGN_CONTENT_NOT_OVERWRITTEN');AtomicFile::replace($config,$clamp,$u['configMode'],$this->recordTemp(AtomicFile::tempFor($config)));}
            }catch(Throwable $e){$problems[]=self::code($e);}
            // Read-only unless a write is needed: an unchanged .env is never inspected for writability or rewritten.
            try{$link=$this->active().'/.env';$target=$this->retained().'/.env';if(!is_link($link)||readlink($link)!==$target||realpath($link)!==$target)throw new RuntimeException('RESTORE_ENV_SYMLINK_CHANGED');$now=hash_file('sha256',$target);
                if($now!==$u['envBeforeSHA256']){if($now!==$u['envAfterSHA256'])throw new RuntimeException('RESTORE_ENV_FOREIGN_CHANGE_NOT_OVERWRITTEN');$env=EnvFlags::inspect($link,$target,$this->retained());AtomicFile::replace($env['target'],AtomicFile::readPrivate($u['envCopy'],$u['envBeforeSHA256']),$u['envMode'],$this->recordTemp(AtomicFile::tempFor($env['target'])));}
            }catch(Throwable $e){$problems[]=self::code($e);}
        }
        try{$this->cleanupRecorded();}catch(Throwable $e){$problems[]=self::code($e);}
        if($problems===[])try{
            $clampSHA??=hash('sha256',$this->clamp());if(hash_file('sha256',$this->configPath())!==$clampSHA)throw new RuntimeException('RESTORE_CONFIG_NOT_CLAMP');
            $this->probeRun('restored',$clampSHA,true,true);$shown=$this->configShow();$this->emit('CONFIG_SHOW_PUBLIC_STOREFRONT',$shown);if($shown!==['enabled'=>false,'checkout_enabled'=>false])throw new RuntimeException('RESTORE_CONFIG_SHOW_NOT_CLAMPED');
            $marker=$this->marker();$this->web->status('/katalog',404,null,$marker);$this->web->status('/login',200,null,$marker);
        }catch(Throwable $e){$problems[]=self::code($e);}
        if($problems!==[]){
            try{$this->save('RESTORE_INCOMPLETE');}catch(Throwable){$this->emit('RESTORE_STATE_UNWRITABLE','FIX_DISK_OR_PERMISSIONS_THEN_RUN_THE_SAME_COMMAND');}
            $this->emit('RESTORE_INCOMPLETE',['problems'=>array_values(array_unique($problems)),'privateCopies'=>is_array($u)?'KEPT_0600_IN_PRIVATE_STATE':'NONE','operatorNote'=>'DO_NOT_EDIT_BY_HAND_FIX_THE_REPORTED_CAUSE_THEN_RUN_THE_SAME_COMMAND_IT_RESTORES_AGAIN','firstPreMigrationBackupId'=>$this->state['firstPreMigrationBackup']['id']??null]);return false;
        }
        $this->save('RESTORED_CLAMPED');$this->emit('RESTORED',['clamp'=>'EXACT_PRIVATE_COPY','env'=>'EXACT_PRIVATE_COPY_OR_UNCHANGED','configShow'=>'enabled=false checkout_enabled=false','storefront'=>'CLOSED']);return true;
    }

    private function hold(string $phase,array $report):int
    {
        $this->save($phase);$this->emit('HOLD_NO_MUTATION',['phase'=>$phase,'blockers'=>$report['blockers'],'firstPreMigrationBackupId'=>$this->state['firstPreMigrationBackup']['id']??null]);$this->preflightRecovery($report);return 2;
    }

    private function finish():int
    {
        $first=$this->state['firstPreMigrationBackup']??null;
        $this->emit('B1145_RESULT',['migrations'=>'71_RAN_0_PENDING','runtimeClamp'=>'REMOVED_CONFIG_IS_STAGED_ORIGINAL','env'=>['PUBLIC_STOREFRONT_ENABLED'=>true,'PUBLIC_CHECKOUT_ENABLED'=>true],'storefront'=>'CLOSED_DB_SWITCH_ABSENT_OR_0','backupId'=>$this->state['backup']['id']??null,'firstPreMigrationBackupId'=>$first['id']??null]);
        $present=null;if($first!==null)try{$present=in_array($first['id'],array_column($this->appState('state')['backups']['completedRuns'],'id'),true);}catch(Throwable){}
        if($first!==null)$this->emit('FIRST_PRE_MIGRATION_BACKUP',['id'=>$first['id'],'key'=>$first['key'],'sizeBytes'=>$first['sizeBytes'],'ranAtBackup'=>self::RAN_BEFORE,'stillOnServer'=>$present,'horizon'=>'THE_APP_KEEPS_ONLY_THE_2_NEWEST_VERIFIED_BACKUPS_THE_NIGHTLY_02_30_BACKUPS_REMOVE_IT_DOWNLOAD_OR_COPY_IT_OFF_THE_SERVER_RIGHT_AFTER_THIS_RUN']);
        $this->emit('OWNER_NEXT_STEPS',['1_ADMIN_LOGIN_CMS_ALD1N_COM','2_CONFIRM_MERCHANT_REVIEW_APPROVED','3_TURN_ON_THE_PUBLIC_STOREFRONT_SWITCH_IN_THE_ADMIN_UI','4_CHECK_KATALOG_200_AND_ONE_TEST_ORDER','EMERGENCY_OFF_IS_THE_SAME_ADMIN_SWITCH']);
        $this->emit('PERMANENT_PRIVATE_DATA_DEPENDENCY_DO_NOT_PRUNE',$this->retained());$this->emit('DO_NOT_RERUN_B1144_AFTER_B1145','ITS_CLAMP_AND_ENV_DIGESTS_ARE_NOW_OUTDATED');return 0;
    }

    /** Re-run after the terminal PASS: re-verify only; the DB switch may already be ON by the owner and is reported, not gated. */
    private function closedRerun():int
    {
        $p=$this->preflight('closed');if($p['blockers']!==[]){$this->emit('HOLD_CLOSED_UNCLAMPED_REVERIFY',$p['blockers']);$this->preflightRecovery($p);return 2;}
        $shown=$this->configShow();$this->emit('CONFIG_SHOW_PUBLIC_STOREFRONT',$shown);if($shown!==['enabled'=>true,'checkout_enabled'=>true]){$this->emit('HOLD_CLOSED_UNCLAMPED_REVERIFY',['CONFIG_SHOW_NOT_UNCLAMPED']);return 2;}
        $this->emit('ALREADY_UNCLAMPED_NO_REPEAT',true);return $this->finish();
    }

    public function run():int
    {
        try{$this->open();$existing=$this->loadExisting();if(!$existing)$this->begin();}catch(Throwable $e){$this->emit('HOLD_BEFORE_ANY_MUTATION',self::code($e));return 2;}
        try{
            $phase=$this->state['phase'];if($phase==='CLOSED_UNCLAMPED')return $this->closedRerun();
            if(in_array($phase,['PREFLIGHT','HOLD_PREFLIGHT_BLOCKED'],true)){
                $this->cleanupRecorded();$p=$this->preflight('fresh');if($p['blockers']!==[])return $this->hold('HOLD_PREFLIGHT_BLOCKED',$p);$this->admit();$this->save('PREFLIGHT_PASSED');
                $this->backup(self::RAN_BEFORE);$this->migrate(self::PENDING,true,self::RAN_BEFORE);
            }elseif(in_array($phase,self::MIGRATE_PHASES,true)){
                $this->cleanupRecorded();$p=$this->preflight('migrate');if($p['blockers']!==[])return $this->hold('HOLD_MIGRATE_PREFLIGHT_BLOCKED',$p);
                // Every run that will execute migrate --force takes its own fresh backup first.
                $pending=$p['values']['migrations']['pending'];$ran=$p['values']['migrations']['ran'];if($pending!==[])$this->backup($ran);$this->migrate($pending,false,$ran);
            }elseif(in_array($phase,self::IN_FLIGHT,true)){
                $this->emit('RESUMED_INTERRUPTED_UNCLAMP',['phase'=>$phase]);if(!$this->restore('RESUMED_INTERRUPTED_'.$phase))return 2;
            }elseif(!in_array($phase,self::UNCLAMP_READY,true))throw new RuntimeException('UNKNOWN_SAVED_PHASE');
            $this->cleanupRecorded();$p=$this->preflight('unclamp');if($p['blockers']!==[])return $this->hold('HOLD_UNCLAMP_PREFLIGHT_BLOCKED',$p);
            try{$this->unclamp();$this->accept();}catch(Throwable $e){$code=self::code($e);$this->emit('UNCLAMP_OR_ACCEPTANCE_FAILED',$code);$this->restore($code);return 2;}
            return $this->finish();
        }catch(Throwable $e){$this->emit('HOLD_CODE_DETAIL',self::code($e));return 2;}
    }
}
