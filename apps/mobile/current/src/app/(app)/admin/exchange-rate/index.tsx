import { useEffect, useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Pill, type PillTone } from '@/components/ui/pill';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminExchangeRate,
  type AdminExchangeRateData,
  type AdminExchangeRateHistoryItem,
  type AdminExchangeRateResponse,
} from '@/features/admin/exchange-rate-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

type Command =
  | { key: 'manual'; run: () => Promise<AdminExchangeRateResponse> }
  | { key: 'automatic'; run: () => Promise<AdminExchangeRateResponse> }
  | { key: 'refresh'; run: () => Promise<AdminExchangeRateResponse> };

function rateText(value: number | null): string {
  return value === null
    ? 'Nije podešeno'
    : `${value.toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} RSD`;
}

function dateText(value: string | null): string {
  if (!value) return 'Nije evidentirano';
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString('sr-RS');
}

function historyTone(status: AdminExchangeRateHistoryItem['status']): PillTone {
  return status === 'success' ? 'success' : 'danger';
}

function historyRate(item: AdminExchangeRateHistoryItem): string {
  if (item.new_rate === null) return 'Bez novog kursa';
  const before = item.old_rate === null ? '—' : item.old_rate.toFixed(2);
  return `${before} → ${item.new_rate.toFixed(2)} RSD`;
}

