import hashlib,http.client,json,os,pathlib,shutil,socket,sqlite3,ssl,subprocess,sys,threading,time
# Real Laravel runtime behind a real TLS listener for cms.ald1n.com, driven by the real LeanWeb/FreshnessProbe/ReleasePrimitives.
# Shows the same-path stale-code problem with OPcache, then the targeted invalidation fixing it, on install AND on rollback.
stage=pathlib.Path(sys.argv[1]);b1144=pathlib.Path(sys.argv[2]).resolve();b1134=pathlib.Path(sys.argv[3]).resolve();base=pathlib.Path(os.environ['RUNNER_TEMP']).resolve()/'b1144-front';base.mkdir()
# Short, non-default realpath TTL: the barrier hold must be derived from the probe-reported value, not from a constant.
REALPATH_TTL=30;HOLD_MARGIN=5
PENDING=['2026_10_23_020000_create_public_order_accesses','2026_10_23_020100_create_public_checkout_attempts','2026_10_24_030000_add_retail_customer_role']
def sha(data):return hashlib.sha256(data).hexdigest()
def free():
    s=socket.socket();s.bind(('127.0.0.1',0));p=s.getsockname()[1];s.close();return p
def bridge(action,**fields):
    cmd=base/'bridge.json';cmd.write_text(json.dumps({'action':action,'b1134':str(b1134),'b1144':str(b1144),**fields}))
    r=subprocess.run(['php','-d','opcache.enable_cli=0',str(b1144/'front-bridge.php'),str(cmd)],capture_output=True,text=True)
    assert r.returncode==0,(action,r.returncode,r.stdout[-2000:],r.stderr[-2000:]);return json.loads(r.stdout)
def artisan(tree,*args):
    r=subprocess.run(['php','-d','opcache.enable_cli=0',str(tree/'artisan'),*args,'--no-ansi','--no-interaction'],capture_output=True,text=True,cwd=tree);assert r.returncode==0,(args,r.stdout[-1500:],r.stderr[-1500:])
work=base/'tree';work.mkdir();current=work/'current';candidate=work/'candidate';retained=work/'retained';failed=work/'failed'
shutil.copytree(stage,current,symlinks=True);shutil.copytree(stage,candidate,symlinks=True)
for rel in ['storage/app/public','storage/logs','storage/framework/sessions','storage/framework/cache/data','storage/framework/views','bootstrap/cache']:(current/rel).mkdir(parents=True,exist_ok=True)
(candidate/'bootstrap/cache').mkdir(parents=True,exist_ok=True);(current/'public/storage').symlink_to(current/'storage/app/public');(current/'storage/app/public/existing').write_text('upload')
# Same path, same size, SAME mtime, different bytes: exactly what timestamp revalidation cannot see after a directory switch.
stamp=time.time()-3600
for tree,word in [(current,'OLD'),(candidate,'NEW')]:
    (tree/'app/B1144FreshnessMarker.php').write_text("<?php return '"+word+"-B1144-MARKER';\n");os.utime(tree/'app/B1144FreshnessMarker.php',(stamp,stamp))
    (tree/'public/b1144-marker.php').write_text("<?php echo require __DIR__.'/../app/B1144FreshnessMarker.php';\n");os.utime(tree/'public/b1144-marker.php',(stamp,stamp))
    # How THIS worker resolves the private .env path, with the exact string Laravel uses (basePath.'/.env'). It never clears the
    # realpath cache unless asked: op=clear stands in for any application unlink/rename, which PHP follows with a full cache clear.
    (tree/'public/b1144-realpath.php').write_text("<?php if(($_GET['op']??'')==='clear'){clearstatcache(true);echo 'CLEARED';return;}$env=dirname(__DIR__).'/.env';$real=realpath($env);$bytes=@file_get_contents($env);echo json_encode(['realpath'=>$real,'readable'=>is_string($bytes)&&str_contains($bytes,'APP_KEY=base64:'),'isFile'=>is_file($env)]);\n");os.utime(tree/'public/b1144-realpath.php',(stamp,stamp))
