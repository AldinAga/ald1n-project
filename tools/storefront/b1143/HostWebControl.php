<?php
declare(strict_types=1);

final class HostWebControl
{
    private ?array $group=null;
    public function setGroup(array $group):void
    {
        $seal=$group['formatSeal']??null;unset($group['formatSeal']);if(!in_array($group['titleWidth']??null,[44,45],true)||($group['formatConformanceProven']??false)!==true||($group['heldExecutionAndShutdown']??false)!==true||($group['activeToIdleTransitionProven']??false)!==true||($group['calibrationId']??null)!==$this->config['id']||($group['librarySHA256']??null)!==$this->config['librarySHA256']||($group['pidNamespace']??null)!==readlink('/proc/self/ns/pid')||($group['executionProfile']??null)!=='UNCACHED_CMS'||($group['startupUncached']??false)!==true||($group['samePathFreshnessProven']??false)!==true||($group['profileSHA256']??null)!==$this->config['profileSHA256']||!is_string($seal)||!hash_equals(hash_hmac('sha256',json_encode($group,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),$this->key),$seal))throw new RuntimeException('CALIBRATED_FORMAT_GROUP_REQUIRED');$group['formatSeal']=$seal;$this->group=$group;
    }
    private function assertGroup():void {if($this->group!==null)HostProcessGuard::assertGroup($this->group);}
    public function __construct(private string $origin,private string $private,private array $config,private string $key,private string $token,private string $smokeToken='')
    {
        if($origin!=='https://cms.ald1n.com'||realpath($private)!==$private||(fileperms($private)&0777)!==0700)throw new RuntimeException('WEB_CLIENT_SCOPE');
    }

    public function setPhase(string $phase,array $files=[]):void
    {
        $this->config['phase']=$phase;$this->config['files']=$files;$this->config['expires']=time()+1500;
        $payload=json_encode($this->config,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);$envelope=json_encode(['payload'=>$payload,'hmac'=>hash_hmac('sha256',$payload,$this->key)],JSON_THROW_ON_ERROR);
        $temporary=$this->private.'/web.json.next';$handle=fopen($temporary,'x');if($handle===false)throw new RuntimeException('WEB_ENVELOPE_COLLISION');chmod($temporary,0600);if(fwrite($handle,$envelope)!==strlen($envelope))throw new RuntimeException('WEB_ENVELOPE_WRITE');fflush($handle);fclose($handle);if(!rename($temporary,$this->private.'/web.json'))throw new RuntimeException('WEB_ENVELOPE_RENAME');
    }

