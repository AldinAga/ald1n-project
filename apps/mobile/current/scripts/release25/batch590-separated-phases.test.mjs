import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import {runController} from '../build25-release-controller.mjs';
import {createJournal,readJournal,transitionJournal} from './journal.mjs';
import {collectPreflight} from './preflight.mjs';

const SHA='f'.repeat(40),BID='11111111-1111-4111-8111-111111111111',SID='22222222-2222-4222-8222-222222222222';
const CERT='16431DCBBCB39811BBDB52A61FA5C56D3C26469D043548BE759643F033F26DA4';
const prevBuild=process.env.ALD1N_BUILD25_LIVE_ENABLE, prevPlay=process.env.ALD1N_BUILD25_PLAY_LIVE_ENABLE;
process.env.ALD1N_BUILD25_LIVE_ENABLE='YES';process.env.ALD1N_BUILD25_PLAY_LIVE_ENABLE='YES';
process.on('exit',()=>{
 if(prevBuild===undefined)delete process.env.ALD1N_BUILD25_LIVE_ENABLE;else process.env.ALD1N_BUILD25_LIVE_ENABLE=prevBuild;
 if(prevPlay===undefined)delete process.env.ALD1N_BUILD25_PLAY_LIVE_ENABLE;else process.env.ALD1N_BUILD25_PLAY_LIVE_ENABLE=prevPlay;
});
function fixture(t){
 const root=fs.mkdtempSync(path.join(os.tmpdir(),'ald1n-batch590-'));
 const state=path.join(root,'state');fs.mkdirSync(state,{mode:0o700});t.after(()=>fs.rmSync(root,{recursive:true,force:true}));
 const calls={build:0,submit:0,download:0,preflight:[]};
 const build={id:BID,platform:'ANDROID',status:'FINISHED',gitCommitHash:SHA,appBuildVersion:'25'};
 const eas={fake:true,async startProductionBuild(){calls.build++;return build},async readBuild(){return build},
  async downloadAabForBuild(_id,f){calls.download++;fs.writeFileSync(f,Buffer.alloc(2048,0x42),{mode:0o400});},
  async readSubmission(){return {id:SID,status:'FINISHED'}}};
 const auth={authorized:true,approvedByOwner:true,productionWriteEnabled:true,lockHeld:true,sourceSha:SHA,attemptId:'phase590test',maxBuilds:1,
   expectedRemoteVersionCode:24,expectedSignerSha256:CERT,uploadSignerVerified:true};
 const preflight=async({phase}={})=>{calls.preflight.push(phase);return {ok:true,evidence:{preflightPhase:phase,gitSha:SHA,
   remoteProjectIdentityVerified:true,releaseAcceptancesVerified:phase==='submit',remoteVersionCode:phase==='build'?24:25}}};
 const authority={ok:true,identity:{profile:'production',channel:'production',track:'production',releaseStatus:'completed'}};
 return {mode:'build-only',sourceSha:SHA,attemptId:'phase590test',stateDir:state,authorization:auth,authority,
   preflight,journalApi:{createJournal,readJournal,transitionJournal},eas,expectedSignerSha256:CERT,
   signatureVerifier:async({expectedSha256})=>({ok:true,sha256:expectedSha256,certSha256:CERT}),
   nativeVerifier:async()=>({ok:true,observed:{deviceReceiptVerified:true,runtimeResourceVerified:true}}),
   submitter:async()=>{calls.submit++;return {submissionId:SID}},artifactPath:path.join(state,'signed.aab'),
   sourceAuthority:{async snapshot(){return {head:SHA,remoteMain:SHA,profile:'production',channel:'production',track:'production'}}},
   expectedIdentity:{packageName:'com.ald1n.mobile'},expectedRuntime:'1.0.0-build17',evidence:{},calls,build};
}
const submitAuth=(f)=>({...f.authorization,playSubmitApproved:true,maxSubmissions:1,expectedBuildId:BID,expectedBuiltVersionCode:25});
function preflightInput(phase){
 return {phase,repoRoot:'/tmp/batch590',expectedSourceSha:SHA,
 authority:{ok:true,identity:{owner:'ald1n',packageName:'com.ald1n.mobile',projectId:'d43b3866-6838-4217-a23e-3dc7f2cc76cc',apiUrl:'https://cms.ald1n.com/api/v1',channel:'production',profile:'production',track:'production'}},
 gitReader:{async snapshot(){return {head:SHA,remoteMain:SHA,staged:[],unstaged:[],untracked:[],sourcePatchesPresent:true}}},
 easReader:{async readRemoteProjectIdentity(){return {owner:'ald1n',slug:'ald1n-mobile',projectId:'d43b3866-6838-4217-a23e-3dc7f2cc76cc'}},async readRemoteVersion(){return {versionCode:24}}},
 toolProbe:{async inspect(){return {freeSpaceBytes:5e9,jarsigner:false,keytool:false,bundletool:false,readelf:false,unzip:true}}},acceptanceIndex:{}};
}
test('build preflight separates postbuild A03-device-JDK evidence without asserting it passed',async()=>{
 const a=await collectPreflight(preflightInput('build'));
 assert.equal(a.ok,true,JSON.stringify(a.blockers));assert.equal(a.evidence.preflightPhase,'build');assert.notEqual(a.evidence.releaseAcceptancesVerified,true);
});
test('submit preflight still requires all A03-A14 devices and JDK tools',async()=>{
 const a=await collectPreflight(preflightInput('submit'));assert.equal(a.ok,false);assert.match(a.blockers.join(','),/RELEASE_FINDING_NOT_CLOSED:device/);assert.match(a.blockers.join(','),/TOOL_MISSING:jarsigner/);
});
test('unknown preflight phase fails closed',async()=>{assert.equal((await collectPreflight(preflightInput('skip-all'))).ok,false)});
test('build-only persists one remote build and never submits',async t=>{
 const f=fixture(t),r=await runController(f);
 assert.equal(r.status,'BUILD_FINISHED_AWAITING_POSTBUILD_ACCEPTANCE');assert.equal(r.versionCode,25);assert.equal(f.calls.build,1);assert.equal(f.calls.submit,0);
 assert.equal(readJournal(f.stateDir).stage,'BUILD_FINISHED');assert.match(r.aabSha256,/^[a-f0-9]{64}$/);assert.equal(fs.statSync(f.artifactPath).size,2048);assert.equal(f.calls.download,1);assert.deepEqual(f.calls.preflight,['build']);
});
test('build-only denies missing or fake owner authorization',async t=>{
 const f=fixture(t);f.authorization={...f.authorization,approvedByOwner:false};
 await assert.rejects(()=>runController(f),/SEPARATED_PHASE_NOT_AUTHORIZED/);assert.equal(f.calls.build,0);
});
test('build-only refuses Play approval piggyback and does not dispatch',async t=>{
 const f=fixture(t);f.authorization={...f.authorization,playSubmitApproved:true};
 await assert.rejects(()=>runController(f),/BUILD_PHASE_MUST_NOT_INCLUDE_PLAY_APPROVAL/);assert.equal(f.calls.build,0);
});
test('build dispatch unknown never retries from existing journal',async t=>{
 const f=fixture(t);f.eas.startProductionBuild=async()=>{f.calls.build++;throw new Error('timeout')};
 const first=await runController(f);assert.equal(first.status,'DISPATCH_OUTCOME_UNKNOWN');
 await assert.rejects(()=>runController(f),/ALREADY_EXISTS/);assert.equal(f.calls.build,1);
});
test('submit-only resumes same verified build, does not dispatch another build',async t=>{
 const f=fixture(t);assert.equal((await runController(f)).status,'BUILD_FINISHED_AWAITING_POSTBUILD_ACCEPTANCE');
 const r=await runController({...f,mode:'submit-only',authorization:submitAuth(f)});
 assert.equal(r.status,'FINAL_STATUS_VERIFIED');assert.equal(f.calls.build,1);assert.equal(f.calls.submit,1);assert.equal(f.calls.download,1);
 assert.equal(readJournal(f.stateDir).stage,'FINAL_STATUS_VERIFIED');assert.deepEqual(f.calls.preflight,['build','submit']);
});
test('submit-only refuses absent distinct Play owner approval',async t=>{
 const f=fixture(t);await runController(f);await assert.rejects(()=>runController({...f,mode:'submit-only'}),/PLAY_SUBMIT_NOT_AUTHORIZED/);assert.equal(f.calls.submit,0);
});
test('submit-only refuses missing device A03-A14 acceptance before downloading',async t=>{
 const f=fixture(t);await runController(f);
 const r=await runController({...f,mode:'submit-only',authorization:submitAuth(f),preflight:async()=>({ok:false,blockers:['RELEASE_FINDING_NOT_CLOSED:A03']})});
 assert.equal(r.status,'BLOCKED');assert.equal(f.calls.download,1);assert.equal(f.calls.submit,0);
});
test('submit-only refuses altered version code without downloading or submitting',async t=>{
 const f=fixture(t);await runController(f);const auth={...submitAuth(f),expectedBuiltVersionCode:26};
 const r=await runController({...f,mode:'submit-only',authorization:auth});assert.equal(r.reason,'BUILT_VERSION_OR_BUILD_ID_DRIFT');assert.equal(f.calls.download,1);
});
test('submit-only refuses unsigned or wrong certificate AAB',async t=>{
 const f=fixture(t);await runController(f);
 const r=await runController({...f,mode:'submit-only',authorization:submitAuth(f),signatureVerifier:async()=>({ok:false})});
 assert.equal(r.reason,'CRYPTO_VERIFY_FAILED');assert.equal(f.calls.submit,0);
});
test('submit-only refuses device/runtime absence even after cryptographic signer',async t=>{
 const f=fixture(t);await runController(f);
 const r=await runController({...f,mode:'submit-only',authorization:submitAuth(f),nativeVerifier:async()=>({ok:true,observed:{deviceReceiptVerified:false}})});
 assert.equal(r.reason,'NATIVE_OR_DEVICE_RECEIPT_NOT_VERIFIED');assert.equal(f.calls.submit,0);
});
test('submit-only cannot retry existing successfully submitted build',async t=>{
 const f=fixture(t);await runController(f);
 assert.equal((await runController({...f,mode:'submit-only',authorization:submitAuth(f)})).status,'FINAL_STATUS_VERIFIED');
 const r=await runController({...f,mode:'submit-only',authorization:submitAuth(f)});assert.equal(r.reason,'BUILD_JOURNAL_NOT_READY');assert.equal(f.calls.submit,1);
});

