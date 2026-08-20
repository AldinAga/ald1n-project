import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { DateTimeField } from '@/components/ui/date-time-field';
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminAuditEvents,
  type AdminAuditEventSummary,
  type AdminAuditPerPage,
  type AdminAuditRequestParams,
} from '@/features/admin/audit-admin-api';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

const PER_PAGE_OPTIONS: Array<{ value: string; label: string }> = [
  { value: '20', label: '20 po strani' },
  { value: '50', label: '50 po strani' },
  { value: '100', label: '100 po strani' },
];

function trimmed(value: string): string | undefined {
  const normalized = value.trim();
  return normalized || undefined;
}

function formatDateTime(value: string | null): string {
  if (!value) return 'Vreme nije dostupno';
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleString('sr-RS');
}

function severityLabel(value: string): string {
  const normalized = value.trim().toLowerCase();
  if (normalized === 'error' || normalized === 'critical') return 'Kriticno';
  if (normalized === 'warning' || normalized === 'warn') return 'Upozorenje';
  if (normalized === 'info') return 'Info';
  return value || 'Nepoznato';
}

export default function AdminAuditIndexScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const feedback = useAppFeedback();
  const allowed = can('security.view');

  const [draftAction, setDraftAction] = useState('');
  const [draftLevel, setDraftLevel] = useState('');
  const [draftUserId, setDraftUserId] = useState('');
  const [draftFrom, setDraftFrom] = useState('');
  const [draftTo, setDraftTo] = useState('');
  const [draftPerPage, setDraftPerPage] = useState<AdminAuditPerPage> (20);
  const [applied, setApplied] = useState<AdminAuditRequestParams> ({ per_page: 20 });

  const query = useQuery({
    queryKey: adminQueryKeys.auditEvents(applied),
    queryFn: () => apiAdminAuditEvents.list(applied),
    enabled: allowed,
  });

  const activeCount = Number(Boolean(draftAction.trim()))
    + Number(Boolean(draftLevel))
    + Number(Boolean(draftUserId))
    + Number(Boolean(draftFrom))
    + Number(Boolean(draftTo))
    + Number(draftPerPage !== 20);

  const applyFilters = () => {
    if (draftFrom && draftTo && draftTo < draftFrom) {
      feedback.notify({
        tone: 'danger',
        title: 'Neispravan period',
        message: 'Datum Do ne moze biti pre datuma Od.',
      });
      return;
    }

    const next: AdminAuditRequestParams = {
      page: 1,
      per_page: draftPerPage,
    };
    const action = trimmed(draftAction);
    if (action) next.action = action;
    if (draftLevel) next.level = draftLevel;
    const userId = Number(draftUserId);
    if (draftUserId && Number.isInteger(userId) && userId > 0) next.user_id = userId;
    if (draftFrom) next.date_from = draftFrom;
    if (draftTo) next.date_to = draftTo;
    setApplied(next);
  };

  const clearFilters = () => {
    setDraftAction('');
    setDraftLevel('');
    setDraftUserId('');
    setDraftFrom('');
    setDraftTo('');
    setDraftPerPage(20);
    setApplied({ per_page: 20, page: 1 });
  };

  const setPage = (page: number) => {
    if (page < 1) return;
    setApplied((current) => ({ ...current, page }));
  };

  if (!allowed) {
    return <UnavailableState title="Audit nije dostupan" />;
  }

  if (query.isLoading) {
    return <LoadingState label="Ucitavanje audit dogadjaja..." />;
  }

  if (query.isError || !query.data) {
    return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  }

  const response = query.data;
  const levelOptions = [
    { value: '', label: 'Svi nivoi' },
    ...response.filter_options.levels.map((level) => ({ value: level, label: severityLabel(level) })),
  ];
  const userOptions = [
    { value: '', label: 'Svi korisnici' },
    ...response.filter_options.users.map((user) => ({
      value: String(user.id),
      label: user.username ? `${user.name} (${user.username})` : user.name,
    })),
  ];

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Administracija</Text>
      </Pressable>

      <PageHeader
        title="Audit i bezbednost"
        eyebrow="Admin · Read only"
        name={bootstrap?.user.name}
      />

      <Text style={styles.copy}>
        Pregled sanitizovanih security dogadjaja. Mobilna aplikacija ne menja niti brise audit zapise.
      </Text>

      <Card style={styles.filtersCard}>
        <View style={styles.sectionHead}>
          <Text style={styles.sectionTitle}>Filteri</Text>
          <Text style={styles.muted}>{activeCount} aktivnih</Text>
        </View>

        <FilterBar activeCount={activeCount} onClear={clearFilters}>
          {response.filter_options.levels.slice(0, 3).map((level) => (
            <FilterChip
              key={level}
              label={severityLabel(level)}
              active={draftLevel === level}
              onPress={() => setDraftLevel(draftLevel === level ? '' : level)}
            />
          ))}
        </FilterBar>

        <TextField
          label="Akcija / dogadjaj"
          value={draftAction}
          onChangeText={setDraftAction}
          placeholder="npr. login, backup, rate_limit"
        />
        <SelectSheet
          label="Nivo"
          value={draftLevel}
          options={levelOptions}
          onChange={setDraftLevel}
        />
        <SelectSheet
          label="Korisnik"
          value={draftUserId}
          options={userOptions}
          onChange={setDraftUserId}
        />
        <DateTimeField
          label="Od datuma"
          mode="date"
          value={draftFrom}
          onChangeText={setDraftFrom}
        />
        <DateTimeField
          label="Do datuma"
          mode="date"
          value={draftTo}
          onChangeText={setDraftTo}
        />
        <SelectSheet
          label="Broj po strani"
          value={String(draftPerPage)}
          options={PER_PAGE_OPTIONS}
          onChange={(value) => {
            const next = Number(value);
            if (next === 20 || next === 50 || next === 100) {
              setDraftPerPage(next);
            }
          }}
        />
        <Button onPress={applyFilters}>Primeni filtere</Button>
      </Card>

      <View style={styles.sectionHead}>
        <View>
          <Text style={styles.sectionTitle}>Security dogadjaji</Text>
          <Text style={styles.muted}>{response.pagination.total} ukupno</Text>
        </View>
        <Button
          variant="secondary"
          onPress={() => void query.refetch()}
        >
          {query.isFetching ? 'Osvezavanje...' : 'Osvezi'}
        </Button>
      </View>

      {response.data.length === 0 ? (
        <Card style={styles.card}>
          <Text style={styles.muted}>Nema dogadjaja za izabrane filtere.</Text>
        </Card>
      ) : (
        response.data.map((item) => (
          <AuditEventCard
            key={item.id}
            item={item}
            canOpen={response.capabilities.detail}
            styles={styles}
          />
        ))
      )}

      <Card style={styles.paginationCard}>
        <Text style={styles.muted}>
          Strana {response.pagination.current_page} od {Math.max(response.pagination.last_page, 1)}
        </Text>
        <View style={styles.actionsRow}>
          <Button
            variant="secondary"
            onPress={() => setPage(response.pagination.current_page - 1)}
          >
            Prethodna
          </Button>
          <Button
            variant="secondary"
            onPress={() => setPage(response.pagination.current_page + 1)}
          >
            Sledeca
          </Button>
        </View>
      </Card>

      <Card style={styles.readOnlyCard}>
        <Text style={styles.cardTitle}>Read-only pristup</Text>
        <Text style={styles.muted}>
          Export i mutacije nisu deo ovog Mobile Audit koraka. Raw context_json i user_agent nisu izlozeni.
        </Text>
      </Card>
    </Screen>
  );
}

