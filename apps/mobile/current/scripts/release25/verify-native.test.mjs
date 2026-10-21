import test from 'node:test';
import assert from 'node:assert/strict';
import {verifyNativeAab} from './verify-native.mjs';
const SHA='c'.repeat(40),BID='11111111-1111-4111-8111-111111111111',AH='f'.repeat(64);
const ABIS=['arm64-v8a','armeabi-v7a','x86','x86_64'];
// Explicit synthetic inspector evidence; this does not attest a real binary.
const fixtureInspection=(entry,loadAlignments)=>({ok:true,loadAlignments,entry,...(entry.includes('/lib/arm64-v8a/')||entry.includes('/lib/x86_64/')?{relro:{status:'PASS'}}:{})});
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
  libInspector:async p=>fixtureInspection(p,[0x4000,0x4000])
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
 const {state,libPaths,...input}=baseline({libInspector:async entry=>fixtureInspection(entry,[0x1000])});
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
const mixedLibInspector=async entry=>fixtureInspection(entry,[is32BitAbi(entry)?0x1000:0x4000]);

test('build25_accepts_4k_32bit_ELF_while_keeping_16k_64bit_requirement',async()=>{
 const {state,libPaths,...input}=baseline({libInspector:mixedLibInspector});
 const result=await verifyNativeAab(input);
 assert.equal(result.ok,true,JSON.stringify(result.blockers));
 assert.equal(result.observed.nativeLibCount,4);
 assert.deepEqual(result.observed.abiSet,[...ABIS].sort());
});
test('build25_rejects_4k_arm64_even_if_other_ABIs_are_valid',async()=>{
 const inspector=async entry=>fixtureInspection(entry,[entry.includes('/lib/arm64-v8a/')?0x1000:0x4000]);
 const {state,libPaths,...input}=baseline({libInspector:inspector});
 const r=await verifyNativeAab(input);
 assert.equal(r.ok,false);assert.match(r.blockers.join(','),/ELF_NOT_16K.*arm64-v8a/);
});
test('build25_rejects_4k_x86_64_even_if_32bit_is_valid',async()=>{
 const inspector=async entry=>fixtureInspection(entry,[entry.includes('/lib/x86_64/')?0x1000:0x4000]);
 const {state,libPaths,...input}=baseline({libInspector:inspector});
 const r=await verifyNativeAab(input);
 assert.equal(r.ok,false);assert.match(r.blockers.join(','),/ELF_NOT_16K.*x86_64/);
});
test('build25_rejects_32bit_ELF_below_4k',async()=>{
 const inspector=async entry=>fixtureInspection(entry,[is32BitAbi(entry)?0x800:0x4000]);
 const {state,libPaths,...input}=baseline({libInspector:inspector});
 const r=await verifyNativeAab(input);
 assert.equal(r.ok,false);assert.match(r.blockers.join(','),/ELF_NOT_4K/);
});
test('build25_rejects_non_power_of_two_ELF_alignment_in_either_ABI_family',async()=>{
 for(const badAbi of ['x86','arm64-v8a']){
  const inspector=async entry=>fixtureInspection(entry,[entry.includes(`/lib/${badAbi}/`)?0x6000:0x4000]);
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

test('rejects_missing_64bit_RELRO_audit_evidence',async()=>{
 const {state,libPaths,...input}=baseline({libInspector:async()=>({ok:true,loadAlignments:[0x4000]})});
 const result=await verifyNativeAab(input);
 assert.equal(result.ok,false);assert.match(result.blockers.join(','),/ELF_RELRO_EVIDENCE_MISSING/);
});
test('accepts_explicitly_audited_absent_RELRO_without_claiming_PASS',async()=>{
 const {state,libPaths,...input}=baseline({libInspector:async entry=>({...fixtureInspection(entry,[is32BitAbi(entry)?0x1000:0x4000]),relro:{status:'ABSENT'}})});
 const result=await verifyNativeAab(input);
 assert.equal(result.ok,true);assert.equal(result.observed.relroByEntry?.['base/lib/arm64-v8a/libald1n.so'],'ABSENT');
});
function syntheticElf64(abi,relroSize=0x4000n){
 const b=Buffer.alloc(0x4000);b.set([127,69,76,70,2,1,1]);b.writeUInt16LE(3,16);b.writeUInt16LE(abi==='arm64-v8a'?183:62,18);b.writeUInt32LE(1,20);
 b.writeBigUInt64LE(64n,32);b.writeUInt16LE(64,52);b.writeUInt16LE(56,54);b.writeUInt16LE(2,56);
 b.writeUInt32LE(1,64);b.writeBigUInt64LE(0x4000n,96);b.writeBigUInt64LE(0x4000n,104);b.writeBigUInt64LE(0x4000n,112);
 b.writeUInt32LE(0x6474e552,120);b.writeBigUInt64LE(relroSize,152);b.writeBigUInt64LE(relroSize,160);b.writeBigUInt64LE(1n,168);return b;
}
function defaultInspectorFixture(badRelro=false){
 const b=baseline(),tools=b.runTool;
 b.libInspector=undefined;
 b.runTool=(cmd,args,opts)=>{
  if(cmd==='unzip'&&args[0]==='-p'){
   const abi=args[2].split('/')[2];return {status:0,stdout:is32BitAbi(args[2])?Buffer.from([127,69,76,70]):syntheticElf64(abi,badRelro?4096n:16384n)};
  }
  if(cmd==='readelf')return {status:0,stdout:'  LOAD 0x000000 0x000000 0x000000 0x1 0x1 R E 0x4000\n'};
  return tools(cmd,args,opts);
 };return b;
}
test('default_inspector_checks_binary_64bit_and_preserves_32bit_readelf',async()=>{
 const {state,libPaths,...input}=defaultInspectorFixture();const result=await verifyNativeAab(input);
 assert.equal(result.ok,true);assert.equal(result.observed.relroByEntry?.['base/lib/x86_64/libald1n.so'],'PASS');
});
test('default_inspector_rejects_good_LOAD_bad_RELRO_binary',async()=>{
 const {state,libPaths,...input}=defaultInspectorFixture(true);const result=await verifyNativeAab(input);
 assert.equal(result.ok,false);assert.match(result.blockers.join(','),/ELF_RELRO_NOT_16K.*arm64-v8a/);
});
