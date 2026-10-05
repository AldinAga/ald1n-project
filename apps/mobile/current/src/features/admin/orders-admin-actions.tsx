import { useMemo, useState, type ReactNode } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { Linking, StyleSheet, Text, View } from 'react-native';

import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { DateTimeField } from '@/components/ui/date-time-field';
import { MoneyField } from '@/components/ui/money-field';
import { SelectSheet } from '@/components/ui/select-sheet';
import { TextField } from '@/components/ui/text-field';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminOrders,
  type AdminOrderCompletionInput,
  type AdminOrderDeliveryMethod,
  type AdminOrderDetailCapabilities,
  type AdminOrderDetailRecord,
  type AdminOrderDetailValue,
  type AdminOrderPaymentEntryType,
  type AdminOrderPaymentMethod,
  type AdminOrderProofFile,
  type AdminOrderShipmentMethod,
  type AdminOrderStatus,
  type AdminOrdersUser,
} from '@/features/admin/orders-admin-api';
import {
  formatAdminOrderProofSize,
  openAdminOrderShipmentProof,
  pickAdminOrderProof,
} from '@/features/admin/orders-admin-shipment-proof';
import { useAppTheme } from '@/theme/app-theme';
import { formatDecimal } from '@/lib/formatters';

type Panel =
  | 'accept'
  | 'status'
  | 'note'
  | 'reassign'
  | 'deadlines'
  | 'payment-entry'
  | 'payment-ledger'
  | 'sale-price-correction'
  | 'shipment'
  | 'complete'
  | 'reopen'
  | null;

type MutationCommand = {
  key: string;
  successTitle: string;
  run: () => Promise<unknown>;
};

type Props = {
  orderId: number;
  data: AdminOrderDetailRecord;
  capabilities: AdminOrderDetailCapabilities;
};

function asRecord(value: AdminOrderDetailValue | undefined): AdminOrderDetailRecord | null {
  if (value === null || value === undefined || Array.isArray(value) || typeof value !== 'object') return null;
  return value;
}

function asRecords(value: AdminOrderDetailValue | undefined): AdminOrderDetailRecord[] {
  if (!Array.isArray(value)) return [];
  return value.flatMap((item) => {
    const record = asRecord(item);
    return record ? [record] : [];
  });
}

function recordText(record: AdminOrderDetailRecord | null, key: string, fallback = ''): string {
  const value = record?.[key];
  if (typeof value === 'number') return formatDecimal(value);
  if (typeof value === 'string') {
    const trimmed = value.trim();
    if (/^-?\d+[.,]\d{3,}$/.test(trimmed)) {
      const parsed = Number(trimmed.replace(',', '.'));
      if (Number.isFinite(parsed)) return formatDecimal(parsed);
    }
    return value;
  }
  if (typeof value === 'boolean') return String(value);
  return fallback;
}

function recordBool(record: AdminOrderDetailRecord | null, key: string): boolean {
  return record?.[key] === true;
}

function recordId(record: AdminOrderDetailRecord | null, key = 'id'): number | null {
  const raw = record?.[key];
  const value = typeof raw === 'number' ? raw : typeof raw === 'string' ? Number(raw) : Number.NaN;
  return Number.isInteger(value) && value > 0 ? value : null;
}

function actionFlag(actions: AdminOrderDetailRecord | null, key: string, fallback: boolean): boolean {
  for (const candidateKey of [key, `can_${key}`]) {
    const value = actions?.[candidateKey];
    if (typeof value === 'boolean') return value;
    const record = asRecord(value);
    if (record) {
      for (const flag of ['enabled', 'allowed', 'available', 'can']) {
        if (typeof record[flag] === 'boolean') return record[flag] === true;
      }
    }
  }
  return fallback;
}

