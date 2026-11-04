import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';
import ts from 'typescript';

// Exercise the real passive and explicit callers; only native I/O and HTTP are simulated.
function fixture(options = {}) {
  const calls = [], saved = [];
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
    '@/lib/api/client': { ApiError },
    '@/lib/api/endpoints': { api: { devices: {
      register: async input => { calls.push({ method: 'POST', input }); if (options.postError) throw options.postError; return device; },
      update: async (id, input) => { calls.push({ method: 'PATCH', id, input }); if (options.status) throw new ApiError(options.status); return device; },
    } } },
    '@/lib/storage': { getInstallationId: async () => '5c352b34-7aa4-4cbd-8376-5b36e2be56e1', getServerDeviceId: async () => options.serverId ?? null, setServerDeviceId: async id => { saved.push(id); } },
  };
  const source = readFileSync(new URL('../../src/features/notifications/push-service.ts', import.meta.url), 'utf8');
  assert.equal(ts.createSourceFile('push-service.ts', source, ts.ScriptTarget.Latest, true).parseDiagnostics.length, 0);
  const exports = {};
  runInNewContext(ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022 } }).outputText, {
    exports, Error, require: name => { assert.ok(Object.hasOwn(native, name), 'Unexpected import ' + name); return native[name]; },
  }, { filename: 'push-service.ts' });
  return { service: exports, calls, saved, device, ApiError, counters: () => ({ prompts, channels, tokens }) };
}

for (const platform of ['android', 'ios']) {
  test('passive startup preserves consent on ' + platform, async () => {
    const h = fixture({ platform });
    assert.equal(await h.service.syncCurrentDeviceAtStartup({ includePush: true }), h.device);
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
    await h.service.syncCurrentDeviceAtStartup({ includePush });
    assert.equal(h.calls.length, 1);
    assert.equal(Object.hasOwn(h.calls[0].input, 'notifications_enabled'), false);
    assert.equal(Object.hasOwn(h.calls[0].input, 'push_token'), false);
    assert.deepEqual(h.saved, [17]);
    assert.equal(h.counters().prompts, 0);
  });
}

for (const [name, options, methods] of [
  ['saved ID', { serverId: 7 }, ['PATCH']],
  ['missing ID', {}, ['POST']],
  ['404 fallback', { serverId: 7, status: 404 }, ['PATCH', 'POST']],
]) {
  test('intentional enable retains true and token through ' + name, async () => {
    const h = fixture(options);
    await h.service.registerCurrentDeviceForPush({ prompt: false });
    assert.deepEqual(h.calls.map(x => x.method), methods);
    for (const call of h.calls) {
      assert.equal(call.input.notifications_enabled, true);
      assert.equal(call.input.push_provider, 'expo');
      assert.equal(call.input.push_token, 'ExponentPushToken[synthetic-b1124]');
    }
    assert.equal(h.counters().prompts, 0);
  });
}

test('intentional enable prompts only when requested and permission is not granted', async () => {
  const h = fixture({ permission: 'undetermined' });
  await h.service.registerCurrentDeviceForPush({ prompt: true });
  assert.equal(h.counters().prompts, 1);
  assert.equal(h.calls[0].input.notifications_enabled, true);
});

test('non404 enable failure propagates without POST or saved-ID replacement', async () => {
  const h = fixture({ serverId: 7, status: 500 });
  await assert.rejects(h.service.registerCurrentDeviceForPush({ prompt: false }), e => e instanceof h.ApiError && e.status === 500);
  assert.deepEqual(h.calls.map(x => x.method), ['PATCH']);
  assert.deepEqual(h.saved, []);
});

test('intentional disable retains false and explicit token removal', async () => {
  const h = fixture({ serverId: 7 });
  await h.service.disableCurrentDevicePush();
  assert.equal(h.calls[0].id, 7);
  assert.equal(h.calls[0].input.notifications_enabled, false);
  assert.equal(h.calls[0].input.push_provider, null);
  assert.equal(h.calls[0].input.push_token, null);
});

test('disable without saved device is a no-op and unsupported startup never calls HTTP', async () => {
  const missing = fixture();
  assert.equal(await missing.service.disableCurrentDevicePush(), null);
  assert.deepEqual(missing.calls, []);
  const web = fixture({ platform: 'web' });
  await assert.rejects(web.service.syncCurrentDeviceAtStartup({ includePush: true }));
  assert.deepEqual(web.calls, []);
});
