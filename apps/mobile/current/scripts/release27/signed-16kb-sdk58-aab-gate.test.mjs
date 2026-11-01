import assert from 'node:assert/strict';
import test from 'node:test';
import {mkdtempSync,writeFileSync,readFileSync,rmSync} from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {
  parseReleaseArgs,parseBundleConfig,parseManifestValue,
  selectDeliveredApks,verifySignedJarOutput
} from './signed-16kb-sdk58-aab-gate.mjs';

const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const source=readFileSync(path.join(root,'scripts/release27/signed-16kb-sdk58-aab-gate.mjs'),'utf8');
const pkg=JSON.parse(readFileSync(path.join(root,'package.json'),'utf8'));
const eas=JSON.parse(readFileSync(path.join(root,'eas.json'),'utf8'));
const RUNTIME='1.0.0-m17-sdk58-play2';

function fixture(callback) {
  const dir=mkdtempSync(path.join(os.tmpdir(),'ald1n-sdk58-aab-args-'));
  try {
    const files=Object.fromEntries(['aab','bundletool','zipalign','apksigner'].map(k=>{
      const f=path.join(dir,k==='aab'?'artifact.aab':k==='bundletool'?'bundletool.jar':k);
      writeFileSync(f,'synthetic-not-a-binary',{mode:0o600});
      return [k,f];
    }));
    const args={
      ...files,'aab-sha256':'a'.repeat(64),
      'signer-sha256':'b'.repeat(64),
      'expected-version-code':'29',
      'eas-build-id':'123e4567-e89b-42d3-a456-426614174000',
      'source-sha':'c'.repeat(40),'runtime-version':RUNTIME,
    };
    return callback(args);
  } finally {rmSync(dir,{recursive:true,force:true})}
}
function parse(a){return parseReleaseArgs(Object.entries(a).flatMap(([k,v])=>['--'+k,v]))}

test('SDK58 Play binary gate is distinct from old v1.5 source-only gate',()=>{
 assert.equal(pkg.dependencies.expo,'~58.0.22');
 assert.equal(pkg.dependencies['react-native-nitro-google-signin'],'2.3.1');
 assert.equal(pkg.dependencies['react-native-nitro-modules'],'0.37.1');
 assert.equal(pkg.packageManager,'npm@12.2.1');
 assert.equal(eas.cli.version,'27.2.0');
 assert.equal(eas.build['m17-sdk58-play-rc'].android.buildType,'app-bundle');
 assert.ok(source.includes("const EXPECTED_VERSION = '1.0.0'"));
 assert.ok(source.includes("const EXPECTED_RUNTIME = '"+RUNTIME+"'"));
 assert.ok(!source.includes("const EXPECTED_VERSION = '1.5.0'"));
 assert.ok(!source.includes("const EXPECTED_RUNTIME = '1.5.0-native2'"));
 assert.ok(!source.includes('eas build --platform'));
 assert.ok(!source.includes('eas submit --'));
});

test('accepts valid syntactic postbuild evidence only; this test does not certify an AAB',()=>{
 fixture(a=>{
   const result=parse(a);
   assert.equal(result['expected-version-code'],'29');
   assert.equal(result['runtime-version'],RUNTIME);
 });
});

test('vCode 28 and older or unaudited runtime are rejected',()=>{
 fixture(a=>{
   for(const n of ['28','27','0'])assert.throws(()=>parse({...a,'expected-version-code':n}),/HOLD_VERSION_CODE_NOT_NEWER_THAN_PRODUCTION_28/);
   assert.throws(()=>parse({...a,'runtime-version':'1.5.0-native2'}),/HOLD_SDK58_PLAY_RUNTIME2_REQUIRED/);
   assert.throws(()=>parse({...a,'runtime-version':'1.0.0-m17-sdk58-play1'}),/HOLD_SDK58_PLAY_RUNTIME2_REQUIRED/);
 });
});

test('requires all signed build identity inputs, signer and actual local files',()=>{
 fixture(a=>{
   assert.throws(()=>parse({...a,'source-sha':'deadbeef'}),/HOLD_INVALID_SOURCE_SHA/);
   assert.throws(()=>parse({...a,'aab-sha256':'bad'}),/HOLD_INVALID_AAB_SHA256/);
   assert.throws(()=>parse({...a,'bundletool':'/does/not/exist'}),/HOLD_MISSING_FILE_BUNDLETOOL/);
   assert.throws(()=>parse({...a,'eas-build-id':'not-a-build'}),/HOLD_INVALID_EAS_BUILD_ID/);
 });
});

test('bundletool must explicitly report 16K alignment and never 4K',()=>{
 assert.equal(parseBundleConfig('PAGE_ALIGNMENT_16K'),'PAGE_ALIGNMENT_16K');
 assert.throws(()=>parseBundleConfig('PAGE_ALIGNMENT_4K'),/HOLD_BUNDLE_CONFIG_NOT_16K/);
 assert.throws(()=>parseBundleConfig('PAGE_ALIGNMENT_16K PAGE_ALIGNMENT_4K'),/HOLD_BUNDLE_CONFIG_NOT_16K/);
 assert.equal(parseManifestValue('com.ald1n.mobile','com.ald1n.mobile','PACKAGE'),'com.ald1n.mobile');
 assert.throws(()=>parseManifestValue('com.ald1n.mobile.preview','com.ald1n.mobile','PACKAGE'),/HOLD_MANIFEST_PACKAGE_MISMATCH/);
});

test('default Play split delivery cannot silently degrade to universal-only',()=>{
 assert.deepEqual(selectDeliveredApks(['splits/base-arm64.apk','splits/config.apk']),['splits/base-arm64.apk','splits/config.apk']);
 assert.throws(()=>selectDeliveredApks(['universal.apk']),/HOLD_DEFAULT_APKS_UNIVERSAL_ONLY/);
 assert.throws(()=>selectDeliveredApks(['../escape.apk']),/HOLD_APKS_UNSAFE_ENTRY_NAME/);
});

test('unsigned AAB and unverifiable JAR signer remain HOLD',()=>{
 assert.throws(()=>verifySignedJarOutput('not signed by any signer'),/HOLD_AAB_SIGNATURE_NOT_VERIFIED/);
 assert.throws(()=>verifySignedJarOutput('jar verified. unsigned entries'),/HOLD_AAB_UNSIGNED_ENTRIES/);
});
