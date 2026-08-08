import { useQueryClient } from '@tanstack/react-query';
import * as Notifications from 'expo-notifications';
import { router } from 'expo-router';
import { useEffect } from 'react';
import { Platform } from 'react-native';
import { useAuth } from '@/features/auth/auth-provider';
import { getPushPermissionState, registerCurrentDeviceForPush } from '@/features/notifications/push-service';

const handledResponses = new Set<string>();

Notifications.setNotificationHandler({
  handleNotification: async () => ({
    shouldPlaySound: true,
    shouldSetBadge: false,
    shouldShowBanner: true,
    shouldShowList: true
  })
});

function numberFromUnknown(value: unknown): number | null {
  const number = typeof value === 'number' ? value : Number(value);
  return Number.isInteger(number) && number > 0 ? number : null;
}

function openNotificationResponse(response: Notifications.NotificationResponse): void {
  const requestId = response.notification.request.identifier;
  if (handledResponses.has(requestId)) return;
  handledResponses.add(requestId);

  const data = response.notification.request.content.data ?? {};
  const orderId = numberFromUnknown(data.order_id);
  if (orderId) {
    router.push({ pathname: '/order/[id]', params: { id: String(orderId) } });
    return;
  }

  const route = typeof data.route === 'string' ? data.route : '';
  const orderMatch = route.match(/^\/(?:orders?|order)\/(\d+)$/i);
  if (orderMatch?.[1]) {
    router.push({ pathname: '/order/[id]', params: { id: orderMatch[1] } });
    return;
  }

  router.push('/notifications');
}

export function PushNotificationBridge() {
  const queryClient = useQueryClient();
  const { status, hasFeature, refreshBootstrap } = useAuth();
  const supportedPlatform = Platform.OS === 'android' || Platform.OS === 'ios';

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
    if (status !== 'authenticated' || !supportedPlatform || !hasFeature('push_registration')) return;

    void (async () => {
      try {
        const permission = await getPushPermissionState();
        if (!permission.granted) return;
        await registerCurrentDeviceForPush({ prompt: false });
        await queryClient.invalidateQueries({ queryKey: ['devices'] });
      } catch {
        // Silent token refresh must never block startup. Manual onboarding surfaces errors to the user.
      }
    })();
  }, [hasFeature, queryClient, status, supportedPlatform]);

  return null;
}
