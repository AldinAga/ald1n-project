import assert from 'node:assert/strict';
import { test } from 'node:test';
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { tmpdir } from 'node:os';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { auditEmulator, parseArgs, parseDevices } from './verify-android16kb-emulator.mjs';

const script = fileURLToPath(new URL('./verify-android16kb-emulator.mjs', import.meta.url));
function fixture(task) {
  const dir = mkdtempSync(path.join(tmpdir(), 'ald1n-b835-'));
  const apk = path.join(dir, 'disposable-debug.apk');
  writeFileSync(apk, Buffer.from('fixture-only, never used in emulator production'));
  try { return task(apk); } finally { rmSync(dir, { recursive: true, force: true }); }
}

function mock(changes = {}) {
  const all = [];
  const table = {
    'devices -l': 'List of devices attached\nemulator-5554 device product:sdk_gphone64_x86_64\n',
    'shell getconf PAGE_SIZE': '16384',
    'shell getprop ro.build.version.sdk': '35',
    'shell getprop ro.product.cpu.abi': 'x86_64',
    'shell settings get global airplane_mode_on': '1',
    'install': 'Success',
    'shell am start -W -n com.ald1n.mobile/.MainActivity': 'Status: ok\nActivity: com.ald1n.mobile/.MainActivity',
    'shell pidof com.ald1n.mobile': '3456',
    'logcat -d -s AndroidRuntime:E': '',
    ...changes
  };
  const invoke = (exe, args) => {
    assert.equal(exe, 'adb');
    all.push(args);
    const key = args[0] === 'devices' ? 'devices -l' : args.slice(2).join(' ');
    if (key === 'install -r ' + args[args.length - 1]) return table.install;
    if (!(key in table)) {
      if (['shell cmd connectivity airplane-mode enable', 'shell svc wifi disable', 'shell svc data disable', 'logcat -c'].includes(key)) return '';
      throw Error('Unexpected adb mock command: ' + key);
    }
    return table[key];
  };
  return { invoke, all };
}

test('valid disposable Android35 x86_64 emulator 16KB offline app launch', async () => {
  await fixture(async apk => {
    const { invoke, all } = mock();
    const result = await auditEmulator(apk, invoke, async () => {});
    assert.equal(result.status, 'PASS_CI_EMULATOR_16KB_OFFLINE_LAUNCH_ONLY');
    assert.equal(result.pageSizeBytes, 16384);
    assert.equal(result.offlineNetwork, 'AIRPLANE_MODE_ATTESTED_BEFORE_APP_START');
    assert.equal(result.physical16kbUat, 'NOT_CHECKED');
    assert.equal(result.googlePlayEligibility, 'NOT_CHECKED');
    const ix = s => all.findIndex(x => x.join(' ').includes(s));
    assert.ok(ix('airplane-mode enable') >= 0);
    assert.ok(ix('airplane-mode enable') < ix('install -r'));
    assert.ok(ix('install -r') < ix('am start -W'));
  });
});

test('4KB kernel is a strict HOLD, no installation', async () => fixture(async apk => {
  const { invoke, all } = mock({ 'shell getconf PAGE_SIZE': '4096' });
  await assert.rejects(auditEmulator(apk, invoke, async()=>{}), /HOLD_DEVICE_NOT_16KB_PAGE_SIZE/);
  assert.equal(all.some(x => x.includes('install')), false);
}));

test('offline mode could not be proved: block launch and install', async () => fixture(async apk => {
  const { invoke, all } = mock({ 'shell settings get global airplane_mode_on': '0' });
  await assert.rejects(auditEmulator(apk, invoke, async()=>{}), /HOLD_OFFLINE_MODE_NOT_ATTESTED/);
  assert.equal(all.some(x => x.includes('install')), false);
}));

test('emulator with wrong ABI fails before install', async () => fixture(async apk => {
  const { invoke } = mock({ 'shell getprop ro.product.cpu.abi': 'arm64-v8a' });
  await assert.rejects(auditEmulator(apk, invoke, async()=>{}), /HOLD_UNEXPECTED_EMULATOR_ABI/);
}));

test('Android API must be 35 or newer', async () => fixture(async apk => {
  const { invoke } = mock({ 'shell getprop ro.build.version.sdk': '34' });
  await assert.rejects(auditEmulator(apk, invoke, async()=>{}), /HOLD_ANDROID_API_LT_35/);
}));

test('successful install but dead process is HOLD', async () => fixture(async apk => {
  const { invoke } = mock({ 'shell pidof com.ald1n.mobile': '' });
  await assert.rejects(auditEmulator(apk, invoke, async()=>{}), /HOLD_APP_PROCESS_NOT_ALIVE/);
}));

test('launcher failure is HOLD', async () => fixture(async apk => {
  const { invoke } = mock({ 'shell am start -W -n com.ald1n.mobile/.MainActivity': 'Error type 3' });
  await assert.rejects(auditEmulator(apk, invoke, async()=>{}), /HOLD_ANDROID_MAIN_ACTIVITY_NOT_STARTED/);
}));

test('native AndroidRuntime fatal exception for app is HOLD', async () => fixture(async apk => {
  const { invoke } = mock({ 'logcat -d -s AndroidRuntime:E': 'FATAL EXCEPTION: main\nProcess: com.ald1n.mobile, PID: 123' });
  await assert.rejects(auditEmulator(apk, invoke, async()=>{}), /HOLD_APP_ANDROID_RUNTIME_FATAL/);
}));

test('different app runtime exception does not get falsely attributed', async () => fixture(async apk => {
  const { invoke } = mock({ 'logcat -d -s AndroidRuntime:E': 'FATAL EXCEPTION: main\nProcess: another.package, PID: 123' });
  const r = await auditEmulator(apk, invoke, async()=>{});
  assert.equal(r.status, 'PASS_CI_EMULATOR_16KB_OFFLINE_LAUNCH_ONLY');
}));

test('only one connected real emulator accepted', () => {
  assert.throws(() => parseDevices('List of devices attached\nemulator-5554 device\nemulator-5556 device'),/HOLD_EXACTLY_ONE_EMULATOR_REQUIRED/);
  assert.throws(() => parseDevices('List of devices attached\nserial123 device'),/HOLD_INVALID_EMULATOR_DEVICE/);
  assert.throws(() => parseDevices('List of devices attached\nemulator-5554 offline'),/HOLD_INVALID_EMULATOR_DEVICE/);
  assert.equal(parseDevices('List of devices attached\nemulator-5554 device'),'emulator-5554');
});

test('CLI rejects absent APK without connecting to any device', () => {
  const result = spawnSync(process.execPath, [script], { encoding: 'utf8' });
  assert.equal(result.status, 2);
  assert.equal(JSON.parse(result.stdout).code, 'HOLD_APK_ARGUMENT_REQUIRED');
  assert.throws(()=>parseArgs(['--apk=/tmp/b835-absent.apk']),/HOLD_APK_NOT_FOUND/);
});
