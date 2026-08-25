import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useMemo } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { EmptyState, ErrorState, LoadingState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { api } from '@/lib/api/endpoints';
import { formatDate } from '@/lib/formatters';
import { useAppTheme } from '@/theme/app-theme';
import type { AccountApiSession, AccountWebSession } from '@/types/api';

type RevokeInput = { kind: 'api' | 'web'; id: number; label: string };

export default function SessionsScreen() {
  const { colors } = useAppTheme();
  const styles = useMemo(() => createStyles(colors), [colors]);
  const feedback = useAppFeedback();
  const queryClient = useQueryClient();
  const { requireReauthentication } = useAuth();
  const query = useQuery({ queryKey: ['account', 'sessions'], queryFn: api.account.sessions });

  const revoke = useMutation({
    mutationFn: (input: RevokeInput) => api.account.revokeSession(input.kind, input.id),
    onSuccess: async (response) => {
      feedback.notify({ tone: 'success', title: 'Prijava opozvana', message: response.message });
      if (response.data.reauthenticate) {
        await requireReauthentication();
        return;
      }
      await queryClient.invalidateQueries({ queryKey: ['account', 'sessions'] });
    }
  });
  const revokeOthers = useMutation({
    mutationFn: api.account.revokeOtherSessions,
    onSuccess: async (response) => {
      feedback.notify({ tone: 'success', title: 'Druge prijave opozvane', message: response.message });
      await queryClient.invalidateQueries({ queryKey: ['account', 'sessions'] });
    }
  });

  if (query.isLoading) return <LoadingState label="Učitavanje aktivnih prijava…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const apiSessions = query.data?.api_sessions ?? [];
  const webSessions = query.data?.web_sessions ?? [];
  const total = apiSessions.length + webSessions.length;

  const confirmRevoke = (input: RevokeInput, current = false) => {
    void (async () => {
      const confirmed = await feedback.confirm({
        tone: 'danger',
        title: current ? 'Opoziv trenutne prijave' : 'Opoziv prijave',
        message: current ? 'Bićeš odmah odjavljen sa ovog uređaja.' : `Opozvati prijavu „${input.label}”?`,
        confirmLabel: 'Opozovi',
        cancelLabel: 'Odustani'
      });
      if (confirmed) revoke.mutate(input);
    })();
  };

  const confirmOthers = () => {
    void (async () => {
      const confirmed = await feedback.confirm({
        tone: 'danger',
        title: 'Opozovi sve druge prijave',
        message: 'Trenutna mobilna prijava ostaje aktivna. Ostali API tokeni i aktivne web prijave biće opozvani.',
        confirmLabel: 'Opozovi druge',
        cancelLabel: 'Odustani'
      });
      if (confirmed) revokeOthers.mutate();
    })();
  };

  return <Screen>
    <Pressable onPress={() => router.back()}><Text style={styles.back}>‹ Nazad na nalog</Text></Pressable>
    <Text style={styles.title}>Aktivne prijave</Text>
    <Text style={styles.copy}>Jedan pregled mobilnih API tokena i aktivnih CMS web sesija. Sirovi session hash i user-agent se ne izlažu.</Text>

    {query.data?.capabilities.revoke_others ? <Button variant="danger" onPress={confirmOthers} loading={revokeOthers.isPending}>Opozovi sve druge prijave</Button> : null}

    {total === 0 ? <EmptyState title="Nema aktivnih prijava" message="Nijedna aktivna sesija nije pronađena." /> : null}

    {apiSessions.length ? <View style={styles.section}>
      <Text style={styles.sectionTitle}>Mobilne / API prijave</Text>
      {apiSessions.map((session) => <ApiSessionCard key={session.id} session={session} styles={styles} onRevoke={() => confirmRevoke({ kind: 'api', id: session.id, label: session.device_name }, session.is_current)} loading={revoke.isPending} />)}
    </View> : null}

    {webSessions.length ? <View style={styles.section}>
      <Text style={styles.sectionTitle}>Web prijave</Text>
      {webSessions.map((session) => <WebSessionCard key={session.id} session={session} styles={styles} onRevoke={() => confirmRevoke({ kind: 'web', id: session.id, label: session.device_label })} loading={revoke.isPending} />)}
    </View> : null}
  </Screen>;
}

function ApiSessionCard({ session, styles, onRevoke, loading }: { session: AccountApiSession; styles: ReturnType<typeof createStyles>; onRevoke: () => void; loading: boolean }) {
  return <Card style={styles.card}>
    <Text style={styles.cardTitle}>{session.device_name}{session.is_current ? ' · Ovaj uređaj' : ''}</Text>
    <Text style={styles.meta}>{session.platform ?? 'API'}{session.app_version ? ` · v${session.app_version}` : ''}</Text>
    <Text style={styles.meta}>Poslednja aktivnost: {formatDate(session.last_seen_at, true)}</Text>
    <Text style={styles.meta}>Ističe: {formatDate(session.expires_at, true)}</Text>
    <Button variant="danger" onPress={onRevoke} loading={loading}>Opozovi prijavu</Button>
  </Card>;
}

function WebSessionCard({ session, styles, onRevoke, loading }: { session: AccountWebSession; styles: ReturnType<typeof createStyles>; onRevoke: () => void; loading: boolean }) {
  return <Card style={styles.card}>
    <Text style={styles.cardTitle}>{session.device_label}</Text>
    <Text style={styles.meta}>IP: {session.ip_address ?? '—'} · {session.remembered ? 'Zapamćena prijava' : 'Standardna prijava'}</Text>
    <Text style={styles.meta}>Poslednja aktivnost: {formatDate(session.last_seen_at, true)}</Text>
    <Button variant="danger" onPress={onRevoke} loading={loading}>Opozovi web prijavu</Button>
  </Card>;
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    title: { ...typography.h1, color: theme.ink },
    copy: { ...typography.body, color: theme.muted },
    section: { gap: spacing.md },
    sectionTitle: { ...typography.h2, color: theme.ink },
    card: { gap: spacing.sm },
    cardTitle: { ...typography.h3, color: theme.ink },
    meta: { ...typography.small, color: theme.muted }
  });
}