<?php
declare(strict_types=1);
/** No application bootstrap, scheduler, deployment, invalidation, cache reset or database access. */
final class B1141ReadControl
{
    private const INDEX_SHA='3a0d98b25997752a47c5f065c3fa05ec518170bf97b679e9339b2e17b4f25d55';
    private array $state=[];private mixed $lock;
    public function __construct(private string $root,private string $home,private string $library)
    {
        if($root!=='/home/icaffeco/ald1n-project'||$home!=='/home/icaffeco'||realpath($root)!==$root||realpath($home)!==$home||PHP_SAPI!=='cli'||PHP_VERSION_ID<80401||PHP_VERSION_ID>=80500||!extension_loaded('curl')||!function_exists('posix_geteuid'))throw new RuntimeException('HOST_PROFILE');
    }
    private static function writeNew(string $path,string $bytes,int $mode=0600):void
    {
        if(is_link($path)||file_exists($path)||realpath(dirname($path))!==dirname($path))throw new RuntimeException('OWNED_FILE_COLLISION');$h=fopen($path,'x');if($h===false)throw new RuntimeException('OWNED_FILE_CREATE');chmod($path,$mode);if(fwrite($h,$bytes)!==strlen($bytes))throw new RuntimeException('OWNED_FILE_WRITE');fflush($h);if(function_exists('fsync'))fsync($h);fclose($h);
    }
    private static function atomic(string $path,array $data):void
    {
        if(is_link($path))throw new RuntimeException('STATE_LINK');$tmp=$path.'.'.bin2hex(random_bytes(8)).'.next';self::writeNew($tmp,json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES));if(!rename($tmp,$path))throw new RuntimeException('STATE_RENAME');
    }
    private static function linkIdentity(string $path):array
    {
        clearstatcache(true);$s=lstat($path);if(!is_array($s)||!is_link($path))throw new RuntimeException('OWNED_LINK_REQUIRED');return ['dev'=>$s['dev'],'ino'=>$s['ino'],'uid'=>$s['uid'],'target'=>readlink($path)];
    }
    public static function publishLink(string $staged,string $target,array $identity):void
    {
        if(self::linkIdentity($staged)!==$identity||file_exists($target)||is_link($target))throw new RuntimeException('FOREIGN_MAINTENANCE_COLLISION');
        // link(2) never replaces an existing destination; source symlink inode is recorded beforehand.
        if(!@link($staged,$target))throw new RuntimeException('OWNED_LINK_PUBLISH');
        if(self::linkIdentity($target)!==$identity)throw new RuntimeException('ATOMIC_SYMLINK_PUBLICATION_UNSUPPORTED');
    }
    public static function removeOwned(string $target,array $identity,string $bridgeSHA):void
    {
        if(!file_exists($target)&&!is_link($target))return;
        if(self::linkIdentity($target)!==$identity||!is_file($identity['target'])||is_link($identity['target'])||hash_file('sha256',$identity['target'])!==$bridgeSHA)throw new RuntimeException('FOREIGN_MAINTENANCE_REFUSE_CLEANUP');if(!unlink($target))throw new RuntimeException('OWNED_LINK_REMOVE');
    }
    public static function validateSnapshot(array $s,array $state,string $nonce):void
    {
        if(($s['status']??'')!=='ok'||($s['id']??null)!==$state['id']||($s['nonce']??null)!==$nonce||($s['librarySHA256']??null)!==$state['config']['librarySHA256']||($s['bridgeSHA256']??null)!==$state['bridgeSHA256']||($s['uid']??null)!==posix_geteuid()||($s['actualIndex']??false)!==true)throw new RuntimeException('FRESH_SNAPSHOT_SCOPE');
    }
    private function save():void{self::atomic($this->state['private'].'/state.json',$this->state);self::atomic($this->home.'/.ald1n-b1141-state/current.json',['private'=>$this->state['private'],'id'=>$this->state['id']]);}
    private function signed(string $phase):void
    {
        $c=$this->state['config'];$c['phase']=$phase;$payload=json_encode($c,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);self::atomic($this->state['private'].'/web.json',['payload'=>$payload,'hmac'=>hash_hmac('sha256',$payload,$this->state['key'])]);
    }
    private function http(string $path,?array $payload=null):array
    {
        $curl=curl_init('https://cms.ald1n.com'.$path);$body='';$overflow=false;curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_WRITEFUNCTION=>static function($handle,string $part)use(&$body,&$overflow):int{if(strlen($body)+strlen($part)>1048576){$overflow=true;return 0;}$body.=$part;return strlen($part);},CURLOPT_HTTPHEADER=>['Cache-Control: no-cache'],CURLOPT_USERAGENT=>'Ald1n-B1141-read-only']);
        if($payload!==null){curl_setopt($curl,CURLOPT_POST,true);curl_setopt($curl,CURLOPT_POSTFIELDS,json_encode($payload,JSON_THROW_ON_ERROR));curl_setopt($curl,CURLOPT_HTTPHEADER,['Cache-Control: no-cache','Content-Type: application/json','X-Ald1n-Read-Token: '.$this->state['token']]);}curl_exec($curl);$status=curl_getinfo($curl,CURLINFO_RESPONSE_CODE);$error=curl_errno($curl);curl_close($curl);if($error||$overflow)throw new RuntimeException('WEB_TRANSPORT');return ['status'=>$status,'body'=>$body];
    }
    private function normal(string $label):void
    {
        $r=$this->http('/login?b1141_normal='.bin2hex(random_bytes(8)));echo 'NORMAL_LOGIN_'.$label.'_HTTP='.$r['status'].PHP_EOL;if($r['status']!==200)throw new RuntimeException('NORMAL_LOGIN_NOT_200');
    }
    private function recoverPrevious():void
    {
        $pointer=$this->home.'/.ald1n-b1141-state/current.json';if(!is_file($pointer))return;if(is_link($pointer)||filesize($pointer)>8192)throw new RuntimeException('RECOVERY_POINTER');$p=json_decode(file_get_contents($pointer),true,32,JSON_THROW_ON_ERROR);if(!preg_match('/^[a-f0-9]{32}$/D',$p['id']??'')||$p['private']!==$this->home.'/.ald1n-b1141-state/'.$p['id'])throw new RuntimeException('RECOVERY_SCOPE');$file=$p['private'].'/state.json';if(is_link($file)||realpath($file)!==$file||filesize($file)>65536)throw new RuntimeException('RECOVERY_STATE');$next=json_decode(file_get_contents($file),true,64,JSON_THROW_ON_ERROR);
        if($next['id']!==$p['id']||$next['private']!==$p['private']||$next['maintenance']!==$this->root.'/apps/cms/current/storage/framework/maintenance.php'||$next['config']['incoming']!==$this->root.'/incoming'||$next['bridgePath']!==$this->root.'/incoming/b1141-bridge-'.$next['bridgeSHA256'].'.php'||$next['linkIdentity']['target']!==$next['bridgePath'])throw new RuntimeException('RECOVERY_BINDING');
        if(($next['stagedLink']??'')!==$this->root.'/incoming/b1141-link-'.$p['id']||!preg_match('/^[a-f0-9]{64}$/D',$next['bridgeSHA256']??'')||!preg_match('/^[a-f0-9]{64}$/D',$next['key']??''))throw new RuntimeException('RECOVERY_OWNERSHIP');
        $this->state=$next;$this->cleanup();echo 'PREVIOUS_B1141_OWNED_BRIDGE_REVOKED_AND_CLEANED=YES'.PHP_EOL;$this->state=[];
    }
    private function cleanup():void
    {
        if(!$this->state)return;$this->signed('revoked');self::removeOwned($this->state['maintenance'],$this->state['linkIdentity'],$this->state['bridgeSHA256']);self::removeOwned($this->state['stagedLink'],$this->state['linkIdentity'],$this->state['bridgeSHA256']);$this->state['phase']='CLOSED';$this->save();echo 'OWNED_PASS_THROUGH_BRIDGE_CLEANUP=PASS'.PHP_EOL;
    }
    private function prepare():void
    {
        $base=$this->root.'/apps/cms/current';foreach([$base,$base.'/public',$base.'/storage',$base.'/storage/framework'] as $dir)if(is_link($dir)||realpath($dir)!==$dir||!is_dir($dir))throw new RuntimeException('ACTIVE_PATH_PROFILE');if(hash_file('sha256',$base.'/public/index.php')!==self::INDEX_SHA)throw new RuntimeException('FRONT_CONTROLLER_SOURCE_DRIFT');
        $maintenance=$base.'/storage/framework/maintenance.php';if(file_exists($maintenance)||is_link($maintenance)||file_exists($base.'/storage/framework/down')||is_link($base.'/storage/framework/down'))throw new RuntimeException('FOREIGN_MAINTENANCE_PRESENT');$librarySHA=hash_file('sha256',$this->library);if(realpath($this->library)!==$this->root.'/incoming/b1141-probe-'.$librarySHA.'.php')throw new RuntimeException('IMMUTABLE_PROBE_LIBRARY');
        $id=bin2hex(random_bytes(16));$private=$this->home.'/.ald1n-b1141-state/'.$id;if(!mkdir($private,0700))throw new RuntimeException('PRIVATE_CREATE');$key=bin2hex(random_bytes(32));$token=bin2hex(random_bytes(32));self::writeNew($private.'/signing-key',$key);$rpc='/__ald1n_opcache_read_'.$id;
        $bridge='<?php'.PHP_EOL.'if (parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH) !== '.var_export($rpc,true).') return;'.PHP_EOL.'require '.var_export($this->library,true).';'.PHP_EOL.'B1141ReadEndpoint::serve('.var_export($private.'/web.json',true).','.var_export($id,true).');'.PHP_EOL;
        $bridgeSHA=hash('sha256',$bridge);$bridgePath=$this->root.'/incoming/b1141-bridge-'.$bridgeSHA.'.php';self::writeNew($bridgePath,$bridge);$staged=$this->root.'/incoming/b1141-link-'.$id;if(!symlink($bridgePath,$staged))throw new RuntimeException('STAGED_LINK_CREATE');$identity=self::linkIdentity($staged);
        // Confirm native PHP preserves symlink inodes privately before publishing under the app.
        $test=$private.'/publication-selfcheck';self::publishLink($staged,$test,$identity);self::removeOwned($test,$identity,$bridgeSHA);
        $this->state=['schemaVersion'=>1,'phase'=>'INTENT','id'=>$id,'private'=>$private,'key'=>$key,'token'=>$token,'maintenance'=>$maintenance,'stagedLink'=>$staged,'bridgePath'=>$bridgePath,'bridgeSHA256'=>$bridgeSHA,'linkIdentity'=>$identity,'config'=>['schemaVersion'=>1,'phase'=>'active','id'=>$id,'expires'=>time()+300,'incoming'=>$this->root.'/incoming','activeRoot'=>$base,'indexSHA256'=>self::INDEX_SHA,'librarySHA256'=>$librarySHA,'bridgePath'=>$bridgePath,'bridgeSHA256'=>$bridgeSHA,'rpcPath'=>$rpc,'tokenSHA256'=>hash('sha256',$token)]];$this->signed('active');$this->save();self::publishLink($staged,$maintenance,$identity);$this->state['phase']='PUBLISHED';$this->save();
    }
    public function run():int
    {
        $dir=$this->home.'/.ald1n-b1141-state';if(!is_dir($dir)&&!mkdir($dir,0700))throw new RuntimeException('STATE_CREATE');if(is_link($dir)||realpath($dir)!==$dir||(fileperms($dir)&0777)!==0700||fileowner($dir)!==posix_geteuid())throw new RuntimeException('STATE_PROFILE');$lock=$dir.'/read.lock';if(is_link($lock))throw new RuntimeException('LOCK_LINK');$this->lock=fopen($lock,'c');if($this->lock===false||!flock($this->lock,LOCK_EX|LOCK_NB))throw new RuntimeException('ANOTHER_READ_CHECK_ACTIVE');chmod($lock,0600);
        $rc=2;try{$this->recoverPrevious();$this->normal('BEFORE');$this->prepare();$this->normal('DURING');$nonce=bin2hex(random_bytes(16));$curl=curl_init('https://cms.ald1n.com'.$this->state['config']['rpcPath']);curl_setopt_array($curl,[CURLOPT_FOLLOWLOCATION=>false,CURLOPT_MAXREDIRS=>0,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode(['action'=>'visibility','nonce'=>$nonce],JSON_THROW_ON_ERROR),CURLOPT_HTTPHEADER=>['Cache-Control: no-cache','Content-Type: application/json','X-Ald1n-Read-Token: '.$this->state['token']]]);$trace=VisibilityObserver::observe($curl,$this->state['private'],$this->state['config'],$nonce);echo 'VISIBILITY_OBSERVATION='.json_encode($trace,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES).PHP_EOL;$rc=$trace['collectionComplete']?0:2;$this->normal('AFTER_OBSERVATION');}catch(Throwable $e){$rc=2;echo 'B1141_HOLD_CODE='.(preg_match('/^[A-Z0-9_]+$/D',$e->getMessage())?$e->getMessage():'UNEXPECTED_NATIVE_IO').PHP_EOL;}
        try{$hadState=(bool)$this->state;$this->cleanup();$this->normal('AFTER_CLEANUP');if($hadState){$r=$this->http($this->state['config']['rpcPath'],['action'=>'visibility','nonce'=>bin2hex(random_bytes(16))]);if($r['status']===200){$data=json_decode($r['body'],true);if(is_array($data)&&($data['id']??null)===$this->state['id'])throw new RuntimeException('PROBE_STILL_AVAILABLE');}echo 'REVOKED_PROBE_UNAVAILABLE=PASS'.PHP_EOL;}}catch(Throwable $e){$rc=2;echo 'CLEANUP_HOLD='.($e instanceof RuntimeException?$e->getMessage():'UNEXPECTED_NATIVE_IO').PHP_EOL;}
        echo 'B1141_RESULT='.($rc===0?'OBSERVATION_COLLECTED_NOT_DEPLOYMENT_APPROVAL':'OBSERVATION_OR_CLEANUP_HOLD').PHP_EOL;return $rc;
    }
}
