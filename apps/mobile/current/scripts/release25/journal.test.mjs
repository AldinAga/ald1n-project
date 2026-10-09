import test from 'node:test';
import assert from 'node:assert/strict';
import os from 'node:os';
import fs from 'node:fs';
import path from 'node:path';
import {spawnSync,spawn} from 'node:child_process';
import {createJournal,readJournal,transitionJournal} from './journal.mjs';
function stateDir(t){ const p=fs.mkdtempSync(path.join(os.tmpdir(),'ald1n25-journal-'));t.after(()=>fs.rmSync(p,{recursive:true,force:true}));return path.join(p,'state') }
const sha='a'.repeat(40);
test('journal_created_private',t=>{
  const p=stateDir(t); const j=createJournal({stateDir:p,attemptId:'attempt-1',sourceSha:sha});
  assert.equal(j.stage,'CREATED');
  assert.equal(readJournal(p).sourceSha,sha);
  assert.equal(fs.statSync(p).mode & 0o777,0o700);
  assert.equal(fs.statSync(path.join(p,'journal.json')).mode&0o777,0o600);
});
test('cannot_overwrite_existing_release',t=>{
  const p=stateDir(t);createJournal({stateDir:p,attemptId:'attempt-1',sourceSha:sha});
  assert.throws(()=>createJournal({stateDir:p,attemptId:'attempt-2',sourceSha:sha}),/ALREADY_EXISTS/);
});
test('journal_enforces_expected_old_stage',t=>{
  const p=stateDir(t);const j=createJournal({stateDir:p,attemptId:'attempt-1',sourceSha:sha});
  transitionJournal(j,'CREATED','PREFLIGHT_PASS',{remoteVersionCode:24});
  assert.equal(readJournal(p).stage,'PREFLIGHT_PASS');
  assert.throws(()=>transitionJournal(j,'CREATED','PREFLIGHT_PASS',{}),/STAGE_MISMATCH/);
});
test('orphan_intent_does_not_retry',t=>{
  const p=stateDir(t);const j=createJournal({stateDir:p,attemptId:'attempt-1',sourceSha:sha});
  const pre=transitionJournal(j,'CREATED','PREFLIGHT_PASS',{});
  transitionJournal(pre,'PREFLIGHT_PASS','BUILD_DISPATCH_INTENT_PERSISTED',{});
  const orphan=readJournal(p);
  assert.equal(orphan.stage,'BUILD_DISPATCH_INTENT_PERSISTED');
  assert.throws(()=>transitionJournal(j,'BUILD_DISPATCH_INTENT_PERSISTED','BUILD_DISPATCH_INTENT_PERSISTED',{}),/INVALID_TRANSITION/);
});
test('invalid_transition_rejected',t=>{
 const p=stateDir(t);const j=createJournal({stateDir:p,attemptId:'attempt-1',sourceSha:sha});
 assert.throws(()=>transitionJournal(j,'CREATED','AAB_VERIFIED',{}),/INVALID_TRANSITION/);
 assert.throws(()=>transitionJournal(j,'CREATED','SUBMIT_DISPATCH_INTENT_PERSISTED',{}),/INVALID_TRANSITION/);
});
test('same_state_after_invalid_patch',t=>{
 const p=stateDir(t);const j=createJournal({stateDir:p,attemptId:'attempt-1',sourceSha:sha});
 assert.throws(()=>transitionJournal(j,'CREATED','PREFLIGHT_PASS',{sourceSha:'b'.repeat(40)}),/IMMUTABLE_FIELD/);
 assert.equal(readJournal(p).sourceSha,sha);
});
test('denies_symlink_state_dir',t=>{
  const root=stateDir(t);const real=path.join(path.dirname(root),'real');fs.mkdirSync(real);fs.symlinkSync(real,root);
  assert.throws(()=>createJournal({stateDir:root,attemptId:'attempt-1',sourceSha:sha}),/SYMLINK/);
});
test('flock_second_process_fails_and_does_not_delete_lock',t=>{
 const p=stateDir(t);fs.mkdirSync(p,{recursive:true});const lock=path.join(p,'release.lock');
 const script='exec 9>"$1"; flock -n -x 9 || exit 73; echo LOCKED; sleep 3';
 const child=spawn('bash',['-c',script,'sh',lock],{stdio:['ignore','pipe','pipe']});
 t.after(()=>child.kill());
 return new Promise((resolve,reject)=>{
  child.on('error',reject);
  child.stdout.once('data',chunk=>{
   try{assert.match(chunk.toString(),/LOCKED/);
   const second=spawnSync('flock',['-n','-x',lock,'true'],{timeout:2000});
   assert.notEqual(second.status,0);assert.equal(fs.existsSync(lock),true);resolve();
   }catch(e){reject(e)}finally{child.kill()}
  });
 });
});
