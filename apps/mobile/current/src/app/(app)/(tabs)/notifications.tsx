import { memo, useCallback, useMemo, useRef } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { PageHeader } from '@/components/layout/page-header';
import { Button } from '@/components/ui/button';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Card } from '@/components/ui/card';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { Pill } from '@/components/ui/pill';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';
import { useAuth } from '@/features/auth/auth-provider';
import { resolveBusinessNotificationNavigation } from '@/features/notifications/notification-routing';
import { api } from '@/lib/api/endpoints';
import { formatDate } from '@/lib/formatters';
import type { BusinessNotification, PaginatedResponse } from '@/types/api';

type NotificationRowProps = {
  item: BusinessNotification;
  onOpen: (item: BusinessNotification) => void;
  styles: ReturnType<typeof createStyles>;
};

const NotificationRow = memo(function NotificationRow({ item, onOpen, styles }: NotificationRowProps) {
  return (
    <Pressable onPress={() => onOpen(item)}>
      <Card style={[styles.card, !item.read && styles.unread]}>
        <View style={[styles.dot, item.read && styles.dotRead]} />
        <View style={styles.copyWrap}>
          <View style={styles.titleRow}>
            <Text style={styles.title}>{item.title}</Text>
            <Pill tone={item.severity === 'danger' ? 'danger' : item.severity === 'warning' ? 'warning' : 'info'}>
              {item.read ? 'Pročitano' : 'Novo'}
            </Pill>
          </View>
          {item.message ? <Text style={styles.message}>{item.message}</Text> : null}
          <Text style={styles.date}>{formatDate(item.created_at, true)}</Text>
        </View>
      </Card>
    </Pressable>
  );
});

const notificationKeyExtractor = (item: BusinessNotification) => item.id;
const NotificationListSeparator = () => <View style={{ height: spacing.md }} />;

export default function NotificationsScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(
    () => createStyles(themeColors),
    [themeColors],
  );
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { bootstrap, setNotificationUnreadCount, hasFeature } = useAuth();
  const readInFlightIds = useRef<Set<string>> ( new Set());
  const allowed = hasFeature('notifications');
  const query = useQuery({ queryKey: ['notifications'], queryFn: () => api.notifications.list(), enabled: allowed });
  const read = useMutation({
    mutationFn: api.notifications.read,
    onSuccess: (updated, notificationId) => {
      let changedUnread = false;

      client.setQueryData<PaginatedResponse<BusinessNotification>> (
        ['notifications'],
        (current) => current ? {
          ...current,
          data: current.data.map((item) => {
            if (item.id !== notificationId) return item;

            changedUnread = !item.read;
            return {
              ...item,
              ...updated,
              read: true,
              read_at: updated.read_at ?? item.read_at ?? new Date().toISOString()
            };
          })
        } : current
      );

      if (changedUnread) {
        const unread = bootstrap?.notification_counts.unread ?? 0;
        setNotificationUnreadCount(Math.max(0, unread - 1));
      }
    },
    onError: (error) => {
      feedback.notify({
        tone: 'danger',
        title: 'Obaveštenje nije označeno kao pročitano',
        message: error instanceof Error ? error.message : 'Pokušaj ponovo.'
      });
    }
  });
  const readMutateRef = useRef(read.mutate);
  readMutateRef.current = read.mutate;
  const feedbackRef = useRef(feedback);
  feedbackRef.current = feedback;

  const readAll = useMutation({
    mutationFn: api.notifications.readAll,
    onSuccess: (result) => {
      const readAt = new Date().toISOString();

      client.setQueryData<PaginatedResponse<BusinessNotification>>(
        ['notifications'],
        (current) => current ? {
          ...current,
          data: current.data.map((item) => item.read ? item : {
            ...item,
            read: true,
            read_at: item.read_at ?? readAt
          })
        } : current
      );

      setNotificationUnreadCount(result.unread);
    },
    onError: (error) => {
      feedback.notify({
        tone: 'danger',
        title: 'Obaveštenja nisu ažurirana',
        message: error instanceof Error ? error.message : 'Pokušaj ponovo.'
      });
    }
  });

  const markReadInBackground = useCallback((item: BusinessNotification) => {
    if (item.read || readInFlightIds.current.has(item.id)) return;

    readInFlightIds.current.add(item.id);
    readMutateRef.current(item.id, {
      onSettled: () => {
        readInFlightIds.current.delete(item.id);
      }
    });
  }, []);

  const open = useCallback((item: BusinessNotification) => {
    const destination = resolveBusinessNotificationNavigation(item);
    markReadInBackground(item);

    if (destination.kind === 'order') {
      router.push({
        pathname: '/order/[id]',
        params: { id: String(destination.id) }
      });
      return;
    }

    if (destination.kind === 'after_sales_case') {
      router.push({
        pathname: '/after-sales/[id]',
        params: { id: String(destination.id) }
      });
      return;
    }

    if (destination.kind === 'product') {
      router.push({
        pathname: '/product/[slug]',
        params: { slug: destination.slug }
      });
      return;
    }
    if (destination.reason === 'stale_assignment') {
      feedbackRef.current.notify({
        tone: 'warning',
        title: 'Porudžbina više nije dodeljena',
        message: 'Ova porudžbina je u međuvremenu dodeljena drugom odgovornom licu.'
      });
    }
  }, [markReadInBackground]);

  const renderNotification = useCallback(
    ({ item }: { item: BusinessNotification }) => (
      <NotificationRow item={item} onOpen={open} styles={styles} />
    ),
    [open, styles],
  );


  if (!allowed) return <UnavailableState title="Obaveštenja nisu dostupna" />;
  if (query.isLoading) return <LoadingState label="Učitavanje obaveštenja…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <FlatList
      data={query.data?.data ?? []}
      keyExtractor={notificationKeyExtractor}
      renderItem={renderNotification}
      ItemSeparatorComponent={NotificationListSeparator}
      initialNumToRender={8}
      maxToRenderPerBatch={8}
      windowSize={5}
      updateCellsBatchingPeriod={50}
      refreshControl={<RefreshControl refreshing={query.isRefetching} onRefresh={() => void query.refetch()} tintColor={themeColors.primary} />}
      contentContainerStyle={styles.content}
      ListHeaderComponent={<View style={styles.header}><PageHeader title="Obaveštenja" eyebrow="Inbox" name={bootstrap?.user.name} /><Button variant="secondary" onPress={() => readAll.mutate()} loading={readAll.isPending}>Označi sve kao pročitano</Button></View>}
      ListEmptyComponent={<EmptyState title="Inbox je prazan" message="Nova poslovna obaveštenja pojaviće se ovde." />}
      />
    </SafeAreaView>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
  safe: { flex: 1, backgroundColor: theme.background },
  content: { paddingHorizontal: spacing.lg, paddingBottom: 120, backgroundColor: theme.background },
  header: { gap: spacing.md, marginBottom: spacing.lg },
  card: { flexDirection: 'row', gap: spacing.md, shadowOpacity: 0, elevation: 0 },
  unread: { borderColor: theme.primary, backgroundColor: theme.surfaceContainer },
  dot: { width: 9, height: 9, marginTop: 7, borderRadius: radii.pill, backgroundColor: theme.primary },
  dotRead: { backgroundColor: theme.line },
  copyWrap: { flex: 1, gap: spacing.sm },
  titleRow: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.sm },
  title: { ...typography.h3, color: theme.ink, flex: 1 },
  message: { ...typography.body, color: theme.muted },
  date: { ...typography.small, color: theme.muted }
  });
}
