import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { ActionSheet, type SheetAction } from '@/components/ui/action-sheet';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmAction } from '@/components/ui/confirm-action';
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
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

// MOBILE_V1_0_ADMIN_COMMISSIONS_DETAIL_UX_REORGANIZATION_BATCH92
type CommissionDetailWorkspace = 'overview' | 'details' | 'payment' | 'actions' | 'history';

const COMMISSION_DETAIL_WORKSPACE_OPTIONS: Array<{ value: CommissionDetailWorkspace; label: string; description: string }> = [
  { value: 'overview', label: 'Pregled', description: 'Iznos, status i kljucni kontekst provizije na jednom mestu.' },
  { value: 'details', label: 'Podaci', description: 'Korisnik, odgovorno lice, statusna napomena i ostali osnovni podaci.' },
  { value: 'payment', label: 'Isplata', description: 'Evidentirani nacin isplate, referenca, batch i datum isplate.' },
  { value: 'actions', label: 'Akcije', description: 'Server-driven promene statusa sa postojecim validacijama i potvrdom.' },
  { value: 'history', label: 'Istorija', description: 'Kompletna postojeca statusna istorija i akteri promena.' },
];

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

  const [actionSheet, setActionSheet] = useState(false);
  const [formAction, setFormAction] = useState<AdminCommissionTransition | null> (null);
  const [note, setNote] = useState('');
  const [paymentMethod, setPaymentMethod] = useState<AdminCommissionPaymentMethod | ''> ('');
  const [paymentReference, setPaymentReference] = useState('');
  const [confirmAction, setConfirmAction] = useState(false);
  const [workspace, setWorkspace] = useState<CommissionDetailWorkspace> ('overview');

  const query = useQuery({
    queryKey: adminQueryKeys.commission(commissionId),
    queryFn: () => apiAdminCommissions.detail(commissionId),
    enabled: allowed && validId,
  });

  const mutation = useMutation({
    mutationFn: (input: AdminCommissionTransitionInput) => apiAdminCommissions.transition(commissionId, input),
    onSuccess: async (updated) => {
      setFormAction(null); setNote(''); setPaymentMethod(''); setPaymentReference('');
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
  const paymentOptions = [
    { value: 'bank_transfer', label: 'Prenos na račun' },
    { value: 'cash', label: 'Gotovina' },
    { value: 'other', label: 'Drugo' },
  ];

  const actions: SheetAction[] = commission.allowed_transitions.map((status) => {
    const action: SheetAction = {
      key: status,
      label: status === 'pending' ? 'Vrati na čekanje' : status === 'approved' ? 'Odobri' : status === 'paid' ? 'Isplati' : 'Storniraj',
      tone: status === 'cancelled' ? 'danger' : 'default',
    };
    if (status === 'cancelled') action.description = 'Storniranje zahteva razlog.';
    return action;
  });

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

  const submitTransition = () => {
    if (!formAction) return;
    if (formAction === 'cancelled' && !note.trim()) {
      feedback.notify({ tone: 'danger', title: 'Razlog je obavezan', message: 'Unesi razlog storniranja provizije.' });
      return;
    }
    if (formAction === 'paid' && !paymentMethod) {
      feedback.notify({ tone: 'danger', title: 'Način isplate je obavezan', message: 'Izaberi način isplate provizije.' });
      return;
    }
    setConfirmAction(true);
  };

  const performTransition = () => {
    if (!formAction) return;
    const input: AdminCommissionTransitionInput = { status: formAction };
    if (note.trim()) input.note = note.trim();
    if (formAction === 'paid' && paymentMethod) input.payment_method = paymentMethod;
    if (formAction === 'paid' && paymentReference.trim()) input.payment_reference = paymentReference.trim();
    mutation.mutate(input);
  };

  const workspaceMeta = COMMISSION_DETAIL_WORKSPACE_OPTIONS.find((option) => option.value === workspace);

  const selectWorkspace = (next: CommissionDetailWorkspace) => {
    setActionSheet(false);
    setConfirmAction(false);
    if (next !== 'actions') {
      setFormAction(null);
      setNote('');
      setPaymentMethod('');
      setPaymentReference('');
    }
    setWorkspace(next);
  };

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}><Text style={styles.back}>‹ Provizije</Text></Pressable>
      <PageHeader title={commission.order.order_number || 'Provizija #' + commission.id} eyebrow="Admin · Provizija" name={bootstrap?.user.name} />

      <Card style={styles.workspaceCard}>
        <Text style={styles.sectionTitle}>Radni prostor provizije</Text>
        <Text style={styles.copy}>{workspaceMeta?.description ?? 'Izaberi deo provizije koji zelis da obradis.'}</Text>
        <FilterBar>
          {COMMISSION_DETAIL_WORKSPACE_OPTIONS.map((option) => (
            <FilterChip key={option.value} label={option.label} active={workspace === option.value} onPress={() => selectWorkspace(option.value)} />
          ))}
        </FilterBar>
      </Card>

      {workspace === 'overview' ? (
        <>
          <Card style={styles.card}>
            <View style={styles.headerRow}><Text style={styles.amount}>{formatMoney(commission.total_eur, 'EUR')}</Text><Text style={styles.status}>{commission.status_label}</Text></View>
            <DetailRow label="Porudžbina" value={commission.order.order_number || '—'} styles={styles} />
            <DetailRow label="Korisnik" value={commission.user.name} styles={styles} />
            <DetailRow label="Odgovorno lice" value={commission.responsible_name} styles={styles} />
            <DetailRow label="Ažurirano" value={commission.status_updated_at ? formatDate(commission.status_updated_at) : '—'} styles={styles} />
          </Card>
          <Card style={styles.overviewCard}>
            <Text style={styles.sectionTitle}>Brzi pristup</Text>
            <View style={styles.actionsRow}>
              <Button onPress={() => selectWorkspace('details')}>Podaci</Button>
              <Button variant="secondary" onPress={() => selectWorkspace('payment')}>Isplata</Button>
              <Button variant="secondary" onPress={() => selectWorkspace('history')}>Istorija</Button>
              {commission.allowed_transitions.length > 0 ? <Button variant="secondary" onPress={() => selectWorkspace('actions')}>Akcije</Button> : null}
            </View>
          </Card>
        </>
      ) : null}

      {workspace === 'details' ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Podaci o proviziji</Text>
          <DetailRow label="Iznos" value={formatMoney(commission.total_eur, 'EUR')} styles={styles} />
          <DetailRow label="Status" value={commission.status_label} styles={styles} />
          <DetailRow label="Korisnik" value={commission.user.name} styles={styles} />
          <DetailRow label="E-mail" value={commission.user.email ?? '—'} styles={styles} />
          <DetailRow label="Odgovorno lice" value={commission.responsible_name} styles={styles} />
          <DetailRow label="Ažurirano" value={commission.status_updated_at ? formatDate(commission.status_updated_at) : '—'} styles={styles} />
          {commission.status_note ? <DetailRow label="Napomena" value={commission.status_note} styles={styles} /> : null}
        </Card>
      ) : null}

      {workspace === 'payment' ? (
        commission.payment ? (
          <Card style={styles.card}>
            <Text style={styles.sectionTitle}>Isplata</Text>
            <DetailRow label="Način" value={commission.payment.method_label ?? '—'} styles={styles} />
            <DetailRow label="Referenca" value={commission.payment.reference ?? '—'} styles={styles} />
            <DetailRow label="Batch" value={commission.payment.batch_number ?? '—'} styles={styles} />
            <DetailRow label="Datum" value={commission.payment.paid_at ? formatDate(commission.payment.paid_at) : '—'} styles={styles} />
          </Card>
        ) : (
          <Card style={styles.card}>
            <Text style={styles.sectionTitle}>Isplata još nije evidentirana</Text>
            <Text style={styles.copy}>Ako server dozvoljava prelaz u status isplaćeno, koristi radni prostor Akcije.</Text>
            {commission.allowed_transitions.includes('paid') ? <Button onPress={() => selectWorkspace('actions')}>Otvori akcije</Button> : null}
          </Card>
        )
      ) : null}

      {workspace === 'actions' ? (
        <>
          <Card style={styles.card}>
            <Text style={styles.sectionTitle}>Promena statusa</Text>
            <Text style={styles.copy}>Dostupne akcije dolaze sa servera i ponovo se proveravaju pri izvršenju.</Text>
            {commission.allowed_transitions.length > 0 ? <Button onPress={() => setActionSheet(true)}>Izaberi akciju</Button> : <Text style={styles.copy}>Za trenutni status nema dozvoljenih prelaza.</Text>}
          </Card>

          {formAction ? (
            <Card style={styles.card}>
              <Text style={styles.sectionTitle}>{formAction === 'pending' ? 'Vraćanje na čekanje' : formAction === 'approved' ? 'Odobravanje' : formAction === 'paid' ? 'Isplata provizije' : 'Storniranje'}</Text>
              {formAction === 'paid' ? (
                <>
                  <SelectSheet label="Način isplate" value={paymentMethod} options={paymentOptions} onChange={(value) => { if (value === 'bank_transfer' || value === 'cash' || value === 'other') setPaymentMethod(value); }} />
                  <TextField label="Referenca" value={paymentReference} onChangeText={setPaymentReference} placeholder="Opcionalno" />
                </>
              ) : null}
              <TextField label={formAction === 'cancelled' ? 'Razlog storniranja' : 'Napomena'} value={note} onChangeText={setNote} multiline numberOfLines={3} />
              <View style={styles.actionsRow}><Button variant="secondary" onPress={() => setFormAction(null)}>Odustani</Button><Button loading={mutation.isPending} onPress={submitTransition}>Nastavi</Button></View>
            </Card>
          ) : null}
        </>
      ) : null}

      {workspace === 'history' ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Istorija statusa</Text>
          <StatusTimeline items={timeline} />
        </Card>
      ) : null}

      <ActionSheet visible={actionSheet} title="Akcija nad provizijom" message="Server će ponovo proveriti dozvoljeni prelaz statusa." actions={actions} onClose={() => setActionSheet(false)} onSelect={(key) => { setActionSheet(false); if (key === 'pending' || key === 'approved' || key === 'paid' || key === 'cancelled') { setWorkspace('actions'); setFormAction(key); setNote(''); setPaymentMethod(''); setPaymentReference(''); } }} />
      <ConfirmAction visible={confirmAction} title="Potvrdi promenu statusa" message={formAction === 'pending' ? 'Vratiti ovu proviziju na čekanje?' : formAction === 'cancelled' ? 'Stornirati ovu proviziju?' : formAction === 'paid' ? 'Označiti ovu proviziju kao isplaćenu?' : 'Odobriti ovu proviziju?'} confirmLabel="Potvrdi" destructive={formAction === 'cancelled'} busy={mutation.isPending} onCancel={() => setConfirmAction(false)} onConfirm={() => { setConfirmAction(false); performTransition(); }} />
    </Screen>
  );
}

function DetailRow({ label, value, styles }: { label: string; value: string; styles: ReturnType<typeof createStyles> }) {
  return <View style={styles.detailRow}><Text style={styles.label}>{label}</Text><Text style={styles.value}>{value}</Text></View>;
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 140, gap: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    workspaceCard: { gap: spacing.md, borderColor: theme.primary },
    overviewCard: { gap: spacing.md },
    copy: { ...typography.body, color: theme.muted },
    card: { gap: spacing.md },
    headerRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
    amount: { ...typography.h2, color: theme.ink, flex: 1 },
    status: { ...typography.label, color: theme.primary },
    sectionTitle: { ...typography.h3, color: theme.ink },
    detailRow: { gap: 2 },
    label: { ...typography.small, color: theme.muted },
    value: { ...typography.body, color: theme.ink },
    actionsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  });
}
