import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {spawnSync} from 'node:child_process';
import path from 'node:path';
import test from 'node:test';
import {fileURLToPath} from 'node:url';

// app.config.js has no default runtime: only an approved identity resolves, everything else throws.
const mobile=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const eas=JSON.parse(readFileSync(path.join(mobile,'eas.json'),'utf8'));
const OLD_DEFAULT='1.0.0-build17';
const HOLD=/HOLD_RUNTIME_IDENTITY_NOT_APPROVED/;
// null = must throw.
const EXPECTED={
 development:'1.0.0-dev-client',
 preview:null,
 'ios-simulator':null,
 'm17-sdk58-uat':'1.0.0-m17-sdk58-uat2',
 'm17-sdk58-play-rc':'1.0.0-m17-sdk58-play2',
 production:null,
 'production-v15-16kb':'1.5.0-native2',
};
const clearEnv=/^(EXPO_PUBLIC_|ALD1N_|EAS_)/;
const command='const c=require(process.argv[1])({config:{}});console.log(JSON.stringify({runtime:c.runtimeVersion,package:c.android.package}));';
function resolve(envPatch={}){
 const env={...process.env};
 for(const k of Object.keys(env)) if(clearEnv.test(k)) delete env[k];
 Object.assign(env,envPatch);
 const r=spawnSync(process.execPath,['-e',command,path.join(mobile,'app.config.js')],{cwd:mobile,env,encoding:'utf8',timeout:12000});
 return r.status===0?{ok:true,...JSON.parse(r.stdout)}:{ok:false,stderr:r.stderr};
}
const profileEnv=name=>({...(eas.build[name].extends?profileEnv(eas.build[name].extends):{}),...(eas.build[name].env||{})});

test('every eas.json profile has an explicit runtime expectation',()=>{
 assert.deepEqual(Object.keys(eas.build).sort(),Object.keys(EXPECTED).sort());
});

for(const [name,runtime] of Object.entries(EXPECTED)){
 test(`profile ${name} ${runtime?`resolves ${runtime}`:'fails closed'}`,()=>{
  for(const withProfileVar of [true,false]){
   const r=resolve({...profileEnv(name),...(withProfileVar?{EAS_BUILD_PROFILE:name}:{})});
   if(runtime){
    assert.equal(r.ok,true,r.stderr);
    assert.equal(r.runtime,runtime);
   }else{
    assert.equal(r.ok,false,`${name} must not resolve a runtime`);
    assert.match(r.stderr,HOLD);
   }
  }
 });
}

test('no profile and no env throws instead of defaulting',()=>{
 const r=resolve();
 assert.equal(r.ok,false);
 assert.match(r.stderr,HOLD);
});

test('unknown profile throws',()=>{
 for(const env of [
  {EAS_BUILD_PROFILE:'does-not-exist'},
  {EXPO_PUBLIC_APP_ENV:'production',EAS_BUILD_PROFILE:'does-not-exist'},
  {EXPO_PUBLIC_APP_ENV:'preview',EAS_BUILD_PROFILE:'does-not-exist'},
  {EXPO_PUBLIC_APP_ENV:'development',EAS_BUILD_PROFILE:'does-not-exist'},
 ]){
  const r=resolve(env);
  assert.equal(r.ok,false,JSON.stringify(env));
  assert.match(r.stderr,HOLD);
 }
});

test(`requesting the SDK57 default ${OLD_DEFAULT} is impossible`,()=>{
 // The Build25 identity (plain production env, with or without the approved-gate markers lacking a v1.5 profile) must not resolve.
 for(const env of [
  {...profileEnv('production'),EAS_BUILD_PROFILE:'production'},
  {...profileEnv('production')},
  {...profileEnv('preview'),EAS_BUILD_PROFILE:'preview'},
  {...profileEnv('development'),EAS_BUILD_PROFILE:'production'},
  {...profileEnv('production-v15-16kb'),ALD1N_V15_RELEASE_PROFILE:'',EAS_BUILD_PROFILE:'preview'},
  {ALD1N_RUNTIME_VERSION:OLD_DEFAULT,RUNTIME_VERSION:OLD_DEFAULT,EXPO_PUBLIC_RUNTIME_VERSION:OLD_DEFAULT},
 ]){
  const r=resolve(env);
  assert.equal(r.ok,false,JSON.stringify(env));
  assert.match(r.stderr,HOLD);
  assert.notEqual(r.runtime,OLD_DEFAULT);
 }
 assert.ok(!readFileSync(path.join(mobile,'app.config.js'),'utf8').includes(OLD_DEFAULT),'default string must not exist in app.config.js');
});
