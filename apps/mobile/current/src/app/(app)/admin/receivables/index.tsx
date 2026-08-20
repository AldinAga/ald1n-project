import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
import { Pill, type PillTone } from '@/components/ui/pill';
import { SelectSheet } from '@/components/ui/select-sheet';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminReceivables,
  shareAdminReceivablesCsv,
  type AdminReceivableListParams,
  type AdminReceivableSettings,
  type AdminReceivableSettingsInput,
  type AdminReceivableSummary,
} from '@/features/admin/receivables-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { formatDate } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';

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

function isOn(value: string | undefined): boolean {
  return value === '1' || value === 'true';
}

function positiveInt(value: string, label: string): number {
  const parsed = Number(value.trim());
  if (!Number.isInteger(parsed) || parsed < 0) throw new Error(`${label} mora biti ceo broj jednak ili veći od nule.`);
  return parsed;
}

function ReceivableCard({ item }: { item: AdminReceivableSummary }) {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`Otvori predmet ${item.case_number}`}
      onPress={() => router.push({ pathname: '/admin/receivables/[id]', params: { id: String(item.id) } })}
      style={({ pressed }) => pressed ? styles.pressed : undefined}
    >
      <Card style={styles.caseCard}>
        <View style={styles.rowBetween}>
          <View style={styles.grow}>
            <Text style={styles.number}>{item.case_number}</Text>
            <Text style={styles.title}>{item.order?.order_number ?? 'Porudžbina nije dostupna'}</Text>
          </View>
          <Pill tone={tone(item.status)}>{item.status_label}</Pill>
        </View>
        <Text style={styles.meta}>Kupac: {item.order?.customer?.name ?? '—'}</Text>
        <Text style={styles.meta}>Preostalo: {money(item.remaining_rsd)}</Text>
        <Text style={styles.meta}>Kašnjenje: {item.days_overdue > 0 ? `${item.days_overdue} dana` : 'Nema kašnjenja'} · aging {item.aging_bucket}</Text>
        <Text style={styles.meta}>Odgovorno lice: {item.assigned_to?.name ?? item.order?.supplier?.name ?? 'Nije dodeljeno'}</Text>
        <Text style={styles.meta}>Sledeća akcija: {item.next_action_at ? formatDate(item.next_action_at, true) : 'Nije definisana'}</Text>
        {item.promised_payment_at ? <Text style={styles.meta}>Obećana uplata: {formatDate(item.promised_payment_at, true)}</Text> : null}
      </Card>
    </Pressable>
  );
}

