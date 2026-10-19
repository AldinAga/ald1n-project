// Android LG-03 Wave 2: native integration contract tests. No EAS or OTA.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');
const shell = read('../../src/app/(app)/_layout.tsx');
const host = read('../../src/components/layout/android-native-glass-bottom-nav.tsx');
const nav = read('../../src/components/layout/app-bottom-nav.tsx');
const policySource = read('../../src/components/layout/android-glass-material-policy.ts');
const appConfig = read('../../app.config.js');
const pkg = JSON.parse(read('../../package.json'));
const lock = JSON.parse(read('../../package-lock.json'));

const javascript = ts.transpileModule(policySource, {
  compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
  reportDiagnostics: true,
});
assert.equal(javascript.diagnostics?.length ?? 0, 0);
const exports = {};
runInNewContext(javascript.outputText, { exports }, { filename: 'android-glass-material-policy.ts' });
const { resolveAndroidGlassMaterial } = exports;

test('native candidate pins the exact published NPM package and lock metadata', () => {
  assert.equal(pkg.dependencies['react-native-android-liquid-glass'], '0.2.1');
  assert.equal(lock.packages[''].dependencies['react-native-android-liquid-glass'], '0.2.1');
  const native = lock.packages['node_modules/react-native-android-liquid-glass'];
  assert.equal(native.version, '0.2.1');
  assert.equal(native.integrity, 'sha512-zcmlp4fvDxz62lleL3ua/uR2lo2SCc9ZhF7Gi682L+9wJueP3E2eUoXpEaCvfJ2c52p6fZ1tON45UwJFm2oRAg==');
  assert.equal(native.resolved, 'https://registry.npmjs.org/react-native-android-liquid-glass/-/react-native-android-liquid-glass-0.2.1.tgz');
});

test('native material is intentionally a separately gated binary, not Build25 OTA', () => {
  assert.equal(pkg.version, '1.0.0');
  assert.equal(pkg.dependencies.expo, '~57.0.27');
  assert.equal(pkg.dependencies['react-native'], '0.86.3');
  assert.match(appConfig, /runtimeVersion: '1\.0\.0-build17'/);
  assert.doesNotMatch(appConfig, /ALD1N_LG_PRODUCTION_NATIVE_AUTORELEASE/);
});

test('refractive root is a direct child of noncollapsed shell alongside Stack', () => {
  assert.match(shell, /<View\s+collapsable=\{false\}\s+style=\{\[styles\.shell/);
  assert.match(shell, /<AndroidNativeGlassBottomNav \/>/);
  assert.ok(shell.indexOf('<Stack') > 0 && shell.indexOf('<Stack') < shell.indexOf('<AndroidNativeGlassBottomNav />'));
  assert.match(shell, /Platform\.OS === 'android'[\s\S]*?<AndroidNativeGlassBottomNav \/>[\s\S]*?: <AppBottomNav variant="bottom" \/>/);
  assert.match(host, /return \(\s*<LiquidGlassAndroidView/);
  assert.doesNotMatch(host, /return \(\s*<View[\s\S]*?<LiquidGlassAndroidView/);
  assert.match(host, /<AppBottomNav variant="native-content" \/>/);
});

test('native candidate protects five role-aware buttons, fallback and keyboard hiding', () => {
  assert.match(nav, /\| 'native-content'/);
  assert.match(nav, /variant === 'native-content'/);
  assert.match(nav, /return <View style=\{styles\.nativeContent\}>\{navItems\}<\/View>/);
  assert.match(nav, /buildBottomNavItems\(/);
  assert.match(nav, /activeBottomNavKey\(/);
  assert.match(nav, /disabled=\{!item\.enabled\}/);
  assert.match(nav, /accessibilityState=\{\{ selected: focused, disabled: !item\.enabled \}\}/);
  assert.match(nav, /if \(keyboardVisible && !rail\) return null/);
  assert.match(host, /if \(keyboardVisible\) return null/);
  assert.match(host, /if \(!nativeSupported\) return <AppBottomNav variant="bottom" \/>/);
  assert.match(nav, /homeCircle: \{\s*width: 52/);
});

test('reduced transparency and Android API<33 cannot enter native lens path', () => {
  for (const api of [24, 28, 31, 32, undefined]) {
    assert.equal(resolveAndroidGlassMaterial({ platform: 'android', androidApiLevel: api }), 'solid');
  }
  assert.equal(resolveAndroidGlassMaterial({platform:'android',androidApiLevel:35,reduceTransparency:true}),'solid');
  assert.equal(resolveAndroidGlassMaterial({platform:'ios',androidApiLevel:36}),'legacy');
  assert.match(host, /useState\(true\)/);
  assert.match(host, /isReduceTransparencyEnabled\(/);
  assert.match(host, /accessibilityMode="auto"/);
});

test('native transient failure and incapable pipeline fall back, not crash or force effects', () => {
  assert.match(host, /if \(event\.nativeEvent\.fatal \|\| event\.nativeEvent\.code === 'PIPELINE_DEGRADED'\)/);
  assert.match(host, /if \(!event\.nativeEvent\.supportsLensPipeline\) setFatalNativeError\(true\)/);
  assert.match(host, /setFatalNativeError\(true\)/);
  assert.doesNotMatch(host, /accessibilityMode="forceFull"/);
});

test('native capture is live over scrolling content but bounded for jank/battery', () => {
  assert.match(host, /overLight=\{!isDark\}/);
  assert.match(host, /useAppTheme\(\)/);
  assert.match(host, /enableDynamicBackground/);
  assert.match(host, /paused=\{!foreground\}/);
  assert.match(host, /globalDownsampleFactor=\{0\.5\}/);
  assert.match(host, /enableChromaticAberration=\{false\}/);
  assert.match(host, /enableChromaticDispersion=\{false\}/);
  assert.match(host, /enableSensorHighlight=\{false\}/);
  assert.match(host, /frameStatsInterval=\{0\}/);
  assert.match(host, /AppState\.addEventListener\('change'/);
  assert.match(host, /height: 70/);
});

test('no new business routes, permissions or backend operations were introduced', () => {
  assert.doesNotMatch(host, /fetch\(|axios|useQuery|apiUrl|users\.retire|process\.env/);
  assert.match(host, /AppBottomNav/);
  assert.match(shell, /useNavigationRail = viewportWidth >= 600/);
});
