import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {spawnSync} from 'node:child_process';
import path from 'node:path';
import test from 'node:test';
import {fileURLToPath} from 'node:url';

// M5 lite source contracts: Kobalt native colours and the play3 runtime, camera-free.
// Source-only. Nothing here builds, submits or publishes.
const mobile=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
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

test('camera, scanner and image-picker stay out of this build (parked: parked/m5-camera-barcode)',()=>{
 for(const n of ['expo-camera','expo-image-picker']){
  assert.ok(!(n in pkg.dependencies),n+' must not be a dependency');
  assert.ok(!plugin(n),n+' must not be a plugin');
 }
 const lock=JSON.parse(read('package-lock.json'));
 assert.ok(!lock.packages['node_modules/expo-camera']&&!lock.packages['node_modules/expo-image-picker']);
 assert.ok(!/CAMERA|RECORD_AUDIO|with-optional-camera-feature/.test(appSource),'no camera permission or plugin in app.config.js');
 assert.ok(!(config.android.permissions||[]).some(p=>/CAMERA|RECORD_AUDIO/.test(p)));
});

test('play3 runtime is a fresh native identity, bound to the play-rc profile only',()=>{
 assert.equal(config.runtimeVersion,RUNTIME);
 assert.match(appSource,/const M17_PLAY_RUNTIME = '1\.0\.0-m17-sdk58-play3'/);
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
 assert.equal(plugin('expo-splash-screen')[1].backgroundColor.toLowerCase(),page);
 assert.equal(plugin('expo-notifications')[1].color.toLowerCase(),primary);
 assert.ok(!/#101214|#C45116/i.test(appSource));
});

test('no build, submit or OTA gate was loosened by M5 lite',()=>{
 const hold=read('scripts/release27/hold-unaudited-native-build.mjs');
 assert.match(hold,/NATIVE_RELEASE_GATE_REQUIRED/);
 assert.ok(!eas.build[PROFILE].autoSubmit);
 assert.deepEqual(eas.submit.production.android,{track:'production',releaseStatus:'completed'});
 assert.equal(pkg.scripts['build:android:preview'],'node scripts/release27/hold-unaudited-native-build.mjs --platform android --profile preview');
});
