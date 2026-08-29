import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { DataList } from '@/components/ui/data-list';
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
import { Pill, type PillTone } from '@/components/ui/pill';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminAfterSales,
  type AdminAfterSalesListParams,
  type AdminAfterSalesOptionMap,
  type AdminAfterSalesSummary,
} from '@/features/admin/after-sales-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { formatDate } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';

// MOBILE_V1_0_ADMIN_AFTER_SALES_LIST_UX_REORGANIZATION_BATCH95
type AfterSalesListWorkspace = 'overview' | 'cases' | 'filters' | 'overdue' | 'execution';

const AFTER_SALES_LIST_WORKSPACE_OPTIONS: Array<{ value: AfterSalesListWorkspace; label: string; description: string }> = [
  { value: 'overview', label: 'Pregled', description: 'Ukupni obim, aktivni filteri i brz ulaz u svakodnevni postprodajni rad.' },
  { value: 'cases', label: 'Slučajevi', description: 'Virtualizovana lista postprodajnih slučajeva sa statusom, rokom i odgovornim licem.' },
  { value: 'filters', label: 'Filteri', description: 'Pretraga, status, prioritet, vrsta slučaja i broj rezultata po strani.' },
  { value: 'overdue', label: 'Rokovi', description: 'Fokus na slučajeve koji su probili postojeći server-side rok.' },
  { value: 'execution', label: 'Izvršenje', description: 'Fokus na slučajeve koji čekaju postojeću izvršnu postprodajnu radnju.' },
];

function tone(status: string): PillTone {
  if (status === 'resolved' || status === 'approved' || status === 'closed') return 'success';
  if (status === 'rejected' || status === 'cancelled') return 'danger';
  if (status === 'under_review' || status === 'awaiting_customer') return 'warning';
  if (status === 'in_service' || status === 'in_progress') return 'info';
  return 'primary';
}

function optionsFromMap(map: AdminAfterSalesOptionMap, allLabel: string) {
  return [
    { value: '', label: allLabel },
    ...Object.entries(map).map(([value, label]) => ({ value, label })),
  ];
}

function CaseCard({ item }: { item: AdminAfterSalesSummary }) {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Otvori postprodajni slučaj ${item.case_number}`}
      onPress={() => router.push({ pathname: '/admin/after-sales/[id]', params: { id: String(item.id) } })}
      style={({ pressed }) => pressed ? styles.pressed : undefined}
    >
      <Card style={styles.caseCard}>
        <View style={styles.cardHead}>
          <View style={styles.grow}>
            <Text style={styles.caseNumber}>{item.case_number}</Text>
            <Text style={styles.subject}>{item.subject}</Text>
          </View>
          <Pill tone={tone(item.status)}>{item.status_label}</Pill>
        </View>
        <Text style={styles.meta}>
          {item.case_type_label} · {item.priority_label}
        </Text>
        <Text style={styles.meta}>
          Porudžbina: {item.order.order_number ?? `#${item.order.id}`}
        </Text>
        <Text style={styles.meta}>
          Odgovorno lice: {item.assignee?.name ?? 'Nije dodeljeno'}
        </Text>
        <Text style={styles.meta}>
          Rok: {item.due_at ? formatDate(item.due_at, true) : 'Nije definisan'}
        </Text>
        {item.pending_actions_count > 0 ? (
          <Text style={styles.attention}>Aktivne izvršne radnje: {item.pending_actions_count}</Text>
        ) : null}
      </Card>
    </Pressable>
  );
}

