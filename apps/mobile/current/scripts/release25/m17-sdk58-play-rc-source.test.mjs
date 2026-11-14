import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {spawnSync} from 'node:child_process';
import path from 'node:path';
import test from 'node:test';
import {fileURLToPath} from 'node:url';

// Source-only release-candidate identity tests. NO EAS build, OTA or Play submit.
const mobile=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const read=f=>readFileSync(path.join(mobile,f),'utf8');
const eas=JSON.parse(read('eas.json'));
const pkg=JSON.parse(read('package.json'));
const PROFILE='m17-sdk58-play-rc';
const RUNTIME='1.0.0-m17-sdk58-play2';
const profile=eas.build[PROFILE];
const removeKeys=[
 'EXPO_PUBLIC_APP_ENV','EAS_BUILD_PROFILE',
 'ALD1N_M17_SDK58_UAT','ALD1N_M17_SDK58_PROFILE',
 'ALD1N_M17_SDK58_PLAY_RC','ALD1N_M17_SDK58_PLAY_PROFILE',
 'ALD1N_V15_RELEASE_GATE','ALD1N_V15_RELEASE_PROFILE',
];
const command=[
 'const make=require(process.argv[1]);',
 'const c=make({config:{}});',
 'console.log(JSON.stringify({version:c.version,runtime:c.runtimeVersion,',
 'package:c.android.package,ios:c.ios.bundleIdentifier,owner:c.owner,',
 'project:c.extra.eas.projectId,env:c.extra.appEnv}));',
].join('');
function resolveConfig(envPatch={}) {
 const env={...process.env};
 for(const k of removeKeys) delete env[k];
 Object.assign(env,envPatch);
 const r=spawnSync(process.execPath,['-e',command,path.join(mobile,'app.config.js')],{
  cwd:mobile,env,encoding:'utf8',timeout:12000,
 });
 return r.status===0?{ok:true,config:JSON.parse(r.stdout)}:{ok:false,stderr:r.stderr};
}
function accepted(env, runtime, androidPackage) {
 const r=resolveConfig(env);
 assert.equal(r.ok,true,r.stderr);
 assert.equal(r.config.runtime,runtime);
 assert.equal(r.config.package,androidPackage);
 return r.config;
}

test('Expo58 Play candidate is a source-only AAB identity and never auto-submits',()=>{
 assert.equal(pkg.dependencies.expo,'~58.0.22');
 assert.equal(pkg.dependencies['react-native'],'0.88.1');
 assert.equal(pkg.dependencies['react-native-nitro-google-signin'],'2.3.1');
 assert.equal(eas.cli.version,'27.2.0');
 assert.equal(eas.cli.appVersionSource,'remote');
 assert.equal(profile.extends,'production');
 assert.equal(profile.environment,'production');
 assert.equal(profile.distribution,'store');
 assert.equal(profile.channel,'production');
 assert.equal(profile.autoIncrement,true);
 assert.equal(profile.android.buildType,'app-bundle');
 assert.equal(profile.env.EXPO_PUBLIC_APP_ENV,'production');
 assert.equal(profile.env.EXPO_PUBLIC_API_URL,'https://cms.ald1n.com/api/v1');
 assert.equal(profile.env.ALD1N_M17_SDK58_PLAY_RC,'source-only');
 assert.equal(profile.env.ALD1N_M17_SDK58_PLAY_PROFILE,PROFILE);
 assert.equal(profile.autoSubmit,undefined);
 assert.ok(!JSON.stringify(profile).includes('releaseStatus'));
 assert.ok(!JSON.stringify(profile).includes('ALD1N_V15_RELEASE_GATE'));
});