// MOBILE_V0_8_EUR_RSD_EXCHANGE_RATE_BATCH13
export default function AdminExchangeRateScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const allowed = can('system.manage_settings');
  const [manualRate, setManualRate] = useState('');
  const [automaticValue, setAutomaticValue] = useState('0');
  const [staleHours, setStaleHours] = useState('48');

  const query = useQuery({
    queryKey: adminQueryKeys.exchangeRate(),
    queryFn: apiAdminExchangeRate.state,
    enabled: allowed,
    staleTime: 30_000,
  });

  const mutation = useMutation({
    mutationFn: (command: Command) => command.run(),
    onSuccess: (response, command) => {
      client.setQueryData(adminQueryKeys.exchangeRate(), response);
      feedback.notify({
        tone: 'success',
        title: command.key === 'refresh' ? 'Kurs je sinhronizovan' : 'Podešavanje je sačuvano',
        message: response.message ?? 'EUR/RSD podešavanje je osveženo.',
      });
    },
    onError: async (error) => {
      await client.invalidateQueries({ queryKey: adminQueryKeys.exchangeRate() });
      feedback.notify({
        tone: 'danger',
        title: 'Kurs nije ažuriran',
        message: error instanceof Error ? error.message : 'Server trenutno ne može da izvrši akciju.',
      });
    },
  });

  const data: AdminExchangeRateData | null = query.data?.data ?? null;
  const configuration = data?.configuration ?? null;

  useEffect(() => {
    if (!configuration) return;
    setAutomaticValue(configuration.mode === 'auto' ? '1' : '0');
    setStaleHours(String(configuration.stale_after_hours));
    if (configuration.rate !== null) setManualRate(String(configuration.rate));
  }, [configuration?.mode, configuration?.rate, configuration?.stale_after_hours]);

  if (!allowed) return <UnavailableState title="Podešavanje EUR/RSD kursa nije dostupno" />;
  if (query.isLoading) return <LoadingState label="Učitavanje EUR/RSD kursa…" />;
  if (query.isError || !data || !configuration) {
    return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  }

  const submitManual = () => {
    const rate = Number(manualRate.replace(',', '.'));
    if (!Number.isFinite(rate) || rate < 50 || rate > 250) {
      feedback.notify({
        tone: 'danger',
        title: 'Proveri kurs',
        message: 'Kurs mora biti između 50 i 250 RSD za 1 EUR.',
      });
      return;
    }
    mutation.mutate({ key: 'manual', run: () => apiAdminExchangeRate.manual(rate) });
  };

  const submitAutomatic = () => {
    const hours = Number(staleHours);
    if (!Number.isInteger(hours) || hours < 1 || hours > 720) {
      feedback.notify({
        tone: 'danger',
        title: 'Proveri period',
        message: 'Period zastarelosti mora biti između 1 i 720 sati.',
      });
      return;
    }
    mutation.mutate({
      key: 'automatic',
      run: () => apiAdminExchangeRate.automatic(automaticValue === '1', hours),
    });
  };

  return (
    <Screen contentStyle={styles.content} keyboardShouldPersistTaps="handled">
      <PageHeader
        title="EUR/RSD kurs"
        eyebrow="Admin · Sistemska podešavanja"
        name={bootstrap?.user.name}
      />

      {/* MOBILE_V1_0_COMMERCIAL_SELLING_RATE_AUTHORITY_BATCH34B */}
      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Komercijalni prodajni kurs</Text>
        <Text style={styles.copy}>
          GLAVNI KURS APLIKACIJE. Primarni izvor je javna NBS kursna lista za devize (EUR / 978 / prodajni kurs), bez kredencijala. Frankfurter API v2 je sekundarni referentni fallback. Ako oba izvora padnu, poslednji uspesno sacuvan kurs ostaje aktivan.
        </Text>
      </Card>

      <Card style={styles.hero}>
        <View style={styles.rowBetween}>
          <View style={styles.flex}>
            <Text style={styles.kicker}>Trenutni kurs</Text>
            <Text style={styles.rate}>{rateText(configuration.rate)}</Text>
          </View>
          <Pill tone={configuration.is_stale ? 'warning' : 'success'}>
            {configuration.is_stale ? 'ZASTAREO' : 'AKTUELAN'}
          </Pill>
        </View>
        <Text style={styles.meta}>Režim: {configuration.mode === 'auto' ? 'Automatski' : 'Ručni'}</Text>
        <Text style={styles.meta}>Izvor: {configuration.source}</Text>
        <Text style={styles.meta}>Datum izvora: {configuration.provider_date ?? 'Nije evidentirano'}</Text>
        <Text style={styles.meta}>Ažurirano: {dateText(configuration.updated_at)}</Text>
        <Text style={styles.meta}>Poslednji pokušaj: {dateText(configuration.last_attempt_at)}</Text>
        {configuration.last_error ? <Text style={styles.errorText}>Poslednja greška: {configuration.last_error}</Text> : null}
      </Card>

      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Ručni kurs</Text>
        <Text style={styles.copy}>
          Ručni unos odmah postaje centralni EUR/RSD kurs i prebacuje sistem u ručni režim.
        </Text>
        <TextField
          label="1 EUR = RSD"
          value={manualRate}
          onChangeText={setManualRate}
          keyboardType="decimal-pad"
          placeholder="117.0000"
        />
        <Button loading={mutation.isPending && mutation.variables?.key === 'manual'} onPress={submitManual}>
          Sačuvaj ručni kurs
        </Button>
      </Card>

      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Automatsko ažuriranje</Text>
        <Text style={styles.copy}>
          Automatski rezim prvo cita javnu NBS kursnu listu, zatim Frankfurter API v2. Ako oba izvora nisu dostupna, postojeci poslednji uspesno sacuvan kurs se ne prepisuje. Ukljucivanje odmah pokusava sinhronizaciju.
        </Text>
        <SelectSheet
          label="Automatsko ažuriranje"
          value={automaticValue}
          options={[
            { value: '1', label: 'Uključeno' },
            { value: '0', label: 'Isključeno · ručni režim' },
          ]}
          onChange={setAutomaticValue}
        />
        <TextField
          label="Kurs zastareo posle (sati)"
          value={staleHours}
          onChangeText={setStaleHours}
          keyboardType="number-pad"
          placeholder="48"
        />
        <Button
          variant="secondary"
          loading={mutation.isPending && mutation.variables?.key === 'automatic'}
          onPress={submitAutomatic}
        >
          Sačuvaj režim
        </Button>
      </Card>

      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Refresh / Sync Now</Text>
        <Text style={styles.copy}>
          Ručna sinhronizacija preuzima novi kurs odmah. Ako je sistem u ručnom režimu, ovaj jednokratni refresh ga ne uključuje automatski.
        </Text>
        <Button
          loading={mutation.isPending && mutation.variables?.key === 'refresh'}
          onPress={() => mutation.mutate({ key: 'refresh', run: apiAdminExchangeRate.refresh })}
        >
          Sinhronizuj sada
        </Button>
      </Card>

      <View style={styles.sectionHead}>
        <Text style={styles.sectionTitle}>Istorija kursa</Text>
        <Text style={styles.meta}>{data.history.length} poslednjih zapisa</Text>
      </View>

      <View style={styles.historyList}>
        {data.history.length === 0 ? (
          <Card style={styles.card}>
            <Text style={styles.copy}>Istorija još nema zapisa.</Text>
          </Card>
        ) : data.history.map((item) => (
          <Card key={item.id} style={styles.historyCard}>
            <View style={styles.rowBetween}>
              <Text style={styles.historyRate}>{historyRate(item)}</Text>
              <Pill tone={historyTone(item.status)}>
                {item.status === 'success' ? 'USPEH' : 'GREŠKA'}
              </Pill>
            </View>
            <Text style={styles.meta}>{item.mode === 'auto' ? 'Automatski' : 'Ručni'} · {item.source}</Text>
            <Text style={styles.meta}>Datum: {dateText(item.created_at)} · Izvorni datum: {item.provider_date ?? '—'}</Text>
            <Text style={styles.meta}>Pokrenuo: {item.updater?.name ?? item.triggered_by}</Text>
            {item.message ? <Text style={item.status === 'failed' ? styles.errorText : styles.copy}>{item.message}</Text> : null}
          </Card>
        ))}
      </View>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 140, gap: spacing.lg },
    hero: { gap: spacing.sm },
    card: { gap: spacing.md },
    rowBetween: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    flex: { flex: 1 },
    kicker: { ...typography.small, color: theme.muted, fontWeight: '700' },
    rate: { ...typography.h1, color: theme.ink, marginTop: spacing.xs },
    sectionTitle: { ...typography.h2, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
    meta: { ...typography.small, color: theme.muted },
    errorText: { ...typography.small, color: theme.danger },
    sectionHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
    historyList: { gap: spacing.md },
    historyCard: { gap: spacing.sm },
    historyRate: { ...typography.h3, color: theme.ink, flex: 1 },
  });
}
