import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { DateTimeField } from '@/components/ui/date-time-field';
import { FilterChip } from '@/components/ui/filter-bar';
import { MoneyField } from '@/components/ui/money-field';
import { Pill, type PillTone } from '@/components/ui/pill';
import { SelectSheet } from '@/components/ui/select-sheet';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminReceivables,
  type AdminReceivableContact,
  type AdminReceivableInstallment,
} from '@/features/admin/receivables-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { formatDate } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';

type Panel = 'update' | 'plan' | 'contact' | 'reminder' | null;
type PlanDraftRow = { key: number; due_at: string; amount_rsd: string; note: string };

function money(value: number): string {
  return `${Number(value || 0).toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} RSD`;
}

function tone(status: string): PillTone {
  if (status === 'closed') return 'success';
  if (status === 'escalated' || status === 'disputed') return 'danger';
  if (status === 'promised') return 'warning';
  if (status === 'installment_plan') return 'info';
  return 'primary';
}

function compact(value: string): string | null {
  const normalized = value.trim();
  return normalized ? normalized : null;
}

function positiveAmount(value: string, label: string): number {
  const parsed = Number(value.trim().replace(',', '.'));
  if (!Number.isFinite(parsed) || parsed <= 0) throw new Error(`${label} mora biti broj veći od nule.`);
  return parsed;
}

function dateOnly(value: string | null): string {
  if (!value) return '';
  return value.slice(0, 10);
}

function InstallmentRow({ item }: { item: AdminReceivableInstallment }) {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  return (
    <View style={styles.lineRow}>
      <View style={styles.grow}>
        <Text style={styles.itemTitle}>Rata {item.sequence_no} · {money(item.amount_rsd)}</Text>
        <Text style={styles.meta}>Dospelo: {item.due_at ? formatDate(item.due_at) : '—'} · Plaćeno: {money(item.paid_amount_rsd)}</Text>
        <Text style={styles.meta}>Status: {item.status}{item.paid_at ? ` · ${formatDate(item.paid_at, true)}` : ''}</Text>
        {item.note ? <Text style={styles.body}>{item.note}</Text> : null}
      </View>
    </View>
  );
}

function ContactRow({ item }: { item: AdminReceivableContact }) {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  return (
    <View style={styles.lineRow}>
      <View style={styles.grow}>
        <Text style={styles.itemTitle}>{item.subject ?? item.channel} · {item.direction}</Text>
        {item.note ? <Text style={styles.body}>{item.note}</Text> : null}
        <Text style={styles.meta}>{item.user?.name ?? 'Sistem'} · {item.contacted_at ? formatDate(item.contacted_at, true) : '—'}</Text>
        <Text style={styles.meta}>{item.is_automatic ? 'Automatski zapis' : 'Ručni zapis'} · {item.visible_to_customer ? 'Vidljivo kupcu' : 'Interno'}</Text>
      </View>
    </View>
  );
}

