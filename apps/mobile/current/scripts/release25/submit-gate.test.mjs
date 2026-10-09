import test from 'node:test';
import assert from 'node:assert/strict';
import os from 'node:os';
import fs from 'node:fs';
import path from 'node:path';
import {createHash} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import {submitAcceptedAab} from './submit-gate.mjs';
const SHA='c'.repeat(40),BID='11111111-1111-4111-8111-111111111111';
function setup(t){
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'ald1n25-submit-'));
 t.after(()=>fs.rmSync(dir,{recursive:true,force:true}));
 const aab=path.join(dir,'release.aab');fs.writeFileSync(aab,Buffer.alloc(4096,0x55),{mode:0o400});
 const ah=createHash('sha256').update(fs.readFileSync(aab)).digest('hex');
 const calls=[];
 const journal={stage:'SUBMIT_DISPATCH_INTENT_PERSISTED',attemptId:'attempt-01',sourceSha:SHA,buildId:BID,sha256:ah};
 const build={id:BID,status:'FINISHED',platform:'ANDROID',gitCommitHash:SHA};
 const approval={authorized:true,artifactAccepted:true,productionWriteEnabled:true,lockHeld:true,persistedIntent:true,attemptId:'attempt-01',sourceSha:SHA};
 const acceptance={signature:{ok:true,sha256:ah},native:{ok:true},buildId:BID,sourceSha:SHA,sha256:ah,deviceGate:'PASS'};
 const authority={ok:true,identity:{profile:'production',channel:'production',track:'production',releaseStatus:'completed'}};
 const easTransport={async submitVerifiedPath(args){calls.push(args);return {id:'22222222-2222-4222-8222-222222222222',status:'FINISHED'}}};
 const sourceAuthority={async snapshot(){return {head:SHA,remoteMain:SHA,profile:'production',channel:'production',track:'production'}}};
 return {sealedAabPath:aab,expectedSha256:ah,journal,build,approval,acceptance,authority,easTransport,sourceAuthority,calls};
}
test('old_execute_cannot_bypass_acceptance',()=>{
 const script=fs.readFileSync(new URL('../submit-android-production.mjs',import.meta.url),'utf8');
 assert.match(script,/DIRECT_SUBMIT_DISABLED/);
});
test('positive_submission_exact_verified_path_once',async t=>{
 const p=setup(t);const r=await submitAcceptedAab(p);assert.equal(r.status,'SUBMITTED');assert.equal(p.calls.length,1);
 assert.equal(p.calls[0].artifactPath,p.sealedAabPath);
});
test('rejects_build_id_or_sha_mismatch',async t=>{
 const p=setup(t);p.build.id='33333333-3333-4333-8333-333333333333';
 await assert.rejects(()=>submitAcceptedAab(p),/BUILD_OR_HASH_MISMATCH/);assert.equal(p.calls.length,0);
});
test('rejects_modified_aab_after_acceptance',async t=>{
 const p=setup(t);fs.chmodSync(p.sealedAabPath,0o600);fs.writeFileSync(p.sealedAabPath,Buffer.alloc(4096,0x56));fs.chmodSync(p.sealedAabPath,0o400);
 await assert.rejects(()=>submitAcceptedAab(p),/AAB_CHANGED/);assert.equal(p.calls.length,0);
});
test('rejects_changed_main_or_play_profile',async t=>{
 const p=setup(t);p.sourceAuthority={async snapshot(){return {head:'d'.repeat(40),remoteMain:SHA,profile:'production',channel:'production',track:'production'}}};
 await assert.rejects(()=>submitAcceptedAab(p),/SOURCE_AUTHORITY_CHANGED/);
 const p2=setup(t);p2.sourceAuthority={async snapshot(){return {head:SHA,remoteMain:SHA,profile:'production',channel:'production',track:'internal'}}};
 await assert.rejects(()=>submitAcceptedAab(p2),/SOURCE_AUTHORITY_CHANGED/);
});
test('rejects_submission_without_device_gate',async t=>{
 const p=setup(t);p.acceptance.deviceGate='NOT_VERIFIED';await assert.rejects(()=>submitAcceptedAab(p),/ACCEPTANCE_NOT_PASS/);
});
test('submit_timeout_never_autoretries',async t=>{
 const p=setup(t);p.easTransport={async submitVerifiedPath(a){p.calls.push(a);throw new Error('DISPATCH_OUTCOME_UNKNOWN')}};
 await assert.rejects(()=>submitAcceptedAab(p),/DISPATCH_OUTCOME_UNKNOWN/);assert.equal(p.calls.length,1);
});
test('rejects_file_symlink_or_hardlink',async t=>{
 const p=setup(t);const a=path.join(path.dirname(p.sealedAabPath),'linked.aab');fs.linkSync(p.sealedAabPath,a);
 await assert.rejects(()=>submitAcceptedAab(p),/AAB_NOT_SEALED/);
});
