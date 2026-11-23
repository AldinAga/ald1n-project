import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const read = (relative) => readFileSync(new URL('../../src/' + relative, import.meta.url), 'utf8');
const create = read('app/(app)/admin/catalog/create.tsx');
const edit = read('app/(app)/admin/catalog/[id].tsx');
const types = read('types/api.ts');
const adminApi = read('features/admin/catalog-admin-api.ts');

test('create screen returns to an empty form with remembered type, brand, line and currency', () => {
  assert.match(create, /const resetForNextEntry = \(\) => \{/);
  const reset = create.slice(create.indexOf('const resetForNextEntry'), create.indexOf('const openSaved'));
  for (const kept of ['setProductTypeId', 'setBrandId', 'setLineId', 'setCurrency']) {
    assert.doesNotMatch(reset, new RegExp(kept + '\\('), kept + ' must stay untouched on reset');
  }
  for (const cleared of ['setModelName', 'setSupplierCode', 'setEan', 'setName', 'setPriceAmount', 'setDescription', 'setImages']) {
    assert.match(reset, new RegExp(cleared + '\\('));
  }
  // Novi artikal ne sme da ponovo iskoristi Idempotency-Key prethodnog snimanja.
  assert.match(reset, /createAttemptRef\.current = null/);
  assert.match(create, /resetForNextEntry\(\);/);
  assert.doesNotMatch(create.slice(create.indexOf('onSuccess: async'), create.indexOf('onError:')), /router\.replace/);
});

test('create screen offers the saved product through an explicit button', () => {
  assert.match(create, /Otvori snimljeni artikal/);
  assert.match(create, /pathname: '\/admin\/catalog\/\[id\]'/);
  assert.match(create, /pathname: '\/product\/\[slug\]'/);
});

test('supplier code and EAN are sent by create and edit and typed in the API contract', () => {
  assert.match(create, /supplier_code: supplierCode\.trim\(\) \|\| undefined/);
  assert.match(create, /ean: ean\.trim\(\) \|\| undefined/);
  assert.match(edit, /supplier_code: supplierCode\.trim\(\)/);
  assert.match(edit, /ean: ean\.trim\(\)/);
  assert.match(edit, /setSupplierCode\(detail\.supplier_code \?\? ''\)/);
  assert.match(types, /supplier_code\?: string \| null;/);
  assert.match(types, /ean\?: string \| null;/);
  assert.match(adminApi, /ean\?: string \| null;/);
});

test('draft autosave uses save_draft, only for drafts, and never rehydrates the form', () => {
  assert.match(edit, /const autosaveMutation = useMutation/);
  assert.match(edit, /setTimeout\(\(\) => submitRef\.current\?\.\(true, true\), 30000\)/);
  assert.match(edit, /product\?\.status !== 'draft' \|\| status !== 'draft' \|\| product\.is_archived/);
  const autosave = edit.slice(edit.indexOf('const autosaveMutation'), edit.indexOf('const archiveMutation'));
  assert.doesNotMatch(autosave, /setHydratedId|refetch/);
  assert.match(autosave, /Automatsko čuvanje nije uspelo/);
  assert.match(edit, /stock_quantity: autosave \? undefined : stock \?\? undefined/);
  assert.match(edit, /low_stock_threshold: autosave \? undefined : threshold \?\? undefined/);
  assert.match(edit, /if \(!autosave && autosavePromiseRef\.current\) \{/);
  assert.match(edit, /status: saveDraft \? 'draft' : status,\s+save_draft: saveDraft \|\| undefined,/);
  assert.match(edit, /if \(autosave && Object\.keys\(nextErrors\)\.length > 0\) return;/);
  assert.match(edit, /\.\.\.\(autosave \? \{ autosave: true \} : \{\}\)/);
  assert.match(types, /autosave\?: boolean;/);
});
