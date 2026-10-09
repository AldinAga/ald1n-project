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

test('P2: current pointer records exact audit source without pretending remote versionCode is known', () => {
  const pre = pointer.split('## Archived Build23/Build24 historical snapshot')[0];
  assert.ok(pre.startsWith('# Ald1n Operations - CURRENT Build25 Authority (2026-10-09)'));
  assert.ok(pre.includes('7c4e4ef435b46a65bc0e0db57dd74509a62a5e89'));
  assert.ok(pre.includes('b5b942e645d28d3ed5f248eaae4180ebe9a54ee6'));
  assert.ok(pre.includes('audit/build25-report575-targeted-fixes-20261009'));
  assert.ok(pre.includes('REMOTE_ANDROID_VERSIONCODE=UNATTESTED'));
  assert.ok(pre.includes('EAS_CLI=eas-cli@24.7.0'));
  assert.ok(pre.includes('BUILD25_AUTHORIZED=NO'));
  assert.ok(pre.includes('PROGRESS=75_PERCENT_ESTIMATE'));
  assert.ok(!pre.includes('eas-cli@24.8.0'));
  assert.ok(!pre.includes('next possible versionCode is 24'));
});

test('P2: old Build23 instructions are visibly archived, not live build authority', () => {
  assert.ok(pointer.includes('## Archived Build23/Build24 historical snapshot - NOT CURRENT'));
  assert.ok(pointer.includes('## Historical planned Build23 command - OBSOLETE, DO NOT EXECUTE'));
  assert.ok(pointer.includes('Obsolete historical entries below are not operational instructions.'));
});

test('P1: CI remains manual-branch-only, artifact upload off and no direct production writes', () => {
  assert.ok(wf.includes('      - '+trigger));
  assert.ok(!/workflow_dispatch:|pull_request:|schedule:/.test(wf));
  const upload=wf.slice(wf.indexOf('      - name: Preserve audit evidence - never Play upload'));
  assert.ok(upload.includes('        if: ${{ false }}'));
  assert.ok(!/eas build|eas submit|google-play-upload/i.test(wf));
});
