import { test } from 'node:test';
import assert from 'node:assert/strict';
import { auditNativeLibraries, checkElf64, native64Paths } from './verify-android16kb-apk.mjs';

function sampleElf(align = 16384n, machine = 183, opts = {}) {
  const b = Buffer.alloc(512);
  b.set([0x7f, 0x45, 0x4c, 0x46, 2, 1, 1], 0);
  b.writeUInt16LE(3, 16);
  b.writeUInt16LE(machine, 18);
  b.writeBigUInt64LE(64n, 32);
  b.writeUInt16LE(56, 54);
  b.writeUInt16LE(opts.zeroLoads ? 0 : 1, 56);
  b.writeUInt32LE(1, 64);
  b.writeBigUInt64LE(256n, 72);
  b.writeBigUInt64LE(opts.badVaddr ? 257n : 256n, 80);
  b.writeBigUInt64LE(opts.oversized ? 999n : 8n, 96);
  b.writeBigUInt64LE(8n, 104);
  b.writeBigUInt64LE(align, 112);
  return b;
}
const arm = 'lib/arm64-v8a/libnativegauss.so';
const x64 = 'lib/x86_64/libreactnative.so';

test('valid arm64 and x86_64 ELF binaries pass with all PT_LOAD aligned', () => {
  assert.equal(checkElf64(sampleElf(), 183).ptLoadSegments, 1);
  assert.equal(checkElf64(sampleElf(16384n, 62), 62).minPageBytes, 16384);
});
test('legacy nativegauss 4KB ELF is rejected', () => {
  assert.throws(() => checkElf64(sampleElf(4096n), 183), /P_ALIGN_LT_16K/);
});
test('bad ABI machine cannot be labeled arm64', () => {
  assert.throws(() => checkElf64(sampleElf(16384n, 62), 183), /MACHINE_ABI_MISMATCH/);
});
test('16KB offset/vaddr incongruence is rejected', () => {
  assert.throws(() => checkElf64(sampleElf(16384n, 183, { badVaddr: true }), 183), /NOT_CONGRUENT_16K/);
});
test('invalid segment file range is rejected', () => {
  assert.throws(() => checkElf64(sampleElf(16384n, 183, { oversized: true }), 183), /SEGMENT_EXTENT/);
});
test('missing or truncated ELF headers are rejected', () => {
  assert.throws(() => checkElf64(Buffer.alloc(48), 183), /INVALID_ELF64/);
  assert.throws(() => checkElf64(sampleElf(16384n, 183, { zeroLoads: true }), 183), /PROGRAM_HEADER_TABLE/);
});
test('unsupported machine/class and ET_EXEC fail closed', () => {
  let v = sampleElf(); v[4] = 1;
  assert.throws(() => checkElf64(v, 183), /UNSUPPORTED_ELF64/);
  v = sampleElf(); v.writeUInt16LE(2, 16);
  assert.throws(() => checkElf64(v, 183), /NOT_ET_DYN/);
});
test('missing 64-bit libs and duplicate ZIP names fail closed', () => {
  assert.throws(() => native64Paths(['assets/icon.png']), /NO_64BIT_NATIVE/);
  assert.throws(() => native64Paths([arm, arm]), /DUPLICATE_NATIVE/);
});
test('ABI filenames are restricted to recognized packaged 64-bit paths', () => {
  assert.deepEqual(native64Paths(['assets/a.so', arm, x64, 'lib/armeabi-v7a/a.so']), [arm, x64]);
});
test('all installed 64-bit libraries are examined; any failure holds', () => {
  const result = auditNativeLibraries([arm, x64], (name) => name === arm ? sampleElf() : sampleElf(4096n, 62));
  assert.equal(result.status, 'HOLD');
  assert.equal(result.total64BitLibraries, 2);
  assert.equal(result.checkedCount, 1);
  assert.equal(result.failures.length, 1);
});
test('all-green compiled debug libraries show only CI-limited PASS', () => {
  const result = auditNativeLibraries([arm, x64], (name) => name === arm ? sampleElf() : sampleElf(16384n, 62));
  assert.equal(result.status, 'PASS_CI_DEBUG_APK_ELF_ONLY');
  assert.equal(result.signedAab, 'NOT_CHECKED');
  assert.equal(result.physical16kbUat, 'NOT_CHECKED');
});
test('malformed individual ELF does not suppress other library checks', () => {
  const result = auditNativeLibraries([arm, x64], (name) => name === arm ? Buffer.alloc(20) : sampleElf(16384n, 62));
  assert.equal(result.checkedCount, 1);
  assert.equal(result.failures.length, 1);
  assert.equal(result.status, 'HOLD');
});

