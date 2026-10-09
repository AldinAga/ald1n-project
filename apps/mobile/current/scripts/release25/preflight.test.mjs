import test from 'node:test';
import assert from 'node:assert/strict';
import { collectPreflight } from './preflight.mjs';
const sha='b'.repeat(40);
const known='d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef';
function input(overrides={}){
  return {
    repoRoot:'/tmp/readonly-repo',expectedSourceSha:sha,
    authority:{ok:true,identity:{cliVersion:'24.8.0',packageName:'com.ald1n.mobile',owner:'ald1n',projectId:'d43b3866-6838-4217-a23e-3dc7f2cc76cc',apiUrl:'https://cms.ald1n.com/api/v1',runtimeVersion:'1.0.0-build17',channel:'production',profile:'production',track:'production'}},
    gitReader:{async snapshot(){return {head:sha,remoteMain:sha,staged:[],unstaged:[],untracked:[],knownHtaccessSha256:null,sourcePatchesPresent:true}}},
    easReader:{async readRemoteVersion(){return {versionCode:24,owner:'ald1n',projectId:'d43b3866-6838-4217-a23e-3dc7f2cc76cc',packageName:'com.ald1n.mobile'}}},
    toolProbe:{async inspect(){return {jarsigner:true,keytool:true,bundletool:true,readelf:true,unzip:true,freeSpaceBytes:4e9}}},
    acceptanceIndex:{A03:'PASS',A04:'PASS',A05:'PASS',A06:'PASS',A07:'PASS',A08:'PASS',A09:'PASS',A10:'PASS',A11:'PASS',A12:'PASS',A13:'PASS',A14:'PASS',device:'PASS'},...overrides
  };
}
test('accepts_exact_read_only_preflight_fixture',async()=>{
 const r=await collectPreflight(input());assert.equal(r.ok,true);assert.equal(r.evidence.remoteVersionCode,24);
});
test('blocks_dirty_or_divergent_canonical_index',async()=>{
 for(const data of [{staged:['a']},{untracked:['a']},{unstaged:['a']},{head:'c'.repeat(40)},{remoteMain:'d'.repeat(40)}]){
 const r=await collectPreflight(input({gitReader:{async snapshot(){return {head:sha,remoteMain:sha,staged:[],unstaged:[],untracked:[],sourcePatchesPresent:true,...data}}}}));assert.equal(r.ok,false,JSON.stringify(data));
 }
});
test('allows_only_exact_known_htaccess_drift',async()=>{
 const gitReader={async snapshot(){return {head:sha,remoteMain:sha,staged:[],unstaged:['apps/cms/current/public/.htaccess'],untracked:[],knownHtaccessSha256:known,sourcePatchesPresent:true}}};
 assert.equal((await collectPreflight(input({gitReader}))).ok,true);
 const bad={async snapshot(){return {...await gitReader.snapshot(),knownHtaccessSha256:'0'.repeat(64)}}};
 assert.equal((await collectPreflight(input({gitReader:bad}))).ok,false);
});
test('blocks_missing_native_patch_or_open_release_finding',async()=>{
 const r=await collectPreflight(input({gitReader:{async snapshot(){return {head:sha,remoteMain:sha,staged:[],unstaged:[],untracked:[],sourcePatchesPresent:false}}}}));
 assert.equal(r.ok,false);assert.match(r.blockers.join(','),/MISSING_NATIVE_FIXES/);
 assert.equal((await collectPreflight(input({acceptanceIndex:{A03:'PASS',A04:'PASS'}}))).ok,false);
});
test('blocks_missing_signing_or_bundletool',async()=>{
 const toolProbe={async inspect(){return {jarsigner:false,keytool:true,bundletool:false,readelf:true,unzip:true,freeSpaceBytes:4e9}}};
 const r=await collectPreflight(input({toolProbe}));assert.equal(r.ok,false);assert.match(r.blockers.join(','),/TOOL/);
});
test('blocks_remote_version_change',async()=>{
 const easReader={async readRemoteVersion(){return {versionCode:25,owner:'ald1n',projectId:'d43b3866-6838-4217-a23e-3dc7f2cc76cc',packageName:'com.ald1n.mobile'}}};
 const r=await collectPreflight(input({easReader,expectedRemoteVersionCode:24}));assert.equal(r.ok,false);assert.match(r.blockers.join(','),/REMOTE_VERSION_CHANGED/);
});
test('rejects_wrong_project_id_or_api',async()=>{
 const r=await collectPreflight(input({authority:{ok:true,identity:{projectId:'wrong',apiUrl:'http://localhost'}}}));
 assert.equal(r.ok,false);
});
test('source_changed_since_preflight_blocks',async()=>{
 const r=await collectPreflight(input({expectedSourceSha:'c'.repeat(40)}));assert.equal(r.ok,false);
});
test('blocking_refuses_unverifiable_device_gate',async()=>{
 const ix=input().acceptanceIndex;ix.device='SKIPPED';assert.equal((await collectPreflight(input({acceptanceIndex:ix}))).ok,false);
});
