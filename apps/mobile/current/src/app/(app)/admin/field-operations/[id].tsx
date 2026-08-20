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
import { Pill, type PillTone } from '@/components/ui/pill';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import {
  apiAdminFieldOperations,
  keyedFieldWorkPartConsumption,
  type AdminFieldWorkAttachment,
  type AdminFieldWorkDetail,
  type AdminFieldWorkPart,
} from '@/features/admin/field-operations-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { openAfterSalesAttachment } from '@/features/after-sales/attachment-download';
import {
  formatAfterSalesAttachmentSize,
  pickAfterSalesAttachments,
} from '@/features/after-sales/attachment-picker';
import { ApiError } from '@/lib/api/client';
import { formatDate } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';
import type { AfterSalesUploadFile } from '@/types/api';

type Panel = 'schedule' | 'complete' | 'cancel' | 'part-add' | null;

function statusTone(status: string): PillTone {
  if (status === 'completed') return 'success';
  if (status === 'cancelled') return 'danger';
  if (status === 'en_route' || status === 'on_site') return 'info';
  if (status === 'planned') return 'warning';
  return 'primary';
}

function errorMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return fallback;
}

function compact(value: string): string | null {
  const result = value.trim();
  return result ? result : null;
}

function numberOrNull(value: string, label: string): number | null {
  const normalized = value.trim().replace(',', '.');
  if (!normalized) return null;
  const parsed = Number(normalized);
  if (!Number.isFinite(parsed) || parsed < 0) throw new Error(`${label} mora biti broj jednak ili veći od nule.`);
  return parsed;
}

function positiveNumber(value: string, label: string): number {
  const parsed = numberOrNull(value, label);
  if (parsed === null || parsed <= 0) throw new Error(`${label} mora biti veći od nule.`);
  return parsed;
}

function formatNumber(value: number | null, digits = 2): string {
  if (value === null || !Number.isFinite(value)) return '—';
  return value.toLocaleString('sr-RS', { minimumFractionDigits: digits, maximumFractionDigits: digits });
}

function defaultConsumption(parts: AdminFieldWorkPart[]): Record<number, string> {
  const result: Record<number, string> = {};
  for (const line of parts) result[line.id] = String(line.requested_quantity ?? 0);
  return result;
}

function AttachmentRow({
  attachment,
  openingPath,
  onOpen,
}: {
  attachment: AdminFieldWorkAttachment;
  openingPath: string | null;
  onOpen: (attachment: AdminFieldWorkAttachment) => void;
}) {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  return (
    <View style={styles.fileRow}>
      <View style={styles.grow}>
        <Text style={styles.itemTitle}>{attachment.original_name}</Text>
        <Text style={styles.meta}>{attachment.visibility === 'public' ? 'Javno' : 'Interno'} · {formatAfterSalesAttachmentSize(attachment.size_bytes)}</Text>
      </View>
      <Button
        variant="secondary"
        loading={openingPath === attachment.download_path}
        disabled={openingPath !== null}
        onPress={() => onOpen(attachment)}
      >
        Otvori / podeli
      </Button>
    </View>
  );
}

