<?php
declare(strict_types=1);
/**
 * CI-only web double: the same requests as OverlayWeb, over plain HTTP to the loopback `php -S` server with
 * Host: cms.ald1n.com instead of HTTPS. Not shipped in the operator package.
 * @author Ald1n <https://ald1n.com>
 */
final class FixtureWeb
{
    public function __construct(private int $port,private string $secrets){}
    public static function get(int $port,string $path,array $headers=[],?string $post=null):array
    {
        $robots=[];$cookies=[];$location=null;$c=curl_init('http://127.0.0.1:'.$port.$path);
        curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>30,CURLOPT_NOPROXY=>'*',CURLOPT_HTTPHEADER=>array_merge(['Host: cms.ald1n.com','Cache-Control: no-cache'],$headers),
            CURLOPT_HEADERFUNCTION=>static function($h,string $line)use(&$robots,&$cookies,&$location):int{if(preg_match('/^X-Robots-Tag:\s*(.*?)\s*$/i',$line,$m))$robots[]=$m[1];if(preg_match('/^Set-Cookie:\s*([^=;\s]+)=([^;]*)/i',$line,$m))$cookies[$m[1]]=$m[2];if(preg_match('/^Location:\s*(\S+)/i',$line,$m))$location=$m[1];return strlen($line);}]+($post===null?[]:[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$post]));
        $body=curl_exec($c);$status=(int)curl_getinfo($c,CURLINFO_RESPONSE_CODE);$error=curl_errno($c);curl_close($c);return ['status'=>$error===0?$status:0,'transport'=>$error!==0,'robots'=>$robots,'cookies'=>$cookies,'location'=>$location,'body'=>is_string($body)?$body:''];
    }
    public function fetch(string $path,string $marker):array {$r=self::get($this->port,$path.'?ald1n_release_check='.$marker);unset($r['body'],$r['cookies'],$r['location']);return $r+['bytes'=>0];}
    public function probe(string $name,string $token,bool $invalidate,?string $route=null):array
    {
        if(!preg_match('/^ald1n-b1144-[a-f0-9]{48}\.php$/D',$name)||!preg_match('/^[a-f0-9]{64}$/D',$token))throw new RuntimeException('FRESHNESS_PROBE_REQUEST_SCOPE');
        file_put_contents($this->secrets,$name."\n".$token."\n",FILE_APPEND);
        $r=self::get($this->port,'/'.$name,array_merge(['X-Ald1n-Release-Probe: '.$token],$invalidate?['X-Ald1n-Release-Invalidate: YES']:[]));
        if($r['transport']||$r['status']!==200)throw new RuntimeException('FRESHNESS_PROBE_HTTP_UNAVAILABLE');$data=json_decode($r['body'],true,16,JSON_THROW_ON_ERROR);$data['_route']='FIXTURE_LOOPBACK_HTTP_CLI_SERVER';return $data;
    }
}
