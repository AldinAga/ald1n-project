// Android LG-03 Google Play testing AAB profile only. No build or account access.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';
import { spawnSync } from 'node:child_process';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const eas = JSON.parse(readFileSync(resolve(root, 'eas.json'), 'utf8'));
const appConfig = readFileSync(resolve(root, 'app.config.js'), 'utf8');
const services = JSON.parse(readFileSync(resolve(root, 'google-services.json'), 'utf8'));
const packages = (services.client || []).map(c => c.client_info?.android_client_info?.package_name);
const program = [
  "const c=require('./app.config.js')({config:{}});",
  "process.stdout.write(JSON.stringify({",
  "android:c.android.package,ios:c.ios.bundleIdentifier,runtime:c.runtimeVersion,",
  "version:c.version,appEnv:c.extra.appEnv,api:c.extra.apiUrl,project:c.extra.eas.projectId",
  "}));",
].join('');
const base = {
  ...process.env,
  EAS_BUILD_PROFILE: '',
  EXPO_PUBLIC_APP_ENV: 'development',
  ALD1N_LG03_NATIVE_UAT: '',
  ALD1N_LG03_PLAY_TESTING: '',
};
function config(env, success = true) {
  const result = spawnSync(process.execPath, ['-e', program], {
    cwd:root, env:{...base,...env},encoding:'utf8',timeout:10000,
  });
  if (!success) {
    assert.notEqual(result.status,0,'invalid profile must be rejected');
    return result.stderr;
  }
  assert.equal(result.status,0,result.stderr.slice(0,600));
  return JSON.parse(result.stdout);
}

test('new AAB build profile targets the existing Play app testing channel only', () => {
  const p = eas.build['lg03-play-testing'];
  assert.ok(p);
  assert.equal(p.distribution,'store');
  assert.equal(p.channel,'lg03-play-testing');
  assert.equal(p.android.buildType,'app-bundle');
  assert.equal(p.autoIncrement,true);
  assert.deepEqual(p.env,{
    EXPO_PUBLIC_APP_ENV:'production',
    EXPO_PUBLIC_API_URL:'https://cms.ald1n.com/api/v1',
    ALD1N_LG03_PLAY_TESTING:'1',
  });
  assert.equal(eas.cli.appVersionSource,'remote');
  assert.equal(eas.cli.version,'25.0.0');
  assert.ok(!p.autoSubmit && !p.submit);
});

test('new testing EAS profile uses registered package with isolated native runtime', () => {
  const cfg = config({...eas.build['lg03-play-testing'].env,EAS_BUILD_PROFILE:'lg03-play-testing'});
  assert.ok(packages.includes('com.ald1n.mobile'));
  assert.equal(cfg.android,'com.ald1n.mobile');
  assert.equal(cfg.ios,'com.ald1n.mobile');
  assert.equal(cfg.runtime,'1.0.0-lg03-play-testing1');
  assert.equal(cfg.version,'1.0.0');
  assert.equal(cfg.project,'d43b3866-6838-4217-a23e-3dc7f2cc76cc');
  assert.equal(cfg.api,'https://cms.ald1n.com/api/v1');
  assert.equal(cfg.appEnv,'production');
});

test('existing production and internal-preview versions and channels remain untouched', () => {
  const prod = config({EXPO_PUBLIC_APP_ENV:'production'});
  assert.equal(prod.android,'com.ald1n.mobile');
  assert.equal(prod.runtime,'1.0.0-build17');
  assert.equal(eas.build.production.channel,'production');
  assert.equal(eas.build.production.autoIncrement,true);
  const prev = config({EXPO_PUBLIC_APP_ENV:'preview'});
  assert.equal(prev.android,'com.ald1n.mobile.preview');
  assert.equal(prev.runtime,'1.0.0-build17');
  assert.equal(eas.build.preview.channel,'preview');
  assert.equal(eas.build['lg03-native-uat'].channel,'lg03-native-uat');
  assert.match(appConfig,/runtimeVersion: '1\.0\.0-build17'/);
});

test('cross-wired production/preview opt-ins cannot silently masquerade as testing', () => {
  assert.match(config({
    EXPO_PUBLIC_APP_ENV:'preview',
    EAS_BUILD_PROFILE:'lg03-play-testing',
    ALD1N_LG03_PLAY_TESTING:'1',
  },false),/LG03_PLAY_TESTING_REQUIRES_EXACT_PROFILE_AND_REGISTERED_PACKAGE/);
  assert.match(config({
    EXPO_PUBLIC_APP_ENV:'production',
    EAS_BUILD_PROFILE:'production',
    ALD1N_LG03_PLAY_TESTING:'1',
  },false),/LG03_PLAY_TESTING_REQUIRES_EXACT_PROFILE_AND_REGISTERED_PACKAGE/);
  assert.match(config({
    EXPO_PUBLIC_APP_ENV:'production',
    EAS_BUILD_PROFILE:'lg03-play-testing',
    ALD1N_LG03_PLAY_TESTING:'',
  },false),/LG03_PLAY_TESTING_OPT_IN_MISSING/);
  assert.match(config({
    EXPO_PUBLIC_APP_ENV:'production',
    EAS_BUILD_PROFILE:'production',
  },false),/LG03_DRAFT_NATIVE_MODULE_FORBIDS_PRODUCTION_EAS_BUILD/);
  assert.match(config({
    EXPO_PUBLIC_APP_ENV:'production',
    EAS_BUILD_PROFILE:'lg03-play-testing',
    ALD1N_LG03_PLAY_TESTING:'1',
    ALD1N_LG03_NATIVE_UAT:'1',
  },false),/LG03_MUTUALLY_EXCLUSIVE_BUILD_TARGETS/);
});
