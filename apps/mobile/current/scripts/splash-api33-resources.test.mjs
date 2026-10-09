import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { splitSplashStyleByApi } = require('../plugins/with-splash-api33-resources.js');

function fixture() {
  return { resources: { style: [
    { $: { name: 'AppTheme', parent: 'Theme.AppCompat.Light.NoActionBar' }, item: [] },
    { $: { name: 'Theme.App.SplashScreen', parent: 'Theme.SplashScreen' }, item: [
      { $: { name: 'windowSplashScreenBackground' }, _: '@color/splashscreen_background' },
      { $: { name: 'windowSplashScreenAnimatedIcon' }, _: '@drawable/splashscreen_logo' },
      { $: { name: 'postSplashScreenTheme' }, _: '@style/AppTheme' },
      { $: { name: 'android:windowSplashScreenBehavior' }, _: 'icon_preferred' },
    ] },
  ] } };
}
function tmp() {
  const root = fs.mkdtempSync(path.join(os.tmpdir(), 'ald1n-splash-'));
  fs.mkdirSync(path.join(root, 'app/src/main/res/values'), { recursive: true });
  return root;
}
function cleanup(root) { fs.rmSync(root, { recursive: true, force: true }); }

test('keeps API33 splash behavior and moves the attribute out of unqualified values', () => {
  const root=tmp();
  try {
    const styles=fixture();
    const result=splitSplashStyleByApi(styles, root);
    assert.equal(result, styles);
    const base=styles.resources.style[1];
    assert.deepEqual(base.item.map(x=>x.$.name), [
      'windowSplashScreenBackground', 'windowSplashScreenAnimatedIcon', 'postSplashScreenTheme']);
    assert.equal(styles.resources.style[0].$.name, 'AppTheme');
    const v33=fs.readFileSync(path.join(root,'app/src/main/res/values-v33/ald1n_splash_api33.xml'),'utf8');
    assert.match(v33,/style name="Theme.App.SplashScreen" parent="Theme.SplashScreen"/);
    assert.match(v33, /<item name="android:windowSplashScreenBehavior">icon_preferred<\/item>/);
    for (const key of ['windowSplashScreenBackground','windowSplashScreenAnimatedIcon','postSplashScreenTheme'])
      assert.ok(v33.includes(`name="${key}"`), key);
    assert.equal((v33.match(/<style /g)||[]).length,1);
  } finally { cleanup(root); }
});

test('fails closed when Expo no longer generates the expected attribute', () => {
  const root=tmp();
  try {
    const styles=fixture();
    styles.resources.style[1].item.pop();
    assert.throws(()=>splitSplashStyleByApi(styles,root),/Expected exactly one API33 behavior item/);
    assert.equal(fs.existsSync(path.join(root,'app/src/main/res/values-v33/ald1n_splash_api33.xml')),false);
  } finally { cleanup(root); }
});

test('fails closed on unexpected splash behavior value', () => {
  const root=tmp();
  try {
    const styles=fixture();
    styles.resources.style[1].item.at(-1)._='unexpected';
    assert.throws(()=>splitSplashStyleByApi(styles,root),/Expected icon_preferred/);
  } finally { cleanup(root); }
});

test('fails closed if destination is not pristine', () => {
  const root=tmp();
  try {
    const d=path.join(root,'app/src/main/res/values-v33');fs.mkdirSync(d,{recursive:true});
    fs.writeFileSync(path.join(d,'ald1n_splash_api33.xml'),'<resources/>');
    const styles=fixture();
    assert.throws(()=>splitSplashStyleByApi(styles,root),/already exists/);
    assert.equal(styles.resources.style[1].item.length,4);
  } finally { cleanup(root); }
});

test('preserves other style properties and escapes XML safely', () => {
  const root=tmp();
  try {
    const styles=fixture();
    styles.resources.style[1].item[0]._='@color/splashscreen_background';
    styles.resources.style[1].item.push({$: {name:'customString'}, _:'A & B'});
    splitSplashStyleByApi(styles,root);
    const v33=fs.readFileSync(path.join(root,'app/src/main/res/values-v33/ald1n_splash_api33.xml'),'utf8');
    assert.match(v33,/<item name="customString">A &amp; B<\/item>/);
    assert.ok(styles.resources.style[1].item.some(x=>x.$.name==='customString'));
  } finally {cleanup(root);}
});

test('generated-resource verification rejects prebuild with unqualified API33 item', () => {
  const root=tmp();
  try {
    splitSplashStyleByApi(fixture(),root);
    const base=path.join(root,'app/src/main/res/values/styles.xml');
    fs.writeFileSync(base,'<resources><style name="Theme.App.SplashScreen"><item name="android:windowSplashScreenBehavior">icon_preferred</item></style></resources>');
    const { verifyGeneratedSplashResources }=require('../plugins/with-splash-api33-resources.js');
    assert.throws(()=>verifyGeneratedSplashResources(root),/unqualified values/);
  } finally {cleanup(root);}
});

test('generated-resource verification accepts expected qualified override', () => {
  const root=tmp();
  try {
    const styles=fixture();splitSplashStyleByApi(styles,root);
    const base=path.join(root,'app/src/main/res/values/styles.xml');
    fs.writeFileSync(base,'<resources><style name="Theme.App.SplashScreen"><item name="windowSplashScreenBackground">@color/splashscreen_background</item><item name="windowSplashScreenAnimatedIcon">@drawable/splashscreen_logo</item><item name="postSplashScreenTheme">@style/AppTheme</item></style></resources>');
    const { verifyGeneratedSplashResources }=require('../plugins/with-splash-api33-resources.js');
    assert.equal(verifyGeneratedSplashResources(root),true);
  } finally {cleanup(root);}
});

test('repeated prebuild with an identical qualified style remains idempotent', () => {
  const root=tmp();
  try {
    splitSplashStyleByApi(fixture(),root);
    const v33=path.join(root,'app/src/main/res/values-v33/ald1n_splash_api33.xml');
    const bytes=fs.readFileSync(v33,'utf8');
    splitSplashStyleByApi(fixture(),root);
    assert.equal(fs.readFileSync(v33,'utf8'),bytes);
  } finally {cleanup(root);}
});

test('introspect does not write generated files', () => {
  const styles=fixture();
  splitSplashStyleByApi(styles,'',{introspect:true});
  assert.equal(styles.resources.style[1].item.some(x=>x.$.name==='android:windowSplashScreenBehavior'),false);
});
