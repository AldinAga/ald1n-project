import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ConfirmAction } from '@/components/ui/confirm-action';
import { DateTimeField } from '@/components/ui/date-time-field';
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminReports,
  type AdminManagementReport,
  type AdminReportDelivery,
  type AdminReportGroupBy,
  type AdminReportRequestParams,
  type AdminReportSchedule,
  type AdminReportScheduleFormat,
  type AdminReportScheduleFrequency,
  type AdminReportScheduleInput,
  type AdminReportScope,
  type AdminReportSegment,
  type AdminReportType,
} from '@/features/admin/reports-admin-api';
import { openAdminReportExport } from '@/features/admin/reports-admin-export';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { useAuth } from '@/features/auth/auth-provider';
import { formatMoney } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';

const SCOPE_OPTIONS: Array<{ value: AdminReportScope; label: string }> = [
  { value: 'completed', label: 'Kompletirane porudžbine' },
  { value: 'active', label: 'Sve osim otkazanih' },
  { value: 'all', label: 'Sve porudžbine' },
];

const GROUP_OPTIONS: Array<{ value: AdminReportGroupBy; label: string }> = [
  { value: 'brand', label: 'Brend' },
  { value: 'line', label: 'Linija' },
  { value: 'type', label: 'Tip artikla' },
  { value: 'product', label: 'Proizvod' },
  { value: 'admin', label: 'Odgovorno lice' },
];

const AGING_LABELS: Record<string, string> = {
  not_due: 'Nije dospelo',
  '1_7': '1–7 dana',
  '8_15': '8–15 dana',
  '16_30': '16–30 dana',
  '31_60': '31–60 dana',
  '61_90': '61–90 dana',
  over_90: 'Preko 90 dana',
};

const REPORT_TYPE_OPTIONS: Array<{ value: AdminReportType; label: string }> = [
  { value: 'management_summary', label: 'Kompletan upravljački' },
  { value: 'profitability', label: 'Profitabilnost' },
  { value: 'inventory', label: 'Lager' },
  { value: 'receivables', label: 'Potraživanja' },
  { value: 'after_sales', label: 'Postprodaja' },
];

const SCHEDULE_FREQUENCY_OPTIONS: Array<{
  value: AdminReportScheduleFrequency;
  label: string;
}> = [
  { value: 'daily', label: 'Dnevno' },
  { value: 'weekly', label: 'Nedeljno' },
  { value: 'monthly', label: 'Mesečno' },
];

const WEEKDAY_OPTIONS: Array<{ value: string; label: string }> = [
  { value: '1', label: 'Ponedeljak' },
  { value: '2', label: 'Utorak' },
  { value: '3', label: 'Sreda' },
  { value: '4', label: 'Četvrtak' },
  { value: '5', label: 'Petak' },
  { value: '6', label: 'Subota' },
  { value: '7', label: 'Nedelja' },
];

function errorMessage(error: unknown, fallback: string): string {
  return error instanceof Error && error.message.trim() ? error.message : fallback;
}

function trimmed(value: string): string | undefined {
  const normalized = value.trim();
  return normalized || undefined;
}

function isReportType(value: string): value is AdminReportType {
  return REPORT_TYPE_OPTIONS.some((option) => option.value === value);
}

function isScheduleFrequency(value: string): value is AdminReportScheduleFrequency {
  return SCHEDULE_FREQUENCY_OPTIONS.some((option) => option.value === value);
}

function reportTypeLabel(value: AdminReportType): string {
  return REPORT_TYPE_OPTIONS.find((option) => option.value === value)?.label ?? value;
}

function displayDateTime(value: string | null): string {
  if (!value) return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString('sr-RS');
}

function displayDate(value: string | null): string {
  if (!value) return '—';
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString('sr-RS');
}

function scheduleCadence(schedule: AdminReportSchedule): string {
  if (schedule.frequency === 'daily') {
    return `Dnevno · ${schedule.send_time}`;
  }

  if (schedule.frequency === 'weekly') {
    const weekday = WEEKDAY_OPTIONS.find(
      (option) => option.value === String(schedule.weekday ?? ''),
    )?.label ?? 'Dan nije podešen';
    return `${weekday} · ${schedule.send_time}`;
  }

  return `${schedule.month_day ?? '—'}. dan u mesecu · ${schedule.send_time}`;
}

