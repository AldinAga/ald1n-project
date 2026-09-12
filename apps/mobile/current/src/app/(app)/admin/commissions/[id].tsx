import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmAction } from '@/components/ui/confirm-action';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { StatusTimeline, type StatusTimelineItem, type StatusTimelineTone } from '@/components/ui/status-timeline';
import { TextField } from '@/components/ui/text-field';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminCommissions,
  type AdminCommissionPaymentMethod,
  type AdminCommissionTransition,
  type AdminCommissionTransitionInput,
} from '@/features/admin/commissions-admin-api';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { useAuth } from '@/features/auth/auth-provider';
import { formatDate, formatMoney } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';

// BATCH156_SINGLE_PAGE_COMMISSION

function errorMessage(error: unknown, fallback: string): string {
  return error instanceof Error && error.message.trim() ? error.message : fallback;
}

function toneForStatus(status: string): StatusTimelineTone {
  if (status === 'paid') return 'success';
  if (status === 'cancelled') return 'danger';
  if (status === 'approved') return 'primary';
  if (status === 'pending') return 'warning';
  return 'neutral';
}

function rateLabel(value: number | null): string {
  if (value === null || !Number.isFinite(value)) return '—';
  return `${String(value).replace('.', ',')}%`;
}

export default function AdminCommissionDetailScreen() {
  const routeParams = useLocalSearchParams();
  const rawId = Array.isArray(routeParams.id) ? routeParams.id[0] : routeParams.id;
  const commissionId = Number(rawId);
  const validId = Number.isInteger(commissionId) && commissionId > 0;
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const allowed = can('commissions.manage');
  const feedback = useAppFeedback();
  const client = useQueryClient();

  const [paymentMethod, setPaymentMethod] = useState<AdminCommissionPaymentMethod | ''>('');
  const [paymentReference, setPaymentReference] = useState('');
  const [paymentNote, setPaymentNote] = useState('');
  const [approvalNote, setApprovalNote] = useState('');
  const [cancelNote, setCancelNote] = useState('');
  const [returnNote, setReturnNote] = useState('');
  const [pendingAction, setPendingAction] = useState<AdminCommissionTransition | null>(null);
  const [confirmVisible, setConfirmVisible] = useState(false);

  const query = useQuery({
    queryKey: adminQueryKeys.commission(commissionId),
    queryFn: () => apiAdminCommissions.detail(commissionId),
    enabled: allowed && validId,
  });

  const mutation = useMutation({
    mutationFn: (input: AdminCommissionTransitionInput) => apiAdminCommissions.transition(commissionId, input),
    onSuccess: async (updated) => {
      setPaymentMethod('');
      setPaymentReference('');
      setPaymentNote('');
      setApprovalNote('');
      setCancelNote('');
      setReturnNote('');
      setPendingAction(null);
      client.setQueryData(adminQueryKeys.commission(commissionId), updated);
      await client.invalidateQueries({ queryKey: adminQueryKeys.commissions() });
      feedback.notify({ tone: 'success', title: 'Status je ažuriran', message: `Provizija je sada: ${updated.status_label}.` });
    },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Promena statusa nije uspela', message: errorMessage(error, 'Proveri podatke i pokušaj ponovo.') }),
  });

  if (!allowed) return <UnavailableState title="Administracija provizija nije dostupna" />;
  if (!validId) return <UnavailableState title="Neispravan identifikator provizije" />;
  if (query.isLoading) return <LoadingState label="Učitavanje provizije…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const commission = query.data;
  const breakdown = commission.commission_breakdown;
  const paymentOptions = [
    { value: 'bank_transfer', label: 'Prenos na račun' },
    { value: 'cash', label: 'Gotovina' },
    { value: 'other', label: 'Drugo' },
  ];

  const timeline: StatusTimelineItem[] = (commission.history ?? []).map((event, index) => {
    const item: StatusTimelineItem = {
      key: String(event.id),
      title: event.new_status_label,
      tone: toneForStatus(event.new_status),
      current: index === 0,
    };
    if (event.note) item.description = event.note;
    const metaParts = [event.actor_name, event.created_at ? formatDate(event.created_at) : null].filter((value): value is string => Boolean(value));
    if (metaParts.length) item.meta = metaParts.join(' · ');
    return item;
  });

  const requestTransition = (status: AdminCommissionTransition) => {
    if (!commission.allowed_transitions.includes(status)) return;
    if (status === 'paid' && !paymentMethod) {
      feedback.notify({ tone: 'danger', title: 'Način isplate je obavezan', message: 'Izaberi način isplate provizije.' });
      return;
    }
    if (status === 'cancelled' && !cancelNote.trim()) {
      feedback.notify({ tone: 'danger', title: 'Razlog je obavezan', message: 'Unesi razlog storniranja provizije.' });
      return;
    }
    setPendingAction(status);
    setConfirmVisible(true);
  };

  const performTransition = () => {
    if (!pendingAction) return;
    const input: AdminCommissionTransitionInput = { status: pendingAction };
    const actionNote = pendingAction === 'paid'
      ? paymentNote.trim()
      : pendingAction === 'approved'
        ? approvalNote.trim()
        : pendingAction === 'cancelled'
          ? cancelNote.trim()
          : returnNote.trim();

    if (actionNote) input.note = actionNote;
    if (pendingAction === 'paid' && paymentMethod) input.payment_method = paymentMethod;
    if (pendingAction === 'paid' && paymentReference.trim()) input.payment_reference = paymentReference.trim();
    mutation.mutate(input);
  };

  const confirmMessage = pendingAction === 'pending'
    ? 'Vratiti ovu proviziju na čekanje?'
    : pendingAction === 'cancelled'
      ? 'Stornirati ovu proviziju?'
      : pendingAction === 'paid'
        ? `Potvrditi isplatu ${formatMoney(commission.total_eur, 'EUR')}?`
        : 'Odobriti ovu proviziju?';

  const confirmLabel = pendingAction === 'pending'
    ? 'Vrati na čekanje'
    : pendingAction === 'cancelled'
      ? 'Storniraj'
      : pendingAction === 'paid'
        ? 'Potvrdi isplatu'
        : 'Odobri';

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}><Text style={styles.back}>‹ Provizije</Text></Pressable>
      <PageHeader title={`Porudžbina ${commission.order.order_number || '#' + commission.order.id}`} eyebrow="Admin · Provizija" name={bootstrap?.user.name} />

      <Card style={styles.heroCard}>
        <View style={styles.headerRow}>
          <View style={styles.flex}>
            <Text style={styles.eyebrow}>UKUPNA PROVIZIJA</Text>
            <Text style={styles.amount}>{formatMoney(commission.total_eur, 'EUR')}</Text>
          </View>
          <Text style={styles.status}>{commission.status_label}</Text>
        </View>
        <DetailRow label="Korisnik" value={commission.user.name} styles={styles} />
        <DetailRow label="Odgovorno lice" value={commission.responsible_name} styles={styles} />
        {commission.status_note ? <DetailRow label="Statusna napomena" value={commission.status_note} styles={styles} /> : null}
      </Card>

      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Obračun provizije</Text>
        <Text style={styles.copy}>Prikaz koristi sačuvane snapshot vrednosti iz trenutka porudžbine; konačna provizija ostaje server authority.</Text>
        {breakdown ? (
          <>
            <View style={styles.itemsList}>
              {breakdown.items.map((item) => (
                <View key={String(item.id)} style={styles.itemCard}>
                  <Text style={styles.itemName}>{item.product_name}</Text>
                  <Text style={styles.meta}>SKU: {item.product_sku ?? '—'} · Količina: {item.quantity}</Text>
                  <DetailRow label="Vrednost stavke" value={formatMoney(item.line_total_rsd, 'RSD')} styles={styles} />
                  <DetailRow label="Stopa provizije" value={rateLabel(item.commission_rate_percent_snapshot)} styles={styles} />
                  <DetailRow label="Provizija stavke" value={item.commission_total_eur_snapshot === null ? '—' : formatMoney(item.commission_total_eur_snapshot, 'EUR')} styles={styles} />
                </View>
              ))}
            </View>
            {breakdown.has_adjustment ? (
              <View style={styles.adjustmentBox}>
                <Text style={styles.itemName}>Korekcija / istorijsko usklađenje</Text>
                <Text style={styles.copy}>Zbir snapshot provizija stavki razlikuje se od konačne provizije. Konačni server iznos ostaje merodavan.</Text>
                <DetailRow label="Zbir stavki" value={formatMoney(breakdown.items_commission_total_eur, 'EUR')} styles={styles} />
                <DetailRow label="Korekcija" value={formatMoney(breakdown.adjustment_eur, 'EUR')} styles={styles} />
              </View>
            ) : null}
            <View style={styles.totalBox}>
              <DetailRow label="UKUPNA VREDNOST PORUDŽBINE" value={formatMoney(breakdown.order_subtotal_rsd, 'RSD')} styles={styles} strong />
              <DetailRow label="UKUPNA PROVIZIJA" value={formatMoney(breakdown.final_commission_total_eur, 'EUR')} styles={styles} strong />
            </View>
          </>
        ) : (
          <Text style={styles.copy}>Detaljni obračun trenutno nije dostupan. Osveži ekran pre evidentiranja isplate.</Text>
        )}
      </Card>

      {commission.status === 'pending' ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Odobravanje provizije</Text>
          <Text style={styles.copy}>Provizija mora prvo biti odobrena. Posle odobravanja ovaj ekran odmah prelazi na unos isplate.</Text>
          <TextField label="Napomena" value={approvalNote} onChangeText={setApprovalNote} placeholder="Opcionalno" multiline numberOfLines={3} />
          {commission.allowed_transitions.includes('approved') ? <Button loading={mutation.isPending} onPress={() => requestTransition('approved')}>ODOBRI PROVIZIJU</Button> : <Text style={styles.copy}>Server trenutno ne dozvoljava odobravanje.</Text>}
        </Card>
      ) : null}

      {commission.status === 'approved' ? (
        <Card style={styles.paymentCard}>
          <Text style={styles.sectionTitle}>Isplata provizije</Text>
          <Text style={styles.copy}>Sve potrebno za završetak isplate je na ovom ekranu.</Text>
          <SelectSheet label="Način isplate" value={paymentMethod} options={paymentOptions} onChange={(value) => { if (value === 'bank_transfer' || value === 'cash' || value === 'other') setPaymentMethod(value); }} />
          <TextField label="Referenca" value={paymentReference} onChangeText={setPaymentReference} placeholder="Opcionalno" />
          <TextField label="Napomena" value={paymentNote} onChangeText={setPaymentNote} placeholder="Opcionalno" multiline numberOfLines={3} />
          {commission.allowed_transitions.includes('paid') ? <Button disabled={!paymentMethod || !breakdown} loading={mutation.isPending} onPress={() => requestTransition('paid')}>POTVRDI ISPLATU PROVIZIJE</Button> : <Text style={styles.copy}>Server trenutno ne dozvoljava isplatu.</Text>}
        </Card>
      ) : null}

      {commission.status === 'paid' ? (
        <Card style={styles.paidCard}>
          <Text style={styles.sectionTitle}>ISPLAĆENO</Text>
          <DetailRow label="Iznos" value={formatMoney(commission.total_eur, 'EUR')} styles={styles} />
          <DetailRow label="Način" value={commission.payment?.method_label ?? '—'} styles={styles} />
          <DetailRow label="Referenca" value={commission.payment?.reference ?? '—'} styles={styles} />
          <DetailRow label="Batch" value={commission.payment?.batch_number ?? '—'} styles={styles} />
          <DetailRow label="Datum" value={commission.payment?.paid_at ? formatDate(commission.payment.paid_at) : '—'} styles={styles} />
        </Card>
      ) : null}

      {commission.status === 'cancelled' ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Provizija je stornirana</Text>
          <Text style={styles.copy}>{commission.status_note ?? 'Nema dodatne statusne napomene.'}</Text>
        </Card>
      ) : null}

      {commission.allowed_transitions.includes('cancelled') ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Storniranje</Text>
          <Text style={styles.copy}>Sekundarna administrativna akcija. Razlog je obavezan i biće sačuvan u istoriji.</Text>
          <TextField label="Razlog storniranja" value={cancelNote} onChangeText={setCancelNote} multiline numberOfLines={3} />
          <Button variant="secondary" loading={mutation.isPending} onPress={() => requestTransition('cancelled')}>Storniraj proviziju</Button>
        </Card>
      ) : null}

      {commission.status === 'cancelled' && commission.allowed_transitions.includes('pending') ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Vrati na čekanje</Text>
          <TextField label="Napomena" value={returnNote} onChangeText={setReturnNote} placeholder="Opcionalno" multiline numberOfLines={3} />
          <Button variant="secondary" loading={mutation.isPending} onPress={() => requestTransition('pending')}>Vrati proviziju na čekanje</Button>
        </Card>
      ) : null}

      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Istorija statusa</Text>
        <StatusTimeline items={timeline} />
      </Card>

      <ConfirmAction
        visible={confirmVisible}
        title="Potvrdi promenu statusa"
        message={confirmMessage}
        confirmLabel={confirmLabel}
        destructive={pendingAction === 'cancelled'}
        busy={mutation.isPending}
        onCancel={() => { setConfirmVisible(false); setPendingAction(null); }}
        onConfirm={() => { setConfirmVisible(false); performTransition(); }}
      />
    </Screen>
  );
}

