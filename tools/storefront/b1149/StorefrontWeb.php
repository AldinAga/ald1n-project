<?php
declare(strict_types=1);

/**
 * B1149 public smokes for cms.ald1n.com, built on the pinned B1147 CatalogWeb helpers (header collection, scriptless CSP,
 * DENY, noindex, edge-cache status, JSON-LD shape), which stay the single implementation of those rules. Additions over the
 * B1148 web helper: Cache-Control, X-Content-Type-Options and Location are collected; the allowlisted query keys include the
 * static stylesheet version v (only for the exact versioned CSS URLs the new pages link and for the /site-assets/ logo URL the
 * catalog links, fetched WITHOUT the cache-busting marker so it is the very cache entry browsers get); a HEAD request
 * (CURLOPT_NOBODY, never a body, never a write); an origin fetch over loopback TLS (cms.ald1n.com resolved to 127.0.0.1, the
 * B1144 probe route) used only to tell an edge that hides the Vary header from an origin that does not send it. The CSP
 * assessors cover the nonce'd theme script of the new layout. No POST, no custom method, no redirect is followed, nobody logs
 * in. Probe URLs and tokens are never printed.
 * @author Ald1n <https://ald1n.com>
 */
final class StorefrontWeb
{
    public const MAX_BODY=4194304;
    private const QUERY_KEYS=['sort','page','v'];
    private LeanWeb $lean;

    public function __construct(private string $host='cms.ald1n.com',private int $port=443,private ?string $caFile=null)
    {
        if($host!=='cms.ald1n.com'||$port<1||$port>65535)throw new RuntimeException('WEB_CLIENT_SCOPE');$this->lean=new LeanWeb($host,$port,$port,$caFile);
    }

    /** Paths that may carry the static version query v (and then no marker): the two stylesheets and a /site-assets/ file. */
    private static function versionable(string $path):bool
    {
        return $path===StorefrontInstallCoordinator::CSS_PATH||$path===StorefrontInstallCoordinator::GALLERY_CSS_PATH||preg_match('~^/site-assets/[A-Za-z0-9_/.-]{1,160}$~D',$path)===1;
    }

    /** The request target: $path, the allowlisted $query and, unless $marker is null, the release-check marker (always last). */
    public static function target(string $path,array $query,?string $marker):string
    {
        if(!preg_match('~^/[A-Za-z0-9/._-]*$~D',$path)||str_contains($path,'..')||str_contains($path,'//')||($marker!==null&&!preg_match('/^[a-f0-9]{0,32}$/D',$marker)))throw new RuntimeException('SMOKE_PATH');
        foreach($query as $k=>$v)if(!in_array($k,self::QUERY_KEYS,true)||!is_string($v)||!preg_match('/^[A-Za-z0-9_-]{1,40}$/D',$v))throw new RuntimeException('SMOKE_QUERY');
        if(isset($query['v'])&&(!self::versionable($path)||count($query)!==1||$marker!==null))throw new RuntimeException('SMOKE_QUERY_VERSIONED_STATIC_ONLY');
        if($marker===null&&!isset($query['v']))throw new RuntimeException('SMOKE_MARKER_REQUIRED');
        $q=$marker===null?$query:$query+['ald1n_release_check'=>$marker];return $path.'?'.http_build_query($q,'','&',PHP_QUERY_RFC3986);
    }

    /** The CatalogWeb headers plus Vary, Cache-Control, X-Content-Type-Options and Location. */
    public static function headers():array {return CatalogWeb::headers()+['vary'=>[],'cache'=>[],'nosniff'=>[],'location'=>[]];}

    public static function header(array &$h,string $line):void
    {
        CatalogWeb::header($h,$line);
        if(preg_match('/^Vary:\s*(.*?)\s*$/i',$line,$m))$h['vary'][]=$m[1];
        if(preg_match('/^Cache-Control:\s*(.*?)\s*$/i',$line,$m))$h['cache'][]=$m[1];
        if(preg_match('/^X-Content-Type-Options:\s*(.*?)\s*$/i',$line,$m))$h['nosniff'][]=$m[1];
        if(preg_match('/^Location:\s*(\S+)/i',$line,$m))$h['location'][]=$m[1];
    }

