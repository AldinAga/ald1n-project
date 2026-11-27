import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import {fileURLToPath} from 'node:url';
import {runInNewContext} from 'node:vm';
import ts from 'typescript';

// SubAgent saves product photos straight from the APK (play3), without any new permission.
// The save flow and Expo wiring run against fakes. Nothing builds the app.
const mobile=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const read=f=>readFileSync(path.join(mobile,f),'utf8');
const download=read('src/features/catalog/product-image-download.ts');
const flowSource=read('src/features/catalog/product-image-save-flow.ts');
const gallery=read('src/components/catalog/product-image-gallery.tsx');
const storage=read('src/lib/storage.ts');
const pkg=JSON.parse(read('package.json'));
const lock=JSON.parse(read('package-lock.json'));
const appSource=read('app.config.js');

function loadModule(source,globals={}){
 const js=ts.transpileModule(source,{compilerOptions:{module:ts.ModuleKind.CommonJS,target:ts.ScriptTarget.ES2022}}).outputText;
 const exports={};
 // Same Error realm as the fakes, so `instanceof Error` behaves as in the app.
 runInNewContext(js,{exports,Error,...globals});
 return exports;
}

assert.ok(!/^import /m.test(flowSource),'flow module stays free of RN/Expo imports');
const flow=loadModule(flowSource);

function downloadWorld({status=200,headers={},body=[1,2,3],truncateWrite=false}={}){
 const cacheRoot='file:///cache';
 const cache=new Map([[cacheRoot,new Map()]]);
 const created=[];
 const saved=[];
 const warnings=[];
 let id=0;
 class Directory {
  constructor(root,name){
   this.uri=name===undefined?root:`${root.uri}/${name}`;
  }
  get exists(){return this.uri==='tree:Pictures';}
  create(){
   assert.ok(!cache.has(this.uri));
   cache.set(this.uri,new Map());
   created.push(this.uri);
  }
  delete(){cache.delete(this.uri);}
  createFile(name,mime){
   return {write(bytes){saved.push({name,mime,bytes:[...bytes]});}};
  }
 }
 class File {
  constructor(directory,name){
   this.files=cache.get(directory.uri);
   this.name=name;
  }
  get exists(){return this.files.has(this.name);}
  get size(){return 0;} // Metadata may still describe the empty file.
  create(){this.files.set(this.name,new Uint8Array());}
  write(bytes){this.files.set(this.name,truncateWrite?bytes.slice(0,-1):bytes);}
  async bytes(){return this.files.get(this.name);}
 }
 const modules={
  'expo-crypto':{randomUUID:()=>String(++id)},
  'expo-file-system':{Directory,File,Paths:{cache:new Directory(cacheRoot)}},
  'react-native':{Platform:{OS:'android'}},
  '@/lib/storage':{imageSaveFolderStore:{get:async()=> 'tree:Pictures'}},
  './product-image-save-flow':flow,
 };
 const wiring=loadModule(download,{
  require:name=>modules[name],
  URL,
  console:{warn:(...args)=>warnings.push(args)},
  fetch:async (_url,{method})=>{
   if(method==='HEAD') return {};
   return {
    status,
    ok:status>=200&&status<300,
    headers:{get:name=>headers[name]??null},
    arrayBuffer:async()=>Uint8Array.from(body).buffer,
   };
  },
 });
 const input={originalUrl:'https://example.test/original.jpg',productSku:'ABC#10%20',productName:'Artikal',imageNumber:1};
 return {save:()=>wiring.saveProductImagesToDevice([input]),cacheRoot,cache,created,saved,warnings};
}

test('Expo wiring verifies actual bytes despite stale size and isolates parallel saves',async()=>{
 const w=downloadWorld({headers:{'content-type':'image/jpeg','content-length':'3'}});
 const results=await Promise.all([w.save(),w.save()]);
 assert.ok(results.every(result=>result.saved===1&&result.firstError===null));
 assert.equal(new Set(w.created).size,2);
 assert.deepEqual([...w.cache.keys()],[w.cacheRoot]);
 assert.deepEqual(w.saved,Array.from({length:2},()=>({
  name:'ABC-10-20-original-slika-1.jpg',mime:'image/jpeg',bytes:[1,2,3],
 })));
 assert.deepEqual(w.warnings,[]);
});

