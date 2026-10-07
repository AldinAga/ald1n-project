import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
const dir = process.env.ALD1N_528R_SOURCE_DIR || path.dirname(fileURLToPath(import.meta.url));
const ci = fs.readFileSync(path.join(dir, 'batch528-ci.sh'), 'utf8');
const audit = fs.readFileSync(path.join(dir, 'batch528-native-snapshot.gradle'), 'utf8');
test('snapshot is not installed as a global Gradle init script', () => {
  assert.ok(!/run native-snapshot[^\n]*--init-script/.test(ci));
});
test('read-only task is applied to generated app project', () => {
  assert.ok(ci.includes('"$APP/android/app/ald1n-audit-528.gradle"'));
  assert.ok(ci.includes('apply from: file("ald1n-audit-528.gradle")'));
});
test('audit rejects application to a project other than :app', () => {
  assert.ok(audit.includes("auditApp.path != ':app'"));
  assert.ok(audit.includes('Batch528R: snapshot must be applied to :app'));
});
test('real composite regression is mandatory before real app snapshot', () => {
  const fixture = ci.indexOf('run composite-scope-regression ');
  assert.ok(fixture >= 0 && fixture < ci.indexOf('run native-snapshot '));
});
test('existing Kotlin RED and release/R8/lint gates remain mandatory', () => {
  for (const needle of ['STAGE=real-kotlin-red', 'Overload resolution ambiguity',
    ':ald1n-restore-credentials:compileReleaseKotlin :app:assembleRelease :app:bundleRelease',
    'run release-log-contract ', 'run lint-release ', 'outputs/mapping/release/mapping.txt']) {
    assert.ok(ci.includes(needle), needle);
  }
});
test('missing snapshot or unresolved dependencies remain failures', () => {
  assert.ok(audit.includes('resolved.rethrowFailure()'));
  assert.ok(ci.includes('run snapshot-contract '));
  assert.ok(ci.includes(':app:ald1nSnapshot528'));
});
test('non-release status and production signing restriction remain explicit', () => {
  assert.ok(ci.includes('RELEASE_READINESS=BLOCKED_OPEN_AUDIT_FINDINGS'));
  assert.ok(ci.includes('Audit must not use production signing configuration'));
  assert.ok(audit.includes('productionReleaseAuthorized: false'));
});
test('no error-suppressing workaround added to audit helper', () => {
  for (const needle of ['continueOnError', 'ignoreFailures', '-dontwarn', '-dontoptimize']) {
    assert.ok(!audit.includes(needle), needle);
  }
});
