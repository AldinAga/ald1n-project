// ALD1N_WORKSPACE_V2_W2_FIELD_OPERATIONS_CONTRACT
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const file = path.join(root, 'src/app/(app)/admin/field-operations/[id].tsx');
const screen = () => fs.readFileSync(file, 'utf8');

test('Field Operations uses five stable workspaces with accessible icon grid', () => {
  const s = screen();
  assert.match(s, /import \{ WorkspaceGrid \} from '@\/components\/workspaces\/workspace-grid'/);
  assert.match(s, /<WorkspaceGrid/);
  assert.match(s, /selected=\{workspace\}/);
  assert.match(s, /onSelect=\{selectWorkspace\}/);
  assert.doesNotMatch(s, /<FilterBar|<FilterChip/);
  for (const id of ['overview', 'planning', 'execution', 'parts', 'documents']) {
    assert.match(s, new RegExp("value: '" + id + "', glyph:"));
  }
});

test('navigating workspaces does not discard scheduled, completion, part or cancel drafts', () => {
  const s = screen();
  const selection = s.match(/const selectWorkspace = \(next: FieldWorkWorkspace\) => \{([\s\S]*?)\n  \};/);
  assert.ok(selection, 'typed selection handler missing');
  assert.match(selection[1], /setWorkspace\(next\)/);
  assert.doesNotMatch(selection[1], /setPanel\(null\)|setTeamId\(|setCompletionResult\(|setCancelReason\(|setServicePartId\(/);
  for (const p of ['schedule', 'complete', 'cancel', 'part-add']) {
    assert.match(s, new RegExp("\\{panel === '" + p + "' \\? \\("));
  }
  assert.doesNotMatch(s, /workspace === 'planning' && panel|workspace === 'execution' && panel|workspace === 'parts' && panel/);
  for (const value of ['plannedStartAt', 'completionResult', 'completionAttachments', 'partConsumption', 'cancelReason', 'servicePartId', 'partNotes']) {
    assert.match(s, new RegExp('const \\[\\s*' + value + ','));
  }
});

test('quick-action hub is placed after hero, before workspace and exposes one action surface', () => {
  const s = screen();
  const hero = s.indexOf('<Card style={styles.hero}>');
  const hub = s.indexOf('<QuickActionHub actions={quickActions}');
  const grid = s.indexOf('<WorkspaceGrid');
  assert.ok(hero >= 0 && hub > hero && grid > hub);
  assert.equal(s.split('<QuickActionHub').length - 1, 1);
  assert.doesNotMatch(s, /<FilterBar|<FilterChip/);
  assert.match(s, /maxVisible=\{4\}/);
});

test('all original Field Operations actions remain server capability gated', () => {
  const s = screen();
  assert.match(s, /const quickActions: QuickAction\[\] = \[\]/);
  for (const capability of ['can_schedule', 'can_mark_en_route', 'can_mark_on_site', 'can_complete', 'can_cancel', 'can_add_parts', 'can_reserve_parts']) {
    assert.match(s, new RegExp('if \\(data\\.capabilities\\.' + capability));
  }
  assert.match(s, /data\.parts\.some\(\(line\) => line\.supply_mode === 'local_stock'\)/);
  assert.match(s, /id: 'cancel', label:/);
  assert.match(s, /kind: 'danger'/);
  assert.match(s, /kind: quickActions\.length === 0 \? 'primary' : 'secondary'/);
  assert.match(s, /disabled: mutation\.isPending/);
  assert.match(s, /id: 'refresh'/);
});

test('existing workflow writes, transitions and secure document downloads are unchanged', () => {
  const s = screen();
  for (const n of ['schedule', 'enRoute', 'onSite', 'complete', 'cancel', 'partAdd', 'partReserve', 'partRemove']) {
    assert.match(s, new RegExp('apiAdminFieldOperations\\.' + n + '\\('));
  }
  assert.match(s, /keyedFieldWorkPartConsumption/);
  assert.match(s, /openAfterSalesAttachment\(attachment\)/);
  assert.match(s, /adminQueryKeys\.fieldWorkOrder\(workOrderId\)/);
  assert.match(s, /adminQueryKeys\.fieldOperations\(\)/);
});

test('dangerous operation keeps reason/confirmation and completion input limits', () => {
  const s = screen();
  assert.match(s, /onPress: openCancel/);
  assert.match(s, /cancelReason\.trim\(\)\.length < 5/);
  assert.match(s, /completionResult\.trim\(\)\.length < 5/);
  assert.match(s, /completionAttachments\.length >= data\.options\.completion_limits\.max_attachments/);
  assert.match(s, /const allowed = can\('field_operations\.view'\)/);
  assert.match(s, /if \(!allowed\) return <UnavailableState/);
});

test('workspace-only selection never calls an API or starts a mutation', () => {
  const s = screen();
  const selection = s.match(/const selectWorkspace = \(next: FieldWorkWorkspace\) => \{([\s\S]*?)\n  \};/);
  assert.ok(selection);
  assert.doesNotMatch(selection[1], /apiAdmin|execute\(|mutate|fetch\(/);
  assert.match(s, /onSelect=\{selectWorkspace\}/);
});

test('shared W1 components remain responsive, screen-reader accessible and presentation only', () => {
  const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');
  const grid = read('src/components/workspaces/workspace-grid.tsx');
  const hub = read('src/components/workspaces/quick-action-hub.tsx');
  assert.match(grid, /useWindowDimensions/);
  assert.match(grid, /accessibilityRole="button"/);
  assert.match(grid, /accessibilityState=\{\{ selected:/);
  assert.match(hub, /accessibilityRole="button"/);
  assert.doesNotMatch(grid + hub, /apiAdminFieldOperations|useMutation|fetch\(/);
});

test('Batch84 validator checks Field Operations W2 navigation without relaxing domain guards', () => {
  const validator = fs.readFileSync(path.join(root, 'scripts/validate-project.mjs'), 'utf8');
  const start = validator.indexOf('// MOBILE_V1_0_ADMIN_FIELD_OPERATIONS_DETAIL_UX_REORGANIZATION_BATCH84_VALIDATOR');
  const end = validator.indexOf('// MOBILE_V1_0_ADMIN_AFTER_SALES_DETAIL_UX_REORGANIZATION_BATCH83_VALIDATOR', start);
  assert.ok(start >= 0 && end > start, 'validator block is missing');
  const block = validator.slice(start, end);
  const required = [
    "fieldOperationsDetailUxBatch84.includes('<WorkspaceGrid')",
    "fieldOperationsDetailUxBatch84.includes('<QuickActionHub actions={quickActions}')",
    "fieldOperationsDetailUxBatch84.includes('selected={workspace}')",
    "fieldOperationsDetailUxBatch84.includes('onSelect={selectWorkspace}')",
    "fieldOperationsDetailUxBatch84.includes(\"panel === 'schedule'\")",
    "fieldOperationsDetailUxBatch84.includes(\"panel === 'complete'\")",
    "fieldOperationsDetailUxBatch84.includes(\"panel === 'cancel'\")",
    "fieldOperationsDetailUxBatch84.includes(\"panel === 'part-add'\")",
    "fieldOperationsDetailUxBatch84.includes(\"can('field_operations.view')\")",
    "fieldOperationsDetailUxBatch84.includes('apiAdminFieldOperations.partRemove')",
    "fieldOperationsDetailUxBatch84.includes('openAfterSalesAttachment')",
  ];
  for (const marker of required) assert.ok(block.includes(marker), `validator contract missing: ${marker}`);
  assert.doesNotMatch(block, /workspace === 'planning' && panel === 'schedule'/);
  assert.doesNotMatch(block, /workspace === 'execution' && panel === 'complete'/);
  assert.doesNotMatch(block, /workspace === 'parts' && panel === 'part-add'/);
  assert.ok(block.includes('!/(ProductVariant|product_variant_id|product_variants|variants_enabled)/.test(fieldOperationsDetailUxBatch84)'));
});
