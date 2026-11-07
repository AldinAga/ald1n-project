import hashlib,hmac,http.client,http.server,json,os,pathlib,secrets,socket,ssl,subprocess,sys,threading,time
tools=pathlib.Path(sys.argv[1]);old=pathlib.Path(sys.argv[2]);root=pathlib.Path(os.environ['RUNNER_TEMP'])/'b1140-old-red';root.mkdir();active=root/'current';(active/'public').mkdir(parents=True);(active/'storage/framework').mkdir(parents=True);incoming=root/'incoming';incoming.mkdir();private=root/'private';private.mkdir(mode=0o700)
sha=lambda data:hashlib.sha256(data).hexdigest()
index=active/'public/index.php';index.write_text('<?php require __DIR__."/../storage/framework/maintenance.php"; http_response_code(404);')
code=(old/'FrontCacheEndpoint.php').read_bytes();digest=sha(code);library=incoming/('b1134-webcache-'+digest+'.php');library.write_bytes(code);job=secrets.token_hex(16);sentinel_code=('<?php return '+json.dumps(job)+';').encode();sentinel=incoming/('b1134-sentinel-'+sha(sentinel_code)+'.php');sentinel.write_bytes(sentinel_code)
key=secrets.token_hex(32);token=secrets.token_hex(32);owner=secrets.token_hex(32);(private/'web-signing-key').write_text(key);(private/'web-signing-key').chmod(0o600)
cfg={'schemaVersion':1,'id':job,'phase':'preflight','expires':int(time.time())+600,'tokenSHA256':sha(token.encode()),'smokeTokenSHA256':sha(token.encode()),'ownerStamp':sha(owner.encode()),'incoming':str(incoming),'activeRoot':str(active),'rpcPath':'/__release_'+job,'indexSHA256':sha(index.read_bytes()),'librarySHA256':digest,'sentinel':str(sentinel),'sentinelSHA256':sha(sentinel_code),'allowedSapis':['cli-server'],'files':{}}
payload=json.dumps(cfg,separators=(',',':'));envelope=private/'web.json';envelope.write_text(json.dumps({'payload':payload,'hmac':hmac.new(key.encode(),payload.encode(),hashlib.sha256).hexdigest()}));envelope.chmod(0o600)
bridge='<?php require '+json.dumps(str(library))+'; WebCacheEndpoint::barrier('+json.dumps(str(envelope))+','+json.dumps(sha(owner.encode()))+','+json.dumps(sha(token.encode()))+','+json.dumps(sha(token.encode()))+','+json.dumps(key)+');';(active/'storage/framework/maintenance.php').write_text(bridge)
def freeport():
 s=socket.socket();s.bind(('127.0.0.1',0));p=s.getsockname()[1];s.close();return p
port=freeport();tlsport=freeport();log=(root/'php.log').open('wb');server=subprocess.Popen(['php','-d','opcache.enable_cli=1','-d','opcache.file_update_protection=0','-S','127.0.0.1:'+str(port),'-t',str(active/'public')],stdout=log,stderr=log)
cert=root/'cert.pem';certkey=root/'key.pem';subprocess.run(['openssl','req','-x509','-newkey','rsa:2048','-nodes','-keyout',str(certkey),'-out',str(cert),'-subj','/CN=127.0.0.1','-addext','subjectAltName=IP:127.0.0.1','-days','1'],check=True,capture_output=True)
observed=[]
class Proxy(http.server.BaseHTTPRequestHandler):
 def log_message(self,*args):pass
 def do_POST(self):
  body=self.rfile.read(int(self.headers.get('Content-Length','0')));c=http.client.HTTPConnection('127.0.0.1',port,timeout=25);c.request('POST',self.path,body,dict(self.headers));r=c.getresponse();data=r.read();self.send_response(r.status);self.send_header('Content-Length',str(len(data)));self.end_headers();self.wfile.write(data);c.close();observed.append({'action':json.loads(body).get('action'),'status':r.status})
proxy=http.server.ThreadingHTTPServer(('127.0.0.1',tlsport),Proxy);context=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER);context.load_cert_chain(cert,certkey);proxy.socket=context.wrap_socket(proxy.socket,server_side=True);thread=threading.Thread(target=proxy.serve_forever,daemon=True);thread.start()
try:
 for n in range(40):
  try:c=socket.create_connection(('127.0.0.1',port),.1);c.close();break
  except OSError:time.sleep(.1)
 command=root/'command.json';command.write_text(json.dumps({'origin':'https://127.0.0.1:'+str(tlsport),'private':str(private),'config':cfg,'key':key,'token':token}))
 run=subprocess.run(['php','-d','curl.cainfo='+str(cert),str(tools/'red-client.php'),str(old/'HostWebControl.php'),str(command)],capture_output=True,text=True,timeout=25);receipt=json.loads(run.stdout);markers=list(private.glob('visibility-*.json'));assert run.returncode==2,(run.returncode,receipt,run.stderr);assert receipt['code']=='WEB_VISIBILITY_TIMEOUT';assert len(markers)==1;marker=json.loads(markers[0].read_text());assert marker['stage']=='done';comm=pathlib.Path('/proc/'+str(marker['pid'])+'/comm').read_text().strip();assert comm!='lsphp';assert any(r=={'action':'visibility','status':200} for r in observed)
 print(json.dumps({'frozenActualCalibrationMethod':True,'actualHTTPSRealPHP':True,'visibilityHTTPStatus':200,'retainedStage':'done','commIsLsphp':False,'actualOldCode':receipt['code'],'elapsedSeconds':receipt['elapsedSeconds'],'host1096CauseNotProven':True},indent=2))
finally:proxy.shutdown();proxy.server_close();server.terminate();server.wait(timeout=5);log.close()
