import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router, type Href } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Pill } from '@/components/ui/pill';
import { Card } from '@/components/ui/card';
import { DateTimeField } from '@/components/ui/date-time-field';
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminOrders,
  type AdminOrderListItem,
  type AdminOrdersPerPage,
  type AdminOrdersRequestParams,
} from '@/features/admin/orders-admin-api';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

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

function formatMoney(value: number): string {
  return `${new Intl.NumberFormat('sr-RS', { maximumFractionDigits: 2 }).format(value)} RSD`;
}

function statusLabel(value: string, completed = false): string {
  if (completed || value === 'completed') return 'Kompletirana';
  return ({
    new: 'Nova',
    processing: 'U obradi',
    confirmed: 'Potvrdjena',
    shipped: 'Poslata',
    cancelled: 'Otkazana',
  } as Record<string, string>)[value] ?? value;
}

function paymentLabel(value: string | null): string {
  if (!value) return 'Nije dostupno';
  return ({ pending: 'Na cekanju', paid: 'Placeno', cancelled: 'Otkazano' } as Record<string, string>)[value] ?? value;
}

function sourceLabel(value: string): string {
  return value === 'laravel' ? 'Laravel' : value === 'legacy' ? 'Legacy' : value;
}

function attentionLabel(value: string): string {
  return ({
    unaccepted: 'Nepreuzete',
    overdue: 'Probili rok',
    due: 'Rok uskoro',
  } as Record<string, string>)[value] ?? value;
}

// MOBILE_V1_0_ADMIN_ORDERS_LIST_UX_REORGANIZATION_BATCH93
type OrdersListWorkspace = 'overview' | 'orders' | 'filters' | 'attention' | 'archive';

const ORDERS_LIST_WORKSPACE_OPTIONS: { value: OrdersListWorkspace; label: string }[] = [
  { value: 'overview', label: 'Pregled' },
  { value: 'orders', label: 'Porudžbine' },
  { value: 'filters', label: 'Filteri' },
  { value: 'attention', label: 'Pažnja' },
  { value: 'archive', label: 'Arhiva' },
];

