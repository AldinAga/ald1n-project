import { useMemo, useState, type Dispatch, type SetStateAction } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
import { Pill } from '@/components/ui/pill';
import { SelectSheet } from '@/components/ui/select-sheet';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminServiceParts,
  type AdminServicePart,
  type AdminServicePartListParams,
  type AdminServicePartWrite,
} from '@/features/admin/service-parts-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

type PartDraft = {
  sku: string;
  name: string;
  unit: string;
  stock: string;
  minimum: string;
  averageCost: string;
  active: boolean;
  notes: string;
};

const emptyDraft = (): PartDraft => ({ sku: '', name: '', unit: 'kom', stock: '0', minimum: '0', averageCost: '0', active: true, notes: '' });

function decimal(value: string, label: string, allowNegative = false): number {
  const normalized = value.trim().replace(',', '.');
  const parsed = Number(normalized);
  if (!Number.isFinite(parsed) || (!allowNegative && parsed < 0)) throw new Error(`${label} nije validan broj.`);
  return parsed;
}

function compact(value: string): string | null {
  const normalized = value.trim();
  return normalized ? normalized : null;
}

function quantity(value: number | null): string {
  if (value === null) return '—';
  return Number(value).toLocaleString('sr-RS', { maximumFractionDigits: 3 });
}

function money(value: number | null): string {
  if (value === null) return '—';
  return `${Number(value).toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} RSD`;
}

function fromPart(part: AdminServicePart): PartDraft {
  return {
    sku: part.sku,
    name: part.name,
    unit: part.unit ?? 'kom',
    stock: String(part.stock_quantity ?? 0),
    minimum: String(part.minimum_quantity ?? 0),
    averageCost: String(part.average_cost_rsd ?? 0),
    active: part.is_active,
    notes: part.notes ?? '',
  };
}

// MOBILE_V1_0_ADMIN_SERVICE_PARTS_UX_REORGANIZATION_BATCH87
type ServicePartsWorkspace = 'overview' | 'parts' | 'create' | 'adjust' | 'procurement';

const SERVICE_PARTS_WORKSPACE_OPTIONS: Array<{ value: ServicePartsWorkspace; label: string; description: string }> = [
  { value: 'overview', label: 'Pregled', description: 'Brzi pregled servisnog lagera i dostupnih ovlašćenja.' },
  { value: 'parts', label: 'Delovi', description: 'Pretraga, filteri, stanje, rezervacije i metadata servisnih delova.' },
  { value: 'create', label: 'Novi deo', description: 'Kreiranje servisnog dela sa početnim stanjem kroz postojeći movement ledger.' },
  { value: 'adjust', label: 'Korekcije', description: 'Kontrolisana promena fizičkog stanja uz razlog i postojeći idempotency ključ.' },
  { value: 'procurement', label: 'Nabavka', description: 'Dobavljači i zahtevi za nabavku kroz postojeće procurement ekrane.' },
];

