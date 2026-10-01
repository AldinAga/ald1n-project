const { withAppBuildGradle, withGradleProperties } = require('@expo/config-plugins');

function upsertGradleProperty(entries, key, value) {
  const next = entries.filter((entry) => !(entry.type === 'property' && entry.key === key));
  next.push({ type: 'property', key, value });
  return next;
}

module.exports = function withAndroidNativeModernization(config, props = {}) {
  const optimizedResourceShrinking = props.optimizedResourceShrinking === true;
  const materialVersion = typeof props.materialVersion === 'string' ? props.materialVersion.trim() : '';

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
