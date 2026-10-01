import { useMemo } from 'react';
import { useInfiniteQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { FlatList, Pressable, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { PageHeader } from '@/components/layout/page-header';
import { OrderCard } from '@/components/orders/order-card';
import { Button } from '@/components/ui/button';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { api } from '@/lib/api/endpoints';
import { useAppTheme } from '@/theme/app-theme';

export default function AssignedOrdersScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(() => createStyles(themeColors), [themeColors]);
  const { bootstrap, can } = useAuth();
  const allowed = can('orders.manage');

  const query = useInfiniteQuery({
    queryKey: ['assigned-orders'],
    queryFn: ({ pageParam }) => api.orders.assignedList(pageParam),
    initialPageParam: 1,
    getNextPageParam: (lastPage) => {
      const current = Number(lastPage.meta?.current_page ?? 1);
      const last = Number(lastPage.meta?.last_page ?? current);
      return current < last ? current + 1 : undefined;
    },
    enabled: allowed,
  });

  if (!allowed) return <UnavailableState title="Dodeljene porudžbine nisu dostupne" />;
  if (query.isLoading) return <LoadingState label="Učitavanje dodeljenih porudžbina…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const orders = query.data?.pages.flatMap((page) => page.data) ?? [];

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <FlatList
        data={orders}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => (
          <OrderCard
            order={item}
            onPress={() => router.push({ pathname: '/assigned-orders/[id]', params: { id: String(item.id) } })}
          />
        )}
        ItemSeparatorComponent={() => <View style={{ height: spacing.md }} />}
        refreshControl={(
          <RefreshControl
            refreshing={query.isRefetching && !query.isFetchingNextPage}
            onRefresh={() => void query.refetch()}
            tintColor={themeColors.primary}
          />
        )}
        contentContainerStyle={styles.content}
        ListHeaderComponent={(
          <View style={styles.header}>
            <Pressable accessibilityRole="button" onPress={() => router.back()}>
              <Text style={styles.back}>‹ Nazad na porudžbine</Text>
            </Pressable>
            <PageHeader title="Dodeljene meni" eyebrow="Operativni inbox" name={bootstrap?.user.name} />
            <Text style={styles.copy}>Porudžbine za koje si trenutno odgovorno lice.</Text>
          </View>
        )}
        ListEmptyComponent={(
          <EmptyState title="Nema dodeljenih porudžbina" message="Nove porudžbine dodeljene tebi pojaviće se ovde." />
        )}
        ListFooterComponent={query.hasNextPage ? (
          <View style={styles.footer}>
            <Button
              variant="secondary"
              onPress={() => void query.fetchNextPage()}
              loading={query.isFetchingNextPage}
            >
              Učitaj još
            </Button>
          </View>
        ) : null}
      />
    </SafeAreaView>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    safe: { flex: 1, backgroundColor: theme.background },
    content: { paddingHorizontal: spacing.lg, paddingBottom: 120, backgroundColor: theme.background },
    header: { gap: spacing.sm, marginBottom: spacing.lg },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    copy: { ...typography.body, color: theme.muted },
    footer: { paddingTop: spacing.lg },
  });
}
