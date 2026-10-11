// ALD1N_MOBILE_WORKSPACE_V2_W2_WARRANTIES_SAFETY_CONTRACT
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const read = (name) => fs.readFileSync(path.join(root, name), 'utf8');
const screen = () => read('src/app/(app)/admin/warranties/[id].tsx');

test('Warranty preserves five typed sections and replaces navigation chips with accessible icon grid', () => {
  const s = screen();
  assert.match(s, /type WarrantyWorkspace = 'overview' \| 'details' \| 'maintenance' \| 'document' \| 'void'/);
  assert.match(s, /<WorkspaceGrid/);
  assert.match(s, /selected=\{workspace\}/);
  assert.match(s, /onSelect=\{selectWorkspace\}/);
  assert.doesNotMatch(s, /<FilterBar|<FilterChip/);
  for (const id of ['overview', 'details', 'maintenance', 'document', 'void']) {
    assert.ok(s.includes("value: '" + id + "', glyph:"), 'missing workspace icon ' + id);
  }
});

test('buyer/product summary comes before contextual action hub and warranty workspace grid', () => {
  const s = screen();
  const buyer = s.indexOf('label="Telefon"');
  const hub = s.indexOf('<QuickActionHub actions={quickActions}');
  const grid = s.indexOf('<WorkspaceGrid');
  assert.ok(buyer >= 0 && hub > buyer && grid > hub, 'summary/action/navigation order changed');
  assert.equal(s.split('<QuickActionHub').length - 1, 1);
  assert.match(s, /maxVisible=\{4\}/);
});

