<?php
declare(strict_types=1);

/**
 * B1146 public GET smokes for cms.ald1n.com that also keep the X-Robots-Tag response headers, so the legal pages can be
 * checked for noindex. The probe request is delegated unchanged to the pinned B1144 LeanWeb client. Probe URLs and tokens
 * are never printed. A smoke never follows redirects and carries a cache-busting marker.
 * @author Ald1n <https://ald1n.com>
 */
final class OverlayWeb
{
    private LeanWeb $lean;

    public function __construct(private string $host='cms.ald1n.com',private int $port=443,private ?string $caFile=null)
    {
        if($host!=='cms.ald1n.com'||$port<1||$port>65535)throw new RuntimeException('WEB_CLIENT_SCOPE');$this->lean=new LeanWeb($host,$port,$port,$caFile);
    }

    /** One GET: status, transport error and every X-Robots-Tag value. */
    public function fetch(string $path,string $marker):array
    {
        if(!preg_match('~^/[A-Za-z0-9/_-]*$~D',$path)||!preg_match('/^[a-f0-9]{0,32}$/D',$marker))throw new RuntimeException('SMOKE_PATH');$robots=[];
        $curl=curl_init('https://'.$this->host.($this->port===443?'':':'.$this->port).$path.'?ald1n_release_check='.$marker);
        $options=[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_CONNECTTIMEOUT=>6,CURLOPT_TIMEOUT=>25,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_NOPROXY=>'*',CURLOPT_USERAGENT=>'Ald1n-B1146-owned-release',CURLOPT_HTTPHEADER=>['Host: '.$this->host,'Cache-Control: no-cache'],
            CURLOPT_HEADERFUNCTION=>static function($c,string $line)use(&$robots):int{if(preg_match('/^X-Robots-Tag:\s*(.*?)\s*$/i',$line,$m))$robots[]=$m[1];return strlen($line);}];
        if($this->caFile!==null)$options[CURLOPT_CAINFO]=$this->caFile;curl_setopt_array($curl,$options);$body=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);curl_close($curl);
        return ['status'=>$error===0?$status:0,'transport'=>$error!==0,'robots'=>$robots,'bytes'=>is_string($body)?strlen($body):0];
    }

    public function probe(string $name,string $token,bool $invalidate,?string $route=null):array {return $this->lean->probe($name,$token,$invalidate,$route);}

    /** True when one of the X-Robots-Tag values carries noindex (SecurityHeaders may add a second value). */
    public static function noindex(array $robots):bool {foreach($robots as $value)if(preg_match('/(?:^|[\s,])noindex(?:$|[\s,])/i',(string)$value))return true;return false;}
}
