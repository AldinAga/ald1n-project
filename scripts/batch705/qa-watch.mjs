import fs from 'node:fs';
import {EXPECTED_QA,assessQaRuns} from '../batch704/controller.mjs';

const REPO='AldinAga/ald1n-project';
const BRANCH='audit/batch705-release-readiness-20261017';
const SHA_RE=/^[a-f0-9]{40}$/;

export function classifyExactRuns(runs,jobsByRun,sha,branch) {
  return assessQaRuns(runs,jobsByRun,sha,branch);
}

export function makeRepairTicket({sha,branch,failures,runs}) {
  if(!SHA_RE.test(sha)||branch!==BRANCH) throw new Error('Wrong repair authority');
  return {
    title:`Batch706 repair intake for QA ${sha.slice(0,12)}`,
    body:`## Batch706: exact-source failed QA intake - NO deploy\n\nSOURCE_SHA=${sha}\nSOURCE_BRANCH=${branch}\nPRODUCTION_DEPLOYED=NO\n\n### Actual failures\n${(failures||[]).map(s=>`- ${String(s).slice(0,240)}`).join('\n')||'- QA unavailable or timed out'}\n\n### CI provenance\n${(runs||[]).map(r=>`- ${r.file||r.name}: https://github.com/${REPO}/actions/runs/${r.id} (${r.conclusion||r.status})`).join('\n')}\n\n### Required next steps\n1. Read exact failed job logs and identify the root cause.\n2. Create a failing regression test and demonstrate RED.\n3. Apply a minimal isolated code fix, then show GREEN and all 14 QA jobs PASS.\n4. Preserve all independent release gates; no production mutation.\n\nThis ticket is not a code repair and does not authorize rollout.\n`,
  };
}

export function gateReceipt(qa,stage,blockers) {
  return {
    source:'Batch705 exact-source release preview',
    qa_result:qa?.status||'missing',
    qa_verified_jobs:qa?.jobs||0,
    staging_package_ready:stage?.ok===true,
    production_authorized:false,
    blockers:[...blockers],
    production_deployed:false,
  };
}

async function api(route,opts={}) {
  const token=process.env.GH_TOKEN||process.env.GITHUB_TOKEN;
  if(!token) throw new Error('Missing GitHub Actions read token');
  const method=opts.method||'GET';
  const response=await fetch(`https://api.github.com/repos/${REPO}/${route}`,{
    method,
    headers:{Authorization:`Bearer ${token}`,Accept:'application/vnd.github+json','X-GitHub-Api-Version':'2022-11-28',...(opts.body?{'Content-Type':'application/json'}:{})},
    body:opts.body?JSON.stringify(opts.body):undefined,
    signal:AbortSignal.timeout(30000),
  });
  if(!response.ok) throw new Error(`GitHub API ${response.status}: ${route.split('?')[0]}`);
  return await response.json();
}

async function observe(sha,branch) {
  const a=await api(`actions/runs?head_sha=${sha}&per_page=100`);
  if(!Array.isArray(a.workflow_runs)||a.total_count>100) throw new Error('Truncated or unavailable QA workflow list');
  const jobs={};
  for(const run of a.workflow_runs) {
    if(run.head_sha!==sha||run.head_branch!==branch||run.event!=='push') continue;
    if(!EXPECTED_QA.some(x=>run.path?.split('@')[0]===`.github/workflows/${x.file}`)) continue;
    if(run.status==='completed'&&run.conclusion==='success') {
      const j=await api(`actions/runs/${run.id}/jobs?per_page=100`);
      if(!Array.isArray(j.jobs)||j.total_count!==j.jobs.length) throw new Error(`Incomplete QA jobs for ${run.id}`);
      jobs[String(run.id)]=j.jobs;
    }
  }
  return classifyExactRuns(a.workflow_runs,jobs,sha,branch);
}

function identity() {
  const sha=process.env.GITHUB_SHA,branch=process.env.GITHUB_REF_NAME;
  if(!SHA_RE.test(sha||'')||branch!==BRANCH||process.env.GITHUB_REPOSITORY!==REPO) throw new Error('Wrong SHA/branch/repository');
  return {sha,branch};
}

async function watch() {
  const {sha,branch}=identity();
  const end=Date.now()+65*60*1000;
  let state={status:'pending',jobs:0,runs:[],failures:[]};
  try {
    for(let i=1;Date.now()<end;i++) {
      state=await observe(sha,branch);
      console.log(`BATCH705_QA_POLL=${i} STATE=${state.status} JOBS=${state.jobs}/14`);
      if(state.status!=='pending') break;
      await new Promise(resolve=>setTimeout(resolve,25000));
    }
    if(state.status==='pending') state={...state,status:'timeout',failures:['QA not completed within 65 minute deadline']};
  } catch(e) {
    state={...state,status:'fail',failures:[`Controller/API error: ${e.message}`]};
  }
  const out={...state,source_sha:sha,source_branch:branch,production_authorized:false};
  fs.mkdirSync('docs/operations',{recursive:true});
  const receipt=`docs/operations/705-BATCH705-QA-${sha.slice(0,12)}.json`;
  fs.writeFileSync(receipt,JSON.stringify(out,null,2)+'\n',{flag:'wx',mode:0o600});
  if(process.env.GITHUB_OUTPUT) fs.appendFileSync(process.env.GITHUB_OUTPUT,`qa_result=${state.status}\nqa_receipt=${receipt}\n`);
  console.log(`QA_FINAL=${state.status.toUpperCase()} QA_VERIFIED_JOBS=${state.jobs} DEPLOYED=NO`);
}

async function repair() {
  const {sha,branch}=identity();
  let qa;
  try{qa=await observe(sha,branch);}catch(e){qa={status:'fail',failures:[e.message],runs:[]};}
  if(qa.status==='pass') throw new Error('Cannot create repair ticket for QA PASS');
  const issue=makeRepairTicket({sha,branch,failures:qa.failures,runs:qa.runs});
  const active=await api('issues?state=all&per_page=100');
  const existing=active.find(x=>x.title===issue.title);
  const submitted=existing||await api('issues',{method:'POST',body:issue});
  console.log(`BATCH706_REPAIR_ISSUE=${submitted.html_url}`);
}

if(process.argv[1]&&import.meta.url===new URL(`file://${process.argv[1]}`).href){
 const fn=({'--watch':watch,'--repair':repair})[process.argv[2]];
 if(!fn){console.error('Usage: --watch|--repair');process.exitCode=2;}
 else fn().catch(e=>{console.error(`BATCH705_CONTROLLER_HOLD=${e.message}`);process.exitCode=2;});
}
