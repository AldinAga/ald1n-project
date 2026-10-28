#!/usr/bin/env node
/** Source-only reusable POST-build gate. Never starts EAS, OTA, Play or a production deployment.
 * Consumes an existing independently authorized, signed AAB and externally attested build metadata.
 * PASS is BINARY-ONLY; it cannot prove the EAS source commit or physical-device/Play acceptance.
 */
import { spawnSync } from 'node:child_process';
import { createHash, randomBytes, X509Certificate } from 'node:crypto';
import { createReadStream, existsSync, mkdtempSync, openSync, closeSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { auditAab } from './verify-android16kb-aab.mjs';
import { auditApk } from './verify-android16kb-apk.mjs';

const EXPECTED_PACKAGE = 'com.ald1n.mobile';
const EXPECTED_VERSION = '1.5.0';
const EXPECTED_RUNTIME = '1.5.0-native2';
const REJECTED_BUILD_ID = 'e3bf78a2-0410-4fe5-a279-e0af5624ca0d';
const BUNDLETOOL_SHA = 'a099cfa1543f55593bc2ed16a70a7c67fe54b1747bb7301f37fdfd6d91028e29';
const REQUIRED = Object.freeze(['aab', 'aab-sha256', 'signer-sha256', 'bundletool', 'zipalign', 'apksigner', 'expected-version-code', 'eas-build-id', 'source-sha', 'runtime-version']);

function hold(reason) { throw new Error('HOLD_' + reason); }
export function parseReleaseArgs(argv) {
  if (!Array.isArray(argv) || argv.length !== REQUIRED.length * 2) hold('ARG_COUNT');
  const out = Object.create(null);
  for (let i = 0; i < argv.length; i += 2) {
    const flag = argv[i];
    if (!flag?.startsWith('--') || !REQUIRED.includes(flag.slice(2)) || out[flag.slice(2)] !== undefined) hold('UNKNOWN_OR_DUPLICATE_FLAG');
    out[flag.slice(2)] = argv[i + 1];
  }
  for (const k of REQUIRED) if (!out[k]) hold('MISSING_' + k.replaceAll('-', '_').toUpperCase());
  for (const k of ['aab-sha256', 'signer-sha256']) {
    if (!/^[a-fA-F0-9]{64}$/.test(out[k])) hold('INVALID_' + k.replaceAll('-', '_').toUpperCase());
    out[k] = out[k].toLowerCase();
  }
  if (!/^[a-fA-F0-9]{40}$/.test(out['source-sha'])) hold('INVALID_SOURCE_SHA');
  if (!/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i.test(out['eas-build-id'])) hold('INVALID_EAS_BUILD_ID');
  if (out['eas-build-id'].toLowerCase() === REJECTED_BUILD_ID) hold('REJECTED_AAB27_BUILD_ID');
  if (!/^[0-9]+$/.test(out['expected-version-code']) || !Number.isSafeInteger(Number(out['expected-version-code'])) || Number(out['expected-version-code']) <= 27) hold('VERSION_CODE_NOT_NEWER_THAN_REJECTED_27');
  if (out['runtime-version'] !== EXPECTED_RUNTIME) hold('RUNTIME_MUST_ISOLATE_AAB27');
  if (!out.aab.endsWith('.aab')) hold('INVALID_AAB_EXTENSION');
  for (const k of ['aab', 'bundletool', 'zipalign', 'apksigner']) if (!existsSync(out[k])) hold('MISSING_FILE_' + k.toUpperCase());
  return out;
}

export function parseBundleConfig(output) {
  if (typeof output !== 'string' || !output.includes('PAGE_ALIGNMENT_16K') || output.includes('PAGE_ALIGNMENT_4K')) hold('BUNDLE_CONFIG_NOT_16K');
  return 'PAGE_ALIGNMENT_16K';
}
export function parseManifestValue(output, expected, label) {
  if (typeof output !== 'string' || output.trim() !== String(expected)) hold('MANIFEST_' + label + '_MISMATCH');
  return output.trim();
}
export function verifySignedJarOutput(output) {
  if (typeof output !== 'string' || !/jar verified\./i.test(output)) hold('AAB_SIGNATURE_NOT_VERIFIED');
  if (/unsigned entries|not signed by any signer/i.test(output)) hold('AAB_UNSIGNED_ENTRIES');
  return 'JAR_SIGNATURE_VERIFIED';
}
export function extractSignerFingerprints(keytoolOutput) {
  const certs = String(keytoolOutput).match(/-----BEGIN CERTIFICATE-----[\s\S]*?-----END CERTIFICATE-----/g) ?? [];
  if (certs.length !== 1) hold('SIGNER_CERT_COUNT_UNEXPECTED');
  try { return [new X509Certificate(certs[0]).fingerprint256.replaceAll(':', '').toLowerCase()]; }
  catch { hold('SIGNER_CERT_UNREADABLE'); }
}

export async function shaFile(file) {
  return await new Promise((resolve, reject) => {
    const h = createHash('sha256');
    const stream = createReadStream(file);
    stream.on('data', chunk => h.update(chunk));
    stream.on('error', reject);
    stream.on('end', () => resolve(h.digest('hex')));
  });
}
function command(program, args, options = {}) {
  const r = spawnSync(program, args, { encoding: options.binary ? null : 'utf8', timeout: 300000, maxBuffer: 64 * 1024 * 1024, ...options });
  if (r.error || r.status !== 0) hold('COMMAND_FAILED_' + path.basename(program).replace(/[^a-z0-9]/gi, '_').toUpperCase());
  return r.stdout;
}
/** bundletool does not promise an APK signer unless one is supplied.  The
 * original AAB signer is checked separately.  Derived APKs get a disposable,
 * locally generated CI-only key, removed together with the temporary outputs.
 */
export function createEphemeralApkSigning(tmp, run = command, password = randomBytes(24).toString('hex')) {
  if (!/^[0-9a-zA-Z]{12,100}$/.test(password)) hold('INVALID_EPHEMERAL_SIGNING_PASSWORD');
  const ks = path.join(tmp, 'ald1n-ci-derived-apks.p12');
  const alias = 'ald1n_ci_apk_audit_only';
  run('keytool', ['-genkeypair', '-noprompt', '-storetype', 'PKCS12',
    '-keystore', ks, '-storepass', password, '-keypass', password,
    '-alias', alias, '-keyalg', 'RSA', '-keysize', '3072', '-validity', '1',
    '-dname', 'CN=Ald1n Ephemeral CI Test Only, OU=Android QA, O=Ald1n, C=RS']);
  return {
    args: ['--ks=' + ks, '--ks-pass=pass:' + password,
      '--ks-key-alias=' + alias, '--key-pass=pass:' + password],
    role: 'EPHEMERAL_LOCAL_BUNDLETOOL_SIGNING_NOT_GOOGLE_PLAY'
  };
}

/** Play delivery uses split/standalone APKs, not only the universal smoke APK.
 * Inspect every packaged APK; native-free configuration splits are normal.
 */
export function selectDeliveredApks(names) {
  if (!Array.isArray(names) || names.length > 8192) hold('APKS_INVALID_ENTRY_LIST');
  const matches = names.filter(n => typeof n === 'string' && n.endsWith('.apk'));
  if (!matches.length) hold('APKS_NO_APK_ENTRIES');
  if (matches.length > 2048) hold('APKS_TOO_MANY_APK_ENTRIES');
  if (new Set(matches).size !== matches.length) hold('APKS_DUPLICATE_APK_ENTRIES');
  for (const name of matches) {
    if (!/^[A-Za-z0-9._\/-]+\.apk$/.test(name) ||
        name.split('/').some(segment => segment === '' || segment === '.' || segment === '..')) {
      hold('APKS_UNSAFE_ENTRY_NAME');
    }
  }
  if (matches.length === 1 && matches[0] === 'universal.apk') hold('DEFAULT_APKS_UNIVERSAL_ONLY');
  return matches.sort();
}

/** Called only after bundletool produced the APKS file from the verified AAB.
 * Executable checks are mandatory for each generated APK (including native-free).
 * Do not treat signatures on locally derived APKs as Google Play signing proof.
 */
export function auditDeliveredApkSet(apksPath, zipalign, apksigner) {
  command('unzip', ['-tqq', apksPath]);
  const entries = selectDeliveredApks(command('unzip', ['-Z1', apksPath]).split(/\r?\n/).filter(Boolean));
  const tmp = mkdtempSync(path.join(tmpdir(), 'ald1n-16kb-split-apks-'));
  let nativeApks = 0;
  let elfCount = 0;
  try {
    for (let i = 0; i < entries.length; i += 1) {
      const apk = path.join(tmp, `${i}.apk`);
      const fd = openSync(apk, 'wx', 0o600);
      try { command('unzip', ['-p', apksPath, entries[i]], { encoding: null, stdio: ['ignore', fd, 'pipe'] }); }
      finally { closeSync(fd); }
      command('unzip', ['-tqq', apk]);
      const members = command('unzip', ['-Z1', apk]).split(/\r?\n/).filter(Boolean);
      if (members.some(name => /^lib\/(?:arm64-v8a|x86_64)\/[^/]+\.so$/.test(name))) {
        const scan = auditApk(apk);
        if (scan.status !== 'PASS_CI_DEBUG_APK_ELF_ONLY') hold('DELIVERED_APK_ELF_NOT_16KB');
        nativeApks += 1;
        elfCount += scan.checkedCount;
      }
      command(zipalign, ['-c', '-P', '16', '-v', '4', apk]);
      command(apksigner, ['verify', apk]);
    }
    if (!nativeApks) hold('DEFAULT_APKS_WITHOUT_NATIVE64');
    return {
      status: 'PASS_DEFAULT_APK_SET_BINARY_ONLY',
      apkCount: entries.length,
      nativeApkCount: nativeApks,
      checked64BitElf: elfCount,
      derivedApkSignature: 'LOCAL_BUNDLETOOL_SIGNATURE_NOT_PLAY_SIGNER',
      physical16kbUat: 'NOT_CHECKED',
      googlePlayAcceptance: 'NOT_CHECKED'
    };
  } finally { rmSync(tmp, { recursive: true, force: true }); }
}

async function releaseGate(o) {
  const hash = await shaFile(o.aab);
  if (hash !== o['aab-sha256']) hold('AAB_SHA256_NOT_ATTESTED');
  if (await shaFile(o.bundletool) !== BUNDLETOOL_SHA) hold('BUNDLETOOL_SHA256_MISMATCH');

  command('unzip', ['-tqq', o.aab]);
  const signature = command('jarsigner', ['-verify', '-verbose', '-certs', o.aab]);
  verifySignedJarOutput(signature);
  const actualSigner = extractSignerFingerprints(command('keytool', ['-printcert', '-rfc', '-jarfile', o.aab]))[0];
  if (actualSigner !== o['signer-sha256']) hold('AAB_SIGNER_SHA256_MISMATCH');

  const aab = auditAab(o.aab);
  if (aab.status !== 'PASS_CI_DISPOSABLE_AAB_ELF_ONLY') hold('AAB_ELF_16KB');
  const jarArgs = (...args) => command('java', ['-jar', o.bundletool, ...args]);
  parseBundleConfig(jarArgs('dump', 'config', '--bundle=' + o.aab));
  parseManifestValue(jarArgs('dump', 'manifest', '--bundle=' + o.aab, '--xpath=/manifest/@package'), EXPECTED_PACKAGE, 'PACKAGE');
  parseManifestValue(jarArgs('dump', 'manifest', '--bundle=' + o.aab, '--xpath=/manifest/@android:versionName'), EXPECTED_VERSION, 'VERSION_NAME');
  const versionCode = Number(o['expected-version-code']);
  parseManifestValue(jarArgs('dump', 'manifest', '--bundle=' + o.aab, '--xpath=/manifest/@android:versionCode'), versionCode, 'VERSION_CODE');

  const tmp = mkdtempSync(path.join(tmpdir(), 'ald1n-signed-16kb-check-'));
  try {
    const apks = path.join(tmp, 'derived.apks');
    const apk = path.join(tmp, 'derived.apk');
    const debugSigning = createEphemeralApkSigning(tmp);
    jarArgs('build-apks', '--bundle=' + o.aab, '--output=' + apks, '--mode=universal', ...debugSigning.args);
    command('unzip', ['-tqq', apks]);
    const fd = openSync(apk, 'wx', 0o600);
    try { command('unzip', ['-p', apks, 'universal.apk'], { encoding: null, stdio: ['ignore', fd, 'pipe'] }); }
    finally { closeSync(fd); }
    command('unzip', ['-tqq', apk]);
    const derived = auditApk(apk);
    if (derived.status !== 'PASS_CI_DEBUG_APK_ELF_ONLY') hold('DERIVED_APK_ELF_16KB');
    command(o.zipalign, ['-c', '-P', '16', '-v', '4', apk]);
    command(o.apksigner, ['verify', apk]);
    // The universal APK omits unfused dynamic features. Check bundletool's
    // actual default split/standalone delivery outputs as a separate gate.
    const defaultApks = path.join(tmp, 'derived-default.apks');
    jarArgs('build-apks', '--bundle=' + o.aab, '--output=' + defaultApks, ...debugSigning.args);
    const delivery = auditDeliveredApkSet(defaultApks, o.zipalign, o.apksigner);
    return {
      status: 'PASS_SIGNED_AAB_BINARY_GATE_ONLY',
      appVersion: EXPECTED_VERSION,
      package: EXPECTED_PACKAGE,
      versionCode,
      runtimeVersion: EXPECTED_RUNTIME,
      signedAabSha256: hash,
      signingCertificateSha256: actualSigner,
      aab64BitElfCount: aab.checkedCount,
      derivedApk64BitElfCount: derived.checkedCount,
      bundleConfig: 'PAGE_ALIGNMENT_16K',
      derivedApk: 'BUNDLETOOL_SAME_AAB_PROVENANCE_AND_ZIPALIGN_PASS',
      defaultDeliveryApks: delivery,
      derivedApkSigning: debugSigning.role,
      easBuildId: o['eas-build-id'],
      claimedSourceCommit: o['source-sha'],
      easSourceAndRuntimeProvenance: 'DECLARED_NOT_INDEPENDENTLY_VERIFIED',
      physical16kbUat: 'NOT_CHECKED',
      googlePlayEligibility: 'NOT_CHECKED',
      productionMutation: 'NONE'
    };
  } finally { rmSync(tmp, { recursive: true, force: true }); }
}

if (process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1]) {
  try {
    const input = parseReleaseArgs(process.argv.slice(2));
    releaseGate(input).then(r => console.log(JSON.stringify(r, null, 2))).catch(e => {
      console.log(JSON.stringify({ status: 'HOLD', code: e instanceof Error ? e.message : 'HOLD_UNEXPECTED' }));
      process.exitCode = 2;
    });
  } catch (e) {
    console.log(JSON.stringify({ status: 'HOLD', code: e instanceof Error ? e.message : 'HOLD_UNEXPECTED' }));
    process.exitCode = 2;
  }
}
