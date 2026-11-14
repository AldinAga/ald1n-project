// B1012: source-only ALT Node22 pin; does not dispatch native EAS builds or submits.
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const mobile = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const source = (name) => fs.readFileSync(path.join(mobile, name), 'utf8');
const pkg = JSON.parse(source('package.json'));
const build = source('scripts/build25-release.sh');
const submit = source('scripts/submit-android-production.mjs');
const node = '/opt/alt/alt-nodejs22/root/bin/node';
const pathPrefix = 'PATH=/opt/alt/alt-nodejs22/root/bin:$PATH ';
const oldNodevenv = '/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node';
const npmCli = '/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js';

test('six legacy npm native entrypoints cannot bypass signed 16KiB release controls', () => {
  for(const name of [
    'build:android:preview','build:android:development',
    'build:ios:preview','build:ios:development','build:ios:simulator','build:ios:production'
  ]) {
    const value=pkg.scripts[name];
    assert.ok(value.includes('node scripts/release27/hold-unaudited-native-build.mjs --platform '),name);
    assert.ok(!value.includes('-- eas build ') && !value.includes('eas-cli@25.0.0'),name);
    assert.ok(!value.includes(oldNodevenv) && !value.includes('--auto-submit'),name);
  }
  assert.equal(pkg.scripts['build:android:production'],'node scripts/build25-release-controller.mjs');
  assert.equal(pkg.scripts['submit:android:production'],'node scripts/submit-android-production.mjs');
});

test('release shell preserves owner authorization, lock, exact controller and PATH', () => {
  assert.ok(build.includes('NODE_BIN=' + node));
  assert.ok(build.includes('export PATH="/opt/alt/alt-nodejs22/root/bin:$PATH"'));
  assert.ok(build.includes('flock -n -x 9'));
  assert.ok(build.includes('"$NODE_BIN" scripts/build25-release-controller.mjs "$@"'));
  assert.ok(!build.includes(oldNodevenv));
});

test('disabled Android production submit helper resolves child node independently', () => {
  assert.ok(submit.includes("const NODE_BIN = '" + node + "'"));
  assert.ok(submit.includes("const EAS_CLI_VERSION = '27.2.0'"));
  assert.ok(submit.includes("PATH: `${path.dirname(NODE_BIN)}${path.delimiter}${process.env.PATH || ''}`"));
  assert.ok(submit.includes("if (mode === '--execute') die('DIRECT_SUBMIT_DISABLED:"));
  assert.ok(!submit.includes(oldNodevenv));
});
