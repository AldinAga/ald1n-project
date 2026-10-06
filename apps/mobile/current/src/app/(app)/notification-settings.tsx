import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useFocusEffect, router } from 'expo-router';
import { useCallback, useState } from 'react';
import { Pressable, StyleSheet, Switch, Text, View } from 'react-native';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { Glyph } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';
import { useAuth } from '@/features/auth/auth-provider';
import {
  disableCurrentDevicePush,
  getPushPermissionState,
  registerCurrentDeviceForPush,
  type PushPermissionState
} from '@/features/notifications/push-service';
import { api } from '@/lib/api/endpoints';
import type { NotificationPreferences } from '@/types/api';

const trackingChannelOptions: Array<{ value: NotificationPreferences['shipment_tracking_channel']; title: string; copy: string }> = [
  { value: 'push', title: 'Push', copy: 'Obaveštenje odmah na registrovanom telefonu.' },
  { value: 'email', title: 'E-mail', copy: 'Broj pošiljke stiže na e-mail naloga.' },
  { value: 'both', title: 'Push + E-mail', copy: 'Pošalji broj pošiljke na oba kanala.' },
];

const preferenceRows: Array<{ key: keyof NotificationPreferences; title: string; copy: string }> = [
  { key: 'order_updates', title: 'Porudžbine', copy: 'Kreiranje, status i otkazivanje porudžbine.' },
  { key: 'payment_alerts', title: 'Uplate', copy: 'Evidentirane uplate, refundacije i promene statusa.' },
  { key: 'document_updates', title: 'Dokumenti', copy: 'Predračuni, računi i druga poslovna dokumenta.' },
  { key: 'after_sales_updates', title: 'Postprodaja', copy: 'Reklamacije, povrati i postprodajni slučajevi.' },
  { key: 'warranty_updates', title: 'Garancije', copy: 'Garancije i preventivno održavanje.' },
  { key: 'service_updates', title: 'Servis', copy: 'Terenski rad i servisne aktivnosti.' },
  { key: 'receivable_updates', title: 'Potraživanja', copy: 'Rate, naplata i dospela potraživanja.' },
  { key: 'commission_updates', title: 'Provizije', copy: 'Promene statusa i isplate provizija.' },
  { key: 'stock_alerts', title: 'Lager', copy: 'Upozorenja za lager i zalihe kada su dostupna tvojoj ulozi.' },
  { key: 'daily_digest', title: 'Dnevni pregled', copy: 'Sažetak važnih događaja kada je dostupan tvojoj ulozi.' }
];

