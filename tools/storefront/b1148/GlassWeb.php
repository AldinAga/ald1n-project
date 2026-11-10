<?php
declare(strict_types=1);

/**
 * B1148 public smokes for cms.ald1n.com, built on the pinned B1147 CatalogWeb helpers (header collection, scriptless CSP,
 * DENY, noindex, edge-cache status, JSON-LD shape), which stay the single implementation of those rules. Additions:
 * the Vary header is collected, the allowlisted query keys include the static stylesheet version v (only for the exact
 * versioned CSS URL the new layout links, fetched WITHOUT the cache-busting marker so it is the very cache entry browsers
 * get), a HEAD request (CURLOPT_NOBODY, a method the theme route does not accept; never a body, never a write) and an
 * origin fetch over loopback TLS (cms.ald1n.com resolved to 127.0.0.1, the B1144 probe route) used only to tell an edge
 * that hides the Vary header from an origin that does not send it. No POST, no custom method, no redirect is followed.
 * Probe URLs and tokens are never printed.
 * @author Ald1n <https://ald1n.com>
 */
final class GlassWeb
{
    public const MAX_BODY=4194304;
    private const QUERY_KEYS=['sort','page','v'];
    private LeanWeb $lean;

    public function __construct(private string $host='cms.ald1n.com',private int $port=443,private ?string $caFile=null)
    {
        if($host!=='cms.ald1n.com'||$port<1||$port>65535)throw new RuntimeException('WEB_CLIENT_SCOPE');$this->lean=new LeanWeb($host,$port,$port,$caFile);
    }

    /** The request target: $path, the allowlisted $query and, unless $marker is null, the release-check marker (always last). */
    public static function target(string $path,array $query,?string $marker):string
    {
        if(!preg_match('~^/[A-Za-z0-9/._-]*$~D',$path)||str_contains($path,'..')||str_contains($path,'//')||($marker!==null&&!preg_match('/^[a-f0-9]{0,32}$/D',$marker)))throw new RuntimeException('SMOKE_PATH');
        foreach($query as $k=>$v)if(!in_array($k,self::QUERY_KEYS,true)||!is_string($v)||!preg_match('/^[A-Za-z0-9_]{1,24}$/D',$v))throw new RuntimeException('SMOKE_QUERY');
        if(isset($query['v'])&&($path!==GlassOverlayCoordinator::CSS_PATH||count($query)!==1||$marker!==null))throw new RuntimeException('SMOKE_QUERY_VERSIONED_CSS_ONLY');
        if($marker===null&&!isset($query['v']))throw new RuntimeException('SMOKE_MARKER_REQUIRED');
        $q=$marker===null?$query:$query+['ald1n_release_check'=>$marker];return $path.'?'.http_build_query($q,'','&',PHP_QUERY_RFC3986);
    }

    /** The CatalogWeb headers plus Vary. */
    public static function headers():array {return CatalogWeb::headers()+['vary'=>[]];}

    public static function header(array &$h,string $line):void
    {
        CatalogWeb::header($h,$line);if(preg_match('/^Vary:\s*(.*?)\s*$/i',$line,$m))$h['vary'][]=$m[1];
    }

    public static function result(bool $transport,int $status,array $h,string $body):array
    {
        return CatalogWeb::result($transport,$status,$h,$body)+['vary'=>$h['vary']??[],'cfRay'=>$h['cfRay']??false];
    }

    /** True when one Vary value lists Cookie (a Vary: * also keys by every header). */
    public static function varyCookie(array $vary):bool
    {
        foreach($vary as $v)foreach(explode(',',(string)$v) as $token){$t=strtolower(trim($token));if($t==='cookie'||$t==='*')return true;}return false;
    }

    /** The first product page link of a catalog body (/katalog/<slug>, never the numeric image route), or null. */
    public static function productPath(string $html):?string
    {
        if(preg_match_all('~href="(/katalog/[A-Za-z0-9-]{1,120})"~',$html,$m))foreach($m[1] as $p)if(!str_starts_with($p,'/katalog/slika'))return $p;return null;
    }

    /**
     * One request. $method GET or HEAD; $origin true resolves cms.ald1n.com:443 to 127.0.0.1 (loopback TLS to the origin,
     * the route the B1144 freshness probe uses), so no edge sits in between. Status, transport error, headers, body.
     */
    public function fetch(string $path,array $query,?string $marker,string $method='GET',bool $origin=false):array
    {
        if(!in_array($method,['GET','HEAD'],true))throw new RuntimeException('SMOKE_METHOD');
        $target=self::target($path,$query,$marker);$h=self::headers();$body='';$over=false;
        $curl=curl_init('https://'.$this->host.($this->port===443?'':':'.$this->port).$target);
        $options=[CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_CONNECTTIMEOUT=>6,CURLOPT_TIMEOUT=>25,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_NOPROXY=>'*',CURLOPT_USERAGENT=>'Ald1n-B1148-owned-release',CURLOPT_HTTPHEADER=>['Host: '.$this->host,'Cache-Control: no-cache'],
            CURLOPT_HEADERFUNCTION=>static function($c,string $line)use(&$h):int{self::header($h,$line);if(preg_match('/^CF-RAY:/i',$line))$h['cfRay']=true;return strlen($line);},
            CURLOPT_WRITEFUNCTION=>static function($c,string $chunk)use(&$body,&$over):int{if(strlen($body)+strlen($chunk)>self::MAX_BODY){$over=true;return 0;}$body.=$chunk;return strlen($chunk);}];
        if($method==='HEAD')$options[CURLOPT_NOBODY]=true;if($origin)$options[CURLOPT_RESOLVE]=[$this->host.':'.$this->port.':127.0.0.1'];
        if($this->caFile!==null)$options[CURLOPT_CAINFO]=$this->caFile;curl_setopt_array($curl,$options);curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);curl_close($curl);
        return self::result($error!==0||$over,$status,$h,$body);
    }

    public function probe(string $name,string $token,bool $invalidate,?string $route=null):array {return $this->lean->probe($name,$token,$invalidate,$route);}
}
