<?php
declare(strict_types=1);
// CI-only bridge: the Python HTTP fixture drives the real B1134/B1144 libraries through this one JSON command.
$c=json_decode(file_get_contents($argv[1]),true,64,JSON_THROW_ON_ERROR);
require $c['b1134'].'/ReleasePrimitives.php';require $c['b1134'].'/InstallFileOps.php';foreach(['LeanGates','FreshnessProbe','LeanWeb'] as $class)require $c['b1144'].'/'.$class.'.php';
function web(array $c):LeanWeb {return new LeanWeb('cms.ald1n.com',(int)$c['loopbackPort'],(int)$c['publicPort'],$c['caFile'],'127.0.0.1');}
function artisan(string $base,string $command):array
{
    $p=proc_open(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0',$base.'/artisan',$command,'--no-interaction','--no-ansi'],[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$base,array_merge(getenv(),['COLUMNS'=>'240']));fclose($pipes[0]);$out=stream_get_contents($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);return ['rc'=>proc_close($p),'stdout'=>$out,'stderrTail'=>substr($err,-1500)];
}
try{
    $result=match($c['action']){
        'private'=>(function()use($c):array{$r=ReleasePrimitives::preservePrivate($c['current'],$c['candidate'],$c['retained'],['public/storage']);$config=$c['candidate'].'/config/public_storefront.php';$original=file_get_contents($config);$clamped=ReleasePrimitives::offConfig($original,hash('sha256',$original));file_put_contents($config,$clamped);return ['preserved'=>$r,'clampedSHA256'=>hash('sha256',$clamped)];})(),
        'journal'=>(function()use($c):array{$a=stat($c['current']);$b=stat($c['candidate']);$plan=['active'=>$c['current'],'candidate'=>$c['candidate'],'rollback'=>$c['retained'],'failed'=>$c['failed'],'oldIdentity'=>['dev'=>$a['dev'],'ino'=>$a['ino']],'candidateIdentity'=>['dev'=>$b['dev'],'ino'=>$b['ino']]];return ['plan'=>$plan,'owner'=>ReleasePrimitives::createJournal($c['journal'],$plan)];})(),
        'own'=>ReleasePrimitives::ownMaintenance($c['base'].'/storage',$c['owner'],$c['expires'],$c['started']),
        'release'=>(function()use($c):array{ReleasePrimitives::releaseMaintenance($c['receipt'],$c['base'].'/storage');return [];})(),
        'switch'=>ReleasePrimitives::switchJournaled($c['current'],$c['candidate'],$c['retained'],$c['journal'],$c['owner']),
        'rollback'=>['result'=>ReleasePrimitives::recoverSwitch($c['plan'],$c['journal'],$c['owner'],$c['failed'])],
        'status'=>(function()use($c):array{ob_start();try{web($c)->status($c['path'],(int)$c['expected'],$c['stamp']??null,bin2hex(random_bytes(4)),(int)($c['attempts']??1));return ['pass'=>true,'line'=>ob_get_clean()];}catch(RuntimeException $e){return ['pass'=>false,'code'=>$e->getMessage(),'line'=>ob_get_clean()];}})(),
        'probe-write'=>(function()use($c):array{FreshnessProbe::assertNoLeftover($c['current'].'/public');$p=FreshnessProbe::plan($c['id'],$c['roots'],array_keys($c['expected']));FreshnessProbe::write($c['current'].'/public/'.$p['name'],$p['source']);return ['name'=>$p['name'],'token'=>$p['token'],'sha256'=>$p['sha256']];})(),
        'probe-request'=>(function()use($c):array{ob_start();try{$r=web($c)->probe($c['name'],$c['token'],true);$route=$r['_route'];unset($r['_route']);$s=FreshnessProbe::validate($r,$c['id'],$c['expected'],$c['sapis'],true);return ['pass'=>true,'route'=>$route,'summary'=>$s,'output'=>ob_get_clean()];}catch(RuntimeException $e){return ['pass'=>false,'code'=>$e->getMessage(),'output'=>ob_get_clean()];}})(),
        'hold'=>(function()use($c):array{$lift=FreshnessProbe::liftNotBefore((int)$c['swappedAt'],(int)$c['probedAt'],$c['summaries']);return FreshnessProbe::hold($lift);})(),
        'probe-remove'=>['removed'=>FreshnessProbe::remove($c['current'].'/public',$c['name'],$c['sha256'])],
        'cli'=>(function()use($c):array{$about=artisan($c['current'],'about');$status=artisan($c['current'],'migrate:status');$parsed=$status['rc']===0?LeanGates::migrationStatus($status['stdout'],LeanGates::migrationFiles($c['current'].'/database/migrations')):null;return ['aboutRC'=>$about['rc'],'aboutStderr'=>$about['stderrTail'],'statusRC'=>$status['rc'],'statusTail'=>substr($status['stdout'],-600).$status['stderrTail'],'parsed'=>$parsed];})(),
        'logs-baseline'=>LeanGates::logBaseline($c['logs']),
        'logs-scan'=>LeanGates::newLogErrors($c['logs'],$c['baseline']),
    };
    echo json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){echo json_encode(['error'=>$e->getMessage(),'class'=>get_class($e)]);exit(3);}
