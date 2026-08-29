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
import { SelectSheet } from '@/components/ui/select-sheet';
import {
  ErrorState,
  LoadingState,
  UnavailableState,
} from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminWarranties,
  type AdminWarrantyListParams,
  type AdminWarrantyMaintenanceFilter,
  type AdminWarrantyStatus,
  type AdminWarrantySummary,
} from '@/features/admin/warranties-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { formatDate } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';

const STATUS_OPTIONS: Array<{
  value: AdminWarrantyStatus;
  label: string;
}> = [
  { value: 'active', label: 'Aktivne' },
  { value: 'expired', label: 'Istekle' },
  { value: 'void', label: 'Poništene' },
];

// MOBILE_V1_0_ADMIN_WARRANTIES_LIST_UX_REORGANIZATION_BATCH94
type WarrantyListWorkspace = 'overview' | 'warranties' | 'filters' | 'maintenance' | 'rules';

const WARRANTY_LIST_WORKSPACE_OPTIONS: Array<{ value: WarrantyListWorkspace; label: string }> = [
  { value: 'overview', label: 'Pregled' },
  { value: 'warranties', label: 'Garancije' },
  { value: 'filters', label: 'Filteri' },
  { value: 'maintenance', label: 'Održavanje' },
  { value: 'rules', label: 'Pravila' },
];

function statusTone(
  status: AdminWarrantyStatus,
  theme: AppColors,
): string {
  if (status === 'active') {
    return theme.success;
  }

  if (status === 'void') {
    return theme.danger;
  }

  return theme.warning;
}

export default function AdminWarrantiesIndexScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const allowed = can('warranties.manage');
  const [workspace, setWorkspace] = useState<WarrantyListWorkspace> ('overview');

  const [page, setPage] = useState(1);
  const [draftQ, setDraftQ] = useState('');
  const [appliedQ, setAppliedQ] = useState('');
  const [status, setStatus] = useState<AdminWarrantyStatus | undefined> (undefined);
  const [maintenance, setMaintenance] =
    useState<AdminWarrantyMaintenanceFilter | ''> ('');

  const params = useMemo(() => {
    const value: AdminWarrantyListParams = {
      page,
      per_page: 40,
    };

    if (appliedQ) {
      value.q = appliedQ;
    }

    if (status) {
      value.status = status;
    }

    if (maintenance) {
      value.maintenance = maintenance;
    }

    return value;
  }, [appliedQ, maintenance, page, status]);

  const query = useQuery({
    queryKey: adminQueryKeys.warrantiesList(params),
    queryFn: () => apiAdminWarranties.list(params),
    enabled: allowed,
  });

  const applyFilters = () => {
    setAppliedQ(draftQ.trim());
    setPage(1);
    setWorkspace('warranties');
  };

  const clearFilters = () => {
    setDraftQ('');
    setAppliedQ('');
    setStatus(undefined);
    setMaintenance('');
    setPage(1);
  };

  if (!allowed) {
    return (
      <UnavailableState title="Administracija garancija nije dostupna" />
    );
  }

  if (query.isLoading) {
    return <LoadingState label="Učitavanje garancija…" />;
  }

  if (query.isError || !query.data) {
    return (
      <ErrorState
        error={query.error}
        onRetry={() => void query.refetch()}
      />
    );
  }

  const data = query.data;
  const activeCount =
    Number(Boolean(appliedQ))
    + Number(Boolean(status))
    + Number(Boolean(maintenance));

  const workspaceOptions = WARRANTY_LIST_WORKSPACE_OPTIONS.filter(
    (option) => option.value !== 'rules' || data.capabilities.rules,
  );

  const workspaceHeader = (
    <View style={styles.header}>
      <Pressable
        accessibilityRole="button"
        onPress={() => router.back()}
      >
        <Text style={styles.back}>‹ Administracija</Text>
      </Pressable>

      <PageHeader
        title="Garancije"
        eyebrow="Admin · Garancije"
        name={bootstrap?.user.name}
      />

      <Text style={styles.copy}>
        Garantni listovi i preventivno održavanje koriste postojeći WarrantyService kao poslovni autoritet.
      </Text>

      <Card style={styles.workspaceCard}>
        <Text style={styles.sectionTitle}>Radni prostor garancija</Text>
        <Text style={styles.copy}>
          Razdvoji pregled, listu, filtere, održavanje i pravila bez promene server poslovne logike.
        </Text>
        <FilterBar>
          {workspaceOptions.map((option) => (
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

  const footer = (
    <View style={styles.pagination}>
      <Button
        variant="secondary"
        disabled={data.meta.current_page <= 1}
        onPress={() => {
          setPage((current) => Math.max(1, current - 1));
        }}
      >
        Prethodna
      </Button>

      <Text style={styles.pageLabel}>
        {data.meta.current_page} / {data.meta.last_page}
      </Text>

      <Button
        variant="secondary"
        disabled={data.meta.current_page >= data.meta.last_page}
        onPress={() => {
          setPage((current) => current + 1);
        }}
      >
        Sledeća
      </Button>
    </View>
  );

  if (workspace === 'warranties') {
    return (
      <DataList<AdminWarrantySummary>
        data={data.data}
        keyExtractor={(item) => String(item.id)}
        header={(
          <>
            {workspaceHeader}
            <Card style={styles.listSummaryCard}>
              <Text style={styles.sectionTitle}>Garantni listovi</Text>
              <Text style={styles.copy}>
                {data.meta.total} ukupno · {activeCount} aktivnih filtera
              </Text>
              <View style={styles.actionsRow}>
                <Button variant="secondary" onPress={() => setWorkspace('filters')}>Filteri</Button>
                <Button variant="secondary" onPress={() => void query.refetch()}>
                  {query.isFetching ? 'Osvežavanje…' : 'Osveži'}
                </Button>
              </View>
            </Card>
          </>
        )}
        footer={footer}
        refreshing={query.isFetching}
        onRefresh={() => void query.refetch()}
        emptyTitle="Nema garantnih listova"
        emptyMessage="Nema garancija za izabrane filtere."
        renderItem={(item) => (
          <Pressable
            accessibilityRole="button"
            onPress={() => {
              router.push({
                pathname: '/admin/warranties/[id]',
                params: { id: String(item.id) },
              });
            }}
          >
            <Card style={styles.rowCard}>
              <View style={styles.rowHeader}>
                <View style={styles.rowTitle}>
                  <Text style={styles.warrantyNumber}>
                    {item.warranty_number}
                  </Text>
                  <Text style={styles.productName}>
                    {item.product_name}
                  </Text>
                </View>

                <Text
                  style={[
                    styles.status,
                    {
                      color: statusTone(item.status, theme),
                    },
                  ]}
                >
                  {item.status_label}
                </Text>
              </View>

              <Text style={styles.meta}>
                {item.order.order_number || 'Bez broja porudžbine'}
                {' · '}
                {item.customer_name}
              </Text>

              <Text style={styles.meta}>
                Važi do: {item.expires_at
                  ? formatDate(item.expires_at)
                  : '—'}
              </Text>

              {item.next_maintenance_at ? (
                <Text style={styles.meta}>
                  Sledeće održavanje:{' '}
                  {formatDate(item.next_maintenance_at)}
                </Text>
              ) : null}
            </Card>
          </Pressable>
        )}
      />
    );
  }

  if (workspace === 'filters') {
    return (
      <Screen contentStyle={styles.content}>
        {workspaceHeader}
        <Card style={styles.filtersCard}>
          <View style={styles.actionsRow}>
            <Text style={styles.sectionTitle}>Filteri</Text>
            <Text style={styles.copy}>{activeCount} aktivnih</Text>
          </View>

          <TextField
            label="Pretraga"
            value={draftQ}
            onChangeText={setDraftQ}
            placeholder="Broj garancije, porudžbina, SKU ili naziv"
          />

          <FilterBar
            activeCount={activeCount}
            onClear={clearFilters}
          >
            {STATUS_OPTIONS.map((option) => (
              <FilterChip
                key={option.value}
                label={option.label}
                active={status === option.value}
                onPress={() => {
                  setStatus(
                    status === option.value
                      ? undefined
                      : option.value,
                  );
                  setPage(1);
                }}
              />
            ))}
          </FilterBar>

          <SelectSheet
            label="Preventivno održavanje"
            value={maintenance}
            options={[
              { value: '', label: 'Sve garancije' },
              ...data.filters.maintenance,
            ]}
            onChange={(value) => {
              if (
                value === ''
                || value === 'due'
                || value === 'scheduled'
                || value === 'overdue'
              ) {
                setMaintenance(value);
                setPage(1);
              }
            }}
          />

          <View style={styles.actionsRow}>
            <Button onPress={applyFilters}>Primeni i otvori garancije</Button>
            {activeCount > 0 ? (
              <Button variant="secondary" onPress={clearFilters}>Očisti filtere</Button>
            ) : null}
          </View>
        </Card>
      </Screen>
    );
  }

  if (workspace === 'maintenance') {
    return (
      <Screen contentStyle={styles.content}>
        {workspaceHeader}
        <View style={styles.statsGrid}>
          <StatCard label="Ističu 30 dana" value={data.stats.expiring} styles={styles} />
          <StatCard label="Održavanje uskoro" value={data.stats.maintenance_due} styles={styles} />
          <StatCard label="Aktivne" value={data.stats.active} styles={styles} />
        </View>
        <Card style={styles.workspaceCard}>
          <Text style={styles.sectionTitle}>Preventivno održavanje</Text>
          <Text style={styles.copy}>
            Izaberi postojeći server maintenance filter i odmah otvori odgovarajuće garantne listove.
          </Text>
          <FilterBar activeCount={maintenance ? 1 : 0} onClear={() => setMaintenance('')}>
            {data.filters.maintenance.map((option) => (
              <FilterChip
                key={option.value}
                label={option.label}
                active={maintenance === option.value}
                onPress={() => {
                  const value = option.value;
                  if (value === 'due' || value === 'scheduled' || value === 'overdue') {
                    setMaintenance(value);
                    setPage(1);
                    setWorkspace('warranties');
                  }
                }}
              />
            ))}
          </FilterBar>
          <Button
            variant="secondary"
            onPress={() => {
              setMaintenance('');
              setPage(1);
              setWorkspace('warranties');
            }}
          >
            Sve garancije
          </Button>
        </Card>
      </Screen>
    );
  }

  if (workspace === 'overview') {
    return (
      <Screen contentStyle={styles.content}>
        {workspaceHeader}
        <View style={styles.statsGrid}>
          <StatCard label="Aktivne" value={data.stats.active} styles={styles} />
          <StatCard label="Ističu 30 dana" value={data.stats.expiring} styles={styles} />
          <StatCard label="Održavanje uskoro" value={data.stats.maintenance_due} styles={styles} />
          <StatCard label="Poništene" value={data.stats.void} styles={styles} />
        </View>
        <Card style={styles.workspaceCard}>
          <Text style={styles.sectionTitle}>Brze akcije</Text>
          <View style={styles.actionsRow}>
            <Button onPress={() => setWorkspace('warranties')}>Otvori garancije</Button>
            <Button variant="secondary" onPress={() => setWorkspace('filters')}>Filteri</Button>
            <Button variant="secondary" onPress={() => setWorkspace('maintenance')}>Održavanje</Button>
            {data.capabilities.rules ? (
              <Button variant="secondary" onPress={() => setWorkspace('rules')}>Pravila</Button>
            ) : null}
          </View>
        </Card>
      </Screen>
    );
  }

  return (
    <Screen contentStyle={styles.content}>
      {workspaceHeader}
      <Card style={styles.workspaceCard}>
        <Text style={styles.sectionTitle}>Pravila garancije</Text>
        <Text style={styles.copy}>
          Product, category i global prioritet ostaju na postojećem server-driven Rules ekranu.
        </Text>
        {data.capabilities.rules ? (
          <Button
            variant="secondary"
            onPress={() => router.push('/admin/warranties/rules')}
          >
            Otvori pravila garancije
          </Button>
        ) : (
          <Text style={styles.copy}>Server trenutno ne dozvoljava upravljanje pravilima.</Text>
        )}
      </Card>
    </Screen>
  );
}

function StatCard({
  label,
  value,
  styles,
}: {
  label: string;
  value: number;
  styles: ReturnType<typeof createStyles>;
}) {
  return (
    <Card style={styles.statCard}>
      <Text style={styles.statValue}>{value}</Text>
      <Text style={styles.statLabel}>{label}</Text>
    </Card>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 140, gap: spacing.lg },
    workspaceCard: { gap: spacing.md },
    listSummaryCard: { gap: spacing.sm, marginTop: spacing.lg },
    actionsRow: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center', gap: spacing.sm },
    header: {
      gap: spacing.lg,
    },
    back: {
      ...typography.label,
      color: theme.primary,
      paddingVertical: spacing.sm,
    },
    copy: {
      ...typography.body,
      color: theme.muted,
    },
    statsGrid: {
      flexDirection: 'row',
      flexWrap: 'wrap',
      gap: spacing.sm,
    },
    statCard: {
      minWidth: '47%',
      flexGrow: 1,
      gap: spacing.xs,
    },
    statValue: {
      ...typography.h2,
      color: theme.ink,
    },
    statLabel: {
      ...typography.small,
      color: theme.muted,
    },
    filtersCard: {
      gap: spacing.md,
    },
    sectionTitle: {
      ...typography.h3,
      color: theme.ink,
    },
    rowCard: {
      gap: spacing.sm,
    },
    rowHeader: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      gap: spacing.md,
    },
    rowTitle: {
      flex: 1,
      gap: 2,
    },
    warrantyNumber: {
      ...typography.label,
      color: theme.primary,
    },
    productName: {
      ...typography.body,
      color: theme.ink,
      fontWeight: '700',
    },
    status: {
      ...typography.small,
      fontWeight: '800',
    },
    meta: {
      ...typography.small,
      color: theme.muted,
    },
    pagination: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.sm,
    },
    pageLabel: {
      ...typography.label,
      color: theme.muted,
    },
  });
}
