import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { test } from 'node:test';
import { runInNewContext } from 'node:vm';

const require = createRequire(import.meta.url);
const ts = require('typescript');
const root = path.resolve(import.meta.dirname, '..');

const source = readFileSync(path.join(root, 'src/features/orders/order-unavailable-state.tsx'), 'utf8');

class StubApiError extends Error {
  constructor(status) {
    super('A public API error');
    this.status = status;
  }
}

function Screen() {}
function EmptyState() {}
function Button() {}
const navigations = [];
function jsx(type, props) {
  return { type, props: props ?? {} };
}
const module = { exports: {} };
const compiled = ts.transpileModule(source, {
  compilerOptions: {
    module: ts.ModuleKind.CommonJS,
    jsx: ts.JsxEmit.ReactJSX,
    target: ts.ScriptTarget.ES2022,
  },
  fileName: 'order-unavailable-state.tsx',
});

runInNewContext(compiled.outputText, {
  module,
  exports: module.exports,
  require(id) {
    switch (id) {
      case 'expo-router':
        return { router: { replace: (url) => navigations.push(url) } };
      case '@/components/layout/screen':
        return { Screen };
      case '@/components/ui/button':
        return { Button };
      case '@/components/ui/states':
        return { EmptyState };
      case '@/lib/api/client':
        return { ApiError: StubApiError };
      case 'react/jsx-runtime':
        return { jsx, jsxs: jsx };
      default:
        throw new Error('Unexpected imported module: ' + id);
    }
  },
}, { filename: 'order-unavailable-state.generated.cjs' });

const { isOrderUnavailable, OrderUnavailableState } = module.exports;

test('only real API 404 is unavailable (never conflate with permissions or connectivity)', () => {
  assert.equal(isOrderUnavailable(new StubApiError(404)), true);
  for (const status of [0, 400, 401, 403, 422, 500, 503]) {
    assert.equal(isOrderUnavailable(new StubApiError(status)), false, String(status));
  }
  assert.equal(isOrderUnavailable(new Error('Missing')), false);
  assert.equal(isOrderUnavailable({ status: 404 }), false);
  assert.equal(isOrderUnavailable(null), false);
});

for (const [audience, expectedPath] of [['admin', '/admin/orders'], ['user', '/orders']]) {
  test('neutral OrderUnavailableState returns ' + audience + ' to its own list', () => {
    navigations.length = 0;
    const screen = OrderUnavailableState({ audience });
    assert.equal(screen.type, Screen);
    const children = screen.props.children;
    assert.ok(Array.isArray(children) && children.length === 2);

    const empty = children[0];
    assert.equal(empty.type, EmptyState);
    assert.equal(empty.props.title, 'Porudžbina nije dostupna');
    assert.match(empty.props.message, /Možda je arhivirana, uklonjena ili više nemate pristup/);
    assert.doesNotMatch(empty.props.message, /134|purged|obrisana trajno|pripada drugom/);

    const button = children[1];
    assert.equal(button.type, Button);
    assert.equal(button.props.children, 'Nazad na listu porudžbina');
    button.props.onPress();
    assert.deepEqual(navigations, [expectedPath]);
  });
}

test('both order detail screens route only API 404 to unavailable state', () => {
  const screens = [
    ['src/app/(app)/admin/orders/[id].tsx', 'admin'],
    ['src/app/(app)/order/[id].tsx', 'user'],
  ];
  for (const [file, audience] of screens) {
    const data = readFileSync(path.join(root, file), 'utf8');
    assert.ok(data.includes('if (query.isError && isOrderUnavailable(query.error))'), file);
    assert.ok(data.includes('audience="' + audience + '"'), file);
    assert.ok(data.includes('retry: (failureCount, error) => !isOrderUnavailable(error) && failureCount < 3'), file);
    assert.ok(data.includes('return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;'), file);
  }
});
