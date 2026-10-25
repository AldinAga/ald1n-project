import assert from 'node:assert/strict';
import { test } from 'node:test';
import { mkdtempSync, rmSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { tmpdir } from 'node:os';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { auditEmulator, parseArgs, parseDevices, adbStage, classifiedAdbFailure, adbCommand, auditEmulatorPreflight } from './verify-android16kb-emulator.mjs';

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

test('a missing app PID is an app-lifecycle HOLD, not a generic ADB failure', async () => fixture(async apk => {
  const { invoke } = mock();
  const pidofAbsent=(executable,args) => {
    if (args.includes('pidof')) throw new Error('HOLD_ADB_APP_PROCESS_EXIT_1');
    return invoke(executable,args);
  };
  await assert.rejects(auditEmulator(apk,pidofAbsent,async()=>{}), /HOLD_APP_PROCESS_NOT_ALIVE/);
}));

test('a missing app PID with its AndroidRuntime fatal is a precise crash HOLD', async () => fixture(async apk => {
  const { invoke } = mock({
    'logcat -d -s AndroidRuntime:E': 'FATAL EXCEPTION: main\nProcess: com.ald1n.mobile, PID: 33',
  });
  const pidofAbsent=(executable,args) => {
    if (args.includes('pidof')) throw new Error('HOLD_ADB_APP_PROCESS_EXIT_1');
    return invoke(executable,args);
  };
  await assert.rejects(auditEmulator(apk,pidofAbsent,async()=>{}), /HOLD_APP_ANDROID_RUNTIME_FATAL/);
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

// B837: each CI ADB failure must identify the failing operation. Never expose
// command arguments or the stdout/stderr contents in the resulting HOLD code.
test('ADB stages resolve to fixed, privacy-safe identifiers', () => {
  const commands = [
    [['devices','-l'], 'DEVICE_DISCOVERY'],
    [['-s','emulator-5554','shell','getconf','PAGE_SIZE'], 'PAGE_SIZE'],
    [['-s','emulator-5554','shell','getprop','ro.build.version.sdk'], 'SDK_VERSION'],
    [['-s','emulator-5554','shell','getprop','ro.product.cpu.abi'], 'CPU_ABI'],
    [['-s','emulator-5554','shell','cmd','connectivity','airplane-mode','enable'], 'AIRPLANE_ENABLE'],
    [['-s','emulator-5554','shell','settings','get','global','airplane_mode_on'], 'AIRPLANE_VERIFY'],
    [['-s','emulator-5554','shell','svc','wifi','disable'], 'WIFI_DISABLE'],
    [['-s','emulator-5554','shell','svc','data','disable'], 'DATA_DISABLE'],
    [['-s','emulator-5554','install','-r','/tmp/sensitive-debug.apk'], 'APK_INSTALL'],
    [['-s','emulator-5554','logcat','-c'], 'LOGCAT_CLEAR'],
    [['-s','emulator-5554','shell','am','start','-W','-n','com.ald1n.mobile/.MainActivity'], 'APP_START'],
    [['-s','emulator-5554','shell','pidof','com.ald1n.mobile'], 'APP_PROCESS'],
    [['-s','emulator-5554','logcat','-d','-s','AndroidRuntime:E'], 'RUNTIME_FATAL_SCAN']
  ];
  for(const [args, stage] of commands) assert.equal(adbStage(args),stage);
  assert.equal(adbStage(['-s','emulator-5554','shell','some-custom-secret']), 'UNKNOWN');
});

test('install failures retain only allowlisted Android error code', () => {
  const args=['-s','emulator-5554','install','-r','/private/launch-secret.apk'];
  assert.equal(classifiedAdbFailure(args,{status:1,stderr:'Failure [INSTALL_FAILED_NO_MATCHING_ABIS: secret path]'}),
    'HOLD_ADB_APK_INSTALL_INSTALL_FAILED_NO_MATCHING_ABIS');
  assert.equal(classifiedAdbFailure(args,{status:1,stdout:'INSTALL_PARSE_FAILED_NO_CERTIFICATES private token'}),
    'HOLD_ADB_APK_INSTALL_INSTALL_PARSE_FAILED_NO_CERTIFICATES');
  assert.equal(classifiedAdbFailure(args,{status:1,stderr:'unknown secret-bearing failure'}), 'HOLD_ADB_APK_INSTALL_EXIT_1');
});

test('timeout, offline and unauthorized errors identify exact step without leaking diagnostics', () => {
  const args=['-s','emulator-5554','shell','cmd','connectivity','airplane-mode','enable'];
  const secrets='https://private.example/?token=123';
  const timedOut=classifiedAdbFailure(args,{error:{code:'ETIMEDOUT'},status:null,stderr:secrets});
  const offline=classifiedAdbFailure(args,{status:1,stderr:'adb: device offline '+secrets});
  const unauthorized=classifiedAdbFailure(args,{status:1,stderr:'device unauthorized '+secrets});
  assert.equal(timedOut,'HOLD_ADB_AIRPLANE_ENABLE_TIMEOUT');
  assert.equal(offline,'HOLD_ADB_AIRPLANE_ENABLE_DEVICE_OFFLINE');
  assert.equal(unauthorized,'HOLD_ADB_AIRPLANE_ENABLE_UNAUTHORIZED');
  for(const code of [timedOut,offline,unauthorized]) assert.equal(code.includes(secrets),false);
});

test('real spawned command failure maps into safe stage instead of generic ADB failure', () => {
  assert.throws(() => adbCommand('/definitely-not-present-b837', ['devices','-l']),/HOLD_ADB_DEVICE_DISCOVERY_SPAWN_FAILED/);
});

test('stage reporting is ordered, privacy safe and does not claim production release', async () => {
  await fixture(async apk => {
    const { invoke } = mock();
    const stages=[];
    const result=await auditEmulator(apk,invoke,async()=>{}, stage=>stages.push(stage));
    assert.deepEqual(stages,[
      'DEVICE_DISCOVERY','CHECK_16KB_KERNEL','CHECK_SDK_ABI','ENABLE_OFFLINE_MODE',
      'INSTALL_DEBUG_APK','LAUNCH_DEBUG_APP','CHECK_PROCESS_AND_RUNTIME'
    ]);
    assert.equal(result.signedProductionAab,'NOT_CHECKED');
  });
});

test('fail-closed 4KB kernel never emits APK installation or launch stages', async () => {
  await fixture(async apk => {
    const { invoke } = mock({'shell getconf PAGE_SIZE':'4096'});
    const stages=[];
    await assert.rejects(auditEmulator(apk,invoke,async()=>{},stage=>stages.push(stage)),/HOLD_DEVICE_NOT_16KB_PAGE_SIZE/);
    assert.deepEqual(stages,['DEVICE_DISCOVERY','CHECK_16KB_KERNEL']);
  });
});

test('standalone real 16KB emulator preflight checks actual kernel and offline state but never installs', () => {
  const { invoke, all } = mock();
  const stages=[];
  const result=auditEmulatorPreflight(invoke, stage=>stages.push(stage));
  assert.equal(result.status,'PASS_CI_EMULATOR_16KB_OFFLINE_INFRA_ONLY');
  assert.equal(result.pageSizeBytes,16384);
  assert.equal(result.nativeApkInstalled,'NOT_CHECKED');
  assert.equal(result.appLaunch,'NOT_CHECKED');
  assert.deepEqual(stages,['DEVICE_DISCOVERY','CHECK_16KB_KERNEL','CHECK_SDK_ABI','ENABLE_OFFLINE_MODE']);
  assert.equal(all.some(x=>x.includes('install')),false);
  assert.equal(all.some(x=>x.includes('start')),false);
});

test('standalone preflight refuses 4KB and inaccurate airplane status', () => {
  const k=mock({'shell getconf PAGE_SIZE':'4096'});
  assert.throws(()=>auditEmulatorPreflight(k.invoke),/HOLD_DEVICE_NOT_16KB_PAGE_SIZE/);
  const air=mock({'shell settings get global airplane_mode_on':'0'});
  assert.throws(()=>auditEmulatorPreflight(air.invoke),/HOLD_OFFLINE_MODE_NOT_ATTESTED/);
});

test('CLI accepts preflight-only mode without an APK path', () => {
  assert.deepEqual(parseArgs(['--preflight-only']),{preflightOnly:true});
  assert.throws(()=>parseArgs(['--preflight-only','--apk=/tmp/test.apk']),/HOLD_APK_ARGUMENT_REQUIRED/);
});
