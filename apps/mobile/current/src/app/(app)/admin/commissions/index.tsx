import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { PageHeader } from '@/components/layout/page-header';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmAction } from '@/components/ui/confirm-action';
import { DataList } from '@/components/ui/data-list';
import { DateTimeField } from '@/components/ui/date-time-field';
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminCommissions,
  type AdminCommission,
  type AdminCommissionListParams,
  type AdminCommissionPaymentMethod,
  type AdminCommissionStatus,
} from '@/features/admin/commissions-admin-api';
import { openAdminCommissionExport } from '@/features/admin/commissions-admin-export';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { formatMoney } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';

const STATUS_OPTIONS: Array<{ value: AdminCommissionStatus; label: string }> = [
  { value: 'pending', label: 'Na čekanju' },
  { value: 'approved', label: 'Odobrena' },
  { value: 'paid', label: 'Isplaćena' },
  { value: 'cancelled', label: 'Stornirana' },
];

// MOBILE_V1_0_ADMIN_COMMISSIONS_LIST_UX_REORGANIZATION_BATCH91
type CommissionListWorkspace = 'commissions' | 'filters' | 'bulk' | 'exports';

const COMMISSION_LIST_WORKSPACE_OPTIONS: Array<{ value: CommissionListWorkspace; label: string; description: string }> = [
  { value: 'commissions', label: 'Provizije', description: 'Lista provizija, statusi, odgovorna lica, izbor za isplatu i paginacija.' },
  { value: 'filters', label: 'Filteri', description: 'Pretraga, status, period, korisnik i odgovorno lice na jednom mestu.' },
  { value: 'bulk', label: 'Masovna isplata', description: 'Kontrolisana isplata samo odobrenih i server-eligible provizija.' },
  { value: 'exports', label: 'Izvoz', description: 'Postojeci autorizovani CSV i PDF izvoz kroz secure admin flow.' },
];

function positiveId(value: string): number | undefined {
  const parsed = Number(value);
  return Number.isInteger(parsed) && parsed > 0 ? parsed : undefined;
}

function errorMessage(error: unknown, fallback: string): string {
  return error instanceof Error && error.message.trim() ? error.message : fallback;
}

