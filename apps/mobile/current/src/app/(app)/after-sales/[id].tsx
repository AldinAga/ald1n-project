import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { Pill, type PillTone } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { openAfterSalesAttachment } from '@/features/after-sales/attachment-download';
import {
  formatAfterSalesAttachmentSize,
  pickAfterSalesAttachments,
} from '@/features/after-sales/attachment-picker';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import { formatDate, formatMoney } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';
import type {
  AfterSalesAttachment,
  AfterSalesStatus,
  AfterSalesUploadFile,
  CreateAfterSalesMessageInput,
} from '@/types/api';

function statusTone(status: AfterSalesStatus | string): PillTone {
  if (status === 'resolved' || status === 'approved' || status === 'completed') return 'success';
  if (status === 'rejected' || status === 'cancelled') return 'danger';
  if (status === 'under_review' || status === 'awaiting_customer' || status === 'pending') return 'warning';
  if (status === 'in_service' || status === 'in_progress') return 'info';
  if (status === 'closed') return 'neutral';
  return 'primary';
}

function formatBytes(value: number): string {
  if (!Number.isFinite(value) || value <= 0) return '0 B';
  if (value < 1024) return `${Math.round(value)} B`;
  if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
  return `${(value / (1024 * 1024)).toFixed(1)} MB`;
}

function apiMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) {
    return error.firstFieldError() ?? error.message;
  }

  if (error instanceof Error && error.message) {
    return error.message;
  }

  return fallback;
}

