#!/usr/bin/env node
// G7 gate: builds apps/cms/current/tests/fixtures/removed-css-classes.json, the frozen list of class names that only
// the legacy stylesheets define. LegacyCssClassesContractTest fails when a view or script uses one of them.
//   node scripts/legacy-css-classes.mjs           write the fixture
//   node scripts/legacy-css-classes.mjs --check   exit 1 when the fixture is stale (used by CI)
// Author: Ald1n <https://ald1n.com>
import { readFileSync, writeFileSync, existsSync } from 'node:fs';
import { createHash } from 'node:crypto';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const cms = join(dirname(fileURLToPath(import.meta.url)), '..', 'apps', 'cms', 'current');
const css = (f) => join(cms, 'public', 'assets', 'css', f);

const LEGACY = [
  'app.css', 'ald1n-ui-v2.css', 'ald1n-liquid-glass.css', 'ald1n-ui-refinement.css',
  'ald1n-dashboard-d1.css', 'vendor/liquid-glass-web.css',
];
const KEPT = [
  'ald1n-admin.css', 'ald1n-tokens.css', 'ald1n-auth.css', 'ald1n-admin-settings.css',
  'ald1n-admin-catalog.css', 'ald1n-admin-products.css', 'ald1n-admin-g5.css', 'ald1n-icon-actions.css',
];
// Generic state/utility words that views and scripts use as plain markers or for third-party markup; never "legacy".
const FALSE_POSITIVES = [
  'active', 'is-active', 'open', 'is-open', 'show', 'hidden', 'sr-only', 'visually-hidden', 'disabled', 'selected',
  'checked', 'loading', 'is-loading', 'danger', 'warning', 'success', 'error', 'info', 'primary', 'secondary',
  'dark', 'light', 'left', 'right', 'center', 'small', 'large', 'full', 'current', 'invalid', 'valid', 'required',
  'collapsed', 'expanded', 'visible', 'sticky', 'fixed', 'no-js', 'js', 'icon', 'text', 'title', 'label', 'link',
  'critical', 'favicon',
];

function classesOf(file) {
  if (!existsSync(file)) return new Set();
  let raw = readFileSync(file, 'utf8');
  // The compat layer in ald1n-admin.css re-skins the old classes; it does not count as defining them (deleted in G7).
  const from = raw.indexOf('LEGACY COMPAT LAYER');
  const to = raw.indexOf('Back to top (all layouts');
  if (from > -1 && to > from) raw = raw.slice(0, raw.lastIndexOf('/*', from)) + raw.slice(raw.lastIndexOf('/*', to));
  const src = raw.replace(/\/\*[\s\S]*?\*\//g, '');
  const out = new Set();
  let prelude = '';
  let depthInDecl = 0; // > 0 while inside a declaration block
  const stack = [];
  for (const ch of src) {
    if (ch === '{') {
      const p = prelude.trim();
      // A block is a declaration block unless its prelude is an at-rule that nests rules.
      const nests = /^@(media|supports|layer|container|document|scope|starting-style)\b/.test(p);
      stack.push(nests);
      if (!nests && !p.startsWith('@')) {
        const selector = p.replace(/\[[^\]]*\]/g, '').replace(/:(?:not|is|where|has)\(/g, ' (');
        for (const m of selector.matchAll(/\.(-?[_a-zA-Z][\w-]*)/g)) out.add(m[1]);
        depthInDecl++;
      } else if (!nests) {
        depthInDecl++; // @font-face, @keyframes: contents are not selectors we track (keyframe steps have no classes)
      }
      prelude = '';
    } else if (ch === '}') {
      const nests = stack.pop();
      if (nests === false) depthInDecl--;
      prelude = '';
    } else if (depthInDecl === 0) {
      prelude += ch;
    } else if (ch === ';') {
      prelude = '';
    }
  }
  return out;
}

// G7: once an old stylesheet is deleted the list can no longer be derived. The fixture is then frozen: --check only
// validates it (still the same legacy_files, still more than 500 classes) and the write mode refuses to shrink it.
const missingLegacy = LEGACY.filter((f) => !existsSync(css(f)));
if (missingLegacy.length > 0) {
  const frozenPath = join(cms, 'tests', 'Fixtures', 'removed-css-classes.json');
  if (!process.argv.includes('--check')) {
    console.error(`legacy stylesheet(s) deleted (${missingLegacy.join(', ')}); the fixture is frozen and is not rewritten.`);
    process.exit(1);
  }
  const frozen = existsSync(frozenPath) ? JSON.parse(readFileSync(frozenPath, 'utf8')) : null;
  // Exact pin: the ratchet list may only change together with a deliberate edit of these two constants.
  const FROZEN_COUNT = 1139;
  const FROZEN_SHA256 = 'a81f257a27aa45a9967aa7610849b5e9739c26d89d493c4668dd4660ed87acc7'; // sha256 of classes.join('\n')
  const digest = frozen && Array.isArray(frozen.classes) ? createHash('sha256').update(frozen.classes.join('\n')).digest('hex') : '';
  if (!frozen || JSON.stringify(frozen.legacy_files) !== JSON.stringify(LEGACY) || frozen.classes.length !== FROZEN_COUNT || digest !== FROZEN_SHA256) {
    console.error(`removed-css-classes.json changed after the legacy stylesheets were deleted (expected ${FROZEN_COUNT} classes, sha256 ${FROZEN_SHA256}).`);
    process.exit(1);
  }
  console.log(`removed-css-classes.json frozen (${frozen.classes.length} classes); deleted: ${missingLegacy.join(', ')}`);
  process.exit(0);
}

const legacy = new Set(LEGACY.flatMap((f) => [...classesOf(css(f))]));
const kept = new Set(KEPT.flatMap((f) => [...classesOf(css(f))]));
const skip = new Set(FALSE_POSITIVES);
const classes = [...legacy].filter((c) => !kept.has(c) && !skip.has(c)).sort();

const fixture = JSON.stringify({
  note: 'Generated by scripts/legacy-css-classes.mjs. Classes defined only by the legacy stylesheets.',
  legacy_files: LEGACY,
  kept_files: KEPT,
  excluded_false_positives: [...FALSE_POSITIVES].sort(),
  classes,
}, null, 2) + '\n';

const target = join(cms, 'tests', 'Fixtures', 'removed-css-classes.json');
if (process.argv.includes('--check')) {
  const current = existsSync(target) ? readFileSync(target, 'utf8') : '';
  if (current !== fixture) {
    console.error('removed-css-classes.json is stale; run: node scripts/legacy-css-classes.mjs');
    process.exit(1);
  }
  console.log(`removed-css-classes.json up to date (${classes.length} classes)`);
} else {
  writeFileSync(target, fixture);
  console.log(`wrote ${classes.length} classes to ${target}`);
}
