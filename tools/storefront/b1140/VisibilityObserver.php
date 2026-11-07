<?php
declare(strict_types=1);
/** Diagnostic only. Never substitutes for the installer's cache/group/drain gates. */
final class VisibilityObserver
{
    private static function boundedProcess(array $command,int $budgetMilliseconds,int $limit):array
    {
        $pipes=[];$process=@proc_open($command,[1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($process))return ['state'=>'unavailable','rc'=>null,'output'=>''];
        foreach($pipes as $pipe)stream_set_blocking($pipe,false);$output='';$bytes=0;$state='complete';$rc=null;$deadline=hrtime(true)+max(1,$budgetMilliseconds)*1000000;
        $drain=static function()use($pipes,&$output,&$bytes,&$state,$limit):bool{foreach($pipes as $number=>$pipe){$part=stream_get_contents($pipe,$limit+1);if(!is_string($part)){$state='unreadable';return false;}$bytes+=strlen($part);if($bytes>$limit){$state='overflow';return false;}if($number===1)$output.=$part;}return true;};
        try{
            do{
                if(!$drain())break;
                $status=proc_get_status($process);if(!$status['running']){if(!$drain())break;$rc=$status['exitcode'];break;}if(hrtime(true)>=$deadline){$state='timeout';break;}usleep(1000);
            }while(true);
            if($state!=='complete'){@proc_terminate($process,9);$output='';}
        }finally{foreach($pipes as $pipe)fclose($pipe);$closed=proc_close($process);if($rc===null&&$state==='complete')$rc=$closed;}
        return ['state'=>$state,'rc'=>$rc,'output'=>$output];
    }
    private static function processSnapshot(int $pid,int $budgetMilliseconds):array
    {
        $read=self::boundedProcess(['ps','-ww','-u',(string)posix_geteuid(),'-o','pid=,comm=,args='],$budgetMilliseconds,1048576);$result=['state'=>$read['state'],'rc'=>$read['rc'],'targetListed'=>null,'targetCommCategory'=>null];
        if($read['state']!=='complete'||$read['rc']!==0)return $result;$result['targetListed']=false;
        foreach(explode("\n",trim($read['output'])) as $line){if(!preg_match('/^\s*(\d+)\s+(\S+)\s+(.*)$/D',$line,$m)){$result['state']='parse_failed';$result['targetListed']=null;$result['targetCommCategory']=null;return $result;}if((int)$m[1]===$pid){$result['targetListed']=true;$result['targetCommCategory']=$m[2]==='lsphp'?'lsphp':(preg_match('/php/i',$m[2])?'other_php':'other');}}
        return $result;
    }
    private static function target(int $pid):array
    {
        if($pid<=0)return ['category'=>'invalid','detail'=>null,'commIsLsphp'=>false];
        $directory='/proc/'.$pid;if(!is_dir($directory))return ['category'=>'absent','detail'=>null,'commIsLsphp'=>false];
        $comm=@file_get_contents($directory.'/comm',false,null,0,128);
        try{$detail=HostProcessGuard::identity($pid);}catch(Throwable $e){return ['category'=>'unreadable_or_foreign','detail'=>null,'commIsLsphp'=>false];}
        if($detail===null||!is_string($comm))return ['category'=>'absent_or_unreadable','detail'=>null,'commIsLsphp'=>false];
        $comm=trim($comm);return ['category'=>$comm==='lsphp'?'lsphp':(preg_match('/php/i',$comm)?'other_php':'other'),'detail'=>$detail,'commIsLsphp'=>$comm==='lsphp'];
    }
    private static function sample(array &$trace,mixed $marker,?array $target,string $nonce,?string $namespace,string $suffix,?array &$identity,array &$matched,bool &$idle,?array $after=null):void
    {
        if(!is_array($marker)){$trace['partialOrMalformedMarkerReads']++;return;}
        $stage=$marker['stage']??null;$known=is_string($stage)&&in_array($stage,['execution','shutdown','done'],true);if($known)$trace['rawStages'][$stage]=true;
        $nonceOK=($marker['nonce']??null)===$nonce;$uidOK=($marker['uid']??null)===posix_geteuid();$nsOK=is_string($namespace)&&$namespace!==''&&is_string($marker['pidNamespace']??null)&&$marker['pidNamespace']===$namespace;
        foreach(['nonceMatchSeen'=>$nonceOK,'uidMatchSeen'=>$uidOK,'namespaceMatchSeen'=>$nsOK] as $key=>$value)if($value)$trace[$key]=true;
        if(!$nonceOK||!$uidOK||!$nsOK||!$known){$trace['rejectedMarkerReads']++;return;}$trace['acceptedStages'][$stage]=true;
        if(!is_int($marker['pid']??null)||$marker['pid']<=0){$trace['invalidTargetPIDReads']++;return;}$trace['targetPID']=$marker['pid'];
        if($target!==null){$trace['targetCommCategories'][$target['category']]=true;if($target['commIsLsphp'])$trace['targetCommLsphpSeen']=true;$detail=$target['detail'];
            if(is_array($detail)){
                $trace['targetIdentityReadableSeen']=true;$suffixMatches=str_ends_with($detail['argv0'],':'.$suffix);if($suffixMatches)$trace['exact45SuffixMatchSeen']=true;$trace['titleHasColonSeen']=$trace['titleHasColonSeen']||str_contains($detail['argv0'],':');if(strlen($detail['argv0'])>=46)$trace['titleAtLeast46BytesSeen']=true;if($suffixMatches&&strlen($detail['argv0'])>46)$trace['nonemptyIdlePrefixSeen']=true;
                if($target['commIsLsphp']&&$suffixMatches){
                    if($identity!==null&&($identity['pid']!==$marker['pid']||$identity['start']!==$detail['start']||$identity['executable']!==$detail['executable'])){$trace['pidStartStable']=false;return;}
                    $identity=$detail+['pid'=>$marker['pid'],'idleArgv0'=>substr($detail['argv0'],0,-46)];$trace['matchingIdentitySeen']=true;
                    if(in_array($stage,['execution','shutdown'],true)){$matched[$stage]=true;$trace['processMatchedStages'][$stage]=true;}
                }
            }
        }
        if($identity!==null&&$stage==='done'){
            $detail=($after??$target)['detail']??null;if(!is_array($detail))return;
            if($detail['start']!==$identity['start']||$detail['executable']!==$identity['executable']){$trace['pidStartStable']=false;return;}
            if($detail['argv0']===$identity['idleArgv0']){$idle=true;$trace['idleTransitionObserved']=true;}
        }
    }
    private static function initial():array
    {
        return ['observationOnly'=>true,'deploymentApproval'=>false,'collectionComplete'=>false,'calibrationWouldPass'=>null,'exactFrozenCalibrationExecuted'=>false,'frozenLoopPredicateSatisfied'=>null,'observedLoopExit'=>false,'heldExecutionAndShutdownSeen'=>false,'psSnapshot'=>null,'psSampledThroughout'=>false,'markerSeen'=>false,'partialOrMalformedMarkerReads'=>0,'rejectedMarkerReads'=>0,'invalidTargetPIDReads'=>0,'rawStages'=>[],'acceptedStages'=>[],'processMatchedStages'=>[],'nonceMatchSeen'=>false,'uidMatchSeen'=>false,'namespaceMatchSeen'=>false,'localNamespaceReadable'=>false,'targetPID'=>null,'targetCommCategories'=>[],'targetCommLsphpSeen'=>false,'targetIdentityReadableSeen'=>false,'exact45SuffixMatchSeen'=>false,'titleHasColonSeen'=>false,'titleAtLeast46BytesSeen'=>false,'nonemptyIdlePrefixSeen'=>false,'matchingIdentitySeen'=>false,'pidStartStable'=>true,'idleTransitionObserved'=>false,'transportCompleted'=>false,'httpStatus'=>null,'curlRC'=>null,'curlMultiRC'=>null,'responseScopeValid'=>false,'bodyOverflow'=>false,'timedOut'=>false,'samples'=>0];
    }
    public static function observe(CurlHandle $curl,string $private,array $config,string $nonce):array
    {
        $trace=self::initial();$namespace=@readlink('/proc/self/ns/pid');$namespace=is_string($namespace)&&$namespace!==''?$namespace:null;$trace['localNamespaceReadable']=$namespace!==null;
        $markerPath=$private.'/visibility-'.$nonce.'.json';$suffix=substr($config['activeRoot'].'/public/index.php',-45);$body='';$overflow=false;
        curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>false,CURLOPT_CONNECTTIMEOUT_MS=>5000,CURLOPT_TIMEOUT_MS=>15000,CURLOPT_WRITEFUNCTION=>static function($h,string $part)use(&$body,&$overflow):int{if(strlen($body)+strlen($part)>16384){$overflow=true;return 0;}$body.=$part;return strlen($part);}]);
        $multi=curl_multi_init();curl_multi_add_handle($multi,$curl);$started=hrtime(true);$deadline=$started+15000000000;$identity=null;$matched=[];$idle=false;$running=1;
        try{
            do{
                $mrc=curl_multi_exec($multi,$running);$trace['curlMultiRC']=$mrc;
                while(($info=curl_multi_info_read($multi))!==false)if(($info['msg']??null)===CURLMSG_DONE){$trace['transportCompleted']=true;$trace['curlRC']=$info['result'];$trace['httpStatus']=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);}
                $marker=null;$target=null;clearstatcache(true,$markerPath);
                if(is_file($markerPath)&&!is_link($markerPath)){$trace['markerSeen']=true;if(filesize($markerPath)<=2048){$raw=@file_get_contents($markerPath,false,null,0,2049);if(is_string($raw)&&strlen($raw)<=2048){try{$marker=json_decode($raw,true,16,JSON_THROW_ON_ERROR);}catch(Throwable $e){}}}if(is_array($marker)&&is_int($marker['pid']??null))$target=self::target($marker['pid']);$after=is_array($marker)&&($marker['stage']??null)==='done'&&$identity!==null?self::target($identity['pid']):null;self::sample($trace,$marker,$target,$nonce,$namespace,$suffix,$identity,$matched,$idle,$after);}
                if($trace['psSnapshot']===null&&$trace['targetPID']!==null){$remainingMilliseconds=max(1,(int)(($deadline-hrtime(true))/1000000));$trace['psSnapshot']=self::processSnapshot($trace['targetPID'],min(500,$remainingMilliseconds));}
                $trace['samples']++;if($mrc!==CURLM_OK){$trace['curlMultiFailure']=true;break;}
                if(!$running&&$idle)break;
                $remaining=$deadline-hrtime(true);if($remaining<=0){$trace['timedOut']=true;break;}usleep((int)min(100000,intdiv($remaining,1000)));
            }while(true);
            $status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$trace['httpStatus']=$status===0?null:$status;if(!$trace['transportCompleted']){$error=curl_errno($curl);$trace['curlRC']=$error===0?null:$error;$trace['transportCompleted']=$running===0;}
            $trace['bodyOverflow']=$overflow;
            try{$receipt=json_decode($body,true,32,JSON_THROW_ON_ERROR);$trace['responseScopeValid']=is_array($receipt)&&($receipt['status']??null)==='ok'&&($receipt['id']??null)===$config['id']&&($receipt['nonce']??null)===$nonce&&($receipt['uid']??null)===posix_geteuid()&&($receipt['actualIndex']??false)===true&&($receipt['librarySHA256']??null)===$config['librarySHA256']&&($receipt['bridgeSHA256']??null)===$config['bridgeSHA256'];}catch(Throwable $e){}
            $trace['observedLoopExit']=!$running&&$idle;$trace['heldExecutionAndShutdownSeen']=isset($matched['execution'],$matched['shutdown']);
            // This diagnostic does not execute the unchanged original calibration.
            $trace['frozenLoopPredicateSatisfied']=null;$trace['calibrationWouldPass']=null;
            $trace['collectionComplete']=true;
        }finally{curl_multi_remove_handle($multi,$curl);curl_multi_close($multi);curl_close($curl);$trace['elapsedSeconds']=round((hrtime(true)-$started)/1e9,3);}
        foreach(['rawStages','acceptedStages','processMatchedStages','targetCommCategories'] as $key)$trace[$key]=array_keys($trace[$key]);
        $missing=[];foreach(['transportCompleted','responseScopeValid','nonceMatchSeen','uidMatchSeen','namespaceMatchSeen','targetCommLsphpSeen','exact45SuffixMatchSeen','matchingIdentitySeen','idleTransitionObserved'] as $key)if(!$trace[$key])$missing[]=$key;foreach(['execution','shutdown'] as $stage)if(!in_array($stage,$trace['processMatchedStages'],true))$missing[]='processMatchedStage_'.$stage;if($trace['httpStatus']!==200)$missing[]='http200';if($trace['curlRC']!==0)$missing[]='curlSuccess';if(!$trace['pidStartStable'])$missing[]='pidStartStable';if($trace['curlMultiRC']!==CURLM_OK)$missing[]='curlMultiSuccess';$ps=$trace['psSnapshot'];if(!is_array($ps)||$ps['state']!=='complete'||$ps['rc']!==0)$missing[]='psSnapshotUnproven';elseif($ps['targetListed']!==true)$missing[]='psTargetListed';elseif($ps['targetCommCategory']!=='lsphp')$missing[]='psTargetCommLsphp';$trace['blockedPredicates']=$missing;$trace['fullInstallerCacheAndGroupGatesExecuted']=false;return $trace;
    }
}
