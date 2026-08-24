import { useMemo, useState } from 'react';
import { useMutation, useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmAction } from '@/components/ui/confirm-action';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminSystemHealth,
  type AdminSystemHealthBackupItem,
  type AdminSystemHealthHistoryItem,
  type AdminSystemHealthSecurityEvent,
  type AdminSystemHealthValue,
} from '@/features/admin/system-health-admin-api';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { useAuth } from '@/features/auth/auth-provider';
import { ApiError } from '@/lib/api/client';
import { useAppTheme } from '@/theme/app-theme';

function isObjectValue(
  value: AdminSystemHealthValue,
): value is { [key: string]: AdminSystemHealthValue } {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function scalarText(value: AdminSystemHealthValue | undefined): string | null {
  if (value === undefined || value === null) return null;
  if (typeof value === 'string') return value.trim() || null;
  if (typeof value === 'number' || typeof value === 'boolean') return String(value);
  return null;
}

function displayValue(value: AdminSystemHealthValue): string {
  const scalar = scalarText(value);
  if (scalar !== null) return scalar;
  try { return JSON.stringify(value); } catch { return '-'; }
}

function firstText(
  object: { [key: string]: AdminSystemHealthValue },
  keys: string[],
): string | null {
  for (const key of keys) {
    const value = scalarText(object[key]);
    if (value !== null) return value;
  }
  return null;
}

function statusLabel(status: string): string {
  const normalized = status.trim().toLowerCase();
  if (normalized === 'healthy' || normalized === 'ok' || normalized === 'pass') return 'Zdravo';
  if (normalized === 'warning' || normalized === 'degraded') return 'Upozorenje';
  if (normalized === 'critical' || normalized === 'error' || normalized === 'failed') return 'Kritično';
  return status || 'Nepoznato';
}

function formatCheckedAt(value: string | null): string {
  if (!value) return 'Vreme nije dostupno';
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleString('sr-RS');
}

function formatBytes(value: number): string {
  if (!Number.isFinite(value) || value <= 0) return '0 MB';
  return `${new Intl.NumberFormat('sr-RS', { maximumFractionDigits: 2 }).format(value / 1048576)} MB`;
}

function errorMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return fallback;
}

