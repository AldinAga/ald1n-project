import { test } from 'node:test';
import assert from 'node:assert/strict';
import { validateSource, validateSnapshot, validateReleaseLog, validatePrebuild } from './batch528-native-contract.mjs';
const source = `CreateRestoreCredentialRequest E2eeUnavailableException isCloudBackupEnabled = false GetRestoreCredentialOption
AsyncFunction("clearRestoreCredential") Coroutine { ->
manager.clearCredentialState(ClearCredentialStateRequest(requestType = TYPE_CLEAR_RESTORE_CREDENTIAL))
}`;
const gradle = 'defaultConfig { versionCode 1\n versionName "1.0.0" }';
const snapshot = { agp:'8.12.0', gradle:'9.3.1', kotlin:'2.1.20', ndk:'27.1.12297006', java:'17.0.20', compileSdk:36, targetSdk:36, minSdk:24, applicationId:'com.ald1n.mobile', versionName:'1.0.0', minify:true, shrinkResources:true, optimizedShrinking:'true', restoreProject:true, fullMode:null, proguard:['proguard-android-optimize.txt-8.12.0'], dependencies:['androidx.credentials:credentials:1.6.0','androidx.credentials:credentials-play-services-auth:1.6.0'] };
test('source guard accepts explicit zero-argument Unit body', () => assert.deepEqual(validateSource(source,gradle),[]));
test('source guard rejects original ambiguous lambda', () => assert.ok(validateSource(source.replace('{ ->','{'),gradle).includes('CLEAR_ZERO_ARITY_REQUIRED')));
test('source guard rejects null even after arrow fix', () => assert.ok(validateSource(source+'\nreturn@Coroutine null',gradle).includes('CLEAR_NULL_RETURN_FORBIDDEN')));
test('library metadata regression is rejected', () => assert.ok(validateSource(source,'android {}').includes('LIBRARY_VERSION_METADATA_REQUIRED')));
test('expected effective configuration accepted', () => assert.deepEqual(validateSnapshot(snapshot),[]));
for (const [key,value] of Object.entries({ minify:false,shrinkResources:false,optimizedShrinking:'false',fullMode:'false',restoreProject:false,agp:'9.0.0',gradle:'10.0',kotlin:'unknown',ndk:'unknown',java:'21.0',targetSdk:35,compileSdk:35,minSdk:23,applicationId:'other',versionName:'2.0.0',proguard:['proguard-android.txt'],dependencies:[] })) {
 test('snapshot rejects '+key+' drift', () => assert.ok(validateSnapshot({...snapshot,[key]:value}).length > 0));
}
const goodLog = '> Task :ald1n-restore-credentials:compileReleaseKotlin\n> Task :app:minifyReleaseWithR8\nBUILD SUCCESSFUL in 1m';
test('real execution markers accepted',()=>assert.deepEqual(validateReleaseLog(goodLog),[]));
for (const status of ['UP-TO-DATE','SKIPPED','FROM-CACHE']) {
 test('cached/skipped R8 not accepted: '+status,()=>assert.ok(validateReleaseLog(goodLog.replace('minifyReleaseWithR8','minifyReleaseWithR8 '+status)).includes('R8_NOT_EXECUTED')));
}
test('success of unrelated task not sufficient',()=>assert.ok(validateReleaseLog('BUILD SUCCESSFUL').length>0));
test('absent success marker cannot pass',()=>assert.ok(validateReleaseLog(goodLog.replace('BUILD SUCCESSFUL','BUILD FAILED')).length>0));

test('known generated prebuild script updates are allowed',()=>assert.deepEqual(validatePrebuild({scripts:{android:'expo start --android'},dependencies:{expo:'~57.0.27'}},{scripts:{android:'expo run:android'},dependencies:{expo:'~57.0.27'}}),[]));
test('prebuild cannot update dependency versions silently',()=>assert.ok(validatePrebuild({dependencies:{expo:'~57.0.27'}},{dependencies:{expo:'~57.0.28'}}).length>0));
test('unknown generated script change is rejected',()=>assert.ok(validatePrebuild({scripts:{android:'expo start --android'}},{scripts:{android:'something else'}}).length>0));
