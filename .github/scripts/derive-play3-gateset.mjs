#!/usr/bin/env node
// Author: Ald1n <https://ald1n.com>
// Derives the play3 release gate set (B1141 build, B1142 post-build, play3 attest) from the frozen play2 set
// (B1041, B1042, b1042-m17-aab-attest). NOT run yet: it needs the merged source SHA, so it is run by hand AFTER PR 234 is merged:
//
//   node .github/scripts/derive-play3-gateset.mjs --sha <40 hex merged main commit> --tree <40 hex tree of that commit> \
//        --pr 234 --ci-runs <id,id,...> [--out <repo root>]
//
// Every substitution must hit an exact number of times, otherwise the script aborts and writes nothing.
// The 16 KB / ELF / bundletool / certificate checks are copied unchanged. Only identity values move:
//   source SHA/tree, PR number and its CI run ids, runtime play2->play3, expo ~58.0.22->~58.0.26, eas-cli 27.2.0->27.11.0,
//   remote versionCode gate 28->29 (the new AAB must be 30), and the PR guard (draft PR 128 -> merged PR, merge commit == SHA).
// The result is a proposal for review: nothing here builds, submits or publishes; B1141 stays manual (workflow_dispatch on main).
import fs from 'node:fs';
import path from 'node:path';

const arg = (n) => { const i = process.argv.indexOf('--' + n); return i > 0 ? process.argv[i + 1] : undefined; };
const sha = arg('sha'), tree = arg('tree'), pr = arg('pr'), runs = (arg('ci-runs') || '').split(',').filter(Boolean);
const out = path.resolve(arg('out') || process.cwd());
const hex40 = /^[0-9a-f]{40}$/;
if (!hex40.test(sha || '') || !hex40.test(tree || '') || !/^[1-9][0-9]*$/.test(pr || '') || !runs.length || runs.some((r) => !/^[1-9][0-9]*$/.test(r))) {
  console.error('Usage: --sha <40hex> --tree <40hex> --pr <number> --ci-runs <id,id,...> [--out <dir>]'); process.exit(2);
}
const OLD_SHA = '6adb3b440b8cfdc068b142a98c418d9d91487f64', OLD_TREE = 'fc4578c93820df8b0c85f5215924ab2bc501ba01';
const read = (f) => fs.readFileSync(path.join(out, f), 'utf8').replace(/\r\n/g, '\n');

// [from, to, exact count]
function sub(text, rules, label) {
  for (const [from, to, n] of rules) {
    const c = text.split(from).length - 1;
    if (c !== n) throw new Error(`${label}: expected ${n} x ${JSON.stringify(from)}, found ${c}`);
    text = text.split(from).join(to);
  }
  return text;
}
const PR_GUARD_OLD = `if(pr.head?.sha!==sha || pr.head?.repo?.full_name!=='AldinAga/ald1n-project' ||
             pr.state!=='open' || pr.draft!==true || pr.merged_at!==null)`;
const PR_GUARD_NEW = `if(pr.merge_commit_sha!==sha || pr.base?.repo?.full_name!=='AldinAga/ald1n-project' ||
             pr.state!=='closed' || pr.merged_at===null)`;
const ids = '[41333706448,41333706461,41333706455,41333706508,41333706470,41333706450,41333706493]';
const rename = [['b1042-m17-aab-attest', 'b1142-m17-play3-aab-attest'], ['b1041', 'b1141'], ['B1041', 'B1141'], ['b1042', 'b1142'], ['B1042', 'B1142']];
const renameAll = (t) => rename.reduce((a, [f, g]) => a.split(f).join(g), t);

const files = {};
files['.github/workflows/b1141-m17-sdk58-play3-eas27-signed-aab-manual.yml'] = renameAll(sub(read('.github/workflows/b1041-m17-sdk58-eas27-signed-aab-manual.yml'), [
  [OLD_SHA, sha, 2], [OLD_SHA.slice(0, 12), sha.slice(0, 12), 1], [OLD_TREE, tree, 1],
  ["'1.0.0-m17-sdk58-play2'", "'1.0.0-m17-sdk58-play3'", 1], ["'~58.0.22'", "'~58.0.26'", 1],
  ['27.2.0', '27.11.0', 4], ['PR128', 'PR' + pr, 3], ['pulls/128', 'pulls/' + pr, 1],
  [PR_GUARD_OLD, PR_GUARD_NEW, 1], [ids, '[' + runs.join(',') + ']', 1],
  ['remote versionCode 28 gate', 'remote versionCode 29 gate', 1],
  ['Number(code)!==28 || !/^(28)$/', 'Number(code)!==29 || !/^(29)$/', 1], ['HOLD_REMOTE_VERSION_NOT_28_NO_BUILD', 'HOLD_REMOTE_VERSION_NOT_29_NO_BUILD', 1],
], 'b1041'));
files['.github/workflows/b1142-m17-play3-aab-postbuild-readonly.yml'] = renameAll(sub(read('.github/workflows/b1042-m17-aab-postbuild-readonly.yml'), [
  [OLD_SHA, sha, 2], [OLD_TREE, tree, 1], ["'1.0.0-m17-sdk58-play2'", "'1.0.0-m17-sdk58-play3'", 1], ["'~58.0.22'", "'~58.0.26'", 1], ['27.2.0', '27.11.0', 3],
], 'b1042'));
files['.github/scripts/b1142-m17-play3-aab-attest.mjs'] = sub(read('.github/scripts/b1042-m17-aab-attest.mjs'), [
  [OLD_SHA, sha, 1], ["'1.0.0-m17-sdk58-play2'", "'1.0.0-m17-sdk58-play3'", 1],
  ['<= 28', '<= 29', 1], ['VERSION_CODE_NOT_GREATER_THAN_28', 'VERSION_CODE_NOT_GREATER_THAN_29', 1],
], 'attest');
files['.github/scripts/b1142-m17-play3-aab-attest.test.mjs'] = sub(read('.github/scripts/b1042-m17-aab-attest.test.mjs'), [
  ['./b1042-m17-aab-attest.mjs', './b1142-m17-play3-aab-attest.mjs', 1],
  ["appBuildVersion:'29'", "appBuildVersion:'30'", 1], ['receipt.versionCode,29', 'receipt.versionCode,30', 1], ['versionCode:29,', 'versionCode:30,', 1],
  ["appBuildVersion:'28'", "appBuildVersion:'29'", 1], ['VERSION_CODE_NOT_GREATER_THAN_28', 'VERSION_CODE_NOT_GREATER_THAN_29', 1],
], 'attest test');

for (const [f, t] of Object.entries(files)) {
  if (/6adb3b44|play2|27\.2\.0/.test(t)) throw new Error(`${f}: a play2 literal survived`);
}
for (const [f, t] of Object.entries(files)) {
  fs.mkdirSync(path.dirname(path.join(out, f)), { recursive: true });
  fs.writeFileSync(path.join(out, f), t);
  console.log('WROTE ' + f);
}
console.log('PLAY3_GATESET_DERIVED. Review the diff against b1041/b1042, then run: node --test .github/scripts/b1142-m17-play3-aab-attest.test.mjs');
