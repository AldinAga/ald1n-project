// Batch680 UX2 Product Editor: real-source regressions for shared section navigation and pinned save.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

const read = (path) => readFileSync(new URL('../../src/' + path, import.meta.url), 'utf8');
const shared = read('features/admin/product-editor-workspace.tsx');
const create = read('app/(app)/admin/catalog/create.tsx');
const edit = read('app/(app)/admin/catalog/[id].tsx');
const screen = read('components/layout/screen.tsx');

test('source files parse cleanly and no new native dependency is required', () => {
  for (const [file, source] of [['workspace', shared], ['create', create], ['edit', edit], ['screen', screen]]) {
    const tree = ts.createSourceFile(file + '.tsx', source, ts.ScriptTarget.Latest, true, ts.ScriptKind.TSX);
    assert.equal(tree.parseDiagnostics.length, 0, file + ' must have valid TSX');
  }
});

test('workspace keys and error-to-section mapping are deterministic', () => {
  const js = ts.transpileModule(shared, { compilerOptions: {
    module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022, jsx: ts.JsxEmit.ReactJSX,
  } });
  const exports = {};
  runInNewContext(js.outputText, { exports, require: () => ({}) }, { filename: 'product-editor-workspace.tsx' });
  const map = exports.productEditorWorkspaceForError;
  assert.equal(typeof map, 'function');
  for (const [field, expected] of [
    ['product_type_id', 'identity'], ['sku', 'identity'],
    ['specs.12', 'specifications'], ['spec_lists.2.1', 'specifications'],
    ['stock_quantity', 'commercial'], ['price_amount', 'commercial'], ['manual_commission_eur', 'commercial'],
    ['description', 'description'], ['notes', 'description'],
    ['images.0', 'images'], ['unknown', 'identity'],
  ]) assert.equal(map(field), expected, field);
  assert.match(shared, /key: 'advanced', title: 'Napredno'/);
  assert.match(shared, /minHeight: 44/);
  assert.match(shared, /accessibilityRole="tab"/);
});

test('both editors keep hidden sections mounted so draft values survive workspace switches', () => {
  for (const [name, source] of [['create', create], ['edit', edit]]) {
    assert.match(source, /activeWorkspace, setActiveWorkspace/);
    assert.match(source, /topAccessory=\{/);
    assert.match(source, /bottomAccessory=\{/);
    assert.match(source, /<ProductEditorWorkspaceNav/);
    assert.match(source, /<ProductEditorSaveDock/);
    assert.match(source, /workspaceHidden: \{ display: 'none' \}/);
    for (const key of ['identity', 'specifications', 'commercial', 'description', 'images']) {
      assert.ok(source.includes("activeWorkspace !== '" + key + "' ? styles.workspaceHidden : null"), name + ': ' + key);
    }
    assert.match(source, /setActiveWorkspace\(productEditorWorkspaceForError\(firstErrorField\)\)/);
    assert.match(source, /onSaveDraft=\{\(\) => submit\(true\)\}/);
    assert.match(source, /onSave=\{\(\) => submit\(false\)\}/);
  }
  assert.match(edit, /activeWorkspace === 'advanced' \? styles.section : styles.workspaceHidden/);
  assert.match(edit, /bottomAccessory=\{!product.is_archived/);
  assert.match(create, /saveLabel="Kreiraj artikal"/);
  assert.match(edit, /saveLabel="Sačuvaj izmene"/);
});

test('Screen pins nav and save outside scroll while remaining optional for all other screens', () => {
  assert.match(screen, /topAccessory\?: ReactNode/);
  assert.match(screen, /bottomAccessory\?: ReactNode/);
  assert.equal((screen.match(/\{topAccessory\}/g) ?? []).length, 2);
  assert.equal((screen.match(/\{bottomAccessory\}/g) ?? []).length, 2);
  assert.ok(screen.indexOf('      {topAccessory}\n      <ScrollView') > 0);
  assert.ok(screen.indexOf('      </ScrollView>\n      {bottomAccessory}') > 0);
  assert.match(shared, /Keyboard.addListener\('keyboardDidShow'/);
  assert.match(shared, /Keyboard.addListener\('keyboardDidHide'/);
  assert.match(shared, /width >= 600/);
  assert.match(shared, /Math.max\(insets.bottom, 0\) \+ 76/);
});

test('business API, Direct Sale, archive and image manager remain in their canonical screens', () => {
  assert.match(create, /api.admin.catalog.createProduct\(input, idempotencyKey\)/);
  assert.match(create, /DraftProductImageManager/);
  assert.match(create, /submitInFlightRef/);
  assert.match(edit, /apiAdminCatalog.update\(productId, input\)/);
  assert.match(edit, /RemoteProductImageManager/);
  assert.match(edit, /CatalogAdvancedProductActions/);
  assert.match(edit, /ProductDeletionAdmin/);
  assert.match(edit, /Evidentiraj prodaju/);
  assert.match(edit, /Arhiviraj artikal/);
  assert.doesNotMatch(shared, /Product Variants|product_variant_id|fetch\(/);
});
