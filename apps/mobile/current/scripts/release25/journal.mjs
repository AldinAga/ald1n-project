import fs from 'node:fs';
import path from 'node:path';
import { randomUUID } from 'node:crypto';

const EDGES = Object.freeze({
 CREATED:['PREFLIGHT_PASS','BLOCKED'],
 PREFLIGHT_PASS:['BUILD_DISPATCH_INTENT_PERSISTED','BLOCKED'],
 BUILD_DISPATCH_INTENT_PERSISTED:['BUILD_ID_KNOWN','DISPATCH_OUTCOME_UNKNOWN','BLOCKED'],
 BUILD_ID_KNOWN:['BUILD_FINISHED','BLOCKED'],
 BUILD_FINISHED:['AAB_VERIFIED','BLOCKED'],
 AAB_VERIFIED:['SUBMIT_DISPATCH_INTENT_PERSISTED','BLOCKED'],
 SUBMIT_DISPATCH_INTENT_PERSISTED:['SUBMISSION_ID_KNOWN','DISPATCH_OUTCOME_UNKNOWN','BLOCKED'],
 SUBMISSION_ID_KNOWN:['SUBMIT_FINISHED','BLOCKED'],
 SUBMIT_FINISHED:['FINAL_STATUS_VERIFIED','BLOCKED'],
 FINAL_STATUS_VERIFIED:[], DISPATCH_OUTCOME_UNKNOWN:['BLOCKED'], BLOCKED:[]
});
const SHA_RE = /^[a-f0-9]{40}$/i;
const FORBIDDEN = /(?:password|token|secret|private|credential|url|cookie|authorization|keyfile)/i;
function assertDir(stateDir){
 if(typeof stateDir!=='string'||!path.isAbsolute(stateDir)) throw new Error('STATE_DIR_NOT_ABSOLUTE');
 if(fs.existsSync(stateDir) && fs.lstatSync(stateDir).isSymbolicLink()) throw new Error('SYMLINK_STATE_DIR');
 fs.mkdirSync(stateDir,{recursive:true,mode:0o700});
 if(fs.lstatSync(stateDir).isSymbolicLink()) throw new Error('SYMLINK_STATE_DIR');
 const mode=fs.statSync(stateDir).mode & 0o777;
 if(mode!==0o700) throw new Error('STATE_DIR_NOT_PRIVATE');
}
function journalFile(stateDir){return path.join(stateDir,'journal.json')}
function fsyncDir(dir){const fd=fs.openSync(dir,fs.constants.O_RDONLY);try{fs.fsyncSync(fd)}finally{fs.closeSync(fd)}}
function writeAtomic(stateDir,object,initial){
 const target=journalFile(stateDir);
 if(fs.existsSync(target)&&fs.lstatSync(target).isSymbolicLink())throw new Error('SYMLINK_JOURNAL');
 const tmp=path.join(stateDir,`.journal.${randomUUID()}.tmp`);
 let fd;
 try{
  fd=fs.openSync(tmp,fs.constants.O_WRONLY|fs.constants.O_CREAT|fs.constants.O_EXCL,0o600);
  fs.writeFileSync(fd,JSON.stringify(object,null,2)+'\n','utf8');fs.fsyncSync(fd);fs.closeSync(fd);fd=undefined;
  if(initial){
   // link() fails with EEXIST rather than clobbering an existing journal.
   fs.linkSync(tmp,target);fs.unlinkSync(tmp);
  } else {fs.renameSync(tmp,target)}
  fsyncDir(stateDir);
 }catch(e){if(fd!==undefined)fs.closeSync(fd);try{fs.unlinkSync(tmp)}catch{};if(e.code==='EEXIST')throw new Error('ALREADY_EXISTS');throw e;}
}
export function createJournal({stateDir,attemptId,sourceSha}){
 assertDir(stateDir);
 if(typeof attemptId!=='string'||!/^[a-zA-Z0-9][a-zA-Z0-9._-]{3,100}$/.test(attemptId))throw new Error('INVALID_ATTEMPT_ID');
 if(!SHA_RE.test(sourceSha))throw new Error('INVALID_SOURCE_SHA');
 if(fs.existsSync(journalFile(stateDir)))throw new Error('ALREADY_EXISTS');
 const journal={attemptId,sourceSha,stage:'CREATED',sequence:0,createdAt:new Date().toISOString(),updatedAt:new Date().toISOString()};
 writeAtomic(stateDir,journal,true);
 return Object.freeze({...journal,stateDir});
}
export function readJournal(stateDir){
 const filename=journalFile(stateDir);
 if(fs.lstatSync(filename).isSymbolicLink())throw new Error('SYMLINK_JOURNAL');
 const data=JSON.parse(fs.readFileSync(filename,'utf8'));
 if(!data||!EDGES[data.stage]||!SHA_RE.test(data.sourceSha)||!Number.isSafeInteger(data.sequence))throw new Error('CORRUPT_JOURNAL');
 return Object.freeze({...data,stateDir});
}
export function transitionJournal(journal,expectedStage,nextStage,patch={}){
 if(!journal?.stateDir||!EDGES[expectedStage]||!EDGES[nextStage])throw new Error('INVALID_TRANSITION');
 if(!EDGES[expectedStage].includes(nextStage))throw new Error('INVALID_TRANSITION');
 const disk=readJournal(journal.stateDir);
 if(disk.stage!==expectedStage||disk.sequence!==journal.sequence)throw new Error('STAGE_MISMATCH');
 if(disk.attemptId!==journal.attemptId||disk.sourceSha!==journal.sourceSha)throw new Error('IMMUTABLE_FIELD');
 if(patch===null||typeof patch!=='object'||Array.isArray(patch))throw new Error('INVALID_PATCH');
 for(const key of Object.keys(patch)){
  if(key==='stage'||key==='sequence'||key==='createdAt'||key==='updatedAt'||key==='attemptId'||key==='sourceSha'||key==='stateDir')throw new Error('IMMUTABLE_FIELD');
  if(FORBIDDEN.test(key)||key==='__proto__'||key==='constructor'||key==='prototype')throw new Error('SENSITIVE_FIELD');
  if(typeof patch[key]==='string'&&/(?:https:\/\/.*(?:token|signature|expires)=)/i.test(patch[key]))throw new Error('SENSITIVE_FIELD');
 }
 const next={...disk,...patch,stage:nextStage,sequence:disk.sequence+1,updatedAt:new Date().toISOString()};
 delete next.stateDir;
 writeAtomic(journal.stateDir,next,false);
 return Object.freeze({...next,stateDir:journal.stateDir});
}
