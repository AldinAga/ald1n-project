// B989: Release profile must deliver native v1.5 gate to BOTH local config
// resolution and the remote EAS builder without enabling it on ordinary production.
import test from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {spawnSync} from 'node:child_process';
import {fileURLToPath} from 'node:url';
import path from 'node:path';

const mobile = path.resolve(fileURLToPath(new URL('../../', import.meta.url)));
const eas = JSON.parse(readFileSync(path.join(mobile, 'eas.json'), 'utf8'));
const profileName = 'production-v15-16kb';
const profile = eas.build[profileName];
const original = eas.build.production;
const ENV_KEYS = ['EXPO_PUBLIC_APP_ENV', 'EAS_BUILD_PROFILE', 'ALD1N_V15_RELEASE_GATE', 'ALD1N_V15_RELEASE_PROFILE'];
const evalJs = [
  'const p=require(process.argv[1]);',
  'const v=p({config:{}});',
  'console.log(JSON.stringify({version:v.version,runtimeVersion:v.runtimeVersion,package:v.android.package,projectId:v.extra.eas.projectId,appEnv:v.extra.appEnv}));'
].join('');

function run(extraEnv) {
  const env = {...process.env};
  for (const key of ENV_KEYS) delete env[key];
  Object.assign(env, extraEnv);
  return spawnSync(process.execPath, ['-e', evalJs, path.join(mobile,'app.config.js')], {
    cwd:mobile,env,encoding:'utf8',timeout:15000
  });
}
function evaluate(extraEnv) {
  const child = run(extraEnv);
  assert.equal(child.status, 0, 'dynamic config failed: '+child.stderr);
  return JSON.parse(child.stdout);
}
// No SDK57 default runtime: anything but an approved identity must fail closed.
function assertHeld(extraEnv) {
  const child = run(extraEnv);
  assert.notEqual(child.status, 0, 'config must not resolve: '+child.stdout);
  assert.match(child.stderr, /HOLD_RUNTIME_IDENTITY_NOT_APPROVED/);
}

test('dedicated v1.5 EAS profile is a store AAB, isolated from ordinary production', () => {
  assert.equal(eas.cli.version, '27.2.0');
  assert.equal(eas.cli.appVersionSource, 'remote');
  assert.equal(original.channel, 'production');
  assert.equal(original.autoIncrement, true);
  assert.equal(original.android.buildType, 'app-bundle');
  assert.equal(original.env.ALD1N_V15_RELEASE_GATE, undefined, 'normal production must NOT auto-enable native v1.5');
  assert.equal(original.env.ALD1N_V15_RELEASE_PROFILE, undefined);
  assert.equal(profile.extends, 'production');
  assert.equal(profile.environment, 'production');
  assert.equal(profile.channel, 'production');
  assert.equal(profile.autoIncrement, true);
  assert.equal(profile.android.buildType, 'app-bundle');
  assert.equal(profile.env.EXPO_PUBLIC_APP_ENV, 'production');
  assert.equal(profile.env.EXPO_PUBLIC_API_URL, 'https://cms.ald1n.com/api/v1');
  assert.equal(profile.env.ALD1N_V15_RELEASE_GATE, 'approved');
  assert.equal(profile.env.ALD1N_V15_RELEASE_PROFILE, profileName);
  assert.ok(!JSON.stringify(profile).includes('autoSubmit'));
});

test('ordinary production without explicit release gate fails closed (no Build25 default)', () => {
  assertHeld({...original.env,EAS_BUILD_PROFILE:'production'});
});

test('dedicated release profile resolves v1.5 even when EAS_BUILD_PROFILE is absent locally', () => {
  const c=evaluate(profile.env);
  assert.deepEqual([c.version,c.runtimeVersion,c.package,c.appEnv],['1.5.0','1.5.0-native2','com.ald1n.mobile','production']);
  assert.equal(c.projectId,'d43b3866-6838-4217-a23e-3dc7f2cc76cc');
});

test('remote EAS builder with dedicated profile has same v1.5 release identity', () => {
  const c=evaluate({...profile.env,EAS_BUILD_PROFILE:profileName});
  assert.deepEqual([c.version,c.runtimeVersion,c.package],['1.5.0','1.5.0-native2','com.ald1n.mobile']);
});

test('old production build contract only activates with deliberate explicit gate', () => {
  const c=evaluate({...original.env,EAS_BUILD_PROFILE:'production',ALD1N_V15_RELEASE_GATE:'approved'});
  assert.deepEqual([c.version,c.runtimeVersion],['1.5.0','1.5.0-native2']);
});

test('rejects missing or wrong release gate, profile marker, or wrong app environment', () => {
  const missingGate={...profile.env};
  delete missingGate.ALD1N_V15_RELEASE_GATE;
  assertHeld(missingGate);
  assertHeld({...profile.env,ALD1N_V15_RELEASE_GATE:'no'});
  assertHeld({EXPO_PUBLIC_APP_ENV:'production',ALD1N_V15_RELEASE_GATE:'approved',EAS_BUILD_PROFILE:'preview'});
  assertHeld({...profile.env,EXPO_PUBLIC_APP_ENV:'development'});
});

test('production profile remains free of auto-submit and native release gate by default', () => {
  assert.deepEqual(eas.submit.production.android,{track:'production',releaseStatus:'completed'});
  assert.equal(original.env.ALD1N_V15_RELEASE_GATE,undefined);
  assert.equal(original.env.ALD1N_V15_RELEASE_PROFILE,undefined);
  assertHeld({EXPO_PUBLIC_APP_ENV:'production'});
});

test('same require(config) responds to release gate change per invocation, as existing CI expects', () => {
  const script = [
    "const cfg=require(process.argv[1]);",
    "delete process.env.ALD1N_V15_RELEASE_GATE;",
    "let old;try{old=cfg({config:{}})}catch(e){old={version:'HOLD',runtimeVersion:/HOLD_RUNTIME_IDENTITY_NOT_APPROVED/.test(e.message)?'HELD':'ERR'}}",
    "process.env.ALD1N_V15_RELEASE_GATE='approved';",
    "const next=cfg({config:{}});",
    "console.log(JSON.stringify([old.version,old.runtimeVersion,next.version,next.runtimeVersion]));"
  ].join('');
  const env = {...process.env,EXPO_PUBLIC_APP_ENV:'production',EAS_BUILD_PROFILE:'production'};
  delete env.ALD1N_V15_RELEASE_GATE;
  delete env.ALD1N_V15_RELEASE_PROFILE;
  const child=spawnSync(process.execPath,['-e',script,path.join(mobile,'app.config.js')],{
    cwd:mobile,env,encoding:'utf8',timeout:15000
  });
  assert.equal(child.status,0,child.stderr);
  assert.deepEqual(JSON.parse(child.stdout),['HOLD','HELD','1.5.0','1.5.0-native2']);
});
