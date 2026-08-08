import { useQuery } from '@tanstack/react-query';
import { router } from 'expo-router';
import { useState } from 'react';
import { FlatList, RefreshControl, StyleSheet, Text, TextInput, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { PageHeader } from '@/components/layout/page-header';
import { ProductCard } from '@/components/catalog/product-card';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { Glyph } from '@/components/ui/glyph';
import { Pill } from '@/components/ui/pill';
import { colors, radii, spacing, typography } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { useCart } from '@/features/cart/cart-provider';
import { api } from '@/lib/api/endpoints';

export default function CatalogScreen() {
  const { bootstrap, hasFeature } = useAuth();
  const { itemCount } = useCart();
  const allowed = hasFeature('catalog');
  const [draft, setDraft] = useState('');
  const [search, setSearch] = useState('');
  const [stock, setStock] = useState<string | undefined>();
  const filters = useQuery({
    queryKey: ['catalog-filters'],
    queryFn: api.catalog.filters,
    enabled: allowed,
    staleTime: 10 * 60_000
  });
  const query = useQuery({
    queryKey: ['products', { search, stock }],
    queryFn: () => api.catalog.products({ q: search, stock, sort: 'updated', per_page: 30 }),
    enabled: allowed
  });
  const stockOptions = [{ value: undefined, label: 'Svi' }, ...(filters.data?.stock_filters ?? [])];

  if (!allowed) return <UnavailableState title="Katalog nije dostupan" />;
  if (query.isLoading) return <LoadingState label="Učitavanje kataloga…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  return (
    <SafeAreaView style={styles.safe} edges={['top']}>
      <FlatList
      data={query.data?.data ?? []}
      keyExtractor={(item) => String(item.id)}
      renderItem={({ item }) => <ProductCard product={item} onPress={() => router.push({ pathname: '/product/[slug]', params: { slug: item.slug } })} />}
      ItemSeparatorComponent={() => <View style={{ height: spacing.md }} />}
      refreshControl={<RefreshControl refreshing={query.isRefetching} onRefresh={() => void query.refetch()} tintColor={colors.primary} />}
      showsVerticalScrollIndicator={false}
      contentContainerStyle={styles.content}
      ListHeaderComponent={(
        <View style={styles.headerWrap}>
          <View style={styles.headerLine}><View style={{ flex: 1 }}><PageHeader title="Katalog" eyebrow="Proizvodi" name={bootstrap?.user.name} /></View>{hasFeature('order_create') ? <Text onPress={() => router.push('/cart')} style={styles.cartLink}>Korpa{itemCount ? ` (${itemCount})` : ''}</Text> : null}</View>
          <View style={styles.searchWrap}>
            <Glyph name="search" size={22} color={colors.muted} />
            <TextInput
              value={draft}
              onChangeText={setDraft}
              onSubmitEditing={() => setSearch(draft.trim())}
              placeholder="Naziv, SKU ili model…"
              placeholderTextColor={colors.muted}
              returnKeyType="search"
              style={styles.search}
            />
          </View>
          <View style={styles.filters}>
            {stockOptions.map((item) => (
              <Text key={item.label} onPress={() => setStock(item.value)} style={[styles.filter, stock === item.value && styles.filterActive]}>{item.label}</Text>
            ))}
          </View>
          <View style={styles.resultRow}><Text style={styles.resultTitle}>{query.data?.meta?.total ?? query.data?.data.length ?? 0} proizvoda</Text>{search ? <Pill tone="primary">„{search}”</Pill> : null}</View>
        </View>
      )}
      ListEmptyComponent={<EmptyState title="Nema proizvoda" message="Promeni pretragu ili filter lagera." />}
      />
    </SafeAreaView>
  );
}

const styles = StyleSheet.create({
  safe: { flex: 1, backgroundColor: colors.background },
  content: { paddingHorizontal: spacing.lg, paddingBottom: 120, backgroundColor: colors.background },
  headerWrap: { gap: spacing.lg, marginBottom: spacing.lg },
  headerLine: { flexDirection: 'row', alignItems: 'flex-start', gap: spacing.md },
  cartLink: { ...typography.label, color: colors.primary, paddingVertical: spacing.sm },
  searchWrap: { minHeight: 54, flexDirection: 'row', alignItems: 'center', gap: spacing.md, paddingHorizontal: spacing.lg, borderWidth: 1, borderColor: colors.line, borderRadius: radii.lg, backgroundColor: colors.surface },
  search: { flex: 1, color: colors.ink, fontSize: 16 },
  filters: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
  filter: { ...typography.small, color: colors.muted, paddingHorizontal: spacing.md, paddingVertical: 8, borderRadius: radii.pill, backgroundColor: colors.surface, borderWidth: 1, borderColor: colors.line, overflow: 'hidden' },
  filterActive: { color: colors.white, backgroundColor: colors.primary, borderColor: colors.primary },
  resultRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', gap: spacing.md },
  resultTitle: { ...typography.h2, color: colors.ink }
});