function localDateTimeNow(): string {
  const date = new Date();
  const pad = (value: number) => String(value).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function isStatus(value: string): value is AdminOrderStatus {
  return value === 'new' || value === 'processing' || value === 'confirmed' || value === 'cancelled';
}

function isShipmentMethod(value: string): value is AdminOrderShipmentMethod {
  return value === 'courier' || value === 'own_transport' || value === 'other';
}

function isDeliveryMethod(value: string): value is AdminOrderDeliveryMethod {
  return value === 'own_transport' || value === 'courier' || value === 'customer_pickup' || value === 'other';
}


function isPaymentEntryType(value: string): value is AdminOrderPaymentEntryType {
  return value === 'payment' || value === 'refund';
}

function isPaymentMethod(value: string): value is AdminOrderPaymentMethod {
  return value === 'bank_transfer' || value === 'cash' || value === 'cash_on_delivery' || value === 'card' || value === 'other';
}

function notifyInputError(feedback: ReturnType<typeof useAppFeedback>, message: string): void {
  feedback.notify({ tone: 'danger', title: 'Proveri unos', message });
}

export function AdminOrderActions({ orderId, data, capabilities }: Props) {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const client = useQueryClient();

  const order = asRecord(data.order);
  const customer = asRecord(data.customer);
  const shipping = asRecord(data.shipping);
  const shipment = asRecord(data.shipment);
  const actions = asRecord(data.actions);
  const items = asRecords(data.items);
  const firstItem = items[0] ?? null;
  const payments = asRecords(data.payments);
  // MOBILE_V0_8_SHIPMENT_COURIER_DIRECTORY_BATCH11
  const couriers = asRecords(data.couriers);

  const orderStatus = recordText(order, 'status');
  const orderVersionToken = recordText(order, 'order_version_token');
  const completed = recordBool(order, 'is_completed') || recordText(order, 'completed_at') !== '';
  const accepted = recordText(order, 'accepted_at') !== '';
  const workflowEnabled = capabilities.workflow_mutations && capabilities.orders_manage;
  const hasShipment = shipment !== null;
  // MOBILE_SHIPMENT_DELIVERY_DUAL_ACTION_BATCH513
  const isCashOnDelivery = recordText(order, 'payment_method') === 'cash_on_delivery';
  const deliveryActionLabel = isCashOnDelivery
    ? 'Potvrdi da je pošiljka isporučena i da je naplaćen otkup'
    : 'Potvrdi da je pošiljka isporučena';
  const defaultCourier = couriers.find((courier) => recordBool(courier, 'is_default')) ?? couriers[0] ?? null;
  const defaultCourierId = defaultCourier ? String(recordId(defaultCourier) ?? '') : '';

  const customerName = recordText(customer, 'name', recordText(order, 'shipping_full_name', 'Kupac'));
  const recipientPhone = recordText(shipping, 'phone', recordText(order, 'shipping_phone'));
  const currentTracking = recordText(order, 'tracking_number');

  const canAccept = actionFlag(actions, 'accept', workflowEnabled && !completed && !accepted && orderStatus !== 'cancelled');
  const canShipment = actionFlag(actions, 'shipment', workflowEnabled && !completed && !hasShipment && orderStatus !== 'cancelled');
  const canComplete = capabilities.confirm_delivery && actionFlag(actions, 'complete', workflowEnabled && !completed && orderStatus !== 'cancelled');
  const canReopen = capabilities.reopen && actionFlag(actions, 'reopen', workflowEnabled && completed);

  const [panel, setPanel] = useState<Panel> (null);
  const [statusValue, setStatusValue] = useState<AdminOrderStatus> ('processing');
  const [statusNote, setStatusNote] = useState('');
  const [internalNote, setInternalNote] = useState('');
  const [supplierId, setSupplierId] = useState('');
  const [reassignReason, setReassignReason] = useState('');
  const [processingAt, setProcessingAt] = useState('');
  const [shippingAt, setShippingAt] = useState('');
  const [entryType, setEntryType] = useState<AdminOrderPaymentEntryType> ('payment');
  const [paymentAmount, setPaymentAmount] = useState('');
  const [paymentMethod, setPaymentMethod] = useState<AdminOrderPaymentMethod> ('bank_transfer');
  const [paidAt, setPaidAt] = useState(localDateTimeNow);
  const [paymentReference, setPaymentReference] = useState('');
  const [paymentNote, setPaymentNote] = useState('');
  const [selectedPaymentId, setSelectedPaymentId] = useState('');
  const [paymentLedgerAction, setPaymentLedgerAction] = useState<'verify' | 'reject' | 'void'> ('verify');
  const [paymentRejectReason, setPaymentRejectReason] = useState('');
  const [salePrice, setSalePrice] = useState(recordText(firstItem, 'unit_price_original', recordText(firstItem, 'unit_price_rsd')));
  const [salePriceCurrency, setSalePriceCurrency] = useState<'RSD' | 'EUR'> (recordText(firstItem, 'original_currency') === 'EUR' ? 'EUR' : 'RSD');
  const [salePriceReason, setSalePriceReason] = useState('');
  const [shipmentMethod, setShipmentMethod] = useState<AdminOrderShipmentMethod> ('courier');
  const [courierServiceId, setCourierServiceId] = useState(defaultCourierId);
  const [shippedAt, setShippedAt] = useState(localDateTimeNow);
  const [shipmentRecipient, setShipmentRecipient] = useState(customerName);
  const [shipmentPhone, setShipmentPhone] = useState(recipientPhone);
  const [trackingNumber, setTrackingNumber] = useState(currentTracking);
  const [shipmentNote, setShipmentNote] = useState('');
  const [shipmentProof, setShipmentProof] = useState<AdminOrderProofFile | null> (null);
  const [deliveryMethod, setDeliveryMethod] = useState<AdminOrderDeliveryMethod> ('courier');
  const [deliveredAt, setDeliveredAt] = useState(localDateTimeNow);
  const [deliveryRecipient, setDeliveryRecipient] = useState(customerName);
  const [deliveryPhone, setDeliveryPhone] = useState(recipientPhone);
  const [deliveryNote, setDeliveryNote] = useState('');
  const [completionNote, setCompletionNote] = useState('');
  const [deliveryProof, setDeliveryProof] = useState<AdminOrderProofFile | null> (null);
  const [reopenReason, setReopenReason] = useState('');
  const [openingProof, setOpeningProof] = useState(false);
  const courierOptions = couriers.flatMap((courier) => { const id = recordId(courier); return id ? [{ value: String(id), label: recordText(courier, 'name', 'Kurirska služba') }] : []; });
  const selectedCourier = couriers.find((courier) => recordId(courier) === Number(courierServiceId)) ?? null;
  const selectedCourierTrackingUrl = recordText(selectedCourier, 'tracking_url');
  const shipmentTrackingUrl = recordText(shipment, 'tracking_url');

  const supplierQuery = useQuery({
    queryKey: ['admin', 'orders', 'supplier-options'],
    queryFn: async (): Promise<AdminOrdersUser[]> => {
      const response = await apiAdminOrders.list({ page: 1, per_page: 20 });
      return response.filter_options.suppliers;
    },
    enabled: capabilities.reassign,
    staleTime: 60_000,
  });

  const mutation = useMutation({
    mutationFn: (command: MutationCommand) => command.run(),
    onSuccess: async (_result, command) => {
      setPanel(null);
      setShipmentProof(null);
      setDeliveryProof(null);
      await client.invalidateQueries({ queryKey: ['admin', 'orders'] });
      feedback.notify({ tone: 'success', title: command.successTitle, message: 'Podaci porudžbine su osveženi.' });
    },
    onError: (error) => {
      feedback.notify({
        tone: 'danger',
        title: 'Akcija nije uspela',
        message: error instanceof Error ? error.message : 'Server je odbio izmenu. Proveri trenutno stanje porudžbine.',
      });
    },
  });

  const busy = (key: string) => mutation.isPending && mutation.variables?.key === key;
  const execute = (key: string, successTitle: string, run: () => Promise<unknown>) => mutation.mutate({ key, successTitle, run });

  async function chooseProof(target: 'shipment' | 'delivery'): Promise<void> {
    try {
      const file = await pickAdminOrderProof();
      if (!file) return;
      if (target === 'shipment') setShipmentProof(file);
      else setDeliveryProof(file);
    } catch (error) {
      notifyInputError(feedback, error instanceof Error ? error.message : 'Dokaz nije moguće izabrati.');
    }
  }

  async function openShipmentProof(): Promise<void> {
    setOpeningProof(true);
    try {
      await openAdminOrderShipmentProof(orderId, recordText(shipment, 'proof_original_name', 'dokaz-slanja'));
    } catch (error) {
      feedback.notify({ tone: 'danger', title: 'Dokaz nije otvoren', message: error instanceof Error ? error.message : 'Pokušaj ponovo.' });
    } finally {
      setOpeningProof(false);
    }
  }

  function submitReassign(): void {
    const nextSupplierId = Number(supplierId);
    if (!Number.isInteger(nextSupplierId) || nextSupplierId <= 0) return notifyInputError(feedback, 'Izaberi odgovorno lice.');
    if (!reassignReason.trim()) return notifyInputError(feedback, 'Unesi razlog ponovne dodele.');
    execute('reassign', 'Porudžbina je ponovo dodeljena', () => apiAdminOrders.reassign(orderId, { supplier_user_id: nextSupplierId, reason: reassignReason.trim() }));
  }

  function submitPaymentEntry(): void {
    const amount = Number(paymentAmount.replace(',', '.'));
    if (!Number.isFinite(amount) || amount <= 0) return notifyInputError(feedback, 'Unesi ispravan iznos u RSD.');
    if (!paidAt.trim()) return notifyInputError(feedback, 'Unesi datum i vreme uplate/refundacije.');
    execute('payment-entry', entryType === 'refund' ? 'Refundacija je evidentirana' : 'Uplata je evidentirana', () => apiAdminOrders.paymentStore(orderId, {
      entry_type: entryType,
      amount_rsd: amount,
      payment_method: paymentMethod,
      paid_at: paidAt.trim(),
      reference: paymentReference.trim() || null,
      note: paymentNote.trim() || null,
    }));
  }

  function submitSalePriceCorrection(): void {
    const amount = Number(salePrice.replace(',', '.'));
    if (!Number.isFinite(amount) || amount <= 0) return notifyInputError(feedback, 'Unesi ispravnu novu prodajnu cenu.');
    if (salePriceReason.trim().length < 3) return notifyInputError(feedback, 'Unesi razlog korekcije.');
    execute('sale-price-correction', 'Prodajna cena je korigovana', () => apiAdminOrders.salePriceCorrection(orderId, {
      new_unit_price_amount: amount,
      new_unit_price_currency: salePriceCurrency,
      reason: salePriceReason.trim(),
    }));
  }

  function submitPaymentLedgerAction(): void {
    const paymentId = Number(selectedPaymentId);
    if (!Number.isInteger(paymentId) || paymentId <= 0) return notifyInputError(feedback, 'Izaberi uplatu.');
    if (paymentLedgerAction === 'reject') {
      if (!paymentRejectReason.trim()) return notifyInputError(feedback, 'Za odbijanje je obavezan razlog.');
      execute('payment-ledger', 'Uplata je odbijena', () => apiAdminOrders.paymentReject(orderId, paymentId, paymentRejectReason.trim()));
      return;
    }
    if (paymentLedgerAction === 'void') {
      execute('payment-ledger', 'Uplata je stornirana', () => apiAdminOrders.paymentVoid(orderId, paymentId));
      return;
    }
    execute('payment-ledger', 'Uplata je verifikovana', () => apiAdminOrders.paymentVerify(orderId, paymentId));
  }

  async function openTrackingUrl(url: string): Promise<void> {
    const safeUrl = url.trim();
    if (!safeUrl.toLowerCase().startsWith('https://')) return notifyInputError(feedback, 'Tracking URL nije bezbedan HTTPS link.');
    try { await Linking.openURL(safeUrl); } catch { feedback.notify({ tone: 'danger', title: 'Tracking stranica nije otvorena', message: 'Proveri URL kurirske službe.' }); }
  }

  function submitShipment(): void {
    if (!shipmentRecipient.trim()) return notifyInputError(feedback, 'Ime primaoca je obavezno.');
    const selectedCourierId = Number(courierServiceId);
    if (shipmentMethod === 'courier' && (!Number.isInteger(selectedCourierId) || selectedCourierId <= 0)) return notifyInputError(feedback, 'Izaberi kurirsku službu.');
    if (shipmentMethod === 'courier' && !trackingNumber.trim()) return notifyInputError(feedback, 'Za kurirsku službu unesi tracking broj.');
    execute('shipment', 'Slanje pošiljke je evidentirano', () => apiAdminOrders.shipment(orderId, {
      order_version_token: orderVersionToken,
      shipment_method: shipmentMethod,
      courier_service_id: shipmentMethod === 'courier' ? selectedCourierId : null,
      shipped_at: shippedAt.trim(),
      recipient_name: shipmentRecipient.trim(),
      recipient_phone: shipmentPhone.trim() || null,
      tracking_number: shipmentMethod === 'courier' ? trackingNumber.trim() : null,
      note: shipmentNote.trim() || null,
      shipment_proof: shipmentProof,
    }));
  }

  function submitCompletion(): void {
    if (!deliveryRecipient.trim()) return notifyInputError(feedback, 'Ime primaoca je obavezno.');
    const input: AdminOrderCompletionInput = {
      delivery_method: deliveryMethod,
      delivered_at: deliveredAt.trim(),
      recipient_name: deliveryRecipient.trim(),
      recipient_phone: deliveryPhone.trim() || null,
      delivery_note: deliveryNote.trim() || null,
      completion_note: completionNote.trim() || null,
      delivery_proof: deliveryProof,
    };
    execute('complete', 'Porudžbina je kompletirana', () => apiAdminOrders.complete(orderId, input));
  }

  if (!workflowEnabled) {
    return (
      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Operativne akcije</Text>
        <Text style={styles.muted}>Backend za ovu porudžbinu trenutno ne dozvoljava workflow mutacije.</Text>
      </Card>
    );
  }

  const supplierOptions = (supplierQuery.data ?? []).map((supplier) => ({
    value: String(supplier.id),
    label: supplier.name,
    detail: supplier.username,
  }));
  const paymentOptions = payments.flatMap((payment, index) => {
    const id = recordId(payment);
    if (!id) return [];
    return [{
      value: String(id),
      label: recordText(payment, 'number', `Uplata #${id}`),
      detail: `${recordText(payment, 'entry_type', 'payment')} · ${recordText(payment, 'status', 'status nije naveden')} · stavka ${index + 1}`,
    }];
  });

  return (
    <View style={styles.section}>
      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Operativne akcije</Text>
        <Text style={styles.muted}>Sve izmene prolaze kroz postojeće Laravel domain servise i server ponovo proverava dozvole i trenutno stanje porudžbine.</Text>
        {(canShipment || canComplete || (hasShipment && !completed)) ? (
          <View style={styles.deliveryActionStack}>
            {canShipment ? (
              <Button style={styles.deliveryActionButton} onPress={() => setPanel('shipment')}>Potvrdi da je pošiljka poslata</Button>
            ) : hasShipment && !completed ? (
              <Button style={styles.deliveryActionButton} variant="secondary" disabled>Pošiljka je poslata</Button>
            ) : null}
            {canComplete ? (
              <Button style={styles.deliveryActionButton} onPress={() => setPanel('complete')}>{deliveryActionLabel}</Button>
            ) : null}
          </View>
        ) : null}
        <View style={styles.actionGrid}>
          {canAccept ? <Button variant="secondary" onPress={() => setPanel('accept')}>Preuzmi porudžbinu</Button> : null}
          {canReopen ? <Button variant="secondary" onPress={() => setPanel('reopen')}>Ponovo otvori</Button> : null}
          {!completed ? <Button variant="secondary" onPress={() => setPanel('status')}>Promeni status</Button> : null}
          {capabilities.internal_notes ? <Button variant="secondary" onPress={() => setPanel('note')}>Interna napomena</Button> : null}
          {capabilities.reassign ? <Button variant="secondary" onPress={() => setPanel('reassign')}>Promeni odgovorno lice</Button> : null}
          {!completed ? <Button variant="secondary" onPress={() => setPanel('deadlines')}>Rokovi</Button> : null}
          {capabilities.payments ? <Button variant="secondary" onPress={() => setPanel('payment-entry')}>Evidentiraj uplatu/refundaciju</Button> : null}
          {capabilities.sale_price_correction ? <Button variant="secondary" onPress={() => setPanel('sale-price-correction')}>Koriguj prodajnu cenu</Button> : null}
          {capabilities.payments && paymentOptions.length > 0 ? <Button variant="secondary" onPress={() => setPanel('payment-ledger')}>Obradi postojeću uplatu</Button> : null}
          {recordBool(shipment, 'has_proof') ? <Button variant="secondary" loading={openingProof} onPress={() => void openShipmentProof()}>Otvori dokaz slanja</Button> : null}
          {shipmentTrackingUrl ? <Button variant="secondary" onPress={() => void openTrackingUrl(shipmentTrackingUrl)}>Otvori tracking stranicu</Button> : null}
        </View>
      </Card>

      {panel === 'accept' ? (
        <ActionPanel title="Preuzimanje porudžbine" onClose={() => setPanel(null)} styles={styles}>
          <Text style={styles.muted}>Potvrdi da preuzimaš porudžbinu u obradu.</Text>
          <Button loading={busy('accept')} onPress={() => execute('accept', 'Porudžbina je preuzeta', () => apiAdminOrders.accept(orderId))}>Potvrdi preuzimanje</Button>
        </ActionPanel>
      ) : null}

      {panel === 'status' ? (
        <ActionPanel title="Promena statusa" onClose={() => setPanel(null)} styles={styles}>
          <SelectSheet label="Novi status" value={statusValue} options={[
            { value: 'new', label: 'Nova' },
            { value: 'processing', label: 'U obradi' },
            { value: 'confirmed', label: 'Potvrđena' },
            { value: 'cancelled', label: 'Otkazana' },
          ]} onChange={(value) => { if (isStatus(value)) setStatusValue(value); }} />
          <TextField label="Napomena" value={statusNote} onChangeText={setStatusNote} multiline />
          <Button loading={busy('status')} onPress={() => execute('status', 'Status porudžbine je promenjen', () => apiAdminOrders.status(orderId, { status: statusValue, note: statusNote.trim() || null, order_version_token: orderVersionToken }))}>Sačuvaj status</Button>
        </ActionPanel>
      ) : null}

      {panel === 'note' ? (
        <ActionPanel title="Interna napomena" onClose={() => setPanel(null)} styles={styles}>
          <TextField label="Napomena" value={internalNote} onChangeText={setInternalNote} multiline />
          <Button loading={busy('note')} disabled={!internalNote.trim()} onPress={() => execute('note', 'Interna napomena je sačuvana', () => apiAdminOrders.internalNote(orderId, internalNote.trim()))}>Sačuvaj napomenu</Button>
        </ActionPanel>
      ) : null}

      {panel === 'reassign' ? (
        <ActionPanel title="Promena odgovornog lica" onClose={() => setPanel(null)} styles={styles}>
          {supplierQuery.isLoading ? <Text style={styles.muted}>Učitavanje odgovornih lica…</Text> : null}
          {supplierOptions.length > 0 ? <SelectSheet label="Odgovorno lice" value={supplierId} options={supplierOptions} onChange={setSupplierId} /> : <Text style={styles.warning}>Lista odgovornih lica trenutno nije dostupna. Izmena nije poslata.</Text>}
          <TextField label="Razlog" value={reassignReason} onChangeText={setReassignReason} multiline />
          <Button loading={busy('reassign')} disabled={supplierOptions.length === 0} onPress={submitReassign}>Sačuvaj dodelu</Button>
        </ActionPanel>
      ) : null}

      {panel === 'deadlines' ? (
        <ActionPanel title="Očekivani rokovi" onClose={() => setPanel(null)} styles={styles}>
          <DateTimeField label="Očekivana obrada" value={processingAt} onChangeText={setProcessingAt} />
          <DateTimeField label="Očekivano slanje" value={shippingAt} onChangeText={setShippingAt} />
          <Button loading={busy('deadlines')} onPress={() => execute('deadlines', 'Rokovi porudžbine su ažurirani', () => apiAdminOrders.deadlines(orderId, { expected_processing_at: processingAt.trim() || null, expected_shipping_at: shippingAt.trim() || null }))}>Sačuvaj rokove</Button>
        </ActionPanel>
      ) : null}


      {panel === 'payment-entry' ? (
        <ActionPanel title="Nova stavka finansijskog ledgera" onClose={() => setPanel(null)} styles={styles}>
          <SelectSheet label="Vrsta" value={entryType} options={[{ value: 'payment', label: 'Uplata' }, { value: 'refund', label: 'Refundacija' }]} onChange={(value) => { if (isPaymentEntryType(value)) setEntryType(value); }} />
          <MoneyField label="Iznos" value={paymentAmount} onChangeText={setPaymentAmount} currency="RSD" required />
          <SelectSheet label="Način plaćanja" value={paymentMethod} options={[
            { value: 'bank_transfer', label: 'Bankovni prenos' },
            { value: 'cash', label: 'Gotovina' },
            { value: 'cash_on_delivery', label: 'Pouzećem' },
            { value: 'card', label: 'Kartica' },
            { value: 'other', label: 'Drugo' },
          ]} onChange={(value) => { if (isPaymentMethod(value)) setPaymentMethod(value); }} />
          <DateTimeField label="Datum i vreme" value={paidAt} onChangeText={setPaidAt} />
          <TextField label="Referenca" value={paymentReference} onChangeText={setPaymentReference} />
          <TextField label="Napomena" value={paymentNote} onChangeText={setPaymentNote} multiline />
          <Button loading={busy('payment-entry')} onPress={submitPaymentEntry}>Evidentiraj</Button>
        </ActionPanel>
      ) : null}

      {panel === 'sale-price-correction' ? (
        <ActionPanel title="Korekcija Direct Sale cene" onClose={() => setPanel(null)} styles={styles}>
          <Text style={styles.warning}>Ova akcija menja stavku, subtotal i originalnu verifikovanu uplatu. Server dozvoljava samo bezbedne Direct Sale slucajeve i upisuje audit trag.</Text>
          <SelectSheet label="Valuta stvarne prodajne cene" value={salePriceCurrency} options={[{ value: 'RSD', label: 'RSD' }, { value: 'EUR', label: 'EUR' }]} onChange={(value) => { if (value === 'RSD' || value === 'EUR') setSalePriceCurrency(value); }} />
          <MoneyField label="Nova jedinicna prodajna cena" value={salePrice} onChangeText={setSalePrice} currency={salePriceCurrency} required />
          <TextField label="Razlog korekcije" value={salePriceReason} onChangeText={setSalePriceReason} multiline />
          <Button loading={busy('sale-price-correction')} onPress={submitSalePriceCorrection}>Potvrdi korekciju</Button>
        </ActionPanel>
      ) : null}

      {panel === 'payment-ledger' ? (
        <ActionPanel title="Obrada postojeće uplate" onClose={() => setPanel(null)} styles={styles}>
          <SelectSheet label="Uplata" value={selectedPaymentId} options={paymentOptions} onChange={setSelectedPaymentId} />
          <SelectSheet label="Akcija" value={paymentLedgerAction} options={[
            { value: 'verify', label: 'Verifikuj' },
            { value: 'reject', label: 'Odbij' },
            { value: 'void', label: 'Storniraj' },
          ]} onChange={(value) => { if (value === 'verify' || value === 'reject' || value === 'void') setPaymentLedgerAction(value); }} />
          {paymentLedgerAction === 'reject' ? <TextField label="Razlog odbijanja" value={paymentRejectReason} onChangeText={setPaymentRejectReason} multiline /> : null}
          <Text style={styles.muted}>Server proverava da li je izabrani prelaz dozvoljen za trenutno stanje uplate.</Text>
          <Button loading={busy('payment-ledger')} onPress={submitPaymentLedgerAction}>Izvrši akciju</Button>
        </ActionPanel>
      ) : null}

      {panel === 'shipment' ? (
        <ActionPanel title="Evidencija slanja pošiljke" onClose={() => setPanel(null)} styles={styles}>
          <SelectSheet label="Način isporuke" value={shipmentMethod} options={[
            { value: 'courier', label: 'Kurirska služba' },
            { value: 'own_transport', label: 'Sopstveni transport' },
            { value: 'other', label: 'Drugo' },
          ]} onChange={(value) => { if (isShipmentMethod(value)) setShipmentMethod(value); }} />
          {shipmentMethod === 'courier' ? (
            <>
              <SelectSheet label="Kurirska služba" value={courierServiceId} placeholder="Izaberi kurirsku službu" options={courierOptions} onChange={setCourierServiceId} />
              {selectedCourierTrackingUrl ? <Button variant="ghost" onPress={() => void openTrackingUrl(selectedCourierTrackingUrl)}>Otvori tracking stranicu kurira</Button> : null}
            </>
          ) : null}
          <DateTimeField label="Datum i vreme slanja" value={shippedAt} onChangeText={setShippedAt} />
          <TextField label="Primalac" value={shipmentRecipient} onChangeText={setShipmentRecipient} />
          <TextField label="Telefon primaoca" value={shipmentPhone} onChangeText={setShipmentPhone} keyboardType="phone-pad" />
          {shipmentMethod === 'courier' ? <TextField label="Broj za praćenje pošiljke" value={trackingNumber} onChangeText={setTrackingNumber} autoCapitalize="characters" /> : null}
          <TextField label="Napomena o slanju" value={shipmentNote} onChangeText={setShipmentNote} multiline />
          <ProofPicker file={shipmentProof} onPick={() => void chooseProof('shipment')} onClear={() => setShipmentProof(null)} styles={styles} />
          <Button loading={busy('shipment')} onPress={submitShipment}>Potvrdi da je pošiljka poslata</Button>
        </ActionPanel>
      ) : null}

      {panel === 'complete' ? (
        <ActionPanel title="Potvrda stvarne isporuke" onClose={() => setPanel(null)} styles={styles}>
          <Text style={styles.warning}>{isCashOnDelivery
            ? 'Potvrdom isporuke backend evidentira preostali iznos kao naplaćen pouzećem i završava porudžbinu.'
            : 'Ova akcija potvrđuje stvarnu isporuku. Saldo mora biti potpuno izmiren pre završetka porudžbine.'}</Text>
          <SelectSheet label="Način isporuke" value={deliveryMethod} options={[
            { value: 'own_transport', label: 'Sopstveni transport' },
            { value: 'courier', label: 'Kurirska služba' },
            { value: 'customer_pickup', label: 'Lično preuzimanje' },
            { value: 'other', label: 'Drugo' },
          ]} onChange={(value) => { if (isDeliveryMethod(value)) setDeliveryMethod(value); }} />
          <DateTimeField label="Datum i vreme isporuke" value={deliveredAt} onChangeText={setDeliveredAt} />
          <TextField label="Primalac" value={deliveryRecipient} onChangeText={setDeliveryRecipient} />
          <TextField label="Telefon primaoca" value={deliveryPhone} onChangeText={setDeliveryPhone} keyboardType="phone-pad" />
          <TextField label="Napomena o isporuci" value={deliveryNote} onChangeText={setDeliveryNote} multiline />
          <TextField label="Napomena o kompletiranju" value={completionNote} onChangeText={setCompletionNote} multiline />
          <ProofPicker file={deliveryProof} onPick={() => void chooseProof('delivery')} onClear={() => setDeliveryProof(null)} styles={styles} />
          <Button loading={busy('complete')} onPress={submitCompletion}>{deliveryActionLabel}</Button>
        </ActionPanel>
      ) : null}

      {panel === 'reopen' ? (
        <ActionPanel title="Ponovno otvaranje" onClose={() => setPanel(null)} styles={styles}>
          <TextField label="Razlog" value={reopenReason} onChangeText={setReopenReason} multiline />
          <Button loading={busy('reopen')} disabled={!reopenReason.trim()} onPress={() => execute('reopen', 'Porudžbina je ponovo otvorena', () => apiAdminOrders.reopen(orderId, reopenReason.trim()))}>Ponovo otvori porudžbinu</Button>
        </ActionPanel>
      ) : null}
    </View>
  );
}

function ActionPanel({ title, onClose, styles, children }: { title: string; onClose: () => void; styles: ReturnType<typeof createStyles>; children: ReactNode }) {
  return (
    <Card style={styles.panel}>
      <View style={styles.panelHead}>
        <Text style={styles.sectionTitle}>{title}</Text>
        <Button variant="secondary" onPress={onClose}>Zatvori</Button>
      </View>
      {children}
    </Card>
  );
}

function ProofPicker({ file, onPick, onClear, styles }: { file: AdminOrderProofFile | null; onPick: () => void; onClear: () => void; styles: ReturnType<typeof createStyles> }) {
  return (
    <View style={styles.proofBox}>
      <Text style={styles.label}>Dokaz</Text>
      <Text style={styles.muted}>{file ? `${file.name} · ${formatAdminOrderProofSize(file.size)}` : 'Opcioni PDF/JPG/PNG/WebP do 10 MB.'}</Text>
      <View style={styles.inlineActions}>
        <Button variant="secondary" onPress={onPick}>{file ? 'Promeni dokaz' : 'Izaberi dokaz'}</Button>
        {file ? <Button variant="secondary" onPress={onClear}>Ukloni</Button> : null}
      </View>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    section: { gap: spacing.md },
    card: { gap: spacing.md },
    panel: { gap: spacing.md },
    panelHead: { flexDirection: 'row', flexWrap: 'wrap', alignItems: 'center', justifyContent: 'space-between', gap: spacing.sm },
    sectionTitle: { ...typography.h3, color: theme.ink, flexShrink: 1 },
    label: { ...typography.label, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    warning: { ...typography.small, color: theme.warning, fontWeight: '700' },
    deliveryActionStack: { gap: spacing.sm, alignSelf: 'stretch' },
    deliveryActionButton: { width: '100%', alignSelf: 'stretch' },
    actionGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, alignItems: 'flex-start' },
    inlineActions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    proofBox: { gap: spacing.sm, paddingVertical: spacing.sm, borderTopWidth: 1, borderTopColor: theme.line },
  });
}
