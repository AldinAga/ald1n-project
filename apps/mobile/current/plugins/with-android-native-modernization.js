const { withAppBuildGradle, withProjectBuildGradle, withGradleProperties } = require('@expo/config-plugins');

function upsertGradleProperty(entries, key, value) {
  const next = entries.filter((entry) => !(entry.type === 'property' && entry.key === key));
  next.push({ type: 'property', key, value });
  return next;
}

const NATIVEGAUSS_16KB_VERSION = 'v2.0.11';
const NATIVEGAUSS_16KB_MARKER = '// ALD1N_NATIVEGAUSS_16KB_UPSTREAM_PIN_V1';

// The upstream RN Fabric bridge reads rootProject.ext BEFORE the app Gradle
// module is configured. Pin on root android/build.gradle, not app/build.gradle.
function pinLiquidGlassRootVersion(contents, version) {
  if (version !== NATIVEGAUSS_16KB_VERSION) {
    throw new Error('Unsupported Liquid Glass native version: require vetted 16KB v2.0.11 AAR.');
  }
  if (typeof contents !== 'string') {
    throw new Error('Expected Groovy root build.gradle source.');
  }
  const declaration = "rootProject.ext.liquidGlassVersion = '" + version + "'";
  const prefix = NATIVEGAUSS_16KB_MARKER + '\n' + declaration + '\n';
  if (contents.startsWith(prefix)
    && contents.split(NATIVEGAUSS_16KB_MARKER).length === 2
    && contents.split('liquidGlassVersion').length === 2) {
    return contents;
  }
  if (contents.includes(NATIVEGAUSS_16KB_MARKER) || contents.includes('liquidGlassVersion')) {
    throw new Error('Conflicting or partial root Liquid Glass version declaration.');
  }
  if ((contents.match(/^\s*buildscript\s*\{/gm) || []).length !== 1) {
    throw new Error('Expected exactly one root Gradle buildscript block.');
  }
  return prefix + contents;
}

module.exports = function withAndroidNativeModernization(config, props = {}) {
  const optimizedResourceShrinking = props.optimizedResourceShrinking === true;
  const materialVersion = typeof props.materialVersion === 'string' ? props.materialVersion.trim() : '';
  const liquidGlassVersion = props.liquidGlassVersion;

  if (optimizedResourceShrinking) {
    config = withGradleProperties(config, (gradleConfig) => {
      gradleConfig.modResults = upsertGradleProperty(
        gradleConfig.modResults,
        'android.r8.optimizedResourceShrinking',
        'true',
      );
      return gradleConfig;
    });
  }

  if (liquidGlassVersion !== undefined) {
    config = withProjectBuildGradle(config, (gradleConfig) => {
      if (gradleConfig.modResults.language !== 'groovy') {
        throw new Error('16KB upstream pin requires Groovy root android/build.gradle.');
      }
      gradleConfig.modResults.contents = pinLiquidGlassRootVersion(
        gradleConfig.modResults.contents,
        liquidGlassVersion,
      );
      return gradleConfig;
    });
  }

  config = withAppBuildGradle(config, (gradleConfig) => {
    if (gradleConfig.modResults.language !== 'groovy') {
      throw new Error('Ald1n Android modernization expects Groovy app/build.gradle.');
    }

    let source = gradleConfig.modResults.contents;
    const legacy = /getDefaultProguardFile\(\s*(['"])proguard-android\.txt\1\s*\)/g;
    const legacyMatches = source.match(legacy) ?? [];
    if (legacyMatches.length > 0) {
      source = source.replace(legacy, 'getDefaultProguardFile("proguard-android-optimize.txt")');
    } else if (!source.includes('proguard-android-optimize.txt')) {
      throw new Error('Neither legacy nor optimized default ProGuard file was found.');
    }

    if (materialVersion) {
      const marker = '// ALD1N_MATERIAL_SDK57_COMPAT_PIN_BATCH497';
      const dependency = 'implementation("com.google.android.material:material:' + materialVersion + '")';
      if (!source.includes(marker)) {
        const anchor = 'dependencies {';
        const first = source.indexOf(anchor);
        if (first < 0 || source.indexOf(anchor, first + anchor.length) >= 0) {
          throw new Error('Expected one app dependencies block for Material pin.');
        }
        source = source.replace(anchor, anchor + '\n    ' + marker + '\n    ' + dependency);
      }
    }

    gradleConfig.modResults.contents = source;
    return gradleConfig;
  });

  return config;
};

module.exports.pinLiquidGlassRootVersion = pinLiquidGlassRootVersion;