    private function handle(string $path,?array $payload,bool $authenticated):CurlHandle
    {
        if($path[0]!=='/'||str_contains($path,'://')||str_contains($path,"\r")||str_contains($path,"\n"))throw new RuntimeException('HTTP_PATH');
        $curl=curl_init($this->origin.$path);$headers=['Cache-Control: no-cache','Accept: application/json'];if($authenticated){$headers[]='X-Ald1n-Release-Token: '.$this->token;$headers[]='X-Ald1n-Release-Front: '.$this->token;}
        curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>35,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_HTTPHEADER=>$headers,CURLOPT_USERAGENT=>'Ald1n-B1134-owned-release']);
        if($payload!==null){$headers[]='Content-Type: application/json';curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);curl_setopt($curl,CURLOPT_POST,true);curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($payload,JSON_THROW_ON_ERROR));}
        return $curl;
    }

    public function request(string $action,?int $offset=null,bool $authenticated=true,int $timeoutSeconds=35):array
    {
        $this->assertGroup();$nonce=bin2hex(random_bytes(16));$payload=['action'=>$action,'nonce'=>$nonce];if($offset!==null)$payload['offset']=$offset;$curl=$this->handle($this->config['rpcPath'],$payload,$authenticated);curl_setopt($curl,CURLOPT_TIMEOUT,max(1,min(35,$timeoutSeconds)));$body=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);curl_close($curl);$this->assertGroup();
        if($error!==0||!is_string($body)||strlen($body)>16384)throw new RuntimeException('WEB_TRANSPORT');
        if(!$authenticated){if($status!==503||!str_contains($body,$this->config['ownerStamp']))throw new RuntimeException('WEB_UNAUTHENTICATED_NOT_BARRIERED');return ['unauthenticatedDenied'=>true];}
        if($status!==200)throw new RuntimeException('WEB_AUTHENTICATED_HTTP_'.$status);$data=json_decode($body,true,64,JSON_THROW_ON_ERROR);
        $this->validateReceipt($data);if(($data['nonce']??null)!==$nonce)throw new RuntimeException('WEB_FRESH_NONCE');return $data;
    }

    private function validateReceipt(array $data,bool $smoke=false):void
    {
        if(($data['status']??'')!=='ok'||($data['id']??'')!==$this->config['id']||($data['phase']??'')!==$this->config['phase']||($data['librarySHA256']??'')!==$this->config['librarySHA256']||($data['uid']??-1)!==posix_geteuid())throw new RuntimeException('WEB_RECEIPT_SCOPE');
        if(($data['executionProfile']??null)!=='UNCACHED_CMS'||($data['profileSHA256']??null)!==$this->config['profileSHA256']||($data['startupUncached']??false)!==true||($data['opcacheEnabled']??null)!==false)throw new RuntimeException('WEB_UNCACHED_PROFILE');
        if(($data['actualIndex']??false)!==true)throw new RuntimeException('WEB_RECEIPT_FRONT_CONTEXT');
        if($this->group!==null&&(($data['sapi']??'')!=='litespeed'||!is_int($data['pid']??null)||$data['pid']<=0||($data['ppid']??null)!==$this->group['parent']['pid']||($data['pidNamespace']??null)!==readlink('/proc/self/ns/pid')))throw new RuntimeException('WEB_RECEIPT_PROCESS_CACHE_GROUP');
    }

    public function verifyFiles():array
    {
        $total=count($this->config['files']);if($total<1)throw new RuntimeException('EMPTY_UNCACHED_CENSUS');$checked=0;$cached=0;
        for($offset=0;$offset<$total;$offset+=128){$receipt=null;for($attempt=0;$attempt<2;$attempt++){try{$receipt=$this->request('verify-files',$offset);break;}catch(Throwable $error){if($attempt===1)throw $error;usleep(200000);}}
            $count=min(128,$total-$offset);if(($receipt['offset']??-1)!==$offset||($receipt['total']??-1)!==$total||($receipt['checked']??-1)!==$count)throw new RuntimeException('WEB_CHUNK_CENSUS');$checked+=$count;
            echo 'UNCACHED_FILE_CHUNK='.$offset.' CHECKED='.$count.' TOTAL='.$total.PHP_EOL;
        }
        return ['allChunksPassed'=>true,'checked'=>$checked,'total'=>$total,'executionProfile'=>'UNCACHED_CMS','actualWeb'=>true];
    }

    public function publicStatus(string $path,int $expected,?string $stamp=null,bool $privateSmoke=false):void
    {
        $this->assertGroup();$nonce=bin2hex(random_bytes(16));$curl=$this->handle($path.'?ald1n_release_check='.$this->config['id'],null,false);if($privateSmoke){if($this->smokeToken==='')throw new RuntimeException('PRIVATE_SMOKE_TOKEN_MISSING');curl_setopt($curl,CURLOPT_HTTPHEADER,['Cache-Control: no-cache','X-Ald1n-Release-Smoke: '.$this->smokeToken,'X-Ald1n-Release-Smoke-Nonce: '.$nonce]);}$body=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);curl_close($curl);$this->assertGroup();
        if($error!==0||$status!==$expected||!is_string($body)||($stamp!==null&&!str_contains($body,$stamp)))throw new RuntimeException('PUBLIC_HTTP_SMOKE_'.$expected);
        if($privateSmoke){$recordPath=$this->private.'/smoke-'.hash('sha256',$path).'.json';$record=null;for($attempt=0;$attempt<100;$attempt++){if(is_file($recordPath)&&!is_link($recordPath)&&filesize($recordPath)<16384){$candidate=json_decode(file_get_contents($recordPath),true);if(is_array($candidate)&&($candidate['nonce']??'')===$nonce){$record=$candidate;break;}}usleep(100000);}
            if(!is_array($record)||($record['id']??'')!==$this->config['id']||($record['phase']??'')!==$this->config['phase']||($record['librarySHA256']??'')!==$this->config['librarySHA256']||($record['uncachedProfileVerified']??false)!==true||($record['applicationBootstrapped']??false)!==true||($record['basePathMatches']??false)!==true||($record['configCacheAbsent']??false)!==true||($record['releaseLocalCompiledViews']??false)!==true)throw new RuntimeException('ACTUAL_GET_CONTEXT_NOT_ATTESTED');
            $this->validateReceipt($record,true);
            if($this->config['phase']==='installed'&&(($record['storefrontEnabled']??null)!==false||($record['checkoutEnabled']??null)!==false))throw new RuntimeException('ACTUAL_GET_OFF_NOT_ATTESTED');
        }
        echo 'PUBLIC_HTTP_EXPECTED_'.$expected.'=PASS'.PHP_EOL;
    }

    public function proveFreshness():array
    {
        foreach(['A','B'] as $literal){$this->config['witness']=UncachedProfile::witness($this->config['incoming'],$this->config['id'],$literal);$this->setPhase($this->config['phase'],$this->config['files']);$r=$this->request('witness');if(($r['executedWitness']??null)!==[$literal,$literal])throw new RuntimeException('UNCACHED_EXECUTED_FRESHNESS');}$this->config['samePathFreshnessProven']=true;$this->setPhase($this->config['phase'],$this->config['files']);echo 'UNCACHED_EXECUTED_SAME_PATH_MAIN_AND_DEPENDENCY=PASS'.PHP_EOL;return ['id'=>$this->config['id'],'profileSHA256'=>$this->config['profileSHA256'],'samePathFreshnessProven'=>true];
    }

    /** Only exact known paths and the two upstream-supported tail widths; never learns an arbitrary title. */
    private static function knownTitle(string $title,array $paths):?array
    {
        $matches=[];foreach($paths as $path)foreach([44,45] as $width){$needle=':'.substr($path,-$width);if(!str_ends_with($title,$needle))continue;$prefix=substr($title,0,-strlen($needle));if($prefix===''||strlen($prefix)>4096)continue;$match=['titleWidth'=>$width,'titleNeedle'=>$needle,'idleArgv0'=>$prefix];$matches[json_encode($match,JSON_THROW_ON_ERROR)]=$match;}if(count($matches)>1)throw new RuntimeException('LSAPI_TITLE_FORMAT_AMBIGUOUS');return $matches?reset($matches):null;
    }
    private function marker(string $path,string $nonce,string $namespace,array $before):?array
    {
        clearstatcache(true,$path);if(is_link($path)||realpath($path)!==$path||!is_file($path)||fileowner($path)!==posix_geteuid()||(fileperms($path)&0777)!==0600||filesize($path)>16384)return null;$raw=@file_get_contents($path,false,null,0,16385);if(!is_string($raw)||strlen($raw)>16384)return null;$m=json_decode($raw,true);if(!is_array($m)||($m['nonce']??null)!==$nonce||($m['id']??null)!==$this->config['id']||($m['phase']??null)!=='preflight'||($m['uid']??null)!==posix_geteuid()||($m['pidNamespace']??null)!==$namespace||($m['librarySHA256']??null)!==$this->config['librarySHA256']||($m['indexSHA256']??null)!==$this->config['indexSHA256']||($m['actualIndex']??false)!==true||($m['profileSHA256']??null)!==$before['profileSHA256']||($m['startupUncached']??false)!==true||($m['opcacheEnabled']??null)!==false||($m['ppid']??null)!==$before['ppid']||!is_int($m['pid']??null)||$m['pid']<=0||!in_array($m['stage']??null,['execution','shutdown','done'],true)||!is_string($m['processStart']??null)||!ctype_digit($m['processStart'])||!preg_match('/^[a-f0-9]{64}$/D',$m['executableSHA256']??''))return null;
        $index=$this->config['activeRoot'].'/public/index.php';if(realpath($index)!==$index||hash_file('sha256',$index)!==$this->config['indexSHA256'])throw new RuntimeException('CALIBRATION_FRONT_SOURCE_CHANGED');$paths=[$index];foreach(['script_filename','path_translated'] as $name){$value=$m['paths'][$name]??null;if($value===null)continue;if(!is_string($value)||strlen($value)>4096||!str_starts_with($value,'/')||preg_match('/[\x00-\x1f\x7f]/',$value)||realpath($value)!==$index)throw new RuntimeException('CALIBRATION_PATH_SCOPE');$paths[]=$value;}$m['_paths']=array_values(array_unique($paths));return $m;
    }
    public function calibrateVisibility():array
    {
        if(($this->config['samePathFreshnessProven']??false)!==true)throw new RuntimeException('UNCACHED_EXECUTED_FRESHNESS_REQUIRED');$before=$this->request('probe');$namespace=readlink('/proc/self/ns/pid');if(!is_string($namespace)||$namespace===''||($before['sapi']??null)!=='litespeed'||($before['pidNamespace']??null)!==$namespace||!is_int($before['ppid']??null)||$before['ppid']<=1||($before['profileSHA256']??null)!==$this->config['profileSHA256']||($before['startupUncached']??false)!==true||($before['opcacheEnabled']??null)!==false)throw new RuntimeException('FRESH_CALIBRATION_SCOPE');
        $nonce=bin2hex(random_bytes(16));$marker=$this->private.'/visibility-'.$nonce.'.json';$curl=$this->handle($this->config['rpcPath'],['action'=>'visibility','nonce'=>$nonce],true);$multi=curl_multi_init();curl_multi_add_handle($multi,$curl);$started=hrtime(true);$identity=null;$stages=[];$idle=false;
        try{do{
            $rc=curl_multi_exec($multi,$running);if($rc!==CURLM_OK)throw new RuntimeException('WEB_VISIBILITY_TRANSPORT');$m=$this->marker($marker,$nonce,$namespace,$before);$stage=$m['stage']??null;if($identity!==null&&$m!==null&&($m['pid']!==$identity['pid']||$m['processStart']!==$identity['start']||$m['executableSHA256']!==hash('sha256',$identity['executable'])))throw new RuntimeException('LSAPI_CALIBRATION_IDENTITY_CHANGED');$processes=HostProcessGuard::processes();
            if($m!==null&&isset($processes[$m['pid']])&&$processes[$m['pid']]['comm']==='lsphp'){$detail=HostProcessGuard::identity($m['pid']);if($detail!==null){if($detail['start']!==$m['processStart']||hash('sha256',$detail['executable'])!==$m['executableSHA256']||$detail['ppid']!==$before['ppid'])throw new RuntimeException('LSAPI_CALIBRATION_IDENTITY_CHANGED');$format=self::knownTitle($detail['argv0'],$m['_paths']);if($format!==null){if($identity!==null&&($identity['pid']!==$m['pid']||$identity['start']!==$detail['start']||$identity['executable']!==$detail['executable']||$identity['titleWidth']!==$format['titleWidth']||$identity['titleNeedle']!==$format['titleNeedle']||$identity['idleArgv0']!==$format['idleArgv0']))throw new RuntimeException('LSAPI_CALIBRATION_FORMAT_OR_IDENTITY_CHANGED');$identity=$detail+['pid'=>$m['pid']]+$format;if(in_array($stage,['execution','shutdown'],true))$stages[$stage]=true;}}}
            if($identity!==null&&$stage==='done'){$after=HostProcessGuard::identity($identity['pid']);if($after!==null&&$after['start']===$identity['start']&&$after['executable']===$identity['executable']&&$after['uid']===$identity['uid']&&$after['ppid']===$identity['ppid']&&$after['argv0']===$identity['idleArgv0'])$idle=true;}
            if(hrtime(true)-$started>15000000000)throw new RuntimeException('WEB_VISIBILITY_TIMEOUT');if($running||!$idle)usleep(100000);
        }while($running||!$idle);$body=curl_multi_getcontent($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$curlError=curl_errno($curl);}finally{curl_multi_remove_handle($multi,$curl);curl_multi_close($multi);curl_close($curl);}
        $receipt=json_decode($body,true,64,JSON_THROW_ON_ERROR);if($curlError!==0||$status!==200||($receipt['visibilityNonce']??null)!==$nonce||!isset($stages['execution'],$stages['shutdown'])||$identity===null||!$idle||($receipt['pid']??null)!==$identity['pid']||($receipt['profileSHA256']??null)!==$before['profileSHA256']||($receipt['startupUncached']??false)!==true||($receipt['opcacheEnabled']??null)!==false||($receipt['ppid']??null)!==$before['ppid'])throw new RuntimeException('WEB_PROCESS_VISIBILITY_UNSUPPORTED');
        $parent=HostProcessGuard::identity($identity['ppid']);if($parent===null||$parent['uid']!==$identity['uid']||$parent['executable']!==$identity['executable']||$parent['argv0']!==$identity['idleArgv0'])throw new RuntimeException('LSAPI_PROCESS_GROUP_NOT_PROVEN');
        $group=$identity+['sapi'=>'litespeed','activeToIdleTransitionProven'=>true,'heldExecutionAndShutdown'=>true,'formatConformanceProven'=>true,'calibrationId'=>$this->config['id'],'librarySHA256'=>$this->config['librarySHA256'],'pidNamespace'=>$namespace,'profileSHA256'=>$before['profileSHA256'],'executionProfile'=>'UNCACHED_CMS','startupUncached'=>true,'samePathFreshnessProven'=>true,'parent'=>$parent+['pid'=>$identity['ppid']],'installer'=>HostProcessGuard::identity(getmypid())+['pid'=>getmypid()]];$group['formatSeal']=hash_hmac('sha256',json_encode($group,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES),$this->key);HostProcessGuard::assertGroup($group);$this->setGroup($group);$this->validateReceipt($receipt);$this->request('probe');echo 'LSAPI_FORMAT_CONFORMANCE='.json_encode(['width'=>$group['titleWidth'],'heldPhases'=>true,'sameIdentityIdle'=>true,'parentAndGroup'=>true,'startupUncached'=>true],JSON_THROW_ON_ERROR).PHP_EOL;return $group;
    }

    public function cleanupStatus():void
    {
        $curl=$this->handle($this->config['rpcPath'],['action'=>'probe'],false);$body=curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);curl_close($curl);if($error!==0||$status!==404)throw new RuntimeException('FRONT_DISPATCH_CLEANUP_NOT_404');
    }
}

