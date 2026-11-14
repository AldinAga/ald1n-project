import {strict as assert} from 'node:assert';
import {spawnSync} from 'node:child_process';
import {readFileSync} from 'node:fs';
import {fileURLToPath} from 'node:url';
import path from 'node:path';
import test from 'node:test';
import {assessNativeEntrypoint} from './hold-unaudited-native-build.mjs';

const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const pkg=JSON.parse(readFileSync(path.join(root,'package.json'),'utf8'));
const eas=JSON.parse(readFileSync(path.join(root,'eas.json'),'utf8'));
const moduleFile=path.join(root,'scripts/release27/hold-unaudited-native-build.mjs');
const scripts={
  'build:android:preview':['android','preview'],
  'build:android:development':['android','development'],
  'build:ios:preview':['ios','preview'],
  'build:ios:development':['ios','development'],
  'build:ios:production':['ios','production'],
  'build:ios:simulator':['ios','ios-simulator'],
};

test('new SDK58 declared CLI27/npm12 authority cannot be overridden by six legacy build scripts',()=>{
  assert.equal(eas.cli.version,'27.2.0');
  assert.equal(pkg.packageManager,'npm@12.2.1');
  for(const [name,[platform,profile]] of Object.entries(scripts)) {
    const cmd=pkg.scripts[name];
    assert.ok(cmd.endsWith(`node scripts/release27/hold-unaudited-native-build.mjs --platform ${platform} --profile ${profile}`),name);
    assert.ok(!cmd.includes('-- eas build '),name);
    assert.ok(!cmd.includes('eas-cli@25.0.0'),name);
    if(platform==='ios') assert.ok(cmd.startsWith(`node scripts/ios-release-readiness.mjs --profile ${profile} && `));
  }
  assert.equal(pkg.scripts['build:android:production'],'node scripts/build25-release-controller.mjs');
  assert.equal(pkg.scripts['submit:android:production'],'node scripts/submit-android-production.mjs');
});

test('all legacy native npm build targets fail closed, never launching EAS or npm',()=>{
  for(const [name,[platform,profile]] of Object.entries(scripts)) {
    const r=spawnSync(process.execPath,[moduleFile,'--platform',platform,'--profile',profile],{encoding:'utf8',timeout:8000});
    assert.equal(r.status,2,name);
    const data=JSON.parse(r.stderr.trim());
    assert.equal(data.status,'HOLD');
    assert.equal(data.reason,'NATIVE_RELEASE_GATE_REQUIRED');
    assert.ok(data.policy.includes('16KiB'));
  }
});

test('unknown/invalid profile, no args or extra tokens never authorizes builds',()=>{
  assert.equal(assessNativeEntrypoint([]).reason,'INVALID_NATIVE_BUILD_ARGUMENTS');
  assert.equal(assessNativeEntrypoint(['--platform','android','--profile','m17-sdk58-play-rc']).reason,'UNKNOWN_NATIVE_BUILD_PROFILE');
  assert.equal(assessNativeEntrypoint(['--platform','android','--profile','preview','--submit']).reason,'INVALID_NATIVE_BUILD_ARGUMENTS');
  assert.equal(assessNativeEntrypoint(['--platform','ios','--profile','production']).status,'HOLD');
});
