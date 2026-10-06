import { Platform } from 'react-native';
import * as Device from 'expo-device';
import * as SecureStore from 'expo-secure-store';
import RestoreCredentialsNative from '../../../modules/ald1n-restore-credentials/src/Ald1nRestoreCredentials';
import { api } from '@/lib/api/endpoints';
import { ApiError } from '@/lib/api/client';

const SYNC_KEY = 'ald1n.restore-credential.synced.v1';
const secureOptions: SecureStore.SecureStoreOptions = { keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY };

function nativeModule() {
  if (Platform.OS !== 'android' || !RestoreCredentialsNative) return null;
  return RestoreCredentialsNative.isSupported() ? RestoreCredentialsNative : null;
}

function unavailable(error: unknown): boolean {
  return error instanceof ApiError && [404, 503].includes(error.status);
}

export async function tryRestoreSession(): Promise<string | null> {
  const native = nativeModule();
  if (!native) return null;
  try {
    const ceremony = await api.auth.restoreOptions();
    const responseJson = await native.getRestoreCredential(JSON.stringify(ceremony.options));
    const deviceName = Device.modelName ?? Device.deviceName ?? 'Ald1n mobile device';
    const result = await api.auth.restoreVerify({
      ceremony_id: ceremony.ceremony_id,
      credential: JSON.parse(responseJson) as Record<string, unknown>,
      device_name: deviceName,
    });
    await SecureStore.setItemAsync(SYNC_KEY, '1', secureOptions);
    return result.token;
  } catch (error) {
    if (unavailable(error)) return null;
    return null;
  }
}

export async function ensureRestoreCredential(): Promise<void> {
  const native = nativeModule();
  if (!native) return;
  const synced = await SecureStore.getItemAsync(SYNC_KEY, secureOptions);
  if (synced === '1') return;
  try {
    const ceremony = await api.auth.restoreRegistrationOptions();
    const responseJson = await native.createRestoreCredential(JSON.stringify(ceremony.options));
    await api.auth.restoreRegister({
      ceremony_id: ceremony.ceremony_id,
      credential: JSON.parse(responseJson) as Record<string, unknown>,
    });
    await SecureStore.setItemAsync(SYNC_KEY, '1', secureOptions);
  } catch (error) {
    if (unavailable(error)) return;
    // Existing password/Google login must remain successful if restore setup is unavailable.
  }
}

export async function clearRestoreCredential(): Promise<void> {
  await SecureStore.deleteItemAsync(SYNC_KEY, secureOptions);
  const native = nativeModule();
  if (!native) return;
  try {
    await native.clearRestoreCredential();
  } catch {
    // Server/local sign-out remains authoritative even when the platform clear fails.
  }
}