export default function AdminReportsIndexScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const feedback = useAppFeedback();
  const allowed = can('reports.view');
  const canManageSchedules = can('reports.manage');

  const [draftFrom, setDraftFrom] = useState('');
  const [draftTo, setDraftTo] = useState('');
  const [scope, setScope] = useState<AdminReportScope> ('completed');
  const [groupBy, setGroupBy] = useState<AdminReportGroupBy> ('brand');
  const [draftBrand, setDraftBrand] = useState('');
  const [draftLine, setDraftLine] = useState('');
  const [draftType, setDraftType] = useState('');
  const [draftQ, setDraftQ] = useState('');
  const [applied, setApplied] = useState<AdminReportRequestParams> ({
    report_type: 'management_summary',
  });
  const [exporting, setExporting] = useState<'csv' | 'pdf' | null> (null);

  const query = useQuery({
    queryKey: adminQueryKeys.managementReport(applied),
    queryFn: () => apiAdminReports.management(applied),
    enabled: allowed,
  });

  const activeCount = Number(Boolean(draftFrom))
    + Number(Boolean(draftTo))
    + Number(scope !== 'completed')
    + Number(groupBy !== 'brand')
    + Number(Boolean(draftBrand.trim()))
    + Number(Boolean(draftLine.trim()))
    + Number(Boolean(draftType.trim()))
    + Number(Boolean(draftQ.trim()));

  const applyFilters = () => {
    if (draftFrom && draftTo && draftTo < draftFrom) {
      feedback.notify({
        tone: 'danger',
        title: 'Neispravan period',
        message: 'Datum „Do“ ne može biti pre datuma „Od“.',
      });
      return;
    }

    const next: AdminReportRequestParams = {
      report_type: 'management_summary',
      scope,
      group_by: groupBy,
    };

    const dateFrom = trimmed(draftFrom);
    const dateTo = trimmed(draftTo);
    const brand = trimmed(draftBrand);
    const line = trimmed(draftLine);
    const type = trimmed(draftType);
    const q = trimmed(draftQ);

    if (dateFrom) next.date_from = dateFrom;
    if (dateTo) next.date_to = dateTo;
    if (brand) next.brand = brand;
    if (line) next.line = line;
    if (type) next.type = type;
    if (q) next.q = q;

    setApplied(next);
  };

  const clearFilters = () => {
    setDraftFrom('');
    setDraftTo('');
    setScope('completed');
    setGroupBy('brand');
    setDraftBrand('');
    setDraftLine('');
    setDraftType('');
    setDraftQ('');
    setApplied({ report_type: 'management_summary' });
  };

  const runExport = async (format: 'csv' | 'pdf') => {
    if (exporting) return;

    setExporting(format);

    try {
      await openAdminReportExport(format, applied);
    } catch (error) {
      feedback.notify({
        tone: 'danger',
        title: 'Izvoz nije uspeo',
        message: errorMessage(
          error,
          'Pokušaj ponovo za nekoliko trenutaka.',
        ),
      });
    } finally {
      setExporting(null);
    }
  };

  if (!allowed) {
    return <UnavailableState title="Administracija izveštaja nije dostupna" />;
  }

  if (query.isLoading) {
    return <LoadingState label="Učitavanje upravljačkih izveštaja…" />;
  }

  if (query.isError || !query.data) {
    return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  }

  const response = query.data;
  const report = response.data;

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Administracija</Text>
      </Pressable>

      <PageHeader
        title="Izveštaji"
        eyebrow="Admin · Wave A"
        name={bootstrap?.user.name}
      />

      <Text style={styles.copy}>
        Upravljački pregled koristi postojeći ManagementReportService kao poslovni autoritet.
      </Text>

      <Card style={styles.periodCard}>
        <Text style={styles.periodLabel}>{report.period_label}</Text>
        <Text style={styles.muted}>Generisano: {report.generated_at}</Text>
      </Card>

      <Card style={styles.filtersCard}>
        <View style={styles.sectionHead}>
          <Text style={styles.sectionTitle}>Filteri</Text>
          <Text style={styles.muted}>{activeCount} aktivnih</Text>
        </View>

        <FilterBar activeCount={activeCount} onClear={clearFilters}>
          <FilterChip
            label="Kompletirane"
            active={scope === 'completed'}
            onPress={() => setScope('completed')}
          />
          <FilterChip
            label="Aktivne"
            active={scope === 'active'}
            onPress={() => setScope('active')}
          />
          <FilterChip
            label="Sve"
            active={scope === 'all'}
            onPress={() => setScope('all')}
          />
        </FilterBar>

        <DateTimeField
          label="Od datuma"
          mode="date"
          value={draftFrom}
          onChangeText={setDraftFrom}
        />
        <DateTimeField
          label="Do datuma"
          mode="date"
          value={draftTo}
          onChangeText={setDraftTo}
        />
        <SelectSheet
          label="Obuhvat"
          value={scope}
          options={SCOPE_OPTIONS}
          onChange={(value) => {
            if (value === 'completed' || value === 'active' || value === 'all') {
              setScope(value);
            }
          }}
        />
        <SelectSheet
          label="Grupisanje"
          value={groupBy}
          options={GROUP_OPTIONS}
          onChange={(value) => {
            if (
              value === 'brand'
              || value === 'line'
              || value === 'type'
              || value === 'product'
              || value === 'admin'
            ) {
              setGroupBy(value);
            }
          }}
        />
        <TextField
          label="Brend"
          value={draftBrand}
          onChangeText={setDraftBrand}
          placeholder="Opcioni filter"
        />
        <TextField
          label="Linija"
          value={draftLine}
          onChangeText={setDraftLine}
          placeholder="Opcioni filter"
        />
        <TextField
          label="Tip artikla"
          value={draftType}
          onChangeText={setDraftType}
          placeholder="Opcioni filter"
        />
        <TextField
          label="Proizvod / SKU"
          value={draftQ}
          onChangeText={setDraftQ}
          placeholder="Naziv ili SKU"
        />
        <Button onPress={applyFilters}>Primeni filtere</Button>
      </Card>

      {response.capabilities.export ? (
        <View style={styles.actionsRow}>
          <Button
            variant="secondary"
            loading={exporting === 'csv'}
            disabled={exporting !== null}
            onPress={() => void runExport('csv')}
          >
            Otvori / podeli CSV
          </Button>
          <Button
            variant="secondary"
            loading={exporting === 'pdf'}
            disabled={exporting !== null}
            onPress={() => void runExport('pdf')}
          >
            Otvori / podeli PDF
          </Button>
        </View>
      ) : null}

      {canManageSchedules && response.capabilities.manage_schedules ? (
        <ScheduleManagerSection applied={applied} styles={styles} />
      ) : null}

      <SummarySection report={report} styles={styles} />
      <SegmentsSection report={report} styles={styles} />
      <TrendSection report={report} styles={styles} />
      <InventorySection report={report} styles={styles} />
      <ReceivablesSection report={report} styles={styles} />
      <AfterSalesSection report={report} styles={styles} />
      <TeamsSection report={report} styles={styles} />
    </Screen>
  );
}