export default function AdminReceivablesDetailScreen() {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { id } = useLocalSearchParams<{ id: string }> ();
  const receivableId = Number(id);
  const { bootstrap, can } = useAuth();
  const allowed = can('receivables.manage');
  const validId = Number.isInteger(receivableId) && receivableId > 0;

  const [panel, setPanel] = useState<Panel> (null);
  const [status, setStatus] = useState('');
  const [assignedTo, setAssignedTo] = useState('');
  const [nextActionAt, setNextActionAt] = useState('');
  const [promisedPaymentAt, setPromisedPaymentAt] = useState('');
  const [internalNote, setInternalNote] = useState('');

  const [planRows, setPlanRows] = useState<PlanDraftRow[]> ([]);
  const [planKey, setPlanKey] = useState(1);

  const [contactChannel, setContactChannel] = useState('');
  const [contactDirection, setContactDirection] = useState('');
  const [contactSubject, setContactSubject] = useState('');
  const [contactNote, setContactNote] = useState('');
  const [contactVisible, setContactVisible] = useState(false);

  const [reminderMessage, setReminderMessage] = useState('');

  const query = useQuery({
    queryKey: adminQueryKeys.receivable(receivableId),
    queryFn: () => apiAdminReceivables.detail(receivableId),
    enabled: allowed && validId,
  });
  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });

  const refreshAfterMutation = async () => {
    await client.invalidateQueries({ queryKey: adminQueryKeys.receivables() });
  };

  const execute = async (title: string, run: () => Promise<unknown>): Promise<boolean> => {
    try {
      await mutation.mutateAsync(run);
      await refreshAfterMutation();
      setPanel(null);
      feedback.notify({ tone: 'success', title, message: 'Promena je potvrđena na serveru.' });
      return true;
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Akcija nije uspela', message: error instanceof Error ? error.message : 'Pokušaj ponovo.' });
      return false;
    }
  };

  if (!allowed) return <UnavailableState title="Potraživanja nisu dostupna" />;
  if (!validId) return <ErrorState error={new Error('Neispravan identifikator predmeta naplate.')} />;
  if (query.isLoading) return <LoadingState label="Učitavanje predmeta naplate…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const response = query.data;
  const data = response.data;
  const statusOptions = Object.entries(response.options.statuses).map(([value, label]) => ({ value, label }));
  const assigneeOptions = [
    { value: '', label: 'Nije dodeljeno' },
    ...response.options.assignees.map((user) => ({ value: String(user.id), label: user.name })),
  ];

  const openUpdate = () => {
    setStatus(data.status);
    setAssignedTo(data.assigned_to ? String(data.assigned_to.id) : '');
    setNextActionAt(data.next_action_at ?? '');
    setPromisedPaymentAt(data.promised_payment_at ?? '');
    setInternalNote(data.internal_note ?? '');
    setPanel('update');
  };

  const openPlan = () => {
    let key = 1;
    const rows = data.installments.length > 0
      ? data.installments.map((item) => ({ key: key++, due_at: dateOnly(item.due_at), amount_rsd: String(item.amount_rsd), note: item.note ?? '' }))
      : [{ key: key++, due_at: '', amount_rsd: data.remaining_rsd > 0 ? String(data.remaining_rsd) : '', note: '' }];
    setPlanRows(rows);
    setPlanKey(key);
    setPanel('plan');
  };

  const openContact = () => {
    setContactChannel('');
    setContactDirection('');
    setContactSubject('');
    setContactNote('');
    setContactVisible(false);
    setPanel('contact');
  };

  const openReminder = () => {
    setReminderMessage('');
    setPanel('reminder');
  };

  const submitUpdate = () => void execute('Predmet je ažuriran', () => apiAdminReceivables.update(receivableId, {
    status,
    assigned_to: assignedTo ? Number(assignedTo) : null,
    next_action_at: compact(nextActionAt),
    promised_payment_at: compact(promisedPaymentAt),
    internal_note: compact(internalNote),
  }));

  const addPlanRow = () => {
    if (planRows.length >= response.options.max_installments) return;
    setPlanRows((current) => [...current, { key: planKey, due_at: '', amount_rsd: '', note: '' }]);
    setPlanKey((current) => current + 1);
  };

  const updatePlanRow = (key: number, field: 'due_at' | 'amount_rsd' | 'note', value: string) => {
    setPlanRows((current) => current.map((row) => row.key === key ? { ...row, [field]: value } : row));
  };

  const removePlanRow = (key: number) => {
    setPlanRows((current) => current.filter((row) => row.key !== key));
  };

  const submitPlan = () => {
    try {
      if (planRows.length < 1 || planRows.length > response.options.max_installments) {
        throw new Error(`Plan mora imati između 1 i ${response.options.max_installments} rata.`);
      }
      const installments = planRows.map((row, index) => {
        const due = row.due_at.trim();
        if (!/^\d{4}-\d{2}-\d{2}$/.test(due)) throw new Error(`Rata ${index + 1}: datum mora biti YYYY-MM-DD.`);
        return {
          due_at: due,
          amount_rsd: positiveAmount(row.amount_rsd, `Rata ${index + 1}`),
          note: compact(row.note),
        };
      });
      void execute('Plan otplate je sačuvan', () => apiAdminReceivables.replacePlan(receivableId, { installments }));
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Plan nije spreman', message: error instanceof Error ? error.message : 'Proveri rate.' });
    }
  };

  const submitContact = () => {
    const channel = contactChannel.trim();
    const direction = contactDirection.trim();
    const note = contactNote.trim();
    if (!channel || !direction || !note) {
      feedback.notify({ tone: 'warning', title: 'Nedostaju podaci', message: 'Kanal, smer i napomena su obavezni.' });
      return;
    }
    void execute('Komunikacija je evidentirana', () => apiAdminReceivables.addContact(receivableId, {
      channel,
      direction,
      subject: compact(contactSubject),
      note,
      visible_to_customer: contactVisible,
    }));
  };

  const submitReminder = () => void execute('Podsetnik je prosleđen u postojeći outbox', () => apiAdminReceivables.sendReminder(receivableId, {
    message: compact(reminderMessage),
  }));

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}><Text style={styles.back}>‹ Potraživanja</Text></Pressable>
      <PageHeader title={data.case_number} eyebrow="Admin · Potraživanja" name={bootstrap?.user.name} />

      <Card style={styles.heroCard}>
        <View style={styles.rowBetween}>
          <View style={styles.grow}>
            <Text style={styles.heroValue}>{money(data.remaining_rsd)}</Text>
            <Text style={styles.meta}>Preostalo potraživanje</Text>
          </View>
          <Pill tone={tone(data.status)}>{data.status_label}</Pill>
        </View>
        <Text style={styles.meta}>Porudžbina: {data.order?.order_number ?? '—'}</Text>
        <Text style={styles.meta}>Kupac: {data.order?.customer?.name ?? '—'}</Text>
        <Text style={styles.meta}>Dospelo: {data.order?.payment_due_at ? formatDate(data.order.payment_due_at) : '—'} · Kašnjenje {data.days_overdue} dana</Text>
        <Text style={styles.meta}>Aging: {data.aging_bucket} · Faza naplate: {data.collection_stage}</Text>
        <Text style={styles.meta}>Odgovorno lice: {data.assigned_to?.name ?? data.order?.supplier?.name ?? 'Nije dodeljeno'}</Text>
        <Text style={styles.meta}>Sledeća akcija: {data.next_action_at ? formatDate(data.next_action_at, true) : '—'}</Text>
        {data.promised_payment_at ? <Text style={styles.meta}>Obećana uplata: {formatDate(data.promised_payment_at, true)}</Text> : null}
        {data.order ? (
          <Button variant="secondary" onPress={() => router.push({ pathname: '/admin/orders/[id]', params: { id: String(data.order?.id) } })}>Otvori porudžbinu</Button>
        ) : null}
      </Card>

      <Card style={styles.actionsCard}>
        <Text style={styles.sectionTitle}>Akcije predmeta</Text>
        <View style={styles.actions}>
          {response.capabilities.can_update ? <Button variant="secondary" onPress={openUpdate}>Ažuriraj predmet</Button> : null}
          {response.capabilities.can_replace_plan ? <Button variant="secondary" onPress={openPlan}>Plan otplate</Button> : null}
          {response.capabilities.can_add_contact ? <Button variant="secondary" onPress={openContact}>Evidentiraj kontakt</Button> : null}
          {response.capabilities.can_send_reminder ? <Button onPress={openReminder}>Pošalji podsetnik</Button> : null}
        </View>
      </Card>

      {panel === 'update' ? (
        <Card style={styles.panelCard}>
          <Text style={styles.sectionTitle}>Ažuriranje predmeta</Text>
          <SelectSheet label="Status" value={status} options={statusOptions} onChange={setStatus} />
          <SelectSheet label="Odgovorno lice" value={assignedTo} options={assigneeOptions} onChange={setAssignedTo} />
          <DateTimeField label="Sledeća akcija" value={nextActionAt} onChangeText={setNextActionAt} />
          <DateTimeField label="Obećana uplata" value={promisedPaymentAt} onChangeText={setPromisedPaymentAt} />
          <TextField label="Interna napomena" value={internalNote} onChangeText={setInternalNote} multiline />
          <View style={styles.actions}>
            <Button loading={mutation.isPending} onPress={submitUpdate}>Sačuvaj</Button>
            <Button variant="secondary" onPress={() => setPanel(null)}>Otkaži</Button>
          </View>
        </Card>
      ) : null}

      {panel === 'plan' ? (
        <Card style={styles.panelCard}>
          <Text style={styles.sectionTitle}>Plan otplate</Text>
          <Text style={styles.meta}>Server proverava zbir rata, hronologiju i zabranu zamene plana nakon alocirane uplate. Maksimalno {response.options.max_installments} rata.</Text>
          {data.installments.some((item) => item.paid_amount_rsd > 0) ? <Text style={styles.warning}>Postoje već alocirane uplate. Server može odbiti zamenu postojećeg plana; evidentiraj novi dogovor kroz komunikaciju.</Text> : null}
          {planRows.map((row, index) => (
            <Card key={row.key} style={styles.installmentDraft}>
              <View style={styles.rowBetween}>
                <Text style={styles.itemTitle}>Rata {index + 1}</Text>
                {planRows.length > 1 ? <Button variant="secondary" onPress={() => removePlanRow(row.key)}>Ukloni</Button> : null}
              </View>
              <TextField label="Datum dospeća" value={row.due_at} onChangeText={(value) => updatePlanRow(row.key, 'due_at', value)} placeholder="YYYY-MM-DD" />
              <MoneyField label="Iznos" value={row.amount_rsd} onChangeText={(value) => updatePlanRow(row.key, 'amount_rsd', value)} currency="RSD" required />
              <TextField label="Napomena" value={row.note} onChangeText={(value) => updatePlanRow(row.key, 'note', value)} multiline />
            </Card>
          ))}
          <View style={styles.actions}>
            <Button variant="secondary" disabled={planRows.length >= response.options.max_installments} onPress={addPlanRow}>Dodaj ratu</Button>
            <Button loading={mutation.isPending} onPress={submitPlan}>Sačuvaj plan</Button>
            <Button variant="secondary" onPress={() => setPanel(null)}>Otkaži</Button>
          </View>
        </Card>
      ) : null}

      {panel === 'contact' ? (
        <Card style={styles.panelCard}>
          <Text style={styles.sectionTitle}>Evidencija komunikacije</Text>
          <Text style={styles.meta}>Kanal i smer se prosleđuju postojećem server FormRequest ugovoru. Komunikacija je interna osim kada eksplicitno uključiš vidljivost kupcu.</Text>
          <TextField label="Kanal" value={contactChannel} onChangeText={setContactChannel} placeholder="npr. email, telefon…" />
          <TextField label="Smer" value={contactDirection} onChangeText={setContactDirection} placeholder="vrednost iz postojećeg web ugovora" />
          <TextField label="Naslov" value={contactSubject} onChangeText={setContactSubject} />
          <TextField label="Napomena" value={contactNote} onChangeText={setContactNote} multiline />
          <View style={styles.actions}>
            <FilterChip label="Vidljivo kupcu" active={contactVisible} onPress={() => setContactVisible((value) => !value)} />
          </View>
          <View style={styles.actions}>
            <Button loading={mutation.isPending} onPress={submitContact}>Evidentiraj</Button>
            <Button variant="secondary" onPress={() => setPanel(null)}>Otkaži</Button>
          </View>
        </Card>
      ) : null}

      {panel === 'reminder' ? (
        <Card style={styles.panelCard}>
          <Text style={styles.sectionTitle}>Ručni podsetnik</Text>
          <Text style={styles.warning}>Podsetnik ide kroz postojeći ReceivablesService i e-mail outbox. Ne kreira sintetičku uplatu niti zaobilazi postojeću deduplikaciju.</Text>
          <TextField label="Prilagođena poruka (opciono)" value={reminderMessage} onChangeText={setReminderMessage} multiline />
          <View style={styles.actions}>
            <Button loading={mutation.isPending} onPress={submitReminder}>Pošalji podsetnik</Button>
            <Button variant="secondary" onPress={() => setPanel(null)}>Otkaži</Button>
          </View>
        </Card>
      ) : null}

      <Card style={styles.sectionCard}>
        <View style={styles.rowBetween}>
          <Text style={styles.sectionTitle}>Rate</Text>
          <Text style={styles.meta}>{data.installments.length}</Text>
        </View>
        {data.installments.length === 0 ? <EmptyState title="Nema plana otplate" message="Plan nije definisan za ovaj predmet." /> : data.installments.map((item) => <InstallmentRow item={item} key={item.id} />)}
      </Card>

      <Card style={styles.sectionCard}>
        <View style={styles.rowBetween}>
          <Text style={styles.sectionTitle}>Komunikacija</Text>
          <Text style={styles.meta}>{data.contacts.length}</Text>
        </View>
        {data.contacts.length === 0 ? <EmptyState title="Nema komunikacije" message="Još nema evidentiranih kontakata ili automatskih opomena." /> : data.contacts.map((item) => <ContactRow item={item} key={item.id} />)}
      </Card>

      <Card style={styles.sectionCard}>
        <Text style={styles.sectionTitle}>Audit podaci</Text>
        <Text style={styles.meta}>Kreirao: {data.created_by?.name ?? 'Sistem'} · Izmenio: {data.updated_by?.name ?? '—'}</Text>
        <Text style={styles.meta}>Poslednji kontakt: {data.last_contact_at ? formatDate(data.last_contact_at, true) : '—'}</Text>
        <Text style={styles.meta}>Poslednja opomena: {data.last_reminder_at ? formatDate(data.last_reminder_at, true) : '—'}{data.last_reminder_stage !== null ? ` · faza ${data.last_reminder_stage}` : ''}</Text>
        {data.internal_note ? <Text style={styles.body}>{data.internal_note}</Text> : null}
      </Card>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: spacing.xxxl },
    back: { ...typography.small, color: theme.primary, fontWeight: '800' },
    heroCard: { gap: spacing.sm },
    heroValue: { ...typography.h2, color: theme.ink },
    actionsCard: { gap: spacing.md },
    panelCard: { gap: spacing.md },
    sectionCard: { gap: spacing.md },
    installmentDraft: { gap: spacing.sm },
    sectionTitle: { ...typography.h3, color: theme.ink },
    itemTitle: { ...typography.body, color: theme.ink, fontWeight: '800' },
    body: { ...typography.body, color: theme.ink, lineHeight: 22 },
    meta: { ...typography.small, color: theme.muted },
    warning: { ...typography.small, color: theme.primary, fontWeight: '800' },
    rowBetween: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    grow: { flex: 1, minWidth: 0 },
    actions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    lineRow: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, borderTopWidth: 1, borderTopColor: theme.line, paddingTop: spacing.md },
  });
}
