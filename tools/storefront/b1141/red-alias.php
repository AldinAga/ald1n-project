<?php
declare(strict_types=1);
require $argv[1].'/VisibilityObserver.php';
$root=getenv('RUNNER_TEMP').'/b1141-alias-red';mkdir($root,0700);mkdir($root.'/long-canonical-project-path',0700);$index=$root.'/long-canonical-project-path/index.php';file_put_contents($index,'<?php return;');$alias=$root.'/front.php';symlink($index,$alias);
if(realpath($alias)!==$index)throw new RuntimeException('FIXTURE_ALIAS_BINDING');
$initial=new ReflectionMethod(VisibilityObserver::class,'initial');$sample=new ReflectionMethod(VisibilityObserver::class,'sample');$trace=$initial->invoke(null);$identity=null;$matched=[];$idle=false;$nonce=str_repeat('a',32);$ns=readlink('/proc/self/ns/pid');$marker=['nonce'=>$nonce,'uid'=>posix_geteuid(),'pidNamespace'=>$ns,'pid'=>123,'stage'=>'execution'];$title='lsphp:'.substr($alias,-45);$target=['category'=>'lsphp','commIsLsphp'=>true,'detail'=>['argv0'=>$title,'start'=>'1','executable'=>'/fixture/lsphp','uid'=>posix_geteuid(),'ppid'=>1],'cmdline'=>$title."\0",'cmdlineState'=>'readable'];
$args=[&$trace,$marker,$target,$nonce,$ns,substr($index,-45),&$identity,&$matched,&$idle,null,['canonical'=>$index,'script_filename'=>$alias],0.1];$sample->invokeArgs(null,$args);
$seen=$trace['phaseFacts']['execution']['titleShapes']??[];$matchedAlias=false;foreach($seen as $shape)foreach($shape['knownMatches']??[] as $m)if($m['candidate']==='script_filename'&&$m['form']==='tail45'&&$m['segment']==='first')$matchedAlias=true;
if(!$matchedAlias){echo 'VALIDATED_ALIAS_TITLE_NOT_CLASSIFIED'.PHP_EOL;exit(2);}echo 'VALIDATED_ALIAS_TITLE_CLASSIFIED'.PHP_EOL;
