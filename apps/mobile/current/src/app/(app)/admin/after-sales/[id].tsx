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
import { MoneyField } from '@/components/ui/money-field';
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
import { Pill, type PillTone } from '@/components/ui/pill';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { openAdminAfterSalesAttachment } from '@/features/admin/after-sales-admin-attachment';
import {
  apiAdminAfterSales,
  keyedAdminAfterSalesActionItems,
  type AdminAfterSalesAction,
  type AdminAfterSalesActionItemDraft,
  type AdminAfterSalesAttachment,
  type AdminAfterSalesDetail,
  type AdminAfterSalesOptionMap,
} from '@/features/admin/after-sales-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import {
  formatAfterSalesAttachmentSize,
  pickAfterSalesAttachments,
} from '@/features/after-sales/attachment-picker';
import { ApiError } from '@/lib/api/client';
import { formatDate } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';
import type { AfterSalesUploadFile } from '@/types/api';

// MOBILE_V1_0_ADMIN_AFTER_SALES_DETAIL_UX_REORGANIZATION_BATCH83
type AfterSalesWorkspace = 'overview' | 'case' | 'communication' | 'actions' | 'history';
type Panel = 'update' | 'message' | 'action-create' | 'action-complete' | 'action-cancel' | null;
type ActionItemState = { selected: boolean; quantity: string; disposition: string };

const AFTER_SALES_WORKSPACE_OPTIONS: Array<{ value: AfterSalesWorkspace; label: string; description: string }> = [
  { value: 'overview', label: 'Pregled', description: 'Status, rok i sažetak sadržaja postprodajnog slučaja.' },
  { value: 'case', label: 'Slučaj', description: 'Kupac, zahtev, pogođene stavke, prilozi i izmena slučaja.' },
  { value: 'communication', label: 'Komunikacija', description: 'Javne i interne poruke sa bezbednim prilozima.' },
  { value: 'actions', label: 'Radnje', description: 'Izvršne radnje, terenski nalozi, završetak i otkazivanje.' },
  { value: 'history', label: 'Istorija', description: 'Read-only sled promena statusa i audit kontekst.' },
];

function statusTone(status: string): PillTone {
  if (status === 'resolved' || status === 'approved' || status === 'closed' || status === 'completed') return 'success';
  if (status === 'rejected' || status === 'cancelled') return 'danger';
  if (status === 'under_review' || status === 'awaiting_customer' || status === 'planned') return 'warning';
  if (status === 'in_service' || status === 'in_progress') return 'info';
  return 'primary';
}

function errorMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  if (error instanceof Error && error.message) return error.message;
  return fallback;
}

function optionsFromMap(map: AdminAfterSalesOptionMap, emptyLabel?: string) {
  const values = Object.entries(map).map(([value, label]) => ({ value, label }));
  return emptyLabel ? [{ value: '', label: emptyLabel }, ...values] : values;
}

function compact(value: string): string | null {
  const result = value.trim();
  return result ? result : null;
}

function formatRsd(value: number | null): string {
  if (value === null || !Number.isFinite(value)) return '—';
  return `${value.toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} RSD`;
}