test('quick actions use existing eligibility and only navigate or download PDF', () => {
  const s = screen();
  assert.match(s, /const quickActions: QuickAction\[\] = \[\]/);
  assert.match(s, /if \(warranty\.capabilities\.can_update\)/);
  assert.match(s, /warranty\.maintenance_records\.some\(\(record\) => record\.can_schedule \|\| record\.can_complete\)/);
  assert.match(s, /if \(warranty\.capabilities\.can_void\)/);
  assert.match(s, /kind: 'danger'/);
  assert.match(s, /id: 'void'/);
  const actions = s.match(/const quickActions: QuickAction\[\] = \[\];([\s\S]*?)\n  return \(/);
  assert.ok(actions);
  assert.doesNotMatch(actions[1], /apiAdminWarranties\.|voidMutation\.mutate|maintenanceMutation\.mutate|updateMutation\.mutate/);
  assert.match(actions[1], /onPress: \(\) => setWorkspace\('void'\)/);
});

test('switching warranty workspaces leaves unsaved detail and void drafts mounted but hidden', () => {
  const s = screen();
  const selection = s.match(/const selectWorkspace = \(next: WarrantyWorkspace\) => \{([\s\S]*?)\n  \};/);
  assert.ok(selection);
  assert.match(selection[1], /setWorkspace\(next\)/);
  assert.doesNotMatch(selection[1], /setStartsAt|setExpiresAt|setSerialNumbers|setTerms|setVoidReason|setConfirmVoid|setMaintenance/);
  assert.match(s, /\{warranty\.capabilities\.can_update \? \(/);
  assert.match(s, /workspace !== 'details' && styles\.hidden/);
  assert.match(s, /\{warranty\.capabilities\.can_void \? \(/);
  assert.match(s, /workspace !== 'void' && styles\.hidden/);
  for (const draft of ['startsAt', 'expiresAt', 'serialNumbers', 'terms', 'voidReason']) {
    assert.ok(s.includes('[' + draft + ', set'), 'draft state missing '+draft);
  }
});

test('maintenance edit stays mounted while visiting other sections and discards only deliberately', () => {
  const s = screen();
  assert.match(s, /\{maintenanceRecordId !== null && maintenanceMode \? \(/);
  assert.match(s, /workspace !== 'maintenance' && styles\.hidden/);
  assert.match(s, /const closeMaintenanceForm = \(\) => \{/);
  assert.match(s, /onPress=\{closeMaintenanceForm\}/);
  assert.match(s, /onSuccess: async \(updated\) => \{\s*closeMaintenanceForm\(\)/);
  assert.match(s, /setMaintenanceDateTime/);
  assert.match(s, /setMaintenanceResult/);
  assert.match(s, /setMaintenanceNotes/);
});

test('destructive void requires permission, five-character reason and explicit ConfirmAction', () => {
  const s = screen();
  assert.match(s, /const allowed = can\('warranties\.manage'\)/);
  assert.match(s, /warranty\.capabilities\.can_void/);
  assert.match(s, /voidReason\.trim\(\)\.length < 5/);
  assert.match(s, /onPress=\{\(\) => setConfirmVoid\(true\)\}/);
  assert.match(s, /<ConfirmAction/);
  assert.match(s, /destructive/);
  assert.match(s, /if \(reason\.length >= 5\)/);
  assert.match(s, /voidMutation\.mutate\(reason\)/);
  assert.match(s, /apiAdminWarranties\.void\(/);
});

test('authenticated private warranty PDF and API/query workflows remain unaltered', () => {
  const s = screen();
  assert.match(s, /openAdminWarrantyPdf\(/);
  assert.match(s, /apiAdminWarranties\.detail\(/);
  assert.match(s, /apiAdminWarranties\.update\(/);
  assert.match(s, /apiAdminWarranties\.scheduleMaintenance\(/);
  assert.match(s, /apiAdminWarranties\.completeMaintenance\(/);
  assert.match(s, /adminQueryKeys\.warranty\(warrantyId\)/);
  assert.match(s, /adminQueryKeys\.warranties\(\)/);
  assert.doesNotMatch(s, /ProductVariant|product_variant_id|product_variants|variants_enabled/);
});

test('historical Batch85 validator is additive for W2 and preserves every domain assertion', () => {
  const v = read('scripts/validate-project.mjs');
  const start = v.indexOf('// MOBILE_V1_0_ADMIN_WARRANTY_DETAIL_UX_REORGANIZATION_BATCH85_VALIDATOR');
  const end = v.indexOf('// MOBILE_V1_0_ADMIN_FIELD_OPERATIONS_DETAIL_UX_REORGANIZATION_BATCH84_VALIDATOR', start);
  assert.ok(start >= 0 && end > start);
  const block = v.slice(start, end);
  for (const marker of [
    "warrantyDetailUxBatch85.includes('<WorkspaceGrid')",
    "warrantyDetailUxBatch85.includes('<QuickActionHub actions={quickActions}')",
    "warrantyDetailUxBatch85.includes('onSelect={selectWorkspace}')",
    `warrantyDetailUxBatch85.includes("workspace !== 'details' && styles.hidden")`,
    `warrantyDetailUxBatch85.includes("workspace !== 'maintenance' && styles.hidden")`,
    `warrantyDetailUxBatch85.includes("workspace !== 'void' && styles.hidden")`,
    "warrantyDetailUxBatch85.includes('apiAdminWarranties.update')",
    "warrantyDetailUxBatch85.includes('apiAdminWarranties.void')",
    "warrantyDetailUxBatch85.includes('apiAdminWarranties.scheduleMaintenance')",
    "warrantyDetailUxBatch85.includes('apiAdminWarranties.completeMaintenance')",
    "warrantyDetailUxBatch85.includes('openAdminWarrantyPdf')",
    "warrantyDetailUxBatch85.includes('ConfirmAction')",
  ]) assert.ok(block.includes(marker), 'lost validator guarantee: '+marker);
  assert.ok(block.includes('!/(ProductVariant|product_variant_id|product_variants|variants_enabled)/.test(warrantyDetailUxBatch85)'));
});

test('shared WorkspaceGrid and QuickActionHub keep accessibility and presentation boundaries', () => {
  const grid = read('src/components/workspaces/workspace-grid.tsx');
  const hub = read('src/components/workspaces/quick-action-hub.tsx');
  assert.match(grid, /useWindowDimensions/);
  assert.match(grid, /accessibilityState=\{\{ selected:/);
  assert.match(grid, /accessibilityRole="button"/);
  assert.match(hub, /accessibilityRole="button"/);
  assert.doesNotMatch(grid + hub, /apiAdminWarranties|useMutation|fetch\(/);
});

test('workspace selection alone is free of API writes and has no direct PDF or void side effects', () => {
  const s = screen();
  const selection = s.match(/const selectWorkspace = \(next: WarrantyWorkspace\) => \{([\s\S]*?)\n  \};/);
  assert.ok(selection);
  assert.match(selection[1], /setWorkspace\(next\)/);
  assert.doesNotMatch(selection[1], /voidMutation|updateMutation|maintenanceMutation|apiAdmin|openPdf|openAdminWarrantyPdf/);
});