function ScheduleManagerSection({
  applied,
  styles,
}: {
  applied: AdminReportRequestParams;
  styles: ReturnType<typeof createStyles>;
}) {
  const client = useQueryClient();
  const feedback = useAppFeedback();
  const [editingId, setEditingId] = useState<number | null> (null);
  const [name, setName] = useState('');
  const [reportType, setReportType] = useState<AdminReportType> ('management_summary');
  const [frequency, setFrequency] = useState<AdminReportScheduleFrequency> ('weekly');
  const [sendTime, setSendTime] = useState('08:00');
  const [weekday, setWeekday] = useState('1');
  const [monthDay, setMonthDay] = useState('1');
  const [timezone, setTimezone] = useState('Europe/Belgrade');
  const [recipients, setRecipients] = useState('');
  const [pdfEnabled, setPdfEnabled] = useState(true);
  const [csvEnabled, setCsvEnabled] = useState(false);
  const [isActive, setIsActive] = useState(true);
  const [scheduleFilters, setScheduleFilters] = useState<AdminReportRequestParams> (applied);
  const [runTarget, setRunTarget] = useState<AdminReportSchedule | null> (null);
  const [deleteTarget, setDeleteTarget] = useState<AdminReportSchedule | null> (null);
  const [retryTarget, setRetryTarget] = useState<AdminReportDelivery | null> (null);

  const query = useQuery({
    queryKey: adminQueryKeys.reportSchedules(),
    queryFn: apiAdminReports.schedules,
  });

  const refresh = async () => {
    await client.invalidateQueries({ queryKey: adminQueryKeys.reportSchedules() });
  };

  const resetForm = () => {
    setEditingId(null);
    setName('');
    setReportType('management_summary');
    setFrequency('weekly');
    setSendTime('08:00');
    setWeekday('1');
    setMonthDay('1');
    setTimezone('Europe/Belgrade');
    setRecipients('');
    setPdfEnabled(true);
    setCsvEnabled(false);
    setIsActive(true);
    setScheduleFilters(applied);
  };

  const editSchedule = (schedule: AdminReportSchedule) => {
    setEditingId(schedule.id);
    setName(schedule.name);
    setReportType(schedule.report_type);
    setFrequency(schedule.frequency);
    setSendTime(schedule.send_time || '08:00');
    setWeekday(String(schedule.weekday ?? 1));
    setMonthDay(String(schedule.month_day ?? 1));
    setTimezone(schedule.timezone || 'Europe/Belgrade');
    setRecipients(schedule.recipients.join('\n'));
    setPdfEnabled(schedule.formats.includes('pdf'));
    setCsvEnabled(schedule.formats.includes('csv'));
    setIsActive(schedule.is_active);
    setScheduleFilters(schedule.filters);
  };

  const buildInput = (): AdminReportScheduleInput | null => {
    const normalizedName = name.trim();
    const normalizedTime = sendTime.trim();
    const normalizedTimezone = timezone.trim();
    const recipientLines = recipients
      .split(/\r?\n/)
      .map((value) => value.trim())
      .filter(Boolean);

    if (!normalizedName) {
      feedback.notify({
        tone: 'danger',
        title: 'Naziv je obavezan',
        message: 'Unesi naziv rasporeda izveštaja.',
      });
      return null;
    }

    if (!/^(?:[01]\d|2[0-3]):[0-5]\d$/.test(normalizedTime)) {
      feedback.notify({
        tone: 'danger',
        title: 'Vreme nije ispravno',
        message: 'Koristi format HH:MM, na primer 08:00.',
      });
      return null;
    }

    if (!normalizedTimezone) {
      feedback.notify({
        tone: 'danger',
        title: 'Vremenska zona je obavezna',
        message: 'Unesi vremensku zonu, na primer Europe/Belgrade.',
      });
      return null;
    }

    if (recipientLines.length === 0) {
      feedback.notify({
        tone: 'danger',
        title: 'Primalac je obavezan',
        message: 'Unesi najmanje jednu e-mail adresu, jednu po redu.',
      });
      return null;
    }

    const invalidRecipient = recipientLines.find(
      (value) => !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
    );
    if (invalidRecipient) {
      feedback.notify({
        tone: 'danger',
        title: 'E-mail nije ispravan',
        message: `Proveri adresu: ${invalidRecipient}`,
      });
      return null;
    }

    const formats: AdminReportScheduleFormat[] = [];
    if (pdfEnabled) formats.push('pdf');
    if (csvEnabled) formats.push('csv');
    if (formats.length === 0) {
      feedback.notify({
        tone: 'danger',
        title: 'Izaberi prilog',
        message: 'Raspored mora da šalje PDF, CSV ili oba formata.',
      });
      return null;
    }

    const weekdayNumber = Number(weekday);
    if (
      frequency === 'weekly'
      && (!Number.isInteger(weekdayNumber) || weekdayNumber < 1 || weekdayNumber > 7)
    ) {
      feedback.notify({
        tone: 'danger',
        title: 'Dan nedelje nije ispravan',
        message: 'Izaberi dan od ponedeljka do nedelje.',
      });
      return null;
    }

    const monthDayNumber = Number(monthDay);
    if (
      frequency === 'monthly'
      && (!Number.isInteger(monthDayNumber) || monthDayNumber < 1 || monthDayNumber > 28)
    ) {
      feedback.notify({
        tone: 'danger',
        title: 'Dan u mesecu nije ispravan',
        message: 'Dozvoljene su vrednosti od 1 do 28.',
      });
      return null;
    }

    return {
      name: normalizedName,
      report_type: reportType,
      frequency,
      send_time: normalizedTime,
      weekday: frequency === 'weekly' ? weekdayNumber : null,
      month_day: frequency === 'monthly' ? monthDayNumber : null,
      timezone: normalizedTimezone,
      recipients: recipientLines.join('\n'),
      filters: scheduleFilters,
      formats,
      is_active: isActive,
    };
  };

  const saveMutation = useMutation({
    mutationFn: ({
      id,
      input,
    }: {
      id: number | null;
      input: AdminReportScheduleInput;
    }) => id === null
      ? apiAdminReports.createSchedule(input)
      : apiAdminReports.updateSchedule(id, input),
    onSuccess: async (_, variables) => {
      await refresh();
      feedback.notify({
        tone: 'success',
        title: variables.id === null ? 'Raspored je kreiran' : 'Raspored je sačuvan',
        message: 'Zakazano slanje koristi postojeći ReportScheduleService na serveru.',
      });
      resetForm();
    },
    onError: (error) => feedback.notify({
      tone: 'danger',
      title: 'Raspored nije sačuvan',
      message: errorMessage(error, 'Proveri podatke i pokušaj ponovo.'),
    }),
  });

  const toggleMutation = useMutation({
    mutationFn: apiAdminReports.toggleSchedule,
    onSuccess: async (schedule) => {
      await refresh();
      feedback.notify({
        tone: 'success',
        title: schedule.is_active ? 'Raspored je aktiviran' : 'Raspored je pauziran',
        message: schedule.name,
      });
    },
    onError: (error) => feedback.notify({
      tone: 'danger',
      title: 'Status nije promenjen',
      message: errorMessage(error, 'Pokušaj ponovo.'),
    }),
  });

  const runMutation = useMutation({
    mutationFn: apiAdminReports.runSchedule,
    onSuccess: async (result) => {
      await refresh();
      setRunTarget(null);
      feedback.notify({
        tone: 'success',
        title: 'Slanje je stavljeno u red',
        message: `Broj zakazanih isporuka: ${result.queued}.`,
      });
    },
    onError: (error) => feedback.notify({
      tone: 'danger',
      title: 'Ručno slanje nije pokrenuto',
      message: errorMessage(error, 'Pokušaj ponovo.'),
    }),
  });

  const deleteMutation = useMutation({
    mutationFn: apiAdminReports.deleteSchedule,
    onSuccess: async (id) => {
      await refresh();
      setDeleteTarget(null);
      if (editingId === id) resetForm();
      feedback.notify({
        tone: 'success',
        title: 'Raspored je obrisan',
        message: 'Istorija postojećih slanja ostaje na serveru.',
      });
    },
    onError: (error) => feedback.notify({
      tone: 'danger',
      title: 'Raspored nije obrisan',
      message: errorMessage(error, 'Pokušaj ponovo.'),
    }),
  });

  const retryMutation = useMutation({
    mutationFn: apiAdminReports.retryDelivery,
    onSuccess: async () => {
      await refresh();
      setRetryTarget(null);
      feedback.notify({
        tone: 'success',
        title: 'Ponovno slanje je zakazano',
        message: 'Isporuka će ponovo proći kroz postojeći serverski tok.',
      });
    },
    onError: (error) => feedback.notify({
      tone: 'danger',
      title: 'Ponovno slanje nije zakazano',
      message: errorMessage(error, 'Pokušaj ponovo.'),
    }),
  });

  const submit = () => {
    const input = buildInput();
    if (!input || saveMutation.isPending) return;
    saveMutation.mutate({ id: editingId, input });
  };

  if (query.isLoading) {
    return (
      <Card style={styles.scheduleFormCard}>
        <Text style={styles.sectionTitle}>Zakazano slanje izveštaja</Text>
        <Text style={styles.muted}>Učitavanje rasporeda i poslednjih slanja…</Text>
      </Card>
    );
  }

  if (query.isError || !query.data) {
    return (
      <Card style={styles.scheduleFormCard}>
        <Text style={styles.sectionTitle}>Zakazano slanje izveštaja</Text>
        <Text style={styles.warning}>
          {errorMessage(query.error, 'Rasporede trenutno nije moguće učitati.')}
        </Text>
        <Button variant="secondary" onPress={() => void query.refetch()}>
          Pokušaj ponovo
        </Button>
      </Card>
    );
  }

  const data = query.data;
  const canSave = editingId === null ? data.capabilities.create : data.capabilities.update;

  return (
    <View style={styles.section}>
      <View style={styles.sectionHead}>
        <View style={styles.scheduleCopy}>
          <Text style={styles.sectionTitle}>Zakazano slanje izveštaja</Text>
          <Text style={styles.muted}>
            Primaoci dobijaju zasebne e-mail poruke. Period izveštaja određuje server pri slanju.
          </Text>
        </View>
        {editingId !== null ? (
          <Button variant="secondary" onPress={resetForm}>Novi raspored</Button>
        ) : null}
      </View>

      <Card style={styles.scheduleFormCard}>
        <Text style={styles.cardTitle}>
          {editingId === null ? 'Novi raspored' : `Izmena rasporeda #${editingId}`}
        </Text>
        <Text style={styles.muted}>
          Obuhvat se čuva iz aktivnih filtera izveštaja u trenutku kreiranja. Kod izmene se čuva postojeći obuhvat rasporeda.
        </Text>
        <TextField
          label="Naziv rasporeda"
          value={name}
          onChangeText={setName}
          placeholder="Nedeljni upravljački izveštaj"
        />
        <SelectSheet
          label="Tip izveštaja"
          value={reportType}
          options={REPORT_TYPE_OPTIONS}
          onChange={(value) => {
            if (isReportType(value)) setReportType(value);
          }}
        />
        <SelectSheet
          label="Učestalost"
          value={frequency}
          options={SCHEDULE_FREQUENCY_OPTIONS}
          onChange={(value) => {
            if (isScheduleFrequency(value)) setFrequency(value);
          }}
        />
        <TextField
          label="Vreme slanja · HH:MM"
          value={sendTime}
          onChangeText={setSendTime}
          placeholder="08:00"
          keyboardType="numbers-and-punctuation"
        />
        {frequency === 'weekly' ? (
          <SelectSheet
            label="Dan nedelje"
            value={weekday}
            options={WEEKDAY_OPTIONS}
            onChange={setWeekday}
          />
        ) : null}
        {frequency === 'monthly' ? (
          <TextField
            label="Dan u mesecu · 1–28"
            value={monthDay}
            onChangeText={setMonthDay}
            keyboardType="number-pad"
          />
        ) : null}
        <TextField
          label="Vremenska zona"
          value={timezone}
          onChangeText={setTimezone}
          placeholder="Europe/Belgrade"
        />
        <TextField
          label="Primaoci · jedan e-mail po redu"
          value={recipients}
          onChangeText={setRecipients}
          multiline
          placeholder={'direktor@example.com\nfinansije@example.com'}
          keyboardType="email-address"
          autoCapitalize="none"
        />

        <View style={styles.formatRow}>
          <Button variant="secondary" onPress={() => setPdfEnabled((value) => !value)}>
            {pdfEnabled ? 'PDF ✓' : 'PDF'}
          </Button>
          <Button variant="secondary" onPress={() => setCsvEnabled((value) => !value)}>
            {csvEnabled ? 'CSV ✓' : 'CSV'}
          </Button>
          <Button variant="secondary" onPress={() => setIsActive((value) => !value)}>
            {isActive ? 'Aktivan ✓' : 'Pauziran'}
          </Button>
        </View>

        {canSave ? (
          <Button
            loading={saveMutation.isPending}
            onPress={submit}
          >
            {editingId === null ? 'Sačuvaj raspored' : 'Sačuvaj izmene'}
          </Button>
        ) : (
          <Text style={styles.muted}>Ova izmena trenutno nije dostupna.</Text>
        )}
      </Card>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Rasporedi</Text>
        {data.data.length === 0 ? (
          <Card style={styles.compactCard}>
            <Text style={styles.muted}>Još nema rasporeda.</Text>
          </Card>
        ) : data.data.map((schedule) => (
          <Card key={schedule.id} style={styles.scheduleCard}>
            <View style={styles.scheduleHeader}>
              <View style={styles.scheduleCopy}>
                <Text style={styles.cardTitle}>{schedule.name}</Text>
                <Text style={styles.scheduleMeta}>
                  {reportTypeLabel(schedule.report_type)} · {scheduleCadence(schedule)}
                </Text>
              </View>
              <Text style={schedule.is_active ? styles.scheduleActive : styles.schedulePaused}>
                {schedule.is_active ? 'Aktivan' : 'Pauziran'}
              </Text>
            </View>
            <Text style={styles.scheduleMeta}>Zona: {schedule.timezone}</Text>
            <Text style={styles.scheduleMeta}>
              Primaoci: {schedule.recipients.join(', ') || '—'}
            </Text>
            <Text style={styles.scheduleMeta}>
              Prilozi: {schedule.formats.map((format) => format.toUpperCase()).join(' + ') || '—'}
            </Text>
            <Text style={styles.scheduleMeta}>
              Sledeće slanje: {displayDateTime(schedule.next_run_at)}
            </Text>
            <Text style={styles.scheduleMeta}>
              Poslednje uspešno: {displayDateTime(schedule.last_success_at)}
            </Text>
            <View style={styles.actionsRow}>
              {data.capabilities.update ? (
                <Button variant="secondary" onPress={() => editSchedule(schedule)}>
                  Izmeni
                </Button>
              ) : null}
              {data.capabilities.toggle ? (
                <Button
                  variant="secondary"
                  loading={toggleMutation.isPending}
                  onPress={() => toggleMutation.mutate(schedule.id)}
                >
                  {schedule.is_active ? 'Pauziraj' : 'Aktiviraj'}
                </Button>
              ) : null}
              {data.capabilities.run ? (
                <Button
                  variant="secondary"
                  onPress={() => setRunTarget(schedule)}
                >
                  Pokreni sada
                </Button>
              ) : null}
              {data.capabilities.delete ? (
                <Button
                  variant="secondary"
                  onPress={() => setDeleteTarget(schedule)}
                >
                  Obriši
                </Button>
              ) : null}
            </View>
          </Card>
        ))}
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Poslednja slanja</Text>
        {data.deliveries.length === 0 ? (
          <Card style={styles.compactCard}>
            <Text style={styles.muted}>Nema evidentiranih slanja.</Text>
          </Card>
        ) : data.deliveries.map((delivery) => (
          <Card key={delivery.id} style={styles.deliveryCard}>
            <View style={styles.scheduleHeader}>
              <View style={styles.scheduleCopy}>
                <Text style={styles.cardTitle}>{delivery.recipient_email}</Text>
                <Text style={styles.scheduleMeta}>
                  {reportTypeLabel(delivery.report_type)} · {delivery.status}
                </Text>
              </View>
              <Text style={styles.scheduleMeta}>Pokušaji: {delivery.attempt_count}</Text>
            </View>
            <Text style={styles.scheduleMeta}>
              Period: {displayDate(delivery.period_from)} – {displayDate(delivery.period_to)}
            </Text>
            <Text style={styles.scheduleMeta}>
              Zakazano: {displayDateTime(delivery.scheduled_for)}
            </Text>
            <Text style={styles.scheduleMeta}>
              Poslato: {displayDateTime(delivery.sent_at)}
            </Text>
            {delivery.can_retry && data.capabilities.retry_delivery ? (
              <View style={styles.actionsRow}>
                <Button
                  variant="secondary"
                  onPress={() => setRetryTarget(delivery)}
                >
                  Ponovi slanje
                </Button>
              </View>
            ) : null}
          </Card>
        ))}
      </View>

      <ConfirmAction
        visible={runTarget !== null}
        title="Pokreni slanje sada"
        message={runTarget ? `Raspored „${runTarget.name}“ će odmah kreirati isporuke kroz postojeći serverski red.` : ''}
        confirmLabel="Pokreni"
        busy={runMutation.isPending}
        onCancel={() => setRunTarget(null)}
        onConfirm={() => {
          if (runTarget) runMutation.mutate(runTarget.id);
        }}
      />

      <ConfirmAction
        visible={deleteTarget !== null}
        title="Obriši raspored"
        message={deleteTarget ? `Obriši raspored „${deleteTarget.name}“? Istorija prethodnih slanja ostaje sačuvana.` : ''}
        confirmLabel="Obriši"
        busy={deleteMutation.isPending}
        onCancel={() => setDeleteTarget(null)}
        onConfirm={() => {
          if (deleteTarget) deleteMutation.mutate(deleteTarget.id);
        }}
      />

      <ConfirmAction
        visible={retryTarget !== null}
        title="Ponovi slanje"
        message={retryTarget ? `Ponovo zakaži slanje za ${retryTarget.recipient_email}?` : ''}
        confirmLabel="Ponovi"
        busy={retryMutation.isPending}
        onCancel={() => setRetryTarget(null)}
        onConfirm={() => {
          if (retryTarget) retryMutation.mutate(retryTarget.id);
        }}
      />
    </View>
  );
}

