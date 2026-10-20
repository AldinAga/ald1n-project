// LG-04 Wave4 — Home/Admin glass is a bounded, accessible navigation affordance only.
import test from 'node:test';
import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

const read = (relative) => readFileSync(new URL(relative, import.meta.url), 'utf8');
const shortcutUrl = new URL('../../src/components/workspaces/glass-workspace-shortcut.tsx', import.meta.url);
const shortcut = existsSync(fileURLToPath(shortcutUrl)) ? readFileSync(shortcutUrl, 'utf8') : '';
const home = read('../../src/app/(app)/(tabs)/home.tsx');
const admin = read('../../src/app/(app)/admin/index.tsx');
const headerPolicy = read('../../src/components/layout/glass-header-policy.ts');
const releaseDoc = read('../../docs/ANDROID-LG04-NEXT-PLAY-OPEN-TESTING-AUTOSUBMIT.md');
const deviceDoc = read('../../docs/ANDROID-LG04-DEVICE-COMPATIBILITY.md');
const pkg = JSON.parse(read('../../package.json'));
const eas = JSON.parse(read('../../eas.json'));

test('shared header policy evaluates actual Android and accessibility limits fail-closed', () => {
  const js = ts.transpileModule(headerPolicy, {
    compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    reportDiagnostics: true,
  });
  assert.equal(js.diagnostics?.length ?? 0, 0);
  const exports = {};
  runInNewContext(js.outputText, { exports });
  const decide = exports.resolveHeaderGlassPolicy;
  assert.equal(decide({ platform: 'android', androidApiLevel: 32, reduceTransparency: false, highTextContrast: false, reduceMotion: false }).material, 'solid');
  assert.equal(decide({ platform: 'ios', androidApiLevel: 33, reduceTransparency: false, reduceMotion: false }).material, 'solid');
  assert.equal(decide({ platform: 'android', androidApiLevel: 33, reduceTransparency: false, highTextContrast: false, reduceMotion: false }).material, 'native');
  assert.equal(decide({ platform: 'android', androidApiLevel: 33, reduceTransparency: true }).material, 'solid');
  assert.equal(decide({ platform: 'android', androidApiLevel: 33, reduceTransparency: false, highTextContrast: true }).material, 'solid');
  assert.equal(decide({ platform: 'android', androidApiLevel: 33, reduceTransparency: false, pipelineFailed: true }).material, 'solid');
  assert.equal(decide({ platform: 'android', androidApiLevel: 33, reduceTransparency: false }).animatePress, false);
});

test('workspace shortcut is one accessible full-row link, not nested or duplicate presses', () => {
  assert.match(shortcut, /export function GlassWorkspaceShortcut/);
  assert.equal((shortcut.match(/<Pressable\b/g) ?? []).length, 1);
  assert.match(shortcut, /accessibilityRole="button"/);
  assert.match(shortcut, /accessibilityLabel=\{copy \? title \+ '\. ' \+ copy : title\}/);
  assert.match(shortcut, /onPress=\{onPress\}/);
  assert.match(shortcut, /minHeight: 72/);
  assert.match(shortcut, /flexShrink: 1/);
  assert.match(shortcut, /minWidth: 0/);
});

test('native lens is restricted to 48dp icon and has an opaque fallback', () => {
  assert.match(shortcut, /resolveHeaderGlassPolicy/);
  assert.match(shortcut, /<View collapsable=\{false\}[^>]*>/);
  assert.match(shortcut, /policy\.material === 'solid'/);
  assert.match(shortcut, /<LiquidGlassAndroidView/);
  assert.match(shortcut, /width: 48/);
  assert.match(shortcut, /height: 48/);
  assert.match(shortcut, /enableDynamicBackground=\{false\}/);
  assert.match(shortcut, /globalDownsampleFactor=\{0\.5\}/);
  assert.match(shortcut, /onError=\{onError\}/);
  assert.match(shortcut, /onPipelineReady=\{onPipelineReady\}/);
  assert.match(shortcut, /backgroundColor: theme\.surface/);
  assert.match(shortcut, /backgroundColor: theme\.surfaceContainerHigh/);
  assert.match(shortcut, /<Text style=\{styles\.title\}>\{title\}<\/Text>/);
  assert.doesNotMatch(shortcut, /<LiquidGlassAndroidView[\s\S]*?<Text/);
});

