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
  apiAdminFieldOperations,
  type AdminFieldWorkListParams,
  type AdminFieldWorkSummary,
  type AdminFieldWorkTeam,
} from '@/features/admin/field-operations-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { formatDate } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';

// MOBILE_V1_0_ADMIN_FIELD_OPERATIONS_LIST_UX_REORGANIZATION_BATCH96
type FieldOperationsListWorkspace = 'overview' | 'work_orders' | 'filters' | 'unassigned' | 'teams';

const FIELD_OPERATIONS_LIST_WORKSPACE_OPTIONS: Array<{ value: FieldOperationsListWorkspace; label: string; description: string }> = [
  { value: 'overview', label: 'Pregled', description: 'Ukupni obim terenskih naloga, aktivni filteri, ekipe i brzi operativni ulazi.' },
  { value: 'work_orders', label: 'Radni nalozi', description: 'Virtualizovana lista terenskih naloga sa ekipom, terminom, statusom i povezanim predmetom.' },
  { value: 'filters', label: 'Filteri', description: 'Pretraga, status, ekipa, period, nedodeljeni nalozi i broj rezultata po strani.' },
  { value: 'unassigned', label: 'Bez ekipe', description: 'Fokus na postojeći server-side unassigned kriterijum bez lokalne poslovne logike.' },
  { value: 'teams', label: 'Ekipe', description: 'Pregled server-prosleđenih ekipa i brz filter njihovih terenskih naloga.' },
];

function tone(status: string): PillTone {
  if (status === 'completed') return 'success';
  if (status === 'cancelled') return 'danger';
  if (status === 'en_route' || status === 'on_site') return 'info';
  if (status === 'planned') return 'warning';
  return 'primary';
}

function teamOptions(teams: AdminFieldWorkTeam[]) {
  return [
    { value: '', label: 'Sve ekipe' },
    ...teams.map((team) => ({ value: String(team.id), label: `${team.name}${team.is_active ? '' : ' · neaktivna'}` })),
  ];
}

function WorkOrderCard({ item }: { item: AdminFieldWorkSummary }) {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Otvori terenski nalog ${item.work_order_number}`}
      onPress={() => router.push({ pathname: '/admin/field-operations/[id]', params: { id: String(item.id) } })}
      style={({ pressed }) => pressed ? styles.pressed : undefined}
    >
      <Card style={styles.workCard}>
        <View style={styles.cardHead}>
          <View style={styles.grow}>
            <Text style={styles.number}>{item.work_order_number}</Text>
            <Text style={styles.subject}>{item.case?.subject ?? item.case?.case_number ?? 'Terenska intervencija'}</Text>
          </View>
          <Pill tone={tone(item.status)}>{item.status_label}</Pill>
        </View>
        <Text style={styles.meta}>Ekipa: {item.team?.name ?? 'Nije dodeljeno'}</Text>
        <Text style={styles.meta}>Termin: {item.planned_start_at ? formatDate(item.planned_start_at, true) : 'Nije zakazan'}</Text>
        {item.case ? <Text style={styles.meta}>Postprodaja: {item.case.case_number}</Text> : null}
        {item.order ? <Text style={styles.meta}>Porudžbina: {item.order.order_number || `#${item.order.id}`}</Text> : null}
        {item.route_reference ? <Text style={styles.meta}>Referenca: {item.route_reference}</Text> : null}
      </Card>
    </Pressable>
  );
}

