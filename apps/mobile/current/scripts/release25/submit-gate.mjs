import fs from 'node:fs';
import path from 'node:path';
import {createHash} from 'node:crypto';
const UUID=/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
const SHA=/^[0-9a-f]{64}$/i;
const blocked=code=>new Error(code);
function hashSealed(file){
 if(typeof file!=='string'||!path.isAbsolute(file)||!file.endsWith('.aab'))throw blocked('AAB_NOT_SEALED');
 const ls=fs.lstatSync(file),parent=fs.statSync(path.dirname(file));
 if(ls.isSymbolicLink()||!ls.isFile()||ls.nlink!==1||ls.size<100||(ls.mode&0o377)!==0||(parent.mode&0o077)!==0)throw blocked('AAB_NOT_SEALED');
 const fd=fs.openSync(file,fs.constants.O_RDONLY|fs.constants.O_NOFOLLOW);
 try{
  const st=fs.fstatSync(fd);if(st.dev!==ls.dev||st.ino!==ls.ino)throw blocked('AAB_CHANGED');
  const h=createHash('sha256');const buf=Buffer.allocUnsafe(1024*1024);let pos=0;
  for(;;){const n=fs.readSync(fd,buf,0,buf.length,pos);if(!n)break;h.update(buf.subarray(0,n));pos+=n}
  const after=fs.lstatSync(file);if(after.dev!==st.dev||after.ino!==st.ino||after.size!==st.size||after.mtimeMs!==st.mtimeMs)throw blocked('AAB_CHANGED');
  return h.digest('hex');
 }finally{fs.closeSync(fd)}
}
export async function submitAcceptedAab({journal,approval,build,sealedAabPath,expectedSha256,acceptance,authority,easTransport,sourceAuthority}){
 if(journal?.stage!=='SUBMIT_DISPATCH_INTENT_PERSISTED'||!approval?.authorized||!approval?.persistedIntent||!approval?.lockHeld||!approval?.productionWriteEnabled||!approval?.artifactAccepted||approval.attemptId!==journal.attemptId||approval.sourceSha!==journal.sourceSha)throw blocked('SUBMIT_NOT_AUTHORIZED');
 if(!UUID.test(build?.id)||build.status!=='FINISHED'||build.platform!=='ANDROID'||build.id!==journal.buildId||build.gitCommitHash!==journal.sourceSha||!SHA.test(expectedSha256)||journal.sha256!==expectedSha256)throw blocked('BUILD_OR_HASH_MISMATCH');
 if(acceptance?.signature?.ok!==true||acceptance?.signature?.sha256!==expectedSha256||acceptance?.native?.ok!==true||acceptance.buildId!==build.id||acceptance.sourceSha!==journal.sourceSha||acceptance.sha256!==expectedSha256||acceptance.deviceGate!=='PASS')throw blocked('ACCEPTANCE_NOT_PASS');
 if(authority?.ok!==true||authority.identity?.profile!=='production'||authority.identity?.channel!=='production'||authority.identity?.track!=='production'||authority.identity?.releaseStatus!=='completed')throw blocked('SUBMIT_PROFILE_CHANGED');
 const actualHash=hashSealed(sealedAabPath);
 if(actualHash!==expectedSha256)throw blocked('AAB_CHANGED');
 const source=await sourceAuthority.snapshot();
 if(source.head!==journal.sourceSha||source.remoteMain!==journal.sourceSha||source.profile!=='production'||source.channel!=='production'||source.track!=='production')throw blocked('SOURCE_AUTHORITY_CHANGED');
 if(hashSealed(sealedAabPath)!==expectedSha256)throw blocked('AAB_CHANGED');
 // Only this method may call the EAS mutating transport; no retry on unknown result.
 const submitted=await easTransport.submitVerifiedPath({approval,build,artifactPath:sealedAabPath});
 if(!UUID.test(submitted?.id))throw blocked('DISPATCH_OUTCOME_UNKNOWN');
 return {status:'SUBMITTED',submissionId:submitted.id,buildId:build.id,sha256:actualHash};
}
