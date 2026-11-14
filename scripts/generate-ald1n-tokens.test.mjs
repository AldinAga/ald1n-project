/*
 * Tests for the Kobalt token generator. Run: node --test scripts/generate-ald1n-tokens.test.mjs
 * @author Ald1n <https://ald1n.com>
 */
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import {
  KNOWN_GAPS,
  MODES,
  PARITY_COLORS,
  PARITY_SHADOWS,
  PATHS,
  ROOT,
  build,
  contrastRatio,
  evaluateGates,
  leaves,
  loadTokens,
  parity,
  run,
  validateSource,
} from './generate-ald1n-tokens.mjs';

const fresh = () => loadTokens(ROOT);
const readRoot = (rel) => fs.readFileSync(path.join(ROOT, rel), 'utf8').replace(/\r\n/g, '\n');
const clone = (o) => JSON.parse(JSON.stringify(o));

/** Copy the five files the generator touches into a temp repo root. */
function tempRoot() {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'a1-tokens-'));
  for (const rel of Object.values(PATHS)) {
    fs.mkdirSync(path.dirname(path.join(dir, rel)), { recursive: true });
    if (fs.existsSync(path.join(ROOT, rel))) fs.copyFileSync(path.join(ROOT, rel), path.join(dir, rel));
  }
  return dir;
}

/** Independent of the generator: custom property name for a leaf path. */
function expectedName(full) {
  const k = (s) => s.replace(/([a-z])([A-Z0-9])/g, '$1-$2').toLowerCase();
  const [g, ...rest] = full;
  if (g === 'color') return '--a1-' + rest.slice(1).map(k).join('-');
  if (g === 'shadow') return '--a1-shadow-' + rest.slice(1).map(k).join('-');
  return '--a1-' + [g, ...rest].map(k).join('-');
}

test('output is byte-identical on two runs, also from a fresh parse', () => {
  const a = build(fresh());
  const b = build(fresh());
  assert.equal(a.css, b.css);
  assert.equal(a.ts, b.ts);
  assert.ok(!a.css.includes('\r') && !a.ts.includes('\r'), 'LF only');
  assert.ok(a.css.endsWith('\n') && a.ts.endsWith('\n'));
  // Writing twice to disk gives the same bytes.
  const dir = tempRoot();
  assert.equal(run([], dir, () => {}), 0);
  const first = fs.readFileSync(path.join(dir, PATHS.css));
  assert.equal(run([], dir, () => {}), 0);
  assert.ok(first.equals(fs.readFileSync(path.join(dir, PATHS.css))));
});

test('committed outputs equal a fresh build', () => {
  const out = build(fresh());
  assert.equal(readRoot(PATHS.css), out.css);
  assert.equal(readRoot(PATHS.ts), out.ts);
});

test('every token of the JSON appears in the CSS, in the right block', () => {
  const tokens = fresh();
  const css = readRoot(PATHS.css);
  const iLight = css.indexOf(':root[data-theme="light"] {');
  const iSystem = css.indexOf('@media (prefers-color-scheme: light) {');
  assert.ok(iLight > 0 && iSystem > iLight);
  const blocks = { dark: css.slice(0, iLight), light: css.slice(iLight, iSystem), system: css.slice(iSystem) };
  const all = [...leaves(tokens)];
  assert.ok(all.length >= 200, `token floor, got ${all.length}`);
  const seen = new Set();
  for (const l of all) {
    const name = expectedName(l.full);
    assert.ok(!seen.has(`${l.mode}|${name}`), `duplicate ${name}`);
    seen.add(`${l.mode}|${name}`);
    const re = new RegExp(`^\\s+${name}: [^;]+;$`, 'm');
    if (l.mode === 'light') {
      assert.match(blocks.light, re, `${name} missing in light block`);
      assert.match(blocks.system.slice(0, blocks.system.indexOf('/* Phones')), re, `${name} missing in system block`);
    } else {
      assert.match(blocks.dark, re, `${name} missing in dark/default block`);
    }
  }
});

