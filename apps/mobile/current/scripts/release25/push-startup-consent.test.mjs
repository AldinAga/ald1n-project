import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

// Exercise the real passive and explicit callers; only native I/O, HTTP and secure storage are simulated.
function fixture(options = {}) {
  const calls = [], saved = [], choices = [];
  const store = new Map(options.choice === undefined || options.choice === null ? [] : [[1, options.choice]]);
  let prompts = 0, channels = 0, tokens = 0;
  class ApiError extends Error { constructor(status) { super('Synthetic HTTP failure'); this.status = status; } }
  const device = { id: 17, notifications_enabled: false, push_registered: false };
  const native = {
    'expo-application': { nativeApplicationVersion: '1.0.0', nativeBuildVersion: '25' },
    'expo-constants': { __esModule: true, default: { expoConfig: { extra: { eas: { projectId: options.missingProject ? null : 'synthetic-project' } } } } },
    'expo-device': { isDevice: options.physical !== false, modelName: 'Synthetic device' },
    'expo-localization': { getCalendars: () => [{ timeZone: 'Europe/Belgrade' }], getLocales: () => [{ languageTag: 'sr-Latn' }] },
    'expo-notifications': {
      AndroidImportance: { HIGH: 4 },
      setNotificationChannelAsync: async () => { channels++; },
      getPermissionsAsync: async () => { if (options.permissionError) throw Error('Synthetic native failure'); return { status: options.permission ?? 'granted', canAskAgain: true }; },
      requestPermissionsAsync: async () => { prompts++; return { status: options.promptResult ?? 'granted', canAskAgain: true }; },
      getExpoPushTokenAsync: async () => { tokens++; if (options.tokenError) throw Error('Synthetic token failure'); return { data: options.token === undefined ? 'ExponentPushToken[synthetic-b1124]' : options.token }; },
    },
    'react-native': { Platform: { OS: options.platform ?? 'android' } },
    '@/design/ald1n-tokens.kobalt.generated': { kobalt: { light: { button: { primary: { bg: '#2d50bf' } } } } },
    '@/lib/api/client': { ApiError },
    '@/lib/api/endpoints': { api: { devices: {
      register: async input => { if (options.postGate) await options.postGate; calls.push({ method: 'POST', input }); if (options.postError) throw options.postError; return device; },
      update: async (id, input) => { calls.push({ method: 'PATCH', id, input }); if (options.status) throw new ApiError(options.status); return device; },
    } } },
    '@/lib/storage': {
      getInstallationId: async () => '5c352b34-7aa4-4cbd-8376-5b36e2be56e1',
      getServerDeviceId: async () => options.serverId ?? null,
      setServerDeviceId: async id => { saved.push(id); },
      getPushConsentChoice: async userId => { if (options.readError) throw Error('Synthetic storage read failure'); return store.get(userId) ?? null; },
      setPushConsentChoice: async (userId, value) => {
        if (options.writeError) throw Error('Synthetic storage write failure');
        if (value === null) store.delete(userId); else store.set(userId, value);
        choices.push([userId, value]);
      },
    },
  };
  const source = readFileSync(new URL('../../src/features/notifications/push-service.ts', import.meta.url), 'utf8');
  assert.equal(ts.createSourceFile('push-service.ts', source, ts.ScriptTarget.Latest, true).parseDiagnostics.length, 0);
  const exports = {};
  runInNewContext(ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 } }).outputText, {
    exports, Error, require: name => { assert.ok(Object.hasOwn(native, name), 'Unexpected import ' + name); return native[name]; },
  }, { filename: 'push-service.ts' });
  return { service: exports, calls, saved, choices, store, choice: (userId = 1) => store.get(userId) ?? null, device, ApiError, counters: () => ({ prompts, channels, tokens }) };
}

