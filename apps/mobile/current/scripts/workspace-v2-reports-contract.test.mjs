// ALD1N_MOBILE_WORKSPACE_V2_W3_REPORTS_NAVIGATION_CONTRACT
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
const root = path.resolve(import.meta.dirname, '..');
const read = (p) => fs.readFileSync(path.join(root, p), 'utf8');
const screen = () => read('src/app/(app)/admin/reports/index.tsx');
const validator = () => read('scripts/validate-project.mjs');

test('all seven report workspace identifiers use responsive accessible icon tiles, not horizontal nav chips', () => {
  const s = screen();
  assert.match(s, /ALD1N_WORKSPACE_V2_W3_REPORTS_GRID_BATCH630/);
  assert.match(s, /import \{ WorkspaceGrid \} from '@\/components\/workspaces\/workspace-grid'/);
  assert.match(s, /<WorkspaceGrid/);
  assert.match(s, /selected=\{workspace\}/);
  assert.match(s, /onSelect=\{selectWorkspace\}/);
  for (const id of ['overview', 'profitability', 'inventory', 'receivables', 'after_sales', 'operations', 'schedules']) {
    assert.ok(s.includes("value: '" + id + "',\n    glyph:"), 'missing glyph for '+id);
  }
  assert.doesNotMatch(s, /<FilterChip\s+key=\{option\.value\}/);
});

test('reports period appears ahead of workspace grid while applied filter disclosure stays after', () => {
  const s=screen();
  const a=s.indexOf('<Card style={styles.periodCard}>');
  const b=s.indexOf('<WorkspaceGrid');
  const c=s.indexOf('Kontekst izve');
  assert.ok(a>=0&&a<b&&b<c,'period/grid/filters priority changed');
  assert.equal(s.split('<WorkspaceGrid').length-1,1);
});

test('workspace option visibility still follows user and server capabilities', () => {
  const s=screen();
  for (const x of ["can('reports.view')", "can('reports.manage')", "if (option.value === 'operations') return canUseOperational", "if (option.value === 'schedules') return canUseSchedules", 'canManageSchedules && response.capabilities.manage_schedules', 'REPORT_WORKSPACE_OPTIONS.filter((option) => {']) assert.ok(s.includes(x), x);
  assert.match(s,/options=\{workspaceOptions\.map\(\(option\) =>/);
});

test('original finance filters, date validation and applied report query remain unchanged', () => {
  const s=screen();
  for (const x of ["adminQueryKeys.managementReport(applied)", 'apiAdminReports.management(applied)', 'const applyFilters = () => {', 'if (draftFrom && draftTo && draftTo < draftFrom)', 'setApplied(next)', 'setFiltersExpanded(false)', '<FilterBar activeCount={activeCount} onClear={clearFilters}>', "onPress={() => setScope('completed')}", 'onPress={applyFilters}']) assert.ok(s.includes(x), x);
});

test('secure report exports and schedule capabilities stay server-authoritative', () => {
  const s=screen();
  for (const x of ['openAdminReportExport(format, applied)', 'openAdminOperationalReportExport', 'apiAdminReports.createSchedule', 'apiAdminReports.updateSchedule', 'apiAdminReports.toggleSchedule', 'apiAdminReports.runSchedule', 'apiAdminReports.deleteSchedule', 'apiAdminReports.retryDelivery', 'response.capabilities.export']) assert.ok(s.includes(x),x);
});

test('workspace tap is navigation-only and reports old visible typo is corrected', () => {
  const s=screen();
  const m=s.match(/const selectWorkspace = \(value: ReportWorkspace\) => \{([\s\S]*?)\n  \};/);
  assert.ok(m,'typed selectWorkspace callback missing');
  assert.match(m[1],/setWorkspace\(value\)/);
  assert.doesNotMatch(m[1],/setApplied|runExport|mutate|apiAdminReports/);
  assert.ok(s.includes('postojeći serverski export tok.'));
  assert.ok(!s.includes('postoje0107i'));
});

test('WorkspaceGrid is an accessible adaptive presentation-only control', () => {
  const g=read('src/components/workspaces/workspace-grid.tsx');
  assert.match(g,/accessibilityRole="button"/);
  assert.match(g,/accessibilityLabel/);
  assert.match(g,/fontScale > 1\.25/);
  assert.match(g,/onPress=\{\(\) => onSelect\(option\.id\)\}/);
  assert.doesNotMatch(g,/apiRequest|fetch\(|axios|mutateAsync/);
});

test('W3 validator adds exactly scoped navigation assertions but keeps earlier report domain checks', () => {
  const s=validator();
  assert.match(s,/ALD1N_WORKSPACE_V2_W3_REPORTS_BATCH630_VALIDATOR/);
  assert.match(s,/P3 Admin Reports 2G zaključava management dashboard/);
  assert.match(s,/P3 Admin Reports 2G zaključava secure CSV\/PDF/);
  assert.match(s,/can\('reports.view'\)/);
});

test('new W3 has no Product Variants or additional business data transport', () => {
  const s=screen();
  assert.doesNotMatch(s,/ProductVariant|product_variant_id|product_variants|variants_enabled/);
  assert.match(s,/useQuery\(\{/);
  assert.match(s,/REPORT_WORKSPACE_OPTIONS/);
});
