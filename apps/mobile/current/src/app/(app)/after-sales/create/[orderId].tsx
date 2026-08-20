import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { useEffect, useState } from 'react';
import { Keyboard, Pressable, StyleSheet, Text, View } from 'react-native';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import {
  formatAfterSalesAttachmentSize,
  pickAfterSalesAttachments,
} from '@/features/after-sales/attachment-picker';
import { ApiError } from '@/lib/api/client';
import { api } from '@/lib/api/endpoints';
import { useThemedStyles } from '@/theme/app-theme';
import type {
  AfterSalesCaseType,
  AfterSalesPriority,
  AfterSalesRequestedResolution,
  AfterSalesUploadFile,
  CreateAfterSalesCaseInput,
} from '@/types/api';

const CASE_TYPES: AfterSalesCaseType[] = ['complaint', 'return', 'service'];
const PRIORITIES: AfterSalesPriority[] = ['low', 'normal', 'high', 'urgent'];
const RESOLUTIONS: AfterSalesRequestedResolution[] = [
  'repair',
  'replacement',
  'partial_refund',
  'full_refund',
  'return',
  'inspection',
  'other',
];

type ItemDraft = {
  selected: boolean;
  quantity: string;
  issueDescription: string;
};

function apiMessage(error: unknown, fallback: string): string {
  if (error instanceof ApiError) {
    return error.firstFieldError() ?? error.message;
  }

  if (error instanceof Error && error.message) {
    return error.message;
  }

  return fallback;
}

