// ALD1N_WORKSPACE_V2_COMPACT_ACTIONS_CONTRACT
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');

test('order detail mounts one action surface before workspace and timeline', () => {
  const s = read('src/app/(app)/admin/orders/[id].tsx');
  const actions = s.indexOf('<AdminOrderActions');
  const workspace = s.indexOf('<WorkspaceGrid');
  const history = s.indexOf("workspace === 'activity'");
  assert.ok(actions >= 0, 'admin actions component must render');
  assert.ok(actions < workspace, 'admin actions must be above workspace navigation');
  assert.ok(actions < history, 'admin actions cannot be hidden in the history workspace');
  assert.equal(s.split('<AdminOrderActions').length - 1, 1, 'only one mounted action source');
  assert.match(s, /onShipmentSuccess=\{\(\) => setWorkspace\('fulfillment'\)\}/);
  assert.match(s, /<AdminOrderArchiveActions/);
});

test('QuickActionHub is a controlled disclosure, not a mutation handler', () => {
  const s = read('src/components/workspaces/quick-action-hub.tsx');
  assert.match(s, /export function QuickActionHub/);
  assert.match(s, /maxVisible/);
  assert.match(s, /accessibilityRole="button"/);
  assert.match(s, /onPress=\{action\.onPress\}/);
  assert.match(s, /setExpanded/);
  assert.doesNotMatch(s, /apiAdminOrders|fetch\(|useMutation/);
});

test('a next-step request cannot reopen a submitted or dismissed panel on refetch', () => {
  const s = read('src/features/admin/orders-admin-actions.tsx');
  assert.match(s, /lastRequestedSequence\.current === requestedAction\.sequence/);
  assert.match(s, /lastRequestedSequence\.current = requestedAction\.sequence/);
});

test('AdminOrderActions reuses existing panels with capability-driven compact links', () => {
  const s = read('src/features/admin/orders-admin-actions.tsx');
  assert.match(s, /<QuickActionHub/);
  assert.match(s, /canAccept/);
  assert.match(s, /capabilities\.internal_notes/);
  assert.match(s, /capabilities\.payments/);
  assert.match(s, /canComplete/);
  assert.match(s, /canShipment/);
  assert.match(s, /deliveryActionStack/);
  assert.match(s, /panel === 'payment-entry'/);
  assert.match(s, /panel === 'shipment'/);
  assert.match(s, /panel === 'complete'/);
  assert.match(s, /panel === 'reopen'/);
});
