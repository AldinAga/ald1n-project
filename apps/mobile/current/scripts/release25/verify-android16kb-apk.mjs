#!/usr/bin/env node
/** Fail-closed 16 KiB PT_LOAD audit of EVERY packaged 64-bit Android library.
 * Structural CI evidence only: it does not attest a signed AAB, APK provenance,
 * Play acceptance or physical-device behavior.
 */
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { existsSync } from 'node:fs';

export const PAGE = 16384n;
const ELF_MAGIC = Buffer.from([0x7f, 0x45, 0x4c, 0x46]);
const ABI_MACHINES = Object.freeze({ 'arm64-v8a': 183, 'x86_64': 62 });
const LIB_PATH = /^lib\/(arm64-v8a|x86_64)\/[^/]+\.so$/;

export function native64Paths(rawNames) {
  if (!Array.isArray(rawNames)) throw new TypeError('ZIP entry list required');
  const names = rawNames.filter((n) => typeof n === 'string' && LIB_PATH.test(n));
  if (!names.length) throw new Error('HOLD_NO_64BIT_NATIVE_LIBRARIES');
  if (new Set(names).size !== names.length) throw new Error('HOLD_DUPLICATE_NATIVE_ZIP_ENTRIES');
  return names.sort();
}

export function checkElf64(bytes, expectedMachine) {
  if (!Buffer.isBuffer(bytes) || bytes.length < 64 || !bytes.subarray(0, 4).equals(ELF_MAGIC)) {
    throw new Error('INVALID_ELF64');
  }
  if (bytes[4] !== 2 || bytes[5] !== 1 || bytes[6] !== 1) throw new Error('UNSUPPORTED_ELF64_CLASS_ENDIAN_VERSION');
  if (bytes.readUInt16LE(16) !== 3) throw new Error('NOT_ET_DYN');
  if (bytes.readUInt16LE(18) !== expectedMachine) throw new Error('ELF_MACHINE_ABI_MISMATCH');
  const phoff = bytes.readBigUInt64LE(32);
  const entsize = bytes.readUInt16LE(54);
  const count = bytes.readUInt16LE(56);
  if (!count || entsize < 56 || phoff + BigInt(entsize) * BigInt(count) > BigInt(bytes.length)) {
    throw new Error('INVALID_ELF_PROGRAM_HEADER_TABLE');
  }
  let loads = 0;
  let fileBacked = 0;
  for (let i = 0; i < count; i += 1) {
    const off = Number(phoff + BigInt(entsize) * BigInt(i));
    if (bytes.readUInt32LE(off) !== 1) continue;
    loads += 1;
    const pOffset = bytes.readBigUInt64LE(off + 8);
    const pVaddr = bytes.readBigUInt64LE(off + 16);
    const pFilesz = bytes.readBigUInt64LE(off + 32);
    const pMemsz = bytes.readBigUInt64LE(off + 40);
    const pAlign = bytes.readBigUInt64LE(off + 48);
    if (pFilesz) fileBacked += 1;
    if (pFilesz > pMemsz || pOffset + pFilesz > BigInt(bytes.length)) throw new Error('INVALID_PT_LOAD_SEGMENT_EXTENT');
    if (pAlign < PAGE || (pAlign & (pAlign - 1n)) !== 0n) throw new Error('HOLD_PT_LOAD_P_ALIGN_LT_16K_OR_INVALID');
    if ((pVaddr - pOffset) % PAGE !== 0n) throw new Error('HOLD_PT_LOAD_OFFSET_VADDR_NOT_CONGRUENT_16K');
  }
  if (!loads || !fileBacked) throw new Error('MISSING_FILE_BACKED_PT_LOAD');
  return { ptLoadSegments: loads, minPageBytes: Number(PAGE) };
}

export function auditNativeLibraries(names, readEntry) {
  const paths = native64Paths(names);
  const checked = [];
  const failures = [];
  for (const path of paths) {
    const abi = path.split('/')[1];
    try {
      const data = readEntry(path);
      const elf = checkElf64(data, ABI_MACHINES[abi]);
      checked.push({ path, ...elf });
    } catch (error) {
      failures.push({ path, code: error instanceof Error ? error.message : 'UNKNOWN_NATIVE_AUDIT_ERROR' });
    }
  }
  return { status: failures.length ? 'HOLD' : 'PASS_CI_DEBUG_APK_ELF_ONLY', checkedCount: checked.length, total64BitLibraries: paths.length, checked, failures, signedAab: 'NOT_CHECKED', playApproval: 'NOT_CHECKED', physical16kbUat: 'NOT_CHECKED' };
}

function runUnzip(args, maxBuffer) {
  const result = spawnSync('unzip', args, { encoding: null, maxBuffer, timeout: 120000 });
  if (result.error || result.status !== 0) throw new Error('INVALID_OR_UNREADABLE_APK_ZIP');
  return result.stdout;
}

export function auditApk(apkPath) {
  if (typeof apkPath !== 'string' || !apkPath.endsWith('.apk') || !existsSync(apkPath)) throw new Error('APK_MISSING_OR_INVALID_PATH');
  const names = runUnzip(['-Z1', apkPath], 4 * 1024 * 1024).toString('utf8').split(/\r?\n/).filter(Boolean);
  return auditNativeLibraries(names, (name) => runUnzip(['-p', apkPath, name], 256 * 1024 * 1024));
}

if (process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1]) {
  let report;
  try {
    report = auditApk(process.argv[2]);
  } catch (error) {
    report = { status: 'HOLD', code: error instanceof Error ? error.message : 'UNKNOWN_APK_ERROR' };
  }
  console.log(JSON.stringify(report, null, 2));
  process.exitCode = report.status === 'PASS_CI_DEBUG_APK_ELF_ONLY' ? 0 : 2;
}
