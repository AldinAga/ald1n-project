import { useQueryClient } from '@tanstack/react-query';
import * as Notifications from 'expo-notifications';
import { router } from 'expo-router';
import { useEffect } from 'react';
import { Platform } from 'react-native';
import { useAuth } from '@/features/auth/auth-provider';
import { resolvePushNotificationNavigation } from '@/features/notifications/notification-routing';
import { syncCurrentDeviceAtStartup } from '@/features/notifications/push-service';

const handledResponses = new Set<string>();

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldPlaySound: true,
    shouldSetBadge: false,
    shouldShowBanner: true,
    shouldShowList: true
  })
});

function openNotificationResponse(response: Notifications.NotificationResponse): void {
  const requestId = response.notification.request.identifier;
  if (handledResponses.has(requestId)) return;
  handledResponses.add(requestId);

  const data = response.notification.request.content.data ?? {};
  const destination = resolvePushNotificationNavigation(data);

  if (destination.kind === 'order') {
    router.push({
      pathname: '/order/[id]',
      params: { id: String(destination.id) }
    });
    return;
  }

  if (destination.kind === 'after_sales_case') {
    router.push({
      pathname: '/after-sales/[id]',
      params: { id: String(destination.id) }
    });
    return;
  }

  if (destination.kind === 'product') {
    router.push({
      pathname: '/product/[slug]',
      params: { slug: destination.slug }
    });
    return;
  }
  router.push('/notifications');
}

export function PushNotificationBridge() {
  const queryClient = useQueryClient();
  const { status, bootstrap, refreshBootstrap } = useAuth();
  const supportedPlatform = Platform.OS === 'android' || Platform.OS === 'ios';
  const bootstrapReady = bootstrap !== null;
  const pushRegistrationEnabled = Boolean(bootstrap?.features.push_registration);

  useEffect(() => {
    if (status !== 'authenticated' || !supportedPlatform) return;

    const received = Notifications.addNotificationReceivedListener(() => {
      void queryClient.invalidateQueries({ queryKey: ['notifications'] });
      void refreshBootstrap();
    });
    const responded = Notifications.addNotificationResponseReceivedListener(openNotificationResponse);

    void Notifications.getLastNotificationResponseAsync().then(async (response) => {
      if (!response) return;
      openNotificationResponse(response);
      await Notifications.clearLastNotificationResponseAsync();
    }).catch(() => undefined);

    return () => {
      received.remove();
      responded.remove();
    };
  }, [queryClient, refreshBootstrap, status, supportedPlatform]);

  useEffect(() => {
    if (status !== 'authenticated' || !bootstrapReady || !supportedPlatform) return;

    void (async () => {
      try {
        await syncCurrentDeviceAtStartup({ includePush: pushRegistrationEnabled });
        await queryClient.invalidateQueries({ queryKey: ['devices'] });
      } catch {
        // Startup device sync is best-effort; manual device/push screens surface actionable errors.
      }
    })();
  }, [bootstrapReady, pushRegistrationEnabled, queryClient, status, supportedPlatform]);

  return null;
}
