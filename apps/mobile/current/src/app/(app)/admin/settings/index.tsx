import type { Href } from 'expo-router';
import { router } from 'expo-router';
import { useMemo } from 'react';
import { StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

const DESTINATIONS = [
  ['Automatizacija', '/admin/settings/automation'],
  ['Cloudflare Turnstile', '/admin/settings/turnstile'],
  ['Izgled sajta', '/admin/settings/appearance'],
  ['E-mail porudžbina', '/admin/settings/order-emails'],
  ['Poslovni dokumenti', '/admin/settings/documents'],
  ['Žiro računi', '/admin/settings/bank-accounts'],
] as const;

// MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37
export default function AdminSystemSettingsHub() {
  const { can, bootstrap } = useAuth();
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);

  if (!can('system.manage_settings')) {
    return <Screen><PageHeader title="Sistemska podešavanja" /><Text style={styles.muted}>Nemaš dozvolu za sistemska podešavanja.</Text></Screen>;
  }

  return (
    <Screen contentStyle={styles.content}>
      <PageHeader title="Sistemska podešavanja" eyebrow="Administracija · Sistem" name={bootstrap?.user.name} />
      <Card style={styles.card}>
        <Text style={styles.title}>Centralna konfiguracija</Text>
        <Text style={styles.muted}>Podešavanja su podeljena po odgovornosti kako bi ekran ostao brz i pregledan.</Text>
        <View style={styles.actions}>
          {DESTINATIONS.map(([label, route]) => (
            <Button key={route} variant="secondary" onPress={() => router.push(route as Href)}>{label}</Button>
          ))}
        </View>
      </Card>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: 120 },
    card: { gap: spacing.md },
    title: { ...typography.h2, color: theme.ink },
    muted: { ...typography.body, color: theme.muted },
    actions: { gap: spacing.sm },
  });
}
