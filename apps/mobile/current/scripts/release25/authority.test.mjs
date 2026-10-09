import test from 'node:test';
import assert from 'node:assert/strict';
import { validateAuthority } from './authority.mjs';
const good = {
  agentsCliVersion: '24.8.0', easCliVersion:'24.8.0',helperCliVersion:'24.8.0',
  packageName:'com.ald1n.mobile',appVersion:'1.0.0',runtimeVersion:'1.0.0-build17',
  owner:'ald1n',projectId:'d43b3866-6838-4217-a23e-3dc7f2cc76cc',
  apiUrl:'https://cms.ald1n.com/api/v1',channel:'production',profile:'production',
  track:'production',releaseStatus:'completed',appVersionSource:'remote',autoIncrement:true
};
test('rejects_conflicting_pins',()=>{
  assert.equal(validateAuthority({...good,agentsCliVersion:'24.7.0'}).reason,'CLI_VERSION_CONFLICT');
  assert.equal(validateAuthority({...good,helperCliVersion:'24.7.0'}).ok,false);
});
test('rejects_wrong_production_identity',()=>{
  for(const entry of [['packageName','com.fake.app'],['channel','preview'],['track','internal'],['runtimeVersion','other'],['apiUrl','http://localhost'],['projectId','bad'],['releaseStatus','draft'],['appVersionSource','local']]) {
    assert.equal(validateAuthority({...good,[entry[0]]:entry[1]}).ok,false,entry[0]);
  }
});
test('accepts_consistent_pin',()=>{
  const r=validateAuthority(good);
  assert.equal(r.ok,true); assert.equal(r.identity.cliVersion,'24.8.0');
  assert.equal(r.identity.packageName,'com.ald1n.mobile');
});
test('rejects_missing_version_source',()=>{
  assert.equal(validateAuthority({...good,autoIncrement:false}).ok,false);
});
