// Batch682: exact EAS CLI25 tooling authority; never infer native or production state from a CLI-only update.
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const mobile = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const root = path.resolve(mobile, '../../..');
const read = (p) => fs.readFileSync(p, 'utf8');
const eas = JSON.parse(read(path.join(mobile, 'eas.json')));
const pkg = JSON.parse(read(path.join(mobile, 'package.json')));
const agents = read(path.join(root, 'AGENTS.md'));
const index = read(path.join(root, 'docs/operations/000-LATEST.md'));
const helper = read(path.join(mobile, 'scripts/submit-android-production.mjs'));
const validator = read(path.join(mobile, 'scripts/validate-project.mjs'));
const app = read(path.join(mobile, 'app.config.js'));

test('canonical v25 authority converges across config and guardrails without global EAS installs', () => {
  assert.equal(eas.cli.version, '25.0.0');
  assert.equal(eas.cli.appVersionSource, 'remote');
  assert.ok(agents.includes('eas-cli@25.0.0'));
  assert.ok(agents.includes('CI 38862601089'));
  assert.ok(agents.includes('Authenticated production exact-group read on hosting'));
  assert.ok(!agents.includes('EAS_SPEC="eas-cli@24.8.0"'));
  assert.ok(index.startsWith('# CURRENT AUTHORITY — Product Editor UX2 Android OTA and EAS CLI25'));
  assert.ok(index.includes('Canonical EAS CLI is `25.0.0`'));
});

test('optional build wrappers, submit helper and validator use only exact EAS CLI25', () => {
  for (const name of ['build:android:preview','build:ios:preview','build:ios:production','build:android:development','build:ios:development']) {
    assert.ok(pkg.scripts[name].includes('--package=eas-cli@25.0.0 -- eas build --platform '), name);
  }
  assert.equal(pkg.scripts['build:android:production'], 'node scripts/build25-release-controller.mjs');
  assert.equal(pkg.scripts['submit:android:production'], 'node scripts/submit-android-production.mjs');
  assert.ok(!JSON.stringify(pkg.scripts).includes('eas-cli@24.8.0'));
  assert.ok(!JSON.stringify(pkg.scripts).includes('eas-cli@latest'));
  assert.ok(helper.includes("const EAS_CLI_VERSION = '25.0.0'"));
  assert.ok(validator.includes("productionSubmitHelper505R.includes(\"const EAS_CLI_VERSION = '25.0.0'\")"));
});

test('Build25 identity and EAS remote app version are untouched', () => {
  assert.equal(pkg.version, '1.0.0');
  assert.equal(eas.build.production.channel, 'production');
  assert.equal(eas.build.production.autoIncrement, true);
  assert.equal(eas.submit.production.android.track, 'production');
  assert.ok(app.includes("runtimeVersion: '1.0.0-build17'"));
  assert.ok(app.includes("version: '1.5.0'"));
  assert.ok(app.includes('com.ald1n.mobile'));
  assert.ok(!JSON.stringify(pkg.scripts).includes('--auto-submit'));
});

test('Report682 production source and old Workspace V2/Build25 forensic pointers remain separate', () => {
  assert.ok(index.includes('37ae8af0-b950-4549-8476-c960e1350866'));
  assert.ok(index.includes('01a1436c-40d0-7f00-a22d-460d74dcf12c'));
  assert.ok(index.includes('904c1db0f4213ff2e3eea3757869f86556d55378'));
  assert.ok(index.includes('HOLD_PENDING_PHYSICAL_PLAY_DEVICE'));
  assert.ok(index.includes('## Historical Workspace V2 production OTA / Batch632 — NOT CURRENT AUTHORITY'));
  assert.ok(index.includes('## Historical Build25 pre-OTA snapshot — NOT CURRENT AUTHORITY'));
});
