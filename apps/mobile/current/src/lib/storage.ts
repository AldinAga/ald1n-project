import * as Crypto from 'expo-crypto';
import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'ald1n.auth.token';
const INSTALLATION_KEY = 'ald1n.installation.id';
const DEVICE_ID_KEY = 'ald1n.device.server-id';

const secureOptions: SecureStore.SecureStoreOptions = {
  keychainAccessible: SecureStore.WHEN_UNLOCKED_THIS_DEVICE_ONLY
};

export const tokenStore = {
  get: () => SecureStore.getItemAsync(TOKEN_KEY, secureOptions),
  set: (token: string) => SecureStore.setItemAsync(TOKEN_KEY, token, secureOptions),
  clear: () => SecureStore.deleteItemAsync(TOKEN_KEY, secureOptions)
};

export async function getInstallationId(): Promise<string> {
  const existing = await SecureStore.getItemAsync(INSTALLATION_KEY, secureOptions);
  if (existing) return existing;

  const generated = Crypto.randomUUID();
  await SecureStore.setItemAsync(INSTALLATION_KEY, generated, secureOptions);
  return generated;
}

export async function setServerDeviceId(id: number): Promise<void> {
  await SecureStore.setItemAsync(DEVICE_ID_KEY, String(id), secureOptions);
}

export async function getServerDeviceId(): Promise<number | null> {
  const value = await SecureStore.getItemAsync(DEVICE_ID_KEY, secureOptions);
  const id = Number(value);
  return Number.isInteger(id) && id > 0 ? id : null;
}

export async function clearServerDeviceId(): Promise<void> {
  await SecureStore.deleteItemAsync(DEVICE_ID_KEY, secureOptions);
}
