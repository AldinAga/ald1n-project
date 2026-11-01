const fs = require('node:fs');
const path = require('node:path');
// MOBILE_BUILD16_NATIVE_PALETTE_BATCH133
const APP_ENV =
  process.env.EXPO_PUBLIC_APP_ENV || 'development';

const PROJECT_ID =
  'd43b3866-6838-4217-a23e-3dc7f2cc76cc';

const EAS_PROJECT_ID =
  process.env.EAS_PROJECT_ID?.trim() || PROJECT_ID;

const GOOGLE_SERVICES_FILE = './google-services.json';
const hasGoogleServicesFile = fs.existsSync(path.join(__dirname, 'google-services.json'));
const IOS_GOOGLE_SERVICES_FILE = './GoogleService-Info.plist';
// MOBILE_BATCH685_IOS_FIREBASE_PROFILE_SPLIT:
// Preview uses the tracked Firebase preview app. Production/development may only
// use a separately supplied EAS file variable; never silently reuse preview OAuth.
const IOS_GOOGLE_SERVICES_PATH = APP_ENV === 'preview'
  ? IOS_GOOGLE_SERVICES_FILE
  : (process.env.ALD1N_IOS_GOOGLE_SERVICES_FILE?.trim() || null);
const hasIosGoogleServicesFile = Boolean(
  IOS_GOOGLE_SERVICES_PATH
  && fs.existsSync(path.isAbsolute(IOS_GOOGLE_SERVICES_PATH)
    ? IOS_GOOGLE_SERVICES_PATH
    : path.join(__dirname, IOS_GOOGLE_SERVICES_PATH)),
);
// MOBILE_IOS_GOOGLE_NATIVE_CONFIG_V06

const isProduction = APP_ENV === 'production';
// B989: A dedicated v1.5 Android AAB profile carries BOTH flags to the remote
// EAS builder. The profile marker is also available during LOCAL config
// resolution, where EAS_BUILD_PROFILE is not guaranteed to exist.
// The ordinary production profile remains Build25 by default.
const v15ReleaseProfile = 'production-v15-16kb';
// M17: isolate the next SDK58 internal-UAT binary from Build25 and v1.5 OTA.
const M17_UAT_PROFILE = 'm17-sdk58-uat';
const M17_UAT_RUNTIME = '1.0.0-m17-sdk58-uat2';
const M17_PLAY_PROFILE = 'm17-sdk58-play-rc';
const M17_PLAY_RUNTIME = '1.0.0-m17-sdk58-play2';
const m17Deps = require('./package.json').dependencies || {};
const isM17Sdk58Source =
  m17Deps.expo === '~58.0.22'
  && m17Deps['react-native'] === '0.88.1'
  && m17Deps['react-native-nitro-google-signin'] === '2.3.1'
  && m17Deps['react-native-nitro-modules'] === '0.37.1';
const suffix = isProduction ? '' : `.${APP_ENV}`;