test('Expo wiring rejects a truncated cache write, cleans it up and logs only byte counts and stage',async()=>{
 const w=downloadWorld({truncateWrite:true});
 const result=await w.save();
 assert.equal(result.saved,0);
 assert.match(result.firstError,/^Upis originalne fotografije/);
 assert.equal(w.saved.length,0);
 assert.deepEqual([...w.cache.keys()],[w.cacheRoot]);
 assert.deepEqual(w.warnings,[['[product-image-cache]','readback',3,2]]);
});

test('Expo wiring rejects HTTP errors, partial, non-image and empty responses before writing',async()=>{
 for(const [options,message] of [
  [{status:404},/HTTP 404/],
  [{status:206},/HTTP 206/],
  [{headers:{'content-type':'text/html'}},/Server nije vratio originalnu fotografiju/],
  [{headers:{'content-type':'application/json'}},/Server nije vratio originalnu fotografiju/],
  [{body:[]},/prazna/],
  [{headers:{'content-length':'4'}},/nije završeno u celosti/],
 ]){
  const w=downloadWorld(options);
  const result=await w.save();
  assert.equal(result.saved,0);
  assert.match(result.firstError,message);
  assert.deepEqual(w.created,[]);
  assert.deepEqual(w.warnings,[]);
 }
});

// A fake world: folders by URI, a store, a picker queue and a log of every side effect.
function world({stored=null,folders={},picks=[],online=true,storeThrows=false,fetchFails={}}={}){
 const log=[];
 const state={stored,files:[]};
 const folder=(uri,{createFails=false,writeFails=false}={})=>({uri,createFile(name,mime){
  if(createFails) throw new Error('permission denied');
  const f={uri,name,mime,bytes:null,deleted:false,write(b){if(writeFails) throw new Error('ENOSPC');f.bytes=b;},delete(){f.deleted=true;log.push('delete:'+name);}};
  state.files.push(f);return f;
 }});
 const deps={
  readStoredFolder:async()=>{if(storeThrows) throw new Error('keystore');return state.stored;},
  rememberFolder:async uri=>{state.stored=uri;log.push('remember:'+uri);},
  forgetFolder:async()=>{state.stored=null;log.push('forget');},
  openFolder:uri=>uri in folders?folder(uri,folders[uri]):null,
  pickFolder:async()=>{log.push('pick');const next=picks.shift();if(next===undefined||next===null) return null;return folder(next.uri,next);},
  isOnline:async()=>online,
  fetchOriginal:async input=>{
   if(fetchFails[input]) throw new Error(fetchFails[input]);
   return {bytes:new Uint8Array([1,2,3]),mimeType:'image/jpeg',fileName:input+'.jpg',discard(){log.push('discard:'+input);}};
  },
 };
 return {deps,log,state};
}
const run=(inputs,w)=>flow.runProductImageSave(inputs,w.deps);

test('first save: picks a folder, remembers it and writes every image',async()=>{
 const w=world({picks:[{uri:'tree:A'}]});
 const r=await run(['a','b'],w);
 assert.equal(r.saved,2);assert.equal(r.cancelled,false);assert.equal(r.firstError,null);
 assert.equal(w.state.stored,'tree:A');
 assert.deepEqual(w.state.files.map(f=>f.name),['a.jpg','b.jpg']);
 assert.ok(w.state.files.every(f=>f.bytes&&f.uri==='tree:A'));
 assert.deepEqual(w.log.filter(l=>l.startsWith('discard')),['discard:a','discard:b']);
});

test('remembered folder is reused without opening the picker',async()=>{
 const w=world({stored:'tree:A',folders:{'tree:A':{}}});
 const r=await run(['a'],w);
 assert.equal(r.saved,1);
 assert.ok(!w.log.includes('pick'));
 assert.equal(w.state.files[0].uri,'tree:A');
});

