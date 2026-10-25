#!/usr/bin/env node
/**
 * B837: fail-closed, stage-attested offline 16 KiB Android emulator smoke for an existing
 * disposable CI APK (ideally bundled-JS, locally debug-signed). No EAS/Play, SSH or host mutation.
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
  if (Array.isArray(argv) && argv.length === 1 && argv[0] === '--preflight-only')
    return Object.freeze({preflightOnly: true});
  if (!Array.isArray(argv) || argv.length !== 1 || !argv[0]?.startsWith('--apk=')) hold('APK_ARGUMENT_REQUIRED');
  const apk = argv[0].slice('--apk='.length);
  if (!apk.endsWith('.apk') || !existsSync(apk)) hold('APK_NOT_FOUND');
  return { apk };
}

// Never log adb arguments, command output, environment or logcat in CI:
// the launched app can handle bearer URLs and private runtime information.
// Stage IDs are a hard-coded allowlist without package names, paths or tokens.
export function adbStage(args) {
  if (!Array.isArray(args)) return 'UNKNOWN';
  const tokens = args[0] === '-s' ? args.slice(2) : args;
  const command = tokens.join(' ');
  if (command === 'devices -l') return 'DEVICE_DISCOVERY';
  if (command === 'shell getconf PAGE_SIZE') return 'PAGE_SIZE';
  if (command === 'shell getprop ro.build.version.sdk') return 'SDK_VERSION';
  if (command === 'shell getprop ro.product.cpu.abi') return 'CPU_ABI';
  if (command === 'shell cmd connectivity airplane-mode enable') return 'AIRPLANE_ENABLE';
  if (command === 'shell settings get global airplane_mode_on') return 'AIRPLANE_VERIFY';
  if (command === 'shell svc wifi disable') return 'WIFI_DISABLE';
  if (command === 'shell svc data disable') return 'DATA_DISABLE';
  if (tokens[0] === 'install' && tokens[1] === '-r') return 'APK_INSTALL';
  if (command === 'logcat -c') return 'LOGCAT_CLEAR';
  if (tokens.slice(0,4).join(' ') === 'shell am start -W') return 'APP_START';
  if (tokens[0] === 'shell' && tokens[1] === 'pidof') return 'APP_PROCESS';
  if (command === 'logcat -d -s AndroidRuntime:E') return 'RUNTIME_FATAL_SCAN';
  return 'UNKNOWN';
}

export function classifiedAdbFailure(args, result) {
  const stage = adbStage(args);
  if (result?.error?.code === 'ETIMEDOUT') return 'HOLD_ADB_' + stage + '_TIMEOUT';
  if (result?.error?.code === 'ENOBUFS') return 'HOLD_ADB_' + stage + '_OUTPUT_LIMIT';
  const diagnostic = String(result?.stderr || '') + '\n' + String(result?.stdout || '');
  if (stage === 'APK_INSTALL') {
    const known = diagnostic.match(/\b(?:INSTALL_FAILED|INSTALL_PARSE_FAILED)_[A-Z0-9_]+\b/);
    if (known) return 'HOLD_ADB_APK_INSTALL_' + known[0];
  }
  if (/\bdevice offline\b/i.test(diagnostic)) return 'HOLD_ADB_' + stage + '_DEVICE_OFFLINE';
  if (/\bunauthorized\b/i.test(diagnostic)) return 'HOLD_ADB_' + stage + '_UNAUTHORIZED';
  if (Number.isInteger(result?.status) && result.status >= 0 && result.status <= 255)
    return 'HOLD_ADB_' + stage + '_EXIT_' + result.status;
  return 'HOLD_ADB_' + stage + '_SPAWN_FAILED';
}

export function adbCommand(executable, args) {
  const result = spawnSync(executable, args, { encoding: 'utf8', timeout: 45000, maxBuffer: 2 * 1024 * 1024 });
  if (result.error || result.status !== 0) throw new Error(classifiedAdbFailure(args, result));
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

export function verifyOffline16kbEmulator(invoke = adbCommand, report = () => {}) {
  const invokeAdb = args => invoke('adb', args);
  report('DEVICE_DISCOVERY');
  const serial = parseDevices(invokeAdb(['devices', '-l']));
  const call = args => invokeAdb(['-s', serial, ...args]);
  const property = args => call(['shell', ...args]);
  report('CHECK_16KB_KERNEL');
  const pageSize = property(['getconf', 'PAGE_SIZE']);
  if (pageSize !== '16384') hold('DEVICE_NOT_16KB_PAGE_SIZE');
  const sdk = property(['getprop', 'ro.build.version.sdk']);
  if (!/^\d+$/.test(sdk) || Number(sdk) < 35) hold('ANDROID_API_LT_35');
  report('CHECK_SDK_ABI');
  const abi = property(['getprop', 'ro.product.cpu.abi']);
  if (abi !== 'x86_64') hold('UNEXPECTED_EMULATOR_ABI');

  // Before the FIRST app launch, disallow connections to the live CMS API.
  report('ENABLE_OFFLINE_MODE');
  call(['shell', 'cmd', 'connectivity', 'airplane-mode', 'enable']);
  if (property(['settings', 'get', 'global', 'airplane_mode_on']) !== '1') hold('OFFLINE_MODE_NOT_ATTESTED');
  call(['shell', 'svc', 'wifi', 'disable']);
  call(['shell', 'svc', 'data', 'disable']);

  return Object.freeze({serial, call, pageSizeBytes: 16384, androidSdk: Number(sdk), abi});
}

/** Checks the real guest kernel + offline controls, but never installs an APK. */
export function auditEmulatorPreflight(invoke = adbCommand, report = () => {}) {
  const {serial, pageSizeBytes, androidSdk, abi} = verifyOffline16kbEmulator(invoke, report);
  return Object.freeze({
    status: 'PASS_CI_EMULATOR_16KB_OFFLINE_INFRA_ONLY',
    emulator: serial, androidSdk, pageSizeBytes, abi,
    offlineNetwork: 'AIRPLANE_MODE_ATTESTED',
    nativeApkInstalled: 'NOT_CHECKED',
    appLaunch: 'NOT_CHECKED', signedProductionAab: 'NOT_CHECKED',
    physical16kbUat: 'NOT_CHECKED', googlePlayEligibility: 'NOT_CHECKED',
    productionMutation: 'NONE'
  });
}

