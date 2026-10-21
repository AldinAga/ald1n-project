import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = path => readFileSync(new URL(path, import.meta.url), 'utf8');
const orders = read('../../src/app/(app)/admin/orders/index.tsx');
const api = read('../../src/features/admin/orders-admin-api.ts');
const php = read('../../../../cms/current/app/Http/Controllers/Api/V1/Admin/OrderController.php');
const service = read('../../../../cms/current/app/Services/OrderIndexService.php');

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

test('safe on the old production API and keeps Android keyboard fix out of scope', () => {
  assert.match(orders, /item\.item_summary\?\.name \?\? 'Naziv artikla nije dostupan'/);
  assert.match(orders, /response\.capabilities\.detail/);
  assert.doesNotMatch(orders, /LiquidGlassAndroidView/);
  const manifest = JSON.parse(read('../../package.json'));
  assert.equal(manifest.version, '1.0.0');
  const editor = read('../../src/app/(app)/admin/catalog/create.tsx');
  assert.match(editor, /keyboardAware/);
});
