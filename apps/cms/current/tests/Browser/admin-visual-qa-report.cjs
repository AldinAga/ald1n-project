'use strict';

/*
 * Merges admin-visual-baseline-<browser>.json shards into admin-visual-baseline.json,
 * writes admin-visual-summary.md (also appended to GITHUB_STEP_SUMMARY) and decides the exit code.
 * Author: Ald1n <https://ald1n.com>
 *
 *   node admin-visual-qa-report.cjs <dir-with-shards>
 *
 * Exit 1 when a shard reported infrastructure failures, a shard is missing, or strict mode applies:
 *   ADMIN_VISUAL_STRICT=1                      every module must have zero recorded violations
 *   ADMIN_VISUAL_STRICT_MODULES=orders,reports only these modules must have zero (a redesigned module is added here)
 */
const fs = require('node:fs');
const path = require('node:path');

const dir = process.argv[2];
if (!dir) { console.error('usage: admin-visual-qa-report.cjs <dir>'); process.exit(2); }
const shards = ['chromium', 'webkit'].map((b) => {
  const f = path.join(dir, `admin-visual-baseline-${b}.json`);
  return fs.existsSync(f) ? JSON.parse(fs.readFileSync(f, 'utf8')) : null;
});
const missing = ['chromium', 'webkit'].filter((_, i) => !shards[i]);
const present = shards.filter(Boolean);
const MODULES = ['shell', 'dashboard', 'orders', 'products', 'warranties', 'customers', 'reports', 'settings', 'auth', 'error'];
const strictAll = process.env.ADMIN_VISUAL_STRICT === '1';
const strictList = (process.env.ADMIN_VISUAL_STRICT_MODULES || '').split(',').map((s) => s.trim()).filter(Boolean);
const isStrict = (m) => strictAll || strictList.includes(m);

const merged = {
  merged_from: present.map((s) => s.browser), missing_shards: missing,
  widths: present[0]?.widths, themes: present[0]?.themes, rules: present[0]?.rules,
  infra_failures: present.flatMap((s) => s.infra_failures),
  determinism: present.flatMap((s) => s.determinism),
  cells: present.flatMap((s) => s.cells), findings: present.flatMap((s) => s.findings),
};
fs.writeFileSync(path.join(dir, 'admin-visual-baseline.json'), JSON.stringify(merged, null, 1));

const sum = (arr, f = (x) => x.count) => arr.reduce((a, x) => a + f(x), 0);
const isContrast = (f) => f.rule === 'color-contrast';
const isOverflow = (f) => f.rule === 'horizontal-overflow';
const isRuntime = (f) => f.rule === 'console-error' || f.rule === 'failed-request';
const isStructural = (f) => !isContrast(f) && !isOverflow(f) && !isRuntime(f);

const L = [];
L.push('## Admin visual QA baseline (NO DEPLOY)');
L.push('');
L.push(`Shards: ${present.map((s) => s.browser).join(', ') || 'none'}${missing.length ? ' (MISSING: ' + missing.join(', ') + ')' : ''}. `
  + `Cells rendered: ${merged.cells.filter((c) => c.screenshot).length} of ${merged.cells.length}. `
  + `Infrastructure failures: ${merged.infra_failures.length}. Recorded violations (nodes/cells): ${sum(merged.findings)}.`);
L.push('');
L.push('### Violations by module (today\'s design)');
L.push('');
L.push('| Module | Pages | Cells | Contrast nodes | Overflow cells | Structural nodes | Console / request | Strict |');
L.push('|---|---:|---:|---:|---:|---:|---:|:-:|');
let strictViolations = 0;
for (const m of MODULES) {
  const cells = merged.cells.filter((c) => c.module === m);
  const f = merged.findings.filter((x) => x.module === m);
  const total = sum(f);
  if (isStrict(m)) strictViolations += total;
  L.push(`| ${m} | ${new Set(cells.map((c) => c.page)).size} | ${cells.length} | ${sum(f.filter(isContrast))} | ${f.filter(isOverflow).length} | ${sum(f.filter(isStructural))} | ${sum(f.filter(isRuntime))} | ${isStrict(m) ? 'yes' : 'no'} |`);
}
const grid = (title, pred, count) => {
  L.push('', `### ${title}`, '', `| Theme | ${merged.widths?.join(' | ')} |`, `|---|${(merged.widths || []).map(() => '---:').join('|')}|`);
  for (const t of merged.themes || []) {
    L.push(`| ${t} | ${(merged.widths || []).map((w) => count(merged.findings.filter((x) => pred(x) && x.theme === t && x.width === w))).join(' | ')} |`);
  }
};
grid('Contrast nodes by theme and width (all pages, both browsers)', isContrast, (a) => sum(a));
grid('Pages with horizontal overflow by theme and width', isOverflow, (a) => a.length);
L.push('', '### Worst rules', '', '| Rule | Nodes / cells |', '|---|---:|');
const byRule = {};
for (const f of merged.findings) byRule[f.rule] = (byRule[f.rule] || 0) + f.count;
for (const [r, n] of Object.entries(byRule).sort((a, b) => b[1] - a[1])) L.push(`| ${r} | ${n} |`);
if (merged.determinism.length) {
  L.push('', `Determinism self-check (same page rendered twice in one run): ${merged.determinism.map((d) => `${d.page}/${d.browser}=${d.identicalInRun ? 'identical' : 'DIFFERENT'}`).join(', ')}.`);
}
const skipped = merged.cells.filter((c) => c.status.startsWith('skip'));
if (skipped.length) L.push('', `Skipped cells (not applicable): ${skipped.length}, e.g. ${skipped[0].page} at ${skipped[0].width}px.`);
if (merged.infra_failures.length) L.push('', '### Infrastructure failures', '', ...merged.infra_failures.slice(0, 40).map((x) => '- ' + x));
L.push('', strictAll || strictList.length
  ? `Strict mode: ${strictAll ? 'ALL modules' : strictList.join(', ')}; violations in strict modules: ${strictViolations}.`
  : 'Strict mode: off (baseline run, violations are recorded, not failed).');
const md = L.join('\n') + '\n';
fs.writeFileSync(path.join(dir, 'admin-visual-summary.md'), md);
console.log(md);
if (process.env.GITHUB_STEP_SUMMARY) fs.appendFileSync(process.env.GITHUB_STEP_SUMMARY, md);

const bad = missing.length > 0 || merged.infra_failures.length > 0 || strictViolations > 0;
process.exit(bad ? 1 : 0);