db=work/'production-like.sqlite';con=sqlite3.connect(db);con.execute('create table migrations (id integer primary key autoincrement not null, migration varchar not null, batch integer not null)')
names=sorted(p.stem for p in (stage/'database/migrations').glob('*.php'));assert len(names)==71 and all(n in names for n in PENDING),len(names)
con.executemany('insert into migrations (migration,batch) values (?,1)',[(n,) for n in names if n not in PENDING]);con.commit();con.close()
env=['APP_NAME=Ald1n','APP_ENV=production','APP_DEBUG=false','APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=','APP_URL=https://cms.ald1n.com','APP_MAINTENANCE_DRIVER=file','DB_CONNECTION=sqlite','DB_DATABASE='+str(db),'SESSION_DRIVER=file','CACHE_STORE=file','QUEUE_CONNECTION=sync','MAIL_MAILER=array','LOG_CHANNEL=single','LOG_LEVEL=debug','PUBLIC_STOREFRONT_ENABLED=true','PUBLIC_CHECKOUT_ENABLED=true','TURNSTILE_ENABLED=false','MOBILE_PUSH_ENABLED=false','OPERATIONAL_EMAIL_NOTIFICATIONS=false','']
(current/'.env').write_text('\n'.join(env));(current/'.env').chmod(0o600)
private=bridge('private',current=str(current),candidate=str(candidate),retained=str(retained));journal=bridge('journal',current=str(current),candidate=str(candidate),retained=str(retained),failed=str(failed),journal=str(work/'journal.jsonl'))
owner=journal['owner'];plan=journal['plan'];job=os.urandom(16).hex();started=int(time.time());expires=started+1500
artisan(current,'package:discover');artisan(current,'view:cache')
# Certificate for cms.ald1n.com only; the client trusts exactly this CA file. /etc/hosts also pins the name to 127.0.0.1 in CI.
cert=work/'cms.pem';key=work/'cms.key'
subprocess.run(['openssl','req','-x509','-newkey','rsa:2048','-nodes','-days','2','-subj','/CN=cms.ald1n.com','-addext','subjectAltName=DNS:cms.ald1n.com','-keyout',str(key),'-out',str(cert)],check=True,capture_output=True)
state={'backend':0}
tls_port=free();server_ctx=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER);server_ctx.load_cert_chain(cert,key);listener=socket.socket();listener.setsockopt(socket.SOL_SOCKET,socket.SO_REUSEADDR,1);listener.bind(('127.0.0.1',tls_port));listener.listen(64)
def pump(a,b):
    try:
        while True:
            d=a.recv(65536)
            if not d:break
            b.sendall(d)
    except OSError:pass
    finally:
        for s in (a,b):
            try:s.shutdown(socket.SHUT_RDWR)
            except OSError:pass
def handle(raw):
    try:tls=server_ctx.wrap_socket(raw,server_side=True)
    except (ssl.SSLError,OSError):raw.close();return
    try:back=socket.create_connection(('127.0.0.1',state['backend']))
    except OSError:tls.close();return
    t=threading.Thread(target=pump,args=(back,tls),daemon=True);t.start();pump(tls,back);t.join(timeout=60);tls.close();back.close()
def serve():
    while True:
        c,_=listener.accept();threading.Thread(target=handle,args=(c,),daemon=True).start()
threading.Thread(target=serve,daemon=True).start()
closed_port=free()
client_ctx=ssl.create_default_context(cafile=str(cert));client_ctx.check_hostname=False
def raw(path,headers=None,method='GET'):
    conn=http.client.HTTPSConnection('127.0.0.1',tls_port,context=client_ctx,timeout=30)
    conn.putrequest(method,path,skip_host=True,skip_accept_encoding=True);conn.putheader('Host',(headers or {}).pop('Host','cms.ald1n.com'))
    for k,v in (headers or {}).items():conn.putheader(k,v)
    conn.endheaders();r=conn.getresponse();body=r.read().decode(errors='replace');conn.close();return r.status,body
