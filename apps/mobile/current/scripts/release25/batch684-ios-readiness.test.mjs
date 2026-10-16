// Batch685: exact iOS profile separation, fail-closed Firebase asset provenance and synthetic negative tests.
// Synthetic OAuth IDs below are TEST FIXTURES, not real Firebase credentials or proof of registration.
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { inspectIosReadiness } from '../ios-release-readiness.mjs';

const mobile = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (name) => fs.readFileSync(path.join(mobile, name), 'utf8');
const pkg = JSON.parse(read('package.json'));
const preview = read('GoogleService-Info.plist');
const variable = 'ALD1N_IOS_GOOGLE_SERVICES_FILE';

function replacePlistValue(source, key, value) {
  const re = new RegExp('(<key>' + key + '</key>\\s*<string>)[^<]*(</string>)');
  const out = source.replace(re, (_, start, end) => start + value + end);
  assert.notEqual(out, source, 'test fixture missing plist key ' + key);
  return out;
}

function syntheticProductionPlist() {
  let p = preview;
  const items = {
    BUNDLE_ID: 'com.ald1n.mobile',
    CLIENT_ID: 'synthetic-ios-production-unit-test.apps.googleusercontent.com',
    REVERSED_CLIENT_ID: 'com.googleusercontent.apps.synthetic-ios-production-unit-test',
    GOOGLE_APP_ID: '1:123456789:ios:synthetic-do-not-use',
  };
  for (const [key, value] of Object.entries(items)) p = replacePlistValue(p, key, value);
  return p;
}

function withEnvFile(content, callback) {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'ald1n-b685-test-'));
  const file = path.join(dir, 'GoogleService-Info.plist');
  const had = Object.hasOwn(process.env, variable);
  const before = process.env[variable];
  try {
    fs.writeFileSync(file, content, { mode: 0o600 });
    process.env[variable] = file;
    return callback(file);
  } finally {
    if (had) process.env[variable] = before;
    else delete process.env[variable];
    fs.rmSync(dir, { recursive: true, force: true });
  }
}

function withoutFileEnv(callback) {
  const had = Object.hasOwn(process.env, variable);
  const before = process.env[variable];
  try {
    delete process.env[variable];
    return callback();
  } finally {
    if (had) process.env[variable] = before;
    else delete process.env[variable];
  }
}

test('preview keeps the real tracked Firebase preview app regardless of production file env', () => {
  const r = withEnvFile(syntheticProductionPlist(), () => inspectIosReadiness('preview'));
  assert.equal(r.ok, true, JSON.stringify(r.blockers));
  assert.equal(r.status, 'PASS_CONFIG_ONLY');
  assert.equal(r.expectedBundleId, 'com.ald1n.mobile.preview');
  assert.equal(r.googlePlistBundleId, r.expectedBundleId);
  assert.equal(r.nativeBuild, 'NOT_STARTED');
  assert.equal(r.signing, 'NOT_ATTESTED');
});

test('production with no independent external plist must hold before EAS build', () => {
  const r = withoutFileEnv(() => inspectIosReadiness('production'));
  assert.equal(r.ok, false);
  assert.ok(r.blockers.includes('IOS_GOOGLE_FILE_VARIABLE_REQUIRED'));
  assert.ok(r.blockers.includes('IOS_GOOGLE_PLIST_MISSING'));
  assert.equal(r.expectedBundleId, 'com.ald1n.mobile');
  assert.equal(r.productionMutation, 'NONE');
});

test('development cannot inadvertently use preview Firebase credentials', () => {
  const r = withoutFileEnv(() => inspectIosReadiness('development'));
  assert.equal(r.ok, false);
  assert.ok(r.blockers.includes('IOS_GOOGLE_FILE_VARIABLE_REQUIRED'));
});

test('production configuration-only PASS is possible with independent synthetic fixture (NOT real Firebase)', () => {
  withEnvFile(syntheticProductionPlist(), () => {
    const r = inspectIosReadiness('production');
    assert.equal(r.ok, true, JSON.stringify(r.blockers));
    assert.equal(r.status, 'PASS_CONFIG_ONLY');
    assert.equal(r.googlePlistBundleId, 'com.ald1n.mobile');
    assert.equal(r.firebaseRegistration, 'NOT_INDEPENDENTLY_VERIFIED');
    assert.equal(r.easFileEnvironment, 'NOT_INDEPENDENTLY_VERIFIED');
    assert.equal(r.nativeBuild, 'NOT_STARTED');
  });
});

test('renaming preview BUNDLE_ID alone cannot spoof production app registration', () => {
  const forged = replacePlistValue(preview, 'BUNDLE_ID', 'com.ald1n.mobile');
  const r = withEnvFile(forged, () => inspectIosReadiness('production'));
  assert.equal(r.ok, false);
  assert.ok(r.blockers.includes('IOS_GOOGLE_CLIENT_REUSED_FROM_PREVIEW'));
  assert.ok(r.blockers.includes('IOS_GOOGLE_APP_REUSED_FROM_PREVIEW'));
});

