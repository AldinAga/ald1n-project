import test from 'node:test';
import assert from 'node:assert/strict';
import {createEasTransport} from './eas-transport.mjs';
const make = output => {
 const calls=[];
 const transport=createEasTransport({nodeBin:'/canonical/node',npmCli:'/canonical/npm.js',easVersion:'24.7.0',mobileRoot:'/tmp/isolated',
  spawn:(cmd,args,opts)=>{calls.push({cmd,args,opts});return {status:0,stdout:JSON.stringify(output),stderr:''}}});
 return {transport,calls};
};
test('pinned eas-cli 24.7.0 emits a string versionCode; it is strictly normalized',async()=>{
 const {transport,calls}=make({versionCode:'24'});
 const read=await transport.readRemoteVersion();
 assert.equal(read.versionCode,24);
 assert.deepEqual(Object.keys(read),['versionCode'],'Do not fabricate owner/project/package EAS receipts');
 assert.equal(calls.length,1);
 assert.deepEqual(calls[0].args.slice(-7),['eas','build:version:get','--platform','android','--profile','production','--json']);
 assert.equal(calls[0].opts.cwd,'/tmp/isolated');
});
test('numeric fixture remains backwards compatible',async()=>{
 assert.equal((await make({versionCode:24}).transport.readRemoteVersion()).versionCode,24);
});
test('rejects empty, malformed, float, negative, oversized and noncanonical version codes',async()=>{
 for(const code of [undefined,null,'',0,-1,1.1,'0','024','24 ',' 24','2e1','24.0','not-a-number','2100000001',2100000001,{},[],false]){
  await assert.rejects(()=>make({versionCode:code}).transport.readRemoteVersion(),/EAS_VERSION_SCHEMA_UNKNOWN/,String(code));
 }
});
test('read-only remote operation never contains build dispatch or mutation flags',async()=>{
 const {transport,calls}=make({versionCode:'25'});await transport.readRemoteVersion();
 const full=calls[0].args.join(' ');
 assert.ok(full.includes('build:version:get'));
 assert.ok(!/\sbuild\s|build:version:set|--auto-submit|--latest|\ssubmit\s|\supdate\s/.test(full));
});