function DetailRow({ label, value, styles, strong = false }: { label: string; value: string; styles: ReturnType<typeof createStyles>; strong?: boolean }) {
  return (
    <View style={styles.detailRow}>
      <Text style={strong ? styles.strongLabel : styles.label}>{label}</Text>
      <Text style={strong ? styles.strongValue : styles.value}>{value}</Text>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 140, gap: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    heroCard: { gap: spacing.md, borderColor: theme.primary },
    card: { gap: spacing.md },
    paymentCard: { gap: spacing.md, borderColor: theme.primary, borderWidth: 1 },
    paidCard: { gap: spacing.md, borderColor: theme.primary, borderWidth: 1 },
    headerRow: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
    flex: { flex: 1 },
    eyebrow: { ...typography.small, color: theme.muted },
    amount: { ...typography.h2, color: theme.ink, marginTop: spacing.xs },
    status: { ...typography.label, color: theme.primary },
    sectionTitle: { ...typography.h3, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
    itemsList: { gap: spacing.sm },
    itemCard: { gap: spacing.xs, padding: spacing.md, borderRadius: 14, backgroundColor: theme.surfaceContainer },
    itemName: { ...typography.label, color: theme.ink },
    meta: { ...typography.small, color: theme.muted },
    adjustmentBox: { gap: spacing.sm, padding: spacing.md, borderRadius: 14, backgroundColor: theme.surfaceContainer },
    totalBox: { gap: spacing.md, paddingTop: spacing.sm },
    detailRow: { gap: 2 },
    label: { ...typography.small, color: theme.muted },
    value: { ...typography.body, color: theme.ink },
    strongLabel: { ...typography.label, color: theme.muted },
    strongValue: { ...typography.h3, color: theme.ink },
  });
}