    public static function result(bool $transport,int $status,array $h,string $body):array
    {
        return CatalogWeb::result($transport,$status,$h,$body)+['vary'=>$h['vary']??[],'cache'=>$h['cache']??[],'nosniff'=>$h['nosniff']??[],'location'=>$h['location']===[]?null:(string)end($h['location']),'cfRay'=>$h['cfRay']??false];
    }

    /** True when one Vary value lists Cookie (a Vary: * also keys by every header). */
    public static function varyCookie(array $vary):bool
    {
        foreach($vary as $v)foreach(explode(',',(string)$v) as $token){$t=strtolower(trim($token));if($t==='cookie'||$t==='*')return true;}return false;
    }

    /** Cache-Control carries both private and no-store (any order, any other directive). */
    public static function privateNoStore(array $cache):bool
    {
        $tokens=[];foreach($cache as $v)foreach(explode(',',(string)$v) as $t)$tokens[strtolower(trim(explode('=',$t)[0]))]=true;return isset($tokens['private'],$tokens['no-store']);
    }

    /** Every X-Content-Type-Options value is nosniff and there is at least one. */
    public static function nosniff(array $values):bool
    {
        if($values===[])return false;foreach($values as $v)if(strcasecmp(trim((string)$v),'nosniff')!==0)return false;return true;
    }

    /** Some CSP value of the response is the sandboxed no-fetch policy of the site-assets route (default-src 'none' and sandbox). */
    public static function sandboxCsp(array $csp):bool
    {
        foreach($csp as $v)if(preg_match("/(?:^|;)\s*default-src\s+'none'\s*(?:;|$)/i",(string)$v)===1&&preg_match('/(?:^|;)\s*sandbox\s*(?:;|$)/i',(string)$v)===1)return true;return false;
    }

    /**
     * The <script> elements of a page as [attributes, body], JSON-LD data blocks left out, split into the scripts the ORIGIN
     * wrote ("app") and the ones the Cloudflare edge injects into every HTML response in production ("edge"): the e-mail
     * obfuscation decoder (src under /cdn-cgi/) and the inline JavaScript-detections loader (window.__CF$cv$params). Cloudflare
     * copies a CSP nonce onto its own scripts, so they are recognised by src or body, never by their attributes. Under
     * script-src 'none' the browser blocks them; they say nothing about the application code. Anything else is an app script.
     */
    public static function scripts(string $html):array
    {
        $out=['app'=>[],'edge'=>[]];if(!preg_match_all('~<script\b([^>]*)>(.*?)</script\s*>~is',$html,$m,PREG_SET_ORDER))$m=[];
        foreach($m as [$all,$attrs,$body]){
            if(preg_match('~\btype="application/ld\+json"~i',$attrs)===1)continue;
            // Every real src attribute of the tag (data-src and the like are not src); a path with ".." is never an edge path.
            $nsrc=(int)preg_match_all('~(?<![\w-])src\s*=\s*(?:"([^"]*)"|\S*)~i',$attrs,$srcs);$src=$nsrc===1?(string)$srcs[1][0]:null;
            $edge=($nsrc===1&&preg_match('~^/cdn-cgi/(?:scripts|challenge-platform)/[A-Za-z0-9/._-]+$~D',$src)===1&&!str_contains($src,'..')&&trim($body)==='')||($nsrc===0&&str_contains($body,'window.__CF$cv$params'));
            $out[$edge?'edge':'app'][]=[trim($attrs),$body];
        }
        // An opening tag without its closing tag is never ignored: it counts as an app script.
        $open=(int)preg_match_all('~<script\b(?![^>]*type="application/ld\+json")~i',$html);for($i=count($out['app'])+count($out['edge']);$i<$open;$i++)$out['app'][]=['UNCLOSED',''];
        return $out;
    }

