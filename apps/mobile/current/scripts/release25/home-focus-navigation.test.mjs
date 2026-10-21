import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
const source = readFileSync(new URL('../../src/app/(app)/(tabs)/home.tsx', import.meta.url), 'utf8');
test('Fokus danas tiles are accessible actionable surfaces with explicit destinations', () => {
  assert.match(source, /<Pressable\s+key=\{signal\.label\}\s+accessibilityRole="button"/);
  assert.match(source, /onPress=\{\(\) => router\.push\(signal\.route\)\}/);
  for (const route of ["'/notifications'", "'/cart'", "'/admin/receivables'"]) assert.ok(source.includes('route: ' + route), route);
  assert.match(source, /route: ordersRoute/);
});
test('Tiles honor availability, role permissions and do not expose unrestricted admin receivables', () => {
  assert.match(source, /notificationsAllowed=\{hasFeature\('notifications'\)\}/);
  assert.match(source, /cartAllowed=\{hasFeature\('order_create'\)\}/);
  assert.match(source, /can\('receivables\.manage'\)/);
  assert.match(source, /can\('orders\.manage'\)/);
  assert.match(source, /if \(completedOrders !== null && ordersRoute\)/);
  assert.match(source, /if \(receivablesOpen !== null\)/);
  assert.doesNotMatch(source, /label: 'Otvorena postprodaja'/);
});