function SummarySection({
  report,
  styles,
}: {
  report: AdminManagementReport;
  styles: ReturnType<typeof createStyles>;
}) {
  const summary = report.summary;

  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>Ključni pokazatelji</Text>
      <View style={styles.metricGrid}>
        <MetricCard label="Prihod" value={formatMoney(summary.revenue_rsd, 'RSD')} styles={styles} />
        <MetricCard label="Bruto dobit" value={formatMoney(summary.gross_profit_rsd, 'RSD')} styles={styles} />
        <MetricCard label="Neto doprinos" value={formatMoney(summary.net_contribution_rsd, 'RSD')} styles={styles} />
        <MetricCard label="Potraživanja" value={formatMoney(summary.outstanding_rsd, 'RSD')} styles={styles} />
        <MetricCard label="Porudžbine" value={String(summary.orders_count)} styles={styles} />
        <MetricCard label="Komada" value={String(summary.units_count)} styles={styles} />
        <MetricCard label="Bruto marža" value={`${summary.gross_margin_percent.toFixed(1)}%`} styles={styles} />
        <MetricCard label="Pokrivenost troška" value={`${summary.cost_coverage_percent.toFixed(1)}%`} styles={styles} />
      </View>

      <Card style={styles.detailCard}>
        <DetailRow label="Provizije" value={formatMoney(summary.commissions_rsd, 'RSD')} styles={styles} />
        <DetailRow label="Refundacije" value={formatMoney(summary.refunds_rsd, 'RSD')} styles={styles} />
        <DetailRow label="Servisni trošak" value={formatMoney(summary.service_cost_rsd, 'RSD')} styles={styles} />
        <DetailRow label="Prosečna porudžbina" value={formatMoney(summary.average_order_rsd, 'RSD')} styles={styles} />
        <DetailRow label="Stavke bez nabavne cene" value={String(summary.missing_cost_lines)} styles={styles} />
      </Card>
    </View>
  );
}

