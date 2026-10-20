import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = path => readFileSync(new URL(path, import.meta.url), 'utf8');
const create = read('../../src/app/(app)/admin/catalog/create.tsx');
const editor = read('../../src/app/(app)/admin/catalog/[id].tsx');
const home = read('../../src/app/(app)/(tabs)/home.tsx');
const adminOrders = read('../../src/app/(app)/admin/orders/index.tsx');
const api = read('../../src/features/admin/orders-admin-api.ts');
const chargerService = read('../../../../cms/current/app/Services/LaptopChargerCatalogProvisioner.php');
const chargerCommand = read('../../../../cms/current/app/Console/Commands/CatalogLaptopChargersCommand.php');
const schemas = [
  read('../../../../cms/current/docs/openapi.yaml'),
  read('../../docs/openapi.yaml'),
  read('../../../../../packages/api-contract/openapi.yaml'),
];

test('charger catalog uses existing brand-scoped line picker as Tip priključka in both creator and editor', () => {
  for (const source of [create, editor]) {
    assert.match(source, /isLaptopCharger/);
    assert.match(source, /Punjači za laptopove/);
    assert.match(source, /label=\{isLaptopCharger \? 'Tip priključka' : 'Linija'\}/);
    assert.match(source, /'Bez tipa priključka'/);
    assert.match(source, /lineOptions/);
    assert.match(source, /selectedBrandId/);
    assert.match(source, /productTypeId/);
  }
});

test('catalog provisioning is explicit opt-in, existing laptop brands and W only, no variants', () => {
  assert.match(chargerService, /'snaga-napajanja'/);
  assert.match(chargerService, /brand_product_type/);
  assert.match(chargerService, /product_line_product_type/);
  assert.match(chargerService, /user_group_categories/);
  assert.match(chargerService, /if \(!\$apply\) return \$counts/);
  assert.match(chargerCommand, /--dry-run/);
  assert.match(chargerCommand, /--apply/);
  assert.doesNotMatch(chargerService, /product_variants|product_variant_spec_values/);
});

test('Wave5 order item snapshot and glass authorization behavior survives catalog integration', () => {
  assert.match(home, /actions=\{myActivities\} glassShortcuts variant="soft"/);
  assert.match(adminOrders, /item\.item_summary\?\.name/);
  assert.match(adminOrders, /paddingBottom: 200/);
  assert.doesNotMatch(adminOrders, /Detail capability=/);
  assert.match(api, /item_summary\?/);
  assert.equal(schemas[0], schemas[1]);
  assert.equal(schemas[1], schemas[2]);
});
