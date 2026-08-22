import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { PageHeader } from '@/components/layout/page-header';
import { Button } from '@/components/ui/button';
import { OrderCard } from '@/components/orders/order-card';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { useAppTheme } from '@/theme/app-theme';
import { useAuth } from '@/features/auth/auth-provider';
import { api } from '@/lib/api/endpoints';

export default function OrdersScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useMemo(
    () => createStyles(themeColors),
    [themeColors],
  );
  const { bootstrap, hasFeature, can } = useAuth();
  const allowed = hasFeature('orders');
  const assignedOrdersAllowed = can('orders.manage');
  const query = useQuery({ queryKey: ['orders'], queryFn: () => api.orders.list(), enabled: allowed });
  if (!allowed) return <UnavailableState title="Porudžbine nisu dostupne" />;
  if (query.isLoading) return <LoadingState label="Učitavanje porudžbina…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <FlatList
      data={query.data?.data ?? []}
      keyExtractor={(item) => String(item.id)}
      renderItem={({ item }) => <OrderCard order={item} onPress={() => router.push({ pathname: '/order/[id]', params: { id: String(item.id) } })} />}
      ItemSeparatorComponent={() => <View style={{ height: spacing.md }} />}
      refreshControl={<RefreshControl refreshing={query.isRefetching} onRefresh={() => void query.refetch()} tintColor={themeColors.primary} />}
      contentContainerStyle={styles.content}
      ListHeaderComponent={(
        <View style={styles.header}>
          {/* MOBILE_V0_9_ORDERS_MY_AND_ASSIGNED_ONLY_BATCH5C */}
          <PageHeader title="Porudžbine" eyebrow="Moje porudžbine" name={bootstrap?.user.name} />
          <Text style={styles.copy}>Moje porudžbine: pregled statusa, plaćanja i stavki.</Text>



          {assignedOrdersAllowed ? (
            <Button variant="secondary" onPress={() => router.push('/assigned-orders')}>
              Dodeljene porudžbine
            </Button>
          ) : null}
        </View>
      )}
      ListEmptyComponent={<EmptyState title="Nema porudžbina" message="Kada napraviš porudžbinu, njen status i detalji pojaviće se ovde." />}
      />
    </SafeAreaView>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
  safe: { flex: 1, backgroundColor: theme.background },
  content: { paddingHorizontal: spacing.lg, paddingBottom: 120, backgroundColor: theme.background },
  header: { gap: spacing.sm, marginBottom: spacing.lg },
  copy: { ...typography.body, color: theme.muted }
  });
}
