import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import {createHash} from 'node:crypto';
import {runController} from '../build25-release-controller.mjs';
import {createJournal,readJournal,transitionJournal} from './journal.mjs';
const SHA='f'.repeat(40),BID='11111111-1111-4111-8111-111111111111',SID='22222222-2222-4222-8222-222222222222';
const hash=f=>createHash('sha256').update(fs.readFileSync(f)).digest('hex');
function fixture(t,override={}){
 const root=fs.mkdtempSync(path.join(os.tmpdir(),'ald1n25-controller-'));
 const dir=path.join(root,'state');fs.mkdirSync(dir,{mode:0o700});t.after(()=>fs.rmSync(root,{recursive:true,force:true}));
 const aab=path.join(dir,'release.aab');fs.writeFileSync(aab,Buffer.alloc(2048,0x45),{mode:0o400});
 const calls={build:0,submit:0,preflight:0,download:0};
 const build={id:BID,platform:'ANDROID',status:'FINISHED',gitCommitHash:SHA,appBuildVersion:'25',artifacts:{buildUrl:'https://expo.example/artifact.aab'}};
 const eas={fake:true,async startProductionBuild(){calls.build++;return build},async readBuild(){return build},async downloadAabForBuild(){calls.download++;return {path:aab}},async readSubmission(){return {id:SID,status:'FINISHED'}}};
 const authorization={authorized:true,productionWriteEnabled:true,maxBuilds:1,attemptId:'attempt-01',sourceSha:SHA,expectedRemoteVersionCode:24,lockHeld:true,approvedByOwner:true};
 const authority={ok:true,identity:{profile:'production',channel:'production',track:'production',releaseStatus:'completed'}};
 return {mode:'simulate',sourceSha:SHA,attemptId:'attempt-01',stateDir:dir,authorization,
   preflight:async()=>{calls.preflight++;return {ok:true,blockers:[],evidence:{remoteVersionCode:24}}},
   journalApi:{createJournal,readJournal,transitionJournal},eas,
   signatureVerifier:async()=>({ok:true,sha256:hash(aab)}),
   nativeVerifier:async()=>({ok:true,observed:{deviceReceiptVerified:true,runtimeResourceVerified:true}}),
   authority,expectedSignerSha256:'A'.repeat(64),
   submitter:async()=>{calls.submit++;return {status:'SUBMITTED',submissionId:SID}},
   artifactPath:aab,sourceAuthority:{async snapshot(){return {head:SHA,remoteMain:SHA,profile:'production',channel:'production',track:'production'}}},
   calls,build,...override};
}
test('preflight_zero_remote_mutations_and_no_journal',async t=>{
 const f=fixture(t,{mode:'preflight'});const r=await runController(f);
 assert.equal(r.status,'PREFLIGHT_PASS');assert.equal(f.calls.build,0);assert.equal(f.calls.submit,0);
 assert.equal(fs.existsSync(path.join(f.stateDir,'journal.json')),false);
});
test('no_execute_without_exact_authorization',async t=>{
 const f=fixture(t,{mode:'execute',authorization:{authorized:false}});
 await assert.rejects(()=>runController(f),/EXECUTION_NOT_AUTHORIZED/);
 assert.equal(f.calls.build,0);
});
test('one_build_then_one_submit',async t=>{
 const f=fixture(t);const r=await runController(f);
 assert.equal(r.status,'FINAL_STATUS_VERIFIED');assert.equal(f.calls.build,1);assert.equal(f.calls.submit,1);
 assert.equal(readJournal(f.stateDir).stage,'FINAL_STATUS_VERIFIED');
});
test('build_error_blocks_submit',async t=>{
 const f=fixture(t);f.eas.startProductionBuild=async()=>{f.calls.build++;return {...f.build,status:'ERRORED'}};
 const r=await runController(f);assert.equal(r.status,'BLOCKED');assert.equal(f.calls.submit,0);
});
test('orphan_intent_blocks_dispatch',async t=>{
 const f=fixture(t),j=createJournal({stateDir:f.stateDir,attemptId:f.attemptId,sourceSha:f.sourceSha});
 const pre=transitionJournal(j,'CREATED','PREFLIGHT_PASS',{});
 transitionJournal(pre,'PREFLIGHT_PASS','BUILD_DISPATCH_INTENT_PERSISTED',{});
 await assert.rejects(()=>runController(f),/ALREADY_EXISTS/);assert.equal(f.calls.build,0);
});
test('wrong_artifact_blocks_submit',async t=>{
 const f=fixture(t);f.signatureVerifier=async()=>({ok:false,reason:'SIGNATURE_CRYPTO_FAILED'});
 const r=await runController(f);assert.equal(r.status,'BLOCKED');assert.equal(f.calls.submit,0);
});
test('final_sha_or_version_change_blocks_submit',async t=>{
 const f=fixture(t);f.eas.readBuild=async()=>({...f.build,gitCommitHash:'a'.repeat(40)});
 const r=await runController(f);assert.equal(r.status,'BLOCKED');assert.equal(f.calls.submit,0);
});
test('submission_unknown_blocks_retry',async t=>{
 const f=fixture(t);f.submitter=async()=>{f.calls.submit++;throw new Error('DISPATCH_OUTCOME_UNKNOWN')};
 const r=await runController(f);assert.equal(r.status,'DISPATCH_OUTCOME_UNKNOWN');assert.equal(f.calls.submit,1);
});
test('redacts_credentials_from_report',async t=>{
 const f=fixture(t);f.signatureVerifier=async()=>{throw new Error('Authorization: Bearer TOP_SECRET_TOKEN')};
 const r=await runController(f);assert.equal(r.status,'BLOCKED');
 assert.ok(!JSON.stringify(readJournal(f.stateDir)).includes('TOP_SECRET_TOKEN'));
});
test('parallel_simulation_one_dispatch',async t=>{
 const f=fixture(t);const results=await Promise.allSettled([runController(f),runController({...f})]);
 assert.equal(f.calls.build,1);assert.equal(results.filter(x=>x.status==='rejected').length,1);
});
test('simulate_requires_fake_transport',async t=>{
 const f=fixture(t);f.eas.fake=false;await assert.rejects(()=>runController(f),/SIMULATION_TRANSPORT_REQUIRED/);
});

test('native_verifier_must_prove_device_and_runtime_receipts',async t=>{
 const f=fixture(t);f.nativeVerifier=async()=>({ok:true});
 const r=await runController(f);assert.equal(r.status,'BLOCKED');assert.equal(f.calls.submit,0);
});
test('cannot_assume_validated_authority_for_submit',async t=>{
 const f=fixture(t,{authority:{ok:false}});const r=await runController(f);
 assert.equal(r.status,'BLOCKED');assert.equal(f.calls.build,0);
});
test('missing_expected_upload_signer_blocks_before_build',async t=>{
 const f=fixture(t,{expectedSignerSha256:undefined});const r=await runController(f);
 assert.equal(r.status,'BLOCKED');assert.equal(f.calls.build,0);
});
