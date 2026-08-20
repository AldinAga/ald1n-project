import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const ts = require('typescript');
const root = path.resolve(import.meta.dirname, '..');
let failures = 0;

function pass(message) { console.log(`PASS ${message}`); }
function fail(message) { failures += 1; console.error(`FAIL ${message}`); }
function assert(condition, message) { condition ? pass(message) : fail(message); }

const required = [
  'package.json', 'app.config.js', 'eas.json', '.env.example',
  'assets/icon.png', 'assets/adaptive-icon.png', 'assets/splash-icon.png',
  'src/app/_layout.tsx', 'src/app/(auth)/login.tsx',
  'src/app/(app)/(tabs)/home.tsx', 'src/app/(app)/(tabs)/catalog.tsx',
  'src/app/(app)/(tabs)/orders.tsx', 'src/app/(app)/(tabs)/notifications.tsx',
  'src/app/(app)/(tabs)/account.tsx', 'src/app/(app)/product/[slug].tsx',
  'src/app/(app)/order/[id].tsx', 'src/app/(app)/devices.tsx',
  'src/app/(app)/cart.tsx', 'src/app/(app)/checkout.tsx',
  'src/app/(app)/notification-settings.tsx',
  'src/app/(app)/after-sales/index.tsx',
  'src/app/(app)/after-sales/[id].tsx',
  'src/app/(app)/after-sales/create/[orderId].tsx',
  'src/app/(app)/warranties/index.tsx',
  'src/app/(app)/warranties/[id].tsx',
  'src/app/(app)/commissions/index.tsx',
  'src/app/(app)/commissions/[id].tsx',
  'src/app/(app)/assigned-orders/index.tsx',
  'src/app/(app)/assigned-orders/[id].tsx',
  'src/features/warranties/warranty-pdf.ts',
  'src/features/orders/order-post-create-files.ts',
  'src/features/after-sales/attachment-picker.ts',
  'src/features/after-sales/attachment-download.ts',
  'src/lib/api/client.ts', 'src/lib/api/endpoints.ts',
  'src/features/auth/auth-provider.tsx', 'src/features/auth/google-auth.ts', 'src/features/device/device-registrar.tsx',
  'src/features/cart/cart-provider.tsx',
  'src/features/notifications/push-service.ts',
  'src/features/notifications/push-notification-bridge.tsx',
  'docs/openapi.yaml',
  'tamagui.config.ts',
  'src/design/ald1n-tokens.generated.ts'
];
for (const file of required) assert(fs.existsSync(path.join(root, file)), `${file} postoji.`);

const projectRoot = path.resolve(root, '../../..');
const tokenGenerator = path.join(
  projectRoot,
  'scripts/generate-design-tokens.mjs'
);

const tokenCheck = spawnSync(
  process.execPath,
  [tokenGenerator, '--check'],
  {
    encoding: 'utf8',
    cwd: projectRoot
  }
);

if (tokenCheck.status === 0) {
  pass(
    'Generated design token fajlovi su sinhronizovani sa canonical JSON source-om.'
  );
} else {
  fail(
    'Generated design token fajlovi nisu sinhronizovani sa canonical JSON source-om.'
  );

  if (tokenCheck.stdout?.trim()) {
    console.error(tokenCheck.stdout.trim());
  }

  if (tokenCheck.stderr?.trim()) {
    console.error(tokenCheck.stderr.trim());
  }
}

const tamaguiThemeSource = fs.readFileSync(
  path.join(root, 'tamagui.config.ts'),
  'utf8'
);

assert(
  tamaguiThemeSource.includes(
    'onBrand: tokens.onPrimary'
  ),
  'Tamagui onBrand koristi canonical onPrimary semantic token.'
);

for (const jsonFile of ['package.json', 'eas.json']) {
  try {
    JSON.parse(fs.readFileSync(path.join(root, jsonFile), 'utf8'));
    pass(`${jsonFile} je validan JSON.`);
  } catch (error) {
    fail(`${jsonFile} nije validan JSON: ${error.message}`);
  }
}

const packageJson = JSON.parse(fs.readFileSync(path.join(root, 'package.json'), 'utf8'));
const packageLockJson = JSON.parse(fs.readFileSync(path.join(root, 'package-lock.json'), 'utf8'));
assert(packageJson.dependencies?.expo === '~57.0.15' && packageLockJson.packages?.['']?.dependencies?.expo === '~57.0.15' && packageLockJson.packages?.['node_modules/expo']?.version === '57.0.15', 'Expo SDK 57 verzija prati zvanični template.');
assert(packageJson.dependencies?.['react-native'] === '0.86.2', 'React Native verzija prati Expo SDK 57 template.');
assert(packageJson.dependencies?.['expo-router'] === '~57.0.15' && packageLockJson.packages?.['']?.dependencies?.['expo-router'] === '~57.0.15' && packageLockJson.packages?.['node_modules/expo-router']?.version === '57.0.15', 'Expo Router verzija je zaključana.');
assert(packageJson.dependencies?.['expo-dev-client'] === '~57.0.14' && packageLockJson.packages?.['']?.dependencies?.['expo-dev-client'] === '~57.0.14' && packageLockJson.packages?.['node_modules/expo-dev-client']?.version === '57.0.14', 'Expo development client je uključen.');
assert(Boolean(packageJson.dependencies?.['expo-secure-store']), 'SecureStore zavisnost postoji.');
assert(Boolean(packageJson.dependencies?.['@tanstack/react-query']), 'TanStack Query zavisnost postoji.');
assert(packageJson.engines?.node === '>=22.13.0', 'Minimalna Node.js verzija odgovara SDK 57 zahtevu.');
// MOBILE_RELEASE_VERSION_V07
// MOBILE_RELEASE_VERSION_V08
assert(packageJson.version === '0.8.0', 'Aplikaciona package verzija je 0.8.0.');
assert(packageLockJson.version === '0.8.0' && packageLockJson.packages?.['']?.version === '0.8.0', 'package-lock release verzija je 0.8.0.');
assert(packageJson.dependencies?.['expo-notifications'] === '~57.0.13' && packageLockJson.packages?.['']?.dependencies?.['expo-notifications'] === '~57.0.13' && packageLockJson.packages?.['node_modules/expo-notifications']?.version === '57.0.13', 'expo-notifications prati SDK 57 preporučenu verziju.');
assert(packageJson.dependencies?.['expo-symbols'] === '~57.0.2', 'Expo Symbols je uključen za native Material/SF ikonice.');
assert(packageJson.dependencies?.['react-native-nitro-google-signin'] === '1.0.2', 'Moderni Google Credential Manager bridge je uključen.');
assert(packageJson.dependencies?.['react-native-nitro-modules'] === '0.36.1', 'Nitro Modules runtime je pinovan.');
assert(packageJson.dependencies?.tamagui === '2.6.0', 'Tamagui 2 runtime je pinovan.');
assert(packageJson.dependencies?.['@tamagui/config'] === '2.6.0', 'Tamagui Config v5 paket je pinovan.');
assert(packageJson.dependencies?.['@tamagui/animations-reanimated'] === '2.6.0', 'Tamagui Reanimated driver je pinovan.');
assert(packageJson.dependencies?.['expo-system-ui'] === '~57.0.2', 'Expo System UI prati SDK 57 preporucenu verziju.');
assert(packageJson.dependencies?.['expo-status-bar'] === '~57.0.1', 'Expo Status Bar prati SDK 57 preporucenu verziju.');
assert(packageJson.dependencies?.['expo-file-system'] === '~57.0.5' && packageLockJson.packages?.['']?.dependencies?.['expo-file-system'] === '~57.0.5' && packageLockJson.packages?.['node_modules/expo-file-system']?.version === '57.0.5', 'Expo FileSystem je direktno zakljucan za after-sales izbor priloga.');
assert(packageJson.dependencies?.['expo-sharing'] === '~57.0.14' && packageLockJson.packages?.['']?.dependencies?.['expo-sharing'] === '~57.0.14' && packageLockJson.packages?.['node_modules/expo-sharing']?.version === '57.0.14', 'Expo Sharing je zakljucan za bezbedno otvaranje privatnih after-sales priloga.');

// MOBILE_V0_8_EXPO_SDK57_COMPATIBILITY_MATRIX
const expoCompatibilityMatrixV08 = {
  expo: { spec: '~57.0.15', version: '57.0.15' },
  'expo-constants': { spec: '~57.0.13', version: '57.0.13' },
  'expo-dev-client': { spec: '~57.0.14', version: '57.0.14' },
  'expo-file-system': { spec: '~57.0.5', version: '57.0.5' },
  'expo-linking': { spec: '~57.0.7', version: '57.0.7' },
  'expo-notifications': { spec: '~57.0.13', version: '57.0.13' },
  'expo-router': { spec: '~57.0.15', version: '57.0.15' },
  'expo-sharing': { spec: '~57.0.14', version: '57.0.14' },
  'expo-updates': { spec: '~57.0.16', version: '57.0.16' },
};
for (const [packageName, expected] of Object.entries(expoCompatibilityMatrixV08)) {
  assert(
    packageJson.dependencies?.[packageName] === expected.spec
      && packageLockJson.packages?.['']?.dependencies?.[packageName] === expected.spec
      && packageLockJson.packages?.[`node_modules/${packageName}`]?.version === expected.version,
    `v0.8 Expo compatibility matrix zaključava ${packageName} na ${expected.spec}.`,
  );
}

const sourceFiles = [];
function walk(directory) {
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const full = path.join(directory, entry.name);
    if (entry.isDirectory()) walk(full);
    else if (/\.(ts|tsx)$/.test(entry.name)) sourceFiles.push(full);
  }
}
walk(path.join(root, 'src'));

const staticThemeConsumers = [];
const legacyColorConsumers = [];
const unsafeCastConsumers = [];

for (const file of sourceFiles) {
  const source = fs.readFileSync(file, 'utf8');
  const relative = path.relative(root, file);

  if (
    /import\s*\{[^}]*\bcolors\b[^}]*\}\s*from\s*['"]@\/constants\/theme['"]/.test(
      source
    )
  ) {
    staticThemeConsumers.push(relative);
  }

  if (
    relative !== 'src/constants/theme.ts' &&
    /\bcolors\./.test(source)
  ) {
    legacyColorConsumers.push(relative);
  }

  if (
    /as never|as unknown as/.test(source)
  ) {
    unsafeCastConsumers.push(relative);
  }
}

if (staticThemeConsumers.length === 0) {
  pass(
    'Static colors consumeri su uklonjeni iz aplikacionog source-a.'
  );
} else {
  fail(
    `Static colors consumeri postoje: ${staticThemeConsumers.join(', ')}`
  );
}

if (legacyColorConsumers.length === 0) {
  pass(
    'Legacy colors.* usage ne postoji van RN theme adaptera.'
  );
} else {
  fail(
    `Legacy colors.* usage postoji: ${legacyColorConsumers.join(', ')}`
  );
}

if (unsafeCastConsumers.length === 0) {
  pass(
    'Unsafe as never / as unknown as castovi ne postoje u source-u.'
  );
} else {
  fail(
    `Unsafe castovi postoje: ${unsafeCastConsumers.join(', ')}`
  );
}

for (const file of sourceFiles) {
  const source = fs.readFileSync(file, 'utf8');
  const result = ts.transpileModule(source, {
    compilerOptions: {
      jsx: ts.JsxEmit.ReactJSX,
      target: ts.ScriptTarget.ES2022,
      module: ts.ModuleKind.ESNext
    },
    fileName: file,
    reportDiagnostics: true
  });
  const diagnostics = result.diagnostics ?? [];
  if (diagnostics.length) {
    fail(`${path.relative(root, file)} ima TypeScript sintaksnu grešku: ${diagnostics.map((d) => ts.flattenDiagnosticMessageText(d.messageText, ' ')).join('; ')}`);
  }
}
assert(failures === 0, `${sourceFiles.length} TypeScript/TSX fajlova prolazi sintaksnu proveru.`);
const configSource = fs.readFileSync(path.join(root, 'app.config.js'), 'utf8');
const configResult = ts.transpileModule(configSource, {
  compilerOptions: { target: ts.ScriptTarget.ES2022, module: ts.ModuleKind.ESNext },
  fileName: 'app.config.js',
  reportDiagnostics: true
});
assert((configResult.diagnostics ?? []).length === 0, 'app.config.ts prolazi TypeScript sintaksnu proveru.');


function localImportExists(specifier) {
  const relative = specifier.slice(2);
  const base = path.join(root, 'src', relative);
  return [base, `${base}.ts`, `${base}.tsx`, path.join(base, 'index.ts'), path.join(base, 'index.tsx')].some(fs.existsSync);
}

