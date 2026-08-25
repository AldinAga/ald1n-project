import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { apiPortal } from '@/features/portal/portal-api';
import { useAuth } from '@/features/auth/auth-provider';
import { formatDate } from '@/lib/formatters';
import { useThemedStyles } from '@/theme/app-theme';

export default function PortalMessageDetailScreen() {
  const styles = useThemedStyles(createStyles);
  const params = useLocalSearchParams<{ id: string }>();
  const id = Number(params.id);
  const { hasFeature, can } = useAuth();
  const allowed = hasFeature('customer_portal') && can('orders.view_own') && Number.isInteger(id) && id > 0;
  const client = useQueryClient();
  const [body, setBody] = useState('');
  const queryKey = ['portal', 'messages', id] as const;
  const query = useQuery({ queryKey, queryFn: () => apiPortal.detail(id), enabled: allowed });
  const reply = useMutation({
    mutationFn: () => apiPortal.reply(id, body.trim()),
    onSuccess: async (data) => {
      setBody('');
      client.setQueryData(queryKey, data);
      await client.invalidateQueries({ queryKey: ['portal', 'messages'] });
    },
  });

  if (!allowed) return <UnavailableState title="Poruka nije dostupna" />;
  if (query.isLoading) return <LoadingState label="Učitavanje razgovora…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  if (!query.data) return <UnavailableState title="Razgovor nije pronađen" />;

  return (
    <Screen>
      <Pressable onPress={() => router.back()}><Text style={styles.back}>‹ Nazad na poruke</Text></Pressable>
      <Text style={styles.title}>{query.data.subject}</Text>
      <Text style={styles.meta}>
        {query.data.status_label}
        {query.data.order ? ` · ${query.data.order.order_number}` : ''}
      </Text>

      <View style={styles.messages}>
        {query.data.messages.map((message) => (
          <Card key={message.id} style={message.sender.is_me ? styles.mine : styles.theirs}>
            <Text style={styles.sender}>{message.sender.is_me ? 'Vi' : message.sender.name}</Text>
            <Text style={styles.body}>{message.body}</Text>
            <Text style={styles.time}>{formatDate(message.sent_at, true)}</Text>
          </Card>
        ))}
      </View>

      {query.data.can_reply ? (
        <Card style={styles.reply}>
          <TextField label="Odgovor" value={body} onChangeText={setBody} multiline maxLength={10000} />
          <Button loading={reply.isPending} onPress={() => reply.mutate()}>Pošalji odgovor</Button>
          {reply.isError ? <Text style={styles.error}>Odgovor nije poslat.</Text> : null}
        </Card>
      ) : (
        <Card muted><Text style={styles.closed}>Tema je zatvorena. Za novo pitanje otvori novu temu.</Text></Card>
      )}
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    title: { ...typography.h1, color: theme.ink },
    meta: { ...typography.small, color: theme.primary },
    messages: { gap: spacing.sm },
    mine: { marginLeft: spacing.xl, gap: spacing.xs },
    theirs: { marginRight: spacing.xl, gap: spacing.xs },
    sender: { ...typography.label, color: theme.primary },
    body: { ...typography.body, color: theme.ink },
    time: { ...typography.small, color: theme.muted },
    reply: { gap: spacing.md },
    error: { ...typography.small, color: theme.danger },
    closed: { ...typography.body, color: theme.muted },
  });
}
