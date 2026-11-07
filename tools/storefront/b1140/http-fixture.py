import hashlib,hmac,json,os,pathlib,secrets,shutil,socket,subprocess,sys,time,urllib.error,urllib.request
tools=pathlib.Path(sys.argv[1]);index_source=pathlib.Path(sys.argv[2]);root=pathlib.Path(os.environ['RUNNER_TEMP'])/'b1140-http';root.mkdir();results=[]
sha=lambda b:hashlib.sha256(b).hexdigest()
for profile in ['enabled','disabled']:
    home=root/profile;home.mkdir();project=home/'project';incoming=project/'incoming';incoming.mkdir(parents=True);active=project/'apps/cms/current';(active/'public').mkdir(parents=True);(active/'storage/framework').mkdir(parents=True);(active/'vendor').mkdir();(active/'bootstrap').mkdir();(home/'.ald1n-b1140-state').mkdir(mode=0o700);shutil.copyfile(index_source,active/'public/index.php')
    assert sha((active/'public/index.php').read_bytes())=='3a0d98b25997752a47c5f065c3fa05ec518170bf97b679e9339b2e17b4f25d55'
    (active/'vendor/autoload.php').write_text('<?php namespace Illuminate\\Foundation {class Application {public function handleRequest($r):void {http_response_code(parse_url($_SERVER["REQUEST_URI"],PHP_URL_PATH)==="/login"?200:404);echo "NORMAL_APP";}}} namespace Illuminate\\Http {class Request {public static function capture(){return null;}}}')
    # Synthetic normal application; cache filler exists only in this disposable CI profile.
    fill=''
    if profile=='full':
        folder=home/'fillers';folder.mkdir()
        for n in range(160):(folder/('f'+str(n)+'.php')).write_text('<?php $x=['+','.join(map(str,range(4000)))+'];return count($x);')
        fill='foreach(glob('+json.dumps(str(folder/'*.php'))+') as $f)require $f;'
    (active/'bootstrap/app.php').write_text('<?php file_put_contents('+json.dumps(str(home/'normal-count'))+',"n",FILE_APPEND);'+fill+'return new \\Illuminate\\Foundation\\Application();')
    source=(tools/'ReadEndpoint.php').read_bytes();library=incoming/('b1140-probe-'+sha(source)+'.php');library.write_bytes(source);command=home/'command.json'
    def call(action,want=0,**extra):
        command.write_text(json.dumps({'action':action,'root':str(project),'home':str(home),'library':str(library),**extra}));r=subprocess.run(['php','-d','opcache.enable_cli=0',str(tools/'fixture-call.php'),str(tools),str(command)],capture_output=True,text=True);assert r.returncode==want,(profile,action,r.returncode,r.stdout,r.stderr);return r.stdout
    sock=socket.socket();sock.bind(('127.0.0.1',0));port=sock.getsockname()[1];sock.close();log=(home/'server.log').open('wb');server=subprocess.Popen(['php','-d','opcache.enable='+('0' if profile=='disabled' else '1'),'-d','opcache.enable_cli=1','-d','opcache.memory_consumption='+('8' if profile=='full' else '64'),'-d','opcache.interned_strings_buffer=1','-d','opcache.file_update_protection=0','-d','opcache.validate_timestamps=0','-S','127.0.0.1:'+str(port),'-t',str(active/'public')],stdout=log,stderr=log)
    def request(path,data=None,token=None):
        headers={'Content-Type':'application/json'}
        if token is not None:headers['X-Ald1n-Read-Token']=token
        try:
            with urllib.request.urlopen(urllib.request.Request('http://127.0.0.1:'+str(port)+path,data=None if data is None else json.dumps(data).encode(),headers=headers),timeout=10) as r:return r.status,r.read().decode()
        except urllib.error.HTTPError as e:return e.code,e.read().decode()
    try:
        for n in range(80):
            try:
                if request('/login')[0]==200:break
            except OSError:time.sleep(.1)
        assert request('/login')==(200,'NORMAL_APP')
        # Existing foreign marker is refused without replacement.
        maintenance=active/'storage/framework/maintenance.php';maintenance.write_text('<?php return;');before=maintenance.read_bytes();call('prepare',want=2);assert maintenance.read_bytes()==before;maintenance.unlink()
        call('prepare');pointer=json.loads((home/'.ald1n-b1140-state/current.json').read_text());state_path=pathlib.Path(pointer['private'])/'state.json';state=json.loads(state_path.read_text());rpc=state['config']['rpcPath'];token=state['token'];envelope=pathlib.Path(state['private'])/'web.json'
        assert maintenance.is_symlink() and not (active/'storage/framework/down').exists();assert request('/login')==(200,'NORMAL_APP');normal_before=(home/'normal-count').stat().st_size
        assert request(rpc,{'action':'visibility','nonce':secrets.token_hex(16)},'wrong')[0]==403
        assert request(rpc,{'action':'reset','nonce':secrets.token_hex(16)},token)[0]==403
        saved=envelope.read_bytes();envelope.write_text('{}');assert request(rpc,{'action':'visibility','nonce':secrets.token_hex(16)},token)[0]==403;envelope.write_bytes(saved)
        snapshots=[]
        for n in range(1):
            nonce=secrets.token_hex(16);status,body=request(rpc,{'action':'visibility','nonce':nonce},token);assert status==200,(profile,status,body);s=json.loads(body);assert s['nonce']==nonce and s['id']==state['id'] and s['actualIndex'] is True and s['sapi']=='cli-server';call('validate',snapshot=s,snapshotState=state,nonce=nonce);snapshots.append(s)
        observed=json.loads(call('observe',origin='http://127.0.0.1:'+str(port),state=str(state_path),nonce=secrets.token_hex(16)).split('OBSERVER_JSON=')[1].splitlines()[0]);assert observed['collectionComplete'] and observed['transportCompleted'] and observed['httpStatus']==200 and observed['curlRC']==0 and observed['responseScopeValid'];assert 'done' in observed['rawStages'] and 'other_php' in observed['targetCommCategories'] and 'targetCommLsphpSeen' in observed['blockedPredicates'];assert observed['psSnapshot']['targetListed'] is True and observed['psSnapshot']['targetCommCategory']=='other_php' and observed['psSampledThroughout'] is False;assert observed['calibrationWouldPass'] is False and not observed['deploymentApproval'];assert (home/'normal-count').stat().st_size==normal_before,'HELPER_BOOTSTRAPPED_APP'
        stale=dict(snapshots[-1]);stale['id']='previous-run';call('validate',want=2,snapshot=stale,snapshotState=state,nonce=nonce)
        if profile=='disabled':assert snapshots[-1]['cacheState']=='DISABLED' and snapshots[-1]['freeMemoryBytes'] is None and snapshots[-1]['cachedScripts'] is None
        elif profile=='full':assert snapshots[-1]['cacheFull'] is True,(profile,snapshots[-1])
        else:assert snapshots[-1]['cacheState']=='READABLE' and snapshots[-1]['freeMemoryBytes']>0
        # Foreign replacement is retained, then restore exact staged inode for fixture-only cleanup.
        maintenance.unlink();maintenance.symlink_to(home/'foreign-target');foreign=maintenance.lstat().st_ino;call('cleanup',want=2,state=str(state_path));assert maintenance.is_symlink() and maintenance.lstat().st_ino==foreign;maintenance.unlink();os.link(state['stagedLink'],maintenance,follow_symlinks=False);call('cleanup',state=str(state_path));assert not maintenance.is_symlink();assert request('/login')==(200,'NORMAL_APP');assert request(rpc,{'action':'visibility','nonce':secrets.token_hex(16)},token)[0]==404
        # Repeated fresh check and interrupted owned publication are both recoverable.
        call('prepare');pointer2=json.loads((home/'.ald1n-b1140-state/current.json').read_text());assert pointer2['id']!=pointer['id'];call('recover');assert not maintenance.is_symlink() and request('/login')==(200,'NORMAL_APP')
        results.append({'profile':profile,'oneActualIndexHeldResponse':True,'specificCommMismatchWithCompletedHTTP':True,'observer':observed,'cacheState':snapshots[-1]['cacheState'],'cacheFull':snapshots[-1]['cacheFull'],'freeMemoryBytes':snapshots[-1]['freeMemoryBytes'],'normalAppPreserved':True,'helperDidNotBootstrapApp':True,'wrongAuthActionTamperStaleRejected':True,'foreignCollisionPreserved':True,'ownedCleanupAndRepeatedOrphanRecovery':True})
    finally:server.terminate();server.wait(timeout=5);log.close()
(root/'receipt.json').write_text(json.dumps(results,indent=2));print(json.dumps(results,indent=2))