export default function NotificationSettingsScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();

  const queryClient = useQueryClient();
  const { bootstrap, refreshBootstrap, hasFeature } = useAuth();
  const [permission, setPermission] = useState<PushPermissionState | null>(null);
  const [pushAction, setPushAction] = useState(false);
  const allowed = hasFeature('notifications');
  const devices = useQuery({ queryKey: ['devices'], queryFn: api.devices.list, enabled: allowed && hasFeature('mobile_devices') });
  const preferences = bootstrap?.notification_preferences;
  const currentDevice = devices.data?.find((device) => device.is_current) ?? null;

  const refreshPermission = useCallback(async () => {
    try {
      setPermission(await getPushPermissionState());
    } catch {
      setPermission(null);
    }
  }, []);

  useFocusEffect(useCallback(() => {
    void refreshPermission();
  }, [refreshPermission]));

  const preferenceMutation = useMutation({
    mutationFn: (input: Partial<NotificationPreferences>) => api.account.notificationPreferences(input),
    onSuccess: async () => {
      await refreshBootstrap();
    }
  });

  if (!allowed) return <UnavailableState title="Obaveštenja nisu dostupna" />;
  if (!preferences || devices.isLoading) return <LoadingState label="Učitavanje podešavanja…" />;
  if (devices.isError) return <ErrorState error={devices.error} onRetry={() => void devices.refetch()} />;

  const enableDevicePush = async (): Promise<boolean> => {
    setPushAction(true);
    try {
      await registerCurrentDeviceForPush({ prompt: true });
      await api.account.notificationPreferences({ push_enabled: true });
      await Promise.all([
        refreshBootstrap(),
        queryClient.invalidateQueries({ queryKey: ['devices'] }),
        refreshPermission()
      ]);
      return true;
    } catch (error) {
      feedback.notify({
        tone: 'danger',
        title: 'Push registracija nije završena',
        message: error instanceof Error ? error.message : 'Pokušaj ponovo.'
      });
      return false;
    } finally {
      setPushAction(false);
    }
  };

  const disableDevicePush = () => {
    void (async () => {
      const confirmed = await feedback.confirm({
        tone: 'danger',
        title: 'Isključi push na ovom uređaju',
        message: 'Ovo uklanja push token samo sa ovog uređaja. Inbox obaveštenja u aplikaciji ostaju dostupna.',
        confirmLabel: 'Isključi',
        cancelLabel: 'Odustani'
      });

      if (!confirmed) return;

      setPushAction(true);

      try {
        await disableCurrentDevicePush();
        await queryClient.invalidateQueries({ queryKey: ['devices'] });
        await refreshPermission();
      } catch (error) {
        feedback.notify({
          tone: 'danger',
          title: 'Push nije isključen',
          message: error instanceof Error ? error.message : 'Pokušaj ponovo.'
        });
      } finally {
        setPushAction(false);
      }
    })();
  };

  const setShipmentTrackingChannel = async (value: NotificationPreferences['shipment_tracking_channel']) => {
    if ((value === 'push' || value === 'both') && !currentDevice?.push_registered) {
      const registered = await enableDevicePush();
      if (!registered) return;
    }
    preferenceMutation.mutate({
      shipment_tracking_channel: value,
      ...(value === 'push' || value === 'both' ? { push_enabled: true } : {}),
      ...(value === 'email' || value === 'both' ? { email_enabled: true } : {}),
    });
  };

  const setPreference = (key: keyof NotificationPreferences, value: boolean) => {
    if (key === 'push_enabled' && value && !currentDevice?.push_registered) {
      void enableDevicePush();
      return;
    }
    preferenceMutation.mutate({ [key]: value });
  };

  const permissionLabel = permission?.granted
    ? 'Dozvoljeno'
    : permission?.status === 'denied'
      ? (permission.canAskAgain ? 'Nije dozvoljeno' : 'Isključeno u sistemu')
      : 'Nije zatraženo';
  const deviceReady = Boolean(currentDevice?.push_registered && currentDevice.notifications_enabled);
  const deliveryReady = hasFeature('push_delivery');

  return (
    <Screen>
      <Pressable onPress={() => router.back()}><Text style={styles.back}>‹ Nazad na nalog</Text></Pressable>
      <Text style={styles.title}>Obaveštenja i push</Text>
      <Text style={styles.copy}>Upravljaj push registracijom ovog uređaja i kategorijama poslovnih obaveštenja.</Text>

      <Card style={styles.statusCard}>
        <View style={styles.statusIcon}><Glyph name="bell" size={24} color={themeColors.primary} /></View>
        <View style={styles.statusCopy}>
          <View style={styles.statusHead}><Text style={styles.statusTitle}>Push na ovom uređaju</Text><Pill tone={deviceReady ? 'success' : 'warning'}>{deviceReady ? 'Registrovan' : 'Nije aktivan'}</Pill></View>
          <Text style={styles.statusText}>Sistemska dozvola: {permissionLabel}</Text>
          <Text style={styles.statusText}>Server push delivery: {deliveryReady ? 'aktivan' : 'još nije aktiviran'}</Text>
          {deviceReady
            ? <Button variant="secondary" onPress={disableDevicePush} loading={pushAction}>Isključi push na ovom uređaju</Button>
            : <Button onPress={() => void enableDevicePush()} loading={pushAction}>Uključi push na ovom uređaju</Button>}
        </View>
      </Card>

      {!deliveryReady ? (
        <Card muted>
          <Text style={styles.noticeTitle}>Registracija je spremna za sledeći backend korak</Text>
          <Text style={styles.noticeCopy}>Uređaj može da sačuva Expo push token, ali produkciona isporuka ostaje isključena dok server dispatcher ne bude aktiviran.</Text>
        </Card>
      ) : null}

      <Text style={styles.sectionTitle}>Kanali</Text>
      <PreferenceRow
        title="Inbox u aplikaciji"
        copy="Čuva poslovna obaveštenja u tabu Obaveštenja."
        value={preferences.in_app_enabled}
        onChange={(value) => setPreference('in_app_enabled', value)}
        disabled={preferenceMutation.isPending}
      />
      <PreferenceRow
        title="Push obaveštenja"
        copy="Dozvoljava serveru da šalje push na registrovane uređaje."
        value={preferences.push_enabled}
        onChange={(value) => setPreference('push_enabled', value)}
        disabled={preferenceMutation.isPending || pushAction}
      />
      <PreferenceRow
        title="Email obaveštenja"
        copy="Koristi email kanal kada je server kanal dostupan."
        value={preferences.email_enabled}
        onChange={(value) => setPreference('email_enabled', value)}
        disabled={preferenceMutation.isPending}
      />

      <Text style={styles.sectionTitle}>Broj pošiljke</Text>
      <Text style={styles.copy}>Izaberi kako želiš da dobiješ broj za praćenje čim SuperAdministrator evidentira slanje.</Text>
      <View style={styles.trackingOptions}>
        {trackingChannelOptions.map((option) => {
          const selected = preferences.shipment_tracking_channel === option.value;
          return (
            <Pressable
              key={option.value}
              accessibilityRole="radio"
              accessibilityState={{ selected, disabled: preferenceMutation.isPending || pushAction }}
              onPress={() => void setShipmentTrackingChannel(option.value)}
              disabled={preferenceMutation.isPending || pushAction}
              style={({ pressed }) => [styles.trackingOption, selected && styles.trackingOptionSelected, pressed && styles.pressed]}
            >
              <View style={styles.trackingOptionCopy}>
                <Text style={styles.preferenceTitle}>{option.title}</Text>
                <Text style={styles.preferenceCopy}>{option.copy}</Text>
              </View>
              <Glyph name={selected ? 'check' : 'info'} size={20} color={selected ? themeColors.primary : themeColors.muted} />
            </Pressable>
          );
        })}
      </View>

      <Text style={styles.sectionTitle}>Kategorije</Text>
      {preferenceRows.map((row) => (
        <PreferenceRow
          key={row.key}
          title={row.title}
          copy={row.copy}
          value={Boolean(preferences[row.key])}
          onChange={(value) => setPreference(row.key, value)}
          disabled={preferenceMutation.isPending}
        />
      ))}
    </Screen>
  );
}

