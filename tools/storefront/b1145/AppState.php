<?php
declare(strict_types=1);
/**
 * B1145 read-only application state, run as its own uncached CLI process: php -d opcache.enable=0 b1145-app-state.php
 * <active tree> state | backup <key>. It boots the CMS console kernel exactly as artisan does, refuses a cached
 * configuration and prints ONE JSON object with booleans, counts and names only (never credentials, paths of private
 * data or raw errors). It only SELECTs and introspects the schema; it never writes to the database.
 * @author Ald1n <https://ald1n.com>
 */
$fail=static function(string $code):never{echo json_encode(['ok'=>false,'error'=>$code]).PHP_EOL;exit(2);};
[$self,$base,$mode,$arg]=$argv+[null,null,null,null];
if(PHP_SAPI!=='cli'||!is_string($base)||realpath($base)!==$base||!is_file($base.'/artisan')||!in_array($mode,['state','backup'],true))$fail('APP_STATE_ARGUMENTS');
if($mode==='backup'&&!preg_match('/^\d{8}-\d{6}-manual-[a-f0-9]{6}$/D',(string)$arg))$fail('APP_STATE_BACKUP_KEY');
if(is_file($base.'/bootstrap/cache/config.php'))$fail('APP_STATE_CONFIG_CACHED');
try{
    chdir($base);require $base.'/vendor/autoload.php';$app=require $base.'/bootstrap/app.php';
    if(method_exists($app,'configurationIsCached')&&$app->configurationIsCached())$fail('APP_STATE_CONFIG_CACHED');
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $db=$app->make('db')->connection();$schema=$db->getSchemaBuilder();$db->select('select 1 as ok');
    if($mode==='backup'){
        $row=$db->table('backup_runs')->where('backup_key',$arg)->first(['id','status','size_bytes','backup_type']);
        echo json_encode(['ok'=>true,'found'=>$row!==null,'id'=>$row===null?null:(int)$row->id,'status'=>$row===null?null:(string)$row->status,'sizeBytes'=>$row===null?null:(int)$row->size_bytes,'type'=>$row===null?null:(string)$row->backup_type],JSON_THROW_ON_ERROR).PHP_EOL;exit(0);
    }
    $flag='absent';
    if($schema->hasTable('settings')){$rows=$db->table('settings')->where('setting_key','public_storefront_open')->limit(2)->pluck('setting_value')->all();if(count($rows)>1)$flag='other';elseif(count($rows)===1)$flag=in_array((string)$rows[0],['0','1'],true)?(string)$rows[0]:'other';}
    $backups=['table'=>$schema->hasTable('backup_runs'),'running'=>null,'lastCompletedSeconds'=>null,'lastCompletedSizeBytes'=>null];
    if($backups['table']){
        $backups['running']=(int)$db->table('backup_runs')->where('status','running')->where('started_at','>=',now()->subHours(6))->count();
        $last=$db->table('backup_runs')->where('status','completed')->whereNotNull('finished_at')->orderByDesc('started_at')->first(['started_at','finished_at','size_bytes']);
        if($last!==null){$backups['lastCompletedSeconds']=max(0,strtotime((string)$last->finished_at)-strtotime((string)$last->started_at));$backups['lastCompletedSizeBytes']=(int)$last->size_bytes;}
    }
    $columns=[];foreach(['merchant_policy_digest','delivery_method','customer_user_id','payment_due_at_snapshot'] as $column)$columns[$column]=$schema->hasTable('public_checkout_attempts')&&$schema->hasColumn('public_checkout_attempts',$column);
    $roles=['user'=>null,'customer'=>null];if($schema->hasTable('roles'))foreach(array_keys($roles) as $slug){$name=$db->table('roles')->where('slug',$slug)->value('name');$roles[$slug]=$name===null?null:(string)$name;}
    $backupPath=(string)config('backup.path');$backupPathExists=$backupPath!==''&&is_dir($backupPath);
    echo json_encode(['ok'=>true,'db'=>true,'configCached'=>false,'storefrontFlag'=>$flag,'storefrontConfig'=>['enabled'=>config('public_storefront.enabled'),'checkout_enabled'=>config('public_storefront.checkout_enabled')],'backups'=>$backups,'backupPathExists'=>$backupPathExists,'backupPathReal'=>$backupPathExists?realpath($backupPath):null,'tables'=>['orders'=>$schema->hasTable('orders'),'public_order_accesses'=>$schema->hasTable('public_order_accesses'),'public_checkout_attempts'=>$schema->hasTable('public_checkout_attempts')],'toggleColumns'=>$columns,'roles'=>$roles],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES).PHP_EOL;exit(0);
}catch(Throwable){$fail('APP_STATE_BOOT_OR_DATABASE_UNAVAILABLE');}
