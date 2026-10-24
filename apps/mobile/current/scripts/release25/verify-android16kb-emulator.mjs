#!/usr/bin/env node
/**
 * B835: fail-closed, offline-only 16 KiB Android emulator smoke for an existing
 * disposable debug APK. Never uses Expo/EAS, Play, signing credentials or host SSH.
 * A PASS demonstrates a single real 16 KiB emulator kernel and an app process
 * surviving an offline launch; it is NOT signed-AAB/physical-device acceptance.
 */
import { spawnSync } from 'node:child_process';
import { existsSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const PACKAGE = 'com.ald1n.mobile';
function hold(code) { throw new Error('HOLD_' + code); }

export function parseArgs(argv) {
  if (!Array.isArray(argv) || argv.length !== 1 || !argv[0]?.startsWith('--apk=')) hold('APK_ARGUMENT_REQUIRED');
  const apk = argv[0].slice('--apk='.length);
  if (!apk.endsWith('.apk') || !existsSync(apk)) hold('APK_NOT_FOUND');
  return { apk };
}

export function adbCommand(executable, args) {
  const result = spawnSync(executable, args, { encoding: 'utf8', timeout: 45000, maxBuffer: 2 * 1024 * 1024 });
  if (result.error || result.status !== 0) hold('ADB_COMMAND_FAILED');
  return String(result.stdout || '').replaceAll('\r', '').trim();
}

export function parseDevices(output) {
  const lines = String(output).replaceAll('\r', '').split('\n').map(x => x.trim()).filter(Boolean);
  const connected = lines.filter(x => !/^List of devices attached/.test(x) && !/^\*/.test(x));
  if (connected.length !== 1) hold('EXACTLY_ONE_EMULATOR_REQUIRED');
  const fields = connected[0].split(/\s+/);
  if (fields.length < 2 || !/^emulator-\d+$/.test(fields[0]) || fields[1] !== 'device') hold('INVALID_EMULATOR_DEVICE');
  return fields[0];
}

export async function auditEmulator(apk, invoke = adbCommand, pause = ms => new Promise(resolve => setTimeout(resolve, ms))) {
  if (!apk || !existsSync(apk)) hold('APK_NOT_FOUND');
  const invokeAdb = args => invoke('adb', args);
  const serial = parseDevices(invokeAdb(['devices', '-l']));
  const call = args => invokeAdb(['-s', serial, ...args]);
  const property = args => call(['shell', ...args]);
  const pageSize = property(['getconf', 'PAGE_SIZE']);
  if (pageSize !== '16384') hold('DEVICE_NOT_16KB_PAGE_SIZE');
  const sdk = property(['getprop', 'ro.build.version.sdk']);
  if (!/^\d+$/.test(sdk) || Number(sdk) < 35) hold('ANDROID_API_LT_35');
  const abi = property(['getprop', 'ro.product.cpu.abi']);
  if (abi !== 'x86_64') hold('UNEXPECTED_EMULATOR_ABI');

  // Before the FIRST app launch, disallow connections to the live CMS API.
  call(['shell', 'cmd', 'connectivity', 'airplane-mode', 'enable']);
  if (property(['settings', 'get', 'global', 'airplane_mode_on']) !== '1') hold('OFFLINE_MODE_NOT_ATTESTED');
  call(['shell', 'svc', 'wifi', 'disable']);
  call(['shell', 'svc', 'data', 'disable']);

  const installed = call(['install', '-r', apk]);
  if (!installed.includes('Success')) hold('ANDROID_INSTALL_FAILED');
  call(['logcat', '-c']);
  const launched = call(['shell', 'am', 'start', '-W', '-n', PACKAGE + '/.MainActivity']);
  if (!/\bStatus:\s*ok\b/i.test(launched)) hold('ANDROID_MAIN_ACTIVITY_NOT_STARTED');
  await pause(7000);
  const pids = property(['pidof', PACKAGE]);
  if (!/^\d+(?:\s+\d+)*$/.test(pids)) hold('APP_PROCESS_NOT_ALIVE');

  // Avoid printing logcat or runtime UI text: it can contain user information.
  const crashes = call(['logcat', '-d', '-s', 'AndroidRuntime:E']);
  if (/FATAL EXCEPTION[\s\S]*?Process:\s*com\.ald1n\.mobile(?:,|\s|$)/i.test(crashes)) hold('APP_ANDROID_RUNTIME_FATAL');
  return Object.freeze({
    status: 'PASS_CI_EMULATOR_16KB_OFFLINE_LAUNCH_ONLY',
    androidSdk: Number(sdk), pageSizeBytes: 16384, abi,
    emulator: serial, appPackage: PACKAGE, applicationProcess: 'ALIVE_AFTER_7_SECONDS',
    offlineNetwork: 'AIRPLANE_MODE_ATTESTED_BEFORE_APP_START',
    liveCustomerCalls: 'NOT_EXECUTED_OFFLINE',
    signedProductionAab: 'NOT_CHECKED', physical16kbUat: 'NOT_CHECKED',
    googlePlayEligibility: 'NOT_CHECKED', productionMutation: 'NONE'
  });
}

if (process.argv[1] && fileURLToPath(import.meta.url) === path.resolve(process.argv[1])) {
  let parsed;
  try { parsed = parseArgs(process.argv.slice(2)); }
  catch (error) {
    console.log(JSON.stringify({ status: 'HOLD', code: error instanceof Error ? error.message : 'HOLD_UNKNOWN' }));
    process.exitCode = 2;
  }
  if (parsed) auditEmulator(parsed.apk).then(result => console.log(JSON.stringify(result, null, 2))).catch(error => {
    console.log(JSON.stringify({ status: 'HOLD', code: error instanceof Error ? error.message : 'HOLD_UNKNOWN' }));
    process.exitCode = 2;
  });
}
