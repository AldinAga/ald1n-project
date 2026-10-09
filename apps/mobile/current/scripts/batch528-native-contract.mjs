import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const moduleRelative = 'modules/ald1n-restore-credentials/android/src/main/java/expo/modules/ald1nrestorecredentials/Ald1nRestoreCredentialsModule.kt';
export const gradleRelative = 'modules/ald1n-restore-credentials/android/build.gradle';

// A source guard only. Real compiler verification is a separate mandatory CI job.
export function validateSource(kotlin, gradle) {
  const failures = [];
  if (!/AsyncFunction\("clearRestoreCredential"\)\s+Coroutine\s*\{\s*->/.test(kotlin)) failures.push('CLEAR_ZERO_ARITY_REQUIRED');
  const tail = kotlin.slice(kotlin.indexOf('AsyncFunction("clearRestoreCredential")'));
  if (/return@Coroutine\s+null/.test(tail)) failures.push('CLEAR_NULL_RETURN_FORBIDDEN');
  if (!tail.includes('manager.clearCredentialState(ClearCredentialStateRequest(requestType = TYPE_CLEAR_RESTORE_CREDENTIAL))')) failures.push('CLEAR_NATIVE_CALL_MISSING');
  if (!/defaultConfig\s*\{[^}]*versionCode\s+1\b[^}]*versionName\s+"1\.0\.0"/s.test(gradle)) failures.push('LIBRARY_VERSION_METADATA_REQUIRED');
  for (const marker of ['CreateRestoreCredentialRequest', 'E2eeUnavailableException', 'isCloudBackupEnabled = false', 'GetRestoreCredentialOption']) {
    if (!kotlin.includes(marker)) failures.push('EXISTING_FLOW_REMOVED:' + marker);
  }
  return failures;
}

export function validateSnapshot(s) {
  const failures = [];
  const expected = { agp: '8.12.0', gradle: '9.3.1', kotlin: '2.1.20', ndk: '27.1.12297006', compileSdk: 36, targetSdk: 36, minSdk: 24, applicationId: 'com.ald1n.mobile', versionName: '1.0.0', minify: true, shrinkResources: true, optimizedShrinking: 'true', restoreProject: true };
  for (const [key, value] of Object.entries(expected)) {
    if (s[key] !== value) failures.push(`${key}: expected ${value}, got ${s[key]}`);
  }
  if (String(s.fullMode) === 'false') failures.push('R8_FULL_MODE_DISABLED');
  if (!String(s.java ?? '').startsWith('17.')) failures.push('JAVA_17_REQUIRED');
  if (!Array.isArray(s.proguard) || !s.proguard.some(x => path.basename(x).startsWith('proguard-android-optimize.txt'))) failures.push('OPTIMIZED_PROGUARD_REQUIRED');
  for (const coordinate of ['androidx.credentials:credentials:1.6.0', 'androidx.credentials:credentials-play-services-auth:1.6.0']) {
    if (!Array.isArray(s.dependencies) || !s.dependencies.includes(coordinate)) failures.push('MISSING_RESOLVED_DEPENDENCY:' + coordinate);
  }
  return failures;
}

export function validatePrebuild(before, after) {
  const a = structuredClone(before), b = structuredClone(after);
  for (const platform of ['android', 'ios']) {
    if (a.scripts?.[platform] !== b.scripts?.[platform]) {
      if (b.scripts?.[platform] !== `expo run:${platform}`) return ['UNEXPECTED_PREBUILD_SCRIPT:' + platform];
      delete a.scripts?.[platform];
      delete b.scripts?.[platform];
    }
  }
  // JSON property order is irrelevant; all values except known generated scripts must match.
  const normalize = x => Array.isArray(x) ? x.map(normalize) : x && typeof x === 'object' ? Object.fromEntries(Object.keys(x).sort().map(k => [k, normalize(x[k])])) : x;
  return JSON.stringify(normalize(a)) === JSON.stringify(normalize(b)) ? [] : ['PREBUILD_CHANGED_LOCKED_PACKAGE_INPUTS'];
}

export function validateReleaseLog(text) {
  const failures = [];
  if (!text.includes('BUILD SUCCESSFUL')) failures.push('GRADLE_SUCCESS_MISSING');
  if (!/^> Task :app:minifyReleaseWithR8\s*$/m.test(text)) failures.push('R8_NOT_EXECUTED');
  if (!/^> Task :ald1n-restore-credentials:compileReleaseKotlin(?:\s+UP-TO-DATE)?\s*$/m.test(text)) failures.push('RESTORE_COMPILE_TASK_MISSING');
  return failures;
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  try {
    const [mode, input] = process.argv.slice(2);
    let failures;
    if (mode === 'source') {
      const root = path.resolve(input || process.cwd());
      failures = validateSource(fs.readFileSync(path.join(root, moduleRelative), 'utf8'), fs.readFileSync(path.join(root, gradleRelative), 'utf8'));
    } else if (mode === 'snapshot') {
      failures = validateSnapshot(JSON.parse(fs.readFileSync(input, 'utf8')));
    } else if (mode === 'prebuild') {
      failures = validatePrebuild(JSON.parse(fs.readFileSync(input, 'utf8')), JSON.parse(fs.readFileSync(process.argv[4], 'utf8')));
    } else if (mode === 'release-log') {
      failures = validateReleaseLog(fs.readFileSync(input, 'utf8'));
    } else throw new Error('Usage: batch528-native-contract.mjs source APP_ROOT | snapshot FILE | release-log FILE');
    console.log(JSON.stringify({ gate: mode, result: failures.length ? 'FAIL' : 'PASS', failures, productionReleaseAuthorized: false }, null, 2));
    process.exitCode = failures.length ? 1 : 0;
  } catch (error) {
    console.error(error.message);
    process.exitCode = 2;
  }
}
