import { useEffect, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { Pill } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useThemedStyles } from '@/theme/app-theme';
import { useAuth } from '@/features/auth/auth-provider';
import { useMoneyPresentation } from '@/features/preferences/money-presentation';
import {
  formatOrderFileSize,
  openOrderConfirmationPdf,
  openOrderDeliveryProof,
  openOrderDocumentPdf,
  openOrderPaymentProof,
  pickOrderPaymentProof,
} from '@/features/orders/order-post-create-files';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import { formatDate, humanize } from '@/lib/formatters';
import type {
  OrderDocumentSummary,
  OrderPaymentLedgerEntry,
  OrderPaymentProofUploadFile,
  SubmitOrderPaymentProofInput,
} from '@/types/api';

function apiMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) {
    return error.firstFieldError() ?? error.message;
  }

  if (error instanceof Error && error.message) {
    return error.message;
  }

  return fallback;
}

function localPaymentDateValue(): string {
  const date = new Date();
  const pad = (value: number) => String(value).padStart(2, '0');

  return [
    date.getFullYear(),
    '-',
    pad(date.getMonth() + 1),
    '-',
    pad(date.getDate()),
    'T',
    pad(date.getHours()),
    ':',
    pad(date.getMinutes()),
  ].join('');
}

export default function OrderDetailScreen() {
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const { formatPrimaryMoney } = useMoneyPresentation();
  const { id } = useLocalSearchParams<{ id: string }>();
  const orderId = Number(id);
  const validOrderId = Number.isInteger(orderId) && orderId > 0;
  const client = useQueryClient();
  const { can, hasFeature } = useAuth();
  const allowed = hasFeature('orders');
  const [proofFile, setProofFile] = useState<OrderPaymentProofUploadFile | null>(null);
  const [proofAmount, setProofAmount] = useState('');
  const [proofPaidAt, setProofPaidAt] = useState(localPaymentDateValue);
  const [proofReference, setProofReference] = useState('');
  const [proofNote, setProofNote] = useState('');
  const [proofError, setProofError] = useState<string | null>(null);
  const [pickingProof, setPickingProof] = useState(false);
  const [openingFile, setOpeningFile] = useState<string | null>(null);

  const query = useQuery({
    queryKey: ['order', orderId],
    queryFn: () => api.orders.detail(orderId),
    enabled: allowed && validOrderId,
  });

  const postCreateQuery = useQuery({
    queryKey: ['order-post-create', orderId],
    queryFn: () => api.orders.postCreate(orderId),
    enabled: allowed && validOrderId,
  });

  const cancel = useMutation({
    mutationFn: () => api.orders.cancel(orderId),
    onSuccess: async () => {
      await client.invalidateQueries({ queryKey: ['orders'] });
      await client.invalidateQueries({ queryKey: ['order', orderId] });
      await client.invalidateQueries({ queryKey: ['order-post-create', orderId] });
    },
  });

  const proofMutation = useMutation({
    mutationFn: (input: SubmitOrderPaymentProofInput) => api.orders.submitPaymentProof(orderId, input),
    onSuccess: async (response) => {
      setProofFile(null);
      setProofAmount('');
      setProofReference('');
      setProofNote('');
      setProofPaidAt(localPaymentDateValue());
      setProofError(null);

      await client.invalidateQueries({ queryKey: ['orders'] });
      await client.invalidateQueries({ queryKey: ['order', orderId] });
      await client.invalidateQueries({ queryKey: ['order-post-create', orderId] });

      feedback.notify({
        tone: 'success',
        title: 'Potvrda uplate je poslata',
        message: response.message || 'Potvrda je poslata odgovornom licu na proveru.',
      });
    },
    onError: (error) => {
      setProofError(apiMessage(error, 'Potvrdu uplate trenutno nije moguće poslati.'));
    },
  });

  const postCreate = postCreateQuery.data;

  useEffect(() => {
    const remaining = postCreate?.order.remaining_rsd;
    if (!remaining || remaining <= 0) return;
    setProofAmount((current) => current.trim() ? current : remaining.toFixed(2));
  }, [postCreate?.order.remaining_rsd]);

  if (!allowed) return <UnavailableState title="Porudžbina nije dostupna" />;
  if (!validOrderId) return <ErrorState error={new Error('Neispravan identifikator porudžbine.')} />;
  if (query.isLoading) return <LoadingState label="Učitavanje porudžbine…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const order = query.data;
  const canCancel = hasFeature('order_cancel') && !['cancelled', 'completed', 'delivered'].includes(order.status);
  const canCreateAfterSales = can('after_sales.create');

  const confirmCancel = () => {
    void (async () => {
      const confirmed = await feedback.confirm({
        tone: 'danger',
        title: 'Otkazivanje porudžbine',
        message: 'Ova akcija menja poslovni status porudžbine. Nastaviti?',
        confirmLabel: 'Otkaži porudžbinu',
        cancelLabel: 'Ne',
      });

      if (confirmed) {
        cancel.mutate();
      }
    })();
  };

  const chooseProof = async () => {
    if (!postCreate) return;
    setProofError(null);
    setPickingProof(true);

    try {
      const selected = await pickOrderPaymentProof(postCreate.payment_proof_limits);
      if (selected) setProofFile(selected);
    } catch (error) {
      setProofError(apiMessage(error, 'Potvrdu trenutno nije moguće izabrati.'));
    } finally {
      setPickingProof(false);
    }
  };

  const submitProof = () => {
    if (!postCreate?.capabilities.can_upload_payment_proof) {
      setProofError('Slanje potvrde uplate nije dostupno za ovu porudžbinu.');
      return;
    }

    if (!proofFile) {
      setProofError('Izaberi PDF/JPG/PNG/WebP potvrdu uplate.');
      return;
    }

    const amount = Number(proofAmount.replace(',', '.'));
    if (!Number.isFinite(amount) || amount <= 0) {
      setProofError('Unesi ispravan iznos uplate veći od nule.');
      return;
    }

    const paidAtDate = new Date(proofPaidAt);
    if (!proofPaidAt.trim() || Number.isNaN(paidAtDate.getTime())) {
      setProofError('Unesi ispravan datum i vreme uplate.');
      return;
    }

    setProofError(null);
    proofMutation.mutate({
      amount_rsd: amount,
      paid_at: paidAtDate.toISOString(),
      reference: proofReference.trim() || null,
      note: proofNote.trim() || null,
      proof: proofFile,
    });
  };

  const openSecureFile = async (
    key: string,
    action: () => Promise<void>,
    title: string,
  ) => {
    if (openingFile) return;
    setOpeningFile(key);

    try {
      await action();
    } catch (error) {
      feedback.notify({
        tone: 'danger',
        title,
        message: apiMessage(error, 'Fajl trenutno nije moguće otvoriti.'),
        durationMs: 5200,
      });
    } finally {
      setOpeningFile(null);
    }
  };

  return (
    <Screen keyboardShouldPersistTaps="handled">
      <Pressable onPress={() => router.back()}>
        <Text style={styles.back}>‹ Nazad na porudžbine</Text>
      </Pressable>

      <View style={styles.heading}>
        <View style={styles.flexOne}>
          <Text style={styles.eyebrow}>PORUDŽBINA</Text>
          <Text style={styles.title}>{order.order_number}</Text>
        </View>
        <Pill tone={order.status === 'completed' ? 'success' : order.status === 'cancelled' ? 'danger' : 'primary'}>
          {humanize(order.status)}
        </Pill>
      </View>

      <Card style={styles.total}>
        <View>
          <Text style={styles.label}>Ukupna vrednost</Text>
          <Text style={styles.totalValue}>{formatPrimaryMoney(order.subtotal_rsd)}</Text>
        </View>
        <View>
          <Text style={styles.label}>Kreirano</Text>
          <Text style={styles.value}>{formatDate(order.created_at, true)}</Text>
        </View>
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>Stavke</Text>
        {order.items?.map((item) => (
          <View key={item.id} style={styles.item}>
            <View style={styles.flexOne}>
              <Text style={styles.itemName}>{item.name}</Text>
              <Text style={styles.itemMeta}>{item.sku} · {item.quantity} kom.</Text>
            </View>
            <Text style={styles.itemPrice}>{formatPrimaryMoney(item.line_total_rsd)}</Text>
          </View>
        ))}
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>Isporuka</Text>
        <Text style={styles.value}>{order.shipping.full_name}</Text>
        <Text style={styles.muted}>{order.shipping.address}, {order.shipping.postal_code} {order.shipping.city}</Text>
        <Text style={styles.muted}>{order.shipping.phone}</Text>
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>Plaćanje i dobavljač</Text>
        <Info label="Način plaćanja" value={humanize(order.payment_method)} />
        <Info label="Status plaćanja" value={humanize(order.payment_status)} />
        <Info label="Dobavljač" value={order.supplier.name ?? '—'} />
      </Card>

      {postCreateQuery.isLoading ? (
        <LoadingState label="Učitavanje uplata, dokumenata i isporuke" />
      ) : null}

      {postCreateQuery.isError ? (
        <Card style={styles.section}>
          <Text style={styles.sectionTitle}>Posle kreiranja porudžbine</Text>
          <Text style={styles.errorText}>{apiMessage(postCreateQuery.error, 'Dodatni podaci trenutno nisu dostupni.')}</Text>
          <Button variant="secondary" onPress={() => void postCreateQuery.refetch()}>
            Pokušaj ponovo
          </Button>
        </Card>
      ) : null}

      {postCreate ? (
        <>
          <Card style={styles.section}>
            <Text style={styles.sectionTitle}>Uplate</Text>
            <View style={styles.summaryGrid}>
              <SummaryValue label="Vrednost" value={formatPrimaryMoney(postCreate.order.subtotal_rsd)} />
              <SummaryValue label="Potvrđeno" value={formatPrimaryMoney(postCreate.order.paid_total_rsd)} />
              <SummaryValue label="Preostalo" value={formatPrimaryMoney(postCreate.order.remaining_rsd)} />
            </View>
            <Info label="Stanje" value={humanize(postCreate.order.payment_state)} />
            <Info
              label="Dospeće"
              value={postCreate.order.payment_due_at ? formatDate(postCreate.order.payment_due_at, true) : '—'}
            />

            {postCreate.bank_transfer ? (
              <View style={styles.subsection}>
                <Text style={styles.subsectionTitle}>Podaci za uplatu na račun</Text>
                <Info label="Račun" value={postCreate.bank_transfer.account_number ?? '—'} />
                <Info label="Primalac" value={postCreate.bank_transfer.recipient_name ?? '—'} />
                <Info label="Model / poziv" value={postCreate.bank_transfer.reference ?? '—'} />
                <Info label="Šifra plaćanja" value={postCreate.bank_transfer.payment_code ?? '—'} />
                <Info label="Svrha" value={postCreate.bank_transfer.purpose ?? '—'} />
              </View>
            ) : null}

            {postCreate.capabilities.can_view_payments ? (
              <View style={styles.subsection}>
                <Text style={styles.subsectionTitle}>Evidencija uplata</Text>
                {postCreate.payments.length > 0 ? postCreate.payments.map((payment) => (
                  <PaymentRow
                    key={payment.id}
                    payment={payment}
                    opening={openingFile === `payment:${payment.id}`}
                    onOpen={() => void openSecureFile(
                      `payment:${payment.id}`,
                      () => openOrderPaymentProof(orderId, payment),
                      'Potvrdu uplate nije moguće otvoriti',
                    )}
                  />
                )) : (
                  <Text style={styles.muted}>Još nema evidentiranih uplata.</Text>
                )}
              </View>
            ) : null}
          </Card>

          {postCreate.capabilities.can_upload_payment_proof ? (
            <Card style={styles.section}>
              <Text style={styles.sectionTitle}>Pošalji potvrdu uplate</Text>
              <Text style={styles.help}>
                Do {formatOrderFileSize(postCreate.payment_proof_limits.max_bytes)} · {postCreate.payment_proof_limits.extensions.map((value) => value.toUpperCase()).join(', ')}
              </Text>

              <TextField
                label="Iznos RSD *"
                value={proofAmount}
                onChangeText={(value) => {
                  setProofAmount(value.replace(/[^0-9.,]/g, ''));
                  if (proofError) setProofError(null);
                }}
                keyboardType="decimal-pad"
              />
              <TextField
                label="Datum i vreme uplate *"
                value={proofPaidAt}
                onChangeText={(value) => {
                  setProofPaidAt(value);
                  if (proofError) setProofError(null);
                }}
                placeholder="YYYY-MM-DDTHH:mm"
                autoCapitalize="none"
                autoCorrect={false}
              />
              <TextField
                label="Referenca"
                value={proofReference}
                onChangeText={setProofReference}
                maxLength={190}
              />
              <TextField
                label="Napomena"
                value={proofNote}
                onChangeText={setProofNote}
                multiline
                numberOfLines={3}
                maxLength={2000}
                textAlignVertical="top"
                style={styles.noteInput}
              />

              <Button
                variant="secondary"
                onPress={() => void chooseProof()}
                loading={pickingProof}
              >
                {proofFile ? 'Promeni izabrani fajl' : 'Izaberi potvrdu'}
              </Button>

              {proofFile ? (
                <View style={styles.fileSelection}>
                  <View style={styles.flexOne}>
                    <Text style={styles.fileName}>{proofFile.name}</Text>
                    <Text style={styles.fileMeta}>{proofFile.type} · {formatOrderFileSize(proofFile.size)}</Text>
                  </View>
                  <Pressable
                    accessibilityRole="button"
                    onPress={() => setProofFile(null)}
                  >
                    <Text style={styles.removeLabel}>Ukloni</Text>
                  </Pressable>
                </View>
              ) : null}

              {proofError ? <Text style={styles.errorText}>{proofError}</Text> : null}

              <Button
                onPress={submitProof}
                loading={proofMutation.isPending}
                disabled={!proofFile || pickingProof}
              >
                Pošalji potvrdu uplate
              </Button>
            </Card>
          ) : null}

          {postCreate.capabilities.can_view_documents ? (
            <Card style={styles.section}>
              <Text style={styles.sectionTitle}>Dokumenti</Text>

              {postCreate.capabilities.can_issue_order_confirmation ? (
                <Button
                  variant="secondary"
                  onPress={() => void openSecureFile(
                    'confirmation',
                    () => openOrderConfirmationPdf(orderId, order.order_number),
                    'Potvrdu porudžbine nije moguće otvoriti',
                  )}
                  loading={openingFile === 'confirmation'}
                  disabled={openingFile !== null}
                >
                  Otvori / podeli potvrdu porudžbine
                </Button>
              ) : null}

              {postCreate.documents.length > 0 ? postCreate.documents.map((document) => (
                <DocumentRow
                  key={document.id}
                  document={document}
                  opening={openingFile === `document:${document.id}`}
                  disabled={openingFile !== null}
                  onOpen={() => void openSecureFile(
                    `document:${document.id}`,
                    () => openOrderDocumentPdf(orderId, document),
                    'Dokument nije moguće otvoriti',
                  )}
                />
              )) : (
                <Text style={styles.muted}>Još nema izdatih dokumenata.</Text>
              )}
            </Card>
          ) : null}

          {postCreate.delivery ? (
            <Card style={styles.section}>
              <Text style={styles.sectionTitle}>Potvrđena isporuka</Text>
              <Info label="Način" value={postCreate.delivery.delivery_method_label} />
              <Info
                label="Datum"
                value={postCreate.delivery.delivered_at ? formatDate(postCreate.delivery.delivered_at, true) : '—'}
              />
              <Info label="Primalac" value={postCreate.delivery.recipient_name ?? '—'} />
              <Info label="Telefon" value={postCreate.delivery.recipient_phone ?? '—'} />
              <Info label="Referenca" value={postCreate.delivery.reference ?? '—'} />
              <Info label="Napomena" value={postCreate.delivery.note ?? '—'} />

              {postCreate.delivery.has_proof
                && postCreate.delivery.proof
                && postCreate.capabilities.can_view_delivery_proof ? (
                  <Button
                    variant="secondary"
                    onPress={() => void openSecureFile(
                      'delivery',
                      () => openOrderDeliveryProof(orderId, postCreate.delivery!),
                      'Dokaz isporuke nije moguće otvoriti',
                    )}
                    loading={openingFile === 'delivery'}
                    disabled={openingFile !== null}
                  >
                    Otvori / podeli dokaz isporuke
                  </Button>
                ) : null}
            </Card>
          ) : null}
        </>
      ) : null}

      {canCreateAfterSales ? (
        <Button
          variant="secondary"
          onPress={() => router.push({ pathname: '/after-sales/create/[orderId]', params: { orderId: String(order.id) } })}
        >
          Pokreni reklamaciju, povrat ili servis
        </Button>
      ) : null}

      {canCancel ? (
        <Button variant="danger" onPress={confirmCancel} loading={cancel.isPending}>
          Otkaži porudžbinu
        </Button>
      ) : null}
    </Screen>
  );
}

