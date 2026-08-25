import { useMemo, useState } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { router, useLocalSearchParams, type Href } from 'expo-router';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { PageHeader } from '@/components/layout/page-header';
import { Screen } from '@/components/layout/screen';
import { useAppFeedback } from '@/components/ui/app-feedback';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ErrorState, LoadingState, UnavailableState } from '@/components/ui/states';
import { TextField } from '@/components/ui/text-field';
import { spacing, typography, type AppColors } from '@/constants/theme';
import { adminQueryKeys } from '@/features/admin/admin-query-keys';
import { apiAdminCatalog } from '@/features/admin/catalog-admin-api';
import { apiCatalogAdvanced, type CatalogCloneInput } from '@/features/admin/catalog-advanced-admin-api';
import { useAuth } from '@/features/auth/auth-provider';
import { ApiError } from '@/lib/api/client';
import { useAppTheme } from '@/theme/app-theme';

function errorMessage(error: unknown): string {
  if (error instanceof ApiError) return error.firstFieldError() ?? error.message;
  return error instanceof Error ? error.message : 'Operacija nije uspela.';
}

function ToggleRow({ label, value, onChange }: { label: string; value: boolean; onChange: (value: boolean) => void }) {
  return (
    <View style={{ gap: spacing.xs }}>
      <Text>{label}</Text>
      <Button variant={value ? 'secondary' : 'ghost'} onPress={() => onChange(!value)}>
        {value ? 'Da' : 'Ne'}
      </Button>
    </View>
  );
}