function PreferenceRow({ title, copy, value, onChange, disabled }: {
  title: string;
  copy: string;
  value: boolean;
  onChange: (value: boolean) => void;
  disabled?: boolean;
}) {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);

  return (
    <Card style={styles.preferenceCard}>
      <View style={{ flex: 1 }}>
        <Text style={styles.preferenceTitle}>{title}</Text>
        <Text style={styles.preferenceCopy}>{copy}</Text>
      </View>
      <Switch
        value={value}
        onValueChange={onChange}
        disabled={disabled}
        trackColor={{ true: themeColors.primarySoft }}
        thumbColor={value ? themeColors.primary : undefined}
      />
    </Card>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
  back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
  title: { ...typography.h1, color: theme.ink },
  copy: { ...typography.body, color: theme.muted },
  statusCard: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
  statusIcon: { width: 50, height: 50, borderRadius: radii.lg, backgroundColor: theme.primarySoft, alignItems: 'center', justifyContent: 'center' },
  statusCopy: { flex: 1, gap: spacing.sm },
  statusHead: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.sm },
  statusTitle: { ...typography.h3, color: theme.ink, flex: 1 },
  statusText: { ...typography.small, color: theme.muted },
  noticeTitle: { ...typography.label, color: theme.ink },
  noticeCopy: { ...typography.small, color: theme.muted, marginTop: spacing.xs },
  sectionTitle: { ...typography.h2, color: theme.ink, marginTop: spacing.md },
  preferenceCard: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
  trackingOptions: { gap: spacing.sm },
  trackingOption: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, borderWidth: 1, borderColor: theme.line, borderRadius: radii.lg, padding: spacing.md },
  trackingOptionSelected: { borderColor: theme.primary, backgroundColor: theme.primarySoft },
  trackingOptionCopy: { flex: 1 },
  pressed: { opacity: 0.72 },
  preferenceTitle: { ...typography.label, color: theme.ink },
  preferenceCopy: { ...typography.small, color: theme.muted, marginTop: spacing.xs }
});
}