test('light and dark are both present and have the same token set', () => {
  const tokens = fresh();
  const css = readRoot(PATHS.css);
  assert.match(css, /^:root \{\n  color-scheme: dark;/m);
  assert.match(css, /^:root\[data-theme="light"\] \{\n  color-scheme: light;/m);
  assert.match(css, /@media \(prefers-color-scheme: light\) \{\n:root\[data-theme="system"\] \{/);
  for (const q of ['@supports not ((-webkit-backdrop-filter:blur(1px)) or (backdrop-filter:blur(1px)))', '(prefers-reduced-transparency: reduce)', '(prefers-contrast: more)', '(forced-colors: active)']) {
    assert.ok(css.includes(q), `fallback ${q}`);
  }
  const names = (mode) => [...leaves(tokens)].filter((l) => l.mode === mode).map((l) => l.full.slice(2).join('.')).sort();
  assert.deepEqual(names('dark'), names('light'));
  assert.ok(names('dark').length >= 62);
  const ts = readRoot(PATHS.ts);
  assert.match(ts, /export const kobaltDark = /);
  assert.match(ts, /export const kobaltLight: KobaltPalette = /);
  assert.deepEqual(MODES, ['dark', 'light']);
  // The theme selectors are the only ones that set colours: no admin-only attribute is required.
  const selectors = css.split('\n').filter((l) => /^\s*:root/.test(l));
  assert.ok(selectors.length >= 4 && selectors.every((l) => !l.includes('data-theme-mode')), 'no selector on data-theme-mode');
});

test('contrast function against known pairs', () => {
  assert.equal(contrastRatio('#000000', '#ffffff').toFixed(2), '21.00');
  assert.equal(contrastRatio('#767676', '#ffffff').toFixed(2), '4.54'); // smallest grey that passes AA on white
  assert.equal(contrastRatio('#777777', '#ffffff').toFixed(2), '4.48'); // one step lighter fails
  assert.throws(() => contrastRatio('rgba(0,0,0,.5)', '#ffffff'), /opaque/);
});

test('storefront parity: Kobalt equals the storefront in both modes', () => {
  const errors = parity(fresh(), readRoot(PATHS.storefront), readRoot(PATHS.gallery));
  assert.deepEqual(errors, []);
  assert.ok(Object.keys(PARITY_COLORS).length >= 57 && Object.keys(PARITY_SHADOWS).length === 5, 'mapping floor');
});

test('parity mutations: a changed token, a changed storefront value, an unmapped property, a divergent system block', () => {
  const sf = readRoot(PATHS.storefront);
  const ga = readRoot(PATHS.gallery);

  const t = clone(fresh());
  t.color.light.text.primary.$value = '#121b25';
  assert.ok(parity(t, sf, ga).some((e) => e.includes('color.light.text.primary')), 'token drift');

  const t2 = clone(fresh());
  t2.shadow.dark.card.$value[1].offsetY = 19;
  assert.ok(parity(t2, sf, ga).some((e) => e.includes('shadow.dark.card')), 'shadow drift');

  const sf2 = sf.replace('--accent:#a9c3ff;', '--accent:#a9c3fe;');
  assert.notEqual(sf2, sf);
  assert.ok(parity(fresh(), sf2, ga).some((e) => e.includes('accent.link')), 'storefront drift');

  const sf3 = sf.replace('--radius-sm:12px;', '--radius-sm:12px;--brand-new:#123456;');
  assert.notEqual(sf3, sf);
  assert.ok(parity(fresh(), sf3, ga).some((e) => e.includes('--brand-new') && e.includes('NOT_TOKENISED')), 'unmapped property');

  const sf4 = sf.replace(/(:root:not\(\[data-theme=dark\]\)\{[^}]*?)--focus:#1d4fd6;/, '$1--focus:#1d4fd7;');
  assert.notEqual(sf4, sf);
  assert.ok(parity(fresh(), sf4, ga).some((e) => e.includes('system block differs')), 'system block');
});

test('contrast gates pass on the real tokens, both modes, with no known gaps', () => {
  const { results, errors } = evaluateGates(fresh());
  assert.deepEqual(errors, []);
  assert.ok(results.length >= 26, `gate floor, got ${results.length}`);
  for (const mode of MODES) assert.ok(results.filter((r) => r.mode === mode).length >= 13);
  assert.deepEqual(KNOWN_GAPS, {}, 'no known gaps are expected on the storefront values');
});

test('gate mutations: a failing pair fails, the allow-list accepts it only with the exact ratio, stale entries fail', () => {
  const t = clone(fresh());
  t.color.dark.text.primary.$value = '#777777'; // far below 7:1 on the dense glass
  const bad = evaluateGates(t, {});
  assert.ok(bad.errors.some((e) => e.includes('dark: text.primary on dense glass over page')), 'gate fires');
  assert.equal(bad.errors.length, 1, 'only that pair fails');
  const hit = bad.results.find((r) => r.id === 'dark: text.primary on dense glass over page');

  const ok = evaluateGates(t, { [hit.id]: { ratio: hit.shown, reason: 'synthetic' } });
  assert.deepEqual(ok.errors, [], 'listed gap is accepted');

  const moved = evaluateGates(t, { [hit.id]: { ratio: '1.00', reason: 'synthetic' } });
  assert.ok(moved.errors.some((e) => e.includes('Known gap moved')), 'wrong ratio rejected');

  const stale = evaluateGates(fresh(), { 'dark: text.primary on dense glass over page': { ratio: '14.99', reason: 'synthetic' } });
  assert.ok(stale.errors.some((e) => e.includes('Stale known gap') && e.includes('now passes')), 'passing pair listed');

  const unknown = evaluateGates(fresh(), { 'dark: no such gate': { ratio: '1.00', reason: 'synthetic' } });
  assert.ok(unknown.errors.some((e) => e.includes('no gate named')), 'unknown id');

  // A translucent status background is measured over the dense glass, not over nothing.
  const t2 = clone(fresh());
  t2.color.dark.status.ok.text.$value = '#5a6a62';
  assert.ok(evaluateGates(t2, {}).errors.some((e) => e.includes('status.ok.text')));

  // Focus ring gate.
  const t3 = clone(fresh());
  t3.color.light.focus.ring.$value = '#e0e4ea';
  assert.ok(evaluateGates(t3, {}).errors.some((e) => e.includes('light: focus.ring')));
});

test('source validation: unsorted keys and bad colours are rejected', () => {
  assert.deepEqual(validateSource(fresh()), []);
  const t = fresh();
  const { surface, ...rest } = t.color.dark;
  t.color.dark = { surface, ...rest }; // surface now first: unsorted
  assert.ok(validateSource(t).some((e) => e.includes('color.dark')));
  const t2 = clone(fresh());
  t2.color.dark.text.primary.$value = 'not-a-colour';
  assert.ok(validateSource(t2).some((e) => e.includes('invalid colour')));
});

test('--check: clean tree passes, a hand-edited output or a stale output fails', () => {
  const dir = tempRoot();
  const logs = [];
  assert.equal(run(['--check'], dir, (m) => logs.push(m)), 0, logs.join('\n'));

  const cssFile = path.join(dir, PATHS.css);
  const original = fs.readFileSync(cssFile, 'utf8');
  fs.writeFileSync(cssFile, original.replace('--a1-surface-page: #0d1216;', '--a1-surface-page: #0d1217;'));
  const l2 = [];
  assert.equal(run(['--check'], dir, (m) => l2.push(m)), 1);
  assert.ok(l2.some((m) => m.includes('Output out of date') && m.includes('ald1n-tokens.css')));
  fs.writeFileSync(cssFile, original);

  const tsFile = path.join(dir, PATHS.ts);
  fs.appendFileSync(tsFile, '// hand edit\n');
  const l3 = [];
  assert.equal(run(['--check'], dir, (m) => l3.push(m)), 1);
  assert.ok(l3.some((m) => m.includes('Output out of date') && m.includes('kobalt.generated.ts')));

  // Changing the source without regenerating is also stale.
  fs.writeFileSync(tsFile, fs.readFileSync(path.join(ROOT, PATHS.ts)));
  const srcFile = path.join(dir, PATHS.source);
  const src = fs.readFileSync(srcFile, 'utf8');
  fs.writeFileSync(srcFile, src.replace('"$value": "#eef2f5"', '"$value": "#eef2f6"'));
  const l4 = [];
  assert.equal(run(['--check'], dir, (m) => l4.push(m)), 1);
  assert.ok(l4.some((m) => m.includes('Output out of date')) && l4.some((m) => m.includes('text.primary')), 'stale and parity both report');
});