test('closing the picker is a silent cancel with nothing written',async()=>{
 const w=world({picks:[null]});
 const r=await run(['a','b'],w);
 assert.deepEqual({saved:r.saved,cancelled:r.cancelled,firstError:r.firstError},{saved:0,cancelled:true,firstError:null});
 assert.equal(w.state.files.length,0);
 assert.equal(w.state.stored,null);
});

test('offline is detected before the picker opens',async()=>{
 const w=world({online:false,picks:[{uri:'tree:A'}]});
 const r=await run(['a'],w);
 assert.equal(r.firstError,flow.OFFLINE_MESSAGE);
 assert.ok(!w.log.includes('pick'));
});

test('losing the network mid-way stops the loop',async()=>{
 const w=world({stored:'tree:A',folders:{'tree:A':{}},fetchFails:{b:flow.OFFLINE_MESSAGE}});
 const r=await run(['a','b','c'],w);
 assert.equal(r.saved,1);assert.equal(r.firstError,flow.OFFLINE_MESSAGE);
 assert.deepEqual(w.state.files.map(f=>f.name),['a.jpg']);
});

test('a failed download of one image does not stop the others',async()=>{
 const w=world({stored:'tree:A',folders:{'tree:A':{}},fetchFails:{b:'Server nije vratio originalnu fotografiju.'}});
 const r=await run(['a','b','c'],w);
 assert.equal(r.saved,2);assert.equal(r.firstError,'Server nije vratio originalnu fotografiju.');
});

test('a deleted remembered folder is forgotten and re-picked before saving',async()=>{
 const w=world({stored:'tree:gone',picks:[{uri:'tree:B'}]});
 const r=await run(['a'],w);
 assert.equal(r.saved,1);
 assert.deepEqual(w.log.slice(0,3),['forget','pick','remember:tree:B']);
 assert.equal(w.state.files[0].uri,'tree:B');
});

test('a revoked grant (createFile fails) on the remembered folder re-picks exactly once',async()=>{
 const w=world({stored:'tree:A',folders:{'tree:A':{createFails:true}},picks:[{uri:'tree:B'}]});
 const r=await run(['a','b'],w);
 assert.equal(r.saved,2);
 assert.equal(w.log.filter(l=>l==='pick').length,1);
 assert.equal(w.state.stored,'tree:B');
 assert.ok(w.state.files.every(f=>f.uri==='tree:B'));
});

test('a freshly picked folder that refuses files stops instead of re-picking forever',async()=>{
 const w=world({picks:[{uri:'tree:A',createFails:true},{uri:'tree:B'}]});
 const r=await run(['a','b'],w);
 assert.equal(r.saved,0);assert.equal(r.firstError,flow.FOLDER_FAILED_MESSAGE);
 assert.equal(w.log.filter(l=>l==='pick').length,1);
});

test('cancelling the re-pick stops the loop',async()=>{
 const w=world({stored:'tree:A',folders:{'tree:A':{createFails:true}},picks:[null]});
 const r=await run(['a','b','c'],w);
 assert.equal(r.saved,0);assert.equal(r.cancelled,true);assert.equal(r.firstError,null);
 assert.deepEqual(w.log.filter(l=>l.startsWith('discard')),['discard:a']);
});

test('a write failure (disk full) deletes the partial file and keeps the remembered folder',async()=>{
 const w=world({stored:'tree:A',folders:{'tree:A':{writeFails:true}}});
 const r=await run(['a'],w);
 assert.equal(r.saved,0);assert.equal(r.firstError,flow.WRITE_FAILED_MESSAGE);
 assert.ok(w.log.includes('delete:a.jpg'));
 assert.ok(!w.log.includes('forget')&&!w.log.includes('pick'));
 assert.equal(w.state.stored,'tree:A');
});

test('an unreadable store falls back to the picker',async()=>{
 const w=world({storeThrows:true,picks:[{uri:'tree:A'}]});
 const r=await run(['a'],w);
 assert.equal(r.saved,1);assert.ok(w.log.includes('pick'));
});

