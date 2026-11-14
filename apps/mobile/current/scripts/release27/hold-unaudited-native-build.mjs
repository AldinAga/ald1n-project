#!/usr/bin/env node
/**
 * B1032 fail-closed legacy npm build entrypoint.
 * No native build is permitted from generic npm scripts on the host. The
 * independently approved per-profile GitHub/EAS workflow must validate the
 * actual 16KiB APK/AAB before promoting any candidate.
 */
import { fileURLToPath } from 'node:url';

export function assessNativeEntrypoint(argv) {
  const pairs = Object.freeze({
    'android:preview': true,
    'android:development': true,
    'ios:preview': true,
    'ios:development': true,
    'ios:production': true,
    'ios:ios-simulator': true,
  });
  if (!Array.isArray(argv) || argv.length !== 4 || argv[0] !== '--platform' || argv[2] !== '--profile') {
    return Object.freeze({status: 'HOLD', reason: 'INVALID_NATIVE_BUILD_ARGUMENTS'});
  }
  const key = `${argv[1]}:${argv[3]}`;
  if (!pairs[key]) return Object.freeze({status: 'HOLD', reason: 'UNKNOWN_NATIVE_BUILD_PROFILE'});
  return Object.freeze({status: 'HOLD', reason: 'NATIVE_RELEASE_GATE_REQUIRED', platform: argv[1], profile: argv[3]});
}

if (process.argv[1] && fileURLToPath(import.meta.url) === process.argv[1]) {
  console.error(JSON.stringify({ ...assessNativeEntrypoint(process.argv.slice(2)),
    policy: 'Use separately approved exact-source GitHub/EAS workflow; every Android APK/AAB must pass signed 16KiB ELF/ZIP and Play bundletool artifact checks. No direct host build, OTA or Play submit.'
  }));
  process.exitCode = 2;
}
