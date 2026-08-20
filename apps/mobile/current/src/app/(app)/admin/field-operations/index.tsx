import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
import { Pill, type PillTone } from '@/components/ui/pill';
import { SelectSheet } from '@/components/ui/select-sheet';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
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

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Administracija</Text>
      </Pressable>
      <PageHeader title="Terenske operacije" eyebrow="Admin · v0.7" name={bootstrap?.user.name} />
      <Text style={styles.copy}>Radni nalozi, ekipe, termini, servisni delovi i završna terenska dokumentacija u dozvoljenom administratorskom scope-u.</Text>

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
        <Button onPress={applyFilters}>Primeni filtere</Button>
        {activeCount > 0 ? <Button variant="secondary" onPress={clearFilters}>Očisti filtere</Button> : null}
      </Card>

      <View style={styles.sectionHead}>
        <View>
          <Text style={styles.sectionTitle}>Radni nalozi</Text>
          <Text style={styles.muted}>{response.meta.total} ukupno</Text>
        </View>
        <Button variant="secondary" onPress={() => void query.refetch()}>
          {query.isFetching ? 'Osvežavanje…' : 'Osveži'}
        </Button>
      </View>

      {response.data.length === 0 ? (
        <EmptyState title="Nema terenskih naloga" message="Nema rezultata za izabrane filtere." />
      ) : (
        <View style={styles.list}>
          {response.data.map((item) => <WorkOrderCard item={item} key={item.id} />)}
        </View>
      )}

      <View style={styles.pagination}>
        <Button
          variant="secondary"
          onPress={() => setApplied((current) => ({ ...current, page: Math.max(1, response.meta.current_page - 1) }))}
        >
          Prethodna
        </Button>
        <Text style={styles.page}>{response.meta.current_page} / {response.meta.last_page}</Text>
        <Button
          variant="secondary"
          onPress={() => setApplied((current) => ({ ...current, page: response.meta.current_page + 1 }))}
        >
          Sledeća
        </Button>
      </View>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: 120 },
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
