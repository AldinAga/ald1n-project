import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {spawnSync} from 'node:child_process';
import {createRequire} from 'node:module';
import path from 'node:path';
import test from 'node:test';
import {fileURLToPath} from 'node:url';

// M5 source contracts: camera/scanner dependencies, native Kobalt colours, play3 runtime.
// Source-only. Nothing here builds, submits or publishes.
const mobile=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const repo=path.resolve(mobile,'../../..');
const read=f=>readFileSync(path.join(mobile,f),'utf8');
const pkg=JSON.parse(read('package.json'));
const eas=JSON.parse(read('eas.json'));
const appSource=read('app.config.js');
const PROFILE='m17-sdk58-play-rc';
const RUNTIME='1.0.0-m17-sdk58-play3';
const removeKeys=[
 'EXPO_PUBLIC_APP_ENV','EAS_BUILD_PROFILE','ALD1N_M17_SDK58_UAT','ALD1N_M17_SDK58_PROFILE',
 'ALD1N_M17_SDK58_PLAY_RC','ALD1N_M17_SDK58_PLAY_PROFILE','ALD1N_V15_RELEASE_GATE','ALD1N_V15_RELEASE_PROFILE',
];
function resolveConfig(){
 const env={...process.env};
 for(const k of removeKeys) delete env[k];
 Object.assign(env,eas.build[PROFILE].env,{EAS_BUILD_PROFILE:PROFILE});
 const r=spawnSync(process.execPath,['-e','console.log(JSON.stringify(require(process.argv[1])({config:{}})))',path.join(mobile,'app.config.js')],{
  cwd:mobile,env,encoding:'utf8',timeout:12000,
 });
 assert.equal(r.status,0,r.stderr);
 return JSON.parse(r.stdout);
}
const config=resolveConfig();
const plugin=name=>config.plugins.find(p=>(Array.isArray(p)?p[0]:p)===name);

test('expo-camera and expo-image-picker are pinned to the SDK 58 line and locked',()=>{
 assert.equal(pkg.dependencies['expo-camera'],'~58.0.14');
 assert.equal(pkg.dependencies['expo-image-picker'],'~58.0.10');
 const lock=JSON.parse(read('package-lock.json'));
 assert.match(lock.packages['node_modules/expo-camera'].version,/^58\.0\./);
 assert.match(lock.packages['node_modules/expo-image-picker'].version,/^58\.0\./);
});

test('plugins keep the microphone out of the manifest',()=>{
 const camera=plugin('expo-camera');
 const picker=plugin('expo-image-picker');
 assert.ok(camera&&picker,'both plugins registered');
 assert.equal(camera[1].recordAudioAndroid,false);
 assert.equal(picker[1].microphonePermission,false);
 assert.ok(typeof camera[1].cameraPermission==='string'&&camera[1].cameraPermission.length>20);
 // Manifest content itself is only proven by `expo prebuild` (RECORD_AUDIO gets tools:node=remove); not testable from config.
 assert.ok(plugin('./plugins/with-optional-camera-feature'));
});

