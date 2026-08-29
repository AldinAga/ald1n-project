import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { DataList } from '@/components/ui/data-list';
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
import { openAdminAuditExport } from '@/features/admin/audit-admin-export';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

// MOBILE_V1_0_ADMIN_AUDIT_LIST_UX_REORGANIZATION_BATCH97
type AuditListWorkspace = 'overview' | 'events' | 'filters' | 'export' | 'policy';

const AUDIT_LIST_WORKSPACE_OPTIONS: Array<{ value: AuditListWorkspace; label: string; description: string }> = [
  { value: 'overview', label: 'Pregled', description: 'Sažetak audit obima, aktivnih filtera i server-prosleđenih read-only mogućnosti.' },
  { value: 'events', label: 'Događaji', description: 'Virtualizovana lista sanitizovanih security događaja sa server paginacijom.' },
  { value: 'filters', label: 'Filteri', description: 'Akcija, nivo, korisnik, vremenski period i broj događaja po strani.' },
  { value: 'export', label: 'Izvoz', description: 'Sanitizovani CSV kroz postojeću server-driven audit.export mogućnost.' },
  { value: 'policy', label: 'Bezbednost', description: 'Read-only granice, sanitizacija i eksplicitno odsustvo mutation workflow-a.' },
];

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
  if (normalized === 'error' || normalized === 'critical') return 'Kritično';
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
  const [workspace, setWorkspace] = useState<AuditListWorkspace> ('overview');

  const [draftAction, setDraftAction] = useState('');
  const [draftLevel, setDraftLevel] = useState('');
  const [draftUserId, setDraftUserId] = useState('');
  const [draftFrom, setDraftFrom] = useState('');
  const [draftTo, setDraftTo] = useState('');
  const [draftPerPage, setDraftPerPage] = useState<AdminAuditPerPage> (20);
  const [applied, setApplied] = useState<AdminAuditRequestParams> ({ per_page: 20 });
  const [exporting, setExporting] = useState(false);

  const query = useQuery({
    queryKey: adminQueryKeys.auditEvents(applied),
    queryFn: () => apiAdminAuditEvents.list(applied),
    enabled: allowed,
  });

  const draftActiveCount = Number(Boolean(draftAction.trim()))
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
        message: 'Datum Do ne može biti pre datuma Od.',
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
    setWorkspace('events');
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

  const exportCsv = async () => {
    if (exporting) return;
    setExporting(true);
    try {
      await openAdminAuditExport(applied);
      feedback.notify({
        tone: 'success',
        title: 'Audit CSV je spreman',
        message: 'Otvoren je sistemski dijalog za čuvanje ili deljenje sanitizovanog CSV izvoza.',
      });
    } catch (error) {
      feedback.notify({
        tone: 'danger',
        title: 'Audit CSV nije otvoren',
        message: error instanceof Error ? error.message : 'Pokušaj ponovo.',
      });
    } finally {
      setExporting(false);
    }
  };

  if (!allowed) {
    return <UnavailableState title="Audit nije dostupan" />;
  }

  if (query.isLoading) {
    return <LoadingState label="Učitavanje audit događaja..." />;
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
  const appliedActiveCount = Number(Boolean(response.filters.action))
    + Number(Boolean(response.filters.level))
    + Number(Boolean(response.filters.user_id))
    + Number(Boolean(response.filters.date_from))
    + Number(Boolean(response.filters.date_to))
    + Number(response.pagination.per_page !== 20);
  const workspaceMeta = AUDIT_LIST_WORKSPACE_OPTIONS.find((option) => option.value === workspace);

  const workspaceHeader = (
    <View style={styles.header}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Administracija</Text>
      </Pressable>
      <PageHeader
        title="Audit i bezbednost"
        eyebrow="Admin · v1.0 · Read only"
        name={bootstrap?.user.name}
      />
      <Text style={styles.copy}>
        Pregled sanitizovanih security događaja. Mobilna aplikacija ne menja niti briše audit zapise.
      </Text>
      <Card style={styles.workspaceCard}>
        <Text style={styles.sectionTitle}>Radni prostor audita</Text>
        <Text style={styles.copy}>{workspaceMeta?.description ?? 'Izaberi audit zadatak koji želiš da pregledaš.'}</Text>
        <FilterBar>
          {AUDIT_LIST_WORKSPACE_OPTIONS.map((option) => (
            <FilterChip
              key={option.value}
              label={option.label}
              active={workspace === option.value}
              onPress={() => setWorkspace(option.value)}
            />
          ))}
        </FilterBar>
      </Card>
    </View>
  );

  const pagination = (
    <Card style={styles.paginationCard}>
      <Text style={styles.muted}>
        Strana {response.pagination.current_page} od {Math.max(response.pagination.last_page, 1)}
      </Text>
      <View style={styles.actionsRow}>
        <Button
          variant="secondary"
          disabled={response.pagination.current_page <= 1}
          onPress={() => setPage(response.pagination.current_page - 1)}
        >
          Prethodna
        </Button>
        <Button
          variant="secondary"
          disabled={response.pagination.current_page >= response.pagination.last_page}
          onPress={() => setPage(response.pagination.current_page + 1)}
        >
          Sledeća
        </Button>
      </View>
    </Card>
  );

  if (workspace === 'events') {
    return (
      <DataList<AdminAuditEventSummary>
        data={response.data}
        keyExtractor={(item) => String(item.id)}
        header={(
          <View style={styles.listHeader}>
            {workspaceHeader}
            <View style={styles.sectionHead}>
              <View style={styles.grow}>
                <Text style={styles.sectionTitle}>Security događaji</Text>
                <Text style={styles.muted}>{response.pagination.total} ukupno · {appliedActiveCount} aktivnih filtera</Text>
              </View>
              <Button variant="secondary" onPress={() => void query.refetch()}>
                {query.isFetching ? 'Osvežavanje...' : 'Osveži'}
              </Button>
            </View>
          </View>
        )}
        footer={pagination}
        refreshing={query.isFetching}
        onRefresh={() => void query.refetch()}
        emptyTitle="Nema audit događaja"
        emptyMessage="Nema događaja za izabrane server filtere."
        renderItem={(item) => (
          <AuditEventCard
            item={item}
            canOpen={response.capabilities.detail}
            styles={styles}
          />
        )}
      />
    );
  }

  if (workspace === 'filters') {
    return (
      <Screen contentStyle={styles.content}>
        {workspaceHeader}
        <Card style={styles.filtersCard}>
          <View style={styles.sectionHead}>
            <Text style={styles.sectionTitle}>Filteri</Text>
            <Text style={styles.muted}>{draftActiveCount} aktivnih</Text>
          </View>
          <FilterBar activeCount={draftActiveCount} onClear={clearFilters}>
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
            label="Akcija / događaj"
            value={draftAction}
            onChangeText={setDraftAction}
            placeholder="npr. login, backup, rate_limit"
          />
          <SelectSheet label="Nivo" value={draftLevel} options={levelOptions} onChange={setDraftLevel} />
          <SelectSheet label="Korisnik" value={draftUserId} options={userOptions} onChange={setDraftUserId} />
          <DateTimeField label="Od datuma" mode="date" value={draftFrom} onChangeText={setDraftFrom} />
          <DateTimeField label="Do datuma" mode="date" value={draftTo} onChangeText={setDraftTo} />
          <SelectSheet
            label="Broj po strani"
            value={String(draftPerPage)}
            options={PER_PAGE_OPTIONS}
            onChange={(value) => {
              const next = Number(value);
              if (next === 20 || next === 50 || next === 100) setDraftPerPage(next);
            }}
          />
          <View style={styles.actionsRow}>
            <Button onPress={applyFilters}>Primeni i otvori događaje</Button>
            {draftActiveCount > 0 ? <Button variant="secondary" onPress={clearFilters}>Očisti filtere</Button> : null}
          </View>
        </Card>
      </Screen>
    );
  }

  if (workspace === 'export') {
    return (
      <Screen contentStyle={styles.content}>
        {workspaceHeader}
        <Card style={styles.focusCard}>
          <Text style={styles.sectionTitle}>Sanitizovani CSV izvoz</Text>
          <Text style={styles.copy}>
            Izvoz koristi trenutno primenjene server filtere i postojeći authenticated Bearer/cache/share tok. Ne uvodi novu download putanju.
          </Text>
          <Text style={styles.muted}>Aktivnih primenjenih filtera: {appliedActiveCount}</Text>
          {response.capabilities.export ? (
            <Button loading={exporting} onPress={() => void exportCsv()}>Izvezi CSV</Button>
          ) : (
            <Text style={styles.muted}>Server za ovaj nalog nije odobrio audit.export capability.</Text>
          )}
        </Card>
      </Screen>
    );
  }

  if (workspace === 'policy') {
    return (
      <Screen contentStyle={styles.content}>
        {workspaceHeader}
        <Card style={styles.readOnlyCard}>
          <Text style={styles.cardTitle}>Read-only bezbednosni ugovor</Text>
          <Text style={styles.muted}>
            Mutacije i brisanje audit zapisa ostaju isključeni. Raw context_json i user_agent nisu izloženi kroz mobilni ugovor.
          </Text>
          <Text style={styles.muted}>
            Capabilities: detail={String(response.capabilities.detail)}, export={String(response.capabilities.export)}, mutate={String(response.capabilities.mutate)}
          </Text>
          <Button variant="secondary" onPress={() => setWorkspace('events')}>Otvori događaje</Button>
        </Card>
      </Screen>
    );
  }

  return (
    <Screen contentStyle={styles.content}>
      {workspaceHeader}
      <View style={styles.summaryGrid}>
        <Card style={styles.summaryCard}>
          <Text style={styles.summaryValue}>{response.pagination.total}</Text>
          <Text style={styles.muted}>Ukupno događaja</Text>
        </Card>
        <Card style={styles.summaryCard}>
          <Text style={styles.summaryValue}>{response.data.length}</Text>
          <Text style={styles.muted}>Na trenutnoj strani</Text>
        </Card>
        <Card style={styles.summaryCard}>
          <Text style={styles.summaryValue}>{appliedActiveCount}</Text>
          <Text style={styles.muted}>Aktivnih filtera</Text>
        </Card>
        <Card style={styles.summaryCard}>
          <Text style={styles.summaryValue}>{response.filter_options.users.length}</Text>
          <Text style={styles.muted}>Korisnika u filteru</Text>
        </Card>
      </View>
      <Card style={styles.focusCard}>
        <Text style={styles.sectionTitle}>Brzi ulazi</Text>
        <Text style={styles.copy}>
          Audit ostaje potpuno read-only; navigacija samo organizuje postojeći pregled, filtere, secure CSV i bezbednosni ugovor.
        </Text>
        <View style={styles.actionsRow}>
          <Button onPress={() => setWorkspace('events')}>Događaji</Button>
          <Button variant="secondary" onPress={() => setWorkspace('filters')}>Filteri</Button>
          <Button variant="secondary" onPress={() => setWorkspace('export')}>Izvoz</Button>
        </View>
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
        <Text style={styles.cardTitle}>{item.event_type || `Događaj #${item.id}`}</Text>
        <Text style={styles.severity}>{severityLabel(item.severity)}</Text>
      </View>
      <Text style={styles.muted}>{formatDateTime(item.created_at)}</Text>
      <Text style={styles.body}>
        {item.method ?? '-'} · {item.route_name ?? 'Ruta nije dostupna'}
      </Text>
      <Text style={styles.muted}>Korisnik: {item.user?.name ?? 'Gost / sistem'}</Text>
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
    header: { gap: spacing.md },
    listHeader: { gap: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    copy: { ...typography.body, color: theme.muted },
    workspaceCard: { gap: spacing.md },
    filtersCard: { gap: spacing.md },
    focusCard: { gap: spacing.md },
    sectionHead: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    sectionTitle: { ...typography.h3, color: theme.ink },
    grow: { flex: 1, minWidth: 0 },
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
    summaryGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.md },
    summaryCard: { flexGrow: 1, flexBasis: '45%', minWidth: 140, gap: spacing.xs },
    summaryValue: { ...typography.h2, color: theme.ink },
  });
}