for (const platform of ['android', 'ios']) {
  test('passive startup preserves consent on ' + platform, async () => {
    const h = fixture({ platform });
    assert.equal(await h.service.syncCurrentDeviceAtStartup({ userId: 1, includePush: true }), h.device);
    assert.equal(h.calls.length, 1);
    const { method, input } = h.calls[0];
    assert.equal(method, 'POST');
    assert.equal(input.platform, platform);
    assert.equal(input.installation_id, '5c352b34-7aa4-4cbd-8376-5b36e2be56e1');
    assert.equal(input.app_version, '1.0.0');
    assert.equal(input.push_provider, 'expo');
    assert.equal(input.push_token, 'ExponentPushToken[synthetic-b1124]');
    assert.equal(Object.hasOwn(input, 'notifications_enabled'), false, 'Passive OS permission cannot enable business consent');
    assert.deepEqual(h.saved, [17]);
    assert.deepEqual(h.choices, [], 'Startup never writes the user choice');
    assert.equal(h.counters().prompts, 0);
    assert.equal(h.counters().channels, platform === 'android' ? 1 : 0);
  });
}

for (const [name, options, includePush] of [
  ['metadata only', {}, false], ['emulator', { physical: false }, true],
  ['denied', { permission: 'denied' }, true], ['undetermined', { permission: 'undetermined' }, true],
  ['permission failure', { permissionError: true }, true], ['token failure', { tokenError: true }, true],
  ['empty token', { token: '' }, true], ['missing project', { missingProject: true }, true],
]) {
  test('startup heartbeat survives ' + name + ' without prompting or consent write', async () => {
    const h = fixture(options);
    await h.service.syncCurrentDeviceAtStartup({ userId: 1, includePush });
    assert.equal(h.calls.length, 1);
    assert.equal(Object.hasOwn(h.calls[0].input, 'notifications_enabled'), false);
    assert.equal(Object.hasOwn(h.calls[0].input, 'push_token'), false);
    assert.deepEqual(h.saved, [17]);
    assert.equal(h.counters().prompts, 0);
  });
}

// (a) U4: the user switched notifications off, then the app restarts.
test('disabled notifications stay off across app restarts', async () => {
  const h = fixture({ serverId: 7 });
  await h.service.disableCurrentDevicePush({ userId: 1 });
  assert.equal(h.choice(), false);
  h.calls.length = 0;
  for (let restart = 0; restart < 3; restart++) {
    await h.service.syncCurrentDeviceAtStartup({ userId: 1, includePush: true });
  }
  assert.equal(h.calls.length, 3);
  for (const { input } of h.calls) {
    assert.notEqual(input.notifications_enabled, true, 'Startup must not send notifications_enabled: true');
    assert.equal(input.notifications_enabled, false, 'Stored choice is asserted explicitly because the backend defaults a missing value to true on create');
    assert.equal(Object.hasOwn(input, 'push_token'), false, 'No token is re-registered for a user who turned push off');
  }
  assert.equal(h.counters().tokens, 0);
});

test('disabled choice survives even when the saved server id is gone', async () => {
  const h = fixture({ choice: false });
  await h.service.syncCurrentDeviceAtStartup({ userId: 1, includePush: true });
  assert.equal(h.calls[0].input.notifications_enabled, false);
});

// (b) First opt-in: fresh install, OS permission already granted, no stored choice.
test('fresh install with granted OS permission registers the token and leaves the first opt-in to the backend default', async () => {
  const h = fixture();
  await h.service.syncCurrentDeviceAtStartup({ userId: 1, includePush: true });
  assert.equal(h.calls.length, 1);
  const { input } = h.calls[0];
  assert.equal(input.push_provider, 'expo');
  assert.equal(input.push_token, 'ExponentPushToken[synthetic-b1124]');
  assert.notEqual(input.notifications_enabled, false, 'A first opt-in must not be blocked');
  assert.equal(Object.hasOwn(input, 'notifications_enabled'), false, 'Startup does not assert consent; the backend creates the row enabled');
  assert.equal(h.choice(), null);
});

// (c) Token refresh keeps whatever the user chose.
test('token refresh keeps an enabled choice without re-asserting it', async () => {
  const h = fixture({ choice: true, token: 'ExponentPushToken[rotated]' });
  await h.service.syncCurrentDeviceAtStartup({ userId: 1, includePush: true });
  const { input } = h.calls[0];
  assert.equal(input.push_token, 'ExponentPushToken[rotated]');
  assert.equal(Object.hasOwn(input, 'notifications_enabled'), false);
  assert.equal(h.choice(), true);
});