test('a failing cache cleanup does not stop the remaining images',async()=>{
 const w=world({stored:'tree:A',folders:{'tree:A':{}}});
 const fetch=w.deps.fetchOriginal;
 w.deps.fetchOriginal=async i=>({...(await fetch(i)),discard(){throw new Error('busy');}});
 const r=await run(['a','b'],w);
 assert.equal(r.saved,2);
});

test('wiring: SAF picker of expo-file-system, store keys, HEAD probe; no media library',()=>{
 assert.match(download,/import \{ Directory, File, Paths \} from 'expo-file-system'/);
 assert.match(download,/Directory\.pickDirectoryAsync\(PICTURES_TREE_URI\)/);
 assert.match(download,/\/cancel\/i\.test/);
 assert.ok(download.includes("fetch(input.originalUrl, { method: 'HEAD' })"));
 assert.ok(download.includes('readStoredFolder: () => imageSaveFolderStore.get()'));
 assert.ok(download.includes('rememberFolder: (uri) => imageSaveFolderStore.set(uri)'));
 assert.ok(download.includes('forgetFolder: () => imageSaveFolderStore.clear()'));
 assert.match(storage,/IMAGE_SAVE_FOLDER_KEY = 'ald1n\.catalog\.image-save-folder'/);
 assert.ok(!/expo-media-library|MediaLibrary|createAssetAsync|saveToLibraryAsync|PermissionsAndroid|requestPermissions/.test(download+flowSource+gallery));
});

test('no new native dependency or permission (Data safety unchanged)',()=>{
 for(const n of ['expo-media-library','expo-camera','expo-image-picker']){
  assert.ok(!(n in pkg.dependencies),n+' must not be a dependency');
  assert.ok(!lock.packages['node_modules/'+n],n+' must not be in the lock');
 }
 assert.ok(!/READ_MEDIA_|WRITE_EXTERNAL_STORAGE|MANAGE_EXTERNAL_STORAGE|ACCESS_MEDIA_LOCATION|CAMERA|RECORD_AUDIO/.test(appSource));
});

test('download stays on the canonical original URL and keeps the share path',()=>{
 assert.ok(download.includes("fetch(input.originalUrl, { method: 'GET' })"));
 assert.ok(!download.includes('fetch(input.displayUrl')&&!download.includes('fetch(input.thumbnailUrl'));
 assert.ok(download.includes('Sharing.shareAsync(file.uri'));
 assert.ok(download.includes('MAX_PRODUCT_IMAGE_BYTES'));
 assert.ok(gallery.includes('originalUrl: image.originalUrl'));
});

test('gallery: Sačuvaj, Podeli, Sačuvaj sve; labels; no word breaking on the 172 px tile',()=>{
 assert.ok(gallery.includes("'Sačuvaj sve'")&&gallery.includes("'Sačuvaj'")&&gallery.includes("'Podeli'"));
 assert.ok(gallery.includes('accessibilityLabel={`Sačuvaj originalnu sliku ${index + 1} na telefon`}'));
 assert.ok(gallery.includes('accessibilityLabel={`Podeli originalnu sliku ${index + 1}`}'));
 assert.ok(gallery.includes('accessibilityLabel={`Sačuvaj svih ${resolvedImages.length} originalnih slika na telefon`}'));
 assert.ok(gallery.includes('if (result.cancelled && result.saved === 0) return;'));
 assert.ok(gallery.includes('canSaveProductImagesToDevice ?')&&download.includes("Platform.OS === 'android'"));
 assert.ok(gallery.includes("mode === 'catalog' ? styles.actionColumn : null"));
 const buttonTexts=gallery.match(/<Text style=\{styles\.(downloadText|shareText)\}[^>]*>/g)??[];
 assert.ok(buttonTexts.length>=3&&buttonTexts.every(t=>t.includes('numberOfLines={1}')&&t.includes('adjustsFontSizeToFit')));
 assert.ok(!gallery.includes('Preuzmi original'));
});
