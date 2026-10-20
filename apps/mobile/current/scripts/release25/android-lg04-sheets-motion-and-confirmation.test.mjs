// LG-04 Wave 3 — shared selection and confirmation overlays never sacrifice readability.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

const read = (file) => readFileSync(new URL(file, import.meta.url), 'utf8');
const motion = read('../../src/components/ui/sheet-motion-policy.ts');
const select = read('../../src/components/ui/select-sheet.tsx');
const actions = read('../../src/components/ui/action-sheet.tsx');
const confirm = read('../../src/components/ui/confirm-action.tsx');
const glass = read('../../src/components/layout/glass-header-action.tsx');
const layout = read('../../src/app/(app)/_layout.tsx');
const pkg = JSON.parse(read('../../package.json'));
const eas = JSON.parse(read('../../eas.json'));

const transpiled = ts.transpileModule(motion, {
  compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
  reportDiagnostics: true,
});
assert.equal(transpiled.diagnostics?.length ?? 0, 0);
const exports = {};
runInNewContext(transpiled.outputText, {
  exports,
  require() { return {useEffect: () => {}, useState: () => [true, () => {}]}; },
}, { filename: 'sheet-motion-policy.ts' });
const resolveSheetAnimationType = exports.resolveSheetAnimationType;

test('reduce motion / unknown setting always prevent modal slide', () => {
  assert.equal(resolveSheetAnimationType(true), 'none');
  assert.equal(resolveSheetAnimationType(undefined), 'none');
  assert.equal(resolveSheetAnimationType(false), 'slide');
  assert.match(motion,/useState\(true\)/);
  assert.match(motion,/isReduceMotionEnabled\(\)/);
  assert.match(motion,/addEventListener\('reduceMotionChanged'/);
  assert.match(motion,/listener\.remove\(\)/);
  assert.match(motion,/if \(mounted\)/);
});

test('select sheet animates only its native Modal and never removes opaque options', () => {
  assert.match(select,/animationType=\{animationType\}/);
  assert.match(select,/useSheetAnimationType\(\)/);
  assert.match(select,/backgroundColor: theme\.surface/);
  assert.match(select,/backgroundColor: theme\.surfaceContainer/);
  assert.match(select,/maxHeight: '84%'/);
  assert.match(select,/style=\{styles\.sheet\}/);
  assert.match(select,/style=\{styles\.options\}/);
  assert.match(select,/<ScrollView/);
  assert.doesNotMatch(select,/enableDynamicBackground/);
  assert.doesNotMatch(select,/Animated\.timing|Animated\.spring/);
});

test('select sheet has one bounded glass close action with correct native parent', () => {
  assert.match(select,/<View collapsable=\{false\} style=\{styles\.sheetHeader\}>/);
  assert.match(select,/<GlassHeaderAction[\s\S]*?accessibilityLabel=\{'Zatvori izbor: ' \+ label\}/);
  assert.match(select,/<Glyph name="close" size=\{20\} color=\{themeColors\.ink\}/);
  assert.match(glass,/minHeight: 48/);
  assert.match(glass,/if \(policy\.material === 'solid'\)/);
  assert.match(glass,/<LiquidGlassAndroidView/);
  assert.match(glass,/enableDynamicBackground=\{false\}/);
  assert.match(select,/onRequestClose=\{\(\) => setOpen\(false\)\}/);
  assert.match(select,/accessibilityLabel="Zatvori izbor"/);
});

test('select sheet retains all option choices, disabled states and onChange transaction', () => {
  assert.match(select,/export type SelectSheetOption/);
  assert.match(select,/accessibilityState=\{\{ disabled, expanded: open \}\}/);
  assert.match(select,/accessibilityState=\{\{ selected: active, disabled: option\.disabled \}\}/);
  assert.match(select,/disabled=\{option\.disabled\}/);
  assert.match(select,/onChange\(option\.value\);\s*setOpen\(false\)/);
  assert.match(select,/value === option\.value|option\.value === value/);
  assert.match(select,/onPress=\{\(\) => setOpen\(true\)\}/);
  assert.match(select,/name="check"/);
  assert.match(select,/triggerError: \{ borderColor: theme\.danger \}/);
});

test('action sheet confirmation flow stays unchanged, content solid and accessibility enhanced', () => {
  assert.match(actions,/export type SheetAction/);
  assert.match(actions,/export function ActionSheet/);
  assert.match(actions,/animationType=\{animationType\}/);
  assert.match(actions,/onRequestClose=\{onClose\}/);
  assert.match(actions,/style=\{StyleSheet\.absoluteFill\}/);
  assert.match(actions,/onPress=\{onClose\}/);
  assert.match(actions,/onPress=\{\(\) => onSelect\(action\.key\)\}/);
  assert.match(actions,/disabled=\{action\.disabled \?\? false\}/);
  assert.match(actions,/accessibilityState=\{\{ disabled: action\.disabled \?\? false \}\}/);
  assert.match(actions,/danger \? styles\.dangerBorder : null/);
  assert.match(actions,/danger \? styles\.dangerText : null/);
  assert.match(actions,/backgroundColor: theme\.surface/);
  assert.match(actions,/backgroundColor: theme\.surfaceContainer/);
  assert.match(actions,/minHeight: 56/);
  assert.match(actions,/minHeight: 48/);
  assert.doesNotMatch(actions,/LiquidGlassAndroidView/);
  assert.doesNotMatch(actions,/setTimeout\(|requestAnimationFrame\(/);
});

test('destructive confirmations remain explicit and guarded by original ConfirmAction', () => {
  assert.match(confirm,/key:'confirm'/);
  assert.match(confirm,/tone:destructive\?'danger':'default'/);
  assert.match(confirm,/disabled:busy/);
  assert.match(confirm,/onConfirm\(\)/);
  assert.match(confirm,/onCancel\(\)/);
  assert.match(confirm,/if\(key==='confirm'\)/);
  assert.doesNotMatch(actions,/onSelect\(action\.key\).*onClose\(\)/s);
});

test('LG03 tested bottom nav and beta release identity remain immutable in this UX batch', () => {
  assert.match(layout,/<AndroidNativeGlassBottomNav \/>/);
  assert.equal(pkg.dependencies['react-native-android-liquid-glass'], '0.2.1');
  assert.equal(pkg.version, '1.0.0');
  assert.equal(eas.build['lg03-play-testing'].channel, 'lg03-play-testing');
  assert.equal(eas.submit['lg03-play-testing'].android.track, 'beta');
  assert.equal(eas.submit.production.android.track, 'production');
});