function SegmentsSection({
  report,
  styles,
}: {
  report: AdminManagementReport;
  styles: ReturnType<typeof createStyles>;
}) {
  if (report.segments.length === 0) return null;

  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>Profitabilnost po segmentu</Text>
      {report.segments.slice(0, 12).map((segment, index) => (
        <SegmentCard
          key={`${segment.label}-${index}`}
          segment={segment}
          styles={styles}
        />
      ))}
    </View>
  );
}

function TrendSection({
  report,
  styles,
}: {
  report: AdminManagementReport;
  styles: ReturnType<typeof createStyles>;
}) {
  if (report.trend.length === 0) return null;

  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>Trend</Text>
      {report.trend.slice(-12).map((point) => (
        <Card key={point.period} style={styles.compactCard}>
          <Text style={styles.cardTitle}>{point.period}</Text>
          <DetailRow label="Prihod" value={formatMoney(point.revenue_rsd, 'RSD')} styles={styles} />
          <DetailRow label="Bruto dobit" value={formatMoney(point.gross_profit_rsd, 'RSD')} styles={styles} />
        </Card>
      ))}
    </View>
  );
}

function InventorySection({
  report,
  styles,
}: {
  report: AdminManagementReport;
  styles: ReturnType<typeof createStyles>;
}) {
  const inventory = report.inventory;

  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>Lager</Text>
      <View style={styles.metricGrid}>
        <MetricCard label="Vrednost lagera" value={formatMoney(inventory.value_rsd, 'RSD')} styles={styles} />
        <MetricCard label="Jedinica" value={String(inventory.units_count)} styles={styles} />
        <MetricCard label="Stavki" value={String(inventory.items_count)} styles={styles} />
        <MetricCard label="Sporo obrtne" value={String(inventory.slow_items)} styles={styles} />
      </View>

      {inventory.top_value.slice(0, 8).map((item) => (
        <Card key={`${item.kind}-${item.id}`} style={styles.compactCard}>
          <Text style={styles.cardTitle}>{item.sku} · {item.name}</Text>
          <DetailRow label="Količina" value={String(item.quantity)} styles={styles} />
          <DetailRow label="Vrednost" value={formatMoney(item.value_rsd, 'RSD')} styles={styles} />
          <DetailRow label="Starost" value={`${item.age_days} dana`} styles={styles} />
          {!item.has_cost ? <Text style={styles.warning}>Nedostaje nabavna cena</Text> : null}
        </Card>
      ))}
    </View>
  );
}

