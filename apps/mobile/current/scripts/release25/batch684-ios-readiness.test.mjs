// Batch684: first iOS preview readiness and fail-closed production Google OAuth gate.
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

import { assessIosReadiness, inspectIosReadiness } from '../ios-release-readiness.mjs';

const mobile = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const require = createRequire(import.meta.url);
const read = (name) => fs.readFileSync(path.join(mobile, name), 'utf8');
const eas = JSON.parse(read('eas.json'));
const pkg = JSON.parse(read('package.json'));
const plist = read('GoogleService-Info.plist');
const googleAuthSource = read('src/features/auth/google-auth.ts');

function productionAppConfig() {
  const src = path.join(mobile, 'app.config.js');
  const modulePath = require.resolve(src);
  const old = process.env.EXPO_PUBLIC_APP_ENV;
  try {
    process.env.EXPO_PUBLIC_APP_ENV = 'production';
    delete require.cache[modulePath];
    return require(modulePath)({ config: {} });
  } finally {
    delete require.cache[modulePath];
    if (old === undefined) delete process.env.EXPO_PUBLIC_APP_ENV;
    else process.env.EXPO_PUBLIC_APP_ENV = old;
  }
}

test('first iOS preview app matches its real Google plist and official web OAuth client', () => {
  const outcome = inspectIosReadiness('preview');
  assert.equal(outcome.ok, true, JSON.stringify(outcome.blockers));
  assert.equal(outcome.status, 'PASS_CONFIG_ONLY');
  assert.equal(outcome.expectedBundleId, 'com.ald1n.mobile.preview');
  assert.equal(outcome.googlePlistBundleId, outcome.expectedBundleId);
  assert.equal(outcome.signing, 'NOT_ATTESTED');
  assert.equal(outcome.nativeBuild, 'NOT_STARTED');
  assert.equal(outcome.physicalIosUat, 'NOT_PERFORMED');
});

test('production iOS is blocked before EAS build due to currently preview-only Firebase plist', () => {
  const outcome = inspectIosReadiness('production');
  assert.equal(outcome.ok, false);
  assert.equal(outcome.status, 'HOLD');
  assert.equal(outcome.expectedBundleId, 'com.ald1n.mobile');
  assert.equal(outcome.googlePlistBundleId, 'com.ald1n.mobile.preview');
  assert.ok(outcome.blockers.includes('IOS_GOOGLE_BUNDLE_ID_MISMATCH'));
  assert.equal(outcome.productionMutation, 'NONE');
});

test('development iOS does not accidentally reuse mismatched preview Firebase credentials', () => {
  const outcome = inspectIosReadiness('development');
  assert.equal(outcome.ok, false);
  assert.ok(outcome.blockers.includes('IOS_GOOGLE_BUNDLE_ID_MISMATCH'));
});

test('production passes CONFIG-ONLY only when a matching iOS Google plist is actually supplied', () => {
  const alignedPlist = plist.replace(
    '<key>BUNDLE_ID</key>\n\t<string>com.ald1n.mobile.preview</string>',
    '<key>BUNDLE_ID</key>\n\t<string>com.ald1n.mobile</string>',
  );
  assert.notEqual(alignedPlist, plist, 'test fixture must change real plist bundle ID');
  const outcome = assessIosReadiness({
    profile: 'production', appConfig: productionAppConfig(), eas, pkg,
    plist: alignedPlist, googleAuthSource, filesPresent: true,
  });
  assert.equal(outcome.ok, true, JSON.stringify(outcome.blockers));
  assert.equal(outcome.status, 'PASS_CONFIG_ONLY');
  assert.equal(outcome.signing, 'NOT_ATTESTED');
});

test('malformed iOS reversed OAuth scheme and backend web OAuth mismatch fail closed', () => {
  const alignedPlist = plist.replace('com.ald1n.mobile.preview', 'com.ald1n.mobile');
  const args = { profile: 'production', appConfig: productionAppConfig(), eas, pkg, googleAuthSource, filesPresent: true };
  const tamperedScheme = alignedPlist.replace(
    /(<key>REVERSED_CLIENT_ID<\/key>\s*<string>)[^<]+(<\/string>)/,
    '$1invalid.reversed.scheme$2',
  );
  const badScheme = assessIosReadiness({ ...args, plist: tamperedScheme });
  assert.ok(badScheme.blockers.includes('IOS_GOOGLE_REVERSED_CLIENT_ID_MISMATCH'));
  const tamperedWeb = alignedPlist.replace(
    /(<key>WEB_CLIENT_ID<\/key>\s*<string>)[^<]+(<\/string>)/,
    '$1not-the-configured-client.apps.googleusercontent.com$2',
  );
  const badWeb = assessIosReadiness({ ...args, plist: tamperedWeb });
  assert.ok(badWeb.blockers.includes('IOS_GOOGLE_WEB_CLIENT_ID_MISMATCH'));
});

test('unknown EAS iOS profile and removed config plugin fail closed', () => {
  const bogus = inspectIosReadiness('unknown');
  assert.equal(bogus.ok, false);
  assert.ok(bogus.blockers.includes('IOS_UNKNOWN_BUILD_PROFILE'));
  const config = productionAppConfig();
  const bad = assessIosReadiness({
    profile: 'production', appConfig: { ...config, plugins: [] }, eas, pkg,
    plist: plist.replace('com.ald1n.mobile.preview', 'com.ald1n.mobile'),
    googleAuthSource, filesPresent: true,
  });
  assert.ok(bad.blockers.includes('IOS_NATIVE_GOOGLE_PLUGIN_MISSING'));
});

test('all iOS build entry points gate readiness before pinned EAS, leaving Android scripts unchanged', () => {
  for (const profile of ['preview', 'production', 'development']) {
    const full = pkg.scripts['build:ios:' + profile];
    assert.ok(full.startsWith('node scripts/ios-release-readiness.mjs --profile ' + profile + ' && '), profile);
    assert.ok(full.includes('--package=eas-cli@25.0.0 -- eas build --platform ios --profile ' + profile), profile);
  }
  assert.equal(pkg.scripts['preflight:ios:preview'], 'node scripts/ios-release-readiness.mjs --profile preview');
  assert.equal(pkg.scripts['preflight:ios:production'], 'node scripts/ios-release-readiness.mjs --profile production');
  assert.equal(pkg.scripts['build:android:production'], 'node scripts/build25-release-controller.mjs');
  assert.ok(pkg.scripts['build:android:preview'].includes('--platform android --profile preview'));
});
