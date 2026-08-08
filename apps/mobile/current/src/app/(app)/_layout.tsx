import * as Application from 'expo-application';
import { Redirect, Stack } from 'expo-router';
import { Linking, Platform, StyleSheet, Text, View } from 'react-native';
import { Button } from '@/components/ui/button';
import { BrandMark } from '@/components/ui/brand-mark';
import { LoadingState } from '@/components/ui/states';
import { colors, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { compareVersions } from '@/lib/formatters';

export default function AppLayout() {
  const { status, bootstrap } = useAuth();
  if (status === 'hydrating') return <LoadingState />;
  if (status !== 'authenticated') return <Redirect href="/login" />;

  const platformConfig = Platform.OS === 'ios' ? bootstrap?.app.ios : bootstrap?.app.android;
  const version = Application.nativeApplicationVersion ?? '0.3.1';
  const blocked = platformConfig ? compareVersions(version, platformConfig.minimum_supported_version) < 0 : false;

  if (blocked) {
    return (
      <View style={styles.blocked}>
        <BrandMark size={64} />
        <Text style={styles.title}>Potrebno je ažuriranje</Text>
        <Text style={styles.copy}>Ova verzija aplikacije više nije podržana. Minimalna verzija je {platformConfig?.minimum_supported_version}.</Text>
        {platformConfig?.store_url ? <Button onPress={() => void Linking.openURL(platformConfig.store_url!)}>Otvori prodavnicu</Button> : null}
      </View>
    );
  }

  return <Stack screenOptions={{ headerShown: false }} />;
}

const styles = StyleSheet.create({
  blocked: { flex: 1, padding: spacing.xxxl, alignItems: 'center', justifyContent: 'center', gap: spacing.lg, backgroundColor: colors.background },
  title: { ...typography.h1, color: colors.ink, textAlign: 'center' },
  copy: { ...typography.body, color: colors.muted, textAlign: 'center' }
});
