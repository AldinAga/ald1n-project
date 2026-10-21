import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = path => readFileSync(new URL(path, import.meta.url), 'utf8');
const shortcut = read('../../src/components/workspaces/glass-workspace-shortcut.tsx');
const home = read('../../src/app/(app)/(tabs)/home.tsx');
const admin = read('../../src/app/(app)/admin/index.tsx');
const orders = read('../../src/app/(app)/admin/orders/index.tsx');
const api = read('../../src/features/admin/orders-admin-api.ts');
const php = read('../../../../cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php');
const service = read('../../../../cms/current/app/Services/OrderIndexService.php');

test('glass remains ONLY icon bounded and theme-opaque on primary and soft action rows', () => {
  assert.match(shortcut, /variant\?: 'primary' \| 'soft'/);
  assert.match(shortcut, /variant = 'primary'/);
  assert.match(shortcut, /variant === 'soft' \? styles\.softRow/);
  assert.match(shortcut, /<View collapsable=\{false\} style=\{styles\.iconHost\}>/);
  assert.match(shortcut, /width: 48/);
  assert.match(shortcut, /height: 48/);
  assert.match(shortcut, /policy\.material === 'solid'/);
  assert.match(shortcut, /backgroundColor: theme\.surface/);
  assert.match(shortcut, /backgroundColor: theme\.surfaceContainerHigh/);
  assert.equal((shortcut.match(/<Pressable\b/g) || []).length, 1);
});

test('Home activity and admin shortcuts use soft lens with prior role/capability checks', () => {
  assert.match(home, /<HomeActionList actions=\{quickActions\} glassShortcuts \/>/);
  assert.match(home, /<HomeActionList actions=\{myActivities\} glassShortcuts variant="soft" \/>/);
  assert.match(home, /<HomeActionList actions=\{adminActions\} glassShortcuts variant="soft" \/>/);
  assert.match(home, /can\('catalog\.manage_products'\)/);
  assert.match(home, /hasFeature\('order_create'\)/);
  assert.match(home, /<GlassWorkspaceShortcut/);
  assert.match(home, /function DashboardMetric/);
  assert.match(home, /inventoryValuation\.purchase_value_rsd/);
  assert.doesNotMatch(home, /<LiquidGlassAndroidView/);
});

test('Admin keeps exact authorization and shows bounded glass only on navigation actions', () => {
  assert.match(admin, /if \(!allowed\) \{/);
  assert.match(admin, /can\('orders\.manage'\)/);
  assert.match(admin, /can\('catalog\.manage_products'\)/);
  assert.match(admin, /<GlassWorkspaceShortcut title="Porudžbine"/);
  assert.match(admin, /<GlassWorkspaceShortcut title="Artikli"/);
  assert.match(admin, /<GlassWorkspaceShortcut title="Upravljanje korisnicima"/);
  assert.match(admin, /variant="soft"/);
  assert.doesNotMatch(admin, /<OperatorRow/);
  assert.match(admin, /value=\{adminSearch\}/);
  assert.match(admin, /inventoryValuation\.purchase_value_rsd/);
  assert.match(admin, /inventoryValuation\.sale_value_rsd/);
  assert.doesNotMatch(admin, /LiquidGlassAndroidView/);
});

test('Admin order overview is organized, removes technical prose and shows server-attested counts', () => {
  assert.match(orders, /title="Porudžbine"/);
  assert.doesNotMatch(orders, /Odvoji pregled, listu, filtere, pažnju i arhivu bez promene server workflow/);
  assert.doesNotMatch(orders, /Detail capability=/);
  assert.doesNotMatch(orders, /workflow_mutations=\{/);
  assert.match(orders, /response\.pagination\.total/);
  assert.match(orders, /response\.attention\.unaccepted/);
  assert.match(orders, /response\.attention\.overdue/);
  assert.match(orders, /<FilterChip/);
  assert.match(orders, /paddingBottom: 200/);
});

test('Order list has snapshot product title and +N summary, readable status and payment', () => {
  assert.match(api, /item_summary\?: \{ name: string \| null; additional_count: number; count: number \}/);
  assert.match(orders, /item\.item_summary\?\.name/);
  assert.match(orders, /item\.item_summary\?\.additional_count/);
  assert.match(orders, /<Pill tone=/);
  assert.match(orders, /Artikal/);
  assert.match(orders, /Plaćanje/);
  assert.match(orders, /formatMoney\(item\.subtotal_rsd\)/);
  assert.match(orders, /canOpen/);
  assert.match(php, /'item_summary'/);
  assert.match(php, /product_name/);
  assert.match(service, /'items'/);
  assert.match(service, /product_name/);
  assert.doesNotMatch(orders, /Odgovorno lice nije dodeljeno/);
});

test('no new native packages or EAS/OTA/Play integration is introduced', () => {
  const manifest = JSON.parse(read('../../package.json'));
  assert.equal(manifest.dependencies['react-native-android-liquid-glass'], '0.2.1');
  assert.equal(manifest.version, '1.0.0');
  const eas = JSON.parse(read('../../eas.json'));
  assert.equal(eas.build['lg03-play-testing'].channel, 'lg03-play-testing');
});
