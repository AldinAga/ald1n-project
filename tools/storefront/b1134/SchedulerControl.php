<?php
declare(strict_types=1);

/** First-party scheduler cache control only. Always boots the retained ORIGINAL application. */
final class SchedulerControl
{
    public static function execute(string $base,string $action,string $owner,array $identity,string $envSHA256,?array $previous):array
    {
        ini_set('display_errors','0');ini_set('log_errors','0');
        if(!in_array($action,['pause','verify','release'],true)||!preg_match('/^[a-f0-9]{64}$/D',$owner)||is_link($base)||realpath($base)!==$base)throw new RuntimeException('SCHEDULER_INPUT');
        $stat=stat($base);if($stat['dev']!==$identity['dev']||$stat['ino']!==$identity['ino']||is_link($base.'/.env')||hash_file('sha256',$base.'/.env')!==$envSHA256)throw new RuntimeException('SCHEDULER_SOURCE_PROVENANCE');
        require $base.'/vendor/autoload.php';$app=require $base.'/bootstrap/app.php';$kernel=$app->make(\Illuminate\Contracts\Console\Kernel::class);$kernel->bootstrap();
        if($app->basePath()!==$base||$app->storagePath()!==$base.'/storage'||$app->getCachedConfigPath()!==$base.'/bootstrap/cache/config.php'||is_file($app->getCachedConfigPath()))throw new RuntimeException('UNSUPPORTED_CONFIG_CACHE');
        foreach(['APP_CONFIG_CACHE','APP_BASE_PATH','APP_ROUTES_CACHE','APP_SERVICES_CACHE','APP_PACKAGES_CACHE','VIEW_COMPILED_PATH'] as $key)if(is_string(env($key))&&env($key)!=='')throw new RuntimeException('UNSUPPORTED_CACHE_OR_BASE_OVERRIDE');
        $driver=(string)config('cache.default');if(!in_array($driver,['redis','file','database','memcached'],true))throw new RuntimeException('NONPERSISTENT_OR_UNSUPPORTED_CACHE');
        $repository=$app->make('cache')->store();$store=$repository->getStore();if(!$store instanceof \Illuminate\Contracts\Cache\LockProvider)throw new RuntimeException('CACHE_OWNERSHIP_UNSUPPORTED');
        $backend=['driver'=>$driver,'store'=>config('cache.stores.'.$driver),'prefix'=>config('cache.prefix')];
        if($driver==='file'){
            foreach(['path','lock_path'] as $field){$configured=config('cache.stores.file.'.$field);if($configured===null&&$field==='lock_path'){$backend['store'][$field]=null;continue;}$path=is_string($configured)?realpath($configured):false;$s=is_string($path)?stat($path):false;if(!is_array($s))throw new RuntimeException('CACHE_PATH');$backend['store'][$field]=['dev'=>$s['dev'],'ino'=>$s['ino']];}
        }
        if($driver==='redis')$backend['connection']=config('database.redis');if($driver==='database')$backend['connection']=config('database.connections.'.config('database.default'));
        $fingerprint=hash('sha256',serialize($backend));
        if($previous!==null&&$previous['backendFingerprint']!==$fingerprint)throw new RuntimeException('CACHE_BACKEND_DRIFT');
        $lockName='ald1n:release:installer';$marker='ald1n:release:scheduler-owner';$pauseKey='illuminate:schedule:paused';
        $lock=$repository->lock($lockName,3600,$owner);
        if(!$lock->isOwnedByCurrentProcess()&&!$lock->get())throw new RuntimeException('SCHEDULER_OWNED_BY_ANOTHER_OPERATION');
        $paused=$repository->get($pauseKey,false);if(!is_bool($paused))throw new RuntimeException('SCHEDULER_STATE_AMBIGUOUS');
        $existingOwner=$repository->get($marker);
        $terminalKey='ald1n:release:scheduler-restored:'.$owner;
        $terminal=$repository->get($terminalKey);
        if($action==='release'&&$terminal!==null){
            if(!is_array($terminal)||($terminal['owner']??null)!==$owner||($terminal['backendFingerprint']??null)!==$fingerprint||!is_bool($terminal['priorPaused']??null))throw new RuntimeException('SCHEDULER_TERMINAL_RECEIPT');
            if($existingOwner===null){
                if($paused!==$terminal['priorPaused'])throw new RuntimeException('SCHEDULER_STATE_CHANGED_AFTER_RESTORE');
                if(!$lock->release())throw new RuntimeException('SCHEDULER_LOCK_RELEASE');
                return ['restoredPriorPaused'=>$terminal['priorPaused'],'backendFingerprint'=>$fingerprint,'ownedRestore'=>true,'alreadyRestored'=>true];
            }
        }
        if($action==='pause'){
            if($existingOwner!==null){
                if(!is_array($existingOwner)||($existingOwner['owner']??null)!==$owner||($existingOwner['backendFingerprint']??null)!==$fingerprint)throw new RuntimeException('EXISTING_SCHEDULER_OWNER_MARKER');
                $receipt=$existingOwner;
                if(!$paused&&$kernel->call('schedule:pause',['--no-interaction'=>true,'--no-ansi'=>true])!==0)throw new RuntimeException('FIRST_PARTY_PAUSE_FAILED');
                if($repository->get($pauseKey)!==true)throw new RuntimeException('PAUSE_NOT_EFFECTIVE');return $receipt;
            }
            $receipt=['priorPaused'=>$paused,'pausedByInstaller'=>!$paused,'backendFingerprint'=>$fingerprint,'driver'=>$driver,'owner'=>$owner,'technicalApplicationBootstrap'=>true];
            if(!$paused){if($repository->forever($marker,$receipt)!==true||$repository->get($marker)!==$receipt)throw new RuntimeException('SCHEDULER_OWNER_NOT_DURABLE');if($kernel->call('schedule:pause',['--no-interaction'=>true,'--no-ansi'=>true])!==0)throw new RuntimeException('FIRST_PARTY_PAUSE_FAILED');}
            if($repository->get($pauseKey)!==true)throw new RuntimeException('PAUSE_NOT_EFFECTIVE');return $receipt;
        }
        if($previous===null&&$action==='release')$previous=is_array($existingOwner)?$existingOwner:['priorPaused'=>$paused,'pausedByInstaller'=>false,'backendFingerprint'=>$fingerprint,'owner'=>$owner];
        if($previous===null)throw new RuntimeException('SCHEDULER_RECEIPT_MISSING');
        if($previous['pausedByInstaller']&&(!is_array($existingOwner)||($existingOwner['owner']??null)!==$owner||($existingOwner['backendFingerprint']??null)!==$fingerprint))throw new RuntimeException('SCHEDULER_OWNERSHIP_CHANGED');
        if($paused!==true&&$action!=='release')throw new RuntimeException('SCHEDULER_PAUSE_CHANGED_EXTERNALLY');
        if($action==='release'){
            if($previous['pausedByInstaller']){if($paused&&$kernel->call('schedule:resume',['--no-interaction'=>true,'--no-ansi'=>true])!==0)throw new RuntimeException('FIRST_PARTY_RESUME_FAILED');if($repository->get($pauseKey,false)!==false)throw new RuntimeException('RESUME_NOT_EFFECTIVE');}
            // Commit restore evidence BEFORE deleting ownership: a killed parent can safely re-enter.
            $terminalReceipt=['owner'=>$owner,'backendFingerprint'=>$fingerprint,'priorPaused'=>$previous['priorPaused']];
            if($repository->forever($terminalKey,$terminalReceipt)!==true||$repository->get($terminalKey)!==$terminalReceipt)throw new RuntimeException('SCHEDULER_RESTORE_NOT_DURABLE');
            if($previous['pausedByInstaller'])$repository->forget($marker);
            if(!$lock->release())throw new RuntimeException('SCHEDULER_LOCK_RELEASE');return ['restoredPriorPaused'=>$previous['priorPaused'],'backendFingerprint'=>$fingerprint,'ownedRestore'=>true];
        }
        return ['paused'=>true,'backendFingerprint'=>$fingerprint,'ownershipVerified'=>true];
    }
}

if(realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){
    try{
        if(PHP_SAPI!=='cli'||realpath(__FILE__)!=='/home/icaffeco/ald1n-project/incoming/b1134-scheduler.php')throw new RuntimeException('SCHEDULER_CANONICAL_ENTRY');
        $path=$argv[1]??'';if(is_link($path)||realpath($path)!==$path||filesize($path)>65536)throw new RuntimeException('SCHEDULER_COMMAND');
        $command=json_decode(file_get_contents($path),true,512,JSON_THROW_ON_ERROR);$result=SchedulerControl::execute($command['base'],$command['action'],$command['owner'],$command['identity'],$command['envSHA256'],$command['previous']);
        echo json_encode(['status'=>'ok','receipt'=>$result],JSON_THROW_ON_ERROR);exit(0);
    }catch(Throwable $error){echo '{"status":"HOLD_SCHEDULER_CONTROL"}';exit(2);}
}
