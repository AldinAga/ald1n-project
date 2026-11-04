// Exercise the real private-file callers and transport; native I/O and HTTP stay synthetic.
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

const pdf = new TextEncoder().encode('%PDF-1.7\nsynthetic-private-document');
const base = 'https://cms.example.invalid/api/v1';
const proof = { original_name: 'proof:/ safe.pdf', mime_type: 'application/pdf', size_bytes: pdf.length };
const attachment = (path = '/api/v1/after-sales/attachments/7') => ({
  id: 7, download_path: path, original_name: 'private:/ safe.pdf', mime_type: 'application/pdf', size_bytes: pdf.length,
});

function fixture(options = {}) {
  const calls = [], files = [], shared = [], timers = new Set(), modules = new Map();
  let unauthorized = 0;
  class File {
    constructor(parent, name) { this.uri = parent + '/' + name; this.name = name; this.exists = false; this.size = 0; files.push(this); }
    create(settings) { this.settings = settings; this.exists = true; }
    write(bytes) { this.bytes = [...bytes]; this.size = options.badWrite ? 0 : bytes.length; }
    delete() { this.deleted = true; this.exists = false; }
  }
  const native = {
    'expo-constants': { default: { expoConfig: { extra: { apiUrl: options.extra ?? base } } } },
    'expo/fetch': { fetch: () => { throw new Error('Unexpected multipart transport'); } },
    '@/lib/storage': { tokenStore: { get: async () => options.token === undefined ? 'synthetic-token' : options.token } },
    'expo-file-system': { File, Paths: { cache: 'file:///synthetic-cache' } },
    'react-native': { Platform: { OS: 'android' } },
    'expo-sharing': { isAvailableAsync: async () => true, shareAsync: async (...args) => shared.push(args) },
  };
  const sourceModules = new Set([
    '@/lib/api/client', '@/lib/api/endpoints', '@/features/orders/order-post-create-files',
    '@/features/warranties/warranty-pdf', '@/features/after-sales/attachment-download',
    '@/features/admin/orders-admin-api', '@/features/admin/order-document-files', '@/features/admin/reports-admin-api',
  ]);
  function load(path) {
    if (Object.hasOwn(native, path)) return native[path];
    if (!sourceModules.has(path)) throw new Error('Unexpected import: ' + path);
    if (modules.has(path)) return modules.get(path);
    const file = path.slice(2) + '.ts';
    const source = readFileSync(new URL('../../src/' + file, import.meta.url), 'utf8');
    const tree = ts.createSourceFile(file, source, ts.ScriptTarget.Latest, true);
    assert.equal(tree.parseDiagnostics.length, 0, file + ' parses');
    const exports = {};
    modules.set(path, exports);
    const compiled = ts.transpileModule(source, { compilerOptions: {
      module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022,
    } });
    runInNewContext(compiled.outputText, {
      exports, require: load,
      process: { env: options.envAbsent ? {} : { EXPO_PUBLIC_API_URL: options.base ?? base } },
      Headers, AbortController, URLSearchParams, Uint8Array, Error,
      setTimeout: (callback, ms) => { const timer = { callback, ms }; timers.add(timer); return timer; },
      clearTimeout: timer => timers.delete(timer),
      fetch: async (url, init) => {
        calls.push({ url, init });
        if (options.error) throw options.error;
        const bytes = options.bytes ?? pdf;
        const status = options.status ?? 200;
        const headers = new Headers(options.headers ?? { 'content-type': 'application/pdf', 'content-length': String(bytes.length) });
        return {
          ok: status >= 200 && status < 300, status, headers,
          arrayBuffer: async () => bytes.slice().buffer,
          json: async () => { if (options.badJson) throw new Error('Invalid JSON'); return options.payload ?? {}; },
          text: async () => options.text ?? '',
        };
      },
    }, { filename: file });
    return exports;
  }
  const client = load('@/lib/api/client');
  client.setUnauthorizedHandler(async () => { unauthorized++; });
  function request(path) {
    assert.equal(calls.length, 1);
    assert.equal(calls[0].url, base + '/' + path);
    assert.equal(calls[0].init.method, 'GET');
    assert.equal(calls[0].init.headers.get('authorization'), 'Bearer synthetic-token');
    assert.equal(calls[0].init.headers.get('accept'), 'application/octet-stream');
    assert.equal(calls[0].init.headers.get('x-mobile-client'), 'ald1n-mobile/0.4.0');
    assert.ok(calls[0].init.signal instanceof AbortSignal);
    assert.equal(timers.size, 0);
  }
  function saved(prefix, share = true) {
    assert.equal(files.length, 1);
    assert.ok(files[0].uri.startsWith('file:///synthetic-cache/' + prefix));
    assert.deepEqual(files[0].bytes, [...pdf]);
    assert.equal(files[0].size, pdf.length);
    assert.equal(shared.length, share ? 1 : 0);
    if (share) assert.equal(shared[0][0], files[0].uri);
  }
  return { load, client, calls, files, shared, timers, request, saved, unauthorized: () => unauthorized };
}

