import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Pill } from '@/components/ui/pill';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiAdminCatalog, type AdminPurchaseCostProduct } from '@/features/admin/catalog-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { useAppTheme } from '@/theme/app-theme';

// MOBILE_V1_0_ADMIN_PURCHASE_COST_PARITY_BATCH19_V4
export default function AdminPurchaseCostsScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const { bootstrap, can } = useAuth();
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const isSuperAdmin = bootstrap?.user.role?.slug === 'superadmin';
  const allowed = isSuperAdmin && can('catalog.manage_products');
  const [showAll, setShowAll] = useState(false);
  const [draftCosts, setDraftCosts] = useState<Record<string, string>> ({});

  const query = useQuery({
    queryKey: adminQueryKeys.purchaseCosts(showAll),
    queryFn: () => apiAdminCatalog.purchaseCosts(showAll),
    enabled: allowed,
  });

  const mutation = useMutation({
    mutationFn: apiAdminCatalog.updatePurchaseCosts,
    onSuccess: async (response) => {
      feedback.notify({ tone: 'success', title: 'Nabavne cene su sačuvane', message: response.message });
      setDraftCosts({});
      await Promise.all([
        client.invalidateQueries({ queryKey: ['admin', 'catalog', 'purchase-costs'] }),
        client.invalidateQueries({ queryKey: adminQueryKeys.foundation() }),
        client.invalidateQueries({ queryKey: ['admin', 'catalog'] }),
      ]);
    },
    onError: (error) => feedback.notify({
      tone: 'danger',
      title: 'Nabavne cene nisu sačuvane',
      message: error instanceof Error ? error.message : 'Server je odbio izmenu.',
    }),
  });

  if (!allowed) return <UnavailableState title="Nabavne cene su dostupne samo Super Administratoru" />;
  if (query.isLoading) return <LoadingState label="Učitavanje nabavnih cena…" />;
  if (query.isError || !query.data) return <ErrorState error={query.error} onRetry={() => void query.refetch()} />;

  const data = query.data;

  function setCost(productId: number, value: string): void {
    setDraftCosts((current) => ({ ...current, [String(productId)]: value }));
  }

  function save(): void {
    const costs: Record<string, number> = {};
    for (const [productId, raw] of Object.entries(draftCosts)) {
      const text = raw.trim().replace(',', '.');
      if (!text) continue;
      const value = Number(text);
      if (!Number.isFinite(value) || value < 0.01 || value > 9999999999.99) {
        feedback.notify({
          tone: 'warning',
          title: 'Nabavna cena nije validna',
          message: 'Svaka izmenjena cena mora biti između 0,01 i 9.999.999.999,99 RSD.',
        });
        return;
      }
      costs[productId] = Math.round(value * 100) / 100;
    }

    if (Object.keys(costs).length === 0) {
      feedback.notify({ tone: 'warning', title: 'Nema promena', message: 'Unesi najmanje jednu nabavnu cenu.' });
      return;
    }
    if (Object.keys(costs).length > data.capabilities.max_batch) {
      feedback.notify({ tone: 'warning', title: 'Previše stavki', message: `Maksimalno ${data.capabilities.max_batch} artikala po čuvanju.` });
      return;
    }
    mutation.mutate(costs);
  }

  return (
    <Screen contentStyle={styles.content}>
      <Pressable accessibilityRole="button" onPress={() => router.back()}>
        <Text style={styles.back}>‹ Katalog i lager</Text>
      </Pressable>
      <PageHeader title="Nabavne cene" eyebrow="SuperAdmin · Katalog" name={bootstrap?.user.name} />
      <Text style={styles.copy}>
        Brzi masovni unos nabavnih cena u RSD. Isti server servis, validacija, transakcija i audit koriste se i u Laravel CMS-u i u Mobile aplikaciji.
      </Text>

      <View style={styles.metrics}>
        <Card style={styles.metricCard}>
          <Text style={styles.metricValue}>{data.missing_total}</Text>
          <Text style={styles.metricLabel}>artikala bez nabavne cene</Text>
        </Card>
        <Card style={styles.metricCard}>
          <Text style={styles.metricValue}>{data.missing_positive_stock}</Text>
          <Text style={styles.metricLabel}>bez cene sa pozitivnim lagerom</Text>
        </Card>
      </View>

      <Card style={styles.toolbar}>
        <View style={styles.rowBetween}>
          <View style={styles.flex}>
            <Text style={styles.sectionTitle}>{showAll ? 'Svi artikli' : 'Artikli bez nabavne cene'}</Text>
            <Text style={styles.muted}>{data.products.length} prikazano</Text>
          </View>
          <Pill tone={data.missing_total === 0 ? 'success' : 'warning'}>
            {data.missing_total === 0 ? 'KOMPLETNO' : 'DOPUNA POTREBNA'}
          </Pill>
        </View>
        <View style={styles.actions}>
          <Button variant="secondary" onPress={() => { setDraftCosts({}); setShowAll((value) => !value); }}>
            {showAll ? 'Prikaži samo bez cene' : 'Prikaži sve'}
          </Button>
          <Button variant="secondary" onPress={() => void query.refetch()}>
            {query.isFetching ? 'Osvežavanje…' : 'Osveži'}
          </Button>
        </View>
      </Card>

      {data.products.length === 0 ? (
        <Card><Text style={styles.muted}>Nema artikala za izabrani prikaz.</Text></Card>
      ) : data.products.map((product) => (
        <PurchaseCostCard
          key={product.id}
          product={product}
          draftValue={draftCosts[String(product.id)]}
          onChange={(value) => setCost(product.id, value)}
          styles={styles}
        />
      ))}

      <Card style={styles.saveCard}>
        <Text style={styles.muted}>Izmenjeno polja: {Object.keys(draftCosts).length}</Text>
        <Button onPress={save} loading={mutation.isPending}>
          {mutation.isPending ? 'Čuvanje…' : 'Sačuvaj nabavne cene'}
        </Button>
      </Card>
    </Screen>
  );
}

