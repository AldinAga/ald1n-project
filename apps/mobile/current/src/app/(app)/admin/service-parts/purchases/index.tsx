import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Pill, type PillTone } from '@/components/ui/pill';
import { SelectSheet } from '@/components/ui/select-sheet';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiAdminServiceParts, type AdminServicePartPurchaseListParams, type AdminServicePartPurchaseWrite } from '@/features/admin/service-parts-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { formatDate } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';

type ItemDraft = { key: number; partId: string; quantity: string; cost: string };
const row = (key: number): ItemDraft => ({ key, partId: '', quantity: '1', cost: '' });
const compact = (value: string) => value.trim() ? value.trim() : null;

function positive(value: string, label: string): number {
  const parsed = Number(value.trim().replace(',', '.'));
  if (!Number.isFinite(parsed) || parsed <= 0) throw new Error(`${label} mora biti broj veći od nule.`);
  return parsed;
}
function optionalNonNegative(value: string, label: string): number | null {
  if (!value.trim()) return null;
  const parsed = Number(value.trim().replace(',', '.'));
  if (!Number.isFinite(parsed) || parsed < 0) throw new Error(`${label} mora biti broj jednak ili veći od nule.`);
  return parsed;
}
function tone(status: string): PillTone {
  if (status === 'received') return 'success';
  if (status === 'cancelled') return 'danger';
  if (status === 'ordered') return 'info';
  if (status === 'submitted') return 'warning';
  return 'primary';
}
function money(value: number | null): string { return value === null ? '—' : `${Number(value).toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} RSD`; }

