import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { PageHeader } from '@/components/layout/page-header';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { Pill } from '@/components/ui/pill';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { api } from '@/lib/api/endpoints';
import { formatDate } from '@/lib/formatters';
import type { BusinessNotification } from '@/types/api';

export default function NotificationsScreen() {
  const client = useQueryClient();
  const { bootstrap, refreshBootstrap, hasFeature } = useAuth();
  const allowed = hasFeature('notifications');
  const query = useQuery({ queryKey: ['notifications'], queryFn: () => api.notifications.list(), enabled: allowed });
  const read = useMutation({ mutationFn: api.notifications.read, onSuccess: async () => { await client.invalidateQueries({ queryKey: ['notifications'] }); await refreshBootstrap(); } });
  const readAll = useMutation({ mutationFn: api.notifications.readAll, onSuccess: async () => { await client.invalidateQueries({ queryKey: ['notifications'] }); await refreshBootstrap(); } });

  if (!allowed) return <UnavailableState title="Obaveštenja nisu dostupna" />;
  if (query.isLoading) return <LoadingState label="Učitavanje obaveštenja…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const open = async (item: BusinessNotification) => {
    if (!item.read) await read.mutateAsync(item.id);
    if (item.route?.startsWith('/orders/')) router.push({ pathname: '/order/[id]', params: { id: item.route.split('/').pop() ?? '' } });
  };

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <FlatList
      data={query.data?.data ?? []}
      keyExtractor={(item) => item.id}
      renderItem={({ item }) => (
        <Pressable onPress={() => void open(item)}>
          <Card style={[styles.card, !item.read && styles.unread]}>
            <View style={[styles.dot, item.read && styles.dotRead]} />
            <View style={styles.copyWrap}>
              <View style={styles.titleRow}><Text style={styles.title}>{item.title}</Text><Pill tone={item.severity === 'danger' ? 'danger' : item.severity === 'warning' ? 'warning' : 'info'}>{item.read ? 'Pročitano' : 'Novo'}</Pill></View>
              {item.message ? <Text style={styles.message}>{item.message}</Text> : null}
              <Text style={styles.date}>{formatDate(item.created_at, true)}</Text>
            </View>
          </Card>
        </Pressable>
      )}
      ItemSeparatorComponent={() => <View style={{ height: spacing.md }} />}
      refreshControl={<RefreshControl refreshing={query.isRefetching} onRefresh={() => void query.refetch()} tintColor={colors.primary} />}
      contentContainerStyle={styles.content}
      ListHeaderComponent={<View style={styles.header}><PageHeader title="Obaveštenja" eyebrow="Inbox" name={bootstrap?.user.name} /><Button variant="secondary" onPress={() => readAll.mutate()} loading={readAll.isPending}>Označi sve kao pročitano</Button></View>}
      ListEmptyComponent={<EmptyState title="Inbox je prazan" message="Nova poslovna obaveštenja pojaviće se ovde." />}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  content: { paddingHorizontal: spacing.lg, paddingBottom: 120, backgroundColor: colors.background },
  header: { gap: spacing.md, marginBottom: spacing.lg },
  card: { flexDirection: 'row', gap: spacing.md, shadowOpacity: 0, elevation: 0 },
  unread: { borderColor: colors.primary, backgroundColor: '#FCFAFF' },
  dot: { width: 9, height: 9, marginTop: 7, borderRadius: radii.pill, backgroundColor: colors.primary },
  dotRead: { backgroundColor: colors.line },
  copyWrap: { flex: 1, gap: spacing.sm },
  titleRow: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.sm },
  title: { ...typography.h3, color: colors.ink, flex: 1 },
  message: { ...typography.body, color: colors.muted },
  date: { ...typography.small, color: colors.muted }
});