function ReceivablesSection({
  report,
  styles,
}: {
  report: AdminManagementReport;
  styles: ReturnType<typeof createStyles>;
}) {
  const receivables = report.receivables;

  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>Potraživanja</Text>
      <View style={styles.metricGrid}>
        <MetricCard label="Otvorene porudžbine" value={String(receivables.open_orders)} styles={styles} />
        <MetricCard label="Ukupno otvoreno" value={formatMoney(receivables.outstanding_rsd, 'RSD')} styles={styles} />
      </View>

      <Card style={styles.detailCard}>
        {Object.entries(receivables.aging).map(([key, value]) => (
          <DetailRow
            key={key}
            label={AGING_LABELS[key] ?? key}
            value={formatMoney(value, 'RSD')}
            styles={styles}
          />
        ))}
      </Card>
    </View>
  );
}

function AfterSalesSection({
  report,
  styles,
}: {
  report: AdminManagementReport;
  styles: ReturnType<typeof createStyles>;
}) {
  const afterSales = report.after_sales;

  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>Postprodaja</Text>
      <View style={styles.metricGrid}>
        <MetricCard label="Slučajevi" value={String(afterSales.cases)} styles={styles} />
        <MetricCard label="Otvoreno" value={String(afterSales.open)} styles={styles} />
        <MetricCard label="Zatvoreno" value={String(afterSales.closed)} styles={styles} />
        <MetricCard label="Prekoračen rok" value={String(afterSales.overdue)} styles={styles} />
        <MetricCard label="Stopa reklamacija" value={`${afterSales.complaint_rate_percent.toFixed(1)}%`} styles={styles} />
        <MetricCard label="Servisni trošak" value={formatMoney(afterSales.service_cost_rsd, 'RSD')} styles={styles} />
      </View>
    </View>
  );
}