function PurchaseCostCard({
  product,
  draftValue,
  onChange,
  styles,
}: {
  product: AdminPurchaseCostProduct;
  draftValue: string | undefined;
  onChange: (value: string) => void;
  styles: ReturnType<typeof createStyles>;
}) {
  const current = product.purchase_price_rsd === null ? '' : String(product.purchase_price_rsd);
  return (
    <Card style={styles.productCard}>
      <View style={styles.rowBetween}>
        <View style={styles.flex}>
          <Text style={styles.productName}>{product.name}</Text>
          <Text style={styles.muted}>SKU: {product.sku}</Text>
        </View>
        <Pill tone={product.purchase_price_rsd && product.purchase_price_rsd > 0 ? 'success' : 'warning'}>
          {product.purchase_price_rsd && product.purchase_price_rsd > 0 ? 'IMA CENU' : 'NEDOSTAJE'}
        </Pill>
      </View>
      <Text style={styles.meta}>Lager: {product.stock_quantity}</Text>
      <Text style={styles.meta}>Prodajna cena: {formatCatalogPrice(product)}</Text>
      <TextField
        label="Nabavna cena (RSD)"
        value={draftValue ?? current}
        onChangeText={onChange}
        keyboardType="decimal-pad"
        placeholder="0,00"
      />
    </Card>
  );
}

function formatCatalogPrice(product: AdminPurchaseCostProduct): string {
  return `${Number(product.price_amount).toLocaleString('sr-RS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${product.price_currency}`;
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: spacing.xl },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    copy: { ...typography.body, color: theme.muted },
    metrics: { gap: spacing.sm },
    metricCard: { gap: spacing.xs },
    metricValue: { ...typography.h2, color: theme.ink },
    metricLabel: { ...typography.small, color: theme.muted },
    toolbar: { gap: spacing.md },
    productCard: { gap: spacing.sm },
    saveCard: { gap: spacing.sm },
    rowBetween: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start', gap: spacing.md },
    flex: { flex: 1, minWidth: 0 },
    actions: { flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm },
    sectionTitle: { ...typography.h3, color: theme.ink },
    productName: { ...typography.h3, color: theme.ink },
    muted: { ...typography.small, color: theme.muted },
    meta: { ...typography.body, color: theme.muted },
  });
}
