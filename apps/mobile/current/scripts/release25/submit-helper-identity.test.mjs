import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import {spawnSync} from 'node:child_process';
import test from 'node:test';
import {fileURLToPath} from 'node:url';

// Runs the real helper in a throwaway root with a fake Expo CLI. Nothing is submitted: --execute is disabled
// and the fake root has no EAS tooling.
const mobile=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const BID='11111111-1111-4111-8111-111111111111';
const eas=JSON.parse(fs.readFileSync(path.join(mobile,'eas.json'),'utf8'));
const pkg=JSON.parse(fs.readFileSync(path.join(mobile,'package.json'),'utf8'));
// Fake `expo config`: reports build17 unless the helper supplied the SDK58 play identity markers.
const fakeCli=`const ok=process.env.ALD1N_M17_SDK58_PLAY_PROFILE==='m17-sdk58-play-rc'&&process.env.ALD1N_M17_SDK58_PLAY_RC==='source-only';
console.log(JSON.stringify({version:'1.0.0',runtimeVersion:process.env.FAKE_RUNTIME||(ok?'1.0.0-m17-sdk58-play2':'1.0.0-build17'),
android:{package:'com.ald1n.mobile'},extra:{appEnv:process.env.EXPO_PUBLIC_APP_ENV,apiUrl:process.env.EXPO_PUBLIC_API_URL}}));`;

function run(args,{fakeRuntime,easPatch}={}){
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'ald1n-submit-id-'));
 try{
  fs.mkdirSync(path.join(dir,'scripts'));
  fs.mkdirSync(path.join(dir,'node_modules/expo/bin'),{recursive:true});
  fs.copyFileSync(path.join(mobile,'scripts/submit-android-production.mjs'),path.join(dir,'scripts/submit-android-production.mjs'));
  fs.writeFileSync(path.join(dir,'package.json'),JSON.stringify({version:pkg.version,type:'commonjs'}));
  fs.writeFileSync(path.join(dir,'eas.json'),JSON.stringify(easPatch?easPatch(structuredClone(eas)):eas));
  fs.writeFileSync(path.join(dir,'node_modules/expo/bin/cli'),fakeCli);
  const env={...process.env};
  if(fakeRuntime) env.FAKE_RUNTIME=fakeRuntime; else delete env.FAKE_RUNTIME;
  return spawnSync(process.execPath,[path.join(dir,'scripts/submit-android-production.mjs'),...args],{cwd:dir,env,encoding:'utf8',timeout:15000});
 }finally{fs.rmSync(dir,{recursive:true,force:true})}
}

test('--check accepts only the SDK58 play identity and never would run a submit',()=>{
 const r=run(['--check',BID]);
 assert.equal(r.status,0,r.stderr);
 assert.match(r.stdout,/RUNTIME_VERSION=1\.0\.0-m17-sdk58-play2/);
 assert.match(r.stdout,/SUBMIT_WOULD_RUN=NO/);
 assert.doesNotMatch(r.stdout,/SUBMIT_WOULD_RUN=YES/);
});

test('a build17 runtime identity is refused',()=>{
 const r=run(['--check',BID],{fakeRuntime:'1.0.0-build17'});
 assert.equal(r.status,65);
 assert.match(r.stderr,/runtimeVersion mismatch: 1\.0\.0-build17/);
 assert.doesNotMatch(r.stdout,/PRODUCTION_SUBMIT_HELPER=PASS/);
 assert.ok(!fs.readFileSync(path.join(mobile,'scripts/submit-android-production.mjs'),'utf8').includes("EXPECTED_RUNTIME = '1.0.0-build17'"));
});

test('--execute is disabled: the approval gate cannot be bypassed through the helper',()=>{
 const r=run(['--execute',BID]);
 assert.equal(r.status,78);
 assert.match(r.stderr,/DIRECT_SUBMIT_DISABLED/);
 assert.doesNotMatch(r.stdout,/SUBMIT_WOULD_RUN/);
});

test('missing SDK58 play profile, bad build id and extra args are refused',()=>{
 const noProfile=run(['--check',BID],{easPatch:e=>{delete e.build['m17-sdk58-play-rc'];return e}});
 assert.equal(noProfile.status,65);
 assert.match(noProfile.stderr,/play release profile mismatch/);
 assert.equal(run(['--check','not-a-uuid']).status,64);
 assert.equal(run(['--check',BID,'--extra']).status,64);
});
