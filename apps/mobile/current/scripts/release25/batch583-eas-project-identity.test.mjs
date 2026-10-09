import test from 'node:test';
import assert from 'node:assert/strict';
import {createEasTransport} from './eas-transport.mjs';
import {collectPreflight} from './preflight.mjs';
const SHA='b'.repeat(40);
const UUID='d43b3866-6838-4217-a23e-3dc7f2cc76cc';
const FULL='@ald1n/ald1n-mobile';
const projectInfo=`fullName  ${FULL}\nID        ${UUID}\n`;
const validAuthority={ok:true,identity:{cliVersion:'24.7.0',owner:'ald1n',projectId:UUID,packageName:'com.ald1n.mobile',apiUrl:'https://cms.ald1n.com/api/v1',channel:'production',profile:'production',track:'production'}};
const accepted=Object.fromEntries([...Array(12)].map((_,i)=>['A'+String(i+3).padStart(2,'0'),'PASS']));accepted.device='PASS';
function setup({project=projectInfo,version={versionCode:'24'},projectStatus=0}={}){
 const calls=[];
 const spawn=(cmd,args,opts)=>{
  const eas=args.slice(args.indexOf('eas')+1);
  calls.push({cmd,args,opts,eas});
  if(eas[0]==='project:info')return {status:projectStatus,stdout:project,stderr:''};
  if(eas[0]==='build:version:get')return {status:0,stdout:JSON.stringify(version),stderr:''};
  throw Error('UNEXPECTED_EAS_COMMAND');
 };
 const eas=createEasTransport({nodeBin:'/canonical/node',npmCli:'/canonical/npm-cli.js',easVersion:'24.7.0',mobileRoot:'/isolated/mobile',spawn});
 return {eas,calls};
}
function inputs(easReader,overrides={}){
 return {repoRoot:'/isolated/repo',expectedSourceSha:SHA,authority:validAuthority,
  gitReader:{async snapshot(){return {head:SHA,remoteMain:SHA,staged:[],unstaged:[],untracked:[],sourcePatchesPresent:true}}},
  easReader,toolProbe:{async inspect(){return {jarsigner:true,keytool:true,bundletool:true,readelf:true,unzip:true,freeSpaceBytes:4e9}}},
  acceptanceIndex:accepted,expectedRemoteVersionCode:24,...overrides};
}
test('EAS project:info is a single read-only pinned call from exact Mobile root',async()=>{
 const {eas,calls}=setup();const id=await eas.readRemoteProjectIdentity();
 assert.deepEqual(id,{owner:'ald1n',slug:'ald1n-mobile',projectId:UUID});
 assert.equal(calls.length,1);assert.equal(calls[0].opts.cwd,'/isolated/mobile');
 assert.deepEqual(calls[0].eas,['project:info']);
 assert.ok(calls[0].args.includes('--package=eas-cli@24.7.0'));
});
test('project:info tolerates ANSI formatting only and does not invent an Android package',async()=>{
 const {eas}=setup({project:`\u001b[2mfullName\u001b[22m  ${FULL}\n\u001b[2mID\u001b[22m        ${UUID}\n`});
 assert.deepEqual(Object.keys(await eas.readRemoteProjectIdentity()),['owner','slug','projectId']);
});
test('project:info rejects missing, extra and malformed field data',async()=>{
 for(const project of ['',`fullName  ${FULL}\n`,'fullName  @other/app\nID        not-uuid\n',`fullName  ${FULL}\nID        ${UUID}\nSECRET extra\n`,`fullName  ${FULL}\nID        ${UUID}\n`.repeat(1000)]){
  await assert.rejects(()=>setup({project}).eas.readRemoteProjectIdentity(),/EAS_PROJECT_SCHEMA_UNKNOWN/);
 }
});
test('project:info failed process blocks independently of successful version endpoint',async()=>{
 const {eas}=setup({projectStatus:1});
 await assert.rejects(()=>eas.readRemoteProjectIdentity(),/EAS_READ_FAILED/);
 const pre=await collectPreflight(inputs(eas));assert.equal(pre.ok,false);
 assert.ok(pre.blockers.includes('EAS_PROJECT_READ_FAILED'));
});
test('preflight accepts real pinned version-only JSON only with separate project identity',async()=>{
 const {eas,calls}=setup();const pre=await collectPreflight(inputs(eas));
 assert.equal(pre.ok,true,JSON.stringify(pre.blockers));
 assert.equal(pre.evidence.remoteVersionCode,24);
 assert.equal(pre.evidence.remoteProjectIdentityVerified,true);
 assert.deepEqual(calls.map(c=>c.eas[0]),['project:info','build:version:get']);
});
test('preflight rejects missing separate identity even when version JSON contains forged metadata',async()=>{
 const forged={async readRemoteVersion(){return {versionCode:24,owner:'ald1n',projectId:UUID,packageName:'com.ald1n.mobile'}}};
 const p=await collectPreflight(inputs(forged));assert.equal(p.ok,false);
 assert.ok(p.blockers.includes('EAS_PROJECT_READ_FAILED'));
});
test('preflight rejects wrong owner, slug and uuid separately',async()=>{
 for(const remote of [
  {owner:'evil',slug:'ald1n-mobile',projectId:UUID},
  {owner:'ald1n',slug:'impostor',projectId:UUID},
  {owner:'ald1n',slug:'ald1n-mobile',projectId:'11111111-1111-4111-8111-111111111111'}
 ]){
  const bad={async readRemoteProjectIdentity(){return remote},async readRemoteVersion(){return {versionCode:24}}};
  const p=await collectPreflight(inputs(bad));assert.equal(p.ok,false,JSON.stringify(remote));
  assert.ok(p.blockers.includes('REMOTE_IDENTITY_MISMATCH'));
 }
});
test('preflight rejects changed version, wrong local package and missing A03-A14/device proofs',async()=>{
 const {eas}=setup({version:{versionCode:'25'}});
 let pre=await collectPreflight(inputs(eas));assert.equal(pre.ok,false);
 assert.ok(pre.blockers.includes('REMOTE_VERSION_CHANGED'));
 const good=setup().eas;
 pre=await collectPreflight(inputs(good,{authority:{ok:true,identity:{...validAuthority.identity,packageName:'com.foreign.app'}}}));
 assert.ok(pre.blockers.includes('WRONG_PRODUCTION_IDENTITY'));
 pre=await collectPreflight(inputs(good,{acceptanceIndex:{...accepted,device:'SKIPPED',A03:'BLOCKED'}}));
 assert.ok(pre.blockers.includes('RELEASE_FINDING_NOT_CLOSED:device'));
 assert.ok(pre.blockers.includes('RELEASE_FINDING_NOT_CLOSED:A03'));
});
test('preflight blocks project method returning null without throwing',async()=>{
 const e={async readRemoteProjectIdentity(){return null},async readRemoteVersion(){return {versionCode:24}}};
 const p=await collectPreflight(inputs(e));assert.equal(p.ok,false);
 assert.ok(p.blockers.includes('EAS_PROJECT_READ_FAILED'));
});
