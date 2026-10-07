/** Real Gradle scope regression. Not an Android/AGP compiler substitute. */
import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import crypto from 'node:crypto';
import { spawnSync, execFileSync } from 'node:child_process';

const [wrapperArg, correctedArg, outArg] = process.argv.slice(2);
if (!wrapperArg || !correctedArg || !outArg) {
  throw new Error('Usage: node batch528r-composite-probe.mjs GRADLE_WRAPPER CORRECTED_PROJECT_SCRIPT OUTPUT_DIR');
}
const wrapper = fs.realpathSync(wrapperArg);
const corrected = fs.readFileSync(correctedArg, 'utf8');
const out = path.resolve(outArg);
fs.mkdirSync(out, { recursive: true });
const baseline = '8321aa38498359053278b3614922feae7940367d';
const oldPath = 'apps/mobile/current/scripts/batch528-native-snapshot.gradle';
const original = execFileSync('git', ['show', `${baseline}:${oldPath}`], { encoding: 'utf8' });
const scratch = fs.mkdtempSync(path.join(os.tmpdir(), 'ald1n528r-composite-'));
const results = [];
const write = (root, rel, text) => {
  const dest = path.join(root, rel);
  fs.mkdirSync(path.dirname(dest), { recursive: true });
  fs.writeFileSync(dest, text);
};
const quoted = (text) => "'" + text.replaceAll('\\', '\\\\').replaceAll("'", "\\'") + "'";
function fixture(name, hasApp = true) {
  const root = path.join(scratch, name);
  write(root, 'settings.gradle', `pluginManagement { includeBuild('settings-plugin') }
plugins { id 'fixture.settings' }
rootProject.name = 'batch528r-composite'
${hasApp ? "include(':app')" : ''}
includeBuild('other-build')
`);
  write(root, 'settings-plugin/settings.gradle', "rootProject.name = 'fixture-settings-plugin'\n");
  write(root, 'settings-plugin/build.gradle', `plugins { id 'java-gradle-plugin' }
gradlePlugin { plugins { fixtureSettings { id = 'fixture.settings'; implementationClass = 'FixtureSettingsPlugin' } } }
`);
  write(root, 'settings-plugin/src/main/java/FixtureSettingsPlugin.java', `import org.gradle.api.Plugin;
import org.gradle.api.initialization.Settings;
public final class FixtureSettingsPlugin implements Plugin<Settings> {
  public void apply(Settings settings) { }
}
`);
  write(root, 'other-build/settings.gradle', "rootProject.name = 'other-build'\ninclude(':app')\n");
  write(root, 'other-build/app/build.gradle', `tasks.register('verifyNoSnapshot') {
  doLast {
    if (tasks.findByName('ald1nSnapshot528') != null) throw new GradleException('Snapshot leaked into included :app')
    println('BATCH528R_INCLUDED_APP_UNTOUCHED=YES')
  }
}
`);
  write(root, 'build.gradle', `tasks.register('verifyScope') {
  dependsOn(':app:verifySnapshotRegistration')
  dependsOn(gradle.includedBuild('other-build').task(':app:verifyNoSnapshot'))
}
`);
  if (hasApp) write(root, 'app/build.gradle', `tasks.register('verifySnapshotRegistration') {
  doLast {
    if (tasks.findByName('ald1nSnapshot528') == null) throw new GradleException('Main :app snapshot task missing')
    println('BATCH528R_MAIN_APP_REGISTERED=YES')
  }
}
`);
  return root;
}
function run(label, root, args) {
  const p = spawnSync(wrapper, ['--offline', '--no-daemon', '--console=plain', '--stacktrace',
    '--no-configuration-cache', '--no-build-cache', '--max-workers=2', ...args], {
    cwd: root, encoding: 'utf8', maxBuffer: 32 * 1024 * 1024, timeout: 300_000,
  });
  const text = (p.stdout || '') + (p.stderr || '');
  fs.writeFileSync(path.join(out, `${label}.log`), text);
  console.log(`COMPOSITE_PROBE_STAGE=${label} EXIT_CODE=${p.status}`);
  if (p.error || p.signal || p.status === null) throw p.error || new Error(`${label}: terminated ${p.signal}`);
  return { code: p.status, text };
}
function record(name, condition, text) {
  results.push({ name, pass: Boolean(condition) });
  if (!condition) {
    console.error(text.split('\n').slice(-60).join('\n'));
    throw new Error(`Composite scope regression failed: ${name}`);
  }
}
let failure;
try {
  const root = fixture('main');
  const oldInit = path.join(scratch, 'original-init.gradle');
  fs.writeFileSync(oldInit, original);
  let result = run('red-original-global-init', root, ['--init-script', oldInit, ':verifyScope']);
  record('original init script reproduces included-build app-missing failure',
    result.code !== 0 && result.text.includes('Batch528: :app missing'), result.text);
  const fixedFile = path.join(root, 'app', 'audit.gradle');
  fs.writeFileSync(fixedFile, corrected);
  const appBuild = path.join(root, 'app', 'build.gradle');
  fs.writeFileSync(appBuild, `apply from: file(${quoted(fixedFile)})\n` + fs.readFileSync(appBuild, 'utf8'));
  result = run('green-project-only-apply', root, [':verifyScope']);
  record('main app receives audit but unrelated included app does not',
    result.code === 0 && result.text.includes('BATCH528R_MAIN_APP_REGISTERED=YES') &&
    result.text.includes('BATCH528R_INCLUDED_APP_UNTOUCHED=YES'), result.text);
  const missing = fixture('missing-main-app', false);
  result = run('negative-missing-main-app', missing, [':app:ald1nSnapshot528']);
  record('missing actual app cannot be accepted', result.code !== 0 &&
    /(?:project ['"]app['"] not found|project with path [^\n]*:app[^\n]*not (?:found|be found))/i.test(result.text), result.text);
  const wrong = fixture('wrong-target');
  const wrongFile = path.join(wrong, 'audit.gradle');
  fs.writeFileSync(wrongFile, corrected);
  write(wrong, 'build.gradle', `apply from: file(${quoted(wrongFile)})\n`);
  result = run('negative-wrong-target', wrong, ['help']);
  record('helper rejects root project application', result.code !== 0 &&
    result.text.includes('Batch528R: snapshot must be applied to :app'), result.text);
} catch (error) {
  failure = error;
} finally {
  const summary = {
    baseline, baselineScriptSha256: crypto.createHash('sha256').update(original).digest('hex'),
    correctedScriptSha256: crypto.createHash('sha256').update(corrected).digest('hex'),
    tests: results, result: failure ? 'FAIL' : 'PASS_COMPOSITE_SCOPE_ONLY',
    fullAndroidReleaseProven: false, failure: failure?.message || null,
  };
  fs.writeFileSync(path.join(out, 'summary.json'), JSON.stringify(summary, null, 2) + '\n');
  console.log(JSON.stringify(summary, null, 2));
  fs.rmSync(scratch, { recursive: true, force: true });
}
if (failure) throw failure;
