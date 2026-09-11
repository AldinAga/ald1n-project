import * as Crypto from 'expo-crypto';
import * as SecureStore from 'expo-secure-store';

const TOKEN_KEY = 'ald1n.auth.token';
const INSTALLATION_KEY = 'ald1n.installation.id';
const DEVICE_ID_KEY = 'ald1n.device.server-id';
const USER_PREFERENCES_PREFIX = 'ald1n.preferences.user.';

export type StoredPriceDisplayMode = 'source' | 'RSD' | 'EUR';

export type StoredAppPreferences = {
  theme_mode: 'system' | 'light' | 'dark';
  price_display_mode?: StoredPriceDisplayMode;
  primary_currency?: 'RSD' | 'EUR';
};

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

function userPreferencesKey(userId: number): string {
  if (!Number.isInteger(userId) || userId <= 0) {
    throw new Error('Neispravan korisnički identifikator za lokalna podešavanja.');
  }
  return USER_PREFERENCES_PREFIX + String(userId);
}

export async function getUserAppPreferences(userId: number): Promise<StoredAppPreferences | null> {
  const raw = await SecureStore.getItemAsync(userPreferencesKey(userId), secureOptions);
  if (!raw) return null;
  try {
    const parsed = JSON.parse(raw) as Partial<StoredAppPreferences>;
    if (!['system', 'light', 'dark'].includes(String(parsed.theme_mode))) return null;

    const explicitDisplay = ['source', 'RSD', 'EUR'].includes(String(parsed.price_display_mode))
      ? parsed.price_display_mode as StoredPriceDisplayMode
      : null;
    const legacyCurrency = ['RSD', 'EUR'].includes(String(parsed.primary_currency))
      ? parsed.primary_currency as 'RSD' | 'EUR'
      : null;
    const displayMode = explicitDisplay ?? legacyCurrency ?? 'source';

    return {
      theme_mode: parsed.theme_mode as StoredAppPreferences['theme_mode'],
      price_display_mode: displayMode,
      primary_currency: legacyCurrency ?? (displayMode === 'EUR' ? 'EUR' : 'RSD'),
    };
  } catch {
    return null;
  }
}

export async function setUserAppPreferences(
  userId: number,
  preferences: StoredAppPreferences,
): Promise<void> {
  await SecureStore.setItemAsync(
    userPreferencesKey(userId),
    JSON.stringify(preferences),
    secureOptions,
  );
}