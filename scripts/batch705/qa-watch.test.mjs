import test from 'node:test';
import assert from 'node:assert/strict';
import {EXPECTED_QA} from '../batch704/controller.mjs';
import {classifyExactRuns,makeRepairTicket,gateReceipt} from './qa-watch.mjs';
const SHA='d'.repeat(40),BRANCH='audit/batch705-release-readiness-20261017';
function fixtures(){
 const runs=EXPECTED_QA.map((s,i)=>({id:100+i,head_sha:SHA,head_branch:BRANCH,event:'push',path:`.github/workflows/${s.file}@${BRANCH}`,status:'completed',conclusion:'success'}));
 const jobs=Object.fromEntries(EXPECTED_QA.map((s,i)=>[String(100+i),s.names.map(name=>({name,status:'completed',conclusion:'success'}))]));
 return {runs,jobs};
}
test('counts all 14 exact-source QA jobs for this branch',()=>{
 const {runs,jobs}=fixtures();const a=classifyExactRuns(runs,jobs,SHA,BRANCH);
 assert.equal(a.status,'pass');assert.equal(a.jobs,14);
});
test('rejects previous Batch704 commit even when all its jobs passed',()=>{
 const {runs,jobs}=fixtures();runs[0].head_sha='e'.repeat(40);
 assert.equal(classifyExactRuns(runs,jobs,SHA,BRANCH).status,'pending');
});
test('missing workflow is pending, failed workflow is fail',()=>{
 const {runs,jobs}=fixtures();assert.equal(classifyExactRuns(runs.slice(1),jobs,SHA,BRANCH).status,'pending');
 runs[0].conclusion='failure';assert.equal(classifyExactRuns(runs,jobs,SHA,BRANCH).status,'fail');
});
test('skipped job or duplicate workflow cannot pass',()=>{
 const {runs,jobs}=fixtures();jobs['100'][2].conclusion='skipped';
 assert.equal(classifyExactRuns(runs,jobs,SHA,BRANCH).status,'fail');
 const clean=fixtures();assert.equal(classifyExactRuns([...clean.runs,clean.runs[1]],clean.jobs,SHA,BRANCH).status,'fail');
});
test('repair intake binds SHA and failed CI URL, not a fabricated source fix',()=>{
 const result=makeRepairTicket({sha:SHA,branch:BRANCH,failures:['MariaDB regression'],runs:[{id:1337,file:'mariadb',conclusion:'failure'}]});
 assert.match(result.title,/Batch706/);assert.match(result.body,/actions\/runs\/1337/);
 assert.match(result.body,/RED/);assert.match(result.body,/PRODUCTION_DEPLOYED=NO/);
});
test('release receipt distinguishes verified QA from manual production HOLD',()=>{
 const r=gateReceipt({status:'pass',jobs:14}, {ok:true,count:123}, ['review missing']);
 assert.equal(r.qa_verified_jobs,14);assert.equal(r.production_authorized,false);
 assert.deepEqual(r.blockers,['review missing']);
});
