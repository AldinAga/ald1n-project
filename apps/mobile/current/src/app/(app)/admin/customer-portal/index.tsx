import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, type Href } from 'expo-router';
import { useState } from 'react';
import { Pressable, StyleSheet, Text, TextInput, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { SelectSheet } from '@/components/ui/select-sheet';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiAdminCustomerPortal } from '@/features/admin/customer-portal-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { formatDate, formatMoney } from '@/lib/formatters';
import { useThemedStyles } from '@/theme/app-theme';

// MOBILE_BUILD18_CUSTOMER360_WORKSPACE_BATCH171
export default function AdminCustomerPortalScreen() {
  const styles = useThemedStyles(createStyles);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { bootstrap, can } = useAuth();
  const allowed = can('system.manage_users');
  const [q, setQ] = useState('');
  const [status, setStatus] = useState('');
  const [email, setEmail] = useState('');
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [phone, setPhone] = useState('');
  const [address, setAddress] = useState('');
  const [city, setCity] = useState('');
  const [postalCode, setPostalCode] = useState('');
  const [unlinkedQ, setUnlinkedQ] = useState('');

  const query = useQuery({
    queryKey: adminQueryKeys.customerPortal({ q, status }),
    queryFn: () => apiAdminCustomerPortal.index({ q: q || undefined, status: status || undefined }),
    enabled: allowed,
  });
  const unlinkedQuery = useQuery({
    queryKey: adminQueryKeys.customerPortalUnlinkedBuyers(unlinkedQ),
    queryFn: () => apiAdminCustomerPortal.unlinkedBuyers(unlinkedQ),
    enabled: allowed,
  });
  const create = useMutation({
    mutationFn: apiAdminCustomerPortal.createCustomer,
    onSuccess: async (result) => {
      setEmail('');
      setFirstName('');
      setLastName('');
      setPhone('');
      setAddress('');
      setCity('');
      setPostalCode('');
      await client.invalidateQueries({ queryKey: adminQueryKeys.customerPortalRoot() });
      feedback.notify({
        tone: result.invitation_sent ? 'success' : 'warning',
        title: 'Kupac kreiran',
        message: result.invitation_sent ? 'Aktivacioni poziv je poslat.' : (result.invitation_error ?? 'Poziv nije poslat.'),
      });
      router.push(`/admin/customer-portal/${result.data.id}` as Href);
    },
  });

  if (!allowed) return <UnavailableState title="Customer Portal administracija nije dostupna" />;
  if (query.isLoading) return <LoadingState label="Učitavanje Customer Portal centra…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;
  if (!query.data) return <EmptyState title="Customer Portal nije dostupan" message="Pokušaj ponovo ili proveri dostupnost modula." />;

  return (
    <Screen>
      <PageHeader title="Customer 360" eyebrow="Administracija · Kupci" name={bootstrap?.user.name} />

      <View style={styles.stats}>
        <Stat label="Aktivni" value={query.data.stats.active_users} />
        <Stat label="Na čekanju" value={query.data.stats.pending_users} />
        <Stat label="Otvorene teme" value={query.data.stats.open_conversations} />
        <Stat label="Nepročitano" value={query.data.stats.unread_messages} />
      </View>

      <Card style={styles.form}>
        <Text style={styles.section}>Dodaj kupca</Text>
        <TextField label="E-mail" value={email} onChangeText={setEmail} autoCapitalize="none" keyboardType="email-address" />
        <TextField label="Ime" value={firstName} onChangeText={setFirstName} />
        <TextField label="Prezime" value={lastName} onChangeText={setLastName} />
        <TextField label="Telefon" value={phone} onChangeText={setPhone} keyboardType="phone-pad" />
        <TextField label="Adresa" value={address} onChangeText={setAddress} />
        <TextField label="Grad" value={city} onChangeText={setCity} />
        <TextField label="Poštanski broj" value={postalCode} onChangeText={setPostalCode} />
        <Button
          loading={create.isPending}
          onPress={() => create.mutate({
            email: email.trim(),
            first_name: firstName.trim(),
            last_name: lastName.trim() || null,
            phone: phone.trim() || null,
            address: address.trim() || null,
            city: city.trim() || null,
            postal_code: postalCode.trim() || null,
          })}
        >
          Kreiraj i pošalji poziv
        </Button>
        {create.isError ? <Text style={styles.error}>Kupca nije moguće kreirati.</Text> : null}
      </Card>

      <Card style={styles.filters}>
        <TextInput
          value={q}
          onChangeText={setQ}
          placeholder="Pretraži kupce"
          autoCapitalize="none"
          autoCorrect={false}
          style={styles.search}
        />
        <SelectSheet
          label="Status"
          value={status}
          onChange={setStatus}
          options={[
            { value: '', label: 'Svi statusi' },
            { value: 'active', label: 'Aktivni' },
            { value: 'pending', label: 'Na čekanju' },
            { value: 'blocked', label: 'Blokirani' },
          ]}
        />
      </Card>

      <Card style={styles.unlinkedCard}>
        <Text style={styles.section}>Nepovezane porudžbine</Text>
        <Text style={styles.meta}>Read-only kandidati bez registrovanog vlasnika. Povezivanje se radi iz detalja kupca.</Text>
        <TextInput
          value={unlinkedQ}
          onChangeText={setUnlinkedQ}
          placeholder="Pretraži broj porudžbine ili kupca"
          autoCapitalize="none"
          autoCorrect={false}
          style={styles.search}
        />
        {unlinkedQuery.isLoading ? <Text style={styles.meta}>Učitavanje nepovezanih porudžbina…</Text> : null}
        {unlinkedQuery.isError ? (
          <Button variant="secondary" onPress={() => void unlinkedQuery.refetch()}>Pokušaj ponovo</Button>
        ) : null}
        {unlinkedQuery.data?.length ? unlinkedQuery.data.map((buyer) => (
          <View key={buyer.id} style={styles.candidate}>
            <View style={styles.rowCopy}>
              <Text style={styles.rowTitle}>{buyer.number}</Text>
              <Text style={styles.meta}>{buyer.name ?? 'Kupac nije naveden'} · {buyer.status}</Text>
              <Text style={styles.meta}>{buyer.email ?? 'E-mail nije dostupan'} · {buyer.phone ?? 'Telefon nije dostupan'}</Text>
              <Text style={styles.meta}>{formatMoney(buyer.subtotal_rsd, 'RSD')} · {formatDate(buyer.created_at, true)}</Text>
            </View>
          </View>
        )) : (!unlinkedQuery.isLoading && !unlinkedQuery.isError ? <Text style={styles.meta}>Nema nepovezanih porudžbina za ovu pretragu.</Text> : null)}
      </Card>

      <Text style={styles.section}>Kupci</Text>
      {query.data.users.length ? query.data.users.map((user) => (
        <Pressable key={user.id} onPress={() => router.push(`/admin/customer-portal/${user.id}` as Href)}>
          <Card style={styles.row}>
            <View style={styles.rowCopy}>
              <Text style={styles.rowTitle}>{user.name}</Text>
              <Text style={styles.meta}>{user.email ?? user.username} · {user.status}</Text>
              <Text style={styles.meta}>{user.orders_count} porudžbina · {user.conversations_count} tema</Text>
            </View>
            <Text style={styles.open}>Otvori ›</Text>
          </Card>
        </Pressable>
      )) : <EmptyState title="Nema kupaca" message="Pretraga nije pronašla kupce za izabrane kriterijume." />}

      <Text style={styles.section}>Poslednje komunikacije</Text>
      {query.data.conversations.length ? query.data.conversations.map((conversation) => (
        <Pressable
          key={conversation.id}
          onPress={() => router.push(`/admin/customer-portal/conversations/${conversation.id}` as Href)}
        >
          <Card style={styles.row}>
            <View style={styles.rowCopy}>
              <Text style={styles.rowTitle}>{conversation.subject}</Text>
              <Text style={styles.meta}>{conversation.customer?.name ?? 'Kupac'} · {conversation.status_label}</Text>
            </View>
            {conversation.unread_staff_count > 0 ? <Text style={styles.unread}>{conversation.unread_staff_count} novo</Text> : null}
          </Card>
        </Pressable>
      )) : <EmptyState title="Nema komunikacija" message="Kada postoje portal razgovori, pojaviće se ovde." />}
    </Screen>
  );
}

function Stat({ label, value }: { label: string; value: number }) {
  const styles = useThemedStyles(createStyles);
  return <Card style={styles.stat}><Text style={styles.statValue}>{value}</Text><Text style={styles.meta}>{label}</Text></Card>;
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    stats: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    stat: { width: '48%', gap: spacing.xs },
    statValue: { ...typography.h2, color: theme.ink },
    form: { gap: spacing.md },
    filters: { gap: spacing.md },
    unlinkedCard: { gap: spacing.md },
    candidate: { flexDirection: 'row', gap: spacing.md, paddingVertical: spacing.sm, borderTopWidth: 1, borderTopColor: theme.line },
    section: { ...typography.h2, color: theme.ink },
    search: { ...typography.body, color: theme.ink, minHeight: 48 },
    row: { flexDirection: 'row', alignItems: 'center', gap: spacing.md },
    rowCopy: { flex: 1, gap: spacing.xs },
    rowTitle: { ...typography.h3, color: theme.ink },
    meta: { ...typography.small, color: theme.muted },
    open: { ...typography.label, color: theme.primary },
    unread: { ...typography.label, color: theme.danger },
    error: { ...typography.small, color: theme.danger },
  });
}
