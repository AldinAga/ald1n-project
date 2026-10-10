import test from 'node:test';
import assert from 'node:assert/strict';
import {verifyNativeAab} from './verify-native.mjs';
const SHA='c'.repeat(40),BID='11111111-1111-4111-8111-111111111111',AH='f'.repeat(64);
const ABIS=['arm64-v8a','armeabi-v7a','x86','x86_64'];
function baseline(overrides={}){
 const state={package:'com.ald1n.mobile',versionName:'1.0.0',versionCode:'25',minSdkVersion:'24',targetSdkVersion:'36',config:'page_alignment: PAGE_ALIGNMENT_16K'};
 const libPaths=ABIS.map(a=>`base/lib/${a}/libald1n.so`);
 const ctx={sealedAabPath:'/sealed/release.aab',expectedIdentity:{packageName:'com.ald1n.mobile',appVersion:'1.0.0',minSdk:24,targetSdk:36},expectedVersionCode:25,expectedAbiSet:ABIS,expectedRuntime:'1.0.0-build17',
  expectedSha256:AH,
  evidence:{sourceSha:SHA,finalSourceSha:SHA,buildId:BID,productionBuildId:BID,mappingSha256:'a'.repeat(64),r8Executed:true,runtimeProof:{runtimeVersion:'1.0.0-build17',source:'BUNDLE_RESOURCE'},deviceReceipt:{approved:true,artifactSha256:AH,sourceSha:SHA,buildId:BID,reviewer:'QA-verified',devicePlatform:'ANDROID'}},
  runTool:(cmd,args)=>{
   if(cmd==='bundletool'&&args[0]==='validate')return {status:0,stdout:'Bundle is valid'};
   if(cmd==='bundletool'&&args[1]==='config')return {status:0,stdout:state.config};
   if(cmd==='bundletool'&&args[1]==='manifest'){
    const xpath=args.find(s=>s.startsWith('--xpath='));let field;
    if(xpath.includes('package'))field='package';else if(xpath.includes('versionName'))field='versionName';
    else if(xpath.includes('versionCode'))field='versionCode';
    else if(xpath.includes('minSdkVersion'))field='minSdkVersion';else field='targetSdkVersion';
    return {status:0,stdout:state[field]};
   }
   if(cmd==='unzip'&&args[0]==='-Z1')return {status:0,stdout:['BundleConfig.pb',...libPaths].join('\n')};
   return {status:1,stdout:'unknown'};
  },
  libInspector:async p=>({ok:true,loadAlignments:[0x4000,0x4000],entry:p})
 };
 return {...ctx,...overrides,state,libPaths};
}
test('passes_complete_fake_tool_fixture',async()=>{
 const {state,libPaths,...input}=baseline();assert.equal((await verifyNativeAab(input)).ok,true);
});
test('rejects_wrong_manifest_identity_version',async()=>{
 const b=baseline();b.state.package='com.another.mobile';const {state,libPaths,...input}=b;
 assert.equal((await verifyNativeAab(input)).ok,false);
 const n=baseline();n.state.versionCode='24';const {state:s2,libPaths:l2,...i2}=n;
 assert.equal((await verifyNativeAab(i2)).ok,false);
});
test('rejects_missing_fourth_abi',async()=>{
 const b=baseline();b.libPaths.pop();const {state,libPaths,...input}=b;
 assert.equal((await verifyNativeAab(input)).ok,false);
});
test('rejects_missing_16k_zip_alignment',async()=>{
 const b=baseline();b.state.config='page_alignment: PAGE_ALIGNMENT_4K';const {state,libPaths,...input}=b;
 assert.equal((await verifyNativeAab(input)).ok,false);
});
test('rejects_elf_load_below_16k',async()=>{
 const {state,libPaths,...input}=baseline({libInspector:async()=>({ok:true,loadAlignments:[0x1000]})});
 assert.equal((await verifyNativeAab(input)).ok,false);
});
test('rejects_missing_mapping',async()=>{
 const b=baseline();b.evidence.mappingSha256='';const {state,libPaths,...input}=b;
 assert.equal((await verifyNativeAab(input)).ok,false);
});
test('rejects_missing_runtime_evidence',async()=>{
 const b=baseline();b.evidence.runtimeProof=undefined;const {state,libPaths,...input}=b;
 assert.equal((await verifyNativeAab(input)).ok,false);
});
test('rejects_device_receipt_absence',async()=>{
 const b=baseline();b.evidence.deviceReceipt=undefined;const {state,libPaths,...input}=b;
 assert.equal((await verifyNativeAab(input)).ok,false);
});
test('rejects_unknown_build_provenance',async()=>{
 const b=baseline();b.evidence.buildId='22222222-2222-4222-8222-222222222222';const {state,libPaths,...input}=b;
 assert.equal((await verifyNativeAab(input)).ok,false);
});

