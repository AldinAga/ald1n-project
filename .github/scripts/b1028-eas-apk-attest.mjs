import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import crypto from 'node:crypto';
import { spawnSync } from 'node:child_process';

const fail = (message) => { throw new Error(message); };
const safeInteger = (value) => {
  if (value === null || value === undefined || value === '') return null;
  const n = Number(value);
  return Number.isSafeInteger(n) && n > 0 ? n : null;
};

export function validateMetadata(build, expected) {
  if (!build || typeof build !== 'object' || Array.isArray(build)) fail('HOLD_EAS_BUILD_JSON_UNTRUSTED');
  if (build.id !== expected.id) fail('HOLD_EAS_BUILD_ID_CHANGED');
  if (build.platform && String(build.platform).toUpperCase() !== 'ANDROID') fail('HOLD_EAS_PLATFORM_MISMATCH');
  if (build.buildProfile && build.buildProfile !== expected.profile) fail('HOLD_EAS_PROFILE_MISMATCH');
  if (build.gitCommitHash && build.gitCommitHash !== expected.source) fail('HOLD_EAS_SOURCE_SHA_MISMATCH');
  if (build.channel && build.channel !== expected.channel) fail('HOLD_EAS_CHANNEL_MISMATCH');
  if (build.runtimeVersion && build.runtimeVersion !== expected.runtime) fail('HOLD_EAS_RUNTIME_MISMATCH');
  if (build.appVersion && build.appVersion !== expected.appVersion) fail('HOLD_EAS_APP_VERSION_MISMATCH');
  const accepted = new Set(['NEW','IN_QUEUE','IN_PROGRESS','FINISHED','ERRORED','CANCELED']);
  if (!accepted.has(build.status)) fail('HOLD_EAS_BUILD_STATUS_UNKNOWN');
  const code = safeInteger(build.appBuildVersion);
  if (build.appBuildVersion !== null && build.appBuildVersion !== undefined && code === null)
    fail('HOLD_EAS_INVALID_REMOTE_VERSION_CODE');
  let artifactUrl = null;
  if (build.status === 'FINISHED') {
    const url = build.artifacts?.buildUrl;
    if (typeof url !== 'string') fail('HOLD_EAS_FINISHED_NO_APK_URL');
    let parsed;
    try { parsed = new URL(url); } catch { fail('HOLD_EAS_ARTIFACT_URL_INVALID'); }
    if (parsed.protocol !== 'https:' || parsed.hostname !== 'expo.dev'
        || !parsed.pathname.startsWith('/artifacts/eas/') || !parsed.pathname.endsWith('.apk')
        || parsed.username || parsed.password) fail('HOLD_EAS_APK_URL_NOT_ALLOWED');
    artifactUrl = url;
  }
  const receipt = {
    type: 'B1028_SIGNED_EAS_APK_READ_ONLY',
    buildId: build.id,
    status: build.status,
    platform: build.platform ?? null,
    expectedSourceSha: expected.source,
    observedSourceSha: build.gitCommitHash ?? null,
    sourceShaObservation: build.gitCommitHash ? 'EAS_VIEW_MATCHED' : 'NOT_EXPOSED_BY_EAS_VIEW',
    expectedBuildProfile: expected.profile,
    observedBuildProfile: build.buildProfile ?? null,
    expectedChannel: expected.channel,
    observedChannel: build.channel ?? null,
    expectedRuntime: expected.runtime,
    observedRuntime: build.runtimeVersion ?? null,
    declaredAppVersion: expected.appVersion,
    observedAppVersion: build.appVersion ?? null,
    versionCodeFromEas: code,
    artifactUrlPresent: Boolean(artifactUrl),
    artifactSha256: 'NOT_CHECKED',
    apkCertificateSha256: 'NOT_CHECKED',
    signedApkNative16KiB: 'NOT_CHECKED',
    physical16KiBPageSize: 'NOT_CHECKED',
    realNativeGoogleSignIn: 'NOT_CHECKED',
    productionOrPlayDeployment: 'NONE',
    verdict: build.status === 'FINISHED' ? 'BINARY_INSPECTION_PENDING' : 'EAS_BUILD_NOT_READY_FOR_APK_UAT',
  };
  return { receipt, artifactUrl };
}