// Exercise the actual ZIP extraction and CLI exit-status boundary, not only
// the pure ELF parser. All fixtures live outside the repository and contain
// synthetic bytes; no EAS, device, Play or production state is touched.
import { mkdtempSync, mkdirSync, writeFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const executable = fileURLToPath(new URL('./verify-android16kb-apk.mjs', import.meta.url));

function runCliFixture(files, corrupt = false) {
  const root = mkdtempSync(path.join(tmpdir(), 'ald1n-b831-16kb-'));
  try {
    const archive = path.join(root, 'synthetic.apk');
    if (corrupt) writeFileSync(archive, Buffer.from('NOT_A_ZIP_ARCHIVE'));
    else {
      for (const [name, bytes] of Object.entries(files)) {
        const target = path.join(root, name);
        mkdirSync(path.dirname(target), { recursive: true });
        writeFileSync(target, bytes);
      }
      const zipped = spawnSync('zip', ['-q', '-X', '-r', archive, ...new Set(Object.keys(files).map((f) => f.split('/')[0]))], { cwd: root, encoding: 'utf8' });
      assert.equal(zipped.status, 0, zipped.stderr);
    }
    const result = spawnSync(process.execPath, [executable, archive], { encoding: 'utf8', timeout: 20000 });
    assert.equal(result.error, undefined);
    return { code: result.status, report: JSON.parse(result.stdout) };
  } finally {
    rmSync(root, { recursive: true, force: true });
  }
}

test('CLI reads a genuine ZIP with two aligned 64-bit ELFs and returns scoped CI PASS', () => {
  const { code, report } = runCliFixture({ [arm]: sampleElf(), [x64]: sampleElf(16384n, 62) });
  assert.equal(code, 0);
  assert.equal(report.status, 'PASS_CI_DEBUG_APK_ELF_ONLY');
  assert.equal(report.total64BitLibraries, 2);
  assert.equal(report.checkedCount, 2);
  assert.equal(report.signedAab, 'NOT_CHECKED');
  assert.equal(report.playApproval, 'NOT_CHECKED');
});

test('CLI rejects ZIP containing legacy 4KB nativegauss even with a valid x86_64 ELF', () => {
  const { code, report } = runCliFixture({ [arm]: sampleElf(4096n), [x64]: sampleElf(16384n, 62) });
  assert.equal(code, 2);
  assert.equal(report.status, 'HOLD');
  assert.equal(report.checkedCount, 1);
  assert.match(report.failures[0].code, /P_ALIGN_LT_16K/);
});

test('CLI fails closed on valid ZIP with no 64-bit native libraries', () => {
  const { code, report } = runCliFixture({ 'assets/icon.png': Buffer.from('fixture') });
  assert.equal(code, 2);
  assert.equal(report.status, 'HOLD');
  assert.equal(report.code, 'HOLD_NO_64BIT_NATIVE_LIBRARIES');
});

test('CLI fails closed for damaged or truncated APK ZIP bytes', () => {
  const { code, report } = runCliFixture({}, true);
  assert.equal(code, 2);
  assert.equal(report.status, 'HOLD');
  assert.equal(report.code, 'INVALID_OR_UNREADABLE_APK_ZIP');
});