export default function AdminCommissionsIndexScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const allowed = can('commissions.manage');

  const [page, setPage] = useState(1);
  const [draftQ, setDraftQ] = useState('');
  const [draftFrom, setDraftFrom] = useState('');
  const [draftTo, setDraftTo] = useState('');
  const [appliedQ, setAppliedQ] = useState('');
  const [appliedFrom, setAppliedFrom] = useState('');
  const [appliedTo, setAppliedTo] = useState('');
  const [status, setStatus] = useState<AdminCommissionStatus | undefined> (undefined);
  const [userId, setUserId] = useState('');
  const [supplierId, setSupplierId] = useState('');
  const [selectedIds, setSelectedIds] = useState<number[]> ([]);
  const [bulkMethod, setBulkMethod] = useState<AdminCommissionPaymentMethod | ''> ('');
  const [bulkReference, setBulkReference] = useState('');
  const [bulkNote, setBulkNote] = useState('');
  const [confirmBulk, setConfirmBulk] = useState(false);
  const [exporting, setExporting] = useState<'csv' | 'pdf' | null> (null);
  const [workspace, setWorkspace] = useState<CommissionListWorkspace> ('commissions');

  const params = useMemo(() => {
    const value: AdminCommissionListParams = { page, per_page: 40 };
    if (appliedQ) value.q = appliedQ;
    if (status) value.status = status;
    const selectedUser = positiveId(userId);
    const selectedSupplier = positiveId(supplierId);
    if (selectedUser) value.user_id = selectedUser;
    if (selectedSupplier) value.supplier_user_id = selectedSupplier;
    if (appliedFrom) value.date_from = appliedFrom;
    if (appliedTo) value.date_to = appliedTo;
    return value;
  }, [appliedFrom, appliedQ, appliedTo, page, status, supplierId, userId]);

  const query = useQuery({
    queryKey: adminQueryKeys.commissionsList(params),
    queryFn: () => apiAdminCommissions.list(params),
    enabled: allowed,
  });

  const bulkMutation = useMutation({
    mutationFn: apiAdminCommissions.bulkPay,
    onSuccess: async (response) => {
      setSelectedIds([]);
      setBulkMethod('');
      setBulkReference('');
      setBulkNote('');
      await client.invalidateQueries({ queryKey: adminQueryKeys.commissions() });
      feedback.notify({ tone: 'success', title: 'Provizije su isplaćene', message: response.message });
    },
    onError: (error) => {
      feedback.notify({ tone: 'danger', title: 'Masovna isplata nije uspela', message: errorMessage(error, 'Proveri izabrane provizije i pokušaj ponovo.') });
    },
  });

  const applyFilters = () => {
    if (draftFrom && draftTo && draftTo < draftFrom) {
      feedback.notify({ tone: 'danger', title: 'Neispravan period', message: 'Datum „Do“ ne može biti pre datuma „Od“.' });
      return;
    }
    setAppliedQ(draftQ.trim());
    setAppliedFrom(draftFrom.trim());
    setAppliedTo(draftTo.trim());
    setPage(1);
    setSelectedIds([]);
    setWorkspace('commissions');
  };

  const clearFilters = () => {
    setDraftQ(''); setDraftFrom(''); setDraftTo('');
    setAppliedQ(''); setAppliedFrom(''); setAppliedTo('');
    setStatus(undefined); setUserId(''); setSupplierId(''); setPage(1); setSelectedIds([]);
    setWorkspace('commissions');
  };

  const toggleSelected = (id: number) => {
    setSelectedIds((current) => current.includes(id) ? current.filter((item) => item !== id) : [...current, id]);
  };

  const runExport = async (format: 'csv' | 'pdf') => {
    try {
      setExporting(format);
      await openAdminCommissionExport(format, params);
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Izvoz nije uspeo', message: errorMessage(error, 'Pokušaj ponovo.') });
    } finally {
      setExporting(null);
    }
  };

  const confirmBulkPay = () => {
    if (!bulkMethod || selectedIds.length === 0) return;
    const input = { commission_ids: selectedIds, payment_method: bulkMethod } as const;
    const payload: Parameters<typeof apiAdminCommissions.bulkPay>[0] = { ...input };
    if (bulkReference.trim()) payload.payment_reference = bulkReference.trim();
    if (bulkNote.trim()) payload.note = bulkNote.trim();
    bulkMutation.mutate(payload);
  };

  if (!allowed) return <UnavailableState title="Administracija provizija nije dostupna" />;
  if (query.isLoading) return <LoadingState label="Učitavanje administratorskih provizija…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const data = query.data;
  const activeCount = Number(Boolean(appliedQ)) + Number(Boolean(status)) + Number(Boolean(appliedFrom)) + Number(Boolean(appliedTo)) + Number(Boolean(userId)) + Number(Boolean(supplierId));
  const canPrevious = data.meta.current_page > 1;
  const canNext = data.meta.current_page < data.meta.last_page;
  const workspaceMeta = COMMISSION_LIST_WORKSPACE_OPTIONS.find((option) => option.value === workspace);

  const selectWorkspace = (next: CommissionListWorkspace) => {
    setConfirmBulk(false);
    setWorkspace(next);
  };

  const header = (
    <View style={styles.header}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}><Text style={styles.back}>‹ Administracija</Text></Pressable>
      <PageHeader title="Provizije" eyebrow="Admin · Wave A" name={bootstrap?.user.name} />
      <Text style={styles.copy}>Odobravanje, isplata, storniranje i izvoz koriste postojecu CMS poslovnu logiku.</Text>

      <Card style={styles.overviewCard}>
        <Text style={styles.sectionTitle}>Sažetak provizija</Text>
        <View style={styles.summaryGrid}>
          <SummaryCard label="Na čekanju" value={formatMoney(data.summary.pending_eur, 'EUR')} count={data.summary.pending_count} styles={styles} />
          <SummaryCard label="Odobreno" value={formatMoney(data.summary.approved_eur, 'EUR')} count={data.summary.approved_count} styles={styles} />
          <SummaryCard label="Isplaćeno" value={formatMoney(data.summary.paid_eur, 'EUR')} count={data.summary.paid_count} styles={styles} />
          <SummaryCard label="Stornirano" value={formatMoney(data.summary.cancelled_eur, 'EUR')} count={data.summary.cancelled_count} styles={styles} />
        </View>
      </Card>

      <Card style={styles.workspaceCard}>
        <Text style={styles.sectionTitle}>Alati provizija</Text>
        <Text style={styles.copy}>{workspaceMeta?.description ?? 'Izaberi deo provizija koji zelis da obradis.'}</Text>
        <FilterBar>
          {COMMISSION_LIST_WORKSPACE_OPTIONS.filter((option) => (option.value !== 'bulk' || data.capabilities.bulk_pay) && (option.value !== 'exports' || data.capabilities.exports)).map((option) => (
            <FilterChip key={option.value} label={option.label} active={workspace === option.value} onPress={() => selectWorkspace(option.value)} />
          ))}
        </FilterBar>
      </Card>


      {workspace === 'filters' ? (
        <Card style={styles.filtersCard}>
          <Text style={styles.sectionTitle}>Filteri</Text>
          <TextField label="Pretraga" value={draftQ} onChangeText={setDraftQ} placeholder="Porudzbina, korisnik ili e-mail" />
          <FilterBar activeCount={activeCount} onClear={clearFilters}>
            {STATUS_OPTIONS.map((item) => <FilterChip key={item.value} label={item.label} active={status === item.value} onPress={() => { setStatus(status === item.value ? undefined : item.value); setPage(1); }} />)}
          </FilterBar>
          <DateTimeField label="Od datuma" mode="date" value={draftFrom} onChangeText={setDraftFrom} />
          <DateTimeField label="Do datuma" mode="date" value={draftTo} onChangeText={setDraftTo} />
          {data.capabilities.can_filter_people ? (
            <>
              <SelectSheet label="Korisnik" value={userId} options={[{ value: '', label: 'Svi korisnici' }, ...data.filters.users.map((item) => ({ value: String(item.id), label: item.email ? item.label + ' - ' + item.email : item.label }))]} onChange={(value) => { setUserId(value); setPage(1); }} />
              <SelectSheet label="Odgovorno lice" value={supplierId} options={[{ value: '', label: 'Sva odgovorna lica' }, ...data.filters.suppliers.map((item) => ({ value: String(item.id), label: item.email ? item.label + ' - ' + item.email : item.label }))]} onChange={(value) => { setSupplierId(value); setPage(1); }} />
            </>
          ) : null}
          <Button onPress={applyFilters}>Primeni filtere</Button>
        </Card>
      ) : null}

      {workspace === 'bulk' && data.capabilities.bulk_pay ? (
        selectedIds.length > 0 ? (
          <Card style={styles.bulkCard}>
            <Text style={styles.sectionTitle}>Masovna isplata · {selectedIds.length}</Text>
            <SelectSheet label="Nacin isplate" value={bulkMethod} options={data.filters.payment_methods.map((item) => ({ value: item.value, label: item.label }))} onChange={(value) => { if (value === 'bank_transfer' || value === 'cash' || value === 'other') setBulkMethod(value); }} />
            <TextField label="Referenca" value={bulkReference} onChangeText={setBulkReference} placeholder="Broj naloga ili interne evidencije" />
            <TextField label="Napomena" value={bulkNote} onChangeText={setBulkNote} placeholder="Opciona napomena" />
            <View style={styles.actionsRow}>
              <Button disabled={!bulkMethod} loading={bulkMutation.isPending} onPress={() => setConfirmBulk(true)}>Oznaci kao isplacene</Button>
              <Button variant="secondary" onPress={() => selectWorkspace('commissions')}>Promeni izbor</Button>
            </View>
          </Card>
        ) : (
          <Card style={styles.overviewCard}>
            <Text style={styles.sectionTitle}>Nema izabranih provizija</Text>
            <Text style={styles.copy}>U radnom prostoru Provizije izaberi server-eligible odobrene stavke, pa se vrati na Masovnu isplatu.</Text>
            <Button onPress={() => selectWorkspace('commissions')}>Izaberi provizije</Button>
          </Card>
        )
      ) : null}

      {workspace === 'exports' && data.capabilities.exports ? (
        <Card style={styles.overviewCard}>
          <Text style={styles.sectionTitle}>Izvoz provizija</Text>
          <Text style={styles.copy}>CSV i PDF koriste postojeci autorizovani Bearer/cache/share tok i trenutne server filtere.</Text>
          <View style={styles.actionsRow}>
            <Button variant="secondary" loading={exporting === 'csv'} disabled={exporting !== null} onPress={() => void runExport('csv')}>CSV</Button>
            <Button variant="secondary" loading={exporting === 'pdf'} disabled={exporting !== null} onPress={() => void runExport('pdf')}>PDF</Button>
          </View>
        </Card>
      ) : null}

      {workspace === 'commissions' ? (
        <Card style={styles.listIntroCard}>
          <Text style={styles.sectionTitle}>Lista provizija</Text>
          <Text style={styles.copy}>{data.meta.total} ukupno · izabrano za isplatu {selectedIds.length}</Text>
          <View style={styles.actionsRow}>
            <Button variant="secondary" onPress={() => void query.refetch()}>{query.isRefetching ? 'Osvezavanje...' : 'Osvezi'}</Button>
            {selectedIds.length > 0 && data.capabilities.bulk_pay ? <Button onPress={() => selectWorkspace('bulk')}>Nastavi na isplatu</Button> : null}
          </View>
        </Card>
      ) : null}
    </View>
  );

  const footer = workspace === 'commissions' ? (
    <View style={styles.pagination}>
      <Text style={styles.pageMeta}>Strana {data.meta.current_page} / {data.meta.last_page} · {data.meta.total} zapisa</Text>
      <View style={styles.actionsRow}>
        <Button variant="secondary" disabled={!canPrevious} onPress={() => setPage((current) => Math.max(1, current - 1))}>Prethodna</Button>
        <Button variant="secondary" disabled={!canNext} onPress={() => setPage((current) => current + 1)}>Sledeća</Button>
      </View>
    </View>
  ) : null;

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <DataList
        data={workspace === 'commissions' ? data.data : []}
        keyExtractor={(item) => String(item.id)}
        renderItem={(item) => <CommissionCard item={item} selected={selectedIds.includes(item.id)} onToggle={() => toggleSelected(item.id)} onOpen={() => router.push({ pathname: '/admin/commissions/[id]', params: { id: String(item.id) } })} styles={styles} />}
        header={header}
        footer={footer}
        refreshing={query.isRefetching}
        onRefresh={() => void query.refetch()}
        emptyTitle={workspace === 'commissions' ? 'Nema provizija' : 'Radni prostor je spreman'}
        emptyMessage={workspace === 'commissions' ? 'Nema provizija za izabrane filtere.' : workspaceMeta?.description ?? 'Izaberi narednu akciju iz kontrola iznad.'}
      />
      <ConfirmAction visible={confirmBulk} title="Potvrdi masovnu isplatu" message={`Označiti ${selectedIds.length} izabranih odobrenih provizija kao isplaćene?`} confirmLabel="Isplati" busy={bulkMutation.isPending} onCancel={() => setConfirmBulk(false)} onConfirm={() => { setConfirmBulk(false); confirmBulkPay(); }} />
    </SafeAreaView>
  );
}

