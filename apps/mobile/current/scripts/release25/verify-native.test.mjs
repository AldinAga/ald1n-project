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
