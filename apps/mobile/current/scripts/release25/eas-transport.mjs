import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { pipeline } from 'node:stream/promises';
import { Readable,Transform } from 'node:stream';
const UUID=/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;
const SHA=/^[0-9a-f]{40}$/i;
const MAX_AAB_BYTES=1_073_741_824;
const failure=(code)=>new Error(code);
const isFinished=(b)=>b?.status==='FINISHED'&&b?.platform==='ANDROID';
export function createEasTransport({nodeBin,npmCli,easVersion,mobileRoot,spawn=spawnSync,fetchArtifact=fetch}){
 if(typeof easVersion!=='string'||!/^\d+\.\d+\.\d+$/.test(easVersion))throw failure('INVALID_CLI_PIN');
 if(!nodeBin||!npmCli||!path.isAbsolute(nodeBin)||!path.isAbsolute(npmCli)||!path.isAbsolute(mobileRoot))throw failure('INVALID_CANONICAL_EAS_PATHS');
 const cli=(args,{mutation=false,textOutput=false}={})=>{
  if(args.includes('--latest')||args.includes('--auto-submit')||args.includes('--auto-submit-with-profile'))throw failure('FORBIDDEN_CLI_ARG');
  const all=[npmCli,'exec','--yes',`--package=eas-cli@${easVersion}`,'--','eas',...args];
  const result=spawn(nodeBin,all,{cwd:mobileRoot,encoding:'utf8',maxBuffer:64*1024*1024,timeout:mutation?3*60*60*1000:3*60*1000,env:{...process.env,EXPO_PUBLIC_APP_ENV:'production',EXPO_PUBLIC_API_URL:'https://cms.ald1n.com/api/v1'}});
  if(result?.error||result?.status!==0)throw failure(mutation?'DISPATCH_OUTCOME_UNKNOWN':'EAS_READ_FAILED');
  if(textOutput)return result.stdout;
  try {return JSON.parse(result.stdout)}catch {throw failure(mutation?'DISPATCH_OUTCOME_UNKNOWN':'INVALID_EAS_JSON')}
 };
 const requireApproval=(approval,sourceSha,attemptId)=>{
  if(approval?.authorized!==true||approval?.productionWriteEnabled!==true||approval?.persistedIntent!==true||approval?.lockHeld!==true||!SHA.test(sourceSha)||approval.sourceSha!==sourceSha||!attemptId||approval.attemptId!==attemptId)throw failure('BUILD_NOT_AUTHORIZED');
 };
 return Object.freeze({
  async readRemoteProjectIdentity(){
   // Pinned eas-cli@24.7.0 project:info prints only fullName + ID, never JSON.
   // The independently read project identity MUST NOT be inferred from version:get.
   const output=cli(['project:info'],{textOutput:true});
   if(typeof output!=='string'||output.length>4096)throw failure('EAS_PROJECT_SCHEMA_UNKNOWN');
   const lines=output.replace(/\x1b\[[0-9;]*m/g,'').trim().split(/\r?\n/).map(x=>x.trim()).filter(Boolean);
   if(lines.length!==2)throw failure('EAS_PROJECT_SCHEMA_UNKNOWN');
   const full=lines[0].match(/^fullName[ \t]{2,}@([a-z0-9._-]+)\/([a-z0-9._-]+)$/);
   const id=lines[1].match(/^ID[ \t]{2,}([0-9a-f-]{36})$/i);
   if(!full||!id||!UUID.test(id[1]))throw failure('EAS_PROJECT_SCHEMA_UNKNOWN');
   return {owner:full[1],slug:full[2],projectId:id[1]};
  },
  async readRemoteVersion(){
   const data=cli(['build:version:get','--platform','android','--profile','production','--json']);
   // eas-cli@24.7.0 emits Android versionCode as a decimal string, not an integer.
   // Normalize only strict canonical decimal values; never invent remote project identity fields.
   const raw=data?.versionCode;
   const value=(typeof raw==='string' && /^[1-9][0-9]{0,9}$/.test(raw))?Number(raw):raw;
   if(!Number.isSafeInteger(value)||value<1||value>2100000000)throw failure('EAS_VERSION_SCHEMA_UNKNOWN');
   return {...data,versionCode:value};
  },
  async readBuild(buildId,expected={}){
   if(!UUID.test(buildId))throw failure('INVALID_BUILD_ID');
   const b=cli(['build:view',buildId,'--json']);
   if(!b||b.id!==buildId||b.platform!=='ANDROID'||!['FINISHED','ERRORED','IN_PROGRESS','IN_QUEUE','CANCELED','NEW'].includes(b.status))throw failure('BUILD_METADATA_MISMATCH');
   if(expected.sourceSha&&b.gitCommitHash!==expected.sourceSha)throw failure('SOURCE_SHA_MISMATCH');
   return b;
  },
  async listBuildsForReconciliation(){
    const data=cli(['build:list','--platform','android','--profile','production','--non-interactive','--json']);
    if(!Array.isArray(data))throw failure('INVALID_BUILD_LIST');return data;
  },
  async startProductionBuild(intent,approval){
   requireApproval(approval,intent?.sourceSha,intent?.attemptId);
   const data=cli(['build','--platform','android','--profile','production','--non-interactive','--wait','--json'],{mutation:true});
   const b=Array.isArray(data)?(data.length===1?data[0]:null):data;
   if(!b||!UUID.test(b.id)||b.platform!=='ANDROID'||b.gitCommitHash!==intent.sourceSha)throw failure('DISPATCH_OUTCOME_UNKNOWN');
   return b;
  },
  async downloadAabForBuild(buildId,target,expected={}){
   const b=await this.readBuild(buildId,expected);
   if(!isFinished(b)||typeof b.artifacts?.buildUrl!=='string')throw failure('BUILD_NOT_FINISHED');
   const url=new URL(b.artifacts.buildUrl);
   if(url.protocol!=='https:')throw failure('ARTIFACT_NOT_HTTPS');
   const parent=path.dirname(target);const safeBase=path.basename(target);
   if(!safeBase.endsWith('.aab')||!fs.statSync(parent).isDirectory()||fs.statSync(parent).mode&0o077)throw failure('INSECURE_ARTIFACT_DIRECTORY');
   const tmp=target+'.downloading';
   if(fs.existsSync(target)||fs.existsSync(tmp)||(()=>{try{fs.lstatSync(tmp);return true}catch{return false}})())throw failure('ARTIFACT_ALREADY_EXISTS');
   let fd,ownedTmp=false;
   try{
    fd=fs.openSync(tmp,fs.constants.O_EXCL|fs.constants.O_CREAT|fs.constants.O_WRONLY|fs.constants.O_NOFOLLOW,0o600);ownedTmp=true;
    const response=await fetchArtifact(url.href);
    if(!response?.ok||!response.body)throw failure('ARTIFACT_DOWNLOAD_FAILED');
    let total=0;
    const limiter=new Transform({transform(chunk,enc,cb){total+=chunk.length;cb(total>MAX_AAB_BYTES?failure('ARTIFACT_TOO_LARGE'):null,chunk)}});
    await pipeline(Readable.fromWeb(response.body),limiter,fs.createWriteStream(tmp,{fd,autoClose:false}));
    if(!total)throw failure('EMPTY_AAB');
    fs.fsyncSync(fd);fs.closeSync(fd);fd=undefined;
    fs.chmodSync(tmp,0o400);
    fs.linkSync(tmp,target);fs.unlinkSync(tmp);ownedTmp=false;
    return {path:target,buildId,bytes:total};
   }catch(e){if(fd!==undefined){try{fs.closeSync(fd)}catch{}}if(ownedTmp){try{fs.unlinkSync(tmp)}catch{}}throw e}
  },
  async readSubmission(submissionId){
   if(!UUID.test(submissionId))throw failure('INVALID_SUBMISSION_ID');
   const d=cli(['submit:view',submissionId,'--json']);
   if(d?.id!==submissionId)throw failure('SUBMISSION_ID_MISMATCH');return d;
  },
  async submitVerifiedPath({approval,build,artifactPath}){
   if(!approval?.artifactAccepted||!approval.persistedIntent||!approval.lockHeld||!approval.productionWriteEnabled||!approval.authorized||!isFinished(build)||build.gitCommitHash!==approval.sourceSha)throw failure('BUILD_NOT_FINISHED');
   if(!UUID.test(build.id)||typeof artifactPath!=='string'||!path.isAbsolute(artifactPath)||!artifactPath.endsWith('.aab'))throw failure('UNSAFE_SUBMIT_SOURCE');
   const data=cli(['submit','--platform','android','--profile','production','--path',artifactPath,'--non-interactive','--wait','--json'],{mutation:true});
   if(!UUID.test(data?.id))throw failure('DISPATCH_OUTCOME_UNKNOWN');
   return data;
  }
 });
}
