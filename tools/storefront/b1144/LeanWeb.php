<?php
declare(strict_types=1);

/**
 * Minimal HTTPS client for cms.ald1n.com. Plain GET smokes go to the public hostname; the freshness probe
 * tries loopback TLS (resolve cms.ald1n.com:443 to 127.0.0.1) first and falls back to the public hostname.
 * Probe URLs and tokens are never printed.
 */
final class LeanWeb
{
    public function __construct(private string $host='cms.ald1n.com',private int $loopbackPort=443,private int $publicPort=443,private ?string $caFile=null,private ?string $publicAddress=null)
    {
        if($host!=='cms.ald1n.com'||$loopbackPort<1||$loopbackPort>65535||$publicPort<1||$publicPort>65535||($publicAddress!==null&&filter_var($publicAddress,FILTER_VALIDATE_IP)===false))throw new RuntimeException('WEB_CLIENT_SCOPE');
    }

    private function get(string $path,array $headers,?string $address,int $port,int $timeout):array
    {
        if($path===''||$path[0]!=='/'||str_contains($path,'://')||preg_match('/[\x00-\x20\x7f]/',$path))throw new RuntimeException('HTTP_PATH');
        $curl=curl_init('https://'.$this->host.($port===443?'':':'.$port).$path);
        $options=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_CONNECTTIMEOUT=>6,CURLOPT_TIMEOUT=>$timeout,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_NOPROXY=>'*',CURLOPT_USERAGENT=>'Ald1n-B1144-owned-release',CURLOPT_HTTPHEADER=>array_merge(['Host: '.$this->host,'Cache-Control: no-cache'],$headers)];
        if($address!==null)$options[CURLOPT_RESOLVE]=[$this->host.':'.$port.':'.$address];if($this->caFile!==null)$options[CURLOPT_CAINFO]=$this->caFile;
        curl_setopt_array($curl,$options);$body=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);curl_close($curl);
        return ['status'=>$status,'body'=>is_string($body)?$body:'','error'=>$error];
    }

    /** Public GET smoke with a cache-busting marker; up to three attempts for transient edge errors. */
    public function status(string $path,int $expected,?string $stamp=null,string $marker='',int $attempts=3):void
    {
        if(!preg_match('~^/[A-Za-z0-9/_-]*$~D',$path)||!preg_match('/^[a-f0-9]{0,32}$/D',$marker)||$attempts<1||$attempts>15)throw new RuntimeException('SMOKE_PATH');$last=null;
        for($attempt=1;$attempt<=$attempts;$attempt++){
            $r=$this->get($path.'?ald1n_release_check='.$marker,[],$this->publicAddress,$this->publicPort,25);$last=$r['error']!==0?'TRANSPORT':(string)$r['status'];
            if($r['error']===0&&$r['status']===$expected&&strlen($r['body'])<=8388608&&($stamp===null||str_contains($r['body'],$stamp))){echo 'PUBLIC_HTTP_SMOKE='.json_encode(['path'=>$path,'expected'=>$expected,'result'=>'PASS','attempts'=>$attempt],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES).PHP_EOL;return;}
            if($attempt<$attempts)usleep(2000000);
        }
        echo 'PUBLIC_HTTP_SMOKE='.json_encode(['path'=>$path,'expected'=>$expected,'result'=>'FAIL','observed'=>$last],JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES).PHP_EOL;
        throw new RuntimeException('PUBLIC_HTTP_SMOKE_'.$expected);
    }

    public function probe(string $name,string $token,bool $invalidate):array
    {
        if(!preg_match('/^ald1n-b1144-[a-f0-9]{48}\.php$/D',$name)||!preg_match('/^[a-f0-9]{64}$/D',$token))throw new RuntimeException('FRESHNESS_PROBE_REQUEST_SCOPE');
        $headers=['X-Ald1n-Release-Probe: '.$token];if($invalidate)$headers[]='X-Ald1n-Release-Invalidate: YES';
        $route='LOOPBACK_TLS_ORIGIN';$r=$this->get('/'.$name,$headers,'127.0.0.1',$this->loopbackPort,20);
        if($r['error']!==0||$r['status']!==200){$route='PUBLIC_TLS_HOSTNAME';$r=$this->get('/'.$name,$headers,$this->publicAddress,$this->publicPort,25);}
        if($r['error']!==0||$r['status']!==200||strlen($r['body'])>1048576){echo 'FRESHNESS_PROBE_HTTP='.json_encode(['route'=>$route,'status'=>$r['error']!==0?'TRANSPORT':$r['status']],JSON_THROW_ON_ERROR).PHP_EOL;throw new RuntimeException('FRESHNESS_PROBE_HTTP_UNAVAILABLE');}
        $data=json_decode($r['body'],true,16,JSON_THROW_ON_ERROR);if(!is_array($data))throw new RuntimeException('FRESHNESS_PROBE_BODY');$data['_route']=$route;return $data;
    }
}
