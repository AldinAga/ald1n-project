import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import ts from 'typescript';

const read = (path) => readFileSync(new URL('../../' + path, import.meta.url), 'utf8');

function loadHelper() {
  const source = read('src/features/admin/catalog-line-labels.ts');
  const module = { exports: {} };
  new Function('module', 'exports', ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 } }).outputText)(module, module.exports);
  return module.exports;
}

test('laptop charger type labels the line selector as connector type', () => {
  const { lineSelectorLabels } = loadHelper();
  for (const name of ['Punjači za laptopove', 'Punjaci za laptopove', ' punjači za laptopove ', 'Punjači za  laptopove']) {
    assert.deepEqual(lineSelectorLabels(name), { label: 'Tip priključka', none: 'Bez tipa priključka' });
  }
});

test('other types and missing type keep the product line label', () => {
  const { lineSelectorLabels } = loadHelper();
  for (const name of ['Laptopovi', 'Torbe za laptopove', '', null, undefined]) {
    assert.deepEqual(lineSelectorLabels(name), { label: 'Linija', none: 'Bez linije' });
  }
});

test('create and edit screens use the helper instead of hard-coded line copy', () => {
  for (const path of ['src/app/(app)/admin/catalog/create.tsx', 'src/app/(app)/admin/catalog/[id].tsx']) {
    const source = read(path);
    assert.match(source, /lineSelectorLabels\(selectedType\?\.name\)/, path);
    assert.match(source, /label=\{lineLabels\.label\}/, path);
    assert.doesNotMatch(source, /label="Linija"/, path);
    assert.doesNotMatch(source, /label: 'Bez linije'/, path);
  }
});
