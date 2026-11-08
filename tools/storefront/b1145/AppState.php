<?php
declare(strict_types=1);
/**
 * B1145 read-only application state, run as its own uncached CLI process: php -d opcache.enable=0 b1145-app-state.php
 * <active tree> state | backup <key> | locks <seconds>. It boots the CMS console kernel exactly as artisan does, refuses a
 * cached configuration and prints ONE JSON object with booleans, counts, names, backup keys and timestamps only (never
 * credentials, paths of private data or raw errors). It only SELECTs and introspects the schema; it never writes.
 * @author Ald1n <https://ald1n.com>
 */
$fail=static function(string $code):never{echo json_encode(['ok'=>false,'error'=>$code]).PHP_EOL;exit(2);};
[$self,$base,$mode,$arg]=$argv+[null,null,null,null];
if(PHP_SAPI!=='cli'||!is_string($base)||realpath($base)!==$base||!is_file($base.'/artisan')||!in_array($mode,['state','backup','locks'],true))$fail('APP_STATE_ARGUMENTS');
if($mode==='backup'&&!preg_match('/^\d{8}-\d{6}-(?:manual|daily|weekly)-[a-f0-9]{6}$/D',(string)$arg))$fail('APP_STATE_BACKUP_KEY');
if($mode==='locks'&&(!preg_match('/^\d{1,3}$/D',(string)$arg)||(int)$arg<1))$fail('APP_STATE_LOCK_SECONDS');
if(is_file($base.'/bootstrap/cache/config.php'))$fail('APP_STATE_CONFIG_CACHED');
try{
    chdir($base);require $base.'/vendor/autoload.php';$app=require $base.'/bootstrap/app.php';
    if(method_exists($app,'configurationIsCached')&&$app->configurationIsCached())$fail('APP_STATE_CONFIG_CACHED');
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $db=$app->make('db')->connection();$schema=$db->getSchemaBuilder();$db->select('select 1 as ok');
    $stamp=static fn($value):?int=>$value===null||$value===''?null:Illuminate\Support\Carbon::parse((string)$value)->getTimestamp();
    $backupBase=(string)config('backup.path');$backupBaseReal=$backupBase!==''&&is_dir($backupBase)?realpath($backupBase):false;
    // A backup directory counts as present only inside the configured backup directory; its path is never printed.
    $present=static function(?string $path)use($backupBaseReal):bool{if($path===null||$path===''||$backupBaseReal===false)return false;$real=realpath($path);return is_string($real)&&is_dir($real)&&str_starts_with($real.'/',$backupBaseReal.'/')&&$real!==$backupBaseReal;};
    $run=static function(object $row)use($stamp,$present):array{$meta=json_decode((string)($row->metadata_json??''),true);return ['id'=>(int)$row->id,'key'=>(string)$row->backup_key,'type'=>(string)$row->backup_type,'status'=>(string)$row->status,'startedAt'=>$stamp($row->started_at),'finishedAt'=>$stamp($row->finished_at??null),'sizeBytes'=>$row->size_bytes===null?null:(int)$row->size_bytes,'dirPresent'=>$present($row->backup_path??null),'fileCount'=>is_array($meta)&&is_int($meta['file_count']??null)?$meta['file_count']:null];};
    $columns=['id','backup_key','backup_type','status','backup_path','size_bytes','started_at','finished_at','metadata_json'];
    if($mode==='backup'){
        $row=$db->table('backup_runs')->where('backup_key',$arg)->first($columns);
        echo json_encode(['ok'=>true,'found'=>$row!==null]+($row===null?['id'=>null,'status'=>null,'sizeBytes'=>null,'dirPresent'=>false]:$run($row)),JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES).PHP_EOL;exit(0);
    }
    if($mode==='locks'){
        // Other sessions of this database that are executing a statement, and (when readable) open InnoDB transactions of
        // other sessions; three consecutive clean one-second samples within the bound.
        $deadline=microtime(true)+(int)$arg;$clean=0;$samples=0;$busy=0;$maxActive=0;$trxReadable=true;$maxTrx=0;
        do{
            $active=(int)$db->selectOne("SELECT COUNT(*) AS n FROM information_schema.PROCESSLIST WHERE ID<>CONNECTION_ID() AND DB=DATABASE() AND COMMAND NOT IN ('Sleep','Daemon','Binlog Dump','Killed')")->n;
            $trx=null;try{$trx=(int)$db->selectOne('SELECT COUNT(*) AS n FROM information_schema.INNODB_TRX WHERE trx_mysql_thread_id<>CONNECTION_ID()')->n;}catch(Throwable){$trxReadable=false;}
            $samples++;$maxActive=max($maxActive,$active);$maxTrx=max($maxTrx,(int)$trx);
            if($active===0&&($trx===null||$trx===0)){if(++$clean>=3)break;}else{$clean=0;$busy++;}
            usleep(1000000);
        }while(microtime(true)<$deadline);
        echo json_encode(['ok'=>true,'clean'=>$clean>=3,'samples'=>$samples,'busySamples'=>$busy,'maxActiveOtherSessions'=>$maxActive,'innodbTrxReadable'=>$trxReadable,'maxOtherInnodbTrx'=>$trxReadable?$maxTrx:null],JSON_THROW_ON_ERROR).PHP_EOL;exit(0);
    }
    $flag='absent';
    if($schema->hasTable('settings')){$rows=$db->table('settings')->where('setting_key','public_storefront_open')->limit(2)->pluck('setting_value')->all();if(count($rows)>1)$flag='other';elseif(count($rows)===1)$flag=in_array((string)$rows[0],['0','1'],true)?(string)$rows[0]:'other';}
    $backups=['table'=>$schema->hasTable('backup_runs'),'running'=>null,'lastCompletedSeconds'=>null,'lastCompletedSizeBytes'=>null,'lastCompletedFileCount'=>null,'completedRuns'=>[],'runningRuns'=>[]];
    if($backups['table']){
        $backups['running']=(int)$db->table('backup_runs')->where('status','running')->where('started_at','>=',now()->subHours(6))->count();
        // Same order as the app's own retention (newest started first).
        foreach($db->table('backup_runs')->where('status','completed')->orderByDesc('started_at')->orderByDesc('id')->limit(10)->get($columns) as $row)$backups['completedRuns'][]=$run($row);
        foreach($db->table('backup_runs')->where('status','running')->orderByDesc('started_at')->orderByDesc('id')->limit(10)->get($columns) as $row)$backups['runningRuns'][]=$run($row);
        $last=$db->table('backup_runs')->where('status','completed')->whereNotNull('finished_at')->orderByDesc('started_at')->first(['started_at','finished_at','size_bytes','metadata_json']);
        if($last!==null){$backups['lastCompletedSeconds']=max(0,(int)$stamp($last->finished_at)-(int)$stamp($last->started_at));$backups['lastCompletedSizeBytes']=(int)$last->size_bytes;$meta=json_decode((string)($last->metadata_json??''),true);$backups['lastCompletedFileCount']=is_array($meta)&&is_int($meta['file_count']??null)?$meta['file_count']:null;}
    }
    $toggle=[];foreach(['merchant_policy_digest','delivery_method','customer_user_id','payment_due_at_snapshot'] as $column)$toggle[$column]=$schema->hasTable('public_checkout_attempts')&&$schema->hasColumn('public_checkout_attempts',$column);
    $roles=['user'=>null,'customer'=>null];if($schema->hasTable('roles'))foreach(array_keys($roles) as $slug){$name=$db->table('roles')->where('slug',$slug)->value('name');$roles[$slug]=$name===null?null:(string)$name;}
    // Columns, indexes and foreign keys of the two Storefront tables, from information_schema (names only, plus a row count).
    $tables=[];
    foreach(['public_order_accesses','public_checkout_attempts'] as $table){
        if(!$schema->hasTable($table)){$tables[$table]=['exists'=>false];continue;}
        $cols=array_map(static fn($r)=>(string)$r->c,$db->select('SELECT COLUMN_NAME AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION',[$table]));
        $indexes=[];foreach($db->select('SELECT INDEX_NAME AS n,NON_UNIQUE AS nu,COLUMN_NAME AS c FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY INDEX_NAME,SEQ_IN_INDEX',[$table]) as $r){$indexes[(string)$r->n]['unique']=(int)$r->nu===0;$indexes[(string)$r->n]['columns'][]=(string)$r->c;}
        $foreign=[];foreach($db->select('SELECT k.CONSTRAINT_NAME AS n,k.COLUMN_NAME AS c,k.REFERENCED_TABLE_NAME AS rt,k.REFERENCED_COLUMN_NAME AS rc,r.DELETE_RULE AS d FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME AND r.TABLE_NAME=k.TABLE_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME=? AND k.REFERENCED_TABLE_NAME IS NOT NULL ORDER BY k.CONSTRAINT_NAME,k.ORDINAL_POSITION',[$table]) as $r)$foreign[]=['name'=>(string)$r->n,'column'=>(string)$r->c,'referencedTable'=>(string)$r->rt,'referencedColumn'=>(string)$r->rc,'deleteRule'=>strtoupper((string)$r->d)];
        $tables[$table]=['exists'=>true,'columns'=>$cols,'indexes'=>$indexes,'foreignKeys'=>$foreign,'rows'=>(int)$db->table($table)->count()];
    }
    // The scheduled backup events exactly as the app's scheduler holds them (cron expression and effective timezone).
    $events=[];
    foreach($app->make(Illuminate\Console\Scheduling\Schedule::class)->events() as $event){
        $command=(string)($event->command??'');if(!str_contains($command,'app:backup-create'))continue;$tz=$event->timezone??null;
        $tz=$tz instanceof DateTimeZone?$tz->getName():(is_string($tz)&&$tz!==''?$tz:(string)config('app.timezone'));
        $events[]=['type'=>preg_match('/--type=(manual|daily|weekly)\b/',$command,$m)?$m[1]:'unknown','expression'=>(string)$event->expression,'timezone'=>$tz];
    }
    $dbBytes=(int)($db->selectOne('SELECT COALESCE(SUM(DATA_LENGTH+INDEX_LENGTH),0) AS b FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE()')->b??0);
    echo json_encode(['ok'=>true,'db'=>true,'configCached'=>false,'storefrontFlag'=>$flag,'storefrontConfig'=>['enabled'=>config('public_storefront.enabled'),'checkout_enabled'=>config('public_storefront.checkout_enabled')],'backups'=>$backups,'backupPathExists'=>$backupBaseReal!==false,'backupPathReal'=>$backupBaseReal===false?null:$backupBaseReal,
        'tables'=>['orders'=>$schema->hasTable('orders'),'public_order_accesses'=>$tables['public_order_accesses']['exists'],'public_checkout_attempts'=>$tables['public_checkout_attempts']['exists']],'toggleColumns'=>$toggle,'roles'=>$roles,'schema'=>$tables,
        'appTimezone'=>(string)config('app.timezone'),'backupSchedule'=>$events,'databaseBytes'=>$dbBytes],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES).PHP_EOL;exit(0);
}catch(Throwable){$fail('APP_STATE_BOOT_OR_DATABASE_UNAVAILABLE');}
