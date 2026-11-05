<?php
declare(strict_types=1);require $argv[1];
$o=(new ReflectionClass(HostWebControl::class))->newInstanceWithoutConstructor();$config=['id'=>'job','phase'=>'installed','librarySHA256'=>str_repeat('a',64)];(new ReflectionProperty($o,'config'))->setValue($o,$config);$o->setGroup(['parent'=>['pid'=>1234]]);
$valid=$config+['status'=>'ok','sapi'=>'litespeed','uid'=>posix_geteuid(),'actualIndex'=>true,'pid'=>5678,'ppid'=>1234,'pidNamespace'=>readlink('/proc/self/ns/pid'),'sentinelPreviouslyCached'=>true,'cacheSharedWithRPC'=>true];$method=new ReflectionMethod($o,'validateReceipt');$method->invoke($o,$valid);$method->invoke($o,$valid,true);$bad=[];
foreach(['ppid'=>9999,'pidNamespace'=>'pid:[foreign]','sentinelPreviouslyCached'=>false,'actualIndex'=>false] as $field=>$value){$data=$valid;$data[$field]=$value;try{$method->invoke($o,$data);$bad[]=$field;}catch(RuntimeException $error){}}
foreach(['ppid'=>9999,'pidNamespace'=>'pid:[foreign]','cacheSharedWithRPC'=>false,'actualIndex'=>false] as $field=>$value){$data=$valid;$data[$field]=$value;try{$method->invoke($o,$data,true);$bad[]='smoke-'.$field;}catch(RuntimeException $error){}}
if($bad)throw new RuntimeException('FOREIGN_RPC_RECEIPTS_ACCEPTED_'.implode('_',$bad));echo json_encode(['validBoundRPCAndGET'=>2,'foreignParentNamespaceCacheAndFrontRejected'=>8,'actualReceiptMethod'=>true]).PHP_EOL;