function TeamsSection({
  report,
  styles,
}: {
  report: AdminManagementReport;
  styles: ReturnType<typeof createStyles>;
}) {
  if (report.teams.length === 0) return null;

  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>Učinak odgovornih lica</Text>
      {report.teams.slice(0, 12).map((segment, index) => (
        <SegmentCard
          key={`${segment.label}-${index}`}
          segment={segment}
          styles={styles}
        />
      ))}
    </View>
  );
}

function MetricCard({
  label,
  value,
  styles,
}: {
  label: string;
  value: string;
  styles: ReturnType<typeof createStyles>;
}) {
  return (
    <Card style={styles.metricCard}>
      <Text style={styles.metricValue}>{value}</Text>
      <Text style={styles.metricLabel}>{label}</Text>
    </Card>
  );
}

function SegmentCard({
  segment,
  styles,
}: {
  segment: AdminReportSegment;
  styles: ReturnType<typeof createStyles>;
}) {
  return (
    <Card style={styles.compactCard}>
      <Text style={styles.cardTitle}>{segment.label || 'Bez oznake'}</Text>
      <DetailRow label="Porudžbine" value={String(segment.orders_count)} styles={styles} />
      <DetailRow label="Komada" value={String(segment.units_count)} styles={styles} />
      <DetailRow label="Prihod" value={formatMoney(segment.revenue_rsd, 'RSD')} styles={styles} />
      <DetailRow label="Bruto dobit" value={formatMoney(segment.gross_profit_rsd, 'RSD')} styles={styles} />
      <DetailRow label="Bruto marža" value={`${segment.gross_margin_percent.toFixed(1)}%`} styles={styles} />
      <DetailRow label="Pokrivenost troška" value={`${segment.cost_coverage_percent.toFixed(1)}%`} styles={styles} />
    </Card>
  );
}

