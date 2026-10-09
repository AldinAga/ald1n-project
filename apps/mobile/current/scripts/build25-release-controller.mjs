#!/usr/bin/env node
import fs from 'node:fs';
import path from 'node:path';
import {createHash} from 'node:crypto';
import {fileURLToPath} from 'node:url';
import {submitAcceptedAab} from './release25/submit-gate.mjs';
const UUID=/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
const SHA=/^[0-9a-f]{40}$/i;
function sha256(pathName){const h=createHash('sha256');const fd=fs.openSync(pathName,fs.constants.O_RDONLY|fs.constants.O_NOFOLLOW);try{const b=Buffer.allocUnsafe(1024*1024);let p=0;for(;;){const n=fs.readSync(fd,b,0,b.length,p);if(!n)break;h.update(b.subarray(0,n));p+=n}return h.digest('hex')}finally{fs.closeSync(fd)}}
const immutableError=(message)=>new Error(message);
export async function runController({mode,sourceSha,attemptId,stateDir,authorization,authority,preflight,journalApi,eas,signatureVerifier,nativeVerifier,submitter=submitAcceptedAab,artifactPath,sourceAuthority,expectedSignerSha256,expectedIdentity,expectedRuntime,evidence}){
 if(!['preflight','simulate','execute'].includes(mode))throw immutableError('INVALID_CONTROLLER_MODE');
 if(!SHA.test(sourceSha))throw immutableError('INVALID_SOURCE_SHA');
 if(authority?.ok!==true||authority?.identity?.profile!=='production'||authority.identity.channel!=='production'||authority.identity.track!=='production'||authority.identity.releaseStatus!=='completed')return {status:'BLOCKED',reason:'UNVERIFIED_RELEASE_AUTHORITY'};
 if(!/^[0-9a-f]{64}$/i.test(expectedSignerSha256??''))return {status:'BLOCKED',reason:'EXPECTED_UPLOAD_SIGNER_NOT_CONFIRMED'};
 if(mode==='simulate'&&eas?.fake!==true)throw immutableError('SIMULATION_TRANSPORT_REQUIRED');
 if(mode==='execute'){
  if(process.env.ALD1N_BUILD25_LIVE_ENABLE!=='YES'||authorization?.approvedByOwner!==true||authorization?.authorized!==true||authorization?.productionWriteEnabled!==true||authorization?.maxBuilds!==1||authorization?.sourceSha!==sourceSha||authorization?.lockHeld!==true)throw immutableError('EXECUTION_NOT_AUTHORIZED');
 }
 const pre=await preflight();
 if(!pre?.ok)return {status:'BLOCKED',blockers:pre?.blockers||['PREFLIGHT_FAILED']};
 if(mode==='preflight')return {status:'PREFLIGHT_PASS',evidence:pre.evidence};
 if(!authorization?.authorized||!authorization.lockHeld||authorization.sourceSha!==sourceSha||authorization.attemptId!==attemptId||authorization.productionWriteEnabled!==true)throw immutableError('EXECUTION_NOT_AUTHORIZED');
 if(!Number.isSafeInteger(pre.evidence?.remoteVersionCode)||pre.evidence.remoteVersionCode<1||authorization.expectedRemoteVersionCode!==pre.evidence.remoteVersionCode)throw immutableError('REMOTE_VERSION_CHANGED');
 let j=journalApi.createJournal({stateDir,attemptId,sourceSha});
 const shift=(from,to,patch={})=>{j=journalApi.transitionJournal(j,from,to,patch);return j};
 let stage='CREATED';
 const block=(reason)=>{try{shift(stage,'BLOCKED',{reason});stage='BLOCKED'}catch{};return {status:'BLOCKED',reason}};
 const unknown=(reason)=>{try{shift(stage,'DISPATCH_OUTCOME_UNKNOWN',{reason});stage='DISPATCH_OUTCOME_UNKNOWN'}catch{};return {status:'DISPATCH_OUTCOME_UNKNOWN',reason}};
 shift(stage,'PREFLIGHT_PASS',{remoteVersionCode:pre.evidence.remoteVersionCode});stage='PREFLIGHT_PASS';
 shift(stage,'BUILD_DISPATCH_INTENT_PERSISTED',{});stage='BUILD_DISPATCH_INTENT_PERSISTED';
 let b;
 try {
  b=await eas.startProductionBuild({sourceSha,attemptId},{...authorization,persistedIntent:true});
 }catch{return unknown('BUILD_DISPATCH_UNCERTAIN')}
 if(!UUID.test(b?.id)||b.platform!=='ANDROID'||b.gitCommitHash!==sourceSha)return unknown('BUILD_RESPONSE_UNVERIFIABLE');
 shift(stage,'BUILD_ID_KNOWN',{buildId:b.id});stage='BUILD_ID_KNOWN';
 if(b.status==='ERRORED'||b.status==='CANCELED')return block('BUILD_NOT_FINISHED');
 try{b=await eas.readBuild(b.id,{sourceSha})}catch{return block('BUILD_METADATA_UNVERIFIABLE')}
 if(b.id!==j.buildId||b.gitCommitHash!==sourceSha||b.status!=='FINISHED'||b.platform!=='ANDROID')return block('BUILD_NOT_FINISHED');
 const actualCode=Number(b.appBuildVersion);
 if(!Number.isSafeInteger(actualCode)||actualCode!==pre.evidence.remoteVersionCode+1)return block('BUILD_VERSION_UNEXPECTED');
 shift(stage,'BUILD_FINISHED',{versionCode:actualCode});stage='BUILD_FINISHED';
 try{await eas.downloadAabForBuild(b.id,artifactPath,{sourceSha})}catch{return block('AAB_DOWNLOAD_FAILED')}
 let digest;
 try{digest=sha256(artifactPath)}catch{return block('AAB_HASH_UNAVAILABLE')}
 let sig,native;
 try{sig=await signatureVerifier({sealedAabPath:artifactPath,expectedSha256:digest,expectedUploadCertSha256:expectedSignerSha256})}catch{return block('CRYPTO_VERIFY_FAILED')}
 if(sig?.ok!==true||sig.sha256!==digest)return block('CRYPTO_VERIFY_FAILED');
 try{native=await nativeVerifier({sealedAabPath:artifactPath,expectedIdentity,expectedVersionCode:actualCode,expectedAbiSet:['arm64-v8a','armeabi-v7a','x86','x86_64'],expectedRuntime,expectedSha256:digest,evidence})}catch{return block('NATIVE_VERIFY_FAILED')}
 if(native?.ok!==true||native?.observed?.deviceReceiptVerified!==true||native?.observed?.runtimeResourceVerified!==true)return block('NATIVE_VERIFY_FAILED');
 shift(stage,'AAB_VERIFIED',{sha256:digest});stage='AAB_VERIFIED';
 shift(stage,'SUBMIT_DISPATCH_INTENT_PERSISTED',{});stage='SUBMIT_DISPATCH_INTENT_PERSISTED';
 const approval={...authorization,persistedIntent:true,artifactAccepted:true};
 let submitted;
 try{
  submitted=await submitter({journal:j,approval,build:b,sealedAabPath:artifactPath,expectedSha256:digest,acceptance:{signature:sig,native,buildId:b.id,sha256:digest,sourceSha,deviceGate:'PASS'},authority,easTransport:eas,sourceAuthority});
 }catch{return unknown('SUBMIT_DISPATCH_UNCERTAIN')}
 if(!UUID.test(submitted?.submissionId))return unknown('SUBMIT_RESPONSE_UNVERIFIABLE');
 shift(stage,'SUBMISSION_ID_KNOWN',{submissionId:submitted.submissionId});stage='SUBMISSION_ID_KNOWN';
 let confirm;
 try{confirm=await eas.readSubmission(submitted.submissionId)}catch{return {status:'SUBMISSION_ID_KNOWN',submissionId:submitted.submissionId}}
 if(confirm?.id!==submitted.submissionId||confirm?.status!=='FINISHED')return {status:'SUBMISSION_ID_KNOWN',submissionId:submitted.submissionId};
 shift(stage,'SUBMIT_FINISHED',{});stage='SUBMIT_FINISHED';
 shift(stage,'FINAL_STATUS_VERIFIED',{});stage='FINAL_STATUS_VERIFIED';
 return {status:'FINAL_STATUS_VERIFIED',buildId:b.id,submissionId:submitted.submissionId,aabSha256:digest,sourceSha,versionCode:actualCode};
}

// Operational CLI requires canonical server preflight adapters and release authorization.
// It must not accept an unreviewed JSON fixture as authorization for a real Play mutation.
const direct=process.argv[1]&&path.resolve(process.argv[1])===fileURLToPath(import.meta.url);
if(direct){
 console.error('BUILD25_BLOCKED: Direct production CLI not enabled until on-host EAS, signer, device and release authority gates are certified. No build or submit attempted.');
 process.exitCode=78;
}
