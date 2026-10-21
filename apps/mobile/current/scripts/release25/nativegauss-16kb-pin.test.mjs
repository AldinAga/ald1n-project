import assert from 'node:assert/strict';
import fs from 'node:fs';
import { createRequire } from 'node:module';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const require = createRequire(import.meta.url);
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const plugin = require(path.join(root, 'plugins/with-android-native-modernization.js'));
const configText = fs.readFileSync(path.join(root, 'app.config.js'), 'utf8');
const pkg = JSON.parse(fs.readFileSync(path.join(root, 'package.json'), 'utf8'));

test('the production native bridge stays on Expo SDK57/RN0.86 and pins compatible upstream', () => {
  assert.match(configText, /liquidGlassVersion:\s*'v2\.0\.11'/);
  assert.match(configText, /optimizedResourceShrinking:\s*true/);
  assert.equal(pkg.dependencies['react-native-android-liquid-glass'], '0.2.1');
  assert.equal(pkg.dependencies['react-native'], '0.86.3');
  assert.equal(pkg.dependencies.expo, '~57.0.27');
});

test('root Gradle pin occurs before subproject evaluation and is idempotent', () => {
  const src = "buildscript {\n  repositories { google() }\n}\n";
  const out = plugin.pinLiquidGlassRootVersion(src, 'v2.0.11');
  assert.match(out, /^\/\/ ALD1N_NATIVEGAUSS_16KB_UPSTREAM_PIN_V1\nrootProject\.ext\.liquidGlassVersion = 'v2\.0\.11'\nbuildscript \{/);
  assert.equal(plugin.pinLiquidGlassRootVersion(out, 'v2.0.11'), out);
});

test('old 4KB pin, duplicates and unsupported root template fail closed', () => {
  const src = "buildscript { }\n";
  assert.throws(() => plugin.pinLiquidGlassRootVersion(src, 'v2.0.0'), /v2\.0\.11/);
  assert.throws(() => plugin.pinLiquidGlassRootVersion("ext.liquidGlassVersion = 'v2.0.0'\n"+src, 'v2.0.11'), /Conflicting/);
  assert.throws(() => plugin.pinLiquidGlassRootVersion('android { }\n', 'v2.0.11'), /exactly one root/);
});

test('upstream AAR byte provenance and compiled native alignment are CI gates', () => {
  const ci = fs.readFileSync(path.join(root, '../../../.github/workflows/android-v15-nativegauss-16kb-qa.yml'), 'utf8');
  assert.match(ci, /com\/github\/QWEA0\/liquidglass\/v2\.0\.11/);
  assert.match(ci, /8218f8075c3c447241f2a1e96d502fc48718c6d007e9e05347bb9669901f13a9/);
  assert.match(ci, /readelf -lW/);
  assert.match(ci, /compileDebugKotlin/);
  assert.match(ci, /NO_EAS_BUILD=YES/);
});