for (const [name, invoke, route, prefix, share] of [
  ['order payment proof', h => h.load('@/features/orders/order-post-create-files').openOrderPaymentProof(12, { id: 7, has_proof: true, proof }), 'orders/12/payments/7/proof', 'order-private-proof-safe.pdf', true],
  ['order confirmation PDF', h => h.load('@/features/orders/order-post-create-files').openOrderConfirmationPdf(12, 'O/12'), 'orders/12/documents/confirmation.pdf', 'order-private-potvrda-O-12.pdf', true],
  ['order document PDF', h => h.load('@/features/orders/order-post-create-files').openOrderDocumentPdf(12, { id: 7, type: 'invoice', number: 'I/7' }), 'orders/12/documents/7.pdf', 'order-private-invoice-I-7.pdf', true],
  ['order delivery proof', h => h.load('@/features/orders/order-post-create-files').openOrderDeliveryProof(12, { id: 7, has_proof: true, proof }), 'orders/12/delivery-proof', 'order-private-proof-safe.pdf', true],
  ['customer warranty PDF', h => h.load('@/features/warranties/warranty-pdf').openWarrantyPdf(7, 'W/7'), 'warranties/7.pdf', 'warranty-7-W-7.pdf', true],
  ['after-sales attachment', h => h.load('@/features/after-sales/attachment-download').downloadAfterSalesAttachment(attachment()), 'after-sales/attachments/7', 'after-sales-7-private__ safe.pdf', false],
  ['field-work attachment', h => h.load('@/features/after-sales/attachment-download').downloadAfterSalesAttachment(attachment('/api/v1/field-work-order-attachments/7')), 'field-work-order-attachments/7', 'field-work-7-private__ safe.pdf', false],
]) {
  test('private download family: ' + name, async () => {
    const h = fixture();
    await invoke(h);
    h.saved(prefix, share);
    h.request(route);
  });
}

test('relative admin PDF callers retain routes, bytes and sharing', async () => {
  for (const [invoke, route, prefix, share] of [
    [h => h.load('@/features/admin/order-document-files').openAdminOrderConfirmationPdf(12), 'admin/orders/12/documents/confirmation.pdf', 'admin-order-potvrda-12.pdf', true],
    [h => h.load('@/features/admin/order-document-files').openAdminOrderDocumentPdf(12, { id: 7, type: 'invoice', number: 'I/7' }), 'admin/orders/12/documents/7.pdf', 'admin-order-invoice-I-7.pdf', true],
    [h => h.load('@/features/warranties/warranty-pdf').downloadAdminWarrantyPdf(7, 'W/7'), 'admin/warranties/7.pdf', 'admin-warranty-7-W-7.pdf', false],
  ]) {
    const h = fixture(); await invoke(h); h.saved(prefix, share); h.request(route);
  }
});

