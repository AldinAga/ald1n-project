<?php
declare(strict_types=1);
/**
 * CI-only web double for B1149: the same requests as StorefrontWeb (GET and HEAD), over plain HTTP to the loopback `php -S`
 * server with Host: cms.ald1n.com instead of HTTPS. The REAL public/.htaccess Header lines are applied to every response
 * (production runs them under LiteSpeed; `php -S` ignores .htaccess), unless the noHtaccess switch file exists.
 * Negative switches (files): dropCsp (CSP headers dropped), dropJsonLd (JSON-LD block removed), dropVaryEdge (the Vary
 * header removed from edge responses only, the origin route still sends it), dropVaryAll (removed from every response),
 * rogueScript (one extra non-edge script in every HTML page), rate429 (every throttled route answers 429 with Retry-After: 3; the file holds the count left, or ALWAYS).
 * The origin route ($origin=true) is the same loopback server without the edge switches. Not shipped.
 * @author Ald1n <https://ald1n.com>
 */
final class StorefrontFixtureWeb
{
    private const HTACCESS_KEYS=['x-robots-tag'=>'robots','content-security-policy'=>'csp','x-frame-options'=>'frame','content-type'=>'contentType','vary'=>'vary','x-content-type-options'=>'nosniff'];

    public function __construct(private int $port,private string $secrets,private array $switches,private string $htaccess,private string $probeRoute='FIXTURE_LOOPBACK_HTTP_CLI_SERVER'){}

    private function on(string $name):bool {$f=$this->switches[$name]??null;return is_string($f)&&is_file($f);}

    private function applyHtaccess(array $r):array
    {
        if($this->on('noHtaccess'))return $r;
        foreach(CatalogFixtureWeb::htaccessHeaders($this->htaccess) as $name=>$value)if(isset(self::HTACCESS_KEYS[$name])){$k=self::HTACCESS_KEYS[$name];$r[$k]=$k==='contentType'?$value:[$value];}
        $r['htaccessHeadersApplied']=true;return $r;
    }

    public static function get(int $port,string $target,array $headers=[],?string $post=null,string $method='GET'):array
    {
        $h=StorefrontWeb::headers();$cookies=[];$location=null;$c=curl_init('http://127.0.0.1:'.$port.$target);
        curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_TIMEOUT=>30,CURLOPT_NOPROXY=>'*',CURLOPT_HTTPHEADER=>array_merge(['Host: cms.ald1n.com','Cache-Control: no-cache'],$headers),
            CURLOPT_HEADERFUNCTION=>static function($x,string $line)use(&$h,&$cookies,&$location):int{StorefrontWeb::header($h,$line);if(preg_match('/^Set-Cookie:\s*([^=;\s]+)=([^;]*)/i',$line,$m))$cookies[$m[1]]=$m[2];if(preg_match('/^Location:\s*(\S+)/i',$line,$m))$location=$m[1];return strlen($line);}]+($post===null?[]:[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$post])+($method==='HEAD'?[CURLOPT_NOBODY=>true]:[]));
        $body=curl_exec($c);$status=(int)curl_getinfo($c,CURLINFO_RESPONSE_CODE);$error=curl_errno($c);curl_close($c);
        return StorefrontWeb::result($error!==0,$status,$h,is_string($body)?$body:'')+['cookies'=>$cookies,'location'=>$location];
    }

    public function fetch(string $path,array $query,?string $marker,string $method='GET',bool $origin=false):array
    {
        $throttled=in_array($path,StorefrontInstallCoordinator::LEGAL,true)||in_array($path,StorefrontInstallCoordinator::SHOP,true)||str_starts_with($path,'/katalog/');
        if(!$origin&&$throttled&&$this->on('rate429')){
            $left=trim((string)file_get_contents($this->switches['rate429']));
            if($left==='ALWAYS'||(int)$left>0){if($left!=='ALWAYS')file_put_contents($this->switches['rate429'],(string)((int)$left-1));$h=StorefrontWeb::headers();$h['retry'][]='3';return $this->applyHtaccess(StorefrontWeb::result(false,429,$h,'Too Many Attempts.'));}
        }
        $r=$this->applyHtaccess(self::get($this->port,StorefrontWeb::target($path,$query,$marker),[],null,$method));unset($r['cookies']);
        if($this->on('dropVaryAll')||(!$origin&&$this->on('dropVaryEdge')))$r['vary']=[];
        if($origin)return $r;
        // Production HTML passes the Cloudflare edge, which injects its e-mail decoder and its JavaScript-detections loader and
        // copies a CSP nonce onto them. Always on (as in production); rogueScript adds a script that is NOT an edge script.
        if(str_starts_with(strtolower((string)$r['contentType']),'text/html')&&str_contains($r['body'],'</body>')){
            $n=preg_match("/script-src 'nonce-([^']+)'/",implode(' ',$r['csp']),$x)===1?' nonce="'.$x[1].'"':'';
            $inject='<script data-cfasync="false"'.$n.' src="/cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script'.$n.'>(function(){function c(){var d={};d.innerHTML="window.__CF$cv$params={r:\'a587f2bbac120380\',t:\'MTc5NDM0NDAyMg==\'};";}c();})();</script>';
            if($this->on('rogueScript'))$inject.='<script'.$n.'>window.rogue=1</script>';
            $r['body']=str_replace('</body>',$inject.'</body>',$r['body']);
        }
        if($this->on('dropCsp'))$r['csp']=[];
        if($this->on('dropJsonLd'))$r['body']=(string)preg_replace('~<script type="application/ld\+json">.*?</script>~s','',$r['body']);
        return $r;
    }

    public function probe(string $name,string $token,bool $invalidate,?string $route=null):array
    {
        if(!preg_match('/^ald1n-b1144-[a-f0-9]{48}\.php$/D',$name)||!preg_match('/^[a-f0-9]{64}$/D',$token))throw new RuntimeException('FRESHNESS_PROBE_REQUEST_SCOPE');
        file_put_contents($this->secrets,$name."\n".$token."\n",FILE_APPEND);
        $r=self::get($this->port,'/'.$name,array_merge(['X-Ald1n-Release-Probe: '.$token],$invalidate?['X-Ald1n-Release-Invalidate: YES']:[]));
        if($r['transport']||$r['status']!==200)throw new RuntimeException('FRESHNESS_PROBE_HTTP_UNAVAILABLE');$data=json_decode($r['body'],true,16,JSON_THROW_ON_ERROR);$data['_route']=$this->probeRoute;return $data;
    }
}
