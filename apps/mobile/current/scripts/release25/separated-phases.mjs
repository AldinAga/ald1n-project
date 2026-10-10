import fs from 'node:fs';
import path from 'node:path';
import {createHash} from 'node:crypto';
import {submitAcceptedAab} from './submit-gate.mjs';

const UUID=/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
const SHA40=/^[0-9a-f]{40}$/i;
const SHA256=/^[0-9a-f]{64}$/i;
const reject=reason=>{throw new Error(reason)};
const blocked=reason=>({status:'BLOCKED',reason});
function fileHash(filename){
 const hash=createHash('sha256');let fd;
 try{
  const st=fs.lstatSync(filename),parent=fs.statSync(path.dirname(filename));
  if(!st.isFile()||st.isSymbolicLink()||st.nlink!==1||st.size<100||(st.mode&0o377)!==0||(parent.mode&0o077)!==0)throw new Error('UNSAFE_AAB_FILE');
  fd=fs.openSync(filename,fs.constants.O_RDONLY|fs.constants.O_NOFOLLOW);
  const opened=fs.fstatSync(fd);if(opened.ino!==st.ino||opened.dev!==st.dev||opened.size!==st.size)throw new Error('AAB_CHANGED');
  const buf=Buffer.allocUnsafe(1024*1024);let pos=0;
  for(;;){const n=fs.readSync(fd,buf,0,buf.length,pos);if(!n)break;hash.update(buf.subarray(0,n));pos+=n;}
  const latest=fs.lstatSync(filename);
  if(latest.ino!==st.ino||latest.dev!==st.dev||latest.size!==st.size||latest.mtimeMs!==st.mtimeMs)throw new Error('AAB_CHANGED');
  return hash.digest('hex');
 }finally{if(fd!==undefined)fs.closeSync(fd)}
}
function validateBase({mode,sourceSha,attemptId,authority,authorization,expectedSignerSha256}){
 if(!['build-only','submit-only'].includes(mode))reject('INVALID_SEPARATED_MODE');
 if(!SHA40.test(sourceSha))reject('INVALID_SOURCE_SHA');
 if(!/^[A-Za-z0-9][A-Za-z0-9._-]{3,100}$/.test(attemptId||''))reject('INVALID_ATTEMPT_ID');
 if(authority?.ok!==true||authority.identity?.profile!=='production'||authority.identity?.channel!=='production'||authority.identity?.track!=='production'||authority.identity?.releaseStatus!=='completed')return blocked('UNVERIFIED_RELEASE_AUTHORITY');
 if(!SHA256.test(expectedSignerSha256||''))return blocked('EXPECTED_UPLOAD_SIGNER_NOT_CONFIRMED');
 if(process.env.ALD1N_BUILD25_LIVE_ENABLE!=='YES'||authorization?.approvedByOwner!==true||authorization.authorized!==true||authorization.productionWriteEnabled!==true||authorization.lockHeld!==true||authorization.sourceSha!==sourceSha||authorization.attemptId!==attemptId||authorization.maxBuilds!==1)reject('SEPARATED_PHASE_NOT_AUTHORIZED');
 if(authorization.uploadSignerVerified!==true||authorization.expectedSignerSha256!==expectedSignerSha256)reject('UPLOAD_SIGNER_AUTHORITY_NOT_ATTESTED');
 if(mode==='build-only'&&authorization.playSubmitApproved===true)reject('BUILD_PHASE_MUST_NOT_INCLUDE_PLAY_APPROVAL');
 if(mode==='submit-only'&&(process.env.ALD1N_BUILD25_PLAY_LIVE_ENABLE!=='YES'||authorization.playSubmitApproved!==true||authorization.maxSubmissions!==1))reject('PLAY_SUBMIT_NOT_AUTHORIZED');
 return null;
}
function runJournal(journalApi,initial){
 let j=initial;
 return {get:()=>j,shift:(from,to,data={})=>{j=journalApi.transitionJournal(j,from,to,data);return j;}};
}
export async function runSeparatedPhase(input){
 const {mode,sourceSha,attemptId,stateDir,authorization,authority,preflight,journalApi,eas,signatureVerifier,nativeVerifier,
  submitter=submitAcceptedAab,artifactPath,sourceAuthority,expectedSignerSha256,expectedIdentity,expectedRuntime,evidence}=input;
 const base=validateBase(input);if(base)return base;
 if(typeof preflight!=='function'||!journalApi||!eas||typeof stateDir!=='string'||!path.isAbsolute(stateDir))reject('PHASE_ADAPTERS_MISSING');
 if(mode==='build-only'&&(typeof eas.startProductionBuild!=='function'||typeof eas.readBuild!=='function'||typeof eas.downloadAabForBuild!=='function'))reject('BUILD_ADAPTER_MISSING');
 if(mode==='submit-only'&&(typeof eas.readBuild!=='function'||typeof signatureVerifier!=='function'||typeof nativeVerifier!=='function'||typeof submitter!=='function'||typeof journalApi.readJournal!=='function'))reject('POSTBUILD_ADAPTER_MISSING');
 const phase=mode==='build-only'?'build':'submit';
 const pre=await preflight({phase});
 if(!pre?.ok)return {status:'BLOCKED',reason:'PREFLIGHT_FAILED',blockers:pre?.blockers||[]};
 if(pre.evidence?.preflightPhase!==phase||pre.evidence?.gitSha!==sourceSha||pre.evidence?.remoteProjectIdentityVerified!==true||
    (phase==='submit'&&pre.evidence.releaseAcceptancesVerified!==true))return blocked('PREFLIGHT_EVIDENCE_NOT_PHASE_BOUND');
 const remote=pre.evidence.remoteVersionCode;
 if(!Number.isSafeInteger(remote)||remote<1)return blocked('REMOTE_VERSION_NOT_ATTESTED');
 if(mode==='build-only'){
  if(authorization.expectedRemoteVersionCode!==remote)return blocked('REMOTE_VERSION_CHANGED');
  // The persistent intent is written BEFORE attempting any build dispatch. Never auto-retry unknown outcomes.
  const journal=runJournal(journalApi,journalApi.createJournal({stateDir,attemptId,sourceSha}));
  journal.shift('CREATED','PREFLIGHT_PASS',{remoteVersionCode:remote});
  journal.shift('PREFLIGHT_PASS','BUILD_DISPATCH_INTENT_PERSISTED');
  let build;
  try{build=await eas.startProductionBuild({sourceSha,attemptId},{...authorization,persistedIntent:true})}
  catch{return {...blocked('BUILD_DISPATCH_OUTCOME_UNKNOWN'),status:'DISPATCH_OUTCOME_UNKNOWN'}}
  if(!UUID.test(build?.id)||build.platform!=='ANDROID'||build.gitCommitHash!==sourceSha){
   journal.shift('BUILD_DISPATCH_INTENT_PERSISTED','DISPATCH_OUTCOME_UNKNOWN',{reason:'UNVERIFIABLE_BUILD_DISPATCH'});
   return {status:'DISPATCH_OUTCOME_UNKNOWN',reason:'UNVERIFIABLE_BUILD_DISPATCH'};
  }
  journal.shift('BUILD_DISPATCH_INTENT_PERSISTED','BUILD_ID_KNOWN',{buildId:build.id});
  let verified;
  try{verified=await eas.readBuild(build.id,{sourceSha})}catch{return blocked('BUILD_STATUS_UNKNOWN_NO_REDISPATCH')}
  if(verified?.id!==build.id||verified.platform!=='ANDROID'||verified.gitCommitHash!==sourceSha||verified.status!=='FINISHED')return blocked('BUILD_NOT_FINISHED_NO_PLAY_ACTION');
  const version=Number(verified.appBuildVersion);
  if(!Number.isSafeInteger(version)||version!==remote+1)return blocked('BUILD_VERSION_UNEXPECTED');
  // Retrieve and seal the real signed AAB BEFORE device acceptance. No signing tools are needed for the transfer.
  if(typeof artifactPath!=='string'||!path.isAbsolute(artifactPath)||!artifactPath.endsWith('.aab')||fs.existsSync(artifactPath))return blocked('UNSAFE_OR_EXISTING_ARTIFACT_PATH');
  try{await eas.downloadAabForBuild(build.id,artifactPath,{sourceSha})}catch{return blocked('BUILD_FINISHED_AAB_RETRIEVAL_PENDING_NO_REDISPATCH')}
  let digest;
  try{digest=fileHash(artifactPath)}catch{return blocked('DOWNLOADED_AAB_HASH_UNAVAILABLE_NO_REDISPATCH')}
  journal.shift('BUILD_ID_KNOWN','BUILD_FINISHED',{versionCode:version,artifactSha256:digest});
  return {status:'BUILD_FINISHED_AWAITING_POSTBUILD_ACCEPTANCE',buildId:build.id,sourceSha,versionCode:version,aabSha256:digest,playSubmitted:false};
 }
 // Resume a previously persisted completed build. This phase is forbidden from dispatching another build.
 let journal;
 try{journal=runJournal(journalApi,journalApi.readJournal(stateDir))}catch{return blocked('BUILD_JOURNAL_MISSING_OR_INVALID')}
 const j=journal.get();
 if(j.stage!=='BUILD_FINISHED'||j.attemptId!==attemptId||j.sourceSha!==sourceSha||!UUID.test(j.buildId)||!Number.isSafeInteger(j.versionCode)||j.versionCode<1||!SHA256.test(j.artifactSha256||''))return blocked('BUILD_JOURNAL_NOT_READY');
 if(remote!==j.versionCode||authorization.expectedBuiltVersionCode!==j.versionCode||authorization.expectedBuildId!==j.buildId)return blocked('BUILT_VERSION_OR_BUILD_ID_DRIFT');
 let build;
 try{build=await eas.readBuild(j.buildId,{sourceSha})}catch{return blocked('BUILD_METADATA_UNVERIFIABLE')}
 if(build?.id!==j.buildId||build.platform!=='ANDROID'||build.status!=='FINISHED'||build.gitCommitHash!==sourceSha||Number(build.appBuildVersion)!==j.versionCode)return blocked('BUILD_METADATA_CHANGED');
 // The AAB was already downloaded, sealed and identified in the build-only phase.
 // Never fetch or replace a different AAB during the submit-only phase.
 if(typeof artifactPath!=='string'||!path.isAbsolute(artifactPath)||!artifactPath.endsWith('.aab'))return blocked('INVALID_SEALED_ARTIFACT_PATH');
 let digest;
 try{digest=fileHash(artifactPath)}catch{return blocked('AAB_HASH_UNAVAILABLE')}
 if(digest!==j.artifactSha256)return blocked('AAB_CHANGED_SINCE_BUILD_PHASE');
 let signature,native;
 try{signature=await signatureVerifier({sealedAabPath:artifactPath,expectedSha256:digest,expectedUploadCertSha256:expectedSignerSha256})}
 catch{return blocked('CRYPTO_VERIFY_FAILED')}
 if(signature?.ok!==true||signature.sha256!==digest||signature.certSha256?.toUpperCase()!==expectedSignerSha256.toUpperCase())return blocked('CRYPTO_VERIFY_FAILED');
 try{native=await nativeVerifier({sealedAabPath:artifactPath,expectedIdentity,expectedVersionCode:j.versionCode,
  expectedAbiSet:['arm64-v8a','armeabi-v7a','x86','x86_64'],expectedRuntime,expectedSha256:digest,evidence})}
 catch{return blocked('NATIVE_VERIFY_FAILED')}
 if(native?.ok!==true||native.observed?.deviceReceiptVerified!==true||native.observed?.runtimeResourceVerified!==true)return blocked('NATIVE_OR_DEVICE_RECEIPT_NOT_VERIFIED');
 if(fileHash(artifactPath)!==digest)return blocked('AAB_CHANGED_AFTER_VERIFICATION');
 journal.shift('BUILD_FINISHED','AAB_VERIFIED',{sha256:digest});
 journal.shift('AAB_VERIFIED','SUBMIT_DISPATCH_INTENT_PERSISTED');
 const approval={...authorization,persistedIntent:true,artifactAccepted:true};
 let submission;
 try{submission=await submitter({journal:journal.get(),approval,build,sealedAabPath:artifactPath,expectedSha256:digest,
  acceptance:{signature,native,buildId:build.id,sourceSha,sha256:digest,deviceGate:'PASS'},authority,easTransport:eas,sourceAuthority})}
 catch{return {status:'DISPATCH_OUTCOME_UNKNOWN',reason:'SUBMIT_DISPATCH_UNCERTAIN'}}
 if(!UUID.test(submission?.submissionId))return {status:'DISPATCH_OUTCOME_UNKNOWN',reason:'SUBMIT_ID_UNVERIFIABLE'};
 journal.shift('SUBMIT_DISPATCH_INTENT_PERSISTED','SUBMISSION_ID_KNOWN',{submissionId:submission.submissionId});
 let finished;
 try{finished=await eas.readSubmission(submission.submissionId)}catch{return {status:'SUBMISSION_ID_KNOWN',submissionId:submission.submissionId}}
 if(finished?.id!==submission.submissionId||finished.status!=='FINISHED')return {status:'SUBMISSION_ID_KNOWN',submissionId:submission.submissionId};
 journal.shift('SUBMISSION_ID_KNOWN','SUBMIT_FINISHED');
 journal.shift('SUBMIT_FINISHED','FINAL_STATUS_VERIFIED');
 return {status:'FINAL_STATUS_VERIFIED',buildId:build.id,submissionId:submission.submissionId,sourceSha,versionCode:j.versionCode,aabSha256:digest};
}
