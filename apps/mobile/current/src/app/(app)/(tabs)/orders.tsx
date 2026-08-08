import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { FlatList, RefreshControl, StyleSheet, Text, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { PageHeader } from '@/components/layout/page-header';
import { OrderCard } from '@/components/orders/order-card';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { colors, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { api } from '@/lib/api/endpoints';

export default function OrdersScreen() {
  const { bootstrap, hasFeature } = useAuth();
  const allowed = hasFeature('orders');
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
      refreshControl={<RefreshControl refreshing={query.isRefetching} onRefresh={() => void query.refetch()} tintColor={colors.primary} />}
      contentContainerStyle={styles.content}
      ListHeaderComponent={<View style={styles.header}><PageHeader title="Porudžbine" eyebrow="Moje aktivnosti" name={bootstrap?.user.name} /><Text style={styles.copy}>Pregled statusa, plaćanja i stavki porudžbine.</Text></View>}
      ListEmptyComponent={<EmptyState title="Nema porudžbina" message="Kada napraviš porudžbinu, njen status i detalji pojaviće se ovde." />}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  content: { paddingHorizontal: spacing.lg, paddingBottom: 120, backgroundColor: colors.background },
  header: { gap: spacing.sm, marginBottom: spacing.lg },
  copy: { ...typography.body, color: colors.muted }
});
