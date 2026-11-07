import http.client,http.server,json,os,pathlib,secrets,socket,ssl,subprocess,sys,threading,time
tools=pathlib.Path(sys.argv[1]);client=pathlib.Path(sys.argv[2]);mode=sys.argv[3];root=pathlib.Path(os.environ['RUNNER_TEMP'])/('b1142-native-'+mode);root.mkdir();results=[]
def port():
 s=socket.socket();s.bind(('127.0.0.1',0));p=s.getsockname()[1];s.close();return p
for width in ([44] if mode=='red' else [44,45]):
 case=root/str(width);case.mkdir();private=case/'private';private.mkdir(mode=0o700);active=case/'known-canonical-project'/'current';(active/'public').mkdir(parents=True);index=active/'public/index.php';index.write_text('<?php return;');backend=port();tls=port();stop=case/'stop';token=secrets.token_hex(32);cfg={'id':secrets.token_hex(16),'phase':'preflight','activeRoot':str(active),'librarySHA256':'a'*64,'indexSHA256':__import__('hashlib').sha256(index.read_bytes()).hexdigest(),'rpcPath':'/owned-fixture','private':str(private),'port':backend,'stop':str(stop),'width':width,'token':token};config=case/'worker.json';config.write_text(json.dumps(cfg));parent=subprocess.Popen(['php',str(tools/'native-parent.php'),str(config),str(tools/'native-worker.php'),'x'*256],stdout=subprocess.PIPE,stderr=subprocess.PIPE,text=True)
 class Proxy(http.server.BaseHTTPRequestHandler):
  def log_message(self,*args):pass
  def do_POST(self):
   body=self.rfile.read(int(self.headers.get('Content-Length','0')));c=http.client.HTTPConnection('127.0.0.1',backend,timeout=20);c.request('POST',self.path,body,dict(self.headers));r=c.getresponse();data=r.read();self.send_response(r.status);self.send_header('Content-Length',str(len(data)));self.end_headers();self.wfile.write(data);c.close()
 cert=case/'cert.pem';key=case/'key.pem';subprocess.run(['openssl','req','-x509','-newkey','rsa:2048','-nodes','-keyout',str(key),'-out',str(cert),'-subj','/CN=127.0.0.1','-addext','subjectAltName=IP:127.0.0.1','-days','1'],check=True,capture_output=True);proxy=http.server.ThreadingHTTPServer(('127.0.0.1',tls),Proxy);context=ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER);context.load_cert_chain(cert,key);proxy.socket=context.wrap_socket(proxy.socket,server_side=True);threading.Thread(target=proxy.serve_forever,daemon=True).start()
 try:
  assert parent.stdout.readline().strip()=='READY';origin='https://127.0.0.1:'+str(tls);assert __import__('urllib.parse').parse.urlparse(origin).hostname=='127.0.0.1';env={k:v for k,v in os.environ.items() if k.lower() not in ['http_proxy','https_proxy','all_proxy']};env['NO_PROXY']='127.0.0.1,localhost';command=case/'client.json';command.write_text(json.dumps({'origin':origin,'private':str(private),'key':secrets.token_hex(32),'token':token,'config':cfg}));run=subprocess.run(['php','-d','curl.cainfo='+str(cert),str(tools/'native-client.php'),str(client),str(command)],capture_output=True,text=True,timeout=22,env=env);receipt=json.loads(run.stdout.strip().splitlines()[-1]);assert receipt['fixtureSAPIIsSimulated']
  if mode=='red':assert run.returncode==2 and receipt['code']=='WEB_VISIBILITY_TIMEOUT',(run.returncode,receipt,run.stderr)
  else:assert run.returncode==0 and receipt['calibrated'] and receipt['width']==width,(run.returncode,receipt,run.stderr)
  results.append({'width':width,'expectedOldFailure':mode=='red','receipt':receipt})
 finally:
  stop.write_text('stop');proxy.shutdown();proxy.server_close()
  try:parent.communicate(timeout=4)
  except subprocess.TimeoutExpired:parent.kill();parent.communicate(timeout=2)
print(json.dumps(results,indent=2))
