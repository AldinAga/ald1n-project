import { useQuery } from '@tanstack/react-query';
import { router, useFocusEffect } from 'expo-router';
import { memo, useCallback, useEffect, useMemo, useState } from 'react';
import {
  FlatList,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  TextInput,
  View,
  useWindowDimensions,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ProductCard } from '@/components/catalog/product-card';
import { PageHeader } from '@/components/layout/page-header';
import { Glyph } from '@/components/ui/glyph';
import { SelectSheet } from '@/components/ui/select-sheet';
import { EmptyState, ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { radii, spacing, typography, type AppColors } from '@/constants/theme';
import { useAuth } from '@/features/auth/auth-provider';
import { useCart } from '@/features/cart/cart-provider';
import { api } from '@/lib/api/endpoints';
import { useAppTheme, useThemedStyles } from '@/theme/app-theme';
import type { CatalogFilters, Product, Taxonomy } from '@/types/api';

// MOBILE_BUILD16_CATALOG_REDESIGN_BATCH127
export default function CatalogScreen() {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const { bootstrap, hasFeature } = useAuth();
  const { itemCount } = useCart();
  const allowed = hasFeature('catalog');
  const { width: viewportWidth } = useWindowDimensions();
  const catalogColumns = viewportWidth >= 1600 ? 4 : viewportWidth >= 1200 ? 3 : viewportWidth >= 840 ? 2 : 1;

  const [search, setSearch] = useState('');
  const [stock, setStock] = useState(undefined as string | undefined);
  const [brandId, setBrandId] = useState('');
  const [typeId, setTypeId] = useState('');
  const [lineId, setLineId] = useState('');
  const [categoryId, setCategoryId] = useState('');
  const [sort, setSort] = useState('updated');
  const [filtersOpen, setFiltersOpen] = useState(false);

  const filters = useQuery({
    queryKey: ['catalog-filters'],
    queryFn: api.catalog.filters,
    enabled: allowed,
    staleTime: 10 * 60_000,
  });

  const productParams = useMemo(
    () => ({
      q: search || undefined,
      stock,
      sort,
      brand_id: brandId ? Number(brandId) : undefined,
      product_type_id: typeId ? Number(typeId) : undefined,
      product_line_id: lineId ? Number(lineId) : undefined,
      category_id: categoryId ? Number(categoryId) : undefined,
      per_page: 30,
    }),
    [brandId, categoryId, lineId, search, sort, stock, typeId],
  );

  const query = useQuery({
    queryKey: ['products', productParams],
    queryFn: () => api.catalog.products(productParams),
    enabled: allowed,
  });

  // MOBILE_BUILD16_CATALOG_FOCUS_FRESHNESS_BATCH134
  const refetchProducts = query.refetch;
  const refetchFilters = filters.refetch;
  useFocusEffect(useCallback(() => {
    if (!allowed) return;
    void refetchProducts();
    void refetchFilters();
  }, [allowed, refetchFilters, refetchProducts]));

  const stockOptions = useMemo(
    () => [{ value: undefined, label: 'Svi' }, ...(filters.data?.stock_filters ?? [])],
    [filters.data?.stock_filters],
  );

  const activeFilterCount = [
    stock,
    brandId,
    typeId,
    lineId,
    categoryId,
    sort !== 'updated' ? sort : '',
  ].filter(Boolean).length;

  const submitSearch = useCallback((value: string) => setSearch(value), []);
  const renderProduct = useCallback(
    ({ item }: { item: Product }) => <CatalogProductRow product={item} />,
    [],
  );

  const resetFilters = useCallback(() => {
    setStock(undefined);
    setBrandId('');
    setTypeId('');
    setLineId('');
    setCategoryId('');
    setSort('updated');
  }, []);

  if (!allowed) return <UnavailableState title="Katalog nije dostupan" />;
  if (query.isLoading) return <LoadingState label="Učitavanje kataloga…" />;
  if (query.isError) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const total = query.data?.meta?.total ?? query.data?.data.length ?? 0;

  return (
    <SafeAreaView style={styles.safe} edges={['top', 'left', 'right']}>
      <FlatList
        key={`catalog-${catalogColumns}`}
        data={query.data?.data ?? []}
        keyExtractor={catalogKeyExtractor}
        renderItem={renderProduct}
        numColumns={catalogColumns}
        columnWrapperStyle={catalogColumns > 1 ? styles.catalogRow : undefined}
        ItemSeparatorComponent={CatalogSeparator}
        initialNumToRender={7}
        maxToRenderPerBatch={7}
        windowSize={5}
        updateCellsBatchingPeriod={50}
        refreshControl={(
          <RefreshControl
            refreshing={query.isRefetching}
            onRefresh={() => void query.refetch()}
            tintColor={themeColors.primary}
          />
        )}
        showsVerticalScrollIndicator={false}
        contentContainerStyle={styles.content}
        ListHeaderComponent={(
          <View style={styles.headerWrap}>
            <View style={styles.headerLine}>
              <View style={styles.headerCopy}>
                <PageHeader title="Katalog" eyebrow="Artikli" name={bootstrap?.user.name} />
              </View>
              {hasFeature('order_create') ? (
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel={`Otvori korpu${itemCount ? `, ${itemCount} artikala` : ''}`}
                  onPress={() => router.push('/cart')}
                  style={({ pressed }) => [styles.cartButton, pressed && styles.controlPressed]}
                >
                  <Glyph name="cart" size={20} color={themeColors.primary} />
                  <Text style={styles.cartButtonText}>Korpa</Text>
                  {itemCount ? <Text style={styles.cartCount}>{itemCount}</Text> : null}
                </Pressable>
              ) : null}
            </View>

            <CatalogSearchInput value={search} onSubmit={submitSearch} />

            <View style={styles.commandRow}>
              <Pressable
                accessibilityRole="button"
                accessibilityState={{ expanded: filtersOpen }}
                onPress={() => setFiltersOpen((current) => !current)}
                style={({ pressed }) => [
                  styles.filterButton,
                  filtersOpen && styles.filterButtonActive,
                  pressed && styles.controlPressed,
                ]}
              >
                <Text style={filtersOpen ? styles.filterButtonTextActive : styles.filterButtonText}>
                  Filteri
                </Text>
                {activeFilterCount > 0 ? (
                  <Text style={styles.filterCount}>{activeFilterCount}</Text>
                ) : null}
              </Pressable>

              <View style={styles.resultSummary}>
                <Text style={styles.resultCount}>{total}</Text>
                <Text style={styles.resultLabel}>artikala</Text>
              </View>
            </View>

            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerStyle={styles.stockFilters}
            >
              {stockOptions.map((item) => {
                const active = stock === item.value;
                return (
                  <Pressable
                    key={item.label}
                    accessibilityRole="button"
                    accessibilityState={{ selected: active }}
                    onPress={() => setStock(item.value)}
                    style={({ pressed }) => [
                      styles.stockFilter,
                      active && styles.stockFilterActive,
                      pressed && styles.controlPressed,
                    ]}
                  >
                    <Text style={active ? styles.stockFilterTextActive : styles.stockFilterText}>
                      {item.label}
                    </Text>
                  </Pressable>
                );
              })}
            </ScrollView>

            {filtersOpen ? (
              <CatalogFilterPanel
                filters={filters.data}
                brandId={brandId}
                typeId={typeId}
                lineId={lineId}
                categoryId={categoryId}
                sort={sort}
                onBrandChange={(value) => {
                  setBrandId(value);
                  if (!value) return;
                  const selectedLine = filters.data?.lines.find((line) => String(line.id) === lineId);
                  if (selectedLine && String(selectedLine.brand_id) !== value) setLineId('');
                }}
                onTypeChange={setTypeId}
                onLineChange={setLineId}
                onCategoryChange={setCategoryId}
                onSortChange={setSort}
                onReset={resetFilters}
              />
            ) : null}

            {search ? (
              <View style={styles.searchContext}>
                <Text style={styles.searchContextLabel}>Pretraga</Text>
                <Text style={styles.searchContextValue} numberOfLines={1}>„{search}”</Text>
              </View>
            ) : null}
          </View>
        )}
        ListEmptyComponent={(
          <EmptyState
            title="Nema proizvoda"
            message="Promeni pretragu ili filtere kataloga."
          />
        )}
      />
    </SafeAreaView>
  );
}

const CatalogProductRow = memo(function CatalogProductRow({ product }: { product: Product }) {
  const styles = useThemedStyles(createStyles);
  const handlePress = useCallback(
    () => router.push({ pathname: '/product/[slug]', params: { slug: product.slug } }),
    [product.slug],
  );

  return (
    <View style={styles.catalogGridItem}>
      <ProductCard product={product} onPress={handlePress} showSku={false} />
    </View>
  );
});

type CatalogFilterPanelProps = {
  filters?: CatalogFilters;
  brandId: string;
  typeId: string;
  lineId: string;
  categoryId: string;
  sort: string;
  onBrandChange: (value: string) => void;
  onTypeChange: (value: string) => void;
  onLineChange: (value: string) => void;
  onCategoryChange: (value: string) => void;
  onSortChange: (value: string) => void;
  onReset: () => void;
};

function CatalogFilterPanel({
  filters,
  brandId,
  typeId,
  lineId,
  categoryId,
  sort,
  onBrandChange,
  onTypeChange,
  onLineChange,
  onCategoryChange,
  onSortChange,
  onReset,
}: CatalogFilterPanelProps) {
  const styles = useThemedStyles(createStyles);
  const lineItems = brandId
    ? (filters?.lines ?? []).filter((line) => String(line.brand_id) === brandId)
    : (filters?.lines ?? []);

  const sortOptions = [
    { value: 'updated', label: 'Poslednja izmena' },
    ...(filters?.sort_options ?? []).filter((option) => option.value !== 'updated'),
  ];

  return (
    <View style={styles.filterPanel}>
      <View style={styles.filterPanelHead}>
        <View style={styles.filterPanelCopy}>
          <Text style={styles.filterPanelEyebrow}>Napredni filteri</Text>
          <Text style={styles.filterPanelTitle}>Suzi katalog bez napuštanja liste</Text>
        </View>
        <Pressable accessibilityRole="button" onPress={onReset} hitSlop={8}>
          <Text style={styles.resetText}>Resetuj</Text>
        </Pressable>
      </View>

      <View style={styles.filterFields}>
        <SelectSheet
          label="Tip artikla"
          value={typeId}
          options={taxonomyOptions(filters?.types ?? [], 'Svi tipovi')}
          onChange={onTypeChange}
        />
        <SelectSheet
          label="Brend"
          value={brandId}
          options={taxonomyOptions(filters?.brands ?? [], 'Svi brendovi')}
          onChange={onBrandChange}
        />
        <SelectSheet
          label="Linija"
          value={lineId}
          options={taxonomyOptions(lineItems, 'Sve linije')}
          onChange={onLineChange}
        />
        <SelectSheet
          label="Kategorija"
          value={categoryId}
          options={taxonomyOptions(filters?.categories ?? [], 'Sve kategorije')}
          onChange={onCategoryChange}
        />
        <SelectSheet
          label="Sortiranje"
          value={sort}
          options={sortOptions}
          onChange={onSortChange}
        />
      </View>
    </View>
  );
}

const CatalogSearchInput = memo(function CatalogSearchInput({
  value,
  onSubmit,
}: {
  value: string;
  onSubmit: (value: string) => void;
}) {
  const { colors: themeColors } = useAppTheme();
  const styles = useThemedStyles(createStyles);
  const [draft, setDraft] = useState(value);

  useEffect(() => setDraft(value), [value]);

  const clear = () => {
    setDraft('');
    onSubmit('');
  };

  return (
    <View style={styles.searchWrap}>
      <Glyph name="search" size={21} color={themeColors.muted} />
      <TextInput
        value={draft}
        onChangeText={setDraft}
        onSubmitEditing={() => onSubmit(draft.trim())}
        placeholder="Naziv, SKU ili model…"
        placeholderTextColor={themeColors.muted}
        returnKeyType="search"
        autoCapitalize="none"
        style={styles.search}
      />
      {draft ? (
        <Pressable accessibilityRole="button" accessibilityLabel="Obriši pretragu" onPress={clear} hitSlop={8}>
          <Glyph name="close" size={19} color={themeColors.muted} />
        </Pressable>
      ) : null}
    </View>
  );
});

function taxonomyOptions(items: Taxonomy[], allLabel: string) {
  return [
    { value: '', label: allLabel },
    ...items.map((item) => ({ value: String(item.id), label: item.name })),
  ];
}

function catalogKeyExtractor(item: Product) {
  return String(item.id);
}

function CatalogSeparator() {
  return <View style={{ height: spacing.md }} />;
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    safe: { flex: 1, backgroundColor: theme.background },
    content: {
      width: '100%',
      maxWidth: 1200,
      alignSelf: 'center',
      paddingHorizontal: spacing.lg,
      paddingBottom: 120,
      backgroundColor: theme.background,
    },
    catalogRow: {
      gap: spacing.md,
    },
    catalogGridItem: {
      flex: 1,
      minWidth: 0,
    },
    headerWrap: { gap: spacing.md, marginBottom: spacing.lg },
    headerLine: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      gap: spacing.md,
    },
    headerCopy: { flex: 1 },
    cartButton: {
      minHeight: 42,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.xs,
      paddingHorizontal: spacing.md,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.md,
      backgroundColor: theme.surface,
    },
    cartButtonText: { ...typography.label, color: theme.ink },
    cartCount: {
      minWidth: 22,
      height: 22,
      paddingHorizontal: 6,
      borderRadius: radii.pill,
      backgroundColor: theme.primarySoft,
      color: theme.primary,
      textAlign: 'center',
      lineHeight: 22,
      fontSize: 11,
      fontWeight: '800',
      overflow: 'hidden',
    },
    searchWrap: {
      minHeight: 52,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.md,
      paddingHorizontal: spacing.md,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surface,
    },
    search: { flex: 1, color: theme.ink, fontSize: 16, paddingVertical: spacing.sm },
    commandRow: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    filterButton: {
      minHeight: 40,
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.sm,
      paddingHorizontal: spacing.md,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.md,
      backgroundColor: theme.surface,
    },
    filterButtonActive: {
      borderColor: theme.primary,
      backgroundColor: theme.primarySoft,
    },
    filterButtonText: { ...typography.label, color: theme.ink },
    filterButtonTextActive: { ...typography.label, color: theme.primary },
    filterCount: {
      minWidth: 21,
      height: 21,
      paddingHorizontal: 6,
      borderRadius: radii.pill,
      backgroundColor: theme.primary,
      color: theme.onPrimary,
      textAlign: 'center',
      lineHeight: 21,
      fontSize: 11,
      fontWeight: '800',
      overflow: 'hidden',
    },
    resultSummary: { flexDirection: 'row', alignItems: 'baseline', gap: spacing.xs },
    resultCount: { ...typography.h2, color: theme.ink },
    resultLabel: { ...typography.small, color: theme.muted },
    stockFilters: { gap: spacing.sm, paddingRight: spacing.lg },
    stockFilter: {
      minHeight: 36,
      justifyContent: 'center',
      paddingHorizontal: spacing.md,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.pill,
      backgroundColor: theme.surface,
    },
    stockFilterActive: {
      borderColor: theme.primary,
      backgroundColor: theme.primarySoft,
    },
    stockFilterText: { ...typography.small, color: theme.muted, fontWeight: '700' },
    stockFilterTextActive: { ...typography.small, color: theme.primary, fontWeight: '800' },
    filterPanel: {
      gap: spacing.lg,
      padding: spacing.lg,
      borderWidth: 1,
      borderColor: theme.line,
      borderRadius: radii.lg,
      backgroundColor: theme.surface,
    },
    filterPanelHead: {
      flexDirection: 'row',
      alignItems: 'flex-start',
      justifyContent: 'space-between',
      gap: spacing.md,
    },
    filterPanelCopy: { flex: 1, gap: 3 },
    filterPanelEyebrow: {
      ...typography.small,
      color: theme.primary,
      textTransform: 'uppercase',
      letterSpacing: 0.8,
      fontWeight: '800',
    },
    filterPanelTitle: { ...typography.label, color: theme.ink },
    resetText: { ...typography.label, color: theme.primary, paddingVertical: spacing.xs },
    filterFields: { gap: spacing.md },
    searchContext: {
      flexDirection: 'row',
      alignItems: 'center',
      gap: spacing.sm,
      paddingTop: spacing.xs,
    },
    searchContextLabel: { ...typography.small, color: theme.muted },
    searchContextValue: { ...typography.small, color: theme.ink, flex: 1, fontWeight: '700' },
    controlPressed: { opacity: 0.84, transform: [{ scale: 0.985 }] },
  });
}
