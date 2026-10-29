import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const dir = path.dirname(fileURLToPath(import.meta.url));
const repo = path.resolve(dir, '../../../../..');
const wf = fs.readFileSync(path.join(repo, '.github/workflows/ald1n-native-528.yml'), 'utf8');
const pointer = fs.readFileSync(path.join(repo, 'docs/operations/000-LATEST.md'), 'utf8');
const signatureTest = path.join(dir, 'verify-signature.test.mjs');
const trigger = 'audit/build25-native-ci-explicit-trigger-20261009';

test('P1: real signature crypto regressions run after JDK17 before Android SDK', () => {
  assert.ok(fs.statSync(signatureTest).isFile());
  const jdk = wf.indexOf('      - name: JDK 17');
  const cheap = wf.indexOf('      - name: Cheap source and cost gates before Android SDK setup');
  const sdk = wf.indexOf('      - name: Record Android SDK and install observed Build24 components');
  assert.ok(jdk >= 0 && cheap > jdk && sdk > cheap, 'JDK -> signing test -> SDK order');
  const preflight = wf.slice(cheap, sdk);
  const toolLine = preflight.match(/for tool in ([a-z ]+); do/);
  assert.deepEqual(toolLine?.[1].trim().split(/\s+/), ['java','jar','keytool','jarsigner','zip','unzip']);
  assert.ok(preflight.includes('scripts/release25/verify-signature.test.mjs'), 'real crypto test suite missing');
  assert.ok(preflight.includes('scripts/release25/batch576-report575-remediation.test.mjs'), 'this regression not included in cheap CI');
  assert.equal((preflight.match(/node --test /g) || []).length, 1, 'single fail-fast test invocation');
});

test('P2: current B963/B962 operations index supersedes historical Build25 release authority', () => {
  assert.ok(pointer.startsWith('# CURRENT B963'), 'newest verified status stays first');
  assert.ok(pointer.includes('B962'), 'actual Laravel release evidence remains linked');
  assert.ok(pointer.includes('PR116'), 'reconciled 16KiB branch/CI status remains linked');
  assert.ok(pointer.includes('1.0.0-build17'), 'existing OTA baseline remains explicit');
  assert.ok(pointer.includes('1.5.0-native2'), 'gated native candidate is explicit');
  assert.ok(pointer.includes('SDK58') || pointer.includes('SDK 58'), 'SDK58 HOLD is documented');
  assert.ok(!pointer.startsWith('# Ald1n Operations - CURRENT Build25 Authority (2026-10-09)'));
});

test('P2: historical Build23 instructions must not be presented as current release instructions', () => {
  assert.ok(pointer.startsWith('# CURRENT B963'));
  assert.ok(pointer.includes('B962'));
  assert.ok(!pointer.slice(0, pointer.indexOf('---')).includes('BUILD25_AUTHORIZED=YES'));
});

test('P1: CI remains manual-branch-only, artifact upload off and no direct production writes', () => {
  assert.ok(wf.includes('      - '+trigger));
  assert.ok(!/workflow_dispatch:|pull_request:|schedule:/.test(wf));
  const upload=wf.slice(wf.indexOf('      - name: Preserve audit evidence - never Play upload'));
  assert.ok(upload.includes('        if: ${{ false }}'));
  assert.ok(!/eas build|eas submit|google-play-upload/i.test(wf));
});