// Build25 regression: Android's 16 KB page-size Play requirement applies to 64-bit ABIs.
// 32-bit libraries must still have valid, power-of-two ELF PT_LOAD alignment >= 4 KB.
const is32BitAbi=entry=>entry.includes('/lib/armeabi-v7a/')||entry.includes('/lib/x86/');
const mixedLibInspector=async entry=>({ok:true,loadAlignments:[is32BitAbi(entry)?0x1000:0x4000]});

test('build25_accepts_4k_32bit_ELF_while_keeping_16k_64bit_requirement',async()=>{
 const {state,libPaths,...input}=baseline({libInspector:mixedLibInspector});
 const result=await verifyNativeAab(input);
 assert.equal(result.ok,true,JSON.stringify(result.blockers));
 assert.equal(result.observed.nativeLibCount,4);
 assert.deepEqual(result.observed.abiSet,[...ABIS].sort());
});
test('build25_rejects_4k_arm64_even_if_other_ABIs_are_valid',async()=>{
 const inspector=async entry=>({ok:true,loadAlignments:[entry.includes('/lib/arm64-v8a/')?0x1000:0x4000]});
 const {state,libPaths,...input}=baseline({libInspector:inspector});
 const r=await verifyNativeAab(input);
 assert.equal(r.ok,false);assert.match(r.blockers.join(','),/ELF_NOT_16K.*arm64-v8a/);
});
test('build25_rejects_4k_x86_64_even_if_32bit_is_valid',async()=>{
 const inspector=async entry=>({ok:true,loadAlignments:[entry.includes('/lib/x86_64/')?0x1000:0x4000]});
 const {state,libPaths,...input}=baseline({libInspector:inspector});
 const r=await verifyNativeAab(input);
 assert.equal(r.ok,false);assert.match(r.blockers.join(','),/ELF_NOT_16K.*x86_64/);
});
test('build25_rejects_32bit_ELF_below_4k',async()=>{
 const inspector=async entry=>({ok:true,loadAlignments:[is32BitAbi(entry)?0x800:0x4000]});
 const {state,libPaths,...input}=baseline({libInspector:inspector});
 const r=await verifyNativeAab(input);
 assert.equal(r.ok,false);assert.match(r.blockers.join(','),/ELF_NOT_4K/);
});
test('build25_rejects_non_power_of_two_ELF_alignment_in_either_ABI_family',async()=>{
 for(const badAbi of ['x86','arm64-v8a']){
  const inspector=async entry=>({ok:true,loadAlignments:[entry.includes(`/lib/${badAbi}/`)?0x6000:0x4000]});
  const {state,libPaths,...input}=baseline({libInspector:inspector});
  const r=await verifyNativeAab(input);
  assert.equal(r.ok,false,`${badAbi} incorrectly accepted invalid ELF alignment`);
 }
});
test('build25_mixed_alignment_still_rejects_missing_device_acceptance',async()=>{
 const b=baseline({libInspector:mixedLibInspector});b.evidence.deviceReceipt=undefined;
 const {state,libPaths,...input}=b;
 const r=await verifyNativeAab(input);
 assert.equal(r.ok,false);assert.match(r.blockers.join(','),/DEVICE_RECEIPT_MISSING/);
});
test('build25_mixed_alignment_still_rejects_4k_bundle_zip_policy',async()=>{
 const b=baseline({libInspector:mixedLibInspector});b.state.config='page_alignment: PAGE_ALIGNMENT_4K';
 const {state,libPaths,...input}=b;
 const r=await verifyNativeAab(input);
 assert.equal(r.ok,false);assert.match(r.blockers.join(','),/BUNDLE_PAGE_ALIGNMENT_NOT_16K/);
});
