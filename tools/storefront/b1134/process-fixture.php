<?php
declare(strict_types=1);
require $argv[1];$uid=posix_geteuid();$exe='/opt/alt/php84/usr/bin/lsphp';
$group=['idleArgv0'=>'lsphp','parent'=>['pid'=>20,'uid'=>$uid,'executable'=>$exe],'installer'=>['pid'=>10,'start'=>'1','uid'=>$uid,'executable'=>'/usr/bin/php']];
$worker=['start'=>'2','uid'=>$uid,'executable'=>$exe,'ppid'=>20,'argv0'=>'lsphp'];$process=['comm'=>'lsphp','args'=>'lsphp'];$positive=0;$negative=0;
function proof(bool $condition):void {if(!$condition)throw new RuntimeException('PROCESS_FIXTURE');}
proof(HostProcessGuard::classify(21,$process,$worker,$group)==='idle');$positive++;
foreach(['lsphp:/cms/public/index.php','lsphp:/cms/public/OTHER.php'] as $title){$d=$worker;$d['argv0']=$title;proof(HostProcessGuard::classify(21,$process,$d,$group)==='busy');$positive++;}
foreach([
    [21,$process,array_replace($worker,['ppid'=>999])],
    [21,$process,array_replace($worker,['executable'=>'/other/lsphp'])],
    [21,$process,array_replace($worker,['argv0'=>'lsphp:'.str_repeat('x',46)])],
    [99,['comm'=>'php','args'=>'php custom.php'],['start'=>'7','uid'=>$uid,'executable'=>'/usr/bin/php','ppid'=>1,'argv0'=>'php']],
    [99,['comm'=>'php8.4','args'=>'php8.4 -r arbitrary'],['start'=>'7','uid'=>$uid,'executable'=>'/usr/bin/php8.4','ppid'=>1,'argv0'=>'php8.4']],
    [10,['comm'=>'php','args'=>'php installer'],['start'=>'REUSED','uid'=>$uid,'executable'=>'/usr/bin/php','ppid'=>1,'argv0'=>'php']],
] as [$pid,$p,$detail]){try{HostProcessGuard::classify($pid,$p,$detail,$group);throw new LogicException('UNKNOWN_PHP_NOT_REJECTED');}catch(RuntimeException $error){$negative++;}}
$actual=HostProcessGuard::identity(getmypid());proof($actual!==null&&$actual['uid']===$uid&&ctype_digit($actual['start']));$positive++;
echo json_encode(['classificationPositives'=>$positive,'classificationNegatives'=>$negative,'actualOwnProcIdentity'=>true,'actualLSPHPGroup'=>'HOST_ONLY_CONDITIONAL_GATE','productionExecution'=>false],JSON_PRETTY_PRINT).PHP_EOL;
