import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminSystemHealth,
  type AdminSystemHealthHistoryItem,
  type AdminSystemHealthValue,
} from '@/features/admin/system-health-admin-api';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { useAuth } from '@/features/auth/auth-provider';
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

  try {
    return JSON.stringify(value);
  } catch {
    return '-';
  }
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
  if (normalized === 'healthy' || normalized === 'ok' || normalized === 'pass') {
    return 'Zdravo';
  }
  if (normalized === 'warning' || normalized === 'degraded') {
    return 'Upozorenje';
  }
  if (normalized === 'critical' || normalized === 'error' || normalized === 'failed') {
    return 'Kriticno';
  }
  return status || 'Nepoznato';
}

function formatCheckedAt(value: string | null): string {
  if (!value) return 'Vreme nije dostupno';
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleString('sr-RS');
}

export default function AdminSystemHealthScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const allowed = can('system.health');

  const query = useQuery({
    queryKey: adminQueryKeys.systemHealth(),
    queryFn: () => apiAdminSystemHealth.current(),
    enabled: allowed,
  });

  if (!allowed) {
    return <UnavailableState title="Zdravlje sistema nije dostupno" />;
  }

  if (query.isLoading) {
    return <LoadingState label="Ucitavanje zdravlja sistema..." />;
  }

  if (query.isError || !query.data) {
    return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  }

  const response = query.data;
  const report = response.data;
  const metrics = Object.entries(report.metrics);

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Admin</Text>
      </Pressable>

      <PageHeader
        title="Zdravlje sistema"
        eyebrow="Admin · Read only"
        name={bootstrap?.user.name}
      />

      <Card style={styles.heroCard}>
        <Text style={styles.heroEyebrow}>Trenutni status</Text>
        <Text style={styles.heroStatus}>{statusLabel(report.status)}</Text>
        <Text style={styles.muted}>Provereno: {formatCheckedAt(report.checked_at)}</Text>
        <Text style={styles.muted}>Provera: {report.checks.length}</Text>
        {response.capabilities.refresh ? (
          <Button
            variant="secondary"
            onPress={() => void query.refetch()}
          >
            {query.isFetching ? 'Osvezavanje...' : 'Osvezi stanje'}
          </Button>
        ) : null}
      </Card>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Provere sistema</Text>
        {report.checks.length === 0 ? (
          <Card style={styles.card}>
            <Text style={styles.muted}>Nema dostupnih provera.</Text>
          </Card>
        ) : (
          report.checks.map((check, index) => {
            const object = isObjectValue(check) ? check : null;
            const title = object
              ? firstText(object, ['label', 'name', 'check', 'title', 'key'])
              : null;
            const status = object
              ? firstText(object, ['status', 'state', 'result'])
              : null;
            const message = object
              ? firstText(object, ['message', 'detail', 'description', 'reason', 'remediation'])
              : null;

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
          })
        )}
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Metrike</Text>
        <Card style={styles.card}>
          {metrics.length === 0 ? (
            <Text style={styles.muted}>Nema dostupnih metrika.</Text>
          ) : (
            metrics.map(([key, value]) => (
              <View key={key} style={styles.metricRow}>
                <Text style={styles.metricLabel}>{key}</Text>
                <Text style={styles.metricValue}>{displayValue(value)}</Text>
              </View>
            ))
          )}
        </Card>
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Istorija stanja</Text>
        {response.history.length === 0 ? (
          <Card style={styles.card}>
            <Text style={styles.muted}>Nema sacuvanih health snapshot zapisa.</Text>
          </Card>
        ) : (
          response.history.map((item) => (
            <HistoryCard key={item.id} item={item} styles={styles} />
          ))
        )}
      </View>

      <Card style={styles.readOnlyCard}>
        <Text style={styles.cardTitle}>Read-only pristup</Text>
        <Text style={styles.muted}>
          Mobilna aplikacija u ovoj verziji ne pravi snapshot, backup niti prune sistema.
        </Text>
      </Card>
    </Screen>
  );
}

function HistoryCard({
  item,
  styles,
}: {
  item: AdminSystemHealthHistoryItem;
  styles: ReturnType<typeof createStyles>;
}) {
  return (
    <Card style={styles.card}>
      <View style={styles.rowBetween}>
        <Text style={styles.cardTitle}>Snapshot #{item.id}</Text>
        <Text style={styles.statusText}>{statusLabel(item.status)}</Text>
      </View>
      <Text style={styles.muted}>{formatCheckedAt(item.checked_at)}</Text>
      <Text style={styles.muted}>Provera: {item.checks.length}</Text>
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
    readOnlyCard: { gap: spacing.sm },
    cardTitle: { ...typography.label, color: theme.ink, flex: 1 },
    body: { ...typography.body, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    statusText: { ...typography.label, color: theme.primary },
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