test('incorrect production iOS bundle, reversed OAuth scheme or web client must hold', () => {
  const valid = syntheticProductionPlist();
  const wrongBundle = withEnvFile(replacePlistValue(valid, 'BUNDLE_ID', 'com.ald1n.wrong'), () => inspectIosReadiness('production'));
  assert.ok(wrongBundle.blockers.includes('IOS_GOOGLE_BUNDLE_ID_MISMATCH'));
  const wrongReverse = withEnvFile(replacePlistValue(valid, 'REVERSED_CLIENT_ID', 'com.googleusercontent.apps.wrong'), () => inspectIosReadiness('production'));
  assert.ok(wrongReverse.blockers.includes('IOS_GOOGLE_REVERSED_CLIENT_ID_MISMATCH'));
  const wrongWeb = withEnvFile(replacePlistValue(valid, 'WEB_CLIENT_ID', 'wrong.apps.googleusercontent.com'), () => inspectIosReadiness('production'));
  assert.ok(wrongWeb.blockers.includes('IOS_GOOGLE_WEB_CLIENT_ID_MISMATCH'));
});

test('different Firebase project, missing App ID and reused client do not pass', () => {
  const valid = syntheticProductionPlist();
  const wrongProject = withEnvFile(replacePlistValue(valid, 'PROJECT_ID', 'other-test-project'), () => inspectIosReadiness('production'));
  assert.ok(wrongProject.blockers.includes('IOS_GOOGLE_FIREBASE_PROJECT_MISMATCH'));
  const wrongApp = withEnvFile(replacePlistValue(valid, 'GOOGLE_APP_ID', ''), () => inspectIosReadiness('production'));
  assert.ok(wrongApp.blockers.includes('IOS_GOOGLE_FIREBASE_IDENTITY_INCOMPLETE'));
  const matchPreviewClient = preview.match(/<key>CLIENT_ID<\/key>\s*<string>([^<]+)/)?.[1];
  assert.ok(matchPreviewClient);
  const reusedClient = withEnvFile(replacePlistValue(valid, 'CLIENT_ID', matchPreviewClient), () => inspectIosReadiness('production'));
  assert.ok(reusedClient.blockers.includes('IOS_GOOGLE_CLIENT_REUSED_FROM_PREVIEW'));
});

test('relative paths, preview-file alias and symlink to repo preview are rejected', () => {
  const previous = process.env[variable];
  const had = Object.hasOwn(process.env, variable);
  try {
    process.env[variable] = './GoogleService-Info.plist';
    const relative = inspectIosReadiness('production');
    assert.ok(relative.blockers.includes('IOS_EXTERNAL_GOOGLE_PLIST_REQUIRED'));
  } finally {
    if (had) process.env[variable] = previous; else delete process.env[variable];
  }
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'ald1n-b685-symlink-'));
  try {
    const alias = path.join(temp, 'copied-preview.plist');
    fs.symlinkSync(path.join(mobile, 'GoogleService-Info.plist'), alias);
    const had2 = Object.hasOwn(process.env, variable);
    const previous2 = process.env[variable];
    try {
      process.env[variable] = alias;
      const spoof = inspectIosReadiness('production');
      assert.ok(spoof.blockers.includes('IOS_EXTERNAL_GOOGLE_PLIST_REQUIRED'));
    } finally {
      if (had2) process.env[variable] = previous2; else delete process.env[variable];
    }
  } finally {
    fs.rmSync(temp, { recursive: true, force: true });
  }
});

test('all iOS build entrypoints still run preflight before exact EAS CLI25; Android untouched', () => {
  for (const profile of ['preview', 'production', 'development']) {
    const cmd = pkg.scripts['build:ios:' + profile];
    assert.ok(cmd.startsWith('node scripts/ios-release-readiness.mjs --profile ' + profile + ' && '));
    assert.ok(cmd.includes('--package=eas-cli@25.0.0 -- eas build --platform ios --profile ' + profile));
  }
  assert.equal(pkg.scripts['preflight:ios:production'], 'node scripts/ios-release-readiness.mjs --profile production');
  assert.equal(pkg.scripts['build:android:production'], 'node scripts/build25-release-controller.mjs');
  assert.ok(pkg.scripts['build:android:preview'].includes('--platform android --profile preview'));
});

test('no unknown EAS profile ever passes', () => {
  const r = inspectIosReadiness('not-a-profile');
  assert.equal(r.status, 'HOLD');
  assert.ok(r.blockers.includes('IOS_UNKNOWN_BUILD_PROFILE'));
});
