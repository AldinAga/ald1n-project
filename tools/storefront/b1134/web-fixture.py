import hashlib,hmac,json,os,pathlib,secrets,shutil,socket,subprocess,time,urllib.request,urllib.error,sys
source=pathlib.Path(sys.argv[1]).read_text(); base=pathlib.Path(os.environ['RUNNER_TEMP'])/'b1134-web';base.mkdir()
def sha(data): return hashlib.sha256(data).hexdigest()
def case(file_cache):
    work=base/('file-cache' if file_cache else 'memory');work.mkdir(); incoming=work/'incoming';incoming.mkdir();private=work/'private';private.mkdir(mode=0o700)
    current=work/'current';candidate=work/'candidate';retained=work/'retained'
    for root,label in [(current,'OLD'),(candidate,'NEW')]:
        for rel in ['public','bootstrap/cache','storage/framework/views']:(root/rel).mkdir(parents=True,exist_ok=True)
        (root/'public/sentinel.php').write_text('<?php echo "'+label+':";echo require __DIR__."/../bootstrap/cache/generated.php";echo ":";echo require __DIR__."/../storage/framework/views/generated.php";')
        (root/'bootstrap/cache/generated.php').write_text('<?php return "'+label+'_CACHE";')
        (root/'storage/framework/views/generated.php').write_text('<?php return "'+label+'_VIEW";')
    os.utime(current/'storage/framework/views/generated.php',(time.time()+86400,time.time()+86400))
    code=source.replace('echo json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);',"$result['executedVersion']='original';echo json_encode($result,JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES);")
    digest=sha(code.encode());library=incoming/('b1134-webcache-'+digest+'.php');library.write_text(code)
    envelope=private/'web.json';key=secrets.token_hex(32);(private/'web-signing-key').write_text(key);(private/'web-signing-key').chmod(0o600);token=secrets.token_hex(32);job=secrets.token_hex(16);alias='ald1n-release-'+job+'.php'
    entry_code='<?php require '+json.dumps(str(library))+';WebCacheEndpoint::serve('+json.dumps(str(envelope))+');'
    entry=incoming/('b1134-entry-'+sha(entry_code.encode())+'.php');entry.write_text(entry_code)
    for root in [current,candidate]:(root/'public'/alias).symlink_to(entry)
    config={'schemaVersion':1,'id':job,'phase':'preflight','expires':int(time.time())+1200,'tokenSHA256':sha(token.encode()),'incoming':str(incoming),'entry':str(entry),'activeRoot':str(current),'alias':alias,'librarySHA256':digest,'allowedSapis':['cli-server'],'files':{}}
    def signed():
        payload=json.dumps(config,separators=(',',':'));envelope.write_text(json.dumps({'payload':payload,'hmac':hmac.new(key.encode(),payload.encode(),hashlib.sha256).hexdigest()}));envelope.chmod(0o600)
    signed();sock=socket.socket();sock.bind(('127.0.0.1',0));port=sock.getsockname()[1];sock.close();cache=work/'opcache-files';cache.mkdir(mode=0o700)
    cmd=['php','-d','opcache.enable=1','-d','opcache.enable_cli=1','-d','opcache.validate_timestamps=0','-d','opcache.file_update_protection=0','-d','opcache.file_cache='+ (str(cache) if file_cache else ''),'-S','127.0.0.1:'+str(port),'-t',str(current/'public')]
    log=(work/'server.log').open('wb');server=subprocess.Popen(cmd,stdout=log,stderr=log);origin='http://127.0.0.1:'+str(port)
    def request(path,action=None,auth=True,offset=None):
        data=None if action is None else json.dumps({'action':action,**({'offset':offset} if offset is not None else {})}).encode();headers={'Content-Type':'application/json'}
        if auth:headers['X-Ald1n-Release-Token']=token
        try:
            with urllib.request.urlopen(urllib.request.Request(origin+'/'+path,data=data,headers=headers),timeout=10) as response:return response.status,response.read().decode()
        except urllib.error.HTTPError as error:return error.code,error.read().decode()
    def invalidate():
        total=len(config['files']);responses=[]
        for offset in range(0,total,128):
            status,text=request(alias,'invalidate',offset=offset);assert status==200,(status,text);data=json.loads(text);assert data['offset']==offset and data['total']==total and data['checked']==min(128,total-offset) and data['invalidated']==data['checked'];responses.append(data)
        return responses
    try:
        for _ in range(100):
            if server.poll() is not None:raise AssertionError('server stopped')
            try:
                if request('sentinel.php')[0]==200:break
            except OSError:time.sleep(.05)
        assert request('sentinel.php')[1]=='OLD:OLD_CACHE:OLD_VIEW';assert request(alias,'probe',False)[0]==403
        status,text=request(alias,'probe');assert status==200,(status,text);assert json.loads(text)['executedVersion']=='original'
        paths=['public/sentinel.php','bootstrap/cache/generated.php','storage/framework/views/generated.php']
        config['phase']='maintenance';config['files']={rel:sha((current/rel).read_bytes()) for rel in paths};signed()
        assert request(alias,'invalidate',False,0)[0]==403;assert request(alias,'invalidate',True,1)[0]==403
        old=invalidate();assert sum(x['cachedBefore'] for x in old)>=3
        current.rename(retained);candidate.rename(current);config['phase']='installed';config['files']={rel:sha((current/rel).read_bytes()) for rel in paths};signed();invalidate()
        assert request('sentinel.php')[1]=='NEW:NEW_CACHE:NEW_VIEW'
        # Replay is safe, and generated-cache hashes are required rather than guessed.
        invalidate();original=config['files']['bootstrap/cache/generated.php'];config['files']['bootstrap/cache/generated.php']='0'*64;signed();assert request(alias,'invalidate',offset=0)[0]==409;config['files']['bootstrap/cache/generated.php']=original;signed()
        # Immutable helper upgrade cannot echo a new disk hash while running old fixed-path bytecode.
        upgraded=code.replace("executedVersion']='original'","executedVersion']='upgraded'");newdigest=sha(upgraded.encode());newlib=incoming/('b1134-webcache-'+newdigest+'.php');newlib.write_text(upgraded)
        newentrycode='<?php require '+json.dumps(str(newlib))+';WebCacheEndpoint::serve('+json.dumps(str(envelope))+');';newentry=incoming/('b1134-entry-'+sha(newentrycode.encode())+'.php');newentry.write_text(newentrycode);(current/'public'/alias).unlink();(current/'public'/alias).symlink_to(newentry);config['librarySHA256']=newdigest;config['entry']=str(newentry);signed()
        assert json.loads(request(alias,'probe')[1])['executedVersion']=='upgraded'
        # PRE-new -> rollback -> POST-old, all old generated paths stay present until invalidated.
        invalidate();current.rename(work/'failed');retained.rename(current);(current/'public'/alias).unlink();(current/'public'/alias).symlink_to(newentry);config['phase']='rollback';config['files']={rel:sha((current/rel).read_bytes()) for rel in paths};signed();invalidate();assert request('sentinel.php')[1]=='OLD:OLD_CACHE:OLD_VIEW'
        config['expires']=int(time.time())-1;signed();assert request(alias,'probe')[0]==403
        (current/'public'/alias).unlink();assert request(alias,'probe')[0]==404
        return {'fileCacheConfigured':file_cache,'actualHTTP':True,'timestampsDisabled':True,'sourceAndGeneratedCacheSwitchRollback':True,'wrongTokenNoInvalidation':True,'strictChunksReplayHashMismatch':True,'immutableHelperUpgrade':True,'expiredDenied':True,'aliasCleanup404':True}
    finally:
        server.terminate();server.wait(timeout=5);log.close()
results=[case(False),case(True)];receipt={'cases':results,'productionExecution':False,'actualWebProtocolOnly':True};(base/'web-receipt.json').write_text(json.dumps(receipt,indent=2));print(json.dumps(receipt,indent=2))
