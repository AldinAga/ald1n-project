// BATCH685_IOS_FIREBASE_PROFILE_SPLIT
// Read-only, fail-closed OAuth and profile preflight: never print OAuth IDs or raw plist.
import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const DEFAULT_ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const GOOGLE_SUFFIX = '.apps.googleusercontent.com';
const EXPECTED_API = 'https://cms.ald1n.com/api/v1';
const PREVIEW_FILE = './GoogleService-Info.plist';
const EXTERNAL_FILE_ENV = 'ALD1N_IOS_GOOGLE_SERVICES_FILE';

function plistString(plist, key) {
  if (typeof plist !== 'string' || !/^[A-Z_]+$/.test(key)) return null;
  const re = new RegExp('<key>\\s*' + key + '\\s*</key>\\s*<string>\\s*([^<]+?)\\s*</string>', 'g');
  const values = [...plist.matchAll(re)];
  return values.length === 1 ? values[0][1].trim() : null;
}

function externalFile(pathValue, root) {
  if (!pathValue || !path.isAbsolute(pathValue)) return false;
  try {
    const realFile = fs.realpathSync(pathValue);
    const realRoot = fs.realpathSync(root);
    const relative = path.relative(realRoot, realFile);
    return fs.statSync(realFile).isFile()
      && (relative === '..' || relative.startsWith('..' + path.sep));
  } catch {
    return false;
  }
}

export function assessIosReadiness({
  profile, appConfig, eas, pkg, plist, previewPlist,
  googleAuthSource, filesPresent = true, root = DEFAULT_ROOT,
}) {
  const blockers = [];
  const requireGate = (passed, code) => { if (!passed) blockers.push(code); };
  const supported = ['preview', 'production', 'development', 'ios-simulator'].includes(profile);
  const effectiveAppEnv = profile === 'ios-simulator' ? 'preview' : profile;
  const expectedBundleId = supported ? 'com.ald1n.mobile' + (effectiveAppEnv === 'production' ? '' : '.' + effectiveAppEnv) : null;
  const declaredProfile = eas?.build?.[profile];
  const appBundleId = appConfig?.ios?.bundleIdentifier ?? null;
  const selectedFile = appConfig?.ios?.googleServicesFile ?? null;
  const plistBundleId = plistString(plist, 'BUNDLE_ID');
  const clientId = plistString(plist, 'CLIENT_ID');
  const reversed = plistString(plist, 'REVERSED_CLIENT_ID');
  const appId = plistString(plist, 'GOOGLE_APP_ID');
  const projectId = plistString(plist, 'PROJECT_ID');
  const webId = plistString(plist, 'WEB_CLIENT_ID');
  const webSourceId = googleAuthSource?.match(/webClientId:\s*'([^']+)'/)?.[1] ?? null;
  const expectedReversed = clientId?.endsWith(GOOGLE_SUFFIX)
    ? 'com.googleusercontent.apps.' + clientId.slice(0, -GOOGLE_SUFFIX.length)
    : null;
  const plugin = (appConfig?.plugins || []).find(
    (entry) => Array.isArray(entry) && entry[0] === 'react-native-nitro-google-signin',
  );

  requireGate(supported, 'IOS_UNKNOWN_BUILD_PROFILE');
  requireGate(Boolean(declaredProfile), 'IOS_EAS_PROFILE_MISSING');
  requireGate(declaredProfile?.channel === effectiveAppEnv, 'IOS_EAS_CHANNEL_PROFILE_MISMATCH');
  requireGate(declaredProfile?.env?.EXPO_PUBLIC_APP_ENV === effectiveAppEnv, 'IOS_EAS_APP_ENV_MISMATCH');
  if (profile === 'ios-simulator') {
    requireGate(declaredProfile?.ios?.simulator === true && declaredProfile?.distribution === 'internal', 'IOS_SIMULATOR_ONLY_PROFILE_REQUIRED');
    requireGate(declaredProfile?.autoIncrement === false && declaredProfile?.developmentClient !== true, 'IOS_SIMULATOR_VERSION_AND_CLIENT_GUARD');
  }
  requireGate(declaredProfile?.env?.EXPO_PUBLIC_API_URL === EXPECTED_API, 'IOS_EAS_API_MISMATCH');
  requireGate(eas?.cli?.version === '25.0.0' && eas?.cli?.appVersionSource === 'remote', 'IOS_EAS_CLI_VERSION_AUTHORITY');
  requireGate(pkg?.version === '1.0.0' && pkg?.dependencies?.['react-native-nitro-google-signin'] === '1.0.2', 'IOS_APP_OR_NATIVE_AUTH_VERSION_DRIFT');
  requireGate((appConfig?.version === '1.0.0' || appConfig?.version === '1.5.0') && appConfig?.runtimeVersion === '1.0.0-build17', 'IOS_APP_RUNTIME_MISMATCH');
  requireGate(appConfig?.extra?.apiUrl === EXPECTED_API && appConfig?.extra?.googleAuthConfigured === true, 'IOS_GOOGLE_API_CONFIG_MISMATCH');
  requireGate(appBundleId === expectedBundleId, 'IOS_APP_BUNDLE_ID_MISMATCH');

  if (effectiveAppEnv === 'preview') {
    requireGate(selectedFile === PREVIEW_FILE, 'IOS_PREVIEW_FILE_SELECTION_DRIFT');
  } else {
    requireGate(Boolean(selectedFile), 'IOS_GOOGLE_FILE_VARIABLE_REQUIRED');
    // EAS File environment variables resolve to absolute paths OUTSIDE the source tree.
    // Reject checked-in preview, relative spoofing, and symlinks back inside the repo.
    requireGate(externalFile(selectedFile, root), 'IOS_EXTERNAL_GOOGLE_PLIST_REQUIRED');
  }

  requireGate(filesPresent && Boolean(selectedFile), 'IOS_GOOGLE_PLIST_MISSING');
  requireGate(plistBundleId === expectedBundleId, 'IOS_GOOGLE_BUNDLE_ID_MISMATCH');
  requireGate(Boolean(clientId?.endsWith(GOOGLE_SUFFIX)) && reversed === expectedReversed, 'IOS_GOOGLE_REVERSED_CLIENT_ID_MISMATCH');
  requireGate(Boolean(webId?.endsWith(GOOGLE_SUFFIX)) && webId === webSourceId, 'IOS_GOOGLE_WEB_CLIENT_ID_MISMATCH');
  requireGate(Boolean(projectId && appId), 'IOS_GOOGLE_FIREBASE_IDENTITY_INCOMPLETE');
  requireGate(plugin?.[1]?.iosGoogleServicesFile === selectedFile && Boolean(selectedFile), 'IOS_NATIVE_GOOGLE_PLUGIN_MISSING');

  if (effectiveAppEnv !== 'preview') {
    // A renamed preview plist is NOT production credentials.
    requireGate(Boolean(clientId && clientId !== plistString(previewPlist, 'CLIENT_ID')), 'IOS_GOOGLE_CLIENT_REUSED_FROM_PREVIEW');
    requireGate(Boolean(appId && appId !== plistString(previewPlist, 'GOOGLE_APP_ID')), 'IOS_GOOGLE_APP_REUSED_FROM_PREVIEW');
    requireGate(Boolean(projectId && projectId === plistString(previewPlist, 'PROJECT_ID')), 'IOS_GOOGLE_FIREBASE_PROJECT_MISMATCH');
  }

  return Object.freeze({
    status: blockers.length === 0 ? 'PASS_CONFIG_ONLY' : 'HOLD',
    profile,
    effectiveAppEnv,
    buildTarget: profile === 'ios-simulator' ? 'IOS_SIMULATOR_ONLY' : 'IOS_DEVICE',
    expectedBundleId,
    appBundleId,
    googlePlistBundleId: plistBundleId,
    appVersion: appConfig?.version ?? null,
    runtimeVersion: appConfig?.runtimeVersion ?? null,
    easCli: eas?.cli?.version ?? null,
    signing: 'NOT_ATTESTED',
    nativeBuild: 'NOT_STARTED',
    simulatorBuild: 'NOT_STARTED',
    physicalIosUat: 'NOT_PERFORMED',
    firebaseRegistration: 'NOT_INDEPENDENTLY_VERIFIED',
    easFileEnvironment: 'NOT_INDEPENDENTLY_VERIFIED',
    productionMutation: 'NONE',
    blockers: [...new Set(blockers)],
    ok: blockers.length === 0,
  });
}

