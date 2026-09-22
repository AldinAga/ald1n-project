import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, type Href, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Switch, Text, TextInput, View } from 'react-native';

import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiAdminCustomerPortal, type AdminPortalOrder } from '@/features/admin/customer-portal-admin-api';
import { apiAdminReports } from '@/features/admin/reports-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { formatDate, formatMoney } from '@/lib/formatters';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';

// MOBILE_BUILD18_CUSTOMER360_WORKSPACE_BATCH171
// MOBILE_BUILD18_CUSTOMER360_PROFITABILITY_BATCH173
export default function AdminCustomerPortalUserScreen() {
  const params = useLocalSearchParams<{ userId: string }>();
  const userId = Number(params.userId);
  const styles = useThemedStyles(createStyles);
  const { colors: themeColors } = useAppTheme();
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { can } = useAuth();
  const allowed = can('system.manage_users') && Number.isInteger(userId) && userId > 0;
  const profitabilityAllowed = can('reports.view');
  const [orderQ, setOrderQ] = useState('');
  const [reason, setReason] = useState('');
  const [confirmReassign, setConfirmReassign] = useState(false);
  const [moveRelated, setMoveRelated] = useState(false);
  const [crmNoteBody, setCrmNoteBody] = useState('');

  const queryKey = adminQueryKeys.customerPortalUser(userId, orderQ);
  const query = useQuery({
    queryKey,
    queryFn: () => apiAdminCustomerPortal.user(userId, orderQ),
    enabled: allowed,
  });
  const profitabilityQuery = useQuery({
    queryKey: adminQueryKeys.reportCustomerProfitability(userId),
    queryFn: () => apiAdminReports.management({
      report_type: 'profitability',
      scope: 'completed',
      customer_user_id: userId,
    }),
    enabled: allowed && profitabilityAllowed,
  });

  const refresh = async () => {
    await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalUserRoot(userId) });
    await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalUser360(userId) });
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

  const appendNote = useMutation({
    mutationFn: () => apiAdminCustomerPortal.appendCrmNote(userId, crmNoteBody.trim()),
    onSuccess: async () => {
      setCrmNoteBody('');
      await refresh();
      feedback.notify({ tone: 'success', title: 'Beleška dodata', message: 'Interna CRM beleška je sačuvana.' });
    },
  });

  if (!allowed) return <UnavailableState title="Kupac nije dostupan" />;
  if (query.isLoading) return <LoadingState label="Učitavanje kupca…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  if (!query.data) return <UnavailableState title="Kupac nije pronađen" />;

  const customer = query.data.customer;
  const customer360 = query.data.customer_360;
  const summary = customer360.summary;  const profitability = profitabilityQuery.data?.data.advanced_analytics.customers.find(
    (row) => row.customer_user_id === userId,
  ) ?? null;
  return (
    <Screen>
      <Pressable onPress={() => router.back()}><Text style={styles.back}>‹ Customer Portal</Text></Pressable>
      <Text style={styles.title}>{customer.name}</Text>
      <Text style={styles.meta}>{customer.email ?? customer.username} · {customer.status}</Text>

      <Card style={styles.customer360Card}>
        <Text style={styles.section}>Customer 360</Text>
        <View style={styles.metricGrid}>
          <Metric label="Porudžbine" value={String(summary.orders_count)} />
          <Metric label="Ukupan promet" value={formatMoney(summary.lifetime_revenue_rsd, 'RSD')} />
          <Metric label="Prosečna porudžbina" value={formatMoney(summary.average_order_value_rsd, 'RSD')} />
          <Metric label="Potraživanje" value={formatMoney(summary.outstanding_rsd, 'RSD')} />
        </View>
        <Text style={styles.meta}>Poslednja kupovina: {formatDate(summary.last_purchase_at, true)}</Text>
        <View style={styles.signalGrid}>
          <Text style={styles.meta}>Aktivne reklamacije: {summary.active_after_sales_count}</Text>
          <Text style={styles.meta}>Aktivne garancije: {summary.active_warranties_count}</Text>
          <Text style={styles.meta}>Otvorene komunikacije: {summary.open_conversations_count}</Text>
          <Text style={styles.meta}>Nepročitano za osoblje: {summary.unread_staff_messages_count}</Text>
        </View>
      </Card>

      {profitabilityAllowed ? (
        <Card style={styles.customer360Card}>
          <Text style={styles.section}>Profitabilnost kupca</Text>
          <Text style={styles.meta}>
            Finansijski pokazatelji dolaze iz Management Reports autoriteta i nisu preračunati u Customer 360 ekranu.
          </Text>
          {profitabilityQuery.isLoading ? <Text style={styles.meta}>Učitavanje profitabilnosti…</Text> : null}
          {profitabilityQuery.isError ? (
            <View style={styles.actions}>
              <Text style={styles.error}>Profitabilnost trenutno nije dostupna.</Text>
              <Button variant="secondary" onPress={() => void profitabilityQuery.refetch()}>Pokušaj ponovo</Button>
            </View>
          ) : null}
          {!profitabilityQuery.isLoading && !profitabilityQuery.isError && profitability ? (
            <>
              <Text style={styles.rowTitle}>Lifetime</Text>
              <View style={styles.metricGrid}>
                <Metric label="LTV" value={profitability.ltv_rsd === null ? '—' : formatMoney(profitability.ltv_rsd, 'RSD')} />
                <Metric label="Lifetime prihod" value={profitability.lifetime_revenue_rsd === null ? '—' : formatMoney(profitability.lifetime_revenue_rsd, 'RSD')} />
                <Metric label="Lifetime neto doprinos" value={profitability.lifetime_net_contribution_rsd === null ? '—' : formatMoney(profitability.lifetime_net_contribution_rsd, 'RSD')} />
                <Metric label="Lifetime porudžbine" value={profitability.lifetime_orders_count === null ? '—' : String(profitability.lifetime_orders_count)} />
              </View>
              <Text style={styles.rowTitle}>Izabrani period</Text>
              <View style={styles.metricGrid}>
                <Metric label="Prihod" value={formatMoney(profitability.revenue_rsd, 'RSD')} />
                <Metric label="Bruto dobit" value={formatMoney(profitability.gross_profit_rsd, 'RSD')} />
                <Metric label="Neto doprinos" value={formatMoney(profitability.net_contribution_rsd, 'RSD')} />
                <Metric label="COGS" value={formatMoney(profitability.cogs_rsd, 'RSD')} />
              </View>
              <Text style={styles.meta}>Bruto marža: {profitability.gross_margin_percent.toFixed(1)}%</Text>
              <Text style={styles.meta}>Neto marža: {profitability.net_margin_percent.toFixed(1)}%</Text>
              <Text style={styles.meta}>Pokrivenost troška: {profitability.cost_coverage_percent.toFixed(1)}%</Text>
            </>
          ) : null}
          {!profitabilityQuery.isLoading && !profitabilityQuery.isError && !profitability ? (
            <Text style={styles.meta}>Nema profitability podataka za ovog kupca u canonical Management Reports odgovoru.</Text>
          ) : null}
        </Card>
      ) : null}
      <Card style={styles.actions}>
        <Text style={styles.section}>Interne CRM beleške</Text>
        <Text style={styles.meta}>Beleške su interne, append-only i vidljive samo administratorskom Customer 360 toku.</Text>
        <TextInput
          value={crmNoteBody}
          onChangeText={setCrmNoteBody}
          placeholder="Dodaj internu belešku"
          multiline
          maxLength={5000}
          textAlignVertical="top"
          style={styles.noteInput}
        />
        <Button
          loading={appendNote.isPending}
          disabled={crmNoteBody.trim().length < 2}
          onPress={() => appendNote.mutate()}
        >
          Dodaj belešku
        </Button>
        {appendNote.isError ? <Text style={styles.error}>Beleška nije sačuvana. Proveri sadržaj i pokušaj ponovo.</Text> : null}
        {customer360.crm_notes.length ? customer360.crm_notes.map((note) => (
          <View key={note.id} style={styles.noteRow}>
            <Text style={styles.rowTitle}>{note.author?.name ?? 'Osoblje'}</Text>
            <Text style={styles.meta}>{formatDate(note.created_at, true)}</Text>
            <Text style={styles.noteBody}>{note.body}</Text>
          </View>
        )) : <Text style={styles.meta}>Još nema internih CRM beleški.</Text>}
      </Card>

      <Text style={styles.section}>Istorija kupca</Text>
      {customer360.timeline.length ? customer360.timeline.map((item, index) => (
        <Card key={`${item.type}-${item.occurred_at ?? 'unknown'}-${index}`} style={styles.row}>
          <Text style={styles.timelineType}>{timelineTypeLabel(item.type)}</Text>
          <Text style={styles.rowTitle}>{item.title}</Text>
          {item.summary ? <Text style={styles.meta}>{item.summary}</Text> : null}
          <Text style={styles.meta}>{formatDate(item.occurred_at, true)}{item.actor?.name ? ` · ${item.actor.name}` : ''}</Text>
        </Card>
      )) : <Card muted><Text style={styles.meta}>Nema događaja u Customer 360 istoriji.</Text></Card>}

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

function Metric({ label, value }: { label: string; value: string }) {
  const styles = useThemedStyles(createStyles);
  return <View style={styles.metric}><Text style={styles.metricValue}>{value}</Text><Text style={styles.meta}>{label}</Text></View>;
}

function timelineTypeLabel(type: string): string {
  if (type === 'order') return 'Porudžbina';
  if (type === 'order_link') return 'Povezivanje';
  if (type === 'crm_note') return 'CRM beleška';
  if (type === 'after_sales') return 'Reklamacija';
  if (type === 'conversation') return 'Komunikacija';
  return 'Događaj';
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    title: { ...typography.h1, color: theme.ink },
    meta: { ...typography.small, color: theme.muted },
    actions: { gap: spacing.md },
    customer360Card: { gap: spacing.md },
    metricGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    metric: { width: '48%', gap: spacing.xs, padding: spacing.sm, borderRadius: 12, backgroundColor: theme.surfaceMuted },
    metricValue: { ...typography.h3, color: theme.ink },
    signalGrid: { gap: spacing.xs },
    noteInput: { ...typography.body, color: theme.ink, minHeight: 120, borderWidth: 1, borderColor: theme.line, borderRadius: 12, padding: spacing.md, backgroundColor: theme.surfaceMuted },
    noteRow: { gap: spacing.xs, paddingVertical: spacing.sm, borderTopWidth: 1, borderTopColor: theme.line },
    noteBody: { ...typography.body, color: theme.ink },
    timelineType: { ...typography.small, color: theme.primary, fontWeight: '800' },
    section: { ...typography.h2, color: theme.ink },
    row: { gap: spacing.xs },
    rowTitle: { ...typography.label, color: theme.ink },
    switchRow: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
    switchCopy: { flex: 1, gap: spacing.xs },
    error: { ...typography.small, color: theme.danger },
  });
}
