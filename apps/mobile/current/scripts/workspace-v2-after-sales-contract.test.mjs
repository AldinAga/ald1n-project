// ALD1N_WORKSPACE_V2_W2_AFTER_SALES_CONTRACT
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const file = path.join(root, 'src/app/(app)/admin/after-sales/[id].tsx');
const readScreen = () => fs.readFileSync(file, 'utf8');

test('After Sales retains all five original stable workspace identifiers under WorkspaceGrid', () => {
  const s = readScreen();
  assert.match(s, /import \{ WorkspaceGrid \} from '@\/components\/workspaces\/workspace-grid'/);
  assert.match(s, /<WorkspaceGrid/);
  assert.match(s, /selected=\{workspace\}/);
  assert.match(s, /onSelect=\{selectWorkspace\}/);
  assert.doesNotMatch(s, /<FilterBar|<FilterChip/);
  for (const id of ['overview', 'case', 'communication', 'actions', 'history']) {
    assert.match(s, new RegExp("value: '" + id + "', glyph:"));
    assert.match(s, new RegExp("workspace !== '" + id + "'"));
  }
});

test('existing screen-owned form and action drafts persist on workspace tile selection', () => {
  const s = readScreen();
  const selection = s.match(/const selectWorkspace = \(next: AfterSalesWorkspace\) => \{([\s\S]*?)\n  \};/);
  assert.ok(selection, 'existing typed screen-owned workspace handler missing');
  assert.match(selection[1], /setWorkspace\(next\)/);
  assert.doesNotMatch(selection[1], /setPanel\(null\)|setSelectedActionId\(null\)|setMessageBody\(|setActionItems\(/);
  for (const name of ['statusValue', 'messageBody', 'messageAttachments', 'actionItems', 'cancellationReason']) {
    assert.match(s, new RegExp('const \\[' + name + ','));
  }
  for (const panel of ['update', 'message', 'action-create', 'action-complete', 'action-cancel']) {
    assert.match(s, new RegExp("panel === '" + panel + "'"));
  }
});

test('compact quick actions map only real server capabilities, retaining refresh', () => {
  const s = readScreen();
  assert.match(s, /import \{ QuickActionHub, type QuickAction \}/);
  assert.match(s, /const quickActions: QuickAction\[\] = \[\]/);
  for (const capability of ['update', 'message', 'execute']) {
    assert.match(s, new RegExp('if \\(data\\.capabilities\\.' + capability + '\\)'));
  }
  assert.match(s, /<QuickActionHub actions=\{quickActions\}/);
  assert.match(s, /id: 'refresh'/);
  assert.match(s, /onPress: \(\) => void query\.refetch\(\)/);
  assert.match(s, /disabled: mutation\.isPending/);
  assert.doesNotMatch(s, /<Text style=\{styles\.sectionTitle\}>Operativne akcije<\/Text>/);
});

test('quick action handlers reuse original panels and never bypass API permission gates', () => {
  const s = readScreen();
  assert.match(s, /onPress: openUpdate/);
  assert.match(s, /setWorkspace\('communication'\); setPanel\('message'\);/);
  assert.match(s, /onPress: openActionCreate/);
  assert.match(s, /const allowed = can\('after_sales\.manage'\)/);
  assert.match(s, /if \(!allowed\) return <UnavailableState/);
  assert.match(s, /apiAdminAfterSales\.actionStart/);
  assert.match(s, /apiAdminAfterSales\.actionComplete/);
  assert.match(s, /apiAdminAfterSales\.actionCancel/);
});

test('cancel confirmation, attachment restrictions and React Query keys remain intact', () => {
  const s = readScreen();
  assert.match(s, /cancellationReason\.trim\(\)\.length < 5/);
  assert.match(s, /action\.capabilities\.complete/);
  assert.match(s, /action\.capabilities\.cancel/);
  assert.match(s, /messageAttachments\.length >= data\.options\.message_limits\.max_attachments/);
  assert.match(s, /adminQueryKeys\.afterSalesAdminCase\(caseId\)/);
  assert.match(s, /adminQueryKeys\.afterSalesAdmin\(\)/);
  assert.match(s, /onPress=\{submitMessage\}/);
});

test('WorkspaceGrid and QuickActionHub remain presentation-only and accessible', () => {
  const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
  const grid = read('src/components/workspaces/workspace-grid.tsx');
  const hub = read('src/components/workspaces/quick-action-hub.tsx');
  assert.match(grid, /useWindowDimensions/);
  assert.match(grid, /accessibilityRole="button"/);
  assert.match(grid, /accessibilityState=\{\{ selected:/);
  assert.match(hub, /accessibilityRole="button"/);
  assert.doesNotMatch(grid + hub, /apiAdminAfterSales|useMutation|fetch\(/);
});
