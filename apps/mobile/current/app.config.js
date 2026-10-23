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

// Android LG-03 native integration is still DRAFT. Only the dedicated internal
// UAT profile may opt in to its distinct runtime. Production remains Build25.
const LG03_NATIVE_UAT_REQUESTED = process.env.ALD1N_LG03_NATIVE_UAT === '1';
// The owner requested one Google Play TESTING AAB: the same app package and
// Firebase client as production, but an isolated channel and native runtime.
const LG03_PLAY_TESTING_REQUESTED = process.env.ALD1N_LG03_PLAY_TESTING === '1';
if (LG03_NATIVE_UAT_REQUESTED && LG03_PLAY_TESTING_REQUESTED) {
  throw new Error('LG03_MUTUALLY_EXCLUSIVE_BUILD_TARGETS');
}
if (LG03_PLAY_TESTING_REQUESTED
  && (APP_ENV !== 'production' || process.env.EAS_BUILD_PROFILE !== 'lg03-play-testing')) {
  throw new Error('LG03_PLAY_TESTING_REQUIRES_EXACT_PROFILE_AND_REGISTERED_PACKAGE');
}
if (process.env.EAS_BUILD_PROFILE === 'lg03-play-testing' && !LG03_PLAY_TESTING_REQUESTED) {
  throw new Error('LG03_PLAY_TESTING_OPT_IN_MISSING');
}
if (LG03_NATIVE_UAT_REQUESTED && APP_ENV !== 'preview') {
  throw new Error('LG03_NATIVE_UAT_REQUIRES_REGISTERED_PREVIEW_APP');
}
if (process.env.EAS_BUILD_PROFILE === 'production') {
  throw new Error('LG03_DRAFT_NATIVE_MODULE_FORBIDS_PRODUCTION_EAS_BUILD');
}

const isProduction = APP_ENV === 'production';
const suffix = isProduction ? '' : `.${APP_ENV}`;

module.exports = ({ config }) => {
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

    version: '1.0.0',
    icon: './assets/icon.png',
    scheme: 'ald1n',
    userInterfaceStyle: 'automatic',
    backgroundColor: '#F6F7F8',

    updates: {
      url: `https://u.expo.dev/${EAS_PROJECT_ID}`,
    },

    // MOBILE_BUILD17_RUNTIME_ISOLATION_BATCH137
    runtimeVersion: '1.0.0-build17',
    // Dedicated native preview uses an incompatible-from-Build25 runtime.
    ...(LG03_NATIVE_UAT_REQUESTED
      ? { runtimeVersion: '1.0.0-lg03-native-uat1' }
      : LG03_PLAY_TESTING_REQUESTED
        ? { runtimeVersion: '1.0.0-lg03-play-testing1' }
        : {}),

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
