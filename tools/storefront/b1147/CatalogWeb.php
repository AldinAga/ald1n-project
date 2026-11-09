<?php
declare(strict_types=1);

/**
 * B1147 public GET smokes for cms.ald1n.com. Unlike the B1146 client it keeps a bounded copy of the response body (markers,
 * JSON-LD), admits an allowlisted query string (sort, page) and a dotted static path (the storefront CSS), and records the
 * Cloudflare cache status so an edge-cached CSS copy is never mistaken for the origin file. The probe request is delegated
 * unchanged to the pinned B1144 LeanWeb client. Probe URLs and tokens are never printed. A smoke never follows redirects
 * and always carries a cache-busting marker.
 * @author Ald1n <https://ald1n.com>
 */
final class CatalogWeb
{
    public const MAX_BODY=4194304;
    private const QUERY_KEYS=['sort','page'];
    private LeanWeb $lean;

    public function __construct(private string $host='cms.ald1n.com',private int $port=443,private ?string $caFile=null)
    {
        if($host!=='cms.ald1n.com'||$port<1||$port>65535)throw new RuntimeException('WEB_CLIENT_SCOPE');$this->lean=new LeanWeb($host,$port,$port,$caFile);
    }

    /** The request target for $path plus the allowlisted $query and the release-check marker (always last). */
    public static function target(string $path,array $query,string $marker):string
    {
        if(!preg_match('~^/[A-Za-z0-9/._-]*$~D',$path)||str_contains($path,'..')||str_contains($path,'//')||!preg_match('/^[a-f0-9]{0,32}$/D',$marker))throw new RuntimeException('SMOKE_PATH');
        foreach($query as $k=>$v)if(!in_array($k,self::QUERY_KEYS,true)||!is_string($v)||!preg_match('/^[A-Za-z0-9_]{1,24}$/D',$v))throw new RuntimeException('SMOKE_QUERY');
        return $path.'?'.http_build_query($query+['ald1n_release_check'=>$marker],'','&',PHP_QUERY_RFC3986);
    }

    /** One GET: status, transport error, security headers, Cloudflare cache status and at most MAX_BODY body bytes. */
    public function fetch(string $path,array $query,string $marker):array
    {
        $target=self::target($path,$query,$marker);$h=['robots'=>[],'csp'=>[],'frame'=>[],'cf'=>[],'type'=>[]];$body='';$over=false;
        $curl=curl_init('https://'.$this->host.($this->port===443?'':':'.$this->port).$target);
        $options=[CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_CONNECTTIMEOUT=>6,CURLOPT_TIMEOUT=>25,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_NOPROXY=>'*',CURLOPT_USERAGENT=>'Ald1n-B1147-owned-release',CURLOPT_HTTPHEADER=>['Host: '.$this->host,'Cache-Control: no-cache'],
            CURLOPT_HEADERFUNCTION=>static function($c,string $line)use(&$h):int{self::header($h,$line);return strlen($line);},
            CURLOPT_WRITEFUNCTION=>static function($c,string $chunk)use(&$body,&$over):int{if(strlen($body)+strlen($chunk)>self::MAX_BODY){$over=true;return 0;}$body.=$chunk;return strlen($chunk);}];
        if($this->caFile!==null)$options[CURLOPT_CAINFO]=$this->caFile;curl_setopt_array($curl,$options);curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);curl_close($curl);
        return self::result($error!==0||$over,$status,$h,$body);
    }

    /** Collects the response headers that the smokes assess (shared with the CI web double). */
    public static function header(array &$h,string $line):void
    {
        foreach(['robots'=>'X-Robots-Tag','csp'=>'Content-Security-Policy','frame'=>'X-Frame-Options','cf'=>'CF-Cache-Status','type'=>'Content-Type'] as $k=>$name)if(preg_match('/^'.preg_quote($name,'/').':\s*(.*?)\s*$/i',$line,$m))$h[$k][]=$m[1];
    }

    public static function result(bool $transport,int $status,array $h,string $body):array
    {
        return ['status'=>$transport?0:$status,'transport'=>$transport,'robots'=>$h['robots'],'csp'=>$h['csp'],'frame'=>$h['frame'],'cfCache'=>$h['cf']===[]?null:strtoupper((string)end($h['cf'])),'contentType'=>$h['type']===[]?null:(string)end($h['type']),'body'=>$transport?'':$body,'bytes'=>strlen($body)];
    }

    public function probe(string $name,string $token,bool $invalidate,?string $route=null):array {return $this->lean->probe($name,$token,$invalidate,$route);}

    /** A CSP header with script-src 'none' (the scriptless public layout); further CSP headers can only restrict more. */
    public static function scriptless(array $csp):bool {foreach($csp as $v)if(preg_match("/(?:^|;)\s*script-src\s+'none'\s*(?:;|$)/i",(string)$v)===1)return true;return false;}

    /** Every X-Frame-Options value is DENY and there is at least one. */
    public static function frameDeny(array $frame):bool {if($frame===[])return false;foreach($frame as $v)if(strcasecmp(trim((string)$v),'DENY')!==0)return false;return true;}

    /** True when one of the X-Robots-Tag values carries noindex. */
    public static function noindex(array $robots):bool {foreach($robots as $value)if(preg_match('/(?:^|[\s,])noindex(?:$|[\s,])/i',(string)$value))return true;return false;}

    /** A Cloudflare status meaning the body came from the edge cache, not from the origin file. */
    public static function edgeCached(?string $cf):bool {return in_array($cf,['HIT','STALE','UPDATING','REVALIDATED'],true);}

    /**
     * The JSON-LD data blocks of a page: exactly one block that parses as a schema.org CollectionPage with an ItemList.
     * Returns null when that holds, otherwise the failure code.
     */
    public static function jsonLdProblem(string $html):?string
    {
        $n=preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s',$html,$m);if($n!==1)return $n===0?'JSON_LD_MISSING':'JSON_LD_NOT_EXACTLY_ONE';
        $data=json_decode($m[1][0],true,64);if(!is_array($data))return 'JSON_LD_UNPARSED';
        if(($data['@context']??null)!=='https://schema.org'||($data['@type']??null)!=='CollectionPage'||($data['mainEntity']['@type']??null)!=='ItemList')return 'JSON_LD_SHAPE';
        return null;
    }
}
