import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useEffect, useMemo, useState } from 'react';
import { StyleSheet, Switch, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiAdminSystemSettings } from '@/features/admin/system-settings-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

const BOOL_KEYS = [
  ['automation_enabled', 'Automatizacija uključena'],
  ['automation_low_stock_enabled', 'Upozorenja za nizak lager'],
  ['automation_overdue_payment_enabled', 'Upozorenja za dospele obaveze'],
  ['automation_deadline_alerts_enabled', 'Upozorenja za rokove'],
  ['automation_daily_digest_enabled', 'Dnevni digest'],
  ['automation_warranty_alerts_enabled', 'Garancijska upozorenja'],
] as const;

// MOBILE_V1_0_SYSTEM_SETTINGS_PARITY_BATCH37_SET02
export default function AdminAutomationSettingsScreen() {
  const { can, bootstrap } = useAuth();
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const allowed = can('system.manage_settings');
  const query = useQuery({ queryKey: adminQueryKeys.systemSettingsAutomation(), queryFn: apiAdminSystemSettings.automation.state, enabled: allowed });
  const [values, setValues] = useState<Record<string, string>>({});
  const [digest, setDigest] = useState(false);

  useEffect(() => { if (query.data?.data.settings) setValues(query.data.data.settings); }, [query.data?.data.settings]);

  const mutation = useMutation({
    mutationFn: async (action: 'save' | 'run' | number) => {
      if (action === 'save') return apiAdminSystemSettings.automation.update({
        ...values,
        ...Object.fromEntries(BOOL_KEYS.map(([key]) => [key, values[key] === '1'])),
      });
      if (action === 'run') return apiAdminSystemSettings.automation.run(digest);
      return apiAdminSystemSettings.automation.resolveAlert(action);
    },
    onSuccess: (response) => { client.setQueryData(adminQueryKeys.systemSettingsAutomation(), response); feedback.notify({ tone: 'success', title: 'Automatizacija je ažurirana' }); },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Akcija nije uspela', message: error instanceof Error ? error.message : 'Greška.' }),
  });

  if (!allowed) return <UnavailableState title="Automatizacija nije dostupna" />;
  if (query.isLoading) return <LoadingState label="Učitavanje automatizacije…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  const data = query.data.data;

  const numberField = (key: string, label: string) => <TextField label={label} value={values[key] ?? ''} keyboardType="number-pad" onChangeText={(value) => setValues((current) => ({ ...current, [key]: value }))} />;

  return (
    <Screen contentStyle={styles.content}>
      <PageHeader title="Automatizacija" eyebrow="Sistem · SET-02" name={bootstrap?.user.name} />
      <Card style={styles.card}>
        <Text style={styles.title}>Stanje</Text>
        <Text style={styles.muted}>{data.ready ? 'Automation schema je spremna.' : `Nedostaje: ${data.missing.join(', ')}`}</Text>
        <Text style={styles.meta}>Otvoreno {data.stats.open} · Kritično {data.stats.danger} · Upozorenja {data.stats.warning} · Failed run 7d {data.stats.failed_runs}</Text>
      </Card>
      <Card style={styles.card}>
        <Text style={styles.title}>Pravila</Text>
        {BOOL_KEYS.map(([key, label]) => <View key={key} style={styles.row}><Text style={styles.body}>{label}</Text><Switch value={values[key] === '1'} onValueChange={(value) => setValues((current) => ({ ...current, [key]: value ? '1' : '0' }))} /></View>)}
        {numberField('automation_unaccepted_order_hours', 'Nepreuzeta porudžbina posle (h)')}
        {numberField('automation_alert_reminder_hours', 'Ponovi upozorenje posle (h)')}
        {numberField('warranty_expiry_notice_days', 'Najava isteka garancije (dana)')}
        {numberField('warranty_maintenance_notice_days', 'Najava održavanja (dana)')}
        <Button loading={mutation.isPending} onPress={() => mutation.mutate('save')}>Sačuvaj podešavanja</Button>
      </Card>
      {data.capabilities.run_automation ? <Card style={styles.card}><Text style={styles.title}>Ručno pokretanje</Text><View style={styles.row}><Text style={styles.body}>Pošalji digest</Text><Switch value={digest} onValueChange={setDigest} /></View><Button variant="secondary" loading={mutation.isPending} onPress={() => mutation.mutate('run')}>Pokreni automatizaciju</Button></Card> : null}
      <Card style={styles.card}>
        <Text style={styles.title}>Otvorena upozorenja</Text>
        {data.alerts.length === 0 ? <Text style={styles.muted}>Nema otvorenih upozorenja.</Text> : data.alerts.map((alert) => <View key={alert.id} style={styles.item}><Text style={styles.body}>{alert.title}</Text><Text style={styles.meta}>{alert.severity.toUpperCase()} · {alert.message}</Text>{data.capabilities.run_automation ? <Button variant="secondary" onPress={() => mutation.mutate(alert.id)}>Razreši</Button> : null}</View>)}
      </Card>
      <Card style={styles.card}><Text style={styles.title}>Poslednja izvršavanja</Text>{data.runs.slice(0, 10).map((run) => <Text key={run.id} style={styles.meta}>{run.task} · {run.status} · +{run.created_alerts}/~{run.updated_alerts}/✓{run.resolved_alerts}</Text>)}</Card>
    </Screen>
  );
}

function createStyles(theme: AppColors) { return StyleSheet.create({ content: { gap: spacing.lg, paddingBottom: 140 }, card: { gap: spacing.md }, title: { ...typography.h2, color: theme.ink }, body: { ...typography.body, color: theme.ink, flex: 1 }, muted: { ...typography.body, color: theme.muted }, meta: { ...typography.small, color: theme.muted }, row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md }, item: { gap: spacing.sm, paddingVertical: spacing.sm } }); }