export default function AfterSalesCreateScreen() {
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { orderId: orderIdParam } = useLocalSearchParams<{ orderId: string }>();
  const orderId = Number(orderIdParam);
  const validOrderId = Number.isInteger(orderId) && orderId > 0;
  const { can } = useAuth();
  const allowed = can('after_sales.create');

  const options = useQuery({
    queryKey: ['after-sales-options', orderId],
    queryFn: () => api.afterSales.options(orderId),
    enabled: allowed && validOrderId,
    staleTime: 60_000,
  });

  const [initializedOrderId, setInitializedOrderId] = useState<number | null>(null);
  const [caseType, setCaseType] = useState<AfterSalesCaseType>('complaint');
  const [priority, setPriority] = useState<AfterSalesPriority>('normal');
  const [requestedResolution, setRequestedResolution] = useState<AfterSalesRequestedResolution | null>(null);
  const [subject, setSubject] = useState('');
  const [description, setDescription] = useState('');
  const [itemDrafts, setItemDrafts] = useState<Record<number, ItemDraft>>({});
  const [attachments, setAttachments] = useState<AfterSalesUploadFile[]>([]);
  const [pickingAttachments, setPickingAttachments] = useState(false);
  const [formError, setFormError] = useState<string | null>(null);

  useEffect(() => {
    if (!options.data || initializedOrderId === orderId) return;

    const drafts: Record<number, ItemDraft> = {};
    for (const item of options.data.order.items) {
      drafts[item.id] = {
        selected: false,
        quantity: '1',
        issueDescription: '',
      };
    }

    setCaseType('complaint');
    setPriority(options.data.defaults.priority);
    setRequestedResolution(null);
    setSubject('');
    setDescription('');
    setItemDrafts(drafts);
    setAttachments([]);
    setFormError(null);
    setInitializedOrderId(orderId);
  }, [initializedOrderId, options.data, orderId]);

  const mutation = useMutation({
    mutationFn: (input: CreateAfterSalesCaseInput) => api.afterSales.create(orderId, input),
    onSuccess: async (created) => {
      Keyboard.dismiss();
      await client.invalidateQueries({ queryKey: ['after-sales'] });
      await client.invalidateQueries({ queryKey: ['after-sales-options', orderId] });

      feedback.notify({
        tone: 'success',
        title: 'Zahtev je kreiran',
        message: `${created.case_number} je uspešno poslat.`,
      });

      if (can('after_sales.view_own')) {
        router.replace({
          pathname: '/after-sales/[id]',
          params: { id: String(created.id) },
        });
        return;
      }

      router.replace({
        pathname: '/order/[id]',
        params: { id: String(orderId) },
      });
    },
    onError: (error) => {
      setFormError(apiMessage(error, 'Zahtev nije moguće kreirati.'));
    },
  });

  if (!allowed) {
    return <UnavailableState title="Kreiranje reklamacije ili servisa nije dostupno" />;
  }

  if (!validOrderId) {
    return <ErrorState error={new Error('Neispravan identifikator porudžbine.')} />;
  }

  if (options.isLoading) {
    return <LoadingState label="Provera uslova i priprema zahteva…" />;
  }

  if (options.isError || !options.data) {
    if (options.error instanceof ApiError && options.error.status === 404) {
      return (
        <UnavailableState
          title="Zahtev još nije moguće otvoriti"
          message="Porudžbina još ne ispunjava uslove za reklamaciju, povrat ili servis, ili više nije dostupna ovom nalogu."
        />
      );
    }

    return <ErrorState error={options.error} onRetry={() => void options.refetch()} />;
  }

  const data = options.data;

  const chooseAttachments = async () => {
    setFormError(null);
    setPickingAttachments(true);

    try {
      const result = await pickAfterSalesAttachments(attachments, data.limits);

      if (result.files.length > 0) {
        setAttachments((current) => [...current, ...result.files]);
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
      setFormError(apiMessage(error, 'Priloge trenutno nije moguće izabrati.'));
    } finally {
      setPickingAttachments(false);
    }
  };

  const removeAttachment = (index: number) => {
    setAttachments((current) => current.filter((_, currentIndex) => currentIndex !== index));
  };

  const updateItem = (itemId: number, patch: Partial<ItemDraft>) => {
    setItemDrafts((current) => ({
      ...current,
      [itemId]: {
        selected: false,
        quantity: '1',
        issueDescription: '',
        ...current[itemId],
        ...patch,
      },
    }));
  };

  const submit = () => {
    setFormError(null);

    const normalizedSubject = subject.trim();
    const normalizedDescription = description.trim();

    if (!normalizedSubject) {
      setFormError('Unesi naslov zahteva.');
      return;
    }

    if (normalizedSubject.length > data.limits.subject_max_length) {
      setFormError(`Naslov može imati najviše ${data.limits.subject_max_length} karaktera.`);
      return;
    }

    if (normalizedDescription.length < data.limits.description_min_length) {
      setFormError(`Opis mora imati najmanje ${data.limits.description_min_length} karaktera.`);
      return;
    }

    if (normalizedDescription.length > data.limits.description_max_length) {
      setFormError(`Opis može imati najviše ${data.limits.description_max_length} karaktera.`);
      return;
    }

    const selectedItems: CreateAfterSalesCaseInput['items'] = [];

    for (const item of data.order.items) {
      const draft = itemDrafts[item.id];
      if (!draft?.selected) continue;

      const quantity = Number(draft.quantity);
      if (!Number.isInteger(quantity) || quantity < 1 || quantity > item.quantity || quantity > 999) {
        setFormError(`Količina za „${item.name}“ mora biti između 1 i ${Math.min(item.quantity, 999)}.`);
        return;
      }

      const issueDescription = draft.issueDescription.trim();
      if (issueDescription.length > data.limits.issue_description_max_length) {
        setFormError(`Opis problema za „${item.name}“ može imati najviše ${data.limits.issue_description_max_length} karaktera.`);
        return;
      }

      selectedItems.push({
        order_item_id: item.id,
        quantity,
        issue_description: issueDescription || null,
      });
    }

    if (selectedItems.length === 0) {
      setFormError('Izaberi najmanje jednu stavku porudžbine.');
      return;
    }

    mutation.mutate({
      case_type: caseType,
      priority,
      subject: normalizedSubject,
      description: normalizedDescription,
      requested_resolution: requestedResolution,
      items: selectedItems,
      attachments: attachments.length ? attachments : undefined,
    });
  };

  return (
    <Screen keyboardShouldPersistTaps="handled">
      <Pressable onPress={() => router.back()}>
        <Text style={styles.back}>‹ Nazad na porudžbinu</Text>
      </Pressable>

      <View>
        <Text style={styles.eyebrow}>PODRŠKA NAKON KUPOVINE</Text>
        <Text style={styles.title}>Novi zahtev</Text>
        <Text style={styles.subtitle}>Porudžbina {data.order.order_number}</Text>
      </View>

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Tip zahteva</Text>
        {CASE_TYPES.map((value) => (
          <ChoiceRow
            key={value}
            label={data.case_types[value]}
            selected={caseType === value}
            onPress={() => setCaseType(value)}
          />
        ))}
      </Card>

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Prioritet</Text>
        {PRIORITIES.map((value) => (
          <ChoiceRow
            key={value}
            label={data.priorities[value]}
            selected={priority === value}
            onPress={() => setPriority(value)}
          />
        ))}
      </Card>

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Opis zahteva</Text>
        <TextField
          label="Naslov *"
          value={subject}
          onChangeText={setSubject}
          maxLength={data.limits.subject_max_length}
        />
        <Text style={styles.counter}>{subject.length}/{data.limits.subject_max_length}</Text>

        <TextField
          label="Detaljan opis *"
          value={description}
          onChangeText={setDescription}
          multiline
          numberOfLines={6}
          maxLength={data.limits.description_max_length}
          style={styles.multiline}
          textAlignVertical="top"
        />
        <Text style={styles.counter}>
          {description.length}/{data.limits.description_max_length} · minimum {data.limits.description_min_length}
        </Text>
      </Card>

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Traženo rešenje</Text>
        <ChoiceRow
          label="Bez posebnog zahteva"
          selected={requestedResolution === null}
          onPress={() => setRequestedResolution(null)}
        />
        {RESOLUTIONS.map((value) => (
          <ChoiceRow
            key={value}
            label={data.requested_resolutions[value]}
            selected={requestedResolution === value}
            onPress={() => setRequestedResolution(value)}
          />
        ))}
      </Card>

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Stavke porudžbine</Text>
        <Text style={styles.help}>Izaberi najmanje jednu stavku na koju se zahtev odnosi.</Text>

        {data.order.items.map((item) => {
          const draft = itemDrafts[item.id] ?? {
            selected: false,
            quantity: '1',
            issueDescription: '',
          };

          return (
            <View key={item.id} style={styles.itemBlock}>
              <Pressable
                accessibilityRole="checkbox"
                accessibilityState={{ checked: draft.selected }}
                onPress={() => updateItem(item.id, { selected: !draft.selected })}
                style={[styles.selectRow, draft.selected && styles.selectRowSelected]}
              >
                <View style={[styles.checkbox, draft.selected && styles.checkboxSelected]} />
                <View style={styles.flexOne}>
                  <Text style={styles.itemName}>{item.name}</Text>
                  <Text style={styles.itemMeta}>{item.sku || 'Bez SKU'} · kupljeno {item.quantity} kom.</Text>
                </View>
              </Pressable>

              {draft.selected ? (
                <View style={styles.itemFields}>
                  <TextField
                    label="Količina *"
                    value={draft.quantity}
                    onChangeText={(value) => updateItem(item.id, { quantity: value.replace(/[^0-9]/g, '') })}
                    keyboardType="number-pad"
                    maxLength={3}
                  />
                  <TextField
                    label="Opis problema za ovu stavku"
                    value={draft.issueDescription}
                    onChangeText={(value) => updateItem(item.id, { issueDescription: value })}
                    multiline
                    numberOfLines={3}
                    maxLength={data.limits.issue_description_max_length}
                    style={styles.issueInput}
                    textAlignVertical="top"
                  />
                </View>
              ) : null}
            </View>
          );
        })}
      </Card>

      <Card style={styles.section}>
        <Text style={styles.sectionTitle}>Prilozi</Text>
        <Text style={styles.help}>
          Možeš dodati do {data.limits.max_attachments} PDF/JPG/PNG/WebP priloga, do {formatAfterSalesAttachmentSize(data.limits.max_attachment_bytes)} po fajlu.
        </Text>

        <Button
          variant="secondary"
          onPress={() => void chooseAttachments()}
          loading={pickingAttachments}
          disabled={attachments.length >= data.limits.max_attachments}
        >
          Dodaj priloge ({attachments.length}/{data.limits.max_attachments})
        </Button>

        {attachments.map((attachment, index) => (
          <View key={`${attachment.uri}:${index}`} style={styles.attachmentRow}>
            <View style={styles.attachmentCopy}>
              <Text style={styles.attachmentName}>{attachment.name}</Text>
              <Text style={styles.attachmentMeta}>
                {attachment.type} · {formatAfterSalesAttachmentSize(attachment.size)}
              </Text>
            </View>
            <Pressable
              accessibilityRole="button"
              accessibilityLabel={`Ukloni prilog ${attachment.name}`}
              onPress={() => removeAttachment(index)}
            >
              <Text style={styles.attachmentRemove}>Ukloni</Text>
            </Pressable>
          </View>
        ))}
      </Card>

      <Card muted>
        <Text style={styles.reviewTitle}>Pre slanja</Text>
        <Text style={styles.reviewCopy}>
          Server ponovo proverava vlasništvo porudžbine, uslove za after-sales zahtev, količine i dozvole pre kreiranja slučaja.
        </Text>
      </Card>

      {formError ? <Text style={styles.error}>{formError}</Text> : null}

      <Button onPress={submit} loading={mutation.isPending}>
        Pošalji zahtev
      </Button>
    </Screen>
  );
}

function ChoiceRow({
  label,
  selected,
  onPress,
}: {
  label: string;
  selected: boolean;
  onPress: () => void;
}) {
  const styles = useThemedStyles(createStyles);

  return (
    <Pressable
      accessibilityRole="radio"
      accessibilityState={{ checked: selected }}
      onPress={onPress}
      style={[styles.option, selected && styles.optionSelected]}
    >
      <View style={[styles.radio, selected && styles.radioSelected]} />
      <Text style={styles.optionTitle}>{label}</Text>
    </Pressable>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    back: {
      ...typography.label,
      color: theme.primary,
      paddingVertical: spacing.sm,
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
      marginTop: spacing.xs,
    },
    subtitle: {
      ...typography.body,
      color: theme.muted,
      marginTop: spacing.xs,
    },
    section: {
      gap: spacing.md,
    },
    sectionTitle: {
      ...typography.h3,
      color: theme.ink,
    },
    help: {
      ...typography.small,
      color: theme.muted,
    },
    option: {
      minHeight: 58,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md,
      padding: spacing.md,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surface,
    },
    optionSelected: {
      borderColor: theme.primary,
      backgroundColor: theme.primarySoft,
    },
    radio: {
      width: 18,
      height: 18,
      borderRadius: 9,
      borderWidth: 2,
      borderColor: theme.muted,
      backgroundColor: theme.surface,
    },
    radioSelected: {
      borderColor: theme.primary,
      backgroundColor: theme.primary,
    },
    optionTitle: {
      ...typography.label,
      color: theme.ink,
      flex: 1,
    },
    counter: {
      ...typography.small,
      color: theme.muted,
      textAlign: 'right',
      marginTop: -spacing.sm,
    },
    multiline: {
      minHeight: 146,
      paddingTop: spacing.md,
    },
    itemBlock: {
      gap: spacing.md,
      paddingTop: spacing.md,
      borderTopWidth: 1,
      borderTopColor: theme.line,
    },
    selectRow: {
      minHeight: 64,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md,
      padding: spacing.md,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surface,
    },
    selectRowSelected: {
      borderColor: theme.primary,
      backgroundColor: theme.primarySoft,
    },
    checkbox: {
      width: 20,
      height: 20,
      borderRadius: 6,
      borderWidth: 2,
      borderColor: theme.muted,
      backgroundColor: theme.surface,
    },
    checkboxSelected: {
      borderColor: theme.primary,
      backgroundColor: theme.primary,
    },
    flexOne: {
      flex: 1,
    },
    itemName: {
      ...typography.label,
      color: theme.ink,
    },
    itemMeta: {
      ...typography.small,
      color: theme.muted,
      marginTop: 3,
    },
    itemFields: {
      gap: spacing.md,
      paddingLeft: spacing.md,
    },
    issueInput: {
      minHeight: 96,
      paddingTop: spacing.md,
    },
    attachmentRow: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md,
      paddingVertical: spacing.sm,
      borderTopWidth: 1,
      borderTopColor: theme.line,
    },
    attachmentCopy: {
      flex: 1,
    },
    attachmentName: {
      ...typography.label,
      color: theme.ink,
    },
    attachmentMeta: {
      ...typography.small,
      color: theme.muted,
      marginTop: 3,
    },
    attachmentRemove: {
      ...typography.label,
      color: theme.danger,
      paddingVertical: spacing.sm,
    },
    reviewTitle: {
      ...typography.label,
      color: theme.ink,
    },
    reviewCopy: {
      ...typography.small,
      color: theme.muted,
      marginTop: spacing.xs,
    },
    error: {
      ...typography.body,
      color: theme.danger,
      backgroundColor: theme.dangerSoft,
      padding: spacing.md,
      borderRadius: radii.lg,
      textAlign: 'center',
    },
  });
}
