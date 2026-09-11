import { router } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { Pill } from '@/components/ui/pill';
import { SegmentedChoice } from '@/components/ui/segmented-choice';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { apiAdminExchangeRate } from '@/features/admin/exchange-rate-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import {
  useAppPreferences,
  type AppThemeMode,
  type PriceDisplayMode,
} from '@/features/preferences/app-preferences';
import { useThemedStyles } from '@/theme/app-theme';

export default function AccountPreferencesScreen() {
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const { bootstrap, can, refreshBootstrap } = useAuth();
  const {
    themeMode,
    priceDisplayMode,
    setThemeMode,
    setPriceDisplayMode,
  } = useAppPreferences();
  const [refreshingRate, setRefreshingRate] = useState(false);

  const applyThemeMode = (value: string) => {
    void setThemeMode(value as AppThemeMode).catch(() => {
      feedback.notify({
        tone: 'danger',
        title: 'Tema nije sačuvana',
        message: 'Lokalno podešavanje teme trenutno nije moguće sačuvati.',
      });
    });
  };

  const applyPriceDisplayMode = (value: string) => {
    void setPriceDisplayMode(value as PriceDisplayMode).catch(() => {
      feedback.notify({
        tone: 'danger',
        title: 'Prikaz cene nije sačuvan',
        message: 'Lokalno podešavanje valute prikaza trenutno nije moguće sačuvati.',
      });
    });
  };

  const currencyContract = bootstrap?.app.currency;
  const canRefreshRate = can('system.manage_settings');

  const refreshRate = async () => {
    if (!canRefreshRate || refreshingRate) return;
    setRefreshingRate(true);
    try {
      const response = await apiAdminExchangeRate.refresh();
      await refreshBootstrap();
      const rate = response.data.configuration.rate;
      feedback.notify({
        tone: 'success',
        title: 'EUR/RSD kurs je ažuriran',
        message: rate ? `Aktuelni kurs: ${rate.toFixed(2)} RSD` : 'Kurs je sinhronizovan.',
      });
    } catch (error) {
      feedback.notify({
        tone: 'danger',
        title: 'Kurs nije ažuriran',
        message: error instanceof Error && error.message ? error.message : 'Automatska sinhronizacija trenutno nije dostupna.',
      });
    } finally {
      setRefreshingRate(false);
    }
  };

  return (
    <Screen>
      {/* MOBILE_V1_0_ACCOUNT_HUB_PREFERENCES_BATCH81 */}
      {/* MOBILE_LARAVEL_APK_PARITY_BATCH151_V4 */}
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Nalog</Text>
      </Pressable>
      <PageHeader title="Izgled i prikaz" eyebrow="Nalog" name={bootstrap?.user.name} />
      <Text style={styles.copy}>Podešavanja su vezana za tvoj nalog samo na ovom uređaju.</Text>

      <Card style={styles.card}>
        <View style={styles.preferenceBlock}>
          <Text style={styles.preferenceLabel}>Tema aplikacije</Text>
          <Text style={styles.preferenceHint}>
            Sistem prati Android temu; Svetla i Tamna ostaju ručno izabrane.
          </Text>
          <SegmentedChoice
            accessibilityLabel="Sistemska tema aplikacije"
            value={themeMode}
            onChange={applyThemeMode}
            options={[{ value: 'system', label: 'Sistem' }]}
          />
          <SegmentedChoice
            accessibilityLabel="Ručna tema aplikacije"
            value={themeMode}
            onChange={applyThemeMode}
            options={[
              { value: 'light', label: 'Svetla' },
              { value: 'dark', label: 'Tamna' },
            ]}
          />
        </View>

        <View style={styles.preferenceBlock}>
          <Text style={styles.preferenceLabel}>Valuta prikaza</Text>
          <Text style={styles.preferenceHint}>
            Izvorno zadržava valutu sačuvanu na artiklu. RSD i EUR samo preračunavaju prikaz; poslovni zapisi i dokumenti se ne menjaju.
          </Text>
          <SegmentedChoice
            accessibilityLabel="Izvorna valuta prikaza"
            value={priceDisplayMode}
            onChange={applyPriceDisplayMode}
            options={[{ value: 'source', label: 'Izvorno' }]}
          />
          <SegmentedChoice
            accessibilityLabel="Preračunata valuta prikaza"
            value={priceDisplayMode}
            onChange={applyPriceDisplayMode}
            options={[
              { value: 'RSD', label: 'RSD' },
              { value: 'EUR', label: 'EUR' },
            ]}
          />
        </View>
      </Card>

      <Pressable
        accessibilityRole={canRefreshRate ? 'button' : undefined}
        accessibilityLabel={canRefreshRate ? 'Ažuriraj EUR RSD kurs' : undefined}
        accessibilityState={{ disabled: !canRefreshRate, busy: refreshingRate }}
        disabled={!canRefreshRate || refreshingRate}
        onPress={() => void refreshRate()}
        style={({ pressed }) => [styles.ratePressable, pressed && canRefreshRate ? styles.ratePressed : null]}
      >
        <Card style={styles.rateCard}>
          <View style={styles.rateTop}>
            <Text style={styles.rateLabel}>
              NBS · {currencyContract?.rate_label ?? 'Komercijalni prodajni'}
            </Text>
            <Pill tone={currencyContract?.is_stale ? 'warning' : 'success'}>
              {refreshingRate ? 'Ažuriranje…' : currencyContract?.is_stale ? 'Poslednji kurs' : 'Aktuelno'}
            </Pill>
          </View>
          <Text style={styles.rateValue}>
            {currencyContract?.eur_rsd_rate
              ? `1 EUR = ${currencyContract.eur_rsd_rate.toFixed(2)} RSD`
              : 'Kurs trenutno nije dostupan za konverziju prikaza'}
          </Text>
          {currencyContract?.provider_date ? (
            <Text style={styles.preferenceHint}>Datum kursa: {currencyContract.provider_date}</Text>
          ) : null}
          <Text style={styles.preferenceHint}>
            {canRefreshRate
              ? 'Dodirni karticu kursa za automatsko ažuriranje.'
              : 'Automatsko ažuriranje kursa je dostupno korisnicima sa sistemskom dozvolom.'}
          </Text>
        </Card>
      </Pressable>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    copy: { ...typography.body, color: theme.muted },
    card: { gap: spacing.xl },
    preferenceBlock: { gap: spacing.sm },
    preferenceLabel: { ...typography.label, color: theme.ink },
    preferenceHint: { ...typography.small, color: theme.muted },
    ratePressable: { borderRadius: 18 },
    ratePressed: { opacity: 0.9, transform: [{ scale: 0.99 }] },
    rateCard: { gap: spacing.sm },
    rateTop: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.sm,
    },
    rateLabel: { ...typography.label, color: theme.ink, flex: 1 },
    rateValue: { ...typography.h3, color: theme.primaryDark },
  });
}