export default function AdminServicePartPurchasesScreen() {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { bootstrap, can } = useAuth();
  const allowed = can('service_parts.procurement');
  const [draftQ, setDraftQ] = useState('');
  const [draftStatus, setDraftStatus] = useState('');
  const [draftSupplier, setDraftSupplier] = useState('');
  const [applied, setApplied] = useState<AdminServicePartPurchaseListParams> ({ page: 1, per_page: 40 });
  const [createOpen, setCreateOpen] = useState(false);
  const [supplierId, setSupplierId] = useState('');
  const [expectedAt, setExpectedAt] = useState('');
  const [notes, setNotes] = useState('');
  const [items, setItems] = useState<ItemDraft[]> ([row(1)]);
  const [nextKey, setNextKey] = useState(2);
  const query = useQuery({ queryKey: adminQueryKeys.servicePartPurchases(applied), queryFn: () => apiAdminServiceParts.purchasesList(applied), enabled: allowed });
  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });

  if (!allowed) return <UnavailableState title="Nabavke servisnih delova nisu dostupne" />;
  if (query.isLoading) return <LoadingState label="Učitavanje nabavki…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  const response = query.data;
  const supplierOptions = [{ value: '', label: 'Bez izabranog dobavljača' }, ...response.options.suppliers.map((supplier) => ({ value: String(supplier.id), label: `${supplier.code} · ${supplier.name}` }))];
  const partOptions = [{ value: '', label: 'Izaberi servisni deo' }, ...response.options.parts.map((part) => ({ value: String(part.id), label: `${part.sku} · ${part.name}` }))];
  const statusOptions = [{ value: '', label: 'Svi statusi' }, { value: 'draft', label: 'Nacrt' }, { value: 'submitted', label: 'Poslato' }, { value: 'ordered', label: 'Naručeno' }, { value: 'received', label: 'Primljeno' }, { value: 'cancelled', label: 'Otkazano' }];

  const updateItem = (key: number, patch: Partial<ItemDraft>) => setItems((current) => current.map((item) => item.key === key ? { ...item, ...patch } : item));
  const createPurchase = async () => {
    try {
      const mapped = items.map((item, index) => {
        const partId = Number(item.partId);
        if (!Number.isInteger(partId) || partId <= 0) throw new Error(`Stavka ${index + 1}: izaberi servisni deo.`);
        return { service_part_id: partId, ordered_quantity: positive(item.quantity, `Stavka ${index + 1}: količina`), unit_cost_rsd: optionalNonNegative(item.cost, `Stavka ${index + 1}: cena`) };
      });
      const input: AdminServicePartPurchaseWrite = { supplier_id: supplierId ? Number(supplierId) : null, expected_at: compact(expectedAt), notes: compact(notes), items: mapped };
      const created = await mutation.mutateAsync(() => apiAdminServiceParts.purchaseCreate(input));
      await client.invalidateQueries({ queryKey: adminQueryKeys.servicePartPurchasesRoot() });
      setSupplierId(''); setExpectedAt(''); setNotes(''); setItems([row(1)]); setNextKey(2); setCreateOpen(false);
      const purchaseId = (created as { data?: { id?: number } }).data?.id;
      feedback.notify({ tone: 'success', title: 'Nacrt nabavke je kreiran', message: 'Statusni tok ostaje draft → submitted → ordered → received.' });
      if (purchaseId) router.push({ pathname: '/admin/service-parts/purchases/[id]', params: { id: String(purchaseId) } });
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Nabavka nije kreirana', message: `${error instanceof Error ? error.message : 'Pokušaj ponovo.'} Sve stavke ostaju u formi.` });
    }
  };

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}><Text style={styles.back}>‹ Servisni lager</Text></Pressable>
      <PageHeader title="Nabavke delova" eyebrow="Admin · Procurement" name={bootstrap?.user.name} />
      <Text style={styles.copy}>Nacrt, slanje, naručivanje, prijem i otkazivanje kroz postojeći ServicePartsInventoryService. Ne postoji dodatni approve korak.</Text>
      <View style={styles.actions}><Button onPress={() => setCreateOpen((value) => !value)}>{createOpen ? 'Zatvori nacrt' : 'Nova nabavka'}</Button><Button variant="secondary" onPress={() => router.push('/admin/service-parts/suppliers')}>Dobavljači</Button></View>
      {createOpen ? <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Novi nacrt nabavke</Text>
        <SelectSheet label="Dobavljač" value={supplierId} options={supplierOptions} onChange={setSupplierId} />
        <TextField label="Očekivani datum (YYYY-MM-DD)" value={expectedAt} onChangeText={setExpectedAt} />
        <TextField label="Napomena" value={notes} onChangeText={setNotes} multiline />
        <Text style={styles.sectionTitle}>Stavke</Text>
        {items.map((item, index) => <Card key={item.key} style={styles.itemCard}>
          <View style={styles.rowBetween}><Text style={styles.title}>Stavka {index + 1}</Text>{items.length > 1 ? <Button variant="secondary" onPress={() => setItems((current) => current.filter((rowItem) => rowItem.key !== item.key))}>Ukloni</Button> : null}</View>
          <SelectSheet label="Servisni deo" value={item.partId} options={partOptions} onChange={(value) => updateItem(item.key, { partId: value })} />
          <TextField label="Količina" value={item.quantity} onChangeText={(value) => updateItem(item.key, { quantity: value })} keyboardType="decimal-pad" />
          <TextField label="Jedinična cena RSD (opciono)" value={item.cost} onChangeText={(value) => updateItem(item.key, { cost: value })} keyboardType="decimal-pad" />
        </Card>)}
        <Button variant="secondary" onPress={() => { setItems((current) => [...current, row(nextKey)]); setNextKey((value) => value + 1); }}>Dodaj još stavku</Button>
        <Button loading={mutation.isPending} onPress={() => void createPurchase()}>Kreiraj nacrt nabavke</Button>
      </Card> : null}
      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Filteri</Text>
        <TextField label="Broj nabavke" value={draftQ} onChangeText={setDraftQ} />
        <SelectSheet label="Status" value={draftStatus} options={statusOptions} onChange={setDraftStatus} />
        <SelectSheet label="Dobavljač" value={draftSupplier} options={[{ value: '', label: 'Svi dobavljači' }, ...supplierOptions.filter((option) => option.value !== '')]} onChange={setDraftSupplier} />
        <View style={styles.actions}><Button onPress={() => setApplied({ q: draftQ.trim() || undefined, status: draftStatus || undefined, supplier_id: draftSupplier ? Number(draftSupplier) : undefined, page: 1, per_page: 40 })}>Primeni</Button><Button variant="secondary" loading={query.isFetching} onPress={() => void query.refetch()}>Osveži</Button></View>
      </Card>
      {response.data.length === 0 ? <EmptyState title="Nema nabavki" message="Nema rezultata za izabrane filtere." /> : <View style={styles.list}>{response.data.map((purchase) => <Pressable key={purchase.id} accessibilityRole="button" onPress={() => router.push({ pathname: '/admin/service-parts/purchases/[id]', params: { id: String(purchase.id) } })} style={({ pressed }) => pressed ? styles.pressed : undefined}><Card style={styles.card}><View style={styles.rowBetween}><View style={styles.grow}><Text style={styles.title}>{purchase.request_number}</Text><Text style={styles.meta}>{purchase.supplier?.name ?? 'Dobavljač nije izabran'}</Text></View><Pill tone={tone(purchase.status)}>{purchase.status}</Pill></View><Text style={styles.meta}>Očekivano: {purchase.expected_at ? formatDate(purchase.expected_at) : '—'} · Ukupno: {money(purchase.total_cost_rsd)}</Text></Card></Pressable>)}</View>}
      <View style={styles.pagination}><Button variant="secondary" disabled={response.meta.current_page <= 1} onPress={() => setApplied((current) => ({ ...current, page: Math.max(1, (current.page ?? 1) - 1) }))}>Prethodna</Button><Text style={styles.meta}>Strana {response.meta.current_page} / {response.meta.last_page}</Text><Button variant="secondary" disabled={response.meta.current_page >= response.meta.last_page} onPress={() => setApplied((current) => ({ ...current, page: (current.page ?? 1) + 1 }))}>Sledeća</Button></View>
    </Screen>
  );
}

function createStyles(theme: AppColors) { return StyleSheet.create({ content: { gap: spacing.lg, paddingBottom: spacing.xxxl }, back: { ...typography.small, color: theme.primary, fontWeight: '800' }, copy: { ...typography.body, color: theme.muted, lineHeight: 22 }, sectionTitle: { ...typography.h3, color: theme.ink }, title: { ...typography.body, color: theme.ink, fontWeight: '800' }, meta: { ...typography.small, color: theme.muted }, card: { gap: spacing.md }, itemCard: { gap: spacing.sm }, list: { gap: spacing.md }, actions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }, rowBetween: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md }, grow: { flex: 1, minWidth: 0 }, pagination: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md }, pressed: { opacity: 0.78 } }); }
