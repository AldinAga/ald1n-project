import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const mobile = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const read = (filename) => readFileSync(path.join(mobile, filename), 'utf8');
const pkg = JSON.parse(read('package.json'));
const lock = JSON.parse(read('package-lock.json'));
const iosReadiness = read('scripts/ios-release-readiness.mjs');
const googleAuth = read('src/features/auth/google-auth.ts');
const login = read('src/app/(auth)/login.tsx');
const appConfig = read('app.config.js');
const eas = JSON.parse(read('eas.json'));

test('M17 Kotlin-blocking Google native bridge is exact v2.3.1 with locked npm tarball', () => {
  assert.equal(pkg.dependencies.expo, '~58.0.25');
  assert.equal(pkg.dependencies['react-native'], '0.88.1');
  assert.equal(pkg.dependencies['react-native-nitro-google-signin'], '2.3.1');
  assert.equal(pkg.dependencies['react-native-nitro-modules'], '0.37.1');
  assert.equal(lock.packages[''].dependencies['react-native-nitro-google-signin'], '2.3.1');
  assert.equal(lock.packages['node_modules/react-native-nitro-google-signin'].version, '2.3.1');
  assert.equal(lock.packages['node_modules/react-native-nitro-google-signin'].integrity,
    'sha512-txDdR3KtJR83WY50OpMj9ndPHmhNJPzX6LE5yqvMOBk5O8NzAaV5OUmrUx0Lm2FdqP+s/Q3Q1lTqdYhe3u1SNA==');
});

test('Native auth iOS readiness remains fail-closed and tracks exact 2.3.1 bridge', () => {
  assert.ok(iosReadiness.includes("pkg?.dependencies?.['react-native-nitro-google-signin'] === '2.3.1'"));
  assert.ok(iosReadiness.includes("'IOS_APP_OR_NATIVE_AUTH_VERSION_DRIFT'"));
  for (const gate of ['IOS_GOOGLE_PLIST_MISSING', 'IOS_GOOGLE_REVERSED_CLIENT_ID_MISMATCH',
    'IOS_GOOGLE_WEB_CLIENT_ID_MISMATCH', 'IOS_NATIVE_GOOGLE_PLUGIN_MISSING']) {
    assert.ok(iosReadiness.includes(gate), gate);
  }
  assert.ok(googleAuth.includes('GoogleOneTapSignIn.checkPlayServices()'));
  assert.ok(googleAuth.includes('GoogleOneTapSignIn.signIn()'));
  assert.ok(googleAuth.includes('GoogleOneTapSignIn.createAccount()'));
  assert.ok(googleAuth.includes('GoogleOneTapSignIn.presentExplicitSignIn()'));
  assert.ok(login.includes('<GoogleSignInButton'));
  assert.ok(login.includes('signInBehavior="none"'));
});

test('Native source upgrade cannot authorize EAS build, OTA or Play', () => {
  assert.equal(pkg.version, '1.0.0');
  assert.equal(pkg.packageManager, 'npm@12.2.1');
  assert.equal(eas.cli.version, '27.2.0');
  assert.equal(eas.cli.appVersionSource, 'remote');
  assert.equal(eas.build.production.channel, 'production');
  assert.equal(pkg.scripts['build:android:production'], 'node scripts/build25-release-controller.mjs');
  assert.ok(appConfig.includes("'1.0.0-build17'"));
  assert.ok(appConfig.includes("'1.5.0-native2'"));
  assert.ok(appConfig.includes("ALD1N_V15_RELEASE_GATE === 'approved'"));
});