export function inspectIosReadiness(profile, root = DEFAULT_ROOT) {
  if (!['preview', 'production', 'development', 'ios-simulator'].includes(profile)) {
    return { ok: false, status: 'HOLD', profile, blockers: ['IOS_UNKNOWN_BUILD_PROFILE'] };
  }
  const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
  const eas = JSON.parse(read('eas.json'));
  const pkg = JSON.parse(read('package.json'));
  const previewPlist = read('GoogleService-Info.plist');
  const googleAuthSource = read('src/features/auth/google-auth.ts');
  const rootAndroidServicesPresent = fs.existsSync(path.join(root, 'google-services.json'));
  const profileEnv = eas?.build?.[profile]?.env?.EXPO_PUBLIC_APP_ENV ?? profile;
  const originalEnv = process.env.EXPO_PUBLIC_APP_ENV;
  const configPath = require.resolve(path.join(root, 'app.config.js'));
  try {
    process.env.EXPO_PUBLIC_APP_ENV = profileEnv;
    delete require.cache[configPath];
    const makeAppConfig = require(configPath);
    const appConfig = makeAppConfig({ config: {} });
    const selectedFile = appConfig?.ios?.googleServicesFile;
    const resolved = selectedFile && (
      path.isAbsolute(selectedFile) ? selectedFile : path.resolve(root, selectedFile)
    );
    const filesPresent = Boolean(resolved && fs.existsSync(resolved)
      && fs.statSync(resolved).isFile() && rootAndroidServicesPresent);
    const plist = filesPresent ? fs.readFileSync(resolved, 'utf8') : '';
    return assessIosReadiness({
      profile, appConfig, eas, pkg, plist, previewPlist,
      googleAuthSource, filesPresent, root,
    });
  } finally {
    delete require.cache[configPath];
    if (originalEnv === undefined) delete process.env.EXPO_PUBLIC_APP_ENV;
    else process.env.EXPO_PUBLIC_APP_ENV = originalEnv;
  }
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const args = process.argv.slice(2);
  const profile = args[0] === '--profile' && args.length === 2 ? args[1] : '';
  try {
    const result = inspectIosReadiness(profile);
    // Never log OAuth/Firebase client IDs, secret file path, contents, tokens or signing data.
    process.stdout.write(JSON.stringify(result, null, 2) + '\n');
    process.exitCode = result.ok ? 0 : 2;
  } catch (error) {
    process.stderr.write('IOS_PREFLIGHT_HOLD: cannot inspect trusted config (' + (error?.code || 'CONFIG_UNREADABLE') + ')\n');
    process.exitCode = 2;
  }
}
