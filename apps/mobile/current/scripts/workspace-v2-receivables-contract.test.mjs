// ALD1N_MOBILE_WORKSPACE_V2_W2_RECEIVABLES_FINANCE_SAFE_CONTRACT
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const read = (name) => fs.readFileSync(path.join(root, name), 'utf8');
const screen = () => read('src/app/(app)/admin/receivables/[id].tsx');
const validator = () => read('scripts/validate-project.mjs');

test('all seven old receivables workspaces use a responsive controlled icon grid', () => {
  const s = screen();
  assert.match(s, /import \{ WorkspaceGrid \} from '@\/components\/workspaces\/workspace-grid'/);
  assert.match(s, /<WorkspaceGrid/);
  assert.match(s, /selected=\{workspace\}/);
  assert.match(s, /onSelect=\{selectWorkspace\}/);
  assert.doesNotMatch(s, /<FilterBar|<FilterChip\s+key=\{option\.value\}/);
  for (const id of ['overview', 'case', 'plan', 'payments', 'communication', 'reminders', 'audit']) {
    assert.ok(s.includes("value: '" + id + "', glyph:"), 'missing icon option: ' + id);
  }
});

test('receivables hero balance appears first, compact actions second, and workspace third', () => {
  const s = screen();
  const balance = s.indexOf('<Card style={styles.heroCard}>');
  const hub = s.indexOf('<QuickActionHub actions={quickActions}');
  const grid = s.indexOf('<WorkspaceGrid');
  assert.ok(balance >= 0 && balance < hub && hub < grid, 'financial snapshot/operations/grid order changed');
  assert.equal(s.split('<QuickActionHub').length - 1, 1);
  assert.match(s, /maxVisible=\{4\}/);
});