function SummaryCard({ label, value, count, styles }: { label: string; value: string; count: number; styles: ReturnType<typeof createStyles> }) {
  return <Card style={styles.summaryCard}><Text style={styles.summaryValue}>{value}</Text><Text style={styles.summaryLabel}>{label} · {count}</Text></Card>;
}

function CommissionCard({ item, selected, onToggle, onOpen, styles }: { item: AdminCommission; selected: boolean; onToggle: () => void; onOpen: () => void; styles: ReturnType<typeof createStyles> }) {
  return (
    <Card style={styles.rowCard}>
      <View style={styles.rowHead}><View style={styles.flex}><Pressable accessibilityRole="button" accessibilityLabel={`Otvori porudžbinu ${item.order.order_number}`} onPress={onOpen}><Text style={styles.order}>{item.order.order_number}</Text></Pressable><Text style={styles.amount}>{formatMoney(item.total_eur, 'EUR')}</Text><Text style={styles.meta}>Ukupna provizija</Text></View><Text style={styles.status}>{item.status_label}</Text></View>
      <Text style={styles.meta}>Vrednost porudžbine: {formatMoney(item.order.subtotal_rsd, 'RSD')}</Text>
      <Text style={styles.meta}>{item.user.name}{item.user.email ? ` · ${item.user.email}` : ''}</Text>
      <Text style={styles.meta}>Odgovorno lice: {item.responsible_name}</Text>
      {item.status_note ? <Text style={styles.note}>{item.status_note}</Text> : null}
      <View style={styles.actionsRow}>
        <Button variant="secondary" onPress={onOpen}>Detalj</Button>
        {item.bulk_pay_eligible ? <Button variant="secondary" onPress={onToggle}>{selected ? 'Ukloni iz izbora' : 'Izaberi za isplatu'}</Button> : null}
      </View>
    </Card>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    safe: { flex: 1, backgroundColor: theme.background },
    header: { gap: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    copy: { ...typography.body, color: theme.muted },
    summaryGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    summaryCard: { width: '48%', gap: spacing.xs },
    summaryValue: { ...typography.h3, color: theme.ink },
    summaryLabel: { ...typography.small, color: theme.muted },
    workspaceCard: { gap: spacing.md, borderColor: theme.primary },
    overviewCard: { gap: spacing.md },
    listIntroCard: { gap: spacing.md },
    filtersCard: { gap: spacing.md },
    bulkCard: { gap: spacing.md, borderWidth: 1, borderColor: theme.primary },
    sectionTitle: { ...typography.h3, color: theme.ink },
    actionsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    rowCard: { gap: spacing.sm },
    rowHead: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
    flex: { flex: 1 },
    order: { ...typography.label, color: theme.primary },
    amount: { ...typography.h3, color: theme.ink, marginTop: 2 },
    status: { ...typography.small, color: theme.primary, fontWeight: '800' },
    meta: { ...typography.small, color: theme.muted },
    note: { ...typography.body, color: theme.ink, backgroundColor: theme.surfaceContainer, padding: spacing.sm, borderRadius: 12 },
    pagination: { gap: spacing.md },
    pageMeta: { ...typography.small, color: theme.muted, textAlign: 'center' },
  });
}
