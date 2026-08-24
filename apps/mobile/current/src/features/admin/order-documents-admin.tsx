import { useMemo, useState } from 'react';
import { useMutation } from '@tanstack/react-query';
import { Modal, StyleSheet, Text, TextInput, View } from 'react-native';

import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminOrders,
  type AdminOrderDetailRecord,
  type AdminOrderDocumentType,
} from '@/features/admin/orders-admin-api';
import { openAdminOrderConfirmationPdf, openAdminOrderDocumentPdf } from '@/features/admin/order-document-files';
import { useAppTheme } from '@/theme/app-theme';

const DOCUMENT_TYPES: Array<{ type: AdminOrderDocumentType; label: string }> = [
  { type: 'proforma', label: 'predračun' },
  { type: 'invoice', label: 'račun' },
  { type: 'delivery_note', label: 'otpremnicu' },
];

function scalar(record: AdminOrderDetailRecord, key: string): string {
  const value = record[key];
  if (typeof value === 'string') return value;
  if (typeof value === 'number' || typeof value === 'boolean') return String(value);
  return '';
}

function numberValue(record: AdminOrderDetailRecord, key: string): number | null {
  const value = record[key];
  if (typeof value === 'number' && Number.isInteger(value) && value > 0) return value;
  if (typeof value === 'string' && /^\d+$/.test(value)) {
    const parsed = Number(value);
    return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
  }
  return null;
}

function boolValue(record: AdminOrderDetailRecord, key: string): boolean {
  return record[key] === true;
}

function labelForType(type: string): string {
  if (type === 'order_confirmation' || type === 'confirmation') return 'Potvrda porudžbine';
  if (type === 'proforma') return 'Predračun';
  if (type === 'invoice') return 'Račun';
  if (type === 'delivery_note') return 'Otpremnica';
  return type || 'Dokument';
}

function errorText(error: unknown): string {
  return error instanceof Error && error.message.trim()
    ? error.message
    : 'Akcija trenutno nije dostupna.';
}