export default function AdminServicePartsScreen() {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { bootstrap, can } = useAuth();
  const canView = can('service_parts.view');
  const canManage = can('service_parts.manage');
  const canProcurement = can('service_parts.procurement');
  const allowed = canView || canManage || canProcurement;

  const [draftQ, setDraftQ] = useState('');
  const [draftActive, setDraftActive] = useState<'' | 'true' | 'false'> ('');
  const [applied, setApplied] = useState<AdminServicePartListParams> ({ page: 1, per_page: 40 });
  const [workspace, setWorkspace] = useState<ServicePartsWorkspace> ('overview');
  const [createDraft, setCreateDraft] = useState<PartDraft> (emptyDraft);
  const [editing, setEditing] = useState<AdminServicePart | null> (null);
  const [editDraft, setEditDraft] = useState<PartDraft> (emptyDraft);
  const [adjusting, setAdjusting] = useState<AdminServicePart | null> (null);
  const [adjustQty, setAdjustQty] = useState('');
  const [adjustNote, setAdjustNote] = useState('');
  const [adjustKey, setAdjustKey] = useState('');

  const query = useQuery({
    queryKey: adminQueryKeys.servicePartsList(applied),
    queryFn: () => apiAdminServiceParts.partsList(applied),
    enabled: canView,
  });
  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });

  if (!allowed) return <UnavailableState title="Servisni lager nije dostupan" />;
  if (canView && query.isLoading) return <LoadingState label="Učitavanje servisnog lagera…" />;
  if (canView && (query.isError || !query.data)) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const response = query.data;
  const updateDraft = (setter: Dispatch<SetStateAction<PartDraft>>, key: keyof PartDraft, value: string | boolean) => {
    setter((current) => ({ ...current, [key]: value }));
  };

  const writeInput = (draft: PartDraft, includeOpeningStock: boolean): AdminServicePartWrite => {
    if (!draft.sku.trim()) throw new Error('SKU je obavezan.');
    if (!draft.name.trim()) throw new Error('Naziv je obavezan.');
    if (!draft.unit.trim()) throw new Error('Jedinica mere je obavezna.');
    return {
      sku: draft.sku.trim(),
      name: draft.name.trim(),
      unit: draft.unit.trim(),
      ...(includeOpeningStock ? { stock_quantity: decimal(draft.stock, 'Početno stanje') } : {}),
      minimum_quantity: decimal(draft.minimum, 'Minimalna količina'),
      average_cost_rsd: decimal(draft.averageCost, 'Prosečna nabavna cena'),
      is_active: draft.active,
      notes: compact(draft.notes),
    };
  };

  const refreshRoot = async () => {
    await client.invalidateQueries({ queryKey: adminQueryKeys.serviceParts() });
  };

  const createPart = async () => {
    try {
      const input = writeInput(createDraft, true);
      await mutation.mutateAsync(() => apiAdminServiceParts.partCreate(input));
      await refreshRoot();
      setCreateDraft(emptyDraft());
      setWorkspace(canView ? 'parts' : 'overview');
      feedback.notify({ tone: 'success', title: 'Servisni deo je kreiran', message: 'Početno stanje je evidentirano kroz postojeći movement ledger.' });
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Servisni deo nije kreiran', message: `${error instanceof Error ? error.message : 'Pokušaj ponovo.'} Uneti podaci ostaju u formi.` });
    }
  };

  const openEdit = (part: AdminServicePart) => {
    setWorkspace('parts');
    setEditing(part);
    setEditDraft(fromPart(part));
  };

  const updatePart = async () => {
    if (!editing) return;
    try {
      const input = writeInput(editDraft, false);
      await mutation.mutateAsync(() => apiAdminServiceParts.partUpdate(editing.id, input));
      await refreshRoot();
      setEditing(null);
      feedback.notify({ tone: 'success', title: 'Servisni deo je izmenjen', message: 'Stanje lagera nije menjano kroz metadata formu.' });
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Izmena nije sačuvana', message: `${error instanceof Error ? error.message : 'Pokušaj ponovo.'} Uneti podaci ostaju u formi.` });
    }
  };

  const openAdjust = (part: AdminServicePart) => {
    setWorkspace('adjust');
    setAdjusting(part);
    setAdjustQty('');
    setAdjustNote('');
    setAdjustKey(`service-part-mobile-${part.id}-${Date.now()}-${Math.random().toString(36).slice(2, 10)}`);
  };

  const adjustPart = async () => {
    if (!adjusting) return;
    try {
      const quantityChange = decimal(adjustQty, 'Promena količine', true);
      if (quantityChange === 0) throw new Error('Promena količine ne može biti nula.');
      if (adjustNote.trim().length < 5) throw new Error('Napomena mora imati najmanje 5 znakova.');
      if (adjustKey.length < 16) throw new Error('Idempotency ključ nije validan.');
      await mutation.mutateAsync(() => apiAdminServiceParts.partAdjust(adjusting.id, {
        quantity_change: quantityChange,
        note: adjustNote.trim(),
        idempotency_key: adjustKey,
      }));
      await refreshRoot();
      setAdjusting(null);
      setWorkspace(canView ? 'parts' : 'overview');
      feedback.notify({ tone: 'success', title: 'Korekcija lagera je evidentirana', message: 'Movement ledger i idempotency zaštita ostaju aktivni.' });
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Korekcija nije evidentirana', message: `${error instanceof Error ? error.message : 'Pokušaj ponovo.'} Isti idempotency ključ ostaje za bezbedan retry.` });
    }
  };

  const applyFilters = () => setApplied({
    q: draftQ.trim() || undefined,
    active: draftActive === '' ? undefined : draftActive === 'true',
    page: 1,
    per_page: 40,
  });

  const workspaceMeta = SERVICE_PARTS_WORKSPACE_OPTIONS.find((option) => option.value === workspace);
  const visibleWorkspaces = SERVICE_PARTS_WORKSPACE_OPTIONS.filter((option) => {
    if (option.value === 'parts') return canView;
    if (option.value === 'create') return canManage;
    if (option.value === 'adjust') return canManage && canView;
    if (option.value === 'procurement') return canProcurement;
    return true;
  });

  const selectWorkspace = (next: ServicePartsWorkspace) => {
    setWorkspace(next);
    if (next !== 'parts') setEditing(null);
    if (next !== 'adjust') setAdjusting(null);
  };

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}><Text style={styles.back}>‹ Administracija</Text></Pressable>
      <PageHeader title="Servisni lager" eyebrow="Admin · v0.7" name={bootstrap?.user.name} />
      <Text style={styles.copy}>Servisni delovi, rezervisana količina, korekcije lagera, dobavljači i nabavke. Terenski utrošak ostaje u postojećem Field Operations toku.</Text>

      <Card style={styles.workspaceCard}>
        <Text style={styles.sectionTitle}>Radni prostor servisnog lagera</Text>
        <Text style={styles.meta}>{workspaceMeta?.description ?? 'Izaberi deo servisnog lagera koji želiš da obradiš.'}</Text>
        <FilterBar>
          {visibleWorkspaces.map((option) => (
            <FilterChip
              key={option.value}
              label={option.label}
              active={workspace === option.value}
              onPress={() => selectWorkspace(option.value)}
            />
          ))}
        </FilterBar>
      </Card>

      {workspace === 'overview' ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Pregled servisnog lagera</Text>
          <Text style={styles.copy}>Ukupno servisnih delova: {canView && response ? response.meta.total : 'pregled nije dostupan'}</Text>
          <Text style={styles.meta}>Pregled lagera: {canView ? 'dostupan' : 'nije dostupan'} · Upravljanje delovima: {canManage ? 'dostupno' : 'nije dostupno'} · Nabavka: {canProcurement ? 'dostupna' : 'nije dostupna'}</Text>
          <Text style={styles.meta}>Fizički utrošak rezervisanih delova ostaje u postojećem Field Operations toku.</Text>
        </Card>
      ) : null}

      {workspace === 'procurement' && canProcurement ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Nabavka servisnih delova</Text>
          <Text style={styles.meta}>Dobavljači i zahtevi za nabavku ostaju na postojećim canonical ekranima i koriste isti procurement API.</Text>
          <View style={styles.actions}>
            <Button variant="secondary" onPress={() => router.push('/admin/service-parts/suppliers')}>Dobavljači</Button>
            <Button onPress={() => router.push('/admin/service-parts/purchases')}>Nabavke</Button>
          </View>
        </Card>
      ) : null}

      {workspace === 'parts' && !canView ? (
        <Card style={styles.card}><Text style={styles.sectionTitle}>Pregled lagera nije dostupan</Text><Text style={styles.copy}>Tvoja uloga nema service_parts.view. Dostupne su samo akcije za koje poseduješ posebnu dozvolu.</Text></Card>
      ) : null}

      {workspace === 'create' && canManage ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Novi servisni deo</Text>
          <Text style={styles.meta}>Početno stanje se upisuje samo pri kreiranju i ulazi u movement ledger. Kasnije promene rade se isključivo kroz „Korekcija lagera“.</Text>
          <TextField label="SKU" value={createDraft.sku} onChangeText={(value) => updateDraft(setCreateDraft, 'sku', value)} />
          <TextField label="Naziv" value={createDraft.name} onChangeText={(value) => updateDraft(setCreateDraft, 'name', value)} />
          <TextField label="Jedinica mere" value={createDraft.unit} onChangeText={(value) => updateDraft(setCreateDraft, 'unit', value)} />
          <TextField label="Početno stanje" value={createDraft.stock} onChangeText={(value) => updateDraft(setCreateDraft, 'stock', value)} keyboardType="decimal-pad" />
          <TextField label="Minimalna količina" value={createDraft.minimum} onChangeText={(value) => updateDraft(setCreateDraft, 'minimum', value)} keyboardType="decimal-pad" />
          <TextField label="Prosečna nabavna cena RSD" value={createDraft.averageCost} onChangeText={(value) => updateDraft(setCreateDraft, 'averageCost', value)} keyboardType="decimal-pad" />
          <FilterChip label={createDraft.active ? 'Aktivan' : 'Neaktivan'} active={createDraft.active} onPress={() => updateDraft(setCreateDraft, 'active', !createDraft.active)} />
          <TextField label="Napomena" value={createDraft.notes} onChangeText={(value) => updateDraft(setCreateDraft, 'notes', value)} multiline />
          <Button loading={mutation.isPending} onPress={() => void createPart()}>Sačuvaj servisni deo</Button>
        </Card>
      ) : null}

      {workspace === 'parts' && editing && canManage ? (
        <Card style={styles.card}>
          <View style={styles.rowBetween}><Text style={styles.sectionTitle}>Izmena: {editing.sku}</Text><Button variant="secondary" onPress={() => setEditing(null)}>Zatvori</Button></View>
          <Text style={styles.meta}>Fizičko stanje: {quantity(editing.stock_quantity)} {editing.unit ?? ''}. Ova forma namerno ne menja fizičko stanje.</Text>
          <TextField label="SKU" value={editDraft.sku} onChangeText={(value) => updateDraft(setEditDraft, 'sku', value)} />
          <TextField label="Naziv" value={editDraft.name} onChangeText={(value) => updateDraft(setEditDraft, 'name', value)} />
          <TextField label="Jedinica mere" value={editDraft.unit} onChangeText={(value) => updateDraft(setEditDraft, 'unit', value)} />
          <TextField label="Minimalna količina" value={editDraft.minimum} onChangeText={(value) => updateDraft(setEditDraft, 'minimum', value)} keyboardType="decimal-pad" />
          <TextField label="Prosečna nabavna cena RSD" value={editDraft.averageCost} onChangeText={(value) => updateDraft(setEditDraft, 'averageCost', value)} keyboardType="decimal-pad" />
          <FilterChip label={editDraft.active ? 'Aktivan' : 'Neaktivan'} active={editDraft.active} onPress={() => updateDraft(setEditDraft, 'active', !editDraft.active)} />
          <TextField label="Napomena" value={editDraft.notes} onChangeText={(value) => updateDraft(setEditDraft, 'notes', value)} multiline />
          <Button loading={mutation.isPending} onPress={() => void updatePart()}>Sačuvaj izmene</Button>
        </Card>
      ) : null}

      {workspace === 'adjust' && adjusting && canManage ? (
        <Card style={styles.card}>
          <View style={styles.rowBetween}><Text style={styles.sectionTitle}>Korekcija: {adjusting.sku}</Text><Button variant="secondary" onPress={() => setAdjusting(null)}>Zatvori</Button></View>
          <Text style={styles.meta}>Trenutno stanje {quantity(adjusting.stock_quantity)} · rezervisano {quantity(adjusting.reserved_quantity)} · raspoloživo {quantity(adjusting.available_quantity)} {adjusting.unit ?? ''}.</Text>
          <TextField label="Promena količine (+/-)" value={adjustQty} onChangeText={setAdjustQty} keyboardType="decimal-pad" />
          <TextField label="Razlog korekcije" value={adjustNote} onChangeText={setAdjustNote} multiline />
          <Text style={styles.meta}>Retry koristi isti idempotency ključ dok ova korekcija ne uspe: {adjustKey}</Text>
          <Button loading={mutation.isPending} onPress={() => void adjustPart()}>Evidentiraj korekciju</Button>
        </Card>
      ) : null}

      {workspace === 'adjust' && canManage && canView && !adjusting ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Izaberi servisni deo</Text>
          <Text style={styles.copy}>Korekcija lagera se pokreće iz liste delova kako bi se zadržali tačan deo, razlog promene i postojeći idempotency retry ugovor.</Text>
          <Button onPress={() => selectWorkspace('parts')}>Otvori delove</Button>
        </Card>
      ) : null}

      {workspace === 'parts' && canView && response ? (
        <>
          <Card style={styles.card}>
            <Text style={styles.sectionTitle}>Filteri</Text>
            <TextField label="Pretraga" value={draftQ} onChangeText={setDraftQ} placeholder="SKU ili naziv" />
            <SelectSheet label="Aktivnost" value={draftActive} options={[{ value: '', label: 'Svi' }, { value: 'true', label: 'Aktivni' }, { value: 'false', label: 'Neaktivni' }]} onChange={(value) => setDraftActive(value as '' | 'true' | 'false')} />
            <View style={styles.actions}><Button onPress={applyFilters}>Primeni filtere</Button><Button variant="secondary" loading={query.isFetching} onPress={() => void query.refetch()}>Osveži</Button></View>
          </Card>

          <View style={styles.rowBetween}><View><Text style={styles.sectionTitle}>Delovi</Text><Text style={styles.meta}>{response.meta.total} ukupno</Text></View></View>
          {response.data.length === 0 ? <EmptyState title="Nema servisnih delova" message="Nema rezultata za izabrane filtere." /> : (
            <View style={styles.list}>{response.data.map((part) => (
              <Card key={part.id} style={styles.card}>
                <View style={styles.rowBetween}><View style={styles.grow}><Text style={styles.title}>{part.sku} · {part.name}</Text><Text style={styles.meta}>{part.is_active ? 'Aktivan' : 'Neaktivan'} · {part.unit ?? '—'}</Text></View><Pill tone={part.available_quantity !== null && part.minimum_quantity !== null && part.available_quantity <= part.minimum_quantity ? 'warning' : 'success'}>{part.is_active ? 'Aktivan' : 'Neaktivan'}</Pill></View>
                <Text style={styles.meta}>Stanje: {quantity(part.stock_quantity)} · Rezervisano: {quantity(part.reserved_quantity)} · Raspoloživo: {quantity(part.available_quantity)}</Text>
                <Text style={styles.meta}>Minimum: {quantity(part.minimum_quantity)} · Prosečna cena: {money(part.average_cost_rsd)}</Text>
                {part.notes ? <Text style={styles.copy}>{part.notes}</Text> : null}
                {canManage ? <View style={styles.actions}><Button variant="secondary" onPress={() => openEdit(part)}>Izmeni metadata</Button><Button onPress={() => openAdjust(part)}>Korekcija lagera</Button></View> : null}
              </Card>
            ))}</View>
          )}
          <View style={styles.pagination}>
            <Button variant="secondary" disabled={response.meta.current_page <= 1} onPress={() => setApplied((current) => ({ ...current, page: Math.max(1, (current.page ?? 1) - 1) }))}>Prethodna</Button>
            <Text style={styles.meta}>Strana {response.meta.current_page} / {response.meta.last_page}</Text>
            <Button variant="secondary" disabled={response.meta.current_page >= response.meta.last_page} onPress={() => setApplied((current) => ({ ...current, page: (current.page ?? 1) + 1 }))}>Sledeća</Button>
          </View>
        </>
      ) : null}
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: spacing.xxxl },
    back: { ...typography.small, color: theme.primary, fontWeight: '800' },
    copy: { ...typography.body, color: theme.muted, lineHeight: 22 },
    sectionTitle: { ...typography.h3, color: theme.ink },
    title: { ...typography.body, color: theme.ink, fontWeight: '800' },
    meta: { ...typography.small, color: theme.muted },
    workspaceCard: { gap: spacing.md, borderColor: theme.primary },
    card: { gap: spacing.md },
    list: { gap: spacing.md },
    actions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    rowBetween: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    grow: { flex: 1, minWidth: 0 },
    pagination: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
  });
}
