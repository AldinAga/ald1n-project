import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
const dir=path.dirname(fileURLToPath(import.meta.url));
const mobile=path.resolve(dir,'../..');
const repo=path.resolve(mobile,'../../..');
const workflow=fs.readFileSync(path.join(repo,'.github/workflows/ald1n-native-528.yml'),'utf8');
const deepTest=fs.readFileSync(path.join(dir,'deep-offline-contract.test.mjs'),'utf8');
const source=fs.readFileSync(path.join(mobile,'scripts/batch528-ci.sh'),'utf8');
const target='audit/build25-native-ci-explicit-trigger-20261009';
const current='audit/build25-ci-cost-gates-20261009';
const previous='audit/build25-combined-native-ci-20261009';
test('CI trigger restricted to future explicit branch only, no accidental execution',()=>{
 assert.ok(workflow.includes('      - '+target),'future explicit trigger missing');
 assert.ok(!workflow.includes('      - '+previous));
 assert.ok(!workflow.includes('      - '+current));
 assert.ok(!/workflow_dispatch:|pull_request:|schedule:/.test(workflow));
 const s=workflow.slice(0,workflow.indexOf('permissions:'));
 assert.equal((s.match(/^\s+- audit\/build25-/gm)||[]).length,1);
});
test('large artifact uploader is hard disabled, not controlled by implicit variable',()=>{
 const marker='      - name: Preserve audit evidence - never Play upload';
 const index=workflow.indexOf(marker);
 assert.ok(index>0,'upload action missing');
 const block=workflow.slice(index,workflow.length);
 assert.ok(block.includes('        if: ${{ false }}'),'upload is not hard disabled');
 assert.ok(block.includes('actions/upload-artifact@ea165f8d65b6e75b540449e92b4886f43607fa02'));
 assert.ok(!block.includes('if: always()'));
});
test('source SHA and release evidence digests survive in job logs without artifact storage',()=>{
 assert.ok(workflow.includes('      - name: Record native evidence digests in Actions summary'));
 assert.ok(workflow.includes('        if: always()'));
 assert.ok(workflow.includes('"$GITHUB_SHA"'));
 assert.ok(workflow.includes('"$GITHUB_STEP_SUMMARY"'));
 assert.ok(workflow.includes('sha256sum "$file"'));
 assert.ok(workflow.includes('artifacts are NOT uploaded'));
});
test('cheap offline regressions run before downloading Android SDK components',()=>{
 const early=workflow.indexOf('      - name: Cheap source and cost gates before Android SDK setup');
 const sdk=workflow.indexOf('      - name: Record Android SDK and install observed Build24 components');
 const native=workflow.indexOf('run: bash apps/mobile/current/scripts/batch528-ci.sh');
 assert.ok(early>0&&sdk>early&&native>sdk,'fail-fast test preflight must precede expensive SDK and compiler');
 assert.ok(workflow.includes('node --test scripts/release25/ci-cost-gate.test.mjs scripts/release25/deep-offline-contract.test.mjs scripts/release25/validator-pin-recovery.test.mjs'));
});
test('existing native real compiler R8 and lint run untouched',()=>{
 assert.ok(workflow.includes('run: bash apps/mobile/current/scripts/batch528-ci.sh'));
 assert.ok(workflow.includes('node-version: \'22.16.0\''));
 assert.ok(workflow.includes("'ndk;27.1.12297006'"));
 assert.ok(source.includes('run lint-release ./gradlew :app:lintRelease'));
 assert.ok(source.includes('run release ./gradlew :ald1n-restore-credentials:compileReleaseKotlin :app:assembleRelease :app:bundleRelease'));
});
test('previous deep validator regression follows new CI branch and keeps direct release blocked',()=>{
 assert.ok(deepTest.includes("marker(wf,'      - "+target+"');"));
 assert.ok(!deepTest.includes("marker(wf,'      - "+previous+"');"));
 const controller=fs.readFileSync(path.join(mobile,'scripts/build25-release-controller.mjs'),'utf8');
 assert.ok(controller.includes('BUILD25_BLOCKED: Direct production CLI not enabled'));
 assert.ok(!/eas build|eas submit|google-play-upload/i.test(workflow));
});