    /** Number of executable scripts the origin wrote (edge-injected Cloudflare scripts and JSON-LD blocks not counted). */
    public static function scriptCount(string $html):int {return count(self::scripts($html)['app']);}

    /** Number of Cloudflare edge-injected scripts (INFO only). */
    public static function edgeScriptCount(string $html):int {return count(self::scripts($html)['edge']);}

    /**
     * The new shop policy: exactly one nonce'd script. A CSP value with script-src and script-src-elem both exactly
     * 'nonce-<22 base64 chars>==' (no 'self', 'unsafe-inline', 'unsafe-eval', 'strict-dynamic' or origin) and script-src-attr 'none',
     * and the page holds exactly one executable script written by the origin, whose only attribute is that very nonce (scripts
     * the Cloudflare edge injects are not origin output, see scripts()).
     */
    public static function nonceScript(array $csp,string $html):bool
    {
        foreach($csp as $v){
            $v=(string)$v;
            if(preg_match("/(?:^|;)\s*script-src\s+'nonce-([A-Za-z0-9+\/]{22}==)'\s*(?:;|$)/i",$v,$m)!==1)continue;
            if(preg_match("/(?:^|;)\s*script-src-elem\s+'nonce-".preg_quote($m[1],'/')."'\s*(?:;|$)/i",$v)!==1||preg_match("/(?:^|;)\s*script-src-attr\s+'none'\s*(?:;|$)/i",$v)!==1)continue;
            $app=self::scripts($html)['app'];if(count($app)===1&&$app[0][0]==='nonce="'.$m[1].'"')return true;
        }
        return false;
    }

    /** The first product page link of a catalog body (/katalog/<slug>, never the numeric image route), or null. */
    public static function productPath(string $html):?string
    {
        if(preg_match_all('~href="(/katalog/[A-Za-z0-9-]{1,120})"~',$html,$m))foreach($m[1] as $p)if(!str_starts_with($p,'/katalog/slika'))return $p;return null;
    }

    /** The first /site-assets/<file>[?v=<version>] URL of a page body as ['path'=>..., 'query'=>[]|['v'=>...]], or null. */
    public static function siteAssetUrl(string $html):?array
    {
        if(preg_match('~(?:src|href)="(/site-assets/[A-Za-z0-9_/.-]{1,160})(?:\?v=([A-Za-z0-9_-]{1,40}))?"~',$html,$m)!==1)return null;
        return ['path'=>$m[1],'query'=>isset($m[2])&&$m[2]!==''?['v'=>$m[2]]:[]];
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
        $options=[CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_CONNECTTIMEOUT=>6,CURLOPT_TIMEOUT=>25,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_NOPROXY=>'*',CURLOPT_USERAGENT=>'Ald1n-B1149-owned-release',CURLOPT_HTTPHEADER=>['Host: '.$this->host,'Cache-Control: no-cache'],
            CURLOPT_HEADERFUNCTION=>static function($c,string $line)use(&$h):int{self::header($h,$line);if(preg_match('/^CF-RAY:/i',$line))$h['cfRay']=true;return strlen($line);},
            CURLOPT_WRITEFUNCTION=>static function($c,string $chunk)use(&$body,&$over):int{if(strlen($body)+strlen($chunk)>self::MAX_BODY){$over=true;return 0;}$body.=$chunk;return strlen($chunk);}];
        if($method==='HEAD')$options[CURLOPT_NOBODY]=true;if($origin)$options[CURLOPT_RESOLVE]=[$this->host.':'.$this->port.':127.0.0.1'];
        if($this->caFile!==null)$options[CURLOPT_CAINFO]=$this->caFile;curl_setopt_array($curl,$options);curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);curl_close($curl);
        return self::result($error!==0||$over,$status,$h,$body);
    }

    public function probe(string $name,string $token,bool $invalidate,?string $route=null):array {return $this->lean->probe($name,$token,$invalidate,$route);}
}
