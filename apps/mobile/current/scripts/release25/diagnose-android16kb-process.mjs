#!/usr/bin/env node
/**
 * B968: restricted offline emulator process-death forensics.
 * No raw logcat, stack, environment, token, paths, or user content leaves memory.
 * This program never turns a runtime HOLD into a PASS.
 */
import { spawnSync } from 'node:child_process';

const PACKAGE = 'com.ald1n.mobile';
const run = args => {
  const result = spawnSync('adb', args, {encoding:'utf8',timeout:20000,maxBuffer:2*1024*1024});
  return {ok:!result.error && result.status===0, output:String(result.stdout||'')};
};
export function classifyProcessLogs({crash='',runtime='',events=''}={}) {
  const text = String(crash) + '\n' + String(runtime);
  const relevant = text.includes(PACKAGE);
  if (relevant && /FATAL EXCEPTION/i.test(text)) return 'JAVA_FATAL_EXCEPTION';
  if (relevant && /Fatal signal\s+\d+|signal\s+(?:SIGSEGV|SIGABRT|SIGBUS)/i.test(text)) return 'NATIVE_SIGNAL_TERMINATION';
  if (relevant && /ReactNativeJS.*(?:TypeError|ReferenceError|Error:|Invariant Violation)/i.test(text))
    return 'REACT_NATIVE_JS_EXCEPTION';
  if (String(events).includes(PACKAGE) && /am_anr/i.test(events)) return 'ANDROID_ANR';
  if (String(events).includes(PACKAGE) && /am_kill/i.test(events)) return 'ANDROID_SYSTEM_KILL_EVENT';
  if (String(events).includes(PACKAGE) && /am_proc_died/i.test(events)) return 'PROCESS_DIED_EVENT_NO_CAUSE';
  return 'NO_CLASSIFIABLE_APP_CRASH_SIGNATURE';
}
export function diagnose() {
  const d=run(['devices']);
  const matches=d.output.split('\n').map(s=>s.trim()).filter(s=>/^emulator-\d+\s+device$/.test(s));
  if (!d.ok || matches.length!==1) return {status:'HOLD',code:'DIAGNOSTIC_EMULATOR_UNAVAILABLE'};
  const serial=matches[0].split(/\s+/)[0];
  const adb=args=>run(['-s',serial,...args]);
  const alive=adb(['shell','pidof',PACKAGE]);
  const crash=adb(['logcat','-d','-b','crash']);
  const runtime=adb(['logcat','-d','-b','main','-s','AndroidRuntime:E','libc:F','DEBUG:F','ReactNativeJS:E','ReactNative:E']);
  const events=adb(['logcat','-d','-b','events','-s','am_proc_died:I','am_kill:I','am_crash:I','am_anr:I']);
  const kind=classifyProcessLogs({crash:crash.output,runtime:runtime.output,events:events.output});
  return {
    status:'HOLD',
    code: alive.ok && /^\d+(\s+\d+)*$/.test(alive.output.trim()) ? 'PROCESS_ALIVE_AT_DIAGNOSTIC_TIME' : 'APP_PROCESS_NOT_ALIVE',
    classification:kind,
    evidence:{
      crashBufferAvailable:crash.ok,
      runtimeBufferAvailable:runtime.ok,
      eventBufferAvailable:events.ok
    },
    rawDeviceLogs:'NEVER_EMITTED',
    productionMutation:'NONE',
    signedProductionAab:'NOT_CHECKED'
  };
}
if(process.argv[1] && import.meta.url.endsWith('/'+process.argv[1].split('/').at(-1))) {
  console.log(JSON.stringify(diagnose()));
  process.exitCode=2;
}
