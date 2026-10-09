import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const mobile=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'../..');
const root=path.resolve(mobile,'../../..');
const validator=fs.readFileSync(path.join(mobile,'scripts/validate-project.mjs'),'utf8');
const pkg=JSON.parse(fs.readFileSync(path.join(mobile,'package.json'),'utf8'));
const eas=JSON.parse(fs.readFileSync(path.join(mobile,'eas.json'),'utf8'));
const helper=fs.readFileSync(path.join(mobile,'scripts/submit-android-production.mjs'),'utf8');
const controller=fs.readFileSync(path.join(mobile,'scripts/build25-release-controller.mjs'),'utf8');
const wf=fs.readFileSync(path.join(root,'.github/workflows/ald1n-native-528.yml'),'utf8');
const app=fs.readFileSync(path.join(mobile,'app.config.js'),'utf8');
const manager=fs.readFileSync(path.join(mobile,'src/features/catalog/product-image-manager.tsx'),'utf8');
const creator=fs.readFileSync(path.join(mobile,'src/app/(app)/admin/catalog/create.tsx'),'utf8');
const marker=(s,x)=>assert.ok(s.includes(x),`missing ${x}`);
const node='/home/icaffeco/nodevenv/mobile-build.ald1n.com/22/bin/node';
const npm='/opt/alt/alt-nodejs22/root/usr/lib/node_modules/npm/bin/npm-cli.js';
test('validator is cwd independent, no inherited TypeScript error',()=>{
 assert.ok(!validator.includes('process.cwd()'));
 marker(validator,'const root = path.resolve(import.meta.dirname,');
 marker(validator,'const syntaxFailuresBefore = failures;');
 marker(validator,'assert(failures === syntaxFailuresBefore,');
});
test('all late validators execute before exactly one terminal summary',()=>{
 assert.equal((validator.match(/process\.exit\(/g)||[]).length,0);
 assert.equal((validator.match(/process\.exitCode\s*=/g)||[]).length,1);
 const i=validator.indexOf('MOBILE_GLOBAL_REPEATABLE_ACTIONS_PRODUCT_CREATE_UX_V07');
 const j=validator.indexOf('MOBILE_BATCH520_SUBAGENT_SHIPMENT_TRACKING_NOTIFICATIONS');
 const end=validator.indexOf('process.exitCode = failures === 0 ? 0 : 1;');
 assert.ok(i>0&&j>i&&end>j);
 marker(validator,"const source = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');");
 marker(validator,'const check = (message, result) => assert(result, message);');
});
test('late product image checks match current shared manager, not old local image state',()=>{
 marker(creator,'<DraftProductImageManager');marker(creator,'onChange={setImages}');
 marker(creator,'limits={options.image_limits}');
 marker(manager,'const existing = new Set(images.map(imageKey));');
 marker(manager,'if (existing.has(imageKey(file))) continue;');
 marker(manager,'onChange([...images, ...appended])');
 marker(validator,'globalRepeatableImageManagerV07');
 assert.ok(!validator.includes("includes('setImages((current) => [...current, ...result.files]);')"));
 assert.ok(!validator.includes("includes('disabled={images.length >= options.image_limits.max_files}')"));
});
test('five optional EAS build scripts are pinned to canonical node/npm, no latest',()=>{
 const targets={
  'build:android:preview':['android','preview'],
  'build:ios:preview':['ios','preview'],
  'build:ios:production':['ios','production'],
  'build:android:development':['android','development'],
  'build:ios:development':['ios','development']
 };
 for(const [key,[platform,profile]] of Object.entries(targets)){
  assert.equal(pkg.scripts[key],`${node} ${npm} exec --yes --package=eas-cli@24.7.0 -- eas build --platform ${platform} --profile ${profile}`,key);
 }
 assert.equal(pkg.scripts['build:android:production'],'node scripts/build25-release-controller.mjs');
 assert.equal(pkg.scripts['submit:android:production'],'node scripts/submit-android-production.mjs');
 assert.ok(!JSON.stringify(pkg.scripts).includes('eas-cli@latest'));
 assert.equal(eas.cli.version,'24.7.0');
 marker(helper,"const EAS_CLI_VERSION = '24.7.0'");
 marker(validator,'productionSubmitHelper505R.includes("const EAS_CLI_VERSION = \'24.7.0\'")');
});
test('production build and legacy submit remain explicitly fail closed',()=>{
 marker(controller,'BUILD25_BLOCKED: Direct production CLI not enabled');
 marker(helper,'DIRECT_SUBMIT_DISABLED: use verified Build25 controller');
 assert.ok(!wf.includes('workflow_dispatch:'));
 assert.ok(!wf.includes('audit/build25-deep-offline-convergence-20261009'));
 marker(wf,'      - audit/build25-native-ci-explicit-trigger-20261009');
});
test('production Android identity and API remain unchanged',()=>{
 marker(app,"runtimeVersion: '1.0.0-build17'");
 marker(app,"version: '1.0.0'");
 marker(app,'com.ald1n.mobile');
 assert.equal(eas.build.production.channel,'production');
 assert.equal(eas.submit.production.android.track,'production');
 assert.equal(eas.cli.appVersionSource,'remote');
});