test('local and remote profile use new SDK58 runtime and exact production package',()=>{
 const local=accepted(profile.env,RUNTIME,'com.ald1n.mobile');
 const remote=accepted({...profile.env,EAS_BUILD_PROFILE:PROFILE},RUNTIME,'com.ald1n.mobile');
 assert.deepEqual(local,remote);
 assert.equal(local.version,'1.0.0');
 assert.equal(local.ios,'com.ald1n.mobile');
 assert.equal(local.env,'production');
 assert.equal(local.owner,'ald1n');
 assert.equal(local.project,'d43b3866-6838-4217-a23e-3dc7f2cc76cc');
 for(const forbidden of ['1.0.0-build17','1.5.0-native2','1.0.0-m17-sdk58-uat2'])
   assert.notEqual(RUNTIME,forbidden);
});

test('bare production Build25, v1.5 and preview SDK58 UAT retain their original identities',()=>{
 accepted({...eas.build.production.env,EAS_BUILD_PROFILE:'production'},'1.0.0-build17','com.ald1n.mobile');
 accepted({...eas.build['production-v15-16kb'].env,EAS_BUILD_PROFILE:'production-v15-16kb'},'1.5.0-native2','com.ald1n.mobile');
 const preview=eas.build['m17-sdk58-uat'];
 accepted({...preview.env,EAS_BUILD_PROFILE:'m17-sdk58-uat'},'1.0.0-m17-sdk58-uat2','com.ald1n.mobile.preview');
});

test('missing, cross-profile, wrong environment and v1.5 overlap reject Play marker',()=>{
 const bad=[
  {...profile.env,EAS_BUILD_PROFILE:'production'},
  {...profile.env,EAS_BUILD_PROFILE:'m17-sdk58-uat'},
  {...profile.env,EXPO_PUBLIC_APP_ENV:'preview'},
  {...profile.env,ALD1N_M17_SDK58_PLAY_RC:'approved'},
  {...profile.env,ALD1N_M17_SDK58_PLAY_PROFILE:'production'},
  {EXPO_PUBLIC_APP_ENV:'production',ALD1N_M17_SDK58_PLAY_RC:'source-only'},
  {EXPO_PUBLIC_APP_ENV:'production',ALD1N_M17_SDK58_PLAY_PROFILE:PROFILE},
  {...profile.env,ALD1N_V15_RELEASE_GATE:'approved'},
  {...profile.env,ALD1N_M17_SDK58_UAT:'isolated'},
  {...profile.env,ALD1N_M17_SDK58_PROFILE:'m17-sdk58-uat'},
 ];
 for(const env of bad){
  const r=resolveConfig(env);
  assert.equal(r.ok,false,JSON.stringify(env));
  assert.match(r.stderr,/HOLD_M17_SDK58_(PLAY_PROFILE_OR_SOURCE|UAT_PROFILE_OR_SOURCE)/);
 }
});

test('preview APK cannot be mistaken for Google Play binary or promoted into production',()=>{
 const uat=eas.build['m17-sdk58-uat'];
 assert.equal(uat.android.buildType,'apk');
 assert.equal(uat.channel,'m17-sdk58-uat');
 assert.equal(uat.autoIncrement,false);
 assert.equal(profile.android.buildType,'app-bundle');
 assert.equal(profile.channel,'production');
 assert.notEqual(profile.channel,uat.channel);
 assert.notEqual(profile.env.EXPO_PUBLIC_APP_ENV,uat.env.EXPO_PUBLIC_APP_ENV);
});

test('release controller and submit paths remain unchanged, and real Play gates are still required',()=>{
 assert.equal(pkg.scripts['build:android:production'],'node scripts/build25-release-controller.mjs');
 assert.equal(pkg.scripts['submit:android:production'],'node scripts/submit-android-production.mjs');
 assert.deepEqual(eas.submit.production.android,{track:'production',releaseStatus:'completed'});
 const gate=read('scripts/release25/signed-16kb-aab-release-gate.mjs');
 assert.match(gate,/EXPECTED_VERSION = '1\.5\.0'/);
 assert.match(gate,/EXPECTED_RUNTIME = '1\.5\.0-native2'/);
 // Old v1.5 AAB gate cannot certify this new SDK58 runtime; a new gate is required before build or submit.
 assert.notEqual(RUNTIME,'1.5.0-native2');
});