let localImports = 0;
for (const file of sourceFiles) {
  const source = fs.readFileSync(file, 'utf8');
  const regex = /(?:from\s+|import\s*)['\"](@\/[^'\"]+)['\"]/g;
  for (const match of source.matchAll(regex)) {
    localImports += 1;
    if (!localImportExists(match[1])) fail(`${path.relative(root, file)} ima nepostojeći lokalni import ${match[1]}.`);
  }
}
if (failures === 0) pass(`${localImports} lokalnih @/ importa je razrešeno.`);

const client = fs.readFileSync(path.join(root, 'src/lib/api/client.ts'), 'utf8');
assert(client.includes('Authorization') && client.includes('Bearer'), 'Bearer token header je implementiran.');
assert(client.includes('response.status === 401'), 'Globalni 401 logout je implementiran.');
assert(client.includes('request_id') || client.includes('requestId'), 'Request ID je sačuvan u API grešci.');
assert(client.includes('AbortController'), 'API timeout je implementiran.');

const auth = fs.readFileSync(path.join(root, 'src/features/auth/auth-provider.tsx'), 'utf8');
assert(auth.includes('tokenStore.set') && auth.includes('tokenStore.clear'), 'Secure auth lifecycle je implementiran.');
assert(auth.includes("setStatus('anonymous')") && auth.includes('throw error'), 'Neuspešan bootstrap posle logina vraća aplikaciju u bezbedno anonymous stanje.');

const endpoints = fs.readFileSync(path.join(root, 'src/lib/api/endpoints.ts'), 'utf8');
const apiTypes = fs.readFileSync(path.join(root, 'src/types/api.ts'), 'utf8');
for (const endpoint of ['auth/token', 'auth/google', 'bootstrap', 'catalog/filters', 'products', 'orders/options', 'Idempotency-Key', 'orders', 'notifications', 'devices', 'me/notification-preferences', 'PATCH']) {
  assert(endpoints.includes(endpoint), `API klijent koristi ${endpoint} ugovor.`);
}

// MOBILE_ASSIGNED_ORDERS_CONTRACT_V05
assert(endpoints.includes('assignedList: (page = 1) =>') && endpoints.includes('orders/assigned${queryString({ page })}') && endpoints.includes('assignedDetail: async (id: number)') && endpoints.includes('orders/assigned/${id}'), 'Order API client exposes Assigned-to-me list/detail contract.');
const assignedOrderEndpointScope = endpoints.slice(endpoints.indexOf('    assignedList:'), endpoints.indexOf('    detail: async (id: number)'));
assert(assignedOrderEndpointScope.includes('PaginatedResponse<Order>') && assignedOrderEndpointScope.includes('apiRequest<{ data: Order }>'), 'Assigned Orders client reuses the canonical Order contract for list/detail.');
assert(!/assigned(?:Cancel|PostCreate|Submit|Complete|Reopen|Verify|Reject|Void|Reassign|Status|Tracking)/.test(assignedOrderEndpointScope), 'Assigned Orders customer/mobile contract adds discovery only and no workflow mutation methods.');
// MOBILE_ORDER_POST_CREATE_CONTRACT_V05
assert(apiTypes.includes('export type OrderPostCreate = {') && apiTypes.includes('export type OrderPaymentLedgerEntry = {') && apiTypes.includes('export type SubmitOrderPaymentProofInput = {'), 'Order post-create API types cover summary, payment ledger and proof upload.');
assert(endpoints.includes('postCreate: async (id: number)') && endpoints.includes('submitPaymentProof: async') && endpoints.includes('paymentProofPath:') && endpoints.includes('confirmationPdfPath:') && endpoints.includes('documentPdfPath:') && endpoints.includes('deliveryProofPath:'), 'Order API client covers post-create summary, proof upload and secure binary path contracts.');
const orderPostCreateTypeScope = apiTypes.slice(apiTypes.indexOf('export type OrderPrivateFile ='), apiTypes.indexOf('export type AfterSalesCaseType ='));
const orderEndpointScope = endpoints.slice(endpoints.indexOf('  orders: {'), endpoints.indexOf('  afterSales: {'));
assert(!/\b(?:submitted_by|verified_by|rejected_by|voided_by|confirmed_by|issued_by|proof_path|proof_disk|payment_batch_id)\b/.test(orderPostCreateTypeScope), 'Order post-create Mobile types do not expose internal actor IDs or storage paths.');
assert(!/\b(?:verifyPayment|rejectPayment|voidPayment|completeOrder|reopenOrder|admin\.)\b/.test(orderEndpointScope), 'Order customer API client does not expose admin payment or delivery workflow actions.');
assert(client.includes('export async function apiDownload') && endpoints.includes('/api/v1/orders/${orderId}/payments/${paymentId}/proof') && endpoints.includes('/api/v1/orders/${orderId}/documents/${documentId}.pdf') && endpoints.includes('/api/v1/orders/${orderId}/delivery-proof'), 'Order private-file paths are prepared for the existing authenticated apiDownload transport.');
// MOBILE_AFTER_SALES_PARITY_V05
const afterSalesListScreen = fs.readFileSync(path.join(root, 'src/app/(app)/after-sales/index.tsx'), 'utf8');
const afterSalesDetailScreen = fs.readFileSync(path.join(root, 'src/app/(app)/after-sales/[id].tsx'), 'utf8');
const afterSalesCreateScreen = fs.readFileSync(path.join(root, 'src/app/(app)/after-sales/create/[orderId].tsx'), 'utf8');
const afterSalesAttachmentPicker = fs.readFileSync(path.join(root, 'src/features/after-sales/attachment-picker.ts'), 'utf8');
const afterSalesAttachmentDownload = fs.readFileSync(path.join(root, 'src/features/after-sales/attachment-download.ts'), 'utf8');
const orderDetailScreen = fs.readFileSync(path.join(root, 'src/app/(app)/order/[id].tsx'), 'utf8');
const orderPostCreateFileHelper = fs.readFileSync(path.join(root, 'src/features/orders/order-post-create-files.ts'), 'utf8');
const ordersScreen = fs.readFileSync(path.join(root, 'src/app/(app)/(tabs)/orders.tsx'), 'utf8');
// MOBILE_ASSIGNED_ORDERS_UI_V05
const assignedOrdersListScreen = fs.readFileSync(path.join(root, 'src/app/(app)/assigned-orders/index.tsx'), 'utf8');
const assignedOrdersDetailScreen = fs.readFileSync(path.join(root, 'src/app/(app)/assigned-orders/[id].tsx'), 'utf8');
assert(ordersScreen.includes("can('orders.manage')") && ordersScreen.includes("router.push('/assigned-orders')") && ordersScreen.includes('Dodeljene meni'), 'Orders ekran otvara Assigned-to-me inbox samo korisniku sa orders.manage dozvolom.');
assert(assignedOrdersListScreen.includes('api.orders.assignedList') && assignedOrdersListScreen.includes("can('orders.manage')") && assignedOrdersListScreen.includes("pathname: '/assigned-orders/[id]'") && assignedOrdersListScreen.includes('useInfiniteQuery') && assignedOrdersListScreen.includes('fetchNextPage'), 'Assigned Orders lista koristi dedicated API, permission gate, detail rutu i server pagination.');
assert(assignedOrdersDetailScreen.includes('api.orders.assignedDetail') && assignedOrdersDetailScreen.includes("can('orders.manage')") && assignedOrdersDetailScreen.includes('order.items') && assignedOrdersDetailScreen.includes('order.shipping') && assignedOrdersDetailScreen.includes('order.supplier'), 'Assigned Order detalj koristi dedicated detail API i prikazuje canonical Order customer/assignment podatke.');
assert(!/api\.orders\.(?:cancel|postCreate|submitPaymentProof)|completeOrder|reopenOrder|verifyPayment|rejectPayment|voidPayment|reassign|internal_note|proof_path|proof_disk/.test(`${assignedOrdersListScreen}\n${assignedOrdersDetailScreen}`), 'Assigned Orders UI ostaje read-only i ne izlaže owner post-create ili admin workflow mutacije/interne storage podatke.');
// MOBILE_ORDER_POST_CREATE_UI_V05
assert(orderDetailScreen.includes('api.orders.postCreate(orderId)') && orderDetailScreen.includes("queryKey: ['order-post-create', orderId]") && orderDetailScreen.includes('postCreate.capabilities.can_view_payments') && orderDetailScreen.includes('postCreate.capabilities.can_view_documents') && orderDetailScreen.includes('postCreate.delivery'), 'Order detalj prikazuje server-driven payment/document/delivery post-create summary.');
assert(orderDetailScreen.includes('api.orders.submitPaymentProof') && orderDetailScreen.includes('pickOrderPaymentProof') && orderDetailScreen.includes('postCreate.payment_proof_limits') && orderDetailScreen.includes('postCreate.capabilities.can_upload_payment_proof'), 'Order detalj šalje payment proof samo kada server capability to dozvoli i koristi server file limite.');
assert(orderPostCreateFileHelper.includes('File.pickFileAsync') && orderPostCreateFileHelper.includes('limits.mime_types') && orderPostCreateFileHelper.includes('limits.extensions') && orderPostCreateFileHelper.includes('limits.max_bytes'), 'Order payment-proof picker koristi postojeći Expo FileSystem i server MIME/extension/size limite.');
assert(orderPostCreateFileHelper.includes('apiDownload') && orderPostCreateFileHelper.includes('Paths.cache') && orderPostCreateFileHelper.includes('response.contentLength !== response.bytes.byteLength') && orderPostCreateFileHelper.includes('file.write(response.bytes)') && orderPostCreateFileHelper.includes('file.size !== response.bytes.byteLength'), 'Order privatni fajlovi koriste Bearer binary transport i provereni privatni cache.');
assert(orderPostCreateFileHelper.includes('hasPdfSignature') && orderPostCreateFileHelper.includes("responseMime !== 'application/pdf'") && orderPostCreateFileHelper.includes("import('expo-sharing')") && orderPostCreateFileHelper.includes('Sharing.isAvailableAsync()') && orderPostCreateFileHelper.includes('Sharing.shareAsync(downloaded.uri') && orderPostCreateFileHelper.includes("Platform.OS === 'web'"), 'Order PDF/proof helper validira PDF i otvara privatne fajlove kroz postojeći Expo Sharing flow.');
assert(orderPostCreateFileHelper.includes('api.orders.paymentProofPath') && orderPostCreateFileHelper.includes('api.orders.confirmationPdfPath') && orderPostCreateFileHelper.includes('api.orders.documentPdfPath') && orderPostCreateFileHelper.includes('api.orders.deliveryProofPath'), 'Order private-file helper prihvata samo tipizovane customer API path buildere.');
assert(orderDetailScreen.includes('openOrderPaymentProof') && orderDetailScreen.includes('openOrderConfirmationPdf') && orderDetailScreen.includes('openOrderDocumentPdf') && orderDetailScreen.includes('openOrderDeliveryProof') && !orderDetailScreen.includes('Linking.openURL'), 'Order detalj ne otvara privatne URL-ove direktno već koristi secure Bearer/cache/share helper.');
assert(orderDetailScreen.includes('api.orders.cancel(orderId)') && orderDetailScreen.includes("can('after_sales.create')") && orderDetailScreen.includes("pathname: '/after-sales/create/[orderId]'"), 'Post-create UI čuva postojeći customer cancel i After-sales create tok.');
assert(!/\b(?:verifyPayment|rejectPayment|voidPayment|completeOrder|reopenOrder|submitted_by|verified_by|rejected_by|voided_by|confirmed_by|issued_by|proof_path|proof_disk|admin\.)\b/.test(`${orderDetailScreen}\n${orderPostCreateFileHelper}`), 'Order customer post-create UI/helper ne izlažu admin akcije, actor ID-jeve ili storage putanje.');
assert(endpoints.includes('afterSales:') && endpoints.includes('after-sales'), 'API klijent sadrži after-sales ugovor.');
assert(afterSalesListScreen.includes('api.afterSales.list') && afterSalesListScreen.includes("can('after_sales.view_own')") && afterSalesListScreen.includes("pathname: '/after-sales/[id]'"), 'After-sales lista koristi API, dozvolu i detalj rutu.');
assert(afterSalesDetailScreen.includes('api.afterSales.detail') && afterSalesDetailScreen.includes("can('after_sales.view_own')") && afterSalesDetailScreen.includes('caseData.actions') && afterSalesDetailScreen.includes('caseData.messages'), 'After-sales detalj prikazuje slučaj, radnje i javnu komunikaciju.');
assert(afterSalesDetailScreen.includes('api.afterSales.message') && afterSalesDetailScreen.includes('caseData.can_message') && afterSalesDetailScreen.includes('caseData.limits.message_max_length') && afterSalesDetailScreen.includes("queryKey: ['after-sales']") && afterSalesDetailScreen.includes('feedback.notify'), 'After-sales detalj podržava slanje javne poruke samo kada je komunikacija otvorena.');
assert(afterSalesCreateScreen.includes('api.afterSales.options') && afterSalesCreateScreen.includes('api.afterSales.create') && afterSalesCreateScreen.includes("can('after_sales.create')") && afterSalesCreateScreen.includes('selectedItems'), 'After-sales create ekran koristi server options, create endpoint, create dozvolu i izabrane stavke.');
assert(afterSalesAttachmentPicker.includes("from 'expo-file-system'") && afterSalesAttachmentPicker.includes('File.pickFileAsync') && afterSalesAttachmentPicker.includes('multipleFiles: true') && afterSalesAttachmentPicker.includes('attachment_mime_types') && afterSalesAttachmentPicker.includes('max_attachment_bytes') && afterSalesAttachmentPicker.includes('max_attachments'), 'After-sales attachment picker koristi Expo FileSystem i server limite bez novog picker paketa.');
assert(client.includes('export async function apiDownload') && client.includes('response.arrayBuffer()') && client.includes("headers.set('Authorization', `Bearer ${token}`)"), 'API klijent podržava autentifikovan binary download uz postojeći Bearer lifecycle.');
assert(afterSalesAttachmentDownload.includes('apiDownload') && afterSalesAttachmentDownload.includes('expectedDownloadPath') && afterSalesAttachmentDownload.includes('Paths.cache') && afterSalesAttachmentDownload.includes('file.write(response.bytes)') && afterSalesAttachmentDownload.includes('file.size !== response.bytes.byteLength'), 'After-sales privatni prilog se preuzima samo kroz očekivanu API putanju i čuva u provereni privatni cache.');
assert(afterSalesAttachmentDownload.includes("import('expo-sharing')") && afterSalesAttachmentDownload.includes('Sharing.isAvailableAsync()') && afterSalesAttachmentDownload.includes('Sharing.shareAsync(downloaded.uri') && afterSalesAttachmentDownload.includes("Platform.OS === 'web'"), 'After-sales privatni prilog koristi Expo Sharing tek nakon provere platforme i dostupnosti sistema.');
assert(afterSalesDetailScreen.includes('openAfterSalesAttachment') && afterSalesDetailScreen.includes('openingAttachmentPath') && afterSalesDetailScreen.includes('Otvori / podeli') && !afterSalesDetailScreen.includes('Linking.openURL'), 'After-sales detalj otvara privatne priloge kroz bezbedan Bearer download umesto direktnog privatnog URL-a.');
const afterSalesActionTypes = apiTypes.slice(apiTypes.indexOf('export type AfterSalesAction ='), apiTypes.indexOf('export type AfterSalesCase ='));
assert(afterSalesActionTypes.includes('attachments: AfterSalesAttachment[];'), 'After-sales work-order tip izlaže javne field-work priloge.');
assert(afterSalesAttachmentDownload.includes('/api/v1/field-work-order-attachments/${attachment.id}') && afterSalesAttachmentDownload.includes('attachmentCachePrefix') && afterSalesAttachmentDownload.includes("? 'field-work'"), 'Secure attachment helper dozvoljava samo očekivanu field-work Bearer putanju i odvaja cache namespace.');
assert(afterSalesDetailScreen.includes('action.work_order.attachments') && afterSalesDetailScreen.includes('action.work_order.planned_start_at') && afterSalesDetailScreen.includes('action.work_order.completion_result') && afterSalesDetailScreen.includes('Otvori ili podeli terenski prilog'), 'After-sales detalj prikazuje javnu terensku dokumentaciju i otvara je kroz postojeći secure flow.');
assert(afterSalesCreateScreen.includes('pickAfterSalesAttachments') && afterSalesCreateScreen.includes('formatAfterSalesAttachmentSize') && afterSalesCreateScreen.includes('attachments: attachments.length ? attachments : undefined') && afterSalesCreateScreen.includes('max_attachments'), 'After-sales create ekran bira, prikazuje i šalje priloge prema server limitima.');
assert(apiTypes.includes("limits: AfterSalesOptions['limits'];"), 'After-sales detail tip izlaže server-driven limite.');
assert(afterSalesDetailScreen.includes('pickAfterSalesAttachments') && afterSalesDetailScreen.includes('messageAttachments') && afterSalesDetailScreen.includes('attachments: messageAttachments.length ? messageAttachments : undefined') && afterSalesDetailScreen.includes('caseData.limits.max_attachments') && afterSalesDetailScreen.includes('caseData.limits.max_attachment_bytes'), 'After-sales message composer bira, prikazuje i šalje priloge prema server limitima.');
assert(orderDetailScreen.includes("can('after_sales.create')") && orderDetailScreen.includes("pathname: '/after-sales/create/[orderId]'"), 'Order detalj otvara create-from-order ekran samo korisniku sa after_sales.create dozvolom.');
assert(ordersScreen.includes("can('after_sales.view_own')") && ordersScreen.includes("router.push('/after-sales')"), 'Orders ekran otvara after-sales listu samo korisniku sa view_own dozvolom.');

// MOBILE_WARRANTIES_CONTRACT_V05
assert(apiTypes.includes('export type WarrantySummary =') && apiTypes.includes('export type Warranty = WarrantySummary &') && apiTypes.includes('WarrantyMaintenanceRecord'), 'Warranty API tipovi pokrivaju listu, detalj i maintenance timeline.');
assert(endpoints.includes('warranties:') && endpoints.includes('PaginatedResponse<WarrantySummary>') && endpoints.includes('apiRequest<{ data: Warranty }>'), 'API klijent sadrži Warranty list/detail ugovor.');
// MOBILE_WARRANTIES_UI_V05
const warrantiesListScreen = fs.readFileSync(path.join(root, 'src/app/(app)/warranties/index.tsx'), 'utf8');
const warrantiesDetailScreen = fs.readFileSync(path.join(root, 'src/app/(app)/warranties/[id].tsx'), 'utf8');
const warrantyPdfHelper = fs.readFileSync(path.join(root, 'src/features/warranties/warranty-pdf.ts'), 'utf8');
assert(warrantiesListScreen.includes('api.warranties.list') && warrantiesListScreen.includes("can('warranties.view_own')") && warrantiesListScreen.includes("pathname: '/warranties/[id]'") && warrantiesListScreen.includes('item.next_maintenance_at'), 'Warranty lista koristi API, permission gate, detail rutu i maintenance summary.');
assert(warrantiesDetailScreen.includes('api.warranties.detail') && warrantiesDetailScreen.includes("can('warranties.view_own')") && warrantiesDetailScreen.includes('warranty.maintenance_records') && warrantiesDetailScreen.includes('warranty.serial_numbers') && warrantiesDetailScreen.includes('warranty.terms') && warrantiesDetailScreen.includes('warranty.void_reason'), 'Warranty detalj prikazuje customer-safe garantni list, uslove, serijske brojeve, status i maintenance timeline.');
// MOBILE_WARRANTIES_PDF_V05
assert(warrantyPdfHelper.includes('apiDownload') && warrantyPdfHelper.includes('`/api/v1/warranties/${warrantyId}.pdf`') && warrantyPdfHelper.includes('Paths.cache') && warrantyPdfHelper.includes("contentType !== 'application/pdf'") && warrantyPdfHelper.includes('file.write(response.bytes)') && warrantyPdfHelper.includes('file.size !== response.bytes.byteLength'), 'Warranty PDF se preuzima Bearer transportom, validira kao PDF i čuva u provereni privatni cache.');
assert(warrantyPdfHelper.includes("import('expo-sharing')") && warrantyPdfHelper.includes('Sharing.isAvailableAsync()') && warrantyPdfHelper.includes('Sharing.shareAsync(downloaded.uri') && warrantyPdfHelper.includes("Platform.OS === 'web'"), 'Warranty PDF koristi postojeći Expo Sharing tek nakon platform/device provere.');
assert(warrantiesDetailScreen.includes('openWarrantyPdf') && warrantiesDetailScreen.includes('openingPdf') && warrantiesDetailScreen.includes('Otvori / podeli PDF') && warrantiesDetailScreen.includes('feedback.notify') && !warrantiesDetailScreen.includes('Linking.openURL'), 'Warranty detalj otvara privatni PDF kroz bezbedan Bearer/cache/share flow bez direktnog URL-a.');
assert(ordersScreen.includes("can('warranties.view_own')") && ordersScreen.includes("router.push('/warranties')"), 'Orders ekran otvara Warranty listu samo korisniku sa warranties.view_own dozvolom.');
// MOBILE_COMMISSIONS_CONTRACT_V05
assert(apiTypes.includes('export type CommissionStatus =') && apiTypes.includes('export type Commission = {') && apiTypes.includes('export type CommissionListResponse = PaginatedResponse<Commission> &'), 'Commission API tipovi pokrivaju customer list/detail, statuse, summary i pagination ugovor.');
assert(endpoints.includes('commissions:') && endpoints.includes('CommissionListParams') && endpoints.includes('apiRequest<CommissionListResponse>') && endpoints.includes('apiRequest<{ data: Commission }>'), 'API klijent sadrži Commission list/filter/detail ugovor.');
const commissionTypeScope = apiTypes.slice(apiTypes.indexOf('export type CommissionStatus ='), apiTypes.indexOf('export type BusinessNotification = {'));
const commissionEndpointScope = endpoints.slice(endpoints.indexOf('  commissions: {'), endpoints.indexOf('  notifications: {'));
assert(!/\b(?:user_id|approved_by|paid_by|cancelled_by|history|payment_batch_id)\b/.test(`${commissionTypeScope}\n${commissionEndpointScope}`), 'Commission Mobile contract ne izlaže admin actor/history/payment-batch interne identifikatore.');
// MOBILE_COMMISSIONS_UI_V05
const commissionsListScreen = fs.readFileSync(path.join(root, 'src/app/(app)/commissions/index.tsx'), 'utf8');
const commissionsDetailScreen = fs.readFileSync(path.join(root, 'src/app/(app)/commissions/[id].tsx'), 'utf8');
assert(commissionsListScreen.includes('api.commissions.list') && commissionsListScreen.includes("can('commissions.view_own')") && commissionsListScreen.includes("pathname: '/commissions/[id]'") && commissionsListScreen.includes('CommissionListParams') && commissionsListScreen.includes('date_from') && commissionsListScreen.includes('date_to') && commissionsListScreen.includes('summary?.pending_eur') && commissionsListScreen.includes('meta?.last_page'), 'Commission lista koristi customer permission, q/status/date filtere, server summary, pagination i detail rutu.');
assert(commissionsDetailScreen.includes('api.commissions.detail') && commissionsDetailScreen.includes("can('commissions.view_own')") && commissionsDetailScreen.includes('commission.responsible_name') && commissionsDetailScreen.includes('commission.status_note') && commissionsDetailScreen.includes('commission.payment') && commissionsDetailScreen.includes("pathname: '/order/[id]'"), 'Commission detalj prikazuje customer-safe obračun, status, napomenu, isplatu i link ka porudžbini.');
assert(ordersScreen.includes("can('commissions.view_own')") && ordersScreen.includes("router.push('/commissions')"), 'Orders ekran otvara Commission listu samo korisniku sa commissions.view_own dozvolom.');
assert(!/\b(?:user_id|approved_by|paid_by|cancelled_by|payment_batch_id|commission_status_history|bulkPay|transition)\b/.test(`${commissionsListScreen}\n${commissionsDetailScreen}`), 'Commission customer UI ne izlaže admin/interne workflow identifikatore ili akcije.');
const cart = fs.readFileSync(path.join(root, 'src/features/cart/cart-provider.tsx'), 'utf8');
assert(cart.includes('productId') && cart.includes('quantity') && !cart.includes('variantId') && !cart.includes('variantName'), 'Lokalna korpa koristi samo proizvod i količinu; variant identitet je dekomisioniran.');
assert(cart.includes("status === 'anonymous'") && cart.includes('setItems([])'), 'Korpa se čisti pri odjavi/promeni korisnika.');

const checkout = fs.readFileSync(path.join(root, 'src/app/(app)/checkout.tsx'), 'utf8');
// PRODUCT_VARIANTS_DECOMMISSION_MOBILE_CONTRACT_V07
const productVariantsDecommissionApiTypes = fs.readFileSync(path.join(root, 'src/types/api.ts'), 'utf8');
const productVariantsDecommissionProductScreen = fs.readFileSync(path.join(root, 'src/app/(app)/product/[slug].tsx'), 'utf8');
const productVariantsDecommissionAdminAfterSales = fs.readFileSync(path.join(root, 'src/features/admin/after-sales-admin-api.ts'), 'utf8');
assert(!productVariantsDecommissionApiTypes.includes('ProductVariant') && !productVariantsDecommissionApiTypes.includes('variants_enabled') && !productVariantsDecommissionApiTypes.includes('product_variant_id') && !productVariantsDecommissionApiTypes.includes('variant_name') && !productVariantsDecommissionApiTypes.includes('variant_attributes'), 'Mobile API tipovi više ne izlažu Product Variants.');
assert(!productVariantsDecommissionProductScreen.includes('variants_enabled') && !productVariantsDecommissionProductScreen.includes('selectedVariant') && !productVariantsDecommissionProductScreen.includes('variantId') && !productVariantsDecommissionProductScreen.includes('variantName'), 'Mobile Product detalj više nema variant izbor.');
assert(!checkout.includes('product_variant_id') && !checkout.includes('variantId'), 'Mobile checkout šalje samo product_id i quantity.');
assert(!productVariantsDecommissionAdminAfterSales.includes('product_variant_id'), 'Admin After-sales Mobile contract više ne izlaže product_variant_id.');
assert(checkout.includes('lastSubmission') && checkout.includes('Crypto.randomUUID'), 'Checkout čuva stabilan idempotency ključ za retry istog payload-a.');
assert(checkout.includes("paymentMethod === 'bank_transfer'") && checkout.includes('bankAccountId'), 'Checkout podržava uslovni izbor računa za bank transfer.');
// MOBILE_V0_8_DEFERRED_PAYMENT_RECEIVABLES_VALIDATOR_BATCH4
assert(apiTypes.includes("'deferred_payment'") && apiTypes.includes('requires_due_date: boolean') && apiTypes.includes('payment_due_at: Nullable<string>'), 'v0.8 Mobile API tipovi pokrivaju Odloženo plaćanje i datum dospeća.');
assert(checkout.includes("paymentMethod !== 'deferred_payment'") && checkout.includes('selectedPayment?.requires_due_date') && checkout.includes('payment_due_at:'), 'v0.8 Checkout prikazuje i šalje datum dospeća samo za Odloženo plaćanje.');

const deviceRegistrar = fs.readFileSync(path.join(root, 'src/features/device/device-registrar.tsx'), 'utf8');
assert(!deviceRegistrar.includes('notifications_enabled: false'), 'Device heartbeat više ne gasi push registraciju pri svakom startu.');

const pushService = fs.readFileSync(path.join(root, 'src/features/notifications/push-service.ts'), 'utf8');
assert(pushService.includes('setNotificationChannelAsync') && pushService.indexOf('setNotificationChannelAsync') < pushService.indexOf('getExpoPushTokenAsync'), 'Android kanal se kreira pre Expo push tokena.');
assert(pushService.includes('getExpoPushTokenAsync') && pushService.includes('projectId'), 'Expo push token koristi EAS projectId.');
assert(pushService.includes("push_provider: 'expo'") && pushService.includes('notifications_enabled: true'), 'Push token se registruje kao Expo device token.');
assert(!pushService.includes('console.log'), 'Push token se ne loguje u klijentu.');

const pushBridge = fs.readFileSync(path.join(root, 'src/features/notifications/push-notification-bridge.tsx'), 'utf8');
assert(pushBridge.includes('addNotificationReceivedListener') && pushBridge.includes('addNotificationResponseReceivedListener'), 'Foreground i tap push listeneri su implementirani.');
assert(pushBridge.includes('clearLastNotificationResponseAsync'), 'Cold-start notification response se čisti nakon obrade.');
assert(pushBridge.includes("pathname: '/order/[id]'"), 'Push order deep link vodi na detalj porudžbine.');

const notificationSettings = fs.readFileSync(path.join(root, 'src/app/(app)/notification-settings.tsx'), 'utf8');
assert(notificationSettings.includes('push_enabled') && notificationSettings.includes('order_updates'), 'Notification settings uređuju push i poslovne kategorije.');
assert(notificationSettings.includes('registerCurrentDeviceForPush') && notificationSettings.includes('disableCurrentDevicePush'), 'Notification settings podržavaju per-device push uključivanje i isključivanje.');


// MOBILE_ACCOUNT_PARITY_V05
const accountScreen = fs.readFileSync(path.join(root, 'src/app/(app)/(tabs)/account.tsx'), 'utf8');
assert(/api\.account\.updateProfile/.test(accountScreen) && /replaceBootstrapUser\s*\(\s*updated\s*\)/.test(accountScreen), 'Account ekran podrzava izmenu profila i lokalno osvezavanje bootstrap korisnika.');
assert(/api\.account\.changePassword/.test(accountScreen) && /requireReauthentication\s*\(\s*\)/.test(accountScreen), 'Account ekran podrzava promenu lozinke i obaveznu ponovnu prijavu.');
assert(/password\s*:\s*z\s*\.string\(\)\s*\.min\(\s*12\s*,/.test(accountScreen), 'Account ekran zahteva najmanje 12 znakova za novu lozinku.');
assert(/password_confirmation/.test(accountScreen) && /path\s*:\s*\[\s*['\"]password_confirmation['\"]\s*\]/.test(accountScreen), 'Account ekran proverava potvrdu nove lozinke.');
const accountEndpoints = fs.readFileSync(path.join(root, 'src/lib/api/endpoints.ts'), 'utf8');
assert(/apiRequest<\{ data: User \}>\(\s*'me'\s*,\s*\{[\s\S]*?method:\s*'PATCH'/.test(accountEndpoints), 'API klijent koristi PATCH /me za profil.');
assert(/apiRequest<AccountPasswordResponse>\(\s*'me\/password'\s*,\s*\{[\s\S]*?method:\s*'PUT'/.test(accountEndpoints), 'API klijent koristi PUT /me/password za lozinku.');

const googleAuth = fs.readFileSync(path.join(root, 'src/features/auth/google-auth.ts'), 'utf8');
assert(googleAuth.includes("webClientId: 'autoDetect'") && googleAuth.includes('getGoogleIdToken'), 'Google Sign-In koristi web client ID iz google-services.json i vraća ID token backendu.');
assert(googleAuth.includes('createAccount') && googleAuth.includes('presentExplicitSignIn'), 'Google login ima saved-account, registration/account-picker i explicit fallback tok.');

const loginScreen = fs.readFileSync(
  path.join(root, 'src/app/(auth)/login.tsx'),
  'utf8'
);
assert(
  loginScreen.includes('colorScheme={scheme}'),
  'Google Sign-In dugme prati aktivnu light/dark temu.'
);

const tabs = fs.readFileSync(path.join(root, 'src/app/(app)/(tabs)/_layout.tsx'), 'utf8');
assert(tabs.includes('focused') && tabs.includes('primaryContainer'), 'Bottom navigation ima Material 3 tonalni aktivni indikator.');
assert(
  tabs.includes('backgroundColor: themeColors.danger') &&
  tabs.includes('color: themeColors.onDanger'),
  'Tab badge koristi semantic danger/onDanger foreground par.'
);
const glyph = fs.readFileSync(path.join(root, 'src/components/ui/glyph.tsx'), 'utf8');
assert(glyph.includes('expo-symbols') && glyph.includes('SymbolView'), 'UI koristi native Expo Symbols umesto tekstualnih pseudo-ikonica.');

const openapi = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');

// MOBILE_CANONICAL_OPENAPI_V05
const canonicalOpenApiPath = path.join(projectRoot, 'packages/api-contract/openapi.yaml');
const canonicalOpenApi = fs.existsSync(canonicalOpenApiPath)
  ? fs.readFileSync(canonicalOpenApiPath, 'utf8')
  : null;
assert(canonicalOpenApi !== null, 'Canonical packages/api-contract/openapi.yaml postoji.');
if (canonicalOpenApi !== null) {
  assert(openapi === canonicalOpenApi, 'Mobile OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.');
}
const cmsOpenApiPath = path.join(projectRoot, 'apps/cms/current/docs/openapi.yaml');
const cmsOpenApi = fs.existsSync(cmsOpenApiPath)
  ? fs.readFileSync(cmsOpenApiPath, 'utf8')
  : null;
assert(cmsOpenApi !== null, 'CMS OpenAPI kopija postoji.');
if (canonicalOpenApi !== null && cmsOpenApi !== null) {
  assert(cmsOpenApi === canonicalOpenApi, 'CMS OpenAPI kopija odgovara canonical packages/api-contract/openapi.yaml.');
}
// MOBILE_ASSIGNED_ORDERS_OPENAPI_V05
assert(openapi.includes('/api/v1/orders/assigned:') && openapi.includes('/api/v1/orders/assigned/{order}:') && openapi.includes('operationId: listAssignedOrders') && openapi.includes('operationId: getAssignedOrder'), 'OpenAPI documents Assigned-to-me list/detail routes.');
const assignedOrdersOpenApiScope = openapi.slice(openapi.indexOf('  /api/v1/orders/assigned:'), openapi.indexOf('  /api/v1/orders/{order}:'));
assert(assignedOrdersOpenApiScope.includes("'403'") && assignedOrdersOpenApiScope.includes("'404'"), 'Assigned Orders OpenAPI documents permission denial and strict detail not-found behavior.');
assert(!/\n\s+(?:post|patch|put|delete):/.test(assignedOrdersOpenApiScope), 'Assigned Orders OpenAPI contains no workflow mutation operations.');
// MOBILE_ORDER_POST_CREATE_OPENAPI_V05
for (const route of ['/api/v1/orders/{order}/post-create:', '/api/v1/orders/{order}/payments/proof:', '/api/v1/orders/{order}/payments/{payment}/proof:', '/api/v1/orders/{order}/documents/confirmation.pdf:', '/api/v1/orders/{order}/documents/{document}.pdf:', '/api/v1/orders/{order}/delivery-proof:']) assert(openapi.includes(route), `OpenAPI contains Order post-create route ${route}.`);
for (const schema of ['OrderPrivateFile:', 'OrderPaymentLedgerEntry:', 'OrderDocumentSummary:', 'OrderDeliverySummary:', 'OrderBankTransferSnapshot:', 'OrderPostCreateCapabilities:', 'OrderPaymentProofLimits:', 'OrderPostCreate:']) assert(openapi.includes(`    ${schema}`), `OpenAPI contains ${schema} schema.`);
const orderPostCreateOpenApiScope = `${openapi.slice(openapi.indexOf('  /api/v1/orders/{order}/post-create:'), openapi.indexOf('  /api/v1/after-sales:'))}\n${openapi.slice(openapi.indexOf('    OrderPrivateFile:'), openapi.indexOf('    AfterSalesCaseSummary:'))}`;
assert(orderPostCreateOpenApiScope.includes('multipart/form-data:') && orderPostCreateOpenApiScope.includes('format: binary') && orderPostCreateOpenApiScope.includes('private, no-store, max-age=0'), 'Order post-create OpenAPI covers proof upload, binary downloads and private no-store cache policy.');
assert(!/\b(?:submitted_by|verified_by|rejected_by|voided_by|confirmed_by|issued_by|proof_path|proof_disk|verifyOrderPayment|rejectOrderPayment|voidOrderPayment|completeOrder|reopenOrder)\b/.test(orderPostCreateOpenApiScope), 'Order post-create OpenAPI does not expose internal actor/storage fields or admin workflow actions.');
// MOBILE_COMMISSIONS_OPENAPI_CONTRACT_V05
assert(openapi.includes('/api/v1/commissions:') && openapi.includes('/api/v1/commissions/{commission}:') && openapi.includes('operationId: listCommissions') && openapi.includes('operationId: getCommission') && openapi.includes('CommissionListResponse:') && openapi.includes('CommissionTotals:') && openapi.includes('    Commission:'), 'OpenAPI dokumentuje Commission list/filter/detail, summary i pagination ugovor.');
const commissionOpenApiScope = `${openapi.slice(openapi.indexOf('  /api/v1/commissions:'), openapi.indexOf('  /api/v1/notifications:'))}\n${openapi.slice(openapi.indexOf('    CommissionPayment:'), openapi.indexOf('    ApiError:'))}`;
assert(!/\b(?:user_id|approved_by|paid_by|cancelled_by|payment_batch_id|commission_status_history)\b/.test(commissionOpenApiScope), 'Commission OpenAPI customer ugovor ne izlaže admin/interne identifikatore.');
// MOBILE_WARRANTIES_OPENAPI_CONTRACT_V05
assert(openapi.includes('/api/v1/warranties:') && openapi.includes('/api/v1/warranties/{warranty}:') && openapi.includes('WarrantySummary:') && openapi.includes('WarrantyMaintenanceRecord:') && openapi.includes('    Warranty:'), 'OpenAPI dokumentuje Warranty list/detail i maintenance schema ugovor.');
assert(openapi.includes('/api/v1/warranties/{warranty}.pdf:') && openapi.includes('operationId: downloadWarrantyPdf') && openapi.includes('application/pdf:') && openapi.includes('format: binary'), 'OpenAPI dokumentuje privatni Warranty PDF Bearer download ugovor.');
const afterSalesCaseSchema = openapi.slice(openapi.indexOf('    AfterSalesCase:'), openapi.indexOf('    AfterSalesOptions:'));
assert(afterSalesCaseSchema.includes('        limits:') && afterSalesCaseSchema.includes('message_max_length: { type: integer }') && afterSalesCaseSchema.includes('max_attachments: { type: integer }') && afterSalesCaseSchema.includes('max_attachment_bytes: { type: integer }') && afterSalesCaseSchema.includes('attachment_mime_types:'), 'OpenAPI AfterSalesCase detalj izlaže server-driven limite za poruke i priloge.');
const afterSalesActionSchema = openapi.slice(openapi.indexOf('    AfterSalesAction:'), openapi.indexOf('    AfterSalesCase:'));
assert(afterSalesActionSchema.includes('        work_order:') && afterSalesActionSchema.includes('                attachments:') && afterSalesActionSchema.includes("items: { $ref: '#/components/schemas/AfterSalesAttachment' }"), 'OpenAPI work-order schema izlaže javne field-work priloge.');
assert(openapi.includes('/api/v1/field-work-order-attachments/{attachment}:') && openapi.includes('operationId: downloadFieldWorkOrderAttachment'), 'OpenAPI field-work attachment ruta dokumentuje Bearer download ugovor.');
for (const route of ['/auth/token', '/auth/google', '/bootstrap', '/catalog/filters', '/products', '/orders/options', 'Idempotency-Key', '/orders', '/notifications', '/devices']) {
  assert(openapi.includes(route), `OpenAPI kopija sadrži ${route}.`);
}

const appConfig = fs.readFileSync(path.join(root, 'app.config.js'), 'utf8');
assert(appConfig.includes("scheme: 'ald1n'"), 'Deep-link scheme je postavljen.');
assert(appConfig.includes('com.ald1n.mobile'), 'Android/iOS identifikatori su postavljeni.');
assert(appConfig.includes('typedRoutes: true'), 'Expo Router typed routes su uključene.');
assert(appConfig.includes('EAS_PROJECT_ID') && appConfig.includes('projectId'), 'Dinamički EAS project ID je podržan.');
assert(appConfig.includes("version: '0.8.0'"), 'Expo app verzija je 0.8.0.');

// MOBILE_BRANDING_ALD1N_CMS_V06
const ald1nBrandSource = fs.readFileSync(path.join(root, 'assets/brand/ald1n-v2-logo.png'));
assert(
  appConfig.includes("name: 'Ald1n CMS'")
    && !appConfig.includes('Ald1n Mobile (${APP_ENV})'),
  'Expo display naziv je Ald1n CMS bez Preview suffixa.',
);
assert(
  [
    'assets/icon.png',
    'assets/adaptive-icon.png',
    'assets/splash-icon.png',
    'assets/favicon.png',
  ].every((relative) => fs.readFileSync(path.join(root, relative)).equals(ald1nBrandSource)),
  'Ald1n V2 logo je canonical icon/adaptive/splash/favicon asset.',
);
const appLayoutVersionSource = fs.readFileSync(path.join(root, 'src/app/(app)/_layout.tsx'), 'utf8');
const accountVersionSource = fs.readFileSync(path.join(root, 'src/app/(app)/(tabs)/account.tsx'), 'utf8');
assert(/nativeApplicationVersion[\s\S]{0,120}\?\?\s*['"]0\.8\.0['"]/.test(appLayoutVersionSource), 'App runtime version fallback je 0.8.0.');
assert(/nativeApplicationVersion[\s\S]{0,120}\?\?\s*['"]0\.8\.0['"]/.test(accountVersionSource), 'Account version fallback je 0.8.0.');
assert(appConfig.includes('google-services.json') && appConfig.includes('googleServicesFile'), 'Android config podržava Firebase google-services.json kada postoji.');
assert(appConfig.includes('react-native-nitro-google-signin'), 'App config uključuje Google Sign-In plugin kada je Firebase config prisutan.');
assert(
  /userInterfaceStyle\s*:\s*['"]automatic['"]/.test(appConfig),
  'Expo userInterfaceStyle prati sistemsku light/dark temu.'
);

const appThemeSource = fs.readFileSync(
  path.join(root, 'src/theme/app-theme.ts'),
  'utf8'
);

assert(
  /appThemeMode\s*:\s*AppThemeMode\s*=\s*['"]system['"]/.test(
    appThemeSource
  ),
  'App theme mode je zakljucan na system.'
);

assert(
  appThemeSource.includes('useColorScheme'),
  'App theme resolver koristi React Native system color scheme.'
);

const rnThemeSource = fs.readFileSync(
  path.join(root, 'src/constants/theme.ts'),
  'utf8'
);

assert(
  rnThemeSource.includes('onDanger: tokens.onDanger'),
  'RN theme adapter koristi canonical onDanger semantic token.'
);

const rootLayoutSource = fs.readFileSync(path.join(root, 'src/app/_layout.tsx'), 'utf8');
assert(
  rootLayoutSource.includes('TamaguiProvider') &&
  rootLayoutSource.includes('tamaguiConfig'),
  'TamaguiProvider je povezan na root aplikacije.'
);

assert(
  rootLayoutSource.includes('defaultTheme={scheme}') &&
  rootLayoutSource.includes("style={isDark ? 'light' : 'dark'}") &&
  rootLayoutSource.includes('backgroundColor: themeColors.background'),
  'Root Tamagui, StatusBar i navigation background prate isti resolved scheme.'
);

const tamaguiSource = fs.readFileSync(path.join(root, 'tamagui.config.ts'), 'utf8');

assert(
  tamaguiSource.includes('@tamagui/config/v5') &&
  tamaguiSource.includes('@tamagui/config/v5-reanimated'),
  'Tamagui Config v5 i Reanimated driver su aktivni.'
);

assert(
  tamaguiSource.includes('ald1nLightPalette') &&
  tamaguiSource.includes('ald1nDarkPalette'),
  'Ald1n Light/Dark Tamagui palette su povezane.'
);

assert(
  tamaguiSource.includes('onDanger: tokens.onDanger'),
  'Tamagui onDanger koristi canonical onDanger semantic token.'
);

// MOBILE_PRODUCT_DESCRIPTION_COPY
const productDescriptionCopyScreen = fs.readFileSync(path.join(root, 'src/app/(app)/product/[slug].tsx'), 'utf8');
const productDescriptionCopyPackage = JSON.parse(fs.readFileSync(path.join(root, 'package.json'), 'utf8'));
assert(
  productDescriptionCopyScreen.includes("from 'expo-clipboard'") &&
  productDescriptionCopyScreen.includes('Clipboard.setStringAsync(product.description)') &&
  productDescriptionCopyScreen.includes('Kopiraj opis artikla') &&
  productDescriptionCopyScreen.includes('Opis kopiran'),
  'Product detail omogućava kopiranje ručno unetog opisa na Android/iOS.'
);
assert(
  productDescriptionCopyPackage.dependencies?.['expo-clipboard'] === '~57.0.1',
  'Mobile ima SDK 57 expo-clipboard zavisnost za kopiranje opisa.'
);
// MOBILE_ADMIN_PRODUCT_CREATE_V06
const adminProductCreateScreenV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/create.tsx'), 'utf8');
const adminProductCreateSelectV06 = fs.readFileSync(path.join(root, 'src/components/ui/select-sheet.tsx'), 'utf8');
const adminProductCreateEndpointsV06 = fs.readFileSync(path.join(root, 'src/lib/api/endpoints.ts'), 'utf8');
const adminProductCreateTypesV06 = fs.readFileSync(path.join(root, 'src/types/api.ts'), 'utf8');
const adminProductCreateHomeV06 = fs.readFileSync(path.join(root, 'src/app/(app)/(tabs)/home.tsx'), 'utf8');
const adminProductCreateOpenApiV06 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
assert(
  adminProductCreateScreenV06.includes("can('catalog.manage_products')")
    && adminProductCreateScreenV06.includes('api.admin.catalog.options')
    && adminProductCreateScreenV06.includes('api.admin.catalog.createProduct'),
  'Admin Product Create ekran koristi catalog.manage_products i canonical admin catalog API.',
);
assert(
  adminProductCreateScreenV06.includes('firstFieldError()')
    && adminProductCreateScreenV06.includes("pathname: '/product/[slug]'"),
  'Admin Product Create prikazuje server validation grešku i posle uspeha otvara novi artikal.',
);
assert(
  adminProductCreateSelectV06.includes('export function SelectSheet')
    && adminProductCreateSelectV06.includes('<Modal'),
  'SelectSheet primitive postoji bez dodatnog native dependency-ja.',
);
assert(
  adminProductCreateEndpointsV06.includes("'admin/catalog/options'")
    && adminProductCreateEndpointsV06.includes("'admin/catalog/products'"),
  'API klijent sadrži Admin Catalog options/create ugovor.',
);
assert(
  adminProductCreateTypesV06.includes('export type AdminCatalogCreateOptions')
    && adminProductCreateTypesV06.includes('export type AdminProductCreateInput')
    && adminProductCreateTypesV06.includes('export type AdminProductCreateResponse'),
  'Mobile tipovi pokrivaju Admin Product Create metadata/input/response.',
);
assert(
  adminProductCreateHomeV06.includes("can('catalog.manage_products')")
    && adminProductCreateHomeV06.includes("route: '/admin/catalog/create'"),
  'Home prikazuje Dodaj artikal samo korisniku sa catalog.manage_products dozvolom.',
);
assert(
  adminProductCreateOpenApiV06.includes('  /api/v1/admin/catalog/options:')
    && adminProductCreateOpenApiV06.includes('  /api/v1/admin/catalog/products:'),
  'OpenAPI dokumentuje Admin Catalog options i product create rute.',
);

// MOBILE_ADMIN_PRODUCT_CREATE_BATCH2_V06
const adminProductCreateBatch2ScreenV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/create.tsx'), 'utf8');
const adminProductCreateBatch2PickerV06 = fs.readFileSync(path.join(root, 'src/features/catalog/product-image-picker.ts'), 'utf8');
const adminProductCreateBatch2EndpointsV06 = fs.readFileSync(path.join(root, 'src/lib/api/endpoints.ts'), 'utf8');
const adminProductCreateBatch2TypesV06 = fs.readFileSync(path.join(root, 'src/types/api.ts'), 'utf8');
const adminProductCreateBatch2OpenApiV06 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
assert(
  adminProductCreateBatch2ScreenV06.includes('standardSpecificationFields.map')
    && adminProductCreateBatch2ScreenV06.includes('selectableOptions(')
    && adminProductCreateBatch2ScreenV06.includes('spec_details'),
  'Admin Product Create renderuje dinamičke specifikacije, zavisne select opcije i detaljna polja.',
);
assert(
  adminProductCreateBatch2ScreenV06.includes("can('catalog.manage_images')")
    && adminProductCreateBatch2ScreenV06.includes('pickProductImages')
    && adminProductCreateBatch2ScreenV06.includes('uploadProductImages'),
  'Admin Product Create fotografije su permission-gated i šalju se kroz canonical image API.',
);
assert(
  adminProductCreateBatch2PickerV06.includes("File.pickFileAsync")
    && adminProductCreateBatch2PickerV06.includes('limits.max_files')
    && adminProductCreateBatch2PickerV06.includes('limits.max_bytes'),
  'Product image picker koristi postojeći Expo FileSystem i server-driven limite bez novog native dependency-ja.',
);
assert(
  adminProductCreateBatch2EndpointsV06.includes("body.append('images[]'")
    && adminProductCreateBatch2EndpointsV06.includes('admin/catalog/products/${productId}/images'),
  'API klijent podržava multipart upload slika posle kreiranja artikla.',
);
assert(
  adminProductCreateBatch2TypesV06.includes('AdminCatalogSpecificationField')
    && adminProductCreateBatch2TypesV06.includes('AdminProductImageLimits')
    && adminProductCreateBatch2TypesV06.includes('spec_structured?'),
  'Mobile tipovi pokrivaju dinamičke specifikacije, image limite i storage contract za sledeći specijalizovani korak.',
);
assert(
  adminProductCreateBatch2OpenApiV06.includes('  /api/v1/admin/catalog/products/{product}/images:')
    && adminProductCreateBatch2OpenApiV06.includes('AdminCatalogSpecificationField')
    && adminProductCreateBatch2OpenApiV06.includes('multipart/form-data:'),
  'OpenAPI dokumentuje napredne spec metadata podatke i multipart product-image upload.',
);

// MOBILE_ADMIN_PRODUCT_CREATE_BATCH2B_V06
const adminProductCreateBatch2BScreenV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/create.tsx'), 'utf8');
const adminProductCreateBatch2BTypesV06 = fs.readFileSync(path.join(root, 'src/types/api.ts'), 'utf8');
const adminProductCreateBatch2BOpenApiV06 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
assert(
  adminProductCreateBatch2BScreenV06.includes('storageFields.map')
    && adminProductCreateBatch2BScreenV06.includes('Dodaj još jedan disk')
    && adminProductCreateBatch2BScreenV06.includes('storageTotal(field.id)')
    && adminProductCreateBatch2BScreenV06.includes('standardSpecificationFields.map'),
  'Admin Product Create ima specijalizovani multi-disk repeater i skriva izvedeni total iz standardnih polja.',
);
const adminProductCreateBatch2BInputMatchV06 = adminProductCreateBatch2BScreenV06.match(
  /const input: AdminProductCreateInput = \{([\s\S]*?)\n\s*\};/
);
const adminProductCreateBatch2BInputBlockV06 = adminProductCreateBatch2BInputMatchV06?.[1] ?? '';
assert(
  adminProductCreateBatch2BInputBlockV06.includes('specs: Object.keys(cleanSpecs)')
    && adminProductCreateBatch2BInputBlockV06.includes('spec_lists: Object.keys(cleanSpecLists)')
    && adminProductCreateBatch2BInputBlockV06.includes('spec_capacities: Object.keys(cleanSpecCapacities)')
    && adminProductCreateBatch2BInputBlockV06.includes('spec_structured: Object.keys(cleanSpecStructured)')
    && !adminProductCreateBatch2BInputBlockV06.includes('storageTotal(')
    && !adminProductCreateBatch2BInputBlockV06.includes('total_field_id'),
  'Storage repeater šalje canonical specs/spec_lists/spec_capacities/spec_structured payload bez ručnog derived total-a.',
);
assert(
  adminProductCreateBatch2BTypesV06.includes('read_only_derived: boolean')
    && adminProductCreateBatch2BTypesV06.includes('storage_repeater: {')
    && adminProductCreateBatch2BTypesV06.includes("capacity_unit: 'GB'"),
  'Mobile tipovi izlažu server-driven storage repeater i read-only derived metadata.',
);
assert(
  adminProductCreateBatch2BOpenApiV06.includes('read_only_derived: { type: boolean }')
    && adminProductCreateBatch2BOpenApiV06.includes('storage_repeater:')
    && adminProductCreateBatch2BOpenApiV06.includes('max_items: { type: integer, enum: [8] }')
    && adminProductCreateBatch2BOpenApiV06.includes('capacity_unit: { type: string, enum: [GB] }'),
  'OpenAPI dokumentuje server-driven storage repeater metadata i derived total polje.',
);
// MOBILE_P2_ADMIN_FOUNDATION_GAP_CLOSE_V06
const p2AdminFoundationIndexV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const p2AdminFoundationAccessV06 = fs.readFileSync(path.join(root, 'src/features/admin/admin-access.ts'), 'utf8');
const p2AdminFoundationApiV06 = fs.readFileSync(path.join(root, 'src/features/admin/admin-api.ts'), 'utf8');
const p2AdminFoundationQueryKeysV06 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const p2AdminFoundationHomeV06 = fs.readFileSync(path.join(root, 'src/app/(app)/(tabs)/home.tsx'), 'utf8');
const p2AdminFoundationOpenApiV06 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');

assert(
  p2AdminFoundationIndexV06.includes('hasAdminAccess({')
    && p2AdminFoundationIndexV06.includes('queryKey: adminQueryKeys.foundation()')
    && p2AdminFoundationIndexV06.includes('queryFn: apiAdmin.foundation'),
  'P2 Admin hub koristi centralni access helper, API i query-key foundation.',
);

assert(
  p2AdminFoundationAccessV06.includes('ADMIN_PERMISSION_SLUGS')
    && p2AdminFoundationAccessV06.includes("ADMIN_ROLE_SLUGS = ['admin', 'superadmin']")
    && p2AdminFoundationAccessV06.includes("'commissions.manage'")
    && p2AdminFoundationAccessV06.includes("'warranties.manage'")
    && p2AdminFoundationAccessV06.includes("'system.health'"),
  'P2 Admin access helper centralizuje administratorske dozvole i admin/superadmin role fallback.',
);

assert(
  p2AdminFoundationApiV06.includes("apiRequest<{ data: AdminFoundation }> ('admin/foundation')")
    && p2AdminFoundationApiV06.includes("api_namespace: '/api/v1/admin'"),
  'P2 Admin API helper koristi canonical /api/v1/admin foundation endpoint.',
);

assert(
  p2AdminFoundationQueryKeysV06.includes("foundation: () => ['admin', 'foundation']")
    && p2AdminFoundationQueryKeysV06.includes("module: (module: AdminModuleKey) => ['admin', module]"),
  'P2 Admin query-key family je centralizovana.',
);

assert(
  p2AdminFoundationHomeV06.includes("from '@/features/admin/admin-access'")
    && p2AdminFoundationHomeV06.includes("route: '/admin' as const"),
  'Home prikazuje centralni Admin entry kroz isti access helper.',
);

assert(
  p2AdminFoundationOpenApiV06.includes('  /api/v1/admin/foundation:')
    && p2AdminFoundationOpenApiV06.includes('    AdminFoundationModule:')
    && p2AdminFoundationOpenApiV06.includes('    AdminFoundation:'),
  'OpenAPI dokumentuje P2 Admin foundation endpoint i schema ugovor.',
);

// MOBILE_P2_SHARED_ADMIN_PRIMITIVES_V06
const p2FilterBar=fs.readFileSync(path.join(root,'src/components/ui/filter-bar.tsx'),'utf8');
const p2DateTime=fs.readFileSync(path.join(root,'src/components/ui/date-time-field.tsx'),'utf8');
const p2Money=fs.readFileSync(path.join(root,'src/components/ui/money-field.tsx'),'utf8');
const p2Lookup=fs.readFileSync(path.join(root,'src/components/ui/async-lookup.tsx'),'utf8');
const p2DataList=fs.readFileSync(path.join(root,'src/components/ui/data-list.tsx'),'utf8');
const p2ActionSheet=fs.readFileSync(path.join(root,'src/components/ui/action-sheet.tsx'),'utf8');
const p2Confirm=fs.readFileSync(path.join(root,'src/components/ui/confirm-action.tsx'),'utf8');
const p2Timeline=fs.readFileSync(path.join(root,'src/components/ui/status-timeline.tsx'),'utf8');
const p2Select=fs.readFileSync(path.join(root,'src/components/ui/select-sheet.tsx'),'utf8');
const p2Feedback=fs.readFileSync(path.join(root,'src/components/ui/app-feedback.tsx'),'utf8');
assert(p2FilterBar.includes('export function FilterBar')&&p2FilterBar.includes('export function FilterChip')&&p2FilterBar.includes('activeCount'),'P2 FilterBar ima chips, active count i clear contract.');
assert(p2DateTime.includes("DateTimeFieldMode = 'date' | 'datetime'")&&p2DateTime.includes('normalizeDateTimeInput')&&p2DateTime.includes('<TextInput'),'P2 DateTimeField je dependency-free kontrolisani date/datetime input.');
assert(p2Money.includes('normalizeMoneyInput')&&p2Money.includes('parseMoneyInput')&&p2Money.includes('currency'),'P2 MoneyField centralizuje decimalni unos i currency prikaz.');
assert(p2Lookup.includes('AsyncLookupOption')&&p2Lookup.includes('export function AsyncLookup')&&p2Lookup.includes('minQueryLength')&&p2Lookup.includes('ActivityIndicator'),'P2 AsyncLookup je server-query friendly lookup bez duplog cache-a.');
assert(p2DataList.includes('export function DataList')&&p2DataList.includes('<FlatList')&&p2DataList.includes('<RefreshControl')&&p2DataList.includes('ListEmptyComponent'),'P2 DataList je mobile-first virtualizovana lista sa refresh i empty state contractom.');
assert(p2ActionSheet.includes('export type SheetAction')&&p2ActionSheet.includes('export function ActionSheet')&&p2ActionSheet.includes('<Modal')&&p2ActionSheet.includes('StyleSheet.absoluteFill'),'P2 ActionSheet koristi dependency-free Modal i aktuelni RN absoluteFill API.');
assert(p2Confirm.includes("from '@/components/ui/action-sheet'")&&p2Confirm.includes("key:'confirm'")&&p2Confirm.includes('onConfirm()'),'P2 ConfirmAction reuse-uje ActionSheet i odvaja confirm/cancel tok.');
assert(p2Timeline.includes('StatusTimelineItem')&&p2Timeline.includes('items.map')&&p2Timeline.includes('resolveToneColor'),'P2 StatusTimeline ima reusable server-driven timeline contract.');
assert(p2Select.includes('export function SelectSheet')&&p2Feedback.includes('useAppFeedback'),'P2 postojeći SelectSheet i AppFeedback ostaju očuvani.');
// MOBILE_P3_COMMISSIONS_ADMIN_V06
const p3CommissionAdminList=fs.readFileSync(path.join(root,'src/app/(app)/admin/commissions/index.tsx'),'utf8');
const p3CommissionAdminDetail=fs.readFileSync(path.join(root,'src/app/(app)/admin/commissions/[id].tsx'),'utf8');
const p3CommissionAdminApi=fs.readFileSync(path.join(root,'src/features/admin/commissions-admin-api.ts'),'utf8');
const p3CommissionAdminExport=fs.readFileSync(path.join(root,'src/features/admin/commissions-admin-export.ts'),'utf8');
const p3CommissionAdminHub=fs.readFileSync(path.join(root,'src/app/(app)/admin/index.tsx'),'utf8');
const p3CommissionAdminKeys=fs.readFileSync(path.join(root,'src/features/admin/admin-query-keys.ts'),'utf8');
assert(p3CommissionAdminApi.includes("admin/commissions")&&p3CommissionAdminApi.includes("method: 'PATCH'")&&p3CommissionAdminApi.includes("admin/commissions/bulk-pay"),'P3 Admin Commissions API klijent pokriva list/detail/status/bulk-pay ugovor.');
assert(p3CommissionAdminApi.includes('admin/commissions.')&&!p3CommissionAdminApi.includes('/api/v1/admin/commissions.')&&p3CommissionAdminApi.includes('queryString(exportParams(params))')&&p3CommissionAdminExport.includes('apiDownload')&&p3CommissionAdminExport.includes('Paths.cache')&&p3CommissionAdminExport.includes("import('expo-sharing')"),'P3 Admin Commissions CSV/PDF koristi relativnu API putanju i postojeći Bearer binary/cache/share flow.');
assert(p3CommissionAdminList.includes("can('commissions.manage')")&&p3CommissionAdminList.includes('FilterBar')&&p3CommissionAdminList.includes('DateTimeField')&&p3CommissionAdminList.includes('bulkPay')&&p3CommissionAdminList.includes('openAdminCommissionExport'),'P3 Admin Commissions lista ima permission gate, filtere, bulk-pay i izvoze.');
assert(p3CommissionAdminDetail.includes("can('commissions.manage')")&&p3CommissionAdminDetail.includes('StatusTimeline')&&p3CommissionAdminDetail.includes('allowed_transitions')&&p3CommissionAdminDetail.includes('apiAdminCommissions.transition'),'P3 Admin Commissions detalj koristi server-driven prelaze i shared timeline.');
assert(p3CommissionAdminHub.includes("router.push('/admin/commissions')")&&p3CommissionAdminHub.includes("can('commissions.manage')"),'P3 Admin hub izlaže Provizije samo commissions.manage korisniku.');
assert(p3CommissionAdminKeys.includes('commissionsList:')&&p3CommissionAdminKeys.includes('commission: (id: number)'),'P3 Admin Commissions query keys su centralizovani.');
assert(openapi.includes('/api/v1/admin/commissions:')&&openapi.includes('/api/v1/admin/commissions/{commission}/status:')&&openapi.includes('/api/v1/admin/commissions/bulk-pay:')&&openapi.includes('/api/v1/admin/commissions.csv:')&&openapi.includes('/api/v1/admin/commissions.pdf:'),'OpenAPI dokumentuje kompletan P3 Admin Commissions route surface.');
assert(openapi.includes('AdminCommissionListResponse:')&&openapi.includes('AdminCommissionTransitionInput:')&&openapi.includes('AdminCommissionBulkPayInput:'),'OpenAPI dokumentuje P3 Admin Commissions schema ugovor.');

// MOBILE_P3_WARRANTIES_ADMIN_CORE_V06
const p3WarrantyAdminApiV06 = fs.readFileSync(path.join(root, 'src/features/admin/warranties-admin-api.ts'), 'utf8');
const p3WarrantyAdminListV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/warranties/index.tsx'), 'utf8');
const p3WarrantyAdminDetailV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/warranties/[id].tsx'), 'utf8');
const p3WarrantyAdminHubV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const p3WarrantyAdminQueryKeysV06 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const p3WarrantyAdminOpenApiV06 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');

assert(
  p3WarrantyAdminApiV06.includes('apiAdminWarranties')
    && p3WarrantyAdminApiV06.includes('scheduleMaintenance')
    && p3WarrantyAdminApiV06.includes('completeMaintenance')
    && p3WarrantyAdminApiV06.includes("method: 'PUT'")
    && p3WarrantyAdminApiV06.includes("method: 'POST'"),
  'P3 Admin Warranties API klijent pokriva list/detail/update/void/maintenance ugovor.',
);

assert(
  p3WarrantyAdminListV06.includes("can('warranties.manage')")
    && p3WarrantyAdminListV06.includes('FilterBar')
    && p3WarrantyAdminListV06.includes('DataList')
    && p3WarrantyAdminListV06.includes("router.push({")
    && p3WarrantyAdminListV06.includes("pathname: '/admin/warranties/[id]'"),
  'P3 Admin Warranties lista ima permission gate, filtere, statistiku i detail rutu.',
);

assert(
  p3WarrantyAdminDetailV06.includes("can('warranties.manage')")
    && p3WarrantyAdminDetailV06.includes('apiAdminWarranties.update')
    && p3WarrantyAdminDetailV06.includes('apiAdminWarranties.void')
    && p3WarrantyAdminDetailV06.includes('apiAdminWarranties.scheduleMaintenance')
    && p3WarrantyAdminDetailV06.includes('apiAdminWarranties.completeMaintenance')
    && p3WarrantyAdminDetailV06.includes('ConfirmAction'),
  'P3 Admin Warranties detalj koristi server-side warranty i maintenance mutacije.',
);

assert(
  p3WarrantyAdminHubV06.includes("can('warranties.manage')")
    && p3WarrantyAdminHubV06.includes("router.push('/admin/warranties')"),
  'P3 Admin hub izlaže Garancije samo warranties.manage korisniku.',
);

assert(
  p3WarrantyAdminQueryKeysV06.includes('warrantiesList:')
    && p3WarrantyAdminQueryKeysV06.includes('warranty: (id: number)'),
  'P3 Admin Warranties query keys su centralizovani.',
);

assert(
  p3WarrantyAdminOpenApiV06.includes('  /api/v1/admin/warranties:')
    && p3WarrantyAdminOpenApiV06.includes('  /api/v1/admin/warranties/{warranty}:')
    && p3WarrantyAdminOpenApiV06.includes('/maintenance/{record}/schedule:')
    && p3WarrantyAdminOpenApiV06.includes('/maintenance/{record}/complete:')
    && p3WarrantyAdminOpenApiV06.includes('    AdminWarrantyListResponse:')
    && p3WarrantyAdminOpenApiV06.includes('    AdminWarrantyUpdateInput:'),
  'OpenAPI dokumentuje P3 Admin Warranties core route i schema ugovor.',
);

// MOBILE_P3_WARRANTIES_ADMIN_BATCH2E_OPENAPI_VALIDATOR_V06
const p3WarrantyRulesBatch2EV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/warranties/rules.tsx'), 'utf8');
const p3WarrantyApiBatch2EV06 = fs.readFileSync(path.join(root, 'src/features/admin/warranties-admin-api.ts'), 'utf8');
const p3WarrantyListBatch2EV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/warranties/index.tsx'), 'utf8');
const p3WarrantyDetailBatch2EV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/warranties/[id].tsx'), 'utf8');
const p3WarrantyPdfBatch2EV06 = fs.readFileSync(path.join(root, 'src/features/warranties/warranty-pdf.ts'), 'utf8');
const p3WarrantyQueryBatch2EV06 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const p3WarrantyOpenApiBatch2EV06 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');

assert(
  p3WarrantyApiBatch2EV06.includes('AdminWarrantyRuleInput')
    && p3WarrantyApiBatch2EV06.includes('AdminWarrantyRulesResponse')
    && p3WarrantyApiBatch2EV06.includes('createRule')
    && p3WarrantyApiBatch2EV06.includes('updateRule')
    && p3WarrantyApiBatch2EV06.includes('backfill: (limit = 500) =>')
    && p3WarrantyApiBatch2EV06.includes('adminPdfPath:')
    && p3WarrantyApiBatch2EV06.includes('admin/warranties/')
    && !p3WarrantyApiBatch2EV06.includes('/api/v1/admin/warranties/'),
  'P3 Admin Warranties 2E zaključava rules/backfill i relativni Admin PDF API ugovor.',
);

assert(
  p3WarrantyRulesBatch2EV06.includes("can('warranties.manage')")
    && p3WarrantyRulesBatch2EV06.includes('AsyncLookup')
    && p3WarrantyRulesBatch2EV06.includes('SelectSheet')
    && p3WarrantyRulesBatch2EV06.includes('ConfirmAction')
    && p3WarrantyRulesBatch2EV06.includes('apiAdminWarranties.createRule')
    && p3WarrantyRulesBatch2EV06.includes('apiAdminWarranties.updateRule')
    && p3WarrantyRulesBatch2EV06.includes('apiAdminWarranties.backfill(500)'),
  'P3 Admin Warranties 2E zaključava Rules UI i Backfill tok.',
);

assert(
  p3WarrantyListBatch2EV06.includes("router.push('/admin/warranties/rules')")
    && p3WarrantyListBatch2EV06.includes('data.capabilities.rules')
    && p3WarrantyDetailBatch2EV06.includes('openAdminWarrantyPdf')
    && p3WarrantyDetailBatch2EV06.includes('Otvori / podeli PDF'),
  'P3 Admin Warranties 2E zaključava Rules navigaciju i Admin PDF UI entry.',
);

assert(
  p3WarrantyPdfBatch2EV06.includes('expectedAdminWarrantyPdfPath')
    && p3WarrantyPdfBatch2EV06.includes('admin/warranties/')
    && !p3WarrantyPdfBatch2EV06.includes('/api/v1/admin/warranties/')
    && p3WarrantyPdfBatch2EV06.includes("contentType !== 'application/pdf'")
    && p3WarrantyPdfBatch2EV06.includes('hasPdfSignature')
    && p3WarrantyPdfBatch2EV06.includes('apiDownload')
    && p3WarrantyPdfBatch2EV06.includes("import('expo-sharing')"),
  'P3 Admin Warranties 2E zaključava secure relativni Admin PDF Bearer/cache/share flow.',
);

assert(
  p3WarrantyQueryBatch2EV06.includes('warrantyRules:')
    && p3WarrantyOpenApiBatch2EV06.includes('/api/v1/admin/warranties/rules:')
    && p3WarrantyOpenApiBatch2EV06.includes('/api/v1/admin/warranties/rules/{rule}:')
    && p3WarrantyOpenApiBatch2EV06.includes('/api/v1/admin/warranties/backfill:')
    && p3WarrantyOpenApiBatch2EV06.includes('/api/v1/admin/warranties/{warranty}.pdf:')
    && p3WarrantyOpenApiBatch2EV06.includes('operationId: listAdminWarrantyRules')
    && p3WarrantyOpenApiBatch2EV06.includes('operationId: createAdminWarrantyRule')
    && p3WarrantyOpenApiBatch2EV06.includes('operationId: updateAdminWarrantyRule')
    && p3WarrantyOpenApiBatch2EV06.includes('operationId: backfillAdminWarranties')
    && p3WarrantyOpenApiBatch2EV06.includes('operationId: downloadAdminWarrantyPdf')
    && p3WarrantyOpenApiBatch2EV06.includes('AdminWarrantyRuleInput:')
    && p3WarrantyOpenApiBatch2EV06.includes('AdminWarrantyRulesResponse:')
    && p3WarrantyOpenApiBatch2EV06.includes('AdminWarrantyBackfillResponse:')
    && p3WarrantyOpenApiBatch2EV06.includes('maximum: 500')
    && p3WarrantyOpenApiBatch2EV06.includes('application/pdf:'),
  'OpenAPI dokumentuje kompletan P3 Admin Warranties Rules/Backfill/Admin PDF ugovor.',
);

// MOBILE_P3_REPORTS_ADMIN_BATCH2G_OPENAPI_VALIDATOR_V06
const p3ReportsApiBatch2GV06 = fs.readFileSync(path.join(root, 'src/features/admin/reports-admin-api.ts'), 'utf8');
const p3ReportsExportBatch2GV06 = fs.readFileSync(path.join(root, 'src/features/admin/reports-admin-export.ts'), 'utf8');
const p3ReportsScreenBatch2GV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/reports/index.tsx'), 'utf8');
const p3ReportsQueryBatch2GV06 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const p3ReportsOpenApiBatch2GV06 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');

assert(
  p3ReportsApiBatch2GV06.includes('AdminManagementReportResponse')
    && p3ReportsApiBatch2GV06.includes('AdminReportScheduleInput')
    && p3ReportsApiBatch2GV06.includes('AdminReportSchedulesResponse')
    && p3ReportsApiBatch2GV06.includes('createSchedule: async (')
    && p3ReportsApiBatch2GV06.includes('updateSchedule: async (')
    && p3ReportsApiBatch2GV06.includes('toggleSchedule: async (')
    && p3ReportsApiBatch2GV06.includes('runSchedule: async (')
    && p3ReportsApiBatch2GV06.includes('deleteSchedule: async (')
    && p3ReportsApiBatch2GV06.includes('retryDelivery: async (')
    && p3ReportsApiBatch2GV06.includes("apiRequest<AdminReportSchedulesResponse> ('admin/report-schedules')")
    && !p3ReportsApiBatch2GV06.includes('/api/v1/admin/report-schedules')
    && !p3ReportsApiBatch2GV06.includes('/api/v1/admin/report-deliveries'),
  'P3 Admin Reports 2G zaključava read/schedule Mobile API ugovor i relativne Admin putanje.',
);

assert(
  p3ReportsExportBatch2GV06.includes('adminReportExportPath')
    && p3ReportsExportBatch2GV06.includes('apiDownload')
    && p3ReportsExportBatch2GV06.includes('hasPdfSignature')
    && p3ReportsExportBatch2GV06.includes("contentType !== 'application/pdf'")
    && p3ReportsExportBatch2GV06.includes("contentType !== 'text/csv'")
    && p3ReportsExportBatch2GV06.includes("import('expo-sharing')")
    && !p3ReportsExportBatch2GV06.includes('/api/v1/admin/reports/'),
  'P3 Admin Reports 2G zaključava secure CSV/PDF Bearer/cache/share export tok.',
);

assert(
  p3ReportsScreenBatch2GV06.includes("can('reports.view')")
    && p3ReportsScreenBatch2GV06.includes("can('reports.manage')")
    && p3ReportsScreenBatch2GV06.includes('ScheduleManagerSection')
    && p3ReportsScreenBatch2GV06.includes('openAdminReportExport')
    && p3ReportsScreenBatch2GV06.includes('apiAdminReports.createSchedule')
    && p3ReportsScreenBatch2GV06.includes('apiAdminReports.updateSchedule')
    && p3ReportsScreenBatch2GV06.includes('apiAdminReports.toggleSchedule')
    && p3ReportsScreenBatch2GV06.includes('apiAdminReports.runSchedule')
    && p3ReportsScreenBatch2GV06.includes('apiAdminReports.deleteSchedule')
    && p3ReportsScreenBatch2GV06.includes('apiAdminReports.retryDelivery'),
  'P3 Admin Reports 2G zaključava management dashboard, permission gate i schedule manager UI.',
);

assert(
  p3ReportsQueryBatch2GV06.includes("reports: () => ['admin', 'reports'] as const")
    && p3ReportsQueryBatch2GV06.includes("managementReport: (params: unknown) => ['admin', 'reports', 'management', params] as const")
    && p3ReportsQueryBatch2GV06.includes("reportSchedules: () => ['admin', 'reports', 'schedules'] as const"),
  'P3 Admin Reports 2G zaključava centralizovane Reports query-key ugovore.',
);

assert(
  p3ReportsOpenApiBatch2GV06.includes('/api/v1/admin/reports/management:')
    && p3ReportsOpenApiBatch2GV06.includes('/api/v1/admin/reports/management.csv:')
    && p3ReportsOpenApiBatch2GV06.includes('/api/v1/admin/reports/management.pdf:')
    && p3ReportsOpenApiBatch2GV06.includes('/api/v1/admin/report-schedules:')
    && p3ReportsOpenApiBatch2GV06.includes('/api/v1/admin/report-schedules/{schedule}:')
    && p3ReportsOpenApiBatch2GV06.includes('/api/v1/admin/report-schedules/{schedule}/toggle:')
    && p3ReportsOpenApiBatch2GV06.includes('/api/v1/admin/report-schedules/{schedule}/run:')
    && p3ReportsOpenApiBatch2GV06.includes('/api/v1/admin/report-deliveries/{delivery}/retry:')
    && p3ReportsOpenApiBatch2GV06.includes('operationId: getAdminManagementReport')
    && p3ReportsOpenApiBatch2GV06.includes('operationId: downloadAdminManagementReportCsv')
    && p3ReportsOpenApiBatch2GV06.includes('operationId: downloadAdminManagementReportPdf')
    && p3ReportsOpenApiBatch2GV06.includes('operationId: listAdminReportSchedules')
    && p3ReportsOpenApiBatch2GV06.includes('operationId: createAdminReportSchedule')
    && p3ReportsOpenApiBatch2GV06.includes('operationId: updateAdminReportSchedule')
    && p3ReportsOpenApiBatch2GV06.includes('operationId: deleteAdminReportSchedule')
    && p3ReportsOpenApiBatch2GV06.includes('operationId: toggleAdminReportSchedule')
    && p3ReportsOpenApiBatch2GV06.includes('operationId: runAdminReportSchedule')
    && p3ReportsOpenApiBatch2GV06.includes('operationId: retryAdminReportDelivery')
    && p3ReportsOpenApiBatch2GV06.includes('AdminManagementReportResponse:')
    && p3ReportsOpenApiBatch2GV06.includes('AdminReportSchedulesResponse:')
    && p3ReportsOpenApiBatch2GV06.includes('AdminReportScheduleInput:')
    && p3ReportsOpenApiBatch2GV06.includes('AdminReportDelivery:')
    && p3ReportsOpenApiBatch2GV06.includes('private, no-store, max-age=0')
    && p3ReportsOpenApiBatch2GV06.includes('text/csv:')
    && p3ReportsOpenApiBatch2GV06.includes('application/pdf:'),
  'OpenAPI dokumentuje kompletan P3 Admin Reports read/export/schedule ugovor od 10 operacija.',
);

// MOBILE_P3_SYSTEM_HEALTH_ADMIN_BATCH2C_OPENAPI_VALIDATOR_V06
const p3SystemHealthApiBatch2CV06 = fs.readFileSync(path.join(root, 'src/features/admin/system-health-admin-api.ts'), 'utf8');
const p3SystemHealthScreenBatch2CV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/system-health/index.tsx'), 'utf8');
const p3SystemHealthHubBatch2CV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const p3SystemHealthQueryBatch2CV06 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const p3SystemHealthOpenApiBatch2CV06 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');

assert(
  p3SystemHealthApiBatch2CV06.includes('AdminSystemHealthResponse')
    && p3SystemHealthApiBatch2CV06.includes("apiRequest<AdminSystemHealthResponse> ('admin/system-health')")
    && !p3SystemHealthApiBatch2CV06.includes('/api/v1/admin/system-health')
    && !p3SystemHealthApiBatch2CV06.includes('snapshot(')
    && !p3SystemHealthApiBatch2CV06.includes('backup(')
    && !p3SystemHealthApiBatch2CV06.includes('prune('),
  'P3 Admin System Health 2C zaključava read-only Mobile API ugovor i relativnu admin/system-health putanju.',
);

assert(
  p3SystemHealthQueryBatch2CV06.includes("systemHealth: () => ['admin', 'system-health'] as const"),
  'P3 Admin System Health 2C zaključava centralizovani System Health query key.',
);

assert(
  p3SystemHealthScreenBatch2CV06.includes("can('system.health')")
    && p3SystemHealthScreenBatch2CV06.includes('apiAdminSystemHealth.current()')
    && p3SystemHealthScreenBatch2CV06.includes('adminQueryKeys.systemHealth()')
    && p3SystemHealthScreenBatch2CV06.includes('response.capabilities.refresh')
    && p3SystemHealthScreenBatch2CV06.includes('query.refetch()')
    && p3SystemHealthScreenBatch2CV06.includes('Istorija stanja')
    && p3SystemHealthScreenBatch2CV06.includes('Read-only pristup')
    && !p3SystemHealthScreenBatch2CV06.includes('snapshot(')
    && !p3SystemHealthScreenBatch2CV06.includes('backup(')
    && !p3SystemHealthScreenBatch2CV06.includes('prune('),
  'P3 Admin System Health 2C zaključava permission-gated read-only UI, refresh, checks, metrics i history tok.',
);

assert(
  p3SystemHealthHubBatch2CV06.includes("can('system.health')")
    && p3SystemHealthHubBatch2CV06.includes("router.push('/admin/system-health')")
    && p3SystemHealthHubBatch2CV06.includes('Zdravlje sistema'),
  'P3 Admin System Health 2C zaključava Admin hub ulaz samo za system.health.',
);

assert(
  p3SystemHealthOpenApiBatch2CV06.includes('/api/v1/admin/system-health:')
    && p3SystemHealthOpenApiBatch2CV06.includes('operationId: getAdminSystemHealth')
    && p3SystemHealthOpenApiBatch2CV06.includes('AdminSystemHealthCurrent:')
    && p3SystemHealthOpenApiBatch2CV06.includes('AdminSystemHealthHistoryItem:')
    && p3SystemHealthOpenApiBatch2CV06.includes('AdminSystemHealthCapabilities:')
    && p3SystemHealthOpenApiBatch2CV06.includes('AdminSystemHealthResponse:')
    && p3SystemHealthOpenApiBatch2CV06.includes('Nedovoljna dozvola system.health')
    && !p3SystemHealthOpenApiBatch2CV06.includes('/api/v1/admin/system-health/run:')
    && !p3SystemHealthOpenApiBatch2CV06.includes('/api/v1/admin/system-health/backup:')
    && !p3SystemHealthOpenApiBatch2CV06.includes('/api/v1/admin/system-health/prune:'),
  'OpenAPI dokumentuje samo read-only P3 Admin System Health GET ugovor bez snapshot/backup/prune mutacija.',
);

// MOBILE_P3_AUDIT_ADMIN_BATCH2C_OPENAPI_VALIDATOR_V06
const p3AuditApiBatch2CV06 = fs.readFileSync(path.join(root, 'src/features/admin/audit-admin-api.ts'), 'utf8');
const p3AuditListBatch2CV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/audit/index.tsx'), 'utf8');
const p3AuditDetailBatch2CV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/audit/[id].tsx'), 'utf8');
const p3AuditHubBatch2CV06 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const p3AuditQueryBatch2CV06 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const p3AuditOpenApiBatch2CV06 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
const p3AuditDirectFetchBatch2CV06 = /(?<![\w.])fetch\s*\(/;
const p3AuditRawUserAgentFieldBatch2CV06 = /(^|[^A-Za-z0-9_])user_agent\s*:/m;
const p3AuditRawContextFieldBatch2CV06 = /(^|[^A-Za-z0-9_])context_json\s*:/m;

assert(
  p3AuditApiBatch2CV06.includes('AdminAuditListResponse')
    && p3AuditApiBatch2CV06.includes('AdminAuditDetailResponse')
    && p3AuditApiBatch2CV06.includes('`admin/audit-events${requestQuery(params)}`')
    && p3AuditApiBatch2CV06.includes('apiRequest<AdminAuditDetailResponse> (`admin/audit-events/${eventId}`)')
    && !p3AuditApiBatch2CV06.includes('/api/v1/admin/audit-events')
    && !p3AuditDirectFetchBatch2CV06.test(p3AuditApiBatch2CV06)
    && !p3AuditApiBatch2CV06.includes('globalThis.fetch')
    && !p3AuditApiBatch2CV06.includes('window.fetch')
    && !p3AuditRawUserAgentFieldBatch2CV06.test(p3AuditApiBatch2CV06)
    && !p3AuditRawContextFieldBatch2CV06.test(p3AuditApiBatch2CV06),
  'P3 Admin Audit 2C zakljucava relativni read-only Mobile API ugovor bez raw user_agent/context_json polja.',
);

assert(
  p3AuditQueryBatch2CV06.includes("auditEvents: (params: unknown) => ['admin', 'audit-events', params] as const")
    && p3AuditQueryBatch2CV06.includes("auditEvent: (eventId: number) => ['admin', 'audit-events', eventId] as const"),
  'P3 Admin Audit 2C zakljucava centralizovane Audit list/detail query key ugovore.',
);

assert(
  p3AuditListBatch2CV06.includes("can('security.view')")
    && p3AuditListBatch2CV06.includes('apiAdminAuditEvents.list(applied)')
    && p3AuditListBatch2CV06.includes('adminQueryKeys.auditEvents(applied)')
    && p3AuditListBatch2CV06.includes('Akcija / dogadjaj')
    && p3AuditListBatch2CV06.includes('DateTimeField')
    && p3AuditListBatch2CV06.includes('PER_PAGE_OPTIONS')
    && p3AuditListBatch2CV06.includes('query.refetch()')
    && p3AuditListBatch2CV06.includes('Read-only pristup')
    && !p3AuditDirectFetchBatch2CV06.test(p3AuditListBatch2CV06),
  'P3 Admin Audit 2C zakljucava security.view list/filter/pagination/refetch read-only UI.',
);

assert(
  p3AuditDetailBatch2CV06.includes("can('security.view')")
    && p3AuditDetailBatch2CV06.includes('apiAdminAuditEvents.detail(eventId)')
    && p3AuditDetailBatch2CV06.includes('adminQueryKeys.auditEvent(eventId)')
    && p3AuditDetailBatch2CV06.includes('Sanitizovani kontekst')
    && p3AuditDetailBatch2CV06.includes('response.capabilities.export')
    && p3AuditDetailBatch2CV06.includes('response.capabilities.mutate')
    && !p3AuditDirectFetchBatch2CV06.test(p3AuditDetailBatch2CV06),
  'P3 Admin Audit 2C zakljucava permission-gated safe detail UI i server-driven read-only capabilities.',
);

assert(
  p3AuditHubBatch2CV06.includes("can('security.view')")
    && p3AuditHubBatch2CV06.includes("router.push('/admin/audit')")
    && p3AuditHubBatch2CV06.includes('Audit i bezbednost'),
  'P3 Admin Audit 2C zakljucava Admin hub ulaz samo za security.view.',
);

assert(
  p3AuditOpenApiBatch2CV06.includes('/api/v1/admin/audit-events:')
    && p3AuditOpenApiBatch2CV06.includes('/api/v1/admin/audit-events/{event}:')
    && p3AuditOpenApiBatch2CV06.includes('operationId: listAdminAuditEvents')
    && p3AuditOpenApiBatch2CV06.includes('operationId: getAdminAuditEvent')
    && p3AuditOpenApiBatch2CV06.includes('AdminAuditEventSummary:')
    && p3AuditOpenApiBatch2CV06.includes('AdminAuditEventDetail:')
    && p3AuditOpenApiBatch2CV06.includes('AdminAuditSafeContext:')
    && p3AuditOpenApiBatch2CV06.includes('AdminAuditEventsResponse:')
    && p3AuditOpenApiBatch2CV06.includes('AdminAuditEventDetailResponse:')
    && p3AuditOpenApiBatch2CV06.includes('Nedovoljna dozvola security.view')
    && !p3AuditOpenApiBatch2CV06.includes('/api/v1/admin/audit-events.csv:')
    && !p3AuditOpenApiBatch2CV06.includes('/api/v1/admin/audit-events/export:'),
  'OpenAPI dokumentuje samo P3 Admin Audit read/filter list i safe detail ugovor bez export/mutation ruta.',
);

// MOBILE_ADMIN_PRODUCT_IMAGES_EXPO_FILE_TRANSPORT_V06
const adminProductImageTransportClientV06 = fs.readFileSync(path.join(root, 'src/lib/api/client.ts'), 'utf8');
const adminProductImageTransportEndpointsV06 = fs.readFileSync(path.join(root, 'src/lib/api/endpoints.ts'), 'utf8');
assert(
  adminProductImageTransportClientV06.includes("import { fetch as expoFetch } from 'expo/fetch';")
    && adminProductImageTransportClientV06.includes('export async function apiExpoMultipartRequest')
    && adminProductImageTransportClientV06.includes('const response = await expoFetch('),
  'Product image upload koristi eksplicitni Expo fetch transport sa postojecim auth/error lifecycle-om.',
);
const adminProductImageUploadStartV06 = adminProductImageTransportEndpointsV06.indexOf('uploadProductImages:');
const adminProductImageUploadEndV06 = adminProductImageTransportEndpointsV06.indexOf('\n      },', adminProductImageUploadStartV06);
const adminProductImageUploadScopeV06 = adminProductImageUploadStartV06 >= 0 && adminProductImageUploadEndV06 > adminProductImageUploadStartV06
  ? adminProductImageTransportEndpointsV06.slice(adminProductImageUploadStartV06, adminProductImageUploadEndV06)
  : '';
assert(
  adminProductImageTransportEndpointsV06.includes("import { File } from 'expo-file-system';")
    && adminProductImageUploadScopeV06.includes("body.append('images[]', new File(file.uri));")
    && adminProductImageUploadScopeV06.includes('apiExpoMultipartRequest')
    && !adminProductImageUploadScopeV06.includes('uri: file.uri'),
  'Product image multipart koristi pravi Expo File umesto legacy uri/name/type pseudo-fajla.',
);
assert(
  !adminProductImageTransportClientV06.includes("headers.set('Content-Type', 'multipart/form-data')")
    && !adminProductImageUploadScopeV06.includes('Content-Type'),
  'Product image multipart ne postavlja rucno Content-Type boundary.',
);

// MOBILE_PRODUCT_IMAGE_ANDROID_EXTENSIONLESS_PICKER_V06
assert(
  adminProductCreateBatch2PickerV06.includes("const mimeAllowed = limits.mime_types.includes(mimeType);")
    && adminProductCreateBatch2PickerV06.includes("const extensionAllowed = extension === '' || limits.extensions.includes(extension);")
    && adminProductCreateBatch2PickerV06.includes('if (!mimeAllowed || !extensionAllowed)'),
  'Product image picker prihvata Android image provider fajl bez ekstenzije kada je MIME dozvoljen, uz zadrzan MIME/extension guard za ostale fajlove.',
);
// MOBILE_IOS_GOOGLE_NATIVE_CONFIG_V06
const iosGoogleConfigAppV06 = fs.readFileSync(path.join(root, 'app.config.js'), 'utf8');
const iosGoogleConfigPlistPathV06 = path.join(root, 'GoogleService-Info.plist');
const iosGoogleConfigPlistV06 = fs.existsSync(iosGoogleConfigPlistPathV06)
  ? fs.readFileSync(iosGoogleConfigPlistPathV06, 'utf8')
  : '';

assert(
  iosGoogleConfigAppV06.includes("const IOS_GOOGLE_SERVICES_FILE = './GoogleService-Info.plist';")
    && iosGoogleConfigAppV06.includes("googleServicesFile: IOS_GOOGLE_SERVICES_FILE")
    && iosGoogleConfigAppV06.includes("iosGoogleServicesFile: IOS_GOOGLE_SERVICES_FILE"),
  'iOS Google Sign-In koristi canonical GoogleService-Info.plist kroz Expo i Nitro config plugin.',
);

assert(
  iosGoogleConfigPlistV06.includes('<key>BUNDLE_ID</key>')
    && iosGoogleConfigPlistV06.includes('<string>com.ald1n.mobile.preview</string>')
    && iosGoogleConfigPlistV06.includes('<key>CLIENT_ID</key>')
    && iosGoogleConfigPlistV06.includes('<key>REVERSED_CLIENT_ID</key>')
    && iosGoogleConfigPlistV06.includes('<key>WEB_CLIENT_ID</key>'),
  'iOS GoogleService-Info.plist sadrži preview bundle, iOS OAuth, reversed scheme i web client ID za autoDetect.',
);

// MOBILE_V0_6_IOS_OPAQUE_ICON_V1
const iosOpaqueIconAppV06 = fs.readFileSync(path.join(root, 'app.config.js'), 'utf8');
const iosOpaqueIconPathV06 = path.join(root, 'assets', 'icon-ios.png');
const iosOpaqueIconBufferV06 = fs.existsSync(iosOpaqueIconPathV06)
  ? fs.readFileSync(iosOpaqueIconPathV06)
  : null;

let iosOpaqueIconContractV06 = false;

if (iosOpaqueIconBufferV06 && iosOpaqueIconBufferV06.length >= 33) {
  const pngSignatureV06 = Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]);
  const isPngV06 = iosOpaqueIconBufferV06.subarray(0, 8).equals(pngSignatureV06);
  const widthV06 = isPngV06 ? iosOpaqueIconBufferV06.readUInt32BE(16) : 0;
  const heightV06 = isPngV06 ? iosOpaqueIconBufferV06.readUInt32BE(20) : 0;
  const bitDepthV06 = isPngV06 ? iosOpaqueIconBufferV06[24] : 0;
  const colorTypeV06 = isPngV06 ? iosOpaqueIconBufferV06[25] : -1;

  let offsetV06 = 8;
  let hasTrnsV06 = false;

  if (isPngV06) {
    while (offsetV06 + 12 <= iosOpaqueIconBufferV06.length) {
      const lengthV06 = iosOpaqueIconBufferV06.readUInt32BE(offsetV06);
      const typeV06 = iosOpaqueIconBufferV06.subarray(offsetV06 + 4, offsetV06 + 8).toString('ascii');
      if (typeV06 === 'tRNS') hasTrnsV06 = true;
      offsetV06 += 12 + lengthV06;
      if (typeV06 === 'IEND') break;
    }
  }

  iosOpaqueIconContractV06 =
    iosOpaqueIconAppV06.includes("icon: './assets/icon-ios.png'")
    && widthV06 === 1024
    && heightV06 === 1024
    && bitDepthV06 === 8
    && colorTypeV06 === 2
    && !hasTrnsV06;
}

assert(
  iosOpaqueIconContractV06,
  'iOS koristi zaseban 1024x1024 opaque RGB app icon bez alpha/tRNS transparentnosti.',
);

// MOBILE_V0_7_RELEASE_CRITICAL_COMMISSION_VISIBILITY
const releaseCommissionHomeV07 = fs.readFileSync(path.join(root, 'src/app/(app)/(tabs)/home.tsx'), 'utf8');
const releaseCommissionOrdersV07 = fs.readFileSync(path.join(root, 'src/app/(app)/(tabs)/orders.tsx'), 'utf8');
const releaseCommissionAdminHubV07 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const releaseCommissionCustomerListV07 = fs.readFileSync(path.join(root, 'src/app/(app)/commissions/index.tsx'), 'utf8');
const releaseCommissionCustomerDetailV07 = fs.readFileSync(path.join(root, 'src/app/(app)/commissions/[id].tsx'), 'utf8');
const releaseCommissionAdminListV07 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/commissions/index.tsx'), 'utf8');
const releaseCommissionAdminDetailV07 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/commissions/[id].tsx'), 'utf8');
const releaseCommissionAdminApiV07 = fs.readFileSync(path.join(root, 'src/features/admin/commissions-admin-api.ts'), 'utf8');
const releaseCommissionAdminExportV07 = fs.readFileSync(path.join(root, 'src/features/admin/commissions-admin-export.ts'), 'utf8');
const releaseCommissionOpenApiV07 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
assert(
  releaseCommissionHomeV07.includes('// MOBILE_V0_7_COMMISSION_RELEASE_CRITICAL_VISIBILITY')
    && releaseCommissionHomeV07.includes("can('commissions.manage')")
    && releaseCommissionHomeV07.includes("can('commissions.view_own')")
    && releaseCommissionHomeV07.includes("route: '/admin/commissions'")
    && releaseCommissionHomeV07.includes("route: '/commissions'"),
  'v0.7 Home izlaže release-critical Provizije odmah kroz manage/view-own permission model.',
);
assert(
  releaseCommissionHomeV07.indexOf("route: '/admin/catalog/create'") < releaseCommissionHomeV07.indexOf("route: '/admin/commissions'"),
  'v0.7 Home prioritet zadržava Dodaj artikal pre Provizija.',
);
assert(
  releaseCommissionAdminHubV07.includes('// MOBILE_V0_7_ADMIN_HUB_COMMISSION_PRIORITY')
    && releaseCommissionAdminHubV07.indexOf("{can('catalog.manage_products') ? (") < releaseCommissionAdminHubV07.indexOf("{can('commissions.manage') ? (")
    && releaseCommissionAdminHubV07.indexOf("{can('commissions.manage') ? (") < releaseCommissionAdminHubV07.indexOf("{can('orders.manage') ? (")
    && releaseCommissionAdminHubV07.includes("router.push('/admin/catalog/create')")
    && releaseCommissionAdminHubV07.includes("router.push('/admin/commissions')")
    && releaseCommissionAdminHubV07.includes("router.push('/admin/orders')")
    && releaseCommissionAdminHubV07.includes('Provizije'),
  'v0.7 Admin Hub drži Provizije kao drugu prioritetnu akciju odmah posle Dodaj artikal.',
);
assert(
  releaseCommissionOrdersV07.includes("can('commissions.view_own')")
    && releaseCommissionOrdersV07.includes("router.push('/commissions')")
    && releaseCommissionCustomerListV07.includes("can('commissions.view_own')")
    && releaseCommissionCustomerDetailV07.includes("can('commissions.view_own')"),
  'v0.7 korisničke Moje provizije ostaju dostupne kroz view-own list/detail tok.',
);
assert(
  releaseCommissionAdminListV07.includes("can('commissions.manage')")
    && releaseCommissionAdminListV07.includes('bulkPay')
    && releaseCommissionAdminListV07.includes('openAdminCommissionExport')
    && releaseCommissionAdminDetailV07.includes("can('commissions.manage')")
    && releaseCommissionAdminDetailV07.includes('allowed_transitions'),
  'v0.7 Admin Provizije zadržavaju list/detail/bulk-pay/export/status workflow.',
);
assert(
  releaseCommissionAdminApiV07.includes('admin/commissions/bulk-pay')
    && releaseCommissionAdminApiV07.includes("method: 'PATCH'")
    && releaseCommissionAdminExportV07.includes('apiDownload'),
  'v0.7 Provizije koriste postojeći Admin API i secure export bez paralelne logike.',
);
assert(
  releaseCommissionOpenApiV07.includes('  /api/v1/admin/commissions:')
    && releaseCommissionOpenApiV07.includes('  /api/v1/admin/commissions/{commission}:')
    && releaseCommissionOpenApiV07.includes('  /api/v1/admin/commissions/{commission}/status:')
    && releaseCommissionOpenApiV07.includes('  /api/v1/admin/commissions/bulk-pay:')
    && releaseCommissionOpenApiV07.includes('  /api/v1/admin/commissions.csv:')
    && releaseCommissionOpenApiV07.includes('  /api/v1/admin/commissions.pdf:'),
  'v0.7 release-critical Provizije ostaju vezane za kompletan canonical Admin OpenAPI surface.',
);

// MOBILE_V0_7_COMMISSION_PERCENTAGE_POLICY
const commissionPercentagePolicyOpenApiV07 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
const commissionPercentagePolicyProductCreateV07 = fs.readFileSync(
  path.join(root, 'src/app/(app)/admin/catalog/create.tsx'),
  'utf8',
);
assert(
  commissionPercentagePolicyOpenApiV07.includes('at least 10% of the product value converted to EUR')
    && commissionPercentagePolicyOpenApiV07.includes('automatic commission is 10% and remains capped at 50 EUR')
    && commissionPercentagePolicyProductCreateV07.includes('MOBILE_V0_7_COMMISSION_PERCENTAGE_POLICY')
    && commissionPercentagePolicyProductCreateV07.includes('Prazno polje koristi automatskih 10% vrednosti artikla'),
  'v0.7 Commission contract uklanja fiksni minimum 20 EUR i dokumentuje podrazumevanih 10 procenata u Product Create toku.',
);
console.log(`\nUkupno FAIL: ${failures}`);
process.exit(failures === 0 ? 0 : 1);


// MOBILE_GLOBAL_REPEATABLE_ACTIONS_PRODUCT_CREATE_UX_V07
const globalRepeatableButtonV07 = fs.readFileSync(path.join(root, 'src/components/ui/button.tsx'), 'utf8');
const globalRepeatableProductCreateV07 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/create.tsx'), 'utf8');
const globalRepeatableProductImagePickerV07 = fs.readFileSync(path.join(root, 'src/features/catalog/product-image-picker.ts'), 'utf8');
assert(
  globalRepeatableButtonV07.includes('MOBILE_GLOBAL_REPEATABLE_ACTIONS_V07')
    && globalRepeatableButtonV07.includes('const isDisabled = Boolean(disabled);')
    && globalRepeatableButtonV07.includes('const pressHandler = onPress;')
    && globalRepeatableButtonV07.includes('MOBILE_GLOBAL_UNRESTRICTED_TAPS_V07')
    && !globalRepeatableButtonV07.includes('loading ? undefined : onPress')
    && globalRepeatableButtonV07.includes('accessibilityState={{ disabled: isDisabled, busy: loading }}')
    && !globalRepeatableButtonV07.includes('disabled || loading'),
  'Globalni Button tretira loading kao vizuelni busy state i ne guta ponovljene tapove.',
);
assert(
  globalRepeatableProductCreateV07.includes('MOBILE_PRODUCT_CREATE_REPEATABLE_ACTIONS_DRAFT_PRESERVATION_V07')
    && globalRepeatableProductCreateV07.includes('setImages((current) => [...current, ...result.files]);')
    && globalRepeatableProductCreateV07.includes('disabled={images.length >= options.image_limits.max_files}')
    && globalRepeatableProductCreateV07.includes('Sve što si uneo ostaje u formi.')
    && globalRepeatableProductCreateV07.includes('Uneti podaci i izabrane fotografije ostaju u formi.'),
  'Admin Product Create dozvoljava ponovljene image-picker sesije i čuva draft nakon validacione greške.',
);
assert(
  globalRepeatableProductImagePickerV07.includes('multipleFiles: true')
    && globalRepeatableProductImagePickerV07.includes('limits.max_files - current.length')
    && globalRepeatableProductImagePickerV07.includes('current.map(imageKey)'),
  'Product image picker podržava više uzastopnih Android izbora uz akumulaciju i deduplikaciju.',
);


// MOBILE_GLOBAL_UNRESTRICTED_TAPS_PRODUCT_CREATE_DRAFT_V07
const unrestrictedTapButtonV07 = fs.readFileSync(path.join(root, 'src/components/ui/button.tsx'), 'utf8');
const unrestrictedTapCreateV07 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/create.tsx'), 'utf8');
assert(
  unrestrictedTapButtonV07.includes('MOBILE_GLOBAL_UNRESTRICTED_TAPS_V07')
    && unrestrictedTapButtonV07.includes('const pressHandler = onPress;')
    && !unrestrictedTapButtonV07.includes('loading ? undefined : onPress'),
  'Globalni Button prosleđuje svaki tap i loading/pending ne blokira onPress.',
);
assert(
  unrestrictedTapCreateV07.includes('MOBILE_PRODUCT_CREATE_PERSISTENT_DRAFT_RETRY_V07')
    && unrestrictedTapCreateV07.includes('setImages((current) => [...current, ...result.files]);')
    && unrestrictedTapCreateV07.includes('Sve što si uneo ostaje u formi.')
    && unrestrictedTapCreateV07.includes('Uneti podaci i izabrane fotografije ostaju u formi.')
    && !/if\s*\(\s*pickingImages\s*\)\s*return/.test(unrestrictedTapCreateV07)
    && !/if\s*\(\s*mutation\.isPending\s*\)\s*return/.test(unrestrictedTapCreateV07),
  'Admin Product Create čuva draft i dozvoljava ponovljene image/submit pokušaje bez globalnog one-shot guarda.',
);


// MOBILE_ADMIN_PRODUCT_CREATE_TYPE_SCOPED_TAXONOMY_V07
const scopedTaxonomyCreateV07 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/create.tsx'), 'utf8');
const scopedTaxonomyTypesV07 = fs.readFileSync(path.join(root, 'src/types/api.ts'), 'utf8');
const scopedTaxonomyOpenApiV07 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
assert(
  scopedTaxonomyTypesV07.includes('product_type_ids: number[];')
    && scopedTaxonomyCreateV07.includes('MOBILE_PRODUCT_CREATE_TYPE_SCOPED_TAXONOMY_V07')
    && scopedTaxonomyCreateV07.includes('brand.product_type_ids.includes(selectedProductTypeId)')
    && scopedTaxonomyCreateV07.includes('line.product_type_ids.includes(selectedProductTypeId)')
    && scopedTaxonomyCreateV07.includes("setBrandId('');")
    && scopedTaxonomyCreateV07.includes("setLineId('');"),
  'Admin Product Create prikazuje samo brendove i linije povezane sa izabranim tipom artikla.',
);
assert(
  scopedTaxonomyOpenApiV07.includes('AdminCatalogBrandOption:')
    && scopedTaxonomyOpenApiV07.includes('AdminCatalogLineOption:')
    && scopedTaxonomyOpenApiV07.includes('product_type_ids:'),
  'OpenAPI dokumentuje type-scoped brand i product-line metadata.',
);