test('capability-driven quick actions open existing forms, not direct financial mutations', () => {
  const s = screen();
  assert.match(s, /const quickActions: QuickAction\[\] = \[\]/);
  assert.match(s, /if \(response\.capabilities\.can_record_payment && data\.remaining_rsd > 0\)/);
  for (const capability of ['can_update', 'can_replace_plan', 'can_add_contact', 'can_send_reminder']) {
    assert.ok(s.includes('if (response.capabilities.' + capability + ')'), capability + ' not guarded');
  }
  for (const open of ['openPayment', 'openUpdate', 'openPlan', 'openContact', 'openReminder']) {
    assert.ok(s.includes('onPress: ' + open), 'no guarded open handler: ' + open);
  }
  const hub = s.match(/const quickActions: QuickAction\[\] = \[\];([\s\S]*?)\n  return \(/);
  assert.ok(hub, 'quick action declarations missing');
  assert.doesNotMatch(hub[1], /apiAdminReceivables\.|submitPayment\(|submitReminder\(|mutateAsync\(/);
});

test('workspace changes preserve payment idempotency, installment and contact drafts', () => {
  const s = screen();
  const select = s.match(/const selectWorkspace = \(next: ReceivablesWorkspace\) => \{([\s\S]*?)\n  \};/);
  assert.ok(select, 'typed selectWorkspace handler is missing');
  assert.match(select[1], /setWorkspace\(next\)/);
  assert.doesNotMatch(select[1], /setPanel\(null\)|setPlanRows\(|setPaymentIdempotencyKey\(|setPaymentAmount\(|setContactNote\(/);
  for (const panel of ['update', 'plan', 'payment', 'contact', 'reminder']) {
    assert.match(s, new RegExp("\\{panel === '" + panel + "' \\? \\("));
  }
  assert.doesNotMatch(s, /workspace === '(case|plan|payments|communication|reminders)' && panel ===/);
  for (const field of ['planRows', 'paymentIdempotencyKey', 'paymentAmount', 'paymentPaidAt', 'contactVisible', 'reminderMessage']) {
    assert.match(s, new RegExp('const \\[\\s*' + field + ','));
  }
});

test('idempotent payment and installment protections remain server-backed', () => {
  const s = screen();
  assert.match(s, /Crypto\.randomUUID\(\)/);
  assert.match(s, /if \(amount > data\.remaining_rsd \+ 0\.004\)/);
  assert.match(s, /idempotency_key: paymentIdempotencyKey/);
  assert.match(s, /apiAdminReceivables\.recordPayment\(/);
  assert.match(s, /response\.options\.max_installments/);
  assert.match(s, /positiveAmount\(row\.amount_rsd/);
  assert.match(s, /apiAdminReceivables\.replacePlan\(/);
  assert.match(s, /data\.installments\.some\(\(item\) => item\.paid_amount_rsd > 0\)/);
});

test('outbox reminder is opened separately and never creates a synthetic payment', () => {
  const s = screen();
  assert.match(s, /onPress: openReminder/);
  assert.match(s, /const openReminder = \(\) => \{/);
  assert.match(s, /apiAdminReceivables\.sendReminder\(/);
  assert.match(s, /e-mail outbox/);
  assert.match(s, /ne kreira sintetičku uplatu/i);
  assert.match(s, /panel === 'reminder'/);
});

test('contact visibility is still opt-in and the original FilterChip is retained for this form', () => {
  const s = screen();
  assert.match(s, /import \{ FilterChip \} from '@\/components\/ui\/filter-bar'/);
  assert.match(s, /<FilterChip label="Vidljivo kupcu" active=\{contactVisible\}/);
  assert.match(s, /visible_to_customer: contactVisible/);
  assert.match(s, /setContactVisible\(false\)/);
});

test('legacy permission, record/query keys, all five domain endpoints and variant removal remain intact', () => {
  const s = screen();
  assert.match(s, /can\('receivables\.manage'\)/);
  assert.match(s, /if \(!allowed\) return <UnavailableState/);
  assert.match(s, /adminQueryKeys\.receivable\(receivableId\)/);
  assert.match(s, /adminQueryKeys\.receivables\(\)/);
  for (const action of ['detail', 'update', 'replacePlan', 'recordPayment', 'addContact', 'sendReminder']) {
    assert.match(s, new RegExp('apiAdminReceivables\\.' + action + '\\('));
  }
  assert.doesNotMatch(s, /ProductVariant|product_variant_id|product_variants|variants_enabled/);
});

test('shared W1 controls remain accessible, touch-friendly and free of business writes', () => {
  const grid = read('src/components/workspaces/workspace-grid.tsx');
  const hub = read('src/components/workspaces/quick-action-hub.tsx');
  assert.match(grid, /useWindowDimensions/);
  assert.match(grid, /accessibilityRole="button"/);
  assert.match(grid, /accessibilityState/);
  assert.match(hub, /setExpanded/);
  assert.match(hub, /accessibilityRole="button"/);
  assert.doesNotMatch(grid + hub, /apiAdminReceivables|useMutation|fetch\(/);
});

test('Batch88 validator is strengthened for W2 without weakening original finance/domain checks', () => {
  const v = validator();
  const begin = v.indexOf('// MOBILE_V1_0_ADMIN_RECEIVABLES_DETAIL_UX_REORGANIZATION_BATCH88_VALIDATOR');
  const end = v.indexOf('// MOBILE_V1_0_RECEIVABLES_PAYMENT_OPENAPI_CONTRACT_REPAIR_BATCH176_VALIDATOR', begin);
  assert.ok(begin >= 0 && end > begin);
  const block = v.slice(begin, end);
  for (const marker of [
    "receivablesDetailUxBatch88.includes('<WorkspaceGrid')",
    "receivablesDetailUxBatch88.includes('<QuickActionHub actions={quickActions}')",
    "receivablesDetailUxBatch88.includes('selected={workspace}')",
    "receivablesDetailUxBatch88.includes('onSelect={selectWorkspace}')",
    "receivablesDetailUxBatch88.includes(\"panel === 'payment'\")",
    "receivablesDetailUxBatch88.includes('idempotency_key: paymentIdempotencyKey')",
    "receivablesDetailUxBatch88.includes('visible_to_customer: contactVisible')",
    "receivablesDetailUxBatch88.includes('apiAdminReceivables.replacePlan')",
    "receivablesDetailUxBatch88.includes('apiAdminReceivables.sendReminder')",
    "receivablesDetailUxBatch88.includes(\"can('receivables.manage')\")",
  ]) assert.ok(block.includes(marker), 'missing guard: ' + marker);
  assert.ok(block.includes('!/(ProductVariant|product_variant_id|product_variants|variants_enabled)/.test(receivablesDetailUxBatch88)'));
});