export default function AdminFieldOperationsDetailScreen() {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { id } = useLocalSearchParams<{ id: string }> ();
  const workOrderId = Number(id);
  const { bootstrap, can } = useAuth();
  const allowed = can('field_operations.view');
  const validId = Number.isInteger(workOrderId) && workOrderId > 0;

  const [panel, setPanel] = useState<Panel> (null);
  const [teamId, setTeamId] = useState('');
  const [plannedStartAt, setPlannedStartAt] = useState('');
  const [plannedEndAt, setPlannedEndAt] = useState('');
  const [routeReference, setRouteReference] = useState('');
  const [publicNote, setPublicNote] = useState('');
  const [internalNote, setInternalNote] = useState('');

  const [completionResult, setCompletionResult] = useState('');
  const [completionRouteReference, setCompletionRouteReference] = useState('');
  const [travelKm, setTravelKm] = useState('');
  const [travelCost, setTravelCost] = useState('');
  const [laborCost, setLaborCost] = useState('');
  const [partsCost, setPartsCost] = useState('');
  const [partConsumption, setPartConsumption] = useState<Record<number, string>> ({});
  const [attachmentVisibility, setAttachmentVisibility] = useState<'internal' | 'public'> ('internal');
  const [completionAttachments, setCompletionAttachments] = useState<AfterSalesUploadFile[]> ([]);
  const [pickingAttachments, setPickingAttachments] = useState(false);
  const [openingAttachmentPath, setOpeningAttachmentPath] = useState<string | null> (null);

  const [cancelReason, setCancelReason] = useState('');

  const [servicePartId, setServicePartId] = useState('');
  const [requestedQuantity, setRequestedQuantity] = useState('1');
  const [supplyMode, setSupplyMode] = useState<'local_stock' | 'external'> ('local_stock');
  const [partNotes, setPartNotes] = useState('');

  const query = useQuery({
    queryKey: adminQueryKeys.fieldWorkOrder(workOrderId),
    queryFn: () => apiAdminFieldOperations.detail(workOrderId),
    enabled: allowed && validId,
  });
  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });

  const refreshAfterMutation = async () => {
    await client.invalidateQueries({ queryKey: adminQueryKeys.fieldOperations() });
  };

  const execute = async (title: string, run: () => Promise<unknown>): Promise<boolean> => {
    try {
      await mutation.mutateAsync(run);
      await refreshAfterMutation();
      setPanel(null);
      feedback.notify({ tone: 'success', title, message: 'Promena je potvrđena na serveru.' });
      return true;
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Akcija nije uspela', message: errorMessage(error, 'Pokušaj ponovo.') });
      return false;
    }
  };

  if (!allowed) return <UnavailableState title="Terenske operacije nisu dostupne" />;
  if (!validId) return <ErrorState error={new Error('Neispravan identifikator terenskog naloga.')} />;
  if (query.isLoading) return <LoadingState label="Učitavanje terenskog naloga…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const data = query.data.data;
  const teamOptions = [
    { value: '', label: 'Nije dodeljeno' },
    ...data.options.teams.map((team) => ({ value: String(team.id), label: `${team.name}${team.service_area ? ` · ${team.service_area}` : ''}` })),
  ];
  const servicePartOptions = [
    { value: '', label: 'Izaberi rezervni deo' },
    ...data.options.service_parts.map((part) => ({
      value: String(part.id),
      label: `${part.sku} · ${part.name} · stanje ${formatNumber(part.stock_quantity, 3)}`,
    })),
  ];

  const openSchedule = () => {
    setTeamId(data.team ? String(data.team.id) : '');
    setPlannedStartAt(data.planned_start_at ?? '');
    setPlannedEndAt(data.planned_end_at ?? '');
    setRouteReference(data.route_reference ?? '');
    setPublicNote(data.public_note ?? '');
    setInternalNote(data.internal_note ?? '');
    setPanel('schedule');
  };

  const openComplete = () => {
    setCompletionResult('');
    setCompletionRouteReference(data.route_reference ?? '');
    setTravelKm(data.travel_km === null ? '' : String(data.travel_km));
    setTravelCost(data.travel_cost_rsd === null ? '' : String(data.travel_cost_rsd));
    setLaborCost(data.labor_cost_rsd === null ? '' : String(data.labor_cost_rsd));
    setPartsCost('');
    setPartConsumption(defaultConsumption(data.parts));
    setAttachmentVisibility('internal');
    setCompletionAttachments([]);
    setPanel('complete');
  };

  const openCancel = () => {
    setCancelReason('');
    setPanel('cancel');
  };

  const openPartAdd = () => {
    setServicePartId('');
    setRequestedQuantity('1');
    setSupplyMode('local_stock');
    setPartNotes('');
    setPanel('part-add');
  };

  const chooseAttachments = async () => {
    setPickingAttachments(true);
    try {
      const result = await pickAfterSalesAttachments(completionAttachments, data.options.completion_limits);
      if (result.files.length > 0) setCompletionAttachments((current) => [...current, ...result.files]);
      if (result.rejected.length > 0) feedback.notify({ tone: 'warning', title: 'Neki prilozi nisu dodati', message: result.rejected.join(' ') });
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Prilozi nisu izabrani', message: errorMessage(error, 'Pokušaj ponovo.') });
    } finally {
      setPickingAttachments(false);
    }
  };

  const openAttachment = async (attachment: AdminFieldWorkAttachment) => {
    if (openingAttachmentPath !== null) return;
    setOpeningAttachmentPath(attachment.download_path);
    try {
      await openAfterSalesAttachment(attachment);
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Prilog nije otvoren', message: errorMessage(error, 'Pokušaj ponovo.') });
    } finally {
      setOpeningAttachmentPath(null);
    }
  };

  const submitSchedule = () => void execute(
    'Termin i ekipa su ažurirani',
    () => apiAdminFieldOperations.schedule(workOrderId, {
      field_service_team_id: teamId ? Number(teamId) : null,
      planned_start_at: compact(plannedStartAt),
      planned_end_at: compact(plannedEndAt),
      route_reference: compact(routeReference),
      public_note: compact(publicNote),
      internal_note: compact(internalNote),
    }),
  );

  const submitComplete = () => {
    try {
      const result = completionResult.trim();
      if (result.length < 5) throw new Error('Rezultat intervencije mora imati najmanje 5 znakova.');
      const rows = data.parts.map((line) => ({
        line_id: line.id,
        quantity: Number((partConsumption[line.id] ?? String(line.requested_quantity ?? 0)).replace(',', '.')),
        requested_quantity: line.requested_quantity ?? 0,
      }));
      const keyed = keyedFieldWorkPartConsumption(rows);
      void execute(
        'Terenski nalog je završen',
        () => apiAdminFieldOperations.complete(workOrderId, {
          route_reference: compact(completionRouteReference),
          completion_result: result,
          travel_km: numberOrNull(travelKm, 'Pređeni kilometri'),
          travel_cost_rsd: numberOrNull(travelCost, 'Trošak puta'),
          labor_cost_rsd: numberOrNull(laborCost, 'Trošak rada'),
          parts_cost_rsd: numberOrNull(partsCost, 'Dodatni trošak delova'),
          part_consumption: keyed,
          attachment_visibility: attachmentVisibility,
          attachments: completionAttachments.length ? completionAttachments : undefined,
        }),
      ).then((ok) => {
        if (!ok) return;
        setCompletionResult('');
        setCompletionAttachments([]);
        setPartConsumption({});
        setTravelKm('');
        setTravelCost('');
        setLaborCost('');
        setPartsCost('');
      });
    } catch (error) {
      feedback.notify({ tone: 'warning', title: 'Proveri podatke završetka', message: errorMessage(error, 'Unos nije validan.') });
    }
  };

  const submitPartAdd = () => {
    try {
      if (!servicePartId) throw new Error('Izaberi rezervni deo.');
      const quantity = positiveNumber(requestedQuantity, 'Količina');
      void execute(
        'Rezervni deo je dodat u nalog',
        () => apiAdminFieldOperations.partAdd(workOrderId, {
          service_part_id: Number(servicePartId),
          requested_quantity: quantity,
          supply_mode: supplyMode,
          notes: compact(partNotes),
        }),
      );
    } catch (error) {
      feedback.notify({ tone: 'warning', title: 'Proveri rezervni deo', message: errorMessage(error, 'Unos nije validan.') });
    }
  };

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Terenske operacije</Text>
      </Pressable>
      <PageHeader title={data.work_order_number} eyebrow="Admin · terenski nalog" name={bootstrap?.user.name} />

      <Card style={styles.hero}>
        <View style={styles.rowBetween}>
          <View style={styles.grow}>
            <Text style={styles.subject}>{data.case?.subject ?? 'Terenska intervencija'}</Text>
            <Text style={styles.meta}>Postprodaja: {data.case?.case_number ?? '—'} · Porudžbina: {data.order?.order_number ?? '—'}</Text>
          </View>
          <Pill tone={statusTone(data.status)}>{data.status_label}</Pill>
        </View>
        <Text style={styles.meta}>Ekipa: {data.team?.name ?? 'Nije dodeljeno'}</Text>
        <Text style={styles.meta}>Termin: {data.planned_start_at ? formatDate(data.planned_start_at, true) : 'Nije zakazan'}{data.planned_end_at ? ` – ${formatDate(data.planned_end_at, true)}` : ''}</Text>
        {data.route_reference ? <Text style={styles.meta}>Referenca: {data.route_reference}</Text> : null}
        <View style={styles.actions}>
          {data.capabilities.can_schedule ? <Button variant="secondary" onPress={openSchedule}>Termin / ekipa</Button> : null}
          {data.capabilities.can_mark_en_route ? <Button onPress={() => void execute('Ekipa je na putu', () => apiAdminFieldOperations.enRoute(workOrderId))}>Na putu</Button> : null}
          {data.capabilities.can_mark_on_site ? <Button onPress={() => void execute('Ekipa je na lokaciji', () => apiAdminFieldOperations.onSite(workOrderId))}>Na lokaciji</Button> : null}
          {data.capabilities.can_complete ? <Button onPress={openComplete}>Završi nalog</Button> : null}
          {data.capabilities.can_cancel ? <Button variant="secondary" onPress={openCancel}>Otkaži nalog</Button> : null}
          {data.capabilities.can_add_parts ? <Button variant="secondary" onPress={openPartAdd}>Dodaj deo</Button> : null}
          {data.capabilities.can_reserve_parts && data.parts.some((line) => line.supply_mode === 'local_stock') ? (
            <Button variant="secondary" onPress={() => void execute('Lokalni delovi su rezervisani', () => apiAdminFieldOperations.partReserve(workOrderId))}>Rezerviši lokalne delove</Button>
          ) : null}
        </View>
      </Card>

      {panel === 'schedule' ? (
        <Card style={styles.panel}>
          <View style={styles.rowBetween}><Text style={styles.sectionTitle}>Termin i ekipa</Text><Button variant="secondary" onPress={() => setPanel(null)}>Zatvori</Button></View>
          <SelectSheet label="Terenska ekipa" value={teamId} options={teamOptions} onChange={setTeamId} />
          <DateTimeField label="Planirani početak" value={plannedStartAt} onChangeText={setPlannedStartAt} />
          <DateTimeField label="Planirani završetak" value={plannedEndAt} onChangeText={setPlannedEndAt} />
          <TextField label="Referenca / ruta" value={routeReference} onChangeText={setRouteReference} />
          <TextField label="Javna napomena" value={publicNote} onChangeText={setPublicNote} multiline />
          <TextField label="Interna napomena" value={internalNote} onChangeText={setInternalNote} multiline />
          <Button loading={mutation.isPending} onPress={submitSchedule}>Sačuvaj raspored</Button>
        </Card>
      ) : null}

      {panel === 'complete' ? (
        <Card style={styles.panel}>
          <View style={styles.rowBetween}><Text style={styles.sectionTitle}>Završi radni nalog</Text><Button variant="secondary" onPress={() => setPanel(null)}>Zatvori</Button></View>
          <TextField label="Rezultat intervencije" value={completionResult} onChangeText={setCompletionResult} multiline />
          <TextField label="Završna referenca" value={completionRouteReference} onChangeText={setCompletionRouteReference} />
          <TextField label="Pređeni kilometri" value={travelKm} onChangeText={setTravelKm} keyboardType="decimal-pad" />
          <TextField label="Trošak puta RSD" value={travelCost} onChangeText={setTravelCost} keyboardType="decimal-pad" />
          <TextField label="Trošak rada RSD" value={laborCost} onChangeText={setLaborCost} keyboardType="decimal-pad" />
          <TextField label="Dodatni trošak delova RSD" value={partsCost} onChangeText={setPartsCost} keyboardType="decimal-pad" />
          {data.parts.length > 0 ? <Text style={styles.sectionTitle}>Stvarni utrošak delova</Text> : null}
          {data.parts.map((line) => (
            <TextField
              key={line.id}
              label={`${line.service_part?.sku ?? ''} ${line.service_part?.name ?? `Deo #${line.id}`} · traženo ${formatNumber(line.requested_quantity, 3)}`.trim()}
              value={partConsumption[line.id] ?? String(line.requested_quantity ?? 0)}
              onChangeText={(value) => setPartConsumption((current) => ({ ...current, [line.id]: value }))}
              keyboardType="decimal-pad"
            />
          ))}
          <Text style={styles.warning}>Utrošak se šalje kao objekat ključevan stvarnim FieldWorkOrderPart ID-jem; neiskorišćena rezervacija se vraća kroz backend servis.</Text>
          <SelectSheet
            label="Vidljivost dokaza"
            value={attachmentVisibility}
            options={data.options.completion_limits.attachment_visibilities.map((value) => ({ value, label: value === 'public' ? 'Vidljivo kupcu' : 'Samo interno' }))}
            onChange={(value) => { if (value === 'public' || value === 'internal') setAttachmentVisibility(value); }}
          />
          <Text style={styles.meta}>Do {data.options.completion_limits.max_attachments} priloga, do {formatAfterSalesAttachmentSize(data.options.completion_limits.max_attachment_bytes)} po fajlu.</Text>
          <Button variant="secondary" disabled={completionAttachments.length >= data.options.completion_limits.max_attachments} loading={pickingAttachments} onPress={() => void chooseAttachments()}>
            Dodaj priloge ({completionAttachments.length}/{data.options.completion_limits.max_attachments})
          </Button>
          {completionAttachments.map((attachment, index) => (
            <View key={`${attachment.uri}:${index}`} style={styles.fileRow}>
              <View style={styles.grow}><Text style={styles.itemTitle}>{attachment.name}</Text><Text style={styles.meta}>{attachment.type} · {formatAfterSalesAttachmentSize(attachment.size)}</Text></View>
              <Button variant="secondary" onPress={() => setCompletionAttachments((current) => current.filter((_, itemIndex) => itemIndex !== index))}>Ukloni</Button>
            </View>
          ))}
          <Button loading={mutation.isPending} disabled={completionResult.trim().length < 5} onPress={submitComplete}>Potvrdi završetak</Button>
        </Card>
      ) : null}

      {panel === 'cancel' ? (
        <Card style={styles.panel}>
          <View style={styles.rowBetween}><Text style={styles.sectionTitle}>Otkaži radni nalog</Text><Button variant="secondary" onPress={() => setPanel(null)}>Zatvori</Button></View>
          <TextField label="Razlog otkazivanja" value={cancelReason} onChangeText={setCancelReason} multiline />
          <Button loading={mutation.isPending} disabled={cancelReason.trim().length < 5} onPress={() => void execute('Terenski nalog je otkazan', () => apiAdminFieldOperations.cancel(workOrderId, cancelReason.trim()))}>Otkaži nalog</Button>
        </Card>
      ) : null}

      {panel === 'part-add' ? (
        <Card style={styles.panel}>
          <View style={styles.rowBetween}><Text style={styles.sectionTitle}>Dodaj rezervni deo</Text><Button variant="secondary" onPress={() => setPanel(null)}>Zatvori</Button></View>
          <SelectSheet label="Rezervni deo" value={servicePartId} options={servicePartOptions} onChange={setServicePartId} />
          <TextField label="Tražena količina" value={requestedQuantity} onChangeText={setRequestedQuantity} keyboardType="decimal-pad" />
          <SelectSheet
            label="Izvor"
            value={supplyMode}
            options={[{ value: 'local_stock', label: 'Lokalni servisni lager' }, { value: 'external', label: 'Spoljna nabavka / deo donosi ekipa' }]}
            onChange={(value) => { if (value === 'local_stock' || value === 'external') setSupplyMode(value); }}
          />
          <TextField label="Napomena" value={partNotes} onChangeText={setPartNotes} />
          <Button loading={mutation.isPending} disabled={!servicePartId} onPress={submitPartAdd}>Dodaj ili izmeni deo</Button>
        </Card>
      ) : null}

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Operativni podaci</Text>
        <Text style={styles.meta}>Ekipa: {data.team?.name ?? 'Nije dodeljeno'}{data.team?.phone ? ` · ${data.team.phone}` : ''}</Text>
        <Text style={styles.meta}>Na putu: {data.en_route_at ? formatDate(data.en_route_at, true) : '—'}</Text>
        <Text style={styles.meta}>Na lokaciji: {data.on_site_at ? formatDate(data.on_site_at, true) : '—'}</Text>
        <Text style={styles.meta}>Završeno: {data.completed_at ? formatDate(data.completed_at, true) : '—'}</Text>
        {data.public_note ? <Text style={styles.body}>Javno: {data.public_note}</Text> : null}
        {data.internal_note ? <Text style={styles.body}>Interno: {data.internal_note}</Text> : null}
        {data.completion_result ? <Text style={styles.body}>Rezultat: {data.completion_result}</Text> : null}
        {data.cancellation_reason ? <Text style={styles.warning}>Otkazivanje: {data.cancellation_reason}</Text> : null}
      </Card>

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Troškovi</Text>
        <Text style={styles.meta}>Kilometraža: {formatNumber(data.travel_km, 2)} km</Text>
        <Text style={styles.meta}>Put: {formatNumber(data.travel_cost_rsd)} RSD</Text>
        <Text style={styles.meta}>Rad: {formatNumber(data.labor_cost_rsd)} RSD</Text>
        <Text style={styles.meta}>Delovi: {formatNumber(data.parts_cost_rsd)} RSD</Text>
      </Card>

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Rezervni delovi</Text>
        {data.parts.length === 0 ? <Text style={styles.meta}>Nema planiranih delova.</Text> : data.parts.map((line) => (
          <View key={line.id} style={styles.lineRow}>
            <View style={styles.grow}>
              <Text style={styles.itemTitle}>{line.service_part?.sku ?? 'Deo'} · {line.service_part?.name ?? `#${line.id}`}</Text>
              <Text style={styles.meta}>Traženo {formatNumber(line.requested_quantity, 3)} · rezervisano {formatNumber(line.reserved_quantity, 3)} · utrošeno {formatNumber(line.consumed_quantity, 3)}</Text>
              <Text style={styles.meta}>Izvor: {line.supply_mode === 'local_stock' ? 'Lokalni lager' : 'Spoljni'}</Text>
              {line.notes ? <Text style={styles.body}>{line.notes}</Text> : null}
            </View>
            {data.capabilities.can_remove_parts ? <Button variant="secondary" onPress={() => void execute('Rezervni deo je uklonjen', () => apiAdminFieldOperations.partRemove(workOrderId, line.id))}>Ukloni</Button> : null}
          </View>
        ))}
      </Card>

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Dokazi i dokumentacija</Text>
        {data.attachments.length === 0 ? <Text style={styles.meta}>Nema priloga.</Text> : data.attachments.map((attachment) => (
          <AttachmentRow key={attachment.download_path} attachment={attachment} openingPath={openingAttachmentPath} onOpen={(file) => void openAttachment(file)} />
        ))}
      </Card>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: 120 },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    hero: { gap: spacing.md },
    panel: { gap: spacing.md, borderColor: theme.primary },
    section: { gap: spacing.md },
    subject: { ...typography.h2, color: theme.ink },
    sectionTitle: { ...typography.h3, color: theme.ink },
    itemTitle: { ...typography.label, color: theme.ink },
    body: { ...typography.body, color: theme.ink },
    meta: { ...typography.small, color: theme.muted },
    warning: { ...typography.small, color: theme.primary, fontWeight: '800' },
    rowBetween: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    grow: { flex: 1, minWidth: 0 },
    actions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    lineRow: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, borderTopWidth: 1, borderTopColor: theme.line, paddingTop: spacing.md },
    fileRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, borderTopWidth: 1, borderTopColor: theme.line, paddingTop: spacing.md },
  });
}
