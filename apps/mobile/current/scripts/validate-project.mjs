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
  'src/app/(auth)/forgot-password.tsx', 'src/app/(auth)/reset-password.tsx', 'src/app/(auth)/activate-account.tsx',
  'src/app/(app)/(tabs)/home.tsx', 'src/app/(app)/(tabs)/catalog.tsx',
  'src/app/(app)/(tabs)/orders.tsx', 'src/app/(app)/(tabs)/notifications.tsx',
  'src/app/(app)/(tabs)/account.tsx', 'src/app/(app)/product/[slug].tsx',
  'src/app/(app)/order/[id].tsx', 'src/app/(app)/devices.tsx', 'src/app/(app)/sessions.tsx',
  'src/app/(app)/portal/messages/index.tsx', 'src/app/(app)/portal/messages/[id].tsx',
  'src/app/(app)/admin/customer-portal/index.tsx', 'src/app/(app)/admin/customer-portal/[userId].tsx',
  'src/app/(app)/admin/customer-portal/conversations/[id].tsx',
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
  'src/features/admin/dictionary-admin-api.ts',
  'src/app/(app)/admin/catalog/dictionaries/index.tsx',
  'src/app/(app)/admin/catalog/dictionaries/[resource].tsx',
  'src/app/(app)/admin/catalog/dictionaries/product-types/[id].tsx',
  'src/features/admin/order-documents-admin.tsx',
  'src/features/admin/order-document-files.ts',
  'src/features/admin/product-deletion-admin.tsx',
  'src/features/admin/audit-admin-export.ts',
  'src/features/admin/module-settings-admin-api.ts',
  'src/features/portal/portal-api.ts',
  'src/features/admin/customer-portal-admin-api.ts',
  'src/features/admin/user-groups-admin-api.ts',
  'src/app/(app)/admin/user-groups/index.tsx',
  'src/features/admin/catalog-advanced-admin-api.ts',
  'src/features/admin/catalog-advanced-product-actions.tsx',
  'src/features/catalog/catalog-product-edit-handoff.ts',
  'src/features/admin/data-quality-admin-api.ts',
  'src/features/admin/data-quality-admin-export.ts',
  'src/app/(app)/admin/catalog/[id]/clone.tsx',
  'src/app/(app)/admin/catalog/bulk/index.tsx',
  'src/app/(app)/admin/catalog/data-quality/index.tsx',
  'src/features/admin/order-archive-admin.tsx',
  'src/app/(app)/admin/orders/archived.tsx',
  'src/features/admin/operational-reports-admin-export.ts',
  'src/features/admin/global-search-admin-api.ts',
  'src/app/(app)/admin/search.tsx',
  'src/app/(app)/admin/settings/modules/index.tsx',
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
assert(packageJson.dependencies?.expo === '~57.0.17' && packageLockJson.packages?.['']?.dependencies?.expo === '~57.0.17' && packageLockJson.packages?.['node_modules/expo']?.version === '57.0.17', 'Expo SDK 57 verzija prati aktuelni SDK 57 patch baseline.');
assert(packageJson.dependencies?.['react-native'] === '0.86.3' && packageLockJson.packages?.['']?.dependencies?.['react-native'] === '0.86.3' && packageLockJson.packages?.['node_modules/react-native']?.version === '0.86.3', 'React Native verzija prati Expo SDK 57 template.');
assert(packageJson.dependencies?.['expo-router'] === '~57.0.17' && packageLockJson.packages?.['']?.dependencies?.['expo-router'] === '~57.0.17' && packageLockJson.packages?.['node_modules/expo-router']?.version === '57.0.17', 'Expo Router verzija je zaključana.');
assert(packageJson.dependencies?.['expo-dev-client'] === '~57.0.16' && packageLockJson.packages?.['']?.dependencies?.['expo-dev-client'] === '~57.0.16' && packageLockJson.packages?.['node_modules/expo-dev-client']?.version === '57.0.16', 'Expo development client je uključen.');
assert(Boolean(packageJson.dependencies?.['expo-secure-store']), 'SecureStore zavisnost postoji.');
assert(Boolean(packageJson.dependencies?.['@tanstack/react-query']), 'TanStack Query zavisnost postoji.');
assert(packageJson.engines?.node === '>=22.13.0', 'Minimalna Node.js verzija odgovara SDK 57 zahtevu.');
// MOBILE_RELEASE_VERSION_V07
// MOBILE_RELEASE_VERSION_V09
// MOBILE_V1_0_RELEASE_METADATA_LOCK_BATCH40
assert(packageJson.version === '1.0.0', 'Aplikaciona package verzija je 1.0.0.');
assert(packageLockJson.version === '1.0.0' && packageLockJson.packages?.['']?.version === '1.0.0', 'package-lock release verzija je 1.0.0.');
assert(packageJson.dependencies?.['expo-notifications'] === '~57.0.15' && packageLockJson.packages?.['']?.dependencies?.['expo-notifications'] === '~57.0.15' && packageLockJson.packages?.['node_modules/expo-notifications']?.version === '57.0.15', 'expo-notifications prati SDK 57 preporučenu verziju.');
assert(packageJson.dependencies?.['expo-symbols'] === '~57.0.2', 'Expo Symbols je uključen za native Material/SF ikonice.');
assert(packageJson.dependencies?.['react-native-nitro-google-signin'] === '1.0.2', 'Moderni Google Credential Manager bridge je uključen.');
assert(packageJson.dependencies?.['react-native-nitro-modules'] === '0.36.1', 'Nitro Modules runtime je pinovan.');
assert(packageJson.dependencies?.tamagui === '2.6.0', 'Tamagui 2 runtime je pinovan.');
assert(packageJson.dependencies?.['@tamagui/config'] === '2.6.0', 'Tamagui Config v5 paket je pinovan.');
assert(packageJson.dependencies?.['@tamagui/animations-reanimated'] === '2.6.0', 'Tamagui Reanimated driver je pinovan.');
assert(packageJson.dependencies?.['expo-system-ui'] === '~57.0.3' && packageLockJson.packages?.['']?.dependencies?.['expo-system-ui'] === '~57.0.3' && packageLockJson.packages?.['node_modules/expo-system-ui']?.version === '57.0.3', 'Expo System UI prati SDK 57 preporucenu verziju.');
assert(packageJson.dependencies?.['expo-status-bar'] === '~57.0.1', 'Expo Status Bar prati SDK 57 preporucenu verziju.');
assert(packageJson.dependencies?.['expo-file-system'] === '~57.0.6' && packageLockJson.packages?.['']?.dependencies?.['expo-file-system'] === '~57.0.6' && packageLockJson.packages?.['node_modules/expo-file-system']?.version === '57.0.6', 'Expo FileSystem je direktno zakljucan za after-sales izbor priloga.');
assert(packageJson.dependencies?.['expo-sharing'] === '~57.0.16' && packageLockJson.packages?.['']?.dependencies?.['expo-sharing'] === '~57.0.16' && packageLockJson.packages?.['node_modules/expo-sharing']?.version === '57.0.16', 'Expo Sharing je zakljucan za bezbedno otvaranje privatnih after-sales priloga.');

