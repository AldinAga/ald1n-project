<?php
declare(strict_types=1);

/**
 * Minimal HTTPS client for cms.ald1n.com. Plain GET smokes go to the public hostname; the freshness probe
 * tries loopback TLS (resolve cms.ald1n.com:443 to 127.0.0.1) first and falls back to the public hostname,
 * unless the pre-switch host probe already fixed the route. Probe URLs and tokens are never printed.
 */
final class LeanWeb
{
    public const ROUTES=['LOOPBACK_TLS_ORIGIN','PUBLIC_TLS_HOSTNAME'];

    public function __construct(private string $host='cms.ald1n.com',private int $loopbackPort=443,private int $publicPort=443,private ?string $caFile=null,private ?string $publicAddress=null)
    {
        if($host!=='cms.ald1n.com'||$loopbackPort<1||$loopbackPort>65535||$publicPort<1||$publicPort>65535||($publicAddress!==null&&filter_var($publicAddress,FILTER_VALIDATE_IP)===false))throw new RuntimeException('WEB_CLIENT_SCOPE');
    }

    private function handle(string $path,array $headers,?string $address,int $port,int $timeout):CurlHandle
    {
        if($path===''||$path[0]!=='/'||str_contains($path,'://')||preg_match('/[\x00-\x20\x7f]/',$path))throw new RuntimeException('HTTP_PATH');
        $curl=curl_init('https://'.$this->host.($port===443?'':':'.$port).$path);
        $options=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_CONNECTTIMEOUT=>6,CURLOPT_TIMEOUT=>$timeout,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_NOPROXY=>'*',CURLOPT_USERAGENT=>'Ald1n-B1144-owned-release',CURLOPT_HTTPHEADER=>array_merge(['Host: '.$this->host,'Cache-Control: no-cache'],$headers)];
        if($address!==null)$options[CURLOPT_RESOLVE]=[$this->host.':'.$port.':'.$address];if($this->caFile!==null)$options[CURLOPT_CAINFO]=$this->caFile;
        curl_setopt_array($curl,$options);return $curl;
    }

    private function get(string $path,array $headers,?string $address,int $port,int $timeout):array
    {
        $curl=$this->handle($path,$headers,$address,$port,$timeout);$body=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);curl_close($curl);
        return ['status'=>$status,'body'=>is_string($body)?$body:'','error'=>$error];
    }

    private static function smokeScope(string $path,string $marker,int $attempts):void
    {
        if(!preg_match('~^/[A-Za-z0-9/_-]*$~D',$path)||!preg_match('/^[a-f0-9]{0,32}$/D',$marker)||$attempts<1||$attempts>15)throw new RuntimeException('SMOKE_PATH');
    }

    /**
     * Public GET smoke with a cache-busting marker; up to three attempts for transient edge errors. With $smokeToken the
     * request carries the owned barrier's private pass-through header, so the real application answers behind the barrier.
     */
    public function status(string $path,int $expected,?string $stamp=null,string $marker='',int $attempts=3,?string $smokeToken=null):void
    {
        self::smokeScope($path,$marker,$attempts);if($smokeToken!==null&&!preg_match('/^[a-f0-9]{64}$/D',$smokeToken))throw new RuntimeException('SMOKE_TOKEN_SCOPE');$last=null;
        $headers=$smokeToken===null?[]:['X-Ald1n-Release-Smoke: '.$smokeToken];$private=$smokeToken!==null;
        for($attempt=1;$attempt<=$attempts;$attempt++){
            $r=$this->get($path.'?ald1n_release_check='.$marker,$headers,$this->publicAddress,$this->publicPort,25);$last=$r['error']!==0?'TRANSPORT':(string)$r['status'];
            if($r['error']===0&&$r['status']===$expected&&strlen($r['body'])<=8388608&&($stamp===null||str_contains($r['body'],$stamp))){echo 'PUBLIC_HTTP_SMOKE='.json_encode(['path'=>$path,'expected'=>$expected,'result'=>'PASS','attempts'=>$attempt]+($private?['behindOwnedBarrier'=>true]:[]),JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES).PHP_EOL;return;}
            if($attempt<$attempts)usleep(2000000);
        }
        echo 'PUBLIC_HTTP_SMOKE='.json_encode(['path'=>$path,'expected'=>$expected,'result'=>'FAIL','observed'=>$last]+($private?['behindOwnedBarrier'=>true]:[]),JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES).PHP_EOL;
        throw new RuntimeException('PUBLIC_HTTP_SMOKE_'.$expected.($private?'_BEHIND_BARRIER':''));
    }

    /** One public GET whose transfer is driven non-blocking; $sample runs repeatedly WHILE the request is in flight. */
    public function sampled(string $path,callable $sample,string $marker=''):array
    {
        self::smokeScope($path,$marker,1);$curl=$this->handle($path.'?ald1n_release_check='.$marker,[],$this->publicAddress,$this->publicPort,25);
        $multi=curl_multi_init();curl_multi_add_handle($multi,$curl);$running=0;$samples=0;$started=hrtime(true);
        do{$code=curl_multi_exec($multi,$running);if($running){$sample();$samples++;curl_multi_select($multi,0.002);}}while($running&&$code===CURLM_OK&&hrtime(true)-$started<30000000000);
        $result=CURLE_OK;while(($info=curl_multi_info_read($multi))!==false)if($info['handle']===$curl)$result=$info['result'];
        $status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);curl_multi_remove_handle($multi,$curl);curl_close($curl);curl_multi_close($multi);
        return ['status'=>$result===CURLE_OK&&$code===CURLM_OK?$status:0,'transport'=>$result!==CURLE_OK||$code!==CURLM_OK||$running,'samplesDuringRequest'=>$samples];
    }

    /** $route fixes the route found by the pre-switch host probe; null tries loopback first, then the public hostname. */
    public function probe(string $name,string $token,bool $invalidate,?string $route=null):array
    {
        if(!preg_match('/^ald1n-b1144-[a-f0-9]{48}\.php$/D',$name)||!preg_match('/^[a-f0-9]{64}$/D',$token)||($route!==null&&!in_array($route,self::ROUTES,true)))throw new RuntimeException('FRESHNESS_PROBE_REQUEST_SCOPE');
        $headers=['X-Ald1n-Release-Probe: '.$token];if($invalidate)$headers[]='X-Ald1n-Release-Invalidate: YES';$tried=[];
        foreach($route===null?self::ROUTES:[$route] as $candidate){
            $r=$candidate==='LOOPBACK_TLS_ORIGIN'?$this->get('/'.$name,$headers,'127.0.0.1',$this->loopbackPort,20):$this->get('/'.$name,$headers,$this->publicAddress,$this->publicPort,25);
            $tried[]=['route'=>$candidate,'status'=>$r['error']!==0?'TRANSPORT':$r['status']];
            if($r['error']===0&&$r['status']===200&&strlen($r['body'])<=1048576){$data=json_decode($r['body'],true,16,JSON_THROW_ON_ERROR);if(!is_array($data))throw new RuntimeException('FRESHNESS_PROBE_BODY');$data['_route']=$candidate;return $data;}
        }
        echo 'FRESHNESS_PROBE_HTTP='.json_encode(['routes'=>$tried],JSON_THROW_ON_ERROR).PHP_EOL;throw new RuntimeException('FRESHNESS_PROBE_HTTP_UNAVAILABLE');
    }
}