test('relative inputs and configuration normalization preserve existing destinations', async () => {
  for (const options of [{}, { base: base + '/' }, { base: base + '///' }, { envAbsent: true, extra: base + '//' }, { base: 'http://local.example.invalid/api/v1' }, { base: 'https://custom.example.invalid' }, { base: 'https://custom.example.invalid/custom' }]) {
    for (const path of ['admin/orders/12.pdf', '/admin/orders/12.pdf']) {
      const h = fixture(options); await h.client.apiDownload(path);
      assert.equal(h.calls[0].url, h.client.API_URL + '/admin/orders/12.pdf');
      assert.equal(h.timers.size, 0);
    }
  }
});

test('host-only and custom bases retain rooted customer-path compatibility', async () => {
  for (const url of ['https://custom.example.invalid', 'https://custom.example.invalid/custom']) {
    const h = fixture({ base: url }); await h.client.apiDownload('/api/v1/warranties/7.pdf');
    assert.equal(h.calls[0].url, url + '/api/v1/warranties/7.pdf');
  }
});

test('rooted customer paths normalize once with trailing slashes, Expo fallback and encoded query bytes', async () => {
  for (const options of [{}, { base: base + '/' }, { base: base + '///' }, { envAbsent: true, extra: base + '//' }, { base: 'http://local.example.invalid/api/v1' }]) {
    const h = fixture(options);
    await h.client.apiDownload('/api/v1/orders/12.pdf?q=a%20%26%2F%3F%C4%8D');
    assert.equal(h.calls[0].url, h.client.API_URL + '/orders/12.pdf?q=a%20%26%2F%3F%C4%8D');
  }
});

test('real report query builders preserve encoded query bytes', async () => {
  for (const build of [m => m.adminReportExportPath('csv', { q: 'a & /? č' }), m => m.adminOperationalReportExportPath('orders-csv', { q: 'a & /? č' })]) {
    const h = fixture(), route = build(h.load('@/features/admin/reports-admin-api'));
    assert.ok(route.endsWith('?q=a+%26+%2F%3F+%C4%8D'));
    await h.client.apiDownload(route); h.request(route);
  }
});

