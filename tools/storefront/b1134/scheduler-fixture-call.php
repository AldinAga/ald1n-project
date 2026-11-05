<?php
declare(strict_types=1);
require $argv[1];$c=json_decode(file_get_contents($argv[2]),true,512,JSON_THROW_ON_ERROR);
if($c['action']==='set-paused'){
    require $c['base'].'/vendor/autoload.php';$app=require $c['base'].'/bootstrap/app.php';$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();$app->make('cache')->store()->forever('illuminate:schedule:paused',$c['value']);echo '{}';exit;
}
try{$result=SchedulerControl::execute($c['base'],$c['action'],$c['owner'],$c['identity'],$c['envSHA256'],$c['previous']);echo json_encode(['status'=>'ok','receipt'=>$result],JSON_THROW_ON_ERROR);}catch(Throwable $error){echo json_encode(['status'=>'hold','type'=>get_class($error),'code'=>$error->getMessage()],JSON_THROW_ON_ERROR);exit(2);}