export function AdminOrderDocuments({
  orderId,
  documents,
  canManage,
  hasDelivery,
  onChanged,
}: {
  orderId: number;
  documents: AdminOrderDetailRecord[];
  canManage: boolean;
  hasDelivery: boolean;
  onChanged: () => void;
}) {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const [feedback, setFeedback] = useState<string | null> (null);
  const [error, setError] = useState<string | null> (null);
  const [openingId, setOpeningId] = useState<number | null> (null);
  const [cancelDocument, setCancelDocument] = useState<AdminOrderDetailRecord | null> (null);
  const [cancelReason, setCancelReason] = useState('');

  const activeByType = useMemo(() => {
    const map = new Map<string, AdminOrderDetailRecord> ();
    for (const document of documents) {
      const type = scalar(document, 'type');
      if (type && scalar(document, 'status') === 'issued' && !map.has(type)) {
        map.set(type, document);
      }
    }
    return map;
  }, [documents]);

  const confirmationMutation = useMutation({
    mutationFn: () => openAdminOrderConfirmationPdf(orderId),
    onSuccess: () => {
      setError(null);
      setFeedback('Potvrda porudžbine je spremna.');
      onChanged();
    },
    onError: (confirmationError) => {
      setFeedback(null);
      setError(errorText(confirmationError));
    },
  });

  const openDocument = async (document: AdminOrderDetailRecord): Promise<void> => {
    const id = numberValue(document, 'id');
    if (id === null) {
      setError('Dokument nema validan identifikator.');
      return;
    }

    setError(null);
    setOpeningId(id);
    try {
      await openAdminOrderDocumentPdf(orderId, {
        id,
        type: scalar(document, 'type') || 'dokument',
        number: scalar(document, 'number') || `dokument-${id}`,
      });
    } catch (openError) {
      setError(errorText(openError));
    } finally {
      setOpeningId(null);
    }
  };

  const issueMutation = useMutation({
    mutationFn: (documentType: AdminOrderDocumentType) =>
      apiAdminOrders.documentIssue(orderId, { document_type: documentType }),
    onSuccess: (response) => {
      setError(null);
      setFeedback(response.message);
      onChanged();
      void openDocument(response.data);
    },
    onError: (issueError) => {
      setFeedback(null);
      setError(errorText(issueError));
    },
  });

  const cancelMutation = useMutation({
    mutationFn: ({ documentId, reason }: { documentId: number; reason: string }) =>
      apiAdminOrders.documentCancel(orderId, documentId, reason),
    onSuccess: (response) => {
      setError(null);
      setFeedback(response.message);
      setCancelDocument(null);
      setCancelReason('');
      onChanged();
    },
    onError: (cancelError) => {
      setFeedback(null);
      setError(errorText(cancelError));
    },
  });

  return (
    <Card style={styles.card}>
      <Text style={styles.sectionTitle}>Poslovni dokumenti</Text>
      <Text style={styles.muted}>
        Predračun, račun i otpremnica koriste isti Laravel OrderDocumentService kao CMS. Storniranjem se čuva istorija, a sledeće izdavanje postaje nova revizija.
      </Text>

      {canManage ? (
        <View style={styles.actions}>
          <Button
            variant="ghost"
            loading={confirmationMutation.isPending}
            onPress={() => confirmationMutation.mutate()}
          >
            Potvrda PDF
          </Button>
          {DOCUMENT_TYPES.map(({ type, label }) => {
            const active = activeByType.get(type);
            const structurallyUnavailable = type === 'delivery_note' && !hasDelivery;
            if (active) {
              const activeId = numberValue(active, 'id');
              return (
                <Button
                  key={type}
                  variant="secondary"
                  loading={activeId !== null && openingId === activeId}
                  onPress={() => void openDocument(active)}
                >
                  Otvori aktivni {label}
                </Button>
              );
            }

            return (
              <Button
                key={type}
                variant={type === 'invoice' ? 'primary' : 'secondary'}
                loading={issueMutation.isPending && issueMutation.variables === type}
                disabled={structurallyUnavailable}
                onPress={() => issueMutation.mutate(type)}
              >
                {type === 'delivery_note' && structurallyUnavailable
                  ? 'Otpremnica nakon evidentirane isporuke'
                  : `Izdaj ${label}`}
              </Button>
            );
          })}
        </View>
      ) : (
        <Text style={styles.muted}>Nedostaje invoices.manage dozvola za izdavanje, PDF i storniranje.</Text>
      )}

      {feedback ? <Text style={styles.feedback}>{feedback}</Text> : null}
      {error ? <Text style={styles.error}>Greška: {error}</Text> : null}

      <View style={styles.history}>
        <Text style={styles.subTitle}>Istorija dokumenata ({documents.length})</Text>
        {documents.length === 0 ? <Text style={styles.muted}>Još nema izdatih dokumenata.</Text> : null}
        {documents.map((document, index) => {
          const id = numberValue(document, 'id');
          const type = scalar(document, 'type');
          const number = scalar(document, 'number') || `Dokument ${index + 1}`;
          const status = scalar(document, 'status') || 'issued';
          const revision = scalar(document, 'revision_number') || '1';
          const supersedes = scalar(document, 'supersedes_number');
          const cancellationReason = scalar(document, 'cancellation_reason');
          const canCancel = canManage && id !== null && boolValue(document, 'can_cancel') && status === 'issued';

          return (
            <View key={`${id ?? index}-${number}`} style={styles.documentRow}>
              <View style={styles.documentCopy}>
                <Text style={styles.documentTitle}>{labelForType(type)} · {number}</Text>
                <Text style={styles.muted}>Status: {status === 'cancelled' ? 'storniran' : 'aktivan'} · Rev. {revision}</Text>
                {supersedes ? <Text style={styles.muted}>Menja dokument {supersedes}</Text> : null}
                {cancellationReason ? <Text style={styles.error}>Razlog storniranja: {cancellationReason}</Text> : null}
              </View>
              {canManage && id !== null ? (
                <View style={styles.rowActions}>
                  <Button
                    variant="ghost"
                    loading={openingId === id}
                    onPress={() => void openDocument(document)}
                  >
                    Otvori PDF
                  </Button>
                  {canCancel ? (
                    <Button
                      variant="danger"
                      onPress={() => {
                        setCancelDocument(document);
                        setCancelReason('');
                        setError(null);
                      }}
                    >
                      Storniraj
                    </Button>
                  ) : null}
                </View>
              ) : null}
            </View>
          );
        })}
      </View>

      <Modal
        visible={cancelDocument !== null}
        transparent
        animationType="fade"
        onRequestClose={() => setCancelDocument(null)}
      >
        <View style={styles.modalOverlay}>
          <Card style={styles.modalCard}>
            <Text style={styles.sectionTitle}>Storniranje dokumenta</Text>
            <Text style={styles.muted}>
              Dokument ostaje u istoriji. Posle storniranja možeš izdati novu reviziju istog tipa.
            </Text>
            <TextInput
              value={cancelReason}
              onChangeText={setCancelReason}
              multiline
              maxLength={1000}
              placeholder="Razlog storniranja (najmanje 5 znakova)"
              accessibilityLabel="Razlog storniranja dokumenta"
              style={styles.input}
            />
            <View style={styles.actions}>
              <Button variant="ghost" onPress={() => setCancelDocument(null)}>Odustani</Button>
              <Button
                variant="danger"
                loading={cancelMutation.isPending}
                disabled={cancelReason.trim().length < 5}
                onPress={() => {
                  const documentId = cancelDocument ? numberValue(cancelDocument, 'id') : null;
                  if (documentId === null) {
                    setError('Dokument nema validan identifikator.');
                    return;
                  }
                  cancelMutation.mutate({ documentId, reason: cancelReason.trim() });
                }}
              >
                Potvrdi storniranje
              </Button>
            </View>
          </Card>
        </View>
      </Modal>
    </Card>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    card: { gap: spacing.md },
    sectionTitle: { ...typography.h2, color: theme.ink },
    subTitle: { ...typography.h3, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    feedback: { ...typography.body, color: theme.ink, fontWeight: '700' },
    error: { ...typography.small, color: theme.ink, fontWeight: '700' },
    actions: { gap: spacing.sm },
    history: { gap: spacing.sm },
    documentRow: { gap: spacing.sm, paddingVertical: spacing.sm },
    documentCopy: { gap: spacing.xs },
    documentTitle: { ...typography.body, color: theme.ink, fontWeight: '800' },
    rowActions: { gap: spacing.sm },
    modalOverlay: {
      flex: 1,
      justifyContent: 'center',
      padding: spacing.lg,
      backgroundColor: 'rgba(0,0,0,0.55)',
    },
    modalCard: { gap: spacing.md },
    input: {
      ...typography.body,
      color: theme.ink,
      minHeight: 120,
      borderWidth: 1,
      borderColor: theme.muted,
      borderRadius: 12,
      padding: spacing.md,
      textAlignVertical: 'top',
    },
  });
}