function PaymentRow({
  payment,
  opening,
  onOpen,
}: {
  payment: OrderPaymentLedgerEntry;
  opening: boolean;
  onOpen: () => void;
}) {
  const styles = useThemedStyles(createStyles);
  const { formatPrimaryMoney } = useMoneyPresentation();

  return (
    <View style={styles.ledgerRow}>
      <View style={styles.flexOne}>
        <Text style={styles.itemName}>{payment.number || payment.entry_label}</Text>
        <Text style={styles.itemMeta}>
          {payment.entry_label} · {humanize(payment.status)} · {payment.paid_at ? formatDate(payment.paid_at, true) : '—'}
        </Text>
        {payment.rejection_reason ? <Text style={styles.rejection}>{payment.rejection_reason}</Text> : null}
      </View>
      <View style={styles.ledgerSide}>
        <Text style={styles.itemPrice}>{formatPrimaryMoney(payment.amount_rsd)}</Text>
        {payment.has_proof && payment.proof ? (
          <Button variant="ghost" onPress={onOpen} loading={opening}>
            Otvori potvrdu
          </Button>
        ) : null}
      </View>
    </View>
  );
}

function DocumentRow({
  document,
  opening,
  disabled,
  onOpen,
}: {
  document: OrderDocumentSummary;
  opening: boolean;
  disabled: boolean;
  onOpen: () => void;
}) {
  const styles = useThemedStyles(createStyles);
  const { formatPrimaryMoney } = useMoneyPresentation();

  return (
    <View style={styles.documentRow}>
      <View style={styles.flexOne}>
        <Text style={styles.itemName}>{document.number}</Text>
        <Text style={styles.itemMeta}>
          {humanize(document.type)} · rev. {document.revision_number} · {document.issued_at ? formatDate(document.issued_at, true) : '—'}
        </Text>
        <Text style={styles.itemMeta}>{document.currency} · {formatPrimaryMoney(document.total_rsd)}</Text>
      </View>
      <Button variant="ghost" onPress={onOpen} loading={opening} disabled={disabled}>
        PDF
      </Button>
    </View>
  );
}

