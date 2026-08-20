import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { DateTimeField } from '@/components/ui/date-time-field';
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminInventory,
  shareAdminInventoryCsv,
  type AdminInventoryCountInput,
  type AdminInventoryListParams,
  type AdminInventoryMovementParams,
  type AdminInventoryProduct,
  type AdminInventoryReceiptInput,
} from '@/features/admin/inventory-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

type Panel = 'movements' | 'receive' | 'count' | null;
type AdjustDraft = { product: AdminInventoryProduct; quantity: string; note: string; key: string };
type ReceiptItemDraft = { product: AdminInventoryProduct; quantity: string; unitCost: string; note: string };
type CountItemDraft = { product: AdminInventoryProduct; counted: string; note: string };

function localDate(): string {
  const value = new Date();
  const year = value.getFullYear();
  const month = String(value.getMonth() + 1).padStart(2, '0');
  const day = String(value.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

function newKey(prefix: string): string {
  return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 12)}`;
}

function optional(value: string): string | null {
  const normalized = value.trim();
  return normalized ? normalized : null;
}

function integer(value: string, label: string, min: number, max = 1000000): number {
  const normalized = value.trim();
  if (!/^-?\d+$/.test(normalized)) throw new Error(`${label} mora biti ceo broj.`);
  const parsed = Number(normalized);
  if (!Number.isSafeInteger(parsed) || parsed < min || parsed > max) throw new Error(`${label} je van dozvoljenog opsega.`);
  return parsed;
}

function decimal(value: string, label: string): number | null {
  const normalized = value.trim();
  if (!normalized) return null;
  const parsed = Number(normalized.replace(',', '.'));
  if (!Number.isFinite(parsed) || parsed < 0 || parsed > 9999999999.99) throw new Error(`${label} nije validan iznos.`);
  return parsed;
}

function numberLabel(value: number): string {
  return Number(value).toLocaleString('sr-RS');
}

function dateTime(value: string | null): string {
  if (!value) return '—';
  const parsed = new Date(value);
  return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString('sr-RS');
}

function ProductChoice({ product, onPress, label }: { product: AdminInventoryProduct; onPress: () => void; label: string }) {
  const { colors: theme } = useAppTheme();
  return (
    <Pressable onPress={onPress}>
      <Card style={{ gap: spacing.xs }}>
        <Text style={{ ...typography.body, color: theme.ink, fontWeight: '700' }}>{product.sku} · {product.name}</Text>
        <Text style={{ ...typography.small, color: theme.muted }}>Stanje {numberLabel(product.stock_quantity)} · Prag {numberLabel(product.low_stock_threshold)}</Text>
        <Text style={{ ...typography.small, color: theme.primary, fontWeight: '700' }}>{label}</Text>
      </Card>
    </Pressable>
  );
}

export default function AdminInventoryScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { can } = useAuth();
  const canView = can('stock.view');
  const canAdjust = can('stock.adjust');
  const canReceive = can('inventory.receive');
  const canCount = can('inventory.count');
  const canExport = can('inventory.export');
  const allowed = canView || canAdjust || canReceive || canCount || canExport;

  const [draftQ, setDraftQ] = useState('');
  const [stockLow, setStockLow] = useState(false);
  const [draftStatus, setDraftStatus] = useState('');
  const [applied, setApplied] = useState<AdminInventoryListParams> ({ page: 1, per_page: 35, stock: 'all' });
  const [panel, setPanel] = useState<Panel> (null);
  const [busyAction, setBusyAction] = useState<string | null> (null);

  const [adjust, setAdjust] = useState<AdjustDraft | null> (null);

  const [receiptDate, setReceiptDate] = useState(localDate());
  const [receiptSupplier, setReceiptSupplier] = useState('');
  const [receiptDocument, setReceiptDocument] = useState('');
  const [receiptNote, setReceiptNote] = useState('');
  const [receiptKey, setReceiptKey] = useState(() => newKey('inventory-receipt'));
  const [receiptItems, setReceiptItems] = useState<ReceiptItemDraft[]> ([]);

  const [countDate, setCountDate] = useState(localDate());
  const [countScope, setCountScope] = useState('');
  const [countNote, setCountNote] = useState('');
  const [countKey, setCountKey] = useState(() => newKey('inventory-count'));
  const [countItems, setCountItems] = useState<CountItemDraft[]> ([]);

  const [lookupQ, setLookupQ] = useState('');
  const [movementQ, setMovementQ] = useState('');
  const [movementType, setMovementType] = useState('');
  const [movementSource, setMovementSource] = useState('');
  const [movementApplied, setMovementApplied] = useState<AdminInventoryMovementParams> ({ page: 1, per_page: 30 });

  const inventoryQuery = useQuery({
    queryKey: adminQueryKeys.inventoryList(applied),
    queryFn: () => apiAdminInventory.list(applied),
    enabled: canView,
  });

  const lookupQuery = useQuery({
    queryKey: adminQueryKeys.inventoryLookup(lookupQ.trim()),
    queryFn: () => apiAdminInventory.list({ q: lookupQ.trim(), page: 1, per_page: 20, stock: 'all' }),
    enabled: (panel === 'receive' || panel === 'count') && lookupQ.trim().length >= 2 && (canReceive || canCount),
  });

  const movementsQuery = useQuery({
    queryKey: adminQueryKeys.inventoryMovements(movementApplied),
    queryFn: () => apiAdminInventory.movements(movementApplied),
    enabled: panel === 'movements' && canView,
  });

  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });

  if (!allowed) return <UnavailableState title="Lager nije dostupan" />;
  if (canView && inventoryQuery.isLoading) return <LoadingState label="Učitavanje lagera…" />;
  if (canView && (inventoryQuery.isError || !inventoryQuery.data)) return <ErrorState error={inventoryQuery.error} onRetry={() => void inventoryQuery.refetch()} />;

  const response = inventoryQuery.data;
  const lookupRows = lookupQuery.data?.data ?? [];

  const refreshInventory = async () => {
    await client.invalidateQueries({ queryKey: adminQueryKeys.inventory() });
  };

  const execute = async (key: string, success: string, run: () => Promise<unknown>, after?: () => void) => {
    setBusyAction(key);
    try {
      await mutation.mutateAsync(run);
      await refreshInventory();
      after?.();
      feedback.notify({ tone: 'success', title: success });
      return true;
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Akcija nije izvršena', message: error instanceof Error ? error.message : 'Pokušajte ponovo.' });
      return false;
    } finally {
      setBusyAction(null);
    }
  };

  const applyFilters = () => setApplied({
    q: optional(draftQ) ?? undefined,
    stock: stockLow ? 'low' : 'all',
    status: optional(draftStatus) ?? undefined,
    page: 1,
    per_page: 35,
  });

  const openPanel = (next: Panel) => {
    setPanel(next);
    setLookupQ('');
  };

  const startAdjust = (product: AdminInventoryProduct) => {
    setAdjust({ product, quantity: '', note: '', key: newKey(`stock-adjust-${product.id}`) });
  };

  const submitAdjust = async () => {
    if (!adjust) return;
    await execute('adjust', 'Stanje lagera je korigovano', () => {
      const change = integer(adjust.quantity, 'Promena količine', -1000000);
      if (change === 0) throw new Error('Promena količine ne može biti 0.');
      if (!adjust.note.trim()) throw new Error('Napomena je obavezna za ručnu korekciju.');
      return apiAdminInventory.adjust(adjust.product.id, {
        quantity_change: change,
        note: adjust.note.trim(),
        idempotency_key: adjust.key,
      });
    }, () => setAdjust(null));
  };

  const addReceiptProduct = (product: AdminInventoryProduct) => {
    setReceiptItems((current) => current.some((item) => item.product.id === product.id)
      ? current
      : [...current, { product, quantity: '1', unitCost: '', note: '' }]);
  };

  const addCountProduct = (product: AdminInventoryProduct) => {
    setCountItems((current) => current.some((item) => item.product.id === product.id)
      ? current
      : [...current, { product, counted: String(product.stock_quantity), note: '' }]);
  };

  const submitReceipt = async () => {
    await execute('receive', 'Prijem robe je proknjižen', () => {
      if (receiptItems.length === 0) throw new Error('Dodajte najmanje jedan artikal u prijem robe.');
      const input: AdminInventoryReceiptInput = {
        received_on: receiptDate,
        supplier_name: optional(receiptSupplier),
        supplier_document_number: optional(receiptDocument),
        note: optional(receiptNote),
        idempotency_key: receiptKey,
        items: receiptItems.map((item) => ({
          product_id: item.product.id,
          quantity: integer(item.quantity, `Količina za ${item.product.sku}`, 1),
          unit_cost_rsd: decimal(item.unitCost, `Nabavna cena za ${item.product.sku}`),
          note: optional(item.note),
        })),
      };
      return apiAdminInventory.receive(input);
    }, () => {
      setReceiptItems([]);
      setReceiptSupplier('');
      setReceiptDocument('');
      setReceiptNote('');
      setReceiptDate(localDate());
      setReceiptKey(newKey('inventory-receipt'));
      setLookupQ('');
    });
  };

  const submitCount = async () => {
    await execute('count', 'Popis je finalizovan', () => {
      if (countItems.length === 0) throw new Error('Dodajte najmanje jedan artikal u popis.');
      const input: AdminInventoryCountInput = {
        counted_on: countDate,
        scope_label: optional(countScope),
        note: optional(countNote),
        idempotency_key: countKey,
        items: countItems.map((item) => ({
          product_id: item.product.id,
          counted_quantity: integer(item.counted, `Popisana količina za ${item.product.sku}`, 0),
          note: optional(item.note),
        })),
      };
      return apiAdminInventory.count(input);
    }, () => {
      setCountItems([]);
      setCountScope('');
      setCountNote('');
      setCountDate(localDate());
      setCountKey(newKey('inventory-count'));
      setLookupQ('');
    });
  };

  const shareCsv = async () => {
    setBusyAction('csv');
    try {
      await shareAdminInventoryCsv();
      feedback.notify({ tone: 'success', title: 'CSV je spreman za deljenje' });
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'CSV izvoz nije uspeo', message: error instanceof Error ? error.message : 'Pokušajte ponovo.' });
    } finally {
      setBusyAction(null);
    }
  };

  return (
    <Screen contentContainerStyle={styles.content}>
      <Pressable onPress={() => router.back()}><Text style={styles.back}>← Admin</Text></Pressable>
      <PageHeader title="Lager" />
      <Text style={styles.copy}>Operativni pregled stanja, ručne korekcije, prijem robe, popis, stock movement ledger i CSV izvoz.</Text>

      {response ? (
        <View style={styles.statsGrid}>
          <Card style={styles.statCard}><Text style={styles.meta}>Artikala</Text><Text style={styles.statValue}>{numberLabel(response.meta.total)}</Text></Card>
          <Card style={styles.statCard}><Text style={styles.meta}>Nizak lager</Text><Text style={styles.statValue}>{numberLabel(response.summary.low_stock_count)}</Text></Card>
        </View>
      ) : null}

      <Card style={styles.filtersCard}>
        <Text style={styles.sectionTitle}>Filteri lagera</Text>
        <TextField label="Pretraga" value={draftQ} onChangeText={setDraftQ} placeholder="SKU ili naziv artikla" />
        <TextField label="Status" value={draftStatus} onChangeText={setDraftStatus} placeholder="Opcionalno" />
        <FilterBar activeCount={(stockLow ? 1 : 0) + (draftQ.trim() ? 1 : 0) + (draftStatus.trim() ? 1 : 0)} onClear={() => { setStockLow(false); setDraftQ(''); setDraftStatus(''); }}>
          <FilterChip label="Samo nizak lager" active={stockLow} onPress={() => setStockLow((value) => !value)} />
        </FilterBar>
        <Button onPress={applyFilters}>Primeni filtere</Button>
      </Card>

      <Card style={styles.operationsCard}>
        <Text style={styles.sectionTitle}>Operacije</Text>
        <View style={styles.actions}>
          {canView ? <Button variant="secondary" onPress={() => openPanel(panel === 'movements' ? null : 'movements')}>Promene lagera</Button> : null}
          {canReceive ? <Button variant="secondary" onPress={() => openPanel(panel === 'receive' ? null : 'receive')}>Prijem robe</Button> : null}
          {canCount ? <Button variant="secondary" onPress={() => openPanel(panel === 'count' ? null : 'count')}>Popis</Button> : null}
          {canExport ? <Button variant="secondary" loading={busyAction === 'csv'} onPress={() => void shareCsv()}>CSV</Button> : null}
        </View>
      </Card>

      {panel === 'movements' && canView ? (
        <Card style={styles.panelCard}>
          <Text style={styles.sectionTitle}>Stock movement ledger</Text>
          <TextField label="Pretraga" value={movementQ} onChangeText={setMovementQ} placeholder="SKU ili naziv" />
          <TextField label="Vrsta promene" value={movementType} onChangeText={setMovementType} placeholder="manual_adjustment…" />
          <TextField label="Izvor" value={movementSource} onChangeText={setMovementSource} placeholder="Opcionalno" />
          <Button onPress={() => setMovementApplied({ q: optional(movementQ) ?? undefined, movement_type: optional(movementType) ?? undefined, source: optional(movementSource) ?? undefined, page: 1, per_page: 30 })}>Primeni</Button>
          {movementsQuery.isLoading ? <LoadingState label="Učitavanje promena…" /> : null}
          {movementsQuery.isError ? <ErrorState error={movementsQuery.error} onRetry={() => void movementsQuery.refetch()} /> : null}
          {movementsQuery.data?.data.map((movement) => (
            <Card key={movement.id} style={styles.itemCard}>
              <Text style={styles.title}>{movement.product ? `${movement.product.sku} · ${movement.product.name}` : 'Artikal nije dostupan'}</Text>
              <Text style={styles.meta}>{movement.movement_type} · {movement.source}</Text>
              <Text style={styles.title}>{movement.quantity_change > 0 ? '+' : ''}{numberLabel(movement.quantity_change)} · {numberLabel(movement.quantity_before)} → {numberLabel(movement.quantity_after)}</Text>
              <Text style={styles.meta}>{dateTime(movement.created_at)}{movement.note ? ` · ${movement.note}` : ''}</Text>
            </Card>
          ))}
          {movementsQuery.data ? (
            <View style={styles.pagination}>
              <Button variant="secondary" disabled={movementsQuery.data.meta.current_page <= 1} onPress={() => setMovementApplied((current) => ({ ...current, page: Math.max(1, (current.page ?? 1) - 1) }))}>Prethodna</Button>
              <Text style={styles.meta}>{movementsQuery.data.meta.current_page} / {movementsQuery.data.meta.last_page}</Text>
              <Button variant="secondary" disabled={movementsQuery.data.meta.current_page >= movementsQuery.data.meta.last_page} onPress={() => setMovementApplied((current) => ({ ...current, page: (current.page ?? 1) + 1 }))}>Sledeća</Button>
            </View>
          ) : null}
        </Card>
      ) : null}

      {panel === 'receive' && canReceive ? (
        <Card style={styles.panelCard}>
          <Text style={styles.sectionTitle}>Prijem robe</Text>
          <DateTimeField label="Datum prijema" mode="date" value={receiptDate} onChangeText={setReceiptDate} />
          <TextField label="Dobavljač" value={receiptSupplier} onChangeText={setReceiptSupplier} />
          <TextField label="Broj dokumenta dobavljača" value={receiptDocument} onChangeText={setReceiptDocument} />
          <TextField label="Napomena" value={receiptNote} onChangeText={setReceiptNote} multiline />
          <TextField label="Pronađi artikal" value={lookupQ} onChangeText={setLookupQ} placeholder="Najmanje 2 slova ili deo SKU-a" />
          {lookupQuery.isFetching ? <Text style={styles.meta}>Pretraga…</Text> : null}
          {lookupRows.map((product) => <ProductChoice key={product.id} product={product} onPress={() => addReceiptProduct(product)} label="Dodaj u prijem" />)}
          <Text style={styles.sectionTitle}>Stavke ({receiptItems.length})</Text>
          {receiptItems.map((item, index) => (
            <Card key={item.product.id} style={styles.itemCard}>
              <Text style={styles.title}>{item.product.sku} · {item.product.name}</Text>
              <TextField label="Količina" value={item.quantity} onChangeText={(value) => setReceiptItems((current) => current.map((row, rowIndex) => rowIndex === index ? { ...row, quantity: value } : row))} keyboardType="number-pad" />
              <TextField label="Nabavna cena RSD" value={item.unitCost} onChangeText={(value) => setReceiptItems((current) => current.map((row, rowIndex) => rowIndex === index ? { ...row, unitCost: value } : row))} keyboardType="decimal-pad" />
              <TextField label="Napomena stavke" value={item.note} onChangeText={(value) => setReceiptItems((current) => current.map((row, rowIndex) => rowIndex === index ? { ...row, note: value } : row))} />
              <Button variant="secondary" onPress={() => setReceiptItems((current) => current.filter((_, rowIndex) => rowIndex !== index))}>Ukloni stavku</Button>
            </Card>
          ))}
          <Text style={styles.meta}>Isti idempotency ključ ostaje tokom neuspelog retry-a; draft se briše tek posle uspešnog knjiženja.</Text>
          <Button loading={busyAction === 'receive'} onPress={() => void submitReceipt()}>Proknjiži prijem</Button>
        </Card>
      ) : null}

      {panel === 'count' && canCount ? (
        <Card style={styles.panelCard}>
          <Text style={styles.sectionTitle}>Popis</Text>
          <DateTimeField label="Datum popisa" mode="date" value={countDate} onChangeText={setCountDate} />
          <TextField label="Obuhvat / oznaka" value={countScope} onChangeText={setCountScope} />
          <TextField label="Napomena" value={countNote} onChangeText={setCountNote} multiline />
          <TextField label="Pronađi artikal" value={lookupQ} onChangeText={setLookupQ} placeholder="Najmanje 2 slova ili deo SKU-a" />
          {lookupQuery.isFetching ? <Text style={styles.meta}>Pretraga…</Text> : null}
          {lookupRows.map((product) => <ProductChoice key={product.id} product={product} onPress={() => addCountProduct(product)} label="Dodaj u popis" />)}
          <Text style={styles.sectionTitle}>Stavke ({countItems.length})</Text>
          {countItems.map((item, index) => (
            <Card key={item.product.id} style={styles.itemCard}>
              <Text style={styles.title}>{item.product.sku} · {item.product.name}</Text>
              <Text style={styles.meta}>Sistemsko stanje: {numberLabel(item.product.stock_quantity)}</Text>
              <TextField label="Popisana količina" value={item.counted} onChangeText={(value) => setCountItems((current) => current.map((row, rowIndex) => rowIndex === index ? { ...row, counted: value } : row))} keyboardType="number-pad" />
              <TextField label="Napomena stavke" value={item.note} onChangeText={(value) => setCountItems((current) => current.map((row, rowIndex) => rowIndex === index ? { ...row, note: value } : row))} />
              <Button variant="secondary" onPress={() => setCountItems((current) => current.filter((_, rowIndex) => rowIndex !== index))}>Ukloni stavku</Button>
            </Card>
          ))}
          <Text style={styles.meta}>Popis koristi server kao finalni autoritet za sistemsko stanje, varijansu, row-lock i ledger.</Text>
          <Button loading={busyAction === 'count'} onPress={() => void submitCount()}>Finalizuj popis</Button>
        </Card>
      ) : null}

      {adjust ? (
        <Card style={styles.panelCard}>
          <Text style={styles.sectionTitle}>Ručna korekcija</Text>
          <Text style={styles.title}>{adjust.product.sku} · {adjust.product.name}</Text>
          <Text style={styles.meta}>Trenutno stanje: {numberLabel(adjust.product.stock_quantity)}</Text>
          <TextField label="Promena količine" value={adjust.quantity} onChangeText={(value) => setAdjust((current) => current ? { ...current, quantity: value } : current)} placeholder="npr. 3 ili -2" />
          <TextField label="Napomena" value={adjust.note} onChangeText={(value) => setAdjust((current) => current ? { ...current, note: value } : current)} multiline />
          <View style={styles.actions}>
            <Button loading={busyAction === 'adjust'} onPress={() => void submitAdjust()}>Evidentiraj korekciju</Button>
            <Button variant="secondary" onPress={() => setAdjust(null)}>Odustani</Button>
          </View>
        </Card>
      ) : null}

      {response ? (
        <>
          <View style={styles.rowBetween}>
            <View style={styles.grow}><Text style={styles.sectionTitle}>Artikli</Text><Text style={styles.meta}>{response.meta.total} ukupno</Text></View>
            <Button variant="secondary" loading={inventoryQuery.isFetching} onPress={() => void inventoryQuery.refetch()}>Osveži</Button>
          </View>
          {response.data.length === 0 ? <EmptyState title="Nema artikala" message="Nema rezultata za izabrane filtere." /> : response.data.map((product) => (
            <Card key={product.id} style={product.is_low_stock ? styles.warningCard : styles.itemCard}>
              <View style={styles.rowBetween}>
                <View style={styles.grow}>
                  <Text style={styles.title}>{product.sku} · {product.name}</Text>
                  <Text style={styles.meta}>Status: {product.status} · Ažurirano: {dateTime(product.updated_at)}</Text>
                </View>
                <Text style={styles.stock}>{numberLabel(product.stock_quantity)}</Text>
              </View>
              <Text style={styles.meta}>Minimalni prag: {numberLabel(product.low_stock_threshold)}{product.is_low_stock ? ' · NIZAK LAGER' : ''}</Text>
              {canAdjust ? <Button variant="secondary" onPress={() => startAdjust(product)}>Koriguj stanje</Button> : null}
            </Card>
          ))}
          <View style={styles.pagination}>
            <Button variant="secondary" disabled={response.meta.current_page <= 1} onPress={() => setApplied((current) => ({ ...current, page: Math.max(1, (current.page ?? 1) - 1) }))}>Prethodna</Button>
            <Text style={styles.meta}>Strana {response.meta.current_page} / {response.meta.last_page}</Text>
            <Button variant="secondary" disabled={response.meta.current_page >= response.meta.last_page} onPress={() => setApplied((current) => ({ ...current, page: (current.page ?? 1) + 1 }))}>Sledeća</Button>
          </View>

          <Card style={styles.summaryCard}>
            <Text style={styles.sectionTitle}>Poslednji prijemi</Text>
            {response.summary.recent_receipts.length === 0 ? <Text style={styles.meta}>Nema evidentiranih prijema.</Text> : response.summary.recent_receipts.map((item) => (
              <Text style={styles.meta} key={item.id}>{item.receipt_number} · {item.received_on ?? '—'} · {item.total_units} kom · {item.supplier_name ?? 'Bez dobavljača'}</Text>
            ))}
          </Card>
          <Card style={styles.summaryCard}>
            <Text style={styles.sectionTitle}>Poslednji popisi</Text>
            {response.summary.recent_counts.length === 0 ? <Text style={styles.meta}>Nema evidentiranih popisa.</Text> : response.summary.recent_counts.map((item) => (
              <Text style={styles.meta} key={item.id}>{item.count_number} · {item.counted_on ?? '—'} · varijansa {item.total_variance}</Text>
            ))}
          </Card>
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
    stock: { ...typography.h2, color: theme.ink },
    statValue: { ...typography.h2, color: theme.ink },
    statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    statCard: { flexGrow: 1, minWidth: 145, gap: spacing.xs },
    filtersCard: { gap: spacing.md },
    operationsCard: { gap: spacing.md },
    panelCard: { gap: spacing.md },
    itemCard: { gap: spacing.sm },
    warningCard: { gap: spacing.sm, borderColor: theme.primary },
    summaryCard: { gap: spacing.sm },
    actions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    rowBetween: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    grow: { flex: 1, minWidth: 0 },
    pagination: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
  });
}
