import hashlib,json,os,pathlib,secrets,shutil,subprocess,sys
stage=pathlib.Path(sys.argv[1]);tools=pathlib.Path(sys.argv[2]);work=pathlib.Path(os.environ['RUNNER_TEMP'])/'b1134-scheduler';work.mkdir();base=work/'current';shutil.copytree(stage,base)
for rel in ['bootstrap/cache','storage/app/public','storage/logs','storage/framework/cache/data','storage/framework/sessions','storage/framework/views']:(base/rel).mkdir(parents=True,exist_ok=True)
env='\n'.join(['APP_NAME=B1134','APP_ENV=testing','APP_DEBUG=false','APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=','APP_URL=http://localhost','APP_MAINTENANCE_DRIVER=file','DB_CONNECTION=sqlite','DB_DATABASE=:memory:','CACHE_STORE=file','CACHE_PREFIX=b1134_scheduler','SESSION_DRIVER=file','QUEUE_CONNECTION=sync','MAIL_MAILER=array','LOG_CHANNEL=single','PUBLIC_STOREFRONT_ENABLED=false','PUBLIC_CHECKOUT_ENABLED=false',''])
(base/'.env').write_text(env);digest=hashlib.sha256(env.encode()).hexdigest();stat=base.stat();identity={'dev':stat.st_dev,'ino':stat.st_ino};command=work/'command.json';results=[]
def invoke(action,owner,previous=None,want=0,**extra):
    command.write_text(json.dumps({'base':str(base),'action':action,'owner':owner,'identity':identity,'envSHA256':digest,'previous':previous,**extra}));r=subprocess.run(['php','-d','opcache.enable_cli=0',str(tools/'scheduler-fixture-call.php'),str(tools/'SchedulerControl.php'),str(command)],capture_output=True,text=True)
    assert r.returncode==want,(action,r.returncode,r.stdout[-1500:],r.stderr[-1500:]);result=json.loads(r.stdout);results.append({'action':action,'rc':r.returncode});return result.get('receipt',result)
first=secrets.token_hex(32);receipt=invoke('pause',first);assert receipt['priorPaused'] is False and receipt['pausedByInstaller'] is True
replayed=invoke('pause',first);assert replayed==receipt
invoke('verify',secrets.token_hex(32),receipt,want=2);invoke('verify',first,receipt)
# Original release relocation must keep the SAME file-cache inode/prefix identity.
retained=work/'retained';base.rename(retained);base=retained;restored=invoke('release',first,receipt);assert restored['restoredPriorPaused'] is False
# Parent can die after the technical cache restore, before persisting its state.
restored=invoke('release',first,receipt);assert restored['restoredPriorPaused'] is False
invoke('set-paused',first,value=True);second=secrets.token_hex(32);already=invoke('pause',second);assert already['priorPaused'] is True and already['pausedByInstaller'] is False;restored=invoke('release',second,already);assert restored['restoredPriorPaused'] is True
invoke('set-paused',first,value=False);third=secrets.token_hex(32);owned=invoke('pause',third);restored=invoke('release',third,None);assert restored['restoredPriorPaused'] is False
receipt={'firstPartyLaravelCacheExecuted':True,'persistentDriver':'file','priorPausedPreserved':True,'foreignOwnerRejected':True,'pauseReplayIdempotent':True,'retainedReleaseBackendIdentityStable':True,'lostParentReceiptRecoveredFromOwnedCacheMarker':True,'results':results,'productionExecution':False};(work/'scheduler-receipt.json').write_text(json.dumps(receipt,indent=2));print(json.dumps(receipt,indent=2))
