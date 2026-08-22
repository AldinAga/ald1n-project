import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { StyleSheet, Text, View } from 'react-native';

import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Pill } from '@/components/ui/pill';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { apiAdminCouriers, type AdminCourierInput, type AdminCourierService } from '@/features/admin/couriers-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

// MOBILE_V0_8_SHIPMENT_COURIER_DIRECTORY_BATCH11
export default function AdminCouriersScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { bootstrap } = useAuth();
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const allowed = bootstrap?.user.role?.slug === 'superadmin';
  const [editingId, setEditingId] = useState<number | null> (null);
  const [name, setName] = useState('');
  const [trackingUrl, setTrackingUrl] = useState('');
  const [sortOrder, setSortOrder] = useState('100');
  const [active, setActive] = useState<'1' | '0'> ('1');
  const [isDefault, setIsDefault] = useState<'1' | '0'> ('0');

  const query = useQuery({ queryKey: ['admin', 'couriers'], queryFn: apiAdminCouriers.list, enabled: allowed });
  const mutation = useMutation({
    mutationFn: ({ id, input }: { id: number | null; input: AdminCourierInput }) => id === null ? apiAdminCouriers.create(input) : apiAdminCouriers.update(id, input),
    onSuccess: async (response) => {
      feedback.notify({ tone: 'success', title: 'Kurirska služba je sačuvana', message: response.data.name });
      reset();
      await Promise.all([
        client.invalidateQueries({ queryKey: ['admin', 'couriers'] }),
        client.invalidateQueries({ queryKey: ['admin', 'orders'] }),
      ]);
    },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Čuvanje nije uspelo', message: error instanceof Error ? error.message : 'Server je odbio izmenu.' }),
  });

  function reset(): void {
    setEditingId(null); setName(''); setTrackingUrl(''); setSortOrder('100'); setActive('1'); setIsDefault('0');
  }
  function edit(courier: AdminCourierService): void {
    setEditingId(courier.id); setName(courier.name); setTrackingUrl(courier.tracking_url); setSortOrder(String(courier.sort_order)); setActive(courier.is_active ? '1' : '0'); setIsDefault(courier.is_default ? '1' : '0');
  }
  function save(): void {
    const order = Number(sortOrder);
    if (!name.trim()) return feedback.notify({ tone: 'warning', title: 'Naziv je obavezan', message: 'Unesi naziv kurirske službe.' });
    if (!trackingUrl.trim().toLowerCase().startsWith('https://')) return feedback.notify({ tone: 'warning', title: 'HTTPS URL je obavezan', message: 'Tracking URL mora početi sa https://.' });
    if (!Number.isInteger(order) || order < 0 || order > 100000) return feedback.notify({ tone: 'warning', title: 'Redosled nije ispravan', message: 'Sort order mora biti ceo broj od 0 do 100000.' });
    mutation.mutate({ id: editingId, input: { name: name.trim(), tracking_url: trackingUrl.trim(), sort_order: order, is_active: active === '1', is_default: isDefault === '1' } });
  }

  if (!allowed) return <UnavailableState title="Kurirske službe su dostupne samo SuperAdministratoru" />;
  if (query.isLoading) return <LoadingState label="Učitavanje kurirskih službi…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  return <Screen contentStyle={styles.content}>
    <Button variant="ghost" onPress={() => router.back()}>Nazad</Button>
    <View style={styles.heading}><Text style={styles.eyebrow}>SUPERADMIN · ŠIFARNICI</Text><Text style={styles.title}>Kurirske službe</Text><Text style={styles.copy}>Centralni šifarnik koristi Shipment UI. Podrazumevana služba mora ostati aktivna, a tracking URL mora koristiti HTTPS.</Text></View>
    <Card style={styles.form}>
      <Text style={styles.sectionTitle}>{editingId === null ? 'Nova kurirska služba' : 'Izmena kurirske službe'}</Text>
      <TextField label="Naziv" value={name} onChangeText={setName} />
      <TextField label="Tracking URL (HTTPS)" value={trackingUrl} onChangeText={setTrackingUrl} autoCapitalize="none" keyboardType="url" />
      <TextField label="Redosled" value={sortOrder} onChangeText={setSortOrder} keyboardType="number-pad" />
      <SelectSheet label="Aktivna" value={active} options={[{ value: '1', label: 'Da' }, { value: '0', label: 'Ne' }]} onChange={(value) => { if (value === '1' || value === '0') setActive(value); }} />
      <SelectSheet label="Podrazumevana" value={isDefault} options={[{ value: '1', label: 'Da' }, { value: '0', label: 'Ne' }]} onChange={(value) => { if (value === '1' || value === '0') setIsDefault(value); }} />
      <Button loading={mutation.isPending} onPress={save}>{editingId === null ? 'Dodaj kurirsku službu' : 'Sačuvaj izmene'}</Button>
      {editingId !== null ? <Button variant="secondary" onPress={reset}>Odustani od izmene</Button> : null}
    </Card>
    <View style={styles.list}>
      {query.data.data.map((courier) => <Card key={courier.id} style={styles.card}>
        <View style={styles.row}><Text style={styles.name}>{courier.name}</Text>{courier.is_default ? <Pill tone="primary">PODRAZUMEVANA</Pill> : courier.is_active ? <Pill tone="success">AKTIVNA</Pill> : <Pill tone="neutral">NEAKTIVNA</Pill>}</View>
        <Text style={styles.meta}>Redosled: {courier.sort_order}</Text><Text style={styles.url}>{courier.tracking_url}</Text>
        <Button variant="secondary" onPress={() => edit(courier)}>Izmeni</Button>
      </Card>)}
    </View>
  </Screen>;
}
function createStyles(theme: AppColors) { return StyleSheet.create({
  content: { paddingBottom: 140, gap: spacing.xl }, heading: { gap: spacing.sm }, eyebrow: { ...typography.small, color: theme.primary, fontWeight: '800' }, title: { ...typography.h1, color: theme.ink }, copy: { ...typography.body, color: theme.muted }, form: { gap: spacing.md }, sectionTitle: { ...typography.h2, color: theme.ink }, list: { gap: spacing.md }, card: { gap: spacing.sm }, row: { flexDirection: 'row', alignItems: 'center', gap: spacing.sm }, name: { ...typography.h3, color: theme.ink, flex: 1 }, meta: { ...typography.small, color: theme.muted }, url: { ...typography.small, color: theme.primary },
}); }
