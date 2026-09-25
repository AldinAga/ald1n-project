import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { FilterBar, FilterChip } from '@/components/ui/filter-bar';
import { AdminOrderActions } from '@/features/admin/orders-admin-actions';
import { AdminOrderDocuments } from '@/features/admin/order-documents-admin';
import { AdminOrderArchiveActions } from '@/features/admin/order-archive-admin';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminOrders,
  type AdminOrderDetailRecord,
  type AdminOrderDetailValue,
} from '@/features/admin/orders-admin-api';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';
import { formatDecimal } from '@/lib/formatters';

function asRecord(value: AdminOrderDetailValue | undefined): AdminOrderDetailRecord | null {
  if (value === null || value === undefined || Array.isArray(value) || typeof value !== 'object') {
    return null;
  }
  return value;
}

function asRecords(value: AdminOrderDetailValue | undefined): AdminOrderDetailRecord[] {
  if (!Array.isArray(value)) return [];
  return value.flatMap((item) => {
    const record = asRecord(item);
    return record ? [record] : [];
  });
}

function text(record: AdminOrderDetailRecord | null, key: string, fallback = '-'): string {
  const value = record?.[key];
  if (typeof value === 'string' && value.trim()) return value;
  if (typeof value === 'number' || typeof value === 'boolean') return String(value);
  return fallback;
}

function numberValue(record: AdminOrderDetailRecord | null, key: string): number | null {
  const value = record?.[key];
  if (typeof value === 'number' && Number.isFinite(value)) return value;
  if (typeof value === 'string' && value.trim()) {
    const parsed = Number(value);
    return Number.isFinite(parsed) ? parsed : null;
  }
  return null;
}

function boolValue(record: AdminOrderDetailRecord | null, key: string): boolean {
  return record?.[key] === true;
}

function formatDateTime(value: string): string {
  if (!value || value === '-') return '-';
  const parsed = new Date(value);
  if (Number.isNaN(parsed.getTime())) return value;
  return parsed.toLocaleString('sr-RS');
}

function moneyRsd(value: number | null): string {
  if (value === null) return '-';
  return `${new Intl.NumberFormat('sr-RS', { maximumFractionDigits: 2 }).format(value)} RSD`;
}

function displayScalar(value: AdminOrderDetailValue | undefined): string {
  if (value === null || value === undefined) return '-';
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
  return '-';
}

// MOBILE_V1_0_ADMIN_ORDER_DETAIL_UX_REORGANIZATION_BATCH86
type OrderWorkspace = 'overview' | 'customer' | 'fulfillment' | 'finance' | 'documents' | 'activity';

const ORDER_WORKSPACE_OPTIONS: Array<{ value: OrderWorkspace; label: string; description: string }> = [
  { value: 'overview', label: 'Pregled', description: 'Status porudžbine, odgovorno lice i readiness upozorenja.' },
  { value: 'customer', label: 'Kupac i stavke', description: 'Kupac, adresa, napomena i sve stavke porudžbine.' },
  { value: 'fulfillment', label: 'Isporuka', description: 'Slanje pošiljke, kurir, tracking i potvrđena isporuka.' },
  { value: 'finance', label: 'Finansije', description: 'Uplate, potraživanje i provizija povezani sa porudžbinom.' },
  { value: 'documents', label: 'Dokumenti', description: 'Predračun, račun, otpremnica, revizije i postojeći secure PDF tok.' },
  { value: 'activity', label: 'Tok i akcije', description: 'Interne napomene, timeline, workflow akcije, arhiviranje i server capabilities.' },
];