export default function AdminOrdersIndexScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const feedback = useAppFeedback();
  const allowed = can('orders.manage');

  const [draftQ, setDraftQ] = useState('');
  const [draftStatus, setDraftStatus] = useState('');
  const [draftPayment, setDraftPayment] = useState('');
  const [draftSource, setDraftSource] = useState('');
  const [draftSupplier, setDraftSupplier] = useState('');
  const [draftFrom, setDraftFrom] = useState('');
  const [draftTo, setDraftTo] = useState('');
  const [draftAttention, setDraftAttention] = useState('');
  const [draftPerPage, setDraftPerPage] = useState<AdminOrdersPerPage> (40);
  const [applied, setApplied] = useState<AdminOrdersRequestParams> ({ page: 1, per_page: 40 });
  const [workspace, setWorkspace] = useState<OrdersListWorkspace> ('overview');

  const query = useQuery({
    queryKey: adminQueryKeys.adminOrders(applied),
    queryFn: () => apiAdminOrders.list(applied),
    enabled: allowed,
  });

  const activeCount = Number(Boolean(draftQ.trim()))
    + Number(Boolean(draftStatus))
    + Number(Boolean(draftPayment))
    + Number(Boolean(draftSource))
    + Number(Boolean(draftSupplier))
    + Number(Boolean(draftFrom))
    + Number(Boolean(draftTo))
    + Number(Boolean(draftAttention))
    + Number(draftPerPage !== 40);

  const applyFilters = () => {
    if (draftFrom && draftTo && draftTo < draftFrom) {
      feedback.notify({
        tone: 'danger',
        title: 'Neispravan period',
        message: 'Datum Do ne moze biti pre datuma Od.',
      });
      return;
    }

    const next: AdminOrdersRequestParams = { page: 1, per_page: draftPerPage };
    const q = trimmed(draftQ);
    if (q) next.q = q;
    if (draftStatus) next.status = draftStatus;
    if (draftPayment) next.payment_status = draftPayment;
    if (draftSource) next.source_system = draftSource;
    const supplierId = Number(draftSupplier);
    if (draftSupplier && Number.isInteger(supplierId) && supplierId > 0) {
      next.supplier_user_id = supplierId;
    }
    if (draftFrom) next.date_from = draftFrom;
    if (draftTo) next.date_to = draftTo;
    if (draftAttention) next.attention = draftAttention;
    setApplied(next);
    setWorkspace('orders');
  };

  const clearFilters = () => {
    setDraftQ('');
    setDraftStatus('');
    setDraftPayment('');
    setDraftSource('');
    setDraftSupplier('');
    setDraftFrom('');
    setDraftTo('');
    setDraftAttention('');
    setDraftPerPage(40);
    setApplied({ page: 1, per_page: 40 });
    setWorkspace('orders');
  };

  const setPage = (page: number) => {
    if (page < 1) return;
    setApplied((current) => ({ ...current, page }));
  };

  if (!allowed) {
    return <UnavailableState title="Administracija porudzbina nije dostupna" />;
  }

  if (query.isLoading) {
    return <LoadingState label="Ucitavanje administratorskih porudzbina..." />;
  }

  if (query.isError || !query.data) {
    return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  }

  const response = query.data;
  const statusOptions = [
    { value: '', label: 'Svi statusi' },
    ...response.filter_options.statuses.map((status) => ({ value: status, label: statusLabel(status) })),
  ];
  const paymentOptions = [
    { value: '', label: 'Sva placanja' },
    ...response.filter_options.payment_statuses.map((status) => ({ value: status, label: paymentLabel(status) })),
  ];
  const sourceOptions = [
    { value: '', label: 'Svi izvori' },
    ...response.filter_options.source_systems.map((source) => ({ value: source, label: sourceLabel(source) })),
  ];
  const supplierOptions = [
    { value: '', label: 'Sva odgovorna lica' },
    ...response.filter_options.suppliers.map((supplier) => ({
      value: String(supplier.id ?? ''),
      label: supplier.username ? `${supplier.name} (${supplier.username})` : supplier.name,
    })),
  ];
  const perPageOptions = response.filter_options.per_page.map((value) => ({
    value: String(value),
    label: `${value} po strani`,
  }));

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Administracija</Text>
      </Pressable>

      <PageHeader
        title="Porudžbine"
        eyebrow="Admin · Prodaja"
        name={bootstrap?.user.name}
      />

      <Card style={styles.workspaceCard}>
        <View style={styles.sectionHead}>
          <Text style={styles.sectionTitle}>Radni prostor porudžbina</Text>
          <Text style={styles.muted}>{response.pagination.total} ukupno</Text>
        </View>
        <FilterBar>
          {ORDERS_LIST_WORKSPACE_OPTIONS.map((option) => (
            <FilterChip
              key={option.value}
              label={option.label}
              active={workspace === option.value}
              onPress={() => setWorkspace(option.value)}
            />
          ))}
        </FilterBar>
      </Card>

      {workspace === 'overview' ? (
        <Card style={styles.overviewCard}>
          <View style={styles.sectionHead}>
            <Text style={styles.sectionTitle}>Pregled</Text>
            <Text style={styles.muted}>Strana {response.pagination.current_page} / {Math.max(response.pagination.last_page, 1)}</Text>
          </View>
          <View style={styles.summaryGrid}>
            <View style={styles.summaryTile}>
              <Text style={styles.muted}>Ukupno porudžbina</Text>
              <Text style={styles.summaryValue}>{response.pagination.total}</Text>
            </View>
            <View style={styles.summaryTile}>
              <Text style={styles.muted}>Nepreuzete</Text>
              <Text style={styles.summaryValue}>{response.attention.unaccepted ?? 0}</Text>
            </View>
            <View style={styles.summaryTile}>
              <Text style={styles.muted}>Probili rok</Text>
              <Text style={styles.summaryValue}>{response.attention.overdue ?? 0}</Text>
            </View>
            <View style={styles.summaryTile}>
              <Text style={styles.muted}>Na strani</Text>
              <Text style={styles.summaryValue}>{response.data.length}</Text>
            </View>
          </View>
          <Button onPress={() => setWorkspace('orders')}>Otvori porudžbine</Button>
        </Card>
      ) : null}

      {workspace === 'attention' ? (
        <Card style={styles.attentionCard}>
          <Text style={styles.sectionTitle}>Potrebna pažnja</Text>
          <Text style={styles.copy}>Izaberi postojeći server attention filter, zatim otvori odgovarajuće porudžbine.</Text>
          {response.filter_options.attention.length > 0 ? (
            <>
              <FilterBar activeCount={draftAttention ? 1 : 0} onClear={() => setDraftAttention('')}>
                {response.filter_options.attention.map((key) => (
                  <FilterChip
                    key={key}
                    label={`${attentionLabel(key)} (${response.attention[key] ?? 0})`}
                    active={draftAttention === key}
                    onPress={() => setDraftAttention(draftAttention === key ? '' : key)}
                  />
                ))}
              </FilterBar>
              <Button onPress={applyFilters}>Prikaži porudžbine</Button>
            </>
          ) : <Text style={styles.copy}>Nema stavki za pregled.</Text>}
        </Card>
      ) : null}

      {workspace === 'filters' ? (
        <Card style={styles.filtersCard}>
          <View style={styles.sectionHead}>
            <Text style={styles.sectionTitle}>Filteri</Text>
            <Text style={styles.muted}>{activeCount} aktivnih</Text>
          </View>

          <TextField
            label="Pretraga"
            value={draftQ}
            onChangeText={setDraftQ}
            placeholder="Broj porudzbine, kupac, username ili email"
          />
          <SelectSheet label="Status" value={draftStatus} options={statusOptions} onChange={setDraftStatus} />
          <SelectSheet label="Placanje" value={draftPayment} options={paymentOptions} onChange={setDraftPayment} />
          <SelectSheet label="Izvor" value={draftSource} options={sourceOptions} onChange={setDraftSource} />
          {response.filter_options.suppliers.length > 0 ? (
            <SelectSheet
              label="Odgovorno lice"
              value={draftSupplier}
              options={supplierOptions}
              onChange={setDraftSupplier}
            />
          ) : null}
          <DateTimeField label="Od datuma" mode="date" value={draftFrom} onChangeText={setDraftFrom} />
          <DateTimeField label="Do datuma" mode="date" value={draftTo} onChangeText={setDraftTo} />
          <SelectSheet
            label="Broj po strani"
            value={String(draftPerPage)}
            options={perPageOptions}
            onChange={(value) => {
              const next = Number(value);
              if (next === 20 || next === 40 || next === 50 || next === 100) {
                setDraftPerPage(next);
              }
            }}
          />
          <Button onPress={applyFilters}>Primeni filtere</Button>
          {activeCount > 0 ? <Button variant="secondary" onPress={clearFilters}>Ocisti filtere</Button> : null}
        </Card>
      ) : null}

      {workspace === 'orders' ? (
        <>
          <View style={styles.sectionHead}>
            <View>
              <Text style={styles.sectionTitle}>Porudžbine</Text>
              <Text style={styles.muted}>{response.pagination.total} ukupno</Text>
            </View>
            <Button variant="secondary" onPress={() => void query.refetch()}>
              {query.isFetching ? 'Osvežavanje...' : 'Osveži'}
            </Button>
          </View>

          {response.data.length === 0 ? (
            <Card style={styles.card}>
              <Text style={styles.muted}>Nema porudžbina za izabrane filtere.</Text>
            </Card>
          ) : (
            response.data.map((item) => (
              <OrderCard
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
                Sledeća
              </Button>
            </View>
          </Card>
        </>
      ) : null}

      {workspace === 'archive' ? (
        <Card style={styles.readOnlyCard}>
          <Text style={styles.sectionTitle}>Arhiva porudžbina</Text>
          <Button variant="secondary" onPress={() => router.push('/admin/orders/archived' as Href)}>
            Otvori arhivirane porudžbine
          </Button>
        </Card>
      ) : null}
    </Screen>
  );
}

function OrderCard({
  item,
  canOpen,
  styles,
}: {
  item: AdminOrderListItem;
  canOpen: boolean;
  styles: ReturnType<typeof createStyles>;
}) {
  const status = statusLabel(item.status, item.is_completed);
  const statusTone = item.is_completed || item.status === 'completed'
    ? 'success' : item.status === 'cancelled' ? 'danger' : item.status === 'new' ? 'warning' : 'primary';
  const name = item.primary_item_name?.trim() || 'Naziv artikla nije evidentiran';
  const extra = Math.max(0, item.additional_items_count ?? 0);
  const content = (
    <Card style={styles.card}>
      <View style={styles.rowBetween}>
        <Text style={styles.cardTitle} numberOfLines={2}>{item.order_number || 'Porudžbina #' + item.id}</Text>
        <Pill tone={statusTone}>{status}</Pill>
      </View>
      <Text style={styles.body}>{item.customer.name || 'Kupac nije dostupan'}</Text>
      <View style={styles.productBlock}>
        <Text style={styles.productLabel}>Artikal</Text>
        <Text style={styles.productName}>{name}{extra > 0 ? ' +' + extra + (extra === 1 ? ' stavka' : ' stavke') : ''}</Text>
      </View>
      <View style={styles.metaGrid}>
        <Text style={styles.meta}>Plaćanje: {paymentLabel(item.payment_status)}</Text>
        <Text style={styles.meta}>Kreirana: {formatDateTime(item.created_at)}</Text>
        {item.supplier?.name ? <Text style={styles.meta}>Odgovorno lice: {item.supplier.name}</Text> : null}
      </View>
      <Text style={styles.total}>{formatMoney(item.subtotal_rsd)}</Text>
      {item.tracking_number ? <Text style={styles.muted}>Praćenje: {item.tracking_number}</Text> : null}
    </Card>
  );

  if (!canOpen) return content;

  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={'Otvori porudžbinu ' + item.order_number + '; artikal ' + name}
      onPress={() => router.push({
        pathname: '/admin/orders/[id]',
        params: { id: String(item.id) },
      })}
    >
      {content}
    </Pressable>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 220, gap: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    copy: { ...typography.body, color: theme.muted },
    workspaceCard: { gap: spacing.md, borderColor: theme.primary },
    overviewCard: { gap: spacing.md },
    summaryGrid: { flexDirection: 'row', flexWrap: 'wrap', columnGap: spacing.sm, rowGap: spacing.sm },
    summaryTile: { flexGrow: 1, flexBasis: '46%', minWidth: 0, padding: spacing.md, borderRadius: 12, backgroundColor: theme.surfaceMuted, gap: spacing.xs },
    summaryValue: { ...typography.h2, color: theme.ink, fontVariant: ['tabular-nums'] },
    attentionCard: { gap: spacing.md },
    filtersCard: { gap: spacing.md },
    sectionHead: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    sectionTitle: { ...typography.h3, color: theme.ink },
    card: { gap: spacing.sm },
    cardTitle: { ...typography.label, color: theme.ink, flex: 1, minWidth: 0, flexShrink: 1 },
    body: { ...typography.body, color: theme.ink, fontWeight: '700' },
    productBlock: { gap: 2, paddingVertical: spacing.sm, borderTopWidth: StyleSheet.hairlineWidth, borderTopColor: theme.line },
    productLabel: { ...typography.small, color: theme.muted },
    productName: { ...typography.body, color: theme.ink, fontWeight: '600', flexShrink: 1, minWidth: 0 },
    muted: { ...typography.small, color: theme.muted },
    status: { ...typography.label, color: theme.primary },
    metaGrid: { gap: 4 },
    meta: { ...typography.small, color: theme.muted },
    total: { ...typography.h3, color: theme.ink },
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
