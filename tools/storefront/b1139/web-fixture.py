import json,os,pathlib,secrets,socket,subprocess,sys,time,urllib.request
tools=pathlib.Path(sys.argv[1]);old=pathlib.Path(sys.argv[2]);work=pathlib.Path(os.environ['RUNNER_TEMP'])/'b1139-web';work.mkdir();current=work/'current';current.mkdir();token=secrets.token_hex(32)
sentinel=work/'sentinel.php';sentinel.write_text('<?php return "PINNED_NONCE";');value=current/'value.php';value.write_text('<?php return "OLD";');other=work/'new-sentinel.php';other.write_text('<?php return "NEW_NONCE";')
for n in range(400): (work/('fill'+str(n)+'.php')).write_text('<?php return '+str(n)+';')
index=work/'index.php';index.write_text('''<?php
if(!hash_equals('''+json.dumps(token)+''',$_SERVER['HTTP_X_TOKEN']??'')){http_response_code(403);exit;}
$action=$_GET['action']??'probe';$sentinel=__DIR__.($action==='new'?'/new-sentinel.php':'/sentinel.php');$prior=opcache_is_script_cached($sentinel);$nonce=require $sentinel;
if($action==='fill'){for($n=0;$n<400;$n++){require __DIR__.'/fill'.$n.'.php';$s=opcache_get_status(false);if($s['cache_full'])break;}}
if($action==='near'){for($n=0;$n<400;$n++){require __DIR__.'/fill'.$n.'.php';$s=opcache_get_status(false);if($s['opcache_statistics']['num_cached_keys'] >= $s['opcache_statistics']['max_cached_keys']-12)break;}}
if($action==='invalidate'){if(!opcache_invalidate(__DIR__.'/current/value.php',true))throw new RuntimeException('INVALIDATE');}
$s=opcache_get_status(false);echo json_encode(['status'=>'ok','sentinelPreviouslyCached'=>$prior,'nonce'=>$nonce,'value'=>require __DIR__.'/current/value.php','sentinelDiagnostics'=>['fileUpdateProtectionSeconds'=>0,'sentinelAgeSeconds'=>10,'maxFileSizeBytes'=>0,'sentinelSizeBytes'=>filesize($sentinel),'freeMemoryBytes'=>$s['memory_usage']['free_memory'],'cachedScripts'=>$s['opcache_statistics']['num_cached_scripts'],'maxCachedKeys'=>$s['opcache_statistics']['max_cached_keys'],'cacheFull'=>$s['cache_full']],'cachedKeys'=>$s['opcache_statistics']['num_cached_keys'],'cacheStartTime'=>$s['opcache_statistics']['start_time']]);
''')
sock=socket.socket();sock.bind(('127.0.0.1',0));port=sock.getsockname()[1];sock.close();origin='http://127.0.0.1:'+str(port)
def start():
    server=subprocess.Popen(['php','-d','opcache.enable=1','-d','opcache.enable_cli=1','-d','opcache.memory_consumption=64','-d','opcache.interned_strings_buffer=4','-d','opcache.max_accelerated_files=200','-d','opcache.validate_timestamps=0','-d','opcache.file_update_protection=0','-S','127.0.0.1:'+str(port),'-t',str(work)],stdout=subprocess.DEVNULL,stderr=subprocess.DEVNULL)
    for n in range(50):
        try: c=socket.create_connection(('127.0.0.1',port),.1);c.close();return server
        except OSError: time.sleep(.1)
    raise AssertionError('SERVER_START')
def request(action='probe'):
    r=urllib.request.Request(origin+'/index.php?action='+action,headers={'X-Token':token});return json.loads(urllib.request.urlopen(r,timeout=5).read())
server=start();results={}
try:
    cold=request();later=request();assert cold['sentinelPreviouslyCached'] is False and later['sentinelPreviouslyCached'] is True;results['coldThenIndependentLaterCached']=True
    near=request('near');assert near['cachedKeys']>=near['sentinelDiagnostics']['maxCachedKeys']-12
    request('invalidate');value.rename(current/'retained-value.php');value.write_text('<?php return "NEW";');request('invalidate');assert request()['value']=='NEW';request('invalidate');value.rename(current/'failed-value.php');(current/'retained-value.php').rename(value);request('invalidate');assert request()['value']=='OLD';results['sameCanonicalPathNearFullSwitchRollback']=True
    full=request('fill');assert full['sentinelDiagnostics']['cacheFull'] and full['sentinelDiagnostics']['freeMemoryBytes']>1000000;assert request()['sentinelPreviouslyCached'] is True;missing=request('new');assert missing['sentinelPreviouslyCached'] is False
    command=work/'test-client.php';command.write_text('<?php require '+json.dumps(str(old/'HostWebControl.php'))+'; $m=new ReflectionMethod(HostWebControl::class,"warmSentinel");try{$m->invoke(null,fn($timeout)=>json_decode(file_get_contents('+json.dumps(origin+'/index.php?action=new')+',false,stream_context_create(["http"=>["header"=>'+json.dumps('X-Token: '+token)+']])),true));exit(9);}catch(RuntimeException $e){if($e->getMessage()!=="WEB_CACHE_SENTINEL_CAPACITY_OR_SIZE_HOLD")throw $e;echo "FULL_UNCACHED_SENTINEL_HOLD";exit(0);}')
    run=subprocess.run(['php',str(command)],capture_output=True,text=True,timeout=5);assert run.returncode==0,(run.stdout,run.stderr);results['hashFullWithFreeMemoryNewSentinelFailsClosed']=True;results['effectiveMaxCachedKeys']=full['sentinelDiagnostics']['maxCachedKeys']
finally: server.terminate();server.wait(timeout=5)
server=start()
try:
    restarted=request();assert restarted['sentinelPreviouslyCached'] is False;results['restartLosesResidentMarker']=True
finally: server.terminate();server.wait(timeout=5)
print(json.dumps({'realPHPHTTP':True,'notLSAPIOrHostRestartProof':True,**results},indent=2))