test('token refresh keeps a disabled choice', async () => {
  const h = fixture({ choice: false, token: 'ExponentPushToken[rotated]' });
  await h.service.syncCurrentDeviceAtStartup({ userId: 1, includePush: true });
  const { input } = h.calls[0];
  assert.equal(input.notifications_enabled, false);
  assert.equal(Object.hasOwn(input, 'push_token'), false);
  assert.equal(h.choice(), false);
});

for (const [name, options, methods] of [
  ['saved ID', { serverId: 7 }, ['PATCH']],
  ['missing ID', {}, ['POST']],
  ['404 fallback', { serverId: 7, status: 404 }, ['PATCH', 'POST']],
]) {
  test('intentional enable retains true and token through ' + name, async () => {
    const h = fixture(options);
    await h.service.registerCurrentDeviceForPush({ userId: 1, prompt: false });
    assert.deepEqual(h.calls.map(x => x.method), methods);
    for (const call of h.calls) {
      assert.equal(call.input.notifications_enabled, true);
      assert.equal(call.input.push_provider, 'expo');
      assert.equal(call.input.push_token, 'ExponentPushToken[synthetic-b1124]');
    }
    assert.equal(h.counters().prompts, 0);
    assert.equal(h.choice(), true, 'Explicit opt-in is remembered');
  });
}

test('intentional enable after a disable lifts the stored choice', async () => {
  const h = fixture({ serverId: 7, choice: false });
  await h.service.registerCurrentDeviceForPush({ userId: 1, prompt: false });
  assert.equal(h.choice(), true);
  h.calls.length = 0;
  await h.service.syncCurrentDeviceAtStartup({ userId: 1, includePush: true });
  assert.equal(Object.hasOwn(h.calls[0].input, 'notifications_enabled'), false);
  assert.equal(h.calls[0].input.push_provider, 'expo');
});

test('intentional enable prompts only when requested and permission is not granted', async () => {
  const h = fixture({ permission: 'undetermined' });
  await h.service.registerCurrentDeviceForPush({ userId: 1, prompt: true });
  assert.equal(h.counters().prompts, 1);
  assert.equal(h.calls[0].input.notifications_enabled, true);
});

test('non404 enable failure propagates without POST, saved-ID replacement or choice change', async () => {
  const h = fixture({ serverId: 7, status: 500, choice: false });
  await assert.rejects(h.service.registerCurrentDeviceForPush({ userId: 1, prompt: false }), e => e instanceof h.ApiError && e.status === 500);
  assert.deepEqual(h.calls.map(x => x.method), ['PATCH']);
  assert.deepEqual(h.saved, []);
  assert.equal(h.choice(), false);
});

test('intentional disable retains false and explicit token removal', async () => {
  const h = fixture({ serverId: 7 });
  await h.service.disableCurrentDevicePush({ userId: 1 });
  assert.equal(h.calls[0].id, 7);
  assert.equal(h.calls[0].input.notifications_enabled, false);
  assert.equal(h.calls[0].input.push_provider, null);
  assert.equal(h.calls[0].input.push_token, null);
  assert.equal(h.choice(), false);
});

test('failed disable rolls the local choice back', async () => {
  const h = fixture({ serverId: 7, status: 500 });
  await assert.rejects(h.service.disableCurrentDevicePush({ userId: 1 }));
  assert.equal(h.choice(), null);
});

// Storage failures.
test('startup fails closed when the stored choice cannot be read: no request at all', async () => {
  const h = fixture({ readError: true });
  await assert.rejects(h.service.syncCurrentDeviceAtStartup({ userId: 1, includePush: true }), /storage read/);
  assert.deepEqual(h.calls, [], 'A request could create or re-activate a row as enabled');
  assert.equal(h.counters().tokens, 0);
});

test('explicit disable fails, without a server call, when the local write fails', async () => {
  const h = fixture({ serverId: 7, writeError: true });
  await assert.rejects(h.service.disableCurrentDevicePush({ userId: 1 }), /storage write/);
  assert.deepEqual(h.calls, [], 'Server must not go off while the local choice stays unknown');
});

