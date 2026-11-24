import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import {fileURLToPath} from 'node:url';
import {runInNewContext} from 'node:vm';
import ts from 'typescript';

// SubAgent saves product photos straight from the APK (play3), without any new permission.
// The save flow runs for real against fakes; the rest are source contracts. Nothing builds the app.
const mobile=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const read=f=>readFileSync(path.join(mobile,f),'utf8');
const download=read('src/features/catalog/product-image-download.ts');
const flowSource=read('src/features/catalog/product-image-save-flow.ts');
const gallery=read('src/components/catalog/product-image-gallery.tsx');
const storage=read('src/lib/storage.ts');
const pkg=JSON.parse(read('package.json'));
const lock=JSON.parse(read('package-lock.json'));
const appSource=read('app.config.js');

const flow=(()=>{
 assert.ok(!/^import /m.test(flowSource),'flow module stays free of RN/Expo imports');
 const js=ts.transpileModule(flowSource,{compilerOptions:{module:ts.ModuleKind.CommonJS,target:ts.ScriptTarget.ES2022}}).outputText;
 const exports={};
 // Same Error realm as the fakes, so `instanceof Error` behaves as in the app.
 runInNewContext(js,{exports,Error});
 return exports;
})();

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