function SummaryValue({ label, value }: { label: string; value: string }) {
  const styles = useThemedStyles(createStyles);
  return (
    <View style={styles.summaryValue}>
      <Text style={styles.label}>{label}</Text>
      <Text style={styles.summaryAmount}>{value}</Text>
    </View>
  );
}

function Info({
  label,
  value,
}: {
  label: string;
  value: string;
}) {
  const styles = useThemedStyles(createStyles);

  return (
    <View style={styles.info}>
      <Text style={styles.label}>{label}</Text>
      <Text style={styles.infoValue}>{value}</Text>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    heading: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.md },
    flexOne: { flex: 1 },
    eyebrow: { ...typography.small, color: theme.primary, letterSpacing: 1.2, fontWeight: '800' },
    title: { ...typography.h1, color: theme.ink, marginTop: 3 },
    total: { flexDirection: 'row', justifyContent: 'space-between', gap: spacing.lg },
    label: { ...typography.small, color: theme.muted },
    value: { ...typography.label, color: theme.ink, marginTop: 3 },
    infoValue: { ...typography.label, color: theme.ink, textAlign: 'right', flex: 1 },
    totalValue: { ...typography.h2, color: theme.primaryDark, marginTop: 3 },
    section: { gap: spacing.md },
    sectionTitle: { ...typography.h3, color: theme.ink },
    subsection: { gap: spacing.sm, marginTop: spacing.sm },
    subsectionTitle: { ...typography.label, color: theme.ink },
    item: { minHeight: 62, flexDirection: 'row', alignItems: 'center', gap: spacing.md, borderTopWidth: 1, borderTopColor: theme.line },
    itemName: { ...typography.label, color: theme.ink },
    itemMeta: { ...typography.small, color: theme.muted, marginTop: 3 },
    itemPrice: { ...typography.label, color: theme.primaryDark },
    muted: { ...typography.body, color: theme.muted, marginTop: spacing.xs },
    help: { ...typography.small, color: theme.muted },
    info: { minHeight: 48, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: spacing.md, borderTopWidth: 1, borderTopColor: theme.line },
    summaryGrid: { gap: spacing.sm },
    summaryValue: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: spacing.md },
    summaryAmount: { ...typography.h3, color: theme.primaryDark },
    ledgerRow: { gap: spacing.md, paddingVertical: spacing.md, borderTopWidth: 1, borderTopColor: theme.line },
    ledgerSide: { gap: spacing.sm },
    rejection: { ...typography.small, color: theme.danger, marginTop: spacing.xs },
    noteInput: { minHeight: 88, paddingTop: spacing.md },
    fileSelection: { flexDirection: 'row', alignItems: 'center', gap: spacing.md, padding: spacing.md, borderRadius: 16, backgroundColor: theme.surfaceMuted },
    fileName: { ...typography.label, color: theme.ink },
    fileMeta: { ...typography.small, color: theme.muted, marginTop: 3 },
    removeLabel: { ...typography.label, color: theme.danger, paddingVertical: spacing.sm },
    errorText: { ...typography.small, color: theme.danger, backgroundColor: theme.dangerSoft, padding: spacing.md, borderRadius: 16 },
    documentRow: { gap: spacing.md, paddingVertical: spacing.md, borderTopWidth: 1, borderTopColor: theme.line },
  });
}