test('play3 runtime is a fresh native identity, bound to the play-rc profile only',()=>{
 assert.equal(config.runtimeVersion,RUNTIME);
 assert.match(appSource,/const M17_PLAY_RUNTIME = '1\.0\.0-m17-sdk58-play3'/);
 assert.ok(!appSource.includes('m17-sdk58-play2')||/live AAB \(versionCode 29\) is play2/.test(appSource));
 assert.ok(!/runtimeVersion:\s*['"]/.test(appSource),'no literal runtime outside the identity table');
});

test('versionCode stays under EAS remote authority (no hardcoded value)',()=>{
 assert.equal(eas.cli.appVersionSource,'remote');
 assert.equal(eas.build[PROFILE].autoIncrement,true);
 assert.ok(!/versionCode\s*[:=]/.test(appSource),'app.config.js must not set versionCode');
 assert.ok(!JSON.stringify(eas).includes('versionCode'));
});

function kobaltToken(group,path2){
 const ts=readFileSync(path.join(mobile,'src/design/ald1n-tokens.kobalt.generated.ts'),'utf8');
 const dark=ts.slice(ts.indexOf('export const kobaltDark'),ts.indexOf('export const kobaltLight'));
 let scope=dark;
 const leaf=path2[path2.length-1];
 for(const key of [group,...path2.slice(0,-1)]){
  const i=scope.search(new RegExp(`\\n\\s*${key}: \\{`));
  assert.ok(i>=0,`token ${group}.${path2.join('.')}`);
  scope=scope.slice(i+1);
 }
 const m=new RegExp(`\\n\\s*${leaf}: "(#[0-9a-fA-F]{6})"`).exec(scope);
 assert.ok(m,`leaf ${leaf}`);
 return m[1].toLowerCase();
}

test('native colours equal the Kobalt dark tokens',()=>{
 const page=kobaltToken('surface',['page']);
 const primary=kobaltToken('button',['primary','bg']);
 assert.equal(page,'#0d1216');
 assert.equal(primary,'#3557c7');
 assert.equal(config.android.adaptiveIcon.backgroundColor.toLowerCase(),page);
 const splash=plugin('expo-splash-screen')[1];
 assert.equal(splash.backgroundColor.toLowerCase(),page);
 assert.equal(plugin('expo-notifications')[1].color.toLowerCase(),primary);
 assert.ok(!/#101214|#C45116/i.test(appSource));
});

test('optional camera plugin writes required=false with tools:replace and is idempotent',()=>{
 const Module=createRequire(import.meta.url)('node:module');
 const original=Module._load;
 let manifest;
 Module._load=function(request,...rest){
  if(request==='expo/config-plugins'){
   return {withAndroidManifest:(cfg,fn)=>{manifest={$:{},'uses-feature':[{$:{'android:name':'android.hardware.camera','android:required':'true'}}]};fn({modResults:{manifest}});fn({modResults:{manifest}});return cfg;}};
  }
  return original.call(this,request,...rest);
 };
 try{
  const file=path.join(mobile,'plugins/with-optional-camera-feature.js');
  delete createRequire(import.meta.url).cache[file];
  createRequire(import.meta.url)(file)({});
 }finally{Module._load=original}
 assert.equal(manifest.$['xmlns:tools'],'http://schemas.android.com/tools');
 const features=manifest['uses-feature'];
 assert.equal(features.length,2,'no duplicates after two runs');
 for(const f of features){
  assert.equal(f.$['android:required'],'false');
  assert.equal(f.$['tools:replace'],'android:required');
 }
});

test('EAN scanner uses the Google scanner, supports only EAN/UPC-E and validates like the server',()=>{
 const src=read('src/features/catalog/ean-scanner.tsx');
 assert.match(src,/CameraView\.launchScanner\(\{ barcodeTypes: \['ean13', 'ean8', 'upc_e'\] \}\)/);
 assert.match(src,/CameraView\.isModernBarcodeScannerAvailable/);
 assert.match(src,/CameraView\.onModernBarcodeScanned/);
 assert.ok(!/<CameraView/.test(src),'no own camera view');
 // expo-camera rejects launchScanner when the user closes the scanner; that must stay silent.
 assert.match(src,/code === 'ERR_BARCODE_SCANNING_CANCELLED'\) return;/);
 assert.match(src,/Platform\.OS === 'ios'\) void CameraView\.dismissScanner/);
 const server=readFileSync(path.join(repo,'apps/cms/current/app/Http/Requests/ProductRequest.php'),'utf8');
 assert.ok(server.includes("'regex:/^[0-9]{8,14}$/'"),'server rule changed, update the client');
 const pattern=/const EAN_PATTERN = \/(.+)\/;/.exec(src)[1];
 const ean=new RegExp(pattern);
 for(const ok of ['12345678','5901234123457','12345678901234']) assert.ok(ean.test(ok),ok);
 for(const bad of ['1234567','123456789012345','12345678a','','12 345 678']) assert.ok(!ean.test(bad),bad);
 assert.match(src,/replace\(\/\[\\s-\]\+\/g, ''\)/,'whitespace and dashes are stripped like the server import');
 for(const file of ['src/app/(app)/admin/catalog/create.tsx','src/app/(app)/admin/catalog/[id].tsx']){
  const screen=read(file);
  assert.match(screen,/import \{ EanScanButton \} from '@\/features\/catalog\/ean-scanner'/,file);
  assert.match(screen,/<EanScanButton onScanned=\{setEan\}/,file);
 }
});

test('product photo: one system-camera shot per tap through the same draft flow',()=>{
 const picker=read('src/features/catalog/product-image-picker.ts');
 assert.match(picker,/ImagePicker\.launchCameraAsync\(/);
 assert.match(picker,/mediaTypes: \['images'\]/);
 assert.ok(!/allowsMultipleSelection/.test(picker));
 const serverLimit=readFileSync(path.join(repo,'apps/cms/current/app/Http/Controllers/Admin/ProductImageController.php'),'utf8');
 assert.ok(serverLimit.includes("'max:10240'"),'server image limit changed, re-check camera quality');
 assert.match(picker,/quality: 0\.8/);
 assert.match(picker,/requestCameraPermissionsAsync/);
 assert.match(picker,/validatePickedFiles\(\[new File\(asset\.uri\)\], current, limits, 1\)/);
 assert.match(picker,/return validatePickedFiles\(selected, current, limits, remaining\)/);
 const manager=read('src/features/catalog/product-image-manager.tsx');
 assert.match(manager,/Slikaj/);
 assert.match(manager,/captureProductImage\(images, limits\)/);
 assert.match(manager,/Mikrofon se ne koristi/);
 assert.match(manager,/appended\.push\(\{ \.\.\.file, rotation_degrees: 0 \}\)/);
});

test('Play data safety notes cover CAMERA, photos and the scanner',()=>{
 const doc=readFileSync(path.join(repo,'docs/google-play-data-safety.md'),'utf8');
 assert.match(doc,/`CAMERA`/);
 assert.match(doc,/RECORD_AUDIO/);
 assert.match(doc,/Fotografije/);
 assert.match(doc,/ne čuva sliku/);
 assert.match(doc,/required=false/);
});

test('no build, submit or OTA gate was loosened by M5',()=>{
 const hold=read('scripts/release27/hold-unaudited-native-build.mjs');
 assert.match(hold,/NATIVE_RELEASE_GATE_REQUIRED/);
 assert.ok(!eas.build[PROFILE].autoSubmit);
 assert.deepEqual(eas.submit.production.android,{track:'production',releaseStatus:'completed'});
 assert.equal(pkg.scripts['build:android:preview'],'node scripts/release27/hold-unaudited-native-build.mjs --platform android --profile preview');
});