function AuditEventCard({
  item,
  canOpen,
  styles,
}: {
  item: AdminAuditEventSummary;
  canOpen: boolean;
  styles: ReturnType<typeof createStyles>;
}) {
  const content = (
    <Card style={styles.card}>
      <View style={styles.rowBetween}>
        <Text style={styles.cardTitle}>{item.event_type || `Dogadjaj #${item.id}`}</Text>
        <Text style={styles.severity}>{severityLabel(item.severity)}</Text>
      </View>
      <Text style={styles.muted}>{formatDateTime(item.created_at)}</Text>
      <Text style={styles.body}>
        {item.method ?? '-'} · {item.route_name ?? 'Ruta nije dostupna'}
      </Text>
      <Text style={styles.muted}>
        Korisnik: {item.user?.name ?? 'Gost / sistem'}
      </Text>
      {item.request_id ? <Text style={styles.requestId}>Request: {item.request_id}</Text> : null}
    </Card>
  );

  if (!canOpen) return content;

  return (
    <Pressable
      accessibilityRole="button"
      onPress={() => router.push({
        pathname: '/admin/audit/[id]',
        params: { id: String(item.id) },
      })}
    >
      {content}
    </Pressable>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 140, gap: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    copy: { ...typography.body, color: theme.muted },
    filtersCard: { gap: spacing.md },
    sectionHead: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    sectionTitle: { ...typography.h3, color: theme.ink },
    card: { gap: spacing.sm },
    cardTitle: { ...typography.label, color: theme.ink, flex: 1 },
    body: { ...typography.body, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    severity: { ...typography.label, color: theme.primary },
    requestId: { ...typography.small, color: theme.muted },
    rowBetween: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    paginationCard: { gap: spacing.md },
    actionsRow: { flexDirection: 'row', gap: spacing.sm, flexWrap: 'wrap' },
    readOnlyCard: { gap: spacing.sm },
  });
}
