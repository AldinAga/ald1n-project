import { useMemo, useState, type Dispatch, type SetStateAction } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { FilterChip } from '@/components/ui/filter-bar';
import { SelectSheet } from '@/components/ui/select-sheet';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminServiceParts,
  type AdminServicePartSupplier,
  type AdminServicePartSupplierListParams,
  type AdminServicePartSupplierWrite,
} from '@/features/admin/service-parts-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

type Draft = { code: string; name: string; contact: string; phone: string; email: string; address: string; leadTime: string; notes: string; active: boolean };
const blank = (): Draft => ({ code: '', name: '', contact: '', phone: '', email: '', address: '', leadTime: '', notes: '', active: true });
const compact = (value: string) => value.trim() ? value.trim() : null;

function toDraft(item: AdminServicePartSupplier): Draft {
  return { code: item.code, name: item.name, contact: item.contact_person ?? '', phone: item.phone ?? '', email: item.email ?? '', address: item.address ?? '', leadTime: item.lead_time_days === null ? '' : String(item.lead_time_days), notes: item.notes ?? '', active: item.is_active };
}

export default function AdminServicePartSuppliersScreen() {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { bootstrap, can } = useAuth();
  const allowed = can('service_parts.procurement');
  const [draftQ, setDraftQ] = useState('');
  const [draftActive, setDraftActive] = useState<'' | 'true' | 'false'> ('');
  const [applied, setApplied] = useState<AdminServicePartSupplierListParams> ({ page: 1, per_page: 40 });
  const [createOpen, setCreateOpen] = useState(false);
  const [createDraft, setCreateDraft] = useState<Draft> (blank);
  const [editing, setEditing] = useState<AdminServicePartSupplier | null> (null);
  const [editDraft, setEditDraft] = useState<Draft> (blank);
  const query = useQuery({ queryKey: adminQueryKeys.servicePartSuppliers(applied), queryFn: () => apiAdminServiceParts.suppliersList(applied), enabled: allowed });
  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });

  if (!allowed) return <UnavailableState title="Dobavljači servisnih delova nisu dostupni" />;
  if (query.isLoading) return <LoadingState label="Učitavanje dobavljača…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  const response = query.data;

  const setField = (setter: Dispatch<SetStateAction<Draft>>, key: keyof Draft, value: string | boolean) => setter((current) => ({ ...current, [key]: value }));
  const input = (draft: Draft): AdminServicePartSupplierWrite => {
    if (!draft.code.trim()) throw new Error('Šifra dobavljača je obavezna.');
    if (!draft.name.trim()) throw new Error('Naziv dobavljača je obavezan.');
    const lead = draft.leadTime.trim() === '' ? null : Number(draft.leadTime);
    if (lead !== null && (!Number.isInteger(lead) || lead < 0 || lead > 3650)) throw new Error('Rok isporuke mora biti ceo broj od 0 do 3650 dana.');
    return { code: draft.code.trim(), name: draft.name.trim(), contact_person: compact(draft.contact), phone: compact(draft.phone), email: compact(draft.email), address: compact(draft.address), lead_time_days: lead, notes: compact(draft.notes), is_active: draft.active };
  };
  const refresh = async () => client.invalidateQueries({ queryKey: adminQueryKeys.servicePartSuppliersRoot() });

  const create = async () => {
    try {
      await mutation.mutateAsync(() => apiAdminServiceParts.supplierCreate(input(createDraft)));
      await refresh();
      setCreateDraft(blank());
      setCreateOpen(false);
      feedback.notify({ tone: 'success', title: 'Dobavljač je kreiran', message: 'Podaci su sačuvani.' });
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Dobavljač nije kreiran', message: `${error instanceof Error ? error.message : 'Pokušaj ponovo.'} Uneti podaci ostaju u formi.` });
    }
  };
  const update = async () => {
    if (!editing) return;
    try {
      await mutation.mutateAsync(() => apiAdminServiceParts.supplierUpdate(editing.id, input(editDraft)));
      await refresh();
      setEditing(null);
      feedback.notify({ tone: 'success', title: 'Dobavljač je izmenjen', message: 'Podaci su sačuvani.' });
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Izmena nije sačuvana', message: `${error instanceof Error ? error.message : 'Pokušaj ponovo.'} Uneti podaci ostaju u formi.` });
    }
  };

  const form = (draft: Draft, setter: Dispatch<SetStateAction<Draft>>, action: () => void, label: string) => (
    <>
      <TextField label="Šifra" value={draft.code} onChangeText={(value) => setField(setter, 'code', value)} />
      <TextField label="Naziv" value={draft.name} onChangeText={(value) => setField(setter, 'name', value)} />
      <TextField label="Kontakt osoba" value={draft.contact} onChangeText={(value) => setField(setter, 'contact', value)} />
      <TextField label="Telefon" value={draft.phone} onChangeText={(value) => setField(setter, 'phone', value)} />
      <TextField label="Email" value={draft.email} onChangeText={(value) => setField(setter, 'email', value)} keyboardType="email-address" />
      <TextField label="Adresa" value={draft.address} onChangeText={(value) => setField(setter, 'address', value)} multiline />
      <TextField label="Rok isporuke (dana)" value={draft.leadTime} onChangeText={(value) => setField(setter, 'leadTime', value)} keyboardType="numeric" />
      <TextField label="Napomena" value={draft.notes} onChangeText={(value) => setField(setter, 'notes', value)} multiline />
      <FilterChip label={draft.active ? 'Aktivan' : 'Neaktivan'} active={draft.active} onPress={() => setField(setter, 'active', !draft.active)} />
      <Button loading={mutation.isPending} onPress={action}>{label}</Button>
    </>
  );

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}><Text style={styles.back}>‹ Servisni lager</Text></Pressable>
      <PageHeader title="Dobavljači delova" eyebrow="Admin · Nabavka" name={bootstrap?.user.name} />
      <View style={styles.actions}><Button onPress={() => setCreateOpen((value) => !value)}>{createOpen ? 'Zatvori unos' : 'Novi dobavljač'}</Button><Button variant="secondary" onPress={() => router.push('/admin/service-parts/purchases')}>Nabavke</Button></View>
      {createOpen ? <Card style={styles.card}><Text style={styles.sectionTitle}>Novi dobavljač</Text>{form(createDraft, setCreateDraft, () => void create(), 'Sačuvaj dobavljača')}</Card> : null}
      {editing ? <Card style={styles.card}><View style={styles.rowBetween}><Text style={styles.sectionTitle}>Izmena: {editing.name}</Text><Button variant="secondary" onPress={() => setEditing(null)}>Zatvori</Button></View>{form(editDraft, setEditDraft, () => void update(), 'Sačuvaj izmene')}</Card> : null}
      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Filteri</Text>
        <TextField label="Pretraga" value={draftQ} onChangeText={setDraftQ} placeholder="Šifra, naziv, kontakt ili email" />
        <SelectSheet label="Aktivnost" value={draftActive} options={[{ value: '', label: 'Svi' }, { value: 'true', label: 'Aktivni' }, { value: 'false', label: 'Neaktivni' }]} onChange={(value) => setDraftActive(value as '' | 'true' | 'false')} />
        <View style={styles.actions}><Button onPress={() => setApplied({ q: draftQ.trim() || undefined, active: draftActive === '' ? undefined : draftActive === 'true', page: 1, per_page: 40 })}>Primeni</Button><Button variant="secondary" loading={query.isFetching} onPress={() => void query.refetch()}>Osveži</Button></View>
      </Card>
      {response.data.length === 0 ? <EmptyState title="Nema dobavljača" message="Nema rezultata za izabrane filtere." /> : <View style={styles.list}>{response.data.map((supplier) => <Card key={supplier.id} style={styles.card}><View style={styles.rowBetween}><View style={styles.grow}><Text style={styles.title}>{supplier.code} · {supplier.name}</Text><Text style={styles.meta}>{supplier.contact_person ?? 'Bez kontakt osobe'} · {supplier.phone ?? 'bez telefona'}</Text></View><FilterChip label={supplier.is_active ? 'Aktivan' : 'Neaktivan'} active={supplier.is_active} onPress={() => {}} /></View><Text style={styles.meta}>{supplier.email ?? 'Bez emaila'} · rok {supplier.lead_time_days ?? '—'} dana</Text>{supplier.address ? <Text style={styles.copy}>{supplier.address}</Text> : null}<Button variant="secondary" onPress={() => { setEditing(supplier); setEditDraft(toDraft(supplier)); }}>Izmeni</Button></Card>)}</View>}
      <View style={styles.pagination}><Button variant="secondary" disabled={response.meta.current_page <= 1} onPress={() => setApplied((current) => ({ ...current, page: Math.max(1, (current.page ?? 1) - 1) }))}>Prethodna</Button><Text style={styles.meta}>Strana {response.meta.current_page} / {response.meta.last_page}</Text><Button variant="secondary" disabled={response.meta.current_page >= response.meta.last_page} onPress={() => setApplied((current) => ({ ...current, page: (current.page ?? 1) + 1 }))}>Sledeća</Button></View>
    </Screen>
  );
}

function createStyles(theme: AppColors) { return StyleSheet.create({ content: { gap: spacing.lg, paddingBottom: spacing.xxxl }, back: { ...typography.small, color: theme.primary, fontWeight: '800' }, sectionTitle: { ...typography.h3, color: theme.ink }, title: { ...typography.body, color: theme.ink, fontWeight: '800' }, meta: { ...typography.small, color: theme.muted }, copy: { ...typography.body, color: theme.muted, lineHeight: 22 }, card: { gap: spacing.md }, list: { gap: spacing.md }, actions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }, rowBetween: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md }, grow: { flex: 1, minWidth: 0 }, pagination: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md } }); }