server=None;log=None
def start(opcache):
    global server,log
    port=free();state['backend']=port;log=(work/('server-'+str(opcache)+'.log')).open('wb')
    server=subprocess.Popen(['php','-d','opcache.enable='+str(int(opcache)),'-d','opcache.enable_cli='+str(int(opcache)),'-d','opcache.validate_timestamps=1','-d','opcache.revalidate_freq=2','-d','opcache.file_update_protection=0','-d','realpath_cache_ttl='+str(REALPATH_TTL),'-S','127.0.0.1:'+str(port),'-t',str(current/'public')],stdout=log,stderr=log,cwd=str(current))
    for _ in range(150):
        try:
            if raw('/up')[0] in (200,503):return
        except OSError:time.sleep(.1)
    raise AssertionError('SERVER_NOT_READY')
def stop():
    server.terminate();server.wait(timeout=10);log.close()
web={'loopbackPort':tls_port,'publicPort':tls_port,'caFile':str(cert)}
def status(path,expected,stamp=None,attempts=1):return bridge('status',path=path,expected=expected,stamp=stamp,attempts=attempts,**web)
roots=[str(current),str(candidate),str(retained),str(failed)]
marker_rel='app/B1144FreshnessMarker.php';old_hash=sha((current/marker_rel).read_bytes());new_hash=sha((candidate/marker_rel).read_bytes())
def freshness(expected_hash,label,fallback=False,sapis=('cli-server',)):
    w=bridge('probe-write',current=str(current),id=job,roots=roots,expected={marker_rel:expected_hash})
    name,token=w['name'],w['token']
    denied={}
    for case,headers,method in [('noToken',{},'GET'),('wrongToken',{'X-Ald1n-Release-Probe':'0'*64},'GET'),('wrongHost',{'Host':'shop.ald1n.com','X-Ald1n-Release-Probe':token},'GET'),('post',{'X-Ald1n-Release-Probe':token},'POST'),('head',{'X-Ald1n-Release-Probe':token},'HEAD')]:
        st,body=raw('/'+name,dict(headers),method);denied[case]=st;assert st==404 and body=='',(label,case,st,body[:200])
    connection=dict(web);connection['loopbackPort']=closed_port if fallback else tls_port
    r=bridge('probe-request',current=str(current),id=job,name=name,token=token,expected={marker_rel:expected_hash},sapis=list(sapis),**connection)
    removed=bridge('probe-remove',current=str(current),name=name,sha256=w['sha256'])
    st,_=raw('/'+name,{'X-Ald1n-Release-Probe':token});assert st!=200,('PROBE_STILL_SERVED',st)
    assert removed['removed'] is True and not (current/'public'/name).exists()
    for leak in (name,token):assert leak not in r.get('output',''),'PROBE_SECRET_PRINTED'
    return r,denied
