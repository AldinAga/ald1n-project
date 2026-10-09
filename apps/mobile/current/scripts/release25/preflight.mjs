import fs from 'node:fs';
import path from 'node:path';
import {createHash} from 'node:crypto';
import {execFileSync} from 'node:child_process';
const SHA40=/^[0-9a-f]{40}$/i;
const HTPATH='apps/cms/current/public/.htaccess';
const KNOWN_HT_SHA='d10ecf0d63f9a8e1bd978a440afcbadb34faf1d24eb899bf2582d1e5c0033cef';
const EXPECTED_PROJECT='d43b3866-6838-4217-a23e-3dc7f2cc76cc';
const EXPECTED_API='https://cms.ald1n.com/api/v1';
const FINDINGS=['A03','A04','A05','A06','A07','A08','A09','A10','A11','A12','A13','A14','device'];
export const makeGitReader=(repoRoot)=>({async snapshot(){
 const run=(...args)=>execFileSync('git',['-C',repoRoot,...args],{encoding:'utf8',timeout:15000}).trim();
 const lines=(s)=>s?s.split('\n').filter(Boolean):[];
 const patch='apps/mobile/current/plugins/with-splash-api33-resources.js';
 const config='apps/mobile/current/app.config.js';
 const kotlin='apps/mobile/current/modules/ald1n-restore-credentials/android/src/main/java/expo/modules/ald1nrestorecredentials/Ald1nRestoreCredentialsModule.kt';
 let sourcePatchesPresent=false;
 try{
 const kt=fs.readFileSync(path.join(repoRoot,kotlin),'utf8');
 const app=fs.readFileSync(path.join(repoRoot,config),'utf8');
 sourcePatchesPresent=kt.includes('Coroutine { ->')&&kt.includes('clearCredentialState(')&&app.includes('./plugins/with-splash-api33-resources')&&fs.statSync(path.join(repoRoot,patch)).isFile();
 }catch{}
 let knownHtaccessSha256=null;
 try{knownHtaccessSha256=createHash('sha256').update(fs.readFileSync(path.join(repoRoot,HTPATH))).digest('hex')}catch{}
 return {
  head:run('rev-parse','HEAD'),remoteMain:run('rev-parse','refs/remotes/origin/main'),
  staged:lines(run('diff','--cached','--name-only','--no-renames')),
  unstaged:lines(run('diff','--name-only','--no-renames')),
  untracked:lines(run('ls-files','--others','--exclude-standard')),
  sourcePatchesPresent,knownHtaccessSha256
 };
}});
const add=(blockers,name)=>{if(!blockers.includes(name))blockers.push(name)};
export async function collectPreflight({repoRoot,expectedSourceSha,authority,gitReader,easReader,toolProbe,acceptanceIndex,expectedRemoteVersionCode}){
 const blockers=[];const evidence={repoRoot,expectedSourceSha};
 if(!SHA40.test(expectedSourceSha))add(blockers,'INVALID_SOURCE_SHA');
 if(!authority?.ok)add(blockers,'CLI_OR_IDENTITY_AUTHORITY');
 const id=authority?.identity||{};
 if(id.packageName!=='com.ald1n.mobile'||id.projectId!==EXPECTED_PROJECT||id.apiUrl!==EXPECTED_API||id.channel!=='production'||id.profile!=='production'||id.track!=='production')add(blockers,'WRONG_PRODUCTION_IDENTITY');
 let git,remote,remoteProject,tools;
 try{git=await gitReader.snapshot()}catch{add(blockers,'GIT_READ_FAILED')}
 if(git){
  if(git.head!==expectedSourceSha||git.remoteMain!==expectedSourceSha)add(blockers,'SOURCE_CHANGED_OR_DIVERGENT');
  if(git.staged?.length||git.untracked?.length)add(blockers,'INDEX_OR_UNTRACKED_DIRTY');
  const diffs=git.unstaged||[];
  if(diffs.some(x=>x!==HTPATH) || (diffs.includes(HTPATH)&&git.knownHtaccessSha256!==KNOWN_HT_SHA))add(blockers,'UNAPPROVED_RUNTIME_DRIFT');
  if(git.sourcePatchesPresent!==true)add(blockers,'MISSING_NATIVE_FIXES');
  evidence.gitSha=git.head;
 }
 // An independent EAS project:info query must attest owner/slug/UUID.
 // build:version:get returns versionCode only. Never infer identity from it.
 try{remoteProject=await easReader.readRemoteProjectIdentity()}catch{add(blockers,'EAS_PROJECT_READ_FAILED')}
 if(!remoteProject){add(blockers,'EAS_PROJECT_READ_FAILED')}
 else if(remoteProject.owner!==id.owner||remoteProject.slug!=='ald1n-mobile'||remoteProject.projectId!==id.projectId){add(blockers,'REMOTE_IDENTITY_MISMATCH')}
 else {evidence.remoteProjectIdentityVerified=true}
 try{remote=await easReader.readRemoteVersion()}catch{add(blockers,'EAS_VERSION_READ_FAILED')}
 if(remote){
  if(!Number.isInteger(remote.versionCode)||remote.versionCode<1)add(blockers,'REMOTE_VERSION_INVALID');
  else evidence.remoteVersionCode=remote.versionCode;
  if(expectedRemoteVersionCode!==undefined && remote.versionCode!==expectedRemoteVersionCode)add(blockers,'REMOTE_VERSION_CHANGED');
 }
 try{tools=await toolProbe.inspect()}catch{add(blockers,'TOOL_PROBE_FAILED')}
 if(tools){
  for(const name of ['jarsigner','keytool','bundletool','readelf','unzip'])if(tools[name]!==true)add(blockers,`TOOL_MISSING:${name}`);
  if(!Number.isFinite(tools.freeSpaceBytes)||tools.freeSpaceBytes<2e9)add(blockers,'ARTIFACT_DISK_CAPACITY');
 }
 for(const name of FINDINGS){if(acceptanceIndex?.[name]!=='PASS')add(blockers,`RELEASE_FINDING_NOT_CLOSED:${name}`)}
 return {ok:blockers.length===0,blockers,evidence};
}
