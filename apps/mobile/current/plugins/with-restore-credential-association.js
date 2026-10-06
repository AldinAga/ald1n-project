const { withAndroidManifest, withStringsXml } = require('expo/config-plugins');

module.exports = function withRestoreCredentialAssociation(config, props = {}) {
  const assetLinksUrl = String(props.assetLinksUrl || '').trim();
  if (!/^https:\/\//.test(assetLinksUrl)) throw new Error('Restore Credentials assetLinksUrl mora biti HTTPS.');

  config = withStringsXml(config, (mod) => {
    const value = JSON.stringify([{ include: assetLinksUrl }]).replace(/"/g, '\\"');
    const strings = mod.modResults.resources.string || [];
    const existing = strings.find((item) => item.$?.name === 'asset_statements');
    if (existing) {
      existing._ = value;
      existing.$.translatable = 'false';
    } else {
      strings.push({ $: { name: 'asset_statements', translatable: 'false' }, _: value });
    }
    mod.modResults.resources.string = strings;
    return mod;
  });

  return withAndroidManifest(config, (mod) => {
    const application = mod.modResults.manifest.application?.[0];
    if (!application) throw new Error('Android application manifest node nije pronađen.');
    application['meta-data'] = application['meta-data'] || [];
    const name = 'asset_statements';
    const existing = application['meta-data'].find((item) => item.$?.['android:name'] === name);
    const payload = { 'android:name': name, 'android:resource': '@string/asset_statements' };
    if (existing) existing.$ = payload;
    else application['meta-data'].push({ $: payload });
    return mod;
  });
};
