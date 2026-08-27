import 'react-native-gesture-handler';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { TamaguiProvider } from 'tamagui';

import { AppFeedbackProvider } from '@/components/ui/app-feedback';
import { AuthProvider, useAuth } from '@/features/auth/auth-provider';
import { CartProvider } from '@/features/cart/cart-provider';
import { DeviceRegistrar } from '@/features/device/device-registrar';
import { PushNotificationBridge } from '@/features/notifications/push-notification-bridge';
import { AppPreferencesProvider, useAppPreferences } from '@/features/preferences/app-preferences';
import { useAppTheme } from '@/theme/app-theme';
import { tamaguiConfig } from '../../tamagui.config';

void SplashScreen.preventAutoHideAsync();

const queryClient = new QueryClient({
  defaultOptions: {
    queries: { staleTime: 45_000, retry: 1, refetchOnWindowFocus: false },
    mutations: { retry: 0 },
  },
});

function AppReady() {
  const { status } = useAuth();
  const { hydrated } = useAppPreferences();
  useEffect(() => {
    if (status !== 'hydrating' && hydrated) void SplashScreen.hideAsync();
  }, [hydrated, status]);
  return (
    <>
      <DeviceRegistrar />
      <PushNotificationBridge />
    </>
  );
}

function ThemedApplication() {
  const { scheme, colors: themeColors, isDark } = useAppTheme();
  return (
    <TamaguiProvider config={tamaguiConfig} defaultTheme={scheme}>
      <SafeAreaProvider>
        <AppFeedbackProvider>
          <CartProvider>
            <StatusBar style={isDark ? 'light' : 'dark'} />
            <AppReady />
            <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: themeColors.background } }}>
              <Stack.Screen name="index" />
              <Stack.Screen name="(auth)" />
              <Stack.Screen name="(app)" />
              <Stack.Screen name="+not-found" />
            </Stack>
          </CartProvider>
        </AppFeedbackProvider>
      </SafeAreaProvider>
    </TamaguiProvider>
  );
}

function PreferenceBoundary() {
  const { bootstrap } = useAuth();
  const userId = bootstrap?.user.id ?? null;
  return (
    <AppPreferencesProvider key={userId ?? 'anonymous'} userId={userId}>
      <ThemedApplication />
    </AppPreferencesProvider>
  );
}

export default function RootLayout() {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <QueryClientProvider client={queryClient}>
        <AuthProvider>
          <PreferenceBoundary />
        </AuthProvider>
      </QueryClientProvider>
    </GestureHandlerRootView>
  );
}