export default function AdminAfterSalesListScreen() {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  const { bootstrap, can } = useAuth();
  const allowed = can('after_sales.manage');
  const [workspace, setWorkspace] = useState<AfterSalesListWorkspace> ('overview');

  const [draftQ, setDraftQ] = useState('');
  const [draftStatus, setDraftStatus] = useState('');
  const [draftPriority, setDraftPriority] = useState('');
  const [draftType, setDraftType] = useState('');
  const [draftOverdue, setDraftOverdue] = useState(false);
  const [draftExecutionPending, setDraftExecutionPending] = useState(false);
  const [draftPerPage, setDraftPerPage] = useState(40);
  const [applied, setApplied] = useState<AdminAfterSalesListParams> ({ page: 1, per_page: 40 });

  const query = useQuery({
    queryKey: adminQueryKeys.afterSalesAdminList(applied),
    queryFn: () => apiAdminAfterSales.list(applied),
    enabled: allowed,
  });

  if (!allowed) return <UnavailableState title="Administracija postprodaje nije dostupna" />;
  if (query.isLoading) return <LoadingState label="Učitavanje postprodajnih slučajeva…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const response = query.data;
  const statusOptions = optionsFromMap(response.filter_options.statuses, 'Svi statusi');
  const priorityOptions = optionsFromMap(response.filter_options.priorities, 'Svi prioriteti');
  const typeOptions = optionsFromMap(response.filter_options.types, 'Sve vrste');
  const activeCount = Number(Boolean(applied.q))
    + Number(Boolean(applied.status))
    + Number(Boolean(applied.priority))
    + Number(Boolean(applied.case_type))
    + Number(Boolean(applied.overdue))
    + Number(Boolean(applied.execution_pending));

  const applyFilters = () => {
    setApplied({
      q: draftQ.trim() || undefined,
      status: draftStatus || undefined,
      priority: draftPriority || undefined,
      case_type: draftType || undefined,
      overdue: draftOverdue || undefined,
      execution_pending: draftExecutionPending || undefined,
      page: 1,
      per_page: draftPerPage,
    });
    setWorkspace('cases');
  };

  const clearFilters = () => {
    setDraftQ('');
    setDraftStatus('');
    setDraftPriority('');
    setDraftType('');
    setDraftOverdue(false);
    setDraftExecutionPending(false);
    setDraftPerPage(40);
    setApplied({ page: 1, per_page: 40 });
  };


  const workspaceMeta = AFTER_SALES_LIST_WORKSPACE_OPTIONS.find((option) => option.value === workspace);
  const pendingActionsOnPage = response.data.reduce((total, item) => total + item.pending_actions_count, 0);

  const selectWorkspace = (next: AfterSalesListWorkspace) => {
    setWorkspace(next);
  };

  const showOverdueCases = () => {
    setDraftOverdue(true);
    setApplied((current) => ({ ...current, overdue: true, page: 1 }));
    setWorkspace('cases');
  };

  const showExecutionCases = () => {
    setDraftExecutionPending(true);
    setApplied((current) => ({ ...current, execution_pending: true, page: 1 }));
    setWorkspace('cases');
  };

  const workspaceHeader = (
    <View style={styles.header}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Administracija</Text>
      </Pressable>
      <PageHeader title="Postprodaja" eyebrow="Admin · v1.0" name={bootstrap?.user.name} />
      <Text style={styles.copy}>
        Operativni pregled reklamacija, servisa, poruka i izvršnih postprodajnih radnji u dozvoljenom administratorskom scope-u.
      </Text>
      <Card style={styles.workspaceCard}>
        <Text style={styles.sectionTitle}>Radni prostor postprodaje</Text>
        <Text style={styles.copy}>
          {workspaceMeta?.description ?? 'Izaberi postprodajni zadatak koji želiš da obradiš.'}
        </Text>
        <FilterBar>
          {AFTER_SALES_LIST_WORKSPACE_OPTIONS.map((option) => (
            <FilterChip
              key={option.value}
              label={option.label}
              active={workspace === option.value}
              onPress={() => selectWorkspace(option.value)}
            />
          ))}
        </FilterBar>
      </Card>
    </View>
  );

  const pagination = (
    <View style={styles.pagination}>
      <Button
        variant="secondary"
        disabled={response.meta.current_page <= 1}
        onPress={() => setApplied((current) => ({ ...current, page: Math.max(1, response.meta.current_page - 1) }))}
      >
        Prethodna
      </Button>
      <Text style={styles.page}>{response.meta.current_page} / {Math.max(response.meta.last_page, 1)}</Text>
      <Button
        variant="secondary"
        disabled={response.meta.current_page >= response.meta.last_page}
        onPress={() => setApplied((current) => ({ ...current, page: response.meta.current_page + 1 }))}
      >
        Sledeća
      </Button>
    </View>
  );

  if (workspace === 'cases') {
    return (
      <DataList<AdminAfterSalesSummary>
        data={response.data}
        keyExtractor={(item) => String(item.id)}
        header={(
          <View style={styles.listHeader}>
            {workspaceHeader}
            <View style={styles.sectionHead}>
              <View>
                <Text style={styles.sectionTitle}>Postprodajni slučajevi</Text>
                <Text style={styles.muted}>{response.meta.total} ukupno · {activeCount} aktivnih filtera</Text>
              </View>
              <Button variant="secondary" onPress={() => void query.refetch()}>
                {query.isFetching ? 'Osvežavanje…' : 'Osveži'}
              </Button>
            </View>
          </View>
        )}
        footer={pagination}
        refreshing={query.isFetching}
        onRefresh={() => void query.refetch()}
        emptyTitle="Nema postprodajnih slučajeva"
        emptyMessage="Nema rezultata za izabrane filtere."
        renderItem={(item) => <CaseCard item={item} />}
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
            <Text style={styles.muted}>{activeCount} aktivnih</Text>
          </View>
          <TextField label="Pretraga" value={draftQ} onChangeText={setDraftQ} placeholder="Broj slučaja, predmet ili porudžbina" />
          <SelectSheet label="Status" value={draftStatus} options={statusOptions} onChange={setDraftStatus} />
          <SelectSheet label="Prioritet" value={draftPriority} options={priorityOptions} onChange={setDraftPriority} />
          <SelectSheet label="Vrsta slučaja" value={draftType} options={typeOptions} onChange={setDraftType} />
          <FilterBar activeCount={Number(draftOverdue) + Number(draftExecutionPending)} onClear={() => { setDraftOverdue(false); setDraftExecutionPending(false); }}>
            <FilterChip label="Probio rok" active={draftOverdue} onPress={() => setDraftOverdue((value) => !value)} />
            <FilterChip label="Čeka izvršenje" active={draftExecutionPending} onPress={() => setDraftExecutionPending((value) => !value)} />
          </FilterBar>
          <SelectSheet
            label="Broj po strani"
            value={String(draftPerPage)}
            options={[20, 40, 50, 100].map((value) => ({ value: String(value), label: String(value) }))}
            onChange={(value) => {
              const next = Number(value);
              if (next === 20 || next === 40 || next === 50 || next === 100) setDraftPerPage(next);
            }}
          />
          <View style={styles.actionsRow}>
            <Button onPress={applyFilters}>Primeni i otvori slučajeve</Button>
            {activeCount > 0 ? <Button variant="secondary" onPress={clearFilters}>Očisti filtere</Button> : null}
          </View>
        </Card>
      </Screen>
    );
  }

  if (workspace === 'overdue') {
    return (
      <Screen contentStyle={styles.content}>
        {workspaceHeader}
        <Card style={styles.focusCard}>
          <Text style={styles.sectionTitle}>Slučajevi koji su probili rok</Text>
          <Text style={styles.copy}>
            Ovaj radni prostor koristi postojeći server-side overdue filter; ne uvodi lokalno računanje roka niti novi poslovni kriterijum.
          </Text>
          <View style={styles.actionsRow}>
            <Button onPress={showOverdueCases}>Prikaži slučajeve koji su probili rok</Button>
            <Button variant="secondary" onPress={() => setWorkspace('filters')}>Dodatni filteri</Button>
          </View>
        </Card>
      </Screen>
    );
  }

  if (workspace === 'execution') {
    return (
      <Screen contentStyle={styles.content}>
        {workspaceHeader}
        <Card style={styles.focusCard}>
          <Text style={styles.sectionTitle}>Slučajevi koji čekaju izvršenje</Text>
          <Text style={styles.copy}>
            Koristi postojeći server-side execution_pending filter i postojeći detail workflow za izvršne radnje.
          </Text>
          <Text style={styles.muted}>Aktivne izvršne radnje na trenutno učitanoj strani: {pendingActionsOnPage}</Text>
          <View style={styles.actionsRow}>
            <Button onPress={showExecutionCases}>Prikaži slučajeve koji čekaju izvršenje</Button>
            <Button variant="secondary" onPress={() => setWorkspace('filters')}>Dodatni filteri</Button>
          </View>
        </Card>
      </Screen>
    );
  }

  return (
    <Screen contentStyle={styles.content}>
      {workspaceHeader}
      <View style={styles.summaryGrid}>
        <Card style={styles.summaryCard}>
          <Text style={styles.summaryValue}>{response.meta.total}</Text>
          <Text style={styles.summaryLabel}>Ukupno slučajeva</Text>
        </Card>
        <Card style={styles.summaryCard}>
          <Text style={styles.summaryValue}>{response.data.length}</Text>
          <Text style={styles.summaryLabel}>Na trenutnoj strani</Text>
        </Card>
        <Card style={styles.summaryCard}>
          <Text style={styles.summaryValue}>{activeCount}</Text>
          <Text style={styles.summaryLabel}>Aktivnih filtera</Text>
        </Card>
        <Card style={styles.summaryCard}>
          <Text style={styles.summaryValue}>{pendingActionsOnPage}</Text>
          <Text style={styles.summaryLabel}>Aktivnih radnji na strani</Text>
        </Card>
      </View>
      <Card style={styles.focusCard}>
        <Text style={styles.sectionTitle}>Brze akcije</Text>
        <View style={styles.actionsRow}>
          <Button onPress={() => setWorkspace('cases')}>Otvori slučajeve</Button>
          <Button variant="secondary" onPress={() => setWorkspace('filters')}>Filteri</Button>
          <Button variant="secondary" onPress={() => setWorkspace('overdue')}>Rokovi</Button>
          <Button variant="secondary" onPress={() => setWorkspace('execution')}>Izvršenje</Button>
        </View>
      </Card>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: 140 },
    header: { gap: spacing.lg },
    workspaceCard: { gap: spacing.md },
    listHeader: { gap: spacing.lg },
    focusCard: { gap: spacing.md },
    actionsRow: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center', gap: spacing.sm },
    summaryGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    summaryCard: { minWidth: '47%', flexGrow: 1, gap: spacing.xs },
    summaryValue: { ...typography.h2, color: theme.ink },
    summaryLabel: { ...typography.small, color: theme.muted },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    copy: { ...typography.body, color: theme.muted },
    filtersCard: { gap: spacing.md },
    sectionHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
    sectionTitle: { ...typography.h3, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    list: { gap: spacing.md },
    caseCard: { gap: spacing.sm },
    cardHead: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
    grow: { flex: 1, minWidth: 0 },
    caseNumber: { ...typography.small, color: theme.primary, fontWeight: '800' },
    subject: { ...typography.h3, color: theme.ink, marginTop: spacing.xs },
    meta: { ...typography.small, color: theme.muted },
    attention: { ...typography.small, color: theme.primary, fontWeight: '800' },
    pagination: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
    page: { ...typography.label, color: theme.ink },
    pressed: { opacity: 0.72 },
  });
}
