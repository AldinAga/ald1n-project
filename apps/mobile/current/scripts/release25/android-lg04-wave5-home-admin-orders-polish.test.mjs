// LG-04 Wave5: approved Glass actions + clean admin orders + immutable order-item snapshots.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (path) => readFileSync(new URL(path, import.meta.url), 'utf8');
const home = read('../../src/app/(app)/(tabs)/home.tsx');
const admin = read('../../src/app/(app)/admin/index.tsx');
const orders = read('../../src/app/(app)/admin/orders/index.tsx');
const api = read('../../src/features/admin/orders-admin-api.ts');
const backendIndex = read('../../../../cms/current/app/Services/OrderIndexService.php');
const backendController = read('../../../../cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php');
const glass = read('../../src/components/workspaces/glass-workspace-shortcut.tsx');
const eas = JSON.parse(read('../../eas.json'));

test('existing safe 48dp Glass remains optional and readable', () => {
  assert.match(glass, /resolveHeaderGlassPolicy/);
  assert.match(glass, /width: 48, height: 48/);
  assert.match(glass, /policy\.material === 'solid'/);
  assert.match(glass, /accessibilityRole="button"/);
  assert.match(glass, /backgroundColor: theme\.surface/);
});

test('Home uses Glass for all authorized quick links, activities and admin entry, not data tiles', () => {
  assert.match(home, /<HomeActionList actions=\{quickActions\} glassShortcuts \/>/);
  assert.match(home, /<HomeActionList actions=\{myActivities\} glassShortcuts \/>/);
  assert.match(home, /<HomeActionList actions=\{adminActions\} glassShortcuts \/>/);
  assert.match(home, /can\('commissions\.view_own'\)/);
  assert.match(home, /can\('warranties\.view_own'\)/);
  assert.match(home, /adminAllowed \? \{ title: 'Administracija'/);
  assert.match(home, /<DashboardMetric/);
  assert.doesNotMatch(home, /LiquidGlassAndroidView/);
});

test('Admin uses bounded Glass for navigation links, keeps permission checks and valuation opaque', () => {
  assert.match(admin, /<GlassWorkspaceShortcut title="Porudžbine"/);
  assert.match(admin, /<GlassWorkspaceShortcut title="Artikli"/);
  assert.match(admin, /<GlassWorkspaceShortcut title="Direktna prodaja"/);
  assert.match(admin, /<GlassWorkspaceShortcut title="Garancije"/);
  assert.match(admin, /<GlassWorkspaceShortcut title="Upravljanje korisnicima"/);
  assert.match(admin, /can\('orders\.manage'\)/);
  assert.match(admin, /can\('catalog\.manage_products'\)/);
  assert.match(admin, /moduleEnabled\('warranties'\)/);
  assert.match(admin, /<Card style=\{styles\.valuationCard\}>/);
  assert.match(admin, /value=\{adminSearch\}/);
  assert.doesNotMatch(admin, /LiquidGlassAndroidView/);
});

test('Orders workspace offers readable summary and avoids internal capability prose', () => {
  assert.match(orders, /Radni prostor porudžbina/);
  assert.match(orders, /ORDERS_LIST_WORKSPACE_OPTIONS\.map/);
  assert.match(orders, /styles\.summaryGrid/);
  assert.match(orders, /response\.pagination\.total/);
  assert.match(orders, /response\.attention\.unaccepted/);
  assert.match(orders, /response\.attention\.overdue/);
  assert.doesNotMatch(orders, /Detail capability=/);
  assert.doesNotMatch(orders, /bez promene server workflow-a/);
  assert.doesNotMatch(orders, /Server trenutno ne vraća kategorije za pažnju/);
});

test('Admin order list presents product snapshot and remaining distinct order lines', () => {
  assert.match(api, /primary_item_name\?: string \| null/);
  assert.match(api, /additional_items_count\?: number/);
  assert.match(orders, /item\.primary_item_name/);
  assert.match(orders, /item\.additional_items_count/);
  assert.match(orders, /<Pill tone=\{/);
  assert.match(orders, /Porudžbina/);
  assert.match(orders, /Plaćanje/);
  assert.doesNotMatch(orders, /Odgovorno lice nije dodeljeno/);
  assert.match(orders, /accessibilityRole="button"/);
});

test('Backend eagerly loads only existing per-order snapshot lines and never adds N+1', () => {
  assert.match(backendIndex, /'items:id,order_id,product_name'/);
  assert.match(backendController, /'primary_item_name' =>/);
  assert.match(backendController, /'additional_items_count' =>/);
  assert.match(backendController, /relationLoaded\('items'\)/);
  assert.match(backendController, /product_name/);
  assert.doesNotMatch(backendController, /'primary_item_name' =>[^]*?DB::table\('products'\)/);
});

test('Orders keep the bottom floating navigation clear at larger fonts', () => {
  assert.match(orders, /paddingBottom: 220/);
  assert.match(orders, /flexShrink: 1/);
  assert.match(orders, /minWidth: 0/);
});

test('Native and release baseline unchanged; no production build or EAS dispatch', () => {
  assert.equal(eas.build['lg03-play-testing'].channel, 'lg03-play-testing');
  assert.equal(eas.build['lg03-native-uat'].channel, 'lg03-native-uat');
  assert.equal(eas.submit.production.android.track, 'production');
});
