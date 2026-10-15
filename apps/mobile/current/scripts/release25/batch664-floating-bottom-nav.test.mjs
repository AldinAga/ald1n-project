// Batch664: exercise the exact TypeScript navigation policy shipped in the app.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

const policySource = readFileSync(new URL('../../src/components/layout/app-bottom-nav-policy.ts', import.meta.url), 'utf8');
const navSource = readFileSync(new URL('../../src/components/layout/app-bottom-nav.tsx', import.meta.url), 'utf8');
const shellSource = readFileSync(new URL('../../src/app/(app)/_layout.tsx', import.meta.url), 'utf8');
const paletteSource = readFileSync(new URL('../../src/design/ald1n-tokens.generated.ts', import.meta.url), 'utf8');
const transpiled = ts.transpileModule(policySource, {
  compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 },
  reportDiagnostics: true,
});
assert.equal(transpiled.diagnostics?.length ?? 0, 0, 'Navigation policy transpilation must succeed');
const exports = {};
runInNewContext(transpiled.outputText, { exports }, { filename: 'app-bottom-nav-policy.ts' });
const { buildBottomNavItems, activeBottomNavKey, hasBottomNavAdminRole } = exports;

const defaultFlags = {
  catalogEnabled: true,
  ordersEnabled: true,
  notificationsEnabled: true,
  canManageOrders: true,
  unread: 4,
};
function items(roleSlug, extra = {}) {
  return buildBottomNavItems({ ...defaultFlags, roleSlug, ...extra });
}

test('five fixed positions and centered Home, independent of role', () => {
  for (const role of ['superadmin', 'admin', 'user', null]) {
    const list = items(role);
    assert.deepEqual(Array.from(list, (item) => item.key), ['catalog', 'orders', 'home', 'secondary', 'account']);
    assert.equal(list.length, 5);
    assert.equal(list[2].center, true);
    assert.equal(list[2].route, '/home');
    assert.equal(list[4].route, '/account');
  }
});

test('Admin and SuperAdmin open managed orders and admin hub', () => {
  for (const role of ['superadmin', 'admin']) {
    assert.equal(hasBottomNavAdminRole(role), true);
    assert.equal(items(role)[1].route, '/admin/orders');
    assert.equal(items(role)[1].enabled, true);
    assert.equal(items(role)[3].label, 'Administracija');
    assert.equal(items(role)[3].route, '/admin');
    assert.equal(items(role)[3].badge, undefined);
  }
});

test('customer opens only personal orders and notification inbox', () => {
  assert.equal(hasBottomNavAdminRole('user'), false);
  assert.equal(items('user')[1].route, '/orders');
  assert.equal(items('user')[3].label, 'Notifikacije');
  assert.equal(items('user')[3].route, '/notifications');
  assert.equal(items('user')[3].badge, 4);
});

test('capability gates do not grant access just because a tab is rendered', () => {
  const admin = items('admin', { canManageOrders: false });
  assert.equal(admin[1].route, '/admin/orders');
  assert.equal(admin[1].enabled, false);
  const customer = items('user', { ordersEnabled: false, notificationsEnabled: false, catalogEnabled: false });
  assert.equal(customer.length, 5);
  assert.equal(customer[0].enabled, false);
  assert.equal(customer[1].enabled, false);
  assert.equal(customer[3].enabled, false);
  assert.equal(customer[2].enabled, true);
  assert.equal(customer[4].enabled, true);
});

test('notification unread is customer-only and rejects invalid counts', () => {
  assert.equal(items('user', { unread: -3 })[3].badge, undefined);
  assert.equal(items('user', { unread: 0 })[3].badge, undefined);
  assert.equal(items('user', { unread: 123 })[3].badge, 123);
  assert.equal(items('admin', { unread: 10 })[3].badge, undefined);
});

test('active destination follows admin and customer order detail routes', () => {
  for (const route of ['/orders', '/order/42', '/assigned-orders', '/admin/orders', '/admin/orders/42', '/admin/orders/archived']) {
    assert.equal(activeBottomNavKey(route, 'admin'), 'orders');
  }
  for (const route of ['/catalog', '/product/desk', '/cart', '/checkout', '/admin/catalog/42']) {
    assert.equal(activeBottomNavKey(route, 'admin'), 'catalog');
  }
});

test('secondary tab follows role, account settings remain account', () => {
  assert.equal(activeBottomNavKey('/admin', 'superadmin'), 'secondary');
  assert.equal(activeBottomNavKey('/admin/users', 'admin'), 'secondary');
  assert.equal(activeBottomNavKey('/notifications', 'user'), 'secondary');
  assert.equal(activeBottomNavKey('/notifications', 'admin'), null);
  assert.equal(activeBottomNavKey('/admin', 'user'), null);
  assert.equal(activeBottomNavKey('/account/preferences', 'user'), 'account');
  assert.equal(activeBottomNavKey('/notification-settings', 'admin'), 'account');
  assert.equal(activeBottomNavKey('/home', 'user'), 'home');
});

test('screen renders policy with role-aware routes, accessibility, and no Home caption in bottom bar', () => {
  assert.match(navSource, /buildBottomNavItems\(/);
  assert.match(navSource, /activeBottomNavKey\(/);
  assert.match(navSource, /disabled=\{!item\.enabled\}/);
  assert.match(navSource, /accessibilityState=\{\{ selected: focused, disabled: !item\.enabled \}\}/);
  assert.match(navSource, /\{center \? null : \(/);
  assert.match(navSource, /adjustsFontSizeToFit/);
  assert.match(navSource, /backgroundColor: BAR_COLOR/);
  assert.match(navSource, /backgroundColor: theme\.primary/);
});

test('navigation is a fixed shell sibling of Stack; Home inherits Ald1n brand theme', () => {
  const stackIndex = shellSource.indexOf('<Stack');
  const bottomNavIndex = shellSource.lastIndexOf('<AppBottomNav variant="bottom"');
  assert.ok(stackIndex > 0 && bottomNavIndex > stackIndex);
  assert.match(paletteSource, /"primary": "#C45116"/);
  assert.match(paletteSource, /"primary": "#FF8A3D"/);
  assert.doesNotMatch(navSource, /#FF0050|#FF0048|#593B7A/);
});