export default function AfterSalesDetailScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const [messageBody, setMessageBody] = useState('');
  const [messageAttachments, setMessageAttachments] = useState<AfterSalesUploadFile[]>([]);
  const [pickingMessageAttachments, setPickingMessageAttachments] = useState(false);
  const [messageApiError, setMessageApiError] = useState<string | null>(null);
  const [openingAttachmentPath, setOpeningAttachmentPath] = useState<string | null>(null);
  const { id } = useLocalSearchParams<{ id: string }>();
  const caseId = Number(id);
  const { can, hasFeature } = useAuth();
  const allowed = can('after_sales.view_own');
  const validId = Number.isInteger(caseId) && caseId > 0;
  const query = useQuery({
    queryKey: ['after-sales', caseId],
    queryFn: () => api.afterSales.detail(caseId),
    enabled: allowed && validId
  });
  const messageMutation = useMutation({
    mutationFn: (input: CreateAfterSalesMessageInput) => api.afterSales.message(caseId, input),
    onSuccess: () => {
      setMessageBody('');
      setMessageAttachments([]);
      setMessageApiError(null);
      void client.invalidateQueries({ queryKey: ['after-sales'] });
      feedback.notify({
        tone: 'success',
        title: 'Poruka je poslata',
        message: 'Javna poruka je dodata u komunikaciju slučaja.'
      });
    },
    onError: (error) => {
      setMessageApiError(apiMessage(error, 'Poruku nije moguće poslati.'));
    }
  });

  if (!allowed) return <UnavailableState title="After-sales slučaj nije dostupan" />;
  if (!validId) return <ErrorState error={new Error('Neispravan identifikator after-sales slučaja.')} />;
  if (query.isLoading) return <LoadingState label="Učitavanje after-sales slučaja…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const caseData = query.data;
  const messageMaxLength = caseData.limits.message_max_length;

  const chooseMessageAttachments = async () => {
    setMessageApiError(null);
    setPickingMessageAttachments(true);

    try {
      const result = await pickAfterSalesAttachments(messageAttachments, caseData.limits);

      if (result.files.length > 0) {
        setMessageAttachments((current) => [...current, ...result.files]);
      }

      if (result.rejected.length > 0) {
        feedback.notify({
          tone: 'warning',
          title: 'Neki prilozi nisu dodati',
          message: result.rejected.join(' '),
          durationMs: 5200,
        });
      }
    } catch (error) {
      setMessageApiError(apiMessage(error, 'Priloge trenutno nije moguće izabrati.'));
    } finally {
      setPickingMessageAttachments(false);
    }
  };

  const removeMessageAttachment = (index: number) => {
    setMessageAttachments((current) => current.filter((_, currentIndex) => currentIndex !== index));
  };

  const openAttachment = async (attachment: AfterSalesAttachment) => {
    if (openingAttachmentPath !== null) return;

    setOpeningAttachmentPath(attachment.download_path);

    try {
      await openAfterSalesAttachment(attachment);
    } catch (error) {
      feedback.notify({
        tone: 'danger',
        title: 'Prilog nije moguće otvoriti',
        message: apiMessage(error, 'Pokušaj ponovo za nekoliko trenutaka.'),
        durationMs: 5200,
      });
    } finally {
      setOpeningAttachmentPath(null);
    }
  };

  const submitMessage = () => {
    const body = messageBody.trim();
    setMessageApiError(null);

    if (!caseData.can_message) {
      setMessageApiError('Komunikacija za ovaj slučaj je zatvorena.');
      return;
    }

    if (!body) {
      setMessageApiError('Unesi poruku pre slanja.');
      return;
    }

    if (body.length > messageMaxLength) {
      setMessageApiError(`Poruka može imati najviše ${messageMaxLength} znakova.`);
      return;
    }

    messageMutation.mutate({
      body,
      attachments: messageAttachments.length ? messageAttachments : undefined,
    });
  };

  return (
    <Screen keyboardShouldPersistTaps="handled">
      <Pressable onPress={() => router.back()}>
        <Text style={styles.back}>‹ Nazad na reklamacije i servis</Text>
      </Pressable>

      <View style={styles.heading}>
        <View style={styles.headingCopy}>
          <Text style={styles.eyebrow}>{caseData.case_type_label.toUpperCase()}</Text>
          <Text style={styles.title}>{caseData.case_number}</Text>
        </View>
        <Pill tone={statusTone(caseData.status)}>{caseData.status_label}</Pill>
      </View>

      <Card style={styles.heroCard}>
        <Text style={styles.subject}>{caseData.subject}</Text>
        <Text style={styles.description}>{caseData.description}</Text>
        <View style={styles.rule} />
        <Info label="Kreirano" value={formatDate(caseData.created_at, true)} />
        <Info label="Poslednja izmena" value={formatDate(caseData.updated_at, true)} />
      </Card>

      <Card>
        <Text style={styles.sectionTitle}>Detalji zahteva</Text>
        <Info label="Porudžbina" value={caseData.order.order_number} />
        <Info label="Tip" value={caseData.case_type_label} />
        <Info label="Prioritet" value={caseData.priority_label} />
        <Info label="Traženo rešenje" value={caseData.requested_resolution_label ?? '—'} />
        <Info label="Dodeljeno" value={caseData.assignee?.name ?? 'Još nije dodeljeno'} />
        <Info label="Rok" value={caseData.due_at ? formatDate(caseData.due_at, true) : '—'} />
        {caseData.resolution_type_label ? <Info label="Rešenje" value={caseData.resolution_type_label} /> : null}
        {caseData.resolution_summary ? <Text style={styles.note}>{caseData.resolution_summary}</Text> : null}

        {hasFeature('orders') ? (
          <Button
            variant="secondary"
            onPress={() => router.push({ pathname: '/order/[id]', params: { id: String(caseData.order.id) } })}
          >
            Otvori porudžbinu
          </Button>
        ) : null}
      </Card>

      {caseData.items.length > 0 ? (
        <Card>
          <Text style={styles.sectionTitle}>Obuhvaćene stavke</Text>
          {caseData.items.map((item) => (
            <View key={item.id} style={styles.block}>
              <View style={styles.rowBetween}>
                <Text style={styles.itemName}>{item.name}</Text>
                <Text style={styles.itemQty}>{item.quantity} kom.</Text>
              </View>
              {item.sku ? <Text style={styles.meta}>{item.sku}</Text> : null}
              {item.issue_description ? <Text style={styles.note}>{item.issue_description}</Text> : null}
            </View>
          ))}
        </Card>
      ) : null}

      {caseData.actions.length > 0 ? (
        <Card>
          <Text style={styles.sectionTitle}>Radnje i servis</Text>
          {caseData.actions.map((action) => (
            <View key={action.id} style={styles.block}>
              <View style={styles.rowBetween}>
                <View style={styles.flexOne}>
                  <Text style={styles.itemName}>{action.action_type_label}</Text>
                  <Text style={styles.meta}>{action.action_number}</Text>
                </View>
                <Pill tone={statusTone(action.status)}>{action.status_label}</Pill>
              </View>
              {action.public_note ? <Text style={styles.note}>{action.public_note}</Text> : null}
              {action.completion_note ? <Text style={styles.note}>{action.completion_note}</Text> : null}
              {action.amount_rsd !== null ? <Text style={styles.meta}>Iznos: {formatMoney(action.amount_rsd)}</Text> : null}
              {action.scheduled_at ? <Text style={styles.meta}>Zakazano: {formatDate(action.scheduled_at, true)}</Text> : null}
              {action.work_order ? (
                <View style={styles.subBlock}>
                  <Text style={styles.itemName}>Terenski nalog {action.work_order.work_order_number}</Text>
                  <Text style={styles.meta}>{action.work_order.status_label}</Text>
                  {action.work_order.planned_start_at ? (
                    <Text style={styles.meta}>
                      Termin: {formatDate(action.work_order.planned_start_at, true)}
                      {action.work_order.planned_end_at ? ` – ${formatDate(action.work_order.planned_end_at, true)}` : ''}
                    </Text>
                  ) : null}
                  {action.work_order.team ? (
                    <Text style={styles.meta}>
                      Ekipa: {action.work_order.team.name}
                      {action.work_order.team.phone ? ` · ${action.work_order.team.phone}` : ''}
                    </Text>
                  ) : null}
                  {action.work_order.completion_result ? (
                    <Text style={styles.note}>{action.work_order.completion_result}</Text>
                  ) : null}
                  {action.work_order.attachments.length > 0 ? (
                    <>
                      <Text style={styles.meta}>Dokazi i dokumentacija</Text>
                      {action.work_order.attachments.map((attachment) => (
                        <View key={attachment.download_path} style={styles.attachmentRow}>
                          <View style={styles.attachmentCopy}>
                            <Text style={styles.attachmentName}>{attachment.original_name}</Text>
                            <Text style={styles.meta}>{formatBytes(attachment.size_bytes)}</Text>
                          </View>
                          <Pressable
                            accessibilityRole="button"
                            accessibilityLabel={`Otvori ili podeli terenski prilog ${attachment.original_name}`}
                            accessibilityState={{ disabled: openingAttachmentPath !== null }}
                            disabled={openingAttachmentPath !== null}
                            onPress={() => void openAttachment(attachment)}
                          >
                            <Text style={styles.attachmentAction}>
                              {openingAttachmentPath === attachment.download_path ? 'Otvaranje…' : 'Otvori / podeli'}
                            </Text>
                          </Pressable>
                        </View>
                      ))}
                    </>
                  ) : null}
                </View>
              ) : null}
            </View>
          ))}
        </Card>
      ) : null}

      <Card>
        <Text style={styles.sectionTitle}>Komunikacija</Text>
        {caseData.messages.length > 0 ? caseData.messages.map((message) => (
          <View key={message.id} style={styles.block}>
            <View style={styles.rowBetween}>
              <Text style={styles.itemName}>{message.author?.name ?? 'Korisnik'}</Text>
              <Text style={styles.meta}>{formatDate(message.created_at, true)}</Text>
            </View>
            <Text style={styles.note}>{message.body}</Text>
            {message.attachments.map((attachment) => (
              <View key={attachment.id} style={styles.attachmentRow}>
                <View style={styles.attachmentCopy}>
                  <Text style={styles.attachmentName}>{attachment.original_name}</Text>
                  <Text style={styles.meta}>{formatBytes(attachment.size_bytes)}</Text>
                </View>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel={`Otvori ili podeli prilog ${attachment.original_name}`}
                  accessibilityState={{ disabled: openingAttachmentPath !== null }}
                  disabled={openingAttachmentPath !== null}
                  onPress={() => void openAttachment(attachment)}
                >
                  <Text style={styles.attachmentAction}>
                    {openingAttachmentPath === attachment.download_path ? 'Otvaranje…' : 'Otvori / podeli'}
                  </Text>
                </Pressable>
              </View>
            ))}
          </View>
        )) : (
          <Text style={styles.muted}>Još nema javnih poruka u ovom slučaju.</Text>
        )}

        {caseData.can_message ? (
          <View style={styles.composer}>
            <TextField
              label="Nova javna poruka"
              value={messageBody}
              onChangeText={(value) => {
                setMessageBody(value);
                if (messageApiError) setMessageApiError(null);
              }}
              multiline
              numberOfLines={4}
              maxLength={messageMaxLength}
              textAlignVertical="top"
              style={styles.messageInput}
            />
            <Text style={styles.counter}>{messageBody.length}/{messageMaxLength}</Text>

            <Text style={styles.messageAttachmentHelp}>
              Možeš dodati do {caseData.limits.max_attachments} PDF/JPG/PNG/WebP priloga, do {formatAfterSalesAttachmentSize(caseData.limits.max_attachment_bytes)} po fajlu.
            </Text>

            <Button
              variant="secondary"
              onPress={() => void chooseMessageAttachments()}
              loading={pickingMessageAttachments}
            >
              Dodaj priloge ({messageAttachments.length}/{caseData.limits.max_attachments})
            </Button>

            {messageAttachments.map((attachment, index) => (
              <View key={`${attachment.uri}:${index}`} style={styles.composerAttachmentRow}>
                <View style={styles.composerAttachmentCopy}>
                  <Text style={styles.composerAttachmentName}>{attachment.name}</Text>
                  <Text style={styles.composerAttachmentMeta}>
                    {attachment.type} · {formatAfterSalesAttachmentSize(attachment.size)}
                  </Text>
                </View>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel={`Ukloni prilog ${attachment.name}`}
                  onPress={() => removeMessageAttachment(index)}
                >
                  <Text style={styles.composerAttachmentRemove}>Ukloni</Text>
                </Pressable>
              </View>
            ))}

            {messageApiError ? <Text style={styles.messageError}>{messageApiError}</Text> : null}
            <Button
              onPress={submitMessage}
              loading={messageMutation.isPending}
              disabled={!messageBody.trim() || pickingMessageAttachments}
            >
              Pošalji poruku
            </Button>
          </View>
        ) : (
          <Text style={styles.closedMessage}>Komunikacija je zatvorena za ovaj slučaj.</Text>
        )}
      </Card>

      {caseData.attachments.length > 0 ? (
        <Card>
          <Text style={styles.sectionTitle}>Prilozi slučaja</Text>
          {caseData.attachments.map((attachment) => (
            <View key={attachment.id} style={styles.attachmentRow}>
              <View style={styles.attachmentCopy}>
                <Text style={styles.attachmentName}>{attachment.original_name}</Text>
                <Text style={styles.meta}>{formatBytes(attachment.size_bytes)}</Text>
              </View>
              <Pressable
                accessibilityRole="button"
                accessibilityLabel={`Otvori ili podeli prilog ${attachment.original_name}`}
                accessibilityState={{ disabled: openingAttachmentPath !== null }}
                disabled={openingAttachmentPath !== null}
                onPress={() => void openAttachment(attachment)}
              >
                <Text style={styles.attachmentAction}>
                  {openingAttachmentPath === attachment.download_path ? 'Otvaranje…' : 'Otvori / podeli'}
                </Text>
              </Pressable>
            </View>
          ))}
        </Card>
      ) : null}
    </Screen>
  );
}

