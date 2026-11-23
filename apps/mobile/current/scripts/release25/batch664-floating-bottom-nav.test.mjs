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
  cartEnabled: true,
  commissionsEnabled: true,
  receivablesEnabled: true,
  unread: 4,
};
function items(roleSlug, extra = {}) {
  return buildBottomNavItems({ ...defaultFlags, roleSlug, ...extra });
}
const labels = (role) => Array.from(items(role), (item) => item.label);
const routes = (role) => Array.from(items(role), (item) => item.route);

test('owner decision 2026-11-16: exact tabs per role, raised Home always in the middle', () => {
  assert.deepEqual(labels('superadmin'), ['Katalog', 'Porudžbine', 'Početna', 'Administracija', 'Nalog']);
  assert.deepEqual(routes('superadmin'), ['/catalog', '/admin/orders', '/home', '/admin', '/account']);
  assert.deepEqual(labels('admin'), ['Katalog', 'Porudžbine', 'Početna', 'Potraživanja', 'Nalog']);
  assert.deepEqual(routes('admin'), ['/catalog', '/admin/orders', '/home', '/admin/receivables', '/account']);
  assert.deepEqual(labels('user'), ['Katalog', 'Korpa', 'Početna', 'Porudžbine', 'Provizije']);
  assert.deepEqual(routes('user'), ['/catalog', '/cart', '/home', '/orders', '/commissions']);
  for (const role of ['superadmin', 'admin', 'user', null, 'unknown']) {
    const list = items(role);
    assert.equal(list.length, 5);
    assert.equal(list[2].key, 'home');
    assert.equal(list[2].center, true);
    assert.equal(list[2].route, '/home');
    assert.equal(list.filter((item) => item.center).length, 1);
  }
});

test('roles without an owner decision keep the previous layout', () => {
  for (const role of [null, undefined, 'unknown']) {
    assert.deepEqual(labels(role), ['Katalog', 'Porudžbine', 'Početna', 'Notifikacije', 'Nalog']);
    assert.equal(items(role)[1].route, '/orders');
    assert.equal(items(role)[3].badge, 4);
  }
});

test('admin tabs: Administracija is not a tab for Admin, no notification badge on tabs', () => {
  assert.equal(hasBottomNavAdminRole('admin'), true);
  assert.equal(hasBottomNavAdminRole('superadmin'), true);
  assert.equal(hasBottomNavAdminRole('user'), false);
  for (const role of ['superadmin', 'admin', 'user']) {
    assert.ok(items(role).every((item) => item.badge === undefined), role + ' tabs carry no badge; the header bell shows unread');
  }
  assert.ok(!labels('admin').includes('Administracija'));
  assert.ok(!labels('user').includes('Notifikacije'));
});

test('capability gates do not grant access just because a tab is rendered', () => {
  const admin = items('admin', { canManageOrders: false, receivablesEnabled: false });
  assert.equal(admin[1].enabled, false);
  assert.equal(admin[3].enabled, false);
  assert.equal(admin[3].route, '/admin/receivables');
  const subAgent = items('user', { ordersEnabled: false, cartEnabled: false, commissionsEnabled: false, catalogEnabled: false });
  assert.deepEqual(Array.from(subAgent, (item) => item.enabled), [false, false, true, false, false]);
  const fallback = items(null, { notificationsEnabled: false });
  assert.equal(fallback[3].enabled, false);
  assert.equal(items('admin')[3].enabled, true);
  assert.equal(items('user')[1].enabled, true);
});

test('notification unread badge only exists in the fallback layout and rejects invalid counts', () => {
  assert.equal(items(null, { unread: -3 })[3].badge, undefined);
  assert.equal(items(null, { unread: 0 })[3].badge, undefined);
  assert.equal(items(null, { unread: 123 })[3].badge, 123);
  assert.equal(items('admin', { unread: 10 })[3].badge, undefined);
});

test('active destination follows admin and SubAgent routes', () => {
  for (const route of ['/orders', '/order/42', '/assigned-orders', '/admin/orders', '/admin/orders/42', '/admin/orders/archived']) {
    assert.equal(activeBottomNavKey(route, 'admin'), 'orders');
    assert.equal(activeBottomNavKey(route, 'user'), 'orders');
  }
  for (const route of ['/catalog', '/product/desk', '/cart', '/checkout', '/admin/catalog/42']) {
    assert.equal(activeBottomNavKey(route, 'admin'), 'catalog');
  }
  assert.equal(activeBottomNavKey('/cart', 'user'), 'cart');
  assert.equal(activeBottomNavKey('/checkout', 'user'), 'cart');
  assert.equal(activeBottomNavKey('/catalog', 'user'), 'catalog');
  assert.equal(activeBottomNavKey('/commissions', 'user'), 'commissions');
  assert.equal(activeBottomNavKey('/commissions/7', 'user'), 'commissions');
  assert.equal(activeBottomNavKey('/commissions', null), 'home');
});

