import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const mobile = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (name) => readFileSync(path.join(mobile, name), 'utf8');
const pkg = JSON.parse(read('package.json'));
const eas = JSON.parse(read('eas.json'));
const PROFILE = 'm17-sdk58-uat';
const RUNTIME = '1.0.0-m17-sdk58-uat2';
const profile = eas.build[PROFILE];
const clearKeys = [
  'EXPO_PUBLIC_APP_ENV', 'EAS_BUILD_PROFILE',
  'ALD1N_V15_RELEASE_GATE', 'ALD1N_V15_RELEASE_PROFILE',
  'ALD1N_M17_SDK58_UAT', 'ALD1N_M17_SDK58_PROFILE',
];
const evaluateConfig = [
  'const make=require(process.argv[1]);',
  'const app=make({config:{}});',
  'console.log(JSON.stringify({version:app.version,runtimeVersion:app.runtimeVersion,',
  'package:app.android.package,iosBundle:app.ios.bundleIdentifier,',
  'projectId:app.extra.eas.projectId,appEnv:app.extra.appEnv}));',
].join('');
function evaluate(extra = {}) {
  const env = {...process.env};
  for (const key of clearKeys) delete env[key];
  Object.assign(env, extra);
  const child = spawnSync(process.execPath,
    ['-e', evaluateConfig, path.join(mobile, 'app.config.js')],
    {cwd:mobile, env, encoding:'utf8', timeout:12000});
  if (child.status === 0) return {ok:true, config:JSON.parse(child.stdout)};
  return {ok:false, reason:child.stderr};
}
function assertConfig(env, expectedVersion, expectedRuntime, expectedPackage) {
  const observed = evaluate(env);
  assert.equal(observed.ok, true, observed.reason);
  assert.deepEqual([observed.config.version, observed.config.runtimeVersion, observed.config.package],
    [expectedVersion, expectedRuntime, expectedPackage]);
  return observed.config;
}

test('SDK58 Android UAT profile is internal, isolated and not a release profile', () => {
  assert.equal(pkg.dependencies.expo, '~58.0.22');
  assert.equal(pkg.dependencies['react-native'], '0.88.1');
  assert.equal(pkg.dependencies['react-native-nitro-google-signin'], '2.3.1');
  assert.equal(eas.cli.version, '27.2.0');
  assert.equal(eas.cli.appVersionSource, 'remote');
  assert.equal(profile.extends, 'preview');
  assert.equal(profile.environment, 'preview');
  assert.equal(profile.distribution, 'internal');
  assert.equal(profile.channel, PROFILE);
  assert.equal(profile.autoIncrement, false);
  assert.equal(profile.android.buildType, 'apk');
  assert.equal(profile.ios.simulator, true);
  assert.equal(profile.env.EXPO_PUBLIC_APP_ENV, 'preview');
  assert.equal(profile.env.EXPO_PUBLIC_API_URL, 'https://cms.ald1n.com/api/v1');
  assert.equal(profile.env.ALD1N_M17_SDK58_UAT, 'isolated');
  assert.equal(profile.env.ALD1N_M17_SDK58_PROFILE, PROFILE);
  assert.equal(eas.build.preview.channel, 'preview');
  assert.equal(eas.build.production.channel, 'production');
  assert.equal(eas.build.production.autoIncrement, true);
  assert.equal(eas.build['production-v15-16kb'].channel, 'production');
  assert.deepEqual(eas.submit.production.android, {track:'production',releaseStatus:'completed'});
  assert.equal(profile.autoSubmit, undefined);
});

test('SDK58 M17 profile resolves identical isolated runtime locally and on remote builder', () => {
  const local = assertConfig(profile.env, '1.0.0', RUNTIME, 'com.ald1n.mobile.preview');
  const remote = assertConfig({...profile.env,EAS_BUILD_PROFILE:PROFILE},
    '1.0.0', RUNTIME, 'com.ald1n.mobile.preview');
  assert.equal(local.projectId, 'd43b3866-6838-4217-a23e-3dc7f2cc76cc');
  assert.equal(remote.projectId, local.projectId);
  assert.equal(local.iosBundle, 'com.ald1n.mobile.preview');
  assert.equal(local.appEnv, 'preview');
  assert.notEqual(RUNTIME, '1.0.0-build17');
  assert.notEqual(RUNTIME, '1.5.0-native2');
});

test('existing production, v1.5 release and ordinary preview identities are unaffected', () => {
  assertConfig({...eas.build.production.env,EAS_BUILD_PROFILE:'production'},
    '1.0.0','1.0.0-build17','com.ald1n.mobile');
  assertConfig({...eas.build.preview.env,EAS_BUILD_PROFILE:'preview'},
    '1.0.0','1.0.0-build17','com.ald1n.mobile.preview');
  assertConfig({...eas.build['production-v15-16kb'].env,EAS_BUILD_PROFILE:'production-v15-16kb'},
    '1.5.0','1.5.0-native2','com.ald1n.mobile');
});

test('incorrect, incomplete or cross-profile SDK58 markers cannot select UAT runtime', () => {
  for (const env of [
    {...profile.env,EAS_BUILD_PROFILE:'production'},
    {...profile.env,EXPO_PUBLIC_APP_ENV:'production'},
    {...profile.env,ALD1N_M17_SDK58_UAT:'approved'},
    {...profile.env,ALD1N_M17_SDK58_PROFILE:'preview'},
    {EXPO_PUBLIC_APP_ENV:'preview',ALD1N_M17_SDK58_UAT:'isolated'},
    {EXPO_PUBLIC_APP_ENV:'preview',ALD1N_M17_SDK58_PROFILE:PROFILE},
  ]) {
    const result = evaluate(env);
    assert.equal(result.ok, false, 'incorrect M17 profile must not be accepted');
    assert.match(result.reason,/HOLD_M17_SDK58_UAT_PROFILE_OR_SOURCE/);
  }
});

test('M17 UAT cannot authorize EAS native production, OTA or Play submission', () => {
  assert.equal(pkg.scripts['build:android:production'], 'node scripts/build25-release-controller.mjs');
  assert.equal(pkg.scripts['submit:android:production'], 'node scripts/submit-android-production.mjs');
  assert.equal(profile.channel, 'm17-sdk58-uat');
  assert.equal(profile.autoIncrement, false);
  assert.equal(profile.android.buildType, 'apk');
  assert.ok(!JSON.stringify(profile).includes('autoSubmit'));
  assert.ok(!JSON.stringify(profile).includes('ALD1N_V15_RELEASE_GATE'));
  const aabGate = read('scripts/release25/signed-16kb-aab-release-gate.mjs');
  assert.match(aabGate, /EXPECTED_VERSION = '1\.5\.0'/);
  assert.match(aabGate, /EXPECTED_RUNTIME = '1\.5\.0-native2'/);
});
