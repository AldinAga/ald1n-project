// Batch686: first iOS Simulator native compilation without Apple signing or postponed production Firebase work.
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

import { inspectIosReadiness, assessIosReadiness } from '../ios-release-readiness.mjs';

const mobile = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const require = createRequire(import.meta.url);
const read = (name) => fs.readFileSync(path.join(mobile, name), 'utf8');
const eas = JSON.parse(read('eas.json'));
const pkg = JSON.parse(read('package.json'));
const plist = read('GoogleService-Info.plist');
const googleAuthSource = read('src/features/auth/google-auth.ts');
const appConfigSrc = read('app.config.js');

function previewConfig() {
  const file = require.resolve(path.join(mobile, 'app.config.js'));
  const before = process.env.EXPO_PUBLIC_APP_ENV;
  try {
    process.env.EXPO_PUBLIC_APP_ENV = 'preview';
    delete require.cache[file];
    return require(file)({ config: {} });
  } finally {
    delete require.cache[file];
    if (before === undefined) delete process.env.EXPO_PUBLIC_APP_ENV;
    else process.env.EXPO_PUBLIC_APP_ENV = before;
  }
}

const simulatedArgs = () => ({
  profile: 'ios-simulator', appConfig: previewConfig(),
  eas, pkg, plist, previewPlist: plist, googleAuthSource,
  filesPresent: true, root: mobile,
});

test('separate iOS Simulator profile uses preview identity, never production channel or autoIncrement', () => {
  const b = eas.build['ios-simulator'];
  assert.equal(eas.cli.version, '25.0.0');
  assert.equal(b.distribution, 'internal');
  assert.equal(b.channel, 'preview');
  assert.equal(b.env.EXPO_PUBLIC_APP_ENV, 'preview');
  assert.equal(b.env.EXPO_PUBLIC_API_URL, 'https://cms.ald1n.com/api/v1');
  assert.equal(b.ios.simulator, true);
  assert.equal(b.autoIncrement, false);
  assert.notEqual(b.developmentClient, true);
  assert.equal(eas.build.preview.distribution, 'internal');
  assert.equal(eas.build.production.channel, 'production');
  assert.equal(eas.build.production.autoIncrement, true);
});

test('real tracked preview Firebase passes simulator CONFIG ONLY, not native signing or device UAT', () => {
  const r = inspectIosReadiness('ios-simulator');
  assert.equal(r.ok, true, JSON.stringify(r.blockers));
  assert.equal(r.status, 'PASS_CONFIG_ONLY');
  assert.equal(r.effectiveAppEnv, 'preview');
  assert.equal(r.buildTarget, 'IOS_SIMULATOR_ONLY');
  assert.equal(r.expectedBundleId, 'com.ald1n.mobile.preview');
  assert.equal(r.googlePlistBundleId, 'com.ald1n.mobile.preview');
  assert.equal(r.nativeBuild, 'NOT_STARTED');
  assert.equal(r.signing, 'NOT_ATTESTED');
  assert.equal(r.physicalIosUat, 'NOT_PERFORMED');
  assert.equal(r.productionMutation, 'NONE');
});

test('environment file override cannot hijack preview Google identity for simulator profile', () => {
  const old = process.env.ALD1N_IOS_GOOGLE_SERVICES_FILE;
  const had = Object.hasOwn(process.env, 'ALD1N_IOS_GOOGLE_SERVICES_FILE');
  try {
    process.env.ALD1N_IOS_GOOGLE_SERVICES_FILE = '/not-a-real-file/production-firebase.plist';
    const r = inspectIosReadiness('ios-simulator');
    assert.equal(r.ok, true, JSON.stringify(r.blockers));
    assert.equal(r.googlePlistBundleId, 'com.ald1n.mobile.preview');
  } finally {
    if (had) process.env.ALD1N_IOS_GOOGLE_SERVICES_FILE = old;
    else delete process.env.ALD1N_IOS_GOOGLE_SERVICES_FILE;
  }
});

test('negative guards reject simulator profile with production channel, wrong Bundle ID or true autoIncrement', () => {
  const modified = (patch) => {
    const copy = structuredClone(eas);
    Object.assign(copy.build['ios-simulator'], patch);
    return assessIosReadiness({ ...simulatedArgs(), eas: copy });
  };
  const channel = modified({ channel: 'production' });
  assert.ok(channel.blockers.includes('IOS_EAS_CHANNEL_PROFILE_MISMATCH'));
  const increment = modified({ autoIncrement: true });
  assert.ok(increment.blockers.includes('IOS_SIMULATOR_VERSION_AND_CLIENT_GUARD'));
  const nativeDevice = modified({ ios: { simulator: false } });
  assert.ok(nativeDevice.blockers.includes('IOS_SIMULATOR_ONLY_PROFILE_REQUIRED'));
  const wrongEnv = modified({ env: { ...eas.build['ios-simulator'].env, EXPO_PUBLIC_APP_ENV: 'production' } });
  assert.ok(wrongEnv.blockers.includes('IOS_EAS_APP_ENV_MISMATCH'));
  const bundle = assessIosReadiness({
    ...simulatedArgs(),
    appConfig: { ...previewConfig(), ios: { bundleIdentifier: 'com.ald1n.mobile' } },
  });
  assert.ok(bundle.blockers.includes('IOS_APP_BUNDLE_ID_MISMATCH'));
});

test('new simulator command is pinned and always runs local profile gate first; Android and production unchanged', () => {
  assert.equal(pkg.scripts['preflight:ios:simulator'],
    'node scripts/ios-release-readiness.mjs --profile ios-simulator');
  const build = pkg.scripts['build:ios:simulator'];
  assert.ok(build.startsWith('node scripts/ios-release-readiness.mjs --profile ios-simulator && '));
  assert.ok(build.endsWith('--package=eas-cli@25.0.0 -- eas build --platform ios --profile ios-simulator'));
  assert.equal(pkg.scripts['build:android:production'], 'node scripts/build25-release-controller.mjs');
  assert.ok(pkg.scripts['build:ios:production'].includes('--profile production'));
  assert.ok(appConfigSrc.includes("'1.0.0-build17'"));
  assert.equal(previewConfig().runtimeVersion, '1.0.0-build17');
});

test('real iOS production still HOLD without postponed Firebase file, not bypassed by simulator profile', () => {
  const hold = inspectIosReadiness('production');
  assert.equal(hold.status, 'HOLD');
  assert.equal(hold.ok, false);
  assert.equal(hold.expectedBundleId, 'com.ald1n.mobile');
  assert.ok(hold.blockers.includes('IOS_GOOGLE_FILE_VARIABLE_REQUIRED'));
  const sim = inspectIosReadiness('ios-simulator');
  assert.equal(sim.ok, true);
  assert.notEqual(sim.expectedBundleId, hold.expectedBundleId);
});
