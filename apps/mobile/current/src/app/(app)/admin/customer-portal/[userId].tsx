import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, type Href, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Switch, Text, View } from 'react-native';

import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiAdminCustomerPortal, type AdminPortalOrder } from '@/features/admin/customer-portal-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { formatDate, formatMoney } from '@/lib/formatters';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';

export default function AdminCustomerPortalUserScreen() {
  const params = useLocalSearchParams<{ userId: string }>();
  const userId = Number(params.userId);
  const styles = useThemedStyles(createStyles);
  const { colors: themeColors } = useAppTheme();
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { can } = useAuth();
  const allowed = can('system.manage_users') && Number.isInteger(userId) && userId > 0;
  const [orderQ, setOrderQ] = useState('');
  const [reason, setReason] = useState('');
  const [confirmReassign, setConfirmReassign] = useState(false);
  const [moveRelated, setMoveRelated] = useState(false);

  const queryKey = adminQueryKeys.customerPortalUser(userId, orderQ);
  const query = useQuery({
    queryKey,
    queryFn: () => apiAdminCustomerPortal.user(userId, orderQ),
    enabled: allowed,
  });

  const refresh = async () => {
    await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalUserRoot(userId) });
    await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalRoot() });
  };
  const invite = useMutation({
    mutationFn: () => apiAdminCustomerPortal.invite(userId),
    onSuccess: async () => {
      await refresh();
      feedback.notify({ tone: 'success', title: 'Poziv poslat', message: 'Aktivacioni link je ponovo poslat kupcu.' });
    },
  });
  const revoke = useMutation({
    mutationFn: () => apiAdminCustomerPortal.revokeSessions(userId),
    onSuccess: async (response) => {
      await refresh();
      feedback.notify({
        tone: 'success',
        title: 'Prijave opozvane',
        message: `${response.data.revoked_web_sessions + response.data.revoked_api_sessions} aktivnih prijava je opozvano.`,
      });
    },
  });
  const link = useMutation({
    mutationFn: (order: AdminPortalOrder) => apiAdminCustomerPortal.linkOrder(userId, {
      order_id: order.id,
      reason: reason.trim(),
      confirm_reassign: confirmReassign,
      move_related_portal_data: moveRelated,
    }),
    onSuccess: async () => {
      setReason('');
      setConfirmReassign(false);
      setMoveRelated(false);
      await refresh();
      feedback.notify({ tone: 'success', title: 'Porudžbina povezana', message: 'Vlasništvo i izabrani portal podaci su usklađeni.' });
    },
  });

  if (!allowed) return <UnavailableState title="Kupac nije dostupan" />;
  if (query.isLoading) return <LoadingState label="Učitavanje kupca…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  if (!query.data) return <UnavailableState title="Kupac nije pronađen" />;

  const customer = query.data.customer;
  return (
    <Screen>
      <Pressable onPress={() => router.back()}><Text style={styles.back}>‹ Customer Portal</Text></Pressable>
      <Text style={styles.title}>{customer.name}</Text>
      <Text style={styles.meta}>{customer.email ?? customer.username} · {customer.status}</Text>

      <Card style={styles.actions}>
        <Button variant="secondary" loading={invite.isPending} onPress={() => invite.mutate()}>Pošalji aktivacioni poziv</Button>
        <Button
          variant="danger"
          loading={revoke.isPending}
          onPress={() => {
            void feedback.confirm({
              tone: 'danger',
              title: 'Opozovi sve prijave',
              message: 'Biće opozvane web, API i povezane mobilne prijave ovog kupca.',
              confirmLabel: 'Opozovi',
              cancelLabel: 'Odustani',
            }).then((ok) => { if (ok) revoke.mutate(); });
          }}
        >
          Opozovi sve prijave
        </Button>
      </Card>

      <Text style={styles.section}>Aktivne web prijave</Text>
      {query.data.active_web_sessions.length ? query.data.active_web_sessions.map((session) => (
        <Card key={session.id} style={styles.row}>
          <Text style={styles.rowTitle}>{session.device_label}</Text>
          <Text style={styles.meta}>{session.ip_address ?? 'IP nije dostupan'} · {session.remembered ? 'Zapamćena prijava' : 'Standardna prijava'}</Text>
          <Text style={styles.meta}>Poslednje: {formatDate(session.last_seen_at, true)}</Text>
        </Card>
      )) : <Card muted><Text style={styles.meta}>Nema aktivnih web prijava.</Text></Card>}

      <Text style={styles.section}>Povezane porudžbine</Text>
      {query.data.orders.map((order) => (
        <Card key={order.id} style={styles.row}>
          <Text style={styles.rowTitle}>{order.order_number}</Text>
          <Text style={styles.meta}>{order.status} · {formatMoney(order.subtotal_rsd, 'RSD')}</Text>
        </Card>
      ))}

      <Card style={styles.actions}>
        <Text style={styles.section}>Poveži / prenesi porudžbinu</Text>
        <TextField label="Pretraga porudžbine" value={orderQ} onChangeText={setOrderQ} />
        <TextField label="Razlog promene" value={reason} onChangeText={setReason} maxLength={500} />
        <View style={styles.switchRow}>
          <View style={styles.switchCopy}><Text style={styles.rowTitle}>Potvrdi prenos vlasništva</Text><Text style={styles.meta}>Potrebno ako porudžbina pripada drugom kupcu.</Text></View>
          <Switch value={confirmReassign} onValueChange={setConfirmReassign} trackColor={{ true: themeColors.primary }} />
        </View>
        <View style={styles.switchRow}>
          <View style={styles.switchCopy}><Text style={styles.rowTitle}>Premesti portal komunikaciju</Text><Text style={styles.meta}>Povezane teme uz porudžbinu prelaze novom kupcu.</Text></View>
          <Switch value={moveRelated} onValueChange={setMoveRelated} trackColor={{ true: themeColors.primary }} />
        </View>
        {query.data.order_search.map((order) => (
          <Card key={order.id} muted style={styles.row}>
            <Text style={styles.rowTitle}>{order.order_number}</Text>
            <Text style={styles.meta}>Trenutni vlasnik: {order.owner?.name ?? 'Nije dodeljen'}</Text>
            <Button variant="secondary" loading={link.isPending} onPress={() => link.mutate(order)}>Poveži sa ovim kupcem</Button>
          </Card>
        ))}
        {link.isError ? <Text style={styles.error}>Povezivanje nije uspelo. Proveri razlog i potvrdu prenosa.</Text> : null}
      </Card>

      <Text style={styles.section}>Komunikacije</Text>
      {query.data.conversations.map((conversation) => (
        <Pressable
          key={conversation.id}
          onPress={() => router.push(`/admin/customer-portal/conversations/${conversation.id}` as Href)}
        >
          <Card style={styles.row}>
            <Text style={styles.rowTitle}>{conversation.subject}</Text>
            <Text style={styles.meta}>{conversation.status_label} · {conversation.priority}</Text>
          </Card>
        </Pressable>
      ))}
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    title: { ...typography.h1, color: theme.ink },
    meta: { ...typography.small, color: theme.muted },
    actions: { gap: spacing.md },
    section: { ...typography.h2, color: theme.ink },
    row: { gap: spacing.xs },
    rowTitle: { ...typography.label, color: theme.ink },
    switchRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
    switchCopy: { flex: 1, gap: spacing.xs },
    error: { ...typography.small, color: theme.danger },
  });
}
