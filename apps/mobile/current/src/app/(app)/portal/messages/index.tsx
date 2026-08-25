import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, type Href } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { SelectSheet } from '@/components/ui/select-sheet';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { apiPortal } from '@/features/portal/portal-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useThemedStyles } from '@/theme/app-theme';

const portalKey = ['portal', 'messages'] as const;

export default function PortalMessagesScreen() {
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { hasFeature, can } = useAuth();
  const allowed = hasFeature('customer_portal') && can('orders.view_own');
  const [subject, setSubject] = useState('');
  const [body, setBody] = useState('');
  const [orderId, setOrderId] = useState('');

  const query = useQuery({ queryKey: portalKey, queryFn: apiPortal.list, enabled: allowed });
  const create = useMutation({
    mutationFn: apiPortal.create,
    onSuccess: async (conversation) => {
      setSubject('');
      setBody('');
      setOrderId('');
      await client.invalidateQueries({ queryKey: portalKey });
      feedback.notify({ tone: 'success', title: 'Poruka poslata', message: 'Nova tema je otvorena.' });
      router.push(`/portal/messages/${conversation.id}` as Href);
    },
  });

  if (!allowed) return <UnavailableState title="Poruke podršci nisu dostupne" />;
  if (query.isLoading) return <LoadingState label="Učitavanje poruka…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  if (!query.data) return <EmptyState title="Portal nije dostupan" message="Pokušaj ponovo ili proveri dostupnost Customer Portal modula." />;

  const orderOptions = [
    { value: '', label: 'Bez povezane porudžbine' },
    ...query.data.orders.map((order) => ({
      value: String(order.id),
      label: order.order_number,
      detail: order.status,
    })),
  ];

  return (
    <Screen>
      <PageHeader title="Poruke podršci" eyebrow="Moje aktivnosti" />
      <Card style={styles.compose}>
        <Text style={styles.title}>Nova tema</Text>
        <Text style={styles.copy}>Pošalji pitanje podršci i opciono ga poveži sa svojom porudžbinom.</Text>
        <TextField label="Naslov" value={subject} onChangeText={setSubject} maxLength={190} />
        <TextField label="Poruka" value={body} onChangeText={setBody} multiline maxLength={10000} />
        <SelectSheet label="Porudžbina" value={orderId} options={orderOptions} onChange={setOrderId} />
        <Button
          loading={create.isPending}
          onPress={() => create.mutate({
            subject: subject.trim(),
            body: body.trim(),
            order_id: orderId ? Number(orderId) : null,
          })}
        >
          Pošalji poruku
        </Button>
        {create.isError ? <Text style={styles.error}>Poruku nije moguće poslati. Proveri podatke i pokušaj ponovo.</Text> : null}
      </Card>

      <Text style={styles.section}>Moje teme</Text>
      {query.data.data.length ? query.data.data.map((conversation) => (
        <Pressable
          key={conversation.id}
          accessibilityRole="button"
          onPress={() => router.push(`/portal/messages/${conversation.id}` as Href)}
        >
          <Card style={styles.row}>
            <View style={styles.rowCopy}>
              <Text style={styles.rowTitle}>{conversation.subject}</Text>
              <Text style={styles.meta}>
                {conversation.status_label}
                {conversation.order ? ` · ${conversation.order.order_number}` : ''}
              </Text>
              {conversation.latest_message ? (
                <Text style={styles.preview} numberOfLines={2}>{conversation.latest_message.body}</Text>
              ) : null}
            </View>
            {conversation.unread_count > 0 ? (
              <View style={styles.badge}><Text style={styles.badgeText}>{conversation.unread_count}</Text></View>
            ) : null}
          </Card>
        </Pressable>
      )) : <EmptyState title="Nema poruka" message="Kada otvoriš temu, pojaviće se ovde." />}
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    compose: { gap: spacing.md },
    title: { ...typography.h2, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
    section: { ...typography.h2, color: theme.ink, marginTop: spacing.sm },
    row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
    rowCopy: { flex: 1, gap: spacing.xs },
    rowTitle: { ...typography.h3, color: theme.ink },
    meta: { ...typography.small, color: theme.primary },
    preview: { ...typography.body, color: theme.muted },
    badge: { minWidth: 30, height: 30, borderRadius: radii.pill, backgroundColor: theme.danger, alignItems: 'center', justifyContent: 'center', paddingHorizontal: spacing.sm },
    badgeText: { ...typography.label, color: theme.onDanger },
    error: { ...typography.small, color: theme.danger },
  });
}