export default function AdminReceivablesListScreen() {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { bootstrap, can } = useAuth();
  const allowed = can('receivables.manage');

  const [draftQ, setDraftQ] = useState('');
  const [draftStatus, setDraftStatus] = useState('');
  const [draftAssignee, setDraftAssignee] = useState('');
  const [draftAction, setDraftAction] = useState<'' | 'overdue' | 'today' | 'promised'> ('');
  const [draftAging, setDraftAging] = useState('');
  const [draftPerPage, setDraftPerPage] = useState(35);
  const [applied, setApplied] = useState<AdminReceivableListParams> ({ page: 1, per_page: 35 });
  const [settingsOpen, setSettingsOpen] = useState(false);
  const [scanConfirmOpen, setScanConfirmOpen] = useState(false);
  const [settingsDraft, setSettingsDraft] = useState<AdminReceivableSettings | null> (null);
  const [exporting, setExporting] = useState(false);

  const query = useQuery({
    queryKey: adminQueryKeys.receivablesList(applied),
    queryFn: () => apiAdminReceivables.list(applied),
    enabled: allowed,
  });
  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });

  if (!allowed) return <UnavailableState title="Potraživanja nisu dostupna" />;
  if (query.isLoading) return <LoadingState label="Učitavanje potraživanja…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const response = query.data;
  const statusOptions = [
    { value: '', label: 'Svi statusi' },
    ...Object.entries(response.filter_options.statuses).map(([value, label]) => ({ value, label })),
  ];
  const assigneeOptions = [
    { value: '', label: 'Sva odgovorna lica' },
    ...response.filter_options.assignees.map((user) => ({ value: String(user.id), label: user.name })),
  ];
  const agingOptions = [
    { value: '', label: 'Svi aging periodi' },
    { value: 'current', label: 'Nije dospelo / tekuće' },
    { value: '1_7', label: '1–7 dana' },
    { value: '8_15', label: '8–15 dana' },
    { value: '16_30', label: '16–30 dana' },
    { value: '31_60', label: '31–60 dana' },
    { value: '61_90', label: '61–90 dana' },
    { value: '90_plus', label: 'Više od 90 dana' },
  ];
  const activeCount = Number(Boolean(applied.q))
    + Number(Boolean(applied.status))
    + Number(Boolean(applied.assigned_to))
    + Number(Boolean(applied.action))
    + Number(Boolean(applied.aging));

  const applyFilters = () => {
    setApplied({
      q: draftQ.trim() || undefined,
      status: draftStatus || undefined,
      assigned_to: draftAssignee ? Number(draftAssignee) : undefined,
      action: draftAction || undefined,
      aging: draftAging ? draftAging as AdminReceivableListParams['aging'] : undefined,
      page: 1,
      per_page: draftPerPage,
    });
  };

  const clearFilters = () => {
    setDraftQ('');
    setDraftStatus('');
    setDraftAssignee('');
    setDraftAction('');
    setDraftAging('');
    setDraftPerPage(35);
    setApplied({ page: 1, per_page: 35 });
  };

  const openSettings = () => {
    setSettingsDraft({ ...response.settings });
    setSettingsOpen(true);
  };

  const updateSetting = (key: keyof AdminReceivableSettings, value: string) => {
    setSettingsDraft((current) => current ? { ...current, [key]: value } : current);
  };

  const saveSettings = async () => {
    if (!settingsDraft) return;
    try {
      const input: AdminReceivableSettingsInput = {
        receivables_enabled: isOn(settingsDraft.receivables_enabled),
        receivables_auto_create_cases: isOn(settingsDraft.receivables_auto_create_cases),
        receivables_auto_reminders_enabled: isOn(settingsDraft.receivables_auto_reminders_enabled),
        receivables_due_soon_days: positiveInt(settingsDraft.receivables_due_soon_days, 'Broj dana pre dospeća'),
        receivables_reminder_stages: settingsDraft.receivables_reminder_stages.trim(),
        receivables_pause_on_promise: isOn(settingsDraft.receivables_pause_on_promise),
        receivables_attach_document: settingsDraft.receivables_attach_document.trim(),
        receivables_send_creator: isOn(settingsDraft.receivables_send_creator),
        receivables_send_supplier: isOn(settingsDraft.receivables_send_supplier),
        receivables_custom_recipients: settingsDraft.receivables_custom_recipients.trim(),
      };
      await mutation.mutateAsync(() => apiAdminReceivables.updateSettings(input));
      await client.invalidateQueries({ queryKey: adminQueryKeys.receivables() });
      setSettingsOpen(false);
      feedback.notify({ tone: 'success', title: 'Podešavanja su sačuvana', message: 'Server je prihvatio Receivables podešavanja.' });
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Podešavanja nisu sačuvana', message: error instanceof Error ? error.message : 'Pokušaj ponovo.' });
    }
  };

  const runScan = async () => {
    try {
      const result = await mutation.mutateAsync(() => apiAdminReceivables.scan());
      await client.invalidateQueries({ queryKey: adminQueryKeys.receivables() });
      setScanConfirmOpen(false);
      const scan = result as Awaited<ReturnType<typeof apiAdminReceivables.scan>>;
      feedback.notify({
        tone: 'success',
        title: 'Kontrolisana provera je završena',
        message: `Pregledano ${scan.data.examined}, novi predmeti ${scan.data.cases_created}, opomene ${scan.data.reminders}, zatvoreno ${scan.data.closed}.`,
      });
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Provera nije završena', message: error instanceof Error ? error.message : 'Pokušaj ponovo.' });
    }
  };

  const exportCsv = async () => {
    if (exporting) return;
    setExporting(true);
    try {
      await shareAdminReceivablesCsv();
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'CSV izvoz nije otvoren', message: error instanceof Error ? error.message : 'Pokušaj ponovo.' });
    } finally {
      setExporting(false);
    }
  };

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Administracija</Text>
      </Pressable>
      <PageHeader title="Potraživanja" eyebrow="Admin · v0.7" name={bootstrap?.user.name} />
      <Text style={styles.copy}>Aging, planovi otplate, komunikacija i automatske opomene kroz postojeći Receivables poslovni autoritet.</Text>

      <View style={styles.statsGrid}>
        <Card style={styles.statCard}><Text style={styles.statValue}>{response.meta.stats.active}</Text><Text style={styles.meta}>Aktivni predmeti</Text></Card>
        <Card style={styles.statCard}><Text style={styles.statValue}>{response.meta.stats.promised}</Text><Text style={styles.meta}>Obećane uplate</Text></Card>
        <Card style={styles.statCard}><Text style={styles.statValue}>{response.meta.stats.plans}</Text><Text style={styles.meta}>Planovi otplate</Text></Card>
        <Card style={styles.statCard}><Text style={styles.statValue}>{response.meta.stats.actions_overdue}</Text><Text style={styles.meta}>Zakasnele akcije</Text></Card>
      </View>

      <Card style={styles.filtersCard}>
        <View style={styles.rowBetween}>
          <Text style={styles.sectionTitle}>Filteri</Text>
          <Text style={styles.meta}>{activeCount} aktivnih</Text>
        </View>
        <TextField label="Pretraga" value={draftQ} onChangeText={setDraftQ} placeholder="Broj predmeta ili porudžbine" />
        <SelectSheet label="Status" value={draftStatus} options={statusOptions} onChange={setDraftStatus} />
        <SelectSheet label="Odgovorno lice" value={draftAssignee} options={assigneeOptions} onChange={setDraftAssignee} />
        <SelectSheet label="Aging" value={draftAging} options={agingOptions} onChange={setDraftAging} />
        <FilterBar activeCount={draftAction ? 1 : 0} onClear={() => setDraftAction('')}>
          <FilterChip label="Zakasnela akcija" active={draftAction === 'overdue'} onPress={() => setDraftAction((value) => value === 'overdue' ? '' : 'overdue')} />
          <FilterChip label="Akcija danas" active={draftAction === 'today'} onPress={() => setDraftAction((value) => value === 'today' ? '' : 'today')} />
          <FilterChip label="Obećana uplata" active={draftAction === 'promised'} onPress={() => setDraftAction((value) => value === 'promised' ? '' : 'promised')} />
        </FilterBar>
        <SelectSheet
          label="Broj po strani"
          value={String(draftPerPage)}
          options={[20, 35, 50, 100].map((value) => ({ value: String(value), label: String(value) }))}
          onChange={(value) => {
            const next = Number(value);
            if (next === 20 || next === 35 || next === 50 || next === 100) setDraftPerPage(next);
          }}
        />
        <View style={styles.actions}>
          <Button onPress={applyFilters}>Primeni filtere</Button>
          {activeCount > 0 ? <Button variant="secondary" onPress={clearFilters}>Očisti</Button> : null}
        </View>
      </Card>

      <Card style={styles.operationsCard}>
        <Text style={styles.sectionTitle}>Operativne akcije</Text>
        <Text style={styles.meta}>Ručno pokretanje provere može kreirati predmete i poslati opomene prema postojećim pravilima. CSV koristi isti autorizovani scope.</Text>
        <View style={styles.actions}>
          <Button variant="secondary" loading={exporting} disabled={!response.capabilities.can_export_csv} onPress={() => void exportCsv()}>
            Izvezi CSV
          </Button>
          <Button variant="secondary" disabled={!response.capabilities.can_update_settings} onPress={openSettings}>Podešavanja</Button>
          <Button disabled={!response.capabilities.can_run_scan} onPress={() => setScanConfirmOpen(true)}>Pokreni proveru</Button>
        </View>
      </Card>

      {scanConfirmOpen ? (
        <Card style={styles.warningCard}>
          <Text style={styles.sectionTitle}>Potvrdi kontrolisanu proveru</Text>
          <Text style={styles.copy}>Ova akcija poziva postojeći ReceivablesService automatizacioni tok i može kreirati predmete, zatvoriti izmirene predmete i poslati opomene kroz postojeći outbox.</Text>
          <View style={styles.actions}>
            <Button loading={mutation.isPending} onPress={() => void runScan()}>Da, pokreni proveru</Button>
            <Button variant="secondary" onPress={() => setScanConfirmOpen(false)}>Otkaži</Button>
          </View>
        </Card>
      ) : null}

      {settingsOpen && settingsDraft ? (
        <Card style={styles.settingsCard}>
          <View style={styles.rowBetween}>
            <Text style={styles.sectionTitle}>Podešavanja naplate</Text>
            <Button variant="secondary" onPress={() => setSettingsOpen(false)}>Zatvori</Button>
          </View>
          <View style={styles.actions}>
            <FilterChip label="Receivables uključen" active={isOn(settingsDraft.receivables_enabled)} onPress={() => updateSetting('receivables_enabled', isOn(settingsDraft.receivables_enabled) ? '0' : '1')} />
            <FilterChip label="Automatsko kreiranje" active={isOn(settingsDraft.receivables_auto_create_cases)} onPress={() => updateSetting('receivables_auto_create_cases', isOn(settingsDraft.receivables_auto_create_cases) ? '0' : '1')} />
            <FilterChip label="Automatske opomene" active={isOn(settingsDraft.receivables_auto_reminders_enabled)} onPress={() => updateSetting('receivables_auto_reminders_enabled', isOn(settingsDraft.receivables_auto_reminders_enabled) ? '0' : '1')} />
            <FilterChip label="Pauza uz obećanje" active={isOn(settingsDraft.receivables_pause_on_promise)} onPress={() => updateSetting('receivables_pause_on_promise', isOn(settingsDraft.receivables_pause_on_promise) ? '0' : '1')} />
            <FilterChip label="Pošalji kreatoru" active={isOn(settingsDraft.receivables_send_creator)} onPress={() => updateSetting('receivables_send_creator', isOn(settingsDraft.receivables_send_creator) ? '0' : '1')} />
            <FilterChip label="Pošalji odgovornom" active={isOn(settingsDraft.receivables_send_supplier)} onPress={() => updateSetting('receivables_send_supplier', isOn(settingsDraft.receivables_send_supplier) ? '0' : '1')} />
          </View>
          <TextField label="Dana pre dospeća" value={settingsDraft.receivables_due_soon_days} onChangeText={(value) => updateSetting('receivables_due_soon_days', value)} keyboardType="numeric" />
          <TextField label="Faze opomena" value={settingsDraft.receivables_reminder_stages} onChangeText={(value) => updateSetting('receivables_reminder_stages', value)} placeholder="0,3,7,15,30" />
          <TextField label="Dokument uz opomenu" value={settingsDraft.receivables_attach_document} onChangeText={(value) => updateSetting('receivables_attach_document', value)} />
          <TextField label="Dodatni primaoci" value={settingsDraft.receivables_custom_recipients} onChangeText={(value) => updateSetting('receivables_custom_recipients', value)} multiline />
          <Button loading={mutation.isPending} onPress={() => void saveSettings()}>Sačuvaj podešavanja</Button>
        </Card>
      ) : null}

      <View style={styles.rowBetween}>
        <View>
          <Text style={styles.sectionTitle}>Predmeti naplate</Text>
          <Text style={styles.meta}>{response.meta.total} ukupno</Text>
        </View>
        <Button variant="secondary" onPress={() => void query.refetch()}>{query.isFetching ? 'Osvežavanje…' : 'Osveži'}</Button>
      </View>

      {response.data.length === 0 ? (
        <EmptyState title="Nema predmeta" message="Nema rezultata za izabrane filtere." />
      ) : (
        <View style={styles.list}>{response.data.map((item) => <ReceivableCard item={item} key={item.id} />)}</View>
      )}

      <View style={styles.pagination}>
        <Button
          variant="secondary"
          onPress={() => setApplied((current) => ({ ...current, page: Math.max(1, (current.page ?? 1) - 1) }))}
        >
          Prethodna
        </Button>
        <Text style={styles.meta}>Strana {response.meta.current_page} / {response.meta.last_page}</Text>
        <Button
          variant="secondary"
          onPress={() => setApplied((current) => ({ ...current, page: (current.page ?? 1) + 1 }))}
        >
          Sledeća
        </Button>
      </View>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: spacing.xxxl },
    back: { ...typography.small, color: theme.primary, fontWeight: '800' },
    copy: { ...typography.body, color: theme.muted, lineHeight: 22 },
    sectionTitle: { ...typography.h3, color: theme.ink },
    title: { ...typography.body, color: theme.ink, fontWeight: '700' },
    number: { ...typography.h3, color: theme.ink },
    meta: { ...typography.small, color: theme.muted },
    statValue: { ...typography.h2, color: theme.ink },
    statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    statCard: { flexGrow: 1, minWidth: 145, gap: spacing.xs },
    filtersCard: { gap: spacing.md },
    operationsCard: { gap: spacing.md },
    settingsCard: { gap: spacing.md },
    warningCard: { gap: spacing.md, borderColor: theme.primary },
    caseCard: { gap: spacing.sm },
    list: { gap: spacing.md },
    rowBetween: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    grow: { flex: 1, minWidth: 0 },
    actions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    pagination: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
    pressed: { opacity: 0.78 },
  });
}
