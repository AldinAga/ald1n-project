"""Actual startup-disabled PHP endpoint; CGI/LiteSpeed override efficacy is NOT simulated as host proof."""
import hashlib,hmac,json,os,pathlib,secrets,socket,subprocess,sys,time,urllib.request,urllib.error
source=pathlib.Path(sys.argv[1]); base=pathlib.Path(os.environ['RUNNER_TEMP'])/'b1143-uncached';base.mkdir(mode=0o700)
def sha(b):return hashlib.sha256(b).hexdigest()
incoming=base/'incoming';incoming.mkdir();private=base/'private';private.mkdir(mode=0o700);public=base/'current/public';public.mkdir(parents=True)
key=secrets.token_hex(32);token=secrets.token_hex(32);job=secrets.token_hex(16);(private/'web-signing-key').write_text(key);(private/'web-signing-key').chmod(0o600)
code=source.read_bytes();digest=sha(code);library=incoming/('b1142-webcache-'+digest+'.php');library.write_bytes(code)
index=public/'index.php';index.write_text('<?php require '+json.dumps(str(library))+';WebCacheEndpoint::serve('+json.dumps(str(private/'web.json'))+');')
sentinel=incoming/'sentinel.php';sentinel.write_text('<?php return '+json.dumps(job)+';')
cfg={'schemaVersion':1,'id':job,'phase':'preflight','expires':int(time.time())+1500,'tokenSHA256':sha(token.encode()),'ownerStamp':sha(b'owner'),'incoming':str(incoming),'activeRoot':str(public.parent),'rpcPath':'/','indexSHA256':sha(index.read_bytes()),'librarySHA256':digest,'sentinel':str(sentinel),'sentinelSHA256':sha(sentinel.read_bytes()),'allowedSapis':['cli-server'],'files':{},'executionProfile':'UNCACHED_CMS','profileSHA256':sha(b'owned-override')}
payload=json.dumps(cfg,separators=(',',':'));env=private/'web.json';env.write_text(json.dumps({'payload':payload,'hmac':hmac.new(key.encode(),payload.encode(),hashlib.sha256).hexdigest()}));env.chmod(0o600)
sock=socket.socket();sock.bind(('127.0.0.1',0));port=sock.getsockname()[1];sock.close()
log=(base/'server.log').open('wb');server=subprocess.Popen(['php','-d','opcache.enable=0','-d','opcache.enable_cli=0','-S','127.0.0.1:'+str(port),'-t',str(public)],stdout=log,stderr=log)
opener=urllib.request.build_opener(urllib.request.ProxyHandler({}))
try:
    for attempt in range(100):
        try:
            with socket.create_connection(('127.0.0.1',port),timeout=.1):break
        except OSError:time.sleep(.05)
    request=urllib.request.Request('http://127.0.0.1:'+str(port)+'/',data=json.dumps({'action':'probe','nonce':secrets.token_hex(16)}).encode(),headers={'Content-Type':'application/json','X-Ald1n-Release-Token':token})
    try:
        with opener.open(request,timeout=10) as response:status=response.status;body=response.read().decode()
    except urllib.error.HTTPError as error:status=error.code;body=error.read().decode()
    assert status==200,('STARTUP_DISABLED_PROFILE_MUST_BE_SUPPORTED',status,body)
    receipt=json.loads(body);assert receipt['opcacheEnabled'] is False and receipt['executionProfile']=='UNCACHED_CMS'
    print(json.dumps({'startupDisabledActualEndpoint':True,'simulatedStartupConfiguration':True,'productionExecution':False}))
finally:
    server.terminate();server.wait(timeout=5);log.close()
