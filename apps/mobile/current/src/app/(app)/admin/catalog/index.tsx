import { useMemo, useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { router, type Href } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { SelectSheet } from '@/components/ui/select-sheet';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import {
  apiAdminCatalog,
  type AdminCatalogProductListParams,
  type AdminCatalogProductSummary,
} from '@/features/admin/catalog-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

type CatalogMode = 'active' | 'archived';
type CatalogStatusFilter = '' | 'draft' | 'active' | 'inactive';

function money(value: number, currency: string): string {
  return `${new Intl.NumberFormat('sr-RS', { maximumFractionDigits: 2 }).format(value)} ${currency}`;
}

function ProductCard({ product, onOpen, styles }: {
  product: AdminCatalogProductSummary;
  onOpen: () => void;
  styles: ReturnType<typeof createStyles>;
}) {
  return (
    <Card style={styles.card}>
      <View style={styles.cardHead}>
        <View style={styles.flexOne}>
          <Text style={styles.productName}>{product.name}</Text>
          <Text style={styles.muted}>{product.sku}{product.model_name ? ` · ${product.model_name}` : ''}</Text>
        </View>
        <Text style={styles.status}>{product.is_archived ? 'ARHIVIRAN' : product.status.toUpperCase()}</Text>
      </View>
      <View style={styles.metaGrid}>
        <Text style={styles.meta}>Tip: {product.product_type?.name ?? '-'}</Text>
        <Text style={styles.meta}>Brend: {product.brand?.name ?? '-'}</Text>
        <Text style={styles.meta}>Linija: {product.product_line?.name ?? '-'}</Text>
        <Text style={styles.meta}>Lager: {product.stock_quantity}</Text>
        <Text style={styles.meta}>Cena: {money(product.price_amount, product.price_currency)}</Text>
        <Text style={styles.meta}>Slike: {product.images_count}</Text>
      </View>
      <Button variant="secondary" onPress={onOpen}>
        {product.is_archived ? 'Otvori / vrati iz arhive' : 'Otvori / izmeni'}
      </Button>
    </Card>
  );
}

// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8
// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8_V2
// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8_V3
// MOBILE_V0_8_PRODUCT_EDIT_ARCHIVE_RESTORE_BATCH8_V4
export default function AdminCatalogIndexScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { bootstrap, can } = useAuth();
  const allowed = can('catalog.manage_products');

  const [mode, setMode] = useState<CatalogMode> ('active');
  const [queryText, setQueryText] = useState('');
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState<CatalogStatusFilter> ('');
  const [page, setPage] = useState(1);

  const params: AdminCatalogProductListParams = {
    q: search || undefined,
    status: mode === 'active' && status ? status : undefined,
    page,
    per_page: 20,
  };

  const query = useQuery({
    queryKey: ['admin', 'catalog', 'products', mode, search, status, page],
    queryFn: () => apiAdminCatalog.list(params, mode === 'archived'),
    enabled: allowed,
  });

  if (!allowed) return <UnavailableState title="Administracija kataloga nije dostupna" />;
  if (query.isLoading) return <LoadingState label="Učitavanje artikala…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const response = query.data;

  return (
    <Screen contentStyle={styles.content} keyboardShouldPersistTaps="handled">
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Administracija</Text>
      </Pressable>

      <PageHeader
        title="Katalog artikala"
        eyebrow="Admin · Artikli"
        name={bootstrap?.user.name}
      />

      <View style={styles.actions}>
        <Button variant="secondary" onPress={() => router.push('/admin/catalog/create')}>
          Dodaj artikal
        </Button>
        <Button variant="ghost" onPress={() => void query.refetch()}>
          {query.isFetching ? 'Osvežavanje…' : 'Osveži'}
        </Button>
      </View>

      <Card style={styles.filters}>
        <SelectSheet
          label="Prikaz"
          value={mode}
          options={[
            { value: 'active', label: 'Operativni artikli' },
            { value: 'archived', label: 'Arhivirani artikli' },
          ]}
          onChange={(value) => {
            setMode(value === 'archived' ? 'archived' : 'active');
            setStatus('');
            setPage(1);
          }}
        />

        {mode === 'active' ? (
          <SelectSheet
            label="Status"
            value={status}
            options={[
              { value: '', label: 'Svi statusi' },
              { value: 'draft', label: 'Nacrt' },
              { value: 'active', label: 'Aktivan' },
              { value: 'inactive', label: 'Neaktivan' },
            ]}
            onChange={(value) => {
              setStatus(value === 'draft' || value === 'active' || value === 'inactive' ? value : '');
              setPage(1);
            }}
          />
        ) : null}

        <TextField
          label="Pretraga"
          value={queryText}
          onChangeText={setQueryText}
          placeholder="Naziv, SKU ili model"
        />
        <View style={styles.actions}>
          <Button
            variant="secondary"
            onPress={() => {
              setSearch(queryText.trim());
              setPage(1);
            }}
          >
            Pretraži
          </Button>
          <Button
            variant="ghost"
            onPress={() => {
              setQueryText('');
              setSearch('');
              setStatus('');
              setPage(1);
            }}
          >
            Resetuj
          </Button>
        </View>
      </Card>

      <Text style={styles.summary}>
        {mode === 'archived' ? 'Arhiviranih' : 'Artikala'}: {response.meta.total}
      </Text>

      {response.data.length === 0 ? (
        <Card muted>
          <Text style={styles.muted}>Nema artikala za izabrane filtere.</Text>
        </Card>
      ) : (
        <View style={styles.list}>
          {response.data.map((product) => (
            <ProductCard
              key={product.id}
              product={product}
              styles={styles}
              onOpen={() => router.push({ pathname: '/admin/catalog/[id]', params: { id: String(product.id) } } as Href)}
            />
          ))}
        </View>
      )}

      <View style={styles.pagination}>
        <Button
          variant="ghost"
          disabled={response.meta.current_page <= 1}
          onPress={() => setPage((current) => Math.max(1, current - 1))}
        >
          Prethodna
        </Button>
        <Text style={styles.pageText}>{response.meta.current_page} / {Math.max(response.meta.last_page, 1)}</Text>
        <Button
          variant="ghost"
          disabled={response.meta.current_page >= response.meta.last_page}
          onPress={() => setPage((current) => current + 1)}
        >
          Sledeća
        </Button>
      </View>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg },
    back: { ...typography.label, color: theme.primary },
    actions: { gap: spacing.sm },
    filters: { gap: spacing.md },
    summary: { ...typography.body, color: theme.muted },
    list: { gap: spacing.md },
    card: { gap: spacing.md },
    cardHead: { flexDirection: 'row', gap: spacing.md, alignItems: 'flex-start' },
    flexOne: { flex: 1, minWidth: 0 },
    productName: { ...typography.h3, color: theme.ink },
    muted: { ...typography.body, color: theme.muted },
    status: { ...typography.small, color: theme.primary, fontWeight: '800' },
    metaGrid: { gap: spacing.xs },
    meta: { ...typography.body, color: theme.ink },
    pagination: { gap: spacing.sm, alignItems: 'center' },
    pageText: { ...typography.label, color: theme.muted },
  });
}
