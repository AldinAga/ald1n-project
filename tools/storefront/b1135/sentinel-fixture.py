import hashlib,hmac,json,os,pathlib,secrets,shutil,socket,subprocess,sys,time,urllib.request
tools=pathlib.Path(sys.argv[1]);old=pathlib.Path(sys.argv[2]);root=pathlib.Path(os.environ['RUNNER_TEMP'])/'b1135-sentinel';root.mkdir();results=[]
sha=lambda data:hashlib.sha256(data).hexdigest()
for mode,protection,want in [('memory',20,0),('disk',20,0),('blacklist',0,2),('maxsize',0,2)]:
    work=root/mode;work.mkdir();active=work/'current';(active/'public').mkdir(parents=True);(active/'storage/framework').mkdir(parents=True);incoming=work/'incoming';incoming.mkdir();private=work/'private';private.mkdir(mode=0o700)
    index=active/'public/index.php';index.write_text('<?php require __DIR__."/../storage/framework/maintenance.php"; http_response_code(404);')
    code=(tools/'FrontCacheEndpoint.php').read_bytes();digest=sha(code);library=incoming/('b1134-webcache-'+digest+'.php');library.write_bytes(code);job=secrets.token_hex(16);sentinel_code=('<?php return '+json.dumps(job)+';').encode();sentinel=incoming/('b1134-sentinel-'+sha(sentinel_code)+'.php');sentinel.write_bytes(sentinel_code)
    key=secrets.token_hex(32);token=secrets.token_hex(32);owner=secrets.token_hex(32);(private/'web-signing-key').write_text(key);(private/'web-signing-key').chmod(0o600);cfg={'schemaVersion':1,'id':job,'phase':'preflight','expires':int(time.time())+1500,'tokenSHA256':sha(token.encode()),'smokeTokenSHA256':sha(hmac.new(owner.encode(),b'private-http-smoke',hashlib.sha256).hexdigest().encode()),'ownerStamp':sha(owner.encode()),'incoming':str(incoming),'activeRoot':str(active),'rpcPath':'/__release_'+job,'indexSHA256':sha(index.read_bytes()),'librarySHA256':digest,'sentinel':str(sentinel),'sentinelSHA256':sha(sentinel_code),'allowedSapis':['cli-server'],'files':{}}
    payload=json.dumps(cfg,separators=(',',':'));envelope=private/'web.json';envelope.write_text(json.dumps({'payload':payload,'hmac':hmac.new(key.encode(),payload.encode(),hashlib.sha256).hexdigest()}));envelope.chmod(0o600)
    create=work/'maintenance.php';create.write_text('<?php require '+json.dumps(str(old/'ReleasePrimitives.php'))+'; ReleasePrimitives::ownMaintenance('+json.dumps(str(active/'storage'))+','+json.dumps(owner)+',time()+1500,time(),["library"=>'+json.dumps(str(library))+',"envelope"=>'+json.dumps(str(envelope))+',"frontSHA256"=>'+json.dumps(sha(token.encode()))+']);');subprocess.run(['php',str(create)],check=True,capture_output=True)
    sock=socket.socket();sock.bind(('127.0.0.1',0));port=sock.getsockname()[1];sock.close();(work/'disk').mkdir();blacklist=work/'blacklist.txt';blacklist.write_text(str(sentinel)+'\n' if mode=='blacklist' else '')
    args=['php','-d','opcache.enable=1','-d','opcache.enable_cli=1','-d','opcache.validate_timestamps=0','-d','opcache.file_update_protection='+str(protection),'-d','opcache.blacklist_filename='+str(blacklist),'-d','opcache.max_file_size='+('1' if mode=='maxsize' else '0'),'-d','opcache.file_cache='+(str(work/'disk') if mode=='disk' else ''),'-S','127.0.0.1:'+str(port),'-t',str(active/'public')]
    log=(work/'server.log').open('wb');server=subprocess.Popen(args,stdout=log,stderr=log);command=work/'command.json';command.write_text(json.dumps({'origin':'http://127.0.0.1:'+str(port),'rpc':cfg['rpcPath'],'token':token,'id':job,'librarySHA256':digest}))
    try:
        for n in range(50):
            try:
                connection=socket.create_connection(('127.0.0.1',port),.2);connection.close();break
            except OSError:time.sleep(.1)
        run=subprocess.run(['php',str(tools/'warm-client-fixture.php'),str(tools/'HostWebControl.php'),str(command)],capture_output=True,text=True,timeout=125);receipt=json.loads(run.stdout);assert run.returncode==want,(mode,run.returncode,receipt,run.stderr);results.append({'profile':mode,**receipt})
    finally:server.terminate();server.wait(timeout=5);log.close()
(root/'receipt.json').write_text(json.dumps(results,indent=2));print(json.dumps(results,indent=2))
