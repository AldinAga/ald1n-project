// Android LG-03 / Wave 1: source-level fallback and non-regression gates.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

const policyPath = new URL('../../src/components/layout/android-glass-material-policy.ts', import.meta.url);
const navPath = new URL('../../src/components/layout/app-bottom-nav.tsx', import.meta.url);
const routePolicyPath = new URL('../../src/components/layout/app-bottom-nav-policy.ts', import.meta.url);
const packagePath = new URL('../../package.json', import.meta.url);
const configPath = new URL('../../app.config.js', import.meta.url);
const policySource = readFileSync(policyPath, 'utf8');
const navSource = readFileSync(navPath, 'utf8');
const routePolicySource = readFileSync(routePolicyPath, 'utf8');
const appConfig = readFileSync(configPath, 'utf8');
const pkg = JSON.parse(readFileSync(packagePath, 'utf8'));

function compilePurePolicy(source, filename) {
  const c = ts.transpileModule(source, {
    compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    reportDiagnostics: true,
  });
  assert.equal(c.diagnostics?.length ?? 0, 0, filename + ' must compile');
  const exports = {};
  runInNewContext(c.outputText, { exports }, { filename });
  return exports;
}
const { resolveAndroidGlassMaterial, androidGlassChrome } = compilePurePolicy(
  policySource, 'android-glass-material-policy.ts',
);

test('Android 33+ supports translucent visual baseline (not native glass)', () => {
  for (const api of [33, 34, 35, 36]) {
    assert.equal(resolveAndroidGlassMaterial({ platform: 'android', androidApiLevel: api }), 'translucent');
  }
});

test('older Android, unknown API, and accessibility preferences force opaque fallback', () => {
  for (const api of [24, 28, 31, 32, undefined, NaN]) {
    assert.equal(resolveAndroidGlassMaterial({ platform: 'android', androidApiLevel: api }), 'solid');
  }
  assert.equal(resolveAndroidGlassMaterial({
    platform: 'android', androidApiLevel: 36, reduceTransparency: true,
  }), 'solid');
  assert.equal(resolveAndroidGlassMaterial({
    platform: 'android', androidApiLevel: 36, highContrast: true,
  }), 'solid');
});

test('other platforms preserve their existing navigation material', () => {
  for (const platform of ['ios', 'web', 'windows']) {
    assert.equal(resolveAndroidGlassMaterial({ platform, androidApiLevel: 36 }), 'legacy');
  }
});

test('glass chrome works in both themes without changing Ald1n orange primary', () => {
  for (const isDark of [true, false]) {
    const chrome = androidGlassChrome(isDark);
    for (const value of Object.values(chrome)) assert.match(value, /^rgba\(/);
  }
  assert.match(navSource, /backgroundColor: theme\.primary/);
  assert.doesNotMatch(policySource, /#593B7A|#6D45E5/);
});

test('navigation has decorative pointer-free layer and solid accessibility fallback', () => {
  assert.match(navSource, /resolveAndroidGlassMaterial\(/);
  assert.match(navSource, /AccessibilityInfo\.isReduceTransparencyEnabled/);
  assert.match(navSource, /styles\.barSolid/);
  assert.match(navSource, /styles\.glassReflection/);
  assert.match(navSource, /pointerEvents="none"/);
  assert.match(navSource, /importantForAccessibility="no-hide-descendants"/);
  assert.match(navSource, /styles\.glassTopRim/);
  assert.match(navSource, /styles\.glassBottomRim/);
});

test('five destinations, keyboard dismissal, role access and home emphasis remain intact', () => {
  const { buildBottomNavItems } = compilePurePolicy(routePolicySource, 'app-bottom-nav-policy.ts');
  const context = {
    catalogEnabled: true, ordersEnabled: true, notificationsEnabled: true,
    canManageOrders: true, unread: 2,
  };
  for (const roleSlug of ['superadmin', 'admin', 'user']) {
    const items = buildBottomNavItems({ ...context, roleSlug });
    assert.deepEqual(Array.from(items, item => item.key), ['catalog', 'orders', 'home', 'secondary', 'account']);
    assert.equal(items[2].route, '/home');
    assert.equal(items[2].center, true);
    assert.equal(items[1].route, roleSlug === 'user' ? '/orders' : '/admin/orders');
  }
  assert.match(navSource, /if \(keyboardVisible && !rail\) return null/);
  assert.match(navSource, /accessibilityState=\{\{ selected: focused, disabled: !item\.enabled \}\}/);
  assert.match(navSource, /pathname !== item\.route\) router\.replace\(item\.route\)/);
  assert.match(navSource, /homeItem: \{ transform: \[\{ translateY: -9 \}\]/);
});

test('M17 source candidate preserves existing app runtime identity and native bridge; release is separately gated', () => {
  assert.equal(pkg.version, '1.0.0');
  assert.equal(pkg.dependencies.expo, '~58.0.22');
  assert.equal(pkg.dependencies['react-native'], '0.88.1');
  assert.equal(pkg.dependencies['expo-blur'], undefined);
  assert.equal(pkg.dependencies['react-native-android-liquid-glass'], '0.2.1');
  assert.match(appConfig, /'1\.0\.0-build17'/);
  assert.match(appConfig, /'1\.5\.0-native2'/);
  assert.doesNotMatch(navSource, /from 'expo-blur'|from 'react-native-android-liquid-glass'/);
});
