import fs from 'node:fs';
import path from 'node:path';
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

for (const jsonFile of ['package.json', 'eas.json']) {
  try {
    JSON.parse(fs.readFileSync(path.join(root, jsonFile), 'utf8'));
    pass(`${jsonFile} je validan JSON.`);
  } catch (error) {
    fail(`${jsonFile} nije validan JSON: ${error.message}`);
  }
}

const packageJson = JSON.parse(fs.readFileSync(path.join(root, 'package.json'), 'utf8'));
assert(packageJson.dependencies?.expo === '~57.0.10', 'Expo SDK 57 verzija prati zvanični template.');
assert(packageJson.dependencies?.['react-native'] === '0.86.2', 'React Native verzija prati Expo SDK 57 template.');
assert(packageJson.dependencies?.['expo-router'] === '~57.0.10', 'Expo Router verzija je zaključana.');
assert(packageJson.dependencies?.['expo-dev-client'] === '~57.0.7', 'Expo development client je uključen.');
assert(Boolean(packageJson.dependencies?.['expo-secure-store']), 'SecureStore zavisnost postoji.');
assert(Boolean(packageJson.dependencies?.['@tanstack/react-query']), 'TanStack Query zavisnost postoji.');
assert(packageJson.engines?.node === '>=22.13.0', 'Minimalna Node.js verzija odgovara SDK 57 zahtevu.');
assert(packageJson.version === '0.4.0', 'Aplikaciona package verzija je 0.4.0.');
assert(packageJson.dependencies?.['expo-notifications'] === '~57.0.6', 'expo-notifications prati SDK 57 preporučenu verziju.');
assert(packageJson.dependencies?.['expo-symbols'] === '~57.0.2', 'Expo Symbols je uključen za native Material/SF ikonice.');
assert(packageJson.dependencies?.['react-native-nitro-google-signin'] === '1.0.2', 'Moderni Google Credential Manager bridge je uključen.');
assert(packageJson.dependencies?.['react-native-nitro-modules'] === '0.36.1', 'Nitro Modules runtime je pinovan.');
assert(packageJson.dependencies?.tamagui === '2.6.0', 'Tamagui 2 runtime je pinovan.');
assert(packageJson.dependencies?.['@tamagui/config'] === '2.6.0', 'Tamagui Config v5 paket je pinovan.');
assert(packageJson.dependencies?.['@tamagui/animations-reanimated'] === '2.6.0', 'Tamagui Reanimated driver je pinovan.');

const sourceFiles = [];
function walk(directory) {
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const full = path.join(directory, entry.name);
    if (entry.isDirectory()) walk(full);
    else if (/\.(ts|tsx)$/.test(entry.name)) sourceFiles.push(full);
  }
}
walk(path.join(root, 'src'));

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
for (const endpoint of ['auth/token', 'auth/google', 'bootstrap', 'catalog/filters', 'products', 'orders/options', 'Idempotency-Key', 'orders', 'notifications', 'devices', 'me/notification-preferences', 'PATCH']) {
  assert(endpoints.includes(endpoint), `API klijent koristi ${endpoint} ugovor.`);
}


const cart = fs.readFileSync(path.join(root, 'src/features/cart/cart-provider.tsx'), 'utf8');
assert(cart.includes('productId') && cart.includes('variantId') && cart.includes('quantity'), 'Lokalna korpa čuva proizvod, varijantu i količinu.');
assert(cart.includes("status === 'anonymous'") && cart.includes('setItems([])'), 'Korpa se čisti pri odjavi/promeni korisnika.');

const checkout = fs.readFileSync(path.join(root, 'src/app/(app)/checkout.tsx'), 'utf8');
assert(checkout.includes('lastSubmission') && checkout.includes('Crypto.randomUUID'), 'Checkout čuva stabilan idempotency ključ za retry istog payload-a.');
assert(checkout.includes("paymentMethod === 'bank_transfer'") && checkout.includes('bankAccountId'), 'Checkout podržava uslovni izbor računa za bank transfer.');

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


const googleAuth = fs.readFileSync(path.join(root, 'src/features/auth/google-auth.ts'), 'utf8');
assert(googleAuth.includes("webClientId: 'autoDetect'") && googleAuth.includes('getGoogleIdToken'), 'Google Sign-In koristi web client ID iz google-services.json i vraća ID token backendu.');
assert(googleAuth.includes('createAccount') && googleAuth.includes('presentExplicitSignIn'), 'Google login ima saved-account, registration/account-picker i explicit fallback tok.');
const tabs = fs.readFileSync(path.join(root, 'src/app/(app)/(tabs)/_layout.tsx'), 'utf8');
assert(tabs.includes('focused') && tabs.includes('primaryContainer'), 'Bottom navigation ima Material 3 tonalni aktivni indikator.');
const glyph = fs.readFileSync(path.join(root, 'src/components/ui/glyph.tsx'), 'utf8');
assert(glyph.includes('expo-symbols') && glyph.includes('SymbolView'), 'UI koristi native Expo Symbols umesto tekstualnih pseudo-ikonica.');

const openapi = fs.readFileSync(path.join(root, 'docs/openapi.yaml'), 'utf8');
for (const route of ['/auth/token', '/auth/google', '/bootstrap', '/catalog/filters', '/products', '/orders/options', 'Idempotency-Key', '/orders', '/notifications', '/devices']) {
  assert(openapi.includes(route), `OpenAPI kopija sadrži ${route}.`);
}

const appConfig = fs.readFileSync(path.join(root, 'app.config.js'), 'utf8');
assert(appConfig.includes("scheme: 'ald1n'"), 'Deep-link scheme je postavljen.');
assert(appConfig.includes('com.ald1n.mobile'), 'Android/iOS identifikatori su postavljeni.');
assert(appConfig.includes('typedRoutes: true'), 'Expo Router typed routes su uključene.');
assert(appConfig.includes('EAS_PROJECT_ID') && appConfig.includes('projectId'), 'Dinamički EAS project ID je podržan.');
assert(appConfig.includes("version: '0.4.0'"), 'Expo app verzija je 0.4.0.');
assert(appConfig.includes('google-services.json') && appConfig.includes('googleServicesFile'), 'Android config podržava Firebase google-services.json kada postoji.');
assert(appConfig.includes('react-native-nitro-google-signin'), 'App config uključuje Google Sign-In plugin kada je Firebase config prisutan.');

const rootLayoutSource = fs.readFileSync(path.join(root, 'src/app/_layout.tsx'), 'utf8');
assert(
  rootLayoutSource.includes('TamaguiProvider') &&
  rootLayoutSource.includes('tamaguiConfig'),
  'TamaguiProvider je povezan na root aplikacije.'
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

console.log(`\nUkupno FAIL: ${failures}`);
process.exit(failures === 0 ? 0 : 1);
