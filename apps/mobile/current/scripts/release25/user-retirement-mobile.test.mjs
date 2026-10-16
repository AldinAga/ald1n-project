import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import ts from 'typescript';

const read = (p) => readFileSync(new URL('../../src/' + p, import.meta.url), 'utf8');
const screen = read('app/(app)/admin/users/[id].tsx');
const api = read('features/admin/users-admin-api.ts');

test('retirement UI and typed API parse', () => {
  for (const [file, body, kind] of [['detail.tsx', screen, ts.ScriptKind.TSX], ['api.ts', api, ts.ScriptKind.TS]]) {
    const tree = ts.createSourceFile(file, body, ts.ScriptTarget.Latest, true, kind);
    assert.equal(tree.parseDiagnostics.length, 0);
  }
});

test('retirement requires server impact, active superadmin, target check and literal confirmation', () => {
  assert.match(screen, /bootstrap\?\.user\.role === 'superadmin'/);
  assert.match(screen, /bootstrap\.user\.id !== user\.id/);
  assert.match(screen, /user\.role\?\.slug !== 'superadmin'/);
  assert.match(screen, /retirementPreview\.data/);
  assert.match(screen, /retirementConfirmation !== 'OBRISI'/);
  assert.match(screen, /key !== 'orders' && count > 0/);
  assert.match(screen, /retirementMutation\.isPending/);
  assert.match(screen, /invalidateQueries/);
});

test('retirement uses authenticated canonical API and no direct model DELETE', () => {
  assert.match(api, /admin\/users\/\$\{userId\}\/retirement/);
  assert.match(api, /method: 'POST', body: \{ confirmation \}/);
  assert.doesNotMatch(api, /method: 'DELETE'/);
});
