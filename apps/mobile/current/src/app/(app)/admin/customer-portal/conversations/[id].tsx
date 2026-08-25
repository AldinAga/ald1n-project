import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiAdminCustomerPortal } from '@/features/admin/customer-portal-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { formatDate } from '@/lib/formatters';
import { useThemedStyles } from '@/theme/app-theme';

export default function AdminPortalConversationScreen() {
  const params = useLocalSearchParams<{ id: string }>();
  const id = Number(params.id);
  const styles = useThemedStyles(createStyles);
  const client = useQueryClient();
  const { can } = useAuth();
  const allowed = can('system.manage_users') && Number.isInteger(id) && id > 0;
  const [body, setBody] = useState('');
  const [visibility, setVisibility] = useState<'public' | 'internal'>('public');
  const [status, setStatus] = useState<string | null>(null);
  const [priority, setPriority] = useState<string | null>(null);
  const [assignedTo, setAssignedTo] = useState<string | null>(null);

  const queryKey = adminQueryKeys.customerPortalConversation(id);
  const query = useQuery({
    queryKey,
    queryFn: () => apiAdminCustomerPortal.conversation(id),
    enabled: allowed,
  });

  const reply = useMutation({
    mutationFn: () => apiAdminCustomerPortal.reply(id, {
      body: body.trim(),
      visibility,
      status: status || null,
    }),
    onSuccess: async (data) => {
      setBody('');
      client.setQueryData(queryKey, data);
      await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalRoot() });
    },
  });
  const update = useMutation({
    mutationFn: () => apiAdminCustomerPortal.updateConversation(id, {
      status: status ?? query.data?.status ?? 'open',
      priority: priority ?? query.data?.priority ?? 'normal',
      assigned_to: assignedTo === null
        ? (query.data?.assignee?.id ?? null)
        : (assignedTo === '' ? null : Number(assignedTo)),
    }),
    onSuccess: async (data) => {
      client.setQueryData(queryKey, data);
      setStatus(data.status);
      setPriority(data.priority);
      setAssignedTo(data.assignee ? String(data.assignee.id) : '');
      await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalRoot() });
    },
  });

  if (!allowed) return <UnavailableState title="Komunikacija nije dostupna" />;
  if (query.isLoading) return <LoadingState label="Učitavanje komunikacije…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  if (!query.data) return <UnavailableState title="Komunikacija nije pronađena" />;

  const currentStatus = status ?? query.data.status;
  const currentPriority = priority ?? query.data.priority;
  const currentAssignee = assignedTo ?? (query.data.assignee ? String(query.data.assignee.id) : '');

  return (
    <Screen>
      <Pressable onPress={() => router.back()}><Text style={styles.back}>‹ Nazad</Text></Pressable>
      <Text style={styles.title}>{query.data.subject}</Text>
      <Text style={styles.meta}>{query.data.customer?.name ?? 'Kupac'} · {query.data.status_label}</Text>

      <View style={styles.messages}>
        {query.data.messages.map((message) => (
          <Card key={message.id} style={message.visibility === 'internal' ? styles.internal : styles.public}>
            <Text style={styles.sender}>{message.sender.name} · {message.visibility === 'internal' ? 'INTERNO' : 'JAVNO'}</Text>
            <Text style={styles.body}>{message.body}</Text>
            <Text style={styles.meta}>{formatDate(message.sent_at, true)}</Text>
          </Card>
        ))}
      </View>

      <Card style={styles.form}>
        <Text style={styles.section}>Odgovor</Text>
        <TextField label="Poruka" value={body} onChangeText={setBody} multiline maxLength={10000} />
        <SelectSheet
          label="Vidljivost"
          value={visibility}
          onChange={(value) => setVisibility(value === 'internal' ? 'internal' : 'public')}
          options={[
            { value: 'public', label: 'Javno kupcu' },
            { value: 'internal', label: 'Interna napomena' },
          ]}
        />
        <Button loading={reply.isPending} onPress={() => reply.mutate()}>Pošalji odgovor</Button>
      </Card>

      <Card style={styles.form}>
        <Text style={styles.section}>Obrada teme</Text>
        <SelectSheet
          label="Status"
          value={currentStatus}
          onChange={setStatus}
          options={Object.entries(query.data.status_labels).map(([value, label]) => ({ value, label }))}
        />
        <SelectSheet
          label="Prioritet"
          value={currentPriority}
          onChange={setPriority}
          options={Object.entries(query.data.priority_labels).map(([value, label]) => ({ value, label }))}
        />
        <SelectSheet
          label="Zaduženi"
          value={currentAssignee}
          onChange={setAssignedTo}
          options={[
            { value: '', label: 'Nije dodeljeno' },
            ...query.data.staff_options.map((staff) => ({ value: String(staff.id), label: staff.name, detail: staff.role ?? undefined })),
          ]}
        />
        <Button variant="secondary" loading={update.isPending} onPress={() => update.mutate()}>Sačuvaj obradu</Button>
      </Card>

      {(reply.isError || update.isError) ? <Text style={styles.error}>Izmena nije sačuvana. Proveri podatke i pokušaj ponovo.</Text> : null}
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    title: { ...typography.h1, color: theme.ink },
    meta: { ...typography.small, color: theme.muted },
    messages: { gap: spacing.sm },
    public: { gap: spacing.xs },
    internal: { gap: spacing.xs, borderColor: theme.accent },
    sender: { ...typography.label, color: theme.primary },
    body: { ...typography.body, color: theme.ink },
    form: { gap: spacing.md },
    section: { ...typography.h2, color: theme.ink },
    error: { ...typography.small, color: theme.danger },
  });
}