// MOBILE_V1_0_SYSTEM_HEALTH_MUTATIONS_PARITY_BATCH25
export default function AdminSystemHealthScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const { can, bootstrap } = useAuth();
  const allowed = can('system.health');
  const [confirmPrune, setConfirmPrune] = useState(false);

  const query = useQuery({
    queryKey: adminQueryKeys.systemHealth(),
    queryFn: () => apiAdminSystemHealth.current(),
    enabled: allowed,
  });

  const runMutation = useMutation({
    mutationFn: () => apiAdminSystemHealth.run(),
    onSuccess: async (response) => {
      await query.refetch();
      feedback.notify({ tone: 'success', title: 'System Health provera završena', message: response.message });
    },
    onError: (error) => feedback.notify({
      tone: 'danger',
      title: 'System Health provera nije uspela',
      message: errorMessage(error, 'Pokušaj ponovo.'),
    }),
  });

  const backupMutation = useMutation({
    mutationFn: (databaseOnly: boolean) => apiAdminSystemHealth.backup(databaseOnly),
    onSuccess: async (response) => {
      await query.refetch();
      feedback.notify({
        tone: 'success',
        title: response.data.database_only ? 'Backup baze je završen' : 'Kompletan backup je završen',
        message: `${response.message} ${formatBytes(response.data.size_bytes)}`,
      });
    },
    onError: (error) => feedback.notify({
      tone: 'danger',
      title: 'Backup nije uspeo',
      message: errorMessage(error, 'Proveri System Health i pokušaj ponovo.'),
    }),
  });

  const pruneMutation = useMutation({
    mutationFn: () => apiAdminSystemHealth.prune(),
    onSuccess: async (response) => {
      setConfirmPrune(false);
      await query.refetch();
      feedback.notify({ tone: 'success', title: 'Retention je primenjen', message: response.message });
    },
    onError: (error) => {
      setConfirmPrune(false);
      feedback.notify({
        tone: 'danger',
        title: 'Retention nije primenjen',
        message: errorMessage(error, 'Pokušaj ponovo.'),
      });
    },
  });

  if (!allowed) return <UnavailableState title="Zdravlje sistema nije dostupno" />;
  if (query.isLoading) return <LoadingState label="Učitavanje zdravlja sistema…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const response = query.data;
  const report = response.data;
  const metrics = Object.entries(report.metrics);

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Admin</Text>
      </Pressable>

      <PageHeader title="Zdravlje sistema" eyebrow="Admin · Sistem" name={bootstrap?.user.name} />

      <Card style={styles.heroCard}>
        <Text style={styles.heroEyebrow}>Trenutni status</Text>
        <Text style={styles.heroStatus}>{statusLabel(report.status)}</Text>
        <Text style={styles.muted}>Provereno: {formatCheckedAt(report.checked_at)}</Text>
        <Text style={styles.muted}>Provera: {report.checks.length}</Text>
        <View style={styles.actions}>
          {response.capabilities.refresh ? (
            <Button variant="secondary" onPress={() => void query.refetch()}>
              {query.isFetching ? 'Osvežavanje…' : 'Osveži stanje'}
            </Button>
          ) : null}
          {response.capabilities.snapshot ? (
            <Button loading={runMutation.isPending} onPress={() => runMutation.mutate()}>
              Pokreni proveru i sačuvaj snapshot
            </Button>
          ) : null}
        </View>
      </Card>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Provere sistema</Text>
        {report.checks.length === 0 ? (
          <Card style={styles.card}><Text style={styles.muted}>Nema dostupnih provera.</Text></Card>
        ) : report.checks.map((check, index) => {
          const object = isObjectValue(check) ? check : null;
          const title = object ? firstText(object, ['label', 'name', 'check', 'title', 'key']) : null;
          const status = object ? firstText(object, ['status', 'state', 'result']) : null;
          const message = object ? firstText(object, ['message', 'detail', 'description', 'reason', 'remediation']) : null;
          return (
            <Card key={`health-check-${index}`} style={styles.card}>
              <View style={styles.rowBetween}>
                <Text style={styles.cardTitle}>{title ?? `Provera ${index + 1}`}</Text>
                {status ? <Text style={styles.statusText}>{statusLabel(status)}</Text> : null}
              </View>
              {message ? <Text style={styles.body}>{message}</Text> : null}
              {!object ? <Text style={styles.body}>{displayValue(check)}</Text> : null}
            </Card>
          );
        })}
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Metrike</Text>
        <Card style={styles.card}>
          {metrics.length === 0 ? <Text style={styles.muted}>Nema dostupnih metrika.</Text> : metrics.map(([key, value]) => (
            <View key={key} style={styles.metricRow}>
              <Text style={styles.metricLabel}>{key}</Text>
              <Text style={styles.metricValue}>{displayValue(value)}</Text>
            </View>
          ))}
        </Card>
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Privatni backup</Text>
        <Card style={styles.card}>
          <Text style={styles.body}>MySQL dump i poslovni upload fajlovi ostaju izvan public direktorijuma.</Text>
          {response.capabilities.backup ? (
            <View style={styles.actions}>
              <Button loading={backupMutation.isPending} onPress={() => backupMutation.mutate(false)}>
                Kreiraj kompletan backup
              </Button>
              <Button variant="secondary" loading={backupMutation.isPending} onPress={() => backupMutation.mutate(true)}>
                Samo baza
              </Button>
            </View>
          ) : null}
          {response.capabilities.prune ? (
            <Button variant="ghost" loading={pruneMutation.isPending} onPress={() => setConfirmPrune(true)}>
              Primeni retention
            </Button>
          ) : null}
        </Card>
        {response.backups.length === 0 ? (
          <Card style={styles.card}><Text style={styles.muted}>Još nema evidentiranih backupa.</Text></Card>
        ) : response.backups.map((backup) => <BackupCard key={backup.id} backup={backup} styles={styles} />)}
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Poslednji security događaji</Text>
        {response.security_events.length === 0 ? (
          <Card style={styles.card}><Text style={styles.muted}>Nema security događaja.</Text></Card>
        ) : response.security_events.map((event) => <SecurityEventCard key={event.id} event={event} styles={styles} />)}
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Istorija System Health provera</Text>
        {response.history.length === 0 ? (
          <Card style={styles.card}><Text style={styles.muted}>Pokreni prvu proveru da sačuvaš snapshot.</Text></Card>
        ) : response.history.map((item) => <HistoryCard key={item.id} item={item} styles={styles} />)}
      </View>

      <ConfirmAction
        visible={confirmPrune}
        title="Primeni backup retention?"
        message="Biće uklonjeni samo stari daily/weekly backupi iznad podešenih retention limita. Ručni backupi nisu meta prune operacije."
        confirmLabel="Primeni retention"
        destructive
        busy={pruneMutation.isPending}
        onCancel={() => setConfirmPrune(false)}
        onConfirm={() => pruneMutation.mutate()}
      />
    </Screen>
  );
}