test('explicit enable fails, without a server call, when the local write fails', async () => {
  const h = fixture({ serverId: 7, writeError: true, choice: false });
  await assert.rejects(h.service.registerCurrentDeviceForPush({ userId: 1, prompt: false }), /storage write/);
  assert.deepEqual(h.calls, []);
});

test('explicit actions fail when the stored choice cannot be read', async () => {
  const h = fixture({ serverId: 7, readError: true });
  await assert.rejects(h.service.disableCurrentDevicePush({ userId: 1 }));
  await assert.rejects(h.service.registerCurrentDeviceForPush({ userId: 1, prompt: false }));
  assert.deepEqual(h.calls, []);
});

// Per-user choice and logout.
test('disable, logout, login as the same user stays off (revoked row is not re-enabled)', async () => {
  const h = fixture({ serverId: 7 });
  await h.service.disableCurrentDevicePush({ userId: 1 });
  // Logout clears only the server device id and token; the per-user choice stays.
  h.calls.length = 0;
  await h.service.syncCurrentDeviceAtStartup({ userId: 1, includePush: true });
  assert.equal(h.calls.length, 1);
  assert.equal(h.calls[0].input.notifications_enabled, false);
  assert.equal(Object.hasOwn(h.calls[0].input, 'push_token'), false);
});

test('another account does not inherit a choice, and its own choice does not change the first', async () => {
  const h = fixture({ serverId: 7, choice: false });
  await h.service.syncCurrentDeviceAtStartup({ userId: 2, includePush: true });
  const { input } = h.calls[0];
  assert.equal(input.push_token, 'ExponentPushToken[synthetic-b1124]');
  assert.equal(Object.hasOwn(input, 'notifications_enabled'), false, 'User 2 gets the first-opt-in behaviour');
  assert.equal(h.choice(1), false, 'User 1 choice untouched');
  assert.equal(h.choice(2), null);
  await h.service.registerCurrentDeviceForPush({ userId: 2, prompt: false });
  assert.equal(h.choice(2), true);
  assert.equal(h.choice(1), false);
  h.calls.length = 0;
  await h.service.syncCurrentDeviceAtStartup({ userId: 1, includePush: true });
  assert.equal(h.calls[0].input.notifications_enabled, false);
});

// Race: startup in flight when the user disables.
test('explicit disable waits for an in-flight startup and wins', async () => {
  let release;
  const gate = new Promise(resolve => { release = resolve; });
  const h = fixture({ serverId: 7, postGate: gate });
  const startup = h.service.syncCurrentDeviceAtStartup({ userId: 1, includePush: true });
  const disable = h.service.disableCurrentDevicePush({ userId: 1 });
  await new Promise(resolve => setTimeout(resolve, 20));
  assert.deepEqual(h.calls, [], 'Nothing has landed while the startup request is still held');
  release();
  await Promise.all([startup, disable]);
  assert.deepEqual(h.calls.map(c => c.method), ['POST', 'PATCH']);
  assert.equal(h.calls.at(-1).input.notifications_enabled, false, 'The explicit disable is the last write');
  assert.equal(h.choice(), false);
});

test('startup queued behind an explicit disable sees the new choice', async () => {
  const h = fixture({ serverId: 7 });
  const disable = h.service.disableCurrentDevicePush({ userId: 1 });
  const startup = h.service.syncCurrentDeviceAtStartup({ userId: 1, includePush: true });
  await Promise.all([disable, startup]);
  const post = h.calls.find(c => c.method === 'POST');
  assert.equal(post.input.notifications_enabled, false);
  assert.equal(Object.hasOwn(post.input, 'push_token'), false);
});

test('disable without saved device sends nothing but still records the choice; unsupported startup never calls HTTP', async () => {
  const missing = fixture();
  assert.equal(await missing.service.disableCurrentDevicePush({ userId: 1 }), null);
  assert.deepEqual(missing.calls, []);
  assert.equal(missing.choice(), false);
  const web = fixture({ platform: 'web' });
  await assert.rejects(web.service.syncCurrentDeviceAtStartup({ userId: 1, includePush: true }));
  assert.deepEqual(web.calls, []);
});