test('unknown accessibility settings do not enable native lens or movement', () => {
  assert.match(shortcut, /useState\(true\)/);
  assert.match(shortcut, /isReduceTransparencyEnabled\(\)/);
  assert.match(shortcut, /isHighTextContrastEnabled\(\)/);
  assert.match(shortcut, /isReduceMotionEnabled\(\)/);
  assert.match(shortcut, /addEventListener\('reduceTransparencyChanged'/);
  assert.match(shortcut, /addEventListener\('reduceMotionChanged'/);
  assert.match(shortcut, /AppState\.addEventListener\('change'/);
  assert.match(shortcut, /if \(!policy\.animatePress\)/);
  assert.match(shortcut, /scale\.stopAnimation\(\)/);
  assert.match(shortcut, /useNativeDriver: true/);
  assert.match(shortcut, /setPipelineFailed\(true\)/);
  assert.match(shortcut, /transparency\.remove\(\)/);
  assert.match(shortcut, /motion\.remove\(\)/);
  assert.match(shortcut, /app\.remove\(\)/);
  assert.doesNotMatch(shortcut, /setInterval\(|requestAnimationFrame\(/);
});

test('Home enhances only existing permission-checked quick links, not reports or KPIs', () => {
  assert.match(home, /can\('catalog\.manage_products'\) \? \{ title: 'Dodaj artikal'/);
  assert.match(home, /hasFeature\('order_create'\) \? \{ title: 'Korpa'/);
  assert.match(home, /<HomeActionList actions=\{quickActions\} glassShortcuts \/>/);
  assert.match(home, /<HomeActionList actions=\{myActivities\} \/>/);
  assert.match(home, /<HomeActionList actions=\{adminActions\} \/>/);
  assert.match(home, /<GlassWorkspaceShortcut/);
  assert.match(home, /<OperatorRow/);
  assert.match(home, /function DashboardMetric/);
  assert.match(home, /<Card style=\{styles\.dashboardMetricCard\}>/);
  assert.match(home, /formatPrimaryMoney\(inventoryValuation\.purchase_value_rsd, 'RSD'\)/);
  assert.match(home, /formatPrimaryMoney\(inventoryValuation\.sale_value_rsd, 'RSD'\)/);
  assert.doesNotMatch(home, /LiquidGlassAndroidView/);
});

test('Admin uses the same safe shortcut for search, while access, query and search form stay intact', () => {
  assert.match(admin, /if \(!allowed\) \{/);
  assert.match(admin, /enabled: allowed/);
  assert.match(admin, /<GlassWorkspaceShortcut[\s\S]*?title="Pretraži sve module"[\s\S]*?router\.push\('\/admin\/search' as Href\)/);
  assert.match(admin, /value=\{adminSearch\}/);
  assert.match(admin, /onChangeText=\{setAdminSearch\}/);
  assert.match(admin, /accessibilityLabel="Pretraga administracije"/);
  assert.match(admin, /backgroundColor: theme\.surfaceMuted/);
  assert.match(admin, /<OperatorRow title="Porudžbine"/);
  assert.match(admin, /<OperatorRow title="Artikli"/);
  assert.match(admin, /inventoryValuation\.purchase_value_rsd/);
  assert.match(admin, /inventoryValuation\.sale_value_rsd/);
  assert.doesNotMatch(admin, /LiquidGlassAndroidView/);
});

test('device guidance keeps solid data and physical acceptance distinct', () => {
  assert.match(deviceDoc, /LG-04 Wave 4/);
  assert.match(deviceDoc, /Početna/);
  assert.match(deviceDoc, /Administracija/);
  assert.match(deviceDoc, /API 24/);
  assert.match(deviceDoc, /Reduce Motion/);
  assert.match(deviceDoc, /KPI/);
  assert.match(releaseDoc, /Wave4/);
  assert.match(releaseDoc, /Brze akcije/);
  assert.match(releaseDoc, /beta/);
  assert.match(releaseDoc, /NE IZVRŠAVATI SADA/);
});

test('Build26 source identity and strict beta-only release gate have not drifted', () => {
  assert.equal(pkg.version, '1.0.0');
  assert.equal(pkg.dependencies['react-native-android-liquid-glass'], '0.2.1');
  assert.equal(pkg.dependencies['react-native'], '0.86.3');
  assert.equal(eas.build['lg03-play-testing'].channel, 'lg03-play-testing');
  assert.equal(eas.submit['lg03-play-testing'].android.track, 'beta');
  assert.equal(eas.submit.production.android.track, 'production');
});
