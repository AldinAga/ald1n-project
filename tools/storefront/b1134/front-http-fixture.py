import hashlib,hmac,json,os,pathlib,secrets,shutil,socket,subprocess,time,urllib.request,urllib.error,sys
stage=pathlib.Path(sys.argv[1]); source=pathlib.Path(sys.argv[2]); tools=pathlib.Path(sys.argv[3]);base=pathlib.Path(os.environ['RUNNER_TEMP'])/'b1134-front';base.mkdir()
class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self,*args,**kwargs):return None
opener=urllib.request.build_opener(NoRedirect())
def sha(data):return hashlib.sha256(data).hexdigest()
def run(file,*args):
    result=subprocess.run(['php','-d','opcache.enable_cli=0',str(file),*map(str,args)],capture_output=True,text=True)
    assert result.returncode==0,(file,result.returncode,result.stdout[-2500:],result.stderr[-2500:]);return result.stdout
def case(filecache,override=False):
    work=base/('override-reject' if override else ('disk' if filecache else 'memory'));work.mkdir();current=work/'current';candidate=work/'candidate';retained=work/'retained';failed=work/'failed';shutil.copytree(stage,current);shutil.copytree(stage,candidate)
    for rel in ['public/uploads','storage/app/public','storage/logs','storage/framework/sessions','storage/framework/cache/data','storage/framework/views','bootstrap/cache']:(current/rel).mkdir(parents=True,exist_ok=True)
    (candidate/'bootstrap/cache').mkdir(parents=True,exist_ok=True)
    (current/'public/storage').symlink_to(current/'storage/app/public');(current/'storage/app/public/existing').write_text('upload');(current/'storage/logs/existing').write_text('log');(current/'public/uploads/old').write_text('public-upload')
    db=work/'old-schema.sqlite';db.touch();environment='\n'.join(['APP_NAME=Ald1n','APP_ENV=testing','APP_DEBUG=false','APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=','APP_URL=http://127.0.0.1','APP_MAINTENANCE_DRIVER=file','DB_CONNECTION=sqlite','DB_DATABASE='+str(db),'SESSION_DRIVER=file','CACHE_STORE=file','CACHE_PREFIX=b1134_fixture','QUEUE_CONNECTION=sync','MAIL_MAILER=array','LOG_CHANNEL=single','PUBLIC_STOREFRONT_ENABLED=true','PUBLIC_CHECKOUT_ENABLED=true','TURNSTILE_ENABLED=false','MOBILE_PUSH_ENABLED=false','OPERATIONAL_EMAIL_NOTIFICATIONS=false',''])
    (current/'.env').write_text(environment);(current/'.env').chmod(0o600)
    command={'current':str(current),'candidate':str(candidate),'retained':str(retained),'failed':str(failed),'owner':'0'*64,'journal':str(work/'journal')};command_file=work/'command.json'
    def bridge(action,**more):
        command_file.write_text(json.dumps({**command,**more,'action':action}));return json.loads(run(tools/'front-fixture-prepare.php',tools/'ReleasePrimitives.php',tools/'InstallFileOps.php',command_file))
    preserved=bridge('private');journal=bridge('journal');command.update(journal);owner=journal['owner'];job=secrets.token_hex(16)
    incoming=work/'incoming';incoming.mkdir();private=work/'private';private.mkdir(mode=0o700);key=secrets.token_hex(32);token=secrets.token_hex(32);(private/'web-signing-key').write_text(key);(private/'web-signing-key').chmod(0o600);envelope=private/'web.json'
    code=source.read_text();digest=sha(code.encode());library=incoming/('b1134-webcache-'+digest+'.php');library.write_text(code);sentinel_code='<?php return '+json.dumps(job)+';';sentinel=incoming/('b1134-sentinel-'+sha(sentinel_code.encode())+'.php');sentinel.write_text(sentinel_code)
    smoke=hmac.new(owner.encode(),b'private-http-smoke',hashlib.sha256).hexdigest();rpc='/__ald1n_release_'+job
    cfg={'schemaVersion':1,'id':job,'phase':'preflight','expires':int(time.time())+1500,'tokenSHA256':sha(token.encode()),'smokeTokenSHA256':sha(smoke.encode()),'ownerStamp':sha(owner.encode()),'incoming':str(incoming),'activeRoot':str(current),'rpcPath':rpc,'indexSHA256':sha((current/'public/index.php').read_bytes()),'librarySHA256':digest,'sentinel':str(sentinel),'sentinelSHA256':sha(sentinel_code.encode()),'allowedSapis':['cli-server'],'files':{}}
    def signed():
        payload=json.dumps(cfg,separators=(',',':'));envelope.write_text(json.dumps({'payload':payload,'hmac':hmac.new(key.encode(),payload.encode(),hashlib.sha256).hexdigest()}));envelope.chmod(0o600)
    signed();started=int(time.time());context={'expires':started+1500,'started':started,'library':str(library),'envelope':str(envelope),'frontSHA256':sha(token.encode())}
    # Real Laravel old-schema boot and stale compiled view with a FUTURE timestamp.
    run(current/'artisan','package:discover','--no-ansi','--no-interaction');run(current/'artisan','view:cache','--no-ansi','--no-interaction')
    locator=work/'locate.php';locator.write_text('<?php require '+json.dumps(str(current/'vendor/autoload.php'))+';$app=require '+json.dumps(str(current/'bootstrap/app.php'))+';$app->make('+json.dumps(r'Illuminate\Contracts\Console\Kernel')+')->bootstrap();echo $app->make("blade.compiler")->getCompiledPath('+json.dumps(str(current/'resources/views/auth/login.blade.php'))+');')
    compiled=pathlib.Path(run(locator).strip());compiled.write_text(compiled.read_text()+'\nSTALE_OLD_COMPILED_SENTINEL\n');os.utime(compiled,(time.time()+86400,time.time()+86400))
    sock=socket.socket();sock.bind(('127.0.0.1',0));port=sock.getsockname()[1];sock.close();disk=work/'opcache';disk.mkdir();log=(work/'server.log').open('wb');server=subprocess.Popen(['php','-d','opcache.enable=1','-d','opcache.enable_cli=1','-d','opcache.validate_timestamps=0','-d','opcache.enable_file_override='+str(int(override)),'-d','opcache.file_update_protection=0','-d','opcache.file_cache='+ (str(disk) if filecache else ''),'-S','127.0.0.1:'+str(port),'-t',str(current/'public')],stdout=log,stderr=log)
    origin='http://127.0.0.1:'+str(port)
    def request(path,action=None,auth=True,smoking=False,**fields):
        data=None if action is None else json.dumps({'action':action,**fields}).encode();headers={'Content-Type':'application/json'}
        if action is not None and auth:headers.update({'X-Ald1n-Release-Front':token,'X-Ald1n-Release-Token':token})
        if smoking:headers.update({'X-Ald1n-Release-Smoke':smoke,'X-Ald1n-Release-Smoke-Nonce':secrets.token_hex(16)})
        try:
            with opener.open(urllib.request.Request(origin+path,data=data,headers=headers),timeout=35) as response:return response.status,response.read().decode(),dict(response.headers)
        except urllib.error.HTTPError as error:return error.code,error.read().decode(),dict(error.headers)
    def invalidate():
        results=[];total=len(cfg['files'])
        for offset in range(0,total,128):
            status,text,_=request(rpc,'invalidate',offset=offset);assert status==200,(offset,status,text[:500]);item=json.loads(text);assert item['offset']==offset and item['total']==total and item['checked']==min(128,total-offset) and item['invalidated']==item['checked'];results.append(item)
        return results
    try:
        for _ in range(100):
            try:
                if request('/login')[0]==200:break
            except OSError:time.sleep(.1)
        status,body,_=request('/login');assert status==200 and 'STALE_OLD_COMPILED_SENTINEL' in body,(status,body[-500:])
        old_m=bridge('maintenance',base=str(current),**context);time.sleep(1.1);new_m=bridge('maintenance',base=str(candidate),**context);assert list(old_m['files'].values())==list(new_m['files'].values())
        assert request('/login')[0]==503;assert request(rpc,'probe',auth=False)[0]==503;status,text,_=request(rpc,'probe')
        if override:
            assert status==403,('UNSAFE_FILE_OVERRIDE_NOT_REJECTED',status,text)
            assert current.is_dir() and candidate.is_dir() and not retained.exists()
            return {'enableFileOverrideRejectedBeforeCodeSwitch':True}
        assert status==200,(status,text);assert json.loads(text)['actualIndex'] is True
        assert request(rpc,'reset')[0]==403
        cfg['phase']='maintenance';cfg['files']=bridge('census',base=str(current));signed();assert request(rpc,'invalidate',offset=1)[0]==403;invalidate()
        bridge('switch');run(current/'artisan','package:discover','--no-ansi','--no-interaction');run(current/'artisan','view:cache','--no-ansi','--no-interaction')
        cfg['phase']='installed';cfg['files']=bridge('census',base=str(current));signed();invalidate();status,text,_=request(rpc,'off-proof');assert status==200,(status,text);off=json.loads(text);assert off['storefrontEnabled'] is False and off['checkoutEnabled'] is False
        cfg['files']=bridge('census',base=str(current));signed();invalidate();request(rpc,'probe')
        for path,expected in [('/login',200),('/admin/catalog',302),('/katalog',404),('/korpa',404),('/porucivanje',404)]:
            status,body,headers=request(path,smoking=True);assert status==expected,(path,status,body[-500:]);assert 'STALE_OLD_COMPILED_SENTINEL' not in body;assert 'laravel_maintenance=' not in headers.get('Set-Cookie','')
        assert request('/login')[0]==503
        (current/'storage/app/public/new-upload').write_text('new');(current/'storage/framework/sessions/new-session').write_text('new');(current/'storage/logs/existing').write_text('new-log')
        invalidate();bridge('rollback');cfg['phase']='rollback';cfg['files']=bridge('census',base=str(current));signed();invalidate();status,body,_=request('/login',smoking=True);assert status==200 and 'STALE_OLD_COMPILED_SENTINEL' in body
        assert (current/'storage/app/public/new-upload').read_text()=='new' and (current/'storage/framework/sessions/new-session').read_text()=='new' and (current/'storage/logs/existing').read_text()=='new-log'
        cfg['files']={'storage/framework/maintenance.php':sha((current/'storage/framework/maintenance.php').read_bytes())};signed();invalidate();cfg['phase']='revoked';signed();assert request(rpc,'probe')[0]!=200
        bridge('release',base=str(failed),receipt=new_m);bridge('release',base=str(current),receipt=old_m);assert request('/login')[0]==200;assert request(rpc,'probe',auth=False)[0]==404
        return {'actualFrontIndex':True,'fileCacheEnabled':filecache,'timestampsOff':True,'oldSchemaNoMigrations':True,'loginAndAdminEntry':True,'ONEnvOverriddenOFF':True,'privateSmokeWhilePublic503':True,'noMaintenanceCookieToBrowser':True,'freshCompiledViewsDespiteFutureOldMtime':True,'prePostForceInvalidationAndRollback':True,'privateDataWriteSurvivesRollback':True,'revokedDispatcherUnavailable':True,'noPublicAliasCreated':True}
    finally:server.terminate();server.wait(timeout=5);log.close()
results=[case(False),case(True),case(False,True)];receipt={'profiles':results,'LSPHPGroup':'HOST_SELF_CALIBRATION_REQUIRED_NOT_AVAILABLE_ON_CLI_SERVER','productionExecution':False};(base/'front-receipt.json').write_text(json.dumps(receipt,indent=2));print(json.dumps(receipt,indent=2))
