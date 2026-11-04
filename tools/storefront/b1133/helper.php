<?php
declare(strict_types=1);
function inspectRuntime(string $base,array $files):array {
    $counts=['sourcePHP'=>['same'=>0,'different'=>0,'missing'=>0,'unsafe'=>0],'sourceNonPHP'=>['same'=>0,'different'=>0,'missing'=>0,'unsafe'=>0],'vendor'=>['same'=>0,'different'=>0,'missing'=>0,'unsafe'=>0]];$findings=[];
    foreach($files as $file){
        $path=$file['path'];if(!is_string($path)||$path===''||str_starts_with($path,'/')||str_contains($path,'\\')||preg_match('~(^|/)(\.{1,2}|\.env[^/]*|storage)(/|$)|[\x00-\x1f]~',$path))throw new RuntimeException('MANIFEST_PATH');
        $group=str_starts_with($path,'vendor/')?'vendor':(str_ends_with($path,'.php')?'sourcePHP':'sourceNonPHP');$full=$base.'/'.$path;
        if(is_link($full))$status='unsafe';elseif(!file_exists($full))$status='missing';elseif(!is_file($full)||realpath($full)!==$full)$status='unsafe';elseif(filesize($full)!==$file['size']||hash_file('sha256',$full)!==$file['sha256'])$status='different';else $status='same';
        $counts[$group][$status]++;if($status!=='same'&&count($findings)<60)$findings[]=['path'=>$path,'status'=>$status];
    }
    return ['counts'=>$counts,'boundedExpectedPathFindings'=>$findings,'findingsLimit'=>60];
}
function cachedFlagInputs(string $text):array {
    if(strlen($text)>262144)throw new RuntimeException('CONFIG_CACHE_SIZE');
        $tokens=array_values(array_filter(token_get_all($text),static fn ($t): bool=>!is_array($t)||!in_array($t[0],[T_WHITESPACE,T_COMMENT,T_DOC_COMMENT],true)));
        if (($tokens[0][0]??null)!==T_OPEN_TAG||($tokens[1][0]??null)!==T_RETURN) throw new RuntimeException('CONFIG_CACHE_RETURN');
        $cursor=2;$items=0;$parse=null;
        // Decode var_export data; never execute a private cached PHP file.
        $parse=static function (int $depth) use (&$parse,&$cursor,&$items,$tokens): mixed {
            if ($depth>64||++$items>100000) throw new RuntimeException('CONFIG_DATA_BOUND');
            $t=$tokens[$cursor++]??null;
            if ($t==='['||(is_array($t)&&$t[0]===T_ARRAY)) {
                $close=$t==='['?']':')';
                if ($close===')'&&($tokens[$cursor++]??null)!=='(') throw new RuntimeException('CONFIG_ARRAY');
                $out=[];
                while (($tokens[$cursor]??null)!==$close) {
                    $first=$parse($depth+1);
                    $next=$tokens[$cursor]??null;
                    if (is_array($next)&&$next[0]===T_DOUBLE_ARROW) {
                        $cursor++;
                        if ((!is_int($first)&&!is_string($first))||array_key_exists($first,$out)) throw new RuntimeException('CONFIG_KEY');
                        $out[$first]=$parse($depth+1);
                    } else $out[]=$first;
                    if (($tokens[$cursor]??null)===$close) break;
                    if (($tokens[$cursor++]??null)!==',') throw new RuntimeException('CONFIG_SEPARATOR');
                }
                $cursor++;
                return $out;
            }
            if (is_array($t)&&$t[0]===T_CONSTANT_ENCAPSED_STRING) {
                $literal=$t[1];
                if ($literal[0]!=="'"||!str_ends_with($literal,"'")) throw new RuntimeException('CONFIG_STRING');
                $raw=substr($literal,1,-1);$value='';
                for ($i=0,$n=strlen($raw);$i<$n;$i++) {
                    if ($raw[$i]==='\\'&&$i+1<$n&&in_array($raw[$i+1],['\\',"'"],true)) $value.=$raw[++$i];
                    else $value.=$raw[$i];
                }
                return $value;
            }
            if (is_array($t)&&$t[0]===T_STRING) {
                return match(strtolower($t[1])) {'true'=>true,'false'=>false,'null'=>null,default=>throw new RuntimeException('CONFIG_CONSTANT')};
            }
            $negative=$t==='-';
            if ($negative) $t=$tokens[$cursor++]??null;
            if (is_array($t)&&$t[0]===T_LNUMBER&&preg_match('/^(?:0|[1-9][0-9]*)$/D',$t[1])) {
                $value=(int)$t[1];
                if ((string)$value!==$t[1]) throw new RuntimeException('CONFIG_INTEGER');
                return $negative?-$value:$value;
            }
            if (is_array($t)&&$t[0]===T_DNUMBER&&is_numeric($t[1])&&is_finite((float)$t[1])) return ($negative?-1:1)*(float)$t[1];
            throw new RuntimeException('NON_LITERAL_CONFIG_DATA');
        };
        $config=$parse(0);
        if (!is_array($config)||($tokens[$cursor++]??null)!==';'||$cursor!==count($tokens)) throw new RuntimeException('CONFIG_DATA_END');
        $flags=[];
        foreach (['enabled','catalog_readonly_enabled','checkout_enabled'] as $key) {
            $value=$config['public_storefront'][$key]??null;
            $flags[$key]=is_bool($value)?($value?'ON':'OFF'):'UNKNOWN';
        }
    return $flags;
}
if(realpath($_SERVER['SCRIPT_FILENAME']??'')===__FILE__){
    if(PHP_SAPI!=='cli'||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500||realpath(__FILE__)!=='/home/icaffeco/ald1n-project/incoming/b1133-readonly-preinstall.php')exit(2);
    $cms='/home/icaffeco/ald1n-project/apps/cms/current';$stage='/home/icaffeco/.ald1n-stage/b1131-ef3f927.6hh7mU/runtime';
    if(realpath($cms)!==$cms||realpath($stage)!==$stage)exit(2);
    $manifest=json_decode(file_get_contents('/home/icaffeco/ald1n-project/incoming/b1130-ef3f927/runtime-manifest.json'),true,512,JSON_THROW_ON_ERROR);
    if(($manifest['sourceCommit']??'')!=='ef3f927b77ba573881c2b6dba341732c417e6725'||count($manifest['files'])!==8531)exit(2);
    $result=inspectRuntime($cms,$manifest['files']);
    $config=$cms.'/bootstrap/cache/config.php';$result['configCache']=is_link($config)?'UNSAFE_LINK':(is_file($config)?'PRESENT_NOT_EVALUATED':'ABSENT');
    try { $result['cachedFlagInputsOnly']=is_file($config)&&!is_link($config)?cachedFlagInputs(file_get_contents($config)):['status'=>'ABSENT_UNATTESTED']; } catch(Throwable $e){$result['cachedFlagInputsOnly']=['status'=>'UNTRUSTED_NOT_EXECUTED'];}
    $result['effectiveStorefrontOFF']='NOT_ATTESTED';$result['schemaMetadata']='NOT_ATTESTED_NO_DB_CONNECTION';$result['FPM']='NOT_ATTESTED';$result['LaravelBootstrapped']=false;$result['databaseAccess']=false;$result['productionMutation']=false;$result['installReady']='HOLD';
    echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;exit(2);
}
