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

test('known Worklets KaModule lint exception is scoped before native release', () => {
  assert.ok(ci.includes('STAGE=worklets-lint-known-bug-workaround'), 'scoped Worklets lint workaround stage missing');
  assert.ok(ci.includes('node_modules/react-native-worklets/android/build.gradle.kts'));
  assert.ok(ci.includes('WORKLETS_EXPECTED_VERSION=0.10.1'));
  assert.ok(ci.includes('apply(from = "./fix-prefab.gradle.kts")'));
  assert.ok(ci.includes('apply(from = "./generate-stub-pch.gradle.kts")'));
  assert.ok(ci.includes('tasks.configureEach { if (name.startsWith("lint")) enabled = false }'));
  const snapshot = ci.indexOf('run snapshot-contract ');
  const patch = ci.indexOf('apply_worklets_lint_known_bug_workaround', snapshot);
  const lint = ci.indexOf('run lint-release ');
  const kotlinRed = ci.indexOf('STAGE=real-kotlin-red');
  const release = ci.indexOf('run release-log-contract ');
  assert.ok(snapshot >= 0 && patch > snapshot && lint > patch && kotlinRed > lint && release > kotlinRed);
  for (const needle of ['-x :react-native-worklets:lintAnalyzeRelease', 'ignoreFailures', 'continueOnError']) {
    assert.ok(!ci.includes(needle), needle);
  }
});

test('R3E early complete app lint runs after snapshot and before Kotlin RED and R8', () => {
  const snap = ci.indexOf('run snapshot-contract ');
  const wp = ci.indexOf('apply_worklets_lint_known_bug_workaround\n', snap);
  const rp = ci.indexOf('apply_reanimated_lint_known_bug_workaround\n', snap);
  const lint = ci.indexOf('run lint-release ');
  const red = ci.indexOf('STAGE=real-kotlin-red');
  const release = ci.indexOf('run release ./gradlew ');
  assert.ok(snap >= 0 && wp > snap && rp > wp && lint > rp && lint < red && red < release, 'lint must fail fast before expensive Kotlin RED/R8 native release');
  assert.ok(ci.includes("grep -Eq '^> Task :app:lintRelease($| )'"), 'actual app lint execution marker must be required');
});

test('R3E Reanimated 4.5.1 Lint exception is scoped, version guarded and restored before release', () => {
  const snap = ci.indexOf('run snapshot-contract ');
  const lint = ci.indexOf('run lint-release ');
  const red = ci.indexOf('STAGE=real-kotlin-red');
  assert.ok(ci.includes('REANIMATED_EXPECTED_VERSION=4.5.1'));
  assert.ok(ci.includes('node_modules/react-native-reanimated/android/build.gradle.kts'));
  assert.ok(ci.includes('apply(from = "./generate-stub-pch.gradle.kts")'));
  assert.ok(ci.includes('REANIMATED_LINT_KAMODULE_WORKAROUND=APPLIED_VERSION_'));
  assert.ok(ci.includes("<<'WORKLETS_NODE' || exit 1"));
  assert.ok(ci.includes("<<'REANIMATED_NODE' || exit 1"));
  assert.ok(ci.includes('restore_reanimated_lint_known_bug_workaround || exit 1'));
  const restore = ci.indexOf('restore_reanimated_lint_known_bug_workaround || exit 1',lint);
  const restoreW = ci.indexOf('restore_worklets_lint_known_bug_workaround || exit 1',lint);
  assert.ok(snap >= 0 && restore > lint && restoreW > restore && restoreW < red);
  for (const bad of ['-x :app:lintRelease', '--warning-mode none', 'ignoreFailures', 'continueOnError', 'org.gradle.daemon.performance.disable-logging=true']) assert.ok(!ci.includes(bad),bad);
});

test('R3F Expo splash API33 plugin order and fast generated-resource contract are mandatory', () => {
  const config=fs.readFileSync(path.join(dir,'../app.config.js'),'utf8');
  const custom=config.indexOf("'./plugins/with-splash-api33-resources'");
  const splash=config.indexOf("'expo-splash-screen'");
  // Expo withMod evaluates later-registered hooks before earlier hooks.
  assert.ok(custom >= 0 && splash > custom, 'API33 mod must register before Expo splash mod');
  const prebuild=ci.indexOf('run prebuild-delta-contract ');
  const check=ci.indexOf('run splash-api33-native-contract ');
  const reclaim=ci.indexOf('reclaim_ephemeral_runner_disk\n',prebuild);
  assert.ok(prebuild >= 0 && check > prebuild && reclaim > check, 'API33 source contract must run before Gradle');
  assert.ok(ci.includes('scripts/splash-api33-resources.test.mjs'), 'API33 regression must be in CI');
});