function Info({ label, value }: { label: string; value: string }) {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);

  return (
    <View style={styles.info}>
      <Text style={styles.label}>{label}</Text>
      <Text style={styles.value}>{value}</Text>
    </View>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    back: {
      ...typography.label,
      color: theme.primary,
      paddingVertical: spacing.sm,
    },
    heading: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    headingCopy: {
      flex: 1,
    },
    eyebrow: {
      ...typography.small,
      color: theme.primary,
      letterSpacing: 1.2,
      fontWeight: '800',
    },
    title: {
      ...typography.h1,
      color: theme.ink,
      marginTop: 3,
    },
    heroCard: {
      gap: spacing.md,
    },
    subject: {
      ...typography.h3,
      color: theme.ink,
    },
    description: {
      ...typography.body,
      color: theme.muted,
    },
    sectionTitle: {
      ...typography.h3,
      color: theme.ink,
      marginBottom: spacing.md,
    },
    info: {
      minHeight: 48,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
      borderTopWidth: 1,
      borderTopColor: theme.line,
    },
    label: {
      ...typography.small,
      color: theme.muted,
      flex: 1,
    },
    value: {
      ...typography.label,
      color: theme.ink,
      flex: 1,
      textAlign: 'right',
    },
    rule: {
      height: 1,
      backgroundColor: theme.line,
    },
    block: {
      gap: spacing.sm,
      paddingVertical: spacing.md,
      borderTopWidth: 1,
      borderTopColor: theme.line,
    },
    subBlock: {
      gap: spacing.xs,
      padding: spacing.md,
      backgroundColor: theme.surfaceContainer,
    },
    rowBetween: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    flexOne: {
      flex: 1,
    },
    itemName: {
      ...typography.label,
      color: theme.ink,
      flex: 1,
    },
    itemQty: {
      ...typography.label,
      color: theme.primary,
    },
    meta: {
      ...typography.small,
      color: theme.muted,
    },
    muted: {
      ...typography.body,
      color: theme.muted,
    },
    note: {
      ...typography.body,
      color: theme.ink,
    },
    composer: {
      gap: spacing.sm,
      marginTop: spacing.md,
      paddingTop: spacing.md,
      borderTopWidth: 1,
      borderTopColor: theme.line,
    },
    messageInput: {
      minHeight: 112,
      paddingTop: spacing.md,
    },
    counter: {
      ...typography.small,
      color: theme.muted,
      textAlign: 'right',
    },
    messageAttachmentHelp: {
      ...typography.small,
      color: theme.muted,
    },
    composerAttachmentRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md,
      paddingVertical: spacing.sm,
      borderTopWidth: 1,
      borderTopColor: theme.line,
    },
    composerAttachmentCopy: {
      flex: 1,
    },
    composerAttachmentName: {
      ...typography.label,
      color: theme.ink,
    },
    composerAttachmentMeta: {
      ...typography.small,
      color: theme.muted,
      marginTop: 3,
    },
    composerAttachmentRemove: {
      ...typography.label,
      color: theme.danger,
      paddingVertical: spacing.sm,
    },
    messageError: {
      ...typography.small,
      color: theme.danger,
    },
    closedMessage: {
      ...typography.small,
      color: theme.muted,
      marginTop: spacing.md,
    },
    attachmentCopy: {
      flex: 1,
      gap: 3,
    },
    attachmentName: {
      ...typography.label,
      color: theme.ink,
    },
    attachmentAction: {
      ...typography.label,
      color: theme.primary,
      paddingLeft: spacing.sm,
      paddingVertical: spacing.sm,
    },
    attachmentRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
      paddingVertical: spacing.sm,
      borderTopWidth: 1,
      borderTopColor: theme.line,
    },
  });
}
