import assert from 'node:assert/strict';
import { test } from 'node:test';
import { createHash, X509Certificate } from 'node:crypto';
import { mkdtempSync, mkdirSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';
import { extractSignerFingerprints, verifySignedJarOutput, parseBundleConfig, parseManifestValue, parseReleaseArgs, shaFile, selectDeliveredApks, auditDeliveredApkSet } from './signed-16kb-aab-release-gate.mjs';

const TEST_CERTIFICATE = `-----BEGIN CERTIFICATE-----
MIIDHzCCAgegAwIBAgIUKJwqsiTT9fzQpiZmj3eisFhmlfUwDQYJKoZIhvcNAQEL
BQAwHzEdMBsGA1UEAwwUVGVzdGluZyBGaXh0dXJlIE9ubHkwHhcNMjYxMDI0MjEx
NDIzWhcNMjYxMDI1MjExNDIzWjAfMR0wGwYDVQQDDBRUZXN0aW5nIEZpeHR1cmUg
T25seTCCASIwDQYJKoZIhvcNAQEBBQADggEPADCCAQoCggEBALkQdXTfZGdJM0AS
QA4WdBb7Uv0mU60k3sqAmOfroRXkOv0tgkRmZdaoxPF1NDJ6aXHRBxy9VV2W8EfB
axBidv7SrFWqg5EpVdxmoOZ6S4hlTDpnkqbSao4+1A4u94EEW2l+eyWHFrT/HAKS
6OU0bZ+DfOFuVeJuOEgBLX93sxTarT8elvJ9qJBw6RINjsxjfR/z0Rdqxqui8GIA
zduSEcseQtKKw9yELg3LQNrhajTP5XPqbE7a6hixZ1sSfh3y9OuCU5aqKQhX9AXv
4lyMY+4RwWV9GS1OxtbN5Tf3QziZbSF3QicxJ/ZZKBhRbR46fSY03Qpyj0mS2/2g
LPxknlMCAwEAAaNTMFEwHQYDVR0OBBYEFLtuI9dBZMAoM81933C78onBXfiOMB8G
A1UdIwQYMBaAFLtuI9dBZMAoM81933C78onBXfiOMA8GA1UdEwEB/wQFMAMBAf8w
DQYJKoZIhvcNAQELBQADggEBAAgtM1x2s8yvAP6AieGrK01qLHA/v00QybWVQKqB
p/o5ZRKsa4nvnvHX2TIQtvlEZStVDp26y0hEJdX48w4lh99yQjYiEoSErZsmS78a
6Dg4ul5p7jPC6eZSLT01wByeYu4GDk+nP3gs7W0vQBLdevOnEIvNzg8NHDp1fKO6
JpkJ/B+Cjl6BJpfX2yJh/ZTIpDo/EL39dvrFKqZA/JDdRJbjqyke5kxjy7f8DLcS
YKr1Pl9LjtExaC9qsFcpnOWJSHuU447D+yFbMQOCIcgxaOZF/itmRdFh2n5lxJjr
MvIH9nnG72Eoy9fMuOeDbZ4QPtEEAAAyymFOGI1WwZ5XCkU=
-----END CERTIFICATE-----`;
const uuid = '6e596fb5-e144-4b77-8b2b-e05c238444c7';
const sha = 'a'.repeat(64);
const signer = 'b'.repeat(64);
const commit = 'c'.repeat(40);
const cli = fileURLToPath(new URL('./signed-16kb-aab-release-gate.mjs', import.meta.url));

function fixture(fn) {
  const d = mkdtempSync(path.join(tmpdir(), 'ald1n-b833-test-'));
  const aab = path.join(d, 'existing.aab');
  const jar = path.join(d, 'bundletool.jar');
  const zipalign = path.join(d, 'zipalign');
  const apksigner = path.join(d, 'apksigner');
  for (const file of [aab,jar,zipalign,apksigner]) writeFileSync(file, 'synthetic-non-executable-test-only');
  try {
    const values = {aab,'aab-sha256':sha,'signer-sha256':signer,bundletool:jar,zipalign,apksigner,'expected-version-code':'28','eas-build-id':uuid,'source-sha':commit,'runtime-version':'1.5.0-native2'};
    return fn(values, d);
  } finally { rmSync(d, {recursive:true,force:true}); }
}
const cliArgs = values => Object.entries(values).flatMap(([k,v]) => ['--'+k, v]);

test('positive argument gate accepts only explicit existing input and independently provided release identities',()=>{
  fixture(v => {
    const o = parseReleaseArgs(cliArgs(v));
    assert.equal(o.aab,v.aab);
    assert.equal(o['runtime-version'],'1.5.0-native2');
    assert.equal(o['expected-version-code'],'28');
  });
});
test('rejects old rejected AAB27 UUID, same runtime, and versionCode<=27',()=>{
  fixture(v => {
    assert.throws(()=>parseReleaseArgs(cliArgs({...v,'eas-build-id':'e3bf78a2-0410-4fe5-a279-e0af5624ca0d'})), /REJECTED_AAB27_BUILD_ID/);
    assert.throws(()=>parseReleaseArgs(cliArgs({...v,'runtime-version':'1.5.0-native1'})), /RUNTIME_MUST_ISOLATE_AAB27/);
    assert.throws(()=>parseReleaseArgs(cliArgs({...v,'expected-version-code':'27'})), /VERSION_CODE_NOT_NEWER_THAN_REJECTED_27/);
    assert.throws(()=>parseReleaseArgs(cliArgs({...v,'expected-version-code':'28.1'})), /VERSION_CODE_NOT_NEWER_THAN_REJECTED_27/);
  });
});
test('rejects missing source inputs, malformed hashes and bad package extension',()=>{
  fixture(v => {
    assert.throws(()=>parseReleaseArgs(cliArgs({...v,'aab-sha256':'short'})), /INVALID_AAB_SHA256/);
    assert.throws(()=>parseReleaseArgs(cliArgs({...v,'source-sha':'def'})), /INVALID_SOURCE_SHA/);
    assert.throws(()=>parseReleaseArgs(cliArgs({...v,'signer-sha256':'unknown'})), /INVALID_SIGNER_SHA256/);
    assert.throws(()=>parseReleaseArgs(cliArgs({...v,'aab':v.aab+'.zip'})), /INVALID_AAB_EXTENSION/);
    assert.throws(()=>parseReleaseArgs(cliArgs({...v,'aab':path.join(path.dirname(v.aab),'absent.aab')})), /MISSING_FILE_AAB/);
  });
});
test('rejects unknown/duplicate flags and missing gate conditions',()=>{
  fixture(v => {
    assert.throws(()=>parseReleaseArgs([]),/ARG_COUNT/);
    const a=cliArgs(v); a[0]='--untrusted';
    assert.throws(()=>parseReleaseArgs(a),/UNKNOWN_OR_DUPLICATE_FLAG/);
    const b=cliArgs(v);b[2]=b[0];
    assert.throws(()=>parseReleaseArgs(b),/UNKNOWN_OR_DUPLICATE_FLAG/);
  });
});
test('accepts only 16k BundleConfig and rejects mixed 4k flags',()=>{
  assert.equal(parseBundleConfig('native_library_compression { alignment: PAGE_ALIGNMENT_16K }'),'PAGE_ALIGNMENT_16K');
  assert.throws(()=>parseBundleConfig('PAGE_ALIGNMENT_4K'),/BUNDLE_CONFIG_NOT_16K/);
  assert.throws(()=>parseBundleConfig('PAGE_ALIGNMENT_16K PAGE_ALIGNMENT_4K'),/BUNDLE_CONFIG_NOT_16K/);
  assert.throws(()=>parseBundleConfig('empty'),/BUNDLE_CONFIG_NOT_16K/);
});
test('signed JAR output requires verification and no reported unsigned entries',()=>{
  assert.equal(verifySignedJarOutput('jar verified.\n'), 'JAR_SIGNATURE_VERIFIED');
  assert.throws(()=>verifySignedJarOutput('jar is unsigned.'),/AAB_SIGNATURE_NOT_VERIFIED/);
  assert.throws(()=>verifySignedJarOutput('jar verified.\nThis jar contains unsigned entries'),/AAB_UNSIGNED_ENTRIES/);
});
test('manifest version/package attributes must match exact release identity',()=>{
  assert.equal(parseManifestValue('com.ald1n.mobile\n','com.ald1n.mobile','PACKAGE'),'com.ald1n.mobile');
  assert.equal(parseManifestValue('28\n',28,'VERSION_CODE'),'28');
  assert.throws(()=>parseManifestValue('27',28,'VERSION_CODE'),/MANIFEST_VERSION_CODE_MISMATCH/);
  assert.throws(()=>parseManifestValue('another.mobile','com.ald1n.mobile','PACKAGE'),/MANIFEST_PACKAGE_MISMATCH/);
});
test('verified signing leaf certificate fingerprints are normalized and duplicates fail closed',()=>{
  const certificate = new X509Certificate(TEST_CERTIFICATE);
  assert.deepEqual(extractSignerFingerprints(TEST_CERTIFICATE),[certificate.fingerprint256.replaceAll(':','').toLowerCase()]);
  assert.throws(()=>extractSignerFingerprints(''),/SIGNER_CERT_COUNT_UNEXPECTED/);
  assert.throws(()=>extractSignerFingerprints(TEST_CERTIFICATE+'\n'+TEST_CERTIFICATE),/SIGNER_CERT_COUNT_UNEXPECTED/);
  assert.throws(()=>extractSignerFingerprints('-----BEGIN CERTIFICATE-----invalid-----END CERTIFICATE-----'),/SIGNER_CERT_UNREADABLE/);
});
test('SHA256 exact byte comparison uses streaming file reads',async()=>{
  const d=mkdtempSync(path.join(tmpdir(),'ald1n-b833-hash-'));
  try{const p=path.join(d,'aab.bin');writeFileSync(p,Buffer.from('real release bytes are never fabricated in CI fixtures'));
    assert.equal(await shaFile(p),createHash('sha256').update('real release bytes are never fabricated in CI fixtures').digest('hex'));
  } finally {rmSync(d,{recursive:true,force:true});}
});
test('missing real signed AAB returns JSON HOLD and exit 2, never a false PASS',()=>{
  const r=spawnSync(process.execPath,[cli],{encoding:'utf8'});
  assert.equal(r.status,2);
  assert.equal(JSON.parse(r.stdout).status,'HOLD');
  assert.equal(JSON.parse(r.stdout).code,'HOLD_ARG_COUNT');
});

// B834 default split/standalone delivery tests (real synthetic nested APK-set ZIPs).
function elf(align = 16384n) {
  const b = Buffer.alloc(512);
  b.set([0x7f, 0x45, 0x4c, 0x46, 2, 1, 1], 0);
  b.writeUInt16LE(3, 16);
  b.writeUInt16LE(183, 18);
  b.writeBigUInt64LE(64n, 32);
  b.writeUInt16LE(56, 54);
  b.writeUInt16LE(1, 56);
  b.writeUInt32LE(1, 64);
  b.writeBigUInt64LE(256n, 72);
  b.writeBigUInt64LE(256n, 80);
  b.writeBigUInt64LE(8n, 96);
  b.writeBigUInt64LE(8n, 104);
  b.writeBigUInt64LE(align, 112);
  return b;
}

function makeZip(output, cwd, names) {
  const r = spawnSync('zip', ['-q', '-X', '-r', output, ...names], { cwd, encoding: 'utf8' });
  assert.equal(r.status, 0, r.stderr);
}

function withApks(specs, action) {
  const dir = mkdtempSync(path.join(tmpdir(), 'ald1n-b834-default-apks-'));
  try {
    const root = path.join(dir, 'set');
    mkdirSync(root);
    for (const [i, spec] of specs.entries()) {
      const sourceDir = path.join(dir, `source-${i}`);
      mkdirSync(sourceDir);
      for (const [relative, contents] of Object.entries(spec.files)) {
        const destination = path.join(sourceDir, relative);
        mkdirSync(path.dirname(destination), { recursive: true });
        writeFileSync(destination, contents);
      }
      const apk = path.join(root, spec.name);
      mkdirSync(path.dirname(apk), { recursive: true });
      if (spec.corrupt) writeFileSync(apk, 'malformed APK that should be rejected');
      else makeZip(apk, sourceDir, Object.keys(spec.files).map(n => n.split('/')[0]).filter((v, k, all) => all.indexOf(v) === k));
    }
    writeFileSync(path.join(root, 'toc.pb'), 'synthetic fixture');
    const archive = path.join(dir, 'synthetic-default.apks');
    makeZip(archive, root, [...new Set(specs.map(s => s.name.split('/')[0])), 'toc.pb']);
    return action(archive);
  } finally { rmSync(dir, { recursive: true, force: true }); }
}

const goodNative = { 'lib/arm64-v8a/libnativegauss.so': elf(), 'assets/icon.txt': Buffer.from('ok') };
const badNative = { 'lib/arm64-v8a/libnativegauss.so': elf(4096n) };
const noNative = { 'assets/config.txt': Buffer.from('split-no-native-lib') };

test('selects sorted default split and standalone APKs, excludes toc', () => {
  assert.deepEqual(selectDeliveredApks(['toc.pb', 'splits/base-arm64_v8a.apk', 'standalones/standalone.apk', 'splits/base-master.apk']),
    ['splits/base-arm64_v8a.apk', 'splits/base-master.apk', 'standalones/standalone.apk']);
});
test('universal-only APK set is insufficient as default Play delivery evidence', () => {
  assert.throws(() => selectDeliveredApks(['universal.apk', 'toc.pb']), /DEFAULT_APKS_UNIVERSAL_ONLY/);
});
test('rejects duplicated APK ZIP entries', () => {
  assert.throws(() => selectDeliveredApks(['splits/base.apk', 'splits/base.apk']), /APKS_DUPLICATE_APK_ENTRIES/);
});
test('rejects unsafe traversal and absolute APK entry names', () => {
  assert.throws(() => selectDeliveredApks(['splits/../evil.apk']), /APKS_UNSAFE_ENTRY_NAME/);
  assert.throws(() => selectDeliveredApks(['/tmp/evil.apk']), /APKS_UNSAFE_ENTRY_NAME/);
});
test('rejects missing or malformed APKS entry inventory', () => {
  assert.throws(() => selectDeliveredApks(['toc.pb']), /APKS_NO_APK_ENTRIES/);
  assert.throws(() => selectDeliveredApks(null), /APKS_INVALID_ENTRY_LIST/);
});
test('actual nested default APK set: native configuration split plus native-free split accepted with bounded evidence', () => {
  withApks([{ name: 'splits/base-arm64_v8a.apk', files: goodNative }, { name: 'splits/base-master.apk', files: noNative }], file => {
    const result = auditDeliveredApkSet(file, '/bin/true', '/bin/true');
    assert.equal(result.status, 'PASS_DEFAULT_APK_SET_BINARY_ONLY');
    assert.equal(result.apkCount, 2);
    assert.equal(result.nativeApkCount, 1);
    assert.equal(result.checked64BitElf, 1);
    assert.equal(result.googlePlayAcceptance, 'NOT_CHECKED');
    assert.match(result.derivedApkSignature, /NOT_PLAY_SIGNER/);
  });
});
test('actual default APK set containing old 4KB nativegauss fails even with other split present', () => {
  withApks([{ name: 'splits/base-arm64_v8a.apk', files: badNative }, { name: 'splits/base-master.apk', files: noNative }], file => {
    assert.throws(() => auditDeliveredApkSet(file, '/bin/true', '/bin/true'), /DELIVERED_APK_ELF_NOT_16KB/);
  });
});
test('all native-free splits refuse a misleading 16KB binary PASS', () => {
  withApks([{ name: 'splits/base-master.apk', files: noNative }], file => {
    assert.throws(() => auditDeliveredApkSet(file, '/bin/true', '/bin/true'), /DEFAULT_APKS_WITHOUT_NATIVE64/);
  });
});
test('bad APK embedded within otherwise valid APKS fails closed', () => {
  withApks([{ name: 'splits/base-arm64_v8a.apk', files: goodNative }, { name: 'splits/bad.apk', files: noNative, corrupt: true }], file => {
    assert.throws(() => auditDeliveredApkSet(file, '/bin/true', '/bin/true'), /COMMAND_FAILED_UNZIP/);
  });
});
test('real ZIP input with good native ABI fails if zipalign rejects a split', () => {
  withApks([{ name: 'splits/base-arm64_v8a.apk', files: goodNative }], file => {
    assert.throws(() => auditDeliveredApkSet(file, '/bin/false', '/bin/true'), /COMMAND_FAILED_FALSE/);
  });
});
test('real ZIP input with good native ABI fails if apksigner rejects a split', () => {
  withApks([{ name: 'splits/base-arm64_v8a.apk', files: goodNative }], file => {
    assert.throws(() => auditDeliveredApkSet(file, '/bin/true', '/bin/false'), /COMMAND_FAILED_FALSE/);
  });
});
test('rejects absent APKS before any successful claim', () => {
  assert.throws(() => auditDeliveredApkSet('/file/does/not/exist.apks', '/bin/true', '/bin/true'), /COMMAND_FAILED_UNZIP/);
});
