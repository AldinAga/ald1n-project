<?php
declare(strict_types=1);
require $argv[1];require $argv[2];
$c=json_decode(file_get_contents($argv[3]),true,512,JSON_THROW_ON_ERROR);$old=$c['current'];$candidate=$c['candidate'];$retained=$c['retained'];$owner=$c['owner'];
if($c['action']==='journal'){$a=stat($old);$b=stat($candidate);$plan=['active'=>$old,'candidate'=>$candidate,'rollback'=>$retained,'oldIdentity'=>['dev'=>$a['dev'],'ino'=>$a['ino']],'candidateIdentity'=>['dev'=>$b['dev'],'ino'=>$b['ino']]];$owner=ReleasePrimitives::createJournal($c['journal'],$plan);echo json_encode(['plan'=>$plan,'owner'=>$owner],JSON_THROW_ON_ERROR);exit;}
if($c['action']==='switch'){$receipt=ReleasePrimitives::switchJournaled($old,$candidate,$retained,$c['journal'],$owner);echo json_encode($receipt,JSON_THROW_ON_ERROR);exit;}
if($c['action']==='rollback'){$result=ReleasePrimitives::recoverSwitch($c['plan'],$c['journal'],$owner,$c['failed']);echo json_encode(['result'=>$result],JSON_THROW_ON_ERROR);exit;}
if($c['action']==='census'){echo json_encode(InstallFileOps::phpCensus($c['base']),JSON_THROW_ON_ERROR);exit;}
if($c['action']==='maintenance'){$receipt=ReleasePrimitives::ownMaintenance($c['base'].'/storage',$owner,$c['expires'],$c['started'],['library'=>$c['library'],'envelope'=>$c['envelope'],'frontSHA256'=>$c['frontSHA256']]);echo json_encode($receipt,JSON_THROW_ON_ERROR);exit;}
if($c['action']==='private'){$receipt=ReleasePrimitives::preservePrivate($old,$candidate,$retained,['public/storage','public/uploads']);$original=file_get_contents($candidate.'/config/public_storefront.php');$clamped=ReleasePrimitives::offConfig($original,hash('sha256',$original));file_put_contents($candidate.'/config/public_storefront.php',$clamped);echo json_encode($receipt,JSON_THROW_ON_ERROR);exit;}
if($c['action']==='release'){ReleasePrimitives::releaseMaintenance($c['receipt'],$c['base'].'/storage');echo '{}';exit;}
throw new RuntimeException('UNKNOWN_FIXTURE_ACTION');
