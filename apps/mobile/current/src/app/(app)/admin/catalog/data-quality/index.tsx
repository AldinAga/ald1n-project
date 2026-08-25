import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmAction } from '@/components/ui/confirm-action';
import { Pill, type PillTone } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiAdminDataQuality, type DataQualitySeverity, type DataQualityStatus } from '@/features/admin/data-quality-admin-api';
import { openAdminDataQualityExport } from '@/features/admin/data-quality-admin-export';
import { useAuth } from '@/features/auth/auth-provider';
import { ApiError } from '@/lib/api/client';
import { useAppTheme } from '@/theme/app-theme';

function errorMessage(error: unknown): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  return error instanceof Error ? error.message : 'Operacija nije uspela.';
}
function statusTone(status: DataQualityStatus): PillTone {
  if (status === 'healthy') return 'success';
  if (status === 'critical') return 'danger';
  return 'warning';
}
function severityTone(severity: DataQualitySeverity): PillTone {
  if (severity === 'critical') return 'danger';
  if (severity === 'warning') return 'warning';
  return 'info';
}
function runner(snapshot: { runner?: { first_name?: string | null; last_name?: string | null; username?: string | null } | null }): string {
  const name = [snapshot.runner?.first_name, snapshot.runner?.last_name].filter(Boolean).join(' ').trim();
  return name || snapshot.runner?.username || 'CLI/Sistem';
}

// MOBILE_V1_0_CATALOG_ADVANCED_PARITY_BATCH35
export default function AdminCatalogDataQualityScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { can, bootstrap } = useAuth();
  const allowed = can('catalog.audit');
  const [confirmRepair, setConfirmRepair] = useState(false);

  const query = useQuery({
    queryKey: adminQueryKeys.dataQuality(),
    queryFn: apiAdminDataQuality.state,
    enabled: allowed,
  });
  const repairMutation = useMutation({
    mutationFn: apiAdminDataQuality.repair,
    onSuccess: async (response) => {
      setConfirmRepair(false);
      await client.invalidateQueries({ queryKey: adminQueryKeys.dataQuality() });
      feedback.notify({ tone: 'success', title: 'Bezbedna popravka je završena', message: `Score ${response.data.before.score} → ${response.data.after.score}` });
    },
    onError: (error) => {
      setConfirmRepair(false);
      feedback.notify({ tone: 'danger', title: 'Popravka nije uspela', message: errorMessage(error) });
    },
  });
  const exportMutation = useMutation({
    mutationFn: openAdminDataQualityExport,
    onError: (error) => feedback.notify({ tone: 'danger', title: 'JSON izvoz nije uspeo', message: errorMessage(error) }),
  });

  if (!allowed) return <UnavailableState title="Data Quality Center nije dostupan" />;
  if (query.isLoading) return <LoadingState label="Pokretanje Data Quality provere…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const state = query.data;
  const report = state.report;
  const visibleIssues = report.issues.filter((issue) => issue.count > 0);

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}><Text style={styles.back}>‹ Katalog i lager</Text></Pressable>
      <PageHeader title="Kvalitet podataka" eyebrow="Admin · Data Quality Center" name={bootstrap?.user.name} />

      <Card style={styles.card}>
        <View style={styles.rowBetween}>
          <View style={styles.flexOne}>
            <Text style={styles.score}>{report.score}/100</Text>
            <Text style={styles.muted}>Provera {new Date(report.generated_at).toLocaleString('sr-RS')} · {report.duration_ms} ms</Text>
          </View>
          <Pill tone={statusTone(report.status)}>{report.status.toUpperCase()}</Pill>
        </View>
        <Text style={styles.body}>Kritično: {report.summary.critical} · Upozorenja: {report.summary.warning} · Za dopunu: {report.summary.info}</Text>
        <Text style={styles.muted}>Aktivni artikli: {report.metrics.products_active ?? 0} · Fotografije: {report.metrics.images_total ?? 0} · Aktivni korisnici: {report.metrics.users_active ?? 0}</Text>
        <View style={styles.actions}>
          <Button variant="secondary" onPress={() => void query.refetch()}>{query.isFetching ? 'Provera…' : 'Ponovi proveru'}</Button>
          {state.capabilities.export ? <Button variant="ghost" loading={exportMutation.isPending} onPress={() => exportMutation.mutate()}>Preuzmi JSON</Button> : null}
        </View>
      </Card>

      <Text style={styles.sectionTitle}>Pronađeni problemi</Text>
      {visibleIssues.length === 0 ? (
        <Card style={styles.card}><Text style={styles.body}>Nema aktivnih Data Quality problema.</Text></Card>
      ) : visibleIssues.map((issue) => (
        <Card key={issue.key} style={styles.card}>
          <View style={styles.rowBetween}>
            <View style={styles.flexOne}>
              <Text style={styles.issueTitle}>{issue.label}</Text>
              <Text style={styles.muted}>{issue.description}</Text>
            </View>
            <Pill tone={severityTone(issue.severity)}>{issue.count}</Pill>
          </View>
          {issue.repairable ? <Text style={styles.repairable}>Bezbedna automatska popravka dostupna</Text> : null}
          {issue.samples.slice(0, 3).map((sample, index) => <Text key={`${issue.key}-${index}`} style={styles.sample}>{JSON.stringify(sample)}</Text>)}
        </Card>
      ))}

      {state.capabilities.repair ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Bezbedna automatska popravka</Text>
          <Text style={styles.muted}>Usklađuje kategorije tipova, čisti zastarele specifikacione veze, preračunava diskove i kompletnost i normalizuje glavne slike. Ne briše artikle, slike ni poslovnu istoriju.</Text>
          <Button variant="secondary" onPress={() => setConfirmRepair(true)}>Pokreni bezbednu popravku</Button>
        </Card>
      ) : null}

      <Text style={styles.sectionTitle}>Istorija provera</Text>
      {state.snapshots.length === 0 ? (
        <Card style={styles.card}><Text style={styles.muted}>Nema sačuvanih istorijskih snapshotova.</Text></Card>
      ) : state.snapshots.slice(0, 20).map((snapshot) => (
        <Card key={snapshot.id} style={styles.compactCard}>
          <Text style={styles.body}>{snapshot.status} · {snapshot.score}/100</Text>
          <Text style={styles.muted}>{snapshot.source} · {runner(snapshot)} · {snapshot.created_at ? new Date(snapshot.created_at).toLocaleString('sr-RS') : '-'}</Text>
        </Card>
      ))}

      <ConfirmAction
        visible={confirmRepair}
        title="Pokrenuti bezbednu popravku?"
        message="Sistem će izmeniti samo automatski popravljive veze i izvedene vrednosti."
        confirmLabel="Pokreni popravku"
        destructive={false}
        busy={repairMutation.isPending}
        onCancel={() => setConfirmRepair(false)}
        onConfirm={() => repairMutation.mutate()}
      />
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: 140 },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    card: { gap: spacing.md },
    compactCard: { gap: spacing.xs },
    rowBetween: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    flexOne: { flex: 1, minWidth: 0 },
    actions: { gap: spacing.sm },
    score: { ...typography.h1, color: theme.ink },
    sectionTitle: { ...typography.h3, color: theme.ink },
    issueTitle: { ...typography.h3, color: theme.ink },
    body: { ...typography.body, color: theme.ink },
    muted: { ...typography.body, color: theme.muted },
    repairable: { ...typography.small, color: theme.warning },
    sample: { ...typography.small, color: theme.muted },
  });
}
