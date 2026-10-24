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
