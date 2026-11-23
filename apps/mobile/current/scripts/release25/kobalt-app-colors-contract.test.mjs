// M1: AppColors keys must come from the Kobalt tokens, in both themes, with AA contrast on primary pairs.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

const read = (p) => readFileSync(new URL(p, import.meta.url), 'utf8');
const load = (src, name, req = () => ({})) => {
  const out = ts.transpileModule(src, {
    compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
    reportDiagnostics: true,
  });
  assert.equal(out.diagnostics?.length ?? 0, 0, name + ' must compile');
  const module = { exports: {} };
  runInNewContext(out.outputText, { module, exports: module.exports, require: req }, { filename: name });
  return module.exports;
};

const tokens = load(read('../../src/design/ald1n-tokens.kobalt.generated.ts'), 'kobalt.ts');
const { createAppColors } = load(read('../../src/design/kobalt-app-colors.ts'), 'adapter.ts', () => tokens);
const themeSource = read('../../src/constants/theme.ts');

const expectedKeys = [
  'background', 'surface', 'surfaceMuted', 'surfaceContainer', 'surfaceContainerHigh',
  'ink', 'muted', 'line', 'outline',
  'primary', 'onPrimary', 'primaryDark', 'primarySoft', 'primaryContainer', 'onPrimaryContainer',
  'secondaryContainer', 'accent', 'accentSoft',
  'success', 'successSoft', 'warning', 'warningSoft', 'danger', 'onDanger', 'dangerSoft',
  'info', 'infoSoft', 'white', 'black', 'hero', 'heroMuted',
];

function flatten(obj, out = new Set()) {
  for (const v of Object.values(obj)) {
    if (typeof v === 'string') out.add(v);
    else if (v && typeof v === 'object') flatten(v, out);
  }
  return out;
}

const parse = (s) => {
  if (s[0] === '#') {
    const n = parseInt(s.slice(1), 16);
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255, 1];
  }
  const m = s.match(/[\d.]+/g).map(Number);
  return [m[0], m[1], m[2], m[3] ?? 1];
};
const over = (f, b) => [0, 1, 2].map((i) => f[i] * f[3] + b[i] * (1 - f[3])).concat(1);
const lum = (c) => {
  const f = (x) => ((x /= 255) <= 0.03928 ? x / 12.92 : ((x + 0.055) / 1.055) ** 2.4);
  return 0.2126 * f(c[0]) + 0.7152 * f(c[1]) + 0.0722 * f(c[2]);
};
const ratio = (a, b) => (Math.max(lum(a), lum(b)) + 0.05) / (Math.min(lum(a), lum(b)) + 0.05);

for (const mode of ['light', 'dark']) {
  const palette = tokens.kobalt[mode];
  const colors = createAppColors(palette);

  test(`${mode}: AppColors keeps every legacy key`, () => {
    for (const key of expectedKeys) assert.ok(typeof colors[key] === 'string', `missing ${key}`);
  });

  test(`${mode}: every AppColors value is a Kobalt token value`, () => {
    const allowed = new Set([...flatten(tokens.kobalt.dark), ...flatten(tokens.kobalt.light)]);
    for (const [key, value] of Object.entries(colors)) assert.ok(allowed.has(value), `${key}=${value} is not a Kobalt token`);
  });

  test(`${mode}: primary pairs meet WCAG AA 4.5:1`, () => {
    const c = Object.fromEntries(Object.entries(colors).map(([k, v]) => [k, parse(v)]));
    const surface = c.surface;
    const pairs = {
      'ink/background': [c.ink, over(c.background, surface)],
      'ink/surface': [c.ink, surface],
      'muted/background': [c.muted, c.background],
      'muted/surface': [c.muted, surface],
      'onPrimary/primary': [c.onPrimary, c.primary],
      'primaryDark/primarySoft': [c.primaryDark, over(c.primarySoft, surface)],
      'success/successSoft': [c.success, over(c.successSoft, surface)],
      'warning/warningSoft': [c.warning, over(c.warningSoft, surface)],
      'info/infoSoft': [c.info, over(c.infoSoft, surface)],
      'danger/surface': [c.danger, surface],
      'onDanger/danger': [c.onDanger, c.danger],
    };
    for (const [name, [fg, bg]] of Object.entries(pairs)) {
      assert.ok(ratio(fg, bg) >= 4.5, `${mode} ${name} = ${ratio(fg, bg).toFixed(2)}`);
    }
  });
}

test('theme.ts builds colorThemes from the Kobalt adapter, not the legacy orange tokens', () => {
  assert.match(themeSource, /createAppColors\(kobalt\.light\)/);
  assert.match(themeSource, /createAppColors\(kobalt\.dark\)/);
  assert.doesNotMatch(themeSource, /ald1nDesignTokens\.(light|dark)/);
  assert.doesNotMatch(read('../../src/features/notifications/push-service.ts'), /#6D45E5/i);
});