receipt={}
try:
    start(True)
    assert status('/login',200)['pass'];assert raw('/b1144-marker.php')[1]=='OLD-B1144-MARKER'
    baseline=bridge('logs-baseline',logs=str(current/'storage/logs'))
    new_m=bridge('own',base=str(candidate),owner=owner,expires=expires,started=started);old_m=bridge('own',base=str(current),owner=owner,expires=expires,started=started)
    barrier=status('/login',503,old_m['ownerSHA256']);assert barrier['pass'],barrier;wrong=status('/login',503,'f'*64);assert not wrong['pass'] and wrong['code']=='PUBLIC_HTTP_SMOKE_503'
    bridge('switch',current=str(current),candidate=str(candidate),retained=str(retained),journal=str(work/'journal.jsonl'),owner=owner);swapped_install=int(time.time())
    artisan(current,'package:discover');artisan(current,'view:cache')
    time.sleep(2.5);stale=raw('/b1144-marker.php')[1];assert stale=='OLD-B1144-MARKER',('STALE_CODE_NOT_REPRODUCED',stale)
    installed,denied=freshness(new_hash,'installed');probed_install=int(time.time());assert installed['pass'],installed;s=installed['summary']
    assert installed['route']=='LOOPBACK_TLS_ORIGIN' and s['opcacheEnabled'] is True and s['targetedInvalidations']>=1 and s['globalReset'] is False and s['sapi']=='cli-server' and s['docrootMatches'] is True,s
    assert s['realpathCacheTtl']==REALPATH_TTL and isinstance(s['realpathCacheSize'],str) and s['holdSeconds']==max(REALPATH_TTL,s['revalidateFreq'])+HOLD_MARGIN,('REALPATH_INI_NOT_REPORTED',s)
    time.sleep(s['waitSeconds']);fresh=raw('/b1144-marker.php')[1];assert fresh=='NEW-B1144-MARKER',('TARGETED_INVALIDATION_DID_NOT_FRESHEN',fresh)
    fallback,_=freshness(new_hash,'installed-fallback',fallback=True);assert fallback['pass'] and fallback['route']=='PUBLIC_TLS_HOSTNAME',fallback
    wrong_sapi,_=freshness(new_hash,'wrong-sapi',sapis=('litespeed',));assert not wrong_sapi['pass'] and wrong_sapi['code']=='FRESHNESS_PROBE_SAPI',wrong_sapi
    stale_tree,_=freshness(old_hash,'wrong-tree');assert not stale_tree['pass'] and stale_tree['code']=='FRESHNESS_PROBE_TREE_CONTENT',stale_tree
    cli=bridge('cli',current=str(current));assert cli['aboutRC']==0 and cli['statusRC']==0 and cli['parsed']=={'ran':68,'pending':PENDING},cli
    # The coordinator hold, by the product code, from the probe-reported values; the lift comes only after it.
    hold_install=bridge('hold',swappedAt=swapped_install,probedAt=probed_install,summaries=[s])
    assert hold_install['liftNotBefore']==max(swapped_install+REALPATH_TTL,probed_install+s['revalidateFreq'])+HOLD_MARGIN and int(time.time())>=hold_install['liftNotBefore'],('INSTALL_HOLD_NOT_DERIVED',hold_install)
    bridge('release',receipt=old_m,base=str(retained));bridge('release',receipt=new_m,base=str(current))
    smokes={}
    for path,expected in [('/login',200),('/up',200),('/katalog',404),('/registracija',404),('/korpa',404)]:
        r=status(path,expected,attempts=1);smokes[path]=r['pass'];assert r['pass'],(path,r)
    scan=bridge('logs-scan',logs=str(current/'storage/logs'),baseline=baseline);assert scan['newErrorLines']==0,(scan,(retained/'storage/logs/laravel.log').read_text()[-3000:] if (retained/'storage/logs/laravel.log').exists() else '')
    # The live new release resolves .env and storage through the private symlinks into the retained tree. After a cache clear
    # (any app unlink/rename does one) live traffic caches those resolutions in this worker: the state a rollback inherits.
    assert raw('/b1144-realpath.php?op=clear')[1]=='CLEARED';assert status('/login',200,attempts=1)['pass'];live_env=json.loads(raw('/b1144-realpath.php')[1])
    assert live_env['readable'] is True and str(live_env['realpath']).endswith('/tree/retained/.env'),('NEW_RELEASE_ENV_NOT_LINKED',live_env)
    (current/'storage/app/public/after-lift').write_text('new-user-bytes')
    # Live traffic on the new code puts the NEW marker in OPcache; a rollback to the same path must then be proven fresh again.
    assert raw('/b1144-marker.php')[1]=='NEW-B1144-MARKER'
    # Post-lift failure path: barrier back on both trees, swap back, restored-tree freshness, lift, /login 200.
    new_again=bridge('own',base=str(current),owner=owner,expires=expires,started=started);old_again=bridge('own',base=str(retained),owner=owner,expires=expires,started=started);assert len(new_again['files'])==2 and len(old_again['files'])==2
    assert status('/login',503,old_m['ownerSHA256'])['pass']
    assert bridge('rollback',plan=plan,journal=str(work/'journal.jsonl'),owner=owner,failed=str(failed))['result']=='OLD_RESTORED';swapped_restore=int(time.time())
    # Negative control: right after the swap back, this worker still resolves .env into the now-missing retained path.
    stale_env=json.loads(raw('/b1144-realpath.php')[1]);assert stale_env['readable'] is False,('STALE_REALPATH_NOT_REPRODUCED',stale_env)
    time.sleep(2.5);stale_back=raw('/b1144-marker.php')[1];assert stale_back=='NEW-B1144-MARKER',('ROLLBACK_STALE_NOT_REPRODUCED',stale_back)
    restored,_=freshness(old_hash,'restored');probed_restore=int(time.time());assert restored['pass'] and restored['summary']['targetedInvalidations']>=1,restored;rs=restored['summary']
    assert rs['realpathCacheTtl']==REALPATH_TTL,('REALPATH_INI_NOT_REPORTED',rs)
    time.sleep(rs['waitSeconds']);assert raw('/b1144-marker.php')[1]=='OLD-B1144-MARKER'
    hold_restore=bridge('hold',swappedAt=swapped_restore,probedAt=probed_restore,summaries=[rs])
    assert hold_restore['liftNotBefore']==max(swapped_restore+REALPATH_TTL,probed_restore+rs['revalidateFreq'])+HOLD_MARGIN and int(time.time())>=hold_restore['liftNotBefore'],('RESTORE_HOLD_NOT_DERIVED',hold_restore)
    bridge('release',receipt=new_m,base=str(failed));bridge('release',receipt=old_m,base=str(current))
    # Strict: the FIRST /login after the lift passes, one attempt, no waiting beyond the coordinator hold.
    first_login=status('/login',200,attempts=1)
    if not first_login['pass']:
        logfile=current/'storage/logs/laravel.log';text=logfile.read_text() if logfile.exists() else 'NO_LOG';heads=[l[:700] for l in text.splitlines() if l.startswith('[20')][-4:]
        print('ROLLBACK_LOGIN_DIAGNOSTIC',json.dumps(first_login),json.dumps(heads),json.dumps(raw('/b1144-realpath.php')[1]),flush=True)
    assert first_login['pass'];assert (current/'storage/app/public/after-lift').read_text()=='new-user-bytes'
    healed_env=json.loads(raw('/b1144-realpath.php')[1]);assert healed_env['readable'] is True and str(healed_env['realpath']).endswith('/tree/current/.env'),('REALPATH_NOT_HEALED',healed_env)
    stop()
    # OPcache-off profile: the same probe validates without invalidation candidates and needs no wait.
    start(False)
    off,_=freshness(old_hash,'opcache-off');assert off['pass'] and off['summary']['opcacheEnabled'] is False and off['summary']['targetedInvalidations']==0,off
    assert raw('/b1144-marker.php')[1]=='OLD-B1144-MARKER'
    stop();server=None
    receipt={'realLaravelRuntime':True,'tlsHostnameCertificateForCmsAld1nCom':True,'sameMtimeSameSizeStaleCodeReproduced':{'install':True,'rollback':True},'targetedInvalidationFreshened':{'install':True,'rollback':True},'loopbackRoute':installed['route'],'fallbackRoute':fallback['route'],'probeDenied404':denied,'probeRemovedAfterUse':True,'probeNameOrTokenPrinted':False,'freshnessSummary':s,'wrongSapi':'HOLD','wrongTree':'HOLD','barrier503WithOwnerStamp':True,'foreignStamp':'HOLD','cliAboutRC':0,'migrateStatus':cli['parsed'],'postLiftSmokes':smokes,'newLogErrorLines':scan['newErrorLines'],'rollbackAfterLift':'OLD_RESTORED_FRESHNESS_VERIFIED_LOGIN_200','realpathCacheTtlReported':REALPATH_TTL,'staleEnvRealpathAfterRollbackReproduced':True,'barrierHold':{'install':hold_install,'restore':hold_restore},'firstLoginAfterRestoreLiftAttempts':1,'opcacheOffProfile':off['summary'],'productionContacted':False}
finally:
    if server is not None and server.poll() is None:server.terminate()
(base/'front-receipt.json').write_text(json.dumps(receipt,indent=2));print(json.dumps(receipt,indent=2))