function HistoryCard({ item, styles }: { item: AdminSystemHealthHistoryItem; styles: ReturnType<typeof createStyles> }) {
  return (
    <Card style={styles.card}>
      <View style={styles.rowBetween}>
        <Text style={styles.cardTitle}>Snapshot #{item.id}</Text>
        <Text style={styles.statusText}>{statusLabel(item.status)}</Text>
      </View>
      <Text style={styles.muted}>{formatCheckedAt(item.checked_at)}</Text>
      <Text style={styles.muted}>{item.checker_name ?? 'CLI/Scheduler'} · Provera: {item.checks.length}</Text>
    </Card>
  );
}

function BackupCard({ backup, styles }: { backup: AdminSystemHealthBackupItem; styles: ReturnType<typeof createStyles> }) {
  return (
    <Card style={styles.card}>
      <View style={styles.rowBetween}>
        <Text style={styles.cardTitle}>{backup.backup_key}</Text>
        <Text style={styles.statusText}>{backup.status}</Text>
      </View>
      <Text style={styles.muted}>{backup.backup_type} · {backup.creator_name ?? 'Scheduler'}</Text>
      <Text style={styles.muted}>{formatBytes(backup.size_bytes)} · {formatCheckedAt(backup.started_at)}</Text>
    </Card>
  );
}

function SecurityEventCard({ event, styles }: { event: AdminSystemHealthSecurityEvent; styles: ReturnType<typeof createStyles> }) {
  const route = event.route_name ?? event.method ?? '-';
  return (
    <Card style={styles.card}>
      <View style={styles.rowBetween}>
        <Text style={styles.cardTitle}>{event.event_type}</Text>
        <Text style={styles.statusText}>{event.severity}</Text>
      </View>
      <Text style={styles.muted}>{event.actor_name ?? 'Gost/Sistem'} · {route}</Text>
      <Text style={styles.muted}>{formatCheckedAt(event.created_at)}</Text>
    </Card>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 140, gap: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    section: { gap: spacing.sm },
    sectionTitle: { ...typography.h3, color: theme.ink },
    heroCard: { gap: spacing.sm },
    heroEyebrow: { ...typography.small, color: theme.muted },
    heroStatus: { ...typography.h2, color: theme.ink },
    card: { gap: spacing.sm },
    cardTitle: { ...typography.label, color: theme.ink, flex: 1 },
    body: { ...typography.body, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    statusText: { ...typography.label, color: theme.primary },
    actions: { gap: spacing.sm },
    rowBetween: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    metricRow: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    metricLabel: { ...typography.small, color: theme.muted, flex: 1 },
    metricValue: { ...typography.body, color: theme.ink, flex: 1, textAlign: 'right' },
  });
}
