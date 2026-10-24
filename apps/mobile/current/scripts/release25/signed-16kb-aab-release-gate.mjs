#!/usr/bin/env node
/** Source-only reusable POST-build gate. Never starts EAS, OTA, Play or a production deployment.
 * Consumes an existing independently authorized, signed AAB and externally attested build metadata.
 * PASS is BINARY-ONLY; it cannot prove the EAS source commit or physical-device/Play acceptance.
 */
import { spawnSync } from 'node:child_process';
import { createHash, X509Certificate } from 'node:crypto';
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
    jarArgs('build-apks', '--bundle=' + o.aab, '--output=' + apks, '--mode=universal');
    command('unzip', ['-tqq', apks]);
    const fd = openSync(apk, 'wx', 0o600);
    try { command('unzip', ['-p', apks, 'universal.apk'], { encoding: null, stdio: ['ignore', fd, 'pipe'] }); }
    finally { closeSync(fd); }
    command('unzip', ['-tqq', apk]);
    const derived = auditApk(apk);
    if (derived.status !== 'PASS_CI_DEBUG_APK_ELF_ONLY') hold('DERIVED_APK_ELF_16KB');
    command(o.zipalign, ['-c', '-P', '16', '-v', '4', apk]);
    command(o.apksigner, ['verify', apk]);
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
