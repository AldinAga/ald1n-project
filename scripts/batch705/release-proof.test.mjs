import test from 'node:test';
import assert from 'node:assert/strict';
import {mkdtempSync,mkdirSync,writeFileSync,symlinkSync,rmSync} from 'node:fs';
import {tmpdir} from 'node:os';
import {join} from 'node:path';
import {inspectStage,assessReview,evaluateReleaseGate,buildManifest} from './release-proof.mjs';

function stage(fn) {
  const root=mkdtempSync(join(tmpdir(),'ald1n-stage-test-'));
  for (const p of ['apps/cms/current','apps/mobile/current','packages/api-contract']) mkdirSync(join(root,p),{recursive:true});
  for(const p of ['apps/cms/current/artisan','apps/mobile/current/package.json','packages/api-contract/openapi.yaml']) writeFileSync(join(root,p),'fixture');
  try{return fn(root);}finally{rmSync(root,{recursive:true,force:true});}
}
const SHA='a'.repeat(40),TREE='b'.repeat(40),HASH='c'.repeat(64);

test('safe scoped stage passes and counts source files',()=>stage(root=>{
  const result=inspectStage(root);
  assert.equal(result.ok,true);
  assert.equal(result.count,3);
  assert.equal(result.bytes,21);
}));
test('rejects stage without canonical Laravel and Mobile roots',()=>stage(root=>{
  rmSync(join(root,'apps/cms/current/artisan'));
  assert.throws(()=>inspectStage(root),/required file/);
}));
test('refuses the protected production .htaccess in a stage',()=>stage(root=>{
  mkdirSync(join(root,'apps/cms/current/public'),{recursive:true});
  writeFileSync(join(root,'apps/cms/current/public/.htaccess'),'unsafe');
  assert.throws(()=>inspectStage(root),/protected/i);
}));
test('rejects tracked .env and private signing keys',()=>stage(root=>{
  writeFileSync(join(root,'apps/cms/current/.env'),'secret');
  assert.throws(()=>inspectStage(root),/sensitive/i);
}));
test('rejects symlinks escaping or pointing within stage, never follows them',()=>stage(root=>{
  symlinkSync('/etc/passwd',join(root,'apps/cms/current/link'));
  assert.throws(()=>inspectStage(root),/symlink/i);
}));
test('rejects other unauthorized top-level staged paths',()=>stage(root=>{
  mkdirSync(join(root,'docs'),{recursive:true});
  writeFileSync(join(root,'docs/private.txt'),'x');
  assert.throws(()=>inspectStage(root),/scope/i);
}));
test('independent current-head APPROVED review counts, self-approval does not',()=>{
  const other=[{state:'APPROVED',author:'reviewer1',commit_id:SHA}];
  assert.equal(assessReview(SHA,'AldinAga',other).ok,true);
  assert.equal(assessReview(SHA,'AldinAga',[{...other[0],author:'AldinAga'}]).ok,false);
});
test('stale approval and comment-only review never satisfy gate',()=>{
  assert.equal(assessReview(SHA,'AldinAga',[{state:'APPROVED',author:'reviewer1',commit_id:TREE}]).ok,false);
  assert.equal(assessReview(SHA,'AldinAga',[{state:'COMMENTED',author:'reviewer1',commit_id:SHA}]).ok,false);
});
test('valid staged package and QA PASS still cannot authorize release without operator evidence',()=>{
  const gate=evaluateReleaseGate({qa:'pass',staging:true,reviews:true,backup:false,restore:false,mdl:false,host:false});
  assert.equal(gate.authorized,false);
  assert.match(gate.blockers.join(' '),/restore/i);
  assert.match(gate.blockers.join(' '),/MDL/i);
});
test('fail-closed even when all Boolean evidence is supplied',()=>{
  const gate=evaluateReleaseGate({qa:'pass',staging:true,reviews:true,backup:true,restore:true,mdl:true,host:true});
  assert.equal(gate.authorized,false);
  assert.match(gate.blockers.join(' '),/production deployment adapter/i);
});
test('any FAIL QA remains blocking',()=>{
  const gate=evaluateReleaseGate({qa:'fail',staging:true,reviews:true,backup:true,restore:true,mdl:true,host:true});
  assert.equal(gate.authorized,false);
  assert.match(gate.blockers.join(' '),/QA/i);
});
test('manifest binds exact 40-character commit, tree, SHA256 and stage metadata',()=>stage(root=>{
  const obj=buildManifest({sha:SHA,tree:TREE,archiveSha:HASH,archiveSize:100,stage:inspectStage(root)});
  assert.equal(obj.source.sha,SHA);
  assert.equal(obj.archive.sha256,HASH);
  assert.equal(obj.archive.deployable,false);
  assert.equal(obj.stage.file_count,3);
}));
test('manifest refuses malformed source and archive identities',()=>stage(root=>{
  const s=inspectStage(root);
  assert.throws(()=>buildManifest({sha:'main',tree:TREE,archiveSha:HASH,archiveSize:100,stage:s}),/SHA/i);
  assert.throws(()=>buildManifest({sha:SHA,tree:TREE,archiveSha:'0',archiveSize:100,stage:s}),/SHA/i);
}));