export default function AdminOrdersDetailScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { can, bootstrap } = useAuth();
  const params = useLocalSearchParams<{ id?: string | string[] }> ();
  const rawId = Array.isArray(params.id) ? params.id[0] : params.id;
  const orderId = Number(rawId);
  const validId = Number.isInteger(orderId) && orderId > 0;
  const allowed = can('orders.manage');
  const [workspace, setWorkspace] = useState<OrderWorkspace> ('overview');

  const query = useQuery({
    queryKey: adminQueryKeys.adminOrder(orderId),
    queryFn: () => apiAdminOrders.detail(orderId),
    enabled: allowed && validId,
  });

  if (!allowed) {
    return <UnavailableState title="Administracija porudzbina nije dostupna" />;
  }

  if (!validId) {
    return <UnavailableState title="Porudzbina nije validna" />;
  }

  if (query.isLoading) {
    return <LoadingState label="Ucitavanje administratorskog detalja..." />;
  }

  if (query.isError || !query.data) {
    return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  }

  const response = query.data;
  const data = response.data;
  const order = asRecord(data.order);
  const customer = asRecord(data.customer);
  const shipping = asRecord(data.shipping);
  const supplier = asRecord(data.supplier);
  const shipment = asRecord(data.shipment);
  const delivery = asRecord(data.delivery);
  const receivable = asRecord(data.receivable);
  const commission = asRecord(data.commission);
  const permissions = asRecord(data.permissions);
  const actions = asRecord(data.actions);
  const items = asRecords(data.items);
  const payments = asRecords(data.payments);
  const documents = asRecords(data.documents);
  const timeline = asRecords(data.timeline);
  const internalNotes = asRecords(data.internal_notes);
  const warnings = Array.isArray(data.warnings)
    ? data.warnings.filter((item): item is string => typeof item === 'string')
    : [];

  const orderNumber = text(order, 'order_number', `Porudzbina #${orderId}`);
  const status = text(order, 'status_label', text(order, 'status'));
  const customerName = text(customer, 'name', text(order, 'shipping_full_name', 'Kupac'));
  const supplierName = text(supplier, 'name', text(order, 'supplier_name_snapshot', '-'));
  const workspaceMeta = ORDER_WORKSPACE_OPTIONS.find((option) => option.value === workspace);

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Porudzbine admin</Text>
      </Pressable>

      <PageHeader title={orderNumber} eyebrow="Admin · Operativni detalj" name={bootstrap?.user.name} />

      <View style={styles.headerRow}>
        <View style={styles.flexOne}>
          <Text style={styles.status}>{status}</Text>
          <Text style={styles.muted}>ID #{orderId}</Text>
        </View>
        <Button variant="secondary" onPress={() => void query.refetch()}>
          {query.isFetching ? 'Osvezavanje...' : 'Osvezi'}
        </Button>
      </View>

      <Card style={styles.workspaceCard}>
        <Text style={styles.sectionTitle}>Radni prostor porudžbine</Text>
        <Text style={styles.muted}>{workspaceMeta?.description ?? 'Izaberi deo porudžbine koji želiš da pregledaš ili obradiš.'}</Text>
        <FilterBar>
          {ORDER_WORKSPACE_OPTIONS.map((option) => (
            <FilterChip
              key={option.value}
              label={option.label}
              active={workspace === option.value}
              onPress={() => setWorkspace(option.value)}
            />
          ))}
        </FilterBar>
      </Card>

      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Porudzbina</Text>
        <DetailRow label="Kupac" value={customerName} styles={styles} />
        <DetailRow label="Odgovorno lice" value={supplierName} styles={styles} />
        <DetailRow label="Status" value={status} styles={styles} />
        <DetailRow label="Placanje" value={text(order, 'payment_state_label', text(order, 'payment_state', text(order, 'payment_status')))} styles={styles} />
        <DetailRow label="Nacin placanja" value={text(order, 'payment_method')} styles={styles} />
        <DetailRow label="Ukupno" value={moneyRsd(numberValue(order, 'subtotal_rsd'))} styles={styles} />
        <DetailRow label="Tracking" value={text(order, 'tracking_number')} styles={styles} />
        <DetailRow label="Kreirana" value={formatDateTime(text(order, 'created_at'))} styles={styles} />
        {boolValue(order, 'is_completed') ? <DetailRow label="Zavrsena" value="Da" styles={styles} /> : null}
      </Card>

      {workspace === 'customer' && (shipping || customer) ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Kupac i isporuka</Text>
          <DetailRow label="Ime" value={customerName} styles={styles} />
          <DetailRow label="Telefon" value={text(shipping, 'phone', text(order, 'shipping_phone'))} styles={styles} />
          <DetailRow label="Adresa" value={text(shipping, 'address', text(order, 'shipping_address'))} styles={styles} />
          <DetailRow label="Grad" value={text(shipping, 'city', text(order, 'shipping_city'))} styles={styles} />
          <DetailRow label="Napomena kupca" value={text(order, 'customer_note')} styles={styles} />
        </Card>
      ) : null}

      {workspace === 'customer' && items.length > 0 ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Stavke ({items.length})</Text>
          {items.map((item, index) => (
            <View key={`${text(item, 'id', String(index))}-${index}`} style={styles.listRow}>
              <View style={styles.flexOne}>
                <Text style={styles.itemTitle}>{text(item, 'name', text(item, 'product_name', `Stavka ${index + 1}`))}</Text>
                <Text style={styles.muted}>SKU: {text(item, 'sku', text(item, 'product_sku'))}</Text>
                <Text style={styles.muted}>Kolicina: {text(item, 'quantity', '1')}</Text>
              </View>
              <Text style={styles.itemValue}>{text(item, 'line_total', moneyRsd(numberValue(item, 'line_total_rsd')))}</Text>
            </View>
          ))}
        </Card>
      ) : null}

      {workspace === 'fulfillment' && shipment ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Slanje posiljke</Text>
          <DetailRow label="Status" value={text(shipment, 'status')} styles={styles} />
          <DetailRow label="Nacin" value={text(shipment, 'shipping_method_label', text(shipment, 'shipping_method'))} styles={styles} />
          <DetailRow label="Kurir" value={text(shipment, 'courier_name')} styles={styles} />
          <DetailRow label="Tracking" value={text(shipment, 'tracking_number', text(shipment, 'reference'))} styles={styles} />
          <DetailRow label="Poslato" value={formatDateTime(text(shipment, 'shipped_at'))} styles={styles} />
          <DetailRow label="Napomena" value={text(shipment, 'note')} styles={styles} />
        </Card>
      ) : null}

      {workspace === 'fulfillment' && delivery ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Isporuka</Text>
          <DetailRow label="Nacin" value={text(delivery, 'delivery_method_label', text(delivery, 'delivery_method'))} styles={styles} />
          <DetailRow label="Primalac" value={text(delivery, 'recipient_name')} styles={styles} />
          <DetailRow label="Telefon" value={text(delivery, 'recipient_phone')} styles={styles} />
          <DetailRow label="Referenca" value={text(delivery, 'reference')} styles={styles} />
          <DetailRow label="Isporuceno" value={formatDateTime(text(delivery, 'delivered_at'))} styles={styles} />
          <DetailRow label="Dokaz" value={boolValue(delivery, 'has_proof') ? text(delivery, 'proof_original_name', 'Postoji') : 'Nema'} styles={styles} />
        </Card>
      ) : null}

      {workspace === 'finance' && payments.length > 0 ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Uplate ({payments.length})</Text>
          {payments.map((payment, index) => (
            <View key={`${text(payment, 'id', String(index))}-${index}`} style={styles.listRow}>
              <View style={styles.flexOne}>
                <Text style={styles.itemTitle}>{text(payment, 'number', `Uplata ${index + 1}`)}</Text>
                <Text style={styles.muted}>{text(payment, 'entry_type')} · {text(payment, 'status')}</Text>
                <Text style={styles.muted}>{formatDateTime(text(payment, 'paid_at'))}</Text>
              </View>
              <Text style={styles.itemValue}>{text(payment, 'amount', moneyRsd(numberValue(payment, 'amount_rsd')))}</Text>
            </View>
          ))}
        </Card>
      ) : null}

      {workspace === 'documents' ? (
        <>
          {/* MOBILE_V1_0_ADMIN_ORDER_DOCUMENTS_INVOICE_PARITY_BATCH23 */}
          <AdminOrderDocuments
            orderId={orderId}
            documents={documents}
            canManage={response.capabilities.documents}
            hasDelivery={delivery !== null}
            onChanged={() => void query.refetch()}
          />
        </>
      ) : null}

      {workspace === 'finance' && receivable ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Potrazivanje</Text>
          <DetailRow label="Status" value={text(receivable, 'status')} styles={styles} />
          <DetailRow label="Sledeca akcija" value={formatDateTime(text(receivable, 'next_action_at'))} styles={styles} />
          <DetailRow label="Obecano placanje" value={formatDateTime(text(receivable, 'promised_payment_at'))} styles={styles} />
        </Card>
      ) : null}

      {workspace === 'finance' && commission ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Provizija</Text>
          <DetailRow label="Status" value={text(commission, 'status')} styles={styles} />
          <DetailRow label="Iznos" value={text(commission, 'total_eur_display', displayScalar(commission.total_eur))} styles={styles} />
          <DetailRow label="Isplaceno" value={formatDateTime(text(commission, 'paid_at'))} styles={styles} />
        </Card>
      ) : null}

      {workspace === 'activity' && internalNotes.length > 0 ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Interne napomene ({internalNotes.length})</Text>
          {internalNotes.map((note, index) => (
            <View key={`${text(note, 'id', String(index))}-${index}`} style={styles.note}>
              <Text style={styles.body}>{text(note, 'note')}</Text>
              <Text style={styles.muted}>{text(note, 'user_name', text(note, 'actor'))} · {formatDateTime(text(note, 'created_at'))}</Text>
            </View>
          ))}
        </Card>
      ) : null}

      {workspace === 'activity' && timeline.length > 0 ? (
        <Card style={styles.card}>
          <Text style={styles.sectionTitle}>Timeline ({timeline.length})</Text>
          {timeline.map((event, index) => (
            <View key={`${text(event, 'type', 'event')}-${index}`} style={styles.timelineItem}>
              <Text style={styles.itemTitle}>{text(event, 'title', `Dogadjaj ${index + 1}`)}</Text>
              <Text style={styles.body}>{text(event, 'description')}</Text>
              <Text style={styles.muted}>{text(event, 'actor')} · {formatDateTime(text(event, 'created_at'))}</Text>
            </View>
          ))}
        </Card>
      ) : null}

      {workspace === 'overview' && warnings.length > 0 ? (
        <Card style={styles.warningCard}>
          <Text style={styles.sectionTitle}>Readiness upozorenja</Text>
          {warnings.map((warning, index) => <Text key={`${warning}-${index}`} style={styles.muted}>• {warning}</Text>)}
        </Card>
      ) : null}

      {workspace === 'activity' ? (
        <>
          <AdminOrderActions
            orderId={orderId}
            data={data}
            capabilities={response.capabilities}
          />

      <AdminOrderArchiveActions
        orderId={orderId}
        orderNumber={orderNumber}
        canArchive={boolValue(order, 'is_completed') || text(order, 'status') === 'cancelled'}
        onArchived={() => router.replace('/admin/orders/archived')}
      />

      <Card style={styles.readOnlyCard}>
        <Text style={styles.sectionTitle}>Server-driven capabilities</Text>
        <DetailRow label="Read" value={String(response.capabilities.read)} styles={styles} />
        <DetailRow label="Workflow mutations" value={String(response.capabilities.workflow_mutations)} styles={styles} />
        <DetailRow label="Interne napomene dozvola" value={String(response.capabilities.internal_notes)} styles={styles} />
        <DetailRow label="Reassignment dozvola" value={String(response.capabilities.reassign)} styles={styles} />
        <DetailRow label="Uplate dozvola" value={String(response.capabilities.payments)} styles={styles} />
        <DetailRow label="Dokumenti dozvola" value={String(response.capabilities.documents)} styles={styles} />
        <DetailRow label="Potvrda isporuke dozvola" value={String(response.capabilities.confirm_delivery)} styles={styles} />
        <DetailRow label="Reopen dozvola" value={String(response.capabilities.reopen)} styles={styles} />
        <Text style={styles.muted}>
          Backend capabilities i actions upravljaju dostupnošću Mobile akcija; server ostaje konačni autoritet za dozvole i poslovne tranzicije.
        </Text>
        {permissions ? <Text style={styles.muted}>Permission snapshot dostupan: da.</Text> : null}
        {actions ? <Text style={styles.muted}>Action snapshot dostupan: da, koristi se za operativni UI.</Text> : null}
      </Card>
        </>
      ) : null}
    </Screen>
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
      <Text selectable style={styles.detailValue}>{value}</Text>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { paddingBottom: 140, gap: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    headerRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md },
    flexOne: { flex: 1, minWidth: 0 },
    status: { ...typography.label, color: theme.primary },
    muted: { ...typography.small, color: theme.muted },
    workspaceCard: { gap: spacing.md, borderColor: theme.primary },
    card: { gap: spacing.sm },
    warningCard: { gap: spacing.sm },
    readOnlyCard: { gap: spacing.sm },
    sectionTitle: { ...typography.h3, color: theme.ink },
    body: { ...typography.body, color: theme.ink },
    detailRow: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    detailLabel: { ...typography.small, color: theme.muted, flex: 1 },
    detailValue: { ...typography.body, color: theme.ink, flex: 2, textAlign: 'right' },
    listRow: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md, paddingVertical: spacing.sm, borderTopWidth: 1, borderTopColor: theme.line },
    itemTitle: { ...typography.label, color: theme.ink },
    itemValue: { ...typography.label, color: theme.primary },
    note: { gap: 4, paddingVertical: spacing.sm, borderTopWidth: 1, borderTopColor: theme.line },
    timelineItem: { gap: 4, paddingVertical: spacing.sm, borderTopWidth: 1, borderTopColor: theme.line },
  });
}