test('submit-only rejects changed sealed AAB without re-downloading',async t=>{
 const f=fixture(t);await runController(f);fs.chmodSync(f.artifactPath,0o600);fs.appendFileSync(f.artifactPath,'TAMPER');fs.chmodSync(f.artifactPath,0o400);
 const r=await runController({...f,mode:'submit-only',authorization:submitAuth(f)});
 assert.equal(r.reason,'AAB_CHANGED_SINCE_BUILD_PHASE');assert.equal(f.calls.download,1);assert.equal(f.calls.submit,0);
});
test('build-only never redispatches after artifact transfer fails',async t=>{
 const f=fixture(t);f.eas.downloadAabForBuild=async()=>{f.calls.download++;throw new Error('download failed')};
 const r=await runController(f);assert.equal(r.reason,'BUILD_FINISHED_AAB_RETRIEVAL_PENDING_NO_REDISPATCH');
 await assert.rejects(()=>runController(f),/ALREADY_EXISTS/);assert.equal(f.calls.build,1);assert.equal(f.calls.submit,0);
});

test('legacy one-shot execute stays permanently blocked despite existing authorization',async t=>{
 const f=fixture(t);f.mode='execute';
 await assert.rejects(()=>runController(f),/EXECUTION_NOT_AUTHORIZED_USE_SEPARATED_PHASES/);
 assert.equal(f.calls.build,0);assert.equal(f.calls.submit,0);
});
