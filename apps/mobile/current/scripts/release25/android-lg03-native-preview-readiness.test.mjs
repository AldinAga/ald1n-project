// Android LG-03: safe no-build preview profile and runtime isolation gates.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const eas = JSON.parse(readFileSync(resolve(root, 'eas.json'), 'utf8'));
const firebase = JSON.parse(readFileSync(resolve(root, 'google-services.json'), 'utf8'));
const clients = firebase.client.map((x) => x.client_info.android_client_info.package_name);
const appConfig = readFileSync(resolve(root, 'app.config.js'), 'utf8');

const script = 'const c=require("./app.config.js")({config:{}}); process.stdout.write(JSON.stringify({android:c.android.package,ios:c.ios.bundleIdentifier,runtime:c.runtimeVersion,name:c.name,slug:c.slug,appEnv:c.extra.appEnv,api:c.extra.apiUrl,project:c.extra.eas.projectId}));';
function readConfig(env, expectSuccess=true) {
  const p = spawnSync(process.execPath, ['-e', script], {
    cwd: root,
    env: {
      ...process.env,
      EXPO_PUBLIC_APP_ENV: 'production',
      ALD1N_LG03_NATIVE_UAT: '',
      EAS_BUILD_PROFILE: '',
      ...env,
    },
    encoding: 'utf8',
    timeout: 8000,
  });
  if (!expectSuccess) {
    assert.notEqual(p.status, 0, 'forbidden profile must be blocked');
    return p.stderr;
  }
  assert.equal(p.status, 0, p.stderr.slice(0, 800));
  return JSON.parse(p.stdout);
}

test('dedicated LG03 preview APK profile is internal, isolated and non-incrementing', () => {
  const uat = eas.build['lg03-native-uat'];
  assert.ok(uat, 'uat profile must exist');
  assert.equal(uat.distribution, 'internal');
  assert.equal(uat.channel, 'lg03-native-uat');
  assert.equal(uat.android.buildType, 'apk');
  assert.equal(uat.autoIncrement, false);
  assert.deepEqual(uat.env, {
    EXPO_PUBLIC_APP_ENV: 'preview',
    EXPO_PUBLIC_API_URL: 'https://cms.ald1n.com/api/v1',
    ALD1N_LG03_NATIVE_UAT: '1',
  });
  assert.equal(eas.cli.version, '25.0.0');
  assert.equal(eas.cli.appVersionSource, 'remote');
  assert.equal(eas.build.production.channel, 'production');
  assert.equal(eas.build.production.autoIncrement, true);
  assert.equal(eas.build.preview.channel, 'preview');
});

test('signed preview must coexist with Play app under real Firebase preview identity', () => {
  assert.ok(clients.includes('com.ald1n.mobile.preview'));
  assert.ok(clients.includes('com.ald1n.mobile'));
  const cfg = readConfig({
    ...eas.build['lg03-native-uat'].env,
    EAS_BUILD_PROFILE: 'lg03-native-uat',
  });
  assert.equal(cfg.android, 'com.ald1n.mobile.preview');
  assert.equal(cfg.ios, 'com.ald1n.mobile.preview');
  assert.equal(cfg.runtime, '1.0.0-lg03-native-uat1');
  assert.equal(cfg.name, 'Ald1n CMS');
  assert.equal(cfg.slug, 'ald1n-mobile');
  assert.equal(cfg.appEnv, 'preview');
  assert.equal(cfg.api, 'https://cms.ald1n.com/api/v1');
  assert.equal(cfg.project, 'd43b3866-6838-4217-a23e-3dc7f2cc76cc');
});

test('current production/ordinary preview runtimes are unaffected', () => {
  const prod = readConfig({EXPO_PUBLIC_APP_ENV:'production'});
  const oldPreview = readConfig({EXPO_PUBLIC_APP_ENV:'preview'});
  assert.equal(prod.android,'com.ald1n.mobile');
  assert.equal(prod.runtime,'1.0.0-build17');
  assert.equal(oldPreview.android,'com.ald1n.mobile.preview');
  assert.equal(oldPreview.runtime,'1.0.0-build17');
  assert.match(appConfig, /runtimeVersion: '1\.0\.0-build17'/);
});

test('invalid opt-in and any production EAS build from draft fail closed', () => {
  const wrongEnv = readConfig({
    EXPO_PUBLIC_APP_ENV:'production',
    ALD1N_LG03_NATIVE_UAT:'1',
  },false);
  assert.match(wrongEnv, /LG03_NATIVE_UAT_REQUIRES_REGISTERED_PREVIEW_APP/);
  const prodBuild = readConfig({
    EXPO_PUBLIC_APP_ENV:'production',
    EAS_BUILD_PROFILE:'production',
  },false);
  assert.match(prodBuild, /LG03_DRAFT_NATIVE_MODULE_FORBIDS_PRODUCTION_EAS_BUILD/);
  // No direct EAS API, no build calls, no OTP/signature material in this test.
});
