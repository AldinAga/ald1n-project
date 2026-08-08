const fs = require('node:fs');
const path = require('node:path');
const APP_ENV =
  process.env.EXPO_PUBLIC_APP_ENV || 'development';

const PROJECT_ID =
  'd43b3866-6838-4217-a23e-3dc7f2cc76cc';

const EAS_PROJECT_ID =
  process.env.EAS_PROJECT_ID?.trim() || PROJECT_ID;

const GOOGLE_SERVICES_FILE = './google-services.json';
const hasGoogleServicesFile = fs.existsSync(path.join(__dirname, 'google-services.json'));

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

    name: isProduction
      ? 'Ald1n Mobile'
      : `Ald1n Mobile (${APP_ENV})`,

    slug: 'ald1n-mobile',
    owner: 'ald1n',

    version: '0.3.1',
    orientation: 'portrait',
    icon: './assets/icon.png',
    scheme: 'ald1n',
    userInterfaceStyle: 'light',
    backgroundColor: '#F8F6FC',

    updates: {
      url: `https://u.expo.dev/${EAS_PROJECT_ID}`,
    },

    runtimeVersion: {
      policy: 'appVersion',
    },

    ios: {
      ...(config.ios || {}),
      supportsTablet: true,
      bundleIdentifier: `com.ald1n.mobile${suffix}`,
      buildNumber: '1',
      infoPlist: {
        ...((config.ios && config.ios.infoPlist) || {}),
        NSFaceIDUsageDescription:
          'Ald1n Mobile koristi Face ID samo za zaštitu lokalne prijave.',
      },
    },

    android: {
      ...(config.android || {}),
      package: `com.ald1n.mobile${suffix}`,
      ...(hasGoogleServicesFile ? { googleServicesFile: GOOGLE_SERVICES_FILE } : {}),

      adaptiveIcon: {
        foregroundImage: './assets/adaptive-icon.png',
        backgroundColor: '#02141E',
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

      [
        'expo-splash-screen',
        {
          backgroundColor: '#02141E',
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
            'Dozvoli aplikaciji Ald1n Mobile korišćenje Face ID zaštite.',
        },
      ],

      'expo-localization',

      [
        'expo-notifications',
        {
          color: '#6D45E5',
          defaultChannel: 'business-updates',
        },
      ],

      ...(hasGoogleServicesFile
        ? [[
            'react-native-nitro-google-signin',
            { androidGoogleServicesFile: GOOGLE_SERVICES_FILE },
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