final class HostProcessGuard
{
    public static function assertGroup(array $group):void
    {
        if(!in_array($group['titleWidth']??null,[44,45],true)||($group['formatConformanceProven']??false)!==true)throw new RuntimeException('CALIBRATED_TITLE_WIDTH_REQUIRED');$parent=$group['parent'];$current=self::identity($parent['pid']);if($current===null||$current['start']!==$parent['start']||$current['executable']!==$parent['executable']||$current['uid']!==$parent['uid']||$current['argv0']!==$group['idleArgv0'])throw new RuntimeException('LSAPI_PARENT_CHANGED');
        $seenParent=false;foreach(self::processes() as $pid=>$process){if($process['comm']!=='lsphp')continue;$detail=self::identity($pid);if($detail===null)continue;if($pid===$parent['pid']){$seenParent=true;continue;}if($detail['ppid']!==$parent['pid']||$detail['executable']!==$parent['executable']||$detail['uid']!==$parent['uid'])throw new RuntimeException('FOREIGN_OR_WORKER_MODE_LSAPI_GROUP');}
        if(!$seenParent)throw new RuntimeException('LSAPI_PARENT_NOT_IN_COMPLETE_SNAPSHOT');
    }

    public static function classify(int $pid,array $process,array $detail,array $group):string
    {
        if($pid===$group['installer']['pid']&&$detail['start']===$group['installer']['start']&&$detail['executable']===$group['installer']['executable']&&$detail['uid']===$group['installer']['uid'])return 'installer';
        if($process['comm']==='lsphp'){if(!in_array($group['titleWidth']??null,[44,45],true)||($group['formatConformanceProven']??false)!==true)throw new RuntimeException('CALIBRATED_TITLE_WIDTH_REQUIRED');
            if($detail['executable']!==$group['parent']['executable']||$detail['uid']!==$group['parent']['uid'])throw new RuntimeException('FOREIGN_LSAPI_EXECUTABLE');
            if($pid!==$group['parent']['pid']&&$detail['ppid']!==$group['parent']['pid'])throw new RuntimeException('FOREIGN_LSAPI_PARENT');
            if($detail['argv0']===$group['idleArgv0'])return 'idle';
            if(str_starts_with($detail['argv0'],$group['idleArgv0'].':')&&strlen(substr($detail['argv0'],strlen($group['idleArgv0'])+1))<=$group['titleWidth'])return 'busy';
            throw new RuntimeException('UNCLASSIFIED_LSAPI_TITLE');
        }
        $name=basename($detail['executable']);$php=preg_match('/^(?:php(?:[0-9.]+)?|lsphp|php-cgi|php-fpm(?:[0-9.]+)?)$/D',$name)||preg_match('/php|lsphp/i',$process['comm']);
        if($php){if(str_contains($process['args'],'artisan'))return 'busy';throw new RuntimeException('UNKNOWN_ACTIVE_PHP_CLI_OR_POOL');}return 'unrelated';
    }
    public static function identity(int $pid):?array
    {
        $directory='/proc/'.$pid;if(!is_dir($directory))return null;
        $stat=@file_get_contents($directory.'/stat');$status=@file_get_contents($directory.'/status');$cmd=@file_get_contents($directory.'/cmdline');$exe=@readlink($directory.'/exe');if(!is_dir($directory))return null;
        if(!is_string($stat)||!is_string($status)||!is_string($cmd)||!is_string($exe)||!preg_match('/^Uid:\s+(\d+)\s+(\d+)/m',$status,$uid)||(int)$uid[2]!==posix_geteuid())throw new RuntimeException('OWNED_PROCESS_UNREADABLE');
        $end=strrpos($stat,') ');$fields=$end===false?[]:preg_split('/\s+/',trim(substr($stat,$end+2)));if(count($fields)<20||!ctype_digit($fields[19]))throw new RuntimeException('PROCESS_START_UNATTESTED');
        return ['start'=>$fields[19],'ppid'=>(int)$fields[1],'executable'=>$exe,'uid'=>(int)$uid[2],'argv0'=>explode("\0",$cmd)[0]];
    }
    public static function processes():array
    {
        $pipes=[];$process=proc_open(['ps','-ww','-u',(string)posix_geteuid(),'-o','pid=,comm=,args='],[1=>['pipe','w'],2=>['pipe','w']],$pipes);if(!is_resource($process))throw new RuntimeException('PS_UNAVAILABLE');$output=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);if(proc_close($process)!==0||strlen($output)>1048576)throw new RuntimeException('PS_UNATTESTED');$result=[];
        foreach(explode("\n",trim($output)) as $line){if(!preg_match('/^\s*(\d+)\s+(\S+)\s+(.*)$/D',$line,$m))throw new RuntimeException('PS_PARSE');$result[(int)$m[1]]=['comm'=>$m[2],'args'=>$m[3]];}return $result;
    }

    public static function quiet(string $active,array $visibility):void
    {
        if(($visibility['activeToIdleTransitionProven']??false)!==true)throw new RuntimeException('HTTP_WORKER_VISIBILITY_MISSING');$consecutive=0;
        for($attempt=0;$attempt<120;$attempt++){$busy=0;self::assertGroup($visibility);
            foreach(self::processes() as $pid=>$process){$detail=self::identity($pid);if($detail===null)continue;if(self::classify($pid,$process,$detail,$visibility)==='busy')$busy++;}
            if($busy===0){$consecutive++;if($consecutive>=3)return;}else $consecutive=0;
            if($attempt%10===0)echo 'OWNED_WORKER_DRAIN_WAIT='.$attempt.' BUSY='.$busy.PHP_EOL;usleep(500000);
        }
        throw new RuntimeException('OWNED_WORKERS_NOT_QUIESCENT');
    }
}
