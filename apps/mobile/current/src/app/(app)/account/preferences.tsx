import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { Pill } from '@/components/ui/pill';
import { SegmentedChoice } from '@/components/ui/segmented-choice';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import {
  useAppPreferences,
  type AppThemeMode,
  type PrimaryCurrency,
} from '@/features/preferences/app-preferences';
import { useThemedStyles } from '@/theme/app-theme';

export default function AccountPreferencesScreen() {
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const { bootstrap } = useAuth();
  const {
    themeMode,
    primaryCurrency,
    setThemeMode,
    setPrimaryCurrency,
  } = useAppPreferences();

  const applyThemeMode = (value: string) => {
    void setThemeMode(value as AppThemeMode).catch(() => {
      feedback.notify({
        tone: 'danger',
        title: 'Tema nije sačuvana',
        message: 'Lokalno podešavanje teme trenutno nije moguće sačuvati.',
      });
    });
  };

  const applyPrimaryCurrency = (value: string) => {
    void setPrimaryCurrency(value as PrimaryCurrency).catch(() => {
      feedback.notify({
        tone: 'danger',
        title: 'Valuta nije sačuvana',
        message: 'Lokalno podešavanje primarne valute trenutno nije moguće sačuvati.',
      });
    });
  };

  const currencyContract = bootstrap?.app.currency;

  return (
    <Screen>
      {/* MOBILE_V1_0_ACCOUNT_HUB_PREFERENCES_BATCH81 */}
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
            accessibilityLabel="Tema aplikacije"
            value={themeMode}
            onChange={applyThemeMode}
            options={[
              { value: 'system', label: 'Sistem' },
              { value: 'light', label: 'Svetla' },
              { value: 'dark', label: 'Tamna' },
            ]}
          />
        </View>

        <View style={styles.preferenceBlock}>
          <Text style={styles.preferenceLabel}>Primarna valuta prikaza</Text>
          <Text style={styles.preferenceHint}>
            Menja samo prikaz. Porudžbine, uplate i dokumenti ostaju u canonical poslovnim valutama.
          </Text>
          <SegmentedChoice
            accessibilityLabel="Primarna valuta prikaza"
            value={primaryCurrency}
            onChange={applyPrimaryCurrency}
            options={[
              { value: 'RSD', label: 'RSD' },
              { value: 'EUR', label: 'EUR' },
            ]}
          />
        </View>
      </Card>

      <Card style={styles.rateCard}>
        <View style={styles.rateTop}>
          <Text style={styles.rateLabel}>
            NBS · {currencyContract?.rate_label ?? 'Komercijalni prodajni'}
          </Text>
          <Pill tone={currencyContract?.is_stale ? 'warning' : 'success'}>
            {currencyContract?.is_stale ? 'Poslednji kurs' : 'Aktuelno'}
          </Pill>
        </View>
        <Text style={styles.rateValue}>
          {currencyContract?.eur_rsd_rate
            ? `1 EUR = ${currencyContract.eur_rsd_rate.toFixed(4)} RSD`
            : 'Kurs trenutno nije dostupan za konverziju prikaza'}
        </Text>
        {currencyContract?.provider_date ? (
          <Text style={styles.preferenceHint}>Datum kursa: {currencyContract.provider_date}</Text>
        ) : null}
      </Card>
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