module.exports = ({ config }) => {
  // Evaluate per call: release-candidate CI toggles the gate after requiring config.
  const isApprovedV15Build = isProduction
    && process.env.ALD1N_V15_RELEASE_GATE === 'approved'
    && (
      process.env.EAS_BUILD_PROFILE === 'production'
      || process.env.EAS_BUILD_PROFILE === v15ReleaseProfile
      || process.env.ALD1N_V15_RELEASE_PROFILE === v15ReleaseProfile
    );
  // Exact isolated UAT markers are required locally and on EAS builders.
  // EAS_BUILD_PROFILE may be absent during local config resolution.
  const m17MarkerPresent = Boolean(
    process.env.ALD1N_M17_SDK58_UAT || process.env.ALD1N_M17_SDK58_PROFILE
  );
  const isM17Uat = isM17Sdk58Source
    && APP_ENV === 'preview'
    && process.env.ALD1N_M17_SDK58_UAT === 'isolated'
    && process.env.ALD1N_M17_SDK58_PROFILE === M17_UAT_PROFILE
    && (!process.env.EAS_BUILD_PROFILE || process.env.EAS_BUILD_PROFILE === M17_UAT_PROFILE);
  if (m17MarkerPresent && !isM17Uat) {
    throw new Error('HOLD_M17_SDK58_UAT_PROFILE_OR_SOURCE');
  }
  // B1029: source-only SDK58 Play identity; NEVER a build/OTA/submit approval.
  const playMarkerPresent = Boolean(
    process.env.ALD1N_M17_SDK58_PLAY_RC || process.env.ALD1N_M17_SDK58_PLAY_PROFILE
  );
  const isM17PlayRc = isM17Sdk58Source
    && isProduction
    && process.env.ALD1N_M17_SDK58_PLAY_RC === 'source-only'
    && process.env.ALD1N_M17_SDK58_PLAY_PROFILE === M17_PLAY_PROFILE
    && (!process.env.EAS_BUILD_PROFILE || process.env.EAS_BUILD_PROFILE === M17_PLAY_PROFILE)
    && process.env.ALD1N_V15_RELEASE_GATE !== 'approved'
    && !m17MarkerPresent;
  if (playMarkerPresent && !isM17PlayRc) {
    throw new Error('HOLD_M17_SDK58_PLAY_PROFILE_OR_SOURCE');
  }
  const existingExtra = config.extra || {};
  const existingEas =
    existingExtra.eas &&
    typeof existingExtra.eas === 'object'
      ? existingExtra.eas
      : {};

  return {
    ...config,

    name: 'Ald1n CMS',

    slug: 'ald1n-mobile',
    owner: 'ald1n',

    version: isApprovedV15Build ? '1.5.0' : '1.0.0',
    icon: './assets/icon.png',
    scheme: 'ald1n',
    userInterfaceStyle: 'automatic',
    backgroundColor: '#F6F7F8',

    updates: {
      url: `https://u.expo.dev/${EAS_PROJECT_ID}`,
    },

    // MOBILE_BUILD17_RUNTIME_ISOLATION_BATCH137
    runtimeVersion: isApprovedV15Build ? '1.5.0-native2' : (isM17Uat ? M17_UAT_RUNTIME : (isM17PlayRc ? M17_PLAY_RUNTIME : '1.0.0-build17')),

    ios: {
      ...(config.ios || {}),
      supportsTablet: true,
      bundleIdentifier: `com.ald1n.mobile${suffix}`,
      icon: './assets/icon-ios.png',
      // MOBILE_V0_6_IOS_OPAQUE_ICON_V1
      ...(hasIosGoogleServicesFile ? { googleServicesFile: IOS_GOOGLE_SERVICES_PATH } : {}),
      buildNumber: '1',
      infoPlist: {
        ...((config.ios && config.ios.infoPlist) || {}),
        NSFaceIDUsageDescription:
          'Ald1n CMS koristi Face ID samo za zaštitu lokalne prijave.',
      },
    },

    android: {
      ...(config.android || {}),
      package: `com.ald1n.mobile${suffix}`,
      softwareKeyboardLayoutMode: 'resize',
      ...(hasGoogleServicesFile ? { googleServicesFile: GOOGLE_SERVICES_FILE } : {}),

      adaptiveIcon: {
        foregroundImage: './assets/adaptive-icon.png',
        backgroundColor: '#101214',
      },

      predictiveBackGestureEnabled: true,
    },

    web: {
      ...(config.web || {}),
      bundler: 'metro',
      output: 'static',
      favicon: './assets/favicon.png',
    },

    plugins: [
      'expo-router',

      // MOBILE_BUILD21_ANDROID_R8_DEX_OPTIMIZATION_BATCH494
      [
        'expo-build-properties',
        {
          android: {
            enableMinifyInReleaseBuilds: true,
            enableShrinkResourcesInReleaseBuilds: true,
          },
        },
      ],

      // MOBILE_BUILD23_ZERO_TAP_RESTORE_CREDENTIALS_BATCH521
      [
        './plugins/with-restore-credential-association',
        { assetLinksUrl: 'https://cms.ald1n.com/.well-known/assetlinks.json' },
      ],

      // MOBILE_BUILD22_ANDROID_NATIVE_MODERNIZATION_BATCH497
      [
        './plugins/with-android-native-modernization',
        {
          optimizedResourceShrinking: true,
          liquidGlassVersion: 'v2.0.11',
        },
      ],

      // Register BEFORE expo-splash-screen: Expo withMod runs hooks in reverse registration order.
      './plugins/with-splash-api33-resources',
      [
        'expo-splash-screen',
        {
          backgroundColor: '#101214',
          image: './assets/splash-icon.png',
          imageWidth: 200,
          resizeMode: 'contain',
        },
      ],

      [
        'expo-secure-store',
        {
          configureAndroidBackup: true,
          faceIDPermission:
            'Dozvoli aplikaciji Ald1n CMS korišćenje Face ID zaštite.',
        },
      ],

      'expo-localization',

      [
        'expo-notifications',
        {
          color: '#C45116',
          defaultChannel: 'business-updates',
        },
      ],

      ...(hasGoogleServicesFile
        ? [[
            'react-native-nitro-google-signin',
            {
              androidGoogleServicesFile: GOOGLE_SERVICES_FILE,
              ...(hasIosGoogleServicesFile ? { iosGoogleServicesFile: IOS_GOOGLE_SERVICES_PATH } : {}),
            },
          ]]
        : []),
    ],

    experiments: {
      ...(config.experiments || {}),
      typedRoutes: true,
    },

    extra: {
      ...existingExtra,

      eas: {
        ...existingEas,
        projectId: EAS_PROJECT_ID,
      },

      appEnv: APP_ENV,

      apiUrl:
        process.env.EXPO_PUBLIC_API_URL ||
        'https://cms.ald1n.com/api/v1',

      googleAuthConfigured: hasGoogleServicesFile,
    },
  };
};