test('initial request cannot delegate bearer credentials to an input origin', async () => {
  for (const path of ['https://other.invalid/file', 'http://other.invalid/file', base + '/file', '//other.invalid/file', '///other.invalid/file', '/api/v1//other.invalid/file', '/api/v1', 'api/v1/orders/12.pdf']) {
    const h = fixture(); await h.client.apiDownload(path);
    const expectedSuffix = path === '/api/v1//other.invalid/file' ? 'other.invalid/file' : path.replace(/^\//, '');
    assert.equal(h.calls[0].url, base + '/' + expectedSuffix);
    assert.equal(new URL(h.calls[0].url).origin, new URL(base).origin);
    assert.equal(h.calls[0].init.headers.get('authorization'), 'Bearer synthetic-token');
  }
});

test('successful bytes, content type, content-length normalization and absent token', async () => {
  for (const [length, expected] of [[undefined, null], ['bad', null], ['-1', null], ['Infinity', null], ['0', 0], [String(pdf.length), pdf.length]]) {
    const headers = { 'content-type': 'application/pdf; charset=binary', ...(length === undefined ? {} : { 'content-length': length }) };
    const h = fixture({ headers, token: null }), result = await h.client.apiDownload('admin/file');
    assert.deepEqual([...result.bytes], [...pdf]);
    assert.equal(result.contentType, 'application/pdf; charset=binary'); assert.equal(result.contentLength, expected);
    assert.equal(h.calls[0].init.headers.has('authorization'), false); assert.equal(h.timers.size, 0);
  }
});

test('JSON error preserves ApiError, field data and header request-ID precedence', async () => {
  const h = fixture({ status: 403, headers: { 'content-type': 'application/json', 'x-request-id': 'header-id' }, payload: { message: 'Denied', code: 'forbidden', errors: { file: ['Denied file'] }, request_id: 'body-id' } });
  await assert.rejects(h.client.apiDownload('admin/file'), error => error instanceof h.client.ApiError && error.status === 403 && error.code === 'forbidden' && error.requestId === 'header-id' && error.firstFieldError() === 'Denied file');
  assert.equal(h.unauthorized(), 0); assert.equal(h.timers.size, 0);
});

test('401 invokes registered handler exactly once and retains payload request-ID', async () => {
  const h = fixture({ status: 401, headers: { 'content-type': 'application/json' }, payload: { message: 'Expired', request_id: 'body-id' } });
  await assert.rejects(h.client.apiDownload('admin/file'), error => error instanceof h.client.ApiError && error.status === 401 && error.requestId === 'body-id');
  assert.equal(h.unauthorized(), 1); assert.equal(h.timers.size, 0);
});

test('non-JSON and malformed JSON errors retain status fallbacks', async () => {
  for (const options of [{ headers: { 'content-type': 'text/html' }, text: ' Denied ' }, { headers: { 'content-type': 'application/json' }, badJson: true }]) {
    const h = fixture({ ...options, status: 500 });
    await assert.rejects(h.client.apiDownload('admin/file'), error => error instanceof h.client.ApiError && error.status === 500 && error.code === 'api_error' && (options.text ? error.message === 'Denied' : error.message.includes('500')));
    assert.equal(h.unauthorized(), 0); assert.equal(h.timers.size, 0);
  }
});

test('network and AbortError use existing mappings and clear the timer', async () => {
  for (const [error, code] of [[new Error('offline'), 'network_error'], [Object.assign(new Error('abort'), { name: 'AbortError' }), 'request_timeout']]) {
    const h = fixture({ error });
    await assert.rejects(h.client.apiDownload('admin/file'), e => e instanceof h.client.ApiError && e.status === 0 && e.code === code);
    assert.equal(h.timers.size, 0);
  }
});

test('invalid caller IDs and untrusted attachment paths fail before HTTP or writes', async () => {
  for (const invoke of [
    h => h.load('@/features/orders/order-post-create-files').openOrderConfirmationPdf(0, 'x'),
    h => h.load('@/features/orders/order-post-create-files').openOrderDocumentPdf(12, { id: -1 }),
    h => h.load('@/features/orders/order-post-create-files').openOrderPaymentProof(12, { id: 7, has_proof: false }),
    h => h.load('@/features/warranties/warranty-pdf').downloadWarrantyPdf(0, 'x'),
    h => h.load('@/features/after-sales/attachment-download').downloadAfterSalesAttachment({ ...attachment(), id: -1 }),
    h => h.load('@/features/after-sales/attachment-download').downloadAfterSalesAttachment(attachment('https://other.invalid/private')),
  ]) {
    const h = fixture(); await assert.rejects(invoke(h)); assert.equal(h.calls.length, 0); assert.equal(h.files.length, 0); assert.equal(h.shared.length, 0);
  }
});

test('malformed PDF and mismatched sizes never reach sharing', async () => {
  for (const options of [{ bytes: new TextEncoder().encode('not-pdf') }, { headers: { 'content-type': 'application/pdf', 'content-length': '999' } }]) {
    for (const invoke of [h => h.load('@/features/warranties/warranty-pdf').openWarrantyPdf(7, 'W/7'), h => h.load('@/features/orders/order-post-create-files').openOrderConfirmationPdf(12, 'O/12')]) {
      const h = fixture(options); await assert.rejects(invoke(h)); assert.equal(h.shared.length, 0); assert.equal(h.files.length, 0);
    }
  }
  const h = fixture();
  await assert.rejects(h.load('@/features/after-sales/attachment-download').downloadAfterSalesAttachment({ ...attachment(), size_bytes: 999 }));
  assert.equal(h.files.length, 0);
});

test('failed native writes are deleted and never shared', async () => {
  for (const invoke of [h => h.load('@/features/warranties/warranty-pdf').openWarrantyPdf(7, 'W/7'), h => h.load('@/features/orders/order-post-create-files').openOrderConfirmationPdf(12, 'O/12'), h => h.load('@/features/after-sales/attachment-download').downloadAfterSalesAttachment(attachment())]) {
    const h = fixture({ badWrite: true }); await assert.rejects(invoke(h)); assert.equal(h.files[0].deleted, true); assert.equal(h.shared.length, 0);
  }
});
