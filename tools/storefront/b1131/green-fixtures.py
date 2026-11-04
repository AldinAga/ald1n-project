import hashlib,json,pathlib,shutil,subprocess,sys
root=pathlib.Path(sys.argv[1]); helper=pathlib.Path(sys.argv[2]); payload=root/'payload'; stage=root/'stage'; results=[]
shutil.copyfile(helper.parent/'runtime-directories.json',payload/'runtime-directories.json')
original_names=(payload/'names.txt').read_text(); original_verbose=(payload/'verbose.txt').read_text()
def call(name,mode,want=0,reason=None):
    r=subprocess.run(['php',str(helper),mode,str(payload),str(stage)],capture_output=True,text=True)
    (root/(name+'.log')).write_text(r.stdout+r.stderr)
    assert r.returncode==want,(name,r.returncode,r.stdout,r.stderr)
    if reason: assert reason in r.stdout+r.stderr,(name,r.stdout,r.stderr)
    results.append({'name':name,'rc':r.returncode,'expected':want,'reason':reason})
call('green-real-tar-pre','pre')
subprocess.run(['tar','-xzf',str(payload/'cms-runtime.tar.gz'),'--directory',str(stage),'--no-same-owner','--no-same-permissions','--keep-old-files'],check=True)
call('green-real-runtime-post','post')
def reset_lists():
    (payload/'names.txt').write_text(original_names);(payload/'verbose.txt').write_text(original_verbose)
for name,addition,reason in [('extra-directory','vendor/unapproved-empty/\n','unlisted tar directory'),('traversal','../escape/\n','tar traversal'),('duplicate',original_names.splitlines()[0]+'\n','duplicate tar member')]:
    reset_lists();(payload/'names.txt').write_text(original_names+addition);(payload/'verbose.txt').write_text(original_verbose+original_verbose.splitlines()[0]+'\n');call(name,'pre',2,reason)
reset_lists();(payload/'names.txt').write_text('\n'.join(original_names.splitlines()[1:])+'\n');(payload/'verbose.txt').write_text('\n'.join(original_verbose.splitlines()[1:])+'\n');call('missing-file-or-directory','pre',2,'missing tar')
for name,kind in [('symlink','l'),('hardlink','h')]:
    reset_lists();(payload/'verbose.txt').write_text(kind+original_verbose[1:]);call(name,'pre',2,'nonregular tar member')
reset_lists()
target=stage/'artisan';old=target.read_bytes();target.write_bytes(old+b'changed');call('file-hash-tamper','post',2,'staged hash mismatch');target.write_bytes(old)
link=stage/'unapproved-link';link.symlink_to('artisan');call('staged-link','post',2,'stage link');link.unlink()
directory=stage/'vendor/unapproved-empty';directory.mkdir();call('staged-extra-directory','post',2,'unexpected staged directory');directory.rmdir()
directories=(payload/'runtime-directories.json').read_bytes();bad=json.loads(directories);bad['directories'].append('vendor/unapproved-empty');(payload/'runtime-directories.json').write_text(json.dumps(bad));call('directory-manifest-tamper','pre',2,'directory census');(payload/'runtime-directories.json').write_bytes(directories)
call('green-restored-pre','pre');call('green-restored-post','post')
receipt={'actualPHPExecution':True,'applicationBootstrapped':False,'databaseAccess':False,'runtimeSourceUnchanged':True,'files':8531,'directories':1181,'helperSHA256':hashlib.sha256(helper.read_bytes()).hexdigest(),'directoryManifestSHA256':hashlib.sha256(directories).hexdigest(),'cases':results}
(root/'green-receipt.json').write_text(json.dumps(receipt,indent=2));print(json.dumps(receipt,indent=2))
