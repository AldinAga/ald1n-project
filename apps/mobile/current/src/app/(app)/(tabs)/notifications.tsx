import { memo, useCallback, useMemo, useRef } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { PageHeader } from '@/components/layout/page-header';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Glyph } from '@/components/ui/glyph';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { Pill } from '@/components/ui/pill';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';
import { useAuth, useNotificationUnreadActions } from '@/features/auth/auth-provider';
import { resolveBusinessNotificationNavigation } from '@/features/notifications/notification-routing';
import { api } from '@/lib/api/endpoints';
import { formatDate } from '@/lib/formatters';
import type { BusinessNotification, PaginatedResponse } from '@/types/api';

type NotificationRowProps = {
  item: BusinessNotification;
  onOpen: (item: BusinessNotification) => void;
  styles: ReturnType<typeof createStyles>;
  theme: AppColors;
};

const NotificationRow = memo(function NotificationRow({ item, onOpen, styles, theme }: NotificationRowProps) {
  return (
    <Pressable
      accessibilityRole="button"
      accessibilityLabel={`${item.read ? 'Pročitano' : 'Novo'} obaveštenje: ${item.title}`}
      onPress={() => onOpen(item)}
      style={({ pressed }) => [styles.row, !item.read && styles.unread, pressed && styles.pressed]}
    >
      <View style={[styles.rail, item.read && styles.railRead]} />
      <View style={[styles.iconTile, item.read && styles.iconTileRead]}>
        <Glyph name="bell" size={21} color={item.read ? theme.muted : theme.primary} />
      </View>
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
      <Glyph name="arrow" size={20} color={theme.muted} />
    </Pressable>
  );
});

const notificationKeyExtractor = (item: BusinessNotification) => item.id;
const NotificationListSeparator = () => <View style={{ height: spacing.sm }} />;