test('Admin highlights Potraživanja, SuperAdmin highlights Administracija, bell routes have no tab', () => {
  assert.equal(activeBottomNavKey('/admin/receivables', 'admin'), 'receivables');
  assert.equal(activeBottomNavKey('/admin/receivables/9', 'admin'), 'receivables');
  assert.equal(activeBottomNavKey('/admin/receivables', 'superadmin'), 'secondary');
  assert.equal(activeBottomNavKey('/admin', 'superadmin'), 'secondary');
  assert.equal(activeBottomNavKey('/admin/users', 'superadmin'), 'secondary');
  assert.equal(activeBottomNavKey('/admin', 'admin'), null);
  assert.equal(activeBottomNavKey('/admin/users', 'admin'), null);
  assert.equal(activeBottomNavKey('/admin', 'user'), null);
  assert.equal(activeBottomNavKey('/notifications', 'admin'), null);
  assert.equal(activeBottomNavKey('/notifications', 'superadmin'), null);
  assert.equal(activeBottomNavKey('/notifications', 'user'), null);
  assert.equal(activeBottomNavKey('/notifications', null), 'secondary');
  assert.equal(activeBottomNavKey('/account/preferences', 'user'), 'account');
  assert.equal(activeBottomNavKey('/notification-settings', 'admin'), 'account');
  assert.equal(activeBottomNavKey('/home', 'user'), 'home');
});

test('every policy route is a real screen and every glyph exists', () => {
  const glyphSource = readFileSync(new URL('../../src/components/ui/glyph.tsx', import.meta.url), 'utf8');
  const screens = {
    '/catalog': '(tabs)/catalog.tsx', '/cart': 'cart.tsx', '/orders': '(tabs)/orders.tsx',
    '/admin/orders': 'admin/orders/index.tsx', '/home': '(tabs)/home.tsx', '/commissions': 'commissions/index.tsx',
    '/admin/receivables': 'admin/receivables/index.tsx', '/admin': 'admin/index.tsx',
    '/notifications': '(tabs)/notifications.tsx', '/account': '(tabs)/account.tsx',
  };
  for (const role of ['superadmin', 'admin', 'user', null]) {
    for (const item of items(role)) {
      assert.ok(screens[item.route], 'unknown route ' + item.route);
      readFileSync(new URL('../../src/app/(app)/' + screens[item.route], import.meta.url), 'utf8');
      assert.match(glyphSource, new RegExp('\\b' + item.glyph + ': \\{'), 'missing glyph ' + item.glyph);
    }
  }
});

test('screen renders policy with role-aware routes, accessibility, and no Home caption in bottom bar', () => {
  assert.match(navSource, /buildBottomNavItems\(/);
  assert.match(navSource, /activeBottomNavKey\(/);
  assert.match(navSource, /disabled=\{!item\.enabled\}/);
  assert.match(navSource, /pathname !== item\.route\) router\.replace\(item\.route\)/);
  assert.match(navSource, /accessibilityState=\{\{ selected: focused, disabled: !item\.enabled \}\}/);
  assert.match(navSource, /\{center \? null : \(/);
  assert.match(navSource, /adjustsFontSizeToFit/);
  assert.match(navSource, /backgroundColor: isDark \? DARK_GLASS : LIGHT_GLASS/);
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

test('Batch678 glass navigation is compact, translucent and overlaid above scrolling pages', () => {
  assert.match(navSource, /const LIGHT_GLASS = 'rgba\(225,229,232,0\.78\)'/);
  assert.match(navSource, /const DARK_GLASS = 'rgba\(48,54,60,0\.76\)'/);
  assert.match(navSource, /backgroundColor: isDark \? DARK_GLASS : LIGHT_GLASS/);
  assert.doesNotMatch(navSource, /BAR_COLOR|#292929/);
  assert.match(navSource, /safeFrame: \{[\s\S]*?backgroundColor: 'transparent'/);
  assert.match(navSource, /bar: \{\s*minHeight: 54/);
  assert.match(navSource, /item: \{[\s\S]*?minHeight: 48/);
  assert.match(navSource, /homeItem: \{ transform: \[\{ translateY: -9 \}\]/);
  assert.match(navSource, /homeCircle: \{\s*width: 52,\s*height: 52/);
  assert.match(navSource, /backgroundColor: theme\.primary/);
  assert.match(navSource, /color=\{center\s*\? theme\.onPrimary/);
  assert.match(navSource, /pressed \? styles\.pressed : null/);
  assert.match(navSource, /safeFrame: \{\s*position: 'absolute',\s*bottom: 0/);
  assert.match(navSource, /pointerEvents="box-none"/);
  assert.doesNotMatch(shellSource, /bottomNavOverlay/);
  assert.match(shellSource, /!useNavigationRail \? <AppBottomNav variant="bottom" \/> : null/);
  assert.ok(shellSource.indexOf('<Stack') < shellSource.lastIndexOf('<AppBottomNav variant="bottom"'));
});
