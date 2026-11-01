import test from 'node:test';
import assert from 'node:assert/strict';
import { validateMetadata, parseAapt, parseSigner, parseElfLoadSegments } from './b1028-eas-apk-attest.mjs';
const expected = { id:'994c264d-a147-40a4-ba7b-51f8734b3422', source:'4af7b16c4f47c6aa09271ed03993f477c9be1bd8', profile:'m17-sdk58-uat', channel:'m17-sdk58-uat', runtime:'1.0.0-m17-sdk58-uat1', appVersion:'1.0.0' };
const base = { id:expected.id, status:'NEW', platform:'ANDROID', buildProfile:expected.profile, gitCommitHash:expected.source, channel:expected.channel, runtimeVersion:expected.runtime, appVersion:expected.appVersion, appBuildVersion:'11' };
test('status NEW is authenticated but not misrepresented as completed APK',()=>{
  const {receipt,artifactUrl}=validateMetadata(base, expected);
  assert.equal(receipt.status,'NEW'); assert.equal(receipt.versionCodeFromEas,11);
  assert.equal(receipt.verdict,'EAS_BUILD_NOT_READY_FOR_APK_UAT'); assert.equal(artifactUrl,null);
});
test('finished matching APK permits only Expo official artifact origin',()=>{
  const {receipt,artifactUrl}=validateMetadata({...base,status:'FINISHED',artifacts:{buildUrl:'https://expo.dev/artifacts/eas/abc123.apk'}},expected);
  assert.equal(receipt.verdict,'BINARY_INSPECTION_PENDING');assert.ok(artifactUrl.endsWith('.apk'));
  for (const url of ['http://expo.dev/artifacts/eas/x.apk','https://example.org/artifacts/eas/x.apk','https://expo.dev.evil.org/artifacts/eas/x.apk','https://expo.dev/evil.apk']) {
    assert.throws(()=>validateMetadata({...base,status:'FINISHED',artifacts:{buildUrl:url}},expected),/HOLD_EAS_APK_URL_NOT_ALLOWED/);
  }
});
test('cross profile, wrong SHA, wrong project runtime and unknown states fail closed',()=>{
  for(const patch of [{id:'a'}, {platform:'IOS'}, {buildProfile:'production'}, {gitCommitHash:'bad'}, {channel:'production'}, {runtimeVersion:'1.5.0-native2'}, {appVersion:'2.0.0'}, {status:'UNKNOWN'}, {appBuildVersion:'abc'}]){
    assert.throws(()=>validateMetadata({...base,...patch},expected),/HOLD_EAS_/);
  }
});
test('AAPT extracts signed APK package, versionCode and version',()=>{
  assert.deepEqual(parseAapt("package: name='com.ald1n.mobile.preview' versionCode='11' versionName='1.0.0' compileSdkVersion='36'\n"),{packageName:'com.ald1n.mobile.preview',versionCode:11,versionName:'1.0.0'});
  assert.throws(()=>parseAapt('broken'),/HOLD_/);
});
test('certificate and modern signing schemes must both be proven',()=>{
  const fingerprint='a'.repeat(64);
  assert.equal(parseSigner('Verified using v2 scheme (APK Signature Scheme v2): true\nSigner #1 certificate SHA-256 digest: '+fingerprint),fingerprint);
  assert.throws(()=>parseSigner('Signer #1 certificate SHA-256 digest: '+fingerprint),/HOLD_/);
});
test('every 64bit ELF LOAD needs 16KiB alignment and congruence',()=>{
  assert.equal(parseElfLoadSegments('  LOAD 0x000000 0x0000000000000000 0x0000000000000000 0x10000 0x10000 R E 0x4000\n').length,1);
  assert.throws(()=>parseElfLoadSegments('  LOAD 0x000000 0x0000000000000000 0x0000000000000000 0x10000 0x10000 R E 0x1000\n'),/HOLD_/);
  assert.throws(()=>parseElfLoadSegments('  LOAD 0x000001 0x0000000000000000 0x0000000000000000 0x10000 0x10000 R E 0x4000\n'),/HOLD_/);
});
