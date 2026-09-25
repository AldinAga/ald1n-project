import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Pill, type PillTone } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiAdminServiceParts } from '@/features/admin/service-parts-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { formatDate } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';

type Transition = 'submit' | 'order' | 'receive' | 'cancel' | null;
function tone(status: string): PillTone { if (status === 'received') return 'success'; if (status === 'cancelled') return 'danger'; if (status === 'ordered') return 'info'; if (status === 'submitted') return 'warning'; return 'primary'; }
function qty(value: number | null): string { return value === null ? '—' : Number(value).toLocaleString('sr-RS', { maximumFractionDigits: 2 }); }
function money(value: number | null): string { return value === null ? '—' : `${Number(value).toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} RSD`; }

export default function AdminServicePartPurchaseDetailScreen() {
  const params = useLocalSearchParams<{ id?: string | string[] }> ();
  const rawId = Array.isArray(params.id) ? params.id[0] : params.id;
  const purchaseId = Number(rawId);
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { bootstrap, can } = useAuth();
  const allowed = can('service_parts.procurement');
  const [confirm, setConfirm] = useState<Transition> (null);
  const [cancelReason, setCancelReason] = useState('');
  const query = useQuery({ queryKey: adminQueryKeys.servicePartPurchase(purchaseId), queryFn: () => apiAdminServiceParts.purchaseDetail(purchaseId), enabled: allowed && Number.isInteger(purchaseId) && purchaseId > 0 });
  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });

  if (!allowed) return <UnavailableState title="Nabavka nije dostupna" />;
  if (!Number.isInteger(purchaseId) || purchaseId <= 0) return <UnavailableState title="Neispravan identifikator nabavke" />;
  if (query.isLoading) return <LoadingState label="Učitavanje nabavke…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  const response = query.data;
  const purchase = response.data;

  const execute = async () => {
    if (!confirm) return;
    try {
      if (confirm === 'submit') await mutation.mutateAsync(() => apiAdminServiceParts.purchaseSubmit(purchaseId));
      if (confirm === 'order') await mutation.mutateAsync(() => apiAdminServiceParts.purchaseOrder(purchaseId));
      if (confirm === 'receive') await mutation.mutateAsync(() => apiAdminServiceParts.purchaseReceive(purchaseId));
      if (confirm === 'cancel') await mutation.mutateAsync(() => apiAdminServiceParts.purchaseCancel(purchaseId, cancelReason));
      await client.invalidateQueries({ queryKey: adminQueryKeys.servicePartPurchasesRoot() });
      await client.invalidateQueries({ queryKey: adminQueryKeys.servicePartPurchase(purchaseId) });
      setConfirm(null);
      setCancelReason('');
      feedback.notify({ tone: 'success', title: 'Status nabavke je promenjen', message: 'Promena je izvršena kroz postojeći poslovni state machine.' });
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Promena statusa nije izvršena', message: `${error instanceof Error ? error.message : 'Pokušaj ponovo.'} Potvrda i razlog ostaju otvoreni za retry.` });
    }
  };

  const confirmText = confirm === 'receive'
    ? 'Prijem će uvećati fizičko stanje, napraviti idempotentni purchase_receipt movement i preračunati ponderisanu prosečnu cenu.'
    : confirm === 'order'
      ? 'Nabavka prelazi iz submitted u ordered.'
      : confirm === 'submit'
        ? 'Nacrt prelazi iz draft u submitted.'
        : 'Otkazivanje je dozvoljeno samo za draft, submitted ili ordered nabavku.';

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}><Text style={styles.back}>‹ Nabavke</Text></Pressable>
      <PageHeader title={purchase.request_number || 'Nabavka'} eyebrow="Admin · Servisni delovi" name={bootstrap?.user.name} />
      <Card style={styles.card}>
        <View style={styles.rowBetween}><View style={styles.grow}><Text style={styles.sectionTitle}>{purchase.supplier?.name ?? 'Dobavljač nije izabran'}</Text><Text style={styles.meta}>Ukupno {money(purchase.total_cost_rsd)}</Text></View><Pill tone={tone(purchase.status)}>{purchase.status}</Pill></View>
        <Text style={styles.meta}>Očekivano: {purchase.expected_at ? formatDate(purchase.expected_at) : '—'}</Text>
        <Text style={styles.meta}>Poslato: {purchase.submitted_at ? formatDate(purchase.submitted_at, true) : '—'} · Naručeno: {purchase.ordered_at ? formatDate(purchase.ordered_at, true) : '—'}</Text>
        <Text style={styles.meta}>Primljeno: {purchase.received_at ? formatDate(purchase.received_at, true) : '—'} · Otkazano: {purchase.cancelled_at ? formatDate(purchase.cancelled_at, true) : '—'}</Text>
        {purchase.notes ? <Text style={styles.copy}>{purchase.notes}</Text> : null}
        {purchase.cancellation_reason ? <Text style={styles.copy}>Razlog otkazivanja: {purchase.cancellation_reason}</Text> : null}
      </Card>

      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Dozvoljene akcije</Text>
        <Text style={styles.meta}>Server capabilities određuju sledeći legalan korak. Ne postoji approve faza.</Text>
        <View style={styles.actions}>
          {response.capabilities.can_submit ? <Button onPress={() => setConfirm('submit')}>Pošalji zahtev</Button> : null}
          {response.capabilities.can_order ? <Button onPress={() => setConfirm('order')}>Označi kao naručeno</Button> : null}
          {response.capabilities.can_receive ? <Button onPress={() => setConfirm('receive')}>Evidentiraj prijem</Button> : null}
          {response.capabilities.can_cancel ? <Button variant="secondary" onPress={() => setConfirm('cancel')}>Otkaži nabavku</Button> : null}
          <Button variant="secondary" loading={query.isFetching} onPress={() => void query.refetch()}>Osveži</Button>
        </View>
      </Card>

      {confirm ? <Card style={styles.warningCard}>
        <Text style={styles.sectionTitle}>Potvrdi akciju: {confirm}</Text>
        <Text style={styles.copy}>{confirmText}</Text>
        {confirm === 'cancel' ? <TextField label="Razlog otkazivanja" value={cancelReason} onChangeText={setCancelReason} multiline /> : null}
        <View style={styles.actions}><Button loading={mutation.isPending} onPress={() => void execute()}>Potvrdi</Button><Button variant="secondary" onPress={() => setConfirm(null)}>Odustani</Button></View>
      </Card> : null}

      <Text style={styles.sectionTitle}>Stavke</Text>
      <View style={styles.list}>{purchase.items.map((item) => <Card key={item.id} style={styles.card}><Text style={styles.title}>{item.service_part ? `${item.service_part.sku} · ${item.service_part.name}` : 'Servisni deo nije dostupan'}</Text><Text style={styles.meta}>Naručeno {qty(item.ordered_quantity)} · Primljeno {qty(item.received_quantity)} {item.service_part?.unit ?? ''}</Text><Text style={styles.meta}>Jedinična cena {money(item.unit_cost_rsd)} · Stavka {money(item.line_total_rsd)}</Text></Card>)}</View>
    </Screen>
  );
}

function createStyles(theme: AppColors) { return StyleSheet.create({ content: { gap: spacing.lg, paddingBottom: spacing.xxxl }, back: { ...typography.small, color: theme.primary, fontWeight: '800' }, copy: { ...typography.body, color: theme.muted, lineHeight: 22 }, sectionTitle: { ...typography.h3, color: theme.ink }, title: { ...typography.body, color: theme.ink, fontWeight: '800' }, meta: { ...typography.small, color: theme.muted }, card: { gap: spacing.md }, warningCard: { gap: spacing.md, borderColor: theme.primary }, list: { gap: spacing.md }, actions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }, rowBetween: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md }, grow: { flex: 1, minWidth: 0 } }); }
