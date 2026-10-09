import test from 'node:test';
import assert from 'node:assert/strict';
import {createEasTransport} from './eas-transport.mjs';
import fs from 'node:fs';import os from 'node:os';import path from 'node:path';
const ID='11111111-1111-4111-8111-111111111111';
const SHA='c'.repeat(40);
function setup(response){
 const calls=[];
 const spawn=(cmd,args,opts)=>{calls.push({cmd,args,opts});return response?.({cmd,args,opts})??{status:0,stdout:JSON.stringify({versionCode:24,owner:'ald1n',projectId:'d43b3866-6838-4217-a23e-3dc7f2cc76cc',packageName:'com.ald1n.mobile'}),stderr:''}};
 const x=createEasTransport({nodeBin:'/canonical/node',npmCli:'/canonical/npm.js',easVersion:'24.7.0',mobileRoot:'/sandbox/app',spawn,fetchArtifact:async()=>({status:200})});
 return {x,calls};
}
const approval={authorized:true,sourceSha:SHA,attemptId:'attempt-01',persistedIntent:true,lockHeld:true,productionWriteEnabled:true};
test('remote_version_is_read_only',async()=>{
 const {x,calls}=setup();const out=await x.readRemoteVersion();assert.equal(out.versionCode,24);
 assert.ok(calls[0].args.includes('build:version:get'));assert.ok(!calls[0].args.includes('build'));assert.equal(calls[0].opts.cwd,'/sandbox/app');
});
test('rejects_latest_and_unpinned_cli',async()=>{
 assert.throws(()=>createEasTransport({nodeBin:'/n',npmCli:'/npm',easVersion:'latest',mobileRoot:'/r',spawn:()=>({})}),/INVALID_CLI_PIN/);
 const {x}=setup();await assert.rejects(()=>x.readBuild('latest'),/INVALID_BUILD_ID/);
});
test('rejects_mutation_without_authorization',async()=>{
 const {x,calls}=setup();await assert.rejects(()=>x.startProductionBuild({sourceSha:SHA},{}),/BUILD_NOT_AUTHORIZED/);
 assert.equal(calls.length,0);
});
test('rejects_zero_rc_bad_json',async()=>{
 const {x}=setup(()=>({status:0,stdout:'build finished\nNOT JSON',stderr:''}));
 await assert.rejects(()=>x.readRemoteVersion(),/INVALID_EAS_JSON/);
});
test('reconciles_unknown_build_without_redispatch',async()=>{
 const {x,calls}=setup(()=>({status:1,stdout:'',stderr:'server timeout'}));
 await assert.rejects(()=>x.startProductionBuild({sourceSha:SHA,attemptId:'attempt-01'},approval),/DISPATCH_OUTCOME_UNKNOWN/);
 assert.equal(calls.length,1);
});
test('build_errored_cannot_submit',async()=>{
 const {x}=setup(()=>({status:0,stdout:JSON.stringify({id:ID,status:'ERRORED',gitCommitHash:SHA,platform:'ANDROID'}),stderr:''}));
 const b=await x.readBuild(ID);assert.equal(b.status,'ERRORED');
 await assert.rejects(()=>x.submitVerifiedPath({approval:{...approval,artifactAccepted:true},build:b,artifactPath:'/tmp/aab'}),/BUILD_NOT_FINISHED/);
});
test('rejects_foreign_build_id_or_wrong_git_sha',async()=>{
 const {x}=setup(()=>({status:0,stdout:JSON.stringify({id:ID,status:'FINISHED',gitCommitHash:'d'.repeat(40),platform:'ANDROID'}),stderr:''}));
 await assert.rejects(()=>x.readBuild(ID,{sourceSha:SHA}),/SOURCE_SHA_MISMATCH/);
});
test('build_dispatch_has_no_auto_submit_and_exact_intent',async()=>{
 const {x,calls}=setup(()=>({status:0,stdout:JSON.stringify({id:ID,status:'FINISHED',gitCommitHash:SHA,platform:'ANDROID'}),stderr:''}));
 const out=await x.startProductionBuild({sourceSha:SHA,attemptId:'attempt-01'},approval);
 assert.equal(out.id,ID);
 assert.ok(calls[0].args.includes('build'));
 assert.ok(!calls[0].args.some(x=>String(x).includes('auto-submit')));
});

test('artifact_download_refuses_existing_target',async t=>{
 const root=fs.mkdtempSync(path.join(os.tmpdir(),'ald1n25-download-'));t.after(()=>fs.rmSync(root,{recursive:true,force:true}));
 const dst=path.join(root,'release.aab');fs.writeFileSync(dst,'original');
 const x=createEasTransport({nodeBin:'/n',npmCli:'/npm',easVersion:'24.7.0',mobileRoot:'/r',spawn:()=>({status:0,stdout:JSON.stringify({id:ID,status:'FINISHED',platform:'ANDROID',artifacts:{buildUrl:'https://example.test/download.aab'}}),stderr:''}),fetchArtifact:async()=>new Response('new data')});
 await assert.rejects(()=>x.downloadAabForBuild(ID,dst),/ARTIFACT_ALREADY_EXISTS/);
 assert.equal(fs.readFileSync(dst,'utf8'),'original');
});
test('artifact_download_does_not_delete_foreign_temp_symlink',async t=>{
 const root=fs.mkdtempSync(path.join(os.tmpdir(),'ald1n25-download-'));t.after(()=>fs.rmSync(root,{recursive:true,force:true}));
 const dst=path.join(root,'release.aab');const foreign=path.join(root,'foreign');fs.writeFileSync(foreign,'keep');
 const tmp=dst+'.downloading';fs.symlinkSync(foreign,tmp);
 const x=createEasTransport({nodeBin:'/n',npmCli:'/npm',easVersion:'24.7.0',mobileRoot:'/r',spawn:()=>({status:0,stdout:JSON.stringify({id:ID,status:'FINISHED',platform:'ANDROID',artifacts:{buildUrl:'https://example.test/download.aab'}}),stderr:''}),fetchArtifact:async()=>new Response('new data')});
 await assert.rejects(()=>x.downloadAabForBuild(ID,dst));
 assert.equal(fs.lstatSync(tmp).isSymbolicLink(),true);assert.equal(fs.readFileSync(foreign,'utf8'),'keep');
});
