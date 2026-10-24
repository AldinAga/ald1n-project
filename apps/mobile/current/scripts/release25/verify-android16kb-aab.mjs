#!/usr/bin/env node
/** Synthetic/disposable App Bundle 64-bit ELF audit, NOT a signed Play artifact. */
import { spawnSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { checkElf64 } from './verify-android16kb-apk.mjs';

const RE_NATIVE64 = /^([^/]+)\/lib\/(arm64-v8a|x86_64)\/([^/]+\.so)$/;
const MACHINES = Object.freeze({ 'arm64-v8a': 183, x86_64: 62 });

export function auditBundleEntries(names, readEntry) {
  if (!Array.isArray(names) || typeof readEntry !== 'function') throw new TypeError('INVALID_AAB_ENTRIES');
  const candidates = names.filter(x => typeof x === 'string' && RE_NATIVE64.test(x)).sort();
  if (!candidates.length) throw new Error('HOLD_AAB_NO_64BIT_NATIVE_LIBRARIES');
  if (candidates.length !== new Set(candidates).size) throw new Error('HOLD_AAB_DUPLICATE_NATIVE_ZIP_ENTRIES');
  const failures = [];
  const checked = [];
  for (const name of candidates) {
    const abi = RE_NATIVE64.exec(name)[2];
    try {
      const result = checkElf64(readEntry(name), MACHINES[abi]);
      checked.push({ path: name, ...result });
    } catch (e) {
      failures.push({ path: name, code: e instanceof Error ? e.message : 'UNKNOWN_AAB_ELF_ERROR' });
    }
  }
  return Object.freeze({
    status: failures.length ? 'HOLD' : 'PASS_CI_DISPOSABLE_AAB_ELF_ONLY',
    total64BitLibraries: candidates.length,
    checkedCount: checked.length,
    checked,
    failures,
    bundleConfig: 'NOT_CHECKED_BY_ELF_AUDIT',
    signedProductionAab: 'NOT_CHECKED',
    playAcceptance: 'NOT_CHECKED',
    physical16kbUat: 'NOT_CHECKED',
  });
}

function unzip(args, maxBuffer) {
  const r = spawnSync('unzip', args, { encoding: null, timeout: 120000, maxBuffer });
  if (r.error || r.status !== 0) throw new Error('INVALID_OR_UNREADABLE_AAB_ZIP');
  return r.stdout;
}

export function auditAab(file) {
  if (typeof file !== 'string' || !file.endsWith('.aab') || !existsSync(file)) {
    throw new Error('AAB_MISSING_OR_INVALID_PATH');
  }
  const names = unzip(['-Z1', file], 8 * 1024 * 1024).toString('utf8').split(/\r?\n/).filter(Boolean);
  if (!names.includes('BundleConfig.pb')) throw new Error('HOLD_AAB_BUNDLE_CONFIG_MISSING');
  return auditBundleEntries(names, name => unzip(['-p', file, name], 256 * 1024 * 1024));
}

if (process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1]) {
  let result;
  try { result = auditAab(process.argv[2]); }
  catch (e) { result = { status: 'HOLD', code: e instanceof Error ? e.message : 'UNKNOWN_AAB_ERROR' }; }
  console.log(JSON.stringify(result, null, 2));
  process.exitCode = result.status === 'PASS_CI_DISPOSABLE_AAB_ELF_ONLY' ? 0 : 2;
}
