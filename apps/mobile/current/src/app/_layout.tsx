import 'react-native-gesture-handler';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { TamaguiProvider } from 'tamagui';
import { Stack } from 'expo-router';
import * as SplashScreen from 'expo-splash-screen';
import { StatusBar } from 'expo-status-bar';
import { useEffect } from 'react';
import { GestureHandlerRootView } from 'react-native-gesture-handler';
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { AuthProvider, useAuth } from '@/features/auth/auth-provider';
import { DeviceRegistrar } from '@/features/device/device-registrar';
import { PushNotificationBridge } from '@/features/notifications/push-notification-bridge';
import { CartProvider } from '@/features/cart/cart-provider';
import { colors } from '@/constants/theme';
import { tamaguiConfig } from '../../tamagui.config';

void SplashScreen.preventAutoHideAsync();

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 45_000,
      retry: 1,
      refetchOnWindowFocus: false
    },
    mutations: { retry: 0 }
  }
});

function AppReady() {
  const { status } = useAuth();
  useEffect(() => {
    if (status !== 'hydrating') void SplashScreen.hideAsync();
  }, [status]);
  return <>
    <DeviceRegistrar />
    <PushNotificationBridge />
  </>;
}

export default function RootLayout() {
  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <TamaguiProvider config={tamaguiConfig} defaultTheme="light">
        <SafeAreaProvider>
        <QueryClientProvider client={queryClient}>
          <AuthProvider>
            <CartProvider>
            <StatusBar style="dark" />
            <AppReady />
            <Stack screenOptions={{ headerShown: false, contentStyle: { backgroundColor: colors.background } }}>
              <Stack.Screen name="index" />
              <Stack.Screen name="(auth)" />
              <Stack.Screen name="(app)" />
              <Stack.Screen name="+not-found" />
            </Stack>
            </CartProvider>
          </AuthProvider>
        </QueryClientProvider>
      </SafeAreaProvider>
      </TamaguiProvider>
    </GestureHandlerRootView>
  );
}