// MOBILE_V0_8_EXPO_SDK57_COMPATIBILITY_MATRIX
// MOBILE_V1_0_EXPO_SDK57_PATCH_ALIGNMENT_BATCH21A_V3
// MOBILE_V1_0_EXPO_SDK57_PATCH_ALIGNMENT_BATCH45_V5
const expoCompatibilityMatrixV08 = {
  expo: { spec: '~57.0.17', version: '57.0.17' },
  'expo-constants': { spec: '~57.0.15', version: '57.0.15' },
  'expo-crypto': { spec: '~57.0.2', version: '57.0.2' },
  'expo-dev-client': { spec: '~57.0.16', version: '57.0.16' },
  'expo-file-system': { spec: '~57.0.6', version: '57.0.6' },
  'expo-linking': { spec: '~57.0.8', version: '57.0.8' },
  'expo-notifications': { spec: '~57.0.15', version: '57.0.15' },
  'expo-router': { spec: '~57.0.17', version: '57.0.17' },
  'expo-sharing': { spec: '~57.0.16', version: '57.0.16' },
  'expo-secure-store': { spec: '~57.0.2', version: '57.0.2' },
  'expo-system-ui': { spec: '~57.0.3', version: '57.0.3' },
  'expo-splash-screen': { spec: '~57.0.8', version: '57.0.8' },
  'expo-updates': { spec: '~57.0.18', version: '57.0.18' },
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
{
  const fs = await import('node:fs');
  const path = await import('node:path');
  const ordersV09 = fs.readFileSync(path.join(process.cwd(), 'src/app/(app)/(tabs)/orders.tsx'), 'utf8');
  assert(ordersV09.includes("const assignedOrdersAllowed = can('orders.manage');") && ordersV09.includes("router.push('/assigned-orders')") && ordersV09.includes('Dodeljene porudžbine'), 'v0.9 Orders ekran otvara Dodeljene porudžbine samo korisniku sa orders.manage dozvolom.');
}
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
assert(!ordersScreen.includes("router.push('/after-sales')"), 'v0.9 After-sales prečica je uklonjena iz Porudžbina i premeštena u Moje aktivnosti.');

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
assert(!ordersScreen.includes("router.push('/warranties')"), 'v0.9 Warranty prečica je uklonjena iz Porudžbina i premeštena u Moje aktivnosti.');
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
assert(!ordersScreen.includes("router.push('/commissions')"), 'v0.9 Commission prečica je uklonjena iz Porudžbina i premeštena u Moje aktivnosti.');
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
// MOBILE_V1_0_BOTTOM_TAB_ACTIVE_STATE_POLISH_VALIDATOR_BATCH48
const appBottomNav = fs.readFileSync(path.join(root, 'src/components/layout/app-bottom-nav.tsx'), 'utf8');
assert(
  appBottomNav.includes('MOBILE_V1_0_BOTTOM_TAB_ACTIVE_STATE_POLISH_BATCH48')
    && appBottomNav.includes('!center && focused ? styles.itemActive : null')
    && appBottomNav.includes('center && focused ? styles.homeCircleActive : null')
    && appBottomNav.includes('backgroundColor: theme.primaryContainer')
    && appBottomNav.includes('borderColor: theme.primary')
    && appBottomNav.includes('(focused ? theme.onPrimary : theme.onPrimaryContainer)')
    && appBottomNav.includes('(focused ? theme.onPrimaryContainer : theme.muted)')
    && appBottomNav.includes('color: theme.onPrimaryContainer')
    && !appBottomNav.includes('focused ? styles.iconWrapActive : null'),
  'v1.0 Bottom navigation aktivni TAB koristi puni tonalni pill indikator za ikonicu i naziv.',
);
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
assert(appConfig.includes("version: '1.0.0'"), 'Expo app verzija je 1.0.0.');

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
assert(/nativeApplicationVersion[\s\S]{0,120}\?\?\s*['"]1\.0\.0['"]/.test(appLayoutVersionSource), 'App runtime version fallback je 1.0.0.');
assert(/nativeApplicationVersion[\s\S]{0,120}\?\?\s*['"]1\.0\.0['"]/.test(accountVersionSource), 'Account version fallback je 1.0.0.');
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
// MOBILE_V0_8_SHARED_PRODUCT_IMAGE_MANAGER_VALIDATOR_V2
const adminProductCreateImageManagerV08 = fs.readFileSync(
  path.join(root, 'src/features/catalog/product-image-manager.tsx'),
  'utf8',
);
assert(
  adminProductCreateBatch2ScreenV06.includes("can('catalog.manage_images')")
    && adminProductCreateBatch2ScreenV06.includes('uploadProductImages')
    && adminProductCreateBatch2ScreenV06.includes('DraftProductImageManager')
    && adminProductCreateImageManagerV08.includes('pickProductImages')
    && adminProductCreateImageManagerV08.includes('limits.max_files')
    && adminProductCreateImageManagerV08.includes('limits.max_bytes'),
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
// MOBILE_V0_8_SUPERADMIN_DIRECT_SALE_BATCH10
const directSaleScreenV08 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/[id]/direct-sale.tsx'), 'utf8');
const directSaleEditV08 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/[id].tsx'), 'utf8');
const directSaleCatalogApiV08 = fs.readFileSync(path.join(root, 'src/features/admin/catalog-admin-api.ts'), 'utf8');
const directSaleOpenApiV08 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
assert(
  directSaleEditV08.includes('Evidentiraj prodaju')
    && directSaleEditV08.includes("bootstrap?.user.role?.slug === 'superadmin'")
    && directSaleEditV08.includes("pathname: '/admin/catalog/[id]/direct-sale'"),
  'v0.8 Product Edit izlaže SuperAdmin Evidentiraj prodaju direktno sa artikla.',
);
assert(
  directSaleScreenV08.includes('apiAdminCatalog.directSaleOptions')
    && directSaleScreenV08.includes('apiAdminCatalog.recordDirectSale')
    && directSaleScreenV08.includes('idempotency_key: idempotencyKey')
    && directSaleScreenV08.includes('MOBILE_GLOBAL_UNRESTRICTED_TAPS_V07')
    && !directSaleScreenV08.includes('disabled={saleMutation.isPending}'),
  'v0.8 Direct Sale ekran koristi server options, stable idempotency i unrestricted tap contract.',
);
assert(
  directSaleCatalogApiV08.includes('directSaleOptions: async (productId: number)')
    && directSaleCatalogApiV08.includes('recordDirectSale: (productId: number, input: AdminDirectSaleInput)')
    && directSaleCatalogApiV08.includes('/direct-sale/options')
    && directSaleCatalogApiV08.includes('/direct-sale'),
  'v0.8 Admin Catalog API klijent pokriva Direct Sale options i record ugovor.',
);
assert(
  directSaleOpenApiV08.includes('  /api/v1/admin/catalog/products/{product}/direct-sale/options:')
    && directSaleOpenApiV08.includes('  /api/v1/admin/catalog/products/{product}/direct-sale:')
    && directSaleOpenApiV08.includes('AdminDirectSaleOptionsEnvelope')
    && directSaleOpenApiV08.includes('AdminDirectSaleResponse'),
  'OpenAPI dokumentuje SuperAdmin Direct Sale options/record i idempotency ugovor.',
);
// MOBILE_V1_0_DIRECT_SALE_UNBOUNDED_PRICE_BATCH21
const directSaleServiceV10 = fs.readFileSync(
  path.resolve(root, '../../cms/current/app/Services/DirectSaleService.php'),
  'utf8',
);
assert(
  directSaleServiceV10.includes('MOBILE_V1_0_DIRECT_SALE_UNBOUNDED_PRICE_BATCH21')
    && directSaleServiceV10.includes("if ($salePrice <= 0)")
    && !directSaleServiceV10.includes('DIRECT_SALE_MAX_UNIT_PRICE_GUARD')
    && !directSaleServiceV10.includes('assertSalePriceWithinCatalogUnitPrice')
    && !directSaleServiceV10.includes('Prodajna cena po komadu ne sme biti veća od zadate cene artikla')
    && directSaleScreenV08.includes('MOBILE_V1_0_DIRECT_SALE_UNBOUNDED_PRICE_BATCH21')
    && !directSaleScreenV08.includes('price > options.product.catalog_unit_price_rsd')
    && !directSaleScreenV08.includes('Cena može biti niža, ali ne može biti viša')
    && directSaleScreenV08.includes('zadata kataloška cena služi samo kao referenca')
    && directSaleOpenApiV08.includes('referentna kataloška RSD cena')
    && !directSaleOpenApiV08.includes('maksimalna RSD cena')
    && !directSaleOpenApiV08.includes('maksimalna prodajna cena'),
  'v1.0 Direct Sale dozvoljava cenu iznad kataloške uz pozitivnu cenu i SuperAdmin workflow.',
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
    && p3SystemHealthApiBatch2CV06.includes('run: () =>')
    && p3SystemHealthApiBatch2CV06.includes('backup: (databaseOnly = false) =>')
    && p3SystemHealthApiBatch2CV06.includes('prune: () =>'),
  'P3/v1.0 Admin System Health koristi relativni API ugovor i izlaže run/backup/prune mutacije kroz canonical servisni tok.',
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
    && p3SystemHealthScreenBatch2CV06.includes('Istorija System Health provera')
    && p3SystemHealthScreenBatch2CV06.includes('Privatni backup')
    && p3SystemHealthScreenBatch2CV06.includes('Pokreni proveru i sačuvaj snapshot')
    && p3SystemHealthScreenBatch2CV06.includes('Kreiraj kompletan backup')
    && p3SystemHealthScreenBatch2CV06.includes('Primeni retention')
    && p3SystemHealthScreenBatch2CV06.includes('ConfirmAction'),
  'P3/v1.0 Admin System Health UI ostaje permission-gated i dodaje snapshot, backup, retention, backup istoriju i security događaje.',
);
{
  const fs = await import('node:fs');
  const path = await import('node:path');
  const adminV09 = fs.readFileSync(path.join(process.cwd(), 'src/app/(app)/admin/index.tsx'), 'utf8');
  assert(adminV09.includes('MOBILE_V0_9_GROUPED_ADMIN_HUB_BATCH5C') && adminV09.includes("can('system.health')") && adminV09.includes("router.push('/admin/system-health')") && adminV09.includes('System Health'), 'v0.9 Admin Hub drži System Health u grupi Sistem samo kroz system.health dozvolu.');
}

assert(
  p3SystemHealthOpenApiBatch2CV06.includes('/api/v1/admin/system-health:')
    && p3SystemHealthOpenApiBatch2CV06.includes('operationId: getAdminSystemHealth')
    && p3SystemHealthOpenApiBatch2CV06.includes('AdminSystemHealthCurrent:')
    && p3SystemHealthOpenApiBatch2CV06.includes('AdminSystemHealthHistoryItem:')
    && p3SystemHealthOpenApiBatch2CV06.includes('AdminSystemHealthCapabilities:')
    && p3SystemHealthOpenApiBatch2CV06.includes('AdminSystemHealthResponse:')
    && p3SystemHealthOpenApiBatch2CV06.includes('Nedovoljna dozvola system.health')
    && p3SystemHealthOpenApiBatch2CV06.includes('/api/v1/admin/system-health/run:')
    && p3SystemHealthOpenApiBatch2CV06.includes('/api/v1/admin/system-health/backup:')
    && p3SystemHealthOpenApiBatch2CV06.includes('/api/v1/admin/system-health/prune:'),
  'OpenAPI dokumentuje puni v1.0 Admin System Health GET/run/backup/prune ugovor.',
);

// MOBILE_P3_AUDIT_ADMIN_BATCH2C_OPENAPI_VALIDATOR_V06
const p3AuditApiBatch2CV06 = fs.readFileSync(path.join(root, 'src/features/admin/audit-admin-api.ts'), 'utf8');
const p3AuditExportBatch29V10 = fs.readFileSync(path.join(root, 'src/features/admin/audit-admin-export.ts'), 'utf8');
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
    && p3AuditApiBatch2CV06.includes('`admin/audit-events.csv${exportQuery(params)}`')
    && !p3AuditApiBatch2CV06.includes('/api/v1/admin/audit-events')
    && !p3AuditDirectFetchBatch2CV06.test(p3AuditApiBatch2CV06)
    && !p3AuditApiBatch2CV06.includes('globalThis.fetch')
    && !p3AuditApiBatch2CV06.includes('window.fetch')
    && !p3AuditRawUserAgentFieldBatch2CV06.test(p3AuditApiBatch2CV06)
    && !p3AuditRawContextFieldBatch2CV06.test(p3AuditApiBatch2CV06),
  'P3/v1.0 Admin Audit zaključava relativni read-only list/detail/CSV Mobile API ugovor bez raw user_agent/context_json polja.',
);

assert(
  p3AuditExportBatch29V10.includes('apiDownload(apiAdminAuditEvents.exportPath(params))')
    && p3AuditExportBatch29V10.includes("contentType !== 'text/csv'")
    && p3AuditExportBatch29V10.includes('new File(Paths.cache')
    && p3AuditExportBatch29V10.includes("import('expo-sharing')")
    && p3AuditExportBatch29V10.includes("mimeType: 'text/csv'")
    && !p3AuditDirectFetchBatch2CV06.test(p3AuditExportBatch29V10),
  'v1.0 AUDIT-01 CSV koristi postojeći Bearer binary transport, privatni cache i Expo Sharing bez paralelnog fetch toka.',
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
    && p3AuditListBatch2CV06.includes('openAdminAuditExport(applied)')
    && p3AuditListBatch2CV06.includes('response.capabilities.export')
    && p3AuditListBatch2CV06.includes('Izvezi CSV')
    && p3AuditListBatch2CV06.includes('Read-only pristup')
    && !p3AuditDirectFetchBatch2CV06.test(p3AuditListBatch2CV06),
  'P3/v1.0 Admin Audit zaključava security.view list/filter/pagination/refetch UI i server-driven audit.export CSV akciju.',
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
    && p3AuditOpenApiBatch2CV06.includes('/api/v1/admin/audit-events.csv:')
    && p3AuditOpenApiBatch2CV06.includes('operationId: downloadAdminAuditEventsCsv')
    && p3AuditOpenApiBatch2CV06.includes('Potrebne su security.view i audit.export dozvole')
    && p3AuditOpenApiBatch2CV06.includes('text/csv:'),
  'OpenAPI dokumentuje kompletan AUDIT-01 read/filter/detail + sanitizovani CSV export ugovor bez mutacija.',
);

// MOBILE_V1_0_SET_01_MODULE_SETTINGS_PARITY_BATCH30
const set01ApiBatch30V10 = fs.readFileSync(path.join(root, 'src/features/admin/module-settings-admin-api.ts'), 'utf8');
const set01ScreenBatch30V10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/settings/modules/index.tsx'), 'utf8');
const set01HubBatch30V10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const set01QueryBatch30V10 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const set01OpenApiBatch30V10 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');

assert(
  set01ApiBatch30V10.includes("apiRequest('admin/settings/modules') as Promise<AdminModuleSettingsResponse>")
    && set01ApiBatch30V10.includes("method: 'PUT'")
    && set01ApiBatch30V10.includes('body: { modules }')
    && !set01ApiBatch30V10.includes('/api/v1/admin/settings/modules'),
  'v1.0 SET-01 Mobile API koristi relativni canonical GET/PUT module settings ugovor.',
);
assert(
  set01ScreenBatch30V10.includes("bootstrap?.user.role?.slug === 'superadmin'")
    && set01ScreenBatch30V10.includes("can('system.manage_settings')")
    && set01ScreenBatch30V10.includes('<Switch')
    && set01ScreenBatch30V10.includes('apiAdminModuleSettings.update(payload)')
    && set01ScreenBatch30V10.includes('refreshBootstrap()')
    && set01ScreenBatch30V10.includes('adminQueryKeys.foundation()')
    && set01ScreenBatch30V10.includes('Isključivanje ne briše podatke'),
  'v1.0 SET-01 ekran je SuperAdmin-only, server-driven i osvežava bootstrap/foundation bez destruktivnog ponašanja.',
);
assert(
  set01HubBatch30V10.includes("router.push('/admin/settings/modules' as Href)")
    && set01HubBatch30V10.includes("moduleEnabled('commissions')")
    && set01HubBatch30V10.includes("moduleEnabled('inventory')")
    && set01HubBatch30V10.includes("moduleEnabled('system_health')")
    && set01HubBatch30V10.includes("moduleEnabled('audit')"),
  'v1.0 SET-01 Admin Hub poštuje server module visibility i izlaže Moduli sistema u organizovanoj Sistem grupi.',
);
assert(
  set01QueryBatch30V10.includes("moduleSettings: () => ['admin', 'settings', 'modules'] as const"),
  'v1.0 SET-01 TanStack query key je centralizovan.',
);
assert(
  set01OpenApiBatch30V10.includes('/api/v1/admin/settings/modules:')
    && set01OpenApiBatch30V10.includes('operationId: getAdminModuleSettings')
    && set01OpenApiBatch30V10.includes('operationId: updateAdminModuleSettings')
    && set01OpenApiBatch30V10.includes('AdminModuleSettingsState:')
    && set01OpenApiBatch30V10.includes('SuperAdministratoru sa system.manage_settings'),
  'OpenAPI dokumentuje kompletan SET-01 read/update ugovor i SuperAdmin permission granicu.',
);

// MOBILE_V1_0_AUTH_ACCOUNT_SECURITY_PARITY_BATCH32
const authAccountEndpointsBatch32 = fs.readFileSync(path.join(root, 'src/lib/api/endpoints.ts'), 'utf8');
const authAccountLoginBatch32 = fs.readFileSync(path.join(root, 'src/app/(auth)/login.tsx'), 'utf8');
const authForgotBatch32 = fs.readFileSync(path.join(root, 'src/app/(auth)/forgot-password.tsx'), 'utf8');
const authResetBatch32 = fs.readFileSync(path.join(root, 'src/app/(auth)/reset-password.tsx'), 'utf8');
const authActivateBatch32 = fs.readFileSync(path.join(root, 'src/app/(auth)/activate-account.tsx'), 'utf8');
const accountScreenBatch32 = fs.readFileSync(path.join(root, 'src/app/(app)/(tabs)/account.tsx'), 'utf8');
const accountSessionsBatch32 = fs.readFileSync(path.join(root, 'src/app/(app)/sessions.tsx'), 'utf8');
const authAccountOpenApiBatch32 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
assert(
  authAccountEndpointsBatch32.includes("auth/password/forgot")
    && authAccountEndpointsBatch32.includes("auth/password/reset")
    && authAccountEndpointsBatch32.includes("auth/customer-activation")
    && authAccountEndpointsBatch32.includes('auth: false'),
  'v1.0 AUTH-02/AUTH-03 Mobile API koristi guest recovery/activation ugovor bez paralelnog token sistema.',
);
assert(
  authAccountLoginBatch32.includes("'/forgot-password' as Href")
    && authAccountLoginBatch32.includes("'/activate-account' as Href")
    && authForgotBatch32.includes('requestPasswordReset')
    && authResetBatch32.includes('resetPassword')
    && authResetBatch32.includes('[A-Za-z0-9]{80}')
    && authActivateBatch32.includes('customerActivationState')
    && authActivateBatch32.includes('activateAccount'),
  'v1.0 AUTH-02/AUTH-03 Mobile UI pokriva forgot/reset/activation i prihvata 80-char CMS recovery token.',
);
assert(
  accountScreenBatch32.includes("'/sessions' as Href")
    && accountSessionsBatch32.includes('api.account.sessions')
    && accountSessionsBatch32.includes('revokeSession')
    && accountSessionsBatch32.includes('revokeOtherSessions')
    && accountSessionsBatch32.includes('requireReauthentication'),
  'v1.0 ACCOUNT-02 Mobile UI pokriva aktivne API/web prijave, pojedinačni revoke i revoke-others uz current-session zaštitu.',
);
assert(
  authAccountOpenApiBatch32.includes('/api/v1/auth/password/forgot:')
    && authAccountOpenApiBatch32.includes('/api/v1/auth/password/reset:')
    && authAccountOpenApiBatch32.includes('/api/v1/auth/customer-activation:')
    && authAccountOpenApiBatch32.includes('/api/v1/me/sessions:')
    && authAccountOpenApiBatch32.includes('/api/v1/me/sessions/others:')
    && authAccountOpenApiBatch32.includes('/api/v1/me/sessions/{kind}/{session}:')
    && authAccountOpenApiBatch32.includes('AccountSessionsData:'),
  'OpenAPI dokumentuje kompletan AUTH-02 + AUTH-03 + ACCOUNT-02 mobile parity ugovor.',
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
assert(releaseCommissionHomeV07.includes('MOBILE_V0_9_HOME_MY_ACTIVITIES_BATCH5C') && releaseCommissionHomeV07.includes("can('commissions.view_own')") && releaseCommissionHomeV07.includes("route: '/commissions'"), 'v0.9 Home izlaže Moje provizije kroz Moje aktivnosti i view-own permission model.');
assert(releaseCommissionHomeV07.indexOf("route: '/admin/catalog/create'") < releaseCommissionHomeV07.indexOf('MOBILE_V0_9_HOME_MY_ACTIVITIES_BATCH5C'), 'v0.9 Home zadržava Brze akcije pre sekcije Moje aktivnosti.');
assert(releaseCommissionAdminHubV07.includes('MOBILE_V0_9_GROUPED_ADMIN_HUB_BATCH5C') && releaseCommissionAdminHubV07.includes("router.push('/admin/commissions')") && releaseCommissionAdminHubV07.includes('Prodaja'), 'v0.9 Admin Hub drži Provizije u grupisanoj sekciji Prodaja.');
assert(releaseCommissionHomeV07.includes("route: '/commissions'") && releaseCommissionCustomerListV07.includes("can('commissions.view_own')") && releaseCommissionCustomerDetailV07.includes("can('commissions.view_own')"), 'v0.9 korisničke Moje provizije ostaju dostupne kroz Home Moje aktivnosti i view-own list/detail tok.');
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

// MOBILE_V1_0_COMMISSION_POLICY
const commissionPolicyOpenApiV10 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
const commissionPolicyProductCreateV10 = fs.readFileSync(
  path.join(root, 'src/app/(app)/admin/catalog/create.tsx'),
  'utf8',
);
assert(
  commissionPolicyOpenApiV10.includes('Authorization and acceptance are enforced by the server')
    && commissionPolicyOpenApiV10.includes('automatic commission is 10% of the product value converted to EUR')
    && !commissionPolicyOpenApiV10.includes('at least 10% of the product value converted to EUR')
    && !commissionPolicyOpenApiV10.includes('remains capped at 50 EUR')
    && commissionPolicyProductCreateV10.includes('MOBILE_V1_0_COMMISSION_POLICY_SUPERADMIN_SILENT_OVERRIDE')
    && commissionPolicyProductCreateV10.includes('{isSuperAdmin ? (')
    && !commissionPolicyProductCreateV10.includes('Prazno polje koristi automatskih 10% vrednosti artikla')
    && !commissionPolicyProductCreateV10.includes('Ručna provizija mora biti najmanje')
    && !commissionPolicyProductCreateV10.includes('maksimalno 50 EUR'),
  'v1.0 Commission policy koristi automatskih 10 procenata bez plafona i SuperAdmin-gated ručni unos bez policy disclosure-a.',
);
// MOBILE_V0_8_SHIPMENT_COURIER_DIRECTORY_BATCH11
const shipmentCourierActionsV08 = fs.readFileSync(path.join(root, 'src/features/admin/orders-admin-actions.tsx'), 'utf8');
const shipmentCourierApiV08 = fs.readFileSync(path.join(root, 'src/features/admin/couriers-admin-api.ts'), 'utf8');
const shipmentCourierScreenV08 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/couriers/index.tsx'), 'utf8');
const shipmentCourierHubV08 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const shipmentCourierOpenApiV08 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
assert(shipmentCourierActionsV08.includes('courier_service_id: shipmentMethod') && shipmentCourierActionsV08.includes('data.couriers') && shipmentCourierActionsV08.includes('Otvori tracking stranicu'), 'v0.8 Shipment UI koristi centralni courier izbor, tracking URL i canonical courier_service_id.');
assert(shipmentCourierApiV08.includes("apiRequest<CourierListResponse> ('admin/couriers')") && shipmentCourierApiV08.includes("method: 'POST'") && shipmentCourierApiV08.includes("method: 'PUT'"), 'v0.8 Courier Directory Mobile API pokriva list/create/update bez delete workflow-a.');
assert(shipmentCourierScreenV08.includes("role?.slug === 'superadmin'") && shipmentCourierScreenV08.includes('Tracking URL (HTTPS)') && shipmentCourierScreenV08.includes('Podrazumevana'), 'v0.8 Courier Directory UI je SuperAdmin-only i uređuje HTTPS tracking, status, default i redosled.');
assert(shipmentCourierHubV08.includes("router.push('/admin/couriers' as Href)") && shipmentCourierHubV08.includes('Kurirske službe'), 'v0.8 Admin Hub izlaže centralni Courier Directory SuperAdministratoru.');
assert(shipmentCourierOpenApiV08.includes('/api/v1/admin/couriers:') && shipmentCourierOpenApiV08.includes('/api/v1/admin/couriers/{courier}:') && shipmentCourierOpenApiV08.includes('AdminCourierService:'), 'OpenAPI dokumentuje centralni Courier Directory list/create/update ugovor.');

// MOBILE_V0_8_COMPLETE_USER_MANAGEMENT_BATCH12
const completeUsersRequestV08 = fs.readFileSync(path.join(root, '../../cms/current/app/Http/Requests/AdminUserRequest.php'), 'utf8');
const completeUsersServiceV08 = fs.readFileSync(path.join(root, '../../cms/current/app/Services/AdminUserService.php'), 'utf8');
const completeUsersApiControllerV08 = fs.readFileSync(path.join(root, '../../cms/current/app/Http/Controllers/Api/V1/Admin/UserController.php'), 'utf8');
const completeUsersApiV08 = fs.readFileSync(path.join(root, 'src/features/admin/users-admin-api.ts'), 'utf8');
const completeUsersFormV08 = fs.readFileSync(path.join(root, 'src/features/admin/users-admin-form.tsx'), 'utf8');
const completeUsersListV08 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/users/index.tsx'), 'utf8');
const completeUsersCreateV08 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/users/create.tsx'), 'utf8');
const completeUsersDetailV08 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/users/[id].tsx'), 'utf8');
const completeUsersQueryKeysV08 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const completeUsersHubV08 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const completeUsersOpenApiV08 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
assert(completeUsersRequestV08.includes("can('system.manage_users')") && completeUsersRequestV08.includes("Rule::unique('users', 'username')") && completeUsersRequestV08.includes("'min:12'"), 'v0.8 Admin User request deli Laravel permission, unique identitet i 12-char password contract.');
assert(completeUsersServiceV08.includes('tokens()->delete()') && completeUsersServiceV08.includes('lockForUpdate()') && completeUsersServiceV08.includes('Poslednji aktivni SuperAdmin ne može biti degradiran ili blokiran.') && completeUsersServiceV08.includes("'user.updated'"), 'v0.8 centralni AdminUserService opoziva tokene, auditira izmene i štiti poslednjeg aktivnog SuperAdmina.');
assert(completeUsersApiControllerV08.includes('public function index(') && completeUsersApiControllerV08.includes('public function options(') && completeUsersApiControllerV08.includes('public function store(') && completeUsersApiControllerV08.includes('public function update(') && !completeUsersApiControllerV08.includes('function destroy('), 'v0.8 User Management API pokriva list/options/detail/create/update bez delete workflow-a.');
assert(completeUsersApiV08.includes("apiRequest<AdminUserListResponse> (") && completeUsersApiV08.includes("admin/users") && completeUsersApiV08.includes("'admin/users/options'") && completeUsersApiV08.includes("method: 'POST'") && completeUsersApiV08.includes("method: 'PUT'") && !completeUsersApiV08.includes("method: 'DELETE'"), 'v0.8 Mobile User API pokriva kompletan Laravel User Manager bez hard delete-a.');
assert(completeUsersFormV08.includes('Korisničko ime') && completeUsersFormV08.includes('Grupa pristupa') && completeUsersFormV08.includes('Status naloga') && completeUsersFormV08.includes('Nova lozinka') && completeUsersFormV08.includes('opoziva sve postojeće API tokene'), 'v0.8 shared User form pokriva identitet, ulogu, grupu, status i password management.');
assert(completeUsersListV08.includes("can('system.manage_users')") && completeUsersListV08.includes("pathname: '/admin/users/[id]'") && completeUsersCreateV08.includes('apiAdminUsers.create') && completeUsersDetailV08.includes('apiAdminUsers.update') && completeUsersDetailV08.includes('requireReauthentication'), 'v0.8 User Management UI ima permission-gated list/create/edit i self-password reauthentication.');
assert(completeUsersQueryKeysV08.includes('usersList:') && completeUsersQueryKeysV08.includes('userOptions:') && completeUsersHubV08.includes("router.push('/admin/users' as Href)"), 'v0.8 User Management query keys i Admin Hub entry su centralizovani.');
assert(completeUsersOpenApiV08.includes('/api/v1/admin/users/options:') && completeUsersOpenApiV08.includes('/api/v1/admin/users:') && completeUsersOpenApiV08.includes('/api/v1/admin/users/{user}:') && completeUsersOpenApiV08.includes('AdminUserMutationResponse:'), 'OpenAPI dokumentuje kompletan Admin User list/options/detail/create/update ugovor.');

// MOBILE_V0_8_EUR_RSD_EXCHANGE_RATE_BATCH13
const exchangeApiControllerV08 = fs.readFileSync(path.join(root, '../../cms/current/app/Http/Controllers/Api/V1/Admin/ExchangeRateController.php'), 'utf8');
const exchangeApiV08 = fs.readFileSync(path.join(root, 'src/features/admin/exchange-rate-admin-api.ts'), 'utf8');
const exchangeScreenV08 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/exchange-rate/index.tsx'), 'utf8');
const exchangeQueryKeysV08 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const exchangeHubV08 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const exchangeOpenApiV08 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
assert(exchangeApiControllerV08.includes("can('system.manage_settings')") && exchangeApiControllerV08.includes('saveManual') && exchangeApiControllerV08.includes('setAutomatic') && exchangeApiControllerV08.includes("updateAutomatically('mobile'") && exchangeApiControllerV08.includes('limit(50)'), 'v0.8 Exchange Rate API koristi centralni ExchangeRateService i 50 zapisa istorije.');
assert(exchangeApiV08.includes("'admin/exchange-rate'") && exchangeApiV08.includes("'admin/exchange-rate/manual'") && exchangeApiV08.includes("'admin/exchange-rate/automatic'") && exchangeApiV08.includes("'admin/exchange-rate/refresh'"), 'v0.8 Mobile Exchange Rate API pokriva state, manual, automatic i refresh ugovor.');
assert(exchangeScreenV08.includes("can('system.manage_settings')") && exchangeScreenV08.includes('Sačuvaj ručni kurs') && exchangeScreenV08.includes('Automatsko ažuriranje') && exchangeScreenV08.includes('Sinhronizuj sada') && exchangeScreenV08.includes('Istorija kursa'), 'v0.8 Exchange Rate ekran ima permission-gated manual/automatic/refresh/history UX.');
const exchangeParallelFetchV08 = /(^|[^A-Za-z0-9_.$])fetch\s*\(/m.test(exchangeScreenV08) || /globalThis\.fetch\s*\(/.test(exchangeScreenV08) || /(^|[^A-Za-z0-9_.$])expoFetch\s*\(/m.test(exchangeScreenV08);
assert(exchangeScreenV08.includes('apiAdminExchangeRate.manual') && exchangeScreenV08.includes('apiAdminExchangeRate.automatic') && exchangeScreenV08.includes('apiAdminExchangeRate.refresh') && !exchangeParallelFetchV08, 'v0.8 Exchange Rate UI koristi canonical API client bez paralelnog fetch toka.');
assert(exchangeQueryKeysV08.includes('exchangeRate:') && exchangeHubV08.includes("router.push('/admin/exchange-rate' as Href)"), 'v0.8 Exchange Rate query key i Admin Hub entry su centralizovani.');
assert(exchangeOpenApiV08.includes('/api/v1/admin/exchange-rate:') && exchangeOpenApiV08.includes('/api/v1/admin/exchange-rate/manual:') && exchangeOpenApiV08.includes('/api/v1/admin/exchange-rate/automatic:') && exchangeOpenApiV08.includes('/api/v1/admin/exchange-rate/refresh:') && exchangeOpenApiV08.includes('AdminExchangeRateStateResponse:'), 'OpenAPI dokumentuje kompletan EUR/RSD Admin contract.');

// MOBILE_V0_9_GLOBAL_BRAND_MANAGER_BATCH3
const brandManagerApiV09 = fs.readFileSync(path.join(root, 'src/features/admin/brand-manager-api.ts'), 'utf8');
const brandManagerScreenV09 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/brands/index.tsx'), 'utf8');
const brandManagerHubV09 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const brandManagerKeysV09 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const brandManagerOpenApiV09 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
assert(
  brandManagerApiV09.includes("'admin/catalog/brands/options'")
    && brandManagerApiV09.includes("'admin/catalog/brands'")
    && brandManagerApiV09.includes('product_type_ids')
    && brandManagerApiV09.includes('line_names_by_type')
    && !brandManagerApiV09.includes('/api/v1/admin/catalog/brands'),
  'v0.9 Brand Manager koristi relativni centralizovani API ugovor sa type-scoped brand/line podacima.',
);
assert(
  brandManagerScreenV09.includes("can('catalog.manage_taxonomy')")
    && brandManagerScreenV09.includes('Pretraga brenda')
    && brandManagerScreenV09.includes('Tip / kategorija')
    && brandManagerScreenV09.includes('+ Dodaj brend')
    && brandManagerScreenV09.includes('Povezani tipovi i linije')
    && !brandManagerScreenV09.includes('ProductVariant'),
  'v0.9 Mobile Brand Manager je permission-gated i pokriva globalni filter/search/add/edit/type/line UX bez Product Variants.',
);
assert(
  brandManagerHubV09.includes("router.push('/admin/catalog/dictionaries' as Href)")
    && brandManagerHubV09.includes("can('catalog.manage_taxonomy')")
    && brandManagerHubV09.includes("adminMatch('Brendovi')"),
  'v0.9/v1.0 Admin Hub izlaže Šifarnike taxonomy administratorima i pretraga obuhvata Brendove.',
);
assert(
  brandManagerKeysV09.includes("brandsList: (params: unknown) => ['admin', 'brands', 'list', params] as const")
    && brandManagerKeysV09.includes("brandOptions: () => ['admin', 'brands', 'options'] as const"),
  'v0.9 Brand Manager koristi centralizovane TanStack query keys.',
);
assert(
  brandManagerOpenApiV09.includes('/api/v1/admin/catalog/brands:')
    && brandManagerOpenApiV09.includes('/api/v1/admin/catalog/brands/options:')
    && brandManagerOpenApiV09.includes('/api/v1/admin/catalog/brands/{brand}:')
    && brandManagerOpenApiV09.includes('catalog.manage_taxonomy'),
  'v0.9 OpenAPI dokumentuje globalni Brand Manager read/create/update/options ugovor i taxonomy permission.',
);

// MOBILE_V0_9_SUPERADMIN_HOME_INVENTORY_VALUE_KPIS
const superAdminHomeKpiV09 = fs.readFileSync(path.join(root, 'src/app/(app)/(tabs)/home.tsx'), 'utf8');
const adminApiHomeKpiV09 = fs.readFileSync(path.join(root, 'src/features/admin/admin-api.ts'), 'utf8');
assert(
  superAdminHomeKpiV09.includes('{/* MOBILE_V0_9_SUPERADMIN_HOME_INVENTORY_VALUE_KPIS */}')
    && superAdminHomeKpiV09.includes('Vrednost lagera po nabavnoj ceni')
    && superAdminHomeKpiV09.includes('Vrednost robe po prodajnoj ceni')
    && superAdminHomeKpiV09.includes("bootstrap?.user.role?.slug === 'superadmin'")
    && superAdminHomeKpiV09.includes('adminQueryKeys.foundation()')
    && superAdminHomeKpiV09.includes('apiAdmin.foundation')
    && superAdminHomeKpiV09.indexOf('Vrednost lagera po nabavnoj ceni') < superAdminHomeKpiV09.indexOf('<SalesPulse')
    && superAdminHomeKpiV09.indexOf('Vrednost robe po prodajnoj ceni') < superAdminHomeKpiV09.indexOf('<SalesPulse'),
  'v0.9 Home prikazuje dve SuperAdmin inventory valuation pločice ispod postojeća četiri KPI-ja i pre Finansijskog pulsa.',
);
assert(
  adminApiHomeKpiV09.includes('AdminInventoryValuation')
    && adminApiHomeKpiV09.includes('inventory_valuation: AdminInventoryValuation | null'),
  'v0.9 Home inventory KPI koristi postojeći centralizovani Admin Foundation valuation contract.',
);

// MOBILE_V0_9_PRODUCT_DETAIL_BATCH5A_VALIDATOR
const v09ProductDetailBatch5A = fs.readFileSync(path.join(root, 'src/app/(app)/product/[slug].tsx'), 'utf8');
const v09ProductCardBatch5A = fs.readFileSync(path.join(root, 'src/components/catalog/product-card.tsx'), 'utf8');
// MOBILE_V1_0_CATALOG_EDIT_HANDOFF_VALIDATOR_BATCH45_V5
const v09ProductEditHandoffBatch45 = fs.readFileSync(path.join(root, 'src/features/catalog/catalog-product-edit-handoff.ts'), 'utf8');
const v09AdminCatalogHandoffBatch45 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/index.tsx'), 'utf8');
const v09ProductDetailMarker = v09ProductDetailBatch5A.indexOf('MOBILE_V0_9_PRODUCT_DETAIL_COMMISSION_DIRECT_SALE_BATCH5A');
const v09DirectSale = v09ProductDetailBatch5A.indexOf('Direktna prodaja', v09ProductDetailMarker);
const v09Edit = v09ProductDetailBatch5A.indexOf('Uredi artikal', v09ProductDetailMarker);
const v09ProductDetailPass = v09ProductDetailMarker >= 0
  && v09DirectSale > v09ProductDetailMarker
  && v09Edit > v09DirectSale
  && v09ProductDetailBatch5A.includes("pathname: '/admin/catalog/[id]/direct-sale'")
  && v09ProductDetailBatch5A.includes('scheduleCatalogProductEditHandoff(product.id)')
  && v09ProductDetailBatch5A.includes("router.replace('/admin/catalog')")
  && !v09ProductDetailBatch5A.includes("router.replace({ pathname: '/admin/catalog/[id]'")
  && v09ProductEditHandoffBatch45.includes('MOBILE_V1_0_CATALOG_EDIT_HANDOFF_PERFORMANCE_BATCH45')
  && v09ProductEditHandoffBatch45.includes('pendingProductId')
  && v09AdminCatalogHandoffBatch45.includes('InteractionManager.runAfterInteractions')
  && v09AdminCatalogHandoffBatch45.includes('enabled: allowed && handoffEditId === null')
  && v09AdminCatalogHandoffBatch45.includes("pathname: '/admin/catalog/[id]'")
  && v09AdminCatalogHandoffBatch45.includes('Priprema izmene artikla...')
  && v09ProductDetailBatch5A.includes("formatPrimaryMoney(product.commission_eur, 'EUR')")
  && v09ProductDetailBatch5A.includes('useMoneyPresentation');
if (v09ProductDetailPass) console.log('PASS v0.9 Product detalj prikazuje server proviziju, SuperAdmin Direct Sale i Uredi artikal kao poslednju admin akciju.');
else { failures += 1; console.log('FAIL v0.9 Product detail commission/direct-sale/edit contract nije kompletan.'); }
const v09ProductCardPass = v09ProductCardBatch5A.includes('MOBILE_V0_9_CATALOG_COMMISSION_BATCH5A')
  && v09ProductCardBatch5A.includes("Provizija: {formatPrimaryMoney(product.commission_eur, 'EUR')}")
  && v09ProductCardBatch5A.includes('useMoneyPresentation');
if (v09ProductCardPass) console.log('PASS v0.9 Catalog kartica prikazuje server obračunatu proviziju.');
else { failures += 1; console.log('FAIL v0.9 Catalog commission card contract nije kompletan.'); }
if (!/product_variant_id|ProductVariant|variants_enabled/.test(v09ProductDetailBatch5A + '\n' + v09ProductCardBatch5A)) console.log('PASS v0.9 Product detail/catalog commission tok ostaje product-only bez Product Variants.');
else { failures += 1; console.log('FAIL v0.9 Product Variants signal je vraćen u Product detail/catalog source.'); }

// MOBILE_V0_9_DIRECT_SALE_DEFERRED_PAYMENT_RECEIVABLES_BATCH5B_V2
const directSaleDeferredServiceV09 = fs.readFileSync(path.join(root, '../../cms/current/app/Services/DirectSaleService.php'), 'utf8');
const directSaleDeferredPaymentV09 = fs.readFileSync(path.join(root, '../../cms/current/app/Services/OrderPaymentService.php'), 'utf8');
const directSaleDeferredControllerV09 = fs.readFileSync(path.join(root, '../../cms/current/app/Http/Controllers/Api/V1/Admin/CatalogProductController.php'), 'utf8');
const directSaleDeferredApiV09 = fs.readFileSync(path.join(root, 'src/features/admin/catalog-admin-api.ts'), 'utf8');
const directSaleDeferredScreenV09 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/[id]/direct-sale.tsx'), 'utf8');
const directSaleDeferredOpenApiV09 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
assert(
  directSaleDeferredServiceV09.includes("'deferred_payment'")
    && directSaleDeferredServiceV09.includes('ensureDeferredReceivablePlan')
    && directSaleDeferredServiceV09.includes('buildDeferredInstallments')
    && directSaleDeferredServiceV09.includes("'payment_state' => $deferred ? 'unpaid' : 'paid'")
    && directSaleDeferredServiceV09.includes('if (!$deferred) {'),
  'v0.9 Direct Sale deferred tok ostavlja finansijski saldo otvoren i koristi postojeći Receivables plan.',
);
assert(
  directSaleDeferredPaymentV09.includes('isDeferredDirectSale')
    && directSaleDeferredPaymentV09.includes("$type !== 'payment'")
    && directSaleDeferredPaymentV09.includes('Uplata ne može biti veća od preostalog duga')
    && directSaleDeferredPaymentV09.includes('recordAfterSalesRefundLocked'),
  'v0.9 deferred Direct Sale dozvoljava payment lifecycle, blokira ad-hoc refund i čuva canonical after-sales refund.',
);
assert(
  directSaleDeferredControllerV09.includes("'deferred_payment', 'label' => 'Odloženo plaćanje'")
    && directSaleDeferredControllerV09.includes("'installment_count'")
    && directSaleDeferredControllerV09.includes("'payment_due_at'"),
  'v0.9 Direct Sale API validira odloženo plaćanje, 1–24 rate i konačni datum.',
);
assert(
  directSaleDeferredApiV09.includes("| 'deferred_payment'")
    && directSaleDeferredApiV09.includes('installment_count?: number')
    && directSaleDeferredApiV09.includes('payment_due_at?: string'),
  'v0.9 Mobile Direct Sale API ugovor sadrži deferred payment polja.',
);
assert(
  directSaleDeferredScreenV09.includes('Plan odloženog plaćanja')
    && directSaleDeferredScreenV09.includes('installmentCount')
    && directSaleDeferredScreenV09.includes('paymentDueAt')
    && directSaleDeferredScreenV09.includes('validIsoDateOnOrAfterToday'),
  'v0.9 Direct Sale ekran prikazuje uslovni plan rata i konačni datum pune isplate.',
);
assert(
  directSaleDeferredOpenApiV09.includes('enum: [cash, card, bank_transfer, other, deferred_payment]')
    && directSaleDeferredOpenApiV09.includes('installment_count:')
    && directSaleDeferredOpenApiV09.includes('payment_due_at:'),
  'OpenAPI dokumentuje deferred Direct Sale payment metodu, rate i konačni datum.',
);
assert(
  !/ProductVariant|product_variant_id|variants_enabled/.test(directSaleDeferredServiceV09 + directSaleDeferredControllerV09 + directSaleDeferredApiV09 + directSaleDeferredScreenV09),
  'v0.9 Direct Sale deferred tok ne vraća Product Variants.',
);

// MOBILE_V0_9_NAVIGATION_ADMIN_HUB_MY_ACTIVITIES_BATCH5C_VALIDATOR
{
  const fs = await import('node:fs');
  const path = await import('node:path');
  const mobileRoot = process.cwd();
  const readText = (rel) => fs.readFileSync(path.join(mobileRoot, rel), 'utf8');
  const tabs = readText('src/app/(app)/(tabs)/_layout.tsx');
  const home = readText('src/app/(app)/(tabs)/home.tsx');
  const orders = readText('src/app/(app)/(tabs)/orders.tsx');
  const account = readText('src/app/(app)/(tabs)/account.tsx');
  const admin = readText('src/app/(app)/admin/index.tsx');

  assert(
    tabs.indexOf('name="home"') < tabs.indexOf('name="catalog"') &&
    tabs.indexOf('name="catalog"') < tabs.indexOf('name="orders"') &&
    tabs.indexOf('name="orders"') < tabs.indexOf('name="notifications"') &&
    tabs.indexOf('name="notifications"') < tabs.indexOf('name="account"'),
    'v0.9 Bottom navigation ostaje Početna, Katalog, Porudžbine, Obaveštenja, Nalog.'
  );

  assert(
    home.includes('MOBILE_V0_9_HOME_FOCUS_SECTION_BATCH5C') &&
    home.includes('MOBILE_V0_9_HOME_MY_ACTIVITIES_BATCH5C') &&
    home.includes('MOBILE_V0_9_HOME_ADMINISTRATION_BATCH5C') &&
    home.indexOf('MOBILE_V0_9_HOME_FOCUS_SECTION_BATCH5C') < home.indexOf('Brze akcije') &&
    home.indexOf('Brze akcije') < home.indexOf('MOBILE_V0_9_HOME_MY_ACTIVITIES_BATCH5C') &&
    home.indexOf('MOBILE_V0_9_HOME_MY_ACTIVITIES_BATCH5C') < home.indexOf('MOBILE_V0_9_HOME_ADMINISTRATION_BATCH5C'),
    'v0.9 Home prati Fokus danas > Brze akcije > Moje aktivnosti > Administracija hijerarhiju.'
  );

  assert(
    home.includes("route: '/orders'") &&
    home.includes("route: '/commissions'") &&
    home.includes("route: '/warranties'") &&
    home.includes("route: '/after-sales'"),
    'v0.9 Moje aktivnosti centralizuju porudžbine, provizije, garancije i postprodaju.'
  );

  assert(
    orders.includes('MOBILE_V0_9_ORDERS_MY_AND_ASSIGNED_ONLY_BATCH5C') &&
    orders.includes('Dodeljene porudžbine') &&
    !orders.includes('Reklamacije i servis') &&
    !orders.includes('Moje garancije') &&
    !orders.includes('Moje provizije'),
    'v0.9 Porudžbine prikazuju Moje i Dodeljene bez cross-feature prečica.'
  );

  assert(
    admin.includes('MOBILE_V0_9_GROUPED_ADMIN_HUB_BATCH5C') &&
    admin.includes('Pretraži administraciju') &&
    ['Prodaja', 'Katalog i lager', 'Postprodaja', 'Poslovanje', 'Korisnici', 'Sistem'].every((label) => admin.includes(label)),
    'v0.9 Admin Hub je permission-filtered, pretraživ i grupisan u šest poslovnih sekcija.'
  );

  assert(
    account.includes('MOBILE_V0_9_ACCOUNT_SECTION_ORDER_BATCH5C') &&
    account.indexOf('title="Profil"') < account.indexOf('title="Bezbednost"') &&
    account.indexOf('title="Bezbednost"') < account.indexOf('Obaveštenja') &&
    account.indexOf('Obaveštenja') < account.indexOf("router.push('/devices')") &&
    account.indexOf("router.push('/devices')") < account.lastIndexOf('Odjava'),
    'v0.9 Nalog prati Profil > Bezbednost > Obaveštenja > Uređaji > Odjava redosled.'
  );

  assert(
    !home.includes('ProductVariant') && !orders.includes('ProductVariant') && !admin.includes('ProductVariant'),
    'v0.9 Navigation reorganizacija ne vraća Product Variants.'
  );
}

// MOBILE_V1_0_PERSONALIZATION_NAVIGATION_UI_MOTION_BATCH50_V2
const batch50StorageV2 = fs.readFileSync(path.join(root, 'src/lib/storage.ts'), 'utf8');
const batch50PreferencesV2 = fs.readFileSync(path.join(root, 'src/features/preferences/app-preferences.tsx'), 'utf8');
const batch50ThemeV2 = fs.readFileSync(path.join(root, 'src/theme/app-theme.ts'), 'utf8');
const batch50RootV2 = fs.readFileSync(path.join(root, 'src/app/_layout.tsx'), 'utf8');
const batch50AccountV2 = fs.readFileSync(path.join(root, 'src/app/(app)/(tabs)/account.tsx'), 'utf8');
const batch50SegmentedV2 = fs.readFileSync(path.join(root, 'src/components/ui/segmented-choice.tsx'), 'utf8');
const batch50LoadingV2 = fs.readFileSync(path.join(root, 'src/components/ui/states.tsx'), 'utf8');
const batch50MoneyV2 = fs.readFileSync(path.join(root, 'src/features/preferences/money-presentation.ts'), 'utf8');
const batch50TypesV2 = fs.readFileSync(path.join(root, 'src/types/api.ts'), 'utf8');
const batch50BottomNavV2 = fs.readFileSync(path.join(root, 'src/components/layout/app-bottom-nav.tsx'), 'utf8');
const batch50PageHeaderV2 = fs.readFileSync(path.join(root, 'src/components/layout/page-header.tsx'), 'utf8');
const batch50GlyphV2 = fs.readFileSync(path.join(root, 'src/components/ui/glyph.tsx'), 'utf8');
const batch50BootstrapV2 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Controllers/Api/V1/BootstrapController.php'), 'utf8');
const batch50ExchangeAuthorityV2 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Services/ExchangeRateService.php'), 'utf8');
const batch50OpenApiV2 = fs.readFileSync(path.join(projectRoot, 'packages/api-contract/openapi.yaml'), 'utf8');
const batch50OrderDetailV2 = fs.readFileSync(path.join(root, 'src/app/(app)/order/[id].tsx'), 'utf8');
assert(
  batch50StorageV2.includes('ald1n.preferences.user.')
    && batch50StorageV2.includes('WHEN_UNLOCKED_THIS_DEVICE_ONLY')
    && batch50PreferencesV2.includes("themeMode: 'system'")
    && batch50PreferencesV2.includes("primaryCurrency: 'RSD'")
    && batch50PreferencesV2.includes('setUserAppPreferences(userId, stored)'),
  'v1.0 Batch50 V2 lokalna tema i valuta su per-user/per-device SecureStore preference bez server write-a.',
);
assert(
  batch50AccountV2.includes('Izgled i prikaz')
    && batch50AccountV2.includes("{ value: 'system', label: 'Sistem' }")
    && batch50AccountV2.includes("{ value: 'light', label: 'Svetla' }")
    && batch50AccountV2.includes("{ value: 'dark', label: 'Tamna' }")
    && batch50AccountV2.includes("{ value: 'RSD', label: 'RSD' }")
    && batch50AccountV2.includes("{ value: 'EUR', label: 'EUR' }")
    && batch50SegmentedV2.includes('transition="quickLessBouncy"')
    && batch50SegmentedV2.includes('accessibilityRole="radio"'),
  'v1.0 Batch50 V2 Profil ima moderne single-choice Tema i Primarna valuta kontrole.',
);
assert(
  batch50RootV2.includes('AppPreferencesProvider')
    && batch50RootV2.includes("key={userId ?? 'anonymous'}")
    && batch50RootV2.includes('defaultTheme={scheme}')
    && batch50ThemeV2.includes('const { themeMode } = useAppPreferences()'),
  'v1.0 Batch50 V2 lokalna tema upravlja RN/Tamagui/StatusBar shell-om posle korisničke preference hidratacije.',
);
assert(
  batch50BootstrapV2.includes('ExchangeRateService $exchangeRate')
    && batch50BootstrapV2.includes("'currency' => $currency")
    && batch50BootstrapV2.includes('ExchangeRateService::RATE_KIND')
    && batch50TypesV2.includes('CurrencyPresentationContract')
    && batch50OpenApiV2.includes('CurrencyPresentationContract:'),
  'v1.0 Batch50 V2 bootstrap izlaže samo read-only presentation metadata postojećeg NBS authority-ja.',
);
assert(
  batch50ExchangeAuthorityV2.includes("public const RATE_KIND = 'commercial_sell'")
    && batch50ExchangeAuthorityV2.includes("public const RATE_LABEL = 'Komercijalni prodajni'")
    && batch50ExchangeAuthorityV2.includes('CurrentForeignExchange')
    && batch50ExchangeAuthorityV2.includes("private const PROVIDER = 'nbs'")
    && batch50ExchangeAuthorityV2.includes('$sellingRate = $this->decimalFromNbs($cells[5]);'),
  'v1.0 Batch50 V2 NBS Komercijalni prodajni authority ostaje jedini EUR/RSD source.',
);
assert(
  batch50MoneyV2.includes('convertPresentationAmount')
    && batch50MoneyV2.includes("source === 'RSD' && targetCurrency === 'EUR'")
    && batch50MoneyV2.includes("source === 'EUR' && targetCurrency === 'RSD'")
    && batch50OrderDetailV2.includes('Iznos RSD *')
    && batch50OrderDetailV2.includes('amount_rsd: amount'),
  'v1.0 Batch50 V2 primarna valuta je display-only; canonical RSD payment input i payload ostaju nepromenjeni.',
);
assert(
  batch50LoadingV2.includes("from 'react-native-reanimated'")
    && batch50LoadingV2.includes('withRepeat(')
    && batch50LoadingV2.includes('BrandMark')
    && batch50LoadingV2.includes('accessibilityRole="progressbar"'),
  'v1.0 Batch50 V2 globalni loading koristi branded Reanimated pulse i skeleton.',
);
assert(
  batch50BottomNavV2.includes('MOBILE_V1_0_CENTER_HOME_ROLE_AWARE_NAV_BATCH50_V2')
    && batch50BottomNavV2.includes("label: 'Početna'")
    && batch50BottomNavV2.includes('center: true')
    && batch50BottomNavV2.includes('transform: [{ translateY: -17 }]')
    && batch50BottomNavV2.includes("bootstrap?.user.role?.slug === 'superadmin'")
    && batch50BottomNavV2.includes("label: 'Admin'")
    && batch50BottomNavV2.includes("route: '/admin'")
    && batch50BottomNavV2.includes("label: 'Obaveštenja'")
    && batch50BottomNavV2.includes("label: 'Nalog'"),
  'v1.0 Batch50 V2 bottom nav drži centralno izdvojenu Početnu i role-aware Admin/Obaveštenja četvrti slot.',
);
assert(
  batch50PageHeaderV2.includes('MOBILE_V1_0_HEADER_NOTIFICATIONS_BATCH50_V2')
    && batch50PageHeaderV2.includes("router.push('/notifications')")
    && batch50PageHeaderV2.includes('useNotificationUnread')
    && batch50PageHeaderV2.includes('function PageHeaderNotificationButton')
    && !batch50PageHeaderV2.includes("router.push('/account')")
    && !batch50PageHeaderV2.includes('styles.avatar')
    && batch50GlyphV2.includes("admin: { ios: 'shield.lefthalf.filled', android: 'admin_panel_settings'"),
  'v1.0 Batch50 V2 header desno koristi notification bell+badge umesto profila, a Nalog ostaje u bottom nav-u.',
);
assert(
  batch50BottomNavV2.includes("pathname.startsWith('/admin/catalog/')")
    && batch50BottomNavV2.indexOf("pathname.startsWith('/admin/catalog/')") < batch50BottomNavV2.indexOf("pathname === '/admin' || pathname.startsWith('/admin/')"),
  'v1.0 Batch50 V2 čuva Katalog active context za admin/catalog edit, dok ostali admin ekrani aktiviraju Admin slot.',
);

// MOBILE_V1_0_ADMIN_CATALOG_DICTIONARIES_BATCH22
const dictionariesApiV10 = fs.readFileSync(path.join(root, 'src/features/admin/dictionary-admin-api.ts'), 'utf8');
const dictionariesHubV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/dictionaries/index.tsx'), 'utf8');
const dictionariesResourceV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/dictionaries/[resource].tsx'), 'utf8');
const dictionariesTypeV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/dictionaries/product-types/[id].tsx'), 'utf8');
const dictionariesAdminHubV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const dictionariesBrandV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/brands/index.tsx'), 'utf8');
const dictionariesKeysV10 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const dictionariesOpenApiV10 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
const brandLineApiV10 = fs.readFileSync(path.join(root, 'src/features/admin/brand-manager-api.ts'), 'utf8');
assert(
  dictionariesHubV10.includes("can('catalog.manage_taxonomy')")
    && dictionariesHubV10.includes("/admin/catalog/brands")
    && dictionariesHubV10.includes("/admin/catalog/dictionaries/categories")
    && dictionariesHubV10.includes("/admin/catalog/dictionaries/product-lines")
    && dictionariesHubV10.includes("/admin/catalog/dictionaries/product-types")
    && dictionariesHubV10.includes("/admin/catalog/dictionaries/specification-fields"),
  'v1.0 Šifarnici hub je permission-gated i vodi na svih pet canonical destinacija bez duplog Brand Managera.',
);
assert(
  dictionariesApiV10.includes('apiAdminDictionaries')
    && dictionariesApiV10.includes('reorderBrands')
    && dictionariesApiV10.includes('purgeSpecificationField')
    && dictionariesApiV10.includes('reorderProductTypeFields')
    && !dictionariesApiV10.includes('/api/v1/admin/catalog/dictionaries'),
  'v1.0 Dictionary API koristi relativni centralizovani CRUD/reorder/purge/product-type ugovor.',
);
assert(
  dictionariesResourceV10.includes('apiAdminDictionaries.create')
    && dictionariesResourceV10.includes('apiAdminDictionaries.update')
    && dictionariesResourceV10.includes('apiAdminDictionaries.deactivate')
    && dictionariesResourceV10.includes('apiAdminDictionaries.reorder')
    && dictionariesResourceV10.includes('apiAdminDictionaries.purgeSpecificationField')
    && dictionariesResourceV10.includes('dependency_map_text')
    && dictionariesResourceV10.includes('detail_input_enabled'),
  'v1.0 Mobile šifarnici pokrivaju create/update/deactivate/reorder i bezbedni specification purge sa korelacijama.',
);
assert(
  dictionariesTypeV10.includes('field_config')
    && dictionariesTypeV10.includes('is_required')
    && dictionariesTypeV10.includes('is_filterable')
    && dictionariesTypeV10.includes('show_in_summary')
    && dictionariesTypeV10.includes('include_in_name')
    && dictionariesTypeV10.includes('completeness_weight')
    && dictionariesTypeV10.includes('default_detail')
    && dictionariesTypeV10.includes('reorderProductTypeFields')
    && dictionariesTypeV10.includes('minimum_completeness_percent')
    && dictionariesTypeV10.includes('name_template'),
  'v1.0 Product Type detalj pokriva kompletan CMS field/completeness/name-template i reorder ugovor.',
);
assert(
  dictionariesBrandV10.includes('apiAdminDictionaries.reorderBrands')
    && dictionariesBrandV10.includes('Pomeri gore')
    && dictionariesBrandV10.includes('Pomeri dole')
    && dictionariesBrandV10.includes('apiAdminBrands.create')
    && dictionariesBrandV10.includes('apiAdminBrands.update'),
  'v1.0 postojeći Global Brand Manager ostaje canonical CRUD ekran i dobija shared reorder bez duplog odredišta.',
);
assert(
  dictionariesBrandV10.includes('MOBILE_V1_0_BRAND_LINE_EXPANSION_BATCH22_V3')
    && dictionariesBrandV10.includes('MAX_BRAND_LINES_PER_TYPE = 10')
    && dictionariesBrandV10.includes('+ Dodaj liniju')
    && dictionariesBrandV10.includes('Ukloni')
    && brandLineApiV10.includes('max_lines_per_type: 10')
    && dictionariesOpenApiV10.includes('do deset kuriranih linija po tipu')
    && dictionariesOpenApiV10.includes('maxItems: 10'),
  'v1.0 Brand Manager podržava do deset type-scoped linija i dinamički Mobile add/remove editor.',
);
assert(
  dictionariesAdminHubV10.includes("router.push('/admin/catalog/dictionaries' as Href)")
    && dictionariesAdminHubV10.includes("adminMatch('Kategorije')")
    && dictionariesAdminHubV10.includes("adminMatch('Linije proizvoda')")
    && dictionariesAdminHubV10.includes("adminMatch('Tipovi proizvoda')")
    && dictionariesAdminHubV10.includes("adminMatch('Specifikaciona polja')"),
  'v1.0 Admin Hub postavlja Šifarnike u Katalog i lager i pretraga nalazi ugnježdene opcije.',
);
assert(
  dictionariesKeysV10.includes('dictionaryProductType:')
    && dictionariesKeysV10.includes('dictionary: (resource: string)'),
  'v1.0 Dictionary TanStack query keys su centralizovani.',
);
assert(
  dictionariesOpenApiV10.includes('/api/v1/admin/catalog/dictionaries/{resource}:')
    && dictionariesOpenApiV10.includes('/api/v1/admin/catalog/dictionaries/{resource}/{item}:')
    && dictionariesOpenApiV10.includes('/api/v1/admin/catalog/dictionaries/{resource}/reorder:')
    && dictionariesOpenApiV10.includes('/api/v1/admin/catalog/dictionaries/brands/reorder:')
    && dictionariesOpenApiV10.includes('/api/v1/admin/catalog/dictionaries/product-types/{productType}:')
    && dictionariesOpenApiV10.includes('/api/v1/admin/catalog/dictionaries/product-types/{productType}/fields/reorder:')
    && dictionariesOpenApiV10.includes('/api/v1/admin/catalog/dictionaries/specification-fields/{item}/purge:'),
  'OpenAPI dokumentuje kompletan ADMIN-CAT-10 dictionary route surface.',
);
const dictionariesVariantIdentifiersV10 = [
  dictionariesHubV10,
  dictionariesResourceV10,
  dictionariesTypeV10,
  dictionariesBrandV10,
  dictionariesApiV10,
].join('\n');
assert(
  !/(ProductVariant|product_variant_id|product_variants|variants_enabled)/.test(dictionariesVariantIdentifiersV10),
  'v1.0 Šifarnici ne vraćaju aktivni Product Variants contract.',
);

// MOBILE_V1_0_ADMIN_ORDER_DOCUMENTS_INVOICE_PARITY_BATCH23
const adminOrderDocumentsUiV10 = fs.readFileSync(path.join(root, 'src/features/admin/order-documents-admin.tsx'), 'utf8');
const adminOrderDocumentFilesV10 = fs.readFileSync(path.join(root, 'src/features/admin/order-document-files.ts'), 'utf8');
const adminOrderDetailV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/orders/[id].tsx'), 'utf8');
const adminOrdersApiV10 = fs.readFileSync(path.join(root, 'src/features/admin/orders-admin-api.ts'), 'utf8');
// MOBILE_V1_0_ADMIN_ORDER_PDF_RELATIVE_PATH_HOTFIX_BATCH51_V3
assert(
  adminOrdersApiV10.includes('documentIssue')
    && adminOrdersApiV10.includes('documentCancel')
    && adminOrdersApiV10.includes('MOBILE_V1_0_ADMIN_ORDER_PDF_RELATIVE_PATH_HOTFIX_BATCH51_V3')
    && adminOrdersApiV10.includes("documentConfirmationPdfPath: (orderId: number) => `admin/orders/${orderId}/documents/confirmation.pdf`")
    && adminOrdersApiV10.includes("documentPdfPath: (orderId: number, documentId: number) => `admin/orders/${orderId}/documents/${documentId}.pdf`")
    && adminOrdersApiV10.includes("shipmentProofPath: (orderId: number) => `admin/orders/${orderId}/shipment-proof`")
    && !adminOrdersApiV10.includes('/api/v1/admin/orders/'),
  'v1.0 Admin Orders PDF/shipment binary putanje su relativne i ne dupliraju /api/v1 prefiks.',
);
assert(
  adminOrderDetailV10.includes('<AdminOrderDocuments')
    && adminOrderDetailV10.includes('canManage={response.capabilities.documents}'),
  'v1.0 Admin Order detalj ugrađuje permission-gated Poslovni dokumenti workbench bez orphan ekrana.',
);
assert(
  adminOrderDocumentsUiV10.includes("'proforma'")
    && adminOrderDocumentsUiV10.includes("'invoice'")
    && adminOrderDocumentsUiV10.includes("'delivery_note'")
    && adminOrderDocumentsUiV10.includes('documentCancel')
    && adminOrderDocumentsUiV10.includes('Potvrda PDF')
    && adminOrderDocumentsUiV10.includes('confirmationMutation')
    && adminOrderDocumentsUiV10.includes('cancelReason.trim().length < 5')
    && adminOrderDocumentsUiV10.includes('revision_number'),
  'v1.0 Mobile document workbench pokriva predračun, račun, otpremnicu, istoriju revizija i kontrolisano storniranje.',
);
assert(
  adminOrderDocumentFilesV10.includes('apiDownload')
    && adminOrderDocumentFilesV10.includes("mime !== 'application/pdf'")
    && adminOrderDocumentFilesV10.includes('hasPdfSignature')
    && adminOrderDocumentFilesV10.includes('apiAdminOrders.documentConfirmationPdfPath')
    && adminOrderDocumentFilesV10.includes('apiAdminOrders.documentPdfPath')
    && adminOrderDocumentFilesV10.includes('expo-sharing'),
  'v1.0 Admin dokument PDF koristi authenticated Bearer download, PDF signature proveru i privatni cache/share flow.',
);
const adminOrderDocumentsVariantGuardV10 = adminOrderDocumentsUiV10 + adminOrderDocumentFilesV10 + adminOrdersApiV10;
assert(
  !/(ProductVariant|product_variant_id|product_variants|variants_enabled)/.test(adminOrderDocumentsVariantGuardV10),
  'v1.0 Admin dokumenti ostaju product-only bez Product Variants contracta.',
);

// MOBILE_V1_0_ADMIN_CATALOG_PURGE_TOTAL_PURGE_BATCH24
const adminCatalogDeletionApiV10 = fs.readFileSync(path.join(root, 'src/features/admin/catalog-admin-api.ts'), 'utf8');
const adminCatalogDeletionUiV10 = fs.readFileSync(path.join(root, 'src/features/admin/product-deletion-admin.tsx'), 'utf8');
const adminCatalogDeletionDetailV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/[id].tsx'), 'utf8');
const adminCatalogDeletionControllerV10 = fs.readFileSync(path.join(root, '../../cms/current/app/Http/Controllers/Api/V1/Admin/ProductDeletionController.php'), 'utf8');
const adminCatalogDeletionServiceV10 = fs.readFileSync(path.join(root, '../../cms/current/app/Services/ProductDeletionService.php'), 'utf8');
const adminCatalogTotalPurgeServiceV10 = fs.readFileSync(path.join(root, '../../cms/current/app/Services/TotalProductPurgeService.php'), 'utf8');
const adminCatalogDeletionOpenApiV10 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
assert(
  adminCatalogDeletionApiV10.includes('AdminCatalogProductDeletionState')
    && adminCatalogDeletionApiV10.includes('deletion: async (productId: number)')
    && adminCatalogDeletionApiV10.includes('purge: (productId: number')
    && adminCatalogDeletionApiV10.includes('totalPurge: (productId: number'),
  'v1.0 Admin Catalog API pokriva server-driven deletion readiness, purge i Total Product Purge.',
);
assert(
  adminCatalogDeletionDetailV10.includes('<ProductDeletionAdmin')
    && adminCatalogDeletionDetailV10.includes('productId={product.id}')
    && adminCatalogDeletionUiV10.includes('Trajno obriši artikal')
    && adminCatalogDeletionUiV10.includes('Total Product Purge — SuperAdmin')
    && adminCatalogDeletionUiV10.includes('total_purge_irreversible_confirmation')
    && adminCatalogDeletionUiV10.includes('total_retention_acknowledged: true')
    && adminCatalogDeletionUiV10.includes('ConfirmAction')
    && adminCatalogDeletionUiV10.includes('Potvrdi trajno brisanje')
    && adminCatalogDeletionUiV10.includes('Potvrdi Total Product Purge')
    && adminCatalogDeletionUiV10.includes('loading={purgeMutation.isPending}')
    && adminCatalogDeletionUiV10.includes('loading={totalPurgeMutation.isPending}'),
  'v1.0 Mobile Product detalj ima postojeći archive/restore plus kontrolisani purge i SuperAdmin Total Product Purge danger-zone workflow.',
);
assert(
  adminCatalogDeletionControllerV10.includes('ProductDeletionService $deletions')
    && adminCatalogDeletionControllerV10.includes('TotalProductPurgeService $purge')
    && adminCatalogDeletionControllerV10.includes('hash_equals((string) $product->sku')
    && adminCatalogDeletionControllerV10.includes("abort_unless($actor->hasRole('superadmin'), 403)")
    && adminCatalogDeletionServiceV10.includes('Artikal ima poslovnu istoriju i ne može trajno da se obriše')
    && adminCatalogTotalPurgeServiceV10.includes("IRREVERSIBLE_CONFIRMATION = 'TRAJNO OBRIŠI SVE TRAGOVE'")
    && adminCatalogTotalPurgeServiceV10.includes('assertDatabaseZero')
    && adminCatalogTotalPurgeServiceV10.includes('assertLiveFilesystemZero'),
  'v1.0 Admin Catalog deletion API reuse-uje postojeće Laravel ProductDeletionService i TotalProductPurgeService ZERO TRACE guardove.',
);
assert(
  adminCatalogDeletionOpenApiV10.includes('/api/v1/admin/catalog/products/{product}/deletion:')
    && adminCatalogDeletionOpenApiV10.includes('/api/v1/admin/catalog/products/{product}/purge:')
    && adminCatalogDeletionOpenApiV10.includes('/api/v1/admin/catalog/products/{product}/total-purge:')
    && adminCatalogDeletionOpenApiV10.includes('total_retention_acknowledged')
    && adminCatalogDeletionOpenApiV10.includes('TRAJNO OBRIŠI SVE TRAGOVE'),
  'OpenAPI dokumentuje ADMIN-CAT-03 deletion readiness, purge i Total Product Purge ugovor.',
);
const adminCatalogDeletionVariantGuardV10 = adminCatalogDeletionApiV10 + adminCatalogDeletionUiV10 + adminCatalogDeletionControllerV10;
assert(
  !/(ProductVariant|product_variant_id|product_variants|variants_enabled)/.test(adminCatalogDeletionVariantGuardV10),
  'v1.0 Admin Catalog purge tok ostaje product-only bez Product Variants contracta.',
);

console.log(`\nUkupno FAIL: ${failures}`);
// MOBILE_V1_0_SYSTEM_HEALTH_MUTATIONS_PARITY_BATCH25
const systemHealthApiV10 = fs.readFileSync(path.join(root, 'src/features/admin/system-health-admin-api.ts'), 'utf8');
const systemHealthUiV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/system-health/index.tsx'), 'utf8');
const systemHealthControllerV10 = fs.readFileSync(path.join(root, '../../cms/current/app/Http/Controllers/Api/V1/Admin/SystemHealthController.php'), 'utf8');
const systemHealthRoutesV10 = fs.readFileSync(path.join(root, '../../cms/current/routes/api.php'), 'utf8');
const systemHealthServiceV10 = fs.readFileSync(path.join(root, '../../cms/current/app/Services/SystemHealthService.php'), 'utf8');
const backupServiceV10 = fs.readFileSync(path.join(root, '../../cms/current/app/Services/BackupService.php'), 'utf8');
const systemHealthOpenApiV10 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
assert(
  systemHealthApiV10.includes('run: () =>')
    && systemHealthApiV10.includes('backup: (databaseOnly = false) =>')
    && systemHealthApiV10.includes('prune: () =>')
    && systemHealthApiV10.includes("'admin/system-health/run'")
    && systemHealthApiV10.includes("'admin/system-health/backup'")
    && systemHealthApiV10.includes("'admin/system-health/prune'"),
  'v1.0 System Health Mobile API pokriva snapshot, backup i retention mutacije relativnim canonical putanjama.',
);
assert(
  systemHealthControllerV10.includes('SystemHealthService $health')
    && systemHealthControllerV10.includes('BackupService $backup')
    && systemHealthControllerV10.includes("'system.health_checked'")
    && systemHealthControllerV10.includes("'backup.created'")
    && systemHealthControllerV10.includes("'backup.pruned'")
    && systemHealthControllerV10.includes("$actor->can('backups.manage')")
    && systemHealthServiceV10.includes('function snapshot')
    && backupServiceV10.includes('function create')
    && backupServiceV10.includes('function prune'),
  'v1.0 System Health API reuse-uje postojeće SystemHealthService i BackupService business guardove bez paralelne logike.',
);
assert(
  systemHealthControllerV10.includes("'backups' => $this->backups()")
    && systemHealthControllerV10.includes("'security_events' => $this->securityEvents()")
    && !systemHealthControllerV10.includes('backup_path')
    && !systemHealthApiV10.includes('backup_path'),
  'v1.0 System Health Mobile state izlaže bezbednu backup/security istoriju bez privatnih backup putanja.',
);
assert(
  systemHealthUiV10.includes('Pokreni proveru i sačuvaj snapshot')
    && systemHealthUiV10.includes('Kreiraj kompletan backup')
    && systemHealthUiV10.includes('Samo baza')
    && systemHealthUiV10.includes('Primeni retention')
    && systemHealthUiV10.includes('Poslednji security događaji')
    && systemHealthUiV10.includes('ConfirmAction')
    && systemHealthUiV10.includes('loading={backupMutation.isPending}')
    && systemHealthUiV10.includes('loading={pruneMutation.isPending}')
    && !systemHealthUiV10.includes('disabled={backupMutation.isPending}')
    && !systemHealthUiV10.includes('disabled={pruneMutation.isPending}'),
  'v1.0 System Health ekran pokriva Web health/backup workflow uz kontrolisani retention confirm i repeatable-action contract.',
);
assert(
  systemHealthRoutesV10.includes("Route::prefix('system-health')")
    && systemHealthRoutesV10.includes("permission:system.health")
    && systemHealthRoutesV10.includes("permission:backups.manage")
    && systemHealthRoutesV10.includes("throttle:backup")
    && systemHealthRoutesV10.includes("name('run')")
    && systemHealthRoutesV10.includes("name('backup')")
    && systemHealthRoutesV10.includes("name('prune')"),
  'v1.0 System Health API rute imaju system.health/backups.manage i odgovarajuće write/backup throttle guardove.',
);
assert(
  systemHealthOpenApiV10.includes('/api/v1/admin/system-health:')
    && systemHealthOpenApiV10.includes('/api/v1/admin/system-health/run:')
    && systemHealthOpenApiV10.includes('/api/v1/admin/system-health/backup:')
    && systemHealthOpenApiV10.includes('/api/v1/admin/system-health/prune:')
    && systemHealthOpenApiV10.includes('AdminSystemHealthBackupItem:')
    && systemHealthOpenApiV10.includes('AdminSystemHealthSecurityEvent:'),
  'OpenAPI dokumentuje kompletan SET-03 System Health GET/run/backup/prune i safe history ugovor.',
);
const systemHealthVariantGuardV10 = systemHealthApiV10 + systemHealthUiV10 + systemHealthControllerV10;
assert(
  !/(ProductVariant|product_variant_id|product_variants|variants_enabled)/.test(systemHealthVariantGuardV10),
  'v1.0 System Health parity ne vraća Product Variants contract.',
);

// MOBILE_V1_0_CUSTOMER_PORTAL_PARITY_BATCH33
const portalApiV10 = fs.readFileSync(path.join(root, 'src/features/portal/portal-api.ts'), 'utf8');
const portalInboxV10 = fs.readFileSync(path.join(root, 'src/app/(app)/portal/messages/index.tsx'), 'utf8');
const portalDetailV10 = fs.readFileSync(path.join(root, 'src/app/(app)/portal/messages/[id].tsx'), 'utf8');
const adminPortalApiV10 = fs.readFileSync(path.join(root, 'src/features/admin/customer-portal-admin-api.ts'), 'utf8');
const adminPortalIndexV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/customer-portal/index.tsx'), 'utf8');
const adminPortalUserV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/customer-portal/[userId].tsx'), 'utf8');
const adminPortalConversationV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/customer-portal/conversations/[id].tsx'), 'utf8');
const customerPortalRoutesV10 = fs.readFileSync(path.join(root, '../../cms/current/routes/api.php'), 'utf8');
const customerPortalWebControllerV10 = fs.readFileSync(path.join(root, '../../cms/current/app/Http/Controllers/Admin/CustomerPortalController.php'), 'utf8');
const customerPortalAdminServiceV10 = fs.readFileSync(path.join(root, '../../cms/current/app/Services/CustomerPortalAdminService.php'), 'utf8');
const customerPortalOpenApiV10 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
const customerPortalHomeV10 = fs.readFileSync(path.join(root, 'src/app/(app)/(tabs)/home.tsx'), 'utf8');
const customerPortalHubV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const customerPortalAdminFoundationApiV10 = fs.readFileSync(path.join(root, 'src/features/admin/admin-api.ts'), 'utf8');
assert(
  portalApiV10.includes("apiRequest<PortalInboxResponse> ('portal/messages')")
    && portalInboxV10.includes("hasFeature('customer_portal')")
    && portalInboxV10.includes('Nova tema')
    && portalDetailV10.includes('Pošalji odgovor'),
  'v1.0 PORTAL-01 Mobile pokriva customer inbox/create/detail/reply kroz relativni canonical API.',
);
assert(
  adminPortalApiV10.includes('admin/customer-portal/users')
    && adminPortalApiV10.includes('/orders/link')
    && adminPortalApiV10.includes('/sessions')
    && adminPortalIndexV10.includes('Kreiraj i pošalji poziv')
    && adminPortalUserV10.includes('Opozovi sve prijave')
    && adminPortalConversationV10.includes('Interna napomena')
    && adminPortalConversationV10.includes('Sačuvaj obradu'),
  'v1.0 PORTAL-ADMIN-01 Mobile pokriva customer create/invite/order-link/session-revoke i conversation workflow.',
);
assert(
  customerPortalRoutesV10.includes("Route::prefix('portal/messages')")
    && customerPortalRoutesV10.includes("Route::get('/customer-portal'")
    && customerPortalRoutesV10.includes("permission:system.manage_users")
    && customerPortalRoutesV10.includes("throttle:portal-messages"),
  'v1.0 Customer Portal API rute čuvaju customer ownership i admin permission/throttle granice.',
);
assert(
  customerPortalWebControllerV10.includes('CustomerPortalAdminService $portal')
    && customerPortalAdminServiceV10.includes('CustomerActivationService')
    && customerPortalAdminServiceV10.includes('PortalSessionService')
    && customerPortalAdminServiceV10.includes('PortalOrderLinkHistory'),
  'v1.0 Customer Portal Web i Mobile write workflow dele isti CustomerPortalAdminService authority.',
);
assert(
  customerPortalHomeV10.includes("route: '/portal/messages' as const")
    && customerPortalHubV10.includes("moduleEnabled('customer_portal')")
    && customerPortalHubV10.includes("router.push('/admin/customer-portal' as Href)")
    && customerPortalAdminFoundationApiV10.includes("| 'customer_portal';"),
  'v1.0 Customer Portal je organizovan u Moje aktivnosti i Admin/Korisnici uz module visibility.',
);
assert(
  customerPortalOpenApiV10.includes('/api/v1/portal/messages:')
    && customerPortalOpenApiV10.includes('/api/v1/admin/customer-portal:')
    && customerPortalOpenApiV10.includes('/api/v1/admin/customer-portal/users/{user}/orders/link:')
    && customerPortalOpenApiV10.includes('/api/v1/admin/customer-portal/conversations/{conversation}:')
    && !customerPortalOpenApiV10.includes('/api/v1/admin/catalog/purchase-costs:    get:')
    && !customerPortalOpenApiV10.includes('AdminModuleSettingKey:      type: string'),
  'OpenAPI dokumentuje PORTAL-01 i PORTAL-ADMIN-01 route surface i popravlja raniji purchase-cost/module-settings line-break drift.',
);
assert(
  !/(ProductVariant|product_variant_id|product_variants|variants_enabled)/.test(
    portalApiV10 + adminPortalApiV10 + customerPortalRoutesV10 + customerPortalAdminServiceV10
  ),
  'v1.0 Customer Portal parity ne vraća Product Variants contract.',
);

// MOBILE_V1_0_USER_GROUPS_PARITY_BATCH34
const userGroupsApiV10 = fs.readFileSync(path.join(root, 'src/features/admin/user-groups-admin-api.ts'), 'utf8');
const userGroupsScreenV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/user-groups/index.tsx'), 'utf8');
const userGroupsHubV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const userGroupsQueryKeysV10 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const userGroupsRoutesV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/routes/api.php'), 'utf8');
const userGroupsApiControllerV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Controllers/Api/V1/Admin/UserGroupController.php'), 'utf8');
const userGroupsWebControllerV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Controllers/Admin/UserGroupController.php'), 'utf8');
const userGroupsServiceV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Services/UserGroupAdminService.php'), 'utf8');
const userGroupsRequestV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Requests/AdminUserGroupRequest.php'), 'utf8');
const userGroupsOpenApiV10 = fs.readFileSync(path.join(projectRoot, 'packages/api-contract/openapi.yaml'), 'utf8');
assert(
  ['list:', 'create:', 'update:', 'remove:'].every((marker) => userGroupsApiV10.includes(marker))
    && userGroupsApiV10.includes('admin/user-groups'),
  'v1.0 USER-02 Mobile API pokriva User Groups list/create/update/delete relativni canonical ugovor.',
);
assert(
  userGroupsScreenV10.includes("can('system.manage_users')")
    && userGroupsScreenV10.includes('Nova grupa pristupa')
    && userGroupsScreenV10.includes('categoryMode')
    && userGroupsScreenV10.includes('permissionIds')
    && userGroupsScreenV10.includes('categoryIds')
    && userGroupsScreenV10.includes('can_delete'),
  'v1.0 USER-02 Mobile ekran pokriva permission, category scope, status, sort i bezbedni delete workflow.',
);
assert(
  userGroupsHubV10.includes("adminMatch('Grupe pristupa')")
    && userGroupsHubV10.includes("router.push('/admin/user-groups' as Href)"),
  'v1.0 USER-02 Admin Hub drži Grupe pristupa u organizovanoj Korisnici sekciji.',
);
assert(
  userGroupsQueryKeysV10.includes('userGroupsRoot:') && userGroupsQueryKeysV10.includes('userGroups:'),
  'v1.0 USER-02 TanStack query keys su centralizovani.',
);
assert(
  userGroupsRoutesV10.includes("Route::get('/user-groups'")
    && userGroupsRoutesV10.includes("Route::post('/user-groups'")
    && userGroupsRoutesV10.includes("Route::put('/user-groups/{userGroup}'")
    && userGroupsRoutesV10.includes("Route::delete('/user-groups/{userGroup}'")
    && userGroupsRoutesV10.includes('permission:system.manage_users'),
  'v1.0 USER-02 API rute dele system.manage_users granicu i puni CRUD surface.',
);
assert(
  userGroupsWebControllerV10.includes('UserGroupAdminService')
    && userGroupsApiControllerV10.includes('UserGroupAdminService')
    && userGroupsWebControllerV10.includes('AdminUserGroupRequest')
    && userGroupsApiControllerV10.includes('AdminUserGroupRequest'),
  'v1.0 USER-02 Web i Mobile API dele isti UserGroupAdminService i AdminUserGroupRequest authority.',
);
assert(
  userGroupsServiceV10.includes("permissions()->sync")
    && userGroupsServiceV10.includes("categories()->sync")
    && userGroupsServiceV10.includes("users()->exists()")
    && userGroupsRequestV10.includes("Rule::in(['all', 'selected', 'none'])")
    && userGroupsRequestV10.includes("Rule::in(['active', 'inactive'])"),
  'v1.0 USER-02 shared servis čuva permission/category sync i blokira brisanje grupe sa korisnicima.',
);
assert(
  userGroupsOpenApiV10.includes('/api/v1/admin/user-groups:')
    && userGroupsOpenApiV10.includes('/api/v1/admin/user-groups/{userGroup}:')
    && userGroupsOpenApiV10.includes('AdminUserGroupInput:')
    && userGroupsOpenApiV10.includes('AdminUserGroupRecord:'),
  'OpenAPI dokumentuje kompletan USER-02 User Groups CRUD ugovor.',
);
assert(
  !/(ProductVariant|product_variant_id|product_variants|variants_enabled)/.test(
    userGroupsApiV10 + userGroupsScreenV10 + userGroupsRoutesV10 + userGroupsApiControllerV10 + userGroupsServiceV10
  ),
  'v1.0 USER-02 parity ne vraća Product Variants contract.',
);
// MOBILE_V1_0_COMMERCIAL_SELLING_RATE_AUTHORITY_BATCH34B
const exchangeRateServiceBatch34B = fs.readFileSync(
  path.join(projectRoot, 'apps/cms/current/app/Services/ExchangeRateService.php'),
  'utf8',
);
const exchangeRateSettingsBatch34B = fs.readFileSync(
  path.join(projectRoot, 'apps/cms/current/app/Services/SettingsService.php'),
  'utf8',
);
const exchangeRateWebBatch34B = fs.readFileSync(
  path.join(projectRoot, 'apps/cms/current/resources/views/admin/settings/exchange-rate.blade.php'),
  'utf8',
);
const exchangeRateMobileBatch34B = fs.readFileSync(
  path.join(root, 'src/app/(app)/admin/exchange-rate/index.tsx'),
  'utf8',
);
assert(
  exchangeRateServiceBatch34B.includes("public const RATE_KIND = 'commercial_sell';")
    && exchangeRateServiceBatch34B.includes('CurrentForeignExchange')
    && exchangeRateServiceBatch34B.includes("private const PROVIDER = 'nbs';")
    && exchangeRateServiceBatch34B.includes('$cells[5]')
    && !exchangeRateServiceBatch34B.includes('frankfurter.dev'),
  'v1.0 glavni EUR/RSD authority je zakljucan na NBS Komercijalni prodajni kurs.',
);
assert(
  exchangeRateSettingsBatch34B.includes("'eur_rsd_provider' => 'nbs'")
    && exchangeRateWebBatch34B.includes('GLAVNI KURS APLIKACIJE')
    && exchangeRateMobileBatch34B.includes('GLAVNI KURS APLIKACIJE')
    && exchangeRateMobileBatch34B.includes('Komercijalni prodajni kurs'),
  'Web i Mobile jasno oznacavaju Komercijalni prodajni kao glavni kurs.',
);


// MOBILE_V1_0_CATALOG_ADVANCED_PARITY_BATCH35
const catalogAdvancedApiV10 = fs.readFileSync(path.join(root, 'src/features/admin/catalog-advanced-admin-api.ts'), 'utf8');
const catalogAdvancedActionsV10 = fs.readFileSync(path.join(root, 'src/features/admin/catalog-advanced-product-actions.tsx'), 'utf8');
const catalogCloneV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/[id]/clone.tsx'), 'utf8');
const catalogBulkV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/bulk/index.tsx'), 'utf8');
const dataQualityApiV10 = fs.readFileSync(path.join(root, 'src/features/admin/data-quality-admin-api.ts'), 'utf8');
const dataQualityExportV10 = fs.readFileSync(path.join(root, 'src/features/admin/data-quality-admin-export.ts'), 'utf8');
const dataQualityScreenV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/catalog/data-quality/index.tsx'), 'utf8');
const catalogAdvancedHubV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const catalogAdvancedQueryV10 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const catalogAdvancedRoutesV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/routes/api.php'), 'utf8');
const catalogAdvancedControllerV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Controllers/Api/V1/Admin/CatalogAdvancedController.php'), 'utf8');
const dataQualityControllerV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Controllers/Api/V1/Admin/DataQualityController.php'), 'utf8');
const catalogAdvancedOpenApiV10 = fs.readFileSync(path.join(projectRoot, 'packages/api-contract/openapi.yaml'), 'utf8');
assert(
  catalogAdvancedApiV10.includes('namePreview:')
    && catalogAdvancedApiV10.includes('clone:')
    && catalogAdvancedApiV10.includes('regenerateName:')
    && catalogCloneV10.includes('Pregledaj naziv iz šablona')
    && catalogAdvancedActionsV10.includes('Kloniraj artikal')
    && catalogAdvancedActionsV10.includes('Regeneriši naziv iz šablona'),
  'v1.0 ADMIN-CAT-04 Mobile pokriva clone, name preview i regenerate-name kroz canonical ProductAdmin/ProductTemplate authority.',
);
assert(
  catalogAdvancedApiV10.includes('bulkOptions:')
    && catalogAdvancedApiV10.includes('bulkPreview:')
    && catalogAdvancedApiV10.includes('bulkExecute:')
    && catalogBulkV10.includes('Masovne izmene')
    && catalogBulkV10.includes('Pregledaj izmene')
    && catalogBulkV10.includes('Izvrši prikazane izmene'),
  'v1.0 ADMIN-CAT-05 Mobile pokriva bulk selection, preview i execute kroz postojeći ProductBulkService.',
);
assert(
  dataQualityApiV10.includes('state:')
    && dataQualityApiV10.includes('repair:')
    && dataQualityExportV10.includes('openAdminDataQualityExport')
    && dataQualityScreenV10.includes('Kvalitet podataka')
    && dataQualityScreenV10.includes('Bezbedna automatska popravka'),
  'v1.0 ADMIN-CAT-11 Mobile pokriva Data Quality audit, safe repair, history i JSON export.',
);
assert(
  catalogAdvancedRoutesV10.includes("Route::post('/products/name-preview'")
    && catalogAdvancedRoutesV10.includes("Route::post('/products/{product}/clone'")
    && catalogAdvancedRoutesV10.includes("Route::post('/products/{product}/regenerate-name'")
    && catalogAdvancedRoutesV10.includes("Route::get('/bulk/options'")
    && catalogAdvancedRoutesV10.includes("Route::post('/bulk/preview'")
    && catalogAdvancedRoutesV10.includes("Route::post('/bulk/execute'"),
  'v1.0 CATALOG_ADVANCED catalog.manage_products API route surface je kompletan.',
);
assert(
  catalogAdvancedRoutesV10.includes("Route::prefix('admin/data-quality')")
    && catalogAdvancedRoutesV10.includes("->middleware('permission:catalog.audit')")
    && dataQualityControllerV10.includes('DataQualityService')
    && dataQualityControllerV10.includes("storeSnapshot($after, 'mobile-repair'"),
  'v1.0 ADMIN-CAT-11 API čuva catalog.audit granicu i shared DataQualityService authority.',
);
assert(
  catalogAdvancedControllerV10.includes('ProductBulkService')
    && catalogAdvancedControllerV10.includes('ProductAdminService')
    && catalogAdvancedControllerV10.includes('ProductTemplateService')
    && catalogAdvancedControllerV10.includes('CatalogAccessService'),
  'v1.0 ADMIN-CAT-04/05 API reuse-uje postojeće Laravel catalog authority servise bez paralelne poslovne logike.',
);
assert(
  catalogAdvancedHubV10.includes("adminMatch('Masovne izmene')")
    && catalogAdvancedHubV10.includes("adminMatch('Kvalitet podataka')")
    && catalogAdvancedHubV10.includes("router.push('/admin/catalog/bulk' as Href)")
    && catalogAdvancedHubV10.includes("router.push('/admin/catalog/data-quality' as Href)"),
  'v1.0 CATALOG_ADVANCED opcije ostaju organizovane u Katalog i lager Admin grupi.',
);
assert(
  catalogAdvancedQueryV10.includes('catalogAdvancedRoot:')
    && catalogAdvancedQueryV10.includes('catalogBulkOptions:')
    && catalogAdvancedQueryV10.includes('dataQuality:'),
  'v1.0 CATALOG_ADVANCED TanStack query keys su centralizovani.',
);
assert(
  catalogAdvancedOpenApiV10.includes('/api/v1/admin/catalog/products/name-preview:')
    && catalogAdvancedOpenApiV10.includes('/api/v1/admin/catalog/products/{product}/clone:')
    && catalogAdvancedOpenApiV10.includes('/api/v1/admin/catalog/bulk/execute:')
    && catalogAdvancedOpenApiV10.includes('/api/v1/admin/data-quality:'),
  'OpenAPI dokumentuje kompletan CATALOG_ADVANCED route surface.',
);
assert(
  !/(ProductVariant|product_variant_id|product_variants|variants_enabled)/.test(
    catalogAdvancedApiV10 + catalogAdvancedActionsV10 + catalogCloneV10 + catalogBulkV10 + dataQualityApiV10 + dataQualityScreenV10 + catalogAdvancedControllerV10 + dataQualityControllerV10
  ),
  'v1.0 CATALOG_ADVANCED parity ne vraća Product Variants contract.',
);
// MOBILE_V1_0_ORDER_REPORT_OPS_PARITY_BATCH36
const orderReportOpsOrdersApiV10 = fs.readFileSync(path.join(root, 'src/features/admin/orders-admin-api.ts'), 'utf8');
const orderReportOpsArchiveUiV10 = fs.readFileSync(path.join(root, 'src/features/admin/order-archive-admin.tsx'), 'utf8');
const orderReportOpsArchiveScreenV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/orders/archived.tsx'), 'utf8');
const orderReportOpsOrderListV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/orders/index.tsx'), 'utf8');
const orderReportOpsOrderDetailV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/orders/[id].tsx'), 'utf8');
const orderReportOpsReportsApiV10 = fs.readFileSync(path.join(root, 'src/features/admin/reports-admin-api.ts'), 'utf8');
const orderReportOpsReportsUiV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/reports/index.tsx'), 'utf8');
const orderReportOpsExportV10 = fs.readFileSync(path.join(root, 'src/features/admin/operational-reports-admin-export.ts'), 'utf8');
const orderReportOpsQueryV10 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const orderReportOpsOpenApiV10 = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
const orderReportOpsApiRoutesV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/routes/api.php'), 'utf8');
const orderReportOpsArchiveControllerV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Controllers/Api/V1/Admin/OrderArchiveController.php'), 'utf8');
const orderReportOpsArchiveServiceV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Services/OrderArchiveService.php'), 'utf8');
const orderReportOpsReportServiceV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Services/OrderReportService.php'), 'utf8');
const orderReportOpsWebReportV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Controllers/Admin/ReportController.php'), 'utf8');
const orderReportOpsApiReportV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Controllers/Api/V1/Admin/ReportController.php'), 'utf8');
assert(
  orderReportOpsOrdersApiV10.includes('admin/orders/archived')
    && orderReportOpsOrdersApiV10.includes('restoreArchived:')
    && orderReportOpsOrdersApiV10.includes('purgeArchived:'),
  'v1.0 ADMIN-ORDER-02 Mobile API pokriva archived list, archive, restore i purge ugovor.',
);
assert(
  orderReportOpsArchiveScreenV10.includes('Arhivirane porudžbine')
    && orderReportOpsArchiveScreenV10.includes('Vrati iz arhive')
    && orderReportOpsArchiveScreenV10.includes('Operativni purge')
    && orderReportOpsArchiveUiV10.includes('Razlog arhiviranja'),
  'v1.0 ADMIN-ORDER-02 Mobile UI pokriva arhivu, restore i SuperAdmin purge sa kontrolisanom potvrdom.',
);
assert(
  orderReportOpsOrderListV10.includes("router.push('/admin/orders/archived' as Href)")
    && orderReportOpsOrderDetailV10.includes('AdminOrderArchiveActions'),
  'v1.0 ADMIN-ORDER-02 ostaje organizovan u postojećem Prodaja/Porudžbine toku.',
);
assert(
  orderReportOpsApiRoutesV10.includes("Route::get('/orders/archived'")
    && orderReportOpsApiRoutesV10.includes("Route::post('/orders/{order}/archive'")
    && orderReportOpsApiRoutesV10.includes("Route::post('/orders/archived/{orderId}/restore'")
    && orderReportOpsApiRoutesV10.includes("Route::delete('/orders/archived/{orderId}/purge'"),
  'v1.0 ADMIN-ORDER-02 API route surface je kompletan.',
);
assert(
  orderReportOpsArchiveControllerV10.includes('OrderArchiveService')
    && orderReportOpsArchiveServiceV10.includes('paginateArchived')
    && orderReportOpsArchiveServiceV10.includes('purgeById')
    && orderReportOpsArchiveServiceV10.includes("hasRole('superadmin')"),
  'v1.0 ADMIN-ORDER-02 reuse-uje canonical OrderArchiveService i čuva SuperAdmin-only purge.',
);
assert(
  orderReportOpsReportsApiV10.includes("'orders-pdf'")
    && orderReportOpsReportsApiV10.includes("'payments-csv'")
    && orderReportOpsReportsApiV10.includes("'inventory-csv'")
    && orderReportOpsReportsUiV10.includes('Operativni izvozi')
    && orderReportOpsExportV10.includes('apiDownload'),
  'v1.0 REPORT-02 Mobile pokriva orders PDF/CSV, payments CSV i inventory CSV kroz secure Bearer download.',
);
assert(
  orderReportOpsApiRoutesV10.includes("Route::get('/reports/orders.pdf'")
    && orderReportOpsApiRoutesV10.includes("Route::get('/reports/orders.csv'")
    && orderReportOpsApiRoutesV10.includes("Route::get('/reports/payments.csv'")
    && orderReportOpsApiRoutesV10.includes("Route::get('/reports/inventory.csv'"),
  'v1.0 REPORT-02 API route surface je kompletan.',
);
assert(
  orderReportOpsReportServiceV10.includes('public function paymentsCsv(User $user): string')
    && orderReportOpsReportServiceV10.includes('public function inventoryCsv(): string')
    && orderReportOpsApiReportV10.includes('$reports->paymentsCsv($user)')
    && orderReportOpsApiReportV10.includes('$reports->inventoryCsv()')
    && orderReportOpsWebReportV10.includes('$reports->paymentsCsv($request->user())')
    && orderReportOpsWebReportV10.includes('$reports->inventoryCsv()'),
  'v1.0 REPORT-02 Web i Mobile API dele isti OrderReportService export authority.',
);
assert(
  orderReportOpsQueryV10.includes('adminOrdersRoot:')
    && orderReportOpsQueryV10.includes('adminOrderArchives:'),
  'v1.0 ORDER_REPORT_OPS TanStack query keys su centralizovani.',
);
assert(
  orderReportOpsOpenApiV10.includes('/api/v1/admin/orders/archived:')
    && orderReportOpsOpenApiV10.includes('/api/v1/admin/orders/{order}/archive:')
    && orderReportOpsOpenApiV10.includes('/api/v1/admin/reports/orders.pdf:')
    && orderReportOpsOpenApiV10.includes('/api/v1/admin/reports/inventory.csv:'),
  'OpenAPI dokumentuje kompletan ORDER_REPORT_OPS route surface.',
);
assert(
  !/(ProductVariant|product_variant_id|product_variants|variants_enabled)/.test(
    orderReportOpsOrdersApiV10 + orderReportOpsArchiveUiV10 + orderReportOpsArchiveScreenV10 + orderReportOpsReportsApiV10 + orderReportOpsReportsUiV10 + orderReportOpsExportV10 + orderReportOpsArchiveControllerV10
  ),
  'v1.0 ORDER_REPORT_OPS parity ne vraća Product Variants contract.',
);
// MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37
const systemSettingsApiV10 = fs.readFileSync(path.join(root, 'src/features/admin/system-settings-admin-api.ts'), 'utf8');
const systemSettingsHubV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/settings/index.tsx'), 'utf8');
const systemSettingsAutomationV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/settings/automation.tsx'), 'utf8');
const systemSettingsTurnstileV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/settings/turnstile.tsx'), 'utf8');
const systemSettingsAppearanceV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/settings/appearance.tsx'), 'utf8');
const systemSettingsOrderEmailsV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/settings/order-emails.tsx'), 'utf8');
const systemSettingsDocumentsV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/settings/documents.tsx'), 'utf8');
const systemSettingsBankAccountsV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/settings/bank-accounts.tsx'), 'utf8');
const systemSettingsAdminHubV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const systemSettingsQueryV10 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const systemSettingsRoutesV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/routes/api.php'), 'utf8');
const systemSettingsControllerV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Controllers/Api/V1/Admin/SystemSettingsController.php'), 'utf8');
const systemSettingsOpenApiV10 = fs.readFileSync(path.join(projectRoot, 'packages/api-contract/openapi.yaml'), 'utf8');
const systemSettingsWebAutomationV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Controllers/Admin/AutomationController.php'), 'utf8');
const systemSettingsWebAppearanceV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Controllers/Admin/SiteAppearanceController.php'), 'utf8');
const systemSettingsWebDocumentsV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Controllers/Admin/DocumentSettingsController.php'), 'utf8');
const systemSettingsWebBankV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Controllers/Admin/BankAccountController.php'), 'utf8');
assert(
  systemSettingsApiV10.includes('MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37_V3_EXPO_FILE_PICKER_OVERLOAD')
    && systemSettingsApiV10.includes('multipleFiles: true')
    && systemSettingsApiV10.includes('File.pickFileAsync({ mimeTypes: limits.mime_types })')
    && !systemSettingsApiV10.includes('File.pickFileAsync({ mimeTypes: limits.mime_types, multipleFiles })'),
  'v1.0 SYSTEM_SETTINGS file picker koristi Expo SDK57 literal overload za single/multiple izbor.',
);
assert(
  systemSettingsAutomationV10.includes('MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37_SET02')
    && systemSettingsAutomationV10.includes('apiAdminSystemSettings.automation.run')
    && systemSettingsAutomationV10.includes('resolveAlert'),
  'v1.0 SET-02 Mobile pokriva automation settings, manual run i resolve alert kroz canonical automation authority.',
);
assert(
  systemSettingsTurnstileV10.includes('MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37_SET04')
    && systemSettingsApiV10.includes('secret_configured')
    && !systemSettingsApiV10.includes('data.turnstile_secret_key'),
  'v1.0 SET-04 Mobile pokriva Turnstile bez izlaganja secret vrednosti.',
);
assert(
  systemSettingsAppearanceV10.includes('MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37_SET05')
    && systemSettingsAppearanceV10.includes('login_slideshow_images')
    && systemSettingsAppearanceV10.includes('login-slide-')
    && systemSettingsApiV10.includes('apiExpoMultipartRequest'),
  'v1.0 SET-05 Mobile pokriva brending, footer i SuperAdmin login background/slideshow asset workflow.',
);
assert(
  systemSettingsOrderEmailsV10.includes('MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37_SET08')
    && systemSettingsOrderEmailsV10.includes('Pošalji outbox sada')
    && systemSettingsOrderEmailsV10.includes('Ponovi sve failed poruke'),
  'v1.0 SET-08 Mobile pokriva order e-mail settings, dispatch i retry workflow.',
);
assert(
  systemSettingsDocumentsV10.includes('MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37_SET09')
    && systemSettingsDocumentsV10.includes('documents_logo')
    && systemSettingsDocumentsV10.includes('removeLogo'),
  'v1.0 SET-09 Mobile pokriva poslovne dokumente i kontrolisani PDF logo lifecycle.',
);
assert(
  systemSettingsBankAccountsV10.includes('MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37_SET10')
    && systemSettingsBankAccountsV10.includes('apiAdminSystemSettings.bankAccounts.create')
    && systemSettingsBankAccountsV10.includes('apiAdminSystemSettings.bankAccounts.update')
    && systemSettingsBankAccountsV10.includes('apiAdminSystemSettings.bankAccounts.remove'),
  'v1.0 SET-10 Mobile pokriva Bank Accounts CRUD uz canonical server MOD97 validaciju.',
);
assert(
  systemSettingsHubV10.includes("'/admin/settings/automation'")
    && systemSettingsHubV10.includes("'/admin/settings/turnstile'")
    && systemSettingsHubV10.includes("'/admin/settings/appearance'")
    && systemSettingsHubV10.includes("'/admin/settings/order-emails'")
    && systemSettingsHubV10.includes("'/admin/settings/documents'")
    && systemSettingsHubV10.includes("'/admin/settings/bank-accounts'")
    && systemSettingsAdminHubV10.includes("router.push('/admin/settings' as Href)"),
  'v1.0 SYSTEM_SETTINGS opcije su organizovane kroz jedan Sistem hub bez zagušenja glavne administracije.',
);
assert(
  ['systemSettingsAutomation:', 'systemSettingsTurnstile:', 'systemSettingsAppearance:', 'systemSettingsOrderEmails:', 'systemSettingsDocuments:', 'systemSettingsBankAccounts:'].every((marker) => systemSettingsQueryV10.includes(marker)),
  'v1.0 SYSTEM_SETTINGS TanStack query keys su centralizovani.',
);
assert(
  systemSettingsRoutesV10.includes("Route::prefix('system-settings')")
    && systemSettingsRoutesV10.includes('permission:system.manage_settings')
    && systemSettingsRoutesV10.includes('permission:automation.manage')
    && (systemSettingsRoutesV10.match(/AdminSystemSettingsController::class/g) || []).length === 20,
  'v1.0 SYSTEM_SETTINGS API route surface ima 20 kontrolisanih operacija sa permission/throttle granicama.',
);
assert(
  systemSettingsControllerV10.includes('WebSiteAppearanceController')
    && systemSettingsControllerV10.includes('WebDocumentSettingsController')
    && systemSettingsControllerV10.includes('WebBankAccountController')
    && systemSettingsControllerV10.includes('OperationalAutomationService')
    && systemSettingsControllerV10.includes('OrderEmailDispatcher')
    && systemSettingsWebAutomationV10.includes('OperationalAutomationService')
    && systemSettingsWebAppearanceV10.includes("storePublicly('site-assets'")
    && systemSettingsWebDocumentsV10.includes('storePdfLogo')
    && systemSettingsWebBankV10.includes('passesMod97'),
  'v1.0 SYSTEM_SETTINGS API reuse-uje postojeće Web/service authority-je umesto paralelne poslovne logike.',
);
assert(
  ['/api/v1/admin/system-settings/automation:', '/api/v1/admin/system-settings/turnstile:', '/api/v1/admin/system-settings/appearance:', '/api/v1/admin/system-settings/order-emails:', '/api/v1/admin/system-settings/documents:', '/api/v1/admin/system-settings/bank-accounts:'].every((pathMarker) => systemSettingsOpenApiV10.includes(pathMarker))
    && systemSettingsOpenApiV10.includes('operationId: deleteAdminBankAccount')
    && systemSettingsOpenApiV10.includes('multipart/form-data:'),
  'OpenAPI dokumentuje kompletan SYSTEM_SETTINGS route surface.',
);
assert(
  !/(ProductVariant|product_variant_id|product_variants|variants_enabled)/.test(
    systemSettingsApiV10 + systemSettingsHubV10 + systemSettingsAutomationV10 + systemSettingsTurnstileV10 + systemSettingsAppearanceV10 + systemSettingsOrderEmailsV10 + systemSettingsDocumentsV10 + systemSettingsBankAccountsV10 + systemSettingsControllerV10
  ),
  'v1.0 SYSTEM_SETTINGS parity ne vraća Product Variants contract.',
);

// MOBILE_V1_0_GLOBAL_SEARCH_PARITY_BATCH38
const globalSearchApiV10 = fs.readFileSync(path.join(root, 'src/features/admin/global-search-admin-api.ts'), 'utf8');
const globalSearchScreenV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/search.tsx'), 'utf8');
const globalSearchAdminHubV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/index.tsx'), 'utf8');
const globalSearchUsersV10 = fs.readFileSync(path.join(root, 'src/app/(app)/admin/users/index.tsx'), 'utf8');
const globalSearchQueryV10 = fs.readFileSync(path.join(root, 'src/features/admin/admin-query-keys.ts'), 'utf8');
const globalSearchRoutesV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/routes/api.php'), 'utf8');
const globalSearchControllerV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Http/Controllers/Api/V1/GlobalSearchController.php'), 'utf8');
const globalSearchAuthorityV10 = fs.readFileSync(path.join(projectRoot, 'apps/cms/current/app/Services/GlobalCommandSearchService.php'), 'utf8');
const globalSearchOpenApiV10 = fs.readFileSync(path.join(projectRoot, 'packages/api-contract/openapi.yaml'), 'utf8');
assert(
  globalSearchApiV10.includes("`global-search?q=${encodeURIComponent(q.trim().slice(0, 80))}`")
    && globalSearchApiV10.includes('mobile_path: string;')
    && globalSearchApiV10.includes('AdminGlobalSearchSection'),
  'v1.0 CAT-02 Mobile API koristi relativni centralizovani global-search ugovor i tipizovane Mobile targete.',
);
assert(
  globalSearchScreenV10.includes('MOBILE_V1_0_GLOBAL_SEARCH_PARITY_BATCH38')
    && globalSearchScreenV10.includes('adminQueryKeys.globalSearch(searchQuery)')
    && globalSearchScreenV10.includes('searchQuery.length >= 2')
    && globalSearchScreenV10.includes('setTimeout(() => setSearchQuery(normalized), 250)')
    && globalSearchScreenV10.includes('router.push(item.mobile_path as Href)')
    && globalSearchScreenV10.includes('data?.sections.map'),
  'v1.0 CAT-02 Mobile ekran pokriva debounce, grouped rezultate i navigaciju kroz server-driven Mobile target.',
);
assert(
  globalSearchAdminHubV10.includes("router.push('/admin/search' as Href)")
    && globalSearchAdminHubV10.includes('Globalna pretraga')
    && globalSearchAdminHubV10.includes('Pretraži sve module'),
  'v1.0 CAT-02 Global Search je organizovan kao jedna jasna Admin quick-action destinacija bez zagušenja poslovnih sekcija.',
);
assert(
  globalSearchUsersV10.includes('useLocalSearchParams')
    && globalSearchUsersV10.includes('initialQuery')
    && globalSearchUsersV10.includes("...(initialQuery ? { q: initialQuery } : {})"),
  'v1.0 CAT-02 user rezultat otvara postojeći User Manager sa primenjenim q filterom.',
);
assert(
  globalSearchQueryV10.includes("globalSearch: (q: string) => ['admin', 'global-search', q] as const"),
  'v1.0 CAT-02 TanStack query key je centralizovan.',
);
assert(
  globalSearchRoutesV10.includes('GlobalSearchController')
    && globalSearchRoutesV10.includes("Route::get('/global-search', [GlobalSearchController::class, 'search'])")
    && globalSearchRoutesV10.includes("->middleware('throttle:120,1')"),
  'v1.0 CAT-02 API ruta je auth/active nasledjena i čuva postojeći Web search throttle.',
);
assert(
  globalSearchControllerV10.includes('GlobalCommandSearchService $search')
    && globalSearchControllerV10.includes('$search->search($actor, $query, 5)')
    && globalSearchControllerV10.includes("unset($item['url']);")
    && globalSearchControllerV10.includes("$item['mobile_path'] = $mobilePath;")
    && globalSearchControllerV10.includes("'/product/'")
    && globalSearchControllerV10.includes("'/admin/users?q='")
    && globalSearchAuthorityV10.includes('final class GlobalCommandSearchService')
    && globalSearchAuthorityV10.includes('$this->catalog')
    && globalSearchAuthorityV10.includes('$this->orders->applyManagedScope')
    && globalSearchAuthorityV10.includes('$this->afterSalesAccess->applyVisibleScope'),
  'v1.0 CAT-02 Mobile API reuse-uje postojeći GlobalCommandSearchService authority i samo adaptira Web URL u Mobile target.',
);
assert(
  globalSearchOpenApiV10.includes('/api/v1/global-search:')
    && globalSearchOpenApiV10.includes('operationId: searchGlobalCommandSpace')
    && globalSearchOpenApiV10.includes('mobile_path:')
    && globalSearchOpenApiV10.includes('Web URL is intentionally omitted'),
  'OpenAPI dokumentuje kompletan CAT-02 permission-aware Global Search ugovor.',
);
assert(
  !/(ProductVariant|product_variant_id|product_variants|variants_enabled)/.test(
    globalSearchApiV10 + globalSearchScreenV10 + globalSearchControllerV10
  ),
  'v1.0 CAT-02 Global Search parity ne vraća Product Variants contract.',
);

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
