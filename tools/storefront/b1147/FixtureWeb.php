<?php
declare(strict_types=1);
/**
 * CI-only web double for B1147: the same requests as CatalogWeb, over plain HTTP to the loopback `php -S` server with
 * Host: cms.ald1n.com instead of HTTPS. Negative switches: while $dropCsp exists the CSP headers are dropped, while
 * $dropJsonLd exists the JSON-LD block is removed from the body. Not shipped in the operator package.
 * @author Ald1n <https://ald1n.com>
 */
final class CatalogFixtureWeb
{
    /**
     * $rate429: while this file exists, every throttled route (the 8 legal pages and the shop paths, one limiter key)
     * answers 429 with Retry-After: 3 (the public-shop-browse throttle behind a shared edge IP); the file holds how many
     * such answers remain, or ALWAYS.
     */
    public function __construct(private int $port,private string $secrets,private ?string $dropCsp=null,private ?string $dropJsonLd=null,private ?string $rate429=null,private ?string $htaccess=null,private ?string $noHtaccess=null){}

    /** Response headers that the smokes read, keyed like CatalogWeb::result(). */
    private const HTACCESS_KEYS=['x-robots-tag'=>'robots','content-security-policy'=>'csp','x-frame-options'=>'frame','content-type'=>'contentType'];

    /**
     * The `Header always set NAME "VALUE"` lines of the REAL public/.htaccess of the active tree (production runs it under
     * LiteSpeed with mod_headers; `php -S` ignores .htaccess). `set` replaces any header of that name the app sent, on
     * every response including static files, so the double applies it to every fetched response the same way.
     */
    public static function htaccessHeaders(string $file):array
    {
        $text=(string)file_get_contents($file);preg_match_all('/^\s*Header\s+always\s+set\s+([A-Za-z0-9-]+)\s+"([^"]*)"\s*$/mi',$text,$m,PREG_SET_ORDER);
        $out=[];foreach($m as $x)$out[strtolower($x[1])]=$x[2];if(!isset($out['x-robots-tag']))throw new RuntimeException('FIXTURE_HTACCESS_HAS_NO_X_ROBOTS_TAG');return $out;
    }

    private function applyHtaccess(array $r):array
    {
        if($this->htaccess===null||($this->noHtaccess!==null&&is_file($this->noHtaccess)))return $r;
        foreach(self::htaccessHeaders($this->htaccess) as $name=>$value)if(isset(self::HTACCESS_KEYS[$name])){$k=self::HTACCESS_KEYS[$name];$r[$k]=$k==='contentType'?$value:[$value];}
        $r['htaccessHeadersApplied']=true;return $r;
    }

    public static function get(int $port,string $target,array $headers=[],?string $post=null):array
    {
        $h=CatalogWeb::headers();$cookies=[];$location=null;$c=curl_init('http://127.0.0.1:'.$port.$target);
        curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>30,CURLOPT_NOPROXY=>'*',CURLOPT_HTTPHEADER=>array_merge(['Host: cms.ald1n.com','Cache-Control: no-cache'],$headers),
            CURLOPT_HEADERFUNCTION=>static function($x,string $line)use(&$h,&$cookies,&$location):int{CatalogWeb::header($h,$line);if(preg_match('/^Set-Cookie:\s*([^=;\s]+)=([^;]*)/i',$line,$m))$cookies[$m[1]]=$m[2];if(preg_match('/^Location:\s*(\S+)/i',$line,$m))$location=$m[1];return strlen($line);}]+($post===null?[]:[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$post]));
        $body=curl_exec($c);$status=(int)curl_getinfo($c,CURLINFO_RESPONSE_CODE);$error=curl_errno($c);curl_close($c);
        return CatalogWeb::result($error!==0,$status,$h,is_string($body)?$body:'')+['cookies'=>$cookies,'location'=>$location];
    }

    public function fetch(string $path,array $query,string $marker):array
    {
        if((in_array($path,CatalogOverlayCoordinator::LEGAL,true)||in_array($path,CatalogOverlayCoordinator::SHOP,true))&&$this->rate429!==null&&is_file($this->rate429)){
            $left=trim((string)file_get_contents($this->rate429));
            if($left==='ALWAYS'||(int)$left>0){if($left!=='ALWAYS')file_put_contents($this->rate429,(string)((int)$left-1));$h=CatalogWeb::headers();$h['retry'][]='3';return $this->applyHtaccess(CatalogWeb::result(false,429,$h,'Too Many Attempts.'));}
        }
        $r=$this->applyHtaccess(self::get($this->port,CatalogWeb::target($path,$query,$marker)));unset($r['cookies'],$r['location']);
        if($this->dropCsp!==null&&is_file($this->dropCsp))$r['csp']=[];
        if($this->dropJsonLd!==null&&is_file($this->dropJsonLd))$r['body']=(string)preg_replace('~<script type="application/ld\+json">.*?</script>~s','',$r['body']);
        return $r;
    }

    public function probe(string $name,string $token,bool $invalidate,?string $route=null):array
    {
        if(!preg_match('/^ald1n-b1144-[a-f0-9]{48}\.php$/D',$name)||!preg_match('/^[a-f0-9]{64}$/D',$token))throw new RuntimeException('FRESHNESS_PROBE_REQUEST_SCOPE');
        file_put_contents($this->secrets,$name."\n".$token."\n",FILE_APPEND);
        $r=self::get($this->port,'/'.$name,array_merge(['X-Ald1n-Release-Probe: '.$token],$invalidate?['X-Ald1n-Release-Invalidate: YES']:[]));
        if($r['transport']||$r['status']!==200)throw new RuntimeException('FRESHNESS_PROBE_HTTP_UNAVAILABLE');$data=json_decode($r['body'],true,16,JSON_THROW_ON_ERROR);$data['_route']='FIXTURE_LOOPBACK_HTTP_CLI_SERVER';return $data;
    }
}
