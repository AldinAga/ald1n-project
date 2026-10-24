import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';
const read = path => readFileSync(new URL('../../' + path, import.meta.url), 'utf8');
test('isolated native glass package and runtime are pinned without production OTA compatibility', () => {
  const pkg = JSON.parse(read('package.json'));
  const lock = JSON.parse(read('package-lock.json'));
  const eas = JSON.parse(read('eas.json'));
  const config = read('app.config.js');
  assert.equal(pkg.dependencies['react-native-android-liquid-glass'], '0.2.1');
  assert.equal(lock.packages[''].dependencies['react-native-android-liquid-glass'], '0.2.1');
  assert.equal(eas.build['lg03-native-uat'].channel, 'lg03-native-uat');
  assert.equal(eas.build.production.channel, 'production');
  assert.match(config, /1\.0\.0-lg03-native-uat1/);
  assert.match(config, /1\.0\.0-build17/);
  assert.match(config, /LG03_DRAFT_NATIVE_MODULE_FORBIDS_PRODUCTION_EAS_BUILD/);
});
test('native glass is a direct app-shell child with opaque accessibility fallback', () => {
  const shell = read('src/app/(app)/_layout.tsx');
  const native = read('src/components/layout/android-native-glass-bottom-nav.tsx');
  const nav = read('src/components/layout/app-bottom-nav.tsx');
  assert.match(shell, /collapsable=\{false\}/);
  assert.match(shell, /<AndroidNativeGlassBottomNav \/>/);
  assert.match(native, /LiquidGlassAndroidView/);
  assert.match(native, /resolveAndroidGlassMaterial/);
  assert.match(nav, /variant === 'native-content'/);
  assert.match(nav, /keyboardVisible && !rail/);
});
test('latest main features are not lost by native foundation port', () => {
  const home = read('src/app/(app)/(tabs)/home.tsx');
  const editor = read('src/app/(app)/admin/catalog/create.tsx');
  assert.match(home, /onPress=\{\(\) => router\.push\(signal\.route\)\}/);
  assert.match(editor, /keyboardAware/);
});
