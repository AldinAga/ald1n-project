// LG-04 Wave 1: global header native glass is decorative and fail-closed.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

const read = (file) => readFileSync(new URL(file, import.meta.url), 'utf8');
const policy = read('../../src/components/layout/glass-header-policy.ts');
const action = read('../../src/components/layout/glass-header-action.tsx');
const header = read('../../src/components/layout/page-header.tsx');
const nav = read('../../src/components/layout/android-native-glass-bottom-nav.tsx');
const root = read('../../src/app/(app)/_layout.tsx');
const pkg = JSON.parse(read('../../package.json'));
const lock = JSON.parse(read('../../package-lock.json'));
const appConfig = read('../../app.config.js');
const eas = JSON.parse(read('../../eas.json'));

const compiled = ts.transpileModule(policy, {
  compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
  reportDiagnostics: true,
});
assert.equal(compiled.diagnostics?.length ?? 0,0);
const exports = {};
runInNewContext(compiled.outputText,{exports},{filename:'glass-header-policy.ts'});
const resolve = exports.resolveHeaderGlassPolicy;

test('native bounded glass only on Android API33+ after accessibility is resolved', () => {
  for(const api of [33,34,35,36]){
    assert.equal(resolve({platform:'android',androidApiLevel:api,reduceTransparency:false,highTextContrast:false}).material,'native');
  }
  for(const api of [24,25,28,31,32,undefined,NaN]){
    assert.equal(resolve({platform:'android',androidApiLevel:api,reduceTransparency:false}).material,'solid');
  }
  for(const platform of ['ios','web','windows']){
    assert.equal(resolve({platform,androidApiLevel:36,reduceTransparency:false}).material,'solid');
  }
});

test('high contrast, reduced transparency, pipeline error and unknown settings are solid',()=>{
  const context={platform:'android',androidApiLevel:36,reduceTransparency:false,highTextContrast:false};
  assert.equal(resolve({...context,reduceTransparency:true}).material,'solid');
  assert.equal(resolve({...context,highTextContrast:true}).material,'solid');
  assert.equal(resolve({...context,pipelineFailed:true}).material,'solid');
  assert.equal(resolve({platform:'android',androidApiLevel:36}).material,'solid');
});

test('reduced motion removes press animations without disabling app actions',()=>{
  const base={platform:'android',androidApiLevel:35,reduceTransparency:false,highTextContrast:false};
  assert.equal(resolve({...base,reduceMotion:true}).animatePress,false);
  assert.equal(resolve({...base,reduceMotion:false}).animatePress,true);
  assert.equal(resolve({...base}).animatePress,false);
  assert.equal(resolve(base).minTouchSize,48);
});

test('native glass is bounded to 48dp, static background, no chromatic or jank features',()=>{
  assert.match(action,/width: 48/);
  assert.match(action,/height: 48/);
  assert.match(action,/minHeight: 48/);
  assert.match(action,/enableDynamicBackground=\{false\}/);
  assert.match(action,/globalDownsampleFactor=\{0\.5\}/);
  assert.match(action,/enableChromaticDispersion=\{false\}/);
  assert.match(action,/enableChromaticAberration=\{false\}/);
  assert.match(action,/frameStatsInterval=\{0\}/);
  assert.match(action,/paused=\{!foreground\}/);
  assert.match(action,/onError=\{onError\}/);
  assert.match(action,/onPipelineReady=\{onPipelineReady\}/);
  assert.doesNotMatch(action,/Animated\.timing\(/);
  assert.doesNotMatch(action,/setInterval\(|requestAnimationFrame\(/);
});

test('all header actions retain accessible press, readable fallback and reduced-motion handling',()=>{
  assert.match(action,/accessibilityRole="button"/);
  assert.match(action,/accessibilityLabel=\{accessibilityLabel\}/);
  assert.match(action,/accessibilityState=\{\{ selected, disabled \}\}/);
  assert.match(action,/onPress=\{onPress\}/);
  assert.match(action,/isReduceMotionEnabled\(/);
  assert.match(action,/isHighTextContrastEnabled\(/);
  assert.match(action,/isReduceTransparencyEnabled\(/);
  assert.match(action,/useState\(true\)/);
  assert.match(action,/scale\.setValue\(1\)/);
  assert.match(action,/backgroundColor: theme\.surfaceContainerHigh/);
  assert.match(action,/useNativeDriver: true/);
  assert.match(action,/if \(policy\.material === 'solid'\)/);
  assert.match(action,/return \(\s*<LiquidGlassAndroidView/);
});

test('global PageHeader typography and navigation remain opaque, role-gated and readable',()=>{
  assert.match(header,/backgroundColor: theme\.surfaceContainer/);
  assert.match(header,/color: theme\.ink/);
  assert.match(header,/collapsable=\{false\} style=\{styles\.actions\}/);
  assert.match(header,/<GlassHeaderAction[\s\S]*?accessibilityLabel="Osveži"/);
  assert.match(header,/disabled=\{refreshing\}/);
  assert.match(header,/notificationsVisible \? <PageHeaderNotificationButton/);
  assert.match(header,/unread > 99 \? '99\+'/);
  assert.match(header,/router\.push\('\/notifications'\)/);
  assert.doesNotMatch(header,/router\.push\('\/account'\)/);
  assert.match(header,/source=\{CANONICAL_APP_ICON\}/);
  assert.match(header,/numberOfLines=\{1\}/);
});

test('LG-03 native bottom bar and Build26 Android runtime cannot regress from a header-only update',()=>{
  assert.match(nav,/return \(\s*<LiquidGlassAndroidView/);
  assert.match(root,/collapsable=\{false\}/);
  assert.equal(pkg.dependencies['react-native-android-liquid-glass'],'0.2.1');
  assert.equal(lock.packages['node_modules/react-native-android-liquid-glass'].version,'0.2.1');
  assert.equal(pkg.version,'1.0.0');
  assert.match(appConfig,/1\.0\.0-lg03-play-testing1/);
  assert.equal(eas.build['lg03-play-testing'].channel,'lg03-play-testing');
  assert.equal(eas.build['lg03-play-testing'].android.buildType,'app-bundle');
  assert.equal(eas.build.production.channel,'production');
});