export default function NotificationsScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { bootstrap, hasFeature } = useAuth();
  const { setUnread, decrementUnread } = useNotificationUnreadActions();
  const readInFlightIds = useRef<Set<string>> (new Set());
  const allowed = hasFeature('notifications');
  const query = useQuery({ queryKey: ['notifications'], queryFn: () => api.notifications.list(), enabled: allowed });
  const notifications = query.data?.data ?? [];
  const unreadVisible = notifications.filter((item) => !item.read).length;

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
              read_at: updated.read_at ?? item.read_at ?? new Date().toISOString(),
            };
          }),
        } : current,
      );

      if (changedUnread) decrementUnread();
    },
    onError: (error) => {
      feedback.notify({
        tone: 'danger',
        title: 'Obaveštenje nije označeno kao pročitano',
        message: error instanceof Error ? error.message : 'Pokušaj ponovo.',
      });
    },
  });

  const readMutateRef = useRef(read.mutate);
  readMutateRef.current = read.mutate;
  const feedbackRef = useRef(feedback);
  feedbackRef.current = feedback;

  const readAll = useMutation({
    mutationFn: api.notifications.readAll,
    onSuccess: (result) => {
      const readAt = new Date().toISOString();

      client.setQueryData<PaginatedResponse<BusinessNotification>> (
        ['notifications'],
        (current) => current ? {
          ...current,
          data: current.data.map((item) => item.read ? item : {
            ...item,
            read: true,
            read_at: item.read_at ?? readAt,
          }),
        } : current,
      );

      setUnread(result.unread);
    },
    onError: (error) => {
      feedback.notify({
        tone: 'danger',
        title: 'Obaveštenja nisu ažurirana',
        message: error instanceof Error ? error.message : 'Pokušaj ponovo.',
      });
    },
  });

  const markReadInBackground = useCallback((item: BusinessNotification) => {
    if (item.read || readInFlightIds.current.has(item.id)) return;

    readInFlightIds.current.add(item.id);
    readMutateRef.current(item.id, {
      onSettled: () => {
        readInFlightIds.current.delete(item.id);
      },
    });
  }, []);

  const open = useCallback((item: BusinessNotification) => {
    const destination = resolveBusinessNotificationNavigation(item);
    markReadInBackground(item);

    if (destination.kind === 'order') {
      router.push({ pathname: '/order/[id]', params: { id: String(destination.id) } });
      return;
    }

    if (destination.kind === 'after_sales_case') {
      router.push({ pathname: '/after-sales/[id]', params: { id: String(destination.id) } });
      return;
    }

    if (destination.kind === 'product') {
      router.push({ pathname: '/product/[slug]', params: { slug: destination.slug } });
      return;
    }

    if (destination.reason === 'stale_assignment') {
      feedbackRef.current.notify({
        tone: 'warning',
        title: 'Porudžbina više nije dodeljena',
        message: 'Ova porudžbina je u međuvremenu dodeljena drugom odgovornom licu.',
      });
    }
  }, [markReadInBackground]);

  const renderNotification = useCallback(
    ({ item }: { item: BusinessNotification }) => (
      <NotificationRow item={item} onOpen={open} styles={styles} theme={themeColors} />
    ),
    [open, styles, themeColors],
  );

  if (!allowed) return <UnavailableState title="Obaveštenja nisu dostupna" />;
  if (query.isLoading) return <LoadingState label="Učitavanje obaveštenja…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <FlatList
        data={notifications}
        keyExtractor={notificationKeyExtractor}
        renderItem={renderNotification}
        ItemSeparatorComponent={NotificationListSeparator}
        initialNumToRender={8}
        maxToRenderPerBatch={8}
        windowSize={5}
        updateCellsBatchingPeriod={50}
        refreshControl={(
          <RefreshControl
            refreshing={query.isRefetching}
            onRefresh={() => void query.refetch()}
            tintColor={themeColors.primary}
          />
        )}
        contentContainerStyle={styles.content}
        ListHeaderComponent={(
          <View style={styles.header}>
            {/* MOBILE_BUILD16_NOTIFICATIONS_REDESIGN_BATCH130 */}
            <PageHeader title="Obaveštenja" eyebrow="Inbox" name={bootstrap?.user.name} />
            <View style={styles.summary}>
              <View style={styles.summaryIcon}>
                <Glyph name="bell" size={22} color={themeColors.primary} />
              </View>
              <View style={styles.summaryCopy}>
                <Text style={styles.summaryLabel}>Poslovni inbox</Text>
                <Text style={styles.summaryValue}>{unreadVisible} novih u prikazu</Text>
              </View>
              <Button variant="secondary" onPress={() => readAll.mutate()} loading={readAll.isPending}>
                Pročitaj sve
              </Button>
            </View>
          </View>
        )}
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
    summary: {
      minHeight: 72,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md,
      padding: spacing.md,
      borderWidth: 1,
      borderColor: theme.line,
      borderLeftWidth: 4,
      borderLeftColor: theme.primary,
      borderRadius: radii.lg,
      backgroundColor: theme.surface,
    },
    summaryIcon: {
      width: 42,
      height: 42,
      alignItems: 'center',
      justifyContent: 'center',
      borderRadius: radii.md,
      backgroundColor: theme.primarySoft,
    },
    summaryCopy: { flex: 1, minWidth: 0 },
    summaryLabel: { ...typography.small, color: theme.muted },
    summaryValue: { ...typography.h3, color: theme.ink, marginTop: 2 },
    row: {
      position: 'relative',
      overflow: 'hidden',
      minHeight: 92,
      flexDirection: 'row',
      alignItems: 'flex-start',
      gap: spacing.md,
      padding: spacing.md,
      paddingLeft: spacing.lg,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surface,
    },
    unread: { borderColor: theme.primary, backgroundColor: theme.surfaceContainer },
    pressed: { transform: [{ scale: 0.992 }], opacity: 0.9 },
    rail: { position: 'absolute', left: 0, top: 0, bottom: 0, width: 4, backgroundColor: theme.primary },
    railRead: { backgroundColor: theme.line },
    iconTile: {
      width: 40,
      height: 40,
      alignItems: 'center',
      justifyContent: 'center',
      borderRadius: radii.md,
      backgroundColor: theme.primarySoft,
    },
    iconTileRead: { backgroundColor: theme.surfaceContainer },
    copyWrap: { flex: 1, gap: spacing.xs, minWidth: 0 },
    titleRow: { flexDirection: 'row', alignItems: 'flex-start', justifyContent: 'space-between', gap: spacing.sm },
    title: { ...typography.h3, color: theme.ink, flex: 1 },
    message: { ...typography.body, color: theme.muted },
    date: { ...typography.small, color: theme.muted, fontVariant: ['tabular-nums'] },
  });
}
