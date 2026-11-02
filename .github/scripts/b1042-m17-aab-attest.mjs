#!/usr/bin/env node
// B1042: immutable SDK58 M17 EAS AAB provenance readback and bounded release-binary evidence.
// No EAS build, credentials mutation, OTA, Play submission or production host operation.
import fs from 'node:fs';
import path from 'node:path';
import { X509Certificate } from 'node:crypto';
import { fileURLToPath } from 'node:url';

export const EXPECTED = Object.freeze({
  source: '6adb3b440b8cfdc068b142a98c418d9d91487f64',
  profile: 'm17-sdk58-play-rc',
  package: 'com.ald1n.mobile',
  channel: 'production',
  runtime: '1.0.0-m17-sdk58-play2',
  appVersion: '1.0.0',
});
const UUID = /^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i;
const SHA256 = /^[a-f0-9]{64}$/i;
const SHA40 = /^[a-f0-9]{40}$/i;
export function hold(message) { throw new Error('HOLD_B1042_' + message); }

export function requireBuildId(raw) {
  if (typeof raw !== 'string' || !UUID.test(raw)) hold('INVALID_BUILD_UUID');
  return raw.toLowerCase();
}
export function selectFromHistory(data) {
  const rows = Array.isArray(data) ? data : Array.isArray(data?.builds) ? data.builds : null;
  if (!rows) hold('HISTORY_SHAPE_INVALID');
  if (rows.length === 0) hold('MATCHING_M17_BUILD_NOT_FOUND');
  if (rows.length >= 50) hold('HISTORY_PAGE_LIMIT_REACHED');
  if (rows.length !== 1) hold('MULTIPLE_M17_BUILDS_FOUND');
  return requireBuildId(rows[0]?.id);
}
export function artifactUrl(build) {
  const raw = build?.artifacts?.buildUrl;
  if (typeof raw !== 'string') hold('NO_SIGNED_AAB_URL');
  let url;
  try { url = new URL(raw); } catch { hold('INVALID_ARTIFACT_URL'); }
  if (url.protocol !== 'https:' || url.hostname !== 'expo.dev' || url.port ||
      url.username || url.password || url.hash ||
      !/^\/artifacts\/eas\/[a-zA-Z0-9._-]+\.aab$/.test(url.pathname)) hold('ARTIFACT_URL_UNTRUSTED');
  return raw;
}
export function signedBuildMetadata(build, chosenId, expected = EXPECTED) {
  if (!build || typeof build !== 'object' || Array.isArray(build)) hold('BUILD_VIEW_SHAPE_INVALID');
  if (requireBuildId(build.id) !== requireBuildId(chosenId)) hold('BUILD_ID_READBACK_MISMATCH');
  if (String(build.platform) !== 'ANDROID') hold('BUILD_PLATFORM_MISMATCH');
  if (build.buildProfile !== expected.profile) hold('BUILD_PROFILE_MISMATCH');
  if (!SHA40.test(build.gitCommitHash ?? '') || build.gitCommitHash.toLowerCase() !== expected.source) hold('SOURCE_COMMIT_UNATTESTED');
  if (build.status !== 'FINISHED') hold('BUILD_NOT_FINISHED');
  if (build.appVersion !== expected.appVersion) hold('APP_VERSION_MISMATCH');
  const rawCode = build.appBuildVersion;
  if (!/^[0-9]+$/.test(String(rawCode ?? '')) || !Number.isSafeInteger(Number(rawCode)) || Number(rawCode) <= 28) hold('VERSION_CODE_NOT_GREATER_THAN_28');
  if (build.channel != null && build.channel !== expected.channel) hold('EAS_CHANNEL_MISMATCH');
  if (build.runtimeVersion != null && build.runtimeVersion !== expected.runtime) hold('EAS_RUNTIME_MISMATCH');
  if (build.appIdentifier != null && build.appIdentifier !== expected.package) hold('EAS_PACKAGE_MISMATCH');
  return {
    privateArtifactUrl: artifactUrl(build),
    receipt: {
      batch: 'B1042',
      phase: 'SIGNED_AAB_BINARY_NOT_YET_CHECKED',
      buildId: build.id.toLowerCase(),
      status: build.status,
      platform: build.platform,
      sourceSha: build.gitCommitHash.toLowerCase(),
      profile: build.buildProfile,
      package: expected.package,
      appVersion: build.appVersion,
      versionCode: Number(rawCode),
      expectedRuntime: expected.runtime,
      observedRuntime: build.runtimeVersion ?? null,
      declaredChannel: expected.channel,
      observedChannel: build.channel ?? null,
      artifactUrlPresent: true,
      signerContinuityWithPlayUploadCertificate: 'NOT_ATTESTED_NEEDS_PLAY_REFERENCE',
      signedAabSha256: null,
      signedAabCertSha256: null,
      binary16kb: 'NOT_CHECKED',
      devicePageSize16384: 'NOT_CHECKED',
      nativeGoogleLoginUat: 'NOT_CHECKED',
      playConsoleStatus: 'NOT_CHECKED',
      newEasBuild: 'NONE',
      playSubmit: 'NONE',
      ota: 'NONE',
      cloudLinuxOrCmsMutation: 'NONE'
    }
  };
}
export function certificateFingerprint(raw) {
  if (typeof raw !== 'string') hold('CERTIFICATE_OUTPUT_INVALID');
  const blocks = raw.match(/-----BEGIN CERTIFICATE-----[\s\S]*?-----END CERTIFICATE-----/g) ?? [];
  if (blocks.length !== 1) hold('SIGNER_CERTIFICATE_COUNT_INVALID');
  let digest;
  try { digest = new X509Certificate(blocks[0]).fingerprint256.replaceAll(':', '').toLowerCase(); }
  catch { hold('SIGNER_CERTIFICATE_INVALID'); }
  if (!SHA256.test(digest)) hold('SIGNER_SHA_INVALID');
  return digest;
}
export function withBinaryResult(receipt, gate) {
  if (!receipt || !gate || gate.status !== 'PASS_SIGNED_AAB_BINARY_GATE_ONLY' ||
      gate.easBuildId !== receipt.buildId || gate.versionCode !== receipt.versionCode ||
      gate.claimedSourceCommit !== receipt.sourceSha || gate.package !== receipt.package ||
      gate.appVersion !== receipt.appVersion || gate.runtimeVersion !== receipt.expectedRuntime ||
      gate.bundleConfig !== 'PAGE_ALIGNMENT_16K' ||
      gate.defaultDeliveryApks?.status !== 'PASS_DEFAULT_APK_SET_BINARY_ONLY' ||
      !SHA256.test(gate.signedAabSha256 ?? '') || !SHA256.test(gate.signingCertificateSha256 ?? '')) {
    hold('BINARY_GATE_UNTRUSTED_OR_MISMATCH');
  }
  return {
    ...receipt,
    phase: 'PASS_SIGNED_AAB_BINARY_ONLY',
    signedAabSha256: gate.signedAabSha256,
    signedAabCertSha256: gate.signingCertificateSha256,
    binary16kb: 'PASS_ELF_ZIP_AAB_BUNDLETOOL_AND_DEFAULT_DELIVERY_APKS',
    bundleConfig: gate.bundleConfig,
    aab64BitElfCount: gate.aab64BitElfCount,
    derivedApkCount: gate.defaultDeliveryApks.apkCount,
    derivedApkNativeCount: gate.defaultDeliveryApks.nativeApkCount,
    derivedApk64BitElfCount: gate.defaultDeliveryApks.checked64BitElf,
    derivedApkSigning: 'DISPOSABLE_TEST_KEY_NOT_PLAY_APP_SIGNING_CERT',
    signerContinuityWithPlayUploadCertificate: 'NOT_ATTESTED_NEEDS_PLAY_REFERENCE',
    devicePageSize16384: 'NOT_CHECKED',
    nativeGoogleLoginUat: 'NOT_CHECKED',
    playConsoleStatus: 'NOT_CHECKED',
    finalVerdict: 'PASS_BINARY_ONLY_RELEASE_AND_DEVICE_ACCEPTANCE_OPEN'
  };
}
function readJson(file) {
  const stat = fs.statSync(file);
  if (!stat.isFile() || stat.size > 5_000_000 || stat.size === 0) hold('JSON_FILE_MISSING_OR_OVERSIZED');
  let obj;
  try { obj = JSON.parse(fs.readFileSync(file, 'utf8')); }
  catch { hold('JSON_PARSE_FAILED'); }
  return obj;
}
function writePrivate(file, data) {
  fs.writeFileSync(file, data, { mode: 0o600, flag: 'wx' });
}
function runCli(args) {
  const [mode, p1, p2, p3, p4] = args;
  if (mode === 'select-id' && p1 && p2 && args.length === 3) {
    writePrivate(p2, requireBuildId(p1) + '\n');
  } else if (mode === 'select-history' && p1 && p2 && args.length === 3) {
    writePrivate(p2, selectFromHistory(readJson(p1)) + '\n');
  } else if (mode === 'verify-view' && p1 && p2 && p3 && p4 && args.length === 5) {
    const id = fs.readFileSync(p2,'utf8').trim();
    const data = signedBuildMetadata(readJson(p1), id);
    writePrivate(p3, data.privateArtifactUrl + '\n');
    writePrivate(p4, JSON.stringify(data.receipt,null,2)+'\n');
    process.stdout.write(`B1042_EAS_FINISHED_BUILD_ID=${data.receipt.buildId}\nB1042_EAS_ACTUAL_VERSION_CODE=${data.receipt.versionCode}\n`);
    fs.appendFileSync(process.env.GITHUB_ENV, `B1042_BUILD_ID=${data.receipt.buildId}\nB1042_VERSION_CODE=${data.receipt.versionCode}\n`);
  } else if (mode === 'cert-fingerprint' && p1 && args.length === 2) {
    process.stdout.write(certificateFingerprint(fs.readFileSync(p1, 'utf8'))+'\n');
  } else if (mode === 'complete' && p1 && p2 && p3 && args.length === 4) {
    const result = withBinaryResult(readJson(p1), readJson(p2));
    writePrivate(p3, JSON.stringify(result,null,2)+'\n');
    process.stdout.write(`B1042_BINARY_16KB=${result.binary16kb}\nB1042_SIGNED_AAB_SHA256=${result.signedAabSha256}\nB1042_SIGNER_SHA256=${result.signedAabCertSha256}\nB1042_PHYSICAL_DEVICE_GOOGLE_LOGIN_PLAY=NOT_CHECKED\n`);
  } else { hold('CLI_ARGUMENTS_INVALID'); }
}
if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  try { runCli(process.argv.slice(2)); }
  catch(e) { console.error(e instanceof Error ? e.message : 'HOLD_B1042_UNKNOWN'); process.exitCode = 2; }
}
