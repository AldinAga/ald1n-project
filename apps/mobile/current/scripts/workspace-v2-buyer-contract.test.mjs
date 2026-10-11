// ALD1N_MOBILE_WORKSPACE_V2_BUYER_FIRST_CONTRACT
import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '..');
const read = (rel) => fs.readFileSync(path.join(root, rel), 'utf8');
const screen = () => read('src/app/(app)/admin/orders/[id].tsx');

test('admin order places real shipping recipient before next-step and workspace navigation', () => {
  const s = screen();
  const buyer = s.indexOf('<OrderBuyerCard');
  const next = s.indexOf('<Card style={styles.nextStepCard}>');
  const workspace = s.indexOf('<WorkspaceGrid');
  assert.ok(buyer >= 0, 'first contact card is missing');
  assert.ok(next > buyer, 'next-step panel must follow buyer');
  assert.ok(workspace > next, 'workspace navigation must follow contact and next step');
  assert.match(s, /const recipientName\s*=\s*text\(order, 'shipping_full_name'/);
  assert.match(s, /name=\{recipientName\}/);
  assert.match(s, /phone=\{text\(order, 'shipping_phone'/);
  assert.match(s, /address=\{text\(order, 'shipping_address'/);
  assert.match(s, /note=\{text\(order, 'customer_note'/);
});

test('buyer card displays missing values honestly and offers safe call or clipboard', () => {
  const s = read('src/features/admin/order-buyer-card.tsx');
  assert.match(s, /Krajnji kupac/);
  assert.match(s, /Ime i prezime primaoca/);
  assert.match(s, /Telefon/);
  assert.match(s, /Adresa za isporuku/);
  assert.match(s, /Napomena kupca/);
  assert.match(s, /Clipboard\.setStringAsync/);
  assert.match(s, /Linking\.openURL/);
  assert.match(s, /\^\\\+\?/); // positive phone format guard, no arbitrary URL
  assert.doesNotMatch(s, /customer\.name|supplier\.name/);
});

test('six controlled workspace options use accessible adaptive tile grid', () => {
  const s = screen();
  const grid = read('src/components/workspaces/workspace-grid.tsx');
  for (const value of ['overview', 'customer', 'fulfillment', 'finance', 'documents', 'activity']) {
    assert.match(s, new RegExp("value: '" + value + "'"));
  }
  assert.match(s, /<WorkspaceGrid/);
  assert.doesNotMatch(s, /<FilterBar>|<FilterChip/);
  assert.match(grid, /accessibilityRole="button"/);
  assert.match(grid, /accessibilityState/);
  assert.match(grid, /useWindowDimensions/);
  assert.match(grid, /option\.disabled/);
});

test('existing shipment success and server-driven action access remain intact', () => {
  const s = screen();
  assert.match(s, /onShipmentSuccess=\{\(\) => setWorkspace\('fulfillment'\)\}/);
  assert.match(s, /response\.capabilities/);
  assert.match(s, /<AdminOrderActions/);
  assert.match(s, /<AdminOrderArchiveActions/);
});
