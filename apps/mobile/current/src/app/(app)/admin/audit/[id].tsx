import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Card } from '@/components/ui/card';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminAuditEvents,
  type AdminAuditContextValue,
} from '@/features/admin/audit-admin-api';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

function formatDateTime(value: string | null): string {
  if (!value) return 'Vreme nije dostupno';
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleString('sr-RS');
}

function displayContext(value: AdminAuditContextValue): string {
  if (value === null) return 'Nema dodatnog sanitizovanog konteksta.';
  if (Array.isArray(value) && value.length === 0) return 'Nema dodatnog sanitizovanog konteksta.';
  if (typeof value === 'object' && !Array.isArray(value) && Object.keys(value).length === 0) return 'Nema dodatnog sanitizovanog konteksta.';
  try {
    return JSON.stringify(value, null, 2);
  } catch {
    return 'Kontekst nije moguce prikazati.';
  }
}

export default function AdminAuditDetailScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const params = useLocalSearchParams<{ id?: string | string[] }> ();
  const rawId = Array.isArray(params.id) ? params.id[0] : params.id;
  const eventId = Number(rawId);
  const validId = Number.isInteger(eventId) && eventId > 0;
  const allowed = can('security.view');

  const query = useQuery({
    queryKey: adminQueryKeys.auditEvent(eventId),
    queryFn: () => apiAdminAuditEvents.detail(eventId),
    enabled: allowed && validId,
  });

  if (!allowed) {
    return <UnavailableState title="Audit nije dostupan" />;
  }

  if (!validId) {
    return <UnavailableState title="Audit dogadjaj nije validan" />;
  }

  if (query.isLoading) {
    return <LoadingState label="Ucitavanje audit detalja..." />;
  }

  if (query.isError || !query.data) {
    return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  }

  const response = query.data;
  const item = response.data;

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Audit</Text>
      </Pressable>

      <PageHeader
        title={item.event_type || `Audit #${item.id}`}
        eyebrow="Admin · Safe detail"
        name={bootstrap?.user.name}
      />

      <Card style={styles.card}>
        <DetailRow label="ID" value={String(item.id)} styles={styles} />
        <DetailRow label="Nivo" value={item.severity || '-'} styles={styles} />
        <DetailRow label="Datum" value={formatDateTime(item.created_at)} styles={styles} />
        <DetailRow label="Korisnik" value={item.user?.name ?? 'Gost / sistem'} styles={styles} />
        <DetailRow label="Username" value={item.user?.username ?? '-'} styles={styles} />
        <DetailRow label="Metod" value={item.method ?? '-'} styles={styles} />
        <DetailRow label="Ruta" value={item.route_name ?? '-'} styles={styles} />
        <DetailRow label="IP" value={item.ip_address ?? '-'} styles={styles} />
        <DetailRow label="Request ID" value={item.request_id ?? '-'} styles={styles} />
      </Card>

      <Card style={styles.card}>
        <Text style={styles.cardTitle}>Sanitizovani kontekst</Text>
        <Text selectable style={styles.context}>{displayContext(item.context)}</Text>
      </Card>

      <Card style={styles.readOnlyCard}>
        <Text style={styles.cardTitle}>Bezbednosni ugovor</Text>
        <Text style={styles.muted}>
          Raw user_agent i raw context_json nisu deo API odgovora. CSV export capability je server-driven; mutacije ostaju isključene.
        </Text>
        <Text style={styles.muted}>
          Capabilities: export={String(response.capabilities.export)}, mutate={String(response.capabilities.mutate)}
        </Text>
      </Card>
    </Screen>
  );
}

function DetailRow({
  label,
  value,
  styles,
}: {
  label: string;
  value: string;
  styles: ReturnType<typeof createStyles>;
}) {
  return (
    <View style={styles.detailRow}>
      <Text style={styles.detailLabel}>{label}</Text>
      <Text selectable style={styles.detailValue}>{value}</Text>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 140, gap: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    card: { gap: spacing.sm },
    readOnlyCard: { gap: spacing.sm },
    cardTitle: { ...typography.label, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    context: { ...typography.small, color: theme.ink },
    detailRow: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    detailLabel: { ...typography.small, color: theme.muted, flex: 1 },
    detailValue: { ...typography.body, color: theme.ink, flex: 2, textAlign: 'right' },
  });
}
