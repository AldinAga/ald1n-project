import * as Application from 'expo-application';
import Constants from 'expo-constants';
import * as Device from 'expo-device';
import { getCalendars, getLocales } from 'expo-localization';
import * as Notifications from 'expo-notifications';
import { Platform } from 'react-native';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import { getInstallationId, getPushConsentChoice, getServerDeviceId, setPushConsentChoice, setServerDeviceId } from '@/lib/storage';
import type { MobileDevice, MobileDeviceInput, MobileDeviceUpdateInput } from '@/types/api';

export const BUSINESS_NOTIFICATION_CHANNEL = 'business-updates';

export type PushPermissionState = {
  status: string;
  granted: boolean;
  canAskAgain: boolean;
};

export async function ensureBusinessNotificationChannel(): Promise<void> {
  if (Platform.OS !== 'android') return;

  await Notifications.setNotificationChannelAsync(BUSINESS_NOTIFICATION_CHANNEL, {
    name: 'Poslovna obaveštenja',
    description: 'Statusi porudžbina, uplata, dokumenata i drugih poslovnih događaja.',
    importance: Notifications.AndroidImportance.HIGH,
    sound: 'default',
    vibrationPattern: [0, 250, 250, 250],
    lightColor: '#6D45E5'
  });
}

export async function getPushPermissionState(): Promise<PushPermissionState> {
  const permission = await Notifications.getPermissionsAsync();
  return {
    status: permission.status,
    granted: permission.status === 'granted',
    canAskAgain: permission.canAskAgain
  };
}

function projectId(): string {
  const value = Constants.expoConfig?.extra?.eas?.projectId ?? Constants.easConfig?.projectId;
  if (!value) throw new Error('EAS projectId nije dostupan u Expo konfiguraciji.');
  return String(value);
}

async function deviceMetadata(): Promise<MobileDeviceUpdateInput> {
  const locale = getLocales()[0]?.languageTag ?? 'sr-Latn';
  const timezone = getCalendars()[0]?.timeZone ?? 'Europe/Belgrade';

  return {
    device_name: Device.modelName ?? Device.deviceName ?? `${Platform.OS} uređaj`,
    app_version: Application.nativeApplicationVersion ?? '0.4.0',
    build_number: Application.nativeBuildVersion ?? '1',
    locale,
    timezone
  };
}

// Startup and explicit push actions run one at a time, so a startup request that read "no choice"
// can never land after the user's explicit disable.
let queue: Promise<unknown> = Promise.resolve();
function serialized<T>(task: () => Promise<T>): Promise<T> {
  const run = queue.then(task, task);
  queue = run.catch(() => undefined);
  return run;
}

// Explicit user action: remember the choice locally first (a failed write fails the action, so
// "server off, local unknown" cannot happen), then call the backend; roll the local value back if
// the backend call fails.
async function withStoredChoice<T>(userId: number, next: boolean, action: () => Promise<T>): Promise<T> {
  const previous = await getPushConsentChoice(userId);
  await setPushConsentChoice(userId, next);
  try {
    return await action();
  } catch (error) {
    await setPushConsentChoice(userId, previous).catch(() => undefined);
    throw error;
  }
}

export function syncCurrentDeviceAtStartup(options: { includePush: boolean; userId: number }): Promise<MobileDevice> {
  return serialized(() => syncStartup(options));
}

async function syncStartup(options: { includePush: boolean; userId: number }): Promise<MobileDevice> {
  if (Platform.OS !== 'android' && Platform.OS !== 'ios') {
    throw new Error('Startup device sync je dostupan samo na Android/iOS uredjajima.');
  }

  const installationId = await getInstallationId();
  const metadata = await deviceMetadata();
  let pushFields: Partial<MobileDeviceInput> = {};

  // Startup is passive: it never asserts consent. The backend keeps the stored value on an existing
  // device; a user who switched push off is re-asserted as false because the backend defaults a
  // missing value to true when it creates or re-activates a device row. If the stored choice cannot
  // be read, fail closed: send nothing (a request could create or re-activate a row as enabled).
  const choice = await getPushConsentChoice(options.userId);
  if (choice === false) {
    pushFields = { notifications_enabled: false };
  } else if (options.includePush && Device.isDevice) {
    try {
      const permission = await Notifications.getPermissionsAsync();
      if (permission.status === 'granted') {
        await ensureBusinessNotificationChannel();
        const token = (await Notifications.getExpoPushTokenAsync({ projectId: projectId() })).data;
        if (token) {
          pushFields = { push_provider: 'expo', push_token: token };
        }
      }
    } catch {
      // Push enrichment must not prevent the canonical device identity heartbeat.
    }
  }

  const device = await api.devices.register({
    installation_id: installationId,
    platform: Platform.OS as 'android' | 'ios',
    ...metadata,
    ...pushFields
  });
  await setServerDeviceId(device.id);
  return device;
}
async function registerFallback(input: MobileDeviceUpdateInput): Promise<MobileDevice> {
  const installationId = await getInstallationId();
  return api.devices.register({
    installation_id: installationId,
    platform: Platform.OS as 'android' | 'ios',
    ...input
  });
}

async function updateOrRegister(input: MobileDeviceUpdateInput): Promise<MobileDevice> {
  const serverDeviceId = await getServerDeviceId();
  if (serverDeviceId) {
    try {
      return await api.devices.update(serverDeviceId, input);
    } catch (error) {
      if (!(error instanceof ApiError) || error.status !== 404) throw error;
    }
  }

  const device = await registerFallback(input);
  await setServerDeviceId(device.id);
  return device;
}

export function registerCurrentDeviceForPush(options: { prompt: boolean; userId: number }): Promise<MobileDevice> {
  return serialized(async () => {
  if (Platform.OS !== 'android' && Platform.OS !== 'ios') {
    throw new Error('Push registracija je dostupna samo na Android/iOS uređajima.');
  }
  if (!Device.isDevice) {
    throw new Error('Push registraciju testiraj na fizičkom Android/iOS uređaju.');
  }

  await ensureBusinessNotificationChannel();

  let permission = await Notifications.getPermissionsAsync();
  if (permission.status !== 'granted' && options.prompt) {
    permission = await Notifications.requestPermissionsAsync();
  }
  if (permission.status !== 'granted') {
    throw new Error(
      permission.canAskAgain
        ? 'Dozvola za push obaveštenja nije odobrena.'
        : 'Push dozvola je isključena u sistemskim podešavanjima uređaja.'
    );
  }

  const token = (await Notifications.getExpoPushTokenAsync({ projectId: projectId() })).data;
  if (!token) throw new Error('Expo push token nije dobijen.');

  const metadata = await deviceMetadata();
  const device = await withStoredChoice(options.userId, true, () => updateOrRegister({
    ...metadata,
    push_provider: 'expo',
    push_token: token,
    notifications_enabled: true
  }));
  await setServerDeviceId(device.id);
  return device;
  });
}

export function disableCurrentDevicePush(options: { userId: number }): Promise<MobileDevice | null> {
  return serialized(async () => {
    const serverDeviceId = await getServerDeviceId();
    return withStoredChoice(options.userId, false, async () => {
      if (!serverDeviceId) return null;
      return api.devices.update(serverDeviceId, {
        push_provider: null,
        push_token: null,
        notifications_enabled: false
      });
    });
  });
}
