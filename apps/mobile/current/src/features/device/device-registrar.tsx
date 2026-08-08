import * as Application from 'expo-application';
import * as Device from 'expo-device';
import { getCalendars, getLocales } from 'expo-localization';
import { Platform } from 'react-native';
import { useEffect } from 'react';
import { useAuth } from '@/features/auth/auth-provider';
import { api } from '@/lib/api/endpoints';
import { getInstallationId, setServerDeviceId } from '@/lib/storage';

export function DeviceRegistrar() {
  const { status } = useAuth();

  useEffect(() => {
    if (status !== 'authenticated' || (Platform.OS !== 'android' && Platform.OS !== 'ios')) return;

    let cancelled = false;
    void (async () => {
      try {
        const installationId = await getInstallationId();
        const locale = getLocales()[0]?.languageTag ?? 'sr-Latn';
        const timezone = getCalendars()[0]?.timeZone ?? 'Europe/Belgrade';
        const device = await api.devices.register({
          installation_id: installationId,
          platform: Platform.OS,
          device_name: Device.modelName ?? Device.deviceName ?? `${Platform.OS} uređaj`,
          app_version: Application.nativeApplicationVersion ?? '0.3.1',
          build_number: Application.nativeBuildVersion ?? '1',
          locale,
          timezone
        });
        if (!cancelled) await setServerDeviceId(device.id);
      } catch {
        // Registracija je best-effort; ekran uređaja omogućava kasniji retry.
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [status]);

  return null;
}
