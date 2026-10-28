import assert from 'node:assert/strict';
import { test } from 'node:test';
import { mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { auditBundleEntries } from './verify-android16kb-aab.mjs';

const arm = 'base/lib/arm64-v8a/libnativegauss.so';
const x86 = 'base/lib/x86_64/libapp.so';
const dynamic = 'some_feature/lib/arm64-v8a/libfeature.so';
const cli = fileURLToPath(new URL('./verify-android16kb-aab.mjs', import.meta.url));

function elf(align = 16384n, machine = 183) {
  const b = Buffer.alloc(512);
  b.set([0x7f, 0x45, 0x4c, 0x46, 2, 1, 1], 0);
  b.writeUInt16LE(3, 16);
  b.writeUInt16LE(machine, 18);
  b.writeBigUInt64LE(64n, 32);
  b.writeUInt16LE(56, 54);
  b.writeUInt16LE(1, 56);
  b.writeUInt32LE(1, 64);
  b.writeBigUInt64LE(256n, 72);
  b.writeBigUInt64LE(256n, 80);
  b.writeBigUInt64LE(8n, 96);
  b.writeBigUInt64LE(8n, 104);
  b.writeBigUInt64LE(align, 112);
  return b;
}

function fixture(files) {
  const root = mkdtempSync(path.join(tmpdir(), 'ald1n-b832-'));
  const aab = path.join(root, 'synthetic.aab');
  for (const [n, bytes] of Object.entries(files)) {
    const target = path.join(root, n);
    mkdirSync(path.dirname(target), { recursive: true });
    writeFileSync(target, bytes);
  }
  const zipped = spawnSync('zip', ['-q', '-X', '-r', aab, ...new Set(Object.keys(files).map(n => n.split('/')[0]))], { cwd: root, encoding: 'utf8' });
  assert.equal(zipped.status, 0, zipped.stderr);
  return { root, aab };
}
function run(files) {
  const { root, aab } = fixture(files);
  try {
    const cmd = spawnSync(process.execPath, [cli, aab], { encoding: 'utf8', timeout: 25000 });
    assert.equal(cmd.error, undefined);
    return { code: cmd.status, data: JSON.parse(cmd.stdout) };
  } finally { rmSync(root, { recursive: true, force: true }); }
}

test('base and dynamic modules: inspect every 64-bit ELF, successful CI-only result', () => {
  const x = auditBundleEntries([arm, x86, dynamic, 'base/lib/armeabi-v7a/ignored.so'], n => n === x86 ? elf(16384n, 62) : elf());
  assert.equal(x.status, 'PASS_CI_DISPOSABLE_AAB_ELF_ONLY');
  assert.equal(x.checkedCount, 3);
  assert.equal(x.signedProductionAab, 'NOT_CHECKED');
});

test('mixed 4KB nativegauss and aligned other libs must HOLD', () => {
  const x = auditBundleEntries([arm, x86], n => n === arm ? elf(4096n) : elf(16384n, 62));
  assert.equal(x.status, 'HOLD');
  assert.equal(x.failures.length, 1);
  assert.match(x.failures[0].code, /P_ALIGN_LT_16K/);
});

test('duplicate ZIP entries and no native entries fail closed', () => {
  assert.throws(() => auditBundleEntries([arm, arm], () => elf()), /DUPLICATE/);
  assert.throws(() => auditBundleEntries(['base/manifest/AndroidManifest.xml'], () => elf()), /NO_64BIT/);
});

test('bad ABI machine in bundle cannot be falsely green', () => {
  const x = auditBundleEntries([arm, x86], () => elf());
  assert.equal(x.status, 'HOLD');
  assert.equal(x.checkedCount, 1);
  assert.match(x.failures[0].code, /MACHINE_ABI_MISMATCH/);
});

test('CLI reads real ZIP synthetic AAB, does not declare signed artifact eligible', () => {
  const x = run({ 'BundleConfig.pb': Buffer.from('synthetic'), [arm]: elf(), [x86]: elf(16384n, 62) });
  assert.equal(x.code, 0);
  assert.equal(x.data.checkedCount, 2);
  assert.equal(x.data.status, 'PASS_CI_DISPOSABLE_AAB_ELF_ONLY');
  assert.equal(x.data.bundleConfig, 'NOT_CHECKED_BY_ELF_AUDIT');
});

test('CLI aborts for 4KB library and missing bundle config', () => {
  const a = run({ 'BundleConfig.pb': Buffer.from('synthetic'), [arm]: elf(4096n) });
  assert.equal(a.code, 2);
  assert.equal(a.data.status, 'HOLD');
  const b = run({ [arm]: elf() });
  assert.equal(b.code, 2);
  assert.equal(b.data.code, 'HOLD_AAB_BUNDLE_CONFIG_MISSING');
});

test('CLI aborts for non-ZIP AAB', () => {
  const root = mkdtempSync(path.join(tmpdir(), 'ald1n-b832-bad-'));
  try {
    const aab = path.join(root, 'damaged.aab');
    writeFileSync(aab, 'not an archive');
    const cmd = spawnSync(process.execPath, [cli, aab], { encoding: 'utf8' });
    assert.equal(cmd.status, 2);
    assert.equal(JSON.parse(cmd.stdout).code, 'INVALID_OR_UNREADABLE_AAB_ZIP');
  } finally { rmSync(root, { recursive: true, force: true }); }
});