function DetailRow({
  label,
  value,
  styles,
}: {
  label: string;
  value: string;
  styles: ReturnType<typeof createStyles>;
}) {
  return (
    <View style={styles.detailRow}>
      <Text style={styles.detailLabel}>{label}</Text>
      <Text style={styles.detailValue}>{value}</Text>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 140, gap: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    copy: { ...typography.body, color: theme.muted },
    periodCard: { gap: spacing.xs },
    periodLabel: { ...typography.h3, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    filtersCard: { gap: spacing.md },
    section: { gap: spacing.sm },
    sectionHead: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: spacing.sm },
    sectionTitle: { ...typography.h3, color: theme.ink },
    actionsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    metricGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    metricCard: { width: '48%', gap: spacing.xs },
    metricValue: { ...typography.h3, color: theme.ink },
    metricLabel: { ...typography.small, color: theme.muted },
    detailCard: { gap: spacing.sm },
    compactCard: { gap: spacing.sm },
    cardTitle: { ...typography.label, color: theme.ink },
    detailRow: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    detailLabel: { ...typography.small, color: theme.muted, flex: 1 },
    detailValue: { ...typography.body, color: theme.ink, flex: 1, textAlign: 'right' },
    warning: { ...typography.small, color: theme.danger },
    scheduleFormCard: { gap: spacing.md },
    scheduleCard: { gap: spacing.sm },
    deliveryCard: { gap: spacing.sm },
    scheduleHeader: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    scheduleCopy: { flex: 1, gap: spacing.xs },
    scheduleMeta: { ...typography.small, color: theme.muted },
    scheduleActive: { ...typography.label, color: theme.success },
    schedulePaused: { ...typography.label, color: theme.muted },
    formatRow: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  });
}
