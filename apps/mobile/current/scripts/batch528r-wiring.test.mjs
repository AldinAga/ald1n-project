import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
const dir = process.env.ALD1N_528R_SOURCE_DIR || path.dirname(fileURLToPath(import.meta.url));
const ci = fs.readFileSync(path.join(dir, 'batch528-ci.sh'), 'utf8');
const audit = fs.readFileSync(path.join(dir, 'batch528-native-snapshot.gradle'), 'utf8');
const repoRoot = path.resolve(dir, '../../../..');
const workflow = fs.readFileSync(path.join(repoRoot, '.github/workflows/ald1n-native-528.yml'), 'utf8');
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

test('dependency inventory walks the resolved graph without selecting Android artifacts', () => {
  assert.ok(audit.includes('configuration.incoming.resolutionResult'));
  assert.ok(!audit.includes('resolved.resolvedArtifacts'));
  assert.ok(!audit.includes('configuration.incoming.artifactView'));
});

test('native audit reclaims ephemeral runner disk without changing ABI contract', () => {
  assert.ok(ci.includes('STAGE=runner-disk-reclaim'));
  assert.ok(ci.includes('disk-capacity-before-cleanup.log'));
  assert.ok(ci.includes('disk-capacity-after-cleanup.log'));
  assert.ok(ci.includes('/usr/share/dotnet'));
  assert.ok(ci.includes('27.1.12297006'));
  assert.ok(ci.includes('3.22.1'));
  const reclaim = ci.indexOf('STAGE=runner-disk-reclaim');
  const gradle = ci.indexOf('run gradle-version ');
  assert.ok(reclaim >= 0 && gradle > reclaim);
  assert.ok(!ci.includes('reactNativeArchitectures=arm64-v8a'));
  assert.ok(!ci.includes('reactNativeArchitectures=armeabi-v7a'));
});

test('workflow bootstraps required CMake before native disk reclaim', () => {
  assert.ok(workflow.includes("'cmake;3.22.1'"));
  assert.ok(workflow.includes('test -x "$sdk/cmake/3.22.1/bin/ninja"'));
  const cmake = workflow.indexOf("'cmake;3.22.1'");
  const native = workflow.indexOf('run: bash apps/mobile/current/scripts/batch528-ci.sh');
  assert.ok(cmake >= 0 && native > cmake);
  assert.ok(ci.includes('[ -x "$sdk/cmake/3.22.1/bin/ninja" ]'));
});
