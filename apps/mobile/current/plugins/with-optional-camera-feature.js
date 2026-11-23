const { withAndroidManifest } = require('expo/config-plugins');

// Author: Ald1n <https://ald1n.com>
// Camera is a convenience (EAN scan, product photos), so Play must not hide the app from
// devices without a camera. Libraries (expo-camera, ML Kit) may declare the feature as
// required; tools:replace forces our required=false in the merged manifest.
const FEATURES = ['android.hardware.camera', 'android.hardware.camera.autofocus'];

module.exports = function withOptionalCameraFeature(config) {
  return withAndroidManifest(config, (mod) => {
    const manifest = mod.modResults.manifest;
    manifest.$ = { ...manifest.$, 'xmlns:tools': 'http://schemas.android.com/tools' };
    const features = (manifest['uses-feature'] = manifest['uses-feature'] || []);
    for (const name of FEATURES) {
      const attrs = {
        'android:name': name,
        'android:required': 'false',
        'tools:replace': 'android:required',
      };
      const existing = features.find((item) => item.$?.['android:name'] === name);
      if (existing) existing.$ = attrs;
      else features.push({ $: attrs });
    }
    return mod;
  });
};
