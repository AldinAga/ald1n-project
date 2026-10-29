import test from 'node:test';
import assert from 'node:assert/strict';
import {classifyProcessLogs} from './diagnose-android16kb-process.mjs';

test('native signal classification is app-scoped',()=>{
  assert.equal(classifyProcessLogs({crash:'Fatal signal 11 (SIGSEGV), cmdline: com.ald1n.mobile'}),'NATIVE_SIGNAL_TERMINATION');
  assert.equal(classifyProcessLogs({crash:'Fatal signal 11 (SIGSEGV), cmdline: other.app'}),'NO_CLASSIFIABLE_APP_CRASH_SIGNATURE');
});
test('Java fatal and React Native JS classifier',()=>{
  assert.equal(classifyProcessLogs({runtime:'FATAL EXCEPTION: main\nProcess: com.ald1n.mobile, PID: 10'}),'JAVA_FATAL_EXCEPTION');
  assert.equal(classifyProcessLogs({runtime:'ReactNativeJS: TypeError: example; com.ald1n.mobile'}),'REACT_NATIVE_JS_EXCEPTION');
});
test('system kill, ANR and process death are separate from diagnosed crash',()=>{
  assert.equal(classifyProcessLogs({events:'am_kill: [com.ald1n.mobile]'}),'ANDROID_SYSTEM_KILL_EVENT');
  assert.equal(classifyProcessLogs({events:'am_anr: [com.ald1n.mobile]'}),'ANDROID_ANR');
  assert.equal(classifyProcessLogs({events:'am_proc_died: [com.ald1n.mobile]'}),'PROCESS_DIED_EVENT_NO_CAUSE');
  assert.equal(classifyProcessLogs({events:'am_proc_died: [other.app]'}),'NO_CLASSIFIABLE_APP_CRASH_SIGNATURE');
});
