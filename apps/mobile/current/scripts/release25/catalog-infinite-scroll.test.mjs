// Catalog infinite-scroll regression: real source, no emulator or backend writes.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

const read = (path) => readFileSync(new URL('../../src/' + path, import.meta.url), 'utf8');
const catalog = read('app/(app)/(tabs)/catalog.tsx');
const paging = read('features/catalog/catalog-pagination.ts');
const backend = readFileSync(new URL('../../../../cms/current/app/Services/CatalogQueryService.php', import.meta.url), 'utf8');

function helpers() {
  const js = ts.transpileModule(paging, { compilerOptions: {
    module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022,
  } });
  const exports = {};
  runInNewContext(js.outputText, { exports }, { filename: 'catalog-pagination.ts' });
  return exports;
}

const page = (current, last, ids, total) => ({
  meta: {
    current_page: current, last_page: last, total,
    from: ids.length ? (current - 1) * 30 + 1 : null,
    to: ids.length ? (current - 1) * 30 + ids.length : null,
    per_page: 30,
  },
  data: ids.map(id => ({ id, name: 'Artikal ' + id, slug: 'artikal-' + id })),
});

test('catalog uses an infinite query, scroll pagination and a graceful footer', () => {
  for (const [name, src, kind] of [['catalog', catalog, ts.ScriptKind.TSX], ['pagination', paging, ts.ScriptKind.TS]]) {
    const tree = ts.createSourceFile(name, src, ts.ScriptTarget.Latest, true, kind);
    assert.equal(tree.parseDiagnostics.length, 0, name + ' parses');
  }
  assert.match(catalog, /useInfiniteQuery\(/);
  assert.match(catalog, /initialPageParam:\s*1/);
  assert.match(catalog, /getNextPageParam:\s*nextCatalogPage/);
  assert.match(catalog, /api\.catalog\.products\(\{\s*\.\.\.productParams,\s*page:\s*pageParam/);
  assert.match(catalog, /queryKey:\s*\['products',\s*productParams\]/);
  assert.match(catalog, /data=\{products\}/);
  assert.match(catalog, /onEndReached=\{loadNextPage\}/);
  assert.match(catalog, /query\.hasNextPage/);
  assert.match(catalog, /query\.isFetching/);
  assert.match(catalog, /query\.isFetchNextPageError/);
  assert.match(catalog, /Prikazano \{products\.length\} od \{total\} artikala/);
  assert.doesNotMatch(catalog, /data=\{query\.data\?\.data/);
});

test('54 products are shown by progressively loading both 30 + 24 pages', () => {
  const { nextCatalogPage, catalogProductsFromPages } = helpers();
  const first = page(1, 2, Array.from({ length: 30 }, (_, i) => i + 1), 54);
  const second = page(2, 2, Array.from({ length: 24 }, (_, i) => i + 31), 54);
  assert.equal(nextCatalogPage(first), 2);
  assert.equal(nextCatalogPage(second), undefined);
  const items = catalogProductsFromPages([first, second]);
  assert.equal(items.length, 54);
  assert.equal(items[0].id, 1);
  assert.equal(items.at(-1).id, 54);
});

test('250 products span nine pages without a hard total cap', () => {
  const { nextCatalogPage, catalogProductsFromPages } = helpers();
  const pages = Array.from({ length: 9 }, (_, n) =>
    page(n + 1, 9, Array.from({ length: Math.min(30, 250 - n * 30) }, (_, i) => n * 30 + i + 1), 250));
  assert.equal(catalogProductsFromPages(pages).length, 250);
  assert.equal(nextCatalogPage(pages[7]), 9);
  assert.equal(nextCatalogPage(pages[8]), undefined);
});

test('short, empty, bad-meta and overlapping page responses cannot loop or duplicate cards', () => {
  const { nextCatalogPage, catalogProductsFromPages } = helpers();
  assert.equal(nextCatalogPage(page(2, 5, [], 100)), undefined);
  assert.equal(nextCatalogPage({ data: [{ id: 1 }], meta: undefined }), undefined);
  assert.equal(nextCatalogPage(page(2, 2, [1], 40)), undefined);
  assert.equal(nextCatalogPage(page(3, 2, [1], 40)), undefined);
  assert.equal(nextCatalogPage(page(0, 3, [1], 40)), undefined);
  const merged = catalogProductsFromPages([page(1, 2, [1, 2, 3], 4), page(2, 2, [3, 4], 4)]);
  assert.deepEqual(merged.map(x => x.id).join(','), '1,2,3,4');
});

test('Laravel API retains limited page size but orders ties deterministically by id', () => {
  assert.match(backend, /orderByDesc\('id'\)/);
  assert.match(backend, /return \$query->paginate\(max\(6, min\(\$safeMaximum, \$perPage\)\)\)->withQueryString\(\)/);
  assert.ok(backend.indexOf("orderByDesc('id')") < backend.indexOf('return $query->paginate(max(6'));
});