export async function auditEmulator(apk, invoke = adbCommand, pause = ms => new Promise(resolve => setTimeout(resolve, ms)), report = () => {}) {
  if (!apk || !existsSync(apk)) hold('APK_NOT_FOUND');
  const {serial, call, pageSizeBytes, androidSdk, abi} = verifyOffline16kbEmulator(invoke, report);
  const property = args => call(['shell', ...args]);
  report('INSTALL_DEBUG_APK');
  const installed = call(['install', '-r', apk]);
  if (!installed.includes('Success')) hold('ANDROID_INSTALL_FAILED');
  call(['logcat', '-c']);
  report('LAUNCH_DEBUG_APP');
  const launched = call(['shell', 'am', 'start', '-W', '-n', PACKAGE + '/.MainActivity']);
  if (!/\bStatus:\s*ok\b/i.test(launched)) hold('ANDROID_MAIN_ACTIVITY_NOT_STARTED');
  await pause(7000);
  report('CHECK_PROCESS_AND_RUNTIME');
  let pids='';
  try {
    pids = property(['pidof', PACKAGE]);
  } catch (error) {
    // Android pidof intentionally exits 1 when the process is not alive.
    // This is an app-lifecycle HOLD, not an unknown ADB transport failure.
    // Inspect only the app-scoped fatal signature; NEVER print raw logcat.
    if (!(error instanceof Error) || error.message !== 'HOLD_ADB_APP_PROCESS_EXIT_1') throw error;
    const crash = call(['logcat', '-d', '-s', 'AndroidRuntime:E']);
    if (/FATAL EXCEPTION[\s\S]*?Process:\s*com\.ald1n\.mobile(?:,|\s|$)/i.test(crash))
      hold('APP_ANDROID_RUNTIME_FATAL');
    hold('APP_PROCESS_NOT_ALIVE');
  }
  if (!/^\d+(?:\s+\d+)*$/.test(pids)) hold('APP_PROCESS_NOT_ALIVE');

  // Avoid printing logcat or runtime UI text: it can contain user information.
  const crashes = call(['logcat', '-d', '-s', 'AndroidRuntime:E']);
  if (/FATAL EXCEPTION[\s\S]*?Process:\s*com\.ald1n\.mobile(?:,|\s|$)/i.test(crashes)) hold('APP_ANDROID_RUNTIME_FATAL');
  return Object.freeze({
    status: 'PASS_CI_EMULATOR_16KB_OFFLINE_LAUNCH_ONLY',
    androidSdk, pageSizeBytes, abi,
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
  if (parsed) {
    const stageReport = stage => console.log(JSON.stringify({ status: 'B837_EMULATOR_STAGE', stage }));
    const run = parsed.preflightOnly
      ? Promise.resolve().then(() => auditEmulatorPreflight(adbCommand, stageReport))
      : auditEmulator(parsed.apk, adbCommand, undefined, stageReport);
    run.then(result => console.log(JSON.stringify(result, null, 2))).catch(error => {
    console.log(JSON.stringify({ status: 'HOLD', code: error instanceof Error ? error.message : 'HOLD_UNKNOWN' }));
    process.exitCode = 2;
  });
  }
}
