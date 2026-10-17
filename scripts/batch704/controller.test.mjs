import test from 'node:test';
import assert from 'node:assert/strict';
import {
  EXPECTED_QA,
  assessQaRuns,
  assessReleaseAuthority,
  makeRepairIssue,
  reportNumber,
  releaseReceipt,
} from './controller.mjs';

const sha = 'c'.repeat(40);
const branch = 'audit/batch704-release-orchestrator-20261017';
function mock(spec, i, overrides={}) {
  return {
    id: 800+i, head_sha: sha, head_branch: branch, event: 'push',
    path: `.github/workflows/${spec.file}@${branch}`,
    status: 'completed', conclusion: 'success', ...overrides
  };
}
const runs = EXPECTED_QA.map(mock);
const jobs = Object.fromEntries(EXPECTED_QA.map((spec,i) => [String(800+i),
  spec.names.map((name,index)=>({id:1000+i*10+index,name,status:'completed',conclusion:'success'}))]));

test('all 14 required jobs for exact SHA/branch are PASS',()=>{
  const result=assessQaRuns(runs,jobs,sha,branch);
  assert.equal(result.status,'pass');
  assert.equal(result.jobs,14);
  assert.equal(result.runs.length,3);
});
test('missing workflow remains pending and never PASS',()=>{
  assert.equal(assessQaRuns(runs.slice(0,2),jobs,sha,branch).status,'pending');
});
test('other SHA or branch is not counted',()=>{
  const impostor=[...runs.slice(0,2),mock(EXPECTED_QA[2],2,{head_sha:'d'.repeat(40)})];
  assert.equal(assessQaRuns(impostor,jobs,sha,branch).status,'pending');
});
test('failed workflow becomes failure with matching run information',()=>{
  const changed=[...runs]; changed[1]=mock(EXPECTED_QA[1],1,{conclusion:'failure'});
  const result=assessQaRuns(changed,jobs,sha,branch);
  assert.equal(result.status,'fail');
  assert.match(result.failures.join(' '),/issue19-enum-safety-audit/);
});
test('a green workflow with skipped matrix job cannot pass',()=>{
  const clone=structuredClone(jobs);clone['800'][2].conclusion='skipped';
  const result=assessQaRuns(runs,clone,sha,branch);
  assert.equal(result.status,'fail');
  assert.match(result.failures.join(' '),/catalog-inventory/);
});
test('unknown extra job and duplicated run both fail closed',()=>{
  const clone=structuredClone(jobs);clone['802'].push({id:193,name:'unapproved job',conclusion:'success'});
  assert.equal(assessQaRuns(runs,clone,sha,branch).status,'fail');
  assert.equal(assessQaRuns([...runs,runs[0]],jobs,sha,branch).status,'fail');
});
test('an in-progress success candidate never passes prematurely',()=>{
  const r=[...runs];r[2]=mock(EXPECTED_QA[2],2,{status:'in_progress',conclusion:null});
  assert.equal(assessQaRuns(r,jobs,sha,branch).status,'pending');
});
test('missing jobs and wrong job count remain pending or fail closed',()=>{
  assert.equal(assessQaRuns(runs,{},sha,branch).status,'pending');
  const clone=structuredClone(jobs);clone['801'].pop();
  assert.equal(assessQaRuns(runs,clone,sha,branch).status,'fail');
});
test('PASS QA does not authorize production without independent evidence',()=>{
  const gates=assessReleaseAuthority({qaResult:'pass',prDrafts:[true,true,true,true],
    independentReview:false,restoreProof:false,backupFresh:false,
    mdlVisibility:false,deploymentAdapter:false,hostClean:false});
  assert.equal(gates.authorized,false);
  assert.ok(gates.blockers.length>=6);
});
test('FAIL QA always prevents release even when attestations claim PASS',()=>{
  const gates=assessReleaseAuthority({qaResult:'fail',prDrafts:[false,false,false,false],
    independentReview:true,restoreProof:true,backupFresh:true,
    mdlVisibility:true,deploymentAdapter:true,hostClean:true});
  assert.equal(gates.authorized,false);
});
test('repair issue carries provenance but no secrets or fabricated fix',()=>{
  const issue=makeRepairIssue({sha,branch,failures:['issue19-enum-safety-audit.yml: failed'],
    runs:[{name:'enum',id:123,conclusion:'failure'}]});
  assert.match(issue.title,/Batch705/);
  assert.match(issue.body,/\/actions\/runs\/123/);
  assert.match(issue.body,/RED reproduction/);
  assert.doesNotMatch(issue.body,/auto[- ]deploy now/i);
});
test('report number obeys highest numeric prefix and never reuses 703',()=>{
  assert.equal(reportNumber(['703-a.md','646-b.md','000-LATEST.md']),704);
  assert.equal(reportNumber(['703-a.md','704-c.md']),705);
});

test('production HOLD receipt retains actual verified job count',()=>{
  const value=releaseReceipt({status:'pass',jobs:14,runs:[{file:'x',id:7}]},'c'.repeat(40),'audit/batch704-release-orchestrator-20261017',['review HOLD']);
  assert.equal(value.jobs,14);
  assert.equal(value.status,'pass');
  assert.deepEqual(value.blockers,['review HOLD']);
});