export function parseAapt(text) {
  const line = text.split(/\r?\n/).find(x => x.startsWith('package: '));
  const m = line?.match(/\bname='([^']+)'\s+versionCode='(\d+)'\s+versionName='([^']+)'/);
  if (!m) fail('HOLD_APK_BADGING_UNTRUSTED');
  const code = safeInteger(m[2]);
  if (!code) fail('HOLD_APK_VERSION_CODE_INVALID');
  return { packageName: m[1], versionCode: code, versionName: m[3] };
}

export function parseSigner(text) {
  const m = text.match(/Signer #1 certificate SHA-256 digest:\s*([a-fA-F0-9]{64})\b/);
  if (!m) fail('HOLD_APK_SIGNING_CERT_UNKNOWN');
  const v2 = /Verified using v2 scheme \(APK Signature Scheme v2\):\s*true/.test(text);
  const v3 = /Verified using v3 scheme \(APK Signature Scheme v3\):\s*true/.test(text);
  if (!v2 && !v3) fail('HOLD_APK_MODERN_SIGNING_SCHEME_MISSING');
  return m[1].toLowerCase();
}

export function parseElfLoadSegments(text) {
  const lines = text.split(/\r?\n/).filter(x => /^\s*LOAD\s+/.test(x));
  if (!lines.length) fail('HOLD_NATIVE_LIBRARY_NO_LOAD_SEGMENTS');
  return lines.map(line => {
    const parts = line.trim().split(/\s+/);
    if (parts.length < 7) fail('HOLD_NATIVE_ELF_HEADER_UNTRUSTED');
    let off, vaddr, align;
    try {
      off = BigInt(parts[1]);
      vaddr = BigInt(parts[2]);
      align = BigInt(parts[parts.length - 1]);
    } catch { fail('HOLD_NATIVE_ELF_HEADER_UNTRUSTED'); }
    if (align < 16384n || off % 16384n !== vaddr % 16384n)
      fail('HOLD_SIGNED_APK_NATIVE_ELF_NOT_16KIB_COMPATIBLE');
    return { offset: Number(off), virtualAddress: Number(vaddr), alignment: Number(align) };
  });
}

function expectedFromEnvironment() {
  const v = process.env;
  const expected = {
    id: v.M17_BUILD_ID,
    source: v.M17_SOURCE_SHA,
    profile: 'm17-sdk58-uat',
    runtime: '1.0.0-m17-sdk58-uat1',
    channel: 'm17-sdk58-uat',
    appVersion: '1.0.0',
    package: 'com.ald1n.mobile.preview'
  };
  assert.match(expected.id ?? '', /^[a-f0-9-]{36}$/i);
  assert.match(expected.source ?? '', /^[a-f0-9]{40}$/);
  return expected;
}

function command(bin, args, opts = {}) {
  const result = spawnSync(bin, args, { encoding: 'utf8', maxBuffer: 1024 * 1024 * 30, ...opts });
  if (result.status !== 0 || result.error) fail(`HOLD_APK_VERIFIER_TOOL_FAILURE_${path.basename(bin)}`);
  return result.stdout;
}

async function main() {
  const [mode, viewPath, receiptPath, auxiliaryPath] = process.argv.slice(2);
  if (!['metadata', 'apk'].includes(mode) || !viewPath || !receiptPath) fail('HOLD_B1028_ARGUMENTS');
  const expected = expectedFromEnvironment();
  const b = JSON.parse(fs.readFileSync(viewPath, 'utf8'));
  const { receipt, artifactUrl } = validateMetadata(b, expected);
  if (mode === 'metadata') {
    if (!auxiliaryPath) fail('HOLD_B1028_NO_TEMP_URL_FILE');
    fs.writeFileSync(receiptPath, JSON.stringify(receipt, null, 2) + '\n', { mode: 0o600 });
    if (artifactUrl) fs.writeFileSync(auxiliaryPath, artifactUrl + '\n', { mode: 0o600 });
    const output = process.env.GITHUB_OUTPUT;
    if (output) fs.appendFileSync(output, `build_status=${receipt.status}\n`);
    console.log('B1028_EAS_BUILD_ID=' + receipt.buildId);
    console.log('B1028_EAS_STATUS=' + receipt.status);
    console.log('B1028_EAS_REMOTE_VERSION_CODE=' + (receipt.versionCodeFromEas ?? 'NOT_EXPOSED'));
    console.log('B1028_SIGNED_APK_ATTESTATION=' + receipt.verdict);
    return;
  }
  if (receipt.status !== 'FINISHED') fail('HOLD_EAS_APK_NOT_FINISHED');
  if (!auxiliaryPath || !fs.existsSync(auxiliaryPath)) fail('HOLD_SIGNED_APK_NOT_DOWNLOADED');
  const apk = auxiliaryPath;
  const badging = parseAapt(command(process.env.AAPT_BIN ?? 'aapt', ['dump', 'badging', apk]));
  if (badging.packageName !== expected.package || badging.versionName !== expected.appVersion)
    fail('HOLD_SIGNED_APK_PACKAGE_OR_VERSION_MISMATCH');
  if (receipt.versionCodeFromEas && receipt.versionCodeFromEas !== badging.versionCode)
    fail('HOLD_EAS_VERSUS_APK_VERSION_CODE_MISMATCH');
  const cert = parseSigner(command(process.env.APKSIGNER_BIN ?? 'apksigner', ['verify', '--verbose', '--print-certs', apk]));
  command(process.env.ZIPALIGN_BIN ?? 'zipalign', ['-c', '-P', '16', '-v', '4', apk]);
  const all = command('unzip', ['-Z1', apk]).split(/\r?\n/).filter(Boolean);
  const libs = all.filter(x => /^lib\/(arm64-v8a|x86_64|riscv64)\/[a-zA-Z0-9._-]+\.so$/.test(x));
  if (!libs.length) fail('HOLD_NO_64BIT_NATIVE_ELF_IN_SIGNED_APK');
  const scratch = fs.mkdtempSync(path.join(os.tmpdir(), 'b1028-native-'));
  let count = 0, segments = 0;
  try {
    for (const item of libs) {
      const bytes = spawnSync('unzip', ['-p', apk, item], { maxBuffer: 1024 * 1024 * 256 });
      if (bytes.status !== 0 || !bytes.stdout?.length) fail('HOLD_CANNOT_EXTRACT_SIGNED_NATIVE_ELF');
      const file = path.join(scratch, 'native.so');
      fs.writeFileSync(file, bytes.stdout, { mode: 0o600 });
      const readout = command('readelf', ['-W', '-l', file]);
      segments += parseElfLoadSegments(readout).length;
      count += 1;
    }
  } finally {
    fs.rmSync(scratch, { recursive: true, force: true });
  }
  const sha = crypto.createHash('sha256');
  for await (const chunk of fs.createReadStream(apk)) sha.update(chunk);
  receipt.artifactSha256 = sha.digest('hex');
  receipt.apkCertificateSha256 = cert;
  receipt.versionCodeFromSignedApk = badging.versionCode;
  receipt.signedApkNative16KiB = 'PASS_SCOPED_64BIT_ELF_AND_ZIP_ALIGNMENT';
  receipt.audited64BitSharedLibraries = count;
  receipt.auditedLoadSegments = segments;
  receipt.verdict = 'SIGNED_INTERNAL_APK_STATIC_PROVENANCE_PASS_DEVICE_UAT_OPEN';
  fs.writeFileSync(receiptPath, JSON.stringify(receipt, null, 2) + '\n', { mode: 0o600 });
  console.log('B1028_SIGNED_APK_SHA256=' + receipt.artifactSha256);
  console.log('B1028_SIGNED_APK_CERT_SHA256=' + cert);
  console.log('B1028_ACTUAL_APK_VERSION_CODE=' + badging.versionCode);
  console.log('B1028_SIGNED_APK_64BIT_LIBRARIES=' + count);
  console.log('B1028_SIGNED_APK_NATIVE_ELF_LOAD_SEGMENTS=' + segments);
  console.log('B1028_SIGNED_APK_STATIC_16KIB=PASS_SCOPED_ONLY');
  console.log('B1028_PHYSICAL_16KIB_AND_GOOGLE_AUTH_UAT=NOT_RUN');
}

if (process.argv[1] && path.resolve(process.argv[1]) === path.resolve(new URL(import.meta.url).pathname)) {
  main().catch(e => { console.error(e.message); process.exitCode = 2; });
}