export default function AdminFieldOperationsListScreen() {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  const { bootstrap, can } = useAuth();
  const allowed = can('field_operations.view');
  const [workspace, setWorkspace] = useState<FieldOperationsListWorkspace> ('overview');

  const [draftQ, setDraftQ] = useState('');
  const [draftStatus, setDraftStatus] = useState('');
  const [draftTeam, setDraftTeam] = useState('');
  const [draftDateFrom, setDraftDateFrom] = useState('');
  const [draftDateTo, setDraftDateTo] = useState('');
  const [draftUnassigned, setDraftUnassigned] = useState(false);
  const [draftPerPage, setDraftPerPage] = useState(40);
  const [applied, setApplied] = useState<AdminFieldWorkListParams> ({ page: 1, per_page: 40 });

  const query = useQuery({
    queryKey: adminQueryKeys.fieldOperationsList(applied),
    queryFn: () => apiAdminFieldOperations.list(applied),
    enabled: allowed,
  });

  if (!allowed) return <UnavailableState title="Terenske operacije nisu dostupne" />;
  if (query.isLoading) return <LoadingState label="Učitavanje terenskih naloga…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const response = query.data;
  const statusOptions = [
    { value: '', label: 'Svi statusi' },
    ...Object.entries(response.filter_options.statuses).map(([value, label]) => ({ value, label })),
  ];
  const activeCount = Number(Boolean(applied.q))
    + Number(Boolean(applied.status))
    + Number(Boolean(applied.team_id))
    + Number(Boolean(applied.date_from))
    + Number(Boolean(applied.date_to))
    + Number(Boolean(applied.unassigned));

  const applyFilters = () => {
    setApplied({
      q: draftQ.trim() || undefined,
      status: draftStatus || undefined,
      team_id: draftTeam ? Number(draftTeam) : undefined,
      date_from: draftDateFrom.trim() || undefined,
      date_to: draftDateTo.trim() || undefined,
      unassigned: draftUnassigned || undefined,
      page: 1,
      per_page: draftPerPage,
    });
    setWorkspace('work_orders');
  };

  const clearFilters = () => {
    setDraftQ('');
    setDraftStatus('');
    setDraftTeam('');
    setDraftDateFrom('');
    setDraftDateTo('');
    setDraftUnassigned(false);
    setDraftPerPage(40);
    setApplied({ page: 1, per_page: 40 });
  };


  const workspaceMeta = FIELD_OPERATIONS_LIST_WORKSPACE_OPTIONS.find((option) => option.value === workspace);
  const unassignedOnPage = response.data.filter((item) => !item.team).length;
  const activeTeams = response.filter_options.teams.filter((team) => team.is_active);

  const selectWorkspace = (next: FieldOperationsListWorkspace) => {
    setWorkspace(next);
  };

  const showUnassigned = () => {
    setDraftUnassigned(true);
    setDraftTeam('');
    setApplied((current) => ({ ...current, team_id: undefined, unassigned: true, page: 1 }));
    setWorkspace('work_orders');
  };

  const showTeam = (teamId: number) => {
    setDraftUnassigned(false);
    setDraftTeam(String(teamId));
    setApplied((current) => ({ ...current, team_id: teamId, unassigned: undefined, page: 1 }));
    setWorkspace('work_orders');
  };

  const workspaceHeader = (
    <View style={styles.header}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Administracija</Text>
      </Pressable>
      <PageHeader title="Terenske operacije" eyebrow="Admin · v1.0" name={bootstrap?.user.name} />
      <Text style={styles.copy}>Radni nalozi, ekipe, termini, servisni delovi i završna terenska dokumentacija u dozvoljenom administratorskom scope-u.</Text>
      <Card style={styles.workspaceCard}>
        <Text style={styles.sectionTitle}>Radni prostor terenskih operacija</Text>
        <Text style={styles.copy}>{workspaceMeta?.description ?? 'Izaberi terenski zadatak koji želiš da obradiš.'}</Text>
        <FilterBar>
          {FIELD_OPERATIONS_LIST_WORKSPACE_OPTIONS.map((option) => (
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

  if (workspace === 'work_orders') {
    return (
      <DataList<AdminFieldWorkSummary>
        data={response.data}
        keyExtractor={(item) => String(item.id)}
        header={(
          <View style={styles.listHeader}>
            {workspaceHeader}
            <View style={styles.sectionHead}>
              <View>
                <Text style={styles.sectionTitle}>Radni nalozi</Text>
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
        emptyTitle="Nema terenskih naloga"
        emptyMessage="Nema rezultata za izabrane server filtere."
        renderItem={(item) => <WorkOrderCard item={item} />}
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
          <TextField label="Pretraga" value={draftQ} onChangeText={setDraftQ} placeholder="Broj naloga, slučaja, porudžbine ili referenca" />
          <SelectSheet label="Status" value={draftStatus} options={statusOptions} onChange={setDraftStatus} />
          <SelectSheet label="Ekipa" value={draftTeam} options={teamOptions(response.filter_options.teams)} onChange={setDraftTeam} />
          <TextField label="Datum od" value={draftDateFrom} onChangeText={setDraftDateFrom} placeholder="YYYY-MM-DD" />
          <TextField label="Datum do" value={draftDateTo} onChangeText={setDraftDateTo} placeholder="YYYY-MM-DD" />
          <FilterBar activeCount={Number(draftUnassigned)} onClear={() => setDraftUnassigned(false)}>
            <FilterChip label="Bez dodeljene ekipe" active={draftUnassigned} onPress={() => setDraftUnassigned((value) => !value)} />
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
            <Button onPress={applyFilters}>Primeni i otvori naloge</Button>
            {activeCount > 0 ? <Button variant="secondary" onPress={clearFilters}>Očisti filtere</Button> : null}
          </View>
        </Card>
      </Screen>
    );
  }

  if (workspace === 'unassigned') {
    return (
      <Screen contentStyle={styles.content}>
        {workspaceHeader}
        <Card style={styles.focusCard}>
          <Text style={styles.sectionTitle}>Nalozi bez dodeljene ekipe</Text>
          <Text style={styles.copy}>
            Ovaj radni prostor koristi postojeći server-side unassigned filter; ne uvodi lokalnu klasifikaciju niti novi workflow kriterijum.
          </Text>
          <Text style={styles.muted}>Bez ekipe na trenutno učitanoj strani: {unassignedOnPage}</Text>
          <View style={styles.actionsRow}>
            <Button onPress={showUnassigned}>Prikaži nedodeljene naloge</Button>
            <Button variant="secondary" onPress={() => setWorkspace('filters')}>Dodatni filteri</Button>
          </View>
        </Card>
      </Screen>
    );
  }

  if (workspace === 'teams') {
    return (
      <Screen contentStyle={styles.content}>
        {workspaceHeader}
        <Card style={styles.focusCard}>
          <View style={styles.sectionHead}>
            <Text style={styles.sectionTitle}>Ekipe</Text>
            <Text style={styles.muted}>{activeTeams.length} aktivnih / {response.filter_options.teams.length} ukupno</Text>
          </View>
          {response.filter_options.teams.length === 0 ? (
            <Text style={styles.copy}>Server trenutno nije vratio nijednu ekipu u filter opcijama.</Text>
          ) : (
            <View style={styles.teamList}>
              {response.filter_options.teams.map((team) => (
                <Card key={team.id} style={styles.teamCard}>
                  <View style={styles.sectionHead}>
                    <View style={styles.grow}>
                      <Text style={styles.subject}>{team.name}</Text>
                      <Text style={styles.meta}>{team.code} · {team.is_active ? 'Aktivna' : 'Neaktivna'}</Text>
                      {team.service_area ? <Text style={styles.meta}>Zona: {team.service_area}</Text> : null}
                    </View>
                    <Button variant="secondary" onPress={() => showTeam(team.id)}>Nalozi</Button>
                  </View>
                </Card>
              ))}
            </View>
          )}
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
          <Text style={styles.summaryLabel}>Ukupno naloga</Text>
        </Card>
        <Card style={styles.summaryCard}>
          <Text style={styles.summaryValue}>{response.data.length}</Text>
          <Text style={styles.summaryLabel}>Na trenutnoj strani</Text>
        </Card>
        <Card style={styles.summaryCard}>
          <Text style={styles.summaryValue}>{unassignedOnPage}</Text>
          <Text style={styles.summaryLabel}>Bez ekipe na strani</Text>
        </Card>
        <Card style={styles.summaryCard}>
          <Text style={styles.summaryValue}>{activeTeams.length}</Text>
          <Text style={styles.summaryLabel}>Aktivnih ekipa</Text>
        </Card>
      </View>
      <Card style={styles.focusCard}>
        <Text style={styles.sectionTitle}>Brze akcije</Text>
        <View style={styles.actionsRow}>
          <Button onPress={() => setWorkspace('work_orders')}>Otvori radne naloge</Button>
          <Button variant="secondary" onPress={() => setWorkspace('filters')}>Filteri</Button>
          <Button variant="secondary" onPress={() => setWorkspace('unassigned')}>Bez ekipe</Button>
          <Button variant="secondary" onPress={() => setWorkspace('teams')}>Ekipe</Button>
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
    teamList: { gap: spacing.sm },
    teamCard: { gap: spacing.sm },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    copy: { ...typography.body, color: theme.muted },
    filtersCard: { gap: spacing.md },
    sectionHead: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
    sectionTitle: { ...typography.h3, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    list: { gap: spacing.md },
    workCard: { gap: spacing.sm },
    cardHead: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
    grow: { flex: 1, minWidth: 0 },
    number: { ...typography.small, color: theme.primary, fontWeight: '800' },
    subject: { ...typography.h3, color: theme.ink, marginTop: spacing.xs },
    meta: { ...typography.small, color: theme.muted },
    pagination: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
    page: { ...typography.label, color: theme.ink },
    pressed: { opacity: 0.72 },
  });
}
