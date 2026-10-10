import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import {spawnSync} from 'node:child_process';
const fail=(reason,observed={})=>({ok:false,blockers:[reason],observed});
const SHA256=/^[0-9a-f]{64}$/i;
const XPATH={packageName:'/manifest/@package',appVersion:'/manifest/@android:versionName',versionCode:'/manifest/@android:versionCode',minSdk:'/manifest/uses-sdk/@android:minSdkVersion',targetSdk:'/manifest/uses-sdk/@android:targetSdkVersion'};
const REAL_ABIS=['arm64-v8a','armeabi-v7a','x86','x86_64'];
// 16 KB page-size devices are 64-bit; keep 32-bit ELF PT_LOAD alignment valid at 4 KB.
const MIN_ELF_LOAD_ALIGNMENT=Object.freeze({'arm64-v8a':16384,'x86_64':16384,'armeabi-v7a':4096,'x86':4096});
function cmdRunner(cmd,args,opts={}){return spawnSync(cmd,args,{encoding:'utf8',maxBuffer:128*1024*1024,timeout:120000,...opts})}
function parseElfAlignments(data){
 const list=[];for(const line of data.split('\n')){
  const m=line.match(/^\s*LOAD\s+.*?\s+(0x[0-9a-f]+)\s*$/i);
  if(m)list.push(Number.parseInt(m[1],16));
 }
 return list;
}
async function inspectNativeLib(entry,aab,runTool){
 const name=path.basename(entry);
 const root=fs.mkdtempSync(path.join(os.tmpdir(),'ald1n25-elf-'));
 try{
  const out=runTool('unzip',['-p',aab,entry],{encoding:null,maxBuffer:128*1024*1024,timeout:120000});
  if(out?.status!==0||!Buffer.isBuffer(out.stdout)||out.stdout.length<4||out.stdout.length>100*1024*1024)return {ok:false,loadAlignments:[]};
  const bin=path.join(root,name);fs.writeFileSync(bin,out.stdout,{mode:0o600});
  const elf=runTool('readelf',['-lW',bin],{encoding:'utf8',maxBuffer:16*1024*1024,timeout:120000});
  if(elf?.status!==0)return {ok:false,loadAlignments:[]};
  return {ok:true,loadAlignments:parseElfAlignments(elf.stdout)};
 }finally{fs.rmSync(root,{recursive:true,force:true})}
}
export async function verifyNativeAab({sealedAabPath,expectedIdentity,expectedVersionCode,expectedAbiSet=REAL_ABIS,expectedRuntime,expectedSha256,evidence,runTool=cmdRunner,libInspector=inspectNativeLib}){
 const observed={};
 if(!sealedAabPath?.endsWith('.aab')||!path.isAbsolute(sealedAabPath))return fail('NOT_AAB');
 if(!Number.isInteger(expectedVersionCode)||expectedVersionCode<1)return fail('INVALID_EXPECTED_VERSION');
 if(!expectedIdentity||!Array.isArray(expectedAbiSet)||expectedAbiSet.length!==4||new Set(expectedAbiSet).size!==4||expectedAbiSet.some(a=>!REAL_ABIS.includes(a)))return fail('INVALID_ABI_POLICY');
 const call=(cmd,args,opts={})=>{const r=runTool(cmd,args,{encoding:'utf8',maxBuffer:32*1024*1024,timeout:120000,...opts});return r?.status===0?r:null};
 const v=call('bundletool',['validate',`--bundle=${sealedAabPath}`]);
 if(!v)return fail('BUNDLETOOL_VALIDATE_FAIL');
 for(const [key,xpath] of Object.entries(XPATH)){
  const r=call('bundletool',['dump','manifest',`--bundle=${sealedAabPath}`,`--xpath=${xpath}`]);
  if(!r)return fail(`MANIFEST_XPATH_MISSING:${key}`);
  observed[key]=String(r.stdout).trim().replace(/^"|"$/g,'');
 }
 const expected={packageName:expectedIdentity.packageName,appVersion:expectedIdentity.appVersion,versionCode:String(expectedVersionCode),minSdk:String(expectedIdentity.minSdk),targetSdk:String(expectedIdentity.targetSdk)};
 for(const [key,value] of Object.entries(expected))if(observed[key]!==value)return fail(`MANIFEST_MISMATCH:${key}`,observed);
 const config=call('bundletool',['dump','config',`--bundle=${sealedAabPath}`]);
 if(!config||!/\bPAGE_ALIGNMENT_16K\b/.test(String(config.stdout)))return fail('BUNDLE_PAGE_ALIGNMENT_NOT_16K',observed);
 const zip=call('unzip',['-Z1',sealedAabPath]);
 if(!zip)return fail('BUNDLE_LIB_INVENTORY_FAIL',observed);
 const entries=String(zip.stdout).trim().split('\n').filter(Boolean);
 const libs=entries.filter(x=>x.endsWith('.so'));
 if(!libs.length)return fail('NO_NATIVE_LIBRARIES',observed);
 const byAbi=new Set(),abiByEntry=new Map();
 for(const entry of libs){
  if(entry.includes('\\')||entry.includes('..')||entry.startsWith('/'))return fail('UNSAFE_LIB_PATH',observed);
  const m=entry.match(/^[^/]+\/lib\/(arm64-v8a|armeabi-v7a|x86|x86_64)\/[^/]+\.so$/);
  if(!m)return fail('UNEXPECTED_LIB_ENTRY',observed);
  byAbi.add(m[1]);abiByEntry.set(entry,m[1]);
 }
 for(const abi of expectedAbiSet)if(!byAbi.has(abi))return fail(`ABI_MISSING:${abi}`,observed);
 for(const entry of libs){
  let result;
  try{result=await libInspector(entry,sealedAabPath,runTool)}catch{return fail(`ELF_INSPECTION_FAILED:${entry}`,observed)}
  const minAlignment=MIN_ELF_LOAD_ALIGNMENT[abiByEntry.get(entry)];
  if(!result?.ok||!Array.isArray(result.loadAlignments)||!result.loadAlignments.length||result.loadAlignments.some(x=>!Number.isSafeInteger(x)||x<minAlignment||(BigInt(x)&(BigInt(x)-1n))!==0n))return fail(`ELF_NOT_${minAlignment===16384?'16K':'4K'}:${entry}`,observed);
 }
 observed.nativeLibCount=libs.length;observed.abiSet=[...byAbi].sort();
 if(!evidence?.sourceSha||evidence.sourceSha!==evidence.finalSourceSha||evidence.buildId!==evidence.productionBuildId)return fail('NATIVE_EVIDENCE_BUILD_MISMATCH',observed);
 if(evidence.r8Executed!==true||!SHA256.test(evidence.mappingSha256))return fail('R8_MAPPING_EVIDENCE_MISSING',observed);
 if(evidence.runtimeProof?.runtimeVersion!==expectedRuntime||evidence.runtimeProof?.source!=='BUNDLE_RESOURCE')return fail('RUNTIME_PROOF_MISSING',observed);
 const dr=evidence.deviceReceipt;
 if(dr?.approved!==true||dr?.artifactSha256!==expectedSha256||dr?.sourceSha!==evidence.sourceSha||dr?.buildId!==evidence.buildId||typeof dr.reviewer!=='string'||dr.reviewer.length<3||dr.devicePlatform!=='ANDROID')return fail('DEVICE_RECEIPT_MISSING',observed);
 observed.deviceReceiptVerified=true;observed.runtimeResourceVerified=true;
 return {ok:true,blockers:[],observed};
}