export default function AdminAfterSalesDetailScreen() {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { id } = useLocalSearchParams<{ id: string }> ();
  const caseId = Number(id);
  const { bootstrap, can } = useAuth();
  const allowed = can('after_sales.manage');
  const validId = Number.isInteger(caseId) && caseId > 0;

  const [workspace, setWorkspace] = useState<AfterSalesWorkspace> ('overview');
  const [panel, setPanel] = useState<Panel> (null);
  const [statusValue, setStatusValue] = useState('');
  const [priorityValue, setPriorityValue] = useState('');
  const [assigneeId, setAssigneeId] = useState('');
  const [dueAt, setDueAt] = useState('');
  const [resolutionType, setResolutionType] = useState('');
  const [resolutionSummary, setResolutionSummary] = useState('');
  const [updateNote, setUpdateNote] = useState('');

  const [messageBody, setMessageBody] = useState('');
  const [messageVisibility, setMessageVisibility] = useState<'public' | 'internal'> ('public');
  const [messageAttachments, setMessageAttachments] = useState<AfterSalesUploadFile[]> ([]);
  const [pickingAttachments, setPickingAttachments] = useState(false);
  const [openingAttachmentId, setOpeningAttachmentId] = useState<number | null> (null);

  const [actionType, setActionType] = useState('');
  const [inventoryHandling, setInventoryHandling] = useState('none');
  const [actionAssigneeId, setActionAssigneeId] = useState('');
  const [fieldTeamId, setFieldTeamId] = useState('');
  const [scheduledAt, setScheduledAt] = useState('');
  const [scheduledEndAt, setScheduledEndAt] = useState('');
  const [actionDueAt, setActionDueAt] = useState('');
  const [actionAmount, setActionAmount] = useState('');
  const [actionReference, setActionReference] = useState('');
  const [actionPublicNote, setActionPublicNote] = useState('');
  const [actionInternalNote, setActionInternalNote] = useState('');
  const [actionItems, setActionItems] = useState<Record<number, ActionItemState>> ({});

  const [selectedActionId, setSelectedActionId] = useState<number | null> (null);
  const [completionReference, setCompletionReference] = useState('');
  const [completionNote, setCompletionNote] = useState('');
  const [cancellationReason, setCancellationReason] = useState('');

  const query = useQuery({
    queryKey: adminQueryKeys.afterSalesAdminCase(caseId),
    queryFn: () => apiAdminAfterSales.detail(caseId),
    enabled: allowed && validId,
  });

  const mutation = useMutation({ mutationFn: (run: () => Promise<unknown>) => run() });

  const refreshAfterMutation = async () => {
    await client.invalidateQueries({ queryKey: adminQueryKeys.afterSalesAdmin() });
  };

  const execute = async (title: string, run: () => Promise<unknown>): Promise<boolean> => {
    try {
      await mutation.mutateAsync(run);
      await refreshAfterMutation();
      setPanel(null);
      feedback.notify({ tone: 'success', title, message: 'Promena je potvrđena na serveru.' });
      return true;
    } catch (error) {
      feedback.notify({
        tone: 'danger',
        title: 'Akcija nije uspela',
        message: errorMessage(error, 'Pokušaj ponovo.'),
      });
      return false;
    }
  };

  if (!allowed) return <UnavailableState title="Administracija postprodaje nije dostupna" />;
  if (!validId) return <ErrorState error={new Error('Neispravan identifikator postprodajnog slučaja.')} />;
  if (query.isLoading) return <LoadingState label="Učitavanje postprodajnog slučaja…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const data = query.data.data;
  const assigneeOptions = [
    { value: '', label: 'Nije dodeljeno' },
    ...data.options.assignees.map((user) => ({ value: String(user.id), label: user.name })),
  ];
  const teamOptions = [
    { value: '', label: 'Bez terenske ekipe' },
    ...data.options.field_teams.map((team) => ({ value: String(team.id), label: team.name })),
  ];
  const availableActionTypes = Object.entries(data.options.actions.types)
    .filter(([value]) => value !== 'refund' || data.capabilities.refund)
    .map(([value, label]) => ({ value, label }));
  const dispositionOptions = optionsFromMap(data.options.actions.dispositions);
  const workspaceMeta = AFTER_SALES_WORKSPACE_OPTIONS.find((option) => option.value === workspace);

  const selectWorkspace = (next: AfterSalesWorkspace) => {
    setWorkspace(next);
    setPanel(null);
    setSelectedActionId(null);
  };

  const openUpdate = () => {
    setWorkspace('case');
    setStatusValue(data.status);
    setPriorityValue(data.priority);
    setAssigneeId(data.assignee ? String(data.assignee.id) : '');
    setDueAt(data.due_at ?? '');
    setResolutionType(data.resolution_type ?? '');
    setResolutionSummary(data.resolution_summary ?? '');
    setUpdateNote('');
    setPanel('update');
  };

  const chooseMessageAttachments = async () => {
    setPickingAttachments(true);
    try {
      const result = await pickAfterSalesAttachments(messageAttachments, data.options.message_limits);
      if (result.files.length > 0) setMessageAttachments((current) => [...current, ...result.files]);
      if (result.rejected.length > 0) {
        feedback.notify({ tone: 'warning', title: 'Neki prilozi nisu dodati', message: result.rejected.join(' ') });
      }
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Prilozi nisu izabrani', message: errorMessage(error, 'Pokušaj ponovo.') });
    } finally {
      setPickingAttachments(false);
    }
  };

  const openAttachment = async (attachment: AdminAfterSalesAttachment) => {
    if (openingAttachmentId !== null) return;
    setOpeningAttachmentId(attachment.id);
    try {
      await openAdminAfterSalesAttachment(attachment);
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Prilog nije otvoren', message: errorMessage(error, 'Pokušaj ponovo.') });
    } finally {
      setOpeningAttachmentId(null);
    }
  };

  const openActionCreate = () => {
    setWorkspace('actions');
    setActionType(availableActionTypes[0]?.value ?? '');
    setInventoryHandling(Object.keys(data.options.actions.inventory_handling)[0] ?? 'none');
    setActionAssigneeId('');
    setFieldTeamId('');
    setScheduledAt('');
    setScheduledEndAt('');
    setActionDueAt('');
    setActionAmount('');
    setActionReference('');
    setActionPublicNote('');
    setActionInternalNote('');
    setActionItems({});
    setPanel('action-create');
  };

  const updateActionItem = (itemId: number, patch: Partial<ActionItemState>) => {
    setActionItems((current) => ({
      ...current,
      [itemId]: {
        selected: current[itemId]?.selected ?? false,
        quantity: current[itemId]?.quantity ?? '1',
        disposition: current[itemId]?.disposition ?? dispositionOptions[0]?.value ?? '',
        ...patch,
      },
    }));
  };

  const submitUpdate = () => execute(
    'Postprodajni slučaj je ažuriran',
    () => apiAdminAfterSales.update(caseId, {
      status: statusValue,
      priority: priorityValue,
      assigned_to: assigneeId ? Number(assigneeId) : null,
      due_at: compact(dueAt),
      resolution_type: compact(resolutionType),
      resolution_summary: compact(resolutionSummary),
      note: compact(updateNote),
    }),
  );

  const submitMessage = () => {
    const body = messageBody.trim();
    if (!body) {
      feedback.notify({ tone: 'warning', title: 'Poruka je prazna', message: 'Unesi tekst poruke.' });
      return;
    }
    void execute(
      'Poruka je poslata',
      () => apiAdminAfterSales.message(caseId, {
        body,
        visibility: messageVisibility,
        attachments: messageAttachments.length ? messageAttachments : undefined,
      }),
    ).then((ok) => {
      if (!ok) return;
      setMessageBody('');
      setMessageAttachments([]);
    });
  };

  const submitActionCreate = () => {
    try {
      const rows: AdminAfterSalesActionItemDraft[] = data.items.map((item) => {
        const draft = actionItems[item.id];
        return {
          case_item_id: item.id,
          selected: draft?.selected ?? false,
          quantity: Number(draft?.quantity ?? '1'),
          disposition: draft?.disposition || undefined,
        };
      });
      const keyedItems = keyedAdminAfterSalesActionItems(rows);
      const amount = compact(actionAmount);
      void execute(
        'Izvršna radnja je kreirana',
        () => apiAdminAfterSales.actionCreate(caseId, {
          action_type: actionType,
          inventory_handling: compact(inventoryHandling),
          assigned_to: actionAssigneeId ? Number(actionAssigneeId) : null,
          scheduled_at: compact(scheduledAt),
          scheduled_end_at: compact(scheduledEndAt),
          field_service_team_id: fieldTeamId ? Number(fieldTeamId) : null,
          due_at: compact(actionDueAt),
          amount_rsd: amount ? Number(amount.replace(',', '.')) : null,
          reference: compact(actionReference),
          public_note: compact(actionPublicNote),
          internal_note: compact(actionInternalNote),
          items: keyedItems,
        }),
      );
    } catch (error) {
      feedback.notify({ tone: 'warning', title: 'Radnja nije spremna', message: errorMessage(error, 'Proveri izabrane stavke.') });
    }
  };

  const openComplete = (action: AdminAfterSalesAction) => {
    setWorkspace('actions');
    setSelectedActionId(action.id);
    setCompletionReference(action.reference ?? '');
    setCompletionNote('');
    setPanel('action-complete');
  };

  const openCancel = (action: AdminAfterSalesAction) => {
    setWorkspace('actions');
    setSelectedActionId(action.id);
    setCancellationReason('');
    setPanel('action-cancel');
  };

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Postprodaja</Text>
      </Pressable>
      <PageHeader title={data.case_number} eyebrow="Admin · postprodaja" name={bootstrap?.user.name} />

      <Card style={styles.hero}>
        <View style={styles.rowBetween}>
          <View style={styles.grow}>
            <Text style={styles.subject}>{data.subject}</Text>
            <Text style={styles.meta}>{data.case_type_label} · {data.priority_label}</Text>
          </View>
          <Pill tone={statusTone(data.status)}>{data.status_label}</Pill>
        </View>
        <Text style={styles.body}>{data.description}</Text>
        <Text style={styles.meta}>Porudžbina: {data.order.order_number ?? `#${data.order.id}`}</Text>
        <Text style={styles.meta}>Odgovorno lice: {data.assignee?.name ?? 'Nije dodeljeno'}</Text>
        <Text style={styles.meta}>Rok: {data.due_at ? formatDate(data.due_at, true) : 'Nije definisan'}</Text>
      </Card>

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Operativne akcije</Text>
        <View style={styles.actions}>
          {data.capabilities.update ? <Button variant="secondary" onPress={openUpdate}>Izmeni slučaj</Button> : null}
          {data.capabilities.message ? <Button variant="secondary" onPress={() => { setWorkspace('communication'); setPanel('message'); }}>Nova poruka</Button> : null}
          {data.capabilities.execute ? <Button variant="secondary" onPress={openActionCreate}>Nova izvršna radnja</Button> : null}
          <Button variant="secondary" onPress={() => void query.refetch()}>Osveži detalj</Button>
        </View>
      </Card>

      <Card style={styles.workspaceCard}>
        <Text style={styles.sectionTitle}>Radni prostor postprodaje</Text>
        <Text style={styles.meta}>{workspaceMeta?.description ?? 'Izaberi deo slučaja koji želiš da obradiš.'}</Text>
        <FilterBar>
          {AFTER_SALES_WORKSPACE_OPTIONS.map((option) => (
            <FilterChip
              key={option.value}
              label={option.label}
              active={workspace === option.value}
              onPress={() => selectWorkspace(option.value)}
            />
          ))}
        </FilterBar>
      </Card>

      <Card style={[styles.section, workspace !== 'overview' && styles.hidden]}>
        <Text style={styles.sectionTitle}>Pregled slučaja</Text>
        <Text style={styles.meta}>Status: {data.status_label} · Prioritet: {data.priority_label}</Text>
        <Text style={styles.meta}>Pogođene stavke: {data.items.length} · Prilozi: {data.attachments.length}</Text>
        <Text style={styles.meta}>Poruke: {data.messages.length} · Izvršne radnje: {data.actions.length}</Text>
        <Text style={styles.meta}>Istorija statusa: {data.history.length} zapisa</Text>
      </Card>

      {panel === 'update' ? (
        <Card style={styles.panel}>
          <View style={styles.rowBetween}><Text style={styles.sectionTitle}>Izmena slučaja</Text><Button variant="secondary" onPress={() => setPanel(null)}>Zatvori</Button></View>
          <SelectSheet label="Status" value={statusValue} options={optionsFromMap(data.options.case.statuses)} onChange={setStatusValue} />
          <SelectSheet label="Prioritet" value={priorityValue} options={optionsFromMap(data.options.case.priorities)} onChange={setPriorityValue} />
          <SelectSheet label="Odgovorno lice" value={assigneeId} options={assigneeOptions} onChange={setAssigneeId} />
          <DateTimeField label="Rok" value={dueAt} onChangeText={setDueAt} />
          <SelectSheet label="Rešenje" value={resolutionType} options={optionsFromMap(data.options.case.resolutions, 'Nije definisano')} onChange={setResolutionType} />
          <TextField label="Sažetak rešenja" value={resolutionSummary} onChangeText={setResolutionSummary} multiline />
          <TextField label="Interna napomena promene" value={updateNote} onChangeText={setUpdateNote} multiline />
          <Button loading={mutation.isPending} onPress={() => void submitUpdate()}>Sačuvaj izmene</Button>
        </Card>
      ) : null}

      {panel === 'message' ? (
        <Card style={styles.panel}>
          <View style={styles.rowBetween}><Text style={styles.sectionTitle}>Nova poruka</Text><Button variant="secondary" onPress={() => setPanel(null)}>Zatvori</Button></View>
          <SelectSheet label="Vidljivost" value={messageVisibility} options={[{ value: 'public', label: 'Javna poruka' }, { value: 'internal', label: 'Interna poruka' }]} onChange={(value) => { if (value === 'public' || value === 'internal') setMessageVisibility(value); }} />
          <TextField label="Poruka" value={messageBody} onChangeText={setMessageBody} multiline />
          <Text style={styles.meta}>Do {data.options.message_limits.max_attachments} priloga · do {formatAfterSalesAttachmentSize(data.options.message_limits.max_attachment_bytes)} po fajlu.</Text>
          <Button variant="secondary" loading={pickingAttachments} disabled={messageAttachments.length >= data.options.message_limits.max_attachments} onPress={() => void chooseMessageAttachments()}>
            Dodaj priloge ({messageAttachments.length}/{data.options.message_limits.max_attachments})
          </Button>
          {messageAttachments.map((attachment, index) => (
            <View key={`${attachment.uri}:${index}`} style={styles.fileRow}>
              <View style={styles.grow}><Text style={styles.itemTitle}>{attachment.name}</Text><Text style={styles.meta}>{attachment.type} · {formatAfterSalesAttachmentSize(attachment.size)}</Text></View>
              <Button variant="secondary" onPress={() => setMessageAttachments((current) => current.filter((_, currentIndex) => currentIndex !== index))}>Ukloni</Button>
            </View>
          ))}
          <Button loading={mutation.isPending} disabled={!messageBody.trim()} onPress={submitMessage}>Pošalji poruku</Button>
        </Card>
      ) : null}

      {panel === 'action-create' ? (
        <Card style={styles.panel}>
          <View style={styles.rowBetween}><Text style={styles.sectionTitle}>Nova izvršna radnja</Text><Button variant="secondary" onPress={() => setPanel(null)}>Zatvori</Button></View>
          <SelectSheet label="Vrsta radnje" value={actionType} options={availableActionTypes} onChange={setActionType} />
          <SelectSheet label="Rukovanje lagerom" value={inventoryHandling} options={optionsFromMap(data.options.actions.inventory_handling)} onChange={setInventoryHandling} />
          <SelectSheet label="Odgovorno lice" value={actionAssigneeId} options={assigneeOptions} onChange={setActionAssigneeId} />
          <SelectSheet label="Terenska ekipa" value={fieldTeamId} options={teamOptions} onChange={setFieldTeamId} />
          <DateTimeField label="Planirani početak" value={scheduledAt} onChangeText={setScheduledAt} />
          <DateTimeField label="Planirani završetak" value={scheduledEndAt} onChangeText={setScheduledEndAt} />
          <DateTimeField label="Rok izvršenja" value={actionDueAt} onChangeText={setActionDueAt} />
          <MoneyField label="Iznos" value={actionAmount} onChangeText={setActionAmount} currency="RSD" />
          <TextField label="Referenca" value={actionReference} onChangeText={setActionReference} />
          <TextField label="Javna napomena" value={actionPublicNote} onChangeText={setActionPublicNote} multiline />
          <TextField label="Interna napomena" value={actionInternalNote} onChangeText={setActionInternalNote} multiline />
          <Text style={styles.sectionTitle}>Stavke radnje</Text>
          {data.items.map((item) => {
            const draft = actionItems[item.id];
            const selected = draft?.selected ?? false;
            const quantity = draft?.quantity ?? '1';
            const disposition = draft?.disposition ?? dispositionOptions[0]?.value ?? '';
            return (
              <Card key={item.id} style={styles.itemCard}>
                <Text style={styles.itemTitle}>{item.name}</Text>
                <Text style={styles.meta}>{item.sku ? `SKU ${item.sku} · ` : ''}Dostupno u slučaju: {item.quantity}</Text>
                <Button variant="secondary" onPress={() => updateActionItem(item.id, { selected: !selected })}>{selected ? 'Ukloni iz radnje' : 'Dodaj u radnju'}</Button>
                {selected ? (
                  <>
                    <TextField label="Količina" value={quantity} onChangeText={(value) => updateActionItem(item.id, { quantity: value })} keyboardType="number-pad" />
                    {dispositionOptions.length > 0 ? <SelectSheet label="Ishod stavke" value={disposition} options={dispositionOptions} onChange={(value) => updateActionItem(item.id, { disposition: value })} /> : null}
                  </>
                ) : null}
              </Card>
            );
          })}
          <Text style={styles.warning}>Stavke se šalju serveru kao objekat ključevan stvarnim ID-jem stavke postprodajnog slučaja.</Text>
          <Button loading={mutation.isPending} disabled={!actionType} onPress={submitActionCreate}>Kreiraj izvršnu radnju</Button>
        </Card>
      ) : null}

      {panel === 'action-complete' && selectedActionId !== null ? (
        <Card style={styles.panel}>
          <View style={styles.rowBetween}><Text style={styles.sectionTitle}>Završi izvršnu radnju</Text><Button variant="secondary" onPress={() => setPanel(null)}>Zatvori</Button></View>
          <TextField label="Referenca" value={completionReference} onChangeText={setCompletionReference} />
          <TextField label="Napomena o završetku" value={completionNote} onChangeText={setCompletionNote} multiline />
          <Button loading={mutation.isPending} onPress={() => void execute('Izvršna radnja je završena', () => apiAdminAfterSales.actionComplete(caseId, selectedActionId, { reference: compact(completionReference), completion_note: compact(completionNote) }))}>Potvrdi završetak</Button>
        </Card>
      ) : null}

      {panel === 'action-cancel' && selectedActionId !== null ? (
        <Card style={styles.panel}>
          <View style={styles.rowBetween}><Text style={styles.sectionTitle}>Otkaži izvršnu radnju</Text><Button variant="secondary" onPress={() => setPanel(null)}>Zatvori</Button></View>
          <TextField label="Razlog otkazivanja" value={cancellationReason} onChangeText={setCancellationReason} multiline />
          <Button loading={mutation.isPending} disabled={cancellationReason.trim().length < 5} onPress={() => void execute('Izvršna radnja je otkazana', () => apiAdminAfterSales.actionCancel(caseId, selectedActionId, cancellationReason.trim()))}>Otkaži radnju</Button>
        </Card>
      ) : null}

      <Card style={[styles.section, workspace !== 'case' && styles.hidden]}>
        <Text style={styles.sectionTitle}>Kupac i zahtev</Text>
        <Text style={styles.meta}>Kupac: {data.customer_snapshot.name ?? '—'}</Text>
        <Text style={styles.meta}>Telefon: {data.customer_snapshot.phone ?? '—'}</Text>
        <Text style={styles.meta}>Adresa: {data.customer_snapshot.address ?? '—'}</Text>
        <Text style={styles.meta}>Traženo rešenje: {data.requested_resolution_label ?? data.requested_resolution ?? '—'}</Text>
        <Text style={styles.meta}>Usvojeno rešenje: {data.resolution_type_label ?? data.resolution_type ?? '—'}</Text>
        {data.resolution_summary ? <Text style={styles.body}>{data.resolution_summary}</Text> : null}
      </Card>

      <Card style={[styles.section, workspace !== 'case' && styles.hidden]}>
        <Text style={styles.sectionTitle}>Pogođene stavke</Text>
        {data.items.map((item) => (
          <View key={item.id} style={styles.lineRow}>
            <View style={styles.grow}><Text style={styles.itemTitle}>{item.name}</Text><Text style={styles.meta}>{item.sku ? `SKU ${item.sku} · ` : ''}Količina {item.quantity}</Text>{item.issue_description ? <Text style={styles.body}>{item.issue_description}</Text> : null}</View>
          </View>
        ))}
      </Card>

      <Card style={[styles.section, workspace !== 'case' && styles.hidden]}>
        <Text style={styles.sectionTitle}>Prilozi slučaja</Text>
        {data.attachments.length === 0 ? <Text style={styles.meta}>Nema priloga.</Text> : data.attachments.map((attachment) => (
          <View key={attachment.id} style={styles.fileRow}>
            <View style={styles.grow}><Text style={styles.itemTitle}>{attachment.original_name}</Text><Text style={styles.meta}>{attachment.mime_type} · {formatAfterSalesAttachmentSize(attachment.size_bytes)}</Text></View>
            <Button variant="secondary" loading={openingAttachmentId === attachment.id} disabled={openingAttachmentId !== null} onPress={() => void openAttachment(attachment)}>Otvori / podeli</Button>
          </View>
        ))}
      </Card>

      <Card style={[styles.section, workspace !== 'communication' && styles.hidden]}>
        <Text style={styles.sectionTitle}>Komunikacija</Text>
        {data.messages.length === 0 ? <Text style={styles.meta}>Nema poruka.</Text> : data.messages.map((message) => (
          <View key={message.id} style={styles.messageCard}>
            <View style={styles.rowBetween}><Text style={styles.itemTitle}>{message.author?.name ?? 'Sistem'}</Text><Pill tone={message.visibility === 'internal' ? 'warning' : 'info'}>{message.visibility === 'internal' ? 'INTERNO' : 'JAVNO'}</Pill></View>
            <Text style={styles.body}>{message.body}</Text>
            <Text style={styles.meta}>{message.created_at ? formatDate(message.created_at, true) : '—'}</Text>
            {message.attachments.map((attachment) => (
              <View key={attachment.id} style={styles.fileRow}>
                <View style={styles.grow}><Text style={styles.itemTitle}>{attachment.original_name}</Text><Text style={styles.meta}>{formatAfterSalesAttachmentSize(attachment.size_bytes)}</Text></View>
                <Button variant="secondary" loading={openingAttachmentId === attachment.id} disabled={openingAttachmentId !== null} onPress={() => void openAttachment(attachment)}>Otvori</Button>
              </View>
            ))}
          </View>
        ))}
      </Card>

      <Card style={[styles.section, workspace !== 'actions' && styles.hidden]}>
        <Text style={styles.sectionTitle}>Izvršne radnje</Text>
        {data.actions.length === 0 ? <Text style={styles.meta}>Nema izvršnih radnji.</Text> : data.actions.map((action) => (
          <Card key={action.id} style={styles.actionCard}>
            <View style={styles.rowBetween}><View style={styles.grow}><Text style={styles.itemTitle}>{action.action_number} · {action.action_type_label}</Text><Text style={styles.meta}>{formatRsd(action.amount_rsd)}</Text></View><Pill tone={statusTone(action.status)}>{action.status_label}</Pill></View>
            <Text style={styles.meta}>Odgovorno lice: {action.assignee?.name ?? 'Nije dodeljeno'}</Text>
            {action.work_order ? <Text style={styles.meta}>Terenski nalog: {action.work_order.work_order_number} · {action.work_order.status_label}</Text> : null}
            {action.reference ? <Text style={styles.meta}>Referenca: {action.reference}</Text> : null}
            <View style={styles.actions}>
              {action.capabilities.start ? <Button variant="secondary" onPress={() => void execute('Izvršna radnja je pokrenuta', () => apiAdminAfterSales.actionStart(caseId, action.id))}>Pokreni</Button> : null}
              {action.capabilities.complete ? <Button variant="secondary" onPress={() => openComplete(action)}>Završi</Button> : null}
              {action.capabilities.cancel ? <Button variant="secondary" onPress={() => openCancel(action)}>Otkaži</Button> : null}
            </View>
          </Card>
        ))}
      </Card>

      <Card style={[styles.section, workspace !== 'history' && styles.hidden]}>
        <Text style={styles.sectionTitle}>Istorija statusa</Text>
        {data.history.length === 0 ? <Text style={styles.meta}>Nema istorije.</Text> : data.history.map((entry) => (
          <View key={entry.id} style={styles.lineRow}>
            <View style={styles.grow}><Text style={styles.itemTitle}>{entry.from_status ?? 'Početak'} → {entry.to_status ?? '—'}</Text>{entry.note ? <Text style={styles.body}>{entry.note}</Text> : null}<Text style={styles.meta}>{entry.actor?.name ?? 'Sistem'} · {entry.created_at ? formatDate(entry.created_at, true) : '—'}</Text></View>
          </View>
        ))}
      </Card>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: 120 },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    hero: { gap: spacing.sm },
    section: { gap: spacing.md },
    workspaceCard: { gap: spacing.md, borderColor: theme.primary },
    panel: { gap: spacing.md, borderColor: theme.primary },
    hidden: { display: 'none' },
    subject: { ...typography.h2, color: theme.ink },
    sectionTitle: { ...typography.h3, color: theme.ink },
    itemTitle: { ...typography.label, color: theme.ink },
    body: { ...typography.body, color: theme.ink },
    meta: { ...typography.small, color: theme.muted },
    warning: { ...typography.small, color: theme.primary, fontWeight: '800' },
    rowBetween: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    grow: { flex: 1, minWidth: 0 },
    actions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    lineRow: { borderTopWidth: 1, borderTopColor: theme.line, paddingTop: spacing.md },
    fileRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, borderTopWidth: 1, borderTopColor: theme.line, paddingTop: spacing.md },
    messageCard: { gap: spacing.sm, borderTopWidth: 1, borderTopColor: theme.line, paddingTop: spacing.md },
    actionCard: { gap: spacing.sm, backgroundColor: theme.surface },
    itemCard: { gap: spacing.sm, backgroundColor: theme.surface },
  });
}
