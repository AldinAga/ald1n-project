// MOBILE_GLOBAL_APP_SHELL_V06
import * as Application from 'expo-application';
import { Redirect, Stack } from 'expo-router';
import { Linking, Platform, StyleSheet, Text, View } from 'react-native';

import { AppBottomNav } from '@/components/layout/app-bottom-nav';
import { Button } from '@/components/ui/button';
import { BrandMark } from '@/components/ui/brand-mark';
import { LoadingState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { compareVersions } from '@/lib/formatters';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';

export default function AppLayout() {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const { status, bootstrap } = useAuth();

  if (status === 'hydrating') return <LoadingState />;
  if (status !== 'authenticated') return <Redirect href="/login" />;

  const platformConfig = Platform.OS === 'ios'
    ? bootstrap?.app.ios
    : bootstrap?.app.android;
  const version = Application.nativeApplicationVersion ?? '0.8.0';
  const blocked = platformConfig
    ? compareVersions(version, platformConfig.minimum_supported_version) < 0
    : false;

  if (blocked) {
    return (
      <View style={styles.blocked}>
        <BrandMark size={64} />
        <Text style={styles.title}>Potrebno je ažuriranje</Text>
        <Text style={styles.copy}>
          Ova verzija aplikacije više nije podržana. Minimalna verzija je{' '}
          {platformConfig?.minimum_supported_version}.
        </Text>
        {platformConfig?.store_url ? (
          <Button onPress={() => void Linking.openURL(platformConfig.store_url!)}>
            Otvori prodavnicu
          </Button>
        ) : null}
      </View>
    );
  }

  return (
    <View style={styles.shell}>
      <View style={styles.stack}>
        <Stack
          screenOptions={{
            headerShown: false,
            contentStyle: { backgroundColor: themeColors.background },
          }}
        />
      </View>
      <AppBottomNav />
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    shell: {
      flex: 1,
      backgroundColor: theme.background,
    },
    stack: {
      flex: 1,
      minHeight: 0,
      backgroundColor: theme.background,
    },
    blocked: {
      flex: 1,
      padding: spacing.xxxl,
      alignItems: 'center',
      justifyContent: 'center',
      gap: spacing.lg,
      backgroundColor: theme.background,
    },
    title: {
      ...typography.h1,
      color: theme.ink,
      textAlign: 'center',
    },
    copy: {
      ...typography.body,
      color: theme.muted,
      textAlign: 'center',
    },
  });
}