// MOBILE_V1_0_CATALOG_ADVANCED_PARITY_BATCH35
export default function AdminCatalogCloneScreen() {
  const { colors: theme } = useAppTheme();
  const styles = useMemo(() => createStyles(theme), [theme]);
  const feedback = useAppFeedback();
  const client = useQueryClient();
  const { can, bootstrap } = useAuth();
  const params = useLocalSearchParams<{ id?: string | string[] }>();
  const rawId = Array.isArray(params.id) ? params.id[0] : params.id;
  const productId = Number(rawId);
  const validId = Number.isInteger(productId) && productId > 0;
  const allowed = can('catalog.manage_products');

  const detailQuery = useQuery({
    queryKey: adminQueryKeys.catalogAdvancedProduct(productId),
    queryFn: () => apiAdminCatalog.detail(productId),
    enabled: allowed && validId,
  });

  const [name, setName] = useState('');
  const [copyBasic, setCopyBasic] = useState(true);
  const [copySpecifications, setCopySpecifications] = useState(true);
  const [copyPrice, setCopyPrice] = useState(true);
  const [copyDescription, setCopyDescription] = useState(true);
  const [copyNotes, setCopyNotes] = useState(false);
  const [copyImages, setCopyImages] = useState(false);
  const [copyWarrantyRules, setCopyWarrantyRules] = useState(false);
  const [regenerateName, setRegenerateName] = useState(false);
  const [previewName, setPreviewName] = useState<string | null>(null);

  const product = detailQuery.data?.data;

  const previewMutation = useMutation({
    mutationFn: async () => {
      if (!product?.product_type_id) throw new Error('Artikal nema tip proizvoda potreban za pregled naziva.');
      return apiCatalogAdvanced.namePreview({
        product_type_id: product.product_type_id,
        brand_id: product.brand_id ?? undefined,
        product_line_id: product.product_line_id ?? undefined,
        model_name: product.model_name ?? undefined,
        sku: product.sku,
        specs: product.specs,
        spec_details: product.spec_details,
      });
    },
    onSuccess: (response) => setPreviewName(response.data.name),
    onError: (error) => feedback.notify({ tone: 'warning', title: 'Pregled naziva nije uspeo', message: errorMessage(error) }),
  });

  const cloneMutation = useMutation({
    mutationFn: (input: CatalogCloneInput) => apiCatalogAdvanced.clone(productId, input),
    onSuccess: async (response) => {
      await client.invalidateQueries({ queryKey: adminQueryKeys.catalogAdvancedRoot() });
      feedback.notify({ tone: 'success', title: 'Artikal je kloniran', message: `${response.data.name} · ${response.data.sku}` });
      router.replace({ pathname: '/admin/catalog/[id]', params: { id: String(response.data.id) } } as Href);
    },
    onError: (error) => feedback.notify({ tone: 'danger', title: 'Kloniranje nije uspelo', message: errorMessage(error) }),
  });

  if (!allowed) return <UnavailableState title="Kloniranje artikla nije dostupno" />;
  if (!validId) return <UnavailableState title="Artikal nije validan" />;
  if (detailQuery.isLoading) return <LoadingState label="Učitavanje izvornog artikla…" />;
  if (detailQuery.isError || !product) return <ErrorState error={detailQuery.error} onRetry={() => void detailQuery.refetch()} />;

  const submit = () => cloneMutation.mutate({
    name: name.trim() || undefined,
    copy_basic: copyBasic,
    copy_specifications: copySpecifications,
    copy_price: copyPrice,
    copy_description: copyDescription,
    copy_notes: copyNotes,
    copy_images: copyImages,
    copy_warranty_rules: copyWarrantyRules,
    regenerate_name: regenerateName,
  });

  return (
    <Screen contentStyle={styles.content} keyboardShouldPersistTaps="handled">
      <Pressable accessibilityRole="button" onPress={() => router.back()}><Text style={styles.back}>‹ Artikal</Text></Pressable>
      <PageHeader title="Kloniraj artikal" eyebrow="Admin · Katalog" name={bootstrap?.user.name} />
      <Card style={styles.card}>
        <Text style={styles.title}>{product.name}</Text>
        <Text style={styles.muted}>{product.sku} · novi klon dobija novi SKU, status Nacrt i lager 0</Text>
      </Card>
      <TextField label="Ručni naziv klona (opciono)" value={name} onChangeText={setName} placeholder="Prazno = izvorni naziv + kopija" />
      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Šta kopirati</Text>
        <ToggleRow label="Osnovni podaci" value={copyBasic} onChange={setCopyBasic} />
        <ToggleRow label="Specifikacije" value={copySpecifications} onChange={setCopySpecifications} />
        <ToggleRow label="Cena i provizija" value={copyPrice} onChange={setCopyPrice} />
        <ToggleRow label="Opis" value={copyDescription} onChange={setCopyDescription} />
        <ToggleRow label="Interne napomene" value={copyNotes} onChange={setCopyNotes} />
        <ToggleRow label="Fotografije" value={copyImages} onChange={setCopyImages} />
        <ToggleRow label="Pravila garancije" value={copyWarrantyRules} onChange={setCopyWarrantyRules} />
        <ToggleRow label="Regeneriši naziv iz šablona" value={regenerateName} onChange={setRegenerateName} />
      </Card>
      <Card style={styles.card}>
        <Text style={styles.sectionTitle}>Name template pregled</Text>
        <Button variant="secondary" loading={previewMutation.isPending} onPress={() => previewMutation.mutate()}>Pregledaj naziv iz šablona</Button>
        {previewName !== null ? <Text style={styles.preview}>{previewName || 'Šablon trenutno ne formira naziv.'}</Text> : null}
      </Card>
      <Button loading={cloneMutation.isPending} onPress={submit}>Kloniraj artikal</Button>
    </Screen>
  );
}

function createStyles(theme: AppColors) {
  return StyleSheet.create({
    content: { gap: spacing.lg, paddingBottom: 140 },
    back: { ...typography.label, color: theme.primary, paddingVertical: spacing.sm },
    card: { gap: spacing.md },
    title: { ...typography.h2, color: theme.ink },
    sectionTitle: { ...typography.h3, color: theme.ink },
    muted: { ...typography.body, color: theme.muted },
    preview: { ...typography.body, color: theme.ink },
  });
}
